<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?= view('partials/complete_data_bar', ['event' => $event, 'module' => 'loyalty', 'completion' => $completion, 'canEdit' => $canEdit, 'user' => $user]) ?>

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= base_url('events/'.$event['id'].'/summary') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h4 class="fw-bold mb-0">Program Loyalty</h4>
        <small class="text-muted"><?= esc($event['name']) ?></small>
    </div>
    <?php if ($canEdit): ?>
    <button class="btn btn-sm btn-primary ms-auto" data-bs-toggle="modal" data-bs-target="#addProgramModal">
        <i class="bi bi-plus-lg me-1"></i> Tambah Program
    </button>
    <?php endif; ?>
</div>

<?php if (empty($programs)): ?>
<div class="card"><div class="card-body text-center py-5 text-muted">
    <i class="bi bi-star display-4 d-block mb-2 opacity-25"></i>
    <p>Belum ada program loyalty untuk event ini.</p>
</div></div>

<?php else: ?>

<?php foreach ($programs as $p):
    $pid           = $p['id'];

    // Member realisasi
    $rData         = $realisasi[$pid] ?? ['total' => 0, 'entries' => []];
    $rTotal        = (int)$rData['total'];
    $memberEntries = $rData['entries'];

    // Voucher items + realisasi
    $myVouchers         = $voucherItems[$pid] ?? [];
    $totalVoucherBudget = 0;
    $totalTerpakai      = 0;
    $totalTersebar      = 0;
    foreach ($myVouchers as $vi) {
        $totalVoucherBudget += (int)$vi['total_diterbitkan'] * (int)$vi['nilai_voucher'];
        $vr = $voucherRealisasi[$vi['id']] ?? [];
        $totalTerpakai += (int)($vr['total_terpakai'] ?? 0);
        $totalTersebar += (int)($vr['total_tersebar'] ?? 0);
    }

    // Hadiah items + realisasi
    $myHadiah          = $hadiahItems[$pid] ?? [];
    $totalHadiahBudget = 0;
    $totalDibagikan    = 0;
    foreach ($myHadiah as $hi) {
        $totalHadiahBudget += (int)$hi['stok'] * (int)$hi['nilai_satuan'];
        $hr = $hadiahRealisasi[$hi['id']] ?? [];
        $totalDibagikan += (int)($hr['total'] ?? 0);
    }

    // Auto-computed budget
    $autoBudget = $totalVoucherBudget + $totalHadiahBudget;

    // Budget realisasi
    $budgetReal = 0;
    foreach ($myVouchers as $vi) {
        $vr = $voucherRealisasi[$vi['id']] ?? [];
        $budgetReal += (int)($vr['total_terpakai'] ?? 0) * (int)$vi['nilai_voucher'];
    }
    foreach ($myHadiah as $hi) {
        $hr = $hadiahRealisasi[$hi['id']] ?? [];
        $budgetReal += (int)($hr['total'] ?? 0) * (int)$hi['nilai_satuan'];
    }

    // Member progress
    $memberTarget      = (int)($p['target_peserta'] ?? 0);
    $memberAktifTarget = (int)($p['target_member_aktif'] ?? 0);
    $rTotalAktif       = (int)($rData['total_aktif'] ?? 0);
    $memberPct         = $memberTarget > 0 ? min(100, round($rTotal / $memberTarget * 100, 1)) : 0;
    $memberAktifPct    = $memberAktifTarget > 0 ? min(100, round($rTotalAktif / $memberAktifTarget * 100, 1)) : 0;
    $memberColor       = $memberPct >= 100 ? 'success' : ($memberPct >= 60 ? 'primary' : ($memberPct >= 30 ? 'warning' : 'danger'));
    $memberAktifColor  = $memberAktifPct >= 100 ? 'success' : ($memberAktifPct >= 60 ? 'primary' : ($memberAktifPct >= 30 ? 'warning' : 'danger'));

    $hasMemberData  = $memberTarget > 0 || $memberAktifTarget > 0 || !empty($memberEntries);
    $hasVoucherData = !empty($myVouchers);
    $hasHadiahData  = !empty($myHadiah);
?>
<div class="card mb-4" id="program-<?= $pid ?>">

    <!-- ── Program Header ──────────────────────────────────────────────────── -->
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start gap-3">
            <div class="flex-grow-1 min-w-0">
                <div class="fw-bold mb-1"><?= esc($p['nama_program']) ?></div>
                <?php if ($p['mekanisme']): ?>
                <p class="small text-muted mb-2" style="white-space:pre-line"><?= esc($p['mekanisme']) ?></p>
                <?php endif; ?>
                <div class="d-flex flex-wrap gap-3 small">
                    <?php if ($autoBudget > 0): ?>
                    <span class="text-muted"><i class="bi bi-cash me-1"></i>Budget: <strong class="text-success">Rp <?= number_format($autoBudget,0,',','.') ?></strong></span>
                    <?php endif; ?>
                    <?php if ($budgetReal > 0): ?>
                    <?php $brColor = ($autoBudget > 0 && $budgetReal > $autoBudget) ? 'danger' : 'primary'; ?>
                    <span class="text-muted"><i class="bi bi-receipt me-1"></i>Realisasi: <strong class="text-<?= $brColor ?>">Rp <?= number_format($budgetReal,0,',','.') ?></strong></span>
                    <?php endif; ?>
                    <?php if ($p['catatan']): ?>
                    <span class="text-muted"><i class="bi bi-sticky me-1"></i><?= esc($p['catatan']) ?></span>
                    <?php endif; ?>
                </div>
                <?php if ($autoBudget > 0 && $budgetReal > 0):
                    $brPct    = min(100, round($budgetReal / $autoBudget * 100, 1));
                    $brColor2 = $budgetReal <= $autoBudget ? 'success' : 'danger';
                ?>
                <div class="mt-2">
                    <div class="progress" style="height:4px">
                        <div class="progress-bar bg-<?= $brColor2 ?>" style="width:<?= min(100,$brPct) ?>%"></div>
                    </div>
                    <div class="text-muted mt-1" style="font-size:.7rem"><?= $brPct ?>% dari budget<?= $budgetReal > $autoBudget ? ' <span class="text-danger fw-semibold">· over budget</span>' : '' ?></div>
                </div>
                <?php endif; ?>
            </div>
            <?php if ($canEdit): ?>
            <div class="d-flex gap-1 flex-shrink-0">
                <button class="btn btn-sm btn-outline-secondary edit-btn"
                    data-id="<?= $p['id'] ?>"
                    data-nama="<?= esc($p['nama_program'], 'attr') ?>"
                    data-mekanisme="<?= esc($p['mekanisme'] ?? '', 'attr') ?>"
                    data-target="<?= $p['target_peserta'] ?? '' ?>"
                    data-target-aktif="<?= $p['target_member_aktif'] ?? '' ?>"
                    data-catatan="<?= esc($p['catatan'] ?? '', 'attr') ?>">
                    <i class="bi bi-pencil"></i>
                </button>
                <a href="<?= base_url('events/'.$event['id'].'/loyalty/'.$p['id'].'/delete') ?>"
                   class="btn btn-sm btn-outline-danger"
                   onclick="return confirm('Hapus program beserta semua data realisasinya?')">
                    <i class="bi bi-trash"></i>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ── Section: Target Member ─────────────────────────────────────────── -->
    <?php if ($canEdit || $hasMemberData): ?>
    <div class="border-top">
        <!-- Section header — blue tint -->
        <div class="px-3 py-2 bg-primary-subtle d-flex justify-content-between align-items-center">
            <span class="small fw-semibold text-primary">
                <i class="bi bi-person-plus me-1"></i>Target Member
                <?php if ($memberTarget > 0): ?>
                <span class="badge bg-primary text-white ms-1" style="font-size:.65rem"><?= number_format($memberTarget) ?> baru</span>
                <?php endif; ?>
                <?php if ($memberAktifTarget > 0): ?>
                <span class="badge bg-info text-white ms-1" style="font-size:.65rem"><?= number_format($memberAktifTarget) ?> aktif</span>
                <?php endif; ?>
                <?php if (!empty($memberEntries)): ?>
                <span class="badge bg-secondary-subtle text-secondary ms-1" style="font-size:.65rem"><?= count($memberEntries) ?> entri</span>
                <?php endif; ?>
            </span>
            <?php if ($canEdit): ?>
            <button class="btn btn-xs btn-outline-primary toggle-add-realisasi" style="padding:.2rem .6rem;font-size:.75rem"
                data-pid="<?= $pid ?>">
                <i class="bi bi-plus-lg me-1"></i>Input
            </button>
            <?php endif; ?>
        </div>

        <!-- Progress bar -->
        <?php if ($memberTarget > 0 || $memberAktifTarget > 0): ?>
        <div class="px-3 py-2">
            <?php if ($memberTarget > 0): ?>
            <div class="d-flex justify-content-between small mb-1">
                <span class="text-muted">Member Baru: <strong class="text-body"><?= number_format($rTotal) ?></strong></span>
                <span class="text-muted">Target: <strong class="text-body"><?= number_format($memberTarget) ?></strong> · <span class="fw-semibold text-<?= $memberColor ?>"><?= $memberPct ?>%</span></span>
            </div>
            <div class="progress mb-2" style="height:6px">
                <div class="progress-bar bg-<?= $memberColor ?>" style="width:<?= $memberPct ?>%"></div>
            </div>
            <?php endif; ?>
            <?php if ($memberAktifTarget > 0): ?>
            <div class="d-flex justify-content-between small mb-1">
                <span class="text-muted">Member Aktif: <strong class="text-body"><?= number_format($rTotalAktif) ?></strong></span>
                <span class="text-muted">Target: <strong class="text-body"><?= number_format($memberAktifTarget) ?></strong> · <span class="fw-semibold text-<?= $memberAktifColor ?>"><?= $memberAktifPct ?>%</span></span>
            </div>
            <div class="progress" style="height:6px">
                <div class="progress-bar bg-<?= $memberAktifColor ?>" style="width:<?= $memberAktifPct ?>%"></div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Form input realisasi (hidden) -->
        <?php if ($canEdit): ?>
        <div id="add-realisasi-<?= $pid ?>" class="d-none px-3 py-2 border-top bg-white">
            <form method="POST" action="<?= base_url('events/'.$event['id'].'/loyalty/'.$pid.'/realisasi/add') ?>">
                <?= csrf_field() ?>
                <div class="row g-2 align-items-end">
                    <div class="col-sm-3">
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

        <!-- Tabel entri -->
        <?php if (!empty($memberEntries)): ?>
        <div class="table-responsive border-top">
        <table class="table table-sm mb-0">
        <thead class="table-light">
            <tr>
                <th class="ps-3">Tanggal</th>
                <th>Member Baru</th>
                <th>Member Aktif</th>
                <th>Catatan</th>
                <?php if ($canEdit): ?><th style="width:40px"></th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($memberEntries as $e): ?>
        <tr>
            <td class="ps-3 small"><?= date('d M Y', strtotime($e['tanggal'])) ?></td>
            <td class="small fw-medium"><?= number_format($e['jumlah']) ?></td>
            <td class="small fw-medium"><?= number_format($e['member_aktif'] ?? 0) ?></td>
            <td class="small text-muted"><?= esc($e['catatan']) ?></td>
            <?php if ($canEdit): ?>
            <td>
                <a href="<?= base_url('events/'.$event['id'].'/loyalty/'.$pid.'/realisasi/'.$e['id'].'/delete') ?>"
                   class="btn btn-xs btn-outline-danger" style="padding:.15rem .4rem;font-size:.7rem"
                   onclick="return confirm('Hapus entri ini?')"><i class="bi bi-trash"></i></a>
            </td>
            <?php endif; ?>
        </tr>
        <?php endforeach; ?>
        <tr class="table-light fw-semibold">
            <td class="ps-3 small">Total</td>
            <td class="small"><?= number_format($rTotal) ?></td>
            <td class="small"><?= number_format($rTotalAktif) ?></td>
            <td colspan="<?= $canEdit ? 2 : 1 ?>"></td>
        </tr>
        </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; // section member ?>

    <!-- ── Section: e-Voucher ─────────────────────────────────────────────── -->
    <?php if ($canEdit || $hasVoucherData): ?>
    <div class="border-top">
        <!-- Section header — yellow tint -->
        <div class="px-3 py-2 bg-warning-subtle d-flex justify-content-between align-items-center">
            <span class="small fw-semibold text-warning-emphasis">
                <i class="bi bi-ticket-perforated me-1"></i>e-Voucher
                <?php if (!empty($myVouchers)): ?>
                <span class="badge bg-warning text-dark ms-1" style="font-size:.65rem"><?= count($myVouchers) ?> item</span>
                <?php endif; ?>
                <?php if ($totalVoucherBudget > 0): ?>
                <span class="badge bg-secondary-subtle text-secondary ms-1" style="font-size:.65rem">Rp <?= number_format($totalVoucherBudget,0,',','.') ?></span>
                <?php endif; ?>
            </span>
            <?php if ($canEdit): ?>
            <button class="btn btn-xs btn-outline-warning toggle-add-voucher-item" style="padding:.2rem .6rem;font-size:.75rem"
                data-pid="<?= $pid ?>">
                <i class="bi bi-plus-lg me-1"></i>Tambah Voucher
            </button>
            <?php endif; ?>
        </div>

        <!-- Form tambah item voucher (hidden) -->
        <?php if ($canEdit): ?>
        <div id="add-voucher-item-<?= $pid ?>" class="d-none px-3 py-2 border-top bg-white">
            <form method="POST" action="<?= base_url('events/'.$event['id'].'/loyalty/'.$pid.'/voucher/add') ?>">
                <?= csrf_field() ?>
                <div class="row g-2 align-items-end">
                    <div class="col-sm-3">
                        <label class="form-label small fw-semibold mb-1">Nama Voucher <span class="text-danger">*</span></label>
                        <input type="text" name="nama_voucher" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-sm-2">
                        <label class="form-label small fw-semibold mb-1">Nilai / pcs (Rp)</label>
                        <input type="text" name="nilai_voucher" class="form-control form-control-sm currency-input" placeholder="0">
                    </div>
                    <div class="col-sm-2">
                        <label class="form-label small fw-semibold mb-1">Qty Diterbitkan</label>
                        <input type="number" name="total_diterbitkan" class="form-control form-control-sm" placeholder="0" min="0">
                    </div>
                    <div class="col-sm-2">
                        <label class="form-label small fw-semibold mb-1">Target Penyerapan</label>
                        <div class="input-group input-group-sm">
                            <input type="number" name="target_penyerapan" class="form-control form-control-sm" placeholder="—" min="0" max="100" step="0.1">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    <div class="col">
                        <label class="form-label small fw-semibold mb-1">Catatan</label>
                        <input type="text" name="catatan" class="form-control form-control-sm" placeholder="Opsional">
                    </div>
                    <div class="col-sm-1">
                        <button type="submit" class="btn btn-warning btn-sm w-100 text-dark">+</button>
                    </div>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- Daftar item voucher -->
        <?php foreach ($myVouchers as $vi):
            $vid         = $vi['id'];
            $vr          = $voucherRealisasi[$vid] ?? ['total_tersebar' => 0, 'total_terpakai' => 0, 'entries' => []];
            $vQty        = (int)$vi['total_diterbitkan'];
            $vNilai      = (int)$vi['nilai_voucher'];
            $viBudget    = $vQty * $vNilai;
            $viTersebar  = (int)($vr['total_tersebar'] ?? 0);
            $viTerpakai  = (int)($vr['total_terpakai'] ?? 0);
            $viTargetPct = ($vi['target_penyerapan'] !== null && $vi['target_penyerapan'] !== '') ? (float)$vi['target_penyerapan'] : null;
            $viTargetQty = ($viTargetPct !== null && $vQty > 0) ? round($viTargetPct / 100 * $vQty) : $vQty;
            $viPct       = $viTargetQty > 0 ? min(100, round($viTerpakai / $viTargetQty * 100, 1)) : 0;
            $viColor     = $viPct >= 100 ? 'success' : ($viPct >= 60 ? 'primary' : 'secondary');
        ?>
        <div class="border-top">
            <div class="px-3 py-2 d-flex justify-content-between align-items-start">
                <div class="flex-grow-1">
                    <div class="fw-semibold small"><?= esc($vi['nama_voucher']) ?></div>
                    <div class="d-flex flex-wrap gap-3 small text-muted mt-1">
                        <?php if ($vNilai > 0): ?>
                        <span><i class="bi bi-tag me-1"></i>Nilai/pcs: <strong class="text-body">Rp <?= number_format($vNilai,0,',','.') ?></strong></span>
                        <?php endif; ?>
                        <?php if ($vQty > 0): ?>
                        <span><i class="bi bi-ticket me-1"></i>Diterbitkan: <strong class="text-body"><?= number_format($vQty) ?></strong></span>
                        <?php endif; ?>
                        <?php if ($viTargetPct !== null): ?>
                        <span><i class="bi bi-bullseye me-1"></i>Target penyerapan: <strong class="text-body"><?= number_format($viTargetPct,1) ?>%</strong></span>
                        <?php endif; ?>
                        <?php if ($viBudget > 0): ?>
                        <span><i class="bi bi-cash me-1"></i>Budget: <strong class="text-body">Rp <?= number_format($viBudget,0,',','.') ?></strong></span>
                        <?php endif; ?>
                        <span><i class="bi bi-send me-1"></i>Tersebar: <strong class="text-body"><?= number_format($viTersebar) ?></strong></span>
                        <span><i class="bi bi-check-circle me-1"></i>Terpakai: <strong class="text-body"><?= number_format($viTerpakai) ?></strong></span>
                    </div>
                    <?php if ($viTargetQty > 0): ?>
                    <div class="mt-2">
                        <div class="d-flex justify-content-between small mb-1">
                            <?php if ($viTargetPct !== null): ?>
                            <span class="text-muted"><?= number_format($viTerpakai) ?> / <?= number_format((int)$viTargetQty) ?> target terpakai</span>
                            <?php else: ?>
                            <span class="text-muted"><?= number_format($viTerpakai) ?> / <?= number_format($vQty) ?> terpakai</span>
                            <?php endif; ?>
                            <span class="text-<?= $viColor ?> fw-semibold"><?= $viPct ?>%</span>
                        </div>
                        <div class="progress" style="height:4px">
                            <div class="progress-bar bg-<?= $viColor ?>" style="width:<?= $viPct ?>%"></div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ($vi['catatan']): ?>
                    <div class="small text-muted mt-1"><i class="bi bi-sticky me-1"></i><?= esc($vi['catatan']) ?></div>
                    <?php endif; ?>
                </div>
                <?php if ($canEdit): ?>
                <div class="d-flex gap-1 ms-3 flex-shrink-0">
                    <button class="btn btn-xs btn-outline-secondary toggle-add-voucher-real" style="padding:.2rem .6rem;font-size:.75rem"
                        data-pid="<?= $pid ?>" data-vid="<?= $vid ?>">
                        <i class="bi bi-plus-lg me-1"></i>Realisasi
                    </button>
                    <form method="POST" action="<?= base_url('events/'.$event['id'].'/loyalty/'.$pid.'/voucher/'.$vid.'/delete') ?>" style="display:inline" onsubmit="return confirm('Hapus voucher ini beserta semua realisasinya?')">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-xs btn-outline-danger" style="padding:.2rem .4rem;font-size:.75rem"><i class="bi bi-trash"></i></button>
                    </form>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($canEdit): ?>
            <div id="add-voucher-real-<?= $pid ?>-<?= $vid ?>" class="d-none px-3 py-2 border-top bg-warning-subtle bg-opacity-25">
                <form method="POST" action="<?= base_url('events/'.$event['id'].'/loyalty/'.$pid.'/voucher/'.$vid.'/realisasi/add') ?>">
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

            <?php if (!empty($vr['entries'])): ?>
            <div class="table-responsive border-top">
            <table class="table table-sm mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">Tanggal</th>
                    <th>Tersebar</th>
                    <th>Terpakai</th>
                    <th>Catatan</th>
                    <?php if ($canEdit): ?><th style="width:40px"></th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($vr['entries'] as $er): ?>
            <tr>
                <td class="ps-3 small"><?= date('d M Y', strtotime($er['tanggal'])) ?></td>
                <td class="small fw-medium"><?= number_format($er['tersebar'] ?? 0) ?></td>
                <td class="small fw-medium"><?= number_format($er['terpakai'] ?? 0) ?></td>
                <td class="small text-muted"><?= esc($er['catatan']) ?></td>
                <?php if ($canEdit): ?>
                <td>
                    <form method="POST" action="<?= base_url('events/'.$event['id'].'/loyalty/'.$pid.'/voucher/'.$vid.'/realisasi/'.$er['id'].'/delete') ?>" style="display:inline" onsubmit="return confirm('Hapus entri ini?')">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-xs btn-outline-danger" style="padding:.15rem .4rem;font-size:.7rem"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
            <tr class="table-light fw-semibold">
                <td class="ps-3 small">Total</td>
                <td class="small"><?= number_format($viTersebar) ?></td>
                <td class="small"><?= number_format($viTerpakai) ?></td>
                <td colspan="<?= $canEdit ? 2 : 1 ?>"></td>
            </tr>
            </tbody>
            </table>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; // section voucher ?>

    <!-- ── Section: Hadiah Barang ─────────────────────────────────────────── -->
    <?php if ($canEdit || $hasHadiahData): ?>
    <div class="border-top">
        <!-- Section header — green tint -->
        <div class="px-3 py-2 bg-success-subtle d-flex justify-content-between align-items-center">
            <span class="small fw-semibold text-success-emphasis">
                <i class="bi bi-gift me-1"></i>Hadiah Barang
                <?php if (!empty($myHadiah)): ?>
                <span class="badge bg-success text-white ms-1" style="font-size:.65rem"><?= count($myHadiah) ?> item</span>
                <?php endif; ?>
                <?php if ($totalHadiahBudget > 0): ?>
                <span class="badge bg-secondary-subtle text-secondary ms-1" style="font-size:.65rem">Rp <?= number_format($totalHadiahBudget,0,',','.') ?></span>
                <?php endif; ?>
            </span>
            <?php if ($canEdit): ?>
            <button class="btn btn-xs btn-outline-success toggle-add-hadiah-item" style="padding:.2rem .6rem;font-size:.75rem"
                data-pid="<?= $pid ?>">
                <i class="bi bi-plus-lg me-1"></i>Tambah Item
            </button>
            <?php endif; ?>
        </div>

        <!-- Form tambah item hadiah (hidden) -->
        <?php if ($canEdit): ?>
        <div id="add-hadiah-item-<?= $pid ?>" class="d-none px-3 py-2 border-top bg-white">
            <form method="POST" action="<?= base_url('events/'.$event['id'].'/loyalty/'.$pid.'/hadiah/add') ?>">
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
                        <label class="form-label small fw-semibold mb-1">Nilai / pcs (Rp)</label>
                        <input type="text" name="nilai_satuan" class="form-control form-control-sm currency-input" placeholder="0">
                    </div>
                    <div class="col">
                        <label class="form-label small fw-semibold mb-1">Catatan</label>
                        <input type="text" name="catatan" class="form-control form-control-sm" placeholder="Opsional">
                    </div>
                    <div class="col-sm-1">
                        <button type="submit" class="btn btn-success btn-sm w-100">+</button>
                    </div>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- Daftar item hadiah -->
        <?php foreach ($myHadiah as $hi):
            $hid          = $hi['id'];
            $hr           = $hadiahRealisasi[$hid] ?? ['total' => 0, 'entries' => []];
            $hTotalDibagi = (int)($hr['total'] ?? 0);
            $hStok        = (int)$hi['stok'];
            $hNilai       = (int)$hi['nilai_satuan'];
            $hPct         = $hStok > 0 ? min(100, round($hTotalDibagi / $hStok * 100, 1)) : 0;
            $hColor       = $hPct >= 100 ? 'success' : ($hPct >= 60 ? 'primary' : 'secondary');
        ?>
        <div class="border-top">
            <div class="px-3 py-2 d-flex justify-content-between align-items-start">
                <div class="flex-grow-1">
                    <div class="fw-semibold small"><?= esc($hi['nama_hadiah']) ?></div>
                    <div class="d-flex flex-wrap gap-3 small text-muted mt-1">
                        <?php if ($hNilai > 0): ?>
                        <span><i class="bi bi-tag me-1"></i>Nilai/pcs: <strong class="text-body">Rp <?= number_format($hNilai,0,',','.') ?></strong></span>
                        <?php endif; ?>
                        <?php if ($hStok > 0): ?>
                        <span><i class="bi bi-box-seam me-1"></i>Stok: <strong class="text-body"><?= number_format($hStok) ?></strong></span>
                        <?php endif; ?>
                        <?php if ($hStok > 0 && $hNilai > 0): ?>
                        <span><i class="bi bi-cash me-1"></i>Budget: <strong class="text-body">Rp <?= number_format($hStok * $hNilai,0,',','.') ?></strong></span>
                        <?php endif; ?>
                        <span><i class="bi bi-gift me-1"></i>Dibagikan: <strong class="text-body"><?= number_format($hTotalDibagi) ?></strong></span>
                    </div>
                    <?php if ($hStok > 0): ?>
                    <div class="mt-2">
                        <div class="d-flex justify-content-between small mb-1">
                            <span class="text-muted"><?= number_format($hTotalDibagi) ?> / <?= number_format($hStok) ?></span>
                            <span class="text-<?= $hColor ?> fw-semibold"><?= $hPct ?>%</span>
                        </div>
                        <div class="progress" style="height:4px">
                            <div class="progress-bar bg-<?= $hColor ?>" style="width:<?= $hPct ?>%"></div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ($hi['catatan']): ?>
                    <div class="small text-muted mt-1"><i class="bi bi-sticky me-1"></i><?= esc($hi['catatan']) ?></div>
                    <?php endif; ?>
                </div>
                <?php if ($canEdit): ?>
                <div class="d-flex gap-1 ms-3 flex-shrink-0">
                    <button class="btn btn-xs btn-outline-secondary toggle-add-hadiah-real" style="padding:.2rem .6rem;font-size:.75rem"
                        data-pid="<?= $pid ?>" data-hid="<?= $hid ?>">
                        <i class="bi bi-plus-lg me-1"></i>Realisasi
                    </button>
                    <form method="POST" action="<?= base_url('events/'.$event['id'].'/loyalty/'.$pid.'/hadiah/'.$hid.'/delete') ?>" style="display:inline" onsubmit="return confirm('Hapus item ini beserta semua realisasinya?')">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-xs btn-outline-danger" style="padding:.2rem .4rem;font-size:.75rem"><i class="bi bi-trash"></i></button>
                    </form>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($canEdit): ?>
            <div id="add-hadiah-real-<?= $pid ?>-<?= $hid ?>" class="d-none px-3 py-2 border-top bg-success-subtle bg-opacity-25">
                <form method="POST" action="<?= base_url('events/'.$event['id'].'/loyalty/'.$pid.'/hadiah/'.$hid.'/realisasi/add') ?>">
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

            <?php if (!empty($hr['entries'])): ?>
            <div class="table-responsive border-top">
            <table class="table table-sm mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">Tanggal</th>
                    <th>Dibagikan</th>
                    <th>Catatan</th>
                    <?php if ($canEdit): ?><th style="width:40px"></th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($hr['entries'] as $er): ?>
            <tr>
                <td class="ps-3 small"><?= date('d M Y', strtotime($er['tanggal'])) ?></td>
                <td class="small fw-medium"><?= number_format($er['jumlah_dibagikan']) ?></td>
                <td class="small text-muted"><?= esc($er['catatan']) ?></td>
                <?php if ($canEdit): ?>
                <td>
                    <form method="POST" action="<?= base_url('events/'.$event['id'].'/loyalty/'.$pid.'/hadiah/'.$hid.'/realisasi/'.$er['id'].'/delete') ?>" style="display:inline" onsubmit="return confirm('Hapus entri ini?')">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-xs btn-outline-danger" style="padding:.15rem .4rem;font-size:.7rem"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
            <tr class="table-light fw-semibold">
                <td class="ps-3 small">Total</td>
                <td class="small"><?= number_format($hTotalDibagi) ?></td>
                <td colspan="<?= $canEdit ? 2 : 1 ?>"></td>
            </tr>
            </tbody>
            </table>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; // section hadiah ?>

</div>
<?php endforeach; ?>

<!-- Footer total budget -->
<div class="card mt-2">
    <div class="card-body py-2 d-flex justify-content-between align-items-center">
        <span class="small text-muted"><?= count($programs) ?> program</span>
        <span class="fw-bold">Total Budget: <span class="text-success">Rp <?= number_format($totalBudgetProgram,0,',','.') ?></span></span>
    </div>
</div>

<?php endif; ?>

<?php if ($canEdit): ?>
<!-- Add Modal -->
<div class="modal fade" id="addProgramModal" tabindex="-1">
<div class="modal-dialog"><div class="modal-content">
<form method="POST" action="<?= base_url('events/'.$event['id'].'/loyalty/add') ?>">
<?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title fw-semibold">Tambah Program Loyalty</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="mb-3">
        <label class="form-label small fw-semibold">Nama Program <span class="text-danger">*</span></label>
        <input type="text" name="nama_program" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label small fw-semibold">Mekanisme Program</label>
        <textarea name="mekanisme" class="form-control" rows="2" placeholder="Jelaskan cara kerja program..."></textarea>
    </div>
    <div class="mb-0">
        <label class="form-label small fw-semibold">Catatan</label>
        <input type="text" name="catatan" class="form-control">
    </div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Tambah</button></div>
</form>
</div></div></div>

<!-- Edit Modal -->
<div class="modal fade" id="editProgramModal" tabindex="-1">
<div class="modal-dialog"><div class="modal-content">
<form id="editProgramForm" method="POST">
<?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title fw-semibold">Edit Program</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="mb-3">
        <label class="form-label small fw-semibold">Nama Program <span class="text-danger">*</span></label>
        <input type="text" name="nama_program" id="editNama" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label small fw-semibold">Mekanisme</label>
        <textarea name="mekanisme" id="editMekanisme" class="form-control" rows="3"></textarea>
    </div>
    <div class="mb-3">
        <label class="form-label small fw-semibold">Target Member</label>
        <div class="row g-2">
            <div class="col-6">
                <div class="input-group input-group-sm">
                    <span class="input-group-text">Baru</span>
                    <input type="number" name="target_peserta" id="editTarget" class="form-control" min="0" placeholder="Opsional">
                </div>
            </div>
            <div class="col-6">
                <div class="input-group input-group-sm">
                    <span class="input-group-text">Aktif</span>
                    <input type="number" name="target_member_aktif" id="editTargetAktif" class="form-control" min="0" placeholder="Opsional">
                </div>
            </div>
        </div>
        <div class="form-text">Kosongkan jika tidak ada target.</div>
    </div>
    <div class="mb-0">
        <label class="form-label small fw-semibold">Catatan</label>
        <input type="text" name="catatan" id="editCatatan" class="form-control">
    </div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
</form>
</div></div></div>
<?php endif; ?>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
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
            : '<i class="bi bi-x me-1"></i>Tutup';
    });
});
document.querySelectorAll('.toggle-add-voucher-real').forEach(btn => {
    btn.addEventListener('click', function() {
        const el = document.getElementById('add-voucher-real-' + this.dataset.pid + '-' + this.dataset.vid);
        el.classList.toggle('d-none');
        this.innerHTML = el.classList.contains('d-none')
            ? '<i class="bi bi-plus-lg me-1"></i>Realisasi'
            : '<i class="bi bi-x me-1"></i>Tutup';
    });
});
document.querySelectorAll('.toggle-add-hadiah-item').forEach(btn => {
    btn.addEventListener('click', function() {
        const el = document.getElementById('add-hadiah-item-' + this.dataset.pid);
        el.classList.toggle('d-none');
        this.innerHTML = el.classList.contains('d-none')
            ? '<i class="bi bi-plus-lg me-1"></i>Tambah Item'
            : '<i class="bi bi-x me-1"></i>Tutup';
    });
});
document.querySelectorAll('.toggle-add-hadiah-real').forEach(btn => {
    btn.addEventListener('click', function() {
        const el = document.getElementById('add-hadiah-real-' + this.dataset.pid + '-' + this.dataset.hid);
        el.classList.toggle('d-none');
        this.innerHTML = el.classList.contains('d-none')
            ? '<i class="bi bi-plus-lg me-1"></i>Realisasi'
            : '<i class="bi bi-x me-1"></i>Tutup';
    });
});
document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('editProgramForm').action = '<?= base_url('events/'.$event['id'].'/loyalty/') ?>' + this.dataset.id + '/edit';
        document.getElementById('editNama').value      = this.dataset.nama;
        document.getElementById('editMekanisme').value = this.dataset.mekanisme;
        document.getElementById('editTarget').value      = this.dataset.target;
        document.getElementById('editTargetAktif').value = this.dataset.targetAktif;
        document.getElementById('editCatatan').value   = this.dataset.catatan;
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
