<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4 fade-up" style="animation-delay:.05s">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-gear me-2"></i>Pengaturan Sistem</h4>
        <small class="text-muted">Konfigurasi notifikasi email dan parameter aplikasi</small>
    </div>
</div>

<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i><?= session()->getFlashdata('success') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
<div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-circle me-2"></i><?= session()->getFlashdata('error') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<!-- Traffic Summary Recipients -->
<div class="card fade-up mb-4" style="animation-delay:.12s">
    <div class="card-header d-flex align-items-center justify-content-between">
        <div>
            <i class="bi bi-envelope-check me-2 text-info"></i><strong>Email Penerima Traffic Summary</strong>
            <div class="text-muted small">Dikirim otomatis setiap hari pukul 07.00 via cron job.</div>
        </div>
        <a href="<?= base_url('admin/settings/test-email') ?>" class="btn btn-sm btn-outline-info"
           onclick="return confirm('Kirim email tes ke akun Anda?')">
            <i class="bi bi-send me-1"></i>Kirim Tes
        </a>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= base_url('admin/settings/save') ?>">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label small fw-semibold">Daftar Email <span class="text-muted fw-normal">(pisahkan dengan koma, spasi, atau enter)</span></label>
                <textarea name="traffic_emails" class="form-control font-monospace" rows="5"
                          placeholder="email1@domain.com, email2@domain.com"><?= esc(implode("\n", $trafficEmails)) ?></textarea>
                <div class="form-text">Saat ini: <strong><?= count($trafficEmails) ?></strong> penerima terdaftar.</div>
            </div>

            <?php if (! empty($trafficEmails)): ?>
            <div class="mb-3">
                <div class="small fw-semibold text-muted mb-2">Penerima saat ini:</div>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($trafficEmails as $em): ?>
                    <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1" style="font-size:.8rem">
                        <i class="bi bi-envelope me-1"></i><?= esc($em) ?>
                    </span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <button type="submit" class="btn btn-primary btn-sm">
                <i class="bi bi-save me-1"></i>Simpan
            </button>
        </form>
    </div>
</div>

<!-- Cron Job Info -->
<div class="card fade-up" style="animation-delay:.18s">
    <div class="card-header">
        <i class="bi bi-clock-history me-2 text-warning"></i><strong>Pengaturan Cron Job</strong>
    </div>
    <div class="card-body">
        <p class="text-muted small mb-3">Tambahkan perintah berikut ke Cron Job di cPanel untuk menjalankan otomasi email.</p>
        <table class="table table-sm table-bordered align-middle small mb-0">
            <thead class="table-light">
                <tr>
                    <th>Jadwal</th>
                    <th>Perintah</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="font-monospace text-nowrap">0 7 * * *</td>
                    <td><code>/usr/bin/php <?= ROOTPATH ?>spark mic:traffic-summary-email</code></td>
                    <td>Rekap traffic harian, setiap hari 07.00</td>
                </tr>
                <tr>
                    <td class="font-monospace text-nowrap">0 8 * * *</td>
                    <td><code>/usr/bin/php <?= ROOTPATH ?>spark mic:pip-review-reminder</code></td>
                    <td>Pengingat review PIP H-1, setiap hari 08.00</td>
                </tr>
            </tbody>
        </table>
        <div class="alert alert-warning py-2 small mt-3 mb-0">
            <i class="bi bi-exclamation-triangle me-1"></i>
            Pastikan konfigurasi email SMTP sudah benar di file <code>.env</code> di server sebelum mengaktifkan cron.
        </div>
    </div>
</div>

<?= $this->endSection() ?>
