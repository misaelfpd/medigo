import csv
import json
from pathlib import Path
import random

# Pemetaan 4 digit awal kode faskes BPJS (kdppk) ke kota/kabupaten & provinsi Jabodetabek.
# Prefix kdppk merepresentasikan Kantor Cabang / Dati-2 BPJS Kesehatan secara pasti,
# sehingga menghindari faskes hilang (karena alamat tidak menyebut nama kota) maupun
# salah deteksi (misal "JL. RAYA SERANG-JAKARTA" atau "CIMENTENG" terbaca Jakarta).
KDPPK_WILAYAH_MAP = {
    # DKI Jakarta
    "0112": ("Jakarta Selatan", "DKI Jakarta"),
    "0902": ("Jakarta Selatan", "DKI Jakarta"),
    "0113": ("Jakarta Timur", "DKI Jakarta"),
    "0903": ("Jakarta Timur", "DKI Jakarta"),
    "0114": ("Jakarta Pusat", "DKI Jakarta"),
    "0901": ("Jakarta Pusat", "DKI Jakarta"),
    "0115": ("Jakarta Barat", "DKI Jakarta"),
    "0904": ("Jakarta Barat", "DKI Jakarta"),
    "0116": ("Jakarta Utara", "DKI Jakarta"),
    "0905": ("Jakarta Utara", "DKI Jakarta"),
    "0483": ("Kepulauan Seribu", "DKI Jakarta"),
    # Bogor (Kota & Kabupaten)
    "0117": ("Bogor", "Jawa Barat"),
    "1004": ("Bogor", "Jawa Barat"),
    "0133": ("Bogor", "Jawa Barat"),
    "1003": ("Bogor", "Jawa Barat"),
    # Depok
    "0138": ("Depok", "Jawa Barat"),
    "1027": ("Depok", "Jawa Barat"),
    # Bekasi (Kota & Kabupaten)
    "0132": ("Bekasi", "Jawa Barat"),
    "1014": ("Bekasi", "Jawa Barat"),
    "0137": ("Bekasi", "Jawa Barat"),
    "1026": ("Bekasi", "Jawa Barat"),
    # Tangerang & Tangerang Selatan
    "0221": ("Tangerang", "Banten"),
    "1005": ("Tangerang", "Banten"),
    "0223": ("Tangerang", "Banten"),
    "1025": ("Tangerang", "Banten"),
    "0496": ("Tangerang Selatan", "Banten"),
}


def extract_faskes_items(node):
    """Kumpulkan seluruh objek faskes (dict yang memiliki kdppk) meski terbungkus dict API."""
    if isinstance(node, list):
        for item in node:
            yield from extract_faskes_items(item)
    elif isinstance(node, dict):
        if "kdppk" in node and "nmppk" in node:
            yield node
        else:
            for value in node.values():
                if isinstance(value, (list, dict)):
                    yield from extract_faskes_items(value)


def resolve_wilayah(kdppk):
    """Tentukan (kota_kabupaten, provinsi) dari 4 digit prefix kdppk Jabodetabek."""
    return KDPPK_WILAYAH_MAP.get(str(kdppk)[:4])


def main():
    base_dir = Path(__file__).resolve().parent
    raw_path = base_dir / "faskes_raw_playwright.json"
    out_path = base_dir / "tabel_faskes.csv"

    print(f"Membaca file {raw_path.name}...")
    try:
        with raw_path.open("r", encoding="utf-8") as f:
            raw_data = json.load(f)
    except FileNotFoundError:
        print(f"File {raw_path} tidak ditemukan!")
        return

    rng = random.Random(42)
    faskes_dict = {}

    for item in extract_faskes_items(raw_data):
        kdppk = str(item.get("kdppk", "")).strip()
        if not kdppk or kdppk in faskes_dict:
            continue

        wilayah = resolve_wilayah(kdppk)
        if not wilayah:
            continue

        kota, provinsi = wilayah
        nama = str(item.get("nmppk", "")).strip()
        jenis = str(item.get("nmjnsppk", "")).strip()
        alamat = str(item.get("nmjlnppk", "")).strip()

        kelas_rs = ""
        jenis_upper = jenis.upper()
        if "RS" in jenis_upper or "RUMAH SAKIT" in jenis_upper or "KLINIK UTAMA" in jenis_upper:
            kelas_rs = rng.choice(["A", "B", "C", "D"])

        faskes_dict[kdppk] = {
            "kdppk": kdppk,
            "nama_faskes": nama,
            "jenis_faskes": jenis,
            "kelas_rs": kelas_rs,
            "provinsi": provinsi,
            "kota_kabupaten": kota,
            "alamat": alamat,
            "telepon": str(item.get("telpppk", "") or "").strip(),
            "latitude": item.get("latitude", ""),
            "longitude": item.get("longitude", ""),
            "support_bpjs": "1",
        }

    # Gabungkan RS tambahan Non-BPJS (jika hasil scrape_non_bpjs_osm.py tersedia)
    non_bpjs_path = base_dir / "tabel_faskes_non_bpjs.csv"
    non_bpjs_added = 0
    if non_bpjs_path.exists():
        with non_bpjs_path.open("r", encoding="utf-8") as f:
            for row in csv.DictReader(f):
                kd = str(row.get("kdppk", "")).strip()
                if not kd or kd in faskes_dict:
                    continue
                faskes_dict[kd] = {
                    "kdppk": kd,
                    "nama_faskes": str(row.get("nama_faskes", "")).strip(),
                    "jenis_faskes": str(row.get("jenis_faskes", "Rumah sakit")).strip(),
                    "kelas_rs": str(row.get("kelas_rs", "")).strip(),
                    "provinsi": str(row.get("provinsi", "")).strip(),
                    "kota_kabupaten": str(row.get("kota_kabupaten", "")).strip(),
                    "alamat": str(row.get("alamat", "-")).strip(),
                    "telepon": str(row.get("telepon", "")).strip(),
                    "latitude": row.get("latitude", ""),
                    "longitude": row.get("longitude", ""),
                    "support_bpjs": str(row.get("support_bpjs", "0")).strip() or "0",
                }
                non_bpjs_added += 1

    if not faskes_dict:
        print("[-] Tidak ada data faskes Jabodetabek yang cocok.")
        return

    faskes_final = []
    for index, data in enumerate(faskes_dict.values(), start=1):
        row = {"id_faskes": index}
        row.update(data)
        faskes_final.append(row)

    with out_path.open("w", newline="", encoding="utf-8") as f:
        writer = csv.DictWriter(f, fieldnames=faskes_final[0].keys())
        writer.writeheader()
        writer.writerows(faskes_final)

    rs_count = sum(1 for row in faskes_final if "rumah sakit" in row["jenis_faskes"].lower())
    print(f"[+] {out_path.name} BERHASIL DIBUAT! (Total: {len(faskes_final)} faskes, Rumah Sakit: {rs_count}, Non-BPJS: {non_bpjs_added})")


if __name__ == "__main__":
    main()
