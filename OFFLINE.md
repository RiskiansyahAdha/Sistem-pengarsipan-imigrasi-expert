# Dashboard Statistik Umur: versi OFFLINE (tanpa server, tanpa internet)

Versi ini tidak memakai XAMPP, MySQL, atau CDN. Semua berjalan dari satu folder:
PHP portable + SQLite + file lokal. Setelah jadi, cukup klik **JALANKAN.bat**.

Yang sudah diubah di paket ini:
- Chart.js diambil dari file lokal (`public/vendor/chartjs/chart.umd.js`, versi 4.4.9), bukan CDN.
- `.env.offline` sudah memakai SQLite dan tanpa layanan luar (session/cache berbasis file, antrean sync).
- `JALANKAN.bat` untuk Windows: membuat `.env` dan kunci aplikasi, menyiapkan database, menjalankan migrasi, lalu membuka browser.

## Yang perlu dilakukan sekali (di komputer yang ada internet)

Paket ini belum berisi folder `vendor` (library Laravel). Folder itu dibuat dengan Composer.

1. Pastikan PHP 8.2+ dan Composer terpasang.
2. Buat proyek Laravel baru:
   ```
   composer create-project laravel/laravel dashboard-umur
   cd dashboard-umur
   ```
3. Salin folder **app**, **database**, **resources**, **routes** dari paket ini ke proyek (pilih Replace / Timpa).
4. Salin `public/vendor/chartjs/` dari paket ini ke `public/vendor/chartjs/` di proyek.
5. Pasang pembaca Excel dan hapus paket dev:
   ```
   composer require phpoffice/phpspreadsheet
   composer install --no-dev --optimize-autoloader
   ```
6. Salin `.env.offline` menjadi `.env` (timpa yang lama), lalu:
   ```
   php artisan key:generate
   php artisan migrate
   ```
7. Tes di browser lewat `php artisan serve`, pastikan dashboard tampil dan impor berjalan.

## Membuat folder aplikasi untuk dibawa

Isi folder akhir (misalnya `DashboardUmur/`):
```
DashboardUmur/
├── php/                  ← PHP portable Windows x64 (Thread Safe), dari windows.php.net
├── app/, bootstrap/, config/, database/, public/, resources/, routes/, storage/, vendor/
├── artisan, composer.json
├── .env                  ← dari .env.offline
└── JALANKAN.bat
```

Salin seluruh isi proyek Laravel dari langkah 2 sampai 6 (termasuk `vendor`), tambahkan folder `php` dan `JALANKAN.bat`.

### Mengaktifkan extension PHP (sekali)

Di folder `php`, salin `php.ini-development` menjadi `php.ini`, lalu ubah atau tambahkan:
```
extension_dir = "ext"
extension=fileinfo
extension=mbstring
extension=openssl
extension=pdo_sqlite
extension=sqlite3
extension=zip
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

## Menjalankan

Klik dua kali **JALANKAN.bat**. Browser akan terbuka di `http://127.0.0.1:8000/dashboard`.
Jangan menutup jendela hitam selama memakai aplikasi. Menutupnya berarti server berhenti.

Server hanya bisa diakses dari komputer itu sendiri (`127.0.0.1`). Jangan ubah ke `0.0.0.0`.

## Catatan penting

- **Data pemohon ada di `database\database.sqlite`.** Salin file ini sebagai cadangan secara berkala, misalnya ke flashdisk. Kalau file ini hilang, data hilang.
- **Impor tetap butuh Excel**: di Google Sheets pilih File > Unduh > Microsoft Excel (.xlsx), lalu impor lewat menu Impor. Proses impor sendiri tidak memerlukan internet.
- **Bawa paket ke komputer lain**: cukup salin folder `DashboardUmur` beserta `database\database.sqlite`. Tidak perlu instal apa pun.
- **Pindah data dari versi XAMPP/MySQL**: ekspor data dari Excel lalu impor, atau pakai DB Browser for SQLite untuk memindahkan tabel `pemohons`.
- Ikuti juga aturan keamanan di `LANGKAH.md`: minta izin pembimbing sebelum memasukkan data asli, dan hapus berkas Excel setelah impor.

## Kalau ada masalah

| Gejala | Solusi |
|---|---|
| Jendela langsung tertutup | Jalankan `JALANKAN.bat` dari Command Prompt untuk melihat pesannya. Biasanya `php\php.exe` belum ada. |
| `could not find driver` | Aktifkan `extension=pdo_sqlite` di `php\php.ini`. |
| Chart tidak muncul | Pastikan file `public\vendor\chartjs\chart.umd.js` ada. |
| Halaman kosong atau error 500 | Buka `storage\logs\laravel.log`. |
| Impor gagal karena ukuran | Naikkan `upload_max_filesize` dan `post_max_size` di `php.ini`. |
| Tampilan lama setelah update file | Jalankan `php\php.exe artisan optimize:clear`. |
