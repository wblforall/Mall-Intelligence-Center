<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<!-- Header -->
<div class="d-flex align-items-center gap-2 mb-4">
    <div class="rounded-2 d-flex align-items-center justify-content-center flex-shrink-0"
         style="width:36px;height:36px;background:rgba(34,197,94,.15)">
        <i class="bi bi-shop-window" style="color:var(--bs-success);font-size:1rem"></i>
    </div>
    <div>
        <h4 class="fw-bold mb-0"><?= esc($tenant['nama']) ?></h4>
        <small class="text-muted">
            <?= $tenant['kategori'] ? esc($tenant['kategori']) . ' · ' : '' ?>
            <?= $tenant['lantai'] ? 'Lt. ' . esc($tenant['lantai']) : '' ?>
            <?= ($tenant['lantai'] && $tenant['nomor_unit']) ? ' · ' : '' ?>
            <?= $tenant['nomor_unit'] ? esc($tenant['nomor_unit']) : 'Historis Kerjasama Program Loyalty' ?>
        </small>
    </div>
    <div class="ms-auto d-flex gap-2">
        <a href="<?= base_url('loyalty/tenants') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Master Tenant
        </a>
        <a href="<?= base_url('loyalty#tenant') ?>" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-star me-1"></i>Program Loyalty
        </a>
    </div>
</div>

<!-- Info Card -->
<div class="card mb-4">
    <div class="card-body py-3">
        <div class="row g-3 align-items-center">
            <?php if ($tenant['contact_person'] || $tenant['no_hp'] || $tenant['email']): ?>
            <div class="col-sm-4">
                <div class="small text-muted fw-semibold mb-1"><i class="bi bi-person me-1"></i>Contact Person</div>
                <?= $tenant['contact_person'] ? '<div>' . esc($tenant['contact_person']) . '</div>' : '' ?>
                <?= $tenant['no_hp']          ? '<div class="text-muted small">' . esc($tenant['no_hp']) . '</div>' : '' ?>
                <?= $tenant['email']          ? '<div class="text-muted small">' . esc($tenant['email']) . '</div>' : '' ?>
            </div>
            <?php endif; ?>
            <?php if ($tenant['catatan']): ?>
            <div class="col-sm-6">
                <div class="small text-muted fw-semibold mb-1"><i class="bi bi-sticky me-1"></i>Catatan</div>
                <div class="small"><?= esc($tenant['catatan']) ?></div>
            </div>
            <?php endif; ?>
            <div class="col ms-auto text-end">
                <div class="small text-muted">Total Program</div>
                <div class="fw-bold fs-3 text-success"><?= count($programs) ?></div>
            </div>
        </div>
    </div>
</div>

<?php
function renderTenantProgramTable(array $rows, string $emptyMsg): void { ?>
<?php if (empty($rows)): ?>
<div class="card"><div class="card-body text-center py-4 text-muted small"><?= $emptyMsg ?></div></div>
<?php else: ?>
<div class="card">
<div class="table-responsive">
<table class="table table-hover mb-0">
<thead class="table-light">
    <tr>
        <th class="ps-3">Nama Program</th>
        <th>Periode</th>
        <th class="text-end">Target</th>
        <th class="text-end">Realisasi</th>
        <th class="text-end">Budget</th>
        <th class="text-center">Dibuat</th>
        <th class="text-center">Status</th>
    </tr>
</thead>
<tbody>
<?php foreach ($rows as $p): ?>
<tr>
    <td class="ps-3 fw-semibold">
        <a href="<?= base_url('loyalty/detail/s/' . $p['id']) ?>" class="text-decoration-none">
            <?= esc($p['nama_program']) ?>
        </a>
        <?= $p['created_by_name'] ? '<div class="text-muted" style="font-size:.7rem">oleh ' . esc($p['created_by_name']) . '</div>' : '' ?>
    </td>
    <td class="small text-muted" style="white-space:nowrap">
        <?= $p['tanggal_mulai'] ? date('d M Y', strtotime($p['tanggal_mulai'])) : '—' ?>
        <?= ($p['tanggal_selesai'] && $p['tanggal_selesai'] !== $p['tanggal_mulai']) ? '<br>' . date('d M Y', strtotime($p['tanggal_selesai'])) : '' ?>
    </td>
    <td class="text-end small"><?= $p['target_peserta'] ? number_format($p['target_peserta']) : '—' ?></td>
    <td class="text-end small fw-semibold text-<?= (int)$p['total_member'] > 0 ? 'success' : 'muted' ?>">
        <?= number_format((int)$p['total_member']) ?>
    </td>
    <td class="text-end small">
        <?= (int)$p['budget'] > 0 ? 'Rp ' . number_format((int)$p['budget'], 0, ',', '.') : '—' ?>
    </td>
    <td class="text-center small text-muted">
        <?= $p['created_at'] ? date('d M Y', strtotime($p['created_at'])) : '—' ?>
    </td>
    <td class="text-center">
        <span class="badge <?= $p['status'] === 'active' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' ?>">
            <?= $p['status'] === 'active' ? 'Aktif' : 'Closed' ?>
        </span>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
<?php endif; ?>
<?php } ?>

<?php if (empty($programs)): ?>
<div class="card"><div class="card-body text-center py-5 text-muted">
    <i class="bi bi-calendar-x display-4 d-block mb-2 opacity-25"></i>
    <p class="mb-0">Belum ada program yang tercatat untuk tenant ini.</p>
</div></div>
<?php else: ?>

<?php
$active   = array_filter($programs, fn($p) => $p['status'] === 'active');
$inactive = array_filter($programs, fn($p) => $p['status'] !== 'active');
?>

<?php if (!empty($active)): ?>
<h6 class="text-success fw-semibold mb-3"><i class="bi bi-play-circle me-1"></i>Program Aktif (<?= count($active) ?>)</h6>
<?php renderTenantProgramTable($active, ''); ?>
<?php if (!empty($inactive)): ?><div class="mb-4"></div><?php endif; ?>
<?php endif; ?>

<?php if (!empty($inactive)): ?>
<h6 class="text-secondary fw-semibold mb-3"><i class="bi bi-archive me-1"></i>Program Closed (<?= count($inactive) ?>)</h6>
<?php renderTenantProgramTable($inactive, 'Tidak ada program closed.'); ?>
<?php endif; ?>

<?php endif; ?>

<?= $this->endSection() ?>
