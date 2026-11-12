@props(['faskes' => []])

@php
    $typeSlug = $faskes['type_slug'] ?? 'hospital';
    $faskesDisplayName = trim($faskes['nama_faskes'] ?? 'Fasilitas Kesehatan');
    if (($typeSlug === 'puskesmas' || str_contains(strtolower($faskes['tipe'] ?? ''), 'puskesmas')) && !preg_match('/^(puskesmas|pkm)\b/i', $faskesDisplayName)) {
        $faskesDisplayName = 'Puskesmas ' . $faskesDisplayName;
    }

    $typeIcon = 'bi-hospital';
    if ($typeSlug === 'clinic') {
        $typeIcon = 'bi-heart-pulse-fill';
    } elseif ($typeSlug === 'puskesmas') {
        $typeIcon = 'bi-plus-circle-fill';
    }
@endphp

<div class="detail-desktop-hero-banner faskes-avatar-{{ $typeSlug }} d-none d-lg-flex align-items-center justify-content-between p-4 p-xl-5 mb-4 rounded-4 shadow-sm text-white position-relative overflow-hidden">
    <div class="d-flex align-items-center gap-4 position-relative" style="z-index: 2;">
        <!-- Logo Box -->
        <div class="faskes-desktop-logo-box">
            <i class="bi {{ $typeIcon }} text-white"></i>
        </div>
        <!-- Title & Badges -->
        <div>
            <span class="badge rounded-pill bg-white text-dark px-3 py-1 fw-bold mb-2 shadow-xs" style="font-size: 0.76rem;">
                {{ (!empty($faskes['tipe']) && strcasecmp($faskes['tipe'], 'Fasilitas Kesehatan') !== 0) ? $faskes['tipe'] : 'Rumah Sakit' }}
            </span>
            <h2 class="fw-bold text-white mb-2">{{ $faskesDisplayName }}</h2>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                @if(!empty($faskes['kelas']))
                    <span class="badge-custom-pill bg-white text-dark shadow-xs">Kelas {{ $faskes['kelas'] }}</span>
                @endif
                <span class="badge-custom-pill bg-white {{ !empty($faskes['is_support_bpjs']) ? 'text-success' : 'text-danger' }} shadow-xs">
                    {{ !empty($faskes['is_support_bpjs']) ? 'BPJS' : 'Non-BPJS' }}
                </span>
                <span class="badge-custom-pill bg-white text-dark shadow-xs" id="desktopHeroDistanceBadge">
                    <i class="bi bi-arrow-down-up me-1"></i> <span class="faskes-distance-display">{{ $faskes['distance'] ?? 'Menghitung jarak...' }}</span>
                </span>
                <span class="badge-custom-pill bg-white text-dark shadow-xs" id="desktopHeroRatingBadge">
                    @if(!empty($faskes['avg_rating']))
                        <i class="bi bi-star-fill text-warning me-1"></i> <strong>{{ $faskes['avg_rating'] }}</strong> (<span class="total-reviews-num">{{ $faskes['total_reviews'] }}</span> Ulasan)
                    @else
                        <i class="bi bi-star text-muted me-1"></i> <span>Belum dirating</span>
                    @endif
                </span>
            </div>
        </div>
    </div>

    <!-- Background Decorative Watermark Icon -->
    <div class="hero-decorative-bg-icon position-absolute end-0 top-50 translate-middle-y pe-4" style="pointer-events: none; opacity: 0.12;">
        <i class="bi {{ $typeIcon }}" style="font-size: 14rem; color: #ffffff;"></i>
    </div>
</div>