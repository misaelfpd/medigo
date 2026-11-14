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

    // Banner sesuai Figma: foto banner -> thumbnail -> placeholder
    $bannerUrl = ($faskes['banner_url'] ?? null) ?: ($faskes['thumbnail_url'] ?? null);
    $tipeLabel = (!empty($faskes['tipe']) && strcasecmp($faskes['tipe'], 'Fasilitas Kesehatan') !== 0) ? $faskes['tipe'] : 'Rumah Sakit';
@endphp

<div class="mg-detail-hero d-none d-lg-block mb-4">
    {{-- Banner panjang (1 foto) --}}
    <div class="mg-detail-banner faskes-avatar-{{ $typeSlug }} faskes-visual-{{ $visualKey }}">
        @if($bannerUrl)
            <img src="{{ $bannerUrl }}" alt="Foto {{ $faskesDisplayName }}" loading="lazy"
                 onerror="this.remove(); this.parentElement.classList.add('mg-detail-banner--empty');">
        @endif
        <div class="mg-detail-banner-placeholder">
            <i class="bi {{ $typeIcon }}"></i>
            <span>Belum ada foto {{ strtolower($tipeLabel) }}</span>
        </div>
    </div>

    {{-- Nama & label --}}
    <div class="mg-detail-titlebar">
        <h1 class="mg-detail-title">{{ $faskesDisplayName }}</h1>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="mg-chip mg-chip--type">{{ $tipeLabel }}</span>
            @if(!empty($faskes['kelas']))
                <span class="mg-chip mg-chip--kelas">Kelas {{ $faskes['kelas'] }}</span>
            @endif
            <span class="mg-chip {{ !empty($faskes['is_support_bpjs']) ? 'mg-chip--bpjs' : 'mg-chip--nonbpjs' }}">
                {{ !empty($faskes['is_support_bpjs']) ? 'BPJS' : 'Non BPJS' }}
            </span>
            <span class="mg-chip-plain" id="desktopHeroDistanceBadge">
                <i class="bi bi-signpost-split"></i>
                <span class="faskes-distance-display">{{ $faskes['distance'] ?? 'Menghitung jarak...' }}</span>
            </span>
            <span class="mg-chip-plain" id="desktopHeroRatingBadge">
                @if(!empty($faskes['avg_rating']))
                    <i class="bi bi-star-fill text-warning"></i>
                    <strong>{{ $faskes['avg_rating'] }}</strong>
                    <span class="text-secondary">(<span class="total-reviews-num">{{ $faskes['total_reviews'] }}</span> ulasan)</span>
                @else
                    <i class="bi bi-star text-muted"></i> <span>Belum dirating</span>
                @endif
            </span>
        </div>
    </div>
</div>
