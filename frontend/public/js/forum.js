
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
        reader.onload = function (e) {
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

document.addEventListener('DOMContentLoaded', function () {
    const presetSelect = document.getElementById('reportReasonPreset');
    const reasonText = document.getElementById('reportReasonText');
    if (presetSelect && reasonText) {
        reasonText.value = presetSelect.value;
        presetSelect.addEventListener('change', function () {
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
