<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$mallLabels   = ['ewalk' => 'eWalk', 'pentacity' => 'Pentacity', 'keduanya' => 'eWalk & Pentacity'];
$mallColors   = ['ewalk' => 'primary', 'pentacity' => 'success', 'keduanya' => 'info'];
$statusColors = ['draft' => 'secondary', 'active' => 'success', 'waiting_data' => 'warning', 'completed' => 'primary'];
$statusLabels = ['draft' => 'Draft', 'active' => 'Active', 'waiting_data' => 'Waiting Data', 'completed' => 'Completed'];

$idBulan    = ['January'=>'Januari','February'=>'Februari','March'=>'Maret','April'=>'April',
               'May'=>'Mei','June'=>'Juni','July'=>'Juli','August'=>'Agustus',
               'September'=>'September','October'=>'Oktober','November'=>'November','December'=>'Desember'];
$bulanDt    = \DateTime::createFromFormat('Y-m', $bulan);
$bulanLabel = strtr($bulanDt->format('F Y'), $idBulan);

$totalProfit = $totalRevenue - $totalBudget;
$totalMobil  = (int)($monthVehicle['total_mobil'] ?? 0);
$totalMotor  = (int)($monthVehicle['total_motor'] ?? 0);
?>

<!-- Header & Navigator -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-calendar-month me-2 text-primary"></i>Summary Bulanan</h4>
        <div class="text-muted small mt-1"><?= $bulanLabel ?> · <?= count($rows) ?> event</div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="?bulan=<?= $prevBulan ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-chevron-left"></i></a>
        <form method="GET">
            <input type="month" name="bulan" value="<?= $bulan ?>" class="form-control form-control-sm" style="width:150px" onchange="this.form.submit()">
        </form>
        <a href="?bulan=<?= $nextBulan ?>" class="btn btn-sm btn-outline-secondary <?= $nextBulan > date('Y-m') ? 'disabled' : '' ?>"><i class="bi bi-chevron-right"></i></a>
    </div>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card h-100 border-primary-subtle">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <div class="rounded-2 p-1 bg-primary-subtle"><i class="bi bi-calendar-check text-primary fs-5"></i></div>
                    <span class="small text-muted">Total Event</span>
                </div>
                <div class="fw-bold fs-4 text-primary"><?= count($rows) ?></div>
                <div class="small text-muted">event bulan ini</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card h-100 border-danger-subtle">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <div class="rounded-2 p-1 bg-danger-subtle"><i class="bi bi-wallet2 text-danger fs-5"></i></div>
                    <span class="small text-muted">Total Budget</span>
                </div>
                <div class="fw-bold fs-5 text-danger">Rp <?= number_format($totalBudget, 0, ',', '.') ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card h-100 border-success-subtle">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <div class="rounded-2 p-1 bg-success-subtle"><i class="bi bi-graph-up-arrow text-success fs-5"></i></div>
                    <span class="small text-muted">Total Revenue</span>
                </div>
                <div class="fw-bold fs-5 text-success">Rp <?= number_format($totalRevenue, 0, ',', '.') ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <?php $profitPositive = $totalProfit >= 0; ?>
        <div class="card h-100 border-<?= $profitPositive ? 'primary' : 'danger' ?>-subtle">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <div class="rounded-2 p-1 bg-<?= $profitPositive ? 'primary' : 'danger' ?>-subtle">
                        <i class="bi bi-<?= $profitPositive ? 'trending-up' : 'trending-down' ?> text-<?= $profitPositive ? 'primary' : 'danger' ?> fs-5"></i>
                    </div>
                    <span class="small text-muted">Profit / Loss</span>
                </div>
                <div class="fw-bold fs-5 text-<?= $profitPositive ? 'primary' : 'danger' ?>">
                    <?= $profitPositive ? '' : '-' ?>Rp <?= number_format(abs($totalProfit), 0, ',', '.') ?>
                </div>
                <?php if ($totalRevenue > 0): ?>
                <div class="small text-<?= $profitPositive ? 'primary' : 'danger' ?>">
                    <?= ($totalProfit >= 0 ? '+' : '') ?><?= round($totalProfit / $totalRevenue * 100, 1) ?>% dari revenue
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Traffic Mall Bulanan -->
<?php if ($monthTrafficEwalk + $monthTrafficPenta + $totalMobil + $totalMotor > 0): ?>
<div class="row g-3 mb-4">
    <?php if ($monthTrafficEwalk + $monthTrafficPenta > 0): ?>
    <div class="col-md-6">
        <div class="card h-100 border-info-subtle">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="rounded-2 p-1 bg-info-subtle"><i class="bi bi-person-walking text-info fs-5"></i></div>
                    <span class="small text-muted fw-semibold">Traffic Pengunjung — <?= $bulanLabel ?></span>
                </div>
                <div class="fw-bold fs-5 text-info"><?= number_format($monthTrafficEwalk + $monthTrafficPenta) ?></div>
                <div class="d-flex gap-3 mt-1" style="font-size:.78rem">
                    <?php if ($monthTrafficEwalk > 0): ?>
                    <span class="text-muted"><i class="bi bi-circle-fill text-primary me-1" style="font-size:.5rem"></i>eWalk <?= number_format($monthTrafficEwalk) ?></span>
                    <?php endif; ?>
                    <?php if ($monthTrafficPenta > 0): ?>
                    <span class="text-muted"><i class="bi bi-circle-fill text-success me-1" style="font-size:.5rem"></i>Pentacity <?= number_format($monthTrafficPenta) ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <?php if ($totalMobil + $totalMotor > 0): ?>
    <div class="col-md-6">
        <div class="card h-100 border-warning-subtle">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="rounded-2 p-1 bg-warning-subtle"><i class="bi bi-car-front text-warning fs-5"></i></div>
                    <span class="small text-muted fw-semibold">Kendaraan — <?= $bulanLabel ?></span>
                </div>
                <div class="fw-bold fs-5 text-warning"><?= number_format($totalMobil + $totalMotor) ?></div>
                <div class="d-flex gap-3 mt-1" style="font-size:.78rem">
                    <?php if ($totalMobil > 0): ?>
                    <span class="text-muted"><i class="bi bi-circle-fill text-warning me-1" style="font-size:.5rem"></i>Mobil <?= number_format($totalMobil) ?></span>
                    <?php endif; ?>
                    <?php if ($totalMotor > 0): ?>
                    <span class="text-muted"><i class="bi bi-circle-fill text-primary me-1" style="font-size:.5rem"></i>Motor <?= number_format($totalMotor) ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Event List -->
<?php if (empty($rows)): ?>
<div class="card">
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-calendar-x display-4 d-block mb-2 opacity-25"></i>
        <p class="mb-0">Tidak ada event di bulan <strong><?= $bulanLabel ?></strong>.</p>
    </div>
</div>
<?php else: ?>

<!-- Chart -->
<?php
$chartLabels  = array_map(fn($r) => esc($r['event']['name']), $rows);
$chartBudget  = array_map(fn($r) => $r['budget'], $rows);
$chartRevenue = array_map(fn($r) => $r['revenue'], $rows);
?>
<div class="card mb-4">
    <div class="card-header"><span class="fw-semibold small"><i class="bi bi-bar-chart me-2"></i>Budget vs Revenue per Event</span></div>
    <div class="card-body">
        <canvas id="budgetRevenueChart" height="<?= max(60, count($rows) * 40) ?>"></canvas>
    </div>
</div>

<!-- Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span class="fw-semibold small"><i class="bi bi-table me-2"></i>Detail Event</span>
        <span class="badge bg-primary-subtle text-primary"><?= count($rows) ?> event</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-sm mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">Event</th>
                    <th>Mall</th>
                    <th>Tanggal</th>
                    <th class="text-end">Budget</th>
                    <th class="text-end">Revenue</th>
                    <th class="text-end">Profit / Loss</th>
                    <th class="text-end pe-3">Traffic</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r):
                $ev       = $r['event'];
                $profit   = $r['profit'];
                $pPct     = $r['revenue'] > 0 ? round($profit / $r['revenue'] * 100, 1) : null;
                $roiPct   = $r['budget']  > 0 ? min(200, round($r['revenue'] / $r['budget'] * 100)) : null;
                $roiColor = $roiPct === null ? 'secondary' : ($roiPct >= 100 ? 'success' : ($roiPct >= 60 ? 'warning' : 'danger'));
                $mallKey  = $ev['mall'] ?? '';
            ?>
            <tr>
                <td class="ps-3">
                    <a href="<?= base_url('events/'.$ev['id'].'/summary') ?>" class="fw-semibold text-decoration-none">
                        <?= esc($ev['name']) ?>
                    </a>
                    <?php if ($ev['tema']): ?>
                    <div class="small text-muted"><?= esc($ev['tema']) ?></div>
                    <?php endif; ?>
                    <span class="badge bg-<?= $statusColors[$ev['status']] ?? 'secondary' ?>-subtle text-<?= $statusColors[$ev['status']] ?? 'secondary' ?>" style="font-size:.62rem">
                        <?= $statusLabels[$ev['status']] ?? $ev['status'] ?>
                    </span>
                </td>
                <td>
                    <span class="badge bg-<?= $mallColors[$mallKey] ?? 'secondary' ?>-subtle text-<?= $mallColors[$mallKey] ?? 'secondary' ?>">
                        <?= $mallLabels[$mallKey] ?? esc($mallKey) ?>
                    </span>
                </td>
                <td style="font-size:.8rem">
                    <div><?= date('d M', strtotime($ev['start_date'])) ?> – <?= date('d M Y', strtotime($r['endDate'])) ?></div>
                    <div class="text-muted"><?= $ev['event_days'] ?> hari</div>
                </td>
                <td class="text-end">
                    <?php if ($r['budget'] > 0): ?>
                    <div class="small fw-semibold text-danger">Rp <?= number_format($r['budget'], 0, ',', '.') ?></div>
                    <?php if ($roiPct !== null): ?>
                    <div class="progress mt-1" style="height:3px;width:80px;margin-left:auto">
                        <div class="progress-bar bg-<?= $roiColor ?>" style="width:<?= min(100,$roiPct) ?>%"></div>
                    </div>
                    <div class="text-muted" style="font-size:.63rem">ROI <?= $roiPct ?>%</div>
                    <?php endif; ?>
                    <?php else: ?>
                    <span class="small text-muted">—</span>
                    <?php endif; ?>
                </td>
                <td class="text-end">
                    <?php if ($r['revenue'] > 0): ?>
                    <span class="small fw-semibold text-success">Rp <?= number_format($r['revenue'], 0, ',', '.') ?></span>
                    <?php else: ?>
                    <span class="small text-muted">—</span>
                    <?php endif; ?>
                </td>
                <td class="text-end">
                    <?php if ($r['budget'] > 0 || $r['revenue'] > 0): ?>
                    <div class="small fw-semibold text-<?= $profit >= 0 ? 'primary' : 'danger' ?>">
                        <?= $profit >= 0 ? '' : '-' ?>Rp <?= number_format(abs($profit), 0, ',', '.') ?>
                    </div>
                    <?php if ($pPct !== null): ?>
                    <div class="small text-<?= $profit >= 0 ? 'primary' : 'danger' ?>"><?= ($pPct >= 0 ? '+' : '') ?><?= $pPct ?>%</div>
                    <?php endif; ?>
                    <?php else: ?>
                    <span class="small text-muted">—</span>
                    <?php endif; ?>
                </td>
                <td class="text-end pe-3">
                    <?php if ($r['traffic'] > 0): ?>
                    <div class="small fw-semibold text-info"><?= number_format($r['traffic']) ?></div>
                    <div class="text-muted" style="font-size:.68rem">
                        <?php if ($mallKey === 'ewalk'): ?>
                        <i class="bi bi-circle-fill text-primary me-1" style="font-size:.45rem"></i>eWalk
                        <?php elseif ($mallKey === 'pentacity'): ?>
                        <i class="bi bi-circle-fill text-success me-1" style="font-size:.45rem"></i>Pentacity
                        <?php else: ?>
                        eWalk &amp; Pentacity
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <span class="small text-muted">—</span>
                    <?php endif; ?>
                    <?php if ($r['kendaraan'] > 0): ?>
                    <div class="text-muted mt-1" style="font-size:.68rem">
                        <i class="bi bi-car-front me-1"></i><?= number_format($r['kendaraan']) ?>
                        <?php if ($r['mobil'] > 0 && $r['motor'] > 0): ?>
                        <span class="text-muted">(<?= number_format($r['mobil']) ?> · <?= number_format($r['motor']) ?>)</span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot class="table-light fw-bold">
                <tr>
                    <td class="ps-3 small" colspan="3">Total</td>
                    <td class="text-end small text-danger">Rp <?= number_format($totalBudget, 0, ',', '.') ?></td>
                    <td class="text-end small text-success">Rp <?= number_format($totalRevenue, 0, ',', '.') ?></td>
                    <td class="text-end small text-<?= $totalProfit >= 0 ? 'primary' : 'danger' ?>">
                        <?= $totalProfit >= 0 ? '' : '-' ?>Rp <?= number_format(abs($totalProfit), 0, ',', '.') ?>
                    </td>
                    <td class="text-end pe-3 small text-info"><?= number_format($totalTraffic) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<?php endif; ?>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<?php if (!empty($rows)): ?>
<script>
new Chart(document.getElementById('budgetRevenueChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($chartLabels) ?>,
        datasets: [
            {
                label: 'Budget',
                data: <?= json_encode($chartBudget) ?>,
                backgroundColor: 'rgba(220,53,69,.7)',
                borderRadius: 3,
            },
            {
                label: 'Revenue',
                data: <?= json_encode($chartRevenue) ?>,
                backgroundColor: 'rgba(25,135,84,.7)',
                borderRadius: 3,
            },
        ]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        scales: { x: { beginAtZero: true, ticks: { callback: v => 'Rp ' + v.toLocaleString('id-ID') } } },
        plugins: {
            legend: { position: 'top', labels: { boxWidth: 12, font: { size: 11 } } },
            tooltip: { callbacks: { label: ctx => ctx.dataset.label + ': Rp ' + ctx.raw.toLocaleString('id-ID') } }
        }
    }
});
</script>
<?php endif; ?>
<?= $this->endSection() ?>
