<?php
use App\Libraries\PestRekap as PR;
use App\Models\PestVisitModel;

$nf = fn($v) => PR::angka($v);
$warna = [1 => '#6366f1', 2 => '#f97316', 3 => '#10b981'];
$label = [];
foreach ($periode as $i => [$a, $b]) $label[$i] = PR::rentang($a, $b);
$mallLabel = $mall ? PestVisitModel::MALLS[$mall] : 'eWalk & Pentacity';
// NAIK = buruk (merah), TURUN = baik (hijau) — sama dengan Laporan Bulanan Pest.
$deltaHtml = function (?float $pct, bool $netral = false) {
    if ($pct === null) return '<span class="zero">—</span>';
    if ($netral || $pct == 0) return '<span class="lencana netral">' . PR::persen($pct, true) . '</span>';
    $cls = $pct <= 0 ? 'delta-up' : 'delta-down';
    return '<span class="' . $cls . '">' . ($pct >= 0 ? '▲' : '▼') . ' ' . PR::persen($pct) . '</span>';
};
$baris = function (array $b, string $kelas = '', bool $netral = false) use ($p, $nf, $deltaHtml): string {
    $h = '<tr class="' . $kelas . '"><td>' . esc($b['label']) . '</td>';
    foreach ($p as $i => $_) $h .= '<td class="' . ($b['v'][$i] ? 'num' : 'zero') . '">' . ($b['v'][$i] ? $nf($b['v'][$i]) : '—') . '</td>';
    foreach ($b['d'] as $i => $d) $h .= '<td class="num">' . ($b['v'][1] === 0 && $b['v'][$i] === 0 ? '<span class="zero">—</span>' : $deltaHtml($d, $netral)) . '</td>';
    return $h . '</tr>';
};
$kol = count($p) * 2;
$itemGrafik = array_values(array_filter($perItem, fn($b) => array_sum($b['v']) > 0));

// Ringkasan analisa rule-based untuk perbandingan.
$analisa = [];
$t = $total['v'];
foreach ($p as $i => $r) {
    $asal = $r['jmlKunjungan'] === 0 && $r['grandLegacy'] > 0
        ? ', seluruhnya dari rekap impor bulanan'
        : ' dari ' . $nf($r['jmlKunjungan']) . ' kunjungan' . ($r['grandLegacy'] > 0 ? ' (+ ' . $nf($r['grandLegacy']) . ' dari rekap impor)' : '');
    $analisa[] = 'P' . $i . ' (' . $label[$i] . ', ' . $r['hari'] . ' hari): ' . $nf($r['grand']) . ' temuan' . $asal
        . ($i > 1 && $total['d'][$i] !== null ? ' — ' . ($total['d'][$i] > 0 ? 'naik ' : ($total['d'][$i] < 0 ? 'turun ' : 'setara ')) . PR::persen($total['d'][$i]) . ' vs P1' : '') . '.';
}
// Item dengan perubahan absolut terbesar P2 vs P1.
$geser = null;
foreach ($perItem as $b) {
    $s = $b['v'][2] - $b['v'][1];
    if ($s !== 0 && (! $geser || abs($s) > abs($geser[1]))) $geser = [$b['label'], $s, $b['v'][1], $b['v'][2]];
}
if ($geser) {
    $analisa[] = 'Perubahan terbesar P2 vs P1: ' . $geser[0] . ' ' . ($geser[1] > 0 ? 'bertambah ' : 'berkurang ') . $nf(abs($geser[1]))
        . ' (' . $nf($geser[2]) . ' → ' . $nf($geser[3]) . ').';
}
$hari = array_map(fn($r) => $r['hari'], $p);
if (count(array_unique($hari)) > 1) {
    $analisa[] = 'Panjang periode berbeda (' . implode(' / ', $hari) . ' hari) — bandingkan juga rata-rata temuan per hari.';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Compare Pest Control — <?= implode(' vs ', $label) ?></title>
<?= view('_laporan/_style') ?>
<style>
@page { @top-right { content: "Compare Periode Pest Control"; } }
.main-table th.num { text-align: right; }
.main-table tr.grup td { background: #eef2f8 !important; font-size: 8.5px; font-weight: 700; letter-spacing: .6px; text-transform: uppercase; color: var(--redup); }
.main-table tr.total td { font-weight: 700; color: var(--tinta); }
.p-dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; margin-right: 4px; vertical-align: 0; }
.kpi-row .kpi-box .p-baris { display: flex; justify-content: space-between; align-items: baseline; gap: 6px; font-variant-numeric: tabular-nums; }
.kpi-row .kpi-box .p-baris b { font-size: 15px; }
.tautan-kembali {
    position: fixed; top: 16px; left: 16px; z-index: 50;
    padding: 8px 14px; border-radius: 8px; background: #fff; border: 1px solid var(--garis);
    color: var(--navy-2); font: 600 12px 'Inter', Arial, sans-serif; text-decoration: none;
    box-shadow: 0 4px 14px rgba(9,21,40,.12);
}
@media screen and (max-width: 700px) {
    body { padding: 64px 12px 16px; margin: 0; }
    .doc-header { flex-direction: column; padding-left: 22px; }
    .doc-header::before, .doc-header::after { display: none; }
    .doc-header .meta { border-left: 0; padding-left: 0; text-align: left; }
    .kpi-row, .chart-panel { flex-wrap: wrap; }
    .kpi-row .kpi-box { flex: 1 1 45%; }
    .insight-box, .chart-box { flex: 1 1 100%; }
    .tabel-gulir { overflow-x: auto; }
}
</style>
</head>
<body>

<?php $qsKembali = ['mall' => $mall];
foreach ($periode as $i => [$a, $b]) $qsKembali += ['from' . $i => $a, 'to' . $i => $b]; ?>
<a class="tautan-kembali no-print" href="<?= base_url('pest/compare') . '?' . http_build_query(array_filter($qsKembali)) ?>">← Kembali ke Compare</a>
<button class="btn-print no-print" onclick="window.print()">Cetak</button>

<div class="doc-header">
    <div>
        <div class="title">Compare Periode — Pest Control</div>
        <div class="sub"><?= implode(' &nbsp;vs&nbsp; ', $label) ?></div>
        <div class="org">PT. Wulandari Bangun Laksana Tbk. &mdash; Mall Intelligence Center &middot; <?= $mallLabel ?></div>
    </div>
    <div class="meta">
        Dicetak oleh: <?= esc($printedBy) ?><br>
        Tanggal cetak: <?= $printedAt ?><br>
        Patokan selisih: Periode 1
    </div>
</div>

<div class="kpi-row">
<?php
$kpi = [['Total Temuan', $total, 'kpi-blue', false]];
if (! $mall) foreach ($perMall as $b) $kpi[] = [$b['label'], $b, $b['label'] === 'eWalk' ? 'kpi-ewalk' : 'kpi-penta', false];
$kpi[] = ['Kunjungan Tercatat', $kunjungan, 'kpi-purple', true];
foreach ($kpi as [$judul, $b, $kelas, $netral]): ?>
    <div class="kpi-box <?= $kelas ?>">
        <div class="kpi-label"><?= $judul ?></div>
        <?php foreach ($p as $i => $_): ?>
        <div class="p-baris"><span><span class="p-dot" style="background:<?= $warna[$i] ?>"></span>P<?= $i ?></span>
            <span><?= $i > 1 ? $deltaHtml($b['d'][$i], $netral) . ' ' : '' ?><b><?= $nf($b['v'][$i]) ?></b></span></div>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>
</div>

<?php foreach ($catatan as $i => $cs): foreach ($cs as $c): ?>
<div class="catatan <?= str_starts_with($c, 'TIDAK') ? 'waspada' : 'info' ?>"><strong>P<?= $i ?>:</strong> <?= esc($c) ?></div>
<?php endforeach; endforeach; ?>

<div class="sec-title">Ringkasan Analisa<span class="sec-sub">selisih terhadap Periode 1</span></div>
<div class="chart-panel">
    <div class="insight-box">
        <div class="insight-title">Catatan</div>
        <ul class="insight-list"><?php foreach ($analisa as $a): ?><li><?= esc($a) ?></li><?php endforeach; ?></ul>
    </div>
    <div class="chart-box" style="flex:1.6">
        <div class="chart-title">Temuan per Item</div>
        <div class="chart-wrap"><canvas id="cItem"></canvas></div>
    </div>
    <div class="chart-box">
        <div class="chart-title">Temuan per Mall</div>
        <div class="chart-wrap"><canvas id="cMall"></canvas></div>
    </div>
</div>

<div class="sec-title">Perbandingan Rinci<span class="sec-sub">merah = temuan naik (memburuk), hijau = turun</span></div>
<div class="tabel-gulir">
<table class="main-table">
<thead><tr>
    <th>Uraian</th>
    <?php foreach ($p as $i => $_): ?><th class="num"><span class="p-dot" style="background:<?= $warna[$i] ?>"></span>P<?= $i ?> · <?= $label[$i] ?></th><?php endforeach; ?>
    <?php foreach ($p as $i => $_): if ($i === 1) continue; ?><th class="num">P<?= $i ?> vs P1</th><?php endforeach; ?>
</tr></thead>
<tbody>
    <tr class="grup"><td colspan="<?= $kol ?>">Per item</td></tr>
    <?php foreach ($perItem as $b) echo $baris($b); ?>
    <tr class="grup"><td colspan="<?= $kol ?>">Per mall</td></tr>
    <?php foreach ($perMall as $b) echo $baris($b); ?>
    <tr class="grup"><td colspan="<?= $kol ?>">Ringkasan</td></tr>
    <?= $baris($total, 'total') ?>
    <?php if (array_sum($impor['v']) > 0) echo $baris($impor, '', true); ?>
    <?= $baris($kunjungan, '', true) ?>
    <?= $baris($nihil, '', true) ?>
    <tr>
        <td>Rata-rata temuan per hari</td>
        <?php foreach ($p as $r): ?><td class="num"><?= str_replace('.', ',', (string) round($r['grand'] / max(1, $r['hari']), 1)) ?></td><?php endforeach; ?>
        <?php $a1 = $p[1]['grand'] / max(1, $p[1]['hari']);
        foreach ($p as $i => $r): if ($i === 1) continue; $bi = $r['grand'] / max(1, $r['hari']); ?>
        <td class="num"><?= $deltaHtml($a1 > 0 ? round(($bi - $a1) / $a1 * 100, 1) : null) ?></td>
        <?php endforeach; ?>
    </tr>
    <tr>
        <td>Panjang periode (hari)</td>
        <?php foreach ($p as $r): ?><td class="num"><?= $r['hari'] ?></td><?php endforeach; ?>
        <?php for ($k = 1; $k < count($p); $k++): ?><td></td><?php endfor; ?>
    </tr>
</tbody>
</table>
</div>

<?= view('_laporan/_ttd', ['signatories' => $signatories]) ?>

<div class="doc-footer">
    <span>Mall Intelligence Center &mdash; Compare Periode Pest Control</span>
    <span><?= $mallLabel ?></span>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    if (typeof Chart === 'undefined') return;
    const ink = 'rgba(51,65,85,.75)';
    const nId = v => Number(v).toLocaleString('id-ID');
    const warna = <?= json_encode($warna) ?>;
    const label = <?= json_encode($label) ?>;
    Chart.defaults.devicePixelRatio = 2;
    const opsi = { responsive: true, maintainAspectRatio: false, animation: false,
        plugins: { legend: { position: 'top', labels: { color: ink, boxWidth: 9, boxHeight: 9, font: { size: 9 } } } },
        scales: { x: { ticks: { color: ink, font: { size: 9 } }, grid: { display: false } },
                  y: { beginAtZero: true, ticks: { precision: 0, color: ink, font: { size: 9 }, callback: v => nId(v) }, grid: { color: 'rgba(0,0,0,.06)' } } } };
    const ds = d => Object.keys(d).map(i => ({ label: 'P' + i, data: d[i], backgroundColor: warna[i], borderRadius: 2 }));
    <?php $d = []; foreach ($p as $i => $_) $d[$i] = array_map(fn($b) => $b['v'][$i], $itemGrafik); ?>
    new Chart(document.getElementById('cItem'), { type: 'bar', data: { labels: <?= json_encode(array_column($itemGrafik, 'label')) ?>, datasets: ds(<?= json_encode($d) ?>) }, options: opsi });
    <?php $d = []; foreach ($p as $i => $_) $d[$i] = array_map(fn($b) => $b['v'][$i], $perMall); ?>
    new Chart(document.getElementById('cMall'), { type: 'bar', data: { labels: <?= json_encode(array_column($perMall, 'label')) ?>, datasets: ds(<?= json_encode($d) ?>) }, options: opsi });
})();
</script>
</body>
</html>
