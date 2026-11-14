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

document.addEventListener("DOMContentLoaded", () => {
    initReportModal();
    initNearbyDiscussions();
});
