<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<style>
.fade-up { opacity:0; transform:translateY(16px); animation:fadeUpSps .5s cubic-bezier(.22,.68,0,1.2) forwards; }
@keyframes fadeUpSps { to { opacity:1; transform:translateY(0); } }
.deal-badge { font-size:.68rem; font-weight:600; padding:2px 8px; border-radius:999px; display:inline-block; }
.deal-prospek      { background:#f1f5f9; color:#64748b; }
.deal-negosiasi    { background:#fef3c7; color:#d97706; }
.deal-terkonfirmasi{ background:#dbeafe; color:#1d4ed8; }
.deal-lunas        { background:#dcfce7; color:#16a34a; }
.deal-batal        { background:#fee2e2; color:#dc2626; }
.prog-bar-wrap { height:6px; border-radius:999px; background:#f1f5f9; overflow:hidden; }
.prog-bar-fill { height:100%; border-radius:999px; background:#d97706; }
</style>
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php
function smFmt(int $n): string { return 'Rp ' . number_format($n, 0, ',', '.'); }
function smShort(int $n): string {
    if ($n >= 1_000_000_000) return 'Rp ' . number_format($n / 1_000_000_000, 1, ',', '.') . ' M';
    if ($n >= 1_000_000)     return 'Rp ' . number_format($n / 1_000_000, 1, ',', '.') . ' jt';
    return smFmt($n);
}
function smPct(int $a, int $b): float { return $b > 0 ? min(100, round($a / $b * 100, 1)) : 0; }

$bulanLabel = function(string $ym): string {
    $names = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'];
    [$y, $m] = explode('-', $ym);
    return ($names[(int)$m - 1] ?? $m) . ' ' . $y;
};
?>

<!-- Header -->
<div class="d-flex align-items-center gap-2 mb-4 fade-up" style="animation-delay:.05s">
    <div class="rounded-2 d-flex align-items-center justify-content-center flex-shrink-0"
         style="width:36px;height:36px;background:rgba(245,158,11,.15)">
        <i class="bi bi-bar-chart-line-fill" style="color:#d97706;font-size:1rem"></i>
    </div>
    <div>
        <h4 class="fw-bold mb-0">Summary Sponsorship</h4>
        <small class="text-muted">Trend bulanan, realisasi per program, dan breakdown sponsor</small>
    </div>
    <div class="ms-auto">
        <a href="<?= base_url('sponsorship') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Kembali ke Deal
        </a>
    </div>
</div>

<!-- Month Selector + KPIs -->
<div class="row g-3 mb-4 align-items-stretch">
    <!-- Month picker -->
    <div class="col-12 col-md-3">
        <div class="card h-100 fade-up" style="animation-delay:.1s">
            <div class="card-body d-flex flex-column justify-content-center gap-2">
                <label class="small text-muted fw-semibold text-uppercase" style="letter-spacing:.05em">Bulan</label>
                <form method="get" id="bulanForm">
                    <select name="bulan" class="form-select form-select-sm" onchange="document.getElementById('bulanForm').submit()">
                        <?php foreach ($monthList as $m): ?>
                        <option value="<?= $m ?>" <?= $m === $bulan ? 'selected' : '' ?>>
                            <?= $bulanLabel($m) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </form>
                <div class="text-muted" style="font-size:.75rem">
                    <?= count($programs) ?> program&nbsp;&middot;&nbsp;<?= $kpiSponsor ?> sponsor terkonfirmasi
                </div>
            </div>
        </div>
    </div>

    <!-- KPI: Terkumpul bulan ini -->
    <div class="col-6 col-md-3">
        <div class="card border-warning-subtle h-100 fade-up" style="animation-delay:.15s">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <div class="rounded-2 p-1 bg-warning-subtle"><i class="bi bi-cash-stack text-warning fs-5"></i></div>
                    <span class="small text-muted">Terkumpul Bulan Ini</span>
                </div>
                <div class="fw-bold fs-5 text-warning"><?= smShort($kpiTerkumpul) ?></div>
                <div class="small text-muted"><?= $bulanLabel($bulan) ?></div>
            </div>
        </div>
    </div>

    <!-- KPI: Committed -->
    <div class="col-6 col-md-3">
        <div class="card border-primary-subtle h-100 fade-up" style="animation-delay:.2s">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <div class="rounded-2 p-1 bg-primary-subtle"><i class="bi bi-check2-circle text-primary fs-5"></i></div>
                    <span class="small text-muted">Nilai Dikonfirmasi</span>
                </div>
                <div class="fw-bold fs-5 text-primary"><?= smShort($kpiCommitted) ?></div>
                <div class="small text-muted"><?= $kpiSponsor ?> sponsor deal</div>
            </div>
        </div>
    </div>

    <!-- KPI: Total terkumpul all-time -->
    <div class="col-6 col-md-3">
        <div class="card border-success-subtle h-100 fade-up" style="animation-delay:.25s">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <div class="rounded-2 p-1 bg-success-subtle"><i class="bi bi-piggy-bank text-success fs-5"></i></div>
                    <span class="small text-muted">Total Terkumpul (All)</span>
                </div>
                <?php $grandTotal = array_sum($allTimeRealMap); ?>
                <div class="fw-bold fs-5 text-success"><?= smShort($grandTotal) ?></div>
                <div class="small text-muted">seluruh program</div>
            </div>
        </div>
    </div>
</div>

<!-- Charts row -->
<div class="row g-3 mb-4">
    <!-- Daily bar chart -->
    <div class="col-12 col-md-7">
        <div class="card fade-up" style="animation-delay:.3s">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="fw-semibold mb-0">Realisasi Harian — <?= $bulanLabel($bulan) ?></h6>
                    <span class="small text-muted"><?= smShort($kpiTerkumpul) ?> total</span>
                </div>
                <canvas id="dailyChart" height="160"></canvas>
            </div>
        </div>
    </div>

    <!-- Monthly trend table -->
    <div class="col-12 col-md-5">
        <div class="card fade-up h-100" style="animation-delay:.35s">
            <div class="card-body">
                <h6 class="fw-semibold mb-3">Trend Bulanan <?= date('Y') ?></h6>
                <?php if (empty($allMonthlyTotals)): ?>
                <p class="text-muted small mb-0">Belum ada data realisasi tahun ini.</p>
                <?php else: ?>
                <div style="max-height:220px;overflow-y:auto">
                <table class="table table-sm table-hover mb-0" style="font-size:.82rem">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th>Bulan</th>
                            <th class="text-end">Total Realisasi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $maxMonthVal = max(array_column($allMonthlyTotals, 'total_nilai') ?: [1]);
                    foreach ($allMonthlyTotals as $row):
                        $isSelected = $row['bulan'] === $bulan;
                    ?>
                    <tr class="<?= $isSelected ? 'table-warning' : '' ?>">
                        <td>
                            <a href="?bulan=<?= $row['bulan'] ?>" class="text-decoration-none <?= $isSelected ? 'fw-bold' : '' ?>">
                                <?= $bulanLabel($row['bulan']) ?>
                            </a>
                        </td>
                        <td class="text-end"><?= smShort((int)$row['total_nilai']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Per-program breakdown -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card fade-up" style="animation-delay:.4s">
            <div class="card-body">
                <h6 class="fw-semibold mb-3">Realisasi per Program — <?= $bulanLabel($bulan) ?></h6>
                <?php if (empty($programs)): ?>
                <p class="text-muted small mb-0">Belum ada program.</p>
                <?php else: ?>
                <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0" style="font-size:.82rem">
                    <thead class="table-light">
                        <tr>
                            <th>Program</th>
                            <th>Status</th>
                            <th class="text-end">Target Nilai</th>
                            <th class="text-end">Dikonfirmasi</th>
                            <th class="text-end">Bulan Ini</th>
                            <th class="text-end">All-time</th>
                            <th style="min-width:120px">Progress</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($programs as $prog):
                        $pid          = (int)$prog['id'];
                        $target       = (int)($prog['target_nilai'] ?? 0);
                        $committed    = (int)(($committedMap[$pid] ?? [])['total_nilai'] ?? 0);
                        $thisMonth    = (int)($monthlyReal[$pid] ?? 0);
                        $allTime      = (int)($allTimeRealMap[$pid] ?? 0);
                        $pct          = smPct($allTime, $target);
                    ?>
                    <tr>
                        <td>
                            <a href="<?= base_url('sponsorship#program-' . $pid) ?>" class="text-decoration-none fw-semibold">
                                <?= esc($prog['nama_program']) ?>
                            </a>
                            <?php if ($prog['locked']): ?>
                            <span class="ms-1"><i class="bi bi-lock-fill text-secondary" style="font-size:.7rem" title="Terkunci"></i></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge <?= $prog['status'] === 'active' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' ?>"
                                  style="font-size:.68rem">
                                <?= $prog['status'] === 'active' ? 'Aktif' : 'Nonaktif' ?>
                            </span>
                        </td>
                        <td class="text-end"><?= $target > 0 ? smShort($target) : '<span class="text-muted">—</span>' ?></td>
                        <td class="text-end text-primary"><?= smShort($committed) ?></td>
                        <td class="text-end <?= $thisMonth > 0 ? 'text-warning fw-semibold' : 'text-muted' ?>">
                            <?= $thisMonth > 0 ? smShort($thisMonth) : '—' ?>
                        </td>
                        <td class="text-end text-success"><?= smShort($allTime) ?></td>
                        <td>
                            <?php if ($target > 0): ?>
                            <div class="prog-bar-wrap">
                                <div class="prog-bar-fill" style="width:<?= $pct ?>%"></div>
                            </div>
                            <small class="text-muted"><?= $pct ?>%</small>
                            <?php else: ?>
                            <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light fw-semibold">
                        <tr>
                            <td colspan="3">Total</td>
                            <td class="text-end text-primary"><?= smShort($kpiCommitted) ?></td>
                            <td class="text-end text-warning"><?= smShort($kpiTerkumpul) ?></td>
                            <td class="text-end text-success"><?= smShort($grandTotal) ?></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Per-program sponsor status breakdown -->
<div class="row g-3 mb-4">
<?php foreach ($programs as $prog):
    $pid      = (int)$prog['id'];
    $spList   = $sponsors[$pid] ?? [];
    if (empty($spList)) continue;

    $byStatus = [];
    foreach ($spList as $sp) {
        $s = $sp['status_deal'];
        if (!isset($byStatus[$s])) $byStatus[$s] = ['count' => 0, 'nilai' => 0];
        $byStatus[$s]['count']++;
        $byStatus[$s]['nilai'] += (int)$sp['nilai'];
    }
    $statusOrder = ['prospek', 'negosiasi', 'terkonfirmasi', 'lunas', 'batal'];
    $statusLabel = [
        'prospek'       => 'Prospek',
        'negosiasi'     => 'Negosiasi',
        'terkonfirmasi' => 'Terkonfirmasi',
        'lunas'         => 'Lunas',
        'batal'         => 'Batal',
    ];
?>
<div class="col-12 col-md-6">
    <div class="card fade-up" style="animation-delay:.45s">
        <div class="card-body">
            <div class="d-flex align-items-center gap-2 mb-3">
                <h6 class="fw-semibold mb-0 flex-grow-1">
                    <a href="<?= base_url('sponsorship#program-' . $pid) ?>" class="text-decoration-none text-dark">
                        <?= esc($prog['nama_program']) ?>
                    </a>
                </h6>
                <span class="small text-muted"><?= count($spList) ?> sponsor</span>
            </div>
            <table class="table table-sm mb-0" style="font-size:.82rem">
                <thead class="table-light">
                    <tr>
                        <th>Status Deal</th>
                        <th class="text-center">Jumlah</th>
                        <th class="text-end">Total Nilai</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($statusOrder as $s):
                    if (!isset($byStatus[$s])) continue;
                    $sd = $byStatus[$s];
                ?>
                <tr>
                    <td><span class="deal-badge deal-<?= $s ?>"><?= $statusLabel[$s] ?></span></td>
                    <td class="text-center"><?= $sd['count'] ?></td>
                    <td class="text-end"><?= smShort($sd['nilai']) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>

<?= $this->section('scripts') ?>
<script>
(function () {
    const labels = <?= json_encode($chartDates) ?>;
    const data   = <?= json_encode(array_values($dailyNilai)) ?>;

    const hasData = data.some(v => v > 0);

    const ctx = document.getElementById('dailyChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Realisasi (Rp)',
                data,
                backgroundColor: data.map(v => v > 0 ? 'rgba(217,119,6,.75)' : 'rgba(217,119,6,.12)'),
                borderColor:     data.map(v => v > 0 ? 'rgba(217,119,6,1)'   : 'rgba(217,119,6,.2)'),
                borderWidth: 1,
                borderRadius: 3,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => 'Rp ' + ctx.parsed.y.toLocaleString('id-ID')
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { font: { size: 10 } }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        font: { size: 10 },
                        callback: v => {
                            if (v >= 1e9) return (v/1e9).toLocaleString('id-ID',{maximumFractionDigits:1}) + ' M';
                            if (v >= 1e6) return (v/1e6).toLocaleString('id-ID',{maximumFractionDigits:1}) + ' jt';
                            return v.toLocaleString('id-ID');
                        }
                    }
                }
            }
        }
    });
})();
</script>
<?= $this->endSection() ?>

<?= $this->endSection() ?>
