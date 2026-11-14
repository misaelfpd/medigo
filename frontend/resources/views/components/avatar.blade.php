{{-- Avatar pengguna: foto jika ada, jika tidak huruf depan nama (default MediGO) --}}
@props(['name' => null, 'src' => null, 'size' => 40])

@php
    $avatarName = trim((string) ($name ?: 'Pengguna'));
    $initial = mb_strtoupper(mb_substr($avatarName, 0, 1)) ?: 'P';
    $px = (int) $size;
@endphp

<span {{ $attributes->merge(['class' => 'mg-avatar']) }}
      style="width: {{ $px }}px; height: {{ $px }}px; font-size: {{ max(11, round($px * 0.42)) }}px;"
      title="{{ $avatarName }}">
    @if(!empty($src))
        <img src="{{ $src }}" alt="Foto {{ $avatarName }}" loading="lazy"
             onerror="this.remove();">
    @endif
    <span class="mg-avatar-initial" aria-hidden="{{ !empty($src) ? 'true' : 'false' }}">{{ $initial }}</span>
</span>
