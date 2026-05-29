<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
$statusLabel = ['draft'=>'Draft','active'=>'Aktif','expired'=>'Expired','terminated'=>'Diakhiri'];
$statusBadge = ['draft'=>'secondary','active'=>'success','expired'=>'danger','terminated'=>'danger'];
$jenisLabel  = ['retail'=>'Retail','fnb'=>'F&B','anchor'=>'Anchor','kiosk'=>'Kiosk','atm'=>'ATM','lainnya'=>'Lainnya'];
$mallLabel   = [1=>'eWalk',2=>'Pentacity'];
?>
<div class="container-fluid py-4">
    <div class="mb-3">
        <nav aria-label="breadcrumb"><ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="<?= base_url('legal') ?>">Legal</a></li>
            <li class="breadcrumb-item"><a href="<?= base_url('legal/leases') ?>">Perjanjian Sewa</a></li>
            <li class="breadcrumb-item active"><?= esc($lease['tenant_name']) ?></li>
        </ol></nav>
        <div class="d-flex align-items-center justify-content-between">
            <h4 class="fw-bold mb-0"><?= esc($lease['tenant_name']) ?></h4>
            <?php if ($canEdit): ?>
            <div class="d-flex gap-2">
                <a href="<?= base_url('legal/leases/'.$lease['id'].'/edit') ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil me-1"></i>Edit</a>
                <form action="<?= base_url('legal/leases/'.$lease['id'].'/delete') ?>" method="post" onsubmit="return confirm('Hapus perjanjian sewa ini?')"><?= csrf_field() ?><button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash me-1"></i>Hapus</button></form>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php if (session()->getFlashdata('success')): ?><div class="alert alert-success"><?= session()->getFlashdata('success') ?></div><?php endif; ?>
    <div class="row g-3"><div class="col-md-8">
        <div class="card mb-3"><div class="card-header fw-semibold">Detail Perjanjian</div><div class="card-body"><div class="row g-3">
            <div class="col-md-6"><div class="text-muted small">NOMOR KONTRAK</div><div class="fw-medium"><?= esc($lease['nomor_kontrak']) ?></div></div>
            <div class="col-md-3"><div class="text-muted small">UNIT</div><div><?= esc($lease['unit_no'] ?? '—') ?></div></div>
            <div class="col-md-3"><div class="text-muted small">JENIS</div><div><?= $jenisLabel[$lease['jenis_sewa']] ?? $lease['jenis_sewa'] ?></div></div>
            <div class="col-md-3"><div class="text-muted small">MALL</div><div><?= $mallLabel[$lease['mall_id']] ?? '—' ?></div></div>
            <div class="col-md-3"><div class="text-muted small">STATUS</div><span class="badge bg-<?= $statusBadge[$lease['status']] ?>-subtle text-<?= $statusBadge[$lease['status']] ?>"><?= $statusLabel[$lease['status']] ?></span></div>
            <div class="col-md-3"><div class="text-muted small">MULAI</div><div><?= date('d M Y', strtotime($lease['tanggal_mulai'])) ?></div></div>
            <div class="col-md-3"><div class="text-muted small">BERAKHIR</div><div><?= date('d M Y', strtotime($lease['tanggal_berakhir'])) ?></div></div>
            <?php if ($lease['nilai_sewa']): ?>
            <div class="col-md-4"><div class="text-muted small">NILAI SEWA</div><div class="fw-medium">Rp <?= number_format($lease['nilai_sewa'],0,',','.') ?></div></div>
            <div class="col-md-4"><div class="text-muted small">PERIODE</div><div><?= ucfirst($lease['periode_pembayaran'] ?? '—') ?></div></div>
            <?php endif; ?>
            <?php if ($lease['catatan']): ?>
            <div class="col-12"><div class="text-muted small">CATATAN</div><div><?= nl2br(esc($lease['catatan'])) ?></div></div>
            <?php endif; ?>
        </div></div></div>
        <?= view('legal/partials/document_list', ['documents' => $documents, 'entity_type' => 'lease', 'entity_id' => $lease['id'], 'canEdit' => $canEdit]) ?>
    </div></div>
</div>
<?= $this->endSection() ?>
