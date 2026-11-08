@props([
    'id' => 1,
    'foto' => null,
    'hospital_name' => 'Metro General Hospital',
    'type' => 'Rumah Sakit',
    'type_slug' => 'hospital',
    'hospital_address' => 'Jl. Metro Pondok Indah, Bogor',
    'hospital_rating' => null,
    'hospital_class' => 'A',
    'bpjs_support' => true,
    'distance' => '3.4 km',
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

    // Icon selection based on type_slug
    $typeIcon = 'bi-hospital';
    if ($type_slug === 'clinic') {
        $typeIcon = 'bi-heart-pulse-fill';
    } elseif ($type_slug === 'puskesmas') {
        $typeIcon = 'bi-plus-circle-fill';
    }
@endphp

<div class="col-12 col-md-4 hospital-card-wrapper" 
     id="home-card-col-{{ $id }}" 
     data-id="{{ $id }}"
     data-name="{{ $formattedHospitalName }}"
     data-type="{{ $type_slug }}"
     data-class="{{ $hospital_class }}"
     data-rating="{{ $hospital_rating }}"
     data-bpjs="{{ $bpjs_support ? '1' : '0' }}"
     data-lat="{{ $lat }}" 
     data-lng="{{ $lng }}">
    <div class="card hospital-card h-100">
        <div class="hospital-card-main">
            <!-- Type-based Logo with Colored Background -->
            <div class="hospital-card-img-wrapper faskes-avatar-banner faskes-avatar-{{ $type_slug }}">
                <i class="bi {{ $typeIcon }} faskes-avatar-icon text-white"></i>
                @if($is_open)
                    <span class="badge-status-open d-md-none">
                        <span class="status-dot"></span> Buka
                    </span>
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
                <p class="card-text text-muted mb-2 hospital-card-address">{{ $hospital_address }}</p>

                <div class="d-flex gap-1 mb-2 align-items-center flex-wrap hospital-card-badges">
                    @if($type)
                        <span class="badge-custom badge-type-{{ $type_slug }}" style="background-color: #f1f5f9; color: #475569;">
                            {{ $type }}
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
                        <span class="distance-value">{{ str_contains($distance, 'km') ? $distance : $distance . ' km' }}</span>
                    </span>
                </div>
            </div>
        </div>

        <!-- Buttons Row -->
        <div class="hospital-card-actions mt-auto d-flex gap-2">
            <a href="{{ $navigasi_url }}" id="home-nav-{{ $id }}" class="btn btn-navigasi flex-grow-1 text-white d-flex justify-content-center align-items-center gap-1 rounded-pill text-decoration-none" target="_blank">
                <i class="bi bi-sign-turn-right"></i> Navigasi
            </a>
            <a href="{{ $finalDetailUrl }}" class="btn btn-detail flex-grow-1 d-flex justify-content-center align-items-center gap-1 rounded-pill text-decoration-none">
                <i class="bi bi-info-circle"></i> Detail
            </a>
        </div>
    </div>
</div>
