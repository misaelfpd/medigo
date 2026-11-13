<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\RoadDistanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MedibotLimitAndDistanceTest extends TestCase
{
    use RefreshDatabase;

    private const GEMINI = 'https://gemini.test/v1beta/models/model-a:generateContent';

    private const OSRM = 'https://osrm.test/table/v1/driving/';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.gemini.key' => 'test-key',
            'services.gemini.base_url' => 'https://gemini.test/v1beta',
            'services.gemini.models' => ['model-a'],
            'services.frontend.service_key' => 'rahasia-fe',
        ]);
    }

    private function fakeGemini(): void
    {
        Http::fake([self::GEMINI => Http::response(['candidates' => [['content' => ['role' => 'model', 'parts' => [['text' => 'Halo']]]]]])]);
    }

    private function guestChat(string $guestId, ?string $clientIp = null)
    {
        $headers = $clientIp ? ['X-Service-Key' => 'rahasia-fe', 'X-Client-IP' => $clientIp] : [];

        return $this->postJson('/api/chat-ai', ['message' => 'halo', 'guest_id' => $guestId], $headers);
    }

    // ---------- Kuota tamu ----------

    public function test_guest_cannot_bypass_quota_by_changing_guest_id_from_same_ip(): void
    {
        config(['services.gemini.guest_ip_daily_limit' => 3]);
        $this->fakeGemini();

        // Ganti guest_id setiap request (seperti hapus cookie), IP asli sama
        foreach (['g1', 'g2', 'g3'] as $id) {
            $this->guestChat($id, '203.0.113.7')->assertOk();
        }
        $this->guestChat('g4', '203.0.113.7')->assertStatus(403)->assertJsonPath('status', 'limit_reached');

        // Pengunjung lain (IP berbeda) tidak ikut kena
        $this->guestChat('g5', '198.51.100.9')->assertOk();
    }

    public function test_frontend_without_client_ip_does_not_share_one_ip_quota(): void
    {
        // Frontend versi lama (belum kirim X-Client-IP): semua tamu terlihat dari IP server frontend.
        // Jangan sampai semua tamu berbagi satu jatah harian.
        config(['services.gemini.guest_ip_daily_limit' => 2]);
        $this->fakeGemini();

        foreach (['a', 'b', 'c', 'd'] as $id) {
            $this->guestChat($id)->assertOk();
        }
    }

    public function test_forged_client_ip_without_service_key_is_ignored(): void
    {
        config(['services.gemini.guest_ip_daily_limit' => 1]);
        $this->fakeGemini();

        // Header X-Client-IP tanpa service key yang benar tidak dipercaya
        $this->postJson('/api/chat-ai', ['message' => 'halo', 'guest_id' => 'x'], ['X-Service-Key' => 'salah', 'X-Client-IP' => '203.0.113.7'])->assertOk();
        $this->guestChat('y', '203.0.113.7')->assertOk(); // jatah IP ini masih utuh
    }

    public function test_guest_must_come_from_frontend_when_enabled(): void
    {
        config(['services.gemini.guest_require_frontend' => true]);
        $this->fakeGemini();

        $this->postJson('/api/chat-ai', ['message' => 'halo', 'guest_id' => 'x'])->assertStatus(403);
        $this->guestChat('x', '203.0.113.7')->assertOk();

        // User login tetap bisa langsung
        Sanctum::actingAs(User::create(['nama_user' => 'U', 'email_user' => 'u@example.com', 'password_user' => 'secret-password']));
        $this->postJson('/api/chat-ai', ['message' => 'halo'])->assertOk();
    }

    public function test_per_minute_throttle(): void
    {
        config(['services.gemini.per_minute_limit' => 2]);
        $this->fakeGemini();
        Sanctum::actingAs(User::create(['nama_user' => 'U', 'email_user' => 'u@example.com', 'password_user' => 'secret-password']));

        $this->postJson('/api/chat-ai', ['message' => 'satu'])->assertOk();
        $this->postJson('/api/chat-ai', ['message' => 'dua'])->assertOk();
        $this->postJson('/api/chat-ai', ['message' => 'tiga'])->assertStatus(429)->assertJsonPath('status', 'too_many_requests');
    }

    public function test_failed_gemini_reply_does_not_use_quota(): void
    {
        config(['services.gemini.guest_ip_daily_limit' => 1]);
        Http::fake([self::GEMINI => Http::sequence()
            ->push(['error' => ['message' => 'bad']], 400)
            ->push(['candidates' => [['content' => ['role' => 'model', 'parts' => [['text' => 'Halo']]]]]])]);

        $this->guestChat('a', '203.0.113.7')->assertStatus(502);
        $this->guestChat('a', '203.0.113.7')->assertOk();
    }

    // ---------- Jarak OSRM ----------

    private function items(int $count): Collection
    {
        // Faskes berjajar ke selatan, makin jauh makin besar index-nya
        return collect(range(1, $count))->map(fn ($i) => (object) [
            'id_faskes' => $i,
            'latitude' => -6.60 - ($i * 0.01),
            'longitude' => 106.80,
        ]);
    }

    private function enableOsrm(): void
    {
        config(['services.osrm.enabled' => true, 'services.osrm.url' => self::OSRM, 'services.osrm.max_items' => 3]);
    }

    public function test_osrm_only_called_for_nearest_items(): void
    {
        $this->enableOsrm();
        Http::fake([self::OSRM.'*' => Http::response(['code' => 'Ok', 'distances' => [[0, 1500, 2600, 3700]]])]);

        $result = RoadDistanceService::applyToCollection($this->items(10)->shuffle(), -6.60, 106.80);

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $r) => substr_count(parse_url($r->url(), PHP_URL_PATH), ';') === 3); // asal + 3 tujuan

        $this->assertSame([1.5, 2.6, 3.7], $result->take(3)->pluck('distance_km')->all());
        // Sisanya tetap punya jarak (garis lurus)
        $this->assertNotNull($result->last()->distance_km);
        $this->assertSame(10, $result->count());
    }

    public function test_osrm_failure_falls_back_and_cools_down(): void
    {
        $this->enableOsrm();
        $calls = 0;
        Http::fake([self::OSRM.'*' => function () use (&$calls) {
            $calls++;
            throw new ConnectionException('timeout');
        }]);

        $result = RoadDistanceService::applyToCollection($this->items(3), -6.60, 106.80);
        $this->assertEqualsWithDelta(1.1, $result->first()->distance_km, 0.1);

        // Request berikutnya tidak memanggil OSRM lagi selama cooldown
        RoadDistanceService::applyToCollection($this->items(3), -6.70, 106.80);
        $this->assertSame(1, $calls);
    }

    public function test_osrm_results_are_cached(): void
    {
        $this->enableOsrm();
        Http::fake([self::OSRM.'*' => Http::response(['code' => 'Ok', 'distances' => [[0, 1500, 2600, 3700]]])]);

        RoadDistanceService::applyToCollection($this->items(3), -6.60, 106.80);
        $again = RoadDistanceService::applyToCollection($this->items(3), -6.60, 106.80);

        Http::assertSentCount(1);
        $this->assertSame(1.5, $again->first()->distance_km);
    }
}
