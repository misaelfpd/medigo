<?php

namespace Tests\Feature;

use App\Models\Diskusi;
use App\Models\Faskes;
use App\Models\TanggapanDiskusi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Regression test untuk bug backend yang diperbaiki.
 */
class BackendBugFixesTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $email, string $role = 'user'): User
    {
        return User::create([
            'nama_user' => 'User '.$email,
            'email_user' => $email,
            'password_user' => 'secret-password',
            'role_user' => $role,
        ]);
    }

    private function makeDiscussion(User $author, string $title = 'Judul'): Diskusi
    {
        return Diskusi::create([
            'id_user' => $author->id_user,
            'judul_diskusi' => $title,
            'isi_diskusi' => 'Isi',
            'status_diskusi' => 'active',
        ]);
    }

    public function test_review_without_body_does_not_crash(): void
    {
        // Kolom isi_komentar NOT NULL; dulu body kosong bikin error 500
        $faskes = Faskes::create(['nama_faskes' => 'Klinik A', 'jenis_faskes' => 'Klinik', 'kota_kabupaten' => 'Bogor', 'alamat_faskes' => 'Jl. A']);
        Sanctum::actingAs($this->makeUser('u@example.com'));

        $this->postJson("/api/faskes/{$faskes->id_faskes}/comments", ['rating' => 5])
            ->assertCreated()
            ->assertJsonPath('data.rating', 5);

        $this->assertDatabaseHas('komentar', ['id_faskes' => $faskes->id_faskes, 'isi_komentar' => '']);
    }

    public function test_comment_list_handles_invalid_page(): void
    {
        $faskes = Faskes::create(['nama_faskes' => 'Klinik A', 'jenis_faskes' => 'Klinik', 'kota_kabupaten' => 'Bogor', 'alamat_faskes' => 'Jl. A']);

        $this->getJson("/api/faskes/{$faskes->id_faskes}/comments?page=0&per_page=0")
            ->assertOk()
            ->assertJsonPath('data.current_page', 1);
    }

    public function test_discussion_detail_knows_logged_in_user(): void
    {
        $user = $this->makeUser('u@example.com');
        $discussion = $this->makeDiscussion($user);
        $token = $user->createToken('t')->plainTextToken;

        $this->withToken($token)
            ->getJson("/api/discussions/{$discussion->id_diskusi}")
            ->assertOk()
            ->assertJsonPath('data.logged_in_user_id', $user->id_user);
    }

    public function test_my_discussions_hides_deleted_ones(): void
    {
        $user = $this->makeUser('u@example.com');
        $this->makeDiscussion($user, 'Masih aktif');
        $this->makeDiscussion($user, 'Sudah dihapus')->update(['status_diskusi' => 'deleted']);
        Sanctum::actingAs($user);

        $this->getJson('/api/user/discussions')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.judul_diskusi', 'Masih aktif');
    }

    public function test_takedown_by_discussion_id_does_not_hit_other_discussion(): void
    {
        // Dulu id diskusi dicoba sebagai id laporan dulu -> diskusi lain yang ter-takedown
        $admin = $this->makeUser('admin@example.com', 'admin');
        $author = $this->makeUser('author@example.com');
        $reporter = $this->makeUser('reporter@example.com');

        $first = $this->makeDiscussion($author, 'Diskusi 1');
        $second = $this->makeDiscussion($author, 'Diskusi 2');
        $third = $this->makeDiscussion($author, 'Diskusi 3');

        // Laporan #1 & #2 milik diskusi 3, jadi id laporan 2 "bentrok" dengan id diskusi 2
        foreach ([1, 2] as $_) {
            DB::table('laporan_diskusi')->insert([
                'id_diskusi' => $third->id_diskusi,
                'id_user' => $reporter->id_user,
                'alasan_laporan' => 'spam',
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Sanctum::actingAs($admin);
        $this->postJson("/api/admin/discussions/{$second->id_diskusi}/takedown")->assertOk();

        $this->assertSame('deleted', $second->fresh()->status_diskusi);
        $this->assertSame('active', $third->fresh()->status_diskusi);
        $this->assertSame('active', $first->fresh()->status_diskusi);

        // Rute berbasis id laporan tetap jalan
        $this->postJson('/api/admin/discussion-reports/1/takedown')->assertOk();
        $this->assertSame('deleted', $third->fresh()->status_diskusi);
    }

    public function test_reply_parent_must_belong_to_same_discussion(): void
    {
        $user = $this->makeUser('u@example.com');
        $a = $this->makeDiscussion($user, 'A');
        $b = $this->makeDiscussion($user, 'B');
        $commentInA = TanggapanDiskusi::create(['id_diskusi' => $a->id_diskusi, 'id_user' => $user->id_user, 'isi_tanggapan' => 'halo']);
        Sanctum::actingAs($user);

        $this->postJson("/api/discussions/{$b->id_diskusi}/comments", ['body' => 'balas', 'parent_id' => $commentInA->id_tanggapan])
            ->assertStatus(422);

        $this->postJson("/api/discussions/{$a->id_diskusi}/comments", ['body' => 'balas', 'parent_id' => $commentInA->id_tanggapan])
            ->assertCreated();
    }

    public function test_admin_cannot_block_self_or_other_admin(): void
    {
        $admin = $this->makeUser('admin@example.com', 'admin');
        $otherAdmin = $this->makeUser('admin2@example.com', 'admin');
        $user = $this->makeUser('u@example.com');
        Sanctum::actingAs($admin);

        $this->postJson("/api/admin/forum/users/{$admin->id_user}/block")->assertStatus(422);
        $this->postJson("/api/admin/forum/users/{$otherAdmin->id_user}/block")->assertStatus(422);
        $this->postJson("/api/admin/forum/users/{$user->id_user}/block")->assertOk();

        $this->assertSame('admin', $admin->fresh()->role_user);
        $this->assertSame('blocked', $user->fresh()->role_user);
    }

    public function test_moderation_page_does_not_insert_fake_data(): void
    {
        // Dulu halaman moderasi membuat user palsu (password123) + diskusi contoh saat laporan kosong
        Sanctum::actingAs($this->makeUser('admin@example.com', 'admin'));

        $this->getJson('/api/admin/forum/moderation')
            ->assertOk()
            ->assertJsonPath('stats.auto_actions', 0)
            ->assertJsonCount(0, 'data');

        $this->assertDatabaseMissing('user', ['email_user' => 'angry.patient@medigo.id']);
        $this->assertSame(0, DB::table('diskusi')->count());
    }

    public function test_admin_report_on_missing_item_returns_404(): void
    {
        Sanctum::actingAs($this->makeUser('admin@example.com', 'admin'));

        $this->postJson('/api/admin/forum/discussions/999/report')->assertNotFound();
        $this->postJson('/api/admin/forum/comments/999/report')->assertNotFound();
    }
}
