<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$statusLabel  = ['draft'=>'Draft','active'=>'Aktif','expired'=>'Expired','terminated'=>'Diakhiri'];
$statusBadge  = ['draft'=>'secondary','active'=>'success','expired'=>'danger','terminated'=>'danger'];
$jenisLabel   = ['cleaning'=>'Cleaning','security'=>'Security','parkir'=>'Parkir','maintenance'=>'Maintenance','catering'=>'Catering','IT'=>'IT','marketing'=>'Marketing','lainnya'=>'Lainnya'];
$mallLabel    = [1=>'eWalk', 2=>'Pentacity'];

function contractExpiryBadge(?string $date): string {
    if (! $date) return '';
    $d = (int)(new DateTime())->diff(new DateTime($date))->format('%r%a');
    if ($d < 0)   return '<span class="badge bg-danger-subtle text-danger small">Expired</span>';
    if ($d <= 7)  return '<span class="badge bg-danger-subtle text-danger small">H-'.$d.'</span>';
    if ($d <= 30) return '<span class="badge bg-warning-subtle text-warning small">H-'.$d.'</span>';
    return '';
}
?>

<div class="container-fluid py-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <nav aria-label="breadcrumb" class="d-none d-md-block"><ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?= base_url('legal') ?>">Legal</a></li>
                <li class="breadcrumb-item active">Kontrak Vendor</li>
            </ol></nav>
            <h4 class="fw-bold mb-0"><i class="bi bi-briefcase me-2 text-primary"></i>Kontrak Vendor</h4>
        </div>
        <?php if ($canEdit): ?>
        <a href="<?= base_url('legal/contracts/new') ?>" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Tambah Kontrak</a>
        <?php endif; ?>
    </div>

    <?php if (session()->getFlashdata('success')): ?><div class="alert alert-success"><?= session()->getFlashdata('success') ?></div><?php endif; ?>

    <form method="get" class="card mb-3">
        <div class="card-body py-2">
            <div class="row g-2 align-items-end">
                <div class="col-md-3"><input type="text" name="q" value="<?= esc($filters['q']??'') ?>" class="form-control form-control-sm" placeholder="Cari vendor / nomor..."></div>
                <div class="col-md-2">
                    <select name="mall_id" class="form-select form-select-sm">
                        <option value="">Semua Mall</option>
                        <option value="1" <?= ($filters['mall_id']??'')==='1'?'selected':'' ?>>eWalk</option>
                        <option value="2" <?= ($filters['mall_id']??'')==='2'?'selected':'' ?>>Pentacity</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Semua Status</option>
                        <?php foreach ($statusLabel as $v=>$l): ?><option value="<?= $v ?>" <?= ($filters['status']??'')===$v?'selected':'' ?>><?= $l ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="jenis" class="form-select form-select-sm">
                        <option value="">Semua Jenis</option>
                        <?php foreach ($jenisLabel as $v=>$l): ?><option value="<?= $v ?>" <?= ($filters['jenis']??'')===$v?'selected':'' ?>><?= $l ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <button class="btn btn-sm btn-primary">Filter</button>
                    <a href="<?= base_url('legal/contracts') ?>" class="btn btn-sm btn-light">Reset</a>
                </div>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="card-body p-0">
            <?php if (empty($contracts)): ?>
            <p class="text-muted text-center py-4 mb-0">Belum ada data kontrak vendor.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>Vendor</th><th>Jenis</th><th>Nomor</th><th>Mall</th><th>Status</th><th>Berakhir</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($contracts as $c): ?>
                        <tr>
                            <td><a href="<?= base_url('legal/contracts/'.$c['id']) ?>" class="fw-medium text-decoration-none"><?= esc($c['nama_vendor']) ?></a></td>
                            <td><span class="badge bg-secondary-subtle text-secondary"><?= $jenisLabel[$c['jenis_kontrak']] ?? $c['jenis_kontrak'] ?></span></td>
                            <td class="text-muted small"><?= esc($c['nomor_kontrak']) ?></td>
                            <td><?= $c['mall_id'] ? ($mallLabel[$c['mall_id']] ?? '—') : '<span class="text-muted">Keduanya</span>' ?></td>
                            <td><span class="badge bg-<?= $statusBadge[$c['status']] ?>-subtle text-<?= $statusBadge[$c['status']] ?>"><?= $statusLabel[$c['status']] ?></span></td>
                            <td><?= contractExpiryBadge($c['tanggal_berakhir']) ?: date('d M Y', strtotime($c['tanggal_berakhir'])) ?></td>
                            <td><?php if ($canEdit): ?><a href="<?= base_url('legal/contracts/'.$c['id'].'/edit') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a><?php endif; ?></td>
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
