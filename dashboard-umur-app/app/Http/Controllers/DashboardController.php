<?php

namespace App\Http\Controllers;

use App\Models\Pemohon;
use App\Support\StatistikUmur;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'tahun' => 'nullable|integer|min:2000|max:2100',
            'jenis' => 'nullable|in:P,MP',
        ]);

        $tahunTersedia = Pemohon::query()->distinct()->orderByDesc('tahun')->pluck('tahun');

        $tahun = (int) ($request->input('tahun') ?: ($tahunTersedia->first() ?? now()->year));
        $jenis = $request->input('jenis') ?: null;

        // Daftar pilihan tahun: tahun yang ada di data + tahun yang sedang dipilih.
        $pilihanTahun = $tahunTersedia->push($tahun)->unique()->sortDesc()->values();

        // Tahun berjalan dibandingkan sampai bulan ini, tahun lain sampai Desember.
        $bulanAcuan = $tahun === (int) now()->year ? (int) now()->month : 12;

        $query = Pemohon::where('tahun', $tahun);
        if ($jenis) {
            $query->where('jenis', $jenis);
        }

        $rows = $query->get(['umur', 'bulan', 'jenis'])->toArray();
        $stat = StatistikUmur::hitung($rows, $bulanAcuan);

        return view('statistik.dashboard', compact('stat', 'tahun', 'jenis', 'pilihanTahun'));
    }
}
