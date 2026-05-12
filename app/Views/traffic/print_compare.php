<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Compare Traffic — <?= date('d M Y', strtotime($from1)) ?> vs <?= date('d M Y', strtotime($from2)) ?></title>
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

/* ── Colors ──────────────────────────────────────────────────────── */
:root {
    --c-p1: #6366f1; --c-p1-bg: #eef2ff; --c-p1-border: #c7d2fe;
    --c-p2: #f97316; --c-p2-bg: #fff7ed; --c-p2-border: #fed7aa;
    --c-p3: #10b981; --c-p3-bg: #ecfdf5; --c-p3-border: #a7f3d0;
}

/* ── Print bar ───────────────────────────────────────────────────── */
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
@media print { tr { break-inside: avoid; } }

/* ── Header ──────────────────────────────────────────────────────── */
.rpt-header {
    background: linear-gradient(135deg, #1e3a8a 0%, #1d4ed8 55%, #0369a1 100%);
    color: #fff; border-radius: 5px;
    padding: 8px 14px;
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 7px;
}
.rpt-header h1 { font-size: 11pt; font-weight: 800; }
.rpt-header .sub { font-size: 7pt; color: #bfdbfe; margin-top: 1px; }
.rpt-header .right { text-align: right; font-size: 6.5pt; color: #93c5fd; line-height: 1.6; }
.rpt-header .right strong { color: #fff; font-size: 8.5pt; display: block; }

/* ── Period strip ────────────────────────────────────────────────── */
.period-strip {
    display: flex; gap: 6px; margin-bottom: 7px;
}
.period-pill {
    border-radius: 5px; padding: 4px 10px; font-size: 7.5pt; font-weight: 700;
    flex: 1; text-align: center;
}
.period-pill.p1 { background: var(--c-p1-bg); color: var(--c-p1); border: 1px solid var(--c-p1-border); }
.period-pill.p2 { background: var(--c-p2-bg); color: var(--c-p2); border: 1px solid var(--c-p2-border); }
.period-pill.p3 { background: var(--c-p3-bg); color: var(--c-p3); border: 1px solid var(--c-p3-border); }
.period-pill .pill-label { font-size: 6pt; font-weight: 800; display: block; letter-spacing: .05em; text-transform: uppercase; }
.period-pill .pill-date  { font-size: 7.5pt; font-weight: 600; }

/* ── Tables ──────────────────────────────────────────────────────── */
.sec-label {
    font-size: 7pt; font-weight: 700; text-transform: uppercase;
    letter-spacing: .06em; color: #1e40af;
    border-left: 3px solid #1e40af; padding-left: 5px;
    margin-bottom: 5px;
}
.sec-label.grn { color: #065f46; border-color: #059669; }
.sec-label.org { color: #92400e; border-color: #d97706; }

table { width: 100%; border-collapse: collapse; font-size: 7.5pt; }
th {
    background: #dbeafe; color: #1e40af;
    padding: 3.5px 7px; border: 1px solid #bfdbfe;
    font-weight: 700; font-size: 7pt; text-align: left;
}
th.r, td.r { text-align: right; }
th.c, td.c { text-align: center; }
td { padding: 3px 7px; border: 1px solid #e2e8f0; color: #334155; }
tr:nth-child(even) td { background: #f8fafc; }
tr.tot td  { background: #dbeafe !important; font-weight: 700; color: #1e40af; }
tr.head-p1 th { background: var(--c-p1-bg); color: var(--c-p1); border-color: var(--c-p1-border); }
tr.head-p2 th { background: var(--c-p2-bg); color: var(--c-p2); border-color: var(--c-p2-border); }
tr.head-p3 th { background: var(--c-p3-bg); color: var(--c-p3); border-color: var(--c-p3-border); }
.diff-up   { color: #16a34a; font-weight: 700; }
.diff-down { color: #dc2626; font-weight: 700; }
.diff-nil  { color: #64748b; }

/* ── 2-col layouts ───────────────────────────────────────────────── */
.cols-asym  { display: grid; grid-template-columns: 3fr 2fr; gap: 8px; margin-bottom: 7px; }
.cols-2     { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 7px; }
.cols-2-eq  { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.section    { margin-bottom: 6px; }

/* ── Chart box ───────────────────────────────────────────────────── */
.chart-box { border: 1px solid #e2e8f0; border-radius: 5px; padding: 7px 10px; }
.chart-wrap { position: relative; }

/* ── Event badges ────────────────────────────────────────────────── */
.ev-section { border: 1px solid #e2e8f0; border-radius: 5px; padding: 5px 8px; margin-bottom: 6px; }
.ev-section .ev-head { font-size: 6.5pt; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 4px; }
.ev-list { display: flex; flex-wrap: wrap; gap: 3px; }
.ev-badge { border-radius: 4px; padding: 2px 6px; font-size: 6.5pt; }
.ev-badge.p1 { background: var(--c-p1-bg); color: var(--c-p1); border: 1px solid var(--c-p1-border); }
.ev-badge.p2 { background: var(--c-p2-bg); color: var(--c-p2); border: 1px solid var(--c-p2-border); }
.ev-badge.p3 { background: var(--c-p3-bg); color: var(--c-p3); border: 1px solid var(--c-p3-border); }
.ev-badge .ev-name { font-weight: 700; }
.ev-badge .ev-per  { font-weight: 400; margin-left: 3px; opacity: .75; }

/* ── Footer ──────────────────────────────────────────────────────── */
.rpt-footer {
    margin-top: 8px; font-size: 6.5pt; color: #94a3b8;
    display: flex; justify-content: space-between;
    border-top: 1px solid #e2e8f0; padding-top: 4px;
}
</style>
</head>
<body>
<?php
$n   = fn(int $v) => number_format($v, 0, ',', '.');
$fmt = fn(int $v) => $v > 0 ? number_format($v, 0, ',', '.') : '—';

function pctDiffPrint(int $a, int $b): ?float {
    if ($a === 0) return null;
    return round(($b - $a) / $a * 100, 1);
}
function diffCell(int $base, int $val, string $n): string {
    $pct = pctDiffPrint($base, $val);
    if ($pct === null) return '<span class="diff-nil">—</span>';
    $cls = $pct > 0 ? 'diff-up' : ($pct < 0 ? 'diff-down' : 'diff-nil');
    $pre = $pct > 0 ? '+' : '';
    return '<span class="' . $cls . '">' . $pre . $pct . '%</span><br><span style="font-size:6.5pt;color:#64748b">' . $n . '</span>';
}

$p1Label = date('d M Y', strtotime($from1)) . ' — ' . date('d M Y', strtotime($to1));
$p2Label = date('d M Y', strtotime($from2)) . ' — ' . date('d M Y', strtotime($to2));
$p3Label = $hasP3 ? date('d M Y', strtotime($from3)) . ' — ' . date('d M Y', strtotime($to3)) : '';

$hasVehicleData = array_sum(array_column($p1Vehicles, null))
                + array_sum(array_column($p2Vehicles, null))
                + array_sum(array_column($p3Vehicles, null)) > 0;

$vtypes = ['mobil' => 'Mobil', 'motor' => 'Motor', 'mobil_box' => 'Mobil Box', 'bus' => 'Bus', 'truck' => 'Truck', 'taxi' => 'Taxi'];

$backUrl = base_url('traffic/compare') . '?from1=' . $from1 . '&to1=' . $to1 . '&from2=' . $from2 . '&to2=' . $to2;
if ($hasP3) $backUrl .= '&from3=' . $from3 . '&to3=' . $to3;

// Build combined door maps
$allDoorsEwalk = [];
foreach ($door1Ewalk as $d) $allDoorsEwalk[$d['pintu']] = ['p1' => (int)$d['total'], 'p2' => 0, 'p3' => 0];
foreach ($door2Ewalk as $d) { $allDoorsEwalk[$d['pintu']] ??= ['p1'=>0,'p2'=>0,'p3'=>0]; $allDoorsEwalk[$d['pintu']]['p2'] = (int)$d['total']; }
if ($hasP3) foreach ($door3Ewalk as $d) { $allDoorsEwalk[$d['pintu']] ??= ['p1'=>0,'p2'=>0,'p3'=>0]; $allDoorsEwalk[$d['pintu']]['p3'] = (int)$d['total']; }

$allDoorsPenta = [];
foreach ($door1Penta as $d) $allDoorsPenta[$d['pintu']] = ['p1' => (int)$d['total'], 'p2' => 0, 'p3' => 0];
foreach ($door2Penta as $d) { $allDoorsPenta[$d['pintu']] ??= ['p1'=>0,'p2'=>0,'p3'=>0]; $allDoorsPenta[$d['pintu']]['p2'] = (int)$d['total']; }
if ($hasP3) foreach ($door3Penta as $d) { $allDoorsPenta[$d['pintu']] ??= ['p1'=>0,'p2'=>0,'p3'=>0]; $allDoorsPenta[$d['pintu']]['p3'] = (int)$d['total']; }
uasort($allDoorsEwalk, fn($a,$b) => ($b['p1']+$b['p2']+$b['p3']) <=> ($a['p1']+$a['p2']+$a['p3']));
uasort($allDoorsPenta, fn($a,$b) => ($b['p1']+$b['p2']+$b['p3']) <=> ($a['p1']+$a['p2']+$a['p3']));
?>

<!-- Print bar -->
<div class="print-bar">
    <a href="<?= $backUrl ?>">← Kembali ke Compare</a>
    <span style="font-weight:600">Compare Traffic — <?= $p1Label ?> vs <?= $p2Label ?></span>
    <button onclick="window.print()">🖨️ Print / Save PDF</button>
</div>
<div class="page-top-spacer"></div>


<!-- ══════════════════ HALAMAN 1 — OVERVIEW ══════════════════════════ -->

<div class="rpt-header">
    <div>
        <h1>Perbandingan Traffic — eWalk &amp; Pentacity</h1>
        <div class="sub">PT. Wulandari Bangun Laksana Tbk. &nbsp;·&nbsp; Mall Intelligence Center v1.3</div>
    </div>
    <div class="right">
        <strong><?= $hasP3 ? 'Tiga Periode' : 'Dua Periode' ?></strong>
        Generate: <?= date('d M Y H:i') ?>
    </div>
</div>

<!-- Period pills -->
<div class="period-strip">
    <div class="period-pill p1"><span class="pill-label">Periode 1</span><span class="pill-date"><?= $p1Label ?></span></div>
    <div class="period-pill p2"><span class="pill-label">Periode 2</span><span class="pill-date"><?= $p2Label ?></span></div>
    <?php if ($hasP3): ?>
    <div class="period-pill p3"><span class="pill-label">Periode 3</span><span class="pill-date"><?= $p3Label ?></span></div>
    <?php endif; ?>
</div>

<!-- KPI table + Weekday/Weekend side by side -->
<div class="cols-asym">

    <!-- Visitor KPI table -->
    <div>
        <div class="sec-label">Perbandingan Pengunjung</div>
        <table>
            <thead>
                <tr>
                    <th>Metrik</th>
                    <th class="r" style="color:var(--c-p1)">Periode 1</th>
                    <th class="r" style="color:var(--c-p2)">Periode 2</th>
                    <?php if ($hasP3): ?><th class="r" style="color:var(--c-p3)">Periode 3</th><?php endif; ?>
                    <th class="c">Selisih P1→P2</th>
                    <?php if ($hasP3): ?><th class="c">Selisih P1→P3</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php
            $visitorRows = [
                ['Total Pengunjung', $p1Total,  $p2Total,  $p3Total],
                ['eWalk',            $p1Ewalk,  $p2Ewalk,  $p3Ewalk],
                ['Pentacity',        $p1Penta,  $p2Penta,  $p3Penta],
            ];
            foreach ($visitorRows as [$lbl, $v1, $v2, $v3]):
            ?>
            <tr>
                <td class="fw-medium"><?= $lbl ?></td>
                <td class="r" style="color:var(--c-p1);font-weight:700"><?= $n($v1) ?></td>
                <td class="r" style="color:var(--c-p2);font-weight:700"><?= $n($v2) ?></td>
                <?php if ($hasP3): ?><td class="r" style="color:var(--c-p3);font-weight:700"><?= $n($v3) ?></td><?php endif; ?>
                <td class="c"><?= diffCell($v1, $v2, $n($v2)) ?></td>
                <?php if ($hasP3): ?><td class="c"><?= diffCell($v1, $v3, $n($v3)) ?></td><?php endif; ?>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($hasVehicleData): ?>
        <div style="margin-top:7px">
        <div class="sec-label org">Perbandingan Kendaraan</div>
        <table>
            <thead>
                <tr>
                    <th>Tipe</th>
                    <th class="r" style="color:var(--c-p1)">P1</th>
                    <th class="r" style="color:var(--c-p2)">P2</th>
                    <?php if ($hasP3): ?><th class="r" style="color:var(--c-p3)">P3</th><?php endif; ?>
                    <th class="c">Selisih P1→P2</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($vtypes as $vk => $vl):
                $v1 = $p1Vehicles[$vk] ?? 0;
                $v2 = $p2Vehicles[$vk] ?? 0;
                $v3 = $p3Vehicles[$vk] ?? 0;
                if ($v1 + $v2 + $v3 === 0) continue;
            ?>
            <tr>
                <td><?= $vl ?></td>
                <td class="r" style="color:var(--c-p1)"><?= $n($v1) ?></td>
                <td class="r" style="color:var(--c-p2)"><?= $n($v2) ?></td>
                <?php if ($hasP3): ?><td class="r" style="color:var(--c-p3)"><?= $n($v3) ?></td><?php endif; ?>
                <td class="c"><?= diffCell($v1, $v2, $n($v2)) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Weekday / Weekend table -->
    <div>
        <div class="sec-label">Weekdays vs Weekend</div>
        <table>
            <thead>
                <tr>
                    <th>Segmen</th>
                    <th class="r" style="color:var(--c-p1)">P1 Total</th>
                    <th class="r" style="color:var(--c-p1)">P1 Avg</th>
                    <th class="r" style="color:var(--c-p2)">P2 Total</th>
                    <th class="r" style="color:var(--c-p2)">P2 Avg</th>
                    <?php if ($hasP3): ?>
                    <th class="r" style="color:var(--c-p3)">P3 Total</th>
                    <th class="r" style="color:var(--c-p3)">P3 Avg</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ([['Weekdays (Sen–Kam)', 'wd'], ['Weekend (Jum–Min)', 'we']] as [$lbl, $seg]): ?>
            <tr>
                <td class="fw-medium"><?= $lbl ?></td>
                <td class="r"><?= $n($p1WdWe[$seg]['total']) ?></td>
                <td class="r" style="color:#64748b"><?= $n($p1WdWe[$seg]['avg']) ?></td>
                <td class="r"><?= $n($p2WdWe[$seg]['total']) ?></td>
                <td class="r" style="color:#64748b"><?= $n($p2WdWe[$seg]['avg']) ?></td>
                <?php if ($hasP3): ?>
                <td class="r"><?= $n($p3WdWe[$seg]['total']) ?></td>
                <td class="r" style="color:#64748b"><?= $n($p3WdWe[$seg]['avg']) ?></td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Events per periode -->
        <?php
        $anyEvents = ! empty($p1Events) || ! empty($p2Events) || ($hasP3 && ! empty($p3Events));
        if ($anyEvents):
        ?>
        <div style="margin-top:7px">
        <div class="sec-label">Event dalam Periode</div>
        <?php foreach ([['p1', $p1Events], ['p2', $p2Events]] as [$cls, $evs]):
            if (empty($evs)) continue; ?>
        <div class="ev-section" style="margin-bottom:4px">
            <div class="ev-head" style="color:var(--c-<?= $cls ?>)">Periode <?= strtoupper(substr($cls,1)) ?></div>
            <div class="ev-list">
            <?php foreach ($evs as $ev):
                $evEnd = date('d M Y', strtotime($ev['start_date'] . ' +' . ($ev['event_days'] - 1) . ' days'));
            ?>
                <div class="ev-badge <?= $cls ?>">
                    <span class="ev-name"><?= esc($ev['name']) ?></span>
                    <span class="ev-per"><?= date('d M Y', strtotime($ev['start_date'])) ?> – <?= $evEnd ?></span>
                </div>
            <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
        <?php if ($hasP3 && ! empty($p3Events)): ?>
        <div class="ev-section">
            <div class="ev-head" style="color:var(--c-p3)">Periode 3</div>
            <div class="ev-list">
            <?php foreach ($p3Events as $ev):
                $evEnd = date('d M Y', strtotime($ev['start_date'] . ' +' . ($ev['event_days'] - 1) . ' days'));
            ?>
                <div class="ev-badge p3">
                    <span class="ev-name"><?= esc($ev['name']) ?></span>
                    <span class="ev-per"><?= date('d M Y', strtotime($ev['start_date'])) ?> – <?= $evEnd ?></span>
                </div>
            <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

</div>

<!-- Charts: Daily bar + Hourly line -->
<div class="cols-asym">
    <div class="chart-box">
        <div class="sec-label">Traffic Harian per Periode (Hari ke-N)</div>
        <div class="chart-wrap" style="height:55mm"><canvas id="dailyChart"></canvas></div>
    </div>
    <div class="chart-box">
        <div class="sec-label">Distribusi per Jam</div>
        <div class="chart-wrap" style="height:55mm"><canvas id="hourChart"></canvas></div>
    </div>
</div>

<div class="rpt-footer">
    <span>Mall Intelligence Center v1.3 · PT. Wulandari Bangun Laksana Tbk.</span>
    <span>Halaman 1 dari 2</span>
    <span>KONFIDENSIAL — Hanya untuk internal perusahaan</span>
</div>


<!-- ══════════════════ HALAMAN 2 — DETAIL PINTU ══════════════════════ -->
<div class="page-break"></div>

<div class="rpt-header">
    <div>
        <h1>Detail Per Pintu — Perbandingan Periode</h1>
        <div class="sub">PT. Wulandari Bangun Laksana Tbk. &nbsp;·&nbsp; <?= $p1Label ?> vs <?= $p2Label ?></div>
    </div>
    <div class="right"><strong>Halaman 2 dari 2</strong>Mall Intelligence Center v1.3</div>
</div>

<!-- Door tables -->
<div class="cols-2-eq">

    <!-- eWalk -->
    <?php if (! empty($allDoorsEwalk)): ?>
    <div class="section">
        <div class="sec-label">Per Pintu — eWalk</div>
        <table>
            <thead>
                <tr>
                    <th>Pintu</th>
                    <th class="r" style="color:var(--c-p1)">P1</th>
                    <th class="r" style="color:var(--c-p2)">P2</th>
                    <?php if ($hasP3): ?><th class="r" style="color:var(--c-p3)">P3</th><?php endif; ?>
                    <th class="c">Selisih P1→P2</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($allDoorsEwalk as $pintu => $v): ?>
            <tr>
                <td><?= esc($pintu) ?></td>
                <td class="r"><?= $n($v['p1']) ?></td>
                <td class="r"><?= $n($v['p2']) ?></td>
                <?php if ($hasP3): ?><td class="r"><?= $n($v['p3']) ?></td><?php endif; ?>
                <td class="c"><?= diffCell($v['p1'], $v['p2'], $n($v['p2'])) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="tot">
                    <td>TOTAL</td>
                    <td class="r"><?= $n($p1Ewalk) ?></td>
                    <td class="r"><?= $n($p2Ewalk) ?></td>
                    <?php if ($hasP3): ?><td class="r"><?= $n($p3Ewalk) ?></td><?php endif; ?>
                    <td class="c"><?= diffCell($p1Ewalk, $p2Ewalk, $n($p2Ewalk)) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php endif; ?>

    <!-- Pentacity -->
    <?php if (! empty($allDoorsPenta)): ?>
    <div class="section">
        <div class="sec-label grn">Per Pintu — Pentacity</div>
        <table>
            <thead>
                <tr>
                    <th style="background:#d1fae5;color:#065f46">Pintu</th>
                    <th class="r" style="background:#d1fae5;color:var(--c-p1)">P1</th>
                    <th class="r" style="background:#d1fae5;color:var(--c-p2)">P2</th>
                    <?php if ($hasP3): ?><th class="r" style="background:#d1fae5;color:var(--c-p3)">P3</th><?php endif; ?>
                    <th class="c" style="background:#d1fae5;color:#065f46">Selisih P1→P2</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($allDoorsPenta as $pintu => $v): ?>
            <tr>
                <td><?= esc($pintu) ?></td>
                <td class="r"><?= $n($v['p1']) ?></td>
                <td class="r"><?= $n($v['p2']) ?></td>
                <?php if ($hasP3): ?><td class="r"><?= $n($v['p3']) ?></td><?php endif; ?>
                <td class="c"><?= diffCell($v['p1'], $v['p2'], $n($v['p2'])) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="tot" style="background:#d1fae5 !important;color:#065f46 !important">
                    <td>TOTAL</td>
                    <td class="r"><?= $n($p1Penta) ?></td>
                    <td class="r"><?= $n($p2Penta) ?></td>
                    <?php if ($hasP3): ?><td class="r"><?= $n($p3Penta) ?></td><?php endif; ?>
                    <td class="c"><?= diffCell($p1Penta, $p2Penta, $n($p2Penta)) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php endif; ?>

</div>

<div class="rpt-footer">
    <span>Mall Intelligence Center v1.3 · PT. Wulandari Bangun Laksana Tbk.</span>
    <span>Halaman 2 dari 2</span>
    <span>KONFIDENSIAL — Hanya untuk internal perusahaan</span>
</div>


<!-- ══════════════════ CHARTS JS ═════════════════════════════════════ -->
<script>
Chart.defaults.animation = false;
Chart.defaults.font.family = "'Segoe UI', Calibri, Arial, sans-serif";

const tickFmt    = v => v > 0 ? v.toLocaleString('id-ID') : '';
const smallTick  = { font: { size: 7 } };
const smallLegend = { position: 'top', labels: { boxWidth: 10, padding: 8, font: { size: 7.5 } } };

// Daily chart
new Chart(document.getElementById('dailyChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($dayLabels) ?>,
        datasets: [
            { label: 'Periode 1', data: <?= json_encode($p1Daily) ?>, backgroundColor: 'rgba(99,102,241,0.78)', borderRadius: 2 },
            { label: 'Periode 2', data: <?= json_encode($p2Daily) ?>, backgroundColor: 'rgba(249,115,22,0.78)',  borderRadius: 2 }
            <?php if ($hasP3): ?>
            ,{ label: 'Periode 3', data: <?= json_encode($p3Daily) ?>, backgroundColor: 'rgba(16,185,129,0.78)', borderRadius: 2 }
            <?php endif; ?>
        ]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: smallLegend },
        scales: {
            x: { ticks: { ...smallTick, maxRotation: 0, autoSkip: true, maxTicksLimit: 20 }, grid: { display: false } },
            y: { beginAtZero: true, ticks: { ...smallTick, callback: tickFmt } }
        }
    }
});

// Hourly chart
new Chart(document.getElementById('hourChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($chartHours) ?>,
        datasets: [
            { label: 'Periode 1', data: <?= json_encode($p1HourData) ?>, borderColor: 'rgba(99,102,241,1)',  backgroundColor: 'rgba(99,102,241,0.08)',  tension: 0.4, fill: true, pointRadius: 2, borderWidth: 1.5 },
            { label: 'Periode 2', data: <?= json_encode($p2HourData) ?>, borderColor: 'rgba(249,115,22,1)',  backgroundColor: 'rgba(249,115,22,0.08)',  tension: 0.4, fill: true, pointRadius: 2, borderWidth: 1.5 }
            <?php if ($hasP3): ?>
            ,{ label: 'Periode 3', data: <?= json_encode($p3HourData) ?>, borderColor: 'rgba(16,185,129,1)', backgroundColor: 'rgba(16,185,129,0.08)', tension: 0.4, fill: true, pointRadius: 2, borderWidth: 1.5 }
            <?php endif; ?>
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

window.addEventListener('load', function () {
    setTimeout(function () { window.print(); }, 800);
});
</script>
</body>
</html>
