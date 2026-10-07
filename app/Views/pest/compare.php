<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<style>
:root { --c-p1: #6366f1; --c-p2: #f97316; --c-p3: #10b981; }
.cmp { border-collapse: collapse; width: 100%; }
.cmp th, .cmp td { padding: .4rem .55rem; border-bottom: 1px solid var(--bs-border-color); font-size: .85rem; white-space: nowrap; }
.cmp thead th { background: var(--bs-tertiary-bg); font-size: .72rem; text-align: right; }
.cmp td.num { text-align: right; font-variant-numeric: tabular-nums; }
.cmp td.nol { opacity: .35; }
.cmp .col-item { text-align: left !important; font-weight: 500; position: sticky; left: 0; background: var(--bs-body-bg); z-index: 1; }
.cmp thead .col-item, .cmp tr.grup td { background: var(--bs-tertiary-bg); }
.cmp tr.grup td { font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: var(--bs-secondary-color); }
.cmp tr.total td { font-weight: 700; }
.p-dot { display: inline-block; width: .6rem; height: .6rem; border-radius: 50%; margin-right: .3rem; vertical-align: .05rem; }
/* Temuan NAIK = buruk → merah; TURUN = baik → hijau (kebalikan Traffic). */
.lencana-d { display: inline-block; padding: .1rem .45rem; border-radius: 999px; font-size: .72rem; font-weight: 600; white-space: nowrap; }
.lencana-d.naik  { color: var(--bs-danger);  background: rgba(var(--bs-danger-rgb), .12); }
.lencana-d.turun { color: var(--bs-success); background: rgba(var(--bs-success-rgb), .12); }
.lencana-d.netral { color: var(--bs-secondary-color); background: var(--bs-tertiary-bg); }
.chart-tinggi { position: relative; height: 280px; }
</style>
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php
use App\Libraries\PestRekap as PR;
use App\Models\PestVisitModel;

$nf = fn($v) => PR::angka($v);
$warna = [1 => 'var(--c-p1)', 2 => 'var(--c-p2)', 3 => 'var(--c-p3)'];
$label = [];
foreach ($periode as $i => [$a, $b]) $label[$i] = PR::rentang($a, $b);
$lencana = function (?float $pct): string {
    if ($pct === null) return '<span class="lencana-d netral">—</span>';
    $cls = $pct > 0 ? 'naik' : ($pct < 0 ? 'turun' : 'netral');
    return '<span class="lencana-d ' . $cls . '">' . ($pct > 0 ? '▲ ' : ($pct < 0 ? '▼ ' : '')) . PR::persen($pct) . '</span>';
};
$qsPrint = ['from1' => $periode[1][0], 'to1' => $periode[1][1], 'from2' => $periode[2][0], 'to2' => $periode[2][1]];
if ($hasP3) $qsPrint += ['from3' => $periode[3][0], 'to3' => $periode[3][1]];
if ($mall) $qsPrint['mall'] = $mall;
$adaData = array_sum($total['v']) > 0;
$itemGrafik = array_values(array_filter($perItem, fn($b) => array_sum($b['v']) > 0));

// $netral: selisih tanpa warna baik/buruk (jumlah kunjungan naik bukan hal buruk).
$sel = function (array $b, string $kelas = '', bool $netral = false) use ($p, $nf, $lencana): string {
    $h = '<tr class="' . $kelas . '"><td class="col-item">' . esc($b['label']) . '</td>';
    foreach ($p as $i => $_) $h .= '<td class="num ' . ($b['v'][$i] ? '' : 'nol') . '">' . ($b['v'][$i] ? $nf($b['v'][$i]) : '—') . '</td>';
    foreach ($b['d'] as $i => $d) {
        $isi = $netral ? '<span class="lencana-d netral">' . PR::persen($d, true) . '</span>' : $lencana($d);
        $h .= '<td class="num">' . ($b['v'][1] === 0 && $b['v'][$i] === 0 ? '<span class="nol">—</span>' : $isi) . '</td>';
    }
    return $h . '</tr>';
};
$kolomD = count($p) - 1;
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-arrow-left-right me-2"></i>Compare Periode Pest Control</h4>
        <small class="text-muted">Bandingkan dua atau tiga periode berdampingan &middot; <?= $mall ? PestVisitModel::MALLS[$mall] : 'Kedua Mall' ?></small>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= base_url('pest/compare/print') . '?' . http_build_query($qsPrint) ?>" target="_blank" class="btn btn-sm btn-outline-danger">
            <i class="bi bi-printer me-1"></i>Print / PDF</a>
        <a href="<?= base_url('pest/rekap') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-bar-chart-line me-1"></i>Rekap Periode</a>
    </div>
</div>

<div class="card mb-3">
<div class="card-body py-3">
<form method="GET" class="row g-3 align-items-end">
    <?php foreach ([1, 2, 3] as $i): $ada = isset($periode[$i]); ?>
    <div class="col-12 col-md-auto <?= $i === 3 && ! $hasP3 ? 'd-none' : '' ?>" <?= $i === 3 ? 'id="p3Wrap"' : '' ?>>
        <div class="mb-2"><span class="badge rounded-pill px-3 py-2" style="background:<?= $warna[$i] ?>;font-size:.8rem">Periode <?= $i ?><?= $i === 1 ? ' · patokan' : '' ?></span></div>
        <div class="d-flex gap-2">
            <div>
                <label class="form-label small fw-semibold mb-1" for="from<?= $i ?>">Dari</label>
                <input type="date" id="from<?= $i ?>" name="from<?= $i ?>" class="form-control form-control-sm" value="<?= $ada ? $periode[$i][0] : '' ?>">
            </div>
            <div>
                <label class="form-label small fw-semibold mb-1" for="to<?= $i ?>">Sampai</label>
                <input type="date" id="to<?= $i ?>" name="to<?= $i ?>" class="form-control form-control-sm" value="<?= $ada ? $periode[$i][1] : '' ?>">
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <div class="col-6 col-md-auto">
        <label class="form-label small fw-semibold mb-1" for="cMall">Mall</label>
        <select id="cMall" name="mall" class="form-select form-select-sm">
            <option value="">Kedua Mall</option>
            <?php foreach (PestVisitModel::MALLS as $k => $v): ?>
            <option value="<?= $k ?>" <?= $mall === $k ? 'selected' : '' ?>><?= $v ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-12 col-md-auto d-flex gap-2">
        <button type="submit" class="btn btn-sm btn-primary">Bandingkan</button>
        <button type="button" id="toggleP3" class="btn btn-sm <?= $hasP3 ? 'btn-outline-danger' : 'btn-outline-secondary' ?>">
            <i class="bi <?= $hasP3 ? 'bi-dash-circle' : 'bi-plus-circle' ?> me-1"></i><?= $hasP3 ? 'Hapus Periode 3' : 'Periode 3' ?>
        </button>
    </div>
</form>
</div>
</div>

<?php $adaCatatan = array_filter($catatan); if ($adaCatatan): ?>
<div class="alert alert-warning small py-2 mb-3">
    <div class="fw-semibold mb-1"><i class="bi bi-info-circle me-1"></i>Rekap bulanan hasil impor Excel</div>
    <ul class="mb-0 ps-3">
    <?php foreach ($adaCatatan as $i => $cs): foreach ($cs as $c): ?>
        <li><span class="p-dot" style="background:<?= $warna[$i] ?>"></span><strong>P<?= $i ?></strong> — <?= esc($c) ?></li>
    <?php endforeach; endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<!-- KPI -->
<div class="row g-3 mb-3">
<?php
$kpi = [['Total Temuan', 'bi-bug', $total]];
foreach ($perMall as $b) if (! $mall) $kpi[] = [$b['label'], 'bi-building', $b];
$kpi[] = ['Kunjungan Tercatat', 'bi-calendar-check', $kunjungan];
foreach ($kpi as [$judul, $ikon, $b]): ?>
<div class="col-6 col-md-3">
    <div class="card h-100"><div class="card-body py-3 px-3">
        <div class="small text-muted mb-2"><i class="bi <?= $ikon ?> me-1"></i><?= $judul ?></div>
        <?php foreach ($p as $i => $_): ?>
        <div class="d-flex justify-content-between align-items-center gap-2 mb-1">
            <span style="font-size:.72rem;font-weight:600;color:<?= $warna[$i] ?>">P<?= $i ?></span>
            <span class="d-flex align-items-center gap-2">
                <?php if ($i > 1 && $b !== $kunjungan): ?><?= $lencana($b['d'][$i]) ?><?php endif; ?>
                <span class="fw-bold" style="color:<?= $warna[$i] ?>;font-size:1.05rem"><?= $nf($b['v'][$i]) ?></span>
            </span>
        </div>
        <?php endforeach; ?>
    </div></div>
</div>
<?php endforeach; ?>
</div>

<!-- Grafik -->
<div class="row g-3 mb-3">
    <div class="col-lg-8">
        <div class="card h-100">
        <div class="card-header"><h6 class="mb-0 fw-semibold"><i class="bi bi-bar-chart me-2"></i>Temuan per Item</h6></div>
        <div class="card-body">
            <?php if (! $itemGrafik): ?><p class="text-muted text-center py-4 mb-0">Tidak ada temuan di periode mana pun.</p>
            <?php else: ?><div class="chart-tinggi"><canvas id="cItem"></canvas></div><?php endif; ?>
        </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
        <div class="card-header"><h6 class="mb-0 fw-semibold"><i class="bi bi-building me-2"></i>Temuan per Mall</h6></div>
        <div class="card-body">
            <?php if (! $adaData): ?><p class="text-muted text-center py-4 mb-0">Belum ada data.</p>
            <?php else: ?><div class="chart-tinggi"><canvas id="cMallChart"></canvas></div><?php endif; ?>
        </div>
        </div>
    </div>
</div>

<!-- Tabel -->
<div class="card mb-3">
<div class="card-header py-2"><span class="fw-semibold small"><i class="bi bi-table me-1"></i>Perbandingan Rinci</span>
    <span class="small text-muted ms-2">selisih % dihitung terhadap Periode 1</span></div>
<div class="table-responsive">
<table class="cmp">
<thead><tr>
    <th class="col-item">Uraian</th>
    <?php foreach ($p as $i => $_): ?><th><span class="p-dot" style="background:<?= $warna[$i] ?>"></span>P<?= $i ?><br><span class="fw-normal"><?= $label[$i] ?></span></th><?php endforeach; ?>
    <?php foreach ($p as $i => $_): if ($i === 1) continue; ?><th>P<?= $i ?> vs P1</th><?php endforeach; ?>
</tr></thead>
<tbody>
    <tr class="grup"><td class="col-item" colspan="<?= count($p) + $kolomD + 1 ?>">Per item</td></tr>
    <?php foreach ($perItem as $b) echo $sel($b); ?>
    <tr class="grup"><td class="col-item" colspan="<?= count($p) + $kolomD + 1 ?>">Per mall</td></tr>
    <?php foreach ($perMall as $b) echo $sel($b); ?>
    <tr class="grup"><td class="col-item" colspan="<?= count($p) + $kolomD + 1 ?>">Ringkasan</td></tr>
    <?= $sel($total, 'total') ?>
    <?php if (array_sum($impor['v']) > 0) echo $sel($impor, '', true); ?>
    <?= $sel($kunjungan, '', true) ?>
    <?= $sel($nihil, '', true) ?>
    <tr>
        <td class="col-item">Rata-rata temuan per hari</td>
        <?php foreach ($p as $i => $r): ?><td class="num"><?= str_replace('.', ',', (string) round($r['grand'] / max(1, $r['hari']), 1)) ?></td><?php endforeach; ?>
        <?php foreach ($p as $i => $r): if ($i === 1) continue;
            $a = $p[1]['grand'] / max(1, $p[1]['hari']); $b2 = $r['grand'] / max(1, $r['hari']); ?>
        <td class="num"><?= $lencana($a > 0 ? round(($b2 - $a) / $a * 100, 1) : null) ?></td>
        <?php endforeach; ?>
    </tr>
    <tr>
        <td class="col-item">Panjang periode (hari)</td>
        <?php foreach ($p as $r): ?><td class="num"><?= $r['hari'] ?></td><?php endforeach; ?>
        <?php for ($k = 0; $k < $kolomD; $k++): ?><td></td><?php endfor; ?>
    </tr>
</tbody>
</table>
</div>
<div class="card-footer bg-transparent small text-muted">
    <i class="bi bi-info-circle me-1"></i>Merah = temuan naik (memburuk), hijau = turun (membaik).
    Bila panjang periode berbeda, bandingkan juga baris <em>rata-rata per hari</em>.
</div>
</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
document.getElementById('toggleP3').addEventListener('click', function () {
    const wrap = document.getElementById('p3Wrap');
    if (! wrap.classList.contains('d-none')) {
        document.getElementById('from3').value = '';
        document.getElementById('to3').value = '';
        wrap.classList.add('d-none');
        this.innerHTML = '<i class="bi bi-plus-circle me-1"></i>Periode 3';
        this.className = 'btn btn-sm btn-outline-secondary';
    } else {
        wrap.classList.remove('d-none');
        this.innerHTML = '<i class="bi bi-dash-circle me-1"></i>Hapus Periode 3';
        this.className = 'btn btn-sm btn-outline-danger';
    }
});
(function () {
    if (typeof Chart === 'undefined') return;
    const nId = v => Number(v).toLocaleString('id-ID');
    const warna = { 1: '#6366f1', 2: '#f97316', 3: '#10b981' };
    const label = <?= json_encode($label) ?>;
    const opsi = { responsive: true, maintainAspectRatio: false, animation: false,
        interaction: { mode: 'index', intersect: false },
        plugins: { legend: { position: 'top' }, tooltip: { callbacks: { label: c => ' ' + c.dataset.label + ': ' + nId(c.parsed.y) } } },
        scales: { y: { beginAtZero: true, ticks: { precision: 0, callback: v => nId(v) } } } };
    const ds = data => Object.keys(data).map(i => ({ label: 'P' + i + ' · ' + label[i], data: data[i], backgroundColor: warna[i], borderRadius: 3 }));

    <?php if ($itemGrafik):
        $d = []; foreach ($p as $i => $_) $d[$i] = array_map(fn($b) => $b['v'][$i], $itemGrafik); ?>
    new Chart(document.getElementById('cItem'), { type: 'bar',
        data: { labels: <?= json_encode(array_column($itemGrafik, 'label')) ?>, datasets: ds(<?= json_encode($d) ?>) }, options: opsi });
    <?php endif; ?>
    <?php if ($adaData):
        $d = []; foreach ($p as $i => $_) $d[$i] = array_map(fn($b) => $b['v'][$i], $perMall); ?>
    new Chart(document.getElementById('cMallChart'), { type: 'bar',
        data: { labels: <?= json_encode(array_column($perMall, 'label')) ?>, datasets: ds(<?= json_encode($d) ?>) }, options: opsi });
    <?php endif; ?>
})();
</script>
<?= $this->endSection() ?>
