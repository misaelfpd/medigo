<div class="mt-4">
    <h6 class="fw-bold mb-4">Balasan ({{ count($comments ?? []) }})</h6>
    @php
        $limit = 5;
    @endphp

    <div class="d-flex flex-column gap-3" id="commentsContainer">
        @forelse($comments ?? [] as $index => $comment)
            <!-- Variabel dikirim lengkap di sini -->
            @include('components.forum.comment-item', [
                'comment' => $comment, 
                'index' => $index, 
                'limit' => $limit
            ])
        @empty
            <div class="text-center py-5 text-muted">Belum ada balasan.</div>
        @endforelse
    </div>

    @if(count($comments ?? []) > $limit)
        <div class="text-center mt-4">
            <button type="button" id="loadMoreCommentsBtn" class="btn btn-outline-secondary rounded-pill px-4 py-2" onclick="loadMoreComments()">Lihat tanggapan lainnya</button>
        </div>
    @endif
</div>