<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin Dashboard - MediGO')</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- Chart.js for Dashboard Charts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    @stack('styles')
</head>

<body class="admin-body">

    <div class="admin-layout-wrapper d-flex">

        <!-- =====================================================================
             ADMIN SIDEBAR (LEFT)
             ===================================================================== -->
        <aside class="admin-sidebar d-flex flex-column justify-content-between p-3 border-end bg-white" id="adminSidebar">
            <div>
                <!-- Brand Section -->
                <div class="admin-brand-wrapper px-3 py-3 mb-4">
                    <a href="{{ route('admin.dashboard') }}" class="d-flex align-items-center gap-2 text-decoration-none admin-brand">
                        <i class="bi bi-hospital navbar-brand-icon" style="font-size: 1.8rem; color: #002b5e;"></i>
                        <div>
                            <div class="fw-bold text-dark" style="font-size: 1.25rem; line-height: 1.1;">Medi<span style="color: #0b5ed7;">GO</span></div>
                            <span class="text-muted" style="font-size: 0.65rem; font-weight: 500;">Your Health, Faster.</span>
                        </div>
                    </a>
                </div>

                <!-- Navigation Links -->
                <nav class="admin-nav d-flex flex-column gap-1">
                    <!-- Dashboard -->
                    <a href="{{ route('admin.dashboard') }}" class="admin-nav-item {{ request()->is('admin/dashboard') || request()->is('admin') ? 'active' : '' }}">
                        <i class="bi bi-grid-fill admin-nav-icon"></i>
                        <span>Dashboard</span>
                    </a>

                    <!-- Rumah Sakit -->
                    <a href="{{ route('admin.faskes') }}" class="admin-nav-item {{ request()->is('admin/faskes*') ? 'active' : '' }}">
                        <i class="bi bi-plus-square-fill admin-nav-icon"></i>
                        <span>Rumah Sakit</span>
                    </a>

                    <!-- Forum -->
                    <a href="{{ route('admin.forum') }}" class="admin-nav-item {{ request()->is('admin/forum*') ? 'active' : '' }}">
                        <i class="bi bi-chat-left-text admin-nav-icon"></i>
                        <span>Forum</span>
                    </a>

                    <!-- Laporan Diskusi -->
                    <a href="{{ route('admin.discussion-reports') }}" class="admin-nav-item {{ request()->is('admin/discussion-reports*') ? 'active' : '' }}">
                        <i class="bi bi-chat-square-quote-fill admin-nav-icon"></i>
                        <span>Laporan Diskusi</span>
                    </a>

                    <!-- Laporan Komentar -->
                    <a href="{{ route('admin.comment-reports') }}" class="admin-nav-item {{ request()->is('admin/comment-reports*') ? 'active' : '' }}">
                        <i class="bi bi-flag-fill admin-nav-icon"></i>
                        <span>Laporan Komentar</span>
                    </a>

                    <!-- Masukan Data Faskes -->
                    <a href="{{ route('admin.faskes-feedbacks') }}" class="admin-nav-item {{ request()->is('admin/feedback-faskes*') ? 'active' : '' }}">
                        <i class="bi bi-patch-question-fill admin-nav-icon"></i>
                        <span>Masukan Data Faskes</span>
                    </a>
                </nav>
            </div>

            <!-- Bottom Section: Keluar (Logout) -->
            <div class="admin-sidebar-bottom pt-3 border-top mt-auto">
                <a href="{{ route('admin.logout') }}" class="admin-logout-btn w-100 d-flex align-items-center gap-2 px-3 py-2 rounded-3 text-decoration-none bg-danger-subtle text-danger">
                    <i class="bi bi-box-arrow-left fs-5 text-danger"></i>
                    <span class="fw-semibold text-danger">Keluar</span>
                </a>
            </div>
        </aside>

        <!-- =====================================================================
             ADMIN MAIN CONTENT AREA (RIGHT)
             ===================================================================== -->
        <div class="admin-main-area flex-grow-1 d-flex flex-column min-vh-100">

            <!-- Top Header Bar -->
            <header class="admin-topbar d-flex align-items-center justify-content-between px-4 py-3 bg-white border-bottom sticky-top">
                <!-- Mobile Sidebar Toggle & Page Context -->
                <div class="d-flex align-items-center gap-3">
                    <button type="button" class="btn btn-sm btn-light d-lg-none" id="btnToggleSidebar">
                        <i class="bi bi-list fs-5"></i>
                    </button>
                    <span class="fw-bold text-dark d-none d-md-inline" style="font-size: 1.05rem;">
                        @yield('page_title', 'Admin Medigo')
                    </span>
                </div>

                <!-- Global Search & Actions -->
                <div class="d-flex align-items-center gap-3 flex-grow-1 justify-content-end max-w-lg">
                    <!-- Search Input Form -->
                    <form action="{{ route('admin.faskes') }}" method="GET" class="admin-search-wrapper position-relative flex-grow-1 max-w-md m-0" id="adminGlobalSearchForm">
                        @if(request('filter') && request('filter') !== 'all')
                            <input type="hidden" name="filter" value="{{ request('filter') }}">
                        @endif
                        <i class="bi bi-search admin-search-icon position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                        <input type="text" name="search" class="form-control admin-search-input ps-5 pe-3 py-2 rounded-3" placeholder="Cari rumah sakit, lokasi, dll..." value="{{ request('search') }}" id="adminGlobalSearch" autocomplete="off">
                    </form>

                    <!-- Notification Bell -->
                    <button type="button" class="btn btn-icon-round position-relative text-secondary p-2" title="Notifikasi">
                        <i class="bi bi-bell fs-5"></i>
                        <span class="position-absolute top-1 start-75 translate-middle p-1 bg-danger border border-light rounded-circle">
                            <span class="visually-hidden">New alerts</span>
                        </span>
                    </button>

                    <!-- Settings Gear -->
                    <button type="button" class="btn btn-icon-round text-secondary p-2" title="Pengaturan">
                        <i class="bi bi-gear fs-5"></i>
                    </button>

                    <!-- Admin Profile Avatar Dropdown -->
                    <div class="dropdown">
                        <button class="btn p-0 border-0 d-flex align-items-center dropdown-toggle" type="button" id="adminUserDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="outline: none;">
                            <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100&auto=format&fit=crop&q=80" alt="Admin Avatar" class="rounded-circle admin-avatar-img border">
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-4 mt-2 p-2" aria-labelledby="adminUserDropdown" style="min-width: 220px;">
                            <li class="px-3 py-2 border-bottom mb-1">
                                <div class="fw-bold text-dark small">{{ session('admin_user.name', 'Admin MediGO') }}</div>
                                <div class="text-muted small" style="font-size: 0.75rem;">{{ session('admin_user.email', 'admin@medigo.id') }}</div>
                            </li>
                            <li>
                                <a class="dropdown-item rounded-3 py-2 text-dark small d-flex align-items-center gap-2" href="{{ url('/') }}">
                                    <i class="bi bi-globe"></i> Lihat Web Publik
                                </a>
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <a href="{{ route('admin.logout') }}" class="dropdown-item rounded-3 py-2 text-danger small d-flex align-items-center gap-2">
                                    <i class="bi bi-box-arrow-left"></i> Keluar
                                </a>
                            </li>
                        </ul>
                    </div>

                    <!-- Direct Header Logout Button -->
                    <a href="{{ route('admin.logout') }}" class="btn btn-outline-danger btn-sm rounded-pill px-3 py-1.5 fw-semibold d-flex align-items-center gap-1 shadow-2xs text-decoration-none" title="Keluar">
                        <i class="bi bi-box-arrow-left"></i>
                        <span>Keluar</span>
                    </a>
                </div>
            </header>

            <!-- Flash Alerts -->
            <div class="px-4 pt-3">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-2xs d-flex align-items-center gap-2 mb-0" role="alert">
                        <i class="bi bi-check-circle-fill text-success fs-5"></i>
                        <span>{{ session('success') }}</span>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-2xs d-flex align-items-center gap-2 mb-0" role="alert">
                        <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
                        <span>{{ session('error') }}</span>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
            </div>

            <!-- Page Content -->
            <main class="admin-page-content p-4 flex-grow-1">
                @yield('content')
            </main>

        </div>

    </div>

    <!-- Logout Confirmation Modal -->
    <div class="modal fade" id="logoutConfirmModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 380px;">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-body text-center p-4">
                    <div class="d-inline-flex align-items-center justify-content-center bg-danger-subtle text-danger p-3 rounded-circle mb-3">
                        <i class="bi bi-box-arrow-right fs-2"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2" id="logoutModalLabel">Konfirmasi Keluar</h5>
                    <p class="text-secondary small mb-4">Apakah Anda yakin ingin keluar dari sesi administrator MediGO?</p>
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-light rounded-pill px-4 py-2 flex-grow-1 fw-semibold small" data-bs-dismiss="modal">Batal</button>
                        <form action="{{ route('admin.logout') }}" method="POST" class="m-0 flex-grow-1">
                            @csrf
                            <button type="submit" class="btn btn-danger rounded-pill px-4 py-2 w-100 fw-semibold small shadow-xs">Ya, Keluar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle mobile sidebar
        const btnToggle = document.getElementById('btnToggleSidebar');
        const sidebar = document.getElementById('adminSidebar');
        if (btnToggle && sidebar) {
            btnToggle.addEventListener('click', () => {
                sidebar.classList.toggle('show-mobile');
            });
        }
    </script>
    @stack('scripts')
</body>
</html>
