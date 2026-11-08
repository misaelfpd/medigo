<!-- Search Box with Icon & Filter -->
<div class="facility-search-input-wrap position-relative mb-2">
    <i class="bi bi-search facility-search-icon"></i>
    <input type="text" id="facilitySearchInput" class="form-control facility-search-input" placeholder="Cari rumah sakit, klinik..." autocomplete="off">
    <button type="button" class="btn facility-filter-btn" id="facilityFilterBtn" title="Buka Pengaturan Radius">
        <i class="bi bi-sliders"></i>
    </button>
</div>

<!-- Desktop Quick-Filter Chips Bar -->
<div class="desktop-filter-chips-wrapper mb-3">
    <div class="d-flex gap-1 flex-wrap" id="desktopFilterChipsList">
        <!-- Toggle Filter 1: Jenis RS -->
        <button type="button" class="btn mobile-chip-pill py-1 px-2" data-filter="hospital" style="font-size: 0.72rem;">
            <i class="bi bi-hospital me-1"></i> RS
        </button>

        <!-- Toggle Filter 2: Jenis Klinik -->
        <button type="button" class="btn mobile-chip-pill py-1 px-2" data-filter="clinic" style="font-size: 0.72rem;">
            <i class="bi bi-heart-pulse-fill me-1"></i> Klinik
        </button>

        <!-- Toggle Filter 3: Jenis Puskesmas -->
        <button type="button" class="btn mobile-chip-pill py-1 px-2" data-filter="puskesmas" style="font-size: 0.72rem;">
            <i class="bi bi-plus-circle-fill me-1"></i> Puskesmas
        </button>

        <!-- Toggle Filter 4: BPJS -->
        <button type="button" class="btn mobile-chip-pill py-1 px-2" data-filter="bpjs" style="font-size: 0.72rem;">
            <i class="bi bi-check-circle me-1"></i> BPJS
        </button>
    </div>
</div>
