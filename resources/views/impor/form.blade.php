@extends('layouts.dasbor')

@section('title', 'Impor Data')

@section('content')
<div class="baris-judul">
    <h1>Impor Data dari Excel</h1>
</div>

<div class="halaman-lebar">
    <div class="catatan">
    <strong>Panduan & Format Berkas:</strong>
    <ul>
        <li><strong>Nama Tab/Sheet:</strong> Format nama tab harus <code>BULAN TAHUN P</code> (Prioritas) atau <code>BULAN TAHUN MP</code> (Non-Prioritas). Contoh: <code>AGUSTUS 2026 MP</code>. Tab lain akan otomatis dilewati.</li>
        <li><strong>Format Kolom:</strong> Kolom A = Nomor ID, Kolom B = Nama, Kolom C = Tanggal Lahir (contoh: <code>15-02-2007</code>).</li>
        <li><strong>Perhitungan Umur:</strong> Umur dihitung dari tanggal lahir hingga akhir bulan yang tertera pada nama tab.</li>
        <li><strong>Sistem Impor Aman:</strong>
            <ul>
                <li>ID baru akan <strong>ditambahkan</strong>.</li>
                <li>ID sama tapi ada perubahan nama/tgl lahir akan <strong>diperbarui</strong>.</li>
                <li>Data yang persis sama akan <strong>dilewati</strong>.</li>
                <li>Format ID tidak sensitif huruf besar/kecil, spasi, maupun angka nol di depan (contoh: <code>00123</code> = <code>123</code>).</li>
            </ul>
        </li>
        <li><strong>Keamanan Data:</strong> Jika terjadi error saat impor, seluruh proses dibatalkan otomatis agar data tidak berantakan. Baris bermasalah akan dilaporkan.</li>
    </ul>

    <hr style="margin: 10px 0; border: 0; border-top: 1px solid #ccc;">

    <p style="margin: 5px 0; font-size: 0.9em; color: #555;">
        Website dikembangkan oleh <strong>Rizkiansyah Adha</strong> &copy; 2026 SMKN 5 Telkom Banda Aceh.<br>
        Jika ada kendala, error, atau bug, silakan hubungi: <strong>082214839047</strong> | <strong>riskiansyahadha@gmail.com</strong>
    </p>
</div>

    <div class="catatan">
        <strong>Cara mengambil berkas dari Google Sheets:</strong>
        buka sheet-nya, lalu <em>File &gt; Unduh &gt; Microsoft Excel (.xlsx)</em>.
        Berkas ini berisi data pribadi pemohon, jadi simpan dan hapus dengan hati-hati.
    </div>

    <form method="POST" action="{{ route('impor.proses') }}" enctype="multipart/form-data" class="edit">
        @csrf

        <label for="berkas">Berkas Excel (.xlsx)</label>
        <input type="file" id="berkas" name="berkas" accept=".xlsx,.xls" required>
        @error('berkas')
            <div class="salah">{{ $message }}</div>
        @enderror

        <p class="info">Proses bisa memakan beberapa detik untuk berkas yang besar. Jangan tutup halaman sebelum hasilnya tampil.</p>

        <div class="aksi">
            <button type="submit" class="btn">Impor</button>
            <a href="{{ route('data.index') }}" class="btn abu">Batal</a>
        </div>
    </form>
</div>
@endsection
