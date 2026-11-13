<?php

namespace Tests\Feature;

use App\Http\Controllers\GeminiController;
use App\Models\Diskusi;
use App\Models\Faskes;
use App\Models\TanggapanDiskusi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ForumFiturBaruTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $email): User
    {
        return User::create(['nama_user' => 'User '.$email, 'email_user' => $email, 'password_user' => 'secret-password']);
    }

    private function makeFaskes(string $nama, float $lat, float $lng, string $jenis = 'Rumah sakit'): Faskes
    {
        return Faskes::create([
            'nama_faskes' => $nama, 'jenis_faskes' => $jenis, 'kota_kabupaten' => 'Kota Bogor',
            'alamat_faskes' => 'Jl. '.$nama, 'latitude' => $lat, 'longitude' => $lng,
        ]);
    }

    private function makeDiscussion(User $author, ?Faskes $mention = null): Diskusi
    {
        return Diskusi::create([
            'id_user' => $author->id_user,
            'judul_diskusi' => 'Judul',
            'isi_diskusi' => 'Isi',
            'status_diskusi' => 'active',
            'id_faskes_mention' => $mention?->id_faskes,
        ]);
    }

    // ---------- Mention ----------

    public function test_mention_search_lists_faskes_from_database(): void
    {
        $this->makeFaskes('RS Siloam Bogor', -6.6, 106.8);
        $this->makeFaskes('Klinik Siloam Kecil', -6.6, 106.8, 'Klinik Pratama');
        $this->makeFaskes('RS Lain', -6.6, 106.8);
        $this->makeFaskes('Puskesmas 100% Sehat', -6.6, 106.8, 'Puskesmas');

        $this->getJson('/api/faskes/mention-search?q=siloam')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure(['data' => [['id_faskes', 'nama_faskes', 'jenis_faskes', 'type_slug', 'kota_kabupaten', 'thumbnail_url', 'detail_path']]]);

        // Wildcard % dicari apa adanya
        $this->getJson('/api/faskes/mention-search?q=%25')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_create_discussion_with_one_mentioned_faskes(): void
    {
        $faskes = $this->makeFaskes('RS Siloam Bogor', -6.6, 106.8);
        $user = $this->makeUser('u@example.com');
        Sanctum::actingAs($user);

        $res = $this->postJson('/api/discussions', [
            'title' => 'Pengalaman di @RS Siloam Bogor',
            'content' => 'Pelayanannya cepat',
            'faskes_id' => $faskes->id_faskes,
        ])->assertCreated()
            ->assertJsonPath('mentioned_faskes.id_faskes', $faskes->id_faskes);

        $id = $res->json('discussion_id');
        $this->assertSame($faskes->id_faskes, (int) DB::table('diskusi')->where('id_diskusi', $id)->value('id_faskes_mention'));

        $this->getJson("/api/discussions/{$id}")->assertOk()->assertJsonPath('data.discussion.mentioned_faskes.nama_faskes', 'RS Siloam Bogor');
        $this->getJson('/api/discussions')->assertOk()->assertJsonPath('data.data.0.mentioned_faskes.id_faskes', $faskes->id_faskes);
        $this->getJson("/api/discussions?faskes_id={$faskes->id_faskes}")->assertOk()->assertJsonCount(1, 'data.data');
    }

    public function test_mention_rejects_multiple_or_unknown_faskes(): void
    {
        $a = $this->makeFaskes('RS A', -6.6, 106.8);
        $b = $this->makeFaskes('RS B', -6.6, 106.8);
        Sanctum::actingAs($this->makeUser('u@example.com'));

        $this->postJson('/api/discussions', ['title' => 'x', 'content' => 'y', 'faskes_id' => [$a->id_faskes, $b->id_faskes]])
            ->assertStatus(422)->assertJsonValidationErrors('faskes_id');
        $this->postJson('/api/discussions', ['title' => 'x', 'content' => 'y', 'faskes_id' => 999])
            ->assertStatus(422)->assertJsonValidationErrors('faskes_id');
        // Tanpa mention tetap bisa
        $this->postJson('/api/discussions', ['title' => 'x', 'content' => 'y'])->assertCreated()->assertJsonPath('mentioned_faskes', null);
    }

    // ---------- Like ----------

    public function test_like_and_unlike_discussion_once_per_user(): void
    {
        $author = $this->makeUser('author@example.com');
        $fan = $this->makeUser('fan@example.com');
        $discussion = $this->makeDiscussion($author);
        Sanctum::actingAs($fan);

        $url = "/api/discussions/{$discussion->id_diskusi}/like";
        $this->postJson($url)->assertOk()->assertJsonPath('data.liked', true)->assertJsonPath('data.likes_count', 1);
        $this->postJson($url)->assertStatus(409)->assertJsonPath('data.likes_count', 1); // tidak bisa 2x
        $this->assertSame(1, DB::table('like_diskusi')->count());

        $this->getJson("/api/discussions/{$discussion->id_diskusi}")
            ->assertJsonPath('data.discussion.likes_count', 1)
            ->assertJsonPath('data.discussion.liked_by_me', true);

        $this->deleteJson($url)->assertOk()->assertJsonPath('data.liked', false)->assertJsonPath('data.likes_count', 0);
        $this->deleteJson($url)->assertStatus(409);
    }

    public function test_cannot_like_own_discussion_or_comment(): void
    {
        $author = $this->makeUser('author@example.com');
        $discussion = $this->makeDiscussion($author);
        $comment = TanggapanDiskusi::create(['id_diskusi' => $discussion->id_diskusi, 'id_user' => $author->id_user, 'isi_tanggapan' => 'hai']);
        Sanctum::actingAs($author);

        $this->postJson("/api/discussions/{$discussion->id_diskusi}/like")->assertForbidden();
        $this->postJson("/api/discussion-comments/{$comment->id_tanggapan}/like")->assertForbidden();
    }

    public function test_like_comment_and_counts_in_detail(): void
    {
        $author = $this->makeUser('author@example.com');
        $fan = $this->makeUser('fan@example.com');
        $discussion = $this->makeDiscussion($author);
        $comment = TanggapanDiskusi::create(['id_diskusi' => $discussion->id_diskusi, 'id_user' => $author->id_user, 'isi_tanggapan' => 'hai']);
        Sanctum::actingAs($fan);

        $this->postJson("/api/discussion-comments/{$comment->id_tanggapan}/like")->assertOk()->assertJsonPath('data.likes_count', 1);
        $this->postJson("/api/discussion-comments/{$comment->id_tanggapan}/like")->assertStatus(409);

        $this->getJson("/api/discussions/{$discussion->id_diskusi}")
            ->assertJsonPath('data.comments.0.likes_count', 1)
            ->assertJsonPath('data.comments.0.liked_by_me', true);

        $this->deleteJson("/api/discussion-comments/{$comment->id_tanggapan}/like")->assertOk()->assertJsonPath('data.likes_count', 0);
    }

    public function test_like_requires_login_and_active_discussion(): void
    {
        $author = $this->makeUser('author@example.com');
        $discussion = $this->makeDiscussion($author);

        $this->postJson("/api/discussions/{$discussion->id_diskusi}/like")->assertUnauthorized();

        $discussion->update(['status_diskusi' => 'deleted']);
        Sanctum::actingAs($this->makeUser('fan@example.com'));
        $this->postJson("/api/discussions/{$discussion->id_diskusi}/like")->assertNotFound();
    }

    public function test_discussion_list_can_sort_by_likes(): void
    {
        $author = $this->makeUser('author@example.com');
        $old = $this->makeDiscussion($author);
        $old->update(['judul_diskusi' => 'Populer', 'created_at' => now()->subDay()]);
        $this->makeDiscussion($author)->update(['judul_diskusi' => 'Baru']);
        foreach (['a', 'b'] as $u) {
            DB::table('like_diskusi')->insert(['id_diskusi' => $old->id_diskusi, 'id_user' => $this->makeUser("{$u}@example.com")->id_user]);
        }

        $this->getJson('/api/discussions')->assertJsonPath('data.data.0.judul_diskusi', 'Baru');
        $this->getJson('/api/discussions?sort=terpopuler')
            ->assertJsonPath('data.data.0.judul_diskusi', 'Populer')
            ->assertJsonPath('data.data.0.likes_count', 2)
            ->assertJsonPath('data.data.0.liked_by_me', false);
    }

    // ---------- Thumbnail kartu diskusi ----------

    public function test_discussion_list_has_thumbnail_from_first_uploaded_image(): void
    {
        Storage::fake('public');
        $user = $this->makeUser('u@example.com');
        Sanctum::actingAs($user);

        $id = $this->post('/api/discussions', [
            'title' => 'Ada foto',
            'content' => 'Isi',
            'images' => [
                UploadedFile::fake()->image('satu.jpg'),
                UploadedFile::fake()->image('dua.jpg'),
            ],
        ], ['Accept' => 'application/json'])->assertCreated()->json('discussion_id');
        $this->makeDiscussion($user)->update(['judul_diskusi' => 'Tanpa foto', 'created_at' => now()->subDay()]);

        $first = $this->getJson('/api/discussions')->assertOk()->json('data.data');
        $this->assertSame('Ada foto', $first[0]['judul_diskusi']);
        $this->assertCount(2, $first[0]['images_list']);
        $this->assertSame($first[0]['images_list'][0], $first[0]['thumbnail_url']);
        $this->assertStringContainsString('/storage/discussions/', $first[0]['thumbnail_url']);
        $this->assertNull($first[1]['thumbnail_url']);
        $this->assertSame([], $first[1]['images_list']);

        $this->getJson("/api/discussions/{$id}")->assertJsonPath('data.discussion.thumbnail_url', $first[0]['thumbnail_url']);
        $this->getJson('/api/user/discussions')->assertJsonPath('data.0.thumbnail_url', $first[0]['thumbnail_url']);
    }

    public function test_old_discussion_with_only_single_image_column_still_gets_thumbnail(): void
    {
        $user = $this->makeUser('u@example.com');
        $this->makeDiscussion($user)->update(['gambar_diskusi' => 'http://localhost/storage/discussions/lama.jpg', 'images' => null]);

        $this->getJson('/api/discussions')->assertJsonPath('data.data.0.thumbnail_url', 'http://localhost/storage/discussions/lama.jpg');
    }

    // ---------- Faskes terdekat di forum ----------

    public function test_nearby_faskes_ranked_by_likes_then_distance(): void
    {
        // User di (-6.60, 106.80)
        $sangatDekat = $this->makeFaskes('RS Sangat Dekat', -6.601, 106.80); // ~0.1 km
        $dekat = $this->makeFaskes('RS Dekat', -6.61, 106.80);               // ~1.1 km
        $agakJauh = $this->makeFaskes('RS Agak Jauh', -6.65, 106.80);        // ~5.6 km, paling banyak like
        $this->makeFaskes('RS Luar Radius', -6.90, 106.80);                  // ~33 km
        $this->makeFaskes('RS Tanpa Koordinat', 0, 0)->update(['latitude' => null, 'longitude' => null]);

        $author = $this->makeUser('author@example.com');
        $d1 = $this->makeDiscussion($author, $agakJauh);
        $d2 = $this->makeDiscussion($author, $dekat);
        foreach (['a', 'b', 'c'] as $i => $u) {
            $liker = $this->makeUser("{$u}@example.com");
            DB::table('like_diskusi')->insert(['id_diskusi' => $d1->id_diskusi, 'id_user' => $liker->id_user]);
            if ($i === 0) {
                DB::table('like_diskusi')->insert(['id_diskusi' => $d2->id_diskusi, 'id_user' => $liker->id_user]);
            }
        }
        // Like di diskusi yang sudah dihapus tidak dihitung
        $deleted = $this->makeDiscussion($author, $sangatDekat);
        DB::table('like_diskusi')->insert(['id_diskusi' => $deleted->id_diskusi, 'id_user' => $liker->id_user]);
        $deleted->update(['status_diskusi' => 'deleted']);

        $res = $this->getJson('/api/forum/faskes-terdekat?lat=-6.60&lng=106.80&radius=10&limit=5')
            ->assertOk()
            ->assertJsonPath('radius_diperluas', false)
            ->assertJsonCount(3, 'data');

        $this->assertSame(['RS Agak Jauh', 'RS Dekat', 'RS Sangat Dekat'], array_column($res->json('data'), 'nama_faskes'));
        $this->assertSame([3, 1, 0], array_column($res->json('data'), 'total_like'));
        $this->assertEqualsWithDelta(5.6, $res->json('data.0.distance_km'), 0.2);
        $this->assertArrayHasKey('jarak_teks', $res->json('data.0'));
    }

    public function test_nearby_faskes_expands_radius_when_empty_and_requires_location(): void
    {
        $this->makeFaskes('RS Jauh Sekali', -7.5, 110.0);

        $this->getJson('/api/forum/faskes-terdekat?lat=-6.60&lng=106.80&radius=5')
            ->assertOk()
            ->assertJsonPath('radius_diperluas', true)
            ->assertJsonPath('data.0.nama_faskes', 'RS Jauh Sekali');

        $this->getJson('/api/forum/faskes-terdekat')->assertStatus(422)->assertJsonValidationErrors(['lat', 'lng']);
        $this->getJson('/api/forum/faskes-terdekat?latitude=-6.6&longitude=106.8')->assertOk();
    }

    // ---------- Medibot di luar topik ----------

    public function test_medibot_system_prompt_restricts_topics(): void
    {
        config([
            'services.gemini.key' => 'test-key',
            'services.gemini.base_url' => 'https://gemini.test/v1beta',
            'services.gemini.models' => ['model-a'],
        ]);
        Http::fake(['*' => Http::response(['candidates' => [['content' => ['role' => 'model', 'parts' => [['text' => GeminiController::OFF_TOPIC_REPLY]]]]]])]);
        Sanctum::actingAs($this->makeUser('u@example.com'));

        $this->postJson('/api/chat-ai', ['message' => 'Siapa juara piala dunia 2022?'])
            ->assertOk()
            ->assertJsonPath('data', GeminiController::OFF_TOPIC_REPLY);

        Http::assertSent(function (HttpRequest $r) {
            $system = $r['system_instruction']['parts'][0]['text'];

            return str_contains($system, 'BATASAN TOPIK')
                && str_contains($system, GeminiController::OFF_TOPIC_REPLY);
        });
    }
}
