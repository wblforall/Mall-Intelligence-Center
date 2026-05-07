<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<style>
.fade-up { opacity:0; transform:translateY(14px); animation:fadeUpVmM .55s cubic-bezier(.22,.68,0,1.2) forwards; }
@keyframes fadeUpVmM { to { opacity:1; transform:translateY(0); } }
</style>
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php
$idBulan    = ['January'=>'Januari','February'=>'Februari','March'=>'Maret','April'=>'April',
               'May'=>'Mei','June'=>'Juni','July'=>'Juli','August'=>'Agustus',
               'September'=>'September','October'=>'Oktober','November'=>'November','December'=>'Desember'];
$bulanDt    = \DateTime::createFromFormat('Y-m', $bulan);
$bulanLabel = strtr($bulanDt->format('F Y'), $idBulan);

$totalItems = count($rows);
$hasChart   = !empty($chartItems);
?>

<!-- Header & Navigator -->
<div class="d-flex justify-content-between align-items-center mb-4 fade-up" style="animation-delay:.05s">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-palette-fill me-2 text-primary"></i>Summary Bulanan — Dekorasi &amp; VM</h4>
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
    <div class="col-6 col-md-3 fade-up" style="animation-delay:.12s">
        <div class="card h-100 border-primary-subtle">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <div class="rounded-2 p-1 bg-primary-subtle"><i class="bi bi-palette text-primary fs-5"></i></div>
                    <span class="small text-muted">Total Item</span>
                </div>
                <div class="fw-bold fs-4 text-primary" data-count="<?= $totalItems ?>"><?= $totalItems ?></div>
                <div class="small text-muted"><?= $activeCount ?> aktif bulan ini</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 fade-up" style="animation-delay:.22s">
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
    <div class="col-6 col-md-3 fade-up" style="animation-delay:.32s">
        <div class="card h-100 border-warning-subtle">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <div class="rounded-2 p-1 bg-warning-subtle"><i class="bi bi-receipt text-warning fs-5"></i></div>
                    <span class="small text-muted">Realisasi Bulan Ini</span>
                </div>
                <div class="fw-bold fs-5 text-warning">Rp <?= number_format($totalRealMonth, 0, ',', '.') ?></div>
            </div>
        </div>
    </div>
</div>

<?php if (empty($rows)): ?>
<div class="card">
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-palette display-4 d-block mb-2 opacity-25"></i>
        <p class="mb-0">Belum ada item Dekorasi &amp; VM.</p>
    </div>
</div>
<?php else: ?>

<!-- Chart -->
<?php if ($hasChart): ?>
<div class="card mb-4">
    <div class="card-header"><span class="fw-semibold small"><i class="bi bi-bar-chart me-2"></i>Budget vs Realisasi Kumulatif</span></div>
    <div class="card-body">
        <canvas id="budgetRealChart" height="<?= max(60, count($chartItems) * 40) ?>"></canvas>
    </div>
</div>
<?php endif; ?>

<!-- Detail Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span class="fw-semibold small"><i class="bi bi-table me-2"></i>Detail Item</span>
        <span class="badge bg-primary-subtle text-primary"><?= $totalItems ?> item</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-sm mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">Nama Item</th>
                    <th>Sumber</th>
                    <th>Deadline</th>
                    <th class="text-end">Budget</th>
                    <th class="text-end pe-3">Realisasi Bulan Ini</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r):
                $item   = $r['item'];
                $budget = (int)$item['budget'];
                $rMon   = (int)($r['realMonth']['total'] ?? 0);

                $link = $item['source'] === 'standalone'
                    ? base_url('vm#item-' . $item['id'])
                    : base_url('events/' . ($item['event_id'] ?? '') . '/vm');
            ?>
            <tr class="<?= $r['hasActivity'] ? '' : 'opacity-75' ?>">
                <td class="ps-3">
                    <a href="<?= $link ?>" class="fw-semibold text-decoration-none"><?= esc($item['nama_item']) ?></a>
                    <?php if ($r['hasActivity']): ?>
                    <span class="badge bg-success-subtle text-success ms-1" style="font-size:.6rem">aktif</span>
                    <?php endif; ?>
                    <?php if ($item['deskripsi_referensi']): ?>
                    <div class="small text-muted text-truncate" style="max-width:260px"><?= esc($item['deskripsi_referensi']) ?></div>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($item['source'] === 'event'): ?>
                    <span class="badge bg-primary-subtle text-primary" style="font-size:.7rem">
                        <i class="bi bi-calendar-event me-1"></i><?= esc($item['event_name'] ?? '') ?>
                    </span>
                    <?php else: ?>
                    <span class="badge bg-secondary-subtle text-secondary" style="font-size:.7rem">Standalone</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($item['tanggal_deadline']): ?>
                    <?php $over = strtotime($item['tanggal_deadline']) < strtotime(date('Y-m-d')); ?>
                    <div class="small <?= $over ? 'text-danger fw-semibold' : 'text-muted' ?> text-nowrap">
                        <?= date('d M Y', strtotime($item['tanggal_deadline'])) ?>
                    </div>
                    <?php if ($over): ?>
                    <span class="badge bg-danger" style="font-size:.58rem"><i class="bi bi-exclamation-triangle-fill me-1"></i>Lewat</span>
                    <?php endif; ?>
                    <?php else: ?>
                    <span class="small text-muted">—</span>
                    <?php endif; ?>
                </td>
                <td class="text-end">
                    <?php if ($budget > 0): ?>
                    <span class="small fw-semibold text-danger">Rp <?= number_format($budget, 0, ',', '.') ?></span>
                    <?php else: ?>
                    <span class="small text-muted">—</span>
                    <?php endif; ?>
                </td>
                <td class="text-end pe-3">
                    <?php if ($rMon > 0): ?>
                    <span class="small fw-semibold text-warning">Rp <?= number_format($rMon, 0, ',', '.') ?></span>
                    <?php else: ?>
                    <span class="small text-muted">—</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
            <?php if ($totalBudget > 0 || $totalRealMonth > 0): ?>
            <tfoot class="table-light fw-bold">
                <tr>
                    <td class="ps-3 small" colspan="3">Total</td>
                    <td class="text-end small text-danger">Rp <?= number_format($totalBudget, 0, ',', '.') ?></td>
                    <td class="text-end pe-3 small text-warning">Rp <?= number_format($totalRealMonth, 0, ',', '.') ?></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>

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
        animation: {
            duration: 700,
            easing: 'easeOutQuart',
            delay: ctx => ctx.type === 'data' ? ctx.dataIndex * 40 : 0,
        },
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
