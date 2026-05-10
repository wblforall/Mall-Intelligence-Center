<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>TNA Assessment</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>body { background:#f8fafc; }</style>
</head>
<body>
<div class="d-flex align-items-center justify-content-center min-vh-100">
    <div class="text-center px-3" style="max-width:400px">
        <div class="mb-3">
            <i class="bi bi-check-circle-fill text-success" style="font-size:3.5rem"></i>
        </div>
        <h5 class="fw-bold"><?= esc($message ?? 'Selesai.') ?></h5>
        <p class="text-muted small mt-2">Halaman ini dapat ditutup.</p>
        <div class="mt-3 text-muted" style="font-size:.75rem">
            <i class="bi bi-building me-1"></i>Mall Intelligence Center · PT. Wulandari Bangun Laksana Tbk.
        </div>
    </div>
</div>
</body>
</html>
