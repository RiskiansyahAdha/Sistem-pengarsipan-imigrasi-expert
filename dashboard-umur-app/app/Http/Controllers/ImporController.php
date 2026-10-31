<?php

namespace App\Http\Controllers;

use App\Support\ImporPemohon;
use Generator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

class ImporController extends Controller
{
    private const UKURAN_POTONGAN = 500;

    public function form()
    {
        return view('impor.form');
    }

    public function proses(Request $request)
    {
        $request->validate([
            'berkas' => 'required|file|mimes:xlsx,xls|max:20480', // maksimal 20 MB
        ], [
            'berkas.required' => 'Pilih berkas Excel terlebih dahulu. Kalau sudah memilih tetapi tetap gagal, berkas mungkin melebihi batas upload PHP (upload_max_filesize / post_max_size di php.ini).',
            'berkas.uploaded' => 'Berkas gagal diunggah. Kemungkinan melebihi batas upload PHP (upload_max_filesize / post_max_size di php.ini).',
            'berkas.mimes' => 'Berkas harus berformat .xlsx atau .xls.',
            'berkas.max' => 'Ukuran berkas maksimal 20 MB.',
        ]);

        if (! class_exists(IOFactory::class)) {
            return back()->withErrors([
                'berkas' => 'Paket pembaca Excel belum terpasang. Jalankan: composer require phpoffice/phpspreadsheet',
            ]);
        }

        @set_time_limit(900);
        @ini_set('memory_limit', '1024M');

        // Satu impor pada satu waktu: klik ganda atau dua orang sekaligus tidak saling menimpa.
        $kunci = null;
        try {
            $kunci = Cache::lock('impor-pemohon', 900);
            if (! $kunci->get()) {
                return back()->withErrors([
                    'berkas' => 'Masih ada impor lain yang sedang berjalan. Tunggu sampai selesai, lalu coba lagi.',
                ]);
            }
        } catch (\Throwable $e) {
            $kunci = null; // penyimpanan cache tidak mendukung kunci: lanjut tanpa kunci
        }

        try {
            $hasil = ImporPemohon::proses($this->bacaExcel($request->file('berkas')->getRealPath()));
            $ringkas = $this->simpan($hasil['records']);
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors([
                'berkas' => 'Impor dibatalkan dan tidak ada data yang berubah. Penyebab: '.$e->getMessage(),
            ]);
        } finally {
            $kunci?->release();
        }

        $baru = $ringkas['baru'];
        $diperbarui = $ringkas['diperbarui'];
        $tidakBerubah = $ringkas['sama'];
        $perubahanTampil = $ringkas['perubahan'];
        $gagalTampil = array_slice($hasil['gagal'], 0, 200);

        return view('impor.hasil', compact('hasil', 'baru', 'diperbarui', 'tidakBerubah', 'perubahanTampil', 'gagalTampil'));
    }

    /**
     * Membandingkan setiap data dengan yang sudah ada (kunci: ID + bulan + tahun + jenis):
     *  - ID belum ada            => ditambahkan
     *  - ID ada, isi berbeda     => diperbarui
     *  - ID ada, isi sama persis => dilewati (tidak ditulis ulang)
     * Semuanya dalam satu transaksi: kalau ada yang gagal, tidak ada yang berubah.
     *
     * @param  array<int, array<string, mixed>>  $records
     * @return array{baru: int, diperbarui: int, sama: int, perubahan: array<int, array<string, mixed>>}
     */
    private function simpan(array $records): array
    {
        $perGrup = [];
        foreach ($records as $r) {
            $perGrup[ImporPemohon::kunciGrup($r['tahun'], $r['bulan'], $r['jenis'])][] = $r;
        }
        unset($records);

        $ringkas = ['baru' => 0, 'diperbarui' => 0, 'sama' => 0, 'perubahan' => []];

        DB::transaction(function () use ($perGrup, &$ringkas) {
            $stempel = now()->toDateTimeString();

            foreach ($perGrup as $grup => $isi) {
                [$tahun, $bulan, $jenis] = explode('|', (string) $grup);

                // Ambil dulu SEMUA data lama kelompok ini sampai habis, baru menulis.
                $ada = [];
                $baris = DB::table('pemohons')
                    ->where('tahun', (int) $tahun)
                    ->where('bulan', (int) $bulan)
                    ->where('jenis', $jenis)
                    ->select('id', 'nomor', 'nama', 'tanggal_lahir', 'tanggal_masuk', 'umur')
                    ->cursor();

                foreach ($baris as $b) {
                    $nomor = ImporPemohon::normalNomor($b->nomor);
                    if ($nomor === null) {
                        continue;
                    }
                    $ada[$nomor] = [
                        'id' => (int) $b->id,
                        'nama' => (string) $b->nama,
                        'tanggal_lahir' => $b->tanggal_lahir !== null ? (string) $b->tanggal_lahir : null,
                        'tanggal_masuk' => $b->tanggal_masuk !== null ? (string) $b->tanggal_masuk : null,
                        'umur' => $b->umur !== null ? (int) $b->umur : null,
                    ];
                }

                $banding = ImporPemohon::bandingkan($isi, $ada);
                unset($ada);

                // 1) Data baru. Upsert (bukan insert biasa) sebagai pengaman kalau database
                //    menganggap dua ID itu sama (mis. beda huruf besar/kecil).
                foreach (array_chunk($banding['baru'], self::UKURAN_POTONGAN) as $potongan) {
                    $potongan = array_map(fn ($r) => $r + ['created_at' => $stempel, 'updated_at' => $stempel], $potongan);

                    DB::table('pemohons')->upsert(
                        $potongan,
                        ['nomor', 'tahun', 'bulan', 'jenis'],
                        ['nama', 'tanggal_lahir', 'tanggal_masuk', 'umur', 'updated_at']
                    );
                }

                // 2) Data lama yang isinya berbeda: diperbarui berdasarkan id barisnya.
                foreach (array_chunk($banding['ubah'], self::UKURAN_POTONGAN) as $potongan) {
                    $potongan = array_map(
                        fn ($u) => ['id' => $u['id']] + $u['baru'] + ['created_at' => $stempel, 'updated_at' => $stempel],
                        $potongan
                    );

                    DB::table('pemohons')->upsert(
                        $potongan,
                        ['id'],
                        ['nama', 'tanggal_lahir', 'tanggal_masuk', 'umur', 'updated_at']
                    );
                }

                // Contoh perubahan untuk ditampilkan (maksimal 50) agar bisa dicek manusia.
                foreach ($banding['ubah'] as $u) {
                    if (count($ringkas['perubahan']) >= 50) {
                        break;
                    }
                    $ringkas['perubahan'][] = [
                        'nomor' => $u['baru']['nomor'],
                        'jenis' => $u['baru']['jenis'],
                        'bulan' => $u['baru']['bulan'],
                        'tahun' => $u['baru']['tahun'],
                        'nama_lama' => $u['lama']['nama'],
                        'nama_baru' => $u['baru']['nama'],
                        'lahir_lama' => $u['lama']['tanggal_lahir'],
                        'lahir_baru' => $u['baru']['tanggal_lahir'],
                        'masuk_lama' => $u['lama']['tanggal_masuk'] ?? null,
                        'masuk_baru' => $u['baru']['tanggal_masuk'] ?? null,
                    ];
                }

                $ringkas['baru'] += count($banding['baru']);
                $ringkas['diperbarui'] += count($banding['ubah']);
                $ringkas['sama'] += $banding['sama'];
            }
        });

        return $ringkas;
    }

    /**
     * Membaca tab satu per satu (bukan seluruh berkas sekaligus) dan hanya kolom A sampai D,
     * supaya berkas puluhan ribu baris tidak menghabiskan memori. Tab yang namanya tidak
     * dikenali tidak dimuat sama sekali, hanya dilaporkan sebagai dilewati.
     *
     * Menghasilkan: ['nama' => 'AGUSTUS 2026 MP', 'baris' => [[A, B, C, D], ...]]
     * Nilai dibaca mentah (tanggal Excel berupa angka seri), lalu diolah ImporPemohon.
     */
    private function bacaExcel(string $path): Generator
    {
        $namaTab = IOFactory::createReaderForFile($path)->listWorksheetNames($path);

        $saring = new class implements IReadFilter
        {
            public function readCell($columnAddress, $row, $worksheetName = ''): bool
            {
                return in_array($columnAddress, ['A', 'B', 'C', 'D'], true);
            }
        };

        foreach ($namaTab as $nama) {
            if (ImporPemohon::parseNamaTab($nama) === null) {
                yield ['nama' => $nama, 'baris' => []];

                continue;
            }

            $pembaca = IOFactory::createReaderForFile($path);
            $pembaca->setReadDataOnly(true);
            $pembaca->setLoadSheetsOnly([$nama]);
            $pembaca->setReadFilter($saring);

            $buku = $pembaca->load($path);
            $lembar = $buku->getSheetByName($nama) ?? $buku->getSheet(0);
            $baris = $lembar->toArray(null, true, false, false);

            $buku->disconnectWorksheets();
            unset($buku, $lembar, $pembaca);
            gc_collect_cycles();

            yield ['nama' => $nama, 'baris' => $baris];
        }
    }
}
