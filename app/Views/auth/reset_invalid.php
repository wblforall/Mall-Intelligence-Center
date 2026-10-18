<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Invalid Link — Mall Intelligence Center</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link rel="stylesheet" href="<?= base_url('css/auth-simple.css') ?>?v=<?= @filemtime(FCPATH . 'css/auth-simple.css') ?>">
</head>
<body class="auth-simple">
<div class="container" style="max-width:420px">
    <div class="card p-4 text-center">
        <i class="bi bi-x-circle text-danger" style="font-size:3rem"></i>
        <h5 class="fw-bold mt-3">Link invalid or expired</h5>
        <p class="text-muted small">Password reset links are valid for 1 hour and can only be used once.</p>
        <a href="<?= base_url('forgot-password') ?>" class="btn btn-primary btn-sm">
            <i class="bi bi-arrow-repeat me-1"></i>Request a new link
        </a>
    </div>
</div>
</body>
</html>
