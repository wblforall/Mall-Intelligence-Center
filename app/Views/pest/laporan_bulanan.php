<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan Bulanan Pest Control — <?= $bulan ?></title>
<?= view('_laporan/_style') ?>
</head>
<body>

<button class="btn-print no-print" onclick="window.print()">&#128438; Cetak</button>

<?php
helper('tanggal');
$fmtBulan   = fn($m) => bulan_indo((int) substr($m, 5, 2)) . ' ' . substr($m, 0, 4);
$bulanLabel = $fmtBulan($bulan);
$prevLabel  = $fmtBulan($prevBulan);
$mallLabel  = $mall ? \App\Models\PestVisitModel::MALLS[$mall] : 'eWalk & Pentacity';
$n = fn($v) => $v > 0 ? number_format($v) : '—';

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

$deltaHtml = function (?float $pct) {
    if ($pct === null) return '<span class="subnote">tidak ada pembanding</span>';
    // Temuan pest NAIK itu buruk — warnanya sengaja dibalik dari laporan
    // pendapatan, di mana naik berarti baik.
    $cls = $pct <= 0 ? 'delta-up' : 'delta-down';
    return '<span class="' . $cls . '">' . ($pct >= 0 ? '▲' : '▼') . ' ' . abs($pct) . '%</span>';
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
    $asal = $jmlLegacy > 0
        ? ' (' . number_format($grandMingguan) . ' dari ' . $jmlKunjungan . ' kunjungan tercatat, sisanya dari rekap impor)'
        : ' dari ' . $jmlKunjungan . ' kunjungan';
    $insight[] = 'Total ' . number_format($grand) . ' temuan' . $asal
        . ($deltaPct === null ? '.' : ', ' . ($deltaPct > 0 ? 'naik' : ($deltaPct < 0 ? 'turun' : 'setara')) . ' ' . abs((float) $deltaPct) . '% dibanding ' . $prevLabel . '.');
    if ($tertinggiN > 0) {
        $insight[] = $tertinggi . ' menjadi temuan terbanyak (' . number_format($tertinggiN) . ', '
            . round($tertinggiN / $grand * 100) . '% dari seluruh temuan).';
    }
    // Minggu puncak
    $puncak = null;
    foreach ($mingguan as $k => $w) if (! $puncak || $w['total'] > $puncak['total']) $puncak = $w;
    if ($puncak && $puncak['total'] > 0) {
        $insight[] = 'Puncak mingguan pada ' . $puncak['label'] . ' (' . $tglSingkat($puncak['dari']) . '–' . $tglSingkat($puncak['sampai'])
            . ') dengan ' . number_format($puncak['total']) . ' temuan.';
    }
    foreach ($items as $it) {
        $a = $ini[(int) $it['id']] ?? 0; $b = $lalu[(int) $it['id']] ?? 0;
        if ($b > 0 && $a > $b * 2 && $a >= 10) {
            $insight[] = $it['nama'] . ' melonjak lebih dari dua kali lipat (' . $b . ' → ' . $a . ') — perlu penelusuran titik sumber.';
            break;
        }
    }
}
if ($jmlLegacy > 0) {
    $insight[] = 'Bulan ini memuat ' . $jmlLegacy . ' baris rekap impor yang tidak punya rincian mingguan; angkanya masuk ke total bulan, tidak ke tabel mingguan.';
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
        <div class="kpi-num"><?= number_format($grand) ?></div>
        <div class="kpi-sub"><?= $deltaHtml($deltaPct) ?> vs <?= $prevLabel ?> (<?= number_format($grandLalu) ?>)</div>
    </div>
    <div class="kpi-box kpi-amber">
        <div class="kpi-label">Temuan Terbanyak</div>
        <div class="kpi-num" style="font-size:15px"><?= $tertinggi !== '' ? esc($tertinggi) : '—' ?></div>
        <div class="kpi-sub"><?= $tertinggiN > 0
            ? number_format($tertinggiN) . ' ekor' . ($jmlLegacy > 0 ? ' (termasuk rekap impor)' : '')
            : 'tidak ada temuan' ?></div>
    </div>
    <div class="kpi-box kpi-green">
        <div class="kpi-label">Kunjungan Tercatat</div>
        <div class="kpi-num"><?= $jmlKunjungan ?></div>
        <div class="kpi-sub"><?= count($mingguan) ?> minggu ada temuan</div>
    </div>
    <div class="kpi-box kpi-purple">
        <div class="kpi-label">Jenis Hama Ditemukan</div>
        <div class="kpi-num"><?= count(array_filter($ini)) ?></div>
        <div class="kpi-sub">dari <?= count($items) ?> jenis yang dipantau</div>
    </div>
</div>

<!-- ══ REKAP PER MALL ══ -->
<div class="sec-title">Rekap per Item &amp; Mall<span class="sec-sub"><?= $bulanLabel ?></span></div>
<table class="main-table">
<thead>
<tr>
    <th>Item Temuan</th>
    <th class="text-center">eWalk</th>
    <th class="text-center">Pentacity</th>
    <th class="text-center">Total</th>
    <th class="text-center"><?= $prevLabel ?></th>
    <th class="text-center">Perubahan</th>
</tr>
</thead>
<tbody>
<?php $te = 0; $tp = 0; foreach ($items as $it): $id = (int) $it['id'];
    $e = $bulanIni['ewalk'][$id] ?? 0;
    $p = $bulanIni['pentacity'][$id] ?? 0;
    $t = $e + $p; $l = $lalu[$id] ?? 0;
    $te += $e; $tp += $p;
    $pc = $l > 0 ? round(($t - $l) / $l * 100, 1) : null; ?>
<tr>
    <td><?= esc($it['nama']) ?></td>
    <td class="<?= $e > 0 ? 'num' : 'zero' ?>"><?= $n($e) ?></td>
    <td class="<?= $p > 0 ? 'num' : 'zero' ?>"><?= $n($p) ?></td>
    <td class="num"><strong><?= $n($t) ?></strong></td>
    <td class="<?= $l > 0 ? 'num' : 'zero' ?>"><?= $n($l) ?></td>
    <td class="num"><?= $t === 0 && $l === 0 ? '<span class="zero">—</span>' : $deltaHtml($pc) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
<tfoot>
<tr style="font-weight:700; background:#f1f5f9">
    <td>TOTAL</td>
    <td class="num"><?= $n($te) ?></td>
    <td class="num"><?= $n($tp) ?></td>
    <td class="num"><?= $n($grand) ?></td>
    <td class="num"><?= $n($grandLalu) ?></td>
    <td class="num"><?= $deltaHtml($deltaPct) ?></td>
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
        <div class="chart-wrap"><canvas id="cWeek"></canvas></div>
    </div>
    <div class="chart-box">
        <div class="chart-title">Komposisi per Item</div>
        <div class="chart-wrap"><canvas id="cItem"></canvas></div>
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
    <?php foreach ($items as $it): ?><th class="text-center"><?= esc($it['nama']) ?></th><?php endforeach; ?>
    <th class="text-center">Total</th>
</tr>
</thead>
<tbody>
<?php if (! $mingguan): ?>
<tr><td colspan="<?= count($items) + 3 ?>" style="text-align:center;color:#94a3b8;padding:14px">
    Tidak ada temuan tercatat pada <?= $bulanLabel ?>.
</td></tr>
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
<tr style="font-weight:700; background:#f1f5f9">
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
<p class="subnote" style="margin-bottom:14px">
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

    const wLabels = <?= json_encode(array_values(array_map(fn($w) => $w['label'], $mingguan))) ?>;
    const wData   = <?= json_encode(array_values(array_map(fn($w) => $w['total'], $mingguan))) ?>;
    const iLabels = <?= json_encode(array_values(array_map(fn($it) => $it['nama'], array_filter($items, fn($it) => ($ini[(int) $it['id']] ?? 0) > 0)))) ?>;
    const iData   = <?= json_encode(array_values(array_map(fn($it) => $ini[(int) $it['id']], array_filter($items, fn($it) => ($ini[(int) $it['id']] ?? 0) > 0)))) ?>;

    if (wLabels.length) new Chart(document.getElementById('cWeek'), {
        type: 'bar',
        data: { labels: wLabels, datasets: [{ label: 'Temuan', data: wData, backgroundColor: '#2563eb' }] },
        options: { responsive: true, maintainAspectRatio: false, animation: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
    });

    if (iLabels.length) new Chart(document.getElementById('cItem'), {
        type: 'doughnut',
        data: { labels: iLabels, datasets: [{ data: iData, backgroundColor: palet }] },
        options: { responsive: true, maintainAspectRatio: false, animation: false,
            plugins: { legend: { position: 'right', labels: { boxWidth: 10, font: { size: 9 } } } } }
    });
})();
</script>
</body>
</html>
