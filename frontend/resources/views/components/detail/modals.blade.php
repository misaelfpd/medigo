@props(['faskes' => [], 'myComment' => null])

{{-- =========================================================================
     MODAL EDIT ULASAN
     ========================================================================= --}}
<div class="modal fade" id="modalEditReview" tabindex="-1" aria-labelledby="modalEditReviewLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            <div class="modal-header border-bottom px-4 py-3 bg-light">
                <h6 class="modal-title fw-bold text-dark m-0" id="modalEditReviewLabel">
                    <i class="bi bi-pencil-square text-medigo-primary me-1"></i> Edit Ulasan Anda
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="editReviewId" value="{{ $myComment['id'] ?? '' }}">

                <div class="mb-3 text-center">
                    <label class="form-label fw-semibold text-dark small d-block mb-2">Bintang Rating</label>
                    <div class="star-rating-selector d-flex align-items-center justify-content-center gap-2" id="editInteractiveStarPicker">
                        @for($i = 1; $i <= 5; $i++)
                            <i class="bi bi-star fs-3 edit-star-pick-item text-muted" data-rating="{{ $i }}" role="button" title="Beri {{ $i }} Bintang"></i>
                        @endfor
                        <input type="hidden" id="editSelectedStarValue" value="{{ $myComment['rating'] ?? '5' }}">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold text-dark small">Tulis Ulasan Anda</label>
                    <textarea id="editReviewCommentInput" class="form-control rounded-3" rows="4" placeholder="Ceritakan pengalaman Anda..."></textarea>
                </div>
            </div>
            <div class="modal-footer border-top px-4 py-3 bg-light d-flex justify-content-between">
                <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-medigo-primary rounded-pill px-4 fw-semibold d-flex align-items-center gap-2" id="btnSaveEditReview">
                    <span class="spinner-border spinner-border-sm d-none" id="editReviewSpinner"></span>
                    <span>Simpan Perubahan</span>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- =========================================================================
     MODAL MASUKAN & KOREKSI DATA FASKES
     ========================================================================= --}}
<div class="modal fade" id="modalFeedbackFaskes" tabindex="-1" aria-labelledby="modalFeedbackFaskesLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            <div class="modal-header border-bottom px-4 py-3 bg-light">
                <h6 class="modal-title fw-bold text-dark m-0" id="modalFeedbackFaskesLabel">
                    <i class="bi bi-patch-question-fill text-medigo-primary me-1"></i> Masukan & Koreksi Data Faskes
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="p-3 bg-light rounded-3 mb-3 border">
                    <small class="text-muted d-block" style="font-size: 0.72rem;">Fasilitas Kesehatan:</small>
                    <strong class="text-dark">{{ $faskes['nama_faskes'] ?? 'Fasilitas Kesehatan' }}</strong>
                    <div class="text-muted small">{{ $faskes['alamat'] ?? '' }}</div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold text-dark small">Bagian data yang keliru / perlu disesuaikan <span class="text-danger">*</span></label>
                    <select id="feedbackCategorySelect" class="form-select rounded-3" style="font-size: 0.88rem;">
                        <option value="lokasi_salah">Titik Lokasi / Koordinat Peta Salah</option>
                        <option value="bpjs_tidak_sesuai">Status Kerjasama BPJS Tidak Sesuai</option>
                        <option value="kontak_keliru">Nomor Telepon / Kontak Keliru</option>
                        <option value="tutup_permanen">Faskes Sudah Tutup / Tidak Beroperasi</option>
                        <option value="lainnya">Lainnya</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold text-dark small">Detail Masukan / Koreksi <span class="text-danger">*</span></label>
                    <textarea id="feedbackNotesInput" class="form-control rounded-3" rows="3" placeholder="Jelaskan kesalahan data, misalnya: titik lokasi di peta salah atau sekarang sudah tidak menerima BPJS..." style="font-size: 0.86rem;"></textarea>
                </div>

                <div class="mb-2">
                    <label class="form-label fw-semibold text-dark small">Usulan / Data yang Benar (Opsional)</label>
                    <input type="text" id="feedbackCorrectionInput" class="form-control rounded-3" placeholder="Contoh: link google maps yang benar, no telepon terbaru..." style="font-size: 0.86rem;">
                </div>
            </div>
            <div class="modal-footer border-top px-4 py-3 bg-light d-flex justify-content-between">
                <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-medigo-primary rounded-pill px-4 fw-semibold d-flex align-items-center gap-2" id="btnSubmitFeedback">
                    <span class="spinner-border spinner-border-sm d-none" id="feedbackSpinner"></span>
                    <span>Kirim Masukan</span>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- =========================================================================
     MODAL LAPORKAN KOMENTAR
     ========================================================================= --}}
<div class="modal fade" id="modalReportReview" tabindex="-1" aria-labelledby="modalReportReviewLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            <div class="modal-header border-bottom px-4 py-3 bg-light">
                <h6 class="modal-title fw-bold text-danger m-0" id="modalReportReviewLabel">
                    <i class="bi bi-flag-fill me-1"></i> Laporkan Ulasan Pengguna
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="reportTargetCommentId" value="">

                <div class="mb-3 p-3 bg-light rounded-3 border">
                    <small class="text-muted d-block" style="font-size: 0.72rem;">Pengulas yang dilaporkan:</small>
                    <strong class="text-dark" id="reportTargetReviewerName">-</strong>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold text-dark small">Alasan Pelaporan <span class="text-danger">*</span></label>
                    <select id="reportReasonSelect" class="form-select rounded-3" style="font-size: 0.88rem;">
                        <option value="kata_kasar">Kata-kata kasar / tidak sopan</option>
                        <option value="sara">Mengandung SARA / ujaran kebencian</option>
                        <option value="spam">Spam atau promosi tidak relevan</option>
                        <option value="hoax">Informasi palsu / fitnah</option>
                        <option value="lainnya">Alasan lainnya</option>
                    </select>
                </div>

                <div class="mb-2">
                    <label class="form-label fw-semibold text-dark small">Keterangan Tambahan (Opsional)</label>
                    <textarea id="reportNotesInput" class="form-control rounded-3" rows="3" placeholder="Tuliskan detail laporan jika diperlukan..." style="font-size: 0.86rem;"></textarea>
                </div>
            </div>
            <div class="modal-footer border-top px-4 py-3 bg-light d-flex justify-content-between">
                <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger rounded-pill px-4 fw-semibold d-flex align-items-center gap-2" id="btnSubmitReportComment">
                    <span class="spinner-border spinner-border-sm d-none" id="reportCommentSpinner"></span>
                    <span>Kirim Laporan</span>
                </button>
            </div>
        </div>
    </div>
</div>