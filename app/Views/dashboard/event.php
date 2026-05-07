<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
function fmtRp(int $n): string { return 'Rp '.number_format($n,0,',','.'); }
function fmtPct(float $v): string { return number_format($v*100,1).'%'; }
function kpiStatus(float $actual, float $target): string {
    if ($target == 0) return 'track';
    return $actual >= $target ? 'on-target' : 'below-target';
}
function kpiLabel(float $actual, float $target): string {
    if ($target == 0) return 'Track';
    return $actual >= $target ? 'On Target' : 'Below Target';
}
?>

<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <h4 class="fw-bold mb-1"><?= esc($event['name']) ?></h4>
        <div class="text-muted small">
            <i class="bi bi-geo-alt me-1"></i><?= esc($event['mall']) ?>
            <?php if ($event['start_date']): ?>
            <span class="mx-2">·</span>
            <i class="bi bi-calendar3 me-1"></i><?= date('d M Y', strtotime($event['start_date'])) ?>
            <span class="mx-2">·</span><?= $event['event_days'] ?> hari
            <?php endif; ?>
            <span class="mx-2">·</span>
            <?php $sc = ['draft'=>'warning','active'=>'success','completed'=>'secondary'][$event['status']] ?? 'secondary' ?>
            <span class="badge bg-<?= $sc ?>-subtle text-<?= $sc ?>"><?= ucfirst($event['status']) ?></span>
        </div>
    </div>
    <a href="<?= base_url('events/'.$event['id'].'/edit') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-pencil me-1"></i>Edit
    </a>
</div>

<?php if ($dayCount === 0): ?>
<div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>
    Belum ada data tracking. Mulai dengan mengisi
    <a href="<?= base_url('events/'.$event['id'].'/inputs') ?>"><strong>Inputs & Biaya</strong></a>,
    <a href="<?= base_url('events/'.$event['id'].'/baseline') ?>"><strong>Baseline</strong></a>,
    lalu <a href="<?= base_url('events/'.$event['id'].'/tracking/add') ?>"><strong>Daily Tracking</strong></a>.
</div>
<?php endif; ?>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
<?php foreach ($kpis as $kpi):
    $status = kpiStatus($kpi['actual'], $kpi['target']);
    $displayActual = $kpi['format'] === 'pct' ? fmtPct($kpi['actual']) : number_format($kpi['actual'],2).'x';
    $displayTarget = $kpi['format'] === 'pct' ? fmtPct($kpi['target']) : number_format($kpi['target'],2).'x';
?>
<div class="col-6 col-md-3">
    <div class="kpi-card <?= $status ?>">
        <div class="kpi-label"><?= $kpi['label'] ?></div>
        <div class="kpi-value"><?= $displayActual ?></div>
        <div class="kpi-target">Target: <?= $displayTarget ?></div>
        <div class="mt-1"><span class="badge bg-white bg-opacity-25 text-white small"><?= kpiLabel($kpi['actual'], $kpi['target']) ?></span></div>
    </div>
</div>
<?php endforeach; ?>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small mb-1"><i class="bi bi-cash-stack me-1"></i>Total Cost</div>
                <div class="fs-4 fw-bold text-danger"><?= fmtRp($totalCost) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small mb-1"><i class="bi bi-wallet2 me-1"></i>Total Direct Revenue</div>
                <div class="fs-4 fw-bold text-success"><?= fmtRp($totalRevenue) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small mb-1"><i class="bi bi-shop me-1"></i>Tenant Sales Uplift</div>
                <div class="fs-4 fw-bold <?= $tenantUplift >= 0 ? 'text-success' : 'text-danger' ?>"><?= fmtRp($tenantUplift) ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Charts -->
<?php if ($dayCount > 0): ?>
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h6 class="mb-0 fw-semibold">Traffic Pengunjung Harian</h6></div>
            <div class="card-body"><canvas id="trafficChart" height="180"></canvas></div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h6 class="mb-0 fw-semibold">Revenue Harian</h6></div>
            <div class="card-body"><canvas id="revenueChart" height="180"></canvas></div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Quick Links -->
<div class="row g-3">
    <?php
    $links = [
        ['Inputs & Biaya', 'sliders', 'inputs', 'primary'],
        ['Baseline', 'clipboard-data', 'baseline', 'info'],
        ['Daily Tracking', 'journal-check', 'tracking', 'success'],
        ['Tenant Impact', 'bar-chart-line', 'tenants/impact', 'warning'],
        ['Funnel', 'funnel', 'funnel', 'secondary'],
        ['ROI Summary', 'currency-dollar', 'roi', 'danger'],
    ];
    foreach ($links as [$label, $icon, $path, $color]):
    ?>
    <div class="col-6 col-md-2">
        <a href="<?= base_url('events/'.$event['id'].'/'.$path) ?>" class="card text-decoration-none h-100">
            <div class="card-body text-center p-3">
                <i class="bi bi-<?= $icon ?> text-<?= $color ?> fs-4 d-block mb-1"></i>
                <div class="small fw-medium"><?= $label ?></div>
            </div>
        </a>
    </div>
    <?php endforeach; ?>
</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<?php if ($dayCount > 0): ?>
<script>
const labels  = <?= json_encode($chartLabels) ?>;
const traffic = <?= json_encode($chartTraffic) ?>;
const revenue = <?= json_encode($chartRevenue) ?>;

new Chart(document.getElementById('trafficChart'), {
    type: 'bar',
    data: { labels, datasets: [{ label: 'Actual Traffic', data: traffic, backgroundColor: '#3b82f6' }] },
    options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
});
new Chart(document.getElementById('revenueChart'), {
    type: 'bar',
    data: { labels, datasets: [{ label: 'Revenue', data: revenue, backgroundColor: '#10b981' }] },
    options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
});
</script>
<?php endif; ?>
<?= $this->endSection() ?>
