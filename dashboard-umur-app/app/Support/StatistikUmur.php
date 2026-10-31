<?php

namespace App\Support;

/**
 * Menghitung seluruh statistik dashboard dari data umur + bulan (+ jenis).
 *
 * Sengaja berupa PHP biasa (tanpa Eloquent/Collection) supaya mudah dibaca
 * dan diuji. Controller cukup mengambil data lalu memanggil hitung().
 */
class StatistikUmur
{
    public const KELOMPOK = ['0-17', '18-25', '26-35', '36-45', '46-60', '60+'];

    /** Jenis layanan: kode di akhir nama tab => nama tampilan. */
    public const JENIS = ['P' => 'Prioritas', 'MP' => 'Non prioritas'];

    public const NAMA_BULAN = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    /** Nama hari: nomor hari ISO-8601 (1 = Senin ... 7 = Minggu). */
    public const HARI = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];

    public const BULAN_SINGKAT = [
        'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
        'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des',
    ];

    public static function kelompok(int $umur): string
    {
        return match (true) {
            $umur < 18 => '0-17',
            $umur <= 25 => '18-25',
            $umur <= 35 => '26-35',
            $umur <= 45 => '36-45',
            $umur <= 60 => '46-60',
            default => '60+',
        };
    }

    /**
     * @param  array<int, array{umur: int|string, bulan: int|string, jenis?: ?string}>  $rows
     * @param  int  $bulanAcuan  bulan yang dibandingkan dengan bulan sebelumnya (1-12)
     */
    public static function hitung(array $rows, int $bulanAcuan = 12): array
    {
        $umur = [];
        $perKelompok = array_fill_keys(self::KELOMPOK, 0);
        $perBulan = array_fill(1, 12, 0);
        $totalUmurBulan = array_fill(1, 12, 0);
        $matriks = [];
        foreach (self::KELOMPOK as $k) {
            $matriks[$k] = array_fill(1, 12, 0);
        }

        $perJenisBulan = [];
        $perJenisKelompok = [];
        foreach (array_keys(self::JENIS) as $kode) {
            $perJenisBulan[$kode] = array_fill(1, 12, 0);
            $perJenisKelompok[$kode] = array_fill_keys(self::KELOMPOK, 0);
        }

        foreach ($rows as $row) {
            $u = (int) $row['umur'];
            $b = (int) $row['bulan'];
            if ($b < 1 || $b > 12) {
                continue;
            }
            $k = self::kelompok($u);
            $umur[] = $u;
            $perKelompok[$k]++;
            $perBulan[$b]++;
            $totalUmurBulan[$b] += $u;
            $matriks[$k][$b]++;

            $kodeJenis = $row['jenis'] ?? null;
            if (is_string($kodeJenis) && isset($perJenisBulan[$kodeJenis])) {
                $perJenisBulan[$kodeJenis][$b]++;
                $perJenisKelompok[$kodeJenis][$k]++;
            }
        }

        sort($umur);
        $total = count($umur);

        // ---- Ringkasan angka ----
        $ringkasan = [
            'total' => $total,
            'rata2' => null,
            'median' => null,
            'modus' => [],
            'termuda' => null,
            'tertua' => null,
        ];

        $histLabel = [];
        $histNilai = [];

        if ($total > 0) {
            $tengah = intdiv($total, 2);
            $median = $total % 2 === 1
                ? $umur[$tengah]
                : ($umur[$tengah - 1] + $umur[$tengah]) / 2;

            $hitungan = array_count_values($umur);
            $maks = max($hitungan);
            $modus = array_keys(array_filter($hitungan, fn ($c) => $c === $maks));
            sort($modus);

            $ringkasan['rata2'] = round(array_sum($umur) / $total, 1);
            $ringkasan['median'] = ($median == floor($median)) ? (int) $median : $median;
            $ringkasan['modus'] = array_map('intval', $modus);
            $ringkasan['termuda'] = $umur[0];
            $ringkasan['tertua'] = $umur[$total - 1];

            // Histogram: setiap angka umur dari termuda sampai tertua.
            for ($u = $umur[0]; $u <= $umur[$total - 1]; $u++) {
                $histLabel[] = $u;
                $histNilai[] = $hitungan[$u] ?? 0;
            }
        }

        // ---- Rata-rata umur per bulan (null kalau bulan itu kosong) ----
        $rata2Bulan = [];
        foreach (range(1, 12) as $b) {
            $rata2Bulan[] = $perBulan[$b] > 0
                ? round($totalUmurBulan[$b] / $perBulan[$b], 1)
                : null;
        }

        // ---- Bulan acuan vs bulan sebelumnya ----
        $acuan = min(max($bulanAcuan, 1), 12);
        $jumlahAcuan = $perBulan[$acuan];
        $jumlahSebelum = $acuan > 1 ? $perBulan[$acuan - 1] : null;
        $perubahan = ($jumlahSebelum !== null && $jumlahSebelum > 0)
            ? round(($jumlahAcuan - $jumlahSebelum) / $jumlahSebelum * 100, 1)
            : null;

        // ---- Bulan tersibuk ----
        $tersibuk = null;
        if ($total > 0) {
            $maksBulan = max($perBulan);
            $bulanTersibuk = array_search($maksBulan, $perBulan, true);
            $tersibuk = [
                'bulan' => self::NAMA_BULAN[$bulanTersibuk],
                'jumlah' => $maksBulan,
            ];
        }

        // ---- Dataset batang bertumpuk: kelompok umur x bulan ----
        $datasetMatriks = [];
        foreach (self::KELOMPOK as $k) {
            $datasetMatriks[] = [
                'label' => $k,
                'data' => array_values($matriks[$k]),
            ];
        }

        // ---- Perbandingan Prioritas vs Non prioritas ----
        $jenisTotal = [];
        $jenisBulan = [];
        $jenisKelompok = [];
        foreach (self::JENIS as $kode => $label) {
            $jenisTotal[$kode] = array_sum($perJenisBulan[$kode]);
            $jenisBulan[] = ['label' => $label, 'data' => array_values($perJenisBulan[$kode])];
            $jenisKelompok[] = ['label' => $label, 'data' => array_values($perJenisKelompok[$kode])];
        }

        return [
            'ringkasan' => $ringkasan,
            'kelompok_label' => self::KELOMPOK,
            'kelompok_nilai' => array_values($perKelompok),
            'bulan_label' => self::BULAN_SINGKAT,
            'bulan_nilai' => array_values($perBulan),
            'rata2_bulan' => $rata2Bulan,
            'matriks' => $datasetMatriks,
            'hist_label' => $histLabel,
            'hist_nilai' => $histNilai,
            'acuan' => [
                'bulan' => self::NAMA_BULAN[$acuan],
                'jumlah' => $jumlahAcuan,
                'sebelum' => $jumlahSebelum,
                'perubahan' => $perubahan,
            ],
            'tersibuk' => $tersibuk,
            'jenis_total' => $jenisTotal,
            'jenis_bulan' => $jenisBulan,
            'jenis_kelompok' => $jenisKelompok,
        ];
    }
}
