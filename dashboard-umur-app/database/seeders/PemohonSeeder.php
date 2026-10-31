<?php

namespace Database\Seeders;

use App\Models\Pemohon;
use App\Support\ImporPemohon;
use Illuminate\Database\Seeder;

/**
 * Data CONTOH (dummy): nomor, nama karangan, tanggal lahir, dan jenis (P / MP).
 * Jangan dipakai untuk data asli kantor.
 */
class PemohonSeeder extends Seeder
{
    public function run(): void
    {
        // Pengaman: jangan menimpa/menambah kalau tabel sudah berisi data.
        if (Pemohon::query()->exists()) {
            $this->command?->warn('Tabel pemohons sudah berisi data, seeder dilewati.');

            return;
        }

        $faker = fake('id_ID');
        $sekarang = now();
        $tahunIni = (int) $sekarang->year;
        $nomor = 13700000;
        $baris = [];

        // Tahun lalu penuh (12 bulan), tahun ini sampai bulan berjalan.
        foreach ([$tahunIni - 1, $tahunIni] as $tahun) {
            $bulanTerakhir = $tahun === $tahunIni ? (int) $sekarang->month : 12;

            for ($i = 0; $i < 150; $i++) {
                $bulan = random_int(1, $bulanTerakhir);
                $lahir = $this->tanggalLahirAcak($tahun, $bulan);
                $hitung = ImporPemohon::umurAtauGalat($lahir, $tahun, $bulan);

                if ($hitung['umur'] === null) {
                    continue;
                }

                $nomor += random_int(1, 9);

                $baris[] = [
                    'nomor' => (string) $nomor,
                    'nama' => mb_strtoupper($faker->name()),
                    'tanggal_lahir' => $lahir->format('Y-m-d'),
                    'jenis' => random_int(1, 100) <= 30 ? 'P' : 'MP',
                    'umur' => $hitung['umur'],
                    'bulan' => $bulan,
                    'tahun' => $tahun,
                    'created_at' => $sekarang,
                    'updated_at' => $sekarang,
                ];
            }
        }

        foreach (array_chunk($baris, 100) as $potongan) {
            Pemohon::insert($potongan);
        }
    }

    /** Tanggal lahir acak yang sebaran umurnya realistis pada akhir bulan tersebut. */
    private function tanggalLahirAcak(int $tahun, int $bulan): \DateTimeImmutable
    {
        $umur = $this->umurAcak();
        $akhir = ImporPemohon::akhirBulan($tahun, $bulan);

        $palingMuda = $akhir->modify("-{$umur} years");
        $palingTua = $akhir->modify('-'.($umur + 1).' years')->modify('+1 day');
        $selisihHari = (int) $palingTua->diff($palingMuda)->days;

        return $palingTua->modify('+'.random_int(0, $selisihHari).' days');
    }

    private function umurAcak(): int
    {
        $r = random_int(1, 100);

        return match (true) {
            $r <= 8 => random_int(1, 17),
            $r <= 38 => random_int(18, 29),
            $r <= 75 => random_int(30, 45),
            $r <= 93 => random_int(46, 60),
            default => random_int(61, 80),
        };
    }
}
