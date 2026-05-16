<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>PIP — <?= esc($plan['judul']) ?></title>
<style>
* { box-sizing:border-box; margin:0; padding:0; }
body { font-family:'Segoe UI',Arial,sans-serif; font-size:11pt; color:#1a1a1a; background:#fff; padding:20mm 18mm; }
h1 { font-size:15pt; font-weight:700; margin-bottom:2px; }
.subtitle { font-size:10pt; color:#555; margin-bottom:16px; }
.section-title { font-size:10pt; font-weight:700; text-transform:uppercase; color:#444; letter-spacing:.05em; border-bottom:1.5px solid #ccc; padding-bottom:4px; margin:18px 0 10px; }
.info-grid { display:grid; grid-template-columns:1fr 1fr; gap:8px 24px; margin-bottom:8px; }
.info-item label { font-size:9pt; color:#777; display:block; }
.info-item span { font-size:10.5pt; font-weight:600; }
.badge { display:inline-block; padding:2px 10px; border-radius:20px; font-size:9pt; font-weight:600; }
.badge-primary { background:#dbeafe; color:#1d4ed8; }
.badge-success { background:#dcfce7; color:#166534; }
.badge-warning { background:#fef3c7; color:#92400e; }
.badge-danger  { background:#fee2e2; color:#991b1b; }
.badge-secondary { background:#f1f5f9; color:#475569; }
table { width:100%; border-collapse:collapse; font-size:9.5pt; }
th { background:#f8fafc; font-weight:700; text-align:left; padding:6px 8px; border:1px solid #dde; }
td { padding:6px 8px; border:1px solid #dde; vertical-align:top; }
tr:nth-child(even) td { background:#fafafa; }
.review-row { padding:8px 0; border-bottom:1px solid #eee; }
.review-row:last-child { border-bottom:none; }
.text-muted { color:#777; }
.alasan-box { background:#f8f9fa; padding:10px; border-left:3px solid #aaa; border-radius:3px; font-size:10pt; }
.penutup-box { background:#f0fdf4; padding:10px; border-left:3px solid #16a34a; border-radius:3px; font-size:10pt; }
.footer-sign { display:grid; grid-template-columns:1fr 1fr 1fr; gap:30px; margin-top:40px; }
.sign-box { border-top:1px solid #aaa; padding-top:6px; text-align:center; font-size:9.5pt; }
@media print { @page { size:A4; margin:18mm; } body { padding:0; } }
</style>
</head>
<body onload="window.print()">

<?php
$statusLabel = ['draft'=>'Draft','menunggu_persetujuan'=>'Menunggu Persetujuan','aktif'=>'Aktif','selesai'=>'Selesai','diperpanjang'=>'Diperpanjang','dihentikan'=>'Dihentikan'];
$statusBadge = ['draft'=>'secondary','menunggu_persetujuan'=>'info','aktif'=>'primary','selesai'=>'success','diperpanjang'=>'warning','dihentikan'=>'danger'];
$progresLabel = ['baik'=>'Baik','cukup'=>'Cukup','kurang'=>'Kurang'];
$prBadge      = ['baik'=>'success','cukup'=>'warning','kurang'=>'danger'];
$spLabel      = ['none'=>'—','sp1'=>'Surat Peringatan 1','sp2'=>'Surat Peringatan 2','sp3'=>'Surat Peringatan 3','phk'=>'PHK'];
$setujuLabel  = ['pending'=>'Menunggu','setuju'=>'Disetujui','menolak'=>'Ditolak'];
$setujuBadge  = ['pending'=>'secondary','setuju'=>'success','menolak'=>'danger'];
?>

<div style="text-align:center;margin-bottom:16px;">
    <div style="font-size:9pt;text-transform:uppercase;letter-spacing:.1em;color:#888;margin-bottom:4px;">PT. Wulandari Bangun Laksana Tbk.</div>
    <h1>Performance Improvement Plan</h1>
    <div class="subtitle"><?= esc($plan['judul']) ?></div>
</div>

<div class="info-grid">
    <div class="info-item"><label>Karyawan</label><span><?= esc($plan['employee_nama']) ?></span></div>
    <div class="info-item"><label>Status</label><span class="badge badge-<?= $statusBadge[$plan['status']] ?>"><?= $statusLabel[$plan['status']] ?></span></div>
    <div class="info-item"><label>Jabatan</label><span><?= esc($plan['jabatan'] ?? '—') ?></span></div>
    <div class="info-item"><label>Departemen</label><span><?= esc($plan['dept_name'] ?? '—') ?></span></div>
    <div class="info-item"><label>Tanggal Mulai</label><span><?= date('d F Y', strtotime($plan['tanggal_mulai'])) ?></span></div>
    <div class="info-item"><label>Tanggal Selesai</label><span><?= date('d F Y', strtotime($plan['tanggal_selesai'])) ?></span></div>
    <div class="info-item"><label>Surat Peringatan</label><span><?= $spLabel[$plan['level_sp']] ?></span></div>
    <div class="info-item"><label>Persetujuan Atasan</label><span class="badge badge-<?= $setujuBadge[$plan['persetujuan_atasan']] ?>"><?= $setujuLabel[$plan['persetujuan_atasan']] ?></span></div>
    <div class="info-item"><label>Persetujuan Karyawan</label><span class="badge badge-<?= $setujuBadge[$plan['persetujuan_karyawan']] ?>"><?= $setujuLabel[$plan['persetujuan_karyawan']] ?></span></div>
    <div class="info-item"><label>Atasan Langsung</label><span><?= esc($plan['atasan_nama'] ?? '—') ?></span></div>
    <div class="info-item"><label>People Development</label><span><?= esc($plan['approved_by_name'] ?? '—') ?></span></div>
    <div class="info-item"><label>Tanggal Cetak</label><span><?= date('d F Y') ?></span></div>
</div>

<?php if ($plan['alasan']): ?>
<div class="section-title">Latar Belakang</div>
<div class="alasan-box"><?= nl2br(esc($plan['alasan'])) ?></div>
<?php endif; ?>

<?php if ($plan['dukungan'] || $plan['konsekuensi']): ?>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:14px">
    <?php if ($plan['dukungan']): ?>
    <div>
        <div class="section-title" style="margin-top:0">Dukungan Perusahaan</div>
        <div class="alasan-box" style="border-left-color:#0ea5e9"><?= nl2br(esc($plan['dukungan'])) ?></div>
    </div>
    <?php endif; ?>
    <?php if ($plan['konsekuensi']): ?>
    <div>
        <div class="section-title" style="margin-top:0">Konsekuensi jika Tidak Tercapai</div>
        <div class="alasan-box" style="border-left-color:#f59e0b"><?= nl2br(esc($plan['konsekuensi'])) ?></div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($plan['persetujuan_atasan'] === 'menolak' && $plan['catatan_penolakan_atasan']): ?>
<div class="section-title">Catatan Penolakan Atasan</div>
<div class="alasan-box" style="border-left-color:#ef4444"><?= nl2br(esc($plan['catatan_penolakan_atasan'])) ?></div>
<?php endif; ?>
<?php if ($plan['persetujuan_karyawan'] === 'menolak' && $plan['catatan_penolakan']): ?>
<div class="section-title">Catatan Penolakan Karyawan</div>
<div class="alasan-box" style="border-left-color:#ef4444"><?= nl2br(esc($plan['catatan_penolakan'])) ?></div>
<?php endif; ?>

<?php if (! empty($items)): ?>
<div class="section-title">Item Perbaikan</div>
<table>
    <thead>
        <tr>
            <th width="4%">#</th>
            <th width="20%">Aspek</th>
            <th width="26%">Kondisi Saat Ini</th>
            <th width="26%">Target yang Diharapkan</th>
            <th width="14%">Metrik</th>
            <th width="10%">Deadline</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($items as $i => $item): ?>
    <tr>
        <td><?= $i + 1 ?></td>
        <td><strong><?= esc($item['aspek']) ?></strong></td>
        <td><?= nl2br(esc($item['masalah'] ?? '—')) ?></td>
        <td><?= nl2br(esc($item['target'] ?? '—')) ?></td>
        <td><?= esc($item['metrik'] ?? '—') ?></td>
        <td><?= $item['deadline'] ? date('d M Y', strtotime($item['deadline'])) : '—' ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<?php if (! empty($reviews)): ?>
<div class="section-title">Riwayat Review</div>
<?php foreach ($reviews as $r): ?>
<div class="review-row">
    <span class="badge badge-<?= $prBadge[$r['progres']] ?>"><?= $progresLabel[$r['progres']] ?></span>
    <strong style="margin-left:8px"><?= date('d F Y', strtotime($r['tanggal_review'])) ?></strong>
    <span class="text-muted" style="font-size:9.5pt;margin-left:6px">oleh <?= esc($r['reviewer_name']) ?></span>
    <?php if ($r['catatan']): ?>
    <div style="margin-top:4px;font-size:9.5pt;color:#444"><?= nl2br(esc($r['catatan'])) ?></div>
    <?php endif; ?>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php if ($plan['catatan_penutup']): ?>
<div class="section-title">Catatan Penutup</div>
<div class="penutup-box"><?= nl2br(esc($plan['catatan_penutup'])) ?></div>
<?php endif; ?>

<div class="footer-sign">
    <div class="sign-box">
        Karyawan<br>
        <br><br><br>
        ( <?= esc($plan['employee_nama']) ?> )
    </div>
    <div class="sign-box">
        Atasan Langsung<br>
        <br><br><br>
        ( <?= esc($plan['atasan_nama'] ?? '______________________________') ?> )
    </div>
    <div class="sign-box">
        People Development<br>
        <br><br><br>
        ( <?= esc($plan['approved_by_name'] ?? '______________________________') ?> )
    </div>
</div>

</body>
</html>
