// Tab Galeri di detail faskes: paginasi foto (data-per-page) dengan tombol sebelumnya/berikutnya & modal preview
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

        // =========================================================================
        // Modal Preview Foto Galeri
        // =========================================================================
        const modalEl = document.getElementById("galleryImageModal");
        if (modalEl && items.length > 0) {
            const modalImg = document.getElementById("galleryModalImage");
            const modalCaption = document.getElementById("galleryImageModalCaption");
            const modalIdx = document.getElementById("galleryModalCurrentIdx");
            const btnPrev = document.getElementById("galleryModalPrev");
            const btnNext = document.getElementById("galleryModalNext");

            const galleryData = Array.from(items).map((item, idx) => ({
                index: idx,
                src: item.getAttribute("data-gallery-src") || item.querySelector("img")?.src || "",
                caption: item.getAttribute("data-gallery-caption") || item.querySelector("img")?.alt || "Foto Galeri",
            }));

            let activeIdx = 0;

            const updateModalContent = (idx) => {
                if (idx < 0 || idx >= galleryData.length) return;
                activeIdx = idx;
                const data = galleryData[idx];

                if (modalImg) {
                    modalImg.style.opacity = "0.3";
                    modalImg.src = data.src;
                    modalImg.alt = data.caption;
                    modalImg.onload = () => {
                        modalImg.style.opacity = "1";
                    };
                }
                if (modalCaption) {
                    modalCaption.textContent = data.caption;
                }
                if (modalIdx) {
                    modalIdx.textContent = idx + 1;
                }
            };

            items.forEach((item, idx) => {
                item.addEventListener("click", (e) => {
                    e.preventDefault();
                    updateModalContent(idx);
                });
            });

            btnPrev?.addEventListener("click", (e) => {
                e.preventDefault();
                e.stopPropagation();
                const newIdx = (activeIdx - 1 + galleryData.length) % galleryData.length;
                updateModalContent(newIdx);
            });

            btnNext?.addEventListener("click", (e) => {
                e.preventDefault();
                e.stopPropagation();
                const newIdx = (activeIdx + 1) % galleryData.length;
                updateModalContent(newIdx);
            });

            // Navigasi keyboard panah kiri/kanan saat modal terbuka
            window.addEventListener("keydown", (e) => {
                if (!modalEl.classList.contains("show")) return;
                if (e.key === "ArrowLeft") {
                    e.preventDefault();
                    const newIdx = (activeIdx - 1 + galleryData.length) % galleryData.length;
                    updateModalContent(newIdx);
                } else if (e.key === "ArrowRight") {
                    e.preventDefault();
                    const newIdx = (activeIdx + 1) % galleryData.length;
                    updateModalContent(newIdx);
                }
            });
        }
    });
});
