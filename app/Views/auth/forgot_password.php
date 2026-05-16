<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Lupa Password — Mall Intelligence Center</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
body { background:#f0f2f5; min-height:100vh; display:flex; align-items:center; justify-content:center; }
.card { border:none; border-radius:1rem; box-shadow:0 8px 32px rgba(0,0,0,.10); }
</style>
</head>
<body>
<div class="container" style="max-width:420px">
    <div class="text-center mb-4">
        <div class="fw-bold fs-5">Mall Intelligence Center</div>
        <div class="text-muted small">PT. Wulandari Bangun Laksana Tbk.</div>
    </div>
    <div class="card p-4">
        <h5 class="fw-bold mb-1"><i class="bi bi-envelope-arrow-up me-2 text-primary"></i>Lupa Password</h5>
        <p class="text-muted small mb-3">Masukkan email akun Anda. Kami akan mengirimkan link untuk membuat password baru.</p>

        <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success py-2 small"><?= session()->getFlashdata('success') ?></div>
        <?php endif; ?>

        <form method="post" action="<?= base_url('forgot-password') ?>">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label fw-semibold">Email</label>
                <input type="email" name="email" class="form-control" required autofocus placeholder="email@perusahaan.com">
            </div>
            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-send me-1"></i>Kirim Link Reset
            </button>
        </form>
    </div>
    <div class="text-center mt-3">
        <a href="<?= base_url('login') ?>" class="text-muted small"><i class="bi bi-arrow-left me-1"></i>Kembali ke Login</a>
    </div>
</div>
</body>
</html>
