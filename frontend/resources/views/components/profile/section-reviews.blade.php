<div class="d-flex justify-content-between align-items-center {{ $isMobile ? 'mb-2 mt-4' : 'mb-3' }}">
    <div>
        <h4 class="fw-bold text-dark m-0" style="font-size: {{ $isMobile ? '0.95rem' : '1.35rem' }};">Ulasan Faskes</h4>
        @if(!$isMobile)<p class="text-muted small m-0">Riwayat ulasan dan rating fasilitas kesehatan yang Anda berikan</p>@endif
    </div>
    <span class="badge bg-light text-medigo-primary border {{ $isMobile ? 'px-2 py-1' : 'px-3 py-1-5' }} rounded-pill fw-bold">
        {{ count($userReviews) }} Ulasan
    </span>
</div>

<!-- Container Scrollable -->
<div id="profileReviewsContainer{{ $isMobile ? 'Mobile' : 'Desktop' }}" class="d-flex flex-column {{ $isMobile ? 'gap-2-5' : 'gap-3' }} mb-3 scrollable-section" style="max-height: 420px; overflow-y: auto; padding-right: 4px;">
    @forelse($userReviews as $review)
        <div class="card border rounded-4 bg-white shadow-2xs p-3 profile-review-item position-relative" id="user-review-{{ $review['id'] }}">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div class="d-flex align-items-center gap-2">
                    <div>
                        <h6 class="fw-bold text-dark mb-0" style="font-size: {{ $isMobile ? '0.9rem' : '0.95rem' }};">
                            <a href="{{ $review['faskes_url'] ?? url('/faskes/' . $review['faskes_id']) }}" class="text-dark text-decoration-none">{{ $review['nama_faskes'] ?? 'Fasilitas Kesehatan' }}</a>
                        </h6>
                        <span class="text-muted" style="font-size: {{ $isMobile ? '0.72rem' : '0.76rem' }};">{{ $review['created_at_human'] ?? 'Baru saja' }}</span>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-1">
                    <i class="bi bi-star-fill text-warning" style="font-size: 0.85rem;"></i>
                    <span class="ms-1 fw-bold text-dark small">{{ $review['rating'] ?? 5 }}.0</span>
                </div>
            </div>
            
            @if(!empty($review['body']))
                <p class="text-dark mb-2 mt-1" style="font-size: {{ $isMobile ? '0.82rem' : '0.88rem' }}; line-height: 1.4;">{{ $review['body'] }}</p>
            @endif

            <div class="d-flex justify-content-between align-items-center mt-2">
                <a href="{{ url('/faskes/' . ($review['faskes_id'] ?? 1)) }}" class="btn btn-sm btn-light rounded-pill px-3 text-medigo-primary fw-semibold" style="font-size: 0.74rem;">
                    Lihat Faskes <i class="bi bi-chevron-right ms-1"></i>
                </a>
                
                <!-- Tombol Hapus Review Faskes -->
                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-2 py-0-5 btn-delete-faskes-review" data-id="{{ $review['id'] }}" style="font-size: 0.72rem;" title="Hapus Ulasan">
                    <i class="bi bi-trash"></i> Hapus
                </button>
            </div>
        </div>
    @empty
        <div class="card border rounded-4 bg-white shadow-2xs p-3 text-center">
            <p class="text-muted small mb-0">Belum ada ulasan.</p>
        </div>
    @endforelse
</div>

@if($hasMoreReviews)
    <div class="text-center {{ $isMobile ? 'mb-3' : 'mb-4' }}" id="loadMoreProfileReviewsWrapper{{ $isMobile ? 'Mobile' : 'Desktop' }}">
        <button type="button" class="btn btn-outline-medigo-primary rounded-pill px-4 py-2 fw-semibold btn-load-more-profile-reviews" data-page="1" data-ismobile="{{ $isMobile ? 'true' : 'false' }}">
            <span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
            <i class="bi bi-arrow-clockwise me-1"></i> Lihat Komentar Lainnya
        </button>
    </div>
@endif