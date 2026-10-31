# Dashboard Statistik Umur v2: impor Excel, tanggal lahir, Prioritas vs Non prioritas

Pembaruan dari paket sebelumnya. Yang baru:

- **Impor Excel** (`/impor`): satu berkas `.xlsx` berisi banyak tab sekaligus.
- **Format yang dibaca**: nama tab `BULAN TAHUN P` / `BULAN TAHUN MP` (contoh `AGUSTUS 2026 MP`), kolom A = nomor ID, B = nama, C = tanggal lahir.
- **Halaman Data** menampilkan ID, nama, **tanggal lahir** (bukan umur), jenis, dan bulan. Bisa dicari berdasarkan ID, nama, jenis, bulan, dan tahun.
- **Dashboard**: filter **Jenis** (Semua / Prioritas / Non prioritas), kartu dan dua grafik perbandingan Prioritas vs Non prioritas.
- Umur dihitung otomatis dari tanggal lahir pada **akhir bulan** di nama tab, dan dipakai untuk semua statistik.

## A. Kalau proyekmu sudah jalan (paket sebelumnya)

1. Salin folder **app**, **database**, **resources**, **routes** dari paket ini ke proyekmu. Pilih **Replace / Timpa**.
2. Pasang pembaca Excel (di folder proyek):
   ```
   composer require phpoffice/phpspreadsheet
   ```
3. Tambahkan kolom baru:
   ```
   php artisan migrate
   ```
4. **Hapus data contoh** sebelum mengimpor data asli, supaya tidak tercampur:
   ```
   php artisan migrate:fresh
   ```
   Perintah ini menghapus SEMUA tabel lalu membuatnya kosong lagi. Jangan dijalankan kalau ada data yang ingin dipertahankan.
   (Mau mencoba dulu dengan data contoh? Pakai `php artisan migrate:fresh --seed`.)
5. Jalankan `php artisan serve`, lalu buka `http://127.0.0.1:8000/impor`.

## B. Kalau memasang dari awal

Ikuti langkah pemasangan biasa (buat proyek, database, `.env`, zona waktu), salin keempat folder di atas,
lalu jalankan `composer require phpoffice/phpspreadsheet`, `php artisan migrate`, dan (opsional) `php artisan db:seed`.

## Cara mengimpor

1. Di Google Sheets: **File > Unduh > Microsoft Excel (.xlsx)**.
2. Buka menu **Impor**, pilih berkas, klik **Impor**.
3. Baca halaman hasil: jumlah data baru, diperbarui, dan baris yang gagal beserta nomor barisnya dan alasannya.
4. Perbaiki baris yang gagal di Excel, lalu impor lagi. Mengimpor berkas yang sama tidak menggandakan data.

### Impor rutin (mis. kemarin 60.000, hari ini 60.178)

Unggah saja berkas terbaru yang lengkap. Setiap baris dicocokkan dengan database lewat **ID + bulan + tahun + jenis**:

| Keadaan | Tindakan |
|---|---|
| ID belum ada | Ditambahkan (178 baris baru) |
| ID ada, nama atau tanggal lahir berbeda | Diperbarui, dan 50 contoh perubahan ditampilkan di halaman hasil |
| ID ada, isi sama persis | Dilewati, tidak ditulis ulang |
| ID muncul dua kali di tab yang sama di berkas | Baris paling bawah dipakai, dan dilaporkan (identik / berbeda) |

ID dibaca tanpa membedakan huruf besar/kecil, spasi diabaikan, dan angka nol di depan diabaikan (`00123` = `123`).
Seluruh impor berjalan dalam satu transaksi: kalau gagal di tengah, tidak ada data yang berubah.

Fitur **edit** di halaman Data sudah dimatikan. Data hanya berubah lewat impor.

Catatan format:
- Tab dengan nama lain (misalnya "REKAP") dilewati dan dilaporkan.
- Baris judul di baris pertama (misalnya "NO | NAMA | TGL LAHIR") dilewati otomatis.
- Tanggal bisa berupa sel tanggal Excel maupun teks `dd-mm-yyyy`. Tanggal mustahil (31-02-2007) ditolak.
- ID yang sama di dua tab berbeda (mis. P dan MP) dihitung sebagai dua data. ID yang sama di tab yang sama dianggap ganda: yang terakhir dipakai.

## Keamanan data (penting)

Berkas Excel ini berisi nama dan tanggal lahir asli pemohon.

- Minta izin pembimbing sebelum data asli dimasukkan ke aplikasi.
- Jalankan hanya dengan `php artisan serve` (hanya bisa dibuka dari komputermu sendiri). **Jangan** memakai `--host=0.0.0.0` dan jangan dihosting online sebelum login terpasang.
- Route impor di `routes/web.php` sudah diberi tanda untuk dipindahkan ke grup `auth` saat login dibuat.
- Hapus berkas Excel hasil unduhan setelah selesai, dan jangan membagikannya.

## Kalau ada masalah

| Pesan / gejala | Penyebab dan solusi |
|---|---|
| `Class "PhpOffice\PhpSpreadsheet\IOFactory" not found` | Jalankan `composer require phpoffice/phpspreadsheet`. |
| `Unknown column 'nomor'` | Jalankan `php artisan migrate`. |
| Semua tab dilewati | Nama tab harus seperti `AGUSTUS 2026 MP` (bulan, tahun 4 digit, lalu `P` atau `MP`). |
| Semua baris gagal "tanggal lahir tidak valid" | Kolom C bukan tanggal, atau urutan kolom berbeda. Urutan harus A = ID, B = nama, C = tanggal lahir. |
| Impor lama / terhenti | Berkas besar. Tab dibaca satu per satu dan hanya kolom A sampai C. Batas waktu 15 menit, batas memori dinaikkan otomatis jadi 1 GB. |
| Berkas gagal diunggah / halaman kembali tanpa pesan jelas | Batas upload PHP terlalu kecil. Di `php.ini` naikkan `upload_max_filesize` dan `post_max_size` (mis. 32M), lalu restart `php artisan serve`. |
| "Masih ada impor lain yang sedang berjalan" | Impor sebelumnya belum selesai. Tunggu, atau jalankan `php artisan cache:clear` kalau impor sebelumnya terputus. |
| Dashboard kosong setelah impor | Pilih **Tahun** yang sesuai di kanan atas, dan pastikan filter **Jenis** = Semua. |
| Tampilan aneh setelah menyalin | Jalankan `php artisan optimize:clear`. |

## Pembaruan tampilan (v4)

- Tampilan baru bergaya situs resmi pemerintah: header instansi, menu navy dengan aksen emas, footer.
- Kartu **Termuda** dan **Tertua** dihapus dari dashboard.
- Animasi: angka kartu menghitung naik, kartu dan grafik muncul saat di-scroll, grafik beranimasi. Otomatis mati bila perangkat memakai "kurangi gerakan".
- Hanya file di `resources/views/` yang berubah. Cukup timpa folder `resources/`, lalu `php artisan view:clear`.
- Lambang di header adalah ikon perisai generik. Ganti dengan logo resmi instansi bila diizinkan (di `layouts/dasbor.blade.php`).
