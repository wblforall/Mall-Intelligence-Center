<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4 fade-up" style="animation-delay:.05s">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-grid-3x3-gap me-2"></i>Akses Aplikasi</h4>
        <small class="text-muted">Default akses kelima sistem per departemen — karyawan baru otomatis mewarisi grant di sini.</small>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body py-2 px-3">
        <small class="text-muted">
            <strong>Unit bisnis:</strong>
            <?php foreach ($companies as $c): ?>
                <span class="badge text-bg-light border me-1"><?= esc($c['kode']) ?></span>
            <?php endforeach; ?>
            &nbsp;·&nbsp;
            <strong>Aplikasi:</strong>
            <?php foreach ($apps as $a): ?>
                <span class="badge text-bg-light border me-1"><i class="bi <?= esc($a['ikon']) ?>"></i> <?= esc($a['nama']) ?></span>
            <?php endforeach; ?>
        </small>
    </div>
</div>

<div class="row g-3">
<?php foreach ($depts as $i => $d): ?>
<div class="col-md-6 col-lg-4 fade-up" style="animation-delay:<?= .1 + $i * .05 ?>s">
<div class="card h-100">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <div>
                <h6 class="fw-bold mb-0"><?= esc($d['name']) ?></h6>
                <span class="badge <?= $d['tingkat'] === 'holding' ? 'text-bg-info-subtle text-info-emphasis' : 'text-bg-secondary-subtle text-secondary-emphasis' ?>">
                    <?= $d['tingkat'] === 'holding' ? 'Holding — lintas unit' : 'Per unit bisnis' ?>
                </span>
            </div>
            <a href="<?= base_url('app-access/'.$d['id'].'/edit') ?>" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-sliders2"></i>
            </a>
        </div>
        <div class="mt-3">
            <div class="fw-bold fs-5"><?= $jumlahGrant[$d['id']] ?? 0 ?></div>
            <small class="text-muted">Grant akses aplikasi aktif</small>
        </div>
    </div>
</div>
</div>
<?php endforeach; ?>
</div>

<?= $this->endSection() ?>
