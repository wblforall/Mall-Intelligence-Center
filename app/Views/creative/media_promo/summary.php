<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/media-promo.css') ?>?v=<?= @filemtime(FCPATH . 'css/media-promo.css') ?: 1 ?>">
<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php include_once __DIR__ . '/_helpers.php'; ?>
<?php
$idBulan    = ['January'=>'Januari','February'=>'Februari','March'=>'Maret','April'=>'April','May'=>'Mei','June'=>'Juni','July'=>'Juli',
               'August'=>'Agustus','September'=>'September','October'=>'Oktober','November'=>'November','December'=>'Desember'];
$bulanDt    = \DateTime::createFromFormat('Y-m', $bulan);
$bulanLabel = strtr($bulanDt->format('F Y'), $idBulan);

$tipeIcon = ['t_banner'=>'flag','hanging'=>'image','sticker_lift'=>'stickies','totem_stainless'=>'reception-4','digital'=>'display'];
$tipeChip = ['t_banner' => 'blue', 'hanging' => 'blue', 'sticker_lift' => 'warn', 'totem_stainless' => 'mute', 'digital' => 'line'];
$sumberLabel = ['internal'=>'Internal Manajemen','tenant'=>'Tenant Mall','external'=>'External Client'];

$totalRequest = array_sum($statusCounts);
$totalActive  = ($statusCounts['approved'] ?? 0) + ($statusCounts['done'] ?? 0);

$chartSpots  = array_slice($spotOccupancy, 0, 20);
$chartLabels = array_map(fn($o) => $o['spot']['kode'], $chartSpots);
$chartPct    = array_map(fn($o) => $o['pct'], $chartSpots);
$chartColors = array_map(function ($o) {
    $p = $o['pct'];
    if ($p >= 80) return 'rgba(220,53,69,.75)';
    if ($p >= 50) return 'rgba(255,193,7,.75)';
    return 'rgba(25,135,84,.75)';
}, $chartSpots);

$mpTitle = 'Summary Media Promo'; $mpIcon = 'bi-graph-up-arrow';
$mpSub = $bulanLabel . ' · ' . $totalRequest . ' request · ' . count($spotOccupancy) . ' titik aktif';
ob_start(); ?>
    <a href="<?= base_url('creative/media-promo/print-summary?bulan=' . $bulan) ?>" target="_blank" class="mp-btn"><i class="bi bi-printer"></i><span class="d-none d-sm-inline">Laporan Bulanan</span></a>
    <a href="<?= base_url('creative/media-promo/print?bulan=' . $bulan) ?>" target="_blank" class="mp-btn"><i class="bi bi-file-earmark-text"></i><span class="d-none d-sm-inline">Booking Sheet</span></a>
<?php $mpActions = ob_get_clean();

// Satu blok sebaran: [label => jumlah]
$dist = function (array $data, int $total) {
    echo '<div class="mp-dist">';
    foreach ($data as $label => $c) {
        $w = $total > 0 ? round($c / $total * 100) : 0;
        echo '<div class="mp-dist-r"><span class="mp-dist-n">' . esc($label) . '</span><div class="mp-bar"><i style="width:' . $w . '%"></i></div><b>' . (int) $c . '</b></div>';
    }
    echo '</div>';
};
?>
<div class="page-mp" id="mpRoot" data-mp="summary" data-base="<?= base_url() ?>">
<?php include __DIR__ . '/_nav.php'; ?>

<!-- Pilih bulan -->
<div class="mp-tools">
    <div class="mp-month">
        <a href="?bulan=<?= $prevBulan ?>" class="mp-iconbtn" aria-label="Bulan sebelumnya"><i class="bi bi-chevron-left"></i></a>
        <form method="GET"><input type="month" name="bulan" value="<?= $bulan ?>" class="mp-input" aria-label="Pilih bulan" onchange="this.form.submit()"></form>
        <a href="?bulan=<?= $nextBulan ?>" class="mp-iconbtn" aria-label="Bulan berikutnya"><i class="bi bi-chevron-right"></i></a>
        <?php if ($bulan !== date('Y-m')): ?><a href="?bulan=<?= date('Y-m') ?>" class="mp-btn mp-btn-sm">Bulan ini</a><?php endif; ?>
    </div>
    <span class="mp-count ms-auto"><?= $bulanLabel ?></span>
</div>

<div class="mp-kpis k4">
    <div class="mp-kpi"><div class="mp-kpi-l">Total Request</div><div class="mp-kpi-v"><?= $totalRequest ?></div><div class="mp-kpi-s">bulan <?= $bulanLabel ?></div></div>
    <div class="mp-kpi"><div class="mp-kpi-l">Approved / Done</div><div class="mp-kpi-v mp-c-ok"><?= $totalActive ?></div><div class="mp-kpi-s"><?= $statusCounts['approved'] ?> approved · <?= $statusCounts['done'] ?> done</div></div>
    <div class="mp-kpi"><div class="mp-kpi-l">Pending Approval</div><div class="mp-kpi-v mp-c-warn"><?= $statusCounts['pending'] ?></div><div class="mp-kpi-s">menunggu approval</div></div>
    <div class="mp-kpi"><div class="mp-kpi-l">Ditolak</div><div class="mp-kpi-v mp-c-bad"><?= $statusCounts['rejected'] ?></div><div class="mp-kpi-s"><?= $statusCounts['draft'] ?> masih draft</div></div>
</div>

<?php if ($totalRequest > 0): ?>
<div class="mp-three">
    <div class="mp-card"><div class="mp-card-h"><h2><i class="bi bi-person-badge me-1"></i>Sumber Materi</h2></div>
        <div class="mp-card-b"><?php
            $d = []; foreach ($sumberCounts as $src => $cnt) { if ($cnt) $d[$sumberLabel[$src] ?? $src] = $cnt; }
            $d['Berbayar'] = $berbayarCount; $d['Gratis'] = $totalRequest - $berbayarCount;
            $dist(array_slice($d, 0, count($d) - 2, true), $totalRequest); ?>
            <hr style="border-color:var(--mp-border);margin:.8rem 0">
            <div class="mp-card-h" style="padding:0 0 .5rem;border:0"><h2><i class="bi bi-cash-coin me-1"></i>Status Biaya</h2></div>
            <?php $dist(['Berbayar' => $berbayarCount, 'Gratis' => $totalRequest - $berbayarCount], $totalRequest); ?>
        </div></div>
    <div class="mp-card"><div class="mp-card-h"><h2><i class="bi bi-grid me-1"></i>Request per Tipe Media</h2></div>
        <div class="mp-card-b"><?php
            $d = []; foreach ($tipeCounts as $tipe => $cnt) $d[mpTipeLabel($tipe)] = $cnt;
            $dist($d, $totalRequest); ?></div></div>
    <div class="mp-card"><div class="mp-card-h"><h2><i class="bi bi-building me-1"></i>Request per Departemen</h2></div>
        <div class="mp-card-b"><?php $dist($deptCounts, $totalRequest); ?></div></div>
</div>
<?php endif; ?>

<?php if (! empty($chartSpots)): ?>
<div class="mp-card">
    <div class="mp-card-h"><h2><i class="bi bi-bar-chart-steps me-1"></i>Occupancy Titik Media — <?= $bulanLabel ?></h2><small>20 teratas · booking approved/done</small></div>
    <div class="mp-card-b"><div style="position:relative;height:<?= max(140, count($chartSpots) * 26 + 40) ?>px"><canvas id="occupancyChart"></canvas></div></div>
</div>
<?php endif; ?>

<?php if (! empty($spotOccupancy)): ?>
<div class="mp-tools" id="mpTools">
    <div class="mp-search"><i class="bi bi-search"></i>
        <input type="search" id="mpQ" placeholder="Cari kode, nama, area titik…" autocomplete="off" aria-label="Cari titik">
    </div>
    <select class="mp-select" id="mpFTipe" aria-label="Filter tipe">
        <option value="">Semua tipe</option>
        <?php foreach (['t_banner','hanging','sticker_lift','totem_stainless','digital'] as $t): ?><option value="<?= $t ?>"><?= mpTipeLabel($t) ?></option><?php endforeach; ?>
    </select>
    <select class="mp-select" id="mpFLevel" aria-label="Filter tingkat occupancy">
        <option value="">Semua occupancy</option>
        <option value="hi">≥ 80% (penuh)</option><option value="mid">50–79%</option><option value="lo">&lt; 50%</option><option value="zero">0% (kosong)</option>
    </select>
    <div class="mp-tools-end">
        <span class="mp-count" id="mpCount" aria-live="polite"></span>
        <button type="button" class="mp-btn mp-btn-sm" id="mpReset"><i class="bi bi-x-circle"></i><span class="d-none d-md-inline">Reset</span></button>
    </div>
</div>
<div class="mp-card">
    <div class="mp-card-h"><h2><i class="bi bi-table me-1"></i>Detail Occupancy per Titik</h2><small>klik judul kolom untuk mengurutkan</small></div>
    <div class="mp-scroll">
    <table class="mp-table is-stack" data-sortable>
        <thead><tr>
            <th data-sort="0">Kode</th><th data-sort="1">Nama</th><th data-sort="2">Tipe</th><th data-sort="3">Area</th>
            <th class="c" data-sort="4">Hari Terpakai</th><th class="c" data-sort="5">Kapasitas</th><th data-sort="6" style="min-width:170px">Occupancy</th>
        </tr></thead>
        <tbody>
        <?php foreach ($spotOccupancy as $o):
            $s = $o['spot']; $pct = $o['pct']; $isDigital = $s['tipe'] === 'digital';
            $barCls = $pct >= 80 ? 'mp-bar-hi' : ($pct >= 50 ? 'mp-bar-mid' : '');
            $txtCls = $pct >= 80 ? 'mp-c-bad' : ($pct >= 50 ? 'mp-c-warn' : 'mp-c-ok');
            $lvl = $pct >= 80 ? 'hi' : ($pct >= 50 ? 'mid' : ($pct > 0 ? 'lo' : 'zero'));
            $srch = mb_strtolower($s['kode'] . ' ' . $s['nama'] . ' ' . ($s['area'] ?? ''));
        ?>
        <tr data-search="<?= esc($srch, 'attr') ?>" data-tipe="<?= esc($s['tipe'], 'attr') ?>" data-level="<?= $lvl ?>">
            <td data-l="Kode" data-v="<?= esc(mb_strtolower($s['kode']), 'attr') ?>"><span class="mp-code"><?= esc($s['kode']) ?></span></td>
            <td data-l="Nama" data-v="<?= esc(mb_strtolower($s['nama']), 'attr') ?>" class="wrap fw-semibold"><?= esc($s['nama']) ?></td>
            <td data-l="Tipe" data-v="<?= esc($s['tipe'], 'attr') ?>"><span class="mp-chip mp-chip-<?= $tipeChip[$s['tipe']] ?? 'mute' ?>"><i class="bi bi-<?= $tipeIcon[$s['tipe']] ?? 'square' ?>"></i><?= esc(mpTipeLabel($s['tipe'])) ?></span></td>
            <td data-l="Area" data-v="<?= esc(mb_strtolower($s['area'] ?? ''), 'attr') ?>" class="wrap mp-muted"><?= esc($s['area'] ?: '—') ?></td>
            <td data-l="Hari Terpakai" data-v="<?= (int) $o['occupied'] ?>" class="c"><?= $o['occupied'] ?><?php if ($isDigital): ?> <span class="mp-muted" style="font-size:.7rem">slot-hari</span><?php endif; ?></td>
            <td data-l="Kapasitas" data-v="<?= (int) $o['capacity'] ?>" class="c mp-muted"><?= $o['capacity'] ?><?php if ($isDigital): ?> <span style="font-size:.7rem">(<?= (int) $s['total_slots'] ?> slot)</span><?php endif; ?></td>
            <td data-l="Occupancy" data-v="<?= (float) $pct ?>" class="mp-full"><div class="mp-pct"><div class="mp-bar <?= $barCls ?>"><i style="width:<?= $pct ?>%"></i></div><b class="<?= $txtCls ?>"><?= $pct ?>%</b></div></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<div class="mp-empty mp-empty-filter" id="mpNoMatch" hidden>
    <i class="bi bi-search"></i><h5>Tidak ada titik yang cocok</h5><p>Ubah kata kunci atau filter.</p>
    <button type="button" class="mp-btn" id="mpReset2"><i class="bi bi-x-circle"></i>Reset filter</button>
</div>
<?php else: ?>
<div class="mp-empty"><i class="bi bi-inbox"></i><h5>Belum ada data untuk bulan ini</h5><p>Pilih bulan lain dengan panah di atas.</p></div>
<?php endif; ?>

</div><!-- /.page-mp -->
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="<?= base_url('js/media-promo.js') ?>?v=<?= @filemtime(FCPATH . 'js/media-promo.js') ?: 1 ?>"></script>
<?php if (! empty($chartSpots)): ?>
<script>
(function () {
    var cs = getComputedStyle(document.getElementById('mpRoot'));
    var txt = cs.getPropertyValue('--mp-muted').trim() || '#64748b', grid = cs.getPropertyValue('--mp-line').trim() || 'rgba(0,0,0,.08)';
    new Chart(document.getElementById('occupancyChart'), {
        type: 'bar',
        data: { labels: <?= json_encode($chartLabels, JSON_UNESCAPED_UNICODE) ?>,
                datasets: [{ label: 'Occupancy (%)', data: <?= json_encode($chartPct) ?>, backgroundColor: <?= json_encode($chartColors) ?>, borderRadius: 4 }] },
        options: {
            indexAxis: 'y', responsive: true, maintainAspectRatio: false, animation: { duration: 600, easing: 'easeOutQuart' },
            scales: {
                x: { beginAtZero: true, max: 100, ticks: { color: txt, callback: function (v) { return v + '%'; } }, grid: { color: grid } },
                y: { ticks: { color: txt, font: { family: 'monospace', size: 11 } }, grid: { display: false } }
            },
            plugins: { legend: { display: false }, tooltip: { callbacks: { label: function (c) { return ' Occupancy: ' + c.raw + '%'; } } } }
        }
    });
})();
</script>
<?php endif; ?>
<?= $this->endSection() ?>
