// Tombol lihat/sembunyikan kata sandi: <button data-toggle-password="idInput"><i class="bi bi-eye"></i></button>
document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll("[data-toggle-password]").forEach((button) => {
        const input = document.getElementById(button.dataset.togglePassword);
        const icon = button.querySelector("i");
        if (!input) return;

        button.addEventListener("click", () => {
            const isPassword = input.getAttribute("type") === "password";
            input.setAttribute("type", isPassword ? "text" : "password");
            button.setAttribute("aria-label", isPassword ? "Sembunyikan kata sandi" : "Tampilkan kata sandi");
            if (icon) {
                icon.classList.toggle("bi-eye", !isPassword);
                icon.classList.toggle("bi-eye-slash", isPassword);
            }
        });
    });
});

// Tautan informasi (mis. "Lupa Kata Sandi?") menampilkan toast, bukan alert bawaan browser
document.addEventListener("click", (event) => {
    const link = event.target.closest("[data-info-toast]");
    if (!link) return;
    event.preventDefault();
    if (typeof window.showGlobalToast === "function") {
        window.showGlobalToast(link.dataset.infoToast, "info");
    }
});
