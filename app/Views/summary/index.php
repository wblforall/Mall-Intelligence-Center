<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/events-summary.css') ?>?v=<?= @filemtime(FCPATH . 'css/events-summary.css') ?: 1 ?>">
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php
$mallLabels   = ['ewalk' => 'eWalk Simply FUNtastic', 'pentacity' => 'Pentacity Shopping Venue', 'keduanya' => 'eWalk Simply FUNtastic & Pentacity Shopping Venue'];
$statusChip   = ['draft' => 'mute', 'active' => 'ok', 'waiting_data' => 'warn', 'completed' => 'blue'];
$statusLabels = ['draft' => 'Draft', 'active' => 'Active', 'waiting_data' => 'Waiting Data', 'completed' => 'Completed'];
$rp = static fn($n) => 'Rp ' . number_format((int) $n, 0, ',', '.');
$deptBudgetTotal = array_sum(array_column($budgetByDept, 'total'));
$missing = array_diff(array_keys($requiredModules), array_keys($completions));
$brClass = ['success' => 'ok', 'warning' => 'warn', 'danger' => 'bad'][$budgetRealColor] ?? 'ok';
$barCls  = ['ok' => 'ev-bar-ok', 'warn' => 'ev-bar-mid', 'bad' => 'ev-bar-low'][$brClass];

// Anggaran vs realisasi per modul (angka dari variabel yang sudah ada)
$avr = [
    ['Departemen', 'bi-buildings', $deptBudgetTotal, null, null],
    ['Program Loyalty', 'bi-star', $loyaltyBudget, $loyaltyBudgetReal, 'loyalty'],
    ['Dekorasi & VM', 'bi-palette', $vmBudget, $vmRealTotal, 'vm'],
    ['Content Event', 'bi-collection-play', $contentBudget, $contentRealisasi, 'content'],
    ['Creative & Design', 'bi-vector-pen', $creativeBudget, $creativeRealisasiTotal, 'creative'],
];
?>
<div class="page-evs">

<!-- Header -->
<div class="ev-head">
    <a href="<?= base_url('events') ?>" class="ev-iconbtn ev-back" title="Kembali ke daftar event" aria-label="Kembali ke daftar event"><i class="bi bi-arrow-left"></i></a>
    <div class="min-w-0">
        <h1 class="ev-title"><?= esc($event['name']) ?></h1>
        <div class="ev-meta">
            <?php if ($event['tema']): ?><span><i class="bi bi-tag"></i><?= esc($event['tema']) ?></span><?php endif; ?>
            <span><i class="bi bi-building"></i><?= $mallLabels[$event['mall']] ?? esc($event['mall']) ?></span>
            <span><i class="bi bi-calendar-range"></i><?= date('d M Y', strtotime($startDate)) ?> – <?= date('d M Y', strtotime($endDate)) ?></span>
            <span><i class="bi bi-clock"></i><?= $event['event_days'] ?> hari</span>
            <?php if (!empty($eventLocations)): ?>
            <span><i class="bi bi-geo-alt"></i><?= esc(implode(', ', array_column($eventLocations, 'nama'))) ?></span>
            <?php endif; ?>
        </div>
    </div>
    <div class="ev-head-act">
        <span class="ev-chip ev-chip-<?= $statusChip[$event['status']] ?? 'mute' ?>"><?= $statusLabels[$event['status']] ?? ucfirst($event['status']) ?></span>
        <a href="<?= base_url('events/'.$event['id'].'/summary/post-event') ?>" target="_blank" class="ev-btn ev-btn-primary">
            <i class="bi bi-file-earmark-text"></i>Laporan Post Event
        </a>
        <div class="ev-menuwrap">
            <button type="button" class="ev-btn" data-ev-menu aria-haspopup="true" aria-expanded="false"><i class="bi bi-three-dots"></i><span class="d-none d-sm-inline">Lainnya</span></button>
            <div class="ev-menu" hidden>
                <a href="<?= base_url('events/'.$event['id'].'/summary/technical-meeting') ?>" target="_blank"><i class="bi bi-printer"></i>Print Technical Meeting</a>
                <a href="<?= base_url('events/'.$event['id'].'/gallery') ?>"><i class="bi bi-images"></i>Gallery Foto</a>
                <a href="<?= base_url('events/'.$event['id'].'/rundown') ?>"><i class="bi bi-clock-history"></i>Rundown</a>
                <a href="<?= base_url('events/'.$event['id'].'/budget') ?>"><i class="bi bi-wallet2"></i>Budget Summary</a>
                <a href="<?= base_url('events/'.$event['id'].'/other-cost') ?>"><i class="bi bi-receipt"></i>Other Cost</a>
            </div>
        </div>
    </div>
</div>
<?= view('partials/event_tabs', ['event' => $event]) ?>

<!-- Data belum lengkap -->
<?php if ($isAfterEvent && ! $allDone): ?>
<div class="ev-note ev-note-warn" role="alert">
    <i class="bi bi-exclamation-triangle-fill"></i>
    <div class="min-w-0">
        <strong>Data belum lengkap.</strong>
        Event ini sudah selesai tapi ada modul yang belum ditandai selesai:
        <?= implode(' ', array_map(fn($m) => '<span class="ev-chip ev-chip-warn">' . esc($requiredModules[$m]) . '</span>', $missing)) ?>
    </div>
</div>
<?php endif; ?>

<?php if (($canApprove ?? false) && ($event['approval_status'] ?? 'approved') !== 'approved'): ?>
<?php $isPendingEvt = ($event['approval_status'] ?? '') === 'pending' ?>
<div class="ev-note <?= $isPendingEvt ? 'ev-note-warn' : 'ev-note-bad' ?>">
    <i class="bi bi-<?= $isPendingEvt ? 'hourglass-split' : 'x-circle-fill' ?>"></i>
    <div class="min-w-0">
        <?php if ($isPendingEvt): ?>
        <strong>Menunggu Persetujuan</strong> — Event ini belum disetujui dan tidak terlihat oleh pengguna lain.
        <?php else: ?>
        <strong>Ditolak</strong><?php if ($event['rejection_reason']): ?> — <?= esc($event['rejection_reason']) ?><?php endif; ?>
        <?php endif; ?>
    </div>
    <div class="ev-note-act">
        <form method="POST" action="<?= base_url('events/'.$event['id'].'/approve') ?>">
            <?= csrf_field() ?>
            <button type="submit" class="ev-btn ev-btn-ok ev-btn-sm" onclick="return confirm('Setujui event ini?')">
                <i class="bi bi-check-lg"></i>Setujui
            </button>
        </form>
        <?php if ($isPendingEvt): ?>
        <button type="button" class="ev-btn ev-btn-bad ev-btn-sm" data-bs-toggle="modal" data-bs-target="#rejectModal">
            <i class="bi bi-x-lg"></i>Tolak
        </button>
        <?php endif; ?>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
<div class="modal-dialog">
<div class="modal-content">
<form method="POST" action="<?= base_url('events/'.$event['id'].'/reject') ?>">
<?= csrf_field() ?>
<div class="modal-header">
    <h5 class="modal-title fw-semibold"><i class="bi bi-x-circle me-2 text-danger"></i>Tolak Event</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
    <p class="text-muted small mb-2">Event: <strong><?= esc($event['name']) ?></strong></p>
    <label class="form-label small fw-semibold">Alasan Penolakan <span class="text-danger">*</span></label>
    <textarea name="rejection_reason" class="form-control" rows="3" required
        placeholder="Jelaskan alasan penolakan..."></textarea>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
    <button type="submit" class="btn btn-danger">Tolak Event</button>
</div>
</form>
</div></div></div>
<?php endif; ?>

<!-- Strip KPI -->
<div class="ev-kpis">
    <div class="ev-kpi">
        <div class="ev-kpi-l">Total Budget</div>
        <div class="ev-kpi-v"><?= $rp($totalBudget) ?></div>
        <div class="ev-kpi-s"><?= $totalBudget > 0 ? 'rencana biaya seluruh modul' : 'belum ada budget' ?></div>
        <?php if ($totalBudget > 0): ?>
        <div class="ev-kpi-rows">
            <?php if ($deptBudgetTotal > 0): ?><div><span><i class="bi bi-buildings me-1"></i>Departemen</span><span><?= $rp($deptBudgetTotal) ?></span></div><?php endif; ?>
            <?php if ($loyaltyBudget > 0): ?><div><span><i class="bi bi-star me-1"></i>Loyalty</span><span><?= $rp($loyaltyBudget) ?></span></div><?php endif; ?>
            <?php if ($vmBudget > 0): ?><div><span><i class="bi bi-palette me-1"></i>VM</span><span><?= $rp($vmBudget) ?></span></div><?php endif; ?>
            <?php if ($contentBudget > 0): ?><div><span><i class="bi bi-collection-play me-1"></i>Content</span><span><?= $rp($contentBudget) ?></span></div><?php endif; ?>
            <?php if ($creativeBudget > 0): ?><div><span><i class="bi bi-vector-pen me-1"></i>Creative</span><span><?= $rp($creativeBudget) ?></span></div><?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    <div class="ev-kpi">
        <div class="ev-kpi-l">Budget Realisasi</div>
        <div class="ev-kpi-v ev-<?= $brClass ?>"><?= $rp($totalBudgetReal) ?></div>
        <div class="ev-kpi-s"><?= $totalBudget > 0 ? $budgetRealPct . '% dari total budget' : 'belum ada realisasi' ?></div>
        <?php if ($totalBudget > 0): ?><div class="ev-bar <?= $barCls ?>"><i style="width:<?= $budgetRealPct ?>%"></i></div><?php endif; ?>
        <?php if ($loyaltyBudgetReal > 0 || $contentRealisasi > 0 || $creativeRealisasiTotal > 0 || $vmRealTotal > 0): ?>
        <div class="ev-kpi-rows">
            <?php if ($loyaltyBudgetReal > 0): ?><div><span><i class="bi bi-star me-1"></i>Loyalty</span><span><?= $rp($loyaltyBudgetReal) ?></span></div><?php endif; ?>
            <?php if ($vmRealTotal > 0): ?><div><span><i class="bi bi-palette me-1"></i>VM</span><span><?= $rp($vmRealTotal) ?></span></div><?php endif; ?>
            <?php if ($contentRealisasi > 0): ?><div><span><i class="bi bi-collection-play me-1"></i>Content</span><span><?= $rp($contentRealisasi) ?></span></div><?php endif; ?>
            <?php if ($creativeRealisasiTotal > 0): ?><div><span><i class="bi bi-vector-pen me-1"></i>Creative</span><span><?= $rp($creativeRealisasiTotal) ?></span></div><?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    <div class="ev-kpi">
        <div class="ev-kpi-l">Total Revenue</div>
        <div class="ev-kpi-v ev-ok"><?= $rp($totalRevenue) ?></div>
        <div class="ev-kpi-s"><?= $totalRevenue > 0 ? 'exhibition + sponsor cash' : 'belum ada pendapatan' ?></div>
        <?php if ($totalRevenue > 0): ?>
        <div class="ev-kpi-rows">
            <?php if ($totalDealing > 0): ?><div><span><i class="bi bi-shop me-1"></i>Exhibition</span><span><?= $rp($totalDealing) ?></span></div><?php endif; ?>
            <?php if ($totalSponsorCash > 0): ?><div><span><i class="bi bi-award me-1"></i>Sponsor Cash</span><span><?= $rp($totalSponsorCash) ?></span></div><?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    <div class="ev-kpi">
        <div class="ev-kpi-l">Margin Profit</div>
        <div class="ev-kpi-v <?= $profitPositive ? 'ev-cyan' : 'ev-bad' ?>"><?= $profitPositive ? '' : '-' ?><?= $rp(abs($profit)) ?></div>
        <div class="ev-kpi-s <?= $profitPositive ? '' : 'ev-bad' ?>"><?= ($marginPct >= 0 ? '+' : '') ?><?= $marginPct ?>% margin · Revenue − Realisasi</div>
    </div>
</div>

<!-- Status modul -->
<div class="ev-panel">
    <div class="ev-panel-h">
        <h2 class="ev-panel-t"><i class="bi bi-<?= $allDone ? 'check-circle-fill' : 'list-check' ?>"></i>Status Penyelesaian Data</h2>
        <div class="ev-panel-end"><span class="ev-chip ev-chip-<?= $allDone ? 'ok' : ($isAfterEvent ? 'warn' : 'mute') ?>"><?= count($completions) ?>/<?= count($requiredModules) ?> modul selesai</span></div>
    </div>
    <div class="ev-mods">
    <?php foreach ($requiredModules as $key => $label):
        $done = isset($completions[$key]);
        $late = ! $done && $isAfterEvent;
        $modUrl = ['content' => 'content', 'loyalty' => 'loyalty', 'vm' => 'vm', 'creative' => 'creative', 'exhibitors' => 'exhibitors', 'sponsors' => 'sponsors'][$key] ?? null; ?>
        <a class="ev-mod <?= $done ? 'is-done' : ($late ? 'is-late' : 'is-open') ?>" <?= $modUrl ? 'href="' . base_url('events/' . $event['id'] . '/' . $modUrl) . '"' : '' ?> style="color:inherit">
            <i class="bi bi-<?= $done ? 'check-circle-fill' : ($late ? 'exclamation-circle-fill' : 'circle') ?>"></i>
            <div class="min-w-0">
                <div class="ev-mod-n"><?= esc($label) ?></div>
                <div class="ev-mod-s"><?= $done ? esc($completions[$key]['completed_by_name']) . ' · ' . date('d M', strtotime($completions[$key]['completed_at'])) : ($late ? 'Belum selesai — perlu dilengkapi' : 'Belum ditandai selesai') ?></div>
            </div>
        </a>
    <?php endforeach; ?>
    </div>
</div>

<!-- Anggaran vs Realisasi & Hasil vs Target -->
<div class="ev-grid2" style="margin-bottom:1rem">
    <div class="ev-panel">
        <div class="ev-panel-h"><h2 class="ev-panel-t"><i class="bi bi-wallet2"></i>Anggaran vs Realisasi</h2>
            <div class="ev-panel-end"><a href="<?= base_url('events/'.$event['id'].'/budget') ?>" class="ev-btn ev-btn-sm">Detail budget<i class="bi bi-arrow-right"></i></a></div></div>
        <div class="ev-tablewrap">
        <table class="ev-table">
            <thead><tr><th>Modul</th><th class="r">Budget</th><th class="r">Realisasi</th><th class="r">Sisa / Lebih</th></tr></thead>
            <tbody>
            <?php foreach ($avr as [$nm, $ic, $bud, $real, $k]):
                if ($bud <= 0 && ($real ?? 0) <= 0) continue;
                $sel = $real === null ? null : $bud - $real;
                $pc  = ($real !== null && $bud > 0) ? min(100, round($real / $bud * 100)) : null; ?>
            <tr>
                <td><i class="bi <?= $ic ?> me-1 ev-mute"></i><?= $nm ?></td>
                <td class="r ev-nw"><?= $bud > 0 ? $rp($bud) : '—' ?></td>
                <td class="r ev-nw"><?= $real === null ? '<span class="ev-mute">—</span>' : ($real > 0 ? $rp($real) : '<span class="ev-mute">belum ada</span>') ?>
                    <?php if ($pc !== null && $real > 0): ?><span class="ev-sub"><?= $pc ?>% dari budget</span><?php endif; ?></td>
                <td class="r ev-nw <?= $sel === null ? '' : ($sel < 0 ? 'ev-bad' : ($real > 0 ? 'ev-ok' : 'ev-mute')) ?>">
                    <?= $sel === null ? '—' : ($sel < 0 ? '+' . $rp(-$sel) . ' lebih' : $rp($sel) . ' sisa') ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot><tr>
                <td>Total</td><td class="r ev-nw"><?= $rp($totalBudget) ?></td>
                <td class="r ev-nw <?= 'ev-' . $brClass ?>"><?= $rp($totalBudgetReal) ?></td>
                <td class="r ev-nw"><?= $totalBudget > 0 ? $budgetRealPct . '%' : '—' ?></td>
            </tr></tfoot>
        </table>
        </div>
    </div>
    <div class="ev-panel">
        <div class="ev-panel-h"><h2 class="ev-panel-t"><i class="bi bi-bullseye"></i>Hasil vs Target</h2>
            <div class="ev-panel-end"><a href="<?= base_url('events/'.$event['id'].'/exhibitors') ?>" class="ev-btn ev-btn-sm">Exhibition<i class="bi bi-arrow-right"></i></a></div></div>
        <div class="ev-panel-b">
            <?php if ($tgtExNilai > 0 || $tgtExJumlah > 0): ?>
                <?php if ($tgtExNilai > 0): ?>
                <div class="d-flex justify-content-between gap-2 flex-wrap"><b>Nilai dealing exhibition</b><span class="ev-<?= ['success'=>'ok','warning'=>'warn','danger'=>'bad','primary'=>'cyan'][$colorExNilai] ?? 'cyan' ?> fw-bold"><?= $pctExNilai ?>%</span></div>
                <div class="ev-bar ev-bar-ok" style="margin:.4rem 0"><i style="width:<?= $pctExNilai ?>%"></i></div>
                <div class="ev-sub"><?= $rp($totalDealing) ?> dari target <?= $rp($tgtExNilai) ?></div>
                <?php endif; ?>
                <?php if ($tgtExJumlah > 0): ?>
                <div class="d-flex justify-content-between gap-2 flex-wrap <?= $tgtExNilai > 0 ? 'mt-3' : '' ?>"><b>Jumlah exhibitor</b><span class="fw-bold"><?= $pctExJumlah ?>%</span></div>
                <div class="ev-bar ev-bar-blue" style="margin:.4rem 0"><i style="width:<?= $pctExJumlah ?>%"></i></div>
                <div class="ev-sub"><?= count($exhibitors) ?> dari target <?= $tgtExJumlah ?> exhibitor</div>
                <?php endif; ?>
            <?php else: ?>
                <div class="ev-empty-sm">Belum ada target exhibition.<br>Hasil: <?= count($exhibitors) ?> exhibitor · <?= $rp($totalDealing) ?></div>
            <?php endif; ?>
            <div class="d-flex justify-content-between gap-2 flex-wrap mt-3 pt-3" style="border-top:1px solid var(--ev-border)">
                <span class="ev-mute"><i class="bi bi-person-walking me-1"></i>Traffic pengunjung</span><b><?= number_format($totalTraffic) ?></b>
            </div>
            <div class="d-flex justify-content-between gap-2 flex-wrap mt-1">
                <span class="ev-mute"><i class="bi bi-car-front me-1"></i>Kendaraan</span><b><?= number_format($totalKendaraan) ?></b>
            </div>
        </div>
    </div>
</div>

<!-- Charts -->
<div class="ev-grid2 mb-3">
<div>
    <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-person-walking me-2"></i>Traffic Pengunjung Harian</h6>
            <?php if ($totalTraffic > 0): ?>
            <div class="text-end" style="font-size:.78rem">
                <div class="fw-bold text-primary"><?= number_format($totalTraffic) ?> total</div>
                <div class="text-muted d-flex gap-2 justify-content-end">
                    <?php if ($totalEwalk > 0): ?><span>eWalk <?= number_format($totalEwalk) ?></span><?php endif; ?>
                    <?php if ($totalPenta > 0): ?><span>Penta <?= number_format($totalPenta) ?></span><?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <?php if ($totalTraffic === 0): ?>
            <p class="text-muted text-center py-4 mb-0">Data traffic belum tersedia.</p>
            <?php else: ?>
            <canvas id="trafficChart" height="130"></canvas>
            <?php endif; ?>
        </div>
    </div>
</div>
<div>
    <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-car-front me-2"></i>Kendaraan Harian</h6>
            <?php if ($totalKendaraan > 0): ?>
            <div class="text-end" style="font-size:.78rem">
                <div class="fw-bold text-warning"><?= number_format($totalKendaraan) ?> total</div>
                <div class="text-muted d-flex gap-2 justify-content-end">
                    <?php if ($totalMobil > 0): ?><span>Mobil <?= number_format($totalMobil) ?></span><?php endif; ?>
                    <?php if ($totalMotor > 0): ?><span>Motor <?= number_format($totalMotor) ?></span><?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <?php if ($totalKendaraan === 0): ?>
            <p class="text-muted text-center py-4 mb-0">Data kendaraan belum tersedia.</p>
            <?php else: ?>
            <canvas id="vehicleChart" height="110"></canvas>
            <?php endif; ?>
        </div>
    </div>
</div>
</div><!-- /charts row -->

<!-- Tabel kendaraan harian per jenis -->
<?php if ($vehicleGrandTotal > 0): ?>
<div class="mb-3">
<div>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-table me-2"></i>Kendaraan Harian per Jenis</h6>
            <span class="fw-bold text-warning" style="font-size:.82rem"><?= number_format($vehicleGrandTotal) ?> total</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0 text-center small">
                    <thead class="table-light">
                        <tr>
                            <th class="text-start ps-3">Tanggal</th>
                            <?php foreach ($vehicleActiveTypes as $label): ?>
                            <th class="text-end"><?= esc($label) ?></th>
                            <?php endforeach; ?>
                            <th class="text-end pe-3">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vehicleDaily as $row): ?>
                        <tr>
                            <td class="text-start ps-3 fw-medium"><?= esc($row['label']) ?></td>
                            <?php foreach ($vehicleActiveTypes as $key => $label): ?>
                            <td class="text-end<?= $row['counts'][$key] === 0 ? ' text-muted' : '' ?>"><?= number_format($row['counts'][$key]) ?></td>
                            <?php endforeach; ?>
                            <td class="text-end pe-3 fw-semibold"><?= number_format($row['total']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td class="text-start ps-3">Total</td>
                            <?php foreach ($vehicleActiveTypes as $key => $label): ?>
                            <td class="text-end"><?= number_format($vehicleTypeTotals[$key]) ?></td>
                            <?php endforeach; ?>
                            <td class="text-end pe-3 text-warning"><?= number_format($vehicleGrandTotal) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
</div>
<?php endif; ?>

<!-- Cards -->
<div class="ev-grid2">
<div class="ev-stack">

    <!-- Content -->
    <?php if (!empty($contentItems)): ?>
    <div class="card ev-pm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-collection-play me-2"></i>Content Event</h6>
            <div class="d-flex align-items-center gap-2">
                <?php if ($contentBudget > 0): ?>
                <span class="small fw-semibold text-muted">Rp <?= number_format($contentBudget,0,',','.') ?></span>
                <?php endif; ?>
                <a href="<?= base_url('events/'.$event['id'].'/content') ?>" class="ev-btn ev-btn-sm" title="Buka modul"><i class="bi bi-box-arrow-up-right"></i><span class="d-none d-sm-inline">Buka</span></a>
            </div>
        </div>
        <div class="card-body p-0">
        <div class="list-group list-group-flush" data-ev-limit="8">
            <?php foreach ($contentItems as $ci):
                $ciRealisasi = $contentRealisasiByItem[$ci['id']] ?? [];
                $ciTotal     = array_sum(array_column($ciRealisasi, 'nilai'));
                $isBiaya     = ($ci['tipe'] ?? 'program') === 'biaya';
                $jam = '';
                if ($ci['waktu_mulai'])   $jam  = substr($ci['waktu_mulai'], 0, 5);
                if ($ci['waktu_selesai']) $jam .= '–' . substr($ci['waktu_selesai'], 0, 5);
            ?>
            <div class="list-group-item py-2 px-3">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div class="min-w-0">
                        <div class="d-flex align-items-center flex-wrap gap-1 mb-1">
                            <?php if ($isBiaya): ?>
                            <span class="badge bg-danger-subtle text-danger" style="font-size:.65rem">Biaya</span>
                            <?php else: ?>
                            <span class="badge bg-primary-subtle text-primary" style="font-size:.65rem">Program</span>
                            <?php endif; ?>
                            <?php if ($ci['jenis']): ?>
                            <span class="badge bg-secondary-subtle text-secondary" style="font-size:.65rem"><?= esc($ci['jenis']) ?></span>
                            <?php endif; ?>
                            <span class="small fw-semibold"><?= esc($ci['nama']) ?></span>
                        </div>
                        <div class="d-flex flex-wrap gap-2 text-muted" style="font-size:.72rem">
                            <?php if ($ci['tanggal']): ?>
                            <span><i class="bi bi-calendar me-1"></i><?= date('d M', strtotime($ci['tanggal'])) ?><?= $jam ? ' · '.$jam : '' ?></span>
                            <?php endif; ?>
                            <?php if ($ci['lokasi']): ?>
                            <span><i class="bi bi-geo-alt me-1"></i><?= esc($ci['lokasi']) ?></span>
                            <?php endif; ?>
                            <?php if ($ci['pic']): ?>
                            <span><i class="bi bi-person me-1"></i><?= esc($ci['pic']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="text-end flex-shrink-0" style="font-size:.78rem">
                        <?php if ($ci['budget'] > 0): ?>
                        <div class="text-muted">Budget: <strong>Rp <?= number_format($ci['budget'],0,',','.') ?></strong></div>
                        <?php endif; ?>
                        <?php if ($ciTotal > 0): ?>
                        <?php $cPct = $ci['budget'] > 0 ? min(100, round($ciTotal / $ci['budget'] * 100)) : null; ?>
                        <div class="text-<?= ($ciTotal > $ci['budget'] && $ci['budget'] > 0) ? 'danger' : 'success' ?> fw-semibold">
                            Realisasi: Rp <?= number_format($ciTotal,0,',','.') ?>
                        </div>
                        <?php if ($cPct !== null): ?>
                        <div class="text-muted"><?= $cPct ?>%</div>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if ($contentBudget > 0 || $contentRealisasi > 0): ?>
            <div class="list-group-item py-2 px-3 bg-light">
                <div class="d-flex justify-content-between">
                    <span class="small fw-bold">Total Budget</span>
                    <span class="small fw-bold text-muted">Rp <?= number_format($contentBudget,0,',','.') ?></span>
                </div>
                <?php if ($contentRealisasi > 0): ?>
                <div class="d-flex justify-content-between">
                    <span class="small fw-bold">Total Realisasi</span>
                    <span class="small fw-bold text-success">Rp <?= number_format($contentRealisasi,0,',','.') ?></span>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Exhibition by Casual Leasing -->
    <div class="card ev-pm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-shop me-2"></i>Exhibition by Casual Leasing</h6>
            <a href="<?= base_url('events/'.$event['id'].'/exhibitors') ?>" class="ev-btn ev-btn-sm" title="Buka modul"><i class="bi bi-box-arrow-up-right"></i><span class="d-none d-sm-inline">Buka</span></a>
        </div>
        <div class="card-body p-0">
        <?php if (empty($exhibitors)): ?>
        <p class="text-muted text-center py-3 small mb-0">Belum ada exhibition.</p>
        <?php else: ?>
        <div class="list-group list-group-flush">
            <?php foreach ($exhibitorsByKat as $kat => $exList): ?>
            <div class="list-group-item py-1 px-3 bg-light d-flex justify-content-between align-items-center">
                <span class="small fw-semibold text-uppercase text-muted" style="letter-spacing:.04em">
                    <i class="bi bi-tag me-1"></i><?= esc($kat) ?>
                </span>
                <span class="badge bg-secondary-subtle text-secondary"><?= count($exList) ?></span>
            </div>
            <?php foreach ($exList as $ex):
                $exProgs = $programsByExhibitor[$ex['id']] ?? [];
            ?>
            <div class="list-group-item px-3 py-2">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="small fw-semibold lh-sm">
                        <?php if ($ex['lokasi_booth']): ?><span class="text-muted fw-normal"><?= esc($ex['lokasi_booth']) ?></span> — <?php endif; ?><?= esc($ex['nama_exhibitor']) ?>
                    </div>
                    <span class="small fw-bold text-success ms-2 text-nowrap">Rp <?= number_format($ex['nilai_dealing'],0,',','.') ?></span>
                </div>
                <?php if (!empty($exProgs)): ?>
                <div class="mt-2 pt-1 border-top">
                    <div class="mb-1 text-muted" style="font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em">
                        Program by <?= esc($ex['nama_exhibitor']) ?>
                    </div>
                    <?php foreach ($exProgs as $p):
                        $jam = '';
                        if ($p['jam_mulai'])   $jam  = substr($p['jam_mulai'], 0, 5);
                        if ($p['jam_selesai']) $jam .= '–'.substr($p['jam_selesai'], 0, 5);
                        $periode = '';
                        if ($p['tanggal_mulai']) {
                            $periode = date('d/m', strtotime($p['tanggal_mulai']));
                            if ($p['tanggal_selesai'] && $p['tanggal_selesai'] !== $p['tanggal_mulai'])
                                $periode .= '–'.date('d/m', strtotime($p['tanggal_selesai']));
                        }
                    ?>
                    <div class="d-flex justify-content-between align-items-baseline" style="font-size:.75rem">
                        <span class="text-body-secondary"><i class="bi bi-dot"></i><?= esc($p['nama_program']) ?></span>
                        <?php if ($periode || $jam): ?>
                        <span class="text-primary fw-medium text-nowrap ms-2"><?= $periode ?><?= ($periode && $jam) ? ' ' : '' ?><?= $jam ?></span>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php endforeach; ?>
            <div class="list-group-item px-3 py-2 bg-light">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="small fw-bold">Total Dealing</span>
                    <span class="small fw-bold text-<?= $colorExNilai ?>">Rp <?= number_format($totalDealing,0,',','.') ?></span>
                </div>
                <?php if ($tgtExNilai > 0): ?>
                <div class="progress mt-1" style="height:4px">
                    <div class="progress-bar bg-<?= $colorExNilai ?>" style="width:<?= $pctExNilai ?>%"></div>
                </div>
                <div class="d-flex justify-content-between mt-1">
                    <span class="text-<?= $colorExNilai ?> fw-semibold" style="font-size:.7rem"><?= $pctExNilai ?>% dari target</span>
                    <span class="text-muted" style="font-size:.7rem">Target: Rp <?= number_format($tgtExNilai,0,',','.') ?></span>
                </div>
                <?php endif; ?>
                <?php if ($tgtExJumlah > 0): ?>
                <div class="d-flex justify-content-between mt-1">
                    <span class="text-muted" style="font-size:.7rem"><?= count($exhibitors) ?>/<?= $tgtExJumlah ?> exhibitor</span>
                    <span class="badge bg-<?= $colorExJumlah ?>-subtle text-<?= $colorExJumlah ?>" style="font-size:.65rem"><?= $pctExJumlah ?>%</span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        </div>
    </div>

    <!-- Sponsorship -->
    <div class="card ev-pm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-award me-2"></i>Sponsorship</h6>
            <a href="<?= base_url('events/'.$event['id'].'/sponsors') ?>" class="ev-btn ev-btn-sm" title="Buka modul"><i class="bi bi-box-arrow-up-right"></i><span class="d-none d-sm-inline">Buka</span></a>
        </div>
        <div class="card-body p-0">
        <?php if (empty($sponsors)): ?>
        <p class="text-muted text-center py-3 small mb-0">Belum ada sponsor.</p>
        <?php else: ?>
        <div class="list-group list-group-flush" data-ev-limit="8">
            <?php foreach ($sponsors as $sp): ?>
            <div class="list-group-item py-2">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="small fw-medium"><?= esc($sp['nama_sponsor']) ?></div>
                        <?php if ($sp['jenis'] === 'barang'): ?>
                        <?php foreach (($itemsBySponsors[$sp['id']] ?? []) as $itm): ?>
                        <div class="small text-muted"><?= esc($itm['deskripsi_barang'] ?: '—') ?><?= $itm['qty'] ? ' · '.number_format($itm['qty']).' pcs' : '' ?></div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="text-end ms-2">
                        <span class="badge <?= $sp['jenis'] === 'cash' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' ?> small"><?= $sp['jenis'] === 'cash' ? 'Cash' : 'In-Kind' ?></span>
                        <div class="small fw-bold mt-1">
                            <?php if ($sp['jenis'] === 'cash'): ?>
                            Rp <?= number_format($sp['nilai'],0,',','.') ?>
                            <?php else: ?>
                            <?= number_format(array_sum(array_column($itemsBySponsors[$sp['id']] ?? [], 'qty'))) ?> pcs
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <div class="list-group-item py-2 bg-light">
                <div class="d-flex justify-content-between">
                    <span class="small fw-bold">Cash</span>
                    <span class="small fw-bold text-success">Rp <?= number_format($totalSponsorCash,0,',','.') ?></span>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="small fw-bold">In-Kind</span>
                    <span class="small fw-bold text-warning"><?= number_format($totalSponsorInKindQty) ?> pcs</span>
                </div>
            </div>
        </div>
        <?php endif; ?>
        </div>
    </div>

</div><!-- /col left -->
<div class="ev-stack">

    <!-- Loyalty Programs -->
    <div class="card ev-pm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-star me-2"></i>Program Loyalty</h6>
            <a href="<?= base_url('events/'.$event['id'].'/loyalty') ?>" class="ev-btn ev-btn-sm" title="Buka modul"><i class="bi bi-box-arrow-up-right"></i><span class="d-none d-sm-inline">Buka</span></a>
        </div>
        <div class="card-body p-0">
        <?php if (empty($programs)): ?>
        <p class="text-muted text-center py-3 small mb-0">Belum ada program loyalty.</p>
        <?php else: ?>
        <div class="list-group list-group-flush">
            <?php foreach ($programs as $pr):
                $pid     = $pr['id'];
                $rData   = $realisasi[$pid] ?? ['total' => 0, 'entries' => []];
                $rTotal  = (int)$rData['total'];

                // Voucher aggregates
                $myVouchers   = $voucherItems[$pid] ?? [];
                $vTotalQty    = 0; $vTotalTerpakai = 0; $vTotalTersebar = 0; $vBudget = 0; $vRealNilai = 0;
                foreach ($myVouchers as $vi) {
                    $vTotalQty    += (int)$vi['total_diterbitkan'];
                    $vBudget      += (int)$vi['total_diterbitkan'] * (int)$vi['nilai_voucher'];
                    $vr = $voucherRealisasi[$vi['id']] ?? [];
                    $vTotalTerpakai  += (int)($vr['total_terpakai']  ?? 0);
                    $vTotalTersebar  += (int)($vr['total_tersebar']  ?? 0);
                    $vRealNilai      += (int)($vr['total_terpakai']  ?? 0) * (int)$vi['nilai_voucher'];
                }

                // Hadiah aggregates
                $myHadiah      = $hadiahItems[$pid] ?? [];
                $hTotalStok    = 0; $hTotalDibagi = 0; $hBudget = 0; $hRealNilai = 0;
                foreach ($myHadiah as $hi) {
                    $hTotalStok  += (int)$hi['stok'];
                    $hBudget     += (int)$hi['stok'] * (int)$hi['nilai_satuan'];
                    $hr = $hadiahRealisasi[$hi['id']] ?? [];
                    $hTotalDibagi  += (int)($hr['total'] ?? 0);
                    $hRealNilai    += (int)($hr['total'] ?? 0) * (int)$hi['nilai_satuan'];
                }

                $autoBudget = $vBudget + $hBudget;
                $progReal   = $vRealNilai + $hRealNilai;
                $memberTarget = (int)($pr['target_peserta'] ?? 0);
                $memberPct    = $memberTarget > 0 ? min(100, round($rTotal / $memberTarget * 100)) : null;
                $memColor     = $memberPct >= 100 ? 'success' : ($memberPct >= 60 ? 'primary' : ($memberPct >= 30 ? 'warning' : 'danger'));
            ?>
            <div class="list-group-item py-2 px-3">
                <div class="d-flex justify-content-between align-items-start mb-1">
                    <span class="small fw-semibold"><?= esc($pr['nama_program']) ?></span>
                    <div class="text-end flex-shrink-0 ms-2" style="font-size:.78rem">
                        <?php if ($autoBudget > 0): ?>
                        <div class="text-muted">Rp <?= number_format($autoBudget,0,',','.') ?></div>
                        <?php endif; ?>
                        <?php if ($progReal > 0): ?>
                        <?php $prPct = $autoBudget > 0 ? min(100, round($progReal / $autoBudget * 100)) : null; ?>
                        <div class="fw-semibold text-<?= ($progReal > $autoBudget && $autoBudget > 0) ? 'danger' : 'success' ?>">
                            Realisasi: Rp <?= number_format($progReal,0,',','.') ?>
                        </div>
                        <?php if ($prPct !== null): ?>
                        <div class="progress mt-1" style="height:3px;width:80px;margin-left:auto">
                            <div class="progress-bar bg-<?= ($progReal > $autoBudget && $autoBudget > 0) ? 'danger' : 'success' ?>" style="width:<?= $prPct ?>%"></div>
                        </div>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <?php
                $targetAktif = (int)($pr['target_member_aktif'] ?? 0);
                $rAktif      = (int)($rData['total_aktif'] ?? 0);
                ?>
                <?php if ($memberTarget > 0 || $rTotal > 0): ?>
                <div class="mb-1" style="font-size:.72rem">
                    <span class="badge bg-primary-subtle text-primary me-1"><i class="bi bi-person-plus"></i> Member</span>
                    <?php if ($memberTarget > 0): ?>
                    <span class="text-muted"><?= number_format($rTotal) ?> / <?= number_format($memberTarget) ?></span>
                    <?php if ($memberPct !== null): ?>
                    <span class="fw-semibold text-<?= $memColor ?> ms-1"><?= $memberPct ?>%</span>
                    <?php endif; ?>
                    <?php else: ?>
                    <span class="text-muted"><?= number_format($rTotal) ?> member</span>
                    <?php endif; ?>
                    <?php if ($targetAktif > 0 || $rAktif > 0): ?>
                    <span class="text-muted ms-2">· aktif <?= number_format($rAktif) ?><?= $targetAktif > 0 ? ' <span class="text-body-tertiary">/ target '.number_format($targetAktif).'</span>' : '' ?></span>
                    <?php endif; ?>
                </div>
                <?php if ($memberTarget > 0): ?>
                <div class="progress mb-2" style="height:3px">
                    <div class="progress-bar bg-<?= $memColor ?>" style="width:<?= $memberPct ?>%"></div>
                </div>
                <?php endif; ?>
                <?php endif; ?>

                <?php if (!empty($myVouchers)): ?>
                <div style="font-size:.72rem">
                    <span class="badge bg-warning-subtle text-warning me-1"><i class="bi bi-ticket-perforated"></i> e-Voucher</span>
                    <span class="text-muted"><?= count($myVouchers) ?> item · <?= number_format($vTotalQty) ?> diterbitkan</span>
                    <?php if ($vTotalTerpakai > 0 || $vTotalTersebar > 0): ?>
                    <span class="text-muted ms-1">· tersebar <?= number_format($vTotalTersebar) ?> · terpakai <?= number_format($vTotalTerpakai) ?></span>
                    <?php endif; ?>
                    <?php if ($vTotalQty > 0 && $vTotalTerpakai > 0): ?>
                    <?php $vPct = min(100, round($vTotalTerpakai / $vTotalQty * 100)); $vCol = $vPct >= 100 ? 'success' : ($vPct >= 60 ? 'primary' : 'secondary'); ?>
                    <span class="fw-semibold text-<?= $vCol ?> ms-1"><?= $vPct ?>%</span>
                    <div class="progress mt-1 mb-1" style="height:3px">
                        <div class="progress-bar bg-<?= $vCol ?>" style="width:<?= $vPct ?>%"></div>
                    </div>
                    <?php endif; ?>
                    <?php foreach ($myVouchers as $vi): ?>
                    <?php if (($vi['target_penyerapan'] ?? null) !== null && $vi['target_penyerapan'] !== ''): ?>
                    <div class="text-body-tertiary mt-1">
                        <i class="bi bi-arrow-return-right me-1"></i><?= esc($vi['nama_voucher']) ?>: target penyerapan <?= (float)$vi['target_penyerapan'] ?>%
                    </div>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($myHadiah)): ?>
                <div style="font-size:.72rem" class="mt-1">
                    <span class="badge bg-success-subtle text-success me-1"><i class="bi bi-gift"></i> Hadiah</span>
                    <span class="text-muted"><?= count($myHadiah) ?> item · stok <?= number_format($hTotalStok) ?></span>
                    <?php if ($hTotalDibagi > 0): ?>
                    <span class="text-muted ms-1">· dibagikan <?= number_format($hTotalDibagi) ?></span>
                    <?php endif; ?>
                    <?php if ($hTotalStok > 0 && $hTotalDibagi > 0): ?>
                    <?php $hPct = min(100, round($hTotalDibagi / $hTotalStok * 100)); $hCol = $hPct >= 100 ? 'success' : ($hPct >= 60 ? 'primary' : 'secondary'); ?>
                    <span class="fw-semibold text-<?= $hCol ?> ms-1"><?= $hPct ?>%</span>
                    <div class="progress mt-1" style="height:3px">
                        <div class="progress-bar bg-<?= $hCol ?>" style="width:<?= $hPct ?>%"></div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if (empty($myVouchers) && empty($myHadiah) && $memberTarget === 0 && $rTotal === 0): ?>
                <div class="small text-muted fst-italic">Belum ada data.</div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php if ($loyaltyBudget > 0 || $loyaltyBudgetReal > 0): ?>
            <div class="list-group-item py-2 px-3 bg-light">
                <?php if ($loyaltyBudget > 0): ?>
                <div class="d-flex justify-content-between">
                    <span class="small fw-bold">Total Budget</span>
                    <span class="small fw-bold text-muted">Rp <?= number_format($loyaltyBudget,0,',','.') ?></span>
                </div>
                <?php endif; ?>
                <?php if ($loyaltyBudgetReal > 0): ?>
                <div class="d-flex justify-content-between">
                    <span class="small fw-bold">Total Realisasi</span>
                    <span class="small fw-bold text-<?= $loyaltyBudgetReal > $loyaltyBudget && $loyaltyBudget > 0 ? 'danger' : 'success' ?>">Rp <?= number_format($loyaltyBudgetReal,0,',','.') ?></span>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        </div>
    </div>

    <!-- VM -->
    <?php if (!empty($vmItems)): ?>
    <div class="card ev-pm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-palette me-2"></i>Visual Merchandising</h6>
            <div class="d-flex align-items-center gap-2">
                <?php if ($vmBudget > 0): ?>
                <span class="small fw-semibold text-muted">Rp <?= number_format($vmBudget,0,',','.') ?></span>
                <?php endif; ?>
                <a href="<?= base_url('events/'.$event['id'].'/vm') ?>" class="ev-btn ev-btn-sm" title="Buka modul"><i class="bi bi-box-arrow-up-right"></i><span class="d-none d-sm-inline">Buka</span></a>
            </div>
        </div>
        <div class="card-body p-0">
        <div class="list-group list-group-flush">
            <?php foreach ($vmItems as $vm):
                $vmReal  = $vmRealisasi[$vm['id']] ?? ['total' => 0];
                $vmPct   = $vm['budget'] > 0 ? min(100, round($vmReal['total'] / $vm['budget'] * 100)) : null;
                $vmColor = ($vmReal['total'] > $vm['budget'] && $vm['budget'] > 0) ? 'danger' : 'success';
            ?>
            <div class="list-group-item py-2 px-3">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div class="small fw-semibold lh-sm"><?= esc($vm['nama_item']) ?></div>
                    <div class="text-end flex-shrink-0" style="font-size:.78rem">
                        <?php if ($vm['budget'] > 0): ?>
                        <div class="text-muted">Rp <?= number_format($vm['budget'],0,',','.') ?></div>
                        <?php endif; ?>
                        <?php if ($vmReal['total'] > 0): ?>
                        <div class="fw-semibold text-<?= $vmColor ?>">
                            Realisasi: Rp <?= number_format($vmReal['total'],0,',','.') ?>
                        </div>
                        <?php if ($vmPct !== null): ?>
                        <div class="progress mt-1" style="height:3px;width:80px;margin-left:auto">
                            <div class="progress-bar bg-<?= $vmColor ?>" style="width:<?= $vmPct ?>%"></div>
                        </div>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($vm['deskripsi_referensi']): ?>
                <div class="small text-muted mt-1" style="white-space:pre-line"><?= esc($vm['deskripsi_referensi']) ?></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php if ($vmBudget > 0 || $vmRealTotal > 0): ?>
            <div class="list-group-item py-2 px-3 bg-light">
                <?php if ($vmBudget > 0): ?>
                <div class="d-flex justify-content-between">
                    <span class="small fw-bold">Total Budget VM</span>
                    <span class="small fw-bold text-muted">Rp <?= number_format($vmBudget,0,',','.') ?></span>
                </div>
                <?php endif; ?>
                <?php if ($vmRealTotal > 0): ?>
                <div class="d-flex justify-content-between">
                    <span class="small fw-bold">Total Realisasi</span>
                    <span class="small fw-bold text-<?= $vmRealTotal > $vmBudget && $vmBudget > 0 ? 'danger' : 'success' ?>">Rp <?= number_format($vmRealTotal,0,',','.') ?></span>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Creative & Design -->
    <?php if (!empty($creativeItems)): ?>
    <?php
    $tipeLabels = ['master_design' => 'Master Design', 'digital' => 'Digital', 'cetak' => 'Cetak', 'influencer' => 'Influencer', 'media_prescon' => 'Media Prescon'];
    $tipeIcons  = ['master_design' => 'bi-vector-pen', 'digital' => 'bi-phone', 'cetak' => 'bi-printer', 'influencer' => 'bi-person-video3', 'media_prescon' => 'bi-newspaper'];
    $statusBadge = ['draft' => 'bg-secondary-subtle text-secondary', 'review' => 'bg-warning-subtle text-warning', 'approved' => 'bg-success-subtle text-success', 'revision' => 'bg-danger-subtle text-danger'];
    $statusLabel = ['draft' => 'Draft', 'review' => 'Review', 'approved' => 'Approved', 'revision' => 'Revisi'];
    $platformLbl = ['ig' => 'IG', 'tiktok' => 'TikTok', 'keduanya' => 'IG+TikTok'];
    ?>
    <div class="card ev-pm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-vector-pen me-2"></i>Creative &amp; Design</h6>
            <div class="d-flex align-items-center gap-2">
                <?php if ($creativeBudget > 0): ?>
                <span class="small fw-semibold text-muted">Rp <?= number_format($creativeBudget,0,',','.') ?></span>
                <?php endif; ?>
                <a href="<?= base_url('events/'.$event['id'].'/creative') ?>" class="ev-btn ev-btn-sm" title="Buka modul"><i class="bi bi-box-arrow-up-right"></i><span class="d-none d-sm-inline">Buka</span></a>
            </div>
        </div>
        <div class="card-body p-0">
        <div class="list-group list-group-flush">
            <?php foreach ($creativeItems as $ci):
                $ciRData   = $creativeRealisasi[$ci['id']] ?? ['total' => 0, 'entries' => []];
                $ciTotal   = (int)$ciRData['total'];
                $ciBudget  = (int)$ci['budget'];
                $ciInsight = $creativeInsights[$ci['id']] ?? null;
            ?>
            <div class="list-group-item py-2 px-3">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div class="min-w-0 flex-grow-1">
                        <div class="d-flex align-items-center flex-wrap gap-1 mb-1">
                            <span class="badge bg-light border text-muted" style="font-size:.62rem">
                                <i class="<?= $tipeIcons[$ci['tipe']] ?? 'bi-circle' ?> me-1"></i><?= $tipeLabels[$ci['tipe']] ?? $ci['tipe'] ?>
                            </span>
                            <?php if ($ci['tipe'] === 'master_design'): ?>
                            <span class="badge <?= $statusBadge[$ci['status']] ?? '' ?>" style="font-size:.62rem"><?= $statusLabel[$ci['status']] ?? $ci['status'] ?></span>
                            <?php endif; ?>
                            <?php if ($ci['platform']): ?>
                            <span class="badge bg-info-subtle text-info" style="font-size:.62rem"><?= $platformLbl[$ci['platform']] ?? $ci['platform'] ?></span>
                            <?php endif; ?>
                            <span class="small fw-semibold"><?= esc($ci['nama']) ?></span>
                        </div>

                        <?php if ($ci['tipe'] === 'digital' && ($ci['tanggal_take'] || $ci['pic'])): ?>
                        <div class="d-flex flex-wrap gap-2 text-muted mb-1" style="font-size:.72rem">
                            <?php if ($ci['tanggal_take']): ?>
                            <span>
                                <i class="bi bi-camera me-1"></i><?= date('d M Y', strtotime($ci['tanggal_take'])) ?>
                                <?php if ($ci['jam_take']): ?><?= substr($ci['jam_take'], 0, 5) ?><?php endif; ?>
                            </span>
                            <?php endif; ?>
                            <?php if ($ci['pic']): ?>
                            <span><i class="bi bi-person me-1"></i><?= esc($ci['pic']) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <?php if ($ci['tipe'] === 'influencer' && !empty($ciRData['entries'])): ?>
                        <div style="font-size:.72rem" class="text-muted mb-1">
                            <?php $names = array_filter(array_column($ciRData['entries'], 'nama_influencer')); ?>
                            <?php if ($names): ?><i class="bi bi-person me-1"></i><?= esc(implode(', ', array_unique($names))) ?><?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <?php if ($ci['tipe'] === 'digital' && $ciInsight): ?>
                        <?php
                        $insightMetrics = [
                            'views'       => ['total_views',       'bi-eye',          'info'],
                            'reach'       => ['total_reach',       'bi-broadcast',    'primary'],
                            'impressions' => ['total_impressions', 'bi-bar-chart',    'secondary'],
                            'likes'       => ['total_likes',       'bi-heart',        'danger'],
                            'shares'      => ['total_shares',      'bi-share',        'success'],
                            'followers_gained' => ['total_followers', 'bi-person-plus', 'success'],
                        ];
                        ?>
                        <div class="d-flex flex-wrap gap-1 mt-1">
                            <?php foreach ($insightMetrics as $field => [$key, $icon, $color]): ?>
                            <?php $val = $ciInsight[$key] ?? 0; if ($val <= 0) continue; ?>
                            <span class="badge bg-<?= $color ?>-subtle text-<?= $color ?>" style="font-size:.65rem">
                                <i class="<?= $icon ?> me-1"></i><?= number_format($val, 0, ',', '.') ?>
                            </span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="text-end flex-shrink-0" style="font-size:.78rem">
                        <?php if ($ciBudget > 0): ?>
                        <div class="text-muted">Rp <?= number_format($ciBudget,0,',','.') ?></div>
                        <?php endif; ?>
                        <?php if ($ciTotal > 0): ?>
                        <div class="fw-semibold text-<?= $ciTotal > $ciBudget && $ciBudget > 0 ? 'danger' : 'success' ?>">
                            Realisasi: Rp <?= number_format($ciTotal,0,',','.') ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if ($creativeBudget > 0 || $creativeRealisasiTotal > 0): ?>
            <div class="list-group-item py-2 px-3 bg-light">
                <?php if ($creativeBudget > 0): ?>
                <div class="d-flex justify-content-between">
                    <span class="small fw-bold">Total Budget</span>
                    <span class="small fw-bold text-muted">Rp <?= number_format($creativeBudget,0,',','.') ?></span>
                </div>
                <?php endif; ?>
                <?php if ($creativeRealisasiTotal > 0): ?>
                <div class="d-flex justify-content-between">
                    <span class="small fw-bold">Total Realisasi</span>
                    <span class="small fw-bold text-success">Rp <?= number_format($creativeRealisasiTotal,0,',','.') ?></span>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        </div>
    </div>
    <?php endif; ?>


</div><!-- /col right -->
</div><!-- /cards row -->

<!-- ── Kesimpulan & Evaluasi Post Event ── -->
<div class="card mb-4" id="evaluasi">
<div class="card-header d-flex align-items-center justify-content-between">
    <h6 class="mb-0 fw-semibold"><i class="bi bi-journal-text me-2"></i>Kesimpulan &amp; Evaluasi Post Event</h6>
    <?php if (! empty($event['eval_updated_at'])): ?>
    <small class="text-muted">Terakhir diperbarui <?= date('d M Y H:i', strtotime($event['eval_updated_at'])) ?></small>
    <?php endif; ?>
</div>
<div class="card-body">
    <?php $evalFields = [
        'eval_kesimpulan'  => ['Kesimpulan', 'Ringkasan hasil event secara keseluruhan…'],
        'eval_pencapaian'  => ['Pencapaian / Yang Berjalan Baik', 'Hal-hal yang tercapai & berjalan baik…'],
        'eval_kendala'     => ['Kendala / Hambatan', 'Kendala yang dihadapi selama event…'],
        'eval_rekomendasi' => ['Rekomendasi', 'Saran & rekomendasi untuk event berikutnya…'],
    ]; ?>
    <?php if (! empty($canEditSummary)): ?>
    <form method="POST" action="<?= base_url('events/' . $event['id'] . '/summary/evaluation') ?>">
        <?= csrf_field() ?>
        <div class="ev-eval-grid">
        <?php foreach ($evalFields as $key => [$label, $ph]): ?>
            <div>
                <label for="f_<?= $key ?>"><?= $label ?></label>
                <textarea id="f_<?= $key ?>" name="<?= $key ?>" class="form-control" rows="4" placeholder="<?= $ph ?>"><?= esc($event[$key] ?? '') ?></textarea>
            </div>
        <?php endforeach; ?>
        </div>
        <div class="text-end mt-3">
            <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-save me-1"></i>Simpan Evaluasi</button>
        </div>
    </form>
    <?php else: ?>
        <?php $adaEval = false; foreach (array_keys($evalFields) as $k) { if (! empty($event[$k])) $adaEval = true; } ?>
        <?php if (! $adaEval): ?>
        <p class="text-muted small mb-0">Belum ada evaluasi.</p>
        <?php else: ?>
        <div class="ev-eval-grid">
        <?php foreach ($evalFields as $key => [$label, $ph]): if (empty($event[$key])) continue; ?>
            <div>
                <div class="small fw-semibold text-muted mb-1"><?= $label ?></div>
                <div style="white-space:pre-line;font-size:.9rem"><?= esc($event[$key]) ?></div>
            </div>
        <?php endforeach; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
</div>

</div><!-- /page-evs -->
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="<?= base_url('js/events-summary.js') ?>?v=<?= @filemtime(FCPATH . 'js/events-summary.js') ?: 1 ?>"></script>
<script>
<?php if (array_sum($chartEwalk) + array_sum($chartPenta) > 0): ?>
new Chart(document.getElementById('trafficChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($chartDates) ?>,
        datasets: [
            { label: 'eWalk',     data: <?= json_encode($chartEwalk) ?>, backgroundColor: 'rgba(37,99,235,.7)' },
            { label: 'Pentacity', data: <?= json_encode($chartPenta) ?>, backgroundColor: 'rgba(5,150,105,.7)' },
        ]
    },
    options: { responsive: true, scales: { x: { stacked: false }, y: { beginAtZero: true } }, plugins: { legend: { position: 'top' } } }
});
<?php endif; ?>
<?php if (array_sum($chartMobil) + array_sum($chartMotor) > 0): ?>
new Chart(document.getElementById('vehicleChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($chartDates) ?>,
        datasets: [
            { label: 'Mobil', data: <?= json_encode($chartMobil) ?>, backgroundColor: 'rgba(245,158,11,.7)' },
            { label: 'Motor', data: <?= json_encode($chartMotor) ?>, backgroundColor: 'rgba(139,92,246,.7)' },
        ]
    },
    options: { responsive: true, scales: { x: { stacked: false }, y: { beginAtZero: true } }, plugins: { legend: { position: 'top' } } }
});
<?php endif; ?>

</script>
<?= $this->endSection() ?>
