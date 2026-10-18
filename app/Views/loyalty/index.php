<?php helper('tanggal'); ?><?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/events-modul.css') ?>?v=<?= @filemtime(FCPATH . 'css/events-modul.css') ?: 1 ?>">
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php
if (! function_exists('evRp')) {
    function evRp($n): string { return 'Rp ' . number_format((float) $n, 0, ',', '.'); }
}
// Foto bukti realisasi (kecil, bisa diklik)
if (! function_exists('evBukti')) {
    function evBukti(?string $foto): string
    {
        if (! $foto) return '<span class="ev-c-mute">—</span>';
        $u = base_url('uploads/loyalty-realisasi/' . $foto);
        return '<a class="ev-thumb" href="' . $u . '" target="_blank" title="Foto bukti"><img src="' . $u . '" alt="Foto bukti" loading="lazy"></a>';
    }
}
$pctColor = fn($p) => $p >= 100 ? 'ok' : ($p >= 60 ? 'blue' : ($p >= 30 ? 'mid' : 'low'));

// ── Hitung ringkasan tiap program (rumus sama dengan tampilan lama) ──────────
$P = [];
$sum = ['member' => 0, 'mTarget' => 0, 'vQty' => 0, 'vTerpakai' => 0, 'hStok' => 0, 'hDibagi' => 0, 'budget' => 0, 'real' => 0];
foreach ($programs as $p) {
    $pid = $p['id'];
    $rData = $realisasi[$pid] ?? ['total' => 0, 'entries' => []];
    $m = ['rTotal' => (int) $rData['total'], 'entries' => $rData['entries'], 'rAktif' => (int) ($rData['total_aktif'] ?? 0)];
    $m['vouchers'] = $voucherItems[$pid] ?? [];
    $m['vBudget'] = $m['vTerpakai'] = $m['vTersebar'] = $m['vQty'] = 0;
    foreach ($m['vouchers'] as $vi) {
        $m['vBudget'] += (int) $vi['total_diterbitkan'] * (int) $vi['nilai_voucher'];
        $m['vQty']    += (int) $vi['total_diterbitkan'];
        $vr = $voucherRealisasi[$vi['id']] ?? [];
        $m['vTerpakai'] += (int) ($vr['total_terpakai'] ?? 0);
        $m['vTersebar'] += (int) ($vr['total_tersebar'] ?? 0);
    }
    $m['hadiah'] = $hadiahItems[$pid] ?? [];
    $m['hBudget'] = $m['hDibagi'] = $m['hStok'] = 0;
    foreach ($m['hadiah'] as $hi) {
        $m['hBudget'] += (int) $hi['stok'] * (int) $hi['nilai_satuan'];
        $m['hStok']   += (int) $hi['stok'];
        $hr = $hadiahRealisasi[$hi['id']] ?? [];
        $m['hDibagi'] += (int) ($hr['total'] ?? 0);
    }
    $m['auto'] = $m['vBudget'] + $m['hBudget'];
    $m['real'] = 0;
    foreach ($m['vouchers'] as $vi) { $vr = $voucherRealisasi[$vi['id']] ?? []; $m['real'] += (int) ($vr['total_terpakai'] ?? 0) * (int) $vi['nilai_voucher']; }
    foreach ($m['hadiah'] as $hi)   { $hr = $hadiahRealisasi[$hi['id']] ?? [];  $m['real'] += (int) ($hr['total'] ?? 0) * (int) $hi['nilai_satuan']; }
    $m['mTarget']  = (int) ($p['target_peserta'] ?? 0);
    $m['mAktifT']  = (int) ($p['target_member_aktif'] ?? 0);
    $m['mPct']     = $m['mTarget'] > 0 ? min(100, round($m['rTotal'] / $m['mTarget'] * 100, 1)) : 0;
    $m['mAktifPct']= $m['mAktifT'] > 0 ? min(100, round($m['rAktif'] / $m['mAktifT'] * 100, 1)) : 0;
    $m['hasMember']= $m['mTarget'] > 0 || $m['mAktifT'] > 0 || ! empty($m['entries']);
    $P[$pid] = $m;
    $sum['member'] += $m['rTotal']; $sum['mTarget'] += $m['mTarget'];
    $sum['vQty'] += $m['vQty']; $sum['vTerpakai'] += $m['vTerpakai'];
    $sum['hStok'] += $m['hStok']; $sum['hDibagi'] += $m['hDibagi'];
    $sum['budget'] += $m['auto']; $sum['real'] += $m['real'];
}
$bPct = $sum['budget'] > 0 ? min(100, round($sum['real'] / $sum['budget'] * 100, 1)) : 0;
$mPctAll = $sum['mTarget'] > 0 ? min(100, round($sum['member'] / $sum['mTarget'] * 100, 1)) : 0;
$vPctAll = $sum['vQty'] > 0 ? min(100, round($sum['vTerpakai'] / $sum['vQty'] * 100, 1)) : 0;
$hPctAll = $sum['hStok'] > 0 ? min(100, round($sum['hDibagi'] / $sum['hStok'] * 100, 1)) : 0;

$actions = '';
if ($canEdit) {
    $actions = '<button class="ev-btn ev-btn-primary" data-bs-toggle="modal" data-bs-target="#addProgramModal"><i class="bi bi-plus-lg"></i>Tambah Program</button>';
}
?>

<div class="page-evm evm-loyalty" id="evmRoot" data-key="loy-<?= $event['id'] ?>" data-unit="program">

<?= view('partials/evm_head', ['event' => $event, 'module' => 'loyalty', 'completion' => $completion, 'canEdit' => $canEdit,
    'title' => 'Program Loyalty', 'icon' => 'bi-star-fill', 'actions' => $actions]) ?>

<!-- Strip KPI -->
<div class="ev-kpis">
    <div class="ev-kpi">
        <div class="ev-kpi-l">Program Loyalty</div>
        <div class="ev-kpi-v"><?= count($programs) ?></div>
        <div class="ev-kpi-s"><?= count(array_filter($P, fn($m) => $m['auto'] > 0)) ?> berbudget · <?= count(array_filter($P, fn($m) => $m['real'] > 0 || $m['rTotal'] > 0)) ?> sudah ada realisasi</div>
    </div>
    <div class="ev-kpi t-cyan">
        <div class="ev-kpi-l">Member Baru</div>
        <div class="ev-kpi-v ev-c-cyan"><?= number_format($sum['member']) ?></div>
        <div class="ev-kpi-s"><?= $sum['mTarget'] > 0 ? $mPctAll . '% dari target ' . number_format($sum['mTarget']) : 'belum ada target member' ?></div>
        <?php if ($sum['mTarget'] > 0): ?><div class="ev-bar ev-bar-<?= $pctColor($mPctAll) ?>"><i style="width:<?= $mPctAll ?>%"></i></div><?php endif; ?>
    </div>
    <div class="ev-kpi t-warn">
        <div class="ev-kpi-l">e-Voucher Terpakai</div>
        <div class="ev-kpi-v ev-c-warn"><?= number_format($sum['vTerpakai']) ?></div>
        <div class="ev-kpi-s"><?= $sum['vQty'] > 0 ? $vPctAll . '% dari ' . number_format($sum['vQty']) . ' diterbitkan' : 'belum ada voucher' ?></div>
        <?php if ($sum['vQty'] > 0): ?><div class="ev-bar ev-bar-<?= $pctColor($vPctAll) ?>"><i style="width:<?= $vPctAll ?>%"></i></div><?php endif; ?>
    </div>
    <div class="ev-kpi t-ok">
        <div class="ev-kpi-l">Hadiah Dibagikan</div>
        <div class="ev-kpi-v ev-c-ok"><?= number_format($sum['hDibagi']) ?></div>
        <div class="ev-kpi-s"><?= $sum['hStok'] > 0 ? $hPctAll . '% dari stok ' . number_format($sum['hStok']) : 'belum ada item hadiah' ?></div>
        <?php if ($sum['hStok'] > 0): ?><div class="ev-bar ev-bar-<?= $pctColor($hPctAll) ?>"><i style="width:<?= $hPctAll ?>%"></i></div><?php endif; ?>
    </div>
    <div class="ev-kpi <?= $sum['budget'] > 0 && $sum['real'] > $sum['budget'] ? 't-bad' : '' ?>">
        <div class="ev-kpi-l">Budget vs Realisasi</div>
        <div class="ev-kpi-v"><?= evRp($sum['real']) ?></div>
        <div class="ev-kpi-s">dari budget <?= evRp($totalBudgetProgram) ?><?= $sum['budget'] > 0 ? ' · ' . $bPct . '%' : '' ?></div>
        <?php if ($sum['budget'] > 0): ?><div class="ev-bar ev-bar-<?= $sum['real'] > $sum['budget'] ? 'low' : 'blue' ?>"><i style="width:<?= $bPct ?>%"></i></div><?php endif; ?>
    </div>
</div>

<?php if (empty($programs)): ?>
<div class="ev-empty">
    <i class="bi bi-star"></i>
    <h5>Belum ada program loyalty untuk event ini</h5>
    <p>Buat program, lalu catat target member, e-voucher, dan hadiah beserta realisasinya.</p>
    <?php if ($canEdit): ?>
    <button class="ev-btn ev-btn-primary" data-bs-toggle="modal" data-bs-target="#addProgramModal"><i class="bi bi-plus-lg"></i>Buat Program Pertama</button>
    <?php endif; ?>
</div>
<?php else: ?>

<div class="ev-tools">
    <div class="ev-search">
        <i class="bi bi-search"></i>
        <input type="search" id="evQ" placeholder="Cari program, hadiah, penerima…" autocomplete="off" aria-label="Cari program loyalty">
    </div>
    <select class="ev-select" data-ev-filter="has" data-ev-multi aria-label="Filter komponen">
        <option value="">Semua komponen</option>
        <option value="member">Ada target/realisasi member</option>
        <option value="voucher">Ada e-voucher</option>
        <option value="hadiah">Ada hadiah / voucher fisik</option>
    </select>
    <select class="ev-select" data-ev-filter="state" aria-label="Filter budget">
        <option value="">Semua kondisi budget</option>
        <option value="belum">Belum ada realisasi</option>
        <option value="jalan">Realisasi berjalan</option>
        <option value="over">Melebihi budget</option>
    </select>
    <select class="ev-select" id="evSort" aria-label="Urutkan">
        <option value="" data-dir="asc">Urutan: input</option>
        <option value="name" data-type="str" data-dir="asc">Nama A–Z</option>
        <option value="budget" data-dir="desc">Budget terbesar</option>
        <option value="real" data-dir="desc">Realisasi terbesar</option>
    </select>
    <div class="ev-tools-end">
        <span class="ev-count" id="evCount" aria-live="polite"></span>
        <button type="button" class="ev-btn ev-btn-sm" id="evExpand" title="Buka semua program"><i class="bi bi-arrows-expand"></i><span class="d-none d-md-inline">Buka semua</span></button>
        <button type="button" class="ev-btn ev-btn-sm" id="evCollapse" title="Tutup semua program"><i class="bi bi-arrows-collapse"></i><span class="d-none d-md-inline">Tutup semua</span></button>
    </div>
</div>

<div class="ev-empty ev-empty-filter" id="evNoMatch" hidden>
    <i class="bi bi-search"></i>
    <h5>Tidak ada program yang cocok</h5>
    <p>Ubah kata kunci atau filter.</p>
    <button type="button" class="ev-btn" id="evReset"><i class="bi bi-x-circle"></i>Reset filter</button>
</div>

<section class="ev-group">
<div class="ev-list">
<?php foreach ($programs as $n => $p):
    $pid = $p['id']; $m = $P[$pid];
    $rTotal = $m['rTotal']; $rTotalAktif = $m['rAktif']; $memberEntries = $m['entries'];
    $myVouchers = $m['vouchers']; $myHadiah = $m['hadiah'];
    $autoBudget = $m['auto']; $budgetReal = $m['real'];
    $memberTarget = $m['mTarget']; $memberAktifTarget = $m['mAktifT'];
    $memberPct = $m['mPct']; $memberAktifPct = $m['mAktifPct'];
    $brPct = $autoBudget > 0 ? min(100, round($budgetReal / $autoBudget * 100, 1)) : 0;
    $over  = $autoBudget > 0 && $budgetReal > $autoBudget;
    $state = ($budgetReal <= 0 && $rTotal <= 0) ? 'belum' : ($over ? 'over' : 'jalan');
    $has   = array_filter([$m['hasMember'] ? 'member' : null, ! empty($myVouchers) ? 'voucher' : null, ! empty($myHadiah) ? 'hadiah' : null]);
    $names = [];
    foreach ($myVouchers as $vi) { $names[] = $vi['nama_voucher']; foreach (($voucherRealisasi[$vi['id']]['entries'] ?? []) as $er) { $names[] = $er['nama_penerima'] ?? ''; } }
    foreach ($myHadiah as $hi)   { $names[] = $hi['nama_hadiah'];  foreach (($hadiahRealisasi[$hi['id']]['entries'] ?? []) as $er) { $names[] = $er['nama_penerima'] ?? ''; } }
    $search = mb_strtolower($p['nama_program'] . ' ' . ($p['mekanisme'] ?? '') . ' ' . ($p['catatan'] ?? '') . ' ' . implode(' ', $names));
?>
<article class="ev-card<?= $over ? ' is-bad' : '' ?>" id="program-<?= $pid ?>"
         data-name="<?= esc($p['nama_program'], 'attr') ?>" data-search="<?= esc($search, 'attr') ?>"
         data-has="<?= implode(',', $has) ?>" data-state="<?= $state ?>"
         data-budget="<?= $autoBudget ?>" data-real="<?= $budgetReal ?>" data-order="<?= $n ?>">
    <div class="ev-card-head" data-ev-toggle>
        <button type="button" class="ev-chev" aria-expanded="false" aria-controls="body-<?= $pid ?>" aria-label="Buka atau tutup rincian program"><i class="bi bi-chevron-right"></i></button>
        <div class="min-w-0">
            <div class="ev-card-name">
                <span class="ev-card-t"><?= esc($p['nama_program']) ?></span>
                <?php if ($over): ?><span class="ev-chip ev-chip-bad"><i class="bi bi-exclamation-triangle-fill"></i>Over budget</span><?php endif; ?>
            </div>
            <div class="ev-card-meta">
                <?php if ($m['hasMember']): ?><span><i class="bi bi-person-plus"></i>Member <?= number_format($rTotal) ?><?= $memberTarget > 0 ? ' / ' . number_format($memberTarget) : '' ?></span><?php endif; ?>
                <span><i class="bi bi-ticket-perforated"></i><?= count($myVouchers) ?> e-voucher</span>
                <span><i class="bi bi-gift"></i><?= count($myHadiah) ?> item hadiah</span>
                <?php if ($p['catatan']): ?><span><i class="bi bi-sticky"></i><?= esc($p['catatan']) ?></span><?php endif; ?>
            </div>
        </div>
        <div class="ev-fig">
            <div class="ev-fig-row">
                <div><span class="ev-fig-l">Budget</span><b><?= $autoBudget > 0 ? evRp($autoBudget) : '—' ?></b></div>
                <div><span class="ev-fig-l">Realisasi</span><b class="<?= $over ? 'ev-c-bad' : 'ev-c-cyan' ?>"><?= evRp($budgetReal) ?></b></div>
                <div><span class="ev-fig-l">Terserap</span><b><?= $autoBudget > 0 ? $brPct . '%' : '—' ?></b></div>
            </div>
            <div class="ev-fig-bar">
                <div class="ev-bar ev-bar-<?= $over ? 'low' : ($brPct >= 100 ? 'ok' : 'blue') ?>"><i style="width:<?= $brPct ?>%"></i></div>
            </div>
        </div>
        <div class="ev-card-act" data-ev-notoggle>
            <?php if ($canEdit): ?>
            <div class="dropdown">
                <button class="ev-btn ev-btn-sm ev-btn-soft" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi bi-plus-lg"></i><span class="d-none d-sm-inline">Tambah</span><i class="bi bi-chevron-down ev-caret"></i></button>
                <ul class="dropdown-menu dropdown-menu-end ev-menu">
                    <li><button type="button" class="dropdown-item" data-ev-open="add-realisasi-<?= $pid ?>"><i class="bi bi-person-plus"></i>Input realisasi member</button></li>
                    <li><button type="button" class="dropdown-item" data-ev-open="add-voucher-item-<?= $pid ?>"><i class="bi bi-ticket-perforated"></i>Tambah e-voucher</button></li>
                    <li><button type="button" class="dropdown-item" data-ev-open="add-hadiah-item-<?= $pid ?>"><i class="bi bi-gift"></i>Tambah barang / voucher fisik</button></li>
                </ul>
            </div>
            <div class="dropdown">
                <button class="ev-iconbtn" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Aksi program"><i class="bi bi-three-dots-vertical"></i></button>
                <ul class="dropdown-menu dropdown-menu-end ev-menu">
                    <li>
                        <button type="button" class="dropdown-item edit-btn"
                            data-id="<?= $p['id'] ?>"
                            data-nama="<?= esc($p['nama_program'], 'attr') ?>"
                            data-mekanisme="<?= esc($p['mekanisme'] ?? '', 'attr') ?>"
                            data-target="<?= $p['target_peserta'] ?? '' ?>"
                            data-target-aktif="<?= $p['target_member_aktif'] ?? '' ?>"
                            data-catatan="<?= esc($p['catatan'] ?? '', 'attr') ?>"><i class="bi bi-pencil"></i>Edit program</button>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="<?= base_url('events/' . $event['id'] . '/loyalty/' . $p['id'] . '/delete') ?>" onsubmit="return confirm('Hapus program beserta semua data realisasinya?')">
                            <?= csrf_field() ?>
                            <button class="dropdown-item text-danger"><i class="bi bi-trash"></i>Hapus program</button>
                        </form>
                    </li>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="ev-card-body" id="body-<?= $pid ?>" hidden>
        <?php if ($p['mekanisme']): ?>
        <div class="ev-pad"><div class="ev-fig-l mb-1">Mekanisme program</div><p class="ev-desc"><?= esc($p['mekanisme']) ?></p></div>
        <?php endif; ?>

        <!-- ── Target Member ── -->
        <?php if ($canEdit || $m['hasMember']): ?>
        <div class="ev-sec s-blue">
            <div class="ev-sec-h">
                <span class="ev-sec-t"><i class="bi bi-person-plus"></i>Target Member
                    <?php if ($memberTarget > 0): ?><span class="ev-chip ev-chip-blue"><?= number_format($memberTarget) ?> baru</span><?php endif; ?>
                    <?php if ($memberAktifTarget > 0): ?><span class="ev-chip ev-chip-violet"><?= number_format($memberAktifTarget) ?> aktif</span><?php endif; ?>
                    <?php if (! empty($memberEntries)): ?><span class="ev-chip ev-chip-mute"><?= count($memberEntries) ?> entri</span><?php endif; ?></span>
                <?php if ($canEdit): ?>
                <button type="button" class="ev-btn ev-btn-xs ev-btn-soft" data-ev-panel="add-realisasi-<?= $pid ?>" aria-expanded="false"><i class="bi bi-plus-lg"></i>Input</button>
                <?php endif; ?>
            </div>
            <?php if ($memberTarget > 0 || $memberAktifTarget > 0): ?>
            <div class="ev-prog">
                <?php if ($memberTarget > 0): ?>
                <div>
                    <div class="ev-prog-row-t"><span>Member baru: <b><?= number_format($rTotal) ?></b></span><span>Target <b><?= number_format($memberTarget) ?></b> · <b><?= $memberPct ?>%</b></span></div>
                    <div class="ev-bar ev-bar-<?= $pctColor($memberPct) ?>"><i style="width:<?= $memberPct ?>%"></i></div>
                </div>
                <?php endif; ?>
                <?php if ($memberAktifTarget > 0): ?>
                <div>
                    <div class="ev-prog-row-t"><span>Member aktif: <b><?= number_format($rTotalAktif) ?></b></span><span>Target <b><?= number_format($memberAktifTarget) ?></b> · <b><?= $memberAktifPct ?>%</b></span></div>
                    <div class="ev-bar ev-bar-<?= $pctColor($memberAktifPct) ?>"><i style="width:<?= $memberAktifPct ?>%"></i></div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if ($canEdit): ?>
            <div class="ev-panel" id="add-realisasi-<?= $pid ?>" hidden>
                <form method="POST" action="<?= base_url('events/' . $event['id'] . '/loyalty/' . $pid . '/realisasi/add') ?>">
                    <?= csrf_field() ?>
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3 col-6"><label class="form-label">Tanggal</label>
                            <input type="date" name="tanggal" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required></div>
                        <div class="col-md-2 col-6"><label class="form-label">Member Baru</label>
                            <input type="number" name="jumlah" class="form-control form-control-sm" placeholder="0" min="0" value="0"></div>
                        <div class="col-md-2 col-6"><label class="form-label">Member Aktif</label>
                            <input type="number" name="member_aktif" class="form-control form-control-sm" placeholder="0" min="0" value="0"></div>
                        <div class="col-md"><label class="form-label">Catatan</label>
                            <input type="text" name="catatan" class="form-control form-control-sm" placeholder="Opsional"></div>
                        <div class="col-md-auto d-flex gap-2">
                            <button type="submit" class="ev-btn ev-btn-sm ev-btn-primary"><i class="bi bi-check-lg"></i>Simpan</button>
                            <button type="button" class="ev-btn ev-btn-sm" data-ev-panel="add-realisasi-<?= $pid ?>">Batal</button>
                        </div>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <?php if (! empty($memberEntries)): ?>
            <div class="ev-tbl-wrap">
            <table class="ev-tbl ev-stack">
                <thead><tr><th>Tanggal</th><th class="ev-num">Member baru</th><th class="ev-num">Member aktif</th><th>Catatan</th><?php if ($canEdit): ?><th></th><?php endif; ?></tr></thead>
                <tbody>
                <?php foreach ($memberEntries as $e): ?>
                <tr>
                    <td class="ev-nowrap" data-l="Tanggal"><?= tgl_indo_pendek($e['tanggal'], false) ?></td>
                    <td class="ev-num" data-l="Member baru"><?= number_format($e['jumlah']) ?></td>
                    <td class="ev-num" data-l="Member aktif"><?= number_format($e['member_aktif'] ?? 0) ?></td>
                    <td data-l="Catatan"><?= esc($e['catatan'] ?: '—') ?><span class="ev-who"><i class="bi bi-person"></i>oleh <?= esc($e['pengisi'] ?? '—') ?></span></td>
                    <?php if ($canEdit): ?>
                    <td class="ev-act-cell">
                        <form method="POST" action="<?= base_url('events/' . $event['id'] . '/loyalty/' . $pid . '/realisasi/' . $e['id'] . '/delete') ?>" class="ev-form-inline" onsubmit="return confirm('Hapus entri ini?')">
                            <?= csrf_field() ?>
                            <button class="ev-iconbtn ev-iconbtn-sm ev-iconbtn-bad" title="Hapus entri" aria-label="Hapus entri member"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot><tr><td>Total</td><td class="ev-num"><?= number_format($rTotal) ?></td><td class="ev-num"><?= number_format($rTotalAktif) ?></td><td colspan="<?= $canEdit ? 2 : 1 ?>"></td></tr></tfoot>
            </table>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- ── e-Voucher ── -->
        <?php if ($canEdit || ! empty($myVouchers)): ?>
        <div class="ev-sec s-warn">
            <div class="ev-sec-h">
                <span class="ev-sec-t"><i class="bi bi-ticket-perforated"></i>e-Voucher
                    <?php if (! empty($myVouchers)): ?><span class="ev-chip ev-chip-warn"><?= count($myVouchers) ?> item</span><?php endif; ?>
                    <?php if ($m['vBudget'] > 0): ?><span class="ev-chip ev-chip-mute"><?= evRp($m['vBudget']) ?></span><?php endif; ?></span>
                <?php if ($canEdit): ?>
                <button type="button" class="ev-btn ev-btn-xs ev-btn-soft" data-ev-panel="add-voucher-item-<?= $pid ?>" aria-expanded="false"><i class="bi bi-plus-lg"></i>Tambah voucher</button>
                <?php endif; ?>
            </div>

            <?php if ($canEdit): ?>
            <div class="ev-panel" id="add-voucher-item-<?= $pid ?>" hidden>
                <form method="POST" action="<?= base_url('events/' . $event['id'] . '/loyalty/' . $pid . '/voucher/add') ?>">
                    <?= csrf_field() ?>
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3"><label class="form-label">Nama Voucher <span class="text-danger">*</span></label>
                            <input type="text" name="nama_voucher" class="form-control form-control-sm" required></div>
                        <div class="col-md-2 col-6"><label class="form-label">Nilai / pcs (Rp)</label>
                            <input type="text" name="nilai_voucher" class="form-control form-control-sm currency-input" placeholder="0"></div>
                        <div class="col-md-2 col-6"><label class="form-label">Qty Diterbitkan</label>
                            <input type="number" name="total_diterbitkan" class="form-control form-control-sm" placeholder="0" min="0"></div>
                        <div class="col-md-2 col-6"><label class="form-label">Target Penyerapan</label>
                            <div class="input-group input-group-sm">
                                <input type="number" name="target_penyerapan" class="form-control form-control-sm" placeholder="—" min="0" max="100" step="0.1">
                                <span class="input-group-text">%</span>
                            </div></div>
                        <div class="col-md"><label class="form-label">Catatan</label>
                            <input type="text" name="catatan" class="form-control form-control-sm" placeholder="Opsional"></div>
                        <div class="col-md-auto d-flex gap-2">
                            <button type="submit" class="ev-btn ev-btn-sm ev-btn-primary"><i class="bi bi-check-lg"></i>Tambah</button>
                            <button type="button" class="ev-btn ev-btn-sm" data-ev-panel="add-voucher-item-<?= $pid ?>">Batal</button>
                        </div>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <?php if (empty($myVouchers)): ?><div class="ev-empty-sm">Belum ada e-voucher.</div><?php endif; ?>
            <?php foreach ($myVouchers as $vi):
                $vid = $vi['id'];
                $vr  = $voucherRealisasi[$vid] ?? ['total_tersebar' => 0, 'total_terpakai' => 0, 'entries' => []];
                $vQty = (int) $vi['total_diterbitkan']; $vNilai = (int) $vi['nilai_voucher'];
                $viBudget = $vQty * $vNilai;
                $viTersebar = (int) ($vr['total_tersebar'] ?? 0); $viTerpakai = (int) ($vr['total_terpakai'] ?? 0);
                $viTargetPct = ($vi['target_penyerapan'] !== null && $vi['target_penyerapan'] !== '') ? (float) $vi['target_penyerapan'] : null;
                $viTargetQty = ($viTargetPct !== null && $vQty > 0) ? round($viTargetPct / 100 * $vQty) : $vQty;
                $viPct = $viTargetQty > 0 ? min(100, round($viTerpakai / $viTargetQty * 100, 1)) : 0;
            ?>
            <div class="ev-sub">
                <div class="ev-sub-h">
                    <div class="min-w-0 flex-grow-1">
                        <div class="ev-sub-name"><?= esc($vi['nama_voucher']) ?></div>
                        <div class="ev-stats">
                            <?php if ($vNilai > 0): ?><span><i class="bi bi-tag"></i>Nilai/pcs <b><?= evRp($vNilai) ?></b></span><?php endif; ?>
                            <?php if ($vQty > 0): ?><span><i class="bi bi-ticket"></i>Diterbitkan <b><?= number_format($vQty) ?></b></span><?php endif; ?>
                            <?php if ($viTargetPct !== null): ?><span><i class="bi bi-bullseye"></i>Target penyerapan <b><?= number_format($viTargetPct, 1) ?>%</b></span><?php endif; ?>
                            <?php if ($viBudget > 0): ?><span><i class="bi bi-cash"></i>Budget <b><?= evRp($viBudget) ?></b></span><?php endif; ?>
                            <span><i class="bi bi-send"></i>Tersebar <b><?= number_format($viTersebar) ?></b></span>
                            <span><i class="bi bi-check-circle"></i>Terpakai <b><?= number_format($viTerpakai) ?></b></span>
                        </div>
                        <?php if ($viTargetQty > 0): ?>
                        <div class="ev-sub-bar">
                            <div class="ev-sub-bar-t">
                                <span><?= number_format($viTerpakai) ?> / <?= number_format($viTargetPct !== null ? (int) $viTargetQty : $vQty) ?> <?= $viTargetPct !== null ? 'target ' : '' ?>terpakai</span>
                                <b class="<?= $viPct >= 100 ? 'ev-c-ok' : '' ?>"><?= $viPct ?>%</b>
                            </div>
                            <div class="ev-bar ev-bar-<?= $pctColor($viPct) ?>"><i style="width:<?= $viPct ?>%"></i></div>
                        </div>
                        <?php endif; ?>
                        <?php if ($vi['catatan']): ?><div class="ev-desc mt-1"><i class="bi bi-sticky me-1"></i><?= esc($vi['catatan']) ?></div><?php endif; ?>
                    </div>
                    <?php if ($canEdit): ?>
                    <div class="ev-sub-act">
                        <button type="button" class="ev-btn ev-btn-xs ev-btn-ok" data-ev-panel="add-voucher-real-<?= $pid ?>-<?= $vid ?>" data-kode-panel aria-expanded="false"><i class="bi bi-plus-lg"></i>Realisasi</button>
                        <form method="POST" action="<?= base_url('events/' . $event['id'] . '/loyalty/' . $pid . '/voucher/' . $vid . '/delete') ?>" class="ev-form-inline" onsubmit="return confirm('Hapus voucher ini beserta semua realisasinya?')">
                            <?= csrf_field() ?>
                            <button type="submit" class="ev-iconbtn ev-iconbtn-sm ev-iconbtn-bad" title="Hapus voucher" aria-label="Hapus voucher"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if ($canEdit): ?>
                <div class="ev-panel" id="add-voucher-real-<?= $pid ?>-<?= $vid ?>" hidden>
                    <form method="POST" action="<?= base_url('events/' . $event['id'] . '/loyalty/' . $pid . '/voucher/' . $vid . '/realisasi/add') ?>" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <div class="row g-2 align-items-end">
                            <div class="col-md-2 col-6"><label class="form-label">Tanggal</label>
                                <input type="date" name="tanggal" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required></div>
                            <?php if (! empty($vi['batch_id'])): ?>
                            <div class="col-md-3"><label class="form-label">Foto Bukti <span class="text-danger">*</span></label>
                                <input type="file" name="foto" accept="image/*" capture="environment" class="form-control form-control-sm" required></div>
                            <div class="col-md-3"><label class="form-label">Kode Voucher</label>
                                <select name="kode_id" class="form-select form-select-sm voucher-kode-select" data-batch-id="<?= $vi['batch_id'] ?>">
                                    <option value="">— loading… —</option>
                                </select></div>
                            <?php else: ?>
                            <div class="col-md-2 col-6"><label class="form-label">Tersebar</label>
                                <input type="number" name="tersebar" class="form-control form-control-sm" placeholder="0" min="0"></div>
                            <div class="col-md-2 col-6"><label class="form-label">Terpakai</label>
                                <input type="number" name="terpakai" class="form-control form-control-sm" placeholder="0" min="0"></div>
                            <?php endif; ?>
                            <div class="col-md-3"><label class="form-label">Nama Penerima</label>
                                <input type="text" name="nama_penerima" class="form-control form-control-sm" placeholder="Opsional"></div>
                            <div class="col-md"><label class="form-label">Catatan</label>
                                <input type="text" name="catatan" class="form-control form-control-sm" placeholder="Opsional"></div>
                            <div class="col-md-auto d-flex gap-2">
                                <button type="submit" class="ev-btn ev-btn-sm ev-btn-primary"><i class="bi bi-check-lg"></i>Simpan</button>
                                <button type="button" class="ev-btn ev-btn-sm" data-ev-panel="add-voucher-real-<?= $pid ?>-<?= $vid ?>">Batal</button>
                            </div>
                        </div>
                    </form>
                </div>
                <?php endif; ?>

                <?php if (! empty($vr['entries'])): ?>
                <div class="ev-tbl-wrap">
                <table class="ev-tbl ev-stack">
                    <thead><tr><th>Tanggal</th><th class="ev-num">Tersebar</th><th class="ev-num">Terpakai</th><th>Penerima</th><th>Kode</th><th>Catatan</th><th>Foto bukti</th><?php if ($canEdit): ?><th></th><?php endif; ?></tr></thead>
                    <tbody>
                    <?php foreach ($vr['entries'] as $er): ?>
                    <tr>
                        <td class="ev-nowrap" data-l="Tanggal"><?= tgl_indo_pendek($er['tanggal'], false) ?></td>
                        <td class="ev-num" data-l="Tersebar"><?= number_format($er['tersebar'] ?? 0) ?></td>
                        <td class="ev-num" data-l="Terpakai"><?= number_format($er['terpakai'] ?? 0) ?></td>
                        <td data-l="Penerima"><?= ! empty($er['nama_penerima']) ? '<b>' . esc($er['nama_penerima']) . '</b>' : '<span class="ev-c-mute">—</span>' ?></td>
                        <td data-l="Kode"><?= ! empty($er['kode_voucher']) ? '<code>' . esc($er['kode_voucher']) . '</code>' : '<span class="ev-c-mute">—</span>' ?></td>
                        <td data-l="Catatan"><?= esc($er['catatan'] ?: '—') ?><span class="ev-who"><i class="bi bi-person"></i>oleh <?= esc($er['pengisi'] ?? '—') ?></span></td>
                        <td data-l="Foto"><?= evBukti($er['foto'] ?? null) ?></td>
                        <?php if ($canEdit): ?>
                        <td class="ev-act-cell">
                            <form method="POST" action="<?= base_url('events/' . $event['id'] . '/loyalty/' . $pid . '/voucher/' . $vid . '/realisasi/' . $er['id'] . '/delete') ?>" class="ev-form-inline" onsubmit="return confirm('Hapus entri ini?')">
                                <?= csrf_field() ?>
                                <button type="submit" class="ev-iconbtn ev-iconbtn-sm ev-iconbtn-bad" title="Hapus entri" aria-label="Hapus entri voucher"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot><tr><td>Total</td><td class="ev-num"><?= number_format($viTersebar) ?></td><td class="ev-num"><?= number_format($viTerpakai) ?></td><td colspan="<?= $canEdit ? 5 : 4 ?>"></td></tr></tfoot>
                </table>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- ── Barang / Voucher Fisik ── -->
        <?php if ($canEdit || ! empty($myHadiah)): ?>
        <div class="ev-sec s-ok">
            <div class="ev-sec-h">
                <span class="ev-sec-t"><i class="bi bi-gift"></i>Barang / Voucher Fisik
                    <?php if (! empty($myHadiah)): ?><span class="ev-chip ev-chip-ok"><?= count($myHadiah) ?> item</span><?php endif; ?>
                    <?php if ($m['hBudget'] > 0): ?><span class="ev-chip ev-chip-mute"><?= evRp($m['hBudget']) ?></span><?php endif; ?></span>
                <?php if ($canEdit): ?>
                <button type="button" class="ev-btn ev-btn-xs ev-btn-soft" data-ev-panel="add-hadiah-item-<?= $pid ?>" aria-expanded="false"><i class="bi bi-plus-lg"></i>Tambah item</button>
                <?php endif; ?>
            </div>

            <?php if ($canEdit): ?>
            <div class="ev-panel" id="add-hadiah-item-<?= $pid ?>" hidden>
                <form method="POST" action="<?= base_url('events/' . $event['id'] . '/loyalty/' . $pid . '/hadiah/add') ?>">
                    <?= csrf_field() ?>
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">Dari Stock Barang</label>
                            <select name="barang_id" class="form-select form-select-sm event-barang-select">
                                <option value="">— Manual —</option>
                                <?php foreach ($stockBarang as $b):
                                    $avail = max(0, (int) $b['stok_tersedia'] - (int) ($b['stok_reserved'] ?? 0)); ?>
                                <option value="<?= $b['id'] ?>" data-nama="<?= esc($b['nama_barang']) ?>" data-nilai="<?= $b['nilai_satuan'] ?>" data-stok="<?= $avail ?>">
                                    <?= esc($b['nama_barang']) ?> (bebas: <?= $avail ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Dari Stock Voucher Fisik</label>
                            <select name="batch_id" class="form-select form-select-sm event-batch-select">
                                <option value="">— Manual —</option>
                                <?php foreach ($stockVoucherBatch as $b):
                                    $bavail = max(0, (int) $b['sisa_kode'] - (int) ($b['stok_reserved'] ?? 0)); ?>
                                <option value="<?= $b['id'] ?>" data-nama="<?= esc($b['nama_voucher']) ?>" data-nilai="<?= $b['nilai_voucher'] ?>" data-stok="<?= $bavail ?>">
                                    <?= esc($b['nama_voucher']) ?> (bebas: <?= $bavail ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3"><label class="form-label">Nama Item <span class="text-danger">*</span></label>
                            <input type="text" name="nama_hadiah" class="form-control form-control-sm event-hadiah-nama" required></div>
                        <div class="col-md-1 col-4"><label class="form-label">Qty</label>
                            <input type="number" name="stok" class="form-control form-control-sm event-hadiah-stok" placeholder="0" min="1"></div>
                        <div class="col-md-2 col-8"><label class="form-label">Nilai / pcs (Rp)</label>
                            <input type="text" name="nilai_satuan" class="form-control form-control-sm currency-input event-hadiah-nilai" placeholder="0"></div>
                        <div class="col-md"><label class="form-label">Catatan</label>
                            <input type="text" name="catatan" class="form-control form-control-sm" placeholder="Opsional"></div>
                        <div class="col-md-auto d-flex gap-2">
                            <button type="submit" class="ev-btn ev-btn-sm ev-btn-primary"><i class="bi bi-check-lg"></i>Tambah</button>
                            <button type="button" class="ev-btn ev-btn-sm" data-ev-panel="add-hadiah-item-<?= $pid ?>">Batal</button>
                        </div>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <?php if (empty($myHadiah)): ?><div class="ev-empty-sm">Belum ada item barang / voucher fisik.</div><?php endif; ?>
            <?php foreach ($myHadiah as $hi):
                $hid = $hi['id'];
                $hr  = $hadiahRealisasi[$hid] ?? ['total' => 0, 'entries' => []];
                $hTotalDibagi = (int) ($hr['total'] ?? 0);
                $hStok = (int) $hi['stok']; $hNilai = (int) $hi['nilai_satuan'];
                $hPct  = $hStok > 0 ? min(100, round($hTotalDibagi / $hStok * 100, 1)) : 0;
            ?>
            <div class="ev-sub">
                <div class="ev-sub-h">
                    <div class="min-w-0 flex-grow-1">
                        <div class="ev-sub-name"><?= esc($hi['nama_hadiah']) ?>
                            <?php if (! empty($hi['batch_id'])): ?><span class="ev-chip ev-chip-violet ms-1"><i class="bi bi-upc-scan"></i>Voucher fisik</span>
                            <?php elseif (! empty($hi['barang_id'])): ?><span class="ev-chip ev-chip-blue ms-1"><i class="bi bi-box-seam"></i>Stock barang</span><?php endif; ?></div>
                        <div class="ev-stats">
                            <?php if ($hNilai > 0): ?><span><i class="bi bi-tag"></i>Nilai/pcs <b><?= evRp($hNilai) ?></b></span><?php endif; ?>
                            <?php if ($hStok > 0): ?><span><i class="bi bi-box-seam"></i>Stok <b><?= number_format($hStok) ?></b></span><?php endif; ?>
                            <?php if ($hStok > 0 && $hNilai > 0): ?><span><i class="bi bi-cash"></i>Budget <b><?= evRp($hStok * $hNilai) ?></b></span><?php endif; ?>
                            <span><i class="bi bi-gift"></i>Dibagikan <b><?= number_format($hTotalDibagi) ?></b></span>
                        </div>
                        <?php if ($hStok > 0): ?>
                        <div class="ev-sub-bar">
                            <div class="ev-sub-bar-t"><span><?= number_format($hTotalDibagi) ?> / <?= number_format($hStok) ?></span><b class="<?= $hPct >= 100 ? 'ev-c-ok' : '' ?>"><?= $hPct ?>%</b></div>
                            <div class="ev-bar ev-bar-<?= $pctColor($hPct) ?>"><i style="width:<?= $hPct ?>%"></i></div>
                        </div>
                        <?php endif; ?>
                        <?php if ($hi['catatan']): ?><div class="ev-desc mt-1"><i class="bi bi-sticky me-1"></i><?= esc($hi['catatan']) ?></div><?php endif; ?>
                    </div>
                    <?php if ($canEdit): ?>
                    <div class="ev-sub-act">
                        <button type="button" class="ev-btn ev-btn-xs ev-btn-ok" data-ev-panel="add-hadiah-real-<?= $pid ?>-<?= $hid ?>" data-kode-panel aria-expanded="false"><i class="bi bi-plus-lg"></i>Realisasi</button>
                        <button type="button" class="ev-iconbtn ev-iconbtn-sm" data-ev-panel="edit-hadiah-item-<?= $pid ?>-<?= $hid ?>" title="Edit item" aria-label="Edit item" aria-expanded="false"><i class="bi bi-pencil"></i></button>
                        <form method="POST" action="<?= base_url('events/' . $event['id'] . '/loyalty/' . $pid . '/hadiah/' . $hid . '/delete') ?>" class="ev-form-inline" onsubmit="return confirm('Hapus item ini beserta semua realisasinya?')">
                            <?= csrf_field() ?>
                            <button type="submit" class="ev-iconbtn ev-iconbtn-sm ev-iconbtn-bad" title="Hapus item" aria-label="Hapus item"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if ($canEdit): ?>
                <div class="ev-panel" id="edit-hadiah-item-<?= $pid ?>-<?= $hid ?>" hidden>
                    <form method="POST" action="<?= base_url('events/' . $event['id'] . '/loyalty/' . $pid . '/hadiah/' . $hid . '/update') ?>">
                        <?= csrf_field() ?>
                        <div class="row g-2 align-items-end">
                            <div class="col-md-3"><label class="form-label">Nama Item <span class="text-danger">*</span></label>
                                <input type="text" name="nama_hadiah" class="form-control form-control-sm" value="<?= esc($hi['nama_hadiah']) ?>"
                                    <?= (! empty($hi['barang_id']) || ! empty($hi['batch_id'])) ? 'readonly' : 'required' ?>></div>
                            <div class="col-md-1 col-4"><label class="form-label">Qty</label>
                                <input type="number" name="stok" class="form-control form-control-sm" value="<?= (int) $hi['stok'] ?>" min="0" required></div>
                            <div class="col-md-2 col-8"><label class="form-label">Nilai / pcs (Rp)</label>
                                <input type="text" name="nilai_satuan" class="form-control form-control-sm currency-input"
                                    value="<?= number_format((int) $hi['nilai_satuan'], 0, ',', '.') ?>" <?= ! empty($hi['barang_id']) ? 'readonly' : '' ?>></div>
                            <div class="col-md"><label class="form-label">Catatan</label>
                                <input type="text" name="catatan" class="form-control form-control-sm" value="<?= esc($hi['catatan'] ?? '') ?>"></div>
                            <div class="col-md-auto d-flex gap-2">
                                <button type="submit" class="ev-btn ev-btn-sm ev-btn-primary"><i class="bi bi-check-lg"></i>Simpan</button>
                                <button type="button" class="ev-btn ev-btn-sm" data-ev-panel="edit-hadiah-item-<?= $pid ?>-<?= $hid ?>">Batal</button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="ev-panel" id="add-hadiah-real-<?= $pid ?>-<?= $hid ?>" hidden>
                    <form method="POST" action="<?= base_url('events/' . $event['id'] . '/loyalty/' . $pid . '/hadiah/' . $hid . '/realisasi/add') ?>" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <div class="row g-2 align-items-end">
                            <div class="col-md-2 col-6"><label class="form-label">Tanggal</label>
                                <input type="date" name="tanggal" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required></div>
                            <div class="col-md-3"><label class="form-label">Foto Bukti <span class="text-danger">*</span></label>
                                <input type="file" name="foto" accept="image/*" capture="environment" class="form-control form-control-sm" required></div>
                            <?php if (! empty($hi['batch_id'])): ?>
                            <div class="col-md-3"><label class="form-label">Kode Voucher</label>
                                <select name="kode_id" class="form-select form-select-sm hadiah-kode-select" data-batch-id="<?= $hi['batch_id'] ?>">
                                    <option value="">— loading… —</option>
                                </select></div>
                            <?php else: ?>
                            <div class="col-md-2 col-6"><label class="form-label">Jumlah Dibagikan</label>
                                <input type="number" name="jumlah_dibagikan" class="form-control form-control-sm" placeholder="0" min="0" required></div>
                            <?php endif; ?>
                            <div class="col-md-3"><label class="form-label">Nama Penerima</label>
                                <input type="text" name="nama_penerima" class="form-control form-control-sm" placeholder="Opsional"></div>
                            <div class="col-md"><label class="form-label">Catatan</label>
                                <input type="text" name="catatan" class="form-control form-control-sm" placeholder="Opsional"></div>
                            <div class="col-md-auto d-flex gap-2">
                                <button type="submit" class="ev-btn ev-btn-sm ev-btn-primary"><i class="bi bi-check-lg"></i>Simpan</button>
                                <button type="button" class="ev-btn ev-btn-sm" data-ev-panel="add-hadiah-real-<?= $pid ?>-<?= $hid ?>">Batal</button>
                            </div>
                        </div>
                    </form>
                </div>
                <?php endif; ?>

                <?php if (! empty($hr['entries'])): ?>
                <div class="ev-tbl-wrap">
                <table class="ev-tbl ev-stack">
                    <thead><tr><th>Tanggal</th><th class="ev-num">Dibagikan</th><th>Penerima</th><th>Kode</th><th>Catatan</th><th>Foto bukti</th><?php if ($canEdit): ?><th></th><?php endif; ?></tr></thead>
                    <tbody>
                    <?php foreach ($hr['entries'] as $er): ?>
                    <tr>
                        <td class="ev-nowrap" data-l="Tanggal"><?= tgl_indo_pendek($er['tanggal'], false) ?></td>
                        <td class="ev-num" data-l="Dibagikan"><?= number_format($er['jumlah_dibagikan']) ?></td>
                        <td data-l="Penerima"><?= ! empty($er['nama_penerima']) ? '<b>' . esc($er['nama_penerima']) . '</b>' : '<span class="ev-c-mute">—</span>' ?></td>
                        <td data-l="Kode"><?= ! empty($er['kode_voucher']) ? '<code>' . esc($er['kode_voucher']) . '</code>' : '<span class="ev-c-mute">—</span>' ?></td>
                        <td data-l="Catatan"><?= esc($er['catatan'] ?: '—') ?><span class="ev-who"><i class="bi bi-person"></i>oleh <?= esc($er['pengisi'] ?? '—') ?></span></td>
                        <td data-l="Foto"><?= evBukti($er['foto'] ?? null) ?></td>
                        <?php if ($canEdit): ?>
                        <td class="ev-act-cell">
                            <form method="POST" action="<?= base_url('events/' . $event['id'] . '/loyalty/' . $pid . '/hadiah/' . $hid . '/realisasi/' . $er['id'] . '/delete') ?>" class="ev-form-inline" onsubmit="return confirm('Hapus entri ini?')">
                                <?= csrf_field() ?>
                                <button type="submit" class="ev-iconbtn ev-iconbtn-sm ev-iconbtn-bad" title="Hapus entri" aria-label="Hapus entri hadiah"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot><tr><td>Total</td><td class="ev-num"><?= number_format($hTotalDibagi) ?></td><td colspan="<?= $canEdit ? 5 : 4 ?>"></td></tr></tfoot>
                </table>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (! $canEdit && ! $m['hasMember'] && empty($myVouchers) && empty($myHadiah) && ! $p['mekanisme']): ?>
        <div class="ev-empty-sm">Belum ada data untuk program ini.</div>
        <?php endif; ?>
    </div>
</article>
<?php endforeach; ?>
</div>
</section>

<div class="ev-foot">
    <span class="ev-foot-l"><?= count($programs) ?> program</span>
    <span class="ev-foot-v">Total Budget <span class="ev-c-ok"><?= evRp($totalBudgetProgram) ?></span></span>
    <span class="ev-foot-v ev-c-cyan"><?= evRp($sum['real']) ?><small>realisasi</small></span>
</div>
<?php endif; ?>

</div><!-- /page-evm -->

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
// Muat daftar kode voucher saat panel realisasi dibuka
function loadKodes(sel) {
    if (!sel || sel.options.length > 1) return;
    fetch('<?= base_url('stock/voucher/') ?>' + sel.dataset.batchId + '/available-kodes')
        .then(r => r.json())
        .then(kodes => {
            sel.innerHTML = '<option value="">— Pilih Kode —</option>';
            kodes.forEach(k => {
                const opt = document.createElement('option');
                opt.value = k.id; opt.textContent = k.kode;
                sel.appendChild(opt);
            });
            if (!kodes.length) sel.innerHTML = '<option value="">Tidak ada kode tersedia</option>';
        });
}
document.addEventListener('click', function (e) {
    const b = e.target.closest('[data-kode-panel]');
    if (!b) return;
    setTimeout(() => {
        const el = document.getElementById(b.getAttribute('data-ev-panel'));
        if (el && !el.hidden) loadKodes(el.querySelector('.voucher-kode-select, .hadiah-kode-select'));
    }, 0);
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
// Autofill event hadiah item from barang stock
document.querySelectorAll('.event-barang-select').forEach(sel => {
    sel.addEventListener('change', function() {
        const opt  = this.options[this.selectedIndex];
        const form = this.closest('form');
        const nama  = form.querySelector('.event-hadiah-nama');
        const nilai = form.querySelector('.event-hadiah-nilai');
        const stok  = form.querySelector('.event-hadiah-stok');
        if (opt.value) {
            if (nama)  nama.value  = opt.dataset.nama  || '';
            if (nilai) nilai.value = opt.dataset.nilai ? Number(opt.dataset.nilai).toLocaleString('id-ID') : '';
            if (stok)  stok.value  = opt.dataset.stok  || '';
            const batchSel = form.querySelector('.event-batch-select');
            if (batchSel) batchSel.value = '';
        } else {
            if (nama)  nama.value  = '';
            if (nilai) nilai.value = '';
            if (stok)  stok.value  = '';
        }
    });
});

// Autofill event hadiah item from voucher fisik batch
document.querySelectorAll('.event-batch-select').forEach(sel => {
    sel.addEventListener('change', function() {
        const opt  = this.options[this.selectedIndex];
        const form = this.closest('form');
        const nama  = form.querySelector('.event-hadiah-nama');
        const nilai = form.querySelector('.event-hadiah-nilai');
        const stok  = form.querySelector('.event-hadiah-stok');
        if (opt.value) {
            if (nama)  nama.value  = opt.dataset.nama  || '';
            if (nilai) nilai.value = opt.dataset.nilai ? Number(opt.dataset.nilai).toLocaleString('id-ID') : '';
            if (stok)  stok.value  = opt.dataset.stok  || '';
            const barangSel = form.querySelector('.event-barang-select');
            if (barangSel) barangSel.value = '';
        } else {
            if (nama)  nama.value  = '';
            if (nilai) nilai.value = '';
            if (stok)  stok.value  = '';
        }
    });
});

document.querySelectorAll('.currency-input').forEach(inp => {
    inp.addEventListener('input', function() {
        let n = parseInt(this.value.replace(/[^0-9]/g, '')) || 0;
        this.value = n.toLocaleString('id-ID');
    });
});
</script>
<script src="<?= base_url('js/events-modul.js') ?>?v=<?= @filemtime(FCPATH . 'js/events-modul.js') ?: 1 ?>"></script>
<?= $this->endSection() ?>
