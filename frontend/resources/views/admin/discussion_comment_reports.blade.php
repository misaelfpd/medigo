@extends('layouts.admin')

@section('title', 'Laporan Komentar Diskusi - Admin MediGO')
@section('page_title', 'Laporan Komentar Diskusi')

@section('content')
<div class="admin-report-card">
    <div class="admin-report-head">
        <div>
            <h5>Laporan Komentar Diskusi</h5>
            <p>Tinjau balasan/komentar di forum diskusi yang dilaporkan pengguna. Takedown menghapus komentar tersebut.</p>
        </div>
        <span class="admin-status admin-status--pending">{{ count($reports) }} laporan</span>
    </div>

    <div class="admin-report-toolbar">
        @include('admin.partials.report-tabs', ['route' => 'admin.discussion-comment-reports'])
        @include('admin.partials.report-search', ['route' => 'admin.discussion-comment-reports', 'placeholder' => 'Cari isi komentar, alasan, pelapor...'])
    </div>

    <div class="table-responsive">
        <table class="table admin-report-table align-top">
            <thead>
                <tr>
                    <th>Komentar</th>
                    <th>Alasan &amp; Catatan</th>
                    <th>Pelapor</th>
                    <th>Status</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reports as $report)
                    @php
                        $reportId = $report['id_laporan_tanggapan'] ?? null;
                        $discussionId = $report['id_diskusi'] ?? null;
                        $commentExists = !empty($report['comment_body']) || !empty($report['comment_image']);
                        $reportStatus = $report['status'] ?? 'pending';
                    @endphp
                    <tr>
                        <td style="min-width: 260px; max-width: 380px;">
                            <div class="admin-report-meta mb-1">
                                Oleh <span class="fw-semibold text-dark">{{ $report['author_name'] ?? 'Pengguna' }}</span>
                                @if(!empty($report['comment_created_at']))
                                    &bull; {{ \Carbon\Carbon::parse($report['comment_created_at'])->diffForHumans() }}
                                @endif
                            </div>
                            @if($commentExists)
                                <div class="admin-report-quote">{{ $report['comment_body'] ?: '(komentar berisi gambar)' }}</div>
                            @else
                                <span class="admin-status admin-status--dismissed">Komentar sudah dihapus</span>
                            @endif
                            @if($discussionId)
                                <div class="admin-report-meta mt-2">
                                    Di diskusi:
                                    <a href="{{ route('discussions.show', $discussionId) }}#comments" target="_blank" rel="noopener" class="admin-report-title">
                                        {{ \Illuminate\Support\Str::limit($report['discussion_title'] ?? 'Diskusi', 70) }}
                                    </a>
                                </div>
                            @endif
                        </td>
                        <td style="min-width: 200px;">
                            <span class="admin-report-reason">{{ $report['alasan_laporan'] ?? '-' }}</span>
                            @if(!empty($report['catatan_tambahan']))
                                <div class="admin-report-notes">{{ $report['catatan_tambahan'] }}</div>
                            @else
                                <div class="admin-report-meta">Tanpa catatan tambahan</div>
                            @endif
                        </td>
                        <td style="min-width: 160px;">
                            <div class="fw-semibold text-dark">{{ $report['reporter_name'] ?? 'Pengguna' }}</div>
                            <div class="admin-report-meta">{{ $report['reporter_email'] ?? '' }}</div>
                            @if(!empty($report['created_at']))
                                <div class="admin-report-meta">{{ \Carbon\Carbon::parse($report['created_at'])->diffForHumans() }}</div>
                            @endif
                        </td>
                        <td>@include('admin.partials.report-status', ['status' => $reportStatus])</td>
                        <td>
                            <div class="admin-report-actions">
                                @if($reportStatus === 'pending' && $reportId)
                                    @if($commentExists)
                                        <form action="{{ route('admin.discussion-comment-reports.takedown', $reportId) }}" method="POST"
                                              data-confirm="Komentar ini akan dihapus permanen dari diskusi."
                                              data-confirm-title="Hapus komentar?" data-confirm-ok="Ya, Hapus">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash me-1"></i>Takedown</button>
                                        </form>
                                    @endif
                                    <form action="{{ route('admin.discussion-comment-reports.dismiss', $reportId) }}" method="POST"
                                          data-confirm="Laporan ditandai tidak melanggar aturan dan komentar tetap tampil."
                                          data-confirm-title="Abaikan laporan?" data-confirm-ok="Ya, Abaikan" data-confirm-variant="primary">
                                        @csrf
                                        <button type="submit" class="btn btn-light border"><i class="bi bi-x-circle me-1"></i>Abaikan</button>
                                    </form>
                                @else
                                    <span class="admin-report-meta">Selesai</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <div class="admin-report-empty">
                                <i class="bi {{ $loadFailed ? 'bi-wifi-off' : 'bi-shield-check' }}"></i>
                                @if($loadFailed)
                                    <div class="fw-semibold text-dark">Data laporan gagal dimuat</div>
                                    <div class="small">Pastikan server backend berjalan, lalu muat ulang halaman.</div>
                                @else
                                    <div class="fw-semibold text-dark">Tidak ada laporan komentar diskusi</div>
                                    <div class="small">Belum ada laporan untuk filter ini.</div>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
