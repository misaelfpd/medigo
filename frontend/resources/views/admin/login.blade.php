<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Admin - MediGO</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/css/medigo-ui.css', 'resources/js/app.js', 'resources/js/core/toast.js'])
</head>

<body class="admin-login-body d-flex align-items-center justify-content-center min-vh-100 p-3">
    @include('partials.flash')

    <div class="card admin-login-card mg-admin-login-card p-4 p-md-5 w-100">
        <!-- Logo & Header -->
        <div class="text-center mb-4">
            <img src="{{ asset('images/medigo_logo.png') }}" alt="MediGO" class="mg-admin-login-logo mb-3">
            <h1 class="h5 fw-bold text-dark mb-1">Panel Admin</h1>
            <p class="text-secondary small mb-0">Masuk untuk mengelola platform MediGO</p>
        </div>

        <!-- Login Form -->
        <form action="{{ route('admin.login.post') }}" method="POST">
            @csrf

            <!-- Email Input -->
            <div class="mb-3">
                <label for="adminEmail" class="form-label fw-semibold text-dark small">Email Administrator</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-envelope"></i></span>
                    <input type="email" name="email" id="adminEmail" class="form-control mg-input-icon-field border-start-0 @error('email') is-invalid @enderror" placeholder="nama@email.com" value="{{ old('email') }}" required autofocus>
                </div>
            </div>

            <!-- Password Input -->
            <div class="mb-3">
                <label for="adminPassword" class="form-label fw-semibold text-dark small">Kata Sandi</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock"></i></span>
                    <input type="password" name="password" id="adminPassword" class="form-control mg-input-icon-field border-start-0 @error('password') is-invalid @enderror" placeholder="Masukkan kata sandi" value="" required>
                </div>
            </div>

            <div class="mb-4"></div>

            <!-- Submit Button -->
            <button type="submit" class="btn mg-btn-primary w-100 py-2 fw-semibold">
                Masuk ke Dashboard
            </button>
        </form>

        <div class="text-center mt-4">
            <a href="{{ url('/') }}" class="text-decoration-none text-muted small d-inline-flex align-items-center gap-1">
                <i class="bi bi-arrow-left"></i> Kembali ke Beranda Pengguna
            </a>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
