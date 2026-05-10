<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0"><i class="bi bi-layers-fill me-2"></i>Master Divisi</h4>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addDivModal">
        <i class="bi bi-plus-lg me-1"></i> Tambah Divisi
    </button>
</div>

<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success alert-dismissible fade show py-2" role="alert">
    <?= session()->getFlashdata('success') ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (empty($divisions)): ?>
<div class="card"><div class="card-body text-center py-5 text-muted">
    <i class="bi bi-layers display-4 d-block mb-2 opacity-25"></i>
    <p class="mb-3">Belum ada divisi. Buat divisi untuk mengelompokkan departemen.</p>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDivModal">
        <i class="bi bi-plus-lg me-1"></i> Tambah Divisi Pertama
    </button>
</div></div>
<?php else: ?>

<div class="row g-3 mb-4">
<?php foreach ($divisions as $div): ?>
<div class="col-md-6">
<div class="card h-100">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h6 class="fw-bold mb-0"><?= esc($div['nama']) ?></h6>
                <?php if ($div['kode']): ?>
                <span class="badge bg-secondary-subtle text-secondary fw-normal"><?= esc($div['kode']) ?></span>
                <?php endif; ?>
                <?php if ($div['deskripsi']): ?>
                <p class="text-muted small mt-1 mb-0"><?= esc($div['deskripsi']) ?></p>
                <?php endif; ?>
            </div>
            <div class="d-flex gap-1 flex-shrink-0 ms-2">
                <button class="btn btn-sm btn-outline-secondary"
                    data-bs-toggle="modal" data-bs-target="#editDivModal"
                    data-id="<?= $div['id'] ?>"
                    data-nama="<?= esc($div['nama']) ?>"
                    data-kode="<?= esc($div['kode'] ?? '') ?>"
                    data-deskripsi="<?= esc($div['deskripsi'] ?? '') ?>">
                    <i class="bi bi-pencil"></i>
                </button>
                <?php if ($div['dept_count'] == 0): ?>
                <a href="<?= base_url('divisions/'.$div['id'].'/delete') ?>"
                   class="btn btn-sm btn-outline-danger"
                   onclick="return confirm('Hapus divisi <?= esc($div['nama']) ?>?')">
                    <i class="bi bi-trash"></i>
                </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="mt-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <small class="fw-semibold text-muted text-uppercase" style="font-size:.7rem;letter-spacing:.05em">
                    Departemen (<?= $div['dept_count'] ?>)
                </small>
            </div>
            <?php if (! empty($div['departments'])): ?>
            <div class="d-flex flex-wrap gap-1">
                <?php foreach ($div['departments'] as $dept): ?>
                <span class="badge bg-primary-subtle text-primary fw-normal"><?= esc($dept['name']) ?></span>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <span class="text-muted small">Belum ada departemen</span>
            <?php endif; ?>
        </div>
    </div>
</div>
</div>
<?php endforeach; ?>
</div>

<!-- Assign Department Section -->
<div class="card">
    <div class="card-header fw-semibold py-2">Pindahkan Departemen ke Divisi</div>
    <div class="card-body">
        <form method="POST" action="<?= base_url('divisions/assign-dept') ?>" class="row g-2 align-items-end">
            <?= csrf_field() ?>
            <div class="col-md-5">
                <label class="form-label small fw-semibold">Departemen</label>
                <select name="dept_id" class="form-select form-select-sm" required>
                    <option value="">-- Pilih Departemen --</option>
                    <?php foreach ($departments as $dept): ?>
                    <option value="<?= $dept['id'] ?>"><?= esc($dept['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label small fw-semibold">Divisi (kosongkan untuk lepas)</label>
                <select name="division_id" class="form-select form-select-sm">
                    <option value="">-- Tidak di Divisi --</option>
                    <?php foreach ($divisions as $div): ?>
                    <option value="<?= $div['id'] ?>"><?= esc($div['nama']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-sm w-100">Simpan</button>
            </div>
        </form>
    </div>
</div>

<?php endif; ?>

<!-- Add Division Modal -->
<div class="modal fade" id="addDivModal" tabindex="-1">
<div class="modal-dialog">
<div class="modal-content">
<form method="POST" action="<?= base_url('divisions/store') ?>">
<?= csrf_field() ?>
<div class="modal-header">
    <h5 class="modal-title fw-semibold">Tambah Divisi</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
    <div class="mb-3">
        <label class="form-label small fw-semibold">Nama Divisi <span class="text-danger">*</span></label>
        <input type="text" name="nama" class="form-control" placeholder="Contoh: Business & People Development" required>
    </div>
    <div class="mb-3">
        <label class="form-label small fw-semibold">Kode (opsional)</label>
        <input type="text" name="kode" class="form-control" placeholder="Contoh: BPD">
    </div>
    <div class="mb-3">
        <label class="form-label small fw-semibold">Deskripsi (opsional)</label>
        <textarea name="deskripsi" class="form-control" rows="2"></textarea>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
    <button type="submit" class="btn btn-primary">Tambah</button>
</div>
</form>
</div></div></div>

<!-- Edit Division Modal -->
<div class="modal fade" id="editDivModal" tabindex="-1">
<div class="modal-dialog">
<div class="modal-content">
<form method="POST" id="editDivForm" action="">
<?= csrf_field() ?>
<div class="modal-header">
    <h5 class="modal-title fw-semibold">Edit Divisi</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
    <div class="mb-3">
        <label class="form-label small fw-semibold">Nama Divisi <span class="text-danger">*</span></label>
        <input type="text" name="nama" id="editDivNama" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label small fw-semibold">Kode (opsional)</label>
        <input type="text" name="kode" id="editDivKode" class="form-control">
    </div>
    <div class="mb-3">
        <label class="form-label small fw-semibold">Deskripsi (opsional)</label>
        <textarea name="deskripsi" id="editDivDeskripsi" class="form-control" rows="2"></textarea>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
    <button type="submit" class="btn btn-primary">Simpan</button>
</div>
</form>
</div></div></div>

<script>
document.getElementById('editDivModal').addEventListener('show.bs.modal', function(e) {
    const btn = e.relatedTarget;
    const id  = btn.dataset.id;
    document.getElementById('editDivForm').action = `<?= base_url('divisions/') ?>${id}/update`;
    document.getElementById('editDivNama').value      = btn.dataset.nama;
    document.getElementById('editDivKode').value      = btn.dataset.kode;
    document.getElementById('editDivDeskripsi').value = btn.dataset.deskripsi;
});
</script>

<?= $this->endSection() ?>
