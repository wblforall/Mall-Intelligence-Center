<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Halaman tidak ditemukan — Mall Intelligence Center</title>
    <link rel="icon" type="image/png" href="<?= base_url('img/mic-logo-sm.png') ?>">
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
            padding: 1rem; background: #0f172a; color: #cbd5e1;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        .wrap {
            width: 100%; max-width: 460px; text-align: center; padding: 2.25rem 1.75rem;
            background: linear-gradient(160deg, #16264a, #0d1a33); border: 1px solid rgba(255,255,255,.1);
            border-radius: 1.1rem; box-shadow: 0 18px 50px rgba(0,0,0,.45);
        }
        .wrap img { height: 46px; width: auto; margin-bottom: 1.25rem; }
        .code { font-size: 3.4rem; font-weight: 800; letter-spacing: -.03em; color: #e8c872; line-height: 1; }
        h1 { margin: .6rem 0 .4rem; font-size: 1.25rem; font-weight: 700; color: #f1f5f9; }
        p { margin: 0 0 1.5rem; font-size: .92rem; line-height: 1.5; color: #94a3b8; }
        .btn {
            display: inline-block; padding: .6rem 1.3rem; border-radius: .6rem; font-weight: 600; font-size: .92rem;
            text-decoration: none; color: #fff; background: #4f46e5;
        }
        .btn:hover { background: #4338ca; }
        .btn-alt { background: transparent; border: 1px solid rgba(255,255,255,.2); color: #cbd5e1; margin-left: .4rem; }
        .btn-alt:hover { background: rgba(255,255,255,.08); }
    </style>
</head>
<body>
    <div class="wrap">
        <img src="<?= base_url('img/mic-logo.png') ?>" alt="Mall Intelligence Center">
        <div class="code">404</div>
        <h1>Halaman tidak ditemukan</h1>
        <p>Alamat yang Anda buka tidak ada atau sudah dipindahkan. Periksa kembali alamatnya, atau kembali ke Dashboard.</p>
        <a class="btn" href="<?= base_url('/') ?>">Ke Dashboard</a><a class="btn btn-alt" href="javascript:history.back()">Kembali</a>
    </div>
</body>
</html>
