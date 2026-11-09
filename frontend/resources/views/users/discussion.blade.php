@extends('layouts.app')

@section('title', 'Forum Diskusi Kesehatan - MediGO')

@section('content')
<div class="container py-4 py-lg-5">
    <!-- Breadcrumb & Header -->
    <div class="row align-items-center mb-4">
        <div class="col-lg-8">
            <nav aria-label="breadcrumb" class="mb-2">
                <ol class="breadcrumb m-0" style="font-size: 0.85rem;">
                    <li class="breadcrumb-item"><a href="/faskes" class="text-decoration-none text-muted">Beranda</a></li>
                    <li class="breadcrumb-item active text-medigo-primary fw-semibold" aria-current="page">Forum Diskusi</li>
                </ol>
            </nav>
            <h3 class="fw-bold text-dark mb-1">Forum Diskusi Komunitas MediGO</h3>
            <p class="text-muted mb-0" style="font-size: 0.92rem;">
                Tanya jawab, berbagi pengalaman layanan faskes, dan diskusikan info kesehatan terpercaya bersama masyarakat & tenaga medis.
            </p>
        </div>
        <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
            @if(Auth::check())
                <a href="{{ route('discussions.create') }}" class="btn btn-medigo-primary rounded-pill px-4 py-2 fw-semibold d-inline-flex align-items-center gap-2 shadow-xs text-white">
                    <i class="bi bi-plus-circle-fill"></i>
                    <span>Mulai Diskusi Baru</span>
                </a>
            @else
                <a href="{{ route('login') }}" class="btn btn-medigo-primary rounded-pill px-4 py-2 fw-semibold d-inline-flex align-items-center gap-2 shadow-xs text-white">
                    <i class="bi bi-box-arrow-in-right"></i>
                    <span>Masuk untuk Berdiskusi</span>
                </a>
            @endif
        </div>
    </div>

    <!-- Category Tabs -->
    <div class="d-flex gap-2 overflow-auto pb-2 mb-4" style="scrollbar-width: none;">
        <a href="{{ route('discussions') }}" class="btn btn-sm rounded-pill px-3 py-1-5 fw-semibold text-decoration-none {{ empty($category) && empty($tab) ? 'btn-medigo-primary text-white' : 'btn-outline-secondary' }}">
            Semua Topik
        </a>
        
        @if(Auth::check())
        <a href="{{ route('discussions', ['tab' => 'my_discussions']) }}" class="btn btn-sm rounded-pill px-3 py-1-5 fw-semibold text-decoration-none {{ $tab === 'my_discussions' ? 'btn-medigo-primary text-white' : 'btn-outline-secondary' }}">
            Diskusi Saya
        </a>
        @endif

        <a href="{{ route('discussions', ['category' => 'BPJS']) }}" class="btn btn-sm rounded-pill px-3 py-1-5 fw-semibold text-decoration-none {{ $category === 'BPJS' ? 'btn-medigo-primary text-white' : 'btn-outline-secondary' }}">
            BPJS
        </a>
        <a href="{{ route('discussions', ['category' => 'Umum']) }}" class="btn btn-sm rounded-pill px-3 py-1-5 fw-semibold text-decoration-none {{ $category === 'Umum' ? 'btn-medigo-primary text-white' : 'btn-outline-secondary' }}">
            Umum
        </a>
        <a href="{{ route('discussions', ['category' => 'Rekomendasi RS']) }}" class="btn btn-sm rounded-pill px-3 py-1-5 fw-semibold text-decoration-none {{ $category === 'Rekomendasi RS' ? 'btn-medigo-primary text-white' : 'btn-outline-secondary' }}">
            Rekomendasi RS
        </a>
        <a href="{{ route('discussions', ['category' => 'Tips Kesehatan']) }}" class="btn btn-sm rounded-pill px-3 py-1-5 fw-semibold text-decoration-none {{ $category === 'Tips Kesehatan' ? 'btn-medigo-primary text-white' : 'btn-outline-secondary' }}">
            Tips Kesehatan
        </a>
    </div>

    <!-- Discussions List -->
    <div class="row g-3">
        @forelse($discussions as $discussion)
            <div class="col-12">
                <div class="card border rounded-4 shadow-2xs hover-lift p-3 p-lg-4 bg-white">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge rounded-pill bg-light text-medigo-primary border px-3 py-1 fw-semibold" style="font-size: 0.75rem;">
                                {{ $discussion['category'] ?? 'Umum' }}
                            </span>
                            @if(!empty($discussion['image_path']) || !empty($discussion['images']))
                                <span class="badge rounded-pill bg-light text-secondary border px-2 py-1 fw-semibold" style="font-size: 0.72rem;">
                                    <i class="bi bi-image me-1 text-medigo-primary"></i> Foto
                                </span>
                            @endif
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="text-muted" style="font-size: 0.78rem;">
                                <i class="bi bi-clock me-1"></i>
                                {{ !empty($discussion['created_at']) ? \Carbon\Carbon::parse($discussion['created_at'])->diffForHumans() : 'Baru saja' }}
                            </span>
                            @php
                                $isMyDisc = Auth::check() && (
                                    (!empty($discussion['author_email']) && Auth::user()->email === $discussion['author_email']) ||
                                    (Auth::id() == ($discussion['user_id'] ?? 0))
                                );
                            @endphp
                            @if(Auth::check() && !$isMyDisc)
                                <div class="dropdown">
                                    <button class="btn btn-link text-muted p-0 m-0 text-decoration-none" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                        <li>
                                            <button class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#reportDiscussionModal" data-discussion-id="{{ $discussion['id'] }}">
                                                <i class="bi bi-flag me-2"></i> Laporkan Diskusi
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                            @endif
                        </div>
                    </div>

                    <h5 class="fw-bold text-dark mb-2" style="font-size: 1.05rem;">
                        <a href="{{ route('discussions.show', $discussion['id']) }}" class="text-dark text-decoration-none hover-primary">
                            {{ $discussion['title'] }}
                        </a>
                    </h5>

                    <p class="text-secondary mb-3" style="font-size: 0.88rem; line-height: 1.6;">
                        {{ \Illuminate\Support\Str::limit($discussion['body'] ?? ($discussion['content'] ?? ''), 150) }}
                    </p>

                    <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar-circle-sm bg-medigo-gradient text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 28px; height: 28px; font-size: 0.75rem;">
                                {{ strtoupper(substr($discussion['author_name'] ?? 'P', 0, 1)) }}
                            </div>
                            <span class="fw-semibold text-dark" style="font-size: 0.82rem;">
                                {{ $discussion['author_name'] ?? 'Pengguna MediGO' }}
                            </span>
                        </div>

                        <div class="d-flex align-items-center gap-3 text-muted" style="font-size: 0.82rem;">
                            <span>
                                <i class="bi bi-chat-left-text me-1 text-medigo-primary"></i>
                                {{ $discussion['comments_count'] ?? 0 }} Komentar
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12 text-center py-5">
                <i class="bi bi-chat-square-dots text-muted fs-1 mb-2"></i>
                <h5 class="fw-bold text-dark">Belum ada diskusi</h5>
                <p class="text-muted">Tidak ada diskusi yang ditemukan di kategori ini.</p>
            </div>
        @endforelse
    </div>

    <!-- UI Pagination Custom MediGO -->
    @if(!empty($pagination) && isset($pagination['last_page']) && $pagination['last_page'] > 1)
    <div class="d-flex justify-content-center mt-5 mb-3">
        <nav aria-label="Page navigation">
            <ul class="pagination pagination-sm mb-0 gap-1">
                <!-- Tombol Prev -->
                <li class="page-item {{ $pagination['current_page'] <= 1 ? 'disabled' : '' }}">
                    <a class="page-link rounded-pill px-3 py-2 border-0 fw-semibold shadow-sm text-dark hover-primary {{ $pagination['current_page'] <= 1 ? 'opacity-50' : 'bg-white' }}" 
                       href="{{ $pagination['current_page'] <= 1 ? '#' : request()->fullUrlWithQuery(['page' => $pagination['current_page'] - 1]) }}"
                       style="transition: all 0.2s ease;">
                        <i class="bi bi-chevron-left me-1" style="font-size: 0.75rem;"></i> Prev
                    </a>
                </li>
                
                <!-- Nomor Halaman -->
                @for($i = 1; $i <= $pagination['last_page']; $i++)
                    <!-- Batasi tampilan page link agar tidak terlalu panjang (menampilkan max 5 halaman) -->
                    @if($i == 1 || $i == $pagination['last_page'] || ($i >= $pagination['current_page'] - 1 && $i <= $pagination['current_page'] + 1))
                        <li class="page-item {{ $pagination['current_page'] == $i ? 'active' : '' }}">
                            <a class="page-link rounded-circle border-0 fw-bold shadow-sm d-flex align-items-center justify-content-center {{ $pagination['current_page'] == $i ? 'btn-medigo-primary' : 'bg-white text-dark hover-primary' }}" 
                               href="{{ request()->fullUrlWithQuery(['page' => $i]) }}"
                               style="width: 36px; height: 36px; transition: all 0.2s ease;">
                                {{ $i }}
                            </a>
                        </li>
                    @elseif($i == 2 && $pagination['current_page'] > 3)
                        <li class="page-item disabled"><span class="page-link border-0 bg-transparent text-muted px-1">...</span></li>
                    @elseif($i == $pagination['last_page'] - 1 && $pagination['current_page'] < $pagination['last_page'] - 2)
                        <li class="page-item disabled"><span class="page-link border-0 bg-transparent text-muted px-1">...</span></li>
                    @endif
                @endfor

                <!-- Tombol Next -->
                <li class="page-item {{ $pagination['current_page'] >= $pagination['last_page'] ? 'disabled' : '' }}">
                    <a class="page-link rounded-pill px-3 py-2 border-0 fw-semibold shadow-sm text-dark hover-primary {{ $pagination['current_page'] >= $pagination['last_page'] ? 'opacity-50' : 'bg-white' }}" 
                       href="{{ $pagination['current_page'] >= $pagination['last_page'] ? '#' : request()->fullUrlWithQuery(['page' => $pagination['current_page'] + 1]) }}"
                       style="transition: all 0.2s ease;">
                        Next <i class="bi bi-chevron-right ms-1" style="font-size: 0.75rem;"></i>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
    
    <!-- Tambahan CSS Khusus (Kalau belum ada di file CSS-mu) -->
    <style>
        .page-link.hover-primary:hover {
            background-color: #e6f0fa !important; /* Sama dengan .bg-medigo-subtle */
            color: #002b5e !important;
        }
        .page-item.active .page-link {
            z-index: 3;
        }
    </style>
    @endif


<!-- Modal Laporkan Diskusi -->
<div class="modal fade" id="reportDiscussionModal" tabindex="-1" aria-labelledby="reportDiscussionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form id="reportDiscussionForm" action="" method="POST">
                @csrf
                <input type="hidden" name="discussion_id" id="report_discussion_id">
                <div class="modal-header border-bottom pb-3">
                    <h5 class="modal-title fw-bold text-dark" id="reportDiscussionModalLabel">Laporkan Diskusi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-4">
                    <p class="text-muted mb-3" style="font-size: 0.9rem;">
                        Beri tahu kami mengapa diskusi ini menurut Anda tidak pantas. Laporan Anda akan ditinjau oleh tim kami.
                    </p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark" style="font-size: 0.88rem;">Alasan Pelaporan</label>
                        <select name="reason_preset" class="form-select rounded-3 mb-2" id="reportReasonPreset">
                            <option value="Spam atau Promosi Komersial">Spam atau Promosi Komersial</option>
                            <option value="Informasi Kesehatan Menyesatkan (Hoax)">Informasi Kesehatan Menyesatkan (Hoax)</option>
                            <option value="Ujaran Kebencian atau Pelecehan">Ujaran Kebencian atau Pelecehan</option>
                            <option value="Konten Tidak Pantas / SARA">Konten Tidak Pantas / SARA</option>
                            <option value="Lainnya">Lainnya...</option>
                        </select>
                        <textarea name="reason" id="reportReasonText" class="form-control rounded-3 mt-2 d-none" rows="3" placeholder="Tuliskan alasan spesifik Anda..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top pt-3">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4 fw-semibold">Kirim Laporan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const reportModal = document.getElementById('reportDiscussionModal');
        if (reportModal) {
            reportModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const discussionId = button.getAttribute('data-discussion-id');
                const form = document.getElementById('reportDiscussionForm');
                
                form.action = `/discussions/${discussionId}/report`;
            });
            
            const presetSelect = document.getElementById('reportReasonPreset');
            const reasonText = document.getElementById('reportReasonText');
            
            reasonText.value = presetSelect.value;
            
            presetSelect.addEventListener('change', function() {
                if (this.value === 'Lainnya') {
                    reasonText.classList.remove('d-none');
                    reasonText.value = '';
                    reasonText.required = true;
                } else {
                    reasonText.classList.add('d-none');
                    reasonText.value = this.value;
                }
            });
        }
    });
</script>
@endsection