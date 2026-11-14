@extends('layouts.admin')

@section('title', 'Ringkasan Dashboard - Admin MediGO')
@section('page_title', 'Admin Medigo')

@section('content')
<div class="admin-dashboard-container">

    <!-- =========================================================================
         DASHBOARD HEADER (Title, Subtitle & Date Filter Button)
         ========================================================================= -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">Ringkasan Dashboard</h3>
            <p class="text-secondary mb-0" style="font-size: 0.92rem;">
                Selamat datang kembali. Inilah yang terjadi di platform hari ini.
            </p>
        </div>

        <span class="admin-period-chip">
            <i class="bi bi-calendar3"></i> Pertumbuhan dihitung 30 hari terakhir
        </span>
    </div>

    <!-- =========================================================================
         3 KPI STAT CARDS (Matching Mockup 2)
         ========================================================================= -->
    @php
        $kpiCards = [
            ['label' => 'Total Pengguna', 'icon' => 'bi-people-fill', 'value' => $stats['total_users'], 'pct' => $stats['user_growth_pct'], 'new' => $stats['new_users_30d'], 'unit' => 'pengguna baru'],
            ['label' => 'Total Faskes', 'icon' => 'bi-plus-square-fill', 'value' => $stats['total_faskes'], 'pct' => $stats['faskes_growth_pct'], 'new' => $stats['new_faskes_30d'], 'unit' => 'faskes baru'],
            ['label' => 'Diskusi Forum Aktif', 'icon' => 'bi-chat-left-text-fill', 'value' => $stats['total_discussions'], 'pct' => $stats['discussions_growth_pct'], 'new' => $stats['new_discussions_30d'], 'unit' => 'diskusi baru'],
        ];
    @endphp
    <div class="row g-3 g-lg-4 mb-4">
        @foreach($kpiCards as $card)
            <div class="col-12 col-md-4">
                <div class="card border rounded-4 shadow-2xs h-100 p-4 bg-white admin-stat-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="admin-stat-icon-box bg-medigo-subtle text-medigo-primary">
                            <i class="bi {{ $card['icon'] }} fs-5"></i>
                        </div>
                        @if($statsLoaded)
                            <span class="badge badge-stat-pill {{ $card['pct'] > 0 ? 'badge-stat-cyan' : 'badge-stat-gray' }}" title="Dibanding total sebelum 30 hari terakhir">
                                @if($card['pct'] > 0)<i class="bi bi-arrow-up-short"></i>@endif
                                {{ rtrim(rtrim(number_format($card['pct'], 1, ',', '.'), '0'), ',') }}%
                            </span>
                        @endif
                    </div>
                    <div class="text-secondary fw-medium mb-1" style="font-size: 0.84rem;">{{ $card['label'] }}</div>
                    <h2 class="fw-bold text-dark mb-1 stat-huge-number">
                        {{ $statsLoaded ? number_format($card['value'], 0, ',', '.') : '-' }}
                    </h2>
                    <div class="text-muted" style="font-size: 0.76rem;">
                        {{ $statsLoaded ? '+' . number_format($card['new'], 0, ',', '.') . ' ' . $card['unit'] . ' dalam 30 hari' : 'Data gagal dimuat dari server' }}
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- =========================================================================
         ROW 2: USER GROWTH CHART & RECENT ACTIVITY
         ========================================================================= -->
    <div class="row g-3 g-lg-4">

        <!-- Chart: Pertumbuhan Pengguna (Left 65%) -->
        <div class="col-12 col-lg-8">
            <div class="card border rounded-4 shadow-2xs h-100 bg-white p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-dark m-0">Pertumbuhan Pengguna</h5>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-light border rounded-3 dropdown-toggle text-dark fw-medium px-3" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            {{ $chartData['year'] === $currentYear ? 'Tahun Ini' : 'Tahun Lalu' }} ({{ $chartData['year'] }})
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border">
                            <li><a class="dropdown-item {{ $chartData['year'] === $currentYear ? 'active' : '' }}" href="{{ route('admin.dashboard', ['year' => $currentYear]) }}">Tahun Ini ({{ $currentYear }})</a></li>
                            <li><a class="dropdown-item {{ $chartData['year'] === $currentYear - 1 ? 'active' : '' }}" href="{{ route('admin.dashboard', ['year' => $currentYear - 1]) }}">Tahun Lalu ({{ $currentYear - 1 }})</a></li>
                        </ul>
                    </div>
                </div>

                <!-- Chart Canvas Container -->
                <p class="text-muted mb-2" style="font-size: 0.78rem;">Total pengguna terdaftar di akhir setiap bulan.</p>
                <div class="chart-container position-relative" style="min-height: 280px; width: 100%;">
                    <canvas id="userGrowthChart"
                            data-labels='@json($chartData['labels'])'
                            data-values='@json($chartData['values'])'></canvas>
                </div>
            </div>
        </div>

        <!-- Panel: Aktivitas Terbaru (Right 35%) -->
        <div class="col-12 col-lg-4">
            <div class="card border rounded-4 shadow-2xs h-100 bg-white p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-dark m-0">Aktivitas Terbaru</h5>
                </div>

                <!-- Activities List -->
                <div class="d-flex flex-column gap-3 mt-2">
                    @forelse($stats['activities'] as $act)
                        <div class="d-flex gap-3 align-items-start pb-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                            <div class="activity-avatar-circle flex-shrink-0 {{ $act['type'] === 'user' ? 'bg-primary-subtle text-primary' : 'bg-light text-secondary' }}">
                                <i class="bi {{ $act['icon'] }}"></i>
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <p class="text-dark mb-1" style="font-size: 0.84rem; line-height: 1.45;">
                                    {{ $act['title'] }}
                                    @if(!empty($act['user']))<span class="text-muted"> &mdash; {{ $act['user'] }}</span>@endif
                                </p>
                                <span class="text-muted" style="font-size: 0.74rem;">{{ $act['time'] }}</span>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted small mb-0">Belum ada aktivitas tercatat.</p>
                    @endforelse
                </div>
            </div>
        </div>

    </div>

</div>
@endsection

@push('scripts')
    @vite('resources/js/admin/dashboard.js')
@endpush
