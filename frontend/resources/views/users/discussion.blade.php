@extends('layouts.app')

@section('title', 'Forum Diskusi Kesehatan - MediGO')

@section('content')
@php
    // Ambil 3 topik terpopuler secara dinamis dari $discussions berdasarkan komentar terbanyak
    $trendingList = collect($discussions ?? [])
        ->sortByDesc(function ($item) {
            return (int) ($item['comments_count'] ?? 0);
        })
        ->take(3)
        ->values();

    // Daftar kategori resmi MediGO (sesuai filter di DiscussionController)
    $forumCategories = [
        ['label' => 'Ulasan Rumah Sakit', 'value' => 'Ulasan Rumah Sakit'],
        ['label' => 'BPJS', 'value' => 'BPJS'],
        ['label' => 'Umum', 'value' => 'Umum'],
        ['label' => 'Rekomendasi RS', 'value' => 'Rekomendasi RS'],
        ['label' => 'Tips Kesehatan', 'value' => 'Tips Kesehatan'],
    ];
@endphp

<div class="container py-4">
    <div class="row g-4 align-items-start">

        <!-- Sidebar Kiri (Diam di Tempat / Tidak Ikut Scroll) -->
        <div class="col-lg-3">
            <div class="medigo-sidebar-sticky">

                <!-- Tombol Diskusi Baru -->
                @if(Auth::check())
                    <a href="{{ route('discussions.create') }}"
                       class="btn btn-medigo-primary w-100 rounded-pill py-2 mb-3 fw-semibold shadow-sm text-white d-flex align-items-center justify-content-center gap-2"
                       style="background-color: #002b5e;">
                        <i class="bi bi-plus-lg"></i>
                        <span>Diskusi Baru</span>
                    </a>
                @else
                    <a href="{{ route('login') }}"
                       class="btn btn-medigo-primary w-100 rounded-pill py-2 mb-3 fw-semibold shadow-sm text-white d-flex align-items-center justify-content-center gap-2"
                       style="background-color: #002b5e;">
                        <i class="bi bi-box-arrow-in-right"></i>
                        <span>Masuk untuk Berdiskusi</span>
                    </a>
                @endif

                <!-- Card Kategori -->
                <div class="card border rounded-4 shadow-sm mb-3 bg-white">
                    <div class="card-body p-3">
                        <h6 class="fw-bold mb-2 px-2 text-dark" style="font-size: 0.78rem; letter-spacing: 0.6px;">
                            KATEGORI
                        </h6>
                        <div class="d-flex flex-column gap-1">
                            <!-- Semua Topik -->
                            <a href="{{ route('discussions') }}"
                               class="d-flex justify-content-between align-items-center text-decoration-none px-3 py-1 rounded-3 {{ empty($category) && empty($tab) ? 'bg-medigo-subtle fw-semibold' : 'text-dark hover-subtle' }}"
                               style="font-size: 0.88rem;">
                                <span>Semua Topik</span>
                            </a>

                            <!-- Diskusi Saya (Jika Login) -->
                            @if(Auth::check())
                                <a href="{{ route('discussions', ['tab' => 'my_discussions']) }}"
                                   class="d-flex justify-content-between align-items-center text-decoration-none px-3 py-1 rounded-3 {{ ($tab ?? '') === 'my_discussions' ? 'bg-medigo-subtle fw-semibold' : 'text-dark hover-subtle' }}"
                                   style="font-size: 0.88rem;">
                                    <span>Diskusi Saya</span>
                                </a>
                            @endif

                            <!-- Daftar Kategori -->
                            @foreach($forumCategories as $cat)
                                <a href="{{ route('discussions', ['category' => $cat['value']]) }}"
                                   class="d-flex justify-content-between align-items-center text-decoration-none px-3 py-1 rounded-3 {{ ($category ?? '') === $cat['value'] ? 'bg-medigo-subtle fw-semibold' : 'text-dark hover-subtle' }}"
                                   style="font-size: 0.88rem;">
                                    <span>{{ $cat['label'] }}</span>
                                    @if(isset($categoryCounts[$cat['value']]))
                                        <span class="text-muted" style="font-size: 0.75rem;">{{ $categoryCounts[$cat['value']] }}</span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Card Sedang Tren (Dinamis dari $discussions) -->
                <div class="card border rounded-4 shadow-sm bg-white">
                    <div class="card-body p-3">
                        <h6 class="fw-bold mb-2 px-2 text-danger d-flex align-items-center gap-2" style="font-size: 0.78rem; letter-spacing: 0.6px;">
                            <i class="bi bi-graph-up-arrow"></i>
                            <span>SEDANG TREN</span>
                        </h6>

                        <div class="d-flex flex-column gap-2 px-2">
                            @forelse($trendingList as $trend)
                                @php
                                    $trendId = $trend['id_diskusi'] ?? $trend['id'] ?? null;
                                    $trendTitle = $trend['judul_diskusi'] ?? $trend['title'] ?? 'Diskusi';
                                    $trendReplies = (int) ($trend['comments_count'] ?? 0);
                                @endphp
                                <div>
                                    <a href="{{ $trendId ? route('discussions.show', $trendId) : '#' }}"
                                       class="text-dark text-decoration-none fw-semibold d-block hover-primary-text"
                                       style="font-size: 0.85rem; line-height: 1.35;">
                                        {{ \Illuminate\Support\Str::limit($trendTitle, 55) }}
                                    </a>
                                    <span class="text-muted" style="font-size: 0.73rem;">
                                        {{ $trendReplies }} balasan
                                    </span>
                                </div>
                            @empty
                                <p class="text-muted mb-0" style="font-size: 0.8rem;">
                                    Belum ada topik yang sedang tren.
                                </p>
                            @endforelse
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Area Konten Utama (Hanya Bagian Ini yang Scroll) -->
        <div class="col-lg-9">
            <div class="medigo-main-scroll pt-1 pb-3 pe-lg-2">
                <div class="row g-3">
                    @forelse($discussions as $discussion)
                        @php
                            $discId = $discussion['id_diskusi'] ?? $discussion['id'] ?? null;
                            $discTitle = $discussion['judul_diskusi'] ?? $discussion['title'] ?? 'Diskusi';
                            $discBody = $discussion['isi_diskusi'] ?? $discussion['body'] ?? ($discussion['content'] ?? '');
                            $discCategory = $discussion['kategori'] ?? $discussion['category'] ?? 'Umum';
                            $discImage = $discussion['gambar_diskusi'] ?? $discussion['image_path'] ?? null;
                            $discUserId = $discussion['id_user'] ?? $discussion['user_id'] ?? 0;
                            $discAuthor = $discussion['author_name'] ?? $discussion['nama_user'] ?? 'Pengguna MediGO';
                            $discAuthorAvatar = $discussion['author_avatar'] ?? null;
                            $discCommentsCount = $discussion['comments_count'] ?? 0;

                            $currentUserEmail = Auth::check() ? (Auth::user()->email_user ?? Auth::user()->email ?? null) : null;
                            $currentUserId = Auth::check() ? (Auth::user()->id_user ?? Auth::id()) : null;

                            $isMyDisc = Auth::check() && (
                                (!empty($discussion['author_email']) && $currentUserEmail === $discussion['author_email']) ||
                                ($currentUserId == $discUserId)
                            );

                            // Resolusi avatar penulis
                            $authorAvatarUrl = null;
                            if ($isMyDisc && Auth::check() && !empty(Auth::user()->avatar) && file_exists(public_path(Auth::user()->avatar))) {
                                $authorAvatarUrl = asset(Auth::user()->avatar);
                            } elseif (!empty($discAuthorAvatar)) {
                                if (str_starts_with($discAuthorAvatar, 'http')) {
                                    $authorAvatarUrl = $discAuthorAvatar;
                                } elseif (file_exists(public_path($discAuthorAvatar))) {
                                    $authorAvatarUrl = asset($discAuthorAvatar);
                                }
                            }
                        @endphp

                        <div class="col-12">
                            <div class="card border rounded-4 shadow-sm hover-lift p-3 p-lg-4 bg-white">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge rounded-pill bg-medigo-subtle px-3 py-1 fw-semibold" style="font-size: 0.75rem;">
                                            {{ $discCategory }}
                                        </span>
                                        @if(!empty($discImage) || !empty($discussion['images']))
                                            <span class="badge rounded-pill bg-light text-secondary border px-2 py-1 fw-semibold" style="font-size: 0.72rem;">
                                                <i class="bi bi-image me-1 text-medigo-primary"></i> Foto
                                            </span>
                                        @endif
                                    </div>

                                    <div class="d-flex align-items-center gap-2">
                                        <span class="text-muted" style="font-size: 0.78rem;">
                                            <i class="bi bi-clock me-1"></i>
                                            {{ !empty($discussion['created_at']) ? \Carbon\Carbon::parse($discussion['created_at'])->diffForHumans() : 'Baru saja' }}
                                        </span>

                                        @if(Auth::check() && !$isMyDisc)
                                            <div class="dropdown">
                                                <button class="btn btn-link text-muted p-0 m-0 text-decoration-none" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="bi bi-three-dots-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                                    <li>
                                                        <button class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#reportDiscussionModal" data-discussion-id="{{ $discId }}">
                                                            <i class="bi bi-flag me-2"></i> Laporkan Diskusi
                                                        </button>
                                                    </li>
                                                </ul>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <h5 class="fw-bold text-dark mb-2" style="font-size: 1.05rem;">
                                    <a href="{{ route('discussions.show', $discId) }}" class="text-dark text-decoration-none hover-primary-text">
                                        {{ $discTitle }}
                                    </a>
                                </h5>

                                <p class="text-secondary mb-3" style="font-size: 0.88rem; line-height: 1.6;">
                                    {{ \Illuminate\Support\Str::limit($discBody, 160) }}
                                </p>

                                <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                    <div class="d-flex align-items-center gap-2">
                                        @if($authorAvatarUrl)
                                            <img src="{{ $authorAvatarUrl }}" 
                                                 alt="{{ $discAuthor }}" 
                                                 class="rounded-circle object-fit-cover border shadow-2xs" 
                                                 style="width: 28px; height: 28px;">
                                        @else
                                            <div class="text-white rounded-circle d-flex align-items-center justify-content-center fw-bold"
                                                 style="width: 28px; height: 28px; font-size: 0.75rem; background-color: #002b5e;">
                                                {{ strtoupper(substr($discAuthor, 0, 1)) }}
                                            </div>
                                        @endif
                                        <span class="fw-semibold text-dark" style="font-size: 0.82rem;">
                                            {{ $discAuthor }}
                                        </span>
                                    </div>

                                    <div class="d-flex align-items-center gap-3 text-muted" style="font-size: 0.82rem;">
                                        <span>
                                            <i class="bi bi-chat-left-text me-1" style="color: #002b5e;"></i>
                                            {{ $discCommentsCount }} Komentar
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <!-- Empty State -->
                        <div class="col-12">
                            <div class="card border rounded-4 shadow-sm p-4 p-lg-5 bg-white text-center d-flex flex-column justify-content-center align-items-center" style="min-height: 62vh;">
                                <div class="mb-4">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto"
                                         style="width: 80px; height: 80px; background-color: #f4f6f9; border: 1px solid #e2e8f0;">
                                        <i class="bi bi-chat-square-text fs-2" style="color: #002b5e;"></i>
                                    </div>
                                </div>

                                <h4 class="fw-bold mb-3" style="color: #002b5e;">Belum Ada Diskusi Terdaftar</h4>

                                <p class="text-muted mb-4 mx-auto" style="font-size: 0.95rem; max-width: 540px; line-height: 1.6;">
                                    Saat ini belum ada diskusi atau pengalaman yang dibagikan di kategori ini. Jadilah yang pertama memulai obrolan atau menanyakan seputar fasilitas kesehatan, rujukan BPJS, atau pengalaman medis.
                                </p>

                                <a href="{{ Auth::check() ? route('discussions.create') : route('login') }}"
                                   class="btn rounded-pill px-4 fw-semibold text-white mb-5 shadow-sm d-inline-flex align-items-center gap-2"
                                   style="background-color: #002b5e; padding-top: 0.65rem; padding-bottom: 0.65rem;">
                                    <i class="bi bi-plus-lg"></i>
                                    <span>Mulai Diskusi Pertama</span>
                                </a>

                                <div class="w-50 border-top mb-4"></div>

                                <p class="text-muted fw-bold mb-3" style="font-size: 0.75rem; letter-spacing: 0.6px;">
                                    IDE TOPIK DISKUSI PEMBUKA
                                </p>

                                <div class="d-flex flex-wrap justify-content-center gap-2">
                                    <a href="{{ Auth::check() ? route('discussions.create') : route('login') }}"
                                       class="badge rounded-pill border fw-normal text-muted text-decoration-none px-3 py-2 bg-transparent hover-subtle"
                                       style="font-size: 0.85rem;">
                                        Pertanyaan seputar faskes BPJS
                                    </a>
                                    <a href="{{ Auth::check() ? route('discussions.create') : route('login') }}"
                                       class="badge rounded-pill border fw-normal text-muted text-decoration-none px-3 py-2 bg-transparent hover-subtle"
                                       style="font-size: 0.85rem;">
                                        Rekomendasi dokter spesialis
                                    </a>
                                    <a href="{{ Auth::check() ? route('discussions.create') : route('login') }}"
                                       class="badge rounded-pill border fw-normal text-muted text-decoration-none px-3 py-2 bg-transparent hover-subtle"
                                       style="font-size: 0.85rem;">
                                        Review fasilitas rumah sakit
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforelse
                </div>

                <!-- UI Pagination Custom MediGO -->
                @if(!empty($pagination) && isset($pagination['last_page']) && $pagination['last_page'] > 1)
                    <div class="d-flex justify-content-center mt-4 mb-2">
                        <nav aria-label="Page navigation">
                            <ul class="pagination pagination-sm mb-0 gap-1">
                                <li class="page-item {{ $pagination['current_page'] <= 1 ? 'disabled' : '' }}">
                                    <a class="page-link rounded-pill px-3 py-2 border-0 fw-semibold shadow-sm text-dark hover-primary {{ $pagination['current_page'] <= 1 ? 'opacity-50' : 'bg-white' }}"
                                       href="{{ $pagination['current_page'] <= 1 ? '#' : request()->fullUrlWithQuery(['page' => $pagination['current_page'] - 1]) }}"
                                       style="transition: all 0.2s ease;">
                                        <i class="bi bi-chevron-left me-1" style="font-size: 0.75rem;"></i> Prev
                                    </a>
                                </li>

                                @for($i = 1; $i <= $pagination['last_page']; $i++)
                                    @if($i == 1 || $i == $pagination['last_page'] || ($i >= $pagination['current_page'] - 1 && $i <= $pagination['current_page'] + 1))
                                        <li class="page-item {{ $pagination['current_page'] == $i ? 'active' : '' }}">
                                            <a class="page-link rounded-circle border-0 fw-bold shadow-sm d-flex align-items-center justify-content-center {{ $pagination['current_page'] == $i ? 'btn-medigo-primary text-white' : 'bg-white text-dark hover-primary' }}"
                                               href="{{ request()->fullUrlWithQuery(['page' => $i]) }}"
                                               style="width: 36px; height: 36px; transition: all 0.2s ease; {{ $pagination['current_page'] == $i ? 'background-color: #002b5e !important;' : '' }}">
                                                {{ $i }}
                                            </a>
                                        </li>
                                    @elseif($i == 2 && $pagination['current_page'] > 3)
                                        <li class="page-item disabled"><span class="page-link border-0 bg-transparent text-muted px-1">...</span></li>
                                    @elseif($i == $pagination['last_page'] - 1 && $pagination['current_page'] < $pagination['last_page'] - 2)
                                        <li class="page-item disabled"><span class="page-link border-0 bg-transparent text-muted px-1">...</span></li>
                                    @endif
                                @endfor

                                <li class="page-item {{ $pagination['current_page'] >= $pagination['last_page'] ? 'disabled' : '' }}">
                                    <a class="page-link rounded-pill px-3 py-2 border-0 fw-semibold shadow-sm text-dark hover-primary {{ $pagination['current_page'] >= $pagination['last_page'] ? 'opacity-50' : 'bg-white' }}"
                                       href="{{ $pagination['current_page'] >= $pagination['last_page'] ? '#' : request()->fullUrlWithQuery(['page' => $pagination['current_page'] + 1]) }}"
                                       style="transition: all 0.2s ease;">
                                        Next <i class="bi bi-chevron-right ms-1" style="font-size: 0.75rem;"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                @endif
            </div>
        </div>

    </div>
</div>

<style>
    /* Cegah ancestor memblokir position: sticky */
    html, body, main, #app {
        overflow-x: clip !important;
    }

    @media (min-width: 992px) {
        /* Sidebar tetap diam di bawah navbar (tidak menimpa navbar & tidak ikut tergulung) */
        .medigo-sidebar-sticky {
            position: -webkit-sticky;
            position: sticky;
            top: 88px;
            z-index: 10;
        }

        /* Hanya area main section yang bisa di-scroll secara independen */
        .medigo-main-scroll {
            max-height: calc(100vh - 110px);
            overflow-y: auto;
            overflow-x: hidden;
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 transparent;
        }

        .medigo-main-scroll::-webkit-scrollbar {
            width: 6px;
        }

        .medigo-main-scroll::-webkit-scrollbar-track {
            background: transparent;
        }

        .medigo-main-scroll::-webkit-scrollbar-thumb {
            background-color: #cbd5e1;
            border-radius: 999px;
        }

        .medigo-main-scroll::-webkit-scrollbar-thumb:hover {
            background-color: #94a3b8;
        }
    }

    .bg-medigo-subtle {
        background-color: #e6f0fa !important;
        color: #002b5e !important;
    }

    .hover-subtle:hover {
        background-color: #f4f7fb;
        color: #002b5e !important;
    }

    .hover-primary-text:hover {
        color: #002b5e !important;
    }

    .page-link.hover-primary:hover {
        background-color: #e6f0fa !important;
        color: #002b5e !important;
    }

    .page-item.active .page-link {
        z-index: 3;
    }

    .hover-lift {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .hover-lift:hover {
        transform: translateY(-2px);
        box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .08) !important;
    }
</style>

<!-- Modal Laporkan Diskusi -->
<div class="modal fade" id="reportDiscussionModal" tabindex="-1" aria-labelledby="reportDiscussionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form id="reportDiscussionForm" action="" method="POST">
                @csrf
                <input type="hidden" name="discussion_id" id="report_discussion_id">
                <div class="modal-header border-bottom pb-3">
                    <h5 class="modal-title fw-bold text-dark" id="reportDiscussionModalLabel">Laporkan Diskusi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-4">
                    <p class="text-muted mb-3" style="font-size: 0.9rem;">
                        Beri tahu kami mengapa diskusi ini menurut Anda tidak pantas. Laporan Anda akan ditinjau oleh tim kami.
                    </p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark" style="font-size: 0.88rem;">Alasan Pelaporan</label>
                        <select name="reason_preset" class="form-select rounded-3 mb-2" id="reportReasonPreset">
                            <option value="Spam atau Promosi Komersial">Spam atau Promosi Komersial</option>
                            <option value="Informasi Kesehatan Menyesatkan (Hoax)">Informasi Kesehatan Menyesatkan (Hoax)</option>
                            <option value="Ujaran Kebencian atau Pelecehan">Ujaran Kebencian atau Pelecehan</option>
                            <option value="Konten Tidak Pantas / SARA">Konten Tidak Pantas / SARA</option>
                            <option value="Lainnya">Lainnya...</option>
                        </select>
                        <textarea name="reason" id="reportReasonText" class="form-control rounded-3 mt-2 d-none" rows="3" placeholder="Tuliskan alasan spesifik Anda..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top pt-3">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4 fw-semibold">Kirim Laporan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const reportModal = document.getElementById('reportDiscussionModal');
        if (reportModal) {
            reportModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const discussionId = button.getAttribute('data-discussion-id');
                const form = document.getElementById('reportDiscussionForm');

                form.action = `/discussions/${discussionId}/report`;
            });

            const presetSelect = document.getElementById('reportReasonPreset');
            const reasonText = document.getElementById('reportReasonText');

            reasonText.value = presetSelect.value;

            presetSelect.addEventListener('change', function() {
                if (this.value === 'Lainnya') {
                    reasonText.classList.remove('d-none');
                    reasonText.value = '';
                    reasonText.required = true;
                } else {
                    reasonText.classList.add('d-none');
                    reasonText.value = this.value;
                }
            });
        }
    });
</script>
@endsection