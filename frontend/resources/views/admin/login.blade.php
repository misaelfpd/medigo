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
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="admin-login-body d-flex align-items-center justify-content-center min-vh-100 p-3">

    <div class="card border-0 rounded-4 shadow-lg admin-login-card p-4 p-md-5 w-100" style="max-width: 440px;">
        <!-- Logo & Header -->
        <div class="text-center mb-4">
            <div class="d-inline-flex align-items-center justify-content-center bg-medigo-subtle p-3 rounded-circle mb-3">
                <i class="bi bi-hospital text-medigo-primary fs-2"></i>
            </div>
            <h4 class="fw-bold text-dark mb-1">Admin Medi<span style="color: #0b5ed7;">GO</span></h4>
            <p class="text-secondary small mb-0">Masuk untuk mengelola platform MediGO</p>
        </div>

        <!-- Alert Error Message -->
        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show rounded-3 p-3 small d-flex align-items-center gap-2 mb-4" role="alert">
                <i class="bi bi-exclamation-circle-fill text-danger flex-shrink-0"></i>
                <div>{{ $errors->first() }}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show rounded-3 p-3 small d-flex align-items-center gap-2 mb-4" role="alert">
                <i class="bi bi-check-circle-fill text-success flex-shrink-0"></i>
                <div>{{ session('success') }}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Login Form -->
        <form action="{{ route('admin.login.post') }}" method="POST">
            @csrf

            <!-- Email Input -->
            <div class="mb-3">
                <label for="adminEmail" class="form-label fw-semibold text-dark small">Email Administrator</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-envelope"></i></span>
                    <input type="email" name="email" id="adminEmail" class="form-control border-start-0 ps-0 @error('email') is-invalid @enderror" placeholder="nama@email.com" value="{{ old('email') }}" required autofocus>
                </div>
            </div>

            <!-- Password Input -->
            <div class="mb-3">
                <label for="adminPassword" class="form-label fw-semibold text-dark small">Kata Sandi</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock"></i></span>
                    <input type="password" name="password" id="adminPassword" class="form-control border-start-0 ps-0 @error('password') is-invalid @enderror" placeholder="Masukkan kata sandi" value="" required>
                </div>
            </div>

            <!-- Remember Me -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="remember" id="rememberMe">
                    <label class="form-check-label text-secondary small" for="rememberMe">
                        Ingat saya
                    </label>
                </div>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn btn-medigo-primary w-100 py-2 rounded-pill fw-semibold shadow-xs">
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
