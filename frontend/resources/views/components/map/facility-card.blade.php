@props([
    'faskes',
    'isFirst' => false
])

@php
    // 1. TANGKAP ID DENGAN AMAN (Fallback ke 0 jika null)
    $faskesId = $faskes['id'] ?? $faskes['id_faskes'] ?? 0;

    $typeSlug = $faskes['type_slug'] ?? 'hospital';
    $faskesName = trim($faskes['name'] ?? $faskes['nama_faskes'] ?? 'Fasilitas Kesehatan');
    
    if (($typeSlug === 'puskesmas' || str_contains(strtolower($faskes['type'] ?? $faskes['jenis_faskes'] ?? ''), 'puskesmas')) && !preg_match('/^(puskesmas|pkm)\b/i', $faskesName)) {
        $faskesName = 'Puskesmas ' . $faskesName;
    }

    $rawAddress = trim($faskes['address'] ?? $faskes['alamat_faskes'] ?? $faskes['alamat'] ?? '');
    $rawAddress = preg_replace('/^(?:jl|jln),\s*/i', 'Jl. ', $rawAddress);
    $cleanAddress = ltrim($rawAddress, ",- \t\n\r\0\x0B");

    $resolvedType = trim($faskes['type'] ?? $faskes['jenis_faskes'] ?? '');
    if (empty($resolvedType) || strcasecmp($resolvedType, 'Fasilitas Kesehatan') === 0 || strcasecmp($resolvedType, 'faskes') === 0) {
        $nameLower = strtolower($faskesName);
        if ($typeSlug === 'puskesmas' || str_contains($nameLower, 'puskesmas') || str_contains($nameLower, 'pkm')) {
            $resolvedType = 'Puskesmas';
        } elseif ($typeSlug === 'clinic' || str_contains($nameLower, 'klinik') || str_contains($nameLower, 'clinic')) {
            $resolvedType = 'Klinik';
        } elseif (str_contains($nameLower, 'apotik') || str_contains($nameLower, 'apotek') || str_contains($nameLower, 'farma')) {
            $resolvedType = 'Apotek';
        } else {
            $resolvedType = 'Rumah Sakit';
        }
    } else {
        $low = strtolower($resolvedType);
        if (str_contains($low, 'rumah sakit') || $low === 'rs') {
            $resolvedType = 'Rumah Sakit';
        } elseif (str_contains($low, 'puskesmas')) {
            $resolvedType = 'Puskesmas';
        } elseif (str_contains($low, 'klinik')) {
            $resolvedType = 'Klinik';
        } elseif (str_contains($low, 'apotik') || str_contains($low, 'apotek')) {
            $resolvedType = 'Apotek';
        } else {
            $resolvedType = ucwords($low);
        }
    }

    // Ikon & warna fallback sesuai jenis faskes (apotek, lab, optik, dokter, dll.)
    $visualKey = \App\Support\FaskesVisual::key($resolvedType, $faskesName);
    $typeIcon = \App\Support\FaskesVisual::TYPES[$visualKey][0];
    
    // 2. GUNAKAN VARIABEL $faskesId YANG SUDAH AMAN
    $detailUrl = (!empty($faskes['detail_url']) && $faskes['detail_url'] !== '#') 
        ? $faskes['detail_url'] 
        : url('/faskes/' . $faskesId);
@endphp

<!-- 3. TERAPKAN $faskesId KE SELURUH ATRIBUT HTML & JAGA LAT/LNG -->
<div class="facility-item-card card {{ $isFirst ? 'is-selected' : '' }}" 
     id="card-faskes-{{ $faskesId }}"
     data-id="{{ $faskesId }}"
     data-name="{{ $faskesName }}"
     data-address="{{ $cleanAddress }}"
     data-lat="{{ $faskes['lat'] ?? '' }}"
     data-lng="{{ $faskes['lng'] ?? '' }}">
    <div class="d-flex gap-3 align-items-start">
        <!-- Logo Avatar with Type Colored Background -->
        <div class="facility-thumb-wrap flex-shrink-0 faskes-avatar-map faskes-avatar-{{ $typeSlug }} faskes-visual-{{ $visualKey }} {{ !empty($faskes['thumbnail_url']) ? 'has-photo' : '' }}">
            <i class="bi {{ $typeIcon }} text-white fs-4"></i>
            @if(!empty($faskes['thumbnail_url']))
                <img src="{{ $faskes['thumbnail_url'] }}" alt="Foto {{ $faskesName }}" class="facility-thumb-photo" loading="lazy"
                     onerror="this.parentElement.classList.remove('has-photo'); this.remove();">
            @endif
        </div>

        <!-- Info Content -->
        <div class="facility-info-wrap flex-grow-1 min-w-0">
            <h6 class="facility-name fw-bold mb-1 text-dark text-truncate" title="{{ $faskesName }}">
                {{ $faskesName }}
            </h6>
            <p class="facility-address text-muted mb-2 text-truncate" title="{{ $cleanAddress }}">
                {{ $cleanAddress }}
            </p>

            <!-- Badges & Distance Row -->
            <div class="d-flex align-items-center gap-1 mb-2 flex-wrap">
                @if($resolvedType)
                    <span class="badge-custom badge-type-{{ $typeSlug }}" style="background-color: #f1f5f9; color: #475569; font-size: 0.72rem; padding: 2px 8px; border-radius: 9999px;">
                        {{ $resolvedType }}
                    </span>
                @endif
                @if(!empty($faskes['hospital_class']))
                    <span class="badge-custom badge-kelas">Kelas {{ $faskes['hospital_class'] }}</span>
                @endif
                <span class="badge-custom {{ !empty($faskes['bpjs_support']) ? 'badge-bpjs' : 'badge-non-bpjs' }}">
                    {{ !empty($faskes['bpjs_support']) ? 'BPJS' : 'Non BPJS' }}
                </span>
                
                <!-- ID DI DISTANCE -->
                <span class="facility-distance ms-auto text-dark fw-bold" id="dist-faskes-{{ $faskesId }}">
                    <i class="bi bi-signpost-split me-1"></i>{{ !empty($faskes['distance']) ? $faskes['distance'] : '-' }}
                </span>
            </div>

            <!-- Action Buttons -->
            <div class="d-flex gap-2">
                <a href="{{ $faskes['navigasi_url'] ?? '#' }}" target="_blank" class="btn btn-sm btn-navigasi-pill flex-grow-1 text-white text-decoration-none d-flex align-items-center justify-content-center gap-1">
                    <i class="bi bi-sign-turn-right"></i> Navigasi
                </a>
                <a href="{{ $detailUrl }}" class="btn btn-sm btn-detail-pill flex-grow-1 text-decoration-none d-flex align-items-center justify-content-center gap-1">
                    <i class="bi bi-info-circle"></i> Detail
                </a>
            </div>
        </div>
    </div>
</div>