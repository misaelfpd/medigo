@extends('layouts.app')

@section('title', 'Pencarian Fasilitas Kesehatan - MediGO')


@section('content')
<div class="search-app-container">

    {{-- 1. HEADER SECTION (STICKY & FIXED, CLEAN, PROPORTIONAL MARGINS) --}}
    <header class="search-app-header" id="searchAppHeader">
        
        {{-- Top Bar with Back Button (Aligned with Container, Clean Margins) --}}
        <div class="search-top-bar px-3 px-sm-4">
            <div class="container-fluid px-0 d-flex align-items-center justify-content-between" style="max-width: 1240px;">
                <a href="{{ route('faskes.index') }}" class="btn btn-sm rounded-pill px-3 py-1.5 d-inline-flex align-items-center gap-2 fw-semibold text-decoration-none shadow-2xs" style="font-size: 0.85rem;">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <div class="text-muted fw-semibold d-none d-md-flex align-items-center gap-2" style="font-size: 0.85rem;">
                    <i class="bi bi-sliders2 text-medigo-primary"></i>
                    <span>Filter & Pencarian Faskes</span>
                </div>
            </div>
        </div>

        {{-- Search Controls Wrapper --}}
        <div class="search-controls-wrapper px-3 px-sm-4 py-3 bg-white">
            <div class="container-fluid px-0 gap-2" style="max-width: 1240px;">
                
                {{-- Main Search Bar Row with Expand/Collapse Button --}}
                <div class="d-flex align-items-center gap-2 mb-2">
                    <form id="searchFaskesForm" class="flex-grow-1 m-0">
                        <div class="input-group main-search-input-group p-1 d-flex align-items-center">
                            <span class="input-group-text bg-transparent border-0 ps-3 pe-2 text-muted">
                                <i class="bi bi-search text-muted fs-6"></i>
                            </span>
                            <input type="text" 
                                   id="searchKeywordInput" 
                                   name="q" 
                                   class="form-control border-0 shadow-none ps-1 bg-transparent" 
                                   placeholder="Cari nama rumah sakit, klinik, atau wilayah di Jabodetabek..." 
                                   value="{{ $initialQuery['q'] ?? '' }}"
                                   autocomplete="off">
                            <button type="submit" 
                                    id="btnSubmitSearch" 
                                    class="btn btn-medigo-primary px-3 px-md-4 py-1.5 rounded-pill fw-semibold text-white shadow-xs me-1">
                                Cari RS
                            </button>
                        </div>
                    </form>

                    {{-- Dedicated Toggle / Expand Button (Desktop & Mobile) --}}
                    <button type="button" 
                            id="btnToggleSearchExpand" 
                            class="btn btn-toggle-expand rounded-pill px-3 py-2 fw-semibold d-inline-flex align-items-center gap-1.5"
                            aria-expanded="false"
                            aria-controls="searchExpandableSection"
                            title="Buka / Ciutkan Kolom Pencarian dan Filter Lengkap">
                        <div class="gap-2">
                            <i class="bi bi-sliders2 fs-6"></i>
                            <span class="d-none d-sm-inline" id="toggleSearchText">Filter</span>
                            <span class="badge bg-medigo-primary text-white rounded-pill ms-0.5 d-none" id="toggleFilterCountBadge">0</span>
                            <!-- <i class="bi bi-chevron-down fs-7 transition-chevron" id="toggleSearchChevron"></i> -->
                        </div>
                        </button>
                </div>

                {{-- Expandable Section (Contains Title, Filters, Popular Tags) --}}
                <div id="searchExpandableSection" class="search-expandable-section">
                    
                    {{-- Title / Subtitle + Reset Filter Row --}}
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pt-3 pb-2 mb-3 border-top" style="border-color: #f1f5f9 !important;">
                        <div>
                            <h5 class="fw-bold mb-1" style="color: #0b2b52; font-size: 1.15rem;">
                                Filter & Spesifikasi Faskes di Jabodetabek
                            </h5>
                            <p class="text-muted mb-0 small d-none d-md-block" style="font-size: 0.82rem;">
                                Saring fasilitas kesehatan berdasarkan wilayah, kelas, jaminan BPJS, dan layanan medis
                            </p>
                        </div>

                        <div>
                            <button type="button" id="btnResetFilters" class="btn btn-link p-0 text-decoration-none text-muted fw-semibold d-inline-flex align-items-center gap-1.5" style="font-size: 0.84rem;">
                                <i class="bi bi-arrow-counterclockwise"></i> Reset Semua Filter
                            </button>
                        </div>
                    </div>

                    {{-- Filter Controls Row (Dropdowns for Daerah, Kelas, BPJS; Modals for Fasilitas, Spesialisasi) --}}
                    <div class="search-filters-row align-items-center mb-3" id="searchFiltersRow">
                        
                        {{-- Dropdown Kota / Wilayah (Daerah) --}}
                        <div class="filter-dropdown-wrapper position-relative">
                            <button class="filter-select-btn" 
                                    type="button" 
                                    id="btnFilterKota">
                                <i class="bi bi-geo-alt text-medigo-primary"></i>
                                <span id="labelFilterKota">Kota / Wilayah</span>
                                <i class="bi bi-chevron-down fs-7 text-muted chevron-icon ms-1"></i>
                            </button>
                            <div class="custom-filter-menu shadow border p-1" id="menuFilterKota">
                                <a class="dropdown-item opt-filter-kota active" href="javascript:void(0)" data-value="">Semua Wilayah</a>
                                <div class="dropdown-divider my-1"></div>
                                @foreach($options['kota'] ?? [] as $k)
                                    <a class="dropdown-item opt-filter-kota" href="javascript:void(0)" data-value="{{ $k }}">{{ $k }}</a>
                                @endforeach
                            </div>
                        </div>

                        {{-- Dropdown Kelas RS --}}
                        <div class="filter-dropdown-wrapper position-relative">
                            <button class="filter-select-btn" 
                                    type="button" 
                                    id="btnFilterKelas">
                                <i class="bi bi-hospital text-medigo-primary"></i>
                                <span id="labelFilterKelas">Kelas RS</span>
                                <i class="bi bi-chevron-down fs-7 text-muted chevron-icon ms-1"></i>
                            </button>
                            <div class="custom-filter-menu shadow border p-1" id="menuFilterKelas">
                                <a class="dropdown-item opt-filter-kelas active" href="javascript:void(0)" data-value="">Semua Kelas</a>
                                <div class="dropdown-divider my-1"></div>
                                <a class="dropdown-item opt-filter-kelas" href="javascript:void(0)" data-value="A">Kelas A (Rujukan Utama)</a>
                                <a class="dropdown-item opt-filter-kelas" href="javascript:void(0)" data-value="B">Kelas B (Rujukan Regional)</a>
                                <a class="dropdown-item opt-filter-kelas" href="javascript:void(0)" data-value="C">Kelas C (Rujukan Sub-Regional)</a>
                                <a class="dropdown-item opt-filter-kelas" href="javascript:void(0)" data-value="D">Kelas D (Pelayanan Dasar)</a>
                            </div>
                        </div>

                        {{-- Dropdown Jaminan / Asuransi (BPJS) --}}
                        <div class="filter-dropdown-wrapper position-relative">
                            <button class="filter-select-btn" 
                                    type="button" 
                                    id="btnFilterBpjs">
                                <i class="bi bi-shield-check text-medigo-primary"></i>
                                <span id="labelFilterBpjs">Jaminan / Asuransi</span>
                                <i class="bi bi-chevron-down fs-7 text-muted chevron-icon ms-1"></i>
                            </button>
                            <div class="custom-filter-menu shadow border p-1" id="menuFilterBpjs">
                                <a class="dropdown-item opt-filter-bpjs active" href="javascript:void(0)" data-value="">Semua Jaminan</a>
                                <div class="dropdown-divider my-1"></div>
                                <a class="dropdown-item opt-filter-bpjs" href="javascript:void(0)" data-value="1">BPJS Kesehatan</a>
                                <a class="dropdown-item opt-filter-bpjs" href="javascript:void(0)" data-value="0">Non - BPJS</a>
                            </div>
                        </div>

                        {{-- Modal Trigger: Fasilitas Penunjang --}}
                        <button type="button" 
                                class="filter-select-btn" 
                                id="btnOpenFasilitasModal" 
                                data-bs-toggle="modal" 
                                data-bs-target="#modalFasilitas">
                            <i class="bi bi-building-add text-medigo-primary"></i>
                            <span id="labelFilterFasilitas">Fasilitas Penunjang</span>
                            <i class="bi bi-chevron-down fs-7 text-muted"></i>
                        </button>

                        {{-- Modal Trigger: Spesialisasi Medis --}}
                        <button type="button" 
                                class="filter-select-btn" 
                                id="btnOpenSpesialisasiModal" 
                                data-bs-toggle="modal" 
                                data-bs-target="#modalSpesialisasi">
                            <i class="bi bi-heart-pulse text-medigo-primary"></i>
                            <span id="labelFilterSpesialisasi">Spesialisasi Medis</span>
                            <i class="bi bi-chevron-down fs-7 text-muted"></i>
                        </button>

                    </div>

                </div>

            </div>
        </div>

        <!-- {{-- Quick Expand/Collapse Handle Bar --}}
        <div class="search-quick-toggle-bar text-center">
            <button type="button" id="btnBottomExpandToggle" class="btn-quick-toggle text-muted" aria-label="Toggle Panel Filter">
                <span id="bottomExpandText">Buka Filter & Opsi Lengkap</span>
                <i class="bi bi-chevron-down ms-1" id="bottomExpandChevron"></i>
            </button>
        </div> -->

        {{-- Status Bar (Location & Count & Active Badges) --}}
        <div class="search-status-bar bg-white px-3 px-sm-4 py-2.5 border-top border-bottom">
            <div class="container-fluid py-2 px-1 d-flex flex-wrap align-items-center justify-content-between gap-2.5" style="max-width: 1240px;">
                <div class="d-flex align-items-center gap-2 flex-wrap" style="font-size: 0.82rem;">
                    <span class="d-inline-flex align-items-center text-dark bg-white px-2 py-1 rounded-pill border shadow-xs" id="searchLocationIndicator">
                        <i class="bi bi-geo-alt-fill text-danger me-1"></i>
                        <span id="searchLocationText" class="fw-medium text-truncate" style="max-width: 220px;">Mendeteksi GPS...</span>
                    </span>
                    <div id="activeFilterTagsContainer" class="d-flex align-items-center gap-1.5 flex-wrap"></div>
                </div>

                <div>
                    <span class="badge bg-light text-dark border rounded-pill px-3 py-1.5 fw-semibold shadow-xs" id="searchCountBadge" style="font-size: 0.8rem;">
                        Memuat data...
                    </span>
                </div>
            </div>
        </div>

    </header>

    {{-- 2. SCROLLABLE RESULTS SECTION (ONLY THIS BODY SCROLLS) --}}
    <main class="search-scroll-body px-3 px-sm-4" id="searchResultsScrollContainer">
        <div class="container-fluid px-0" style="max-width: 1240px;">
            
            {{-- Loading State --}}
            <div id="searchLoadingState" class="text-center py-5 bg-white rounded-4 border p-4 my-3 d-none">
                <div class="spinner-border text-medigo-primary mb-2" role="status" style="width: 2.2rem; height: 2.2rem;">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div class="text-muted fw-medium" style="font-size: 0.9rem;">Mencari fasilitas kesehatan sesuai filter Anda...</div>
            </div>

            {{-- Results Grid --}}
            <div id="searchResultsGrid" class="row g-3 g-lg-4 mb-4"></div>

            {{-- Empty State --}}
            <div id="searchEmptyState" class="text-center py-5 bg-white rounded-4 border p-4 my-3 d-none">
                <div class="mb-3">
                    <div class="bg-medigo-subtle d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 70px; height: 70px;">
                        <i class="bi bi-hospital fs-1 text-medigo-primary"></i>
                    </div>
                </div>
                <h5 class="fw-bold text-dark mb-1">Tidak Ada Fasilitas Kesehatan Ditemukan</h5>
                <p class="text-muted mb-3 mx-auto" style="max-width: 440px; font-size: 0.9rem;">
                    Coba sesuaikan kata kunci pencarian Anda atau gunakan tombol Reset Filter untuk melihat semua fasilitas kesehatan.
                </p>
                <button type="button" class="btn btn-medigo-primary rounded-pill px-4 py-2 fw-semibold text-white btn-reset-from-empty shadow-xs">
                    Reset Semua Filter
                </button>
            </div>

        </div>
    </main>

</div>

{{-- MODAL SEARCHABLE: FASILITAS PENUNJANG (762 DATA) --}}
<div class="modal fade" id="modalFasilitas" tabindex="-1" aria-labelledby="modalFasilitasLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
            <div class="modal-header border-bottom px-4 py-3 bg-light">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-building-add text-medigo-primary fs-5"></i>
                    <h5 class="modal-title fw-bold text-dark mb-0" id="modalFasilitasLabel">Pilih Fasilitas Penunjang</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                {{-- Quick Search Input inside Modal --}}
                <div class="input-group mb-3">
                    <span class="input-group-text bg-white border-end-0 text-muted ps-3">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" 
                           id="modalFasilitasSearchInput" 
                           class="form-control border-start-0 shadow-none ps-2" 
                           placeholder="Ketik untuk mencari fasilitas (contoh: IGD, Lab, ICU, Radiologi, Ambulans)..."
                           autocomplete="off">
                </div>

                {{-- List Items Grid (Auto-filtered by Search Input) --}}
                <div class="text-muted fw-bold mb-2" style="font-size: 0.78rem; text-transform: uppercase;">
                    Semua Fasilitas Penunjang (<span id="countFasilitasListed">{{ count($options['fasilitas'] ?? []) }}</span>):
                </div>
                <div class="row g-2" id="modalFasilitasListContainer" style="max-height: 340px; overflow-y: auto;">
                    @foreach($options['fasilitas'] ?? [] as $fas)
                        <div class="col-12 col-md-6 modal-fasilitas-item" data-name="{{ strtolower($fas['nama_fasilitas'] ?? '') }}">
                            <button type="button" 
                                    class="modal-item-btn btn-choose-fasilitas d-flex align-items-center justify-content-between" 
                                    data-value="{{ $fas['nama_fasilitas'] ?? '' }}">
                                <span class="text-truncate me-2">{{ $fas['nama_fasilitas'] ?? '' }}</span>
                                <i class="bi bi-check2 check-icon d-none fs-5"></i>
                            </button>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="modal-footer border-top px-4 py-3 bg-light justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3" id="btnClearFasilitasModal">Hapus Pilihan</button>
                    <span class="small text-muted fw-semibold" id="countFasilitasSelectedText"></span>
                </div>
                <button type="button" class="btn btn-medigo-primary btn-sm rounded-pill px-4 fw-semibold text-white shadow-xs" id="btnApplyFasilitasModal" data-bs-dismiss="modal">Terapkan Filter</button>
            </div>
        </div>
    </div>
</div>

{{-- MODAL SEARCHABLE: SPESIALISASI MEDIS (152 DATA) --}}
<div class="modal fade" id="modalSpesialisasi" tabindex="-1" aria-labelledby="modalSpesialisasiLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
            <div class="modal-header border-bottom px-4 py-3 bg-light">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-heart-pulse text-medigo-primary fs-5"></i>
                    <h5 class="modal-title fw-bold text-dark mb-0" id="modalSpesialisasiLabel">Pilih Spesialisasi Medis</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                {{-- Quick Search Input inside Modal --}}
                <div class="input-group mb-3">
                    <span class="input-group-text bg-white border-end-0 text-muted ps-3">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" 
                           id="modalSpesialisasiSearchInput" 
                           class="form-control border-start-0 shadow-none ps-2" 
                           placeholder="Ketik untuk mencari dokter spesialis (contoh: Anak, Jantung, Mata, Bedah, Gigi)..."
                           autocomplete="off">
                </div>

                {{-- List Items Grid (Auto-filtered by Search Input) --}}
                <div class="text-muted fw-bold mb-2" style="font-size: 0.78rem; text-transform: uppercase;">
                    Semua Spesialisasi Medis (<span id="countSpesialisasiListed">{{ count($options['spesialisasi'] ?? []) }}</span>):
                </div>
                <div class="row g-2" id="modalSpesialisasiListContainer" style="max-height: 340px; overflow-y: auto;">
                    @foreach($options['spesialisasi'] ?? [] as $spes)
                        <div class="col-12 col-md-6 modal-spesialisasi-item" data-name="{{ strtolower($spes['nama_spesialisasi'] ?? '') }}">
                            <button type="button" 
                                    class="modal-item-btn btn-choose-spesialisasi d-flex align-items-center justify-content-between" 
                                    data-value="{{ $spes['nama_spesialisasi'] ?? '' }}">
                                <span class="text-truncate me-2">{{ $spes['nama_spesialisasi'] ?? '' }}</span>
                                <i class="bi bi-check2 check-icon d-none fs-5"></i>
                            </button>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="modal-footer border-top px-4 py-3 bg-light justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3" id="btnClearSpesialisasiModal">Hapus Pilihan</button>
                    <span class="small text-muted fw-semibold" id="countSpesialisasiSelectedText"></span>
                </div>
                <button type="button" class="btn btn-medigo-primary btn-sm rounded-pill px-4 fw-semibold text-white shadow-xs" id="btnApplySpesialisasiModal" data-bs-dismiss="modal">Terapkan Filter</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    window.MedigoSearchConfig = {
        dataUrl: '{{ route('search.data') }}',
        initialQuery: @json($initialQuery)
    };
</script>
@vite('resources/js/search.js')
@endpush
@endsection
