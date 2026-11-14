@props(['faskesList', 'errorMessage' => null])

<!-- Mobile Bottom Sheet / Draggable Expandable Cards Drawer -->
<div class="mobile-bottom-sheet d-lg-none" id="mobileBottomSheet">
    <!-- Drag Handle Area (Always at the very top of sheet) -->
    <div class="sheet-drag-area" id="sheetDragArea" title="Geser ke atas untuk melihat daftar faskes">
        <div class="sheet-drag-handle"></div>
    </div>

    <!-- Header When Expanded (Only visible in expanded mode) -->
    <div class="sheet-expanded-header" id="sheetExpandedHeader">
        <div class="d-flex align-items-center gap-2">
            <h6 class="fw-bold text-dark m-0 sheet-title">Daftar Faskes Terdekat</h6>
            <span class="badge badge-medigo-primary rounded-pill px-2 py-0-5 d-none" id="mobileSheetCountBadge" style="font-size: 0.68rem;">
                {{ count($faskesList ?? []) }}
            </span>
        </div>
        <!-- Close (X) Button -->
        <button type="button" class="btn btn-sheet-close" id="btnCloseBottomSheet" aria-label="Tutup">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    @if(!empty($errorMessage))
        <div class="alert alert-warning alert-dismissible fade show d-flex align-items-center gap-2 py-2 px-3 small rounded-3 m-3 mb-1 shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill text-warning flex-shrink-0 fs-6"></i>
            <div class="flex-grow-1" style="font-size: 0.75rem;">{{ $errorMessage }}</div>
            <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Mobile Cards Loading Spinner (Active by Default) -->
    <div class="text-center py-4" id="mobileCardsLoadingState">
        <div class="spinner-border spinner-border-sm text-medigo-primary mb-1" role="status" style="width: 1.6rem; height: 1.6rem;"></div>
        <div class="small fw-semibold text-dark" style="font-size: 0.78rem;">Menghitung faskes terdekat...</div>
    </div>

    <!-- Empty State for Mobile When No Facilities Match -->
    <div class="text-center py-3 px-2 d-none" id="mobileFacilityEmptyState">
        <div class="mb-2">
            <i class="bi bi-geo-alt-slash text-muted" style="font-size: 1.8rem;"></i>
        </div>
        <h6 class="fw-bold text-dark mb-1" style="font-size: 0.88rem;">Tidak ada fasilitas kesehatan terdekat dalam radius Anda</h6>
        <p class="text-muted mb-3" style="font-size: 0.74rem;">Coba perbesar jangkauan radius atau ubah filter faskes.</p>
        <button type="button" class="btn btn-sm btn-medigo-primary rounded-pill px-3 py-1 fw-semibold" id="btnMobileExpandRadius" style="font-size: 0.75rem;">
            <i class="bi bi-arrows-angle-expand me-1"></i> Perbesar Radius ke 10 Km
        </button>
    </div>

    <!-- Scrollable Cards Container (Snap Scroll in peek mode, Vertical list in expanded mode) -->
    <div class="mobile-cards-scroll-container d-none" id="mobileCardsContainer">
        @foreach($faskesList as $faskes)
            @php
                $typeSlug = $faskes['type_slug'] ?? 'hospital';
                $faskesName = trim($faskes['name'] ?? 'Fasilitas Kesehatan');
                if (($typeSlug === 'puskesmas' || str_contains(strtolower($faskes['type'] ?? ''), 'puskesmas')) && !preg_match('/^(puskesmas|pkm)\b/i', $faskesName)) {
                    $faskesName = 'Puskesmas ' . $faskesName;
                }

                // Ikon & warna fallback sesuai jenis faskes (apotek, lab, optik, dokter, dll.)
                $visualKey = \App\Support\FaskesVisual::key(($faskes['type'] ?? ''), $faskesName);
                $typeIcon = \App\Support\FaskesVisual::TYPES[$visualKey][0];
                $detailUrl = (!empty($faskes['detail_url']) && $faskes['detail_url'] !== '#') ? $faskes['detail_url'] : url('/faskes/' . $faskes['id']);
            @endphp
            <div class="mobile-facility-sheet-card {{ $loop->first ? 'is-active' : '' }}" 
                 id="mobile-card-{{ $faskes['id'] }}"
                 data-id="{{ $faskes['id'] }}"
                 data-type="{{ $typeSlug }}"
                 data-bpjs="{{ $faskes['bpjs_support'] ? '1' : '0' }}"
                 data-lat="{{ $faskes['lat'] }}"
                 data-lng="{{ $faskes['lng'] }}">
                <div class="d-flex gap-3 align-items-center mb-2">
                    <!-- Type Colored Logo Box -->
                    <div class="mobile-sheet-thumb-wrap position-relative flex-shrink-0 faskes-avatar-map faskes-avatar-{{ $typeSlug }} faskes-visual-{{ $visualKey }}">
                        <i class="bi {{ $typeIcon }} text-white fs-4"></i>
                    </div>

                    <!-- Info Content -->
                    <div class="mobile-sheet-info flex-grow-1 min-w-0">
                        <h6 class="mobile-sheet-title fw-bold text-dark text-truncate mb-1" title="{{ $faskesName }}">
                            {{ $faskesName }}
                        </h6>
                        <p class="mobile-sheet-address text-muted text-truncate mb-1" title="{{ $faskes['address'] }}">
                            {{ $faskes['address'] }}
                        </p>
                        <div class="d-flex align-items-center gap-1 flex-wrap">
                            @if(!empty($faskes['type']))
                                <span class="badge-custom badge-type-pill" style="background-color: #f1f5f9; color: #475569; font-size: 0.65rem;">
                                    {{ $faskes['type'] }}
                                </span>
                            @endif
                            @if($faskes['hospital_class'])
                                <span class="badge-custom badge-kelas">Kelas {{ $faskes['hospital_class'] }}</span>
                            @endif
                            <span class="badge-custom {{ $faskes['bpjs_support'] ? 'badge-bpjs' : 'badge-non-bpjs' }}">
                                {{ $faskes['bpjs_support'] ? 'BPJS' : 'Non-BPJS' }}
                            </span>
                            <span class="mobile-sheet-distance ms-auto text-dark fw-bold" id="mobile-dist-{{ $faskes['id'] }}">
                                <i class="bi bi-sign-turn-right text-primary me-1"></i>{{ !empty($faskes['distance']) ? $faskes['distance'] : '-' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="d-flex gap-2 mt-2">
                    <a href="{{ $faskes['navigasi_url'] }}" id="mobile-nav-{{ $faskes['id'] }}" target="_blank" class="btn btn-medigo-navigasi flex-fill text-white text-decoration-none d-flex align-items-center justify-content-center gap-1">
                        <i class="bi bi-sign-turn-right"></i> Navigasi
                    </a>
                    <a href="{{ $detailUrl }}" class="btn btn-medigo-detail flex-fill text-decoration-none d-flex align-items-center justify-content-center gap-1">
                        <i class="bi bi-info-circle"></i> Detail
                    </a>
                </div>
            </div>
        @endforeach
    </div>
</div>
