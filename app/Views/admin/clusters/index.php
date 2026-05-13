<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3 fade-up" style="animation-delay:.05s">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-collection-fill me-2"></i>Cluster Kompetensi</h4>
        <small class="text-muted">Pengelompokan kompetensi lintas departemen</small>
    </div>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
        <i class="bi bi-plus-lg me-1"></i>Tambah Cluster
    </button>
</div>

<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success alert-dismissible fade show py-2">
    <?= session()->getFlashdata('success') ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (empty($clusters)): ?>
<div class="card"><div class="card-body text-center py-5 text-muted">
    <i class="bi bi-collection display-4 d-block mb-2 opacity-25"></i>
    <p class="mb-0">Belum ada cluster. Tambahkan cluster kompetensi terlebih dahulu.</p>
    <p class="small mt-1">Contoh: Leadership & Management, Technical & Digital, Administrative, dll.</p>
</div></div>
<?php else: ?>
<div class="row g-3">
<?php foreach ($clusters as $i => $cl): ?>
<div class="col-md-6 col-xl-4 fade-up" style="animation-delay:<?= .15 + $i * .07 ?>s">
    <div class="card h-100">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
                <div class="flex-grow-1">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="badge bg-secondary-subtle text-secondary fw-semibold">#<?= $cl['urutan'] ?></span>
                        <h6 class="fw-bold mb-0"><?= esc($cl['nama']) ?></h6>
                    </div>
                    <?php if ($cl['deskripsi']): ?>
                    <p class="text-muted small mb-2"><?= esc($cl['deskripsi']) ?></p>
                    <?php endif; ?>
                    <span class="badge bg-primary-subtle text-primary">
                        <i class="bi bi-diagram-2 me-1"></i><?= $cl['competency_count'] ?> kompetensi
                    </span>
                </div>
                <div class="d-flex gap-1 ms-2">
                    <button class="btn btn-sm btn-outline-secondary edit-btn"
                        data-id="<?= $cl['id'] ?>"
                        data-nama="<?= esc($cl['nama']) ?>"
                        data-deskripsi="<?= esc($cl['deskripsi'] ?? '') ?>"
                        data-urutan="<?= $cl['urutan'] ?>">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <a href="<?= base_url('admin/clusters/'.$cl['id'].'/delete') ?>"
                       class="btn btn-sm btn-outline-danger"
                       onclick="return confirm('Hapus cluster \'<?= esc($cl['nama']) ?>\'? Kompetensi di dalamnya tidak ikut terhapus, hanya cluster assignment-nya yang hilang.')">
                        <i class="bi bi-trash"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
<div class="modal-dialog"><div class="modal-content">
<form method="POST" action="<?= base_url('admin/clusters/store') ?>">
<?= csrf_field() ?>
<div class="modal-header">
    <h5 class="modal-title fw-semibold">Tambah Cluster</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
    <div class="mb-3">
        <label class="form-label small fw-semibold">Nama Cluster <span class="text-danger">*</span></label>
        <input type="text" name="nama" class="form-control" required
               placeholder="cth: Leadership & Management, Technical & Digital...">
    </div>
    <div class="mb-3">
        <label class="form-label small fw-semibold">Deskripsi</label>
        <textarea name="deskripsi" class="form-control" rows="2"
                  placeholder="Jelaskan cakupan kompetensi dalam cluster ini..."></textarea>
    </div>
    <div class="mb-3">
        <label class="form-label small fw-semibold">Urutan tampil</label>
        <input type="number" name="urutan" class="form-control" min="0" max="99"
               placeholder="Kosongkan untuk otomatis">
        <div class="form-text">Angka kecil tampil lebih awal.</div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
    <button type="submit" class="btn btn-primary">Tambah</button>
</div>
</form>
</div></div></div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
<div class="modal-dialog"><div class="modal-content">
<form method="POST" id="editForm" action="">
<?= csrf_field() ?>
<div class="modal-header">
    <h5 class="modal-title fw-semibold">Edit Cluster</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
    <div class="mb-3">
        <label class="form-label small fw-semibold">Nama Cluster <span class="text-danger">*</span></label>
        <input type="text" name="nama" id="eNama" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label small fw-semibold">Deskripsi</label>
        <textarea name="deskripsi" id="eDesk" class="form-control" rows="2"></textarea>
    </div>
    <div class="mb-3">
        <label class="form-label small fw-semibold">Urutan tampil</label>
        <input type="number" name="urutan" id="eUrutan" class="form-control" min="0" max="99">
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
    <button type="submit" class="btn btn-primary">Simpan</button>
</div>
</form>
</div></div></div>

<script>
document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('editForm').action = '<?= base_url('admin/clusters/') ?>' + this.dataset.id + '/update';
        document.getElementById('eNama').value    = this.dataset.nama;
        document.getElementById('eDesk').value    = this.dataset.deskripsi;
        document.getElementById('eUrutan').value  = this.dataset.urutan;
        new bootstrap.Modal(document.getElementById('editModal')).show();
    });
});
</script>

<?= $this->endSection() ?>
