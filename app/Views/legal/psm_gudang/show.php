<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
$statusLabel = ['draft' => 'Draft', 'active' => 'Aktif', 'expired' => 'Expired', 'terminated' => 'Diakhiri'];
$statusBadge = ['draft' => 'secondary', 'active' => 'success', 'expired' => 'danger', 'terminated' => 'danger'];
$periodeLabel = ['bulanan' => 'Bulanan', 'triwulan' => 'Triwulan', 'tahunan' => 'Tahunan'];
$s = $row['status'] ?? 'draft';
?>

<div class="container-fluid py-4">
    <div class="mb-3">
        <nav aria-label="breadcrumb"><ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="<?= base_url('legal') ?>">Legal</a></li>
            <li class="breadcrumb-item"><a href="<?= base_url('legal/psm-gudang') ?>">PSM Gudang</a></li>
            <li class="breadcrumb-item active"><?= esc($row['nomor_psm']) ?></li>
        </ol></nav>
        <div class="d-flex align-items-center justify-content-between">
            <h4 class="fw-bold mb-0"><?= esc($row['nomor_psm']) ?></h4>
            <?php if ($canEdit): ?>
            <div class="d-flex gap-2">
                <a href="<?= base_url('legal/psm-gudang/' . $row['id'] . '/edit') ?>" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-pencil me-1"></i>Edit
                </a>
                <form action="<?= base_url('legal/psm-gudang/' . $row['id'] . '/delete') ?>" method="post"
                      onsubmit="return confirm('Hapus PSM Gudang ini? Data dokumen terkait juga akan dihapus.')">
                    <?= csrf_field() ?>
                    <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash me-1"></i>Hapus</button>
                </form>
            </div>
            <?php endif; ?>
        </div>
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

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header fw-semibold">Detail PSM Gudang</div>
                <div class="card-body">
                    <div class="row g-3">

                        <div class="col-md-6">
                            <div class="text-muted small">NOMOR PSM</div>
                            <div class="fw-medium"><?= esc($row['nomor_psm']) ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">PENYEWA</div>
                            <div class="fw-medium"><?= esc($row['nama_penyewa']) ?></div>
                        </div>

                        <div class="col-12">
                            <div class="text-muted small">LOKASI GUDANG</div>
                            <div><?= esc($row['lokasi_gudang']) ?></div>
                        </div>

                        <div class="col-md-3">
                            <div class="text-muted small">STATUS</div>
                            <span class="badge bg-<?= $statusBadge[$s] ?? 'secondary' ?>-subtle text-<?= $statusBadge[$s] ?? 'secondary' ?>">
                                <?= $statusLabel[$s] ?? esc($s) ?>
                            </span>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted small">LUAS</div>
                            <div><?= $row['luas_m2'] !== null ? number_format((float)$row['luas_m2'], 2, ',', '.') . ' m²' : '<span class="text-muted">—</span>' ?></div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted small">PERIODE PEMBAYARAN</div>
                            <div><?= $row['periode_pembayaran'] ? ($periodeLabel[$row['periode_pembayaran']] ?? esc($row['periode_pembayaran'])) : '<span class="text-muted">—</span>' ?></div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted small">NILAI SEWA</div>
                            <div class="fw-medium"><?= $row['nilai_sewa'] ? 'Rp ' . number_format((float)$row['nilai_sewa'], 0, ',', '.') : '<span class="text-muted">—</span>' ?></div>
                        </div>

                        <div class="col-md-4">
                            <div class="text-muted small">TANGGAL MULAI</div>
                            <div><?= $row['tanggal_mulai'] ? date('d M Y', strtotime($row['tanggal_mulai'])) : '<span class="text-muted">—</span>' ?></div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small">TANGGAL BERAKHIR</div>
                            <div><?= $row['tanggal_berakhir'] ? date('d M Y', strtotime($row['tanggal_berakhir'])) : '<span class="text-muted">—</span>' ?></div>
                        </div>

                        <?php if ($row['catatan']): ?>
                        <div class="col-12">
                            <div class="text-muted small">CATATAN</div>
                            <div><?= nl2br(esc($row['catatan'])) ?></div>
                        </div>
                        <?php endif; ?>

                        <?php if ($row['creator_name'] ?? ''): ?>
                        <div class="col-12">
                            <div class="text-muted small">DIBUAT OLEH</div>
                            <div class="small"><?= esc($row['creator_name']) ?></div>
                        </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>

            <?= view('legal/partials/document_list', [
                'documents'   => $documents,
                'entity_type' => 'psm_gudang',
                'entity_id'   => $row['id'],
                'canEdit'     => $canEdit,
            ]) ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
