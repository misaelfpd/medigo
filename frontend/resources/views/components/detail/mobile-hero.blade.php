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

<div class="detail-mobile-hero faskes-detail-hero-mobile faskes-avatar-{{ $typeSlug }} d-block d-lg-none position-relative p-4 text-center">
    <div class="faskes-hero-logo-circle mb-3 mx-auto">
        <i class="bi {{ $typeIcon }} text-white"></i>
    </div>
    <h4 class="text-white fw-bold mb-2 detail-mobile-hero-title">{{ $faskesDisplayName }}</h4>
    <div class="d-flex align-items-center justify-content-center gap-2 flex-wrap">
        <!-- Rating Badge -->
        <span class="badge-hero-pill badge-hero-rating" id="mobileHeroRatingBadge">
            @if(!empty($faskes['avg_rating']))
                <i class="bi bi-star-fill text-warning me-1"></i> {{ $faskes['avg_rating'] }}
            @else
                <i class="bi bi-star text-white-50 me-1"></i> Belum dirating
            @endif
        </span>
        <!-- BPJS Badge -->
        <span class="badge-hero-pill {{ !empty($faskes['is_support_bpjs']) ? 'badge-hero-bpjs' : 'badge-hero-nonbpjs' }}">
            {{ !empty($faskes['is_support_bpjs']) ? 'BPJS' : 'Non-BPJS' }}
        </span>
        <!-- Distance Badge -->
        <span class="badge-hero-pill badge-hero-distance">
            <i class="bi bi-geo-alt-fill me-1"></i> {{ $faskes['distance'] ?? '-' }}
        </span>
    </div>
</div>