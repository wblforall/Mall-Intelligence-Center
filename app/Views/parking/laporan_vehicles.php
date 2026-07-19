<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan Bulanan Traffic Kendaraan Parkir — <?= $bulan ?></title>
<?= view('_laporan/_style') ?>
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
$n    = fn($v) => $v > 0 ? number_format($v) : '—';
$deltaHtml = function (?float $pct) {
    if ($pct === null) return '';
    $cls = $pct >= 0 ? 'delta-up' : 'delta-down';
    return '<span class="' . $cls . '">' . ($pct >= 0 ? '▲' : '▼') . ' ' . abs($pct) . '%</span>';
};
$typeLabel = ['mobil'=>'Mobil','motor'=>'Motor','box'=>'Mobil Box','truck'=>'Truck','taxi'=>'Taxi','bus'=>'Bus'];
$durLabel  = ['le1'=>'≤ 1 jam','h1_2'=>'1–2 jam','h2_3'=>'2–3 jam','h3_4'=>'3–4 jam','h4_5'=>'4–5 jam','h5_6'=>'5–6 jam','h6_7'=>'6–7 jam','gt7'=>'> 7 jam'];
$dowShort  = [1=>'Sen',2=>'Sel',3=>'Rab',4=>'Kam',5=>'Jum',6=>'Sab',7=>'Min'];
$durTotal  = array_sum($duration);
?>

<!-- ══ HEADER ══ -->
<div class="doc-header">
    <div>
        <div class="title">Laporan Bulanan — Traffic Kendaraan Parkir</div>
        <div class="sub"><?= $bulanLabel ?></div>
        <div class="org">PT. Wulandari Bangun Laksana Tbk. &mdash; IT Department &mdash; Mall Intelligence Center</div>
    </div>
    <div class="meta">
        Dicetak oleh: <?= esc($printedBy) ?><br>
        Tanggal cetak: <?= $printedAt ?><br>
        Sumber data: SPI Parking System
    </div>
</div>

<!-- ══ KPI ══ -->
<div class="kpi-row">
    <div class="kpi-box kpi-blue">
        <div class="kpi-label">Total Kendaraan Masuk</div>
        <div class="kpi-num"><?= number_format($grand) ?></div>
        <div class="kpi-sub"><?= $deltaHtml($changePct) ?> vs <?= $prevLabel ?> (<?= number_format($prevTotal) ?>)</div>
    </div>
    <div class="kpi-box kpi-green">
        <div class="kpi-label">Mobil</div>
        <div class="kpi-num"><?= number_format($byType['mobil']) ?></div>
        <div class="kpi-sub"><?= $grand > 0 ? round($byType['mobil'] / $grand * 100) : 0 ?>% · bulan lalu <?= number_format($prevByType['mobil']) ?></div>
    </div>
    <div class="kpi-box kpi-amber">
        <div class="kpi-label">Motor</div>
        <div class="kpi-num"><?= number_format($byType['motor']) ?></div>
        <div class="kpi-sub"><?= $grand > 0 ? round($byType['motor'] / $grand * 100) : 0 ?>% · bulan lalu <?= number_format($prevByType['motor']) ?></div>
    </div>
    <div class="kpi-box kpi-purple">
        <div class="kpi-label">Langganan / Pass</div>
        <div class="kpi-num"><?= number_format($freeTot) ?></div>
        <div class="kpi-sub"><?= $grand > 0 ? round($freeTot / $grand * 100) : 0 ?>% dari total · bayar <?= number_format($grand - $freeTot) ?></div>
    </div>
    <div class="kpi-box">
        <div class="kpi-label">Rata-rata / Hari</div>
        <div class="kpi-num"><?= number_format($avgDaily) ?></div>
        <div class="kpi-sub"><?= $deltaHtml($avgChangePct) ?> vs bulan lalu</div>
    </div>
    <div class="kpi-box">
        <div class="kpi-label">Hari Teramai</div>
        <div class="kpi-num" style="font-size:13px;padding-top:4px"><?= $peakDay ? date('d M Y', strtotime($peakDay)) : '—' ?></div>
        <div class="kpi-sub"><?= $peakVal ? number_format($peakVal) . ' kendaraan' : '' ?></div>
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
        <div class="chart-title">Tren Kendaraan — 6 Bulan Terakhir</div>
        <div class="chart-wrap"><canvas id="chartTrend"></canvas></div>
    </div>
    <div class="chart-box">
        <div class="chart-title">Kendaraan Harian — <?= $bulanLabel ?></div>
        <div class="chart-wrap"><canvas id="chartDaily"></canvas></div>
    </div>
</div>

<!-- ══ PER JENIS + WEEKDAY/WEEKEND & DURASI ══ -->
<div class="duo">
<div>
    <div class="sec-title"><span>Kendaraan per Jenis</span><span class="sec-sub">bayar vs langganan</span></div>
    <table class="main-table">
    <thead><tr><th>Jenis</th><th class="text-center">Bayar</th><th class="text-center">Langganan</th><th class="text-center">Total</th><th class="text-center"><?= $prevLabel ?></th><th class="text-center">Δ</th></tr></thead>
    <tbody>
    <?php foreach ($types as $t):
        $now = $byType[$t]; $prev = $prevByType[$t];
        if ($now === 0 && $prev === 0) continue;
        $d = $prev > 0 ? round(($now - $prev) / $prev * 100, 1) : null;
    ?>
        <tr><td><strong><?= $typeLabel[$t] ?? ucfirst($t) ?></strong></td>
            <td class="<?= $paid[$t] ? 'num' : 'zero' ?>"><?= $n($paid[$t]) ?></td>
            <td class="<?= $free[$t] ? 'num' : 'zero' ?>"><?= $n($free[$t]) ?></td>
            <td class="num"><strong><?= $n($now) ?></strong></td>
            <td class="<?= $prev ? 'num' : 'zero' ?>"><?= $n($prev) ?></td>
            <td class="num"><?= $d !== null ? $deltaHtml($d) : '—' ?></td></tr>
    <?php endforeach; ?>
        <tr><td><strong>TOTAL</strong></td>
            <td class="num"><strong><?= $n($grand - $freeTot) ?></strong></td>
            <td class="num"><strong><?= $n($freeTot) ?></strong></td>
            <td class="num"><strong><?= $n($grand) ?></strong></td>
            <td class="num"><strong><?= $n($prevTotal) ?></strong></td>
            <td class="num"><?= $deltaHtml($changePct) ?></td></tr>
    </tbody>
    </table>

    <div class="sec-title"><span>Weekday vs Weekend</span></div>
    <table class="main-table">
    <thead><tr><th></th><th class="text-center">Total</th><th class="text-center">Rata²/hari</th></tr></thead>
    <tbody>
        <tr><td><strong>Weekday</strong> <span class="subnote">Sen–Kam · <?= $wd['days'] ?> hari</span></td>
            <td class="num"><?= $n($wd['total']) ?></td><td class="num"><?= $n($wd['avg']) ?></td></tr>
        <tr><td><strong>Weekend</strong> <span class="subnote">Jum–Min · <?= $we['days'] ?> hari</span></td>
            <td class="num"><?= $n($we['total']) ?></td><td class="num"><?= $n($we['avg']) ?></td></tr>
    </tbody>
    </table>
</div>
<div>
    <div class="sec-title"><span>Distribusi Lama Parkir</span><span class="sec-sub">akumulasi <?= $bulanLabel ?></span></div>
    <table class="main-table">
    <thead><tr><th>Durasi</th><th class="text-center">Kendaraan</th><th class="text-center">Share</th></tr></thead>
    <tbody>
    <?php if ($durTotal === 0): ?>
        <tr><td colspan="3" style="color:#94a3b8;text-align:center">Belum ada data durasi bulan ini.</td></tr>
    <?php endif; ?>
    <?php foreach ($duration as $k => $v): if ($durTotal === 0) break; ?>
        <tr><td><?= $durLabel[$k] ?></td>
            <td class="<?= $v ? 'num' : 'zero' ?>"><?= $n($v) ?></td>
            <td class="num"><?= $durTotal > 0 ? round($v / $durTotal * 100, 1) : 0 ?>%</td></tr>
    <?php endforeach; ?>
    </tbody>
    </table>

    <div class="sec-title"><span>Gate Tersibuk</span><span class="sec-sub">akumulasi <?= $bulanLabel ?></span></div>
    <table class="main-table">
    <thead><tr><th>Gate Masuk</th><th class="text-center">Kendaraan</th><th>Gate Keluar</th><th class="text-center">Kendaraan</th></tr></thead>
    <tbody>
    <?php $rows = max(count($gateMasuk), count($gateKeluar));
    if ($rows === 0): ?>
        <tr><td colspan="4" style="color:#94a3b8;text-align:center">Belum ada data gate bulan ini.</td></tr>
    <?php endif;
    for ($i = 0; $i < $rows; $i++): $gm = $gateMasuk[$i] ?? null; $gk = $gateKeluar[$i] ?? null; ?>
        <tr>
            <td><?= $gm ? esc($gm['gate']) : '' ?></td>
            <td class="num"><?= $gm ? $n((int)$gm['total']) : '' ?></td>
            <td><?= $gk ? esc($gk['gate']) : '' ?></td>
            <td class="num"><?= $gk ? $n((int)$gk['total']) : '' ?></td>
        </tr>
    <?php endfor; ?>
    </tbody>
    </table>
</div>
</div>

<!-- ══ REKAP HARIAN ══ -->
<div class="sec-title"><span>Rekap Harian — <?= $bulanLabel ?></span>
    <span class="sec-sub">baris kuning = weekend (Jum–Min)</span></div>
<table class="main-table">
<thead><tr>
    <th style="width:13%">Tanggal</th>
    <th class="text-center">Mobil</th><th class="text-center">Motor</th><th class="text-center">Box</th>
    <th class="text-center">Truck</th><th class="text-center">Taxi</th><th class="text-center">Bus</th>
    <th class="text-center">Langganan</th><th class="text-center">Total</th>
</tr></thead>
<tbody>
<?php foreach ($daily as $d): if ((int)$d['total'] === 0) continue;
    $dow = (int)date('N', strtotime($d['tanggal']));
    $rowFree = 0;
    foreach ($types as $t) { $rowFree += min((int)($d[$t . '_free'] ?? 0), (int)($d[$t] ?? 0)); }
?>
    <tr class="<?= $dow >= 5 ? 'we-row' : '' ?>">
        <td><?= date('d/m', strtotime($d['tanggal'])) ?> <span class="subnote"><?= $dowShort[$dow] ?></span></td>
        <?php foreach ($types as $t): ?>
        <td class="<?= (int)$d[$t] ? 'num' : 'zero' ?>"><?= $n((int)$d[$t]) ?></td>
        <?php endforeach; ?>
        <td class="<?= $rowFree ? 'num' : 'zero' ?>"><?= $n($rowFree) ?></td>
        <td class="num"><strong><?= $n((int)$d['total']) ?></strong></td>
    </tr>
<?php endforeach; ?>
</tbody>
</table>

<?= view('_laporan/_ttd', ['signatories' => $signatories]) ?>

<div class="doc-footer">
    <span>Mall Intelligence Center &mdash; IT Department PT. Wulandari Bangun Laksana Tbk.</span>
    <span>Digenerate otomatis dari SPI &mdash; <?= $printedAt ?></span>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Palet tervalidasi CVD (surface terang)
const C = { blue: '#2a78d6', amber: '#eda100' };
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
            { label: 'Mobil', data: trend.map(t => t.mobil), backgroundColor: C.blue,  ...barStyle },
            { label: 'Motor', data: trend.map(t => t.motor), backgroundColor: C.amber, ...barStyle },
        ],
    },
    options: baseOpts,
});

const daily = <?= json_encode(array_map(fn($d) => ['l' => (int)substr($d['tanggal'], 8), 'v' => (int)$d['total']], $daily)) ?>;
new Chart(document.getElementById('chartDaily'), {
    type: 'bar',
    data: { labels: daily.map(d => String(d.l).padStart(2, '0')),
        datasets: [{ label: 'Kendaraan', data: daily.map(d => d.v), backgroundColor: C.blue, ...barStyle }] },
    options: { ...baseOpts, plugins: { legend: { display: false } }, scales: { ...baseOpts.scales,
        x: { ...baseOpts.scales.x, ticks: { ...baseOpts.scales.x.ticks, autoSkip: true, maxTicksLimit: 16 } } } },
});
</script>

</body>
</html>
