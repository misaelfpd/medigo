@props(['faskes' => []])

<div class="detail-mobile-topbar d-flex d-lg-none align-items-center justify-content-between px-3 py-3 bg-white border-bottom sticky-top">
    <a href="{{ route('faskes.index') }}" class="btn-detail-back text-dark d-flex align-items-center text-decoration-none" title="Kembali ke Beranda">
        <i class="bi bi-arrow-left fs-4"></i>
    </a>
    <h6 class="m-0 fw-bold text-dark detail-mobile-heading text-truncate px-2">
        Detail {{ $faskes['tipe'] ?? 'Rumah Sakit' }}
    </h6>
    <div style="width: 24px;"></div>
</div>