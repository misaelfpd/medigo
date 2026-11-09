<div class="d-flex justify-content-between align-items-center {{ $isMobile ? 'mb-2' : 'mb-3' }}">
    <h4 class="fw-bold text-dark m-0" style="font-size: {{ $isMobile ? '1.1rem' : '1.35rem' }};">Rumah Sakit Tersimpan</h4>
    <a href="/faskes" class="text-medigo-primary fw-semibold text-decoration-none" style="font-size: 0.85rem;">Lihat Semua</a>
</div>

<div class="d-flex flex-column gap-3 mb-4">
    @forelse($savedHospitals as $hospital)
        <div class="card border rounded-4 bg-white shadow-2xs overflow-hidden saved-hospital-card p-3">
            <div class="d-flex gap-3 align-items-center">
                <!-- Thumbnail Photo -->
                <div class="saved-hospital-thumb rounded-3 overflow-hidden flex-shrink-0 position-relative" style="width: {{ $isMobile ? '60px' : '80px' }}; height: {{ $isMobile ? '60px' : '80px' }};">
                    <img src="{{ asset($hospital['foto']) }}" alt="{{ $hospital['nama_faskes'] }}" class="w-100 h-100 object-fit-cover">
                </div>

                <!-- Content -->
                <div class="flex-grow-1 min-w-0">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <h6 class="fw-bold text-dark mb-0 text-truncate" style="font-size: {{ $isMobile ? '0.9rem' : '0.98rem' }};">
                            <a href="{{ $hospital['detail_url'] }}" class="text-dark text-decoration-none hover-primary">
                                {{ $hospital['nama_faskes'] }}
                            </a>
                        </h6>
                        <!-- Bookmark Icon Button -->
                        <button type="button" class="btn btn-link p-0 text-medigo-primary ms-2 flex-shrink-0" title="Tersimpan">
                            <i class="bi bi-bookmark-fill {{ $isMobile ? 'fs-6' : 'fs-5' }}" style="color: #002b5e;"></i>
                        </button>
                    </div>

                    <!-- Badges -->
                    <div class="d-flex align-items-center gap-1 mb-2 flex-wrap">
                        @if(!empty($hospital['kelas']))
                            <span class="badge bg-light text-dark border px-2 py-1" style="font-size: 0.7rem;">Kelas {{ $hospital['kelas'] }}</span>
                        @endif
                        @if($hospital['bpjs_support'])
                            <span class="badge bg-success-subtle text-success border-success-subtle px-2 py-1" style="font-size: 0.7rem;">
                                <i class="bi bi-check2"></i> BPJS
                            </span>
                        @endif
                    </div>

                    <!-- Bottom Row: Distance & Open Status -->
                    <div class="d-flex align-items-center justify-content-between flex-wrap text-muted" style="font-size: {{ $isMobile ? '0.72rem' : '0.78rem' }};">
                        <div class="d-flex align-items-center gap-1">
                            <i class="bi bi-geo-alt"></i>
                            <span>Berjarak {{ $hospital['distance'] }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="card border rounded-4 bg-white shadow-2xs p-3 text-center">
            <p class="text-muted small mb-0">Belum ada fasilitas kesehatan yang tersimpan.</p>
        </div>
    @endforelse
</div>