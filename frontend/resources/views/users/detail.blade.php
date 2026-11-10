@extends('layouts.app')

@section('title', ($faskes['nama_faskes'] ?? 'Detail Faskes') . ' - MediGO')

@push('styles')
    <!-- Leaflet CSS for Mini Map Preview -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" />
@endpush

@php
    $currentUserId = $currentUserId ?? (Auth::id() ?? ($currentUser->id ?? null));
    $isLoggedIn = $isLoggedIn ?? Auth::check();
    $currentUser = $currentUser ?? Auth::user();
    $myComment = $myComment ?? null;
    $hasMoreComments = $hasMoreComments ?? false;
    $commentsPage = $commentsPage ?? 1;
    $reviews = $reviews ?? [];
    $ratingBreakdown = $ratingBreakdown ?? [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
    $ratingPercentages = $ratingPercentages ?? [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];

    $typeSlug = $faskes['type_slug'] ?? 'hospital';
    $faskesDisplayName = trim($faskes['nama_faskes'] ?? 'Fasilitas Kesehatan');
    if (($typeSlug === 'puskesmas' || str_contains(strtolower($faskes['tipe'] ?? ''), 'puskesmas')) && !preg_match('/^(puskesmas|pkm)\b/i', $faskesDisplayName)) {
        $faskesDisplayName = 'Puskesmas ' . $faskesDisplayName;
    }

    $typeIcon = 'bi-hospital';
    if ($typeSlug === 'clinic') {
        $typeIcon = 'bi-heart-pulse-fill';
    } elseif ($typeSlug === 'puskesmas') {
        $typeIcon = 'bi-plus-circle-fill';
    }
@endphp

@section('content')
<div class="faskes-detail-wrapper">

    <!-- =========================================================================
         MOBILE TOP HEADER (Visible only on Mobile screens < 992px)
         ========================================================================= -->
    <div class="detail-mobile-topbar d-flex d-lg-none align-items-center justify-content-between px-3 py-3 bg-white border-bottom sticky-top">
        <a href="javascript:history.back()" class="btn-detail-back text-dark d-flex align-items-center text-decoration-none" title="Kembali">
            <i class="bi bi-arrow-left fs-4"></i>
        </a>
        <h6 class="m-0 fw-bold text-dark detail-mobile-heading text-truncate px-2">
            Detail {{ $faskes['tipe'] ?? 'Rumah Sakit' }}
        </h6>
        <div style="width: 24px;"></div> <!-- Spacer for balance -->
    </div>

    <!-- =========================================================================
         MOBILE HERO BANNER (Type Logo with Colored Background)
         ========================================================================= -->
    <!-- =========================================================================
         MOBILE HERO BANNER (Type Logo with Colored Background)
         ========================================================================= -->
    <div class="detail-mobile-hero faskes-detail-hero-mobile faskes-avatar-{{ $typeSlug }} d-block d-lg-none position-relative p-4 text-center">
        <div class="faskes-hero-logo-circle mb-3 mx-auto">
            <i class="bi {{ $typeIcon }} text-white"></i>
        </div>
        <h4 class="text-white fw-bold mb-2 detail-mobile-hero-title">{{ $faskesDisplayName }}</h4>
        <div class="d-flex align-items-center justify-content-center gap-2 flex-wrap">
            <!-- Rating Badge -->
            <span class="badge-hero-pill badge-hero-rating" id="mobileHeroRatingBadge">
                @if(!empty($faskes['avg_rating']))
                    <i class="bi bi-star-fill text-warning me-1"></i> {{ $faskes['avg_rating'] }}
                @else
                    <i class="bi bi-star text-white-50 me-1"></i> Belum dirating
                @endif
            </span>
            <!-- BPJS Badge -->
            <span class="badge-hero-pill {{ $faskes['is_support_bpjs'] ? 'badge-hero-bpjs' : 'badge-hero-nonbpjs' }}">
                {{ $faskes['is_support_bpjs'] ? 'BPJS' : 'Non-BPJS' }}
            </span>
            <!-- Distance Badge -->
            <span class="badge-hero-pill badge-hero-distance">
                <i class="bi bi-geo-alt-fill me-1"></i> {{ $faskes['distance'] }}
            </span>
        </div>
    </div>

    <!-- =========================================================================
         MAIN CONTAINER (Desktop & Mobile Content Structure)
         ========================================================================= -->
    <div class="container py-3 py-lg-4">

        <!-- =====================================================================
             DESKTOP HERO SHOWCASE BANNER (Visible only on Desktop screens >= 992px)
             ===================================================================== -->
        <div class="detail-desktop-hero-banner faskes-avatar-{{ $typeSlug }} d-none d-lg-flex align-items-center justify-content-between p-4 p-xl-5 mb-4 rounded-4 shadow-sm text-white position-relative overflow-hidden">
            <div class="d-flex align-items-center gap-4 position-relative" style="z-index: 2;">
                <!-- Logo Box -->
                <div class="faskes-desktop-logo-box">
                    <i class="bi {{ $typeIcon }} text-white"></i>
                </div>
                <!-- Title & Badges -->
                <div>
                    <span class="badge rounded-pill bg-white text-dark px-3 py-1 fw-bold mb-2 shadow-xs" style="font-size: 0.76rem;">
                        {{ (!empty($faskes['tipe']) && strcasecmp($faskes['tipe'], 'Fasilitas Kesehatan') !== 0) ? $faskes['tipe'] : 'Rumah Sakit' }}
                    </span>
                    <h2 class="fw-bold text-white mb-2">{{ $faskesDisplayName }}</h2>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        @if(!empty($faskes['kelas']))
                            <span class="badge-custom-pill bg-white text-dark shadow-xs">Kelas {{ $faskes['kelas'] }}</span>
                        @endif
                        <span class="badge-custom-pill bg-white {{ $faskes['is_support_bpjs'] ? 'text-success' : 'text-danger' }} shadow-xs">
                            {{ $faskes['is_support_bpjs'] ? 'BPJS' : 'Non-BPJS' }}
                        </span>
                        <span class="badge-custom-pill bg-white text-dark shadow-xs">
                            <i class="bi bi-arrow-down-up me-1"></i> {{ $faskes['distance'] }}
                        </span>
                        <span class="badge-custom-pill bg-white text-dark shadow-xs" id="desktopHeroRatingBadge">
                            @if(!empty($faskes['avg_rating']))
                                <i class="bi bi-star-fill text-warning me-1"></i> <strong>{{ $faskes['avg_rating'] }}</strong> (<span class="total-reviews-num">{{ $faskes['total_reviews'] }}</span> Ulasan)
                            @else
                                <i class="bi bi-star text-muted me-1"></i> <span>Belum dirating</span>
                            @endif
                        </span>
                    </div>
                </div>
            </div>

            <!-- Background Decorative Watermark Icon -->
            <div class="hero-decorative-bg-icon position-absolute end-0 top-50 translate-middle-y pe-4" style="pointer-events: none; opacity: 0.12;">
                <i class="bi {{ $typeIcon }}" style="font-size: 14rem; color: #ffffff;"></i>
            </div>
        </div>

        <!-- =====================================================================
             DESKTOP TABS & TENTANG
             ===================================================================== -->
        <div class="detail-desktop-header d-none d-lg-block mb-4">
            <!-- Tab Bar Underline Divider -->
            <div class="detail-nav-tabs-wrapper border-bottom pb-1 mb-3">
                <div class="d-inline-flex align-items-center gap-2 pb-2 border-bottom border-3 border-primary text-dark fw-bold" style="border-color: #002b5e !important; font-size: 0.95rem;">
                    <i class="bi bi-building"></i>
                    <span>Tentang</span>
                </div>
            </div>

            <!-- Tentang Paragraph on Desktop -->
            <p class="text-secondary detail-desktop-about mb-4" style="line-height: 1.7; font-size: 0.92rem;">
                {{ $faskes['description'] }}
            </p>
        </div>

        <!-- =====================================================================
             MOBILE CARD 1: TENTANG (Visible only on Mobile screens < 992px)
             ===================================================================== -->
        <div class="detail-mobile-card card border rounded-4 shadow-2xs mb-3 d-block d-lg-none">
            <div class="card-body p-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="bi bi-building text-medigo-primary fs-5"></i>
                    <h6 class="fw-bold text-dark m-0">Tentang</h6>
                </div>
                <div class="detail-mobile-about-text text-secondary mb-2" id="mobileAboutText" style="font-size: 0.85rem; line-height: 1.6;">
                    {{ $faskes['description'] }}
                </div>
                <button type="button" class="btn btn-link p-0 text-medigo-primary text-decoration-none fw-semibold small d-inline-flex align-items-center gap-1" id="btnToggleAbout">
                    <span id="toggleAboutLabel">Selengkapnya</span>
                    <i class="bi bi-chevron-down" id="toggleAboutIcon"></i>
                </button>
            </div>
        </div>

        <!-- =====================================================================
             DESKTOP 3-COLUMN & MOBILE STACKED LAYOUT
             ===================================================================== -->
        <div class="row g-3 g-lg-4">

            <!-- =================================================================
                 COLUMN 1: RATING SUMMARY & BERIKAN RATING FORM
                 (Mobile: Inside Rating & Ulasan Card | Desktop: Left Column)
                 ================================================================= -->
            <div class="col-lg-4 col-12 order-1">
                <div class="card border rounded-4 shadow-2xs h-100 detail-section-card">
                    <div class="card-body p-3 p-lg-4">

                        <!-- Mobile Header inside Card -->
                        <div class="d-flex align-items-center gap-2 mb-3 d-lg-none">
                            <i class="bi bi-chat-square-quote-fill text-medigo-primary fs-5"></i>
                            <h6 class="fw-bold text-dark m-0">Rating & Ulasan</h6>
                        </div>

                        <!-- Desktop Heading -->
                        <h6 class="fw-bold text-dark mb-3 d-none d-lg-block">Ringkasan Rating</h6>

                        <!-- Rating Score Box -->
                        <div class="rating-score-box rounded-4 p-3 mb-3">
                            <div class="row align-items-center">
                                <!-- Big Rating Number & Stars -->
                                <div class="col-5 text-center border-end">
                                    <div class="rating-huge-score fw-bold text-dark" id="ratingHugeScoreText">
                                        {{ !empty($faskes['avg_rating']) ? $faskes['avg_rating'] : '-' }}
                                    </div>
                                    <div class="d-flex justify-content-center text-warning gap-1 mb-1" id="ratingHugeStarsContainer" style="font-size: 0.75rem;">
                                        @if(!empty($faskes['avg_rating']))
                                            @for($i = 1; $i <= 5; $i++)
                                                <i class="bi bi-star{{ $i <= round((float)$faskes['avg_rating']) ? '-fill' : '' }}"></i>
                                            @endfor
                                        @else
                                            @for($i = 1; $i <= 5; $i++)
                                                <i class="bi bi-star text-muted opacity-50"></i>
                                            @endfor
                                        @endif
                                    </div>
                                    <div class="text-muted rating-subtext" style="font-size: 0.72rem;" id="ratingSubtextDisplay">
                                        @if(!empty($faskes['avg_rating']))
                                            <span class="d-lg-none"><span class="total-reviews-num">{{ $faskes['total_reviews'] }}</span> Ulasan</span>
                                            <span class="d-none d-lg-inline">dari 5</span>
                                        @else
                                            <span>Belum dirating</span>
                                        @endif
                                    </div>
                                </div>

                                <!-- Progress Bar Breakdown (5 to 1) -->
                                <div class="col-7">
                                    <div class="rating-breakdown-list d-flex flex-column gap-1">
                                        @php
                                            $percentages = $ratingPercentages ?? [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
                                        @endphp
                                        @foreach([5, 4, 3, 2, 1] as $star)
                                            @php
                                                $pct = $percentages[$star] ?? 0;
                                            @endphp
                                            <div class="d-flex align-items-center gap-2" style="font-size: 0.72rem;">
                                                <span class="fw-semibold text-secondary" style="width: 8px;">{{ $star }}</span>
                                                <div class="progress flex-grow-1" style="height: 6px; background-color: #e5e7eb; border-radius: 50px;">
                                                    <div class="progress-bar bg-warning rating-bar-star-{{ $star }}" id="ratingBar{{ $star }}" role="progressbar" style="width: {{ $pct }}%; border-radius: 50px;" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Subtitle on Desktop -->
                        <p class="text-muted d-none d-lg-block mb-4" id="ratingDesktopSubtitle" style="font-size: 0.78rem;">
                            @if(!empty($faskes['avg_rating']))
                                Berdasarkan <span class="total-reviews-num">{{ $faskes['total_reviews'] }}</span> ulasan dari pasien terverifikasi.
                            @else
                                Fasilitas ini belum memiliki ulasan rating.
                            @endif
                        </p>

                        <!-- Form "Berikan Rating" OR Pinned Review Box -->
                        <div class="give-rating-box">
                            
                            @if(!Auth::check())
                                <!-- GUEST STATE -->
                                <div class="guest-review-box p-3 rounded-4 bg-light border text-center my-2">
                                    <div class="mb-2 text-medigo-primary">
                                        <i class="bi bi-shield-lock-fill fs-3"></i>
                                    </div>
                                    <h6 class="fw-bold text-dark mb-1" style="font-size: 0.92rem;">Ingin memberikan ulasan?</h6>
                                    <p class="text-muted mb-3" style="font-size: 0.78rem; line-height: 1.5;">
                                        Silakan masuk ke akun Anda terlebih dahulu untuk memberikan rating dan ulasan faskes ini.
                                    </p>
                                    <a href="{{ route('login', ['redirect' => url()->current()]) }}" class="btn btn-medigo-primary w-100 rounded-pill py-2 fw-semibold d-flex align-items-center justify-content-center gap-2 text-decoration-none shadow-xs">
                                        <i class="bi bi-box-arrow-in-right"></i>
                                        <span>Masuk Sekarang</span>
                                    </a>
                                </div>
                            @else
                                <!-- LOGGED IN STATE -->
                                
                                <!-- Container if User Already Commented -->
                                <div id="myCommentBox" class="{{ $myComment ? '' : 'd-none' }} mb-3">
                                    <div class="card border border-primary-subtle bg-primary-subtle bg-opacity-10 rounded-4 p-3 position-relative">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="badge bg-medigo-primary text-white rounded-pill px-2-5 py-1" style="font-size: 0.7rem;">
                                                <i class="bi bi-check-circle-fill me-1"></i> Ulasan Anda
                                            </span>
                                            <div class="d-flex gap-2">
                                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-2 py-0-5 d-flex align-items-center gap-1" id="btnEditMyComment" style="font-size: 0.72rem;">
                                                    <i class="bi bi-pencil-square"></i> Edit
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-2 py-0-5 d-flex align-items-center gap-1" id="btnDeleteMyComment" style="font-size: 0.72rem;">
                                                    <i class="bi bi-trash"></i> Hapus
                                                </button>
                                            </div>
                                        </div>

                                        <div class="d-flex text-warning gap-1 mb-1" id="myCommentStarsDisplay" style="font-size: 0.75rem;">
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

                                <!-- Review Input Form -->
                                <div id="reviewInputFormContainer" class="{{ $myComment ? 'd-none' : '' }}">
                                    <h6 class="fw-bold text-dark mb-2" style="font-size: 0.95rem;">Berikan Rating & Ulasan</h6>
                                    
                                    <!-- Interactive Star Selection -->
                                    <div class="star-rating-selector d-flex align-items-center gap-2 mb-3" id="interactiveStarPicker">
                                        @for($i = 1; $i <= 5; $i++)
                                            <i class="bi bi-star fs-4 star-pick-item text-muted" data-rating="{{ $i }}" role="button" title="Beri {{ $i }} Bintang"></i>
                                        @endfor
                                        <input type="hidden" id="selectedStarValue" value="0">
                                    </div>

                                    <!-- Comment Input -->
                                    <div class="mb-3">
                                        <textarea id="reviewCommentInput" class="form-control review-textarea rounded-3" rows="3" placeholder="Ceritakan pengalaman Anda di faskes ini..."></textarea>
                                    </div>

                                    <!-- Submit Button -->
                                    <button type="button" class="btn btn-medigo-primary w-100 rounded-pill py-2 fw-semibold d-flex align-items-center justify-content-center gap-2 shadow-xs" id="btnSubmitReview">
                                        <span class="spinner-border spinner-border-sm d-none" id="reviewSpinner" role="status"></span>
                                        <span>Kirim Ulasan</span>
                                    </button>
                                </div>
                            @endif

                        </div>

                        <!-- Mobile: Integrated Reviews list directly inside Rating & Ulasan Card -->
                        <div class="d-block d-lg-none mt-4 pt-3 border-top">
                            <h6 class="fw-bold text-dark mb-2" style="font-size: 0.9rem;">Daftar Ulasan Pasien</h6>
                            
                            <div class="mobile-reviews-list" id="mobileReviewsContainer">
                                @forelse($reviews as $review)
                                    <div class="mobile-review-item py-2 {{ !$loop->last ? 'border-bottom' : '' }} {{ !empty($review['is_my_comment']) ? 'bg-light p-2 rounded-3 mb-2' : '' }}" id="mobile-review-{{ $review['id'] }}">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <div class="d-flex align-items-center gap-1-5">
                                                <span class="fw-bold text-dark" style="font-size: 0.88rem;">{{ $review['reviewer_name'] }}</span>
                                                @if(!empty($review['is_my_comment']))
                                                    <span class="badge bg-medigo-primary text-white rounded-pill px-2 py-0-5" style="font-size: 0.65rem;">Anda</span>
                                                @endif
                                            </div>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="text-muted" style="font-size: 0.72rem;">{{ $review['created_at_human'] ?? 'Baru saja' }}</span>
                                                @if(empty($review['is_my_comment']))
                                                    <button type="button" class="btn btn-sm btn-link text-muted p-0 text-decoration-none btn-trigger-report" data-comment-id="{{ $review['id'] }}" data-reviewer="{{ $review['reviewer_name'] }}" title="Laporkan ulasan ini">
                                                        <i class="bi bi-flag text-danger opacity-75"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="d-flex text-warning gap-1 mb-1" style="font-size: 0.7rem;">
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

                            <!-- Mobile Load More Button -->
                            <div class="mt-3 {{ $hasMoreComments ? '' : 'd-none' }}" id="mobileLoadMoreWrapper">
                                <button type="button" class="btn btn-outline-secondary w-100 rounded-pill py-1-5 fw-semibold d-flex align-items-center justify-content-center gap-2" id="btnMobileLoadMore" style="font-size: 0.78rem;">
                                    <span class="spinner-border spinner-border-sm d-none" id="mobileLoadMoreSpinner"></span>
                                    <span>Lihat Komentar Lainnya</span>
                                </button>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- =================================================================
                 COLUMN 2: ULASAN TERBARU (Desktop Only Column)
                 ================================================================= -->
            <div class="col-lg-4 d-none d-lg-block order-2">
                <div class="card border rounded-4 shadow-2xs h-100 detail-section-card">
                    <div class="card-body p-4 d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold text-dark m-0">Ulasan Terbaru</h6>
                            <span class="badge bg-light text-muted border rounded-pill px-2 py-1" style="font-size: 0.72rem;" id="desktopReviewsCountBadge">
                                <span class="total-reviews-num">{{ $faskes['total_reviews'] ?? count($reviews) }}</span> Ulasan
                            </span>
                        </div>

                        <!-- Desktop Reviews List Container -->
                        <div class="desktop-reviews-list d-flex flex-column gap-3 flex-grow-1" id="desktopReviewsContainer">
                            @forelse($reviews as $review)
                                <div class="desktop-review-card {{ !$loop->last ? 'border-bottom pb-3' : '' }} {{ !empty($review['is_my_comment']) ? 'p-3 bg-light rounded-4 border' : '' }}" id="desktop-review-{{ $review['id'] }}">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <div class="d-flex align-items-center gap-1-5">
                                            <span class="fw-bold text-dark" style="font-size: 0.9rem;">{{ $review['reviewer_name'] }}</span>
                                            @if(!empty($review['is_my_comment']))
                                                <span class="badge bg-medigo-primary text-white rounded-pill px-2 py-0-5" style="font-size: 0.68rem;">Ulasan Anda</span>
                                            @endif
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="text-muted" style="font-size: 0.75rem;">{{ $review['created_at_human'] ?? 'Baru saja' }}</span>
                                            @if(empty($review['is_my_comment']))
                                                <button type="button" class="btn btn-sm btn-link text-muted p-0 text-decoration-none btn-trigger-report" data-comment-id="{{ $review['id'] }}" data-reviewer="{{ $review['reviewer_name'] }}" title="Laporkan ulasan tidak sopan">
                                                    <i class="bi bi-flag text-danger opacity-75" style="font-size: 0.78rem;"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="d-flex text-warning gap-1 mb-2" style="font-size: 0.72rem;">
                                        @for($s = 1; $s <= 5; $s++)
                                            <i class="bi bi-star{{ $s <= $review['rating'] ? '-fill' : '' }}"></i>
                                        @endfor
                                    </div>
                                    <p class="text-secondary mb-0" style="font-size: 0.84rem; line-height: 1.55;">
                                        {{ $review['body'] ?? $review['comment'] ?? '' }}
                                    </p>
                                </div>
                            @empty
                                <div class="text-center py-5 text-muted my-auto" id="desktopEmptyReviewsNotice" style="font-size: 0.86rem;">
                                    <i class="bi bi-chat-square-text text-muted opacity-50 fs-2 d-block mb-2"></i>
                                    Belum ada ulasan untuk fasilitas ini.<br>Jadilah yang pertama memberikan ulasan!
                                </div>
                            @endforelse
                        </div>

                        <!-- Desktop Load More Button -->
                        <div class="mt-3 pt-2 border-top {{ $hasMoreComments ? '' : 'd-none' }}" id="desktopLoadMoreWrapper">
                            <button type="button" class="btn btn-outline-secondary w-100 rounded-pill py-2 fw-semibold d-flex align-items-center justify-content-center gap-2" id="btnDesktopLoadMore" style="font-size: 0.82rem;">
                                <span class="spinner-border spinner-border-sm d-none" id="desktopLoadMoreSpinner"></span>
                                <span>Lihat Komentar Lainnya</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- =================================================================
                 COLUMN 3: INFORMASI KONTAK & MINI MAP (Desktop Right / Mobile Bottom)
                 ================================================================= -->
            <div class="col-lg-4 col-12 order-3">
                <div class="card border rounded-4 shadow-2xs overflow-hidden detail-section-card contact-card">
                    
                    <!-- Mini Map Container -->
                    <div class="detail-mini-map-wrapper position-relative">
                        <div id="detailMiniMap" class="detail-mini-map"></div>
                    </div>

                    <!-- Contact Body -->
                    <div class="card-body p-3 p-lg-4">
                        <!-- Header -->
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <i class="bi bi-info-circle-fill text-medigo-primary fs-5"></i>
                            <h6 class="fw-bold text-dark m-0">Informasi Kontak</h6>
                        </div>

                        <!-- Address -->
                        <div class="d-flex gap-3 align-items-start mb-3">
                            <i class="bi bi-geo-alt text-medigo-primary fs-5 mt-1 flex-shrink-0"></i>
                            <div>
                                <div class="fw-bold text-dark" style="font-size: 0.86rem;">Alamat</div>
                                <div class="text-secondary" style="font-size: 0.82rem; line-height: 1.5;">
                                    {{ $faskes['alamat'] }}
                                </div>
                            </div>
                        </div>

                        <hr class="my-3 text-muted opacity-25">

                        <!-- Telepon -->
                        <div class="d-flex gap-3 align-items-center mb-4">
                            <i class="bi bi-telephone text-medigo-primary fs-5 flex-shrink-0"></i>
                            <div>
                                <div class="fw-bold text-dark" style="font-size: 0.86rem;">Telepon</div>
                                <div class="text-secondary" style="font-size: 0.84rem;">
                                    {{ $faskes['phone'] }}
                                </div>
                            </div>
                        </div>

                        <!-- Navigation Button -->
                        <a id="btnNavigasiDetail" href="{{ $faskes['navigasi_url'] }}" target="_blank" class="btn btn-medigo-primary w-100 rounded-pill py-2 fw-semibold d-flex align-items-center justify-content-center gap-2 text-decoration-none shadow-xs">
                            <i class="bi bi-sign-turn-right"></i>
                            <span>Navigasi</span>
                        </a>

                        <!-- Feedback / Koreksi Data Button -->
                        <button type="button" class="btn btn-outline-secondary w-100 rounded-pill py-2 fw-semibold d-flex align-items-center justify-content-center gap-2 mt-2 shadow-xs" data-bs-toggle="modal" data-bs-target="#modalFeedbackFaskes" style="font-size: 0.84rem;">
                            <i class="bi bi-patch-question"></i>
                            <span>Koreksi / Masukan Data</span>
                        </button>

                    </div>
                </div>
            </div>

        </div>

    </div>

</div>

<!-- =========================================================================
     MODAL EDIT ULASAN
     ========================================================================= -->
<div class="modal fade" id="modalEditReview" tabindex="-1" aria-labelledby="modalEditReviewLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            <div class="modal-header border-bottom px-4 py-3 bg-light">
                <h6 class="modal-title fw-bold text-dark m-0" id="modalEditReviewLabel">
                    <i class="bi bi-pencil-square text-medigo-primary me-1"></i> Edit Ulasan Anda
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="editReviewId" value="{{ $myComment['id'] ?? '' }}">
                
                <!-- Star Rating Picker inside Modal -->
                <div class="mb-3 text-center">
                    <label class="form-label fw-semibold text-dark small d-block mb-2">Bintang Rating</label>
                    <div class="star-rating-selector d-flex align-items-center justify-content-center gap-2" id="editInteractiveStarPicker">
                        @for($i = 1; $i <= 5; $i++)
                            <i class="bi bi-star fs-3 edit-star-pick-item text-muted" data-rating="{{ $i }}" role="button" title="Beri {{ $i }} Bintang"></i>
                        @endfor
                        <input type="hidden" id="editSelectedStarValue" value="{{ $myComment['rating'] ?? '5' }}">
                    </div>
                </div>

                <!-- Review Body -->
                <div class="mb-3">
                    <label class="form-label fw-semibold text-dark small">Tulis Ulasan Anda</label>
                    <textarea id="editReviewCommentInput" class="form-control rounded-3" rows="4" placeholder="Ceritakan pengalaman Anda..."></textarea>
                </div>
            </div>
            <div class="modal-footer border-top px-4 py-3 bg-light d-flex justify-content-between">
                <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-medigo-primary rounded-pill px-4 fw-semibold d-flex align-items-center gap-2" id="btnSaveEditReview">
                    <span class="spinner-border spinner-border-sm d-none" id="editReviewSpinner"></span>
                    <span>Simpan Perubahan</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- =========================================================================
     MODAL MASUKAN & KOREKSI DATA FASKES
     ========================================================================= -->
<div class="modal fade" id="modalFeedbackFaskes" tabindex="-1" aria-labelledby="modalFeedbackFaskesLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            <div class="modal-header border-bottom px-4 py-3 bg-light">
                <h6 class="modal-title fw-bold text-dark m-0" id="modalFeedbackFaskesLabel">
                    <i class="bi bi-patch-question-fill text-medigo-primary me-1"></i> Masukan & Koreksi Data Faskes
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="p-3 bg-light rounded-3 mb-3 border">
                    <small class="text-muted d-block" style="font-size: 0.72rem;">Fasilitas Kesehatan:</small>
                    <strong class="text-dark">{{ $faskes['nama_faskes'] ?? 'Fasilitas Kesehatan' }}</strong>
                    <div class="text-muted small">{{ $faskes['alamat'] ?? '' }}</div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold text-dark small">Bagian data yang keliru / perlu disesuaikan <span class="text-danger">*</span></label>
                    <select id="feedbackCategorySelect" class="form-select rounded-3" style="font-size: 0.88rem;">
                        <option value="lokasi_salah">Titik Lokasi / Koordinat Peta Salah</option>
                        <option value="bpjs_tidak_sesuai">Status Kerjasama BPJS Tidak Sesuai</option>
                        <option value="kontak_keliru">Nomor Telepon / Kontak Keliru</option>
                        <option value="tutup_permanen">Faskes Sudah Tutup / Tidak Beroperasi</option>
                        <option value="lainnya">Lainnya</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold text-dark small">Detail Masukan / Koreksi <span class="text-danger">*</span></label>
                    <textarea id="feedbackNotesInput" class="form-control rounded-3" rows="3" placeholder="Jelaskan kesalahan data, misalnya: titik lokasi di peta salah atau sekarang sudah tidak menerima BPJS..." style="font-size: 0.86rem;"></textarea>
                </div>

                <div class="mb-2">
                    <label class="form-label fw-semibold text-dark small">Usulan / Data yang Benar (Opsional)</label>
                    <input type="text" id="feedbackCorrectionInput" class="form-control rounded-3" placeholder="Contoh: link google maps yang benar, no telepon terbaru..." style="font-size: 0.86rem;">
                </div>
            </div>
            <div class="modal-footer border-top px-4 py-3 bg-light d-flex justify-content-between">
                <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-medigo-primary rounded-pill px-4 fw-semibold d-flex align-items-center gap-2" id="btnSubmitFeedback">
                    <span class="spinner-border spinner-border-sm d-none" id="feedbackSpinner"></span>
                    <span>Kirim Masukan</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- =========================================================================
     MODAL LAPORKAN KOMENTAR
     ========================================================================= -->
<div class="modal fade" id="modalReportReview" tabindex="-1" aria-labelledby="modalReportReviewLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            <div class="modal-header border-bottom px-4 py-3 bg-light">
                <h6 class="modal-title fw-bold text-danger m-0" id="modalReportReviewLabel">
                    <i class="bi bi-flag-fill me-1"></i> Laporkan Ulasan Pengguna
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="reportTargetCommentId" value="">
                
                <div class="mb-3 p-3 bg-light rounded-3 border">
                    <small class="text-muted d-block" style="font-size: 0.72rem;">Pengulas yang dilaporkan:</small>
                    <strong class="text-dark" id="reportTargetReviewerName">-</strong>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold text-dark small">Alasan Pelaporan <span class="text-danger">*</span></label>
                    <select id="reportReasonSelect" class="form-select rounded-3" style="font-size: 0.88rem;">
                        <option value="kata_kasar">Kata-kata kasar / tidak sopan</option>
                        <option value="sara">Mengandung SARA / ujaran kebencian</option>
                        <option value="spam">Spam atau promosi tidak relevan</option>
                        <option value="hoax">Informasi palsu / fitnah</option>
                        <option value="lainnya">Alasan lainnya</option>
                    </select>
                </div>

                <div class="mb-2">
                    <label class="form-label fw-semibold text-dark small">Keterangan Tambahan (Opsional)</label>
                    <textarea id="reportNotesInput" class="form-control rounded-3" rows="3" placeholder="Tuliskan detail laporan jika diperlukan..." style="font-size: 0.86rem;"></textarea>
                </div>
            </div>
            <div class="modal-footer border-top px-4 py-3 bg-light d-flex justify-content-between">
                <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger rounded-pill px-4 fw-semibold d-flex align-items-center gap-2" id="btnSubmitReportComment">
                    <span class="spinner-border spinner-border-sm d-none" id="reportCommentSpinner"></span>
                    <span>Kirim Laporan</span>
                </button>
            </div>
        </div>
    </div>
</div>

@php
    $ratingPercentages = $ratingPercentages ?? [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
@endphp
<!-- Pass Data to Detail JS -->
<script>
    window.detailFaskesData = {
        id: {{ $faskes['id'] }},
        nama_faskes: @json($faskes['nama_faskes']),
        lat: {{ $faskes['lat'] ?? -6.2758 }},
        lng: {{ $faskes['lng'] ?? 106.7820 }},
        navigasi_url: @json($faskes['navigasi_url']),
        alamat: @json($faskes['alamat'] ?? ''),
        kota_kabupaten: @json($faskes['kota_kabupaten'] ?? ''),
        isLoggedIn: @json($isLoggedIn),
        currentUserId: @json($currentUserId),
        myComment: @json($myComment),
        total_reviews: {{ (int)($faskes['total_reviews'] ?? 0) }},
        avg_rating: @json($faskes['avg_rating'] ?? null),
        rating_percentages: @json($ratingPercentages),
        csrfToken: @json(csrf_token())
    };
</script>
@endsection

@push('scripts')
    <!-- Leaflet JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
    <!-- Detail Page JS Module -->
    @vite(['resources/js/detail.js'])
@endpush
