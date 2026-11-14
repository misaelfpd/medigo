@extends('layouts.app')

@section('title', 'Forum Diskusi Kesehatan - MediGO')

@push('styles')
    @vite(['resources/css/pages/forum-list.css', 'resources/js/pages/forum-list.js'])
@endpush

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

<div class="container py-3 py-lg-4">
    <div class="row g-4 align-items-start">

        <!-- Sidebar Kiri (Desktop Only: Diam di Tempat / Tidak Ikut Scroll) -->
        <div class="col-lg-3 d-none d-lg-block">
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
                <div class="card border rounded-4 shadow-sm mb-3 bg-white">
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

                <!-- Card Diskusi Faskes Dekat Anda: judul diskusi + nama faskes di sekitar user -->
                <div class="card border rounded-4 shadow-sm mb-3 bg-white mg-nearby-card" id="forumNearbyCard"
                     data-url="{{ route('forum.nearby-discussions') }}"
                     data-discussion-url="{{ url('/discussions') }}"
                     data-faskes-url="{{ url('/faskes') }}">
                    <div class="card-body p-3">
                        <h6 class="fw-bold mb-2 px-2 text-dark d-flex align-items-center gap-2" style="font-size: 0.78rem; letter-spacing: 0.6px;">
                            <i class="bi bi-geo-alt"></i>
                            <span id="forumNearbyHeading">DISKUSI FASKES DEKAT ANDA</span>
                        </h6>
                        <div class="px-2" id="forumNearbyList">
                            <div class="text-muted" style="font-size: 0.8rem;">
                                <span class="spinner-border spinner-border-sm me-1"></span> Memuat diskusi...
                            </div>
                        </div>
                        <div class="px-2 mt-2">
                            <button type="button" class="btn btn-sm mg-btn-outline px-3 d-none" id="forumNearbyLocateBtn">
                                <i class="bi bi-crosshair me-1"></i> Gunakan Lokasi Saya
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Area Konten Utama (Hanya Bagian Ini yang Scroll) -->
        <div class="col-lg-9">
            <div class="medigo-main-scroll pt-1 pb-3 pe-lg-2">
                <!-- Floating Mobile Filter Component (d-lg-none) -->
                <div class="d-lg-none mg-forum-mobile-filter-wrapper sticky-top mb-3">
                    <div class="mg-forum-mobile-filter-card card border rounded-4 shadow-sm p-2 px-3">
                        <!-- Dropdown Kategori -->
                        <div class="mb-2">
                            <select id="mobileForumCategorySelect" class="form-select form-select-sm rounded-pill px-3 py-1-5 border" style="font-size: 0.88rem; background-color: #f8fafc;">
                                <option value="{{ route('discussions', array_filter(['sort' => $sort, 'filter' => $filter])) }}">Semua Kategori</option>
                                @if(Auth::check())
                                    <option value="{{ route('discussions', array_filter(['tab' => 'my_discussions', 'sort' => $sort, 'filter' => $filter])) }}" {{ ($tab ?? '') === 'my_discussions' ? 'selected' : '' }}>Diskusi Saya</option>
                                @endif
                                @foreach($forumCategories as $cat)
                                    <option value="{{ route('discussions', array_filter(['category' => $cat['value'], 'sort' => $sort, 'filter' => $filter])) }}" {{ ($category ?? '') === $cat['value'] ? 'selected' : '' }}>
                                        {{ $cat['label'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Filter Chips: Semua, Sedang Tren, Faskes Terdekat -->
                        <div class="d-flex gap-2 overflow-x-auto pb-1 mg-filter-chips-scroll" style="scrollbar-width: none;">
                            <a href="{{ route('discussions', array_filter(['category' => $category, 'tab' => $tab])) }}"
                               class="btn btn-sm rounded-pill px-3 py-1 text-nowrap {{ empty($sort) && empty($filter) ? 'btn-medigo-primary text-white shadow-sm' : 'btn-outline-secondary border' }}"
                               style="{{ empty($sort) && empty($filter) ? 'background-color: #002b5e; border-color: #002b5e;' : '' }}">
                                Semua
                            </a>
                            <a href="{{ route('discussions', array_filter(['category' => $category, 'sort' => 'terpopuler'])) }}"
                               class="btn btn-sm rounded-pill px-3 py-1 text-nowrap {{ ($sort ?? '') === 'terpopuler' ? 'btn-medigo-primary text-white shadow-sm' : 'btn-outline-secondary border' }}"
                               style="{{ ($sort ?? '') === 'terpopuler' ? 'background-color: #002b5e; border-color: #002b5e;' : '' }}">
                                <i class="bi bi-graph-up-arrow text-danger me-1"></i> Sedang Tren
                            </a>
                            <a href="{{ route('discussions', array_filter(['category' => $category, 'filter' => 'terdekat'])) }}"
                               id="mobileChipFaskesTerdekat"
                               class="btn btn-sm rounded-pill px-3 py-1 text-nowrap {{ ($filter ?? '') === 'terdekat' ? 'btn-medigo-primary text-white shadow-sm' : 'btn-outline-secondary border' }}"
                               style="{{ ($filter ?? '') === 'terdekat' ? 'background-color: #002b5e; border-color: #002b5e;' : '' }}">
                                <i class="bi bi-geo-alt text-primary me-1"></i> Faskes Terdekat
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Tombol Mengambang Tambah Diskusi di Pojok Kanan Bawah Mobile (+) -->
                <div class="d-lg-none">
                    <a href="{{ Auth::check() ? route('discussions.create') : route('login') }}"
                       class="mg-forum-fab shadow-lg"
                       title="Tambah Diskusi Baru"
                       aria-label="Tambah Diskusi">
                        <i class="bi bi-plus-lg"></i>
                    </a>
                </div>

                @if(!empty($faskesFilterId))
                    <div class="alert alert-light border d-flex align-items-center justify-content-between gap-2 py-2 px-3 mb-3 rounded-3" style="font-size: 0.85rem;">
                        <span><i class="bi bi-funnel me-1"></i> Menampilkan diskusi yang membahas faskes pilihan.</span>
                        <a href="{{ route('discussions') }}" class="text-decoration-none fw-semibold">Hapus filter</a>
                    </div>
                @elseif(($filter ?? '') === 'terdekat')
                    <div class="alert alert-light border d-flex align-items-center justify-content-between gap-2 py-2 px-3 mb-3 rounded-3 shadow-xs" style="font-size: 0.85rem;">
                        <span><i class="bi bi-geo-alt-fill text-primary me-1"></i> Menampilkan diskusi fasilitas kesehatan terdekat dari lokasi Anda (diurutkan berdasarkan jarak terdekat).</span>
                        <a href="{{ route('discussions') }}" class="text-decoration-none fw-semibold">Hapus filter</a>
                    </div>
                @endif
                <div class="row g-3" id="forumDiscussionsList">
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

                            // Thumbnail kartu: foto pertama yang diunggah di diskusi (dari backend)
                            $discImagesList = $discussion['images_list'] ?? [];
                            if (empty($discImagesList) && !empty($discussion['images'])) {
                                $decodedImages = is_array($discussion['images']) ? $discussion['images'] : json_decode($discussion['images'], true);
                                $discImagesList = is_array($decodedImages) ? array_values($decodedImages) : [];
                            }
                            $discThumbnail = $discussion['thumbnail_url'] ?? ($discImagesList[0] ?? $discImage);
                            $discPhotoCount = max(count($discImagesList), $discThumbnail ? 1 : 0);

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

                        @php
                            $discMention = $discussion['mentioned_faskes'] ?? null;
                            $discLikes = (int) ($discussion['likes_count'] ?? 0);
                            $discLikedByMe = (bool) ($discussion['liked_by_me'] ?? false);
                            $discMentionName = is_array($discMention) ? ($discMention['nama_faskes'] ?? null) : null;
                            $discMentionId = is_array($discMention) ? ($discMention['id_faskes'] ?? null) : null;
                            $discDistance = !empty($discussion['distance_km']) ? $discussion['distance_km'] : (is_array($discMention) && !empty($discMention['distance_km']) ? $discMention['distance_km'] : null);
                        @endphp

                        <div class="col-12">
                            <article class="mg-discussion-card">
                                @if(!empty($discThumbnail))
                                    <a href="{{ route('discussions.show', $discId) }}" class="mg-discussion-cover">
                                        <img src="{{ $discThumbnail }}"
                                             alt="Foto diskusi {{ $discTitle }}"
                                             loading="lazy"
                                             onerror="this.closest('a').classList.add('d-none');">
                                        @if($discPhotoCount > 1)
                                            <span class="mg-discussion-cover-count">+{{ $discPhotoCount - 1 }} foto</span>
                                        @endif
                                    </a>
                                @endif

                                <div class="mg-discussion-meta">
                                    <span class="mg-chip mg-chip--category">{{ $discCategory }}</span>
                                    @if($discMentionName)
                                        <a href="{{ $discMentionId ? route('faskes.detail', $discMentionId) : '#' }}" class="mg-chip mg-chip--mention" title="Faskes yang dibahas">
                                            <i class="bi bi-hospital"></i> {{ $discMentionName }}
                                        </a>
                                        @if(!empty($discDistance))
                                            <span class="badge bg-light text-primary border rounded-pill px-2 py-1 small fw-semibold" title="Jarak faskes dari lokasi Anda">
                                                <i class="bi bi-geo-alt-fill text-danger me-1"></i>{{ number_format((float) $discDistance, 1) }} km
                                            </span>
                                        @endif
                                    @endif
                                    <span class="mg-discussion-author">
                                        @if($authorAvatarUrl)
                                            <img src="{{ $authorAvatarUrl }}" alt="{{ $discAuthor }}">
                                        @else
                                            <i class="bi bi-person"></i>
                                        @endif
                                        {{ $discAuthor }}
                                    </span>
                                    <span class="mg-discussion-time">
                                        &bull; {{ !empty($discussion['created_at']) ? \Carbon\Carbon::parse($discussion['created_at'])->diffForHumans() : 'Baru saja' }}
                                    </span>

                                    @if(Auth::check() && !$isMyDisc)
                                        <div class="dropdown ms-auto">
                                            <button class="btn btn-link text-muted p-0 m-0 text-decoration-none" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Opsi diskusi">
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

                                <h2 class="mg-discussion-title">
                                    <a href="{{ route('discussions.show', $discId) }}">{{ $discTitle }}</a>
                                </h2>
                                <p class="mg-discussion-excerpt">{{ \Illuminate\Support\Str::limit($discBody, 200) }}</p>

                                <div class="mg-discussion-footer">
                                    <a href="{{ route('discussions.show', $discId) }}#comments" class="mg-discussion-stat">
                                        <i class="bi bi-chat-square"></i> {{ $discCommentsCount }} Balasan
                                    </a>
                                    <span class="mg-discussion-stat mg-discussion-likes ms-auto {{ $discLikedByMe ? 'is-liked' : '' }}" title="Jumlah suka">
                                        <i class="bi bi-arrow-up"></i> {{ $discLikes }}
                                    </span>
                                </div>
                            </article>
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

                <!-- Lazy Loading Controller -->
                @php
                    $currentPage = (int) ($pagination['current_page'] ?? 1);
                    $lastPage = (int) ($pagination['last_page'] ?? 1);
                    $hasMore = $currentPage < $lastPage;
                @endphp

                <div id="forumLazyLoadingTrigger"
                     class="text-center py-4 my-2 {{ !$hasMore ? 'd-none' : '' }}"
                     data-current-page="{{ $currentPage }}"
                     data-last-page="{{ $lastPage }}"
                     data-url="{{ route('discussions') }}"
                     data-query-params='@json(request()->except('page'))'>
                    <div id="forumLazySpinner" class="d-none py-2">
                        <div class="spinner-border text-primary" role="status" style="width: 2rem; height: 2rem; color: #002b5e !important;">
                            <span class="visually-hidden">Memuat...</span>
                        </div>
                        <p class="text-muted small mt-2 mb-0">Memuat diskusi lebih banyak...</p>
                    </div>
                    <button type="button" id="btnForumLoadMore" class="btn btn-medigo-outline rounded-pill px-4 py-2 fw-semibold shadow-sm" style="border-color: #002b5e; color: #002b5e;">
                        <i class="bi bi-arrow-down-circle me-1"></i> Muat Lebih Banyak Diskusi
                    </button>
                </div>

                <div id="forumEndOfList" class="text-center py-3 text-muted small {{ empty($discussions) || $hasMore ? 'd-none' : '' }}">
                    <i class="bi bi-check2-circle me-1 text-success"></i> Anda telah melihat semua diskusi.
                </div>

                <!-- Fallback Pagination (Hanya jika Javascript Dinonaktifkan) -->
                <noscript>
                    @if(!empty($pagination) && isset($pagination['last_page']) && $pagination['last_page'] > 1)
                        <div class="d-flex justify-content-center mt-4 mb-2">
                            <nav aria-label="Page navigation">
                                <ul class="pagination pagination-sm mb-0 gap-1">
                                    <li class="page-item {{ $pagination['current_page'] <= 1 ? 'disabled' : '' }}">
                                        <a class="page-link rounded-pill px-3 py-2 border-0 fw-semibold shadow-sm text-dark hover-primary {{ $pagination['current_page'] <= 1 ? 'opacity-50' : 'bg-white' }}"
                                           href="{{ $pagination['current_page'] <= 1 ? '#' : request()->fullUrlWithQuery(['page' => $pagination['current_page'] - 1]) }}">
                                            <i class="bi bi-chevron-left me-1" style="font-size: 0.75rem;"></i> Prev
                                        </a>
                                    </li>

                                    @for($i = 1; $i <= $pagination['last_page']; $i++)
                                        @if($i == 1 || $i == $pagination['last_page'] || ($i >= $pagination['current_page'] - 1 && $i <= $pagination['current_page'] + 1))
                                            <li class="page-item {{ $pagination['current_page'] == $i ? 'active' : '' }}">
                                                <a class="page-link rounded-circle border-0 fw-bold shadow-sm d-flex align-items-center justify-content-center {{ $pagination['current_page'] == $i ? 'btn-medigo-primary text-white' : 'bg-white text-dark hover-primary' }}"
                                                   href="{{ request()->fullUrlWithQuery(['page' => $i]) }}"
                                                   style="width: 36px; height: 36px; {{ $pagination['current_page'] == $i ? 'background-color: #002b5e !important;' : '' }}">
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
                                           href="{{ $pagination['current_page'] >= $pagination['last_page'] ? '#' : request()->fullUrlWithQuery(['page' => $pagination['current_page'] + 1]) }}">
                                            Next <i class="bi bi-chevron-right ms-1" style="font-size: 0.75rem;"></i>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    @endif
                </noscript>
            </div>
        </div>

    </div>
</div>


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
                    <div class="mb-1">
                        <label for="reportNotesText" class="form-label fw-semibold text-dark" style="font-size: 0.88rem;">
                            Catatan Tambahan <span class="text-muted fw-normal">(opsional)</span>
                        </label>
                        <textarea name="notes" id="reportNotesText" class="form-control rounded-3" rows="3" maxlength="500"
                                  placeholder="Jelaskan detail pelanggaran agar admin lebih mudah meninjau..."></textarea>
                        <div class="form-text text-end" style="font-size: 0.72rem;">Maks. 500 karakter</div>
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

@endsection