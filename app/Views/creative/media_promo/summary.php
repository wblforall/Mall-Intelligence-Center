<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$idBulan    = ['January'=>'Januari','February'=>'Februari','March'=>'Maret','April'=>'April',
               'May'=>'Mei','June'=>'Juni','July'=>'Juli','August'=>'Agustus',
               'September'=>'September','October'=>'Oktober','November'=>'November','December'=>'Desember'];
$bulanDt    = \DateTime::createFromFormat('Y-m', $bulan);
$bulanLabel = strtr($bulanDt->format('F Y'), $idBulan);

$tipeLabel = ['t_banner'=>'T-Banner','hanging'=>'Hanging','sticker_lift'=>'Sticker Lift','totem_stainless'=>'Totem Stainless','digital'=>'Digital'];
$tipeBadge = ['t_banner'=>'primary','hanging'=>'info text-dark','sticker_lift'=>'warning text-dark','totem_stainless'=>'secondary','digital'=>'dark'];
$tipeIcon  = ['t_banner'=>'flag','hanging'=>'image','sticker_lift'=>'stickies','totem_stainless'=>'reception-4','digital'=>'display'];

$totalRequest = array_sum($statusCounts);
$totalActive  = ($statusCounts['approved'] ?? 0) + ($statusCounts['done'] ?? 0);

// Occupancy chart data (top 20 by pct)
$chartSpots = array_slice($spotOccupancy, 0, 20);
$chartLabels = array_map(fn($o) => $o['spot']['kode'], $chartSpots);
$chartPct    = array_map(fn($o) => $o['pct'], $chartSpots);
$chartColors = array_map(function($o) {
    $p = $o['pct'];
    if ($p >= 80) return 'rgba(220,53,69,.75)';
    if ($p >= 50) return 'rgba(255,193,7,.75)';
    return 'rgba(25,135,84,.75)';
}, $chartSpots);
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-graph-up-arrow me-2 text-primary"></i>Summary — Media Promo</h4>
        <div class="text-muted small mt-1"><?= $bulanLabel ?> · <?= $totalRequest ?> request · <?= count($spotOccupancy) ?> titik aktif</div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="?bulan=<?= $prevBulan ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-chevron-left"></i></a>
        <form method="GET">
            <input type="month" name="bulan" value="<?= $bulan ?>" class="form-control form-control-sm" style="width:150px" onchange="this.form.submit()">
        </form>
        <a href="?bulan=<?= $nextBulan ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-chevron-right"></i></a>
        <a href="<?= base_url('creative/media-promo') ?>" class="btn btn-sm btn-outline-secondary ms-2">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
    </div>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card h-100 border-primary-subtle">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <div class="rounded-2 p-1 bg-primary-subtle"><i class="bi bi-inbox text-primary fs-5"></i></div>
                    <span class="small text-muted">Total Request</span>
                </div>
                <div class="fw-bold fs-3 text-primary"><?= $totalRequest ?></div>
                <div class="small text-muted">bulan <?= $bulanLabel ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card h-100 border-success-subtle">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <div class="rounded-2 p-1 bg-success-subtle"><i class="bi bi-check-circle text-success fs-5"></i></div>
                    <span class="small text-muted">Approved / Done</span>
                </div>
                <div class="fw-bold fs-3 text-success"><?= $totalActive ?></div>
                <div class="small text-muted"><?= $statusCounts['approved'] ?> approved · <?= $statusCounts['done'] ?> done</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card h-100 border-warning-subtle">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <div class="rounded-2 p-1 bg-warning-subtle"><i class="bi bi-hourglass-split text-warning fs-5"></i></div>
                    <span class="small text-muted">Pending Approval</span>
                </div>
                <div class="fw-bold fs-3 text-warning"><?= $statusCounts['pending'] ?></div>
                <div class="small text-muted">menunggu approval</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card h-100 border-danger-subtle">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <div class="rounded-2 p-1 bg-danger-subtle"><i class="bi bi-x-circle text-danger fs-5"></i></div>
                    <span class="small text-muted">Ditolak</span>
                </div>
                <div class="fw-bold fs-3 text-danger"><?= $statusCounts['rejected'] ?></div>
                <div class="small text-muted"><?= $statusCounts['draft'] ?> masih draft</div>
            </div>
        </div>
    </div>
</div>

<?php if ($totalRequest > 0): ?>

<?php
$sumberLabel = ['internal'=>'Internal Manajemen','tenant'=>'Tenant Mall','external'=>'External Client'];
$sumberBadge = ['internal'=>'secondary','tenant'=>'info text-dark','external'=>'warning text-dark'];
?>
<!-- Sumber & Berbayar -->
<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body py-3">
                <div class="small fw-semibold text-muted mb-2"><i class="bi bi-person-badge me-1"></i>Sumber Materi</div>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($sumberCounts as $src => $cnt): if (!$cnt) continue; ?>
                    <span class="badge bg-<?= $sumberBadge[$src] ?> px-2 py-1" style="font-size:.78rem">
                        <?= $sumberLabel[$src] ?> <strong><?= $cnt ?></strong>
                    </span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body py-3">
                <div class="small fw-semibold text-muted mb-2"><i class="bi bi-cash-coin me-1"></i>Status Biaya</div>
                <div class="d-flex flex-wrap gap-3 align-items-center">
                    <span class="badge bg-success px-2 py-1" style="font-size:.78rem">
                        Berbayar <strong><?= $berbayarCount ?></strong>
                    </span>
                    <span class="badge border text-muted px-2 py-1" style="font-size:.78rem">
                        Gratis <strong><?= $totalRequest - $berbayarCount ?></strong>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tipe & Dept Distribution -->
<div class="row g-3 mb-4">
    <div class="col-md-5">
        <div class="card h-100">
            <div class="card-body py-3">
                <div class="small fw-semibold text-muted mb-2"><i class="bi bi-grid me-1"></i>Request per Tipe Media</div>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($tipeCounts as $tipe => $cnt): ?>
                    <span class="badge bg-<?= $tipeBadge[$tipe] ?? 'secondary' ?> px-2 py-1" style="font-size:.78rem">
                        <i class="bi bi-<?= $tipeIcon[$tipe] ?? 'square' ?> me-1"></i><?= $tipeLabel[$tipe] ?? $tipe ?> <strong><?= $cnt ?></strong>
                    </span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-7">
        <div class="card h-100">
            <div class="card-body py-3">
                <div class="small fw-semibold text-muted mb-2"><i class="bi bi-building me-1"></i>Request per Departemen</div>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($deptCounts as $dept => $cnt): ?>
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1" style="font-size:.78rem">
                        <?= esc($dept) ?> <strong><?= $cnt ?></strong>
                    </span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

<!-- Occupancy Chart -->
<?php if (! empty($chartSpots)): ?>
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span class="fw-semibold small"><i class="bi bi-bar-chart-horizontal me-2"></i>Occupancy Titik Media — <?= $bulanLabel ?></span>
        <span class="small text-muted">Berdasarkan booking approved/done</span>
    </div>
    <div class="card-body">
        <canvas id="occupancyChart" height="<?= max(80, count($chartSpots) * 28) ?>"></canvas>
    </div>
</div>
<?php endif; ?>

<!-- Detail Table -->
<?php if (! empty($spotOccupancy)): ?>
<div class="card">
    <div class="card-header"><span class="fw-semibold small"><i class="bi bi-table me-2"></i>Detail Occupancy per Titik</span></div>
    <div class="card-body p-0">
        <table class="table table-hover table-sm mb-0 align-middle small">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">Kode</th>
                    <th>Nama</th>
                    <th>Tipe</th>
                    <th>Area</th>
                    <th class="text-center">Hari Terpakai</th>
                    <th class="text-center">Kapasitas</th>
                    <th style="width:140px">Occupancy</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($spotOccupancy as $o):
                $s   = $o['spot'];
                $pct = $o['pct'];
                $barColor = $pct >= 80 ? 'danger' : ($pct >= 50 ? 'warning' : 'success');
                $isDigital = $s['tipe'] === 'digital';
            ?>
            <tr>
                <td class="ps-3 fw-semibold font-monospace"><?= esc($s['kode']) ?></td>
                <td><?= esc($s['nama']) ?></td>
                <td>
                    <span class="badge bg-<?= $tipeBadge[$s['tipe']] ?? 'secondary' ?>" style="font-size:.65rem">
                        <?= $tipeLabel[$s['tipe']] ?? esc($s['tipe']) ?>
                    </span>
                </td>
                <td class="text-muted"><?= esc($s['area'] ?? '—') ?></td>
                <td class="text-center">
                    <?= $o['occupied'] ?>
                    <?php if ($isDigital): ?>
                    <span class="text-muted" style="font-size:.7rem">slot-hari</span>
                    <?php endif; ?>
                </td>
                <td class="text-center text-muted">
                    <?= $o['capacity'] ?>
                    <?php if ($isDigital): ?>
                    <span style="font-size:.7rem">(<?= $s['total_slots'] ?> slot)</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <div class="progress flex-grow-1" style="height:8px">
                            <div class="progress-bar bg-<?= $barColor ?>" style="width:<?= $pct ?>%"></div>
                        </div>
                        <span class="fw-semibold text-<?= $barColor ?>" style="min-width:38px;font-size:.78rem"><?= $pct ?>%</span>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php else: ?>
<div class="card"><div class="card-body text-center text-muted py-5">Belum ada data untuk bulan ini.</div></div>
<?php endif; ?>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<?php if (! empty($chartSpots)): ?>
<script>
new Chart(document.getElementById('occupancyChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($chartLabels, JSON_UNESCAPED_UNICODE) ?>,
        datasets: [{
            label: 'Occupancy (%)',
            data: <?= json_encode($chartPct) ?>,
            backgroundColor: <?= json_encode($chartColors) ?>,
            borderRadius: 4,
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        animation: { duration: 600, easing: 'easeOutQuart' },
        scales: {
            x: {
                beginAtZero: true,
                max: 100,
                ticks: { callback: v => v + '%' }
            },
            y: { ticks: { font: { family: 'monospace', size: 11 } } }
        },
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: ctx => ' Occupancy: ' + ctx.raw + '%'
                }
            }
        }
    }
});
</script>
<?php endif; ?>
<?= $this->endSection() ?>
