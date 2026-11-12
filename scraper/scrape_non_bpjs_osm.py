import csv
import json
import math
from pathlib import Path
import random
import re
import time
import urllib.error
import urllib.parse
import urllib.request

OVERPASS_ENDPOINTS = [
    "https://overpass-api.de/api/interpreter",
    "https://overpass.kumi.systems/api/interpreter",
]

# Query berbasis radius sekitar pusat kota Jabodetabek jauh lebih ringan
# bagi server Overpass daripada bounding-box persegi raksasa.
JABODETABEK_ZONES = [
    ("DKI Jakarta", -6.2088, 106.8456, 16000),
    ("Bogor Kota", -6.5971, 106.8060, 12000),
    ("Bogor Kab", -6.4797, 106.8900, 15000),
    ("Depok", -6.4025, 106.7942, 11000),
    ("Tangerang Kota", -6.1783, 106.6319, 13000),
    ("Tangerang Selatan", -6.3000, 106.7100, 11000),
    ("Bekasi Kota", -6.2383, 106.9756, 12000),
    ("Bekasi Kab", -6.2800, 107.1400, 14000),
]

HOSPITAL_NAME_REGEX = re.compile(
    r"\b(rs|rsu|rsud|rsup|rsia|rskia|rsk|rsj|rsp|rskb|rsgm|rspad|rumkit|rumkital|rumah\s+sakit|hospital|hospitals|medical\s+cent(?:er|re))\b",
    re.IGNORECASE,
)

GENERIC_NAME_TOKENS = {
    "rs", "rsu", "rsud", "rsup", "rsia", "rskia", "rsk", "rsj", "rsp",
    "rumah", "sakit", "hospital", "hospitals", "umum", "daerah", "khusus",
    "islam", "ibu", "dan", "anak", "klinik", "utama", "pratama", "kota",
    "kabupaten", "kab", "dr", "prof", "h", "hj", "st", "santo", "santa",
    "jakarta", "bogor", "depok", "tangerang", "selatan", "bekasi",
}

EXCLUDE_NAME_KEYWORDS = [
    "hewan", "veteriner", "satwa", "puskesmas", "posyandu", "apotek", "apotik",
    "praktek", "praktik", "bidan", "laboratorium", "optik", "gedung parkir",
    "parkir", "igd ", "poli ", "poliklinik", "inst ", "instalasi",
]

OUTSIDE_CITY_KEYWORDS = [
    "karawang", "cikampek", "rengasdengklok", "serang", "cilegon", "cianjur",
    "cipanas", "sukabumi", "cicurug", "purwakarta", "subang", "pandeglang",
    "lebak", "rangkasbitung", "bandung", "cimahi",
]

CITY_TAG_RULES = [
    (["jakarta selatan", "jaksel"], ("Jakarta Selatan", "DKI Jakarta")),
    (["jakarta timur", "jaktim"], ("Jakarta Timur", "DKI Jakarta")),
    (["jakarta pusat", "jakpus"], ("Jakarta Pusat", "DKI Jakarta")),
    (["jakarta barat", "jakbar"], ("Jakarta Barat", "DKI Jakarta")),
    (["jakarta utara", "jakut"], ("Jakarta Utara", "DKI Jakarta")),
    (["kepulauan seribu"], ("Kepulauan Seribu", "DKI Jakarta")),
    (["tangerang selatan", "tangsel", "serpong", "ciputat", "pamulang", "bintaro"], ("Tangerang Selatan", "Banten")),
    (["tangerang"], ("Tangerang", "Banten")),
    (["bogor", "cibinong", "citeureup", "sentul", "ciawi", "cileungsi", "gunung putri", "parung"], ("Bogor", "Jawa Barat")),
    (["depok", "cinere", "cimanggis", "sawangan", "margonda"], ("Depok", "Jawa Barat")),
    (["bekasi", "cikarang", "tambun", "cibitung"], ("Bekasi", "Jawa Barat")),
]


def haversine_meters(lat1, lon1, lat2, lon2):
    r = 6371000.0
    phi1, phi2 = math.radians(lat1), math.radians(lat2)
    dphi = math.radians(lat2 - lat1)
    dlambda = math.radians(lon2 - lon1)
    a = math.sin(dphi / 2) ** 2 + math.cos(phi1) * math.cos(phi2) * math.sin(dlambda / 2) ** 2
    return 2 * r * math.atan2(math.sqrt(a), math.sqrt(1 - a))


def normalize_tokens(name):
    cleaned = re.sub(r"[^a-z0-9\s]", " ", name.lower())
    return {tok for tok in cleaned.split() if len(tok) > 1 and tok not in GENERIC_NAME_TOKENS}


def is_similar_hospital(name_a, lat_a, lon_a, name_b, lat_b, lon_b, radius_close=350.0, radius_name=1200.0):
    dist = haversine_meters(lat_a, lon_a, lat_b, lon_b)
    if dist <= radius_close:
        return True
    if dist <= radius_name:
        toks_a = normalize_tokens(name_a)
        toks_b = normalize_tokens(name_b)
        if toks_a and toks_b:
            overlap = toks_a & toks_b
            if overlap and (len(overlap) >= 2 or len(overlap) / min(len(toks_a), len(toks_b)) >= 0.6):
                return True
    return False


def fetch_zone(label, lat, lon, radius_m):
    query = f"""
[out:json][timeout:45];
nwr(around:{radius_m},{lat},{lon})["amenity"="hospital"];
out center tags;
"""
    payload = urllib.parse.urlencode({"data": query}).encode("utf-8")
    last_err = None
    for attempt in range(3):
        for endpoint in OVERPASS_ENDPOINTS:
            req = urllib.request.Request(
                endpoint,
                data=payload,
                headers={
                    "User-Agent": "MediGO-Scraper/1.0",
                    "Accept": "application/json",
                },
            )
            try:
                with urllib.request.urlopen(req, timeout=60) as resp:
                    return json.loads(resp.read().decode("utf-8"))
            except (urllib.error.URLError, TimeoutError, json.JSONDecodeError) as e:
                print(f"    -> {endpoint} gagal ({e}), coba mirror lain...", flush=True)
                last_err = e
                time.sleep(2)
        time.sleep(3 * (attempt + 1))
    raise RuntimeError(f"Semua mirror Overpass gagal untuk zona {label}: {last_err}")


def fetch_overpass_data(raw_cache_path):
    all_elements = {}
    completed_zones = set()

    if raw_cache_path.exists():
        try:
            with raw_cache_path.open("r", encoding="utf-8") as f:
                cached = json.load(f)
            for el in cached.get("elements", []):
                all_elements[f"{el.get('type')}:{el.get('id')}"] = el
            completed_zones = set(cached.get("completed_zones", []))
        except Exception:
            pass

    for idx, (label, lat, lon, radius_m) in enumerate(JABODETABEK_ZONES, start=1):
        if label in completed_zones:
            continue
        print(f"Mengambil zona {idx}/{len(JABODETABEK_ZONES)}: {label} (radius {radius_m//1000} km)...", flush=True)
        zone_data = fetch_zone(label, lat, lon, radius_m)
        elems = zone_data.get("elements", [])
        for el in elems:
            all_elements[f"{el.get('type')}:{el.get('id')}"] = el
        completed_zones.add(label)
        print(f"  -> OK ({len(elems)} elemen, akumulasi unik: {len(all_elements)})", flush=True)

        with raw_cache_path.open("w", encoding="utf-8") as f:
            json.dump(
                {"completed_zones": sorted(completed_zones), "elements": list(all_elements.values())},
                f,
                ensure_ascii=False,
                indent=2,
            )
        time.sleep(2)

    return {"elements": list(all_elements.values())}


def format_osm_address(tags):
    parts = [
        tags.get("addr:street", ""),
        tags.get("addr:housenumber", ""),
        tags.get("addr:subdistrict", "") or tags.get("addr:village", ""),
        tags.get("addr:district", ""),
        tags.get("addr:city", ""),
    ]
    joined = ", ".join(p.strip() for p in parts if p and p.strip())
    if joined:
        return joined
    full = (tags.get("addr:full") or tags.get("address") or "").strip()
    return full if full else "-"


def resolve_city_from_osm(tags, lat, lon, bpjs_points):
    addr_blob = " ".join(
        str(tags.get(k, "") or "")
        for k in ("addr:city", "addr:district", "addr:subdistrict", "addr:full", "is_in")
    ).lower()

    if any(out_kw in addr_blob for out_kw in OUTSIDE_CITY_KEYWORDS):
        return None

    # Gunakan koordinat BPJS terdekat sebagai sumber utama bila di bawah 2.5 km (sangat akurat untuk Jabodetabek)
    best_dist = float("inf")
    best_wilayah = None
    for pt in bpjs_points:
        d = haversine_meters(lat, lon, pt["lat"], pt["lon"])
        if d < best_dist:
            best_dist = d
            best_wilayah = (pt["kota"], pt["provinsi"])

    if best_dist <= 2500.0 and best_wilayah:
        return best_wilayah

    for keywords, wilayah in CITY_TAG_RULES:
        if any(kw in addr_blob for kw in keywords):
            return wilayah

    if best_dist <= 8000.0:
        return best_wilayah
    return None


def main():
    base_dir = Path(__file__).resolve().parent
    faskes_csv_path = base_dir / "tabel_faskes.csv"
    raw_cache_path = base_dir / "osm_hospitals_raw.json"
    out_non_bpjs_path = base_dir / "tabel_faskes_non_bpjs.csv"

    if not faskes_csv_path.exists():
        print("[-] tabel_faskes.csv belum ada. Jalankan mapper.py terlebih dahulu!")
        return

    bpjs_rows = []
    bpjs_points = []
    bpjs_rs_or_ku = []

    with faskes_csv_path.open("r", encoding="utf-8") as f:
        for row in csv.DictReader(f):
            if row.get("support_bpjs", "1") == "0" or str(row.get("kdppk", "")).startswith("OSM-"):
                continue
            bpjs_rows.append(row)
            try:
                lat = float(row["latitude"])
                lon = float(row["longitude"])
            except (TypeError, ValueError):
                continue
            info = {
                "nama": row["nama_faskes"],
                "jenis": row["jenis_faskes"],
                "kota": row["kota_kabupaten"],
                "provinsi": row["provinsi"],
                "lat": lat,
                "lon": lon,
            }
            bpjs_points.append(info)
            jenis_up = row["jenis_faskes"].upper()
            if "RUMAH SAKIT" in jenis_up or "RS" in jenis_up or "KLINIK UTAMA" in jenis_up:
                bpjs_rs_or_ku.append(info)

    data = fetch_overpass_data(raw_cache_path)
    elements = data.get("elements", [])
    print(f"Total elemen OSM diterima: {len(elements)}")

    # Urutkan way/relation lebih dulu daripada node karena biasanya punya info lebih lengkap
    type_priority = {"way": 0, "relation": 1, "node": 2}
    elements.sort(key=lambda el: (type_priority.get(el.get("type"), 9), -len(el.get("tags", {}))))

    rng = random.Random(99)
    non_bpjs_items = []

    for el in elements:
        tags = el.get("tags") or {}
        nama = (tags.get("name") or "").strip()
        if not nama:
            continue

        nama_lower = nama.lower()
        if nama_lower in {"rumah sakit", "hospital", "rs", "klinik", "puskesmas"}:
            continue
        if any(ex in nama_lower for ex in EXCLUDE_NAME_KEYWORDS):
            continue
        if not HOSPITAL_NAME_REGEX.search(nama):
            continue

        lat = el.get("lat") if "lat" in el else (el.get("center") or {}).get("lat")
        lon = el.get("lon") if "lon" in el else (el.get("center") or {}).get("lon")
        if lat is None or lon is None:
            continue
        lat, lon = float(lat), float(lon)

        wilayah = resolve_city_from_osm(tags, lat, lon, bpjs_points)
        if not wilayah:
            continue

        # Cek apakah RS ini sudah ada di data BPJS Aplicares
        if any(
            is_similar_hospital(nama, lat, lon, ref["nama"], ref["lat"], ref["lon"], radius_close=250.0, radius_name=1000.0)
            for ref in bpjs_rs_or_ku
        ):
            continue

        # Cek duplikat antar elemen OSM sendiri (misal node + building way untuk RS yang sama)
        if any(
            is_similar_hospital(nama, lat, lon, prev["nama_faskes"], prev["latitude"], prev["longitude"], radius_close=300.0, radius_name=800.0)
            for prev in non_bpjs_items
        ):
            continue

        kota, provinsi = wilayah
        telepon = (tags.get("phone") or tags.get("contact:phone") or "").strip()
        alamat = format_osm_address(tags)
        if alamat == "-":
            alamat = f"{kota}, {provinsi}"

        non_bpjs_items.append({
            "kdppk": f"OSM-{el.get('type', 'n')[0].upper()}{el.get('id')}",
            "nama_faskes": nama,
            "jenis_faskes": "Rumah sakit",
            "kelas_rs": rng.choice(["B", "C"]),
            "provinsi": provinsi,
            "kota_kabupaten": kota,
            "alamat": alamat,
            "telepon": telepon,
            "latitude": lat,
            "longitude": lon,
            "support_bpjs": "0",
        })

    fieldnames = [
        "id_faskes", "kdppk", "nama_faskes", "jenis_faskes", "kelas_rs",
        "provinsi", "kota_kabupaten", "alamat", "telepon", "latitude", "longitude", "support_bpjs",
    ]

    with out_non_bpjs_path.open("w", newline="", encoding="utf-8") as f:
        writer = csv.DictWriter(f, fieldnames=fieldnames)
        writer.writeheader()
        for idx, item in enumerate(non_bpjs_items, start=1):
            row = {"id_faskes": idx}
            row.update(item)
            writer.writerow(row)

    print(f"[+] Ditemukan {len(non_bpjs_items)} RS tambahan (Non-BPJS) -> disimpan ke {out_non_bpjs_path.name}")


if __name__ == "__main__":
    main()
