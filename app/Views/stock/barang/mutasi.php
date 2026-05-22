<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$bulanNama = ['','Januari','Februari','Maret','April','Mei','Juni',
              'Juli','Agustus','September','Oktober','November','Desember'];
[$thn, $bln] = explode('-', $bulan);
$judulBulan  = $bulanNama[(int)$bln] . ' ' . $thn;
?>

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="<?= base_url('stock/barang') ?>" class="btn btn-sm btn-light"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h5 class="fw-bold mb-0"><i class="bi bi-arrow-left-right me-2"></i>Laporan Mutasi Stok Barang</h5>
        <div class="text-muted small"><?= $judulBulan ?></div>
    </div>
    <button class="btn btn-sm btn-outline-secondary ms-auto" onclick="window.print()">
        <i class="bi bi-printer me-1"></i>Print
    </button>
</div>

<!-- Filter -->
<div class="card mb-3">
<div class="card-body py-2">
<form method="GET" class="d-flex flex-wrap align-items-end gap-3">
    <div>
        <label class="form-label small fw-semibold mb-1">Bulan</label>
        <input type="month" name="bulan" class="form-control form-control-sm" value="<?= esc($bulan) ?>">
    </div>
    <div>
        <label class="form-label small fw-semibold mb-1">Barang</label>
        <select name="barang_id" class="form-select form-select-sm" style="min-width:200px">
            <option value="">Semua Barang</option>
            <?php foreach ($barangs as $b): ?>
            <option value="<?= $b['id'] ?>" <?= $barangId == $b['id'] ? 'selected' : '' ?>><?= esc($b['nama_barang']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="submit" class="btn btn-sm btn-primary">Tampilkan</button>
</form>
</div>
</div>

<?php if (empty($logs)): ?>
<div class="card"><div class="card-body text-center text-muted py-5">
    <i class="bi bi-inbox display-4 d-block mb-2 opacity-25"></i>
    Tidak ada mutasi stok pada <?= $judulBulan ?>.
</div></div>

<?php else: ?>

<!-- Rekap per Barang -->
<div class="card mb-3">
<div class="card-header fw-semibold py-2">Rekap <?= $judulBulan ?></div>
<div class="card-body p-0">
<table class="table table-sm align-middle mb-0">
<thead class="table-light">
<tr>
    <th class="ps-3">Barang</th>
    <th class="text-center">Total Masuk</th>
    <th class="text-center">Total Keluar</th>
    <th class="text-center">Net</th>
</tr>
</thead>
<tbody>
<?php foreach ($rekap as $r):
    $net = $r['masuk'] - $r['keluar'];
?>
<tr>
    <td class="ps-3 fw-semibold small"><?= esc($r['nama']) ?></td>
    <td class="text-center">
        <?php if ($r['masuk']): ?>
        <span class="badge bg-success-subtle text-success border fw-semibold">+<?= number_format($r['masuk']) ?> <?= esc($r['satuan']) ?></span>
        <?php else: ?><span class="text-muted">—</span><?php endif; ?>
    </td>
    <td class="text-center">
        <?php if ($r['keluar']): ?>
        <span class="badge bg-danger-subtle text-danger border fw-semibold">-<?= number_format($r['keluar']) ?> <?= esc($r['satuan']) ?></span>
        <?php else: ?><span class="text-muted">—</span><?php endif; ?>
    </td>
    <td class="text-center fw-bold <?= $net >= 0 ? 'text-success' : 'text-danger' ?>">
        <?= ($net >= 0 ? '+' : '') . number_format($net) ?> <?= esc($r['satuan']) ?>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>

<!-- Detail Log -->
<div class="card">
<div class="card-header fw-semibold py-2 d-flex align-items-center justify-content-between">
    <span>Detail Mutasi</span>
    <span class="badge bg-secondary"><?= count($logs) ?> transaksi</span>
</div>
<div class="card-body p-0">
<div class="table-responsive">
<table class="table table-sm table-hover align-middle mb-0">
<thead class="table-light">
<tr>
    <th class="ps-3">Tanggal</th>
    <th>Barang</th>
    <th class="text-center">Tipe</th>
    <th class="text-end">Jumlah</th>
    <th class="text-center">Stok Sebelum</th>
    <th class="text-center">Stok Sesudah</th>
    <th>Keterangan</th>
</tr>
</thead>
<tbody>
<?php foreach ($logs as $log): ?>
<tr>
    <td class="ps-3 text-muted small"><?= date('d M Y', strtotime($log['tanggal'])) ?></td>
    <td class="fw-semibold small"><?= esc($log['nama_barang']) ?></td>
    <td class="text-center">
        <span class="badge bg-<?= $log['tipe'] === 'masuk' ? 'success' : 'danger' ?>">
            <?= $log['tipe'] === 'masuk' ? '↑ Masuk' : '↓ Keluar' ?>
        </span>
    </td>
    <td class="text-end fw-semibold <?= $log['tipe'] === 'masuk' ? 'text-success' : 'text-danger' ?>">
        <?= ($log['tipe'] === 'masuk' ? '+' : '-') . number_format($log['jumlah']) ?>
        <span class="text-muted fw-normal small"><?= esc($log['satuan']) ?></span>
    </td>
    <td class="text-center small"><?= number_format($log['stok_sebelum']) ?></td>
    <td class="text-center small"><?= number_format($log['stok_sesudah']) ?></td>
    <td class="small text-muted">
        <?php
        $ket = $log['catatan'] ?? '';
        if (!$ket && $log['referensi_tipe']) {
            $ket = ucfirst($log['referensi_tipe']) . ' #' . $log['referensi_id'];
        }
        ?>
        <?= esc($ket ?: '—') ?>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
</div>

<?php endif; ?>

<style>
@media print {
    .btn, form.d-flex { display:none !important; }
    .card { border:1px solid #dee2e6 !important; box-shadow:none !important; }
}
</style>

<?= $this->endSection() ?>
