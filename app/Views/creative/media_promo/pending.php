<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/media-promo.css') ?>?v=<?= @filemtime(FCPATH . 'css/media-promo.css') ?: 1 ?>">
<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php include_once __DIR__ . '/_helpers.php'; ?>
<?php
$today = date('Y-m-d');
$nBatch = count($groups); $nItem = 0; $oldest = 0; $urgent = 0; $nPaid = 0;
foreach ($groups as $items) {
    $f = $items[0]; $nItem += count($items);
    if (! empty($f['submitted_at'])) $oldest = max($oldest, mpDays($today, date('Y-m-d', strtotime($f['submitted_at']))));
    if (mpDays($f['tanggal_mulai'], $today) <= 3) $urgent++;
    if (! empty($f['is_berbayar'])) $nPaid++;
}
$mpTitle = 'Pending Approval'; $mpIcon = 'bi-hourglass-split';
$mpSub = $nBatch > 0 ? ($nBatch . ' pengajuan · ' . $nItem . ' titik/slot menunggu keputusan') : 'Tidak ada pengajuan yang menunggu keputusan';
$tipeChip = ['t_banner' => 'blue', 'hanging' => 'blue', 'sticker_lift' => 'warn', 'totem_stainless' => 'mute', 'digital' => 'line'];
?>
<div class="page-mp" id="mpRoot" data-mp="pending" data-base="<?= base_url() ?>">
<?php include __DIR__ . '/_nav.php'; ?>

<div class="mp-kpis k4">
    <div class="mp-kpi"><div class="mp-kpi-l">Pengajuan Menunggu</div><div class="mp-kpi-v <?= $nBatch ? 'mp-c-warn' : '' ?>"><?= $nBatch ?></div><div class="mp-kpi-s">kelompok request</div></div>
    <div class="mp-kpi"><div class="mp-kpi-l">Titik / Slot</div><div class="mp-kpi-v"><?= $nItem ?></div><div class="mp-kpi-s"><?= $nPaid ?> pengajuan berbayar</div></div>
    <div class="mp-kpi"><div class="mp-kpi-l">Mendesak</div><div class="mp-kpi-v <?= $urgent ? 'mp-c-bad' : '' ?>"><?= $urgent ?></div><div class="mp-kpi-s">periode mulai ≤ 3 hari / sudah berjalan</div></div>
    <div class="mp-kpi"><div class="mp-kpi-l">Menunggu Terlama</div><div class="mp-kpi-v <?= $oldest >= 3 ? 'mp-c-warn' : '' ?>"><?= $oldest ?><span class="mp-muted" style="font-size:.9rem;font-weight:600"> hari</span></div><div class="mp-kpi-s">sejak disubmit</div></div>
</div>

<?php if (empty($groups)): ?>
<div class="mp-empty">
    <i class="bi bi-check2-circle"></i>
    <h5>Semua sudah diputuskan</h5>
    <p>Tidak ada request yang menunggu approval saat ini.</p>
    <a href="<?= base_url('creative/media-promo/gantt') ?>" class="mp-btn"><i class="bi bi-bar-chart-steps"></i>Lihat Gantt</a>
</div>
<?php else: ?>

<div class="mp-tools" id="mpTools">
    <div class="mp-search"><i class="bi bi-search"></i>
        <input type="search" id="mpQ" placeholder="Cari materi, departemen, pemohon, kode titik…" autocomplete="off" aria-label="Cari pengajuan">
    </div>
    <select class="mp-select" id="mpFSumber" aria-label="Filter sumber">
        <option value="">Semua sumber</option><option value="internal">Internal</option><option value="tenant">Tenant</option><option value="external">External</option>
    </select>
    <select class="mp-select" id="mpFUrgent" aria-label="Filter urgensi">
        <option value="">Semua pengajuan</option><option value="1">Mendesak saja</option>
    </select>
    <select class="mp-select" id="mpSort" aria-label="Urutkan">
        <option value="">Urutan: menunggu terlama</option>
        <option value="mulai">Periode mulai terdekat</option>
        <option value="nama">Materi A–Z</option>
    </select>
    <div class="mp-tools-end">
        <span class="mp-count" id="mpCount" aria-live="polite"></span>
        <button type="button" class="mp-btn mp-btn-sm" id="mpReset"><i class="bi bi-x-circle"></i><span class="d-none d-md-inline">Reset</span></button>
    </div>
</div>

<div id="mpList">
<?php $tipeLabelX = null; $order = 0;
foreach ($groups as $batchKey => $items):
    $first = $items[0]; $order++;
    $waitDays = ! empty($first['submitted_at']) ? mpDays($today, date('Y-m-d', strtotime($first['submitted_at']))) : 0;
    $dMulai   = mpDays($first['tanggal_mulai'], $today);
    $isUrgent = $dMulai <= 3;
    $srch = mb_strtolower($first['nama_materi'] . ' ' . $first['dept'] . ' ' . ($first['requested_by'] ?? '') . ' ' . implode(' ', array_column($items, 'spot_kode')));
?>
<article class="mp-batch" data-search="<?= esc($srch, 'attr') ?>" data-sumber="<?= esc($first['sumber'] ?? 'internal', 'attr') ?>"
         data-urgent="<?= $isUrgent ? 1 : 0 ?>" data-submitted="<?= ! empty($first['submitted_at']) ? strtotime($first['submitted_at']) : 0 ?>"
         data-mulai="<?= esc($first['tanggal_mulai'], 'attr') ?>" data-nama="<?= esc(mb_strtolower($first['nama_materi']), 'attr') ?>" data-order="<?= $order ?>">
    <div class="mp-batch-h">
        <div class="min-w-0" style="flex:1 1 320px">
            <div class="mp-batch-t"><?= esc($first['nama_materi']) ?></div>
            <div class="mp-batch-meta">
                <span><i class="bi bi-building"></i><?= esc($first['dept']) ?><?= $first['requested_by'] ? ' · ' . esc($first['requested_by']) : '' ?></span>
                <span><i class="bi bi-calendar3"></i><?= esc(mpDate($first['tanggal_mulai'])) ?> s/d <?= esc(mpDate($first['tanggal_selesai'])) ?></span>
                <?php if (! empty($first['submitted_at'])): ?><span><i class="bi bi-clock"></i>Disubmit <?= date('d M Y H:i', strtotime($first['submitted_at'])) ?></span><?php endif; ?>
            </div>
            <div class="mp-batch-meta">
                <span class="mp-chip mp-chip-<?= ['internal' => 'mute', 'tenant' => 'blue', 'external' => 'warn'][$first['sumber'] ?? 'internal'] ?? 'mute' ?>"><?= esc(mpSumberLabel($first['sumber'] ?? 'internal')) ?></span>
                <?php if ($first['is_berbayar']): ?><span class="mp-chip mp-chip-ok">Berbayar</span><?php else: ?><span class="mp-chip mp-chip-line">Gratis</span><?php endif; ?>
                <span class="mp-chip mp-chip-<?= $waitDays >= 3 ? 'bad' : ($waitDays >= 1 ? 'warn' : 'mute') ?>"><i class="bi bi-hourglass-split"></i>Menunggu <?= $waitDays ?> hari</span>
                <?php if ($dMulai < 0): ?><span class="mp-chip mp-chip-bad"><i class="bi bi-exclamation-triangle-fill"></i>Periode sudah berjalan</span>
                <?php elseif ($dMulai === 0): ?><span class="mp-chip mp-chip-bad"><i class="bi bi-exclamation-triangle-fill"></i>Mulai hari ini</span>
                <?php else: ?><span class="mp-chip mp-chip-<?= $dMulai <= 3 ? 'warn' : 'mute' ?>">Mulai <?= $dMulai ?> hari lagi</span><?php endif; ?>
            </div>
            <?php if ($first['catatan_pemohon']): ?>
            <div class="mp-batch-note"><i class="bi bi-chat-left-text me-1"></i><?= esc($first['catatan_pemohon']) ?></div>
            <?php endif; ?>
        </div>
        <div class="mp-batch-r">
            <span class="mp-chip mp-chip-warn"><?= count($items) ?> item</span>
            <button type="button" class="mp-btn mp-btn-sm" data-mp-detail="<?= esc(json_encode($first), 'attr') ?>" data-count="<?= count($items) ?>"><i class="bi bi-eye"></i>Detail</button>
        </div>
    </div>

    <form method="POST" action="<?= base_url('creative/media-promo/usage/batch-approve') ?>" class="batch-form" id="batch_<?= esc($batchKey) ?>"><?= csrf_field() ?>
    <div class="mp-scroll">
        <table class="mp-items">
        <thead><tr>
            <th class="w-chk"><input type="checkbox" class="mp-chk batch-check-all" data-batch="<?= esc($batchKey) ?>" checked title="Pilih semua" aria-label="Pilih semua item"></th>
            <th>Titik</th><th>Materi</th><th>Slot</th><th class="hide-sm">Area</th><th class="hide-sm">Tipe</th>
        </tr></thead>
        <tbody>
        <?php foreach ($items as $u): ?>
        <tr>
            <td><input type="checkbox" class="mp-chk item-check" name="approve_ids[]" value="<?= $u['id'] ?>" data-batch="<?= esc($batchKey) ?>" checked aria-label="Pilih <?= esc($u['spot_kode'], 'attr') ?>"></td>
            <td><span class="mp-code"><?= esc($u['spot_kode']) ?></span><div class="mp-muted" style="font-size:.74rem;margin-top:.15rem"><?= esc($u['spot_nama']) ?></div></td>
            <td><b><?= esc($u['nama_materi']) ?></b>
                <?php if ($u['deskripsi_materi']): ?><div class="mp-muted hide-sm" style="font-size:.74rem"><?= esc($u['deskripsi_materi']) ?></div><?php endif; ?></td>
            <td><?= $u['slot_number'] ? 'Slot ' . $u['slot_number'] : '—' ?></td>
            <td class="mp-muted hide-sm"><?= esc($u['spot_area'] ?? '—') ?></td>
            <td class="hide-sm"><span class="mp-chip mp-chip-<?= $tipeChip[$u['spot_tipe']] ?? 'mute' ?>"><?= esc(mpTipeLabel($u['spot_tipe'])) ?></span></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        </table>
    </div>
    <div class="mp-batch-f">
        <input type="text" name="catatan_approver" class="mp-input" placeholder="Catatan approver (opsional)" aria-label="Catatan approver">
        <span class="mp-selcount" data-selcount></span>
        <div class="mp-sp">
            <button type="button" class="mp-btn mp-btn-bad" data-mp-reject="<?= esc(json_encode(array_column($items, 'id')), 'attr') ?>"><i class="bi bi-x-lg"></i>Tolak Semua</button>
            <button type="submit" class="mp-btn mp-btn-solid-ok"><i class="bi bi-check-lg"></i>Setujui Terpilih</button>
        </div>
    </div>
    </form>
</article>
<?php endforeach; ?>
</div>

<div class="mp-empty mp-empty-filter" id="mpNoMatch" hidden>
    <i class="bi bi-search"></i><h5>Tidak ada pengajuan yang cocok</h5><p>Ubah kata kunci atau filter.</p>
    <button type="button" class="mp-btn" id="mpReset2"><i class="bi bi-x-circle"></i>Reset filter</button>
</div>
<?php endif; ?>

<!-- Modal Detail Request -->
<div class="modal fade" id="modalDetail" tabindex="-1">
<div class="modal-dialog"><div class="modal-content">
<div class="modal-header"><h5 class="modal-title"><i class="bi bi-info-circle me-2"></i>Detail Request</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <dl class="row mb-0 small">
        <dt class="col-4 text-muted fw-normal">Nama Materi</dt>
        <dd class="col-8 fw-semibold mb-2" id="detailNamaMateri"></dd>
        <dt class="col-4 text-muted fw-normal">Deskripsi</dt>
        <dd class="col-8 mb-2" id="detailDeskripsi"></dd>
        <dt class="col-4 text-muted fw-normal">Dept / Pemohon</dt>
        <dd class="col-8 mb-2" id="detailDept"></dd>
        <dt class="col-4 text-muted fw-normal">Periode</dt>
        <dd class="col-8 mb-2" id="detailPeriode"></dd>
        <dt class="col-4 text-muted fw-normal">Sumber</dt>
        <dd class="col-8 mb-2" id="detailSumber"></dd>
        <dt class="col-4 text-muted fw-normal">Berbayar</dt>
        <dd class="col-8 mb-2" id="detailBerbayar"></dd>
        <dt class="col-4 text-muted fw-normal">Jumlah Item</dt>
        <dd class="col-8 mb-2" id="detailJumlahItem"></dd>
        <dt class="col-4 text-muted fw-normal">Catatan</dt>
        <dd class="col-8 mb-2" id="detailCatatan"></dd>
        <dt class="col-4 text-muted fw-normal">Disubmit oleh</dt>
        <dd class="col-8 mb-2" id="detailSubmittedBy"></dd>
        <dt class="col-4 text-muted fw-normal">Waktu Submit</dt>
        <dd class="col-8 mb-0" id="detailSubmittedAt"></dd>
    </dl>
</div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button></div>
</div></div></div>

<!-- Modal Reject Batch -->
<div class="modal fade" id="modalRejectBatch" tabindex="-1">
<div class="modal-dialog modal-sm"><div class="modal-content">
<form method="POST" id="formRejectBatch" action=""><?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title text-danger">Tolak Request</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div id="rejectBatchIds"></div>
    <label class="form-label small fw-semibold">Alasan Penolakan <span class="text-danger">*</span></label>
    <textarea name="rejection_reason" class="form-control" rows="3" required placeholder="Wajib diisi"></textarea>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
    <button type="submit" class="btn btn-danger"><i class="bi bi-x-lg me-1"></i>Tolak</button>
</div>
</form></div></div></div>

<!-- Modal Approve Individual (from gantt) -->
<div class="modal fade" id="modalApprove" tabindex="-1">
<div class="modal-dialog modal-sm"><div class="modal-content">
<form method="POST" id="formApprove" action=""><?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title text-success">Approve Request</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <p class="small mb-2">Request: <strong id="approveNama"></strong></p>
    <label class="form-label small fw-semibold">Catatan (opsional)</label>
    <textarea name="catatan_approver" class="form-control" rows="3"></textarea>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
    <button type="submit" class="btn btn-success"><i class="bi bi-check-lg me-1"></i>Approve</button>
</div>
</form></div></div></div>


</div><!-- /.page-mp -->
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="<?= base_url('js/media-promo.js') ?>?v=<?= @filemtime(FCPATH . 'js/media-promo.js') ?: 1 ?>"></script>
<?= $this->endSection() ?>
