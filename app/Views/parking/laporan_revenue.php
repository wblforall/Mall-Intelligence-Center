<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan Bulanan Pendapatan Parkir — <?= $bulan ?></title>
<?= view('_laporan/_style') ?>
<style>
/* Lokal laporan parkir — kandidat naik ke gaya bersama: th.num, .kpi-num .rp, .bilah. */
.main-table th.num { text-align: right; }
.main-table td.text-center .delta-up, .main-table td.text-center .delta-down { vertical-align: 0; }
.chart-wrap { height: 215px; }
.rp { font-style: normal; }
.kpi-num .rp { font-size: 11px; font-weight: 700; color: var(--redup); margin-right: 3px; letter-spacing: 0; }
.kpi-num.tgl { font-size: 17px; }
.bilah { display: inline-block; width: 52px; height: 5px; margin-right: 7px; border-radius: 3px; background: var(--garis-halus); overflow: hidden; vertical-align: 1px; }
.bilah > i { display: block; height: 100%; border-radius: 3px; background: var(--navy-3); }
.deret-angka b .rp { display: inline; font-size: 9.5px; font-weight: 600; color: var(--redup); margin-right: 2px; }
.deret-angka small { display: block; font-size: 8.5px; color: var(--redup2); font-weight: 400; }
/* Tanda tangan + kaki dokumen satu blok, dan jangan terdampar sendirian di halaman baru. */
.penutup { break-inside: avoid; page-break-inside: avoid; }
.main-table.harian { table-layout: fixed; }
.main-table.bersambung { margin-bottom: 0; }
.penutup .sambungan { margin-top: 0; }
</style>
</head>
<body>

<button class="btn-print no-print" onclick="window.print()">&#128438; Cetak</button>

<?php
$idBulan = ['January'=>'Januari','February'=>'Februari','March'=>'Maret','April'=>'April',
            'May'=>'Mei','June'=>'Juni','July'=>'Juli','August'=>'Agustus',
            'September'=>'September','October'=>'Oktober','November'=>'November','December'=>'Desember'];
$blnPendek  = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
$fmtBulan   = fn($m) => strtr(\DateTime::createFromFormat('Y-m', $m)->format('F Y'), $idBulan);
$bulanLabel = $fmtBulan($bulan);
$prevLabel  = $fmtBulan($prevBulan);
$nf   = fn($v) => number_format((float) $v, 0, ',', '.');
$pct  = fn($v) => rtrim(rtrim(number_format((float) $v, 1, ',', '.'), '0'), ',') . '%';
$rp   = fn($v) => $v > 0 ? 'Rp ' . $nf($v) : '—';
$rpK  = fn($v) => '<i class="rp">Rp</i>' . $nf($v);          // angka besar di kartu
$n    = fn($v) => $v > 0 ? $nf($v) : '—';
$tgl  = fn($d) => date('d', strtotime($d)) . ' ' . $blnPendek[(int) date('n', strtotime($d))] . ' ' . date('Y', strtotime($d));
$deltaHtml = function (?float $p) use ($pct) {
    if ($p === null) return '';
    $cls = $p >= 0 ? 'delta-up' : 'delta-down';
    return '<span class="' . $cls . '">' . ($p >= 0 ? '▲' : '▼') . ' ' . $pct(abs($p)) . '</span>';
};
$bilah = fn($p) => '<span class="bilah"><i style="width:' . max(0, min(100, round($p, 1))) . '%"></i></span>';
$typeLabel = ['mobil'=>'Mobil','motor'=>'Motor','box'=>'Mobil Box','truck'=>'Truck','taxi'=>'Taxi','bus'=>'Bus'];
$dowShort  = [1=>'Sen',2=>'Sel',3=>'Rab',4=>'Kam',5=>'Jum',6=>'Sab',7=>'Min'];
$lainnya   = $total - $byType['mobil'] - $byType['motor'];
$cmTotal   = $kpiCasual + $kpiMember;

// Rekap harian: hanya hari berdata, plus jumlah per kolom untuk baris total.
$dailyRows = array_values(array_filter($daily, fn($d) => (int) $d['total'] > 0));
$dailySum  = array_fill_keys(array_merge($types, ['total']), 0);
foreach ($dailyRows as $d) { foreach ($dailySum as $k => $_) { $dailySum[$k] += (int) $d[$k]; } }
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
        <div class="kpi-num"><?= $rpK($total) ?></div>
        <div class="kpi-sub"><?php if ($prevTotal > 0): ?><?= $deltaHtml($changePct) ?> vs <?= $prevLabel ?> &middot; <?= $rp($prevTotal) ?><?php else: ?>tidak ada data <?= $prevLabel ?><?php endif; ?></div>
    </div>
    <div class="kpi-box kpi-green">
        <div class="kpi-label">Pendapatan Mobil</div>
        <div class="kpi-num"><?= $rpK($byType['mobil']) ?></div>
        <div class="kpi-sub"><?= $total > 0 ? $pct($byType['mobil'] / $total * 100) : '0%' ?> dari total &middot; bulan lalu <?= $rp($prevByType['mobil']) ?></div>
    </div>
    <div class="kpi-box kpi-amber">
        <div class="kpi-label">Pendapatan Motor</div>
        <div class="kpi-num"><?= $rpK($byType['motor']) ?></div>
        <div class="kpi-sub"><?= $total > 0 ? $pct($byType['motor'] / $total * 100) : '0%' ?> dari total &middot; bulan lalu <?= $rp($prevByType['motor']) ?></div>
    </div>
    <div class="kpi-box kpi-purple">
        <div class="kpi-label">Rata-rata / Hari</div>
        <div class="kpi-num"><?= $rpK($avgDaily) ?></div>
        <div class="kpi-sub"><?= $avgChangePct !== null ? $deltaHtml($avgChangePct) . ' vs bulan lalu &middot; ' : '' ?><?= count($dailyRows) ?> hari berdata</div>
    </div>
</div>

<div class="deret-angka">
    <div><span>Casual</span><b><?= $rpK($kpiCasual) ?></b>
        <small><?= $cmTotal > 0 ? $pct($kpiCasual / $cmTotal * 100) . ' dari casual + member' : '—' ?></small></div>
    <div><span>Member / Langganan</span><b><?= $rpK($kpiMember) ?></b>
        <small><?= $cmTotal > 0 ? $pct($kpiMember / $cmTotal * 100) . ' dari casual + member' : '—' ?></small></div>
    <div><span>Hari Tertinggi</span><b><?= $maxDay ? $tgl($maxDay) : '—' ?></b>
        <small><?= $maxVal ? $rp($maxVal) : 'belum ada data' ?></small></div>
    <div><span>Kendaraan Lain</span><b><?= $rpK($lainnya) ?></b>
        <small>Mobil Box, Truck, Taxi, Bus</small></div>
    <div><span>Total <?= $prevLabel ?></span><b><?= $rpK($prevTotal) ?></b>
        <small>pembanding bulan lalu</small></div>
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
        <div class="chart-title">Pendapatan Harian — <?= $bulanLabel ?> (Rp) <span class="subnote">· emas = weekend</span></div>
        <div class="chart-wrap"><canvas id="chartDaily"></canvas></div>
    </div>
</div>

<!-- ══ PER JENIS + METODE PEMBAYARAN ══ -->
<div class="duo">
<div>
    <div class="sec-title"><span>Pendapatan per Jenis Kendaraan</span><span class="sec-sub">dibanding <?= $prevLabel ?></span></div>
    <table class="main-table">
    <thead><tr><th>Jenis</th><th class="num"><?= $bulanLabel ?></th><th class="num"><?= $prevLabel ?></th><th class="text-center">Δ</th><th class="num">Share</th></tr></thead>
    <tbody>
    <?php foreach ($types as $t):
        $now = $byType[$t]; $prev = $prevByType[$t];
        if ($now === 0 && $prev === 0) continue;
        $d = $prev > 0 ? round(($now - $prev) / $prev * 100, 1) : null;
        $sh = $total > 0 ? $now / $total * 100 : 0;
    ?>
        <tr><td><strong><?= $typeLabel[$t] ?? ucfirst($t) ?></strong></td>
            <td class="<?= $now ? 'num' : 'zero' ?>"><?= $rp($now) ?></td>
            <td class="<?= $prev ? 'num' : 'zero' ?>"><?= $rp($prev) ?></td>
            <td class="text-center"><?= $d !== null ? $deltaHtml($d) : '—' ?></td>
            <td class="num"><?= $bilah($sh) ?><?= $pct($sh) ?></td></tr>
    <?php endforeach; ?>
    <?php if ($total === 0 && $prevTotal === 0): ?>
        <tr class="empty-row"><td colspan="5">Belum ada data pendapatan per jenis kendaraan.</td></tr>
    <?php endif; ?>
    </tbody>
    <?php if ($total > 0 || $prevTotal > 0): ?>
    <tfoot>
        <tr><td>TOTAL</td>
            <td class="num"><?= $rp($total) ?></td>
            <td class="num"><?= $rp($prevTotal) ?></td>
            <td class="text-center"><?= $deltaHtml($changePct) ?: '—' ?></td>
            <td class="num"><?= $total > 0 ? '100%' : '—' ?></td></tr>
    </tfoot>
    <?php endif; ?>
    </table>
</div>
<div>
    <div class="sec-title"><span>Metode Pembayaran</span><span class="sec-sub">akumulasi <?= $bulanLabel ?></span></div>
    <table class="main-table">
    <thead><tr><th>Metode</th><th class="num">Nilai</th><th class="num">Share</th></tr></thead>
    <tbody>
    <?php if (empty($payments)): ?>
        <tr class="empty-row"><td colspan="3">Belum ada data metode pembayaran bulan ini.</td></tr>
    <?php endif; ?>
    <?php foreach ($payments as $p): $sh = $payTotal > 0 ? (int) $p['total'] / $payTotal * 100 : 0; ?>
        <tr><td><strong><?= esc($p['method']) ?></strong></td>
            <td class="num"><?= $rp((int)$p['total']) ?></td>
            <td class="num"><?= $bilah($sh) ?><?= $pct($sh) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
    <?php if (! empty($payments)): ?>
    <tfoot>
        <tr><td>TOTAL</td><td class="num"><?= $rp($payTotal) ?></td><td class="num">100%</td></tr>
    </tfoot>
    <?php endif; ?>
    </table>
</div>
</div>

<!-- ══ REKAP HARIAN ══ -->
<?php
// Beberapa baris terakhir + TOTAL ditaruh di tabel sambungan di dalam blok penutup bersama
// tanda tangan, supaya tanda tangan tidak pernah terdampar sendirian di halaman baru.
// Jumlah baris sambungan dipilih agar tabel pertama berbaris genap (zebra tetap bersambung).
$nRows   = count($dailyRows);
$nEkor   = $nRows <= 3 ? $nRows : (($nRows - 3) % 2 === 0 ? 3 : 2);
$rowsA   = array_slice($dailyRows, 0, $nRows - $nEkor);
$rowsB   = array_slice($dailyRows, $nRows - $nEkor);
$colgroup = '<colgroup><col style="width:12%">' . str_repeat('<col>', count($types) + 1) . '</colgroup>';
$thead = '<thead><tr><th>Tanggal</th>'
       . implode('', array_map(fn($t) => '<th class="num">' . ($t === 'box' ? 'Box' : ($typeLabel[$t] ?? ucfirst($t))) . '</th>', $types))
       . '<th class="num">Total</th></tr></thead>';
$barisHarian = function (array $rows) use ($types, $n, $dowShort) {
    foreach ($rows as $d) {
        $dow = (int) date('N', strtotime($d['tanggal']));
        echo '<tr class="' . ($dow >= 5 ? 'we-row' : '') . '"><td>' . date('d/m', strtotime($d['tanggal']))
           . ' <span class="subnote">' . $dowShort[$dow] . '</span></td>';
        foreach ($types as $t) {
            echo '<td class="' . ((int) $d[$t] ? 'num' : 'zero') . '">' . $n((int) $d[$t]) . '</td>';
        }
        echo '<td class="num"><strong>' . $n((int) $d['total']) . '</strong></td></tr>';
    }
};
?>
<div class="sec-title"><span>Rekap Harian — <?= $bulanLabel ?></span>
    <span class="sec-sub">nilai dalam Rupiah &middot; baris kuning = weekend (Jum–Min)</span></div>
<?php if (! $dailyRows): ?>
<table class="main-table harian"><?= $colgroup . $thead ?>
<tbody><tr class="empty-row"><td colspan="<?= count($types) + 2 ?>">Belum ada data pendapatan harian dari SPI untuk <?= $bulanLabel ?>.</td></tr></tbody>
</table>
<?php elseif ($rowsA): ?>
<table class="main-table harian bersambung"><?= $colgroup . $thead ?><tbody><?php $barisHarian($rowsA); ?></tbody></table>
<?php endif; ?>

<div class="penutup">
<?php if ($dailyRows): ?>
<table class="main-table harian<?= $rowsA ? ' sambungan' : '' ?>"><?= $colgroup . ($rowsA ? '' : $thead) ?>
<tbody><?php $barisHarian($rowsB); ?></tbody>
<tfoot>
    <tr><td>TOTAL <span class="subnote"><?= $nRows ?> hari</span></td>
        <?php foreach ($types as $t): ?>
        <td class="num"><?= $n($dailySum[$t]) ?></td>
        <?php endforeach; ?>
        <td class="num"><?= $n($dailySum['total']) ?></td></tr>
</tfoot>
</table>
<?php endif; ?>
<?= view('_laporan/_ttd', ['signatories' => $signatories]) ?>

<div class="doc-footer">
    <span>Mall Intelligence Center &mdash; IT Department PT. Wulandari Bangun Laksana Tbk.</span>
    <span>Digenerate otomatis dari SPI &mdash; <?= $printedAt ?></span>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Palet tervalidasi CVD (surface terang)
const C = { blue: '#2a78d6', green: '#1baf7a', amber: '#eda100', navy: '#0f2a4f' };
const ink  = 'rgba(51,65,85,.75)';
const grid = 'rgba(0,0,0,.06)';
Chart.defaults.animation = false;
Chart.defaults.devicePixelRatio = 2;
Chart.defaults.font.family = "'Inter', Arial, sans-serif";

// Format Indonesia: koma desimal, M = miliar, jt = juta, rb = ribu.
const dec1 = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 1 });
const dec2 = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 });
const rpShort = v => v >= 1e9 ? dec1.format(v/1e9) + ' M' : v >= 1e6 ? Math.round(v/1e6) + ' jt' : v >= 1e3 ? Math.round(v/1e3) + ' rb' : v;
const rpLabel = v => v >= 1e9 ? dec2.format(v/1e9) + ' M' : v >= 1e6 ? dec1.format(v/1e6) + ' jt' : dec1.format(v);

// Teks "Belum ada data" di tengah grafik bila semua nilai nol.
const kosong = {
    id: 'kosong',
    afterDraw(chart) {
        const ada = chart.data.datasets.some(ds => ds.data.some(v => v > 0));
        if (ada) return;
        const { ctx, chartArea: a } = chart;
        ctx.save();
        ctx.fillStyle = '#94a3b8'; ctx.font = "italic 10px 'Inter', Arial, sans-serif";
        ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
        ctx.fillText('Belum ada data', (a.left + a.right) / 2, (a.top + a.bottom) / 2);
        ctx.restore();
    },
};
// Angka total di atas batang bertumpuk.
const totalAtas = {
    id: 'totalAtas',
    afterDatasetsDraw(chart) {
        const { ctx } = chart;
        const metas = chart.data.datasets.map((_, i) => chart.getDatasetMeta(i));
        ctx.save();
        ctx.fillStyle = '#0f172a'; ctx.font = "600 8.5px 'Inter', Arial, sans-serif";
        ctx.textAlign = 'center'; ctx.textBaseline = 'bottom';
        chart.data.labels.forEach((_, j) => {
            const sum = chart.data.datasets.reduce((s, ds) => s + (+ds.data[j] || 0), 0);
            if (sum <= 0) return;
            const top = Math.min(...metas.map(m => m.data[j].y));
            ctx.fillText(rpLabel(sum), metas[0].data[j].x, top - 3);
        });
        ctx.restore();
    },
};

const baseOpts = {
    responsive: true, maintainAspectRatio: false,
    layout: { padding: { top: 14 } },
    plugins: { legend: { position: 'bottom', labels: { color: ink, usePointStyle: true, pointStyle: 'circle', boxWidth: 7, boxHeight: 7, font: { size: 9.5 } } } },
    scales: {
        x: { ticks: { color: ink, font: { size: 9.5 } }, grid: { display: false } },
        y: { ticks: { color: ink, font: { size: 9.5 }, callback: v => rpShort(v) }, grid: { color: grid }, beginAtZero: true, suggestedMax: 1e6 },
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
    plugins: [totalAtas, kosong],
});

// Batang weekend (Jum–Min) diberi warna emas, selaras baris kuning di Rekap Harian.
const daily = <?= json_encode(array_map(fn($d) => ['l' => (int)substr($d['tanggal'], 8), 'v' => (int)$d['total'], 'we' => (int)date('N', strtotime($d['tanggal'])) >= 5], $daily)) ?>;
new Chart(document.getElementById('chartDaily'), {
    type: 'bar',
    data: { labels: daily.map(d => String(d.l).padStart(2, '0')),
        datasets: [{ label: 'Pendapatan', data: daily.map(d => d.v), backgroundColor: daily.map(d => d.we ? C.amber : C.green), ...barStyle }] },
    options: { ...baseOpts, layout: { padding: { top: 4 } },
        plugins: { legend: { display: false } }, scales: { ...baseOpts.scales,
        x: { ...baseOpts.scales.x, ticks: { ...baseOpts.scales.x.ticks, autoSkip: true, maxTicksLimit: 16 } } } },
    plugins: [kosong],
});
</script>

</body>
</html>
