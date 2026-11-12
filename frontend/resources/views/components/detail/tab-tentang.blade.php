@props([
    'faskes' => [],
    'ratingPercentages' => [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0],
    'myComment' => null,
    'reviews' => [],
    'hasMoreComments' => false,
])

<div class="tab-pane fade show active" id="pane-tentang" role="tabpanel" aria-labelledby="tab-tentang-btn">

    {{-- Deskripsi Faskes --}}
    <div class="mb-4">
        <p class="text-secondary detail-desktop-about d-none d-lg-block mb-0" style="line-height: 1.75; font-size: 0.92rem;">
            {{ $faskes['description'] ?? '' }}
        </p>

        {{-- Versi Mobile dengan tombol Selengkapnya --}}
        <div class="d-block d-lg-none">
            <div class="detail-mobile-about-text text-secondary mb-1" id="mobileAboutText" style="font-size: 0.86rem; line-height: 1.65;">
                {{ $faskes['description'] ?? '' }}
            </div>
            <button type="button" class="btn btn-link p-0 text-medigo-primary text-decoration-none fw-semibold small d-inline-flex align-items-center gap-1" id="btnToggleAbout">
                <span id="toggleAboutLabel">Selengkapnya</span>
                <i class="bi bi-chevron-down" id="toggleAboutIcon"></i>
            </button>
        </div>
    </div>

    {{-- 2 Kolom Sejajar: Ringkasan Rating & Ulasan Terbaru --}}
    <div class="row g-4">
        {{-- Kolom Kiri: Ringkasan Rating & Form Ulasan --}}
        <div class="col-md-6 col-12">
            <x-detail.rating-summary
                :faskes="$faskes"
                :rating-percentages="$ratingPercentages"
                :my-comment="$myComment"
            />
        </div>

        {{-- Kolom Tengah: Daftar Ulasan Terbaru --}}
        <div class="col-md-6 col-12">
            <x-detail.reviews-list
                :faskes="$faskes"
                :reviews="$reviews"
                :has-more-comments="$hasMoreComments"
            />
        </div>
    </div>

</div>