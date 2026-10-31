<?php

namespace App\Http\Controllers;

use App\Models\Pemohon;
use Illuminate\Http\Request;

class PemohonController extends Controller
{
    /** Daftar data + pencarian + filter + urutan. */
    public function index(Request $request)
    {
        $request->validate([
            'nomor' => 'nullable|string|max:30',
            'nama' => 'nullable|string|max:100',
            'jenis' => 'nullable|in:P,MP',
            'bulan' => 'nullable|integer|min:1|max:12',
            'tahun' => 'nullable|integer|min:2000|max:2100',
            'sort' => 'nullable|in:id,nomor,nama,tanggal_lahir,bulan',
            'arah' => 'nullable|in:asc,desc',
        ]);

        $query = Pemohon::query();

        if ($request->filled('nomor')) {
            $query->where('nomor', trim($request->input('nomor')));
        }
        if ($request->filled('nama')) {
            $query->where('nama', 'like', '%'.$request->input('nama').'%');
        }
        if ($request->filled('jenis')) {
            $query->where('jenis', $request->input('jenis'));
        }
        if ($request->filled('bulan')) {
            $query->where('bulan', $request->integer('bulan'));
        }
        if ($request->filled('tahun')) {
            $query->where('tahun', $request->integer('tahun'));
        }

        $sort = $request->input('sort', 'id');
        $arah = $request->input('arah', 'asc'); // sudah divalidasi: hanya asc / desc

        if ($sort === 'nomor') {
            // Nomor disimpan sebagai teks, jadi diurutkan sebagai angka.
            $query->orderByRaw('CAST(nomor AS UNSIGNED) '.$arah)->orderBy('id');
        } else {
            $query->orderBy($sort, $arah)->orderBy('id');
        }

        $data = $query->paginate(15)->withQueryString();

        $tahunTersedia = Pemohon::query()->distinct()->orderByDesc('tahun')->pluck('tahun');

        return view('data.index', compact('data', 'tahunTersedia'));
    }

    public function show(Pemohon $pemohon)
    {
        return view('data.show', compact('pemohon'));
    }
}
