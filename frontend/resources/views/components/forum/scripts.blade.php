<script>
    function setReplyTarget(commentId, commentatorName) {
        document.getElementById('replyParentId').value = commentId;
        document.getElementById('replyingToText').innerText = 'Membalas ' + commentatorName;
        document.getElementById('replyingIndicator').classList.remove('d-none');
        document.getElementById('replyInputContent').focus();
    }
    function cancelReplyTarget() {
        document.getElementById('replyParentId').value = '';
        document.getElementById('replyingIndicator').classList.add('d-none');
    }
    function openImageViewer(imageUrl) {
        document.getElementById('fullSizeImage').src = imageUrl;
        new bootstrap.Modal(document.getElementById('imageViewerModal')).show();
    }
    function previewImage(input) {
        if (input.files && input.files[0]) {
            let reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('imagePreview').src = e.target.result;
                document.getElementById('imagePreviewContainer').classList.remove('d-none');
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
    function removeImage() {
        document.getElementById('commentImageInput').value = "";
        document.getElementById('imagePreviewContainer').classList.add('d-none');
        document.getElementById('imagePreview').src = "#";
    }
    function loadMoreComments() {
        document.querySelectorAll('.hidden-comment').forEach(el => el.classList.remove('d-none'));
        const btn = document.getElementById('loadMoreCommentsBtn');
        if (btn) btn.parentElement.style.display = 'none';
    }

    document.addEventListener('DOMContentLoaded', function() {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        // =========================================================
        // HAPUS KOMENTAR / BALASAN (AJAX - event delegation)
        // =========================================================
        document.addEventListener('click', function(e) {
            const deleteBtn = e.target.closest('.btn-delete-forum-comment');
            if (!deleteBtn) return;

            const commentId = deleteBtn.getAttribute('data-id');
            const targetId = deleteBtn.getAttribute('data-target') || `comment-item-${commentId}`;

            if (!confirm('Apakah Anda yakin ingin menghapus komentar/balasan ini?')) return;

            deleteBtn.disabled = true;

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
                    const el = document.getElementById(targetId);
                    if (el) el.remove();
                } else {
                    alert(data.message || 'Gagal menghapus. Silakan coba lagi.');
                    deleteBtn.disabled = false;
                }
            })
            .catch(err => {
                console.error('Error:', err);
                alert('Terjadi kesalahan. Silakan coba lagi.');
                deleteBtn.disabled = false;
            });
        });

        // =========================================================
        // HAPUS DISKUSI (AJAX)
        // =========================================================
        document.addEventListener('click', function(e) {
            const deleteDiscBtn = e.target.closest('.btn-delete-discussion');
            if (!deleteDiscBtn) return;

            // Sudah ada confirm dari onclick di blade, tapi fallback di sini
            const discussionId = deleteDiscBtn.getAttribute('data-id');
            deleteDiscBtn.disabled = true;

            fetch(`/discussions/${discussionId}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    window.location.href = '/discussions';
                } else {
                    alert(data.message || 'Gagal menghapus diskusi.');
                    deleteDiscBtn.disabled = false;
                }
            })
            .catch(err => {
                console.error('Error:', err);
                alert('Terjadi kesalahan. Silakan coba lagi.');
                deleteDiscBtn.disabled = false;
            });
        });

        // =========================================================
        // REPORT MODAL
        // =========================================================
        const presetSelect = document.getElementById('reportReasonPreset');
        const reasonText = document.getElementById('reportReasonText');
        if (presetSelect && reasonText) {
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

        const reportModal = document.getElementById('dynamicReportModal');
        if (reportModal) {
            reportModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const reportType = button.getAttribute('data-report-type'); 
                const reportId = button.getAttribute('data-report-id');
                const form = document.getElementById('dynamicReportForm');
                form.action = `/forum/report/${reportType}/${reportId}`;
            });
        }
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>