<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Forgot Password — Mall Intelligence Center</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link rel="stylesheet" href="<?= base_url('css/auth-simple.css') ?>?v=<?= @filemtime(FCPATH . 'css/auth-simple.css') ?>">
</head>
<body class="auth-simple">
<div class="container" style="max-width:420px">
    <div class="text-center mb-4 auth-brand">
        <div class="fw-bold fs-5">Mall Intelligence Center</div>
        <div class="text-muted small">PT. Wulandari Bangun Laksana Tbk.</div>
    </div>
    <div class="card p-4">
        <h5 class="fw-bold mb-1"><i class="bi bi-envelope-arrow-up me-2 text-primary"></i>Forgot password?</h5>
        <p class="text-muted small mb-3">Enter your account email. We will send you a link to create a new password.</p>

        <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success py-2 small"><?= session()->getFlashdata('success') ?></div>
        <?php endif; ?>

        <form method="post" action="<?= base_url('forgot-password') ?>">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label fw-semibold">Email</label>
                <input type="email" name="email" class="form-control" required autofocus placeholder="Your email">
            </div>
            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-send me-1"></i>Send reset link
            </button>
        </form>
    </div>
    <div class="text-center mt-3">
        <a href="<?= base_url('login') ?>" class="text-muted small"><i class="bi bi-arrow-left me-1"></i>Back to sign in</a>
    </div>
</div>
</body>
</html>
