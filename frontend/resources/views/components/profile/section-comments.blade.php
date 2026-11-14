<div class="d-flex justify-content-between align-items-center {{ $isMobile ? 'mb-2 mt-4' : 'mt-4 mb-3' }}">
    <div>
        <h4 class="fw-bold text-dark m-0" style="font-size: {{ $isMobile ? '0.95rem' : '1.35rem' }};">Tanggapan Forum</h4>
        @if(!$isMobile)
            <p class="text-muted small m-0">Komentar dan balasan Anda di berbagai diskusi</p>
        @endif
    </div>
    <span class="badge bg-light text-medigo-primary border {{ $isMobile ? 'px-2 py-1' : 'px-3 py-1-5' }} rounded-pill fw-bold" style="font-size: {{ $isMobile ? '0.72rem' : '0.8rem' }};">
        {{ count($myForumComments ?? []) }} Balasan
    </span>
</div>

<!-- Container Scrollable -->
<div class="d-flex flex-column {{ $isMobile ? 'gap-2-5' : 'gap-3' }} mb-4 scrollable-section" style="max-height: 420px; overflow-y: auto; padding-right: 4px;">
    @forelse($myForumComments ?? [] as $komentar)
        @php
            $cId = $komentar['id_tanggapan'] ?? ($komentar['id'] ?? null);
            $cDiscId = $komentar['id_diskusi'] ?? ($komentar['discussion_id'] ?? null);
            $cBody = $komentar['isi_tanggapan'] ?? ($komentar['body'] ?? ($komentar['content'] ?? ''));
            $cTitle = $komentar['discussion_title'] ?? ($komentar['judul_diskusi'] ?? 'Diskusi Terkait');
        @endphp
        <div class="card border rounded-4 bg-light shadow-none p-3 position-relative comment-card-item" id="user-comment-{{ $cId }}">
            <div class="mb-1">
                <span class="badge bg-secondary mb-1" style="font-size: 0.65rem;">Membalas di diskusi:</span>
                <h6 class="fw-bold text-dark mb-0 text-truncate" style="font-size: {{ $isMobile ? '0.85rem' : '0.9rem' }}; max-width: 80%;">
                    <a href="{{ url('/discussions/' . $cDiscId) }}" class="text-medigo-primary text-decoration-none">
                        {{ $cTitle }}
                    </a>
                </h6>
            </div>
            
            <p class="text-dark mb-2" style="font-size: {{ $isMobile ? '0.8rem' : '0.88rem' }}; line-height: 1.4;">
                "{{ \Illuminate\Support\Str::limit($cBody, $isMobile ? 80 : 120) }}"
            </p>

            <div class="d-flex justify-content-between align-items-center">
                <span class="text-muted" style="font-size: 0.7rem;">
                    {{ !empty($komentar['created_at']) ? \Carbon\Carbon::parse($komentar['created_at'])->diffForHumans() : 'Baru saja' }}
                </span>
                
                <div class="d-flex gap-2">
                    <!-- Tombol ke Halaman Diskusi -->
                    <a href="{{ url('/discussions/' . $cDiscId) }}" class="btn btn-sm btn-outline-medigo-primary rounded-pill px-3 py-1 fw-semibold" style="font-size: 0.74rem;" title="Lihat Diskusi">
                        <i class="bi bi-box-arrow-up-right me-1"></i> Lihat
                    </a>
                    <!-- Tombol Hapus Komentar -->
                    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-2-5 py-1 fw-semibold btn-delete-forum-comment" data-id="{{ $cId }}" style="font-size: 0.72rem;" title="Hapus Komentar">
                        <i class="bi bi-trash me-1"></i> Hapus
                    </button>
                </div>
            </div>
        </div>
    @empty
        <div class="card border rounded-4 bg-white shadow-2xs p-3 text-center">
            <p class="text-muted small mb-0">Anda belum memberikan tanggapan di forum.</p>
        </div>
    @endforelse
</div>