// Ikon & warna fallback per jenis faskes. Versi PHP: app/Support/FaskesVisual.php (jaga tetap sama).
export const FASKES_TYPES = {
    "rumah-sakit": ["bi-hospital-fill", "Rumah Sakit"],
    puskesmas: ["bi-house-heart-fill", "Puskesmas"],
    klinik: ["bi-heart-pulse-fill", "Klinik"],
    apotek: ["bi-capsule-pill", "Apotek"],
    laboratorium: ["bi-eyedropper", "Laboratorium"],
    optik: ["bi-eyeglasses", "Optik"],
    "dokter-gigi": ["bi-emoji-smile-fill", "Dokter Gigi"],
    "dokter-umum": ["bi-person-badge-fill", "Dokter Umum"],
    lainnya: ["bi-plus-square-fill", "Fasilitas Kesehatan"],
};

const RULES = [
    ["dokter-gigi", ["dokter gigi", "drg", "dental", "gigi"]],
    ["dokter-umum", ["dokter umum", "praktik dokter", "praktek dokter", "dokter"]],
    ["laboratorium", ["laboratorium", "lab ", "lab.", "prodia", "pramita"]],
    ["apotek", ["apotek", "apotik", "farmasi", "pharmacy", "kimia farma"]],
    ["optik", ["optik", "optical"]],
    ["puskesmas", ["puskesmas", "pkm"]],
    ["klinik", ["klinik", "clinic", "balai pengobatan"]],
    ["rumah-sakit", ["rumah sakit", "hospital", "rsud", "rsu", "rsia", "rs "]],
];

function match(text) {
    const t = String(text || "").toLowerCase().trim();
    if (!t) return null;
    for (const [key, needles] of RULES) {
        if (needles.some((n) => (t + " ").includes(n))) return key;
    }
    // Singkatan RS di awal nama: RS, RSUD, RSPAD, RSCM, RSIA, ...
    return /^rs[a-z]*\b/.test(t) ? "rumah-sakit" : null;
}

/** Kembalikan { key, icon, label } dari jenis_faskes (prioritas) lalu nama faskes. */
export function faskesVisual(jenis, nama = "") {
    const key = match(jenis) || match(nama) || "lainnya";
    return { key, icon: FASKES_TYPES[key][0], label: FASKES_TYPES[key][1] };
}
