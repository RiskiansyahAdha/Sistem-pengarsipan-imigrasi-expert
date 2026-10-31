<!DOCTYPE html>
<html lang="id">
<head>
    <link rel="icon" type="image/x-icon" href="{{ asset('logo.ico') }}">
    <link rel="shortcut icon" href="{{ asset('logo.ico') }}">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') | Statistik Pemohon Paspor</title>
    <style>
        :root {
            --navy: #0b2a4a; --navy-2: #123a63; --utama: #1d4f91; --aksen: #c8a24a;
            --bg: #f3f5f8; --panel: #ffffff; --teks: #1b2733; --redup: #5d6b7a;
            --garis: #dde3ea; --naik: #146c3a; --turun: #a61e1e;
            --bayang: 0 1px 2px rgba(11, 42, 74, .06), 0 4px 14px rgba(11, 42, 74, .05);
        }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body { margin: 0; font-family: "Segoe UI", system-ui, -apple-system, Roboto, "Helvetica Neue", Arial, sans-serif;
               background: var(--bg); color: var(--teks); line-height: 1.55; font-size: 15px;
               min-height: 100vh; display: flex; flex-direction: column; }
        a { color: var(--utama); }
        .wrap { max-width: 1180px; margin: 0 auto; padding: 0 20px; width: 100%; }

        /* ---------- Header instansi ---------- */
        .gov-bar { background: #081d34; color: #c9d6e4; font-size: .78rem; }
        .gov-bar .wrap { display: flex; justify-content: space-between; gap: 12px; padding-top: 6px; padding-bottom: 6px; flex-wrap: wrap; }
        .brand-area { background: var(--navy); color: #fff; border-bottom: 3px solid var(--aksen); }
        .brand-area .wrap { display: flex; align-items: center; gap: 14px; padding-top: 14px; padding-bottom: 14px; }
        .lambang { width: 46px; height: 46px; flex: none; }
        .brand-teks small { display: block; letter-spacing: .08em; text-transform: uppercase; font-size: .7rem; color: #aebfd1; }
        .brand-teks strong { font-size: 1.15rem; font-weight: 600; line-height: 1.2; }
        .menu-bar { background: var(--navy-2); position: sticky; top: 0; z-index: 20; box-shadow: 0 2px 6px rgba(0, 0, 0, .15); }
        .menu-bar nav { display: flex; gap: 2px; overflow-x: auto; }
        .menu-bar a { color: #dbe6f2; text-decoration: none; padding: 12px 18px; font-weight: 500; font-size: .93rem;
                      border-bottom: 3px solid transparent; transition: background .2s, border-color .2s, color .2s; white-space: nowrap; }
        .menu-bar a:hover { background: rgba(255, 255, 255, .08); color: #fff; }
        .menu-bar a.aktif { color: #fff; border-bottom-color: var(--aksen); background: rgba(255, 255, 255, .06); }

        main { padding: 28px 20px 56px; flex: 1; }
        h1 { margin: 0; font-size: 1.45rem; color: var(--navy); font-weight: 650; }
        h2 { color: var(--navy); font-size: 1.05rem; margin: 28px 0 10px; }
        .baris-judul { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px;
                       margin-bottom: 20px; padding-bottom: 14px; border-bottom: 1px solid var(--garis); position: relative; }
        .baris-judul::after { content: ""; position: absolute; left: 0; bottom: -1px; width: 64px; height: 3px; background: var(--aksen); }
        .baris-judul .sub-judul { color: var(--redup); font-size: .88rem; margin-top: 2px; }

        /* ---------- Kartu ---------- */
        .kartu-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 14px; margin-bottom: 22px; }
        .kartu { background: var(--panel); border: 1px solid var(--garis); border-top: 3px solid var(--utama); border-radius: 6px;
                 padding: 14px 16px; display: flex; flex-direction: column; gap: 2px; box-shadow: var(--bayang);
                 transition: transform .25s ease, box-shadow .25s ease; }
        .kartu:hover { transform: translateY(-3px); box-shadow: 0 8px 22px rgba(11, 42, 74, .12); }
        .kartu.k2 { border-top-color: var(--aksen); }
        .kartu .label { font-size: .78rem; color: var(--redup); text-transform: uppercase; letter-spacing: .04em; font-weight: 600; }
        .kartu .nilai { font-size: 1.75rem; font-weight: 700; color: var(--navy); font-variant-numeric: tabular-nums; }
        .kartu .sub { font-size: .8rem; color: var(--redup); }
        .naik { color: var(--naik); font-weight: 600; }
        .turun { color: var(--turun); font-weight: 600; }

        .grafik-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 16px; }
        .panel { background: var(--panel); border: 1px solid var(--garis); border-radius: 6px; padding: 16px 18px; box-shadow: var(--bayang); }
        .panel h2 { margin: 0 0 12px; font-size: .98rem; padding-bottom: 8px; border-bottom: 1px solid var(--garis); }
        .panel.lebar { grid-column: 1 / -1; }
        .grafik { position: relative; height: 290px; }

        .kosong { background: var(--panel); border: 1px dashed #b7c2cf; border-radius: 6px; padding: 36px; text-align: center; color: var(--redup); }

        /* ---------- Form & tombol ---------- */
        form.filter, form.filter-atas { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
        form.filter { background: var(--panel); border: 1px solid var(--garis); border-radius: 6px; padding: 12px; margin-bottom: 16px; box-shadow: var(--bayang); }
        form.filter-atas { background: var(--panel); border: 1px solid var(--garis); border-radius: 6px; padding: 8px 12px; }
        form.filter-atas label { font-size: .82rem; color: var(--redup); font-weight: 600; }
        input, select { padding: 8px 10px; border: 1px solid #c3ccd6; border-radius: 4px; font: inherit; background: #fff; color: inherit;
                        transition: border-color .15s, box-shadow .15s; }
        input:focus, select:focus { outline: none; border-color: var(--utama); box-shadow: 0 0 0 3px rgba(29, 79, 145, .18); }
        .btn { display: inline-block; padding: 8px 16px; border: 0; border-radius: 4px; background: var(--utama);
               color: #fff; font: inherit; font-weight: 600; text-decoration: none; cursor: pointer; transition: background .2s, transform .1s; }
        .btn:hover { background: var(--navy-2); }
        .btn:active { transform: scale(.98); }
        .btn.abu { background: #e6eaef; color: var(--teks); }
        .btn.abu:hover { background: #d6dce4; }

        /* ---------- Tabel ---------- */
        table { width: 100%; border-collapse: collapse; background: var(--panel); border: 1px solid var(--garis); }
        th, td { padding: 10px 14px; text-align: left; border-bottom: 1px solid var(--garis); }
        th { background: var(--navy); color: #fff; font-size: .78rem; text-transform: uppercase; letter-spacing: .05em; font-weight: 600; }
        tbody tr { transition: background .15s; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        tbody tr:hover { background: #eaf1fa; }
        tr:last-child td { border-bottom: 0; }
        .tabel-bungkus { overflow-x: auto; border-radius: 6px; box-shadow: var(--bayang); }
        .info { color: var(--redup); margin: 8px 0; }

        .alert { padding: 11px 16px; border-radius: 4px; margin-bottom: 16px; border-left: 4px solid; }
        .alert.ok { background: #e7f5ec; color: #14532d; border-color: #1f9d55; }
        .alert.err { background: #fdeaea; color: #8a1c1c; border-color: #d33; }

        .pager { display: flex; flex-wrap: wrap; gap: 4px; margin-top: 16px; }
        .pager a, .pager span { padding: 6px 12px; border: 1px solid var(--garis); border-radius: 4px; background: var(--panel); text-decoration: none; color: var(--teks); }
        .pager a:hover { background: #eaf1fa; }
        .pager .active { background: var(--utama); border-color: var(--utama); color: #fff; }
        .pager .disabled { color: #9aa5b1; }

        .detail { background: var(--panel); border: 1px solid var(--garis); border-radius: 6px; padding: 8px 20px; max-width: 560px; box-shadow: var(--bayang); }
        .detail dl { margin: 0; }
        .detail .item { display: flex; padding: 11px 0; border-bottom: 1px solid var(--garis); }
        .detail .item:last-child { border-bottom: 0; }
        .detail dt { width: 150px; color: var(--redup); }
        .detail dd { margin: 0; font-weight: 600; }

        form.edit { background: var(--panel); border: 1px solid var(--garis); border-radius: 6px; padding: 20px; max-width: 560px; box-shadow: var(--bayang); }
        form.edit label { display: block; margin: 12px 0 4px; font-weight: 600; }
        form.edit input[type=text], form.edit input[type=number] { width: 100%; }
        form.edit .salah { color: var(--turun); font-size: .85rem; margin-top: 4px; }
        form.edit .aksi { margin-top: 20px; display: flex; gap: 8px; }
        form.edit input[type=file] { width: 100%; padding: 10px; background: #f8fafc; }

        .catatan { background: #fbf6e6; border: 1px solid #ecdca3; border-left: 4px solid var(--aksen); color: #5b4710; border-radius: 4px; padding: 12px 16px; margin-bottom: 16px; }
        .catatan ul { margin: 6px 0 0; padding-left: 20px; }
        code { background: #eaeff5; padding: 1px 6px; border-radius: 3px; font-size: .9em; }
        .halaman-lebar { max-width: 780px; }
        .kartu-grid.rapat { margin-bottom: 16px; }
        .kecil { font-size: .85rem; color: var(--redup); }
        .lencana { display: inline-block; padding: 1px 9px; border-radius: 3px; font-size: .76rem; font-weight: 600; }
        .lencana.p { background: #dbe7f7; color: #14407a; }
        .lencana.mp { background: #f3e8c6; color: #6a4f0a; }
        .teks-abu { color: var(--redup, #888); font-style: italic; }

        /* ---------- Footer ---------- */
        .footer { background: var(--navy); color: #aebfd1; font-size: .82rem; border-top: 3px solid var(--aksen); }
        .footer .wrap { padding-top: 18px; padding-bottom: 18px; display: flex; justify-content: space-between; gap: 12px; flex-wrap: wrap; }

        /* ---------- Animasi ---------- */
        @keyframes naik-masuk { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: none; } }
        @keyframes turun-masuk { from { opacity: 0; transform: translateY(-8px); } to { opacity: 1; transform: none; } }
        .brand-area, .menu-bar { animation: turun-masuk .5s ease both; }
        .baris-judul { animation: naik-masuk .5s ease both; }
        .reveal { opacity: 0; transform: translateY(18px); transition: opacity .6s ease, transform .6s ease; transition-delay: var(--tunda, 0ms); }
        .reveal.tampil { opacity: 1; transform: none; }
        .tabel-bungkus, form.filter, form.edit, .detail, .catatan { animation: naik-masuk .5s ease both; }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation: none !important; transition: none !important; scroll-behavior: auto !important; }
            .reveal { opacity: 1; transform: none; }
        }
        @media (max-width: 600px) {
            .gov-bar .wrap span:last-child { display: none; }
            .brand-teks strong { font-size: 1rem; }
        }
    </style>
</head>
<body>
    <div class="gov-bar">
        <div class="wrap">
            <span>Portal Statistik Layanan Paspor</span>
            <span>{{ now()->locale('id')->translatedFormat('l, j F Y') }}</span>
        </div>
    </div>

    <header class="brand-area">
        <div class="wrap">
           <img class="lambang" src="{{ asset('logo.webp') }}" alt="Logo Instansi">
            <div class="brand-teks">
                <small>Kantor Imigrasi Kelas II TPI Lhokseumawe</small>
                <strong>Sistem Informasi Statistik Pemohon Paspor</strong>
            </div>
        </div>
    </header>

    <div class="menu-bar">
        <div class="wrap">
            <nav aria-label="Menu utama">
                <a href="{{ route('dashboard') }}" @class(['aktif' => request()->routeIs('dashboard')])>⊞ Dashboard</a>
                <a href="{{ route('data.index') }}" @class(['aktif' => request()->routeIs('data.*')])>☰ Data Pemohon</a>
                <a href="{{ route('impor.form') }}" @class(['aktif' => request()->routeIs('impor.*')])>⇪ Impor Data</a>
            </nav>
        </div>
    </div>

    <main class="wrap">
        @if (session('sukses'))
            <div class="alert ok">{{ session('sukses') }}</div>
        @endif

        @yield('content')
    </main>

    <footer class="footer">
        <div class="wrap">
            <span>&copy; {{ now()->year }} Kantor Imigrasi. Data bersifat rahasia dan hanya untuk keperluan internal.</span>
            <span>Sistem Informasi Statistik Pemohon Paspor</span>
        </div>
    </footer>

    <script>
        // Animasi muncul saat di-scroll (dimatikan otomatis bila pengguna memilih "kurangi gerakan").
        (function () {
            var el = document.querySelectorAll('.reveal');
            if (!('IntersectionObserver' in window) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                el.forEach(function (e) { e.classList.add('tampil'); });
                return;
            }
            var io = new IntersectionObserver(function (items) {
                items.forEach(function (i) {
                    if (i.isIntersecting) { i.target.classList.add('tampil'); io.unobserve(i.target); }
                });
            }, { threshold: .12 });
            el.forEach(function (e) { io.observe(e); });
        })();
    </script>
    @stack('scripts')
</body>
</html>
