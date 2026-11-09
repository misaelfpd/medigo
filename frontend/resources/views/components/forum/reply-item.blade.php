@php
    $beUrl = rtrim(str_replace('localhost', '127.0.0.1', config('services.medigo.url', 'http://127.0.0.1:8005')), '/');
    
    $replyId = $reply['id_tanggapan'] ?? ($reply['id'] ?? null);
    $commenterName = $reply['nama_user'] ?? ($reply['commentator_name'] ?? 'Pengguna');
    $commenterEmail = $reply['email_user'] ?? ($reply['commentator_email'] ?? ($reply['email'] ?? null));
    $replyBody = $reply['isi_tanggapan'] ?? ($reply['body'] ?? ($reply['content'] ?? ''));
    $replyImage = $reply['gambar_tanggapan'] ?? ($reply['image_path'] ?? null);
    
    // CEK KEPEMILIKAN BERDASARKAN EMAIL (Aman dari perbedaan ID database FE & BE)
    $isReplyOwner = Auth::check() && $commenterEmail && (Auth::user()->email === $commenterEmail);
    
    // Bisa report jika sudah login DAN BUKAN pemilik komentar sendiri
    $canReportReply = Auth::check() && !$isReplyOwner;
@endphp

<div class="bg-light p-3 rounded-3" id="comment-item-{{ $replyId }}">
    <div class="d-flex justify-content-between align-items-center mb-1">
        <div class="fw-semibold text-dark">{{ $commenterName }}</div>
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