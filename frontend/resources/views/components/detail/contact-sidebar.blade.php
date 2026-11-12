@props(['faskes' => [], 'faskesDisplayName' => null])

@php
    $faskesDisplayName = $faskesDisplayName ?? ($faskes['nama_faskes'] ?? 'Fasilitas Kesehatan');
@endphp

<div class="card contact-sidebar-card overflow-hidden bg-white">
    {{-- Mini Map Leaflet --}}
    <div class="detail-mini-map-wrapper position-relative border-bottom">
        <div id="detailMiniMap" class="detail-mini-map"></div>
        <span class="map-caption-badge text-truncate" style="max-width: 85%;">
            {{ $faskesDisplayName }}
        </span>
    </div>

    {{-- Informasi Kontak --}}
    <div class="card-body p-3 p-lg-4">
        <div class="d-flex align-items-center gap-2 mb-3">
            <i class="bi bi-question-circle text-dark fs-5"></i>
            <h6 class="fw-bold text-dark m-0" style="font-size: 1rem;">Informasi Kontak</h6>
        </div>

        {{-- Alamat --}}
        <div class="d-flex gap-3 align-items-start mb-3">
            <i class="bi bi-geo-alt text-dark fs-5 mt-1 flex-shrink-0"></i>
            <div>
                <div class="fw-bold text-dark" style="font-size: 0.85rem;">Alamat</div>
                <div class="text-secondary" style="font-size: 0.81rem; line-height: 1.55;">
                    {{ $faskes['alamat'] ?? '-' }}
                </div>
            </div>
        </div>

        {{-- Telepon --}}
        <div class="d-flex gap-3 align-items-start mb-4">
            <i class="bi bi-telephone text-dark fs-5 mt-1 flex-shrink-0"></i>
            <div>
                <div class="fw-bold text-dark" style="font-size: 0.85rem;">Telepon</div>
                <div class="text-secondary" style="font-size: 0.82rem;">
                    {{ $faskes['phone'] ?? '-' }}
                </div>
            </div>
        </div>

        {{-- Tombol Navigasi Utama --}}
        <a id="btnNavigasiDetail"
           href="{{ $faskes['navigasi_url'] ?? '#' }}"
           target="_blank"
           class="btn btn-medigo-primary w-100 rounded-pill py-2 fw-semibold d-flex align-items-center justify-content-center gap-2 text-decoration-none">
            <i class="bi bi-sign-turn-right"></i>
            <span>Navigasi</span>
        </a>

        {{-- Tombol Koreksi Data --}}
        <button type="button"
                class="btn btn-outline-secondary w-100 rounded-pill py-2 fw-semibold d-flex align-items-center justify-content-center gap-2 mt-2"
                data-bs-toggle="modal"
                data-bs-target="#modalFeedbackFaskes"
                style="font-size: 0.82rem;">
            <i class="bi bi-patch-question"></i>
            <span>Koreksi / Masukan Data</span>
        </button>
    </div>
</div>