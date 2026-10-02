<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Traffic Summary — <?= date('d M Y', strtotime($from)) ?> s/d <?= date('d M Y', strtotime($to)) ?></title>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<?= $this->include('_laporan/_style') ?>
<style>
/* Khusus Traffic Summary (cetak rentang tanggal): tiga halaman tetap. */
@page { @top-right { content: "Traffic Summary"; } }
.kpi-ewalk .kpi-num { color: #2a78d6; }
.kpi-penta .kpi-num { color: #15803d; }
.kpi-amber .kpi-num { color: #a16207; }
.kpi-row { margin-bottom: 12px; }
.kpi-row .kpi-sub + .kpi-sub { margin-top: 1px; }

/* Sorotan (jam tersibuk, hari terbaik, pintu terpadat, pembanding) */
.sorotan { display: flex; gap: 8px; margin-bottom: 18px; }
.sorotan-item {
    flex: 1; min-width: 0; display: flex; align-items: center; gap: 8px;
    padding: 7px 10px; border: 1px solid var(--garis); border-radius: 8px; background: var(--latar);
}
.sorotan-ikon {
    flex: 0 0 26px; width: 26px; height: 26px; border-radius: 6px;
    display: flex; align-items: center; justify-content: center; font-size: 12px;
}
.sorotan-item .s-lbl { font-size: 8px; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; color: var(--redup); }
.sorotan-item .s-val { font-size: 12px; font-weight: 700; color: var(--tinta); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.sorotan-item .s-sub { font-size: 8.5px; color: var(--redup2); }

/* Panel grafik tanpa kotak analisa */
.chart-panel .chart-box + .chart-box { padding-left: 14px; border-left: 1px solid var(--garis-halus); }

/* Tabel */
.main-table th.r, .main-table td.r { text-align: right; }
.main-table td.redup { color: var(--redup); }
.main-table.rapat { margin-bottom: 12px; }
.main-table.rapat th { padding: 4px 7px; font-size: 8.5px; }
.main-table.rapat td { padding: 1.5px 7px; font-size: 9px; line-height: 1.3; }
.main-table tbody tr.peak td { background: #fef3c7 !important; font-weight: 600; color: var(--tinta); }
.main-table tbody tr.nodata td { color: #cbd5e1; }

/* Weekday vs weekend: satu baris per segmen supaya rekap harian muat satu halaman */
.kpi-row.ringkas { margin-bottom: 12px; }
.kpi-row.ringkas .kpi-box { padding: 7px 12px 7px 16px; }
.kpi-box .wd-baris { display: flex; gap: 20px; align-items: flex-end; }
.kpi-box .wd-baris .kpi-num { font-size: 17px; }
.kpi-box .wd-baris .kpi-sub { margin-top: 1px; }
.kpi-box .wd-baris .wd-kecil { font-size: 13px; }
.sec-title.rapat { margin-bottom: 8px; }
.doc-footer.rapat { margin-top: 10px; }

/* Event */
.ev-list { display: flex; flex-wrap: wrap; gap: 5px; margin-bottom: 14px; }
.ev-list .chip .ev-per { font-weight: 400; color: var(--redup); }

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
$n    = fn(int $v) => number_format($v, 0, ',', '.');
$fmt  = fn(int $v) => $v > 0 ? number_format($v, 0, ',', '.') : '—';

$totalVehicle   = $vehicles['mobil'] + $vehicles['motor'] + $vehicles['mobil_box'] + $vehicles['truck'] + $vehicles['bus'] + $vehicles['mobil_free'] + $vehicles['motor_free'];
$hasExtraVehicle = ($vehicles['mobil_box'] + $vehicles['truck'] + $vehicles['bus'] + $vehicles['mobil_free'] + $vehicles['motor_free']) > 0;
$maxHourTotal = max(array_column($hours, 'total') ?: [0]);
$maxDayTotal  = max(array_column($days,  'total') ?: [0]);

$fromFmt = date('d M Y', strtotime($from));
$toFmt   = date('d M Y', strtotime($to));
$prevFmt = date('d M', strtotime($prevFrom)) . '–' . date('d M Y', strtotime($prevTo));

// Chart data arrays
$chartDates = array_column($days, 'date_fmt');
$chartEwalk = array_column($days, 'ewalk');
$chartPenta = array_column($days, 'pentacity');
$chartMobil    = array_column($days, 'mobil');
$chartMotor    = array_column($days, 'motor');
$chartMobilBox  = array_column($days, 'mobil_box');
$chartTruck     = array_column($days, 'truck');
$chartBus       = array_column($days, 'bus');
$chartMobilFree = array_column($days, 'mobil_free');
$chartMotorFree = array_column($days, 'motor_free');
$vPrintDatasets = array_filter([
    ['Mobil',      $chartMobil,     'rgba(245,158,11,0.8)'],
    ['Motor',      $chartMotor,     'rgba(239,68,68,0.75)'],
    ['Box',        $chartMobilBox,  'rgba(99,102,241,0.8)'],
    ['Truk',       $chartTruck,     'rgba(139,92,246,0.8)'],
    ['Bus',        $chartBus,       'rgba(16,185,129,0.8)'],
    ['Mobil Free', $chartMobilFree, 'rgba(14,165,233,0.8)'],
    ['Motor Free', $chartMotorFree, 'rgba(236,72,153,0.8)'],
], fn($d) => array_sum($d[1]) > 0);
$chartHours = array_column($hours, 'jam');
$chartHrEw  = array_column($hours, 'ewalk');
$chartHrPt  = array_column($hours, 'pentacity');

$doorEwalkLabels = array_column($doorEwalk, 'pintu');
$doorEwalkVals   = array_map('intval', array_column($doorEwalk, 'total'));
$doorPentaLabels = array_column($doorPenta, 'pintu');
$doorPentaVals   = array_map('intval', array_column($doorPenta, 'total'));
?>

<a class="tautan-kembali no-print" href="<?= base_url('traffic/summary') ?>?from=<?= $from ?>&to=<?= $to ?>">← Kembali ke Summary</a>
<button class="btn-print no-print" onclick="window.print()">Cetak</button>

<!-- ════════════════════ HALAMAN 1 — OVERVIEW & CHARTS ═══════════════════ -->

<div class="doc-header">
    <div>
        <div class="title">Traffic Summary — eWalk &amp; Pentacity</div>
        <div class="sub"><?= $fromFmt ?> — <?= $toFmt ?></div>
        <div class="org">PT. Wulandari Bangun Laksana Tbk. &nbsp;·&nbsp; Mall Intelligence Center v1.4</div>
    </div>
    <div class="meta">
        Generate: <?= date('d M Y H:i') ?><br>
        <?= count($days) ?> hari data<br>
        Pembanding: <?= $prevFmt ?>
    </div>
</div>

<!-- KPI -->
<?php $vsAksen = $changePct === null ? '#94a3b8' : ($changePct >= 0 ? '#16a34a' : '#dc2626'); ?>
<div class="kpi-row">
    <div class="kpi-box kpi-total">
        <div class="kpi-label">Total Pengunjung</div>
        <div class="kpi-num"><?= $n($totalVisitor) ?></div>
        <div class="kpi-sub">eWalk + Pentacity</div>
    </div>
    <div class="kpi-box kpi-ewalk">
        <div class="kpi-label">eWalk</div>
        <div class="kpi-num"><?= $n($totalEwalk) ?></div>
        <?php if ($totalVisitor > 0): ?>
        <div class="kpi-sub"><?= round($totalEwalk / $totalVisitor * 100) ?>% dari total</div>
        <?php endif; ?>
    </div>
    <div class="kpi-box kpi-penta">
        <div class="kpi-label">Pentacity</div>
        <div class="kpi-num"><?= $n($totalPenta) ?></div>
        <?php if ($totalVisitor > 0): ?>
        <div class="kpi-sub"><?= round($totalPenta / $totalVisitor * 100) ?>% dari total</div>
        <?php endif; ?>
    </div>
    <div class="kpi-box kpi-amber">
        <div class="kpi-label">Kendaraan</div>
        <div class="kpi-num"><?= $n($totalVehicle) ?></div>
        <div class="kpi-sub"><?= $n($vehicles['mobil']) ?> mobil · <?= $n($vehicles['motor']) ?> motor</div>
        <?php if ($hasExtraVehicle): ?>
        <div class="kpi-sub"><?php
            $parts = [];
            if ($vehicles['mobil_box']  > 0) $parts[] = $n($vehicles['mobil_box'])  . ' box';
            if ($vehicles['truck']      > 0) $parts[] = $n($vehicles['truck'])      . ' truk';
            if ($vehicles['bus']        > 0) $parts[] = $n($vehicles['bus'])        . ' bus';
            if ($vehicles['mobil_free'] > 0) $parts[] = $n($vehicles['mobil_free']) . ' mobil free';
            if ($vehicles['motor_free'] > 0) $parts[] = $n($vehicles['motor_free']) . ' motor free';
            echo implode(' · ', $parts);
        ?></div>
        <?php endif; ?>
    </div>
    <div class="kpi-box kpi-avg">
        <div class="kpi-label">Rata-rata / Hari</div>
        <div class="kpi-num"><?= $n($avgDaily) ?></div>
        <div class="kpi-sub">pengunjung aktif</div>
    </div>
    <div class="kpi-box" style="--aksen:<?= $vsAksen ?>">
        <div class="kpi-label">vs Periode Sebelumnya</div>
        <div class="kpi-num" style="color:<?= $changePct === null ? '#64748b' : ($changePct >= 0 ? '#15803d' : '#b91c1c') ?>">
            <?= $changePct === null ? '—' : ($changePct >= 0 ? '+' : '') . $changePct . '%' ?>
        </div>
        <div class="kpi-sub"><?= $prevTotal > 0 ? $n($prevTotal) . ' org' : 'belum ada data' ?></div>
    </div>
</div>

<!-- Sorotan -->
<?php
$insights = array_filter([
    $peakHour ? ['icon' => '🕐', 'bg' => '#fef3c7', 'lbl' => 'Jam Tersibuk',             'val' => $peakHour,               'sub' => $n($peakHourVal) . ' pengunjung'] : null,
    $bestDay  ? ['icon' => '🏆', 'bg' => '#dcfce7', 'lbl' => 'Hari Terbaik',             'val' => $bestDay,                'sub' => $n($bestDayVal) . ' pengunjung']  : null,
    ! empty($doorEwalk) ? ['icon' => '🚪', 'bg' => '#dbeafe', 'lbl' => 'Pintu Terpadat eWalk',     'val' => $doorEwalk[0]['pintu'], 'sub' => $n((int)$doorEwalk[0]['total']) . ' org'] : null,
    ! empty($doorPenta) ? ['icon' => '🚪', 'bg' => '#d1fae5', 'lbl' => 'Pintu Terpadat Pentacity', 'val' => $doorPenta[0]['pintu'], 'sub' => $n((int)$doorPenta[0]['total']) . ' org'] : null,
    $changePct !== null && $prevTotal > 0 ? [
        'icon' => $changePct >= 0 ? '📈' : '📉',
        'bg'   => $changePct >= 0 ? '#dcfce7' : '#fee2e2',
        'lbl'  => 'vs ' . $prevFmt,
        'val'  => ($changePct >= 0 ? '+' : '') . $changePct . '%',
        'sub'  => 'sebelumnya ' . $n($prevTotal) . ' org',
    ] : null,
]);
if (! empty($insights)):
?>
<div class="sorotan">
<?php foreach ($insights as $ins): ?>
    <div class="sorotan-item">
        <div class="sorotan-ikon" style="background:<?= $ins['bg'] ?>"><?= $ins['icon'] ?></div>
        <div style="min-width:0">
            <div class="s-lbl"><?= $ins['lbl'] ?></div>
            <div class="s-val"><?= esc($ins['val']) ?></div>
            <div class="s-sub"><?= $ins['sub'] ?></div>
        </div>
    </div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Grafik: Traffic Harian (lebar) + per Jam (lebih sempit) -->
<div class="sec-title"><span>Grafik Traffic Pengunjung</span>
    <span class="sec-sub">harian &amp; distribusi per jam · <?= $fromFmt ?> — <?= $toFmt ?></span></div>
<div class="chart-panel">
    <div class="chart-box" style="flex:3">
        <div class="chart-title">Traffic Pengunjung Harian</div>
        <div class="chart-wrap" style="height:<?= count($days) > 30 ? '50mm' : '54mm' ?>">
            <canvas id="dailyChart"></canvas>
        </div>
    </div>
    <div class="chart-box" style="flex:2">
        <div class="chart-title">Distribusi per Jam</div>
        <div class="chart-wrap" style="height:<?= count($days) > 30 ? '50mm' : '54mm' ?>">
            <canvas id="hourChart"></canvas>
        </div>
    </div>
</div>

<div class="doc-footer">
    <span>Mall Intelligence Center v1.4 · PT. Wulandari Bangun Laksana Tbk.</span>
    <span>KONFIDENSIAL — Hanya untuk internal perusahaan</span>
</div>


<!-- ════════════════════ HALAMAN 2 — DATA HARIAN ═══════════════════════ -->
<div class="putus-halaman"></div>

<?php if ($wdTotal + $weTotal > 0): ?>
<div class="sec-title rapat"><span>Weekdays vs Weekend</span>
    <span class="sec-sub"><?= $fromFmt ?> — <?= $toFmt ?></span></div>
<div class="kpi-row ringkas">
    <div class="kpi-box" style="--aksen:#6366f1">
        <div class="kpi-label" style="color:#6366f1">Weekdays — Senin s/d Kamis</div>
        <div class="wd-baris">
            <div><div class="kpi-num"><?= $n($wdTotal) ?></div><div class="kpi-sub">total pengunjung</div></div>
            <div><div class="kpi-num wd-kecil"><?= $n($wdAvg) ?></div><div class="kpi-sub">rata-rata/hari (<?= $wdDays ?> hari aktif)</div></div>
            <div><div class="kpi-sub">eWalk: <?= $n($wdEwalk) ?> &nbsp;·&nbsp; Pentacity: <?= $n($wdPenta) ?></div></div>
        </div>
    </div>
    <div class="kpi-box" style="--aksen:#f59e0b">
        <div class="kpi-label" style="color:#d97706">Weekend — Jumat s/d Minggu</div>
        <div class="wd-baris">
            <div><div class="kpi-num"><?= $n($weTotal) ?></div><div class="kpi-sub">total pengunjung</div></div>
            <div><div class="kpi-num wd-kecil"><?= $n($weAvg) ?></div><div class="kpi-sub">rata-rata/hari (<?= $weDays ?> hari aktif)</div></div>
            <div><div class="kpi-sub">eWalk: <?= $n($weEwalk) ?> &nbsp;·&nbsp; Pentacity: <?= $n($wePenta) ?></div></div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="sec-title rapat"><span>Traffic Harian — Detail per Tanggal</span>
    <span class="sec-sub">★ = hari dengan traffic tertinggi (baris kuning)</span></div>
<table class="main-table rapat">
    <thead>
        <tr>
            <th style="width:68px">Tanggal</th>
            <th class="r" style="width:72px">eWalk</th>
            <th class="r" style="width:72px">Pentacity</th>
            <th class="r" style="width:72px">Total</th>
            <th class="r" style="width:55px">Mobil</th>
            <th class="r" style="width:55px">Motor</th>
            <?php if ($hasExtraVehicle): ?>
            <th class="r" style="width:50px">Box</th>
            <th class="r" style="width:50px">Truk</th>
            <th class="r" style="width:50px">Bus</th>
            <th class="r" style="width:55px">Mobil Free</th>
            <th class="r" style="width:55px">Motor Free</th>
            <?php endif; ?>
            <th class="r" style="width:65px">Kendaraan</th>
            <th class="r" style="width:52px">% Total</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($days as $row):
        $rowVehicle = $row['mobil'] + $row['motor'] + $row['mobil_box'] + $row['truck'] + $row['bus'] + $row['mobil_free'] + $row['motor_free'];
        $noData = $row['total'] === 0 && $row['mobil'] === 0;
        $isBest = $row['total'] === $maxDayTotal && $maxDayTotal > 0;
    ?>
        <tr class="<?= $noData ? 'nodata' : ($isBest ? 'peak' : '') ?>">
            <td><?= $row['date_fmt'] ?><?= $isBest ? ' ★' : '' ?></td>
            <td class="r"><?= $fmt($row['ewalk'])     ?></td>
            <td class="r"><?= $fmt($row['pentacity']) ?></td>
            <td class="r" style="font-weight:<?= $row['total'] > 0 ? '700' : 'normal' ?>"><?= $fmt($row['total']) ?></td>
            <td class="r"><?= $fmt($row['mobil'])  ?></td>
            <td class="r"><?= $fmt($row['motor'])  ?></td>
            <?php if ($hasExtraVehicle): ?>
            <td class="r"><?= $fmt($row['mobil_box'])  ?></td>
            <td class="r"><?= $fmt($row['truck'])      ?></td>
            <td class="r"><?= $fmt($row['bus'])        ?></td>
            <td class="r"><?= $fmt($row['mobil_free']) ?></td>
            <td class="r"><?= $fmt($row['motor_free']) ?></td>
            <?php endif; ?>
            <td class="r"><?= $fmt($rowVehicle) ?></td>
            <td class="r redup"><?= $totalVisitor > 0 && $row['total'] > 0 ? round($row['total'] / $totalVisitor * 100, 1) . '%' : '—' ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr class="tot">
            <td>TOTAL</td>
            <td class="r"><?= $n($totalEwalk) ?></td>
            <td class="r"><?= $n($totalPenta) ?></td>
            <td class="r"><?= $n($totalVisitor) ?></td>
            <td class="r"><?= $n($vehicles['mobil']) ?></td>
            <td class="r"><?= $n($vehicles['motor']) ?></td>
            <?php if ($hasExtraVehicle): ?>
            <td class="r"><?= $n($vehicles['mobil_box'])  ?></td>
            <td class="r"><?= $n($vehicles['truck'])      ?></td>
            <td class="r"><?= $n($vehicles['bus'])        ?></td>
            <td class="r"><?= $n($vehicles['mobil_free']) ?></td>
            <td class="r"><?= $n($vehicles['motor_free']) ?></td>
            <?php endif; ?>
            <td class="r"><?= $n($totalVehicle) ?></td>
            <td class="r">100%</td>
        </tr>
    </tfoot>
</table>



<!-- ════════════════════ HALAMAN 3 — JAM, KENDARAAN & PINTU ═══════════ -->
<div class="putus-halaman"></div>

<div class="sec-title rapat"><span>Kendaraan Harian — Semua Tipe</span>
    <span class="sec-sub"><?= $fromFmt ?> — <?= $toFmt ?></span></div>
<div class="chart-panel" style="margin-bottom:12px">
    <div class="chart-box">
        <div class="chart-wrap" style="height:33mm">
            <canvas id="vehicleChart"></canvas>
        </div>
    </div>
</div>

<!-- Tabel per jam + per pintu (3 kolom) -->
<div class="duo">

    <div>
        <div class="sec-title rapat"><span>Traffic per Jam</span></div>
        <table class="main-table rapat">
            <thead>
                <tr>
                    <th>Jam</th>
                    <th class="r">eWalk</th>
                    <th class="r">Pentacity</th>
                    <th class="r">Total</th>
                    <th class="r">%</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($hours as $row):
                $isPeak = $row['total'] === $maxHourTotal && $maxHourTotal > 0;
            ?>
                <tr class="<?= $isPeak ? 'peak' : '' ?>">
                    <td><?= $row['jam'] ?><?= $isPeak ? ' ★' : '' ?></td>
                    <td class="r"><?= $n($row['ewalk'])     ?></td>
                    <td class="r"><?= $n($row['pentacity']) ?></td>
                    <td class="r" style="font-weight:<?= $isPeak ? '700' : 'normal' ?>"><?= $n($row['total']) ?></td>
                    <td class="r redup"><?= $totalVisitor > 0 && $row['total'] > 0 ? round($row['total'] / $totalVisitor * 100, 1) . '%' : '—' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="tot">
                    <td>TOTAL</td>
                    <td class="r"><?= $n($totalEwalk) ?></td>
                    <td class="r"><?= $n($totalPenta) ?></td>
                    <td class="r"><?= $n($totalVisitor) ?></td>
                    <td class="r">—</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div>
        <div class="sec-title rapat"><span>Per Pintu — eWalk</span></div>
        <?php if (empty($doorEwalk)): ?>
        <div class="catatan">Belum ada data.</div>
        <?php else: ?>
        <table class="main-table rapat">
            <thead>
                <tr>
                    <th>Pintu</th>
                    <th class="r">Total</th>
                    <th class="r">%</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($doorEwalk as $d): ?>
                <tr>
                    <td><?= esc($d['pintu']) ?></td>
                    <td class="r"><?= $n((int)$d['total']) ?></td>
                    <td class="r redup"><?= $totalEwalk > 0 ? round($d['total'] / $totalEwalk * 100) . '%' : '—' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="tot"><td>TOTAL</td><td class="r"><?= $n($totalEwalk) ?></td><td class="r">100%</td></tr>
            </tfoot>
        </table>
        <?php endif; ?>
    </div>

    <div>
        <div class="sec-title rapat"><span>Per Pintu — Pentacity</span></div>
        <?php if (empty($doorPenta)): ?>
        <div class="catatan">Belum ada data.</div>
        <?php else: ?>
        <table class="main-table rapat">
            <thead>
                <tr>
                    <th>Pintu</th>
                    <th class="r">Total</th>
                    <th class="r">%</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($doorPenta as $d): ?>
                <tr>
                    <td><?= esc($d['pintu']) ?></td>
                    <td class="r"><?= $n((int)$d['total']) ?></td>
                    <td class="r redup"><?= $totalPenta > 0 ? round($d['total'] / $totalPenta * 100) . '%' : '—' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="tot"><td>TOTAL</td><td class="r"><?= $n($totalPenta) ?></td><td class="r">100%</td></tr>
            </tfoot>
        </table>
        <?php endif; ?>
    </div>

</div>

<?php if (! empty($periodEvents)): ?>
<div class="sec-title rapat"><span>Event dalam Periode Ini</span>
    <span class="sec-sub"><?= count($periodEvents) ?> event</span></div>
<div class="ev-list">
<?php foreach ($periodEvents as $ev):
    $evEnd = date('d M Y', strtotime($ev['start_date'] . ' +' . ($ev['event_days'] - 1) . ' days'));
?>
    <span class="chip"><span><?= esc($ev['name']) ?></span><span class="ev-per"><?= date('d M Y', strtotime($ev['start_date'])) ?> – <?= $evEnd ?></span></span>
<?php endforeach; ?>
</div>
<?php endif; ?>

<div class="doc-footer rapat">
    <span>Mall Intelligence Center v1.4 · PT. Wulandari Bangun Laksana Tbk.</span>
    <span>★ = hari / jam dengan traffic tertinggi</span>
    <span>KONFIDENSIAL — Hanya untuk internal perusahaan</span>
</div>


<!-- ════════════════════ CHARTS JS ═══════════════════════════════════ -->
<script>
Chart.defaults.animation = false;
Chart.defaults.font.family = "'Inter', Arial, sans-serif";
Chart.defaults.color = 'rgba(51,65,85,.75)';
Chart.defaults.devicePixelRatio = 2;

const tickFmt = v => v > 0 ? v.toLocaleString('id-ID') : '';
const smallTick = { font: { size: 8 } };
const smallLegend = { position: 'top', labels: { usePointStyle: true, pointStyle: 'circle', boxWidth: 7, boxHeight: 7, padding: 10, font: { size: 8.5 } } };

// ── Daily Chart ───────────────────────────────────────────────────────
new Chart(document.getElementById('dailyChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($chartDates) ?>,
        datasets: [
            {
                label: 'eWalk',
                data:  <?= json_encode($chartEwalk) ?>,
                backgroundColor: '#2a78d6',
                borderRadius: 2, borderSkipped: false
            },
            {
                label: 'Pentacity',
                data:  <?= json_encode($chartPenta) ?>,
                backgroundColor: '#1baf7a',
                borderRadius: 2, borderSkipped: false
            }
        ]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: smallLegend },
        scales: {
            x: { ticks: { ...smallTick, maxRotation: 45, autoSkip: true, maxTicksLimit: 20 }, grid: { display: false } },
            y: { beginAtZero: true, ticks: { ...smallTick, callback: tickFmt } }
        }
    }
});

// ── Hourly Chart ──────────────────────────────────────────────────────
new Chart(document.getElementById('hourChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($chartHours) ?>,
        datasets: [
            {
                label: 'eWalk',
                data:  <?= json_encode($chartHrEw) ?>,
                borderColor: 'rgba(37,99,235,1)',
                backgroundColor: 'rgba(37,99,235,0.1)',
                tension: 0.4, fill: true, pointRadius: 3, borderWidth: 1.5
            },
            {
                label: 'Pentacity',
                data:  <?= json_encode($chartHrPt) ?>,
                borderColor: 'rgba(5,150,105,1)',
                backgroundColor: 'rgba(5,150,105,0.1)',
                tension: 0.4, fill: true, pointRadius: 3, borderWidth: 1.5
            }
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

// ── Vehicle Chart ─────────────────────────────────────────────────────
new Chart(document.getElementById('vehicleChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($chartDates) ?>,
        datasets: [
            <?php foreach ($vPrintDatasets as [$vl, $vd, $vc]): ?>
            { label: '<?= $vl ?>', data: <?= json_encode($vd) ?>, backgroundColor: '<?= $vc ?>', borderRadius: 2, borderSkipped: false },
            <?php endforeach; ?>
        ]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: smallLegend },
        scales: {
            x: { ticks: { ...smallTick, maxRotation: 45, autoSkip: true, maxTicksLimit: 20 }, grid: { display: false } },
            y: { beginAtZero: true, ticks: { ...smallTick, callback: tickFmt } }
        }
    }
});

// ── Auto-print setelah chart render ──────────────────────────────────
window.addEventListener('load', function () {
    setTimeout(function () { window.print(); }, 800);
});
</script>
</body>
</html>
