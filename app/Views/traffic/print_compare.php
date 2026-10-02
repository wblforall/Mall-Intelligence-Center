<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Compare Traffic — <?= date('d M Y', strtotime($from1)) ?> vs <?= date('d M Y', strtotime($from2)) ?></title>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<?= $this->include('_laporan/_style') ?>
<style>
/* Khusus Perbandingan Traffic: warna per periode (P1 indigo, P2 oranye, P3 hijau). */
@page { @top-right { content: "Perbandingan Traffic"; } }
:root {
    --c-p1: #6366f1; --c-p1-bg: #eef2ff; --c-p1-border: #c7d2fe;
    --c-p2: #f97316; --c-p2-bg: #fff7ed; --c-p2-border: #fed7aa;
    --c-p3: #10b981; --c-p3-bg: #ecfdf5; --c-p3-border: #a7f3d0;
}
.p1-teks { color: var(--c-p1) !important; }
.p2-teks { color: var(--c-p2) !important; }
.p3-teks { color: var(--c-p3) !important; }

/* Kartu periode */
.kpi-row.periode { margin-bottom: 12px; }
.kpi-row.periode .kpi-box { display: flex; align-items: baseline; gap: 10px; padding-top: 6px; padding-bottom: 6px; }
.kpi-row.periode .kpi-label { margin-bottom: 0; }
.kpi-row.periode .kpi-num { font-size: 12px; font-weight: 700; }

/* Titik warna periode di kepala tabel (latar navy) */
.titik { display: inline-block; width: 7px; height: 7px; border-radius: 50%; margin-right: 5px; vertical-align: 0; }
.titik.p1 { background: #a5b4fc; } .titik.p2 { background: #fdba74; } .titik.p3 { background: #6ee7b7; }

/* Tabel */
.main-table th.r, .main-table td.r { text-align: right; }
.main-table th.c, .main-table td.c { text-align: center; }
.main-table.rapat { margin-bottom: 12px; }
.main-table.rapat th { padding: 4px 7px; font-size: 8.5px; }
.main-table.rapat td { padding: 2px 7px; font-size: 9.5px; line-height: 1.3; }
.main-table td.redup { color: var(--redup); }
.diff-up   { color: #15803d; font-weight: 700; }
.diff-down { color: #b91c1c; font-weight: 700; }
.diff-nil  { color: var(--redup); }
.diff-sub  { font-size: 8px; color: var(--redup); margin-left: 4px; }
.sec-title.rapat { margin-bottom: 8px; }
.doc-footer.rapat { margin-top: 10px; }

/* Event per periode */
.ev-section { margin-bottom: 6px; }
.ev-section .ev-head { font-size: 8px; font-weight: 700; text-transform: uppercase; letter-spacing: .6px; margin-bottom: 3px; }
.ev-list { display: flex; flex-wrap: wrap; gap: 4px; }
.ev-badge { border-radius: 5px; padding: 2px 7px; font-size: 8.5px; }
.ev-badge.p1 { background: var(--c-p1-bg); color: #4338ca; border: 1px solid var(--c-p1-border); }
.ev-badge.p2 { background: var(--c-p2-bg); color: #c2410c; border: 1px solid var(--c-p2-border); }
.ev-badge.p3 { background: var(--c-p3-bg); color: #047857; border: 1px solid var(--c-p3-border); }
.ev-badge .ev-name { font-weight: 700; }
.ev-badge .ev-per  { font-weight: 400; margin-left: 3px; opacity: .8; }

/* Panel grafik tanpa kotak analisa */
.chart-panel .chart-box + .chart-box { padding-left: 14px; border-left: 1px solid var(--garis-halus); }

/* Tautan kembali (layar saja) */
.tautan-kembali {
    position: fixed; top: 16px; left: 16px; z-index: 50;
    padding: 8px 14px; border-radius: 8px; background: #fff; border: 1px solid var(--garis);
    color: var(--navy-2); font: 600 12px 'Inter', Arial, sans-serif; text-decoration: none;
    box-shadow: 0 4px 14px rgba(9,21,40,.12);
}
.tautan-kembali:hover { border-color: var(--emas); }
</style>
</head>
<body>
<?php
$n   = fn(int $v) => number_format($v, 0, ',', '.');
$fmt = fn(int $v) => $v > 0 ? number_format($v, 0, ',', '.') : '—';

if (! function_exists('pctDiffPrint')) {
    function pctDiffPrint(int $a, int $b): ?float {
        if ($a === 0) return null;
        return round(($b - $a) / $a * 100, 1);
    }
}
if (! function_exists('diffCell')) {
    function diffCell(int $base, int $val, string $n): string {
        $pct = pctDiffPrint($base, $val);
        if ($pct === null) return '<span class="diff-nil">—</span>';
        $cls = $pct > 0 ? 'diff-up' : ($pct < 0 ? 'diff-down' : 'diff-nil');
        $pre = $pct > 0 ? '+' : '';
        return '<span class="' . $cls . '">' . $pre . $pct . '%</span><span class="diff-sub">' . $n . '</span>';
    }
}

$p1Label = date('d M Y', strtotime($from1)) . ' — ' . date('d M Y', strtotime($to1));
$p2Label = date('d M Y', strtotime($from2)) . ' — ' . date('d M Y', strtotime($to2));
$p3Label = $hasP3 ? date('d M Y', strtotime($from3)) . ' — ' . date('d M Y', strtotime($to3)) : '';

$hasVehicleData = array_sum(array_column($p1Vehicles, null))
                + array_sum(array_column($p2Vehicles, null))
                + array_sum(array_column($p3Vehicles, null)) > 0;

$vtypes = ['mobil' => 'Mobil', 'motor' => 'Motor', 'mobil_box' => 'Box', 'truck' => 'Truk', 'bus' => 'Bus', 'mobil_free' => 'Mobil Free', 'motor_free' => 'Motor Free'];

$backUrl = base_url('traffic/compare') . '?from1=' . $from1 . '&to1=' . $to1 . '&from2=' . $from2 . '&to2=' . $to2;
if ($hasP3) $backUrl .= '&from3=' . $from3 . '&to3=' . $to3;

// Build combined door maps
$allDoorsEwalk = [];
foreach ($door1Ewalk as $d) $allDoorsEwalk[$d['pintu']] = ['p1' => (int)$d['total'], 'p2' => 0, 'p3' => 0];
foreach ($door2Ewalk as $d) { $allDoorsEwalk[$d['pintu']] ??= ['p1'=>0,'p2'=>0,'p3'=>0]; $allDoorsEwalk[$d['pintu']]['p2'] = (int)$d['total']; }
if ($hasP3) foreach ($door3Ewalk as $d) { $allDoorsEwalk[$d['pintu']] ??= ['p1'=>0,'p2'=>0,'p3'=>0]; $allDoorsEwalk[$d['pintu']]['p3'] = (int)$d['total']; }

$allDoorsPenta = [];
foreach ($door1Penta as $d) $allDoorsPenta[$d['pintu']] = ['p1' => (int)$d['total'], 'p2' => 0, 'p3' => 0];
foreach ($door2Penta as $d) { $allDoorsPenta[$d['pintu']] ??= ['p1'=>0,'p2'=>0,'p3'=>0]; $allDoorsPenta[$d['pintu']]['p2'] = (int)$d['total']; }
if ($hasP3) foreach ($door3Penta as $d) { $allDoorsPenta[$d['pintu']] ??= ['p1'=>0,'p2'=>0,'p3'=>0]; $allDoorsPenta[$d['pintu']]['p3'] = (int)$d['total']; }
uasort($allDoorsEwalk, fn($a,$b) => ($b['p1']+$b['p2']+$b['p3']) <=> ($a['p1']+$a['p2']+$a['p3']));
uasort($allDoorsPenta, fn($a,$b) => ($b['p1']+$b['p2']+$b['p3']) <=> ($a['p1']+$a['p2']+$a['p3']));
?>

<a class="tautan-kembali no-print" href="<?= $backUrl ?>">← Kembali ke Compare</a>
<button class="btn-print no-print" onclick="window.print()">Cetak</button>


<!-- ══════════════════ HALAMAN 1 — OVERVIEW ══════════════════════════ -->

<div class="doc-header">
    <div>
        <div class="title">Perbandingan Traffic — eWalk &amp; Pentacity</div>
        <div class="sub"><?= $hasP3 ? 'Tiga Periode' : 'Dua Periode' ?></div>
        <div class="org">PT. Wulandari Bangun Laksana Tbk. &nbsp;·&nbsp; Mall Intelligence Center v1.4</div>
    </div>
    <div class="meta">
        Generate: <?= date('d M Y H:i') ?><br>
        P1: <?= $p1Label ?><br>
        P2: <?= $p2Label ?>
        <?php if ($hasP3): ?><br>P3: <?= $p3Label ?><?php endif; ?>
    </div>
</div>

<!-- Kartu periode -->
<div class="kpi-row periode">
    <div class="kpi-box" style="--aksen:var(--c-p1)"><div class="kpi-label p1-teks">Periode 1</div><div class="kpi-num"><?= $p1Label ?></div></div>
    <div class="kpi-box" style="--aksen:var(--c-p2)"><div class="kpi-label p2-teks">Periode 2</div><div class="kpi-num"><?= $p2Label ?></div></div>
    <?php if ($hasP3): ?>
    <div class="kpi-box" style="--aksen:var(--c-p3)"><div class="kpi-label p3-teks">Periode 3</div><div class="kpi-num"><?= $p3Label ?></div></div>
    <?php endif; ?>
</div>

<!-- Tabel KPI + Weekday/Weekend berdampingan -->
<div class="duo">

    <div style="flex:3">
        <div class="sec-title rapat"><span>Perbandingan Pengunjung</span></div>
        <table class="main-table rapat">
            <thead>
                <tr>
                    <th>Metrik</th>
                    <th class="r"><span class="titik p1"></span>Periode 1</th>
                    <th class="r"><span class="titik p2"></span>Periode 2</th>
                    <?php if ($hasP3): ?><th class="r"><span class="titik p3"></span>Periode 3</th><?php endif; ?>
                    <th class="c">Selisih P1→P2</th>
                    <?php if ($hasP3): ?><th class="c">Selisih P1→P3</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php
            $visitorRows = [
                ['Total Pengunjung', $p1Total,  $p2Total,  $p3Total],
                ['eWalk',            $p1Ewalk,  $p2Ewalk,  $p3Ewalk],
                ['Pentacity',        $p1Penta,  $p2Penta,  $p3Penta],
            ];
            foreach ($visitorRows as [$lbl, $v1, $v2, $v3]):
            ?>
            <tr>
                <td><?= $lbl ?></td>
                <td class="r p1-teks" style="font-weight:700"><?= $n($v1) ?></td>
                <td class="r p2-teks" style="font-weight:700"><?= $n($v2) ?></td>
                <?php if ($hasP3): ?><td class="r p3-teks" style="font-weight:700"><?= $n($v3) ?></td><?php endif; ?>
                <td class="c"><?= diffCell($v1, $v2, $n($v2)) ?></td>
                <?php if ($hasP3): ?><td class="c"><?= diffCell($v1, $v3, $n($v3)) ?></td><?php endif; ?>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($hasVehicleData): ?>
        <div class="sec-title rapat"><span>Perbandingan Kendaraan</span></div>
        <table class="main-table rapat">
            <thead>
                <tr>
                    <th>Tipe</th>
                    <th class="r"><span class="titik p1"></span>P1</th>
                    <th class="r"><span class="titik p2"></span>P2</th>
                    <?php if ($hasP3): ?><th class="r"><span class="titik p3"></span>P3</th><?php endif; ?>
                    <th class="c">Selisih P1→P2</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($vtypes as $vk => $vl):
                $v1 = $p1Vehicles[$vk] ?? 0;
                $v2 = $p2Vehicles[$vk] ?? 0;
                $v3 = $p3Vehicles[$vk] ?? 0;
                if ($v1 + $v2 + $v3 === 0) continue;
            ?>
            <tr>
                <td><?= $vl ?></td>
                <td class="r p1-teks"><?= $n($v1) ?></td>
                <td class="r p2-teks"><?= $n($v2) ?></td>
                <?php if ($hasP3): ?><td class="r p3-teks"><?= $n($v3) ?></td><?php endif; ?>
                <td class="c"><?= diffCell($v1, $v2, $n($v2)) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <div style="flex:2">
        <div class="sec-title rapat"><span>Weekdays vs Weekend</span></div>
        <table class="main-table rapat">
            <thead>
                <tr>
                    <th>Segmen</th>
                    <th class="r"><span class="titik p1"></span>P1 Total</th>
                    <th class="r">P1 Avg</th>
                    <th class="r"><span class="titik p2"></span>P2 Total</th>
                    <th class="r">P2 Avg</th>
                    <?php if ($hasP3): ?>
                    <th class="r"><span class="titik p3"></span>P3 Total</th>
                    <th class="r">P3 Avg</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ([['Weekdays (Sen–Kam)', 'wd'], ['Weekend (Jum–Min)', 'we']] as [$lbl, $seg]): ?>
            <tr>
                <td><?= $lbl ?></td>
                <td class="r"><?= $n($p1WdWe[$seg]['total']) ?></td>
                <td class="r redup"><?= $n($p1WdWe[$seg]['avg']) ?></td>
                <td class="r"><?= $n($p2WdWe[$seg]['total']) ?></td>
                <td class="r redup"><?= $n($p2WdWe[$seg]['avg']) ?></td>
                <?php if ($hasP3): ?>
                <td class="r"><?= $n($p3WdWe[$seg]['total']) ?></td>
                <td class="r redup"><?= $n($p3WdWe[$seg]['avg']) ?></td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Events per periode -->
        <?php
        $anyEvents = ! empty($p1Events) || ! empty($p2Events) || ($hasP3 && ! empty($p3Events));
        if ($anyEvents):
        ?>
        <div class="sec-title rapat"><span>Event dalam Periode</span></div>
        <?php foreach ([['p1', $p1Events], ['p2', $p2Events]] as [$cls, $evs]):
            if (empty($evs)) continue; ?>
        <div class="ev-section">
            <div class="ev-head <?= $cls ?>-teks">Periode <?= strtoupper(substr($cls,1)) ?></div>
            <div class="ev-list">
            <?php foreach ($evs as $ev):
                $evEnd = date('d M Y', strtotime($ev['start_date'] . ' +' . ($ev['event_days'] - 1) . ' days'));
            ?>
                <div class="ev-badge <?= $cls ?>">
                    <span class="ev-name"><?= esc($ev['name']) ?></span>
                    <span class="ev-per"><?= date('d M Y', strtotime($ev['start_date'])) ?> – <?= $evEnd ?></span>
                </div>
            <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
        <?php if ($hasP3 && ! empty($p3Events)): ?>
        <div class="ev-section">
            <div class="ev-head p3-teks">Periode 3</div>
            <div class="ev-list">
            <?php foreach ($p3Events as $ev):
                $evEnd = date('d M Y', strtotime($ev['start_date'] . ' +' . ($ev['event_days'] - 1) . ' days'));
            ?>
                <div class="ev-badge p3">
                    <span class="ev-name"><?= esc($ev['name']) ?></span>
                    <span class="ev-per"><?= date('d M Y', strtotime($ev['start_date'])) ?> – <?= $evEnd ?></span>
                </div>
            <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>

</div>

<!-- Grafik: harian (bar) + per jam (garis) -->
<div class="chart-panel" style="margin-bottom:0">
    <div class="chart-box" style="flex:3">
        <div class="chart-title">Traffic Harian per Periode (Hari ke-N)</div>
        <div class="chart-wrap" style="height:36mm"><canvas id="dailyChart"></canvas></div>
    </div>
    <div class="chart-box" style="flex:2">
        <div class="chart-title">Distribusi per Jam</div>
        <div class="chart-wrap" style="height:36mm"><canvas id="hourChart"></canvas></div>
    </div>
</div>


<!-- ══════════════════ HALAMAN 2 — DETAIL PINTU ══════════════════════ -->
<div class="putus-halaman"></div>

<div class="sec-title rapat"><span>Detail per Pintu — Perbandingan Periode</span>
    <span class="sec-sub"><?= $p1Label ?> vs <?= $p2Label ?><?= $hasP3 ? ' vs ' . $p3Label : '' ?></span></div>

<div class="duo">

    <?php if (! empty($allDoorsEwalk)): ?>
    <div>
        <div class="sec-title rapat tanpa-nomor"><span>Per Pintu — eWalk</span></div>
        <table class="main-table rapat">
            <thead>
                <tr>
                    <th>Pintu</th>
                    <th class="r"><span class="titik p1"></span>P1</th>
                    <th class="r"><span class="titik p2"></span>P2</th>
                    <?php if ($hasP3): ?><th class="r"><span class="titik p3"></span>P3</th><?php endif; ?>
                    <th class="c">Selisih P1→P2</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($allDoorsEwalk as $pintu => $v): ?>
            <tr>
                <td><?= esc($pintu) ?></td>
                <td class="r"><?= $n($v['p1']) ?></td>
                <td class="r"><?= $n($v['p2']) ?></td>
                <?php if ($hasP3): ?><td class="r"><?= $n($v['p3']) ?></td><?php endif; ?>
                <td class="c"><?= diffCell($v['p1'], $v['p2'], $n($v['p2'])) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="tot">
                    <td>TOTAL</td>
                    <td class="r"><?= $n($p1Ewalk) ?></td>
                    <td class="r"><?= $n($p2Ewalk) ?></td>
                    <?php if ($hasP3): ?><td class="r"><?= $n($p3Ewalk) ?></td><?php endif; ?>
                    <td class="c"><?= diffCell($p1Ewalk, $p2Ewalk, $n($p2Ewalk)) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php endif; ?>

    <?php if (! empty($allDoorsPenta)): ?>
    <div>
        <div class="sec-title rapat tanpa-nomor"><span>Per Pintu — Pentacity</span></div>
        <table class="main-table rapat">
            <thead>
                <tr>
                    <th>Pintu</th>
                    <th class="r"><span class="titik p1"></span>P1</th>
                    <th class="r"><span class="titik p2"></span>P2</th>
                    <?php if ($hasP3): ?><th class="r"><span class="titik p3"></span>P3</th><?php endif; ?>
                    <th class="c">Selisih P1→P2</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($allDoorsPenta as $pintu => $v): ?>
            <tr>
                <td><?= esc($pintu) ?></td>
                <td class="r"><?= $n($v['p1']) ?></td>
                <td class="r"><?= $n($v['p2']) ?></td>
                <?php if ($hasP3): ?><td class="r"><?= $n($v['p3']) ?></td><?php endif; ?>
                <td class="c"><?= diffCell($v['p1'], $v['p2'], $n($v['p2'])) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="tot">
                    <td>TOTAL</td>
                    <td class="r"><?= $n($p1Penta) ?></td>
                    <td class="r"><?= $n($p2Penta) ?></td>
                    <?php if ($hasP3): ?><td class="r"><?= $n($p3Penta) ?></td><?php endif; ?>
                    <td class="c"><?= diffCell($p1Penta, $p2Penta, $n($p2Penta)) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php endif; ?>

</div>

<div class="doc-footer rapat">
    <span>Mall Intelligence Center v1.4 · PT. Wulandari Bangun Laksana Tbk.</span>
    <span>KONFIDENSIAL — Hanya untuk internal perusahaan</span>
</div>


<!-- ══════════════════ CHARTS JS ═════════════════════════════════════ -->
<script>
Chart.defaults.animation = false;
Chart.defaults.font.family = "'Inter', Arial, sans-serif";
Chart.defaults.color = 'rgba(51,65,85,.75)';
Chart.defaults.devicePixelRatio = 2;

const tickFmt    = v => v > 0 ? v.toLocaleString('id-ID') : '';
const smallTick  = { font: { size: 8 } };
const smallLegend = { position: 'top', labels: { usePointStyle: true, pointStyle: 'circle', boxWidth: 7, boxHeight: 7, padding: 10, font: { size: 8.5 } } };

// Daily chart
new Chart(document.getElementById('dailyChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($dayLabels) ?>,
        datasets: [
            { label: 'Periode 1', data: <?= json_encode($p1Daily) ?>, backgroundColor: 'rgba(99,102,241,0.78)', borderRadius: 2 },
            { label: 'Periode 2', data: <?= json_encode($p2Daily) ?>, backgroundColor: 'rgba(249,115,22,0.78)',  borderRadius: 2 }
            <?php if ($hasP3): ?>
            ,{ label: 'Periode 3', data: <?= json_encode($p3Daily) ?>, backgroundColor: 'rgba(16,185,129,0.78)', borderRadius: 2 }
            <?php endif; ?>
        ]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: smallLegend },
        scales: {
            x: { ticks: { ...smallTick, maxRotation: 0, autoSkip: true, maxTicksLimit: 20 }, grid: { display: false } },
            y: { beginAtZero: true, ticks: { ...smallTick, callback: tickFmt } }
        }
    }
});

// Hourly chart
new Chart(document.getElementById('hourChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($chartHours) ?>,
        datasets: [
            { label: 'Periode 1', data: <?= json_encode($p1HourData) ?>, borderColor: 'rgba(99,102,241,1)',  backgroundColor: 'rgba(99,102,241,0.08)',  tension: 0.4, fill: true, pointRadius: 2, borderWidth: 1.5 },
            { label: 'Periode 2', data: <?= json_encode($p2HourData) ?>, borderColor: 'rgba(249,115,22,1)',  backgroundColor: 'rgba(249,115,22,0.08)',  tension: 0.4, fill: true, pointRadius: 2, borderWidth: 1.5 }
            <?php if ($hasP3): ?>
            ,{ label: 'Periode 3', data: <?= json_encode($p3HourData) ?>, borderColor: 'rgba(16,185,129,1)', backgroundColor: 'rgba(16,185,129,0.08)', tension: 0.4, fill: true, pointRadius: 2, borderWidth: 1.5 }
            <?php endif; ?>
        ]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: smallLegend },
        scales: {
            x: { ticks: { ...smallTick, maxRotation: 45 }, grid: { display: false } },
            y: { beginAtZero: true, ticks: { ...smallTick, callback: tickFmt } }
        }
    }
});

window.addEventListener('load', function () {
    setTimeout(function () { window.print(); }, 800);
});
</script>
</body>
</html>
