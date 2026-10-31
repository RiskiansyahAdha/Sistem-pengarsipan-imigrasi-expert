@extends('layouts.dasbor')

@section('title', 'Hasil Impor')

@section('content')
@php
    $f = fn ($n) => number_format($n, 0, ',', '.');
    $jumlahGagal = count($hasil['gagal']);
@endphp

<div class="baris-judul">
    <h1>Hasil Impor</h1>
    <div>
        <a href="{{ route('dashboard') }}" class="btn">Lihat dashboard</a>
        <a href="{{ route('data.index') }}" class="btn abu">Lihat data</a>
        <a href="{{ route('impor.form') }}" class="btn abu">Impor lagi</a>
    </div>
</div>

@if (count($hasil['tab']) === 0)
    <div class="alert err">Tidak ada tab yang dikenali di berkas ini, jadi tidak ada data yang masuk. Pastikan nama tab seperti <code>AGUSTUS 2026 MP</code>.</div>
@elseif ($jumlahGagal === 0)
    <div class="alert ok">Impor selesai tanpa baris bermasalah.</div>
@else
    <div class="alert ok">Impor selesai. {{ $f($jumlahGagal) }} baris bermasalah tidak dimasukkan (lihat daftar di bawah).</div>
@endif

<div class="kartu-grid rapat">
    <div class="kartu"><span class="label">Baris dibaca</span><span class="nilai">{{ $f($hasil['total_baris']) }}</span></div>
    <div class="kartu"><span class="label">Data baru</span><span class="nilai">{{ $f($baru) }}</span><span class="sub">ID belum ada, ditambahkan</span></div>
    <div class="kartu"><span class="label">Data diperbarui</span><span class="nilai">{{ $f($diperbarui) }}</span><span class="sub">ID sama, isi berbeda</span></div>
    <div class="kartu"><span class="label">Tidak berubah</span><span class="nilai">{{ $f($tidakBerubah) }}</span><span class="sub">ID sama, isi sama, dilewati</span></div>
    <div class="kartu"><span class="label">Baris gagal</span><span class="nilai">{{ $f($jumlahGagal) }}</span></div>
    @if ($hasil['duplikat'] > 0)
        <div class="kartu"><span class="label">ID ganda dalam berkas</span><span class="nilai">{{ $f($hasil['duplikat']) }}</span><span class="sub">{{ $f($hasil['duplikat_identik']) }} identik, {{ $f($hasil['duplikat_beda']) }} isi berbeda</span></div>
    @endif
</div>

@if (count($hasil['tab']) > 0)
    <h2>Per tab</h2>
    <div class="tabel-bungkus">
        <table>
            <thead>
                <tr><th>Tab</th><th>Bulan</th><th>Jenis</th><th>Dibaca</th><th>Valid</th><th>Gagal</th></tr>
            </thead>
            <tbody>
                @foreach ($hasil['tab'] as $tab)
                    <tr>
                        <td>{{ $tab['nama'] }}</td>
                        <td>{{ \App\Support\StatistikUmur::NAMA_BULAN[$tab['bulan']] }} {{ $tab['tahun'] }}</td>
                        <td><span class="lencana {{ strtolower($tab['jenis']) }}">{{ \App\Support\StatistikUmur::JENIS[$tab['jenis']] }}</span></td>
                        <td>{{ $f($tab['diproses']) }}</td>
                        <td>{{ $f($tab['valid']) }}</td>
                        <td>{{ $f($tab['gagal']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

@if (count($hasil['tab_dilewati']) > 0)
    <h2>Tab yang dilewati</h2>
    <div class="tabel-bungkus">
        <table>
            <thead><tr><th>Tab</th><th>Alasan</th></tr></thead>
            <tbody>
                @foreach ($hasil['tab_dilewati'] as $tab)
                    <tr><td>{{ $tab['nama'] }}</td><td>{{ $tab['alasan'] }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

@if (count($perubahanTampil) > 0)
    <h2>Data yang diperbarui</h2>
    <p class="info">ID sudah ada, tetapi nama atau tanggal lahirnya berbeda dari yang tersimpan, jadi ditimpa dengan isi berkas.
        Periksa kalau ada yang tidak Anda harapkan.
        @if ($diperbarui > count($perubahanTampil)) Menampilkan {{ count($perubahanTampil) }} pertama dari {{ $f($diperbarui) }}. @endif</p>
    <div class="tabel-bungkus">
        <table>
            <thead><tr><th>ID</th><th>Bulan</th><th>Jenis</th><th>Nama (lama &rarr; baru)</th><th>Tgl lahir (lama &rarr; baru)</th><th>Tgl masuk (lama &rarr; baru)</th></tr></thead>
            <tbody>
                @foreach ($perubahanTampil as $u)
                    <tr>
                        <td>{{ $u['nomor'] }}</td>
                        <td>{{ \App\Support\StatistikUmur::NAMA_BULAN[$u['bulan']] }} {{ $u['tahun'] }}</td>
                        <td>{{ $u['jenis'] }}</td>
                        <td>@if ($u['nama_lama'] === $u['nama_baru']) {{ $u['nama_baru'] }} @else {{ $u['nama_lama'] }} &rarr; <strong>{{ $u['nama_baru'] }}</strong> @endif</td>
                        <td>@if ($u['lahir_lama'] === $u['lahir_baru']) {{ \Carbon\Carbon::parse($u['lahir_baru'])->format('d-m-Y') }} @else {{ $u['lahir_lama'] ? \Carbon\Carbon::parse($u['lahir_lama'])->format('d-m-Y') : '-' }} &rarr; <strong>{{ \Carbon\Carbon::parse($u['lahir_baru'])->format('d-m-Y') }}</strong> @endif</td>
                        <td>@if (($u['masuk_lama'] ?? null) === ($u['masuk_baru'] ?? null)) {{ $u['masuk_baru'] ? \Carbon\Carbon::parse($u['masuk_baru'])->format('d-m-Y') : 'Tidak ada data masuk' }} @else {{ $u['masuk_lama'] ? \Carbon\Carbon::parse($u['masuk_lama'])->format('d-m-Y') : 'Tidak ada data masuk' }} &rarr; <strong>{{ $u['masuk_baru'] ? \Carbon\Carbon::parse($u['masuk_baru'])->format('d-m-Y') : 'Tidak ada data masuk' }}</strong> @endif</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

@if (count($hasil['duplikat_detail']) > 0)
    <h2>ID ganda dalam berkas</h2>
    <p class="info">ID yang sama muncul lebih dari sekali di tab yang sama. Baris yang paling bawah yang dipakai.
        <strong>Identik</strong> = nama dan tanggal lahir sama (aman). <strong>Berbeda</strong> = isinya tidak sama, sebaiknya dicek di Excel.
        @if ($hasil['duplikat'] > count($hasil['duplikat_detail'])) Menampilkan {{ count($hasil['duplikat_detail']) }} pertama dari {{ $f($hasil['duplikat']) }}. @endif</p>
    <div class="tabel-bungkus">
        <table>
            <thead><tr><th>Tab</th><th>ID</th><th>Baris</th><th>Sama dengan baris</th><th>Status</th></tr></thead>
            <tbody>
                @foreach ($hasil['duplikat_detail'] as $d)
                    <tr>
                        <td>{{ $d['tab'] }}</td>
                        <td>{{ $d['nomor'] }}</td>
                        <td>{{ $d['baris'] }}</td>
                        <td>{{ $d['baris_sebelumnya'] }}</td>
                        <td>{{ $d['identik'] ? 'Identik' : 'Berbeda' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

@if ($jumlahGagal > 0)
    <h2>Baris yang gagal</h2>
    <p class="info">Nomor baris sesuai nomor baris di Excel.
        @if ($jumlahGagal > count($gagalTampil)) Menampilkan {{ count($gagalTampil) }} pertama dari {{ $f($jumlahGagal) }}. @endif</p>
    <div class="tabel-bungkus">
        <table>
            <thead><tr><th>Tab</th><th>Baris</th><th>Alasan</th></tr></thead>
            <tbody>
                @foreach ($gagalTampil as $g)
                    <tr><td>{{ $g['tab'] }}</td><td>{{ $g['baris'] }}</td><td>{{ $g['alasan'] }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
@endsection
