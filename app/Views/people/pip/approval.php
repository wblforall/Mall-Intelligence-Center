<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Persetujuan PIP</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
body { background:#f4f6fb; }
.pip-card { max-width:680px; margin:40px auto; }
.section-label { font-size:.75rem; text-transform:uppercase; letter-spacing:.06em; color:#6c757d; font-weight:600; }
</style>
</head>
<body>

<?php
$spLabel     = ['none'=>'Tanpa SP','sp1'=>'SP 1','sp2'=>'SP 2','sp3'=>'SP 3','phk'=>'PHK'];
$pihakLabel  = ['atasan'=>'Atasan Langsung','karyawan'=>'Karyawan'];
$setujuLabel = ['pending'=>'Menunggu','setuju'=>'Disetujui','menolak'=>'Ditolak'];
$setujuColor = ['pending'=>'secondary','setuju'=>'success','menolak'=>'danger'];
$statusField = 'persetujuan_' . $pihak;
?>

<div class="pip-card px-3">
    <div class="text-center mb-4 mt-4">
        <div class="text-muted small">PT. Wulandari Bangun Laksana Tbk.</div>
        <h5 class="fw-bold mt-1">Persetujuan Performance Improvement Plan</h5>
        <div class="text-muted small">Anda diminta sebagai: <strong><?= $pihakLabel[$pihak] ?></strong></div>
    </div>

    <!-- Info PIP -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="section-label mb-2">Informasi PIP</div>
            <div class="row g-2">
                <div class="col-6">
                    <div class="text-muted small">Karyawan</div>
                    <div class="fw-semibold"><?= esc($plan['employee_nama']) ?></div>
                    <div class="text-muted small"><?= esc($plan['jabatan'] ?? '') ?> · <?= esc($plan['dept_name'] ?? '') ?></div>
                </div>
                <div class="col-6">
                    <div class="text-muted small">Judul PIP</div>
                    <div class="fw-semibold"><?= esc($plan['judul']) ?></div>
                </div>
                <div class="col-6">
                    <div class="text-muted small">Periode</div>
                    <div class="fw-semibold"><?= date('d M Y', strtotime($plan['tanggal_mulai'])) ?> – <?= date('d M Y', strtotime($plan['tanggal_selesai'])) ?></div>
                </div>
                <div class="col-6">
                    <div class="text-muted small">Surat Peringatan</div>
                    <div class="fw-semibold"><?= $spLabel[$plan['level_sp']] ?></div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($plan['alasan']): ?>
    <div class="card mb-3">
        <div class="card-body">
            <div class="section-label mb-1">Latar Belakang</div>
            <div class="small"><?= nl2br(esc($plan['alasan'])) ?></div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (! empty($items)): ?>
    <div class="card mb-3">
        <div class="card-body p-0">
            <div class="section-label p-3 pb-0 mb-2">Item Perbaikan</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Aspek</th>
                            <th>Target</th>
                            <th>Deadline</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($items as $i => $item): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><strong><?= esc($item['aspek']) ?></strong><?php if ($item['masalah']): ?><br><span class="text-muted small"><?= esc($item['masalah']) ?></span><?php endif; ?></td>
                        <td class="small"><?= esc($item['target'] ?? '—') ?></td>
                        <td class="small text-nowrap"><?= $item['deadline'] ? date('d M Y', strtotime($item['deadline'])) : '—' ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($plan['konsekuensi']): ?>
    <div class="card mb-3 border-warning">
        <div class="card-body">
            <div class="section-label mb-1"><i class="bi bi-exclamation-triangle me-1 text-warning"></i>Konsekuensi jika Tidak Tercapai</div>
            <div class="small"><?= nl2br(esc($plan['konsekuensi'])) ?></div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Status / Form -->
    <?php if ($statusDone): ?>
    <div class="alert alert-<?= $setujuColor[$plan[$statusField]] ?> text-center">
        <i class="bi bi-check-circle me-2"></i>
        Anda sudah <strong><?= $setujuLabel[$plan[$statusField]] ?></strong> PIP ini.
    </div>
    <?php else: ?>
    <div class="card mb-4">
        <div class="card-body">
            <div class="section-label mb-3">Keputusan Anda</div>
            <form method="post" action="<?= base_url('pip/approval/' . $pihak . '/' . $token . '/submit') ?>">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <textarea name="catatan" class="form-control" rows="3"
                        placeholder="Catatan (wajib diisi jika menolak)…"></textarea>
                </div>
                <div class="d-grid gap-2">
                    <button type="submit" name="keputusan" value="setuju" class="btn btn-success btn-lg"
                        onclick="return confirm('Anda yakin menyetujui PIP ini?')">
                        <i class="bi bi-check-circle me-2"></i>Saya Setuju
                    </button>
                    <button type="submit" name="keputusan" value="menolak" class="btn btn-outline-danger"
                        onclick="return confirm('Anda yakin menolak PIP ini?')">
                        <i class="bi bi-x-circle me-2"></i>Saya Menolak
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
