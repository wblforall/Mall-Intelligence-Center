<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= base_url('users') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h4 class="fw-bold mb-0">Akses Menu Tambahan</h4>
        <small class="text-muted"><?= esc($target['name']) ?> · <?= esc($target['email']) ?> · <?= esc(ucfirst($target['role'] ?? '-')) ?></small>
    </div>
</div>

<div class="alert alert-info py-2 small">
    <i class="bi bi-info-circle me-1"></i>Centang menu yang ingin diberikan ke user ini <strong>secara khusus</strong> — berlaku <strong>di atas</strong> akses departemennya (additive). Berguna saat satu departemen punya beberapa orang dengan akses berbeda (mis. hanya staff Legal di dept HR-GA &amp; Legal yang boleh menu Kontrak).
    <br>Kalau user adalah <strong>Admin</strong>, dia sudah otomatis akses semua — pengaturan ini tak berpengaruh.
</div>

<form method="POST" action="<?= base_url('users/'.$target['id'].'/menu-access') ?>">
    <?= csrf_field() ?>
    <div class="card">
    <div class="card-body p-0">
    <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
    <thead class="table-light"><tr><th class="ps-3">Menu</th><th class="text-center" style="width:120px">Lihat</th><th class="text-center" style="width:120px">Edit</th></tr></thead>
    <tbody>
    <?php foreach ($menuLabels as $key => $label):
        $canView = ! empty($access[$key]['can_view']);
        $canEdit = ! empty($access[$key]['can_edit']);
    ?>
    <tr>
        <td class="ps-3 fw-medium small"><?= esc($label) ?> <span class="text-muted">(<?= $key ?>)</span></td>
        <td class="text-center">
            <input type="checkbox" class="form-check-input" name="menus[<?= $key ?>][can_view]" value="1" <?= $canView ? 'checked' : '' ?>>
        </td>
        <td class="text-center">
            <input type="checkbox" class="form-check-input" name="menus[<?= $key ?>][can_edit]" value="1" <?= $canEdit ? 'checked' : '' ?>>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    </table>
    </div>
    </div>
    </div>
    <div class="mt-3 d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Simpan</button>
        <a href="<?= base_url('users') ?>" class="btn btn-outline-secondary">Batal</a>
    </div>
</form>

<?= $this->endSection() ?>
