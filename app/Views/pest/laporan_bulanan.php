<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan Bulanan Pest Control — <?= $bulan ?></title>
<?= view('_laporan/_style') ?>
<style>
/* Kepala kolom angka rata kanan, sejajar dengan isinya. */
.main-table th.num { text-align: right; }
/* KPI berisi nama item (teks), bukan angka besar. */
.kpi-num.kpi-teks { font-size: 15px; padding-top: 3px; letter-spacing: -.1px; }
/* Pesan pengganti grafik saat tidak ada data. */
.grafik-kosong {
    position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; text-align: center;
    padding: 0 16px; border: 1px dashed var(--garis); border-radius: 8px; background: #fbfcfe;
    font-size: 10px; font-style: italic; color: var(--redup2);
}
/* Tren 6 bulan: grafik + tabel angka berdampingan. */
.tren-panel .chart-box:first-child { flex: 1; }
.tren-panel .tren-tabel { flex: 1.25; }
.tren-panel .main-table { margin-bottom: 4px; }
.main-table.rapat th { padding: 4px 6px; font-size: 8.5px; }
.main-table.rapat td { padding: 2px 6px; font-size: 9.5px; }
/* Layar ponsel saja — kertas tetap A4 lanskap. */
@media screen and (max-width: 700px) {
    body { padding: 64px 12px 16px; margin: 0; }
    .doc-header { flex-direction: column; padding-left: 22px; }
    .doc-header::before, .doc-header::after { display: none; }
    .doc-header .meta { border-left: 0; padding-left: 0; text-align: left; }
    .kpi-row, .chart-panel { flex-wrap: wrap; }
    .kpi-row .kpi-box { flex: 1 1 45%; }
    .insight-box, .chart-box, .tren-panel .tren-tabel { flex: 1 1 100%; }
    .main-table { display: block; overflow-x: auto; }
}
.main-table tfoot tr.tren-mall td { font-weight: 500; color: var(--teks); border-top: 0; background: #f6f8fb; }
</style>
</head>
<body>

<button class="btn-print no-print" onclick="window.print()">&#128438; Cetak</button>

<?php
helper('tanggal');
$fmtBulan   = fn($m) => bulan_indo((int) substr($m, 5, 2)) . ' ' . substr($m, 0, 4);
$bulanLabel = $fmtBulan($bulan);
$prevLabel  = $fmtBulan($prevBulan);
$mallLabel  = $mall ? \App\Models\PestVisitModel::MALLS[$mall] : 'eWalk & Pentacity';
$f = fn($v) => number_format((float) $v, 0, ',', '.');   // format Indonesia: titik ribuan
$n = fn($v) => $v > 0 ? $f($v) : '—';
$pctId = fn($v) => str_replace('.', ',', (string) $v);  // 12.5 → 12,5

$tglSingkat = function (string $d) {
    return date('j', strtotime($d)) . ' ' . substr(bulan_indo((int) date('n', strtotime($d))), 0, 3);
};

// Ratakan per item lintas mall
$ratakan = function (array $perMall): array {
    $out = [];
    foreach ($perMall as $perItem) foreach ($perItem as $iid => $v) $out[$iid] = ($out[$iid] ?? 0) + $v;
    return $out;
};
$ini   = $ratakan($bulanIni);
$lalu  = $ratakan($bulanLalu);
$grand = array_sum($ini);
$grandLalu = array_sum($lalu);
$deltaPct = $grandLalu > 0 ? round(($grand - $grandLalu) / $grandLalu * 100, 1) : null;

// Bulan yang sama tahun lalu (YoY).
$yoy       = $ratakan($bulanYoY);
$grandYoY  = array_sum($yoy);
$yoyLabel  = $fmtBulan($yoyBulan);
$yoyPct    = $grandYoY > 0 ? round(($grand - $grandYoY) / $grandYoY * 100, 1) : null;
$yoyImpor  = ! empty($yoyLegacy);   // angka tahun lalu berasal dari rekap impor Excel

// Tren 6 bulan: rata-rata 5 bulan sebelumnya sebagai patokan bulan ini.
$trenSebelum = array_slice($tren, 0, 5);
$trenAda     = array_filter($trenSebelum, fn($t) => $t['total'] > 0);
$trenRata    = $trenAda ? array_sum(array_column($trenAda, 'total')) / count($trenAda) : null;
$trenImpor   = array_filter($tren, fn($t) => $t['legacy']);

$deltaHtml = function (?float $pct) {
    if ($pct === null) return '<span class="subnote">tidak ada pembanding</span>';
    // Temuan pest NAIK itu buruk — warnanya sengaja dibalik dari laporan
    // pendapatan, di mana naik berarti baik.
    if ($pct == 0) return '<span class="lencana netral">0%</span>';
    $cls = $pct <= 0 ? 'delta-up' : 'delta-down';
    return '<span class="' . $cls . '">' . ($pct >= 0 ? '▲' : '▼') . ' ' . str_replace('.', ',', (string) abs($pct)) . '%</span>';
};

// Total baris mingguan — dipakai untuk membedakan temuan dari kunjungan nyata
// terhadap temuan yang datang dari rekap impor.
$grandMingguan = 0;
foreach ($mingguan as $w) $grandMingguan += $w['total'];

// Item tertinggi
$tertinggi = ''; $tertinggiN = 0;
foreach ($items as $it) {
    $v = $ini[(int) $it['id']] ?? 0;
    if ($v > $tertinggiN) { $tertinggiN = $v; $tertinggi = $it['nama']; }
}

// Ringkasan analisa rule-based
$insight = [];
if ($grand === 0) {
    $insight[] = $jmlKunjungan > 0
        ? 'Tidak ada temuan sama sekali sepanjang ' . $bulanLabel . ' dari ' . $jmlKunjungan . ' kunjungan yang tercatat.'
        : 'Belum ada kunjungan tercatat pada ' . $bulanLabel . ' — angka nol di sini berarti belum diinput, bukan nihil temuan.';
} else {
    // Saat ada baris impor, jumlah temuan TIDAK berasal dari jumlah kunjungan
    // nyata — menggabungkan keduanya dalam satu kalimat akan menyesatkan.
    $asal = $jmlLegacy > 0 && $jmlKunjungan === 0
        ? ', seluruhnya dari rekap bulanan impor Excel'
        : ($jmlLegacy > 0
        ? ' (' . $f($grandMingguan) . ' dari ' . $jmlKunjungan . ' kunjungan tercatat, sisanya dari rekap impor)'
        : ' dari ' . $jmlKunjungan . ' kunjungan');
    $insight[] = 'Total ' . $f($grand) . ' temuan' . $asal
        . ($deltaPct === null ? '.' : ', ' . ($deltaPct > 0 ? 'naik' : ($deltaPct < 0 ? 'turun' : 'setara')) . ' ' . $pctId(abs((float) $deltaPct)) . '% dibanding ' . $prevLabel . '.');
    if ($tertinggiN > 0) {
        $insight[] = $tertinggi . ' menjadi temuan terbanyak (' . $f($tertinggiN) . ', '
            . round($tertinggiN / $grand * 100) . '% dari seluruh temuan).';
    }
    // Minggu puncak
    $puncak = null;
    foreach ($mingguan as $k => $w) if (! $puncak || $w['total'] > $puncak['total']) $puncak = $w;
    if ($puncak && $puncak['total'] > 0) {
        $insight[] = 'Puncak mingguan pada ' . $puncak['label'] . ' (' . $tglSingkat($puncak['dari']) . '–' . $tglSingkat($puncak['sampai'])
            . ') dengan ' . $f($puncak['total']) . ' temuan.';
    }
    foreach ($items as $it) {
        $a = $ini[(int) $it['id']] ?? 0; $b = $lalu[(int) $it['id']] ?? 0;
        if ($b > 0 && $a > $b * 2 && $a >= 10) {
            $insight[] = $it['nama'] . ' melonjak lebih dari dua kali lipat (' . $b . ' → ' . $a . ') — perlu penelusuran titik sumber.';
            break;
        }
    }
}
// Pembanding bulan yang sama tahun lalu — membuang pengaruh musim.
if (! $yoyAda) {
    $insight[] = 'Belum ada data ' . $yoyLabel . ' untuk pembanding tahun lalu.';
} elseif ($grand > 0 || $grandYoY > 0) {
    $insight[] = 'Dibanding ' . $yoyLabel . ' (' . $f($grandYoY) . ' temuan' . ($yoyImpor ? ', dari rekap impor Excel' : '') . '), '
        . ($yoyPct === null ? 'bulan ini ' . $f($grand) . ' temuan — tahun lalu tidak ada temuan untuk dibandingkan.'
            : 'temuan ' . ($yoyPct > 0 ? 'naik ' : ($yoyPct < 0 ? 'turun ' : 'setara ')) . $pctId(abs($yoyPct)) . '%.');
    // Item yang paling berubah terhadap tahun lalu.
    $geser = null;
    foreach ($items as $it) {
        $s = ($ini[(int) $it['id']] ?? 0) - ($yoy[(int) $it['id']] ?? 0);
        if ($s !== 0 && (! $geser || abs($s) > abs($geser[1]))) $geser = [$it['nama'], $s];
    }
    if ($geser && abs($geser[1]) >= 5) {
        $insight[] = 'Perubahan terbesar terhadap tahun lalu: ' . $geser[0] . ' ' . ($geser[1] > 0 ? 'bertambah ' : 'berkurang ') . $f(abs($geser[1])) . '.';
    }
}
if ($trenRata !== null) {
    $selisih = $trenRata > 0 ? round(($grand - $trenRata) / $trenRata * 100) : null;
    $insight[] = 'Rata-rata ' . count($trenAda) . ' bulan sebelumnya ' . $f(round($trenRata)) . ' temuan/bulan — bulan ini '
        . ($selisih === null ? 'setara.' : ($selisih >= 0 ? $selisih . '% di atas' : abs($selisih) . '% di bawah') . ' rata-rata itu.');
}
if ($jmlLegacy > 0) {
    $insight[] = 'Bulan ini memuat ' . $jmlLegacy . ' baris rekap impor yang tidak punya rincian mingguan; angkanya masuk ke total bulan, tidak ke tabel mingguan.';
}
if ($jmlTergeser > 0) {
    $insight[] = $jmlTergeser . ' baris rekap impor bulan ini diabaikan karena sudah ada catatan kunjungan harian — supaya tidak terhitung dua kali.';
}
?>

<!-- ══ HEADER ══ -->
<div class="doc-header">
    <div>
        <div class="title">Laporan Bulanan — Pest Control</div>
        <div class="sub"><?= $bulanLabel ?> &middot; <?= $mallLabel ?></div>
        <div class="org">PT. Wulandari Bangun Laksana Tbk. &mdash; IT Department &mdash; Mall Intelligence Center</div>
    </div>
    <div class="meta">
        Dicetak oleh: <?= esc($printedBy) ?><br>
        Tanggal cetak: <?= $printedAt ?><br>
        Sumber data: Input kunjungan Pest Control
    </div>
</div>

<!-- ══ KPI ══ -->
<div class="kpi-row">
    <div class="kpi-box kpi-blue">
        <div class="kpi-label">Total Temuan</div>
        <div class="kpi-num"><?= $f($grand) ?></div>
        <div class="kpi-sub"><?= $deltaHtml($deltaPct) ?> vs <?= $prevLabel ?> (<?= $f($grandLalu) ?>)</div>
    </div>
    <div class="kpi-box kpi-amber">
        <div class="kpi-label">Temuan Terbanyak</div>
        <div class="kpi-num kpi-teks"><?= $tertinggi !== '' ? esc($tertinggi) : '—' ?></div>
        <div class="kpi-sub"><?= $tertinggiN > 0
            ? $f($tertinggiN) . ' ekor' . ($jmlLegacy > 0 ? ' (termasuk rekap impor)' : '')
            : 'tidak ada temuan' ?></div>
    </div>
    <div class="kpi-box kpi-green">
        <div class="kpi-label">Kunjungan Tercatat</div>
        <div class="kpi-num"><?= $f($jmlKunjungan) ?></div>
        <div class="kpi-sub"><?= count($mingguan) ?> minggu ada temuan</div>
    </div>
    <div class="kpi-box kpi-purple">
        <div class="kpi-label">Jenis Hama Ditemukan</div>
        <div class="kpi-num"><?= count(array_filter($ini)) ?></div>
        <div class="kpi-sub">dari <?= count($items) ?> jenis yang dipantau</div>
    </div>
    <div class="kpi-box kpi-gold">
        <div class="kpi-label">Bulan Sama Tahun Lalu</div>
        <div class="kpi-num"><?= $yoyAda ? $f($grandYoY) : '—' ?></div>
        <div class="kpi-sub"><?= $yoyAda
            ? $deltaHtml($yoyPct) . ' ' . $yoyLabel . ($yoyImpor ? ' · rekap impor' : '')
            : 'belum ada data ' . $yoyLabel ?></div>
    </div>
</div>

<!-- ══ REKAP PER MALL ══ -->
<div class="sec-title">Rekap per Item &amp; Mall<span class="sec-sub"><?= $bulanLabel ?></span></div>
<table class="main-table">
<thead>
<tr>
    <th>Item Temuan</th>
    <th class="num">eWalk</th>
    <th class="num">Pentacity</th>
    <th class="num">Total</th>
    <th class="num"><?= $prevLabel ?></th>
    <th class="num">vs Bln Lalu</th>
    <th class="num"><?= $yoyLabel ?><?= $yoyImpor ? '*' : '' ?></th>
    <th class="num">vs Thn Lalu</th>
</tr>
</thead>
<tbody>
<?php $te = 0; $tp = 0; foreach ($items as $it): $id = (int) $it['id'];
    $e = $bulanIni['ewalk'][$id] ?? 0;
    $p = $bulanIni['pentacity'][$id] ?? 0;
    $t = $e + $p; $l = $lalu[$id] ?? 0; $y = $yoy[$id] ?? 0;
    $te += $e; $tp += $p;
    $pc = $l > 0 ? round(($t - $l) / $l * 100, 1) : null;
    $py = $y > 0 ? round(($t - $y) / $y * 100, 1) : null; ?>
<tr>
    <td><?= esc($it['nama']) ?></td>
    <td class="<?= $e > 0 ? 'num' : 'zero' ?>"><?= $n($e) ?></td>
    <td class="<?= $p > 0 ? 'num' : 'zero' ?>"><?= $n($p) ?></td>
    <td class="num"><strong><?= $n($t) ?></strong></td>
    <td class="<?= $l > 0 ? 'num' : 'zero' ?>"><?= $n($l) ?></td>
    <td class="num"><?= $t === 0 && $l === 0 ? '<span class="zero">—</span>' : $deltaHtml($pc) ?></td>
    <td class="<?= $y > 0 ? 'num' : 'zero' ?>"><?= $yoyAda ? $n($y) : '—' ?></td>
    <td class="num"><?= ! $yoyAda || ($t === 0 && $y === 0) ? '<span class="zero">—</span>' : $deltaHtml($py) ?></td>
</tr>
<?php endforeach; ?>
<?php if (! $items): ?><tr class="empty-row"><td colspan="8">Belum ada item temuan yang dipantau.</td></tr><?php endif; ?>
</tbody>
<tfoot>
<tr>
    <td>TOTAL</td>
    <td class="num"><?= $n($te) ?></td>
    <td class="num"><?= $n($tp) ?></td>
    <td class="num"><?= $n($grand) ?></td>
    <td class="num"><?= $n($grandLalu) ?></td>
    <td class="num"><?= $deltaHtml($deltaPct) ?></td>
    <td class="num"><?= $yoyAda ? $n($grandYoY) : '—' ?></td>
    <td class="num"><?= $yoyAda ? $deltaHtml($yoyPct) : '—' ?></td>
</tr>
</tfoot>
</table>

<!-- ══ ANALISA + GRAFIK ══ -->
<div class="sec-title">Ringkasan Analisa<span class="sec-sub">disusun otomatis dari data bulan ini</span></div>
<div class="chart-panel">
    <div class="insight-box">
        <div class="insight-title">Catatan</div>
        <ul class="insight-list">
            <?php foreach ($insight as $i): ?><li><?= esc($i) ?></li><?php endforeach; ?>
        </ul>
    </div>
    <div class="chart-box">
        <div class="chart-title">Temuan per Minggu</div>
        <div class="chart-wrap"><canvas id="cWeek"></canvas><?php if (! $mingguan): ?>
            <div class="grafik-kosong">Tidak ada temuan per minggu yang tercatat pada <?= $bulanLabel ?>.</div><?php endif; ?></div>
    </div>
    <div class="chart-box">
        <div class="chart-title">Komposisi per Item</div>
        <div class="chart-wrap"><canvas id="cItem"></canvas><?php if (! array_filter($ini)): ?>
            <div class="grafik-kosong">Belum ada temuan untuk disusun komposisinya.</div><?php endif; ?></div>
    </div>
</div>

<!-- ══ TREN 6 BULAN ══ -->
<?php $blnSingkat = fn($m) => substr(bulan_indo((int) substr($m, 5, 2)), 0, 3) . ' ' . substr($m, 2, 2); ?>
<div class="sec-title">Tren 6 Bulan<span class="sec-sub"><?= $fmtBulan($tren[0]['bulan']) ?> &ndash; <?= $bulanLabel ?></span></div>
<div class="chart-panel tren-panel">
    <div class="chart-box">
        <div class="chart-title">Temuan per Bulan<?= $mall ? '' : ' per Mall' ?></div>
        <div class="chart-wrap"><canvas id="cTren"></canvas><?php if (! array_sum(array_column($tren, 'total'))): ?>
            <div class="grafik-kosong">Belum ada temuan dalam 6 bulan terakhir.</div><?php endif; ?></div>
    </div>
    <div class="chart-box tren-tabel">
        <table class="main-table rapat">
        <thead><tr>
            <th>Item</th>
            <?php foreach ($tren as $t): ?><th class="num"><?= $blnSingkat($t['bulan']) ?><?= $t['legacy'] ? '*' : '' ?></th><?php endforeach; ?>
        </tr></thead>
        <tbody>
        <?php foreach ($items as $it): $id = (int) $it['id'];
            if (! array_sum(array_map(fn($t) => $t['items'][$id] ?? 0, $tren))) continue; ?>
        <tr>
            <td><?= esc($it['nama']) ?></td>
            <?php foreach ($tren as $t): $v = $t['items'][$id] ?? 0; ?><td class="<?= $v ? 'num' : 'zero' ?>"><?= $n($v) ?></td><?php endforeach; ?>
        </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
        <?php if (! $mall): foreach (\App\Models\PestVisitModel::MALLS as $mk => $ml): ?>
        <tr class="tren-mall"><td><?= $ml ?></td><?php foreach ($tren as $t): ?><td class="num"><?= $n($t['mall'][$mk]) ?></td><?php endforeach; ?></tr>
        <?php endforeach; endif; ?>
        <tr><td>TOTAL</td><?php foreach ($tren as $t): ?><td class="num"><?= $n($t['total']) ?></td><?php endforeach; ?></tr>
        </tfoot>
        </table>
        <?php if ($trenImpor || $yoyImpor): ?>
        <div class="subnote">* angka bulan bertanda bintang (seluruh atau sebagian mall) berasal dari rekap impor Excel bulanan, bukan dari kunjungan tercatat.</div>
        <?php endif; ?>
    </div>
</div>

<!-- ══ RINCIAN MINGGUAN ══ -->
<div class="sec-title">
    Rincian Mingguan
    <span class="sec-sub"><?= $jmlLegacy > 0
        ? 'minggu ISO, dipotong di batas bulan — hanya kunjungan tercatat, tanpa rekap impor'
        : 'minggu ISO, dipotong di batas bulan — jumlahnya sama dengan total ' . $bulanLabel ?></span>
</div>
<table class="main-table">
<thead>
<tr>
    <th>Minggu</th><th>Periode</th>
    <?php foreach ($items as $it): ?><th class="num"><?= esc($it['nama']) ?></th><?php endforeach; ?>
    <th class="num">Total</th>
</tr>
</thead>
<tbody>
<?php if (! $mingguan): ?>
<tr class="empty-row"><td colspan="<?= count($items) + 3 ?>">Tidak ada temuan tercatat pada <?= $bulanLabel ?>.</td></tr>
<?php else: $cekTotal = 0; foreach ($mingguan as $w): $cekTotal += $w['total']; ?>
<tr>
    <td><strong><?= $w['label'] ?></strong><?= $w['sebagian'] ? ' <span class="subnote">(sebagian)</span>' : '' ?></td>
    <td><?= $tglSingkat($w['dari']) ?> &ndash; <?= $tglSingkat($w['sampai']) ?></td>
    <?php foreach ($items as $it): $v = $w['items'][(int) $it['id']] ?? 0; ?>
    <td class="<?= $v > 0 ? 'num' : 'zero' ?>"><?= $n($v) ?></td>
    <?php endforeach; ?>
    <td class="num"><strong><?= $n($w['total']) ?></strong></td>
</tr>
<?php endforeach; ?>
</tbody>
<tfoot>
<tr>
    <td colspan="2">TOTAL MINGGUAN</td>
    <?php foreach ($items as $it): $s = 0; foreach ($mingguan as $w) $s += $w['items'][(int) $it['id']] ?? 0; ?>
    <td class="num"><?= $n($s) ?></td>
    <?php endforeach; ?>
    <td class="num"><?= $n($cekTotal) ?></td>
</tr>
</tfoot>
<?php endif; ?>
</table>

<?php if ($jmlLegacy > 0): ?>
<p class="catatan info">
    <strong>Catatan:</strong> <?= $jmlLegacy ?> baris pada bulan ini berasal dari impor rekap bulanan
    Excel dan tidak punya tanggal kunjungan sesungguhnya. Angkanya ikut pada tabel rekap dan KPI di atas,
    tetapi <strong>tidak</strong> pada tabel mingguan — karena itulah kedua total bisa berbeda.
</p>
<?php endif; ?>

<?= view('_laporan/_ttd', ['signatories' => $signatories]) ?>

<div class="doc-footer">
    <span>Mall Intelligence Center &mdash; Laporan Bulanan Pest Control</span>
    <span><?= $bulanLabel ?> &middot; <?= $mallLabel ?></span>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    if (typeof Chart === 'undefined') return;
    const palet = ['#2563eb', '#d97706', '#059669', '#9333ea', '#dc2626', '#0891b2', '#65a30d', '#c026d3'];
    const ink   = 'rgba(51,65,85,.75)';
    const nId   = v => Number(v).toLocaleString('id-ID');
    Chart.defaults.devicePixelRatio = 2;

    const wLabels = <?= json_encode(array_values(array_map(fn($w) => $w['label'], $mingguan))) ?>;
    const wData   = <?= json_encode(array_values(array_map(fn($w) => $w['total'], $mingguan))) ?>;
    const iLabels = <?= json_encode(array_values(array_map(fn($it) => $it['nama'], array_filter($items, fn($it) => ($ini[(int) $it['id']] ?? 0) > 0)))) ?>;
    const iData   = <?= json_encode(array_values(array_map(fn($it) => $ini[(int) $it['id']], array_filter($items, fn($it) => ($ini[(int) $it['id']] ?? 0) > 0)))) ?>;

    if (wLabels.length) new Chart(document.getElementById('cWeek'), {
        type: 'bar',
        data: { labels: wLabels, datasets: [{ label: 'Temuan', data: wData, backgroundColor: '#2563eb', borderRadius: 3 }] },
        options: { responsive: true, maintainAspectRatio: false, animation: false,
            plugins: { legend: { display: false },
                tooltip: { callbacks: { label: c => 'Temuan: ' + nId(c.parsed.y) } } },
            scales: {
                x: { ticks: { color: ink, font: { size: 9.5 } }, grid: { display: false } },
                y: { beginAtZero: true, ticks: { precision: 0, color: ink, font: { size: 9.5 }, callback: v => nId(v) }, grid: { color: 'rgba(0,0,0,.06)' } } } }
    });

    const tren = <?= json_encode(array_map(fn($t) => ['l' => $blnSingkat($t['bulan']), 'mall' => $t['mall'], 'total' => $t['total']], $tren)) ?>;
    const trenMall = <?= json_encode($mall ? [$mall] : array_keys(\App\Models\PestVisitModel::MALLS)) ?>;
    const namaMall = <?= json_encode(\App\Models\PestVisitModel::MALLS) ?>;
    const warnaMall = { ewalk: '#2a78d6', pentacity: '#16a34a' };
    if (tren.some(t => t.total > 0)) new Chart(document.getElementById('cTren'), {
        type: 'bar',
        data: { labels: tren.map(t => t.l),
            datasets: trenMall.map(mk => ({ label: namaMall[mk], data: tren.map(t => t.mall[mk]), backgroundColor: warnaMall[mk], borderRadius: 3 })) },
        options: { responsive: true, maintainAspectRatio: false, animation: false,
            plugins: { legend: { position: 'top', labels: { color: ink, boxWidth: 9, boxHeight: 9, font: { size: 9.5 } } },
                tooltip: { callbacks: { label: c => ' ' + c.dataset.label + ': ' + nId(c.parsed.y) } } },
            scales: {
                x: { ticks: { color: ink, font: { size: 9.5 } }, grid: { display: false } },
                y: { beginAtZero: true, ticks: { precision: 0, color: ink, font: { size: 9.5 }, callback: v => nId(v) }, grid: { color: 'rgba(0,0,0,.06)' } } } }
    });

    if (iLabels.length) new Chart(document.getElementById('cItem'), {
        type: 'doughnut',
        data: { labels: iLabels, datasets: [{ data: iData, backgroundColor: palet, borderColor: '#fff', borderWidth: 1.5 }] },
        options: { responsive: true, maintainAspectRatio: false, animation: false, cutout: '58%',
            plugins: {
                legend: { position: 'right', labels: { color: ink, usePointStyle: true, pointStyle: 'circle', boxWidth: 7, boxHeight: 7, font: { size: 9.5 },
                    // Label legenda memuat jumlahnya — angka tetap terbaca di kertas.
                    generateLabels: ch => Chart.overrides.doughnut.plugins.legend.labels.generateLabels(ch)
                        .map((l, i) => ({ ...l, text: l.text + ' · ' + nId(iData[i]) })) } },
                tooltip: { callbacks: { label: c => c.label + ': ' + nId(c.parsed) } } } }
    });
})();
</script>
</body>
</html>
