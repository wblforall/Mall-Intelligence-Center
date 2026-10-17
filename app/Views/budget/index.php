<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/events-summary.css') ?>?v=<?= @filemtime(FCPATH . 'css/events-summary.css') ?: 1 ?>">
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php
$profit          = $revenue - $totalBudget;
$roiPct          = $totalBudget > 0 ? round($revenue / $totalBudget * 100) : 0;
$roiCls          = $roiPct >= 100 ? 'ok' : ($roiPct >= 60 ? 'cyan' : ($roiPct >= 30 ? 'warn' : 'bad'));
$roiBar          = $roiPct >= 100 ? 'ev-bar-ok' : ($roiPct >= 60 ? 'ev-bar-blue' : ($roiPct >= 30 ? 'ev-bar-mid' : 'ev-bar-low'));
$deptBudgetTotal = array_sum(array_column($budgetByDept, 'total'));
$rp = static fn($n) => 'Rp ' . number_format((int) $n, 0, ',', '.');

// Alokasi: semua budget departemen digabung sebagai satu blok "Departemen"
$allocItems = [];
if ($deptBudgetTotal > 0) $allocItems[] = ['label' => 'Departemen',        'amount' => $deptBudgetTotal, 'color' => '#ef4444'];
if ($loyaltyBudget  > 0)  $allocItems[] = ['label' => 'Program Loyalty',   'amount' => $loyaltyBudget,   'color' => '#3b82f6'];
if ($vmBudget       > 0)  $allocItems[] = ['label' => 'Dekorasi & VM',     'amount' => $vmBudget,        'color' => '#f59e0b'];
if ($contentBudget  > 0)  $allocItems[] = ['label' => 'Content Event',     'amount' => $contentBudget,   'color' => '#22d3ee'];
if ($creativeBudget > 0)  $allocItems[] = ['label' => 'Creative & Design', 'amount' => $creativeBudget,  'color' => '#a855f7'];
?>
<div class="page-evs">

<!-- Header -->
<div class="ev-head">
    <a href="<?= base_url('events/'.$event['id'].'/summary') ?>" class="ev-iconbtn ev-back" title="Kembali ke Summary event" aria-label="Kembali ke Summary event"><i class="bi bi-arrow-left"></i></a>
    <div class="min-w-0">
        <h1 class="ev-title">Budget Summary</h1>
        <div class="ev-subtitle"><?= esc($event['name']) ?></div>
    </div>
    <div class="ev-head-act">
        <a href="<?= base_url('events/'.$event['id'].'/other-cost') ?>" class="ev-btn"><i class="bi bi-receipt"></i><span class="d-none d-sm-inline">Other Cost</span></a>
    </div>
</div>
<?= view('partials/event_tabs', ['event' => $event]) ?>

<!-- Strip KPI -->
<div class="ev-kpis">
    <div class="ev-kpi">
        <div class="ev-kpi-l">Total Budget</div>
        <div class="ev-kpi-v ev-bad"><?= $rp($totalBudget) ?></div>
        <div class="ev-kpi-s"><?= $totalBudget > 0 ? 'seluruh modul' : 'belum ada budget' ?></div>
        <?php if ($totalBudget > 0): ?>
        <div class="ev-kpi-rows">
            <?php if ($deptBudgetTotal > 0): ?><div><span><i class="bi bi-buildings me-1"></i>Departemen</span><span><?= $rp($deptBudgetTotal) ?></span></div><?php endif; ?>
            <?php if ($loyaltyBudget > 0): ?><div><span><i class="bi bi-star me-1"></i>Loyalty</span><span><?= $rp($loyaltyBudget) ?></span></div><?php endif; ?>
            <?php if ($vmBudget > 0): ?><div><span><i class="bi bi-palette me-1"></i>VM</span><span><?= $rp($vmBudget) ?></span></div><?php endif; ?>
            <?php if ($contentBudget > 0): ?><div><span><i class="bi bi-collection-play me-1"></i>Content</span><span><?= $rp($contentBudget) ?></span></div><?php endif; ?>
            <?php if ($creativeBudget > 0): ?><div><span><i class="bi bi-brush me-1"></i>Creative</span><span><?= $rp($creativeBudget) ?></span></div><?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    <div class="ev-kpi">
        <div class="ev-kpi-l">Total Revenue</div>
        <div class="ev-kpi-v ev-ok"><?= $rp($revenue) ?></div>
        <div class="ev-kpi-s"><?= $revenue > 0 ? 'exhibition + sponsor cash' : 'belum ada pendapatan' ?></div>
    </div>
    <div class="ev-kpi">
        <div class="ev-kpi-l">Profit / Loss</div>
        <div class="ev-kpi-v <?= $profit >= 0 ? 'ev-ok' : 'ev-bad' ?>"><?= ($profit >= 0 ? '+' : '−') ?><?= $rp(abs($profit)) ?></div>
        <div class="ev-kpi-s">Revenue − Total Budget</div>
    </div>
    <div class="ev-kpi">
        <div class="ev-kpi-l">ROI</div>
        <div class="ev-kpi-v ev-<?= $roiCls ?>"><?= $roiPct ?>%</div>
        <div class="ev-kpi-s">Revenue ÷ Budget</div>
        <?php if ($totalBudget > 0): ?><div class="ev-bar <?= $roiBar ?>"><i style="width:<?= min(100, $roiPct) ?>%"></i></div><?php endif; ?>
    </div>
</div>

<?php if ($totalBudget <= 0 && empty($allBudgets) && empty($loyaltyPrograms) && empty($vmItems) && empty($contentItems) && empty($creativeItems)): ?>
<div class="ev-empty">
    <i class="bi bi-wallet2"></i>
    <h5>Belum ada budget untuk event ini</h5>
    <p>Budget terisi dari modul Loyalty, VM, Content, Creative, dan Other Cost.</p>
    <a href="<?= base_url('events/'.$event['id'].'/other-cost') ?>" class="ev-btn ev-btn-primary"><i class="bi bi-receipt"></i>Buka Other Cost</a>
</div>
<?php endif; ?>

<!-- Alokasi Budget -->
<?php if ($totalBudget > 0 && !empty($allocItems)): ?>
<div class="ev-panel">
    <div class="ev-panel-h"><h2 class="ev-panel-t"><i class="bi bi-pie-chart"></i>Alokasi Budget</h2></div>
    <div class="ev-panel-b">
        <div class="ev-bar ev-bar-lg" role="img" aria-label="Alokasi budget per modul">
            <?php foreach ($allocItems as $ai):
                $w = round($ai['amount'] / $totalBudget * 100);
                if ($w <= 0) continue; ?>
            <i style="width:<?= $w ?>%;background:<?= $ai['color'] ?>" title="<?= esc($ai['label']) ?>: <?= $w ?>%"></i>
            <?php endforeach; ?>
        </div>
        <div class="ev-alloc-legend">
            <?php foreach ($allocItems as $ai):
                $pct = round($ai['amount'] / $totalBudget * 100); ?>
            <div>
                <span class="sw" style="background:<?= $ai['color'] ?>"></span>
                <span class="ev-mute"><?= esc($ai['label']) ?></span>
                <b><?= $rp($ai['amount']) ?></b>
                <span class="ev-chip ev-chip-line"><?= $pct ?>%</span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Budget Departemen -->
<?php if (! empty($allBudgets)): ?>
<div class="ev-panel">
<div class="ev-panel-h">
    <h2 class="ev-panel-t"><i class="bi bi-buildings"></i>Detail Budget Departemen</h2>
    <div class="ev-panel-end"><a href="<?= base_url('events/'.$event['id'].'/other-cost') ?>" class="ev-btn ev-btn-sm">Kelola<i class="bi bi-arrow-right"></i></a></div>
</div>
<div class="ev-tablewrap">
<table class="ev-table">
<thead><tr><th>Kategori</th><th>Keterangan</th><th class="r">Jumlah</th></tr></thead>
<tbody>
<?php
$lastDept = null;
foreach ($allBudgets as $b):
    if ($b['dept_name'] !== $lastDept): $lastDept = $b['dept_name']; ?>
<tr class="ev-grp"><td colspan="3"><?= esc($b['dept_name'] ?? '—') ?></td></tr>
<?php endif; ?>
<tr>
    <td class="ev-wrap" style="padding-left:1.4rem"><?= esc($b['kategori']) ?></td>
    <td class="ev-wrap ev-mute"><?= esc($b['keterangan']) ?></td>
    <td class="r nw fw-semibold"><?= $rp($b['jumlah']) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
<?php endif; ?>

<!-- Loyalty -->
<?php if (! empty($loyaltyPrograms)): ?>
<div class="ev-panel">
<div class="ev-panel-h">
    <h2 class="ev-panel-t"><i class="bi bi-star"></i>Budget Program Loyalty</h2>
    <div class="ev-panel-end"><a href="<?= base_url('events/'.$event['id'].'/loyalty') ?>" class="ev-btn ev-btn-sm">Kelola<i class="bi bi-arrow-right"></i></a></div>
</div>
<div class="ev-tablewrap">
<table class="ev-table">
<thead><tr><th>Program</th><th>Tipe</th><th class="r">Budget</th></tr></thead>
<tbody>
<?php foreach ($loyaltyPrograms as $lp): ?>
<tr>
    <td class="ev-wrap fw-semibold"><?= esc($lp['nama_program']) ?></td>
    <td>
        <?php if ($lp['target_type'] === 'member'): ?><span class="ev-chip ev-chip-blue">Member</span>
        <?php elseif ($lp['target_type'] === 'evoucher'): ?><span class="ev-chip ev-chip-warn">e-Voucher</span>
        <?php else: ?><span class="ev-mute">—</span><?php endif; ?>
    </td>
    <td class="r nw fw-semibold"><?= $rp($lp['budget']) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
<tfoot><tr><td colspan="2">Total</td><td class="r nw ev-cyan"><?= $rp($loyaltyBudget) ?></td></tr></tfoot>
</table>
</div>
</div>
<?php endif; ?>

<!-- VM -->
<?php if (! empty($vmItems)): ?>
<div class="ev-panel">
<div class="ev-panel-h">
    <h2 class="ev-panel-t"><i class="bi bi-palette"></i>Budget Dekorasi &amp; VM</h2>
    <div class="ev-panel-end"><a href="<?= base_url('events/'.$event['id'].'/vm') ?>" class="ev-btn ev-btn-sm">Kelola<i class="bi bi-arrow-right"></i></a></div>
</div>
<div class="ev-tablewrap">
<table class="ev-table">
<thead><tr><th>Item</th><th>Keterangan</th><th class="r">Budget</th></tr></thead>
<tbody>
<?php foreach ($vmItems as $vi): ?>
<tr>
    <td class="ev-wrap fw-semibold"><?= esc($vi['nama_item']) ?></td>
    <td class="ev-wrap ev-mute"><?= esc($vi['deskripsi_referensi']) ?></td>
    <td class="r nw fw-semibold"><?= $rp($vi['budget']) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
<tfoot><tr><td colspan="2">Total</td><td class="r nw ev-warn"><?= $rp($vmBudget) ?></td></tr></tfoot>
</table>
</div>
</div>
<?php endif; ?>

<!-- Content -->
<?php if (! empty($contentItems)): ?>
<div class="ev-panel">
<div class="ev-panel-h">
    <h2 class="ev-panel-t"><i class="bi bi-collection-play"></i>Budget Content Event</h2>
    <div class="ev-panel-end"><a href="<?= base_url('events/'.$event['id'].'/content') ?>" class="ev-btn ev-btn-sm">Kelola<i class="bi bi-arrow-right"></i></a></div>
</div>
<div class="ev-tablewrap">
<table class="ev-table">
<thead><tr><th>Nama</th><th>Tipe</th><th>PIC</th><th class="r">Budget</th></tr></thead>
<tbody>
<?php foreach ($contentItems as $ci): ?>
<tr>
    <td class="ev-wrap fw-semibold"><?= esc($ci['nama']) ?></td>
    <td><span class="ev-chip <?= ($ci['tipe'] ?? '') === 'biaya' ? 'ev-chip-warn' : 'ev-chip-blue' ?>"><?= esc(ucfirst($ci['tipe'])) ?></span></td>
    <td class="ev-wrap ev-mute"><?= esc($ci['pic'] ?? '—') ?></td>
    <td class="r nw fw-semibold"><?= $rp($ci['budget']) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
<tfoot><tr><td colspan="3">Total</td><td class="r nw ev-cyan"><?= $rp($contentBudget) ?></td></tr></tfoot>
</table>
</div>
</div>
<?php endif; ?>

<!-- Creative -->
<?php if (! empty($creativeItems)): ?>
<?php
$creativeTipeLabel = ['master_design' => 'Design','motion_graphic'=>'Motion Graphic','digital'=>'Digital','cetak'=>'Cetak','influencer'=>'Influencer','media_prescon'=>'Media Prescon'];
$lastTipe = null;
?>
<div class="ev-panel">
<div class="ev-panel-h">
    <h2 class="ev-panel-t"><i class="bi bi-brush"></i>Budget Creative &amp; Design</h2>
    <div class="ev-panel-end"><a href="<?= base_url('events/'.$event['id'].'/creative') ?>" class="ev-btn ev-btn-sm">Kelola<i class="bi bi-arrow-right"></i></a></div>
</div>
<div class="ev-tablewrap">
<table class="ev-table">
<thead><tr><th>Nama</th><th class="r">Budget</th></tr></thead>
<tbody>
<?php foreach ($creativeItems as $ci): ?>
<?php if ($ci['tipe'] !== $lastTipe): $lastTipe = $ci['tipe']; ?>
<tr class="ev-grp"><td colspan="2"><?= esc($creativeTipeLabel[$ci['tipe']] ?? ucfirst($ci['tipe'])) ?></td></tr>
<?php endif; ?>
<tr>
    <td class="ev-wrap" style="padding-left:1.4rem"><?= esc($ci['nama']) ?></td>
    <td class="r nw fw-semibold"><?= $rp($ci['budget']) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
<tfoot><tr><td>Total</td><td class="r nw" style="color:var(--c-creative)"><?= $rp($creativeBudget) ?></td></tr></tfoot>
</table>
</div>
</div>
<?php endif; ?>

</div><!-- /page-evs -->
<?= $this->endSection() ?>
