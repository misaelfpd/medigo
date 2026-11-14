@extends('layouts.app')

@section('title', 'Tambah Diskusi Baru - MediGO')

@push('styles')
    @vite(['resources/css/pages/forum-create.css', 'resources/js/pages/forum-create.js'])
@endpush

@section('content')

<!-- ========================================== -->
<!-- FORM TUNGGAL (UNIFIED) RESIK & TERPADU     -->
<!-- ========================================== -->
<form id="discussionForm" action="{{ route('discussions.store') }}" method="POST" enctype="multipart/form-data" data-mention-search-url="{{ route('forum.mention-search') }}">
    @csrf
    <input type="hidden" name="category" id="form_category" value="{{ old('category', 'BPJS') }}">
    <input type="hidden" name="faskes_id" id="form_faskes_id" value="{{ old('faskes_id') }}">
    <input type="hidden" name="faskes_name" id="form_faskes_name" value="{{ old('faskes_name') }}">
    <input type="file" id="sharedImageInput" name="images[]" multiple accept="image/png, image/jpeg, image/jpg" class="d-none" onchange="handleFileSelect(event)">

    <!-- ========================================== -->
    <!-- 1. TAMPILAN MOBILE (< 992px) SESUAI MOCKUP -->
    <!-- ========================================== -->
    <div class="d-block d-lg-none">
        <!-- Header Atas Mobile: [X] - Tambah Diskusi - [Post] -->
        <div class="d-flex align-items-center justify-content-between py-3 px-3 bg-white border-bottom sticky-top shadow-2xs">
            <a href="{{ route('discussions') }}" class="text-dark fs-4 text-decoration-none d-flex align-items-center">
                <i class="bi bi-x-lg"></i>
            </a>
            <h6 class="fw-bold mb-0 text-dark" style="font-size: 1.05rem;">Tambah Diskusi</h6>
            <button type="submit" class="btn btn-medigo-primary btn-sm rounded-pill px-3 py-1-5 fw-semibold text-white shadow-xs" style="background-color: #002b5e; border-color: #002b5e; min-width: 65px;">
                Post
            </button>
        </div>

        <div class="p-3">
            <!-- Alert Error -->
            @if (isset($errors) && $errors->any())
                <div class="alert alert-danger rounded-4 py-2 px-3 mb-3 small">
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <!-- KATEGORI TOPIK MOBILE -->
            <div class="mb-3">
                <label class="form-label text-secondary fw-bold text-uppercase mb-2" style="font-size: 0.78rem; letter-spacing: 0.5px;">
                    KATEGORI TOPIK
                </label>
                <div class="d-flex gap-2 overflow-auto pb-1" style="scrollbar-width: none;">
                    <button type="button" class="mobile-category-pill active" data-value="BPJS" onclick="selectCategory('BPJS')">BPJS</button>
                    <button type="button" class="mobile-category-pill" data-value="Rekomendasi RS" onclick="selectCategory('Rekomendasi RS')">Rekomendasi RS</button>
                    <button type="button" class="mobile-category-pill" data-value="Umum" onclick="selectCategory('Umum')">Umum</button>
                    <button type="button" class="mobile-category-pill" data-value="Tips Kesehatan" onclick="selectCategory('Tips Kesehatan')">Tips Kesehatan</button>
                </div>
            </div>

            <!-- CARD FORM MOBILE -->
            <div class="card border rounded-4 bg-white shadow-2xs p-3 mb-4">
                <div class="mb-2">
                    <input type="text" name="title" class="form-control shadow-none px-0 discussion-title-input" placeholder="JUDUL PEMBAHASAN ...." value="{{ old('title') }}" required>
                </div>
                <div>
                    <textarea name="content" class="form-control shadow-none px-0 discussion-content-textarea" rows="8" placeholder="Ceritakan detail pertanyaan atau pengalaman Anda di sini. Hindari membagikan informasi medis pribadi yang sensitif..." required>{{ old('content') }}</textarea>
                </div>
            </div>

            <!-- NAMA RUMAH SAKIT (MENTION FASKES) MOBILE -->
            <div class="mb-3">
                <label class="form-label text-secondary fw-bold text-uppercase mb-2" style="font-size: 0.78rem; letter-spacing: 0.5px;" for="mentionSearch-mobile">
                    NAMA RUMAH SAKIT
                </label>
                <x-forum.mention-picker suffix="mobile" />
            </div>

            <!-- LAMPIRAN MOBILE -->
            <div class="mb-4">
                <label class="form-label text-secondary fw-bold text-uppercase mb-2" style="font-size: 0.78rem; letter-spacing: 0.5px;">
                    LAMPIRAN
                </label>

                <div class="mobile-upload-box" onclick="triggerFileInput()">
                    <i class="bi bi-images fs-2 text-secondary mb-1 d-block"></i>
                    <span class="text-secondary fw-medium" style="font-size: 0.88rem;">Tambah Foto</span>
                </div>

                <!-- Previews Mobile -->
                <div id="mobileImagePreviews" class="d-flex gap-2 mt-3 flex-wrap d-none"></div>

                <p class="text-muted mt-2 mb-0" style="font-size: 0.75rem; line-height: 1.4;">
                    Maksimal 3 foto. Hindari dokumen yang menampilkan NIK atau nomor kartu BPJS.
                </p>
            </div>
        </div>
    </div>


    <!-- =========================================== -->
    <!-- 2. TAMPILAN DESKTOP (>= 992px) SESUAI MOCKUP -->
    <!-- =========================================== -->
    <div class="d-none d-lg-block py-5 bg-light min-vh-100">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-9 col-xl-8">

                    <!-- Alert Feedback Desktop -->
                    @if (isset($errors) && $errors->any())
                        <div class="alert alert-danger rounded-4 py-3 px-4 mb-4 shadow-sm border-0">
                            <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i> Periksa kembali input Anda:</div>
                            <ul class="mb-0 ps-3">
                                @foreach ($errors->all() as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <!-- Card Form Desktop -->
                    <div class="card border rounded-4 shadow-sm bg-white p-4 p-xl-5">
                        
                        <!-- Judul Form Desktop -->
                        <h3 class="fw-bold text-dark mb-4">Tambah Diskusi Baru</h3>
                        <hr class="text-muted opacity-25 mb-4">

                        <!-- KATEGORI TOPIK DESKTOP -->
                        <div class="mb-4">
                            <label class="form-label text-secondary fw-bold text-uppercase mb-2" style="font-size: 0.8rem; letter-spacing: 0.5px;">
                                KATEGORI TOPIK
                            </label>
                            <div class="row g-3">
                                <div class="col-3">
                                    <div class="category-card active" data-value="BPJS" onclick="selectCategory('BPJS')">
                                        <i class="bi bi-shield-check fs-4"></i>
                                        <span>Tanya BPJS</span>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="category-card" data-value="Umum" onclick="selectCategory('Umum')">
                                        <i class="bi bi-chat-left-text fs-4"></i>
                                        <span>Umum</span>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="category-card" data-value="Rekomendasi RS" onclick="selectCategory('Rekomendasi RS')">
                                        <i class="bi bi-hospital fs-4"></i>
                                        <span>Rekomendasi RS</span>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="category-card" data-value="Tips Kesehatan" onclick="selectCategory('Tips Kesehatan')">
                                        <i class="bi bi-heart-pulse fs-4"></i>
                                        <span>Tips Kesehatan</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- JUDUL DISKUSI DESKTOP -->
                        <div class="mb-4">
                            <label class="form-label text-secondary fw-bold text-uppercase mb-2" style="font-size: 0.8rem; letter-spacing: 0.5px;">
                                JUDUL DISKUSI
                            </label>
                            <input type="text" id="desktopTitleMirror" class="form-control discussion-title-input shadow-none" placeholder="Tulis judul yang jelas dan singkat..." value="{{ old('title') }}" oninput="syncMirrorInputs(this, 'title')">
                        </div>

                        <!-- NAMA RUMAH SAKIT (MENTION FASKES) DESKTOP -->
                        <div class="mb-4">
                            <label class="form-label text-secondary fw-bold text-uppercase mb-2" style="font-size: 0.8rem; letter-spacing: 0.5px;" for="mentionSearch-desktop">
                                NAMA RUMAH SAKIT
                            </label>
                            <x-forum.mention-picker suffix="desktop" />
                        </div>

                        <!-- ISI DISKUSI DESKTOP -->
                        <div class="mb-4">
                            <label class="form-label text-secondary fw-bold text-uppercase mb-2" style="font-size: 0.8rem; letter-spacing: 0.5px;">
                                ISI DISKUSI
                            </label>
                            <textarea id="desktopContentMirror" class="form-control discussion-content-textarea shadow-none" rows="6" placeholder="Jelaskan pertanyaan atau informasi yang ingin Anda bagikan secara detail..." oninput="syncMirrorInputs(this, 'content')">{{ old('content') }}</textarea>
                        </div>

                        <!-- LAMPIRAN (OPSIONAL) DESKTOP -->
                        <div class="mb-4">
                            <label class="form-label text-secondary fw-bold text-uppercase mb-2" style="font-size: 0.8rem; letter-spacing: 0.5px;">
                                LAMPIRAN (OPSIONAL)
                            </label>

                            <div id="desktopDropzone" class="upload-dropzone" onclick="triggerFileInput()">
                                <i class="bi bi-cloud-arrow-up fs-1 text-primary mb-2 d-block" style="color: #002b5e !important;"></i>
                                <div class="text-secondary fw-medium mb-1">
                                    Tarik & lepas foto di sini, atau <span class="text-medigo-primary fw-bold cursor-pointer" style="color: #002b5e;">klik untuk mengunggah</span>
                                </div>
                                <div class="text-muted small">
                                    Maksimal ukuran file 5MB per gambar (JPG, PNG) • Maksimal 3 foto
                                </div>
                            </div>

                            <!-- Previews Desktop -->
                            <div id="desktopImagePreviews" class="d-flex gap-3 mt-3 flex-wrap d-none"></div>
                        </div>

                        <!-- Tombol Aksi Desktop -->
                        <div class="d-flex justify-content-end align-items-center gap-3 pt-3 border-top">
                            <a href="{{ route('discussions') }}" class="btn btn-outline-secondary rounded-pill px-4 py-2 fw-semibold">
                                Batal
                            </a>
                            <button type="submit" class="btn btn-medigo-primary rounded-pill px-4 py-2 fw-semibold text-white shadow-xs" style="background-color: #002b5e; border-color: #002b5e; min-width: 140px;">
                                Kirim Diskusi
                            </button>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

@endsection

