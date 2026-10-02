<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Laporan Post Event — <?= esc($event['name']) ?></title>
<?= $this->include('_laporan/_style') ?>
<?php
// Teks kop berjalan (@page margin box) — di-escape sebagai string CSS.
$cssStr = fn (string $s) => '"' . preg_replace_callback('/[^A-Za-z0-9 .,:&()\-\/]/u', fn ($m) => '\\' . dechex(mb_ord($m[0])) . ' ', $s) . '"';
$kopBerjalan = $cssStr('Laporan Post Event — ' . mb_strimwidth((string) $event['name'], 0, 60, '…'));
?>
<style>
/* Laporan Post Event: A4 PORTRAIT (menimpa landscape bawaan tema). */
@page { size: A4 portrait; margin: 13mm 12mm 14mm; @top-right { content: <?= $kopBerjalan ?>; } }
@page :first { @top-right { content: none; } }
@media screen { body { max-width: 210mm; padding: 12mm 12mm; } }

/* Kop: tema event di bawah periode */
.doc-header .hdr-tema { order: 4; margin-top: 6px; font-size: 9.5px; color: #c7d3e6; }
.doc-header .hdr-tema b { color: var(--emas-terang); font-weight: 600; letter-spacing: 1px; font-size: 7.5px; margin-right: 5px; }
.doc-header .sub::before { content: 'PELAKSANAAN'; }
.doc-header .meta { max-width: 42%; }

/* KPI keuangan: angka rupiah panjang → sedikit lebih kecil di portrait */
.kpi-row .kpi-num { font-size: 16px; }
.kpi-red .kpi-num { color: #b91c1c; }
.kpi-num.text-red { color: #b91c1c; }
.kpi-num.text-blue { color: #1d4ed8; }
.kpi-bar { height: 4px; border-radius: 2px; margin-top: 6px; background: #e5e7eb; overflow: hidden; }
.kpi-bar-fill { height: 4px; border-radius: 2px; }

/* Bagian */
.section { margin-top: 6px; margin-bottom: 14px; }
.section .main-table { margin-bottom: 12px; }
.main-table th { white-space: normal; vertical-align: bottom; }
.main-table td { vertical-align: top; }
.main-table td.nomor { text-align: center; color: var(--redup2); font-size: 9px; }
.empty {
    margin-bottom: 12px; padding: 12px; text-align: center; font-size: 10px; font-style: italic;
    color: var(--redup2); background: #fbfcfe; border: 1px dashed var(--garis); border-radius: 8px;
}
.empty.kecil { text-align: left; padding: 6px 10px; margin: 4px 0 0; }

/* Sub-judul di dalam bagian (hari rundown, kategori, kelompok tipe) */
.sub-header, .day-header, .tipe-group-header {
    display: flex; justify-content: space-between; align-items: center; gap: 12px;
    margin: 8px 0 0; padding: 5px 10px;
    background: #eef2f8; border-left: 3px solid var(--emas); border-radius: 6px 6px 0 0;
    font-size: 10px; font-weight: 700; color: var(--navy-2);
    break-after: avoid; page-break-after: avoid;
}
.sub-header .ket, .day-header .ket, .tipe-group-header .ket { font-size: 9px; font-weight: 400; color: var(--redup); }
.sub-header .ket b, .tipe-group-header .ket b { color: var(--tinta); }

/* Rundown: baris dari Content Event */
.from-content-tag { font-size: 7px; font-weight: 700; color: #2563eb; text-transform: uppercase; letter-spacing: .5px; }
.main-table tr.from-content td { background: #eef4ff !important; }

/* Tabel detail (bersarang) */
.tabel-detail { width: 100%; border-collapse: collapse; font-size: 9px; margin-top: 2px; }
.tabel-detail th { background: #e7ecf4; color: var(--navy-2); font-weight: 600; text-align: left; padding: 3px 6px; }
.tabel-detail td { padding: 3px 6px; border-bottom: 1px solid var(--garis-halus); background: #fff !important; }
.detail-wrap { padding: 4px 8px 8px 16px !important; background: var(--latar) !important; }
.detail-judul { font-size: 8px; font-weight: 700; color: var(--redup); text-transform: uppercase; letter-spacing: .6px; margin-bottom: 3px; }
.foto-mini { width: 50px; height: 38px; object-fit: cover; border-radius: 3px; display: block; }

/* Progress bar */
.progress-wrap { background: #e5e7eb; border-radius: 3px; height: 5px; margin-top: 3px; overflow: hidden; }
.progress-bar  { height: 5px; border-radius: 3px; }
.bar-success { background: #16a34a; }
.bar-warning { background: #d97706; }
.bar-danger  { background: #dc2626; }
.bar-primary { background: #2563eb; }
.pct-kecil { font-size: 8px; color: var(--redup2); }

/* Warna nilai */
.text-red    { color: #b91c1c; }
.text-yellow { color: #a16207; }
.text-green  { color: #15803d; }
.text-blue   { color: #1d4ed8; }
.deret-angka b.info { color: #1d4ed8; }

/* Loyalty */
.program-block {
    margin: 0 0 10px; border: 1px solid var(--garis); border-radius: 8px; overflow: hidden;
    break-inside: avoid; page-break-inside: avoid;
}
.program-name {
    display: flex; justify-content: space-between; align-items: center; gap: 12px;
    padding: 6px 12px; background: var(--latar); border-bottom: 1px solid var(--garis);
    font-size: 10.5px; font-weight: 700; color: var(--tinta);
}
.program-name .ket { font-size: 9px; font-weight: 400; color: var(--redup); }
.program-body { padding: 8px 12px 4px; }
.program-body .main-table { margin-bottom: 8px; }
.program-body .deret-angka { max-width: 420px; margin-bottom: 6px; }
.section-label { font-size: 8px; font-weight: 700; text-transform: uppercase; letter-spacing: .8px; color: var(--emas); margin: 4px 0 4px; }
.total-kecil { font-size: 9px; color: var(--redup); text-align: right; margin: -4px 0 8px; }

/* Creative & Design */
.item-block { padding: 8px 10px 10px; border-bottom: 1px solid var(--garis); break-inside: avoid; page-break-inside: avoid; }
.item-block:last-child { border-bottom: 0; margin-bottom: 10px; }
.item-title { font-weight: 700; font-size: 10.5px; color: var(--tinta); margin-bottom: 3px; }
.item-title .lencana { margin-right: 5px; }
.item-title .ket { font-size: 9px; font-weight: 400; color: var(--redup); margin-left: 6px; }
.item-meta { font-size: 9px; color: var(--redup); display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 6px; }
.item-meta b { color: var(--teks); font-weight: 600; }
.item-block .main-table { margin: 6px 0 0; }
.file-grid { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 6px; }
.file-thumb { border: 1px solid var(--garis); border-radius: 6px; overflow: hidden; text-align: center; }
.file-thumb img { display: block; width: 120px; height: 90px; object-fit: cover; }
.file-thumb .file-ext { width: 120px; height: 90px; display: flex; align-items: center; justify-content: center; background: var(--latar); font-size: 10px; color: var(--redup); }
.file-thumb .file-name { font-size: 7.5px; color: var(--redup); padding: 2px 4px; background: var(--latar); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 120px; }
.insight-chips { display: flex; flex-wrap: wrap; gap: 5px; margin-top: 6px; }
.realisasi-digital { font-size: 9.5px; color: var(--teks); margin-top: 6px; }

/* Tabel total exhibition (hanya tfoot) — jangan munculkan pesan "belum ada data". */
.main-table.tanpa-kosong::after { content: none; display: none; }
.main-table.tanpa-kosong { margin-top: -12px; }

/* Kesimpulan & Evaluasi */
.eval-item { margin-bottom: 8px; padding: 8px 12px; border: 1px solid var(--garis); border-left: 3px solid var(--emas); border-radius: 6px; break-inside: avoid; page-break-inside: avoid; }
.eval-judul { font-size: 8.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .7px; color: #8a6a1f; margin-bottom: 4px; }
.eval-isi { font-size: 10px; line-height: 1.55; color: var(--teks); }
.eval-penyusun { display: flex; justify-content: space-between; font-size: 9px; color: var(--redup); padding: 2px 2px 0; }

/* Tanda tangan */
.sign-wrap { margin-top: 18px; break-inside: avoid; page-break-inside: avoid; }
.sign-place { text-align: right; font-size: 10px; color: var(--teks); }
.sign-wrap .sign-row { margin-top: 10px; gap: 22px; }

/* Tautan kembali (layar saja) */
.btn-kembali {
    position: fixed; top: 16px; left: 16px; z-index: 50; padding: 8px 14px; border-radius: 8px;
    background: #fff; border: 1px solid var(--garis); color: var(--navy-2); font: 600 12px 'Inter', Arial, sans-serif;
    text-decoration: none; box-shadow: 0 4px 14px rgba(9,21,40,.12);
}
</style>
</head>
<body>
<?php
$mallLabels  = ['ewalk' => 'eWalk Simply FUNtastic', 'pentacity' => 'Pentacity Shopping Venue', 'keduanya' => 'eWalk Simply FUNtastic & Pentacity Shopping Venue'];
$startDate   = $event['start_date'];
$endDate     = date('Y-m-d', strtotime($startDate . ' +' . ($event['event_days'] - 1) . ' days'));
$sameDay     = $startDate === $endDate;
$tipeLabels  = ['master_design' => 'Master Design', 'digital' => 'Content Digital', 'cetak' => 'Media Cetak', 'influencer' => 'Influencer', 'media_prescon' => 'Media Prescon'];
$platformLbl = ['ig' => 'Instagram', 'tiktok' => 'TikTok', 'keduanya' => 'IG & TikTok'];
$statusClass = ['draft' => 'netral', 'review' => 'waspada', 'approved' => 'baik', 'revision' => 'buruk'];
$statusLabel = ['draft' => 'Draft', 'review' => 'Review', 'approved' => 'Approved', 'revision' => 'Revisi'];
$imageExts   = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

$insightDef = [
    'total_views'       => ['Views',         'info'],
    'total_reach'       => ['Reach',         'ungu'],
    'total_impressions' => ['Impressions',   'netral'],
    'total_likes'       => ['Likes',         'buruk'],
    'total_comments'    => ['Komentar',      'waspada'],
    'total_shares'      => ['Share',         'baik'],
    'total_saves'       => ['Saves',         'ungu'],
    'total_followers'   => ['Follower Baru', 'baik'],
];
?>

<button class="btn-print no-print" onclick="window.print()">Cetak</button>
<a class="btn-kembali no-print" href="<?= base_url('events/'.$event['id'].'/summary') ?>">← Kembali ke Summary</a>

<!-- ══ KOP ══ -->
<div class="doc-header">
    <div>
        <div class="title"><?= esc($event['name']) ?></div>
        <div class="org">Laporan Post Event &mdash; <?= $mallLabels[$event['mall']] ?? esc($event['mall']) ?><?php if (!empty($eventLocations)): ?> &middot; <?= esc(implode(', ', array_column($eventLocations, 'nama'))) ?><?php endif; ?></div>
        <div class="sub"><?= $sameDay
            ? tgl_indo_hari($startDate)
            : tgl_indo_hari($startDate) . ' – ' . tgl_indo_hari($endDate) ?> &middot; <?= $event['event_days'] ?> hari</div>
        <?php if ($event['tema']): ?>
        <div class="hdr-tema"><b>TEMA</b><?= esc($event['tema']) ?></div>
        <?php endif; ?>
    </div>
    <div class="meta">
        Dokumen: Laporan Post Event<br>
        Durasi: <?= $event['event_days'] ?> hari<br>
        Dicetak: <?= date('d M Y, H:i') ?>
    </div>
</div>

<!-- ══ KPI ══ -->
<?php
// Post-event = angka aktual. Profit pakai biaya REALISASI, bukan budget rencana.
$profit    = $totalRevenue - $totalBudgetReal;
$profitPos = $profit >= 0;
$realPct   = $totalBudget > 0 ? min(100, round($totalBudgetReal / $totalBudget * 100, 1)) : 0;
$realColor = $totalBudgetReal > $totalBudget ? '#dc2626' : ($realPct >= 80 ? '#d97706' : '#16a34a');
$marginPct = $totalRevenue > 0 ? round($profit / $totalRevenue * 100, 1) : 0;
?>
<div class="kpi-row">
    <div class="kpi-box kpi-red">
        <div class="kpi-label">Total Budget</div>
        <div class="kpi-num">Rp <?= number_format($totalBudget, 0, ',', '.') ?></div>
        <div class="kpi-sub"><?= count($exhibitors) ?> exhibitor · <?= count($sponsors) ?> sponsor</div>
    </div>
    <div class="kpi-box kpi-amber">
        <div class="kpi-label">Budget Realisasi</div>
        <div class="kpi-num">Rp <?= number_format($totalBudgetReal, 0, ',', '.') ?></div>
        <?php if ($totalBudget > 0): ?>
        <div class="kpi-bar"><div class="kpi-bar-fill" style="width:<?= $realPct ?>%;background:<?= $realColor ?>"></div></div>
        <div class="kpi-sub"><?= $realPct ?>% dari total budget</div>
        <?php endif; ?>
    </div>
    <div class="kpi-box kpi-green">
        <div class="kpi-label">Total Revenue</div>
        <div class="kpi-num">Rp <?= number_format($totalRevenue, 0, ',', '.') ?></div>
        <div class="kpi-sub">
            <?php if ($totalDealing > 0): ?>Exhibition: Rp <?= number_format($totalDealing, 0, ',', '.') ?><?php endif; ?>
            <?php if ($totalDealing > 0 && $totalSponsorCash > 0): ?> · <?php endif; ?>
            <?php if ($totalSponsorCash > 0): ?>Sponsor: Rp <?= number_format($totalSponsorCash, 0, ',', '.') ?><?php endif; ?>
        </div>
    </div>
    <div class="kpi-box <?= $profitPos ? 'kpi-blue' : 'kpi-red' ?>">
        <div class="kpi-label">Margin Profit</div>
        <div class="kpi-num <?= $profitPos ? 'text-blue' : 'text-red' ?>"><?= $profitPos ? '' : '−' ?>Rp <?= number_format(abs($profit), 0, ',', '.') ?></div>
        <div class="kpi-sub"><span class="<?= $profitPos ? 'delta-up' : 'delta-down' ?>"><?= ($marginPct >= 0 ? '+' : '') ?><?= $marginPct ?>%</span> margin · Revenue − Realisasi</div>
    </div>
</div>

<!-- ══ PERFORMA TRAFFIC & KENDARAAN ══ -->
<div class="section">
    <div class="sec-title tanpa-nomor"><span>Performa Event — Traffic &amp; Kendaraan</span>
        <span class="sec-sub"><span class="lencana info"><?= number_format($trafficTotal, 0, ',', '.') ?> pengunjung</span></span></div>
    <?php if ($trafficTotal == 0 && $vehGrandTotal == 0): ?>
    <div class="empty">Belum ada data traffic / kendaraan untuk periode event ini.</div>
    <?php else: ?>
    <div class="deret-angka">
        <div><span>Total Pengunjung</span><b class="info"><?= number_format($trafficTotal, 0, ',', '.') ?></b></div>
        <div><span>Rata-rata / Hari</span><b><?= number_format($trafficAvg, 0, ',', '.') ?></b></div>
        <?php if ($peakDate): ?>
        <div><span>Puncak — <?= date('d M', strtotime($peakDate)) ?></span><b class="baik"><?= number_format($peakVal, 0, ',', '.') ?></b></div>
        <?php endif; ?>
        <div><span>Total Kendaraan</span><b class="info"><?= number_format($vehGrandTotal, 0, ',', '.') ?></b></div>
    </div>
    <table class="main-table">
    <thead><tr>
        <th>Tanggal</th>
        <th style="text-align:right">Pengunjung</th>
        <?php foreach ($vehActiveTypes as $lbl): ?><th style="text-align:right"><?= esc($lbl) ?></th><?php endforeach; ?>
        <th style="text-align:right">Total Kendaraan</th>
    </tr></thead>
    <tbody>
    <?php foreach ($perfDaily as $row): ?>
    <tr>
        <td style="font-weight:600;color:var(--navy-2);white-space:nowrap"><?= tgl_indo_pendek($row['date']) ?></td>
        <td class="num"><?= $row['pengunjung'] ? number_format($row['pengunjung'], 0, ',', '.') : '—' ?></td>
        <?php foreach (array_keys($vehActiveTypes) as $k): ?><td class="num"><?= $row['counts'][$k] ? number_format($row['counts'][$k], 0, ',', '.') : '—' ?></td><?php endforeach; ?>
        <td class="num" style="font-weight:600"><?= $row['vehTotal'] ? number_format($row['vehTotal'], 0, ',', '.') : '—' ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot><tr>
        <td>Total</td>
        <td class="num"><?= number_format($trafficTotal, 0, ',', '.') ?></td>
        <?php foreach (array_keys($vehActiveTypes) as $k): ?><td class="num"><?= number_format($vehTypeTotals[$k], 0, ',', '.') ?></td><?php endforeach; ?>
        <td class="num"><?= number_format($vehGrandTotal, 0, ',', '.') ?></td>
    </tr></tfoot>
    </table>
    <?php endif; ?>
</div>

<!-- ══ 1. RUNDOWN ══ -->
<div class="section">
    <div class="sec-title"><span>Rundown</span>
        <span class="sec-sub"><span class="lencana netral"><?= $event['event_days'] ?> Hari</span></span></div>
    <?php if (empty($rundown)): ?>
    <div class="empty">Belum ada data rundown.</div>
    <?php else: ?>
    <?php foreach ($rundown as $hariKe => $rows):
        $tanggalHari = $rows[0]['tanggal'] ?? null;
    ?>
    <div class="day-header">
        <span>Hari <?= $hariKe ?><?php if ($tanggalHari): ?> — <?= tgl_indo_hari($tanggalHari) ?><?php endif; ?></span>
    </div>
    <table class="main-table">
    <thead><tr>
        <th style="width:28px">#</th>
        <th style="width:80px">Waktu</th>
        <th style="width:28%">Sesi / Acara</th>
        <th>Deskripsi</th>
        <th style="width:100px">PIC</th>
        <th style="width:100px">Lokasi</th>
    </tr></thead>
    <tbody>
    <?php $no = 0; foreach ($rows as $r):
        $no++;
        $waktu = '';
        if ($r['waktu_mulai']) { $waktu = date('H:i', strtotime($r['waktu_mulai'])); if ($r['waktu_selesai']) $waktu .= '–'.date('H:i', strtotime($r['waktu_selesai'])); }
    ?>
    <tr class="<?= !empty($r['content_item_id']) ? 'from-content' : '' ?>">
        <td class="nomor"><?= $no ?></td>
        <td style="font-weight:600;color:var(--navy-2);white-space:nowrap"><?= $waktu ?: '—' ?></td>
        <td>
            <div style="font-weight:600;color:var(--tinta)"><?= esc($r['sesi']) ?></div>
            <?php if (!empty($r['content_item_id'])): ?><div class="from-content-tag">Content Event</div><?php endif; ?>
        </td>
        <td><?= esc($r['deskripsi'] ?: '') ?></td>
        <td><?= esc($r['pic'] ?: '—') ?></td>
        <td><?= esc($r['lokasi'] ?: '—') ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    </table>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- ══ 2. CONTENT EVENT ══ -->
<?php $uploadBaseContent = base_url('uploads/content-realisasi/' . $event['id'] . '/'); ?>
<div class="section">
    <div class="sec-title"><span>Content Event</span>
        <span class="sec-sub"><span class="lencana netral"><?= count($contentItems) ?> item<?= $contentRealTotal > 0 ? ' · Realisasi Rp '.number_format($contentRealTotal,0,',','.') : '' ?></span></span></div>
    <?php if (empty($contentItems)): ?>
    <div class="empty">Belum ada data content event.</div>
    <?php else: ?>
    <?php foreach ([['label' => 'Program / Aktivasi', 'items' => $contentPrograms], ['label' => 'Biaya Operasional', 'items' => $contentBiaya]] as $group):
        if (empty($group['items'])) continue;
        $gBudget = array_sum(array_column(array_values($group['items']), 'budget'));
        $gReal   = array_sum(array_map(fn($it) => array_sum(array_column($contentRealisasiByItem[$it['id']] ?? [], 'nilai')), array_values($group['items'])));
    ?>
    <div class="tipe-group-header">
        <span><?= $group['label'] ?></span>
        <span class="ket">
            <?= $gBudget > 0 ? 'Budget: Rp '.number_format($gBudget,0,',','.').' · ' : '' ?>Realisasi: <b>Rp <?= number_format($gReal,0,',','.') ?></b>
        </span>
    </div>
    <table class="main-table">
    <thead><tr>
        <th style="width:20%">Nama</th>
        <th style="width:8%">Tipe</th>
        <th style="width:10%">Tanggal</th>
        <th style="width:9%">Waktu</th>
        <th style="width:9%">PIC</th>
        <th style="width:9%">Lokasi</th>
        <th style="width:11%;text-align:right">Budget</th>
        <th style="width:11%;text-align:right">Realisasi</th>
        <th style="width:13%">Keterangan</th>
    </tr></thead>
    <tbody>
    <?php foreach ($group['items'] as $ci):
        $rList    = $contentRealisasiByItem[$ci['id']] ?? [];
        $rTotal   = array_sum(array_column($rList, 'nilai'));
        $pct      = $ci['budget'] > 0 ? min(100, round($rTotal / $ci['budget'] * 100)) : null;
        $barColor = $rTotal > $ci['budget'] && $ci['budget'] > 0 ? '#dc2626' : ($pct >= 80 ? '#d97706' : '#16a34a');
    ?>
    <tr>
        <td>
            <strong><?= esc($ci['nama']) ?></strong>
            <?php if ($ci['jenis']): ?><br><span class="subnote"><?= esc($ci['jenis']) ?></span><?php endif; ?>
        </td>
        <td><?= esc($ci['tipe'] ?? '—') ?></td>
        <td style="white-space:nowrap"><?= $ci['tanggal'] ? date('d/m/Y', strtotime($ci['tanggal'])) : '—' ?></td>
        <td style="white-space:nowrap"><?= $ci['waktu_mulai'] ? substr($ci['waktu_mulai'],0,5).($ci['waktu_selesai'] ? '–'.substr($ci['waktu_selesai'],0,5) : '') : '—' ?></td>
        <td><?= esc($ci['pic'] ?: '—') ?></td>
        <td><?= esc($ci['lokasi'] ?: '—') ?></td>
        <td class="num"><?= $ci['budget'] ? 'Rp '.number_format($ci['budget'],0,',','.') : '—' ?></td>
        <td class="num">
            <?= $rTotal > 0 ? 'Rp '.number_format($rTotal,0,',','.') : '—' ?>
            <?php if ($pct !== null): ?>
            <div class="progress-wrap"><div class="progress-bar" style="width:<?= min(100,$pct) ?>%;background:<?= $barColor ?>"></div></div>
            <div class="pct-kecil"><?= $pct ?>%</div>
            <?php endif; ?>
        </td>
        <td><?= esc($ci['keterangan'] ?: '—') ?></td>
    </tr>
    <?php if (!empty($rList)): ?>
    <tr>
        <td colspan="9" class="detail-wrap">
            <div class="detail-judul">Detail Realisasi</div>
            <table class="tabel-detail">
            <thead><tr>
                <th style="width:80px">Tanggal</th>
                <th style="width:100px;text-align:right">Nilai</th>
                <th>Catatan</th>
                <th style="width:66px">Foto</th>
                <th style="width:66px">Terima</th>
            </tr></thead>
            <tbody>
            <?php foreach ($rList as $r): ?>
            <tr>
                <td><?= $r['tanggal'] ? date('d/m/Y', strtotime($r['tanggal'])) : '—' ?></td>
                <td style="text-align:right"><?= $r['nilai'] ? 'Rp '.number_format($r['nilai'],0,',','.') : '—' ?></td>
                <td><?= esc($r['catatan'] ?: '—') ?></td>
                <td>
                    <?php if ($r['file_foto']): $extc = strtolower(pathinfo($r['file_foto'], PATHINFO_EXTENSION)); ?>
                        <?php if (in_array($extc, ['jpg','jpeg','png','gif','webp'])): ?>
                        <img class="foto-mini" src="<?= $uploadBaseContent.$r['file_foto'] ?>">
                        <?php else: ?>
                        <span class="lencana netral">📄 File</span>
                        <?php endif; ?>
                    <?php else: ?>—<?php endif; ?>
                </td>
                <td>
                    <?php if ($r['file_terima']): $extc2 = strtolower(pathinfo($r['file_terima'], PATHINFO_EXTENSION)); ?>
                        <?php if (in_array($extc2, ['jpg','jpeg','png','gif','webp'])): ?>
                        <img class="foto-mini" src="<?= $uploadBaseContent.$r['file_terima'] ?>">
                        <?php else: ?>
                        <span class="lencana netral">📄 File</span>
                        <?php endif; ?>
                    <?php else: ?>—<?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
            </table>
        </td>
    </tr>
    <?php endif; ?>
    <?php endforeach; ?>
    </tbody>
    <?php if (count($group['items']) > 1): ?>
    <tfoot><tr>
        <td colspan="6">Total <?= $group['label'] ?></td>
        <td class="num"><?= $gBudget > 0 ? 'Rp '.number_format($gBudget,0,',','.') : '—' ?></td>
        <td class="num"><?= $gReal > 0 ? 'Rp '.number_format($gReal,0,',','.') : '—' ?></td>
        <td></td>
    </tr></tfoot>
    <?php endif; ?>
    </table>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- ══ 3. DEKORASI / VM ══ -->
<div class="section">
    <?php $vmBudgetTotal = array_sum(array_column($vmItems, 'budget'));
          $vmRealTotal   = array_sum(array_map(fn($r) => $r['total'] ?? 0, $vmRealisasi)); ?>
    <div class="sec-title"><span>Dekorasi / Visual Merchandising</span>
        <span class="sec-sub"><span class="lencana netral"><?= count($vmItems) ?> item<?= $vmRealTotal > 0 ? ' · Realisasi Rp '.number_format($vmRealTotal,0,',','.') : '' ?></span></span></div>
    <?php if (empty($vmItems)): ?>
    <div class="empty">Belum ada data dekorasi.</div>
    <?php else: ?>
    <table class="main-table">
    <thead><tr>
        <th style="width:28px">#</th>
        <th style="width:30%">Item</th>
        <th>Deskripsi / Referensi</th>
        <th style="width:110px;text-align:right">Budget</th>
        <th style="width:110px;text-align:right">Realisasi</th>
    </tr></thead>
    <tbody>
    <?php foreach ($vmItems as $i => $vm):
        $vmReal = $vmRealisasi[$vm['id']] ?? ['total' => 0];
        $vmPct  = $vm['budget'] > 0 ? min(100, round($vmReal['total'] / $vm['budget'] * 100)) : null;
        $vmCol  = $vmReal['total'] > $vm['budget'] && $vm['budget'] > 0 ? 'danger' : 'success';
    ?>
    <tr>
        <td class="nomor"><?= $i + 1 ?></td>
        <td style="font-weight:600;color:var(--tinta)"><?= esc($vm['nama_item']) ?>
            <?php if ($vm['catatan']): ?><div class="subnote" style="font-weight:400"><?= esc($vm['catatan']) ?></div><?php endif; ?>
        </td>
        <td style="white-space:pre-line"><?= esc($vm['deskripsi_referensi'] ?: '—') ?></td>
        <td class="num"><?= $vm['budget'] > 0 ? 'Rp '.number_format($vm['budget'],0,',','.') : '—' ?></td>
        <td class="num">
            <?php if ($vmReal['total'] > 0): ?>
            <span style="font-weight:700" class="<?= $vmCol === 'danger' ? 'text-red' : 'text-green' ?>">Rp <?= number_format($vmReal['total'],0,',','.') ?></span>
            <?php if ($vmPct !== null): ?>
            <div class="pct-kecil"><?= $vmPct ?>%</div>
            <div class="progress-wrap"><div class="progress-bar bar-<?= $vmCol ?>" style="width:<?= min(100,$vmPct) ?>%"></div></div>
            <?php endif; ?>
            <?php else: ?>
            <span style="color:var(--redup2)">—</span>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    <?php if ($vmBudgetTotal > 0 || $vmRealTotal > 0): ?>
    <tfoot><tr>
        <td colspan="3" style="text-align:right">Total</td>
        <td class="num"><?= $vmBudgetTotal > 0 ? 'Rp '.number_format($vmBudgetTotal,0,',','.') : '—' ?></td>
        <td class="num <?= $vmRealTotal > $vmBudgetTotal && $vmBudgetTotal > 0 ? 'text-red' : 'text-green' ?>"><?= $vmRealTotal > 0 ? 'Rp '.number_format($vmRealTotal,0,',','.') : '—' ?></td>
    </tr></tfoot>
    <?php endif; ?>
    </table>
    <?php endif; ?>
</div>

<!-- ══ 4. EXHIBITION ══ -->
<div class="section">
    <?php
    $exTotal  = array_sum(array_column(array_merge(...(array_values($exhibitorsByKat) ?: [[]])), 'nilai_dealing'));
    $exCount  = array_sum(array_map('count', $exhibitorsByKat));
    $exBadge  = $exCount . ($tgtExJumlah > 0 ? '/'.$tgtExJumlah : '') . ' exhibitor';
    if ($exTotal > 0) $exBadge .= ' · Rp ' . number_format($exTotal,0,',','.');
    if ($tgtExNilai > 0) $exBadge .= ' / target Rp ' . number_format($tgtExNilai,0,',','.');
    ?>
    <div class="sec-title"><span>Exhibition by Casual Leasing</span>
        <span class="sec-sub"><span class="lencana netral"><?= $exBadge ?></span></span></div>
    <?php if (empty($exhibitorsByKat)): ?>
    <div class="empty">Belum ada data exhibition.</div>
    <?php else: ?>
    <?php foreach ($exhibitorsByKat as $kat => $exList): ?>
    <div class="sub-header"><span><?= esc($kat) ?> <span class="ket">(<?= count($exList) ?>)</span></span></div>
    <table class="main-table">
    <thead><tr>
        <th style="width:28px">#</th>
        <th style="width:20%">Booth</th>
        <th style="width:28%">Nama Exhibitor</th>
        <th>Program</th>
        <th style="width:120px;text-align:right">Nilai Dealing</th>
    </tr></thead>
    <tbody>
    <?php foreach ($exList as $i => $ex):
        $exProgs = $progsByExhibitor[$ex['id']] ?? [];
    ?>
    <tr>
        <td class="nomor"><?= $i + 1 ?></td>
        <td><?= esc($ex['lokasi_booth'] ?: '—') ?></td>
        <td style="font-weight:600;color:var(--tinta)"><?= esc($ex['nama_exhibitor']) ?></td>
        <td style="font-size:9.5px">
            <?php if (empty($exProgs)): ?><span style="color:var(--redup2)">—</span><?php else: ?>
            <?php foreach ($exProgs as $p):
                $jam = ''; if ($p['jam_mulai']) { $jam = substr($p['jam_mulai'],0,5); if ($p['jam_selesai']) $jam .= '–'.substr($p['jam_selesai'],0,5); }
                $periode = ''; if ($p['tanggal_mulai']) { $periode = date('d/m',strtotime($p['tanggal_mulai'])); if ($p['tanggal_selesai'] && $p['tanggal_selesai'] !== $p['tanggal_mulai']) $periode .= '–'.date('d/m',strtotime($p['tanggal_selesai'])); }
            ?>
            <div>• <?= esc($p['nama_program']) ?><?php if ($periode||$jam): ?> <span style="color:#1d4ed8;font-weight:600"><?= trim($periode.' '.$jam) ?></span><?php endif; ?></div>
            <?php endforeach; ?>
            <?php endif; ?>
        </td>
        <td class="num text-green" style="font-weight:600">Rp <?= number_format($ex['nilai_dealing'],0,',','.') ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    </table>
    <?php endforeach; ?>
    <?php if ($exTotal > 0 || $tgtExNilai > 0): ?>
    <table class="main-table tanpa-kosong"><tfoot>
    <tr>
        <td colspan="4" style="text-align:right">Total Dealing</td>
        <td class="num text-green" style="width:140px">Rp <?= number_format($exTotal,0,',','.') ?></td>
    </tr>
    <?php if ($tgtExNilai > 0): ?>
    <tr>
        <td colspan="4" style="text-align:right;font-weight:500;color:var(--redup)">Target Dealing</td>
        <td class="num" style="font-weight:500;color:var(--redup)">Rp <?= number_format($tgtExNilai,0,',','.') ?></td>
    </tr>
    <tr>
        <td colspan="4" style="text-align:right">Pencapaian Dealing</td>
        <td class="num"><span class="lencana <?= $pctExNilai >= 100 ? 'baik' : ($pctExNilai >= 60 ? 'info' : ($pctExNilai >= 30 ? 'waspada' : 'buruk')) ?>"><?= $pctExNilai ?>%</span></td>
    </tr>
    <?php endif; ?>
    <?php if ($tgtExJumlah > 0): ?>
    <tr>
        <td colspan="4" style="text-align:right;font-weight:500;color:var(--redup)">Jumlah Exhibitor</td>
        <td class="num" style="font-weight:500"><?= $exCount ?> / target <?= $tgtExJumlah ?>
            <span class="lencana <?= $pctExJumlah >= 100 ? 'baik' : ($pctExJumlah >= 60 ? 'info' : 'waspada') ?>"><?= $pctExJumlah ?>%</span>
        </td>
    </tr>
    <?php endif; ?>
    </tfoot></table>
    <?php endif; ?>
    <?php endif; ?>
</div>

<!-- ══ 5. PROGRAM LOYALTY ══ -->
<div class="section">
    <div class="sec-title"><span>Program Loyalty</span>
        <span class="sec-sub"><span class="lencana netral"><?= count($programs) ?> program</span></span></div>
    <?php if (empty($programs)): ?>
    <div class="empty">Belum ada program loyalty.</div>
    <?php else: ?>
    <?php foreach ($programs as $pr):
        $pid        = $pr['id'];
        $mData      = $memberRealisasi[$pid] ?? ['total' => 0];
        $mTotal     = (int)$mData['total'];
        $mTarget    = (int)($pr['target_peserta'] ?? 0);
        $mPct       = $mTarget > 0 ? min(100, round($mTotal / $mTarget * 100)) : null;
        $mCol       = $mPct !== null ? ($mPct >= 100 ? 'success' : ($mPct >= 60 ? 'primary' : ($mPct >= 30 ? 'warning' : 'danger'))) : 'primary';
        $vouchers   = $voucherItems[$pid] ?? [];
        $hadiahList = $hadiahItems[$pid] ?? [];
        $vBudget = 0; $vQty = 0; $vTerpakai = 0; $vTersebar = 0;
        foreach ($vouchers as $v) {
            $vBudget   += (int)$v['total_diterbitkan'] * (int)$v['nilai_voucher'];
            $vQty      += (int)$v['total_diterbitkan'];
            $vr = $voucherRealisasi[$v['id']] ?? [];
            $vTerpakai += (int)($vr['total_terpakai'] ?? 0);
            $vTersebar += (int)($vr['total_tersebar'] ?? 0);
        }
        $hBudget = 0; $hStok = 0; $hDibagi = 0;
        foreach ($hadiahList as $h) {
            $hBudget += (int)$h['stok'] * (int)$h['nilai_satuan'];
            $hStok   += (int)$h['stok'];
            $hr = $hadiahRealisasi[$h['id']] ?? [];
            $hDibagi += (int)($hr['total'] ?? 0);
        }
        $autoBudget = $vBudget + $hBudget;
        $warnaKelas = ['success' => 'baik', 'danger' => 'buruk', 'warning' => 'waspada', 'primary' => 'info'];
    ?>
    <div class="program-block">
        <div class="program-name">
            <span><?= esc($pr['nama_program']) ?></span>
            <?php if ($autoBudget > 0): ?>
            <span class="ket">Budget: <b>Rp <?= number_format($autoBudget,0,',','.') ?></b></span>
            <?php endif; ?>
        </div>
        <div class="program-body">
            <?php if ($mTarget > 0 || $mTotal > 0): ?>
            <div class="section-label">Member</div>
            <div class="deret-angka">
                <div><span>Terdaftar</span><b><?= number_format($mTotal) ?></b></div>
                <?php if ($mTarget > 0): ?>
                <div><span>Target</span><b style="color:var(--redup)"><?= number_format($mTarget) ?></b></div>
                <div><span>Pencapaian</span><b class="<?= $warnaKelas[$mCol] ?>"><?= $mPct ?? '—' ?>%</b></div>
                <?php endif; ?>
            </div>
            <?php if ($mPct !== null): ?>
            <div class="progress-wrap" style="max-width:420px;margin:-2px 0 8px">
                <div class="progress-bar bar-<?= $mCol ?>" style="width:<?= $mPct ?>%"></div>
            </div>
            <?php endif; ?>
            <?php endif; ?>

            <?php if (!empty($vouchers)): ?>
            <div class="section-label" style="margin-top:8px">e-Voucher</div>
            <table class="main-table">
            <thead><tr>
                <th>Nama Voucher</th>
                <th style="width:90px;text-align:right">Nilai</th>
                <th style="width:72px;text-align:right">Diterbitkan</th>
                <th style="width:66px;text-align:right">Tersebar</th>
                <th style="width:66px;text-align:right">Terpakai</th>
                <th style="width:60px;text-align:right">Target Serap</th>
                <th style="width:70px;text-align:right">Serapan</th>
            </tr></thead>
            <tbody>
            <?php foreach ($vouchers as $v):
                $vr    = $voucherRealisasi[$v['id']] ?? ['total_terpakai' => 0, 'total_tersebar' => 0];
                $tPct  = (int)$v['total_diterbitkan'] > 0 ? min(100, round($vr['total_terpakai'] / $v['total_diterbitkan'] * 100)) : 0;
                $tCol  = $tPct >= ($v['target_penyerapan'] ?? 0) ? 'success' : ($tPct >= 50 ? 'warning' : 'danger');
            ?>
            <tr>
                <td style="font-weight:600;color:var(--tinta)"><?= esc($v['nama_voucher']) ?></td>
                <td class="num">Rp <?= number_format($v['nilai_voucher'],0,',','.') ?></td>
                <td class="num"><?= number_format($v['total_diterbitkan']) ?></td>
                <td class="num"><?= number_format($vr['total_tersebar']) ?></td>
                <td class="num text-green" style="font-weight:700"><?= number_format($vr['total_terpakai']) ?></td>
                <td class="num text-blue"><?= ($v['target_penyerapan'] !== null && $v['target_penyerapan'] !== '') ? (float)$v['target_penyerapan'].'%' : '—' ?></td>
                <td class="num">
                    <span style="font-weight:700" class="<?= $tCol === 'success' ? 'text-green' : ($tCol === 'danger' ? 'text-red' : 'text-yellow') ?>"><?= $tPct ?>%</span>
                    <div class="progress-wrap"><div class="progress-bar bar-<?= $tCol ?>" style="width:<?= $tPct ?>%"></div></div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
            </table>
            <?php if (count($vouchers) > 1): ?>
            <div class="total-kecil">Total terpakai: <strong><?= number_format($vTerpakai) ?></strong> / <?= number_format($vQty) ?> pcs</div>
            <?php endif; ?>
            <?php endif; ?>

            <?php if (!empty($hadiahList)): ?>
            <div class="section-label" style="margin-top:8px">Hadiah</div>
            <table class="main-table">
            <thead><tr>
                <th>Nama Hadiah</th>
                <th style="width:70px;text-align:right">Stok</th>
                <th style="width:76px;text-align:right">Dibagikan</th>
                <th style="width:66px;text-align:right">Sisa</th>
                <th style="width:76px;text-align:right">Distribusi</th>
                <th style="width:110px;text-align:right">Nilai Total</th>
            </tr></thead>
            <tbody>
            <?php foreach ($hadiahList as $h):
                $hr   = $hadiahRealisasi[$h['id']] ?? ['total' => 0];
                $hPct = (int)$h['stok'] > 0 ? min(100, round($hr['total'] / $h['stok'] * 100)) : 0;
                $hCol = $hPct >= 100 ? 'success' : ($hPct >= 60 ? 'primary' : 'warning');
            ?>
            <tr>
                <td style="font-weight:600;color:var(--tinta)"><?= esc($h['nama_hadiah']) ?></td>
                <td class="num"><?= number_format($h['stok']) ?></td>
                <td class="num text-green" style="font-weight:700"><?= number_format($hr['total']) ?></td>
                <td class="num" style="color:var(--redup)"><?= number_format(max(0, $h['stok'] - $hr['total'])) ?></td>
                <td class="num">
                    <span style="font-weight:700"><?= $hPct ?>%</span>
                    <div class="progress-wrap"><div class="progress-bar bar-<?= $hCol ?>" style="width:<?= $hPct ?>%"></div></div>
                </td>
                <td class="num">Rp <?= number_format($h['stok'] * $h['nilai_satuan'],0,',','.') ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
            </table>
            <?php endif; ?>

            <?php if (empty($vouchers) && empty($hadiahList) && $mTotal === 0): ?>
            <div class="empty kecil" style="margin-bottom:8px">Belum ada data realisasi.</div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- ══ 6. SPONSORSHIP ══ -->
<div class="section">
    <?php
    $totalCash   = array_sum(array_column(array_filter($sponsors, fn($s) => $s['jenis'] === 'cash'), 'nilai'));
    $totalInKind = array_sum(array_map(fn($s) => array_sum(array_column($itemsBySponsors[$s['id']] ?? [], 'qty')), array_filter($sponsors, fn($s) => $s['jenis'] !== 'cash')));
    ?>
    <div class="sec-title"><span>Sponsorship</span>
        <span class="sec-sub"><span class="lencana netral"><?= count($sponsors) ?> sponsor<?= $totalCash > 0 ? ' · Cash Rp '.number_format($totalCash,0,',','.') : '' ?></span></span></div>
    <?php if (empty($sponsors)): ?>
    <div class="empty">Belum ada data sponsor.</div>
    <?php else: ?>
    <table class="main-table">
    <thead><tr>
        <th style="width:28px">#</th>
        <th style="width:32%">Nama Sponsor</th>
        <th style="width:70px;text-align:center">Jenis</th>
        <th>Detail / Item</th>
        <th style="width:120px;text-align:right">Nilai</th>
    </tr></thead>
    <tbody>
    <?php foreach ($sponsors as $i => $sp):
        $spItems = $itemsBySponsors[$sp['id']] ?? [];
    ?>
    <tr>
        <td class="nomor"><?= $i + 1 ?></td>
        <td style="font-weight:600;color:var(--tinta)"><?= esc($sp['nama_sponsor']) ?></td>
        <td style="text-align:center">
            <span class="lencana <?= $sp['jenis'] === 'cash' ? 'baik' : 'waspada' ?>"><?= $sp['jenis'] === 'cash' ? 'Cash' : 'In-Kind' ?></span>
        </td>
        <td style="font-size:9.5px">
            <?php if ($sp['jenis'] === 'barang' && !empty($spItems)): ?>
            <?php foreach ($spItems as $si): ?><div>• <?= esc($si['deskripsi_barang'] ?: '—') ?><?= $si['qty'] ? ' · '.number_format($si['qty']).' pcs' : '' ?></div><?php endforeach; ?>
            <?php elseif ($sp['deskripsi'] ?? null): ?><span><?= esc($sp['deskripsi']) ?></span>
            <?php else: ?><span style="color:var(--redup2)">—</span><?php endif; ?>
        </td>
        <td class="num" style="font-weight:600">
            <?php if ($sp['jenis'] === 'cash'): ?>
            <span class="text-green">Rp <?= number_format($sp['nilai'],0,',','.') ?></span>
            <?php else: ?>
            <span class="text-yellow"><?= number_format(array_sum(array_column($spItems,'qty'))) ?> pcs</span>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot>
        <?php if ($totalCash > 0): ?>
        <tr><td colspan="4" style="text-align:right">Total Cash</td><td class="num text-green">Rp <?= number_format($totalCash,0,',','.') ?></td></tr>
        <?php endif; ?>
        <?php if ($totalInKind > 0): ?>
        <tr><td colspan="4" style="text-align:right">Total In-Kind</td><td class="num text-yellow"><?= number_format($totalInKind) ?> pcs</td></tr>
        <?php endif; ?>
    </tfoot>
    </table>
    <?php endif; ?>
</div>

<!-- ══ 7. CREATIVE & DESIGN ══ -->
<div class="section">
    <?php $creativeBudgetTotal = array_sum(array_column($creativeItems, 'budget'));
          $creativeRealTotal   = array_sum(array_map(fn($r) => $r['total'] ?? 0, $creativeRealisasi)); ?>
    <div class="sec-title"><span>Creative, Concept &amp; Design</span>
        <span class="sec-sub"><span class="lencana netral"><?= count($creativeItems) ?> item<?= $creativeRealTotal > 0 ? ' · Realisasi Rp '.number_format($creativeRealTotal,0,',','.') : '' ?></span></span></div>
    <?php if (empty($creativeItems)): ?>
    <div class="empty">Belum ada data creative &amp; design.</div>
    <?php else: ?>
    <?php foreach ($tipeLabels as $tipe => $tipeLabel):
        $tipeItems = $byTipe[$tipe] ?? [];
        if (empty($tipeItems)) continue;
        $tipeBudget = array_sum(array_column($tipeItems, 'budget'));
        $tipeReal   = array_sum(array_map(fn($ci) => ($creativeRealisasi[$ci['id']]['total'] ?? 0), $tipeItems));
    ?>
    <div class="tipe-group-header">
        <span><?= $tipeLabel ?> <span class="ket">(<?= count($tipeItems) ?>)</span></span>
        <?php if ($tipeBudget > 0 || $tipeReal > 0): ?>
        <span class="ket">
            <?php if ($tipeBudget > 0): ?>Budget: Rp <?= number_format($tipeBudget,0,',','.') ?><?php endif; ?>
            <?php if ($tipeReal > 0): ?> · Realisasi: <b>Rp <?= number_format($tipeReal,0,',','.') ?></b><?php endif; ?>
        </span>
        <?php endif; ?>
    </div>

    <?php foreach ($tipeItems as $ci):
        $ciId      = $ci['id'];
        $ciRData   = $creativeRealisasi[$ciId] ?? ['total' => 0, 'entries' => []];
        $ciTotal   = (int)$ciRData['total'];
        $ciBudget  = (int)$ci['budget'];
        $ciPct     = $ciBudget > 0 ? min(100, round($ciTotal / $ciBudget * 100)) : null;
        $ciCol     = $ciTotal > $ciBudget && $ciBudget > 0 ? 'danger' : 'success';
        $ciFiles   = $creativeFiles[$ciId] ?? [];
        $ciInsight = $creativeInsights[$ciId] ?? null;
    ?>
    <div class="item-block">
        <div class="item-title">
            <?php if ($tipe === 'master_design'): ?>
            <span class="lencana <?= $statusClass[$ci['status']] ?? 'netral' ?>"><?= $statusLabel[$ci['status']] ?? $ci['status'] ?></span>
            <?php endif; ?>
            <?php if ($ci['platform']): ?>
            <span class="lencana info"><?= $platformLbl[$ci['platform']] ?? $ci['platform'] ?></span>
            <?php endif; ?>
            <?= esc($ci['nama']) ?>
            <?php if ($ciBudget > 0): ?>
            <span class="ket">· Budget: Rp <?= number_format($ciBudget,0,',','.') ?></span>
            <?php endif; ?>
        </div>
        <div class="item-meta">
            <?php if ($tipe === 'digital' && ($ci['tanggal_take'] || $ci['pic'])): ?>
            <?php if ($ci['tanggal_take']): ?><span>Take: <b><?= date('d M Y', strtotime($ci['tanggal_take'])) ?><?= $ci['jam_take'] ? ' '.substr($ci['jam_take'],0,5) : '' ?></b></span><?php endif; ?>
            <?php if ($ci['pic']): ?><span>PIC: <b><?= esc($ci['pic']) ?></b></span><?php endif; ?>
            <?php endif; ?>
            <?php if ($ci['deskripsi']): ?><span><?= esc($ci['deskripsi']) ?></span><?php endif; ?>
        </div>

        <?php /* ── MASTER DESIGN: file thumbnails ── */ ?>
        <?php if ($tipe === 'master_design' && !empty($ciFiles)): ?>
        <div class="file-grid">
            <?php foreach ($ciFiles as $f):
                $ext   = strtolower(pathinfo($f['file_name'], PATHINFO_EXTENSION));
                $isImg = in_array($ext, $imageExts);
                $fUrl  = base_url('uploads/creative/'.$ci['event_id'].'/'.$f['file_name']);
            ?>
            <div class="file-thumb">
                <?php if ($isImg): ?>
                <img src="<?= $fUrl ?>" alt="<?= esc($f['original_name']) ?>">
                <?php else: ?>
                <div class="file-ext"><?= strtoupper($ext) ?></div>
                <?php endif; ?>
                <div class="file-name"><?= esc($f['original_name']) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php /* ── DIGITAL: insight chips + screenshot thumbnails ── */ ?>
        <?php if ($tipe === 'digital' && $ciInsight): ?>
        <div class="insight-chips">
            <?php foreach ($insightDef as $key => [$label, $cls]):
                $val = $ciInsight[$key] ?? 0;
                if ($val <= 0) continue;
            ?>
            <span class="lencana <?= $cls ?>"><?= number_format($val,0,',','.') ?> <?= $label ?></span>
            <?php endforeach; ?>
        </div>
        <?php if (!empty($ciInsight['entries'])): ?>
        <div class="file-grid" style="margin-top:8px">
            <?php foreach ($ciInsight['entries'] as $ins):
                if (!$ins['file_name']) continue;
                $ssUrl = base_url('uploads/creative/'.$ci['event_id'].'/'.$ins['file_name']);
                $ssFmt = $platformLbl[$ins['platform']] ?? '';
            ?>
            <div class="file-thumb">
                <img src="<?= $ssUrl ?>" alt="screenshot">
                <div class="file-name"><?= date('d/m/y', strtotime($ins['tanggal'])) ?><?= $ssFmt ? ' · '.$ssFmt : '' ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <?php /* ── INFLUENCER: realisasi table with bukti thumbnails ── */ ?>
        <?php if ($tipe === 'influencer' && !empty($ciRData['entries'])): ?>
        <table class="main-table">
        <thead><tr>
            <th style="width:84px">Tanggal</th>
            <th>Influencer</th>
            <th style="width:110px;text-align:right">Nilai</th>
            <th style="width:70px;text-align:center">SS Insight</th>
            <th style="width:76px;text-align:center">Serah Terima</th>
            <th>Catatan</th>
        </tr></thead>
        <tbody>
        <?php foreach ($ciRData['entries'] as $e): ?>
        <tr>
            <td style="white-space:nowrap"><?= date('d M Y', strtotime($e['tanggal'])) ?></td>
            <td style="font-weight:600;color:var(--tinta)"><?= esc($e['nama_influencer'] ?? '—') ?></td>
            <td class="num text-green" style="font-weight:700">Rp <?= number_format($e['nilai'],0,',','.') ?></td>
            <td style="text-align:center">
                <?php if ($e['file_name'] && in_array(strtolower(pathinfo($e['file_name'],PATHINFO_EXTENSION)), $imageExts)): ?>
                <img src="<?= base_url('uploads/creative/'.$ci['event_id'].'/'.$e['file_name']) ?>"
                     style="height:40px;width:auto;border-radius:3px;object-fit:cover">
                <?php elseif ($e['file_name']): ?>
                <span class="lencana info">📎 File</span>
                <?php else: ?><span style="color:var(--redup2)">—</span><?php endif; ?>
            </td>
            <td style="text-align:center">
                <?php if ($e['serah_terima_file_name'] && in_array(strtolower(pathinfo($e['serah_terima_file_name'],PATHINFO_EXTENSION)), $imageExts)): ?>
                <img src="<?= base_url('uploads/creative/'.$ci['event_id'].'/'.$e['serah_terima_file_name']) ?>"
                     style="height:40px;width:auto;border-radius:3px;object-fit:cover">
                <?php elseif ($e['serah_terima_file_name']): ?>
                <span class="lencana info">📎 File</span>
                <?php else: ?><span style="color:var(--redup2)">—</span><?php endif; ?>
            </td>
            <td style="color:var(--redup)"><?= esc($e['catatan'] ?: '—') ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        <?php if ($ciTotal > 0): ?>
        <tfoot><tr>
            <td colspan="2" style="text-align:right">Total</td>
            <td class="num text-green">Rp <?= number_format($ciTotal,0,',','.') ?></td>
            <td colspan="3"></td>
        </tr></tfoot>
        <?php endif; ?>
        </table>
        <?php endif; ?>

        <?php /* ── CETAK: realisasi biaya table ── */ ?>
        <?php if ($tipe === 'cetak' && !empty($ciRData['entries'])): ?>
        <table class="main-table">
        <thead><tr>
            <th style="width:90px">Tanggal</th>
            <th style="width:130px;text-align:right">Nilai</th>
            <th>Catatan</th>
            <th style="width:80px">Bukti Terpasang</th>
        </tr></thead>
        <tbody>
        <?php foreach ($ciRData['entries'] as $e): ?>
        <tr>
            <td style="white-space:nowrap"><?= date('d M Y', strtotime($e['tanggal'])) ?></td>
            <td class="num text-green" style="font-weight:700">Rp <?= number_format($e['nilai'],0,',','.') ?></td>
            <td style="color:var(--redup)"><?= esc($e['catatan'] ?: '—') ?></td>
            <td>
                <?php if ($e['bukti_terpasang_file_name']):
                    $btUrl = base_url('uploads/creative/'.$ci['event_id'].'/'.$e['bukti_terpasang_file_name']);
                    $btExt = strtolower(pathinfo($e['bukti_terpasang_file_name'], PATHINFO_EXTENSION));
                ?>
                    <?php if (in_array($btExt, $imageExts)): ?>
                    <img src="<?= $btUrl ?>" style="width:60px;height:45px;object-fit:cover;border-radius:3px">
                    <?php else: ?>
                    <span class="lencana netral">📄 File</span>
                    <?php endif; ?>
                <?php else: ?>—<?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        <?php if ($ciTotal > 0): ?>
        <tfoot><tr>
            <td style="text-align:right">Total</td>
            <td class="num text-green">Rp <?= number_format($ciTotal,0,',','.') ?></td>
            <td><?php if ($ciPct !== null): ?><span style="font-size:9px;font-weight:500"><?= $ciPct ?>% dari budget</span><?php endif; ?></td>
            <td></td>
        </tr></tfoot>
        <?php endif; ?>
        </table>
        <?php endif; ?>

        <?php /* ── Realisasi Media Prescon ── */ ?>
        <?php if ($tipe === 'media_prescon' && !empty($ciRData['entries'])): ?>
        <table class="main-table">
        <thead><tr>
            <th style="width:90px">Tanggal</th>
            <th style="width:130px;text-align:right">Nilai</th>
            <th>Catatan</th>
            <th style="width:80px">Dokumentasi</th>
        </tr></thead>
        <tbody>
        <?php foreach ($ciRData['entries'] as $e): ?>
        <tr>
            <td style="white-space:nowrap"><?= date('d M Y', strtotime($e['tanggal'])) ?></td>
            <td class="num text-green" style="font-weight:700">Rp <?= number_format($e['nilai'],0,',','.') ?></td>
            <td style="color:var(--redup)"><?= esc($e['catatan'] ?: '—') ?></td>
            <td>
                <?php if ($e['file_name']):
                    $mpUrl = base_url('uploads/creative/'.$ci['event_id'].'/'.$e['file_name']);
                    $mpExt = strtolower(pathinfo($e['file_name'], PATHINFO_EXTENSION));
                ?>
                    <?php if (in_array($mpExt, $imageExts)): ?>
                    <img src="<?= $mpUrl ?>" style="width:60px;height:45px;object-fit:cover;border-radius:3px">
                    <?php else: ?>
                    <span class="lencana netral">📄 File</span>
                    <?php endif; ?>
                <?php else: ?>—<?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        <?php if ($ciTotal > 0): ?>
        <tfoot><tr>
            <td style="text-align:right">Total</td>
            <td class="num text-green">Rp <?= number_format($ciTotal,0,',','.') ?></td>
            <td><?php if ($ciPct !== null): ?><span style="font-size:9px;font-weight:500"><?= $ciPct ?>% dari budget</span><?php endif; ?></td>
            <td></td>
        </tr></tfoot>
        <?php endif; ?>
        </table>
        <?php endif; ?>

        <?php /* ── Realisasi biaya digital ── */ ?>
        <?php if ($tipe === 'digital' && $ciTotal > 0): ?>
        <div class="realisasi-digital">
            Realisasi biaya: <strong class="text-green">Rp <?= number_format($ciTotal,0,',','.') ?></strong>
            <?php if ($ciBudget > 0): ?>(<?= $ciPct ?>% dari Rp <?= number_format($ciBudget,0,',','.') ?>)<?php endif; ?>
        </div>
        <?php endif; ?>

    </div><!-- /item-block -->
    <?php endforeach; ?>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- ══ KESIMPULAN & EVALUASI ══ -->
<?php
$evalItems = [
    'eval_kesimpulan'  => 'Kesimpulan',
    'eval_pencapaian'  => 'Pencapaian / Yang Berjalan Baik',
    'eval_kendala'     => 'Kendala / Hambatan',
    'eval_rekomendasi' => 'Rekomendasi',
];
$adaEval = false;
foreach (array_keys($evalItems) as $k) { if (! empty($event[$k])) { $adaEval = true; break; } }
?>
<?php if ($adaEval): ?>
<div class="section">
    <div class="sec-title tanpa-nomor"><span>Kesimpulan &amp; Evaluasi</span></div>
    <?php foreach ($evalItems as $key => $label): if (empty($event[$key])) continue; ?>
    <div class="eval-item">
        <div class="eval-judul"><?= $label ?></div>
        <div class="eval-isi"><?= nl2br(esc($event[$key])) ?></div>
    </div>
    <?php endforeach; ?>
    <?php if (! empty($event['eval_updated_at'])): ?>
    <div class="eval-penyusun">
        <span>Disusun oleh: <strong><?= esc($evalUpdatedByName ?: '—') ?></strong></span>
        <span><?= tgl_indo($event['eval_updated_at'], true) ?></span>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php
/* ── Blok tanda tangan formal ──
   Laporan ini naik sampai Direksi, jadi memakai rantai penandatangan baku
   MIC (ReportSignatories): Disusun = Dept Head dept pemilik modul Events,
   Diperiksa = Senior Manager (bila ada) + Deputy GM, Mengetahui = GM. */
$sg = $signatories ?? [];
// Tidak ada dept pemegang can_edit menu 'events' → slot "Disusun oleh"
// diisi penulis Kesimpulan & Evaluasi (data yang memang sudah ada),
// bukan dibiarkan kosong.
if (empty($sg['disusun']) && ! empty($evalUpdatedByName)) {
    $sg['disusun'] = ['nama' => $evalUpdatedByName, 'jabatan' => 'Penyusun Laporan'];
}
$signSlot = function (?array $s) {
    $html = '<div class="sign-space"></div>';
    if ($s) {
        return $html . '<span class="sign-role">' . esc($s['nama']) . '</span>'
             . '<div class="sign-jabatan">' . esc($s['jabatan']) . '</div>';
    }
    return $html . '<span class="sign-role">( ……………………………… )</span><div class="sign-jabatan">&nbsp;</div>';
};
?>
<div class="sign-wrap">
    <div class="sign-place">Balikpapan, <?= tgl_indo(date('Y-m-d')) ?></div>
    <div class="sign-row">
        <div class="sign-box"><div class="sign-label">Disusun oleh</div><?= $signSlot($sg['disusun'] ?? null) ?></div>
        <?php if (! empty($sg['diperiksa_sm'])): ?>
        <div class="sign-box" style="flex:1.6">
            <div class="sign-label">Diperiksa oleh</div>
            <div class="sign-pair">
                <div><?= $signSlot($sg['diperiksa_sm']) ?></div>
                <div><?= $signSlot($sg['diperiksa'] ?? null) ?></div>
            </div>
        </div>
        <?php else: ?>
        <div class="sign-box"><div class="sign-label">Diperiksa oleh</div><?= $signSlot($sg['diperiksa'] ?? null) ?></div>
        <?php endif; ?>
        <div class="sign-box"><div class="sign-label">Mengetahui</div><?= $signSlot($sg['mengetahui'] ?? null) ?></div>
    </div>
</div>

<div class="doc-footer">
    <span>Laporan Post Event &mdash; <?= esc($event['name']) ?></span>
    <span>Mall Intelligence Center</span>
    <span>Dicetak: <?= tgl_indo(date('Y-m-d H:i:s'), true) ?></span>
</div>

<script>
if (new URLSearchParams(window.location.search).get('print') === '1') window.print();
</script>
</body>
</html>
