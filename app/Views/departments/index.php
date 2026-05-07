<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0"><i class="bi bi-diagram-3 me-2"></i>Manajemen Departemen</h4>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addDeptModal">
        <i class="bi bi-plus-lg me-1"></i> Tambah Departemen
    </button>
</div>

<?php if (empty($depts)): ?>
<div class="card">
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-diagram-3 display-4 d-block mb-2 opacity-25"></i>
        <p class="mb-3">Belum ada departemen. Buat departemen untuk mengatur akses menu per tim.</p>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDeptModal">
            <i class="bi bi-plus-lg me-1"></i> Tambah Departemen Pertama
        </button>
    </div>
</div>
<?php else: ?>
<div class="row g-3">
<?php foreach ($depts as $d): ?>
<div class="col-md-6 col-lg-4">
<div class="card h-100">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <div>
                <h6 class="fw-bold mb-0"><?= esc($d['name']) ?></h6>
                <?php if ($d['description']): ?>
                <small class="text-muted"><?= esc($d['description']) ?></small>
                <?php endif; ?>
            </div>
            <div class="d-flex gap-1">
                <a href="<?= base_url('departments/'.$d['id'].'/edit') ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-sliders2"></i>
                </a>
                <?php if ($d['user_count'] == 0): ?>
                <a href="<?= base_url('departments/'.$d['id'].'/delete') ?>" class="btn btn-sm btn-outline-danger"
                   onclick="return confirm('Hapus departemen <?= esc($d['name']) ?>?')">
                    <i class="bi bi-trash"></i>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <div class="d-flex gap-3 mt-3">
            <div class="text-center">
                <div class="fw-bold fs-5"><?= $d['menu_count'] ?></div>
                <small class="text-muted">Menu Akses</small>
            </div>
            <div class="text-center">
                <div class="fw-bold fs-5"><?= $d['user_count'] ?></div>
                <small class="text-muted">User</small>
            </div>
        </div>
    </div>
</div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Add Department Modal -->
<div class="modal fade" id="addDeptModal" tabindex="-1">
<div class="modal-dialog">
<div class="modal-content">
<form method="POST" action="<?= base_url('departments/add') ?>">
<?= csrf_field() ?>
<div class="modal-header">
    <h5 class="modal-title fw-semibold">Tambah Departemen</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
    <div class="mb-3">
        <label class="form-label small fw-semibold">Nama Departemen <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control" placeholder="Contoh: Tim Loyalty, Tim Traffic, Tim Komersial" required>
    </div>
    <div class="mb-3">
        <label class="form-label small fw-semibold">Deskripsi (opsional)</label>
        <input type="text" name="description" class="form-control" placeholder="Contoh: Mengelola data loyalty & member">
    </div>
    <p class="text-muted small mb-0"><i class="bi bi-info-circle me-1"></i>Setelah membuat departemen, klik tombol <strong>pengaturan</strong> untuk mengatur akses menu.</p>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
    <button type="submit" class="btn btn-primary">Tambah</button>
</div>
</form>
</div></div></div>

<?= $this->endSection() ?>
