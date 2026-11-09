<div class="discussion-reply-form container-fluid px-0">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                @if(Auth::check())
                <form action="{{ route('discussions.reply', $discussion['id_diskusi'] ?? $discussion['id']) }}" method="POST" enctype="multipart/form-data" class="bg-light p-2 rounded-pill shadow-sm border position-relative">
                    @csrf
                    <input type="hidden" name="parent_id" id="replyParentId" value="">

                    <div id="replyingIndicator" class="d-none position-absolute bg-white px-3 py-1 rounded-pill border shadow-sm text-muted small" style="bottom: 100%; left: 10px; margin-bottom: 8px;">
                        <span id="replyingToText">Membalas komentar</span>
                        <button type="button" class="btn-close ms-2" style="font-size: 0.65rem;" onclick="cancelReplyTarget()"></button>
                    </div>

                    <div id="imagePreviewContainer" class="d-none position-absolute bg-white p-2 rounded border shadow-sm" style="bottom: 100%; left: 20px; margin-bottom: 10px;">
                        <img id="imagePreview" src="#" alt="Preview" class="rounded" style="max-height: 100px; object-fit: cover;">
                        <button type="button" class="btn btn-sm btn-danger rounded-circle position-absolute top-0 start-100 translate-middle p-0" style="width: 22px; height: 22px;" onclick="removeImage()"><i class="bi bi-x"></i></button>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <label for="commentImageInput" class="btn btn-light rounded-circle text-muted p-2 d-flex m-0 cursor-pointer"><i class="bi bi-image fs-5"></i></label>
                        <input type="file" name="image" id="commentImageInput" class="d-none" accept="image/*" onchange="previewImage(this)">
                        <input type="text" name="content" id="replyInputContent" class="form-control border-0 bg-transparent flex-grow-1 shadow-none ps-1" placeholder="Tulis balasan..." required autocomplete="off">
                        <button type="submit" class="btn btn-medigo-primary rounded-pill px-4 fw-semibold"><i class="bi bi-send"></i> Kirim</button>
                    </div>
                </form>
                @else
                <div class="text-center p-2 bg-light rounded-pill border">
                    <span class="text-muted small">Silakan <a href="{{ route('login') }}" class="text-medigo-primary fw-bold text-decoration-none">Masuk</a> terlebih dahulu.</span>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>