<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?= $this->include('stock/_fn') ?>
<style>
/* Breadcrumb Kartu Stok: selaras dengan halaman Stok Fisik lain (stock-fisik.css tidak dimuat di sini) */
.sf-crumbs { display: flex; flex-wrap: wrap; align-items: center; gap: .15rem .45rem; font-size: .78rem; color: var(--bs-secondary-color, #94a3b8); }
.sf-crumbs a { color: inherit; text-decoration: none; }
.sf-crumbs a:hover { text-decoration: underline; color: var(--bs-body-color); }
.sf-crumbs .bi-chevron-right { font-size: .6rem; opacity: .6; }
.sf-crumbs [aria-current] { color: var(--bs-body-color); font-weight: 600; }
</style>
<?php
$ret = in_array((string) service('request')->getGet('ret'), ['summary', 'barang', 'voucher'], true) ? (string) service('request')->getGet('ret') : 'summary';
$pq  = '?from=' . esc($from) . '&to=' . esc($to);
if ($ret === 'barang')       { $backUrl = base_url('stock/barang' . $pq . '#barang-' . ($barang['id'] ?? 0));  $backLbl = 'Kembali ke Stock Hadiah'; }
elseif ($ret === 'voucher')  { $backUrl = base_url('stock/voucher' . $pq . '#batch-' . ($batch['id'] ?? 0)); $backLbl = 'Kembali ke Stock Voucher'; }
else                         { $backUrl = base_url('stock/summary' . $pq); $backLbl = 'Kembali ke Summary Stok'; }
$kartuNama = 'voucher';
?>
<div class="sf-crumbs-wrap" style="font-size:.78rem;margin-bottom:.55rem">
<?= sf_crumbs(array_filter([
    ['Summary Stok Fisik', base_url('stock/summary' . $pq)],
    $ret === 'barang'  ? ['Stock Hadiah Fisik', base_url('stock/barang' . $pq)] : null,
    $ret === 'voucher' ? ['Stock Voucher Fisik', base_url('stock/voucher' . $pq)] : null,
    ['Kartu Stok', null],
])) ?>
</div>

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="<?= $backUrl ?>" class="btn btn-sm btn-outline-secondary" title="<?= $backLbl ?>"><i class="bi bi-arrow-left me-1"></i><span class="d-none d-sm-inline"><?= $backLbl ?></span></a>
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-card-list me-2"></i>Kartu Stok — <?= esc($batch['nama_voucher']) ?></h4>
        <small class="text-muted">Total kode <?= sf_n((int)$batch['total_kode']) ?> · Tersedia saat ini: <strong><?= sf_n((int)$batch['sisa_kode']) ?></strong></small>
    </div>
</div>

<div class="card mb-3"><div class="card-body py-2">
<form method="GET" class="row g-2 align-items-end">
    <input type="hidden" name="ret" value="<?= esc($ret) ?>">
    <div class="col-auto"><label class="form-label small fw-semibold mb-1">Dari</label>
        <input type="date" name="from" value="<?= esc($from) ?>" class="form-control form-control-sm"></div>
    <div class="col-auto"><label class="form-label small fw-semibold mb-1">Sampai</label>
        <input type="date" name="to" value="<?= esc($to) ?>" class="form-control form-control-sm"></div>
    <div class="col-auto"><button class="btn btn-sm btn-primary">Terapkan</button></div>
</form>
</div></div>

<div class="card">
<div class="table-responsive">
<table class="table table-sm table-hover mb-0 align-middle">
    <thead class="table-light"><tr>
        <th class="ps-3" style="width:110px">Tanggal</th>
        <th style="width:90px">Jenis</th>
        <th class="text-end" style="width:90px">Jumlah</th>
        <th class="text-end" style="width:100px">Saldo</th>
        <th>Referensi</th>
        <th>Catatan</th>
        <th class="pe-3" style="width:130px">Oleh</th>
    </tr></thead>
    <tbody>
    <?php if (empty($entries)): ?>
    <tr><td colspan="7" class="text-center text-muted py-4">Belum ada mutasi pada periode ini.</td></tr>
    <?php endif; ?>
    <?php
    $tipeBadge = ['masuk' => ['success','Masuk'], 'keluar' => ['danger','Keluar'], 'retur' => ['info','Retur']];
    $refLabel  = [
        'import'        => 'Import kode',
        'manual'        => 'Distribusi manual',
        'deassign'      => 'Batal distribusi manual',
        'delete'        => 'Hapus kode',
        'program'       => 'Distribusi via program',
        'program_batal' => 'Batal realisasi program',
        'backfill'      => 'Data lama',
    ];
    foreach ($entries as $e):
        [$col, $lbl] = $tipeBadge[$e['tipe']] ?? ['secondary', $e['tipe']];
        $plus = $e['tipe'] !== 'keluar';
        $refTxt = $refLabel[$e['referensi_tipe']] ?? ($e['referensi_tipe'] ?? '—');
        if (in_array($e['referensi_tipe'], ['program', 'program_batal'], true) && $e['referensi_id']) {
            $pn = $progNames[(int)$e['referensi_id']] ?? null;
            $refTxt .= $pn ? ' — ' . $pn : ' #' . $e['referensi_id'];
        }
    ?>
    <tr>
        <td class="ps-3 small"><?= date('d M Y', strtotime($e['tanggal'])) ?></td>
        <td><span class="badge bg-<?= $col ?>-subtle text-<?= $col ?>"><?= $lbl ?></span></td>
        <td class="text-end fw-semibold text-<?= $col ?>"><?= ($plus ? '+' : '-') . sf_n((int)$e['jumlah']) ?></td>
        <td class="text-end"><?= sf_n((int)$e['saldo_sesudah']) ?></td>
        <td class="small text-muted"><?= esc($refTxt) ?></td>
        <td class="small"><?= esc($e['catatan'] ?? '') ?></td>
        <td class="pe-3 small text-muted"><i class="bi bi-person me-1"></i><?= esc($e['pengisi'] ?? '—') ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
</div>

<?= $this->endSection() ?>
