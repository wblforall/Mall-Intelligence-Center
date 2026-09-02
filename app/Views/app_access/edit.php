<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= base_url('app-access') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h4 class="fw-bold mb-0">Akses Aplikasi — <?= esc($dept['name']) ?></h4>
        <small class="text-muted">
            <?= $dept['tingkat'] === 'holding'
                ? 'Departemen holding: satu peran berlaku di SEMUA unit bisnis.'
                : 'Departemen per-unit: peran diatur terpisah untuk tiap unit bisnis tempat departemen ini punya karyawan.' ?>
        </small>
    </div>
</div>

<?php if ($dept['tingkat'] === 'unit' && empty($companies)): ?>
<div class="alert alert-warning">
    Belum ada karyawan aktif di departemen ini yang punya unit bisnis (<code>company_id</code>) terisi,
    jadi belum ada kolom untuk diisi. Lengkapi <code>company_id</code> karyawannya lebih dulu.
</div>
<?php else: ?>

<form method="POST" action="<?= base_url('app-access/'.$dept['id'].'/edit') ?>">
<?= csrf_field() ?>
<div class="card">
<div class="card-header"><h6 class="mb-0 fw-semibold"><i class="bi bi-key me-2"></i>Peran per Aplikasi</h6></div>
<div class="card-body p-0">
<div class="table-responsive">
<table class="table table-sm align-middle mb-0">
<thead>
<tr>
    <th style="width:200px">Aplikasi</th>
    <?php if ($dept['tingkat'] === 'holding'): ?>
        <th>Peran (semua unit bisnis)</th>
    <?php else: ?>
        <?php foreach ($companies as $c): ?>
        <th><?= esc($c['kode']) ?><br><small class="text-muted fw-normal"><?= esc($c['nama']) ?></small></th>
        <?php endforeach; ?>
    <?php endif; ?>
</tr>
</thead>
<tbody>
<?php foreach ($apps as $app): ?>
<tr>
    <td><i class="bi <?= esc($app['ikon']) ?> me-1"></i> <?= esc($app['nama']) ?></td>
    <?php if ($dept['tingkat'] === 'holding'): ?>
        <td>
            <select name="grant[<?= $app['id'] ?>][0]" class="form-select form-select-sm" <?= $canEdit ? '' : 'disabled' ?>>
                <option value="">— Tidak diberi —</option>
                <?php foreach ($roleByApp[$app['id']] ?? [] as $role): ?>
                <option value="<?= $role['id'] ?>" <?= (($terpilih[$app['id']][0] ?? null) == $role['id']) ? 'selected' : '' ?>>
                    <?= esc($role['label']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </td>
    <?php else: ?>
        <?php foreach ($companies as $c): ?>
        <td>
            <select name="grant[<?= $app['id'] ?>][<?= $c['id'] ?>]" class="form-select form-select-sm" <?= $canEdit ? '' : 'disabled' ?>>
                <option value="">— Tidak diberi —</option>
                <?php foreach ($roleByApp[$app['id']] ?? [] as $role): ?>
                <option value="<?= $role['id'] ?>" <?= (($terpilih[$app['id']][$c['id']] ?? null) == $role['id']) ? 'selected' : '' ?>>
                    <?= esc($role['label']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </td>
        <?php endforeach; ?>
    <?php endif; ?>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
<?php if ($canEdit): ?>
<div class="card-footer text-end">
    <button class="btn btn-primary btn-sm"><i class="bi bi-check-lg me-1"></i> Simpan</button>
</div>
<?php endif; ?>
</div>
</form>

<?php endif; ?>

<?= $this->endSection() ?>
