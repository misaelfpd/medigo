@php
    $beUrl = rtrim(str_replace('localhost', '127.0.0.1', config('services.medigo.url', 'http://127.0.0.1:8005')), '/');
    
    $replyId = $reply['id_tanggapan'] ?? ($reply['id'] ?? null);
    $commenterName = $reply['nama_user'] ?? ($reply['commentator_name'] ?? 'Pengguna');
    $commenterEmail = $reply['email_user'] ?? ($reply['commentator_email'] ?? ($reply['email'] ?? null));
    $commenterAvatar = $reply['commentator_avatar'] ?? ($reply['avatar'] ?? null);
    $replyBody = $reply['isi_tanggapan'] ?? ($reply['body'] ?? ($reply['content'] ?? ''));
    $replyImage = $reply['gambar_tanggapan'] ?? ($reply['image_path'] ?? null);
    
    // CEK KEPEMILIKAN BERDASARKAN EMAIL (Aman dari perbedaan ID database FE & BE)
    $isReplyOwner = Auth::check() && $commenterEmail && (Auth::user()->email === $commenterEmail);
    
    // Bisa report jika sudah login DAN BUKAN pemilik komentar sendiri
    $canReportReply = Auth::check() && !$isReplyOwner;

    // Resolusi avatar balasan
    $replyAvatarUrl = null;
    if ($isReplyOwner && Auth::check() && !empty(Auth::user()->avatar) && file_exists(public_path(Auth::user()->avatar))) {
        $replyAvatarUrl = asset(Auth::user()->avatar);
    } elseif (!empty($commenterAvatar)) {
        if (str_starts_with($commenterAvatar, 'http')) {
            $replyAvatarUrl = $commenterAvatar;
        } elseif (file_exists(public_path($commenterAvatar))) {
            $replyAvatarUrl = asset($commenterAvatar);
        }
    }
@endphp

<div class="bg-light p-3 rounded-3" id="comment-item-{{ $replyId }}">
    <div class="d-flex justify-content-between align-items-center mb-1">
        <div class="d-flex align-items-center gap-2">
            @if($replyAvatarUrl)
                <img src="{{ $replyAvatarUrl }}" 
                     alt="{{ $commenterName }}" 
                     class="rounded-circle object-fit-cover border shadow-2xs" 
                     style="width: 26px; height: 26px;">
            @else
                <div class="avatar-circle-sm bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 26px; height: 26px; font-size: 0.72rem;">
                    {{ strtoupper(substr($commenterName, 0, 1)) }}
                </div>
            @endif
            <div class="fw-semibold text-dark">{{ $commenterName }}</div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="text-muted small">{{ \Carbon\Carbon::parse($reply['created_at'] ?? now())->diffForHumans() }}</span>
            
            <!-- Tombol Hapus Sub-Komentar / Balasan (hanya untuk pemilik atau admin) -->
            @if($isReplyOwner || (Auth::check() && method_exists(Auth::user(), 'isAdmin') && Auth::user()->isAdmin()))
                <button 
                    type="button"
                    class="btn btn-link text-danger p-0 m-0 text-decoration-none small btn-delete-forum-comment"
                    data-id="{{ $replyId }}"
                    data-target="comment-item-{{ $replyId }}"
                    title="Hapus Balasan">
                    <i class="bi bi-trash"></i>
                </button>
            @endif

            <!-- Tombol Report Sub-Komentar (hanya untuk bukan pemilik) -->
            @if($canReportReply)
                <button class="btn btn-link text-danger p-0 m-0" data-bs-toggle="modal" data-bs-target="#dynamicReportModal" data-report-type="comment" data-report-id="{{ $replyId }}" title="Laporkan Balasan">
                    <i class="bi bi-flag"></i>
                </button>
            @endif
        </div>
    </div>
    <div class="text-secondary">{!! nl2br(e($replyBody)) !!}</div>

    <!-- GAMBAR BALASAN (NESTED REPLY) -->
    @if(!empty($replyImage))
        @php
            $replyImgUrl = str_starts_with($replyImage, 'http') ? str_replace('http://localhost', $beUrl, $replyImage) : $beUrl . '/storage/' . ltrim($replyImage, '/');
        @endphp
        <div class="mt-2">
            <img src="{{ $replyImgUrl }}" alt="Lampiran" class="img-fluid rounded border shadow-sm cursor-pointer clickable-image" style="max-height: 140px; object-fit: cover;" onclick="openImageViewer('{{ $replyImgUrl }}')">
        </div>
    @endif
</div>