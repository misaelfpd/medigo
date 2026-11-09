@extends('layouts.app')

@section('title', 'Masuk ke Akun Anda - MediGO')

@section('content')
<div class="auth-page-wrapper py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-5">
                
                <!-- Auth Card -->
                <div class="card auth-card border-0 rounded-4 shadow-sm p-4 p-md-5 bg-white position-relative overflow-hidden">
                    
                    <!-- Decorative top accent bar -->
                    <div class="auth-card-accent"></div>

                    <!-- Header / Logo -->
                    <div class="text-center mb-4 pt-2">
                        <div class="auth-icon-badge mx-auto mb-3">
                            <i class="bi bi-hospital"></i>
                        </div>
                        <h4 class="fw-bold text-dark mb-1">Selamat Datang Kembali</h4>
                        <p class="text-muted" style="font-size: 0.88rem;">
                            Masuk untuk mengakses forum diskusi, ulasan faskes, dan fitur personal lainnya.
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

                    @if (session('success'))
                        <div class="alert alert-success rounded-3 py-2 px-3 mb-4 d-flex align-items-center gap-2" role="alert" style="font-size: 0.84rem;">
                            <i class="bi bi-check-circle-fill flex-shrink-0"></i>
                            <div>{{ session('success') }}</div>
                        </div>
                    @endif

                    <!-- Login Form -->
                    <form action="{{ route('login.post') }}" method="POST" id="userLoginForm">
                        @csrf
                        @if(request('redirect'))
                            <input type="hidden" name="redirect" value="{{ request('redirect') }}">
                        @endif

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
                                       autocomplete="email" 
                                       autofocus>
                            </div>
                        </div>

                        <!-- Password Input -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label for="password" class="form-label fw-semibold text-dark mb-0" style="font-size: 0.86rem;">
                                    Kata Sandi
                                </label>
                            </div>
                            <div class="input-group auth-input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted">
                                    <i class="bi bi-lock"></i>
                                </span>
                                <input type="password" 
                                       class="form-control bg-light border-start-0 border-end-0 @error('password') is-invalid @enderror" 
                                       id="password" 
                                       name="password" 
                                       placeholder="••••••••" 
                                       required 
                                       autocomplete="current-password">
                                <button type="button" class="input-group-text bg-light border-start-0 text-muted btn-toggle-password" id="btnTogglePassword" tabindex="-1">
                                    <i class="bi bi-eye" id="togglePasswordIcon"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Remember Me -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                                <label class="form-check-label text-muted" for="remember" style="font-size: 0.84rem;">
                                    Ingat saya
                                </label>
                            </div>
                            <a href="#" class="text-medigo-primary text-decoration-none fw-semibold" style="font-size: 0.82rem;" onclick="alert('Silakan hubungi administrator atau buat akun baru.'); return false;">
                                Lupa kata sandi?
                            </a>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="btn btn-medigo-primary w-100 rounded-pill py-2-5 fw-semibold d-flex align-items-center justify-content-center gap-2 shadow-sm text-white mb-3">
                            <i class="bi bi-box-arrow-in-right"></i>
                            <span>Masuk Sekarang</span>
                        </button>
                    </form>

                    <!-- Divider -->
                    <div class="d-flex align-items-center my-3">
                        <hr class="flex-grow-1 my-0 text-muted opacity-25">
                        <span class="px-3 text-muted" style="font-size: 0.8rem;">atau</span>
                        <hr class="flex-grow-1 my-0 text-muted opacity-25">
                    </div>

                    <!-- Register Link -->
                    <div class="text-center">
                        <p class="text-muted mb-0" style="font-size: 0.88rem;">
                            Belum memiliki akun? 
                            <a href="{{ route('register') }}" class="text-medigo-primary fw-bold text-decoration-none">
                                Daftar Akun Baru
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
        const toggleBtn = document.getElementById('btnTogglePassword');
        const passwordInput = document.getElementById('password');
        const toggleIcon = document.getElementById('togglePasswordIcon');

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
