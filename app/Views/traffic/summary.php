<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<style>
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
}
@keyframes fadeIn {
    from { opacity: 0; }
    to   { opacity: 1; }
}
.anim-fade-up  { opacity: 0; animation: fadeUp .5s cubic-bezier(.22,.68,0,1.15) forwards; }
.anim-fade-in  { opacity: 0; animation: fadeIn .45s ease forwards; }
</style>
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-bar-chart-line me-2"></i>Summary Daily Traffic</h4>
        <small class="text-muted"><?= date('d M Y', strtotime($from)) ?> — <?= date('d M Y', strtotime($to)) ?></small>
    </div>
    <div class="d-flex gap-2">
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
    <div class="col-auto ms-2 d-flex gap-1">
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

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3 anim-fade-up" style="animation-delay:.05s">
        <div class="card text-center h-100">
            <div class="card-body py-3">
                <div class="small text-muted mb-1">Total Pengunjung</div>
                <div class="fw-bold fs-4" data-count="<?= $totalVisitor ?>"><?= number_format($totalVisitor) ?></div>
                <div class="small text-muted">eWalk + Pentacity</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 anim-fade-up" style="animation-delay:.12s">
        <div class="card text-center h-100" style="border-top:3px solid var(--c-ewalk)">
            <div class="card-body py-3">
                <div class="small text-muted mb-1"><i class="bi bi-building text-primary me-1"></i>eWalk</div>
                <div class="fw-bold fs-4 text-primary" data-count="<?= $totalEwalk ?>"><?= number_format($totalEwalk) ?></div>
                <?php if ($totalVisitor > 0): ?>
                <div class="small text-muted"><?= round($totalEwalk / $totalVisitor * 100) ?>% total</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 anim-fade-up" style="animation-delay:.19s">
        <div class="card text-center h-100" style="border-top:3px solid var(--c-penta)">
            <div class="card-body py-3">
                <div class="small text-muted mb-1"><i class="bi bi-building text-success me-1"></i>Pentacity</div>
                <div class="fw-bold fs-4 text-success" data-count="<?= $totalPenta ?>"><?= number_format($totalPenta) ?></div>
                <?php if ($totalVisitor > 0): ?>
                <div class="small text-muted"><?= round($totalPenta / $totalVisitor * 100) ?>% total</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 anim-fade-up" style="animation-delay:.26s">
        <div class="card text-center h-100">
            <div class="card-body py-3">
                <div class="small text-muted mb-1"><i class="bi bi-car-front me-1"></i>Kendaraan</div>
                <div class="fw-bold fs-5" data-count="<?= $totalMobil + $totalMotor ?>"><?= number_format($totalMobil + $totalMotor) ?></div>
                <div class="small text-muted"><?= number_format($totalMobil) ?> mobil · <?= number_format($totalMotor) ?> motor</div>
            </div>
        </div>
    </div>
</div>

<?php if ($totalVisitor > 0):
    $dominant  = $totalEwalk >= $totalPenta ? 'eWalk' : 'Pentacity';
    $domColor  = $totalEwalk >= $totalPenta ? 'primary' : 'success';
    $domPct    = round(max($totalEwalk, $totalPenta) / $totalVisitor * 100);
    $prevLabel = date('d M', strtotime($insightPrevFrom)) . '–' . date('d M Y', strtotime($insightPrevTo));

    if ($insightChangePct === null) {
        $vsIcon = 'bi-dash-circle'; $vsColor = 'secondary'; $vsVal = '—'; $vsSub = 'belum ada data';
    } elseif ($insightChangePct >= 0) {
        $vsIcon = 'bi-arrow-up-circle-fill'; $vsColor = 'success';
        $vsVal  = '+' . $insightChangePct . '%';
        $vsSub  = number_format($insightPrevTotal) . ' orang';
    } else {
        $vsIcon = 'bi-arrow-down-circle-fill'; $vsColor = 'danger';
        $vsVal  = $insightChangePct . '%';
        $vsSub  = number_format($insightPrevTotal) . ' orang';
    }

    $stats = array_filter([
        ['icon'=>'bi-people',              'color'=>'indigo',   'label'=>'Rata-rata/hari',       'val'=>number_format($insightAvgDaily), 'sub'=>'pengunjung'],
        $insightPeakHour ? ['icon'=>'bi-clock-history', 'color'=>'orange',   'label'=>'Jam tersibuk',         'val'=>$insightPeakHour,               'sub'=>number_format($insightPeakVal).' orang'] : null,
        $insightBestDay  ? ['icon'=>'bi-trophy',        'color'=>'success',  'label'=>'Hari terbaik',         'val'=>$insightBestDay,                'sub'=>number_format($insightBestVal).' orang']  : null,
        ($totalEwalk > 0 || $totalPenta > 0) ? ['icon'=>'bi-building', 'color'=>$domColor, 'label'=>'Mall dominan', 'val'=>$dominant, 'sub'=>$domPct.'% traffic'] : null,
        !empty($doorEwalk) ? ['icon'=>'bi-door-open',  'color'=>'primary',  'label'=>'Pintu tersibuk eWalk',     'val'=>esc($doorEwalk[0]['pintu']),    'sub'=>number_format($doorEwalk[0]['total']).' orang'] : null,
        !empty($doorPenta) ? ['icon'=>'bi-door-open',  'color'=>'success',  'label'=>'Pintu tersibuk Pentacity', 'val'=>esc($doorPenta[0]['pintu']),   'sub'=>number_format($doorPenta[0]['total']).' orang'] : null,
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
        <span class="text-muted small ms-1"><?= date('d M Y', strtotime($from)) ?> — <?= date('d M Y', strtotime($to)) ?></span>
    </div>
    <div class="row g-2">
    <?php foreach ($stats as $i => $s):
        $c = $colorMap[$s['color']] ?? $colorMap['secondary']; ?>
    <div class="col-6 col-md-4 col-xl-auto flex-xl-fill anim-fade-up" style="animation-delay:<?= (.38 + $i * .06) ?>s">
        <div class="d-flex align-items-center gap-2 p-2 rounded-2 h-100" style="background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1)">
            <div class="rounded-2 p-2 flex-shrink-0" style="background:<?= $c['bg'] ?>">
                <i class="bi <?= $s['icon'] ?>" style="color:<?= $c['fg'] ?>;font-size:.9rem"></i>
            </div>
            <div class="min-w-0">
                <div class="text-muted lh-1 mb-1" style="font-size:.7rem"><?= $s['label'] ?></div>
                <div class="fw-bold lh-1" style="font-size:.9rem;color:<?= $c['fg'] ?>"><?= $s['val'] ?></div>
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
<?php if (($totalMobil + $totalMotor) === 0): ?>
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
<div class="col-6 border-end">
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
<div class="col-6">
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

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
// Count-up animation
(function() {
    const dur = 900;
    document.querySelectorAll('[data-count]').forEach(el => {
        const target = parseInt(el.dataset.count) || 0;
        if (!target) return;
        el.textContent = '0';
        const start = performance.now();
        function step(now) {
            const t    = Math.min(1, (now - start) / dur);
            const ease = 1 - Math.pow(1 - t, 3);
            el.textContent = Math.round(ease * target).toLocaleString('id-ID');
            if (t < 1) requestAnimationFrame(step);
        }
        requestAnimationFrame(step);
    });
})();

// Progress bar grow (IntersectionObserver)
(function() {
    const bars = document.querySelectorAll('.progress-bar');
    bars.forEach(bar => {
        const target = bar.style.width;
        bar.style.width = '0';
        bar.style.transition = 'none';
    });
    const obs = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (!entry.isIntersecting) return;
            const bar = entry.target;
            const target = bar.dataset.w;
            setTimeout(() => {
                bar.style.transition = 'width .8s cubic-bezier(.25,.46,.45,.94)';
                bar.style.width = target;
            }, 120);
            obs.unobserve(bar);
        });
    }, { threshold: 0.1 });
    bars.forEach(bar => {
        bar.dataset.w = bar.style.width || '0%';
        obs.observe(bar);
    });
})();
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

<?php if (($totalMobil + $totalMotor) > 0): ?>
new Chart(document.getElementById('vehicleChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($chartDates) ?>,
        datasets: [
            { label: 'Mobil', data: <?= json_encode($chartVMobil) ?>, backgroundColor: 'rgba(245,158,11,0.75)', borderRadius: 3 },
            { label: 'Motor', data: <?= json_encode($chartVMotor) ?>, backgroundColor: 'rgba(239,68,68,0.75)',  borderRadius: 3 },
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'top' } },
        scales: { x: { stacked: false }, y: { beginAtZero: true } }
    }
});
<?php endif; ?>
</script>
<?= $this->endSection() ?>
