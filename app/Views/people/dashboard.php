<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<style>
@keyframes fadeUp { from { opacity:0; transform:translateY(14px); } to { opacity:1; transform:none; } }
.anim-fade-up { animation: fadeUp .4s cubic-bezier(.22,.68,0,1.1) both; }
.kpi-people { border-radius:.85rem; padding:1.1rem 1.25rem; }
.kpi-people .kpi-val { font-size:2rem; font-weight:700; line-height:1.1; }
.kpi-people .kpi-lbl { font-size:.72rem; opacity:.75; font-weight:500; }
.kpi-people .kpi-sub { font-size:.68rem; margin-top:.2rem; }
.section-title { font-size:.75rem; font-weight:700; text-transform:uppercase; letter-spacing:.08em; opacity:.5; margin-bottom:.65rem; }
.training-item { padding:.6rem .9rem; border-radius:.55rem; margin-bottom:.4rem; font-size:.83rem; }
.budget-bar { height:7px; border-radius:4px; background:var(--bs-secondary-bg); }
.budget-bar-fill { height:100%; border-radius:4px; }
.cert-row-urgent { border-left:3px solid var(--bs-danger) !important; }
.cert-row-warn   { border-left:3px solid var(--bs-warning) !important; }
</style>
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php
$aktif   = (int)($empMap['aktif']      ?? 0);
$resign  = (int)($empMap['resign']     ?? 0);
$cuti    = (int)($empMap['cuti_panjang'] ?? 0);
$pensiun = (int)($empMap['pensiun']    ?? 0);
$totalEmp = $aktif + $resign + $cuti + $pensiun;

$budgetPct = $totalAnggaran > 0 ? round($totalRealisasi / $totalAnggaran * 100, 1) : null;
?>

<!-- Header -->
<div class="d-flex align-items-center justify-content-between mb-4 anim-fade-up" style="animation-delay:.05s">
    <div>
        <h4 class="fw-bold mb-0">People Development</h4>
        <div class="text-muted small">Dashboard ringkasan — <?= date('d F Y') ?></div>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= base_url('people/employees') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-person-vcard me-1"></i>Karyawan</a>
        <a href="<?= base_url('people/training') ?>"  class="btn btn-sm btn-outline-secondary"><i class="bi bi-mortarboard me-1"></i>Training</a>
        <a href="<?= base_url('people/tna') ?>"        class="btn btn-sm btn-outline-secondary"><i class="bi bi-clipboard2-pulse me-1"></i>TNA</a>
    </div>
</div>

<!-- KPI Strip -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg anim-fade-up" style="animation-delay:.08s">
        <div class="card kpi-people">
            <div class="kpi-lbl"><i class="bi bi-people me-1"></i>Karyawan Aktif</div>
            <div class="kpi-val"><?= $aktif ?></div>
            <div class="kpi-sub text-muted"><?= $totalEmp ?> total terdaftar</div>
        </div>
    </div>
    <div class="col-6 col-lg anim-fade-up" style="animation-delay:.11s">
        <div class="card kpi-people">
            <div class="kpi-lbl"><i class="bi bi-mortarboard me-1"></i>Training Bulan Ini</div>
            <div class="kpi-val"><?= $trainingThisMonth ?></div>
            <div class="kpi-sub text-muted"><?= date('F Y') ?></div>
        </div>
    </div>
    <div class="col-6 col-lg anim-fade-up" style="animation-delay:.14s">
        <div class="card kpi-people <?= $certExpiring30 > 0 ? 'border-danger' : '' ?>">
            <div class="kpi-lbl"><i class="bi bi-shield-exclamation me-1"></i>Sertifikat Expire</div>
            <div class="kpi-val <?= $certExpiring30 > 0 ? 'text-danger' : '' ?>"><?= count($certExpiring) ?></div>
            <div class="kpi-sub <?= $certExpiring30 > 0 ? 'text-danger' : 'text-muted' ?>">
                <?= $certExpiring30 > 0 ? $certExpiring30 . ' dalam 30 hari' : 'dalam 60 hari' ?>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg anim-fade-up" style="animation-delay:.17s">
        <div class="card kpi-people">
            <div class="kpi-lbl"><i class="bi bi-clipboard2-pulse me-1"></i>Periode TNA Aktif</div>
            <div class="kpi-val"><?= $activeTnaPeriods ?></div>
            <div class="kpi-sub text-muted"><a href="<?= base_url('people/tna') ?>" class="text-muted">Lihat semua &rsaquo;</a></div>
        </div>
    </div>
    <div class="col-6 col-lg anim-fade-up" style="animation-delay:.2s">
        <div class="card kpi-people">
            <div class="kpi-lbl"><i class="bi bi-wallet2 me-1"></i>Budget <?= $tahun ?> Terpakai</div>
            <div class="kpi-val <?= $budgetPct > 90 ? 'text-danger' : '' ?>"><?= $budgetPct !== null ? $budgetPct . '%' : '—' ?></div>
            <div class="kpi-sub text-muted">dari Rp <?= $totalAnggaran > 0 ? number_format($totalAnggaran / 1e6, 1) . 'jt' : '—' ?></div>
        </div>
    </div>
</div>

<!-- Row 2: TNA Gaps + Upcoming Training -->
<div class="row g-3 mb-3">
    <!-- TNA Gap Chart -->
    <div class="col-lg-7 anim-fade-up" style="animation-delay:.22s">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div>
                    <div class="section-title mb-0">TNA Gap Terbesar</div>
                    <?php if ($latestPeriod): ?>
                    <div class="text-muted" style="font-size:.73rem"><?= esc($latestPeriod['nama']) ?></div>
                    <?php endif; ?>
                </div>
                <a href="<?= base_url('people/tna') ?>" class="btn btn-xs btn-sm btn-outline-secondary py-0 px-2" style="font-size:.72rem">Buka TNA</a>
            </div>
            <div class="card-body">
                <?php if (empty($tnaGaps)): ?>
                <div class="text-center text-muted py-4 small">
                    <i class="bi bi-bar-chart" style="font-size:2rem;opacity:.3"></i>
                    <p class="mt-2 mb-0">Belum ada data TNA yang disubmit.</p>
                </div>
                <?php else: ?>
                <canvas id="gapChart" style="max-height:280px"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Upcoming Training -->
    <div class="col-lg-5 anim-fade-up" style="animation-delay:.25s">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div class="section-title mb-0">Training Terdekat</div>
                <a href="<?= base_url('people/training') ?>" class="btn btn-xs btn-sm btn-outline-secondary py-0 px-2" style="font-size:.72rem">Semua</a>
            </div>
            <div class="card-body p-2">
                <?php if (empty($upcomingTraining)): ?>
                <div class="text-center text-muted py-4 small">Tidak ada training terjadwal.</div>
                <?php else: ?>
                <?php foreach ($upcomingTraining as $t):
                    $isOngoing = $t['status'] === 'ongoing';
                ?>
                <a href="<?= base_url('people/training/' . $t['id']) ?>" class="d-block text-decoration-none training-item border <?= $isOngoing ? 'border-success bg-success bg-opacity-10' : '' ?>">
                    <div class="d-flex align-items-start justify-content-between gap-2">
                        <div class="fw-medium" style="font-size:.83rem"><?= esc($t['nama']) ?></div>
                        <?php if ($isOngoing): ?>
                        <span class="badge bg-success flex-shrink-0" style="font-size:.65rem">Berjalan</span>
                        <?php else: ?>
                        <span class="badge bg-primary flex-shrink-0" style="font-size:.65rem">Dijadwalkan</span>
                        <?php endif; ?>
                    </div>
                    <div class="text-muted mt-1" style="font-size:.72rem">
                        <?= $t['tanggal_mulai'] ? date('d M Y', strtotime($t['tanggal_mulai'])) : 'Tanggal TBD' ?>
                        · <?= $t['peserta_count'] ?> peserta
                        <?php if ($t['tipe'] === 'eksternal'): ?>
                        · <span class="text-primary">Eksternal</span>
                        <?php endif; ?>
                    </div>
                </a>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Row 3: Cert expiry + Budget bars -->
<div class="row g-3 mb-3">
    <!-- Sertifikat Expiring -->
    <div class="col-lg-7 anim-fade-up" style="animation-delay:.28s">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div class="section-title mb-0">Sertifikat Akan Kadaluarsa <span class="badge bg-danger ms-1"><?= count($certExpiring) ?></span></div>
                <a href="<?= base_url('people/employees') ?>" class="btn btn-xs btn-sm btn-outline-secondary py-0 px-2" style="font-size:.72rem">Lihat Karyawan</a>
            </div>
            <?php if (empty($certExpiring)): ?>
            <div class="card-body text-center text-muted py-4 small">
                <i class="bi bi-shield-check" style="font-size:2rem;opacity:.3"></i>
                <p class="mt-2 mb-0">Tidak ada sertifikat yang akan kadaluarsa dalam 60 hari.</p>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm mb-0 align-middle">
                    <thead><tr>
                        <th>Karyawan</th><th>Sertifikat</th><th>Dept</th><th class="text-center">Sisa Hari</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($certExpiring as $c): ?>
                    <tr class="<?= $c['days_left'] <= 7 ? 'cert-row-urgent' : ($c['days_left'] <= 30 ? 'cert-row-warn' : '') ?>">
                        <td>
                            <div class="fw-medium" style="font-size:.82rem"><?= esc($c['emp_nama']) ?></div>
                            <div class="text-muted" style="font-size:.71rem"><?= esc($c['jabatan']) ?></div>
                        </td>
                        <td style="font-size:.82rem"><?= esc($c['nama_sertifikat']) ?></td>
                        <td class="text-muted" style="font-size:.75rem"><?= esc($c['dept_name']) ?></td>
                        <td class="text-center">
                            <span class="badge bg-<?= $c['days_left'] <= 7 ? 'danger' : ($c['days_left'] <= 30 ? 'warning text-dark' : 'secondary') ?>">
                                <?= $c['days_left'] ?>h
                            </span>
                            <div class="text-muted" style="font-size:.66rem"><?= date('d M Y', strtotime($c['tanggal_kadaluarsa'])) ?></div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Budget per Dept -->
    <div class="col-lg-5 anim-fade-up" style="animation-delay:.31s">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div class="section-title mb-0">Budget Training <?= $tahun ?></div>
                <a href="<?= base_url('people/training/budget') ?>" class="btn btn-xs btn-sm btn-outline-secondary py-0 px-2" style="font-size:.72rem">Kelola</a>
            </div>
            <div class="card-body">
                <?php if (empty($budgetRows)): ?>
                <div class="text-center text-muted py-4 small">
                    <i class="bi bi-wallet2" style="font-size:2rem;opacity:.3"></i>
                    <p class="mt-2 mb-0">Belum ada budget ditetapkan untuk <?= $tahun ?>.</p>
                    <a href="<?= base_url('people/training/budget') ?>" class="btn btn-sm btn-primary mt-2">Set Budget</a>
                </div>
                <?php else: ?>
                <?php foreach ($budgetRows as $b):
                    $pct = $b['pct'];
                    $barColor = $pct === null ? 'secondary' : ($pct > 100 ? 'danger' : ($pct > 80 ? 'warning' : 'success'));
                ?>
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-baseline mb-1">
                        <span style="font-size:.8rem;font-weight:500"><?= esc($b['dept_name']) ?></span>
                        <span style="font-size:.72rem" class="text-muted">
                            <?= $pct !== null ? $pct . '%' : '—' ?>
                            <?php if ($pct > 100): ?><span class="text-danger fw-bold"> !</span><?php endif; ?>
                        </span>
                    </div>
                    <div class="budget-bar">
                        <div class="budget-bar-fill bg-<?= $barColor ?>" style="width:<?= min($pct ?? 0, 100) ?>%"></div>
                    </div>
                    <div class="d-flex justify-content-between mt-1" style="font-size:.68rem;color:var(--bs-secondary-color)">
                        <span>Rp <?= number_format($b['realisasi'] / 1e6, 1) ?>jt / <?= $b['anggaran'] > 0 ? 'Rp ' . number_format($b['anggaran'] / 1e6, 1) . 'jt' : '—' ?></span>
                        <?php if ($b['anggaran'] > 0): ?>
                        <span class="<?= $b['anggaran'] - $b['realisasi'] < 0 ? 'text-danger' : '' ?>">
                            Sisa Rp <?= number_format(($b['anggaran'] - $b['realisasi']) / 1e6, 1) ?>jt
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Row 4: Employee distribution -->
<?php if (! empty($empByDept)): ?>
<div class="row g-3 anim-fade-up" style="animation-delay:.34s">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><div class="section-title mb-0">Distribusi Karyawan Aktif per Departemen</div></div>
            <div class="card-body"><canvas id="empDeptChart" style="max-height:200px"></canvas></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><div class="section-title mb-0">Status Karyawan</div></div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <canvas id="empStatusChart" style="max-height:180px;max-width:180px"></canvas>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
const _dark = document.documentElement.getAttribute('data-theme') === 'dark';
const gridColor  = _dark ? 'rgba(255,255,255,.06)' : 'rgba(0,0,0,.06)';
const textColor  = _dark ? 'rgba(180,210,255,.6)' : '#64748b';

<?php if (! empty($tnaGaps)): ?>
// TNA Gap Chart
new Chart(document.getElementById('gapChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($tnaGaps, 'nama')) ?>,
        datasets: [{
            label: 'Avg Gap',
            data: <?= json_encode(array_map(fn($g) => (float)$g['avg_gap'], $tnaGaps)) ?>,
            backgroundColor: <?= json_encode(array_map(fn($g) => ((float)$g['avg_gap'] > 1.5 ? 'rgba(239,68,68,.75)' : ((float)$g['avg_gap'] > 0.7 ? 'rgba(245,158,11,.75)' : 'rgba(99,102,241,.75)')), $tnaGaps)) ?>,
            borderRadius: 4,
        }, {
            label: 'Avg Target',
            data: <?= json_encode(array_map(fn($g) => (float)$g['avg_target'], $tnaGaps)) ?>,
            backgroundColor: 'rgba(100,116,139,.2)',
            borderRadius: 4,
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false }, tooltip: { callbacks: {
            label: ctx => ctx.dataset.label + ': ' + ctx.parsed.x.toFixed(2)
        }}},
        scales: {
            x: { grid: { color: gridColor }, ticks: { color: textColor }, beginAtZero: true, max: 5 },
            y: { grid: { color: 'transparent' }, ticks: { color: textColor, font: { size: 11 } } }
        }
    }
});
<?php endif; ?>

<?php if (! empty($empByDept)): ?>
// Employee by dept chart
new Chart(document.getElementById('empDeptChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($empByDept, 'dept_name')) ?>,
        datasets: [{
            label: 'Karyawan Aktif',
            data: <?= json_encode(array_column($empByDept, 'cnt')) ?>,
            backgroundColor: 'rgba(99,102,241,.7)',
            borderRadius: 4,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { color: gridColor }, ticks: { color: textColor } },
            y: { grid: { color: gridColor }, ticks: { color: textColor, stepSize: 1 }, beginAtZero: true }
        }
    }
});

// Employee status donut
new Chart(document.getElementById('empStatusChart'), {
    type: 'doughnut',
    data: {
        labels: ['Aktif', 'Resign', 'Cuti Panjang', 'Pensiun'],
        datasets: [{
            data: [<?= $aktif ?>, <?= $resign ?>, <?= $cuti ?>, <?= $pensiun ?>],
            backgroundColor: ['rgba(34,197,94,.8)', 'rgba(239,68,68,.8)', 'rgba(245,158,11,.8)', 'rgba(100,116,139,.8)'],
            borderWidth: 0,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: true,
        cutout: '65%',
        plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 }, color: textColor } } }
    }
});
<?php endif; ?>
</script>
<?= $this->endSection() ?>
