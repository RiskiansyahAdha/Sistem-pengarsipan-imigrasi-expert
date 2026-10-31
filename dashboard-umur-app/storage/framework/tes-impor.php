<?php

use App\Support\ImporPemohon;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$r = ImporPemohon::proses([
    ['nama' => 'AGUSTUS 2026 MP', 'baris' => [
        ['NO', 'NAMA', 'TGL LAHIR', 'TGL MASUK'],
        [1, 'Budi', '01-05-1990', '15-10-2026'],
        [2, 'Siti', '02-06-1985', null],        // kolom D kosong (data lama)
        [3, 'Ani', '03-07-1992', 'teks aneh'],  // kolom D tidak valid
    ]],
]);
echo json_encode([
    'valid' => $r['total_valid'],
    'gagal' => count($r['gagal']),
    'rekam' => array_map(fn ($x) => ['nama' => $x['nama'], 'masuk' => $x['tanggal_masuk']], $r['records']),
]).PHP_EOL;
