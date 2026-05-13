<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<style>
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(18px); }
    to   { opacity: 1; transform: translateY(0); }
}
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
.anim-fade-up { opacity: 0; animation: fadeUp .48s cubic-bezier(.22,.68,0,1.15) forwards; }
.anim-fade-in { opacity: 0; animation: fadeIn .35s ease forwards; }
</style>
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php
$mallLabels   = ['ewalk' => 'eWalk Simply FUNtastic', 'pentacity' => 'Pentacity Shopping Venue', 'keduanya' => 'eWalk Simply FUNtastic & Pentacity Shopping Venue'];
$statusColors = ['draft' => 'secondary', 'active' => 'success', 'waiting_data' => 'warning', 'completed' => 'primary'];
$statusLabels = ['draft' => 'Draft', 'active' => 'Active', 'waiting_data' => 'Waiting Data', 'completed' => 'Completed'];
?>

<!-- Incomplete data alert -->
<?php if ($isAfterEvent && ! $allDone): ?>
<div class="alert alert-warning d-flex align-items-center gap-2 py-2 mb-3 alert-dismissible fade show anim-fade-in" style="animation-delay:.05s" role="alert">
    <i class="bi bi-exclamation-triangle-fill fs-5 flex-shrink-0"></i>
    <div>
        <strong>Data belum lengkap.</strong>
        Event ini sudah selesai tapi ada modul yang belum ditandai selesai:
        <?php
        $missing = array_diff(array_keys($requiredModules), array_keys($completions));
        echo implode(', ', array_map(fn($m) => '<span class="badge bg-warning text-dark">' . esc($requiredModules[$m]) . '</span>', $missing));
        ?>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Event Header -->
<div class="d-flex justify-content-between align-items-start mb-3 anim-fade-up" style="animation-delay:.08s">
    <div>
        <h4 class="fw-bold mb-1"><?= esc($event['name']) ?></h4>
        <a href="<?= base_url('events/'.$event['id'].'/summary/technical-meeting') ?>" target="_blank"
           class="btn btn-sm btn-outline-secondary mt-1">
            <i class="bi bi-printer me-1"></i>Print Technical Meeting
        </a>
        <a href="<?= base_url('events/'.$event['id'].'/summary/post-event') ?>" target="_blank"
           class="btn btn-sm btn-outline-primary mt-1">
            <i class="bi bi-file-earmark-text me-1"></i>Print Laporan Post Event
        </a>
        <a href="<?= base_url('events/'.$event['id'].'/gallery') ?>"
           class="btn btn-sm btn-outline-info mt-1">
            <i class="bi bi-images me-1"></i>Gallery Foto
        </a>
        <div class="text-muted small d-flex flex-wrap gap-3">
            <?php if ($event['tema']): ?><span><i class="bi bi-tag me-1"></i><?= esc($event['tema']) ?></span><?php endif; ?>
            <span><i class="bi bi-building me-1"></i><?= $mallLabels[$event['mall']] ?? esc($event['mall']) ?></span>
            <span><i class="bi bi-calendar-range me-1"></i><?= date('d M Y', strtotime($startDate)) ?> – <?= date('d M Y', strtotime($endDate)) ?></span>
            <span><i class="bi bi-clock me-1"></i><?= $event['event_days'] ?> hari</span>
            <?php if (!empty($eventLocations)): ?>
            <span><i class="bi bi-geo-alt me-1"></i><?= implode(', ', array_column($eventLocations, 'nama')) ?></span>
            <?php endif; ?>
        </div>
    </div>
    <span class="badge bg-<?= $statusColors[$event['status']] ?? 'secondary' ?> fs-6 ms-3">
        <?= $statusLabels[$event['status']] ?? ucfirst($event['status']) ?>
    </span>
</div>

<?php if (($canApprove ?? false) && ($event['approval_status'] ?? 'approved') !== 'approved'): ?>
<?php $isPendingEvt = ($event['approval_status'] ?? '') === 'pending' ?>
<div class="alert <?= $isPendingEvt ? 'alert-warning' : 'alert-danger' ?> d-flex align-items-center justify-content-between gap-3 py-2 mb-3 anim-fade-in" style="animation-delay:.10s">
    <div class="d-flex align-items-center gap-2">
        <i class="bi bi-<?= $isPendingEvt ? 'hourglass-split' : 'x-circle-fill' ?> fs-5"></i>
        <div>
            <?php if ($isPendingEvt): ?>
            <strong>Menunggu Persetujuan</strong> — Event ini belum disetujui dan tidak terlihat oleh pengguna lain.
            <?php else: ?>
            <strong>Ditolak</strong><?php if ($event['rejection_reason']): ?> — <?= esc($event['rejection_reason']) ?><?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    <div class="d-flex gap-2 flex-shrink-0">
        <form method="POST" action="<?= base_url('events/'.$event['id'].'/approve') ?>">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-success btn-sm"
                onclick="return confirm('Setujui event ini?')">
                <i class="bi bi-check-lg me-1"></i>Setujui
            </button>
        </form>
        <?php if ($isPendingEvt): ?>
        <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#rejectModal">
            <i class="bi bi-x-lg me-1"></i>Tolak
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

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-6 anim-fade-up" style="animation-delay:.14s">
        <div class="card h-100 border-danger-subtle">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="rounded-2 p-1 bg-danger-subtle"><i class="bi bi-wallet2 text-danger fs-5"></i></div>
                    <span class="small text-muted">Total Budget</span>
                </div>
                <div class="fw-bold fs-5 text-danger" data-count-rp="<?= $totalBudget ?>">Rp <?= number_format($totalBudget,0,',','.') ?></div>
                <?php $deptBudgetTotal = array_sum(array_column($budgetByDept, 'total')); ?>
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
                    <div class="d-flex justify-content-between text-muted"><span><i class="bi bi-vector-pen me-1"></i>Creative</span><span>Rp <?= number_format($creativeBudget,0,',','.') ?></span></div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6 anim-fade-up" style="animation-delay:.20s">
        <div class="card h-100 border-<?= $budgetRealColor ?>-subtle">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="rounded-2 p-1 bg-<?= $budgetRealColor ?>-subtle">
                        <i class="bi bi-receipt text-<?= $budgetRealColor ?> fs-5"></i>
                    </div>
                    <span class="small text-muted">Budget Realisasi</span>
                </div>
                <div class="fw-bold fs-5 text-<?= $budgetRealColor ?>" data-count-rp="<?= $totalBudgetReal ?>">Rp <?= number_format($totalBudgetReal,0,',','.') ?></div>
                <?php if ($totalBudget > 0): ?>
                <div class="mt-1">
                    <div class="progress" style="height:4px">
                        <div class="progress-bar bg-<?= $budgetRealColor ?>" style="width:<?= $budgetRealPct ?>%"></div>
                    </div>
                    <div class="small text-muted mt-1"><?= $budgetRealPct ?>% dari total budget</div>
                </div>
                <?php endif; ?>
                <?php if ($loyaltyBudgetReal > 0 || $contentRealisasi > 0 || $creativeRealisasiTotal > 0 || $vmRealTotal > 0): ?>
                <div class="mt-2 pt-2 border-top" style="font-size:.72rem">
                    <?php if ($loyaltyBudgetReal > 0): ?>
                    <div class="d-flex justify-content-between text-muted"><span><i class="bi bi-star me-1"></i>Loyalty</span><span>Rp <?= number_format($loyaltyBudgetReal,0,',','.') ?></span></div>
                    <?php endif; ?>
                    <?php if ($vmRealTotal > 0): ?>
                    <div class="d-flex justify-content-between text-muted"><span><i class="bi bi-palette me-1"></i>VM</span><span>Rp <?= number_format($vmRealTotal,0,',','.') ?></span></div>
                    <?php endif; ?>
                    <?php if ($contentRealisasi > 0): ?>
                    <div class="d-flex justify-content-between text-muted"><span><i class="bi bi-collection-play me-1"></i>Content</span><span>Rp <?= number_format($contentRealisasi,0,',','.') ?></span></div>
                    <?php endif; ?>
                    <?php if ($creativeRealisasiTotal > 0): ?>
                    <div class="d-flex justify-content-between text-muted"><span><i class="bi bi-vector-pen me-1"></i>Creative</span><span>Rp <?= number_format($creativeRealisasiTotal,0,',','.') ?></span></div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6 anim-fade-up" style="animation-delay:.26s">
        <div class="card h-100 border-success-subtle">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="rounded-2 p-1 bg-success-subtle"><i class="bi bi-graph-up-arrow text-success fs-5"></i></div>
                    <span class="small text-muted">Total Revenue</span>
                </div>
                <div class="fw-bold fs-5 text-success" data-count-rp="<?= $totalRevenue ?>">Rp <?= number_format($totalRevenue,0,',','.') ?></div>
                <?php if ($totalRevenue > 0): ?>
                <div class="mt-2 pt-2 border-top" style="font-size:.72rem">
                    <?php if ($totalDealing > 0): ?>
                    <div class="d-flex justify-content-between text-muted"><span><i class="bi bi-shop me-1"></i>Exhibition</span><span>Rp <?= number_format($totalDealing,0,',','.') ?></span></div>
                    <?php endif; ?>
                    <?php if ($totalSponsorCash > 0): ?>
                    <div class="d-flex justify-content-between text-muted"><span><i class="bi bi-award me-1"></i>Sponsor Cash</span><span>Rp <?= number_format($totalSponsorCash,0,',','.') ?></span></div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6 anim-fade-up" style="animation-delay:.32s">
        <div class="card h-100 border-<?= $profitPositive ? 'primary' : 'danger' ?>-subtle">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="rounded-2 p-1 bg-<?= $profitPositive ? 'primary' : 'danger' ?>-subtle">
                        <i class="bi bi-<?= $profitPositive ? 'trending-up' : 'trending-down' ?> text-<?= $profitPositive ? 'primary' : 'danger' ?> fs-5"></i>
                    </div>
                    <span class="small text-muted">Margin Profit</span>
                </div>
                <div class="fw-bold fs-5 text-<?= $profitPositive ? 'primary' : 'danger' ?>"
                     data-count-rp="<?= abs($profit) ?>" data-count-rp-prefix="<?= $profitPositive ? '' : '-' ?>">
                    <?= $profitPositive ? '' : '-' ?>Rp <?= number_format(abs($profit),0,',','.') ?>
                </div>
                <div class="small text-<?= $profitPositive ? 'primary' : 'danger' ?> mt-1">
                    <?= ($marginPct >= 0 ? '+' : '') ?><?= $marginPct ?>% dari revenue
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Status Penyelesaian (hanya setelah event) -->
<?php if ($isAfterEvent): ?>
<div class="card mb-4 border-<?= $allDone ? 'success' : 'warning' ?>-subtle anim-fade-up" style="animation-delay:.38s">
    <div class="card-header d-flex justify-content-between align-items-center bg-<?= $allDone ? 'success' : 'warning' ?>-subtle">
        <h6 class="mb-0 fw-semibold text-<?= $allDone ? 'success' : 'warning' ?>">
            <i class="bi bi-<?= $allDone ? 'check-circle-fill' : 'hourglass-split' ?> me-2"></i>Status Penyelesaian Data
        </h6>
        <span class="badge bg-<?= $allDone ? 'success' : 'warning text-dark' ?>"><?= count($completions) ?>/<?= count($requiredModules) ?> modul</span>
    </div>
    <div class="card-body p-0">
        <div class="list-group list-group-flush">
        <?php foreach ($requiredModules as $key => $label):
            $done = isset($completions[$key]); ?>
        <div class="list-group-item d-flex justify-content-between align-items-center py-2">
            <span class="small fw-medium">
                <i class="bi bi-<?= $done ? 'check-circle-fill text-success' : 'circle text-muted' ?> me-2"></i><?= $label ?>
            </span>
            <?php if ($done): ?>
            <span class="small text-muted"><?= esc($completions[$key]['completed_by_name']) ?> · <?= date('d M', strtotime($completions[$key]['completed_at'])) ?></span>
            <?php else: ?>
            <span class="badge bg-warning-subtle text-warning">Belum selesai</span>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Charts -->
<div class="row g-3 mb-3">
<div class="col-lg-6 anim-fade-up" style="animation-delay:.42s">
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
<div class="col-lg-6 anim-fade-up" style="animation-delay:.48s">
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

<!-- Cards -->
<div class="row g-3">
<div class="col-lg-6 anim-fade-up" style="animation-delay:.52s">

    <!-- Content -->
    <?php if (!empty($contentItems)): ?>
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-collection-play me-2"></i>Content Event</h6>
            <div class="d-flex align-items-center gap-2">
                <?php if ($contentBudget > 0): ?>
                <span class="small fw-semibold text-muted">Rp <?= number_format($contentBudget,0,',','.') ?></span>
                <?php endif; ?>
                <a href="<?= base_url('events/'.$event['id'].'/content') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
            </div>
        </div>
        <div class="card-body p-0">
        <div class="list-group list-group-flush">
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
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-shop me-2"></i>Exhibition by Casual Leasing</h6>
            <a href="<?= base_url('events/'.$event['id'].'/exhibitors') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
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
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-award me-2"></i>Sponsorship</h6>
            <a href="<?= base_url('events/'.$event['id'].'/sponsors') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
        </div>
        <div class="card-body p-0">
        <?php if (empty($sponsors)): ?>
        <p class="text-muted text-center py-3 small mb-0">Belum ada sponsor.</p>
        <?php else: ?>
        <div class="list-group list-group-flush">
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
<div class="col-lg-6 anim-fade-up" style="animation-delay:.58s">

    <!-- Loyalty Programs -->
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-star me-2"></i>Program Loyalty</h6>
            <a href="<?= base_url('events/'.$event['id'].'/loyalty') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
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
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-palette me-2"></i>Visual Merchandising</h6>
            <div class="d-flex align-items-center gap-2">
                <?php if ($vmBudget > 0): ?>
                <span class="small fw-semibold text-muted">Rp <?= number_format($vmBudget,0,',','.') ?></span>
                <?php endif; ?>
                <a href="<?= base_url('events/'.$event['id'].'/vm') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
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
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-vector-pen me-2"></i>Creative &amp; Design</h6>
            <div class="d-flex align-items-center gap-2">
                <?php if ($creativeBudget > 0): ?>
                <span class="small fw-semibold text-muted">Rp <?= number_format($creativeBudget,0,',','.') ?></span>
                <?php endif; ?>
                <a href="<?= base_url('events/'.$event['id'].'/creative') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
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

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
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

document.querySelectorAll('[data-count-rp]').forEach(el => {
    const target = parseInt(el.dataset.countRp) || 0;
    const prefix = el.dataset.countRpPrefix || '';
    if (!target) return;
    const dur = 900, start = performance.now();
    (function step(now) {
        const t = Math.min(1, (now - start) / dur);
        const val = Math.round((1 - Math.pow(1 - t, 3)) * target);
        el.textContent = prefix + 'Rp ' + val.toLocaleString('id-ID');
        if (t < 1) requestAnimationFrame(step);
    })(start);
});
</script>
<?= $this->endSection() ?>
