<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <div class="mb-3">
        <nav aria-label="breadcrumb" class="d-none d-md-block"><ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="<?= base_url('legal') ?>">Legal</a></li>
            <li class="breadcrumb-item"><a href="<?= base_url('legal/reviews') ?>">Review Kontrak</a></li>
            <li class="breadcrumb-item"><a href="<?= base_url('legal/reviews/'.$review['id']) ?>"><?= esc($review['judul']) ?></a></li>
            <li class="breadcrumb-item active">Arsipkan</li>
        </ol></nav>
        <h4 class="fw-bold mb-0">Arsipkan ke Legal</h4>
        <p class="text-muted small">Simpan dokumen yang sudah Final ke salah satu modul arsip Legal.</p>
    </div>
    <div class="card" style="max-width:480px"><div class="card-body">
        <p class="text-muted small mb-3">Review: <strong><?= esc($review['judul']) ?></strong></p>
        <p class="mb-3">Arsipkan ke modul mana?</p>
        <div class="d-grid gap-2">
            <a href="<?= base_url('legal/permits/new?from_review='.$review['id']) ?>" class="btn btn-outline-primary">
                <i class="bi bi-patch-check me-2"></i>Perizinan & Lisensi
            </a>
            <a href="<?= base_url('legal/spk/new?from_review='.$review['id']) ?>" class="btn btn-outline-primary">
                <i class="bi bi-file-earmark-text me-2"></i>Review SPK
            </a>
            <a href="<?= base_url('legal/pks/new?from_review='.$review['id']) ?>" class="btn btn-outline-primary">
                <i class="bi bi-handshake me-2"></i>Perjanjian Kerja Sama
            </a>
            <a href="<?= base_url('legal/psm-mall/new?from_review='.$review['id']) ?>" class="btn btn-outline-primary">
                <i class="bi bi-shop me-2"></i>PSM Mall
            </a>
            <a href="<?= base_url('legal/psm-developer/new?from_review='.$review['id']) ?>" class="btn btn-outline-primary">
                <i class="bi bi-building me-2"></i>PSM Developer
            </a>
            <a href="<?= base_url('legal/psm-gudang/new?from_review='.$review['id']) ?>" class="btn btn-outline-primary">
                <i class="bi bi-box-seam me-2"></i>PSM Gudang
            </a>
            <a href="<?= base_url('legal/kontrak-pameran/new?from_review='.$review['id']) ?>" class="btn btn-outline-primary">
                <i class="bi bi-easel me-2"></i>Kontrak Sewa Pameran
            </a>
        </div>
        <div class="mt-3">
            <a href="<?= base_url('legal/reviews/'.$review['id']) ?>" class="btn btn-light w-100">Kembali</a>
        </div>
    </div></div>
</div>
<?= $this->endSection() ?>
