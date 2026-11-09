from playwright.sync_api import sync_playwright
import json

def main():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=False, channel="chrome")
        context = browser.new_context()
        page = context.new_page()

        data_faskes_terkumpul = []

        def simpan_data_browser(data):
            print(f"\n Bingo, berhasil menangkap data! Tipe: {type(data)}")
            data_faskes_terkumpul.append(data)

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
                    print(f"\n URL: {response.url} (Panjang: {len(teks)} karakter)")
                    print(f"          Cuplikan: {teks[:80]}...")
                    try:
                        data_json = response.json()
                        if len(teks) > 500:
                            print("          -> Status: JSON Standar tertangkap!")
                            data_faskes_terkumpul.append(data_json)
                    except Exception:
                        print("          -> Status: Dienkripsi JWE (Menunggu hasil sadapan JSON.parse browser...)")
                except Exception:
                    pass

        page.on("response", tangkap_response)

        print("Membuka web BPJS Aplicares...")
        page.goto("https://faskes.bpjs-kesehatan.go.id/aplicares/", timeout=60000)

        print("\n" + "="*60)
        print("BROWSER TERBUKA! SILAKAN LAKUKAN MANUAL DI CHROME:")
        print("1. Pilih Provinsi (misal: DKI Jakarta) dan klik 'Cari Faskes'")
        print("2. Lihat ke terminal ini, pastikan muncul log [LOG API] atau [BINGO]")
        print("3. JIKA SUDAH SELESAI, TUTUP SAJA JENDELA CHROME-NYA (Klik X)")
        print("   Data akan otomatis tersimpan saat Chrome ditutup!")
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
            with open("faskes_raw_playwright.json", "w", encoding="utf-8") as f:
                json.dump(data_faskes_terkumpul, f, ensure_ascii=False, indent=2)
            print(f"YEAY! {len(data_faskes_terkumpul)} paket data berhasil disimpan ke 'faskes_raw_playwright.json'")
        else:
            print("Yah, belum ada data yang tertangkap.")

if __name__ == "__main__":
    main()