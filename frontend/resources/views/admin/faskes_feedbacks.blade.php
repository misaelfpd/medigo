@extends('layouts.admin')

@section('title', 'Masukan Data Faskes - Admin MediGO')
@section('page_title', 'Masukan Data Faskes')

@php
    $categoryLabels = [
        'lokasi_salah' => 'Titik lokasi / peta salah',
        'bpjs_tidak_sesuai' => 'Status BPJS tidak sesuai',
        'kontak_keliru' => 'Nomor kontak keliru',
        'tutup_permanen' => 'Faskes sudah tutup',
        'lainnya' => 'Lainnya',
    ];
    $feedbackStatusLabels = ['pending' => 'Menunggu', 'resolved' => 'Diterapkan', 'dismissed' => 'Ditolak'];
@endphp

@section('content')
<div class="admin-report-card">
    <div class="admin-report-head">
        <div>
            <h5>Masukan &amp; Koreksi Data Faskes</h5>
            <p>Masukan pengguna tentang data faskes yang keliru: lokasi peta, status BPJS, kontak, atau faskes yang sudah tutup.</p>
        </div>
        <span class="admin-status admin-status--pending">{{ count($feedbacks) }} masukan</span>
    </div>

    <div class="admin-report-toolbar">
        @include('admin.partials.report-tabs', [
            'route' => 'admin.faskes-feedbacks',
            'tabs' => ['' => 'Semua', 'pending' => 'Menunggu', 'resolved' => 'Diterapkan', 'dismissed' => 'Ditolak'],
        ])
        @include('admin.partials.report-search', ['route' => 'admin.faskes-feedbacks', 'placeholder' => 'Cari faskes, catatan, pengirim...'])
    </div>

    <div class="table-responsive">
        <table class="table admin-report-table align-top">
            <thead>
                <tr>
                    <th>Faskes</th>
                    <th>Masukan</th>
                    <th>Pengirim</th>
                    <th>Status</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($feedbacks as $fb)
                    @php
                        $fbId = $fb['id_feedback_faskes'] ?? null;
                        $fbStatus = $fb['status'] ?? 'pending';
                        $category = $fb['kategori'] ?? 'lainnya';
                        $senderName = $fb['nama_pengguna'] ?: ($fb['registered_user_name'] ?? null) ?: 'Anonim';
                        $senderEmail = $fb['email_pengguna'] ?: ($fb['registered_user_email'] ?? null);
                    @endphp
                    <tr>
                        <td style="min-width: 220px; max-width: 300px;">
                            @if(!empty($fb['id_faskes']))
                                <a href="{{ route('faskes.detail', $fb['id_faskes']) }}" target="_blank" rel="noopener" class="admin-report-title">{{ $fb['nama_faskes'] ?? 'Faskes' }}</a>
                            @else
                                <span class="admin-report-title">{{ $fb['nama_faskes'] ?? 'Faskes' }}</span>
                            @endif
                            <div class="admin-report-meta">{{ $fb['faskes_tipe'] ?? 'Faskes' }} &bull; {{ $fb['kota_kabupaten'] ?? '-' }}</div>
                            @if(!empty($fb['faskes_alamat']))
                                <div class="admin-report-meta">{{ $fb['faskes_alamat'] }}</div>
                            @endif
                        </td>
                        <td style="min-width: 260px; max-width: 380px;">
                            <span class="admin-report-reason">{{ $categoryLabels[$category] ?? ucwords(str_replace('_', ' ', $category)) }}</span>
                            <div class="admin-report-quote">{{ $fb['catatan'] ?? '-' }}</div>
                            @if(!empty($fb['saran_koreksi']))
                                <div class="admin-report-notes mt-2"><span class="fw-semibold">Usulan data benar:</span> {{ $fb['saran_koreksi'] }}</div>
                            @endif
                            @if(!empty($fb['catatan_admin']))
                                <div class="admin-report-meta mt-2"><i class="bi bi-chat-left-text me-1"></i>Catatan admin: {{ $fb['catatan_admin'] }}</div>
                            @endif
                        </td>
                        <td style="min-width: 160px;">
                            <div class="fw-semibold text-dark">{{ $senderName }}</div>
                            @if($senderEmail)
                                <div class="admin-report-meta">{{ $senderEmail }}</div>
                            @endif
                            @if(!empty($fb['created_at']))
                                <div class="admin-report-meta">{{ \Carbon\Carbon::parse($fb['created_at'])->diffForHumans() }}</div>
                            @endif
                        </td>
                        <td>@include('admin.partials.report-status', ['status' => $fbStatus, 'labels' => $feedbackStatusLabels])</td>
                        <td>
                            <div class="admin-report-actions">
                                @if(!empty($fb['id_faskes']))
                                    <a href="{{ route('admin.faskes.edit', $fb['id_faskes']) }}" class="btn btn-light border"><i class="bi bi-pencil me-1"></i>Edit Faskes</a>
                                @endif
                                @if($fbId)
                                    <button type="button" class="btn mg-btn-primary" data-bs-toggle="modal" data-bs-target="#modalFeedback{{ $fbId }}">
                                        Tindak Lanjut
                                    </button>
                                @endif
                            </div>

                            @if($fbId)
                                <div class="modal fade text-start" id="modalFeedback{{ $fbId }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content rounded-4 border-0 shadow">
                                            <form action="{{ route('admin.faskes-feedbacks.status', $fbId) }}" method="POST">
                                                @csrf
                                                @method('PUT')
                                                <div class="modal-header border-bottom px-4 py-3">
                                                    <h6 class="modal-title fw-bold text-dark mb-0">Tindak Lanjut Masukan</h6>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                                                </div>
                                                <div class="modal-body p-4">
                                                    <div class="mb-3">
                                                        <div class="fw-bold text-dark">{{ $fb['nama_faskes'] ?? 'Faskes' }}</div>
                                                        <div class="admin-report-meta">{{ $categoryLabels[$category] ?? $category }}</div>
                                                    </div>
                                                    <div class="admin-report-quote mb-3">{{ $fb['catatan'] ?? '-' }}</div>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold small" for="fbStatus{{ $fbId }}">Status</label>
                                                        <select name="status" id="fbStatus{{ $fbId }}" class="form-select rounded-3">
                                                            <option value="pending" @selected($fbStatus === 'pending')>Menunggu ditinjau</option>
                                                            <option value="resolved" @selected($fbStatus === 'resolved')>Diterapkan (data sudah disesuaikan)</option>
                                                            <option value="dismissed" @selected($fbStatus === 'dismissed')>Ditolak (data sudah benar)</option>
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <label class="form-label fw-semibold small" for="fbNotes{{ $fbId }}">Catatan Admin <span class="text-muted fw-normal">(opsional)</span></label>
                                                        <textarea name="admin_notes" id="fbNotes{{ $fbId }}" class="form-control rounded-3" rows="3" maxlength="1000" placeholder="Contoh: nomor telepon sudah diperbarui.">{{ $fb['catatan_admin'] ?? '' }}</textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer border-top px-4 py-3">
                                                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn mg-btn-primary rounded-pill px-4">Simpan</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <div class="admin-report-empty">
                                <i class="bi {{ $loadFailed ? 'bi-wifi-off' : 'bi-inbox' }}"></i>
                                @if($loadFailed)
                                    <div class="fw-semibold text-dark">Data masukan gagal dimuat</div>
                                    <div class="small">Pastikan server backend berjalan, lalu muat ulang halaman.</div>
                                @else
                                    <div class="fw-semibold text-dark">Belum ada masukan data</div>
                                    <div class="small">Belum ada masukan untuk filter ini.</div>
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
