@extends('layouts.admin')

@section('title', 'Moderasi Forum - Admin MediGO')
@section('page_title', 'Moderasi Forum')

@section('content')
<div class="container-fluid px-4 py-3">

    {{-- Breadcrumb & Top Bar Context --}}
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 text-muted small mb-1">
                <span class="fw-semibold text-dark">Admin Medigo</span>
                <i class="bi bi-chevron-right" style="font-size: 0.75rem;"></i>
                <span class="text-secondary">Moderasi Forum</span>
            </div>
            <h3 class="fw-bold text-dark mb-1" style="font-size: 1.6rem; letter-spacing: -0.02em;">Moderasi Forum</h3>
            <p class="text-secondary small mb-0">Kelola diskusi komunitas, tinjau konten yang dilaporkan, dan pertahankan panduan platform.</p>
        </div>

        {{-- Filter Pills & Sort Dropdown --}}
        <div class="d-flex flex-column align-items-end gap-2">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                {{-- Filter: Tren --}}
                <a href="{{ route('admin.forum', array_merge(request()->query(), ['tab' => 'trending', 'page' => 1])) }}" 
                   class="btn btn-sm rounded-pill px-3 py-1.5 fw-semibold d-inline-flex align-items-center gap-1.5 transition-all text-decoration-none {{ $tab === 'trending' ? 'btn-medigo-navy text-white shadow-xs' : 'btn-outline-secondary bg-white text-dark' }}">
                    <i class="bi bi-graph-up-arrow"></i>
                    <span>Tren</span>
                </a>

                {{-- Filter: Terbaru --}}
                <a href="{{ route('admin.forum', array_merge(request()->query(), ['tab' => 'recent', 'page' => 1])) }}" 
                   class="btn btn-sm rounded-pill px-3 py-1.5 fw-semibold d-inline-flex align-items-center gap-1.5 transition-all text-decoration-none {{ $tab === 'recent' ? 'btn-medigo-navy text-white shadow-xs' : 'btn-outline-secondary bg-white text-dark' }}">
                    <i class="bi bi-check2-circle"></i>
                    <span>Terbaru</span>
                </a>

                {{-- Filter: Dilaporkan --}}
                <a href="{{ route('admin.forum', array_merge(request()->query(), ['tab' => 'reported', 'page' => 1])) }}" 
                   class="btn btn-sm rounded-pill px-3 py-1.5 fw-semibold d-inline-flex align-items-center gap-1.5 transition-all text-decoration-none {{ $tab === 'reported' ? 'btn-danger text-white shadow-xs' : 'btn-outline-danger bg-white text-danger' }}">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <span>Dilaporkan ({{ $stats['pending_reports'] }})</span>
                </a>
            </div>

            {{-- Sort Select Dropdown --}}
            <form action="{{ route('admin.forum') }}" method="GET" class="d-inline-flex align-items-center" id="sortForm">
                <input type="hidden" name="tab" value="{{ $tab }}">
                @if(!empty($search))
                    <input type="hidden" name="search" value="{{ $search }}">
                @endif
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0 text-muted small pe-1">
                        Urutkan berdasarkan:
                    </span>
                    <select name="sort" class="form-select border-start-0 ps-1 rounded-end-3 text-dark fw-semibold small" onchange="this.form.submit()" style="cursor: pointer;">
                        <option value="latest" {{ $sort === 'latest' ? 'selected' : '' }}>Tanggal (Terbaru)</option>
                        <option value="oldest" {{ $sort === 'oldest' ? 'selected' : '' }}>Tanggal (Terlama)</option>
                        <option value="reports_desc" {{ $sort === 'reports_desc' ? 'selected' : '' }}>Jumlah Laporan Terbanyak</option>
                        <option value="comments_desc" {{ $sort === 'comments_desc' ? 'selected' : '' }}>Komentar Terbanyak</option>
                    </select>
                </div>
            </form>
        </div>
    </div>

    {{-- Stats Metrics Cards Row --}}
    <div class="row g-3 mb-4">
        {{-- Card 1: Utas Aktif --}}
        <div class="col-12 col-md-4">
            <div class="card border rounded-4 p-4 bg-white shadow-2xs h-100" style="border-color: #e2e8f0 !important;">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-uppercase fw-bold text-muted" style="font-size: 0.72rem; letter-spacing: 0.05em;">UTAS AKTIF</span>
                    <div class="rounded-3 p-2 bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                        <i class="bi bi-chat-left-text-fill fs-5"></i>
                    </div>
                </div>
                <div class="fw-bold text-dark mb-1" style="font-size: 2rem; line-height: 1.1;">
                    {{ number_format($stats['active_threads']) }}
                </div>
                <div class="text-success small fw-semibold d-flex align-items-center gap-1">
                    <i class="bi bi-arrow-up-short fs-5"></i>
                    <span>+5.2% minggu ini</span>
                </div>
            </div>
        </div>

        {{-- Card 2: Laporan Tertunda --}}
        <div class="col-12 col-md-4">
            <div class="card border rounded-4 p-4 bg-white shadow-2xs h-100" style="border-color: #e2e8f0 !important;">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-uppercase fw-bold text-muted" style="font-size: 0.72rem; letter-spacing: 0.05em;">LAPORAN TERTUNDA</span>
                    <div class="rounded-3 p-2 bg-danger-subtle text-danger d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                        <i class="bi bi-flag-fill fs-5"></i>
                    </div>
                </div>
                <div class="fw-bold text-danger mb-1" style="font-size: 2rem; line-height: 1.1;">
                    {{ $stats['pending_reports'] }}
                </div>
                <div class="text-muted small">
                    Memerlukan perhatian segera
                </div>
            </div>
        </div>

        {{-- Card 3: Tindakan Otomatis --}}
        <div class="col-12 col-md-4">
            <div class="card border rounded-4 p-4 bg-white shadow-2xs h-100" style="border-color: #e2e8f0 !important;">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-uppercase fw-bold text-muted" style="font-size: 0.72rem; letter-spacing: 0.05em;">TINDAKAN OTOMATIS</span>
                    <div class="rounded-3 p-2 bg-info-subtle text-info d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                        <i class="bi bi-robot fs-5"></i>
                    </div>
                </div>
                <div class="fw-bold text-dark mb-1" style="font-size: 2rem; line-height: 1.1;">
                    {{ number_format($stats['auto_actions']) }}
                </div>
                <div class="text-muted small">
                    Spam disaring dalam 24 jam terakhir
                </div>
            </div>
        </div>
    </div>

    {{-- Main Section: Antrean Moderasi --}}
    <div class="card border rounded-4 bg-white shadow-2xs overflow-hidden mb-4" style="border-color: #e2e8f0 !important;">
        {{-- Card Header with Title and Search Toolbar --}}
        <div class="p-3 px-4 border-bottom bg-white d-flex justify-content-between align-items-center flex-wrap gap-3">
            <h5 class="fw-bold text-dark m-0" style="font-size: 1.15rem;">Antrean Moderasi</h5>

            <form action="{{ route('admin.forum') }}" method="GET" class="d-flex align-items-center gap-2 m-0">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <input type="hidden" name="sort" value="{{ $sort }}">
                <div class="input-group input-group-sm" style="min-width: 260px;">
                    <span class="input-group-text bg-white border-end-0 text-muted ps-3">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" name="search" class="form-control border-start-0 py-1.5" placeholder="Cari utas atau pengguna..." value="{{ $search }}" autocomplete="off">
                </div>
                @if(!empty($search))
                    <a href="{{ route('admin.forum', ['tab' => $tab, 'sort' => $sort]) }}" class="btn btn-sm btn-outline-secondary rounded-3" title="Reset Cari">
                        <i class="bi bi-x"></i>
                    </a>
                @endif
            </form>
        </div>

        {{-- Queue List Items --}}
        <div class="moderation-queue-list">
            @forelse($threads as $item)
                @php
                    $isComment = ($item['item_type'] ?? '') === 'comment';
                    $itemId = $isComment ? ($item['id_tanggapan'] ?? $item['item_id']) : ($item['id_diskusi'] ?? $item['item_id']);
                    $itemPrefix = $isComment ? 'comment_' : 'disc_';
                    $modalTakedownId = 'modalTakedown_' . $itemPrefix . $itemId . '_' . $loop->index;
                    $modalBlockUserId = 'modalBlockUser_' . ($item['author_id'] ?? $item['id_user']) . '_' . $loop->index;
                @endphp

                <div class="moderation-item-row p-4 border-bottom d-flex align-items-start gap-3 transition-all hover-bg-light">
                    {{-- Status Icon on Left --}}
                    <div class="pt-1 flex-shrink-0">
                        <i class="bi {{ $item['icon'] }} fs-4" style="color: {{ $item['icon_color'] }};"></i>
                    </div>

                    {{-- Main Content --}}
                    <div class="flex-grow-1 min-w-0">
                        {{-- Meta Bar: Badges, Time & Reporter --}}
                        <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge {{ $item['badge_class'] }} rounded-2 px-2.5 py-1 text-uppercase fw-bold" style="font-size: 0.68rem; letter-spacing: 0.04em;">
                                    {{ $item['badge_text'] }}
                                </span>
                                @if($isComment)
                                    <span class="badge rounded-2 px-2 py-1 fw-bold text-uppercase border" style="background-color: #f1f5f9; color: #475569; font-size: 0.68rem; border-color: #cbd5e1 !important;">
                                        <i class="bi bi-chat-left-quote me-1"></i>KOMENTAR
                                    </span>
                                @else
                                    <span class="badge rounded-2 px-2 py-1 fw-bold text-uppercase border" style="background-color: #e0f2fe; color: #0369a1; font-size: 0.68rem; border-color: #bae6fd !important;">
                                        <i class="bi bi-chat-text me-1"></i>UTAS DISKUSI
                                    </span>
                                @endif
                                <span class="text-muted small d-inline-flex align-items-center gap-1">
                                    <i class="bi bi-clock"></i>
                                    <span>{{ $item['time_ago'] }}</span>
                                </span>
                            </div>

                            <span class="text-muted small fw-medium">
                                {{ $item['reporter_summary'] }}
                            </span>
                        </div>

                        {{-- Title / Thread Context --}}
                        @if($isComment)
                            <div class="mb-2">
                                <span class="text-muted small"><i class="bi bi-chat-left-quote me-1"></i>Komentar pada utas:</span>
                                <a href="{{ route('discussions.show', ['id_diskusi' => $item['id_diskusi']]) }}" target="_blank" class="fw-bold text-dark text-decoration-none hover-primary ms-1" style="font-size: 1.02rem;">
                                    "{{ $item['judul_diskusi'] }}"
                                </a>
                                <a href="{{ route('discussions.show', ['id_diskusi' => $item['id_diskusi']]) }}" target="_blank" class="text-primary ms-1" title="Lihat Utas di Tab Baru">
                                    <i class="bi bi-box-arrow-up-right" style="font-size: 0.75rem;"></i>
                                </a>
                            </div>
                        @else
                            <h5 class="fw-bold text-dark mb-1" style="font-size: 1.05rem;">
                                <a href="{{ route('discussions.show', ['id_diskusi' => $item['id_diskusi']]) }}" target="_blank" class="text-dark text-decoration-none hover-primary">
                                    {{ $item['judul_diskusi'] }}
                                </a>
                                <a href="{{ route('discussions.show', ['id_diskusi' => $item['id_diskusi']]) }}" target="_blank" class="text-primary ms-1" title="Lihat Utas di Tab Baru">
                                    <i class="bi bi-box-arrow-up-right" style="font-size: 0.75rem;"></i>
                                </a>
                            </h5>
                        @endif

                        {{-- Content Excerpt --}}
                        <div class="text-secondary small mb-3 {{ $isComment ? 'p-3 rounded-3 bg-light border-start border-3 border-primary' : '' }}" style="line-height: 1.5; font-size: 0.88rem;">
                            {{ Str::limit(strip_tags($item['isi_konten'] ?? $item['isi_diskusi']), 300) }}
                        </div>

                        {{-- Reason Box if reported --}}
                        @if(!empty($item['latest_report_reason']))
                            <div class="p-2.5 px-3 rounded-3 bg-light border mb-3 small text-muted d-flex align-items-start gap-2">
                                <i class="bi bi-info-circle text-danger mt-0.5"></i>
                                <div>
                                    <strong class="text-dark">Alasan Laporan:</strong> {{ $item['latest_report_reason'] }}
                                </div>
                            </div>
                        @endif

                        {{-- Author Info & Category --}}
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            {{-- Author Avatar / Initial --}}
                            @if(!empty($item['author_avatar']))
                                <img src="{{ $item['author_avatar'] }}" alt="{{ $item['author_name'] }}" class="rounded-circle border" style="width: 24px; height: 24px; object-fit: cover;">
                            @else
                                <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center fw-bold" style="width: 24px; height: 24px; font-size: 0.7rem;">
                                    {{ strtoupper(substr($item['author_name'], 0, 2)) }}
                                </div>
                            @endif

                            <span class="fw-semibold text-dark small">{{ $item['author_name'] }}</span>
                            <span class="text-muted small">•</span>
                            <span class="badge bg-light text-secondary border small fw-normal">{{ $item['kategori'] }}</span>

                            @if(($item['author_role'] ?? 'user') === 'blocked')
                                <span class="badge bg-danger-subtle text-danger small">Akun Diblokir</span>
                            @endif
                        </div>
                    </div>

                    {{-- Action Buttons on Right --}}
                    <div class="d-flex flex-column gap-2 flex-shrink-0 align-items-stretch" style="min-width: 170px;">
                        {{-- Hapus Postingan / Hapus Komentar (Takedown) --}}
                        <button type="button" 
                                class="btn btn-danger btn-sm rounded-3 py-2 fw-semibold d-flex align-items-center justify-content-center gap-1.5 shadow-xs" 
                                data-bs-toggle="modal" 
                                data-bs-target="#{{ $modalTakedownId }}"
                                style="background-color: #b91c1c; border-color: #b91c1c;">
                            <i class="bi bi-trash"></i>
                            <span>{{ $isComment ? 'Hapus Komentar' : 'Hapus Postingan' }}</span>
                        </button>

                        {{-- Blokir Pengguna --}}
                        @if(($item['author_role'] ?? 'user') !== 'blocked')
                            <button type="button" 
                                    class="btn btn-outline-dark btn-sm rounded-3 py-2 fw-semibold d-flex align-items-center justify-content-center gap-1.5" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#{{ $modalBlockUserId }}">
                                <i class="bi bi-slash-circle"></i>
                                <span>Blokir Pengguna</span>
                            </button>
                        @endif

                        {{-- Abaikan Laporan (Jika ada laporan) --}}
                        @if(($item['reports_count'] ?? 0) > 0)
                            @if($isComment)
                                <form action="{{ route('admin.forum.comments.dismiss', ['id' => $item['id_tanggapan']]) }}" method="POST" class="m-0">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-secondary btn-sm w-100 rounded-3 py-1.5 small fw-semibold" title="Tandai laporan komentar bukan pelanggaran">
                                        <i class="bi bi-x-circle me-1"></i>Abaikan
                                    </button>
                                </form>
                            @else
                                <form action="{{ route('admin.forum.discussions.dismiss', ['id' => $item['id_diskusi']]) }}" method="POST" class="m-0">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-secondary btn-sm w-100 rounded-3 py-1.5 small fw-semibold" title="Tandai laporan diskusi bukan pelanggaran">
                                        <i class="bi bi-x-circle me-1"></i>Abaikan
                                    </button>
                                </form>
                            @endif
                        @endif
                    </div>
                </div>

                {{-- MODAL KONFIRMASI HAPUS / TAKEDOWN --}}
                <div class="modal fade" id="{{ $modalTakedownId }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0 rounded-4 shadow">
                            <div class="modal-header border-bottom px-4 py-3">
                                <h6 class="modal-title fw-bold text-dark m-0 d-flex align-items-center gap-2">
                                    <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
                                    <span>{{ $isComment ? 'Hapus Komentar Forum' : 'Hapus Postingan Diskusi' }}</span>
                                </h6>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-4 text-center">
                                <div class="mb-3">
                                    <i class="bi bi-trash-fill text-danger" style="font-size: 3rem;"></i>
                                </div>
                                @if($isComment)
                                    <h6 class="fw-bold text-dark mb-2">Hapus komentar dari "{{ $item['author_name'] }}"?</h6>
                                    <p class="text-muted small mb-0">
                                        Komentar ini akan dihapus dari forum dan semua laporan terkait akan ditandai selesai. Tindakan ini dicatat pada log moderasi admin.
                                    </p>
                                @else
                                    <h6 class="fw-bold text-dark mb-2">Hapus "{{ $item['judul_diskusi'] }}"?</h6>
                                    <p class="text-muted small mb-0">
                                        Postingan ini akan di-takedown dari forum publik dan semua laporan terkait akan ditandai selesai. Tindakan ini dicatat pada log moderasi admin.
                                    </p>
                                @endif
                            </div>
                            <div class="modal-footer border-top px-4 py-3 bg-light d-flex justify-content-between">
                                <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                                @if($isComment)
                                    <form action="{{ route('admin.forum.comments.takedown', ['id' => $item['id_tanggapan']]) }}" method="POST" class="m-0">
                                        @csrf
                                        <button type="submit" class="btn btn-danger rounded-pill px-4 fw-semibold shadow-xs" style="background-color: #b91c1c; border-color: #b91c1c;">
                                            Ya, Hapus Komentar
                                        </button>
                                    </form>
                                @else
                                    <form action="{{ route('admin.forum.discussions.takedown', ['id' => $item['id_diskusi']]) }}" method="POST" class="m-0">
                                        @csrf
                                        <button type="submit" class="btn btn-danger rounded-pill px-4 fw-semibold shadow-xs" style="background-color: #b91c1c; border-color: #b91c1c;">
                                            Ya, Hapus Postingan
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- MODAL KONFIRMASI BLOKIR PENGGUNA --}}
                <div class="modal fade" id="{{ $modalBlockUserId }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0 rounded-4 shadow">
                            <div class="modal-header border-bottom px-4 py-3">
                                <h6 class="modal-title fw-bold text-dark m-0 d-flex align-items-center gap-2">
                                    <i class="bi bi-slash-circle-fill text-danger fs-5"></i>
                                    <span>Blokir Pengguna</span>
                                </h6>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-4 text-center">
                                <div class="mb-3">
                                    <i class="bi bi-person-x-fill text-danger" style="font-size: 3rem;"></i>
                                </div>
                                <h6 class="fw-bold text-dark mb-2">Blokir "{{ $item['author_name'] }}"?</h6>
                                <p class="text-muted small mb-0">
                                    Pengguna ini tidak akan dapat login, membuat postingan, atau berkomentar lagi di platform MediGO. Seluruh postingan aktif milik pengguna ini juga akan dinonaktifkan.
                                </p>
                            </div>
                            <div class="modal-footer border-top px-4 py-3 bg-light d-flex justify-content-between">
                                <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                                <form action="{{ route('admin.forum.users.block', ['id' => $item['author_id'] ?? $item['id_user']]) }}" method="POST" class="m-0">
                                    @csrf
                                    <button type="submit" class="btn btn-danger rounded-pill px-4 fw-semibold shadow-xs">
                                        Ya, Blokir Pengguna
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-5">
                    <div class="mb-3 text-muted opacity-50">
                        <i class="bi bi-shield-check" style="font-size: 3.5rem;"></i>
                    </div>
                    <h6 class="fw-bold text-dark mb-1">Tidak Ada Konten dalam Antrean Moderasi</h6>
                    <p class="text-muted small mb-0">
                        Semua postingan dan komentar forum dalam kondisi baik atau filter saat ini tidak menemukan data.
                    </p>
                </div>
            @endforelse
        </div>

        {{-- Card Footer & Pagination --}}
        @if($pagination['total'] > 0)
            <div class="p-3 px-4 border-top bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span class="text-muted small">
                    Menampilkan {{ $pagination['from'] }}-{{ $pagination['to'] }} dari {{ $pagination['total'] }} item {{ $tab === 'reported' ? 'yang dilaporkan' : '' }}
                </span>

                {{-- Pagination Links --}}
                <nav aria-label="Page navigation">
                    <ul class="pagination pagination-sm m-0">
                        {{-- Previous Page Link --}}
                        <li class="page-item {{ $pagination['current_page'] <= 1 ? 'disabled' : '' }}">
                            <a class="page-link rounded-2 me-1 text-dark" href="{{ route('admin.forum', array_merge(request()->query(), ['page' => $pagination['current_page'] - 1])) }}" aria-label="Previous">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        </li>

                        {{-- Page Numbers --}}
                        @for($p = 1; $p <= $pagination['last_page']; $p++)
                            <li class="page-item {{ $p === $pagination['current_page'] ? 'active' : '' }}">
                                <a class="page-link rounded-2 me-1 {{ $p === $pagination['current_page'] ? 'bg-dark border-dark text-white' : 'text-dark' }}" href="{{ route('admin.forum', array_merge(request()->query(), ['page' => $p])) }}">
                                    {{ $p }}
                                </a>
                            </li>
                        @endfor

                        {{-- Next Page Link --}}
                        <li class="page-item {{ $pagination['current_page'] >= $pagination['last_page'] ? 'disabled' : '' }}">
                            <a class="page-link rounded-2 text-dark" href="{{ route('admin.forum', array_merge(request()->query(), ['page' => $pagination['current_page'] + 1])) }}" aria-label="Next">
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        @endif
    </div>
</div>
@endsection

@push('styles')
<style>
    .btn-medigo-navy {
        background-color: #0b2b52;
        border-color: #0b2b52;
    }
    .btn-medigo-navy:hover {
        background-color: #082140;
        border-color: #082140;
    }
    .hover-bg-light:hover {
        background-color: #f8fafc;
    }
    .hover-primary:hover {
        color: #0b5ed7 !important;
    }
</style>
@endpush
