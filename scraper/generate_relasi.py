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

def main():
    print("Membaca data master...")
    
    # Baca file dari Prima
    raw_spesialisasi = load_json_list("spesialisasi.json")
    raw_fasilitas = load_json_list("fasilitas.json")
    
    # 1. Bikin Tabel Master Spesialisasi & Fasilitas dengan ID urut
    master_spesialisasi = [{"id_spesialisasi": idx, "nama_spesialisasi": item} for idx, item in enumerate(raw_spesialisasi, start=1)]
    master_fasilitas = [{"id_fasilitas": idx, "nama_fasilitas": item} for idx, item in enumerate(raw_fasilitas, start=1)]
    
    # Baca id_faskes dari tabel_faskes.csv
    id_faskes_list = []
    faskes_jenis = {} # Simpan jenis buat filter dummy data
    try:
        with open("tabel_faskes.csv", "r", encoding="utf-8") as f:
            reader = csv.DictReader(f)
            for row in reader:
                id_faskes_list.append(row["id_faskes"])
                faskes_jenis[row["id_faskes"]] = row["jenis_faskes"]
    except FileNotFoundError:
        print("[-] Error: tabel_faskes.csv tidak ditemukan. Run mapper.py dulu!")
        return

    # 2. Generate Relasi Acak (Lebih cerdas, sesuai jenis faskes)
    faskes_spesialisasi = []
    faskes_fasilitas = []

    id_sp_list = [sp["id_spesialisasi"] for sp in master_spesialisasi]
    id_fs_list = [fs["id_fasilitas"] for fs in master_fasilitas]

    for id_faskes in id_faskes_list:
        jenis = faskes_jenis[id_faskes].upper()
        
        # RS dapat lebih banyak fasilitas/spesialisasi dibanding klinik/apotek
        if "RS" in jenis or "RUMAH SAKIT" in jenis:
            jml_sp = random.randint(10, 30)
            jml_fs = random.randint(20, 50)
        else:
            jml_sp = random.randint(1, 5)
            jml_fs = random.randint(3, 10)
            
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
    
    print("\n[V] SELESAI! Semua CSV relasi siap di-seeding.")

if __name__ == "__main__":
    main()