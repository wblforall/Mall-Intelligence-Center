<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<style>
@keyframes fadeUp { from { opacity:0; transform:translateY(14px); } to { opacity:1; transform:none; } }
.anim-fade-up { animation: fadeUp .4s cubic-bezier(.22,.68,0,1.1) both; }
</style>

<div class="d-flex align-items-center justify-content-between mb-4 anim-fade-up" style="animation-delay:.05s">
    <div>
        <a href="<?= base_url('people/pip') ?>" class="text-muted small text-decoration-none">
            <i class="bi bi-arrow-left me-1"></i>Performance Improvement Plan
        </a>
        <h4 class="fw-bold mb-0 mt-1">Master Aspek PIP</h4>
        <div class="text-muted small">Daftar aspek perbaikan beserta target dan metrik default</div>
    </div>
    <?php if ($isAdmin): ?>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
        <i class="bi bi-plus-lg me-1"></i>Tambah Aspek
    </button>
    <?php endif; ?>
</div>

<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i><?= session()->getFlashdata('success') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<?php if (empty($grouped)): ?>
<div class="text-center py-5 text-muted">Belum ada aspek. Tambah aspek pertama.</div>
<?php else: ?>
<?php foreach ($grouped as $kategori => $items): ?>
<div class="card mb-3 anim-fade-up">
    <div class="card-header fw-semibold"><?= esc($kategori) ?></div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Aspek</th>
                    <th>Target Default</th>
                    <th>Metrik Default</th>
                    <th>Status</th>
                    <?php if ($isAdmin): ?><th></th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item): ?>
            <tr class="<?= ! $item['aktif'] ? 'text-muted' : '' ?>">
                <td class="fw-semibold"><?= esc($item['aspek']) ?></td>
                <td class="small"><?= esc($item['target_default'] ?? '—') ?></td>
                <td class="small"><?= esc($item['metrik_default'] ?? '—') ?></td>
                <td>
                    <?php if ($item['aktif']): ?>
                    <span class="badge bg-success">Aktif</span>
                    <?php else: ?>
                    <span class="badge bg-secondary">Nonaktif</span>
                    <?php endif; ?>
                </td>
                <?php if ($isAdmin): ?>
                <td class="text-end text-nowrap">
                    <button class="btn btn-sm btn-outline-secondary edit-btn"
                        data-id="<?= $item['id'] ?>"
                        data-aspek="<?= esc($item['aspek']) ?>"
                        data-kategori="<?= esc($item['kategori'] ?? '') ?>"
                        data-target="<?= esc($item['target_default'] ?? '') ?>"
                        data-metrik="<?= esc($item['metrik_default'] ?? '') ?>"
                        data-aktif="<?= $item['aktif'] ?>"
                        data-bs-toggle="modal" data-bs-target="#editModal">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <a href="<?= base_url('people/pip/aspek/' . $item['id'] . '/toggle') ?>"
                       class="btn btn-sm btn-outline-<?= $item['aktif'] ? 'warning' : 'success' ?>"
                       onclick="return confirm('Ubah status aspek ini?')">
                        <i class="bi bi-<?= $item['aktif'] ? 'pause' : 'play' ?>"></i>
                    </a>
                    <a href="<?= base_url('people/pip/aspek/' . $item['id'] . '/delete') ?>"
                       class="btn btn-sm btn-outline-danger"
                       onclick="return confirm('Hapus aspek ini?')">
                        <i class="bi bi-trash"></i>
                    </a>
                </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<!-- Modal Tambah -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" action="<?= base_url('people/pip/aspek/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Tambah Aspek PIP</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body row g-3">
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Aspek <span class="text-danger">*</span></label>
                        <input type="text" name="aspek" class="form-control" required placeholder="cth: Ketepatan Waktu Hadir">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Kategori</label>
                        <input type="text" name="kategori" class="form-control" list="kategoriList" placeholder="cth: Kehadiran">
                        <datalist id="kategoriList">
                            <option value="Kehadiran">
                            <option value="Kinerja">
                            <option value="Perilaku">
                            <option value="Kompetensi">
                        </datalist>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Target Default</label>
                        <textarea name="target_default" class="form-control" rows="2" placeholder="cth: Hadir tepat waktu min. 95% dalam sebulan"></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Metrik / Cara Ukur Default</label>
                        <input type="text" name="metrik_default" class="form-control" placeholder="cth: Rekap absensi harian">
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

<!-- Modal Edit -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" id="editForm" action="">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Edit Aspek PIP</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body row g-3">
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Aspek <span class="text-danger">*</span></label>
                        <input type="text" name="aspek" id="edit_aspek" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Kategori</label>
                        <input type="text" name="kategori" id="edit_kategori" class="form-control" list="kategoriList">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Target Default</label>
                        <textarea name="target_default" id="edit_target" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Metrik / Cara Ukur Default</label>
                        <input type="text" name="metrik_default" id="edit_metrik" class="form-control">
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input type="checkbox" name="aktif" id="edit_aktif" class="form-check-input" value="1">
                            <label class="form-check-label" for="edit_aktif">Aktif</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const id = this.dataset.id;
        document.getElementById('editForm').action = `<?= base_url('people/pip/aspek/') ?>${id}/update`;
        document.getElementById('edit_aspek').value    = this.dataset.aspek;
        document.getElementById('edit_kategori').value = this.dataset.kategori;
        document.getElementById('edit_target').value   = this.dataset.target;
        document.getElementById('edit_metrik').value   = this.dataset.metrik;
        document.getElementById('edit_aktif').checked  = this.dataset.aktif === '1';
    });
});
</script>

<?= $this->endSection() ?>
