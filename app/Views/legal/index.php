<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$mallLabel = [1 => 'eWalk', 2 => 'Pentacity'];
$typeLabel = ['lease' => 'Perjanjian Sewa', 'permit' => 'Perizinan', 'contract' => 'Kontrak Vendor'];
$typeIcon  = ['lease' => 'bi-building', 'permit' => 'bi-patch-check', 'contract' => 'bi-briefcase'];
$typeUrl   = ['lease' => 'leases', 'permit' => 'permits', 'contract' => 'contracts'];

function legalDaysLeft(string $date): int {
    return (int)(new DateTime())->diff(new DateTime($date))->format('%r%a');
}
function legalExpiryBadge(string $date): string {
    $d = legalDaysLeft($date);
    if ($d < 0)   return '<span class="badge bg-danger-subtle text-danger">Expired</span>';
    if ($d <= 7)  return '<span class="badge bg-danger-subtle text-danger"><i class="bi bi-exclamation-triangle-fill me-1"></i>H-'.$d.'</span>';
    if ($d <= 30) return '<span class="badge bg-warning-subtle text-warning"><i class="bi bi-clock me-1"></i>H-'.$d.'</span>';
    return '';
}
?>

<div class="container-fluid py-4">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold mb-0"><i class="bi bi-shield-check me-2 text-primary"></i>Legal</h4>
            <p class="text-muted small mb-0">Monitor kontrak, perizinan, dan dokumen hukum</p>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <?php
        $cards = [
            ['label' => 'Perjanjian Sewa',   'icon' => 'bi-building',     'url' => 'legal/leases',    'key' => 'leases'],
            ['label' => 'Perizinan & Lisensi','icon' => 'bi-patch-check',  'url' => 'legal/permits',   'key' => 'permits'],
            ['label' => 'Kontrak Vendor',     'icon' => 'bi-briefcase',    'url' => 'legal/contracts', 'key' => 'contracts'],
        ];
        foreach ($cards as $c):
            $s = $summary[$c['key']];
        ?>
        <div class="col-md-4">
            <a href="<?= base_url($c['url']) ?>" class="card text-decoration-none h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 p-2" style="background:var(--c-icon-primary-bg)">
                            <i class="bi <?= $c['icon'] ?> fs-4" style="color:var(--c-icon-primary-fg)"></i>
                        </div>
                        <div>
                            <div class="text-muted small"><?= $c['label'] ?></div>
                            <div class="fw-bold fs-4"><?= $s['active'] ?> <span class="text-muted fs-6 fw-normal">aktif</span></div>
                        </div>
                        <?php if ($s['expiring'] > 0): ?>
                        <div class="ms-auto">
                            <span class="badge bg-warning-subtle text-warning">
                                <i class="bi bi-clock me-1"></i><?= $s['expiring'] ?> segera berakhir
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Expiring Soon Table -->
    <div class="card">
        <div class="card-header d-flex align-items-center gap-2">
            <i class="bi bi-exclamation-triangle text-warning"></i>
            <span class="fw-semibold">Segera Berakhir <span class="text-muted fw-normal">(≤ 30 hari)</span></span>
            <span class="badge bg-warning-subtle text-warning ms-1"><?= count($expiring) ?></span>
        </div>
        <div class="card-body p-0">
            <?php if (empty($expiring)): ?>
            <p class="text-muted text-center py-4 mb-0"><i class="bi bi-check-circle-fill text-success me-2"></i>Tidak ada yang segera berakhir.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Tipe</th>
                            <th>Nama</th>
                            <th>Nomor</th>
                            <th>Mall</th>
                            <th>Berakhir</th>
                            <th>Sisa</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($expiring as $r):
                            $days = legalDaysLeft($r['tanggal_berakhir']);
                            $urlKey = $typeUrl[$r['entity_type']] ?? '#';
                        ?>
                        <tr>
                            <td>
                                <span class="badge bg-secondary-subtle text-secondary">
                                    <i class="bi <?= $typeIcon[$r['entity_type']] ?? '' ?> me-1"></i>
                                    <?= $typeLabel[$r['entity_type']] ?? $r['entity_type'] ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?= base_url('legal/' . $urlKey . '/' . $r['id']) ?>" class="fw-medium text-decoration-none">
                                    <?= esc($r['nama']) ?>
                                </a>
                            </td>
                            <td class="text-muted small"><?= esc($r['nomor']) ?></td>
                            <td><?= $mallLabel[$r['mall_id']] ?? '—' ?></td>
                            <td><?= date('d M Y', strtotime($r['tanggal_berakhir'])) ?></td>
                            <td><?= legalExpiryBadge($r['tanggal_berakhir']) ?></td>
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
