@extends('layouts.admin')

@section('title', 'Kelola Foto Faskes - Admin MediGO')
@section('page_title', 'Kelola Foto Rumah Sakit')

@php
    $faskesId = $faskes['id_faskes'] ?? $faskes['id'] ?? 0;
    $namaFaskes = $faskes['nama_faskes'] ?? 'Fasilitas Kesehatan';
    $thumbnail = $photos['thumbnail'] ?? null;
    $banner = $photos['banner'] ?? null;
    $galeri = $photos['galeri'] ?? [];
@endphp

@section('content')
<div class="container-fluid px-0" style="max-width: 1100px;">
    <a href="{{ route('admin.faskes') }}" class="text-decoration-none text-secondary small d-inline-flex align-items-center gap-1 mb-3">
        <i class="bi bi-arrow-left"></i> Kembali ke Direktori
    </a>

    <div class="mg-admin-pagehead">
        <div>
            <h1 class="mg-admin-title">Foto {{ $namaFaskes }}</h1>
            <p class="mg-admin-subtitle">Banner tampil di atas halaman detail, thumbnail di kartu beranda &amp; peta, galeri di tab Galeri.</p>
        </div>
        <a href="{{ route('faskes.detail', $faskesId) }}" target="_blank" class="btn mg-btn-outline mg-btn-rect px-3 py-2">
            <i class="bi bi-box-arrow-up-right me-1"></i> Lihat Halaman Detail
        </a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger rounded-3"><i class="bi bi-exclamation-triangle-fill me-1"></i>{{ $errors->first() }}</div>
    @endif

    <div class="row g-4">
        {{-- Banner & Thumbnail --}}
        @foreach([['key' => 'banner', 'label' => 'Banner', 'foto' => $banner], ['key' => 'thumbnail', 'label' => 'Thumbnail', 'foto' => $thumbnail]] as $slot)
            <div class="col-md-6">
                <div class="mg-admin-card p-3 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h2 class="h6 fw-bold m-0">{{ $slot['label'] }}</h2>
                        @if($slot['foto'])
                            <form action="{{ route('admin.faskes.photos.destroy', [$faskesId, $slot['foto']['id_foto']]) }}" method="POST" onsubmit="return confirm('Hapus foto {{ strtolower($slot['label']) }} ini?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="mg-icon-btn mg-icon-btn--danger" title="Hapus {{ $slot['label'] }}"><i class="bi bi-trash"></i></button>
                            </form>
                        @endif
                    </div>
                    <div class="mg-photo-slot {{ $slot['key'] === 'banner' ? 'mg-photo-slot--banner' : '' }}">
                        @if($slot['foto'])
                            <img src="{{ $slot['foto']['url'] }}" alt="{{ $slot['label'] }} {{ $namaFaskes }}">
                        @else
                            <span class="text-muted small"><i class="bi bi-image me-1"></i>Belum ada {{ strtolower($slot['label']) }}</span>
                        @endif
                    </div>
                    <form action="{{ route('admin.faskes.photos.upload', $faskesId) }}" method="POST" enctype="multipart/form-data" class="d-flex gap-2 mt-3">
                        @csrf
                        <input type="file" name="{{ $slot['key'] }}" accept="image/jpeg,image/png,image/webp" class="form-control form-control-sm" required>
                        <button type="submit" class="btn btn-sm mg-btn-primary mg-btn-rect text-nowrap">{{ $slot['foto'] ? 'Ganti' : 'Unggah' }}</button>
                    </form>
                    <div class="form-text">JPG/PNG/WEBP, maks. 5MB. Atau pilih dari galeri di bawah.</div>
                </div>
            </div>
        @endforeach

        {{-- Galeri --}}
        <div class="col-12">
            <div class="mg-admin-card p-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <h2 class="h6 fw-bold m-0">Galeri <span class="text-muted fw-normal">({{ count($galeri) }}/20)</span></h2>
                    <form action="{{ route('admin.faskes.photos.upload', $faskesId) }}" method="POST" enctype="multipart/form-data" class="d-flex gap-2">
                        @csrf
                        <input type="file" name="galeri[]" accept="image/jpeg,image/png,image/webp" class="form-control form-control-sm" multiple required>
                        <button type="submit" class="btn btn-sm mg-btn-primary mg-btn-rect text-nowrap">Tambah ke Galeri</button>
                    </form>
                </div>

                @if(count($galeri))
                    <div class="mg-admin-gallery">
                        @foreach($galeri as $foto)
                            <div class="mg-admin-gallery-item">
                                <img src="{{ $foto['url'] }}" alt="Foto galeri {{ $namaFaskes }}" loading="lazy">
                                <div class="mg-admin-gallery-actions">
                                    <form action="{{ route('admin.faskes.photos.set', [$faskesId, $foto['id_foto']]) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="tipe" value="thumbnail">
                                        <button type="submit" class="btn btn-sm btn-light" title="Jadikan thumbnail">Thumbnail</button>
                                    </form>
                                    <form action="{{ route('admin.faskes.photos.set', [$faskesId, $foto['id_foto']]) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="tipe" value="banner">
                                        <button type="submit" class="btn btn-sm btn-light" title="Jadikan banner">Banner</button>
                                    </form>
                                    <form action="{{ route('admin.faskes.photos.destroy', [$faskesId, $foto['id_foto']]) }}" method="POST" onsubmit="return confirm('Hapus foto ini dari galeri?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-light text-danger" title="Hapus foto"><i class="bi bi-trash"></i></button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="mg-empty-state py-4">
                        <div class="mg-empty-icon"><i class="bi bi-images"></i></div>
                        <div class="small text-secondary">Belum ada foto galeri.</div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
