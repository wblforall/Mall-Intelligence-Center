<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<style>
.fade-up {
    opacity: 0;
    transform: translateY(14px);
    animation: fadeUpSum .5s cubic-bezier(.22,.68,0,1.2) forwards;
}
@keyframes fadeUpSum {
    to { opacity: 1; transform: translateY(0); }
}
</style>
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php
// Helper: prev / next month
$bulanDt   = \DateTime::createFromFormat('Y-m', $bulan);
$prevBulan = (clone $bulanDt)->modify('-1 month')->format('Y-m');
$nextBulan = (clone $bulanDt)->modify('+1 month')->format('Y-m');
$bulanLabel = $bulanDt->format('F Y');

// Lokalize bulan ke Indonesia
$idBulan = ['January'=>'Januari','February'=>'Februari','March'=>'Maret','April'=>'April',
    'May'=>'Mei','June'=>'Juni','July'=>'Juli','August'=>'Agustus',
    'September'=>'September','October'=>'Oktober','November'=>'November','December'=>'Desember'];
$bulanLabel = strtr($bulanLabel, $idBulan);

// Month labels for trend chart
$trendLabels      = [];
$trendMember      = [];
$trendMemberAktif = [];
$trendSebar       = [];
$trendTerpakai    = [];
$trendHadiah      = [];
$shortBulan = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'];
foreach ($allMonthlyTotals as $row) {
    $dt = \DateTime::createFromFormat('Y-m', $row['bulan']);
    $trendLabels[]      = strtr($dt->format('M Y'), array_combine(array_keys($idBulan), $shortBulan));
    $trendMember[]      = (int)$row['total_jumlah'];
    $trendMemberAktif[] = (int)($row['total_member_aktif'] ?? 0);
    $trendSebar[]       = (int)$row['total_tersebar'];
    $trendTerpakai[]    = (int)$row['total_terpakai'];
    $trendHadiah[]      = (int)($row['total_hadiah'] ?? 0);
}
?>

<!-- Header -->
<div class="d-flex align-items-center gap-2 mb-4 fade-up" style="animation-delay:.05s">
    <a href="<?= base_url('loyalty') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h4 class="fw-bold mb-0">Summary Bulanan — Program Loyalty</h4>
        <small class="text-muted">Agregasi realisasi dari semua program (standalone + event)</small>
    </div>
</div>

<!-- Month Navigator -->
<div class="card mb-4 fade-up" style="animation-delay:.12s">
    <div class="card-body py-2">
        <div class="d-flex align-items-center gap-2">
            <a href="?bulan=<?= $prevBulan ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-chevron-left"></i></a>
            <select class="form-select form-select-sm w-auto" onchange="location.href='?bulan='+this.value">
                <?php foreach ($monthList as $m):
                    $dt  = \DateTime::createFromFormat('Y-m', $m);
                    $lbl = strtr($dt->format('F Y'), $idBulan);
                ?>
                <option value="<?= $m ?>" <?= $m === $bulan ? 'selected' : '' ?>><?= $lbl ?></option>
                <?php endforeach; ?>
            </select>
            <a href="?bulan=<?= $nextBulan ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-chevron-right"></i></a>
            <span class="ms-2 fw-semibold"><?= $bulanLabel ?></span>
            <a href="?bulan=<?= date('Y-m') ?>" class="btn btn-sm btn-outline-primary ms-auto">Bulan Ini</a>
        </div>
    </div>
</div>

<!-- KPI -->
<?php $pctTerpakai = $kpiTersebar > 0 ? round($kpiTerpakai / $kpiTersebar * 100, 1) : 0; ?>
<div class="row g-3 mb-4">
    <div class="col-6 col-xl">
        <div class="card border-primary-subtle h-100 fade-up" style="animation-delay:.18s">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <div class="rounded-2 p-1 bg-primary-subtle"><i class="bi bi-person-plus text-primary fs-5"></i></div>
                    <span class="small text-muted">Member Baru</span>
                </div>
                <div class="fw-bold fs-4 text-primary" data-count="<?= $kpiMember ?>"><?= number_format($kpiMember) ?></div>
                <div class="small text-muted">bulan <?= $bulanLabel ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl">
        <div class="card border-info-subtle h-100 fade-up" style="animation-delay:.26s">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <div class="rounded-2 p-1 bg-info-subtle"><i class="bi bi-person-check text-info fs-5"></i></div>
                    <span class="small text-muted">Member Aktif</span>
                </div>
                <div class="fw-bold fs-4 text-info" data-count="<?= $kpiMemberAktif ?>"><?= number_format($kpiMemberAktif) ?></div>
                <div class="small text-muted">bulan <?= $bulanLabel ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl">
        <div class="card border-warning-subtle h-100 fade-up" style="animation-delay:.34s">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <div class="rounded-2 p-1 bg-warning-subtle"><i class="bi bi-ticket text-warning fs-5"></i></div>
                    <span class="small text-muted">Voucher Tersebar</span>
                </div>
                <div class="fw-bold fs-4 text-warning" data-count="<?= $kpiTersebar ?>"><?= number_format($kpiTersebar) ?></div>
                <div class="small text-muted">bulan <?= $bulanLabel ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl">
        <div class="card border-success-subtle h-100 fade-up" style="animation-delay:.42s">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <div class="rounded-2 p-1 bg-success-subtle"><i class="bi bi-ticket-perforated text-success fs-5"></i></div>
                    <span class="small text-muted">Voucher Dipakai</span>
                </div>
                <div class="fw-bold fs-4 text-success" data-count="<?= $kpiTerpakai ?>"><?= number_format($kpiTerpakai) ?></div>
                <div class="small text-muted"><?= $pctTerpakai ?>% dari tersebar</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl">
        <div class="card border-danger-subtle h-100 fade-up" style="animation-delay:.50s">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <div class="rounded-2 p-1 bg-danger-subtle"><i class="bi bi-gift text-danger fs-5"></i></div>
                    <span class="small text-muted">Hadiah Dibagikan</span>
                </div>
                <div class="fw-bold fs-4 text-danger" data-count="<?= $kpiHadiah ?>"><?= number_format($kpiHadiah) ?></div>
                <div class="small text-muted">bulan <?= $bulanLabel ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl">
        <div class="card border-secondary-subtle h-100 fade-up" style="animation-delay:.58s">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <div class="rounded-2 p-1 bg-secondary-subtle"><i class="bi bi-wallet2 text-secondary fs-5"></i></div>
                    <span class="small text-muted">Total Budget</span>
                </div>
                <div class="fw-bold fs-5 text-danger">Rp <?= number_format($totalBudgetActive,0,',','.') ?></div>
                <?php if ($totalBudget !== $totalBudgetActive): ?>
                <div class="small text-muted">program aktif · total Rp <?= number_format($totalBudget,0,',','.') ?></div>
                <?php else: ?>
                <div class="small text-muted"><?= count($programs) ?> program</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
// Hitung ringkasan pencapaian target semua program untuk bulan ini
$sumPrograms  = 0; $sumAchieved = 0; $sumPartial = 0;
$sumBelum     = 0; $sumNoTarget = 0;
foreach ($programMap as $key => $prog) {
    $isS   = str_starts_with($key, 's_');
    $isEv2 = ($prog['target_type'] ?? '') === 'evoucher';
    $dat2  = $monthlyData[$key] ?? [];
    $mB2   = (int)($dat2['total_jumlah'] ?? 0);
    $mA2   = (int)($dat2['total_member_aktif'] ?? 0);
    $vd2   = $isS ? ($voucherByProgram[$prog['id']] ?? []) : ($evoucherByProgram[$prog['id']] ?? []);
    $vP2   = (int)($vd2['total_terpakai'] ?? 0);
    $vQ2   = 0;
    if ($isS && $isEv2 && ($prog['total_voucher'] ?? 0) > 0) {
        $vQ2 = (int)$prog['total_voucher'];
    } elseif (! $isS && isset($evoucherItemsGrouped[$prog['id']])) {
        $vQ2 = array_sum(array_column($evoucherItemsGrouped[$prog['id']], 'total_diterbitkan'));
    }
    $tgt2 = 0; $ach2 = 0;
    if (($prog['target_peserta'] ?? 0) > 0)     { $tgt2++; if ($mB2 >= (int)$prog['target_peserta'])     $ach2++; }
    if (($prog['target_member_aktif'] ?? 0) > 0) { $tgt2++; if ($mA2 >= (int)$prog['target_member_aktif']) $ach2++; }
    if ($vQ2 > 0)                                { $tgt2++; if ($vP2 >= $vQ2)                              $ach2++; }
    $sumPrograms++;
    if ($tgt2 === 0)        $sumNoTarget++;
    elseif ($ach2 === $tgt2) $sumAchieved++;
    elseif ($ach2 > 0)      $sumPartial++;
    else                    $sumBelum++;
}
$sumWithTarget = $sumPrograms - $sumNoTarget;
?>
<?php if ($sumWithTarget > 0): ?>
<div class="d-flex align-items-center gap-3 mb-4 px-1 flex-wrap fade-up" style="animation-delay:.65s">
    <span class="small fw-semibold text-muted">Status Target Bulan Ini:</span>
    <?php if ($sumAchieved > 0): ?>
    <span class="badge bg-success px-3 py-2" style="font-size:.8rem">
        <i class="bi bi-check-circle-fill me-1"></i><?= $sumAchieved ?> program semua target tercapai
    </span>
    <?php endif; ?>
    <?php if ($sumPartial > 0): ?>
    <span class="badge bg-warning text-dark px-3 py-2" style="font-size:.8rem">
        <i class="bi bi-exclamation-circle-fill me-1"></i><?= $sumPartial ?> program sebagian tercapai
    </span>
    <?php endif; ?>
    <?php if ($sumBelum > 0): ?>
    <span class="badge bg-danger px-3 py-2" style="font-size:.8rem">
        <i class="bi bi-x-circle-fill me-1"></i><?= $sumBelum ?> program belum tercapai
    </span>
    <?php endif; ?>
    <?php if ($sumNoTarget > 0): ?>
    <span class="badge bg-secondary px-3 py-2" style="font-size:.8rem">
        <i class="bi bi-dash-circle me-1"></i><?= $sumNoTarget ?> program tanpa target
    </span>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php $hasChartData = array_sum($dailyMember) + array_sum($dailyTersebar) + array_sum($dailyTerpakai) > 0; ?>

<!-- Daily Chart -->
<div class="card mb-4 fade-up" style="animation-delay:.72s">
    <div class="card-header">
        <span class="fw-semibold small">Realisasi Harian — <?= $bulanLabel ?></span>
    </div>
    <div class="card-body py-3">
        <?php if ($hasChartData): ?>
        <canvas id="dailyChart" height="80"></canvas>
        <?php else: ?>
        <div class="text-center text-muted py-4">
            <i class="bi bi-bar-chart display-4 opacity-25 d-block mb-2"></i>
            Belum ada data di bulan ini
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Split programs into sections
$mapStandaloneActive   = [];
$mapStandaloneInactive = [];
$mapEvent              = [];
foreach ($programMap as $key => $prog) {
    if ($prog['source'] === 'event')                         $mapEvent[$key] = $prog;
    elseif (($prog['status'] ?? 'active') === 'inactive')   $mapStandaloneInactive[$key] = $prog;
    else                                                      $mapStandaloneActive[$key] = $prog;
}

// Compute per-card stats and return as array
$buildCardData = function(string $key, array $prog) use ($monthlyData, $voucherByProgram, $evoucherByProgram, $hadiahByProgram, $ehadiahByProgram, $evoucherItemsGrouped): array {
    $isS     = str_starts_with($key, 's_');
    $isEv    = ($prog['target_type'] ?? '') === 'evoucher';
    $data    = $monthlyData[$key] ?? [];
    $mBaru   = (int)($data['total_jumlah'] ?? 0);
    $mAktif  = (int)($data['total_member_aktif'] ?? 0);
    $vd      = $isS ? ($voucherByProgram[$prog['id']] ?? []) : ($evoucherByProgram[$prog['id']] ?? []);
    $vSebar  = (int)($vd['total_tersebar'] ?? 0);
    $vPakai  = (int)($vd['total_terpakai'] ?? 0);
    $hDibagi = $isS ? (int)($hadiahByProgram[$prog['id']] ?? 0) : (int)($ehadiahByProgram[$prog['id']] ?? 0);
    $vQuota  = 0;
    if ($isS && $isEv && ($prog['total_voucher'] ?? 0) > 0) {
        $vQuota = (int)$prog['total_voucher'];
    } elseif (!$isS && isset($evoucherItemsGrouped[$prog['id']])) {
        $vQuota = array_sum(array_column($evoucherItemsGrouped[$prog['id']], 'total_diterbitkan'));
    }
    $tgt = 0; $ach = 0;
    if (($prog['target_peserta'] ?? 0) > 0)     { $tgt++; if ($mBaru  >= (int)$prog['target_peserta'])      $ach++; }
    if (($prog['target_member_aktif'] ?? 0) > 0) { $tgt++; if ($mAktif >= (int)$prog['target_member_aktif']) $ach++; }
    if ($vQuota > 0)                             { $tgt++; if ($vPakai >= $vQuota)                            $ach++; }
    return compact('isS','isEv','mBaru','mAktif','vSebar','vPakai','hDibagi','vQuota','tgt','ach');
};

// Render a single program card (outputs HTML directly)
$renderCard = function(string $key, array $prog) use ($buildCardData): void {
    $d = $buildCardData($key, $prog);
    extract($d); // isS, isEv, mBaru, mAktif, vSebar, vPakai, hDibagi, vQuota, tgt, ach
    $isInactive  = ($prog['status'] ?? 'active') === 'inactive';
    $hasAnyData  = $mBaru > 0 || $mAktif > 0 || $vSebar > 0 || $vPakai > 0 || $hDibagi > 0;
    $hasContent  = $hasAnyData || $tgt > 0;
    $borderColor = $isS ? 'primary' : 'warning';
    $detailUrl   = base_url('loyalty/detail/' . ($isS ? 's' : 'e') . '/' . $prog['id']);
    $typeLabel   = $prog['target_type'] ?? '';

    // Progress bar helper — returns HTML string
    $bar = function(int $actual, int $target, string $label, string $c1, string $c2) use ($isInactive): string {
        $pct = min(100, round($actual / $target * 100, 1));
        $c   = $pct >= 100 ? $c1 : ($pct >= 60 ? $c2 : ($pct >= 30 ? 'warning' : 'danger'));
        $bw  = $pct > 0 ? $pct . '%' : '0%;min-width:2px';
        return '<div class="d-flex justify-content-between" style="font-size:.65rem">'
             . '<span class="text-muted">' . $label . ': ' . number_format($actual) . ' / ' . number_format($target) . '</span>'
             . '<span class="fw-semibold text-' . $c . '">' . $pct . '%</span>'
             . '</div>'
             . '<div class="progress mb-1" style="height:3px"><div class="progress-bar bg-' . $c . '" style="width:' . $bw . '"></div></div>';
    };
    ?>
    <div class="col-md-6 col-xl-4">
    <div class="card h-100 border-start border-3 border-<?= $borderColor ?><?= $isInactive ? ' opacity-75' : '' ?>">

        <div class="card-header py-2 px-3 d-flex align-items-start justify-content-between gap-2">
            <div class="min-w-0 flex-grow-1">
                <div class="fw-semibold small lh-sm"><?= esc($prog['nama_program']) ?></div>
                <?php if (!$isS && ($prog['event_name'] ?? '')): ?>
                <div class="text-warning-emphasis mt-1" style="font-size:.7rem">
                    <i class="bi bi-calendar-event me-1"></i><?= esc($prog['event_name']) ?>
                </div>
                <?php endif; ?>
            </div>
            <div class="d-flex flex-wrap gap-1 justify-content-end flex-shrink-0">
                <?php if ($typeLabel === 'evoucher'): ?>
                <span class="badge bg-warning-subtle text-warning-emphasis" style="font-size:.6rem">e-Voucher</span>
                <?php elseif ($typeLabel === 'member'): ?>
                <span class="badge bg-primary-subtle text-primary" style="font-size:.6rem">Member</span>
                <?php endif; ?>
                <?php if ($isInactive): ?>
                <span class="badge bg-secondary" style="font-size:.6rem">Non-aktif</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="card-body py-2 px-3">

            <?php if ($tgt > 0): ?>
            <div class="mb-2">
                <?php if ($ach === $tgt): ?>
                <span class="badge bg-success" style="font-size:.68rem"><i class="bi bi-check-circle-fill me-1"></i>Semua target tercapai</span>
                <?php elseif ($ach > 0): ?>
                <span class="badge bg-warning text-dark" style="font-size:.68rem"><i class="bi bi-exclamation-circle-fill me-1"></i><?= $ach ?>/<?= $tgt ?> target tercapai</span>
                <?php else: ?>
                <span class="badge bg-danger" style="font-size:.68rem"><i class="bi bi-x-circle-fill me-1"></i>Belum tercapai</span>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if (!$hasContent): ?>
            <div class="text-center text-muted py-2" style="font-size:.8rem">
                <i class="bi bi-inbox opacity-25 d-block mb-1 fs-4"></i>Belum ada realisasi &amp; target
            </div>
            <?php else: ?>

            <?php
            $showMember  = $mBaru > 0 || $mAktif > 0 || ($prog['target_peserta'] ?? 0) > 0 || ($prog['target_member_aktif'] ?? 0) > 0;
            $showVoucher = $vSebar > 0 || $vPakai > 0 || $vQuota > 0;
            $showHadiah  = $hDibagi > 0;
            ?>

            <?php if ($showMember): ?>
            <div class="mb-2">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-muted fw-semibold" style="font-size:.72rem"><i class="bi bi-person-plus me-1"></i>Member</span>
                    <span style="font-size:.72rem">
                        <span class="text-primary fw-semibold"><?= number_format($mBaru) ?> baru</span>
                        <?php if ($mAktif > 0 || ($prog['target_member_aktif'] ?? 0) > 0): ?>
                        &nbsp;·&nbsp;<span class="text-info fw-semibold"><?= number_format($mAktif) ?> aktif</span>
                        <?php endif; ?>
                    </span>
                </div>
                <?php if (($prog['target_peserta'] ?? 0) > 0): echo $bar($mBaru, (int)$prog['target_peserta'], 'Baru', 'success', 'primary'); endif; ?>
                <?php if (($prog['target_member_aktif'] ?? 0) > 0): echo $bar($mAktif, (int)$prog['target_member_aktif'], 'Aktif', 'success', 'info'); endif; ?>
            </div>
            <?php endif; ?>

            <?php if ($showVoucher): ?>
            <div class="<?= $showMember ? 'border-top pt-2 ' : '' ?>mb-2">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-muted fw-semibold" style="font-size:.72rem"><i class="bi bi-ticket-perforated me-1"></i>Voucher</span>
                    <span style="font-size:.72rem">
                        <?php if ($vSebar > 0): ?><span class="text-warning fw-semibold"><?= number_format($vSebar) ?> sebar</span><?php endif; ?>
                        <?php if ($vPakai > 0): ?><?= $vSebar > 0 ? ' &nbsp;·&nbsp; ' : '' ?><span class="text-success fw-semibold"><?= number_format($vPakai) ?> pakai</span><?php endif; ?>
                    </span>
                </div>
                <?php if ($vQuota > 0): echo $bar($vPakai, $vQuota, 'Terpakai', 'success', 'primary'); endif; ?>
            </div>
            <?php endif; ?>

            <?php if ($showHadiah): ?>
            <div class="<?= ($showMember || $showVoucher) ? 'border-top pt-2 ' : '' ?>">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted fw-semibold" style="font-size:.72rem"><i class="bi bi-gift me-1"></i>Hadiah Dibagikan</span>
                    <span class="text-danger fw-semibold" style="font-size:.72rem"><?= number_format($hDibagi) ?></span>
                </div>
            </div>
            <?php endif; ?>

            <?php endif; // hasContent ?>
        </div>

        <div class="card-footer py-2 px-3 bg-transparent d-flex justify-content-between align-items-center gap-2">
            <?php if ($prog['mekanisme'] ?? ''): ?>
            <small class="text-muted text-truncate" style="font-size:.65rem;max-width:72%"><?= esc($prog['mekanisme']) ?></small>
            <?php else: ?>
            <span></span>
            <?php endif; ?>
            <a href="<?= $detailUrl ?>" class="btn btn-sm btn-outline-secondary flex-shrink-0" style="font-size:.72rem;padding:.2rem .6rem">
                Detail <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>

    </div>
    </div>
    <?php
};
?>

<?php if (!empty($mapStandaloneActive) || !empty($mapStandaloneInactive)): ?>
<div class="d-flex align-items-center gap-2 mb-3">
    <div class="rounded-2 p-1 bg-primary-subtle flex-shrink-0"><i class="bi bi-star-fill text-primary" style="font-size:.85rem"></i></div>
    <h6 class="fw-bold mb-0">Program Loyalty Standalone</h6>
    <span class="badge bg-primary-subtle text-primary"><?= count($mapStandaloneActive) + count($mapStandaloneInactive) ?> program</span>
    <div class="flex-grow-1 border-top ms-1"></div>
</div>
<div class="row g-3 mb-2">
    <?php foreach ($mapStandaloneActive as $key => $prog): $renderCard($key, $prog); endforeach; ?>
</div>
<?php if (!empty($mapStandaloneInactive)): ?>
<div class="d-flex align-items-center gap-2 my-2 ms-1">
    <span class="text-muted" style="font-size:.7rem;font-weight:600;letter-spacing:.07em;text-transform:uppercase">Non-aktif</span>
    <div class="flex-grow-1 border-top"></div>
</div>
<div class="row g-3 mb-2">
    <?php foreach ($mapStandaloneInactive as $key => $prog): $renderCard($key, $prog); endforeach; ?>
</div>
<?php endif; ?>
<?php endif; ?>

<?php if (!empty($mapEvent)): ?>
<div class="d-flex align-items-center gap-2 mb-3 mt-3">
    <div class="rounded-2 p-1 bg-warning-subtle flex-shrink-0"><i class="bi bi-calendar-event text-warning-emphasis" style="font-size:.85rem"></i></div>
    <h6 class="fw-bold mb-0">Support Event</h6>
    <span class="badge bg-warning-subtle text-warning-emphasis"><?= count($mapEvent) ?> program</span>
    <div class="flex-grow-1 border-top ms-1"></div>
</div>
<div class="row g-3 mb-2">
    <?php foreach ($mapEvent as $key => $prog): $renderCard($key, $prog); endforeach; ?>
</div>
<?php endif; ?>

<!-- Voucher & Hadiah Item Lists -->
<?php
$flatVouchers = [];
// Standalone voucher items
foreach ($voucherItemsGrouped as $progId => $items) {
    $prog = null;
    foreach ($programMap as $p) { if ($p['id'] == $progId && $p['source'] === 'standalone') { $prog = $p; break; } }
    foreach ($items as $vi) {
        $vd = $vMonthly[$vi['id']] ?? ['total_tersebar' => 0, 'total_terpakai' => 0];
        $flatVouchers[] = ['item' => $vi, 'prog' => $prog, 'vd' => $vd];
    }
}
// Event voucher items
foreach ($evoucherItemsGrouped as $progId => $items) {
    $prog = null;
    foreach ($programMap as $p) { if ($p['id'] == $progId && $p['source'] === 'event') { $prog = $p; break; } }
    foreach ($items as $vi) {
        $vd = $evMonthly[$vi['id']] ?? ['total_tersebar' => 0, 'total_terpakai' => 0];
        $flatVouchers[] = ['item' => $vi, 'prog' => $prog, 'vd' => $vd];
    }
}
$hasVouchers = !empty($flatVouchers);

$flatHadiah = [];
// Standalone hadiah items
foreach ($hadiahItemsGrouped as $progId => $items) {
    $prog = null;
    foreach ($programMap as $p) { if ($p['id'] == $progId && $p['source'] === 'standalone') { $prog = $p; break; } }
    foreach ($items as $hi) {
        $flatHadiah[] = [
            'item'     => $hi,
            'prog'     => $prog,
            'dibagikan'=> (int)($hMonthly[$hi['id']] ?? 0),
            'totalAll' => (int)($hAllTime[$hi['id']]  ?? 0),
        ];
    }
}
// Event hadiah items
foreach ($ehadiahItemsGrouped as $progId => $items) {
    $prog = null;
    foreach ($programMap as $p) { if ($p['id'] == $progId && $p['source'] === 'event') { $prog = $p; break; } }
    foreach ($items as $hi) {
        $flatHadiah[] = [
            'item'     => $hi,
            'prog'     => $prog,
            'dibagikan'=> (int)($ehMonthly[$hi['id']] ?? 0),
            'totalAll' => (int)($ehAllTime[$hi['id']]  ?? 0),
        ];
    }
}
$hasHadiah = !empty($flatHadiah);
?>
<?php if ($hasVouchers || $hasHadiah): ?>
<div class="row g-3 mb-4">

<?php if ($hasVouchers): ?>
<div class="col-lg-<?= $hasHadiah ? '7' : '12' ?>">
<div class="card">
    <div class="card-header">
        <span class="fw-semibold small"><i class="bi bi-ticket-perforated me-2 text-warning"></i>List Voucher — <?= $bulanLabel ?></span>
    </div>
    <div class="table-responsive">
    <table class="table table-sm mb-0">
    <thead class="table-light">
        <tr>
            <th class="ps-3">Voucher</th>
            <th class="text-end">Nilai</th>
            <th class="text-end">Diterbitkan</th>
            <th class="text-end">Tersebar</th>
            <th class="text-end pe-3">Terpakai</th>
        </tr>
    </thead>
    <tbody>
    <?php
    $tDiterbitkan = 0; $tTersebar = 0; $tTerpakai = 0;
    foreach ($flatVouchers as $row):
        $vi  = $row['item'];
        $vd  = $row['vd'];
        $pct = $vi['total_diterbitkan'] > 0
            ? min(100, round((int)$vd['total_terpakai'] / $vi['total_diterbitkan'] * 100, 1))
            : 0;
        $color = $pct >= 100 ? 'success' : ($pct >= 60 ? 'primary' : ($pct >= 30 ? 'warning' : 'danger'));
        $tDiterbitkan += (int)$vi['total_diterbitkan'];
        $tTersebar    += (int)$vd['total_tersebar'];
        $tTerpakai    += (int)$vd['total_terpakai'];
    ?>
    <tr>
        <td class="ps-3">
            <div class="small fw-medium"><?= esc($vi['nama_voucher']) ?></div>
            <?php if ($row['prog']): ?>
            <div class="text-muted" style="font-size:.68rem">
                <?= esc($row['prog']['nama_program']) ?>
                <?php if (($row['prog']['source'] ?? '') === 'event' && ($row['prog']['event_name'] ?? '')): ?>
                <span class="text-warning-emphasis"> · <?= esc($row['prog']['event_name']) ?></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <?php if ($pct > 0): ?>
            <div class="progress mt-1" style="height:3px">
                <div class="progress-bar bg-<?= $color ?>" style="width:<?= $pct ?>%"></div>
            </div>
            <div class="text-muted" style="font-size:.65rem"><?= $pct ?>% terpakai</div>
            <?php endif; ?>
            <?php if (($vi['target_penyerapan'] ?? null) !== null && $vi['target_penyerapan'] !== ''): ?>
            <div class="text-muted" style="font-size:.65rem">Target penyerapan: <?= (float)$vi['target_penyerapan'] ?>%</div>
            <?php endif; ?>
        </td>
        <td class="text-end align-middle small text-muted">Rp <?= number_format($vi['nilai_voucher'],0,',','.') ?></td>
        <td class="text-end align-middle small"><?= number_format($vi['total_diterbitkan']) ?></td>
        <td class="text-end align-middle small <?= (int)$vd['total_tersebar'] > 0 ? 'fw-semibold text-warning' : 'text-muted' ?>">
            <?= (int)$vd['total_tersebar'] > 0 ? number_format($vd['total_tersebar']) : '—' ?>
        </td>
        <td class="text-end pe-3 align-middle small <?= (int)$vd['total_terpakai'] > 0 ? 'fw-semibold text-success' : 'text-muted' ?>">
            <?= (int)$vd['total_terpakai'] > 0 ? number_format($vd['total_terpakai']) : '—' ?>
        </td>
    </tr>
    <?php endforeach; ?>
    <tr class="table-light fw-semibold">
        <td class="ps-3 small">Total</td>
        <td class="text-end small text-muted">—</td>
        <td class="text-end small"><?= number_format($tDiterbitkan) ?></td>
        <td class="text-end small text-warning"><?= $tTersebar > 0 ? number_format($tTersebar) : '—' ?></td>
        <td class="text-end pe-3 small text-success"><?= $tTerpakai > 0 ? number_format($tTerpakai) : '—' ?></td>
    </tr>
    </tbody>
    </table>
    </div>
</div>
</div>
<?php endif; ?>

<?php if ($hasHadiah): ?>
<div class="col-lg-<?= $hasVouchers ? '5' : '12' ?>">
<div class="card">
    <div class="card-header">
        <span class="fw-semibold small"><i class="bi bi-gift me-2 text-danger"></i>List Barang/Hadiah — <?= $bulanLabel ?></span>
    </div>
    <div class="table-responsive">
    <table class="table table-sm mb-0">
    <thead class="table-light">
        <tr>
            <th class="ps-3">Barang</th>
            <th class="text-end">Stok</th>
            <th class="text-end">Nilai</th>
            <th class="text-end pe-3">Dibagikan</th>
        </tr>
    </thead>
    <tbody>
    <?php
    $tStok = 0; $tDibagikan = 0;
    foreach ($flatHadiah as $row):
        $hi        = $row['item'];
        $dibagikan = $row['dibagikan'];    // bulan ini
        $totalAll  = $row['totalAll'];     // all-time
        // Progress bar: all-time dibagikan vs stok (seperti di main index)
        $pct       = $hi['stok'] > 0 ? min(100, round($totalAll / $hi['stok'] * 100, 1)) : 0;
        $color     = $pct >= 100 ? 'danger' : ($pct >= 60 ? 'warning' : ($pct >= 30 ? 'primary' : 'success'));
        $tStok      += (int)$hi['stok'];
        $tDibagikan += $dibagikan;
    ?>
    <tr>
        <td class="ps-3">
            <div class="small fw-medium"><?= esc($hi['nama_hadiah']) ?></div>
            <?php if ($row['prog']): ?>
            <div class="text-muted" style="font-size:.68rem">
                <?= esc($row['prog']['nama_program']) ?>
                <?php if (($row['prog']['source'] ?? '') === 'event' && ($row['prog']['event_name'] ?? '')): ?>
                <span class="text-warning-emphasis"> · <?= esc($row['prog']['event_name']) ?></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <?php if ($pct > 0): ?>
            <div class="progress mt-1" style="height:3px">
                <div class="progress-bar bg-<?= $color ?>" style="width:<?= $pct ?>%"></div>
            </div>
            <div class="text-muted" style="font-size:.65rem">
                <?= number_format($totalAll) ?>/<?= number_format($hi['stok']) ?> dibagikan (<?= $pct ?>%)
                <?php if ($dibagikan > 0): ?>· <span class="text-danger"><?= number_format($dibagikan) ?> bulan ini</span><?php endif; ?>
            </div>
            <?php elseif ($dibagikan > 0): ?>
            <div class="text-muted" style="font-size:.65rem"><span class="text-danger"><?= number_format($dibagikan) ?> bulan ini</span></div>
            <?php endif; ?>
        </td>
        <td class="text-end align-middle small"><?= number_format($hi['stok']) ?></td>
        <td class="text-end align-middle small text-muted">Rp <?= number_format($hi['nilai_satuan'],0,',','.') ?></td>
        <td class="text-end pe-3 align-middle small <?= $dibagikan > 0 ? 'fw-semibold text-danger' : 'text-muted' ?>">
            <?= $dibagikan > 0 ? number_format($dibagikan) : '—' ?>
        </td>
    </tr>
    <?php endforeach; ?>
    <tr class="table-light fw-semibold">
        <td class="ps-3 small">Total</td>
        <td class="text-end small"><?= number_format($tStok) ?></td>
        <td class="text-end small text-muted">—</td>
        <td class="text-end pe-3 small text-danger"><?= $tDibagikan > 0 ? number_format($tDibagikan) : '—' ?></td>
    </tr>
    </tbody>
    </table>
    </div>
</div>
</div>
<?php endif; ?>

</div>
<?php endif; ?>

<!-- Monthly Trend Table -->
<div class="card fade-up" style="animation-delay:.1s">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span class="fw-semibold small"><i class="bi bi-table me-2"></i>Tren Bulanan — <?= date('Y') ?></span>
    </div>
    <?php if (empty($allMonthlyTotals)): ?>
    <div class="card-body text-center text-muted py-4">Belum ada data realisasi.</div>
    <?php else: ?>

    <!-- Trend chart -->
    <div class="card-body border-bottom pb-3">
        <canvas id="trendChart" height="100"></canvas>
    </div>

    <div class="table-responsive">
    <table class="table table-sm mb-0">
    <thead class="table-light">
        <tr>
            <th class="ps-3">Bulan</th>
            <th class="text-end">Member Baru</th>
            <th class="text-end">Member Aktif</th>
            <th class="text-end">Voucher Sebar</th>
            <th class="text-end">Voucher Pakai</th>
            <th class="text-end pe-3">Hadiah</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach (array_reverse($allMonthlyTotals) as $row):
        $dt  = \DateTime::createFromFormat('Y-m', $row['bulan']);
        $lbl = strtr($dt->format('F Y'), $idBulan);
        $isSelected = $row['bulan'] === $bulan;
        $mAktifRow  = (int)($row['total_member_aktif'] ?? 0);
        $hadiahRow  = (int)($row['total_hadiah'] ?? 0);
    ?>
    <tr class="<?= $isSelected ? 'table-primary' : '' ?>">
        <td class="ps-3 small">
            <a href="?bulan=<?= $row['bulan'] ?>" class="<?= $isSelected ? 'fw-bold text-primary' : 'text-body' ?> text-decoration-none">
                <?= $lbl ?>
                <?php if ($isSelected): ?><i class="bi bi-arrow-left-short text-primary"></i><?php endif; ?>
            </a>
        </td>
        <td class="text-end small <?= (int)$row['total_jumlah'] > 0 ? 'fw-semibold text-primary' : 'text-muted' ?>">
            <?= (int)$row['total_jumlah'] > 0 ? number_format($row['total_jumlah']) : '—' ?>
        </td>
        <td class="text-end small <?= $mAktifRow > 0 ? 'fw-semibold text-info' : 'text-muted' ?>">
            <?= $mAktifRow > 0 ? number_format($mAktifRow) : '—' ?>
        </td>
        <td class="text-end small <?= (int)$row['total_tersebar'] > 0 ? 'fw-semibold text-warning' : 'text-muted' ?>">
            <?= (int)$row['total_tersebar'] > 0 ? number_format($row['total_tersebar']) : '—' ?>
        </td>
        <td class="text-end small <?= (int)$row['total_terpakai'] > 0 ? 'fw-semibold text-success' : 'text-muted' ?>">
            <?= (int)$row['total_terpakai'] > 0 ? number_format($row['total_terpakai']) : '—' ?>
        </td>
        <td class="text-end pe-3 small <?= $hadiahRow > 0 ? 'fw-semibold text-danger' : 'text-muted' ?>">
            <?= $hadiahRow > 0 ? number_format($hadiahRow) : '—' ?>
        </td>
    </tr>
    <?php endforeach; ?>
    <!-- Total row -->
    <?php
    $grandMember      = array_sum(array_column($allMonthlyTotals, 'total_jumlah'));
    $grandMemberAktif = array_sum(array_column($allMonthlyTotals, 'total_member_aktif'));
    $grandTersebar    = array_sum(array_column($allMonthlyTotals, 'total_tersebar'));
    $grandTerpakai    = array_sum(array_column($allMonthlyTotals, 'total_terpakai'));
    $grandHadiah      = array_sum(array_column($allMonthlyTotals, 'total_hadiah'));
    ?>
    <tr class="table-light fw-bold">
        <td class="ps-3 small">Total</td>
        <td class="text-end small text-primary"><?= number_format($grandMember) ?></td>
        <td class="text-end small text-info"><?= $grandMemberAktif > 0 ? number_format($grandMemberAktif) : '—' ?></td>
        <td class="text-end small text-warning"><?= number_format($grandTersebar) ?></td>
        <td class="text-end small text-success"><?= number_format($grandTerpakai) ?></td>
        <td class="text-end pe-3 small text-danger"><?= $grandHadiah > 0 ? number_format($grandHadiah) : '—' ?></td>
    </tr>
    </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
// KPI count-up
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

// Per-program card stagger
document.querySelectorAll('.col-md-6.col-xl-4').forEach((col, i) => {
    const card = col.querySelector('.card');
    if (!card) return;
    card.style.opacity = '0';
    card.style.transform = 'translateY(18px)';
    setTimeout(() => {
        card.style.transition = 'opacity .45s ease, transform .45s ease';
        card.style.opacity = '1';
        card.style.transform = 'translateY(0)';
    }, 200 + i * 75);
});

// Progress bar animation
document.querySelectorAll('.progress-bar').forEach((bar, i) => {
    const target = bar.style.width;
    if (!target || parseFloat(target) === 0) return;
    bar.style.width = '0';
    setTimeout(() => {
        bar.style.transition = 'width .7s ease';
        bar.style.width = target;
    }, 500 + i * 50);
});
</script>
<script>
<?php if ($hasChartData ?? false): ?>
new Chart(document.getElementById('dailyChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($chartDates) ?>,
        datasets: [
            {
                label: 'Member Baru',
                data: <?= json_encode($dailyMember) ?>,
                backgroundColor: 'rgba(99,102,241,.7)',
                borderRadius: 3,
                order: 1,
            },
            {
                label: 'Voucher Tersebar',
                data: <?= json_encode($dailyTersebar) ?>,
                backgroundColor: 'rgba(245,158,11,.6)',
                borderRadius: 3,
                order: 2,
            },
            {
                label: 'Voucher Dipakai',
                data: <?= json_encode($dailyTerpakai) ?>,
                type: 'line',
                borderColor: '#10b981',
                backgroundColor: 'rgba(16,185,129,.1)',
                pointRadius: 3,
                tension: 0.3,
                order: 0,
            },
        ]
    },
    options: {
        responsive: true,
        animation: {
            duration: 600,
            easing: 'easeOutQuart',
            delay: ctx => ctx.type === 'data' ? ctx.dataIndex * 22 : 0,
        },
        plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } },
        scales: {
            x: { ticks: { font: { size: 10 } } },
            y: { beginAtZero: true, ticks: { font: { size: 10 } } }
        }
    }
});
<?php endif; ?>

<?php if (!empty($allMonthlyTotals)): ?>
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
                pointRadius: 4,
                tension: 0.3,
                fill: true,
            },
            {
                label: 'Member Aktif',
                data: <?= json_encode($trendMemberAktif) ?>,
                borderColor: '#06b6d4',
                backgroundColor: 'transparent',
                pointRadius: 4,
                tension: 0.3,
                borderDash: [3, 2],
            },
            {
                label: 'Voucher Tersebar',
                data: <?= json_encode($trendSebar) ?>,
                borderColor: '#f59e0b',
                backgroundColor: 'transparent',
                pointRadius: 4,
                tension: 0.3,
                borderDash: [4, 3],
            },
            {
                label: 'Voucher Dipakai',
                data: <?= json_encode($trendTerpakai) ?>,
                borderColor: '#10b981',
                backgroundColor: 'transparent',
                pointRadius: 4,
                tension: 0.3,
            },
            {
                label: 'Hadiah Dibagikan',
                data: <?= json_encode($trendHadiah) ?>,
                borderColor: '#ef4444',
                backgroundColor: 'transparent',
                pointRadius: 4,
                tension: 0.3,
                borderDash: [2, 2],
            },
        ]
    },
    options: {
        responsive: true,
        animation: {
            duration: 1100,
            easing: 'easeInOutCubic',
            delay: ctx => ctx.type === 'data' ? ctx.datasetIndex * 180 + ctx.dataIndex * 30 : 0,
        },
        plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } },
        scales: {
            x: { ticks: { font: { size: 10 } } },
            y: { beginAtZero: true, ticks: { font: { size: 10 } } }
        }
    }
});
<?php endif; ?>
</script>
<?= $this->endSection() ?>
