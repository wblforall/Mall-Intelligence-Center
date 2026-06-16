<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
// opsi <option> kandidat user
$opts = function ($selected) use ($candidates) {
    $out = '<option value="">— belum ditentukan (fallback: HR) —</option>';
    foreach ($candidates as $c) {
        $sel = ((int) $c['user_id'] === (int) $selected) ? ' selected' : '';
        $label = esc($c['nama']) . ' · ' . esc($c['jabatan_nama'] ?? '-') . ' (' . esc($c['dept_name'] ?? '-') . ')';
        $out .= '<option value="' . (int) $c['user_id'] . '"' . $sel . '>' . $label . '</option>';
    }
    return $out;
};
?>
<div class="d-flex align-items-center gap-2 mb-3">
    <a href="<?= base_url('appraisal') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h4 class="fw-bold mb-0">Penyusun Template KPI</h4>
        <small class="text-muted">Tunjuk Dept Head (per dept) & Deputy (per divisi). Template level puncak dept disusun Deputy; sisanya Dept Head; tanpa penunjukan → HR.</small>
    </div>
</div>

<?php if (session('error')): ?><div class="alert alert-danger py-2 small"><?= esc(session('error')) ?></div><?php endif; ?>
<?php if (session('success')): ?><div class="alert alert-success py-2 small"><?= esc(session('success')) ?></div><?php endif; ?>

<form method="POST" action="<?= base_url('appraisal/authors/save') ?>">
<?= csrf_field() ?>

<div class="card mb-3">
<div class="card-header py-2"><h6 class="mb-0 fw-semibold"><i class="bi bi-diagram-3 me-1"></i>Deputy per Divisi</h6></div>
<div class="card-body p-0">
<table class="table align-middle mb-0">
<thead class="small text-muted"><tr><th class="ps-3" style="width:40%">Divisi</th><th>Deputy (penyusun template level Manager/Dept Head)</th></tr></thead>
<tbody>
<?php foreach ($divisions as $dv): ?>
<tr>
    <td class="ps-3 fw-medium"><?= esc($dv['nama']) ?></td>
    <td><select name="deputy[<?= $dv['id'] ?>]" class="form-select form-select-sm"><?= $opts($deputyMap[$dv['id']] ?? '') ?></select></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>

<div class="card mb-3">
<div class="card-header py-2"><h6 class="mb-0 fw-semibold"><i class="bi bi-person-badge me-1"></i>Dept Head per Departemen</h6></div>
<div class="card-body p-0">
<table class="table align-middle mb-0">
<thead class="small text-muted"><tr><th class="ps-3" style="width:30%">Departemen</th><th style="width:20%">Divisi</th><th>Dept Head (penyusun template bawahannya)</th></tr></thead>
<tbody>
<?php foreach ($depts as $d): ?>
<tr>
    <td class="ps-3 fw-medium"><?= esc($d['name']) ?></td>
    <td class="small text-muted"><?= esc($d['division_nama'] ?? '—') ?></td>
    <td><select name="dept[<?= $d['id'] ?>]" class="form-select form-select-sm"><?= $opts($authorMap[$d['id']] ?? '') ?></select></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>

<div class="text-end mb-4"><button class="btn btn-primary"><i class="bi bi-save me-1"></i>Simpan Penunjukan</button></div>
</form>
<?= $this->endSection() ?>
