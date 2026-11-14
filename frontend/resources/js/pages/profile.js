/**
 * Halaman profil: muat lebih banyak ulasan, hapus komentar diskusi & ulasan faskes.
 */
const esc = (v) =>
    String(v ?? "").replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" })[c]);
const notify = (message, type) => {
    if (typeof window.showGlobalToast === "function") window.showGlobalToast(message, type);
    else window.alert(message);
};
const notifyError = (message) => notify(message, "error");

document.addEventListener('DOMContentLoaded', function () {

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    // ==========================================
    // 1. FITUR LOAD MORE ULASAN FASKES
    // ==========================================
    let currentProfilePage = 1;
    const loadMoreButtons = document.querySelectorAll('.btn-load-more-profile-reviews');

    loadMoreButtons.forEach(button => {
        button.addEventListener('click', function () {
            const nextPage = currentProfilePage + 1;
            const spinner = this.querySelector('.spinner-border');
            const icon = this.querySelector('.bi-arrow-clockwise');
            const isMobile = this.getAttribute('data-ismobile') === 'true';

            if (spinner) spinner.classList.remove('d-none');
            if (icon) icon.classList.add('d-none');
            this.disabled = true;

            fetch(`/profile/comments?page=${nextPage}&limit=3`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(res => res.json())
                .then(res => {
                    if (res.status === 'success' && res.data) {
                        currentProfilePage = nextPage;
                        const reviews = res.data.reviews || [];
                        const hasMore = res.data.has_more;

                        const containerId = isMobile ? 'profileReviewsContainerMobile' : 'profileReviewsContainerDesktop';
                        const container = document.getElementById(containerId);

                        reviews.forEach(review => {
                            if (container) {
                                const revId = review.id || review.id_komentar;
                                const faskesId = review.faskes_id || review.id_faskes || 1;
                                const revBody = review.body || review.isi_komentar || '';
                                const card = document.createElement('div');
                                card.className = 'card border rounded-4 bg-white shadow-2xs p-3 profile-review-item mb-3 position-relative';
                                card.id = `user-review-${revId}`;
                                card.innerHTML = `
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h6 class="fw-bold text-dark mb-0"><a href="${esc(review.faskes_url || '/faskes/' + faskesId)}" class="text-dark text-decoration-none">${esc(review.nama_faskes || 'Fasilitas Kesehatan')}</a></h6>
                                    <span class="text-muted" style="font-size: 0.76rem;">${esc(review.created_at_human || 'Baru saja')}</span>
                                </div>
                                <div class="d-flex align-items-center gap-1">
                                    <i class="bi bi-star-fill text-warning"></i>
                                    <span class="ms-1 fw-bold text-dark small">${Number(review.rating) || 0}.0</span>
                                </div>
                            </div>
                            <p class="${revBody ? 'text-dark' : 'text-muted fst-italic'} mb-2 mt-1" style="font-size: 0.88rem;">
                                ${revBody ? esc(revBody) : '(Tidak menyertakan komentar)'}
                            </p>
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <a href="/faskes/${encodeURIComponent(faskesId)}" class="btn btn-sm btn-light rounded-pill px-3 text-medigo-primary fw-semibold" style="font-size: 0.74rem;">
                                    Lihat Faskes <i class="bi bi-chevron-right ms-1"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-2 py-0-5 btn-delete-faskes-review" data-id="${esc(revId)}" style="font-size: 0.72rem;" title="Hapus Ulasan">
                                    <i class="bi bi-trash"></i> Hapus
                                </button>
                            </div>
                            `;
                                container.appendChild(card);
                            }
                        });

                        if (!hasMore) {
                            const wrapId = isMobile ? 'loadMoreProfileReviewsWrapperMobile' : 'loadMoreProfileReviewsWrapperDesktop';
                            const wrap = document.getElementById(wrapId);
                            if (wrap) wrap.style.display = 'none';
                        }
                    }
                })
                .catch(err => console.error('Error loading reviews:', err))
                .finally(() => {
                    if (spinner) spinner.classList.add('d-none');
                    if (icon) icon.classList.remove('d-none');
                    this.disabled = false;
                });
        });
    });

    // ==========================================
    // 2. HAPUS KOMENTAR/TANGGAPAN FORUM (dari profil)
    //    Gunakan event delegation agar tombol dinamis juga bisa
    // ==========================================
    document.addEventListener('click', function (e) {
        const deleteForumBtn = e.target.closest('.btn-delete-forum-comment');
        if (!deleteForumBtn) return;

        const commentId = deleteForumBtn.getAttribute('data-id');
        if (!confirm('Apakah Anda yakin ingin menghapus komentar ini?')) return;

        deleteForumBtn.disabled = true;

        fetch(`/discussion-comments/${commentId}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    // Coba hapus elemen dengan id user-comment-{id}
                    const el = document.getElementById(`user-comment-${commentId}`);
                    if (el) el.remove();
                    notify(data.message || 'Komentar berhasil dihapus.', 'success');
                } else {
                    notifyError(data.message || 'Gagal menghapus komentar.');
                    deleteForumBtn.disabled = false;
                }
            })
            .catch(err => {
                console.error('Error:', err);
                notifyError('Terjadi kesalahan. Silakan coba lagi.');
                deleteForumBtn.disabled = false;
            });
    });

    // ==========================================
    // 3. HAPUS ULASAN FASKES (dari profil)
    //    Endpoint: DELETE /reviews/{commentId}
    // ==========================================
    document.addEventListener('click', function (e) {
        const deleteFaskesBtn = e.target.closest('.btn-delete-faskes-review');
        if (!deleteFaskesBtn) return;

        const reviewId = deleteFaskesBtn.getAttribute('data-id');
        if (!confirm('Apakah Anda yakin ingin menghapus ulasan faskes ini?')) return;

        deleteFaskesBtn.disabled = true;

        fetch(`/reviews/${reviewId}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        })
            .then(res => res.json())
            .then(data => {
                // BE FaskesCommentController mengembalikan 'message' bukan 'status'
                // tapi FE FaskesDetailController forward response BE, jadi cek keduanya
                if (data.status === 'success' || data.message === 'Ulasan berhasil dihapus' || (data.message && !data.status)) {
                    const el = document.getElementById(`user-review-${reviewId}`);
                    if (el) el.remove();
                    notify(data.message || 'Ulasan berhasil dihapus.', 'success');
                } else {
                    notifyError(data.message || 'Gagal menghapus ulasan.');
                    deleteFaskesBtn.disabled = false;
                }
            })
            .catch(err => {
                console.error('Error:', err);
                notifyError('Terjadi kesalahan. Silakan coba lagi.');
                deleteFaskesBtn.disabled = false;
            });
    });
});
// Buka kembali modal pengaturan jika ada error validasi dari form di dalamnya
document.addEventListener("DOMContentLoaded", () => {
    const settingsModalEl = document.getElementById("settingsModal");
    if (settingsModalEl && settingsModalEl.dataset.autoOpen === "1" && window.bootstrap) {
        window.bootstrap.Modal.getOrCreateInstance(settingsModalEl).show();
    }
});
