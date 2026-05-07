<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$isStandalone  = $source === 's';
$isLocked      = $isStandalone && (bool)($prog['locked'] ?? false);
$targetPeserta = (int)($prog['target_peserta']      ?? 0);
$targetAktif   = (int)($prog['target_member_aktif'] ?? 0);
$pctMember     = $targetPeserta  > 0 ? min(100, round($totalMember      / $targetPeserta  * 100, 1)) : null;
$pctAktif      = $targetAktif    > 0 ? min(100, round($totalMemberAktif / $targetAktif    * 100, 1)) : null;
$pctSerapan    = $totalDiterbitkan > 0 ? min(100, round($totalTerpakai   / $totalDiterbitkan * 100, 1)) : null;
$pctStok       = $totalStok > 0       ? min(100, round($totalHadiah      / $totalStok       * 100, 1)) : null;
?>

<!-- Header -->
<div class="d-flex align-items-start gap-2 mb-4">
    <a href="<?= base_url('loyalty/summary') ?>" class="btn btn-sm btn-outline-secondary mt-1"><i class="bi bi-arrow-left"></i></a>
    <div class="flex-grow-1">
        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
            <h4 class="fw-bold mb-0"><?= esc($prog['nama_program']) ?></h4>
            <?php if (! $isStandalone): ?>
            <span class="badge bg-warning-subtle text-warning-emphasis">
                <i class="bi bi-calendar-event me-1"></i><?= esc($prog['event_name']) ?>
            </span>
            <?php endif; ?>
            <?php if (($prog['status'] ?? 'active') === 'inactive'): ?>
            <span class="badge bg-secondary">Non-aktif</span>
            <?php else: ?>
            <span class="badge bg-success-subtle text-success">Aktif</span>
            <?php endif; ?>
            <?php if ($isLocked): ?>
            <span class="badge bg-danger"><i class="bi bi-lock-fill me-1"></i>Terkunci</span>
            <?php endif; ?>
        </div>
        <div class="d-flex gap-3 flex-wrap text-muted small">
            <?php if ($prog['event_mall'] ?? null): ?>
            <span><i class="bi bi-building me-1"></i><?= esc($prog['event_mall']) ?></span>
            <?php endif; ?>
            <?php if ($prog['mekanisme'] ?? null): ?>
            <span><i class="bi bi-info-circle me-1"></i><?= esc($prog['mekanisme']) ?></span>
            <?php endif; ?>
            <?php if (($prog['tanggal_mulai'] ?? null) || ($prog['tanggal_selesai'] ?? null)): ?>
            <span><i class="bi bi-calendar me-1"></i>
                <?= $prog['tanggal_mulai']  ? date('d M Y', strtotime($prog['tanggal_mulai']))  : '?' ?>
                —
                <?= $prog['tanggal_selesai'] ? date('d M Y', strtotime($prog['tanggal_selesai'])) : '?' ?>
            </span>
            <?php endif; ?>
            <?php if ($prog['catatan'] ?? null): ?>
            <span><i class="bi bi-sticky me-1"></i><?= esc($prog['catatan']) ?></span>
            <?php endif; ?>
        </div>
    </div>
    <a href="<?= base_url('loyalty') ?>" class="btn btn-sm btn-outline-primary">
        <i class="bi bi-list-ul me-1"></i>Semua Program
    </a>
</div>

<!-- KPI Cards (all-time) -->
<div class="row g-3 mb-4">
    <div class="col-6 col-xl">
        <div class="card border-primary-subtle h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <div class="rounded-2 p-1 bg-primary-subtle"><i class="bi bi-person-plus text-primary fs-5"></i></div>
                    <span class="small text-muted">Member Baru</span>
                </div>
                <div class="fw-bold fs-3 text-primary"><?= number_format($totalMember) ?></div>
                <?php if ($pctMember !== null): ?>
                <div class="progress mt-1" style="height:4px">
                    <?php $c = $pctMember >= 100 ? 'success' : ($pctMember >= 60 ? 'primary' : ($pctMember >= 30 ? 'warning' : 'danger')); ?>
                    <div class="progress-bar bg-<?= $c ?>" style="width:<?= max($pctMember, 1) ?>%"></div>
                </div>
                <div class="small text-muted mt-1"><?= $pctMember ?>% dari target <?= number_format($targetPeserta) ?></div>
                <?php elseif ($targetPeserta === 0): ?>
                <div class="small text-muted">Belum ada target</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl">
        <div class="card border-info-subtle h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <div class="rounded-2 p-1 bg-info-subtle"><i class="bi bi-person-check text-info fs-5"></i></div>
                    <span class="small text-muted">Member Aktif</span>
                </div>
                <div class="fw-bold fs-3 text-info"><?= number_format($totalMemberAktif) ?></div>
                <?php if ($pctAktif !== null): ?>
                <div class="progress mt-1" style="height:4px">
                    <?php $c = $pctAktif >= 100 ? 'success' : ($pctAktif >= 60 ? 'info' : ($pctAktif >= 30 ? 'warning' : 'danger')); ?>
                    <div class="progress-bar bg-<?= $c ?>" style="width:<?= max($pctAktif, 1) ?>%"></div>
                </div>
                <div class="small text-muted mt-1"><?= $pctAktif ?>% dari target <?= number_format($targetAktif) ?></div>
                <?php elseif ($targetAktif === 0): ?>
                <div class="small text-muted">Belum ada target</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php if (!empty($voucherItems)): ?>
    <div class="col-6 col-xl">
        <div class="card border-warning-subtle h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <div class="rounded-2 p-1 bg-warning-subtle"><i class="bi bi-ticket text-warning fs-5"></i></div>
                    <span class="small text-muted">Voucher Tersebar</span>
                </div>
                <div class="fw-bold fs-3 text-warning"><?= number_format($totalTersebar) ?></div>
                <div class="small text-muted">dari <?= number_format($totalDiterbitkan) ?> diterbitkan</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl">
        <div class="card border-success-subtle h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <div class="rounded-2 p-1 bg-success-subtle"><i class="bi bi-ticket-perforated text-success fs-5"></i></div>
                    <span class="small text-muted">Voucher Terpakai</span>
                </div>
                <div class="fw-bold fs-3 text-success"><?= number_format($totalTerpakai) ?></div>
                <?php if ($pctSerapan !== null): ?>
                <div class="progress mt-1" style="height:4px">
                    <?php $c = $pctSerapan >= 100 ? 'success' : ($pctSerapan >= 60 ? 'success' : ($pctSerapan >= 30 ? 'warning' : 'danger')); ?>
                    <div class="progress-bar bg-<?= $c ?>" style="width:<?= max($pctSerapan, 1) ?>%"></div>
                </div>
                <div class="small text-muted mt-1"><?= $pctSerapan ?>% penyerapan</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <?php if (!empty($hadiahItems)): ?>
    <div class="col-6 col-xl">
        <div class="card border-danger-subtle h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <div class="rounded-2 p-1 bg-danger-subtle"><i class="bi bi-gift text-danger fs-5"></i></div>
                    <span class="small text-muted">Hadiah Dibagikan</span>
                </div>
                <div class="fw-bold fs-3 text-danger"><?= number_format($totalHadiah) ?></div>
                <?php if ($pctStok !== null): ?>
                <div class="progress mt-1" style="height:4px">
                    <?php $c = $pctStok >= 100 ? 'danger' : ($pctStok >= 60 ? 'warning' : ($pctStok >= 30 ? 'primary' : 'success')); ?>
                    <div class="progress-bar bg-<?= $c ?>" style="width:<?= max($pctStok, 1) ?>%"></div>
                </div>
                <div class="small text-muted mt-1"><?= $pctStok ?>% dari total stok <?= number_format($totalStok) ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <?php
    // Budget plan & realisasi
    $budgetPlanVoucher = array_sum(array_map(fn($vi) => (int)$vi['total_diterbitkan'] * (int)$vi['nilai_voucher'], $voucherItems));
    $budgetPlanHadiah  = array_sum(array_map(fn($hi) => (int)$hi['stok']              * (int)$hi['nilai_satuan'],  $hadiahItems));
    $budgetPlan        = $budgetPlanVoucher + $budgetPlanHadiah ?: (int)($prog['budget'] ?? 0);
    $budgetRealVoucher = 0;
    foreach ($voucherItems as $vi) {
        $budgetRealVoucher += (int)($voucherReal[$vi['id']]['total_terpakai'] ?? 0) * (int)$vi['nilai_voucher'];
    }
    $budgetRealHadiah = 0;
    foreach ($hadiahItems as $hi) {
        $budgetRealHadiah += (int)($hadiahReal[$hi['id']]['total'] ?? 0) * (int)$hi['nilai_satuan'];
    }
    $budgetReal = $budgetRealVoucher + $budgetRealHadiah;
    $pctBudget  = $budgetPlan > 0 ? round($budgetReal / $budgetPlan * 100, 1) : null;
    $overBudget = $budgetReal > $budgetPlan && $budgetPlan > 0;
    ?>
    <?php if ($budgetPlan > 0 || (int)($prog['budget'] ?? 0) > 0): ?>
    <div class="col-12">
        <div class="card border-secondary-subtle h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="rounded-2 p-1 bg-secondary-subtle"><i class="bi bi-wallet2 text-secondary fs-5"></i></div>
                    <span class="small text-muted">Budget</span>
                    <?php if ($overBudget): ?>
                    <span class="badge bg-danger ms-auto">Over Budget</span>
                    <?php elseif ($pctBudget !== null): ?>
                    <span class="badge bg-secondary ms-auto"><?= $pctBudget ?>% terpakai</span>
                    <?php endif; ?>
                </div>
                <div class="row g-3 align-items-center">
                    <div class="col-sm-4 text-center">
                        <div class="small text-muted mb-1">Budget Plan</div>
                        <div class="fw-bold fs-5 text-secondary">Rp <?= number_format($budgetPlan, 0, ',', '.') ?></div>
                        <?php if ($budgetPlanVoucher > 0 || $budgetPlanHadiah > 0): ?>
                        <div class="small text-muted mt-1">
                            <?php if ($budgetPlanVoucher > 0): ?>
                            <div>Voucher: Rp <?= number_format($budgetPlanVoucher, 0, ',', '.') ?></div>
                            <?php endif; ?>
                            <?php if ($budgetPlanHadiah > 0): ?>
                            <div>Hadiah: Rp <?= number_format($budgetPlanHadiah, 0, ',', '.') ?></div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-sm-4 text-center">
                        <div class="small text-muted mb-1">Realisasi Budget</div>
                        <div class="fw-bold fs-5 <?= $overBudget ? 'text-danger' : 'text-success' ?>">
                            Rp <?= number_format($budgetReal, 0, ',', '.') ?>
                        </div>
                        <?php if ($budgetRealVoucher > 0 || $budgetRealHadiah > 0): ?>
                        <div class="small text-muted mt-1">
                            <?php if ($budgetRealVoucher > 0): ?>
                            <div>Voucher: Rp <?= number_format($budgetRealVoucher, 0, ',', '.') ?></div>
                            <?php endif; ?>
                            <?php if ($budgetRealHadiah > 0): ?>
                            <div>Hadiah: Rp <?= number_format($budgetRealHadiah, 0, ',', '.') ?></div>
                            <?php endif; ?>
                        </div>
                        <?php elseif ($budgetReal === 0): ?>
                        <div class="small text-muted mt-1">Belum ada realisasi</div>
                        <?php endif; ?>
                    </div>
                    <div class="col-sm-4 text-center">
                        <div class="small text-muted mb-1">Sisa Budget</div>
                        <?php $sisaBudget = $budgetPlan - $budgetReal; ?>
                        <div class="fw-bold fs-5 <?= $sisaBudget < 0 ? 'text-danger' : 'text-body' ?>">
                            <?= $sisaBudget < 0 ? '-' : '' ?>Rp <?= number_format(abs($sisaBudget), 0, ',', '.') ?>
                        </div>
                        <?php if ($pctBudget !== null): ?>
                        <div class="progress mt-2" style="height:6px">
                            <?php $bc = $overBudget ? 'danger' : ($pctBudget >= 80 ? 'warning' : 'success'); ?>
                            <div class="progress-bar bg-<?= $bc ?>" style="width:<?= min(100, $pctBudget) ?>%"></div>
                        </div>
                        <div class="small text-muted mt-1"><?= $pctBudget ?>% dari plan</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Tren Bulanan (hanya untuk standalone jika ada data lebih dari 1 bulan) -->
<?php if ($isStandalone && count($trend) > 1):
$idBulan = ['January'=>'Januari','February'=>'Februari','March'=>'Maret','April'=>'April',
    'May'=>'Mei','June'=>'Juni','July'=>'Juli','August'=>'Agustus',
    'September'=>'September','October'=>'Oktober','November'=>'November','December'=>'Desember'];
$shortBulan = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'];
$trendLabels   = [];
$trendMember   = [];
$trendAktif    = [];
$trendSebar    = [];
$trendTerpakai = [];
$trendHadiah   = [];
foreach ($trend as $row) {
    $dt = \DateTime::createFromFormat('Y-m', $row['bulan']);
    $trendLabels[]   = strtr($dt->format('M Y'), array_combine(array_keys($idBulan), $shortBulan));
    $trendMember[]   = (int)$row['total_member'];
    $trendAktif[]    = (int)$row['total_aktif'];
    $trendSebar[]    = (int)$row['total_tersebar'];
    $trendTerpakai[] = (int)$row['total_terpakai'];
    $trendHadiah[]   = (int)$row['total_hadiah'];
}
?>
<div class="row g-3 mb-4">
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header"><span class="fw-semibold small"><i class="bi bi-graph-up me-2"></i>Tren Bulanan</span></div>
            <div class="card-body"><canvas id="trendChart" height="180"></canvas></div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header"><span class="fw-semibold small"><i class="bi bi-table me-2"></i>Ringkasan per Bulan</span></div>
            <div class="table-responsive">
            <table class="table table-sm mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">Bulan</th>
                    <th class="text-end">Baru</th>
                    <th class="text-end">Aktif</th>
                    <?php if (!empty($voucherItems)): ?><th class="text-end">Sebar</th><th class="text-end">Pakai</th><?php endif; ?>
                    <?php if (!empty($hadiahItems)): ?><th class="text-end pe-3">Hadiah</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach (array_reverse($trend) as $row):
                $dt  = \DateTime::createFromFormat('Y-m', $row['bulan']);
                $lbl = strtr($dt->format('F Y'), $idBulan);
            ?>
            <tr>
                <td class="ps-3 small"><?= $lbl ?></td>
                <td class="text-end small <?= $row['total_member'] > 0 ? 'fw-semibold text-primary' : 'text-muted' ?>">
                    <?= $row['total_member'] > 0 ? number_format($row['total_member']) : '—' ?>
                </td>
                <td class="text-end small <?= $row['total_aktif'] > 0 ? 'fw-semibold text-info' : 'text-muted' ?>">
                    <?= $row['total_aktif'] > 0 ? number_format($row['total_aktif']) : '—' ?>
                </td>
                <?php if (!empty($voucherItems)): ?>
                <td class="text-end small <?= $row['total_tersebar'] > 0 ? 'fw-semibold text-warning' : 'text-muted' ?>">
                    <?= $row['total_tersebar'] > 0 ? number_format($row['total_tersebar']) : '—' ?>
                </td>
                <td class="text-end small <?= $row['total_terpakai'] > 0 ? 'fw-semibold text-success' : 'text-muted' ?>">
                    <?= $row['total_terpakai'] > 0 ? number_format($row['total_terpakai']) : '—' ?>
                </td>
                <?php endif; ?>
                <?php if (!empty($hadiahItems)): ?>
                <td class="text-end pe-3 small <?= $row['total_hadiah'] > 0 ? 'fw-semibold text-danger' : 'text-muted' ?>">
                    <?= $row['total_hadiah'] > 0 ? number_format($row['total_hadiah']) : '—' ?>
                </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
            <tr class="table-light fw-bold">
                <td class="ps-3 small">Total</td>
                <td class="text-end small text-primary"><?= number_format($totalMember) ?></td>
                <td class="text-end small text-info"><?= $totalMemberAktif > 0 ? number_format($totalMemberAktif) : '—' ?></td>
                <?php if (!empty($voucherItems)): ?>
                <td class="text-end small text-warning"><?= number_format($totalTersebar) ?></td>
                <td class="text-end small text-success"><?= number_format($totalTerpakai) ?></td>
                <?php endif; ?>
                <?php if (!empty($hadiahItems)): ?>
                <td class="text-end pe-3 small text-danger"><?= $totalHadiah > 0 ? number_format($totalHadiah) : '—' ?></td>
                <?php endif; ?>
            </tr>
            </tbody>
            </table>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Grafik Harian -->
<?php
$dailyData = [];
foreach ($allEntries as $e) {
    $tgl = $e['tanggal'];
    $dailyData[$tgl]['member'] = ($dailyData[$tgl]['member'] ?? 0) + (int)$e['jumlah'];
    $dailyData[$tgl]['aktif']  = ($dailyData[$tgl]['aktif']  ?? 0) + (int)($e['member_aktif'] ?? 0);
}
foreach ($voucherReal as $vr) {
    foreach ($vr['entries'] ?? [] as $e) {
        $tgl = $e['tanggal'];
        $dailyData[$tgl]['sebar'] = ($dailyData[$tgl]['sebar'] ?? 0) + (int)$e['tersebar'];
        $dailyData[$tgl]['pakai'] = ($dailyData[$tgl]['pakai'] ?? 0) + (int)$e['terpakai'];
    }
}
foreach ($hadiahReal as $hr) {
    foreach ($hr['entries'] ?? [] as $e) {
        $tgl = $e['tanggal'];
        $dailyData[$tgl]['hadiah'] = ($dailyData[$tgl]['hadiah'] ?? 0) + (int)$e['jumlah_dibagikan'];
    }
}
ksort($dailyData);

if (!empty($dailyData)):
    $idBulanS    = ['January'=>'Jan','February'=>'Feb','March'=>'Mar','April'=>'Apr','May'=>'Mei','June'=>'Jun','July'=>'Jul','August'=>'Ags','September'=>'Sep','October'=>'Okt','November'=>'Nov','December'=>'Des'];
    $dailyLabels = $dailyMember = $dailyAktif = $dailySebar = $dailyPakai = $dailyHadiah = [];
    foreach ($dailyData as $tgl => $d) {
        $dailyLabels[] = strtr(date('d M Y', strtotime($tgl)), $idBulanS);
        $dailyMember[] = $d['member'] ?? 0;
        $dailyAktif[]  = $d['aktif']  ?? 0;
        $dailySebar[]  = $d['sebar']  ?? 0;
        $dailyPakai[]  = $d['pakai']  ?? 0;
        $dailyHadiah[] = $d['hadiah'] ?? 0;
    }
?>
<div class="card mb-4">
    <div class="card-header"><span class="fw-semibold small"><i class="bi bi-bar-chart-line me-2"></i>Grafik Harian</span></div>
    <div class="card-body"><canvas id="dailyChart" height="<?= count($dailyData) > 14 ? 100 : 120 ?>"></canvas></div>
</div>
<?php endif; ?>

<!-- Voucher Items Detail -->
<?php if (!empty($voucherItems)): ?>
<div class="card mb-4">
    <div class="card-header">
        <span class="fw-semibold small"><i class="bi bi-ticket-perforated me-2 text-warning"></i>Detail Voucher</span>
    </div>
    <?php foreach ($voucherItems as $vi):
        $vd      = $voucherReal[$vi['id']] ?? ['total_tersebar' => 0, 'total_terpakai' => 0, 'entries' => []];
        $serapan = $vi['total_diterbitkan'] > 0 ? min(100, round($vd['total_terpakai'] / $vi['total_diterbitkan'] * 100, 1)) : 0;
        $sisa    = (int)$vi['total_diterbitkan'] - (int)$vd['total_terpakai'];
        $cSerapan = $serapan >= 100 ? 'success' : ($serapan >= 60 ? 'success' : ($serapan >= 30 ? 'warning' : 'danger'));
    ?>
    <div class="card-body border-bottom py-3">
        <div class="row align-items-start g-3">
            <div class="col-md-4">
                <div class="fw-semibold"><?= esc($vi['nama_voucher']) ?></div>
                <div class="text-muted small">Nilai: Rp <?= number_format($vi['nilai_voucher'], 0, ',', '.') ?></div>
                <?php if ($vi['target_penyerapan'] ?? null): ?>
                <div class="text-muted small">Target penyerapan: <?= $vi['target_penyerapan'] ?>%</div>
                <?php endif; ?>
            </div>
            <div class="col-md-4">
                <div class="row g-2 text-center">
                    <div class="col-4">
                        <div class="small text-muted">Diterbitkan</div>
                        <div class="fw-semibold"><?= number_format($vi['total_diterbitkan']) ?></div>
                    </div>
                    <div class="col-4">
                        <div class="small text-muted">Tersebar</div>
                        <div class="fw-semibold text-warning"><?= number_format($vd['total_tersebar']) ?></div>
                    </div>
                    <div class="col-4">
                        <div class="small text-muted">Terpakai</div>
                        <div class="fw-semibold text-success"><?= number_format($vd['total_terpakai']) ?></div>
                    </div>
                </div>
                <div class="progress mt-2" style="height:5px">
                    <div class="progress-bar bg-<?= $cSerapan ?>" style="width:<?= max($serapan, $serapan > 0 ? $serapan : 1) ?>%"></div>
                </div>
                <div class="small text-muted mt-1 text-center"><?= $serapan ?>% penyerapan · sisa <?= number_format(max(0, $sisa)) ?></div>
            </div>
            <div class="col-md-4">
                <?php if (!empty($vd['entries'])): ?>
                <div class="small text-muted fw-semibold mb-1">Log Realisasi</div>
                <div style="max-height:140px;overflow-y:auto">
                <table class="table table-sm table-borderless mb-0" style="font-size:.78rem">
                <thead><tr class="text-muted"><th class="p-0 pb-1">Tanggal</th><th class="text-end p-0 pb-1">Sebar</th><th class="text-end p-0 pb-1">Pakai</th></tr></thead>
                <tbody>
                <?php foreach ($vd['entries'] as $e): ?>
                <tr>
                    <td class="p-0 py-1"><?= date('d M Y', strtotime($e['tanggal'])) ?></td>
                    <td class="text-end p-0 py-1 text-warning"><?= number_format($e['tersebar']) ?></td>
                    <td class="text-end p-0 py-1 text-success"><?= number_format($e['terpakai']) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
                </table>
                </div>
                <?php else: ?>
                <div class="small text-muted fst-italic">Belum ada realisasi</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Hadiah Items Detail -->
<?php if (!empty($hadiahItems)): ?>
<div class="card mb-4">
    <div class="card-header">
        <span class="fw-semibold small"><i class="bi bi-gift me-2 text-danger"></i>Detail Barang/Hadiah</span>
    </div>
    <?php foreach ($hadiahItems as $hi):
        $hd       = $hadiahReal[$hi['id']] ?? ['total' => 0, 'entries' => []];
        $dibagikan = (int)$hd['total'];
        $sisa      = (int)$hi['stok'] - $dibagikan;
        $pctH      = $hi['stok'] > 0 ? min(100, round($dibagikan / $hi['stok'] * 100, 1)) : 0;
        $cH        = $pctH >= 100 ? 'danger' : ($pctH >= 60 ? 'warning' : ($pctH >= 30 ? 'primary' : 'success'));
    ?>
    <div class="card-body border-bottom py-3">
        <div class="row align-items-start g-3">
            <div class="col-md-4">
                <div class="fw-semibold"><?= esc($hi['nama_hadiah']) ?></div>
                <div class="text-muted small">Nilai satuan: Rp <?= number_format($hi['nilai_satuan'], 0, ',', '.') ?></div>
                <div class="text-muted small">Total nilai: Rp <?= number_format($hi['stok'] * $hi['nilai_satuan'], 0, ',', '.') ?></div>
            </div>
            <div class="col-md-4">
                <div class="row g-2 text-center">
                    <div class="col-4">
                        <div class="small text-muted">Stok</div>
                        <div class="fw-semibold"><?= number_format($hi['stok']) ?></div>
                    </div>
                    <div class="col-4">
                        <div class="small text-muted">Dibagikan</div>
                        <div class="fw-semibold text-danger"><?= number_format($dibagikan) ?></div>
                    </div>
                    <div class="col-4">
                        <div class="small text-muted">Sisa</div>
                        <div class="fw-semibold <?= $sisa <= 0 ? 'text-danger' : ($sisa <= 3 ? 'text-warning' : '') ?>"><?= number_format(max(0, $sisa)) ?></div>
                    </div>
                </div>
                <div class="progress mt-2" style="height:5px">
                    <div class="progress-bar bg-<?= $cH ?>" style="width:<?= max($pctH, $pctH > 0 ? $pctH : 1) ?>%"></div>
                </div>
                <div class="small text-muted mt-1 text-center"><?= $pctH ?>% terpakai</div>
            </div>
            <div class="col-md-4">
                <?php if (!empty($hd['entries'])): ?>
                <div class="small text-muted fw-semibold mb-1">Log Pembagian</div>
                <div style="max-height:140px;overflow-y:auto">
                <table class="table table-sm table-borderless mb-0" style="font-size:.78rem">
                <thead><tr class="text-muted"><th class="p-0 pb-1">Tanggal</th><th class="text-end p-0 pb-1">Jumlah</th></tr></thead>
                <tbody>
                <?php foreach ($hd['entries'] as $e): ?>
                <tr>
                    <td class="p-0 py-1"><?= date('d M Y', strtotime($e['tanggal'])) ?></td>
                    <td class="text-end p-0 py-1 text-danger"><?= number_format($e['jumlah_dibagikan']) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
                </table>
                </div>
                <?php else: ?>
                <div class="small text-muted fst-italic">Belum ada realisasi</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Realisasi Member Log -->
<div class="card mb-4">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span class="fw-semibold small"><i class="bi bi-journal-text me-2"></i>Log Realisasi Member</span>
        <span class="badge bg-secondary"><?= count($allEntries) ?> entri</span>
    </div>
    <?php if (empty($allEntries)): ?>
    <div class="card-body text-center text-muted py-4">Belum ada data realisasi.</div>
    <?php else: ?>
    <div class="table-responsive">
    <table class="table table-sm mb-0">
    <thead class="table-light">
        <tr>
            <th class="ps-3">Tanggal</th>
            <th class="text-end">Member Baru</th>
            <th class="text-end">Member Aktif</th>
            <th class="pe-3">Catatan</th>
        </tr>
    </thead>
    <tbody>
    <?php
    $runMember = 0;
    $runAktif  = 0;
    $sorted = $allEntries;
    usort($sorted, fn($a, $b) => strcmp($b['tanggal'], $a['tanggal']));
    foreach ($sorted as $e):
        $runMember += (int)$e['jumlah'];
        $runAktif  += (int)($e['member_aktif'] ?? 0);
    ?>
    <tr>
        <td class="ps-3 small"><?= date('d M Y', strtotime($e['tanggal'])) ?></td>
        <td class="text-end small fw-semibold text-primary"><?= number_format($e['jumlah']) ?></td>
        <td class="text-end small <?= (int)($e['member_aktif'] ?? 0) > 0 ? 'fw-semibold text-info' : 'text-muted' ?>">
            <?= (int)($e['member_aktif'] ?? 0) > 0 ? number_format($e['member_aktif']) : '—' ?>
        </td>
        <td class="pe-3 small text-muted"><?= esc($e['catatan'] ?? '') ?></td>
    </tr>
    <?php endforeach; ?>
    <tr class="table-light fw-bold">
        <td class="ps-3 small">Total</td>
        <td class="text-end small text-primary"><?= number_format($totalMember) ?></td>
        <td class="text-end small text-info"><?= $totalMemberAktif > 0 ? number_format($totalMemberAktif) : '—' ?></td>
        <td class="pe-3"></td>
    </tr>
    </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<?php if ($isStandalone && count($trend) > 1): ?>
<script>
new Chart(document.getElementById('trendChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($trendLabels) ?>,
        datasets: [
            {
                label: 'Member Baru',
                data: <?= json_encode($trendMember) ?>,
                borderColor: '#6366f1',
                backgroundColor: 'rgba(99,102,241,.1)',
                pointRadius: 4, tension: 0.3, fill: true,
            },
            {
                label: 'Member Aktif',
                data: <?= json_encode($trendAktif) ?>,
                borderColor: '#06b6d4',
                backgroundColor: 'transparent',
                pointRadius: 4, tension: 0.3, borderDash: [3,2],
            },
            <?php if (!empty($voucherItems)): ?>
            {
                label: 'Voucher Tersebar',
                data: <?= json_encode($trendSebar) ?>,
                borderColor: '#f59e0b',
                backgroundColor: 'transparent',
                pointRadius: 4, tension: 0.3, borderDash: [4,3],
            },
            {
                label: 'Voucher Terpakai',
                data: <?= json_encode($trendTerpakai) ?>,
                borderColor: '#10b981',
                backgroundColor: 'transparent',
                pointRadius: 4, tension: 0.3,
            },
            <?php endif; ?>
            <?php if (!empty($hadiahItems)): ?>
            {
                label: 'Hadiah Dibagikan',
                data: <?= json_encode($trendHadiah) ?>,
                borderColor: '#ef4444',
                backgroundColor: 'transparent',
                pointRadius: 4, tension: 0.3, borderDash: [2,2],
            },
            <?php endif; ?>
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } },
        scales: {
            x: { ticks: { font: { size: 10 } } },
            y: { beginAtZero: true, ticks: { font: { size: 10 } } }
        }
    }
});
</script>
<?php endif; ?>
<?php if (!empty($dailyData)): ?>
<script>
new Chart(document.getElementById('dailyChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($dailyLabels) ?>,
        datasets: [
            { label: 'Member Baru',   data: <?= json_encode($dailyMember) ?>, backgroundColor: 'rgba(99,102,241,.75)',  borderRadius: 3 },
            { label: 'Member Aktif',  data: <?= json_encode($dailyAktif) ?>,  backgroundColor: 'rgba(6,182,212,.75)',   borderRadius: 3 },
            <?php if (!empty($voucherItems)): ?>
            { label: 'Voucher Tersebar', data: <?= json_encode($dailySebar) ?>, backgroundColor: 'rgba(245,158,11,.75)', borderRadius: 3 },
            { label: 'Voucher Terpakai', data: <?= json_encode($dailyPakai) ?>, backgroundColor: 'rgba(16,185,129,.75)', borderRadius: 3 },
            <?php endif; ?>
            <?php if (!empty($hadiahItems)): ?>
            { label: 'Hadiah Dibagikan', data: <?= json_encode($dailyHadiah) ?>, backgroundColor: 'rgba(239,68,68,.75)', borderRadius: 3 },
            <?php endif; ?>
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } },
        scales: {
            x: { ticks: { font: { size: 10 } } },
            y: { beginAtZero: true, ticks: { precision: 0, font: { size: 10 } } }
        }
    }
});
</script>
<?php endif; ?>
<?= $this->endSection() ?>
