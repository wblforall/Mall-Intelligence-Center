<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<style>
.rekap { border-collapse: collapse; width: 100%; }
.rekap th, .rekap td { padding: .4rem .5rem; border-bottom: 1px solid var(--bs-border-color); font-size: .85rem; white-space: nowrap; }
.rekap thead th { background: var(--bs-tertiary-bg); font-size: .72rem; text-align: center; }
.rekap td.num { text-align: center; font-variant-numeric: tabular-nums; }
.rekap td.nol { opacity: .35; }
.rekap tfoot td { font-weight: 700; background: var(--bs-tertiary-bg); }
.rekap .col-item { text-align: left; font-weight: 500; position: sticky; left: 0; background: var(--bs-body-bg); }
.rekap thead .col-item { background: var(--bs-tertiary-bg); }
.naik { color: var(--bs-danger); } .turun { color: var(--bs-success); }
</style>
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php
$bulanNama = [1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'Mei',6=>'Jun',7=>'Jul',8=>'Ags',9=>'Sep',10=>'Okt',11=>'Nov',12=>'Des'];
$qs = fn(array $ubah) => base_url('pest/summary') . '?' . http_build_query(array_merge(['tahun' => $tahun, 'mall' => $mall], $ubah));

// Ratakan bulanan lintas mall bila filter mall kosong.
$sel = [];  // [bulan => [item_id => n]]
foreach ($bulanan as $bln => $perMall) {
    foreach ($perMall as $mk => $perItem) {
        foreach ($perItem as $iid => $n) $sel[$bln][$iid] = ($sel[$bln][$iid] ?? 0) + $n;
    }
}
$ratakan = function (array $perMall): array {
    $out = [];
    foreach ($perMall as $perItem) foreach ($perItem as $iid => $n) $out[$iid] = ($out[$iid] ?? 0) + $n;
    return $out;
};
$ini  = $ratakan($iniTahun);
$lalu = $ratakan($laluTahun);
$grandIni = array_sum($ini); $grandLalu = array_sum($lalu);
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-bar-chart me-2"></i>Rekap Pest Control</h4>
        <small class="text-muted">Tahun <?= $tahun ?> &middot; <?= $mall ? \App\Models\PestVisitModel::MALLS[$mall] : 'Kedua Mall' ?></small>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= base_url('pest') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-graph-up me-1"></i>Tren Mingguan</a>
        <a href="<?= base_url('pest/laporan-bulanan?bulan=' . date('Y-m')) ?>" target="_blank" class="btn btn-sm btn-outline-dark">
            <i class="bi bi-printer me-1"></i>Laporan Bulanan
        </a>
    </div>
</div>

<div class="card mb-3">
<div class="card-body py-2 d-flex flex-wrap gap-3 align-items-center">
    <div class="btn-group btn-group-sm">
        <a href="<?= $qs(['mall' => null]) ?>" class="btn <?= ! $mall ? 'btn-primary' : 'btn-outline-secondary' ?>">Kedua Mall</a>
        <a href="<?= $qs(['mall' => 'ewalk']) ?>" class="btn <?= $mall === 'ewalk' ? 'btn-primary' : 'btn-outline-secondary' ?>">eWalk</a>
        <a href="<?= $qs(['mall' => 'pentacity']) ?>" class="btn <?= $mall === 'pentacity' ? 'btn-primary' : 'btn-outline-secondary' ?>">Pentacity</a>
    </div>
    <?php if ($tahunAda): ?>
    <div class="btn-group btn-group-sm">
        <?php foreach ($tahunAda as $th): ?>
        <a href="<?= $qs(['tahun' => $th]) ?>" class="btn <?= $tahun === $th ? 'btn-primary' : 'btn-outline-secondary' ?>"><?= $th ?></a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
</div>

<!-- ══ Rekap bulanan ══ -->
<div class="card mb-3">
<div class="card-header py-2"><span class="fw-semibold small"><i class="bi bi-calendar3 me-1"></i>Rekap Bulanan <?= $tahun ?></span></div>
<div class="table-responsive">
<table class="rekap">
<thead>
<tr>
    <th class="col-item">Item</th>
    <?php for ($b = 1; $b <= 12; $b++): ?><th><?= $bulanNama[$b] ?></th><?php endfor; ?>
    <th style="background:var(--bs-secondary-bg)">Total</th>
</tr>
</thead>
<tbody>
<?php $totBln = []; foreach ($items as $it): $id = (int) $it['id']; $totRow = 0; ?>
<tr>
    <td class="col-item"><?= esc($it['nama']) ?></td>
    <?php for ($b = 1; $b <= 12; $b++):
        $kunci = sprintf('%d-%02d', $tahun, $b);
        $v = $sel[$kunci][$id] ?? 0; $totRow += $v;
        $totBln[$b] = ($totBln[$b] ?? 0) + $v; ?>
    <td class="num <?= $v === 0 ? 'nol' : '' ?>"><?= $v > 0 ? number_format($v) : '—' ?></td>
    <?php endfor; ?>
    <td class="num fw-bold"><?= $totRow > 0 ? number_format($totRow) : '—' ?></td>
</tr>
<?php endforeach; ?>
</tbody>
<tfoot>
<tr>
    <td class="col-item">Total</td>
    <?php for ($b = 1; $b <= 12; $b++): ?>
    <td class="num"><?= ! empty($totBln[$b]) ? number_format($totBln[$b]) : '—' ?></td>
    <?php endfor; ?>
    <td class="num"><?= number_format(array_sum($totBln)) ?></td>
</tr>
</tfoot>
</table>
</div>
</div>

<!-- ══ Pembanding antar tahun ══ -->
<div class="card">
<div class="card-header py-2 d-flex justify-content-between align-items-center">
    <span class="fw-semibold small"><i class="bi bi-arrow-left-right me-1"></i><?= $tahun ?> vs <?= $tahun - 1 ?></span>
    <?php if ($grandLalu > 0):
        $d = round(($grandIni - $grandLalu) / $grandLalu * 100, 1); ?>
    <span class="small <?= $d >= 0 ? 'naik' : 'turun' ?>">
        <?= $d >= 0 ? '▲' : '▼' ?> <?= abs($d) ?>% total
    </span>
    <?php endif; ?>
</div>
<div class="table-responsive">
<table class="rekap">
<thead><tr>
    <th class="col-item">Item</th><th><?= $tahun ?></th><th><?= $tahun - 1 ?></th><th>Selisih</th><th>Perubahan</th>
</tr></thead>
<tbody>
<?php foreach ($items as $it): $id = (int) $it['id'];
    $a = $ini[$id] ?? 0; $b = $lalu[$id] ?? 0;
    if ($a === 0 && $b === 0) continue;
    $sel2 = $a - $b;
    $pct  = $b > 0 ? round($sel2 / $b * 100, 1) : null; ?>
<tr>
    <td class="col-item"><?= esc($it['nama']) ?></td>
    <td class="num fw-semibold"><?= number_format($a) ?></td>
    <td class="num"><?= number_format($b) ?></td>
    <td class="num <?= $sel2 > 0 ? 'naik' : ($sel2 < 0 ? 'turun' : '') ?>"><?= $sel2 > 0 ? '+' : '' ?><?= number_format($sel2) ?></td>
    <td class="num <?= $pct === null ? '' : ($pct >= 0 ? 'naik' : 'turun') ?>">
        <?= $pct === null ? '<span class="text-muted">baru</span>' : (($pct >= 0 ? '▲ ' : '▼ ') . abs($pct) . '%') ?>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
<tfoot><tr>
    <td class="col-item">Total</td>
    <td class="num"><?= number_format($grandIni) ?></td>
    <td class="num"><?= number_format($grandLalu) ?></td>
    <td class="num"><?= ($grandIni - $grandLalu) > 0 ? '+' : '' ?><?= number_format($grandIni - $grandLalu) ?></td>
    <td class="num"><?= $grandLalu > 0 ? round(($grandIni - $grandLalu) / $grandLalu * 100, 1) . '%' : '—' ?></td>
</tr></tfoot>
</table>
</div>
<div class="card-footer bg-transparent">
    <small class="text-muted"><i class="bi bi-info-circle me-1"></i>
        Rekap bulanan dan tahunan <strong>memuat data hasil impor Excel 2025&ndash;2026</strong> —
        di sini satuannya bulan, dan data impor memang bulanan. Halaman
        <a href="<?= base_url('pest') ?>">Tren Mingguan</a> mengecualikannya karena data itu
        tidak punya resolusi mingguan.
    </small>
</div>
</div>

<?= $this->endSection() ?>
