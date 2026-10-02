<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Rundown — <?= esc($event['name']) ?></title>
<?= view('_laporan/_dokumen', ['labelHalaman' => 'Rundown · ' . strip_tags((string) $event['name'])]) ?>
<style>
/* Judul hari: bilah navy tipis dengan label emas. */
.day-header {
    display: flex; align-items: center; gap: 8px; margin: 16px 0 0; padding: 6px 10px;
    background: var(--navy-2); color: #fff; border-radius: 6px 6px 0 0;
    font-size: 10px; font-weight: 600; letter-spacing: .2px;
    break-after: avoid; page-break-after: avoid;
}
.day-header .hari { color: var(--emas-terang); font-weight: 700; text-transform: uppercase; letter-spacing: .8px; }
.day-header + .dk-tabel { margin-top: 0; }
.day-header + .dk-tabel th { background: #eef2f8; color: var(--navy-2); border-color: var(--garis-isian); border-top: 0; }

.col-no    { width: 30px;  text-align: center; color: var(--redup2); }
.col-time  { width: 92px; white-space: nowrap; font-weight: 600; color: var(--navy-2); }
.col-sesi  { width: 25%; }
.col-pic   { width: 105px; color: var(--redup); }
.col-lok   { width: 118px; color: var(--redup); }

.sesi-name { font-weight: 600; color: var(--tinta); }
.content-tag { font-size: 7.5px; font-weight: 700; color: #2563eb; text-transform: uppercase; letter-spacing: .5px; }
.dk-tabel tbody tr.from-content > td { background: #eef4ff; }
.kosong { color: var(--redup2); text-align: center; padding: 40px 0; font-style: italic; }
</style>
</head>
<body>
<?php
$mallLabels = ['ewalk' => 'eWalk Simply FUNtastic', 'pentacity' => 'Pentacity Shopping Venue', 'keduanya' => 'eWalk Simply FUNtastic & Pentacity Shopping Venue'];
$startDate  = $event['start_date'];
$endDate    = date('Y-m-d', strtotime($startDate . ' +' . ($event['event_days'] - 1) . ' days'));
$sameDay    = $startDate === $endDate;
?>
<button class="dk-tombol no-print" onclick="window.print()">Cetak / Simpan PDF</button>

<header class="dk-kop">
    <div>
        <div class="dk-label">Mall Intelligence Center · Rundown Acara</div>
        <h1 class="dk-judul"><?= esc($event['name']) ?></h1>
        <div class="dk-sub">PT. Wulandari Bangun Laksana Tbk.</div>
    </div>
    <img class="dk-logo" src="<?= base_url('img/mic-logo.png') ?>" alt="MIC">
</header>
<div class="dk-pita"></div>

<dl class="dk-identitas">
    <div><dt>Lokasi</dt><dd><?= $mallLabels[$event['mall']] ?? esc($event['mall']) ?></dd></div>
    <div><dt>Tanggal</dt><dd>
        <?= $sameDay
            ? date('d F Y', strtotime($startDate))
            : date('d', strtotime($startDate)) . '–' . date('d F Y', strtotime($endDate)) ?>
    </dd></div>
    <div><dt>Durasi</dt><dd><?= $event['event_days'] ?> hari</dd></div>
    <?php if ($event['tema']): ?>
    <div><dt>Tema</dt><dd><?= esc($event['tema']) ?></dd></div>
    <?php endif; ?>
</dl>

<?php if (empty($grouped)): ?>
<p class="kosong">Belum ada data rundown.</p>
<?php else: ?>
<?php
$no = 0;
foreach ($grouped as $hariKe => $rows):
    $tanggalHari = $rows[0]['tanggal'] ?? null;
?>
<div class="day-header">
    <span class="hari">Hari <?= $hariKe ?></span>
    <?php if ($tanggalHari): ?><span><?= date('l, d F Y', strtotime($tanggalHari)) ?></span><?php endif; ?>
</div>
<table class="dk-tabel">
<thead>
<tr>
    <th class="col-no">#</th>
    <th class="col-time">Waktu</th>
    <th class="col-sesi">Sesi / Acara</th>
    <th class="col-desk">Deskripsi</th>
    <th class="col-pic">PIC</th>
    <th class="col-lok">Lokasi</th>
</tr>
</thead>
<tbody>
<?php foreach ($rows as $r):
    $no++;
    $fromContent = ! empty($r['content_item_id']);
    $waktu = '';
    if ($r['waktu_mulai']) {
        $waktu = date('H:i', strtotime($r['waktu_mulai']));
        if ($r['waktu_selesai']) $waktu .= '–' . date('H:i', strtotime($r['waktu_selesai']));
    }
?>
<tr class="<?= $fromContent ? 'from-content' : '' ?>">
    <td class="col-no"><?= $no ?></td>
    <td class="col-time"><?= $waktu ?: '—' ?></td>
    <td class="col-sesi">
        <div class="sesi-name"><?= esc($r['sesi']) ?></div>
        <?php if ($fromContent): ?><div class="content-tag">Content Event</div><?php endif; ?>
    </td>
    <td><?= esc($r['deskripsi'] ?: '') ?></td>
    <td class="col-pic"><?= esc($r['pic'] ?: '—') ?></td>
    <td class="col-lok"><?= esc($r['lokasi'] ?: '—') ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endforeach; ?>
<?php endif; ?>

<footer class="dk-kaki">
    <span><b>Mall Intelligence Center</b> · dicetak <?= date('d F Y, H:i') ?></span>
    <span>Rundown — <?= esc($event['name']) ?></span>
</footer>

<script>window.onload = function() { window.print(); }</script>
</body>
</html>
