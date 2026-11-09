import json
import csv
import random

def main():
    print("Membaca file faskes_raw_playwright.json...")
    try:
        with open("faskes_raw_playwright.json", "r", encoding="utf-8") as f:
            raw_data = json.load(f)
    except FileNotFoundError:
        print("File JSON faskes tidak ditemukan!")
        return

    faskes_dict = {}
    jabodetabek_cities = [
        "Jakarta Selatan", "Jakarta Pusat", "Jakarta Timur", 
        "Jakarta Barat", "Jakarta Utara", "Jakarta", 
        "Bogor", "Depok", "Tangerang", "Tangerang Selatan", "Bekasi"
    ]

    for paket in raw_data:
        if isinstance(paket, list):
            for item in paket:
                kdppk = item.get("kdppk")
                if kdppk and kdppk not in faskes_dict:
                    nama = item.get("nmppk", "").strip()
                    jenis = item.get("nmjnsppk", "").strip()
                    alamat = item.get("nmjlnppk", "").strip()
                    
                    # Logic Kota & Provinsi
                    teks_lokasi = (nama + " " + alamat).upper()
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
                    
                    if kota not in jabodetabek_cities:
                        continue
                    
                    kelas_rs = ""
                    if "RS" in jenis.upper() or "RUMAH SAKIT" in jenis.upper() or "KLINIK UTAMA" in jenis.upper():
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
                        "longitude": item.get("longitude", "")
                    }

    faskes_final = []
    for index, (kode_bpjs, data) in enumerate(faskes_dict.items(), start=1):
        row = {"id_faskes": index}
        row.update(data)
        faskes_final.append(row)
    
    with open("tabel_faskes.csv", "w", newline="", encoding="utf-8") as f:
        writer = csv.DictWriter(f, fieldnames=faskes_final[0].keys())
        writer.writeheader()
        writer.writerows(faskes_final)

    print(f"[+] tabel_faskes.csv BERHASIL DIBUAT! (Total: {len(faskes_final)} faskes)")

if __name__ == "__main__":
    main()