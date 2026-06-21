<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$rp  = fn($n) => $n === null ? '—' : 'Rp ' . number_format((float) $n, 0, ',', '.');
$num = fn($n) => $n === null ? '—' : number_format((float) $n);
$fmtDate = fn($d) => $d ? date('d M Y', strtotime($d)) : '—';
$delta = function ($cap, $fin) {
    if ($cap === null || $fin === null || $fin == 0) return ['t' => '—', 'c' => ''];
    $d = ($cap - $fin) / $fin * 100;
    return ['t' => ($d >= 0 ? '▲' : '▼') . ' ' . number_format(abs($d), 1) . '%', 'c' => $d >= 0 ? 'text-success' : 'text-danger'];
};
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h4 class="mb-0"><i class="bi bi-clipboard2-data me-2"></i>Rekaman vs SPI Final — <span class="text-warning">Analisa</span></h4>
        <div class="text-secondary small">Banding data yang kita rekam (snapshot live) dengan data final SPI</div>
    </div>
    <a href="<?= base_url('parking/occupancy') ?>" class="btn btn-outline-info btn-sm"><i class="bi bi-activity"></i> Okupansi Intraday</a>
</div>

<div class="alert alert-secondary py-2 px-3 small mb-3">
    <i class="bi bi-info-circle"></i> Perbandingan hanya level <b>total</b> (rekaman tak punya per-jenis). Muncul untuk hari yang <b>sudah kita rekam</b> DAN <b>sudah difinalkan SPI</b> (±H-3<?= !empty($latestFinal) ? ', final terakhir ' . $fmtDate($latestFinal) : '' ?>). Beda basis (live counter/gross vs tiket/casual) → selisih itu yang dianalisa, bukan dianggap salah.
</div>

<!-- Tabel harian -->
<div class="card mb-3"><div class="card-body">
    <h6 class="card-title">Perbandingan Harian</h6>
    <?php if ($rows): ?>
    <div class="table-responsive"><table class="table table-sm align-middle mb-0">
        <thead><tr>
            <th>Tanggal</th>
            <?php if ($canVeh): ?><th class="text-end">Masuk (rekam)</th><th class="text-end">Masuk (SPI)</th><th class="text-end">Δ</th><?php endif; ?>
            <?php if ($canRev): ?><th class="text-end">Income (rekam)</th><th class="text-end">Income (SPI)</th><th class="text-end">Δ</th><?php endif; ?>
            <th class="text-end">Status</th>
        </tr></thead>
        <tbody>
        <?php foreach ($rows as $r):
            $dm = $delta($r['capMasuk'], $r['finMasuk']);
            $di = $delta($r['capIncome'], $r['finIncome']); ?>
            <tr>
                <td><?= $fmtDate($r['tanggal']) ?> <?= $r['partial'] ? '<span class="badge bg-secondary">hari berjalan</span>' : '' ?></td>
                <?php if ($canVeh): ?>
                <td class="text-end"><?= $num($r['capMasuk']) ?></td>
                <td class="text-end"><?= $r['finMasuk'] === null ? '<span class="text-secondary">menunggu</span>' : $num($r['finMasuk']) ?></td>
                <td class="text-end <?= $dm['c'] ?>"><?= $dm['t'] ?></td>
                <?php endif; ?>
                <?php if ($canRev): ?>
                <td class="text-end"><?= $rp($r['capIncome']) ?></td>
                <td class="text-end"><?= $r['finIncome'] === null ? '<span class="text-secondary">menunggu</span>' : $rp($r['finIncome']) ?></td>
                <td class="text-end <?= $di['c'] ?>"><?= $di['t'] ?></td>
                <?php endif; ?>
                <td class="text-end"><?= $r['final'] ? '<span class="badge bg-success">final</span>' : '<span class="badge bg-secondary">belum final</span>' ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <div class="text-secondary small mt-2">Δ = (rekaman − SPI) ÷ SPI. "menunggu" = SPI belum finalkan hari itu. "hari berjalan" = rekaman masih parsial.</div>
    <?php else: ?>
    <div class="text-center py-4 text-secondary">
        <div style="font-size:2rem"><i class="bi bi-hourglass-split"></i></div>
        <p class="small mb-0 mt-2">Belum ada rekaman. Data terkumpul lewat cron <code>mic:spi-snapshot</code>; perbandingan vs SPI final muncul beberapa hari setelahnya.</p>
    </div>
    <?php endif; ?>
</div></div>

<!-- Perbandingan per jenis kendaraan -->
<div class="card mb-3"><div class="card-body">
    <h6 class="card-title">Per Jenis Kendaraan — Rekaman vs SPI <span class="text-secondary small fw-normal">(<?= $payDate ? $fmtDate($payDate) : '—' ?>)</span></h6>
    <?php if ($typeCompare): ?>
    <div class="text-secondary small mb-2"><i class="bi bi-info-circle"></i> Masuk rekaman dari dashboard (mobil/motor/box); income rekaman dikelompokkan ke jenis dasar. SPI final per jenis dari arsip harian.</div>
    <div class="table-responsive"><table class="table table-sm align-middle mb-0">
        <thead><tr><th>Jenis</th>
            <?php if ($canVeh): ?><th class="text-end">Masuk (rekam)</th><th class="text-end">Masuk (SPI)</th><th class="text-end">Δ</th><?php endif; ?>
            <?php if ($canRev): ?><th class="text-end">Income (rekam)</th><th class="text-end">Income (SPI)</th><th class="text-end">Δ</th><?php endif; ?>
        </tr></thead>
        <tbody>
        <?php foreach ($typeCompare as $r):
            $dm = $delta($r['capMasuk'], $r['finMasuk']);
            $di = $delta($r['capIncome'], $r['finIncome']); ?>
            <tr>
                <td class="text-capitalize"><?= esc($r['jenis']) ?></td>
                <?php if ($canVeh): ?>
                <td class="text-end"><?= $num($r['capMasuk']) ?></td>
                <td class="text-end"><?= $r['finMasuk'] === null ? '<span class="text-secondary">menunggu</span>' : $num($r['finMasuk']) ?></td>
                <td class="text-end <?= $dm['c'] ?>"><?= $dm['t'] ?></td>
                <?php endif; ?>
                <?php if ($canRev): ?>
                <td class="text-end"><?= $rp($r['capIncome']) ?></td>
                <td class="text-end"><?= $r['finIncome'] === null ? '<span class="text-secondary">menunggu</span>' : $rp($r['finIncome']) ?></td>
                <td class="text-end <?= $di['c'] ?>"><?= $di['t'] ?></td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <div class="text-secondary small mt-2">Pilih tanggal di bagian Metode Pembayaran di bawah (dropdown yang sama).</div>
    <?php else: ?>
    <div class="text-secondary small">Belum ada rekaman per jenis untuk dibandingkan.</div>
    <?php endif; ?>
</div></div>

<?php if ($canRev): ?>
<!-- Perbandingan metode pembayaran -->
<div class="card"><div class="card-body">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
        <h6 class="card-title mb-0">Metode Pembayaran — Rekaman vs SPI</h6>
        <?php if ($payDays): ?>
        <form method="get" class="d-flex align-items-center gap-2">
            <select name="paydate" class="form-select form-select-sm" onchange="this.form.submit()">
                <?php foreach ($payDays as $d): ?>
                <option value="<?= esc($d) ?>" <?= $d === $payDate ? 'selected' : '' ?>><?= $fmtDate($d) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <?php endif; ?>
    </div>
    <?php if ($payCompare): ?>
    <?php if (! $payIsFinal): ?>
    <div class="alert alert-warning py-2 px-3 small mb-2"><i class="bi bi-exclamation-triangle"></i> <strong><?= $fmtDate($payDate) ?> belum final di SPI</strong> (final terakhir <?= $fmtDate($latestFinal) ?>, ±H-3). Kolom SPI sengaja dikosongkan — data <code>spi_payment_daily</code> hari berjalan masih <em>parsial</em>, jadi tak dibandingkan agar tak menyesatkan. Terisi otomatis setelah SPI finalkan tanggal ini.</div>
    <?php else: ?>
    <div class="text-secondary small mb-2"><i class="bi bi-info-circle"></i> Rekaman = snapshot EOD (hari itu); SPI = arsip <code>spi_payment_daily</code> (sering bolong — rekaman bisa menambal).</div>
    <?php endif; ?>
    <div class="table-responsive"><table class="table table-sm align-middle mb-0">
        <thead><tr><th>Metode</th><th class="text-end">Rekaman</th><th class="text-end">SPI Final</th><th class="text-end">Selisih</th></tr></thead>
        <tbody>
        <?php foreach ($payCompare as $p):
            $dd = $delta($p['cap'], $p['fin']); ?>
            <tr>
                <td><?= esc($p['method']) ?></td>
                <td class="text-end"><?= $rp($p['cap']) ?></td>
                <td class="text-end"><?= $p['fin'] === null ? ($payIsFinal ? '<span class="text-secondary">— (kosong di SPI)</span>' : '<span class="text-secondary">menunggu</span>') : $rp($p['fin']) ?></td>
                <td class="text-end <?= $dd['c'] ?>"><?= $dd['t'] ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php else: ?>
    <div class="text-secondary small">Belum ada rekaman metode pembayaran untuk dibandingkan.</div>
    <?php endif; ?>
</div></div>
<?php endif; ?>

<?= $this->endSection() ?>
