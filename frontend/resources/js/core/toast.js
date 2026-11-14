/**
 * MediGO - Toast global (satu gaya untuk seluruh halaman user & admin).
 * Pakai: window.showGlobalToast('Pesan', 'success' | 'error' | 'warning' | 'info')
 * Pesan flash dari server dibaca dari elemen #mgFlashMessages (partials/flash.blade.php).
 */
const TYPES = {
    success: { icon: "bi-check-circle-fill", title: "Berhasil" },
    error: { icon: "bi-x-circle-fill", title: "Terjadi Kesalahan" },
    warning: { icon: "bi-exclamation-triangle-fill", title: "Perhatian" },
    info: { icon: "bi-info-circle-fill", title: "Informasi" },
};

function container() {
    let el = document.getElementById("mgToastContainer");
    if (!el) {
        el = document.createElement("div");
        el.id = "mgToastContainer";
        el.className = "mg-toast-container";
        el.setAttribute("aria-live", "polite");
        document.body.appendChild(el);
    }
    return el;
}

export function showToast(message, type = "success", options = {}) {
    if (!message) return;
    const conf = TYPES[type] || TYPES.info;
    const toast = document.createElement("div");
    toast.className = `mg-toast mg-toast--${TYPES[type] ? type : "info"}`;
    toast.setAttribute("role", type === "error" ? "alert" : "status");

    const icon = document.createElement("i");
    icon.className = `bi ${conf.icon} mg-toast-icon`;

    const body = document.createElement("div");
    body.className = "mg-toast-body";
    const title = document.createElement("div");
    title.className = "mg-toast-title";
    title.textContent = options.title || conf.title;
    const text = document.createElement("div");
    text.className = "mg-toast-text";
    text.textContent = String(message); // aman dari HTML/XSS
    body.append(title, text);

    const close = document.createElement("button");
    close.type = "button";
    close.className = "mg-toast-close";
    close.setAttribute("aria-label", "Tutup notifikasi");
    close.innerHTML = '<i class="bi bi-x-lg"></i>';

    toast.append(icon, body, close);
    container().appendChild(toast);
    requestAnimationFrame(() => toast.classList.add("is-visible"));

    const hide = () => {
        toast.classList.remove("is-visible");
        setTimeout(() => toast.remove(), 250);
    };
    close.addEventListener("click", hide);
    setTimeout(hide, options.delay || 4500);
}

window.showGlobalToast = showToast;

function showFlashMessages() {
    const holder = document.getElementById("mgFlashMessages");
    if (!holder) return;
    try {
        const messages = JSON.parse(holder.dataset.messages || "[]");
        messages.forEach((m) => showToast(m.message, m.type));
    } catch (e) {
        /* abaikan data flash yang rusak */
    }
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", showFlashMessages);
} else {
    showFlashMessages();
}
