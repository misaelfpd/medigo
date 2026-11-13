import json
import csv
import os
import re
import math
from difflib import SequenceMatcher

def parse_and_fix_coords(lat_raw, lon_raw):
    """Membaca koordinat dan memperbaiki kesalahan tanda positif pada latitude Jabodetabek."""
    try:
        lat = float(str(lat_raw).replace(",", ".").strip())
        lon = float(str(lon_raw).replace(",", ".").strip())
        
        # Koreksi jika latitude tertulis positif di rentang Jabodetabek (misal 6.340966 -> -6.340966)
        if 5.85 <= lat <= 6.95 and 106.30 <= lon <= 107.45:
            lat = -lat
            
        return lat, lon
    except (ValueError, TypeError):
        return None, None

def is_within_jabodetabek_bbox(lat, lon):
    """Memastikan titik koordinat berada di dalam kotak wilayah Jabodetabek."""
    if lat is None or lon is None:
        return False
    return (-6.95 <= lat <= -5.85) and (106.30 <= lon <= 107.45)

def is_explicit_outside_city(teks):
    """Membuang faskes yang secara eksplisit menyebut kota luar Jabodetabek (meski ada kata Kuningan/Ciawi/Cikini)."""
    t = teks.upper()
    outside_cities = [
        r'\bINDRAMAYU\b', r'\bMAJALENGKA\b', r'\bTASIKMALAYA\b', r'\bCIREBON\b',
        r'\bSUBANG\b', r'\bPURWAKARTA\b', r'\bKARAWANG\b', r'\bSUKABUMI\b',
        r'\bCIANJUR\b', r'\bBANDUNG\b', r'\bGARUT\b', r'\bSUMEDANG\b',
        r'\bCIAMIS\b', r'\bBANJAR\b', r'\bPANGANDARAN\b', r'\bSERANG\b',
        r'\bCILEGON\b', r'\bPANDEGLANG\b', r'\bLEBAK\b', r'\bRANGKASBITUNG\b',
        r'\bKAB\.?\s*KUNINGAN\b', r'\bKABUPATEN\s+KUNINGAN\b', r'\bKOTA\s+KUNINGAN\b'
    ]
    for pattern in outside_cities:
        if re.search(pattern, t):
            return True
    # Tangkap "KIMIA FARMA KUNINGAN" (nama cabang kota Kuningan Jabar, bukan Jl. Kuningan Jaksel)
    if "KIMIA FARMA KUNINGAN" in t and "JAKARTA" not in t and "SETIABUDI" not in t:
        return True
    return False

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

def classify_jakarta_by_coords(lat, lon):
    """Memetakan koordinat di DKI Jakarta ke 5 Kota Administrasi terdekat."""
    try:
        lt, ln = float(lat), float(lon)
        centers = {
            "Jakarta Pusat": (-6.1805, 106.8284),
            "Jakarta Utara": (-6.1214, 106.9004),
            "Jakarta Barat": (-6.1674, 106.7637),
            "Jakarta Selatan": (-6.2615, 106.8106),
            "Jakarta Timur": (-6.2250, 106.9004),
        }
        best_city = min(centers.keys(), key=lambda c: (lt - centers[c][0])**2 + (ln - centers[c][1])**2)
        return best_city, "DKI Jakarta"
    except (ValueError, TypeError):
        return "Jakarta Pusat", "DKI Jakarta"

def get_kota_provinsi(teks_lokasi, kota_asli="", lat=None, lon=None):
    """Menentukan Kota/Kabupaten secara spesifik (Wajib lolos filter koordinat Jabodetabek terlebih dahulu)."""
    if kota_asli and kota_asli != "-":
        kc = kota_asli.upper()
        if "JAKARTA SELATAN" in kc or "JAKSEL" in kc: return "Jakarta Selatan", "DKI Jakarta"
        if "JAKARTA PUSAT" in kc or "JAKPUS" in kc: return "Jakarta Pusat", "DKI Jakarta"
        if "JAKARTA TIMUR" in kc or "JAKTIM" in kc: return "Jakarta Timur", "DKI Jakarta"
        if "JAKARTA BARAT" in kc or "JAKBAR" in kc: return "Jakarta Barat", "DKI Jakarta"
        if "JAKARTA UTARA" in kc or "JAKUT" in kc or "KEPULAUAN SERIBU" in kc: return "Jakarta Utara", "DKI Jakarta"
        if "TANGERANG SELATAN" in kc or "TANGSEL" in kc: return "Tangerang Selatan", "Banten"
        if "TANGERANG" in kc: return "Tangerang", "Banten"
        if "BOGOR" in kc: return "Bogor", "Jawa Barat"
        if "DEPOK" in kc: return "Depok", "Jawa Barat"
        if "BEKASI" in kc: return "Bekasi", "Jawa Barat"

    t = teks_lokasi.upper()
    
    if any(x in t for x in ["JAKARTA PUSAT", "JAKPUS", "MENTENG", "TANAH ABANG", "GAMBIR", "SENEN", "CEMPAKA PUTIH", "KEMAYORAN", "SAWAH BESAR", "JOHAR BARU", "SALEMBA", "DIPONEGORO", "CIKINI", "KRAMAT", "KWITANG", "PEJOMPONGAN", "GATOT SOEBROTO", "GATOT SUBROTO", "RSPAD", "RSCM", "CIPTO MANGUNKUSUMO", "BENDUNGAN HILIR", "PETOJO", "CIDENG", "KARTINI", "GUNUNG SAHARI", "PASAR BARU"]):
        return "Jakarta Pusat", "DKI Jakarta"
    if any(x in t for x in ["JAKARTA UTARA", "JAKUT", "KELAPA GADING", "TANJUNG PRIOK", "CILINCING", "KOJA", "PADEMANGAN", "PENJARINGAN", "SUNTER", "PAPANGGO", "PLUIT", "PIK", "MUARA KARANG", "ANCOL", "SULIANTI SAROSO", "KAPUK MUARA", "PEJAGALAN", "SEMPER", "ROROTAN", "MARUNDA", "WARAKAS", "KEBON BAWANG", "PEGANGSAAN DUA"]):
        return "Jakarta Utara", "DKI Jakarta"
    if any(x in t for x in ["JAKARTA SELATAN", "JAKSEL", "TEBET", "KEBAYORAN", "PASAR MINGGU", "CILANDAK", "JAGAKARSA", "PESANGGRAHAN", "PANCORAN", "SETIABUDI", "MAMPANG", "FATMAWATI", "KUNINGAN", "SUDIRMAN", "BLOK M", "PONDOK INDAH", "KALIBATA", "PEJATEN", "RAGUNAN", "LENTENG AGUNG", " CIGANJUR", "CIPEDAK", " BINTARO JAYA", "ULUJAMI", "PETUKANGAN", "CIPULIR", "PONDOK PINANG", "LEBAK BULUS", "GANDARIA", "CIPETE", "MELAWAI", "SENAYAN", "KARET SEMANGGI", "MANGGARAI", "BUKIT DURI", "KEBON BARU", "DUREN TIGA", "PENGADEGAN", "BANGKA", "KEMANG"]):
        return "Jakarta Selatan", "DKI Jakarta"
    if any(x in t for x in ["JAKARTA TIMUR", "JAKTIM", "JATINEGARA", "CAKUNG", "MATRAMAN", "PULO GADUNG", "PULOGADUNG", "KRAMAT JATI", "PASAR REBO", "DUREN SAWIT", "MAKASAR", "CIRACAS", "CIPAYUNG", "RAWAMANGUN", "PONDOK BAMBU", "PONDOK KELAPA", "CAWANG", "KLENDER", "CIPINANG", "KAYU PUTIH", "PISANGAN", "UTAN KAYU", "BIDARA CINA", "KAMPUNG MELAYU", "CILILITAN", "CONDET", "HALIM", "PINANG RANTI", "BAMBU APUS", "LUBANG BUAYA", "CEGER", "CIBUBUR", "PEKAYON", "KALISARI", "CIJANTUNG", "PENGGILINGAN", "PULO GEBANG", "UJUNG MENTENG"]):
        return "Jakarta Timur", "DKI Jakarta"
    if any(x in t for x in ["JAKARTA BARAT", "JAKBAR", "GROGOL", "PETAMBURAN", "CENGKARENG", "TAMAN SARI", "TAMBORA", "KEBON JERUK", "PALMERAH", "KALIDERES", "KEMBANGAN", "SLIPI", "TOMANG", "DAAN MOGOT", "MERUYA", "PURI INDAH", "JELAMBAR", "TANJUNG DUREN", "WIJAYA KUSUMA", "KAPUK", "RAWA BUAYA", "DURI KOSAMBI", "SEMANAN", "PEGADUNGAN", "TEGAL ALUR", "KAMAL", "KEDOYA", "SUKABUMI UTARA", "SUKABUMI SELATAN", "KELAPA DUA JAKBAR", "KOTA BAMBU", "JATI PULO", "GLODOK", "MANGGA BESAR", "KRUKUT", "JEMBATAN LIMA", "ANGKE", "SRENGSENG", "JOGLO"]):
        return "Jakarta Barat", "DKI Jakarta"
    if any(x in t for x in ["TANGERANG SELATAN", "TANGSEL", "BINTARO", "CIPUTAT", "PAMULANG", "SERPONG", "BSD", "PONDOK AREN", "SETU", "ALAM SUTERA", "CIATER", "JOMBANG", "SAWAH BARU", "PONDOK CABE", "BAMBU APUS TANGSEL", "BENDA BARU", "LENGKONG", "PAKU JAYA", "PONDOK KACANG", "PARIGI", "JURANG MANGU"]):
        return "Tangerang Selatan", "Banten"
    if any(x in t for x in ["TANGERANG", "CIKUPA", "CURUG", "KARAWACI", "CILEDUG", "JATIUWUNG", "PERIUK", "BENDA", "BATUCEPER", "NEGLASARI", "CIPONDOH", "PINANG", "LARANGAN", "KARANG TENGAH", "CIBODAS", "TIGARAKSA", "BALARAJA", "PASAR KEMIS", "KELAPA DUA", "PAGEDANGAN", "LEGOK", "CISAUK", "SEPATAN", "TELUKNAGA", "KOSAMBI", "SITANALA", "MAUK", "RAJEG", "PAKUHAJI", " SINDANG JAYA", "PANONGAN", "JAMBE", "CISOKA", "SOLEAR", "JAYANTI", "SUKAMULYA", "KRESEK", "GUNUNG KALER", "MEKAR BARU", "KEMIRI", "KRONJO"]):
        return "Tangerang", "Banten"
    if any(x in t for x in ["BOGOR", "CIBINONG", "CITEUREUP", "SENTUL", "CILEUNGSI", "GUNUNG PUTRI", "CIAWI", "PARUNG", "DRAMAGA", "CIOMAS", "BOJONGGEDE", "TAJUR", "PAJAJARAN", "SUKARAJA", "CISARUA", "LEUWILIANG", "JONGGOL", "CIBUNGBULANG", "CIAMPEA", "KEMANG BOGOR", "RANCA BUNGUR", "TAJURHALANG", "SUKMAJAYA", "TANAH SAREAL", "BOGOR UTARA", "BOGOR SELATAN", "BOGOR TIMUR", "BOGOR BARAT", "BOGOR TENAH", "BABAKAN MADANG", "KLAPANUNGGAL", "CARIU", "TANJUNGSARI", "SUKAMAKMUR", "MEGAMENDUNG", "CARINGIN", "CIJERUK", "CIGOMBONG", "TAMANSARI BOGOR", "RUMPIN", "GUNUNG SINDUR", "CISEENG", "TENJO", "PARUNG PANJANG", "JASINGA", "NANGGUNG", "SUKAJAYA", "CIGUDEG", "LEUWISADENG", "PAMIJAHAN", "TENJOLAYA"]):
        return "Bogor", "Jawa Barat"
    if any(x in t for x in ["DEPOK", "CINERE", "CIMANGGIS", "BEJI", "MARGONDA", "SAWANGAN", "PANCORAN MAS", "SUKMAJAYA", "CILODONG", "LIMO", "TAPOS", "BOJONGSARI", "CIPAYUNG DEPOK", "KELAPA DUA DEPOK", "TUGU", "MEKARJAYA", "ABADI JAYA", "BAKTI JAYA", "CISALAK", "HARJAMUKTI", "SUKATANI", "CILANGKAP", "JATIJAJAR", "SUKAMAJU", "KALIMULYA", "DEPOK JAYA", "MAMPANG DEPOK", "MERUYUNG", "GROGOL DEPOK", "KRUKUT DEPOK", "PENGASINAN", "BEDAHAN", "PASIR PUTIH", "PONDOK PETIR", "SERUA", "CURUG DEPOK"]):
        return "Depok", "Jawa Barat"
    if any(x in t for x in ["BEKASI", "CIBITUNG", "CIKARANG", "TAMBUN", "PONDOK GEDE", "JATIASIH", "JATISAMPURNA", "MUSTIKA JAYA", "RAWALUMBU", "BANTARGEBANG", "MEDAN SATRIA", "HARAPAN INDAH", "SUMMARECON BEKASI", "GALAXY", "LIPPO CIKARANG", "JABABEKA", "SETU BEKASI", "BABELAN", "TARUMAJAYA", "PONDOK MELATI", "BEKASI BARAT", "BEKASI TIMUR", "BEKASI UTARA", "BEKASI SELATAN", "KRANJI", " BINTARA", "JAKASAMPURNA", "KAYURINGIN", "MARGAJAYA", "PEKAYON JAYA", "JATIBENING", "JATIMAKMUR", "JATIWARINGIN", "JATIRAHAYU", "JATIMEKAR", "JATIRASA", "PENGASINAN BEKASI", "BOJONG RAWALUMBU", "SEPANJANG JAYA", "PEDURENAN", "CIMUNING", "MUSTIKASARI", "PEJUANG", "HARAPAN JAYA", "KALIABANG", "TELUK PUCUNG", "MARGA MULYA", "SUKAWANGI", "TAMBELANG", "SUKATANI BEKASI", "KARANGBAHAGIA", "PEBAYURAN", "KEDUNGWARINGIN", "CABANGBUNGIN", "MUARAGEMBONG", "SERANG BARU", "CIBARUSAH", "BOJONGMANGU"]):
        return "Bekasi", "Jawa Barat"

    # Fallback Koordinat (Memastikan seluruh titik di dalam kotak Jabodetabek mendapat kota yang akurat)
    try:
        lt, ln = float(lat), float(lon)
        if -6.37 <= lt <= -6.08 and 106.68 <= ln <= 106.98:
            return classify_jakarta_by_coords(lt, ln)
        if ln < 106.75:
            return ("Tangerang Selatan", "Banten") if lt < -6.24 else ("Tangerang", "Banten")
        if lt < -6.48:
            return "Bogor", "Jawa Barat"
        if -6.48 <= lt < -6.34 and 106.72 <= ln <= 106.92:
            return "Depok", "Jawa Barat"
        if ln > 106.92:
            return "Bekasi", "Jawa Barat"
        return classify_jakarta_by_coords(lt, ln)
    except (ValueError, TypeError):
        pass

    if "JAKARTA" in t or "DKI" in t:
        return classify_jakarta_by_coords(lat, lon)

    return "-", "-"

def main():
    jabodetabek_cities = [
        "Jakarta Selatan", "Jakarta Pusat", "Jakarta Timur", 
        "Jakarta Barat", "Jakarta Utara", 
        "Bogor", "Depok", "Tangerang", "Tangerang Selatan", "Bekasi"
    ]

    faskes_dict = {}
    bpjs_reference_list = []
    skipped_outside_bbox = 0

    # ==========================================
    # 1. BACA & GABUNGKAN SEMUA FILE JSON BPJS
    # ==========================================
    bpjs_files = ["faskes_raw_playwright.json", "faskes_jabodetabek (1).json", "faskes_jabodetabek.json"]
    found_any_bpjs = False

    for bpjs_file in bpjs_files:
        if not os.path.exists(bpjs_file):
            continue
        found_any_bpjs = True
        print(f"Membaca & menggabungkan data BPJS dari: {bpjs_file}...")
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

        for item in items_bpjs:
            if not isinstance(item, dict):
                continue
            kdppk = str(item.get("_kdppk") or item.get("kdppk") or "").strip()
            if not kdppk:
                continue

            nama = (item.get("nama_faskes") or item.get("nmppk") or "").strip().upper()
            jenis_raw = (item.get("jenis_faskes") or item.get("nmjnsppk") or "").strip()
            alamat = (item.get("alamat_faskes") or item.get("nmjlnppk") or "").strip()
            kota_asli = item.get("kota_kabupaten", "")

            # 1. Perbaiki tanda koordinat (misal 6.340966 -> -6.340966) & saring kotak Jabodetabek
            lat, lon = parse_and_fix_coords(item.get("latitude", ""), item.get("longitude", ""))
            if not is_within_jabodetabek_bbox(lat, lon):
                skipped_outside_bbox += 1
                continue

            # 2. Saring faskes yang namanya/alamatnya eksplisit kota luar Jabodetabek (Indramayu, Serang, Tasikmalaya, dll.)
            if is_explicit_outside_city(nama + " " + alamat + " " + str(kota_asli)):
                skipped_outside_bbox += 1
                continue

            jenis = normalize_jenis_faskes(jenis_raw, nama, is_osm=False)
            if not jenis:
                continue

            kota, provinsi = get_kota_provinsi(nama + " " + alamat, kota_asli, lat, lon)
            if kota not in jabodetabek_cities:
                skipped_outside_bbox += 1
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
                if not kelas_rs and faskes_dict[kdppk]["kelas_rs"] and jenis == "Rumah Sakit":
                    kelas_rs = faskes_dict[kdppk]["kelas_rs"]

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
            "kdppk": "0111R001",
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
            "kdppk": "0111R002",
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
            "kdppk": "0112R002",
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
            "kdppk": "0225R003",
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

    for rs in essential_bpjs_hospitals:
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

            raw_lat = item.get("lat", item.get("center", {}).get("lat", ""))
            raw_lon = item.get("lon", item.get("center", {}).get("lon", ""))
            lat, lon = parse_and_fix_coords(raw_lat, raw_lon)

            # Saring ketat dengan Bounding Box Jabodetabek
            if not is_within_jabodetabek_bbox(lat, lon):
                skipped_outside_bbox += 1
                continue

            alamat_parts = [tags.get("addr:street", ""), tags.get("addr:housenumber", ""), tags.get("addr:city", "")]
            alamat = ", ".join([p for p in alamat_parts if p]).strip()
            if not alamat or alamat == ",":
                alamat = "-"

            if is_explicit_outside_city(nama + " " + alamat + " " + tags.get("addr:city", "")):
                skipped_outside_bbox += 1
                continue

            jenis = normalize_jenis_faskes("", nama, is_osm=True, tags=tags)
            if not jenis:
                skipped_invalid += 1
                continue

            kota, provinsi = get_kota_provinsi(nama + " " + alamat, tags.get("addr:city", ""), lat, lon)
            if kota not in jabodetabek_cities:
                skipped_outside_bbox += 1
                continue

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