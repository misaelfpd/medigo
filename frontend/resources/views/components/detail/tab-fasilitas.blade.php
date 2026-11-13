@props(['faskes' => [], 'faskesDisplayName' => null])

@php
    $faskesDisplayName = $faskesDisplayName ?? ($faskes['nama_faskes'] ?? 'Fasilitas Kesehatan');
    $fasilitasList = $faskes['fasilitas'] ?? [];
@endphp

<div class="tab-pane fade" id="pane-fasilitas" role="tabpanel" aria-labelledby="tab-fasilitas-btn">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h6 class="fw-bold text-dark m-0" style="font-size: 1rem;">Daftar Fasilitas Medis Unggulan</h6>
        @if(count($fasilitasList) > 0)
            <span class="badge bg-light text-secondary border rounded-pill px-2 py-1" style="font-size: 0.72rem;">
                {{ count($fasilitasList) }} Fasilitas
            </span>
        @endif
    </div>

    @if(count($fasilitasList) > 0)
        <div class="faskes-scrollable-list">
            <div class="row row-cols-1 row-cols-md-2 g-2">
                @foreach($fasilitasList as $item)
                    <div class="col">
                        <div class="faskes-feature-card d-flex align-items-center justify-content-between gap-2">
                            <h6 class="fw-semibold text-dark m-0" style="font-size: 0.85rem;">
                                {{ $item['title'] }}
                            </h6>
                            @if(!empty($item['badge']))
                                <span class="badge {{ $item['badge_class'] ?? 'bg-primary-subtle text-primary' }} rounded-pill px-2 py-0-5 flex-shrink-0" style="font-size: 0.66rem; font-weight: 600;">
                                    {{ $item['badge'] }}
                                </span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <div class="text-center py-5 px-3 bg-light rounded-4 border">
            <p class="text-muted mb-0" style="font-size: 0.85rem;">
                Belum ada rincian fasilitas medis yang tercatat untuk {{ $faskesDisplayName }}.
            </p>
        </div>
    @endif
</div>