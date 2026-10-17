<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/creative-main.css') ?>?v=<?= @filemtime(FCPATH . 'css/creative-main.css') ?: 1 ?>">
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php
$idBulan    = ['January'=>'Januari','February'=>'Februari','March'=>'Maret','April'=>'April',
               'May'=>'Mei','June'=>'Juni','July'=>'Juli','August'=>'Agustus',
               'September'=>'September','October'=>'Oktober','November'=>'November','December'=>'Desember'];
$bulanDt    = \DateTime::createFromFormat('Y-m', $bulan);
$bulanLabel = strtr($bulanDt->format('F Y'), $idBulan);
$bulanS     = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
$fTgl = function ($d) use ($bulanS): string {
    if (!$d || $d === '0000-00-00') return '';
    $t = strtotime($d);
    return date('d', $t) . ' ' . $bulanS[(int) date('n', $t) - 1] . ' ' . date('Y', $t);
};

$statusChip   = ['draft' => 'cr-chip-mute', 'review' => 'cr-chip-warn', 'approved' => 'cr-chip-ok', 'revision' => 'cr-chip-bad'];
$statusLabels = ['draft' => 'Draft', 'review' => 'Review', 'approved' => 'Approved', 'revision' => 'Revision'];
$tipeLabels   = ['master_design' => 'Master Design', 'digital' => 'Content Digital', 'cetak' => 'Media Cetak', 'influencer' => 'Influencer',
                 'media_prescon' => 'Media Prescon', 'print' => 'Print'];
$tipeIcons    = ['master_design' => 'vector-pen', 'digital' => 'phone', 'cetak' => 'printer', 'influencer' => 'person-video3',
                 'media_prescon' => 'newspaper', 'print' => 'printer'];

$totalItems = count($rows);
$hasChart   = !empty($chartItems);
$hasInsight = !empty($insightLabels);
$hasTrend   = array_sum($trendReach) + array_sum($trendImpr) + array_sum($trendReal) + array_sum($trendEng) > 0;
if (empty($rows)) { $hasChart = $hasInsight = $hasTrend = false; } // keadaan kosong tak menampilkan kanvas grafik
$rp = fn($v) => 'Rp ' . number_format((int)$v, 0, ',', '.');

// Selisih vs bulan sebelumnya
$deltaBadge = function (float $cur, float $prev): string {
    if ($prev <= 0) return $cur > 0 ? '<span class="cm-delta up">baru</span>' : '';
    $d = round(($cur - $prev) / $prev * 100);
    if ($d == 0) return '<span class="cm-delta flat">±0% <small>vs bln lalu</small></span>';
    $up = $d > 0;
    return '<span class="cm-delta ' . ($up ? 'up' : 'down') . '"><i class="bi bi-caret-' . ($up ? 'up' : 'down') . '-fill"></i>' . abs($d) . '% <small>vs bln lalu</small></span>';
};
$serapanCls = $serapanPct > 100 ? 'cr-c-bad' : ($serapanPct >= 80 ? 'cr-c-warn' : 'cr-c-ok');
?>

<div class="page-cre" id="creMon">

<!-- Header -->
<div class="cr-crumb">
    <a href="<?= base_url('creative') ?>"><i class="bi bi-arrow-left"></i> Creative &amp; Design</a>
    <span>/</span><span>Summary Bulanan</span>
</div>
<div class="cr-head">
    <div class="cr-head-ico"><i class="bi bi-bar-chart-line-fill"></i></div>
    <div class="min-w-0">
        <h1 class="cr-title">Summary Bulanan — Creative &amp; Design</h1>
        <div class="cr-subtitle"><?= $bulanLabel ?> · <?= $totalItems ?> item · <?= $activeCount ?> aktif bulan ini</div>
    </div>
</div>

<!-- Navigator bulan -->
<div class="cm-bar">
    <div class="cm-nav">
        <a href="?bulan=<?= $prevBulan ?>" class="cr-iconbtn" aria-label="Bulan sebelumnya"><i class="bi bi-chevron-left"></i></a>
        <form method="GET" class="m-0">
            <input type="month" name="bulan" value="<?= $bulan ?>" class="cr-month" aria-label="Pilih bulan" onchange="this.form.submit()">
        </form>
        <a href="?bulan=<?= $nextBulan ?>" class="cr-iconbtn <?= $nextBulan > date('Y-m') ? 'disabled' : '' ?>" aria-label="Bulan berikutnya"><i class="bi bi-chevron-right"></i></a>
        <span class="cm-bar-title"><?= $bulanLabel ?></span>
    </div>
    <div class="cm-bar-act">
        <a href="<?= base_url('creative') ?>" class="cr-btn"><i class="bi bi-list-ul"></i>Semua Item</a>
        <a href="<?= base_url('creative/monthly-summary/print?bulan=' . $bulan) ?>" target="_blank" class="cr-btn cr-btn-primary"><i class="bi bi-printer"></i>Cetak Laporan</a>
    </div>
</div>

<!-- KPI -->
<div class="cm-kpis">
    <div class="cr-kpi">
        <div class="cr-kpi-l">Total Item</div>
        <div class="cr-kpi-v"><?= $totalItems ?></div>
        <div class="cr-kpi-s"><?= $activeCount ?> aktif bulan ini</div>
    </div>
    <div class="cr-kpi" title="Realisasi s/d bulan ini <?= $rp($realCumulative) ?> dari budget <?= $rp($totalBudget) ?>">
        <div class="cr-kpi-l">Budget &amp; Serapan</div>
        <div class="cr-kpi-v"><?= $rp($totalBudget) ?></div>
        <div class="cr-kpi-s"><?php if ($totalBudget > 0): ?>serapan <b class="<?= $serapanCls ?>"><?= $serapanPct ?>%</b><?php else: ?>budget belum di-set<?php endif; ?></div>
        <?php if ($totalBudget > 0): ?><div class="cr-bar <?= $serapanPct > 100 ? 'cr-bar-over' : 'cr-bar-ok' ?>"><i style="width:<?= min(100, $serapanPct) ?>%"></i></div><?php endif; ?>
    </div>
    <div class="cr-kpi">
        <div class="cr-kpi-l">Realisasi Bln Ini</div>
        <div class="cr-kpi-v cr-c-warn"><?= $rp($totalRealisasi) ?></div>
        <div class="cr-kpi-s"><?= $deltaBadge($totalRealisasi, $prev['real']) ?: 'bulan ini' ?></div>
    </div>
    <div class="cr-kpi" title="Biaya per 1.000 impresi · Cost per reach <?= $rp($costPerReach) ?>">
        <div class="cr-kpi-l">CPM (Efisiensi)</div>
        <div class="cr-kpi-v"><?= $cpm > 0 ? $rp($cpm) : '—' ?></div>
        <div class="cr-kpi-s"><?= $costPerReach > 0 ? $rp($costPerReach) . ' / reach' : 'per 1.000 impresi' ?></div>
    </div>
    <div class="cr-kpi k3">
        <div class="cr-kpi-l">Reach</div>
        <div class="cr-kpi-v cr-c-cyan"><?= number_format($totalReach) ?></div>
        <div class="cr-kpi-s"><?= $deltaBadge($totalReach, $prev['reach']) ?: 'bulan ini' ?></div>
    </div>
    <div class="cr-kpi k3">
        <div class="cr-kpi-l">Impressions</div>
        <div class="cr-kpi-v cr-c-cyan"><?= number_format($totalImpressions) ?></div>
        <div class="cr-kpi-s"><?= $deltaBadge($totalImpressions, $prev['impr']) ?: 'bulan ini' ?></div>
    </div>
    <div class="cr-kpi k3" title="Likes + Comments + Shares + Saves">
        <div class="cr-kpi-l">Engagement</div>
        <div class="cr-kpi-v cr-c-ok"><?= number_format($totalEngagement) ?></div>
        <div class="cr-kpi-s">rate <b><?= $engagementRate ?>%</b> · +<?= number_format($totalFollowers) ?> flw</div>
    </div>
</div>

<?php if ($topItemReach > 0): ?>
<div class="cm-banner">
    <i class="bi bi-trophy-fill"></i>
    <div><b>Konten terbaik bulan ini:</b> <?= esc($topItemName) ?>
        — <b class="cr-c-cyan"><?= number_format($topItemReach) ?></b> reach<?php if ($topItemEng > 0): ?>,
        <b class="cr-c-ok"><?= number_format($topItemEng) ?></b> engagement<?php endif; ?></div>
</div>
<?php endif; ?>

<?php if (!empty($analysis)): ?>
<div class="cm-card">
    <div class="cm-card-h"><h2><i class="bi bi-stars"></i>Analisa Otomatis — <?= $bulanLabel ?></h2><span class="cr-chip cr-chip-mute">auto</span></div>
    <div class="cm-card-b"><ul class="cm-analysis"><?php foreach ($analysis as $line): ?><li><?= esc($line) ?></li><?php endforeach; ?></ul></div>
</div>
<?php endif; ?>

<?php if ($totalItems > 0): ?>
<?php
$tipeCounts = [];
foreach ($rows as $r) { $t = $r['item']['tipe']; $tipeCounts[$t] = ($tipeCounts[$t] ?? 0) + 1; }
?>
<div class="cm-two">
    <div class="cm-card">
        <div class="cm-card-h"><h2><i class="bi bi-circle-half"></i>Status Item</h2></div>
        <div class="cm-card-b"><div class="cm-chips">
            <?php foreach ($statusLabels as $key => $label): $cnt = $statusCounts[$key] ?? 0; if ($cnt === 0) continue; ?>
            <span class="cr-chip <?= $statusChip[$key] ?>"><?= $label ?> <b><?= $cnt ?></b></span>
            <?php endforeach; ?>
        </div></div>
    </div>
    <div class="cm-card">
        <div class="cm-card-h"><h2><i class="bi bi-grid"></i>Tipe Item</h2></div>
        <div class="cm-card-b"><div class="cm-chips">
            <?php foreach ($tipeCounts as $tipe => $cnt): ?>
            <span class="cr-chip cr-chip-blue"><?= $tipeLabels[$tipe] ?? ucfirst($tipe) ?> <b><?= $cnt ?></b></span>
            <?php endforeach; ?>
            <?php if ($totalFollowers > 0): ?><span class="cm-sub ms-auto"><i class="bi bi-person-plus"></i> +<?= number_format($totalFollowers) ?> followers</span><?php endif; ?>
        </div></div>
    </div>
</div>
<?php endif; ?>

<?php if (empty($rows)): ?>
<div class="cr-empty">
    <i class="bi bi-vector-pen"></i>
    <h5>Belum ada item Creative &amp; Design bulan ini</h5>
    <p>Item tampil di bulan sesuai tanggalnya, atau bila ada realisasi/insight pada bulan itu. Coba bulan lain.</p>
    <a href="<?= base_url('creative') ?>" class="cr-btn"><i class="bi bi-list-ul"></i>Buka daftar Creative</a>
</div>
<?php else: ?>

<?php if ($hasTrend): ?>
<div class="cm-card">
    <div class="cm-card-h"><h2><i class="bi bi-graph-up-arrow"></i>Tren 6 Bulan — Reach, Impressions, Engagement &amp; Realisasi</h2></div>
    <div class="cm-card-b"><div class="cm-chart" style="height:300px"><canvas id="trendChart"></canvas></div></div>
</div>
<?php endif; ?>

<?php if ($hasChart || $hasInsight): ?>
<div class="<?= ($hasChart && $hasInsight) ? 'cm-two' : '' ?>">
<?php if ($hasChart): ?>
<div class="cm-card">
    <div class="cm-card-h"><h2><i class="bi bi-bar-chart"></i>Budget vs Realisasi Bulan Ini</h2><span class="cr-chip cr-chip-mute"><?= count($chartItems) ?> item</span></div>
    <div class="cm-card-b"><div class="cm-chart" style="height:<?= max(200, count($chartItems) * 48 + 70) ?>px"><canvas id="budgetRealChart"></canvas></div></div>
</div>
<?php endif; ?>
<?php if ($hasInsight): ?>
<div class="cm-card">
    <div class="cm-card-h"><h2><i class="bi bi-graph-up"></i>Reach &amp; Impressions — <?= $bulanLabel ?></h2><span class="cr-chip cr-chip-mute"><?= count($insightLabels) ?> item</span></div>
    <div class="cm-card-b"><div class="cm-chart" style="height:<?= max(200, count($insightLabels) * 48 + 70) ?>px"><canvas id="insightChart"></canvas></div></div>
</div>
<?php endif; ?>
</div>
<?php endif; ?>

<!-- Rincian per tipe -->
<div class="cr-tools" id="cmTools">
    <div class="cr-search">
        <i class="bi bi-search"></i>
        <input type="search" id="cmQ" placeholder="Cari nama item, PIC, event…" autocomplete="off" aria-label="Cari item">
    </div>
    <label class="cr-check"><input type="checkbox" id="cmActive">Hanya yang aktif bulan ini</label>
    <div class="cr-tools-end">
        <span class="cr-count" id="cmCount" aria-live="polite"><?= $totalItems ?> item</span>
        <button type="button" class="cr-btn cr-btn-sm" id="cmReset"><i class="bi bi-x-circle"></i>Reset</button>
    </div>
</div>
<div class="cr-empty" id="cmNoMatch" hidden><i class="bi bi-funnel"></i><h5>Tidak ada item yang cocok</h5><p>Ubah kata kunci atau matikan filter “aktif”.</p></div>

<?php
$rowsByTipe = [];
foreach ($rows as $r) { $rowsByTipe[$r['item']['tipe']][] = $r; }
$tipeOrder  = array_unique(array_merge(['master_design', 'digital', 'cetak', 'influencer', 'media_prescon', 'print'], array_keys($rowsByTipe)));
foreach ($tipeOrder as $tipe):
    if (empty($rowsByTipe[$tipe])) continue;
    $tipeRows  = $rowsByTipe[$tipe];
    $isDigital = ($tipe === 'digital');
    $grpBudget = array_sum(array_map(fn($r) => (int)$r['item']['budget'], $tipeRows));
    $grpReal   = array_sum(array_map(fn($r) => (int)($r['realMonth']['total'] ?? 0), $tipeRows));
    $grpReach  = array_sum(array_map(fn($r) => (int)($r['insMonth']['max_reach'] ?? 0), $tipeRows));
    $grpEng    = array_sum(array_map(fn($r) => (int)(($r['insMonth']['max_likes'] ?? 0) + ($r['insMonth']['max_comments'] ?? 0) + ($r['insMonth']['max_shares'] ?? 0) + ($r['insMonth']['max_saves'] ?? 0)), $tipeRows));
?>
<section class="cm-card cm-grp t-<?= esc($tipe, 'attr') ?>">
    <div class="cm-card-h">
        <h2><i class="bi bi-<?= $tipeIcons[$tipe] ?? 'grid' ?>"></i><?= $tipeLabels[$tipe] ?? ucfirst($tipe) ?></h2>
        <span class="cr-chip cr-chip-blue"><?= count($tipeRows) ?> item</span>
    </div>
    <div class="cr-tbl-wrap">
        <table class="cr-tbl">
            <thead>
                <tr>
                    <th>Nama Item</th>
                    <th>Status</th>
                    <th class="r">Budget</th>
                    <th class="r">Realisasi Bulan Ini</th>
                    <?php if ($isDigital): ?>
                    <th class="r">Reach / Impr</th>
                    <th class="r">Engagement</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($tipeRows as $r):
                $item    = $r['item'];
                $budget  = (int)$item['budget'];
                $realMon = (int)($r['realMonth']['total']          ?? 0);
                $reach   = (int)($r['insMonth']['max_reach']       ?? 0);
                $impr    = (int)($r['insMonth']['max_impressions'] ?? 0);
                $likes   = (int)($r['insMonth']['max_likes']    ?? 0);
                $comments= (int)($r['insMonth']['max_comments'] ?? 0);
                $shares  = (int)($r['insMonth']['max_shares']   ?? 0);
                $saves   = (int)($r['insMonth']['max_saves']    ?? 0);
                $engR    = $likes + $comments + $shares + $saves;
                $link    = $item['_source'] === 's'
                    ? base_url('creative#item-' . $item['id'] . '-s')
                    : base_url('events/' . $item['event_id'] . '/creative');
                $itgl    = ($item['tanggal'] ?? '') ?: ($item['tanggal_take'] ?? '');
                $stKey   = $item['status'] ?? 'draft';
                $srch    = mb_strtolower(($item['nama'] ?? '') . ' ' . ($item['pic'] ?? '') . ' ' . ($item['event_name'] ?? ''));
            ?>
            <tr data-cm-row data-active="<?= $r['hasActivity'] ? '1' : '0' ?>" data-search="<?= esc($srch, 'attr') ?>" class="<?= $r['hasActivity'] ? '' : 'cm-quiet' ?>">
                <td>
                    <a href="<?= $link ?>" class="cr-rowlink"><?= esc($item['nama']) ?></a>
                    <?php if ($r['hasActivity']): ?><span class="cr-chip cr-chip-ok">aktif</span><?php endif; ?>
                    <?php if (!empty($item['is_closed'])): ?><span class="cr-chip cr-chip-mute"><i class="bi bi-check2-circle"></i>Selesai</span><?php endif; ?>
                    <?php if ($item['_source'] !== 's'): ?><span class="cr-chip cr-chip-gold">Event</span><?php endif; ?>
                    <?php if ($itgl): ?><div class="cm-sub"><i class="bi bi-calendar3"></i> <?= $fTgl($itgl) ?></div><?php endif; ?>
                    <?php if (!empty($item['event_name'])): ?><div class="cm-sub"><?= esc($item['event_name']) ?></div><?php endif; ?>
                    <?php if (!empty($item['pic'])): ?><div class="cm-sub"><i class="bi bi-person"></i> <?= esc($item['pic']) ?></div><?php endif; ?>
                </td>
                <td><span class="cr-chip <?= $statusChip[$stKey] ?? 'cr-chip-mute' ?>"><?= $statusLabels[$stKey] ?? ucfirst($stKey) ?></span></td>
                <td class="r nw"><?= $budget > 0 ? '<b class="cr-c-bad">' . $rp($budget) . '</b>' : '<span class="cr-c-mute">—</span>' ?></td>
                <td class="r nw"><?= $realMon > 0 ? '<b class="cr-c-warn">' . $rp($realMon) . '</b>' : '<span class="cr-c-mute">—</span>' ?></td>
                <?php if ($isDigital): ?>
                <td class="r nw">
                    <?php if ($reach > 0 || $impr > 0): ?>
                    <b class="cr-c-cyan"><?= number_format($reach) ?></b>
                    <?php if ($impr > 0): ?><div class="cm-eng-sub">Impr <?= number_format($impr) ?></div><?php endif; ?>
                    <?php else: ?><span class="cr-c-mute">—</span><?php endif; ?>
                </td>
                <td class="r nw">
                    <?php if ($engR > 0): ?>
                    <b class="cr-c-ok"><?= number_format($engR) ?></b>
                    <div class="cm-eng-sub"><?= number_format($likes) ?>L · <?= number_format($comments) ?>C · <?= number_format($shares) ?>S</div>
                    <?php else: ?><span class="cr-c-mute">—</span><?php endif; ?>
                </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
            </tbody>
            <?php if ($grpBudget > 0 || $grpReal > 0): ?>
            <tfoot>
                <tr>
                    <td colspan="2">Subtotal</td>
                    <td class="r nw cr-c-bad"><?= $rp($grpBudget) ?></td>
                    <td class="r nw cr-c-warn"><?= $rp($grpReal) ?></td>
                    <?php if ($isDigital): ?>
                    <td class="r nw cr-c-cyan"><?= $grpReach > 0 ? number_format($grpReach) : '—' ?></td>
                    <td class="r nw cr-c-ok"><?= $grpEng > 0 ? number_format($grpEng) : '—' ?></td>
                    <?php endif; ?>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
</section>
<?php endforeach; ?>

<?php endif; ?>

</div><!-- /.page-cre -->

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="<?= base_url('js/creative-main.js') ?>?v=<?= @filemtime(FCPATH . 'js/creative-main.js') ?: 1 ?>"></script>
<?php if ($hasTrend): ?>
<script>
new Chart(document.getElementById('trendChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($trendLabels) ?>,
        datasets: [
            { label: 'Reach',          data: <?= json_encode($trendReach) ?>, borderColor: '#0dcaf0', backgroundColor: 'rgba(13,202,240,.1)', tension: .3, pointRadius: 3, fill: true, yAxisID: 'y' },
            { label: 'Impressions',    data: <?= json_encode($trendImpr) ?>,  borderColor: '#6366f1', backgroundColor: 'transparent', tension: .3, pointRadius: 3, borderDash: [4,3], yAxisID: 'y' },
            { label: 'Engagement',     data: <?= json_encode($trendEng) ?>,   borderColor: '#198754', backgroundColor: 'transparent', tension: .3, pointRadius: 3, yAxisID: 'y' },
            { label: 'Realisasi (Rp)', data: <?= json_encode($trendReal) ?>,  borderColor: '#ffc107', backgroundColor: 'rgba(255,193,7,.1)', tension: .3, pointRadius: 3, fill: true, yAxisID: 'y1' },
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } },
            tooltip: { callbacks: { label: ctx => ctx.dataset.label + ': ' + (ctx.dataset.yAxisID === 'y1' ? 'Rp ' + ctx.raw.toLocaleString('id-ID') : ctx.raw.toLocaleString('id-ID')) } }
        },
        scales: {
            y:  { beginAtZero: true, position: 'left',  ticks: { font: { size: 10 }, callback: v => v.toLocaleString('id-ID') } },
            y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false }, ticks: { font: { size: 10 }, callback: v => 'Rp ' + v.toLocaleString('id-ID') } }
        }
    }
});
</script>
<?php endif; ?>
<?php if ($hasChart): ?>
<script>
new Chart(document.getElementById('budgetRealChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($chartItems) ?>,
        datasets: [
            {
                label: 'Budget',
                data: <?= json_encode($chartBudget) ?>,
                backgroundColor: 'rgba(220,53,69,.7)',
                borderRadius: 3,
            },
            {
                label: 'Realisasi Bulan Ini',
                data: <?= json_encode($chartRealMonth) ?>,
                backgroundColor: 'rgba(255,193,7,.7)',
                borderRadius: 3,
            },
        ]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        animation: {
            duration: 700,
            easing: 'easeOutQuart',
            delay: ctx => ctx.type === 'data' ? ctx.dataIndex * 40 : 0,
        },
        scales: { x: { beginAtZero: true, ticks: { callback: v => 'Rp ' + v.toLocaleString('id-ID') } }, y: { ticks: { font: { size: 10 }, callback: function (v) { var l = this.getLabelForValue(v); return l.length > 20 ? l.slice(0, 19) + '…' : l; } } } },
        plugins: {
            legend: { position: 'top', labels: { boxWidth: 12, font: { size: 11 } } },
            tooltip: { callbacks: { label: ctx => ctx.dataset.label + ': Rp ' + ctx.raw.toLocaleString('id-ID') } }
        }
    }
});
</script>
<?php endif; ?>
<?php if ($hasInsight): ?>
<script>
new Chart(document.getElementById('insightChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($insightLabels) ?>,
        datasets: [
            {
                label: 'Reach',
                data: <?= json_encode($insightReach) ?>,
                backgroundColor: 'rgba(13,202,240,.7)',
                borderRadius: 3,
            },
            {
                label: 'Impressions',
                data: <?= json_encode($insightImpr) ?>,
                backgroundColor: 'rgba(99,102,241,.5)',
                borderRadius: 3,
            },
        ]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        animation: {
            duration: 700,
            easing: 'easeOutQuart',
            delay: ctx => ctx.type === 'data' ? ctx.dataIndex * 40 : 0,
        },
        scales: { x: { beginAtZero: true, ticks: { callback: v => v.toLocaleString('id-ID') } }, y: { ticks: { font: { size: 10 }, callback: function (v) { var l = this.getLabelForValue(v); return l.length > 20 ? l.slice(0, 19) + '…' : l; } } } },
        plugins: {
            legend: { position: 'top', labels: { boxWidth: 12, font: { size: 11 } } },
            tooltip: { callbacks: { label: ctx => ctx.dataset.label + ': ' + ctx.raw.toLocaleString('id-ID') } }
        }
    }
});
</script>
<?php endif; ?>
<?= $this->endSection() ?>
