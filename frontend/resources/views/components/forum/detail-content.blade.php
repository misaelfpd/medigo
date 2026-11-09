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
    } elseif (!empty($discussion['image_path'])) {
        $discImages = [$discussion['image_path']];
    }
@endphp

<h4 class="fw-bold mb-3">{{ $discussion['title'] }}</h4>
<div class="text-secondary mb-4" style="line-height: 1.7; font-size: 0.98rem;">{!! nl2br(e($discussion['body'] ?? $discussion['content'] ?? '')) !!}</div>

@if(!empty($discImages))
    <div class="mb-4">
        <label class="small fw-semibold text-muted text-uppercase mb-2 d-block" style="letter-spacing: 0.5px;">Lampiran Foto:</label>
        <div class="d-flex flex-wrap gap-2">
            @foreach($discImages as $img)
                @php
                    $fullImgUrl = str_starts_with($img, 'http') ? str_replace('http://localhost', $beUrl, $img) : $beUrl . '/storage/' . ltrim($img, '/');
                @endphp
                <div class="rounded-3 overflow-hidden border shadow-2xs cursor-pointer clickable-image" style="max-width: 220px; max-height: 200px;" onclick="openImageViewer('{{ $fullImgUrl }}')">
                    <img src="{{ $fullImgUrl }}" alt="Lampiran Diskusi" class="img-fluid object-fit-cover w-100 h-100" style="max-height: 200px; cursor: pointer;">
                </div>
            @endforeach
        </div>
    </div>
@endif