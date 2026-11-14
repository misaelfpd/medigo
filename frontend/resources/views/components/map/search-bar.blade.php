<!-- Search Box with Icon & Filter -->
<div class="facility-search-input-wrap position-relative mb-2">
    <i class="bi bi-search facility-search-icon"></i>
    <input type="text" id="facilitySearchInput" class="form-control facility-search-input" placeholder="Cari rumah sakit, klinik..." autocomplete="off">
    <button type="button" class="btn facility-filter-btn" id="facilityFilterBtn" title="Buka Pengaturan Radius">
        <i class="bi bi-sliders"></i>
    </button>
</div>

<!-- Desktop Quick-Filter Chips Bar (Sesuai jenis faskes di Beranda) -->
<div class="desktop-filter-chips-wrapper mb-3">
    <div class="d-flex gap-1 flex-wrap" id="desktopFilterChipsList">
        <!-- Semua (Default) -->
        <button type="button" class="btn mobile-chip-pill active py-1 px-2" data-filter="all" style="font-size: 0.72rem;">
            Semua
        </button>

        <!-- Rumah Sakit -->
        <button type="button" class="btn mobile-chip-pill py-1 px-2" data-filter="rumah-sakit" style="font-size: 0.72rem;">
            <i class="bi bi-hospital-fill me-1"></i> RS
        </button>

        <!-- Puskesmas -->
        <button type="button" class="btn mobile-chip-pill py-1 px-2" data-filter="puskesmas" style="font-size: 0.72rem;">
            <i class="bi bi-house-heart-fill me-1"></i> Puskesmas
        </button>

        <!-- Klinik -->
        <button type="button" class="btn mobile-chip-pill py-1 px-2" data-filter="klinik" style="font-size: 0.72rem;">
            <i class="bi bi-heart-pulse-fill me-1"></i> Klinik
        </button>

        <!-- Apotek -->
        <button type="button" class="btn mobile-chip-pill py-1 px-2" data-filter="apotek" style="font-size: 0.72rem;">
            <i class="bi bi-capsule-pill me-1"></i> Apotek
        </button>

        <!-- Laboratorium -->
        <button type="button" class="btn mobile-chip-pill py-1 px-2" data-filter="laboratorium" style="font-size: 0.72rem;">
            <i class="bi bi-eyedropper me-1"></i> Lab
        </button>

        <!-- Optik -->
        <button type="button" class="btn mobile-chip-pill py-1 px-2" data-filter="optik" style="font-size: 0.72rem;">
            <i class="bi bi-eyeglasses me-1"></i> Optik
        </button>

        <!-- Dokter Gigi -->
        <button type="button" class="btn mobile-chip-pill py-1 px-2" data-filter="dokter-gigi" style="font-size: 0.72rem;">
            <i class="bi bi-tooth me-1"></i> Gigi
        </button>

        <!-- Dokter Umum -->
        <button type="button" class="btn mobile-chip-pill py-1 px-2" data-filter="dokter-umum" style="font-size: 0.72rem;">
            <i class="bi bi-stethoscope me-1"></i> Dokter
        </button>

        <!-- BPJS -->
        <button type="button" class="btn mobile-chip-pill py-1 px-2" data-filter="bpjs" style="font-size: 0.72rem;">
            <i class="bi bi-check-circle me-1"></i> BPJS
        </button>
    </div>
</div>
