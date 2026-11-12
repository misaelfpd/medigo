@props([
    'faskes' => [],
    'ratingPercentages' => [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0],
    'myComment' => null,
])

<div class="rating-summary-section">
    <h6 class="fw-bold text-dark mb-3" style="font-size: 1rem;">Ringkasan Rating</h6>

    {{-- Skor Besar & Progress Bar 5-1 --}}
    <div class="row align-items-center g-2 mb-2">
        <div class="col-5 text-center border-end pe-2">
            <div class="rating-huge-score fw-bold mb-1" id="ratingHugeScoreText">
                {{ !empty($faskes['avg_rating']) ? $faskes['avg_rating'] : '-' }}
            </div>
            <div class="text-muted" style="font-size: 0.74rem;" id="ratingSubtextDisplay">
                @if(!empty($faskes['avg_rating']))
                    <span>dari 5</span>
                @else
                    <span>Belum dirating</span>
                @endif
            </div>
        </div>

        <div class="col-7 ps-3">
            <div class="rating-breakdown-list d-flex flex-column gap-1">
                @php
                    $percentages = $ratingPercentages ?? [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
                @endphp
                @foreach([5, 4, 3, 2, 1] as $star)
                    @php $pct = $percentages[$star] ?? 0; @endphp
                    <div class="d-flex align-items-center gap-2" style="font-size: 0.74rem;">
                        <span class="fw-semibold text-secondary" style="width: 10px;">{{ $star }}</span>
                        <div class="progress flex-grow-1" style="height: 6px; background-color: #e2e8f0; border-radius: 50px;">
                            <div class="progress-bar bg-warning rating-bar-star-{{ $star }}"
                                 id="ratingBar{{ $star }}"
                                 role="progressbar"
                                 style="width: {{ $pct }}%; border-radius: 50px;"
                                 aria-valuenow="{{ $pct }}"
                                 aria-valuemin="0"
                                 aria-valuemax="100"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Keterangan Jumlah Ulasan --}}
    <p class="text-muted mb-4" id="ratingDesktopSubtitle" style="font-size: 0.78rem;">
        @if(!empty($faskes['avg_rating']))
            Berdasarkan <span class="total-reviews-num">{{ $faskes['total_reviews'] }}</span> ulasan dari pasien terverifikasi.
        @else
            Fasilitas ini belum memiliki ulasan rating.
        @endif
    </p>

    {{-- Form Berikan Rating / Ulasan Pengguna --}}
    <div class="give-rating-box">
        @if(!Auth::check())
            {{-- Guest State --}}
            <div class="guest-review-box p-3 rounded-4 bg-light border text-center">
                <div class="mb-2 text-medigo-primary">
                    <i class="bi bi-shield-lock-fill fs-4"></i>
                </div>
                <h6 class="fw-bold text-dark mb-1" style="font-size: 0.9rem;">Ingin memberikan ulasan?</h6>
                <p class="text-muted mb-3" style="font-size: 0.78rem; line-height: 1.5;">
                    Silakan masuk ke akun Anda terlebih dahulu untuk memberikan rating dan ulasan faskes ini.
                </p>
                <a href="{{ route('login', ['redirect' => url()->current()]) }}"
                   class="btn btn-medigo-primary w-100 rounded-pill py-2 fw-semibold d-flex align-items-center justify-content-center gap-2 text-decoration-none">
                    <i class="bi bi-box-arrow-in-right"></i>
                    <span>Masuk Sekarang</span>
                </a>
            </div>
        @else
            {{-- Pinned Review Box (Jika user sudah pernah mengulas) --}}
            <div id="myCommentBox" class="{{ $myComment ? '' : 'd-none' }} mb-3">
                <div class="card border border-primary-subtle bg-primary-subtle bg-opacity-10 rounded-4 p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge bg-medigo-primary text-white rounded-pill px-2 py-1" style="font-size: 0.7rem;">
                            <i class="bi bi-check-circle-fill me-1"></i> Ulasan Anda
                        </span>
                        <div class="d-flex gap-1">
                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-2 py-0" id="btnEditMyComment" style="font-size: 0.72rem;">
                                <i class="bi bi-pencil-square"></i> Edit
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-2 py-0" id="btnDeleteMyComment" style="font-size: 0.72rem;">
                                <i class="bi bi-trash"></i> Hapus
                            </button>
                        </div>
                    </div>

                    <div class="d-flex text-warning gap-1 mb-1" id="myCommentStarsDisplay" style="font-size: 0.78rem;">
                        @if($myComment)
                            @for($i = 1; $i <= 5; $i++)
                                <i class="bi bi-star{{ $i <= $myComment['rating'] ? '-fill' : '' }}"></i>
                            @endfor
                        @endif
                    </div>

                    <p class="text-dark mb-1" id="myCommentBodyDisplay" style="font-size: 0.84rem; line-height: 1.5;">
                        {{ $myComment['body'] ?? '' }}
                    </p>
                    <small class="text-muted" id="myCommentTimeDisplay" style="font-size: 0.7rem;">
                        {{ $myComment['created_at_human'] ?? 'Baru saja' }}
                    </small>
                </div>
            </div>

            {{-- Form Input Ulasan --}}
            <div id="reviewInputFormContainer" class="{{ $myComment ? 'd-none' : '' }}">
                <h6 class="fw-bold text-dark mb-2" style="font-size: 1rem;">Berikan Rating</h6>

                <div class="star-rating-selector d-flex align-items-center gap-2 mb-3" id="interactiveStarPicker">
                    @for($i = 1; $i <= 5; $i++)
                        <i class="bi bi-star fs-5 star-pick-item text-muted" data-rating="{{ $i }}" role="button" title="Beri {{ $i }} Bintang"></i>
                    @endfor
                    <input type="hidden" id="selectedStarValue" value="0">
                </div>

                <div class="mb-3">
                    <textarea id="reviewCommentInput"
                              class="form-control review-textarea rounded-3 p-3"
                              rows="3"
                              placeholder="Ceritakan pengalaman Anda..."></textarea>
                </div>

                <button type="button"
                        class="btn btn-medigo-primary w-100 rounded-pill py-2 fw-semibold d-flex align-items-center justify-content-center gap-2"
                        id="btnSubmitReview">
                    <span class="spinner-border spinner-border-sm d-none" id="reviewSpinner" role="status"></span>
                    <span>Kirim Ulasan</span>
                </button>
            </div>
        @endif
    </div>
</div>