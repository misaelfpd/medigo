@props(['faskes' => [], 'faskesDisplayName' => null])

@php
    $faskesDisplayName = $faskesDisplayName ?? ($faskes['nama_faskes'] ?? 'Fasilitas Kesehatan');
    $tipeLabel = (!empty($faskes['tipe']) && strcasecmp($faskes['tipe'], 'Fasilitas Kesehatan') !== 0)
        ? $faskes['tipe']
        : 'Rumah Sakit';

    $typeSlug = strtolower($faskes['type_slug'] ?? '');
    $tipeLower = strtolower($tipeLabel);

    $heroIcon = 'bi-hospital';
    if ($typeSlug === 'puskesmas' || str_contains($tipeLower, 'puskesmas')) {
        $heroIcon = 'bi-heart-pulse';
    } elseif ($typeSlug === 'clinic' || str_contains($tipeLower, 'klinik')) {
        $heroIcon = 'bi-plus-square';
    } elseif (str_contains($tipeLower, 'apotek') || str_contains($tipeLower, 'apotik')) {
        $heroIcon = 'bi-capsule';
    } elseif (str_contains($tipeLower, 'laboratorium') || str_contains($tipeLower, 'lab')) {
        $heroIcon = 'bi-activity';
    }
@endphp

<div class="detail-hero-banner rounded-4 overflow-hidden position-relative d-flex flex-column align-items-center justify-content-center text-center mb-3 mb-lg-4">
    <div class="detail-hero-icon-circle mb-2">
        <i class="bi {{ $heroIcon }}"></i>
    </div>
    <span class="detail-hero-type-pill">
        {{ $tipeLabel }}
    </span>
</div>