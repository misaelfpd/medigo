// Panel admin: buka/tutup sidebar di layar kecil (dipindah dari layouts/admin.blade.php)
const btnToggle = document.getElementById("btnToggleSidebar");
const sidebar = document.getElementById("adminSidebar");
if (btnToggle && sidebar) {
    btnToggle.addEventListener("click", () => {
        sidebar.classList.toggle("show-mobile");
    });
}

// Konfirmasi aksi admin dengan modal yang seragam.
// Pakai: <form data-confirm="Pesan konfirmasi" data-confirm-title="Judul" data-confirm-variant="danger|primary">
const confirmModalEl = document.getElementById("adminConfirmModal");
if (confirmModalEl) {
    const titleEl = confirmModalEl.querySelector("[data-confirm-title]");
    const messageEl = confirmModalEl.querySelector("[data-confirm-message]");
    const okBtn = confirmModalEl.querySelector("[data-confirm-ok]");
    let pendingForm = null;

    document.addEventListener("submit", (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.dataset.confirm || form.dataset.confirmed === "1") return;

        event.preventDefault();
        pendingForm = form;
        titleEl.textContent = form.dataset.confirmTitle || "Konfirmasi";
        messageEl.textContent = form.dataset.confirm;
        const variant = form.dataset.confirmVariant === "primary" ? "mg-btn-primary" : "btn-danger";
        okBtn.className = `btn ${variant} rounded-pill px-4 fw-semibold flex-grow-1`;
        okBtn.textContent = form.dataset.confirmOk || "Ya, Lanjutkan";
        window.bootstrap.Modal.getOrCreateInstance(confirmModalEl).show();
    });

    okBtn.addEventListener("click", () => {
        if (!pendingForm) return;
        pendingForm.dataset.confirmed = "1";
        okBtn.disabled = true;
        pendingForm.requestSubmit ? pendingForm.requestSubmit() : pendingForm.submit();
    });

    confirmModalEl.addEventListener("hidden.bs.modal", () => {
        if (pendingForm && pendingForm.dataset.confirmed !== "1") pendingForm = null;
        okBtn.disabled = false;
    });
}
