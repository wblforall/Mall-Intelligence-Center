<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan Bulanan Traffic Kendaraan Parkir — <?= $bulan ?></title>
<?= view('_laporan/_style') ?>
<style>
/* Lokal laporan parkir — kandidat naik ke gaya bersama: th.num, .bilah, .penutup. */
.main-table th.num { text-align: right; }
.chart-wrap { height: 215px; }
.bilah { display: inline-block; width: 52px; height: 5px; margin-right: 7px; border-radius: 3px; background: var(--garis-halus); overflow: hidden; vertical-align: 1px; }
.bilah > i { display: block; height: 100%; border-radius: 3px; background: var(--navy-3); }
.deret-angka small { display: block; font-size: 8.5px; color: var(--redup2); font-weight: 400; }
.kpi-num .satuan { font-size: 10px; font-weight: 600; color: var(--redup); margin-left: 3px; letter-spacing: 0; }
.main-table td.gate { font-weight: 600; color: var(--tinta); }
.main-table td.pemisah, .main-table th.pemisah { border-left: 1px solid var(--garis); }
.main-table th.pemisah { border-left-color: rgba(255,255,255,.2); }
/* Rekap harian bersambung ke blok penutup (baris terakhir + TOTAL + tanda tangan + kaki)
   supaya tanda tangan tidak pernah terdampar sendirian di halaman baru. */
.penutup { break-inside: avoid; page-break-inside: avoid; }
.main-table.harian { table-layout: fixed; }
.main-table.bersambung { margin-bottom: 0; }
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
$n    = fn($v) => $v > 0 ? $nf($v) : '—';
$tgl  = fn($d) => date('d', strtotime($d)) . ' ' . $blnPendek[(int) date('n', strtotime($d))] . ' ' . date('Y', strtotime($d));
$deltaHtml = function (?float $p) use ($pct) {
    if ($p === null) return '';
    $cls = $p >= 0 ? 'delta-up' : 'delta-down';
    return '<span class="' . $cls . '">' . ($p >= 0 ? '▲' : '▼') . ' ' . $pct(abs($p)) . '</span>';
};
$bilah = fn($p) => '<span class="bilah"><i style="width:' . max(0, min(100, round($p, 1))) . '%"></i></span>';
$typeLabel = ['mobil'=>'Mobil','motor'=>'Motor','box'=>'Mobil Box','truck'=>'Truck','taxi'=>'Taxi','bus'=>'Bus'];
$durLabel  = ['le1'=>'≤ 1 jam','h1_2'=>'1–2 jam','h2_3'=>'2–3 jam','h3_4'=>'3–4 jam','h4_5'=>'4–5 jam','h5_6'=>'5–6 jam','h6_7'=>'6–7 jam','gt7'=>'> 7 jam'];
$dowShort  = [1=>'Sen',2=>'Sel',3=>'Rab',4=>'Kam',5=>'Jum',6=>'Sab',7=>'Min'];
$durTotal  = array_sum($duration);

// Rekap harian: hanya hari berdata; langganan per hari; jumlah per kolom untuk baris TOTAL.
$dailyRows = [];
$dailySum  = array_fill_keys(array_merge($types, ['free', 'total']), 0);
foreach ($daily as $d) {
    if ((int) $d['total'] === 0) continue;
    $d['_free'] = 0;
    foreach ($types as $t) { $d['_free'] += min((int) ($d[$t . '_free'] ?? 0), (int) ($d[$t] ?? 0)); }
    foreach ($types as $t) { $dailySum[$t] += (int) $d[$t]; }
    $dailySum['free']  += $d['_free'];
    $dailySum['total'] += (int) $d['total'];
    $dailyRows[] = $d;
}
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
        <div class="kpi-num"><?= $nf($grand) ?></div>
        <div class="kpi-sub"><?php if ($prevTotal > 0): ?><?= $deltaHtml($changePct) ?> vs <?= $prevLabel ?> &middot; <?= $nf($prevTotal) ?><?php else: ?>tidak ada data <?= $prevLabel ?><?php endif; ?></div>
    </div>
    <div class="kpi-box kpi-green">
        <div class="kpi-label">Mobil</div>
        <div class="kpi-num"><?= $nf($byType['mobil']) ?></div>
        <div class="kpi-sub"><?= $grand > 0 ? $pct($byType['mobil'] / $grand * 100) : '0%' ?> dari total &middot; bulan lalu <?= $n($prevByType['mobil']) ?></div>
    </div>
    <div class="kpi-box kpi-amber">
        <div class="kpi-label">Motor</div>
        <div class="kpi-num"><?= $nf($byType['motor']) ?></div>
        <div class="kpi-sub"><?= $grand > 0 ? $pct($byType['motor'] / $grand * 100) : '0%' ?> dari total &middot; bulan lalu <?= $n($prevByType['motor']) ?></div>
    </div>
    <div class="kpi-box kpi-purple">
        <div class="kpi-label">Rata-rata / Hari</div>
        <div class="kpi-num"><?= $nf($avgDaily) ?><span class="satuan">kendaraan</span></div>
        <div class="kpi-sub"><?= $avgChangePct !== null ? $deltaHtml($avgChangePct) . ' vs bulan lalu &middot; ' : '' ?><?= count($dailyRows) ?> hari berdata</div>
    </div>
</div>

<div class="deret-angka">
    <div><span>Langganan / Pass</span><b><?= $nf($freeTot) ?></b>
        <small><?= $grand > 0 ? $pct($freeTot / $grand * 100) . ' dari total' : '—' ?></small></div>
    <div><span>Kendaraan Bayar</span><b><?= $nf($grand - $freeTot) ?></b>
        <small><?= $grand > 0 ? $pct(($grand - $freeTot) / $grand * 100) . ' dari total' : '—' ?></small></div>
    <div><span>Hari Teramai</span><b><?= $peakDay ? $tgl($peakDay) : '—' ?></b>
        <small><?= $peakVal ? $nf($peakVal) . ' kendaraan' : 'belum ada data' ?></small></div>
    <div><span>Rata² Weekday</span><b><?= $nf($wd['avg']) ?></b>
        <small>Sen–Kam &middot; <?= $wd['days'] ?> hari</small></div>
    <div><span>Rata² Weekend</span><b><?= $nf($we['avg']) ?></b>
        <small>Jum–Min &middot; <?= $we['days'] ?> hari<?= $wd['avg'] > 0 && $we['avg'] > 0 ? ' &middot; ' . $pct($we['avg'] / $wd['avg'] * 100) . ' dari weekday' : '' ?></small></div>
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
        <div class="chart-title">Kendaraan Harian — <?= $bulanLabel ?> <span class="subnote">· emas = weekend</span></div>
        <div class="chart-wrap"><canvas id="chartDaily"></canvas></div>
    </div>
</div>

<!-- ══ PER JENIS + DURASI ══ (dua baris .duo supaya nomor bagian urut kiri→kanan) -->
<div class="duo">
<div>
    <div class="sec-title"><span>Kendaraan per Jenis</span><span class="sec-sub">bayar vs langganan &middot; dibanding <?= $prevLabel ?></span></div>
    <table class="main-table">
    <thead><tr><th>Jenis</th><th class="num">Bayar</th><th class="num">Langganan</th><th class="num">Total</th><th class="num"><?= $prevLabel ?></th><th class="text-center">Δ</th></tr></thead>
    <tbody>
    <?php foreach ($types as $t):
        $now = $byType[$t]; $prev = $prevByType[$t];
        if ($now === 0 && $prev === 0) continue;
        $d = $prev > 0 ? round(($now - $prev) / $prev * 100, 1) : null;
    ?>
        <tr><td><strong><?= $typeLabel[$t] ?? ucfirst($t) ?></strong></td>
            <td class="<?= $paid[$t] ? 'num' : 'zero' ?>"><?= $n($paid[$t]) ?></td>
            <td class="<?= $free[$t] ? 'num' : 'zero' ?>"><?= $n($free[$t]) ?></td>
            <td class="<?= $now ? 'num' : 'zero' ?>"><strong><?= $n($now) ?></strong></td>
            <td class="<?= $prev ? 'num' : 'zero' ?>"><?= $n($prev) ?></td>
            <td class="text-center"><?= $d !== null ? $deltaHtml($d) : '—' ?></td></tr>
    <?php endforeach; ?>
    <?php if ($grand === 0 && $prevTotal === 0): ?>
        <tr class="empty-row"><td colspan="6">Belum ada data kendaraan per jenis.</td></tr>
    <?php endif; ?>
    </tbody>
    <?php if ($grand > 0 || $prevTotal > 0): ?>
    <tfoot>
        <tr><td>TOTAL</td>
            <td class="num"><?= $n($grand - $freeTot) ?></td>
            <td class="num"><?= $n($freeTot) ?></td>
            <td class="num"><?= $n($grand) ?></td>
            <td class="num"><?= $n($prevTotal) ?></td>
            <td class="text-center"><?= $deltaHtml($changePct) ?: '—' ?></td></tr>
    </tfoot>
    <?php endif; ?>
    </table>
</div>
<div>
    <div class="sec-title"><span>Distribusi Lama Parkir</span><span class="sec-sub">akumulasi <?= $bulanLabel ?></span></div>
    <table class="main-table">
    <thead><tr><th>Durasi</th><th class="num">Kendaraan</th><th class="num">Share</th></tr></thead>
    <tbody>
    <?php if ($durTotal === 0): ?>
        <tr class="empty-row"><td colspan="3">Belum ada data durasi parkir bulan ini.</td></tr>
    <?php endif; ?>
    <?php foreach ($duration as $k => $v): if ($durTotal === 0) break; $sh = $v / $durTotal * 100; ?>
        <tr><td><?= $durLabel[$k] ?></td>
            <td class="<?= $v ? 'num' : 'zero' ?>"><?= $n($v) ?></td>
            <td class="num"><?= $bilah($sh) ?><?= $pct($sh) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
    <?php if ($durTotal > 0): ?>
    <tfoot><tr><td>TOTAL</td><td class="num"><?= $n($durTotal) ?></td><td class="num">100%</td></tr></tfoot>
    <?php endif; ?>
    </table>
</div>
</div>

<!-- ══ GATE + WEEKDAY/WEEKEND ══ -->
<div class="duo">
<div>
    <div class="sec-title"><span>Gate Tersibuk</span><span class="sec-sub">8 teratas &middot; akumulasi <?= $bulanLabel ?></span></div>
    <table class="main-table">
    <thead><tr><th>Gate Masuk</th><th class="num">Kendaraan</th><th class="pemisah">Gate Keluar</th><th class="num">Kendaraan</th></tr></thead>
    <tbody>
    <?php $rows = max(count($gateMasuk), count($gateKeluar));
    if ($rows === 0): ?>
        <tr class="empty-row"><td colspan="4">Belum ada data gate bulan ini.</td></tr>
    <?php endif;
    for ($i = 0; $i < $rows; $i++): $gm = $gateMasuk[$i] ?? null; $gk = $gateKeluar[$i] ?? null; ?>
        <tr>
            <td class="gate"><?= $gm ? esc($gm['gate']) : '' ?></td>
            <td class="num"><?= $gm ? $n((int)$gm['total']) : '' ?></td>
            <td class="gate pemisah"><?= $gk ? esc($gk['gate']) : '' ?></td>
            <td class="num"><?= $gk ? $n((int)$gk['total']) : '' ?></td>
        </tr>
    <?php endfor; ?>
    </tbody>
    </table>
</div>
<div>
    <div class="sec-title"><span>Weekday vs Weekend</span><span class="sec-sub">rata-rata per hari berdata</span></div>
    <table class="main-table">
    <thead><tr><th>Kelompok Hari</th><th class="num">Hari</th><th class="num">Total</th><th class="num">Rata²/hari</th></tr></thead>
    <tbody>
        <tr><td><strong>Weekday</strong> <span class="subnote">Sen–Kam</span></td>
            <td class="num"><?= $wd['days'] ?></td>
            <td class="<?= $wd['total'] ? 'num' : 'zero' ?>"><?= $n($wd['total']) ?></td>
            <td class="<?= $wd['avg'] ? 'num' : 'zero' ?>"><?= $n($wd['avg']) ?></td></tr>
        <tr><td><strong>Weekend</strong> <span class="subnote">Jum–Min</span></td>
            <td class="num"><?= $we['days'] ?></td>
            <td class="<?= $we['total'] ? 'num' : 'zero' ?>"><?= $n($we['total']) ?></td>
            <td class="<?= $we['avg'] ? 'num' : 'zero' ?>"><?= $n($we['avg']) ?></td></tr>
    </tbody>
    </table>
</div>
</div>

<!-- ══ REKAP HARIAN ══ -->
<?php
// Beberapa baris terakhir + TOTAL masuk tabel sambungan di blok penutup bersama tanda tangan.
// Jumlah baris sambungan dipilih agar tabel pertama berbaris genap (zebra tetap bersambung).
$nRows   = count($dailyRows);
$nEkor   = $nRows <= 3 ? $nRows : (($nRows - 3) % 2 === 0 ? 3 : 2);
$rowsA   = array_slice($dailyRows, 0, $nRows - $nEkor);
$rowsB   = array_slice($dailyRows, $nRows - $nEkor);
$nKolom  = count($types) + 3;
$colgroup = '<colgroup><col style="width:11%">' . str_repeat('<col>', count($types) + 2) . '</colgroup>';
$thead = '<thead><tr><th>Tanggal</th>'
       . implode('', array_map(fn($t) => '<th class="num">' . ($t === 'box' ? 'Box' : ($typeLabel[$t] ?? ucfirst($t))) . '</th>', $types))
       . '<th class="num">Langganan</th><th class="num">Total</th></tr></thead>';
$barisHarian = function (array $rows) use ($types, $n, $dowShort) {
    foreach ($rows as $d) {
        $dow = (int) date('N', strtotime($d['tanggal']));
        echo '<tr class="' . ($dow >= 5 ? 'we-row' : '') . '"><td>' . date('d/m', strtotime($d['tanggal']))
           . ' <span class="subnote">' . $dowShort[$dow] . '</span></td>';
        foreach ($types as $t) {
            echo '<td class="' . ((int) $d[$t] ? 'num' : 'zero') . '">' . $n((int) $d[$t]) . '</td>';
        }
        echo '<td class="' . ($d['_free'] ? 'num' : 'zero') . '">' . $n($d['_free']) . '</td>';
        echo '<td class="num"><strong>' . $n((int) $d['total']) . '</strong></td></tr>';
    }
};
?>
<div class="sec-title"><span>Rekap Harian — <?= $bulanLabel ?></span>
    <span class="sec-sub">jumlah kendaraan masuk &middot; baris kuning = weekend (Jum–Min)</span></div>
<?php if (! $dailyRows): ?>
<table class="main-table harian"><?= $colgroup . $thead ?>
<tbody><tr class="empty-row"><td colspan="<?= $nKolom ?>">Belum ada data kendaraan harian dari SPI untuk <?= $bulanLabel ?>.</td></tr></tbody>
</table>
<?php elseif ($rowsA): ?>
<table class="main-table harian bersambung"><?= $colgroup . $thead ?><tbody><?php $barisHarian($rowsA); ?></tbody></table>
<?php endif; ?>

<div class="penutup">
<?php if ($dailyRows): ?>
<table class="main-table harian"><?= $colgroup . ($rowsA ? '' : $thead) ?>
<tbody><?php $barisHarian($rowsB); ?></tbody>
<tfoot>
    <tr><td>TOTAL <span class="subnote"><?= $nRows ?> hari</span></td>
        <?php foreach ($types as $t): ?>
        <td class="num"><?= $n($dailySum[$t]) ?></td>
        <?php endforeach; ?>
        <td class="num"><?= $n($dailySum['free']) ?></td>
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
const C = { blue: '#2a78d6', amber: '#eda100', green: '#1baf7a' };
const ink  = 'rgba(51,65,85,.75)';
const grid = 'rgba(0,0,0,.06)';
Chart.defaults.animation = false;
Chart.defaults.devicePixelRatio = 2;
Chart.defaults.font.family = "'Inter', Arial, sans-serif";

// Format Indonesia: koma desimal, jt = juta, rb = ribu.
const dec1 = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 1 });
const nShort = v => v >= 1e6 ? dec1.format(v/1e6) + ' jt' : v >= 1e3 ? Math.round(v/1e3) + ' rb' : dec1.format(v);
const nLabel = v => v >= 1e6 ? dec1.format(v/1e6) + ' jt' : v >= 1e3 ? Math.round(v/1e3) + ' rb' : dec1.format(v);

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
// Angka di atas tiap batang (grafik tren — hanya 12 batang, masih lapang).
const angkaAtas = {
    id: 'angkaAtas',
    afterDatasetsDraw(chart) {
        const { ctx } = chart;
        ctx.save();
        ctx.fillStyle = '#334155'; ctx.font = "600 7.5px 'Inter', Arial, sans-serif";
        ctx.textAlign = 'center'; ctx.textBaseline = 'bottom';
        chart.data.datasets.forEach((ds, i) => {
            chart.getDatasetMeta(i).data.forEach((bar, j) => {
                const v = +ds.data[j] || 0;
                if (v > 0) ctx.fillText(nLabel(v), bar.x, bar.y - 2);
            });
        });
        ctx.restore();
    },
};

const baseOpts = {
    responsive: true, maintainAspectRatio: false,
    layout: { padding: { top: 12 } },
    plugins: { legend: { position: 'bottom', labels: { color: ink, usePointStyle: true, pointStyle: 'circle', boxWidth: 7, boxHeight: 7, font: { size: 9.5 } } } },
    scales: {
        x: { ticks: { color: ink, font: { size: 9.5 } }, grid: { display: false } },
        y: { ticks: { color: ink, font: { size: 9.5 }, callback: v => nShort(v) }, grid: { color: grid }, beginAtZero: true, suggestedMax: 10 },
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
    plugins: [angkaAtas, kosong],
});

// Batang weekend (Jum–Min) diberi warna emas, selaras baris kuning di Rekap Harian.
const daily = <?= json_encode(array_map(fn($d) => ['l' => (int)substr($d['tanggal'], 8), 'v' => (int)$d['total'], 'we' => (int)date('N', strtotime($d['tanggal'])) >= 5], $daily)) ?>;
new Chart(document.getElementById('chartDaily'), {
    type: 'bar',
    data: { labels: daily.map(d => String(d.l).padStart(2, '0')),
        datasets: [{ label: 'Kendaraan', data: daily.map(d => d.v), backgroundColor: daily.map(d => d.we ? C.amber : C.green), ...barStyle }] },
    options: { ...baseOpts, layout: { padding: { top: 4 } },
        plugins: { legend: { display: false } }, scales: { ...baseOpts.scales,
        x: { ...baseOpts.scales.x, ticks: { ...baseOpts.scales.x.ticks, autoSkip: true, maxTicksLimit: 16 } } } },
    plugins: [kosong],
});
</script>

</body>
</html>
