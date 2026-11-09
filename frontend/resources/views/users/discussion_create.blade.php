@extends('layouts.app')

@section('title', 'Tambah Diskusi Baru - MediGO')

@push('styles')
<style>
    /* Styling responsif Mobile & Desktop */
    @media (max-width: 991.98px) {
        .navbar { display: none !important; }
        footer { display: none !important; }
        body { background-color: #f8fafc !important; padding-bottom: 75px; }
        
        .discussion-title-input {
            border: none !important;
            border-bottom: 1.5px solid #e2e8f0 !important;
            border-radius: 0 !important;
            background: transparent !important;
            padding: 0.6rem 0 !important;
            font-weight: 700 !important;
            font-size: 0.95rem !important;
            color: #1e293b !important;
        }
        .discussion-title-input::placeholder {
            text-transform: uppercase;
            font-size: 0.88rem;
            letter-spacing: 0.5px;
            color: #94a3b8;
        }

        .discussion-content-textarea {
            border: none !important;
            background: transparent !important;
            padding: 0.75rem 0 !important;
            font-size: 0.9rem !important;
            line-height: 1.65 !important;
            color: #334155 !important;
            min-height: 180px;
            resize: none;
        }
        .discussion-content-textarea::placeholder {
            color: #94a3b8;
        }
    }

    @media (min-width: 992px) {
        .discussion-title-input {
            border: none !important;
            background-color: #f8fafc !important;
            border-radius: 12px !important;
            padding: 0.85rem 1.15rem !important;
            font-size: 0.95rem !important;
            color: #1e293b !important;
        }

        .discussion-content-textarea {
            border: none !important;
            background-color: #f8fafc !important;
            border-radius: 12px !important;
            padding: 1.15rem !important;
            font-size: 0.95rem !important;
            line-height: 1.65 !important;
            color: #334155 !important;
            min-height: 160px;
        }
    }

    /* Kategori Card di Desktop */
    .category-card {
        border: 1.5px solid #e2e8f0;
        background-color: #ffffff;
        color: #334155;
        border-radius: 14px;
        padding: 1.1rem 1rem;
        cursor: pointer;
        transition: all 0.2s ease;
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 8px;
        font-weight: 600;
        font-size: 0.92rem;
    }

    .category-card:hover {
        border-color: #002b5e;
        background-color: #f8fafc;
        transform: translateY(-2px);
    }

    .category-card.active {
        background-color: #002b5e !important;
        border-color: #002b5e !important;
        color: #ffffff !important;
        box-shadow: 0 4px 14px rgba(0, 43, 94, 0.22);
    }

    .category-card.active i {
        color: #ffffff !important;
    }

    /* Kategori Pills di Mobile */
    .mobile-category-pill {
        white-space: nowrap;
        border-radius: 50rem;
        padding: 0.45rem 1.25rem;
        font-size: 0.85rem;
        font-weight: 600;
        border: 1.5px solid #002b5e;
        background-color: #ffffff;
        color: #002b5e;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .mobile-category-pill.active {
        background-color: #002b5e !important;
        color: #ffffff !important;
        box-shadow: 0 2px 8px rgba(0, 43, 94, 0.2);
    }

    /* Upload Dropzone Box */
    .upload-dropzone {
        border: 2px dashed #cbd5e1;
        border-radius: 14px;
        padding: 2.2rem 1.5rem;
        text-align: center;
        background-color: #ffffff;
        cursor: pointer;
        transition: all 0.25s ease;
    }

    .upload-dropzone:hover, .upload-dropzone.dragover {
        border-color: #002b5e;
        background-color: #f1f5f9;
    }

    /* Mobile Upload Box */
    .mobile-upload-box {
        border: 2px dashed #cbd5e1;
        border-radius: 12px;
        padding: 1.5rem 1rem;
        text-align: center;
        background-color: #ffffff;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .mobile-upload-box:hover {
        border-color: #002b5e;
        background-color: #f8fafc;
    }

    /* Preview Item */
    .image-preview-item {
        position: relative;
        width: 88px;
        height: 88px;
        border-radius: 12px;
        overflow: hidden;
        border: 1.5px solid #e2e8f0;
        box-shadow: 0 2px 6px rgba(0,0,0,0.06);
    }

    .image-preview-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .image-preview-remove {
        position: absolute;
        top: 4px;
        right: 4px;
        width: 22px;
        height: 22px;
        background-color: rgba(220, 38, 38, 0.9);
        color: white;
        border: none;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.72rem;
        cursor: pointer;
        transition: background-color 0.2s ease;
    }

    .image-preview-remove:hover {
        background-color: #b91c1c;
    }
</style>
@endpush

@section('content')

<!-- ========================================== -->
<!-- FORM TUNGGAL (UNIFIED) RESIK & TERPADU     -->
<!-- ========================================== -->
<form id="discussionForm" action="{{ route('discussions.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="category" id="form_category" value="{{ old('category', 'BPJS') }}">
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
            @if (session('error'))
                <div class="alert alert-danger rounded-4 py-2 px-3 mb-3 small">
                    {{ session('error') }}
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
                    @if (session('error'))
                        <div class="alert alert-danger rounded-4 py-3 px-4 mb-4 shadow-sm border-0 d-flex align-items-center gap-2">
                            <i class="bi bi-exclamation-octagon-fill fs-5"></i>
                            <span>{{ session('error') }}</span>
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

@push('scripts')
<script>
    let fileTransfer = new DataTransfer();

    // 1. Sinkronisasi Kategori Topik
    function selectCategory(cat) {
        document.getElementById('form_category').value = cat;

        // Update Desktop cards
        document.querySelectorAll('.category-card').forEach(card => {
            if (card.getAttribute('data-value') === cat) {
                card.classList.add('active');
            } else {
                card.classList.remove('active');
            }
        });

        // Update Mobile pills
        document.querySelectorAll('.mobile-category-pill').forEach(pill => {
            if (pill.getAttribute('data-value') === cat) {
                pill.classList.add('active');
            } else {
                pill.classList.remove('active');
            }
        });
    }

    // 2. Sinkronisasi Antara Input Desktop & Form Asli
    function syncMirrorInputs(el, targetName) {
        const formInput = document.querySelector(`form#discussionForm [name="${targetName}"]`);
        if (formInput) {
            formInput.value = el.value;
        }
    }

    // Inisialisasi saat load
    document.addEventListener('DOMContentLoaded', function() {
        const initialCategory = document.getElementById('form_category').value || 'BPJS';
        selectCategory(initialCategory);

        // Jika user mengetik di form mobile, sinkronkan ke desktop
        const mainTitle = document.querySelector('form#discussionForm [name="title"]');
        const desktopTitle = document.getElementById('desktopTitleMirror');
        if (mainTitle && desktopTitle) {
            mainTitle.addEventListener('input', function() {
                desktopTitle.value = this.value;
            });
            // Initial sync
            if (desktopTitle.value) mainTitle.value = desktopTitle.value;
            else if (mainTitle.value) desktopTitle.value = mainTitle.value;
        }

        const mainContent = document.querySelector('form#discussionForm [name="content"]');
        const desktopContent = document.getElementById('desktopContentMirror');
        if (mainContent && desktopContent) {
            mainContent.addEventListener('input', function() {
                desktopContent.value = this.value;
            });
            if (desktopContent.value) mainContent.value = desktopContent.value;
            else if (mainContent.value) desktopContent.value = mainContent.value;
        }
    });

    // 3. Trigger File Chooser
    function triggerFileInput() {
        document.getElementById('sharedImageInput').click();
    }

    // 4. Tangani File
    function handleFileSelect(event) {
        const newFiles = Array.from(event.target.files);
        addFiles(newFiles);
    }

    function addFiles(files) {
        const maxFiles = 3;
        const maxSize = 5 * 1024 * 1024; // 5MB

        for (let file of files) {
            if (fileTransfer.items.length >= maxFiles) {
                if (window.showGlobalToast) {
                    showGlobalToast('Maksimal 3 foto yang dapat dilampirkan.', 'warning');
                } else {
                    alert('Maksimal 3 foto yang dapat dilampirkan.');
                }
                break;
            }

            if (file.size > maxSize) {
                if (window.showGlobalToast) {
                    showGlobalToast(`Ukuran file "${file.name}" melebihi batas 5MB.`, 'warning');
                } else {
                    alert(`Ukuran file "${file.name}" melebihi batas 5MB.`);
                }
                continue;
            }

            if (!file.type.match('image.*')) {
                if (window.showGlobalToast) {
                    showGlobalToast(`File "${file.name}" bukan format gambar yang didukung.`, 'warning');
                } else {
                    alert(`File "${file.name}" bukan format gambar yang didukung.`);
                }
                continue;
            }

            fileTransfer.items.add(file);
        }

        document.getElementById('sharedImageInput').files = fileTransfer.files;
        renderImagePreviews();
    }

    // 5. Hapus File dari Galeri
    function removeFile(index) {
        const dt = new DataTransfer();
        const { files } = fileTransfer;

        for (let i = 0; i < files.length; i++) {
            if (i !== index) {
                dt.items.add(files[i]);
            }
        }

        fileTransfer = dt;
        document.getElementById('sharedImageInput').files = fileTransfer.files;
        renderImagePreviews();
    }

    // 6. Render Previews
    function renderImagePreviews() {
        const files = Array.from(fileTransfer.files);
        const mobileContainer = document.getElementById('mobileImagePreviews');
        const desktopContainer = document.getElementById('desktopImagePreviews');

        if (files.length === 0) {
            if (mobileContainer) {
                mobileContainer.innerHTML = '';
                mobileContainer.classList.add('d-none');
            }
            if (desktopContainer) {
                desktopContainer.innerHTML = '';
                desktopContainer.classList.add('d-none');
            }
            return;
        }

        if (mobileContainer) {
            mobileContainer.innerHTML = '';
            mobileContainer.classList.remove('d-none');
        }
        if (desktopContainer) {
            desktopContainer.innerHTML = '';
            desktopContainer.classList.remove('d-none');
        }

        files.forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const previewHtml = `
                    <div class="image-preview-item">
                        <img src="${e.target.result}" alt="Preview">
                        <button type="button" class="image-preview-remove" onclick="removeFile(${index})" title="Hapus foto">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                `;
                if (mobileContainer) mobileContainer.insertAdjacentHTML('beforeend', previewHtml);
                if (desktopContainer) desktopContainer.insertAdjacentHTML('beforeend', previewHtml);
            };
            reader.readAsDataURL(file);
        });
    }

    // 7. Drag & Drop Desktop
    const dropzone = document.getElementById('desktopDropzone');
    if (dropzone) {
        ['dragenter', 'dragover'].forEach(eventName => {
            dropzone.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.add('dragover');
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.remove('dragover');
            }, false);
        });

        dropzone.addEventListener('drop', (e) => {
            const dt = e.dataTransfer;
            const files = dt.files;
            addFiles(Array.from(files));
        }, false);
    }
</script>
@endpush
