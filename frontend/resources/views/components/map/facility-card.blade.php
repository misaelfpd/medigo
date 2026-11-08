@props([
    'faskes',
    'isFirst' => false
])

@php
    $typeSlug = $faskes['type_slug'] ?? 'hospital';
    $faskesName = trim($faskes['name'] ?? 'Fasilitas Kesehatan');
    if (($typeSlug === 'puskesmas' || str_contains(strtolower($faskes['type'] ?? ''), 'puskesmas')) && !preg_match('/^(puskesmas|pkm)\b/i', $faskesName)) {
        $faskesName = 'Puskesmas ' . $faskesName;
    }

    $typeIcon = 'bi-hospital';
    if ($typeSlug === 'clinic') {
        $typeIcon = 'bi-heart-pulse-fill';
    } elseif ($typeSlug === 'puskesmas') {
        $typeIcon = 'bi-plus-circle-fill';
    }
    $detailUrl = (!empty($faskes['detail_url']) && $faskes['detail_url'] !== '#') ? $faskes['detail_url'] : url('/faskes/' . $faskes['id']);
@endphp

<div class="facility-item-card card {{ $isFirst ? 'is-selected' : '' }}" 
     id="card-faskes-{{ $faskes['id'] }}"
     data-id="{{ $faskes['id'] }}"
     data-name="{{ $faskesName }}"
     data-address="{{ $faskes['address'] }}"
     data-lat="{{ $faskes['lat'] }}"
     data-lng="{{ $faskes['lng'] }}">
    <div class="d-flex gap-3 align-items-start">
        <!-- Logo Avatar with Type Colored Background -->
        <div class="facility-thumb-wrap flex-shrink-0 faskes-avatar-map faskes-avatar-{{ $typeSlug }}">
            <i class="bi {{ $typeIcon }} text-white fs-4"></i>
        </div>

        <!-- Info Content -->
        <div class="facility-info-wrap flex-grow-1 min-w-0">
            <h6 class="facility-name fw-bold mb-1 text-dark text-truncate" title="{{ $faskesName }}">
                {{ $faskesName }}
            </h6>
            <p class="facility-address text-muted mb-2 text-truncate" title="{{ $faskes['address'] }}">
                {{ $faskes['address'] }}
            </p>

            <!-- Badges & Distance Row -->
            <div class="d-flex align-items-center gap-1 mb-2 flex-wrap">
                @if($faskes['hospital_class'])
                    <span class="badge-custom badge-kelas">Kelas {{ $faskes['hospital_class'] }}</span>
                @endif
                <span class="badge-custom {{ $faskes['bpjs_support'] ? 'badge-bpjs' : 'badge-non-bpjs' }}">
                    {{ $faskes['bpjs_support'] ? 'BPJS' : 'Non - BPJS' }}
                </span>
                <span class="facility-distance ms-auto text-dark fw-bold" id="dist-faskes-{{ $faskes['id'] }}">
                    <i class="bi bi-arrow-down-up me-1"></i>{{ !empty($faskes['distance']) ? $faskes['distance'] : '-' }}
                </span>
            </div>

            <!-- Action Buttons -->
            <div class="d-flex gap-2">
                <a href="{{ $faskes['navigasi_url'] }}" target="_blank" class="btn btn-sm btn-navigasi-pill flex-grow-1 text-white text-decoration-none d-flex align-items-center justify-content-center gap-1">
                    <i class="bi bi-sign-turn-right"></i> Navigasi
                </a>
                <a href="{{ $detailUrl }}" class="btn btn-sm btn-detail-pill flex-grow-1 text-decoration-none d-flex align-items-center justify-content-center gap-1">
                    <i class="bi bi-info-circle"></i> Detail
                </a>
            </div>
        </div>
    </div>
</div>
