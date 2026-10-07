<?php
use App\Libraries\PestRekap as PR;
use App\Models\PestVisitModel;

$nf  = fn($v) => PR::angka($v);
$n0  = fn($v) => $v > 0 ? PR::angka($v) : '—';
$dari = $r['dari']; $sampai = $r['sampai'];
$rentangLabel = PR::rentang($dari, $sampai);
$prevLabel    = PR::rentang($prev['dari'], $prev['sampai']);
$mallLabel    = $mall ? PestVisitModel::MALLS[$mall] : 'eWalk & Pentacity';
$rc = $r['rincian'];
$modeLabel = ['harian' => 'per Hari', 'mingguan' => 'per Minggu', 'bulanan' => 'per Bulan'][$rc['mode']];
$deltaTotal = PR::pct($r['grand'], $prev['grand']);

// Temuan pest NAIK itu buruk — warna sengaja dibalik dari laporan pendapatan
// (sama dengan Laporan Bulanan Pest).
$deltaHtml = function (?float $pct) {
    if ($pct === null) return '<span class="subnote">tanpa pembanding</span>';
    $cls = $pct <= 0 ? 'delta-up' : 'delta-down';
    return '<span class="' . $cls . '">' . ($pct >= 0 ? '▲' : '▼') . ' ' . PR::persen($pct) . '</span>';
};
$itemGrafik = array_values(array_filter($items, fn($it) => ($r['perItem'][(int) $it['id']] ?? 0) > 0));
$adaRincian = array_sum(array_column($rc['ember'], 'total')) > 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Rekap Pest Control — <?= $rentangLabel ?></title>
<?= view('_laporan/_style') ?>
<style>
@page { @top-right { content: "Rekap Periode Pest Control"; } }
.main-table th.num { text-align: right; }
.kpi-num.kpi-teks { font-size: 15px; padding-top: 3px; }
.main-table tr.baris-impor td { background: #fdf6e3 !important; font-style: italic; }
.main-table tr.tanpa-catatan td { color: #b6c0cd; }
.main-table.rapat th { padding: 4px 6px; font-size: 8.5px; }
.main-table.rapat td { padding: 2px 6px; font-size: 9.5px; }
.grafik-kosong {
    position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; text-align: center;
    padding: 0 16px; border: 1px dashed var(--garis); border-radius: 8px; background: #fbfcfe;
    font-size: 10px; font-style: italic; color: var(--redup2);
}
.tautan-kembali {
    position: fixed; top: 16px; left: 16px; z-index: 50;
    padding: 8px 14px; border-radius: 8px; background: #fff; border: 1px solid var(--garis);
    color: var(--navy-2); font: 600 12px 'Inter', Arial, sans-serif; text-decoration: none;
    box-shadow: 0 4px 14px rgba(9,21,40,.12);
}
.tautan-kembali:hover { border-color: var(--emas); }
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

<a class="tautan-kembali no-print" href="<?= base_url('pest/rekap') . '?' . http_build_query(array_filter(['from' => $dari, 'to' => $sampai, 'mall' => $mall])) ?>">← Kembali ke Rekap</a>
<button class="btn-print no-print" onclick="window.print()">Cetak</button>

<div class="doc-header">
    <div>
        <div class="title">Rekap Periode — Pest Control</div>
        <div class="sub"><?= $rentangLabel ?> &middot; <?= $mallLabel ?></div>
        <div class="org">PT. Wulandari Bangun Laksana Tbk. &mdash; Mall Intelligence Center</div>
    </div>
    <div class="meta">
        Dicetak oleh: <?= esc($printedBy) ?><br>
        Tanggal cetak: <?= $printedAt ?><br>
        <?= $r['hari'] ?> hari &middot; pembanding <?= $prevLabel ?>
    </div>
</div>

<div class="kpi-row">
    <div class="kpi-box kpi-blue">
        <div class="kpi-label">Total Temuan</div>
        <div class="kpi-num"><?= $nf($r['grand']) ?></div>
        <div class="kpi-sub"><?= $deltaHtml($deltaTotal) ?> vs <?= $prevLabel ?> (<?= $nf($prev['grand']) ?>)</div>
    </div>
    <?php if (! $mall): foreach (PestVisitModel::MALLS as $mk => $ml):
        $v = $r['totalMall'][$mk] ?? 0; ?>
    <div class="kpi-box <?= $mk === 'ewalk' ? 'kpi-ewalk' : 'kpi-penta' ?>">
        <div class="kpi-label"><?= $ml ?></div>
        <div class="kpi-num"><?= $nf($v) ?></div>
        <div class="kpi-sub"><?= $deltaHtml(PR::pct($v, $prev['totalMall'][$mk] ?? 0)) ?>
            <?= $r['grand'] > 0 ? ' · ' . round($v / $r['grand'] * 100) . '% total' : '' ?></div>
    </div>
    <?php endforeach; else:
        $top = $r['perItem']; arsort($top); $topId = array_key_first($top);
        $topNama = $topId ? (array_column($items, 'nama', 'id')[$topId] ?? '—') : '—'; ?>
    <div class="kpi-box kpi-amber">
        <div class="kpi-label">Temuan Terbanyak</div>
        <div class="kpi-num kpi-teks"><?= $topId && $top[$topId] > 0 ? esc($topNama) : '—' ?></div>
        <div class="kpi-sub"><?= $topId && $top[$topId] > 0 ? $nf($top[$topId]) . ' temuan' : 'tidak ada temuan' ?></div>
    </div>
    <?php endif; ?>
    <div class="kpi-box kpi-purple">
        <div class="kpi-label">Kunjungan Tercatat</div>
        <div class="kpi-num"><?= $nf($r['jmlKunjungan']) ?></div>
        <div class="kpi-sub"><?= $nf($r['jmlNihil']) ?> nihil temuan<?= $r['grandLegacy'] > 0 ? ' · + ' . count($r['legacyDipakai']) . ' rekap impor' : '' ?></div>
    </div>
</div>

<?php foreach ($catatan as $c): ?>
<div class="catatan <?= str_starts_with($c, 'TIDAK') ? 'waspada' : 'info' ?>"><?= esc($c) ?></div>
<?php endforeach; ?>

<div class="sec-title">Rekap per Item &amp; Mall<span class="sec-sub"><?= $rentangLabel ?> vs <?= $prevLabel ?></span></div>
<div class="tabel-gulir">
<table class="main-table">
<thead><tr>
    <th>Item Temuan</th>
    <?php foreach ($r['malls'] as $mk): ?><th class="num"><?= PestVisitModel::MALLS[$mk] ?></th><?php endforeach; ?>
    <?php if (count($r['malls']) > 1): ?><th class="num">Total</th><?php endif; ?>
    <th class="num">Periode Lalu</th>
    <th class="num">Perubahan</th>
</tr></thead>
<tbody>
<?php foreach ($items as $it): $id = (int) $it['id'];
    $t = $r['perItem'][$id] ?? 0; $l = $prev['perItem'][$id] ?? 0; ?>
<tr>
    <td><?= esc($it['nama']) ?><?= (int) $it['aktif'] ? '' : ' <span class="subnote">(nonaktif)</span>' ?></td>
    <?php foreach ($r['malls'] as $mk): $v = $r['perMall'][$mk][$id] ?? 0; ?>
    <td class="<?= $v ? 'num' : 'zero' ?>"><?= $n0($v) ?></td>
    <?php endforeach; ?>
    <?php if (count($r['malls']) > 1): ?><td class="num"><strong><?= $n0($t) ?></strong></td><?php endif; ?>
    <td class="<?= $l ? 'num' : 'zero' ?>"><?= $n0($l) ?></td>
    <td class="num"><?= $t === 0 && $l === 0 ? '<span class="zero">—</span>' : $deltaHtml(PR::pct($t, $l)) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
<tfoot><tr>
    <td>TOTAL</td>
    <?php foreach ($r['malls'] as $mk): ?><td class="num"><?= $nf($r['totalMall'][$mk] ?? 0) ?></td><?php endforeach; ?>
    <?php if (count($r['malls']) > 1): ?><td class="num"><?= $nf($r['grand']) ?></td><?php endif; ?>
    <td class="num"><?= $nf($prev['grand']) ?></td>
    <td class="num"><?= $deltaHtml($deltaTotal) ?></td>
</tr></tfoot>
</table>
</div>
<?php if ($catatanPrev): ?>
<div class="catatan">Periode pembanding (<?= $prevLabel ?>): <?= esc(implode(' ', $catatanPrev)) ?></div>
<?php endif; ?>

<div class="sec-title">Ringkasan Analisa<span class="sec-sub">disusun otomatis dari data periode ini</span></div>
<div class="chart-panel">
    <div class="insight-box">
        <div class="insight-title">Catatan</div>
        <ul class="insight-list"><?php foreach ($analisa as $a): ?><li><?= esc($a) ?></li><?php endforeach; ?></ul>
    </div>
    <div class="chart-box">
        <div class="chart-title">Temuan <?= $modeLabel ?></div>
        <div class="chart-wrap"><canvas id="cEmber"></canvas><?php if (! $adaRincian): ?>
            <div class="grafik-kosong">Tidak ada temuan kunjungan untuk dirinci<?= $rc['legacy']['total'] > 0 ? ' — angka periode ini berasal dari rekap impor bulanan' : '' ?>.</div><?php endif; ?></div>
    </div>
    <div class="chart-box">
        <div class="chart-title">Temuan per Item</div>
        <div class="chart-wrap"><canvas id="cItem"></canvas><?php if (! $itemGrafik): ?>
            <div class="grafik-kosong">Tidak ada temuan pada periode ini.</div><?php endif; ?></div>
    </div>
</div>

<div class="sec-title">Rincian <?= $modeLabel ?>
    <span class="sec-sub"><?= $rc['mode'] === 'mingguan' ? 'minggu ISO, dipotong di batas rentang' : ($rc['mode'] === 'bulanan' ? 'bulan terpotong ditandai tanggalnya' : 'baris pudar = belum ada catatan kunjungan') ?></span></div>
<div class="tabel-gulir">
<table class="main-table rapat">
<thead><tr>
    <th><?= ['harian' => 'Tanggal', 'mingguan' => 'Minggu', 'bulanan' => 'Bulan'][$rc['mode']] ?></th>
    <?php foreach ($items as $it): ?><th class="num"><?= esc($it['nama']) ?></th><?php endforeach; ?>
    <th class="num">Total</th><th class="num">Kunj.</th>
</tr></thead>
<tbody>
<?php foreach ($rc['ember'] as $e): $kosong = $e['kunjungan'] === 0 && ! $e['legacy']; ?>
<tr class="<?= $kosong ? 'tanpa-catatan' : '' ?>">
    <td><?= esc(PR::labelEmber($e, $rc['mode'])) ?><?= $e['legacy'] ? ' <span class="lencana waspada">impor</span>' : '' ?></td>
    <?php foreach ($items as $it): $v = $e['items'][(int) $it['id']] ?? 0; ?>
    <td class="<?= $v ? 'num' : 'zero' ?>"><?= $n0($v) ?></td>
    <?php endforeach; ?>
    <td class="num"><strong><?= $kosong ? '·' : $nf($e['total']) ?></strong></td>
    <td class="num"><?= $e['kunjungan'] ?: '—' ?></td>
</tr>
<?php endforeach; ?>
<?php if ($rc['legacy']['total'] > 0): ?>
<tr class="baris-impor">
    <td>Rekap impor bulanan (tanpa tanggal)</td>
    <?php foreach ($items as $it): $v = $rc['legacy']['items'][(int) $it['id']] ?? 0; ?>
    <td class="<?= $v ? 'num' : 'zero' ?>"><?= $n0($v) ?></td>
    <?php endforeach; ?>
    <td class="num"><strong><?= $nf($rc['legacy']['total']) ?></strong></td>
    <td class="num"><?= $rc['legacy']['baris'] ?> rekap</td>
</tr>
<?php endif; ?>
</tbody>
<tfoot><tr>
    <td>TOTAL</td>
    <?php foreach ($items as $it): ?><td class="num"><?= $nf($r['perItem'][(int) $it['id']] ?? 0) ?></td><?php endforeach; ?>
    <td class="num"><?= $nf($r['grand']) ?></td>
    <td class="num"><?= $nf($r['jmlKunjungan']) ?></td>
</tr></tfoot>
</table>
</div>
<p class="catatan">Angka 0 berarti "bersih" hanya bila ada kunjungan tercatat; tanda · berarti belum ada catatan sama sekali pada periode itu.</p>

<?= view('_laporan/_ttd', ['signatories' => $signatories]) ?>

<div class="doc-footer">
    <span>Mall Intelligence Center &mdash; Rekap Periode Pest Control</span>
    <span><?= $rentangLabel ?> &middot; <?= $mallLabel ?></span>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    if (typeof Chart === 'undefined') return;
    const ink = 'rgba(51,65,85,.75)';
    const nId = v => Number(v).toLocaleString('id-ID');
    const warnaMall = { ewalk: '#2a78d6', pentacity: '#16a34a' };
    const namaMall  = <?= json_encode(PestVisitModel::MALLS) ?>;
    const malls     = <?= json_encode($r['malls']) ?>;
    Chart.defaults.devicePixelRatio = 2;
    const sumbu = { ticks: { color: ink, font: { size: 9 } }, grid: { display: false } };
    const legenda = { position: 'top', labels: { color: ink, boxWidth: 9, boxHeight: 9, font: { size: 9 } } };

    <?php if ($adaRincian):
        $dataMall = [];
        foreach ($r['malls'] as $mk) $dataMall[$mk] = array_map(fn($e) => ($e['kunjungan'] === 0 && ! $e['legacy']) ? null : ($e['mall'][$mk] ?? 0), $rc['ember']); ?>
    const dm = <?= json_encode($dataMall) ?>;
    new Chart(document.getElementById('cEmber'), {
        type: 'bar',
        data: { labels: <?= json_encode(array_map(fn($e) => PR::labelSumbu($e, $rc['mode']), $rc['ember'])) ?>,
            datasets: malls.map(mk => ({ label: namaMall[mk], data: dm[mk], backgroundColor: warnaMall[mk], borderRadius: 2 })) },
        options: { responsive: true, maintainAspectRatio: false, animation: false,
            plugins: { legend: legenda },
            scales: { x: { ...sumbu, stacked: true, ticks: { ...sumbu.ticks, autoSkip: true, maxRotation: 0 } },
                      y: { stacked: true, beginAtZero: true, ticks: { precision: 0, color: ink, font: { size: 9 }, callback: v => nId(v) }, grid: { color: 'rgba(0,0,0,.06)' } } } }
    });
    <?php endif; ?>

    <?php if ($itemGrafik): ?>
    const di = <?= json_encode(array_combine($r['malls'], array_map(fn($mk) => array_map(fn($it) => $r['perMall'][$mk][(int) $it['id']] ?? 0, $itemGrafik), $r['malls']))) ?>;
    new Chart(document.getElementById('cItem'), {
        type: 'bar',
        data: { labels: <?= json_encode(array_column($itemGrafik, 'nama')) ?>,
            datasets: malls.map(mk => ({ label: namaMall[mk], data: di[mk], backgroundColor: warnaMall[mk], borderRadius: 2 })) },
        options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, animation: false,
            plugins: { legend: legenda },
            scales: { x: { stacked: true, beginAtZero: true, ticks: { precision: 0, color: ink, font: { size: 9 }, callback: v => nId(v) }, grid: { color: 'rgba(0,0,0,.06)' } },
                      y: { ...sumbu, stacked: true } } }
    });
    <?php endif; ?>
})();
</script>
</body>
</html>
