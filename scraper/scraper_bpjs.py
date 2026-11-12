from pathlib import Path
from playwright.sync_api import sync_playwright
import json


def extract_kdppk_items(node):
    """Ambil semua objek faskes yang punya kdppk secara rekursif untuk laporan ringkas."""
    if isinstance(node, list):
        for item in node:
            yield from extract_kdppk_items(item)
    elif isinstance(node, dict):
        if "kdppk" in node and "nmppk" in node:
            yield node
        else:
            for value in node.values():
                if isinstance(value, (list, dict)):
                    yield from extract_kdppk_items(value)


def main():
    base_dir = Path(__file__).resolve().parent
    out_path = base_dir / "faskes_raw_playwright.json"

    with sync_playwright() as p:
        try:
            browser = p.chromium.launch(headless=False, channel="chrome")
        except Exception:
            browser = p.chromium.launch(headless=False)

        context = browser.new_context()
        page = context.new_page()

        data_faskes_terkumpul = []
        unique_kdppk = set()

        def catat_faskes(payload):
            data_faskes_terkumpul.append(payload)
            baru = 0
            for item in extract_kdppk_items(payload):
                kd = item.get("kdppk")
                if kd and kd not in unique_kdppk:
                    unique_kdppk.add(kd)
                    baru += 1
            if baru:
                print(f"          -> +{baru} faskes baru tertangkap (Total unik: {len(unique_kdppk)})")

        def simpan_data_browser(data):
            print(f"\n[BINGO] Menangkap data terdekripsi dari browser! Tipe: {type(data).__name__}")
            catat_faskes(data)

        page.expose_function("kirimKePython", simpan_data_browser)

        page.add_init_script("""
            const originalParse = JSON.parse;
            JSON.parse = function(text, reviver) {
                const data = originalParse(text, reviver);
                try {
                    if (data && typeof data === 'object') {
                        const str = JSON.stringify(data);
                        if (str.includes('kdppk') || str.includes('nmppk') || (Array.isArray(data) && data.length > 20)) {
                            window.kirimKePython(data);
                        }
                    }
                } catch (e) {}
                return data;
            };
        """)

        def tangkap_response(response):
            url_kecil = response.url.lower()
            if "api/" in url_kecil and response.status == 200:
                try:
                    teks = response.text()
                    print(f"\n[LOG API] URL: {response.url} (Panjang: {len(teks)} karakter)")
                    try:
                        data_json = response.json()
                        if len(teks) > 500:
                            print("          -> Status: JSON Standar tertangkap!")
                            catat_faskes(data_json)
                    except Exception:
                        print("          -> Status: Dienkripsi JWE (Menunggu hasil sadapan JSON.parse browser...)")
                except Exception:
                    pass

        page.on("response", tangkap_response)

        print("Membuka web BPJS Aplicares...")
        page.goto("https://faskes.bpjs-kesehatan.go.id/aplicares/", timeout=60000)

        print("\n" + "="*60)
        print("BROWSER TERBUKA! SILAKAN LAKUKAN MANUAL DI CHROME:")
        print("1. Untuk Jabodetabek, pilih satu per satu Provinsi:")
        print("   - DKI Jakarta, Jawa Barat, dan Banten")
        print("   lalu klik 'Cari Faskes' dan tunggu tabel selesai termuat.")
        print("2. Jika ada filter Kab/Kota atau pagination, buka halamannya")
        print("   sampai log terminal berhenti bertambah.")
        print("3. JIKA SUDAH SELESAI, TUTUP JENDELA CHROME-NYA (Klik X)")
        print(f"   Data otomatis tersimpan ke {out_path.name}!")
        print("="*60 + "\n")

        # KUNCI UTAMA: Gunakan loop wait_for_timeout agar Playwright TIDAK MEMBEKU
        try:
            while len(context.pages) > 0:
                page.wait_for_timeout(1000)
        except Exception:
            # Ketika jendela Chrome di-close manual oleh kamu, dia akan keluar dari loop ini
            pass

        print("\nBrowser ditutup. Menyimpan data...")
        if data_faskes_terkumpul:
            with out_path.open("w", encoding="utf-8") as f:
                json.dump(data_faskes_terkumpul, f, ensure_ascii=False, indent=2)
            print(f"YEAY! {len(data_faskes_terkumpul)} paket ({len(unique_kdppk)} faskes unik) disimpan ke '{out_path.name}'")
        else:
            print("Yah, belum ada data yang tertangkap.")

if __name__ == "__main__":
    main()
