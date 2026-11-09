<!-- Desktop User Profile Sidebar -->
<div class="card border rounded-4 bg-white p-4 shadow-2xs mb-4 text-center">
    <div class="mb-3 mx-auto" style="width: 80px; height: 80px;">
        <img src="{{ asset('images/user_avatar.jpg') }}" alt="{{ $user->name ?? $user->nama_user }}" class="rounded-circle w-100 h-100 object-fit-cover shadow-xs border">
    </div>
    <h5 class="fw-bold text-dark mb-1">{{ $user->name ?? $user->nama_user }}</h5>
    <p class="text-muted small mb-0">{{ $user->email ?? $user->email_user }}</p>
</div>

<!-- Stats Card -->
<div class="row g-2 mb-4">
    <div class="col-6">
        <div class="card border rounded-4 bg-white p-3 shadow-2xs h-100 text-center">
            <div class="stat-icon-circle rounded-circle d-flex align-items-center justify-content-center mx-auto mb-2 text-warning bg-warning-subtle" style="width: 36px; height: 36px;">
                <i class="bi bi-star-fill"></i>
            </div>
            <div class="fw-bold text-dark mb-1" style="font-size: 0.84rem;">Aktivitas Rating</div>
            <div class="text-muted" style="font-size: 0.72rem;">{{ $stats['reviews_count'] ?? 0 }} rating</div>
        </div>
    </div>
    <div class="col-6">
        <div class="card border rounded-4 bg-white p-3 shadow-2xs h-100 text-center">
            <div class="stat-icon-circle rounded-circle d-flex align-items-center justify-content-center mx-auto mb-2 text-primary bg-primary-subtle" style="width: 36px; height: 36px;">
                <i class="bi bi-chat-dots-fill"></i>
            </div>
            <div class="fw-bold text-dark mb-1" style="font-size: 0.84rem;">Aktivitas Forum</div>
            <div class="text-muted" style="font-size: 0.72rem;">{{ $stats['discussions_count'] ?? 0 }} post</div>
        </div>
    </div>
</div>

<!-- Settings & Navigation Card -->
<div class="card border rounded-4 bg-white shadow-2xs overflow-hidden mb-4">
    <div class="list-group list-group-flush profile-menu-list">
        <a href="#settingsModal" data-bs-toggle="modal" class="list-group-item list-group-item-action p-3 d-flex align-items-center gap-2">
            <i class="bi bi-gear fs-5 text-secondary"></i>
            <span class="fw-medium text-dark">Pengaturan Akun</span>
        </a>
    </div>
</div>

<!-- Logout Form -->
<form action="{{ route('logout') }}" method="POST">
    @csrf
    <button type="submit" class="btn btn-outline-danger w-100 rounded-4 py-2-5 d-flex align-items-center justify-content-center gap-2 fw-semibold">
        <i class="bi bi-box-arrow-left"></i>
        <span>Keluar dari Akun</span>
    </button>
</form>
