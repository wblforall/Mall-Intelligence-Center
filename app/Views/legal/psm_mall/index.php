<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
$statusLabel = ['draft'=>'Draft','active'=>'Aktif','expired'=>'Expired','terminated'=>'Diakhiri'];
$statusBadge = ['draft'=>'secondary','active'=>'success','expired'=>'danger','terminated'=>'danger'];
$mallLabel   = [1=>'eWalk', 2=>'Pentacity'];

function psmMallExpiryBadge(?string $date): string {
    if (! $date) return '';
    $d = (int)(new DateTime())->diff(new DateTime($date))->format('%r%a');
    if ($d < 0)   return '<span class="badge bg-danger-subtle text-danger small">Overdue</span>';
    if ($d <= 7)  return '<span class="badge bg-danger-subtle text-danger small">H-'.$d.'</span>';
    if ($d <= 30) return '<span class="badge bg-warning-subtle text-warning small">H-'.$d.'</span>';
    return '';
}
?>

<div class="container-fluid py-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <nav aria-label="breadcrumb"><ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?= base_url('legal') ?>">Legal</a></li>
                <li class="breadcrumb-item active">PSM Mall</li>
            </ol></nav>
            <h4 class="fw-bold mb-0"><i class="bi bi-building me-2 text-primary"></i>PSM Mall</h4>
        </div>
        <?php if ($canEdit): ?>
        <a href="<?= base_url('legal/psm-mall/new') ?>" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Tambah PSM</a>
        <?php endif; ?>
    </div>

    <?php if (session()->getFlashdata('success')): ?><div class="alert alert-success"><?= session()->getFlashdata('success') ?></div><?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?><div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div><?php endif; ?>

    <form method="get" class="card mb-3">
        <div class="card-body py-2">
            <div class="row g-2 align-items-end">
                <div class="col-md-4"><input type="text" name="q" value="<?= esc($filters['q'] ?? '') ?>" class="form-control form-control-sm" placeholder="Cari tenant / nomor PSM..."></div>
                <div class="col-md-2">
                    <select name="mall_id" class="form-select form-select-sm">
                        <option value="">Semua Mall</option>
                        <option value="1" <?= ($filters['mall_id'] ?? '') === '1' ? 'selected' : '' ?>>eWalk</option>
                        <option value="2" <?= ($filters['mall_id'] ?? '') === '2' ? 'selected' : '' ?>>Pentacity</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Semua Status</option>
                        <?php foreach ($statusLabel as $v => $l): ?>
                        <option value="<?= $v ?>" <?= ($filters['status'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <button class="btn btn-sm btn-primary">Filter</button>
                    <a href="<?= base_url('legal/psm-mall') ?>" class="btn btn-sm btn-light">Reset</a>
                </div>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="card-body p-0">
            <?php if (empty($rows)): ?>
            <p class="text-muted text-center py-4 mb-0">Belum ada data PSM Mall.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Nomor PSM</th>
                            <th>Tenant</th>
                            <th>Unit</th>
                            <th>Mall</th>
                            <th>Status</th>
                            <th>Berakhir</th>
                            <th class="text-end">Luas (m²)</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $r): ?>
                        <tr>
                            <td class="text-muted small"><?= esc($r['nomor_psm']) ?></td>
                            <td><a href="<?= base_url('legal/psm-mall/'.$r['id']) ?>" class="fw-medium text-decoration-none"><?= esc($r['nama_tenant']) ?></a></td>
                            <td class="text-muted small"><?= esc($r['unit_lokasi']) ?: '—' ?></td>
                            <td><?= $mallLabel[$r['mall_id']] ?? '—' ?></td>
                            <td><span class="badge bg-<?= $statusBadge[$r['status']] ?? 'secondary' ?>-subtle text-<?= $statusBadge[$r['status']] ?? 'secondary' ?>"><?= $statusLabel[$r['status']] ?? esc($r['status']) ?></span></td>
                            <td>
                                <?php if ($r['tanggal_berakhir']): ?>
                                <span class="me-1"><?= date('d M Y', strtotime($r['tanggal_berakhir'])) ?></span>
                                <?= psmMallExpiryBadge($r['tanggal_berakhir']) ?>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                            <td class="text-end"><?= $r['luas_m2'] !== null && $r['luas_m2'] !== '' ? number_format($r['luas_m2'], 2, ',', '.') : '—' ?></td>
                            <td class="text-end"><a href="<?= base_url('legal/psm-mall/'.$r['id']) ?>" class="btn btn-sm btn-outline-secondary py-0">Lihat</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
