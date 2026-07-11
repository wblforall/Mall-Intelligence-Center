<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$statusLabel = ['active'=>'Aktif','expired'=>'Expired','pending_renewal'=>'Pending Renewal','revoked'=>'Dicabut'];
$statusBadge = ['active'=>'success','expired'=>'danger','pending_renewal'=>'warning','revoked'=>'danger'];
$jenisLabel  = ['IMB'=>'IMB','SLF'=>'SLF','HO_SITU'=>'HO/SITU','SIUP'=>'SIUP','TDP'=>'TDP','Amdal'=>'Amdal','K3'=>'K3','lainnya'=>'Lainnya'];
$mallLabel   = [1=>'eWalk', 2=>'Pentacity'];

function permitExpiryBadge(?string $date): string {
    if (! $date) return '<span class="badge bg-secondary-subtle text-secondary small">Berlaku Tetap</span>';
    $d = (int)(new DateTime())->diff(new DateTime($date))->format('%r%a');
    if ($d < 0)   return '<span class="badge bg-danger-subtle text-danger small">Expired</span>';
    if ($d <= 7)  return '<span class="badge bg-danger-subtle text-danger small">H-'.$d.'</span>';
    if ($d <= 30) return '<span class="badge bg-warning-subtle text-warning small">H-'.$d.'</span>';
    return '<span class="badge bg-success-subtle text-success small">'.date('d M Y', strtotime($date)).'</span>';
}
?>

<div class="container-fluid py-4">

    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <nav aria-label="breadcrumb" class="d-none d-md-block"><ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?= base_url('legal') ?>">Legal</a></li>
                <li class="breadcrumb-item active">Perizinan & Lisensi</li>
            </ol></nav>
            <h4 class="fw-bold mb-0"><i class="bi bi-patch-check me-2 text-primary"></i>Perizinan & Lisensi</h4>
        </div>
        <?php if ($canEdit): ?>
        <a href="<?= base_url('legal/permits/new') ?>" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Tambah Izin
        </a>
        <?php endif; ?>
    </div>

    <!-- Flash -->
    <?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
    <?php endif; ?>

    <!-- Filter -->
    <form method="get" class="card mb-3">
        <div class="card-body py-2">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <input type="text" name="q" value="<?= esc($filters['q'] ?? '') ?>" class="form-control form-control-sm" placeholder="Cari nama / nomor...">
                </div>
                <div class="col-md-2">
                    <select name="mall_id" class="form-select form-select-sm">
                        <option value="">Semua Mall</option>
                        <option value="1" <?= ($filters['mall_id']??'')=='1'?'selected':'' ?>>eWalk</option>
                        <option value="2" <?= ($filters['mall_id']??'')=='2'?'selected':'' ?>>Pentacity</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Semua Status</option>
                        <?php foreach ($statusLabel as $v => $l): ?>
                        <option value="<?= $v ?>" <?= ($filters['status']??'')===$v?'selected':'' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="jenis" class="form-select form-select-sm">
                        <option value="">Semua Jenis</option>
                        <?php foreach ($jenisLabel as $v => $l): ?>
                        <option value="<?= $v ?>" <?= ($filters['jenis']??'')===$v?'selected':'' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <button class="btn btn-sm btn-primary">Filter</button>
                    <a href="<?= base_url('legal/permits') ?>" class="btn btn-sm btn-light">Reset</a>
                </div>
            </div>
        </div>
    </form>

    <!-- Table -->
    <div class="card">
        <div class="card-body p-0">
            <?php if (empty($permits)): ?>
            <p class="text-muted text-center py-4 mb-0">Belum ada data perizinan.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Nama Izin</th>
                            <th>Jenis</th>
                            <th>Nomor</th>
                            <th>Mall</th>
                            <th>Instansi</th>
                            <th>Status</th>
                            <th>Berlaku s/d</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($permits as $p): ?>
                        <tr>
                            <td><a href="<?= base_url('legal/permits/' . $p['id']) ?>" class="fw-medium text-decoration-none"><?= esc($p['nama_izin']) ?></a></td>
                            <td><span class="badge bg-secondary-subtle text-secondary"><?= $jenisLabel[$p['jenis_izin']] ?? $p['jenis_izin'] ?></span></td>
                            <td class="text-muted small"><?= esc($p['nomor_izin']) ?></td>
                            <td><?= $mallLabel[$p['mall_id']] ?? '—' ?></td>
                            <td class="text-muted small"><?= esc($p['instansi_penerbit'] ?? '—') ?></td>
                            <td><span class="badge bg-<?= $statusBadge[$p['status']] ?>-subtle text-<?= $statusBadge[$p['status']] ?>"><?= $statusLabel[$p['status']] ?></span></td>
                            <td><?= permitExpiryBadge($p['tanggal_berakhir']) ?></td>
                            <td>
                                <?php if ($canEdit): ?>
                                <a href="<?= base_url('legal/permits/' . $p['id'] . '/edit') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                                <?php endif; ?>
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
