<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Link Tidak Valid — Mall Intelligence Center</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
body { background:#f0f2f5; min-height:100vh; display:flex; align-items:center; justify-content:center; }
.card { border:none; border-radius:1rem; box-shadow:0 8px 32px rgba(0,0,0,.10); }
</style>
</head>
<body>
<div class="container" style="max-width:420px">
    <div class="card p-4 text-center">
        <i class="bi bi-x-circle text-danger" style="font-size:3rem"></i>
        <h5 class="fw-bold mt-3">Link Tidak Valid atau Kadaluarsa</h5>
        <p class="text-muted small">Link reset password hanya berlaku selama 1 jam dan hanya bisa digunakan sekali.</p>
        <a href="<?= base_url('forgot-password') ?>" class="btn btn-primary btn-sm">
            <i class="bi bi-arrow-repeat me-1"></i>Minta Link Baru
        </a>
    </div>
</div>
</body>
</html>
