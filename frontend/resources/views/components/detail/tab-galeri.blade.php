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
                    <a href="{{ $foto['url'] }}" target="_blank" rel="noopener"
                       class="mg-gallery-item {{ $index >= $perPage ? 'd-none' : '' }}"
                       data-gallery-index="{{ $index }}">
                        <img src="{{ $foto['url'] }}" alt="{{ $foto['keterangan'] ?: 'Foto ' . $faskesDisplayName }}" loading="lazy">
                    </a>
                @endforeach
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
