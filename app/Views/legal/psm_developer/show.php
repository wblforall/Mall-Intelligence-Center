<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
$statusLabel = ['draft'=>'Draft','active'=>'Aktif','expired'=>'Expired','terminated'=>'Diakhiri'];
$statusBadge = ['draft'=>'secondary','active'=>'success','expired'=>'danger','terminated'=>'danger'];
$mallLabel   = [1=>'eWalk', 2=>'Pentacity'];
?>
<div class="container-fluid py-4">
    <div class="mb-3">
        <nav aria-label="breadcrumb"><ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="<?= base_url('legal') ?>">Legal</a></li>
            <li class="breadcrumb-item"><a href="<?= base_url('legal/psm-developer') ?>">PSM Developer</a></li>
            <li class="breadcrumb-item active"><?= esc($row['nomor_psm']) ?></li>
        </ol></nav>
        <div class="d-flex align-items-center justify-content-between">
            <h4 class="fw-bold mb-0"><?= esc($row['nama_developer']) ?></h4>
            <?php if ($canEdit): ?>
            <div class="d-flex gap-2">
                <a href="<?= base_url('legal/psm-developer/'.$row['id'].'/edit') ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil me-1"></i>Edit</a>
                <form action="<?= base_url('legal/psm-developer/'.$row['id'].'/delete') ?>" method="post" onsubmit="return confirm('Hapus PSM Developer ini?')">
                    <?= csrf_field() ?><button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash me-1"></i>Hapus</button>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if (session()->getFlashdata('success')): ?><div class="alert alert-success"><?= session()->getFlashdata('success') ?></div><?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?><div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div><?php endif; ?>

    <div class="row g-3">
        <div class="col-md-8">
            <div class="card mb-3">
                <div class="card-header fw-semibold">Detail PSM Developer</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="text-muted small">NOMOR PSM</div>
                            <div class="fw-medium"><?= esc($row['nomor_psm']) ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">NAMA DEVELOPER</div>
                            <div class="fw-medium"><?= esc($row['nama_developer']) ?></div>
                        </div>
                        <div class="col-12">
                            <div class="text-muted small">OBJEK PERJANJIAN</div>
                            <div><?= esc($row['objek_perjanjian']) ?></div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small">MALL</div>
                            <div><?= $row['mall_id'] ? ($mallLabel[$row['mall_id']] ?? '—') : 'Keduanya' ?></div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small">STATUS</div>
                            <?php $sc = $statusBadge[$row['status']] ?? 'secondary'; ?>
                            <span class="badge bg-<?= $sc ?>-subtle text-<?= $sc ?>"><?= $statusLabel[$row['status']] ?? esc($row['status']) ?></span>
                        </div>
                        <?php if ($row['nilai'] !== null && $row['nilai'] !== ''): ?>
                        <div class="col-md-4">
                            <div class="text-muted small">NILAI</div>
                            <div class="fw-medium">Rp <?= number_format($row['nilai'], 0, ',', '.') ?></div>
                        </div>
                        <?php endif; ?>
                        <div class="col-md-4">
                            <div class="text-muted small">TANGGAL MULAI</div>
                            <div><?= $row['tanggal_mulai'] ? date('d M Y', strtotime($row['tanggal_mulai'])) : '—' ?></div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small">TANGGAL BERAKHIR</div>
                            <div><?= $row['tanggal_berakhir'] ? date('d M Y', strtotime($row['tanggal_berakhir'])) : '—' ?></div>
                        </div>
                        <?php if ($row['catatan']): ?>
                        <div class="col-12">
                            <div class="text-muted small">CATATAN</div>
                            <div><?= nl2br(esc($row['catatan'])) ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($row['creator_name'])): ?>
                        <div class="col-12">
                            <div class="text-muted small">DIBUAT OLEH</div>
                            <div><?= esc($row['creator_name']) ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?= view('legal/partials/document_list', ['documents' => $documents, 'entity_type' => 'psm_developer', 'entity_id' => $row['id'], 'canEdit' => $canEdit]) ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
