<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4 fade-up" style="animation-delay:.05s">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-link-45deg me-2"></i>Penautan Akun</h4>
        <small class="text-muted">Sandingkan akun aplikasi lain dengan data karyawan. Sistem mengusulkan, admin memutuskan.</small>
    </div>
</div>

<div class="alert alert-light border d-flex gap-2 fade-up" style="animation-delay:.1s">
    <i class="bi bi-info-circle text-primary mt-1"></i>
    <div class="small">
        <strong>Tidak ada penautan otomatis.</strong>
        Sistem hanya mengusulkan pasangan beserta tingkat keyakinannya — setiap tautan
        lahir dari keputusan admin. Usul yang kuat datang tercentang untuk memudahkan,
        tapi mencentang bukan menyimpan.
        <br>
        Tautan menentukan akun mana yang dinonaktifkan ketika karyawan resign, jadi
        tautan yang salah akan menonaktifkan akun orang lain.
    </div>
</div>

<div class="row g-3">
<?php foreach ($apps as $i => $a): ?>
<div class="col-md-6 col-lg-4 fade-up" style="animation-delay:<?= .15 + $i * .05 ?>s">
    <div class="card h-100">
        <div class="card-body">
            <div class="d-flex align-items-center gap-2 mb-2">
                <i class="bi <?= esc($a['ikon']) ?> fs-5 text-primary"></i>
                <h6 class="fw-bold mb-0"><?= esc($a['nama']) ?></h6>
            </div>

            <div class="small text-muted mb-3">
                <?= (int) $a['jumlah_tertaut'] ?> akun tertaut
            </div>

            <?php if (! $a['terkonfigurasi']): ?>
                <div class="small text-muted">
                    <i class="bi bi-slash-circle me-1"></i>Belum bisa ditinjau
                    <div class="mt-1" style="font-size:.78rem">
                        Butuh <code>apps.url</code> dan token <code>sync.token.<?= esc($a['kode']) ?></code>
                        di <code>.env</code>. Tanpa keduanya MIC tidak punya jalan membaca
                        daftar akunnya.
                    </div>
                </div>
            <?php else: ?>
                <a href="<?= base_url('penautan-akun/' . $a['kode']) ?>" class="btn btn-sm btn-primary">
                    <i class="bi bi-search me-1"></i>Tinjau &amp; tautkan
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>

<?= $this->endSection() ?>
