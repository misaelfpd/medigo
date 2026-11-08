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

        <!-- Date Filter Button (Matching Mockup) -->
        <div>
            <button type="button" class="btn btn-white bg-white border rounded-3 px-3 py-2 fw-medium text-dark shadow-2xs d-inline-flex align-items-center gap-2" style="font-size: 0.88rem;">
                <i class="bi bi-calendar3 text-muted"></i>
                <span>30 Hari Terakhir</span>
            </button>
        </div>
    </div>

    <!-- =========================================================================
         3 KPI STAT CARDS (Matching Mockup 2)
         ========================================================================= -->
    <div class="row g-3 g-lg-4 mb-4">

        <!-- Card 1: Total Pengguna -->
        <div class="col-12 col-md-4">
            <div class="card border rounded-4 shadow-2xs h-100 p-4 bg-white admin-stat-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="admin-stat-icon-box bg-medigo-subtle text-medigo-primary">
                        <i class="bi bi-people-fill fs-5"></i>
                    </div>
                    <span class="badge badge-stat-pill badge-stat-cyan">
                        <i class="bi bi-arrow-up-short"></i> {{ $stats['user_growth_pct'] }}%
                    </span>
                </div>
                <div class="text-secondary fw-medium mb-1" style="font-size: 0.84rem;">Total Pengguna</div>
                <h2 class="fw-bold text-dark mb-0 stat-huge-number">
                    {{ is_numeric($stats['total_users']) ? number_format($stats['total_users'], 0, ',', '.') : $stats['total_users'] }}
                </h2>
            </div>
        </div>

        <!-- Card 2: Total Rumah Sakit -->
        <div class="col-12 col-md-4">
            <div class="card border rounded-4 shadow-2xs h-100 p-4 bg-white admin-stat-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="admin-stat-icon-box bg-medigo-subtle text-medigo-primary">
                        <i class="bi bi-plus-square-fill fs-5"></i>
                    </div>
                    <span class="badge badge-stat-pill badge-stat-cyan">
                        <i class="bi bi-arrow-up-short"></i> {{ $stats['faskes_growth_pct'] }}%
                    </span>
                </div>
                <div class="text-secondary fw-medium mb-1" style="font-size: 0.84rem;">Total Rumah Sakit</div>
                <h2 class="fw-bold text-dark mb-0 stat-huge-number">
                    {{ $stats['total_faskes'] }}
                </h2>
            </div>
        </div>

        <!-- Card 3: Postingan Forum Aktif -->
        <div class="col-12 col-md-4">
            <div class="card border rounded-4 shadow-2xs h-100 p-4 bg-white admin-stat-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="admin-stat-icon-box bg-light text-secondary">
                        <i class="bi bi-chat-left-text-fill fs-5"></i>
                    </div>
                    <span class="badge badge-stat-pill badge-stat-gray">
                        — {{ $stats['discussions_growth_pct'] }}%
                    </span>
                </div>
                <div class="text-secondary fw-medium mb-1" style="font-size: 0.84rem;">Postingan Forum Aktif</div>
                <h2 class="fw-bold text-dark mb-0 stat-huge-number">
                    {{ is_numeric($stats['total_discussions']) ? number_format($stats['total_discussions'], 0, ',', '.') : $stats['total_discussions'] }}
                </h2>
            </div>
        </div>

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
                        <button class="btn btn-sm btn-light border rounded-3 dropdown-toggle text-dark fw-medium px-3" type="button" data-bs-toggle="dropdown">
                            Tahun Ini
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border">
                            <li><a class="dropdown-item active" href="#">Tahun Ini</a></li>
                            <li><a class="dropdown-item" href="#">Tahun Lalu</a></li>
                            <li><a class="dropdown-item" href="#">Semua Waktu</a></li>
                        </ul>
                    </div>
                </div>

                <!-- Chart Canvas Container -->
                <div class="chart-container position-relative" style="min-height: 280px; width: 100%;">
                    <canvas id="userGrowthChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Panel: Aktivitas Terbaru (Right 35%) -->
        <div class="col-12 col-lg-4">
            <div class="card border rounded-4 shadow-2xs h-100 bg-white p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-dark m-0">Aktivitas Terbaru</h5>
                    <a href="#" class="text-decoration-none fw-semibold small" style="color: #002b5e;">Lihat Semua</a>
                </div>

                <!-- Activities List -->
                <div class="d-flex flex-column gap-3 mt-2">
                    @foreach($stats['activities'] as $act)
                        <div class="d-flex gap-3 align-items-start pb-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                            <div class="activity-avatar-circle flex-shrink-0 {{ $act['type'] === 'user' ? 'bg-primary-subtle text-primary' : 'bg-light text-secondary' }}">
                                <i class="bi {{ $act['icon'] }}"></i>
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <p class="text-dark mb-1" style="font-size: 0.84rem; line-height: 1.45;">
                                    {{ $act['title'] }}
                                </p>
                                <span class="text-muted" style="font-size: 0.74rem;">{{ $act['time'] }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

    </div>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const ctx = document.getElementById('userGrowthChart');
    if (!ctx) return;

    const labels = @json($chartData['labels']);
    const values = @json($chartData['values']);

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Pengguna',
                data: values,
                borderColor: '#1e3a5f',
                borderWidth: 2.5,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#1e3a5f',
                pointBorderWidth: 2.5,
                pointRadius: 4.5,
                pointHoverRadius: 6,
                fill: true,
                backgroundColor: (context) => {
                    const chart = context.chart;
                    const {ctx, chartArea} = chart;
                    if (!chartArea) return null;
                    const gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                    gradient.addColorStop(0, 'rgba(30, 58, 95, 0.18)');
                    gradient.addColorStop(1, 'rgba(30, 58, 95, 0.01)');
                    return gradient;
                },
                tension: 0.35
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#002b5e',
                    padding: 10,
                    titleFont: { size: 12, weight: 'bold' },
                    bodyFont: { size: 12 },
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            return context.parsed.y.toLocaleString('id-ID') + ' Pengguna';
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: {
                        color: '#64748b',
                        font: { size: 11 }
                    }
                },
                y: {
                    min: 8000,
                    max: 13000,
                    ticks: {
                        stepSize: 1000,
                        color: '#64748b',
                        font: { size: 11 },
                        callback: function(val) {
                            return (val / 1000) + 'k';
                        }
                    },
                    grid: {
                        color: '#f1f5f9'
                    }
                }
            }
        }
    });
});
</script>
@endpush
