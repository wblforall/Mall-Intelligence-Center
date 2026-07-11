<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center justify-content-between mb-3">
    <h4 class="fw-bold mb-0"><i class="bi bi-hourglass-split me-2"></i>Pending Approval — Media Promo</h4>
    <a href="<?= base_url('creative/media-promo') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Kembali
    </a>
</div>

<?php if (empty($groups)): ?>
<div class="card"><div class="card-body text-center text-muted py-5">Tidak ada request yang menunggu approval.</div></div>
<?php else: ?>

<?php $tipeLabel = ['t_banner' => 'T-Banner', 'hanging' => 'Hanging', 'sticker_lift' => 'Sticker Lift', 'totem_stainless' => 'Totem Stainless', 'digital' => 'Digital']; ?>
<?php $tipeBadge = ['t_banner' => 'primary', 'hanging' => 'info text-dark', 'sticker_lift' => 'warning text-dark', 'totem_stainless' => 'secondary', 'digital' => 'dark']; ?>
<?php $sumberLabel = ['internal' => 'Internal', 'tenant' => 'Tenant', 'external' => 'External']; ?>
<?php $sumberBadge = ['internal' => 'secondary', 'tenant' => 'info text-dark', 'external' => 'warning text-dark']; ?>

<?php foreach ($groups as $batchKey => $items):
    $first = $items[0];
?>
<div class="card mb-3">
    <!-- Batch header -->
    <div class="card-header py-2 d-flex align-items-start justify-content-between">
        <div>
            <div class="fw-bold"><?= esc($first['nama_materi']) ?></div>
            <div class="small text-muted mt-1">
                <span class="me-3"><i class="bi bi-building me-1"></i><?= esc($first['dept']) ?><?= $first['requested_by'] ? ' · ' . esc($first['requested_by']) : '' ?></span>
                <span class="me-3"><i class="bi bi-calendar3 me-1"></i><?= date('d M Y', strtotime($first['tanggal_mulai'])) ?> s/d <?= date('d M Y', strtotime($first['tanggal_selesai'])) ?></span>
                <span class="badge bg-<?= $sumberBadge[$first['sumber']] ?? 'secondary' ?> me-1"><?= $sumberLabel[$first['sumber']] ?? $first['sumber'] ?></span>
                <?php if ($first['is_berbayar']): ?>
                <span class="badge bg-success">Berbayar</span>
                <?php else: ?>
                <span class="badge border text-muted">Gratis</span>
                <?php endif; ?>
                <?php if ($first['submitted_at']): ?>
                <span class="text-muted"><i class="bi bi-clock me-1"></i>Disubmit <?= date('d M Y H:i', strtotime($first['submitted_at'])) ?></span>
                <?php endif; ?>
            </div>
            <?php if ($first['catatan_pemohon']): ?>
            <div class="small text-info mt-1"><i class="bi bi-chat-left-text me-1"></i><?= esc($first['catatan_pemohon']) ?></div>
            <?php endif; ?>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-warning text-dark"><?= count($items) ?> item</span>
            <button type="button" class="btn btn-sm btn-outline-secondary py-0"
                    onclick="openDetail(<?= htmlspecialchars(json_encode($first)) ?>, <?= count($items) ?>)"
                    title="Lihat detail">
                <i class="bi bi-eye"></i>
            </button>
        </div>
    </div>

    <!-- Batch items with checkboxes -->
    <form method="POST" action="<?= base_url('creative/media-promo/usage/batch-approve') ?>"
          class="batch-form" id="batch_<?= esc($batchKey) ?>"><?= csrf_field() ?>
    <div class="card-body p-0">
        <table class="table table-sm mb-0 align-middle small">
        <thead class="table-light">
            <tr>
                <th style="width:36px">
                    <input type="checkbox" class="form-check-input batch-check-all"
                           data-batch="<?= esc($batchKey) ?>" checked title="Pilih semua">
                </th>
                <th>Titik</th>
                <th>Materi</th>
                <th>Slot</th>
                <th class="d-none d-sm-table-cell">Area</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $u): ?>
        <tr>
            <td>
                <input type="checkbox" class="form-check-input item-check"
                       name="approve_ids[]" value="<?= $u['id'] ?>"
                       data-batch="<?= esc($batchKey) ?>" checked>
            </td>
            <td>
                <span class="fw-semibold font-monospace"><?= esc($u['spot_kode']) ?></span>
                <div class="text-muted" style="font-size:.72rem"><?= esc($u['spot_nama']) ?></div>
            </td>
            <td>
                <div class="fw-semibold"><?= esc($u['nama_materi']) ?></div>
                <?php if ($u['deskripsi_materi']): ?>
                <div class="text-muted d-none d-sm-block" style="font-size:.72rem"><?= esc($u['deskripsi_materi']) ?></div>
                <?php endif; ?>
            </td>
            <td><?= $u['slot_number'] ? 'Slot '.$u['slot_number'] : '—' ?></td>
            <td class="text-muted d-none d-sm-table-cell"><?= esc($u['spot_area'] ?? '—') ?></td>
            <td>
                <span class="badge bg-<?= $tipeBadge[$u['spot_tipe']] ?? 'secondary' ?>" style="font-size:.65rem">
                    <?= $tipeLabel[$u['spot_tipe']] ?? $u['spot_tipe'] ?>
                </span>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        </table>
    </div>
    <div class="card-footer py-2 d-flex align-items-center gap-2">
        <input type="text" name="catatan_approver" class="form-control form-control-sm"
               placeholder="Catatan approver (opsional)" style="max-width:320px">
        <button type="submit" class="btn btn-sm btn-success ms-auto">
            <i class="bi bi-check-lg me-1"></i>Approve Terpilih
        </button>
        <button type="button" class="btn btn-sm btn-outline-danger"
                onclick="openRejectBatch('<?= esc($batchKey) ?>', <?= htmlspecialchars(json_encode(array_column($items, 'id'))) ?>)">
            <i class="bi bi-x-lg me-1"></i>Tolak Semua
        </button>
    </div>
    </form>
</div>
<?php endforeach; ?>
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

<script>
const BASE = '<?= base_url() ?>';

// Select-all per batch
document.querySelectorAll('.batch-check-all').forEach(chkAll => {
    chkAll.addEventListener('change', function() {
        const batch = this.dataset.batch;
        document.querySelectorAll(`.item-check[data-batch="${batch}"]`)
            .forEach(c => c.checked = this.checked);
    });
});

// Sync select-all state when individual items change
document.querySelectorAll('.item-check').forEach(chk => {
    chk.addEventListener('change', function() {
        const batch   = this.dataset.batch;
        const all     = document.querySelectorAll(`.item-check[data-batch="${batch}"]`);
        const checked = document.querySelectorAll(`.item-check[data-batch="${batch}"]:checked`);
        const chkAll  = document.querySelector(`.batch-check-all[data-batch="${batch}"]`);
        if (chkAll) chkAll.checked = all.length === checked.length;
    });
});

// Validate at least 1 checked before approve submit
document.querySelectorAll('.batch-form').forEach(form => {
    form.addEventListener('submit', function(e) {
        const checked = this.querySelectorAll('.item-check:checked');
        if (checked.length === 0) {
            e.preventDefault();
            alert('Pilih minimal 1 item untuk disetujui.');
        }
    });
});

function openRejectBatch(batchKey, ids) {
    const container = document.getElementById('rejectBatchIds');
    container.innerHTML = ids.map(id => `<input type="hidden" name="ids[]" value="${id}">`).join('');
    document.getElementById('formRejectBatch').action = BASE + 'creative/media-promo/usage/reject-batch';
    document.querySelector('#modalRejectBatch textarea').value = '';
    new bootstrap.Modal(document.getElementById('modalRejectBatch')).show();
}

function openDetail(d, itemCount) {
    const sumberLabel = {internal:'Internal Manajemen', tenant:'Tenant Mall', external:'External Client'};
    document.getElementById('detailNamaMateri').textContent  = d.nama_materi;
    document.getElementById('detailDeskripsi').textContent   = d.deskripsi_materi || '—';
    document.getElementById('detailDept').textContent        = d.dept + (d.requested_by ? ' · ' + d.requested_by : '');
    document.getElementById('detailPeriode').textContent     = d.tanggal_mulai + ' s/d ' + d.tanggal_selesai;
    document.getElementById('detailSumber').textContent      = sumberLabel[d.sumber] || d.sumber;
    document.getElementById('detailBerbayar').textContent    = d.is_berbayar == 1 ? 'Ya' : 'Tidak';
    document.getElementById('detailCatatan').textContent     = d.catatan_pemohon || '—';
    document.getElementById('detailSubmittedBy').textContent = d.submitted_by_name || '—';
    document.getElementById('detailSubmittedAt').textContent = d.submitted_at || '—';
    document.getElementById('detailJumlahItem').textContent  = itemCount + ' titik / slot';
    new bootstrap.Modal(document.getElementById('modalDetail')).show();
}

function openApprove(id, nama) {
    document.getElementById('approveNama').textContent = nama;
    document.getElementById('formApprove').action = BASE + 'creative/media-promo/usage/' + id + '/approve';
    document.querySelector('#formApprove textarea').value = '';
    new bootstrap.Modal(document.getElementById('modalApprove')).show();
}
</script>

<?= $this->endSection() ?>
