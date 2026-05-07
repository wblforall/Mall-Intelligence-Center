<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<style>
.fade-up {
    opacity: 0;
    transform: translateY(16px);
    animation: fadeUpLoy .5s cubic-bezier(.22,.68,0,1.2) forwards;
}
@keyframes fadeUpLoy {
    to { opacity: 1; transform: translateY(0); }
}
</style>
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php
function slPct(int $actual, int $target): float {
    return $target > 0 ? min(100, round($actual / $target * 100, 1)) : 0;
}

$standaloneActive   = array_filter($programs, fn($p) => $p['source'] === 'standalone' && $p['status'] === 'active');
$standaloneInactive = array_filter($programs, fn($p) => $p['source'] === 'standalone' && $p['status'] === 'inactive');
$eventOpen          = array_filter($programs, fn($p) => $p['source'] === 'event' && $p['status'] === 'active');
$eventClosed        = array_filter($programs, fn($p) => $p['source'] === 'event' && $p['status'] === 'inactive');
$eventPrograms      = $eventOpen;
?>

<!-- Header -->
<div class="d-flex align-items-center gap-2 mb-4 fade-up" style="animation-delay:.05s">
    <div class="rounded-2 d-flex align-items-center justify-content-center flex-shrink-0"
         style="width:36px;height:36px;background:rgba(99,102,241,.15)">
        <i class="bi bi-star-fill" style="color:var(--bs-primary);font-size:1rem"></i>
    </div>
    <div>
        <h4 class="fw-bold mb-0">Program Loyalty</h4>
        <small class="text-muted">Standalone &amp; dari event — Member, e-Voucher &amp; Hadiah</small>
    </div>
    <div class="d-flex gap-2 ms-auto">
        <a href="<?= base_url('loyalty/summary') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-bar-chart-line me-1"></i>Summary Bulanan
        </a>
        <?php if ($canEdit): ?>
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addProgramModal">
            <i class="bi bi-plus-lg me-1"></i>Tambah Program
        </button>
        <?php endif; ?>
    </div>
</div>

<!-- KPI -->
<div class="row g-3 mb-4">
    <div class="col-sm-4">
        <div class="card border-primary-subtle h-100 fade-up" style="animation-delay:.12s">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <div class="rounded-2 p-1 bg-primary-subtle"><i class="bi bi-star text-primary fs-5"></i></div>
                    <span class="small text-muted">Program Aktif</span>
                </div>
                <div class="fw-bold fs-4 text-primary" data-count="<?= $activeCount ?>"><?= $activeCount ?></div>
                <div class="small text-muted">dari <?= count($programs) ?> total</div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card border-success-subtle h-100 fade-up" style="animation-delay:.22s">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <div class="rounded-2 p-1 bg-success-subtle"><i class="bi bi-person-plus text-success fs-5"></i></div>
                    <span class="small text-muted">Total Member Baru</span>
                </div>
                <div class="fw-bold fs-4 text-success" data-count="<?= $totalMemberKpi ?>"><?= number_format($totalMemberKpi) ?></div>
                <?php if ($targetMemberKpi > 0): ?>
                <div class="small text-muted">target <?= number_format($targetMemberKpi) ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card border-warning-subtle h-100 fade-up" style="animation-delay:.32s">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <div class="rounded-2 p-1 bg-warning-subtle"><i class="bi bi-ticket-perforated text-warning fs-5"></i></div>
                    <span class="small text-muted">e-Voucher Dipakai</span>
                </div>
                <div class="fw-bold fs-4 text-warning" data-count="<?= $totalTerpakaiKpi ?>"><?= number_format($totalTerpakaiKpi) ?></div>
                <div class="small text-muted">dari semua program aktif</div>
            </div>
        </div>
    </div>
</div>

<?php if (empty($programs)): ?>
<div class="card"><div class="card-body text-center py-5 text-muted">
    <i class="bi bi-star display-4 d-block mb-2 opacity-25"></i>
    <p class="mb-0">Belum ada program loyalty. Klik "Tambah Program" untuk memulai.</p>
</div></div>
<?php else: ?>

<?php
function renderLoyaltyCard(array $p, array $realisasi, bool $canEdit, array $hadiahItems = [], array $hadiahRealisasi = [], array $voucherItems = [], array $voucherRealisasi = [], bool $isAdmin = false): void {
    $pid          = $p['id'];
    $isStandalone = $p['source'] === 'standalone';
    $key          = ($isStandalone ? 's_' : 'e_') . $pid;
    $rData        = $realisasi[$key] ?? ['total' => 0, 'total_aktif' => 0, 'entries' => []];
    $rTotal       = (int)$rData['total'];
    $rTotalAktif  = (int)($rData['total_aktif'] ?? 0);
    $entries      = $rData['entries'] ?? [];
    $isInactive   = ($p['status'] ?? 'active') === 'inactive';
    $isLocked     = $isStandalone && (bool)($p['locked'] ?? false);
    $canManage    = $canEdit && $isStandalone && ! $isLocked;
    $myItems        = $hadiahItems[$pid] ?? [];
    $myVoucherItems = $voucherItems[$pid] ?? [];
    $isEvoucher     = ($p['target_type'] ?? '') === 'evoucher';

    // Budget planned from items
    $vBudgetPlan = 0;
    foreach ($myVoucherItems as $vi) { $vBudgetPlan += (int)$vi['total_diterbitkan'] * (int)$vi['nilai_voucher']; }
    $hBudgetPlan = 0;
    foreach ($myItems as $hi) { $hBudgetPlan += (int)$hi['stok'] * (int)$hi['nilai_satuan']; }
    $budgetPlan = $vBudgetPlan + $hBudgetPlan;

    // Budget realisasi from items
    $vBudgetReal = 0;
    foreach ($myVoucherItems as $vi) { $vBudgetReal += (int)($voucherRealisasi[$vi['id']]['total_terpakai'] ?? 0) * (int)$vi['nilai_voucher']; }
    $hBudgetReal = 0;
    foreach ($myItems as $hi) { $hBudgetReal += (int)($hadiahRealisasi[$hi['id']]['total'] ?? 0) * (int)$hi['nilai_satuan']; }
    $budgetReal = $vBudgetReal + $hBudgetReal;

    $anchorId = 'program-' . ($isStandalone ? 's' : 'e') . '-' . $pid;
?>
<div class="card mb-4 <?= $isInactive ? 'opacity-75' : '' ?> border-start border-4 <?= $isStandalone ? 'border-primary' : 'border-warning' ?>" id="<?= $anchorId ?>">
    <?php if ($isStandalone): ?>
    <div class="card-header py-1 px-3 bg-primary-subtle d-flex align-items-center gap-2" style="font-size:.75rem">
        <i class="bi bi-star-fill text-primary"></i>
        <span class="fw-semibold text-primary">Program Loyalty</span>
        <?php if ($isLocked): ?><span class="badge bg-danger ms-1"><i class="bi bi-lock-fill me-1"></i>Terkunci</span><?php endif; ?>
        <?php if ($isInactive): ?><span class="badge bg-secondary ms-auto">Non-aktif</span><?php endif; ?>
    </div>
    <?php else: ?>
    <div class="card-header py-1 px-3 bg-warning-subtle d-flex align-items-center gap-2" style="font-size:.75rem">
        <i class="bi bi-calendar-event text-warning-emphasis"></i>
        <span class="fw-semibold text-warning-emphasis">Program Event</span>
        <span class="text-warning-emphasis ms-1">· <?= esc($p['event_name']) ?></span>
        <?php if ($isInactive): ?><span class="badge bg-secondary ms-auto">Non-aktif</span><?php endif; ?>
    </div>
    <?php endif; ?>
    <div class="card-body pb-2">
        <div class="d-flex justify-content-between align-items-start gap-3">
            <div class="flex-grow-1 min-w-0">
                <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                    <span class="fw-semibold"><?= esc($p['nama_program']) ?></span>
                </div>
                <?php
                if ($isStandalone && ($p['tanggal_mulai'] ?? null)) {
                    $periodStr = date('d M Y', strtotime($p['tanggal_mulai']));
                    if ($p['tanggal_selesai'] && $p['tanggal_selesai'] !== $p['tanggal_mulai'])
                        $periodStr .= ' – ' . date('d M Y', strtotime($p['tanggal_selesai']));
                    $timeStr = '';
                    if ($p['jam_mulai'])   $timeStr = ' · ' . substr($p['jam_mulai'], 0, 5);
                    if ($p['jam_selesai']) $timeStr .= '–' . substr($p['jam_selesai'], 0, 5);
                    echo '<div class="small text-muted mb-1"><i class="bi bi-calendar-range me-1"></i>' . $periodStr . $timeStr . '</div>';
                }
                ?>
                <?php if ($p['mekanisme']): ?>
                <p class="small text-muted mb-2" style="white-space:pre-line"><?= esc($p['mekanisme']) ?></p>
                <?php endif; ?>
                <div class="d-flex flex-wrap gap-3 small text-muted">
                    <?php if ($p['target_peserta']): ?>
                    <span><i class="bi bi-bullseye me-1"></i>Target Baru: <strong class="text-body"><?= number_format($p['target_peserta']) ?></strong></span>
                    <?php endif; ?>
                    <?php if ($p['target_member_aktif'] ?? 0): ?>
                    <span><i class="bi bi-bullseye me-1"></i>Target Aktif: <strong class="text-body"><?= number_format($p['target_member_aktif']) ?></strong></span>
                    <?php endif; ?>
                    <?php if ($budgetPlan > 0): ?>
                    <span><i class="bi bi-cash me-1"></i>Budget: <strong class="text-success">Rp <?= number_format($budgetPlan,0,',','.') ?></strong></span>
                    <?php elseif ((int)($p['budget'] ?? 0) > 0): ?>
                    <span><i class="bi bi-cash me-1"></i>Budget: <strong class="text-success">Rp <?= number_format($p['budget'],0,',','.') ?></strong></span>
                    <?php endif; ?>
                    <?php if ($p['catatan']): ?>
                    <span><i class="bi bi-sticky me-1"></i><?= esc($p['catatan']) ?></span>
                    <?php endif; ?>
                </div>

                <?php if ($budgetReal > 0):
                    $bPlan  = $budgetPlan ?: (int)($p['budget'] ?? 0);
                    $bPct   = $bPlan > 0 ? min(100, round($budgetReal / $bPlan * 100, 1)) : null;
                    $bColor = $budgetReal <= $bPlan ? 'success' : 'danger';
                ?>
                <div class="mt-2 pt-2 border-top">
                    <div class="d-flex justify-content-between align-items-center small mb-1">
                        <span class="text-muted fw-semibold"><i class="bi bi-receipt me-1"></i>Budget Realisasi</span>
                        <span class="fw-bold text-<?= $bColor ?>">Rp <?= number_format($budgetReal,0,',','.') ?></span>
                    </div>
                    <?php if ($bPct !== null): ?>
                    <div class="progress" style="height:4px">
                        <div class="progress-bar bg-<?= $bColor ?>" style="width:<?= min(100,$bPct) ?>%"></div>
                    </div>
                    <div class="text-muted mt-1" style="font-size:.7rem">
                        <?= $bPct ?>% dari budget Rp <?= number_format($bPlan,0,',','.') ?>
                        <?= $budgetReal > $bPlan ? ' <span class="text-danger fw-semibold">· over budget</span>' : '' ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="d-flex gap-1 flex-shrink-0">
                <a href="<?= base_url('loyalty/detail/' . ($isStandalone ? 's' : 'e') . '/' . $pid) ?>"
                   class="btn btn-sm btn-outline-primary" title="Lihat Laporan Detail">
                    <i class="bi bi-bar-chart-line"></i>
                </a>
                <?php if ($isStandalone && $canEdit): ?>
                <?php if ($isLocked): ?>
                <?php if ($isAdmin): ?>
                <form method="POST" action="<?= base_url('loyalty/'.$pid.'/unlock') ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Buka Kunci">
                        <i class="bi bi-unlock-fill"></i>
                    </button>
                </form>
                <?php endif; ?>
                <?php else: ?>
                <form method="POST" action="<?= base_url('loyalty/'.$pid.'/toggle') ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-sm <?= $isInactive ? 'btn-outline-success' : 'btn-outline-secondary' ?>"
                        title="<?= $isInactive ? 'Aktifkan' : 'Non-aktifkan' ?>">
                        <i class="bi bi-<?= $isInactive ? 'play-circle' : 'pause-circle' ?>"></i>
                    </button>
                </form>
                <form method="POST" action="<?= base_url('loyalty/'.$pid.'/lock') ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-sm btn-outline-secondary" title="Kunci Program"
                        onclick="return confirm('Kunci program ini? Data tidak bisa diedit setelah dikunci.')">
                        <i class="bi bi-lock"></i>
                    </button>
                </form>
                <button class="btn btn-sm btn-outline-secondary edit-btn"
                    data-id="<?= $pid ?>"
                    data-nama="<?= esc($p['nama_program'], 'attr') ?>"
                    data-tanggal-mulai="<?= $p['tanggal_mulai'] ?? '' ?>"
                    data-tanggal-selesai="<?= $p['tanggal_selesai'] ?? '' ?>"
                    data-jam-mulai="<?= substr($p['jam_mulai'] ?? '', 0, 5) ?>"
                    data-jam-selesai="<?= substr($p['jam_selesai'] ?? '', 0, 5) ?>"
                    data-mekanisme="<?= esc($p['mekanisme'], 'attr') ?>"
                    data-target="<?= $p['target_peserta'] ?>"
                    data-target-aktif="<?= $p['target_member_aktif'] ?? '' ?>"
                    data-catatan="<?= esc($p['catatan'], 'attr') ?>">
                    <i class="bi bi-pencil"></i>
                </button>
                <form method="POST" action="<?= base_url('loyalty/'.$pid.'/delete') ?>"
                    onsubmit="return confirm('Hapus program beserta semua data realisasinya?')">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                </form>
                <?php endif; /* isLocked else */ ?>
                <?php elseif (!$isStandalone): ?>
                <a href="<?= base_url('events/'.$p['event_id'].'/loyalty') ?>" class="btn btn-sm btn-outline-secondary" title="Kelola di event">
                    <i class="bi bi-box-arrow-up-right"></i>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if (!$isStandalone): ?>
    <div class="border-top px-3 py-2 text-center">
        <a href="<?= base_url('events/'.$p['event_id'].'/loyalty') ?>" class="small text-muted">
            <i class="bi bi-box-arrow-up-right me-1"></i>Kelola di halaman event
        </a>
    </div>

    <?php else: ?>

    <!-- Section: Member -->
    <?php $showMember = $canEdit || !empty($entries); ?>
    <?php if ($showMember): ?>
    <div class="border-top">
        <div class="px-3 py-2 bg-primary-subtle d-flex justify-content-between align-items-center">
            <span class="small fw-semibold text-primary">
                <i class="bi bi-person-plus me-1"></i>Member
                <?php if ($rTotal > 0): ?>
                <span class="badge bg-primary text-white ms-1"><?= number_format($rTotal) ?> baru</span>
                <?php endif; ?>
                <?php if ($rTotalAktif > 0): ?>
                <span class="badge bg-info text-white ms-1"><?= number_format($rTotalAktif) ?> aktif</span>
                <?php endif; ?>
                <?php if (!empty($entries)): ?>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-1"><?= count($entries) ?> entri</span>
                <?php endif; ?>
            </span>
            <?php if ($canManage && !$isInactive): ?>
            <button class="btn btn-xs btn-outline-primary toggle-add-realisasi"
                    style="padding:.2rem .6rem;font-size:.75rem"
                    data-pid="<?= $pid ?>">
                <i class="bi bi-plus-lg me-1"></i>Input
            </button>
            <?php endif; ?>
        </div>

        <?php $targetBaru  = (int)($p['target_peserta'] ?? 0); ?>
        <?php $targetAktif = (int)($p['target_member_aktif'] ?? 0); ?>
        <?php if ($targetBaru > 0 || $targetAktif > 0): ?>
        <div class="px-3 pt-2">
            <?php if ($targetBaru > 0):
                $mPct   = slPct($rTotal, $targetBaru);
                $mColor = $mPct >= 100 ? 'success' : ($mPct >= 60 ? 'primary' : ($mPct >= 30 ? 'warning' : 'danger'));
            ?>
            <div class="d-flex justify-content-between small mb-1">
                <span class="text-muted">Member Baru: <strong class="text-body"><?= number_format($rTotal) ?></strong></span>
                <span class="text-muted">Target: <strong class="text-body"><?= number_format($targetBaru) ?></strong> · <span class="fw-semibold text-<?= $mColor ?>"><?= $mPct ?>%</span></span>
            </div>
            <div class="progress mb-2" style="height:4px">
                <div class="progress-bar bg-<?= $mColor ?>" style="width:<?= $mPct ?>%"></div>
            </div>
            <?php endif; ?>
            <?php if ($targetAktif > 0):
                $maPct   = slPct($rTotalAktif, $targetAktif);
                $maColor = $maPct >= 100 ? 'success' : ($maPct >= 60 ? 'primary' : ($maPct >= 30 ? 'warning' : 'danger'));
            ?>
            <div class="d-flex justify-content-between small mb-1">
                <span class="text-muted">Member Aktif: <strong class="text-body"><?= number_format($rTotalAktif) ?></strong></span>
                <span class="text-muted">Target: <strong class="text-body"><?= number_format($targetAktif) ?></strong> · <span class="fw-semibold text-<?= $maColor ?>"><?= $maPct ?>%</span></span>
            </div>
            <div class="progress" style="height:4px">
                <div class="progress-bar bg-<?= $maColor ?>" style="width:<?= $maPct ?>%"></div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($canManage && !$isInactive): ?>
        <div id="add-realisasi-<?= $pid ?>" class="d-none px-3 py-2 border-bottom bg-primary-subtle bg-opacity-25">
            <form method="POST" action="<?= base_url('loyalty/'.$pid.'/realisasi/add') ?>">
                <?= csrf_field() ?>
                <div class="row g-2 align-items-end">
                    <div class="col-sm-2">
                        <label class="form-label small fw-semibold mb-1">Tanggal</label>
                        <input type="date" name="tanggal" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-sm-2">
                        <label class="form-label small fw-semibold mb-1">Member Baru</label>
                        <input type="number" name="jumlah" class="form-control form-control-sm" placeholder="0" min="0" value="0">
                    </div>
                    <div class="col-sm-2">
                        <label class="form-label small fw-semibold mb-1">Member Aktif</label>
                        <input type="number" name="member_aktif" class="form-control form-control-sm" placeholder="0" min="0" value="0">
                    </div>
                    <div class="col">
                        <label class="form-label small fw-semibold mb-1">Catatan</label>
                        <input type="text" name="catatan" class="form-control form-control-sm" placeholder="Opsional">
                    </div>
                    <div class="col-sm-2">
                        <button type="submit" class="btn btn-primary btn-sm w-100">Simpan</button>
                    </div>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <?php if (!empty($entries)): ?>
        <div class="table-responsive">
        <table class="table table-sm mb-0">
        <thead class="table-light">
            <tr>
                <th class="ps-3">Tanggal</th>
                <th>Member Baru</th>
                <th>Member Aktif</th>
                <th>Catatan</th>
                <?php if ($canManage): ?><th style="width:40px"></th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($entries as $e): ?>
        <tr>
            <td class="ps-3 small"><?= date('d M Y', strtotime($e['tanggal'])) ?></td>
            <td class="small fw-medium"><?= number_format($e['jumlah']) ?></td>
            <td class="small fw-medium"><?= number_format($e['member_aktif'] ?? 0) ?></td>
            <td class="small text-muted"><?= esc($e['catatan']) ?></td>
            <?php if ($canManage): ?>
            <td>
                <form method="POST" action="<?= base_url('loyalty/'.$pid.'/realisasi/'.$e['id'].'/delete') ?>"
                    onsubmit="return confirm('Hapus entri ini?')">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-xs btn-outline-danger" style="padding:.15rem .4rem;font-size:.7rem">
                        <i class="bi bi-trash"></i>
                    </button>
                </form>
            </td>
            <?php endif; ?>
        </tr>
        <?php endforeach; ?>
        <tr class="table-light fw-semibold">
            <td class="ps-3 small">Total</td>
            <td class="small"><?= number_format($rTotal) ?></td>
            <td class="small"><?= number_format($rTotalAktif) ?></td>
            <td colspan="<?= $canManage ? 2 : 1 ?>"></td>
        </tr>
        </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; // showMember ?>

    <!-- Section: e-Voucher -->
    <?php $showVoucher = $canEdit || !empty($myVoucherItems) || ($isEvoucher && (int)($p['total_voucher'] ?? 0) > 0); ?>
    <?php if ($showVoucher): ?>
    <?php
    $voucherBudgetReal = 0;
    foreach ($myVoucherItems as $vi) {
        $voucherBudgetReal += (int)($voucherRealisasi[$vi['id']]['total_terpakai'] ?? 0) * (int)$vi['nilai_voucher'];
    }
    ?>
    <div class="border-top">
        <div class="px-3 py-2 bg-warning-subtle d-flex justify-content-between align-items-center">
            <span class="small fw-semibold text-warning-emphasis">
                <i class="bi bi-ticket-perforated me-1"></i>e-Voucher
                <?php if (!empty($myVoucherItems)): ?>
                <span class="badge bg-warning text-dark ms-1"><?= count($myVoucherItems) ?> jenis</span>
                <?php endif; ?>
                <?php if ($voucherBudgetReal > 0): ?>
                <span class="ms-2 fw-semibold text-warning">Rp <?= number_format($voucherBudgetReal,0,',','.') ?> dipakai</span>
                <?php endif; ?>
            </span>
            <?php if ($canManage && !$isInactive): ?>
            <button class="btn btn-xs btn-outline-warning toggle-add-voucher-item"
                    style="padding:.2rem .6rem;font-size:.75rem"
                    data-pid="<?= $pid ?>">
                <i class="bi bi-plus-lg me-1"></i>Tambah Voucher
            </button>
            <?php endif; ?>
        </div>

        <?php if ($canManage && !$isInactive): ?>
        <div id="add-voucher-item-<?= $pid ?>" class="d-none px-3 py-2 border-bottom bg-warning-subtle bg-opacity-25">
            <form method="POST" action="<?= base_url('loyalty/'.$pid.'/voucher/add') ?>">
                <?= csrf_field() ?>
                <div class="row g-2 align-items-end">
                    <div class="col-sm-3">
                        <label class="form-label small fw-semibold mb-1">Nama Voucher <span class="text-danger">*</span></label>
                        <input type="text" name="nama_voucher" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-sm-2">
                        <label class="form-label small fw-semibold mb-1">Nilai (Rp) <span class="text-danger">*</span></label>
                        <input type="text" name="nilai_voucher" class="form-control form-control-sm currency-input" placeholder="0" required>
                    </div>
                    <div class="col-sm-2">
                        <label class="form-label small fw-semibold mb-1">Qty Terbit</label>
                        <input type="number" name="total_diterbitkan" class="form-control form-control-sm" placeholder="0" min="0">
                    </div>
                    <div class="col-sm-2">
                        <label class="form-label small fw-semibold mb-1">Target Serap</label>
                        <div class="input-group input-group-sm">
                            <input type="number" name="target_penyerapan" class="form-control" placeholder="—" min="0" max="100" step="0.1">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    <div class="col">
                        <label class="form-label small fw-semibold mb-1">Catatan</label>
                        <input type="text" name="catatan" class="form-control form-control-sm" placeholder="Opsional">
                    </div>
                    <div class="col-sm-auto">
                        <button type="submit" class="btn btn-warning btn-sm">Tambah</button>
                    </div>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <?php if ($isEvoucher && empty($myVoucherItems) && ((int)($p['total_voucher'] ?? 0) > 0 || (int)($p['nilai_voucher'] ?? 0) > 0)):
            $evTotal  = (int)($p['total_voucher']   ?? 0);
            $evNilai  = (int)($p['nilai_voucher']   ?? 0);
            $evTgtPct = ($p['target_penyerapan'] !== null && $p['target_penyerapan'] !== '') ? (float)$p['target_penyerapan'] : null;
        ?>
        <div class="px-3 py-2 border-bottom">
            <div class="fw-semibold small"><?= esc($p['nama_program']) ?> (e-Voucher)</div>
            <div class="d-flex flex-wrap gap-3 small text-muted mt-1">
                <?php if ($evNilai > 0): ?>
                <span><i class="bi bi-tag me-1"></i>Nilai/pcs: <strong class="text-body">Rp <?= number_format($evNilai,0,',','.') ?></strong></span>
                <?php endif; ?>
                <?php if ($evTotal > 0): ?>
                <span><i class="bi bi-ticket me-1"></i>Diterbitkan: <strong class="text-body"><?= number_format($evTotal) ?></strong></span>
                <?php endif; ?>
                <?php if ($evTgtPct !== null): ?>
                <span><i class="bi bi-bullseye me-1"></i>Target penyerapan: <strong class="text-body"><?= number_format($evTgtPct,1) ?>%</strong></span>
                <?php endif; ?>
                <?php if ($evTotal > 0 && $evNilai > 0): ?>
                <span><i class="bi bi-cash me-1"></i>Budget: <strong class="text-body">Rp <?= number_format($evTotal * $evNilai,0,',','.') ?></strong></span>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php foreach ($myVoucherItems as $vi):
            $vid         = $vi['id'];
            $vReal       = $voucherRealisasi[$vid] ?? ['total_tersebar' => 0, 'total_terpakai' => 0, 'entries' => []];
            $vTersebar   = (int)$vReal['total_tersebar'];
            $vTerpakai   = (int)$vReal['total_terpakai'];
            $vQty        = (int)$vi['total_diterbitkan'];
            $viTargetPct = ($vi['target_penyerapan'] !== null && $vi['target_penyerapan'] !== '') ? (float)$vi['target_penyerapan'] : null;
            $viTargetQty = ($viTargetPct !== null && $vQty > 0) ? round($viTargetPct / 100 * $vQty) : $vQty;
            $vPct        = $viTargetQty > 0 ? min(100, round($vTerpakai / $viTargetQty * 100, 1)) : 0;
            $vBudget     = $vQty * (int)$vi['nilai_voucher'];
            $vBudgetReal = $vTerpakai * (int)$vi['nilai_voucher'];
            $vColor      = $vPct >= 100 ? 'success' : ($vPct >= 60 ? 'primary' : 'secondary');
        ?>
        <div class="border-bottom">
            <div class="px-3 py-2 d-flex justify-content-between align-items-start">
                <div class="flex-grow-1">
                    <div class="fw-semibold small"><?= esc($vi['nama_voucher']) ?></div>
                    <div class="d-flex flex-wrap gap-3 small text-muted mt-1">
                        <?php if ((int)$vi['nilai_voucher'] > 0): ?>
                        <span><i class="bi bi-tag me-1"></i>Nilai/pcs: <strong class="text-body">Rp <?= number_format($vi['nilai_voucher'],0,',','.') ?></strong></span>
                        <?php endif; ?>
                        <?php if ($vQty > 0): ?>
                        <span><i class="bi bi-ticket me-1"></i>Diterbitkan: <strong class="text-body"><?= number_format($vQty) ?></strong></span>
                        <?php endif; ?>
                        <?php if ($viTargetPct !== null): ?>
                        <span><i class="bi bi-bullseye me-1"></i>Target penyerapan: <strong class="text-body"><?= number_format($viTargetPct,1) ?>%</strong></span>
                        <?php endif; ?>
                        <?php if ($vBudget > 0): ?>
                        <span><i class="bi bi-cash me-1"></i>Budget: <strong class="text-body">Rp <?= number_format($vBudget,0,',','.') ?></strong></span>
                        <?php endif; ?>
                        <span><i class="bi bi-send me-1"></i>Tersebar: <strong class="text-body"><?= number_format($vTersebar) ?></strong></span>
                        <span><i class="bi bi-check-circle me-1"></i>Terpakai: <strong class="text-body"><?= number_format($vTerpakai) ?></strong></span>
                    </div>
                    <?php if ($viTargetQty > 0): ?>
                    <div class="mt-2">
                        <div class="d-flex justify-content-between small mb-1">
                            <?php if ($viTargetPct !== null): ?>
                            <span class="text-muted"><?= number_format($vTerpakai) ?> / <?= number_format((int)$viTargetQty) ?> target terpakai</span>
                            <?php else: ?>
                            <span class="text-muted"><?= number_format($vTerpakai) ?> / <?= number_format($vQty) ?> terpakai</span>
                            <?php endif; ?>
                            <span class="text-<?= $vColor ?> fw-semibold"><?= $vPct ?>%</span>
                        </div>
                        <div class="progress" style="height:4px">
                            <div class="progress-bar bg-<?= $vColor ?>" style="width:<?= $vPct ?>%"></div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ($vi['catatan']): ?>
                    <div class="small text-muted mt-1"><i class="bi bi-sticky me-1"></i><?= esc($vi['catatan']) ?></div>
                    <?php endif; ?>
                </div>
                <?php if ($canManage && !$isInactive): ?>
                <div class="d-flex gap-1 ms-3 flex-shrink-0">
                    <button class="btn btn-xs btn-outline-secondary toggle-add-voucher-real"
                            style="padding:.2rem .6rem;font-size:.75rem"
                            data-vid="<?= $vid ?>">
                        <i class="bi bi-plus-lg me-1"></i>Realisasi
                    </button>
                    <form method="POST" action="<?= base_url('loyalty/'.$pid.'/voucher/'.$vid.'/delete') ?>"
                          onsubmit="return confirm('Hapus voucher ini beserta semua realisasinya?')">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-xs btn-outline-danger" style="padding:.2rem .4rem;font-size:.75rem">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
            <?php if ($canManage && !$isInactive): ?>
            <div id="add-voucher-real-<?= $vid ?>" class="d-none px-3 py-2 border-top bg-warning-subtle bg-opacity-25">
                <form method="POST" action="<?= base_url('loyalty/'.$pid.'/voucher/'.$vid.'/realisasi/add') ?>">
                    <?= csrf_field() ?>
                    <div class="row g-2 align-items-end">
                        <div class="col-sm-3">
                            <label class="form-label small fw-semibold mb-1">Tanggal</label>
                            <input type="date" name="tanggal" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-sm-2">
                            <label class="form-label small fw-semibold mb-1">Tersebar</label>
                            <input type="number" name="tersebar" class="form-control form-control-sm" placeholder="0" min="0">
                        </div>
                        <div class="col-sm-2">
                            <label class="form-label small fw-semibold mb-1">Terpakai</label>
                            <input type="number" name="terpakai" class="form-control form-control-sm" placeholder="0" min="0">
                        </div>
                        <div class="col">
                            <label class="form-label small fw-semibold mb-1">Catatan</label>
                            <input type="text" name="catatan" class="form-control form-control-sm" placeholder="Opsional">
                        </div>
                        <div class="col-sm-2">
                            <button type="submit" class="btn btn-warning btn-sm w-100 text-dark">Simpan</button>
                        </div>
                    </div>
                </form>
            </div>
            <?php endif; ?>
            <?php if (!empty($vReal['entries'])): ?>
            <div class="table-responsive border-top">
            <table class="table table-sm mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">Tanggal</th>
                    <th>Tersebar</th>
                    <th>Terpakai</th>
                    <th>Catatan</th>
                    <?php if ($canManage): ?><th style="width:40px"></th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($vReal['entries'] as $e): ?>
            <tr>
                <td class="ps-3 small"><?= date('d M Y', strtotime($e['tanggal'])) ?></td>
                <td class="small fw-medium"><?= number_format($e['tersebar']) ?></td>
                <td class="small fw-medium"><?= number_format($e['terpakai']) ?></td>
                <td class="small text-muted"><?= esc($e['catatan']) ?></td>
                <?php if ($canManage): ?>
                <td>
                    <form method="POST" action="<?= base_url('loyalty/'.$pid.'/voucher/'.$vid.'/realisasi/'.$e['id'].'/delete') ?>"
                        onsubmit="return confirm('Hapus entri ini?')">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-xs btn-outline-danger" style="padding:.15rem .4rem;font-size:.7rem">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
            <tr class="table-light fw-semibold">
                <td class="ps-3 small">Total</td>
                <td class="small"><?= number_format($vTersebar) ?></td>
                <td class="small"><?= number_format($vTerpakai) ?></td>
                <td colspan="<?= $canManage ? 2 : 1 ?>"></td>
            </tr>
            </tbody>
            </table>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; // showVoucher ?>

    <!-- Section: Hadiah Barang -->
    <?php $showHadiah = $canEdit || !empty($myItems); ?>
    <?php if ($showHadiah): ?>
    <?php
    $hadiahBudgetReal = 0;
    foreach ($myItems as $item) {
        $hadiahBudgetReal += (int)($hadiahRealisasi[$item['id']]['total'] ?? 0) * (int)$item['nilai_satuan'];
    }
    ?>
    <div class="border-top">
        <div class="px-3 py-2 bg-success-subtle d-flex justify-content-between align-items-center">
            <span class="small fw-semibold text-success-emphasis">
                <i class="bi bi-gift me-1"></i>Hadiah Barang
                <?php if (!empty($myItems)): ?>
                <span class="badge bg-success text-white ms-1"><?= count($myItems) ?> item</span>
                <?php endif; ?>
                <?php if ($hadiahBudgetReal > 0): ?>
                <span class="ms-2 fw-semibold text-success">Rp <?= number_format($hadiahBudgetReal,0,',','.') ?> dibagikan</span>
                <?php endif; ?>
            </span>
            <?php if ($canManage && !$isInactive): ?>
            <button class="btn btn-xs btn-outline-success toggle-add-hadiah-item"
                    style="padding:.2rem .6rem;font-size:.75rem"
                    data-pid="<?= $pid ?>">
                <i class="bi bi-plus-lg me-1"></i>Tambah Item
            </button>
            <?php endif; ?>
        </div>

        <?php if ($canManage && !$isInactive): ?>
        <div id="add-hadiah-item-<?= $pid ?>" class="d-none px-3 py-2 border-bottom bg-success-subtle bg-opacity-25">
            <form method="POST" action="<?= base_url('loyalty/'.$pid.'/hadiah/add') ?>">
                <?= csrf_field() ?>
                <div class="row g-2 align-items-end">
                    <div class="col-sm-4">
                        <label class="form-label small fw-semibold mb-1">Nama Hadiah <span class="text-danger">*</span></label>
                        <input type="text" name="nama_hadiah" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-sm-2">
                        <label class="form-label small fw-semibold mb-1">Stok</label>
                        <input type="number" name="stok" class="form-control form-control-sm" placeholder="0" min="0">
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label small fw-semibold mb-1">Nilai Satuan (Rp)</label>
                        <input type="text" name="nilai_satuan" class="form-control form-control-sm currency-input" placeholder="0">
                    </div>
                    <div class="col">
                        <label class="form-label small fw-semibold mb-1">Catatan</label>
                        <input type="text" name="catatan" class="form-control form-control-sm" placeholder="Opsional">
                    </div>
                    <div class="col-sm-auto">
                        <button type="submit" class="btn btn-success btn-sm">Tambah</button>
                    </div>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <?php foreach ($myItems as $item):
            $iid        = $item['id'];
            $iReal      = $hadiahRealisasi[$iid] ?? ['total' => 0, 'entries' => []];
            $dibagikan  = (int)$iReal['total'];
            $itemStok   = (int)$item['stok'];
            $itemNilai  = (int)$item['nilai_satuan'];
            $stockPct   = $itemStok > 0 ? min(100, round($dibagikan / $itemStok * 100, 1)) : 0;
            $stockColor = $stockPct >= 100 ? 'success' : ($stockPct >= 60 ? 'primary' : 'secondary');
        ?>
        <div class="border-bottom">
            <div class="px-3 py-2 d-flex justify-content-between align-items-start">
                <div class="flex-grow-1">
                    <div class="fw-semibold small"><?= esc($item['nama_hadiah']) ?></div>
                    <div class="d-flex flex-wrap gap-3 small text-muted mt-1">
                        <?php if ($itemNilai > 0): ?>
                        <span><i class="bi bi-tag me-1"></i>Nilai/pcs: <strong class="text-body">Rp <?= number_format($itemNilai,0,',','.') ?></strong></span>
                        <?php endif; ?>
                        <?php if ($itemStok > 0): ?>
                        <span><i class="bi bi-box-seam me-1"></i>Stok: <strong class="text-body"><?= number_format($itemStok) ?></strong></span>
                        <?php endif; ?>
                        <?php if ($itemStok > 0 && $itemNilai > 0): ?>
                        <span><i class="bi bi-cash me-1"></i>Budget: <strong class="text-body">Rp <?= number_format($itemStok * $itemNilai,0,',','.') ?></strong></span>
                        <?php endif; ?>
                        <span><i class="bi bi-gift me-1"></i>Dibagikan: <strong class="text-body"><?= number_format($dibagikan) ?></strong></span>
                    </div>
                    <?php if ($itemStok > 0): ?>
                    <div class="mt-2">
                        <div class="d-flex justify-content-between small mb-1">
                            <span class="text-muted"><?= number_format($dibagikan) ?> / <?= number_format($itemStok) ?></span>
                            <span class="text-<?= $stockColor ?> fw-semibold"><?= $stockPct ?>%</span>
                        </div>
                        <div class="progress" style="height:4px">
                            <div class="progress-bar bg-<?= $stockColor ?>" style="width:<?= $stockPct ?>%"></div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ($item['catatan']): ?>
                    <div class="small text-muted mt-1"><i class="bi bi-sticky me-1"></i><?= esc($item['catatan']) ?></div>
                    <?php endif; ?>
                </div>
                <?php if ($canManage && !$isInactive): ?>
                <div class="d-flex gap-1 ms-3 flex-shrink-0">
                    <button class="btn btn-xs btn-outline-secondary toggle-add-hadiah-real"
                            style="padding:.2rem .6rem;font-size:.75rem"
                            data-iid="<?= $iid ?>">
                        <i class="bi bi-plus-lg me-1"></i>Realisasi
                    </button>
                    <form method="POST" action="<?= base_url('loyalty/'.$pid.'/hadiah/'.$iid.'/delete') ?>"
                          onsubmit="return confirm('Hapus item hadiah ini beserta semua realisasinya?')">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-xs btn-outline-danger" style="padding:.2rem .4rem;font-size:.75rem">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
            <?php if ($canManage && !$isInactive): ?>
            <div id="add-hadiah-real-<?= $iid ?>" class="d-none px-3 py-2 border-top bg-success-subtle bg-opacity-25">
                <form method="POST" action="<?= base_url('loyalty/'.$pid.'/hadiah/'.$iid.'/realisasi/add') ?>">
                    <?= csrf_field() ?>
                    <div class="row g-2 align-items-end">
                        <div class="col-sm-3">
                            <label class="form-label small fw-semibold mb-1">Tanggal</label>
                            <input type="date" name="tanggal" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-sm-3">
                            <label class="form-label small fw-semibold mb-1">Jumlah Dibagikan</label>
                            <input type="number" name="jumlah_dibagikan" class="form-control form-control-sm" placeholder="0" min="0" required>
                        </div>
                        <div class="col">
                            <label class="form-label small fw-semibold mb-1">Catatan</label>
                            <input type="text" name="catatan" class="form-control form-control-sm" placeholder="Opsional">
                        </div>
                        <div class="col-sm-2">
                            <button type="submit" class="btn btn-success btn-sm w-100">Simpan</button>
                        </div>
                    </div>
                </form>
            </div>
            <?php endif; ?>
            <?php if (!empty($iReal['entries'])): ?>
            <div class="table-responsive border-top">
            <table class="table table-sm mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">Tanggal</th>
                    <th>Dibagikan</th>
                    <th>Catatan</th>
                    <?php if ($canManage): ?><th style="width:40px"></th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($iReal['entries'] as $e): ?>
            <tr>
                <td class="ps-3 small"><?= date('d M Y', strtotime($e['tanggal'])) ?></td>
                <td class="small fw-medium"><?= number_format($e['jumlah_dibagikan']) ?></td>
                <td class="small text-muted"><?= esc($e['catatan']) ?></td>
                <?php if ($canManage): ?>
                <td>
                    <form method="POST" action="<?= base_url('loyalty/'.$pid.'/hadiah/'.$iid.'/realisasi/'.$e['id'].'/delete') ?>"
                        onsubmit="return confirm('Hapus entri ini?')">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-xs btn-outline-danger" style="padding:.15rem .4rem;font-size:.7rem">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
            <tr class="table-light fw-semibold">
                <td class="ps-3 small">Total</td>
                <td class="small"><?= number_format($dibagikan) ?></td>
                <td colspan="<?= $canManage ? 2 : 1 ?>"></td>
            </tr>
            </tbody>
            </table>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; // showHadiah ?>

    <?php endif; // isStandalone ?>
</div>
<?php } // end renderLoyaltyCard ?>

<?php $isAdmin = ($user['role'] ?? '') === 'admin'; ?>
<?php $openCount   = count($standaloneActive) + count($eventOpen); ?>
<?php $closedCount = count($standaloneInactive) + count($eventClosed); ?>

<!-- Tabs -->
<ul class="nav nav-tabs mb-3" id="loyaltyTabs">
    <li class="nav-item">
        <button class="nav-link active fw-semibold" data-tab="open">
            <i class="bi bi-unlock me-1"></i>Open
            <span class="badge bg-primary ms-1"><?= $openCount ?></span>
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link fw-semibold" data-tab="closed">
            <i class="bi bi-lock me-1"></i>Closed
            <?php if ($closedCount > 0): ?>
            <span class="badge bg-secondary ms-1"><?= $closedCount ?></span>
            <?php endif; ?>
        </button>
    </li>
</ul>

<!-- Tab: Open -->
<div id="tab-open">
    <?php foreach ($standaloneActive as $p): renderLoyaltyCard($p, $realisasi, $canEdit, $hadiahItems, $hadiahRealisasi, $voucherItems, $voucherRealisasi, $isAdmin); endforeach; ?>
    <?php if (!empty($eventPrograms)): ?>
    <div class="d-flex align-items-center gap-2 my-3">
        <span class="text-muted" style="font-size:.7rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase">Dari Event</span>
        <div class="flex-grow-1 border-top"></div>
    </div>
    <?php foreach ($eventPrograms as $p): renderLoyaltyCard($p, $realisasi, $canEdit, [], [], [], []); endforeach; ?>
    <?php endif; ?>
    <?php if ($openCount === 0): ?>
    <div class="card"><div class="card-body text-center py-5 text-muted">
        <i class="bi bi-unlock display-4 d-block mb-2 opacity-25"></i>
        <p class="mb-0">Tidak ada program yang sedang berjalan.</p>
    </div></div>
    <?php endif; ?>
</div>

<!-- Tab: Closed -->
<div id="tab-closed" class="d-none">
    <?php foreach ($standaloneInactive as $p): renderLoyaltyCard($p, $realisasi, $canEdit, $hadiahItems, $hadiahRealisasi, $voucherItems, $voucherRealisasi, $isAdmin); endforeach; ?>
    <?php if (!empty($eventClosed)): ?>
    <div class="d-flex align-items-center gap-2 my-3">
        <span class="text-muted" style="font-size:.7rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase">Dari Event</span>
        <div class="flex-grow-1 border-top"></div>
    </div>
    <?php foreach ($eventClosed as $p): renderLoyaltyCard($p, $realisasi, $canEdit, [], [], [], []); endforeach; ?>
    <?php endif; ?>
    <?php if ($closedCount === 0): ?>
    <div class="card"><div class="card-body text-center py-5 text-muted">
        <i class="bi bi-lock display-4 d-block mb-2 opacity-25"></i>
        <p class="mb-0">Belum ada program yang ditutup.</p>
    </div></div>
    <?php endif; ?>
</div>

<?php endif; ?>

<?php if ($canEdit): ?>
<!-- Add Modal -->
<div class="modal fade" id="addProgramModal" tabindex="-1">
<div class="modal-dialog"><div class="modal-content">
<form method="POST" action="<?= base_url('loyalty/add') ?>">
<?= csrf_field() ?>
<div class="modal-header">
    <h5 class="modal-title fw-semibold">Tambah Program Loyalty</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
    <div class="mb-3">
        <label class="form-label small fw-semibold">Nama Program <span class="text-danger">*</span></label>
        <input type="text" name="nama_program" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label small fw-semibold">Periode</label>
        <div class="row g-2">
            <div class="col-6">
                <input type="date" name="tanggal_mulai" class="form-control form-control-sm" placeholder="Tanggal Mulai">
            </div>
            <div class="col-6">
                <input type="date" name="tanggal_selesai" class="form-control form-control-sm" placeholder="Tanggal Selesai">
            </div>
            <div class="col-6">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-clock"></i></span>
                    <input type="time" name="jam_mulai" class="form-control">
                </div>
            </div>
            <div class="col-6">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-clock"></i></span>
                    <input type="time" name="jam_selesai" class="form-control">
                </div>
            </div>
        </div>
        <div class="form-text">Opsional — isi jika program punya jadwal tertentu</div>
    </div>
    <div class="mb-3">
        <label class="form-label small fw-semibold">Target Akuisisi Member</label>
        <div class="input-group">
            <input type="number" name="target_peserta" class="form-control" min="0" placeholder="0">
            <span class="input-group-text">member</span>
        </div>
        <div class="form-text">Opsional — target member baru untuk program ini</div>
    </div>
    <div class="mb-3">
        <label class="form-label small fw-semibold">Mekanisme Program</label>
        <textarea name="mekanisme" class="form-control" rows="3" placeholder="Jelaskan cara kerja program..."></textarea>
    </div>
    <div class="mb-0">
        <label class="form-label small fw-semibold">Catatan</label>
        <input type="text" name="catatan" class="form-control">
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
    <button type="submit" class="btn btn-primary">Tambah</button>
</div>
</form>
</div></div></div>

<!-- Edit Modal -->
<div class="modal fade" id="editProgramModal" tabindex="-1">
<div class="modal-dialog"><div class="modal-content">
<form id="editProgramForm" method="POST">
<?= csrf_field() ?>
<div class="modal-header">
    <h5 class="modal-title fw-semibold">Edit Program</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
    <div class="mb-3">
        <label class="form-label small fw-semibold">Nama Program</label>
        <input type="text" name="nama_program" id="editNama" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label small fw-semibold">Periode</label>
        <div class="row g-2">
            <div class="col-6">
                <input type="date" name="tanggal_mulai" id="editTanggalMulai" class="form-control form-control-sm">
            </div>
            <div class="col-6">
                <input type="date" name="tanggal_selesai" id="editTanggalSelesai" class="form-control form-control-sm">
            </div>
            <div class="col-6">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-clock"></i></span>
                    <input type="time" name="jam_mulai" id="editJamMulai" class="form-control">
                </div>
            </div>
            <div class="col-6">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-clock"></i></span>
                    <input type="time" name="jam_selesai" id="editJamSelesai" class="form-control">
                </div>
            </div>
        </div>
        <div class="form-text">Opsional</div>
    </div>
    <div class="mb-3">
        <label class="form-label small fw-semibold">Target Member</label>
        <div class="row g-2">
            <div class="col-6">
                <div class="input-group">
                    <span class="input-group-text">Baru</span>
                    <input type="number" name="target_peserta" id="editTarget" class="form-control" min="0" placeholder="Opsional">
                </div>
            </div>
            <div class="col-6">
                <div class="input-group">
                    <span class="input-group-text">Aktif</span>
                    <input type="number" name="target_member_aktif" id="editTargetAktif" class="form-control" min="0" placeholder="Opsional">
                </div>
            </div>
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label small fw-semibold">Mekanisme</label>
        <textarea name="mekanisme" id="editMekanisme" class="form-control" rows="3"></textarea>
    </div>
    <div class="mb-0">
        <label class="form-label small fw-semibold">Catatan</label>
        <input type="text" name="catatan" id="editCatatan" class="form-control">
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
    <button type="submit" class="btn btn-primary">Simpan</button>
</div>
</form>
</div></div></div>
<?php endif; ?>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
// Tabs
(function() {
    const tabs = document.querySelectorAll('[data-tab]');
    function activate(name) {
        tabs.forEach(btn => {
            const isActive = btn.dataset.tab === name;
            btn.classList.toggle('active', isActive);
            document.getElementById('tab-' + btn.dataset.tab).classList.toggle('d-none', !isActive);
        });
        history.replaceState(null, '', '#' + name);
    }
    tabs.forEach(btn => btn.addEventListener('click', () => activate(btn.dataset.tab)));
    const hash = location.hash.replace('#', '');
    if (hash === 'closed') activate('closed');
})();

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

// Program card stagger entrance
document.querySelectorAll('.card.border-start').forEach((card, i) => {
    card.style.opacity = '0';
    card.style.transform = 'translateY(18px)';
    setTimeout(() => {
        card.style.transition = 'opacity .45s ease, transform .45s ease';
        card.style.opacity = '1';
        card.style.transform = 'translateY(0)';
    }, 180 + i * 90);
});

// Progress bar grow animation
document.querySelectorAll('.progress-bar').forEach((bar, i) => {
    const target = bar.style.width;
    if (!target || target === '0%') return;
    bar.style.width = '0';
    setTimeout(() => {
        bar.style.transition = 'width .75s ease';
        bar.style.width = target;
    }, 420 + i * 55);
});
</script>
<script>
<?php if ($canEdit): ?>
document.querySelectorAll('.toggle-add-realisasi').forEach(btn => {
    btn.addEventListener('click', function() {
        const el = document.getElementById('add-realisasi-' + this.dataset.pid);
        el.classList.toggle('d-none');
        this.innerHTML = el.classList.contains('d-none')
            ? '<i class="bi bi-plus-lg me-1"></i>Input'
            : '<i class="bi bi-x me-1"></i>Tutup';
    });
});

document.querySelectorAll('.toggle-add-voucher-item').forEach(btn => {
    btn.addEventListener('click', function() {
        const el = document.getElementById('add-voucher-item-' + this.dataset.pid);
        el.classList.toggle('d-none');
        this.innerHTML = el.classList.contains('d-none')
            ? '<i class="bi bi-plus-lg me-1"></i>Tambah Voucher'
            : '<i class="bi bi-x me-1"></i>Batal';
    });
});

document.querySelectorAll('.toggle-add-voucher-real').forEach(btn => {
    btn.addEventListener('click', function() {
        const el = document.getElementById('add-voucher-real-' + this.dataset.vid);
        el.classList.toggle('d-none');
        this.innerHTML = el.classList.contains('d-none') ? '<i class="bi bi-plus-lg"></i>' : '<i class="bi bi-x"></i>';
    });
});

document.querySelectorAll('.toggle-add-hadiah-item').forEach(btn => {
    btn.addEventListener('click', function() {
        const el = document.getElementById('add-hadiah-item-' + this.dataset.pid);
        el.classList.toggle('d-none');
        this.innerHTML = el.classList.contains('d-none')
            ? '<i class="bi bi-plus-lg me-1"></i>Tambah Item'
            : '<i class="bi bi-x me-1"></i>Batal';
    });
});

document.querySelectorAll('.toggle-add-hadiah-real').forEach(btn => {
    btn.addEventListener('click', function() {
        const el = document.getElementById('add-hadiah-real-' + this.dataset.iid);
        el.classList.toggle('d-none');
        this.innerHTML = el.classList.contains('d-none') ? '<i class="bi bi-plus-lg"></i>' : '<i class="bi bi-x"></i>';
    });
});

document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('editProgramForm').action   = '<?= base_url('loyalty/') ?>' + this.dataset.id + '/edit';
        document.getElementById('editNama').value           = this.dataset.nama;
        document.getElementById('editTanggalMulai').value   = this.dataset.tanggalMulai;
        document.getElementById('editTanggalSelesai').value = this.dataset.tanggalSelesai;
        document.getElementById('editJamMulai').value       = this.dataset.jamMulai;
        document.getElementById('editJamSelesai').value     = this.dataset.jamSelesai;
        document.getElementById('editMekanisme').value      = this.dataset.mekanisme;
        document.getElementById('editTarget').value         = this.dataset.target;
        document.getElementById('editTargetAktif').value    = this.dataset.targetAktif;
        document.getElementById('editCatatan').value        = this.dataset.catatan;
        new bootstrap.Modal(document.getElementById('editProgramModal')).show();
    });
});
<?php endif; ?>

document.querySelectorAll('.currency-input').forEach(inp => {
    inp.addEventListener('input', function() {
        let n = parseInt(this.value.replace(/[^0-9]/g, '')) || 0;
        this.value = n.toLocaleString('id-ID');
    });
});
</script>
<?= $this->endSection() ?>
