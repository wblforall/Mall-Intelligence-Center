<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Booking Sheet Media Promo — <?= $bulan ?></title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: Arial, sans-serif; font-size: 11px; color: #111; background: #fff; }

/* Page layout */
@page { size: A4 landscape; margin: 12mm 14mm; }
@media print {
    .no-print { display: none !important; }
    body { font-size: 10px; }
}

/* Header */
.print-header { display: flex; justify-content: space-between; align-items: flex-start;
    border-bottom: 2px solid #1e293b; padding-bottom: 8px; margin-bottom: 14px; }
.print-header .org { font-size: 9px; color: #64748b; margin-top: 3px; }
.print-header .title { font-size: 16px; font-weight: 700; color: #1e293b; }
.print-header .sub   { font-size: 11px; color: #475569; margin-top: 2px; }
.print-header .meta  { text-align: right; font-size: 9px; color: #64748b; line-height: 1.7; }

/* Section header */
.section-title {
    background: #1e293b; color: #f1f5f9;
    padding: 4px 10px; font-size: 10px; font-weight: 700;
    margin-top: 14px; margin-bottom: 0;
    border-radius: 4px 4px 0 0;
}

/* Table */
table { width: 100%; border-collapse: collapse; }
thead th {
    background: #f1f5f9; font-size: 9.5px; font-weight: 700;
    padding: 5px 7px; border: 1px solid #cbd5e1; text-align: left;
    white-space: nowrap;
}
tbody td { padding: 5px 7px; border: 1px solid #e2e8f0; vertical-align: top; font-size: 10px; }
tbody tr:nth-child(even) { background: #f8fafc; }

/* Badges */
.badge {
    display: inline-block; padding: 1px 6px; border-radius: 3px;
    font-size: 8.5px; font-weight: 700; white-space: nowrap;
}
.badge-pending  { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
.badge-approved { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
.badge-done     { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }
.badge-internal { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }
.badge-tenant   { background: #e0f2fe; color: #075985; border: 1px solid #bae6fd; }
.badge-external { background: #fef9c3; color: #713f12; border: 1px solid #fde68a; }
.badge-paid     { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
.badge-free     { background: #f1f5f9; color: #6b7280; border: 1px solid #e5e7eb; }

/* Tipe pill (in section header context) */
.tipe-pill { display: inline-block; padding: 2px 9px; border-radius: 20px; font-size: 9.5px; font-weight: 700; }
.tipe-t_banner       { background: #dbeafe; color: #1e40af; }
.tipe-hanging        { background: #e0f2fe; color: #075985; }
.tipe-sticker_lift   { background: #fef9c3; color: #713f12; }
.tipe-totem_stainless{ background: #f1f5f9; color: #475569; }
.tipe-digital        { background: #1e293b; color: #f1f5f9; }

/* Footer */
.print-footer { margin-top: 18px; border-top: 1px solid #e2e8f0; padding-top: 7px;
    display: flex; justify-content: space-between; font-size: 9px; color: #94a3b8; }

/* No-data */
.no-data { padding: 12px; text-align: center; color: #94a3b8; border: 1px solid #e2e8f0; border-top: none; font-style: italic; }

/* Print button */
.btn-print {
    position: fixed; top: 16px; right: 16px;
    background: #1e293b; color: #fff; border: none;
    padding: 8px 18px; border-radius: 6px; cursor: pointer; font-size: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,.2);
}
.btn-print:hover { background: #334155; }
</style>
</head>
<body>

<button class="btn-print no-print" onclick="window.print()">&#128438; Cetak</button>

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
<div class="print-header">
    <div>
        <div class="title">Booking Sheet — Media Promo</div>
        <div class="sub"><?= $bulanLabel ?></div>
        <div class="org">PT. Wulandari Bangun Laksana Tbk. · IT Department · Mall Intelligence Center</div>
    </div>
    <div class="meta">
        Periode: <?= date('d M Y', strtotime($bulanMulai)) ?> s/d <?= date('d M Y', strtotime($bulanSelesai)) ?><br>
        Dicetak oleh: <?= esc($printedBy) ?><br>
        Tanggal cetak: <?= $printedAt ?><br>
        Total booking: <?= count($usages) ?>
    </div>
</div>

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
<table>
<thead>
    <tr>
        <th style="width:7%">Kode</th>
        <th style="width:14%">Nama Titik</th>
        <?php if ($tipe === 'digital'): ?><th style="width:4%">Slot</th><?php endif; ?>
        <th style="width:8%">Area</th>
        <th style="width:9%">Departemen</th>
        <th style="width:8%">Pemohon</th>
        <th style="width:18%">Nama Materi</th>
        <th style="width:9%">Periode</th>
        <th style="width:6%">Sumber</th>
        <th style="width:5%">Biaya</th>
        <th style="width:6%">Status</th>
        <th>Catatan</th>
    </tr>
</thead>
<tbody>
<?php foreach ($rows as $u): ?>
<tr>
    <td class="fw-bold" style="font-family:monospace;font-weight:700"><?= esc($u['spot_kode']) ?></td>
    <td><?= esc($u['spot_nama']) ?></td>
    <?php if ($tipe === 'digital'): ?>
    <td style="text-align:center"><?= $u['slot_number'] ? 'S'.$u['slot_number'] : '—' ?></td>
    <?php endif; ?>
    <td style="color:#64748b"><?= esc($u['spot_area'] ?? '—') ?></td>
    <td><?= esc($u['dept']) ?></td>
    <td style="color:#64748b"><?= esc($u['requested_by'] ?? '—') ?></td>
    <td><strong><?= esc($u['nama_materi']) ?></strong>
        <?php if ($u['deskripsi_materi']): ?>
        <br><span style="color:#64748b;font-size:9px"><?= esc($u['deskripsi_materi']) ?></span>
        <?php endif; ?>
    </td>
    <td style="white-space:nowrap">
        <?= date('d/m/y', strtotime($u['tanggal_mulai'])) ?><br>
        <span style="color:#94a3b8">s/d</span> <?= date('d/m/y', strtotime($u['tanggal_selesai'])) ?>
    </td>
    <td><span class="badge badge-<?= $u['sumber'] ?>"><?= $sumberLabel[$u['sumber']] ?? $u['sumber'] ?></span></td>
    <td><span class="badge <?= $u['is_berbayar'] ? 'badge-paid' : 'badge-free' ?>"><?= $u['is_berbayar'] ? 'Berbayar' : 'Gratis' ?></span></td>
    <td><span class="badge badge-<?= $u['status'] ?>"><?= ucfirst($u['status']) ?></span></td>
    <td style="color:#64748b;font-size:9px"><?= esc($u['catatan_pemohon'] ?? '') ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endforeach; ?>

<?php endif; ?>

<!-- Footer -->
<div class="print-footer">
    <span>Mall Intelligence Center v1.9 · IT Department PT. Wulandari Bangun Laksana Tbk.</span>
    <span>Dokumen ini digenerate otomatis — <?= $printedAt ?></span>
</div>

</body>
</html>
