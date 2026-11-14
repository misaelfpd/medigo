/**
 * Halaman ubah profil: lihat kata sandi, unggah & crop foto profil (Cropper.js dari public/vendor).
 */
const notifyError = (message) => {
    if (typeof window.showGlobalToast === 'function') window.showGlobalToast(message, 'error');
    else window.alert(message);
};

// Tombol lihat/sembunyikan kata sandi: <button data-toggle-password="idInput">
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-toggle-password]').forEach(function (button) {
        const input = document.getElementById(button.dataset.togglePassword);
        const icon = button.querySelector('i');
        if (!input) return;
        button.addEventListener('click', function () {
            const isPassword = input.getAttribute('type') === 'password';
            input.setAttribute('type', isPassword ? 'text' : 'password');
            if (icon) {
                icon.classList.toggle('bi-eye', !isPassword);
                icon.classList.toggle('bi-eye-slash', isPassword);
            }
        });
    });
});

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

    const avatarPreviewInitial = document.getElementById('avatarPreviewInitial');
    const originalAvatarUrl = avatarClickableContainer ? avatarClickableContainer.dataset.originalAvatar || '' : '';

    // Tampilkan foto bila ada, selain itu huruf depan nama
    function setAvatarPreview(src) {
        if (!avatarPreviewImage) return;
        if (src) {
            avatarPreviewImage.src = src;
            avatarPreviewImage.classList.remove('d-none');
            avatarPreviewInitial?.classList.add('d-none');
        } else {
            avatarPreviewImage.removeAttribute('src');
            avatarPreviewImage.classList.add('d-none');
            avatarPreviewInitial?.classList.remove('d-none');
        }
    }
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
                notifyError('Format file tidak didukung! Harap unggah foto berekstensi JPG, JPEG, atau PNG.');
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
                notifyError('Ukuran foto terlalu besar! Maksimal ukuran file adalah 2MB.');
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
            notifyError('Foto belum dapat diproses. Silakan pilih foto kembali.');
            return;
        }

        // Pasang ke preview dan input form
        setAvatarPreview(base64Cropped);
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
        setAvatarPreview(originalAvatarUrl);
        avatarCroppedInput.value = '';
        avatarFileInput.value = '';
        btnRemoveCropped.classList.add('d-none');

        if (window.showGlobalToast) {
            window.showGlobalToast('Perubahan foto dibatalkan.', 'info');
        }
    });
});
