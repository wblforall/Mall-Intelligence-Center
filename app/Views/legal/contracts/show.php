<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
$statusLabel = ['draft'=>'Draft','active'=>'Aktif','expired'=>'Expired','terminated'=>'Diakhiri'];
$statusBadge = ['draft'=>'secondary','active'=>'success','expired'=>'danger','terminated'=>'danger'];
$mallLabel   = [1=>'eWalk', 2=>'Pentacity'];
?>
<div class="container-fluid py-4">
    <div class="mb-3">
        <nav aria-label="breadcrumb" class="d-none d-md-block"><ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="<?= base_url('legal') ?>">Legal</a></li>
            <li class="breadcrumb-item"><a href="<?= base_url('legal/contracts') ?>">Kontrak Vendor</a></li>
            <li class="breadcrumb-item active"><?= esc($contract['nama_vendor']) ?></li>
        </ol></nav>
        <div class="d-flex align-items-center justify-content-between">
            <h4 class="fw-bold mb-0"><?= esc($contract['nama_vendor']) ?></h4>
            <?php if ($canEdit): ?>
            <div class="d-flex gap-2">
                <a href="<?= base_url('legal/contracts/'.$contract['id'].'/edit') ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil me-1"></i>Edit</a>
                <form action="<?= base_url('legal/contracts/'.$contract['id'].'/delete') ?>" method="post" onsubmit="return confirm('Hapus kontrak ini?')">
                    <?= csrf_field() ?><button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash me-1"></i>Hapus</button>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php if (session()->getFlashdata('success')): ?><div class="alert alert-success"><?= session()->getFlashdata('success') ?></div><?php endif; ?>
    <div class="row g-3">
        <div class="col-md-8">
            <div class="card mb-3"><div class="card-header fw-semibold">Detail Kontrak</div>
                <div class="card-body"><div class="row g-3">
                    <div class="col-md-6"><div class="text-muted small">NOMOR KONTRAK</div><div class="fw-medium"><?= esc($contract['nomor_kontrak']) ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">JENIS</div><div><?= esc($contract['jenis_kontrak']) ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">MALL</div><div><?= $contract['mall_id'] ? ($mallLabel[$contract['mall_id']] ?? '—') : 'Keduanya' ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">STATUS</div><span class="badge bg-<?= $statusBadge[$contract['status']] ?>-subtle text-<?= $statusBadge[$contract['status']] ?>"><?= $statusLabel[$contract['status']] ?></span></div>
                    <div class="col-md-3"><div class="text-muted small">MULAI</div><div><?= date('d M Y', strtotime($contract['tanggal_mulai'])) ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">BERAKHIR</div><div><?= date('d M Y', strtotime($contract['tanggal_berakhir'])) ?></div></div>
                    <?php if ($contract['nilai_kontrak']): ?>
                    <div class="col-md-3"><div class="text-muted small">NILAI</div><div class="fw-medium">Rp <?= number_format($contract['nilai_kontrak'], 0, ',', '.') ?></div></div>
                    <?php endif; ?>
                    <?php if ($contract['lingkup_pekerjaan']): ?>
                    <div class="col-12"><div class="text-muted small">LINGKUP PEKERJAAN</div><div><?= nl2br(esc($contract['lingkup_pekerjaan'])) ?></div></div>
                    <?php endif; ?>
                    <?php if ($contract['catatan']): ?>
                    <div class="col-12"><div class="text-muted small">CATATAN</div><div><?= nl2br(esc($contract['catatan'])) ?></div></div>
                    <?php endif; ?>
                </div></div>
            </div>
            <?= view('legal/partials/document_list', ['documents' => $documents, 'entity_type' => 'contract', 'entity_id' => $contract['id'], 'canEdit' => $canEdit]) ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
