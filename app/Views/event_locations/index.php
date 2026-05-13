<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<style>
.card { overflow: hidden; }
.list-group-item { transition: background .15s; }
</style>
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4 fade-up" style="animation-delay:.05s">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-geo-alt me-2"></i>Master Lokasi Event</h4>
        <small class="text-muted">Lokasi yang tersedia saat membuat event</small>
    </div>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
        <i class="bi bi-plus-lg me-1"></i> Tambah Lokasi
    </button>
</div>

<div class="row g-3">

<?php $mi = 0; foreach (['ewalk' => 'eWalk', 'pentacity' => 'Pentacity'] as $mallKey => $mallLabel): ?>
<div class="col-lg-6 fade-up" style="animation-delay:<?= .15 + $mi++ * .1 ?>s">
<div class="card">
<div class="card-header d-flex justify-content-between align-items-center">
    <h6 class="mb-0 fw-semibold">
        <i class="bi bi-building me-2 text-<?= $mallKey === 'ewalk' ? 'primary' : 'success' ?>"></i><?= $mallLabel ?>
    </h6>
    <span class="badge bg-secondary-subtle text-secondary"><?= count($grouped[$mallKey]) ?> lokasi</span>
</div>
<div class="card-body p-0">
<?php if (empty($grouped[$mallKey])): ?>
<div class="text-center py-4 text-muted small">Belum ada lokasi untuk <?= $mallLabel ?>.</div>
<?php else: ?>
<div class="list-group list-group-flush">
<?php foreach ($grouped[$mallKey] as $li => $loc): ?>
<div class="list-group-item d-flex justify-content-between align-items-center py-2 fade-up"
     style="animation-delay:<?= (.15 + $mi * .1) + $li * .05 ?>s">
    <div class="d-flex align-items-center gap-2">
        <i class="bi bi-geo-alt text-muted" style="font-size:.8rem"></i>
        <span class="fw-medium small"><?= esc($loc['nama']) ?></span>
        <?php if (! $loc['aktif']): ?>
        <span class="badge bg-secondary-subtle text-secondary" style="font-size:.65rem">Nonaktif</span>
        <?php endif; ?>
    </div>
    <div class="d-flex gap-1">
        <button class="btn btn-xs btn-outline-secondary edit-btn" style="padding:.2rem .5rem;font-size:.75rem"
            data-id="<?= $loc['id'] ?>"
            data-nama="<?= esc($loc['nama']) ?>"
            data-mall="<?= $loc['mall'] ?>"
            data-aktif="<?= $loc['aktif'] ?>">
            <i class="bi bi-pencil"></i>
        </button>
        <a href="<?= base_url('event-locations/'.$loc['id'].'/delete') ?>"
           class="btn btn-xs btn-outline-danger" style="padding:.2rem .5rem;font-size:.75rem"
           onclick="return confirm('Hapus lokasi ini?')">
            <i class="bi bi-trash"></i>
        </a>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>
</div>
</div>
<?php endforeach; ?>

</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<form method="POST" action="<?= base_url('event-locations/add') ?>">
<?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title fw-semibold">Tambah Lokasi</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="mb-3">
        <label class="form-label small fw-semibold">Mall <span class="text-danger">*</span></label>
        <select name="mall" class="form-select">
            <option value="ewalk">eWalk</option>
            <option value="pentacity">Pentacity</option>
        </select>
    </div>
    <div class="mb-0">
        <label class="form-label small fw-semibold">Nama Lokasi <span class="text-danger">*</span></label>
        <input type="text" name="nama" class="form-control" placeholder="Contoh: GF Atrium, LG Food Court..." required>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
    <button type="submit" class="btn btn-primary">Tambah</button>
</div>
</form>
</div></div></div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<form id="editForm" method="POST">
<?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title fw-semibold">Edit Lokasi</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="mb-3">
        <label class="form-label small fw-semibold">Mall</label>
        <select name="mall" id="eMall" class="form-select">
            <option value="ewalk">eWalk</option>
            <option value="pentacity">Pentacity</option>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label small fw-semibold">Nama Lokasi</label>
        <input type="text" name="nama" id="eNama" class="form-control" required>
    </div>
    <div class="form-check">
        <input class="form-check-input" type="checkbox" name="aktif" id="eAktif" value="1">
        <label class="form-check-label small" for="eAktif">Aktif (tampil saat pembuatan event)</label>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
    <button type="submit" class="btn btn-primary">Simpan</button>
</div>
</form>
</div></div></div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        document.getElementById('editForm').action = '<?= base_url('event-locations/') ?>' + this.dataset.id + '/edit';
        document.getElementById('eMall').value    = this.dataset.mall;
        document.getElementById('eNama').value    = this.dataset.nama;
        document.getElementById('eAktif').checked = this.dataset.aktif === '1';
        new bootstrap.Modal(document.getElementById('editModal')).show();
    });
});
</script>
<?= $this->endSection() ?>
