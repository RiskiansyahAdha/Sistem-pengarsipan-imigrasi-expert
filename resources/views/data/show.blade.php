@extends('layouts.dasbor')

@section('title', 'Detail Data '.($pemohon->nomor ?? '#'.$pemohon->id))

@section('content')
<div class="baris-judul">
    <h1>Detail Data {{ $pemohon->nomor ?? '#'.$pemohon->id }}</h1>
    <div>
        <a href="{{ route('data.index') }}" class="btn abu">Kembali</a>
    </div>
</div>

<div class="detail">
    <dl>
        <div class="item"><dt>ID</dt><dd>{{ $pemohon->nomor ?? '-' }}</dd></div>
        <div class="item"><dt>Nama</dt><dd>{{ $pemohon->nama }}</dd></div>
        <div class="item"><dt>Tanggal lahir</dt><dd>{{ $pemohon->tanggal_lahir?->format('d-m-Y') ?? '-' }}</dd></div>
        <div class="item"><dt>Tanggal data masuk</dt><dd>{{ $pemohon->tanggal_masuk_label }}</dd></div>
        <div class="item"><dt>Jenis</dt><dd>{{ $pemohon->jenis_label }}</dd></div>
        <div class="item"><dt>Bulan</dt><dd>{{ $pemohon->nama_bulan }} {{ $pemohon->tahun }}</dd></div>
        <div class="item"><dt>Terakhir diubah</dt><dd>{{ $pemohon->updated_at?->format('d-m-Y H:i') }}</dd></div>
    </dl>
</div>
@endsection
