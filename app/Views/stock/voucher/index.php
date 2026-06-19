<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center justify-content-between mb-3">
    <h4 class="fw-bold mb-0"><i class="bi bi-ticket-perforated me-2"></i>Stock Voucher Fisik</h4>
    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahBatch">
        <i class="bi bi-plus-lg me-1"></i>Tambah Batch
    </button>
</div>

<?php if (empty($batches)): ?>
<div class="card"><div class="card-body text-center text-muted py-5">Belum ada batch voucher.</div></div>
<?php else: foreach ($batches as $batch):
    $expired = $batch['expired_date'] && $batch['expired_date'] < date('Y-m-d');
?>
<div class="card mb-3">
<div class="card-header d-flex align-items-center justify-content-between py-2">
    <div>
        <span class="fw-semibold"><?= esc($batch['nama_voucher']) ?></span>
        <span class="ms-2 text-muted small">Rp <?= number_format($batch['nilai_voucher']) ?></span>
        <?php if ($batch['expired_date']): ?>
        <span class="ms-2 badge bg-<?= $expired ? 'danger' : 'secondary' ?>">
            Exp: <?= date('d M Y', strtotime($batch['expired_date'])) ?>
        </span>
        <?php endif; ?>
    </div>
    <div class="d-flex align-items-center gap-2">
        <?php $vreserved = (int)($batch['stok_reserved'] ?? 0); $vbebas = max(0, (int)$batch['sisa_kode'] - $vreserved); ?>
        <span class="badge bg-success"><?= $batch['sisa_kode'] ?> tersedia</span>
        <?php if ($vreserved > 0): ?>
        <span class="badge bg-warning text-dark"><?= $vreserved ?> reserved</span>
        <span class="badge bg-info text-dark"><?= $vbebas ?> bebas</span>
        <?php endif; ?>
        <span class="text-muted small">/ <?= $batch['total_kode'] ?> total</span>
        <button class="btn btn-sm btn-outline-primary" onclick="openEditBatch(<?= htmlspecialchars(json_encode($batch)) ?>)" title="Edit">
            <i class="bi bi-pencil"></i>
        </button>
        <button class="btn btn-sm btn-outline-success" onclick="openImportKode(<?= $batch['id'] ?>, '<?= esc($batch['nama_voucher']) ?>')" title="Import Kode">
            <i class="bi bi-upload"></i> Import Kode
        </button>
        <form method="POST" action="<?= base_url('stock/voucher/'.$batch['id'].'/delete') ?>" class="d-inline" onsubmit="return confirm('Hapus batch ini dan semua kode-nya?')">
            <?= csrf_field() ?>
            <button class="btn btn-sm btn-outline-danger" title="Hapus"><i class="bi bi-trash"></i></button>
        </form>
    </div>
</div>
<?php if (! empty($batch['kodes'])): ?>
<div class="card-body p-0">
<div style="max-height:240px;overflow-y:auto">
<table class="table table-sm table-hover mb-0 small">
<thead class="table-light sticky-top">
    <tr>
        <th>Kode</th>
        <th>Status</th>
        <th>Penerima</th>
        <th>Tanggal Assign</th>
        <th>Program</th>
        <th></th>
    </tr>
</thead>
<tbody>
<?php foreach ($batch['kodes'] as $kode): ?>
<tr>
    <td class="font-monospace"><?= esc($kode['kode']) ?></td>
    <td>
        <span class="badge bg-<?= $kode['status'] === 'available' ? 'success' : ($kode['status'] === 'assigned' ? 'primary' : 'secondary') ?>">
            <?= $kode['status'] ?>
        </span>
    </td>
    <td>
        <?= esc($kode['nama_penerima'] ?? '—') ?>
        <?php if ($kode['status'] === 'assigned' && !empty($assignedBy[$kode['id']])): ?>
        <div class="text-muted" style="font-size:.72rem"><i class="bi bi-person me-1"></i>oleh <?= esc($assignedBy[$kode['id']]) ?></div>
        <?php endif; ?>
    </td>
    <td><?= $kode['assigned_at'] ? date('d M Y', strtotime($kode['assigned_at'])) : '—' ?></td>
    <td>
        <?php if ($kode['program_type'] === 'manual'): ?>
        <span class="badge bg-secondary">Manual</span>
        <?php elseif ($kode['program_type']):
            $pnKey = $kode['program_type'] . '_' . $kode['program_id'];
            $pn    = $progNames[$pnKey] ?? null;
        ?>
        <?php if ($pn): ?>
        <span class="fw-medium"><?= esc($pn) ?></span>
        <span class="badge bg-<?= $kode['program_type'] === 'event' ? 'warning text-dark' : 'primary' ?>-subtle text-<?= $kode['program_type'] === 'event' ? 'warning-emphasis' : 'primary' ?>" style="font-size:.6rem"><?= $kode['program_type'] === 'event' ? 'Event' : 'Standalone' ?></span>
        <?php else: ?>
        <?= esc(($kode['program_type'] === 'event' ? 'Event' : 'Program') . ' #' . $kode['program_id']) ?>
        <?php endif; ?>
        <?php else: ?>
        —
        <?php endif; ?>
    </td>
    <td>
        <?php if ($kode['status'] === 'available'): ?>
        <button class="btn btn-xs btn-outline-success py-0 px-1 me-1"
            onclick="openDistribusi(<?= $batch['id'] ?>, <?= $kode['id'] ?>, '<?= esc($kode['kode']) ?>')"
            title="Distribusi Manual"><i class="bi bi-send"></i></button>
        <form method="POST" action="<?= base_url('stock/voucher/'.$batch['id'].'/kode/'.$kode['id'].'/delete') ?>" class="d-inline" onsubmit="return confirm('Hapus kode ini?')">
            <?= csrf_field() ?>
            <button class="btn btn-xs btn-outline-danger py-0 px-1" title="Hapus"><i class="bi bi-x"></i></button>
        </form>
        <?php elseif (($canDeassign ?? false) && $kode['status'] === 'assigned' && $kode['program_type'] === 'manual'): ?>
        <a href="<?= base_url('stock/voucher/'.$batch['id'].'/kode/'.$kode['id'].'/deassign') ?>"
           onclick="return confirm('Batalkan distribusi manual kode ini? Kode kembali ke stok tersedia agar bisa dialokasikan via program.')"
           class="btn btn-xs btn-outline-warning py-0 px-1" title="Batalkan distribusi manual"><i class="bi bi-arrow-counterclockwise me-1"></i>Batalkan</a>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
<?php endif; ?>
</div>
<?php endforeach; endif; ?>

<!-- Modal Tambah Batch -->
<div class="modal fade" id="modalTambahBatch" tabindex="-1">
<div class="modal-dialog">
<div class="modal-content">
<form method="POST" action="<?= base_url('stock/voucher/store') ?>">
<?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title">Tambah Batch Voucher</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="mb-3">
        <label class="form-label small fw-semibold">Nama Voucher <span class="text-danger">*</span></label>
        <input type="text" name="nama_voucher" class="form-control" required>
    </div>
    <div class="row g-2">
        <div class="col-6">
            <label class="form-label small fw-semibold">Nilai Voucher (Rp)</label>
            <input type="text" name="nilai_voucher" class="form-control nominal" value="0">
        </div>
        <div class="col-6">
            <label class="form-label small fw-semibold">Expired Date</label>
            <input type="date" name="expired_date" class="form-control">
        </div>
    </div>
    <div class="mb-3 mt-2">
        <label class="form-label small fw-semibold">Catatan</label>
        <textarea name="catatan" class="form-control" rows="2"></textarea>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
    <button type="submit" class="btn btn-primary">Simpan</button>
</div>
</form>
</div>
</div>
</div>

<!-- Modal Edit Batch -->
<div class="modal fade" id="modalEditBatch" tabindex="-1">
<div class="modal-dialog">
<div class="modal-content">
<form method="POST" id="formEditBatch" action="">
<?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title">Edit Batch Voucher</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="mb-3">
        <label class="form-label small fw-semibold">Nama Voucher <span class="text-danger">*</span></label>
        <input type="text" name="nama_voucher" id="edit_nama_voucher" class="form-control" required>
    </div>
    <div class="row g-2">
        <div class="col-6">
            <label class="form-label small fw-semibold">Nilai Voucher (Rp)</label>
            <input type="text" name="nilai_voucher" id="edit_nilai_voucher" class="form-control nominal">
        </div>
        <div class="col-6">
            <label class="form-label small fw-semibold">Expired Date</label>
            <input type="date" name="expired_date" id="edit_expired_date" class="form-control">
        </div>
    </div>
    <div class="mb-3 mt-2">
        <label class="form-label small fw-semibold">Catatan</label>
        <textarea name="catatan" id="edit_catatan_batch" class="form-control" rows="2"></textarea>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
    <button type="submit" class="btn btn-primary">Simpan</button>
</div>
</form>
</div>
</div>
</div>

<!-- Modal Distribusi Kode -->
<div class="modal fade" id="modalDistribusi" tabindex="-1">
<div class="modal-dialog modal-sm">
<div class="modal-content">
<form method="POST" id="formDistribusi" action="">
<?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title">Distribusi Manual</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <p class="small text-muted mb-2">Kode: <strong id="distribusiKode" class="font-monospace"></strong></p>
    <div class="mb-3">
        <label class="form-label small fw-semibold">Nama Penerima</label>
        <input type="text" name="nama_penerima" class="form-control" placeholder="Opsional">
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
    <button type="submit" class="btn btn-success"><i class="bi bi-send me-1"></i>Distribusikan</button>
</div>
</form>
</div>
</div>
</div>

<!-- Modal Import Kode -->
<div class="modal fade" id="modalImportKode" tabindex="-1">
<div class="modal-dialog">
<div class="modal-content">
<form method="POST" id="formImportKode" action="">
<?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title">Import Kode — <span id="importBatchNama"></span></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <p class="small text-muted">Masukkan kode voucher, satu per baris. Kode duplikat akan diabaikan.</p>
    <textarea name="kodes" class="form-control font-monospace" rows="10" placeholder="KODE001&#10;KODE002&#10;KODE003"></textarea>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
    <button type="submit" class="btn btn-success"><i class="bi bi-upload me-1"></i>Import</button>
</div>
</form>
</div>
</div>
</div>

<script>
document.querySelectorAll('.nominal').forEach(inp => {
    inp.addEventListener('input', function() {
        const n = parseInt(this.value.replace(/[^0-9]/g, '')) || 0;
        this.value = n ? n.toLocaleString('id-ID') : '';
    });
});

function openEditBatch(batch) {
    document.getElementById('edit_nama_voucher').value    = batch.nama_voucher;
    document.getElementById('edit_nilai_voucher').value   = Number(batch.nilai_voucher).toLocaleString('id-ID');
    document.getElementById('edit_expired_date').value    = batch.expired_date || '';
    document.getElementById('edit_catatan_batch').value   = batch.catatan || '';
    document.getElementById('formEditBatch').action       = '<?= base_url('stock/voucher/') ?>' + batch.id + '/update';
    new bootstrap.Modal(document.getElementById('modalEditBatch')).show();
}
function openDistribusi(batchId, kodeId, kode) {
    document.getElementById('distribusiKode').textContent = kode;
    document.getElementById('formDistribusi').action = '<?= base_url('stock/voucher/') ?>' + batchId + '/kode/' + kodeId + '/distribute';
    document.querySelector('#formDistribusi [name=nama_penerima]').value = '';
    new bootstrap.Modal(document.getElementById('modalDistribusi')).show();
}
function openImportKode(id, nama) {
    document.getElementById('importBatchNama').textContent = nama;
    document.getElementById('formImportKode').action       = '<?= base_url('stock/voucher/') ?>' + id + '/import-kode';
    document.querySelector('#formImportKode textarea').value = '';
    new bootstrap.Modal(document.getElementById('modalImportKode')).show();
}
</script>

<?= $this->endSection() ?>
