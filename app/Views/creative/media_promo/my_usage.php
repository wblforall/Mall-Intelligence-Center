<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center justify-content-between mb-3">
    <h4 class="fw-bold mb-0"><i class="bi bi-list-check me-2"></i>Request Saya — Media Promo</h4>
    <a href="<?= base_url('creative/media-promo') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Kembali
    </a>
</div>

<?php if (empty($usages)): ?>
<div class="card"><div class="card-body text-center text-muted py-5">Belum ada request.</div></div>
<?php else: ?>
<form method="POST" action="<?= base_url('creative/media-promo/usage/submit-selected') ?>" id="formBulkSubmit">
<?= csrf_field() ?>
<div class="card">
<div class="card-body p-0">
<table class="table table-hover mb-0 align-middle small">
<thead class="table-light">
    <tr>
        <th style="width:36px">
            <input type="checkbox" class="form-check-input" id="checkAll" title="Pilih semua draft">
        </th>
        <th>Titik</th>
        <th>Slot</th>
        <th>Dept / Pemohon</th>
        <th>Materi</th>
        <th>Sumber</th>
        <th>Periode</th>
        <th>Status</th>
        <th></th>
    </tr>
</thead>
<tbody>
<?php
$statusBadge = [
    'draft'    => 'secondary',
    'pending'  => 'warning text-dark',
    'approved' => 'success',
    'rejected' => 'danger',
    'done'     => 'dark',
];
$sumberLabel = ['internal'=>'Internal','tenant'=>'Tenant','external'=>'External'];
$sumberBadge = ['internal'=>'secondary','tenant'=>'info text-dark','external'=>'warning text-dark'];
foreach ($usages as $u):
    $isSubmittable = in_array($u['status'], ['draft', 'rejected']);
?>
<tr>
    <td>
        <?php if ($isSubmittable): ?>
        <input type="checkbox" class="form-check-input row-check" name="ids[]" value="<?= $u['id'] ?>">
        <?php endif; ?>
    </td>
    <td>
        <div class="fw-semibold"><?= esc($u['spot_kode']) ?></div>
        <div class="text-muted" style="font-size:.72rem"><?= esc($u['spot_nama']) ?></div>
        <?php
        $tbMap = ['t_banner'=>'primary','hanging'=>'info text-dark','sticker_lift'=>'warning text-dark','totem_stainless'=>'secondary','digital'=>'dark'];
        $tlMap = ['t_banner'=>'T-Banner','hanging'=>'Hanging','sticker_lift'=>'Sticker Lift','totem_stainless'=>'Totem Stainless','digital'=>'Digital'];
        ?>
        <span class="badge bg-<?= $tbMap[$u['spot_tipe']] ?? 'secondary' ?>" style="font-size:.65rem">
            <?= $tlMap[$u['spot_tipe']] ?? esc($u['spot_tipe']) ?>
        </span>
    </td>
    <td><?= $u['slot_number'] ? 'Slot '.$u['slot_number'] : '—' ?></td>
    <td>
        <div><?= esc($u['dept']) ?></div>
        <?php if ($u['requested_by']): ?><div class="text-muted" style="font-size:.72rem"><?= esc($u['requested_by']) ?></div><?php endif; ?>
    </td>
    <td>
        <div class="fw-semibold"><?= esc($u['nama_materi']) ?></div>
        <?php if ($u['rejection_reason']): ?>
        <div class="text-danger mt-1" style="font-size:.72rem">
            <i class="bi bi-exclamation-circle me-1"></i><?= esc($u['rejection_reason']) ?>
        </div>
        <?php endif; ?>
        <?php if ($u['catatan_approver']): ?>
        <div class="text-muted mt-1" style="font-size:.72rem">
            <i class="bi bi-chat-left-dots me-1"></i><?= esc($u['catatan_approver']) ?>
        </div>
        <?php endif; ?>
    </td>
    <td>
        <span class="badge bg-<?= $sumberBadge[$u['sumber']] ?? 'secondary' ?>" style="font-size:.65rem">
            <?= $sumberLabel[$u['sumber']] ?? $u['sumber'] ?>
        </span>
        <?php if ($u['is_berbayar']): ?>
        <span class="badge bg-success" style="font-size:.65rem">Berbayar</span>
        <?php else: ?>
        <span class="badge border text-muted" style="font-size:.65rem">Gratis</span>
        <?php endif; ?>
    </td>
    <td class="text-nowrap">
        <?= date('d M Y', strtotime($u['tanggal_mulai'])) ?><br>
        <span class="text-muted">s/d</span> <?= date('d M Y', strtotime($u['tanggal_selesai'])) ?>
    </td>
    <td><span class="badge bg-<?= $statusBadge[$u['status']] ?? 'secondary' ?>"><?= $u['status'] ?></span></td>
    <td>
        <div class="d-flex gap-1">
        <?php if (in_array($u['status'], ['draft', 'rejected'])): ?>
            <button class="btn btn-xs btn-outline-primary py-0 px-1"
                onclick="openEdit(<?= htmlspecialchars(json_encode($u)) ?>)" title="Edit">
                <i class="bi bi-pencil"></i>
            </button>
            <form method="POST" action="<?= base_url('creative/media-promo/usage/'.$u['id'].'/submit') ?>" class="d-inline">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-xs btn-outline-success py-0 px-1" title="Submit">
                    <i class="bi bi-send"></i>
                </button>
            </form>
        <?php endif; ?>
        <?php if (! in_array($u['status'], ['approved', 'done'])): ?>
            <form method="POST" action="<?= base_url('creative/media-promo/usage/'.$u['id'].'/cancel') ?>" class="d-inline"
                onsubmit="return confirm('Batalkan request ini?')">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-xs btn-outline-danger py-0 px-1" title="Batalkan">
                    <i class="bi bi-trash"></i>
                </button>
            </form>
        <?php endif; ?>
        </div>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
</form>

<!-- Sticky bulk-submit bar -->
<div id="bulkBar" class="position-fixed bottom-0 start-0 end-0 bg-dark text-white px-4 py-2 d-flex align-items-center gap-3" style="display:none!important;z-index:1050">
    <span id="bulkCount" class="small"></span>
    <button type="submit" form="formBulkSubmit" class="btn btn-sm btn-success ms-auto">
        <i class="bi bi-send me-1"></i>Submit Terpilih
    </button>
    <button type="button" class="btn btn-sm btn-outline-light" onclick="uncheckAll()">Batal</button>
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

<script>
const BASE = '<?= base_url() ?>';

// Select-all draft checkboxes
document.getElementById('checkAll').addEventListener('change', function() {
    document.querySelectorAll('.row-check').forEach(c => c.checked = this.checked);
    updateBulkBar();
});

document.querySelectorAll('.row-check').forEach(c => {
    c.addEventListener('change', updateBulkBar);
});

function updateBulkBar() {
    const checked = document.querySelectorAll('.row-check:checked');
    const bar     = document.getElementById('bulkBar');
    if (checked.length > 0) {
        bar.style.removeProperty('display');
        document.getElementById('bulkCount').textContent = checked.length + ' request dipilih';
    } else {
        bar.style.display = 'none';
    }
    const all = document.querySelectorAll('.row-check');
    document.getElementById('checkAll').checked = all.length > 0 && checked.length === all.length;
}

function uncheckAll() {
    document.querySelectorAll('.row-check').forEach(c => c.checked = false);
    document.getElementById('checkAll').checked = false;
    updateBulkBar();
}

function openEdit(u) {
    document.getElementById('editSpotLabel').value    = '[' + u.spot_kode + '] ' + u.spot_nama;
    document.getElementById('editSpotId').value       = u.spot_id;
    const deptSel = document.getElementById('editDept');
    deptSel.value = u.dept;
    if (!deptSel.value) { // fallback jika nama lama tidak cocok
        const opt = document.createElement('option');
        opt.value = u.dept; opt.textContent = u.dept; opt.selected = true;
        deptSel.appendChild(opt);
    }
    document.getElementById('editRequestedBy').value  = u.requested_by || '';
    document.getElementById('editTglMulai').value     = u.tanggal_mulai;
    document.getElementById('editTglSelesai').value   = u.tanggal_selesai;
    document.getElementById('editNamaMateri').value   = u.nama_materi;
    document.getElementById('editDeskripsi').value    = u.deskripsi_materi || '';
    document.getElementById('editCatatan').value      = u.catatan_pemohon || '';
    document.getElementById('editSumber').value       = u.sumber || 'internal';
    document.getElementById('editIsBerbayar').checked = u.is_berbayar == 1;
    const slotRow = document.getElementById('editSlotRow');
    if (u.spot_tipe === 'digital') {
        slotRow.style.display = '';
        document.getElementById('editSlotNumber').value = u.slot_number || '';
    } else {
        slotRow.style.display = 'none';
    }
    document.getElementById('formEditRequest').action = BASE + 'creative/media-promo/usage/' + u.id + '/update';
    new bootstrap.Modal(document.getElementById('modalEditRequest')).show();
}
</script>

<?= $this->endSection() ?>
