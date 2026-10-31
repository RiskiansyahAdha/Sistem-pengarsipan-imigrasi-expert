<?php

namespace App\Support;

use DateTimeImmutable;
use DateTimeInterface;

/**
 * Logika impor data pemohon dari Excel. Sengaja PHP biasa (tanpa Laravel/Excel)
 * supaya mudah diuji: terima data tab dalam bentuk array, kembalikan hasil olahan.
 *
 * Format yang dibaca:
 *  - Nama tab  : "<BULAN> <TAHUN> <P|MP>", contoh "AGUSTUS 2026 MP"
 *                (P = Prioritas, MP = Non prioritas)
 *  - Kolom A   : nomor ID
 *  - Kolom B   : nama
 *  - Kolom C   : tanggal lahir (dd-mm-yyyy, atau sel tanggal Excel)
 *  - Kolom D   : tanggal data dimasukkan (boleh kosong / belum ada di berkas lama).
 *                Kosong ATAU tidak bisa dibaca => disimpan null, ditampilkan
 *                "Tidak ada data masuk" (TIDAK dihitung gagal).
 *  - Umur dihitung dari tanggal lahir pada AKHIR bulan di nama tab.
 */
class ImporPemohon
{
    private const BULAN = [
        'JAN' => 1, 'FEB' => 2, 'MAR' => 3, 'APR' => 4, 'MEI' => 5, 'MAY' => 5,
        'JUN' => 6, 'JUL' => 7, 'AGU' => 8, 'AGS' => 8, 'AUG' => 8, 'SEP' => 9,
        'OKT' => 10, 'OCT' => 10, 'NOV' => 11, 'NOP' => 11, 'DES' => 12, 'DEC' => 12,
    ];

    /** "AGUSTUS 2026 MP" => ['bulan' => 8, 'tahun' => 2026, 'jenis' => 'MP'], atau null. */
    public static function parseNamaTab(string $nama): ?array
    {
        if (! preg_match('/^\s*([A-Za-z]+)\s+(\d{4})\s+(MP|P)\s*$/i', $nama, $m)) {
            return null;
        }

        $bulan = self::BULAN[strtoupper(substr($m[1], 0, 3))] ?? null;
        $tahun = (int) $m[2];

        if ($bulan === null || $tahun < 2000 || $tahun > 2100) {
            return null;
        }

        return ['bulan' => $bulan, 'tahun' => $tahun, 'jenis' => strtoupper($m[3])];
    }

    /**
     * Membaca tanggal dari: teks (dd-mm-yyyy, dd/mm/yyyy, dd.mm.yyyy, yyyy-mm-dd),
     * angka seri tanggal Excel, atau objek tanggal. Tanggal tidak valid (31-02-2007) => null.
     */
    public static function parseTanggal(mixed $nilai): ?DateTimeImmutable
    {
        if ($nilai instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($nilai)->setTime(0, 0);
        }

        if (is_int($nilai) || is_float($nilai) || (is_string($nilai) && is_numeric(trim($nilai)))) {
            $seri = (float) (is_string($nilai) ? trim($nilai) : $nilai);
            if ($seri >= 1 && $seri <= 80000) {
                $hari = (int) floor($seri);

                return (new DateTimeImmutable('1899-12-30'))->modify("+{$hari} days");
            }

            return null;
        }

        if (! is_string($nilai)) {
            return null;
        }

        $teks = trim($nilai);
        if ($teks === '') {
            return null;
        }

        foreach (['!d-m-Y', '!d/m/Y', '!d.m.Y', '!Y-m-d'] as $format) {
            $tgl = DateTimeImmutable::createFromFormat($format, $teks);
            $galat = DateTimeImmutable::getLastErrors();
            $bersih = ! $galat || ($galat['warning_count'] === 0 && $galat['error_count'] === 0);

            if ($tgl !== false && $bersih) {
                $tahun = (int) $tgl->format('Y');
                if ($tahun >= 1900 && $tahun <= 2100) {
                    return $tgl;
                }
            }
        }

        return null;
    }

    /** Tanggal terakhir pada bulan tersebut. */
    public static function akhirBulan(int $tahun, int $bulan): DateTimeImmutable
    {
        return (new DateTimeImmutable(sprintf('%04d-%02d-01', $tahun, $bulan)))
            ->modify('last day of this month');
    }

    /** @return array{umur: ?int, galat: ?string} */
    public static function umurAtauGalat(DateTimeImmutable $lahir, int $tahun, int $bulan): array
    {
        $akhir = self::akhirBulan($tahun, $bulan);

        if ($lahir > $akhir) {
            return ['umur' => null, 'galat' => 'Tanggal lahir setelah bulan permohonan'];
        }

        $umur = $lahir->diff($akhir)->y;

        if ($umur > 120) {
            return ['umur' => null, 'galat' => 'Umur tidak masuk akal (lebih dari 120 tahun)'];
        }

        return ['umur' => $umur, 'galat' => null];
    }

    /**
     * @param  array<int, array{nama: string, baris: array<int, array<int, mixed>>}>  $tabs
     * @return array{
     *   records: array<int, array<string, mixed>>,
     *   tab: array<int, array<string, mixed>>,
     *   tab_dilewati: array<int, array{nama: string, alasan: string}>,
     *   gagal: array<int, array{tab: string, baris: int, alasan: string}>,
     *   duplikat: int, duplikat_identik: int, duplikat_beda: int,
     *   duplikat_detail: array<int, array<string, mixed>>,
     *   total_baris: int, total_valid: int
     * }
     */
    public static function proses(iterable $tabs): array
    {
        $hasil = [
            'records' => [],
            'tab' => [],
            'tab_dilewati' => [],
            'gagal' => [],
            'duplikat' => 0,
            'duplikat_identik' => 0,   // ID ganda dengan data sama persis (aman, digabung)
            'duplikat_beda' => 0,      // ID ganda dengan nama/tanggal lahir berbeda (perlu dicek)
            'duplikat_detail' => [],   // maksimal 200 contoh
            'total_baris' => 0,
            'total_valid' => 0,
        ];
        $peta = [];
        $asal = [];

        foreach ($tabs as $tab) {
            $namaTab = (string) ($tab['nama'] ?? '');
            $info = self::parseNamaTab($namaTab);

            if ($info === null) {
                $hasil['tab_dilewati'][] = [
                    'nama' => $namaTab,
                    'alasan' => 'Nama tab tidak dikenali. Format yang benar: BULAN TAHUN P atau MP, contoh "AGUSTUS 2026 MP".',
                ];

                continue;
            }

            $ringkas = ['nama' => $namaTab] + $info + ['diproses' => 0, 'valid' => 0, 'gagal' => 0];

            foreach (array_values($tab['baris'] ?? []) as $i => $baris) {
                $noBaris = $i + 1;
                $baris = is_array($baris) ? $baris : [];
                $a = $baris[0] ?? null;
                $b = $baris[1] ?? null;
                $c = $baris[2] ?? null;
                $d = $baris[3] ?? null;

                if (self::kosong($a) && self::kosong($b) && self::kosong($c) && self::kosong($d)) {
                    continue;
                }

                // Baris judul (mis. "NO | NAMA | TGL LAHIR") dilewati tanpa dihitung gagal.
                if ($i === 0 && ! is_numeric($a) && self::parseTanggal($c) === null) {
                    continue;
                }

                $ringkas['diproses']++;
                $hasil['total_baris']++;

                $galat = null;
                $nomor = self::normalNomor($a);
                $bersihNama = preg_replace('/\s+/u', ' ', is_scalar($b) ? (string) $b : '');
                $nama = trim($bersihNama ?? '');
                $lahir = self::parseTanggal($c);
                $umur = null;

                if ($nomor === null) {
                    $galat = 'Nomor ID (kolom A) kosong';
                } elseif (mb_strlen($nomor) > 30) {
                    $galat = 'Nomor ID (kolom A) terlalu panjang';
                } elseif ($nama === '') {
                    $galat = 'Nama (kolom B) kosong';
                } elseif ($lahir === null) {
                    $teks = is_scalar($c) ? mb_substr((string) $c, 0, 30) : '';
                    $galat = self::kosong($c)
                        ? 'Tanggal lahir (kolom C) kosong'
                        : "Tanggal lahir (kolom C) tidak valid: \"{$teks}\"";
                } else {
                    $cek = self::umurAtauGalat($lahir, $info['tahun'], $info['bulan']);
                    $galat = $cek['galat'];
                    $umur = $cek['umur'];
                }

                // Kolom D: tanggal data dimasukkan. Kosong/tidak valid => null,
                // ditampilkan "Tidak ada data masuk" — BUKAN galat.
                $masuk = self::parseTanggal($d);

                if ($galat !== null) {
                    $ringkas['gagal']++;
                    $hasil['gagal'][] = ['tab' => $namaTab, 'baris' => $noBaris, 'alasan' => $galat];

                    continue;
                }

                $ringkas['valid']++;
                $hasil['total_valid']++;

                $kunci = $nomor.'|'.$info['tahun'].'|'.$info['bulan'].'|'.$info['jenis'];

                $rekam = [
                    'nomor' => $nomor,
                    'nama' => mb_substr($nama, 0, 100),
                    'tanggal_lahir' => $lahir->format('Y-m-d'),
                    'tanggal_masuk' => $masuk?->format('Y-m-d'),
                    'jenis' => $info['jenis'],
                    'umur' => $umur,
                    'bulan' => $info['bulan'],
                    'tahun' => $info['tahun'],
                ];

                if (isset($peta[$kunci])) {
                    // Nomor yang sama di tab yang sama: yang terakhir dipakai, tapi dilaporkan.
                    $hasil['duplikat']++;
                    $identik = $peta[$kunci]['nama'] === $rekam['nama']
                        && $peta[$kunci]['tanggal_lahir'] === $rekam['tanggal_lahir'];
                    $identik ? $hasil['duplikat_identik']++ : $hasil['duplikat_beda']++;

                    if (count($hasil['duplikat_detail']) < 200) {
                        $hasil['duplikat_detail'][] = [
                            'tab' => $namaTab,
                            'nomor' => $nomor,
                            'baris' => $noBaris,
                            'baris_sebelumnya' => $asal[$kunci][1],
                            'tab_sebelumnya' => $asal[$kunci][0],
                            'identik' => $identik,
                        ];
                    }
                }

                $peta[$kunci] = $rekam;
                $asal[$kunci] = [$namaTab, $noBaris];
            }

            $hasil['tab'][] = $ringkas;
        }

        $hasil['records'] = array_values($peta);

        return $hasil;
    }

    private static function kosong(mixed $nilai): bool
    {
        return $nilai === null || (is_string($nilai) && trim($nilai) === '');
    }

    /**
     * Menyeragamkan nomor ID supaya ID yang "sama" selalu dikenali sama, walau penulisannya
     * berbeda antar impor: spasi (termasuk spasi tak terlihat) dibuang, huruf besar, "123.0"
     * jadi "123", dan angka nol di depan pada ID yang seluruhnya angka dibuang ("00123" = "123").
     */
    public static function normalNomor(mixed $nilai): ?string
    {
        if (is_int($nilai)) {
            $teks = (string) $nilai;
        } elseif (is_float($nilai)) {
            $teks = floor($nilai) == $nilai ? number_format($nilai, 0, '', '') : (string) $nilai;
        } elseif (is_string($nilai)) {
            $teks = $nilai;
        } else {
            return null;
        }

        $teks = preg_replace('/[\s\x{00A0}\x{200B}\x{FEFF}]+/u', '', $teks) ?? '';
        if ($teks === '') {
            return null;
        }

        if (preg_match('/^\d+\.0+$/', $teks)) {
            $teks = explode('.', $teks)[0];
        }

        $teks = mb_strtoupper($teks);

        if (ctype_digit($teks)) {
            $teks = ltrim($teks, '0');
            if ($teks === '') {
                $teks = '0';
            }
        }

        return $teks;
    }

    /** Kunci kelompok: tahun|bulan|jenis, dipakai untuk mengambil data lama per tab. */
    public static function kunciGrup(int $tahun, int $bulan, string $jenis): string
    {
        return $tahun.'|'.$bulan.'|'.$jenis;
    }

    /**
     * Membandingkan data hasil baca Excel dengan data yang sudah ada di database
     * (untuk SATU kelompok tahun|bulan|jenis).
     *
     * @param  array<int, array<string, mixed>>  $records  hasil proses()
     * @param  array<string, array{id: int, nama: string, tanggal_lahir: ?string, umur: ?int}>  $ada  nomor ternormalisasi => data lama
     * @return array{baru: array<int, array<string, mixed>>, ubah: array<int, array<string, mixed>>, sama: int}
     */
    public static function bandingkan(array $records, array $ada): array
    {
        $baru = [];
        $ubah = [];
        $sama = 0;

        foreach ($records as $r) {
            $lama = $ada[$r['nomor']] ?? null;

            if ($lama === null) {
                $baru[] = $r;

                continue;
            }

            if ($lama['nama'] === $r['nama']
                && $lama['tanggal_lahir'] === $r['tanggal_lahir']
                && ($lama['tanggal_masuk'] ?? null) === $r['tanggal_masuk']
                && (int) $lama['umur'] === (int) $r['umur']) {
                $sama++; // ID sama, isi sama persis: tidak perlu disentuh

                continue;
            }

            $ubah[] = ['id' => $lama['id'], 'baru' => $r, 'lama' => $lama];
        }

        return ['baru' => $baru, 'ubah' => $ubah, 'sama' => $sama];
    }
}
