import json
import csv
import random

def get_kota_provinsi(teks_lokasi):
    teks_lokasi = teks_lokasi.upper()
    kota, provinsi = "-", "-"
    
    if any(x in teks_lokasi for x in ["JAKARTA SELATAN", "TEBET", "KEBAYORAN", "PASAR MINGGU", "CILANDAK", "JAGAKARSA", "PESANGGRAHAN", "PANCORAN", "SETIABUDI"]):
        kota, provinsi = "Jakarta Selatan", "DKI Jakarta"
    elif any(x in teks_lokasi for x in ["JAKARTA PUSAT", "MENTENG", "TANAH ABANG", "GAMBIR", "SENEN", "CEMPAKA PUTIH", "KEMAYORAN"]):
        kota, provinsi = "Jakarta Pusat", "DKI Jakarta"
    elif any(x in teks_lokasi for x in ["JAKARTA TIMUR", "JATINEGARA", "CAKUNG", "MATRAMAN", "PULO GADUNG", "KRAMAT JATI", "PASAR REBO"]):
        kota, provinsi = "Jakarta Timur", "DKI Jakarta"
    elif any(x in teks_lokasi for x in ["JAKARTA BARAT", "GROGOL", "CENGKARENG", "TAMAN SARI", "TAMBORA", "KEBON JERUK", "PALMERAH"]):
        kota, provinsi = "Jakarta Barat", "DKI Jakarta"
    elif any(x in teks_lokasi for x in ["JAKARTA UTARA", "KELAPA GADING", "TANJUNG PRIOK", "CILINCING", "KOJA", "PADEMANGAN", "PENJARINGAN"]):
        kota, provinsi = "Jakarta Utara", "DKI Jakarta"
    elif "JAKARTA" in teks_lokasi:
        kota, provinsi = "Jakarta", "DKI Jakarta"
    elif any(x in teks_lokasi for x in ["BOGOR", "CIBINONG", "CITEUREUP", "SENTUL"]):
        kota, provinsi = "Bogor", "Jawa Barat"
    elif any(x in teks_lokasi for x in ["DEPOK", "CINERE", "CIMANGGIS", "BEJI", "MARGONDA"]):
        kota, provinsi = "Depok", "Jawa Barat"
    elif any(x in teks_lokasi for x in ["TANGERANG", "BINTARO", "CIPUTAT", "PAMULANG", "SERPONG"]):
        provinsi = "Banten"
        kota = "Tangerang Selatan" if any(y in teks_lokasi for y in ["SELATAN", "TANGSEL", "CIPUTAT", "PAMULANG", "BINTARO"]) else "Tangerang"
    elif any(x in teks_lokasi for x in ["BEKASI", "CIBITUNG", "CIKARANG", "TAMBUN"]):
        kota, provinsi = "Bekasi", "Jawa Barat"
    
    return kota, provinsi

def main():
    jabodetabek_cities = [
        "Jakarta Selatan", "Jakarta Pusat", "Jakarta Timur", 
        "Jakarta Barat", "Jakarta Utara", "Jakarta", 
        "Bogor", "Depok", "Tangerang", "Tangerang Selatan", "Bekasi"
    ]

    faskes_dict = {}

    # ==========================================
    # 1. BACA DATA BPJS (is_support_bpjs = True)
    # ==========================================
    print("Membaca data BPJS...")
    try:
        with open("faskes_raw_playwright.json", "r", encoding="utf-8") as f:
            raw_bpjs = json.load(f)
            
        for paket in raw_bpjs:
            if isinstance(paket, list):
                for item in paket:
                    kdppk = item.get("kdppk")
                    if kdppk and kdppk not in faskes_dict:
                        nama = item.get("nmppk", "").strip().upper()
                        jenis = item.get("nmjnsppk", "").strip()
                        alamat = item.get("nmjlnppk", "").strip()
                        
                        kota, provinsi = get_kota_provinsi(nama + " " + alamat)
                        if kota not in jabodetabek_cities: continue
                        
                        kelas_rs = ""
                        if "RS" in jenis.upper() or "RUMAH SAKIT" in jenis.upper() or "KLINIK" in jenis.upper():
                            kelas_rs = random.choice(["A", "B", "C", "D"])

                        faskes_dict[kdppk] = {
                            "kdppk": kdppk,
                            "nama_faskes": nama,
                            "jenis_faskes": jenis,
                            "kelas_rs": kelas_rs,
                            "provinsi": provinsi,
                            "kota_kabupaten": kota,
                            "alamat": alamat,
                            "telepon": item.get("telpppk", "").strip(),
                            "latitude": item.get("latitude", ""),
                            "longitude": item.get("longitude", ""),
                            "is_support_bpjs": True
                        }
    except FileNotFoundError:
        print("[-] File BPJS tidak ditemukan, dilewati.")

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
            if not nama: continue # Kalo gaada nama, skip aja
            
            unique_id = f"OSM_{osm_id}" # Bikin prefix biar ga bentrok sama kdppk BPJS
            
            if unique_id not in faskes_dict:
                jenis = "Rumah Sakit" if "hospital" in tags.get("amenity", "") or "hospital" in tags.get("healthcare", "") else "Klinik Pratama"
                
                # Rakit alamat dari tag OSM
                alamat_parts = [tags.get("addr:street", ""), tags.get("addr:housenumber", ""), tags.get("addr:city", "")]
                alamat = ", ".join([p for p in alamat_parts if p]).strip()
                if not alamat or alamat == ",": alamat = "-"
                
                kota, provinsi = get_kota_provinsi(nama + " " + alamat)
                if kota not in jabodetabek_cities: continue
                
                kelas_rs = ""
                if "RS" in jenis.upper() or "RUMAH SAKIT" in jenis.upper() or "KLINIK" in jenis.upper():
                    kelas_rs = random.choice(["A", "B", "C", "D"])

                # Ambil koordinat (biasanya lat/lon ada di luar tags, atau di dalam dict center untuk OSM Ways)
                lat = item.get("lat", item.get("center", {}).get("lat", ""))
                lon = item.get("lon", item.get("center", {}).get("lon", ""))

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
    except FileNotFoundError:
        print("[-] File osm_hospitals_raw.json tidak ditemukan, dilewati.")

    # ==========================================
    # 3. GABUNGKAN & EXPORT
    # ==========================================
    faskes_final = []
    # Sortir berdasarkan nama biar rapi pas di-seeding
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

    print("\n" + "="*50)
    print("MERGE DATA BPJS & NON-BPJS BERHASIL!")
    print(f"Total Faskes Terkumpul : {len(faskes_final)} baris")
    print("File output            : tabel_faskes.csv")
    print("="*50)

if __name__ == "__main__":
    main()