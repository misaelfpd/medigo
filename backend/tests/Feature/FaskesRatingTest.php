<?php

namespace Tests\Feature;

use App\Models\Faskes;
use App\Models\Komentar;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaskesRatingTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $email): User
    {
        return User::create([
            'nama_user' => 'User '.$email,
            'email_user' => $email,
            'password_user' => 'secret-password',
            'role_user' => 'user',
        ]);
    }

    private function makeFaskes(): Faskes
    {
        return Faskes::create([
            'nama_faskes' => 'Klinik Medigo Sejahtera',
            'jenis_faskes' => 'Klinik',
            'kota_kabupaten' => 'Jakarta Selatan',
            'alamat_faskes' => 'Jl. RS Fatmawati No. 12',
            '_support_bpjs' => true,
        ]);
    }

    public function test_faskes_list_calculates_avg_rating_and_total_reviews_from_komentar(): void
    {
        $faskes = $this->makeFaskes();
        $u1 = $this->makeUser('u1@example.com');
        $u2 = $this->makeUser('u2@example.com');

        Komentar::create([
            'id_faskes' => $faskes->id_faskes,
            'id_user' => $u1->id_user,
            'rating' => 5,
            'isi_komentar' => 'Bagus sekali',
        ]);

        Komentar::create([
            'id_faskes' => $faskes->id_faskes,
            'id_user' => $u2->id_user,
            'rating' => 4,
            'isi_komentar' => 'Cukup memuaskan',
        ]);

        $response = $this->getJson('/api/faskes');
        $response->assertOk();

        $item = collect($response->json('data.data'))->firstWhere('id_faskes', $faskes->id_faskes);
        $this->assertNotNull($item);
        $this->assertEquals(2, $item['total_reviews']);
        $this->assertEquals('4.5', $item['avg_rating']);
    }

    public function test_faskes_show_returns_correct_rating_and_reviews_list(): void
    {
        $faskes = $this->makeFaskes();
        $u1 = $this->makeUser('u1@example.com');

        Komentar::create([
            'id_faskes' => $faskes->id_faskes,
            'id_user' => $u1->id_user,
            'rating' => 5,
            'isi_komentar' => 'Dokter sangat komunikatif',
        ]);

        $response = $this->getJson("/api/faskes/{$faskes->id_faskes}");
        $response->assertOk()
            ->assertJsonPath('data.faskes.total_reviews', 1)
            ->assertJsonPath('data.faskes.avg_rating', '5')
            ->assertJsonPath('data.reviews.0.isi_komentar', 'Dokter sangat komunikatif')
            ->assertJsonPath('data.reviews.0.reviewer_name', 'User u1@example.com');
    }
}
