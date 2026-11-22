<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<style>
    .analisa-grid { display: grid; gap: .5rem; grid-template-columns: repeat(2, minmax(0, 1fr)); }
    @media (min-width: 768px)  { .analisa-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    @media (min-width: 1200px) { .analisa-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
/* Panel kembar (weekday/weekend, per-pintu): saat stack di HP, ganti garis kanan jadi garis bawah */
@media (max-width: 575.98px) {
    .split-col { border-right: 0 !important; border-bottom: 1px solid var(--bs-border-color) !important; }
}
</style>
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-bar-chart-line me-2"></i>Summary Daily Traffic</h4>
        <small class="text-muted"><?= date('d M Y', strtotime($from)) ?> — <?= date('d M Y', strtotime($to)) ?></small>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= base_url('traffic/laporan-bulanan') ?>?bulan=<?= date('Y-m', strtotime($from)) ?>"
           target="_blank" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-file-earmark-text me-1"></i>Laporan Bulanan
        </a>
        <a href="<?= base_url('traffic/print-summary') ?>?from=<?= $from ?>&to=<?= $to ?>"
           target="_blank" class="btn btn-sm btn-outline-danger">
            <i class="bi bi-printer me-1"></i>Print / PDF
        </a>
        <a href="<?= base_url('traffic/compare') ?>" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-arrow-left-right me-1"></i>Compare
        </a>
        <a href="<?= base_url('traffic') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-pencil me-1"></i>Input Data
        </a>
    </div>
</div>

<!-- Filter -->
<div class="card mb-4">
<div class="card-body py-2">
<form method="GET" class="row g-2 align-items-end">
    <div class="col-auto">
        <label class="form-label small fw-semibold mb-1">Dari</label>
        <input type="date" name="from" class="form-control form-control-sm" value="<?= $from ?>">
    </div>
    <div class="col-auto">
        <label class="form-label small fw-semibold mb-1">Sampai</label>
        <input type="date" name="to" class="form-control form-control-sm" value="<?= $to ?>">
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-sm btn-primary">Tampilkan</button>
    </div>
    <div class="col-auto ms-sm-2 d-flex gap-1 flex-wrap">
        <?php
        $presets = [
            'Hari ini'  => ['from' => date('Y-m-d'), 'to' => date('Y-m-d')],
            '7 hari'    => ['from' => date('Y-m-d', strtotime('-6 days')), 'to' => date('Y-m-d')],
            '30 hari'   => ['from' => date('Y-m-d', strtotime('-29 days')), 'to' => date('Y-m-d')],
            'Bln ini'   => ['from' => date('Y-m-01'), 'to' => date('Y-m-t')],
            'Bln lalu'  => ['from' => date('Y-m-01', strtotime('first day of last month')), 'to' => date('Y-m-t', strtotime('last day of last month'))],
        ];
        foreach ($presets as $label => $p):
            $active = ($from === $p['from'] && $to === $p['to']) ? 'btn-secondary' : 'btn-outline-secondary';
        ?>
        <a href="?from=<?= $p['from'] ?>&to=<?= $p['to'] ?>" class="btn btn-sm <?= $active ?>"><?= $label ?></a>
        <?php endforeach; ?>
    </div>
</form>
</div>
</div>

<?php
// Rincian weekdays (Sen–Kam) & weekend (Jum–Min) di bawah angka tiap kartu.
$_rpHari = fn ($n) => number_format((int) $n, 0, ',', '.');
// $hWd/$hWe = hari ber-data yang dipakai membagi (per mal: harinya sendiri; Total: gabungan).
$_splitHari = function (int $wd, int $we, ?int $hWd = null, ?int $hWe = null) use ($wdDays, $weDays, $_rpHari): string {
    if ($wd + $we <= 0) return '';
    $hWd ??= $wdDays; $hWe ??= $weDays;
    $avgWd = $hWd > 0 ? (int) round($wd / $hWd) : 0;
    $avgWe = $hWe > 0 ? (int) round($we / $hWe) : 0;
    return '<div class="split-hari mt-2 pt-2 border-top text-start">'
        . '<div class="d-flex justify-content-between small"><span><span class="badge bg-primary-subtle text-primary border me-1" style="font-size:.65rem">Weekdays</span></span>'
        . '<span class="fw-semibold">' . $_rpHari($wd) . '</span></div>'
        . '<div class="d-flex justify-content-between text-muted" style="font-size:.72rem"><span>Sen–Kam</span><span>' . $_rpHari($avgWd) . '/hari</span></div>'
        . '<div class="d-flex justify-content-between small mt-1"><span><span class="badge bg-warning-subtle text-warning-emphasis border me-1" style="font-size:.65rem">Weekend</span></span>'
        . '<span class="fw-semibold">' . $_rpHari($we) . '</span></div>'
        . '<div class="d-flex justify-content-between text-muted" style="font-size:.72rem"><span>Jum–Min</span><span>' . $_rpHari($avgWe) . '/hari</span></div>'
        . '</div>';
};
?>
<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 anim-fade-up" style="animation-delay:.05s">
        <div class="card text-center h-100" style="border-top:3px solid var(--c-ewalk)">
            <div class="card-body py-3">
                <div class="small text-muted mb-1"><i class="bi bi-building text-primary me-1"></i>eWalk</div>
                <div class="fw-bold fs-4 text-primary" data-count="<?= $totalEwalk ?>"><?= number_format($totalEwalk, 0, ',', '.') ?></div>
                <?php if ($totalVisitor > 0): ?>
                <div class="small text-muted"><?= round($totalEwalk / $totalVisitor * 100) ?>% total</div>
                <?php endif; ?>
                <?= $_splitHari($wdEwalk, $weEwalk, $hariMal['wdEw'] ?? null, $hariMal['weEw'] ?? null) ?>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 anim-fade-up" style="animation-delay:.12s">
        <div class="card text-center h-100" style="border-top:3px solid var(--c-penta)">
            <div class="card-body py-3">
                <div class="small text-muted mb-1"><i class="bi bi-building text-success me-1"></i>Pentacity</div>
                <div class="fw-bold fs-4 text-success" data-count="<?= $totalPenta ?>"><?= number_format($totalPenta, 0, ',', '.') ?></div>
                <?php if ($totalVisitor > 0): ?>
                <div class="small text-muted"><?= round($totalPenta / $totalVisitor * 100) ?>% total</div>
                <?php endif; ?>
                <?= $_splitHari($wdPenta, $wePenta, $hariMal['wdPt'] ?? null, $hariMal['wePt'] ?? null) ?>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4 anim-fade-up" style="animation-delay:.19s">
        <div class="card text-center h-100">
            <div class="card-body py-3">
                <div class="small text-muted mb-1">Total Pengunjung</div>
                <div class="fw-bold fs-4" data-count="<?= $totalVisitor ?>"><?= number_format($totalVisitor, 0, ',', '.') ?></div>
                <div class="small text-muted">eWalk + Pentacity</div>
                <?= $_splitHari($wdTotal, $weTotal) ?>
                <?php if ($wdDays + $weDays > 0): ?><div class="text-muted mt-1" style="font-size:.68rem"><?= $wdDays ?> hari weekdays · <?= $weDays ?> hari weekend aktif</div><?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if ($totalVisitor > 0):
    $dominant  = $totalEwalk >= $totalPenta ? 'eWalk' : 'Pentacity';
    $domColor  = $totalEwalk >= $totalPenta ? 'primary' : 'success';
    $domPct    = round(max($totalEwalk, $totalPenta) / $totalVisitor * 100);
    // Format Indonesia: angka bertitik, bulan & hari berbahasa Indonesia.
    $_bln  = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $_hari = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
    $_tgl  = fn ($t, $thn = true) => (int) date('j', strtotime($t)) . ' ' . $_bln[(int) date('n', strtotime($t))] . ($thn ? ' ' . date('Y', strtotime($t)) : '');
    $_ang  = fn ($n) => number_format((float) $n, 0, ',', '.');
    $prevLabel = $_tgl($insightPrevFrom, date('Y', strtotime($insightPrevFrom)) !== date('Y', strtotime($insightPrevTo))) . ' – ' . $_tgl($insightPrevTo);
    $bestLabel = ! empty($insightBestDate) ? $_hari[(int) date('w', strtotime($insightBestDate))] . ', ' . $_tgl($insightBestDate, false) : $insightBestDay;

    if ($insightChangePct === null) {
        $vsIcon = 'bi-dash-circle'; $vsColor = 'secondary'; $vsVal = '—'; $vsSub = 'belum ada data';
    } elseif ($insightChangePct >= 0) {
        $vsIcon = 'bi-arrow-up-circle-fill'; $vsColor = 'success';
        $vsVal  = '+' . str_replace('.', ',', (string) $insightChangePct) . '%';
        $vsSub  = $_ang($insightPrevTotal) . ' orang periode lalu';
    } else {
        $vsIcon = 'bi-arrow-down-circle-fill'; $vsColor = 'danger';
        $vsVal  = str_replace('.', ',', (string) $insightChangePct) . '%';
        $vsSub  = $_ang($insightPrevTotal) . ' orang periode lalu';
    }

    $stats = array_filter([
        ['icon'=>'bi-people',              'color'=>'indigo',   'label'=>'Rata-rata/hari',       'val'=>$_ang($insightAvgDaily), 'sub'=>'pengunjung'],
        $insightPeakHour ? ['icon'=>'bi-clock-history', 'color'=>'orange',   'label'=>'Jam tersibuk',         'val'=>$insightPeakHour,               'sub'=>$_ang($insightPeakVal).' orang'] : null,
        $insightBestDay  ? ['icon'=>'bi-trophy',        'color'=>'success',  'label'=>'Hari terbaik',         'val'=>$bestLabel,                    'sub'=>$_ang($insightBestVal).' orang']  : null,
        ($totalEwalk > 0 || $totalPenta > 0) ? ['icon'=>'bi-building', 'color'=>$domColor, 'label'=>'Mall dominan', 'val'=>$dominant, 'sub'=>$domPct.'% traffic'] : null,
        !empty($doorEwalk) ? ['icon'=>'bi-door-open',  'color'=>'primary',  'label'=>'Pintu tersibuk eWalk',     'val'=>esc($doorEwalk[0]['pintu']),    'sub'=>$_ang($doorEwalk[0]['total']).' orang'] : null,
        !empty($doorPenta) ? ['icon'=>'bi-door-open',  'color'=>'success',  'label'=>'Pintu tersibuk Pentacity', 'val'=>esc($doorPenta[0]['pintu']),   'sub'=>$_ang($doorPenta[0]['total']).' orang'] : null,
        ['icon'=>$vsIcon, 'color'=>$vsColor, 'label'=>'vs '.$prevLabel, 'val'=>$vsVal, 'sub'=>$vsSub],
    ]);

    $colorMap = ['indigo'   => ['bg' => 'var(--c-icon-primary-bg)', 'fg' => 'var(--c-icon-primary-fg)'],
                 'orange'   => ['bg' => 'var(--c-icon-orange-bg)',  'fg' => 'var(--c-icon-orange-fg)'],
                 'success'  => ['bg' => 'var(--c-icon-success-bg)', 'fg' => 'var(--c-icon-success-fg)'],
                 'primary'  => ['bg' => 'var(--c-icon-blue-bg)',    'fg' => 'var(--c-icon-blue-fg)'],
                 'danger'   => ['bg' => 'var(--c-icon-danger-bg)',  'fg' => 'var(--c-icon-danger-fg)'],
                 'secondary'=> ['bg' => 'var(--c-icon-muted-bg)',   'fg' => 'var(--c-icon-muted-fg)']];
?>
<div class="card mb-4 anim-fade-up" style="border-left:4px solid var(--accent-primary);animation-delay:.33s">
<div class="card-body py-3 px-3">
    <div class="d-flex align-items-center gap-2 mb-3">
        <i class="bi bi-stars" style="color:var(--bs-primary)"></i>
        <span class="fw-semibold small" style="color:var(--bs-primary)">Analisa Otomatis</span>
        <span class="text-muted small ms-1"><?= $_tgl($from) ?> – <?= $_tgl($to) ?></span>
    </div>
    <?php
    // Item terakhir melebar mengisi sisa baris agar tidak ada kotak yatim (2/3/4 kolom).
    $_n = count($stats);
    $_span = fn ($c) => ($_n % $c) === 0 ? 1 : $c - ($_n % $c) + 1;
    ?>
    <style>
        .analisa-grid > :last-child { grid-column: span <?= $_span(2) ?>; }
        @media (min-width: 768px)  { .analisa-grid > :last-child { grid-column: span <?= $_span(3) ?>; } }
        @media (min-width: 1200px) { .analisa-grid > :last-child { grid-column: span <?= $_span(4) ?>; } }
    </style>
    <div class="analisa-grid">
    <?php foreach ($stats as $i => $s):
        $c = $colorMap[$s['color']] ?? $colorMap['secondary']; ?>
    <div class="anim-fade-up" style="min-width:0;animation-delay:<?= (.38 + $i * .06) ?>s">
        <div class="d-flex align-items-center gap-2 p-2 rounded-2 h-100" style="background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1)">
            <div class="rounded-2 p-2 flex-shrink-0" style="background:<?= $c['bg'] ?>">
                <i class="bi <?= $s['icon'] ?>" style="color:<?= $c['fg'] ?>;font-size:.9rem"></i>
            </div>
            <div style="min-width:0">
                <div class="text-muted lh-1 mb-1" style="font-size:.7rem"><?= $s['label'] ?></div>
                <div class="fw-bold lh-sm text-break" style="font-size:.9rem;color:<?= $c['fg'] ?>"><?= $s['val'] ?></div>
                <div class="text-muted lh-1 mt-1" style="font-size:.7rem"><?= $s['sub'] ?></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    </div>
</div>
</div>
<?php endif; ?>

<div class="row g-3 mb-3">

<!-- Traffic harian chart -->
<div class="col-lg-6 anim-fade-up" style="animation-delay:.45s">
<div class="card">
<div class="card-header"><h6 class="mb-0 fw-semibold"><i class="bi bi-graph-up me-2"></i>Traffic Pengunjung Harian</h6></div>
<div class="card-body">
<?php if ($totalVisitor === 0): ?>
<p class="text-muted text-center py-4">Belum ada data untuk periode ini.</p>
<?php else: ?>
<canvas id="dailyChart" height="110"></canvas>
<?php endif; ?>
</div>
</div>
</div>

<!-- Traffic per Jam chart -->
<div class="col-lg-6 anim-fade-up" style="animation-delay:.52s">
<div class="card">
<div class="card-header"><h6 class="mb-0 fw-semibold"><i class="bi bi-clock me-2"></i>Traffic per Jam (Gabungan)</h6></div>
<div class="card-body">
<?php if (array_sum($chartHourEwalk) + array_sum($chartHourPenta) === 0): ?>
<p class="text-muted text-center py-4">Belum ada data.</p>
<?php else: ?>
<canvas id="hourChart" height="130"></canvas>
<?php endif; ?>
</div>
</div>
</div>

</div>


<div class="row g-3 mb-3">

<!-- Vehicle chart -->
<div class="col-lg-5 anim-fade-up" style="animation-delay:.58s">
<div class="card h-100">
<div class="card-header"><h6 class="mb-0 fw-semibold"><i class="bi bi-car-front me-2"></i>Kendaraan Harian</h6></div>
<div class="card-body">
<?php if (($totalMobil + $totalMotor + $totalMobilBox + $totalTruck + $totalBus + $totalMobilFree + $totalMotorFree) === 0): ?>
<p class="text-muted text-center py-4">Belum ada data kendaraan.</p>
<?php else: ?>
<canvas id="vehicleChart" height="160"></canvas>
<?php endif; ?>
</div>
</div>
</div>

<!-- Door breakdown -->
<div class="col-lg-7 anim-fade-up" style="animation-delay:.64s">
<div class="card h-100">
<div class="card-header"><h6 class="mb-0 fw-semibold"><i class="bi bi-door-open me-2"></i>Traffic per Pintu</h6></div>
<div class="card-body p-0">
<div class="row g-0">

<!-- eWalk -->
<div class="col-12 col-sm-6 border-end split-col">
<div class="px-3 py-2 border-bottom bg-light">
    <span class="small fw-semibold text-primary"><i class="bi bi-building me-1"></i>eWalk</span>
</div>
<?php if (empty($doorEwalk)): ?>
<p class="text-muted text-center small py-3">Belum ada data.</p>
<?php else: ?>
<?php $maxEwalk = max(array_column($doorEwalk, 'total')); ?>
<?php foreach ($doorEwalk as $d): ?>
<div class="px-3 py-2 border-bottom">
    <div class="d-flex justify-content-between small mb-1">
        <span class="fw-medium"><?= esc($d['pintu']) ?></span>
        <span class="text-muted"><?= number_format($d['total']) ?></span>
    </div>
    <div class="progress" style="height:4px">
        <div class="progress-bar bg-primary" style="width:<?= $maxEwalk > 0 ? round($d['total']/$maxEwalk*100) : 0 ?>%"></div>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>

<!-- Pentacity -->
<div class="col-12 col-sm-6">
<div class="px-3 py-2 border-bottom bg-light">
    <span class="small fw-semibold text-success"><i class="bi bi-building me-1"></i>Pentacity</span>
</div>
<?php if (empty($doorPenta)): ?>
<p class="text-muted text-center small py-3">Belum ada data.</p>
<?php else: ?>
<?php $maxPenta = max(array_column($doorPenta, 'total')); ?>
<?php foreach ($doorPenta as $d): ?>
<div class="px-3 py-2 border-bottom">
    <div class="d-flex justify-content-between small mb-1">
        <span class="fw-medium"><?= esc($d['pintu']) ?></span>
        <span class="text-muted"><?= number_format($d['total']) ?></span>
    </div>
    <div class="progress" style="height:4px">
        <div class="progress-bar bg-success" style="width:<?= $maxPenta > 0 ? round($d['total']/$maxPenta*100) : 0 ?>%"></div>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>

</div>
</div>
</div>
</div>

</div>

<?php if (! empty($periodEvents)): ?>
<div class="card mb-4 anim-fade-up" style="animation-delay:.7s">
<div class="card-body py-2 px-3 d-flex align-items-center gap-2 flex-wrap">
    <span class="small fw-semibold text-muted me-1"><i class="bi bi-calendar-event me-1"></i>Event periode ini:</span>
    <?php foreach ($periodEvents as $ev):
        $evEnd = date('d M Y', strtotime($ev['start_date'] . ' +' . ($ev['event_days'] - 1) . ' days'));
        $evStart = date('d M Y', strtotime($ev['start_date']));
    ?>
    <span class="badge bg-primary-subtle text-primary border d-inline-flex flex-wrap align-items-center gap-1 text-start" style="font-size:.78rem;font-weight:500;white-space:normal;max-width:100%">
        <?= esc($ev['name']) ?>
        <span class="fw-normal text-muted" style="font-size:.72rem"><?= $evStart ?> – <?= $evEnd ?></span>
    </span>
    <?php endforeach; ?>
</div>
</div>
<?php endif; ?>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
</script>
<script>
<?php if ($totalVisitor > 0): ?>
new Chart(document.getElementById('dailyChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($chartDates) ?>,
        datasets: [
            { label: 'eWalk',     data: <?= json_encode($chartEwalk) ?>, backgroundColor: 'rgba(37,99,235,0.75)', borderRadius: 3 },
            { label: 'Pentacity', data: <?= json_encode($chartPenta) ?>, backgroundColor: 'rgba(5,150,105,0.75)', borderRadius: 3 },
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'top' } },
        scales: { x: { stacked: false }, y: { beginAtZero: true, ticks: { callback: v => v.toLocaleString('id-ID') } } }
    }
});
<?php endif; ?>

<?php if (array_sum($chartHourEwalk) + array_sum($chartHourPenta) > 0): ?>
new Chart(document.getElementById('hourChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($chartHours) ?>,
        datasets: [
            {
                label: 'eWalk',
                data: <?= json_encode($chartHourEwalk) ?>,
                borderColor: 'rgba(37,99,235,1)',
                backgroundColor: 'rgba(37,99,235,0.08)',
                tension: 0.4, fill: true, pointRadius: 3, pointHoverRadius: 5
            },
            {
                label: 'Pentacity',
                data: <?= json_encode($chartHourPenta) ?>,
                borderColor: 'rgba(5,150,105,1)',
                backgroundColor: 'rgba(5,150,105,0.08)',
                tension: 0.4, fill: true, pointRadius: 3, pointHoverRadius: 5
            }
        ]
    },
    options: {
        responsive: true,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: { position: 'top' },
            tooltip: { callbacks: { label: ctx => ' ' + ctx.dataset.label + ': ' + ctx.parsed.y.toLocaleString('id-ID') } }
        },
        scales: { y: { beginAtZero: true, ticks: { callback: v => v.toLocaleString('id-ID') } } }
    }
});
<?php endif; ?>

<?php
$totalAllVehicles = $totalMobil + $totalMotor + $totalMobilBox + $totalTruck + $totalBus + $totalMobilFree + $totalMotorFree;
if ($totalAllVehicles > 0):
    $vDatasets = [
        ['Mobil',      $chartVMobil,     'rgba(245,158,11,0.8)'],
        ['Motor',      $chartVMotor,     'rgba(239,68,68,0.8)'],
        ['Box',        $chartVMobilBox,  'rgba(99,102,241,0.8)'],
        ['Truk',       $chartVTruck,     'rgba(139,92,246,0.8)'],
        ['Bus',        $chartVBus,       'rgba(16,185,129,0.8)'],
        ['Mobil Free', $chartVMobilFree, 'rgba(14,165,233,0.8)'],
        ['Motor Free', $chartVMotorFree, 'rgba(236,72,153,0.8)'],
    ];
    $vDatasets = array_filter($vDatasets, fn($d) => array_sum($d[1]) > 0);
?>
new Chart(document.getElementById('vehicleChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($chartDates) ?>,
        datasets: [
            <?php foreach ($vDatasets as [$vLabel, $vData, $vColor]): ?>
            { label: '<?= $vLabel ?>', data: <?= json_encode($vData) ?>, backgroundColor: '<?= $vColor ?>', borderRadius: 3 },
            <?php endforeach; ?>
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
</script>
<?= $this->endSection() ?>
