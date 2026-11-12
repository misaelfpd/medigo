@props(['faskes' => [], 'faskesDisplayName' => null])

@php
    $faskesDisplayName = $faskesDisplayName ?? ($faskes['nama_faskes'] ?? 'Fasilitas Kesehatan');
@endphp

<div class="detail-header-info mb-3 mb-lg-4">
    <h2 class="fw-bold text-dark mb-2" style="font-size: clamp(1.35rem, 2.2vw, 1.75rem); letter-spacing: -0.01em;">
        {{ $faskesDisplayName }}
    </h2>

    <div class="d-flex align-items-center gap-2 flex-wrap">
        {{-- Badge Kelas / Tipe Faskes --}}
        @if(!empty($faskes['kelas']))
            <span class="badge-faskes-kelas">Kelas {{ $faskes['kelas'] }}</span>
        @else
            <span class="badge-faskes-kelas">
                {{ (!empty($faskes['tipe']) && strcasecmp($faskes['tipe'], 'Fasilitas Kesehatan') !== 0) ? $faskes['tipe'] : 'Rumah Sakit' }}
            </span>
        @endif

        {{-- Badge BPJS --}}
        <span class="{{ !empty($faskes['is_support_bpjs']) ? 'badge-faskes-bpjs' : 'badge-faskes-nonbpjs' }}">
            {{ !empty($faskes['is_support_bpjs']) ? 'BPJS' : 'Non-BPJS' }}
        </span>

        {{-- Jarak --}}
        @if(!empty($faskes['distance']))
            <span class="text-secondary d-inline-flex align-items-center gap-1 ms-1" style="font-size: 0.84rem; font-weight: 500;">
                <i class="bi bi-signpost-2 text-muted"></i>
                <span>{{ $faskes['distance'] }}</span>
            </span>
        @endif

        {{-- Rating Indicator (Sinkron dengan live AJAX) --}}
        <span class="text-secondary d-inline-flex align-items-center gap-1 ms-2" style="font-size: 0.84rem;" id="desktopHeroRatingBadge">
            @if(!empty($faskes['avg_rating']))
                <i class="bi bi-star-fill text-warning"></i>
                <strong class="text-dark">{{ $faskes['avg_rating'] }}</strong>
                <span class="text-muted">(<span class="total-reviews-num">{{ $faskes['total_reviews'] }}</span> ulasan)</span>
            @else
                <i class="bi bi-star text-muted"></i>
                <span class="text-muted">Belum dirating</span>
            @endif
        </span>
    </div>
</div>