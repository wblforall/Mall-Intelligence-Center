<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
// Status → warna chart (palet tervalidasi CVD utk light & dark surface).
// Kuning & hijau light <3:1 kontras → mitigasi: legend + tooltip + tabel freshness (relief rule).
$statusMeta = [
    'on_track'  => ['label' => 'On Track',   'light' => '#1baf7a', 'dark' => '#199e70'],
    'at_risk'   => ['label' => 'At Risk',    'light' => '#eda100', 'dark' => '#c98500'],
    'delayed'   => ['label' => 'Delayed',    'light' => '#d03b3b', 'dark' => '#d03b3b'],
    'done'      => ['label' => 'Selesai',    'light' => '#2a78d6', 'dark' => '#3987e5'],
    'cancelled' => ['label' => 'Dibatalkan', 'light' => '#55544f', 'dark' => '#63625a'],
    'no_update' => ['label' => 'Belum Update', 'light' => '#a3a29b', 'dark' => '#9a998f'],
];
$fmtDate = fn($d) => $d ? date('d M Y', strtotime($d)) : null;
?>

<div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-speedometer2 me-2"></i>Dashboard Progress Report</h4>
        <small class="text-muted"><?= $scopedDiv ? esc($scopedDiv) : 'Semua divisi' ?></small>
    </div>
    <div class="d-flex gap-2 align-items-center flex-wrap">
        <?php if (! empty($divisis)): ?>
        <form method="GET" action="<?= base_url('work-report/dashboard') ?>" class="d-flex gap-2">
            <select name="divisi_id" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">Semua Divisi</option>
                <?php foreach ($divisis as $dv): ?>
                <option value="<?= $dv['id'] ?>" <?= (int) $filterDiv === (int) $dv['id'] ? 'selected' : '' ?>><?= esc($dv['nama']) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <?php endif; ?>
        <a href="<?= base_url('work-report') ?>" class="btn btn-outline-secondary btn-sm text-nowrap">
            <i class="bi bi-arrow-left me-1"></i>Ke Daftar
        </a>
    </div>
</div>

<!-- KPI row -->
<?php
$tiles = [
    ['key' => 'total',     'label' => 'Program Aktif',      'icon' => 'bi-kanban',                'accent' => null],
    ['key' => 'on_track',  'label' => 'On Track',           'icon' => 'bi-check-circle',          'accent' => 'on_track'],
    ['key' => 'at_risk',   'label' => 'At Risk',            'icon' => 'bi-exclamation-triangle',  'accent' => 'at_risk'],
    ['key' => 'delayed',   'label' => 'Delayed',            'icon' => 'bi-x-circle',              'accent' => 'delayed'],
    ['key' => 'overdue',   'label' => 'Lewat Target',       'icon' => 'bi-calendar-x',            'accent' => 'delayed'],
    ['key' => 'stale7',    'label' => 'Tak Update >7 Hari', 'icon' => 'bi-hourglass-split',       'accent' => 'at_risk'],
    ['key' => 'no_update', 'label' => 'Belum Pernah Update','icon' => 'bi-question-circle',       'accent' => 'no_update'],
];
?>
<div class="row g-2 mb-4">
<?php foreach ($tiles as $t): ?>
    <div class="col-6 col-md-4 col-xl">
        <div class="card h-100">
            <div class="card-body py-2 px-3">
                <div class="d-flex align-items-center gap-1 text-muted" style="font-size:.68rem">
                    <i class="bi <?= $t['icon'] ?>"></i> <?= $t['label'] ?>
                </div>
                <div class="fw-bold d-flex align-items-center gap-2" style="font-size:1.6rem">
                    <?php if ($t['accent']): ?>
                    <span class="viz-status-dot" data-status="<?= $t['accent'] ?>" style="width:.55rem;height:.55rem;border-radius:50%;display:inline-block"></span>
                    <?php endif; ?>
                    <?= $stat[$t['key']] ?>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>

<div class="row g-3 mb-4">
    <!-- Status per divisi -->
    <div class="col-12 col-lg-6">
        <div class="card h-100">
            <div class="card-header py-2"><span class="fw-semibold small"><i class="bi bi-layers me-2 text-muted"></i>Status Program per Divisi</span></div>
            <div class="card-body">
                <?php if (empty($byDivisi)): ?>
                <div class="text-center text-muted py-4" style="font-size:.8rem">Belum ada program kerja aktif.</div>
                <?php else: ?>
                <div style="height:<?= max(180, count($byDivisi) * 44 + 70) ?>px"><canvas id="chartDivisi"></canvas></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <!-- Tren mingguan -->
    <div class="col-12 col-lg-6">
        <div class="card h-100">
            <div class="card-header py-2"><span class="fw-semibold small"><i class="bi bi-graph-up me-2 text-muted"></i>Update Dilaporkan per Minggu <span class="text-muted fw-normal">(12 minggu terakhir)</span></span></div>
            <div class="card-body">
                <div style="height:260px"><canvas id="chartTren"></canvas></div>
            </div>
        </div>
    </div>
</div>

<!-- Freshness per dept -->
<div class="card">
<div class="card-header py-2"><span class="fw-semibold small"><i class="bi bi-clock-history me-2 text-muted"></i>Update Terakhir per Departemen</span></div>
<div class="table-responsive">
<table class="table table-sm align-middle mb-0" style="font-size:.78rem">
<thead>
<tr>
    <th>Departemen</th>
    <th>Divisi</th>
    <th class="text-center" style="width:12%">Program Aktif</th>
    <th class="text-center" style="width:12%">Lewat Target</th>
    <th style="width:18%">Update Terakhir</th>
    <th style="width:16%">Keaktifan</th>
</tr>
</thead>
<tbody>
<?php if (empty($byDept)): ?>
<tr><td colspan="6" class="text-center text-muted py-4">Belum ada program kerja aktif.</td></tr>
<?php endif; ?>
<?php foreach ($byDept as $row):
    $days = $row['last_update'] ? floor((time() - strtotime($row['last_update'])) / 86400) : null;
    if ($days === null)     { $fr = ['Belum pernah', 'bg-secondary'];          }
    elseif ($days <= 7)     { $fr = ['Minggu ini', 'bg-success'];              }
    elseif ($days <= 14)    { $fr = [$days . ' hari lalu', 'bg-warning text-dark']; }
    else                    { $fr = [$days . ' hari lalu', 'bg-danger'];       }
?>
<tr>
    <td class="fw-semibold"><?= esc($row['dept']) ?></td>
    <td class="text-muted"><?= esc($row['divisi']) ?></td>
    <td class="text-center"><?= $row['total'] ?></td>
    <td class="text-center"><?= $row['overdue'] ? '<span class="badge bg-danger" style="font-size:.65rem">' . $row['overdue'] . '</span>' : '—' ?></td>
    <td><?= $fmtDate($row['last_update']) ?? '<span class="text-muted">—</span>' ?></td>
    <td><span class="badge <?= $fr[1] ?>" style="font-size:.65rem"><?= $fr[0] ?></span></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const dark   = document.documentElement.getAttribute('data-bs-theme') !== 'light';
    const meta   = <?= json_encode(array_map(fn($m) => ['label' => $m['label'], 'color' => $m['light'], 'colorDark' => $m['dark']], $statusMeta)) ?>;
    const color  = k => dark ? meta[k].colorDark : meta[k].color;
    const surface = dark ? '#0e1a2a' : '#ffffff';
    const inkMuted = dark ? 'rgba(221,232,248,.65)' : 'rgba(33,37,41,.65)';
    const gridCol  = dark ? 'rgba(255,255,255,.07)' : 'rgba(0,0,0,.06)';
    const statuses = ['on_track', 'at_risk', 'delayed', 'done', 'cancelled', 'no_update'];

    // Titik warna di KPI tiles mengikuti theme
    document.querySelectorAll('.viz-status-dot').forEach(el => {
        el.style.background = color(el.dataset.status);
    });

    const baseOpts = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom', labels: { color: inkMuted, usePointStyle: true, pointStyle: 'circle', boxWidth: 7, boxHeight: 7, font: { size: 10 } } },
            tooltip: { mode: 'index' },
        },
    };

    // ── Status per divisi (stacked horizontal) ─────────────────────────
    <?php if (! empty($byDivisi)): ?>
    new Chart(document.getElementById('chartDivisi'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_keys($byDivisi)) ?>,
            datasets: statuses.map(s => ({
                label: meta[s].label,
                data: <?= json_encode(array_values($byDivisi)) ?>.map(r => r[s] || 0),
                backgroundColor: color(s),
                borderColor: surface,   // 2px gap antar segmen
                borderWidth: 2,
                borderRadius: 4,
                borderSkipped: false,
            })),
        },
        options: {
            ...baseOpts,
            indexAxis: 'y',
            scales: {
                x: { stacked: true, ticks: { color: inkMuted, precision: 0, font: { size: 10 } }, grid: { color: gridCol } },
                y: { stacked: true, ticks: { color: inkMuted, font: { size: 10 } }, grid: { display: false } },
            },
        },
    });
    <?php endif; ?>

    // ── Tren update mingguan (stacked kolom) ───────────────────────────
    const weeks = <?= json_encode($weeks) ?>;
    new Chart(document.getElementById('chartTren'), {
        type: 'bar',
        data: {
            labels: weeks.map(w => w.label),
            datasets: ['on_track', 'at_risk', 'delayed', 'done', 'cancelled'].map(s => ({
                label: meta[s].label,
                data: weeks.map(w => w.data[s] || 0),
                backgroundColor: color(s),
                borderColor: surface,
                borderWidth: 2,
                borderRadius: 4,
                borderSkipped: false,
            })),
        },
        options: {
            ...baseOpts,
            scales: {
                x: { stacked: true, ticks: { color: inkMuted, font: { size: 10 } }, grid: { display: false } },
                y: { stacked: true, ticks: { color: inkMuted, precision: 0, font: { size: 10 } }, grid: { color: gridCol } },
            },
        },
    });
});
</script>

<?= $this->endSection() ?>
