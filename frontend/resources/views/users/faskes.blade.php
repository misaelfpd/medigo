
@extends('layouts.app')

@section('title', 'MediGO - Beranda')

@section('content')
    {{-- Sticky Search Bar (Stays pinned cleanly below navbar on scroll, solid white, no overlapping bugs) --}}
    <div id="stickyHomeSearchBar" class="sticky-home-search-bar" aria-label="Pencarian Cepat">
        <div class="container">
            <form action="{{ route('search.index') }}" method="GET" class="m-0 d-flex align-items-center gap-2">
                <div class="input-group d-flex align-items-center rounded-pill border bg-white p-1 flex-grow-1 shadow-2xs" style="border-color: #cbd5e1 !important;">
                    <span class="input-group-text bg-transparent border-0 ps-3 pe-2 text-muted">
                        <i class="bi bi-search text-medigo-primary fs-6"></i>
                    </span>
                    <input type="text" 
                           name="q" 
                           class="form-control border-0 shadow-none ps-1 bg-transparent" 
                           placeholder="Cari RS, klinik, atau spesialis..." 
                           autocomplete="off"
                           style="font-size: 0.88rem;">
                    <a href="{{ route('search.index') }}" class="btn btn-outline-secondary rounded-pill px-2 py-1 me-1 fw-medium d-inline-flex align-items-center gap-1 border-0" title="Buka Filter Lengkap" style="font-size: 0.82rem; color: #475569;">
                        <i class="bi bi-sliders2 fs-7"></i> <span class="d-none d-sm-inline">Filter</span>
                    </a>
                    <button type="submit" class="btn btn-medigo-primary px-3 py-1.5 rounded-pill fw-semibold text-white shadow-xs" style="font-size: 0.84rem;">
                        Cari RS
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Search Banner / Bar Section --}}
    <section class="home-search-banner pt-3 pb-2 pt-lg-4 pb-lg-3">
        <div class="container">
            <div class="card border-0 rounded-4 shadow-sm p-3 p-md-4 bg-white" style="border: 1px solid #e2e8f0 !important; box-shadow: 0 4px 20px rgba(11,43,82,0.06);">
                <div class="row align-items-center g-3">
                    <div class="col-12 col-lg-5">
                        <h4 class="fw-bold mb-1" style="color: #0b2b52;">Temukan Fasilitas Kesehatan</h4>
                        <p class="text-muted mb-0" style="font-size: 0.88rem;">Cari rumah sakit, klinik, puskesmas, dan spesialisasi medis</p>
                    </div>
                    <div class="col-12 col-lg-7">
                        <form action="{{ route('search.index') }}" method="GET" class="m-0">
                            <div class="input-group d-flex align-items-center rounded-pill border bg-white p-1" style="border-color: #cbd5e1 !important; transition: border-color 0.2s;">
                                <span class="input-group-text bg-transparent border-0 ps-3 text-muted">
                                    <i class="bi bi-search text-medigo-primary fs-6"></i>
                                </span>
                                <input type="text" 
                                       name="q" 
                                       class="form-control border-0 shadow-none ps-2 bg-transparent" 
                                       placeholder="Cari nama rumah sakit, klinik, atau wilayah..." 
                                       autocomplete="off"
                                       style="font-size: 0.9rem;">
                                <a href="{{ route('search.index') }}" class="btn btn-outline-secondary rounded-pill px-2.5 px-sm-3 py-1.5 me-1 fw-medium d-inline-flex align-items-center gap-1 border-0" title="Buka Filter Lengkap" style="font-size: 0.84rem; color: #475569;">
                                    <i class="bi bi-sliders2 fs-7"></i> <span class="d-none d-sm-inline">Filter</span>
                                </a>
                                <button type="submit" class="btn btn-medigo-primary px-3 px-sm-4 py-2 rounded-pill fw-semibold text-white shadow-xs">
                                    Cari RS
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Akses Cepat Filter Langsung Dari Beranda --}}
                <!-- <div class="d-flex align-items-center gap-2 mt-3 pt-3 border-top flex-wrap" style="border-color: #f1f5f9 !important;">
                    <span class="text-muted fw-semibold d-none d-sm-inline" style="font-size: 0.8rem;">Akses Langsung Filter:</span>
                    <a href="{{ route('search.index', ['bpjs' => 1]) }}" class="badge-filter-quick">
                        <i class="bi bi-shield-check text-success"></i> BPJS Kesehatan
                    </a>
                    <a href="{{ route('search.index', ['fasilitas' => 'IGD']) }}" class="badge-filter-quick">
                        <i class="bi bi-lightning-charge-fill text-warning"></i> IGD 24 Jam
                    </a>
                    <a href="{{ route('search.index', ['spesialisasi' => 'Anak']) }}" class="badge-filter-quick">
                        <i class="bi bi-person-heart text-info"></i> Spesialis Anak
                    </a>
                    <a href="{{ route('search.index', ['kelas' => 'A']) }}" class="badge-filter-quick">
                        <i class="bi bi-hospital text-primary"></i> RS Kelas A
                    </a>
                    <a href="{{ route('search.index') }}" class="ms-auto btn-medigo-primary">
                        <i class="bi bi-sliders2"></i> Buka Semua Filter <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div> -->
            </div>
        </div>
    </section>

    {{-- Rekomendasi / Hospital Section --}}
    <section class="hospital-section py-3 py-lg-4">
        <div class="container">

            {{-- Header --}}
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <h4 class="fw-bold mb-1 section-title text-dark"
                        id="homeSectionHeading">
                        Rumah Sakit Terdekat
                    </h4>

                    {{-- Location Indicator --}}
                    <div class="d-flex align-items-center gap-2 flex-wrap"
                         style="font-size: 0.78rem;">

                        <span class="d-inline-flex align-items-center text-dark bg-white px-2 py-1 rounded-pill border shadow-xs"
                              id="homeLocationIndicator">

                            <i class="bi bi-geo-alt-fill text-danger me-1"
                               id="homeLocationIcon"></i>

                            <span id="homeCurrentLocationText"
                                  class="fw-medium text-truncate"
                                  style="max-width: 260px;">
                                Mendeteksi lokasi GPS...
                            </span>

                            <span class="badge bg-success-subtle text-success ms-1 d-none"
                                  id="homeGpsLockedBadge"
                                  style="font-size: 0.6rem;">
                                GPS Terkunci
                            </span>

                            <span class="badge bg-warning-subtle text-warning ms-1 d-none"
                                  id="homeRatingSortBadge"
                                  style="font-size: 0.6rem;">
                                Urut Rating
                            </span>
                        </span>

                        <button type="button"
                                class="btn btn-sm btn-link p-0 text-decoration-none text-muted fw-semibold d-inline-flex align-items-center gap-1"
                                id="btnHomeDetectLocation"
                                title="Perbarui lokasi GPS">

                            <span class="spinner-border spinner-border-sm d-none"
                                  id="btnHomeSpinner"
                                  role="status"
                                  style="width: 0.75rem; height: 0.75rem;">
                            </span>

                            <i class="bi bi-arrow-clockwise"
                               id="btnHomeIcon"></i>

                            <span id="btnHomeDetectText">
                                Perbarui
                            </span>
                        </button>
                    </div>
                </div>

                {{-- Total Faskes --}}
                <div class="d-flex align-items-center gap-2 ms-auto">
                    <span class="badge badge-medigo-primary rounded-pill px-3 py-2 d-none"
                          style="font-size: 0.74rem;"
                          id="homeTotalCountBadge">
                        {{ count($hospitals) }} Faskes
                    </span>
                </div>
            </div>

            {{-- Filter Chips --}}
            <div class="filter-chips-wrapper d-flex gap-2 overflow-x-auto pb-1 mb-3"
                 id="homeFilterChips">

                {{-- Distance Filter --}}
                <button type="button"
                        class="chip-filter text-nowrap"
                        data-toggle="distance"
                        id="homeChipDistance"
                        title="Klik untuk memfilter hanya radius 5km">

                    <i class="bi bi-geo-alt-fill me-1"></i>
                    Jarak &lt; 5km
                </button>

                {{-- Hospital Filter --}}
                <button type="button"
                        class="chip-filter text-nowrap"
                        data-toggle="type"
                        data-type="hospital"
                        id="homeChipHospital">

                    <i class="bi bi-hospital me-1"></i>
                    Rumah Sakit
                </button>

                {{-- Clinic Filter --}}
                <button type="button"
                        class="chip-filter text-nowrap"
                        data-toggle="type"
                        data-type="clinic"
                        id="homeChipClinic">

                    <i class="bi bi-heart-pulse-fill me-1"></i>
                    Klinik
                </button>

                {{-- Puskesmas Filter --}}
                <button type="button"
                        class="chip-filter text-nowrap"
                        data-toggle="type"
                        data-type="puskesmas"
                        id="homeChipPuskesmas">

                    <i class="bi bi-plus-circle-fill me-1"></i>
                    Puskesmas
                </button>

                {{-- BPJS Filter --}}
                <button type="button"
                        class="chip-filter text-nowrap"
                        data-toggle="bpjs"
                        id="homeChipBpjs">

                    <i class="bi bi-check2-circle me-1"></i>
                    BPJS
                </button>
            </div>

            {{-- Loading State --}}
            <div class="text-center py-5 bg-white rounded-4 border p-4 my-3"
                 id="homeCardsLoadingState">

                <div class="spinner-border text-medigo-primary mb-2"
                     role="status"
                     style="width: 2.2rem; height: 2.2rem;">

                    <span class="visually-hidden">
                        Loading...
                    </span>
                </div>

                <h6 class="fw-bold text-dark mb-1">
                    Mendeteksi Posisi & Menghitung Jarak Terdekat...
                </h6>

                <p class="text-muted small mb-0">
                    Mohon tunggu sebentar selagi kami menyesuaikan
                    faskes di sekitar Anda.
                </p>
            </div>

            {{-- Hospital Cards Grid --}}
            <div class="row g-3 g-md-4 d-none"
                 id="homeHospitalsGrid">

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

            {{-- Load More Container --}}
            <div class="text-center mt-4 {{ !($hasMore ?? false) ? 'd-none' : '' }}"
                 id="homeLoadMoreContainer">

                <button type="button"
                        class="btn btn-outline-medigo-primary rounded-pill px-4 py-2 fw-semibold shadow-xs"
                        id="btnHomeLoadMore">

                    <span class="spinner-border spinner-border-sm me-1 d-none"
                          id="homeLoadMoreSpinner"
                          role="status"
                          aria-hidden="true">
                    </span>

                    <i class="bi bi-arrow-down-circle me-1"
                       id="homeLoadMoreIcon"></i>

                    <span id="homeLoadMoreText">
                        Muat Lebih Banyak
                    </span>
                </button>
            </div>

            {{-- Empty State: Belum Ada Faskes --}}
            <div class="text-center py-5 d-none bg-white rounded-4 border p-4 mt-3"
                 id="homeEmptyState">

                <i class="bi bi-hospital text-muted fs-1 mb-3 d-block"></i>

                <h5 class="fw-bold text-dark mb-2">
                    Belum Ada Fasilitas Kesehatan
                </h5>

                <p class="text-muted small mb-0">
                    Saat ini belum terdapat data fasilitas kesehatan
                    yang dapat ditampilkan. Silakan coba kembali
                    beberapa saat lagi.
                </p>
            </div>

            {{-- Empty State: Filter Tidak Menemukan Hasil --}}
            <div class="text-center py-5 d-none bg-white rounded-4 border p-4 mt-3"
                 id="homeNoResultsState">

                <i class="bi bi-search text-muted fs-1 mb-3 d-block"></i>

                <h5 class="fw-bold text-dark mb-2">
                    Tidak Ada Faskes yang Sesuai
                </h5>

                <p class="text-muted small mb-3">
                    Tidak ditemukan fasilitas kesehatan berdasarkan
                    filter yang kamu pilih. Coba nonaktifkan beberapa
                    filter untuk melihat faskes lainnya.
                </p>

                <button type="button"
                        class="btn btn-sm btn-medigo-primary rounded-pill px-4 py-2"
                        id="btnHomeResetFilter">

                    Reset Semua Filter
                </button>
            </div>

        </div>
    </section>
@endsection

@push('scripts')

    {{-- Initial Data for Beranda JS --}}
    <script>
        window.hospitalsInitialData = @json($hospitals);
        window.hasMoreInitial = @json($hasMore ?? false);
    </script>

    @vite(['resources/js/beranda.js'])

@endpush
