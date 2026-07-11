<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$statusLabel = ['draft'=>'Draft','aktif'=>'Aktif','selesai'=>'Selesai','batal'=>'Batal'];
$statusBadge = ['draft'=>'secondary','aktif'=>'primary','selesai'=>'success','batal'=>'danger'];
?>

<div class="container-fluid py-4">

    <div class="mb-3">
        <nav aria-label="breadcrumb" class="d-none d-md-block"><ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="<?= base_url('legal') ?>">Legal</a></li>
            <li class="breadcrumb-item"><a href="<?= base_url('legal/spk') ?>">Review SPK</a></li>
            <li class="breadcrumb-item active"><?= esc($row['nomor_spk']) ?></li>
        </ol></nav>
        <div class="d-flex align-items-center justify-content-between">
            <h4 class="fw-bold mb-0"><?= esc($row['nomor_spk']) ?></h4>
            <?php if ($canEdit): ?>
            <div class="d-flex gap-2">
                <a href="<?= base_url('legal/spk/' . $row['id'] . '/edit') ?>" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-pencil me-1"></i> Edit
                </a>
                <form action="<?= base_url('legal/spk/' . $row['id'] . '/delete') ?>" method="post"
                      onsubmit="return confirm('Hapus SPK ini beserta semua dokumennya?')">
                    <?= csrf_field() ?>
                    <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash me-1"></i> Hapus</button>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
    <?php endif; ?>

    <div class="row g-3">
        <div class="col-md-8">

            <div class="card mb-3">
                <div class="card-header fw-semibold">Detail SPK</div>
                <div class="card-body">
                    <div class="row g-3">

                        <div class="col-md-6">
                            <div class="text-muted small text-uppercase">Nomor SPK</div>
                            <div class="fw-medium"><?= esc($row['nomor_spk']) ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small text-uppercase">Vendor</div>
                            <div class="fw-medium"><?= esc($row['nama_vendor']) ?></div>
                        </div>

                        <?php if ($row['deskripsi_pekerjaan']): ?>
                        <div class="col-12">
                            <div class="text-muted small text-uppercase">Deskripsi Pekerjaan</div>
                            <div><?= nl2br(esc($row['deskripsi_pekerjaan'])) ?></div>
                        </div>
                        <?php endif; ?>

                        <div class="col-md-4">
                            <div class="text-muted small text-uppercase">Nilai SPK</div>
                            <div class="fw-semibold">
                                <?= $row['nilai_spk'] ? 'Rp ' . number_format($row['nilai_spk'], 0, ',', '.') : '—' ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small text-uppercase">Status</div>
                            <span class="badge bg-<?= $statusBadge[$row['status']] ?>-subtle text-<?= $statusBadge[$row['status']] ?>">
                                <?= $statusLabel[$row['status']] ?>
                            </span>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small text-uppercase">PIC</div>
                            <div><?= esc($pic['name'] ?? '—') ?></div>
                        </div>

                        <div class="col-md-4">
                            <div class="text-muted small text-uppercase">Tanggal Terbit</div>
                            <div><?= $row['tanggal_terbit'] ? date('d M Y', strtotime($row['tanggal_terbit'])) : '—' ?></div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small text-uppercase">Tanggal Selesai</div>
                            <div><?= $row['tanggal_selesai'] ? date('d M Y', strtotime($row['tanggal_selesai'])) : '—' ?></div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small text-uppercase">Dibuat oleh</div>
                            <div class="text-muted small"><?= esc($row['creator_name'] ?? '—') ?></div>
                        </div>

                        <?php if ($row['catatan']): ?>
                        <div class="col-12">
                            <div class="text-muted small text-uppercase">Catatan</div>
                            <div><?= nl2br(esc($row['catatan'])) ?></div>
                        </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>

            <?= view('legal/partials/document_list', [
                'documents'   => $documents,
                'entity_type' => 'spk',
                'entity_id'   => $row['id'],
                'canEdit'     => $canEdit,
            ]) ?>

        </div>
    </div>

</div>
<?= $this->endSection() ?>
