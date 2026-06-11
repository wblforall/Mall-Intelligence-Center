<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$rp = fn($n) => 'Rp ' . number_format((int)$n, 0, ',', '.');
$fromFmt = date('d M Y', strtotime($from));
$toFmt   = date('d M Y', strtotime($to));
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-box-seam me-2"></i>Summary Stok Fisik</h4>
        <small class="text-muted">Barang &amp; Voucher fisik — masuk / keluar periode <?= $fromFmt ?> – <?= $toFmt ?></small>
    </div>
</div>

<!-- Filter periode -->
<div class="card mb-3"><div class="card-body py-2">
<form method="GET" class="row g-2 align-items-end">
    <div class="col-auto"><label class="form-label small fw-semibold mb-1">Dari</label>
        <input type="date" name="from" value="<?= esc($from) ?>" class="form-control form-control-sm"></div>
    <div class="col-auto"><label class="form-label small fw-semibold mb-1">Sampai</label>
        <input type="date" name="to" value="<?= esc($to) ?>" class="form-control form-control-sm"></div>
    <div class="col-auto"><button class="btn btn-sm btn-primary">Terapkan</button></div>
    <div class="col-auto"><a href="<?= base_url('stock/summary') ?>" class="btn btn-sm btn-outline-secondary">Bulan Ini</a></div>
</form>
</div></div>

<!-- KPI -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-primary-subtle h-100"><div class="card-body py-3">
            <div class="small text-muted mb-1"><i class="bi bi-box me-1"></i>Nilai Stok Barang</div>
            <div class="fw-bold fs-5 text-primary"><?= $rp($totalNilaiBarang) ?></div>
            <div class="small text-muted"><?= count($barang) ?> jenis barang</div>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card border-warning-subtle h-100"><div class="card-body py-3">
            <div class="small text-muted mb-1"><i class="bi bi-ticket-perforated me-1"></i>Nilai Stok Voucher (tersedia)</div>
            <div class="fw-bold fs-5 text-warning"><?= $rp($totalNilaiVoucher) ?></div>
            <div class="small text-muted"><?= count($batches) ?> batch voucher</div>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card border-success-subtle h-100"><div class="card-body py-3">
            <div class="small text-muted mb-1"><i class="bi bi-cash-stack me-1"></i>Total Nilai Stok Fisik</div>
            <div class="fw-bold fs-5 text-success"><?= $rp($totalNilaiBarang + $totalNilaiVoucher) ?></div>
            <div class="small text-muted">barang + voucher tersedia</div>
        </div></div>
    </div>
</div>

<!-- Barang -->
<div class="card mb-4">
    <div class="card-header d-flex align-items-center"><i class="bi bi-box me-2 text-primary"></i><span class="fw-semibold small">Barang Fisik</span></div>
    <div class="table-responsive">
    <table class="table table-sm table-hover mb-0 align-middle">
        <thead class="table-light"><tr>
            <th class="ps-3">Barang</th>
            <th class="text-end">Stok Tersedia</th>
            <th class="text-end text-success">Masuk</th>
            <th class="text-end text-danger">Keluar</th>
            <th class="text-end">Nilai/Satuan</th>
            <th class="text-end">Nilai Stok</th>
            <th class="text-end pe-3"></th>
        </tr></thead>
        <tbody>
        <?php if (empty($barang)): ?>
        <tr><td colspan="7" class="text-center text-muted py-3">Belum ada barang.</td></tr>
        <?php endif; ?>
        <?php foreach ($barang as $b): ?>
        <tr>
            <td class="ps-3 fw-medium"><?= esc($b['nama_barang']) ?> <span class="text-muted small">(<?= esc($b['satuan']) ?>)</span></td>
            <td class="text-end"><?= number_format((int)$b['stok_tersedia']) ?></td>
            <td class="text-end <?= $b['masuk'] ? 'text-success fw-semibold' : 'text-muted' ?>"><?= $b['masuk'] ? '+'.number_format($b['masuk']) : '—' ?></td>
            <td class="text-end <?= $b['keluar'] ? 'text-danger fw-semibold' : 'text-muted' ?>"><?= $b['keluar'] ? '-'.number_format($b['keluar']) : '—' ?></td>
            <td class="text-end small text-muted"><?= $rp($b['nilai_satuan']) ?></td>
            <td class="text-end fw-semibold"><?= $rp($b['nilai_stok']) ?></td>
            <td class="text-end pe-3"><a href="<?= base_url('stock/barang/'.$b['id'].'/kartu?from='.$from.'&to='.$to) ?>" class="btn btn-xs btn-outline-secondary" style="font-size:.72rem;padding:.15rem .5rem">Kartu Stok</a></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>

<!-- Voucher -->
<div class="card mb-4">
    <div class="card-header d-flex align-items-center"><i class="bi bi-ticket-perforated me-2 text-warning"></i><span class="fw-semibold small">Voucher Fisik</span></div>
    <div class="table-responsive">
    <table class="table table-sm table-hover mb-0 align-middle">
        <thead class="table-light"><tr>
            <th class="ps-3">Batch Voucher</th>
            <th class="text-end">Total Kode</th>
            <th class="text-end">Tersedia</th>
            <th class="text-end text-success">Masuk</th>
            <th class="text-end text-danger">Keluar</th>
            <th class="text-end text-info">Retur</th>
            <th class="text-end">Nilai/Voucher</th>
            <th class="text-end">Nilai Stok</th>
            <th class="text-end pe-3"></th>
        </tr></thead>
        <tbody>
        <?php if (empty($batches)): ?>
        <tr><td colspan="9" class="text-center text-muted py-3">Belum ada batch voucher.</td></tr>
        <?php endif; ?>
        <?php foreach ($batches as $v): ?>
        <tr>
            <td class="ps-3 fw-medium"><?= esc($v['nama_voucher']) ?></td>
            <td class="text-end"><?= number_format((int)$v['total_kode']) ?></td>
            <td class="text-end fw-semibold"><?= number_format((int)$v['sisa_kode']) ?></td>
            <td class="text-end <?= $v['masuk'] ? 'text-success fw-semibold' : 'text-muted' ?>"><?= $v['masuk'] ? '+'.number_format($v['masuk']) : '—' ?></td>
            <td class="text-end <?= $v['keluar'] ? 'text-danger fw-semibold' : 'text-muted' ?>"><?= $v['keluar'] ? '-'.number_format($v['keluar']) : '—' ?></td>
            <td class="text-end <?= $v['retur'] ? 'text-info fw-semibold' : 'text-muted' ?>"><?= $v['retur'] ? '+'.number_format($v['retur']) : '—' ?></td>
            <td class="text-end small text-muted"><?= $rp($v['nilai_voucher']) ?></td>
            <td class="text-end fw-semibold"><?= $rp($v['nilai_stok']) ?></td>
            <td class="text-end pe-3"><a href="<?= base_url('stock/voucher/'.$v['id'].'/kartu?from='.$from.'&to='.$to) ?>" class="btn btn-xs btn-outline-secondary" style="font-size:.72rem;padding:.15rem .5rem">Kartu Stok</a></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>

<?= $this->endSection() ?>
