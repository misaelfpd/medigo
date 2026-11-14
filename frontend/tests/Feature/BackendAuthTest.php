<?php

use App\Auth\BackendUser;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

/*
 * Login frontend sekarang sepenuhnya lewat backend; frontend tidak punya tabel users.
 */

beforeEach(function () {
    config(['services.medigo.url' => 'http://be.test', 'services.medigo.service_key' => 'kunci-fe']);
    $this->artisan('migrate');
});

function backendUser(array $override = []): array
{
    return array_merge([
        'id_user' => 7,
        'nama_user' => 'Galih',
        'email_user' => 'galih@example.com',
        'no_telp' => '0812',
        'tanggal_lahir' => '2000-05-17',
        'role_user' => 'user',
        'avatar' => null,
    ], $override);
}

function loginAs(array $user = []): void
{
    $data = backendUser($user);
    session(['user_backend_token' => 'tok-123', 'user_backend_data' => $data]);
    Auth::login(BackendUser::fromBackend($data));
}

test('frontend database no longer has a users table', function () {
    expect(Schema::hasTable('users'))->toBeFalse()
        ->and(Schema::hasTable('sessions'))->toBeTrue();
});

test('login is checked by backend and user data comes from backend', function () {
    Http::fake([
        'be.test/api/login' => Http::response(['data' => backendUser(), 'access_token' => 'tok-123']),
        'be.test/*' => Http::response(['data' => []]),
    ]);

    $this->post('/login', ['email' => 'galih@example.com', 'password' => 'rahasia123'])
        ->assertRedirect(route('faskes.index'));

    expect(Auth::check())->toBeTrue()
        ->and(Auth::user()->name)->toBe('Galih')
        ->and(Auth::id())->toBe(7)
        ->and(session('user_backend_token'))->toBe('tok-123');

    Http::assertSent(fn (Request $r) => $r->url() === 'http://be.test/api/login' && $r['password'] === 'rahasia123');

    // Request berikutnya tetap login (user dibaca dari session) dan header menampilkan nama
    $this->get('/discussions')->assertOk()->assertSee('Galih');
});

test('wrong password or blocked account is rejected', function () {
    Http::fake(['be.test/api/login' => Http::response([
        'message' => 'blocked',
        'errors' => ['email' => ['Akun Anda telah dinonaktifkan atau diblokir oleh administrator karena pelanggaran pedoman komunitas.']],
    ], 422)]);

    $this->from('/login')->post('/login', ['email' => 'galih@example.com', 'password' => 'salah'])
        ->assertRedirect('/login')
        ->assertSessionHasErrors(['email' => 'Akun Anda telah dinonaktifkan atau diblokir oleh administrator karena pelanggaran pedoman komunitas.']);

    expect(Auth::check())->toBeFalse();
});

test('login shows friendly error when backend is down', function () {
    Http::fake(['be.test/*' => fn () => throw new ConnectionException('down')]);

    $this->from('/login')->post('/login', ['email' => 'galih@example.com', 'password' => 'x'])
        ->assertSessionHasErrors('email');

    expect(Auth::check())->toBeFalse();
});

test('register creates account in backend only', function () {
    Http::fake([
        'be.test/api/register' => Http::response(['data' => backendUser(['id_user' => 9]), 'access_token' => 'tok-9'], 201),
        'be.test/*' => Http::response(['data' => []]),
    ]);

    $this->post('/register', [
        'name' => 'Galih', 'email' => 'galih@example.com',
        'password' => 'rahasia123', 'password_confirmation' => 'rahasia123',
    ])->assertRedirect(route('faskes.index'));

    expect(Auth::id())->toBe(9)->and(session('user_backend_token'))->toBe('tok-9');
});

test('register shows error when email already exists in backend', function () {
    Http::fake(['be.test/api/register' => Http::response(['errors' => ['email' => ['The email has already been taken.']]], 422)]);

    $this->from('/register')->post('/register', [
        'name' => 'Galih', 'email' => 'galih@example.com',
        'password' => 'rahasia123', 'password_confirmation' => 'rahasia123',
    ])->assertSessionHasErrors(['email' => 'Email ini sudah terdaftar. Silakan login atau gunakan email lain.']);

    expect(Auth::check())->toBeFalse();
});

test('profile update is saved to backend and session is refreshed', function () {
    loginAs();
    Http::fake(['be.test/api/user' => Http::response(['data' => backendUser(['nama_user' => 'Galih Baru', 'email_user' => 'baru@example.com'])])]);

    $this->put('/profile', ['name' => 'Galih Baru', 'email' => 'baru@example.com', 'phone' => '0812', 'birth_date' => '2000-05-17'])
        ->assertRedirect(route('profile'))
        ->assertSessionHas('success');

    Http::assertSent(fn (Request $r) => $r->method() === 'PUT' && $r['email'] === 'baru@example.com' && $r->hasHeader('Authorization', 'Bearer tok-123'));
    expect(session('user_backend_data.email_user'))->toBe('baru@example.com');
});

test('profile is not changed when backend rejects the update', function () {
    loginAs();
    Http::fake(['be.test/api/user' => Http::response(['errors' => ['current_password' => ['Kata sandi saat ini tidak sesuai.']]], 422)]);

    $this->from('/profile/edit')->put('/profile', [
        'name' => 'Galih', 'email' => 'galih@example.com',
        'current_password' => 'salah', 'new_password' => 'barubaru123',
    ])->assertSessionHasErrors(['current_password' => 'Kata sandi saat ini tidak sesuai.']);

    expect(session('user_backend_data.nama_user'))->toBe('Galih');
});

test('profile update fails safely when backend is down', function () {
    loginAs();
    Http::fake(['be.test/*' => fn () => throw new ConnectionException('down')]);

    $this->from('/profile/edit')->put('/profile', ['name' => 'Nama Lain', 'email' => 'galih@example.com'])
        ->assertSessionHasErrors('email');

    expect(session('user_backend_data.nama_user'))->toBe('Galih');
});

test('chat forwards service key and real visitor ip to backend', function () {
    Http::fake(['be.test/api/chat-ai' => Http::response(['message' => 'Success', 'data' => 'Halo', 'faskes' => []])]);

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.7'])
        ->postJson('/chat', ['message' => 'halo'])
        ->assertOk();

    Http::assertSent(fn (Request $r) => $r->url() === 'http://be.test/api/chat-ai'
        && $r->hasHeader('X-Service-Key', 'kunci-fe')
        && $r->hasHeader('X-Client-IP', '203.0.113.7'));
});

test('logout clears backend session data', function () {
    loginAs();
    Http::fake(['be.test/*' => Http::response([])]);

    $this->post('/logout')->assertRedirect(route('login'));

    expect(Auth::check())->toBeFalse()->and(session('user_backend_data'))->toBeNull();
});

test('main pages render for logged in user without users table', function () {
    loginAs(['role_user' => 'admin']);
    Http::fake(['be.test/*' => Http::response(['status' => 'success', 'data' => []])]);

    foreach (['/', '/faskes', '/search', '/map', '/discussions', '/discussions/create', '/chat', '/profile', '/profile/edit'] as $url) {
        $status = $this->get($url)->status();
        expect($status)->toBeLessThan(500, "Halaman {$url} error ({$status})");
    }

    expect(Auth::user()->isAdmin())->toBeTrue()
        ->and(Auth::user()->birth_date->format('Y-m-d'))->toBe('2000-05-17');
});

test('remember me keeps user logged in after session expires', function () {
    Http::fake([
        'be.test/api/login' => Http::response(['data' => backendUser(), 'access_token' => 'tok-123']),
        'be.test/*' => Http::response(['data' => []]),
    ]);

    $login = $this->post('/login', ['email' => 'galih@example.com', 'password' => 'rahasia123', 'remember' => '1']);
    $cookie = collect($login->headers->getCookies())->first(fn ($c) => str_starts_with($c->getName(), 'remember_web_'));

    expect($cookie)->not->toBeNull()
        // Cookie berlaku 3 hari
        ->and(abs($cookie->getExpiresTime() - now()->addDays(3)->getTimestamp()))->toBeLessThan(60);

    // Session habis (misal browser ditutup / lewat 120 menit), cookie "Ingat saya" masih ada
    $this->flushSession();
    Auth::forgetGuards();

    $this->withUnencryptedCookie($cookie->getName(), $cookie->getValue())->get('/discussions')->assertOk()->assertSee('Galih');

    expect(Auth::id())->toBe(7)->and(session('user_backend_token'))->toBe('tok-123');
});

test('without remember me no remember cookie is set', function () {
    Http::fake([
        'be.test/api/login' => Http::response(['data' => backendUser(), 'access_token' => 'tok-123']),
        'be.test/*' => Http::response(['data' => []]),
    ]);

    $login = $this->post('/login', ['email' => 'galih@example.com', 'password' => 'rahasia123']);

    expect(collect($login->headers->getCookies())->first(fn ($c) => str_starts_with($c->getName(), 'remember_web_')))->toBeNull();
});

test('logout invalidates remember me data', function () {
    Http::fake([
        'be.test/api/login' => Http::response(['data' => backendUser(), 'access_token' => 'tok-123']),
        'be.test/*' => Http::response(['data' => []]),
    ]);

    $login = $this->post('/login', ['email' => 'galih@example.com', 'password' => 'rahasia123', 'remember' => '1']);
    $cookie = collect($login->headers->getCookies())->first(fn ($c) => str_starts_with($c->getName(), 'remember_web_'));

    // Request baru: user dibaca ulang dari session (seperti di browser sungguhan)
    Auth::forgetGuards();
    $this->post('/logout');
    $this->flushSession();
    Auth::forgetGuards();

    // Cookie lama dipakai lagi setelah logout: tidak boleh login
    $this->withUnencryptedCookie($cookie->getName(), $cookie->getValue())->get('/discussions')->assertOk();
    expect(Auth::check())->toBeFalse();
});

test('forum list shows thumbnail from first discussion photo', function () {
    Http::fake(['be.test/api/discussions*' => Http::response(['data' => ['data' => [
        [
            'id_diskusi' => 1, 'judul_diskusi' => 'Dengan foto', 'isi_diskusi' => 'isi', 'kategori' => 'Umum',
            'author_name' => 'A', 'created_at' => now()->toDateTimeString(),
            'images_list' => ['http://be.test/storage/discussions/a.jpg', 'http://be.test/storage/discussions/b.jpg'],
            'thumbnail_url' => 'http://be.test/storage/discussions/a.jpg',
        ],
        [
            'id_diskusi' => 2, 'judul_diskusi' => 'Tanpa foto', 'isi_diskusi' => 'isi', 'kategori' => 'Umum',
            'author_name' => 'B', 'created_at' => now()->toDateTimeString(),
            'images_list' => [], 'thumbnail_url' => null,
        ],
    ]]])]);

    $html = $this->get('/discussions')->assertOk()->getContent();

    expect(substr_count($html, 'http://be.test/storage/discussions/a.jpg'))->toBe(1)
        ->and($html)->toContain('+1')
        ->and($html)->toContain('Tanpa foto')
        ->and($html)->not->toContain('Foto diskusi Tanpa foto');
});
