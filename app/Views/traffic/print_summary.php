<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Traffic Summary — <?= date('d M Y', strtotime($from)) ?> s/d <?= date('d M Y', strtotime($to)) ?></title>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

@page { size: A4 landscape; margin: 10mm 14mm; }

body {
    font-family: 'Segoe UI', Calibri, Arial, sans-serif;
    font-size: 8pt;
    color: #1e293b;
    background: #fff;
    line-height: 1.35;
}

/* ── Print bar (screen only) ─────────────────────────────────────── */
.print-bar {
    position: fixed; top: 0; left: 0; right: 0; z-index: 999;
    background: #1e3a8a; color: #fff;
    padding: 7px 18px;
    display: flex; align-items: center; justify-content: space-between;
    font-size: 8.5pt;
    box-shadow: 0 2px 8px rgba(0,0,0,.3);
}
.print-bar button {
    background: #fff; color: #1e3a8a; border: none;
    padding: 5px 14px; border-radius: 5px;
    font-weight: 700; font-size: 8.5pt; cursor: pointer;
}
.print-bar button:hover { background: #dbeafe; }
.print-bar a { color: #bfdbfe; text-decoration: none; font-size: 8pt; }
.print-bar a:hover { color: #fff; }
.page-top-spacer { height: 40px; }
@media print {
    .print-bar, .page-top-spacer { display: none !important; }
}

/* ── Page break ──────────────────────────────────────────────────── */
.page-break { break-before: page; page-break-before: always; }
@media print {
    tr { break-inside: avoid; }
}

/* ── Header ──────────────────────────────────────────────────────── */
.rpt-header {
    background: linear-gradient(135deg, #1e3a8a 0%, #1d4ed8 55%, #0369a1 100%);
    color: #fff; border-radius: 5px;
    padding: 8px 14px;
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 7px;
}
.rpt-header h1 { font-size: 12pt; font-weight: 800; }
.rpt-header .sub { font-size: 7.5pt; color: #bfdbfe; margin-top: 1px; }
.rpt-header .right { text-align: right; font-size: 7pt; color: #93c5fd; line-height: 1.6; }
.rpt-header .right strong { color: #fff; font-size: 9pt; display: block; }

/* ── KPI strip ───────────────────────────────────────────────────── */
.kpi-strip {
    display: grid; grid-template-columns: repeat(6, 1fr);
    gap: 5px; margin-bottom: 6px;
}
.kpi-card {
    border: 1px solid #e2e8f0; border-radius: 5px;
    padding: 6px 8px; text-align: center;
}
.kpi-card .lbl { font-size: 6.5pt; color: #64748b; text-transform: uppercase; letter-spacing: .04em; margin-bottom: 2px; }
.kpi-card .val { font-size: 13pt; font-weight: 800; line-height: 1.1; }
.kpi-card .sub { font-size: 6.5pt; color: #94a3b8; margin-top: 2px; }
.kpi-card.ew  { border-top: 3px solid #2563eb; }
.kpi-card.pt  { border-top: 3px solid #059669; }
.kpi-card.hl  { border-top: 3px solid #d97706; }

/* ── Insights ────────────────────────────────────────────────────── */
.insight-strip {
    display: flex; gap: 5px; margin-bottom: 7px; flex-wrap: nowrap;
}
.insight-item {
    flex: 1; min-width: 0;
    display: flex; align-items: center; gap: 6px;
    border: 1px solid #e2e8f0; border-radius: 5px;
    padding: 5px 7px; background: #f8fafc;
}
.insight-icon {
    width: 26px; height: 26px; border-radius: 5px;
    display: flex; align-items: center; justify-content: center;
    font-size: 12px; flex-shrink: 0;
}
.insight-item .ilbl { font-size: 6.5pt; color: #64748b; }
.insight-item .ival { font-size: 9pt; font-weight: 700; color: #1e293b; white-space: nowrap; }
.insight-item .isub { font-size: 6.5pt; color: #94a3b8; }

/* ── Charts ──────────────────────────────────────────────────────── */
.charts-2col { display: grid; grid-template-columns: 3fr 2fr; gap: 8px; margin-bottom: 0; }
.charts-2col-eq { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.chart-box { border: 1px solid #e2e8f0; border-radius: 5px; padding: 7px 10px; }
.chart-box .sec-label { font-size: 7pt; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #1e40af; border-left: 3px solid #1e40af; padding-left: 5px; margin-bottom: 5px; }
.chart-box .sec-label.grn { color: #065f46; border-color: #059669; }
.chart-wrap { position: relative; }

/* ── Tables ──────────────────────────────────────────────────────── */
.sec-label {
    font-size: 7pt; font-weight: 700; text-transform: uppercase;
    letter-spacing: .06em; color: #1e40af;
    border-left: 3px solid #1e40af; padding-left: 5px;
    margin-bottom: 5px;
}
.sec-label.grn { color: #065f46; border-color: #059669; }

table { width: 100%; border-collapse: collapse; font-size: 7.5pt; }
th {
    background: #dbeafe; color: #1e40af;
    padding: 3.5px 7px; border: 1px solid #bfdbfe;
    font-weight: 700; font-size: 7pt; text-align: left;
}
th.r, td.r { text-align: right; }
td { padding: 3px 7px; border: 1px solid #e2e8f0; color: #334155; }
tr:nth-child(even) td { background: #f8fafc; }
tr.peak td { background: #fef3c7 !important; font-weight: 600; }
tr.tot td  { background: #dbeafe !important; font-weight: 700; color: #1e40af; }
tr.tot.grn td { background: #d1fae5 !important; color: #065f46; }
tr.nodata td { color: #cbd5e1; }

/* ── 3-col layout ────────────────────────────────────────────────── */
.cols-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; align-items: start; }
.cols-2 { display: grid; grid-template-columns: 3fr 2fr; gap: 8px; align-items: start; }

/* ── Section block ───────────────────────────────────────────────── */
.section { margin-bottom: 6px; }

/* ── Weekday/Weekend ─────────────────────────────────────────────── */
.wdwe-strip {
    display: grid; grid-template-columns: 1fr 1fr;
    gap: 6px; margin-bottom: 7px;
}
.wdwe-card {
    border: 1px solid #e2e8f0; border-radius: 5px; padding: 6px 10px;
}
.wdwe-card.wd { border-top: 3px solid #6366f1; }
.wdwe-card.we { border-top: 3px solid #f59e0b; }
.wdwe-card .wdlbl { font-size: 6.5pt; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 3px; }
.wdwe-card.wd .wdlbl { color: #6366f1; }
.wdwe-card.we .wdlbl { color: #d97706; }
.wdwe-card .wdrow { display: flex; gap: 14px; align-items: baseline; flex-wrap: wrap; }
.wdwe-card .wdnum { font-size: 11pt; font-weight: 800; color: #1e293b; }
.wdwe-card .wdsub { font-size: 6.5pt; color: #64748b; }
.wdwe-card .wdmall { font-size: 6.5pt; color: #94a3b8; margin-top: 2px; }

/* ── Event badges ────────────────────────────────────────────────── */
.event-strip {
    border: 1px solid #e2e8f0; border-radius: 5px;
    padding: 5px 8px; margin-bottom: 6px;
}
.event-strip .ev-lbl { font-size: 6.5pt; font-weight: 700; color: #1e40af; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 4px; }
.event-strip .ev-list { display: flex; flex-wrap: wrap; gap: 4px; }
.ev-badge {
    border: 1px solid #bfdbfe; background: #eff6ff; border-radius: 4px;
    padding: 2px 6px; font-size: 6.5pt; color: #1e40af;
}
.ev-badge .ev-name { font-weight: 600; }
.ev-badge .ev-per  { color: #64748b; margin-left: 4px; }

/* ── Footer ──────────────────────────────────────────────────────── */
.rpt-footer {
    margin-top: 8px;
    font-size: 6.5pt; color: #94a3b8;
    display: flex; justify-content: space-between;
    border-top: 1px solid #e2e8f0; padding-top: 4px;
}
</style>
</head>
<body>
<?php
$n    = fn(int $v) => number_format($v, 0, ',', '.');
$fmt  = fn(int $v) => $v > 0 ? number_format($v, 0, ',', '.') : '—';

$totalVehicle   = $vehicles['mobil'] + $vehicles['motor'] + $vehicles['mobil_box'] + $vehicles['bus'] + $vehicles['truck'] + $vehicles['taxi'];
$hasExtraVehicle = ($vehicles['mobil_box'] + $vehicles['bus'] + $vehicles['truck'] + $vehicles['taxi']) > 0;
$maxHourTotal = max(array_column($hours, 'total') ?: [0]);
$maxDayTotal  = max(array_column($days,  'total') ?: [0]);

$fromFmt = date('d M Y', strtotime($from));
$toFmt   = date('d M Y', strtotime($to));
$prevFmt = date('d M', strtotime($prevFrom)) . '–' . date('d M Y', strtotime($prevTo));

// Chart data arrays
$chartDates = array_column($days, 'date_fmt');
$chartEwalk = array_column($days, 'ewalk');
$chartPenta = array_column($days, 'pentacity');
$chartMobil    = array_column($days, 'mobil');
$chartMotor    = array_column($days, 'motor');
$chartMobilBox = array_column($days, 'mobil_box');
$chartBus      = array_column($days, 'bus');
$chartTruck    = array_column($days, 'truck');
$chartTaxi     = array_column($days, 'taxi');
$vPrintDatasets = array_filter([
    ['Mobil',     $chartMobil,    'rgba(245,158,11,0.8)'],
    ['Motor',     $chartMotor,    'rgba(239,68,68,0.75)'],
    ['Mobil Box', $chartMobilBox, 'rgba(99,102,241,0.8)'],
    ['Bus',       $chartBus,      'rgba(16,185,129,0.8)'],
    ['Truck',     $chartTruck,    'rgba(139,92,246,0.8)'],
    ['Taxi',      $chartTaxi,     'rgba(236,72,153,0.8)'],
], fn($d) => array_sum($d[1]) > 0);
$chartHours = array_column($hours, 'jam');
$chartHrEw  = array_column($hours, 'ewalk');
$chartHrPt  = array_column($hours, 'pentacity');

$doorEwalkLabels = array_column($doorEwalk, 'pintu');
$doorEwalkVals   = array_map('intval', array_column($doorEwalk, 'total'));
$doorPentaLabels = array_column($doorPenta, 'pintu');
$doorPentaVals   = array_map('intval', array_column($doorPenta, 'total'));
?>

<!-- Print bar -->
<div class="print-bar">
    <a href="<?= base_url('traffic/summary') ?>?from=<?= $from ?>&to=<?= $to ?>">← Kembali ke Summary</a>
    <span style="font-weight:600">Traffic Summary — <?= $fromFmt ?> s/d <?= $toFmt ?></span>
    <button onclick="window.print()">🖨️ Print / Save PDF</button>
</div>
<div class="page-top-spacer"></div>

<!-- ════════════════════ HALAMAN 1 — OVERVIEW & CHARTS ═══════════════════ -->

<div class="rpt-header">
    <div>
        <h1>Traffic Summary — eWalk &amp; Pentacity</h1>
        <div class="sub">PT. Wulandari Bangun Laksana Tbk. &nbsp;·&nbsp; Mall Intelligence Center v1.3</div>
    </div>
    <div class="right">
        <strong><?= $fromFmt ?> — <?= $toFmt ?></strong>
        Generate: <?= date('d M Y H:i') ?> &nbsp;·&nbsp; <?= count($days) ?> hari data
    </div>
</div>

<!-- KPI -->
<div class="kpi-strip">
    <div class="kpi-card">
        <div class="lbl">Total Pengunjung</div>
        <div class="val"><?= $n($totalVisitor) ?></div>
        <div class="sub">eWalk + Pentacity</div>
    </div>
    <div class="kpi-card ew">
        <div class="lbl">eWalk</div>
        <div class="val" style="color:#2563eb"><?= $n($totalEwalk) ?></div>
        <?php if ($totalVisitor > 0): ?>
        <div class="sub"><?= round($totalEwalk / $totalVisitor * 100) ?>% dari total</div>
        <?php endif; ?>
    </div>
    <div class="kpi-card pt">
        <div class="lbl">Pentacity</div>
        <div class="val" style="color:#059669"><?= $n($totalPenta) ?></div>
        <?php if ($totalVisitor > 0): ?>
        <div class="sub"><?= round($totalPenta / $totalVisitor * 100) ?>% dari total</div>
        <?php endif; ?>
    </div>
    <div class="kpi-card hl">
        <div class="lbl">Kendaraan</div>
        <div class="val" style="color:#d97706"><?= $n($totalVehicle) ?></div>
        <div class="sub"><?= $n($vehicles['mobil']) ?> mobil · <?= $n($vehicles['motor']) ?> motor</div>
        <?php if ($hasExtraVehicle): ?>
        <div class="sub"><?php
            $parts = [];
            if ($vehicles['mobil_box'] > 0) $parts[] = $n($vehicles['mobil_box']) . ' box';
            if ($vehicles['bus']       > 0) $parts[] = $n($vehicles['bus'])       . ' bus';
            if ($vehicles['truck']     > 0) $parts[] = $n($vehicles['truck'])     . ' truck';
            if ($vehicles['taxi']      > 0) $parts[] = $n($vehicles['taxi'])      . ' taxi';
            echo implode(' · ', $parts);
        ?></div>
        <?php endif; ?>
    </div>
    <div class="kpi-card">
        <div class="lbl">Rata-rata / Hari</div>
        <div class="val"><?= $n($avgDaily) ?></div>
        <div class="sub">pengunjung aktif</div>
    </div>
    <?php
    $vsColor = $changePct === null ? '#64748b' : ($changePct >= 0 ? '#16a34a' : '#dc2626');
    $vsBorder = $changePct === null ? '#cbd5e1' : ($changePct >= 0 ? '#16a34a' : '#dc2626');
    ?>
    <div class="kpi-card" style="border-top:3px solid <?= $vsBorder ?>">
        <div class="lbl">vs Periode Sebelumnya</div>
        <div class="val" style="color:<?= $vsColor ?>;font-size:12pt">
            <?= $changePct === null ? '—' : ($changePct >= 0 ? '+' : '') . $changePct . '%' ?>
        </div>
        <div class="sub"><?= $prevTotal > 0 ? $n($prevTotal) . ' org' : 'belum ada data' ?></div>
    </div>
</div>

<!-- Insights -->
<?php
$insights = array_filter([
    $peakHour ? ['icon' => '🕐', 'bg' => '#fef3c7', 'lbl' => 'Jam Tersibuk',             'val' => $peakHour,               'sub' => $n($peakHourVal) . ' pengunjung'] : null,
    $bestDay  ? ['icon' => '🏆', 'bg' => '#dcfce7', 'lbl' => 'Hari Terbaik',             'val' => $bestDay,                'sub' => $n($bestDayVal) . ' pengunjung']  : null,
    ! empty($doorEwalk) ? ['icon' => '🚪', 'bg' => '#dbeafe', 'lbl' => 'Pintu Terpadat eWalk',     'val' => $doorEwalk[0]['pintu'], 'sub' => $n((int)$doorEwalk[0]['total']) . ' org'] : null,
    ! empty($doorPenta) ? ['icon' => '🚪', 'bg' => '#d1fae5', 'lbl' => 'Pintu Terpadat Pentacity', 'val' => $doorPenta[0]['pintu'], 'sub' => $n((int)$doorPenta[0]['total']) . ' org'] : null,
    $changePct !== null && $prevTotal > 0 ? [
        'icon' => $changePct >= 0 ? '📈' : '📉',
        'bg'   => $changePct >= 0 ? '#dcfce7' : '#fee2e2',
        'lbl'  => 'vs ' . $prevFmt,
        'val'  => ($changePct >= 0 ? '+' : '') . $changePct . '%',
        'sub'  => 'sebelumnya ' . $n($prevTotal) . ' org',
    ] : null,
]);
if (! empty($insights)):
?>
<div class="insight-strip">
<?php foreach ($insights as $ins): ?>
    <div class="insight-item">
        <div class="insight-icon" style="background:<?= $ins['bg'] ?>"><?= $ins['icon'] ?></div>
        <div>
            <div class="ilbl"><?= $ins['lbl'] ?></div>
            <div class="ival"><?= $ins['val'] ?></div>
            <div class="isub"><?= $ins['sub'] ?></div>
        </div>
    </div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Charts row: Traffic Harian (lebar) + per Jam (lebih sempit) -->
<div class="charts-2col">
    <div class="chart-box">
        <div class="sec-label">Traffic Pengunjung Harian</div>
        <div class="chart-wrap" style="height:<?= count($days) > 30 ? '54mm' : '58mm' ?>">
            <canvas id="dailyChart"></canvas>
        </div>
    </div>
    <div class="chart-box">
        <div class="sec-label">Distribusi per Jam</div>
        <div class="chart-wrap" style="height:<?= count($days) > 30 ? '54mm' : '58mm' ?>">
            <canvas id="hourChart"></canvas>
        </div>
    </div>
</div>

<!-- Footer halaman 1 -->
<div class="rpt-footer">
    <span>Mall Intelligence Center v1.3 · PT. Wulandari Bangun Laksana Tbk.</span>
    <span>Halaman 1 dari 3</span>
    <span>KONFIDENSIAL — Hanya untuk internal perusahaan</span>
</div>


<!-- ════════════════════ HALAMAN 2 — DATA HARIAN ═══════════════════════ -->
<div class="page-break"></div>

<div class="rpt-header">
    <div>
        <h1>Traffic Harian — Detail per Tanggal</h1>
        <div class="sub">PT. Wulandari Bangun Laksana Tbk. &nbsp;·&nbsp; <?= $fromFmt ?> — <?= $toFmt ?></div>
    </div>
    <div class="right"><strong>Halaman 2 dari 3</strong>Mall Intelligence Center v1.3</div>
</div>

<?php if ($wdTotal + $weTotal > 0): ?>
<div class="wdwe-strip">
    <div class="wdwe-card wd">
        <div class="wdlbl">Weekdays — Senin s/d Kamis</div>
        <div class="wdrow">
            <div><div class="wdnum"><?= $n($wdTotal) ?></div><div class="wdsub">total pengunjung</div></div>
            <div><div class="wdnum" style="font-size:9pt"><?= $n($wdAvg) ?></div><div class="wdsub">rata-rata/hari (<?= $wdDays ?> hari aktif)</div></div>
        </div>
        <div class="wdmall">eWalk: <?= $n($wdEwalk) ?> &nbsp;·&nbsp; Pentacity: <?= $n($wdPenta) ?></div>
    </div>
    <div class="wdwe-card we">
        <div class="wdlbl">Weekend — Jumat s/d Minggu</div>
        <div class="wdrow">
            <div><div class="wdnum"><?= $n($weTotal) ?></div><div class="wdsub">total pengunjung</div></div>
            <div><div class="wdnum" style="font-size:9pt"><?= $n($weAvg) ?></div><div class="wdsub">rata-rata/hari (<?= $weDays ?> hari aktif)</div></div>
        </div>
        <div class="wdmall">eWalk: <?= $n($weEwalk) ?> &nbsp;·&nbsp; Pentacity: <?= $n($wePenta) ?></div>
    </div>
</div>
<?php endif; ?>

<div class="section">
    <div class="sec-label">Traffic Harian</div>
    <table>
        <thead>
            <tr>
                <th style="width:68px">Tanggal</th>
                <th class="r" style="width:72px">eWalk</th>
                <th class="r" style="width:72px">Pentacity</th>
                <th class="r" style="width:72px">Total</th>
                <th class="r" style="width:55px">Mobil</th>
                <th class="r" style="width:55px">Motor</th>
                <?php if ($hasExtraVehicle): ?>
                <th class="r" style="width:50px">Box</th>
                <th class="r" style="width:50px">Bus</th>
                <th class="r" style="width:50px">Truck</th>
                <th class="r" style="width:50px">Taxi</th>
                <?php endif; ?>
                <th class="r" style="width:65px">Kendaraan</th>
                <th class="r" style="width:52px">% Total</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($days as $row):
            $rowVehicle = $row['mobil'] + $row['motor'] + $row['mobil_box'] + $row['bus'] + $row['truck'] + $row['taxi'];
            $noData = $row['total'] === 0 && $row['mobil'] === 0;
            $isBest = $row['total'] === $maxDayTotal && $maxDayTotal > 0;
        ?>
            <tr class="<?= $noData ? 'nodata' : ($isBest ? 'peak' : '') ?>">
                <td><?= $row['date_fmt'] ?><?= $isBest ? ' ★' : '' ?></td>
                <td class="r"><?= $fmt($row['ewalk'])     ?></td>
                <td class="r"><?= $fmt($row['pentacity']) ?></td>
                <td class="r" style="font-weight:<?= $row['total'] > 0 ? '700' : 'normal' ?>"><?= $fmt($row['total']) ?></td>
                <td class="r"><?= $fmt($row['mobil'])  ?></td>
                <td class="r"><?= $fmt($row['motor'])  ?></td>
                <?php if ($hasExtraVehicle): ?>
                <td class="r"><?= $fmt($row['mobil_box']) ?></td>
                <td class="r"><?= $fmt($row['bus'])       ?></td>
                <td class="r"><?= $fmt($row['truck'])     ?></td>
                <td class="r"><?= $fmt($row['taxi'])      ?></td>
                <?php endif; ?>
                <td class="r"><?= $fmt($rowVehicle) ?></td>
                <td class="r" style="color:#64748b"><?= $totalVisitor > 0 && $row['total'] > 0 ? round($row['total'] / $totalVisitor * 100, 1) . '%' : '—' ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="tot">
                <td>TOTAL</td>
                <td class="r"><?= $n($totalEwalk) ?></td>
                <td class="r"><?= $n($totalPenta) ?></td>
                <td class="r"><?= $n($totalVisitor) ?></td>
                <td class="r"><?= $n($vehicles['mobil']) ?></td>
                <td class="r"><?= $n($vehicles['motor']) ?></td>
                <?php if ($hasExtraVehicle): ?>
                <td class="r"><?= $n($vehicles['mobil_box']) ?></td>
                <td class="r"><?= $n($vehicles['bus'])       ?></td>
                <td class="r"><?= $n($vehicles['truck'])     ?></td>
                <td class="r"><?= $n($vehicles['taxi'])      ?></td>
                <?php endif; ?>
                <td class="r"><?= $n($totalVehicle) ?></td>
                <td class="r">100%</td>
            </tr>
        </tfoot>
    </table>
</div>

<div class="rpt-footer">
    <span>Mall Intelligence Center v1.3 · PT. Wulandari Bangun Laksana Tbk.</span>
    <span>Halaman 2 dari 3</span>
    <span>★ = hari dengan traffic tertinggi</span>
</div>


<!-- ════════════════════ HALAMAN 3 — JAM, KENDARAAN & PINTU ═══════════ -->
<div class="page-break"></div>

<div class="rpt-header">
    <div>
        <h1>Traffic — Kendaraan, per Jam &amp; per Pintu</h1>
        <div class="sub">PT. Wulandari Bangun Laksana Tbk. &nbsp;·&nbsp; <?= $fromFmt ?> — <?= $toFmt ?></div>
    </div>
    <div class="right"><strong>Halaman 3 dari 3</strong>Mall Intelligence Center v1.3</div>
</div>

<!-- Kendaraan chart (full width) -->
<div class="chart-box section">
    <div class="sec-label" style="color:#d97706;border-color:#d97706">Kendaraan Harian — Semua Tipe</div>
    <div class="chart-wrap" style="height:52mm">
        <canvas id="vehicleChart"></canvas>
    </div>
</div>

<!-- Hourly table + Per Pintu (3 kolom) -->
<div class="cols-3">

    <!-- Traffic per Jam (tabel) -->
    <div class="section">
        <div class="sec-label">Traffic per Jam</div>
        <table>
            <thead>
                <tr>
                    <th>Jam</th>
                    <th class="r">eWalk</th>
                    <th class="r">Pentacity</th>
                    <th class="r">Total</th>
                    <th class="r">%</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($hours as $row):
                $isPeak = $row['total'] === $maxHourTotal && $maxHourTotal > 0;
            ?>
                <tr class="<?= $isPeak ? 'peak' : '' ?>">
                    <td><?= $row['jam'] ?><?= $isPeak ? ' ★' : '' ?></td>
                    <td class="r"><?= $n($row['ewalk'])     ?></td>
                    <td class="r"><?= $n($row['pentacity']) ?></td>
                    <td class="r" style="font-weight:<?= $isPeak ? '700' : 'normal' ?>"><?= $n($row['total']) ?></td>
                    <td class="r" style="color:#64748b"><?= $totalVisitor > 0 && $row['total'] > 0 ? round($row['total'] / $totalVisitor * 100, 1) . '%' : '—' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="tot">
                    <td>TOTAL</td>
                    <td class="r"><?= $n($totalEwalk) ?></td>
                    <td class="r"><?= $n($totalPenta) ?></td>
                    <td class="r"><?= $n($totalVisitor) ?></td>
                    <td class="r">—</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Per Pintu eWalk -->
    <div class="section">
        <div class="sec-label">Per Pintu — eWalk</div>
        <?php if (empty($doorEwalk)): ?>
        <p style="color:#94a3b8;padding:8px 0">Belum ada data.</p>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Pintu</th>
                    <th class="r">Total</th>
                    <th class="r">%</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($doorEwalk as $d): ?>
                <tr>
                    <td><?= esc($d['pintu']) ?></td>
                    <td class="r"><?= $n((int)$d['total']) ?></td>
                    <td class="r" style="color:#64748b"><?= $totalEwalk > 0 ? round($d['total'] / $totalEwalk * 100) . '%' : '—' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="tot"><td>TOTAL</td><td class="r"><?= $n($totalEwalk) ?></td><td class="r">100%</td></tr>
            </tfoot>
        </table>
        <?php endif; ?>
    </div>

    <!-- Per Pintu Pentacity -->
    <div class="section">
        <div class="sec-label grn">Per Pintu — Pentacity</div>
        <?php if (empty($doorPenta)): ?>
        <p style="color:#94a3b8;padding:8px 0">Belum ada data.</p>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th style="background:#d1fae5;color:#065f46">Pintu</th>
                    <th class="r" style="background:#d1fae5;color:#065f46">Total</th>
                    <th class="r" style="background:#d1fae5;color:#065f46">%</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($doorPenta as $d): ?>
                <tr>
                    <td><?= esc($d['pintu']) ?></td>
                    <td class="r"><?= $n((int)$d['total']) ?></td>
                    <td class="r" style="color:#64748b"><?= $totalPenta > 0 ? round($d['total'] / $totalPenta * 100) . '%' : '—' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="tot grn"><td>TOTAL</td><td class="r"><?= $n($totalPenta) ?></td><td class="r">100%</td></tr>
            </tfoot>
        </table>
        <?php endif; ?>
    </div>

</div>

<?php if (! empty($periodEvents)): ?>
<div class="event-strip">
    <div class="ev-lbl"><span style="margin-right:4px">📅</span> Event dalam Periode Ini</div>
    <div class="ev-list">
    <?php foreach ($periodEvents as $ev):
        $evEnd = date('d M Y', strtotime($ev['start_date'] . ' +' . ($ev['event_days'] - 1) . ' days'));
    ?>
        <div class="ev-badge">
            <span class="ev-name"><?= esc($ev['name']) ?></span>
            <span class="ev-per"><?= date('d M Y', strtotime($ev['start_date'])) ?> – <?= $evEnd ?></span>
        </div>
    <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<div class="rpt-footer">
    <span>Mall Intelligence Center v1.3 · PT. Wulandari Bangun Laksana Tbk.</span>
    <span>Halaman 3 dari 3</span>
    <span>KONFIDENSIAL — Hanya untuk internal perusahaan</span>
</div>


<!-- ════════════════════ CHARTS JS ═══════════════════════════════════ -->
<script>
Chart.defaults.animation = false;
Chart.defaults.font.family = "'Segoe UI', Calibri, Arial, sans-serif";

const tickFmt = v => v > 0 ? v.toLocaleString('id-ID') : '';
const smallTick = { font: { size: 7 } };
const smallLegend = { position: 'top', labels: { boxWidth: 10, padding: 8, font: { size: 7.5 } } };

// ── Daily Chart ───────────────────────────────────────────────────────
new Chart(document.getElementById('dailyChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($chartDates) ?>,
        datasets: [
            {
                label: 'eWalk',
                data:  <?= json_encode($chartEwalk) ?>,
                backgroundColor: 'rgba(37,99,235,0.78)',
                borderRadius: 2, borderSkipped: false
            },
            {
                label: 'Pentacity',
                data:  <?= json_encode($chartPenta) ?>,
                backgroundColor: 'rgba(5,150,105,0.78)',
                borderRadius: 2, borderSkipped: false
            }
        ]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: smallLegend },
        scales: {
            x: { ticks: { ...smallTick, maxRotation: 45, autoSkip: true, maxTicksLimit: 20 }, grid: { display: false } },
            y: { beginAtZero: true, ticks: { ...smallTick, callback: tickFmt } }
        }
    }
});

// ── Hourly Chart ──────────────────────────────────────────────────────
new Chart(document.getElementById('hourChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($chartHours) ?>,
        datasets: [
            {
                label: 'eWalk',
                data:  <?= json_encode($chartHrEw) ?>,
                borderColor: 'rgba(37,99,235,1)',
                backgroundColor: 'rgba(37,99,235,0.1)',
                tension: 0.4, fill: true, pointRadius: 3, borderWidth: 1.5
            },
            {
                label: 'Pentacity',
                data:  <?= json_encode($chartHrPt) ?>,
                borderColor: 'rgba(5,150,105,1)',
                backgroundColor: 'rgba(5,150,105,0.1)',
                tension: 0.4, fill: true, pointRadius: 3, borderWidth: 1.5
            }
        ]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: smallLegend },
        scales: {
            x: { ticks: { ...smallTick, maxRotation: 45 }, grid: { display: false } },
            y: { beginAtZero: true, ticks: { ...smallTick, callback: tickFmt } }
        }
    }
});

// ── Vehicle Chart ─────────────────────────────────────────────────────
new Chart(document.getElementById('vehicleChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($chartDates) ?>,
        datasets: [
            <?php foreach ($vPrintDatasets as [$vl, $vd, $vc]): ?>
            { label: '<?= $vl ?>', data: <?= json_encode($vd) ?>, backgroundColor: '<?= $vc ?>', borderRadius: 2, borderSkipped: false },
            <?php endforeach; ?>
        ]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: smallLegend },
        scales: {
            x: { ticks: { ...smallTick, maxRotation: 45, autoSkip: true, maxTicksLimit: 20 }, grid: { display: false } },
            y: { beginAtZero: true, ticks: { ...smallTick, callback: tickFmt } }
        }
    }
});

// ── Auto-print setelah chart render ──────────────────────────────────
window.addEventListener('load', function () {
    setTimeout(function () { window.print(); }, 800);
});
</script>
</body>
</html>
