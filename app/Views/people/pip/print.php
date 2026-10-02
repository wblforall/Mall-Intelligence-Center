<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>PIP — <?= esc($plan['judul']) ?></title>
<?= view('_laporan/_dokumen', ['labelHalaman' => 'PIP · ' . strip_tags((string) $plan['employee_nama'])]) ?>
<style>
.badge { display:inline-block; padding:1.5px 9px; border-radius:999px; font-size:8.5px; font-weight:600; line-height:1.45; }
.badge-primary   { background:#dbeafe; color:#1d4ed8; }
.badge-info      { background:#e0f2fe; color:#0369a1; }
.badge-success   { background:#dcfce7; color:#166534; }
.badge-warning   { background:#fef3c7; color:#92400e; }
.badge-danger    { background:#fee2e2; color:#991b1b; }
.badge-secondary { background:#f1f5f9; color:#475569; }
.dua-kolom { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
.dua-kolom .dk-bagian { margin-top: 16px; }
.review-row { padding:7px 0; border-bottom:1px solid var(--garis-halus); break-inside:avoid; }
.review-row:last-child { border-bottom:none; }
.review-row .tgl { margin-left:8px; }
.review-row .oleh { margin-left:6px; color:var(--redup); font-size:9.5px; }
.review-row .isi { margin-top:3px; font-size:10px; color:var(--teks); }
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

<button class="dk-tombol no-print" onclick="window.print()">Cetak / Simpan PDF</button>

<header class="dk-kop">
    <div>
        <div class="dk-label">Mall Intelligence Center · People Development</div>
        <h1 class="dk-judul">Performance Improvement Plan</h1>
        <div class="dk-sub"><b><?= esc($plan['judul']) ?></b> · PT. Wulandari Bangun Laksana Tbk.</div>
    </div>
    <img class="dk-logo" src="<?= base_url('img/mic-logo.png') ?>" alt="MIC">
</header>
<div class="dk-pita"></div>

<dl class="dk-identitas">
    <div><dt>Karyawan</dt><dd><?= esc($plan['employee_nama']) ?></dd></div>
    <div><dt>Status</dt><dd><span class="badge badge-<?= $statusBadge[$plan['status']] ?>"><?= $statusLabel[$plan['status']] ?></span></dd></div>
    <div><dt>Jabatan</dt><dd><?= esc($plan['jabatan'] ?? '—') ?></dd></div>
    <div><dt>Departemen</dt><dd><?= esc($plan['dept_name'] ?? '—') ?></dd></div>
    <div><dt>Tanggal Mulai</dt><dd><?= date('d F Y', strtotime($plan['tanggal_mulai'])) ?></dd></div>
    <div><dt>Tanggal Selesai</dt><dd><?= date('d F Y', strtotime($plan['tanggal_selesai'])) ?></dd></div>
    <div><dt>Surat Peringatan</dt><dd><?= $spLabel[$plan['level_sp']] ?></dd></div>
    <div><dt>Persetujuan Atasan</dt><dd><span class="badge badge-<?= $setujuBadge[$plan['persetujuan_atasan']] ?>"><?= $setujuLabel[$plan['persetujuan_atasan']] ?></span></dd></div>
    <div><dt>Persetujuan Karyawan</dt><dd><span class="badge badge-<?= $setujuBadge[$plan['persetujuan_karyawan']] ?>"><?= $setujuLabel[$plan['persetujuan_karyawan']] ?></span></dd></div>
    <div><dt>Atasan Langsung</dt><dd><?= esc($plan['atasan_nama'] ?? '—') ?></dd></div>
    <div><dt>People Development</dt><dd><?= esc($plan['approved_by_name'] ?? '—') ?></dd></div>
    <div><dt>Tanggal Cetak</dt><dd><?= date('d F Y') ?></dd></div>
</dl>

<?php if ($plan['alasan']): ?>
<h2 class="dk-bagian">Latar Belakang</h2>
<div class="dk-kotak"><?= nl2br(esc($plan['alasan'])) ?></div>
<?php endif; ?>

<?php if ($plan['dukungan'] || $plan['konsekuensi']): ?>
<div class="dua-kolom">
    <?php if ($plan['dukungan']): ?>
    <div>
        <h2 class="dk-bagian">Dukungan Perusahaan</h2>
        <div class="dk-kotak info"><?= nl2br(esc($plan['dukungan'])) ?></div>
    </div>
    <?php endif; ?>
    <?php if ($plan['konsekuensi']): ?>
    <div>
        <h2 class="dk-bagian">Konsekuensi jika Tidak Tercapai</h2>
        <div class="dk-kotak waspada"><?= nl2br(esc($plan['konsekuensi'])) ?></div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($plan['persetujuan_atasan'] === 'menolak' && $plan['catatan_penolakan_atasan']): ?>
<h2 class="dk-bagian">Catatan Penolakan Atasan</h2>
<div class="dk-kotak buruk"><?= nl2br(esc($plan['catatan_penolakan_atasan'])) ?></div>
<?php endif; ?>
<?php if ($plan['persetujuan_karyawan'] === 'menolak' && $plan['catatan_penolakan']): ?>
<h2 class="dk-bagian">Catatan Penolakan Karyawan</h2>
<div class="dk-kotak buruk"><?= nl2br(esc($plan['catatan_penolakan'])) ?></div>
<?php endif; ?>

<?php if (! empty($items)): ?>
<h2 class="dk-bagian">Item Perbaikan</h2>
<table class="dk-tabel">
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
<h2 class="dk-bagian">Riwayat Review</h2>
<?php foreach ($reviews as $r): ?>
<div class="review-row">
    <span class="badge badge-<?= $prBadge[$r['progres']] ?>"><?= $progresLabel[$r['progres']] ?></span>
    <strong class="tgl"><?= date('d F Y', strtotime($r['tanggal_review'])) ?></strong>
    <span class="oleh">oleh <?= esc($r['reviewer_name']) ?></span>
    <?php if ($r['catatan']): ?>
    <div class="isi"><?= nl2br(esc($r['catatan'])) ?></div>
    <?php endif; ?>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php if ($plan['catatan_penutup']): ?>
<h2 class="dk-bagian">Catatan Penutup</h2>
<div class="dk-kotak baik"><?= nl2br(esc($plan['catatan_penutup'])) ?></div>
<?php endif; ?>

<div class="dk-utuh">
<div class="dk-ttd">
    <div>
        <div class="dk-ttd-peran">Karyawan</div><div class="dk-ttd-ruang"></div>
        <div class="dk-ttd-nama"><?= esc($plan['employee_nama']) ?></div>
    </div>
    <div>
        <div class="dk-ttd-peran">Atasan Langsung</div><div class="dk-ttd-ruang"></div>
        <div class="dk-ttd-nama"><?= $plan['atasan_nama'] ? esc($plan['atasan_nama']) : '&nbsp;' ?></div>
    </div>
    <div>
        <div class="dk-ttd-peran">People Development</div><div class="dk-ttd-ruang"></div>
        <div class="dk-ttd-nama"><?= $plan['approved_by_name'] ? esc($plan['approved_by_name']) : '&nbsp;' ?></div>
    </div>
</div>

<footer class="dk-kaki">
    <span><b>Mall Intelligence Center</b> · dicetak <?= date('d/m/Y H:i') ?></span>
    <span>Performance Improvement Plan — <?= esc($plan['employee_nama']) ?></span>
</footer>
</div>

</body>
</html>
