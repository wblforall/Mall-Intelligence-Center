<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan Bulanan Pendapatan Parkir — <?= $bulan ?></title>
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
$rp   = fn($v) => $v > 0 ? 'Rp ' . number_format($v, 0, ',', '.') : '—';
$n    = fn($v) => $v > 0 ? number_format($v) : '—';
$deltaHtml = function (?float $pct) {
    if ($pct === null) return '';
    $cls = $pct >= 0 ? 'delta-up' : 'delta-down';
    return '<span class="' . $cls . '">' . ($pct >= 0 ? '▲' : '▼') . ' ' . abs($pct) . '%</span>';
};
$typeLabel = ['mobil'=>'Mobil','motor'=>'Motor','box'=>'Mobil Box','truck'=>'Truck','taxi'=>'Taxi','bus'=>'Bus'];
$dowShort  = [1=>'Sen',2=>'Sel',3=>'Rab',4=>'Kam',5=>'Jum',6=>'Sab',7=>'Min'];
?>

<!-- ══ HEADER ══ -->
<div class="doc-header">
    <div>
        <div class="title">Laporan Bulanan — Pendapatan Parkir</div>
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
        <div class="kpi-label">Total Pendapatan</div>
        <div class="kpi-num" style="font-size:16px;padding-top:2px"><?= $rp($total) ?></div>
        <div class="kpi-sub"><?= $deltaHtml($changePct) ?> vs <?= $prevLabel ?> (<?= $rp($prevTotal) ?>)</div>
    </div>
    <div class="kpi-box kpi-green">
        <div class="kpi-label">Pendapatan Mobil</div>
        <div class="kpi-num" style="font-size:15px;padding-top:3px"><?= $rp($byType['mobil']) ?></div>
        <div class="kpi-sub"><?= $total > 0 ? round($byType['mobil'] / $total * 100) : 0 ?>% · bulan lalu <?= $rp($prevByType['mobil']) ?></div>
    </div>
    <div class="kpi-box kpi-amber">
        <div class="kpi-label">Pendapatan Motor</div>
        <div class="kpi-num" style="font-size:15px;padding-top:3px"><?= $rp($byType['motor']) ?></div>
        <div class="kpi-sub"><?= $total > 0 ? round($byType['motor'] / $total * 100) : 0 ?>% · bulan lalu <?= $rp($prevByType['motor']) ?></div>
    </div>
    <div class="kpi-box kpi-purple">
        <div class="kpi-label">Casual vs Member</div>
        <div class="kpi-num" style="font-size:13px;padding-top:5px"><?= $rp($kpiCasual) ?></div>
        <div class="kpi-sub">member/langganan <?= $rp($kpiMember) ?></div>
    </div>
    <div class="kpi-box">
        <div class="kpi-label">Rata-rata / Hari</div>
        <div class="kpi-num" style="font-size:15px;padding-top:3px"><?= $rp($avgDaily) ?></div>
        <div class="kpi-sub"><?= $deltaHtml($avgChangePct) ?> vs bulan lalu</div>
    </div>
    <div class="kpi-box">
        <div class="kpi-label">Hari Tertinggi</div>
        <div class="kpi-num" style="font-size:13px;padding-top:4px"><?= $maxDay ? date('d M Y', strtotime($maxDay)) : '—' ?></div>
        <div class="kpi-sub"><?= $maxVal ? $rp($maxVal) : '' ?></div>
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
        <div class="chart-title">Tren Pendapatan — 6 Bulan Terakhir (Rp)</div>
        <div class="chart-wrap"><canvas id="chartTrend"></canvas></div>
    </div>
    <div class="chart-box">
        <div class="chart-title">Pendapatan Harian — <?= $bulanLabel ?> (Rp)</div>
        <div class="chart-wrap"><canvas id="chartDaily"></canvas></div>
    </div>
</div>

<!-- ══ PER JENIS + METODE PEMBAYARAN ══ -->
<div class="duo">
<div>
    <div class="sec-title"><span>Pendapatan per Jenis Kendaraan</span></div>
    <table class="main-table">
    <thead><tr><th>Jenis</th><th class="text-center"><?= $bulanLabel ?></th><th class="text-center"><?= $prevLabel ?></th><th class="text-center">Δ</th><th class="text-center">Share</th></tr></thead>
    <tbody>
    <?php foreach ($types as $t):
        $now = $byType[$t]; $prev = $prevByType[$t];
        if ($now === 0 && $prev === 0) continue;
        $d = $prev > 0 ? round(($now - $prev) / $prev * 100, 1) : null;
    ?>
        <tr><td><strong><?= $typeLabel[$t] ?? ucfirst($t) ?></strong></td>
            <td class="<?= $now ? 'num' : 'zero' ?>"><?= $rp($now) ?></td>
            <td class="<?= $prev ? 'num' : 'zero' ?>"><?= $rp($prev) ?></td>
            <td class="num"><?= $d !== null ? $deltaHtml($d) : '—' ?></td>
            <td class="num"><?= $total > 0 ? round($now / $total * 100, 1) : 0 ?>%</td></tr>
    <?php endforeach; ?>
        <tr><td><strong>TOTAL</strong></td>
            <td class="num"><strong><?= $rp($total) ?></strong></td>
            <td class="num"><strong><?= $rp($prevTotal) ?></strong></td>
            <td class="num"><?= $deltaHtml($changePct) ?></td>
            <td class="num">100%</td></tr>
    </tbody>
    </table>
</div>
<div>
    <div class="sec-title"><span>Metode Pembayaran</span><span class="sec-sub">akumulasi <?= $bulanLabel ?></span></div>
    <table class="main-table">
    <thead><tr><th>Metode</th><th class="text-center">Nilai</th><th class="text-center">Share</th></tr></thead>
    <tbody>
    <?php if (empty($payments)): ?>
        <tr><td colspan="3" style="color:#94a3b8;text-align:center">Belum ada data metode pembayaran bulan ini.</td></tr>
    <?php endif; ?>
    <?php foreach ($payments as $p): ?>
        <tr><td><strong><?= esc($p['method']) ?></strong></td>
            <td class="num"><?= $rp((int)$p['total']) ?></td>
            <td class="num"><?= $payTotal > 0 ? round((int)$p['total'] / $payTotal * 100, 1) : 0 ?>%</td></tr>
    <?php endforeach; ?>
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
    <th class="text-center">Total</th>
</tr></thead>
<tbody>
<?php foreach ($daily as $d): if ((int)$d['total'] === 0) continue; $dow = (int)date('N', strtotime($d['tanggal'])); ?>
    <tr class="<?= $dow >= 5 ? 'we-row' : '' ?>">
        <td><?= date('d/m', strtotime($d['tanggal'])) ?> <span class="subnote"><?= $dowShort[$dow] ?></span></td>
        <?php foreach ($types as $t): ?>
        <td class="<?= (int)$d[$t] ? 'num' : 'zero' ?>"><?= $n((int)$d[$t]) ?></td>
        <?php endforeach; ?>
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
const C = { blue: '#2a78d6', green: '#1baf7a', amber: '#eda100' };
const ink  = 'rgba(51,65,85,.75)';
const grid = 'rgba(0,0,0,.06)';
Chart.defaults.animation = false;
Chart.defaults.devicePixelRatio = 2;

const rpShort = v => v >= 1e9 ? (v/1e9).toFixed(1) + ' M' : v >= 1e6 ? Math.round(v/1e6) + ' jt' : v >= 1e3 ? Math.round(v/1e3) + ' rb' : v;
const baseOpts = {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { position: 'bottom', labels: { color: ink, usePointStyle: true, pointStyle: 'circle', boxWidth: 7, boxHeight: 7, font: { size: 9.5 } } } },
    scales: {
        x: { ticks: { color: ink, font: { size: 9.5 } }, grid: { display: false } },
        y: { ticks: { color: ink, font: { size: 9.5 }, callback: v => rpShort(v) }, grid: { color: grid }, beginAtZero: true },
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
            { label: 'Casual', data: trend.map(t => t.casual), backgroundColor: C.blue,  ...barStyle, stack: 's' },
            { label: 'Member', data: trend.map(t => t.member), backgroundColor: C.amber, ...barStyle, stack: 's' },
        ],
    },
    options: { ...baseOpts, scales: { ...baseOpts.scales,
        x: { ...baseOpts.scales.x, stacked: true }, y: { ...baseOpts.scales.y, stacked: true } } },
});

const daily = <?= json_encode(array_map(fn($d) => ['l' => (int)substr($d['tanggal'], 8), 'v' => (int)$d['total']], $daily)) ?>;
new Chart(document.getElementById('chartDaily'), {
    type: 'bar',
    data: { labels: daily.map(d => String(d.l).padStart(2, '0')),
        datasets: [{ label: 'Pendapatan', data: daily.map(d => d.v), backgroundColor: C.green, ...barStyle }] },
    options: { ...baseOpts, plugins: { legend: { display: false } }, scales: { ...baseOpts.scales,
        x: { ...baseOpts.scales.x, ticks: { ...baseOpts.scales.x.ticks, autoSkip: true, maxTicksLimit: 16 } } } },
});
</script>

</body>
</html>
