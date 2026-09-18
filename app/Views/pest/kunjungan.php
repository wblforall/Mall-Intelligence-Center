<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$totalHal = max(1, (int) ceil($total / $per));
$qs = fn(array $ubah) => base_url('pest/kunjungan') . '?' . http_build_query(array_merge(
    ['mall' => $mall, 'sumber' => $sumber === null ? 'semua' : 'kunjungan', 'hal' => $hal], $ubah));
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-calendar-check me-2"></i>Kunjungan Pest</h4>
        <small class="text-muted"><?= number_format($total) ?> kunjungan tercatat</small>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= base_url('pest') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-graph-up me-1"></i>Tren</a>
        <?php if ($canEdit): ?>
        <a href="<?= base_url('pest/input/ewalk/' . date('Y-m-d')) ?>" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i>Input eWalk</a>
        <a href="<?= base_url('pest/input/pentacity/' . date('Y-m-d')) ?>" class="btn btn-sm btn-success"><i class="bi bi-plus-lg me-1"></i>Input Pentacity</a>
        <?php endif; ?>
    </div>
</div>

<div class="card mb-3">
<div class="card-body py-2 d-flex flex-wrap gap-3 align-items-center">
    <div class="btn-group btn-group-sm">
        <a href="<?= $qs(['mall' => null, 'hal' => 1]) ?>" class="btn <?= ! $mall ? 'btn-primary' : 'btn-outline-secondary' ?>">Kedua Mall</a>
        <a href="<?= $qs(['mall' => 'ewalk', 'hal' => 1]) ?>" class="btn <?= $mall === 'ewalk' ? 'btn-primary' : 'btn-outline-secondary' ?>">eWalk</a>
        <a href="<?= $qs(['mall' => 'pentacity', 'hal' => 1]) ?>" class="btn <?= $mall === 'pentacity' ? 'btn-primary' : 'btn-outline-secondary' ?>">Pentacity</a>
    </div>
    <div class="btn-group btn-group-sm">
        <a href="<?= $qs(['sumber' => 'kunjungan', 'hal' => 1]) ?>" class="btn <?= $sumber ? 'btn-primary' : 'btn-outline-secondary' ?>">Kunjungan saja</a>
        <a href="<?= $qs(['sumber' => 'semua', 'hal' => 1]) ?>" class="btn <?= ! $sumber ? 'btn-primary' : 'btn-outline-secondary' ?>">+ Rekap impor</a>
    </div>
</div>
</div>

<div class="card">
<div class="table-responsive">
<table class="table table-hover align-middle mb-0">
<thead class="table-light">
<tr>
    <th>Tanggal</th><th>Mall</th><th class="text-center">Temuan</th>
    <th class="text-center">Foto</th><th>Status</th><th class="text-end">Aksi</th>
</tr>
</thead>
<tbody>
<?php if (! $rows): ?>
<tr><td colspan="6" class="text-center text-muted py-4">
    <i class="bi bi-inbox d-block fs-3 mb-2 opacity-25"></i>Belum ada kunjungan tercatat.
</td></tr>
<?php else: foreach ($rows as $r):
    $legacy = $r['sumber'] === 'rekap_legacy';
    $nihil  = ! $legacy && (int) $r['baris_temuan'] === 0; ?>
<tr>
    <td>
        <span class="fw-medium"><?= tgl_indo($r['tanggal']) ?></span>
        <?php if (! $legacy): ?>
        <span class="d-block small text-muted">
            W<?= date('W', strtotime($r['tanggal'])) ?> &middot; <?= ['Sen','Sel','Rab','Kam','Jum','Sab','Min'][date('N', strtotime($r['tanggal'])) - 1] ?>
        </span>
        <?php endif; ?>
    </td>
    <td><span class="badge bg-<?= $r['mall'] === 'ewalk' ? 'primary' : 'success' ?>-subtle text-<?= $r['mall'] === 'ewalk' ? 'primary' : 'success' ?>">
        <?= \App\Models\PestVisitModel::MALLS[$r['mall']] ?></span></td>
    <td class="text-center fw-semibold"><?= (int) $r['total_temuan'] > 0 ? number_format((int) $r['total_temuan']) : '—' ?></td>
    <td class="text-center"><?= (int) $r['jml_foto'] > 0 ? '<i class="bi bi-camera"></i> ' . (int) $r['jml_foto'] : '<span class="text-muted">—</span>' ?></td>
    <td>
        <?php if ($legacy): ?>
            <span class="badge bg-secondary-subtle text-secondary" title="Hasil impor rekap bulanan Excel — tidak punya resolusi mingguan">
                <i class="bi bi-archive me-1"></i>Rekap impor
            </span>
        <?php elseif ($nihil): ?>
            <span class="badge bg-success-subtle text-success"><i class="bi bi-check-circle me-1"></i>Nihil temuan</span>
        <?php else: ?>
            <span class="badge bg-warning-subtle text-warning-emphasis"><?= (int) $r['baris_temuan'] ?> jenis</span>
        <?php endif; ?>
    </td>
    <td class="text-end">
        <?php if (! $legacy): ?>
        <a href="<?= base_url('pest/input/' . $r['mall'] . '/' . $r['tanggal']) ?>"
           class="btn btn-sm btn-outline-<?= $r['mall'] === 'ewalk' ? 'primary' : 'success' ?>"
           title="<?= $canEdit ? 'Sunting' : 'Lihat' ?> <?= \App\Models\PestVisitModel::MALLS[$r['mall']] ?>">
            <i class="bi bi-<?= $canEdit ? 'pencil' : 'eye' ?>"></i>
        </a>
        <?php endif; ?>
        <?php if ($canEdit): ?>
        <form method="POST" action="<?= base_url('pest/delete/' . $r['id']) ?>" class="d-inline"
              onsubmit="return confirm('Hapus kunjungan <?= tgl_indo($r['tanggal']) ?> (<?= \App\Models\PestVisitModel::MALLS[$r['mall']] ?>)?\n\nSeluruh temuan dan foto buktinya ikut terhapus.');">
            <?= csrf_field() ?>
            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
        </form>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; endif; ?>
</tbody>
</table>
</div>
<?php if ($totalHal > 1): ?>
<div class="card-footer d-flex justify-content-between align-items-center py-2">
    <small class="text-muted">Halaman <?= $hal ?> dari <?= $totalHal ?></small>
    <div class="btn-group btn-group-sm">
        <a href="<?= $qs(['hal' => max(1, $hal - 1)]) ?>" class="btn btn-outline-secondary <?= $hal <= 1 ? 'disabled' : '' ?>">&laquo;</a>
        <a href="<?= $qs(['hal' => min($totalHal, $hal + 1)]) ?>" class="btn btn-outline-secondary <?= $hal >= $totalHal ? 'disabled' : '' ?>">&raquo;</a>
    </div>
</div>
<?php endif; ?>
</div>

<?= $this->endSection() ?>
