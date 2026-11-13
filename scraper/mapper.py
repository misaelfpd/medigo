import json
import csv
import os
import re
import math
from difflib import SequenceMatcher

# ==========================================
# PENENTUAN WILAYAH BERDASARKAN KODE BPJS (kdppk)
# ==========================================
# 4 digit awal kdppk adalah kode kantor cabang/wilayah BPJS. Pemetaan ini diambil dari
# data BPJS sendiri (faskes_jabodetabek.json punya kolom kota_kabupaten + _kdppk, dan
# setiap prefix selalu menunjuk ke tepat satu kota), jadi tidak perlu menebak dari teks.
KDPPK_PREFIX_KOTA = {
    "0112": "Jakarta Selatan", "0113": "Jakarta Timur", "0114": "Jakarta Pusat",
    "0115": "Jakarta Barat", "0116": "Jakarta Utara",
    "0117": "Bogor", "0133": "Bogor",            # Kab. Bogor, Kota Bogor
    "0132": "Bekasi", "0137": "Bekasi",          # Kab. Bekasi, Kota Bekasi
    "0138": "Depok",
    "0221": "Tangerang", "0223": "Tangerang",    # Kab. Tangerang, Kota Tangerang
    "0496": "Tangerang Selatan",
    # Kode puskesmas/RS pemerintah (format lain), dicek dari nama & koordinat anggotanya
    "1003": "Bogor", "1004": "Bogor",            # Kota Bogor, Kab. Bogor
    "1014": "Bekasi", "1026": "Bekasi",          # Kab. Bekasi, Kota Bekasi
    "1027": "Depok",
    "1025": "Tangerang",                         # Kota Tangerang (puskesmas & RS daerah)
}
# Prefix yang wilayahnya pasti, tetapi kotanya bisa campuran -> (kota bawaan, kota yang mungkin).
# Kota bawaan dipakai selama ada faskes sekota di dekat titiknya; kalau tidak, kota dipilih dari koordinat.
_DKI = ("Jakarta Pusat", "Jakarta Selatan", "Jakarta Timur", "Jakarta Barat", "Jakarta Utara")
_TNG = ("Tangerang", "Tangerang Selatan")
KDPPK_PREFIX_DARI_KOORDINAT = {
    "0901": ("Jakarta Pusat", _DKI), "0902": ("Jakarta Selatan", _DKI), "0903": ("Jakarta Timur", _DKI),
    "0904": ("Jakarta Barat", _DKI), "0905": ("Jakarta Utara", _DKI),   # puskesmas & RS pemerintah DKI
    "1005": (None, _TNG),   # Kab. Tangerang + Tangerang Selatan
}
# Prefix lain (Sukabumi, Cianjur, Karawang, Purwakarta, Serang, Lebak, Banjar, Kep. Seribu, dst.)
# dianggap di luar cakupan 10 kota Medigo.

PROVINSI_KOTA = {
    "Jakarta Selatan": "DKI Jakarta", "Jakarta Pusat": "DKI Jakarta", "Jakarta Timur": "DKI Jakarta",
    "Jakarta Barat": "DKI Jakarta", "Jakarta Utara": "DKI Jakarta",
    "Bogor": "Jawa Barat", "Depok": "Jawa Barat", "Bekasi": "Jawa Barat",
    "Tangerang": "Banten", "Tangerang Selatan": "Banten",
}
LUAR_WILAYAH = "__LUAR__"

NAMA_GENERIK = {
    "RUMAH SAKIT", "RS", "KLINIK", "CLINIC", "APOTEK", "APOTIK", "PHARMACY", "PUSKESMAS",
    "OPTIK", "LABORATORIUM", "LAB", "DOKTER", "HOSPITAL", "FASKES", "EMERGENCY", "IGD", "UGD",
}


def wilayah_dari_kdppk(kdppk):
    """Mengembalikan nama kota, 'KOORDINAT', atau None (di luar cakupan)."""
    prefix = str(kdppk)[:4]
    if prefix in KDPPK_PREFIX_KOTA:
        return KDPPK_PREFIX_KOTA[prefix]
    if prefix in KDPPK_PREFIX_DARI_KOORDINAT:
        return "KOORDINAT"
    return None


def parse_and_fix_coords(lat_raw, lon_raw):
    """Membaca koordinat; memperbaiki tanda latitude positif; menolak koordinat rusak (0,0 / lat==lon)."""
    try:
        lat = float(str(lat_raw).replace(",", ".").strip())
        lon = float(str(lon_raw).replace(",", ".").strip())
    except (ValueError, TypeError):
        return None, None

    if lat == 0 or lon == 0 or lat == lon:
        return None, None

    # Koreksi jika latitude tertulis positif di rentang Jawa bagian barat (misal 6.340966 -> -6.340966)
    if 5.0 <= lat <= 7.5 and 105.0 <= lon <= 109.0:
        lat = -lat

    return lat, lon


def is_plausible_jabodetabek(lat, lon):
    """Kotak kasar Jabodetabek, hanya untuk membuang koordinat yang jelas salah."""
    if lat is None or lon is None:
        return False
    return (-6.82 <= lat <= -5.90) and (106.33 <= lon <= 107.30)


class PenentuWilayah:
    """Menentukan kota dari koordinat memakai tetangga terdekat dari data BPJS yang kotanya sudah pasti."""

    SEL = 0.05  # ukuran sel grid (derajat, ~5,5 km)

    def __init__(self):
        self.grid = {}

    def tambah(self, kdppk, lat, lon, label):
        key = (int(math.floor(lat / self.SEL)), int(math.floor(lon / self.SEL)))
        self.grid.setdefault(key, []).append((lat, lon, label, kdppk))

    def tetangga(self, lat, lon, k=7, abaikan_kdppk=None, radius_sel=2):
        ci, cj = int(math.floor(lat / self.SEL)), int(math.floor(lon / self.SEL))
        kandidat = []
        for di in range(-radius_sel, radius_sel + 1):
            for dj in range(-radius_sel, radius_sel + 1):
                for (la, lo, label, kd) in self.grid.get((ci + di, cj + dj), []):
                    if abaikan_kdppk and kd == abaikan_kdppk:
                        continue
                    jarak = haversine_distance(lat, lon, la, lo)
                    kandidat.append((jarak, label))
        kandidat.sort(key=lambda x: x[0])
        return kandidat[:k]

    def kota_di(self, lat, lon, abaikan_kdppk=None, maks_jarak=5000, pilihan=None):
        """Kota mayoritas dari tetangga terdekat; None jika tidak ada data BPJS di sekitar titik.
        Jika `pilihan` diisi, hanya kota-kota itu yang dipertimbangkan."""
        if pilihan:
            semua = self.tetangga(lat, lon, k=400, abaikan_kdppk=abaikan_kdppk, radius_sel=4)
            dekat = [t for t in semua if t[1] in pilihan][:7]
        else:
            dekat = [t for t in self.tetangga(lat, lon, abaikan_kdppk=abaikan_kdppk) if t[0] <= maks_jarak]
        if not dekat:
            return None
        skor = {}
        for jarak, label in dekat:
            skor[label] = skor.get(label, 0) + 1.0 / (1.0 + jarak / 500.0)
        return max(skor.items(), key=lambda x: x[1])[0]

    def ada_kota_sama_di_dekat(self, lat, lon, kota, abaikan_kdppk=None, maks_jarak=3000):
        return any(
            label == kota and jarak <= maks_jarak
            for jarak, label in self.tetangga(lat, lon, k=400, abaikan_kdppk=abaikan_kdppk)
        )


def baca_item_bpjs(bpjs_file):
    with open(bpjs_file, "r", encoding="utf-8") as f:
        raw_bpjs = json.load(f)
    items_bpjs = []
    if isinstance(raw_bpjs, dict):
        for val in raw_bpjs.values():
            if isinstance(val, list):
                items_bpjs.extend(val)
    elif isinstance(raw_bpjs, list):
        for el in raw_bpjs:
            if isinstance(el, list):
                items_bpjs.extend(el)
            elif isinstance(el, dict):
                items_bpjs.append(el)
    return [it for it in items_bpjs if isinstance(it, dict)]


def haversine_distance(lat1, lon1, lat2, lon2):
    """Menghitung jarak dalam meter antara dua titik koordinat."""
    try:
        lat1, lon1, lat2, lon2 = map(float, [lat1, lon1, lat2, lon2])
    except (ValueError, TypeError):
        return float('inf')
    
    R = 6371000
    phi1, phi2 = math.radians(lat1), math.radians(lat2)
    dphi = math.radians(lat2 - lat1)
    dlambda = math.radians(lon2 - lon1)
    
    a = math.sin(dphi / 2.0)**2 + math.cos(phi1) * math.cos(phi2) * math.sin(dlambda / 2.0)**2
    return 2 * R * math.atan2(math.sqrt(a), math.sqrt(1 - a))

def normalize_rs_name(name):
    """Membersihkan awalan umum & ejaan untuk pencocokan nama yang akurat."""
    n = name.upper()
    n = n.replace("SOEBROTO", "SUBROTO").replace("SUHARSO", "SOEHARSO")
    n = re.sub(r'\bRSCM\b', 'CIPTO MANGUNKUSUMO', n)
    n = re.sub(r'\bRSPAD\b', 'GATOT SUBROTO', n)
    n = re.sub(r'\bRSPI\b', 'SULIANTI SAROSO', n)
    
    generic_words = [
        r'\bRUMAH SAKIT UMUM PUSAT NASIONAL\b', r'\bRUMAH SAKIT UMUM PUSAT\b',
        r'\bRUMAH SAKIT UMUM DAERAH\b', r'\bRUMAH SAKIT UMUM\b',
        r'\bRUMAH SAKIT KHUSUS\b', r'\bRUMAH SAKIT\b', r'\bKEPRESIDENAN\b',
        r'\bRSUPN\b', r'\bRSUP\b', r'\bRSUD\b', r'\bRSU\b', r'\bRSIA\b', 
        r'\bRSK\b', r'\bRSJ\b', r'\bRS\b', r'\bKLINIK PRATAMA\b', 
        r'\bKLINIK UTAMA\b', r'\bKLINIK\b', r'\bPUSKESMAS\b', r'\bAPOTEK\b', 
        r'\bAPOTIK\b', r'\bOPTIK\b', r'\bLABORATORIUM\b', r'\bLAB\b',
        r'\bDR\b', r'\bPROF\b', r'\bH\b', r'\bHJ\b', r'\bTNI AD\b',
        r'\bIBU DAN ANAK\b', r'\bKHUSUS\b', r'\bDAERAH\b', r'\bUMUM\b', r'\bPUSAT\b'
    ]
    for pattern in generic_words:
        n = re.sub(pattern, ' ', n)
        
    n = re.sub(r'[^A-Z0-9\s]', ' ', n)
    return re.sub(r'\s+', ' ', n).strip()

def is_duplicate_faskes(osm_nama, osm_lat, osm_lon, bpjs_list):
    """Mengecek apakah faskes OSM sudah ada di daftar BPJS berdasarkan jarak & kemiripan nama."""
    norm_osm = normalize_rs_name(osm_nama)
    if not norm_osm:
        return False

    for bpjs in bpjs_list:
        norm_bpjs = bpjs["norm_name"]
        if not norm_bpjs:
            continue

        dist = haversine_distance(osm_lat, osm_lon, bpjs["latitude"], bpjs["longitude"])
        sim = SequenceMatcher(None, norm_osm, norm_bpjs).ratio()

        if norm_osm == norm_bpjs and (dist < 15000 or dist == float('inf')):
            return True

        if (norm_osm in norm_bpjs or norm_bpjs in norm_osm) and len(min(norm_osm, norm_bpjs, key=len)) >= 5:
            if dist < 5000 or dist == float('inf'):
                return True

        if dist < 500 and sim >= 0.58:
            return True

        if dist < 3500 and sim >= 0.80:
            return True

    return False

def normalize_jenis_faskes(jenis_raw, nama_faskes="", is_osm=False, tags=None):
    """Menstandarkan penulisan jenis_faskes (termasuk Lab, Dokter Umum, Dokter Gigi) dan membuang tempat non-medis."""
    nama_up = nama_faskes.upper()
    jenis_up = (jenis_raw or "").upper().strip()

    blacklist_keywords = [
        "GYM", "FITNESS", "SINSHE", "SHINSHE", "PIJAT", "MASSAGE", 
        "SALON", "SPA", "SKINCARE", "BEAUTY", "PET SHOP", "VETERINARY", "HEWAN"
    ]
    if any(k in nama_up for k in blacklist_keywords):
        return None
    if tags and (tags.get("leisure") or tags.get("sport") or tags.get("shop") in ["beauty", "massage", "herbalist"]):
        return None

    if any(k in jenis_up for k in ["LABORATORIUM", "LAB"]):
        return "Laboratorium"
    if "DOKTER GIGI" in jenis_up or "DRG" in jenis_up:
        return "Dokter Gigi"
    if any(k in jenis_up for k in ["DOKTER UMUM", "DOKTER PRAKTEK PERORANGAN", "DOKTER PRAKTIK PERORANGAN", "DPP", "PRAKTEK DOKTER", "PRAKTIK DOKTER"]):
        return "Dokter Umum"
    if "PUSKESMAS" in jenis_up:
        return "Puskesmas"
    if "KLINIK UTAMA" in jenis_up:
        return "Klinik Utama"
    if "KLINIK PRATAMA" in jenis_up or "KLINIK" in jenis_up:
        return "Klinik Pratama"
    if "RUMAH SAKIT" in jenis_up or jenis_up in ["RS", "RSU", "RSUD", "RSIA", "RSK", "RSUP"]:
        return "Rumah Sakit"
    if "APOTEK" in jenis_up or "APOTIK" in jenis_up:
        return "Apotek"
    if "OPTIK" in jenis_up:
        return "Optik"

    if any(k in nama_up for k in ["LABORATORIUM", "LAB ", "LAB.", "PRODIA", "BIOMEDIKA", "CITO", " KIMIA FARMA LAB", "PATHLAB", "GENOMIK"]):
        return "Laboratorium"
    if any(k in nama_up for k in ["DOKTER GIGI", "DRG.", "DRG ", "DENTAL"]):
        if "RS" not in nama_up and "RUMAH SAKIT" not in nama_up and "KLINIK" not in nama_up:
            return "Dokter Gigi"
    if any(k in nama_up for k in ["DOKTER UMUM", "PRAKTEK DOKTER", "PRAKTIK DOKTER"]) or (nama_up.startswith(("DR.", "DR ")) and "RS" not in nama_up and "KLINIK" not in nama_up):
        return "Dokter Umum"
    if any(k in nama_up for k in ["APOTEK", "APOTIK", "FARMASI", "KIMIA FARMA", "K-24", "K24", "GUARDIAN", "CENTURY", "WATSONS"]):
        return "Apotek"
    if "OPTIK" in nama_up or "OPTICAL" in nama_up:
        return "Optik"
    if "PUSKESMAS" in nama_up or "PKM " in nama_up:
        return "Puskesmas"
    if "KLINIK UTAMA" in nama_up:
        return "Klinik Utama"
    if any(k in nama_up for k in ["KLINIK", "CLINIC", "BALAI PENGOBATAN"]):
        return "Klinik Pratama"

    if is_osm and tags:
        amenity = tags.get("amenity", "").lower()
        healthcare = tags.get("healthcare", "").lower()
        if healthcare == "laboratory" or amenity == "laboratory":
            return "Laboratorium"
        if amenity == "dentist" or healthcare == "dentist":
            return "Dokter Gigi"
        if amenity == "doctors" or healthcare == "doctor":
            return "Dokter Umum"
        if amenity == "pharmacy" or healthcare == "pharmacy":
            return "Apotek"
        if healthcare == "optometrist":
            return "Optik"
        if amenity == "clinic" or healthcare in ["clinic", "centre"]:
            return "Klinik Pratama"
        if amenity == "hospital" or healthcare == "hospital":
            if any(k in nama_up for k in ["RS ", "RS.", "RSU", "RSUD", "RSIA", "RSUP", "RSK", "RSPAD", "RSCM", "RSPI", "RUMAH SAKIT", "HOSPITAL", "MEDICAL CENTER", "MEDICAL CENTRE"]):
                return "Rumah Sakit"
            return "Klinik Pratama"

    if any(k in nama_up for k in ["RS ", "RS.", "RSU", "RSUD", "RSIA", "RSUP", "RSK", "RSPAD", "RSCM", "RSPI", "RUMAH SAKIT", "HOSPITAL"]):
        return "Rumah Sakit"

    return "Klinik Pratama"

def main():
    jabodetabek_cities = [
        "Jakarta Selatan", "Jakarta Pusat", "Jakarta Timur", 
        "Jakarta Barat", "Jakarta Utara", 
        "Bogor", "Depok", "Tangerang", "Tangerang Selatan", "Bekasi"
    ]

    faskes_dict = {}
    bpjs_reference_list = []
    skipped_outside_bbox = 0
    skipped_bad_coords = 0
    skipped_invalid = 0
    skipped_duplicates = 0

    # ==========================================
    # 1. BACA & GABUNGKAN SEMUA FILE JSON BPJS
    # ==========================================
    bpjs_files = ["faskes_raw_playwright.json", "faskes_jabodetabek (1).json", "faskes_jabodetabek.json"]
    found_any_bpjs = False

    # Kumpulkan dulu semua item dari semua file (urutan file dipertahankan: file belakangan meng-update file sebelumnya)
    semua_item_bpjs = []
    for bpjs_file in bpjs_files:
        if not os.path.exists(bpjs_file):
            continue
        found_any_bpjs = True
        print(f"Membaca & menggabungkan data BPJS dari: {bpjs_file}...")
        semua_item_bpjs.extend(baca_item_bpjs(bpjs_file))

    # Referensi wilayah: titik BPJS yang kotanya pasti dari kdppk (termasuk yang di luar Jabodetabek)
    penentu = PenentuWilayah()
    sudah_ref = set()
    for item in semua_item_bpjs:
        kdppk = str(item.get("_kdppk") or item.get("kdppk") or "").strip()
        if not kdppk or kdppk in sudah_ref:
            continue
        wilayah = wilayah_dari_kdppk(kdppk)
        if wilayah == "KOORDINAT":
            continue  # kotanya belum pasti, jangan dipakai sebagai referensi
        lat, lon = parse_and_fix_coords(item.get("latitude", ""), item.get("longitude", ""))
        if lat is None:
            continue
        if wilayah is not None and not is_plausible_jabodetabek(lat, lon):
            continue
        sudah_ref.add(kdppk)
        penentu.tambah(kdppk, lat, lon, wilayah if wilayah else LUAR_WILAYAH)

    for item in semua_item_bpjs:
        kdppk = str(item.get("_kdppk") or item.get("kdppk") or "").strip()
        if not kdppk:
            continue

        nama = (item.get("nama_faskes") or item.get("nmppk") or "").strip().upper()
        jenis_raw = (item.get("jenis_faskes") or item.get("nmjnsppk") or "").strip()
        alamat = (item.get("alamat_faskes") or item.get("nmjlnppk") or "").strip()
        alamat = re.sub(r"\s+", " ", alamat).strip()

        # 1. Wilayah dari kode BPJS (bukan tebakan dari nama jalan)
        wilayah = wilayah_dari_kdppk(kdppk)
        if wilayah is None:
            skipped_outside_bbox += 1
            continue

        # 2. Koordinat wajib valid & masuk akal
        lat, lon = parse_and_fix_coords(item.get("latitude", ""), item.get("longitude", ""))
        if not is_plausible_jabodetabek(lat, lon):
            skipped_bad_coords += 1
            continue

        if wilayah == "KOORDINAT":
            bawaan, pilihan = KDPPK_PREFIX_DARI_KOORDINAT[kdppk[:4]]
            if bawaan and penentu.ada_kota_sama_di_dekat(lat, lon, bawaan, abaikan_kdppk=kdppk, maks_jarak=2000):
                kota = bawaan
            else:
                kota = penentu.kota_di(lat, lon, abaikan_kdppk=kdppk, pilihan=pilihan)
            if kota is None:
                skipped_bad_coords += 1
                continue
        else:
            kota = wilayah
            # Koordinat harus konsisten dengan kotanya. Yang dibuang hanya titik yang jelas berada di
            # kota Jabodetabek LAIN tanpa satu pun faskes sekota dalam radius 5 km
            # (contoh: farmasi puskesmas Bogor Timur yang titiknya di Jakarta Pusat).
            kota_titik = penentu.kota_di(lat, lon, abaikan_kdppk=kdppk)
            if (kota_titik in PROVINSI_KOTA and kota_titik != kota
                    and not penentu.ada_kota_sama_di_dekat(lat, lon, kota, abaikan_kdppk=kdppk, maks_jarak=5000)):
                skipped_bad_coords += 1
                continue
        provinsi = PROVINSI_KOTA[kota]

        jenis = normalize_jenis_faskes(jenis_raw, nama, is_osm=False)
        if not jenis:
            skipped_invalid += 1
            continue

        kelas_raw = (
            item.get("kelas_faskes") or
            item.get("kelas_rs") or
            item.get("klsrsu") or
            item.get("kelas") or
            ""
        )
        kelas_rs = str(kelas_raw).upper().replace("KELAS", "").strip() if kelas_raw else ""
        # Pastikan kelas_rs hanya A/B/C/D dan hanya berlaku untuk Rumah Sakit (Klinik Utama dikosongkan)
        if kelas_rs not in ["A", "B", "C", "D"] or jenis != "Rumah Sakit":
            kelas_rs = ""

        telp = str(item.get("telp_faskes") or item.get("telpppk") or "").strip()
        if "undefined" in telp.lower() or not telp:
            telp = "-"

        if kdppk in faskes_dict:
            lama = faskes_dict[kdppk]
            if not kelas_rs and lama["kelas_rs"] and jenis == "Rumah Sakit":
                kelas_rs = lama["kelas_rs"]
            if telp == "-" and lama["telepon"] != "-":
                telp = lama["telepon"]
            if not alamat and lama["alamat"] != "-":
                alamat = lama["alamat"]

        faskes_dict[kdppk] = {
            "kdppk": kdppk,
            "nama_faskes": nama,
            "jenis_faskes": jenis,
            "kelas_rs": kelas_rs,
            "provinsi": provinsi,
            "kota_kabupaten": kota,
            "alamat": alamat if alamat else "-",
            "telepon": telp,
            "latitude": lat,
            "longitude": lon,
            "is_support_bpjs": item.get("is_support_bpjs", True)
        }

    if not found_any_bpjs:
        print("[-] Peringatan: Tidak ada file JSON BPJS yang ditemukan!")

    # ==========================================
    # 1B. PASTIKAN 4 RS VERTIKAL UTAMA MITRA BPJS TERDAFTAR
    # ==========================================
    essential_bpjs_hospitals = [
        {
            "kdppk": "0901R001",
            "nama_faskes": "RSUPN DR. CIPTO MANGUNKUSUMO (RSCM)",
            "jenis_faskes": "Rumah Sakit",
            "kelas_rs": "A",
            "provinsi": "DKI Jakarta",
            "kota_kabupaten": "Jakarta Pusat",
            "alamat": "JL. DIPONEGORO NO. 71, KENARI, SENEN",
            "telepon": "1500135",
            "latitude": -6.1973,
            "longitude": 106.8469,
            "is_support_bpjs": True
        },
        {
            "kdppk": "0901R003",
            "nama_faskes": "RSPAD GATOT SOEBROTO",
            "jenis_faskes": "Rumah Sakit",
            "kelas_rs": "A",
            "provinsi": "DKI Jakarta",
            "kota_kabupaten": "Jakarta Pusat",
            "alamat": "JL. ABDUL RAHMAN SALEH NO. 24, SENEN",
            "telepon": "021-3441008",
            "latitude": -6.1764,
            "longitude": 106.8378,
            "is_support_bpjs": True
        },
        {
            "kdppk": "0905R002",
            "nama_faskes": "RSPI PROF. DR. SULIANTI SAROSO",
            "jenis_faskes": "Rumah Sakit",
            "kelas_rs": "A",
            "provinsi": "DKI Jakarta",
            "kota_kabupaten": "Jakarta Utara",
            "alamat": "JL. BARU SUNTER PERMAI RAYA, PAPANGGO, TANJUNG PRIOK",
            "telepon": "021-6506559",
            "latitude": -6.1306,
            "longitude": 106.8622,
            "is_support_bpjs": True
        },
        {
            "kdppk": "1025R006",
            "nama_faskes": "RSUP DR. SITANALA TANGERANG",
            "jenis_faskes": "Rumah Sakit",
            "kelas_rs": "A",
            "provinsi": "Banten",
            "kota_kabupaten": "Tangerang",
            "alamat": "JL. DR. SITANALA NO. 99, KARANG SARI, NEGLASARI",
            "telepon": "021-5523059",
            "latitude": -6.1643,
            "longitude": 106.6262,
            "is_support_bpjs": True
        }
    ]

    existing_bpjs_refs = [
        {
            "norm_name": normalize_rs_name(v["nama_faskes"]),
            "latitude": v["latitude"],
            "longitude": v["longitude"]
        }
        for v in faskes_dict.values()
    ]

    # kdppk di atas adalah kode asli BPJS. Jika data aslinya sudah terbaca, cukup lengkapi kelasnya.
    for rs in essential_bpjs_hospitals:
        if rs["kdppk"] in faskes_dict:
            asli = faskes_dict[rs["kdppk"]]
            if asli["jenis_faskes"] == "Rumah Sakit" and not asli["kelas_rs"]:
                asli["kelas_rs"] = rs["kelas_rs"]
            continue
        if not is_duplicate_faskes(rs["nama_faskes"], rs["latitude"], rs["longitude"], existing_bpjs_refs):
            faskes_dict[rs["kdppk"]] = rs
            existing_bpjs_refs.append({
                "norm_name": normalize_rs_name(rs["nama_faskes"]),
                "latitude": rs["latitude"],
                "longitude": rs["longitude"]
            })

    bpjs_reference_list = list(existing_bpjs_refs)

    # ==========================================
    # 2. BACA DATA OSM / NON-BPJS (is_support_bpjs = False)
    # ==========================================
    print("Membaca data Non-BPJS (Overpass OSM)...")
    try:
        with open("osm_hospitals_raw.json", "r", encoding="utf-8") as f:
            raw_osm = json.load(f)
            
        elements = raw_osm.get("elements", [])
        for item in elements:
            osm_id = str(item.get("id", ""))
            tags = item.get("tags", {})
            
            nama = tags.get("name", "").strip().upper()
            if not nama:
                continue
            
            unique_id = f"OSM_{osm_id}"
            if unique_id in faskes_dict:
                continue

            raw_lat = item.get("lat", item.get("center", {}).get("lat", ""))
            raw_lon = item.get("lon", item.get("center", {}).get("lon", ""))
            lat, lon = parse_and_fix_coords(raw_lat, raw_lon)

            if not is_plausible_jabodetabek(lat, lon):
                skipped_outside_bbox += 1
                continue

            # Nama generik ("RUMAH SAKIT", "KLINIK", ...) tidak bisa dipakai sebagai data faskes
            if re.sub(r"[^A-Z ]", "", nama).strip() in NAMA_GENERIK:
                skipped_invalid += 1
                continue

            alamat_parts = [tags.get("addr:street", ""), tags.get("addr:housenumber", ""), tags.get("addr:city", "")]
            alamat = ", ".join([p for p in alamat_parts if p]).strip()
            if not alamat or alamat == ",":
                alamat = "-"

            jenis = normalize_jenis_faskes("", nama, is_osm=True, tags=tags)
            if not jenis:
                skipped_invalid += 1
                continue

            # Kota dari koordinat (tetangga terdekat data BPJS); titik di Cianjur/Karawang/Serang otomatis keluar
            kota = penentu.kota_di(lat, lon)
            if kota is None or kota == LUAR_WILAYAH:
                skipped_outside_bbox += 1
                continue
            provinsi = PROVINSI_KOTA[kota]

            if is_duplicate_faskes(nama, lat, lon, bpjs_reference_list):
                skipped_duplicates += 1
                continue

            kelas_rs = ""

            faskes_dict[unique_id] = {
                "kdppk": unique_id,
                "nama_faskes": nama,
                "jenis_faskes": jenis,
                "kelas_rs": kelas_rs,
                "provinsi": provinsi,
                "kota_kabupaten": kota,
                "alamat": alamat,
                "telepon": tags.get("phone", tags.get("contact:phone", "-")),
                "latitude": lat,
                "longitude": lon,
                "is_support_bpjs": False
            }
            bpjs_reference_list.append({
                "norm_name": normalize_rs_name(nama),
                "latitude": lat,
                "longitude": lon
            })
    except FileNotFoundError:
        print("[-] File osm_hospitals_raw.json tidak ditemukan, dilewati.")

    # ==========================================
    # 3. GABUNGKAN & EXPORT
    # ==========================================
    faskes_final = []
    sorted_faskes = sorted(faskes_dict.values(), key=lambda x: x["nama_faskes"])
    
    for index, data in enumerate(sorted_faskes, start=1):
        row = {"id_faskes": index}
        row.update(data)
        row["foto_url"] = f"https://picsum.photos/seed/{index}/400/300.webp"
        faskes_final.append(row)
    
    if not faskes_final:
        print("Gagal mengekstrak data apapun.")
        return

    keys_faskes = faskes_final[0].keys()
    with open("tabel_faskes.csv", "w", newline="", encoding="utf-8") as f:
        writer = csv.DictWriter(f, fieldnames=keys_faskes)
        writer.writeheader()
        writer.writerows(faskes_final)

    rekap_jenis = {}
    for f_row in faskes_final:
        j = f_row["jenis_faskes"]
        rekap_jenis[j] = rekap_jenis.get(j, 0) + 1

    print("\n" + "="*55)
    print("MERGE & FILTER KOORDINAT JABODETABEK BERHASIL!")
    print(f"Faskes Luar Jabodetabek : {skipped_outside_bbox} dibuang")
    print(f"Koordinat rusak/janggal : {skipped_bad_coords} dibuang")
    print(f"Duplikat OSM dibuang    : {skipped_duplicates} faskes")
    print(f"Entitas Non-Medis       : {skipped_invalid} dibuang")
    print(f"Total Faskes Bersih     : {len(faskes_final)} baris")
    print("-" * 55)
    print("Rincian per Jenis Faskes:")
    for k, v in sorted(rekap_jenis.items(), key=lambda x: -x[1]):
        print(f"  - {k:<18} : {v} faskes")
    print("="*55)

if __name__ == "__main__":
    main()