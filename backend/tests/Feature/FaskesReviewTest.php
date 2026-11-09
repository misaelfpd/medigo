<?php

namespace Tests\Feature;

use App\Models\Faskes;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FaskesReviewTest extends TestCase
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

    private function makeFaskes(): Faskes
    {
        return Faskes::create([
            'nama_faskes' => 'RSUD Sehat',
            'jenis_faskes' => 'Rumah Sakit',
            'kota_kabupaten' => 'Jakarta Barat',
            'alamat_faskes' => 'Jl. Kesehatan No. 10',
            '_support_bpjs' => true,
        ]);
    }

    public function test_user_can_submit_review_and_it_records_activity_log(): void
    {
        $user = $this->makeUser('pasien1@example.com');
        $faskes = $this->makeFaskes();

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/faskes/{$faskes->id_faskes}/comments", [
            'rating' => 5,
            'body' => 'Pelayanannya sangat ramah dan cepat.',
        ]);

        $response->assertCreated()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.rating', 5);

        $this->assertDatabaseHas('komentar', [
            'id_faskes' => $faskes->id_faskes,
            'id_user' => $user->id_user,
            'rating' => 5,
            'isi_komentar' => 'Pelayanannya sangat ramah dan cepat.',
        ]);

        $this->assertDatabaseHas('log_aktivitas', [
            'id_user' => $user->id_user,
            'tipe_aksi' => 'Beri Ulasan Faskes',
        ]);
    }

    public function test_user_can_report_review_and_admin_can_takedown(): void
    {
        $author = $this->makeUser('author@example.com');
        $reporter = $this->makeUser('reporter@example.com');
        $admin = $this->makeUser('admin@example.com', 'admin');
        $faskes = $this->makeFaskes();

        Sanctum::actingAs($author);
        $reviewRes = $this->postJson("/api/faskes/{$faskes->id_faskes}/comments", [
            'rating' => 1,
            'body' => 'Spam ulasan kasar',
        ]);
        $commentId = $reviewRes->json('data.id');

        Sanctum::actingAs($reporter);
        $reportRes = $this->postJson("/api/faskes-comments/{$commentId}/report", [
            'reason' => 'Ulasan kasar dan tidak pantas',
        ]);
        $reportRes->assertCreated();

        Sanctum::actingAs($admin);
        $reportsList = $this->getJson('/api/admin/faskes-comment-reports');
        $reportsList->assertOk();
        $reportId = $reportsList->json('data.0.id_laporan_komentar');

        $takedownRes = $this->postJson("/api/admin/faskes-comment-reports/{$reportId}/takedown");
        $takedownRes->assertOk();

        $this->assertDatabaseMissing('komentar', [
            'id_komentar' => $commentId,
        ]);

        $this->assertDatabaseHas('log_aktivitas', [
            'tipe_aksi' => 'Takedown Komentar Faskes',
        ]);
    }
}
