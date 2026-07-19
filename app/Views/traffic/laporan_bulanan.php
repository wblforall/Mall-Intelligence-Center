<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan Bulanan Traffic — <?= $bulan ?></title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: Arial, sans-serif; font-size: 11px; color: #111; background: #fff; }

@page { size: A4 landscape; margin: 12mm 14mm 10mm; }
@media print {
    .no-print { display: none !important; }
    body { font-size: 11px; }
}

/* ── Page break saat print ── */
thead { display: table-header-group; }
tbody.prog-block { break-inside: avoid; page-break-inside: avoid; }
.main-table tr { break-inside: avoid; page-break-inside: avoid; }
.sec-title { break-after: avoid; page-break-after: avoid; break-inside: avoid; }
.kpi-row, .chart-panel, .sign-row, .duo { break-inside: avoid; page-break-inside: avoid; }

/* ── Header ── */
.doc-header {
    border-bottom: 3px solid #1e293b; padding-bottom: 10px; margin-bottom: 16px;
    display: flex; justify-content: space-between; align-items: flex-end;
}
.doc-header .title { font-size: 18px; font-weight: 700; color: #1e293b; }
.doc-header .sub   { font-size: 13px; color: #475569; margin-top: 2px; }
.doc-header .org   { font-size: 10px; color: #94a3b8; margin-top: 5px; }
.doc-header .meta  { text-align: right; font-size: 10px; color: #64748b; line-height: 1.8; }

/* ── KPI ── */
.kpi-row { display: flex; gap: 10px; margin-bottom: 16px; }
.kpi-box { flex: 1; border: 1px solid #e2e8f0; border-radius: 6px; padding: 9px 12px; background: #f8fafc; }
.kpi-label { font-size: 10px; color: #64748b; margin-bottom: 3px; }
.kpi-num   { font-size: 19px; font-weight: 700; line-height: 1.15; }
.kpi-sub   { font-size: 9.5px; color: #94a3b8; margin-top: 2px; }
.kpi-total  { border-color: #bfdbfe; background: #eff6ff; } .kpi-total .kpi-num  { color: #1d4ed8; }
.kpi-ewalk  { border-color: #bfdbfe; background: #eff6ff; } .kpi-ewalk .kpi-num  { color: #2a78d6; }
.kpi-penta  { border-color: #bbf7d0; background: #f0fdf4; } .kpi-penta .kpi-num  { color: #15803d; }
.kpi-avg    { border-color: #fde68a; background: #fffbeb; } .kpi-avg .kpi-num    { color: #b45309; }
.delta-up   { color: #15803d; font-weight: 700; }
.delta-down { color: #b91c1c; font-weight: 700; }

/* ── Section & tabel ── */
.sec-title {
    font-size: 11px; font-weight: 700; color: #f1f5f9; text-transform: uppercase;
    letter-spacing: .4px; background: #1e293b; padding: 5px 10px;
    border-radius: 4px 4px 0 0; display: flex; justify-content: space-between; align-items: center;
}
.sec-title .sec-sub { font-weight: 400; font-size: 9.5px; opacity: .75; }
.main-table { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
.main-table th {
    background: #334155; color: #f1f5f9; font-size: 10px;
    padding: 5px 7px; border: 1px solid #475569; text-align: left; white-space: nowrap;
}
.main-table th.text-center { text-align: center; }
.main-table td { padding: 4px 7px; border: 1px solid #e2e8f0; font-size: 11px; vertical-align: middle; }
.main-table tr:nth-child(even) td { background: #f8fafc; }
.num  { text-align: right; font-variant-numeric: tabular-nums; }
.zero { color: #cbd5e1; text-align: right; }
.subnote { font-size: 9px; color: #94a3b8; }
.we-row td { background: #fffbeb !important; }

/* dua tabel berdampingan */
.duo { display: flex; gap: 14px; }
.duo > div { flex: 1; min-width: 0; }

/* ── Analisa & grafik ── */
.chart-panel {
    display: flex; gap: 10px; margin-bottom: 18px;
    border: 1px solid #e2e8f0; border-top: none; border-radius: 0 0 6px 6px; padding: 10px;
}
.insight-box { flex: 0 0 30%; }
.insight-title { font-size: 10.5px; font-weight: 700; color: #1e293b; margin-bottom: 5px; text-transform: uppercase; letter-spacing: .3px; }
.insight-list { margin: 0; padding-left: 14px; }
.insight-list li { font-size: 10.5px; color: #334155; line-height: 1.5; margin-bottom: 5px; }
.chart-box { flex: 1; min-width: 0; }
.chart-title { font-size: 10px; font-weight: 700; color: #475569; margin-bottom: 4px; }
.chart-wrap { height: 165px; position: relative; }

/* ── Signature & footer ── */
.sign-row { display: flex; gap: 20px; margin-top: 24px; }
.sign-box { flex: 1; border: 1px solid #e2e8f0; border-radius: 6px; padding: 9px 12px 10px; text-align: center; font-size: 11px; color: #475569; }
.sign-box .sign-role { font-weight: 700; color: #1e293b; font-size: 11.5px; margin-top: 3px; }
.doc-footer {
    margin-top: 16px; border-top: 1px solid #e2e8f0; padding-top: 6px;
    display: flex; justify-content: space-between; font-size: 9.5px; color: #94a3b8;
}
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
$fmtBulan   = fn($m) => strtr(\DateTime::createFromFormat('Y-m', $m)->format('F Y'), $idBulan);
$bulanLabel = $fmtBulan($bulan);
$prevLabel  = $fmtBulan($prevBulan);
$n          = fn($v) => $v > 0 ? number_format($v) : '—';
$deltaHtml  = function (?float $pct) {
    if ($pct === null) return '';
    $cls = $pct >= 0 ? 'delta-up' : 'delta-down';
    return '<span class="' . $cls . '">' . ($pct >= 0 ? '▲' : '▼') . ' ' . abs($pct) . '%</span>';
};
$dowShort  = [1=>'Sen',2=>'Sel',3=>'Rab',4=>'Kam',5=>'Jum',6=>'Sab',7=>'Min'];
$mallLabel = ['ewalk' => 'eWalk', 'pentacity' => 'Pentacity', 'both' => 'eWalk & Pentacity'];
?>

<!-- ══ HEADER ══ -->
<div class="doc-header">
    <div>
        <div class="title">Laporan Bulanan — Traffic Pengunjung</div>
        <div class="sub"><?= $bulanLabel ?></div>
        <div class="org">PT. Wulandari Bangun Laksana Tbk. &mdash; IT Department &mdash; Mall Intelligence Center</div>
    </div>
    <div class="meta">
        Dicetak oleh: <?= esc($printedBy) ?><br>
        Tanggal cetak: <?= $printedAt ?><br>
        Pembanding: <?= $prevLabel ?>
    </div>
</div>

<!-- ══ KPI ══ -->
<div class="kpi-row">
    <div class="kpi-box kpi-total">
        <div class="kpi-label">Total Pengunjung</div>
        <div class="kpi-num"><?= number_format($totalVisitor) ?></div>
        <div class="kpi-sub"><?= $deltaHtml($changePct) ?> vs <?= $prevLabel ?> (<?= number_format($prevTotal) ?>)</div>
    </div>
    <div class="kpi-box kpi-ewalk">
        <div class="kpi-label">eWalk</div>
        <div class="kpi-num"><?= number_format($totalEwalk) ?></div>
        <div class="kpi-sub"><?= $totalVisitor > 0 ? round($totalEwalk / $totalVisitor * 100) : 0 ?>% · bulan lalu <?= number_format($prevEwalk) ?></div>
    </div>
    <div class="kpi-box kpi-penta">
        <div class="kpi-label">Pentacity</div>
        <div class="kpi-num"><?= number_format($totalPenta) ?></div>
        <div class="kpi-sub"><?= $totalVisitor > 0 ? round($totalPenta / $totalVisitor * 100) : 0 ?>% · bulan lalu <?= number_format($prevPenta) ?></div>
    </div>
    <div class="kpi-box kpi-avg">
        <div class="kpi-label">Rata-rata / Hari</div>
        <div class="kpi-num"><?= number_format($avgDaily) ?></div>
        <div class="kpi-sub"><?= $deltaHtml($avgChangePct) ?> vs bulan lalu</div>
    </div>
    <div class="kpi-box">
        <div class="kpi-label">Hari Teramai</div>
        <div class="kpi-num" style="font-size:13px;padding-top:4px"><?= $bestDay ?: '—' ?></div>
        <div class="kpi-sub"><?= $bestVal ? number_format($bestVal) . ' pengunjung' : '' ?><?= $peakHour ? ' · jam puncak ' . $peakHour : '' ?></div>
    </div>
    <div class="kpi-box">
        <div class="kpi-label">Kendaraan (Mobil · Motor)</div>
        <div class="kpi-num" style="font-size:14px;padding-top:3px"><?= number_format($vehicles['mobil']) ?> · <?= number_format($vehicles['motor']) ?></div>
        <div class="kpi-sub">box <?= number_format($vehicles['mobil_box']) ?> · bus <?= number_format($vehicles['bus']) ?> · truck <?= number_format($vehicles['truck']) ?></div>
    </div>
</div>

<!-- ══ ANALISA & GRAFIK ══ -->
<div class="sec-title"><span>Analisa &amp; Tren</span>
    <span class="sec-sub">tren 6 bulan terakhir &middot; harian <?= $bulanLabel ?></span></div>
<div class="chart-panel">
    <div class="insight-box">
        <div class="insight-title">Ringkasan Analisa</div>
        <ul class="insight-list">
            <?php foreach (($insights ?? []) as $ins): ?>
            <li><?= esc($ins) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <div class="chart-box">
        <div class="chart-title">Tren Pengunjung — 6 Bulan Terakhir</div>
        <div class="chart-wrap"><canvas id="chartTrend"></canvas></div>
    </div>
    <div class="chart-box">
        <div class="chart-title">Pengunjung Harian — <?= $bulanLabel ?></div>
        <div class="chart-wrap"><canvas id="chartDaily"></canvas></div>
    </div>
</div>

<!-- ══ WEEKDAY VS WEEKEND + PER PINTU ══ -->
<div class="duo">
<div>
    <div class="sec-title"><span>Weekday vs Weekend</span></div>
    <table class="main-table">
    <thead><tr><th></th><th class="text-center">eWalk</th><th class="text-center">Pentacity</th><th class="text-center">Total</th><th class="text-center">Rata²/hari</th></tr></thead>
    <tbody>
        <tr><td><strong>Weekday</strong> <span class="subnote">Sen–Kam · <?= $wd['days'] ?> hari</span></td>
            <td class="num"><?= $n($wd['ewalk']) ?></td><td class="num"><?= $n($wd['pentacity']) ?></td>
            <td class="num"><strong><?= $n($wd['total']) ?></strong></td><td class="num"><?= $n($wd['avg']) ?></td></tr>
        <tr><td><strong>Weekend</strong> <span class="subnote">Jum–Min · <?= $we['days'] ?> hari</span></td>
            <td class="num"><?= $n($we['ewalk']) ?></td><td class="num"><?= $n($we['pentacity']) ?></td>
            <td class="num"><strong><?= $n($we['total']) ?></strong></td><td class="num"><?= $n($we['avg']) ?></td></tr>
    </tbody>
    </table>

    <div class="sec-title"><span>Pengunjung per Jam</span><span class="sec-sub">akumulasi sebulan</span></div>
    <table class="main-table">
    <thead><tr><th>Jam</th><th class="text-center">eWalk</th><th class="text-center">Pentacity</th><th class="text-center">Total</th></tr></thead>
    <tbody>
    <?php foreach ($hours as $h): if ($h['total'] === 0) continue; ?>
        <tr><td><?= $h['jam'] ?></td>
            <td class="<?= $h['ewalk'] ? 'num' : 'zero' ?>"><?= $n($h['ewalk']) ?></td>
            <td class="<?= $h['pentacity'] ? 'num' : 'zero' ?>"><?= $n($h['pentacity']) ?></td>
            <td class="num"><strong><?= $n($h['total']) ?></strong></td></tr>
    <?php endforeach; ?>
    </tbody>
    </table>
</div>
<div>
    <div class="sec-title"><span>Pengunjung per Pintu</span><span class="sec-sub">akumulasi sebulan</span></div>
    <table class="main-table">
    <thead><tr><th>Pintu</th><th>Mall</th><th class="text-center">Total</th><th class="text-center">%</th></tr></thead>
    <tbody>
    <?php
    $doorRows = [];
    foreach ($doorEwalk as $d) $doorRows[] = ['pintu' => $d['pintu'], 'mall' => 'eWalk',     'total' => (int)$d['total']];
    foreach ($doorPenta as $d) $doorRows[] = ['pintu' => $d['pintu'], 'mall' => 'Pentacity', 'total' => (int)$d['total']];
    usort($doorRows, fn($a, $b) => $b['total'] <=> $a['total']);
    foreach ($doorRows as $d): ?>
        <tr><td><?= esc($d['pintu']) ?></td><td style="color:#64748b;font-size:10px"><?= $d['mall'] ?></td>
            <td class="num"><?= $n($d['total']) ?></td>
            <td class="num"><?= $totalVisitor > 0 ? round($d['total'] / $totalVisitor * 100, 1) : 0 ?>%</td></tr>
    <?php endforeach; ?>
    </tbody>
    </table>
</div>
</div>

<!-- ══ TRAFFIC PER EVENT ══ -->
<?php if (! empty($periodEvents)): ?>
<div class="sec-title"><span>Traffic Selama Event Berlangsung</span>
    <span class="sec-sub"><?= count($periodEvents) ?> event beririsan dengan <?= $bulanLabel ?></span></div>
<table class="main-table">
<thead><tr>
    <th style="width:30%">Event</th><th style="width:12%">Mall</th><th style="width:20%">Periode Event</th>
    <th class="text-center" style="width:12%">Total Traffic</th>
    <th class="text-center" style="width:13%">Mobil</th><th class="text-center" style="width:13%">Motor</th>
</tr></thead>
<tbody>
<?php foreach ($periodEvents as $ev):
    $eid  = (int)$ev['id'];
    $end  = date('Y-m-d', strtotime($ev['start_date'] . ' +' . (max(1, (int)$ev['event_days']) - 1) . ' days'));
    $veh  = $eventVehicles[$eid] ?? ['mobil' => 0, 'motor' => 0];
?>
    <tr>
        <td><strong><?= esc($ev['name']) ?></strong></td>
        <td style="color:#64748b;font-size:10px"><?= $mallLabel[$ev['mall']] ?? esc(ucfirst((string)$ev['mall'])) ?></td>
        <td style="color:#64748b;font-size:10px"><?= date('d M', strtotime($ev['start_date'])) ?> – <?= date('d M Y', strtotime($end)) ?> (<?= (int)$ev['event_days'] ?> hari)</td>
        <td class="num"><?= $n((int)($eventTraffic[$eid] ?? 0)) ?></td>
        <td class="num"><?= $n((int)$veh['mobil']) ?></td>
        <td class="num"><?= $n((int)$veh['motor']) ?></td>
    </tr>
<?php endforeach; ?>
</tbody>
</table>
<div class="subnote" style="margin:-12px 0 14px">Traffic event = total pengunjung kedua mall pada rentang tanggal event (traffic tidak dicatat per event; event di tanggal sama saling berbagi angka).</div>
<?php endif; ?>

<!-- ══ REKAP HARIAN ══ -->
<div class="sec-title"><span>Rekap Harian — <?= $bulanLabel ?></span>
    <span class="sec-sub">baris kuning = weekend (Jum–Min)</span></div>
<table class="main-table">
<thead><tr>
    <th style="width:14%">Tanggal</th>
    <th class="text-center">eWalk</th><th class="text-center">Pentacity</th><th class="text-center">Total Pengunjung</th>
    <th class="text-center">Mobil</th><th class="text-center">Motor</th>
</tr></thead>
<tbody>
<?php foreach ($days as $d): if ($d['total'] === 0 && $d['mobil'] === 0 && $d['motor'] === 0) continue; ?>
    <tr class="<?= $d['dow'] >= 5 ? 'we-row' : '' ?>">
        <td><?= $d['date_fmt'] ?> <span class="subnote"><?= $dowShort[$d['dow']] ?></span></td>
        <td class="<?= $d['ewalk'] ? 'num' : 'zero' ?>"><?= $n($d['ewalk']) ?></td>
        <td class="<?= $d['pentacity'] ? 'num' : 'zero' ?>"><?= $n($d['pentacity']) ?></td>
        <td class="num"><strong><?= $n($d['total']) ?></strong></td>
        <td class="<?= $d['mobil'] ? 'num' : 'zero' ?>"><?= $n($d['mobil']) ?></td>
        <td class="<?= $d['motor'] ? 'num' : 'zero' ?>"><?= $n($d['motor']) ?></td>
    </tr>
<?php endforeach; ?>
</tbody>
</table>

<!-- ══ TANDA TANGAN ══ -->
<?php
$sg = $signatories ?? [];
$signSlot = function (?array $s) {
    if ($s) {
        return '<div style="height:42px"></div><span class="sign-role" style="text-decoration:underline">' . esc($s['nama']) . '</span>'
             . '<div style="font-size:8.5px;color:#64748b;margin-top:2px">' . esc($s['jabatan']) . '</div>';
    }
    return '<div style="height:42px"></div><span class="sign-role">( ……………………………… )</span>';
};
?>
<div class="sign-row">
    <div class="sign-box">Disusun oleh<?= $signSlot($sg['disusun'] ?? null) ?></div>
    <?php if (! empty($sg['diperiksa_sm'])): ?>
    <div class="sign-box" style="flex:1.6">
        Diperiksa oleh
        <div style="display:flex;gap:14px">
            <div style="flex:1"><?= $signSlot($sg['diperiksa_sm']) ?></div>
            <div style="flex:1"><?= $signSlot($sg['diperiksa'] ?? null) ?></div>
        </div>
    </div>
    <?php else: ?>
    <div class="sign-box">Diperiksa oleh<?= $signSlot($sg['diperiksa'] ?? null) ?></div>
    <?php endif; ?>
    <div class="sign-box">Mengetahui<?= $signSlot($sg['mengetahui'] ?? null) ?></div>
</div>

<!-- ══ FOOTER ══ -->
<div class="doc-footer">
    <span>Mall Intelligence Center &mdash; IT Department PT. Wulandari Bangun Laksana Tbk.</span>
    <span>Digenerate otomatis &mdash; <?= $printedAt ?></span>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Palet tervalidasi CVD (surface terang): eWalk biru, Pentacity hijau — konsisten dashboard
const C = { ewalk: '#2a78d6', penta: '#1baf7a' };
const ink  = 'rgba(51,65,85,.75)';
const grid = 'rgba(0,0,0,.06)';
Chart.defaults.animation = false;
Chart.defaults.devicePixelRatio = 2;

const nShort = v => v >= 1e6 ? (v/1e6).toFixed(1) + ' jt' : v >= 1e3 ? Math.round(v/1e3) + ' rb' : v;
const baseOpts = {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { position: 'bottom', labels: { color: ink, usePointStyle: true, pointStyle: 'circle', boxWidth: 7, boxHeight: 7, font: { size: 9.5 } } } },
    scales: {
        x: { ticks: { color: ink, font: { size: 9.5 } }, grid: { display: false } },
        y: { ticks: { color: ink, font: { size: 9.5 }, callback: v => nShort(v) }, grid: { color: grid }, beginAtZero: true },
    },
};
const barStyle = { borderColor: '#ffffff', borderWidth: 1, borderRadius: 3, borderSkipped: false };

const trend = <?= json_encode($trendMonths ?? []) ?>;
const idShort = { '01':'Jan','02':'Feb','03':'Mar','04':'Apr','05':'Mei','06':'Jun','07':'Jul','08':'Agu','09':'Sep','10':'Okt','11':'Nov','12':'Des' };
const mLabel  = m => idShort[m.slice(5)] + ' ' + m.slice(2, 4);
new Chart(document.getElementById('chartTrend'), {
    type: 'bar',
    data: {
        labels: trend.map(t => mLabel(t.bulan)),
        datasets: [
            { label: 'eWalk',     data: trend.map(t => t.ewalk),     backgroundColor: C.ewalk, ...barStyle },
            { label: 'Pentacity', data: trend.map(t => t.pentacity), backgroundColor: C.penta, ...barStyle },
        ],
    },
    options: baseOpts,
});

const days = <?= json_encode(array_map(fn($d) => ['l' => (int)substr($d['tanggal'], 8), 'e' => $d['ewalk'], 'p' => $d['pentacity']], $days)) ?>;
new Chart(document.getElementById('chartDaily'), {
    type: 'bar',
    data: {
        labels: days.map(d => String(d.l).padStart(2, '0')),
        datasets: [
            { label: 'eWalk',     data: days.map(d => d.e), backgroundColor: C.ewalk, ...barStyle, stack: 's' },
            { label: 'Pentacity', data: days.map(d => d.p), backgroundColor: C.penta, ...barStyle, stack: 's' },
        ],
    },
    options: { ...baseOpts, scales: { ...baseOpts.scales,
        x: { ...baseOpts.scales.x, stacked: true, ticks: { ...baseOpts.scales.x.ticks, autoSkip: true, maxTicksLimit: 16 } },
        y: { ...baseOpts.scales.y, stacked: true } } },
});
</script>

</body>
</html>
