<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?= view('partials/complete_data_bar', ['event' => $event, 'module' => 'sponsors', 'completion' => $completion, 'canEdit' => $canEdit, 'user' => $user]) ?>

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= base_url('events/'.$event['id'].'/summary') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h4 class="fw-bold mb-0">Sponsorship</h4>
        <small class="text-muted"><?= esc($event['name']) ?></small>
    </div>
    <?php if ($canEdit): ?>
    <button class="btn btn-sm btn-primary ms-auto" data-bs-toggle="modal" data-bs-target="#addModal">
        <i class="bi bi-plus-lg me-1"></i> Tambah Sponsor
    </button>
    <?php endif; ?>
</div>

<?php if (! empty($sponsors)): ?>
<div class="row g-3 mb-3">
    <div class="col-md-2"><div class="card text-center h-100"><div class="card-body py-3">
        <div class="fw-bold fs-4"><?= count($sponsors) ?></div>
        <small class="text-muted">Total Sponsor</small>
    </div></div></div>
    <div class="col-md-3"><div class="card h-100"><div class="card-body py-3">
        <div class="small text-muted">Nilai Cash</div>
        <div class="fw-bold text-success">Rp <?= number_format($totalCash,0,',','.') ?></div>
    </div></div></div>
    <div class="col-md-3"><div class="card h-100"><div class="card-body py-3">
        <div class="small text-muted">Nilai Barang / In-Kind</div>
        <div class="fw-bold text-info">Rp <?= number_format($totalBarang,0,',','.') ?></div>
    </div></div></div>
    <div class="col-md-4"><div class="card h-100"><div class="card-body py-3">
        <div class="d-flex justify-content-between align-items-start mb-1">
            <div class="small text-muted">Total Realisasi</div>
            <span class="badge bg-<?= $barGlobal ?>-subtle text-<?= $barGlobal ?> small"><?= $pctGlobal ?>%</span>
        </div>
        <div class="fw-bold text-<?= $barGlobal ?>">Rp <?= number_format($totalRealisasi,0,',','.') ?></div>
        <div class="progress mt-2" style="height:5px">
            <div class="progress-bar bg-<?= $barGlobal ?>" style="width:<?= $pctGlobal ?>%"></div>
        </div>
    </div></div></div>
</div>
<?php endif; ?>

<?php if (empty($sponsors)): ?>
<div class="card"><div class="card-body text-center py-5 text-muted">
    <i class="bi bi-award display-4 d-block mb-2 opacity-25"></i>
    <p>Belum ada sponsor untuk event ini.</p>
    <?php if ($canEdit): ?>
    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
        <i class="bi bi-plus-lg me-1"></i> Tambah Sponsor
    </button>
    <?php endif; ?>
</div></div>
<?php else: ?>
<?php foreach ($sponsors as $sp):
    $items  = $itemsBySponsors[$sp['id']] ?? [];
    $rList  = $realisasi[$sp['id']] ?? [];
    $rTotal = array_sum(array_column($rList, 'nilai'));
    $pct    = $sp['nilai'] > 0 ? min(100, round($rTotal / $sp['nilai'] * 100)) : 0;
    $barColor = $pct >= 100 ? 'danger' : ($pct >= 75 ? 'warning' : 'success');
?>
<div class="card mb-3" id="sponsor-<?= $sp['id'] ?>">
<div class="card-header d-flex align-items-start gap-2">
    <div class="flex-grow-1">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="fw-semibold"><?= esc($sp['nama_sponsor']) ?></span>
            <?php if ($sp['jenis'] === 'cash'): ?>
            <span class="badge bg-success-subtle text-success">Cash</span>
            <?php else: ?>
            <span class="badge bg-info-subtle text-info">Barang / In-Kind</span>
            <?php endif; ?>
        </div>
        <?php if ($sp['detail']): ?>
        <div class="small text-muted mt-1"><?= esc($sp['detail']) ?></div>
        <?php endif; ?>
        <?php if ($sp['jenis'] === 'barang' && ! empty($items)): ?>
        <div class="small text-muted mt-1 d-flex flex-wrap gap-3">
            <?php foreach ($items as $itm): ?>
            <span><i class="bi bi-box me-1"></i><?= esc($itm['deskripsi_barang'] ?: '—') ?><?= $itm['qty'] ? ' · ' . number_format($itm['qty']) . ' pcs' : '' ?><?= $itm['nilai'] ? ' · Rp ' . number_format($itm['nilai'],0,',','.') : '' ?></span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <div class="text-end ms-2 flex-shrink-0">
        <div class="small text-muted">Nilai</div>
        <div class="fw-bold <?= $sp['jenis'] === 'cash' ? 'text-success' : 'text-info' ?>">Rp <?= number_format($sp['nilai'],0,',','.') ?></div>
    </div>
    <?php if ($canEdit): ?>
    <div class="flex-shrink-0 d-flex gap-1">
        <button class="btn btn-sm btn-outline-secondary edit-btn"
            data-id="<?= $sp['id'] ?>"
            data-nama="<?= esc($sp['nama_sponsor'], 'attr') ?>"
            data-jenis="<?= $sp['jenis'] ?>"
            data-nilai="<?= number_format($sp['nilai'],0,',','.') ?>"
            data-detail="<?= esc($sp['detail'], 'attr') ?>"
            data-items="<?= esc(json_encode($items), 'attr') ?>">
            <i class="bi bi-pencil"></i>
        </button>
        <a href="<?= base_url('events/'.$event['id'].'/sponsors/'.$sp['id'].'/delete') ?>"
           class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus sponsor ini?')">
            <i class="bi bi-trash"></i>
        </a>
    </div>
    <?php endif; ?>
</div>
<div class="card-body">
    <!-- Progress realisasi -->
    <?php if ($sp['nilai'] > 0): ?>
    <div class="d-flex justify-content-between small mb-1">
        <span class="text-muted">Realisasi</span>
        <span class="fw-semibold text-<?= $barColor ?>">Rp <?= number_format($rTotal,0,',','.') ?> / Rp <?= number_format($sp['nilai'],0,',','.') ?> (<?= $pct ?>%)</span>
    </div>
    <div class="progress mb-3" style="height:6px">
        <div class="progress-bar bg-<?= $barColor ?>" style="width:<?= $pct ?>%"></div>
    </div>
    <?php endif; ?>

    <!-- Realisasi entries -->
    <?php if (! empty($rList)): ?>
    <div class="table-responsive mb-3">
    <table class="table table-sm table-bordered mb-0" style="font-size:.82rem">
    <thead class="table-light"><tr>
        <th>Tanggal</th><th class="text-end">Nilai Realisasi</th><th>Catatan</th>
        <th class="text-center">Foto Dokumentasi</th><th class="text-center">Bukti Tanda Terima</th>
        <?php if ($canEdit): ?><th></th><?php endif; ?>
    </tr></thead>
    <tbody>
    <?php foreach ($rList as $r): ?>
    <tr>
        <td class="text-nowrap"><?= $r['tanggal'] ? date('d M Y', strtotime($r['tanggal'])) : '—' ?></td>
        <td class="text-end fw-medium"><?= $r['nilai'] ? 'Rp ' . number_format($r['nilai'],0,',','.') : '—' ?></td>
        <td><?= esc($r['catatan'] ?: '—') ?></td>
        <td class="text-center">
            <?php if ($r['file_foto']): ?>
            <?php $ext = strtolower(pathinfo($r['file_foto'], PATHINFO_EXTENSION)); ?>
            <?php if (in_array($ext, ['jpg','jpeg','png','webp'])): ?>
            <a href="<?= base_url('uploads/sponsor-realisasi/'.$sp['event_id'].'/'.$r['file_foto']) ?>" target="_blank">
                <img src="<?= base_url('uploads/sponsor-realisasi/'.$sp['event_id'].'/'.$r['file_foto']) ?>"
                     style="height:40px;width:60px;object-fit:cover;border-radius:4px" alt="foto">
            </a>
            <?php else: ?>
            <a href="<?= base_url('uploads/sponsor-realisasi/'.$sp['event_id'].'/'.$r['file_foto']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-file-earmark"></i>
            </a>
            <?php endif; ?>
            <?php else: ?>—<?php endif; ?>
        </td>
        <td class="text-center">
            <?php if ($r['file_terima']): ?>
            <?php $ext = strtolower(pathinfo($r['file_terima'], PATHINFO_EXTENSION)); ?>
            <?php if (in_array($ext, ['jpg','jpeg','png','webp'])): ?>
            <a href="<?= base_url('uploads/sponsor-realisasi/'.$sp['event_id'].'/'.$r['file_terima']) ?>" target="_blank">
                <img src="<?= base_url('uploads/sponsor-realisasi/'.$sp['event_id'].'/'.$r['file_terima']) ?>"
                     style="height:40px;width:60px;object-fit:cover;border-radius:4px" alt="terima">
            </a>
            <?php else: ?>
            <a href="<?= base_url('uploads/sponsor-realisasi/'.$sp['event_id'].'/'.$r['file_terima']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-file-earmark-pdf"></i>
            </a>
            <?php endif; ?>
            <?php else: ?>—<?php endif; ?>
        </td>
        <?php if ($canEdit): ?>
        <td>
            <a href="<?= base_url('events/'.$event['id'].'/sponsors/'.$sp['id'].'/realisasi/'.$r['id'].'/delete') ?>"
               class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus realisasi ini?')">
                <i class="bi bi-trash"></i>
            </a>
        </td>
        <?php endif; ?>
    </tr>
    <?php endforeach; ?>
    </tbody>
    </table>
    </div>
    <?php endif; ?>

    <!-- Form tambah realisasi -->
    <?php if ($canEdit): ?>
    <button class="btn btn-sm btn-outline-primary" type="button"
            data-bs-toggle="collapse" data-bs-target="#formReal<?= $sp['id'] ?>">
        <i class="bi bi-plus-lg me-1"></i> Input Realisasi
    </button>
    <div class="collapse mt-3" id="formReal<?= $sp['id'] ?>">
    <form method="POST" action="<?= base_url('events/'.$event['id'].'/sponsors/'.$sp['id'].'/realisasi/add') ?>"
          enctype="multipart/form-data" class="border rounded p-3 bg-light">
    <?= csrf_field() ?>
    <div class="row g-2 mb-2">
        <div class="col-md-3">
            <label class="form-label small fw-semibold">Tanggal</label>
            <input type="date" name="tanggal" class="form-control form-control-sm"
                   min="<?= $event['start_date'] ?>"
                   max="<?= date('Y-m-d', strtotime($event['start_date'] . ' +' . ($event['event_days'] - 1) . ' days')) ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold">Nilai Realisasi (Rp)</label>
            <input type="text" name="nilai" class="form-control form-control-sm currency-input" value="0">
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold">Catatan</label>
            <input type="text" name="catatan" class="form-control form-control-sm" placeholder="Keterangan realisasi...">
        </div>
    </div>
    <div class="row g-2 mb-2">
        <div class="col-md-6">
            <label class="form-label small fw-semibold"><i class="bi bi-image me-1"></i>Foto Dokumentasi</label>
            <input type="file" name="file_foto" class="form-control form-control-sm" accept="image/*,.pdf">
            <div class="form-text">JPG, PNG, PDF maks 5MB</div>
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold"><i class="bi bi-receipt me-1"></i>Bukti Tanda Terima</label>
            <input type="file" name="file_terima" class="form-control form-control-sm" accept="image/*,.pdf">
            <div class="form-text">JPG, PNG, PDF maks 5MB</div>
        </div>
    </div>
    <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-check-lg me-1"></i> Simpan Realisasi</button>
    </form>
    </div>
    <?php endif; ?>
</div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php if ($canEdit): ?>
<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
<form method="POST" action="<?= base_url('events/'.$event['id'].'/sponsors/add') ?>">
<?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title fw-semibold">Tambah Sponsor</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="mb-3">
        <label class="form-label small fw-semibold">Nama Sponsor <span class="text-danger">*</span></label>
        <input type="text" name="nama_sponsor" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label small fw-semibold">Jenis</label>
        <select name="jenis" class="form-select" id="addJenis">
            <option value="cash">Cash</option>
            <option value="barang">Barang / In-Kind</option>
        </select>
    </div>
    <div id="addCashSection">
        <div class="mb-3">
            <label class="form-label small fw-semibold">Nilai (Rp)</label>
            <input type="text" name="nilai" class="form-control currency-input" value="0">
        </div>
    </div>
    <div id="addBarangSection" style="display:none">
        <label class="form-label small fw-semibold">Daftar Barang</label>
        <div class="mb-1 row g-1 px-1">
            <div class="col-5"><small class="text-muted">Nama Barang</small></div>
            <div class="col-2"><small class="text-muted">Qty</small></div>
            <div class="col-4"><small class="text-muted">Nilai Estimasi (Rp)</small></div>
        </div>
        <div id="addItemsContainer"></div>
        <button type="button" class="btn btn-sm btn-outline-secondary mt-1" onclick="addItemRow('add')">
            <i class="bi bi-plus-lg me-1"></i> Tambah Item
        </button>
        <div class="mt-2 text-end small fw-semibold">Total: <span id="addBarangTotal">Rp 0</span></div>
    </div>
    <div class="mb-3 mt-3">
        <label class="form-label small fw-semibold">Keterangan</label>
        <textarea name="detail" class="form-control" rows="2"></textarea>
    </div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Tambah</button></div>
</form>
</div></div></div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
<form id="editForm" method="POST">
<?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title fw-semibold">Edit Sponsor</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="mb-3">
        <label class="form-label small fw-semibold">Nama Sponsor</label>
        <input type="text" name="nama_sponsor" id="eNama" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label small fw-semibold">Jenis</label>
        <select name="jenis" id="eJenis" class="form-select">
            <option value="cash">Cash</option>
            <option value="barang">Barang / In-Kind</option>
        </select>
    </div>
    <div id="editCashSection">
        <div class="mb-3">
            <label class="form-label small fw-semibold">Nilai (Rp)</label>
            <input type="text" name="nilai" id="eNilai" class="form-control currency-input">
        </div>
    </div>
    <div id="editBarangSection">
        <label class="form-label small fw-semibold">Daftar Barang</label>
        <div class="mb-1 row g-1 px-1">
            <div class="col-5"><small class="text-muted">Nama Barang</small></div>
            <div class="col-2"><small class="text-muted">Qty</small></div>
            <div class="col-4"><small class="text-muted">Nilai Estimasi (Rp)</small></div>
        </div>
        <div id="editItemsContainer"></div>
        <button type="button" class="btn btn-sm btn-outline-secondary mt-1" onclick="addItemRow('edit')">
            <i class="bi bi-plus-lg me-1"></i> Tambah Item
        </button>
        <div class="mt-2 text-end small fw-semibold">Total: <span id="editBarangTotal">Rp 0</span></div>
    </div>
    <div class="mb-3 mt-3">
        <label class="form-label small fw-semibold">Keterangan</label>
        <textarea name="detail" id="eDetail" class="form-control" rows="2"></textarea>
    </div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
</form>
</div></div></div>
<?php endif; ?>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
function formatRp(n) { return 'Rp ' + n.toLocaleString('id-ID'); }
function parseCurrency(v) { return parseInt(v.replace(/[^0-9]/g, '')) || 0; }

function updateBarangTotal(prefix) {
    let total = 0;
    document.getElementById(prefix + 'ItemsContainer').querySelectorAll('.item-nilai').forEach(inp => {
        total += parseCurrency(inp.value);
    });
    document.getElementById(prefix + 'BarangTotal').textContent = formatRp(total);
}

function addItemRow(prefix, desk, qty, nilai) {
    const container = document.getElementById(prefix + 'ItemsContainer');
    const row = document.createElement('div');
    row.className = 'row g-1 mb-1 item-row';
    row.innerHTML =
        '<div class="col-5"><input type="text" name="deskripsi_barang[]" class="form-control form-control-sm" placeholder="Nama barang" value="' + (desk||'') + '"></div>' +
        '<div class="col-2"><input type="number" name="qty[]" class="form-control form-control-sm" placeholder="0" min="0" value="' + (qty||'') + '"></div>' +
        '<div class="col-4"><input type="text" name="nilai_item[]" class="form-control form-control-sm item-nilai currency-input" placeholder="0" value="' + (nilai ? parseInt(nilai).toLocaleString('id-ID') : '') + '"></div>' +
        '<div class="col-1"><button type="button" class="btn btn-sm btn-outline-danger w-100 remove-row"><i class="bi bi-x"></i></button></div>';
    container.appendChild(row);
    row.querySelector('.item-nilai').addEventListener('input', function() {
        let n = parseCurrency(this.value); this.value = n.toLocaleString('id-ID');
        updateBarangTotal(prefix);
    });
    row.querySelector('.remove-row').addEventListener('click', function() {
        row.remove(); updateBarangTotal(prefix);
    });
    updateBarangTotal(prefix);
}

function toggleJenis(jenis, prefix) {
    document.getElementById(prefix + 'CashSection').style.display  = jenis === 'cash'   ? '' : 'none';
    document.getElementById(prefix + 'BarangSection').style.display = jenis === 'barang' ? '' : 'none';
}

<?php if ($canEdit): ?>
document.getElementById('addModal').addEventListener('show.bs.modal', function() {
    const c = document.getElementById('addItemsContainer');
    if (c.children.length === 0) addItemRow('add');
});
document.getElementById('addJenis').addEventListener('change', function() { toggleJenis(this.value, 'add'); });

document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const jenis = this.dataset.jenis;
        document.getElementById('editForm').action = '<?= base_url('events/'.$event['id'].'/sponsors/') ?>' + this.dataset.id + '/edit';
        document.getElementById('eNama').value   = this.dataset.nama;
        document.getElementById('eJenis').value  = jenis;
        document.getElementById('eNilai').value  = this.dataset.nilai;
        document.getElementById('eDetail').value = this.dataset.detail;
        const container = document.getElementById('editItemsContainer');
        container.innerHTML = '';
        if (jenis === 'barang') {
            const items = JSON.parse(this.dataset.items || '[]');
            if (items.length > 0) {
                items.forEach(itm => addItemRow('edit', itm.deskripsi_barang||'', itm.qty||'', itm.nilai||0));
            } else {
                addItemRow('edit');
            }
        }
        toggleJenis(jenis, 'edit');
        new bootstrap.Modal(document.getElementById('editModal')).show();
    });
});
document.getElementById('eJenis').addEventListener('change', function() {
    const jenis = this.value;
    if (jenis === 'barang') {
        const c = document.getElementById('editItemsContainer');
        if (c.children.length === 0) addItemRow('edit');
    }
    toggleJenis(jenis, 'edit');
});
<?php endif; ?>

document.querySelectorAll('.currency-input:not(.item-nilai)').forEach(inp => {
    inp.addEventListener('input', function() { let n = parseCurrency(this.value); this.value = n.toLocaleString('id-ID'); });
});
</script>
<?= $this->endSection() ?>
