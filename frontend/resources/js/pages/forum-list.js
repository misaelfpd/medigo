/**
 * Halaman daftar forum diskusi.
 * - Modal "Laporkan Diskusi"
 * - Sidebar "Diskusi Faskes Dekat Anda": judul diskusi + nama faskes, klik membuka diskusi
 */
import { faskesVisual } from "../utils/faskes-visual.js";

const esc = (v) =>
    String(v ?? "").replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" })[c]);

function initReportModal() {
    const reportModal = document.getElementById("reportDiscussionModal");
    if (!reportModal) return;

    const form = document.getElementById("reportDiscussionForm");
    const presetSelect = document.getElementById("reportReasonPreset");
    const reasonText = document.getElementById("reportReasonText");

    reportModal.addEventListener("show.bs.modal", (event) => {
        const button = event.relatedTarget;
        const discussionId = button ? button.getAttribute("data-discussion-id") : null;
        if (form && discussionId) {
            form.action = `/forum/report/discussion/${encodeURIComponent(discussionId)}`;
        }
    });

    if (!presetSelect || !reasonText) return;
    reasonText.value = presetSelect.value;
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

function initNearbyDiscussions() {
    const card = document.getElementById("forumNearbyCard");
    const list = document.getElementById("forumNearbyList");
    const btn = document.getElementById("forumNearbyLocateBtn");
    const heading = document.getElementById("forumNearbyHeading");
    if (!card || !list) return;

    const endpoint = card.dataset.url;
    const discussionBase = (card.dataset.discussionUrl || "/discussions").replace(/\/$/, "");
    const faskesBase = (card.dataset.faskesUrl || "/faskes").replace(/\/$/, "");
    const STORAGE_KEY = "medigoForumLoc";

    const note = (text, cls = "text-muted") => `<p class="${cls} mb-0" style="font-size: 0.8rem;">${esc(text)}</p>`;

    function render(items, { byLocation, expanded }) {
        if (heading) heading.textContent = byLocation ? "DISKUSI FASKES DEKAT ANDA" : "DISKUSI FASKES POPULER";

        if (!items.length) {
            list.innerHTML = note(
                byLocation
                    ? "Belum ada diskusi yang membahas faskes di sekitar Anda."
                    : "Belum ada diskusi yang menandai faskes."
            );
            return;
        }

        let html = items
            .map((d) => {
                const f = d.faskes || {};
                const visual = faskesVisual(f.jenis_faskes || "", f.nama_faskes || "");
                const distance = f.distance_km !== null && f.distance_km !== undefined ? `${Number(f.distance_km).toLocaleString("id-ID")} km` : "";
                return `
                <div class="mg-nearby-item">
                    <a href="${discussionBase}/${encodeURIComponent(d.id_diskusi)}" class="mg-nearby-title">${esc(d.judul_diskusi)}</a>
                    <a href="${faskesBase}/${encodeURIComponent(f.id_faskes)}" class="mg-nearby-faskes" title="${esc(f.nama_faskes)}">
                        <i class="bi ${visual.icon}"></i><span>${esc(f.nama_faskes || "Faskes")}</span>
                    </a>
                    <div class="mg-nearby-meta">
                        <span><i class="bi bi-chat-square"></i> ${Number(d.comments_count || 0)}</span>
                        <span><i class="bi bi-arrow-up"></i> ${Number(d.likes_count || 0)}</span>
                        ${distance ? `<span><i class="bi bi-geo-alt"></i> ${esc(distance)}</span>` : ""}
                    </div>
                </div>`;
            })
            .join("");

        if (expanded) {
            html = note("Radius diperluas karena diskusi di dekat Anda masih sedikit.").replace("mb-0", "mb-2") + html;
        }
        list.innerHTML = html;
    }

    function load(coords) {
        list.innerHTML =
            '<div class="text-muted" style="font-size: 0.8rem;"><span class="spinner-border spinner-border-sm me-1"></span> Memuat diskusi...</div>';
        const params = new URLSearchParams();
        if (coords) {
            params.set("lat", coords[0]);
            params.set("lng", coords[1]);
        }
        fetch(`${endpoint}${params.toString() ? `?${params}` : ""}`, { headers: { Accept: "application/json" } })
            .then((r) => r.json())
            .then((res) =>
                render(Array.isArray(res.data) ? res.data : [], {
                    byLocation: !!res.berbasis_lokasi,
                    expanded: !!res.radius_diperluas,
                })
            )
            .catch(() => {
                list.innerHTML = note("Gagal memuat diskusi. Coba muat ulang halaman.", "text-danger");
            });
    }

    function showLocateButton(show) {
        if (btn) btn.classList.toggle("d-none", !show);
    }

    function locate() {
        // Geolocation hanya jalan di HTTPS atau localhost/127.0.0.1
        if (!navigator.geolocation || !window.isSecureContext) {
            showLocateButton(false);
            return;
        }
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                const coords = [pos.coords.latitude, pos.coords.longitude];
                try {
                    sessionStorage.setItem(STORAGE_KEY, JSON.stringify(coords));
                } catch (e) {
                    /* sessionStorage tidak tersedia */
                }
                showLocateButton(false);
                load(coords);
            },
            () => {
                // Izin ditolak: daftar diskusi faskes populer tetap tampil, tombol untuk mencoba lagi
                showLocateButton(true);
            },
            { enableHighAccuracy: false, timeout: 10000, maximumAge: 300000 }
        );
    }

    if (btn) btn.addEventListener("click", locate);

    let saved = null;
    try {
        saved = JSON.parse(sessionStorage.getItem(STORAGE_KEY) || "null");
    } catch (e) {
        saved = null;
    }

    if (Array.isArray(saved) && saved.length === 2) {
        load(saved);
    } else {
        // Tampilkan diskusi faskes populer dulu, lalu ganti ke yang terdekat setelah izin lokasi diberikan
        load(null);
        locate();
    }
}

function initMobileFilter() {
    const categorySelect = document.getElementById("mobileForumCategorySelect");
    if (categorySelect) {
        categorySelect.addEventListener("change", function () {
            if (this.value) {
                window.location.href = this.value;
            }
        });
    }
}

function timeAgo(dateString) {
    if (!dateString) return "Baru saja";
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return "Baru saja";
    const seconds = Math.floor((new Date() - date) / 1000);
    if (seconds < 60) return "Baru saja";
    const minutes = Math.floor(seconds / 60);
    if (minutes < 60) return `${minutes} menit lalu`;
    const hours = Math.floor(minutes / 60);
    if (hours < 24) return `${hours} jam lalu`;
    const days = Math.floor(hours / 24);
    if (days < 30) return `${days} hari lalu`;
    const months = Math.floor(days / 30);
    if (months < 12) return `${months} bulan lalu`;
    const years = Math.floor(days / 365);
    return `${years} tahun lalu`;
}

function renderDiscussionCard(d) {
    const id = d.id_diskusi || d.id;
    const title = d.judul_diskusi || d.title || "Diskusi";
    const body = d.isi_diskusi || d.body || d.content || "";
    const excerpt = body.length > 200 ? body.substring(0, 200) + "..." : body;
    const category = d.kategori || d.category || "Umum";
    const author = d.author_name || d.nama_user || "Pengguna MediGO";
    const authorAvatar = d.author_avatar;
    const time = timeAgo(d.created_at);
    const commentsCount = Number(d.comments_count || 0);
    const likesCount = Number(d.likes_count || 0);
    const isLiked = !!d.liked_by_me;
    const thumbnail = d.thumbnail_url || (Array.isArray(d.images_list) && d.images_list[0]) || d.gambar_diskusi || null;
    const photoCount = Array.isArray(d.images_list) ? d.images_list.length : (thumbnail ? 1 : 0);

    const faskes = d.mentioned_faskes;
    const faskesName = faskes && typeof faskes === "object" ? faskes.nama_faskes : null;
    const faskesId = faskes && typeof faskes === "object" ? faskes.id_faskes : null;

    let coverHtml = "";
    if (thumbnail) {
        coverHtml = `
            <a href="/discussions/${encodeURIComponent(id)}" class="mg-discussion-cover">
                <img src="${esc(thumbnail)}" alt="Foto diskusi ${esc(title)}" loading="lazy" onerror="this.closest('a').classList.add('d-none');">
                ${photoCount > 1 ? `<span class="mg-discussion-cover-count">+${photoCount - 1} foto</span>` : ""}
            </a>
        `;
    }

    let faskesBadgeHtml = "";
    if (faskesName) {
        faskesBadgeHtml = `
            <a href="/faskes/${encodeURIComponent(faskesId || "")}" class="mg-chip mg-chip--mention" title="Faskes yang dibahas">
                <i class="bi bi-hospital"></i> ${esc(faskesName)}
            </a>
        `;
    }

    let avatarHtml = authorAvatar
        ? `<img src="${esc(authorAvatar)}" alt="${esc(author)}">`
        : `<i class="bi bi-person"></i>`;

    return `
        <div class="col-12 mg-discussion-item">
            <article class="mg-discussion-card">
                ${coverHtml}
                <div class="mg-discussion-meta">
                    <span class="mg-chip mg-chip--category">${esc(category)}</span>
                    ${faskesBadgeHtml}
                    <span class="mg-discussion-author">
                        ${avatarHtml}
                        ${esc(author)}
                    </span>
                    <span class="mg-discussion-time">&bull; ${esc(time)}</span>
                </div>
                <h2 class="mg-discussion-title">
                    <a href="/discussions/${encodeURIComponent(id)}">${esc(title)}</a>
                </h2>
                <p class="mg-discussion-excerpt">${esc(excerpt)}</p>
                <div class="mg-discussion-footer">
                    <a href="/discussions/${encodeURIComponent(id)}#comments" class="mg-discussion-stat">
                        <i class="bi bi-chat-square"></i> ${commentsCount} Balasan
                    </a>
                    <span class="mg-discussion-stat mg-discussion-likes ms-auto ${isLiked ? "is-liked" : ""}" title="Jumlah suka">
                        <i class="bi bi-arrow-up"></i> ${likesCount}
                    </span>
                </div>
            </article>
        </div>
    `;
}

function initForumLazyLoading() {
    const trigger = document.getElementById("forumLazyLoadingTrigger");
    const list = document.getElementById("forumDiscussionsList");
    const loadBtn = document.getElementById("btnForumLoadMore");
    const spinner = document.getElementById("forumLazySpinner");
    const endOfList = document.getElementById("forumEndOfList");

    if (!trigger || !list) return;

    let currentPage = parseInt(trigger.dataset.currentPage, 10) || 1;
    let lastPage = parseInt(trigger.dataset.lastPage, 10) || 1;
    let baseUrl = trigger.dataset.url || window.location.pathname;
    let queryParams = {};
    try {
        queryParams = JSON.parse(trigger.dataset.queryParams || "{}");
    } catch (e) {
        queryParams = {};
    }

    let isLoading = false;

    async function loadNextPage() {
        if (isLoading || currentPage >= lastPage) return;
        isLoading = true;

        if (spinner) spinner.classList.remove("d-none");
        if (loadBtn) loadBtn.classList.add("d-none");

        const nextPage = currentPage + 1;
        const params = new URLSearchParams(queryParams);
        params.set("page", nextPage);

        try {
            const res = await fetch(`${baseUrl}?${params.toString()}`, {
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    "Accept": "application/json",
                },
            });

            if (!res.ok) throw new Error("Gagal mengambil data diskusi");

            const json = await res.json();
            const items = Array.isArray(json.data) ? json.data : [];

            if (items.length > 0) {
                const fragment = document.createDocumentFragment();
                const tempDiv = document.createElement("div");
                items.forEach((item) => {
                    tempDiv.innerHTML = renderDiscussionCard(item);
                    while (tempDiv.firstChild) {
                        fragment.appendChild(tempDiv.firstChild);
                    }
                });
                list.appendChild(fragment);
            }

            currentPage = json.pagination?.current_page || nextPage;
            lastPage = json.pagination?.last_page || lastPage;
            const hasMore = json.pagination?.has_more ?? (currentPage < lastPage);

            trigger.dataset.currentPage = currentPage;
            trigger.dataset.lastPage = lastPage;

            if (!hasMore || currentPage >= lastPage) {
                trigger.classList.add("d-none");
                if (endOfList) endOfList.classList.remove("d-none");
            } else {
                if (loadBtn) loadBtn.classList.remove("d-none");
            }
        } catch (err) {
            console.error("Lazy loading error:", err);
            if (loadBtn) {
                loadBtn.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i> Coba Lagi';
                loadBtn.classList.remove("d-none");
            }
        } finally {
            if (spinner) spinner.classList.add("d-none");
            isLoading = false;
        }
    }

    if (loadBtn) {
        loadBtn.addEventListener("click", loadNextPage);
    }

    if ("IntersectionObserver" in window) {
        const observer = new IntersectionObserver(
            (entries) => {
                if (entries[0].isIntersecting && !isLoading && currentPage < lastPage) {
                    loadNextPage();
                }
            },
            { rootMargin: "200px" }
        );
        observer.observe(trigger);
    }
}

document.addEventListener("DOMContentLoaded", () => {
    initReportModal();
    initNearbyDiscussions();
    initMobileFilter();
    initForumLazyLoading();
});
