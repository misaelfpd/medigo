@extends('layouts.admin')

@section('title', 'Masukan Data Faskes - Admin MediGO')
@section('page_title', 'Masukan Data Faskes')

@section('content')
<div class="container-fluid">
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

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-bottom p-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="mb-1 fw-bold text-dark">Daftar Masukan & Koreksi Data Faskes</h5>
                <p class="text-muted small mb-0">Laporan dari pengguna terkait kesalahan lokasi peta, status BPJS, kontak, atau data faskes lainnya.</p>
            </div>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-2 fw-semibold">
                {{ count($feedbacks) }} Total Masukan
            </span>
        </div>
        
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="px-4 py-3" width="5%">ID</th>
                            <th scope="col" class="py-3" width="22%">Fasilitas Kesehatan</th>
                            <th scope="col" class="py-3" width="18%">Kategori Masukan</th>
                            <th scope="col" class="py-3" width="25%">Catatan & Usulan Koreksi</th>
                            <th scope="col" class="py-3" width="12%">Pelapor</th>
                            <th scope="col" class="py-3" width="10%">Status</th>
                            <th scope="col" class="py-3 text-center" width="8%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($feedbacks as $fb)
                        <tr>
                            <td class="px-4 text-muted fw-semibold">#{{ $fb->id }}</td>
                            <td>
                                <div class="fw-bold text-dark">{{ $fb->nama_faskes }}</div>
                                <div class="text-muted small">
                                    <span class="badge bg-light text-secondary border me-1">{{ $fb->faskes_tipe ?? 'Faskes' }}</span>
                                    {{ $fb->kota_kabupaten }}
                                </div>
                            </td>
                            <td>
                                @php
                                    $catLabels = [
                                        'lokasi_salah' => ['label' => 'Titik Lokasi / Peta Salah', 'badge' => 'bg-danger-subtle text-danger border-danger-subtle'],
                                        'bpjs_tidak_sesuai' => ['label' => 'Status BPJS Tidak Sesuai', 'badge' => 'bg-warning-subtle text-dark border-warning-subtle'],
                                        'kontak_keliru' => ['label' => 'Nomor Kontak / Telepon Keliru', 'badge' => 'bg-info-subtle text-info-emphasis border-info-subtle'],
                                        'tutup_permanen' => ['label' => 'Faskes Sudah Tutup', 'badge' => 'bg-secondary-subtle text-secondary border-secondary-subtle'],
                                        'lainnya' => ['label' => 'Lainnya', 'badge' => 'bg-light text-dark border'],
                                    ];
                                    $catInfo = $catLabels[$fb->category] ?? ['label' => ucwords(str_replace('_', ' ', $fb->category)), 'badge' => 'bg-light text-dark border'];
                                @endphp
                                <span class="badge {{ $catInfo['badge'] }} border rounded-pill px-2-5 py-1" style="font-size: 0.72rem;">
                                    {{ $catInfo['label'] }}
                                </span>
                            </td>
                            <td>
                                <div class="text-dark small fw-medium text-break" style="max-height: 80px; overflow-y: auto;">
                                    {{ $fb->notes }}
                                </div>
                                @if(!empty($fb->suggested_correction))
                                    <div class="mt-1 small text-primary fst-italic text-break">
                                        <i class="bi bi-arrow-right-short"></i> Usulan: {{ $fb->suggested_correction }}
                                    </div>
                                @endif
                                @if(!empty($fb->admin_notes))
                                    <div class="mt-1 small text-muted">
                                        <i class="bi bi-chat-left-text me-1"></i> Catatan Admin: {{ $fb->admin_notes }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                <div class="small fw-semibold text-dark">{{ $fb->user_name ?: ($fb->registered_user_name ?: 'Anonim') }}</div>
                                @if(!empty($fb->user_email ?: $fb->registered_user_email))
                                    <div class="text-muted" style="font-size: 0.72rem;">{{ $fb->user_email ?: $fb->registered_user_email }}</div>
                                @endif
                                <div class="text-muted" style="font-size: 0.68rem;">{{ \Carbon\Carbon::parse($fb->created_at)->diffForHumans() }}</div>
                            </td>
                            <td>
                                @if($fb->status === 'applied')
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1">Diterapkan</span>
                                @elseif($fb->status === 'reviewed')
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2 py-1">Ditinjau</span>
                                @elseif($fb->status === 'rejected')
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2 py-1">Ditolak</span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-2 py-1">Menunggu</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-2-5 py-1 d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#modalFeedback{{ $fb->id }}" style="font-size: 0.75rem;">
                                    <i class="bi bi-gear"></i> Tindak Lanjut
                                </button>
                            </td>
                        </tr>

                        <!-- Modal Tindak Lanjut Feedback -->
                        <div class="modal fade" id="modalFeedback{{ $fb->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content rounded-4 border-0 shadow">
                                    <form action="{{ route('admin.faskes-feedbacks.status', $fb->id) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-header border-bottom px-4 py-3">
                                            <h6 class="modal-title fw-bold text-dark mb-0">Tindak Lanjut Masukan #{{ $fb->id }}</h6>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body p-4">
                                            <div class="mb-3">
                                                <label class="form-label text-muted small mb-1">Fasilitas Kesehatan</label>
                                                <div class="fw-bold text-dark">{{ $fb->nama_faskes }}</div>
                                                <div class="text-muted small">{{ $fb->faskes_alamat }}</div>
                                            </div>

                                            <div class="mb-3 p-3 bg-light rounded-3 border">
                                                <div class="small fw-bold text-dark mb-1">Masukan Pengguna:</div>
                                                <div class="text-dark small">{{ $fb->notes }}</div>
                                                @if(!empty($fb->suggested_correction))
                                                    <div class="mt-2 pt-2 border-top small text-primary">
                                                        <strong>Saran / Usulan:</strong> {{ $fb->suggested_correction }}
                                                    </div>
                                                @endif
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label fw-semibold text-dark small">Ubah Status Masukan</label>
                                                <select name="status" class="form-select rounded-3">
                                                    <option value="pending" {{ $fb->status === 'pending' ? 'selected' : '' }}>Menunggu Peninjauan</option>
                                                    <option value="reviewed" {{ $fb->status === 'reviewed' ? 'selected' : '' }}>Sedang Ditinjau</option>
                                                    <option value="applied" {{ $fb->status === 'applied' ? 'selected' : '' }}>Selesai / Data Sudah Disesuaikan</option>
                                                    <option value="rejected" {{ $fb->status === 'rejected' ? 'selected' : '' }}>Ditolak / Data Sudah Sesuai</option>
                                                </select>
                                            </div>

                                            <div class="mb-2">
                                                <label class="form-label fw-semibold text-dark small">Catatan Admin (Opsional)</label>
                                                <textarea name="admin_notes" class="form-control rounded-3" rows="2" placeholder="Tuliskan catatan tindak lanjut...">{{ $fb->admin_notes }}</textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer border-top px-4 py-3">
                                            <button type="button" class="btn btn-sm btn-light rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn btn-sm btn-medigo-primary rounded-pill px-4">Simpan Perubahan</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox text-secondary fs-1 mb-2 d-block opacity-50"></i>
                                <h6 class="fw-semibold text-dark mb-1">Belum Ada Masukan Data</h6>
                                <p class="small text-muted mb-0">Belum ada pengguna yang mengirimkan masukan atau koreksi data faskes.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
