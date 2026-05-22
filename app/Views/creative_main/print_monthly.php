<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan Bulanan Creative — <?= $bulan ?></title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: Arial, sans-serif; font-size: 10.5px; color: #111; background: #fff; }

@page { size: A4 landscape; margin: 12mm 14mm 10mm; }
@media print {
    .no-print { display: none !important; }
    body { font-size: 10px; }
}

/* ── Header ── */
.doc-header {
    border-bottom: 3px solid #1e293b; padding-bottom: 10px; margin-bottom: 16px;
    display: flex; justify-content: space-between; align-items: flex-end;
}
.doc-header .title { font-size: 17px; font-weight: 700; color: #1e293b; }
.doc-header .sub   { font-size: 12px; color: #475569; margin-top: 2px; }
.doc-header .org   { font-size: 9px; color: #94a3b8; margin-top: 5px; }
.doc-header .meta  { text-align: right; font-size: 9px; color: #64748b; line-height: 1.8; }

/* ── KPI ── */
.kpi-row { display: flex; gap: 10px; margin-bottom: 16px; }
.kpi-box {
    flex: 1; border: 1px solid #e2e8f0; border-radius: 6px;
    padding: 9px 12px; background: #f8fafc;
}
.kpi-box .kpi-label { font-size: 8.5px; color: #64748b; margin-bottom: 3px; }
.kpi-box .kpi-num   { font-size: 18px; font-weight: 700; line-height: 1.1; }
.kpi-box .kpi-sub   { font-size: 8px; color: #94a3b8; margin-top: 2px; }
.kpi-total  { border-color: #bfdbfe; background: #eff6ff; }
.kpi-total .kpi-num  { color: #1d4ed8; }
.kpi-budget { border-color: #fecaca; background: #fef2f2; }
.kpi-budget .kpi-num { color: #b91c1c; }
.kpi-real   { border-color: #fde68a; background: #fffbeb; }
.kpi-real .kpi-num   { color: #b45309; }
.kpi-reach  { border-color: #a5f3fc; background: #ecfeff; }
.kpi-reach .kpi-num  { color: #0e7490; }
.kpi-impr   { border-color: #c4b5fd; background: #f5f3ff; }
.kpi-impr .kpi-num   { color: #6d28d9; }

/* ── Status strip ── */
.status-strip { display: flex; gap: 8px; margin-bottom: 14px; align-items: center; font-size: 9.5px; }
.status-strip .lbl { color: #64748b; font-weight: 700; margin-right: 4px; }
.sbadge {
    display: inline-block; padding: 2px 9px; border-radius: 3px;
    font-size: 9px; font-weight: 700; border: 1px solid;
}
.sbadge-draft    { background: #f1f5f9; color: #475569; border-color: #cbd5e1; }
.sbadge-review   { background: #fffbeb; color: #92400e; border-color: #fde68a; }
.sbadge-approved { background: #f0fdf4; color: #15803d; border-color: #bbf7d0; }
.sbadge-revision { background: #fef2f2; color: #b91c1c; border-color: #fecaca; }

/* ── Section title ── */
.sec-title {
    font-size: 9.5px; font-weight: 700; color: #475569; text-transform: uppercase;
    letter-spacing: .4px; background: #1e293b; color: #f1f5f9;
    padding: 4px 10px; margin-bottom: 0; border-radius: 4px 4px 0 0;
    display: flex; justify-content: space-between; align-items: center;
}
.sec-title .sec-sub { font-weight: 400; font-size: 8.5px; opacity: .75; }

/* ── Main table ── */
.main-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
.main-table th {
    background: #334155; color: #f1f5f9; font-size: 8.5px;
    padding: 5px 7px; border: 1px solid #475569; text-align: left; white-space: nowrap;
}
.main-table td { padding: 5px 7px; border: 1px solid #e2e8f0; font-size: 9.5px; vertical-align: top; }
.main-table tr:nth-child(even) td { background: #f8fafc; }
.main-table tr.has-activity td { background: #fafff4 !important; }
.main-table tr.group-header td {
    background: #f1f5f9 !important; font-weight: 700; font-size: 9px;
    color: #475569; padding: 4px 8px; border-top: 2px solid #94a3b8;
}

/* tipe pill */
.tipe-pill {
    display: inline-block; padding: 1px 7px; border-radius: 10px;
    font-size: 8px; font-weight: 700; white-space: nowrap;
}
.tipe-print   { background: #dbeafe; color: #1e40af; }
.tipe-digital { background: #e0f2fe; color: #075985; }

/* source pill */
.src-pill {
    display: inline-block; padding: 1px 6px; border-radius: 3px;
    font-size: 7.5px; font-weight: 700; border: 1px solid;
}
.src-standalone { background: #f1f5f9; color: #64748b; border-color: #cbd5e1; }
.src-event      { background: #ede9fe; color: #5b21b6; border-color: #c4b5fd; }

/* number cells */
.num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
.zero { color: #cbd5e1; }

/* ── Signature ── */
.sign-row { display: flex; gap: 20px; margin-top: 24px; }
.sign-box {
    flex: 1; border: 1px solid #e2e8f0; border-radius: 6px;
    padding: 9px 12px 48px; text-align: center; font-size: 9.5px; color: #475569;
}
.sign-box .sign-role { font-weight: 700; color: #1e293b; font-size: 10px; margin-top: 3px; }

/* ── Footer ── */
.doc-footer {
    margin-top: 16px; border-top: 1px solid #e2e8f0; padding-top: 6px;
    display: flex; justify-content: space-between; font-size: 8.5px; color: #94a3b8;
}

/* ── Print button ── */
.btn-print {
    position: fixed; top: 16px; right: 16px; background: #1e293b; color: #fff;
    border: none; padding: 8px 18px; border-radius: 6px; cursor: pointer;
    font-size: 12px; box-shadow: 0 2px 8px rgba(0,0,0,.2);
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

$statusLabels = ['draft'=>'Draft','review'=>'Review','approved'=>'Approved','revision'=>'Revision'];
$tipeLabels   = ['print'=>'Print','digital'=>'Digital'];
$tipeOrder    = ['print','digital'];

$totalItems = count($rows);

function rp(int $n): string {
    return $n > 0 ? 'Rp '.number_format($n, 0, ',', '.') : '—';
}
function num(int $n): string {
    return $n > 0 ? number_format($n) : '—';
}

// Group by tipe
$rowsByTipe = [];
foreach ($rows as $r) {
    $rowsByTipe[$r['item']['tipe']][] = $r;
}
?>

<!-- ══ HEADER ══ -->
<div class="doc-header">
    <div>
        <div class="title">Laporan Bulanan — Creative &amp; Design</div>
        <div class="sub"><?= $bulanLabel ?></div>
        <div class="org">PT. Wulandari Bangun Laksana Tbk. &mdash; IT Department &mdash; Mall Intelligence Center</div>
    </div>
    <div class="meta">
        Dicetak oleh: <?= esc($printedBy) ?><br>
        Tanggal cetak: <?= $printedAt ?><br>
        Total item: <?= $totalItems ?> &middot; Aktif bulan ini: <?= $activeCount ?>
    </div>
</div>

<!-- ══ KPI ══ -->
<div class="kpi-row">
    <div class="kpi-box kpi-total">
        <div class="kpi-label">Total Item</div>
        <div class="kpi-num"><?= $totalItems ?></div>
        <div class="kpi-sub"><?= $activeCount ?> aktif bulan ini</div>
    </div>
    <div class="kpi-box kpi-budget">
        <div class="kpi-label">Total Budget</div>
        <div class="kpi-num" style="font-size:13px"><?= rp($totalBudget) ?></div>
        <div class="kpi-sub">semua item</div>
    </div>
    <div class="kpi-box kpi-real">
        <div class="kpi-label">Realisasi Bulan Ini</div>
        <div class="kpi-num" style="font-size:13px"><?= rp($totalRealisasi) ?></div>
        <div class="kpi-sub"><?= $bulanLabel ?></div>
    </div>
    <div class="kpi-box kpi-reach">
        <div class="kpi-label">Total Reach</div>
        <div class="kpi-num"><?= num($totalReach) ?></div>
        <div class="kpi-sub">digital insight</div>
    </div>
    <div class="kpi-box kpi-impr">
        <div class="kpi-label">Impressions</div>
        <div class="kpi-num"><?= num($totalImpressions) ?></div>
        <div class="kpi-sub">
            <?php if ($totalFollowers > 0): ?>+<?= number_format($totalFollowers) ?> followers<?php else: ?>digital insight<?php endif; ?>
        </div>
    </div>
</div>

<!-- ══ STATUS STRIP ══ -->
<div class="status-strip">
    <span class="lbl">Status Item:</span>
    <?php foreach ($statusLabels as $key => $lbl):
        $cnt = $statusCounts[$key] ?? 0; if (!$cnt) continue; ?>
    <span class="sbadge sbadge-<?= $key ?>"><?= $lbl ?> <?= $cnt ?></span>
    <?php endforeach; ?>
</div>

<!-- ══ DETAIL TABLE ══ -->
<?php foreach ($tipeOrder as $tipe):
    if (empty($rowsByTipe[$tipe])) continue;
    $tipeRows  = $rowsByTipe[$tipe];
    $isDigital = ($tipe === 'digital');
    $grpBudget = array_sum(array_map(fn($r) => (int)$r['item']['budget'], $tipeRows));
    $grpReal   = array_sum(array_map(fn($r) => (int)($r['realMonth']['total'] ?? 0), $tipeRows));
    $grpReach  = array_sum(array_map(fn($r) => (int)($r['insMonth']['max_reach'] ?? 0), $tipeRows));
    $grpImpr   = array_sum(array_map(fn($r) => (int)($r['insMonth']['max_impressions'] ?? 0), $tipeRows));
?>
<div class="sec-title">
    <span><span class="tipe-pill tipe-<?= $tipe ?>" style="font-size:9px"><?= $tipeLabels[$tipe] ?></span>&ensp;<?= count($tipeRows) ?> item</span>
    <span class="sec-sub">Budget <?= rp($grpBudget) ?> &middot; Realisasi <?= rp($grpReal) ?><?= $isDigital && $grpReach ? ' &middot; Reach '.number_format($grpReach) : '' ?></span>
</div>
<table class="main-table">
<thead>
    <tr>
        <th style="width:28%">Nama Item</th>
        <th style="width:8%">Asal</th>
        <th style="width:10%">Budget</th>
        <th style="width:10%">Realisasi Bln Ini</th>
        <?php if ($isDigital): ?>
        <th style="width:9%">Reach</th>
        <th style="width:9%">Impressions</th>
        <th style="width:8%">Followers</th>
        <?php endif; ?>
        <th style="width:8%">Status</th>
        <th>Keterangan / Event</th>
    </tr>
</thead>
<tbody>
<?php foreach ($tipeRows as $r):
    $item    = $r['item'];
    $realMon = (int)($r['realMonth']['total']                ?? 0);
    $reach   = (int)($r['insMonth']['max_reach']             ?? 0);
    $impr    = (int)($r['insMonth']['max_impressions']       ?? 0);
    $flw     = (int)($r['insMonth']['total_followers_gained'] ?? 0);
    $budget  = (int)$item['budget'];
    $isSt    = $item['_source'] === 's';
?>
<tr class="<?= $r['hasActivity'] ? 'has-activity' : '' ?>">
    <td><strong><?= esc($item['nama']) ?></strong></td>
    <td>
        <span class="src-pill <?= $isSt ? 'src-standalone' : 'src-event' ?>">
            <?= $isSt ? 'Standalone' : 'Event' ?>
        </span>
    </td>
    <td class="num <?= !$budget ? 'zero' : '' ?>"><?= rp($budget) ?></td>
    <td class="num <?= !$realMon ? 'zero' : '' ?>"><?= rp($realMon) ?></td>
    <?php if ($isDigital): ?>
    <td class="num <?= !$reach ? 'zero' : '' ?>"><?= num($reach) ?></td>
    <td class="num <?= !$impr  ? 'zero' : '' ?>"><?= num($impr) ?></td>
    <td class="num <?= !$flw   ? 'zero' : '' ?>"><?= $flw > 0 ? '+'.number_format($flw) : '—' ?></td>
    <?php endif; ?>
    <td><span class="sbadge sbadge-<?= $item['status'] ?? 'draft' ?>"><?= $statusLabels[$item['status']] ?? ucfirst($item['status'] ?? '') ?></span></td>
    <td style="color:#64748b;font-size:9px"><?= esc($item['event_name'] ?? '') ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endforeach; ?>

<!-- ══ TANDA TANGAN ══ -->
<div class="sign-row">
    <div class="sign-box">
        Dibuat oleh<br>
        <span class="sign-role">Creative &amp; Design</span>
    </div>
    <div class="sign-box">
        Mengetahui<br>
        <span class="sign-role">Kepala Departemen</span>
    </div>
    <div class="sign-box">
        Menyetujui<br>
        <span class="sign-role">General Manager</span>
    </div>
</div>

<!-- ══ FOOTER ══ -->
<div class="doc-footer">
    <span>Mall Intelligence Center v1.9 &mdash; IT Department PT. Wulandari Bangun Laksana Tbk.</span>
    <span>Digenerate otomatis &mdash; <?= $printedAt ?></span>
</div>

</body>
</html>
