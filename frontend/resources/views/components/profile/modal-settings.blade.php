<div class="modal fade" id="settingsModal" tabindex="-1" aria-labelledby="settingsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            <div class="modal-header border-bottom px-4 py-3 bg-light">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-gear-fill text-medigo-primary fs-5"></i>
                    <h6 class="modal-title fw-bold text-dark m-0" id="settingsModalLabel">Pengaturan Akun & Profil</h6>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('profile.update') }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body p-4">
                    <!-- Update Nama -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark small">Nama Lengkap</label>
                        <input type="text" name="name" class="form-control rounded-3 @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required>
                        @error('name')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Email (Disabled) -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark small">Email</label>
                        <input type="email" class="form-control rounded-3 bg-light" value="{{ $user->email }}" disabled readonly>
                    </div>

                    <hr class="my-3 opacity-25">

                    <!-- Update Password -->
                    <h6 class="fw-bold text-dark small mb-2">Ubah Kata Sandi (Opsional)</h6>
                    <div class="mb-2">
                        <input type="password" name="current_password" class="form-control rounded-3 small @error('current_password') is-invalid @enderror" placeholder="Kata sandi saat ini">
                        @error('current_password')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-2">
                        <input type="password" name="new_password" class="form-control rounded-3 small @error('new_password') is-invalid @enderror" placeholder="Kata sandi baru (min. 8 karakter)">
                        @error('new_password')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-2">
                        <input type="password" name="new_password_confirmation" class="form-control rounded-3 small" placeholder="Konfirmasi kata sandi baru">
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3 bg-light d-flex justify-content-between">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-medigo-primary rounded-pill px-4 fw-semibold">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

@if($errors->any())
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var settingsModalEl = document.getElementById('settingsModal');
            if (settingsModalEl && typeof bootstrap !== 'undefined') {
                var modal = new bootstrap.Modal(settingsModalEl);
                modal.show();
            }
        });
    </script>
@endif