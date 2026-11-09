@php
    $beUrl = rtrim(str_replace('localhost', '127.0.0.1', config('services.medigo.url', 'http://127.0.0.1:8005')), '/');
    
    // Ambil email dari data komentar yang dikirim backend
    $commenterEmail = $comment['commentator_email'] ?? ($comment['email'] ?? null);
    
    // CEK KEPEMILIKAN BERDASARKAN EMAIL (Aman dari perbedaan ID database FE & BE)
    $isOwner = Auth::check() && $commenterEmail && (Auth::user()->email === $commenterEmail);
    
    // Bisa report jika sudah login DAN BUKAN pemilik komentar sendiri
    $canReport = Auth::check() && !$isOwner;
@endphp
<div class="card border-0 border-bottom rounded-0 pb-3 bg-transparent comment-item {{ $index >= $limit ? 'd-none hidden-comment' : '' }}" id="comment-item-{{ $comment['id'] }}">
    <div class="card-body p-0">
        
        <!-- Info Komentar Utama -->
        <div class="d-flex justify-content-between mb-2">
            <div class="d-flex align-items-center gap-2">
                <div class="avatar-circle-sm bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 36px; height: 36px;">
                    {{ strtoupper(substr($comment['commentator_name'] ?? 'U', 0, 1)) }}
                </div>
                <div class="fw-semibold text-dark">{{ $comment['commentator_name'] ?? 'Pengguna' }}</div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted small">{{ \Carbon\Carbon::parse($comment['created_at'] ?? now())->diffForHumans() }}</span>
                
                <!-- Tombol Hapus Komentar Utama (hanya tampil untuk pemilik) -->
                @if($isOwner)
                    <button 
                        type="button" 
                        class="btn btn-link text-danger p-0 text-decoration-none small btn-delete-forum-comment" 
                        data-id="{{ $comment['id'] }}"
                        data-target="comment-item-{{ $comment['id'] }}"
                        title="Hapus Komentar">
                        <i class="bi bi-trash"></i>
                    </button>
                @endif

                <!-- Tombol Report Komentar (hanya tampil untuk bukan pemilik) -->
                @if($canReport)
                    <button class="btn btn-link text-danger p-0" data-bs-toggle="modal" data-bs-target="#dynamicReportModal" data-report-type="comment" data-report-id="{{ $comment['id'] }}" title="Laporkan Komentar">
                        <i class="bi bi-flag"></i>
                    </button>
                @endif
            </div>
        </div>
        <div class="ps-5 text-secondary">{!! nl2br(e($comment['body'] ?? '')) !!}</div>
        
        <!-- GAMBAR KOMENTAR UTAMA -->
        @if(!empty($comment['image_path']))
            @php
                $imgUrl = str_starts_with($comment['image_path'], 'http') ? str_replace('http://localhost', $beUrl, $comment['image_path']) : $beUrl . '/storage/' . ltrim($comment['image_path'], '/');
            @endphp
            <div class="ps-5 mt-2">
                <img src="{{ $imgUrl }}" alt="Lampiran" class="img-fluid rounded border shadow-sm cursor-pointer clickable-image" style="max-height: 200px; object-fit: cover;" onclick="openImageViewer('{{ $imgUrl }}')">
            </div>
        @endif
        
        <div class="ps-5 mt-2">
            <button class="btn btn-link fw-semibold text-medigo-primary p-0 text-decoration-none" onclick="setReplyTarget('{{ $comment['id'] }}', '{{ $comment['commentator_name'] ?? 'Pengguna' }}')">Balas</button>
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