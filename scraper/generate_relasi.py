import csv
import json
import random

def load_json_list(filename):
    try:
        with open(filename, "r", encoding="utf-8") as f:
            return json.load(f)
    except Exception as e:
        print(f"Error baca {filename}: {e}")
        return []

def export_csv(filename, data):
    if not data: return
    with open(filename, "w", newline="", encoding="utf-8") as f:
        writer = csv.DictWriter(f, fieldnames=data[0].keys())
        writer.writeheader()
        writer.writerows(data)
    print(f"[+] {filename} berhasil dibuat! ({len(data)} baris)")

# FUNGSI BARU: Bikin huruf depan kapital, kecuali kata "dan"
def format_judul(teks):
    kata_kata = teks.split()
    hasil = []
    for i, kata in enumerate(kata_kata):
        kata_format = kata.capitalize()
        # Kalau katanya adalah "dan" (dan bukan kata paling pertama), bikin kecil
        if kata_format.lower() == "dan" and i > 0:
            kata_format = "dan"
        hasil.append(kata_format)
    return " ".join(hasil)

def main():
    print("Membaca data master...")
    
    # Pastikan nama filenya udah sesuai sama yang ada di folder lu
    raw_spesialisasi = load_json_list("spesialisasi.json")
    raw_fasilitas = load_json_list("fasilitas.json") # Ganti jadi "fasilitas.json" kalau belum lu rename
    
    # 1. Bikin Tabel Master Spesialisasi & Fasilitas + Diformat biar Rapi
    master_spesialisasi = [{"id_spesialisasi": idx, "nama_spesialisasi": format_judul(item)} for idx, item in enumerate(raw_spesialisasi, start=1)]
    master_fasilitas = [{"id_fasilitas": idx, "nama_fasilitas": format_judul(item)} for idx, item in enumerate(raw_fasilitas, start=1)]
    
    # Baca id_faskes dari tabel_faskes.csv
    id_faskes_list = []
    faskes_jenis = {} 
    try:
        with open("tabel_faskes.csv", "r", encoding="utf-8") as f:
            reader = csv.DictReader(f)
            for row in reader:
                id_faskes_list.append(row["id_faskes"])
                faskes_jenis[row["id_faskes"]] = row["jenis_faskes"]
    except FileNotFoundError:
        print("[-] Error: tabel_faskes.csv tidak ditemukan. Run mapper.py dulu!")
        return

    # 2. Generate Relasi Acak 
    faskes_spesialisasi = []
    faskes_fasilitas = []

    id_sp_list = [sp["id_spesialisasi"] for sp in master_spesialisasi]
    id_fs_list = [fs["id_fasilitas"] for fs in master_fasilitas]

    for id_faskes in id_faskes_list:
        jenis = faskes_jenis[id_faskes].upper()
        
        if "RS" in jenis or "RUMAH SAKIT" in jenis or "KLINIK UTAMA" in jenis:
            jml_sp = random.randint(10, 30)
            jml_fs = random.randint(20, 50)
        else:
            jml_sp = random.randint(1, 5)
            jml_fs = random.randint(3, 10)
            
        # Fail-safe biar gak error "Sample larger than population" lagi
        jml_sp = min(jml_sp, len(id_sp_list))
        jml_fs = min(jml_fs, len(id_fs_list))
            
        sp_terpilih = random.sample(id_sp_list, jml_sp)
        for sp in sp_terpilih:
            faskes_spesialisasi.append({"id_faskes": id_faskes, "id_spesialisasi": sp})
            
        fs_terpilih = random.sample(id_fs_list, jml_fs)
        for fs in fs_terpilih:
            faskes_fasilitas.append({"id_faskes": id_faskes, "id_fasilitas": fs})

    print("\nMembuat file CSV...")
    export_csv("tabel_spesialisasi.csv", master_spesialisasi)
    export_csv("tabel_fasilitas.csv", master_fasilitas)
    export_csv("tabel_faskes_spesialisasi.csv", faskes_spesialisasi)
    export_csv("tabel_faskes_fasilitas.csv", faskes_fasilitas)
    
    print("\n[V] SELESAI! CSV Spesialisasi & Fasilitas sudah rapi (Title Case) dan relasi aman.")

if __name__ == "__main__":
    main()