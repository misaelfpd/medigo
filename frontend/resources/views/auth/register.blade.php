@extends('layouts.app')

@section('title', 'Daftar Akun Baru - MediGO')

@section('content')
<div class="auth-page-wrapper py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-9 col-lg-6">
                
                <!-- Register Card -->
                <div class="card auth-card border-0 rounded-4 shadow-sm p-4 p-md-5 bg-white position-relative overflow-hidden">
                    
                    <!-- Decorative top accent bar -->
                    <div class="auth-card-accent"></div>

                    <!-- Header / Logo -->
                    <div class="text-center mb-4 pt-2">
                        <div class="auth-icon-badge mx-auto mb-3">
                            <i class="bi bi-person-plus-fill"></i>
                        </div>
                        <h4 class="fw-bold text-dark mb-1">Daftar Akun MediGO</h4>
                        <p class="text-muted" style="font-size: 0.88rem;">
                            Bergabunglah untuk menikmati akses forum diskusi, memberikan review faskes, dan fitur personal lainnya.
                        </p>
                    </div>

                    <!-- Alert Errors -->
                    @if (isset($errors) && $errors->any())
                        <div class="alert alert-danger rounded-3 py-2 px-3 mb-4 d-flex align-items-center gap-2" role="alert" style="font-size: 0.84rem;">
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

                        <!-- Full Name Input -->
                        <div class="mb-3">
                            <label for="name" class="form-label fw-semibold text-dark" style="font-size: 0.86rem;">
                                Nama Lengkap
                            </label>
                            <div class="input-group auth-input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted">
                                    <i class="bi bi-person"></i>
                                </span>
                                <input type="text" 
                                       class="form-control bg-light border-start-0 @error('name') is-invalid @enderror" 
                                       id="name" 
                                       name="name" 
                                       value="{{ old('name') }}" 
                                       placeholder="Nama Lengkap Anda" 
                                       required 
                                       autocomplete="name" 
                                       autofocus>
                            </div>
                        </div>

                        <!-- Email Input -->
                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold text-dark" style="font-size: 0.86rem;">
                                Alamat Email
                            </label>
                            <div class="input-group auth-input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted">
                                    <i class="bi bi-envelope"></i>
                                </span>
                                <input type="email" 
                                       class="form-control bg-light border-start-0 @error('email') is-invalid @enderror" 
                                       id="email" 
                                       name="email" 
                                       value="{{ old('email') }}" 
                                       placeholder="nama@email.com" 
                                       required 
                                       autocomplete="email">
                            </div>
                        </div>

                        <!-- Password Input -->
                        <div class="mb-3">
                            <label for="password" class="form-label fw-semibold text-dark" style="font-size: 0.86rem;">
                                Kata Sandi
                            </label>
                            <div class="input-group auth-input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted">
                                    <i class="bi bi-lock"></i>
                                </span>
                                <input type="password" 
                                       class="form-control bg-light border-start-0 border-end-0 @error('password') is-invalid @enderror" 
                                       id="password" 
                                       name="password" 
                                       placeholder="Minimal 8 karakter" 
                                       required 
                                       autocomplete="new-password">
                                <button type="button" class="input-group-text bg-light border-start-0 text-muted btn-toggle-password" id="btnToggleRegPassword" tabindex="-1">
                                    <i class="bi bi-eye" id="toggleRegPasswordIcon"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Password Confirmation Input -->
                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label fw-semibold text-dark" style="font-size: 0.86rem;">
                                Konfirmasi Kata Sandi
                            </label>
                            <div class="input-group auth-input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted">
                                    <i class="bi bi-shield-check"></i>
                                </span>
                                <input type="password" 
                                       class="form-control bg-light border-start-0" 
                                       id="password_confirmation" 
                                       name="password_confirmation" 
                                       placeholder="Ulangi kata sandi" 
                                       required 
                                       autocomplete="new-password">
                            </div>
                        </div>

                        <!-- Terms & Conditions Checkbox -->
                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input @error('terms') is-invalid @enderror" type="checkbox" name="terms" id="terms" required {{ old('terms') ? 'checked' : '' }}>
                                <label class="form-check-label text-muted" for="terms" style="font-size: 0.82rem; line-height: 1.4;">
                                    Saya menyetujui <a href="#" class="text-medigo-primary fw-semibold text-decoration-none">Syarat & Ketentuan</a> serta <a href="#" class="text-medigo-primary fw-semibold text-decoration-none">Kebijakan Privasi</a> MediGO.
                                </label>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="btn btn-medigo-primary w-100 rounded-pill py-2-5 fw-semibold d-flex align-items-center justify-content-center gap-2 shadow-sm text-white mb-3">
                            <i class="bi bi-person-check-fill"></i>
                            <span>Daftar Sekarang</span>
                        </button>
                    </form>

                    <!-- Divider -->
                    <div class="d-flex align-items-center my-3">
                        <hr class="flex-grow-1 my-0 text-muted opacity-25">
                        <span class="px-3 text-muted" style="font-size: 0.8rem;">atau</span>
                        <hr class="flex-grow-1 my-0 text-muted opacity-25">
                    </div>

                    <!-- Login Link -->
                    <div class="text-center">
                        <p class="text-muted mb-0" style="font-size: 0.88rem;">
                            Sudah memiliki akun? 
                            <a href="{{ route('login') }}" class="text-medigo-primary fw-bold text-decoration-none">
                                Masuk ke Akun
                            </a>
                        </p>
                    </div>

                </div>

            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const toggleBtn = document.getElementById('btnToggleRegPassword');
        const passwordInput = document.getElementById('password');
        const toggleIcon = document.getElementById('toggleRegPasswordIcon');

        if (toggleBtn && passwordInput && toggleIcon) {
            toggleBtn.addEventListener('click', function () {
                const isPassword = passwordInput.getAttribute('type') === 'password';
                passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
                toggleIcon.classList.toggle('bi-eye', !isPassword);
                toggleIcon.classList.toggle('bi-eye-slash', isPassword);
            });
        }
    });
</script>
@endpush
@endsection
