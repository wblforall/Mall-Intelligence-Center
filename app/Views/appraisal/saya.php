<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
$statusBadge = ['input'=>['secondary','Perlu input'],'in_review'=>['info','Perlu review'],'hr_review'=>['warning','Di HR'],'finalized'=>['success','Final']];
$n = fn($v) => $v === null ? '—' : rtrim(rtrim(number_format((float)$v,2),'0'),'.');
?>
<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h4 class="fw-bold mb-0">Penilaian Saya</h4>
        <small class="text-muted">Form penilaian yang menunggu tindakan Anda</small>
    </div>
    <?php if ($isManager): ?>
    <a href="<?= base_url('appraisal/templates') ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-bullseye me-1"></i>Susun Template KPI</a>
    <?php endif; ?>
</div>

<?php if (session('error')): ?><div class="alert alert-danger py-2 small"><?= esc(session('error')) ?></div><?php endif; ?>
<?php if (session('success')): ?><div class="alert alert-success py-2 small"><?= esc(session('success')) ?></div><?php endif; ?>

<div class="card mb-3">
<div class="card-header py-2"><h6 class="mb-0 fw-semibold"><i class="bi bi-inbox me-1"></i>Menunggu Tindakan (<?= count($inbox) ?>)</h6></div>
<div class="card-body p-0">
<div class="table-responsive">
<table class="table table-hover align-middle mb-0">
<thead class="small text-muted"><tr><th class="ps-3">Karyawan</th><th>Jabatan</th><th>Periode</th><th class="text-center">Nilai Akhir</th><th>Tahap</th><th></th></tr></thead>
<tbody>
<?php if (empty($inbox)): ?>
<tr><td colspan="6" class="text-center text-muted py-4">Tidak ada yang menunggu. 🎉</td></tr>
<?php else: foreach ($inbox as $f): [$bc,$bl]=$statusBadge[$f['status']]??['secondary',$f['status']]; ?>
<tr>
    <td class="ps-3"><div class="fw-medium"><?= esc($f['employee_nama']) ?></div><div class="small text-muted"><?= esc($f['nik']??'') ?></div></td>
    <td class="small"><?= esc($f['jabatan_nama']??'—') ?></td>
    <td class="small text-muted"><?= esc($f['periode_nama']??'—') ?></td>
    <td class="text-center"><?= $n($f['nilai_akhir']) ?></td>
    <td><span class="badge bg-<?= $bc ?>"><?= $bl ?></span></td>
    <td class="text-end pe-3"><a href="<?= base_url('appraisal/forms/' . $f['id']) ?>" class="btn btn-sm btn-primary"><?= $f['status']==='input'?'Nilai':'Review' ?></a></td>
</tr>
<?php endforeach; endif; ?>
</tbody>
</table>
</div>
</div>
</div>

<?php if (! empty($done)): ?>
<div class="card">
<div class="card-header py-2"><h6 class="mb-0 fw-semibold text-muted"><i class="bi bi-clock-history me-1"></i>Sudah Diteruskan</h6></div>
<div class="card-body p-0">
<div class="table-responsive">
<table class="table table-sm align-middle mb-0">
<thead class="small text-muted"><tr><th class="ps-3">Karyawan</th><th>Periode</th><th class="text-center">Nilai Akhir</th><th>Status</th><th></th></tr></thead>
<tbody>
<?php foreach ($done as $f): [$bc,$bl]=$statusBadge[$f['status']]??['secondary',$f['status']]; ?>
<tr>
    <td class="ps-3 small fw-medium"><?= esc($f['employee_nama']) ?></td>
    <td class="small text-muted"><?= esc($f['periode_nama']??'—') ?></td>
    <td class="text-center"><?= $n($f['nilai_akhir']) ?></td>
    <td><span class="badge bg-<?= $bc ?>"><?= $bl ?></span></td>
    <td class="text-end pe-3"><a href="<?= base_url('appraisal/forms/' . $f['id']) ?>" class="btn btn-sm btn-outline-secondary">Lihat</a></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
</div>
<?php endif; ?>
<?= $this->endSection() ?>
