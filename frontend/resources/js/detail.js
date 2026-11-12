/**
 * MediGO - Faskes Detail Page Interactive Controller
 * Full CRUD for user comments, pagination load-more, and dynamic rating sync
 */

document.addEventListener("DOMContentLoaded", () => {
    initMiniMap();
    initStarRating(
        "interactiveStarPicker",
        "selectedStarValue",
        ".star-pick-item",
    );
    initStarRating(
        "editInteractiveStarPicker",
        "editSelectedStarValue",
        ".edit-star-pick-item",
    );
    initReviewActions();
    initLoadMoreComments();
    initMobileAboutToggle();
    initFeedbackAndReporting();
});

function escapeHtml(str) {
    if (str === null || str === undefined) return "";
    return String(str)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

// Helper to generate Google Maps Search URL combining Name, Address, and Coordinates
function buildGoogleMapsUrl(name, lat, lng, city = "", address = "") {
    const parts = [name, address, city].filter((p) => p && p.trim());
    const query = parts.join(", ");
    const encoded = encodeURIComponent(query);
    if (lat && lng && lat !== "" && lng !== "") {
        return `https://www.google.com/maps/search/${encoded}/@${lat},${lng},17z`;
    }
    return `https://www.google.com/maps/search/?api=1&query=${encoded}`;
}

/**
 * 1. Initialize Leaflet Mini Map Preview
 */
function initMiniMap() {
    const mapEl = document.getElementById("detailMiniMap");
    if (!mapEl || typeof L === "undefined") return;

    const data = window.detailFaskesData || {};
    const lat = data.lat || -6.2758;
    const lng = data.lng || 106.782;
    const hospitalName = data.nama_faskes || "Fasilitas Kesehatan";
    const address = data.alamat || "";
    const city = data.kota_kabupaten || "";

    // Check if navigasi_url is a specific link (has '@', 'place', 'maps.app.goo.gl', or 'dir')
    const isSpecificLink =
        data.navigasi_url &&
        data.navigasi_url !== "-" &&
        data.navigasi_url.startsWith("http") &&
        (data.navigasi_url.includes("@") ||
            data.navigasi_url.includes("place") ||
            data.navigasi_url.includes("maps.app.goo.gl") ||
            data.navigasi_url.includes("dir"));

    const navUrl = isSpecificLink
        ? data.navigasi_url
        : buildGoogleMapsUrl(hospitalName, lat, lng, city, address);

    // Sync the main navigation button href in the HTML
    const btnNav = document.getElementById("btnNavigasiDetail");
    if (btnNav) btnNav.href = navUrl;

    try {
        const miniMap = L.map("detailMiniMap", {
            center: [lat, lng],
            zoom: 15,
            zoomControl: true,
            scrollWheelZoom: false,
            attributionControl: false,
        });

        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            maxZoom: 19,
        }).addTo(miniMap);

        const customIcon = L.divIcon({
            className: "custom-detail-map-marker",
            html: `
                <div style="
                    background-color: #002b5e;
                    color: white;
                    width: 38px;
                    height: 38px;
                    border-radius: 50% 50% 50% 0;
                    transform: rotate(-45deg);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    box-shadow: 0 4px 12px rgba(0,43,94,0.35);
                    border: 2.5px solid #ffffff;
                ">
                    <i class="bi bi-hospital" style="transform: rotate(45deg); font-size: 16px;"></i>
                </div>
            `,
            iconSize: [38, 38],
            iconAnchor: [19, 38],
            popupAnchor: [0, -36],
        });

        const marker = L.marker([lat, lng], { icon: customIcon }).addTo(
            miniMap,
        );

        marker.bindPopup(`
            <div style="font-family: 'Inter', sans-serif; font-size: 12px; padding: 2px;">
                <strong style="color: #002b5e; font-size: 13px; display: block; margin-bottom: 4px;">${escapeHtml(hospitalName)}</strong>
                <a href="${escapeHtml(navUrl)}" target="_blank" style="color: #0b5ed7; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                    <i class="bi bi-sign-turn-right"></i> Buka Rute
                </a>
            </div>
        `);

        setTimeout(() => miniMap.invalidateSize(), 300);
    } catch (e) {
        console.warn("MiniMap initialization error:", e);
    }
}

/**
 * 2. Reusable Star Rating Picker
 */
function initStarRating(pickerId, inputId, itemClass) {
    const starPicker = document.getElementById(pickerId);
    const starInput = document.getElementById(inputId);
    if (!starPicker || !starInput) return;

    const stars = starPicker.querySelectorAll(itemClass);
    const sizeClass =
        pickerId === "editInteractiveStarPicker" ? "fs-3" : "fs-4";

    function highlight(count) {
        stars.forEach((star) => {
            const val = parseInt(star.getAttribute("data-rating") || "0", 10);
            if (val <= count) {
                star.className = `bi bi-star-fill ${sizeClass} ${itemClass.replace(".", "")} text-warning`;
            } else {
                star.className = `bi bi-star ${sizeClass} ${itemClass.replace(".", "")} text-muted`;
            }
        });
    }

    stars.forEach((star) => {
        const ratingVal = parseInt(star.getAttribute("data-rating") || "0", 10);

        star.addEventListener("mouseenter", () => highlight(ratingVal));
        star.addEventListener("click", () => {
            starInput.value = ratingVal;
            highlight(ratingVal);
        });
    });

    starPicker.addEventListener("mouseleave", () => {
        const currentVal = parseInt(starInput.value || "0", 10);
        highlight(currentVal);
    });

    // Initial highlight if value preset
    if (starInput.value && parseInt(starInput.value, 10) > 0) {
        highlight(parseInt(starInput.value, 10));
    }
}

/**
 * 3. Handle Full Review CRUD (Create, Edit, Delete)
 */
function initReviewActions() {
    const data = window.detailFaskesData || {};
    const faskesId = data.id;
    const csrfToken = data.csrfToken;

    let currentMyComment = data.myComment || null;
    if (Array.isArray(currentMyComment)) {
        currentMyComment =
            currentMyComment.length > 0 ? currentMyComment[0] : null;
    }

    // Ensure display state matches current comment existence on load
    if (currentMyComment) {
        document.getElementById("myCommentBox")?.classList.remove("d-none");
        document
            .getElementById("reviewInputFormContainer")
            ?.classList.add("d-none");
    }

    // Helper to generate star icons HTML
    function generateStarsHtml(rating) {
        let html = "";
        for (let i = 1; i <= 5; i++) {
            html += `<i class="bi bi-star${i <= rating ? "-fill" : ""}"></i>`;
        }
        return html;
    }

    // Helper to update global rating displays & breakdown bars across the page
    function updateRatingScoresUI(
        avgRating,
        totalReviews,
        ratingPercentages = null,
    ) {
        const count = Number(totalReviews || 0);
        const hasRating =
            avgRating !== null &&
            avgRating !== undefined &&
            avgRating !== "0.0" &&
            avgRating !== "0" &&
            count > 0;
        const ratingStr = hasRating ? parseFloat(avgRating).toFixed(1) : "-";

        // 1. Big Score in Box
        const hugeScoreEl = document.getElementById("ratingHugeScoreText");
        if (hugeScoreEl) hugeScoreEl.textContent = ratingStr;

        // 2. Stars in Score Box (if present)
        const hugeStarsEl = document.getElementById("ratingHugeStarsContainer");
        if (hugeStarsEl) {
            hugeStarsEl.innerHTML = generateStarsHtml(
                hasRating ? Math.round(parseFloat(avgRating)) : 0,
            );
        }

        // 3. Subtext in Score Box
        const subtextEl = document.getElementById("ratingSubtextDisplay");
        if (subtextEl) {
            subtextEl.innerHTML = hasRating
                ? `<span>dari 5</span>`
                : `<span>Belum dirating</span>`;
        }

        // 4. Desktop Subtitle
        const verifiedSubtitleEl = document.getElementById(
            "ratingDesktopSubtitle",
        );
        if (verifiedSubtitleEl) {
            verifiedSubtitleEl.innerHTML = hasRating
                ? `Berdasarkan <span class="total-reviews-num">${count}</span> ulasan dari pasien terverifikasi.`
                : `Fasilitas ini belum memiliki ulasan rating.`;
        }

        // 5. Total reviews counts
        document
            .querySelectorAll(".total-reviews-num")
            .forEach((el) => (el.textContent = count));

        // 6. Hero Badges
        const desktopHeroBadge = document.getElementById(
            "desktopHeroRatingBadge",
        );
        if (desktopHeroBadge) {
            desktopHeroBadge.innerHTML = hasRating
                ? `<i class="bi bi-star-fill text-warning me-1"></i> <strong>${ratingStr}</strong> (<span class="total-reviews-num">${count}</span> ulasan)`
                : `<i class="bi bi-star text-muted me-1"></i> <span>Belum dirating</span>`;
        }

        const mobileHeroBadge = document.getElementById(
            "mobileHeroRatingBadge",
        );
        if (mobileHeroBadge) {
            mobileHeroBadge.innerHTML = hasRating
                ? `<i class="bi bi-star-fill text-warning me-1"></i> ${ratingStr}`
                : `<i class="bi bi-star text-white-50 me-1"></i> Belum dirating`;
        }

        // 7. Rating Breakdown Progress Bars (5..1)
        const percentages =
            ratingPercentages ||
            (count === 0 ? { 5: 0, 4: 0, 3: 0, 2: 0, 1: 0 } : null);
        if (percentages) {
            [5, 4, 3, 2, 1].forEach((star) => {
                const bar = document.getElementById(`ratingBar${star}`);
                if (bar) {
                    const pct = Number(percentages[star] ?? 0);
                    bar.style.width = `${pct}%`;
                    bar.setAttribute("aria-valuenow", pct);
                }
            });
        }
    }

    // A. CREATE REVIEW
    const btnSubmit = document.getElementById("btnSubmitReview");
    const commentInput = document.getElementById("reviewCommentInput");
    const starInput = document.getElementById("selectedStarValue");
    const spinner = document.getElementById("reviewSpinner");
    const desktopContainer = document.getElementById("desktopReviewsContainer");
    const mobileContainer = document.getElementById("mobileReviewsContainer");

    btnSubmit?.addEventListener("click", async () => {
        const rating = parseInt(starInput?.value || "0", 10);
        const comment = commentInput?.value.trim() || "";

        if (rating === 0) {
            alert(
                "Silakan pilih jumlah bintang rating terlebih dahulu (1 - 5 bintang).",
            );
            return;
        }

        btnSubmit.disabled = true;
        spinner?.classList.remove("d-none");

        try {
            const resp = await fetch(`/faskes/${faskesId}/comments`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                    Accept: "application/json",
                },
                body: JSON.stringify({ rating: rating, body: comment }),
            });

            // Handle session expiry or unauthenticated
            if (resp.status === 401 || resp.status === 419) {
                alert("Sesi Anda telah berakhir. Silakan login kembali.");
                window.location.href =
                    "/login?redirect=" +
                    encodeURIComponent(window.location.href);
                return;
            }

            const result = await resp.json();

            if (resp.ok) {
                const commentData = result.data || {};
                const commentId =
                    commentData.id || commentData.comment?.id || Date.now();

                currentMyComment = {
                    id: commentId,
                    rating: rating,
                    body: comment,
                    created_at_human: "Baru saja",
                };

                // Update my comment box display
                const myCommentBox = document.getElementById("myCommentBox");
                const reviewFormContainer = document.getElementById(
                    "reviewInputFormContainer",
                );
                const starsDisplay = document.getElementById(
                    "myCommentStarsDisplay",
                );
                const bodyDisplay = document.getElementById(
                    "myCommentBodyDisplay",
                );
                const timeDisplay = document.getElementById(
                    "myCommentTimeDisplay",
                );

                if (starsDisplay)
                    starsDisplay.innerHTML = generateStarsHtml(rating);
                if (bodyDisplay) bodyDisplay.textContent = comment;
                if (timeDisplay) timeDisplay.textContent = "Baru saja";

                myCommentBox?.classList.remove("d-none");
                reviewFormContainer?.classList.add("d-none");

                // Remove empty notices if present
                document.getElementById("desktopEmptyReviewsNotice")?.remove();
                document.getElementById("mobileEmptyReviewsNotice")?.remove();

                const safeComment = escapeHtml(comment);

                // Prepend or update pinned card in desktop & mobile lists
                document
                    .getElementById(`desktop-review-${commentId}`)
                    ?.remove();
                const newDesktopCard = document.createElement("div");
                newDesktopCard.id = `desktop-review-${commentId}`;
                newDesktopCard.className =
                    "desktop-review-card p-3 bg-light rounded-4 border animate-fade-in mb-2";
                newDesktopCard.innerHTML = `
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <div class="d-flex align-items-center gap-1-5">
                            <span class="fw-bold text-dark" style="font-size: 0.9rem;">Anda</span>
                            <span class="badge bg-medigo-primary text-white rounded-pill px-2 py-0-5" style="font-size: 0.68rem;">Ulasan Anda</span>
                        </div>
                        <span class="text-muted" style="font-size: 0.75rem;">Baru saja</span>
                    </div>
                    <div class="d-flex text-warning gap-1 mb-2" style="font-size: 0.72rem;">
                        ${generateStarsHtml(rating)}
                    </div>
                    <p class="text-secondary mb-0" style="font-size: 0.84rem; line-height: 1.55;">
                        ${safeComment}
                    </p>
                `;
                desktopContainer?.prepend(newDesktopCard);

                document.getElementById(`mobile-review-${commentId}`)?.remove();
                const newMobileCard = document.createElement("div");
                newMobileCard.id = `mobile-review-${commentId}`;
                newMobileCard.className =
                    "mobile-review-item py-2 bg-light p-2 rounded-3 mb-2 animate-fade-in";
                newMobileCard.innerHTML = `
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <div class="d-flex align-items-center gap-1-5">
                            <span class="fw-bold text-dark" style="font-size: 0.88rem;">Anda</span>
                            <span class="badge bg-medigo-primary text-white rounded-pill px-2 py-0-5" style="font-size: 0.65rem;">Anda</span>
                        </div>
                        <span class="text-muted" style="font-size: 0.72rem;">Baru saja</span>
                    </div>
                    <div class="d-flex text-warning gap-1 mb-1" style="font-size: 0.7rem;">
                        ${generateStarsHtml(rating)}
                    </div>
                    <p class="text-secondary mb-0" style="font-size: 0.82rem; line-height: 1.5;">
                        ${safeComment}
                    </p>
                `;
                mobileContainer?.prepend(newMobileCard);

                // Update ratings
                updateRatingScoresUI(
                    commentData.avg_rating,
                    commentData.total_reviews,
                    commentData.rating_percentages || null,
                );
                showSuccessToast("Ulasan dan rating berhasil dikirim!");
            } else {
                alert(result.message || "Gagal mengirim ulasan.");
            }
        } catch (e) {
            console.error("Submit review error:", e);
            alert("Terjadi kesalahan saat mengirim ulasan.");
        } finally {
            btnSubmit.disabled = false;
            spinner?.classList.add("d-none");
        }
    });

    // B. EDIT REVIEW (Trigger Modal)
    const btnEdit = document.getElementById("btnEditMyComment");
    const modalEditEl = document.getElementById("modalEditReview");
    const editStarInput = document.getElementById("editSelectedStarValue");
    const editCommentInput = document.getElementById("editReviewCommentInput");
    const editReviewIdInput = document.getElementById("editReviewId");

    btnEdit?.addEventListener("click", () => {
        if (!currentMyComment) return;

        if (editReviewIdInput) editReviewIdInput.value = currentMyComment.id;
        if (editStarInput) editStarInput.value = currentMyComment.rating;
        if (editCommentInput)
            editCommentInput.value =
                currentMyComment.body || currentMyComment.comment || "";

        // Highlight stars in modal
        const editStars = modalEditEl?.querySelectorAll(".edit-star-pick-item");
        editStars?.forEach((star) => {
            const val = parseInt(star.getAttribute("data-rating") || "0", 10);
            if (val <= currentMyComment.rating) {
                star.className =
                    "bi bi-star-fill fs-3 edit-star-pick-item text-warning";
            } else {
                star.className =
                    "bi bi-star fs-3 edit-star-pick-item text-muted";
            }
        });

        if (typeof bootstrap !== "undefined" && modalEditEl) {
            const modal = bootstrap.Modal.getOrCreateInstance(modalEditEl);
            modal.show();
        }
    });

    // SAVE EDITED REVIEW
    const btnSaveEdit = document.getElementById("btnSaveEditReview");
    const editSpinner = document.getElementById("editReviewSpinner");

    btnSaveEdit?.addEventListener("click", async () => {
        const commentId =
            editReviewIdInput?.value ||
            currentMyComment?.id ||
            currentMyComment?.comment_id;
        const rating = parseInt(editStarInput?.value || "5", 10);
        const comment = editCommentInput?.value.trim() || "";

        if (!commentId) return;

        btnSaveEdit.disabled = true;
        editSpinner?.classList.remove("d-none");

        try {
            const resp = await fetch(
                `/faskes/${faskesId}/comments/${commentId}`,
                {
                    method: "PUT",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrfToken,
                        Accept: "application/json",
                    },
                    body: JSON.stringify({ rating: rating, body: comment }),
                },
            );

            const result = await resp.json();

            if (resp.ok) {
                const commentData = result.data || {};
                currentMyComment.rating = rating;
                currentMyComment.body = comment;

                // Update my comment box display
                const starsDisplay = document.getElementById(
                    "myCommentStarsDisplay",
                );
                const bodyDisplay = document.getElementById(
                    "myCommentBodyDisplay",
                );
                if (starsDisplay)
                    starsDisplay.innerHTML = generateStarsHtml(rating);
                if (bodyDisplay) bodyDisplay.textContent = comment;

                // Update list cards
                const desktopCard = document.getElementById(
                    `desktop-review-${commentId}`,
                );
                if (desktopCard) {
                    const starEl = desktopCard.querySelector(
                        ".d-flex.text-warning",
                    );
                    const pEl = desktopCard.querySelector("p");
                    if (starEl) starEl.innerHTML = generateStarsHtml(rating);
                    if (pEl) pEl.textContent = comment;
                }
                const mobileCard = document.getElementById(
                    `mobile-review-${commentId}`,
                );
                if (mobileCard) {
                    const starEl = mobileCard.querySelector(
                        ".d-flex.text-warning",
                    );
                    const pEl = mobileCard.querySelector("p");
                    if (starEl) starEl.innerHTML = generateStarsHtml(rating);
                    if (pEl) pEl.textContent = comment;
                }

                // Update ratings
                updateRatingScoresUI(
                    commentData.avg_rating,
                    commentData.total_reviews,
                    commentData.rating_percentages || null,
                );

                // Close modal
                if (typeof bootstrap !== "undefined" && modalEditEl) {
                    const modal = bootstrap.Modal.getInstance(modalEditEl);
                    modal?.hide();
                }

                showSuccessToast("Ulasan Anda berhasil diperbarui!");
            } else {
                alert(result.message || "Gagal mengubah ulasan.");
            }
        } catch (e) {
            console.error("Update review error:", e);
            alert("Terjadi kesalahan saat memperbarui ulasan.");
        } finally {
            btnSaveEdit.disabled = false;
            editSpinner?.classList.add("d-none");
        }
    });

    // C. DELETE REVIEW
    const btnDelete = document.getElementById("btnDeleteMyComment");
    btnDelete?.addEventListener("click", async () => {
        if (!currentMyComment) return;

        if (!confirm("Apakah Anda yakin ingin menghapus ulasan ini?")) {
            return;
        }

        const commentId = currentMyComment.id || currentMyComment.comment_id;
        try {
            const resp = await fetch(
                `/faskes/${faskesId}/comments/${commentId}`,
                {
                    method: "DELETE",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrfToken,
                        Accept: "application/json",
                    },
                },
            );

            const result = await resp.json();

            if (resp.ok) {
                const resData = result.data || {};
                currentMyComment = null;

                // Hide my comment box, show new input form and reset inputs
                document
                    .getElementById("myCommentBox")
                    ?.classList.add("d-none");
                document
                    .getElementById("reviewInputFormContainer")
                    ?.classList.remove("d-none");

                if (commentInput) commentInput.value = "";
                if (starInput) starInput.value = "0";
                document
                    .querySelectorAll("#interactiveStarPicker .star-pick-item")
                    .forEach((s) => {
                        s.className =
                            "bi bi-star fs-4 star-pick-item text-muted";
                    });

                // Remove review from lists
                document
                    .getElementById(`desktop-review-${commentId}`)
                    ?.remove();
                document.getElementById(`mobile-review-${commentId}`)?.remove();

                // Update ratings
                updateRatingScoresUI(
                    resData.avg_rating,
                    resData.total_reviews,
                    resData.rating_percentages || null,
                );
                showSuccessToast("Ulasan berhasil dihapus.");
            } else {
                alert(result.message || "Gagal menghapus ulasan.");
            }
        } catch (e) {
            console.error("Delete review error:", e);
            alert("Terjadi kesalahan saat menghapus ulasan.");
        }
    });
}

/**
 * 4. Pagination / "Lihat Komentar Lainnya" (Load More)
 */
function initLoadMoreComments() {
    const data = window.detailFaskesData || {};
    const faskesId = data.id;

    let currentPage = 1;
    const limit = 4;
    let isLoading = false;

    function renderStarsHtml(rating) {
        let html = "";
        for (let i = 1; i <= 5; i++) {
            html += `<i class="bi bi-star${i <= rating ? "-fill" : ""}"></i>`;
        }
        return html;
    }

    async function loadMore() {
        if (isLoading) return;
        isLoading = true;

        const desktopBtn = document.getElementById("btnDesktopLoadMore");
        const mobileBtn = document.getElementById("btnMobileLoadMore");
        const desktopSpinner = document.getElementById(
            "desktopLoadMoreSpinner",
        );
        const mobileSpinner = document.getElementById("mobileLoadMoreSpinner");

        if (desktopBtn) desktopBtn.disabled = true;
        if (mobileBtn) mobileBtn.disabled = true;
        desktopSpinner?.classList.remove("d-none");
        mobileSpinner?.classList.remove("d-none");

        try {
            const nextPage = currentPage + 1;
            const resp = await fetch(
                `/faskes/${faskesId}/comments?page=${nextPage}&limit=${limit}`,
            );
            if (resp.ok) {
                const res = await resp.json();
                const commData = res.data || {};
                let newComments = commData.comments || [];
                if (!Array.isArray(newComments)) {
                    if (newComments.data && Array.isArray(newComments.data)) {
                        newComments = newComments.data;
                    } else {
                        newComments = Object.values(newComments);
                    }
                }
                const hasMore = commData.has_more || false;

                const desktopContainer = document.getElementById(
                    "desktopReviewsContainer",
                );
                const mobileContainer = document.getElementById(
                    "mobileReviewsContainer",
                );

                newComments.forEach((c) => {
                    // Skip if already in DOM (e.g. pinned review)
                    if (document.getElementById(`desktop-review-${c.id}`))
                        return;

                    const safeReviewer = escapeHtml(c.reviewer_name);
                    const safeTime = escapeHtml(c.created_at_human);
                    const safeBody = escapeHtml(c.body || c.comment || "");

                    // Desktop card
                    const deskCard = document.createElement("div");
                    deskCard.id = `desktop-review-${c.id}`;
                    deskCard.className = `desktop-review-card border-bottom pb-3 animate-fade-in ${c.is_my_comment ? "p-3 bg-light rounded-4 border" : ""}`;
                    deskCard.innerHTML = `
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <div class="d-flex align-items-center gap-1-5">
                                <span class="fw-bold text-dark" style="font-size: 0.9rem;">${safeReviewer}</span>
                                ${c.is_my_comment ? '<span class="badge bg-medigo-primary text-white rounded-pill px-2 py-0-5" style="font-size: 0.68rem;">Ulasan Anda</span>' : ""}
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="text-muted" style="font-size: 0.75rem;">${safeTime}</span>
                                ${
                                    !c.is_my_comment
                                        ? `
                                    <button type="button" class="btn btn-sm btn-link text-muted p-0 text-decoration-none btn-trigger-report" data-comment-id="${c.id}" data-reviewer="${safeReviewer}" title="Laporkan ulasan tidak sopan">
                                        <i class="bi bi-flag text-danger opacity-75" style="font-size: 0.78rem;"></i>
                                    </button>
                                `
                                        : ""
                                }
                            </div>
                        </div>
                        <div class="d-flex text-warning gap-1 mb-2" style="font-size: 0.72rem;">
                            ${renderStarsHtml(c.rating)}
                        </div>
                        <p class="text-secondary mb-0" style="font-size: 0.84rem; line-height: 1.55;">
                            ${safeBody}
                        </p>
                    `;
                    desktopContainer?.appendChild(deskCard);

                    // Mobile card
                    const mobCard = document.createElement("div");
                    mobCard.id = `mobile-review-${c.id}`;
                    mobCard.className = `mobile-review-item py-2 border-bottom animate-fade-in ${c.is_my_comment ? "bg-light p-2 rounded-3 mb-2" : ""}`;
                    mobCard.innerHTML = `
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <div class="d-flex align-items-center gap-1-5">
                                <span class="fw-bold text-dark" style="font-size: 0.88rem;">${safeReviewer}</span>
                                ${c.is_my_comment ? '<span class="badge bg-medigo-primary text-white rounded-pill px-2 py-0-5" style="font-size: 0.65rem;">Anda</span>' : ""}
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="text-muted" style="font-size: 0.72rem;">${safeTime}</span>
                                ${
                                    !c.is_my_comment
                                        ? `
                                    <button type="button" class="btn btn-sm btn-link text-muted p-0 text-decoration-none btn-trigger-report" data-comment-id="${c.id}" data-reviewer="${safeReviewer}" title="Laporkan ulasan ini">
                                        <i class="bi bi-flag text-danger opacity-75"></i>
                                    </button>
                                `
                                        : ""
                                }
                            </div>
                        </div>
                        <div class="d-flex text-warning gap-1 mb-1" style="font-size: 0.7rem;">
                            ${renderStarsHtml(c.rating)}
                        </div>
                        <p class="text-secondary mb-0" style="font-size: 0.82rem; line-height: 1.5;">
                            ${safeBody}
                        </p>
                    `;
                    mobileContainer?.appendChild(mobCard);
                });

                currentPage = nextPage;

                // Toggle visibility of load more wrapper if no more items
                if (!hasMore) {
                    const deskWrap = document.getElementById(
                        "desktopLoadMoreWrapper",
                    );
                    if (deskWrap) {
                        deskWrap.classList.remove("d-lg-block");
                        deskWrap.classList.add("d-none", "d-lg-none");
                    }
                    document
                        .getElementById("mobileLoadMoreWrapper")
                        ?.classList.add("d-none");
                }
            }
        } catch (e) {
            console.error("Load more comments error:", e);
        } finally {
            isLoading = false;
            if (desktopBtn) desktopBtn.disabled = false;
            if (mobileBtn) mobileBtn.disabled = false;
            desktopSpinner?.classList.add("d-none");
            mobileSpinner?.classList.add("d-none");
        }
    }

    document
        .getElementById("btnDesktopLoadMore")
        ?.addEventListener("click", loadMore);
    document
        .getElementById("btnMobileLoadMore")
        ?.addEventListener("click", loadMore);
}

/**
 * 5. Mobile "Tentang" Accordion Toggle
 */
function initMobileAboutToggle() {
    const btnToggle = document.getElementById("btnToggleAbout");
    const aboutText = document.getElementById("mobileAboutText");
    const label = document.getElementById("toggleAboutLabel");
    const icon = document.getElementById("toggleAboutIcon");

    if (!btnToggle || !aboutText) return;

    let isExpanded = false;
    aboutText.classList.add("about-text-clamped");

    // Sembunyikan tombol jika teks memang pendek dan tidak ter-clamp
    if (aboutText.scrollHeight <= aboutText.clientHeight + 2) {
        btnToggle.classList.add("d-none");
        return;
    }

    btnToggle.addEventListener("click", () => {
        isExpanded = !isExpanded;
        if (isExpanded) {
            aboutText.classList.remove("about-text-clamped");
            if (label) label.textContent = "Tutup";
            if (icon) icon.className = "bi bi-chevron-up";
        } else {
            aboutText.classList.add("about-text-clamped");
            if (label) label.textContent = "Selengkapnya";
            if (icon) icon.className = "bi bi-chevron-down";
        }
    });
}

/**
 * Helper: Toast Notification
 */
function showSuccessToast(message) {
    const toast = document.createElement("div");
    toast.className = "detail-toast-alert";
    toast.innerHTML = `
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-check-circle-fill text-success fs-5"></i>
            <span class="fw-semibold text-dark" style="font-size: 0.85rem;">${escapeHtml(message)}</span>
        </div>
    `;
    document.body.appendChild(toast);

    setTimeout(() => toast.classList.add("show"), 50);
    setTimeout(() => {
        toast.classList.remove("show");
        setTimeout(() => toast.remove(), 300);
    }, 3500);
}

/**
 * 6. Facility Data Feedback & Comment Reporting Interactive Handlers
 */
function initFeedbackAndReporting() {
    const data = window.detailFaskesData || {};
    const faskesId = data.id;
    const csrfToken = data.csrfToken;
    const isLoggedIn = data.isLoggedIn;

    // A. Trigger Report Comment Modal
    document.addEventListener("click", (e) => {
        const trigger = e.target.closest(".btn-trigger-report");
        if (!trigger) return;

        if (!isLoggedIn) {
            alert("Silakan masuk terlebih dahulu untuk melaporkan ulasan.");
            window.location.href =
                "/login?redirect=" + encodeURIComponent(window.location.href);
            return;
        }

        const commentId = trigger.getAttribute("data-comment-id");
        const reviewer = trigger.getAttribute("data-reviewer") || "Pengguna";

        const targetIdInput = document.getElementById("reportTargetCommentId");
        const targetReviewerEl = document.getElementById(
            "reportTargetReviewerName",
        );
        const notesInput = document.getElementById("reportNotesInput");

        if (targetIdInput) targetIdInput.value = commentId;
        if (targetReviewerEl) targetReviewerEl.textContent = reviewer;
        if (notesInput) notesInput.value = "";

        const modalEl = document.getElementById("modalReportReview");
        if (modalEl && typeof bootstrap !== "undefined") {
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        }
    });

    // Submit Report Comment
    const btnSubmitReport = document.getElementById("btnSubmitReportComment");
    const reportSpinner = document.getElementById("reportCommentSpinner");
    btnSubmitReport?.addEventListener("click", async () => {
        const commentId = document.getElementById(
            "reportTargetCommentId",
        )?.value;
        const reason =
            document.getElementById("reportReasonSelect")?.value ||
            "kata_kasar";
        const notes =
            document.getElementById("reportNotesInput")?.value.trim() || "";

        if (!commentId) return;

        btnSubmitReport.disabled = true;
        reportSpinner?.classList.remove("d-none");

        try {
            const resp = await fetch(
                `/faskes/${faskesId}/comments/${commentId}/report`,
                {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrfToken,
                        Accept: "application/json",
                    },
                    body: JSON.stringify({ reason: reason, notes: notes }),
                },
            );

            if (resp.status === 401) {
                alert("Silakan masuk terlebih dahulu untuk melaporkan ulasan.");
                window.location.href =
                    "/login?redirect=" +
                    encodeURIComponent(window.location.href);
                return;
            }

            const resData = await resp.json();
            if (resp.ok) {
                const modalEl = document.getElementById("modalReportReview");
                if (modalEl && typeof bootstrap !== "undefined") {
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    modal?.hide();
                }
                showSuccessToast(
                    resData.message || "Laporan berhasil dikirim ke admin.",
                );
            } else {
                alert(resData.message || "Gagal mengirim laporan ulasan.");
            }
        } catch (e) {
            console.error("Report comment error:", e);
            alert("Terjadi kesalahan saat mengirim laporan ulasan.");
        } finally {
            btnSubmitReport.disabled = false;
            reportSpinner?.classList.add("d-none");
        }
    });

    // Helper to force close a modal and guarantee backdrop removal
    function forceCloseModal(id) {
        const el = document.getElementById(id);
        if (!el) return;
        if (typeof bootstrap !== "undefined") {
            bootstrap.Modal.getOrCreateInstance(el).hide();
        }
        // Belt-and-suspenders: manually remove stray backdrop after hide animation
        setTimeout(() => {
            document
                .querySelectorAll(".modal-backdrop")
                .forEach((b) => b.remove());
            document.body.classList.remove("modal-open");
            document.body.style.removeProperty("overflow");
            document.body.style.removeProperty("padding-right");
        }, 300);
    }

    // B. Submit Faskes Data Feedback
    const btnSubmitFeedback = document.getElementById("btnSubmitFeedback");
    const feedbackSpinner = document.getElementById("feedbackSpinner");
    btnSubmitFeedback?.addEventListener("click", async () => {
        const category =
            document.getElementById("feedbackCategorySelect")?.value ||
            "lokasi_salah";
        const notes =
            document.getElementById("feedbackNotesInput")?.value.trim() || "";
        const correction =
            document.getElementById("feedbackCorrectionInput")?.value.trim() ||
            "";

        if (!notes) {
            alert(
                "Silakan isi detail masukan atau koreksi Anda terlebih dahulu.",
            );
            document.getElementById("feedbackNotesInput")?.focus();
            return;
        }

        btnSubmitFeedback.disabled = true;
        feedbackSpinner?.classList.remove("d-none");

        try {
            const resp = await fetch(`/faskes/${faskesId}/feedback`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                    Accept: "application/json",
                },
                body: JSON.stringify({
                    category: category,
                    notes: notes,
                    suggested_correction: correction,
                }),
            });

            let resData = {};
            try {
                resData = await resp.json();
            } catch (_) {}

            // Always close the modal regardless of response
            forceCloseModal("modalFeedbackFaskes");

            // Reset form
            const notesEl = document.getElementById("feedbackNotesInput");
            const corrEl = document.getElementById("feedbackCorrectionInput");
            if (notesEl) notesEl.value = "";
            if (corrEl) corrEl.value = "";

            if (resp.ok) {
                showSuccessToast(
                    resData.message ||
                        "Masukan data faskes berhasil dikirim. Terima kasih!",
                );
            } else {
                showSuccessToast(
                    resData.message || "Masukan berhasil diterima.",
                );
            }
        } catch (e) {
            console.error("Submit feedback error:", e);
            forceCloseModal("modalFeedbackFaskes");
            alert("Terjadi kesalahan saat mengirim masukan data.");
        } finally {
            btnSubmitFeedback.disabled = false;
            feedbackSpinner?.classList.add("d-none");
        }
    });
}
