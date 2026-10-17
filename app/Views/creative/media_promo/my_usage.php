<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/media-promo.css') ?>?v=<?= @filemtime(FCPATH . 'css/media-promo.css') ?: 1 ?>">
<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php include_once __DIR__ . '/_helpers.php'; ?>
<?php
$today = date('Y-m-d');
$cnt = ['draft' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0, 'done' => 0];
foreach ($usages as $u) { $cnt[$u['status']] = ($cnt[$u['status']] ?? 0) + 1; }
$total = count($usages);
$perluTindakan = $cnt['draft'] + $cnt['rejected'];
$tipeChip = ['t_banner' => 'blue', 'hanging' => 'blue', 'sticker_lift' => 'warn', 'totem_stainless' => 'mute', 'digital' => 'line'];
$mpTitle = 'Request Saya'; $mpIcon = 'bi-list-check';
$mpSub = 'Request media promo yang kamu buat — edit draft, kirim untuk approval, pantau statusnya';
ob_start(); ?>
    <a href="<?= base_url('creative/media-promo') ?>" class="mp-btn mp-btn-primary"><i class="bi bi-plus-lg"></i>Buat Request</a>
<?php $mpActions = ob_get_clean();
?>
<div class="page-mp" id="mpRoot" data-mp="my" data-base="<?= base_url() ?>">
<?php include __DIR__ . '/_nav.php'; ?>

<div class="mp-kpis">
    <div class="mp-kpi"><div class="mp-kpi-l">Total Request</div><div class="mp-kpi-v"><?= $total ?></div><div class="mp-kpi-s">semua status</div></div>
    <div class="mp-kpi"><div class="mp-kpi-l">Perlu Tindakan</div><div class="mp-kpi-v <?= $perluTindakan ? 'mp-c-warn' : '' ?>"><?= $perluTindakan ?></div><div class="mp-kpi-s"><?= $cnt['draft'] ?> draft · <?= $cnt['rejected'] ?> ditolak</div></div>
    <div class="mp-kpi"><div class="mp-kpi-l">Menunggu Approval</div><div class="mp-kpi-v mp-c-warn"><?= $cnt['pending'] ?></div><div class="mp-kpi-s">sudah dikirim</div></div>
    <div class="mp-kpi"><div class="mp-kpi-l">Disetujui</div><div class="mp-kpi-v mp-c-ok"><?= $cnt['approved'] ?></div><div class="mp-kpi-s">aktif / akan tayang</div></div>
    <div class="mp-kpi"><div class="mp-kpi-l">Selesai</div><div class="mp-kpi-v"><?= $cnt['done'] ?></div><div class="mp-kpi-s">periode sudah lewat</div></div>
</div>

<?php if (empty($usages)): ?>
<div class="mp-empty">
    <i class="bi bi-inbox"></i>
    <h5>Belum ada request</h5>
    <p>Buat request pertama dari halaman Ketersediaan: pilih titik media, isi materi dan periode.</p>
    <a href="<?= base_url('creative/media-promo') ?>" class="mp-btn mp-btn-primary"><i class="bi bi-plus-lg"></i>Buat Request</a>
</div>
<?php else: ?>

<div class="mp-tools" id="mpTools">
    <div class="mp-search"><i class="bi bi-search"></i>
        <input type="search" id="mpQ" placeholder="Cari materi, titik, departemen…" autocomplete="off" aria-label="Cari request">
    </div>
    <div class="mp-chips" id="mpStatusChips" role="group" aria-label="Filter status">
        <button type="button" class="mp-fchip is-on" data-st="">Semua<b><?= $total ?></b></button>
        <?php foreach (['draft' => 'Draft', 'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Ditolak', 'done' => 'Selesai'] as $k => $l): ?>
        <button type="button" class="mp-fchip" data-st="<?= $k ?>"><?= $l ?><b><?= $cnt[$k] ?></b></button>
        <?php endforeach; ?>
    </div>
    <select class="mp-select" id="mpSort" aria-label="Urutkan">
        <option value="">Urutan: terbaru dibuat</option>
        <option value="mulai">Periode mulai terdekat</option>
        <option value="end">Segera berakhir</option>
        <option value="nama">Materi A–Z</option>
    </select>
    <div class="mp-tools-end">
        <span class="mp-count" id="mpCount" aria-live="polite"></span>
        <button type="button" class="mp-btn mp-btn-sm" id="mpReset"><i class="bi bi-x-circle"></i><span class="d-none d-md-inline">Reset</span></button>
    </div>
</div>

<!-- Form massal (kotak centang memakai atribut form=) -->
<form method="POST" action="<?= base_url('creative/media-promo/usage/submit-selected') ?>" id="formBulkSubmit"><?= csrf_field() ?></form>

<div class="mp-list" id="mpList">
<?php foreach ($usages as $n => $u):
    $isSubmittable = in_array($u['status'], ['draft', 'rejected']);
    [$stLbl, $stCls] = mpStatusMeta($u['status']);
    $due = in_array($u['status'], ['pending', 'approved']) ? mpDue($u, $today) : null;
    $srch = mb_strtolower($u['nama_materi'] . ' ' . $u['spot_kode'] . ' ' . $u['spot_nama'] . ' ' . $u['dept'] . ' ' . ($u['requested_by'] ?? ''));
?>
<article class="mp-req s-<?= esc($u['status'], 'attr') ?>" data-status="<?= esc($u['status'], 'attr') ?>" data-search="<?= esc($srch, 'attr') ?>"
         data-nama="<?= esc(mb_strtolower($u['nama_materi']), 'attr') ?>" data-mulai="<?= esc($u['tanggal_mulai'], 'attr') ?>" data-end="<?= esc($u['tanggal_selesai'], 'attr') ?>" data-order="<?= $n ?>">
    <div class="mp-req-head" data-mp-toggle>
        <div data-mp-stop>
            <?php if ($isSubmittable): ?><input type="checkbox" class="mp-chk row-check" name="ids[]" value="<?= $u['id'] ?>" form="formBulkSubmit" aria-label="Pilih request <?= esc($u['nama_materi'], 'attr') ?>">
            <?php else: ?><span class="mp-chk-ph"></span><?php endif; ?>
        </div>
        <div class="min-w-0">
            <div class="mp-req-t"><?= esc($u['nama_materi']) ?></div>
            <div class="mp-req-s"><span class="mp-code"><?= esc($u['spot_kode']) ?></span> <?= esc($u['spot_nama']) ?><?= $u['slot_number'] ? ' · Slot ' . (int) $u['slot_number'] : '' ?>
                <span class="mp-chip mp-chip-<?= $tipeChip[$u['spot_tipe']] ?? 'mute' ?>" style="margin-left:.3rem"><?= esc(mpTipeLabel($u['spot_tipe'])) ?></span></div>
            <?php if ($u['rejection_reason']): ?>
            <div class="mp-req-s mp-note-bad"><i class="bi bi-exclamation-circle me-1"></i>Ditolak: <?= esc($u['rejection_reason']) ?></div>
            <?php endif; ?>
        </div>
        <div class="mp-req-c">
            <span class="mp-req-l">Periode</span>
            <span class="mp-req-v"><?= esc(mpRange($u['tanggal_mulai'], $u['tanggal_selesai'])) ?></span>
            <?php if ($due): ?><div><span class="mp-chip mp-chip-<?= $due[1] ?>" style="margin-top:.2rem"><i class="bi bi-clock"></i><?= esc($due[0]) ?></span></div><?php endif; ?>
        </div>
        <div class="mp-req-c">
            <span class="mp-req-l">Departemen</span>
            <span class="mp-req-v"><?= esc($u['dept']) ?></span>
            <div class="mp-req-s"><?= esc(mpSumberLabel($u['sumber'])) ?> · <?= $u['is_berbayar'] ? 'Berbayar' : 'Gratis' ?></div>
        </div>
        <div class="mp-req-act" data-mp-stop>
            <span class="mp-chip mp-chip-<?= $stCls ?>"><?= $stLbl ?></span>
            <?php if ($isSubmittable): ?>
            <form method="POST" action="<?= base_url('creative/media-promo/usage/' . $u['id'] . '/submit') ?>" class="mp-form-inline">
                <?= csrf_field() ?>
                <button type="submit" class="mp-btn mp-btn-xs mp-btn-primary" title="Kirim untuk approval"><i class="bi bi-send"></i>Kirim</button>
            </form>
            <?php endif; ?>
            <?php if ($isSubmittable || ! in_array($u['status'], ['approved', 'done'])): ?>
            <div class="dropdown">
                <button class="mp-iconbtn" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Aksi request"><i class="bi bi-three-dots-vertical"></i></button>
                <ul class="dropdown-menu dropdown-menu-end mp-menu">
                    <?php if ($isSubmittable): ?>
                    <li><button type="button" class="dropdown-item" data-mp-edit-req="<?= esc(json_encode($u), 'attr') ?>"><i class="bi bi-pencil"></i>Edit request</button></li>
                    <?php endif; ?>
                    <?php if (! in_array($u['status'], ['approved', 'done'])): ?>
                    <li>
                        <form method="POST" action="<?= base_url('creative/media-promo/usage/' . $u['id'] . '/cancel') ?>" data-mp-confirm="Batalkan request ini?">
                            <?= csrf_field() ?>
                            <button type="submit" class="dropdown-item text-danger"><i class="bi bi-trash"></i>Batalkan request</button>
                        </form>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="mp-req-body" hidden>
        <dl class="mp-kv">
            <dt>Titik</dt><dd><?= esc($u['spot_kode']) ?> — <?= esc($u['spot_nama']) ?><?= $u['spot_area'] ? ' · ' . esc($u['spot_area']) : '' ?></dd>
            <dt>Pemohon</dt><dd><?= esc($u['requested_by'] ?: '—') ?></dd>
            <dt>Deskripsi</dt><dd><?= esc($u['deskripsi_materi'] ?: '—') ?></dd>
            <dt>Catatan</dt><dd><?= esc($u['catatan_pemohon'] ?: '—') ?></dd>
            <?php if ($u['catatan_approver']): ?><dt>Catatan approver</dt><dd><?= esc($u['catatan_approver']) ?></dd><?php endif; ?>
            <?php if ($u['rejection_reason']): ?><dt>Alasan ditolak</dt><dd class="mp-note-bad"><?= esc($u['rejection_reason']) ?></dd><?php endif; ?>
            <dt>Dibuat</dt><dd><?= esc($u['created_at'] ?? '—') ?></dd>
            <?php if (! empty($u['submitted_at'])): ?><dt>Disubmit</dt><dd><?= esc($u['submitted_at']) ?></dd><?php endif; ?>
            <?php if (! empty($u['approved_at'])): ?><dt>Diputuskan</dt><dd><?= esc($u['approved_at']) ?></dd><?php endif; ?>
        </dl>
    </div>
</article>
<?php endforeach; ?>
</div>

<div class="mp-empty mp-empty-filter" id="mpNoMatch" hidden>
    <i class="bi bi-search"></i><h5>Tidak ada request yang cocok</h5><p>Ubah kata kunci atau filter status.</p>
    <button type="button" class="mp-btn" id="mpReset2"><i class="bi bi-x-circle"></i>Reset filter</button>
</div>

<!-- Bar aksi massal -->
<div id="bulkBar" class="mp-bulk" hidden>
    <span id="bulkCount" class="small"></span>
    <div class="mp-bulk-end">
        <button type="button" class="mp-btn mp-btn-sm mp-btn-ghost" id="bulkClear">Batal</button>
        <button type="submit" form="formBulkSubmit" class="mp-btn mp-btn-sm mp-btn-solid-ok"><i class="bi bi-send"></i>Submit Terpilih</button>
    </div>
</div>
<?php endif; ?>

<!-- Modal Edit Request -->
<div class="modal fade" id="modalEditRequest" tabindex="-1">
<div class="modal-dialog modal-lg"><div class="modal-content">
<form method="POST" id="formEditRequest" action=""><?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title">Edit Request</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label small fw-semibold">Titik Media</label>
            <input type="text" id="editSpotLabel" class="form-control" readonly>
            <input type="hidden" name="spot_id" id="editSpotId">
        </div>
        <div class="col-md-6" id="editSlotRow" style="display:none">
            <label class="form-label small fw-semibold">Slot</label>
            <input type="number" name="slot_number" id="editSlotNumber" class="form-control" min="1" max="12">
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold">Departemen <span class="text-danger">*</span></label>
            <select name="dept" id="editDept" class="form-select" required>
                <option value="">-- Pilih Departemen --</option>
                <?php foreach ($depts as $d): ?>
                <option value="<?= esc($d) ?>"><?= esc($d) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold">Requested By</label>
            <input type="text" name="requested_by" id="editRequestedBy" class="form-control" placeholder="Nama pemohon">
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold">Tanggal Mulai <span class="text-danger">*</span></label>
            <input type="date" name="tanggal_mulai" id="editTglMulai" class="form-control" required>
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold">Tanggal Selesai <span class="text-danger">*</span></label>
            <input type="date" name="tanggal_selesai" id="editTglSelesai" class="form-control" required>
        </div>
        <div class="col-12">
            <label class="form-label small fw-semibold">Nama Materi <span class="text-danger">*</span></label>
            <input type="text" name="nama_materi" id="editNamaMateri" class="form-control" required>
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold">Sumber Materi <span class="text-danger">*</span></label>
            <select name="sumber" id="editSumber" class="form-select" required>
                <option value="internal">Internal Manajemen</option>
                <option value="tenant">Tenant Mall</option>
                <option value="external">External Client</option>
            </select>
        </div>
        <div class="col-md-6 d-flex align-items-end pb-1">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="is_berbayar" id="editIsBerbayar" role="switch">
                <label class="form-check-label fw-semibold" for="editIsBerbayar">Berbayar</label>
            </div>
        </div>
        <div class="col-12">
            <label class="form-label small fw-semibold">Deskripsi Materi</label>
            <textarea name="deskripsi_materi" id="editDeskripsi" class="form-control" rows="2"></textarea>
        </div>
        <div class="col-12">
            <label class="form-label small fw-semibold">Catatan</label>
            <textarea name="catatan_pemohon" id="editCatatan" class="form-control" rows="2"></textarea>
        </div>
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
