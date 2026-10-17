<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/events-summary.css') ?>?v=<?= @filemtime(FCPATH . 'css/events-summary.css') ?: 1 ?>">
<?= $this->endSection() ?>
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
$totalMobil  = (int)($monthVehicle['mobil'] ?? 0);
$totalMotor  = (int)($monthVehicle['motor'] ?? 0);
$rp = static fn($n) => 'Rp ' . number_format((int) $n, 0, ',', '.');
$stChip = ['draft' => 'mute', 'active' => 'ok', 'waiting_data' => 'warn', 'completed' => 'blue'];
$mlChip = ['ewalk' => 'blue', 'pentacity' => 'ok', 'keduanya' => 'warn'];
?>

<div class="page-evs">

<!-- Header & Navigator -->
<div class="ev-head">
    <div class="ev-head-ico"><i class="bi bi-calendar-month"></i></div>
    <div class="min-w-0">
        <h1 class="ev-title">Summary Bulanan</h1>
        <div class="ev-subtitle"><?= $bulanLabel ?> · <?= count($rows) ?> event</div>
    </div>
    <div class="ev-head-act">
        <a href="?bulan=<?= $prevBulan ?>" class="ev-iconbtn" title="Bulan sebelumnya" aria-label="Bulan sebelumnya"><i class="bi bi-chevron-left"></i></a>
        <form method="GET" class="m-0">
            <input type="month" name="bulan" value="<?= $bulan ?>" class="form-control form-control-sm" style="width:155px" aria-label="Pilih bulan" onchange="this.form.submit()">
        </form>
        <a href="?bulan=<?= $nextBulan ?>" class="ev-iconbtn <?= $nextBulan > date('Y-m') ? 'disabled' : '' ?>" <?= $nextBulan > date('Y-m') ? 'style="opacity:.4;pointer-events:none" aria-disabled="true"' : '' ?> title="Bulan berikutnya" aria-label="Bulan berikutnya"><i class="bi bi-chevron-right"></i></a>
    </div>
</div>

<!-- Strip KPI -->
<?php $profitPositive = $totalProfit >= 0; ?>
<div class="ev-kpis">
    <div class="ev-kpi">
        <div class="ev-kpi-l">Total Event</div>
        <div class="ev-kpi-v"><?= count($rows) ?></div>
        <div class="ev-kpi-s">event bulan ini</div>
    </div>
    <div class="ev-kpi">
        <div class="ev-kpi-l">Total Budget</div>
        <div class="ev-kpi-v ev-bad"><?= $rp($totalBudget) ?></div>
        <div class="ev-kpi-s">rencana biaya</div>
    </div>
    <div class="ev-kpi">
        <div class="ev-kpi-l">Total Revenue</div>
        <div class="ev-kpi-v ev-ok"><?= $rp($totalRevenue) ?></div>
        <div class="ev-kpi-s">pendapatan event</div>
    </div>
    <div class="ev-kpi">
        <div class="ev-kpi-l">Profit / Loss</div>
        <div class="ev-kpi-v <?= $profitPositive ? 'ev-cyan' : 'ev-bad' ?>"><?= $profitPositive ? '' : '-' ?><?= $rp(abs($totalProfit)) ?></div>
        <div class="ev-kpi-s"><?= $totalRevenue > 0 ? ($totalProfit >= 0 ? '+' : '') . round($totalProfit / $totalRevenue * 100, 1) . '% dari revenue' : 'belum ada revenue' ?></div>
    </div>
</div>

<!-- Traffic Mall Bulanan -->
<?php if ($monthTrafficEwalk + $monthTrafficPenta + $totalMobil + $totalMotor > 0): ?>
<div class="ev-grid2" style="margin-bottom:1rem">
    <?php if ($monthTrafficEwalk + $monthTrafficPenta > 0): ?>
    <div class="ev-kpi">
        <div class="ev-kpi-l"><i class="bi bi-person-walking me-1"></i>Traffic Pengunjung — <?= $bulanLabel ?></div>
        <div class="ev-kpi-v ev-cyan"><?= number_format($monthTrafficEwalk + $monthTrafficPenta) ?></div>
        <div class="ev-kpi-rows">
            <?php if ($monthTrafficEwalk > 0): ?><div><span>eWalk</span><span><?= number_format($monthTrafficEwalk) ?></span></div><?php endif; ?>
            <?php if ($monthTrafficPenta > 0): ?><div><span>Pentacity</span><span><?= number_format($monthTrafficPenta) ?></span></div><?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    <?php if ($totalMobil + $totalMotor > 0): ?>
    <div class="ev-kpi">
        <div class="ev-kpi-l"><i class="bi bi-car-front me-1"></i>Kendaraan — <?= $bulanLabel ?></div>
        <div class="ev-kpi-v ev-warn"><?= number_format($totalMobil + $totalMotor) ?></div>
        <div class="ev-kpi-rows">
            <?php if ($totalMobil > 0): ?><div><span>Mobil</span><span><?= number_format($totalMobil) ?></span></div><?php endif; ?>
            <?php if ($totalMotor > 0): ?><div><span>Motor</span><span><?= number_format($totalMotor) ?></span></div><?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Event List -->
<?php if (empty($rows)): ?>
<div class="ev-empty">
    <i class="bi bi-calendar-x"></i>
    <h5>Tidak ada event di bulan <?= $bulanLabel ?></h5>
    <p>Pilih bulan lain dengan panah di atas, atau lihat semua event.</p>
    <a href="<?= base_url('events') ?>" class="ev-btn"><i class="bi bi-calendar-event"></i>Daftar Event</a>
</div>
<?php else: ?>

<?php
$chartLabels  = array_map(fn($r) => esc($r['event']['name']), $rows);
$chartBudget  = array_map(fn($r) => $r['budget'], $rows);
$chartRevenue = array_map(fn($r) => $r['revenue'], $rows);
?>
<div class="ev-panel">
    <div class="ev-panel-h"><h2 class="ev-panel-t"><i class="bi bi-bar-chart"></i>Budget vs Revenue per Event</h2></div>
    <div class="ev-panel-b ev-chart">
        <canvas id="budgetRevenueChart" height="<?= max(60, count($rows) * 40) ?>"></canvas>
    </div>
</div>

<!-- Pencarian -->
<div class="ev-tools">
    <div class="ev-search">
        <i class="bi bi-search"></i>
        <input type="search" id="evQ" placeholder="Cari nama atau tema event…" autocomplete="off" aria-label="Cari event">
    </div>
    <span class="ev-count" id="evCount"></span>
</div>

<!-- Table -->
<div class="ev-panel">
    <div class="ev-panel-h">
        <h2 class="ev-panel-t"><i class="bi bi-table"></i>Detail Event</h2>
        <div class="ev-panel-end"><span class="ev-chip ev-chip-blue"><?= count($rows) ?> event</span></div>
    </div>
    <div class="ev-tablewrap">
        <table class="ev-table">
            <thead>
                <tr>
                    <th>Event</th><th>Mall</th><th>Tanggal</th>
                    <th class="r">Budget</th><th class="r">Revenue</th><th class="r">Profit / Loss</th><th class="r">Traffic</th><th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r):
                $ev       = $r['event'];
                $profit   = $r['profit'];
                $pPct     = $r['revenue'] > 0 ? round($profit / $r['revenue'] * 100, 1) : null;
                $roiPct   = $r['budget']  > 0 ? min(200, round($r['revenue'] / $r['budget'] * 100)) : null;
                $roiBar   = $roiPct === null ? '' : ($roiPct >= 100 ? 'ev-bar-ok' : ($roiPct >= 60 ? 'ev-bar-mid' : 'ev-bar-low'));
                $mallKey  = $ev['mall'] ?? '';
            ?>
            <tr class="is-click" data-href="<?= base_url('events/'.$ev['id'].'/summary') ?>" data-search="<?= esc(strtolower($ev['name'] . ' ' . ($ev['tema'] ?? '')), 'attr') ?>">
                <td class="ev-wrap" style="min-width:200px">
                    <a href="<?= base_url('events/'.$ev['id'].'/summary') ?>" class="fw-semibold" style="color:inherit"><?= esc($ev['name']) ?></a>
                    <?php if ($ev['tema']): ?><span class="ev-sub"><?= esc($ev['tema']) ?></span><?php endif; ?>
                    <span class="ev-chip ev-chip-<?= $stChip[$ev['status']] ?? 'mute' ?>" style="margin-top:.2rem"><?= $statusLabels[$ev['status']] ?? $ev['status'] ?></span>
                </td>
                <td><span class="ev-chip ev-chip-<?= $mlChip[$mallKey] ?? 'mute' ?>"><?= $mallLabels[$mallKey] ?? esc($mallKey) ?></span></td>
                <td class="nw">
                    <?= date('d M', strtotime($ev['start_date'])) ?> – <?= date('d M Y', strtotime($r['endDate'])) ?>
                    <span class="ev-sub"><?= $ev['event_days'] ?> hari</span>
                </td>
                <td class="r nw">
                    <?php if ($r['budget'] > 0): ?>
                    <b class="ev-bad"><?= $rp($r['budget']) ?></b>
                    <?php if ($roiPct !== null): ?>
                    <div class="ev-cell-bar"><div class="ev-bar <?= $roiBar ?>"><i style="width:<?= min(100,$roiPct) ?>%"></i></div></div>
                    <span class="ev-sub">ROI <?= $roiPct ?>%</span>
                    <?php endif; ?>
                    <?php else: ?><span class="ev-mute">—</span><?php endif; ?>
                </td>
                <td class="r nw"><?= $r['revenue'] > 0 ? '<b class="ev-ok">' . $rp($r['revenue']) . '</b>' : '<span class="ev-mute">—</span>' ?></td>
                <td class="r nw">
                    <?php if ($r['budget'] > 0 || $r['revenue'] > 0): ?>
                    <b class="<?= $profit >= 0 ? 'ev-cyan' : 'ev-bad' ?>"><?= $profit >= 0 ? '' : '-' ?><?= $rp(abs($profit)) ?></b>
                    <?php if ($pPct !== null): ?><span class="ev-sub <?= $profit >= 0 ? '' : 'ev-bad' ?>"><?= ($pPct >= 0 ? '+' : '') ?><?= $pPct ?>%</span><?php endif; ?>
                    <?php else: ?><span class="ev-mute">—</span><?php endif; ?>
                </td>
                <td class="r nw">
                    <?php if ($r['traffic'] > 0): ?>
                    <b class="ev-cyan"><?= number_format($r['traffic']) ?></b>
                    <span class="ev-sub"><?= $mallKey === 'ewalk' ? 'eWalk' : ($mallKey === 'pentacity' ? 'Pentacity' : 'eWalk &amp; Pentacity') ?></span>
                    <?php else: ?><span class="ev-mute">—</span><?php endif; ?>
                    <?php if ($r['kendaraan'] > 0): ?>
                    <span class="ev-sub"><i class="bi bi-car-front me-1"></i><?= number_format($r['kendaraan']) ?><?php if ($r['mobil'] > 0 && $r['motor'] > 0): ?> (<?= number_format($r['mobil']) ?> · <?= number_format($r['motor']) ?>)<?php endif; ?></span>
                    <?php endif; ?>
                </td>
                <td class="r"><a href="<?= base_url('events/'.$ev['id'].'/summary') ?>" class="ev-btn ev-btn-sm">Buka<i class="bi bi-arrow-right"></i></a></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3">Total</td>
                    <td class="r nw ev-bad"><?= $rp($totalBudget) ?></td>
                    <td class="r nw ev-ok"><?= $rp($totalRevenue) ?></td>
                    <td class="r nw <?= $totalProfit >= 0 ? 'ev-cyan' : 'ev-bad' ?>"><?= $totalProfit >= 0 ? '' : '-' ?><?= $rp(abs($totalProfit)) ?></td>
                    <td class="r nw ev-cyan"><?= number_format($totalTraffic) ?></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <div class="ev-empty-sm" id="evNoMatch" hidden>Tidak ada event yang cocok dengan pencarian.</div>
</div>
<?php endif; ?>

</div><!-- /page-evs -->

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="<?= base_url('js/events-summary.js') ?>?v=<?= @filemtime(FCPATH . 'js/events-summary.js') ?: 1 ?>"></script>
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
