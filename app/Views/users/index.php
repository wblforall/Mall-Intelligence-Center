<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0"><i class="bi bi-people me-2"></i>User Management</h4>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
        <i class="bi bi-plus-lg me-1"></i> Tambah User
    </button>
</div>

<?php
$deptMap = [];
foreach ($depts as $d) { $deptMap[$d['id']] = $d['name']; }
$roleMap = [];
foreach ($roles as $r) { $roleMap[$r['id']] = $r; }
?>

<div class="card">
    <div class="card-body p-0">
    <div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead><tr><th>#</th><th>Nama</th><th>Email</th><th>Role</th><th>Departemen</th><th>Status</th><th>Aksi</th></tr></thead>
        <tbody>
        <?php foreach ($users as $i => $u): ?>
        <?php
            $roleRow = $u['role_id'] ? ($roleMap[$u['role_id']] ?? null) : null;
            $roleName = $roleRow ? $roleRow['name'] : ucfirst($u['role']);
            $roleColor = $roleRow && $roleRow['is_admin'] ? 'danger' : (in_array($u['role'], ['manager']) ? 'primary' : 'secondary');
        ?>
        <tr>
            <td class="text-muted small"><?= $i+1 ?></td>
            <td class="fw-medium"><?= esc($u['name']) ?></td>
            <td><?= esc($u['email']) ?></td>
            <td><span class="badge bg-<?= $roleColor ?>-subtle text-<?= $roleColor ?>"><?= esc($roleName) ?></span></td>
            <td>
                <?php if ($u['department_id'] && isset($deptMap[$u['department_id']])): ?>
                <span class="badge bg-info-subtle text-info"><?= esc($deptMap[$u['department_id']]) ?></span>
                <?php else: ?>
                <span class="text-muted small">—</span>
                <?php endif; ?>
            </td>
            <td>
                <?= $u['is_active']
                    ? '<span class="badge bg-success-subtle text-success">Aktif</span>'
                    : '<span class="badge bg-danger-subtle text-danger">Nonaktif</span>' ?>
            </td>
            <td>
                <button class="btn btn-sm btn-outline-secondary edit-user-btn me-1"
                    data-id="<?= $u['id'] ?>"
                    data-name="<?= esc($u['name']) ?>"
                    data-role_id="<?= $u['role_id'] ?? '' ?>"
                    data-dept="<?= $u['department_id'] ?? '' ?>">
                    <i class="bi bi-pencil"></i>
                </button>
                <a href="<?= base_url('users/'.$u['id'].'/toggle') ?>" class="btn btn-sm btn-outline-warning me-1"
                   title="<?= $u['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?>">
                    <i class="bi bi-<?= $u['is_active'] ? 'pause' : 'play' ?>-fill"></i>
                </a>
                <?php if ($u['id'] !== $user['id']): ?>
                <a href="<?= base_url('users/'.$u['id'].'/delete') ?>" class="btn btn-sm btn-outline-danger"
                   onclick="return confirm('Hapus user ini?')">
                    <i class="bi bi-trash"></i>
                </a>
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
<div class="modal fade" id="addUserModal" tabindex="-1">
<div class="modal-dialog">
<div class="modal-content">
<form method="POST" action="<?= base_url('users/add') ?>">
<?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title fw-semibold">Tambah User</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="mb-3"><label class="form-label small fw-semibold">Nama</label><input type="text" name="name" class="form-control" required></div>
    <div class="mb-3"><label class="form-label small fw-semibold">Email</label><input type="email" name="email" class="form-control" required></div>
    <div class="mb-3"><label class="form-label small fw-semibold">Password</label><input type="password" name="password" class="form-control" required minlength="6"></div>
    <div class="mb-3">
        <label class="form-label small fw-semibold">Role <span class="text-danger">*</span></label>
        <select name="role_id" class="form-select">
            <?php foreach ($roles as $r): ?>
            <option value="<?= $r['id'] ?>"><?= esc($r['name']) ?> <?= $r['is_admin'] ? '(Admin)' : '' ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label small fw-semibold">Departemen</label>
        <select name="department_id" class="form-select">
            <option value="">— Tanpa Departemen —</option>
            <?php foreach ($depts as $d): ?>
            <option value="<?= $d['id'] ?>"><?= esc($d['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <small class="text-muted">Admin tidak perlu departemen.</small>
    </div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Tambah</button></div>
</form>
</div></div></div>

<!-- Edit Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
<div class="modal-dialog">
<div class="modal-content">
<form id="editUserForm" method="POST">
<?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title fw-semibold">Edit User</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="mb-3"><label class="form-label small fw-semibold">Nama</label><input type="text" name="name" id="editUserName" class="form-control" required></div>
    <div class="mb-3"><label class="form-label small fw-semibold">Password Baru <span class="text-muted fw-normal">(kosongkan jika tidak ganti)</span></label><input type="password" name="password" class="form-control" minlength="6"></div>
    <div class="mb-3">
        <label class="form-label small fw-semibold">Role</label>
        <select name="role_id" id="editUserRole" class="form-select">
            <?php foreach ($roles as $r): ?>
            <option value="<?= $r['id'] ?>"><?= esc($r['name']) ?> <?= $r['is_admin'] ? '(Admin)' : '' ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label small fw-semibold">Departemen</label>
        <select name="department_id" id="editUserDept" class="form-select">
            <option value="">— Tanpa Departemen —</option>
            <?php foreach ($depts as $d): ?>
            <option value="<?= $d['id'] ?>"><?= esc($d['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
</form>
</div></div></div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
document.querySelectorAll('.edit-user-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('editUserForm').action = '<?= base_url('users/') ?>' + this.dataset.id + '/edit';
        document.getElementById('editUserName').value  = this.dataset.name;
        document.getElementById('editUserRole').value  = this.dataset.role_id || '';
        document.getElementById('editUserDept').value  = this.dataset.dept || '';
        new bootstrap.Modal(document.getElementById('editUserModal')).show();
    });
});
</script>
<?= $this->endSection() ?>
