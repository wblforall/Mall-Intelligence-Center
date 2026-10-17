<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/media-promo.css') ?>?v=<?= @filemtime(FCPATH . 'css/media-promo.css') ?: 1 ?>">
<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php include_once __DIR__ . '/_helpers.php'; ?>
<?php
$all     = array_merge($cetak, $digital);
$nAktif  = count(array_filter($all, fn($s) => ! empty($s['is_active'])));
$nOff    = count($all) - $nAktif;
$slotSum = array_sum(array_map(fn($s) => (int) $s['total_slots'], $digital));
$tipeChip = ['t_banner' => 'blue', 'hanging' => 'blue', 'sticker_lift' => 'warn', 'totem_stainless' => 'mute', 'digital' => 'line'];

ob_start(); ?>
    <button class="mp-btn mp-btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahTitik"><i class="bi bi-plus-lg"></i>Tambah Titik</button>
<?php $mpActions = ob_get_clean();
$mpTitle = 'Master Titik Media Promo'; $mpIcon = 'bi-pin-map-fill';
$mpSub = 'Daftar titik media cetak dan digital — tambah, ubah, atau hapus titik';

// Satu baris titik (dipakai tabel cetak & digital)
$rowSpot = function (array $s, bool $digital) use ($tipeChip) {
    $srch = mb_strtolower($s['kode'] . ' ' . $s['nama'] . ' ' . ($s['area'] ?? '') . ' ' . ($s['ukuran'] ?? ''));
    ob_start(); ?>
    <tr data-search="<?= esc($srch, 'attr') ?>" data-tipe="<?= esc($s['tipe'], 'attr') ?>" data-active="<?= (int) $s['is_active'] ?>">
        <td data-l="Kode" data-v="<?= esc(mb_strtolower($s['kode']), 'attr') ?>"><span class="mp-code"><?= esc($s['kode']) ?></span></td>
        <td data-l="Nama" data-v="<?= esc(mb_strtolower($s['nama']), 'attr') ?>" class="wrap fw-semibold"><?= esc($s['nama']) ?></td>
        <?php if (! $digital): ?>
        <td data-l="Tipe" data-v="<?= esc($s['tipe'], 'attr') ?>"><span class="mp-chip mp-chip-<?= $tipeChip[$s['tipe']] ?? 'mute' ?>"><?= esc(mpTipeLabel($s['tipe'])) ?></span></td>
        <?php endif; ?>
        <td data-l="Area / Lokasi" data-v="<?= esc(mb_strtolower($s['area'] ?? ''), 'attr') ?>" class="wrap"><?= esc($s['area'] ?: '—') ?></td>
        <td data-l="<?= $digital ? 'Ukuran / Resolusi' : 'Ukuran' ?>" class="wrap"><?= esc($s['ukuran'] ?: '—') ?></td>
        <?php if ($digital): ?>
        <td data-l="Slot" data-v="<?= (int) $s['total_slots'] ?>" class="c"><span class="mp-chip mp-chip-line"><?= (int) $s['total_slots'] ?> slot</span></td>
        <?php endif; ?>
        <td data-l="Status" data-v="<?= (int) $s['is_active'] ?>"><span class="mp-chip mp-chip-<?= $s['is_active'] ? 'ok' : 'mute' ?>"><?= $s['is_active'] ? 'Aktif' : 'Nonaktif' ?></span></td>
        <td class="r mp-full">
            <div class="d-inline-flex gap-1 align-items-center">
                <button type="button" class="mp-btn mp-btn-xs mp-btn-soft" data-mp-edit="<?= esc(json_encode($s), 'attr') ?>"><i class="bi bi-pencil"></i>Edit</button>
                <form method="POST" action="<?= base_url('creative/media-promo/spots/' . $s['id'] . '/delete') ?>" class="mp-form-inline" data-mp-confirm="Hapus titik ini?">
                    <?= csrf_field() ?>
                    <button class="mp-iconbtn mp-btn-bad" style="width:28px;height:28px" title="Hapus titik" aria-label="Hapus titik <?= esc($s['kode'], 'attr') ?>"><i class="bi bi-trash"></i></button>
                </form>
            </div>
        </td>
    </tr>
    <?php return ob_get_clean();
};
?>

<div class="page-mp" id="mpRoot" data-mp="master" data-base="<?= base_url() ?>">
<?php include __DIR__ . '/_nav.php'; ?>

<div class="mp-kpis k4">
    <div class="mp-kpi"><div class="mp-kpi-l">Total Titik</div><div class="mp-kpi-v"><?= count($all) ?></div><div class="mp-kpi-s"><?= count($cetak) ?> cetak · <?= count($digital) ?> digital</div></div>
    <div class="mp-kpi"><div class="mp-kpi-l">Aktif</div><div class="mp-kpi-v mp-c-ok"><?= $nAktif ?></div><div class="mp-kpi-s">tampil di pemesanan</div></div>
    <div class="mp-kpi"><div class="mp-kpi-l">Nonaktif</div><div class="mp-kpi-v <?= $nOff ? 'mp-c-warn' : '' ?>"><?= $nOff ?></div><div class="mp-kpi-s">tidak dihitung di Summary</div></div>
    <div class="mp-kpi"><div class="mp-kpi-l">Total Slot Digital</div><div class="mp-kpi-v mp-c-cyan"><?= $slotSum ?></div><div class="mp-kpi-s">di <?= count($digital) ?> titik digital</div></div>
</div>

<div class="mp-tools" id="mpTools">
    <div class="mp-search"><i class="bi bi-search"></i>
        <input type="search" id="mpQ" placeholder="Cari kode, nama, area, ukuran…" autocomplete="off" aria-label="Cari titik">
    </div>
    <select class="mp-select" id="mpFTipe" aria-label="Filter tipe">
        <option value="">Semua tipe</option>
        <option value="t_banner">T-Banner</option><option value="hanging">Hanging Banner</option>
        <option value="sticker_lift">Sticker Lift</option><option value="totem_stainless">Totem Stainless</option>
        <option value="digital">Digital</option>
    </select>
    <select class="mp-select" id="mpFStatus" aria-label="Filter status">
        <option value="">Semua status</option><option value="1">Aktif</option><option value="0">Nonaktif</option>
    </select>
    <div class="mp-tools-end">
        <span class="mp-count" id="mpCount" aria-live="polite"></span>
        <button type="button" class="mp-btn mp-btn-sm" id="mpReset"><i class="bi bi-x-circle"></i><span class="d-none d-md-inline">Reset</span></button>
    </div>
</div>

<!-- Cetak -->
<section class="mp-card" data-group="cetak">
    <div class="mp-card-h"><h2><i class="bi bi-file-earmark-image me-1"></i>Media Cetak</h2><small data-gcount><?= count($cetak) ?> titik</small></div>
    <div class="mp-scroll">
    <table class="mp-table is-stack" data-sortable>
        <thead><tr>
            <th data-sort="0">Kode</th><th data-sort="1">Nama</th><th data-sort="2">Tipe</th><th data-sort="3">Area / Lokasi</th><th>Ukuran</th><th data-sort="5">Status</th><th></th>
        </tr></thead>
        <tbody>
        <?php if (empty($cetak)): ?>
        <tr class="mp-static"><td colspan="7" class="c mp-muted" style="padding:1.5rem">Belum ada titik media cetak.</td></tr>
        <?php else: foreach ($cetak as $s) echo $rowSpot($s, false); endif; ?>
        </tbody>
    </table>
    </div>
</section>

<!-- Digital -->
<section class="mp-card" data-group="digital">
    <div class="mp-card-h"><h2><i class="bi bi-display me-1"></i>Media Digital</h2><small data-gcount><?= count($digital) ?> titik</small></div>
    <div class="mp-scroll">
    <table class="mp-table is-stack" data-sortable>
        <thead><tr>
            <th data-sort="0">Kode</th><th data-sort="1">Nama</th><th data-sort="2">Area / Lokasi</th><th>Ukuran / Resolusi</th><th class="c" data-sort="4">Jumlah Slot</th><th data-sort="5">Status</th><th></th>
        </tr></thead>
        <tbody>
        <?php if (empty($digital)): ?>
        <tr class="mp-static"><td colspan="7" class="c mp-muted" style="padding:1.5rem">Belum ada titik media digital.</td></tr>
        <?php else: foreach ($digital as $s) echo $rowSpot($s, true); endif; ?>
        </tbody>
    </table>
    </div>
</section>

<div class="mp-empty mp-empty-filter" id="mpNoMatch" hidden>
    <i class="bi bi-search"></i><h5>Tidak ada titik yang cocok</h5><p>Ubah kata kunci atau filter.</p>
    <button type="button" class="mp-btn" id="mpReset2"><i class="bi bi-x-circle"></i>Reset filter</button>
</div>

<!-- Modal Tambah Titik -->
<div class="modal fade" id="modalTambahTitik" tabindex="-1">
<div class="modal-dialog"><div class="modal-content">
<form method="POST" action="<?= base_url('creative/media-promo/spots/store') ?>"><?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title">Tambah Titik Media</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="row g-2 mb-3">
        <div class="col-4">
            <label class="form-label small fw-semibold">Kode <span class="text-danger">*</span></label>
            <input type="text" name="kode" class="form-control text-uppercase" required maxlength="20" placeholder="TB-001">
        </div>
        <div class="col-8">
            <label class="form-label small fw-semibold">Nama <span class="text-danger">*</span></label>
            <input type="text" name="nama" class="form-control" required>
        </div>
    </div>
    <div class="row g-2 mb-3">
        <div class="col-6">
            <label class="form-label small fw-semibold">Tipe <span class="text-danger">*</span></label>
            <select name="tipe" id="tambahTipe" class="form-select" required data-mp-slot-toggle="tambahSlotRow">
                <option value="">-- Pilih --</option>
                <option value="t_banner">T-Banner</option>
                <option value="hanging">Hanging Banner</option>
                <option value="sticker_lift">Sticker Lift</option>
                <option value="totem_stainless">Totem Stainless</option>
                <option value="digital">Digital</option>
            </select>
        </div>
        <div class="col-6">
            <label class="form-label small fw-semibold">Area / Lokasi</label>
            <input type="text" name="area" class="form-control" placeholder="Lantai 1 - Area A">
        </div>
    </div>
    <div class="row g-2 mb-3">
        <div class="col-6">
            <label class="form-label small fw-semibold">Ukuran / Resolusi</label>
            <input type="text" name="ukuran" class="form-control" placeholder="60x160cm">
        </div>
        <div class="col-6" id="tambahSlotRow" style="display:none">
            <label class="form-label small fw-semibold">Jumlah Slot <span class="text-danger">*</span></label>
            <input type="number" name="total_slots" class="form-control" min="1" max="50" value="12" placeholder="12">
        </div>
    </div>
    <div class="mb-2">
        <label class="form-label small fw-semibold">Catatan</label>
        <textarea name="catatan" class="form-control" rows="2"></textarea>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
    <button type="submit" class="btn btn-primary">Simpan</button>
</div>
</form></div></div></div>

<!-- Modal Edit Titik -->
<div class="modal fade" id="modalEditTitik" tabindex="-1">
<div class="modal-dialog"><div class="modal-content">
<form method="POST" id="formEditTitik" action=""><?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title">Edit Titik Media</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="row g-2 mb-3">
        <div class="col-4">
            <label class="form-label small fw-semibold">Kode <span class="text-danger">*</span></label>
            <input type="text" name="kode" id="edit_kode" class="form-control text-uppercase" required maxlength="20">
        </div>
        <div class="col-8">
            <label class="form-label small fw-semibold">Nama <span class="text-danger">*</span></label>
            <input type="text" name="nama" id="edit_nama" class="form-control" required>
        </div>
    </div>
    <div class="row g-2 mb-3">
        <div class="col-6">
            <label class="form-label small fw-semibold">Tipe</label>
            <input type="text" id="edit_tipe_label" class="form-control" readonly>
        </div>
        <div class="col-6">
            <label class="form-label small fw-semibold">Area / Lokasi</label>
            <input type="text" name="area" id="edit_area" class="form-control">
        </div>
    </div>
    <div class="row g-2 mb-3">
        <div class="col-6">
            <label class="form-label small fw-semibold">Ukuran / Resolusi</label>
            <input type="text" name="ukuran" id="edit_ukuran" class="form-control">
        </div>
        <div class="col-6" id="editSlotRow" style="display:none">
            <label class="form-label small fw-semibold">Jumlah Slot</label>
            <input type="number" name="total_slots" id="edit_total_slots" class="form-control" min="1" max="50">
        </div>
    </div>
    <div class="mb-2">
        <label class="form-label small fw-semibold">Catatan</label>
        <textarea name="catatan" id="edit_catatan" class="form-control" rows="2"></textarea>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
    <button type="submit" class="btn btn-primary">Simpan</button>
</div>
</form></div></div></div>


</div><!-- /.page-mp -->
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="<?= base_url('js/media-promo.js') ?>?v=<?= @filemtime(FCPATH . 'js/media-promo.js') ?: 1 ?>"></script>
<?= $this->endSection() ?>
