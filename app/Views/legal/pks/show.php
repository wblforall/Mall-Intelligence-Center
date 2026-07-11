<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$statusLabel = ['draft'=>'Draft','active'=>'Aktif','expired'=>'Expired','terminated'=>'Dihentikan'];
$statusBadge = ['draft'=>'secondary','active'=>'success','expired'=>'danger','terminated'=>'danger'];
?>

<div class="container-fluid py-4">

    <div class="mb-3">
        <nav aria-label="breadcrumb" class="d-none d-md-block"><ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="<?= base_url('legal') ?>">Legal</a></li>
            <li class="breadcrumb-item"><a href="<?= base_url('legal/pks') ?>">Perjanjian Kerja Sama</a></li>
            <li class="breadcrumb-item active"><?= esc($row['nomor_pks']) ?></li>
        </ol></nav>
        <div class="d-flex align-items-center justify-content-between">
            <h4 class="fw-bold mb-0"><?= esc($row['nomor_pks']) ?></h4>
            <?php if ($canEdit): ?>
            <div class="d-flex gap-2">
                <a href="<?= base_url('legal/pks/' . $row['id'] . '/edit') ?>" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-pencil me-1"></i> Edit
                </a>
                <form action="<?= base_url('legal/pks/' . $row['id'] . '/delete') ?>" method="post"
                      onsubmit="return confirm('Hapus PKS ini beserta semua dokumennya?')">
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
                <div class="card-header fw-semibold">Detail PKS</div>
                <div class="card-body">
                    <div class="row g-3">

                        <div class="col-md-6">
                            <div class="text-muted small text-uppercase">Nomor PKS</div>
                            <div class="fw-medium"><?= esc($row['nomor_pks']) ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small text-uppercase">Pihak Kedua</div>
                            <div class="fw-medium"><?= esc($row['pihak_kedua']) ?></div>
                        </div>

                        <?php if ($row['ruang_lingkup']): ?>
                        <div class="col-12">
                            <div class="text-muted small text-uppercase">Ruang Lingkup</div>
                            <div><?= nl2br(esc($row['ruang_lingkup'])) ?></div>
                        </div>
                        <?php endif; ?>

                        <div class="col-md-4">
                            <div class="text-muted small text-uppercase">Nilai</div>
                            <div class="fw-semibold">
                                <?= $row['nilai'] ? 'Rp ' . number_format($row['nilai'], 0, ',', '.') : '—' ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small text-uppercase">Status</div>
                            <span class="badge bg-<?= $statusBadge[$row['status']] ?>-subtle text-<?= $statusBadge[$row['status']] ?>">
                                <?= $statusLabel[$row['status']] ?>
                            </span>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small text-uppercase">Dibuat oleh</div>
                            <div class="text-muted small"><?= esc($row['creator_name'] ?? '—') ?></div>
                        </div>

                        <div class="col-md-4">
                            <div class="text-muted small text-uppercase">Tanggal Mulai</div>
                            <div><?= $row['tanggal_mulai'] ? date('d M Y', strtotime($row['tanggal_mulai'])) : '—' ?></div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small text-uppercase">Tanggal Berakhir</div>
                            <?php
                            $berakhir = $row['tanggal_berakhir'];
                            if ($berakhir):
                                $d = (int)(new DateTime())->diff(new DateTime($berakhir))->format('%r%a');
                                $fmt = date('d M Y', strtotime($berakhir));
                                if ($d < 0):
                            ?>
                            <div><?= $fmt ?> <span class="badge bg-danger-subtle text-danger ms-1">Expired <?= abs($d) ?> hari lalu</span></div>
                            <?php elseif ($d <= 7): ?>
                            <div><?= $fmt ?> <span class="badge bg-danger-subtle text-danger ms-1"><i class="bi bi-exclamation-triangle-fill me-1"></i>H-<?= $d ?></span></div>
                            <?php elseif ($d <= 30): ?>
                            <div><?= $fmt ?> <span class="badge bg-warning-subtle text-warning ms-1"><i class="bi bi-clock me-1"></i>H-<?= $d ?></span></div>
                            <?php else: ?>
                            <div><?= $fmt ?></div>
                            <?php endif; else: ?>
                            <div class="text-muted">—</div>
                            <?php endif; ?>
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
                'entity_type' => 'pks',
                'entity_id'   => $row['id'],
                'canEdit'     => $canEdit,
            ]) ?>

        </div>
    </div>

</div>
<?= $this->endSection() ?>
