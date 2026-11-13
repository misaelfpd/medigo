<?php

namespace Tests\Feature;

use App\Models\Faskes;
use App\Models\FotoFaskes;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FotoFaskesTest extends TestCase
{
    use RefreshDatabase;

    private Faskes $faskes;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->faskes = Faskes::create([
            'nama_faskes' => 'RS Sehat',
            'jenis_faskes' => 'Rumah sakit',
            'kota_kabupaten' => 'Bogor',
            'alamat_faskes' => 'Jl. Sehat 1',
        ]);
    }

    private function admin(): User
    {
        return User::create(['nama_user' => 'Admin', 'email_user' => 'admin@example.com', 'password_user' => 'secret-password', 'role_user' => 'admin']);
    }

    private function jpg(int $w = 2400, int $h = 1200, string $name = 'foto.jpg'): UploadedFile
    {
        return UploadedFile::fake()->image($name, $w, $h);
    }

    private function url(string $suffix = ''): string
    {
        return "/api/faskes/{$this->faskes->id_faskes}/foto{$suffix}";
    }

    public function test_upload_converts_to_webp_and_resizes(): void
    {
        Sanctum::actingAs($this->admin());

        $res = $this->post($this->url(), ['tipe' => 'banner', 'foto' => $this->jpg(4000, 1500)], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.0.tipe', 'banner')
            ->assertJsonPath('data.0.lebar', 1920);

        $foto = FotoFaskes::first();
        $this->assertStringEndsWith('.webp', $foto->path_foto);
        Storage::disk('public')->assertExists($foto->path_foto);

        // Isi file benar-benar WebP (header RIFF....WEBP)
        $bytes = Storage::disk('public')->get($foto->path_foto);
        $this->assertSame('RIFF', substr($bytes, 0, 4));
        $this->assertSame('WEBP', substr($bytes, 8, 4));
        $this->assertStringEndsWith('.webp', $res->json('data.0.url'));
    }

    public function test_png_upload_is_converted_too(): void
    {
        Sanctum::actingAs($this->admin());

        $this->post($this->url(), ['tipe' => 'galeri', 'foto' => UploadedFile::fake()->image('a.png', 300, 300)], ['Accept' => 'application/json'])
            ->assertCreated();

        $bytes = Storage::disk('public')->get(FotoFaskes::first()->path_foto);
        $this->assertSame('WEBP', substr($bytes, 8, 4));
    }

    public function test_thumbnail_and_banner_are_single_and_replace_old_file(): void
    {
        Sanctum::actingAs($this->admin());

        $this->post($this->url(), ['tipe' => 'thumbnail', 'foto' => $this->jpg()], ['Accept' => 'application/json'])->assertCreated();
        $oldPath = FotoFaskes::first()->path_foto;

        $this->post($this->url(), ['tipe' => 'thumbnail', 'foto' => $this->jpg()], ['Accept' => 'application/json'])->assertCreated();

        $this->assertSame(1, FotoFaskes::where('tipe', 'thumbnail')->count());
        Storage::disk('public')->assertMissing($oldPath);

        // Thumbnail/banner tidak boleh banyak file sekaligus
        $this->post($this->url(), ['tipe' => 'banner', 'foto' => [$this->jpg(), $this->jpg()]], ['Accept' => 'application/json'])
            ->assertStatus(422);
    }

    public function test_gallery_accepts_multiple_files_and_limit(): void
    {
        Sanctum::actingAs($this->admin());

        $this->post($this->url(), ['tipe' => 'galeri', 'foto' => [$this->jpg(800, 600), $this->jpg(800, 600), $this->jpg(800, 600)]], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonCount(3, 'data');

        $this->getJson($this->url())
            ->assertOk()
            ->assertJsonCount(3, 'data.galeri')
            ->assertJsonPath('data.thumbnail', null)
            ->assertJsonPath('data.banner', null);

        // Isi galeri sampai batas, lalu upload berikutnya ditolak
        for ($i = 3; $i < FotoFaskes::MAKS_GALERI; $i++) {
            FotoFaskes::create(['id_faskes' => $this->faskes->id_faskes, 'tipe' => 'galeri', 'path_foto' => "faskes/x/{$i}.webp"]);
        }
        $this->post($this->url(), ['tipe' => 'galeri', 'foto' => $this->jpg(800, 600)], ['Accept' => 'application/json'])
            ->assertStatus(422);
    }

    public function test_gallery_photo_can_become_thumbnail(): void
    {
        Sanctum::actingAs($this->admin());

        $this->post($this->url(), ['tipe' => 'galeri', 'foto' => $this->jpg(3000, 2000)], ['Accept' => 'application/json'])->assertCreated();
        $galeri = FotoFaskes::first();

        $this->postJson($this->url("/{$galeri->id_foto}/jadikan"), ['tipe' => 'thumbnail'])
            ->assertCreated()
            ->assertJsonPath('data.tipe', 'thumbnail')
            ->assertJsonPath('data.lebar', 800);

        // Foto galeri aslinya tetap ada
        $this->assertSame(1, FotoFaskes::where('tipe', 'galeri')->count());
        $this->assertSame(1, FotoFaskes::where('tipe', 'thumbnail')->count());

        // Thumbnail muncul di list faskes dan detail
        $thumbUrl = FotoFaskes::where('tipe', 'thumbnail')->first()->toApi()['url'];
        $this->getJson('/api/faskes/search')->assertOk()->assertJsonPath('data.0.thumbnail_url', $thumbUrl);
        $this->getJson("/api/faskes/{$this->faskes->id_faskes}")
            ->assertOk()
            ->assertJsonPath('data.faskes.thumbnail_url', $thumbUrl)
            ->assertJsonPath('data.faskes.banner_url', null)
            ->assertJsonCount(1, 'data.foto.galeri');
    }

    public function test_update_and_delete_photo(): void
    {
        Sanctum::actingAs($this->admin());

        $this->post($this->url(), ['tipe' => 'galeri', 'foto' => $this->jpg(800, 600)], ['Accept' => 'application/json'])->assertCreated();
        $foto = FotoFaskes::first();
        $oldPath = $foto->path_foto;

        $this->putJson($this->url("/{$foto->id_foto}"), ['keterangan' => 'Lobi utama', 'urutan' => 5])
            ->assertOk()
            ->assertJsonPath('data.keterangan', 'Lobi utama')
            ->assertJsonPath('data.urutan', 5);

        // Ganti file lewat POST multipart
        $this->post($this->url("/{$foto->id_foto}"), ['foto' => $this->jpg(500, 500)], ['Accept' => 'application/json'])
            ->assertOk();
        Storage::disk('public')->assertMissing($oldPath);
        $newPath = $foto->fresh()->path_foto;
        Storage::disk('public')->assertExists($newPath);

        $this->deleteJson($this->url("/{$foto->id_foto}"))->assertOk();
        $this->assertSame(0, FotoFaskes::count());
        Storage::disk('public')->assertMissing($newPath);
    }

    public function test_deleting_faskes_removes_photo_files(): void
    {
        Sanctum::actingAs($this->admin());
        $this->post($this->url(), ['tipe' => 'banner', 'foto' => $this->jpg()], ['Accept' => 'application/json'])->assertCreated();
        $path = FotoFaskes::first()->path_foto;

        $this->deleteJson("/api/faskes/{$this->faskes->id_faskes}")->assertOk();

        Storage::disk('public')->assertMissing($path);
        $this->assertSame(0, FotoFaskes::count());
    }

    public function test_only_admin_can_upload_and_validation(): void
    {
        Sanctum::actingAs(User::create(['nama_user' => 'U', 'email_user' => 'u@example.com', 'password_user' => 'secret-password']));
        $this->post($this->url(), ['tipe' => 'banner', 'foto' => $this->jpg()], ['Accept' => 'application/json'])->assertForbidden();

        Sanctum::actingAs($this->admin());
        $this->post($this->url(), ['tipe' => 'poster', 'foto' => $this->jpg()], ['Accept' => 'application/json'])->assertStatus(422);
        $this->post($this->url(), ['tipe' => 'galeri', 'foto' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')], ['Accept' => 'application/json'])
            ->assertStatus(422);
        $this->post('/api/faskes/999/foto', ['tipe' => 'galeri', 'foto' => $this->jpg()], ['Accept' => 'application/json'])->assertNotFound();
    }
}
