<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="<?= base_url('users') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">Tinjauan Hak Akses</h4>
        <small class="text-muted">Siapa memegang menu apa — untuk peninjauan berkala</small>
    </div>
    <button class="btn btn-sm btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer me-1"></i>Cetak</button>
</div>

<div class="alert alert-info py-2 small">
    <i class="bi bi-info-circle me-1"></i>Akses berlaku <strong>additive</strong>: Admin (bypass semua) → grant khusus per-user → akses departemen.
    Kolom <strong>Setujui</strong> menandai siapa yang boleh memutuskan persetujuan di modul itu.
</div>

<!-- Admin: bypass semua menu -->
<div class="card mb-3">
<div class="card-body py-2">
    <div class="small">
        <span class="badge bg-danger-subtle text-danger-emphasis me-1"><i class="bi bi-shield-lock me-1"></i>Admin</span>
        <span class="text-muted">akses & setujui SEMUA menu tanpa perlu grant:</span>
        <?php foreach ($admins as $a): ?>
        <span class="badge bg-secondary-subtle text-secondary-emphasis"><?= esc($a['name']) ?></span>
        <?php endforeach; ?>
        <?php if (! $admins): ?><em class="text-muted">tidak ada</em><?php endif; ?>
    </div>
</div>
</div>

<?php if ($redundan): ?>
<!-- Grant per-user yang sudah dicakup departemennya -->
<div class="card mb-3 border-warning-subtle">
<div class="card-body py-2">
    <div class="fw-semibold small mb-2"><i class="bi bi-exclamation-triangle text-warning me-1"></i>Akses khusus yang sudah dicakup departemen (<?= count($redundan) ?> user)</div>
    <div class="small text-muted mb-2">Tidak berbahaya, tapi bikin tinjauan rancu — biasanya sisa saat user pindah departemen. Boleh dibersihkan.</div>
    <div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
    <tbody>
    <?php foreach ($redundan as $uid => $r): ?>
    <tr>
        <td class="fw-medium small" style="width:220px"><?= esc($r['nama']) ?><br><span class="text-muted"><?= esc($r['dept'] ?? '-') ?></span></td>
        <td class="small text-muted"><?= esc(implode(', ', $r['menus'])) ?></td>
        <td class="text-end" style="width:210px">
            <a href="<?= base_url('users/'.$uid.'/menu-access') ?>" class="btn btn-sm btn-outline-secondary">Tinjau</a>
            <form method="POST" action="<?= base_url('users/akses/bersihkan/'.$uid) ?>" class="d-inline"
                  onsubmit="return confirm('Hapus akses khusus yang sudah dicakup departemen untuk <?= esc($r['nama'], 'js') ?>?')">
                <?= csrf_field() ?>
                <button class="btn btn-sm btn-outline-warning">Bersihkan</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    </table>
    </div>
</div>
</div>
<?php endif; ?>

<!-- Matriks per menu -->
<div class="card">
<div class="card-body p-0">
<div class="table-responsive">
<table class="table table-hover align-middle mb-0">
<thead class="table-light"><tr>
    <th class="ps-3" style="min-width:200px">Menu</th>
    <th style="min-width:230px">Departemen</th>
    <th style="min-width:230px">Grant khusus per-user</th>
    <th style="min-width:200px">Boleh menyetujui</th>
</tr></thead>
<tbody>
<?php
$tag = function (array $r): string {
    $p = array_filter([
        ! empty($r['can_view'])    ? 'L' : null,
        ! empty($r['can_edit'])    ? 'E' : null,
        ! empty($r['can_approve']) ? 'S' : null,
    ]);
    return $p ? implode('', $p) : '-';
};
foreach ($menuLabels as $key => $label):
    $d = $deptAkses[$key] ?? [];
    $u = $userAkses[$key] ?? [];
    $approvers = array_merge(
        array_map(fn ($x) => 'Dept ' . $x['dept'], array_filter($d, fn ($x) => ! empty($x['can_approve']))),
        array_map(fn ($x) => $x['name'], array_filter($u, fn ($x) => ! empty($x['can_approve']) && $x['is_active']))
    );
?>
<tr>
    <td class="ps-3 small fw-medium"><?= esc($label) ?><br><span class="text-muted fw-normal"><?= $key ?></span></td>
    <td class="small">
        <?php foreach ($d as $x): ?>
        <span class="badge bg-primary-subtle text-primary-emphasis mb-1"><?= esc($x['dept']) ?> <span class="opacity-75"><?= $tag($x) ?></span></span>
        <?php endforeach; ?>
        <?php if (! $d): ?><span class="text-muted">—</span><?php endif; ?>
    </td>
    <td class="small">
        <?php foreach ($u as $x): ?>
        <span class="badge <?= $x['is_active'] ? 'bg-info-subtle text-info-emphasis' : 'bg-secondary-subtle text-secondary-emphasis text-decoration-line-through' ?> mb-1"
              title="<?= esc(($x['dept'] ?? '-') . ($x['is_active'] ? '' : ' · nonaktif')) ?>"><?= esc($x['name']) ?> <span class="opacity-75"><?= $tag($x) ?></span></span>
        <?php endforeach; ?>
        <?php if (! $u): ?><span class="text-muted">—</span><?php endif; ?>
    </td>
    <td class="small">
        <?php foreach ($approvers as $nama): ?>
        <span class="badge bg-success-subtle text-success-emphasis mb-1"><i class="bi bi-check2-circle me-1"></i><?= esc($nama) ?></span>
        <?php endforeach; ?>
        <?php if (! $approvers): ?><span class="text-muted">hanya Admin</span><?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
</div>

<div class="small text-muted mt-2">Keterangan kode: <strong>L</strong> = Lihat · <strong>E</strong> = Edit · <strong>S</strong> = Setujui. Nama dicoret = user nonaktif.</div>

<?= $this->endSection() ?>
