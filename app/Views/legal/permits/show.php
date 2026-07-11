<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$statusLabel = ['active'=>'Aktif','expired'=>'Expired','pending_renewal'=>'Pending Renewal','revoked'=>'Dicabut'];
$statusBadge = ['active'=>'success','expired'=>'danger','pending_renewal'=>'warning','revoked'=>'danger'];
$mallLabel   = [1=>'eWalk', 2=>'Pentacity'];
?>

<div class="container-fluid py-4">
    <div class="mb-3">
        <nav aria-label="breadcrumb" class="d-none d-md-block"><ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="<?= base_url('legal') ?>">Legal</a></li>
            <li class="breadcrumb-item"><a href="<?= base_url('legal/permits') ?>">Perizinan</a></li>
            <li class="breadcrumb-item active"><?= esc($permit['nama_izin']) ?></li>
        </ol></nav>
        <div class="d-flex align-items-center justify-content-between">
            <h4 class="fw-bold mb-0"><?= esc($permit['nama_izin']) ?></h4>
            <?php if ($canEdit): ?>
            <div class="d-flex gap-2">
                <a href="<?= base_url('legal/permits/'.$permit['id'].'/edit') ?>" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-pencil me-1"></i> Edit
                </a>
                <form action="<?= base_url('legal/permits/'.$permit['id'].'/delete') ?>" method="post"
                      onsubmit="return confirm('Hapus perizinan ini beserta semua dokumennya?')">
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

    <div class="row g-3">
        <div class="col-md-8">
            <div class="card mb-3">
                <div class="card-header fw-semibold">Detail</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="text-muted small text-uppercase">Nomor Izin</div>
                            <div class="fw-medium"><?= esc($permit['nomor_izin']) ?></div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted small text-uppercase">Jenis</div>
                            <div><?= $permit['jenis_izin'] === 'HO_SITU' ? 'HO/SITU' : esc($permit['jenis_izin']) ?></div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted small text-uppercase">Mall</div>
                            <div><?= $mallLabel[$permit['mall_id']] ?? '—' ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small text-uppercase">Instansi Penerbit</div>
                            <div><?= esc($permit['instansi_penerbit'] ?? '—') ?></div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted small text-uppercase">Tanggal Terbit</div>
                            <div><?= date('d M Y', strtotime($permit['tanggal_terbit'])) ?></div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted small text-uppercase">Berlaku Sampai</div>
                            <div><?= $permit['tanggal_berakhir'] ? date('d M Y', strtotime($permit['tanggal_berakhir'])) : '<span class="text-muted">Berlaku Tetap</span>' ?></div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted small text-uppercase">Status</div>
                            <span class="badge bg-<?= $statusBadge[$permit['status']] ?>-subtle text-<?= $statusBadge[$permit['status']] ?>"><?= $statusLabel[$permit['status']] ?></span>
                        </div>
                        <?php if ($permit['catatan']): ?>
                        <div class="col-12">
                            <div class="text-muted small text-uppercase">Catatan</div>
                            <div class="text-body"><?= nl2br(esc($permit['catatan'])) ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?= view('legal/partials/document_list', [
                'documents'   => $documents,
                'entity_type' => 'permit',
                'entity_id'   => $permit['id'],
                'canEdit'     => $canEdit,
            ]) ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
