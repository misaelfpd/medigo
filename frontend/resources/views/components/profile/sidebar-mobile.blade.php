<!-- Bagian atas (Avatar + Stat) -->
<div class="card border rounded-4 bg-white p-3 shadow-2xs mb-3">
    <div class="d-flex align-items-center gap-3">
        <x-avatar :name="$user->name" :src="$user->avatar_url" :size="50" class="profile-avatar-mobile flex-shrink-0" />
        <div class="min-w-0 flex-grow-1">
            <h5 class="fw-bold text-dark mb-0 text-truncate" style="font-size: 1.05rem;">{{ $user->name }}</h5>
            <p class="text-muted mb-0 text-truncate" style="font-size: 0.82rem;">{{ $user->email }}</p>
        </div>
    </div>
</div>

<div class="row g-2 mb-3">
    <div class="col-6">
        <div class="card border rounded-4 bg-white p-3 shadow-2xs h-100">
            <div class="stat-icon-circle rounded-circle d-flex align-items-center justify-content-center mb-2"><i class="bi bi-star-fill"></i></div>
            <div class="fw-bold text-dark mb-1" style="font-size: 0.84rem;">Aktivitas Rating</div>
            <div class="text-muted" style="font-size: 0.72rem;">{{ $stats['reviews_count'] ?? 0 }} rating</div>
        </div>
    </div>
    <div class="col-6">
        <div class="card border rounded-4 bg-white p-3 shadow-2xs h-100">
            <div class="stat-icon-circle rounded-circle d-flex align-items-center justify-content-center mb-2"><i class="bi bi-chat-dots-fill"></i></div>
            <div class="fw-bold text-dark mb-1" style="font-size: 0.84rem;">Aktivitas Forum</div>
            <div class="text-muted" style="font-size: 0.72rem;">{{ $stats['discussions_count'] ?? 0 }} post</div>
        </div>
    </div>
</div>