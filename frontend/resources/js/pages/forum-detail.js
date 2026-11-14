/**
 * Halaman detail diskusi: balas komentar, viewer gambar, hapus komentar/diskusi, modal laporan.
 * Fungsi yang dipanggil lewat atribut onclick di Blade diekspos ke window.
 */
const toast = (message, type = "error") => {
    if (typeof window.showGlobalToast === "function") window.showGlobalToast(message, type);
};

function setReplyTarget(commentId, commentatorName) {
    document.getElementById("replyParentId").value = commentId;
    document.getElementById("replyingToText").innerText = "Membalas " + commentatorName;
    document.getElementById("replyingIndicator").classList.remove("d-none");
    document.getElementById("replyInputContent").focus();
}

function cancelReplyTarget() {
    document.getElementById("replyParentId").value = "";
    document.getElementById("replyingIndicator").classList.add("d-none");
}

function openImageViewer(imageUrl) {
    document.getElementById("fullSizeImage").src = imageUrl;
    window.bootstrap.Modal.getOrCreateInstance(document.getElementById("imageViewerModal")).show();
}

function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = (e) => {
            document.getElementById("imagePreview").src = e.target.result;
            document.getElementById("imagePreviewContainer").classList.remove("d-none");
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function removeImage() {
    document.getElementById("commentImageInput").value = "";
    document.getElementById("imagePreviewContainer").classList.add("d-none");
    document.getElementById("imagePreview").src = "#";
}

function loadMoreComments() {
    document.querySelectorAll(".hidden-comment").forEach((el) => el.classList.remove("d-none"));
    const btn = document.getElementById("loadMoreCommentsBtn");
    if (btn) btn.parentElement.style.display = "none";
}

Object.assign(window, { setReplyTarget, cancelReplyTarget, openImageViewer, previewImage, removeImage, loadMoreComments });

const REPORT_TITLES = {
    discussion: "Laporkan Diskusi",
    comment: "Laporkan Komentar",
};

function initReportModal() {
    const reportModal = document.getElementById("dynamicReportModal");
    if (!reportModal) return;

    const form = document.getElementById("dynamicReportForm");
    const title = reportModal.querySelector(".modal-title");
    const presetSelect = document.getElementById("reportReasonPreset");
    const reasonText = document.getElementById("reportReasonText");
    const notes = document.getElementById("dynamicReportNotes");

    const resetReason = () => {
        if (!presetSelect || !reasonText) return;
        presetSelect.selectedIndex = 0;
        reasonText.classList.add("d-none");
        reasonText.value = presetSelect.value;
    };

    if (presetSelect && reasonText) {
        resetReason();
        presetSelect.addEventListener("change", function () {
            if (this.value === "Lainnya") {
                reasonText.classList.remove("d-none");
                reasonText.value = "";
                reasonText.required = true;
            } else {
                reasonText.classList.add("d-none");
                reasonText.value = this.value;
            }
        });
    }

    reportModal.addEventListener("show.bs.modal", (event) => {
        const button = event.relatedTarget;
        if (!button || !form) return;
        const reportType = button.getAttribute("data-report-type") === "comment" ? "comment" : "discussion";
        const reportId = button.getAttribute("data-report-id");
        form.action = `/forum/report/${reportType}/${encodeURIComponent(reportId)}`;
        if (title) title.textContent = REPORT_TITLES[reportType];
        resetReason();
        if (notes) notes.value = "";
    });
}

function initDeleteActions(csrfToken) {
    // Hapus komentar / balasan (event delegation)
    document.addEventListener("click", (e) => {
        const deleteBtn = e.target.closest(".btn-delete-forum-comment");
        if (!deleteBtn) return;

        const commentId = deleteBtn.getAttribute("data-id");
        const targetId = deleteBtn.getAttribute("data-target") || `comment-item-${commentId}`;
        if (!confirm("Apakah Anda yakin ingin menghapus komentar/balasan ini?")) return;

        deleteBtn.disabled = true;
        fetch(`/discussion-comments/${encodeURIComponent(commentId)}`, {
            method: "DELETE",
            headers: { Accept: "application/json", "X-CSRF-TOKEN": csrfToken },
        })
            .then((res) => res.json())
            .then((data) => {
                if (data.status === "success") {
                    document.getElementById(targetId)?.remove();
                    toast(data.message || "Komentar berhasil dihapus.", "success");
                } else {
                    toast(data.message || "Gagal menghapus. Silakan coba lagi.");
                    deleteBtn.disabled = false;
                }
            })
            .catch(() => {
                toast("Terjadi kesalahan. Silakan coba lagi.");
                deleteBtn.disabled = false;
            });
    });

    // Hapus diskusi (konfirmasi sudah ada di onclick Blade)
    document.addEventListener("click", (e) => {
        const deleteDiscBtn = e.target.closest(".btn-delete-discussion");
        if (!deleteDiscBtn) return;

        const discussionId = deleteDiscBtn.getAttribute("data-id");
        deleteDiscBtn.disabled = true;
        fetch(`/discussions/${encodeURIComponent(discussionId)}`, {
            method: "DELETE",
            headers: { Accept: "application/json", "X-CSRF-TOKEN": csrfToken },
        })
            .then((res) => res.json())
            .then((data) => {
                if (data.status === "success") {
                    window.location.href = "/discussions";
                } else {
                    toast(data.message || "Gagal menghapus diskusi.");
                    deleteDiscBtn.disabled = false;
                }
            })
            .catch(() => {
                toast("Terjadi kesalahan. Silakan coba lagi.");
                deleteDiscBtn.disabled = false;
            });
    });
}

document.addEventListener("DOMContentLoaded", () => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content");
    initDeleteActions(csrfToken);
    initReportModal();
});
