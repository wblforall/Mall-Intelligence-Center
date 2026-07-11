<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$statusLabel = ['draft'=>'Draft','active'=>'Aktif','expired'=>'Expired','terminated'=>'Dihentikan'];
$statusBadge = ['draft'=>'secondary','active'=>'success','expired'=>'danger','terminated'=>'danger'];

function pksBerakhirBadge(?string $date, string $status): string {
    if ($status === 'terminated' || $status === 'draft') return $date ? date('d M Y', strtotime($date)) : '—';
    if (! $date) return '—';
    $d = (int)(new DateTime())->diff(new DateTime($date))->format('%r%a');
    $fmt = date('d M Y', strtotime($date));
    if ($d < 0)   return '<span class="badge bg-danger-subtle text-danger small">Expired ' . abs($d) . ' hari lalu</span>';
    if ($d <= 7)  return '<span class="badge bg-danger-subtle text-danger small"><i class="bi bi-exclamation-triangle-fill me-1"></i>H-' . $d . '</span> <span class="text-muted small">' . $fmt . '</span>';
    if ($d <= 30) return '<span class="badge bg-warning-subtle text-warning small"><i class="bi bi-clock me-1"></i>H-' . $d . '</span> <span class="text-muted small">' . $fmt . '</span>';
    return $fmt;
}
?>

<div class="container-fluid py-4">

    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <nav aria-label="breadcrumb" class="d-none d-md-block"><ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?= base_url('legal') ?>">Legal</a></li>
                <li class="breadcrumb-item active">Perjanjian Kerja Sama</li>
            </ol></nav>
            <h4 class="fw-bold mb-0"><i class="bi bi-file-earmark-text me-2 text-primary"></i>Perjanjian Kerja Sama (PKS)</h4>
        </div>
        <?php if ($canEdit): ?>
        <a href="<?= base_url('legal/pks/new') ?>" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Tambah PKS
        </a>
        <?php endif; ?>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
    <?php endif; ?>

    <!-- Filter -->
    <form method="get" class="card mb-3">
        <div class="card-body py-2">
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <input type="text" name="q" value="<?= esc($filters['q'] ?? '') ?>"
                           class="form-control form-control-sm" placeholder="Cari nomor PKS / pihak kedua...">
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
                    <a href="<?= base_url('legal/pks') ?>" class="btn btn-sm btn-light">Reset</a>
                </div>
            </div>
        </div>
    </form>

    <!-- Table -->
    <div class="card">
        <div class="card-body p-0">
            <?php if (empty($rows)): ?>
            <p class="text-muted text-center py-4 mb-0">Belum ada data PKS.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Nomor PKS</th>
                            <th>Pihak Kedua</th>
                            <th>Status</th>
                            <th>Mulai</th>
                            <th>Berakhir</th>
                            <th class="text-end">Nilai</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $r): ?>
                        <tr>
                            <td>
                                <a href="<?= base_url('legal/pks/' . $r['id']) ?>" class="fw-medium text-decoration-none">
                                    <?= esc($r['nomor_pks']) ?>
                                </a>
                                <?php if ($r['ruang_lingkup']): ?>
                                <div class="text-muted small text-truncate" style="max-width:200px"><?= esc($r['ruang_lingkup']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><?= esc($r['pihak_kedua']) ?></td>
                            <td><span class="badge bg-<?= $statusBadge[$r['status']] ?>-subtle text-<?= $statusBadge[$r['status']] ?>"><?= $statusLabel[$r['status']] ?></span></td>
                            <td class="text-muted small">
                                <?= $r['tanggal_mulai'] ? date('d M Y', strtotime($r['tanggal_mulai'])) : '—' ?>
                            </td>
                            <td class="small"><?= pksBerakhirBadge($r['tanggal_berakhir'], $r['status']) ?></td>
                            <td class="text-end text-muted small">
                                <?= $r['nilai'] ? 'Rp ' . number_format($r['nilai'], 0, ',', '.') : '—' ?>
                            </td>
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
