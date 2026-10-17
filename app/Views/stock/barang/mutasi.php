<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/stock-fisik.css') ?>?v=<?= @filemtime(FCPATH . 'css/stock-fisik.css') ?: 1 ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?= $this->include('stock/_fn') ?>

<?php
$bulanNama = ['','Januari','Februari','Maret','April','Mei','Juni',
              'Juli','Agustus','September','Oktober','November','Desember'];
[$thn, $bln] = explode('-', $bulan);
$judulBulan  = $bulanNama[(int)$bln] . ' ' . $thn;
$totMasuk = array_sum(array_column($rekap, 'masuk')); $totKeluar = array_sum(array_column($rekap, 'keluar'));
?>

<div class="page-sf">
<?php $pq = sf_period_qs(); $ret = (string) service('request')->getGet('ret'); ?>
<?= sf_crumbs([['Summary Stok Fisik', sf_summary_url()], ['Stock Hadiah Fisik', base_url('stock/barang' . ($pq ? '?' . ltrim($pq, '&') : ''))], ['Laporan Mutasi', null]]) ?>

<div class="sf-head">
    <div class="d-flex align-items-center gap-2 min-w-0">
        <a href="<?= $ret === 'summary' ? sf_summary_url() : base_url('stock/barang' . ($pq ? '?' . ltrim($pq, '&') : '')) ?>" class="sf-back" aria-label="Kembali" title="<?= $ret === 'summary' ? 'Kembali ke Summary Stok' : 'Kembali ke Stock Hadiah' ?>"><i class="bi bi-arrow-left"></i></a>
        <div class="min-w-0">
            <h4 class="sf-title"><i class="bi bi-arrow-left-right"></i>Laporan Mutasi Stok Barang</h4>
            <div class="sf-sub"><?= $judulBulan ?></div>
        </div>
    </div>
    <div class="sf-head-actions">
        <a href="<?= $ret === 'summary' ? sf_summary_url() : base_url('stock/barang' . ($pq ? '?' . ltrim($pq, '&') : '')) ?>" class="btn btn-sm btn-outline-secondary sf-backbtn"><i class="bi bi-arrow-left"></i><?= $ret === 'summary' ? 'Kembali ke Summary Stok' : 'Kembali ke Stock Hadiah' ?></a>
        <button class="btn btn-sm btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>
    </div>
</div>

<!-- Filter -->
<div class="card sf-panel mb-3">
<div class="card-body py-2">
<form method="GET" class="d-flex flex-wrap align-items-end gap-3 sf-filter">
    <?php if ($ret): ?><input type="hidden" name="ret" value="<?= esc($ret) ?>"><?php endif; ?>
    <?php foreach (['from', 'to'] as $hk): $hv = (string) service('request')->getGet($hk); if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $hv)): ?><input type="hidden" name="<?= $hk ?>" value="<?= esc($hv) ?>"><?php endif; endforeach; ?>
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
<div class="card sf-panel"><div class="card-body"><div class="sf-empty">
    <i class="bi bi-inbox"></i>
    Tidak ada mutasi stok pada <?= $judulBulan ?>.
</div></div></div>

<?php else: ?>

<div class="sf-kpis">
    <div class="sf-kpi t-ok"><div class="sf-kpi-l"><i class="bi bi-box-arrow-in-down"></i>Total Masuk</div><div class="sf-kpi-v sf-pos">+<?= sf_n($totMasuk) ?></div><div class="sf-kpi-s"><?= count($rekap) ?> barang bergerak</div></div>
    <div class="sf-kpi t-bad"><div class="sf-kpi-l"><i class="bi bi-box-arrow-up"></i>Total Keluar</div><div class="sf-kpi-v sf-neg">-<?= sf_n($totKeluar) ?></div><div class="sf-kpi-s">distribusi &amp; realisasi</div></div>
    <div class="sf-kpi t-info"><div class="sf-kpi-l"><i class="bi bi-plus-slash-minus"></i>Net</div><div class="sf-kpi-v"><?= ($totMasuk - $totKeluar >= 0 ? '+' : '') . sf_n($totMasuk - $totKeluar) ?></div><div class="sf-kpi-s">masuk dikurangi keluar</div></div>
    <div class="sf-kpi"><div class="sf-kpi-l"><i class="bi bi-list-check"></i>Transaksi</div><div class="sf-kpi-v"><?= count($logs) ?></div><div class="sf-kpi-s"><?= $judulBulan ?></div></div>
</div>

<!-- Rekap per Barang -->
<div class="card sf-panel" id="sfRekap" data-sf-list data-sf-default-sort="name:asc">
<div class="sf-panel-h"><div class="sf-panel-t">Rekap <?= $judulBulan ?> <small data-sf-count></small></div></div>
<div class="sf-tools">
    <label class="sf-search"><i class="bi bi-search"></i>
        <input type="search" class="form-control form-control-sm" data-sf-search placeholder="Cari barang…" aria-label="Cari barang" autocomplete="off">
        <button type="button" class="sf-clear" aria-label="Hapus pencarian"><i class="bi bi-x-lg"></i></button></label>
</div>
<div class="sf-scroll">
<table class="sf-tbl sf-cards">
<thead>
<tr>
    <th data-sort="name" data-type="text">Barang</th>
    <th class="c" data-sort="masuk" data-type="num" data-first="desc">Total Masuk</th>
    <th class="c" data-sort="keluar" data-type="num" data-first="desc">Total Keluar</th>
    <th class="c" data-sort="net" data-type="num" data-first="desc">Net</th>
</tr>
</thead>
<tbody>
<?php foreach ($rekap as $r):
    $net = $r['masuk'] - $r['keluar'];
?>
<tr class="sf-item"<?= sf_attrs(['data-q' => mb_strtolower($r['nama']), 'data-v-name' => mb_strtolower($r['nama']), 'data-v-masuk' => $r['masuk'], 'data-v-keluar' => $r['keluar'], 'data-v-net' => $net, 'data-sum-masuk' => $r['masuk'], 'data-sum-keluar' => $r['keluar'], 'data-sum-net' => $net]) ?>>
    <td class="sf-t"><div class="sf-name"><?= esc($r['nama']) ?></div></td>
    <td class="c" data-label="Total Masuk">
        <?php if ($r['masuk']): ?>
        <span class="sf-badge is-ok">+<?= sf_n($r['masuk']) ?> <?= esc($r['satuan']) ?></span>
        <?php else: ?><span class="sf-muted">—</span><?php endif; ?>
    </td>
    <td class="c" data-label="Total Keluar">
        <?php if ($r['keluar']): ?>
        <span class="sf-badge is-bad">-<?= sf_n($r['keluar']) ?> <?= esc($r['satuan']) ?></span>
        <?php else: ?><span class="sf-muted">—</span><?php endif; ?>
    </td>
    <td class="c <?= $net >= 0 ? 'sf-pos' : 'sf-neg' ?>" data-label="Net">
        <?= ($net >= 0 ? '+' : '') . sf_n($net) ?> <?= esc($r['satuan']) ?>
    </td>
</tr>
<?php endforeach; ?>
<tr class="sf-anchor" hidden></tr>
<tr data-sf-empty hidden><td colspan="4"><div class="sf-empty"><i class="bi bi-search"></i>Tidak ada barang yang cocok.</div></td></tr>
</tbody>
</table>
</div>
</div>

<!-- Detail Log -->
<div class="card sf-panel" id="sfLog" data-sf-list>
<div class="sf-panel-h"><div class="sf-panel-t">Detail Mutasi <small data-sf-count></small></div></div>
<div class="sf-tools">
    <label class="sf-search"><i class="bi bi-search"></i>
        <input type="search" class="form-control form-control-sm" data-sf-search placeholder="Cari barang / keterangan…" aria-label="Cari mutasi" autocomplete="off">
        <button type="button" class="sf-clear" aria-label="Hapus pencarian"><i class="bi bi-x-lg"></i></button></label>
    <div class="sf-chips">
        <button type="button" class="sf-chip is-on" data-sf-filter="all">Semua <b><?= count($logs) ?></b></button>
        <button type="button" class="sf-chip" data-sf-filter="status:masuk"><span class="dot is-ok"></span>Masuk</button>
        <button type="button" class="sf-chip" data-sf-filter="status:keluar"><span class="dot is-bad"></span>Keluar</button>
    </div>
</div>
<div class="sf-scroll">
<table class="sf-tbl sf-cards">
<thead>
<tr>
    <th data-sort="tgl" data-type="text" data-first="desc">Tanggal</th>
    <th data-sort="name" data-type="text">Barang</th>
    <th class="c">Tipe</th>
    <th class="r" data-sort="jml" data-type="num" data-first="desc">Jumlah</th>
    <th class="c">Stok Sebelum</th>
    <th class="c">Stok Sesudah</th>
    <th>Keterangan</th>
</tr>
</thead>
<tbody>
<?php foreach ($logs as $log):
    $ket = $log['catatan'] ?? '';
    if (!$ket && $log['referensi_tipe']) {
        $ket = ucfirst($log['referensi_tipe']) . ' #' . $log['referensi_id'];
    }
?>
<tr class="sf-item"<?= sf_attrs(['data-status' => $log['tipe'], 'data-q' => mb_strtolower($log['nama_barang'] . ' ' . $ket), 'data-v-tgl' => $log['tanggal'], 'data-v-name' => mb_strtolower($log['nama_barang']), 'data-v-jml' => $log['jumlah']]) ?>>
    <td class="sf-muted small" data-label="Tanggal"><?= date('d M Y', strtotime($log['tanggal'])) ?></td>
    <td class="sf-t"><div class="sf-name"><?= esc($log['nama_barang']) ?></div></td>
    <td class="c" data-label="Tipe">
        <span class="sf-badge <?= $log['tipe'] === 'masuk' ? 'is-ok' : 'is-bad' ?>">
            <?= $log['tipe'] === 'masuk' ? '↑ Masuk' : '↓ Keluar' ?>
        </span>
    </td>
    <td class="r <?= $log['tipe'] === 'masuk' ? 'sf-pos' : 'sf-neg' ?>" data-label="Jumlah">
        <?= ($log['tipe'] === 'masuk' ? '+' : '-') . sf_n($log['jumlah']) ?>
        <span class="sf-muted fw-normal small"><?= esc($log['satuan']) ?></span>
    </td>
    <td class="c small" data-label="Stok Sebelum"><?= sf_n($log['stok_sebelum']) ?></td>
    <td class="c small" data-label="Stok Sesudah"><?= sf_n($log['stok_sesudah']) ?></td>
    <td class="small sf-muted" data-label="Keterangan"><?= esc($ket ?: '—') ?></td>
</tr>
<?php endforeach; ?>
<tr class="sf-anchor" hidden></tr>
<tr data-sf-empty hidden><td colspan="7"><div class="sf-empty"><i class="bi bi-search"></i>Tidak ada mutasi yang cocok.</div></td></tr>
</tbody>
</table>
</div>
</div>

<?php endif; ?>

</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/stock-fisik.js') ?>?v=<?= @filemtime(FCPATH . 'js/stock-fisik.js') ?: 1 ?>"></script>
<?= $this->endSection() ?>
