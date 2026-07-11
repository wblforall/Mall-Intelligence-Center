<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
$statusLabel = ['draft' => 'Draft', 'aktif' => 'Aktif', 'selesai' => 'Selesai', 'batal' => 'Batal'];
$statusBadge = ['draft' => 'secondary', 'aktif' => 'primary', 'selesai' => 'success', 'batal' => 'danger'];
$mallLabel   = [1 => 'eWalk', 2 => 'Pentacity'];
$s = $row['status'] ?? 'draft';

$tglRange = '';
if ($row['tanggal_mulai'] && $row['tanggal_selesai']) {
    $mulai   = new DateTime($row['tanggal_mulai']);
    $selesai = new DateTime($row['tanggal_selesai']);
    if ($mulai->format('Y') === $selesai->format('Y')) {
        $tglRange = $mulai->format('d M') . ' – ' . $selesai->format('d M Y');
    } else {
        $tglRange = $mulai->format('d M Y') . ' – ' . $selesai->format('d M Y');
    }
} elseif ($row['tanggal_mulai']) {
    $tglRange = date('d M Y', strtotime($row['tanggal_mulai']));
}
?>

<div class="container-fluid py-4">
    <div class="mb-3">
        <nav aria-label="breadcrumb" class="d-none d-md-block"><ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="<?= base_url('legal') ?>">Legal</a></li>
            <li class="breadcrumb-item"><a href="<?= base_url('legal/kontrak-pameran') ?>">Kontrak Sewa Pameran</a></li>
            <li class="breadcrumb-item active"><?= esc($row['nomor_kontrak']) ?></li>
        </ol></nav>
        <div class="d-flex align-items-center justify-content-between">
            <h4 class="fw-bold mb-0"><?= esc($row['nama_event']) ?></h4>
            <?php if ($canEdit): ?>
            <div class="d-flex gap-2">
                <a href="<?= base_url('legal/kontrak-pameran/' . $row['id'] . '/edit') ?>" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-pencil me-1"></i>Edit
                </a>
                <form action="<?= base_url('legal/kontrak-pameran/' . $row['id'] . '/delete') ?>" method="post"
                      onsubmit="return confirm('Hapus kontrak pameran ini? Data dokumen terkait juga akan dihapus.')">
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
                <div class="card-header fw-semibold">Detail Kontrak Sewa Pameran</div>
                <div class="card-body">
                    <div class="row g-3">

                        <div class="col-md-6">
                            <div class="text-muted small">NOMOR KONTRAK</div>
                            <div class="fw-medium"><?= esc($row['nomor_kontrak']) ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">PENYELENGGARA</div>
                            <div class="fw-medium"><?= esc($row['nama_penyelenggara']) ?></div>
                        </div>

                        <div class="col-12">
                            <div class="text-muted small">NAMA EVENT</div>
                            <div><?= esc($row['nama_event']) ?></div>
                        </div>

                        <div class="col-md-6">
                            <div class="text-muted small">LOKASI / AREA</div>
                            <div><?= $row['lokasi_area'] ? esc($row['lokasi_area']) : '<span class="text-muted">—</span>' ?></div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted small">MALL</div>
                            <div><?= $row['mall_id'] ? ($mallLabel[(int)$row['mall_id']] ?? '—') : '<span class="text-muted">—</span>' ?></div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted small">STATUS</div>
                            <span class="badge bg-<?= $statusBadge[$s] ?? 'secondary' ?>-subtle text-<?= $statusBadge[$s] ?? 'secondary' ?>">
                                <?= $statusLabel[$s] ?? esc($s) ?>
                            </span>
                        </div>

                        <div class="col-md-6">
                            <div class="text-muted small">TANGGAL MULAI</div>
                            <div><?= $row['tanggal_mulai'] ? date('d M Y', strtotime($row['tanggal_mulai'])) : '<span class="text-muted">—</span>' ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">TANGGAL SELESAI</div>
                            <div><?= $row['tanggal_selesai'] ? date('d M Y', strtotime($row['tanggal_selesai'])) : '<span class="text-muted">—</span>' ?></div>
                        </div>

                        <?php if ($tglRange): ?>
                        <div class="col-12">
                            <div class="text-muted small">PERIODE</div>
                            <div><?= esc($tglRange) ?></div>
                        </div>
                        <?php endif; ?>

                        <div class="col-md-6">
                            <div class="text-muted small">NILAI SEWA</div>
                            <div class="fw-medium"><?= $row['nilai_sewa'] ? 'Rp ' . number_format((float)$row['nilai_sewa'], 0, ',', '.') : '<span class="text-muted">—</span>' ?></div>
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
                'entity_type' => 'kontrak_pameran',
                'entity_id'   => $row['id'],
                'canEdit'     => $canEdit,
            ]) ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
