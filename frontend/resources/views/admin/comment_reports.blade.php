@extends('layouts.admin')

@section('title', 'Laporan Ulasan Faskes - Admin MediGO')
@section('page_title', 'Laporan Ulasan Faskes')

@php
    $reasonLabels = [
        'kata_kasar' => 'Kata kasar / tidak sopan',
        'sara' => 'SARA / ujaran kebencian',
        'spam' => 'Spam / promosi tidak relevan',
        'hoax' => 'Informasi palsu / fitnah',
        'lainnya' => 'Alasan lainnya',
    ];
@endphp

@section('content')
<div class="admin-report-card">
    <div class="admin-report-head">
        <div>
            <h5>Laporan Ulasan Faskes</h5>
            <p>Tinjau ulasan fasilitas kesehatan yang dilaporkan pengguna karena tidak sopan, SARA, spam, atau informasi palsu.</p>
        </div>
        <span class="admin-status admin-status--pending">{{ count($reports) }} laporan</span>
    </div>

    <div class="admin-report-toolbar">
        @include('admin.partials.report-tabs', ['route' => 'admin.comment-reports'])
        @include('admin.partials.report-search', ['route' => 'admin.comment-reports', 'placeholder' => 'Cari faskes, ulasan, pelapor...'])
    </div>
<!-- kata ael disini taro watermark -->
    <div class="table-responsive">
        <table class="table admin-report-table align-top">
            <thead>
                <tr>
                    <th>Ulasan</th>
                    <th>Alasan &amp; Catatan</th>
                    <th>Pelapor</th>
                    <th>Status</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reports as $report)
                    @php
                        $reportId = $report['id_laporan_komentar'] ?? null;
                        $reportStatus = $report['status'] ?? 'pending';
                        $reviewExists = !empty($report['comment_body']);
                        $rating = (int) ($report['comment_rating'] ?? 0);
                        $reason = $report['alasan_laporan'] ?? '';
                    @endphp
                    <tr>
                        <td style="min-width: 260px; max-width: 380px;">
                            @if(!empty($report['id_faskes']))
                                <a href="{{ route('faskes.detail', $report['id_faskes']) }}" target="_blank" rel="noopener" class="admin-report-title">{{ $report['nama_faskes'] ?? 'Faskes' }}</a>
                            @else
                                <span class="admin-report-title">{{ $report['nama_faskes'] ?? 'Faskes' }}</span>
                            @endif
                            <div class="admin-report-meta mb-2">
                                {{ $report['kota_kabupaten'] ?? '' }} &bull; oleh {{ $report['author_name'] ?? 'Pengguna' }}
                                @if($rating > 0)
                                    &bull; <i class="bi bi-star-fill text-warning"></i> {{ $rating }}/5
                                @endif
                            </div>
                            @if($reviewExists)
                                <div class="admin-report-quote">{{ $report['comment_body'] }}</div>
                            @else
                                <span class="admin-status admin-status--dismissed">Ulasan sudah dihapus</span>
                            @endif
                        </td>
                        <td style="min-width: 200px;">
                            <span class="admin-report-reason">{{ $reasonLabels[$reason] ?? ($reason ?: '-') }}</span>
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
                                    @if($reviewExists)
                                        <form action="{{ route('admin.comment-reports.takedown', $reportId) }}" method="POST"
                                              data-confirm="Ulasan ini akan dihapus permanen dari halaman faskes."
                                              data-confirm-title="Hapus ulasan?" data-confirm-ok="Ya, Hapus">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash me-1"></i>Takedown</button>
                                        </form>
                                    @endif
                                    <form action="{{ route('admin.comment-reports.dismiss', $reportId) }}" method="POST"
                                          data-confirm="Laporan ditandai tidak melanggar aturan dan ulasan tetap tampil."
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
                                    <div class="fw-semibold text-dark">Tidak ada laporan ulasan</div>
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
