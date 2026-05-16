<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Persetujuan Terkirim</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>body { background:#f4f6fb; }</style>
</head>
<body>
<div class="text-center" style="max-width:480px;margin:80px auto;padding:0 16px">
    <?php if ($keputusan === 'setuju'): ?>
    <i class="bi bi-check-circle-fill text-success" style="font-size:4rem"></i>
    <h5 class="fw-bold mt-3">Persetujuan Terkirim</h5>
    <p class="text-muted">Anda telah <strong>menyetujui</strong> PIP untuk <strong><?= esc($plan['employee_nama'] ?? '') ?></strong>. Terima kasih.</p>
    <?php else: ?>
    <i class="bi bi-x-circle-fill text-danger" style="font-size:4rem"></i>
    <h5 class="fw-bold mt-3">Penolakan Terkirim</h5>
    <p class="text-muted">Anda telah <strong>menolak</strong> PIP untuk <strong><?= esc($plan['employee_nama'] ?? '') ?></strong>. HR akan dihubungi.</p>
    <?php endif; ?>
    <p class="small text-muted mt-4">Halaman ini dapat ditutup.</p>
</div>
</body>
</html>
