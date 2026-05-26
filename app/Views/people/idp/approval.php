<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Persetujuan IDP</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
body { background:#f4f6fb; }
.idp-card { max-width:680px; margin:40px auto; }
.section-label { font-size:.75rem; text-transform:uppercase; letter-spacing:.06em; color:#6c757d; font-weight:600; }
</style>
</head>
<body>
<div class="idp-card px-3">
    <div class="text-center mb-4 mt-4">
        <div class="text-muted small">PT. Wulandari Bangun Laksana Tbk.</div>
        <h5 class="fw-bold mt-1">Persetujuan Individual Development Plan</h5>
        <div class="text-muted small">Anda diminta sebagai: <strong>Atasan Langsung</strong></div>
    </div>

    <?php if ($statusDone): ?>
    <div class="alert alert-<?= $plan['persetujuan_atasan'] === 'setuju' ? 'success' : 'danger' ?> text-center">
        <i class="bi bi-<?= $plan['persetujuan_atasan'] === 'setuju' ? 'check-circle' : 'x-circle' ?> fs-4 d-block mb-1"></i>
        Anda sudah <strong><?= $plan['persetujuan_atasan'] === 'setuju' ? 'menyetujui' : 'menolak' ?></strong> IDP ini.
    </div>
    <?php endif; ?>

    <!-- Info IDP -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="section-label mb-2">Informasi IDP</div>
            <div class="row g-2">
                <div class="col-6">
                    <div class="text-muted small">Karyawan</div>
                    <div class="fw-semibold"><?= esc($plan['employee_nama']) ?></div>
                    <div class="text-muted small"><?= esc($plan['jabatan'] ?? '') ?> · <?= esc($plan['dept_name'] ?? '') ?></div>
                </div>
                <div class="col-6">
                    <div class="text-muted small">Periode</div>
                    <div class="fw-semibold"><?= esc($plan['periode_label']) ?></div>
                    <div class="text-muted small">Tahun <?= $plan['tahun'] ?></div>
                </div>
                <?php if ($plan['tujuan_karir']): ?>
                <div class="col-12">
                    <div class="text-muted small">Tujuan Karir</div>
                    <div class="small"><?= nl2br(esc($plan['tujuan_karir'])) ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Goals -->
    <?php if (! empty($items)): ?>
    <div class="card mb-3">
        <div class="card-body p-0">
            <div class="section-label p-3 pb-0 mb-2">Goal Pengembangan</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr><th>#</th><th>Goal</th><th>Level</th><th>Deadline</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($items as $i => $item): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td>
                            <div class="fw-semibold small"><?= esc($item['judul']) ?></div>
                            <?php if ($item['competency_nama']): ?>
                            <div class="text-muted" style="font-size:.75rem"><?= esc($item['competency_nama']) ?></div>
                            <?php endif; ?>
                            <?php if ($item['langkah_aksi']): ?>
                            <div class="text-muted" style="font-size:.75rem"><?= nl2br(esc($item['langkah_aksi'])) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="small">
                            <?= $item['level_saat_ini'] ? number_format((float)$item['level_saat_ini'], 1) : '-' ?>
                            <?= ($item['level_saat_ini'] && $item['level_target']) ? ' → ' . $item['level_target'] : '' ?>
                        </td>
                        <td class="small"><?= $item['deadline'] ? date('d M Y', strtotime($item['deadline'])) : '-' ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Form Approval -->
    <?php if (! $statusDone): ?>
    <div class="card mb-4">
        <div class="card-body">
            <div class="section-label mb-3">Keputusan Anda</div>
            <form method="post" action="<?= base_url('idp/approval/' . $token . '/submit') ?>">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="keputusan" id="setuju" value="setuju" required>
                        <label class="form-check-label text-success fw-semibold" for="setuju">
                            <i class="bi bi-check-circle me-1"></i>Setuju
                        </label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="keputusan" id="menolak" value="menolak">
                        <label class="form-check-label text-danger fw-semibold" for="menolak">
                            <i class="bi bi-x-circle me-1"></i>Tolak
                        </label>
                    </div>
                </div>
                <div class="mb-3" id="catatanWrap" style="display:none">
                    <label class="form-label small fw-semibold">Catatan Penolakan</label>
                    <textarea name="catatan" class="form-control form-control-sm" rows="3"
                              placeholder="Jelaskan alasan penolakan..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary w-100">Kirim Keputusan</button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <p class="text-center text-muted small pb-4">
        Mall Intelligence Center · PT. Wulandari Bangun Laksana Tbk.
    </p>
</div>

<script>
document.querySelectorAll('input[name="keputusan"]').forEach(function(el) {
    el.addEventListener('change', function() {
        document.getElementById('catatanWrap').style.display =
            this.value === 'menolak' ? 'block' : 'none';
    });
});
</script>
</body>
</html>
