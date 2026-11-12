@props([
    'faskes' => [],
    'reviews' => [],
    'hasMoreComments' => false,
])

<div class="reviews-list-section">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold text-dark m-0" style="font-size: 1rem;">Ulasan Terbaru</h6>
        <span class="badge bg-light text-muted border rounded-pill px-2 py-1 d-none" id="desktopReviewsCountBadge" style="font-size: 0.72rem;">
            <span class="total-reviews-num">{{ $faskes['total_reviews'] ?? count($reviews) }}</span> Ulasan
        </span>
    </div>

    {{-- Desktop Reviews Container --}}
    <div class="desktop-reviews-list d-none d-lg-flex flex-column gap-3" id="desktopReviewsContainer">
        @forelse($reviews as $review)
            <div class="desktop-review-card {{ !$loop->last ? 'border-bottom pb-3' : '' }} {{ !empty($review['is_my_comment']) ? 'p-3 bg-light rounded-3' : '' }}"
                 id="desktop-review-{{ $review['id'] }}">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <div class="d-flex align-items-center gap-2">
                        <span class="fw-bold text-dark" style="font-size: 0.88rem;">{{ $review['reviewer_name'] }}</span>
                        @if(!empty($review['is_my_comment']))
                            <span class="badge bg-medigo-primary text-white rounded-pill px-2 py-0" style="font-size: 0.65rem;">Anda</span>
                        @endif
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted" style="font-size: 0.74rem;">{{ $review['created_at_human'] ?? 'Baru saja' }}</span>
                        @if(empty($review['is_my_comment']))
                            <button type="button"
                                    class="btn btn-sm btn-link text-muted p-0 text-decoration-none btn-trigger-report"
                                    data-comment-id="{{ $review['id'] }}"
                                    data-reviewer="{{ $review['reviewer_name'] }}"
                                    title="Laporkan ulasan ini">
                                <i class="bi bi-flag text-danger opacity-75" style="font-size: 0.76rem;"></i>
                            </button>
                        @endif
                    </div>
                </div>

                <div class="d-flex text-warning gap-1 mb-2" style="font-size: 0.75rem;">
                    @for($s = 1; $s <= 5; $s++)
                        <i class="bi bi-star{{ $s <= $review['rating'] ? '-fill' : '' }}"></i>
                    @endfor
                </div>

                <p class="text-secondary mb-0" style="font-size: 0.83rem; line-height: 1.55;">
                    {{ $review['body'] ?? $review['comment'] ?? '' }}
                </p>
            </div>
        @empty
            <div class="text-center py-4 text-muted" id="desktopEmptyReviewsNotice" style="font-size: 0.84rem;">
                Belum ada ulasan untuk fasilitas ini.<br>Jadilah yang pertama memberikan ulasan!
            </div>
        @endforelse
    </div>

    {{-- Desktop Load More --}}
    <div class="mt-3 pt-2 border-top d-none d-lg-block {{ $hasMoreComments ? '' : 'd-lg-none' }}" id="desktopLoadMoreWrapper">
        <button type="button" class="btn btn-outline-secondary w-100 rounded-pill py-2 fw-semibold d-flex align-items-center justify-content-center gap-2" id="btnDesktopLoadMore" style="font-size: 0.8rem;">
            <span class="spinner-border spinner-border-sm d-none" id="desktopLoadMoreSpinner"></span>
            <span>Lihat Komentar Lainnya</span>
        </button>
    </div>

    {{-- Mobile Reviews Container --}}
    <div class="d-block d-lg-none">
        <div class="mobile-reviews-list d-flex flex-column gap-3" id="mobileReviewsContainer">
            @forelse($reviews as $review)
                <div class="mobile-review-item pb-3 {{ !$loop->last ? 'border-bottom' : '' }} {{ !empty($review['is_my_comment']) ? 'p-2 bg-light rounded-3' : '' }}"
                     id="mobile-review-{{ $review['id'] }}">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <div class="d-flex align-items-center gap-1">
                            <span class="fw-bold text-dark" style="font-size: 0.86rem;">{{ $review['reviewer_name'] }}</span>
                            @if(!empty($review['is_my_comment']))
                                <span class="badge bg-medigo-primary text-white rounded-pill px-2 py-0" style="font-size: 0.64rem;">Anda</span>
                            @endif
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="text-muted" style="font-size: 0.72rem;">{{ $review['created_at_human'] ?? 'Baru saja' }}</span>
                            @if(empty($review['is_my_comment']))
                                <button type="button"
                                        class="btn btn-sm btn-link text-muted p-0 text-decoration-none btn-trigger-report"
                                        data-comment-id="{{ $review['id'] }}"
                                        data-reviewer="{{ $review['reviewer_name'] }}"
                                        title="Laporkan ulasan ini">
                                    <i class="bi bi-flag text-danger opacity-75"></i>
                                </button>
                            @endif
                        </div>
                    </div>
                    <div class="d-flex text-warning gap-1 mb-1" style="font-size: 0.72rem;">
                        @for($s = 1; $s <= 5; $s++)
                            <i class="bi bi-star{{ $s <= $review['rating'] ? '-fill' : '' }}"></i>
                        @endfor
                    </div>
                    <p class="text-secondary mb-0" style="font-size: 0.82rem; line-height: 1.5;">
                        {{ $review['body'] ?? $review['comment'] ?? '' }}
                    </p>
                </div>
            @empty
                <div class="text-center py-3 text-muted" id="mobileEmptyReviewsNotice" style="font-size: 0.82rem;">
                    Belum ada ulasan untuk faskes ini.
                </div>
            @endforelse
        </div>

        <div class="mt-3 {{ $hasMoreComments ? '' : 'd-none' }}" id="mobileLoadMoreWrapper">
            <button type="button" class="btn btn-outline-secondary w-100 rounded-pill py-2 fw-semibold d-flex align-items-center justify-content-center gap-2" id="btnMobileLoadMore" style="font-size: 0.78rem;">
                <span class="spinner-border spinner-border-sm d-none" id="mobileLoadMoreSpinner"></span>
                <span>Lihat Komentar Lainnya</span>
            </button>
        </div>
    </div>
</div>