@extends('layouts.app')

@section('title', 'MediGO - Peta Faskes')

@push('styles')
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" />
@endpush

@section('content')
<div class="map-view-section py-lg-4 py-0">
    <div class="container-fluid px-lg-4 px-0 h-100">
        <div class="row g-4 h-100 m-0">
            <!-- Left Column (Desktop Only): Search, Radius Tabs & Facility Cards List -->
            <div class="col-lg-4 col-md-5 d-none d-lg-block">
                <div class="facility-search-container card border shadow-sm">
                    <div class="card-body p-3 p-lg-4">
                        <!-- Heading & Global Counter -->
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold m-0 text-dark facility-search-heading">Cari Fasilitas</h5>
                            <span class="badge badge-medigo-primary rounded-pill px-2 py-1 d-none" style="font-size: 0.72rem;" id="totalFaskesBadge">
                                {{ count($faskesList) }} Faskes
                            </span>
                        </div>

                        <!-- Modern Horizontal Tab Navigator (Segmented Pill) -->
                        <div class="map-horizontal-tabs mb-3" id="mapTabsNav" role="tablist">
                            <button class="nav-link active map-horiz-tab-btn" 
                                    id="tab-faskes-btn" 
                                    data-bs-toggle="pill" 
                                    data-bs-target="#tab-faskes-pane" 
                                    type="button" 
                                    role="tab" 
                                    aria-controls="tab-faskes-pane" 
                                    aria-selected="true">
                                <i class="bi bi-hospital me-1"></i>
                                <span>Faskes</span>
                                <span class="badge badge-tab-pill ms-1 d-none" id="tabFaskesCountBadge">
                                    {{ count($faskesList) }}
                                </span>
                            </button>

                            <button class="nav-link map-horiz-tab-btn" 
                                    id="tab-radius-btn" 
                                    data-bs-toggle="pill" 
                                    data-bs-target="#tab-radius-pane" 
                                    type="button" 
                                    role="tab" 
                                    aria-controls="tab-radius-pane" 
                                    aria-selected="false">
                                <i class="bi bi-broadcast me-1"></i>
                                <span>GPS / Radius</span>
                            </button>
                        </div>

                        <!-- Tab Content Panes -->
                        <div class="tab-content" id="mapLeftTabsContent">
                            <!-- Tab 1: Daftar Faskes (Search Bar & Cards directly on top) -->
                            <div class="tab-pane fade show active" id="tab-faskes-pane" role="tabpanel" aria-labelledby="tab-faskes-btn">
                                <!-- Search Component -->
                                <x-map.search-bar />

                                @if(!empty($errorMessage))
                                    <div class="alert alert-warning alert-dismissible fade show d-flex align-items-center gap-2 py-2 px-3 small rounded-3 my-2 shadow-sm" role="alert">
                                        <i class="bi bi-exclamation-triangle-fill text-warning flex-shrink-0 fs-6"></i>
                                        <div class="flex-grow-1" style="font-size: 0.8rem;">{{ $errorMessage }}</div>
                                        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                @endif

                                <!-- Facility Cards Loading Spinner (Desktop Sidebar - Visible by Default) -->
                                <div class="text-center py-5 bg-light rounded-3 my-2" id="mapCardsLoadingState">
                                    <div class="spinner-border text-medigo-primary mb-2" role="status" style="width: 2rem; height: 2rem;">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <div class="small fw-semibold text-dark">Memperbarui data faskes...</div>
                                    <div class="text-muted" style="font-size: 0.72rem;">Menyesuaikan jarak & lokasi GPS</div>
                                </div>

                                <!-- Facility Cards Stack (Hidden by default until JS initializes) -->
                                <div class="facility-cards-list d-flex flex-column gap-3 d-none" id="facilityCardsList">
                                    @foreach($faskesList as $faskes)
                                        <x-map.facility-card :faskes="$faskes" :is-first="$loop->first" />
                                    @endforeach

                                    <!-- Empty State Component -->
                                    <x-map.empty-state />
                                </div>
                            </div>

                            <!-- Tab 2: Pengaturan Lokasi & Radius Slider -->
                            <div class="tab-pane fade" id="tab-radius-pane" role="tabpanel" aria-labelledby="tab-radius-btn">
                                <x-map.radius-panel />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column / Mobile Fullscreen: Interactive Leaflet Map Display -->
            <div class="col-lg-8 col-12 p-0 px-lg-2 h-100">
                <div class="map-display-wrapper card border-0 border-lg shadow-sm position-relative">
                    <!-- Mobile Top Floating Search & Filter Chips (Matching Screenshot) -->
                    <x-map.mobile-top-bar />

                    <!-- Map Loading Overlay Spinner (Visible by Default covering map until initialized) -->
                    <div id="mapLoadingOverlay" class="map-loading-overlay">
                        <div class="spinner-border text-medigo-primary mb-2" role="status" style="width: 2.4rem; height: 2.4rem;">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <span class="small fw-bold text-dark">Mendeteksi lokasi & menyiapkan peta...</span>
                        <span class="text-muted" style="font-size: 0.72rem;">Memuat rute dan faskes terdekat</span>
                    </div>

                    <!-- Leaflet Canvas -->
                    <div id="facilityLeafletMap" class="w-100 h-100"></div>

                    <!-- Floating Map Controls (Zoom & Geolocation) -->
                    <x-map.map-controls />

                    <!-- Map Category Legend Box (Desktop Only) -->
                    <div class="d-none d-md-block">
                        <x-map.legend />
                    </div>

                    <!-- Mobile Bottom Sheet / Scrollable Cards Carousel (Matching Screenshot) -->
                    <x-map.mobile-bottom-sheet :faskes-list="$faskesList" :error-message="$errorMessage" />
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <!-- Leaflet JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>

    <!-- Pass Backend Faskes Data to Frontend JS Module -->
    <script>
        window.faskesInitialData = @json($faskesList);
        window.mapErrorMessage = @json($errorMessage ?? null);
    </script>

    <!-- Modular Map JavaScript -->
    @vite(['resources/js/map.js'])
@endpush