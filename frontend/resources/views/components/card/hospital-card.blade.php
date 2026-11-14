@props([
    'id' => 0,
    'foto' => null,
    'thumbnail' => null,
    'hospital_name' => 'Fasilitas Kesehatan',
    'type' => 'Rumah Sakit',
    'type_slug' => 'hospital',
    'hospital_address' => '',
    'hospital_rating' => null,
    'hospital_class' => null,
    'bpjs_support' => true,
    'distance' => null,
    'is_open' => true,
    'lat' => null,
    'lng' => null,
    'navigasi_url' => '#',
    'detail_url' => null,
])

@php
    $finalDetailUrl = ($detail_url && $detail_url !== '#') ? $detail_url : url('/faskes/' . $id);
    
    // Format Puskesmas name if prefix missing
    $formattedHospitalName = trim($hospital_name);
    if (($type_slug === 'puskesmas' || str_contains(strtolower($type ?? ''), 'puskesmas')) && !preg_match('/^(puskesmas|pkm)\b/i', $formattedHospitalName)) {
        $formattedHospitalName = 'Puskesmas ' . $formattedHospitalName;
    }

    // Resolve clean facility type for badge (never show generic "Fasilitas Kesehatan")
    $resolvedType = trim($type ?? '');
    if (empty($resolvedType) || strcasecmp($resolvedType, 'Fasilitas Kesehatan') === 0 || strcasecmp($resolvedType, 'faskes') === 0) {
        $nameLower = strtolower($formattedHospitalName);
        if ($type_slug === 'puskesmas' || str_contains($nameLower, 'puskesmas') || str_contains($nameLower, 'pkm')) {
            $resolvedType = 'Puskesmas';
        } elseif ($type_slug === 'clinic' || str_contains($nameLower, 'klinik') || str_contains($nameLower, 'clinic')) {
            $resolvedType = 'Klinik';
        } elseif (str_contains($nameLower, 'apotik') || str_contains($nameLower, 'apotek') || str_contains($nameLower, 'farma')) {
            $resolvedType = 'Apotek';
        } elseif (str_contains($nameLower, 'laboratorium') || str_contains($nameLower, 'lab')) {
            $resolvedType = 'Laboratorium';
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

    // Clean address: remove leading comma or orphan comma, ensure road formatting
    $cleanAddress = trim($hospital_address ?? '');
    $cleanAddress = preg_replace('/^(?:jl|jln),\s*/i', 'Jl. ', $cleanAddress);
    $cleanAddress = ltrim($cleanAddress, ",- \t\n\r\0\x0B");
    if (!empty($cleanAddress) && !preg_match('/^(?:jl|jalan|gang|gg|komp|komplek|gedung|kav|kavling|dusun|desa|kel|kec)\b/i', $cleanAddress)) {
        if (preg_match('/(?:raya|barat|timur|selatan|utara|no\b|nomor|km\b|blok)/i', $cleanAddress)) {
            $cleanAddress = 'Jl. ' . $cleanAddress;
        }
    }

    // Ikon & warna fallback sesuai jenis faskes (apotek, lab, optik, dokter, dll.)
    $visualKey = \App\Support\FaskesVisual::key($resolvedType, $formattedHospitalName);
    $typeIcon = \App\Support\FaskesVisual::TYPES[$visualKey][0];
@endphp

<div class="col-12 col-md-4 hospital-card-wrapper" 
     id="home-card-col-{{ $id }}" 
     data-id="{{ $id }}"
     data-name="{{ $formattedHospitalName }}"
     data-type="{{ $type_slug }}"
     data-visual="{{ $visualKey }}"
     data-class="{{ $hospital_class }}"
     data-rating="{{ $hospital_rating }}"
     data-bpjs="{{ $bpjs_support ? '1' : '0' }}"
     data-lat="{{ $lat }}" 
     data-lng="{{ $lng }}">
    <div class="card hospital-card h-100">
        <div class="hospital-card-main">
            <!-- Type-based Logo with Colored Background -->
            <div class="hospital-card-img-wrapper faskes-avatar-banner faskes-avatar-{{ $type_slug }} faskes-visual-{{ $visualKey }} {{ $thumbnail ? 'has-photo' : '' }}">
                <i class="bi {{ $typeIcon }} faskes-avatar-icon text-white"></i>
                @if($thumbnail)
                    <img src="{{ $thumbnail }}" alt="Foto {{ $formattedHospitalName }}" class="hospital-card-photo" loading="lazy"
                         onerror="this.parentElement.classList.remove('has-photo'); this.remove();">
                @endif
            </div>

            <!-- Card Body / Info -->
            <div class="card-body hospital-card-body d-flex flex-column">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <h6 class="card-title fw-bold mb-0 text-dark hospital-card-title">{{ $formattedHospitalName }}</h6>
                    @if($hospital_rating && (float)$hospital_rating > 0)
                        <div class="rating-badge d-flex align-items-center flex-shrink-0 ms-1">
                            <i class="bi bi-star-fill text-warning me-1" style="font-size: 0.72rem;"></i>
                            <span class="fw-bold text-dark" style="font-size: 0.78rem;">{{ $hospital_rating }}</span>
                        </div>
                    @else
                        <div class="rating-badge d-flex align-items-center flex-shrink-0 ms-1 text-muted" style="font-size: 0.72rem;">
                            <i class="bi bi-star me-1 text-muted opacity-50"></i>
                            <span class="small text-muted" style="font-size: 0.7rem;">Belum dirating</span>
                        </div>
                    @endif
                </div>
                <p class="card-text text-muted mb-2 hospital-card-address">{{ $cleanAddress }}</p>

                <div class="d-flex gap-1 mb-2 align-items-center flex-wrap hospital-card-badges">
                    @if($resolvedType)
                        <span class="badge-custom badge-type-{{ $type_slug }}" style="background-color: #f1f5f9; color: #475569;">
                            {{ $resolvedType }}
                        </span>
                    @endif
                    @if($hospital_class)
                        <span class="badge-custom badge-kelas">Kelas {{ $hospital_class }}</span>
                    @endif
                    <span class="badge-custom {{ $bpjs_support ? 'badge-bpjs' : 'badge-non-bpjs' }}">
                        {{ $bpjs_support ? 'BPJS' : 'Non BPJS' }}
                    </span>
                    <span class="distance-info ms-auto" id="home-distance-{{ $id }}">
                        <i class="bi bi-sign-turn-right me-1" style="font-size: 0.8rem;"></i>
                        <span class="distance-value">{{ $distance ? (str_contains((string) $distance, 'km') ? $distance : $distance . ' km') : '-' }}</span>
                    </span>
                </div>
            </div>
        </div>

        <!-- Buttons Row -->
        <div class="hospital-card-actions mt-auto d-flex gap-2">
            <a href="{{ $navigasi_url }}" id="home-nav-{{ $id }}" class="btn btn-navigasi flex-grow-1 text-white d-flex justify-content-center align-items-center gap-1 rounded-pill text-decoration-none" target="_blank">
                <i class="bi bi-sign-turn-right-fill"></i> Navigasi
            </a>
            <a href="{{ $finalDetailUrl }}" class="btn btn-detail flex-grow-1 d-flex justify-content-center align-items-center gap-1 rounded-pill text-decoration-none">
                <i class="bi bi-info-circle"></i> Detail
            </a>
        </div>
    </div>
</div>
