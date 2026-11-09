@php
    // Cocokkan kepemilikan berdasarkan email user yang login di frontend 
    // dengan email author yang dikirim dari API backend
    $authorEmail = $discussion['author_email'] ?? ($discussion['email_user'] ?? ($discussion['email'] ?? null));
    $authorName = $discussion['author_name'] ?? ($discussion['nama_user'] ?? 'Pengguna');
    $discId = $discussion['id_diskusi'] ?? ($discussion['id'] ?? null);
    $isDiscussionOwner = Auth::check() && $authorEmail && (Auth::user()->email === $authorEmail);
    $canReportDiscussion = Auth::check() && !$isDiscussionOwner;
@endphp

<div class="d-flex align-items-center justify-content-between mb-3">
    <div class="d-flex align-items-center gap-3">
        <div class="avatar-circle text-white rounded-circle d-flex align-items-center justify-content-center fw-bold fs-5 shadow-sm" style="width: 48px; height: 48px; background-color: #002b5e;">
            {{ strtoupper(substr($authorName, 0, 1)) }}
        </div>
        <div>
            <div class="fw-bold">{{ $authorName }}</div>
            <div class="text-muted small">{{ \Carbon\Carbon::parse($discussion['created_at'] ?? now())->diffForHumans() }}</div>
        </div>
    </div>
    
    <!-- Tombol Titik Tiga untuk Pemilik Diskusi: Hapus Diskusi Sendiri -->
    @if($isDiscussionOwner)
    <div class="dropdown">
        <button class="btn btn-link text-muted p-2 m-0 text-decoration-none" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-three-dots-vertical"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
            <li>
                <button 
                    class="dropdown-item text-danger btn-delete-discussion"
                    data-id="{{ $discId }}"
                    onclick="return confirm('Apakah Anda yakin ingin menghapus diskusi ini? Tindakan ini tidak bisa dibatalkan.')">
                    <i class="bi bi-trash me-2"></i> Hapus Diskusi
                </button>
            </li>
        </ul>
    </div>
    @endif

    <!-- Tombol Titik Tiga untuk Bukan Pemilik: Laporkan Diskusi Orang Lain -->
    @if($canReportDiscussion)
    <div class="dropdown">
        <button class="btn btn-link text-muted p-2 m-0 text-decoration-none" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-three-dots-vertical"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
            <li>
                <button class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#dynamicReportModal" data-report-type="discussion" data-report-id="{{ $discId }}">
                    <i class="bi bi-flag me-2"></i> Laporkan Diskusi
                </button>
            </li>
        </ul>
    </div>
    @endif
</div>