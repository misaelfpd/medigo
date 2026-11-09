<!-- Modal Viewer Gambar -->
<div class="modal fade" id="imageViewerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content bg-transparent border-0">
            <div class="modal-header border-0 pb-0 justify-content-end">
                <button type="button" class="btn-close bg-white rounded-circle p-2" data-bs-dismiss="modal" style="opacity: 0.8;"></button>
            </div>
            <div class="modal-body text-center p-0">
                <img id="fullSizeImage" src="" class="img-fluid rounded shadow-lg" style="max-height: 85vh; object-fit: contain;">
            </div>
        </div>
    </div>
</div>

<!-- Modal Report Dinamis -->
@if(Auth::check())
<div class="modal fade" id="dynamicReportModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form id="dynamicReportForm" method="POST">
                @csrf
                <div class="modal-header border-bottom pb-3">
                    <h5 class="modal-title fw-bold text-dark">Laporkan Konten</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Alasan Pelaporan</label>
                        <select name="reason_preset" class="form-select rounded-3 mb-2" id="reportReasonPreset">
                            <option value="Spam atau Promosi">Spam atau Promosi</option>
                            <option value="Hoax Kesehatan">Hoax / Misinformasi Kesehatan</option>
                            <option value="Ujaran Kebencian">Ujaran Kebencian / Pelecehan</option>
                            <option value="SARA">Konten Tidak Pantas / SARA</option>
                            <option value="Lainnya">Lainnya...</option>
                        </select>
                        <textarea name="reason" id="reportReasonText" class="form-control rounded-3 mt-2 d-none" rows="3" required></textarea>
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
@endif