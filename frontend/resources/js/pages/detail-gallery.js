// Tab Galeri di detail faskes: paginasi foto (data-per-page) dengan tombol sebelumnya/berikutnya
document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll(".mg-gallery").forEach((gallery) => {
        const perPage = parseInt(gallery.dataset.perPage || "4", 10);
        const items = gallery.querySelectorAll("[data-gallery-index]");
        const pages = Math.max(1, Math.ceil(items.length / perPage));
        const label = gallery.querySelector("[data-gallery-page]");
        let page = 0;

        const render = () => {
            items.forEach((item, i) => item.classList.toggle("d-none", Math.floor(i / perPage) !== page));
            if (label) label.textContent = page + 1;
        };

        gallery.querySelector("[data-gallery-prev]")?.addEventListener("click", () => {
            page = (page - 1 + pages) % pages;
            render();
        });
        gallery.querySelector("[data-gallery-next]")?.addEventListener("click", () => {
            page = (page + 1) % pages;
            render();
        });
    });
});
