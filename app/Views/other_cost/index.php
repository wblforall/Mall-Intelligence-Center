<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$deptMap = [];
foreach ($departments as $d) { $deptMap[$d['id']] = $d['name']; }
?>

<!-- Header -->
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= base_url('events/'.$event['id'].'/summary') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h4 class="fw-bold mb-0">Other Cost</h4>
        <small class="text-muted"><?= esc($event['name']) ?></small>
    </div>
    <?php if ($canEdit): ?>
    <button class="btn btn-sm btn-primary ms-auto" data-bs-toggle="modal" data-bs-target="#addModal">
        <i class="bi bi-plus-lg me-1"></i>Tambah
    </button>
    <?php endif; ?>
</div>

<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success alert-dismissible fade show py-2" role="alert">
    <?= session()->getFlashdata('success') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
<div class="alert alert-danger alert-dismissible fade show py-2" role="alert">
    <?= session()->getFlashdata('error') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (empty($items)): ?>
<div class="card"><div class="card-body text-center py-5 text-muted">
    <i class="bi bi-receipt display-4 d-block mb-2 opacity-25"></i>
    <p>Belum ada other cost untuk event ini.</p>
</div></div>
<?php else: ?>

<!-- Summary strip -->
<div class="card mb-3">
    <div class="card-body py-3 d-flex align-items-center gap-3">
        <div class="rounded-2 p-2 bg-warning-subtle"><i class="bi bi-wallet2 text-warning fs-5"></i></div>
        <div>
            <div class="text-muted small">Total Other Cost</div>
            <div class="fw-bold fs-5">Rp <?= number_format($total, 0, ',', '.') ?></div>
        </div>
        <div class="ms-auto text-muted small"><?= count($items) ?> item</div>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">Dept</th>
                    <th>Nama / Kategori</th>
                    <th>Keterangan</th>
                    <th class="text-end">Jumlah</th>
                    <?php if ($canEdit): ?><th></th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $it): ?>
            <tr>
                <td class="ps-3">
                    <span class="badge bg-secondary-subtle text-secondary-emphasis"><?= esc($it['dept_name'] ?? '-') ?></span>
                </td>
                <td class="fw-semibold"><?= esc($it['kategori']) ?></td>
                <td class="text-muted small"><?= esc($it['keterangan'] ?? '') ?></td>
                <td class="text-end fw-semibold">Rp <?= number_format((int)$it['jumlah'], 0, ',', '.') ?></td>
                <?php if ($canEdit): ?>
                <td class="text-end pe-3">
                    <button class="btn btn-sm btn-outline-secondary"
                        data-bs-toggle="modal" data-bs-target="#editModal"
                        data-id="<?= $it['id'] ?>"
                        data-dept="<?= $it['department_id'] ?>"
                        data-kategori="<?= esc($it['kategori'], 'attr') ?>"
                        data-keterangan="<?= esc($it['keterangan'] ?? '', 'attr') ?>"
                        data-jumlah="<?= (int)$it['jumlah'] ?>">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger"
                        data-bs-toggle="modal" data-bs-target="#deleteModal"
                        data-id="<?= $it['id'] ?>"
                        data-label="<?= esc($it['kategori'], 'attr') ?>">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot class="table-light">
                <tr>
                    <td colspan="3" class="ps-3 fw-semibold text-end">Total</td>
                    <td class="text-end fw-bold">Rp <?= number_format($total, 0, ',', '.') ?></td>
                    <?php if ($canEdit): ?><td></td><?php endif; ?>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if ($canEdit): ?>
<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" action="<?= base_url('events/'.$event['id'].'/other-cost/add') ?>">
            <?= csrf_field() ?>
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title fw-semibold">Tambah Other Cost</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label form-label-sm fw-semibold">Departemen <span class="text-danger">*</span></label>
                        <select name="department_id" class="form-select form-select-sm" required>
                            <option value="">-- Pilih Departemen --</option>
                            <?php foreach ($departments as $d): ?>
                            <option value="<?= $d['id'] ?>"><?= esc($d['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label form-label-sm fw-semibold">Nama / Kategori <span class="text-danger">*</span></label>
                        <input type="text" name="kategori" class="form-control form-control-sm" required placeholder="cth. Biaya Transportasi">
                    </div>
                    <div class="mb-3">
                        <label class="form-label form-label-sm fw-semibold">Keterangan</label>
                        <input type="text" name="keterangan" class="form-control form-control-sm" placeholder="Opsional">
                    </div>
                    <div class="mb-3">
                        <label class="form-label form-label-sm fw-semibold">Jumlah (Rp) <span class="text-danger">*</span></label>
                        <input type="number" name="jumlah" class="form-control form-control-sm" required min="0" placeholder="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" id="editForm" action="">
            <?= csrf_field() ?>
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title fw-semibold">Edit Other Cost</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label form-label-sm fw-semibold">Departemen <span class="text-danger">*</span></label>
                        <select name="department_id" id="editDept" class="form-select form-select-sm" required>
                            <option value="">-- Pilih Departemen --</option>
                            <?php foreach ($departments as $d): ?>
                            <option value="<?= $d['id'] ?>"><?= esc($d['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label form-label-sm fw-semibold">Nama / Kategori <span class="text-danger">*</span></label>
                        <input type="text" name="kategori" id="editKategori" class="form-control form-control-sm" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label form-label-sm fw-semibold">Keterangan</label>
                        <input type="text" name="keterangan" id="editKeterangan" class="form-control form-control-sm">
                    </div>
                    <div class="mb-3">
                        <label class="form-label form-label-sm fw-semibold">Jumlah (Rp) <span class="text-danger">*</span></label>
                        <input type="number" name="jumlah" id="editJumlah" class="form-control form-control-sm" required min="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <form method="post" id="deleteForm" action="">
            <?= csrf_field() ?>
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title fw-semibold text-danger">Hapus Item</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Hapus <strong id="deleteLabel"></strong>?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<?php if ($canEdit): ?>
<script>
document.querySelectorAll('[data-bs-target="#editModal"]').forEach(btn => {
    btn.addEventListener('click', () => {
        const id = btn.dataset.id;
        document.getElementById('editForm').action = `<?= base_url('events/'.$event['id'].'/other-cost/') ?>${id}/edit`;
        document.getElementById('editDept').value      = btn.dataset.dept;
        document.getElementById('editKategori').value  = btn.dataset.kategori;
        document.getElementById('editKeterangan').value= btn.dataset.keterangan;
        document.getElementById('editJumlah').value    = btn.dataset.jumlah;
    });
});
document.querySelectorAll('[data-bs-target="#deleteModal"]').forEach(btn => {
    btn.addEventListener('click', () => {
        const id = btn.dataset.id;
        document.getElementById('deleteForm').action = `<?= base_url('events/'.$event['id'].'/other-cost/') ?>${id}/delete`;
        document.getElementById('deleteLabel').textContent = btn.dataset.label;
    });
});
</script>
<?php endif; ?>
<?= $this->endSection() ?>
