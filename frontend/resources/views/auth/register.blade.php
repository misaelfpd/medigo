@extends('layouts.auth')

@section('title', 'Daftar Akun Baru - MediGO')

@section('content')
  <div class="auth-page-wrapper d-flex align-items-center h-100 w-100"
    style="background-color: #f8f9fa;">
    <div class="container">
      <div class="row w-100 g-4 align-items-center justify-content-center m-0">

        <!-- Kolom Kiri: Auth Card (Form Register) -->
        <div class="col-12 col-md-8 col-lg-5 order-2 order-lg-1">
          <!-- Tambahan max-height dan overflow-y auto agar jika dilayar HP sangat pendek, hanya dalam card yang bisa discroll -->
          <div
            class="card auth-card border-0 rounded-4 shadow-sm p-3 p-md-4 bg-white position-relative mx-auto"
            style="max-width: 400px; max-height: 95vh; overflow-y: auto;">

            <!-- Tombol Kembali -->
            <div class="mb-2">
              <a href="{{ url('/') }}" class="text-decoration-none text-muted fw-semibold"
                style="font-size: 0.8rem;">
                <i class="bi bi-arrow-left me-1"></i>
              </a>
            </div>

            <!-- Header / Logo -->
            <div class="text-center mb-3">
              <div class="d-flex align-items-center justify-content-center gap-2 mb-2 text-primary">
                <div class="d-flex align-items-center justify-content-center gap-2 mb-3 text-primary">
                  <img class="w-50" src="{{ asset('images/medigo_logo.png') }}" alt="Logo Faskes">
                </div>
              </div>
              <h5 class="fw-bold mb-1" style="color: #0d2342; font-size: 1.1rem;">Daftar Akun Baru
              </h5>
              <p class="text-muted mb-0" style="font-size: 0.75rem;">
                Lengkapi data di bawah untuk bergabung dengan MediGO
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

            <!-- Register Form -->
            <form action="{{ route('register.post') }}" method="POST" id="userRegisterForm">
              @csrf

              <!-- Nama Lengkap Input -->
              <div class="mb-2">
                <label for="name" class="form-label fw-bold text-dark mb-1"
                  style="font-size: 0.75rem;">
                  Nama Lengkap
                </label>
                <div class="input-group input-group-sm auth-input-group">
                  <span class="input-group-text bg-white border-end-0 text-muted">
                    <i class="bi bi-person"></i>
                  </span>
                  <input type="text"
                    class="form-control border-start-0 @error('name') is-invalid @enderror"
                    id="name" name="name" value="{{ old('name') }}"
                    placeholder="Masukkan nama lengkap" required autofocus style="font-size: 0.8rem;">
                </div>
              </div>

              <!-- Username / Email Input -->
              <div class="mb-2">
                <label for="email" class="form-label fw-bold text-dark mb-1"
                  style="font-size: 0.75rem;">
                  Username / Email
                </label>
                <div class="input-group input-group-sm auth-input-group">
                  <span class="input-group-text bg-white border-end-0 text-muted">
                    <i class="bi bi-envelope-at"></i>
                  </span>
                  <input type="email"
                    class="form-control border-start-0 @error('email') is-invalid @enderror"
                    id="email" name="email" value="{{ old('email') }}"
                    placeholder="Masukkan email atau username" required autocomplete="email"
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
                    id="password" name="password" placeholder="Buat kata sandi" required
                    autocomplete="new-password" style="font-size: 0.8rem;">
                  <button type="button"
                    class="input-group-text bg-white border-start-0 text-muted btn-toggle-password"
                    onclick="togglePassword('password', 'icon-pass')" tabindex="-1">
                    <i class="bi bi-eye" id="icon-pass"></i>
                  </button>
                </div>
              </div>

              <!-- Konfirmasi Password Input -->
              <div class="mb-3">
                <label for="password_confirmation" class="form-label fw-bold text-dark mb-1"
                  style="font-size: 0.75rem;">
                  Konfirmasi Kata Sandi
                </label>
                <div class="input-group input-group-sm auth-input-group">
                  <span class="input-group-text bg-white border-end-0 text-muted">
                    <i class="bi bi-shield-lock"></i>
                  </span>
                  <input type="password" class="form-control border-start-0 border-end-0"
                    id="password_confirmation" name="password_confirmation"
                    placeholder="Ulangi kata sandi" required autocomplete="new-password"
                    style="font-size: 0.8rem;">
                  <button type="button"
                    class="input-group-text bg-white border-start-0 text-muted btn-toggle-password"
                    onclick="togglePassword('password_confirmation', 'icon-pass-confirm')"
                    tabindex="-1">
                    <i class="bi bi-eye" id="icon-pass-confirm"></i>
                  </button>
                </div>
              </div>

              <!-- Submit Button -->
              <button type="submit"
                class="btn btn-sm w-100 rounded-3 py-1 fw-bold text-white mb-2 mt-1"
                style="background-color: #0b2b52; font-size: 0.85rem;">
                Daftar
              </button>
            </form>

            <!-- Login Link -->
            <div class="text-center mt-2">
              <p class="text-muted mb-0" style="font-size: 0.75rem;">
                Sudah punya akun?
                <a href="{{ route('login') }}"
                  class="text-medigo-primary fw-bold text-decoration-none">
                  Masuk
                </a>
              </p>
            </div>

          </div>
        </div>

        <!-- Kolom Kanan: Hero Section (Hilang di Mobile) -->
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
    <style>
      /* Styling agar seragam dengan login */
      .auth-input-group .form-control:focus {
        box-shadow: none;
        border-color: #dee2e6;
      }

      .auth-input-group:focus-within {
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        border-radius: 0.25rem;
      }

      /* Kustomisasi scrollbar dalam card jika layar terlalu pendek */
      .auth-card::-webkit-scrollbar {
        width: 6px;
      }

      .auth-card::-webkit-scrollbar-thumb {
        background-color: #dee2e6;
        border-radius: 10px;
      }
    </style>
    <script>
      // Fungsi reusable untuk toggle kedua input password
      function togglePassword(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);

        if (input && icon) {
          const isPassword = input.getAttribute('type') === 'password';
          input.setAttribute('type', isPassword ? 'text' : 'password');
          icon.classList.toggle('bi-eye', !isPassword);
          icon.classList.toggle('bi-eye-slash', isPassword);
        }
      }
    </script>
  @endpush
@endsection
