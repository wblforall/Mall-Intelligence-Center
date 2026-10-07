<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<style>
.rekap { border-collapse: collapse; width: 100%; }
.rekap th, .rekap td { padding: .4rem .55rem; border-bottom: 1px solid var(--bs-border-color); font-size: .85rem; white-space: nowrap; }
.rekap thead th { background: var(--bs-tertiary-bg); font-size: .72rem; text-align: right; }
.rekap thead th.kiri, .rekap td.kiri { text-align: left; }
.rekap td.num { text-align: right; font-variant-numeric: tabular-nums; }
.rekap td.nol { opacity: .35; }
.rekap tfoot td { font-weight: 700; background: var(--bs-tertiary-bg); }
.rekap .col-item { text-align: left; font-weight: 500; position: sticky; left: 0; background: var(--bs-body-bg); z-index: 1; }
.rekap thead .col-item, .rekap tfoot .col-item { background: var(--bs-tertiary-bg); }
.rekap tr.baris-impor td { background: rgba(var(--bs-warning-rgb), .08); font-style: italic; }
.rekap tr.tanpa-catatan td:not(.col-item) { opacity: .45; }
.sub-ket { display: block; font-size: .68rem; font-weight: 400; opacity: .7; }
/* Temuan pest NAIK = buruk: warna sengaja dibalik dari laporan pendapatan. */
.naik  { color: var(--bs-danger); }
.turun { color: var(--bs-success); }
.lencana-d { display: inline-block; padding: .1rem .45rem; border-radius: 999px; font-size: .72rem; font-weight: 600; white-space: nowrap; }
.lencana-d.naik  { background: rgba(var(--bs-danger-rgb), .12); }
.lencana-d.turun { background: rgba(var(--bs-success-rgb), .12); }
.lencana-d.netral { background: var(--bs-tertiary-bg); color: var(--bs-secondary-color); }
.kpi-angka { font-size: 1.5rem; font-weight: 700; font-variant-numeric: tabular-nums; line-height: 1.15; }
.analisa-list { margin: 0; padding-left: 1.1rem; }
.analisa-list li { font-size: .875rem; margin-bottom: .3rem; }
.chart-tinggi { position: relative; height: 260px; }
</style>
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php
use App\Libraries\PestRekap as PR;
use App\Models\PestVisitModel;

$nf  = fn($v) => PR::angka($v);
$n0  = fn($v) => $v > 0 ? PR::angka($v) : '—';
$dari = $r['dari']; $sampai = $r['sampai'];
$mallLabel = $mall ? PestVisitModel::MALLS[$mall] : 'Kedua Mall';
$qsBase = ['from' => $dari, 'to' => $sampai, 'mall' => $mall];
$qs = fn(array $ubah) => '?' . http_build_query(array_filter(array_merge($qsBase, $ubah), fn($v) => $v !== null && $v !== ''));

$lencana = function (?float $pct): string {
    if ($pct === null) return '<span class="lencana-d netral">tanpa pembanding</span>';
    $cls = $pct > 0 ? 'naik' : ($pct < 0 ? 'turun' : 'netral');
    return '<span class="lencana-d ' . $cls . '">' . ($pct > 0 ? '▲ ' : ($pct < 0 ? '▼ ' : '')) . PR::persen($pct) . '</span>';
};
$deltaTotal = PR::pct($r['grand'], $prev['grand']);
$rc = $r['rincian'];
$modeLabel = ['harian' => 'per Hari', 'mingguan' => 'per Minggu (ISO)', 'bulanan' => 'per Bulan'][$rc['mode']];

// Item tampil di grafik: yang punya angka saja.
$itemGrafik = array_values(array_filter($items, fn($it) => ($r['perItem'][(int) $it['id']] ?? 0) > 0));

$presets = [
    'Bln ini'   => [date('Y-m-01'), date('Y-m-d')],
    'Bln lalu'  => [date('Y-m-01', strtotime('first day of last month')), date('Y-m-t', strtotime('last day of last month'))],
    '3 bln'     => [date('Y-m-01', strtotime('first day of -2 month')), date('Y-m-d')],
    'Tahun ini' => [date('Y-01-01'), date('Y-m-d')],
    '12 bln'    => [date('Y-m-01', strtotime('first day of -11 month')), date('Y-m-d')],
];
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-bar-chart-line me-2"></i>Rekap Periode Pest Control</h4>
        <small class="text-muted"><?= PR::rentang($dari, $sampai) ?> &middot; <?= $r['hari'] ?> hari &middot; <?= $mallLabel ?></small>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= base_url('pest/laporan-bulanan') . '?' . http_build_query(array_filter(['bulan' => substr($sampai, 0, 7), 'mall' => $mall])) ?>"
           target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-file-earmark-text me-1"></i>Laporan Bulanan</a>
        <a href="<?= base_url('pest/rekap/print') . $qs([]) ?>" target="_blank" class="btn btn-sm btn-outline-danger">
            <i class="bi bi-printer me-1"></i>Print / PDF</a>
        <a href="<?= base_url('pest/rekap/export') . $qs([]) ?>" class="btn btn-sm btn-outline-success">
            <i class="bi bi-file-earmark-excel me-1"></i>Export Excel</a>
        <a href="<?= base_url('pest/compare') ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-arrow-left-right me-1"></i>Compare</a>
    </div>
</div>

<!-- Filter -->
<div class="card mb-3">
<div class="card-body py-2">
<form method="GET" class="row g-2 align-items-end">
    <div class="col-6 col-sm-auto">
        <label class="form-label small fw-semibold mb-1" for="fFrom">Dari</label>
        <input type="date" id="fFrom" name="from" class="form-control form-control-sm" value="<?= $dari ?>">
    </div>
    <div class="col-6 col-sm-auto">
        <label class="form-label small fw-semibold mb-1" for="fTo">Sampai</label>
        <input type="date" id="fTo" name="to" class="form-control form-control-sm" value="<?= $sampai ?>">
    </div>
    <div class="col-8 col-sm-auto">
        <label class="form-label small fw-semibold mb-1" for="fMall">Mall</label>
        <select id="fMall" name="mall" class="form-select form-select-sm">
            <option value="">Kedua Mall</option>
            <?php foreach (PestVisitModel::MALLS as $k => $v): ?>
            <option value="<?= $k ?>" <?= $mall === $k ? 'selected' : '' ?>><?= $v ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-4 col-sm-auto">
        <button type="submit" class="btn btn-sm btn-primary w-100">Tampilkan</button>
    </div>
    <div class="col-12 col-lg-auto ms-lg-2 d-flex gap-1 flex-wrap">
        <?php foreach ($presets as $label => [$a, $b]):
            $aktif = $dari === $a && $sampai === $b; ?>
        <a href="<?= $qs(['from' => $a, 'to' => $b]) ?>" class="btn btn-sm <?= $aktif ? 'btn-primary' : 'btn-outline-secondary' ?>"><?= $label ?></a>
        <?php endforeach; ?>
    </div>
</form>
</div>
</div>

<?php if ($catatan): ?>
<div class="alert <?= $r['legacyTerpotong'] ? 'alert-warning' : 'alert-info' ?> small py-2 mb-3">
    <div class="fw-semibold mb-1"><i class="bi bi-info-circle me-1"></i>Rekap bulanan hasil impor Excel pada periode ini</div>
    <ul class="mb-0 ps-3"><?php foreach ($catatan as $c): ?><li><?= esc($c) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<!-- KPI -->
<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="card text-center h-100"><div class="card-body py-3">
            <div class="small text-muted mb-1">Total Temuan</div>
            <div class="kpi-angka"><?= $nf($r['grand']) ?></div>
            <div class="small mt-1"><?= $lencana($deltaTotal) ?></div>
            <div class="small text-muted" style="font-size:.72rem">vs <?= PR::rentang($prev['dari'], $prev['sampai']) ?> (<?= $nf($prev['grand']) ?>)</div>
        </div></div>
    </div>
    <?php if (! $mall): foreach (PestVisitModel::MALLS as $mk => $ml):
        $v = $r['totalMall'][$mk] ?? 0; $pv = $prev['totalMall'][$mk] ?? 0; ?>
    <div class="col-6 col-md-3">
        <div class="card text-center h-100" style="border-top:3px solid var(<?= $mk === 'ewalk' ? '--c-ewalk' : '--c-penta' ?>)"><div class="card-body py-3">
            <div class="small text-muted mb-1"><i class="bi bi-building me-1"></i><?= $ml ?></div>
            <div class="kpi-angka"><?= $nf($v) ?></div>
            <div class="small mt-1"><?= $lencana(PR::pct($v, $pv)) ?></div>
            <div class="small text-muted" style="font-size:.72rem"><?= $r['grand'] > 0 ? round($v / $r['grand'] * 100) . '% total · ' : '' ?><?= $nf($r['kunjungan'][$mk]['n']) ?> kunjungan</div>
        </div></div>
    </div>
    <?php endforeach; else:
        $top = $r['perItem']; arsort($top); $topId = array_key_first($top);
        $topNama = $topId ? (array_column($items, 'nama', 'id')[$topId] ?? '—') : '—'; ?>
    <div class="col-6 col-md-3">
        <div class="card text-center h-100"><div class="card-body py-3">
            <div class="small text-muted mb-1">Temuan Terbanyak</div>
            <div class="kpi-angka" style="font-size:1.2rem"><?= $topId && $top[$topId] > 0 ? esc($topNama) : '—' ?></div>
            <div class="small text-muted"><?= $topId && $top[$topId] > 0 ? $nf($top[$topId]) . ' temuan' : 'tidak ada temuan' ?></div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center h-100"><div class="card-body py-3">
            <div class="small text-muted mb-1">Jenis Hama Ditemukan</div>
            <div class="kpi-angka"><?= count(array_filter($r['perItem'])) ?></div>
            <div class="small text-muted">dari <?= count($items) ?> jenis dipantau</div>
        </div></div>
    </div>
    <?php endif; ?>
    <div class="col-6 col-md-3">
        <div class="card text-center h-100"><div class="card-body py-3">
            <div class="small text-muted mb-1"><i class="bi bi-calendar-check me-1"></i>Kunjungan Tercatat</div>
            <div class="kpi-angka"><?= $nf($r['jmlKunjungan']) ?></div>
            <div class="small text-muted"><?= $nf($r['jmlNihil']) ?> nihil temuan</div>
            <?php if ($r['grandLegacy'] > 0): ?><div class="small text-muted" style="font-size:.72rem">+ <?= count($r['legacyDipakai']) ?> rekap impor bulanan</div><?php endif; ?>
        </div></div>
    </div>
</div>

<!-- Analisa -->
<div class="card mb-3" style="border-left:4px solid var(--accent-primary)">
<div class="card-body py-3">
    <div class="d-flex align-items-center gap-2 mb-2">
        <i class="bi bi-stars" style="color:var(--bs-primary)"></i>
        <span class="fw-semibold small" style="color:var(--bs-primary)">Analisa Otomatis</span>
        <span class="text-muted small"><?= PR::rentang($dari, $sampai) ?></span>
    </div>
    <ul class="analisa-list"><?php foreach ($analisa as $a): ?><li><?= esc($a) ?></li><?php endforeach; ?></ul>
</div>
</div>

<!-- Grafik -->
<div class="row g-3 mb-3">
    <div class="col-lg-5">
        <div class="card h-100">
        <div class="card-header"><h6 class="mb-0 fw-semibold"><i class="bi bi-bug me-2"></i>Temuan per Item</h6></div>
        <div class="card-body">
            <?php if (! $itemGrafik): ?><p class="text-muted text-center py-4 mb-0">Tidak ada temuan pada periode ini.</p>
            <?php else: ?><div class="chart-tinggi"><canvas id="cItem"></canvas></div><?php endif; ?>
        </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card h-100">
        <div class="card-header"><h6 class="mb-0 fw-semibold"><i class="bi bi-graph-up me-2"></i>Temuan <?= $modeLabel ?></h6></div>
        <div class="card-body">
            <?php if (array_sum(array_column($rc['ember'], 'total')) === 0): ?>
            <p class="text-muted text-center py-4 mb-0">Tidak ada temuan kunjungan untuk dirinci<?= $rc['legacy']['total'] > 0 ? ' — seluruh angka periode ini berasal dari rekap impor bulanan' : '' ?>.</p>
            <?php else: ?><div class="chart-tinggi"><canvas id="cEmber"></canvas></div>
            <?php if ($rc['legacy']['total'] > 0): ?><div class="small text-muted mt-2"><i class="bi bi-info-circle me-1"></i><?= $nf($rc['legacy']['total']) ?> temuan dari rekap impor bulanan tidak tampil di grafik ini (tanpa tanggal kunjungan).</div><?php endif; ?>
            <?php endif; ?>
        </div>
        </div>
    </div>
</div>

<!-- Rekap per item & mall -->
<div class="card mb-3">
<div class="card-header py-2 d-flex justify-content-between align-items-center flex-wrap gap-1">
    <span class="fw-semibold small"><i class="bi bi-table me-1"></i>Rekap per Item &amp; Mall</span>
    <span class="small text-muted">pembanding: <?= PR::rentang($prev['dari'], $prev['sampai']) ?></span>
</div>
<div class="table-responsive">
<table class="rekap">
<thead><tr>
    <th class="col-item">Item</th>
    <?php foreach ($r['malls'] as $mk): ?><th><?= PestVisitModel::MALLS[$mk] ?></th><?php endforeach; ?>
    <?php if (count($r['malls']) > 1): ?><th>Total</th><?php endif; ?>
    <th>Periode lalu</th><th>Perubahan</th>
</tr></thead>
<tbody>
<?php foreach ($items as $it): $id = (int) $it['id'];
    $t = $r['perItem'][$id] ?? 0; $l = $prev['perItem'][$id] ?? 0; ?>
<tr>
    <td class="col-item"><?= esc($it['nama']) ?><?= (int) $it['aktif'] ? '' : ' <span class="badge text-bg-secondary">nonaktif</span>' ?></td>
    <?php foreach ($r['malls'] as $mk): $v = $r['perMall'][$mk][$id] ?? 0; ?>
    <td class="num <?= $v ? '' : 'nol' ?>"><?= $n0($v) ?></td>
    <?php endforeach; ?>
    <?php if (count($r['malls']) > 1): ?><td class="num fw-semibold <?= $t ? '' : 'nol' ?>"><?= $n0($t) ?></td><?php endif; ?>
    <td class="num <?= $l ? '' : 'nol' ?>"><?= $n0($l) ?></td>
    <td class="num"><?= $t === 0 && $l === 0 ? '<span class="nol">—</span>' : $lencana(PR::pct($t, $l)) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
<tfoot><tr>
    <td class="col-item">Total</td>
    <?php foreach ($r['malls'] as $mk): ?><td class="num"><?= $nf($r['totalMall'][$mk] ?? 0) ?></td><?php endforeach; ?>
    <?php if (count($r['malls']) > 1): ?><td class="num"><?= $nf($r['grand']) ?></td><?php endif; ?>
    <td class="num"><?= $nf($prev['grand']) ?></td>
    <td class="num"><?= $lencana($deltaTotal) ?></td>
</tr></tfoot>
</table>
</div>
<?php if ($catatanPrev): ?>
<div class="card-footer bg-transparent small text-muted">
    <i class="bi bi-info-circle me-1"></i>Periode pembanding: <?= esc(implode(' ', $catatanPrev)) ?>
</div>
<?php endif; ?>
</div>

<!-- Rincian -->
<div class="card mb-3">
<div class="card-header py-2 d-flex justify-content-between align-items-center flex-wrap gap-1">
    <span class="fw-semibold small"><i class="bi bi-calendar3 me-1"></i>Rincian <?= $modeLabel ?></span>
    <span class="small text-muted"><?= $rc['mode'] === 'mingguan' ? 'minggu ISO Senin–Minggu, dipotong di batas rentang' : ($rc['mode'] === 'bulanan' ? 'bulan terpotong rentang ditandai tanggalnya' : 'baris pudar = belum ada catatan kunjungan') ?></span>
</div>
<div class="table-responsive">
<table class="rekap">
<thead><tr>
    <th class="col-item"><?= ['harian' => 'Tanggal', 'mingguan' => 'Minggu', 'bulanan' => 'Bulan'][$rc['mode']] ?></th>
    <?php foreach ($items as $it): ?><th><?= esc($it['nama']) ?></th><?php endforeach; ?>
    <th>Total</th><th>Kunjungan</th>
</tr></thead>
<tbody>
<?php foreach ($rc['ember'] as $e): ?>
<tr class="<?= $e['kunjungan'] === 0 && ! $e['legacy'] ? 'tanpa-catatan' : '' ?>">
    <td class="col-item"><?= esc(PR::labelEmber($e, $rc['mode'])) ?><?= $e['legacy'] ? ' <span class="badge text-bg-warning" title="Memuat rekap impor bulanan">impor</span>' : '' ?></td>
    <?php foreach ($items as $it): $v = $e['items'][(int) $it['id']] ?? 0; ?>
    <td class="num <?= $v ? '' : 'nol' ?>"><?= $n0($v) ?></td>
    <?php endforeach; ?>
    <td class="num fw-semibold"><?= $e['kunjungan'] === 0 && ! $e['legacy'] ? '·' : $nf($e['total']) ?></td>
    <td class="num"><?= $e['kunjungan'] ?: '—' ?></td>
</tr>
<?php endforeach; ?>
<?php if ($rc['legacy']['total'] > 0): ?>
<tr class="baris-impor">
    <td class="col-item">Rekap impor bulanan<span class="sub-ket">tanpa tanggal kunjungan</span></td>
    <?php foreach ($items as $it): $v = $rc['legacy']['items'][(int) $it['id']] ?? 0; ?>
    <td class="num <?= $v ? '' : 'nol' ?>"><?= $n0($v) ?></td>
    <?php endforeach; ?>
    <td class="num fw-semibold"><?= $nf($rc['legacy']['total']) ?></td>
    <td class="num"><?= $rc['legacy']['baris'] ?> rekap</td>
</tr>
<?php endif; ?>
</tbody>
<tfoot><tr>
    <td class="col-item">Total</td>
    <?php foreach ($items as $it): ?><td class="num"><?= $nf($r['perItem'][(int) $it['id']] ?? 0) ?></td><?php endforeach; ?>
    <td class="num"><?= $nf($r['grand']) ?></td>
    <td class="num"><?= $nf($r['jmlKunjungan']) ?></td>
</tr></tfoot>
</table>
</div>
<div class="card-footer bg-transparent small text-muted">
    <i class="bi bi-info-circle me-1"></i>Angka 0 hanya berarti "bersih" bila ada kunjungan tercatat;
    tanda <strong>·</strong> berarti belum ada catatan sama sekali pada periode itu.
</div>
</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
(function () {
    if (typeof Chart === 'undefined') return;
    const nId = v => Number(v).toLocaleString('id-ID');
    const warnaMall = { ewalk: '#2563eb', pentacity: '#059669' };
    const namaMall  = <?= json_encode(PestVisitModel::MALLS) ?>;
    const malls     = <?= json_encode($r['malls']) ?>;

    <?php if ($itemGrafik): ?>
    new Chart(document.getElementById('cItem'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($itemGrafik, 'nama')) ?>,
            datasets: malls.map(mk => ({
                label: namaMall[mk], backgroundColor: warnaMall[mk], borderRadius: 3,
                data: <?= json_encode(array_combine($r['malls'], array_map(fn($mk) => array_map(fn($it) => $r['perMall'][$mk][(int) $it['id']] ?? 0, $itemGrafik), $r['malls']))) ?>[mk],
            })),
        },
        options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, animation: false,
            plugins: { legend: { position: 'top' }, tooltip: { callbacks: { label: c => ' ' + c.dataset.label + ': ' + nId(c.parsed.x) } } },
            scales: { x: { stacked: true, beginAtZero: true, ticks: { precision: 0, callback: v => nId(v) } }, y: { stacked: true } } }
    });
    <?php endif; ?>

    <?php if (array_sum(array_column($rc['ember'], 'total')) > 0):
        // Ember tanpa kunjungan dikirim sebagai null — grafik menampilkan celah, bukan nol palsu.
        $sumbu = array_map(fn($e) => PR::labelSumbu($e, $rc['mode']), $rc['ember']);
        $dataMall = [];
        foreach ($r['malls'] as $mk) {
            $dataMall[$mk] = array_map(fn($e) => ($e['kunjungan'] === 0 && ! $e['legacy']) ? null : ($e['mall'][$mk] ?? 0), $rc['ember']);
        } ?>
    const dataMall = <?= json_encode($dataMall) ?>;
    new Chart(document.getElementById('cEmber'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($sumbu) ?>,
            datasets: malls.map(mk => ({ label: namaMall[mk], data: dataMall[mk], backgroundColor: warnaMall[mk], borderRadius: 2 })),
        },
        options: { responsive: true, maintainAspectRatio: false, animation: false,
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { position: 'top' }, tooltip: { callbacks: { label: c => ' ' + c.dataset.label + ': ' + (c.raw === null ? 'belum ada catatan' : nId(c.parsed.y)) } } },
            scales: { x: { stacked: true, ticks: { autoSkip: true, maxRotation: 0 } }, y: { stacked: true, beginAtZero: true, ticks: { precision: 0, callback: v => nId(v) } } } }
    });
    <?php endif; ?>
})();
</script>
<?= $this->endSection() ?>
