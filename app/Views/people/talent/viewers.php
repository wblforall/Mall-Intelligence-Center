<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-eye me-2"></i>Viewer Talent Portfolio</h4>
        <small class="text-muted">Daftar user yang boleh melihat peta 9-box penuh. Dikelola admin. Data ini sensitif.</small>
    </div>
    <a href="<?= base_url('people/talent') ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-grid-3x3-gap me-1"></i>Peta 9-Box</a>
</div>

<div class="alert alert-warning py-2 small"><i class="bi bi-shield-lock me-1"></i>Selain admin, hanya user di daftar ini yang bisa membuka peta talent. Default kosong = hanya admin.</div>

<div class="row g-3">
<div class="col-lg-5">
    <div class="card">
        <div class="card-header py-2 fw-semibold small"><i class="bi bi-person-plus me-1"></i>Tambah Viewer</div>
        <div class="card-body">
            <form method="POST" action="<?= base_url('people/talent/viewers/add') ?>">
                <?= csrf_field() ?>
                <select name="user_id" class="form-select form-select-sm mb-2" required>
                    <option value="">— pilih user —</option>
                    <?php foreach ($avail as $u): ?>
                    <option value="<?= $u['id'] ?>"><?= esc($u['name']) ?> (<?= esc($u['email']) ?>)</option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-primary btn-sm w-100"><i class="bi bi-plus-lg me-1"></i>Tambahkan</button>
            </form>
        </div>
    </div>
</div>
<div class="col-lg-7">
    <div class="card">
    <div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
        <thead><tr><th>Nama</th><th>Email</th><th>Role</th><th class="text-end">Aksi</th></tr></thead>
        <tbody>
        <?php if (empty($viewers)): ?>
        <tr><td colspan="4" class="text-center text-muted py-4">Belum ada viewer. Hanya admin yang bisa melihat peta.</td></tr>
        <?php else: foreach ($viewers as $v): ?>
        <tr>
            <td class="fw-medium"><?= esc($v['name'] ?? '—') ?></td>
            <td class="small text-muted"><?= esc($v['email'] ?? '—') ?></td>
            <td><span class="badge bg-secondary"><?= esc($v['role'] ?? '-') ?></span></td>
            <td class="text-end">
                <form method="POST" action="<?= base_url('people/talent/viewers/'.$v['id'].'/remove') ?>" class="d-inline" onsubmit="return confirm('Hapus viewer ini?')">
                    <?= csrf_field() ?><button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                </form>
            </td>
        </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
    </div>
    </div>
</div>
</div>

<?= $this->endSection() ?>
