<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?= $this->include('budget_dept/_kepala') ?>
<?php
use App\Libraries\RabSusun;

$STATUS = [
    'draf'       => ['secondary', 'Draf'],
    'diajukan'   => ['warning', 'Diajukan — menunggu TTD'],
    'disetujui'  => ['success', 'Disetujui'],
    'digantikan' => ['light', 'Digantikan revisi'],
    'batal'      => ['light', 'Batal'],
];
$STATUS_LAP = ['diajukan' => ['warning', 'Menunggu TTD'], 'disahkan' => ['success', 'Disahkan']];
$lencana = fn (array $peta, string $s) => '<span class="badge text-bg-' . $peta[$s][0] . '">' . $peta[$s][1] . '</span>';
$bulanDepan = date('Y-m', strtotime('first day of next month'));
$bulanLalu  = date('Y-m', strtotime('first day of last month'));
?>

<div class="alert alert-light border small mb-3">
    <i class="bi bi-info-circle me-1"></i>
    Isi RAB diambil dari <strong>Event Content</strong> di modul Events (program, biaya, qty × freq × harga satuan, pos).
    Alur: <strong>susun draf</strong> → <strong>ajukan</strong> (isi dibekukan) → cetak PDF &amp; tanda tangani lewat <strong>PAM e-Sign</strong>
    → <strong>admin E&amp;P mengunggah</strong> PDF yang sudah lengkap tanda tangannya → <strong>disetujui</strong>.
    Perubahan sesudahnya lewat <strong>revisi</strong>. Kegiatan rutin bulanan dicatat sebagai event berjenis <em>Rutin</em>.
</div>

<?php if ($tanpaPos): ?>
<div class="alert alert-warning small">
    <i class="bi bi-exclamation-triangle me-1"></i>
    Realisasi event berikut belum punya <strong>pos budget</strong>, jadi belum masuk Budget Departemen. Tentukan pos-nya di Edit Event (atau per item):
    <ul class="mb-0 mt-1">
        <?php foreach ($tanpaPos as $t): ?>
        <li><a href="<?= base_url('events/' . $t['id'] . '/content') ?>"><?= esc($t['name']) ?></a> — <?= $t['n'] ?> transaksi, Rp <?= number_format((float) $t['nilai'], 0, ',', '.') ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div class="row g-3">
<div class="col-lg-8">
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-file-earmark-text me-2"></i>Dokumen RAB</h6>
            <?php if ($canEdit): ?>
            <form method="POST" action="<?= $url('rab/buat') ?>" class="d-flex align-items-center gap-1 flex-wrap">
                <?= csrf_field() ?>
                <label class="small text-muted" for="rab-mulai">Periode</label>
                <input type="month" id="rab-mulai" name="mulai" class="form-control form-control-sm" style="width:150px" value="<?= $bulanDepan ?>" required>
                <span class="small text-muted">s.d.</span>
                <input type="month" name="selesai" class="form-control form-control-sm" style="width:150px" aria-label="Sampai bulan"
                       value="<?= date('Y-m', strtotime($bulanDepan . '-01 +1 month')) ?>" required>
                <button class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i>Susun RAB</button>
            </form>
            <?php endif; ?>
        </div>
        <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
            <thead><tr><th>Periode</th><th>Nomor</th><th>Status</th><th>Diajukan</th><th>Disetujui</th><th></th></tr></thead>
            <tbody>
            <?php if (! $rantai): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">Belum ada RAB.</td></tr>
            <?php endif; ?>
            <?php foreach ($rantai as $versi): $d = $versi[0]; ?>
            <tr>
                <td class="fw-semibold text-nowrap"><?= RabSusun::labelPeriode($d['periode_mulai'], $d['periode_selesai']) ?></td>
                <td class="text-nowrap"><?= esc($d['nomor'] ?: 'v' . $d['versi'] . ' (draf)') ?></td>
                <td><?= $lencana($STATUS, $d['status']) ?></td>
                <td class="text-nowrap"><?= $d['diajukan_at'] ? date('d/m/Y', strtotime($d['diajukan_at'])) . '<div class="text-muted">' . esc($d['pengaju']) . '</div>' : '–' ?></td>
                <td class="text-nowrap"><?= $d['disetujui_at'] ? date('d/m/Y', strtotime($d['disetujui_at'])) . '<div class="text-muted">' . esc($d['penyetuju']) . '</div>' : '–' ?></td>
                <td class="text-end"><a href="<?= $url('rab/' . $d['id']) ?>" class="btn btn-sm btn-outline-primary">Buka</a></td>
            </tr>
            <?php foreach (array_slice($versi, 1) as $v): ?>
            <tr class="text-muted">
                <td></td>
                <td class="ps-3"><i class="bi bi-arrow-return-right me-1"></i><?= esc($v['nomor'] ?: 'v' . $v['versi']) ?></td>
                <td><?= $lencana($STATUS, $v['status']) ?></td>
                <td><?= $v['diajukan_at'] ? date('d/m/Y', strtotime($v['diajukan_at'])) : '–' ?></td>
                <td><?= $v['disetujui_at'] ? date('d/m/Y', strtotime($v['disetujui_at'])) : '–' ?></td>
                <td class="text-end"><a href="<?= $url('rab/' . $v['id']) ?>" class="btn btn-sm btn-link">Lihat</a></td>
            </tr>
            <?php endforeach; ?>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<div class="col-lg-4">
    <div class="card">
        <div class="card-header"><h6 class="mb-0 fw-semibold"><i class="bi bi-clipboard-check me-2"></i>Laporan Realisasi Bulanan</h6></div>
        <div class="card-body">
            <?php if ($canEdit): ?>
            <form method="POST" action="<?= $url('rab/laporan/buat') ?>" class="d-flex gap-1 mb-3">
                <?= csrf_field() ?>
                <input type="month" name="bulan" class="form-control form-control-sm" value="<?= $bulanLalu ?>" max="<?= $bulanLalu ?>" required aria-label="Bulan laporan">
                <button class="btn btn-sm btn-primary text-nowrap"><i class="bi bi-plus-lg me-1"></i>Buat</button>
            </form>
            <div class="form-text mb-3">Dibuat setelah bulannya selesai: RAB yang disetujui vs realisasi Event Content. Setelah disahkan, realisasi bulan itu terkunci.</div>
            <?php endif; ?>
            <?php if (! $laporan): ?><div class="text-muted small">Belum ada laporan.</div><?php endif; ?>
            <?php foreach ($laporan as $l): ?>
            <div class="border rounded p-2 mb-2 small">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div>
                        <div class="fw-semibold"><?= RabSusun::labelBulan($l['bulan']) ?></div>
                        <div class="text-muted"><?= esc($l['nomor']) ?></div>
                    </div>
                    <?= $lencana($STATUS_LAP, $l['status']) ?>
                </div>
                <div class="d-flex gap-1 mt-2 flex-wrap">
                    <a href="<?= $url('rab/laporan/' . $l['id'] . '/cetak') ?>" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-printer me-1"></i>Cetak</a>
                    <?php if ($l['status'] === 'disahkan'): ?>
                    <a href="<?= $url('rab/laporan/' . $l['id'] . '/berkas') ?>" target="_blank" class="btn btn-sm btn-outline-success"><i class="bi bi-file-earmark-check me-1"></i>Berkas TTD</a>
                    <?php elseif ($bolehSetujui): ?>
                    <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#mUnggah"
                            data-aksi="<?= $url('rab/laporan/' . $l['id'] . '/unggah') ?>" data-nomor="<?= esc($l['nomor'], 'attr') ?>">
                        <i class="bi bi-upload me-1"></i>Unggah TTD</button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
</div>

<?= view('budget_dept/_rab_unggah') ?>
<?= $this->endSection() ?>
