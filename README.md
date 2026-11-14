# MediGO - Platform Direktori & Komunitas Fasilitas Kesehatan Jabodetabek

[![Laravel](https://img.shields.io/badge/Laravel-11.x-FF2D20?style=flat&logo=laravel)](https://laravel.com/)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=flat&logo=php)](https://www.php.net/)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=flat&logo=bootstrap)](https://getbootstrap.com/)
[![Leaflet](https://img.shields.io/badge/Leaflet-1.9-199900?style=flat&logo=leaflet)](https://leafletjs.com/)
[![License](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)

**MediGO** adalah platform direktori interaktif dan forum komunitas fasilitas kesehatan (faskes) di wilayah **Jabodetabek**. MediGO dirancang untuk memudahkan masyarakat menemukan rumah sakit, puskesmas, klinik, apotek, laboratorium medis, optik, hingga praktik dokter umum dan dokter gigi terdekat beserta informasi rujukan BPJS, spesialisasi dokter, fasilitas penunjang, ulasan pengguna, dan konsultasi interaktif dengan asisten kecerdasan buatan (**MediBot**).

---

## 📑 Daftar Isi
1. [Fitur Utama](#-fitur-utama)
2. [Arsitektur & Struktur Proyek](#-arsitektur--struktur-proyek)
3. [Prasyarat Sistem (Prerequisites)](#-prasyarat-sistem-prerequisites)
4. [Panduan Instalasi & Setup Lengkap](#-panduan-instalasi--setup-lengkap)
   - [Langkah 1: Setup Backend](#1-setup-backend-medigobackend)
   - [Langkah 2: Setup Frontend](#2-setup-frontend-medigofrontend)
5. [Kredensial Akun Default](#-kredensial-akun-default-demo--testing)
6. [Struktur Endpoint & Port Default](#-struktur-endpoint--port-default)
7. [Tim Pengembang](#-tim-pengembang)
8. [Troubleshooting](#-troubleshooting)
9. [Lisensi](#-lisensi)

---

## 🌟 Fitur Utama

- **Direktori Faskes Jabodetabek**: Pencarian dan filter faskes berdasarkan jenis (Rumah Sakit, Puskesmas, Klinik, Apotek, Laboratorium, Optik, Dokter Gigi, Dokter Umum, serta faskes BPJS), spesialisasi dokter, fasilitas penunjang, hingga wilayah kabupaten/kota di Jabodetabek.
- **Peta Interaktif (Map View)**: Integrasi peta Leaflet & OpenStreetMap dengan pelacakan lokasi pengguna, pin marker faskes, radius filter terdekat, dan navigasi langsung ke rute faskes.
- **Forum Komunitas Kesehatan**:
  - Berbagi ulasan pengalaman faskes, tips kesehatan, dan tanya-jawab BPJS.
  - Fitur **Lazy Loading** agar daftar diskusi dimuat bertahap dan cepat.
  - Filter kategori via dropdown di mobile, serta filter cepat diskusi **Sedang Tren** dan **Faskes Terdekat**.
  - Pelaporan diskusi (*reporting system*) untuk keamanan komunitas.
- **MediBot (Asisten AI Kesehatan)**: Konsultasi kesehatan interaktif berbasis Gemini AI untuk rekomendasi penanganan pertama dan rujukan faskes yang tepat.
- **Bookmark & Review Faskes**: Simpan faskes favorit dan berikan rating bintang beserta ulasan terperinci.
- **Panel Admin Lengkap**:
  - Manajemen Fasilitas Kesehatan (CRUD, sinkronisasi koordinat peta, foto galeri).
  - Manajemen Spesialisasi Dokter (CRUD).
  - Manajemen Fasilitas Penunjang Medis (CRUD).
  - Moderasi Laporan Diskusi Forum.

---

## 🏗 Arsitektur & Struktur Proyek

MediGO dibangun dengan arsitektur **Decoupled (Separation of Concerns)** menggunakan dua aplikasi Laravel 11:

```text
medigo/
├── backend/                  # RESTful API Service (Laravel 11)
│   ├── app/Http/Controllers/ # Endpoint API Faskes, Forum, Auth, AI MediBot, Admin
│   ├── database/migrations/  # Skema database MySQL
│   ├── database/seeders/     # Data master faskes, spesialisasi, fasilitas, akun
│   └── routes/api.php        # API routes (/api/faskes, /api/discussions, dll)
│
├── frontend/                 # Client Web Application (Laravel 11 + Blade + Vite)
│   ├── app/Http/Controllers/ # Client Controller (konsumsi backend API)
│   ├── resources/
│   │   ├── css/              # Modular CSS per halaman & design tokens
│   │   ├── js/               # Modular JavaScript (Map, Lazy load forum, Beranda)
│   │   └── views/            # Blade templates (beranda, map, faskes, forum, admin)
│   └── routes/web.php        # Routing halaman web pengguna & admin
```

---

## ⚙ Prasyarat Sistem (Prerequisites)

Pastikan lingkungan lokal Anda memenuhi spesifikasi berikut:
- **PHP** >= 8.2 (ekstensi wajib: `pdo_mysql`, `mbstring`, `openssl`, `curl`, `fileinfo`, `gd`, `bcmath`)
- **Composer** >= 2.x
- **Node.js** >= 18.x & **NPM** >= 9.x
- **Database Server**: MySQL >= 8.0 atau MariaDB >= 10.4 (disarankan menggunakan **Laragon** atau **XAMPP**)
- **Git**

---

## 🚀 Panduan Instalasi & Setup Lengkap

### 1. Setup Backend (`medigo/backend`)

Buka terminal (PowerShell / Command Prompt / Bash):

```bash
# Pindah ke direktori backend
cd medigo/backend

# Install dependensi PHP
composer install

# Salin file konfigurasi environment
cp .env.example .env
```

Buka file `.env` di `medigo/backend` dan sesuaikan koneksi database MySQL:
```env
APP_NAME=MediGO_Backend
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://127.0.0.1:8005

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=medigo_db
DB_USERNAME=root
DB_PASSWORD=

# Konfigurasi Gemini AI untuk fitur MediBot
GEMINI_API_KEY=your_gemini_api_key_here
GEMINI_MODEL=gemini-1.5-flash
GEMINI_TIMEOUT=30
```

> [!NOTE]
> - Buat database baru bernama `medigo_db` di MySQL Anda (misal via HeidiSQL, phpMyAdmin, atau MySQL CLI: `CREATE DATABASE medigo_db;`).
> - `GEMINI_MODEL`: Model Gemini yang digunakan untuk chatbot MediBot (rekomendasi: `gemini-1.5-flash` atau `gemini-1.5-pro`).
> - `GEMINI_TIMEOUT`: Batas waktu maksimal permintaan ke Google Gemini API dalam detik (default: `30`).

Lanjutkan perintah berikut:
```bash
# Generate application key
php artisan key:generate

# Jalankan migrasi dan seeder data faskes Jabodetabek & akun default
php artisan migrate --seed

# Buat symbolic link folder storage publik
php artisan storage:link

# Jalankan server backend di port 8005
php artisan serve --port=8005
```

Backend API kini aktif dan dapat diakses di: `http://127.0.0.1:8005/api`

---

### 2. Setup Frontend (`medigo/frontend`)

Buka terminal **baru** (biarkan backend tetap berjalan):

```bash
# Pindah ke direktori frontend
cd medigo/frontend

# Install dependensi PHP
composer install

# Install dependensi Node.js / frontend assets
npm install

# Salin file konfigurasi environment
cp .env.example .env
```

Buka file `.env` di `medigo/frontend` dan pastikan konfigurasi berikut:
```env
APP_NAME=MediGO
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

# URL backend API MediGO (sesuaikan dengan port backend di atas)
MEDIGO_BE=http://127.0.0.1:8005

# Sesi login web
SESSION_DRIVER=file
```

Lanjutkan perintah berikut:
```bash
# Generate application key frontend
php artisan key:generate

# Buat symbolic link storage publik di frontend
php artisan storage:link

# Build aset frontend (CSS & JS modular)
npm run build

# Jalankan server frontend di port 8000
php artisan serve --port=8000
```

> [!TIP]
> Jika Anda sedang melakukan pengembangan aktif (*development mode*), jalankan `npm run dev` pada satu terminal agar Vite memantau perubahan file CSS/JS secara live (Hot Module Replacement).

Frontend MediGO kini siap dibuka di browser: `http://127.0.0.1:8000`

---

## 👥 Kredensial Akun Default (Demo & Testing)

Database seeder telah menyiapkan akun demo untuk pengujian sistem:

| Role | Nama Pengguna | Email Login | Password | Akses & Fitur |
| :--- | :--- | :--- | :--- | :--- |
| **Admin** | Admin MediGO | `admin@medigo.id` | `password` | Panel Manajemen Admin (`/admin/dashboard`), Kelola Faskes, Spesialisasi, Fasilitas Penunjang, Moderasi Laporan Forum |
| **User** | Dimas Pratama | `dimas.pratama@gmail.com` | `password` | Eksplorasi direktori, rating & ulasan faskes, simpan bookmark, buat topik diskusi, komentar forum, konsultasi MediBot |

---

## 🌐 Struktur Endpoint & Port Default

| Komponen | URL Default | Catatan |
| :--- | :--- | :--- |
| **Frontend Web** | `http://127.0.0.1:8000` | Antarmuka pengguna MediGO |
| **Backend REST API** | `http://127.0.0.1:8005/api` | API penyedia data faskes, autentikasi, & AI |
| **Halaman Peta Faskes** | `http://127.0.0.1:8000/map` | Peta interaktif Leaflet se-Jabodetabek |
| **Halaman Forum** | `http://127.0.0.1:8000/discussions` | Forum diskusi dengan lazy loading |
| **Dashboard Admin** | `http://127.0.0.1:8000/admin/dashboard` | Area khusus akun role Admin |

---

## 👨‍💻 Tim Pengembang

Proyek MediGO dikembangkan oleh:
- **Muhammad Prima Yudhistira** — Project Manager, Front-End Developer
- **Raden Mas Galih Pradityo** — Back-End Developer
- **Misael Feodora Pranata Dauhan** — UI/UX Developer
- **Osanna Patricia Limbong** — Data Analyst, Quality Assurance

---

## 🔧 Troubleshooting

1. **Gambar / Ikon Logo Tidak Muncul**:
   Pastikan perintah `php artisan storage:link` telah dijalankan di folder `medigo/frontend` dan `medigo/backend`.
2. **Error `cURL error 7: Failed to connect to 127.0.0.1 port 8005`**:
   Pastikan backend sudah aktif berjalan dengan perintah `php artisan serve --port=8005` di folder `medigo/backend`.
3. **Peta Tidak Menampilkan Marker**:
   Pastikan koneksi internet aktif untuk memuat tile OpenStreetMap, dan pastikan izin akses lokasi (*geolocation*) diizinkan di peramban Anda.
4. **Membersihkan Cache Laravel**:
   Jika terjadi perubahan rute atau tampilan Blade yang belum ter-refresh:
   ```bash
   php artisan route:clear
   php artisan view:clear
   php artisan config:clear
   php artisan cache:clear
   ```

---

## 📄 Lisensi
kata ael nama timnya ini aja
