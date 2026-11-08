@extends('layouts.app')

@section('title', 'MediGO - Beranda')

@section('content')
    <!-- Rekomendasi / Hospital Section -->
    <section class="hospital-section py-3 py-lg-4">
        <div class="container">
            <!-- Compact Modern Header with Integrated Inline Location Indicator -->
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <h4 class="fw-bold mb-1 section-title text-dark" id="homeSectionHeading">Rumah Sakit Terdekat</h4>
                    <!-- Inline Location Pill & GPS Trigger -->
                    <div class="d-flex align-items-center gap-2 flex-wrap" style="font-size: 0.78rem;">
                        <span class="d-inline-flex align-items-center text-dark bg-white px-2 py-1 rounded-pill border shadow-xs" id="homeLocationIndicator">
                            <i class="bi bi-geo-alt-fill text-danger me-1" id="homeLocationIcon"></i>
                            <span id="homeCurrentLocationText" class="fw-medium text-truncate" style="max-width: 260px;">Mendeteksi lokasi GPS...</span>
                            <span class="badge bg-success-subtle text-success ms-1 d-none" id="homeGpsLockedBadge" style="font-size: 0.6rem;">GPS Terkunci</span>
                            <span class="badge bg-warning-subtle text-warning ms-1 d-none" id="homeRatingSortBadge" style="font-size: 0.6rem;">Urut Rating</span>
                        </span>
                        <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none text-muted fw-semibold d-inline-flex align-items-center gap-1" id="btnHomeDetectLocation" title="Perbarui lokasi GPS">
                            <span class="spinner-border spinner-border-sm d-none" id="btnHomeSpinner" role="status" style="width: 0.75rem; height: 0.75rem;"></span>
                            <i class="bi bi-arrow-clockwise" id="btnHomeIcon"></i>
                            <span id="btnHomeDetectText">Perbarui</span>
                        </button>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2 ms-auto">
                    <span class="badge badge-medigo-primary rounded-pill px-3 py-2 d-none" style="font-size: 0.74rem;" id="homeTotalCountBadge">
                        {{ count($hospitals) }} Faskes
                    </span>
                </div>
            </div>

            <!-- Toggle Filter Chips / Pills (Independent Multi-select Toggles) -->
            <div class="filter-chips-wrapper d-flex gap-2 overflow-x-auto pb-1 mb-3" id="homeFilterChips">
                <!-- Toggle 1: Jarak < 5km -->
                <button type="button" class="chip-filter text-nowrap" data-toggle="distance" id="homeChipDistance" title="Klik untuk memfilter hanya radius 5km">
                    <i class="bi bi-geo-alt-fill me-1"></i> Jarak &lt; 5km
                </button>

                <!-- Toggle 2: Jenis Faskes - Rumah Sakit -->
                <button type="button" class="chip-filter text-nowrap" data-toggle="type" data-type="hospital" id="homeChipHospital">
                    <i class="bi bi-hospital me-1"></i> Rumah Sakit
                </button>

                <!-- Toggle 3: Jenis Faskes - Klinik -->
                <button type="button" class="chip-filter text-nowrap" data-toggle="type" data-type="clinic" id="homeChipClinic">
                    <i class="bi bi-heart-pulse-fill me-1"></i> Klinik
                </button>

                <!-- Toggle 4: Jenis Faskes - Puskesmas -->
                <button type="button" class="chip-filter text-nowrap" data-toggle="type" data-type="puskesmas" id="homeChipPuskesmas">
                    <i class="bi bi-plus-circle-fill me-1"></i> Puskesmas
                </button>

                <!-- Toggle 5: BPJS Only -->
                <button type="button" class="chip-filter text-nowrap" data-toggle="bpjs" id="homeChipBpjs">
                    <i class="bi bi-check2-circle me-1"></i> BPJS
                </button>
            </div>

            <!-- Section Loading Spinner for Hospital Cards on Beranda -->
            <div class="text-center py-5 bg-white rounded-4 border p-4 my-3" id="homeCardsLoadingState">
                <div class="spinner-border text-medigo-primary mb-2" role="status" style="width: 2.2rem; height: 2.2rem;">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <h6 class="fw-bold text-dark mb-1">Mendeteksi Posisi & Menghitung Jarak Terdekat...</h6>
                <p class="text-muted small mb-0">Mohon tunggu sebentar selagi kami menyesuaikan faskes di sekitar Anda.</p>
            </div>

            <!-- Hospital Cards Grid -->
            <div class="row g-3 g-md-4 d-none" id="homeHospitalsGrid">
                @foreach($hospitals as $hospital)
                    <x-card.hospital-card
                        :id="$hospital['id']"
                        :foto="$hospital['foto']"
                        :hospital_name="$hospital['hospital_name']"
                        :type="$hospital['type'] ?? 'Rumah Sakit'"
                        :type_slug="$hospital['type_slug'] ?? 'hospital'"
                        :hospital_address="$hospital['hospital_address']"
                        :hospital_rating="$hospital['hospital_rating']"
                        :hospital_class="$hospital['hospital_class']"
                        :bpjs_support="$hospital['bpjs_support']"
                        :distance="$hospital['distance']"
                        :is_open="$hospital['is_open'] ?? true"
                        :lat="$hospital['lat'] ?? null"
                        :lng="$hospital['lng'] ?? null"
                        :navigasi_url="$hospital['navigasi_url'] ?? '#'"
                        :detail_url="$hospital['detail_url'] ?? '#'"
                    />
                @endforeach
            </div>

            <!-- Load More Container -->
            <div class="text-center mt-4 {{ !($hasMore ?? false) ? 'd-none' : '' }}" id="homeLoadMoreContainer">
                <button type="button" class="btn btn-outline-medigo-primary rounded-pill px-4 py-2 fw-semibold shadow-xs" id="btnHomeLoadMore">
                    <span class="spinner-border spinner-border-sm me-1 d-none" id="homeLoadMoreSpinner" role="status" aria-hidden="true"></span>
                    <i class="bi bi-arrow-down-circle me-1" id="homeLoadMoreIcon"></i>
                    <span id="homeLoadMoreText">Muat Lebih Banyak</span>
                </button>
            </div>

            <!-- Empty State for Beranda -->
            <div class="text-center py-5 d-none bg-white rounded-4 border p-4 mt-3" id="homeEmptyState">
                <i class="bi bi-geo-alt text-muted fs-1 mb-2 d-block"></i>
                <h5 class="fw-bold text-dark mb-1">Tidak Ada Faskes yang Sesuai</h5>
                <p class="text-muted small mb-3">Coba nonaktifkan beberapa filter di atas untuk melihat faskes lainnya.</p>
                <button type="button" class="btn btn-sm btn-medigo-primary rounded-pill px-4 py-2" id="btnHomeResetFilter">
                    Reset Semua Filter
                </button>
            </div>
        </div>
    </section>    
@endsection

@push('scripts')
    <!-- Pass Initial Hospital Data to Beranda JS Module -->
    <script>
        window.hospitalsInitialData = @json($hospitals);
        window.hasMoreInitial = @json($hasMore ?? false);
    </script>
    @vite(['resources/js/beranda.js'])
@endpush