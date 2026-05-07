<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Laporan Post Event — <?= esc($event['name']) ?></title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 10.5pt; color: #1a1a1a; background: #fff; }
.page { max-width: 980px; margin: 0 auto; padding: 28px 36px; }

/* Document header */
.doc-header { border-bottom: 3px solid #1e3a5f; padding-bottom: 14px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: flex-end; }
.doc-title   { font-size: 20pt; font-weight: 700; color: #1e3a5f; }
.doc-sub     { font-size: 10pt; color: #555; margin-top: 4px; }
.doc-badge   { font-size: 8pt; background: #1e3a5f; color: #fff; padding: 4px 10px; border-radius: 4px; white-space: nowrap; text-align: right; line-height: 1.8; }
.doc-meta    { display: flex; flex-wrap: wrap; gap: 18px; margin-top: 8px; }
.doc-meta span { font-size: 9pt; color: #555; }
.doc-meta span strong { color: #1e3a5f; }

/* Section header */
.section { margin-top: 28px; }
.section-header {
    background: #1e3a5f; color: #fff;
    padding: 7px 14px; font-size: 11pt; font-weight: 700;
    letter-spacing: .03em; display: flex; justify-content: space-between; align-items: center;
    page-break-after: avoid;
}
.section-header .badge { background: rgba(255,255,255,.2); font-size: 8pt; padding: 2px 8px; border-radius: 3px; font-weight: 600; }

/* Sub-section */
.sub-header { background: #e8eef5; color: #1e3a5f; padding: 5px 12px; font-size: 9.5pt; font-weight: 700; margin-top: 0; border-bottom: 1px solid #c5d3e0; page-break-after: avoid; }

/* Tables */
table { width: 100%; border-collapse: collapse; font-size: 9.5pt; }
thead th { background: #f0f4f8; color: #1e3a5f; font-size: 8.5pt; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; padding: 6px 10px; border-bottom: 2px solid #1e3a5f; text-align: left; }
tbody td { padding: 6px 10px; border-bottom: 1px solid #e8ecf0; vertical-align: top; }
tbody tr:nth-child(even) td { background: #f8fafc; }
tfoot td { background: #eef2f7; font-weight: 700; font-size: 9pt; padding: 6px 10px; border-top: 2px solid #1e3a5f; }

/* Rundown */
.day-header { background: #2d4e7f; color: #fff; padding: 5px 12px; font-size: 9.5pt; font-weight: 600; margin-top: 6px; page-break-after: avoid; }
.from-content-tag { font-size: 7pt; font-weight: 700; color: #2563eb; text-transform: uppercase; letter-spacing: .05em; }
tr.from-content td { background: #eef4ff !important; }

/* Progress bars */
.progress-wrap { background: #e5e7eb; border-radius: 3px; height: 5px; margin-top: 3px; overflow: hidden; }
.progress-bar  { height: 5px; border-radius: 3px; }
.bar-success { background: #10b981; }
.bar-warning { background: #f59e0b; }
.bar-danger  { background: #ef4444; }
.bar-primary { background: #3b82f6; }

/* Badges */
.badge-status { display: inline-block; padding: 1px 7px; border-radius: 3px; font-size: 7.5pt; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; }
.badge-draft    { background: #e5e7eb; color: #6b7280; }
.badge-review   { background: #fef3c7; color: #b45309; }
.badge-approved { background: #d1fae5; color: #065f46; }
.badge-revision { background: #fee2e2; color: #991b1b; }
.badge-platform { background: #cffafe; color: #0e7490; font-size: 7.5pt; padding: 1px 6px; border-radius: 3px; font-weight: 600; }
.badge-tipe { background: #e0f2fe; color: #0369a1; font-size: 7.5pt; padding: 1px 6px; border-radius: 3px; font-weight: 600; }

/* Sponsor */
.sponsor-cash   { background: #d1fae5; color: #065f46; }
.sponsor-inkind { background: #fef3c7; color: #92400e; }

/* Creative & Design */
.tipe-group-header { background: #e8eef5; color: #1e3a5f; padding: 5px 12px; font-size: 9.5pt; font-weight: 700; border-bottom: 1px solid #c5d3e0; display: flex; justify-content: space-between; }

/* Item block */
.item-block { border-bottom: 2px solid #d5dfe8; padding: 10px 12px; page-break-inside: avoid; }
.item-block:last-child { border-bottom: none; }
.item-title { font-weight: 700; font-size: 10pt; margin-bottom: 3px; }
.item-meta  { font-size: 8.5pt; color: #666; display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 6px; }

/* Files / images */
.file-grid { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 6px; }
.file-thumb { border: 1px solid #d1d5db; border-radius: 4px; overflow: hidden; text-align: center; }
.file-thumb img { display: block; width: 120px; height: 90px; object-fit: cover; }
.file-thumb .file-name { font-size: 7pt; color: #888; padding: 2px 4px; background: #f9fafb; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 120px; }

/* Insight chips */
.insight-chips { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 6px; }
.chip { display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; border-radius: 12px; font-size: 8.5pt; font-weight: 600; }
.chip-info      { background: #e0f2fe; color: #0369a1; }
.chip-primary   { background: #ede9fe; color: #6d28d9; }
.chip-secondary { background: #f3f4f6; color: #374151; }
.chip-danger    { background: #fee2e2; color: #991b1b; }
.chip-warning   { background: #fef3c7; color: #92400e; }
.chip-success   { background: #d1fae5; color: #065f46; }

/* Loyalty */
.program-block { border: 1px solid #d5dfe8; margin: 6px 0; border-radius: 3px; page-break-inside: avoid; }
.program-name  { background: #f0f6ff; padding: 6px 12px; font-weight: 700; font-size: 10pt; border-bottom: 1px solid #d5dfe8; display: flex; justify-content: space-between; align-items: center; }
.program-body  { padding: 8px 12px; }
.section-label { font-size: 7.5pt; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #888; margin: 6px 0 3px; }
.stat-row      { display: flex; gap: 20px; flex-wrap: wrap; margin: 4px 0; }
.stat-item     { text-align: center; }
.stat-val      { font-size: 14pt; font-weight: 700; color: #1e3a5f; line-height: 1; }
.stat-lbl      { font-size: 7.5pt; color: #888; margin-top: 1px; }

/* KPI Cards */
.kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin: 16px 0 24px; }
.kpi-card { border: 1px solid #d5dfe8; border-radius: 6px; padding: 10px 14px; background: #fff; }
.kpi-card .kpi-label { font-size: 7.5pt; color: #888; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 4px; }
.kpi-card .kpi-value { font-size: 13pt; font-weight: 700; line-height: 1.2; }
.kpi-card .kpi-sub   { font-size: 7.5pt; color: #888; margin-top: 3px; }
.kpi-card .kpi-bar   { height: 4px; border-radius: 2px; margin-top: 5px; background: #e5e7eb; overflow: hidden; }
.kpi-card .kpi-bar-fill { height: 4px; border-radius: 2px; }
.kpi-budget   { border-left: 3px solid #ef4444; }
.kpi-real     { border-left: 3px solid #f59e0b; }
.kpi-revenue  { border-left: 3px solid #10b981; }
.kpi-margin   { border-left: 3px solid #3b82f6; }
.text-red     { color: #dc2626; }
.text-yellow  { color: #d97706; }
.text-green   { color: #059669; }
.text-blue    { color: #2563eb; }

/* Footer */
.doc-footer { margin-top: 28px; padding-top: 10px; border-top: 1px solid #ccc; display: flex; justify-content: space-between; font-size: 8.5pt; color: #888; }
.no-print { text-align: center; margin: 24px 0 0; }
.btn-print { background: #1e3a5f; color: #fff; border: none; padding: 10px 28px; font-size: 11pt; border-radius: 5px; cursor: pointer; }
.empty { color: #aaa; font-style: italic; padding: 10px 12px; font-size: 9pt; }

@media print {
    body { font-size: 9.5pt; }
    .page { padding: 0; max-width: 100%; }
    .no-print { display: none !important; }
    .section { page-break-inside: avoid; }
    thead { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .section-header, .day-header, .sub-header, .tipe-group-header, .program-name { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    tr.from-content td, tbody tr:nth-child(even) td, tfoot td { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .chip, .badge-status, .badge-platform, .badge-tipe { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .progress-bar, .kpi-bar-fill { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .kpi-card { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .file-thumb img { max-width: 120px; }
    .program-block, .item-block { page-break-inside: avoid; }
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
$tipeIcons   = ['master_design' => '✏️', 'digital' => '📱', 'cetak' => '🖨️', 'influencer' => '🎥', 'media_prescon' => '📰'];
$platformLbl = ['ig' => 'Instagram', 'tiktok' => 'TikTok', 'keduanya' => 'IG & TikTok'];
$statusClass = ['draft' => 'badge-draft', 'review' => 'badge-review', 'approved' => 'badge-approved', 'revision' => 'badge-revision'];
$statusLabel = ['draft' => 'Draft', 'review' => 'Review', 'approved' => 'Approved', 'revision' => 'Revisi'];
$imageExts   = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

$insightDef = [
    'total_views'       => ['Views',         'chip-info'],
    'total_reach'       => ['Reach',         'chip-primary'],
    'total_impressions' => ['Impressions',   'chip-secondary'],
    'total_likes'       => ['Likes',         'chip-danger'],
    'total_comments'    => ['Komentar',      'chip-warning'],
    'total_shares'      => ['Share',         'chip-success'],
    'total_saves'       => ['Saves',         'chip-primary'],
    'total_followers'   => ['Follower Baru', 'chip-success'],
];
?>

<div class="page">

    <!-- Document Header -->
    <div class="doc-header">
        <div>
            <div class="doc-title"><?= esc($event['name']) ?></div>
            <div class="doc-meta">
                <span>📍 <strong><?= $mallLabels[$event['mall']] ?? esc($event['mall']) ?></strong></span>
                <?php if (!empty($eventLocations)): ?>
                <span>🏢 <strong><?= esc(implode(', ', array_column($eventLocations, 'nama'))) ?></strong></span>
                <?php endif; ?>
                <span>📅 <strong><?= $sameDay
                    ? date('l, d F Y', strtotime($startDate))
                    : date('l, d F Y', strtotime($startDate)) . ' – ' . date('l, d F Y', strtotime($endDate)) ?></strong></span>
                <span>⏱ <strong><?= $event['event_days'] ?> hari</strong></span>
                <?php if ($event['tema']): ?>
                <span>🎯 <strong><?= esc($event['tema']) ?></strong></span>
                <?php endif; ?>
            </div>
            <div class="doc-sub" style="margin-top:6px">Laporan Post Event</div>
        </div>
        <div style="text-align:right">
            <img src="<?= base_url('img/mic-logo.png') ?>" alt="MIC Logo" style="height:52px;object-fit:contain;margin-bottom:6px;display:block;margin-left:auto">
            <div class="doc-badge" style="display:inline-block">Dicetak: <?= date('d M Y, H:i') ?></div>
        </div>
    </div>

    <!-- KPI Cards -->
    <?php
    $profit    = $totalRevenue - $totalBudget;
    $profitPos = $profit >= 0;
    $realPct   = $totalBudget > 0 ? min(100, round($totalBudgetReal / $totalBudget * 100, 1)) : 0;
    $realColor = $totalBudgetReal > $totalBudget ? '#ef4444' : ($realPct >= 80 ? '#f59e0b' : '#10b981');
    $marginPct = $totalRevenue > 0 ? round($profit / $totalRevenue * 100, 1) : 0;
    ?>
    <div class="kpi-grid">
        <div class="kpi-card kpi-budget">
            <div class="kpi-label">Total Budget</div>
            <div class="kpi-value text-red">Rp <?= number_format($totalBudget, 0, ',', '.') ?></div>
            <div class="kpi-sub">
                <?= count($exhibitors) ?> exhibitor · <?= count($sponsors) ?> sponsor
            </div>
        </div>
        <div class="kpi-card kpi-real">
            <div class="kpi-label">Budget Realisasi</div>
            <div class="kpi-value text-yellow">Rp <?= number_format($totalBudgetReal, 0, ',', '.') ?></div>
            <?php if ($totalBudget > 0): ?>
            <div class="kpi-bar"><div class="kpi-bar-fill" style="width:<?= $realPct ?>%;background:<?= $realColor ?>"></div></div>
            <div class="kpi-sub"><?= $realPct ?>% dari total budget</div>
            <?php endif; ?>
        </div>
        <div class="kpi-card kpi-revenue">
            <div class="kpi-label">Total Revenue</div>
            <div class="kpi-value text-green">Rp <?= number_format($totalRevenue, 0, ',', '.') ?></div>
            <div class="kpi-sub">
                <?php if ($totalDealing > 0): ?>Exhibition: Rp <?= number_format($totalDealing, 0, ',', '.') ?><?php endif; ?>
                <?php if ($totalDealing > 0 && $totalSponsorCash > 0): ?> · <?php endif; ?>
                <?php if ($totalSponsorCash > 0): ?>Sponsor: Rp <?= number_format($totalSponsorCash, 0, ',', '.') ?><?php endif; ?>
            </div>
        </div>
        <div class="kpi-card kpi-margin">
            <div class="kpi-label">Margin Profit</div>
            <div class="kpi-value <?= $profitPos ? 'text-blue' : 'text-red' ?>">
                <?= $profitPos ? '' : '−' ?>Rp <?= number_format(abs($profit), 0, ',', '.') ?>
            </div>
            <div class="kpi-sub" style="color:<?= $profitPos ? '#2563eb' : '#dc2626' ?>">
                <?= ($marginPct >= 0 ? '+' : '') ?><?= $marginPct ?>% dari revenue
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════ -->
    <!-- 1. RUNDOWN                                  -->
    <!-- ═══════════════════════════════════════════ -->
    <div class="section">
        <div class="section-header">
            <span>1 &nbsp; Rundown</span>
            <span class="badge"><?= $event['event_days'] ?> Hari</span>
        </div>
        <?php if (empty($rundown)): ?>
        <div class="empty">Belum ada data rundown.</div>
        <?php else: ?>
        <?php foreach ($rundown as $hariKe => $rows):
            $tanggalHari = $rows[0]['tanggal'] ?? null;
        ?>
        <div class="day-header">
            Hari <?= $hariKe ?><?php if ($tanggalHari): ?> — <?= date('l, d F Y', strtotime($tanggalHari)) ?><?php endif; ?>
        </div>
        <table>
        <thead><tr>
            <th style="width:28px">#</th>
            <th style="width:110px">Waktu</th>
            <th style="width:28%">Sesi / Acara</th>
            <th>Deskripsi</th>
            <th style="width:110px">PIC</th>
            <th style="width:110px">Lokasi</th>
        </tr></thead>
        <tbody>
        <?php $no = 0; foreach ($rows as $r):
            $no++;
            $waktu = '';
            if ($r['waktu_mulai']) { $waktu = date('H:i', strtotime($r['waktu_mulai'])); if ($r['waktu_selesai']) $waktu .= '–'.date('H:i', strtotime($r['waktu_selesai'])); }
        ?>
        <tr class="<?= !empty($r['content_item_id']) ? 'from-content' : '' ?>">
            <td style="text-align:center;color:#999;font-size:8.5pt"><?= $no ?></td>
            <td style="font-weight:600;color:#1e3a5f;white-space:nowrap"><?= $waktu ?: '—' ?></td>
            <td>
                <div style="font-weight:600"><?= esc($r['sesi']) ?></div>
                <?php if (!empty($r['content_item_id'])): ?><div class="from-content-tag">Content Event</div><?php endif; ?>
            </td>
            <td style="color:#444"><?= esc($r['deskripsi'] ?: '') ?></td>
            <td style="color:#555"><?= esc($r['pic'] ?: '—') ?></td>
            <td style="color:#555"><?= esc($r['lokasi'] ?: '—') ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        </table>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- ═══════════════════════════════════════════ -->
    <!-- 2. CONTENT EVENT                            -->
    <!-- ═══════════════════════════════════════════ -->
    <?php $uploadBaseContent = base_url('uploads/content-realisasi/' . $event['id'] . '/'); ?>
    <div class="section">
        <div class="section-header">
            <span>2 &nbsp; Content Event</span>
            <span class="badge"><?= count($contentItems) ?> item<?= $contentRealTotal > 0 ? ' · Realisasi Rp '.number_format($contentRealTotal,0,',','.') : '' ?></span>
        </div>
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
            <span style="font-size:8.5pt;font-weight:400">
                <?= $gBudget > 0 ? 'Budget: Rp '.number_format($gBudget,0,',','.').' · ' : '' ?>Realisasi: Rp <?= number_format($gReal,0,',','.') ?>
            </span>
        </div>
        <table>
        <thead><tr>
            <th style="width:22%">Nama</th>
            <th style="width:8%">Tipe</th>
            <th style="width:9%">Tanggal</th>
            <th style="width:10%">Waktu</th>
            <th style="width:8%">PIC</th>
            <th style="width:9%">Lokasi</th>
            <th style="width:10%;text-align:right">Budget</th>
            <th style="width:10%;text-align:right">Realisasi</th>
            <th style="width:14%">Keterangan</th>
        </tr></thead>
        <tbody>
        <?php foreach ($group['items'] as $ci):
            $rList    = $contentRealisasiByItem[$ci['id']] ?? [];
            $rTotal   = array_sum(array_column($rList, 'nilai'));
            $pct      = $ci['budget'] > 0 ? min(100, round($rTotal / $ci['budget'] * 100)) : null;
            $barColor = $rTotal > $ci['budget'] && $ci['budget'] > 0 ? '#ef4444' : ($pct >= 80 ? '#f59e0b' : '#10b981');
        ?>
        <tr>
            <td>
                <strong><?= esc($ci['nama']) ?></strong>
                <?php if ($ci['jenis']): ?><br><span style="font-size:7.5pt;color:#666"><?= esc($ci['jenis']) ?></span><?php endif; ?>
            </td>
            <td><?= esc($ci['tipe'] ?? '—') ?></td>
            <td><?= $ci['tanggal'] ? date('d/m/Y', strtotime($ci['tanggal'])) : '—' ?></td>
            <td><?= $ci['waktu_mulai'] ? substr($ci['waktu_mulai'],0,5).($ci['waktu_selesai'] ? '–'.substr($ci['waktu_selesai'],0,5) : '') : '—' ?></td>
            <td><?= esc($ci['pic'] ?: '—') ?></td>
            <td><?= esc($ci['lokasi'] ?: '—') ?></td>
            <td style="text-align:right"><?= $ci['budget'] ? 'Rp '.number_format($ci['budget'],0,',','.') : '—' ?></td>
            <td style="text-align:right">
                <?= $rTotal > 0 ? 'Rp '.number_format($rTotal,0,',','.') : '—' ?>
                <?php if ($pct !== null): ?>
                <div class="progress-wrap"><div class="progress-bar" style="width:<?= min(100,$pct) ?>%;background:<?= $barColor ?>"></div></div>
                <div style="font-size:7pt;color:#888"><?= $pct ?>%</div>
                <?php endif; ?>
            </td>
            <td><?= esc($ci['keterangan'] ?: '—') ?></td>
        </tr>
        <?php if (!empty($rList)): ?>
        <tr>
            <td colspan="9" style="padding:4px 10px 8px 18px;background:#f8fafc">
                <div style="font-size:8pt;font-weight:700;color:#555;margin-bottom:4px">Detail Realisasi:</div>
                <table style="width:100%;font-size:8.5pt">
                <thead><tr>
                    <th style="width:90px;background:#eef2f7;padding:3px 6px">Tanggal</th>
                    <th style="width:100px;background:#eef2f7;padding:3px 6px;text-align:right">Nilai</th>
                    <th style="background:#eef2f7;padding:3px 6px">Catatan</th>
                    <th style="width:70px;background:#eef2f7;padding:3px 6px">Foto</th>
                    <th style="width:70px;background:#eef2f7;padding:3px 6px">Terima</th>
                </tr></thead>
                <tbody>
                <?php foreach ($rList as $r): ?>
                <tr>
                    <td style="padding:3px 6px"><?= $r['tanggal'] ? date('d/m/Y', strtotime($r['tanggal'])) : '—' ?></td>
                    <td style="padding:3px 6px;text-align:right"><?= $r['nilai'] ? 'Rp '.number_format($r['nilai'],0,',','.') : '—' ?></td>
                    <td style="padding:3px 6px"><?= esc($r['catatan'] ?: '—') ?></td>
                    <td style="padding:3px 6px">
                        <?php if ($r['file_foto']): $extc = strtolower(pathinfo($r['file_foto'], PATHINFO_EXTENSION)); ?>
                            <?php if (in_array($extc, ['jpg','jpeg','png','gif','webp'])): ?>
                            <img src="<?= $uploadBaseContent.$r['file_foto'] ?>" style="width:50px;height:38px;object-fit:cover;border-radius:3px">
                            <?php else: ?>
                            <span style="font-size:7.5pt">📄</span>
                            <?php endif; ?>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td style="padding:3px 6px">
                        <?php if ($r['file_terima']): $extc2 = strtolower(pathinfo($r['file_terima'], PATHINFO_EXTENSION)); ?>
                            <?php if (in_array($extc2, ['jpg','jpeg','png','gif','webp'])): ?>
                            <img src="<?= $uploadBaseContent.$r['file_terima'] ?>" style="width:50px;height:38px;object-fit:cover;border-radius:3px">
                            <?php else: ?>
                            <span style="font-size:7.5pt">📄</span>
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
            <td colspan="6"><strong>Total <?= $group['label'] ?></strong></td>
            <td style="text-align:right"><strong><?= $gBudget > 0 ? 'Rp '.number_format($gBudget,0,',','.') : '—' ?></strong></td>
            <td style="text-align:right"><strong><?= $gReal > 0 ? 'Rp '.number_format($gReal,0,',','.') : '—' ?></strong></td>
            <td></td>
        </tr></tfoot>
        <?php endif; ?>
        </table>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- ═══════════════════════════════════════════ -->
    <!-- 3. DEKORASI / VM                            -->
    <!-- ═══════════════════════════════════════════ -->
    <div class="section">
        <?php $vmBudgetTotal = array_sum(array_column($vmItems, 'budget'));
              $vmRealTotal   = array_sum(array_map(fn($r) => $r['total'] ?? 0, $vmRealisasi)); ?>
        <div class="section-header">
            <span>3 &nbsp; Dekorasi / Visual Merchandising</span>
            <span class="badge"><?= count($vmItems) ?> item<?= $vmRealTotal > 0 ? ' · Realisasi Rp '.number_format($vmRealTotal,0,',','.') : '' ?></span>
        </div>
        <?php if (empty($vmItems)): ?>
        <div class="empty">Belum ada data dekorasi.</div>
        <?php else: ?>
        <table>
        <thead><tr>
            <th style="width:28px">#</th>
            <th style="width:30%">Item</th>
            <th>Deskripsi / Referensi</th>
            <th style="width:120px;text-align:right">Budget</th>
            <th style="width:120px;text-align:right">Realisasi</th>
        </tr></thead>
        <tbody>
        <?php foreach ($vmItems as $i => $vm):
            $vmReal = $vmRealisasi[$vm['id']] ?? ['total' => 0];
            $vmPct  = $vm['budget'] > 0 ? min(100, round($vmReal['total'] / $vm['budget'] * 100)) : null;
            $vmCol  = $vmReal['total'] > $vm['budget'] && $vm['budget'] > 0 ? 'danger' : 'success';
        ?>
        <tr>
            <td style="text-align:center;color:#999;font-size:8.5pt"><?= $i + 1 ?></td>
            <td style="font-weight:600"><?= esc($vm['nama_item']) ?>
                <?php if ($vm['catatan']): ?><div style="font-size:8.5pt;color:#888;font-weight:400"><?= esc($vm['catatan']) ?></div><?php endif; ?>
            </td>
            <td style="color:#444;white-space:pre-line;font-size:9pt"><?= esc($vm['deskripsi_referensi'] ?: '—') ?></td>
            <td style="text-align:right;color:#555">
                <?= $vm['budget'] > 0 ? 'Rp '.number_format($vm['budget'],0,',','.') : '—' ?>
            </td>
            <td style="text-align:right">
                <?php if ($vmReal['total'] > 0): ?>
                <span style="font-weight:700;color:<?= $vmCol === 'danger' ? '#dc2626' : '#059669' ?>">
                    Rp <?= number_format($vmReal['total'],0,',','.') ?>
                </span>
                <?php if ($vmPct !== null): ?>
                <div style="font-size:8pt;color:#888"><?= $vmPct ?>%</div>
                <div class="progress-wrap"><div class="progress-bar bar-<?= $vmCol ?>" style="width:<?= min(100,$vmPct) ?>%"></div></div>
                <?php endif; ?>
                <?php else: ?>
                <span style="color:#aaa">—</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if ($vmBudgetTotal > 0 || $vmRealTotal > 0): ?>
        <tfoot><tr>
            <td colspan="3" style="text-align:right">Total</td>
            <td style="text-align:right"><?= $vmBudgetTotal > 0 ? 'Rp '.number_format($vmBudgetTotal,0,',','.') : '—' ?></td>
            <td style="text-align:right;color:<?= $vmRealTotal > $vmBudgetTotal && $vmBudgetTotal > 0 ? '#dc2626' : '#059669' ?>">
                <?= $vmRealTotal > 0 ? 'Rp '.number_format($vmRealTotal,0,',','.') : '—' ?>
            </td>
        </tr></tfoot>
        <?php endif; ?>
        </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- ═══════════════════════════════════════════ -->
    <!-- 4. EXHIBITION                               -->
    <!-- ═══════════════════════════════════════════ -->
    <div class="section">
        <?php
        $exTotal  = array_sum(array_column(array_merge(...(array_values($exhibitorsByKat) ?: [[]])), 'nilai_dealing'));
        $exCount  = array_sum(array_map('count', $exhibitorsByKat));
        $exBadge  = $exCount . ($tgtExJumlah > 0 ? '/'.$tgtExJumlah : '') . ' exhibitor';
        if ($exTotal > 0) $exBadge .= ' · Rp ' . number_format($exTotal,0,',','.');
        if ($tgtExNilai > 0) $exBadge .= ' / target Rp ' . number_format($tgtExNilai,0,',','.');
        ?>
        <div class="section-header">
            <span>4 &nbsp; Exhibition by Casual Leasing</span>
            <span class="badge"><?= $exBadge ?></span>
        </div>
        <?php if (empty($exhibitorsByKat)): ?>
        <div class="empty">Belum ada data exhibition.</div>
        <?php else: ?>
        <?php foreach ($exhibitorsByKat as $kat => $exList): ?>
        <div class="sub-header"><?= esc($kat) ?> <span style="font-weight:400;font-size:8.5pt">(<?= count($exList) ?>)</span></div>
        <table>
        <thead><tr>
            <th style="width:28px">#</th>
            <th style="width:20%">Booth</th>
            <th style="width:30%">Nama Exhibitor</th>
            <th>Program</th>
            <th style="width:130px;text-align:right">Nilai Dealing</th>
        </tr></thead>
        <tbody>
        <?php foreach ($exList as $i => $ex):
            $exProgs = $progsByExhibitor[$ex['id']] ?? [];
        ?>
        <tr>
            <td style="text-align:center;color:#999;font-size:8.5pt"><?= $i + 1 ?></td>
            <td style="color:#666"><?= esc($ex['lokasi_booth'] ?: '—') ?></td>
            <td style="font-weight:600"><?= esc($ex['nama_exhibitor']) ?></td>
            <td style="font-size:9pt">
                <?php if (empty($exProgs)): ?><span style="color:#aaa">—</span><?php else: ?>
                <?php foreach ($exProgs as $p):
                    $jam = ''; if ($p['jam_mulai']) { $jam = substr($p['jam_mulai'],0,5); if ($p['jam_selesai']) $jam .= '–'.substr($p['jam_selesai'],0,5); }
                    $periode = ''; if ($p['tanggal_mulai']) { $periode = date('d/m',strtotime($p['tanggal_mulai'])); if ($p['tanggal_selesai'] && $p['tanggal_selesai'] !== $p['tanggal_mulai']) $periode .= '–'.date('d/m',strtotime($p['tanggal_selesai'])); }
                ?>
                <div>• <?= esc($p['nama_program']) ?><?php if ($periode||$jam): ?> <span style="color:#2563eb;font-weight:600"><?= trim($periode.' '.$jam) ?></span><?php endif; ?></div>
                <?php endforeach; ?>
                <?php endif; ?>
            </td>
            <td style="text-align:right;font-weight:600;color:#059669">Rp <?= number_format($ex['nilai_dealing'],0,',','.') ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        </table>
        <?php endforeach; ?>
        <?php if ($exTotal > 0 || $tgtExNilai > 0): ?>
        <table><tfoot>
        <tr>
            <td colspan="4" style="text-align:right">Total Dealing</td>
            <td style="text-align:right;font-weight:700;color:#059669">Rp <?= number_format($exTotal,0,',','.') ?></td>
        </tr>
        <?php if ($tgtExNilai > 0): ?>
        <tr style="background:#f9fafb">
            <td colspan="4" style="text-align:right;color:#6b7280">Target Dealing</td>
            <td style="text-align:right;color:#6b7280">Rp <?= number_format($tgtExNilai,0,',','.') ?></td>
        </tr>
        <tr style="background:#f0fdf4">
            <td colspan="4" style="text-align:right;color:#374151">Pencapaian Dealing</td>
            <td style="text-align:right;font-weight:700;color:<?= $pctExNilai >= 100 ? '#059669' : ($pctExNilai >= 60 ? '#2563eb' : ($pctExNilai >= 30 ? '#d97706' : '#dc2626')) ?>">
                <?= $pctExNilai ?>%
            </td>
        </tr>
        <?php endif; ?>
        <?php if ($tgtExJumlah > 0): ?>
        <tr style="background:#f9fafb">
            <td colspan="4" style="text-align:right;color:#6b7280">Jumlah Exhibitor</td>
            <td style="text-align:right;color:#374151"><?= $exCount ?> / target <?= $tgtExJumlah ?> &nbsp;
                <span style="font-weight:700;color:<?= $pctExJumlah >= 100 ? '#059669' : ($pctExJumlah >= 60 ? '#2563eb' : '#d97706') ?>">(<?= $pctExJumlah ?>%)</span>
            </td>
        </tr>
        <?php endif; ?>
        </tfoot></table>
        <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- ═══════════════════════════════════════════ -->
    <!-- 5. PROGRAM LOYALTY                          -->
    <!-- ═══════════════════════════════════════════ -->
    <div class="section">
        <div class="section-header">
            <span>5 &nbsp; Program Loyalty</span>
            <span class="badge"><?= count($programs) ?> program</span>
        </div>
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
        ?>
        <div class="program-block">
            <div class="program-name">
                <span><?= esc($pr['nama_program']) ?></span>
                <?php if ($autoBudget > 0): ?>
                <span style="font-size:9pt;font-weight:400;color:#555">Budget: Rp <?= number_format($autoBudget,0,',','.') ?></span>
                <?php endif; ?>
            </div>
            <div class="program-body">
                <?php if ($mTarget > 0 || $mTotal > 0): ?>
                <div class="section-label">👥 Member</div>
                <div class="stat-row">
                    <div class="stat-item">
                        <div class="stat-val" style="color:#1e3a5f"><?= number_format($mTotal) ?></div>
                        <div class="stat-lbl">Terdaftar</div>
                    </div>
                    <?php if ($mTarget > 0): ?>
                    <div class="stat-item">
                        <div class="stat-val" style="color:#6b7280"><?= number_format($mTarget) ?></div>
                        <div class="stat-lbl">Target</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-val" style="color:<?= $mCol === 'success' ? '#059669' : ($mCol === 'danger' ? '#dc2626' : ($mCol === 'warning' ? '#d97706' : '#2563eb')) ?>"><?= $mPct ?? '—' ?>%</div>
                        <div class="stat-lbl">Pencapaian</div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php if ($mPct !== null): ?>
                <div class="progress-wrap" style="max-width:300px;margin-top:4px">
                    <div class="progress-bar bar-<?= $mCol ?>" style="width:<?= $mPct ?>%"></div>
                </div>
                <?php endif; ?>
                <?php endif; ?>

                <?php if (!empty($vouchers)): ?>
                <div class="section-label" style="margin-top:10px">🎟 e-Voucher</div>
                <table>
                <thead><tr>
                    <th>Nama Voucher</th>
                    <th style="width:110px;text-align:right">Nilai</th>
                    <th style="width:100px;text-align:right">Diterbitkan</th>
                    <th style="width:100px;text-align:right">Tersebar</th>
                    <th style="width:100px;text-align:right">Terpakai</th>
                    <th style="width:80px;text-align:right">Target Serap</th>
                    <th style="width:80px;text-align:right">Serapan</th>
                </tr></thead>
                <tbody>
                <?php foreach ($vouchers as $v):
                    $vr    = $voucherRealisasi[$v['id']] ?? ['total_terpakai' => 0, 'total_tersebar' => 0];
                    $tPct  = (int)$v['total_diterbitkan'] > 0 ? min(100, round($vr['total_terpakai'] / $v['total_diterbitkan'] * 100)) : 0;
                    $tCol  = $tPct >= ($v['target_penyerapan'] ?? 0) ? 'success' : ($tPct >= 50 ? 'warning' : 'danger');
                ?>
                <tr>
                    <td style="font-weight:600"><?= esc($v['nama_voucher']) ?></td>
                    <td style="text-align:right">Rp <?= number_format($v['nilai_voucher'],0,',','.') ?></td>
                    <td style="text-align:right"><?= number_format($v['total_diterbitkan']) ?></td>
                    <td style="text-align:right"><?= number_format($vr['total_tersebar']) ?></td>
                    <td style="text-align:right;font-weight:700;color:#059669"><?= number_format($vr['total_terpakai']) ?></td>
                    <td style="text-align:right;color:#2563eb"><?= ($v['target_penyerapan'] !== null && $v['target_penyerapan'] !== '') ? (float)$v['target_penyerapan'].'%' : '—' ?></td>
                    <td style="text-align:right">
                        <span style="font-weight:700;color:<?= $tCol === 'success' ? '#059669' : ($tCol === 'danger' ? '#dc2626' : '#d97706') ?>"><?= $tPct ?>%</span>
                        <div class="progress-wrap"><div class="progress-bar bar-<?= $tCol ?>" style="width:<?= $tPct ?>%"></div></div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
                </table>
                <?php if (count($vouchers) > 1): ?>
                <div style="font-size:8.5pt;color:#555;margin-top:4px;text-align:right">
                    Total terpakai: <strong><?= number_format($vTerpakai) ?></strong> / <?= number_format($vQty) ?> pcs
                </div>
                <?php endif; ?>
                <?php endif; ?>

                <?php if (!empty($hadiahList)): ?>
                <div class="section-label" style="margin-top:10px">🎁 Hadiah</div>
                <table>
                <thead><tr>
                    <th>Nama Hadiah</th>
                    <th style="width:90px;text-align:right">Stok</th>
                    <th style="width:90px;text-align:right">Dibagikan</th>
                    <th style="width:80px;text-align:right">Sisa</th>
                    <th style="width:80px;text-align:right">Distribusi</th>
                    <th style="width:130px;text-align:right">Nilai Total</th>
                </tr></thead>
                <tbody>
                <?php foreach ($hadiahList as $h):
                    $hr   = $hadiahRealisasi[$h['id']] ?? ['total' => 0];
                    $hPct = (int)$h['stok'] > 0 ? min(100, round($hr['total'] / $h['stok'] * 100)) : 0;
                    $hCol = $hPct >= 100 ? 'success' : ($hPct >= 60 ? 'primary' : 'warning');
                ?>
                <tr>
                    <td style="font-weight:600"><?= esc($h['nama_hadiah']) ?></td>
                    <td style="text-align:right"><?= number_format($h['stok']) ?></td>
                    <td style="text-align:right;font-weight:700;color:#059669"><?= number_format($hr['total']) ?></td>
                    <td style="text-align:right;color:#6b7280"><?= number_format(max(0, $h['stok'] - $hr['total'])) ?></td>
                    <td style="text-align:right">
                        <span style="font-weight:700"><?= $hPct ?>%</span>
                        <div class="progress-wrap"><div class="progress-bar bar-<?= $hCol ?>" style="width:<?= $hPct ?>%"></div></div>
                    </td>
                    <td style="text-align:right;color:#555">Rp <?= number_format($h['stok'] * $h['nilai_satuan'],0,',','.') ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
                </table>
                <?php endif; ?>

                <?php if (empty($vouchers) && empty($hadiahList) && $mTotal === 0): ?>
                <div style="font-size:9pt;color:#aaa;font-style:italic">Belum ada data realisasi.</div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- ═══════════════════════════════════════════ -->
    <!-- 6. SPONSORSHIP                              -->
    <!-- ═══════════════════════════════════════════ -->
    <div class="section">
        <?php
        $totalCash   = array_sum(array_column(array_filter($sponsors, fn($s) => $s['jenis'] === 'cash'), 'nilai'));
        $totalInKind = array_sum(array_map(fn($s) => array_sum(array_column($itemsBySponsors[$s['id']] ?? [], 'qty')), array_filter($sponsors, fn($s) => $s['jenis'] !== 'cash')));
        ?>
        <div class="section-header">
            <span>6 &nbsp; Sponsorship</span>
            <span class="badge"><?= count($sponsors) ?> sponsor<?= $totalCash > 0 ? ' · Cash Rp '.number_format($totalCash,0,',','.') : '' ?></span>
        </div>
        <?php if (empty($sponsors)): ?>
        <div class="empty">Belum ada data sponsor.</div>
        <?php else: ?>
        <table>
        <thead><tr>
            <th style="width:28px">#</th>
            <th style="width:35%">Nama Sponsor</th>
            <th style="width:80px;text-align:center">Jenis</th>
            <th>Detail / Item</th>
            <th style="width:130px;text-align:right">Nilai</th>
        </tr></thead>
        <tbody>
        <?php foreach ($sponsors as $i => $sp):
            $spItems = $itemsBySponsors[$sp['id']] ?? [];
        ?>
        <tr>
            <td style="text-align:center;color:#999;font-size:8.5pt"><?= $i + 1 ?></td>
            <td style="font-weight:600"><?= esc($sp['nama_sponsor']) ?></td>
            <td style="text-align:center">
                <span class="badge-status <?= $sp['jenis'] === 'cash' ? 'sponsor-cash' : 'sponsor-inkind' ?>">
                    <?= $sp['jenis'] === 'cash' ? 'Cash' : 'In-Kind' ?>
                </span>
            </td>
            <td style="font-size:9pt">
                <?php if ($sp['jenis'] === 'barang' && !empty($spItems)): ?>
                <?php foreach ($spItems as $si): ?><div>• <?= esc($si['deskripsi_barang'] ?: '—') ?><?= $si['qty'] ? ' · '.number_format($si['qty']).' pcs' : '' ?></div><?php endforeach; ?>
                <?php elseif ($sp['deskripsi'] ?? null): ?><span style="color:#555"><?= esc($sp['deskripsi']) ?></span>
                <?php else: ?><span style="color:#aaa">—</span><?php endif; ?>
            </td>
            <td style="text-align:right;font-weight:600">
                <?php if ($sp['jenis'] === 'cash'): ?>
                <span style="color:#059669">Rp <?= number_format($sp['nilai'],0,',','.') ?></span>
                <?php else: ?>
                <span style="color:#b45309"><?= number_format(array_sum(array_column($spItems,'qty'))) ?> pcs</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        <tfoot>
            <?php if ($totalCash > 0): ?>
            <tr><td colspan="4" style="text-align:right">Total Cash</td><td style="text-align:right;color:#059669">Rp <?= number_format($totalCash,0,',','.') ?></td></tr>
            <?php endif; ?>
            <?php if ($totalInKind > 0): ?>
            <tr><td colspan="4" style="text-align:right">Total In-Kind</td><td style="text-align:right;color:#b45309"><?= number_format($totalInKind) ?> pcs</td></tr>
            <?php endif; ?>
        </tfoot>
        </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- ═══════════════════════════════════════════ -->
    <!-- 7. CREATIVE & DESIGN                        -->
    <!-- ═══════════════════════════════════════════ -->
    <div class="section">
        <?php $creativeBudgetTotal = array_sum(array_column($creativeItems, 'budget'));
              $creativeRealTotal   = array_sum(array_map(fn($r) => $r['total'] ?? 0, $creativeRealisasi)); ?>
        <div class="section-header">
            <span>7 &nbsp; Creative, Concept &amp; Design</span>
            <span class="badge"><?= count($creativeItems) ?> item<?= $creativeRealTotal > 0 ? ' · Realisasi Rp '.number_format($creativeRealTotal,0,',','.') : '' ?></span>
        </div>
        <?php if (empty($creativeItems)): ?>
        <div class="empty">Belum ada data creative & design.</div>
        <?php else: ?>
        <?php foreach ($tipeLabels as $tipe => $tipeLabel):
            $tipeItems = $byTipe[$tipe] ?? [];
            if (empty($tipeItems)) continue;
            $tipeBudget = array_sum(array_column($tipeItems, 'budget'));
            $tipeReal   = array_sum(array_map(fn($ci) => ($creativeRealisasi[$ci['id']]['total'] ?? 0), $tipeItems));
        ?>
        <div class="tipe-group-header">
            <span><?= $tipeIcons[$tipe] ?> <?= $tipeLabel ?> <span style="font-weight:400;font-size:8.5pt">(<?= count($tipeItems) ?>)</span></span>
            <?php if ($tipeBudget > 0 || $tipeReal > 0): ?>
            <span style="font-size:8.5pt;font-weight:400">
                <?php if ($tipeBudget > 0): ?>Budget: Rp <?= number_format($tipeBudget,0,',','.') ?><?php endif; ?>
                <?php if ($tipeReal > 0): ?> · Realisasi: <strong>Rp <?= number_format($tipeReal,0,',','.') ?></strong><?php endif; ?>
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
                <span class="badge-status <?= $statusClass[$ci['status']] ?? 'badge-draft' ?>" style="margin-right:6px"><?= $statusLabel[$ci['status']] ?? $ci['status'] ?></span>
                <?php endif; ?>
                <?php if ($ci['platform']): ?>
                <span class="badge-platform" style="margin-right:6px"><?= $platformLbl[$ci['platform']] ?? $ci['platform'] ?></span>
                <?php endif; ?>
                <?= esc($ci['nama']) ?>
                <?php if ($ciBudget > 0): ?>
                <span style="font-size:9pt;font-weight:400;color:#555;margin-left:8px">· Budget: Rp <?= number_format($ciBudget,0,',','.') ?></span>
                <?php endif; ?>
            </div>
            <div class="item-meta">
                <?php if ($tipe === 'digital' && ($ci['tanggal_take'] || $ci['pic'])): ?>
                <?php if ($ci['tanggal_take']): ?><span>📷 <?= date('d M Y', strtotime($ci['tanggal_take'])) ?><?= $ci['jam_take'] ? ' '.substr($ci['jam_take'],0,5) : '' ?></span><?php endif; ?>
                <?php if ($ci['pic']): ?><span>👤 <?= esc($ci['pic']) ?></span><?php endif; ?>
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
                    <div style="width:120px;height:90px;display:flex;align-items:center;justify-content:center;background:#f3f4f6;font-size:9pt;color:#6b7280">
                        <?= strtoupper($ext) ?>
                    </div>
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
                <span class="chip <?= $cls ?>"><?= number_format($val,0,',','.') ?> <?= $label ?></span>
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
            <table style="margin-top:6px">
            <thead><tr>
                <th style="width:100px">Tanggal</th>
                <th>Influencer</th>
                <th style="width:130px;text-align:right">Nilai</th>
                <th style="width:70px;text-align:center">SS Insight</th>
                <th style="width:80px;text-align:center">Serah Terima</th>
                <th>Catatan</th>
            </tr></thead>
            <tbody>
            <?php foreach ($ciRData['entries'] as $e): ?>
            <tr>
                <td style="font-size:9pt"><?= date('d M Y', strtotime($e['tanggal'])) ?></td>
                <td style="font-weight:600"><?= esc($e['nama_influencer'] ?? '—') ?></td>
                <td style="text-align:right;font-weight:700;color:#059669">Rp <?= number_format($e['nilai'],0,',','.') ?></td>
                <td style="text-align:center">
                    <?php if ($e['file_name'] && in_array(strtolower(pathinfo($e['file_name'],PATHINFO_EXTENSION)), $imageExts)): ?>
                    <img src="<?= base_url('uploads/creative/'.$ci['event_id'].'/'.$e['file_name']) ?>"
                         style="height:40px;width:auto;border-radius:3px;object-fit:cover">
                    <?php elseif ($e['file_name']): ?>
                    <span style="font-size:8pt;color:#2563eb">📎 file</span>
                    <?php else: ?><span style="color:#ccc">—</span><?php endif; ?>
                </td>
                <td style="text-align:center">
                    <?php if ($e['serah_terima_file_name'] && in_array(strtolower(pathinfo($e['serah_terima_file_name'],PATHINFO_EXTENSION)), $imageExts)): ?>
                    <img src="<?= base_url('uploads/creative/'.$ci['event_id'].'/'.$e['serah_terima_file_name']) ?>"
                         style="height:40px;width:auto;border-radius:3px;object-fit:cover">
                    <?php elseif ($e['serah_terima_file_name']): ?>
                    <span style="font-size:8pt;color:#2563eb">📎 file</span>
                    <?php else: ?><span style="color:#ccc">—</span><?php endif; ?>
                </td>
                <td style="font-size:9pt;color:#666"><?= esc($e['catatan'] ?: '—') ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if ($ciTotal > 0): ?>
            <tfoot><tr>
                <td colspan="2" style="text-align:right">Total</td>
                <td style="text-align:right;color:#059669">Rp <?= number_format($ciTotal,0,',','.') ?></td>
                <td colspan="3"></td>
            </tr></tfoot>
            <?php endif; ?>
            </tbody>
            </table>
            <?php endif; ?>

            <?php /* ── CETAK: realisasi biaya table ── */ ?>
            <?php if ($tipe === 'cetak' && !empty($ciRData['entries'])): ?>
            <table style="margin-top:6px">
            <thead><tr>
                <th style="width:100px">Tanggal</th>
                <th style="width:150px;text-align:right">Nilai</th>
                <th>Catatan</th>
                <th style="width:80px">Bukti Terpasang</th>
            </tr></thead>
            <tbody>
            <?php foreach ($ciRData['entries'] as $e): ?>
            <tr>
                <td style="font-size:9pt"><?= date('d M Y', strtotime($e['tanggal'])) ?></td>
                <td style="text-align:right;font-weight:700;color:#059669">Rp <?= number_format($e['nilai'],0,',','.') ?></td>
                <td style="font-size:9pt;color:#666"><?= esc($e['catatan'] ?: '—') ?></td>
                <td>
                    <?php if ($e['bukti_terpasang_file_name']):
                        $btUrl = base_url('uploads/creative/'.$ci['event_id'].'/'.$e['bukti_terpasang_file_name']);
                        $btExt = strtolower(pathinfo($e['bukti_terpasang_file_name'], PATHINFO_EXTENSION));
                    ?>
                        <?php if (in_array($btExt, $imageExts)): ?>
                        <img src="<?= $btUrl ?>" style="width:60px;height:45px;object-fit:cover;border-radius:3px">
                        <?php else: ?>
                        <span style="font-size:7.5pt">📄</span>
                        <?php endif; ?>
                    <?php else: ?>—<?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if ($ciTotal > 0): ?>
            <tfoot><tr>
                <td style="text-align:right">Total</td>
                <td style="text-align:right;color:#059669">Rp <?= number_format($ciTotal,0,',','.') ?></td>
                <td><?php if ($ciPct !== null): ?><span style="font-size:8.5pt"><?= $ciPct ?>% dari budget</span><?php endif; ?></td>
                <td></td>
            </tr></tfoot>
            <?php endif; ?>
            </tbody>
            </table>
            <?php endif; ?>

            <?php /* ── Realisasi Media Prescon ── */ ?>
            <?php if ($tipe === 'media_prescon' && !empty($ciRData['entries'])): ?>
            <table style="margin-top:6px">
            <thead><tr>
                <th style="width:100px">Tanggal</th>
                <th style="width:150px;text-align:right">Nilai</th>
                <th>Catatan</th>
                <th style="width:80px">Dokumentasi</th>
            </tr></thead>
            <tbody>
            <?php foreach ($ciRData['entries'] as $e): ?>
            <tr>
                <td style="font-size:9pt"><?= date('d M Y', strtotime($e['tanggal'])) ?></td>
                <td style="text-align:right;font-weight:700;color:#059669">Rp <?= number_format($e['nilai'],0,',','.') ?></td>
                <td style="font-size:9pt;color:#666"><?= esc($e['catatan'] ?: '—') ?></td>
                <td>
                    <?php if ($e['file_name']):
                        $mpUrl = base_url('uploads/creative/'.$ci['event_id'].'/'.$e['file_name']);
                        $mpExt = strtolower(pathinfo($e['file_name'], PATHINFO_EXTENSION));
                    ?>
                        <?php if (in_array($mpExt, $imageExts)): ?>
                        <img src="<?= $mpUrl ?>" style="width:60px;height:45px;object-fit:cover;border-radius:3px">
                        <?php else: ?>
                        <span style="font-size:7.5pt">📄</span>
                        <?php endif; ?>
                    <?php else: ?>—<?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if ($ciTotal > 0): ?>
            <tfoot><tr>
                <td style="text-align:right">Total</td>
                <td style="text-align:right;color:#059669">Rp <?= number_format($ciTotal,0,',','.') ?></td>
                <td><?php if ($ciPct !== null): ?><span style="font-size:8.5pt"><?= $ciPct ?>% dari budget</span><?php endif; ?></td>
                <td></td>
            </tr></tfoot>
            <?php endif; ?>
            </tbody>
            </table>
            <?php endif; ?>

            <?php /* ── Realisasi biaya digital ── */ ?>
            <?php if ($tipe === 'digital' && $ciTotal > 0): ?>
            <div style="font-size:9pt;color:#555;margin-top:4px">
                💰 Realisasi biaya: <strong style="color:#059669">Rp <?= number_format($ciTotal,0,',','.') ?></strong>
                <?php if ($ciBudget > 0): ?>(<?= $ciPct ?>% dari Rp <?= number_format($ciBudget,0,',','.') ?>)<?php endif; ?>
            </div>
            <?php endif; ?>

        </div><!-- /item-block -->
        <?php endforeach; ?>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="doc-footer">
        <span>Dicetak: <?= date('d F Y, H:i') ?></span>
        <span>Laporan Post Event — <?= esc($event['name']) ?></span>
        <span>Mall Intelligence Center</span>
    </div>

</div>

<div class="no-print">
    <button class="btn-print" onclick="window.print()">🖨️ Print / Save PDF</button>
    &nbsp;
    <a href="<?= base_url('events/'.$event['id'].'/summary') ?>" style="font-size:10pt;color:#666;margin-left:12px">← Kembali ke Summary</a>
</div>

<script>
if (new URLSearchParams(window.location.search).get('print') === '1') window.print();
</script>
</body>
</html>
