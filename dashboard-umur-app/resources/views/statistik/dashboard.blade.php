@extends('layouts.dasbor')

@section('title', 'Dashboard')

@section('content')
@php
    $r = $stat['ringkasan'];
    $fmt = fn ($n) => is_null($n) ? '-' : (is_float($n) ? number_format($n, 1, ',', '.') : number_format($n, 0, ',', '.'));
    $acuan = $stat['acuan'];
    $jumlahP = $stat['jenis_total']['P'];
    $jumlahMP = $stat['jenis_total']['MP'];
    $jumlahJenis = $jumlahP + $jumlahMP;
    $persen = fn ($n) => $jumlahJenis > 0 ? number_format($n / $jumlahJenis * 100, 1, ',', '.').'%' : '-';
    $tampilBandingJenis = ! $jenis && $jumlahJenis > 0;
@endphp

<div class="baris-judul">
    <div>
        <h1>Dashboard Statistik Umur</h1>
        <div class="sub-judul">Ringkasan umur pemohon paspor tahun {{ $tahun }}</div>
    </div>
    <form method="GET" action="{{ route('dashboard') }}" class="filter-atas">
        <label for="tahun">Tahun</label>
        <select id="tahun" name="tahun" onchange="this.form.submit()">
            @foreach ($pilihanTahun as $t)
                <option value="{{ $t }}" @selected($t == $tahun)>{{ $t }}</option>
            @endforeach
        </select>

        <label for="jenis">Jenis</label>
        <select id="jenis" name="jenis" onchange="this.form.submit()">
            <option value="">Semua</option>
            @foreach (\App\Support\StatistikUmur::JENIS as $kode => $label)
                <option value="{{ $kode }}" @selected($jenis === $kode)>{{ $label }}</option>
            @endforeach
        </select>
    </form>
</div>

@if ($r['total'] === 0)
    <div class="kosong">
        Belum ada data untuk tahun {{ $tahun }}@if ($jenis) dengan jenis {{ \App\Support\StatistikUmur::JENIS[$jenis] }}@endif.<br>
        Impor data lewat menu <a href="{{ route('impor.form') }}">Impor</a>, atau jalankan <code>php artisan db:seed</code> untuk data contoh.
    </div>
@else
    <div class="kartu-grid" id="kartuGrid">
        <div class="kartu reveal">
            <span class="label">Total data {{ $tahun }}@if ($jenis) ({{ \App\Support\StatistikUmur::JENIS[$jenis] }})@endif</span>
            <span class="nilai" data-hitung="{{ $r['total'] }}" data-desimal="0">{{ $fmt($r['total']) }}</span>
        </div>
        <div class="kartu reveal">
            <span class="label">Rata-rata umur</span>
            <span class="nilai" data-hitung="{{ $r['rata2'] }}" data-desimal="1">{{ $fmt($r['rata2']) }}</span>
        </div>
        <div class="kartu reveal">
            <span class="label">Median umur</span>
            <span class="nilai" data-hitung="{{ $r['median'] }}" data-desimal="{{ is_float($r['median']) ? 1 : 0 }}">{{ $fmt($r['median']) }}</span>
        </div>
        <div class="kartu reveal">
            <span class="label">Modus (umur terbanyak)</span>
            <span class="nilai">{{ implode(', ', array_slice($r['modus'], 0, 3)) }}</span>
        </div>
        <div class="kartu reveal">
            <span class="label">{{ $acuan['bulan'] }} {{ $tahun }}</span>
            <span class="nilai" data-hitung="{{ $acuan['jumlah'] }}" data-desimal="0">{{ $fmt($acuan['jumlah']) }}</span>
            <span class="sub">
                @if (is_null($acuan['perubahan']))
                    tidak ada pembanding bulan lalu
                @else
                    <span class="{{ $acuan['perubahan'] >= 0 ? 'naik' : 'turun' }}">
                        {{ $acuan['perubahan'] >= 0 ? '▲' : '▼' }} {{ number_format(abs($acuan['perubahan']), 1, ',', '.') }}%
                    </span>
                    dibanding bulan lalu
                @endif
            </span>
        </div>
        <div class="kartu reveal">
            <span class="label">Bulan tersibuk</span>
            <span class="nilai">{{ $stat['tersibuk']['bulan'] }}</span>
            <span class="sub">{{ $fmt($stat['tersibuk']['jumlah']) }} data</span>
        </div>
        @if ($tampilBandingJenis)
            <div class="kartu reveal">
                <span class="label">Prioritas</span>
                <span class="nilai" data-hitung="{{ $jumlahP }}" data-desimal="0">{{ $fmt($jumlahP) }}</span>
                <span class="sub">{{ $persen($jumlahP) }} dari data berjenis</span>
            </div>
            <div class="kartu reveal">
                <span class="label">Non prioritas</span>
                <span class="nilai" data-hitung="{{ $jumlahMP }}" data-desimal="0">{{ $fmt($jumlahMP) }}</span>
                <span class="sub">{{ $persen($jumlahMP) }} dari data berjenis</span>
            </div>
        @endif
    </div>

    <div class="grafik-grid">
        <div class="panel reveal">
            <h2>Jumlah per kelompok umur</h2>
            <div class="grafik"><canvas id="gKelompok"></canvas></div>
        </div>
        <div class="panel reveal">
            <h2>Persentase kelompok umur</h2>
            <div class="grafik"><canvas id="gPersen"></canvas></div>
        </div>
        <div class="panel reveal">
            <h2>Jumlah data per bulan</h2>
            <div class="grafik"><canvas id="gBulan"></canvas></div>
        </div>
        <div class="panel reveal">
            <h2>Rata-rata umur per bulan</h2>
            <div class="grafik"><canvas id="gRata2"></canvas></div>
        </div>
        @if ($tampilBandingJenis)
            <div class="panel reveal">
                <h2>Prioritas vs Non prioritas per bulan</h2>
                <div class="grafik"><canvas id="gJenisBulan"></canvas></div>
            </div>
            <div class="panel reveal">
                <h2>Kelompok umur: Prioritas vs Non prioritas</h2>
                <div class="grafik"><canvas id="gJenisKelompok"></canvas></div>
            </div>
        @endif
        <div class="panel lebar reveal">
            <h2>Kelompok umur per bulan</h2>
            <div class="grafik"><canvas id="gMatriks"></canvas></div>
        </div>
        <div class="panel lebar reveal">
            <h2>Sebaran umur (per angka)</h2>
            <div class="grafik"><canvas id="gHist"></canvas></div>
        </div>
    </div>
@endif
@endsection

@if ($stat['ringkasan']['total'] > 0)
    @push('scripts')
        <script src="{{ asset('vendor/chartjs/chart.umd.js') }}"></script>
        <script>
            const stat = @json($stat);
            const palet = ['#1d4f91', '#2e8b6f', '#c8a24a', '#b5453a', '#6a5a9c', '#2a8fa6'];
            const warnaJenis = ['#1d4f91', '#c8a24a'];

            Chart.defaults.font.family = '"Segoe UI", system-ui, -apple-system, Roboto, Arial, sans-serif';
            Chart.defaults.color = '#44505e';
            Chart.defaults.animation = { duration: 1100, easing: 'easeOutQuart' };

            const dasar = {
                maintainAspectRatio: false,
                transitions: { active: { animation: { duration: 250 } } },
                plugins: { tooltip: { backgroundColor: '#0b2a4a', padding: 10, cornerRadius: 4 } }
            };
            const sumbuY = { beginAtZero: true, ticks: { precision: 0 } };

            new Chart(document.getElementById('gKelompok'), {
                type: 'bar',
                data: {
                    labels: stat.kelompok_label,
                    datasets: [{ label: 'Jumlah', data: stat.kelompok_nilai, backgroundColor: palet }]
                },
                options: { ...dasar, plugins: { ...dasar.plugins, legend: { display: false } }, scales: { y: sumbuY } }
            });

            new Chart(document.getElementById('gPersen'), {
                type: 'doughnut',
                data: {
                    labels: stat.kelompok_label,
                    datasets: [{ data: stat.kelompok_nilai, backgroundColor: palet }]
                },
                options: { ...dasar, plugins: { ...dasar.plugins, legend: { position: 'bottom' } } }
            });

            new Chart(document.getElementById('gBulan'), {
                type: 'line',
                data: {
                    labels: stat.bulan_label,
                    datasets: [{ label: 'Jumlah data', data: stat.bulan_nilai, borderColor: palet[0],
                                 backgroundColor: 'rgba(29, 79, 145, .12)', fill: true, tension: .3 }]
                },
                options: { ...dasar, plugins: { ...dasar.plugins, legend: { display: false } }, scales: { y: sumbuY } }
            });

            new Chart(document.getElementById('gRata2'), {
                type: 'line',
                data: {
                    labels: stat.bulan_label,
                    datasets: [{ label: 'Rata-rata umur', data: stat.rata2_bulan, borderColor: palet[1],
                                 backgroundColor: palet[1], tension: .3, spanGaps: false }]
                },
                options: { ...dasar, plugins: { ...dasar.plugins, legend: { display: false } } }
            });

            @if (! $jenis && array_sum($stat['jenis_total']) > 0)
            new Chart(document.getElementById('gJenisBulan'), {
                type: 'bar',
                data: {
                    labels: stat.bulan_label,
                    datasets: stat.jenis_bulan.map((d, i) => ({ label: d.label, data: d.data, backgroundColor: warnaJenis[i] }))
                },
                options: { ...dasar, plugins: { ...dasar.plugins, legend: { position: 'bottom' } }, scales: { y: sumbuY } }
            });

            new Chart(document.getElementById('gJenisKelompok'), {
                type: 'bar',
                data: {
                    labels: stat.kelompok_label,
                    datasets: stat.jenis_kelompok.map((d, i) => ({ label: d.label, data: d.data, backgroundColor: warnaJenis[i] }))
                },
                options: { ...dasar, plugins: { ...dasar.plugins, legend: { position: 'bottom' } }, scales: { y: sumbuY } }
            });
            @endif

            new Chart(document.getElementById('gMatriks'), {
                type: 'bar',
                data: {
                    labels: stat.bulan_label,
                    datasets: stat.matriks.map((d, i) => ({ label: d.label, data: d.data, backgroundColor: palet[i] }))
                },
                options: {
                    ...dasar,
                    plugins: { ...dasar.plugins, legend: { position: 'bottom' } },
                    scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } } }
                }
            });

            new Chart(document.getElementById('gHist'), {
                type: 'bar',
                data: {
                    labels: stat.hist_label,
                    datasets: [{ label: 'Jumlah', data: stat.hist_nilai, backgroundColor: palet[0],
                                 categoryPercentage: 1, barPercentage: .95 }]
                },
                options: {
                    ...dasar,
                    plugins: { ...dasar.plugins, legend: { display: false } },
                    scales: { y: sumbuY, x: { title: { display: true, text: 'Umur (tahun)' } } }
                }
            });

            // Angka kartu menghitung naik dari 0.
            (function () {
                const kurang = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                document.querySelectorAll('[data-hitung]').forEach(function (el) {
                    const akhir = parseFloat(el.dataset.hitung);
                    const des = parseInt(el.dataset.desimal || '0', 10);
                    if (kurang || isNaN(akhir)) return;
                    const fmt = new Intl.NumberFormat('id-ID', { minimumFractionDigits: des, maximumFractionDigits: des });
                    const mulai = performance.now(), durasi = 1200;
                    el.textContent = fmt.format(0);
                    function langkah(t) {
                        const p = Math.min((t - mulai) / durasi, 1);
                        el.textContent = fmt.format(akhir * (1 - Math.pow(1 - p, 3)));
                        if (p < 1) requestAnimationFrame(langkah);
                    }
                    requestAnimationFrame(langkah);
                });
            })();

            // Jeda bertahap agar kartu muncul berurutan.
            document.querySelectorAll('#kartuGrid .kartu').forEach(function (k, i) { k.style.setProperty('--tunda', (i * 70) + 'ms'); });
        </script>
    @endpush
@endif
