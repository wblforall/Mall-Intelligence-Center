<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/media-promo.css') ?>?v=<?= @filemtime(FCPATH . 'css/media-promo.css') ?: 1 ?>">
<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php include_once __DIR__ . '/_helpers.php'; ?>
<?php
// ── Ringkasan angka (dihitung dari data yang sama dengan kartu) ──
$cTotal = count($cetak); $cBusy = $cPend = $cFree = $cEnding = 0;
foreach ($cetak as $s) {
    $au = $s['active_usage'] ?? null;
    if (! $au) { $cFree++; continue; }
    if ($au['status'] === 'pending') $cPend++; else $cBusy++;
    if (mpDays($au['tanggal_selesai'], $today) <= 7) $cEnding++;
}
$dTotal = count($digital); $slotAll = $slotUsed = 0;
foreach ($digital as $s) { $slotAll += (int) $s['total_slots']; $slotUsed += count($s['used_slots'] ?? []); }
$slotFree = $slotAll - $slotUsed;
$slotPct  = $slotAll > 0 ? round($slotUsed / $slotAll * 100) : 0;
$tipeBadgeMap = ['t_banner' => 'blue', 'hanging' => 'blue', 'sticker_lift' => 'warn', 'totem_stainless' => 'mute', 'digital' => 'line'];

ob_start(); ?>
    <a href="<?= base_url('creative/media-promo/print?bulan=' . date('Y-m')) ?>" target="_blank" class="mp-btn"><i class="bi bi-printer"></i><span class="d-none d-sm-inline">Print</span></a>
    <button class="mp-btn mp-btn-primary" data-bs-toggle="modal" data-bs-target="#modalBuatRequest"><i class="bi bi-plus-lg"></i>Buat Request</button>
<?php $mpActions = ob_get_clean();
$mpTitle = 'Media Promo'; $mpIcon = 'bi-megaphone-fill';
$mpSub = 'Ketersediaan titik media cetak & digital per ' . date('d M Y') . ' — booking, pantau tenggat, kelola titik';
?>

<div class="page-mp" id="mpRoot" data-mp="index" data-base="<?= base_url() ?>">
<?php include __DIR__ . '/_nav.php'; ?>

<!-- Strip KPI -->
<div class="mp-kpis">
    <div class="mp-kpi">
        <div class="mp-kpi-l">Titik Media</div>
        <div class="mp-kpi-v"><?= $cTotal + $dTotal ?></div>
        <div class="mp-kpi-s"><?= $cTotal ?> cetak · <?= $dTotal ?> digital</div>
    </div>
    <div class="mp-kpi">
        <div class="mp-kpi-l">Cetak Tersedia</div>
        <div class="mp-kpi-v mp-c-ok"><?= $cFree ?></div>
        <div class="mp-kpi-s">dari <?= $cTotal ?> titik cetak</div>
    </div>
    <div class="mp-kpi">
        <div class="mp-kpi-l">Cetak Terpakai</div>
        <div class="mp-kpi-v mp-c-bad"><?= $cBusy ?></div>
        <div class="mp-kpi-s"><?= $cEnding > 0 ? '<span class="mp-c-warn">' . $cEnding . ' berakhir ≤ 7 hari</span>' : 'tidak ada yang segera berakhir' ?></div>
    </div>
    <div class="mp-kpi">
        <div class="mp-kpi-l">Cetak Pending</div>
        <div class="mp-kpi-v mp-c-warn"><?= $cPend ?></div>
        <div class="mp-kpi-s">menunggu approval</div>
    </div>
    <div class="mp-kpi">
        <div class="mp-kpi-l">Slot Digital Bebas</div>
        <div class="mp-kpi-v mp-c-cyan"><?= $slotFree ?><span class="mp-muted" style="font-size:.9rem;font-weight:600"> / <?= $slotAll ?></span></div>
        <div class="mp-kpi-s"><?= $slotPct ?>% terisi hari ini</div>
        <div class="mp-bar <?= $slotPct >= 90 ? 'mp-bar-hi' : ($slotPct >= 70 ? 'mp-bar-mid' : '') ?>"><i style="width:<?= $slotPct ?>%"></i></div>
    </div>
</div>

<!-- Pencarian & filter -->
<div class="mp-tools" id="mpTools">
    <div class="mp-search">
        <i class="bi bi-search"></i>
        <input type="search" id="mpQ" placeholder="Cari kode, nama, area, atau materi…" autocomplete="off" aria-label="Cari titik media">
    </div>
    <select class="mp-select" id="mpFKind" aria-label="Filter jenis media">
        <option value="">Cetak & digital</option>
        <option value="cetak">Media cetak</option>
        <option value="digital">Media digital</option>
    </select>
    <select class="mp-select" id="mpFTipe" aria-label="Filter tipe">
        <option value="">Semua tipe</option>
        <option value="t_banner">T-Banner</option>
        <option value="hanging">Hanging Banner</option>
        <option value="sticker_lift">Sticker Lift</option>
        <option value="totem_stainless">Totem Stainless</option>
    </select>
    <select class="mp-select" id="mpFStatus" aria-label="Filter status">
        <option value="">Semua status</option>
        <option value="avail">Bisa dibooking</option>
        <option value="busy">Terpakai</option>
        <option value="pending">Pending</option>
        <option value="full">Digital penuh</option>
    </select>
    <select class="mp-select" id="mpSort" aria-label="Urutkan">
        <option value="">Urutan: kode</option>
        <option value="nama">Nama A–Z</option>
        <option value="end">Segera berakhir</option>
    </select>
    <div class="mp-tools-end">
        <span class="mp-count" id="mpCount" aria-live="polite"></span>
        <button type="button" class="mp-btn mp-btn-sm" id="mpReset"><i class="bi bi-x-circle"></i><span class="d-none d-md-inline">Reset</span></button>
    </div>
</div>

<div class="mp-legend">
    <span><i class="mp-dot" style="background:var(--mp-ok)"></i>Tersedia</span>
    <span><i class="mp-dot" style="background:var(--mp-warn)"></i>Pending / sebagian terisi</span>
    <span><i class="mp-dot" style="background:var(--mp-bad)"></i>Terpakai / penuh</span>
    <span class="ms-1">Tenggat: <span class="mp-chip mp-chip-warn">≤ 7 hari</span></span>
</div>

<!-- Media Cetak -->
<section class="mp-group" data-group="cetak">
    <div class="mp-group-h"><h2 class="mp-group-t"><i class="bi bi-file-earmark-image me-1"></i>Media Cetak</h2><span class="mp-group-n" id="mpNCetak"><?= $cTotal ?> titik</span></div>
    <?php if (empty($cetak)): ?>
    <div class="mp-empty mp-empty-filter"><i class="bi bi-file-earmark-image"></i><p class="mb-0">Belum ada titik media cetak.</p></div>
    <?php else: ?>
    <div class="mp-grid" data-list="cetak">
    <?php foreach ($cetak as $idx => $spot):
        $au   = $spot['active_usage'] ?? null;
        $st   = ! $au ? 'free' : ($au['status'] === 'pending' ? 'pending' : 'busy');
        $due  = $au ? mpDue($au, $today) : null;
        $srch = mb_strtolower($spot['kode'] . ' ' . $spot['nama'] . ' ' . ($spot['area'] ?? '') . ' ' . ($au['nama_materi'] ?? '') . ' ' . ($au['dept'] ?? ''));
    ?>
    <article class="mp-spot st-<?= $st ?><?= empty($spot['is_active']) ? ' is-off' : '' ?>"
             data-kind="cetak" data-tipe="<?= esc($spot['tipe'], 'attr') ?>" data-status="<?= $st ?>" data-avail="<?= $st === 'free' ? 1 : 0 ?>"
             data-search="<?= esc($srch, 'attr') ?>" data-nama="<?= esc(mb_strtolower($spot['nama']), 'attr') ?>"
             data-end="<?= $au ? esc($au['tanggal_selesai'], 'attr') : '' ?>" data-order="<?= $idx ?>">
        <div class="mp-spot-top">
            <span class="mp-code"><?= esc($spot['kode']) ?></span>
            <span class="mp-chip mp-chip-<?= $tipeBadgeMap[$spot['tipe']] ?? 'mute' ?>"><?= esc(mpTipeLabel($spot['tipe'])) ?></span>
            <?php if (! empty($canEdit)): ?>
            <div class="dropdown mp-menu-wrap">
                <button class="mp-iconbtn" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Aksi titik <?= esc($spot['kode'], 'attr') ?>"><i class="bi bi-three-dots-vertical"></i></button>
                <ul class="dropdown-menu dropdown-menu-end mp-menu">
                    <li><button type="button" class="dropdown-item" data-mp-edit="<?= esc(json_encode($spot), 'attr') ?>"><i class="bi bi-pencil"></i>Edit titik</button></li>
                    <li>
                        <form method="POST" action="<?= base_url('creative/media-promo/spots/' . $spot['id'] . '/delete') ?>" data-mp-confirm="Hapus titik ini?">
                            <?= csrf_field() ?>
                            <button class="dropdown-item text-danger"><i class="bi bi-trash"></i>Hapus titik</button>
                        </form>
                    </li>
                </ul>
            </div>
            <?php endif; ?>
        </div>
        <div>
            <div class="mp-spot-n"><?= esc($spot['nama']) ?></div>
            <div class="mp-spot-d"><?= esc($spot['area']) ?><?= ($spot['area'] && $spot['ukuran']) ? ' · ' : '' ?><?= esc($spot['ukuran']) ?></div>
        </div>
        <div class="mp-spot-line">
            <?php if ($st === 'free'): ?><span class="mp-chip mp-chip-ok"><i class="bi bi-check-circle-fill"></i>Tersedia</span>
            <?php elseif ($st === 'pending'): ?><span class="mp-chip mp-chip-warn"><i class="bi bi-hourglass-split"></i>Pending</span>
            <?php else: ?><span class="mp-chip mp-chip-bad"><i class="bi bi-x-circle-fill"></i>Terpakai</span><?php endif; ?>
            <?php if (empty($spot['is_active'])): ?><span class="mp-chip mp-chip-mute">Nonaktif</span><?php endif; ?>
            <?php if ($due): ?><span class="mp-chip mp-chip-<?= $due[1] ?>"><i class="bi bi-clock"></i><?= esc($due[0]) ?></span><?php endif; ?>
        </div>
        <?php if ($au): ?>
        <div class="mp-spot-use">
            <b><?= esc($au['nama_materi']) ?></b>
            <span><?= esc($au['dept']) ?> · <?= esc(mpRange($au['tanggal_mulai'], $au['tanggal_selesai'])) ?></span>
        </div>
        <?php endif; ?>
        <?php if ($st === 'free'): ?>
        <div class="mp-spot-foot">
            <button type="button" class="mp-btn mp-btn-xs mp-btn-ok" data-mp-book="cetak" data-spot="<?= $spot['id'] ?>"><i class="bi bi-plus-lg"></i>Booking titik ini</button>
        </div>
        <?php endif; ?>
    </article>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>

<!-- Media Digital -->
<section class="mp-group" data-group="digital">
    <div class="mp-group-h"><h2 class="mp-group-t"><i class="bi bi-display me-1"></i>Media Digital</h2><span class="mp-group-n" id="mpNDigital"><?= $dTotal ?> titik</span></div>
    <?php if (empty($digital)): ?>
    <div class="mp-empty mp-empty-filter"><i class="bi bi-display"></i><p class="mb-0">Belum ada titik media digital.</p></div>
    <?php else: ?>
    <div class="mp-grid is-dig" data-list="digital">
    <?php foreach ($digital as $idx => $spot):
        $usedCount  = count($spot['used_slots'] ?? []);
        $totalSlots = (int) $spot['total_slots'];
        $pct        = $totalSlots > 0 ? round($usedCount / $totalSlots * 100) : 0;
        $st         = $usedCount === 0 ? 'free' : ($usedCount >= $totalSlots ? 'full' : 'part');
        $barClass   = $pct >= 100 ? 'mp-bar-hi' : ($pct >= 75 ? 'mp-bar-mid' : '');
        $srch       = mb_strtolower($spot['kode'] . ' ' . $spot['nama'] . ' ' . ($spot['area'] ?? ''));
    ?>
    <article class="mp-spot st-<?= $st ?><?= empty($spot['is_active']) ? ' is-off' : '' ?>"
             data-kind="digital" data-tipe="digital" data-status="<?= $st ?>" data-avail="<?= $spot['sisa_slots'] > 0 ? 1 : 0 ?>"
             data-search="<?= esc($srch, 'attr') ?>" data-nama="<?= esc(mb_strtolower($spot['nama']), 'attr') ?>" data-end="" data-order="<?= $idx ?>">
        <div class="mp-spot-top">
            <span class="mp-code"><?= esc($spot['kode']) ?></span>
            <span class="mp-chip mp-chip-line">Digital</span>
            <?php if (empty($spot['is_active'])): ?><span class="mp-chip mp-chip-mute">Nonaktif</span><?php endif; ?>
            <?php if (! empty($canEdit)): ?>
            <div class="dropdown mp-menu-wrap">
                <button class="mp-iconbtn" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Aksi titik <?= esc($spot['kode'], 'attr') ?>"><i class="bi bi-three-dots-vertical"></i></button>
                <ul class="dropdown-menu dropdown-menu-end mp-menu">
                    <li><button type="button" class="dropdown-item" data-mp-edit="<?= esc(json_encode($spot), 'attr') ?>"><i class="bi bi-pencil"></i>Edit titik</button></li>
                    <li>
                        <form method="POST" action="<?= base_url('creative/media-promo/spots/' . $spot['id'] . '/delete') ?>" data-mp-confirm="Hapus titik ini?">
                            <?= csrf_field() ?>
                            <button class="dropdown-item text-danger"><i class="bi bi-trash"></i>Hapus titik</button>
                        </form>
                    </li>
                </ul>
            </div>
            <?php endif; ?>
        </div>
        <div>
            <div class="mp-spot-n"><?= esc($spot['nama']) ?></div>
            <?php if ($spot['area']): ?><div class="mp-spot-d"><?= esc($spot['area']) ?></div><?php endif; ?>
        </div>
        <div class="mp-slot-sum">
            <span><?= $usedCount ?>/<?= $totalSlots ?> slot terisi</span>
            <b class="<?= $spot['sisa_slots'] > 0 ? 'mp-c-ok' : 'mp-c-bad' ?>"><?= $spot['sisa_slots'] ?> bebas</b>
        </div>
        <div class="mp-bar <?= $barClass ?>" style="margin-top:0"><i style="width:<?= $pct ?>%"></i></div>
        <div class="mp-slots">
        <?php for ($s = 1; $s <= $totalSlots; $s++): ?>
            <span class="mp-slot <?= in_array($s, $spot['used_slots']) ? 'u' : 'f' ?>" title="Slot <?= $s ?>: <?= in_array($s, $spot['used_slots']) ? 'terisi' : 'bebas' ?>"><?= $s ?></span>
        <?php endfor; ?>
        </div>
        <?php if ($spot['sisa_slots'] > 0): ?>
        <div class="mp-spot-foot">
            <button type="button" class="mp-btn mp-btn-xs mp-btn-ok" data-mp-book="digital" data-spot="<?= $spot['id'] ?>"><i class="bi bi-plus-lg"></i>Pilih slot</button>
        </div>
        <?php endif; ?>
    </article>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>

<div class="mp-empty mp-empty-filter" id="mpNoMatch" hidden>
    <i class="bi bi-search"></i>
    <h5>Tidak ada titik yang cocok</h5>
    <p>Ubah kata kunci atau filter.</p>
    <button type="button" class="mp-btn" id="mpReset2"><i class="bi bi-x-circle"></i>Reset filter</button>
</div>

<!-- Modal Edit Titik -->
<div class="modal fade" id="modalEditTitik" tabindex="-1">
<div class="modal-dialog"><div class="modal-content">
<form method="POST" id="formEditTitik" action=""><?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title">Edit Titik Media</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="row g-2 mb-3">
        <div class="col-4">
            <label class="form-label small fw-semibold">Kode <span class="text-danger">*</span></label>
            <input type="text" name="kode" id="edit_spot_kode" class="form-control text-uppercase" required maxlength="20">
        </div>
        <div class="col-8">
            <label class="form-label small fw-semibold">Nama <span class="text-danger">*</span></label>
            <input type="text" name="nama" id="edit_spot_nama" class="form-control" required>
        </div>
    </div>
    <div class="row g-2 mb-3">
        <div class="col-6">
            <label class="form-label small fw-semibold">Tipe</label>
            <input type="text" id="edit_spot_tipe_label" class="form-control" readonly>
        </div>
        <div class="col-6">
            <label class="form-label small fw-semibold">Area/Lokasi</label>
            <input type="text" name="area" id="edit_spot_area" class="form-control">
        </div>
    </div>
    <div class="row g-2 mb-3">
        <div class="col-6">
            <label class="form-label small fw-semibold">Ukuran / Resolusi</label>
            <input type="text" name="ukuran" id="edit_spot_ukuran" class="form-control">
        </div>
        <div class="col-6" id="editSpotSlotRow" style="display:none">
            <label class="form-label small fw-semibold">Jumlah Slot</label>
            <input type="number" name="total_slots" id="edit_spot_slots" class="form-control" min="1" max="50">
        </div>
    </div>
    <div class="mb-2">
        <label class="form-label small fw-semibold">Catatan</label>
        <textarea name="catatan" id="edit_spot_catatan" class="form-control" rows="2"></textarea>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
    <button type="submit" class="btn btn-primary">Simpan</button>
</div>
</form></div></div></div>

<!-- Modal Buat Request -->
<div class="modal fade" id="modalBuatRequest" tabindex="-1">
<div class="modal-dialog modal-lg"><div class="modal-content">
<form method="POST" action="<?= base_url('creative/media-promo/usage/store') ?>" id="formBuatRequest"><?= csrf_field() ?>
<input type="hidden" name="req_mode" id="reqMode" value="cetak">
<div class="modal-header"><h5 class="modal-title">Buat Request Media Promo</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <!-- Mode toggle -->
    <div class="d-flex gap-4 mb-3 pb-3 border-bottom">
        <div class="form-check">
            <input class="form-check-input" type="radio" name="req_mode_ui" id="modeCetak" value="cetak" checked>
            <label class="form-check-label fw-semibold" for="modeCetak"><i class="bi bi-file-earmark-image me-1"></i>Media Cetak</label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="radio" name="req_mode_ui" id="modeDigital" value="digital">
            <label class="form-check-label fw-semibold" for="modeDigital"><i class="bi bi-display me-1"></i>Media Digital</label>
        </div>
    </div>
    <!-- Shared fields -->
    <div class="row g-3 mb-3">
        <div class="col-md-8">
            <label class="form-label small fw-semibold">Nama Materi <span class="text-danger">*</span></label>
            <input type="text" name="nama_materi" class="form-control" required placeholder="Contoh: Banner Promo Lebaran 2026">
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-semibold">Requested By</label>
            <input type="text" name="requested_by" class="form-control" placeholder="Nama pemohon">
        </div>
    </div>
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <label class="form-label small fw-semibold">Departemen <span class="text-danger">*</span></label>
            <select name="dept" class="form-select" required>
                <option value="">-- Pilih --</option>
                <?php foreach ($depts as $d): ?>
                <option value="<?= esc($d) ?>"><?= esc($d) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-semibold">Tanggal Mulai <span class="text-danger">*</span></label>
            <input type="date" name="tanggal_mulai" id="reqTglMulai" class="form-control" required>
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-semibold">Tanggal Selesai <span class="text-danger">*</span></label>
            <input type="date" name="tanggal_selesai" id="reqTglSelesai" class="form-control" required>
        </div>
    </div>
    <!-- Cetak: checkbox list -->
    <div id="cetakSection">
        <label class="form-label small fw-semibold">Pilih Titik Media Cetak <span class="text-danger">*</span></label>
        <?php if (empty($cetak)): ?>
        <div class="border rounded p-3 text-center text-muted small">Belum ada titik media cetak.</div>
        <?php else: ?>
        <div class="border rounded p-2" style="max-height:200px;overflow-y:auto">
        <?php foreach ($cetak as $s):
            $occ = !empty($s['active_usage']);
            $isPend = $occ && $s['active_usage']['status'] === 'pending';
        ?>
            <div class="form-check py-1 border-bottom">
                <input class="form-check-input cetak-check" type="checkbox" name="spot_ids[]"
                       value="<?= $s['id'] ?>" id="spotCk_<?= $s['id'] ?>">
                <label class="form-check-label d-flex align-items-center gap-2 small w-100" for="spotCk_<?= $s['id'] ?>">
                    <span class="badge bg-secondary font-monospace"><?= esc($s['kode']) ?></span>
                    <span><?= esc($s['nama']) ?></span>
                    <?php if ($s['area']): ?><span class="text-muted">· <?= esc($s['area']) ?></span><?php endif; ?>
                    <span class="badge bg-<?= $occ ? ($isPend ? 'warning text-dark' : 'danger') : 'success' ?> ms-auto avail-badge">
                        <?= $occ ? ($isPend ? 'Pending' : 'Terpakai') : 'Tersedia' ?>
                    </span>
                </label>
            </div>
        <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <div id="cetakNoCheck" class="text-danger small mt-1" style="display:none">Pilih minimal 1 titik.</div>
    </div>
    <!-- Digital: checkbox list per spot dengan slot inline -->
    <div id="digitalSection" style="display:none">
        <label class="form-label small fw-semibold">Pilih Slot Digital <span class="text-danger">*</span></label>
        <?php if (empty($digital)): ?>
        <div class="border rounded p-3 text-center text-muted small">Belum ada titik media digital.</div>
        <?php else: ?>
        <div class="border rounded p-2" style="max-height:240px;overflow-y:auto" id="digitalSpotList">
        <?php foreach ($digital as $s): ?>
            <div class="py-2 border-bottom digital-spot-row" data-spot-id="<?= $s['id'] ?>">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-secondary font-monospace" style="font-size:.65rem"><?= esc($s['kode']) ?></span>
                    <span class="fw-semibold small"><?= esc($s['nama']) ?></span>
                    <?php if ($s['area']): ?>
                    <span class="text-muted" style="font-size:.7rem">· <?= esc($s['area']) ?></span>
                    <?php endif; ?>
                    <span class="ms-auto text-muted small spot-sisa-label">
                        <?= $s['sisa_slots'] ?>/<?= $s['total_slots'] ?> bebas
                    </span>
                </div>
                <div class="d-flex flex-wrap gap-1 slot-btn-group">
                <?php for ($sl = 1; $sl <= (int)$s['total_slots']; $sl++): ?>
                    <div>
                        <input type="checkbox" class="btn-check digital-slot-check"
                               name="slot_selections[<?= $s['id'] ?>][]"
                               id="ds_<?= $s['id'] ?>_<?= $sl ?>" value="<?= $sl ?>"
                               autocomplete="off">
                        <label class="btn btn-sm btn-outline-secondary slot-btn"
                               for="ds_<?= $s['id'] ?>_<?= $sl ?>"
                               style="min-width:36px;font-size:.72rem"><?= $sl ?></label>
                    </div>
                <?php endfor; ?>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <div id="digitalNoSlot" class="text-danger small mt-1" style="display:none">Pilih minimal 1 slot.</div>
    </div>
    <!-- Shared bottom -->
    <div class="row g-3 mt-1">
        <div class="col-md-5">
            <label class="form-label small fw-semibold">Sumber Materi <span class="text-danger">*</span></label>
            <select name="sumber" class="form-select" required>
                <option value="internal">Internal Manajemen</option>
                <option value="tenant">Tenant Mall</option>
                <option value="external">External Client</option>
            </select>
        </div>
        <div class="col-md-4 d-flex align-items-end pb-1">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="is_berbayar" id="reqIsBerbayar" role="switch">
                <label class="form-check-label fw-semibold" for="reqIsBerbayar">Berbayar</label>
            </div>
        </div>
        <div class="col-12">
            <label class="form-label small fw-semibold">Deskripsi Materi</label>
            <textarea name="deskripsi_materi" class="form-control" rows="2" placeholder="Opsional"></textarea>
        </div>
        <div class="col-12">
            <label class="form-label small fw-semibold">Catatan</label>
            <textarea name="catatan_pemohon" class="form-control" rows="2" placeholder="Opsional"></textarea>
        </div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
    <button type="submit" class="btn btn-primary">Simpan sebagai Draft</button>
</div>
</form></div></div></div>


</div><!-- /.page-mp -->
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="<?= base_url('js/media-promo.js') ?>?v=<?= @filemtime(FCPATH . 'js/media-promo.js') ?: 1 ?>"></script>
<?= $this->endSection() ?>
