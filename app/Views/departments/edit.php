<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= base_url('departments') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h4 class="fw-bold mb-0">Pengaturan Departemen</h4>
        <small class="text-muted"><?= esc($dept['name']) ?></small>
    </div>
</div>

<form method="POST" action="<?= base_url('departments/'.$dept['id'].'/edit') ?>">
<?= csrf_field() ?>

<div class="row g-3">

<!-- Basic Info -->
<div class="col-md-4">
<div class="card">
<div class="card-header"><h6 class="mb-0 fw-semibold"><i class="bi bi-info-circle me-2"></i>Info Departemen</h6></div>
<div class="card-body">
    <div class="mb-3">
        <label class="form-label small fw-semibold">Nama Departemen <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control" value="<?= esc($dept['name']) ?>" required>
    </div>
    <div class="mb-0">
        <label class="form-label small fw-semibold">Deskripsi</label>
        <input type="text" name="description" class="form-control" value="<?= esc($dept['description']) ?>" placeholder="Opsional">
    </div>
</div>
</div>
</div>

<!-- Menu Access -->
<div class="col-md-8">
<div class="card">
<div class="card-header"><h6 class="mb-0 fw-semibold"><i class="bi bi-key me-2"></i>Akses Menu</h6></div>
<div class="card-body p-0">
<div class="table-responsive">
<table class="table table-sm align-middle mb-0">
<thead>
<tr>
    <th style="width:180px">Menu</th>
    <th class="text-center" style="width:80px">Lihat</th>
    <th class="text-center" style="width:80px">Edit</th>
    <th>Section / Data</th>
</tr>
</thead>
<tbody>
<?php
$firstEventKey = true;
$standaloneKeys = ['events', 'loyalty_main', 'creative_main', 'vm_main', 'sponsorship_main', 'people_dev', 'traffic'];
echo '<tr><td colspan="4" class="py-1 px-3 bg-body-secondary" style="font-size:.68rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--bs-secondary-color)">Standalone</td></tr>';
foreach ($menuLabels as $key => $label):
    if ($firstEventKey && !in_array($key, $standaloneKeys)):
        $firstEventKey = false;
?>
<tr><td colspan="4" class="py-1 px-3 bg-body-secondary" style="font-size:.68rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--bs-secondary-color)">Per Event</td></tr>
<?php   endif;
    $access = $dept['menus'][$key] ?? [];
    $canView = !empty($access['can_view']);
    $canEdit = !empty($access['can_edit']);
    $section = $access['section_type'] ?? 'all';
?>
<tr>
    <td class="fw-medium small"><?= $label ?></td>
    <td class="text-center">
        <input type="checkbox" name="menus[<?= $key ?>][can_view]" value="1"
               class="form-check-input menu-view-cb" data-menu="<?= $key ?>"
               <?= $canView ? 'checked' : '' ?>>
    </td>
    <td class="text-center">
        <input type="checkbox" name="menus[<?= $key ?>][can_edit]" value="1"
               class="form-check-input menu-edit-cb" data-menu="<?= $key ?>"
               <?= $canEdit ? 'checked' : '' ?>>
    </td>
    <td>
        <?php if (in_array($key, ['tracking', 'baseline'])): ?>
        <select name="menus[<?= $key ?>][section_type]" class="form-select form-select-sm" style="max-width:200px">
            <?php foreach ($sectionLabels as $sv => $sl): ?>
            <option value="<?= $sv ?>" <?= $section === $sv ? 'selected' : '' ?>><?= $sl ?></option>
            <?php endforeach; ?>
        </select>
        <?php else: ?>
        <input type="hidden" name="menus[<?= $key ?>][section_type]" value="all">
        <span class="badge bg-secondary-subtle text-secondary small">Semua</span>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
<div class="card-footer bg-transparent">
    <small class="text-muted"><i class="bi bi-info-circle me-1"></i>
        <strong>Section</strong> hanya berlaku untuk menu <em>Baseline</em> dan <em>Daily Tracking</em> — menentukan kelompok field yang bisa diisi oleh departemen ini.
    </small>
</div>
</div>
</div>

</div>

<div class="mt-4 d-flex gap-2">
    <button type="submit" class="btn btn-primary">
        <i class="bi bi-check-lg me-1"></i> Simpan Pengaturan
    </button>
    <a href="<?= base_url('departments') ?>" class="btn btn-outline-secondary">Batal</a>
</div>
</form>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
// When "edit" is checked, auto-check "view" too
document.querySelectorAll('.menu-edit-cb').forEach(cb => {
    cb.addEventListener('change', function() {
        if (this.checked) {
            const viewCb = document.querySelector(`.menu-view-cb[data-menu="${this.dataset.menu}"]`);
            if (viewCb) viewCb.checked = true;
        }
    });
});
// When "view" is unchecked, also uncheck "edit"
document.querySelectorAll('.menu-view-cb').forEach(cb => {
    cb.addEventListener('change', function() {
        if (! this.checked) {
            const editCb = document.querySelector(`.menu-edit-cb[data-menu="${this.dataset.menu}"]`);
            if (editCb) editCb.checked = false;
        }
    });
});
</script>
<?= $this->endSection() ?>
