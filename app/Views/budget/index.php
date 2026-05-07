<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$profit          = $revenue - $totalBudget;
$roiPct          = $totalBudget > 0 ? round($revenue / $totalBudget * 100) : 0;
$roiColor        = $roiPct >= 100 ? 'success' : ($roiPct >= 60 ? 'primary' : ($roiPct >= 30 ? 'warning' : 'danger'));
$deptBudgetTotal = array_sum(array_column($budgetByDept, 'total'));

// Allocation bar segments: combine all dept budgets as one "Departemen" block
$allocItems = [];
if ($deptBudgetTotal > 0) $allocItems[] = ['label' => 'Departemen',        'amount' => $deptBudgetTotal, 'color' => '#dc3545'];
if ($loyaltyBudget  > 0)  $allocItems[] = ['label' => 'Program Loyalty',   'amount' => $loyaltyBudget,   'color' => '#0d6efd'];
if ($vmBudget       > 0)  $allocItems[] = ['label' => 'Dekorasi & VM',     'amount' => $vmBudget,        'color' => '#ffc107'];
if ($contentBudget  > 0)  $allocItems[] = ['label' => 'Content Event',     'amount' => $contentBudget,   'color' => '#0dcaf0'];
if ($creativeBudget > 0)  $allocItems[] = ['label' => 'Creative & Design', 'amount' => $creativeBudget,  'color' => '#9c27b0'];
?>

<!-- Page header -->
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= base_url('events/'.$event['id'].'/summary') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h4 class="fw-bold mb-0">Budget Summary</h4>
        <small class="text-muted"><?= esc($event['name']) ?></small>
    </div>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4 align-items-start">
    <div class="col-md-3 col-6">
        <div class="card border-danger-subtle">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="rounded-2 p-1 bg-danger-subtle"><i class="bi bi-wallet2 text-danger fs-5"></i></div>
                    <span class="small text-muted">Total Budget</span>
                </div>
                <div class="fw-bold fs-5 text-danger">Rp <?= number_format($totalBudget,0,',','.') ?></div>
                <?php if ($totalBudget > 0): ?>
                <div class="mt-2 pt-2 border-top" style="font-size:.72rem">
                    <?php if ($deptBudgetTotal > 0): ?>
                    <div class="d-flex justify-content-between text-muted"><span><i class="bi bi-buildings me-1"></i>Departemen</span><span>Rp <?= number_format($deptBudgetTotal,0,',','.') ?></span></div>
                    <?php endif; ?>
                    <?php if ($loyaltyBudget > 0): ?>
                    <div class="d-flex justify-content-between text-muted"><span><i class="bi bi-star me-1"></i>Loyalty</span><span>Rp <?= number_format($loyaltyBudget,0,',','.') ?></span></div>
                    <?php endif; ?>
                    <?php if ($vmBudget > 0): ?>
                    <div class="d-flex justify-content-between text-muted"><span><i class="bi bi-palette me-1"></i>VM</span><span>Rp <?= number_format($vmBudget,0,',','.') ?></span></div>
                    <?php endif; ?>
                    <?php if ($contentBudget > 0): ?>
                    <div class="d-flex justify-content-between text-muted"><span><i class="bi bi-collection-play me-1"></i>Content</span><span>Rp <?= number_format($contentBudget,0,',','.') ?></span></div>
                    <?php endif; ?>
                    <?php if ($creativeBudget > 0): ?>
                    <div class="d-flex justify-content-between text-muted"><span><i class="bi bi-brush me-1"></i>Creative</span><span>Rp <?= number_format($creativeBudget,0,',','.') ?></span></div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-success-subtle">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="rounded-2 p-1 bg-success-subtle"><i class="bi bi-graph-up-arrow text-success fs-5"></i></div>
                    <span class="small text-muted">Total Revenue</span>
                </div>
                <div class="fw-bold fs-5 text-success">Rp <?= number_format($revenue,0,',','.') ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card <?= $profit >= 0 ? 'border-success-subtle' : 'border-danger-subtle' ?>">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="rounded-2 p-1 <?= $profit >= 0 ? 'bg-success-subtle' : 'bg-danger-subtle' ?>">
                        <i class="bi bi-<?= $profit >= 0 ? 'arrow-up-circle' : 'arrow-down-circle' ?> <?= $profit >= 0 ? 'text-success' : 'text-danger' ?> fs-5"></i>
                    </div>
                    <span class="small text-muted">Profit / Loss</span>
                </div>
                <div class="fw-bold fs-5 <?= $profit >= 0 ? 'text-success' : 'text-danger' ?>">
                    <?= ($profit >= 0 ? '+' : '−') ?>Rp <?= number_format(abs($profit),0,',','.') ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-<?= $roiColor ?>-subtle">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="rounded-2 p-1 bg-<?= $roiColor ?>-subtle"><i class="bi bi-percent text-<?= $roiColor ?> fs-5"></i></div>
                    <span class="small text-muted">ROI</span>
                </div>
                <div class="fw-bold fs-5 text-<?= $roiColor ?>"><?= $roiPct ?>%</div>
                <?php if ($totalBudget > 0): ?>
                <div class="mt-1">
                    <div class="progress" style="height:4px">
                        <div class="progress-bar bg-<?= $roiColor ?>" style="width:<?= min(100, $roiPct) ?>%"></div>
                    </div>
                    <div class="text-muted mt-1" style="font-size:.72rem">Revenue ÷ Budget</div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Budget Allocation Bar -->
<?php if ($totalBudget > 0 && !empty($allocItems)): ?>
<div class="card mb-4">
    <div class="card-header"><h6 class="mb-0 fw-semibold"><i class="bi bi-pie-chart me-2"></i>Alokasi Budget</h6></div>
    <div class="card-body pb-3">
        <div class="progress mb-3" style="height:20px;border-radius:6px;overflow:hidden">
            <?php foreach ($allocItems as $ai):
                $w = round($ai['amount'] / $totalBudget * 100);
                if ($w <= 0) continue; ?>
            <div class="progress-bar" role="progressbar" style="width:<?= $w ?>%;background:<?= $ai['color'] ?>"
                 title="<?= esc($ai['label']) ?>: <?= $w ?>%"></div>
            <?php endforeach; ?>
        </div>
        <div class="d-flex flex-wrap gap-3">
            <?php foreach ($allocItems as $ai):
                $pct = round($ai['amount'] / $totalBudget * 100); ?>
            <div class="d-flex align-items-center gap-2 small">
                <span class="rounded-1 flex-shrink-0" style="width:12px;height:12px;background:<?= $ai['color'] ?>"></span>
                <span class="text-muted"><?= esc($ai['label']) ?></span>
                <span class="fw-semibold">Rp <?= number_format($ai['amount'],0,',','.') ?></span>
                <span class="badge rounded-pill" style="background:<?= $ai['color'] ?>20;color:<?= $ai['color'] ?>"><?= $pct ?>%</span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<div>

<!-- Budget Departemen -->
<?php if (! empty($allBudgets)): ?>
<div class="card mb-3">
<div class="card-header"><h6 class="mb-0 fw-semibold"><i class="bi bi-buildings me-2"></i>Detail Budget Departemen</h6></div>
<div class="card-body p-0">
<div class="table-responsive">
<table class="table table-sm table-hover mb-0">
<thead><tr><th>Kategori</th><th>Keterangan</th><th class="text-end">Jumlah</th></tr></thead>
<tbody>
<?php
$lastDept = null;
foreach ($allBudgets as $b):
    if ($b['dept_name'] !== $lastDept): $lastDept = $b['dept_name']; ?>
<tr class="table-light"><td colspan="3" class="small fw-semibold text-secondary py-1 ps-3"><?= esc($b['dept_name'] ?? '—') ?></td></tr>
<?php endif; ?>
<tr>
    <td class="small ps-4"><?= esc($b['kategori']) ?></td>
    <td class="small text-muted"><?= esc($b['keterangan']) ?></td>
    <td class="text-end small fw-medium">Rp <?= number_format($b['jumlah'],0,',','.') ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
</div>
<?php endif; ?>

<!-- Loyalty Programs budget detail -->
<?php if (! empty($loyaltyPrograms)): ?>
<div class="card mb-3">
<div class="card-header d-flex justify-content-between align-items-center">
    <h6 class="mb-0 fw-semibold"><i class="bi bi-star me-2 text-primary"></i>Budget Program Loyalty</h6>
    <a href="<?= base_url('events/'.$event['id'].'/loyalty') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-right me-1"></i>Kelola</a>
</div>
<div class="card-body p-0">
<div class="table-responsive">
<table class="table table-sm table-hover mb-0">
<thead><tr><th>Program</th><th>Tipe</th><th class="text-end">Budget</th></tr></thead>
<tbody>
<?php foreach ($loyaltyPrograms as $lp): ?>
<tr>
    <td class="small fw-medium"><?= esc($lp['nama_program']) ?></td>
    <td class="small">
        <?php if ($lp['target_type'] === 'member'): ?>
        <span class="badge bg-primary-subtle text-primary">Member</span>
        <?php elseif ($lp['target_type'] === 'evoucher'): ?>
        <span class="badge bg-warning-subtle text-warning-emphasis">e-Voucher</span>
        <?php else: ?>
        <span class="text-muted">—</span>
        <?php endif; ?>
    </td>
    <td class="text-end small fw-medium">Rp <?= number_format($lp['budget'],0,',','.') ?></td>
</tr>
<?php endforeach; ?>
<tr class="table-light">
    <td colspan="2" class="small fw-bold">Total</td>
    <td class="text-end small fw-bold text-primary">Rp <?= number_format($loyaltyBudget,0,',','.') ?></td>
</tr>
</tbody>
</table>
</div>
</div>
</div>
<?php endif; ?>

<!-- VM Items budget detail -->
<?php if (! empty($vmItems)): ?>
<div class="card mb-3">
<div class="card-header d-flex justify-content-between align-items-center">
    <h6 class="mb-0 fw-semibold"><i class="bi bi-palette me-2 text-warning"></i>Budget Dekorasi & VM</h6>
    <a href="<?= base_url('events/'.$event['id'].'/vm') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-right me-1"></i>Kelola</a>
</div>
<div class="card-body p-0">
<div class="table-responsive">
<table class="table table-sm table-hover mb-0">
<thead><tr><th>Item</th><th>Keterangan</th><th class="text-end">Budget</th></tr></thead>
<tbody>
<?php foreach ($vmItems as $vi): ?>
<tr>
    <td class="small fw-medium"><?= esc($vi['nama_item']) ?></td>
    <td class="small text-muted"><?= esc($vi['deskripsi_referensi']) ?></td>
    <td class="text-end small fw-medium">Rp <?= number_format($vi['budget'],0,',','.') ?></td>
</tr>
<?php endforeach; ?>
<tr class="table-light">
    <td colspan="2" class="small fw-bold">Total</td>
    <td class="text-end small fw-bold text-warning">Rp <?= number_format($vmBudget,0,',','.') ?></td>
</tr>
</tbody>
</table>
</div>
</div>
</div>
<?php endif; ?>

<!-- Content Items budget detail -->
<?php if (! empty($contentItems)): ?>
<div class="card mb-3">
<div class="card-header d-flex justify-content-between align-items-center">
    <h6 class="mb-0 fw-semibold"><i class="bi bi-collection-play me-2 text-info"></i>Budget Content Event</h6>
    <a href="<?= base_url('events/'.$event['id'].'/content') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-right me-1"></i>Kelola</a>
</div>
<div class="card-body p-0">
<div class="table-responsive">
<table class="table table-sm table-hover mb-0">
<thead><tr><th>Nama</th><th>Tipe</th><th>PIC</th><th class="text-end">Budget</th></tr></thead>
<tbody>
<?php foreach ($contentItems as $ci): ?>
<tr>
    <td class="small fw-medium"><?= esc($ci['nama']) ?></td>
    <td class="small"><span class="badge bg-info-subtle text-info"><?= esc(ucfirst($ci['tipe'])) ?></span></td>
    <td class="small text-muted"><?= esc($ci['pic'] ?? '—') ?></td>
    <td class="text-end small fw-medium">Rp <?= number_format($ci['budget'],0,',','.') ?></td>
</tr>
<?php endforeach; ?>
<tr class="table-light">
    <td colspan="3" class="small fw-bold">Total</td>
    <td class="text-end small fw-bold text-info">Rp <?= number_format($contentBudget,0,',','.') ?></td>
</tr>
</tbody>
</table>
</div>
</div>
</div>
<?php endif; ?>

<!-- Creative Items budget detail -->
<?php if (! empty($creativeItems)): ?>
<?php
$creativeTipeLabel = ['master_design'=>'Master Design','digital'=>'Digital','cetak'=>'Cetak','influencer'=>'Influencer','media_prescon'=>'Media Prescon'];
$lastTipe = null;
?>
<div class="card mb-3">
<div class="card-header d-flex justify-content-between align-items-center">
    <h6 class="mb-0 fw-semibold"><i class="bi bi-brush me-2" style="color:var(--c-creative)"></i>Budget Creative & Design</h6>
    <a href="<?= base_url('events/'.$event['id'].'/creative') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-right me-1"></i>Kelola</a>
</div>
<div class="card-body p-0">
<div class="table-responsive">
<table class="table table-sm table-hover mb-0">
<thead><tr><th>Tipe</th><th>Nama</th><th class="text-end">Budget</th></tr></thead>
<tbody>
<?php foreach ($creativeItems as $ci): ?>
<?php if ($ci['tipe'] !== $lastTipe): $lastTipe = $ci['tipe']; ?>
<tr class="table-light"><td colspan="3" class="small fw-semibold text-secondary py-1 ps-3"><?= esc($creativeTipeLabel[$ci['tipe']] ?? ucfirst($ci['tipe'])) ?></td></tr>
<?php endif; ?>
<tr>
    <td class="text-muted small ps-4">—</td>
    <td class="small"><?= esc($ci['nama']) ?></td>
    <td class="text-end small fw-medium">Rp <?= number_format($ci['budget'],0,',','.') ?></td>
</tr>
<?php endforeach; ?>
<tr class="table-light">
    <td colspan="2" class="small fw-bold">Total</td>
    <td class="text-end small fw-bold" style="color:var(--c-creative)">Rp <?= number_format($creativeBudget,0,',','.') ?></td>
</tr>
</tbody>
</table>
</div>
</div>
</div>
<?php endif; ?>

</div>

<?= $this->endSection() ?>
