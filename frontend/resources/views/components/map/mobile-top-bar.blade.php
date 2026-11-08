<!-- Mobile Top Floating Search & Quick-Filter Chips Bar -->
<div class="mobile-map-top-bar d-lg-none">
    <!-- Floating Search Input Pill -->
    <div class="mobile-search-pill-wrap shadow-sm">
        <i class="bi bi-search mobile-search-icon"></i>
        <input type="text" id="mobileFacilitySearchInput" class="form-control mobile-search-input" placeholder="Cari rumah sakit, klinik..." autocomplete="off">
        <button type="button" class="btn mobile-search-filter-btn" id="btnMobileFilterToggle" title="Buka Pengaturan Radius">
            <i class="bi bi-sliders"></i>
        </button>
    </div>

    <!-- Floating Horizontal Quick-Filter Chips -->
    <div class="mobile-filter-chips-wrapper mt-2">
        <div class="d-flex gap-2 mobile-chips-scroll" id="mobileFilterChipsList">
            <!-- Radius Slider Trigger Chip -->
            <button type="button" class="btn mobile-chip-pill" id="mobileChipRadius" title="Atur Radius Jarak">
                <i class="bi bi-broadcast text-primary me-1"></i> Radius: <span id="mobileChipRadiusVal">5.0</span> Km
            </button>

            <!-- Toggle Filter 1: Jenis RS -->
            <button type="button" class="btn mobile-chip-pill" data-filter="hospital" id="mobileChipHospital">
                <i class="bi bi-hospital me-1"></i> RS
            </button>

            <!-- Toggle Filter 2: Jenis Klinik -->
            <button type="button" class="btn mobile-chip-pill" data-filter="clinic" id="mobileChipClinic">
                <i class="bi bi-heart-pulse-fill me-1"></i> Klinik
            </button>

            <!-- Toggle Filter 3: Jenis Puskesmas -->
            <button type="button" class="btn mobile-chip-pill" data-filter="puskesmas" id="mobileChipPuskesmas">
                <i class="bi bi-plus-circle-fill me-1"></i> Puskesmas
            </button>

            <!-- Toggle Filter 4: BPJS -->
            <button type="button" class="btn mobile-chip-pill" data-filter="bpjs" id="mobileChipBpjs">
                <i class="bi bi-check-circle me-1"></i> BPJS
            </button>
        </div>
    </div>

    <!-- Mobile Collapsible Radius & GPS Panel -->
    <div class="mobile-radius-panel card border-0 shadow-lg mt-2 p-3 rounded-4 d-none" id="mobileRadiusPanel">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-broadcast text-medigo-primary fs-5"></i>
                <span class="fw-bold text-dark small">Pengaturan Radius GPS</span>
            </div>
            <button type="button" class="btn-close btn-close-sm" id="btnCloseMobileRadiusPanel" aria-label="Close"></button>
        </div>
        
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="text-muted small">Jangkauan Radius</span>
            <span class="badge badge-medigo-primary rounded-pill px-2 py-1" id="mobileRadiusDisplayBadge">5.0 Km</span>
        </div>
        
        <input type="range" class="form-range custom-radius-slider" min="1" max="10" step="0.5" value="5" id="mobileRadiusSlider">
        
        <div class="d-flex justify-content-between text-muted mt-1" style="font-size: 0.7rem;">
            <span>1 Km</span>
            <span>5 Km (Standar)</span>
            <span>10 Km</span>
        </div>
        
        <div class="form-check form-switch mt-3 pt-2 border-top d-flex justify-content-between align-items-center ps-0">
            <label class="form-check-label small fw-semibold text-dark m-0" for="mobileFilterRadiusSwitch">
                Batasi hanya dalam radius
            </label>
            <input class="form-check-input ms-auto" type="checkbox" role="switch" id="mobileFilterRadiusSwitch">
        </div>
    </div>
</div>
