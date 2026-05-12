<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<style>
:root {
    --c-p1: #6366f1;
    --c-p2: #f97316;
    --c-p3: #10b981;
    --c-badge-up-bg:      #dcfce7; --c-badge-up-fg:      #15803d;
    --c-badge-down-bg:    #fee2e2; --c-badge-down-fg:    #b91c1c;
    --c-badge-neutral-bg: #f1f5f9; --c-badge-neutral-fg: #475569;
}
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
        <small class="text-muted">Bandingkan dua atau tiga periode secara berdampingan</small>
    </div>
    <div class="d-flex gap-2">
        <?php
        $printUrl = base_url('traffic/print-compare') . '?from1=' . $from1 . '&to1=' . $to1 . '&from2=' . $from2 . '&to2=' . $to2;
        if ($hasP3) $printUrl .= '&from3=' . $from3 . '&to3=' . $to3;
        ?>
        <a href="<?= $printUrl ?>" target="_blank" class="btn btn-sm btn-outline-danger">
            <i class="bi bi-printer me-1"></i>Print / PDF
        </a>
        <a href="<?= base_url('traffic/summary') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-bar-chart-line me-1"></i>Summary
        </a>
    </div>
</div>

<!-- Filter -->
<div class="card mb-4">
<div class="card-body py-3">
<form method="GET" class="row g-3 align-items-end" id="compareForm">

    <!-- Periode 1 -->
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

    <!-- Periode 2 -->
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

    <!-- Periode 3 (opsional) -->
    <div id="p3Wrap" class="col-12 col-md-auto <?= $hasP3 ? '' : 'd-none' ?>">
        <div class="d-flex align-items-center gap-2 mb-2">
            <span class="badge rounded-pill px-3 py-2" style="background:var(--c-p3);font-size:.8rem">Periode 3</span>
        </div>
        <div class="d-flex gap-2">
            <div>
                <label class="form-label small fw-semibold mb-1">Dari</label>
                <input type="date" name="from3" id="from3Input" class="form-control form-control-sm" value="<?= $from3 ?? '' ?>">
            </div>
            <div>
                <label class="form-label small fw-semibold mb-1">Sampai</label>
                <input type="date" name="to3" id="to3Input" class="form-control form-control-sm" value="<?= $to3 ?? '' ?>">
            </div>
        </div>
    </div>

    <!-- Buttons -->
    <div class="col-auto">
        <label class="form-label small mb-1 d-block">&nbsp;</label>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-sm btn-primary">Bandingkan</button>
            <button type="button" id="toggleP3"
                    class="btn btn-sm <?= $hasP3 ? 'btn-outline-danger' : 'btn-outline-secondary' ?>">
                <i class="bi <?= $hasP3 ? 'bi-dash-circle' : 'bi-plus-circle' ?> me-1"></i><?= $hasP3 ? 'Hapus P3' : '+ Periode 3' ?>
            </button>
        </div>
    </div>

</form>
</div>
</div>

<?php
function pctDiff(int $a, int $b): ?float {
    if ($a === 0) return null;
    return round(($b - $a) / $a * 100, 1);
}
function diffBadge(int $base, int $val): string {
    $pct = pctDiff($base, $val);
    if ($pct === null) return '<span class="badge bg-secondary">—</span>';
    if ($pct > 0)  return '<span class="badge" style="background:var(--c-badge-up-bg);color:var(--c-badge-up-fg)"><i class="bi bi-arrow-up-short"></i>+' . $pct . '%</span>';
    if ($pct < 0)  return '<span class="badge" style="background:var(--c-badge-down-bg);color:var(--c-badge-down-fg)"><i class="bi bi-arrow-down-short"></i>' . $pct . '%</span>';
    return '<span class="badge" style="background:var(--c-badge-neutral-bg);color:var(--c-badge-neutral-fg)">0%</span>';
}
$p1Label = date('d M Y', strtotime($from1)) . ' — ' . date('d M Y', strtotime($to1));
$p2Label = date('d M Y', strtotime($from2)) . ' — ' . date('d M Y', strtotime($to2));
$p3Label = $hasP3 ? date('d M Y', strtotime($from3)) . ' — ' . date('d M Y', strtotime($to3)) : '';
$hasVehicleData = array_sum(array_column($p1Vehicles, null)) + array_sum(array_column($p2Vehicles, null)) + array_sum(array_column($p3Vehicles, null)) > 0;
?>

<!-- KPI Helper -->
<?php
function kpiCard(array $k, bool $hasP3): string {
    ob_start();
    ?>
    <div class="card h-100">
    <div class="card-body py-3 px-3">
        <div class="small text-muted mb-2"><i class="bi <?= $k['icon'] ?> me-1"></i><?= $k['label'] ?></div>
        <?php if (! $hasP3): ?>
        <div class="d-flex justify-content-between align-items-end mb-2">
            <div>
                <div style="font-size:.7rem" class="text-muted">Periode 1</div>
                <div class="fw-bold" data-count="<?= $k['p1'] ?>" style="color:var(--c-p1);font-size:1.1rem"><?= number_format($k['p1']) ?></div>
            </div>
            <div class="text-end">
                <div style="font-size:.7rem" class="text-muted">Periode 2</div>
                <div class="fw-bold" data-count="<?= $k['p2'] ?>" style="color:var(--c-p2);font-size:1.1rem"><?= number_format($k['p2']) ?></div>
            </div>
        </div>
        <div class="text-center"><?= diffBadge($k['p1'], $k['p2']) ?></div>
        <?php else: ?>
        <div class="d-flex flex-column gap-1">
            <div class="d-flex justify-content-between align-items-center">
                <span style="font-size:.7rem;color:var(--c-p1);font-weight:600">P1</span>
                <span class="fw-bold" data-count="<?= $k['p1'] ?>" style="color:var(--c-p1);font-size:1rem"><?= number_format($k['p1']) ?></span>
            </div>
            <div class="d-flex justify-content-between align-items-center">
                <span style="font-size:.7rem;color:var(--c-p2);font-weight:600">P2</span>
                <div class="d-flex align-items-center gap-2">
                    <?= diffBadge($k['p1'], $k['p2']) ?>
                    <span class="fw-bold" data-count="<?= $k['p2'] ?>" style="color:var(--c-p2);font-size:1rem"><?= number_format($k['p2']) ?></span>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center">
                <span style="font-size:.7rem;color:var(--c-p3);font-weight:600">P3</span>
                <div class="d-flex align-items-center gap-2">
                    <?= diffBadge($k['p1'], $k['p3']) ?>
                    <span class="fw-bold" data-count="<?= $k['p3'] ?>" style="color:var(--c-p3);font-size:1rem"><?= number_format($k['p3']) ?></span>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    </div>
    <?php
    return ob_get_clean();
}

$visitorKpis = [
    ['label' => 'Total Pengunjung', 'icon' => 'bi-people',   'p1' => $p1Total, 'p2' => $p2Total, 'p3' => $p3Total],
    ['label' => 'eWalk',            'icon' => 'bi-building', 'p1' => $p1Ewalk, 'p2' => $p2Ewalk, 'p3' => $p3Ewalk],
    ['label' => 'Pentacity',        'icon' => 'bi-building', 'p1' => $p1Penta, 'p2' => $p2Penta, 'p3' => $p3Penta],
];
$vehicleKpis = [
    ['label' => 'Mobil',     'icon' => 'bi-car-front',  'p1' => $p1Vehicles['mobil'],     'p2' => $p2Vehicles['mobil'],     'p3' => $p3Vehicles['mobil']],
    ['label' => 'Motor',     'icon' => 'bi-bicycle',    'p1' => $p1Vehicles['motor'],     'p2' => $p2Vehicles['motor'],     'p3' => $p3Vehicles['motor']],
    ['label' => 'Mobil Box', 'icon' => 'bi-truck',      'p1' => $p1Vehicles['mobil_box'], 'p2' => $p2Vehicles['mobil_box'], 'p3' => $p3Vehicles['mobil_box']],
    ['label' => 'Bus',       'icon' => 'bi-bus-front',  'p1' => $p1Vehicles['bus'],       'p2' => $p2Vehicles['bus'],       'p3' => $p3Vehicles['bus']],
    ['label' => 'Truck',     'icon' => 'bi-truck',      'p1' => $p1Vehicles['truck'],     'p2' => $p2Vehicles['truck'],     'p3' => $p3Vehicles['truck']],
    ['label' => 'Taxi',      'icon' => 'bi-taxi-front', 'p1' => $p1Vehicles['taxi'],      'p2' => $p2Vehicles['taxi'],      'p3' => $p3Vehicles['taxi']],
];
?>

<!-- KPI Pengunjung -->
<div class="row g-3 mb-3">
<?php foreach ($visitorKpis as $i => $k): ?>
<div class="col-6 col-md-4 col-lg anim-fade-up" style="animation-delay:<?= (.05 + $i * .08) ?>s">
    <?= kpiCard($k, $hasP3) ?>
</div>
<?php endforeach; ?>
</div>

<!-- KPI Kendaraan -->
<?php if ($hasVehicleData): ?>
<div class="row g-3 mb-4">
<?php foreach ($vehicleKpis as $i => $k): ?>
<div class="col-6 col-md-4 col-lg anim-fade-up" style="animation-delay:<?= (.29 + $i * .07) ?>s">
    <?= kpiCard($k, $hasP3) ?>
</div>
<?php endforeach; ?>
</div>
<?php else: ?>
<div class="mb-4"></div>
<?php endif; ?>

<?php
// Weekday / Weekend per periode
$wdWeData = [
    ['label' => 'Periode 1', 'color' => 'var(--c-p1)', 'wd' => $p1WdWe['wd'], 'we' => $p1WdWe['we']],
    ['label' => 'Periode 2', 'color' => 'var(--c-p2)', 'wd' => $p2WdWe['wd'], 'we' => $p2WdWe['we']],
];
if ($hasP3) $wdWeData[] = ['label' => 'Periode 3', 'color' => 'var(--c-p3)', 'wd' => $p3WdWe['wd'], 'we' => $p3WdWe['we']];
$hasWdWe = array_sum(array_map(fn($d) => $d['wd']['total'] + $d['we']['total'], $wdWeData)) > 0;
?>
<?php if ($hasWdWe): ?>
<div class="card mb-4 anim-fade-up" style="animation-delay:.65s">
<div class="card-header py-2">
    <span class="small fw-semibold"><i class="bi bi-calendar-week me-2"></i>Weekdays vs Weekend per Periode</span>
</div>
<div class="card-body p-0">
<table class="table table-sm mb-0" style="font-size:.85rem">
<thead class="table-light">
<tr>
    <th class="ps-3">Periode</th>
    <th colspan="3" class="text-center border-start" style="background:#eff6ff">Weekdays <span class="fw-normal text-muted">(Sen–Kam)</span></th>
    <th colspan="3" class="text-center border-start" style="background:#fffbeb">Weekend <span class="fw-normal text-muted">(Jum–Min)</span></th>
</tr>
<tr>
    <th class="ps-3 text-muted fw-normal" style="font-size:.75rem"></th>
    <th class="text-end border-start" style="background:#eff6ff;font-size:.75rem">Total</th>
    <th class="text-end" style="background:#eff6ff;font-size:.75rem">Rata-rata/hari</th>
    <th class="text-end" style="background:#eff6ff;font-size:.75rem">Hari aktif</th>
    <th class="text-end border-start" style="background:#fffbeb;font-size:.75rem">Total</th>
    <th class="text-end" style="background:#fffbeb;font-size:.75rem">Rata-rata/hari</th>
    <th class="text-end pe-3" style="background:#fffbeb;font-size:.75rem">Hari aktif</th>
</tr>
</thead>
<tbody>
<?php foreach ($wdWeData as $row): ?>
<tr>
    <td class="ps-3 fw-semibold" style="color:<?= $row['color'] ?>"><?= $row['label'] ?></td>
    <td class="text-end border-start"><?= number_format($row['wd']['total']) ?></td>
    <td class="text-end text-muted"><?= number_format($row['wd']['avg']) ?></td>
    <td class="text-end text-muted"><?= $row['wd']['days'] ?></td>
    <td class="text-end border-start"><?= number_format($row['we']['total']) ?></td>
    <td class="text-end text-muted"><?= number_format($row['we']['avg']) ?></td>
    <td class="text-end pe-3 text-muted"><?= $row['we']['days'] ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
<?php endif; ?>

<?php
$anyEvents = ! empty($p1Events) || ! empty($p2Events) || ($hasP3 && ! empty($p3Events));
if ($anyEvents):
    $periods = [
        ['label' => 'Periode 1', 'color' => 'var(--c-p1)', 'events' => $p1Events, 'from' => $from1, 'to' => $to1],
        ['label' => 'Periode 2', 'color' => 'var(--c-p2)', 'events' => $p2Events, 'from' => $from2, 'to' => $to2],
    ];
    if ($hasP3) $periods[] = ['label' => 'Periode 3', 'color' => 'var(--c-p3)', 'events' => $p3Events, 'from' => $from3, 'to' => $to3];
?>
<div class="row g-3 mb-4">
<?php foreach ($periods as $col): ?>
<?php if (empty($col['events'])) continue; ?>
<div class="col anim-fade-up" style="animation-delay:.7s">
<div class="card h-100" style="border-top:3px solid <?= $col['color'] ?>">
<div class="card-body py-2 px-3">
    <div class="small fw-semibold mb-2" style="color:<?= $col['color'] ?>">
        <i class="bi bi-calendar-event me-1"></i><?= $col['label'] ?>
    </div>
    <div class="d-flex flex-column gap-1">
    <?php foreach ($col['events'] as $ev):
        $evEnd = date('d M Y', strtotime($ev['start_date'] . ' +' . ($ev['event_days'] - 1) . ' days'));
    ?>
    <div>
        <span class="fw-medium small"><?= esc($ev['name']) ?></span>
        <div class="text-muted" style="font-size:.72rem"><?= date('d M Y', strtotime($ev['start_date'])) ?> – <?= $evEnd ?></div>
    </div>
    <?php endforeach; ?>
    </div>
</div>
</div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Daily + Hourly charts -->
<div class="row g-3 mb-3">

<div class="col-lg-7 anim-fade-up" style="animation-delay:.45s">
<div class="card">
<div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h6 class="mb-0 fw-semibold"><i class="bi bi-graph-up me-2"></i>Traffic Pengunjung Harian</h6>
    <div class="d-flex flex-wrap gap-2 small">
        <span><span class="badge rounded-pill" style="background:var(--c-p1)">&nbsp;</span> <?= $p1Label ?></span>
        <span><span class="badge rounded-pill" style="background:var(--c-p2)">&nbsp;</span> <?= $p2Label ?></span>
        <?php if ($hasP3): ?>
        <span><span class="badge rounded-pill" style="background:var(--c-p3)">&nbsp;</span> <?= $p3Label ?></span>
        <?php endif; ?>
    </div>
</div>
<div class="card-body">
<?php if ($p1Total + $p2Total + $p3Total === 0): ?>
<p class="text-muted text-center py-4">Belum ada data untuk periode yang dipilih.</p>
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
<?php if (array_sum($p1HourData) + array_sum($p2HourData) + array_sum($p3HourData) === 0): ?>
<p class="text-muted text-center py-4">Belum ada data.</p>
<?php else: ?>
<canvas id="hourChart" height="160"></canvas>
<?php endif; ?>
</div>
</div>
</div>

</div>

<!-- Vehicle comparison chart -->
<?php
$vtypes = [
    ['Mobil',     'mobil',     'rgba(245,158,11,0.8)'],
    ['Motor',     'motor',     'rgba(239,68,68,0.8)'],
    ['Mobil Box', 'mobil_box', 'rgba(99,102,241,0.8)'],
    ['Bus',       'bus',       'rgba(16,185,129,0.8)'],
    ['Truck',     'truck',     'rgba(139,92,246,0.8)'],
    ['Taxi',      'taxi',      'rgba(236,72,153,0.8)'],
];
?>
<?php if ($hasVehicleData): ?>
<div class="row g-3 mb-3">
<div class="col-12 anim-fade-up" style="animation-delay:.6s">
<div class="card">
<div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h6 class="mb-0 fw-semibold"><i class="bi bi-car-front me-2"></i>Perbandingan Kendaraan per Tipe</h6>
    <div class="d-flex flex-wrap gap-2 small">
        <span><span class="badge rounded-pill" style="background:var(--c-p1)">&nbsp;</span> <?= $p1Label ?></span>
        <span><span class="badge rounded-pill" style="background:var(--c-p2)">&nbsp;</span> <?= $p2Label ?></span>
        <?php if ($hasP3): ?>
        <span><span class="badge rounded-pill" style="background:var(--c-p3)">&nbsp;</span> <?= $p3Label ?></span>
        <?php endif; ?>
    </div>
</div>
<div class="card-body">
<canvas id="vehicleCompareChart" height="80"></canvas>
</div>
</div>
</div>
</div>
<?php endif; ?>

<!-- Door comparison -->
<?php
$allDoorsEwalk = [];
foreach ($door1Ewalk as $d) $allDoorsEwalk[$d['pintu']] = ['p1' => (int)$d['total'], 'p2' => 0, 'p3' => 0];
foreach ($door2Ewalk as $d) {
    if (! isset($allDoorsEwalk[$d['pintu']])) $allDoorsEwalk[$d['pintu']] = ['p1' => 0, 'p2' => 0, 'p3' => 0];
    $allDoorsEwalk[$d['pintu']]['p2'] = (int)$d['total'];
}
if ($hasP3) {
    foreach ($door3Ewalk as $d) {
        if (! isset($allDoorsEwalk[$d['pintu']])) $allDoorsEwalk[$d['pintu']] = ['p1' => 0, 'p2' => 0, 'p3' => 0];
        $allDoorsEwalk[$d['pintu']]['p3'] = (int)$d['total'];
    }
}

$allDoorsPenta = [];
foreach ($door1Penta as $d) $allDoorsPenta[$d['pintu']] = ['p1' => (int)$d['total'], 'p2' => 0, 'p3' => 0];
foreach ($door2Penta as $d) {
    if (! isset($allDoorsPenta[$d['pintu']])) $allDoorsPenta[$d['pintu']] = ['p1' => 0, 'p2' => 0, 'p3' => 0];
    $allDoorsPenta[$d['pintu']]['p2'] = (int)$d['total'];
}
if ($hasP3) {
    foreach ($door3Penta as $d) {
        if (! isset($allDoorsPenta[$d['pintu']])) $allDoorsPenta[$d['pintu']] = ['p1' => 0, 'p2' => 0, 'p3' => 0];
        $allDoorsPenta[$d['pintu']]['p3'] = (int)$d['total'];
    }
}
?>

<?php if (! empty($allDoorsEwalk) || ! empty($allDoorsPenta)): ?>
<div class="row g-3 mb-3">

<?php foreach ([['eWalk', $allDoorsEwalk, '#2563eb'], ['Pentacity', $allDoorsPenta, '#059669']] as $di => [$mallName, $doorData, $color]): ?>
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
        <th class="text-end" style="color:var(--c-p1)">P1</th>
        <th class="text-end" style="color:var(--c-p2)">P2</th>
        <?php if ($hasP3): ?>
        <th class="text-end" style="color:var(--c-p3)">P3</th>
        <?php else: ?>
        <th class="text-end">Selisih</th>
        <?php endif; ?>
    </tr>
</thead>
<tbody>
<?php
uasort($doorData, fn($a, $b) => ($b['p1'] + $b['p2'] + $b['p3']) <=> ($a['p1'] + $a['p2'] + $a['p3']));
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
    <?php if ($hasP3): ?>
    <td class="text-end"><?= number_format($v['p3']) ?></td>
    <?php else: ?>
    <td class="text-end"><?= $diffStr ?></td>
    <?php endif; ?>
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
// Count-up animation
(function () {
    const dur = 900;
    document.querySelectorAll('[data-count]').forEach(function (el) {
        const target = parseInt(el.dataset.count) || 0;
        if (! target) return;
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

// Toggle Periode 3
document.getElementById('toggleP3').addEventListener('click', function () {
    const wrap    = document.getElementById('p3Wrap');
    const visible = ! wrap.classList.contains('d-none');
    if (visible) {
        document.getElementById('from3Input').value = '';
        document.getElementById('to3Input').value   = '';
        wrap.classList.add('d-none');
        this.innerHTML = '<i class="bi bi-plus-circle me-1"></i>+ Periode 3';
        this.className = 'btn btn-sm btn-outline-secondary';
    } else {
        wrap.classList.remove('d-none');
        this.innerHTML = '<i class="bi bi-dash-circle me-1"></i>Hapus P3';
        this.className = 'btn btn-sm btn-outline-danger';
    }
});
</script>
<script>
<?php if ($p1Total + $p2Total + $p3Total > 0): ?>
const dayLabels = <?= json_encode($dayLabels) ?>;
const p1Daily   = <?= json_encode($p1Daily) ?>;
const p2Daily   = <?= json_encode($p2Daily) ?>;
<?php if ($hasP3): ?>
const p3Daily   = <?= json_encode($p3Daily) ?>;
<?php endif; ?>

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
            <?php if ($hasP3): ?>
            ,{
                label: 'Periode 3 — <?= addslashes($p3Label) ?>',
                data: p3Daily,
                backgroundColor: 'rgba(16,185,129,0.75)',
                borderRadius: 3
            }
            <?php endif; ?>
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'top', labels: { boxWidth: 12 } } },
        scales: {
            x: { stacked: false },
            y: { beginAtZero: true, ticks: { callback: function (v) { return v.toLocaleString('id-ID'); } } }
        }
    }
});
<?php endif; ?>

<?php if ($hasVehicleData): ?>
new Chart(document.getElementById('vehicleCompareChart'), {
    type: 'bar',
    data: {
        labels: [<?php foreach ($vtypes as [$vl,,]) echo "'" . $vl . "',"; ?>],
        datasets: [
            {
                label: 'Periode 1 — <?= addslashes($p1Label) ?>',
                data: [<?php foreach ($vtypes as [,$vk,]) echo ($p1Vehicles[$vk] ?? 0) . ','; ?>],
                backgroundColor: 'rgba(99,102,241,0.8)',
                borderRadius: 4
            },
            {
                label: 'Periode 2 — <?= addslashes($p2Label) ?>',
                data: [<?php foreach ($vtypes as [,$vk,]) echo ($p2Vehicles[$vk] ?? 0) . ','; ?>],
                backgroundColor: 'rgba(249,115,22,0.8)',
                borderRadius: 4
            }
            <?php if ($hasP3): ?>
            ,{
                label: 'Periode 3 — <?= addslashes($p3Label) ?>',
                data: [<?php foreach ($vtypes as [,$vk,]) echo ($p3Vehicles[$vk] ?? 0) . ','; ?>],
                backgroundColor: 'rgba(16,185,129,0.8)',
                borderRadius: 4
            }
            <?php endif; ?>
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top', labels: { boxWidth: 12 } },
            tooltip: { callbacks: { label: ctx => ' ' + ctx.dataset.label + ': ' + ctx.parsed.y.toLocaleString('id-ID') } }
        },
        scales: {
            x: { stacked: false },
            y: { beginAtZero: true, ticks: { callback: v => v.toLocaleString('id-ID') } }
        }
    }
});
<?php endif; ?>

<?php if (array_sum($p1HourData) + array_sum($p2HourData) + array_sum($p3HourData) > 0): ?>
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
            <?php if ($hasP3): ?>
            ,{
                label: 'Periode 3',
                data: <?= json_encode($p3HourData) ?>,
                borderColor: 'rgba(16,185,129,1)',
                backgroundColor: 'rgba(16,185,129,0.08)',
                tension: 0.4, fill: true, pointRadius: 3
            }
            <?php endif; ?>
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'top', labels: { boxWidth: 12 } } },
        scales: { y: { beginAtZero: true, ticks: { callback: function (v) { return v.toLocaleString('id-ID'); } } } }
    }
});
<?php endif; ?>
</script>
<?= $this->endSection() ?>
