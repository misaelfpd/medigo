@props(['faskes' => []])

@php
    $faskesDisplayName = $faskes['nama_faskes'] ?? 'Fasilitas Kesehatan';
    $galeri = collect($faskes['galeri'] ?? [])->filter(fn ($f) => !empty($f['url']))->values();
    $perPage = 4;
    $totalPages = max(1, (int) ceil($galeri->count() / $perPage));
@endphp

<div class="tab-pane fade" id="pane-galeri" role="tabpanel" aria-labelledby="tab-galeri-btn">
    <div class="mg-card mg-gallery" data-per-page="{{ $perPage }}">
        <div class="d-flex align-items-center justify-content-between mb-3 gap-2">
            <h6 class="mg-card-title m-0">Galeri {{ $faskesDisplayName }}</h6>
            @if($totalPages > 1)
                <div class="d-flex align-items-center gap-2 mg-gallery-nav">
                    <button type="button" class="mg-round-btn" data-gallery-prev aria-label="Foto sebelumnya">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    <span class="small text-secondary"><span data-gallery-page>1</span>/{{ $totalPages }}</span>
                    <button type="button" class="mg-round-btn" data-gallery-next aria-label="Foto berikutnya">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
            @endif
        </div>

        @if($galeri->isNotEmpty())
            <div class="mg-gallery-grid">
                @foreach($galeri as $index => $foto)
                    <a href="javascript:void(0)" role="button"
                       class="mg-gallery-item {{ $index >= $perPage ? 'd-none' : '' }}"
                       data-gallery-index="{{ $index }}"
                       data-gallery-src="{{ $foto['url'] }}"
                       data-gallery-caption="{{ $foto['keterangan'] ?: 'Foto ' . $faskesDisplayName }}"
                       data-bs-toggle="modal"
                       data-bs-target="#galleryImageModal"
                       aria-label="Lihat foto {{ $index + 1 }}">
                        <img src="{{ $foto['url'] }}" alt="{{ $foto['keterangan'] ?: 'Foto ' . $faskesDisplayName }}" loading="lazy">
                    </a>
                @endforeach
            </div>

            {{-- Modal Pratinjau Foto Galeri --}}
            <div class="modal fade" id="galleryImageModal" tabindex="-1" aria-labelledby="galleryImageModalCaption" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-xl">
                    <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden bg-dark text-white">
                        <div class="modal-header border-0 pb-0 pt-3 px-3 px-md-4 d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-2 text-truncate pe-3">
                                <i class="bi bi-image text-white-50"></i>
                                <span class="small fw-semibold text-white-50 text-truncate" id="galleryImageModalCaption">Foto Galeri</span>
                            </div>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
                        </div>
                        <div class="modal-body position-relative text-center p-2 p-md-4 d-flex align-items-center justify-content-center" style="min-height: 320px;">
                            @if($galeri->count() > 1)
                                <button type="button" class="btn btn-dark bg-black bg-opacity-50 text-white rounded-circle position-absolute start-0 top-50 translate-middle-y ms-2 ms-md-4 d-flex align-items-center justify-content-center border-0 shadow" id="galleryModalPrev" style="width: 44px; height: 44px; z-index: 10;" aria-label="Foto Sebelumnya">
                                    <i class="bi bi-chevron-left fs-5"></i>
                                </button>
                            @endif

                            <img id="galleryModalImage" src="" alt="Pratinjau Foto" class="img-fluid rounded-3 shadow" style="max-height: 80vh; max-width: 100%; object-fit: contain; transition: opacity .2s ease;">

                            @if($galeri->count() > 1)
                                <button type="button" class="btn btn-dark bg-black bg-opacity-50 text-white rounded-circle position-absolute end-0 top-50 translate-middle-y me-2 me-md-4 d-flex align-items-center justify-content-center border-0 shadow" id="galleryModalNext" style="width: 44px; height: 44px; z-index: 10;" aria-label="Foto Berikutnya">
                                    <i class="bi bi-chevron-right fs-5"></i>
                                </button>
                            @endif
                        </div>
                        @if($galeri->count() > 1)
                            <div class="modal-footer border-0 pt-0 pb-3 px-4 justify-content-center">
                                <span class="small text-white-50"><span id="galleryModalCurrentIdx">1</span> / {{ $galeri->count() }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @else
            <div class="mg-empty-state">
                <div class="mg-empty-icon"><i class="bi bi-images"></i></div>
                <div class="fw-semibold text-dark">Belum Ada Foto Galeri</div>
                <div class="small text-secondary">Foto fasilitas ini belum ditambahkan oleh admin.</div>
            </div>
        @endif
    </div>
</div>

@once
    @push('scripts')
        @vite('resources/js/pages/detail-gallery.js')
    @endpush
@endonce
