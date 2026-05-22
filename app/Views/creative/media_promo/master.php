<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center justify-content-between mb-3">
    <h4 class="fw-bold mb-0"><i class="bi bi-pin-map me-2"></i>Master Titik Media Promo</h4>
    <div class="d-flex gap-2">
        <a href="<?= base_url('creative/media-promo') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahTitik">
            <i class="bi bi-plus-lg me-1"></i>Tambah Titik
        </button>
    </div>
</div>

<!-- Cetak -->
<h6 class="fw-semibold text-muted mb-2"><i class="bi bi-file-earmark-image me-1"></i>Media Cetak</h6>
<div class="card mb-4">
<div class="card-body p-0">
<table class="table table-hover mb-0 align-middle small">
<thead class="table-light">
    <tr>
        <th>Kode</th>
        <th>Nama</th>
        <th>Tipe</th>
        <th>Area / Lokasi</th>
        <th>Ukuran</th>
        <th>Status</th>
        <th></th>
    </tr>
</thead>
<tbody>
<?php if (empty($cetak)): ?>
<tr><td colspan="7" class="text-center text-muted py-4">Belum ada titik media cetak.</td></tr>
<?php else: foreach ($cetak as $s): ?>
<tr>
    <td class="fw-semibold font-monospace"><?= esc($s['kode']) ?></td>
    <td><?= esc($s['nama']) ?></td>
    <td>
        <span class="badge bg-<?= $s['tipe'] === 't_banner' ? 'primary' : 'info text-dark' ?>">
            <?= $s['tipe'] === 't_banner' ? 'T-Banner' : 'Hanging Banner' ?>
        </span>
    </td>
    <td><?= esc($s['area'] ?? '—') ?></td>
    <td><?= esc($s['ukuran'] ?? '—') ?></td>
    <td>
        <span class="badge bg-<?= $s['is_active'] ? 'success' : 'secondary' ?>">
            <?= $s['is_active'] ? 'Aktif' : 'Nonaktif' ?>
        </span>
    </td>
    <td>
        <div class="d-flex gap-1">
            <button class="btn btn-sm btn-outline-primary" onclick="openEdit(<?= htmlspecialchars(json_encode($s)) ?>)">
                <i class="bi bi-pencil"></i>
            </button>
            <a href="<?= base_url('creative/media-promo/spots/'.$s['id'].'/delete') ?>"
               onclick="return confirm('Hapus titik ini?')"
               class="btn btn-sm btn-outline-danger">
                <i class="bi bi-trash"></i>
            </a>
        </div>
    </td>
</tr>
<?php endforeach; endif; ?>
</tbody>
</table>
</div>
</div>

<!-- Digital -->
<h6 class="fw-semibold text-muted mb-2"><i class="bi bi-display me-1"></i>Media Digital</h6>
<div class="card">
<div class="card-body p-0">
<table class="table table-hover mb-0 align-middle small">
<thead class="table-light">
    <tr>
        <th>Kode</th>
        <th>Nama</th>
        <th>Area / Lokasi</th>
        <th>Ukuran / Resolusi</th>
        <th class="text-center">Jumlah Slot</th>
        <th>Status</th>
        <th></th>
    </tr>
</thead>
<tbody>
<?php if (empty($digital)): ?>
<tr><td colspan="7" class="text-center text-muted py-4">Belum ada titik media digital.</td></tr>
<?php else: foreach ($digital as $s): ?>
<tr>
    <td class="fw-semibold font-monospace"><?= esc($s['kode']) ?></td>
    <td><?= esc($s['nama']) ?></td>
    <td><?= esc($s['area'] ?? '—') ?></td>
    <td><?= esc($s['ukuran'] ?? '—') ?></td>
    <td class="text-center">
        <span class="badge bg-dark"><?= $s['total_slots'] ?> slot</span>
    </td>
    <td>
        <span class="badge bg-<?= $s['is_active'] ? 'success' : 'secondary' ?>">
            <?= $s['is_active'] ? 'Aktif' : 'Nonaktif' ?>
        </span>
    </td>
    <td>
        <div class="d-flex gap-1">
            <button class="btn btn-sm btn-outline-primary" onclick="openEdit(<?= htmlspecialchars(json_encode($s)) ?>)">
                <i class="bi bi-pencil"></i>
            </button>
            <a href="<?= base_url('creative/media-promo/spots/'.$s['id'].'/delete') ?>"
               onclick="return confirm('Hapus titik ini?')"
               class="btn btn-sm btn-outline-danger">
                <i class="bi bi-trash"></i>
            </a>
        </div>
    </td>
</tr>
<?php endforeach; endif; ?>
</tbody>
</table>
</div>
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
            <select name="tipe" id="tambahTipe" class="form-select" required onchange="toggleSlotInput(this.value,'tambahSlotRow')">
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

<script>
const BASE = '<?= base_url() ?>';
const tipeLabel = {t_banner: 'T-Banner', hanging: 'Hanging Banner', sticker_lift: 'Sticker Lift', totem_stainless: 'Totem Stainless', digital: 'Digital'};

function toggleSlotInput(tipe, rowId) {
    document.getElementById(rowId).style.display = tipe === 'digital' ? '' : 'none';
    const inp = document.querySelector('#' + rowId + ' input');
    if (inp) inp.required = tipe === 'digital';
}

function openEdit(s) {
    document.getElementById('edit_kode').value        = s.kode;
    document.getElementById('edit_nama').value        = s.nama;
    document.getElementById('edit_tipe_label').value  = tipeLabel[s.tipe] || s.tipe;
    document.getElementById('edit_area').value        = s.area || '';
    document.getElementById('edit_ukuran').value      = s.ukuran || '';
    document.getElementById('edit_catatan').value     = s.catatan || '';
    const slotRow = document.getElementById('editSlotRow');
    if (s.tipe === 'digital') {
        slotRow.style.display = '';
        document.getElementById('edit_total_slots').value = s.total_slots;
    } else {
        slotRow.style.display = 'none';
    }
    document.getElementById('formEditTitik').action = BASE + 'creative/media-promo/spots/' + s.id + '/update';
    new bootstrap.Modal(document.getElementById('modalEditTitik')).show();
}
</script>

<?= $this->endSection() ?>
