@extends('layouts.admin')

@section('title', 'Laporan Diskusi - Admin MediGO')
@section('page_title', 'Laporan Diskusi')

@section('content')
<div class="admin-report-card">
    <div class="admin-report-head">
        <div>
            <h5>Laporan Diskusi</h5>
            <p>Tinjau topik diskusi forum yang dilaporkan pengguna. Takedown menyembunyikan diskusi dari forum.</p>
        </div>
        <span class="admin-status admin-status--pending">{{ count($reports) }} laporan</span>
    </div>

    <div class="admin-report-toolbar">
        @include('admin.partials.report-tabs', ['route' => 'admin.discussion-reports'])
        @include('admin.partials.report-search', ['route' => 'admin.discussion-reports', 'placeholder' => 'Cari judul, alasan, pelapor...'])
    </div>

    <div class="table-responsive">
        <table class="table admin-report-table align-top">
            <thead>
                <tr>
                    <th>Diskusi</th>
                    <th>Alasan &amp; Catatan</th>
                    <th>Pelapor</th>
                    <th>Status</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reports as $report)
                    @php
                        $reportId = $report['id_laporan_diskusi'] ?? null;
                        $discussionId = $report['id_diskusi'] ?? null;
                        $discussionStatus = $report['discussion_status'] ?? 'active';
                        $discussionActive = $discussionStatus === 'active';
                        $reportStatus = $report['status'] ?? 'pending';
                    @endphp
                    <tr>
                        <td style="min-width: 260px; max-width: 380px;">
                            @if($discussionId && $discussionActive)
                                <a href="{{ route('discussions.show', $discussionId) }}" target="_blank" rel="noopener" class="admin-report-title">
                                    {{ $report['discussion_title'] ?? 'Diskusi' }}
                                </a>
                            @else
                                <span class="admin-report-title">{{ $report['discussion_title'] ?? 'Diskusi' }}</span>
                            @endif
                            <div class="admin-report-meta mt-1">
                                {{ $report['discussion_category'] ?? 'Umum' }} &bull; oleh {{ $report['author_name'] ?? 'Pengguna' }}
                                @unless($discussionActive)
                                    &bull; <span class="text-danger fw-semibold">sudah di-takedown</span>
                                @endunless
                            </div>
                            @if(!empty($report['discussion_content']))
                                <div class="admin-report-quote mt-2">{{ \Illuminate\Support\Str::limit($report['discussion_content'], 220) }}</div>
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
                                    @if($discussionActive)
                                        <form action="{{ route('admin.discussion-reports.takedown', $reportId) }}" method="POST"
                                              data-confirm="Diskusi ini akan disembunyikan dari forum dan semua laporannya ditandai selesai."
                                              data-confirm-title="Takedown diskusi?" data-confirm-ok="Ya, Takedown">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash me-1"></i>Takedown</button>
                                        </form>
                                    @endif
                                    <form action="{{ route('admin.discussion-reports.dismiss', $reportId) }}" method="POST"
                                          data-confirm="Laporan ditandai tidak melanggar aturan dan diskusi tetap tampil."
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
                                    <div class="fw-semibold text-dark">Tidak ada laporan diskusi</div>
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
