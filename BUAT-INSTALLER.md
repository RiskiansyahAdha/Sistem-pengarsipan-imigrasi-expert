# Cara Membuat Installer .exe dengan Inno Setup

Hasil akhir: satu file **`DashboardUmur-Setup.exe`** yang bisa dibagikan.
User cukup klik dua kali → isi direktori → selesai, langsung bisa dipakai.

---

## Langkah 1 – Siapkan proyek Laravel yang lengkap

Kamu butuh satu folder proyek Laravel yang sudah berjalan offline. Lakukan di
komputer yang ada internet, satu kali saja:

```
composer create-project laravel/laravel dashboard-umur
cd dashboard-umur
```

Salin folder-folder dari **paket ini** ke folder proyek (pilih Replace/Timpa):
```
app\
database\
resources\
routes\
public\vendor\chartjs\  ← baru dari paket ini
```

Pasang library dan bersihkan dev dependency:
```
composer require phpoffice/phpspreadsheet
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate
```

Tes dulu: `php artisan serve`, buka `http://127.0.0.1:8000`, pastikan jalan.

---

## Langkah 2 – Unduh PHP portable

1. Buka https://windows.php.net/download/
2. Pilih **PHP 8.3**, **VS17 x64**, **Thread Safe** → klik **Zip**.
3. Ekstrak isi zip ke folder `php\` di dalam proyek.
4. Di folder `php\`, salin `php.ini-development` → `php.ini`, lalu buka
   `php.ini` dan ubah/aktifkan baris-baris berikut (hapus titik koma di depan):

```ini
extension_dir = "ext"
extension=fileinfo
extension=mbstring
extension=openssl
extension=pdo_sqlite
extension=sqlite3
extension=xml
extension=dom
extension=tokenizer
extension=ctype
extension=pdo
extension=curl
upload_max_filesize = 32M
post_max_size = 32M
memory_limit = 1024M
```

---

## Langkah 3 – Susun folder "build\"

Buat folder `build\` di samping file `INSTALLER.iss` ini, lalu **salin** isi
proyek ke dalamnya. Struktur akhir:

```
[folder kerja Inno Setup]
├── INSTALLER.iss          ← file script ini
└── build\
    ├── php\               ← PHP portable (step 2)
    │   ├── php.exe
    │   ├── php.ini
    │   └── ext\
    ├── app\
    ├── bootstrap\
    ├── config\
    ├── database\
    │   └── database.sqlite   ← bisa kosong, akan dibuat ulang saat install
    ├── public\
    │   └── vendor\chartjs\chart.umd.js
    ├── resources\
    ├── routes\
    ├── storage\
    ├── vendor\
    ├── artisan
    ├── composer.json
    └── .env               ← hasil key:generate tadi
```

**Jangan** ikutkan folder `node_modules\`, `.git\`, atau berkas `.env.example`.

---

## Langkah 4 – Install Inno Setup

1. Unduh dari https://jrsoftware.org/isinfo.php (gratis, open source).
2. Install seperti biasa (Next → Next → Finish).

---

## Langkah 5 – Build installer

1. Buka **Inno Setup Compiler**.
2. File → Open → pilih **`INSTALLER.iss`**.
3. Build → **Compile** (atau tekan `Ctrl+F9`).
4. Tunggu. Hasilnya: **`Output\DashboardUmur-Setup.exe`**.

---

## Hasil installer

Ketika user menjalankan `DashboardUmur-Setup.exe`:
- Pilih folder install (default: `C:\Program Files\DashboardUmur`).
- Semua file diekstrak, database disiapkan otomatis.
- Opsi: buat ikon Desktop dan/atau Start Menu.
- Di akhir: tawaran langsung buka dashboard.

Untuk menjalankan setelah install: klik pintasan di Start Menu atau Desktop,
atau buka `_launcher.bat` di folder install.

---

## Ukuran perkiraan

| Komponen          | Ukuran  |
|-------------------|---------|
| PHP portable      | ~30 MB  |
| vendor (no-dev)   | ~20–40 MB (tergantung paket) |
| Sisa proyek       | ~5 MB   |
| **Total installer** | **~30–50 MB** (dikompres lzma2) |

---

## Troubleshooting

| Masalah | Solusi |
|---------|--------|
| Compiler error "Source file not found" | Pastikan folder `build\` sudah lengkap dan path di INSTALLER.iss sesuai. |
| Database error saat install | Buka `storage\logs\laravel.log` di folder install. |
| Port 8000 sudah dipakai | Edit `_launcher.bat` di folder install, ganti `8000` ke `8001` di semua baris. |
| Ingin ganti ikon .exe | Simpan ikon `.ico` di folder build, lalu ubah `SetupIconFile=` di INSTALLER.iss. |
| Ingin ubah nama default folder install | Ubah `DefaultDirName=` di INSTALLER.iss. |
