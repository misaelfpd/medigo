<!-- Geolocation & Radius Control Panel (Tab Content) -->
<div class="location-radius-panel">
    <div class="d-flex align-items-center gap-2 mb-3">
        <div class="bg-medigo-subtle text-medigo-primary p-2 rounded-3 d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
            <i class="bi bi-geo-alt-fill fs-6"></i>
        </div>
        <div>
            <h6 class="fw-bold mb-0 text-dark" style="font-size: 0.88rem;">Lokasi Acuan & Jangkauan</h6>
            <span class="text-muted" style="font-size: 0.7rem;">Sesuaikan titik pusat & radius pencarian faskes</span>
        </div>
    </div>

    <!-- Geolocation Trigger Button & Status with Spinners -->
    <div class="d-flex gap-2 mb-2">
        <button type="button" class="btn btn-sm btn-locate-user flex-grow-1 d-flex align-items-center justify-content-center gap-2" id="btnDetectLocation">
            <span class="spinner-border spinner-border-sm d-none" id="gpsBtnSpinner" role="status" aria-hidden="true"></span>
            <i class="bi bi-crosshair" id="gpsBtnIcon"></i>
            <span id="btnDetectText">Deteksi GPS Saya</span>
        </button>
        <span class="badge bg-secondary-subtle text-secondary d-flex align-items-center px-2" id="gpsStatusBadge" style="font-size: 0.68rem;">
            <span class="spinner-grow spinner-grow-sm text-warning me-1 d-none" id="gpsBadgeSpinner"></span>
            <span id="gpsStatusBadgeText">Standby</span>
        </span>
    </div>

    <!-- User Coordinates Info Banner -->
    <div class="user-coords-banner p-2 mb-3 rounded border bg-white d-flex justify-content-between align-items-center" style="font-size: 0.72rem;">
        <div class="text-truncate me-2">
            <i class="bi bi-geo-alt-fill text-danger me-1"></i>
            <span id="userCoordinatesText" class="fw-medium text-dark">Mendeteksi lokasi...</span>
        </div>
        <span class="text-muted text-nowrap" id="userAccuracyText" style="font-size: 0.65rem;">(Bisa digeser)</span>
    </div>

    <!-- Radius Slider Section -->
    <div class="bg-white p-3 rounded border mb-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="small fw-semibold text-dark">
                <i class="bi bi-broadcast text-medigo-primary me-1"></i>Radius Jangkauan
            </span>
            <span class="badge badge-medigo-primary fw-bold px-2 py-1" id="radiusDisplayBadge" style="font-size: 0.75rem;">
                5.0 Km
            </span>
        </div>

        <!-- Range Slider (1 km to 10 km, default 5 km) -->
        <input type="range" class="form-range custom-radius-slider" id="radiusSlider" min="1" max="10" step="0.5" value="5">
        
        <!-- Slider Ticks / Min Max Labels -->
        <div class="d-flex justify-content-between text-muted mt-1" style="font-size: 0.68rem;">
            <span>1 Km</span>
            <span class="text-medigo-primary fw-semibold">5 Km (Default)</span>
            <span>10 Km</span>
        </div>
    </div>

    <!-- Toggle Only Within Radius -->
    <div class="form-check form-switch mb-3 bg-white p-2 ps-4 rounded border">
        <input class="form-check-input ms-0 me-2" type="checkbox" id="filterRadiusSwitch">
        <label class="form-check-label text-dark small fw-medium" for="filterRadiusSwitch">
            Hanya tampilkan faskes dalam radius (<span id="countInsideRadius" class="fw-bold text-medigo-primary">0</span> faskes)
        </label>
    </div>

    <!-- Interactive Map Drag Tip -->
    <div class="alert alert-light border d-flex gap-2 p-2 mb-3" style="font-size: 0.7rem;">
        <i class="bi bi-info-circle-fill text-medigo-primary flex-shrink-0 mt-1"></i>
        <div class="text-muted">
            <strong>Tips:</strong> Anda juga dapat mengklik dan menggeser <strong>marker lingkaran biru</strong> langsung di peta untuk memindahkan titik pusat pencarian.
        </div>
    </div>

    <!-- Switch Back to Facilities Tab Button -->
    <button type="button" class="btn btn-sm btn-medigo-primary rounded-pill w-100 d-flex align-items-center justify-content-center gap-1 py-2 fw-medium" id="btnViewFilteredFaskes">
        <i class="bi bi-hospital"></i>
        <span>Lihat Hasil Daftar Faskes</span>
    </button>
</div>
