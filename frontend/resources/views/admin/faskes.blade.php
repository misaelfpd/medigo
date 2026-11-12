@extends('layouts.admin')

@section('title', 'Direktori Fasilitas Kesehatan - Admin MediGO')
@section('page_title', 'Manajemen Fasilitas Kesehatan')

@section('content')
<div class="container-fluid">
    {{-- Alerts --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 mb-3 shadow-xs" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-3 shadow-xs" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Main Card --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        {{-- Card Header --}}
        <div class="card-header bg-white border-bottom p-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h5 class="mb-1 fw-bold text-dark">Direktori Fasilitas Kesehatan</h5>
                <p class="text-muted small mb-0">Kelola data fasilitas kesehatan, lengkapi daftar fasilitas penunjang medis dan spesialisasi dokter.</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-medigo-primary rounded-pill px-3 py-2 fw-semibold d-inline-flex align-items-center gap-2 shadow-xs" data-bs-toggle="modal" data-bs-target="#modalCreateFaskes">
                    <i class="bi bi-plus-circle-fill fs-6"></i>
                    <span>Tambah Faskes</span>
                </button>
            </div>
        </div>

        {{-- Filter & Search Toolbar --}}
        <div class="p-3 bg-light border-bottom">
            <form action="{{ route('admin.faskes') }}" method="GET" class="row g-2 align-items-center">
                {{-- Search Box --}}
                <div class="col-12 col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="Cari nama faskes / alamat..." value="{{ $search }}">
                    </div>
                </div>

                {{-- Filter Tipe / Kategori --}}
                <div class="col-6 col-md-2">
                    <select name="filter" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="all" {{ $filter === 'all' ? 'selected' : '' }}>Semua Tipe</option>
                        <option value="kelas_a" {{ $filter === 'kelas_a' ? 'selected' : '' }}>Rumah Sakit</option>
                        <option value="bpjs" {{ $filter === 'bpjs' ? 'selected' : '' }}>Mendukung BPJS</option>
                        <option value="non_bpjs" {{ $filter === 'non_bpjs' ? 'selected' : '' }}>Non-BPJS</option>
                    </select>
                </div>

                {{-- Filter Provinsi --}}
                <div class="col-6 col-md-3">
                    <select name="provinsi" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Semua Provinsi</option>
                        @foreach($provincesList as $prov)
                            <option value="{{ $prov }}" {{ $provinsi === $prov ? 'selected' : '' }}>{{ $prov }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Filter Kota --}}
                <div class="col-8 col-md-2">
                    <input type="text" name="kota" class="form-control form-control-sm" placeholder="Kota / Kabupaten" value="{{ $kota }}">
                </div>

                {{-- Button Filter --}}
                <div class="col-4 col-md-1 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-dark w-100 rounded-3">Cari</button>
                    @if(!empty($search) || !empty($provinsi) || !empty($kota) || $filter !== 'all')
                        <a href="{{ route('admin.faskes') }}" class="btn btn-sm btn-outline-secondary rounded-3" title="Reset Filter"><i class="bi bi-arrow-counterclockwise"></i></a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Table Content --}}
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="px-4 py-3" width="7%">Kode</th>
                            <th scope="col" class="py-3" width="23%">Nama Fasilitas</th>
                            <th scope="col" class="py-3" width="12%">Tipe & Kelas</th>
                            <th scope="col" class="py-3" width="10%">BPJS</th>
                            <th scope="col" class="py-3" width="22%">Lokasi & Alamat</th>
                            <th scope="col" class="py-3" width="14%">Kontak</th>
                            <th scope="col" class="py-3 text-center" width="12%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($faskesList as $item)
                        <tr>
                            <td class="px-4 text-muted fw-semibold" style="font-size: 0.8rem;">
                                <span class="badge bg-light text-dark border">{{ $item['code_id'] }}</span>
                            </td>
                            <td>
                                <div class="fw-bold text-dark mb-0" style="font-size: 0.9rem;">
                                    {{ $item['nama_faskes'] }}
                                </div>
                                <div class="d-flex align-items-center gap-2 mt-1">
                                    @if(!empty($item['rating']))
                                        <span class="text-warning small d-inline-flex align-items-center gap-1">
                                            <i class="bi bi-star-fill" style="font-size: 0.75rem;"></i>
                                            <span class="text-dark fw-semibold" style="font-size: 0.78rem;">{{ $item['rating'] }}</span>
                                        </span>
                                    @endif
                                    @if(!empty($item['link_maps']))
                                        <a href="{{ $item['link_maps'] }}" target="_blank" class="badge bg-light text-primary border text-decoration-none" style="font-size: 0.7rem;">
                                            <i class="bi bi-geo-alt-fill me-1"></i>Maps
                                        </a>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2 py-1 mb-1" style="font-size: 0.72rem;">
                                    {{ $item['jenis_faskes'] ?: 'Rumah Sakit' }}
                                </span>
                                @if(!empty($item['kelas']))
                                    <div class="text-muted small" style="font-size: 0.74rem;">Kelas {{ $item['kelas'] }}</div>
                                @endif
                            </td>
                            <td>
                                @if($item['is_support_bpjs'] === 1)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1" style="font-size: 0.72rem;">
                                        <i class="bi bi-check-circle-fill me-1"></i>BPJS
                                    </span>
                                @elseif($item['is_support_bpjs'] === 0)
                                    <span class="badge bg-secondary-subtle text-secondary border rounded-pill px-2 py-1" style="font-size: 0.72rem;">
                                        Non-BPJS
                                    </span>
                                @else
                                    <span class="badge bg-light text-muted border rounded-pill px-2 py-1" style="font-size: 0.72rem;">-</span>
                                @endif
                            </td>
                            <td>
                                <div class="fw-semibold text-dark small">{{ $item['kota_kabupaten'] ?: '-' }}</div>
                                <div class="text-secondary small text-truncate" style="max-width: 260px; font-size: 0.75rem;" title="{{ $item['alamat_faskes'] }}">
                                    {{ $item['alamat_faskes'] ?: '-' }}
                                </div>
                            </td>
                            <td>
                                <span class="text-dark small"><i class="bi bi-telephone text-muted me-1"></i>{{ $item['kontak'] ?: '-' }}</span>
                            </td>
                            <td class="text-center">
                                <div class="d-inline-flex gap-1">
                                    {{-- Edit Button --}}
                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-2 py-1 btn-edit-faskes" data-id="{{ $item['id_faskes'] }}" title="Ubah Data Faskes">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    {{-- Delete Button --}}
                                    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-2 py-1" data-bs-toggle="modal" data-bs-target="#modalDelete{{ $item['id_faskes'] }}" title="Hapus Faskes">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>

                                {{-- Modal Delete Confirmation --}}
                                <div class="modal fade" id="modalDelete{{ $item['id_faskes'] }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered" style="max-width: 380px;">
                                        <div class="modal-content rounded-4 border-0 shadow">
                                            <div class="modal-body text-center p-4">
                                                <div class="mb-3 text-danger">
                                                    <i class="bi bi-exclamation-triangle-fill" style="font-size: 3rem;"></i>
                                                </div>
                                                <h6 class="fw-bold text-dark mb-1">Hapus Faskes?</h6>
                                                <p class="text-muted small mb-4">Apakah Anda yakin ingin menghapus <strong>{{ $item['nama_faskes'] }}</strong>? Tindakan ini tidak dapat dibatalkan.</p>
                                                <form action="{{ route('admin.faskes.destroy', $item['id_faskes']) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <div class="d-flex gap-2">
                                                        <button type="button" class="btn btn-light rounded-pill px-3 flex-grow-1" data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit" class="btn btn-danger rounded-pill px-3 flex-grow-1 fw-semibold">Ya, Hapus</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-hospital fs-2 d-block mb-2 text-muted opacity-50"></i>
                                <span>Tidak ada data fasilitas kesehatan yang sesuai kriteria pencarian.</span>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination footer --}}
            <div class="card-footer bg-white border-top p-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <small class="text-muted">
                    Menampilkan <strong>{{ $paginationData['from'] }}</strong> - <strong>{{ $paginationData['to'] }}</strong> dari <strong>{{ $paginationData['total'] }}</strong> data
                </small>
                <div>
                    @if($paginationData['last_page'] > 1)
                        <nav aria-label="Page navigation">
                            <ul class="pagination pagination-sm m-0">
                                <li class="page-item {{ $paginationData['current_page'] <= 1 ? 'disabled' : '' }}">
                                    <a class="page-link" href="{{ request()->fullUrlWithQuery(['page' => $paginationData['current_page'] - 1]) }}">Sebelumnya</a>
                                </li>
                                <li class="page-item active">
                                    <span class="page-link">{{ $paginationData['current_page'] }} / {{ $paginationData['last_page'] }}</span>
                                </li>
                                <li class="page-item {{ $paginationData['current_page'] >= $paginationData['last_page'] ? 'disabled' : '' }}">
                                    <a class="page-link" href="{{ request()->fullUrlWithQuery(['page' => $paginationData['current_page'] + 1]) }}">Selanjutnya</a>
                                </li>
                            </ul>
                        </nav>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- =========================================================================
     MODAL TAMBAH FASKES (CREATE)
     ========================================================================= --}}
<div class="modal fade" id="modalCreateFaskes" tabindex="-1" aria-labelledby="modalCreateFaskesLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-bottom px-4 py-3 bg-light">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-plus-circle-fill text-medigo-primary fs-5"></i>
                    <h6 class="modal-title fw-bold text-dark m-0" id="modalCreateFaskesLabel">Tambah Fasilitas Kesehatan Baru</h6>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.faskes.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    {{-- Row 1: Nama & Tipe --}}
                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold text-dark small">Nama Faskes <span class="text-danger">*</span></label>
                            <input type="text" name="nama_faskes" class="form-control rounded-3" placeholder="Contoh: RS Umum Daerah Pasar Minggu" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold text-dark small">Jenis / Tipe <span class="text-danger">*</span></label>
                            <select name="tipe" class="form-select rounded-3" required>
                                <option value="Rumah Sakit" selected>Rumah Sakit</option>
                                <option value="Klinik">Klinik</option>
                                <option value="Puskesmas">Puskesmas</option>
                                <option value="Apotek">Apotek</option>
                                <option value="Laboratorium">Laboratorium</option>
                            </select>
                        </div>
                    </div>

                    {{-- Row 2: Kelas RS, BPJS, Kontak --}}
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold text-dark small">Kelas Faskes</label>
                            <select name="kelas" class="form-select rounded-3">
                                <option value="">Tidak Diketahui / Lainnya</option>
                                <option value="A">Kelas A</option>
                                <option value="B">Kelas B</option>
                                <option value="C">Kelas C</option>
                                <option value="D">Kelas D</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold text-dark small">Mendukung BPJS?</label>
                            <select name="is_support_bpjs" class="form-select rounded-3">
                                <option value="1" selected>Ya (Mendukung BPJS)</option>
                                <option value="0">Tidak (Non-BPJS)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold text-dark small">No. Telepon / Kontak</label>
                            <input type="text" name="kontak" class="form-control rounded-3" placeholder="(021) 1234567">
                        </div>
                    </div>

                    {{-- Row 3: Kota/Kabupaten & Provinsi --}}
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-dark small">Kota / Kabupaten <span class="text-danger">*</span></label>
                            <input type="text" name="kota_kabupaten" class="form-control rounded-3" placeholder="Contoh: Jakarta Selatan" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-dark small">Provinsi <span class="text-danger">*</span></label>
                            <select name="provinsi" class="form-select rounded-3" required>
                                @foreach($provincesList as $prov)
                                    <option value="{{ $prov }}" {{ $prov === 'DKI Jakarta' ? 'selected' : '' }}>{{ $prov }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Row 4: Alamat Lengkap --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark small">Alamat Lengkap <span class="text-danger">*</span></label>
                        <textarea name="alamat" class="form-control rounded-3" rows="2" placeholder="Jl. TB Simatupang No. 1, RT 01 / RW 05..." required></textarea>
                    </div>

                    {{-- Row 5: Koordinat & Link Maps --}}
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold text-dark small">Latitude</label>
                            <input type="number" step="any" name="latitude" class="form-control rounded-3" placeholder="-6.2912">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold text-dark small">Longitude</label>
                            <input type="number" step="any" name="longitude" class="form-control rounded-3" placeholder="106.8285">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold text-dark small">Link Google Maps</label>
                            <input type="url" name="link_maps" class="form-control rounded-3" placeholder="https://maps.google.com/...">
                        </div>
                    </div>

                    <hr class="my-4">

                    {{-- Row 6: DAFTAR FASILITAS YANG DIMILIKI --}}
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <label class="form-label fw-bold text-dark mb-0">
                                    <i class="bi bi-hospital text-medigo-primary me-1"></i> Fasilitas Medis Penunjang
                                </label>
                                <div class="text-muted" style="font-size: 0.75rem;">Pilih fasilitas yang disediakan oleh faskes ini.</div>
                            </div>
                            <span class="badge bg-light text-dark border" id="createFasilitasCountBadge">0 dipilih</span>
                        </div>
                        {{-- Search Input for Facilities --}}
                        <div class="mb-2">
                            <input type="text" class="form-control form-control-sm rounded-3 filter-item-search" data-target="#createFasilitasList" placeholder="Cari fasilitas medis (misal: IGD, Laboratorium, Radiologi)...">
                        </div>
                        {{-- Scrollable Checkbox Box --}}
                        <div class="border rounded-3 p-3 bg-light overflow-auto" id="createFasilitasList" style="max-height: 180px;">
                            <div class="row g-2">
                                @foreach($allFasilitas as $fas)
                                    <div class="col-md-6 filter-item-box" data-text="{{ strtolower($fas['nama_fasilitas']) }}">
                                        <div class="form-check form-check-sm">
                                            <input class="form-check-input check-fasilitas-item" type="checkbox" name="fasilitas[]" value="{{ $fas['id_fasilitas'] }}" id="create_fas_{{ $fas['id_fasilitas'] }}">
                                            <label class="form-check-label small text-dark" for="create_fas_{{ $fas['id_fasilitas'] }}">
                                                {{ $fas['nama_fasilitas'] }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Row 7: DAFTAR SPESIALISASI YANG DIMILIKI --}}
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <label class="form-label fw-bold text-dark mb-0">
                                    <i class="bi bi-person-badge text-medigo-primary me-1"></i> Spesialisasi Dokter
                                </label>
                                <div class="text-muted" style="font-size: 0.75rem;">Pilih spesialisasi dokter yang tersedia pada faskes ini.</div>
                            </div>
                            <span class="badge bg-light text-dark border" id="createSpesialisasiCountBadge">0 dipilih</span>
                        </div>
                        {{-- Search Input for Specialties --}}
                        <div class="mb-2">
                            <input type="text" class="form-control form-control-sm rounded-3 filter-item-search" data-target="#createSpesialisasiList" placeholder="Cari spesialisasi dokter (misal: Anak, Jantung, Bedah, Gigi)...">
                        </div>
                        {{-- Scrollable Checkbox Box --}}
                        <div class="border rounded-3 p-3 bg-light overflow-auto" id="createSpesialisasiList" style="max-height: 180px;">
                            <div class="row g-2">
                                @foreach($allSpesialisasi as $spec)
                                    <div class="col-md-6 filter-item-box" data-text="{{ strtolower($spec['nama_spesialisasi']) }}">
                                        <div class="form-check form-check-sm">
                                            <input class="form-check-input check-spesialisasi-item" type="checkbox" name="spesialisasi[]" value="{{ $spec['id_spesialisasi'] }}" id="create_spec_{{ $spec['id_spesialisasi'] }}">
                                            <label class="form-check-label small text-dark" for="create_spec_{{ $spec['id_spesialisasi'] }}">
                                                {{ $spec['nama_spesialisasi'] }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-top px-4 py-3 bg-light d-flex justify-content-between">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-medigo-primary rounded-pill px-4 fw-semibold shadow-xs">Simpan Faskes</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- =========================================================================
     MODAL EDIT FASKES (EDIT)
     ========================================================================= --}}
<div class="modal fade" id="modalEditFaskes" tabindex="-1" aria-labelledby="modalEditFaskesLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-bottom px-4 py-3 bg-light">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-pencil-square text-medigo-primary fs-5"></i>
                    <h6 class="modal-title fw-bold text-dark m-0" id="modalEditFaskesLabel">Ubah Data Fasilitas Kesehatan</h6>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            {{-- Loading spinner state inside modal --}}
            <div id="editModalLoading" class="text-center py-5">
                <div class="spinner-border text-medigo-primary mb-2" role="status"></div>
                <div class="text-muted small">Memuat data fasilitas kesehatan...</div>
            </div>

            <form id="editFaskesForm" method="POST" action="" class="d-none">
                @csrf
                @method('PUT')
                <div class="modal-body p-4">
                    {{-- Row 1: Nama & Tipe --}}
                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold text-dark small">Nama Faskes <span class="text-danger">*</span></label>
                            <input type="text" name="nama_faskes" id="edit_nama_faskes" class="form-control rounded-3" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold text-dark small">Jenis / Tipe <span class="text-danger">*</span></label>
                            <select name="tipe" id="edit_tipe" class="form-select rounded-3" required>
                                <option value="Rumah Sakit">Rumah Sakit</option>
                                <option value="Klinik">Klinik</option>
                                <option value="Puskesmas">Puskesmas</option>
                                <option value="Apotek">Apotek</option>
                                <option value="Laboratorium">Laboratorium</option>
                            </select>
                        </div>
                    </div>

                    {{-- Row 2: Kelas RS, BPJS, Kontak --}}
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold text-dark small">Kelas Faskes</label>
                            <select name="kelas" id="edit_kelas" class="form-select rounded-3">
                                <option value="">Tidak Diketahui / Lainnya</option>
                                <option value="A">Kelas A</option>
                                <option value="B">Kelas B</option>
                                <option value="C">Kelas C</option>
                                <option value="D">Kelas D</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold text-dark small">Mendukung BPJS?</label>
                            <select name="is_support_bpjs" id="edit_bpjs" class="form-select rounded-3">
                                <option value="1">Ya (Mendukung BPJS)</option>
                                <option value="0">Tidak (Non-BPJS)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold text-dark small">No. Telepon / Kontak</label>
                            <input type="text" name="kontak" id="edit_kontak" class="form-control rounded-3">
                        </div>
                    </div>

                    {{-- Row 3: Kota/Kabupaten & Provinsi --}}
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-dark small">Kota / Kabupaten <span class="text-danger">*</span></label>
                            <input type="text" name="kota_kabupaten" id="edit_kota" class="form-control rounded-3" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-dark small">Provinsi <span class="text-danger">*</span></label>
                            <select name="provinsi" id="edit_provinsi" class="form-select rounded-3" required>
                                @foreach($provincesList as $prov)
                                    <option value="{{ $prov }}">{{ $prov }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Row 4: Alamat Lengkap --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark small">Alamat Lengkap <span class="text-danger">*</span></label>
                        <textarea name="alamat" id="edit_alamat" class="form-control rounded-3" rows="2" required></textarea>
                    </div>

                    {{-- Row 5: Koordinat & Link Maps --}}
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold text-dark small">Latitude</label>
                            <input type="number" step="any" name="latitude" id="edit_latitude" class="form-control rounded-3">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold text-dark small">Longitude</label>
                            <input type="number" step="any" name="longitude" id="edit_longitude" class="form-control rounded-3">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold text-dark small">Link Google Maps</label>
                            <input type="url" name="link_maps" id="edit_link_maps" class="form-control rounded-3">
                        </div>
                    </div>

                    <hr class="my-4">

                    {{-- Row 6: DAFTAR FASILITAS YANG DIMILIKI --}}
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <label class="form-label fw-bold text-dark mb-0">
                                    <i class="bi bi-hospital text-medigo-primary me-1"></i> Fasilitas Medis Penunjang
                                </label>
                                <div class="text-muted" style="font-size: 0.75rem;">Centang atau hapus centang fasilitas untuk memperbarui data faskes ini.</div>
                            </div>
                            <span class="badge bg-light text-dark border" id="editFasilitasCountBadge">0 dipilih</span>
                        </div>
                        {{-- Search Input for Facilities --}}
                        <div class="mb-2">
                            <input type="text" class="form-control form-control-sm rounded-3 filter-item-search" data-target="#editFasilitasList" placeholder="Cari fasilitas medis...">
                        </div>
                        {{-- Scrollable Checkbox Box --}}
                        <div class="border rounded-3 p-3 bg-light overflow-auto" id="editFasilitasList" style="max-height: 180px;">
                            <div class="row g-2">
                                @foreach($allFasilitas as $fas)
                                    <div class="col-md-6 filter-item-box" data-text="{{ strtolower($fas['nama_fasilitas']) }}">
                                        <div class="form-check form-check-sm">
                                            <input class="form-check-input edit-check-fasilitas-item" type="checkbox" name="fasilitas[]" value="{{ $fas['id_fasilitas'] }}" id="edit_fas_{{ $fas['id_fasilitas'] }}">
                                            <label class="form-check-label small text-dark" for="edit_fas_{{ $fas['id_fasilitas'] }}">
                                                {{ $fas['nama_fasilitas'] }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Row 7: DAFTAR SPESIALISASI YANG DIMILIKI --}}
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <label class="form-label fw-bold text-dark mb-0">
                                    <i class="bi bi-person-badge text-medigo-primary me-1"></i> Spesialisasi Dokter
                                </label>
                                <div class="text-muted" style="font-size: 0.75rem;">Centang atau hapus centang spesialisasi dokter untuk memperbarui data faskes ini.</div>
                            </div>
                            <span class="badge bg-light text-dark border" id="editSpesialisasiCountBadge">0 dipilih</span>
                        </div>
                        {{-- Search Input for Specialties --}}
                        <div class="mb-2">
                            <input type="text" class="form-control form-control-sm rounded-3 filter-item-search" data-target="#editSpesialisasiList" placeholder="Cari spesialisasi dokter...">
                        </div>
                        {{-- Scrollable Checkbox Box --}}
                        <div class="border rounded-3 p-3 bg-light overflow-auto" id="editSpesialisasiList" style="max-height: 180px;">
                            <div class="row g-2">
                                @foreach($allSpesialisasi as $spec)
                                    <div class="col-md-6 filter-item-box" data-text="{{ strtolower($spec['nama_spesialisasi']) }}">
                                        <div class="form-check form-check-sm">
                                            <input class="form-check-input edit-check-spesialisasi-item" type="checkbox" name="spesialisasi[]" value="{{ $spec['id_spesialisasi'] }}" id="edit_spec_{{ $spec['id_spesialisasi'] }}">
                                            <label class="form-check-label small text-dark" for="edit_spec_{{ $spec['id_spesialisasi'] }}">
                                                {{ $spec['nama_spesialisasi'] }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-top px-4 py-3 bg-light d-flex justify-content-between">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-medigo-primary rounded-pill px-4 fw-semibold shadow-xs">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Search Filter helper for Facility & Specialty check lists
    document.querySelectorAll('.filter-item-search').forEach(function (input) {
        input.addEventListener('input', function () {
            var targetSelector = this.getAttribute('data-target');
            var container = document.querySelector(targetSelector);
            if (!container) return;

            var q = this.value.trim().toLowerCase();
            container.querySelectorAll('.filter-item-box').forEach(function (box) {
                var text = box.getAttribute('data-text') || '';
                if (q === '' || text.indexOf(q) !== -1) {
                    box.classList.remove('d-none');
                } else {
                    box.classList.add('d-none');
                }
            });
        });
    });

    // 2. Count updater helper
    function updateCount(checkboxClass, badgeId) {
        var count = document.querySelectorAll(checkboxClass + ':checked').length;
        var badge = document.getElementById(badgeId);
        if (badge) {
            badge.textContent = count + ' dipilih';
        }
    }

    // Bind count change for Create Modal
    document.querySelectorAll('.check-fasilitas-item').forEach(function (cb) {
        cb.addEventListener('change', function () {
            updateCount('.check-fasilitas-item', 'createFasilitasCountBadge');
        });
    });
    document.querySelectorAll('.check-spesialisasi-item').forEach(function (cb) {
        cb.addEventListener('change', function () {
            updateCount('.check-spesialisasi-item', 'createSpesialisasiCountBadge');
        });
    });

    // Bind count change for Edit Modal
    document.querySelectorAll('.edit-check-fasilitas-item').forEach(function (cb) {
        cb.addEventListener('change', function () {
            updateCount('.edit-check-fasilitas-item', 'editFasilitasCountBadge');
        });
    });
    document.querySelectorAll('.edit-check-spesialisasi-item').forEach(function (cb) {
        cb.addEventListener('change', function () {
            updateCount('.edit-check-spesialisasi-item', 'editSpesialisasiCountBadge');
        });
    });

    // 3. Edit Faskes Modal population via AJAX
    var editModalEl = document.getElementById('modalEditFaskes');
    var editModal = editModalEl && typeof bootstrap !== 'undefined' ? new bootstrap.Modal(editModalEl) : null;
    var editForm = document.getElementById('editFaskesForm');
    var editLoading = document.getElementById('editModalLoading');

    document.querySelectorAll('.btn-edit-faskes').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = this.getAttribute('data-id');
            if (!id || !editModal) return;

            // Reset UI
            editLoading.classList.remove('d-none');
            editForm.classList.add('d-none');
            editForm.action = '/admin/faskes/' + id;

            // Uncheck all edit checkboxes
            document.querySelectorAll('.edit-check-fasilitas-item').forEach(function (cb) { cb.checked = false; });
            document.querySelectorAll('.edit-check-spesialisasi-item').forEach(function (cb) { cb.checked = false; });

            editModal.show();

            // Fetch details from JSON endpoint
            fetch('/admin/faskes/' + id + '/json')
                .then(function (res) { return res.json(); })
                .then(function (res) {
                    if (res && res.data) {
                        var faskes = res.data.faskes || res.data;
                        document.getElementById('edit_nama_faskes').value = faskes.nama_faskes || '';
                        
                        var tipeSelect = document.getElementById('edit_tipe');
                        if (tipeSelect) {
                            var currentTipe = faskes.jenis_faskes || faskes.tipe || 'Rumah Sakit';
                            tipeSelect.value = currentTipe;
                        }

                        document.getElementById('edit_kelas').value = faskes.kelas || '';
                        
                        var bpjsSelect = document.getElementById('edit_bpjs');
                        var bpjsVal = (faskes._support_bpjs === 1 || faskes.is_support_bpjs === 1 || faskes._support_bpjs === true) ? '1' : '0';
                        if (bpjsSelect) bpjsSelect.value = bpjsVal;

                        document.getElementById('edit_kontak').value = faskes.kontak || faskes.telepon || '';
                        document.getElementById('edit_kota').value = faskes.kota_kabupaten || '';
                        
                        var provSelect = document.getElementById('edit_provinsi');
                        if (provSelect && faskes.provinsi) {
                            provSelect.value = faskes.provinsi;
                        }

                        document.getElementById('edit_alamat').value = faskes.alamat_faskes || faskes.alamat || '';
                        document.getElementById('edit_latitude').value = faskes.latitude || '';
                        document.getElementById('edit_longitude').value = faskes.longitude || '';
                        document.getElementById('edit_link_maps').value = faskes.link_maps || '';

                        // Check possessed facilities
                        var fasilitasArr = res.data.fasilitas || faskes.fasilitas || [];
                        fasilitasArr.forEach(function (item) {
                            var fasId = item.id_fasilitas || item;
                            var cb = document.getElementById('edit_fas_' + fasId);
                            if (cb) cb.checked = true;
                        });
                        updateCount('.edit-check-fasilitas-item', 'editFasilitasCountBadge');

                        // Check possessed specialties
                        var spesialisasiArr = res.data.spesialisasi || faskes.spesialisasi || [];
                        spesialisasiArr.forEach(function (item) {
                            var specId = item.id_spesialisasi || item;
                            var cb = document.getElementById('edit_spec_' + specId);
                            if (cb) cb.checked = true;
                        });
                        updateCount('.edit-check-spesialisasi-item', 'editSpesialisasiCountBadge');
                    }

                    editLoading.classList.add('d-none');
                    editForm.classList.remove('d-none');
                })
                .catch(function (err) {
                    console.error('Error fetching faskes data:', err);
                    editLoading.innerHTML = '<div class="text-danger small py-3"><i class="bi bi-exclamation-triangle-fill me-1"></i> Gagal memuat data faskes. Silakan coba lagi.</div>';
                });
        });
    });
});
</script>
@endpush
