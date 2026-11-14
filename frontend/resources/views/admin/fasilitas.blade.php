@extends('layouts.admin')

@section('title', 'Manajemen Fasilitas Penunjang - Admin MediGO')
@section('page_title', 'Manajemen Fasilitas Penunjang')

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    <div class="mg-admin-pagehead mb-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <h1 class="mg-admin-title">Fasilitas Penunjang</h1>
            <p class="mg-admin-subtitle">Kelola daftar master fasilitas penunjang medis pada faskes (misal: IGD 24 Jam, Farmasi, Radiologi, ICU).</p>
        </div>
        <button type="button" class="btn mg-btn-primary mg-btn-rect d-inline-flex align-items-center gap-2 px-3 py-2" data-bs-toggle="modal" data-bs-target="#modalAddFasilitas">
            <i class="bi bi-plus-lg"></i>
            <span>Tambah Fasilitas</span>
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
            <form action="{{ route('admin.fasilitas') }}" method="GET" class="d-flex align-items-center gap-2 m-0 flex-grow-1" style="max-width: 400px;">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Cari nama fasilitas..." value="{{ $search }}">
                </div>
                <button type="submit" class="btn btn-sm mg-btn-primary mg-btn-rect px-3">Cari</button>
                @if(!empty($search))
                    <a href="{{ route('admin.fasilitas') }}" class="btn btn-sm btn-outline-secondary" title="Reset Pencarian"><i class="bi bi-arrow-counterclockwise"></i></a>
                @endif
            </form>
            <div class="text-muted small">
                Total: <strong>{{ count($fasilitas) }}</strong> fasilitas
            </div>
        </div>

        {{-- Table --}}
        <div class="table-responsive">
            <table class="table align-middle mb-0 mg-admin-table">
                <thead>
                    <tr>
                        <th scope="col" style="width: 70px;" class="ps-3">No</th>
                        <th scope="col">Nama Fasilitas Penunjang</th>
                        <th scope="col" class="text-center" style="width: 180px;">Faskes Terkait</th>
                        <th scope="col" class="text-center" style="width: 150px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($fasilitas as $idx => $item)
                        @php
                            $fId = is_array($item) ? ($item['id_fasilitas'] ?? 0) : ($item->id_fasilitas ?? 0);
                            $fNama = is_array($item) ? ($item['nama_fasilitas'] ?? '') : ($item->nama_fasilitas ?? '');
                            $fTotal = is_array($item) ? ($item['total_faskes'] ?? 0) : ($item->total_faskes ?? 0);
                        @endphp
                        <tr>
                            <td class="ps-3 text-secondary font-monospace" style="font-size: 0.85rem;">{{ $idx + 1 }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2.5">
                                    <span class="mg-admin-rowicon bg-light text-medigo-primary border rounded-3" style="width: 34px; height: 34px; font-size: 0.95rem;">
                                        <i class="bi bi-building-add"></i>
                                    </span>
                                    <div>
                                        <div class="mg-admin-rowtitle">{{ $fNama }}</div>
                                        <div class="mg-admin-rowsub">ID: FAS-{{ str_pad($fId, 3, '0', STR_PAD_LEFT) }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge rounded-pill bg-light text-dark border px-2.5 py-1" style="font-size: 0.78rem;">
                                    <i class="bi bi-hospital me-1 text-medigo-primary"></i> {{ $fTotal }} Faskes
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="d-inline-flex gap-1">
                                    <button type="button" class="mg-icon-btn btn-edit-feature" 
                                            data-id="{{ $fId }}" 
                                            data-name="{{ $fNama }}" 
                                            data-action="{{ route('admin.fasilitas.update', $fId) }}"
                                            title="Ubah Fasilitas" 
                                            aria-label="Ubah {{ $fNama }}">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form action="{{ route('admin.fasilitas.destroy', $fId) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus fasilitas {{ addslashes($fNama) }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="mg-icon-btn text-danger" title="Hapus Fasilitas" aria-label="Hapus {{ $fNama }}">
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
                                <span>Tidak ada data fasilitas penunjang{{ !empty($search) ? ' yang sesuai dengan pencarian' : '' }}.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Modal Tambah Fasilitas --}}
<div class="modal fade" id="modalAddFasilitas" tabindex="-1" aria-labelledby="modalAddFasilitasLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <form action="{{ route('admin.fasilitas.store') }}" method="POST">
                @csrf
                <div class="modal-header border-bottom px-4 py-3 bg-light">
                    <h5 class="modal-title fw-bold text-dark fs-6" id="modalAddFasilitasLabel">
                        <i class="bi bi-plus-circle me-1 text-medigo-primary"></i> Tambah Fasilitas Penunjang
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="add_nama_fasilitas" class="form-label fw-semibold text-dark" style="font-size: 0.85rem;">
                            Nama Fasilitas Penunjang <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="add_nama_fasilitas" name="nama_fasilitas" placeholder="Contoh: Unit Gawat Darurat (UGD) 24 Jam" required maxlength="150">
                        <div class="form-text" style="font-size: 0.78rem;">Pastikan penulisan jelas dan sesuai standar layanan faskes.</div>
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3 bg-light">
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-medigo-primary rounded-pill px-4 fw-semibold text-white">Simpan Fasilitas</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Edit Fasilitas --}}
<div class="modal fade" id="modalEditFasilitas" tabindex="-1" aria-labelledby="modalEditFasilitasLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <form id="formEditFasilitas" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header border-bottom px-4 py-3 bg-light">
                    <h5 class="modal-title fw-bold text-dark fs-6" id="modalEditFasilitasLabel">
                        <i class="bi bi-pencil-square me-1 text-medigo-primary"></i> Ubah Fasilitas Penunjang
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="edit_nama_fasilitas" class="form-label fw-semibold text-dark" style="font-size: 0.85rem;">
                            Nama Fasilitas Penunjang <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="edit_nama_fasilitas" name="nama_fasilitas" required maxlength="150">
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
