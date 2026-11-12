@extends('layouts.app')

@section('title', 'Pencarian Fasilitas Kesehatan - MediGO')

@push('styles')
    @vite('resources/css/app.css')
    <style>
        .search-app-container {
            height: 100vh;
            height: 100dvh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            background-color: #f8fafc;
        }

        .search-app-header {
            flex-shrink: 0;
            background-color: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            box-shadow: 0 4px 15px rgba(11, 43, 82, 0.04);
            z-index: 50;
        }

        .search-top-nav {
            background-color: #ffffff;
            border-bottom: 1px solid #f1f5f9;
        }

        .main-search-input-group {
            border: 1.5px solid #cbd5e1;
            border-radius: 50rem;
            transition: all 0.2s ease;
            background: #ffffff;
        }
        .main-search-input-group:focus-within {
            border-color: #0b2b52;
            box-shadow: 0 0 0 3px rgba(11, 43, 82, 0.1);
        }

        .search-filters-row {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            align-items: center;
        }
        @media (max-width: 767.98px) {
            .search-filters-row {
                flex-wrap: nowrap;
                overflow-x: auto;
                padding-bottom: 4px;
                -webkit-overflow-scrolling: touch;
                scrollbar-width: none;
            }
            .search-filters-row::-webkit-scrollbar {
                display: none;
            }
        }

        .filter-select-btn {
            background-color: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 50rem;
            color: #334155;
            font-size: 0.84rem;
            font-weight: 500;
            padding: 0.45rem 1.05rem;
            transition: all 0.15s ease;
            white-space: nowrap;
        }
        .filter-select-btn:hover {
            border-color: #94a3b8;
            background-color: #f8fafc;
            color: #0b2b52;
        }
        .filter-select-btn.is-active {
            background-color: #e0f2fe;
            border-color: #0284c7;
            color: #0369a1;
            font-weight: 600;
        }

        .popular-search-tag {
            background-color: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            font-size: 0.78rem;
            padding: 0.2rem 0.6rem;
            cursor: pointer;
            transition: all 0.15s ease;
            text-decoration: none;
            display: inline-block;
        }
        .popular-search-tag:hover {
            background-color: #e2e8f0;
            color: #0b2b52;
        }
        .popular-search-tag.active {
            background-color: #0b2b52;
            color: #ffffff;
            border-color: #0b2b52;
        }

        .modal-item-btn {
            border: 1px solid #e2e8f0;
            background: #ffffff;
            border-radius: 10px;
            padding: 0.5rem 0.75rem;
            font-size: 0.85rem;
            text-align: left;
            transition: all 0.15s ease;
            cursor: pointer;
            width: 100%;
        }
        .modal-item-btn:hover {
            border-color: #0b2b52;
            background: #f8fafc;
        }
        .modal-item-btn.selected {
            background: #0b2b52;
            color: #ffffff;
            border-color: #0b2b52;
            font-weight: 600;
        }

        .search-scroll-body {
            flex: 1 1 auto;
            overflow-y: auto !important;
            -webkit-overflow-scrolling: touch;
            padding-top: 1rem;
            padding-bottom: 3.5rem;
        }

        .search-faskes-card {
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        .search-faskes-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(11, 43, 82, 0.08);
            border-color: #cbd5e1;
        }
    </style>
@endpush

@section('content')
<div class="search-app-container">

    {{-- 1. HEADER SECTION (STICKY & FIXED, NO SHAKE / JITTER ON SCROLL) --}}
    <header class="search-app-header" id="searchAppHeader">
        
        {{-- Top App Navigation --}}
        <div class="search-top-nav px-3 px-lg-4 py-2 d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2 gap-sm-3">
                <a href="{{ route('faskes.index') }}" class="d-flex align-items-center text-decoration-none">
                    <img style="max-height: 32px; width: auto;" src="{{ asset('images/medigo_logo.png') }}" alt="MediGO">
                </a>
                <a href="{{ route('faskes.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-2.5 px-sm-3 py-1 d-inline-flex align-items-center gap-1.5" style="font-size: 0.8rem; font-weight: 500;">
                    <i class="bi bi-arrow-left"></i> <span class="d-none d-sm-inline">Kembali ke Beranda</span><span class="d-sm-none">Beranda</span>
                </a>
            </div>

            <div class="d-flex align-items-center gap-2">
                @if(Auth::check())
                    <a href="{{ route('profile') }}" class="btn user-nav-btn d-flex align-items-center gap-1.5 rounded-pill px-2.5 py-1 text-decoration-none border" style="font-size: 0.82rem;">
                        @if(!empty(Auth::user()->avatar) && file_exists(public_path(Auth::user()->avatar)))
                            <img src="{{ asset(Auth::user()->avatar) }}" alt="{{ Auth::user()->name }}" class="rounded-circle object-fit-cover shadow-2xs" style="width: 26px; height: 26px;">
                        @else
                            <div class="avatar-circle-sm bg-medigo-gradient text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 26px; height: 26px; font-size: 0.75rem;">
                                {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}
                            </div>
                        @endif
                        <span class="user-nav-name fw-semibold text-truncate text-dark d-none d-sm-inline" style="max-width: 120px;">
                            {{ Auth::user()->name }}
                        </span>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-sm btn-medigo-primary rounded-pill px-3 py-1 fw-semibold text-white text-decoration-none" style="font-size: 0.82rem;">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Masuk
                    </a>
                @endif
            </div>
        </div>

        {{-- Search Controls Wrapper --}}
        <div class="search-controls-wrapper px-3 px-lg-4 pt-2.5 pb-2 bg-white">
            <div class="container-fluid px-0" style="max-width: 1240px;">
                
                {{-- Main Search Bar Row with Expand/Collapse Button --}}
                <div class="d-flex align-items-center gap-2 mb-1.5">
                    <form id="searchFaskesForm" class="flex-grow-1 m-0">
                        <div class="input-group main-search-input-group p-1 d-flex align-items-center">
                            <span class="input-group-text bg-transparent border-0 ps-3 pe-2 text-muted">
                                <i class="bi bi-search text-muted fs-6"></i>
                            </span>
                            <input type="text" 
                                   id="searchKeywordInput" 
                                   name="q" 
                                   class="form-control border-0 shadow-none ps-1 bg-transparent" 
                                   placeholder="Cari nama rumah sakit, klinik, atau wilayah..." 
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
                            class="btn btn-toggle-expand rounded-pill px-2.5 px-sm-3 py-1.5 fw-semibold d-inline-flex align-items-center gap-1.5"
                            aria-expanded="false"
                            aria-controls="searchExpandableSection"
                            title="Buka / Ciutkan Kolom Pencarian dan Filter Lengkap">
                        <i class="bi bi-sliders2 fs-6"></i>
                        <span class="d-none d-sm-inline" id="toggleSearchText">Filter</span>
                        <span class="badge bg-medigo-primary text-white rounded-pill ms-0.5 d-none" id="toggleFilterCountBadge">0</span>
                        <i class="bi bi-chevron-down fs-7 transition-chevron" id="toggleSearchChevron"></i>
                    </button>
                </div>

                {{-- Expandable Section (Contains Title, Filters, Popular Tags) --}}
                <div id="searchExpandableSection" class="search-expandable-section">
                    
                    {{-- Title / Subtitle + Reset Filter Row --}}
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2 pt-1 border-top" style="border-color: #f1f5f9 !important;">
                        <div>
                            <h5 class="fw-bold mb-0" style="color: #0b2b52; font-size: 1.15rem;">
                                Filter & Spesifikasi Faskes
                            </h5>
                            <p class="text-muted mb-0 small d-none d-md-block" style="font-size: 0.8rem;">
                                Saring fasilitas kesehatan berdasarkan wilayah, kelas, jaminan, dan layanan medis
                            </p>
                        </div>

                        <div>
                            <button type="button" id="btnResetFilters" class="btn btn-link p-0 text-decoration-none text-muted fw-semibold d-inline-flex align-items-center gap-1" style="font-size: 0.82rem;">
                                <i class="bi bi-arrow-counterclockwise"></i> Reset Semua Filter
                            </button>
                        </div>
                    </div>

                    {{-- Filter Controls Row --}}
                    <div class="search-filters-row align-items-center mb-2" id="searchFiltersRow">
                        
                        {{-- Dropdown Kota / Wilayah --}}
                        <div class="dropdown">
                            <button class="filter-select-btn d-flex align-items-center gap-2 dropdown-toggle" 
                                    type="button" 
                                    id="btnFilterKota" 
                                    data-bs-toggle="dropdown" 
                                    aria-expanded="false">
                                <span id="labelFilterKota">Kota / Wilayah</span>
                            </button>
                            <ul class="dropdown-menu shadow-sm border rounded-3 p-1" style="max-height: 280px; overflow-y: auto;" aria-labelledby="btnFilterKota">
                                <li><a class="dropdown-item py-2 rounded-2 opt-filter-kota active" href="javascript:void(0)" data-value="">Semua Wilayah</a></li>
                                <li><hr class="dropdown-divider my-1"></li>
                                @foreach($options['kota'] ?? [] as $k)
                                    <li><a class="dropdown-item py-2 rounded-2 opt-filter-kota" href="javascript:void(0)" data-value="{{ $k }}">{{ $k }}</a></li>
                                @endforeach
                            </ul>
                        </div>

                        {{-- Dropdown Kelas RS --}}
                        <div class="dropdown">
                            <button class="filter-select-btn d-flex align-items-center gap-2 dropdown-toggle" 
                                    type="button" 
                                    id="btnFilterKelas" 
                                    data-bs-toggle="dropdown" 
                                    aria-expanded="false">
                                <span id="labelFilterKelas">Kelas RS</span>
                            </button>
                            <ul class="dropdown-menu shadow-sm border rounded-3 p-1" aria-labelledby="btnFilterKelas">
                                <li><a class="dropdown-item py-2 rounded-2 opt-filter-kelas active" href="javascript:void(0)" data-value="">Semua Kelas</a></li>
                                <li><hr class="dropdown-divider my-1"></li>
                                <li><a class="dropdown-item py-2 rounded-2 opt-filter-kelas" href="javascript:void(0)" data-value="A">Kelas A (Rujukan Utama)</a></li>
                                <li><a class="dropdown-item py-2 rounded-2 opt-filter-kelas" href="javascript:void(0)" data-value="B">Kelas B</a></li>
                                <li><a class="dropdown-item py-2 rounded-2 opt-filter-kelas" href="javascript:void(0)" data-value="C">Kelas C</a></li>
                                <li><a class="dropdown-item py-2 rounded-2 opt-filter-kelas" href="javascript:void(0)" data-value="D">Kelas D</a></li>
                            </ul>
                        </div>

                        {{-- Dropdown Jaminan / Asuransi --}}
                        <div class="dropdown">
                            <button class="filter-select-btn d-flex align-items-center gap-2 dropdown-toggle" 
                                    type="button" 
                                    id="btnFilterBpjs" 
                                    data-bs-toggle="dropdown" 
                                    aria-expanded="false">
                                <span id="labelFilterBpjs">Jaminan / Asuransi</span>
                            </button>
                            <ul class="dropdown-menu shadow-sm border rounded-3 p-1" aria-labelledby="btnFilterBpjs">
                                <li><a class="dropdown-item py-2 rounded-2 opt-filter-bpjs active" href="javascript:void(0)" data-value="">Semua Jaminan</a></li>
                                <li><hr class="dropdown-divider my-1"></li>
                                <li><a class="dropdown-item py-2 rounded-2 opt-filter-bpjs" href="javascript:void(0)" data-value="1">BPJS Kesehatan</a></li>
                                <li><a class="dropdown-item py-2 rounded-2 opt-filter-bpjs" href="javascript:void(0)" data-value="0">Non - BPJS</a></li>
                            </ul>
                        </div>

                        {{-- Modal Trigger: Fasilitas Penunjang --}}
                        <button type="button" 
                                class="filter-select-btn d-flex align-items-center gap-2" 
                                id="btnOpenFasilitasModal" 
                                data-bs-toggle="modal" 
                                data-bs-target="#modalFasilitas">
                            <span id="labelFilterFasilitas">Fasilitas Penunjang</span>
                            <i class="bi bi-chevron-down fs-7 text-muted"></i>
                        </button>

                        {{-- Modal Trigger: Spesialisasi Medis --}}
                        <button type="button" 
                                class="filter-select-btn d-flex align-items-center gap-2" 
                                id="btnOpenSpesialisasiModal" 
                                data-bs-toggle="modal" 
                                data-bs-target="#modalSpesialisasi">
                            <span id="labelFilterSpesialisasi">Spesialisasi Medis</span>
                            <i class="bi bi-chevron-down fs-7 text-muted"></i>
                        </button>

                    </div>

                    {{-- Popular Search Tags Row --}}
                    <div class="d-flex align-items-center gap-2 pt-2 border-top" style="border-color: #f1f5f9 !important;">
                        <span class="text-muted fw-semibold d-none d-sm-inline" style="font-size: 0.78rem;">Pencarian Populer:</span>
                        <div class="d-flex align-items-center gap-1 flex-wrap">
                            <button type="button" class="popular-search-tag" data-tag="IGD">IGD 24 Jam</button>
                            <button type="button" class="popular-search-tag" data-tag="Kelas A">RSUP Rujukan</button>
                            <button type="button" class="popular-search-tag" data-tag="Ambulans">Ambulan</button>
                            <button type="button" class="popular-search-tag" data-tag="Penyakit Dalam">Dokter Penyakit Dalam</button>
                            <button type="button" class="popular-search-tag" data-tag="Mata">Spesialis Mata</button>
                        </div>
                    </div>

                </div>

            </div>
        </div>

        {{-- Quick Expand/Collapse Handle Bar --}}
        <div class="search-quick-toggle-bar text-center">
            <button type="button" id="btnBottomExpandToggle" class="btn-quick-toggle text-muted" aria-label="Toggle Panel Filter">
                <span id="bottomExpandText">Buka Filter & Opsi Lengkap</span>
                <i class="bi bi-chevron-down ms-1" id="bottomExpandChevron"></i>
            </button>
        </div>

        {{-- Status Bar (Location & Count & Active Badges) --}}
        <div class="search-status-bar bg-light-subtle px-3 px-lg-4 py-1.5 border-top border-bottom">
            <div class="container-fluid px-0 d-flex flex-wrap align-items-center justify-content-between gap-2" style="max-width: 1240px;">
                <div class="d-flex align-items-center gap-1.5 flex-wrap" style="font-size: 0.8rem;">
                    <span class="d-inline-flex align-items-center text-dark bg-white px-2 py-0.5 rounded-pill border shadow-xs" id="searchLocationIndicator">
                        <i class="bi bi-geo-alt-fill text-danger me-1"></i>
                        <span id="searchLocationText" class="fw-medium text-truncate" style="max-width: 200px;">Mendeteksi GPS...</span>
                    </span>
                    <div id="activeFilterTagsContainer" class="d-flex align-items-center gap-1 flex-wrap"></div>
                </div>

                <div>
                    <span class="badge bg-white text-dark border rounded-pill px-2.5 py-1 fw-semibold shadow-xs" id="searchCountBadge" style="font-size: 0.78rem;">
                        Memuat data...
                    </span>
                </div>
            </div>
        </div>

    </header>

    {{-- 2. SCROLLABLE RESULTS SECTION (ONLY THIS BODY SCROLLS) --}}
    <main class="search-scroll-body px-3 px-lg-4" id="searchResultsScrollContainer">
        <div class="container-fluid px-0" style="max-width: 1240px;">
            
            {{-- Loading State --}}
            <div id="searchLoadingState" class="text-center py-5 bg-white rounded-4 border p-4 my-2 d-none">
                <div class="spinner-border text-medigo-primary mb-2" role="status" style="width: 2.2rem; height: 2.2rem;">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div class="text-muted fw-medium" style="font-size: 0.9rem;">Mencari fasilitas kesehatan sesuai filter Anda...</div>
            </div>

            {{-- Results Grid --}}
            <div id="searchResultsGrid" class="row g-3 g-lg-4"></div>

            {{-- Empty State --}}
            <div id="searchEmptyState" class="text-center py-5 bg-white rounded-4 border p-4 my-2 d-none">
                <div class="mb-3">
                    <div class="bg-medigo-subtle d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 70px; height: 70px;">
                        <i class="bi bi-hospital fs-1 text-medigo-primary"></i>
                    </div>
                </div>
                <h5 class="fw-bold text-dark mb-1">Tidak Ada Fasilitas Kesehatan Ditemukan</h5>
                <p class="text-muted mb-3 mx-auto" style="max-width: 420px; font-size: 0.9rem;">
                    Coba sesuaikan kata kunci pencarian Anda atau gunakan tombol Reset Filter untuk melihat semua fasilitas kesehatan.
                </p>
                <button type="button" class="btn btn-medigo-primary rounded-pill px-4 py-2 fw-semibold text-white btn-reset-from-empty">
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

                {{-- Rekomendasi Populer --}}
                <div class="mb-3">
                    <div class="text-muted fw-bold mb-2" style="font-size: 0.78rem; text-transform: uppercase;">Paling Sering Dicari:</div>
                    <div class="d-flex flex-wrap gap-1">
                        @foreach(['IGD', 'Laboratorium', 'Radiologi', 'ICU', 'Ambulans', 'Farmasi', 'Rawat Inap', 'USG', 'CT Scan'] as $popFas)
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill btn-quick-select-fasilitas" data-name="{{ $popFas }}" style="font-size: 0.8rem;">
                                + {{ $popFas }}
                            </button>
                        @endforeach
                    </div>
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

                {{-- Rekomendasi Populer --}}
                <div class="mb-3">
                    <div class="text-muted fw-bold mb-2" style="font-size: 0.78rem; text-transform: uppercase;">Paling Sering Dicari:</div>
                    <div class="d-flex flex-wrap gap-1">
                        @foreach(['Anak', 'Penyakit Dalam', 'Bedah', 'Kandungan', 'Jantung', 'Mata', 'Saraf', 'Gigi', 'THT', 'Paru'] as $popSpes)
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill btn-quick-select-spesialisasi" data-name="{{ $popSpes }}" style="font-size: 0.8rem;">
                                + {{ $popSpes }}
                            </button>
                        @endforeach
                    </div>
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
