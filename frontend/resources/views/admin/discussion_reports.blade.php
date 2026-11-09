@extends('layouts.admin')

@section('title', 'Laporan Diskusi - Admin MediGO')
@section('page_title', 'Laporan Diskusi')

@section('content')
<div class="container-fluid">
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white border-bottom p-4 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold text-dark">Daftar Laporan Diskusi</h5>
    </div>
    
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col" class="px-4 py-3" width="5%">ID</th>
                        <th scope="col" class="py-3" width="20%">Judul Diskusi</th>
                        <th scope="col" class="py-3" width="15%">Pelapor</th>
                        <th scope="col" class="py-3" width="30%">Alasan</th>
                        <th scope="col" class="py-3" width="15%">Status Diskusi</th>
                        <th scope="col" class="py-3 text-center" width="15%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reports as $report)
                    @php
                        $repId = $report['id_laporan_diskusi'] ?? ($report['id'] ?? null);
                        $discId = $report['id_diskusi'] ?? ($report['discussion_id'] ?? null);
                        $reason = $report['alasan_laporan'] ?? ($report['reason'] ?? '');
                        $status = $report['discussion_status'] ?? ($report['status_diskusi'] ?? 'active');
                        $isTakedown = in_array($status, ['takedown', 'deleted']);
                    @endphp
                    <tr>
                        <td class="px-4 text-muted">#{{ $repId }}</td>
                        <td class="fw-medium text-dark">{{ $report['discussion_title'] ?? 'Diskusi' }}</td>
                        <td>{{ $report['reporter_name'] ?? 'Pelapor' }}</td>
                        <td class="text-secondary small">{{ $reason }}</td>
                        <td>
                            @if($isTakedown)
                                <span class="badge bg-danger">Takedown</span>
                            @else
                                <span class="badge bg-success">Aktif</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if(!$isTakedown)
                                <form action="{{ route('admin.discussions.takedown', $discId) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin men-takedown diskusi ini?');">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i> Takedown
                                    </button>
                                </form>
                            @else
                                <button class="btn btn-sm btn-secondary" disabled>Ter-takedown</button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="bi bi-flag-fill text-secondary fs-1 mb-3 d-block"></i>
                            <p class="mb-0">Tidak ada laporan diskusi.</p>
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
