<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Booking Sheet Media Promo — <?= $bulan ?></title>
<?= view('_laporan/_dokumen', ['orientasi' => 'landscape', 'labelHalaman' => 'Booking Sheet Media Promo · ' . $bulan]) ?>
<style>
/* Judul kelompok per tipe titik: bilah navy tipis di atas tabel. */
.section-title {
    display: flex; align-items: center; gap: 6px; margin: 14px 0 0; padding: 5px 10px;
    background: var(--navy-2); color: #e6ecf5; border-radius: 6px 6px 0 0;
    font-size: 9.5px; font-weight: 600; break-after: avoid; page-break-after: avoid;
}
.section-title + .dk-tabel { margin-top: 0; }
.section-title + .dk-tabel th { background: #eef2f8; color: var(--navy-2); border-color: var(--garis-isian); border-top: 0; white-space: nowrap; }
.dk-tabel td { font-size: 9.5px; padding: 5px 7px; }
.kode { font-family: ui-monospace, Menlo, Consolas, monospace; font-weight: 700; color: var(--tinta); }
.redup { color: var(--redup); }

/* Badges */
.badge {
    display: inline-block; padding: 1px 7px; border-radius: 999px;
    font-size: 8px; font-weight: 600; white-space: nowrap; line-height: 1.5;
}
.badge-pending  { background: #fef3c7; color: #92400e; }
.badge-approved { background: #dcfce7; color: #166534; }
.badge-done     { background: #f1f5f9; color: #475569; }
.badge-internal { background: #f1f5f9; color: #475569; }
.badge-tenant   { background: #e0f2fe; color: #075985; }
.badge-external { background: #fef9c3; color: #713f12; }
.badge-paid     { background: #dcfce7; color: #166534; }
.badge-free     { background: #f1f5f9; color: #6b7280; }

/* Tipe pill (di bilah judul kelompok) */
.tipe-pill { display: inline-block; padding: 1.5px 9px; border-radius: 999px; font-size: 9px; font-weight: 700; }
.tipe-t_banner       { background: #dbeafe; color: #1e40af; }
.tipe-hanging        { background: #e0f2fe; color: #075985; }
.tipe-sticker_lift   { background: #fef9c3; color: #713f12; }
.tipe-totem_stainless{ background: #f1f5f9; color: #475569; }
.tipe-digital        { background: var(--emas-pucat); color: #7a5b14; }

.no-data { padding: 14px; text-align: center; color: var(--redup2); border: 1px solid var(--garis-isian); border-radius: 6px; font-style: italic; }
</style>
</head>
<body>

<button class="dk-tombol no-print" onclick="window.print()">Cetak / Simpan PDF</button>

<?php
$idBulan = ['January'=>'Januari','February'=>'Februari','March'=>'Maret','April'=>'April',
            'May'=>'Mei','June'=>'Juni','July'=>'Juli','August'=>'Agustus',
            'September'=>'September','October'=>'Oktober','November'=>'November','December'=>'Desember'];
$bulanDt    = \DateTime::createFromFormat('Y-m', $bulan);
$bulanLabel = strtr($bulanDt->format('F Y'), $idBulan);

$tipeLabel = ['t_banner'=>'T-Banner','hanging'=>'Hanging','sticker_lift'=>'Sticker Lift','totem_stainless'=>'Totem Stainless','digital'=>'Digital'];
$sumberLabel = ['internal'=>'Internal','tenant'=>'Tenant','external'=>'External'];

// Group by tipe
$grouped = [];
foreach ($usages as $u) {
    $grouped[$u['spot_tipe']][] = $u;
}
$tipeOrder = ['t_banner','hanging','sticker_lift','totem_stainless','digital'];
?>

<!-- Header -->
<header class="dk-kop">
    <div>
        <div class="dk-label">Mall Intelligence Center · Creative</div>
        <h1 class="dk-judul">Booking Sheet — Media Promo</h1>
        <div class="dk-sub"><b><?= $bulanLabel ?></b> · PT. Wulandari Bangun Laksana Tbk. · IT Department · Mall Intelligence Center</div>
    </div>
    <img class="dk-logo" src="<?= base_url('img/mic-logo.png') ?>" alt="MIC">
</header>
<div class="dk-pita"></div>

<dl class="dk-identitas">
    <div><dt>Periode</dt><dd><?= date('d M Y', strtotime($bulanMulai)) ?> s/d <?= date('d M Y', strtotime($bulanSelesai)) ?></dd></div>
    <div><dt>Dicetak oleh</dt><dd><?= esc($printedBy) ?></dd></div>
    <div><dt>Total booking</dt><dd><?= count($usages) ?></dd></div>
    <div><dt>Tanggal cetak</dt><dd><?= $printedAt ?></dd></div>
</dl>

<?php if (empty($usages)): ?>
<div class="no-data">Tidak ada booking aktif untuk bulan <?= $bulanLabel ?>.</div>
<?php else: ?>

<?php foreach ($tipeOrder as $tipe):
    if (empty($grouped[$tipe])) continue;
    $rows = $grouped[$tipe];
?>
<div class="section-title">
    <span class="tipe-pill tipe-<?= $tipe ?>"><?= $tipeLabel[$tipe] ?></span>
    &nbsp;· <?= count($rows) ?> booking
</div>
<table class="dk-tabel">
<thead>
    <tr>
        <th style="width:7%">Kode</th>
        <th style="width:11%">Nama Titik</th>
        <?php if ($tipe === 'digital'): ?><th style="width:4%">Slot</th><?php endif; ?>
        <th style="width:8%">Area</th>
        <th style="width:9%">Departemen</th>
        <th style="width:8%">Pemohon</th>
        <th style="width:17%">Nama Materi</th>
        <th style="width:8%">Periode</th>
        <th style="width:6%">Sumber</th>
        <th style="width:6%">Biaya</th>
        <th style="width:6%">Status</th>
        <th>Catatan</th>
    </tr>
</thead>
<tbody>
<?php foreach ($rows as $u): ?>
<tr>
    <td class="kode"><?= esc($u['spot_kode']) ?></td>
    <td><?= esc($u['spot_nama']) ?></td>
    <?php if ($tipe === 'digital'): ?>
    <td style="text-align:center"><?= $u['slot_number'] ? 'S'.$u['slot_number'] : '—' ?></td>
    <?php endif; ?>
    <td class="redup"><?= esc($u['spot_area'] ?? '—') ?></td>
    <td><?= esc($u['dept']) ?></td>
    <td class="redup"><?= esc($u['requested_by'] ?? '—') ?></td>
    <td><strong><?= esc($u['nama_materi']) ?></strong>
        <?php if ($u['deskripsi_materi']): ?>
        <br><span class="redup" style="font-size:8.5px"><?= esc($u['deskripsi_materi']) ?></span>
        <?php endif; ?>
    </td>
    <td style="white-space:nowrap">
        <?= date('d/m/y', strtotime($u['tanggal_mulai'])) ?><br>
        <span style="color:var(--redup2)">s/d</span> <?= date('d/m/y', strtotime($u['tanggal_selesai'])) ?>
    </td>
    <td><span class="badge badge-<?= $u['sumber'] ?>"><?= $sumberLabel[$u['sumber']] ?? $u['sumber'] ?></span></td>
    <td><span class="badge <?= $u['is_berbayar'] ? 'badge-paid' : 'badge-free' ?>"><?= $u['is_berbayar'] ? 'Berbayar' : 'Gratis' ?></span></td>
    <td><span class="badge badge-<?= $u['status'] ?>"><?= ucfirst($u['status']) ?></span></td>
    <td class="redup" style="font-size:8.5px"><?= esc($u['catatan_pemohon'] ?? '') ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endforeach; ?>

<?php endif; ?>

<!-- Footer -->
<footer class="dk-kaki">
    <span><b>Mall Intelligence Center</b> v1.9 · IT Department PT. Wulandari Bangun Laksana Tbk.</span>
    <span>Dokumen ini digenerate otomatis — <?= $printedAt ?></span>
</footer>

</body>
</html>
