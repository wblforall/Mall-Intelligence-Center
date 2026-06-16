<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
$badge = [
    'draft'     => ['secondary', 'Draft'],
    'submitted' => ['warning', 'Diajukan ke HR'],
    'approved'  => ['success', 'Disetujui'],
];
?>
<div class="d-flex align-items-center gap-2 mb-3">
    <a href="<?= base_url('appraisal') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h4 class="fw-bold mb-0">Template Appraisal (KPI per Jabatan)</h4>
        <small class="text-muted"><?= $isHr ? 'HR — kelola & setujui semua template' : 'Manager — susun template untuk jabatan di departemen Anda' ?></small>
    </div>
</div>

<?php if (session('error')): ?><div class="alert alert-danger py-2 small"><?= esc(session('error')) ?></div><?php endif; ?>
<?php if (session('success')): ?><div class="alert alert-success py-2 small"><?= esc(session('success')) ?></div><?php endif; ?>

<!-- Buat baru -->
<?php if (! empty($jabsAvailable)): ?>
<div class="card mb-3">
<div class="card-body py-2">
    <form method="POST" action="<?= base_url('appraisal/templates/create') ?>" class="row g-2 align-items-end">
        <?= csrf_field() ?>
        <div class="col-auto">
            <label class="form-label small fw-semibold mb-1">Buat template untuk jabatan</label>
            <select name="jabatan_id" class="form-select form-select-sm" required>
                <option value="">— pilih jabatan —</option>
                <?php foreach ($jabsAvailable as $j): ?>
                <option value="<?= $j['id'] ?>"><?= esc($j['dept_name'] ?? '-') ?> · <?= esc($j['nama']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-auto">
            <button class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i>Buat</button>
        </div>
    </form>
</div>
</div>
<?php endif; ?>

<div class="card">
<div class="card-body p-0">
<div class="table-responsive">
<table class="table table-hover align-middle mb-0">
<thead class="small text-muted">
<tr>
    <th class="ps-3">Jabatan</th>
    <th>Departemen</th>
    <th class="text-center">Item KPI</th>
    <th class="text-center">Total Bobot</th>
    <th>Status</th>
    <th></th>
</tr>
</thead>
<tbody>
<?php if (empty($templates)): ?>
<tr><td colspan="6" class="text-center text-muted py-4">Belum ada template.</td></tr>
<?php else: foreach ($templates as $t): [$bc, $bl] = $badge[$t['status']] ?? ['secondary', $t['status']]; ?>
<tr>
    <td class="ps-3 fw-medium"><?= esc($t['jabatan_nama'] ?? '—') ?></td>
    <td class="small text-muted"><?= esc($t['dept_name'] ?? '—') ?></td>
    <td class="text-center"><?= $t['kpi_count'] ?></td>
    <td class="text-center">
        <span class="<?= abs($t['total_bobot'] - 100) < 0.01 ? 'text-success' : 'text-danger' ?>"><?= rtrim(rtrim(number_format($t['total_bobot'], 2), '0'), '.') ?></span>/100
    </td>
    <td><span class="badge bg-<?= $bc ?>"><?= $bl ?></span></td>
    <td class="text-end pe-3">
        <a href="<?= base_url('appraisal/templates/' . $t['id']) ?>" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-pencil"></i> <?= $t['status'] === 'approved' && ! $isHr ? 'Lihat' : 'Kelola' ?>
        </a>
    </td>
</tr>
<?php endforeach; endif; ?>
</tbody>
</table>
</div>
</div>
</div>
<?= $this->endSection() ?>
