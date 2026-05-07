<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center gap-2">
        <a href="<?= base_url('events/'.$event['id'].'/dashboard') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
        <div>
            <h4 class="fw-bold mb-0">Manajemen Tenant</h4>
            <small class="text-muted"><?= esc($event['name']) ?></small>
        </div>
    </div>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addTenantModal">
        <i class="bi bi-plus-lg me-1"></i> Tambah Tenant
    </button>
</div>

<div class="card mb-3">
    <div class="card-body p-0">
        <?php if (empty($tenants)): ?>
        <div class="p-4 text-center text-muted">
            <i class="bi bi-shop display-4 d-block mb-2"></i>
            Belum ada tenant. Tambahkan tenant yang berpartisipasi dalam event.
        </div>
        <?php else: ?>
        <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr>
                <th>Nama Tenant</th><th>Kategori</th><th>Promo</th>
                <th>Baseline Sales</th><th>Relevansi</th><th>Aksi</th>
            </tr></thead>
            <tbody>
            <?php foreach ($tenants as $t): ?>
            <tr>
                <td class="fw-medium"><?= esc($t['name']) ?></td>
                <td><?= esc($t['category']) ?></td>
                <td><?= $t['participating_promo'] ? '<span class="badge bg-success-subtle text-success">Ya</span>' : '<span class="badge bg-secondary-subtle text-secondary">Tidak</span>' ?></td>
                <td>Rp <?= number_format((int)$t['baseline_sales'], 0, ',', '.') ?></td>
                <td>
                    <?php $rc = ['High'=>'danger','Medium'=>'warning','Low'=>'secondary'][$t['event_relevance']] ?? 'secondary' ?>
                    <span class="badge bg-<?= $rc ?>-subtle text-<?= $rc ?>"><?= $t['event_relevance'] ?></span>
                </td>
                <td>
                    <button class="btn btn-sm btn-outline-secondary edit-tenant-btn"
                        data-id="<?= $t['id'] ?>"
                        data-name="<?= esc($t['name']) ?>"
                        data-category="<?= esc($t['category']) ?>"
                        data-promo="<?= $t['participating_promo'] ?>"
                        data-sales="<?= $t['baseline_sales'] ?>"
                        data-relevance="<?= $t['event_relevance'] ?>">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <a href="<?= base_url('events/'.$event['id'].'/tenants/'.$t['id'].'/delete') ?>"
                       class="btn btn-sm btn-outline-danger"
                       onclick="return confirm('Hapus tenant ini?')">
                        <i class="bi bi-trash"></i>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="text-end">
    <a href="<?= base_url('events/'.$event['id'].'/tenants/impact') ?>" class="btn btn-outline-primary">
        <i class="bi bi-bar-chart-line me-1"></i> Input Tenant Impact
    </a>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addTenantModal" tabindex="-1">
<div class="modal-dialog">
<div class="modal-content">
<form method="POST" action="<?= base_url('events/'.$event['id'].'/tenants/add') ?>">
<?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title fw-semibold">Tambah Tenant</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="mb-3"><label class="form-label small fw-semibold">Nama Tenant</label><input type="text" name="name" class="form-control" required></div>
    <div class="mb-3"><label class="form-label small fw-semibold">Kategori</label><input type="text" name="category" class="form-control" placeholder="F&B, Kids & Toys, Fashion, dsb." required></div>
    <div class="row">
        <div class="col-6 mb-3">
            <label class="form-label small fw-semibold">Baseline Sales (Rp)</label>
            <input type="text" name="baseline_sales" class="form-control" value="0">
        </div>
        <div class="col-6 mb-3">
            <label class="form-label small fw-semibold">Event Relevance</label>
            <select name="event_relevance" class="form-select">
                <option value="High">High</option>
                <option value="Medium" selected>Medium</option>
                <option value="Low">Low</option>
            </select>
        </div>
    </div>
    <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" name="participating_promo" value="1" id="promoCheck" checked>
        <label class="form-check-label" for="promoCheck">Berpartisipasi dalam promo</label>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
    <button type="submit" class="btn btn-primary">Tambah</button>
</div>
</form>
</div></div></div>

<!-- Edit Modal -->
<div class="modal fade" id="editTenantModal" tabindex="-1">
<div class="modal-dialog">
<div class="modal-content">
<form id="editTenantForm" method="POST">
<?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title fw-semibold">Edit Tenant</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="mb-3"><label class="form-label small fw-semibold">Nama Tenant</label><input type="text" name="name" id="editName" class="form-control" required></div>
    <div class="mb-3"><label class="form-label small fw-semibold">Kategori</label><input type="text" name="category" id="editCategory" class="form-control" required></div>
    <div class="row">
        <div class="col-6 mb-3"><label class="form-label small fw-semibold">Baseline Sales (Rp)</label><input type="text" name="baseline_sales" id="editSales" class="form-control"></div>
        <div class="col-6 mb-3">
            <label class="form-label small fw-semibold">Event Relevance</label>
            <select name="event_relevance" id="editRelevance" class="form-select">
                <option value="High">High</option><option value="Medium">Medium</option><option value="Low">Low</option>
            </select>
        </div>
    </div>
    <div class="form-check"><input class="form-check-input" type="checkbox" name="participating_promo" value="1" id="editPromo"><label class="form-check-label" for="editPromo">Berpartisipasi promo</label></div>
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
document.querySelectorAll('.edit-tenant-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const id = this.dataset.id;
        document.getElementById('editTenantForm').action = '<?= base_url('events/'.$event['id'].'/tenants/') ?>' + id + '/edit';
        document.getElementById('editName').value = this.dataset.name;
        document.getElementById('editCategory').value = this.dataset.category;
        document.getElementById('editSales').value = parseInt(this.dataset.sales).toLocaleString('id-ID');
        document.getElementById('editPromo').checked = this.dataset.promo == '1';
        document.getElementById('editRelevance').value = this.dataset.relevance;
        new bootstrap.Modal(document.getElementById('editTenantModal')).show();
    });
});
</script>
<?= $this->endSection() ?>
