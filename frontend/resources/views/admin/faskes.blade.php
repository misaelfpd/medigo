@extends('layouts.admin')

@section('title', 'Direktori Fasilitas Kesehatan - Admin MediGO')
@section('page_title', 'Manajemen Fasilitas Kesehatan')

@section('content')
<div class="container-fluid">
    {{-- Judul halaman (sesuai desain: Direktori Rumah Sakit) --}}
    <div class="mg-admin-pagehead">
        <div>
            <h1 class="mg-admin-title">Direktori Rumah Sakit</h1>
            <p class="mg-admin-subtitle">Kelola daftar rumah sakit, status, dan detail.</p>
        </div>
        <a href="{{ route('admin.faskes.create') }}" class="btn mg-btn-primary mg-btn-rect d-inline-flex align-items-center gap-2 px-3 py-2">
            <i class="bi bi-plus-lg"></i>
            <span>Tambah Rumah Sakit Baru</span>
        </a>
    </div>

    @php
        $chipFilters = [
            'all' => 'Semua (' . number_format((int) ($counts['all'] ?? $totalCount), 0, ',', '.') . ')',
            'kelas_a' => 'Kelas A',
            'kelas_b' => 'Kelas B',
            'bpjs' => 'Menerima BPJS',
            'non_bpjs' => 'Non BPJS',
        ];
        $hasExtraFilter = !empty($search) || !empty($provinsi) || !empty($kota) || !empty($tipe) || !empty($kelas) || ($bpjs !== '' && $bpjs !== null);
    @endphp

    {{-- Main Card --}}
    <div class="mg-admin-card mb-4">
        {{-- Chip filter + tombol Filter --}}
        <div class="mg-admin-toolbar">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                @foreach($chipFilters as $key => $label)
                    <a href="{{ route('admin.faskes', array_filter(['filter' => $key === 'all' ? null : $key, 'search' => $search ?: null, 'tipe' => $tipe ?: null, 'kota' => $kota ?: null, 'kelas' => $kelas ?: null, 'bpjs' => ($bpjs !== '' ? $bpjs : null), 'provinsi' => $provinsi ?: null])) }}"
                       class="mg-filter-chip {{ $filter === $key ? 'is-active' : '' }}">{{ $label }}</a>
                @endforeach
            </div>
            <button type="button" class="btn btn-sm mg-filter-toggle d-inline-flex align-items-center gap-2 {{ $hasExtraFilter ? 'is-active' : '' }}"
                    data-bs-toggle="collapse" data-bs-target="#adminFaskesFilter" aria-expanded="{{ $hasExtraFilter ? 'true' : 'false' }}" aria-controls="adminFaskesFilter">
                <i class="bi bi-filter"></i> Filter
            </button>
        </div>

        {{-- Filter lanjutan (mengikuti format filter user: pencarian, jenis faskes, kota, kelas, bpjs) --}}
        <div class="collapse {{ $hasExtraFilter ? 'show' : '' }}" id="adminFaskesFilter">
            <form action="{{ route('admin.faskes') }}" method="GET" class="px-3 pb-3">
                <input type="hidden" name="filter" value="{{ $filter }}">
                
                <div class="row g-2 align-items-center">
                    {{-- Pencarian Nama / Alamat --}}
                    <div class="col-12 col-md-4">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" name="search" class="form-control border-start-0" placeholder="Cari nama faskes / alamat..." value="{{ $search }}">
                        </div>
                    </div>

                    {{-- Jenis Faskes --}}
                    <div class="col-6 col-md-2">
                        <select name="tipe" class="form-select form-select-sm">
                            <option value="">Semua Jenis</option>
                            @foreach($typesList as $t)
                                <option value="{{ $t }}" {{ ($tipe ?? '') === $t ? 'selected' : '' }}>{{ $t }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Kota / Kabupaten (Dinamis dari database, bukan hardcode) --}}
                    <div class="col-6 col-md-2">
                        <select name="kota" class="form-select form-select-sm">
                            <option value="">Semua Kota/Kab</option>
                            @foreach($citiesList as $c)
                                <option value="{{ $c }}" {{ ($kota ?? '') === $c ? 'selected' : '' }}>{{ $c }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Kelas RS --}}
                    <div class="col-6 col-md-2">
                        <select name="kelas" class="form-select form-select-sm">
                            <option value="">Semua Kelas</option>
                            <option value="A" {{ ($kelas ?? '') === 'A' ? 'selected' : '' }}>Kelas A</option>
                            <option value="B" {{ ($kelas ?? '') === 'B' ? 'selected' : '' }}>Kelas B</option>
                            <option value="C" {{ ($kelas ?? '') === 'C' ? 'selected' : '' }}>Kelas C</option>
                            <option value="D" {{ ($kelas ?? '') === 'D' ? 'selected' : '' }}>Kelas D</option>
                        </select>
                    </div>

                    {{-- Jaminan BPJS --}}
                    <div class="col-6 col-md-2">
                        <select name="bpjs" class="form-select form-select-sm">
                            <option value="">Semua Jaminan</option>
                            <option value="1" {{ ($bpjs ?? '') === '1' ? 'selected' : '' }}>BPJS</option>
                            <option value="0" {{ ($bpjs ?? '') === '0' ? 'selected' : '' }}>Non BPJS</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex justify-content-end align-items-center gap-2 mt-2">
                    @if($hasExtraFilter || $filter !== 'all')
                        <a href="{{ route('admin.faskes') }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1" title="Reset Filter">
                            <i class="bi bi-arrow-counterclockwise"></i> Reset
                        </a>
                    @endif
                    <button type="submit" class="btn btn-sm mg-btn-primary mg-btn-rect px-3 d-inline-flex align-items-center gap-1">
                        <i class="bi bi-funnel"></i> Terapkan
                    </button>
                </div>
            </form>
        </div>

        {{-- Tabel --}}
        <div class="table-responsive">
            <table class="table align-middle mb-0 mg-admin-table">
                <thead>
                    <tr>
                        <th scope="col" class="ps-3">Nama Fasilitas Kesehatan</th>
                        <th scope="col">Lokasi</th>
                        <th scope="col">Tipe</th>
                        <th scope="col">BPJS/Non BPJS</th>
                        <th scope="col">Rating</th>
                        <th scope="col">Terakhir<br>Diperbarui</th>
                        <th scope="col" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($faskesList as $item)
                    <tr>
                        <td class="ps-3">
                            <div class="d-flex align-items-center gap-3">
                                @php
                                    $visualKey = \App\Support\FaskesVisual::key($item['jenis_faskes'] ?? null, $item['nama_faskes'] ?? null);
                                    $visualIcon = \App\Support\FaskesVisual::icon($item['jenis_faskes'] ?? null, $item['nama_faskes'] ?? null);
                                @endphp
                                <span class="mg-admin-rowicon faskes-visual-{{ $visualKey }} text-white border-0 shadow-2xs" title="{{ $item['jenis_faskes'] ?: 'Fasilitas Kesehatan' }}">
                                    <i class="bi {{ $visualIcon }}"></i>
                                </span>
                                <div class="min-w-0">
                                    <div class="mg-admin-rowtitle">{{ $item['nama_faskes'] }}</div>
                                    <div class="mg-admin-rowsub">ID: {{ $item['code_id'] }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="text-dark" style="font-size: .88rem;">{{ $item['kota_kabupaten'] ?: '-' }}</div>
                            <div class="text-secondary text-truncate" style="max-width: 200px; font-size: .72rem;" title="{{ $item['alamat_faskes'] }}">{{ $item['alamat_faskes'] ?: '' }}</div>
                        </td>
                        <td>
                            @if(!empty($item['kelas']))
                                <span class="mg-chip mg-chip--kelas">Kelas {{ $item['kelas'] }}</span>
                            @else
                                <span class="mg-chip mg-chip--type">{{ $item['jenis_faskes'] ?: 'Rumah Sakit' }}</span>
                            @endif
                        </td>
                        <td>
                            @if($item['is_support_bpjs'] === 1)
                                <span class="mg-chip mg-chip--bpjs">BPJS</span>
                            @elseif($item['is_support_bpjs'] === 0)
                                <span class="mg-chip mg-chip--nonbpjs">Non BPJS</span>
                            @else
                                <span class="mg-chip mg-chip--nonbpjs">Tidak diketahui</span>
                            @endif
                        </td>
                        <td>
                            @if(!empty($item['rating']))
                                <span class="d-inline-flex align-items-center gap-1" style="font-size: .9rem;">
                                    <i class="bi bi-star-fill text-warning"></i> {{ $item['rating'] }}
                                </span>
                            @else
                                <span class="text-muted" style="font-size: .8rem;">Belum ada</span>
                            @endif
                        </td>
                        <td class="text-secondary" style="font-size: .86rem;">{{ $item['updated_at_formatted'] ?? '-' }}</td>
                        <td class="text-center">
                            <div class="d-inline-flex gap-1">
                                <a href="{{ route('admin.faskes.edit', $item['id_faskes']) }}" class="mg-icon-btn" title="Ubah Data Faskes" aria-label="Ubah {{ $item['nama_faskes'] }}">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="{{ route('admin.faskes.photos', $item['id_faskes']) }}" class="mg-icon-btn" title="Kelola Foto" aria-label="Kelola foto {{ $item['nama_faskes'] }}">
                                    <i class="bi bi-images"></i>
                                </a>
                                <button type="button" class="mg-icon-btn mg-icon-btn--danger" data-bs-toggle="modal" data-bs-target="#modalDelete{{ $item['id_faskes'] }}" title="Hapus Faskes" aria-label="Hapus {{ $item['nama_faskes'] }}">
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
            <div class="mg-admin-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
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
@endsection

