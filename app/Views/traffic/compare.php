<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<style>
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
}
.anim-fade-up { opacity: 0; animation: fadeUp .5s cubic-bezier(.22,.68,0,1.15) forwards; }
</style>
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-arrow-left-right me-2"></i>Perbandingan Periode Traffic</h4>
        <small class="text-muted">Bandingkan dua periode secara berdampingan</small>
    </div>
    <a href="<?= base_url('traffic/summary') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-bar-chart-line me-1"></i>Summary
    </a>
</div>

<!-- Filter -->
<div class="card mb-4">
<div class="card-body py-3">
<form method="GET" class="row g-3 align-items-end">
    <div class="col-12 col-md-auto">
        <div class="d-flex align-items-center gap-2 mb-2">
            <span class="badge rounded-pill px-3 py-2" style="background:var(--c-p1);font-size:.8rem">Periode 1</span>
        </div>
        <div class="d-flex gap-2">
            <div>
                <label class="form-label small fw-semibold mb-1">Dari</label>
                <input type="date" name="from1" class="form-control form-control-sm" value="<?= $from1 ?>">
            </div>
            <div>
                <label class="form-label small fw-semibold mb-1">Sampai</label>
                <input type="date" name="to1" class="form-control form-control-sm" value="<?= $to1 ?>">
            </div>
        </div>
    </div>
    <div class="col-auto d-none d-md-flex align-items-end pb-1">
        <i class="bi bi-arrow-left-right text-muted fs-5"></i>
    </div>
    <div class="col-12 col-md-auto">
        <div class="d-flex align-items-center gap-2 mb-2">
            <span class="badge rounded-pill px-3 py-2" style="background:var(--c-p2);font-size:.8rem">Periode 2</span>
        </div>
        <div class="d-flex gap-2">
            <div>
                <label class="form-label small fw-semibold mb-1">Dari</label>
                <input type="date" name="from2" class="form-control form-control-sm" value="<?= $from2 ?>">
            </div>
            <div>
                <label class="form-label small fw-semibold mb-1">Sampai</label>
                <input type="date" name="to2" class="form-control form-control-sm" value="<?= $to2 ?>">
            </div>
        </div>
    </div>
    <div class="col-auto">
        <label class="form-label small mb-1 d-block">&nbsp;</label>
        <button type="submit" class="btn btn-sm btn-primary">Bandingkan</button>
    </div>
</form>
</div>
</div>

<?php
function pctDiff(int $a, int $b): ?float {
    if ($a === 0) return null;
    return round(($b - $a) / $a * 100, 1);
}
function diffBadge(int $p1, int $p2): string {
    $pct = pctDiff($p1, $p2);
    if ($pct === null) return '<span class="badge bg-secondary">—</span>';
    if ($pct > 0)  return '<span class="badge" style="background:var(--c-badge-up-bg);color:var(--c-badge-up-fg)"><i class="bi bi-arrow-up-short"></i>+' . $pct . '%</span>';
    if ($pct < 0)  return '<span class="badge" style="background:var(--c-badge-down-bg);color:var(--c-badge-down-fg)"><i class="bi bi-arrow-down-short"></i>' . $pct . '%</span>';
    return '<span class="badge" style="background:var(--c-badge-neutral-bg);color:var(--c-badge-neutral-fg)">0%</span>';

}
$p1Label = date('d M Y', strtotime($from1)) . ' — ' . date('d M Y', strtotime($to1));
$p2Label = date('d M Y', strtotime($from2)) . ' — ' . date('d M Y', strtotime($to2));
?>

<!-- KPI Comparison -->
<div class="row g-3 mb-4">

    <?php
    $kpis = [
        ['label' => 'Total Pengunjung', 'icon' => 'bi-people', 'p1' => $p1Total, 'p2' => $p2Total],
        ['label' => 'eWalk',            'icon' => 'bi-building', 'p1' => $p1Ewalk, 'p2' => $p2Ewalk],
        ['label' => 'Pentacity',        'icon' => 'bi-building', 'p1' => $p1Penta, 'p2' => $p2Penta],
        ['label' => 'Mobil',            'icon' => 'bi-car-front', 'p1' => $p1Vehicles['mobil'], 'p2' => $p2Vehicles['mobil']],
        ['label' => 'Motor',            'icon' => 'bi-bicycle',  'p1' => $p1Vehicles['motor'], 'p2' => $p2Vehicles['motor']],
    ];
    foreach ($kpis as $i => $k):
    ?>
    <div class="col-6 col-md-4 col-lg anim-fade-up" style="animation-delay:<?= (.05 + $i * .08) ?>s">
        <div class="card h-100">
            <div class="card-body py-3 px-3">
                <div class="small text-muted mb-2"><i class="bi <?= $k['icon'] ?> me-1"></i><?= $k['label'] ?></div>
                <div class="d-flex justify-content-between align-items-end mb-2">
                    <div>
                        <div class="x-small text-muted" style="font-size:.7rem">Periode 1</div>
                        <div class="fw-bold" data-count="<?= $k['p1'] ?>" style="color:var(--c-p1);font-size:1.1rem"><?= number_format($k['p1']) ?></div>
                    </div>
                    <div class="text-end">
                        <div class="x-small text-muted" style="font-size:.7rem">Periode 2</div>
                        <div class="fw-bold" data-count="<?= $k['p2'] ?>" style="color:var(--c-p2);font-size:1.1rem"><?= number_format($k['p2']) ?></div>
                    </div>
                </div>
                <div class="text-center"><?= diffBadge($k['p1'], $k['p2']) ?></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

</div>

<!-- Daily + Hourly charts -->
<div class="row g-3 mb-3">

<div class="col-lg-7 anim-fade-up" style="animation-delay:.45s">
<div class="card">
<div class="card-header d-flex justify-content-between align-items-center">
    <h6 class="mb-0 fw-semibold"><i class="bi bi-graph-up me-2"></i>Traffic Pengunjung Harian</h6>
    <div class="d-flex gap-2 small">
        <span><span class="badge rounded-pill" style="background:var(--c-p1)">&nbsp;</span> <?= $p1Label ?></span>
        <span><span class="badge rounded-pill" style="background:var(--c-p2)">&nbsp;</span> <?= $p2Label ?></span>
    </div>
</div>
<div class="card-body">
<?php if ($p1Total + $p2Total === 0): ?>
<p class="text-muted text-center py-4">Belum ada data untuk kedua periode.</p>
<?php else: ?>
<canvas id="dailyChart" height="100"></canvas>
<?php endif; ?>
</div>
</div>
</div>

<div class="col-lg-5 anim-fade-up" style="animation-delay:.52s">
<div class="card">
<div class="card-header"><h6 class="mb-0 fw-semibold"><i class="bi bi-clock me-2"></i>Traffic per Jam</h6></div>
<div class="card-body">
<?php if (array_sum($p1HourData) + array_sum($p2HourData) === 0): ?>
<p class="text-muted text-center py-4">Belum ada data.</p>
<?php else: ?>
<canvas id="hourChart" height="160"></canvas>
<?php endif; ?>
</div>
</div>
</div>

</div>

<!-- Door comparison -->
<?php
$allDoorsEwalk = [];
foreach ($door1Ewalk as $d) $allDoorsEwalk[$d['pintu']] = ['p1' => (int)$d['total'], 'p2' => 0];
foreach ($door2Ewalk as $d) {
    if (!isset($allDoorsEwalk[$d['pintu']])) $allDoorsEwalk[$d['pintu']] = ['p1' => 0, 'p2' => 0];
    $allDoorsEwalk[$d['pintu']]['p2'] = (int)$d['total'];
}

$allDoorsPenta = [];
foreach ($door1Penta as $d) $allDoorsPenta[$d['pintu']] = ['p1' => (int)$d['total'], 'p2' => 0];
foreach ($door2Penta as $d) {
    if (!isset($allDoorsPenta[$d['pintu']])) $allDoorsPenta[$d['pintu']] = ['p1' => 0, 'p2' => 0];
    $allDoorsPenta[$d['pintu']]['p2'] = (int)$d['total'];
}
?>

<?php if (!empty($allDoorsEwalk) || !empty($allDoorsPenta)): ?>
<div class="row g-3 mb-3">

<?php foreach ([['eWalk', $allDoorsEwalk, '#2563eb', 'primary'], ['Pentacity', $allDoorsPenta, '#059669', 'success']] as $di => [$mallName, $doorData, $color, $cls]): ?>
<?php if (empty($doorData)) continue; ?>
<div class="col-lg-6 anim-fade-up" style="animation-delay:<?= (.58 + $di * .08) ?>s">
<div class="card">
<div class="card-header">
    <h6 class="mb-0 fw-semibold"><i class="bi bi-door-open me-2" style="color:<?= $color ?>"></i>Per Pintu — <?= $mallName ?></h6>
</div>
<div class="card-body p-0">
<table class="table table-sm mb-0">
<thead class="table-light">
    <tr>
        <th>Pintu</th>
        <th class="text-end" style="color:var(--c-p1)">Periode 1</th>
        <th class="text-end" style="color:var(--c-p2)">Periode 2</th>
        <th class="text-end">Selisih</th>
    </tr>
</thead>
<tbody>
<?php
uasort($doorData, fn($a, $b) => ($b['p1'] + $b['p2']) <=> ($a['p1'] + $a['p2']));
foreach ($doorData as $pintu => $v):
    $diff = $v['p2'] - $v['p1'];
    $diffStr = $diff > 0 ? '<span class="text-success">+' . number_format($diff) . '</span>'
             : ($diff < 0 ? '<span class="text-danger">' . number_format($diff) . '</span>'
             : '<span class="text-muted">0</span>');
?>
<tr>
    <td class="fw-medium"><?= esc($pintu) ?></td>
    <td class="text-end"><?= number_format($v['p1']) ?></td>
    <td class="text-end"><?= number_format($v['p2']) ?></td>
    <td class="text-end"><?= $diffStr ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
</div>
<?php endforeach; ?>

</div>
<?php endif; ?>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
// Count-up
(function() {
    const dur = 900;
    document.querySelectorAll('[data-count]').forEach(el => {
        const target = parseInt(el.dataset.count) || 0;
        if (!target) return;
        el.textContent = '0';
        const start = performance.now();
        function step(now) {
            const t = Math.min(1, (now - start) / dur);
            el.textContent = Math.round((1 - Math.pow(1 - t, 3)) * target).toLocaleString('id-ID');
            if (t < 1) requestAnimationFrame(step);
        }
        requestAnimationFrame(step);
    });
})();
</script>
<script>
<?php if ($p1Total + $p2Total > 0): ?>
const dayLabels = <?= json_encode($dayLabels) ?>;
const p1Daily   = <?= json_encode($p1Daily) ?>;
const p2Daily   = <?= json_encode($p2Daily) ?>;

new Chart(document.getElementById('dailyChart'), {
    type: 'bar',
    data: {
        labels: dayLabels,
        datasets: [
            {
                label: 'Periode 1 — <?= addslashes($p1Label) ?>',
                data: p1Daily,
                backgroundColor: 'rgba(99,102,241,0.75)',
                borderRadius: 3
            },
            {
                label: 'Periode 2 — <?= addslashes($p2Label) ?>',
                data: p2Daily,
                backgroundColor: 'rgba(249,115,22,0.75)',
                borderRadius: 3
            }
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'top', labels: { boxWidth: 12 } } },
        scales: {
            x: { stacked: false },
            y: { beginAtZero: true, ticks: { callback: v => v.toLocaleString('id-ID') } }
        }
    }
});
<?php endif; ?>

<?php if (array_sum($p1HourData) + array_sum($p2HourData) > 0): ?>
new Chart(document.getElementById('hourChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($chartHours) ?>,
        datasets: [
            {
                label: 'Periode 1',
                data: <?= json_encode($p1HourData) ?>,
                borderColor: 'rgba(99,102,241,1)',
                backgroundColor: 'rgba(99,102,241,0.08)',
                tension: 0.4, fill: true, pointRadius: 3
            },
            {
                label: 'Periode 2',
                data: <?= json_encode($p2HourData) ?>,
                borderColor: 'rgba(249,115,22,1)',
                backgroundColor: 'rgba(249,115,22,0.08)',
                tension: 0.4, fill: true, pointRadius: 3
            }
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'top', labels: { boxWidth: 12 } } },
        scales: { y: { beginAtZero: true, ticks: { callback: v => v.toLocaleString('id-ID') } } }
    }
});
<?php endif; ?>
</script>
<?= $this->endSection() ?>
