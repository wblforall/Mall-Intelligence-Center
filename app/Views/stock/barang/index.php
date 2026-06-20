<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <h4 class="fw-bold mb-0"><i class="bi bi-box-seam me-2"></i>Stock Barang / Hadiah Fisik</h4>
    <div class="d-flex gap-2">
        <a href="<?= base_url('stock/barang/mutasi') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left-right me-1"></i>Laporan Mutasi
        </a>
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahBarang">
            <i class="bi bi-plus-lg me-1"></i>Tambah Barang
        </button>
    </div>
</div>


<div class="card">
<div class="card-body p-0">
<table class="table table-hover mb-0 align-middle">
<thead class="table-light">
    <tr>
        <th>Nama Barang</th>
        <th>Satuan</th>
        <th class="text-end">Nilai Satuan</th>
        <th class="text-center">Stok Awal</th>
        <th class="text-center">Stok Tersedia</th>
        <th class="text-center">Reserved</th>
        <th class="text-center">Bebas</th>
        <th class="text-center">Aksi</th>
    </tr>
</thead>
<tbody>
<?php if (empty($items)): ?>
<tr><td colspan="8" class="text-center text-muted py-4">Belum ada data barang.</td></tr>
<?php else: foreach ($items as $item): ?>
<tr>
    <td>
        <div class="fw-semibold"><?= esc($item['nama_barang']) ?></div>
        <?php if ($item['catatan']): ?><div class="small text-muted"><?= esc($item['catatan']) ?></div><?php endif; ?>
    </td>
    <td><?= esc($item['satuan']) ?></td>
    <td class="text-end">Rp <?= number_format($item['nilai_satuan']) ?></td>
    <td class="text-center"><?= number_format($item['stok_awal']) ?></td>
    <td class="text-center">
        <span class="badge bg-<?= $item['stok_tersedia'] > 0 ? 'success' : 'danger' ?>">
            <?= number_format($item['stok_tersedia']) ?>
        </span>
    </td>
    <?php $reserved = (int)($item['stok_reserved'] ?? 0); $bebas = max(0, (int)$item['stok_tersedia'] - $reserved); ?>
    <td class="text-center">
        <?php if ($reserved > 0): ?>
        <span class="badge bg-warning text-dark"><?= number_format($reserved) ?></span>
        <?php else: ?>
        <span class="text-muted small">—</span>
        <?php endif; ?>
    </td>
    <td class="text-center">
        <span class="badge bg-<?= $bebas > 0 ? 'info' : 'secondary' ?> text-dark">
            <?= number_format($bebas) ?>
        </span>
    </td>
    <td class="text-center">
        <div class="d-flex gap-1 justify-content-center">
            <button class="btn btn-sm btn-outline-success" title="Tambah Stok"
                onclick="openTambahStok(<?= $item['id'] ?>, '<?= esc($item['nama_barang']) ?>', <?= $item['stok_tersedia'] ?>)">
                <i class="bi bi-plus-circle"></i>
            </button>
            <button class="btn btn-sm btn-outline-warning" title="Keluarkan Stok"
                onclick="toggleKeluar(<?= $item['id'] ?>)">
                <i class="bi bi-box-arrow-right"></i>
            </button>
            <button class="btn btn-sm btn-outline-secondary" title="Lihat Log"
                data-bs-toggle="collapse" data-bs-target="#log-<?= $item['id'] ?>">
                <i class="bi bi-clock-history"></i>
            </button>
            <button class="btn btn-sm btn-outline-primary" title="Edit"
                onclick="openEditBarang(<?= htmlspecialchars(json_encode($item)) ?>)">
                <i class="bi bi-pencil"></i>
            </button>
            <form method="POST" action="<?= base_url('stock/barang/'.$item['id'].'/delete') ?>" class="d-inline" onsubmit="return confirm('Hapus barang ini beserta semua log stok?')">
                <?= csrf_field() ?>
                <button class="btn btn-sm btn-outline-danger" title="Hapus"><i class="bi bi-trash"></i></button>
            </form>
        </div>
    </td>
</tr>
<tr class="collapse" id="keluar-<?= $item['id'] ?>">
    <td colspan="8" class="bg-light p-2">
        <form method="POST" action="<?= base_url('stock/barang/'.$item['id'].'/realisasi') ?>">
        <?= csrf_field() ?>
        <div class="d-flex align-items-end gap-2 flex-wrap">
            <div>
                <label class="form-label small fw-semibold mb-1">Tanggal</label>
                <input type="date" name="tanggal" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div>
                <label class="form-label small fw-semibold mb-1">Jumlah</label>
                <input type="number" name="jumlah" class="form-control form-control-sm keluar-jumlah"
                    min="1" max="<?= $item['stok_tersedia'] ?>"
                    data-bebas="<?= $bebas ?>" data-satuan="<?= esc($item['satuan']) ?>"
                    style="width:90px" required>
                <div class="keluar-warn text-warning small mt-1" style="display:none">Melebihi stok bebas (<?= $bebas ?>)</div>
            </div>
            <div style="flex:1;min-width:160px">
                <label class="form-label small fw-semibold mb-1">Catatan</label>
                <input type="text" name="catatan" class="form-control form-control-sm" placeholder="Opsional">
            </div>
            <div>
                <button type="submit" class="btn btn-sm btn-warning">Keluarkan</button>
                <button type="button" class="btn btn-sm btn-link text-muted" onclick="toggleKeluar(<?= $item['id'] ?>)">Batal</button>
            </div>
        </div>
        </form>
    </td>
</tr>
<tr class="collapse" id="log-<?= $item['id'] ?>">
    <td colspan="8" class="bg-light p-0">
        <div class="px-3 py-2">
            <div class="small fw-semibold text-muted mb-2">Riwayat Stok (10 terakhir)</div>
            <?php if (empty($item['log'])): ?>
            <div class="small text-muted">Belum ada riwayat.</div>
            <?php else: ?>
            <table class="table table-sm table-borderless mb-0 small">
                <thead><tr><th>Tanggal</th><th>Tipe</th><th>Jumlah</th><th>Sebelum</th><th>Sesudah</th><th>Keterangan</th></tr></thead>
                <tbody>
                <?php foreach ($item['log'] as $log): ?>
                <tr>
                    <td><?= $log['tanggal'] ?></td>
                    <td><span class="badge bg-<?= $log['tipe'] === 'masuk' ? 'success' : 'danger' ?>"><?= $log['tipe'] ?></span></td>
                    <td><?= number_format($log['jumlah']) ?></td>
                    <td><?= number_format($log['stok_sebelum']) ?></td>
                    <td><?= number_format($log['stok_sesudah']) ?></td>
                    <td><?= esc($log['catatan'] ?? ($log['referensi_tipe'] ? $log['referensi_tipe'].' #'.$log['referensi_id'] : '')) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </td>
</tr>
<?php endforeach; endif; ?>
</tbody>
</table>
</div>
</div>

<!-- Modal Tambah Barang -->
<div class="modal fade" id="modalTambahBarang" tabindex="-1">
<div class="modal-dialog">
<div class="modal-content">
<form method="POST" action="<?= base_url('stock/barang/store') ?>">
<?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title">Tambah Barang</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="mb-3">
        <label class="form-label small fw-semibold">Nama Barang <span class="text-danger">*</span></label>
        <input type="text" name="nama_barang" class="form-control" required>
    </div>
    <div class="row g-2">
        <div class="col-6">
            <label class="form-label small fw-semibold">Satuan</label>
            <input type="text" name="satuan" class="form-control" value="pcs" placeholder="pcs, buah, lembar...">
        </div>
        <div class="col-6">
            <label class="form-label small fw-semibold">Nilai Satuan (Rp)</label>
            <input type="text" name="nilai_satuan" class="form-control nominal" value="0">
        </div>
    </div>
    <div class="mb-3 mt-2">
        <label class="form-label small fw-semibold">Stok Awal</label>
        <input type="number" name="stok_awal" class="form-control" value="0" min="0">
    </div>
    <div class="mb-3">
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

<!-- Modal Edit Barang -->
<div class="modal fade" id="modalEditBarang" tabindex="-1">
<div class="modal-dialog">
<div class="modal-content">
<form method="POST" id="formEditBarang" action="">
<?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title">Edit Barang</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="mb-3">
        <label class="form-label small fw-semibold">Nama Barang <span class="text-danger">*</span></label>
        <input type="text" name="nama_barang" id="edit_nama_barang" class="form-control" required>
    </div>
    <div class="row g-2">
        <div class="col-6">
            <label class="form-label small fw-semibold">Satuan</label>
            <input type="text" name="satuan" id="edit_satuan" class="form-control">
        </div>
        <div class="col-6">
            <label class="form-label small fw-semibold">Nilai Satuan (Rp)</label>
            <input type="text" name="nilai_satuan" id="edit_nilai_satuan" class="form-control nominal">
        </div>
    </div>
    <div class="mb-3 mt-2">
        <label class="form-label small fw-semibold">Catatan</label>
        <textarea name="catatan" id="edit_catatan" class="form-control" rows="2"></textarea>
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

<!-- Modal Tambah Stok -->
<div class="modal fade" id="modalTambahStok" tabindex="-1">
<div class="modal-dialog modal-sm">
<div class="modal-content">
<form method="POST" id="formTambahStok" action="">
<?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title">Tambah Stok</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <p class="small text-muted mb-2">Stok tersedia saat ini: <strong id="stokSaatIni">—</strong></p>
    <div class="mb-3">
        <label class="form-label small fw-semibold">Jumlah Masuk <span class="text-danger">*</span></label>
        <input type="number" name="jumlah" class="form-control" min="1" required>
    </div>
    <div class="mb-3">
        <label class="form-label small fw-semibold">Catatan</label>
        <input type="text" name="catatan" class="form-control" placeholder="Opsional">
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
    <button type="submit" class="btn btn-success">Tambah</button>
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

function openEditBarang(item) {
    document.getElementById('edit_nama_barang').value  = item.nama_barang;
    document.getElementById('edit_satuan').value       = item.satuan;
    document.getElementById('edit_nilai_satuan').value = Number(item.nilai_satuan).toLocaleString('id-ID');
    document.getElementById('edit_catatan').value      = item.catatan || '';
    document.getElementById('formEditBarang').action   = '<?= base_url('stock/barang/') ?>' + item.id + '/update';
    new bootstrap.Modal(document.getElementById('modalEditBarang')).show();
}
function openTambahStok(id, nama, stok) {
    document.getElementById('stokSaatIni').textContent = stok.toLocaleString('id-ID');
    document.getElementById('formTambahStok').action   = '<?= base_url('stock/barang/') ?>' + id + '/tambah-stok';
    document.querySelector('#formTambahStok [name=jumlah]').value = '';
    new bootstrap.Modal(document.getElementById('modalTambahStok')).show();
}
function toggleKeluar(id) {
    const row = document.getElementById('keluar-' + id);
    row.classList.toggle('show');
    if (row.classList.contains('show')) {
        row.querySelector('[name=jumlah]').focus();
    }
}
document.addEventListener('input', function(e) {
    if (!e.target.classList.contains('keluar-jumlah')) return;
    const inp  = e.target;
    const warn = inp.closest('tr').querySelector('.keluar-warn');
    const val  = parseInt(inp.value) || 0;
    const bebas = parseInt(inp.dataset.bebas) || 0;
    warn.style.display = val > bebas ? '' : 'none';
});
</script>

<?= $this->endSection() ?>
