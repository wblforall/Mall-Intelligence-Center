<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
$statusBadge = [
    'input'     => ['secondary', 'Input penilai'],
    'in_review' => ['info', 'Review atasan'],
    'hr_review' => ['warning', 'Review HR'],
    'finalized' => ['success', 'Final'],
];
$n = fn($v) => $v === null ? '—' : rtrim(rtrim(number_format((float)$v, 2), '0'), '.');
?>
<div class="d-flex align-items-center gap-2 mb-3">
    <a href="<?= base_url('appraisal') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0"><?= esc($period['nama']) ?>
            <span class="badge bg-<?= $period['status']==='open'?'success':'secondary' ?> align-middle ms-1"><?= $period['status']==='open'?'Terbuka':'Ditutup' ?></span>
        </h4>
        <small class="text-muted"><?= count($forms) ?> form penilaian</small>
    </div>
    <?php if ($period['status']==='open'): ?>
    <form method="POST" action="<?= base_url('appraisal/periods/' . $period['id'] . '/close') ?>" onsubmit="return confirm('Tutup periode ini?')">
        <?= csrf_field() ?>
        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-lock me-1"></i>Tutup Periode</button>
    </form>
    <?php endif; ?>
</div>

<?php if (session('error')): ?><div class="alert alert-danger py-2 small"><?= esc(session('error')) ?></div><?php endif; ?>
<?php if (session('success')): ?><div class="alert alert-success py-2 small"><?= esc(session('success')) ?></div><?php endif; ?>

<div class="card">
<div class="card-body p-0">
<div class="table-responsive">
<table class="table table-hover align-middle mb-0">
<thead class="small text-muted">
<tr>
    <th class="ps-3">Karyawan</th><th>Jabatan</th><th>Dept</th>
    <th class="text-center">KPI</th><th class="text-center">Komp.</th><th class="text-center">Nilai Akhir</th>
    <th>Status</th><th>Di tangan</th><th></th>
</tr>
</thead>
<tbody>
<?php if (empty($forms)): ?>
<tr><td colspan="9" class="text-center text-muted py-4">Belum ada form. Pastikan ada template <b>disetujui</b> untuk jabatan karyawan.</td></tr>
<?php else: foreach ($forms as $f): [$bc,$bl] = $statusBadge[$f['status']] ?? ['secondary',$f['status']]; ?>
<tr>
    <td class="ps-3">
        <div class="fw-medium"><?= esc($f['employee_nama'] ?? '—') ?></div>
        <div class="small text-muted"><?= esc($f['nik'] ?? '') ?></div>
    </td>
    <td class="small"><?= esc($f['jabatan_nama'] ?? '—') ?></td>
    <td class="small text-muted"><?= esc($f['dept_name'] ?? '—') ?></td>
    <td class="text-center"><?= $n($f['skor_kpi']) ?></td>
    <td class="text-center"><?= $n($f['skor_kompetensi']) ?></td>
    <td class="text-center fw-bold"><?= $n($f['nilai_akhir']) ?></td>
    <td><span class="badge bg-<?= $bc ?>"><?= $bl ?></span></td>
    <td class="small text-muted"><?= $f['status']==='finalized' ? '—' : esc($userNames[$f['current_user_id']] ?? ($f['status']==='hr_review'?'HR':'—')) ?></td>
    <td class="text-end pe-3"><a href="<?= base_url('appraisal/forms/' . $f['id']) ?>" class="btn btn-sm btn-outline-primary">Buka</a></td>
</tr>
<?php endforeach; endif; ?>
</tbody>
</table>
</div>
</div>
</div>
<?= $this->endSection() ?>
