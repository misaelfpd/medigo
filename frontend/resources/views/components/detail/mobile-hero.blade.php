@props(['faskes' => []])

@php
    $typeSlug = $faskes['type_slug'] ?? 'hospital';
    $faskesDisplayName = trim($faskes['nama_faskes'] ?? 'Fasilitas Kesehatan');
    if (($typeSlug === 'puskesmas' || str_contains(strtolower($faskes['tipe'] ?? ''), 'puskesmas')) && !preg_match('/^(puskesmas|pkm)\b/i', $faskesDisplayName)) {
        $faskesDisplayName = 'Puskesmas ' . $faskesDisplayName;
    }

    $visualKey = \App\Support\FaskesVisual::key(($faskes['tipe'] ?? ''), $faskesDisplayName);
    $typeIcon = \App\Support\FaskesVisual::TYPES[$visualKey][0];
    $mobileBannerUrl = ($faskes['banner_url'] ?? null) ?: ($faskes['thumbnail_url'] ?? null);
    $tipeLabel = (!empty($faskes['tipe']) && strcasecmp($faskes['tipe'], 'Fasilitas Kesehatan') !== 0) ? $faskes['tipe'] : 'Rumah Sakit';
@endphp

<div class="detail-mobile-hero d-block d-lg-none position-relative overflow-hidden mb-3">
    {{-- Banner Foto Mobile --}}
    <div class="mg-detail-banner mg-detail-banner--mobile faskes-avatar-{{ $typeSlug }} faskes-visual-{{ $visualKey }} position-relative">
        @if($mobileBannerUrl)
            <img src="{{ $mobileBannerUrl }}" alt="Foto {{ $faskesDisplayName }}" loading="lazy" class="w-100 h-100" style="object-fit: cover;"
                 onerror="this.remove(); this.parentElement.classList.add('mg-detail-banner--empty');">
            <div class="mg-mobile-hero-overlay"></div>
        @endif
        <div class="mg-detail-banner-placeholder">
            <i class="bi {{ $typeIcon }}"></i>
            <span>Belum ada foto {{ strtolower($tipeLabel) }}</span>
        </div>
    </div>

    {{-- Content Card / Title & Badges --}}
    <div class="p-3 text-center bg-white border-bottom">
        <h4 class="text-dark fw-bold mb-2" style="font-size: 1.25rem;">{{ $faskesDisplayName }}</h4>
        <div class="d-flex align-items-center justify-content-center gap-2 flex-wrap">
            <span class="mg-chip mg-chip--type">{{ $tipeLabel }}</span>
            @if(!empty($faskes['kelas']))
                <span class="mg-chip mg-chip--kelas">Kelas {{ $faskes['kelas'] }}</span>
            @endif
            <span class="mg-chip {{ !empty($faskes['is_support_bpjs']) ? 'mg-chip--bpjs' : 'mg-chip--nonbpjs' }}">
                {{ !empty($faskes['is_support_bpjs']) ? 'BPJS' : 'Non BPJS' }}
            </span>
            <span class="mg-chip-plain" id="mobileHeroDistanceBadge">
                <i class="bi bi-geo-alt-fill text-danger me-1"></i>
                <span class="faskes-distance-display">{{ $faskes['distance'] ?? 'Menghitung jarak...' }}</span>
            </span>
            @if(!empty($faskes['avg_rating']))
                <span class="mg-chip-plain" id="mobileHeroRatingBadge">
                    <i class="bi bi-star-fill text-warning me-1"></i>
                    <strong>{{ $faskes['avg_rating'] }}</strong>
                </span>
            @endif
        </div>
    </div>
</div>
