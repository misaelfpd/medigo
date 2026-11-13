<?php

namespace Tests\Feature;

use App\Models\Faskes;
use App\Models\GeminiChat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GeminiChatTest extends TestCase
{
    use RefreshDatabase;

    private const BASE = 'https://gemini.test/v1beta';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.gemini.key' => 'test-key',
            'services.gemini.base_url' => self::BASE,
            'services.gemini.models' => ['model-a', 'model-b'],
            'services.gemini.timeout' => 5,
        ]);

        $this->user = User::create([
            'nama_user' => 'Galih',
            'email_user' => 'galih@example.com',
            'password_user' => 'secret-password',
        ]);
        Sanctum::actingAs($this->user);
    }

    private static function text(string $text): array
    {
        return ['candidates' => [['content' => ['role' => 'model', 'parts' => [['text' => $text]]], 'finishReason' => 'STOP']]];
    }

    private static function url(string $model): string
    {
        return self::BASE."/models/{$model}:generateContent";
    }

    public function test_plain_reply_is_returned_and_saved(): void
    {
        Http::fake([self::url('model-a') => Http::response(self::text('Halo, ada yang bisa dibantu?'))]);

        $this->postJson('/api/chat-ai', ['message' => 'Halo'])
            ->assertOk()
            ->assertExactJson(['message' => 'Success', 'data' => 'Halo, ada yang bisa dibantu?', 'faskes' => []]);

        Http::assertSent(fn (Request $r) => $r->url() === self::url('model-a')
            && $r->hasHeader('x-goog-api-key', 'test-key')
            && ! str_contains($r->url(), 'key=')
            && $r['contents'] === [['role' => 'user', 'parts' => [['text' => 'Halo']]]]);

        $this->assertSame(
            [['user', 'Halo'], ['model', 'Halo, ada yang bisa dibantu?']],
            GeminiChat::orderBy('id')->get()->map(fn ($c) => [$c->role, $c->message])->all()
        );
    }

    public function test_recent_history_is_sent_as_memory_and_old_history_is_not(): void
    {
        GeminiChat::create(['id_user' => $this->user->id_user, 'role' => 'user', 'message' => 'kemarin lusa', 'created_at' => now()->subDays(2)]);
        GeminiChat::create(['id_user' => $this->user->id_user, 'role' => 'user', 'message' => 'tadi pagi']);
        GeminiChat::create(['id_user' => $this->user->id_user, 'role' => 'model', 'message' => 'oke']);

        Http::fake([self::url('model-a') => Http::response(self::text('siap'))]);

        $this->postJson('/api/chat-ai', ['message' => 'lagi'])->assertOk();

        Http::assertSent(fn (Request $r) => array_column(array_merge(...array_column($r['contents'], 'parts')), 'text')
            === ['tadi pagi', 'oke', 'lagi']);
    }

    public function test_function_call_searches_faskes_and_echoes_the_model_turn(): void
    {
        Faskes::create(['nama_faskes' => 'Klinik Gigi Sehat', 'jenis_faskes' => 'Klinik', 'kota_kabupaten' => 'Kota Bogor', 'alamat_faskes' => 'Jl. Pajajaran 1']);
        Faskes::create(['nama_faskes' => 'Klinik Gigi Ceria', 'jenis_faskes' => 'Klinik', 'kota_kabupaten' => 'Kota Depok', 'alamat_faskes' => 'Jl. Margonda 2']);

        $callTurn = ['role' => 'model', 'parts' => [[
            'functionCall' => ['id' => 'call_1', 'name' => 'cari_faskes', 'args' => ['keyword' => 'GIGI bogor']],
            'thoughtSignature' => 'sig-abc',
        ]]];

        Http::fake([self::url('model-a') => Http::sequence()
            ->push(['candidates' => [['content' => $callTurn]]])
            ->push(self::text('Ketemu Klinik Gigi Sehat di Bogor.')),
        ]);

        $this->postJson('/api/chat-ai', ['message' => 'cari klinik gigi di bogor'])
            ->assertOk()
            ->assertJsonPath('data', 'Ketemu Klinik Gigi Sehat di Bogor.');

        $followUp = Http::recorded()[1][0];
        [$modelTurn, $toolTurn] = array_slice($followUp['contents'], -2);

        $this->assertSame($callTurn, $modelTurn, 'model turn must be sent back unchanged, signature included');
        $this->assertSame('user', $toolTurn['role']);
        $response = $toolTurn['parts'][0]['functionResponse'];
        $this->assertSame(['call_1', 'cari_faskes'], [$response['id'], $response['name']]);
        $this->assertSame(['Klinik Gigi Sehat'], array_column($response['response']['faskes'], 'nama_faskes'));

        // Tool rounds are not stored as memory, only the user's question and the final answer.
        $this->assertSame(2, GeminiChat::count());
    }

    public function test_search_treats_like_wildcards_literally(): void
    {
        Faskes::create(['nama_faskes' => 'Klinik Umum', 'jenis_faskes' => 'Klinik', 'kota_kabupaten' => 'Bogor', 'alamat_faskes' => '-']);

        Http::fake([self::url('model-a') => Http::sequence()
            ->push(['candidates' => [['content' => ['role' => 'model', 'parts' => [['functionCall' => ['name' => 'cari_faskes', 'args' => ['keyword' => '%']]]]]]]])
            ->push(self::text('Tidak ada.')),
        ]);

        $this->postJson('/api/chat-ai', ['message' => 'cari %'])->assertOk();

        $toolTurn = last(Http::recorded()[1][0]['contents']);
        $this->assertArrayHasKey('message', $toolTurn['parts'][0]['functionResponse']['response']);
    }

    public function test_falls_back_to_next_model_when_busy_and_stays_on_it_for_tool_follow_up(): void
    {
        Http::fake([
            self::url('model-a') => Http::response(['error' => ['message' => 'high demand']], 503),
            self::url('model-b') => Http::sequence()
                ->push(['candidates' => [['content' => ['role' => 'model', 'parts' => [['functionCall' => ['name' => 'cari_faskes', 'args' => ['keyword' => 'rs']]]]]]]])
                ->push(self::text('Dari model B')),
        ]);

        $this->postJson('/api/chat-ai', ['message' => 'cari rs'])
            ->assertOk()
            ->assertJsonPath('data', 'Dari model B');

        $this->assertSame(
            [self::url('model-a'), self::url('model-b'), self::url('model-b')],
            array_map(fn ($pair) => $pair[0]->url(), Http::recorded()->all())
        );
    }

    public function test_falls_back_when_a_model_times_out(): void
    {
        Http::fake([
            self::url('model-a') => fn () => throw new ConnectionException('timed out'),
            self::url('model-b') => Http::response(self::text('Halo dari B')),
        ]);

        $this->postJson('/api/chat-ai', ['message' => 'halo'])->assertOk()->assertJsonPath('data', 'Halo dari B');
    }

    public function test_bad_request_is_not_retried_on_other_models(): void
    {
        Http::fake(['*' => Http::response(['error' => ['message' => 'API key not valid']], 400)]);

        $this->postJson('/api/chat-ai', ['message' => 'halo'])->assertStatus(502);

        Http::assertSentCount(1);
    }

    public function test_failure_returns_502_and_saves_nothing(): void
    {
        Http::fake(['*' => Http::response(['error' => ['message' => 'overloaded']], 503)]);

        $this->postJson('/api/chat-ai', ['message' => 'halo'])
            ->assertStatus(502)
            ->assertJsonMissingPath('data');

        Http::assertSentCount(2);
        $this->assertSame(0, GeminiChat::count());
    }

    public function test_empty_or_blocked_reply_is_a_failure(): void
    {
        Http::fake(['*' => Http::response(['candidates' => [['content' => ['parts' => []], 'finishReason' => 'SAFETY']]])]);

        $this->postJson('/api/chat-ai', ['message' => 'halo'])->assertStatus(502);
        $this->assertSame(0, GeminiChat::count());
    }

    public function test_missing_api_key_returns_503_without_calling_gemini(): void
    {
        config(['services.gemini.key' => null]);
        Http::fake();

        $this->postJson('/api/chat-ai', ['message' => 'halo'])->assertStatus(503);

        Http::assertNothingSent();
    }

    public function test_message_is_required(): void
    {
        Http::fake();

        $this->postJson('/api/chat-ai', [])->assertUnprocessable()->assertJsonValidationErrors('message');
    }

    public function test_nearest_faskes_sorted_by_distance_when_coordinates_provided(): void
    {
        $dekat = Faskes::create([
            'nama_faskes' => 'RS Dekat Sehat',
            'jenis_faskes' => 'Rumah Sakit',
            'kota_kabupaten' => 'Kota Bogor',
            'alamat_faskes' => 'Jl. Pajajaran Dekat',
            'latitude' => -6.59500000,
            'longitude' => 106.79000000,
            '_support_bpjs' => true,
        ]);

        $jauh = Faskes::create([
            'nama_faskes' => 'RS Jauh Banget',
            'jenis_faskes' => 'Rumah Sakit',
            'kota_kabupaten' => 'Kota Depok',
            'alamat_faskes' => 'Jl. Margonda Jauh',
            'latitude' => -6.40000000,
            'longitude' => 106.80000000,
            '_support_bpjs' => false,
        ]);

        $callTurn = ['role' => 'model', 'parts' => [[
            'functionCall' => [
                'id' => 'call_nearby',
                'name' => 'cari_faskes',
                'args' => [
                    'keyword' => 'rumah sakit terdekat',
                    'terdekat' => true,
                ],
            ],
        ]]];

        Http::fake([self::url('model-a') => Http::sequence()
            ->push(['candidates' => [['content' => $callTurn]]])
            ->push(self::text('RS terdekat adalah RS Dekat Sehat dengan jarak sekitar 0.56 km.')),
        ]);

        $this->postJson('/api/chat-ai', [
            'message' => 'apakah ada rumah sakit terdekat?',
            'latitude' => -6.59000000,
            'longitude' => 106.79000000,
        ])
            ->assertOk()
            ->assertJsonPath('data', 'RS terdekat adalah RS Dekat Sehat dengan jarak sekitar 0.56 km.');

        // Koordinat dikirim ke Gemini di pesan user terakhir (tidak disimpan ke riwayat)
        $firstReq = Http::recorded()[0][0];
        $lastUserText = last($firstReq['contents'])['parts'][0]['text'];
        $this->assertStringContainsString('Latitude: -6.59', $lastUserText);
        $this->assertStringContainsString('Longitude: 106.79', $lastUserText);
        $this->assertSame('apakah ada rumah sakit terdekat?', GeminiChat::where('role', 'user')->value('message'));

        $toolReq = Http::recorded()[1][0];
        $functionResponse = last($toolReq['contents'])['parts'][0]['functionResponse'];
        $faskesList = $functionResponse['response']['faskes'];

        $this->assertSame('RS Dekat Sehat', $faskesList[0]['nama_faskes']);
        $this->assertLessThan($faskesList[1]['jarak_km'], $faskesList[0]['jarak_km']);
        $this->assertArrayHasKey('jarak_km', $faskesList[0]);
        $this->assertArrayHasKey('jarak_teks', $faskesList[0]);
    }

    public function test_can_search_faskes_by_doctor_spesialisasi(): void
    {
        $faskes = Faskes::create([
            'nama_faskes' => 'Klinik Jantung Utama',
            'jenis_faskes' => 'Klinik',
            'kota_kabupaten' => 'Kota Bogor',
            'alamat_faskes' => 'Jl. Jenderal Sudirman',
        ]);

        $idSpesialisasi = DB::table('spesialisasi')->insertGetId([
            'nama_spesialisasi' => 'Spesialis Jantung dan Pembuluh Darah',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('spesialisasi_faskes')->insert([
            'id_faskes' => $faskes->id_faskes,
            'id_spesialisasi' => $idSpesialisasi,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $callTurn = ['role' => 'model', 'parts' => [[
            'functionCall' => [
                'name' => 'cari_faskes',
                'args' => ['keyword' => 'jantung'],
            ],
        ]]];

        Http::fake([self::url('model-a') => Http::sequence()
            ->push(['candidates' => [['content' => $callTurn]]])
            ->push(self::text('Ditemukan Klinik Jantung Utama.')),
        ]);

        $this->postJson('/api/chat-ai', ['message' => 'cari faskes dokter spesialis jantung'])
            ->assertOk();

        $toolReq = Http::recorded()[1][0];
        $faskesList = last($toolReq['contents'])['parts'][0]['functionResponse']['response']['faskes'];
        $this->assertSame('Klinik Jantung Utama', $faskesList[0]['nama_faskes']);
        $this->assertContains('Spesialis Jantung dan Pembuluh Darah', $faskesList[0]['spesialisasi']);
    }
}
