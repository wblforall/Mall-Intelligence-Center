<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Persetujuan IDP — Selesai</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>body { background:#f4f6fb; }</style>
</head>
<body>
<div style="max-width:480px;margin:80px auto;padding:0 16px;text-align:center">
    <?php if ($keputusan === 'setuju'): ?>
    <div class="text-success mb-3"><i class="bi bi-check-circle-fill" style="font-size:3rem"></i></div>
    <h5 class="fw-bold">IDP Disetujui</h5>
    <p class="text-muted">Terima kasih. IDP <strong><?= esc($plan['periode_label']) ?></strong> untuk
        <strong><?= esc($plan['employee_nama'] ?? '') ?></strong> telah Anda setujui dan sekarang berstatus <span class="badge bg-primary">Aktif</span>.</p>
    <?php else: ?>
    <div class="text-danger mb-3"><i class="bi bi-x-circle-fill" style="font-size:3rem"></i></div>
    <h5 class="fw-bold">IDP Ditolak</h5>
    <p class="text-muted">Keputusan penolakan Anda telah tercatat. Tim HR akan meninjau kembali IDP ini dan menghubungi Anda jika diperlukan.</p>
    <?php endif; ?>
    <p class="text-muted small mt-3">Mall Intelligence Center · PT. Wulandari Bangun Laksana Tbk.</p>
</div>
</body>
</html>
