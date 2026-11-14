{{-- Layout halaman error MediGO. Mandiri (tanpa Vite/sesi) agar tetap tampil saat aplikasi bermasalah. --}}
@php
    $isAdminArea = request()->is('admin') || request()->is('admin/*');
    $homeUrl = $isAdminArea ? url('/admin') : url('/');
    $homeLabel = $isAdminArea ? 'Ke Dashboard Admin' : 'Kembali ke Beranda';
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('code') &middot; @yield('title') - MediGO</title>
    <link rel="icon" href="{{ asset('images/medigo_logo.png') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/errors.css') }}">
</head>
<body>
    <header class="err-header">
        <a href="{{ $homeUrl }}" aria-label="MediGO">
            <img src="{{ asset('images/medigo_logo.png') }}" alt="MediGO - Your Health, Faster.">
        </a>
    </header>

    <main class="err-main">
        <div class="err-card">
            <div class="err-icon" aria-hidden="true"><i class="bi @yield('icon', 'bi-exclamation-circle')"></i></div>
            <p class="err-code">@yield('code')</p>
            <h1 class="err-title">@yield('title')</h1>
            <p class="err-text">@yield('message')</p>
            <div class="err-actions">
                @hasSection('actions')
                    @yield('actions')
                @else
                    <a href="{{ $homeUrl }}" class="err-btn err-btn--primary"><i class="bi bi-house-door"></i> {{ $homeLabel }}</a>
                    <a href="javascript:history.back()" class="err-btn err-btn--ghost"><i class="bi bi-arrow-left"></i> Halaman Sebelumnya</a>
                @endif
            </div>
        </div>
    </main>

    <footer class="err-footer">&copy; {{ date('Y') }} MediGO &middot; Your Health, Faster.</footer>
</body>
</html>
