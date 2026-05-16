<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4 fade-up" style="animation-delay:.05s">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-shield-check me-2"></i>Role Management</h4>
        <small class="text-muted">Kelola role dan permission akses sistem</small>
    </div>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
        <i class="bi bi-plus-lg me-1"></i> Tambah Role
    </button>
</div>

<?php
$globalPerms = [
    'is_admin'           => ['label' => 'Full Admin',    'icon' => 'shield-fill',   'color' => 'danger'],
    'can_create_event'   => ['label' => 'Buat Event',    'icon' => 'calendar-plus', 'color' => 'primary'],
    'can_delete_event'   => ['label' => 'Hapus Event',   'icon' => 'calendar-x',    'color' => 'warning'],
    'can_manage_users'   => ['label' => 'Kelola User',   'icon' => 'people-fill',   'color' => 'info'],
    'can_delete_traffic' => ['label' => 'Hapus Traffic',  'icon' => 'trash',                    'color' => 'danger'],
    'can_import_traffic' => ['label' => 'Import Excel',   'icon' => 'file-earmark-arrow-up',    'color' => 'success'],
    'can_view_logs'      => ['label' => 'Lihat Log',      'icon' => 'journal-text',             'color' => 'info'],
    'can_approve_events' => ['label' => 'Approve Event',  'icon' => 'patch-check',              'color' => 'success'],
    'can_approve_pip'    => ['label' => 'Approve PIP',    'icon' => 'person-check-fill',  'color' => 'primary'],
    'can_view_gantt'     => ['label' => 'Lihat Gantt',   'icon' => 'bar-chart-steps',    'color' => 'info'],
];
?>

<div class="card fade-up" style="animation-delay:.15s">
<div class="card-body p-0">
<div class="table-responsive">
<table class="table table-hover mb-0 align-middle">
<thead>
<tr>
    <th>Role</th>
    <th>Slug</th>
    <th>Deskripsi</th>
    <?php foreach ($globalPerms as $key => $p): ?>
    <th class="text-center" style="width:80px">
        <i class="bi bi-<?= $p['icon'] ?> text-<?= $p['color'] ?>"></i>
        <div style="font-size:.62rem"><?= $p['label'] ?></div>
    </th>
    <?php endforeach; ?>
    <th class="text-center" style="width:60px">Users</th>
    <th style="width:80px"></th>
</tr>
</thead>
<tbody>
<?php foreach ($roles as $i => $r): ?>
<tr class="fade-up" style="animation-delay:<?= .2 + $i * .05 ?>s">
    <td class="fw-semibold"><?= esc($r['name']) ?></td>
    <td><code class="small"><?= esc($r['slug']) ?></code></td>
    <td class="small text-muted"><?= esc($r['description']) ?></td>
    <?php foreach (array_keys($globalPerms) as $key): ?>
    <td class="text-center">
        <?php if ($r[$key]): ?>
        <i class="bi bi-check-circle-fill text-success"></i>
        <?php else: ?>
        <i class="bi bi-dash-circle text-muted opacity-25"></i>
        <?php endif; ?>
    </td>
    <?php endforeach; ?>
    <td class="text-center">
        <span class="badge bg-secondary-subtle text-secondary"><?= $r['user_count'] ?></span>
    </td>
    <td>
        <button class="btn btn-sm btn-outline-secondary edit-btn me-1"
            data-id="<?= $r['id'] ?>"
            data-name="<?= esc($r['name']) ?>"
            data-slug="<?= esc($r['slug']) ?>"
            data-description="<?= esc($r['description']) ?>"
            data-is_admin="<?= $r['is_admin'] ?>"
            data-can_create_event="<?= $r['can_create_event'] ?>"
            data-can_delete_event="<?= $r['can_delete_event'] ?>"
            data-can_manage_users="<?= $r['can_manage_users'] ?>"
            data-can_delete_traffic="<?= $r['can_delete_traffic'] ?>"
            data-can_import_traffic="<?= $r['can_import_traffic'] ?>"
            data-can_view_logs="<?= $r['can_view_logs'] ?>"
            data-can_approve_events="<?= $r['can_approve_events'] ?? 0 ?>"
            data-can_approve_pip="<?= $r['can_approve_pip'] ?? 0 ?>"
            data-can_view_gantt="<?= $r['can_view_gantt'] ?? 0 ?>">
            <i class="bi bi-pencil"></i>
        </button>
        <?php if ($r['user_count'] == 0): ?>
        <a href="<?= base_url('roles/'.$r['id'].'/delete') ?>"
           class="btn btn-sm btn-outline-danger"
           onclick="return confirm('Hapus role <?= esc($r['name']) ?>?')">
            <i class="bi bi-trash"></i>
        </a>
        <?php else: ?>
        <button class="btn btn-sm btn-outline-danger" disabled title="Role masih digunakan">
            <i class="bi bi-trash"></i>
        </button>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
</div>


<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
<form method="POST" action="<?= base_url('roles/add') ?>">
<?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title fw-semibold">Tambah Role</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="row">
        <div class="col-7 mb-3">
            <label class="form-label small fw-semibold">Nama Role <span class="text-danger">*</span></label>
            <input type="text" name="name" id="addName" class="form-control" required>
        </div>
        <div class="col-5 mb-3">
            <label class="form-label small fw-semibold">Slug <span class="text-danger">*</span></label>
            <input type="text" name="slug" id="addSlug" class="form-control" placeholder="auto">
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label small fw-semibold">Deskripsi</label>
        <input type="text" name="description" class="form-control">
    </div>

    <label class="form-label small fw-semibold">Akses Global</label>
    <div class="row g-2 mb-0">
        <?php foreach ($globalPerms as $key => $p): ?>
        <div class="col-6 col-md-3">
            <div class="form-check border rounded px-3 py-2">
                <input class="form-check-input" type="checkbox" name="<?= $key ?>" id="add_<?= $key ?>" value="1">
                <label class="form-check-label small" for="add_<?= $key ?>">
                    <i class="bi bi-<?= $p['icon'] ?> text-<?= $p['color'] ?> me-1"></i><?= $p['label'] ?>
                </label>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

</div>
<div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Tambah</button></div>
</form>
</div></div></div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
<form id="editForm" method="POST">
<?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title fw-semibold">Edit Role</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="row">
        <div class="col-7 mb-3">
            <label class="form-label small fw-semibold">Nama Role</label>
            <input type="text" name="name" id="eNama" class="form-control" required>
        </div>
        <div class="col-5 mb-3">
            <label class="form-label small fw-semibold">Slug</label>
            <input type="text" name="slug" id="eSlug" class="form-control">
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label small fw-semibold">Deskripsi</label>
        <input type="text" name="description" id="eDesc" class="form-control">
    </div>

    <label class="form-label small fw-semibold">Akses Global</label>
    <div class="row g-2 mb-0">
        <?php foreach ($globalPerms as $key => $p): ?>
        <div class="col-6 col-md-3">
            <div class="form-check border rounded px-3 py-2">
                <input class="form-check-input" type="checkbox" name="<?= $key ?>" id="edit_<?= $key ?>" value="1">
                <label class="form-check-label small" for="edit_<?= $key ?>">
                    <i class="bi bi-<?= $p['icon'] ?> text-<?= $p['color'] ?> me-1"></i><?= $p['label'] ?>
                </label>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

</div>
<div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
</form>
</div></div></div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
document.getElementById('addName').addEventListener('input', function() {
    const slug = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_|_$/g, '');
    document.getElementById('addSlug').value = slug;
});

document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('editForm').action = '<?= base_url('roles/') ?>' + this.dataset.id + '/edit';
        document.getElementById('eNama').value = this.dataset.name;
        document.getElementById('eSlug').value = this.dataset.slug;
        document.getElementById('eDesc').value  = this.dataset.description;

        // Global perms
        ['is_admin','can_create_event','can_delete_event','can_manage_users',
         'can_delete_traffic','can_import_traffic','can_view_logs','can_approve_events','can_approve_pip','can_view_gantt'].forEach(key => {
            const cb = document.getElementById('edit_' + key);
            if (cb) cb.checked = this.dataset[key] === '1';
        });

        new bootstrap.Modal(document.getElementById('editModal')).show();
    });
});
</script>
<?= $this->endSection() ?>
