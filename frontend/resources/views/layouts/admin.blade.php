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
    @vite(['resources/css/app.css', 'resources/css/medigo-ui.css', 'resources/js/app.js', 'resources/js/core/toast.js'])
    
    @stack('styles')
</head>

<body class="admin-body">

    <div class="admin-layout-wrapper d-flex">

        <!-- =====================================================================
             ADMIN SIDEBAR (LEFT)
             ===================================================================== -->
        <aside class="admin-sidebar d-flex flex-column p-3 border-end bg-white" id="adminSidebar">
            <div>
                <!-- Brand Section -->
                <div class="admin-brand-wrapper px-3 py-3 mb-4">
                    <a href="{{ route('admin.dashboard') }}" class="d-flex align-items-center text-decoration-none admin-brand" aria-label="MediGO Admin">
                        <img src="{{ asset('images/medigo_logo.png') }}" alt="MediGO - Your Health, Faster." class="admin-brand-logo">
                    </a>
                </div>

                @php
                    $pending = $adminPendingCounts ?? \App\Support\AdminPendingCounts::EMPTY;
                    $adminMenu = [
                        ['route' => 'admin.dashboard', 'active' => ['admin', 'admin/dashboard'], 'icon' => 'bi-grid-fill', 'label' => 'Dashboard'],
                        ['route' => 'admin.faskes', 'active' => ['admin/faskes*'], 'icon' => 'bi-plus-square-fill', 'label' => 'Rumah Sakit'],
                        ['route' => 'admin.fasilitas', 'active' => ['admin/fasilitas*'], 'icon' => 'bi-building-add', 'label' => 'Fasilitas Penunjang'],
                        ['route' => 'admin.spesialisasi', 'active' => ['admin/spesialisasi*'], 'icon' => 'bi-heart-pulse-fill', 'label' => 'Spesialisasi Medis'],
                        ['route' => 'admin.forum', 'active' => ['admin/forum*'], 'icon' => 'bi-chat-left-text', 'label' => 'Forum'],
                        ['route' => 'admin.discussion-reports', 'active' => ['admin/discussion-reports*'], 'icon' => 'bi-chat-square-quote-fill', 'label' => 'Laporan Diskusi', 'count' => $pending['discussion_reports']],
                        ['route' => 'admin.discussion-comment-reports', 'active' => ['admin/discussion-comment-reports*'], 'icon' => 'bi-chat-dots-fill', 'label' => 'Laporan Komentar Diskusi', 'count' => $pending['discussion_comment_reports']],
                        ['route' => 'admin.comment-reports', 'active' => ['admin/comment-reports*'], 'icon' => 'bi-flag-fill', 'label' => 'Laporan Ulasan Faskes', 'count' => $pending['faskes_comment_reports']],
                        ['route' => 'admin.faskes-feedbacks', 'active' => ['admin/feedback-faskes*'], 'icon' => 'bi-patch-question-fill', 'label' => 'Masukan Data Faskes', 'count' => $pending['faskes_feedbacks']],
                    ];
                @endphp

                <!-- Navigation Links -->
                <nav class="admin-nav d-flex flex-column gap-1">
                    @foreach($adminMenu as $menu)
                        @php $count = (int) ($menu['count'] ?? 0); @endphp
                        <a href="{{ route($menu['route']) }}" class="admin-nav-item {{ request()->is(...$menu['active']) ? 'active' : '' }}">
                            <i class="bi {{ $menu['icon'] }} admin-nav-icon"></i>
                            <span class="flex-grow-1">{{ $menu['label'] }}</span>
                            @if($count > 0)
                                <span class="admin-nav-badge" title="{{ $count }} menunggu ditinjau">{{ $count > 99 ? '99+' : $count }}</span>
                            @endif
                        </a>
                    @endforeach
                </nav>
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

                    <!-- Notifikasi: laporan & masukan yang menunggu ditinjau -->
                    <div class="dropdown">
                        <button type="button" class="btn btn-icon-round position-relative text-secondary p-2" title="Notifikasi" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-bell fs-5"></i>
                            @if($pending['total'] > 0)
                                <span class="admin-bell-badge">{{ $pending['total'] > 99 ? '99+' : $pending['total'] }}</span>
                            @endif
                        </button>
                        <div class="dropdown-menu dropdown-menu-end shadow border-0 rounded-4 mt-2 p-2 admin-notif-menu">
                            <div class="px-3 py-2 fw-bold text-dark small border-bottom mb-1">Menunggu Ditinjau</div>
                            @if($pending['total'] > 0)
                                @foreach([
                                    ['admin.discussion-reports', 'discussion_reports', 'Laporan diskusi', 'bi-chat-square-quote'],
                                    ['admin.discussion-comment-reports', 'discussion_comment_reports', 'Laporan komentar diskusi', 'bi-chat-dots'],
                                    ['admin.comment-reports', 'faskes_comment_reports', 'Laporan ulasan faskes', 'bi-flag'],
                                    ['admin.faskes-feedbacks', 'faskes_feedbacks', 'Masukan data faskes', 'bi-patch-question'],
                                ] as [$notifRoute, $notifKey, $notifLabel, $notifIcon])
                                    @if($pending[$notifKey] > 0)
                                        <a class="dropdown-item rounded-3 py-2 small d-flex align-items-center gap-2" href="{{ route($notifRoute) }}">
                                            <i class="bi {{ $notifIcon }}"></i>
                                            <span class="flex-grow-1">{{ $notifLabel }}</span>
                                            <span class="admin-nav-badge">{{ $pending[$notifKey] }}</span>
                                        </a>
                                    @endif
                                @endforeach
                            @else
                                <div class="px-3 py-3 text-muted small text-center">Tidak ada laporan baru.</div>
                            @endif
                        </div>
                    </div>

                    <!-- Admin Profile Avatar Dropdown -->
                    <div class="dropdown">
                        <button class="btn p-0 border-0 d-flex align-items-center dropdown-toggle" type="button" id="adminUserDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="outline: none;">
                            <x-avatar :name="session('admin_user.name', 'Admin')" :size="36" />
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
                                <button type="button" class="dropdown-item rounded-3 py-2 text-danger small d-flex align-items-center gap-2 border-0 bg-transparent w-100 text-start" data-bs-toggle="modal" data-bs-target="#logoutConfirmModal">
                                    <i class="bi bi-box-arrow-left"></i> Keluar
                                </button>
                            </li>
                        </ul>
                    </div>

                    <!-- Direct Header Logout Button -->
                    <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3 py-1.5 fw-semibold d-flex align-items-center gap-1 shadow-2xs text-decoration-none" title="Keluar" data-bs-toggle="modal" data-bs-target="#logoutConfirmModal">
                        <i class="bi bi-box-arrow-left"></i>
                        <span>Keluar</span>
                    </button>
                </div>
            </header>

            {{-- Pesan flash -> toast global --}}
            @include('partials.flash')

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

    <!-- Modal konfirmasi aksi admin (dipakai form dengan atribut data-confirm) -->
    <div class="modal fade" id="adminConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-body text-center p-4">
                    <div class="d-inline-flex align-items-center justify-content-center bg-danger-subtle text-danger p-3 rounded-circle mb-3">
                        <i class="bi bi-exclamation-triangle fs-3"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2" data-confirm-title>Konfirmasi</h5>
                    <p class="text-secondary small mb-4" data-confirm-message></p>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-light rounded-pill px-4 fw-semibold flex-grow-1" data-bs-dismiss="modal">Batal</button>
                        <button type="button" class="btn btn-danger rounded-pill px-4 fw-semibold flex-grow-1" data-confirm-ok>Ya, Lanjutkan</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @vite('resources/js/admin/layout.js')
    @stack('scripts')
</body>
</html>
