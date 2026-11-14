@extends('layouts.auth')

@section('title', 'Masuk ke Akun Anda - MediGO')

@section('content')
  <div class="auth-page-wrapper d-flex align-items-center h-100 w-100"
    style="background-color: #f8f9fa;">
    <div class="container">
      <!-- Mengurangi gap antar kolom dari g-5 menjadi g-4 -->
      <div class="row w-100 g-4 align-items-center justify-content-center m-0">

        <!-- Kolom Kiri: Auth Card (Form Login) -->
        <div class="col-12 col-md-8 col-lg-5 order-2 order-lg-1">
          <!-- Padding card diperkecil menjadi p-3 p-md-4 -->
          <div
            class="card auth-card border-0 rounded-4 shadow-sm p-3 p-md-4 bg-white position-relative overflow-hidden mx-auto"
            style="max-width: 400px;">

            <!-- Tombol Kembali -->
            <div class="mb-3">
              <a href="{{ url('/') }}" class="text-decoration-none text-muted fw-semibold"
                style="font-size: 0.8rem;">
                <i class="bi bi-arrow-left me-1"></i>
              </a>
            </div>

            <!-- Header / Logo -->
            <div class="text-center mb-3">
              <div class="d-flex align-items-center justify-content-center gap-2 mb-3 text-primary">
                <img class="w-50" src="{{ asset('images/medigo_logo.png') }}" alt="Logo Faskes">
              </div>
              <h5 class="fw-bold mb-1" style="color: #0d2342; font-size: 1.1rem;">Selamat Datang di
                Medigo</h5>
              <p class="text-muted mb-0" style="font-size: 0.75rem;">
                Masukkan username dan password yang terdaftar
              </p>
            </div>

            <!-- Alert Errors -->
            @if (isset($errors) && $errors->any())
              <div class="alert alert-danger rounded-3 py-1 px-2 mb-3 d-flex align-items-center gap-2"
                role="alert" style="font-size: 0.75rem;">
                <i class="bi bi-exclamation-circle-fill flex-shrink-0"></i>
                <div>
                  @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                  @endforeach
                </div>
              </div>
            @endif

            <!-- Login Form -->
            <form action="{{ route('login.post') }}" method="POST" id="userLoginForm">
              @csrf
              @if (request('redirect'))
                <input type="hidden" name="redirect" value="{{ request('redirect') }}">
              @endif

              <!-- Username / Email Input -->
              <div class="mb-2">
                <label for="email" class="form-label fw-bold text-dark mb-1"
                  style="font-size: 0.75rem;">
                  Username
                </label>
                <div class="input-group input-group-sm auth-input-group">
                  <span class="input-group-text bg-white border-end-0 text-muted">
                    <i class="bi bi-person"></i>
                  </span>
                  <input type="email"
                    class="form-control border-start-0 @error('email') is-invalid @enderror"
                    id="email" name="email" value="{{ old('email') }}"
                    placeholder="Masukkan nama pengguna" required autocomplete="email" autofocus
                    style="font-size: 0.8rem;">
                </div>
              </div>

              <!-- Password Input -->
              <div class="mb-2">
                <label for="password" class="form-label fw-bold text-dark mb-1"
                  style="font-size: 0.75rem;">
                  Kata Sandi
                </label>
                <div class="input-group input-group-sm auth-input-group">
                  <span class="input-group-text bg-white border-end-0 text-muted">
                    <i class="bi bi-lock"></i>
                  </span>
                  <input type="password"
                    class="form-control border-start-0 border-end-0 @error('password') is-invalid @enderror"
                    id="password" name="password" placeholder="Masukkan kata sandi" required
                    autocomplete="current-password" style="font-size: 0.8rem;">
                  <button type="button"
                    class="input-group-text bg-white border-start-0 text-muted btn-toggle-password"
                    id="btnTogglePassword" data-toggle-password="password" aria-label="Tampilkan kata sandi" tabindex="-1">
                    <i class="bi bi-eye" id="togglePasswordIcon"></i>
                  </button>
                </div>
              </div>

              <!-- Ingat Saya & Lupa Kata Sandi -->
              <div class="mb-3 mt-1 d-flex justify-content-between align-items-center">
                <div class="form-check mb-0">
                  <input class="form-check-input" type="checkbox" name="remember" value="1" id="remember"
                    {{ old('remember') ? 'checked' : '' }}>
                  <label class="form-check-label text-muted" for="remember" style="font-size: 0.75rem;">
                    Ingat saya 
                  </label>
                </div>
                <a href="#" class="text-dark text-decoration-none fw-bold"
                  style="font-size: 0.75rem;"
                  data-info-toast="Untuk mengatur ulang kata sandi, silakan hubungi administrator MediGO.">
                  Lupa Kata Sandi?
                </a>
              </div>

              <!-- Submit Button -->
              <button type="submit" class="btn btn-sm w-100 rounded-3 py-1 fw-bold text-white mb-3"
                style="background-color: #0b2b52; font-size: 0.85rem;">
                Masuk
              </button>
            </form>

            <!-- Register Link -->
            <div class="text-center">
              <p class="text-muted mb-0" style="font-size: 0.75rem;">
                Belum punya akun?
                <a href="{{ route('register', request('redirect') ? ['redirect' => request('redirect')] : []) }}"
                  class="fw-bold text-decoration-none text-medigo-primary">
                  Daftar Sekarang
                </a>
              </p>
            </div>

          </div>
        </div>

        <!-- Kolom Kanan: Hero Section (Ukuran teks diperkecil) -->
        <div class="col-12 col-lg-6 d-none d-lg-block order-1 order-lg-2 ps-lg-4">
          <h1 class="fw-bold mb-2" style="font-size: 2.25rem; color: #0d2342; line-height: 1.1;">
            Temukan Layanan<br>Kesehatan Terdekat
          </h1>
          <p class="text-muted" style="max-width: 450px; font-size: 0.85rem;">
            Temukan rumah sakit dan klinik terbaik di dekat Anda dengan cepat. Akses berbagai fitur
            eksklusif untuk membantu anda menemukan layanan kesehatan yang dapat diandalkan saat Anda
            sangat membutuhkannya.
          </p>
        </div>

      </div>
    </div>
  </div>

  @push('scripts')
    @vite(['resources/css/pages/auth.css', 'resources/js/pages/auth.js'])
  @endpush
@endsection
