import json
import csv
import os
import re
import math
from difflib import SequenceMatcher

def haversine_distance(lat1, lon1, lat2, lon2):
    """Menghitung jarak dalam meter antara dua titik koordinat."""
    try:
        lat1, lon1, lat2, lon2 = map(float, [lat1, lon1, lat2, lon2])
    except (ValueError, TypeError):
        return float('inf')
    
    R = 6371000  # Radius bumi dalam meter
    phi1, phi2 = math.radians(lat1), math.radians(lat2)
    dphi = math.radians(lat2 - lat1)
    dlambda = math.radians(lon2 - lon1)
    
    a = math.sin(dphi / 2.0)**2 + math.cos(phi1) * math.cos(phi2) * math.sin(dlambda / 2.0)**2
    return 2 * R * math.atan2(math.sqrt(a), math.sqrt(1 - a))

def normalize_rs_name(name):
    """Membersihkan awalan umum RS/Klinik untuk pencocokan nama yang akurat."""
    n = name.upper()
    # Samakan singkatan RS terkenal
    n = re.sub(r'\bRSCM\b', 'CIPTO MANGUNKUSUMO', n)
    n = re.sub(r'\bRSPAD\b', 'GATOT SOEBROTO', n)
    n = re.sub(r'\bRSPI\b', 'SULIANTI SAROSO', n)
    
    # Hapus kata generik faskes & gelar umum
    generic_words = [
        r'\bRUMAH SAKIT UMUM PUSAT\b', r'\bRUMAH SAKIT UMUM DAERAH\b',
        r'\bRUMAH SAKIT UMUM\b', r'\bRUMAH SAKIT KHUSUS\b', r'\bRUMAH SAKIT\b',
        r'\bRSUPN\b', r'\bRSUP\b', r'\bRSUD\b', r'\bRSU\b', r'\bRSIA\b', 
        r'\bRSK\b', r'\bRSJ\b', r'\bRS\b', r'\bKLINIK PRATAMA\b', 
        r'\bKLINIK UTAMA\b', r'\bKLINIK\b', r'\bPUSKESMAS\b', r'\bAPOTEK\b', 
        r'\bAPOTIK\b', r'\bOPTIK\b', r'\bDR\b', r'\bPROF\b', r'\bH\b', r'\bHJ\b',
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

        # 1. Nama inti sama persis (contoh: "EMC CIBITUNG" == "EMC CIBITUNG")
        if norm_osm == norm_bpjs and (dist < 10000 or dist == float('inf')):
            return True

        # 2. Salah satu nama inti memuat nama lainnya dan cukup dekat (< 2 km)
        if (norm_osm in norm_bpjs or norm_bpjs in norm_osm) and len(min(norm_osm, norm_bpjs, key=len)) >= 5:
            if dist < 2000 or dist == float('inf'):
                return True

        # 3. Jarak sangat dekat (< 500m) dan nama mirip (>= 60%)
        if dist < 500 and sim >= 0.60:
            return True

        # 4. Jarak dalam kota (< 3km) dan nama sangat mirip (>= 82%)
        if dist < 3000 and sim >= 0.82:
            return True

    return False

def normalize_jenis_faskes(jenis_raw, nama_faskes="", is_osm=False, tags=None):
    """Menstandarkan penulisan jenis_faskes dan membuang tempat non-medis."""
    nama_up = nama_faskes.upper()
    jenis_up = (jenis_raw or "").upper().strip()

    # Filter tempat non-medis / tidak relevan (Gym, Sinshe, Salon, dll.)
    blacklist_keywords = [
        "GYM", "FITNESS", "SINSHE", "SHINSHE", "PIJAT", "MASSAGE", 
        "SALON", "SPA", "SKINCARE", "BEAUTY", "PET SHOP", "VETERINARY", "HEWAN"
    ]
    if any(k in nama_up for k in blacklist_keywords):
        return None
    if tags and (tags.get("leisure") or tags.get("sport") or tags.get("shop") in ["beauty", "massage", "herbalist"]):
        return None

    # Koreksi berbasis Nama Faskes terlebih dahulu (mengatasi salah tag di OSM)
    if any(k in nama_up for k in ["APOTEK", "APOTIK", "FARMASI", "KIMIA FARMA", "K-24", "K24", "GUARDIAN", "CENTURY", "WATSONS"]):
        return "Apotek"
    if "OPTIK" in nama_up or "OPTICAL" in nama_up:
        return "Optik"
    if "PUSKESMAS" in nama_up or "PKM " in nama_up:
        return "Puskesmas"
    if "KLINIK UTAMA" in nama_up:
        return "Klinik Utama"
    if "KLINIK" in nama_up or "CLINIC" in nama_up or "BALAI PENGOBATAN" in nama_up or "PRAKTEK DOKTER" in nama_up or "PRAKTIK DOKTER" in nama_up:
        return "Klinik Pratama"

    # Normalisasi penulisan standar dari jenis_raw / tag
    if "RUMAH SAKIT" in jenis_up or jenis_up in ["RS", "RSU", "RSUD", "RSIA", "RSK", "RSUP"]:
        return "Rumah Sakit"
    if "KLINIK UTAMA" in jenis_up:
        return "Klinik Utama"
    if "KLINIK" in jenis_up or "DOKTER" in jenis_up:
        return "Klinik Pratama"
    if "PUSKESMAS" in jenis_up:
        return "Puskesmas"
    if "APOTEK" in jenis_up or "APOTIK" in jenis_up:
        return "Apotek"
    if "OPTIK" in jenis_up:
        return "Optik"

    if is_osm and tags:
        amenity = tags.get("amenity", "").lower()
        healthcare = tags.get("healthcare", "").lower()
        if amenity == "pharmacy" or healthcare == "pharmacy":
            return "Apotek"
        if healthcare == "optometrist":
            return "Optik"
        if amenity == "clinic" or healthcare in ["clinic", "doctor", "centre"]:
            return "Klinik Pratama"
        if amenity == "hospital" or healthcare == "hospital":
            # Hanya akui sebagai Rumah Sakit jika namanya memang mengandung unsur RS/Hospital
            if any(k in nama_up for k in ["RS ", "RS.", "RSU", "RSUD", "RSIA", "RSUP", "RSK", "RSPAD", "RSCM", "RSPI", "RUMAH SAKIT", "HOSPITAL", "MEDICAL CENTER", "MEDICAL CENTRE"]):
                return "Rumah Sakit"
            return "Klinik Pratama"

    return "Klinik Pratama"

def get_kota_provinsi(teks_lokasi, kota_asli="", lat="", lon=""):
    """Menentukan Kota/Kabupaten dan Provinsi secara komprehensif untuk Jabodetabek."""
    if kota_asli and kota_asli != "-":
        kc = kota_asli.upper()
        if "JAKARTA SELATAN" in kc: return "Jakarta Selatan", "DKI Jakarta"
        if "JAKARTA PUSAT" in kc: return "Jakarta Pusat", "DKI Jakarta"
        if "JAKARTA TIMUR" in kc: return "Jakarta Timur", "DKI Jakarta"
        if "JAKARTA BARAT" in kc: return "Jakarta Barat", "DKI Jakarta"
        if "JAKARTA UTARA" in kc: return "Jakarta Utara", "DKI Jakarta"
        if "JAKARTA" in kc: return "Jakarta", "DKI Jakarta"
        if "TANGERANG SELATAN" in kc or "TANGSEL" in kc: return "Tangerang Selatan", "Banten"
        if "TANGERANG" in kc: return "Tangerang", "Banten"
        if "BOGOR" in kc: return "Bogor", "Jawa Barat"
        if "DEPOK" in kc: return "Depok", "Jawa Barat"
        if "BEKASI" in kc: return "Bekasi", "Jawa Barat"

    t = teks_lokasi.upper()
    
    # Daftar kecamatan/area diperluas untuk menyelamatkan RSPAD, RSCM, RSPI Sulianti Saroso, RSUP Sitanala, dll.
    if any(x in t for x in ["JAKARTA PUSAT", "JAKPUS", "MENTENG", "TANAH ABANG", "GAMBIR", "SENEN", "CEMPAKA PUTIH", "KEMAYORAN", "SAWAH BESAR", "JOHAR BARU", "SALEMBA", "DIPONEGORO", "CIKINI", "KRAMAT", "KWITANG", "PEJOMPONGAN", "GATOT SOEBROTO", "RSPAD", "RSCM", "CIPTO MANGUNKUSUMO"]):
        return "Jakarta Pusat", "DKI Jakarta"
    if any(x in t for x in ["JAKARTA UTARA", "JAKUT", "KELAPA GADING", "TANJUNG PRIOK", "CILINCING", "KOJA", "PADEMANGAN", "PENJARINGAN", "SUNTER", "PAPANGGO", "PLUIT", "PIK", "MUARA KARANG", "ANCOL", "SULIANTI SAROSO"]):
        return "Jakarta Utara", "DKI Jakarta"
    if any(x in t for x in ["JAKARTA SELATAN", "JAKSEL", "TEBET", "KEBAYORAN", "PASAR MINGGU", "CILANDAK", "JAGAKARSA", "PESANGGRAHAN", "PANCORAN", "SETIABUDI", "MAMPANG", "FATMAWATI", "KUNINGAN", "SUDIRMAN", "BLOK M", "PONDOK INDAH", "CILANDAK", "KALIBATA"]):
        return "Jakarta Selatan", "DKI Jakarta"
    if any(x in t for x in ["JAKARTA TIMUR", "JAKTIM", "JATINEGARA", "CAKUNG", "MATRAMAN", "PULO GADUNG", "PULOGADUNG", "KRAMAT JATI", "PASAR REBO", "DUREN SAWIT", "MAKASAR", "CIRACAS", "CIPAYUNG", "RAWAMANGUN", "PONDOK BAMBU", "PONDOK KELAPA", "CAWANG", "KLENDER"]):
        return "Jakarta Timur", "DKI Jakarta"
    if any(x in t for x in ["JAKARTA BARAT", "JAKBAR", "GROGOL", "PETAMBURAN", "CENGKARENG", "TAMAN SARI", "TAMBORA", "KEBON JERUK", "PALMERAH", "KALIDERES", "KEMBANGAN", "SLIPI", "TOMANG", "DAAN MOGOT", "MERUYA", "PURI INDAH", "JELAMBAR"]):
        return "Jakarta Barat", "DKI Jakarta"
    if "JAKARTA" in t or "DKI" in t:
        return "Jakarta", "DKI Jakarta"
    if any(x in t for x in ["TANGERANG SELATAN", "TANGSEL", "BINTARO", "CIPUTAT", "PAMULANG", "SERPONG", "BSD", "PONDOK AREN", "SETU", "ALAM SUTERA", "CIATER"]):
        return "Tangerang Selatan", "Banten"
    if any(x in t for x in ["TANGERANG", "CIKUPA", "CURUG", "KARAWACI", "CILEDUG", "JATIUWUNG", "PERIUK", "BENDA", "BATUCEPER", "NEGLASARI", "CIPONDOH", "PINANG", "LARANGAN", "KARANG TENGAH", "CIBODAS", "TIGARAKSA", "BALARAJA", "PASAR KEMIS", "KELAPA DUA", "PAGEDANGAN", "LEGOK", "CISAUK", "SEPATAN", "TELUKNAGA", "KOSAMBI", "SITANALA"]):
        return "Tangerang", "Banten"
    if any(x in t for x in ["BOGOR", "CIBINONG", "CITEUREUP", "SENTUL", "CILEUNGSI", "GUNUNG PUTRI", "CIAWI", "PARUNG", "DRAMAGA", "CIOMAS", "BOJONGGEDE", "TAJUR", "PAJAJARAN", "SUKARAJA", "CISARUA", "LEUWILIANG", "JONGGOL"]):
        return "Bogor", "Jawa Barat"
    if any(x in t for x in ["DEPOK", "CINERE", "CIMANGGIS", "BEJI", "MARGONDA", "SAWANGAN", "PANCORAN MAS", "SUKMAJAYA", "CILODONG", "LIMO", "TAPOS", "BOJONGSARI", "CIPAYUNG DEPOK", "KELAPA DUA DEPOK"]):
        return "Depok", "Jawa Barat"
    if any(x in t for x in ["BEKASI", "CIBITUNG", "CIKARANG", "TAMBUN", "PONDOK GEDE", "JATIASIH", "JATISAMPURNA", "MUSTIKA JAYA", "RAWALUMBU", "BANTARGEBANG", "MEDAN SATRIA", "HARAPAN INDAH", "SUMMARECON BEKASI", "GALAXY", "LIPPO CIKARANG", "JABABEKA", "SETU BEKASI", "BABELAN", "TARUMAJAYA"]):
        return "Bekasi", "Jawa Barat"

    # Fallback Koordinat (Jika alamat tidak memuat nama kecamatan/kota, tapi koordinatnya di Jabodetabek)
    try:
        lt, ln = float(lat), float(lon)
        if -6.85 <= lt <= -5.95 and 106.35 <= ln <= 107.35:
            if -6.38 <= lt <= -6.08 and 106.68 <= ln <= 106.98:
                if lt > -6.16: return "Jakarta Utara", "DKI Jakarta"
                if lt > -6.22 and 106.79 <= ln <= 106.87: return "Jakarta Pusat", "DKI Jakarta"
                if ln < 106.79 and lt > -6.24: return "Jakarta Barat", "DKI Jakarta"
                if ln > 106.86: return "Jakarta Timur", "DKI Jakarta"
                return "Jakarta Selatan", "DKI Jakarta"
            if ln < 106.75:
                return ("Tangerang Selatan", "Banten") if lt < -6.24 else ("Tangerang", "Banten")
            if lt < -6.48:
                return "Bogor", "Jawa Barat"
            if -6.48 <= lt < -6.34 and 106.72 <= ln <= 106.92:
                return "Depok", "Jawa Barat"
            if ln > 106.92:
                return "Bekasi", "Jawa Barat"
    except (ValueError, TypeError):
        pass

    return "-", "-"

def main():
    jabodetabek_cities = [
        "Jakarta Selatan", "Jakarta Pusat", "Jakarta Timur", 
        "Jakarta Barat", "Jakarta Utara", "Jakarta", 
        "Bogor", "Depok", "Tangerang", "Tangerang Selatan", "Bekasi"
    ]

    faskes_dict = {}
    bpjs_reference_list = []

    # ==========================================
    # 1. BACA DATA BPJS (is_support_bpjs = True)
    # ==========================================
    bpjs_files = ["faskes_jabodetabek.json", "faskes_jabodetabek (1).json", "faskes_raw_playwright.json"]
    bpjs_file_used = next((f for f in bpjs_files if os.path.exists(f)), None)

    if bpjs_file_used:
        print(f"Membaca data asli BPJS dari: {bpjs_file_used}...")
        with open(bpjs_file_used, "r", encoding="utf-8") as f:
            raw_bpjs = json.load(f)

        items_bpjs = []
        for el in raw_bpjs:
            if isinstance(el, list):
                items_bpjs.extend(el)
            elif isinstance(el, dict):
                items_bpjs.append(el)

        for item in items_bpjs:
            kdppk = item.get("_kdppk") or item.get("kdppk")
            if kdppk and kdppk not in faskes_dict:
                nama = (item.get("nama_faskes") or item.get("nmppk") or "").strip().upper()
                jenis_raw = (item.get("jenis_faskes") or item.get("nmjnsppk") or "").strip()
                alamat = (item.get("alamat_faskes") or item.get("nmjlnppk") or "").strip()
                kota_asli = item.get("kota_kabupaten", "")
                lat = item.get("latitude", "")
                lon = item.get("longitude", "")

                jenis = normalize_jenis_faskes(jenis_raw, nama, is_osm=False)
                if not jenis:
                    continue

                kota, provinsi = get_kota_provinsi(nama + " " + alamat, kota_asli, lat, lon)
                if kota not in jabodetabek_cities:
                    continue

                # Ambil kelas asli dari BPJS (Tanpa Random)
                kelas_raw = (
                    item.get("kelas_faskes") or 
                    item.get("kelas_rs") or 
                    item.get("klsrsu") or 
                    item.get("kelas") or 
                    ""
                )
                kelas_rs = str(kelas_raw).upper().replace("KELAS", "").strip() if kelas_raw else ""
                if kelas_rs not in ["A", "B", "C", "D"]:
                    kelas_rs = ""

                telp = (item.get("telp_faskes") or item.get("telpppk") or "").strip()
                if "undefined" in telp.lower() or not telp:
                    telp = "-"

                record = {
                    "kdppk": kdppk,
                    "nama_faskes": nama,
                    "jenis_faskes": jenis,
                    "kelas_rs": kelas_rs,
                    "provinsi": provinsi,
                    "kota_kabupaten": kota,
                    "alamat": alamat,
                    "telepon": telp,
                    "latitude": lat,
                    "longitude": lon,
                    "is_support_bpjs": item.get("is_support_bpjs", True)
                }
                faskes_dict[kdppk] = record
                bpjs_reference_list.append({
                    "norm_name": normalize_rs_name(nama),
                    "latitude": lat,
                    "longitude": lon
                })
    else:
        print("[-] Tidak ada file JSON BPJS yang ditemukan!")

    # ==========================================
    # 2. BACA DATA OSM / NON-BPJS (is_support_bpjs = False)
    # ==========================================
    print("Membaca data Non-BPJS (Overpass OSM)...")
    skipped_duplicates = 0
    skipped_invalid = 0

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

            # Validasi dan bersihkan jenis faskes OSM (buang Gym, Sinshe, dll.)
            jenis = normalize_jenis_faskes("", nama, is_osm=True, tags=tags)
            if not jenis:
                skipped_invalid += 1
                continue

            lat = item.get("lat", item.get("center", {}).get("lat", ""))
            lon = item.get("lon", item.get("center", {}).get("lon", ""))

            alamat_parts = [tags.get("addr:street", ""), tags.get("addr:housenumber", ""), tags.get("addr:city", "")]
            alamat = ", ".join([p for p in alamat_parts if p]).strip()
            if not alamat or alamat == ",":
                alamat = "-"

            kota, provinsi = get_kota_provinsi(nama + " " + alamat, tags.get("addr:city", ""), lat, lon)
            if kota not in jabodetabek_cities:
                continue

            # CEK DUPLIKASI DENGAN DATA BPJS (Jarak & Nama)
            if is_duplicate_faskes(nama, lat, lon, bpjs_reference_list):
                skipped_duplicates += 1
                continue

            # Kelas untuk OSM dikosongkan sesuai hasil review (Poin 2)
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
            # Tambahkan juga ke reference list agar sesama titik OSM (node & way RS yang sama) tidak dobel
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

    print("\n" + "="*55)
    print("MERGE & DEDUPLIKASI DATA BPJS + OSM BERHASIL!")
    print(f"Duplikat OSM dibuang   : {skipped_duplicates} faskes")
    print(f"Entitas Non-Medis      : {skipped_invalid} dibuang")
    print(f"Total Faskes Bersih    : {len(faskes_final)} baris")
    print("File output            : tabel_faskes.csv")
    print("="*55)

if __name__ == "__main__":
    main()