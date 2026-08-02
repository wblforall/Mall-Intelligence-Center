<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= base_url('users') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">Akses Menu Tambahan</h4>
        <small class="text-muted"><?= esc($target['name']) ?> · <?= esc($target['email']) ?> · <?= esc(ucfirst($target['role'] ?? '-')) ?><?= $deptNama ? ' · ' . esc($deptNama) : '' ?></small>
    </div>
    <a href="<?= base_url('users/akses') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-list-check me-1"></i>Tinjau Semua Akses</a>
</div>

<div class="alert alert-info py-2 small">
    <i class="bi bi-info-circle me-1"></i>Centang menu yang ingin diberikan ke user ini <strong>secara khusus</strong> — berlaku <strong>di atas</strong> akses departemennya (additive). Berguna saat satu departemen punya beberapa orang dengan akses berbeda (mis. hanya staff Legal di dept HR-GA &amp; Legal yang boleh menu Kontrak).
    <br><strong>Setujui</strong> = boleh memutuskan persetujuan pada modul itu (mis. approve request Media Promo, event, PIP) — tanpa perlu mengubah role yang dipakai bersama orang lain.
    <br>Kalau user adalah <strong>Admin</strong>, dia sudah otomatis akses semua — pengaturan ini tak berpengaruh.
</div>

<?php if ($deptNama): ?>
<div class="alert alert-secondary py-2 small">
    <i class="bi bi-diagram-3 me-1"></i>Akses bawaan dari departemen <strong><?= esc($deptNama) ?></strong> ditandai <span class="badge bg-secondary-subtle text-secondary-emphasis">dept</span> di bawah — tak perlu dicentang ulang.
</div>
<?php endif; ?>

<!-- Salin akses dari user lain -->
<div class="card mb-3 border-primary-subtle">
<div class="card-body py-2">
    <form method="POST" action="<?= base_url('users/'.$target['id'].'/menu-access/salin') ?>" class="d-flex flex-wrap align-items-center gap-2"
          onsubmit="return confirm('Akses menu user ini akan DIGANTI mengikuti user yang dipilih. Lanjutkan?')">
        <?= csrf_field() ?>
        <span class="small fw-semibold"><i class="bi bi-files me-1"></i>Samakan dengan user lain:</span>
        <select name="sumber_user_id" class="form-select form-select-sm" style="max-width:320px" required>
            <option value="">— pilih user —</option>
            <?php foreach ($userLain as $u): ?>
            <option value="<?= $u['id'] ?>"><?= esc($u['name']) ?><?= $u['dept'] ? ' — ' . esc($u['dept']) : '' ?> (<?= (int) $u['jml'] ?> menu)</option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-sm btn-outline-primary">Salin</button>
        <span class="small text-muted">Menyalin dulu lalu menyesuaikan lebih cepat &amp; kecil risiko ada menu terlewat.</span>
    </form>
</div>
</div>

<form method="POST" action="<?= base_url('users/'.$target['id'].'/menu-access') ?>">
    <?= csrf_field() ?>
    <div class="card">
    <div class="card-body p-0">
    <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
    <thead class="table-light"><tr>
        <th class="ps-3">Menu</th>
        <th class="text-center" style="width:110px">Lihat</th>
        <th class="text-center" style="width:110px">Edit</th>
        <th class="text-center" style="width:120px">Setujui</th>
    </tr></thead>
    <tbody>
    <?php foreach ($menuLabels as $key => $label):
        $canView    = ! empty($access[$key]['can_view']);
        $canEdit    = ! empty($access[$key]['can_edit']);
        $canApprove = ! empty($access[$key]['can_approve']);
        $d          = $deptAccess[$key] ?? null;
    ?>
    <tr>
        <td class="ps-3 fw-medium small">
            <?= esc($label) ?> <span class="text-muted">(<?= $key ?>)</span>
            <?php if ($d): ?>
            <span class="badge bg-secondary-subtle text-secondary-emphasis ms-1" style="font-size:.62rem" title="Sudah didapat dari departemen">dept:
                <?= implode('/', array_filter([
                    ! empty($d['can_view']) ? 'lihat' : null,
                    ! empty($d['can_edit']) ? 'edit' : null,
                    ! empty($d['can_approve']) ? 'setujui' : null,
                ])) ?></span>
            <?php endif; ?>
        </td>
        <td class="text-center">
            <input type="checkbox" class="form-check-input" name="menus[<?= $key ?>][can_view]" value="1" <?= $canView ? 'checked' : '' ?>>
        </td>
        <td class="text-center">
            <input type="checkbox" class="form-check-input" name="menus[<?= $key ?>][can_edit]" value="1" <?= $canEdit ? 'checked' : '' ?>>
        </td>
        <td class="text-center">
            <input type="checkbox" class="form-check-input" name="menus[<?= $key ?>][can_approve]" value="1" <?= $canApprove ? 'checked' : '' ?>>
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
