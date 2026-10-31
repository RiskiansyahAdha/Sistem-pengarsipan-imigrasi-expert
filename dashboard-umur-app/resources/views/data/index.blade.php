@extends('layouts.dasbor')

@section('title', 'Data')

@section('content')
@php
    $namaBulan = \App\Support\StatistikUmur::NAMA_BULAN;
    $namaJenis = \App\Support\StatistikUmur::JENIS;
@endphp

<div class="baris-judul">
    <div><h1>Data Pemohon</h1><div class="sub-judul">Daftar pemohon hasil impor, dapat dicari dan disaring</div></div>
    <a href="{{ route('impor.form') }}" class="btn">Impor Excel</a>
</div>

@if ($errors->any())
    <div class="alert err">
        @foreach ($errors->all() as $pesan)
            <div>{{ $pesan }}</div>
        @endforeach
    </div>
@endif

<form method="GET" action="{{ route('data.index') }}" class="filter">
    <input type="text" name="nomor" placeholder="ID" value="{{ request('nomor') }}" style="width: 120px">
    <input type="text" name="nama" placeholder="Cari nama" value="{{ request('nama') }}">

    <select name="jenis">
        <option value="">Semua jenis</option>
        @foreach ($namaJenis as $kode => $label)
            <option value="{{ $kode }}" @selected(request('jenis') == $kode)>{{ $label }}</option>
        @endforeach
    </select>

    <select name="bulan">
        <option value="">Semua bulan</option>
        @foreach ($namaBulan as $angka => $nama)
            <option value="{{ $angka }}" @selected(request('bulan') == $angka)>{{ $nama }}</option>
        @endforeach
    </select>

    <select name="tahun">
        <option value="">Semua tahun</option>
        @foreach ($tahunTersedia as $t)
            <option value="{{ $t }}" @selected(request('tahun') == $t)>{{ $t }}</option>
        @endforeach
    </select>

    <select name="sort">
        @foreach (['id' => 'Urutan masuk', 'nomor' => 'ID', 'nama' => 'Nama', 'tanggal_lahir' => 'Tanggal lahir', 'bulan' => 'Bulan'] as $kunci => $label)
            <option value="{{ $kunci }}" @selected(request('sort', 'id') == $kunci)>Urut: {{ $label }}</option>
        @endforeach
    </select>
    <select name="arah">
        <option value="asc" @selected(request('arah', 'asc') == 'asc')>Naik</option>
        <option value="desc" @selected(request('arah') == 'desc')>Turun</option>
    </select>

    <button type="submit" class="btn">Cari</button>
    <a href="{{ route('data.index') }}" class="btn abu">Reset</a>
</form>

<p class="info">Menampilkan {{ number_format($data->total(), 0, ',', '.') }} data</p>

<div class="tabel-bungkus">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nama</th>
                <th>Tanggal lahir</th>
                <th>Tanggal data masuk</th>
                <th>Jenis</th>
                <th>Bulan</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data as $baris)
                <tr>
                    <td>{{ $baris->nomor ?? '-' }}</td>
                    <td>{{ $baris->nama }}</td>
                    <td>{{ $baris->tanggal_lahir?->format('d-m-Y') ?? '-' }}</td>
                    <td>@if ($baris->tanggal_masuk)<span>{{ $baris->tanggal_masuk_label }}</span>@else<span class="teks-abu">Tidak ada data masuk</span>@endif</td>
                    <td>
                        @if ($baris->jenis)
                            <span class="lencana {{ strtolower($baris->jenis) }}">{{ $baris->jenis_label }}</span>
                        @else
                            -
                        @endif
                    </td>
                    <td>{{ $baris->nama_bulan }} {{ $baris->tahun }}</td>
                    <td>
                        <a href="{{ route('data.show', $baris) }}">Lihat</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">Data tidak ditemukan.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $data->links('partials.pagination') }}
@endsection
