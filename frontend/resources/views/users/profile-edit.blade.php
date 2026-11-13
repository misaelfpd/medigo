@extends('layouts.app')

@section('title', 'Edit Profil - MediGO')

@push('styles')
    <!-- Local Cropper.js CSS (Zero External Dependencies) -->
    <link rel="stylesheet" href="{{ asset('vendor/cropperjs/cropper.min.css') }}?v=1.6.2">
    <style>
        .cropper-view-box,
        .cropper-face {
            border-radius: 50% !important;
        }
        .cropper-view-box {
            outline: 2px solid #0b2b52 !important;
            outline-color: rgba(11, 43, 82, 0.9) !important;
        }
        .cropper-line,
        .cropper-point {
            background-color: #0b2b52 !important;
        }
        .cropper-preview-circle {
            box-shadow: 0 4px 14px rgba(11, 43, 82, 0.1);
            width: 130px;
            height: 130px;
            border-radius: 50% !important;
            overflow: hidden !important;
            margin-left: auto;
            margin-right: auto;
            background-color: #f8fafc;
            border: 2px solid #cbd5e1;
        }
        .cropper-container-wrapper {
            position: relative;
            width: 100%;
            height: 340px;
            max-height: 50vh;
            background-color: #0f172a;
            border-radius: 0.75rem;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .cropper-container-wrapper img#imageToCrop {
            display: block;
            max-width: 100%;
            max-height: 100%;
        }
        .avatar-clickable-wrapper {
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .avatar-clickable-wrapper:hover #avatarPreviewImage {
            filter: brightness(0.88);
        }
        .avatar-clickable-wrapper:hover #btnTriggerAvatarUpload {
            transform: scale(1.12);
        }
    </style>
@endpush

@section('content')
<div class="edit-profile-wrapper py-4 py-lg-5" style="background-color: #f8fafc; min-height: calc(100vh - 160px);">
    <div class="container" style="max-width: 1120px;">
        
        <!-- Header Page -->
        <div class="mb-4">
            <h2 class="fw-bold mb-1" style="color: #0b2b52; font-size: 1.85rem;">Edit Profil</h2>
            <p class="text-muted mb-0" style="font-size: 0.92rem;">Perbarui informasi pribadi dan pengaturan keamanan Anda.</p>
        </div>

        <!-- Global Errors Alert -->
        @if ($errors->any())
            <div class="alert alert-danger rounded-4 p-3 mb-4 shadow-2xs border-0" role="alert">
                <div class="d-flex align-items-center gap-2 fw-bold mb-1 text-danger">
                    <i class="bi bi-exclamation-octagon-fill fs-5"></i>
                    <span>Terdapat kesalahan pengisian data:</span>
                </div>
                <ul class="mb-0 ps-4 small text-danger">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" id="editProfileForm">
            @csrf
            @method('PUT')

            <div class="row g-4 mb-4">
                
                <!-- Card Kiri: Informasi Pribadi -->
                <div class="col-12 col-lg-7">
                    <div class="card border rounded-4 bg-white p-4 p-md-4 shadow-2xs h-100" style="border-color: #e2e8f0 !important;">
                        <h5 class="fw-bold mb-3" style="color: #0b2b52; font-size: 1.15rem;">Informasi Pribadi</h5>
                        
                        <!-- Avatar Section: Cukup Gambar Kamera & Foto untuk Mengganti (Sesuai Permintaan) -->
                        <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom" style="border-color: #f1f5f9 !important;">
                            <div class="avatar-clickable-wrapper position-relative flex-shrink-0" id="avatarClickableContainer" style="width: 86px; height: 86px;" title="Klik ikon kamera untuk mengganti foto profil">
                                <img src="{{ $user->avatar_url }}" 
                                     alt="{{ $user->name }}" 
                                     id="avatarPreviewImage" 
                                     class="rounded-circle w-100 h-100 object-fit-cover border shadow-xs"
                                     style="border-color: #cbd5e1 !important;">
                                
                                <button type="button" 
                                        id="btnTriggerAvatarUpload" 
                                        class="btn btn-sm btn-medigo-primary position-absolute bottom-0 end-0 rounded-circle p-0 d-flex align-items-center justify-content-center shadow-xs text-white" 
                                        style="width: 28px; height: 28px; border: 2px solid #ffffff; background-color: #0b2b52;"
                                        title="Ganti & Sesuaikan Foto">
                                    <i class="bi bi-camera-fill" style="font-size: 0.82rem;"></i>
                                </button>
                            </div>

                            <div class="flex-grow-1 min-w-0">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="fw-semibold text-dark" style="font-size: 0.95rem;">Foto Profil</span>
                                    <button type="button" id="btnRemoveCropped" class="btn btn-sm btn-link text-danger text-decoration-none p-0 d-none ms-2" style="font-size: 0.8rem;">
                                        <i class="bi bi-x-circle me-1"></i>Batal Ganti Foto
                                    </button>
                                </div>
                                <p class="text-muted mb-0" style="font-size: 0.78rem; line-height: 1.4;">
                                    Klik ikon kamera pada foto untuk mengganti.<br>
                                    Format yang didukung: <strong>JPG, JPEG, PNG</strong>. Maksimal <strong>2MB</strong>.
                                </p>
                            </div>

                            <!-- Hidden Inputs for File & Cropped Data -->
                            <input type="file" id="avatarFileInput" accept="image/jpeg,image/png,image/jpg" class="d-none">
                            <input type="hidden" name="avatar_cropped" id="avatarCroppedInput">
                        </div>

                        <div class="row g-3">
                            <!-- Nama Lengkap -->
                            <div class="col-12 col-md-6">
                                <label for="name" class="form-label fw-semibold text-dark small mb-1">Nama Lengkap</label>
                                <input type="text" 
                                       id="name" 
                                       name="name" 
                                       class="form-control rounded-3 py-2 @error('name') is-invalid @enderror" 
                                       value="{{ old('name', $user->name) }}" 
                                       placeholder="Nama Lengkap" 
                                       required 
                                       style="font-size: 0.9rem;">
                                @error('name')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Alamat Email -->
                            <div class="col-12 col-md-6">
                                <label for="email" class="form-label fw-semibold text-dark small mb-1">Alamat Email</label>
                                <input type="email" 
                                       id="email" 
                                       name="email" 
                                       class="form-control rounded-3 py-2 @error('email') is-invalid @enderror" 
                                       value="{{ old('email', $user->email) }}" 
                                       placeholder="Alamat Email" 
                                       required 
                                       style="font-size: 0.9rem;">
                                @error('email')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Nomor Telepon -->
                            <div class="col-12 col-md-6">
                                <label for="phone" class="form-label fw-semibold text-dark small mb-1">Nomor Telepon</label>
                                <input type="tel" 
                                       id="phone" 
                                       name="phone" 
                                       class="form-control rounded-3 py-2 @error('phone') is-invalid @enderror" 
                                       value="{{ old('phone', $user->phone ?? '') }}" 
                                       placeholder="+62 812 3456 7890" 
                                       style="font-size: 0.9rem;">
                                @error('phone')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Tanggal Lahir -->
                            <div class="col-12 col-md-6">
                                <label for="birth_date" class="form-label fw-semibold text-dark small mb-1">Tanggal Lahir</label>
                                <input type="date" 
                                       id="birth_date" 
                                       name="birth_date" 
                                       class="form-control rounded-3 py-2 @error('birth_date') is-invalid @enderror" 
                                       value="{{ old('birth_date', $user->birth_date ? \Carbon\Carbon::parse($user->birth_date)->format('Y-m-d') : '') }}" 
                                       style="font-size: 0.9rem;">
                                @error('birth_date')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Card Kanan: Keamanan -->
                <div class="col-12 col-lg-5">
                    <div class="card border rounded-4 bg-white p-4 p-md-4 shadow-2xs h-100" style="border-color: #e2e8f0 !important;">
                        <h5 class="fw-bold mb-3" style="color: #0b2b52; font-size: 1.15rem;">Keamanan</h5>

                        <!-- Kata Sandi Saat Ini -->
                        <div class="mb-3">
                            <label for="current_password" class="form-label fw-semibold text-dark small mb-1">Kata Sandi Saat Ini</label>
                            <div class="input-group">
                                <input type="password" 
                                       id="current_password" 
                                       name="current_password" 
                                       class="form-control border-end-0 rounded-start-3 py-2 @error('current_password') is-invalid @enderror" 
                                       placeholder="••••••••" 
                                       autocomplete="current-password"
                                       style="font-size: 0.9rem;">
                                <button type="button" 
                                        class="input-group-text bg-white border-start-0 text-muted rounded-end-3" 
                                        onclick="togglePassVisibility('current_password', 'icon_curr_pass')"
                                        tabindex="-1">
                                    <i class="bi bi-eye" id="icon_curr_pass"></i>
                                </button>
                            </div>
                            @error('current_password')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Kata Sandi Baru -->
                        <div class="mb-2">
                            <label for="new_password" class="form-label fw-semibold text-dark small mb-1">Kata Sandi Baru</label>
                            <div class="input-group">
                                <input type="password" 
                                       id="new_password" 
                                       name="new_password" 
                                       class="form-control border-end-0 rounded-start-3 py-2 @error('new_password') is-invalid @enderror" 
                                       placeholder="••••••••" 
                                       autocomplete="new-password"
                                       style="font-size: 0.9rem;">
                                <button type="button" 
                                        class="input-group-text bg-white border-start-0 text-muted rounded-end-3" 
                                        onclick="togglePassVisibility('new_password', 'icon_new_pass')"
                                        tabindex="-1">
                                    <i class="bi bi-eye" id="icon_new_pass"></i>
                                </button>
                            </div>
                            <span class="text-muted" style="font-size: 0.75rem;">Kosongkan jika tidak ingin mengubah kata sandi.</span>
                            @error('new_password')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                    </div>
                </div>

            </div>

            <!-- Tombol Aksi (Kanan Bawah Sesuai Desain) -->
            <div class="d-flex justify-content-end align-items-center gap-3">
                <a href="{{ route('profile') }}" class="btn btn-outline-secondary rounded-pill px-4 py-2 fw-semibold" style="font-size: 0.9rem; min-width: 100px;">
                    Batal
                </a>
                <button type="submit" class="btn btn-medigo-primary rounded-pill px-4 py-2 fw-semibold text-white shadow-xs" style="font-size: 0.9rem; min-width: 160px; background-color: #0b2b52;">
                    Simpan Perubahan
                </button>
            </div>

        </form>

    </div>
</div>

<!-- MODAL CROP FOTO PROFIL -->
<div class="modal fade" id="cropAvatarModal" tabindex="-1" aria-labelledby="cropAvatarModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            
            <div class="modal-header border-bottom px-4 py-3 bg-light">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-crop text-medigo-primary fs-5"></i>
                    <div>
                        <h6 class="modal-title fw-bold text-dark mb-0" id="cropAvatarModalLabel">Sesuaikan & Potong Foto Profil</h6>
                        <small class="text-muted" style="font-size: 0.78rem;">Geser dan atur posisi foto sesuai lingkaran.</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-3 p-md-4 bg-light-subtle">
                <div class="row g-4 align-items-center">
                    
                    <!-- Area Cropper -->
                    <div class="col-12 col-md-8">
                        <div class="cropper-container-wrapper shadow-xs" id="cropperWrapper">
                            <img id="imageToCrop" src="" alt="Gambar untuk Crop">
                        </div>

                        <!-- Tool Buttons: Zoom & Rotate -->
                        <div class="d-flex align-items-center justify-content-center gap-2 mt-3 flex-wrap">
                            <button type="button" class="btn btn-sm btn-white border rounded-pill px-3 shadow-2xs" id="btnZoomInCrop" title="Perbesar">
                                <i class="bi bi-zoom-in me-1"></i> Perbesar
                            </button>
                            <button type="button" class="btn btn-sm btn-white border rounded-pill px-3 shadow-2xs" id="btnZoomOutCrop" title="Perkecil">
                                <i class="bi bi-zoom-out me-1"></i> Perkecil
                            </button>
                            <button type="button" class="btn btn-sm btn-white border rounded-pill px-3 shadow-2xs" id="btnRotateLeftCrop" title="Putar Kiri 90°">
                                <i class="bi bi-arrow-counterclockwise me-1"></i> Putar Kiri
                            </button>
                            <button type="button" class="btn btn-sm btn-white border rounded-pill px-3 shadow-2xs" id="btnRotateRightCrop" title="Putar Kanan 90°">
                                <i class="bi bi-arrow-clockwise me-1"></i> Putar Kanan
                            </button>
                            <button type="button" class="btn btn-sm btn-white border rounded-pill px-3 shadow-2xs" id="btnResetCrop" title="Reset Posisi">
                                <i class="bi bi-arrow-repeat me-1"></i> Reset
                            </button>
                        </div>
                    </div>

                    <!-- Pratinjau Lingkaran -->
                    <div class="col-12 col-md-4 text-center">
                        <h6 class="fw-semibold text-dark small mb-2">Pratinjau Foto Profil</h6>
                        <div class="cropper-preview-circle mx-auto shadow-sm mb-2">
                        </div>
                        <p class="text-muted small mb-0" style="font-size: 0.78rem;">Foto Anda akan tampil dalam bentuk lingkaran seperti di atas.</p>
                    </div>

                </div>
            </div>

            <div class="modal-footer border-top px-4 py-3 bg-light d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">
                    Batal
                </button>
                <button type="button" class="btn btn-medigo-primary rounded-pill px-4 fw-semibold text-white shadow-xs" id="btnApplyCrop" style="background-color: #0b2b52;">
                    <i class="bi bi-check2 me-1"></i> Terapkan Foto
                </button>
            </div>

        </div>
    </div>
</div>

@push('scripts')
<!-- Local Cropper.js JavaScript (Zero External Dependencies) -->
<script src="{{ asset('vendor/cropperjs/cropper.min.js') }}?v=1.6.2"></script>

<script>
    // Password visibility toggle
    function togglePassVisibility(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);
        if (!input || !icon) return;

        const isPassword = input.getAttribute('type') === 'password';
        input.setAttribute('type', isPassword ? 'text' : 'password');
        icon.classList.toggle('bi-eye', !isPassword);
        icon.classList.toggle('bi-eye-slash', isPassword);
    }

    // Avatar Upload & Cropper Logic
    document.addEventListener('DOMContentLoaded', function () {
        const avatarFileInput = document.getElementById('avatarFileInput');
        const avatarCroppedInput = document.getElementById('avatarCroppedInput');
        const avatarPreviewImage = document.getElementById('avatarPreviewImage');
        const avatarClickableContainer = document.getElementById('avatarClickableContainer');
        const btnTriggerAvatarUpload = document.getElementById('btnTriggerAvatarUpload');
        const btnRemoveCropped = document.getElementById('btnRemoveCropped');
        const imageToCrop = document.getElementById('imageToCrop');
        const btnApplyCrop = document.getElementById('btnApplyCrop');
        const cropAvatarModalEl = document.getElementById('cropAvatarModal');
        
        const originalAvatarUrl = '{{ $user->avatar_url }}';
        let cropperInstance = null;
        let cropModal = null;

        if (typeof bootstrap !== 'undefined' && cropAvatarModalEl) {
            cropModal = new bootstrap.Modal(cropAvatarModalEl);
        }

        // Trigger file dialog saat klik avatar atau tombol kamera
        function triggerFileInput() {
            avatarFileInput?.click();
        }

        avatarClickableContainer?.addEventListener('click', triggerFileInput);
        btnTriggerAvatarUpload?.addEventListener('click', function(e) {
            e.stopPropagation();
            triggerFileInput();
        });

        // Inisialisasi Cropper instance
        function initCropper() {
            if (typeof Cropper === 'undefined') {
                console.error('Cropper.js belum terdefinisi!');
                return;
            }

            if (cropperInstance) {
                cropperInstance.destroy();
                cropperInstance = null;
            }

            cropperInstance = new Cropper(imageToCrop, {
                aspectRatio: 1, // 1:1 Circle / Square
                viewMode: 1,
                dragMode: 'move',
                autoCropArea: 0.85,
                restore: false,
                guides: true,
                center: true,
                highlight: false,
                cropBoxMovable: true,
                cropBoxResizable: true,
                toggleDragModeOnDblclick: false,
                preview: '.cropper-preview-circle',
            });
        }

        // File selection & validation
        avatarFileInput?.addEventListener('change', function (e) {
            const file = e.target.files && e.target.files[0];
            if (!file) return;

            // 1. Validasi Tipe File (jpg, png, jpeg)
            const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
            const fileExtension = file.name.split('.').pop().toLowerCase();
            const allowedExtensions = ['jpg', 'jpeg', 'png'];

            if (!allowedTypes.includes(file.type) && !allowedExtensions.includes(fileExtension)) {
                if (window.showGlobalToast) {
                    window.showGlobalToast('Format file tidak didukung! Harap unggah foto berekstensi JPG, JPEG, atau PNG.', 'error');
                } else {
                    alert('Format file tidak didukung! Harap unggah foto berekstensi JPG, JPEG, atau PNG.');
                }
                avatarFileInput.value = '';
                return;
            }

            // 2. Validasi Ukuran File (Maksimal 2MB = 2,097,152 bytes)
            const maxSize = 2 * 1024 * 1024;
            if (file.size > maxSize) {
                if (window.showGlobalToast) {
                    window.showGlobalToast('Ukuran foto terlalu besar! Maksimal ukuran file adalah 2MB.', 'error');
                } else {
                    alert('Ukuran foto terlalu besar! Maksimal ukuran file adalah 2MB.');
                }
                avatarFileInput.value = '';
                return;
            }

            // 3. Load ke Cropper Modal
            const reader = new FileReader();
            reader.onload = function (event) {
                if (cropperInstance) {
                    cropperInstance.destroy();
                    cropperInstance = null;
                }

                imageToCrop.src = event.target.result;

                if (cropModal) {
                    cropModal.show();
                } else if (typeof bootstrap !== 'undefined' && cropAvatarModalEl) {
                    cropModal = new bootstrap.Modal(cropAvatarModalEl);
                    cropModal.show();
                }
            };
            reader.readAsDataURL(file);
        });

        // Inisialisasi Cropper saat modal sudah tampil sepenuhnya di layar
        cropAvatarModalEl?.addEventListener('shown.bs.modal', function () {
            initCropper();
        });

        // Cleanup Cropper saat modal ditutup
        cropAvatarModalEl?.addEventListener('hidden.bs.modal', function () {
            if (cropperInstance) {
                cropperInstance.destroy();
                cropperInstance = null;
            }
            avatarFileInput.value = '';
        });

        // Cropper Tool Controls: Zoom & Rotate
        document.getElementById('btnZoomInCrop')?.addEventListener('click', function () {
            if (cropperInstance) {
                cropperInstance.zoom(0.1);
            }
        });

        document.getElementById('btnZoomOutCrop')?.addEventListener('click', function () {
            if (cropperInstance) {
                cropperInstance.zoom(-0.1);
            }
        });

        document.getElementById('btnRotateLeftCrop')?.addEventListener('click', function () {
            if (cropperInstance) {
                cropperInstance.rotate(-90);
            }
        });

        document.getElementById('btnRotateRightCrop')?.addEventListener('click', function () {
            if (cropperInstance) {
                cropperInstance.rotate(90);
            }
        });

        document.getElementById('btnResetCrop')?.addEventListener('click', function () {
            if (cropperInstance) {
                cropperInstance.reset();
            }
        });

        // Terapkan Hasil Pemotongan Foto
        btnApplyCrop?.addEventListener('click', function () {
            let base64Cropped = null;

            if (cropperInstance) {
                try {
                    const croppedCanvas = cropperInstance.getCroppedCanvas({
                        width: 400,
                        height: 400,
                        imageSmoothingEnabled: true,
                        imageSmoothingQuality: 'high',
                    });
                    if (croppedCanvas) {
                        base64Cropped = croppedCanvas.toDataURL('image/jpeg', 0.92);
                    }
                } catch (err) {
                    console.error('Gagal mengambil canvas cropper:', err);
                }
            }

            // Fallback jika tidak didapatkan dari canvas
            if (!base64Cropped && imageToCrop.src && imageToCrop.src.startsWith('data:image/')) {
                base64Cropped = imageToCrop.src;
            }

            if (!base64Cropped) {
                alert('Foto belum dapat diproses. Silakan pilih foto kembali.');
                return;
            }

            // Pasang ke preview dan input form
            avatarPreviewImage.src = base64Cropped;
            avatarCroppedInput.value = base64Cropped;

            // Tampilkan tombol Batal Ganti Foto
            btnRemoveCropped?.classList.remove('d-none');

            // Tutup modal
            if (cropModal) {
                cropModal.hide();
            }

            if (window.showGlobalToast) {
                window.showGlobalToast('Foto berhasil disesuaikan! Klik "Simpan Perubahan" untuk menyimpan.', 'success');
            }
        });

        // Batal Ganti Foto (Kembalikan ke foto semula sebelum diedit)
        btnRemoveCropped?.addEventListener('click', function () {
            avatarPreviewImage.src = originalAvatarUrl;
            avatarCroppedInput.value = '';
            avatarFileInput.value = '';
            btnRemoveCropped.classList.add('d-none');

            if (window.showGlobalToast) {
                window.showGlobalToast('Perubahan foto dibatalkan.', 'info');
            }
        });
    });
</script>
@endpush
@endsection
