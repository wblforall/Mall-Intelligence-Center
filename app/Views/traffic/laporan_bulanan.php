<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan Bulanan Traffic — <?= $bulan ?></title>
<?= $this->include('_laporan/_style') ?>
<style>
/* Khusus laporan Traffic: warna KPI per mall & blok per-program */
.kpi-ewalk { border-color:#bfdbfe; background:#eff6ff; } .kpi-ewalk .kpi-num { color:#2a78d6; }
.kpi-penta { border-color:#bbf7d0; background:#f0fdf4; } .kpi-penta .kpi-num { color:#15803d; }
.kpi-total { border-color:#bfdbfe; background:#eff6ff; } .kpi-total .kpi-num { color:#1d4ed8; }
.kpi-avg   { border-color:#fde68a; background:#fffbeb; } .kpi-avg   .kpi-num { color:#b45309; }
tbody.prog-block { break-inside: avoid; page-break-inside: avoid; }
/* Kepala kolom angka rata kanan, sejajar dengan isinya. */
.main-table th.num { text-align: right; }
/* KPI berisi teks (tanggal), bukan angka besar. */
.kpi-num.kpi-teks { font-size: 15px; padding-top: 3px; letter-spacing: -.1px; }
/* Grafik halaman 1 sedikit lebih tinggi — ruangnya tersedia. */
.chart-wrap { height: 205px; }
.nowrap { white-space: nowrap; }
/* Pesan pengganti grafik saat tidak ada data. */
.grafik-kosong {
    position: absolute; inset: 0 0 26px 0; display: flex; align-items: center; justify-content: center; text-align: center;
    padding: 0 16px; font-size: 10px; font-style: italic; color: var(--redup2); pointer-events: none;
}
.grafik-kosong span { padding: 5px 12px; border-radius: 6px; background: rgba(251,252,254,.92); border: 1px dashed var(--garis); }
/* Blok dua kolom (per pintu | weekday-weekend + per jam) dijaga utuh dalam satu halaman;
   tabelnya dirapatkan supaya muat. */
.duo-utuh { break-inside: avoid; page-break-inside: avoid; }
.duo-utuh .main-table { margin-bottom: 14px; }
.duo-utuh .main-table td { padding-top: 3.5px; padding-bottom: 3.5px; font-size: 10px; }
.td-mall { color: #64748b; font-size: 9.5px; }
.deret-angka .deret-ket { display: inline; font-size: 8.5px; font-weight: 500; color: var(--redup2); }
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
$f          = fn($v) => number_format((float) $v, 0, ',', '.');           // format Indonesia: titik ribuan
$n          = fn($v) => $v > 0 ? $f($v) : '—';
$pct1       = fn(float $v) => str_replace('.', ',', (string) round($v, 1));  // 18.7 → 18,7
// Singkatan bulan Inggris dari date('M') → Indonesia (tanggal di KPI & periode event).
$blnId      = fn(string $s) => preg_replace_callback('/\b(May|Aug|Oct|Dec)\b/', fn($m) => ['May' => 'Mei', 'Aug' => 'Agu', 'Oct' => 'Okt', 'Dec' => 'Des'][$m[1]], $s);
$deltaHtml  = function (?float $pct) {
    if ($pct === null) return '';
    $cls = $pct >= 0 ? 'delta-up' : 'delta-down';
    return '<span class="' . $cls . '">' . ($pct >= 0 ? '▲' : '▼') . ' ' . str_replace('.', ',', (string) abs($pct)) . '%</span>';
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
        <div class="kpi-num"><?= $f($totalVisitor) ?></div>
        <div class="kpi-sub"><?= $deltaHtml($changePct) ?> vs <?= $prevLabel ?> (<?= $f($prevTotal) ?>)</div>
    </div>
    <div class="kpi-box kpi-ewalk">
        <div class="kpi-label">eWalk</div>
        <div class="kpi-num"><?= $f($totalEwalk) ?></div>
        <div class="kpi-sub"><?= $totalVisitor > 0 ? round($totalEwalk / $totalVisitor * 100) : 0 ?>% · bulan lalu <?= $f($prevEwalk) ?></div>
    </div>
    <div class="kpi-box kpi-penta">
        <div class="kpi-label">Pentacity</div>
        <div class="kpi-num"><?= $f($totalPenta) ?></div>
        <div class="kpi-sub"><?= $totalVisitor > 0 ? round($totalPenta / $totalVisitor * 100) : 0 ?>% · bulan lalu <?= $f($prevPenta) ?></div>
    </div>
    <div class="kpi-box kpi-avg">
        <div class="kpi-label">Rata-rata / Hari</div>
        <div class="kpi-num"><?= $f($avgDaily) ?></div>
        <div class="kpi-sub"><?= $deltaHtml($avgChangePct) ?> vs bulan lalu</div>
    </div>
    <div class="kpi-box kpi-purple">
        <div class="kpi-label">Hari Teramai</div>
        <div class="kpi-num kpi-teks"><?= $bestDay ? $blnId($bestDay) : '—' ?></div>
        <div class="kpi-sub"><?= $bestVal ? $f($bestVal) . ' pengunjung' : 'belum ada data' ?><?= $peakHour ? ' · <span class="nowrap">jam puncak ' . $peakHour . '</span>' : '' ?></div>
    </div>
</div>

<!-- ══ ANGKA SEKUNDER: weekday/weekend & kendaraan ══ -->
<div class="deret-angka">
    <div><span>Weekday <span class="deret-ket">Sen–Kam · <?= $wd['days'] ?> hari</span></span><b><?= $n($wd['avg']) ?><?= $wd['avg'] > 0 ? ' <span class="deret-ket">/hari</span>' : '' ?></b></div>
    <div><span>Weekend <span class="deret-ket">Jum–Min · <?= $we['days'] ?> hari</span></span><b><?= $n($we['avg']) ?><?= $we['avg'] > 0 ? ' <span class="deret-ket">/hari</span>' : '' ?></b></div>
    <div><span>Jam Puncak</span><b><?= $peakHour ?: '—' ?></b></div>
    <div><span>Mobil</span><b><?= $f($vehicles['mobil']) ?></b></div>
    <div><span>Motor</span><b><?= $f($vehicles['motor']) ?></b></div>
    <div><span>Mobil Box · Bus · Truck</span><b><?= $f($vehicles['mobil_box']) ?> · <?= $f($vehicles['bus']) ?> · <?= $f($vehicles['truck']) ?></b></div>
</div>

<!-- ══ ANALISA & GRAFIK ══ -->
<div class="sec-title"><span>Analisa &amp; Tren</span>
    <span class="sec-sub">tren 6 bulan terakhir &middot; harian <?= $bulanLabel ?></span></div>
<div class="chart-panel">
    <div class="insight-box">
        <div class="insight-title">Ringkasan Analisa</div>
        <ul class="insight-list">
            <?php foreach (($insights ?? []) as $ins): ?>
            <li><?= esc($blnId($ins)) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <div class="chart-box">
        <div class="chart-title">Tren Pengunjung — 6 Bulan Terakhir</div>
        <div class="chart-wrap"><canvas id="chartTrend"></canvas></div>
    </div>
    <div class="chart-box">
        <div class="chart-title">Pengunjung Harian — <?= $bulanLabel ?></div>
        <div class="chart-wrap"><canvas id="chartDaily"></canvas><?php if ($totalVisitor <= 0): ?>
            <div class="grafik-kosong"><span>Belum ada data pengunjung harian pada <?= $bulanLabel ?>.</span></div><?php endif; ?></div>
    </div>
</div>

<!-- ══ PER PINTU | WEEKDAY VS WEEKEND + PER JAM ══
     Urutan DOM = urutan nomor bagian: kiri (02) lalu kanan atas (03) dan kanan bawah (04). -->
<?php
$doorRows = [];
foreach ($doorEwalk as $d) $doorRows[] = ['pintu' => $d['pintu'], 'mall' => 'eWalk',     'total' => (int)$d['total']];
foreach ($doorPenta as $d) $doorRows[] = ['pintu' => $d['pintu'], 'mall' => 'Pentacity', 'total' => (int)$d['total']];
usort($doorRows, fn($a, $b) => $b['total'] <=> $a['total']);
$jamRows = array_filter($hours, fn($h) => $h['total'] !== 0);
?>
<div class="duo duo-utuh">
<div>
    <div class="sec-title"><span>Pengunjung per Pintu</span><span class="sec-sub">akumulasi sebulan · urut terbanyak</span></div>
    <table class="main-table">
    <thead><tr><th>Pintu</th><th>Mall</th><th class="num">Total</th><th class="num">%</th></tr></thead>
    <tbody>
    <?php foreach ($doorRows as $d): ?>
        <tr><td><?= esc($d['pintu']) ?></td><td class="td-mall"><?= $d['mall'] ?></td>
            <td class="num"><?= $n($d['total']) ?></td>
            <td class="num"><?= $totalVisitor > 0 ? $pct1($d['total'] / $totalVisitor * 100) : 0 ?>%</td></tr>
    <?php endforeach; ?>
    <?php if (! $doorRows): ?><tr class="empty-row"><td colspan="4">Belum ada data pintu pada <?= $bulanLabel ?>.</td></tr><?php endif; ?>
    </tbody>
    </table>
</div>
<div>
    <div class="sec-title"><span>Weekday vs Weekend</span></div>
    <table class="main-table">
    <thead><tr><th></th><th class="num">eWalk</th><th class="num">Pentacity</th><th class="num">Total</th><th class="num">Rata²/hari</th></tr></thead>
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
    <thead><tr><th>Jam</th><th class="num">eWalk</th><th class="num">Pentacity</th><th class="num">Total</th></tr></thead>
    <tbody>
    <?php foreach ($jamRows as $h): ?>
        <tr><td><?= $h['jam'] ?><?= $h['jam'] === $peakHour ? ' <span class="lencana emas">puncak</span>' : '' ?></td>
            <td class="<?= $h['ewalk'] ? 'num' : 'zero' ?>"><?= $n($h['ewalk']) ?></td>
            <td class="<?= $h['pentacity'] ? 'num' : 'zero' ?>"><?= $n($h['pentacity']) ?></td>
            <td class="num"><strong><?= $n($h['total']) ?></strong></td></tr>
    <?php endforeach; ?>
    <?php if (! $jamRows): ?><tr class="empty-row"><td colspan="4">Belum ada data per jam pada <?= $bulanLabel ?>.</td></tr><?php endif; ?>
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
    <th class="num" style="width:12%">Total Traffic</th>
    <th class="num" style="width:13%">Mobil</th><th class="num" style="width:13%">Motor</th>
</tr></thead>
<tbody>
<?php foreach ($periodEvents as $ev):
    $eid  = (int)$ev['id'];
    $end  = date('Y-m-d', strtotime($ev['start_date'] . ' +' . (max(1, (int)$ev['event_days']) - 1) . ' days'));
    $veh  = $eventVehicles[$eid] ?? ['mobil' => 0, 'motor' => 0];
?>
    <tr>
        <td><strong><?= esc($ev['name']) ?></strong></td>
        <td class="td-mall"><?= $mallLabel[$ev['mall']] ?? esc(ucfirst((string)$ev['mall'])) ?></td>
        <td class="td-mall"><?= $blnId(date('d M', strtotime($ev['start_date']))) ?> – <?= $blnId(date('d M Y', strtotime($end))) ?> (<?= (int)$ev['event_days'] ?> hari)</td>
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
    <th class="num" style="width:17%">eWalk</th><th class="num" style="width:17%">Pentacity</th><th class="num" style="width:18%">Total Pengunjung</th>
    <th class="num" style="width:17%">Mobil</th><th class="num" style="width:17%">Motor</th>
</tr></thead>
<tbody>
<?php $adaHari = false; foreach ($days as $d): if ($d['total'] === 0 && $d['mobil'] === 0 && $d['motor'] === 0) continue; $adaHari = true; ?>
    <tr class="<?= $d['dow'] >= 5 ? 'we-row' : '' ?>">
        <td><?= $d['date_fmt'] ?> <span class="subnote"><?= $dowShort[$d['dow']] ?></span></td>
        <td class="<?= $d['ewalk'] ? 'num' : 'zero' ?>"><?= $n($d['ewalk']) ?></td>
        <td class="<?= $d['pentacity'] ? 'num' : 'zero' ?>"><?= $n($d['pentacity']) ?></td>
        <td class="num"><strong><?= $n($d['total']) ?></strong></td>
        <td class="<?= $d['mobil'] ? 'num' : 'zero' ?>"><?= $n($d['mobil']) ?></td>
        <td class="<?= $d['motor'] ? 'num' : 'zero' ?>"><?= $n($d['motor']) ?></td>
    </tr>
<?php endforeach; ?>
<?php if (! $adaHari): ?><tr class="empty-row"><td colspan="6">Belum ada data harian pada <?= $bulanLabel ?>.</td></tr><?php endif; ?>
</tbody>
</table>

<!-- ══ TANDA TANGAN ══ -->
<?= $this->include('_laporan/_ttd') ?>

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

const nShort = v => v >= 1e6 ? (v/1e6).toFixed(1).replace('.', ',') + ' jt' : v >= 1e3 ? Math.round(v/1e3) + ' rb' : v;
const nId    = v => Number(v).toLocaleString('id-ID');
const baseOpts = {
    responsive: true, maintainAspectRatio: false,
    plugins: {
        legend: { position: 'bottom', labels: { color: ink, usePointStyle: true, pointStyle: 'circle', boxWidth: 7, boxHeight: 7, font: { size: 9.5 } } },
        tooltip: { callbacks: { label: c => c.dataset.label + ': ' + nId(c.parsed.y) } },
    },
    scales: {
        x: { ticks: { color: ink, font: { size: 9.5 } }, grid: { display: false } },
        y: { ticks: { color: ink, font: { size: 9.5 }, precision: 0, callback: v => nShort(v) }, grid: { color: grid }, beginAtZero: true },
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
