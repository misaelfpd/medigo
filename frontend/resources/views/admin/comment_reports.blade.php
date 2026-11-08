@extends('layouts.admin')

@section('title', 'Laporan Komentar - Admin MediGO')
@section('page_title', 'Laporan Komentar')

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
                <h5 class="mb-1 fw-bold text-dark">Daftar Laporan Komentar & Ulasan Faskes</h5>
                <p class="text-muted small mb-0">Tinjau laporan dari pengguna terhadap ulasan fasilitas kesehatan yang tidak sopan, mengandung unsur SARA, atau spam.</p>
            </div>
            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3 py-2 fw-semibold">
                {{ count($reports) }} Total Laporan
            </span>
        </div>
        
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="px-4 py-3" width="5%">ID</th>
                            <th scope="col" class="py-3" width="20%">Fasilitas Kesehatan</th>
                            <th scope="col" class="py-3" width="25%">Komentar yang Dilaporkan</th>
                            <th scope="col" class="py-3" width="18%">Alasan Laporan</th>
                            <th scope="col" class="py-3" width="12%">Pelapor</th>
                            <th scope="col" class="py-3" width="8%">Status</th>
                            <th scope="col" class="py-3 text-center" width="12%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reports as $rep)
                        <tr>
                            <td class="px-4 text-muted fw-semibold">#{{ $rep->id }}</td>
                            <td>
                                <div class="fw-bold text-dark">{{ $rep->nama_faskes }}</div>
                                <div class="text-muted small">{{ $rep->kota_kabupaten }}</div>
                            </td>
                            <td>
                                @if(!empty($rep->comment_body))
                                    <div class="d-flex align-items-center gap-1 text-warning mb-1" style="font-size: 0.72rem;">
                                        @for($s = 1; $s <= 5; $s++)
                                            <i class="bi bi-star{{ $s <= (int)$rep->comment_rating ? '-fill' : '' }}"></i>
                                        @endfor
                                        <span class="text-muted ms-1 small">Oleh: {{ $rep->author_name ?? 'Pengguna' }}</span>
                                    </div>
                                    <div class="p-2 bg-light rounded-3 border small text-dark text-break fst-italic">
                                        "{{ $rep->comment_body }}"
                                    </div>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary">Komentar telah dihapus</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $reasonBadges = [
                                        'kata_kasar' => ['label' => 'Kata Kasar / Tidak Sopan', 'badge' => 'bg-danger-subtle text-danger'],
                                        'sara' => ['label' => 'Mengandung SARA / Ujaran Kebencian', 'badge' => 'bg-danger text-white'],
                                        'spam' => ['label' => 'Spam / Iklan Tidak Relevan', 'badge' => 'bg-warning-subtle text-dark'],
                                        'hoax' => ['label' => 'Informasi Palsu / Fitnah', 'badge' => 'bg-info-subtle text-dark'],
                                        'lainnya' => ['label' => 'Lainnya', 'badge' => 'bg-light text-dark border'],
                                    ];
                                    $rInfo = $reasonBadges[$rep->reason] ?? ['label' => ucwords(str_replace('_', ' ', $rep->reason)), 'badge' => 'bg-light text-dark border'];
                                @endphp
                                <span class="badge {{ $rInfo['badge'] }} rounded-pill px-2 py-1 mb-1 d-inline-block" style="font-size: 0.72rem;">
                                    {{ $rInfo['label'] }}
                                </span>
                                @if(!empty($rep->notes))
                                    <div class="text-muted small text-break">{{ $rep->notes }}</div>
                                @endif
                            </td>
                            <td>
                                <div class="small fw-semibold text-dark">{{ $rep->reporter_name }}</div>
                                <div class="text-muted" style="font-size: 0.7rem;">{{ $rep->reporter_email }}</div>
                                <div class="text-muted" style="font-size: 0.68rem;">{{ \Carbon\Carbon::parse($rep->created_at)->diffForHumans() }}</div>
                            </td>
                            <td>
                                @if($rep->status === 'resolved')
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1">Takedown</span>
                                @elseif($rep->status === 'dismissed')
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-2 py-1">Diabaikan</span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2 py-1">Perlu Tindakan</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($rep->status === 'pending')
                                    <div class="d-flex gap-1 justify-content-center">
                                        <form action="{{ route('admin.comment-reports.takedown', $rep->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus ulasan ini secara permanen?');">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-2-5 py-1 d-inline-flex align-items-center gap-1" style="font-size: 0.75rem;" title="Hapus Komentar">
                                                <i class="bi bi-trash"></i> Takedown
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.comment-reports.dismiss', $rep->id) }}" method="POST" onsubmit="return confirm('Tandai laporan ini selesai tanpa menghapus ulasan?');">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-light border rounded-pill px-2 py-1 text-muted" style="font-size: 0.75rem;" title="Abaikan Laporan">
                                                <i class="bi bi-x-circle"></i>
                                            </button>
                                        </form>
                                    </div>
                                @else
                                    <span class="text-muted small">Selesai</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-shield-check text-success fs-1 mb-2 d-block opacity-75"></i>
                                <h6 class="fw-semibold text-dark mb-1">Tidak Ada Laporan Komentar</h6>
                                <p class="small text-muted mb-0">Semua komentar faskes bersih atau belum ada laporan yang masuk.</p>
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
