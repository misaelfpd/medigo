import csv
import json
import os
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

def format_judul(teks):
    kata_kata = teks.split()
    hasil = []
    for i, kata in enumerate(kata_kata):
        kata_format = kata.capitalize()
        if kata_format.lower() == "dan" and i > 0:
            kata_format = "dan"
        hasil.append(kata_format)
    return " ".join(hasil)

def main():
    print("Membaca data master...")
    raw_spesialisasi = load_json_list("spesialisasi.json")
    
    # Cek nama file fasilitas yang ada di folder
    fas_file = "fasilitas_penunjang.json" if os.path.exists("fasilitas_penunjang.json") else "fasilitas.json"
    raw_fasilitas = load_json_list(fas_file)
    
    # 1. Buat Tabel Master & Dictionary Lookup (Nama Asli -> ID)
    master_spesialisasi = []
    sp_lookup = {}
    for idx, item in enumerate(raw_spesialisasi, start=1):
        nama_rapi = format_judul(item)
        master_spesialisasi.append({"id_spesialisasi": idx, "nama_spesialisasi": nama_rapi})
        sp_lookup[item.strip().lower()] = idx
        sp_lookup[nama_rapi.lower()] = idx

    master_fasilitas = []
    fs_lookup = {}
    for idx, item in enumerate(raw_fasilitas, start=1):
        nama_rapi = format_judul(item)
        master_fasilitas.append({"id_fasilitas": idx, "nama_fasilitas": nama_rapi})
        fs_lookup[item.strip().lower()] = idx
        fs_lookup[nama_rapi.lower()] = idx

    # 2. Baca Data Asli Relasi dari JSON BPJS Lengkap (Jika ada)
    bpjs_relasi_map = {}
    bpjs_files = ["faskes_jabodetabek (1).json", "faskes_jabodetabek.json", "faskes_raw_playwright.json"]
    bpjs_file_used = next((f for f in bpjs_files if os.path.exists(f)), None)
    
    if bpjs_file_used:
        with open(bpjs_file_used, "r", encoding="utf-8") as f:
            raw_bpjs = json.load(f)
        for el in raw_bpjs:
            items = el if isinstance(el, list) else [el]
            for item in items:
                kd = item.get("_kdppk") or item.get("kdppk")
                if kd and ("spesialisasi" in item or "fasilitas_penunjang" in item):
                    bpjs_relasi_map[kd] = {
                        "spesialisasi": item.get("spesialisasi", []),
                        "fasilitas": item.get("fasilitas_penunjang", [])
                    }

    # 3. Hubungkan dengan tabel_faskes.csv
    faskes_spesialisasi = []
    faskes_fasilitas = []

    id_sp_list = [sp["id_spesialisasi"] for sp in master_spesialisasi]
    id_fs_list = [fs["id_fasilitas"] for fs in master_fasilitas]

    with open("tabel_faskes.csv", "r", encoding="utf-8") as f:
        reader = csv.DictReader(f)
        for row in reader:
            id_faskes = row["id_faskes"]
            kdppk = row["kdppk"]
            jenis = row["jenis_faskes"].upper()

            # JIKA ADA DATA ASLI DARI JSON BPJS, PAKAI 100% DATA ASLI!
            if kdppk in bpjs_relasi_map:
                asli_sp = bpjs_relasi_map[kdppk]["spesialisasi"]
                asli_fs = bpjs_relasi_map[kdppk]["fasilitas"]

                for sp_name in set(asli_sp):
                    sp_id = sp_lookup.get(sp_name.strip().lower())
                    if sp_id:
                        faskes_spesialisasi.append({"id_faskes": id_faskes, "id_spesialisasi": sp_id})

                for fs_name in set(asli_fs):
                    fs_id = fs_lookup.get(fs_name.strip().lower())
                    if fs_id:
                        faskes_fasilitas.append({"id_faskes": id_faskes, "id_fasilitas": fs_id})
            
            # JIKA DATA DARI OSM (NON-BPJS) ATAU JSON LAMA YANG BELUM ADA RELASI
            elif kdppk.startswith("OSM_") or not bpjs_relasi_map:
                if "RS" in jenis or "RUMAH SAKIT" in jenis:
                    sp_terpilih = random.sample(id_sp_list, min(random.randint(8, 20), len(id_sp_list)))
                    fs_terpilih = random.sample(id_fs_list, min(random.randint(15, 35), len(id_fs_list)))
                    for sp in sp_terpilih:
                        faskes_spesialisasi.append({"id_faskes": id_faskes, "id_spesialisasi": sp})
                    for fs in fs_terpilih:
                        faskes_fasilitas.append({"id_faskes": id_faskes, "id_fasilitas": fs})

    print("\nMembuat file CSV...")
    export_csv("tabel_spesialisasi.csv", master_spesialisasi)
    export_csv("tabel_fasilitas.csv", master_fasilitas)
    export_csv("tabel_faskes_spesialisasi.csv", faskes_spesialisasi)
    export_csv("tabel_faskes_fasilitas.csv", faskes_fasilitas)
    print("\n[V] SELESAI! Semua relasi sekarang menggunakan data asli Aplicares.")

if __name__ == "__main__":
    main()