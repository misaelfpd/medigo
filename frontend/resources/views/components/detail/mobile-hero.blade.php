@props(['faskes' => []])

@php
    $typeSlug = $faskes['type_slug'] ?? 'hospital';
    $faskesDisplayName = trim($faskes['nama_faskes'] ?? 'Fasilitas Kesehatan');
    if (($typeSlug === 'puskesmas' || str_contains(strtolower($faskes['tipe'] ?? ''), 'puskesmas')) && !preg_match('/^(puskesmas|pkm)\b/i', $faskesDisplayName)) {
        $faskesDisplayName = 'Puskesmas ' . $faskesDisplayName;
    }

    // Ikon & warna fallback sesuai jenis faskes (apotek, lab, optik, dokter, dll.)
    $visualKey = \App\Support\FaskesVisual::key(($faskes['tipe'] ?? ''), $faskesDisplayName);
    $typeIcon = \App\Support\FaskesVisual::TYPES[$visualKey][0];
@endphp

@php
    $mobileBannerUrl = ($faskes['banner_url'] ?? null) ?: ($faskes['thumbnail_url'] ?? null);
@endphp
<div class="detail-mobile-hero faskes-detail-hero-mobile faskes-avatar-{{ $typeSlug }} faskes-visual-{{ $visualKey }} d-block d-lg-none position-relative p-4 text-center {{ $mobileBannerUrl ? 'mg-mobile-hero--photo' : '' }}"
     @if($mobileBannerUrl) style="background-image: linear-gradient(180deg, rgba(0,52,97,.35), rgba(0,52,97,.85)), url('{{ $mobileBannerUrl }}');" @endif>
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
        <span class="badge-hero-pill badge-hero-distance" id="mobileHeroDistanceBadge">
            <i class="bi bi-geo-alt-fill me-1"></i> <span class="faskes-distance-display">{{ $faskes['distance'] ?? 'Menghitung jarak...' }}</span>
        </span>
    </div>
</div>