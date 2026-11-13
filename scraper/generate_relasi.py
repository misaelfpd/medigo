import csv
import json
import os

def load_json_list(filename):
    try:
        with open(filename, "r", encoding="utf-8") as f:
            return json.load(f)
    except Exception as e:
        print(f"Error baca {filename}: {e}")
        return []

def export_csv(filename, data, default_headers=None):
    headers = list(data[0].keys()) if data else (default_headers or [])
    if not headers:
        return
    with open(filename, "w", newline="", encoding="utf-8") as f:
        writer = csv.DictWriter(f, fieldnames=headers)
        writer.writeheader()
        if data:
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

    # 2. Baca Data Asli Relasi dari SEMUA file JSON BPJS yang tersedia
    bpjs_relasi_map = {}
    bpjs_files = ["faskes_raw_playwright.json", "faskes_jabodetabek (1).json", "faskes_jabodetabek.json"]
    
    for bpjs_file in bpjs_files:
        if not os.path.exists(bpjs_file):
            continue
        print(f"Membaca relasi asli dari: {bpjs_file}...")
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
            kd = str(item.get("_kdppk") or item.get("kdppk") or "").strip()
            if kd and ("spesialisasi" in item or "fasilitas_penunjang" in item):
                existing = bpjs_relasi_map.get(kd, {"spesialisasi": [], "fasilitas": []})
                bpjs_relasi_map[kd] = {
                    "spesialisasi": existing["spesialisasi"] + item.get("spesialisasi", []),
                    "fasilitas": existing["fasilitas"] + item.get("fasilitas_penunjang", [])
                }

    # 3. Hubungkan dengan tabel_faskes.csv (Deduplikasi Ketat di Level Pasangan ID)
    faskes_spesialisasi = []
    faskes_fasilitas = []
    
    seen_sp_pairs = set()
    seen_fs_pairs = set()

    with open("tabel_faskes.csv", "r", encoding="utf-8") as f:
        reader = csv.DictReader(f)
        for row in reader:
            id_faskes = int(row["id_faskes"])
            kdppk = row["kdppk"]

            if kdppk in bpjs_relasi_map:
                asli_sp = bpjs_relasi_map[kdppk]["spesialisasi"]
                asli_fs = bpjs_relasi_map[kdppk]["fasilitas"]

                for sp_name in asli_sp:
                    sp_id = sp_lookup.get(sp_name.strip().lower())
                    if sp_id and (id_faskes, sp_id) not in seen_sp_pairs:
                        seen_sp_pairs.add((id_faskes, sp_id))
                        faskes_spesialisasi.append({"id_faskes": id_faskes, "id_spesialisasi": sp_id})

                for fs_name in asli_fs:
                    fs_id = fs_lookup.get(fs_name.strip().lower())
                    if fs_id and (id_faskes, fs_id) not in seen_fs_pairs:
                        seen_fs_pairs.add((id_faskes, fs_id))
                        faskes_fasilitas.append({"id_faskes": id_faskes, "id_fasilitas": fs_id})

    # Urutkan agar rapi di CSV
    faskes_spesialisasi.sort(key=lambda x: (x["id_faskes"], x["id_spesialisasi"]))
    faskes_fasilitas.sort(key=lambda x: (x["id_faskes"], x["id_fasilitas"]))

    print("\nMembuat file CSV...")
    export_csv("tabel_spesialisasi.csv", master_spesialisasi, ["id_spesialisasi", "nama_spesialisasi"])
    export_csv("tabel_fasilitas.csv", master_fasilitas, ["id_fasilitas", "nama_fasilitas"])
    export_csv("tabel_faskes_spesialisasi.csv", faskes_spesialisasi, ["id_faskes", "id_spesialisasi"])
    export_csv("tabel_faskes_fasilitas.csv", faskes_fasilitas, ["id_faskes", "id_fasilitas"])
    print("\n[V] SELESAI! Relasi 100% bebas duplikat ID dan murni dari Aplicares.")

if __name__ == "__main__":
    main()