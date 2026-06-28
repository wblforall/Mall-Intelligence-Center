<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$statusLabel = ['draft' => 'Draft', 'active' => 'Aktif', 'expired' => 'Expired', 'terminated' => 'Diakhiri'];
$statusBadge = ['draft' => 'secondary', 'active' => 'success', 'expired' => 'danger', 'terminated' => 'danger'];

function psmGudangExpiryBadge(?string $date): string {
    if (! $date) return '';
    $d = (int)(new DateTime())->diff(new DateTime($date))->format('%r%a');
    if ($d < 0)   return '<span class="badge bg-danger-subtle text-danger small">Expired ' . abs($d) . 'h lalu</span>';
    if ($d <= 7)  return '<span class="badge bg-danger-subtle text-danger small"><i class="bi bi-exclamation-triangle-fill me-1"></i>H-' . $d . '</span>';
    if ($d <= 30) return '<span class="badge bg-warning-subtle text-warning small"><i class="bi bi-clock me-1"></i>H-' . $d . '</span>';
    return '';
}
?>

<div class="container-fluid py-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <nav aria-label="breadcrumb"><ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?= base_url('legal') ?>">Legal</a></li>
                <li class="breadcrumb-item active">PSM Gudang</li>
            </ol></nav>
            <h4 class="fw-bold mb-0"><i class="bi bi-archive me-2 text-primary"></i>PSM Gudang</h4>
        </div>
        <?php if ($canEdit): ?>
        <a href="<?= base_url('legal/psm-gudang/new') ?>" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Tambah PSM</a>
        <?php endif; ?>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= session()->getFlashdata('success') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?= session()->getFlashdata('error') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <form method="get" class="card mb-3">
        <div class="card-body py-2">
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <input type="text" name="q" value="<?= esc($filters['q'] ?? '') ?>" class="form-control form-control-sm" placeholder="Cari nomor PSM / penyewa / lokasi…">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Semua Status</option>
                        <?php foreach ($statusLabel as $v => $l): ?>
                        <option value="<?= $v ?>" <?= ($filters['status'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <button class="btn btn-sm btn-primary">Filter</button>
                    <a href="<?= base_url('legal/psm-gudang') ?>" class="btn btn-sm btn-light">Reset</a>
                </div>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="card-body p-0">
            <?php if (empty($rows)): ?>
            <p class="text-muted text-center py-4 mb-0">Belum ada data PSM Gudang.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Nomor PSM</th>
                            <th>Penyewa</th>
                            <th>Lokasi Gudang</th>
                            <th>Status</th>
                            <th>Berakhir</th>
                            <th class="text-end">Luas (m²)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $r): ?>
                        <tr>
                            <td>
                                <a href="<?= base_url('legal/psm-gudang/' . $r['id']) ?>" class="fw-medium text-decoration-none">
                                    <?= esc($r['nomor_psm']) ?>
                                </a>
                            </td>
                            <td><?= esc($r['nama_penyewa']) ?></td>
                            <td class="text-muted small"><?= esc($r['lokasi_gudang']) ?></td>
                            <td>
                                <?php $s = $r['status'] ?? 'draft'; ?>
                                <span class="badge bg-<?= $statusBadge[$s] ?? 'secondary' ?>-subtle text-<?= $statusBadge[$s] ?? 'secondary' ?>">
                                    <?= $statusLabel[$s] ?? esc($s) ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                $badge = psmGudangExpiryBadge($r['tanggal_berakhir']);
                                if ($badge):
                                    echo $badge . ' ';
                                endif;
                                echo $r['tanggal_berakhir'] ? date('d M Y', strtotime($r['tanggal_berakhir'])) : '<span class="text-muted">—</span>';
                                ?>
                            </td>
                            <td class="text-end"><?= $r['luas_m2'] !== null ? number_format((float)$r['luas_m2'], 2, ',', '.') : '<span class="text-muted">—</span>' ?></td>
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
