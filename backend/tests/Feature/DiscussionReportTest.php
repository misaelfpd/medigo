<?php

namespace Tests\Feature;

use App\Models\Diskusi;
use App\Models\TanggapanDiskusi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DiscussionReportTest extends TestCase
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

    public function test_user_can_report_discussion(): void
    {
        $author = $this->makeUser('author@example.com');
        $reporter = $this->makeUser('reporter@example.com');

        $discussion = Diskusi::create([
            'id_user' => $author->id_user,
            'judul_diskusi' => 'Diskusi Spam',
            'isi_diskusi' => 'Jual obat terlarang',
            'status_diskusi' => 'active',
        ]);

        Sanctum::actingAs($reporter);

        $response = $this->postJson("/api/discussions/{$discussion->id_diskusi}/report", [
            'reason' => 'Konten spam terlarang',
        ]);

        $response->assertCreated()
            ->assertJsonPath('message', 'Laporan berhasil dikirim');

        $this->assertDatabaseHas('laporan_diskusi', [
            'id_diskusi' => $discussion->id_diskusi,
            'id_user' => $reporter->id_user,
            'status' => 'pending',
            'alasan_laporan' => 'Konten spam terlarang',
        ]);

        // Tidak bisa melapor dua kali
        $dupResponse = $this->postJson("/api/discussions/{$discussion->id_diskusi}/report", [
            'reason' => 'Laporan lagi',
        ]);
        $dupResponse->assertOk()
            ->assertJsonPath('status', 'success');

        // Author tidak bisa melapor diskusinya sendiri
        Sanctum::actingAs($author);
        $this->postJson("/api/discussions/{$discussion->id_diskusi}/report", ['reason' => 'Tes'])
            ->assertStatus(422);
    }

    public function test_user_can_report_discussion_comment(): void
    {
        $author = $this->makeUser('author@example.com');
        $reporter = $this->makeUser('reporter@example.com');

        $discussion = Diskusi::create([
            'id_user' => $author->id_user,
            'judul_diskusi' => 'Diskusi Sehat',
            'isi_diskusi' => 'Tips hidup sehat',
            'status_diskusi' => 'active',
        ]);

        $comment = TanggapanDiskusi::create([
            'id_diskusi' => $discussion->id_diskusi,
            'id_user' => $author->id_user,
            'isi_tanggapan' => 'Komentar kasar',
        ]);

        Sanctum::actingAs($reporter);

        $response = $this->postJson("/api/comments/{$comment->id_tanggapan}/report", [
            'reason' => 'Kata-kata kasar dan tidak pantas',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('laporan_tanggapan', [
            'id_tanggapan' => $comment->id_tanggapan,
            'id_user' => $reporter->id_user,
            'status' => 'pending',
        ]);

        // Tidak bisa melapor komentar dua kali
        $dupCommentReport = $this->postJson("/api/comments/{$comment->id_tanggapan}/report", [
            'reason' => 'Lapor lagi',
        ]);
        $dupCommentReport->assertOk();

        // Author tidak bisa melapor komentarnya sendiri
        Sanctum::actingAs($author);
        $this->postJson("/api/comments/{$comment->id_tanggapan}/report", ['reason' => 'Tes'])
            ->assertStatus(422);
    }

    public function test_admin_can_view_discussion_reports_and_takedown(): void
    {
        $author = $this->makeUser('author@example.com');
        $reporter = $this->makeUser('reporter@example.com');
        $admin = $this->makeUser('admin@example.com', 'admin');

        $discussion = Diskusi::create([
            'id_user' => $author->id_user,
            'judul_diskusi' => 'Diskusi Ilegal',
            'isi_diskusi' => 'Spam link',
            'status_diskusi' => 'active',
        ]);

        Sanctum::actingAs($reporter);
        $this->postJson("/api/discussions/{$discussion->id_diskusi}/report", ['reason' => 'Spam']);

        Sanctum::actingAs($admin);
        $listResponse = $this->getJson('/api/admin/discussion-reports');
        $listResponse->assertOk()
            ->assertJsonPath('data.0.discussion_title', 'Diskusi Ilegal');

        $takedownResponse = $this->postJson("/api/admin/discussions/{$discussion->id_diskusi}/takedown");
        $takedownResponse->assertOk()
            ->assertJsonPath('message', 'Diskusi berhasil di-takedown');

        $this->assertDatabaseHas('diskusi', [
            'id_diskusi' => $discussion->id_diskusi,
            'status_diskusi' => 'deleted',
        ]);

        $this->assertDatabaseHas('laporan_diskusi', [
            'id_diskusi' => $discussion->id_diskusi,
            'status' => 'resolved',
        ]);

        // Diskusi yang sudah dihapus tidak bisa dibuka di detail
        $this->getJson("/api/discussions/{$discussion->id_diskusi}")->assertNotFound();
    }
}
