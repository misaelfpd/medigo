@php
    $beUrl = rtrim(str_replace('localhost', '127.0.0.1', config('services.medigo.url', 'http://127.0.0.1:8005')), '/');
    
    $discImages = [];
    if (!empty($discussion['images_list']) && is_array($discussion['images_list'])) {
        $discImages = $discussion['images_list'];
    } elseif (!empty($discussion['images'])) {
        $decoded = is_string($discussion['images']) ? json_decode($discussion['images'], true) : $discussion['images'];
        if (is_array($decoded)) {
            $discImages = $decoded;
        }
    } elseif (!empty($discussion['gambar_diskusi'])) {
        $discImages = [$discussion['gambar_diskusi']];
    } elseif (!empty($discussion['image_path'])) {
        $discImages = [$discussion['image_path']];
    }

    $title = $discussion['judul_diskusi'] ?? ($discussion['title'] ?? '');
    $body = $discussion['isi_diskusi'] ?? ($discussion['body'] ?? ($discussion['content'] ?? ''));
@endphp

@php
    $category = $discussion['kategori'] ?? ($discussion['category'] ?? 'Umum');
    $mention = $discussion['mentioned_faskes'] ?? null;
    $mentionName = is_array($mention) ? ($mention['nama_faskes'] ?? null) : null;
    $mentionId = is_array($mention) ? ($mention['id_faskes'] ?? null) : null;
@endphp

<h1 class="mg-thread-title">{{ $title }}</h1>
<div class="mg-discussion-meta mb-3">
    <span class="mg-chip mg-chip--category">{{ $category }}</span>
    @if($mentionName)
        <a href="{{ $mentionId ? route('faskes.detail', $mentionId) : '#' }}" class="mg-chip mg-chip--mention" title="Faskes yang dibahas">
            <i class="bi bi-hospital"></i> {{ $mentionName }}
        </a>
    @endif
    <span class="mg-discussion-time">&bull; {{ \Carbon\Carbon::parse($discussion['created_at'] ?? now())->diffForHumans() }}</span>
</div>
<div class="mg-thread-body mb-4">{!! nl2br(e($body)) !!}</div>

@if(!empty($discImages))
    <div class="mb-4 mg-thread-images">
        @foreach($discImages as $img)
            @php
                $fullImgUrl = str_starts_with($img, 'http') ? str_replace('http://localhost', $beUrl, $img) : $beUrl . '/storage/' . ltrim($img, '/');
            @endphp
            <div class="clickable-image {{ $loop->first ? 'mg-thread-image-main' : 'mg-thread-image-thumb' }}" onclick="openImageViewer(@js($fullImgUrl))">
                <img src="{{ $fullImgUrl }}" alt="Lampiran Diskusi" loading="lazy">
            </div>
        @endforeach
    </div>
@endif

@php
    $likesCount = (int) ($discussion['likes_count'] ?? 0);
    $likedByMe = (bool) ($discussion['liked_by_me'] ?? false);
    $discOwnerEmail = $discussion['author_email'] ?? ($discussion['email_user'] ?? null);
    $isOwnDiscussion = Auth::check() && $discOwnerEmail && strtolower(Auth::user()->email ?? '') === strtolower($discOwnerEmail);
    $commentsCount = isset($discussion['comments_count'])
        ? (int) $discussion['comments_count']
        : collect($comments ?? [])->sum(fn ($c) => 1 + count($c['replies'] ?? []));
@endphp
<div class="mg-thread-actions">
    <button type="button"
            class="mg-like-btn {{ $likedByMe ? 'is-liked' : '' }}"
            data-like-type="discussion"
            data-like-id="{{ $discussion['id_diskusi'] ?? ($discussion['id'] ?? '') }}"
            data-liked="{{ $likedByMe ? '1' : '0' }}"
            data-own="{{ $isOwnDiscussion ? '1' : '0' }}"
            data-login="{{ Auth::check() ? '1' : '0' }}"
            aria-pressed="{{ $likedByMe ? 'true' : 'false' }}">
        <i class="bi {{ $likedByMe ? 'bi-hand-thumbs-up-fill' : 'bi-hand-thumbs-up' }}"></i>
        <span><span data-like-count>{{ $likesCount }}</span> Suka</span>
    </button>
    <a href="#comments" class="mg-discussion-stat">
        <i class="bi bi-chat-square"></i> <span>{{ $commentsCount }} Balasan</span>
    </a>
</div>