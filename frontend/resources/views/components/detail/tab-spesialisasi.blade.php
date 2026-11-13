@props(['faskes' => [], 'faskesDisplayName' => null])

@php
    $faskesDisplayName = $faskesDisplayName ?? ($faskes['nama_faskes'] ?? 'Fasilitas Kesehatan');
    $spesialisasiList = $faskes['spesialisasi'] ?? [];
@endphp

<div class="tab-pane fade" id="pane-spesialisasi" role="tabpanel" aria-labelledby="tab-spesialisasi-btn">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h6 class="fw-bold text-dark m-0" style="font-size: 1rem;">Daftar Spesialis Medis Unggulan</h6>
        @if(count($spesialisasiList) > 0)
            <span class="badge bg-light text-secondary border rounded-pill px-2 py-1" style="font-size: 0.72rem;">
                {{ count($spesialisasiList) }} Spesialisasi
            </span>
        @endif
    </div>

    @if(count($spesialisasiList) > 0)
        <div class="faskes-scrollable-list">
            <div class="row row-cols-1 row-cols-md-2 g-2">
                @foreach($spesialisasiList as $spec)
                    <div class="col">
                        <div class="faskes-feature-card d-flex align-items-center">
                            <h6 class="fw-semibold text-dark m-0" style="font-size: 0.85rem;">
                                {{ $spec['title'] }}
                            </h6>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <div class="text-center py-5 px-3 bg-light rounded-4 border">
            <p class="text-muted mb-0" style="font-size: 0.85rem;">
                Belum ada rincian spesialisasi dokter yang tercatat untuk {{ $faskesDisplayName }}.
            </p>
        </div>
    @endif
</div>