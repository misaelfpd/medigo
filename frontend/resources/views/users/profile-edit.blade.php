@extends('layouts.app')

@section('title', 'Edit Profil - MediGO')

@push('styles')
    <!-- Local Cropper.js CSS (Zero External Dependencies) -->
    <link rel="stylesheet" href="{{ asset('vendor/cropperjs/cropper.min.css') }}?v=1.6.2">
    @vite(['resources/css/pages/profile-edit.css', 'resources/js/pages/profile-edit.js'])
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
                            <div class="avatar-clickable-wrapper position-relative flex-shrink-0" id="avatarClickableContainer" style="width: 86px; height: 86px;" title="Klik ikon kamera untuk mengganti foto profil"
                                 data-original-avatar="{{ $user->avatar_url ?? '' }}">
                                <img src="{{ $user->avatar_url ?? '' }}"
                                     alt="{{ $user->name }}"
                                     id="avatarPreviewImage"
                                     class="rounded-circle w-100 h-100 object-fit-cover border shadow-xs {{ $user->avatar_url ? '' : 'd-none' }}"
                                     style="border-color: #cbd5e1 !important;">
                                <span id="avatarPreviewInitial" class="avatar-preview-initial {{ $user->avatar_url ? 'd-none' : '' }}" aria-hidden="true">
                                    {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(trim($user->name ?? '') ?: 'P', 0, 1)) }}
                                </span>
                                
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
                                        data-toggle-password="current_password" aria-label="Tampilkan kata sandi"
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
                                        data-toggle-password="new_password" aria-label="Tampilkan kata sandi"
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

@endpush
@endsection
