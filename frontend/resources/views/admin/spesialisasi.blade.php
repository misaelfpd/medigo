@extends('layouts.admin')

@section('title', 'Manajemen Spesialisasi Medis - Admin MediGO')
@section('page_title', 'Manajemen Spesialisasi Medis')

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    <div class="mg-admin-pagehead mb-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <h1 class="mg-admin-title">Spesialisasi Medis</h1>
            <p class="mg-admin-subtitle">Kelola daftar master keahlian / spesialisasi dokter pada fasilitas kesehatan (misal: Spesialis Anak, Jantung & Pembuluh Darah, Penyakit Dalam).</p>
        </div>
        <button type="button" class="btn mg-btn-primary mg-btn-rect d-inline-flex align-items-center gap-2 px-3 py-2" data-bs-toggle="modal" data-bs-target="#modalAddSpesialisasi">
            <i class="bi bi-plus-lg"></i>
            <span>Tambah Spesialisasi</span>
        </button>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 py-2 px-3 mb-3 d-flex align-items-center gap-2" role="alert">
            <i class="bi bi-check-circle-fill text-success"></i>
            <div class="flex-grow-1" style="font-size: 0.88rem;">{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-3 py-2 px-3 mb-3 d-flex align-items-center gap-2" role="alert">
            <i class="bi bi-exclamation-circle-fill text-danger"></i>
            <div class="flex-grow-1" style="font-size: 0.88rem;">{{ session('error') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Main Card --}}
    <div class="mg-admin-card mb-4">
        {{-- Toolbar / Search --}}
        <div class="mg-admin-toolbar px-3 py-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2">
            <form action="{{ route('admin.spesialisasi') }}" method="GET" class="d-flex align-items-center gap-2 m-0 flex-grow-1" style="max-width: 400px;">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Cari nama spesialisasi..." value="{{ $search }}">
                </div>
                <button type="submit" class="btn btn-sm mg-btn-primary mg-btn-rect px-3">Cari</button>
                @if(!empty($search))
                    <a href="{{ route('admin.spesialisasi') }}" class="btn btn-sm btn-outline-secondary" title="Reset Pencarian"><i class="bi bi-arrow-counterclockwise"></i></a>
                @endif
            </form>
            <div class="text-muted small">
                Total: <strong>{{ count($spesialisasi) }}</strong> spesialisasi
            </div>
        </div>

        {{-- Table --}}
        <div class="table-responsive">
            <table class="table align-middle mb-0 mg-admin-table">
                <thead>
                    <tr>
                        <th scope="col" style="width: 70px;" class="ps-3">No</th>
                        <th scope="col">Nama Spesialisasi Dokter</th>
                        <th scope="col" class="text-center" style="width: 180px;">Faskes Terkait</th>
                        <th scope="col" class="text-center" style="width: 150px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($spesialisasi as $idx => $item)
                        @php
                            $sId = is_array($item) ? ($item['id_spesialisasi'] ?? 0) : ($item->id_spesialisasi ?? 0);
                            $sNama = is_array($item) ? ($item['nama_spesialisasi'] ?? '') : ($item->nama_spesialisasi ?? '');
                            $sTotal = is_array($item) ? ($item['total_faskes'] ?? 0) : ($item->total_faskes ?? 0);
                        @endphp
                        <tr>
                            <td class="ps-3 text-secondary font-monospace" style="font-size: 0.85rem;">{{ $idx + 1 }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2.5">
                                    <span class="mg-admin-rowicon bg-light text-medigo-primary border rounded-3" style="width: 34px; height: 34px; font-size: 0.95rem;">
                                        <i class="bi bi-heart-pulse-fill"></i>
                                    </span>
                                    <div>
                                        <div class="mg-admin-rowtitle">{{ $sNama }}</div>
                                        <div class="mg-admin-rowsub">ID: SPE-{{ str_pad($sId, 3, '0', STR_PAD_LEFT) }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge rounded-pill bg-light text-dark border px-2.5 py-1" style="font-size: 0.78rem;">
                                    <i class="bi bi-hospital me-1 text-medigo-primary"></i> {{ $sTotal }} Faskes
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="d-inline-flex gap-1">
                                    <button type="button" class="mg-icon-btn btn-edit-feature" 
                                            data-id="{{ $sId }}" 
                                            data-name="{{ $sNama }}" 
                                            data-action="{{ route('admin.spesialisasi.update', $sId) }}"
                                            title="Ubah Spesialisasi" 
                                            aria-label="Ubah {{ $sNama }}">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form action="{{ route('admin.spesialisasi.destroy', $sId) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus spesialisasi {{ addslashes($sNama) }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="mg-icon-btn text-danger" title="Hapus Spesialisasi" aria-label="Hapus {{ $sNama }}">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                <span>Tidak ada data spesialisasi medis{{ !empty($search) ? ' yang sesuai dengan pencarian' : '' }}.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Modal Tambah Spesialisasi --}}
<div class="modal fade" id="modalAddSpesialisasi" tabindex="-1" aria-labelledby="modalAddSpesialisasiLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <form action="{{ route('admin.spesialisasi.store') }}" method="POST">
                @csrf
                <div class="modal-header border-bottom px-4 py-3 bg-light">
                    <h5 class="modal-title fw-bold text-dark fs-6" id="modalAddSpesialisasiLabel">
                        <i class="bi bi-plus-circle me-1 text-medigo-primary"></i> Tambah Spesialisasi Medis
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="add_nama_spesialisasi" class="form-label fw-semibold text-dark" style="font-size: 0.85rem;">
                            Nama Spesialisasi Dokter <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="add_nama_spesialisasi" name="nama_spesialisasi" placeholder="Contoh: Spesialis Anak (Sp.A)" required maxlength="100">
                        <div class="form-text" style="font-size: 0.78rem;">Dapat menyertakan gelar profesi spesialisasi jika relevan.</div>
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3 bg-light">
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-medigo-primary rounded-pill px-4 fw-semibold text-white">Simpan Spesialisasi</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Edit Spesialisasi --}}
<div class="modal fade" id="modalEditSpesialisasi" tabindex="-1" aria-labelledby="modalEditSpesialisasiLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <form id="formEditSpesialisasi" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header border-bottom px-4 py-3 bg-light">
                    <h5 class="modal-title fw-bold text-dark fs-6" id="modalEditSpesialisasiLabel">
                        <i class="bi bi-pencil-square me-1 text-medigo-primary"></i> Ubah Spesialisasi Medis
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="edit_nama_spesialisasi" class="form-label fw-semibold text-dark" style="font-size: 0.85rem;">
                            Nama Spesialisasi Dokter <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="edit_nama_spesialisasi" name="nama_spesialisasi" required maxlength="100">
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3 bg-light">
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-medigo-primary rounded-pill px-4 fw-semibold text-white">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
    @vite('resources/js/admin/master-features.js')
@endpush
@endsection
