<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<style>
.fade-up {
    opacity: 0;
    transform: translateY(14px);
    animation: fadeUpCr .5s cubic-bezier(.22,.68,0,1.2) forwards;
}
@keyframes fadeUpCr { to { opacity: 1; transform: translateY(0); } }
</style>
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php
$idBulan    = ['January'=>'Januari','February'=>'Februari','March'=>'Maret','April'=>'April',
               'May'=>'Mei','June'=>'Juni','July'=>'Juli','August'=>'Agustus',
               'September'=>'September','October'=>'Oktober','November'=>'November','December'=>'Desember'];
$bulanDt    = \DateTime::createFromFormat('Y-m', $bulan);
$bulanLabel = strtr($bulanDt->format('F Y'), $idBulan);

$statusColors = ['draft' => 'secondary', 'review' => 'warning', 'approved' => 'success', 'revision' => 'danger'];
$statusLabels = ['draft' => 'Draft', 'review' => 'Review', 'approved' => 'Approved', 'revision' => 'Revision'];
$tipeColors   = ['print' => 'primary', 'digital' => 'info'];
$tipeLabels   = ['print' => 'Print', 'digital' => 'Digital'];

$totalItems = count($rows);
$hasChart   = !empty($chartItems);
$hasInsight = !empty($insightLabels);
?>

<!-- Header & Navigator -->
<div class="d-flex justify-content-between align-items-center mb-4 fade-up" style="animation-delay:.05s">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-vector-pen me-2 text-primary"></i>Summary Bulanan — Creative &amp; Design</h4>
        <div class="text-muted small mt-1"><?= $bulanLabel ?> · <?= $totalItems ?> item · <?= $activeCount ?> aktif bulan ini</div>
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
        <div class="card h-100 border-primary-subtle fade-up" style="animation-delay:.14s">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <div class="rounded-2 p-1 bg-primary-subtle"><i class="bi bi-layers text-primary fs-5"></i></div>
                    <span class="small text-muted">Total Item</span>
                </div>
                <div class="fw-bold fs-4 text-primary" data-count="<?= $totalItems ?>"><?= $totalItems ?></div>
                <div class="small text-muted"><?= $activeCount ?> aktif bulan ini</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card h-100 border-danger-subtle fade-up" style="animation-delay:.24s">
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
        <div class="card h-100 border-warning-subtle fade-up" style="animation-delay:.34s">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <div class="rounded-2 p-1 bg-warning-subtle"><i class="bi bi-receipt text-warning fs-5"></i></div>
                    <span class="small text-muted">Realisasi Bulan Ini</span>
                </div>
                <div class="fw-bold fs-5 text-warning">Rp <?= number_format($totalRealisasi, 0, ',', '.') ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card h-100 border-info-subtle fade-up" style="animation-delay:.44s">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <div class="rounded-2 p-1 bg-info-subtle"><i class="bi bi-eye text-info fs-5"></i></div>
                    <span class="small text-muted">Reach Bulan Ini</span>
                </div>
                <div class="fw-bold fs-5 text-info" data-count="<?= $totalReach ?>"><?= number_format($totalReach) ?></div>
                <?php if ($totalImpressions > 0): ?>
                <div class="small text-muted">Impr <?= number_format($totalImpressions) ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Status & Tipe distribution -->
<?php if ($totalItems > 0): ?>
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body py-3">
                <div class="small fw-semibold text-muted mb-2"><i class="bi bi-circle-half me-1"></i>Status Item</div>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($statusLabels as $key => $label): ?>
                    <?php $cnt = $statusCounts[$key] ?? 0; if ($cnt === 0) continue; ?>
                    <span class="badge bg-<?= $statusColors[$key] ?>-subtle text-<?= $statusColors[$key] ?> border border-<?= $statusColors[$key] ?>-subtle px-2 py-1" style="font-size:.78rem">
                        <?= $label ?> <span class="fw-bold"><?= $cnt ?></span>
                    </span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body py-3">
                <div class="small fw-semibold text-muted mb-2"><i class="bi bi-grid me-1"></i>Tipe Item</div>
                <?php
                $tipeCounts = [];
                foreach ($rows as $r) { $t = $r['item']['tipe']; $tipeCounts[$t] = ($tipeCounts[$t] ?? 0) + 1; }
                ?>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($tipeCounts as $tipe => $cnt): ?>
                    <span class="badge bg-<?= $tipeColors[$tipe] ?? 'secondary' ?>-subtle text-<?= $tipeColors[$tipe] ?? 'secondary' ?> border border-<?= $tipeColors[$tipe] ?? 'secondary' ?>-subtle px-2 py-1" style="font-size:.78rem">
                        <?= $tipeLabels[$tipe] ?? ucfirst($tipe) ?> <span class="fw-bold"><?= $cnt ?></span>
                    </span>
                    <?php endforeach; ?>
                    <?php if ($totalFollowers > 0): ?>
                    <span class="ms-auto small text-muted align-self-center"><i class="bi bi-person-plus me-1"></i>+<?= number_format($totalFollowers) ?> followers</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (empty($rows)): ?>
<div class="card">
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-vector-pen display-4 d-block mb-2 opacity-25"></i>
        <p class="mb-0">Belum ada item Creative &amp; Design.</p>
    </div>
</div>
<?php else: ?>

<!-- Budget vs Realisasi Chart -->
<?php if ($hasChart): ?>
<div class="card mb-4">
    <div class="card-header"><span class="fw-semibold small"><i class="bi bi-bar-chart me-2"></i>Budget vs Realisasi (Kumulatif)</span></div>
    <div class="card-body">
        <canvas id="budgetRealChart" height="<?= max(60, count($chartItems) * 40) ?>"></canvas>
    </div>
</div>
<?php endif; ?>

<!-- Insight Chart -->
<?php if ($hasInsight): ?>
<div class="card mb-4">
    <div class="card-header"><span class="fw-semibold small"><i class="bi bi-graph-up me-2"></i>Reach &amp; Impressions — <?= $bulanLabel ?></span></div>
    <div class="card-body">
        <canvas id="insightChart" height="<?= max(60, count($insightLabels) * 40) ?>"></canvas>
    </div>
</div>
<?php endif; ?>

<!-- Detail Table — grouped by tipe -->
<?php
$rowsByTipe = [];
foreach ($rows as $r) { $rowsByTipe[$r['item']['tipe']][] = $r; }
$tipeOrder  = array_unique(array_merge(['print', 'digital'], array_keys($rowsByTipe)));
$tipeIcons  = ['print' => 'printer', 'digital' => 'phone'];
foreach ($tipeOrder as $tipe):
    if (empty($rowsByTipe[$tipe])) continue;
    $tipeRows  = $rowsByTipe[$tipe];
    $isDigital = ($tipe === 'digital');
    $grpBudget = array_sum(array_map(fn($r) => (int)$r['item']['budget'], $tipeRows));
    $grpReal   = array_sum(array_map(fn($r) => (int)($r['realMonth']['total'] ?? 0), $tipeRows));
    $grpReach  = array_sum(array_map(fn($r) => (int)($r['insMonth']['max_reach'] ?? 0), $tipeRows));
?>
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span class="fw-semibold small">
            <i class="bi bi-<?= $tipeIcons[$tipe] ?? 'grid' ?> me-2 text-<?= $tipeColors[$tipe] ?? 'secondary' ?>"></i>
            <?= $tipeLabels[$tipe] ?? ucfirst($tipe) ?>
        </span>
        <span class="badge bg-<?= $tipeColors[$tipe] ?? 'secondary' ?>-subtle text-<?= $tipeColors[$tipe] ?? 'secondary' ?>"><?= count($tipeRows) ?> item</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-sm mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">Nama Item</th>
                    <th>Status</th>
                    <th class="text-end">Budget</th>
                    <th class="text-end <?= $isDigital ? '' : 'pe-3' ?>">Realisasi Bulan Ini</th>
                    <?php if ($isDigital): ?>
                    <th class="text-end pe-3">Reach / Impr (Bln Ini)</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($tipeRows as $r):
                $item    = $r['item'];
                $budget  = (int)$item['budget'];
                $realMon = (int)($r['realMonth']['total']          ?? 0);
                $reach   = (int)($r['insMonth']['max_reach']       ?? 0);
                $impr    = (int)($r['insMonth']['max_impressions'] ?? 0);
                $link    = $item['_source'] === 's'
                    ? base_url('creative#item-' . $item['id'] . '-s')
                    : base_url('events/' . $item['event_id'] . '/creative');
            ?>
            <tr class="<?= $r['hasActivity'] ? '' : 'opacity-75' ?>">
                <td class="ps-3">
                    <a href="<?= $link ?>" class="fw-semibold text-decoration-none"><?= esc($item['nama']) ?></a>
                    <?php if ($r['hasActivity']): ?>
                    <span class="badge bg-success-subtle text-success ms-1" style="font-size:.6rem">aktif</span>
                    <?php endif; ?>
                    <?php if (!empty($item['event_name'])): ?>
                    <div class="small text-muted"><?= esc($item['event_name']) ?></div>
                    <?php endif; ?>
                    <?php if ($item['pic']): ?>
                    <div class="small text-muted"><i class="bi bi-person me-1"></i><?= esc($item['pic']) ?></div>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="badge bg-<?= $statusColors[$item['status']] ?? 'secondary' ?>-subtle text-<?= $statusColors[$item['status']] ?? 'secondary' ?>" style="font-size:.7rem">
                        <?= $statusLabels[$item['status']] ?? ucfirst($item['status']) ?>
                    </span>
                </td>
                <td class="text-end">
                    <?php if ($budget > 0): ?>
                    <span class="small fw-semibold text-danger">Rp <?= number_format($budget, 0, ',', '.') ?></span>
                    <?php else: ?>
                    <span class="small text-muted">—</span>
                    <?php endif; ?>
                </td>
                <td class="text-end <?= $isDigital ? '' : 'pe-3' ?>">
                    <?php if ($realMon > 0): ?>
                    <span class="small fw-semibold text-warning">Rp <?= number_format($realMon, 0, ',', '.') ?></span>
                    <?php else: ?>
                    <span class="small text-muted">—</span>
                    <?php endif; ?>
                </td>
                <?php if ($isDigital): ?>
                <td class="text-end pe-3">
                    <?php if ($reach > 0 || $impr > 0): ?>
                    <div class="small fw-semibold text-info"><?= number_format($reach) ?></div>
                    <?php if ($impr > 0): ?>
                    <div class="text-muted" style="font-size:.68rem">Impr <?= number_format($impr) ?></div>
                    <?php endif; ?>
                    <?php else: ?>
                    <span class="small text-muted">—</span>
                    <?php endif; ?>
                </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
            </tbody>
            <?php if ($grpBudget > 0 || $grpReal > 0): ?>
            <tfoot class="table-light fw-bold">
                <tr>
                    <td class="ps-3 small" colspan="2">Subtotal</td>
                    <td class="text-end small text-danger">Rp <?= number_format($grpBudget, 0, ',', '.') ?></td>
                    <td class="text-end small text-warning <?= $isDigital ? '' : 'pe-3' ?>">Rp <?= number_format($grpReal, 0, ',', '.') ?></td>
                    <?php if ($isDigital): ?>
                    <td class="text-end pe-3 small text-info"><?= $grpReach > 0 ? number_format($grpReach) : '—' ?></td>
                    <?php endif; ?>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>
<?php endforeach; ?>

<?php endif; ?>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
(function() {
    const dur = 900;
    document.querySelectorAll('[data-count]').forEach(el => {
        const target = parseInt(el.dataset.count) || 0;
        if (!target) return;
        const start = performance.now();
        function step(now) {
            const t = Math.min(1, (now - start) / dur);
            const ease = 1 - Math.pow(1 - t, 3);
            el.textContent = Math.round(ease * target).toLocaleString('id-ID');
            if (t < 1) requestAnimationFrame(step);
        }
        requestAnimationFrame(step);
    });
})();
</script>
<?php if ($hasChart): ?>
<script>
new Chart(document.getElementById('budgetRealChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($chartItems) ?>,
        datasets: [
            {
                label: 'Budget',
                data: <?= json_encode($chartBudget) ?>,
                backgroundColor: 'rgba(220,53,69,.7)',
                borderRadius: 3,
            },
            {
                label: 'Realisasi Bulan Ini',
                data: <?= json_encode($chartRealMonth) ?>,
                backgroundColor: 'rgba(255,193,7,.7)',
                borderRadius: 3,
            },
        ]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        animation: {
            duration: 700,
            easing: 'easeOutQuart',
            delay: ctx => ctx.type === 'data' ? ctx.dataIndex * 40 : 0,
        },
        scales: { x: { beginAtZero: true, ticks: { callback: v => 'Rp ' + v.toLocaleString('id-ID') } } },
        plugins: {
            legend: { position: 'top', labels: { boxWidth: 12, font: { size: 11 } } },
            tooltip: { callbacks: { label: ctx => ctx.dataset.label + ': Rp ' + ctx.raw.toLocaleString('id-ID') } }
        }
    }
});
</script>
<?php endif; ?>
<?php if ($hasInsight): ?>
<script>
new Chart(document.getElementById('insightChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($insightLabels) ?>,
        datasets: [
            {
                label: 'Reach',
                data: <?= json_encode($insightReach) ?>,
                backgroundColor: 'rgba(13,202,240,.7)',
                borderRadius: 3,
            },
            {
                label: 'Impressions',
                data: <?= json_encode($insightImpr) ?>,
                backgroundColor: 'rgba(99,102,241,.5)',
                borderRadius: 3,
            },
        ]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        animation: {
            duration: 700,
            easing: 'easeOutQuart',
            delay: ctx => ctx.type === 'data' ? ctx.dataIndex * 40 : 0,
        },
        scales: { x: { beginAtZero: true, ticks: { callback: v => v.toLocaleString('id-ID') } } },
        plugins: {
            legend: { position: 'top', labels: { boxWidth: 12, font: { size: 11 } } },
            tooltip: { callbacks: { label: ctx => ctx.dataset.label + ': ' + ctx.raw.toLocaleString('id-ID') } }
        }
    }
});
</script>
<?php endif; ?>
<?= $this->endSection() ?>
