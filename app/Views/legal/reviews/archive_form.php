<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <div class="mb-3">
        <nav aria-label="breadcrumb"><ol class="breadcrumb mb-1 small">
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
            <a href="<?= base_url('legal/contracts/new?from_review='.$review['id']) ?>" class="btn btn-outline-primary">
                <i class="bi bi-briefcase me-2"></i>Kontrak Vendor
            </a>
            <a href="<?= base_url('legal/leases/new?from_review='.$review['id']) ?>" class="btn btn-outline-primary">
                <i class="bi bi-building me-2"></i>Perjanjian Sewa
            </a>
        </div>
        <div class="mt-3">
            <a href="<?= base_url('legal/reviews/'.$review['id']) ?>" class="btn btn-light w-100">Kembali</a>
        </div>
    </div></div>
</div>
<?= $this->endSection() ?>
