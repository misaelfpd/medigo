<div class="d-flex justify-content-between align-items-center {{ $isMobile ? 'mb-2 mt-4' : 'mt-4 mb-3' }}">
    <div>
        <h4 class="fw-bold text-dark m-0" style="font-size: {{ $isMobile ? '0.95rem' : '1.35rem' }};">Diskusi Forum</h4>
        @if(!$isMobile)
            <p class="text-muted small m-0">Topik diskusi kesehatan yang Anda buat di forum</p>
        @endif
    </div>
    <span class="badge bg-light text-medigo-primary border {{ $isMobile ? 'px-2 py-1' : 'px-3 py-1-5' }} rounded-pill fw-bold" style="font-size: {{ $isMobile ? '0.72rem' : '0.8rem' }};">
        {{ count($myDiscussions ?? []) }} Diskusi
    </span>
</div>

<!-- Container Scrollable -->
<div class="d-flex flex-column {{ $isMobile ? 'gap-2-5' : 'gap-3' }} mb-4 scrollable-section" style="max-height: 420px; overflow-y: auto; padding-right: 4px;">
    @forelse($myDiscussions ?? [] as $disc)
        @php
            $discId = $disc['id_diskusi'] ?? ($disc['id'] ?? null);
            $discTitle = $disc['judul_diskusi'] ?? ($disc['title'] ?? 'Topik Diskusi');
            $discBody = $disc['isi_diskusi'] ?? ($disc['body'] ?? ($disc['content'] ?? ''));
            $discCategory = $disc['kategori'] ?? ($disc['category'] ?? 'Umum');
            $discDate = !empty($disc['created_at']) ? \Carbon\Carbon::parse($disc['created_at'])->diffForHumans() : 'Baru saja';
        @endphp
        <div class="card border rounded-4 bg-light shadow-none p-3 position-relative discussion-card-item" id="user-discussion-{{ $discId }}">
            <div class="d-flex justify-content-between align-items-start mb-1">
                <div class="min-w-0" style="max-width: 80%;">
                    <span class="badge bg-light text-medigo-primary border mb-1" style="font-size: 0.65rem;">
                        {{ $discCategory }}
                    </span>
                    <h6 class="fw-bold text-dark mb-0 text-truncate" style="font-size: {{ $isMobile ? '0.85rem' : '0.92rem' }};">
                        <a href="{{ url('/discussions/' . $discId) }}" class="text-dark text-decoration-none hover-primary">
                            {{ $discTitle }}
                        </a>
                    </h6>
                </div>
                <span class="text-muted text-nowrap" style="font-size: 0.7rem;">
                    {{ $discDate }}
                </span>
            </div>
            
            @if(!empty($discBody))
                <p class="text-secondary mb-2 mt-1" style="font-size: {{ $isMobile ? '0.8rem' : '0.86rem' }}; line-height: 1.4;">
                    {{ \Illuminate\Support\Str::limit($discBody, $isMobile ? 80 : 120) }}
                </p>
            @endif

            <div class="d-flex justify-content-between align-items-center mt-2">
                <a href="{{ url('/discussions/' . $discId) }}" class="btn btn-sm btn-outline-primary rounded-pill px-2 py-0-5" style="font-size: 0.72rem;" title="Lihat Diskusi">
                    <i class="bi bi-box-arrow-up-right"></i> Lihat Diskusi
                </a>
                
                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-2 py-0-5 btn-delete-discussion" data-id="{{ $discId }}" style="font-size: 0.72rem;" title="Hapus Diskusi" onclick="return confirm('Apakah Anda yakin ingin menghapus diskusi ini?');">
                    <i class="bi bi-trash"></i> Hapus
                </button>
            </div>
        </div>
    @empty
        <div class="card border rounded-4 bg-white shadow-2xs p-3 text-center">
            <p class="text-muted small mb-0">Anda belum membuat topik diskusi di forum.</p>
        </div>
    @endforelse
</div>
