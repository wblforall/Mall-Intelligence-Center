<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4 fade-up" style="animation-delay:.05s">
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

<div class="card mb-3 fade-up" style="animation-delay:.1s">
    <div class="card-body py-2">
    <div class="row g-2 align-items-end">
        <div class="col-md">
            <label class="form-label small fw-semibold mb-1 text-muted">Cari nama / email</label>
            <input type="text" id="fSearch" class="form-control form-control-sm" placeholder="Ketik nama atau email...">
        </div>
        <div class="col-6 col-md-auto">
            <label class="form-label small fw-semibold mb-1 text-muted">Role</label>
            <select id="fRole" class="form-select form-select-sm">
                <option value="">Semua</option>
                <?php foreach ($roles as $r): ?>
                <option value="<?= $r['id'] ?>"><?= esc($r['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-auto">
            <label class="form-label small fw-semibold mb-1 text-muted">Departemen</label>
            <select id="fDept" class="form-select form-select-sm">
                <option value="">Semua</option>
                <?php foreach ($depts as $d): ?>
                <option value="<?= $d['id'] ?>"><?= esc($d['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-auto">
            <label class="form-label small fw-semibold mb-1 text-muted">Status</label>
            <select id="fStatus" class="form-select form-select-sm">
                <option value="">Semua</option>
                <option value="1">Aktif</option>
                <option value="0">Nonaktif</option>
            </select>
        </div>
        <div class="col-6 col-md-auto">
            <button type="button" id="fReset" class="btn btn-sm btn-outline-secondary">Reset</button>
        </div>
    </div>
    </div>
</div>

<div class="card fade-up" style="animation-delay:.15s">
    <div class="card-body p-0">
    <div class="px-3 pt-2 small text-muted">Menampilkan <span id="fCount"><?= count($users) ?></span> dari <?= count($users) ?> user</div>
    <div class="table-responsive">
    <table class="table table-hover mb-0 table-cardify">
        <thead><tr><th>#</th><th>Nama</th><th>Email</th><th>Role</th><th>Departemen</th><th>Status</th><th>Last Login</th><th>Aksi</th></tr></thead>
        <tbody>
        <?php foreach ($users as $i => $u): ?>
        <?php
            $roleRow = $u['role_id'] ? ($roleMap[$u['role_id']] ?? null) : null;
            $roleName = $roleRow ? $roleRow['name'] : ucfirst($u['role']);
            $roleColor = $roleRow && $roleRow['is_admin'] ? 'danger' : (in_array($u['role'], ['manager']) ? 'primary' : 'secondary');
        ?>
        <tr class="fade-up user-row" style="animation-delay:<?= .2 + $i * .04 ?>s"
            data-search="<?= esc(strtolower($u['name'] . ' ' . $u['email'])) ?>"
            data-role="<?= $u['role_id'] ?? '' ?>"
            data-dept="<?= $u['department_id'] ?? '' ?>"
            data-status="<?= $u['is_active'] ? '1' : '0' ?>">
            <td class="text-muted small row-num cardify-hide"><?= $i+1 ?></td>
            <td class="fw-medium cardify-title"><?= esc($u['name']) ?></td>
            <td data-label="Email" style="word-break:break-all"><?= esc($u['email']) ?></td>
            <td data-label="Role"><span class="badge bg-<?= $roleColor ?>-subtle text-<?= $roleColor ?>"><?= esc($roleName) ?></span></td>
            <td data-label="Departemen">
                <?php if ($u['department_id'] && isset($deptMap[$u['department_id']])): ?>
                <span class="badge bg-info-subtle text-info"><?= esc($deptMap[$u['department_id']]) ?></span>
                <?php else: ?>
                <span class="text-muted small">—</span>
                <?php endif; ?>
            </td>
            <td data-label="Status">
                <?= $u['is_active']
                    ? '<span class="badge bg-success-subtle text-success">Aktif</span>'
                    : '<span class="badge bg-danger-subtle text-danger">Nonaktif</span>' ?>
                <?php
                $isLocked = ! empty($u['locked_until']) && strtotime($u['locked_until']) > time();
                if ($isLocked): ?>
                <span class="badge bg-warning text-dark ms-1" title="Terkunci hingga <?= date('H:i', strtotime($u['locked_until'])) ?>">
                    <i class="bi bi-lock-fill me-1"></i>Terkunci
                </span>
                <?php endif; ?>
            </td>
            <td data-label="Last Login">
                <?php
                $lastLog = $loginLogs[$u['id']][0] ?? null;
                if ($lastLog): ?>
                <div class="small lh-sm">
                    <span class="text-muted"><?= date('d M Y H:i', strtotime($lastLog['login_at'])) ?></span><br>
                    <span class="text-muted opacity-75">
                        <i class="bi bi-<?= $lastLog['device_type'] === 'mobile' ? 'phone' : ($lastLog['device_type'] === 'tablet' ? 'tablet' : 'laptop') ?>"></i>
                        <?= esc($lastLog['browser'] ?? '—') ?>
                    </span>
                </div>
                <?php else: ?>
                <span class="text-muted small">—</span>
                <?php endif; ?>
            </td>
            <td class="cardify-actions">
                <button class="btn btn-sm btn-outline-info login-hist-btn me-1"
                    title="Riwayat Login"
                    data-uid="<?= $u['id'] ?>"
                    data-uname="<?= esc($u['name']) ?>">
                    <i class="bi bi-clock-history"></i>
                </button>
                <button class="btn btn-sm btn-outline-secondary edit-user-btn me-1"
                    data-id="<?= $u['id'] ?>"
                    data-name="<?= esc($u['name']) ?>"
                    data-role_id="<?= $u['role_id'] ?? '' ?>"
                    data-dept="<?= $u['department_id'] ?? '' ?>">
                    <i class="bi bi-pencil"></i>
                </button>
                <a href="<?= base_url('users/'.$u['id'].'/menu-access') ?>" class="btn btn-sm btn-outline-secondary me-1" title="Akses Menu Tambahan"><i class="bi bi-list-check"></i></a>
                <?php if ($isLocked): ?>
                <form method="POST" action="<?= base_url('users/'.$u['id'].'/unlock') ?>" class="d-inline" onsubmit="return confirm('Buka kunci akun ini?')">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm btn-warning me-1" title="Buka kunci akun"><i class="bi bi-unlock-fill"></i></button>
                </form>
                <?php endif; ?>
                <form method="POST" action="<?= base_url('users/'.$u['id'].'/toggle') ?>" class="d-inline">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm btn-outline-warning me-1" title="<?= $u['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?>"><i class="bi bi-<?= $u['is_active'] ? 'pause' : 'play' ?>-fill"></i></button>
                </form>
                <?php if ($u['id'] !== $user['id']): ?>
                <form method="POST" action="<?= base_url('users/'.$u['id'].'/delete') ?>" class="d-inline" onsubmit="return confirm('Hapus user ini?')">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm btn-outline-danger" title="Hapus"><i class="bi bi-trash"></i></button>
                </form>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        <tr id="noResultRow" style="display:none"><td colspan="8" class="text-center text-muted py-4">Tidak ada user yang cocok dengan filter.</td></tr>
        </tbody>
    </table>
    </div>
    </div>
</div>

<!-- Login History Modal -->
<div class="modal fade" id="loginHistModal" tabindex="-1">
<div class="modal-dialog modal-lg">
<div class="modal-content">
<div class="modal-header">
    <h5 class="modal-title fw-semibold"><i class="bi bi-clock-history me-2"></i>Riwayat Login — <span id="loginHistName"></span></h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body p-0">
<div class="table-responsive">
<table class="table table-sm mb-0">
    <thead><tr><th>Waktu</th><th>IP</th><th>Host</th><th>Browser</th><th>OS</th><th>Device</th></tr></thead>
    <tbody id="loginHistBody"></tbody>
</table>
</div>
</div>
</div></div></div>

<?php
// Encode login logs for JS
$loginLogsJson = [];
foreach ($loginLogs as $uid => $logs) {
    $loginLogsJson[$uid] = $logs;
}
?>
<script>
const loginLogsData = <?= json_encode($loginLogsJson) ?>;
</script>

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
    <div class="mb-3 p-2 rounded bg-light border small text-muted"><i class="bi bi-info-circle me-1"></i>Password awal otomatis <strong>123456</strong>. User akan diminta membuat password baru saat login pertama.</div>
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
// Filter halaman User (client-side)
(function(){
    const fSearch = document.getElementById('fSearch');
    const fRole   = document.getElementById('fRole');
    const fDept   = document.getElementById('fDept');
    const fStatus = document.getElementById('fStatus');
    const fReset  = document.getElementById('fReset');
    const fCount  = document.getElementById('fCount');
    const noRow   = document.getElementById('noResultRow');
    const rows    = [...document.querySelectorAll('.user-row')];
    if (! rows.length) return;

    function apply(){
        const q = (fSearch.value || '').trim().toLowerCase();
        const r = fRole.value, d = fDept.value, s = fStatus.value;
        let shown = 0, n = 0;
        rows.forEach(row => {
            const ok = (! q || row.dataset.search.includes(q))
                && (! r || row.dataset.role === r)
                && (! d || row.dataset.dept === d)
                && (! s || row.dataset.status === s);
            row.style.display = ok ? '' : 'none';
            if (ok) { shown++; const num = row.querySelector('.row-num'); if (num) num.textContent = ++n; }
        });
        fCount.textContent = shown;
        noRow.style.display = shown ? 'none' : '';
    }

    [fSearch, fRole, fDept, fStatus].forEach(el => {
        el.addEventListener('input', apply);
        el.addEventListener('change', apply);
    });
    fReset.addEventListener('click', () => {
        fSearch.value = ''; fRole.value = ''; fDept.value = ''; fStatus.value = '';
        apply();
    });
})();
</script>
<script>
const esc = (s) => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
document.querySelectorAll('.login-hist-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const uid   = this.dataset.uid;
        const uname = this.dataset.uname;
        document.getElementById('loginHistName').textContent = uname;
        const logs  = loginLogsData[uid] || [];
        const tbody = document.getElementById('loginHistBody');
        if (!logs.length) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-3">Belum ada riwayat login.</td></tr>';
        } else {
            tbody.innerHTML = logs.map(l => {
                const dt = l.login_at ? esc(l.login_at.replace('T', ' ').substring(0, 16)) : '—';
                const devIcon = l.device_type === 'mobile' ? 'phone' : (l.device_type === 'tablet' ? 'tablet' : 'laptop');
                const devLabel = l.device_name ? `${esc(l.device_type)} (${esc(l.device_name)})` : esc(l.device_type);
                return `<tr>
                    <td class="small">${dt}</td>
                    <td class="small font-monospace">${esc(l.ip) || '—'}</td>
                    <td class="small">${esc(l.hostname) || '<span class="text-muted">—</span>'}</td>
                    <td class="small">${esc(l.browser) || '—'} ${esc(l.browser_ver)}</td>
                    <td class="small">${esc(l.platform) || '—'}</td>
                    <td class="small"><i class="bi bi-${devIcon} me-1"></i>${devLabel}</td>
                </tr>`;
            }).join('');
        }
        new bootstrap.Modal(document.getElementById('loginHistModal')).show();
    });
});

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
