<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<!-- Header -->
<div class="d-flex align-items-center gap-2 mb-4">
    <div class="rounded-2 d-flex align-items-center justify-content-center flex-shrink-0"
         style="width:36px;height:36px;background:rgba(34,197,94,.15)">
        <i class="bi bi-shop-window" style="color:var(--bs-success);font-size:1rem"></i>
    </div>
    <div>
        <h4 class="fw-bold mb-0">Master Tenant</h4>
        <small class="text-muted">Data tenant mitra program loyalty</small>
    </div>
    <div class="ms-auto d-flex gap-2">
        <a href="<?= base_url('loyalty') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
        <?php if ($canEdit): ?>
        <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addTenantModal">
            <i class="bi bi-plus-lg me-1"></i>Tambah Tenant
        </button>
        <?php endif; ?>
    </div>
</div>

<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success alert-dismissible fade show py-2">
    <?= session()->getFlashdata('success') ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
<div class="alert alert-danger alert-dismissible fade show py-2">
    <?= session()->getFlashdata('error') ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (empty($tenants)): ?>
<div class="card"><div class="card-body text-center py-5 text-muted">
    <i class="bi bi-shop display-4 d-block mb-2 opacity-25"></i>
    <p class="mb-0">Belum ada data tenant. Klik "Tambah Tenant" untuk memulai.</p>
</div></div>
<?php else: ?>
<div class="card">
    <div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr>
                <th class="ps-3">Nama Tenant</th>
                <th>Kategori</th>
                <th>Lantai / Unit</th>
                <th>Contact Person</th>
                <th class="text-center">Program</th>
                <th class="text-center">Aktif</th>
                <th class="text-center">Status</th>
                <?php if ($canEdit): ?><th style="width:100px"></th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($tenants as $t): ?>
        <tr>
            <td class="ps-3 fw-semibold">
                <a href="<?= base_url('loyalty/tenants/' . $t['id']) ?>" class="text-decoration-none">
                    <?= esc($t['nama']) ?>
                </a>
            </td>
            <td class="small text-muted"><?= esc($t['kategori'] ?? '—') ?></td>
            <td class="small text-muted">
                <?= $t['lantai'] ? 'Lt. ' . esc($t['lantai']) : '' ?>
                <?= ($t['lantai'] && $t['nomor_unit']) ? ' · ' : '' ?>
                <?= $t['nomor_unit'] ? esc($t['nomor_unit']) : '' ?>
                <?= (!$t['lantai'] && !$t['nomor_unit']) ? '—' : '' ?>
            </td>
            <td class="small">
                <?= $t['contact_person'] ? esc($t['contact_person']) : '' ?>
                <?= $t['no_hp'] ? '<br><span class="text-muted">' . esc($t['no_hp']) . '</span>' : '' ?>
                <?= (!$t['contact_person'] && !$t['no_hp']) ? '<span class="text-muted">—</span>' : '' ?>
            </td>
            <td class="text-center">
                <span class="fw-semibold"><?= (int)$t['program_count'] ?></span>
            </td>
            <td class="text-center">
                <?php if ((int)$t['program_aktif'] > 0): ?>
                <span class="badge bg-success"><?= (int)$t['program_aktif'] ?></span>
                <?php else: ?>
                <span class="text-muted small">0</span>
                <?php endif; ?>
            </td>
            <td class="text-center">
                <span class="badge <?= $t['status'] === 'active' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' ?>">
                    <?= $t['status'] === 'active' ? 'Aktif' : 'Non-aktif' ?>
                </span>
            </td>
            <?php if ($canEdit): ?>
            <td>
                <div class="d-flex gap-1 justify-content-end pe-2">
                    <a href="<?= base_url('loyalty/tenants/' . $t['id']) ?>" class="btn btn-xs btn-outline-primary" style="padding:.2rem .5rem;font-size:.75rem" title="Lihat historis">
                        <i class="bi bi-clock-history"></i>
                    </a>
                    <button class="btn btn-xs btn-outline-secondary edit-tenant-btn"
                            style="padding:.2rem .5rem;font-size:.75rem"
                            data-id="<?= $t['id'] ?>"
                            data-nama="<?= esc($t['nama'], 'attr') ?>"
                            data-kategori="<?= esc($t['kategori'] ?? '', 'attr') ?>"
                            data-lantai="<?= esc($t['lantai'] ?? '', 'attr') ?>"
                            data-nomor-unit="<?= esc($t['nomor_unit'] ?? '', 'attr') ?>"
                            data-contact="<?= esc($t['contact_person'] ?? '', 'attr') ?>"
                            data-no-hp="<?= esc($t['no_hp'] ?? '', 'attr') ?>"
                            data-email="<?= esc($t['email'] ?? '', 'attr') ?>"
                            data-catatan="<?= esc($t['catatan'] ?? '', 'attr') ?>">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <?php if ((int)$t['program_count'] === 0): ?>
                    <form method="POST" action="<?= base_url('loyalty/tenants/' . $t['id'] . '/delete') ?>"
                          onsubmit="return confirm('Hapus tenant <?= esc($t['nama'], 'js') ?>?')">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-xs btn-outline-danger" style="padding:.2rem .5rem;font-size:.75rem">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                    <?php else: ?>
                    <button class="btn btn-xs btn-outline-secondary" style="padding:.2rem .5rem;font-size:.75rem" disabled title="Tidak bisa dihapus — ada program terkait">
                        <i class="bi bi-trash"></i>
                    </button>
                    <?php endif; ?>
                </div>
            </td>
            <?php endif; ?>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<?php endif; ?>

<?php if ($canEdit): ?>
<!-- Add Modal -->
<div class="modal fade" id="addTenantModal" tabindex="-1">
<div class="modal-dialog"><div class="modal-content">
<form method="POST" action="<?= base_url('loyalty/tenants/add') ?>">
<?= csrf_field() ?>
<div class="modal-header">
    <h5 class="modal-title fw-semibold">Tambah Tenant</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
    <div class="row g-3">
        <div class="col-12">
            <label class="form-label small fw-semibold">Nama Tenant <span class="text-danger">*</span></label>
            <input type="text" name="nama" class="form-control" required placeholder="Contoh: H&M, Starbucks, Cinema XXI">
        </div>
        <div class="col-sm-6">
            <label class="form-label small fw-semibold">Kategori</label>
            <input type="text" name="kategori" class="form-control" placeholder="Contoh: Fashion, F&B, Hiburan">
        </div>
        <div class="col-sm-3">
            <label class="form-label small fw-semibold">Lantai</label>
            <input type="text" name="lantai" class="form-control" placeholder="1, 2, B1…">
        </div>
        <div class="col-sm-3">
            <label class="form-label small fw-semibold">Nomor Unit</label>
            <input type="text" name="nomor_unit" class="form-control" placeholder="A1, B12…">
        </div>
        <div class="col-sm-6">
            <label class="form-label small fw-semibold">Contact Person</label>
            <input type="text" name="contact_person" class="form-control" placeholder="Nama PIC">
        </div>
        <div class="col-sm-6">
            <label class="form-label small fw-semibold">No. HP</label>
            <input type="text" name="no_hp" class="form-control" placeholder="08xx…">
        </div>
        <div class="col-12">
            <label class="form-label small fw-semibold">Email</label>
            <input type="email" name="email" class="form-control" placeholder="Opsional">
        </div>
        <div class="col-12">
            <label class="form-label small fw-semibold">Catatan</label>
            <input type="text" name="catatan" class="form-control" placeholder="Opsional">
        </div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
    <button type="submit" class="btn btn-success">Tambah</button>
</div>
</form>
</div></div></div>

<!-- Edit Modal -->
<div class="modal fade" id="editTenantModal" tabindex="-1">
<div class="modal-dialog"><div class="modal-content">
<form id="editTenantForm" method="POST">
<?= csrf_field() ?>
<div class="modal-header">
    <h5 class="modal-title fw-semibold">Edit Tenant</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
    <div class="row g-3">
        <div class="col-12">
            <label class="form-label small fw-semibold">Nama Tenant <span class="text-danger">*</span></label>
            <input type="text" name="nama" id="editTenantNama" class="form-control" required>
        </div>
        <div class="col-sm-6">
            <label class="form-label small fw-semibold">Kategori</label>
            <input type="text" name="kategori" id="editTenantKategori" class="form-control">
        </div>
        <div class="col-sm-3">
            <label class="form-label small fw-semibold">Lantai</label>
            <input type="text" name="lantai" id="editTenantLantai" class="form-control">
        </div>
        <div class="col-sm-3">
            <label class="form-label small fw-semibold">Nomor Unit</label>
            <input type="text" name="nomor_unit" id="editTenantNomorUnit" class="form-control">
        </div>
        <div class="col-sm-6">
            <label class="form-label small fw-semibold">Contact Person</label>
            <input type="text" name="contact_person" id="editTenantContact" class="form-control">
        </div>
        <div class="col-sm-6">
            <label class="form-label small fw-semibold">No. HP</label>
            <input type="text" name="no_hp" id="editTenantNoHp" class="form-control">
        </div>
        <div class="col-12">
            <label class="form-label small fw-semibold">Email</label>
            <input type="email" name="email" id="editTenantEmail" class="form-control">
        </div>
        <div class="col-12">
            <label class="form-label small fw-semibold">Catatan</label>
            <input type="text" name="catatan" id="editTenantCatatan" class="form-control">
        </div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
    <button type="submit" class="btn btn-primary">Simpan</button>
</div>
</form>
</div></div></div>
<?php endif; ?>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
<?php if ($canEdit): ?>
document.querySelectorAll('.edit-tenant-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('editTenantForm').action = '<?= base_url('loyalty/tenants/') ?>' + this.dataset.id + '/edit';
        document.getElementById('editTenantNama').value       = this.dataset.nama;
        document.getElementById('editTenantKategori').value   = this.dataset.kategori;
        document.getElementById('editTenantLantai').value     = this.dataset.lantai;
        document.getElementById('editTenantNomorUnit').value  = this.dataset.nomorUnit;
        document.getElementById('editTenantContact').value    = this.dataset.contact;
        document.getElementById('editTenantNoHp').value       = this.dataset.noHp;
        document.getElementById('editTenantEmail').value      = this.dataset.email;
        document.getElementById('editTenantCatatan').value    = this.dataset.catatan;
        new bootstrap.Modal(document.getElementById('editTenantModal')).show();
    });
});
<?php endif; ?>
</script>
<?= $this->endSection() ?>
