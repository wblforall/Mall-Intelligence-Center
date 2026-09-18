<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<style>
.pest-wrap { overflow-x: auto; }
.pest-tren { border-collapse: separate; border-spacing: 0; min-width: 100%; }
.pest-tren th, .pest-tren td { padding: .4rem .55rem; white-space: nowrap; font-size: .875rem; border-bottom: 1px solid var(--bs-border-color); }
.pest-tren thead th { position: sticky; top: 0; background: var(--bs-tertiary-bg); z-index: 2; font-size: .72rem; text-align: center; }
.pest-tren .col-item { position: sticky; left: 0; background: var(--bs-body-bg); z-index: 1; text-align: left; font-weight: 500; min-width: 130px; }
.pest-tren thead .col-item { z-index: 3; background: var(--bs-tertiary-bg); }
.pest-tren td.num { text-align: center; font-variant-numeric: tabular-nums; }
.pest-tren td.nol { color: var(--bs-secondary-color); opacity: .45; }
.pest-tren tfoot td { font-weight: 700; background: var(--bs-tertiary-bg); }
.wk-sub { display: block; font-weight: 400; opacity: .6; font-size: .65rem; }
.wk-kosong { background: repeating-linear-gradient(45deg, transparent, transparent 4px, rgba(var(--bs-secondary-rgb), .08) 4px, rgba(var(--bs-secondary-rgb), .08) 8px); }
</style>
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php
$mallLabel = $mall ? \App\Models\PestVisitModel::MALLS[$mall] : 'Kedua Mall';
$qs = fn(array $ubah) => base_url('pest') . '?' . http_build_query(array_merge(
    ['mall' => $mall, 'minggu' => $minggu], $ubah));

// Total per item & per minggu
$totItem = []; $totMinggu = []; $grand = 0;
foreach ($items as $it) {
    $id = (int) $it['id']; $totItem[$id] = 0;
    foreach ($kolom as $k) {
        $v = $perMinggu[$k['yw']][$id] ?? 0;
        $totItem[$id] += $v;
        $totMinggu[$k['yw']] = ($totMinggu[$k['yw']] ?? 0) + $v;
        $grand += $v;
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-bug me-2"></i>Pest Control</h4>
        <small class="text-muted">Tren temuan mingguan &middot; <?= $mallLabel ?> &middot; <?= $minggu ?> minggu terakhir</small>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= base_url('pest/kunjungan') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-list-ul me-1"></i>Kunjungan</a>
        <a href="<?= base_url('pest/summary') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-bar-chart me-1"></i>Rekap</a>
        <?php if ($canEdit): ?>
        <a href="<?= base_url('pest/input/ewalk/' . date('Y-m-d')) ?>" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i>Input eWalk</a>
        <a href="<?= base_url('pest/input/pentacity/' . date('Y-m-d')) ?>" class="btn btn-sm btn-success"><i class="bi bi-plus-lg me-1"></i>Input Pentacity</a>
        <?php endif; ?>
    </div>
</div>

<div class="card mb-3">
<div class="card-body py-2 d-flex flex-wrap gap-3 align-items-center">
    <div class="btn-group btn-group-sm">
        <a href="<?= $qs(['mall' => null]) ?>" class="btn <?= ! $mall ? 'btn-primary' : 'btn-outline-secondary' ?>">Kedua Mall</a>
        <a href="<?= $qs(['mall' => 'ewalk']) ?>" class="btn <?= $mall === 'ewalk' ? 'btn-primary' : 'btn-outline-secondary' ?>">eWalk</a>
        <a href="<?= $qs(['mall' => 'pentacity']) ?>" class="btn <?= $mall === 'pentacity' ? 'btn-primary' : 'btn-outline-secondary' ?>">Pentacity</a>
    </div>
    <div class="btn-group btn-group-sm">
        <?php foreach ([8, 12, 26, 52] as $w): ?>
        <a href="<?= $qs(['minggu' => $w]) ?>" class="btn <?= $minggu === $w ? 'btn-primary' : 'btn-outline-secondary' ?>"><?= $w ?> mg</a>
        <?php endforeach; ?>
    </div>
    <span class="ms-auto small text-muted">
        Total <strong class="text-body"><?= number_format($grand) ?></strong> temuan
    </span>
</div>
</div>

<div class="alert alert-info py-2 small">
    <i class="bi bi-info-circle me-1"></i>
    Minggu memakai penomoran <strong>ISO (Senin&ndash;Minggu)</strong>, jadi minggu di awal atau akhir
    bulan bisa menyeberang dua bulan. Untuk rincian yang terkunci di dalam satu bulan, pakai
    <a href="<?= base_url('pest/laporan-bulanan') ?>">Laporan Bulanan</a>.
    Kolom berarsir berarti <strong>belum ada kunjungan tercatat</strong> pada minggu itu &mdash; berbeda dari nol yang berarti diperiksa dan nihil.
</div>

<div class="card">
<div class="pest-wrap">
<table class="pest-tren mb-0">
<thead>
<tr>
    <th class="col-item">Item Temuan</th>
    <?php foreach ($kolom as $k): ?>
    <th class="<?= empty($jmlKunjungan[$k['yw']]) ? 'wk-kosong' : '' ?>">
        <?= $k['label'] ?><span class="wk-sub"><?= $k['rentang'] ?></span>
    </th>
    <?php endforeach; ?>
    <th style="background:var(--bs-secondary-bg)">Total</th>
</tr>
</thead>
<tbody>
<?php foreach ($items as $it): $id = (int) $it['id']; ?>
<tr>
    <td class="col-item"><?= esc($it['nama']) ?></td>
    <?php foreach ($kolom as $k): $v = $perMinggu[$k['yw']][$id] ?? 0; ?>
    <td class="num <?= $v === 0 ? 'nol' : '' ?> <?= empty($jmlKunjungan[$k['yw']]) ? 'wk-kosong' : '' ?>">
        <?= $v > 0 ? number_format($v) : (empty($jmlKunjungan[$k['yw']]) ? '·' : '0') ?>
    </td>
    <?php endforeach; ?>
    <td class="num fw-bold"><?= $totItem[$id] > 0 ? number_format($totItem[$id]) : '—' ?></td>
</tr>
<?php endforeach; ?>
</tbody>
<tfoot>
<tr>
    <td class="col-item">Total</td>
    <?php foreach ($kolom as $k): ?>
    <td class="num"><?= ! empty($totMinggu[$k['yw']]) ? number_format($totMinggu[$k['yw']]) : '—' ?></td>
    <?php endforeach; ?>
    <td class="num"><?= number_format($grand) ?></td>
</tr>
</tfoot>
</table>
</div>
</div>

<?php if ($grand > 0): ?>
<div class="card mt-3">
<div class="card-header py-2"><span class="fw-semibold small"><i class="bi bi-graph-up me-1"></i>Tren Mingguan</span></div>
<div class="card-body"><div style="height:260px"><canvas id="chartTren"></canvas></div></div>
</div>
<?php endif; ?>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<?php if ($grand > 0): ?>
<script>
(function () {
    const ctx = document.getElementById('chartTren');
    if (! ctx || typeof Chart === 'undefined') return;

    // Palet aman untuk buta warna (CVD-safe), konsisten dengan laporan lain.
    const palet = ['#2563eb', '#d97706', '#059669', '#9333ea', '#dc2626', '#0891b2', '#65a30d', '#c026d3'];
    const labels = <?= json_encode(array_map(fn($k) => $k['label'] . ' · ' . $k['rentang'], $kolom)) ?>;
    const sets = <?= json_encode(array_values(array_filter(array_map(function ($it) use ($kolom, $perMinggu, $totItem) {
        $id = (int) $it['id'];
        if (($totItem[$id] ?? 0) === 0) return null;   // item nol sepanjang periode hanya meramaikan legenda
        return ['label' => $it['nama'], 'data' => array_map(fn($k) => $perMinggu[$k['yw']][$id] ?? 0, $kolom)];
    }, $items)))) ?>;

    new Chart(ctx, {
        type: 'line',
        data: { labels: labels, datasets: sets.map((s, i) => ({
            label: s.label, data: s.data,
            borderColor: palet[i % palet.length],
            backgroundColor: palet[i % palet.length],
            tension: .3, borderWidth: 2, pointRadius: 2.5
        })) },
        options: {
            responsive: true, maintainAspectRatio: false, animation: false,
            interaction: { mode: 'index', intersect: false },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } },
                      x: { ticks: { maxRotation: 60, minRotation: 0, font: { size: 10 } } } },
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } }
        }
    });
})();
</script>
<?php endif; ?>
<?= $this->endSection() ?>
