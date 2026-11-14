@php
    $beUrl = rtrim(str_replace('localhost', '127.0.0.1', config('services.medigo.url', 'http://127.0.0.1:8005')), '/');
    
    $commentId = $comment['id_tanggapan'] ?? ($comment['id'] ?? null);
    $commenterName = $comment['nama_user'] ?? ($comment['commentator_name'] ?? 'Pengguna');
    $commenterEmail = $comment['email_user'] ?? ($comment['commentator_email'] ?? ($comment['email'] ?? null));
    $commenterAvatar = $comment['commentator_avatar'] ?? ($comment['avatar'] ?? null);
    $commentBody = $comment['isi_tanggapan'] ?? ($comment['body'] ?? ($comment['content'] ?? ''));
    $commentImage = $comment['gambar_tanggapan'] ?? ($comment['image_path'] ?? null);
    
    // CEK KEPEMILIKAN BERDASARKAN EMAIL (Aman dari perbedaan ID database FE & BE)
    $isOwner = Auth::check() && $commenterEmail && (Auth::user()->email === $commenterEmail);
    
    // Bisa report jika sudah login DAN BUKAN pemilik komentar sendiri
    $canReport = Auth::check() && !$isOwner;

    // Resolusi avatar komentator
    $commenterAvatarUrl = null;
    if ($isOwner && Auth::check() && !empty(Auth::user()->avatar) && file_exists(public_path(Auth::user()->avatar))) {
        $commenterAvatarUrl = asset(Auth::user()->avatar);
    } elseif (!empty($commenterAvatar)) {
        if (str_starts_with($commenterAvatar, 'http')) {
            $commenterAvatarUrl = $commenterAvatar;
        } elseif (file_exists(public_path($commenterAvatar))) {
            $commenterAvatarUrl = asset($commenterAvatar);
        }
    }
@endphp
<div class="card border-0 border-bottom rounded-0 pb-3 bg-transparent comment-item {{ $index >= $limit ? 'd-none hidden-comment' : '' }}" id="comment-item-{{ $commentId }}">
    <div class="card-body p-0">
        
        <!-- Info Komentar Utama -->
        <div class="d-flex justify-content-between mb-2">
            <div class="d-flex align-items-center gap-2">
                @if($commenterAvatarUrl)
                    <img src="{{ $commenterAvatarUrl }}" 
                         alt="{{ $commenterName }}" 
                         class="rounded-circle object-fit-cover border shadow-2xs" 
                         style="width: 36px; height: 36px;">
                @else
                    <div class="avatar-circle-sm bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 36px; height: 36px;">
                        {{ strtoupper(substr($commenterName, 0, 1)) }}
                    </div>
                @endif
                <div class="fw-semibold text-dark">{{ $commenterName }}</div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted small">{{ \Carbon\Carbon::parse($comment['created_at'] ?? now())->diffForHumans() }}</span>
                
                <!-- Tombol Hapus Komentar Utama (hanya tampil untuk pemilik) -->
                @if($isOwner)
                    <button 
                        type="button" 
                        class="btn btn-link text-danger p-0 text-decoration-none small btn-delete-forum-comment" 
                        data-id="{{ $commentId }}"
                        data-target="comment-item-{{ $commentId }}"
                        title="Hapus Komentar">
                        <i class="bi bi-trash"></i>
                    </button>
                @endif

                <!-- Tombol Report Komentar (hanya tampil untuk bukan pemilik) -->
                @if($canReport)
                    <button class="btn btn-link text-danger p-0" data-bs-toggle="modal" data-bs-target="#dynamicReportModal" data-report-type="comment" data-report-id="{{ $commentId }}" title="Laporkan Komentar">
                        <i class="bi bi-flag"></i>
                    </button>
                @endif
            </div>
        </div>
        <div class="ps-5 text-secondary">{!! nl2br(e($commentBody)) !!}</div>
        
        <!-- GAMBAR KOMENTAR UTAMA -->
        @if(!empty($commentImage))
            @php
                $imgUrl = str_starts_with($commentImage, 'http') ? str_replace('http://localhost', $beUrl, $commentImage) : $beUrl . '/storage/' . ltrim($commentImage, '/');
            @endphp
            <div class="ps-5 mt-2">
                <img src="{{ $imgUrl }}" alt="Lampiran" class="img-fluid rounded border shadow-sm cursor-pointer clickable-image" style="max-height: 200px; object-fit: cover;" onclick="openImageViewer(@js($imgUrl))">
            </div>
        @endif
        
        @php
            $commentLikes = (int) ($comment['likes_count'] ?? 0);
            $commentLiked = (bool) ($comment['liked_by_me'] ?? false);
        @endphp
        <div class="ps-5 mt-2 d-flex align-items-center gap-3">
            <button type="button"
                    class="mg-like-btn mg-like-btn--sm {{ $commentLiked ? 'is-liked' : '' }}"
                    data-like-type="comment"
                    data-like-id="{{ $commentId }}"
                    data-liked="{{ $commentLiked ? '1' : '0' }}"
                    data-own="{{ $isOwner ? '1' : '0' }}"
                    data-login="{{ Auth::check() ? '1' : '0' }}"
                    aria-pressed="{{ $commentLiked ? 'true' : 'false' }}">
                <i class="bi {{ $commentLiked ? 'bi-hand-thumbs-up-fill' : 'bi-hand-thumbs-up' }}"></i>
                <span data-like-count>{{ $commentLikes }}</span>
            </button>
            <button class="btn btn-link fw-semibold text-medigo-primary p-0 text-decoration-none" onclick="setReplyTarget(@js((string) $commentId), @js($commenterName))">Balas</button>
        </div>

        <!-- NESTED REPLIES LOOP -->
        @if(!empty($comment['replies']))
            <div class="mt-3 ms-4 ps-3 border-start border-2 border-primary-subtle d-flex flex-column gap-3">
                @foreach($comment['replies'] as $reply)
                    <!-- Variabel $reply dikirim ke komponen ini -->
                    @include('components.forum.reply-item', ['reply' => $reply])
                @endforeach
            </div>
        @endif
    </div>
</div>