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
 * Sidebar "Diskusi Faskes Dekat Anda", catatan laporan, dan badge jumlah laporan admin.
 */
class ForumLaporanQaTest extends TestCase
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

    private function makeFaskes(string $nama, float $lat, float $lng): Faskes
    {
        return Faskes::create([
            'nama_faskes' => $nama, 'jenis_faskes' => 'Rumah Sakit', 'kota_kabupaten' => 'Kota Bogor',
            'alamat_faskes' => 'Jl. '.$nama, 'latitude' => $lat, 'longitude' => $lng,
        ]);
    }

    private function makeDiscussion(User $author, ?Faskes $mention, string $judul, string $status = 'active'): Diskusi
    {
        return Diskusi::create([
            'id_user' => $author->id_user,
            'judul_diskusi' => $judul,
            'isi_diskusi' => 'Isi '.$judul,
            'status_diskusi' => $status,
            'id_faskes_mention' => $mention?->id_faskes,
        ]);
    }

    public function test_nearby_discussions_lists_title_and_faskes_name_sorted_by_likes(): void
    {
        $author = $this->makeUser('a@example.com');
        $liker = $this->makeUser('b@example.com');
        $near = $this->makeFaskes('RS Dekat', -6.600, 106.800);
        $near2 = $this->makeFaskes('RS Dekat Dua', -6.605, 106.805);
        $far = $this->makeFaskes('RS Jauh', -7.500, 110.000);

        $d1 = $this->makeDiscussion($author, $near, 'Antrean IGD');
        $d2 = $this->makeDiscussion($author, $near2, 'Parkir penuh');
        $this->makeDiscussion($author, $far, 'Diskusi jauh');
        $this->makeDiscussion($author, $near, 'Sudah di-takedown', 'deleted');
        $this->makeDiscussion($author, null, 'Tanpa mention');
        DB::table('like_diskusi')->insert(['id_diskusi' => $d2->id_diskusi, 'id_user' => $liker->id_user]);

        $res = $this->getJson('/api/forum/diskusi-terdekat?lat=-6.6&lng=106.8')
            ->assertOk()
            ->assertJsonPath('berbasis_lokasi', true)
            ->assertJsonCount(2, 'data');

        $this->assertSame($d2->id_diskusi, $res->json('data.0.id_diskusi'));
        $this->assertSame('Parkir penuh', $res->json('data.0.judul_diskusi'));
        $this->assertSame('RS Dekat Dua', $res->json('data.0.faskes.nama_faskes'));
        $this->assertSame(1, $res->json('data.0.likes_count'));
        $this->assertSame($d1->id_diskusi, $res->json('data.1.id_diskusi'));
    }

    public function test_nearby_discussions_without_location_returns_popular_mentions(): void
    {
        $author = $this->makeUser('a@example.com');
        $this->makeDiscussion($author, $this->makeFaskes('RS Jauh', -7.5, 110.0), 'Diskusi jauh');

        $this->getJson('/api/forum/diskusi-terdekat')
            ->assertOk()
            ->assertJsonPath('berbasis_lokasi', false)
            ->assertJsonPath('data.0.faskes.nama_faskes', 'RS Jauh')
            ->assertJsonPath('data.0.faskes.distance_km', null);

        $this->getJson('/api/forum/diskusi-terdekat?lat=-6.6')->assertStatus(422);
    }

    public function test_reports_store_optional_notes(): void
    {
        $author = $this->makeUser('a@example.com');
        $reporter = $this->makeUser('r@example.com');
        $discussion = $this->makeDiscussion($author, null, 'Judul');
        $comment = TanggapanDiskusi::create([
            'id_diskusi' => $discussion->id_diskusi,
            'id_user' => $author->id_user,
            'isi_tanggapan' => 'Komentar',
        ]);

        Sanctum::actingAs($reporter);

        $this->postJson("/api/discussions/{$discussion->id_diskusi}/report", [
            'reason' => 'Spam',
            'notes' => 'Isinya promosi obat',
        ])->assertSuccessful();
        $this->assertSame('Isinya promosi obat', DB::table('laporan_diskusi')->value('catatan_tambahan'));

        $this->postJson("/api/discussion-comments/{$comment->id_tanggapan}/report", [
            'reason' => 'Kata kasar',
        ])->assertSuccessful();
        $this->assertNull(DB::table('laporan_tanggapan')->value('catatan_tambahan'));

        $this->postJson("/api/discussion-comments/{$comment->id_tanggapan}/report", [
            'reason' => 'Kata kasar',
            'notes' => str_repeat('a', 501),
        ])->assertStatus(422);
    }

    public function test_admin_pending_counts(): void
    {
        $author = $this->makeUser('a@example.com');
        $reporter = $this->makeUser('r@example.com');
        $discussion = $this->makeDiscussion($author, null, 'Judul');
        DB::table('laporan_diskusi')->insert([
            ['id_diskusi' => $discussion->id_diskusi, 'id_user' => $reporter->id_user, 'alasan_laporan' => 'Spam', 'status' => 'pending'],
            ['id_diskusi' => $discussion->id_diskusi, 'id_user' => $author->id_user, 'alasan_laporan' => 'Spam', 'status' => 'resolved'],
        ]);

        $this->getJson('/api/admin/pending-counts')->assertUnauthorized();

        Sanctum::actingAs($reporter);
        $this->getJson('/api/admin/pending-counts')->assertForbidden();

        Sanctum::actingAs($this->makeUser('admin@example.com', 'admin'));
        $this->getJson('/api/admin/pending-counts')
            ->assertOk()
            ->assertJsonPath('data.discussion_reports', 1)
            ->assertJsonPath('data.total', 1);
    }
}
