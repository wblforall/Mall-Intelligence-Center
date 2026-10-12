<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<style>
.bd-kpi { border-radius:.75rem; padding:1rem 1.15rem; height:100%; }
.bd-kpi .lbl { font-size:.75rem; color:var(--bs-secondary-color); }
.bd-kpi .val { font-weight:700; font-size:1.15rem; margin-top:.15rem; font-variant-numeric:tabular-nums; white-space:nowrap; }
@media (max-width:575.98px) { .bd-kpi { padding:.75rem .8rem; } .bd-kpi .val { font-size:.92rem; } }
.bd-bar { height:6px; border-radius:3px; background:var(--bs-secondary-bg); overflow:hidden; min-width:60px; }
.bd-bar > div { height:100%; border-radius:3px; }
.bd-tabel { font-size:.82rem; }
.bd-tabel td, .bd-tabel th { white-space:nowrap; font-variant-numeric:tabular-nums; }
.bd-tabel thead th { font-size:.72rem; text-transform:uppercase; letter-spacing:.03em; color:var(--bs-secondary-color); font-weight:600; }
.bd-tabel .col-pos { position:sticky; left:0; background-color:var(--card-bg); background-image:linear-gradient(var(--card-bg),var(--card-bg)); z-index:1; min-width:220px; white-space:normal; }
.bd-tabel tfoot td { font-weight:700; border-top:2px solid var(--bs-border-color); }
.bd-tabel .grp { border-left:1px solid var(--bs-border-color); }
/* tema memaksa warna .table td dengan !important — perlu selektor lebih spesifik */
.bd-lewat, .table td.bd-lewat, .table td.bd-lewat a.sel { color:var(--bs-danger) !important; font-weight:600; }
.bd-ada-rinci { font-size:.7rem; color:var(--bs-primary-text-emphasis); margin-right:.25rem; cursor:help; }
.bd-tabel a.sel { color:inherit; text-decoration:none; border-bottom:1px dotted var(--bs-secondary-color); }
.bd-tabel a.sel:hover { color:var(--bs-primary); }

.bd-tabel thead .col-pos, .bd-tabel tfoot .col-pos { background-color:var(--card-bg) !important; background-image:linear-gradient(var(--card-bg),var(--card-bg)) !important; z-index:3; }
</style>
<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= $this->include('budget_dept/_kepala') ?>
<?php
$BLN = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
$t   = $rekap['total'];
$q   = fn (array $x = []) => array_merge(['tahun' => $tahun, 'mal' => $mal, 'sd' => $sd, 'tampil' => $tampil], $x);
$warna = fn (?float $p) => $p === null ? 'secondary' : ($p > 100 ? 'danger' : ($p > 85 ? 'warning' : 'success'));
?>

<?php if (! $rekap['baris']): ?>
<div class="card"><div class="card-body text-center py-5">
    <i class="bi bi-wallet2 fs-1 text-body-tertiary"></i>
    <h6 class="mt-3">Belum ada pos budget untuk <?= esc($dept['name']) ?></h6>
    <p class="text-muted small mb-3">Mulai dengan membuat daftar pos (mis. "Biaya reimburse poin", "Sponsor"), lalu isi budget per bulan.</p>
    <a href="<?= $url('pos') ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Buat Pos Budget</a>
</div></div>
<?php else: ?>

<!-- Kendali -->
<div class="d-flex flex-wrap gap-2 align-items-center mb-3">
    <?php if ($pisahMal): ?>
    <div class="btn-group btn-group-sm" role="group" aria-label="Mal">
        <?php foreach (['semua' => 'Gabungan'] + \App\Libraries\BudgetRekap::MAL as $k => $l): ?>
        <a href="<?= $url('', $q(['mal' => $k])) ?>" class="btn btn-outline-secondary <?= $mal === $k ? 'active' : '' ?>"><?= $l ?></a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <form method="GET" class="d-flex align-items-center gap-1">
        <?php foreach ($q() as $k => $v): if ($k === 'sd') continue; ?>
        <input type="hidden" name="<?= $k ?>" value="<?= esc($v) ?>">
        <?php endforeach; ?>
        <?php if ($isAdmin): ?><input type="hidden" name="dept" value="<?= $dept['id'] ?>"><?php endif; ?>
        <label class="small text-muted text-nowrap" for="bd-sd">Budget s.d.</label>
        <select id="bd-sd" name="sd" class="form-select form-select-sm" onchange="this.form.submit()">
            <?php foreach ($BLN as $m => $n): ?>
            <option value="<?= $m ?>" <?= $m === $sd ? 'selected' : '' ?>><?= $n ?></option>
            <?php endforeach; ?>
        </select>
    </form>
    <div class="btn-group btn-group-sm ms-auto" role="group" aria-label="Tampilan">
        <a href="<?= $url('', $q(['tampil' => 'ringkas'])) ?>" class="btn btn-outline-secondary <?= $tampil === 'ringkas' ? 'active' : '' ?>"><i class="bi bi-list me-1"></i>Ringkas</a>
        <a href="<?= $url('', $q(['tampil' => 'bulanan'])) ?>" class="btn btn-outline-secondary <?= $tampil === 'bulanan' ? 'active' : '' ?>"><i class="bi bi-calendar3 me-1"></i>Per Bulan</a>
    </div>
</div>

<!-- KPI -->
<div class="row g-2 mb-3">
    <div class="col-6 col-lg">
        <div class="card bd-kpi"><div class="lbl">Budget <?= $tahun ?></div><div class="val">Rp <?= rpb($t['budget'], false) ?></div></div>
    </div>
    <div class="col-6 col-lg">
        <div class="card bd-kpi"><div class="lbl">Budget Jan–<?= $BLN[$sd] ?></div><div class="val">Rp <?= rpb($t['budgetSd'], false) ?></div></div>
    </div>
    <div class="col-6 col-lg">
        <div class="card bd-kpi"><div class="lbl">Realisasi</div>
            <div class="val <?= $t['realisasi'] > $t['budgetSd'] && $t['budgetSd'] > 0 ? 'bd-lewat' : '' ?>">Rp <?= rpb($t['realisasi'], false) ?></div>
            <?php if ($t['pctSd'] !== null): ?>
            <div class="d-flex align-items-center gap-2 mt-1">
                <div class="bd-bar flex-grow-1"><div class="bg-<?= $warna($t['pctSd']) ?>" style="width:<?= min(100, $t['pctSd']) ?>%"></div></div>
                <span class="small <?= $t['pctSd'] > 100 ? 'bd-lewat' : '' ?>"><?= number_format($t['pctSd'], 0) ?>%</span>
            </div>
            <div class="lbl mt-1">dari budget Jan–<?= $BLN[$sd] ?></div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-6 col-lg">
        <div class="card bd-kpi"><div class="lbl">Sisa Budget <?= $tahun ?></div>
            <div class="val <?= $t['sisa'] < 0 ? 'bd-lewat' : 'text-success' ?>">Rp <?= rpb($t['sisa'], false) ?></div>
        </div>
    </div>
    <div class="col-6 col-lg">
        <div class="card bd-kpi"><div class="lbl">Realisasi <?= $tahun - 1 ?></div><div class="val">Rp <?= rpb($t['lalu'], false) ?></div></div>
    </div>
</div>

<?php if ($rekap['tanpaMal'] > 0): ?>
<div class="alert alert-warning small py-2">
    <i class="bi bi-exclamation-triangle me-1"></i>
    Ada Rp <?= rpb($rekap['tanpaMal'], false) ?> budget/realisasi tanpa mal yang tidak masuk ke tampilan <?= esc(\App\Libraries\BudgetRekap::MAL[$mal]) ?>. Lihat di tampilan Gabungan.
</div>
<?php endif; ?>

<div class="card">
<div class="table-responsive">
<?php if ($tampil === 'ringkas'): ?>
<table class="table table-hover align-middle mb-0 bd-tabel">
    <thead><tr>
        <th class="col-pos">Pos</th>
        <th class="text-end">Budget <?= $tahun ?></th>
        <th class="text-end">Budget Jan–<?= $BLN[$sd] ?></th>
        <th class="text-end">Realisasi</th>
        <th style="min-width:130px">Terpakai (s.d. <?= $BLN[$sd] ?>)</th>
        <th class="text-end">Sisa <?= $tahun ?></th>
        <th class="text-end">Realisasi <?= $tahun - 1 ?></th>
    </tr></thead>
    <tbody>
    <?php $no = 0; foreach ($rekap['baris'] as $id => $b): $no++; ?>
    <tr>
        <td class="col-pos">
            <span class="text-muted me-1"><?= $no ?>.</span><?= esc($b['pos']['nama']) ?>
            <?php if (empty($b['pos']['aktif'])): ?><span class="badge text-bg-secondary ms-1">nonaktif</span><?php endif; ?>
        </td>
        <td class="text-end"><?= rpb($b['budget']) ?></td>
        <td class="text-end"><?= rpb($b['budgetSd']) ?></td>
        <td class="text-end <?= $b['realisasi'] > $b['budgetSd'] ? 'bd-lewat' : '' ?>">
            <?php if ($b['realisasi'] != 0): ?>
            <a class="sel" href="<?= $url('realisasi', ['tahun' => $tahun, 'pos' => $id]) ?>"><?= rpb($b['realisasi']) ?></a>
            <?php else: ?><?= rpb(0) ?><?php endif; ?>
        </td>
        <td>
            <?php if ($b['pctSd'] !== null): ?>
            <div class="d-flex align-items-center gap-2">
                <div class="bd-bar flex-grow-1"><div class="bg-<?= $warna($b['pctSd']) ?>" style="width:<?= min(100, $b['pctSd']) ?>%"></div></div>
                <span class="<?= $b['pctSd'] > 100 ? 'bd-lewat' : '' ?>" style="min-width:42px;text-align:right"><?= number_format($b['pctSd'], 0) ?>%</span>
            </div>
            <?php elseif ($b['realisasi'] > 0): ?>
            <span class="badge text-bg-danger-subtle text-danger-emphasis">tanpa budget</span>
            <?php endif; ?>
        </td>
        <td class="text-end <?= $b['sisa'] < 0 ? 'bd-lewat' : '' ?>"><?= rpb($b['sisa']) ?></td>
        <td class="text-end text-muted"><?= rpb($b['lalu']) ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot><tr>
        <td class="col-pos">Total</td>
        <td class="text-end"><?= rpb($t['budget'], false) ?></td>
        <td class="text-end"><?= rpb($t['budgetSd'], false) ?></td>
        <td class="text-end <?= $t['realisasi'] > $t['budgetSd'] ? 'bd-lewat' : '' ?>"><?= rpb($t['realisasi'], false) ?></td>
        <td><?= $t['pctSd'] !== null ? number_format($t['pctSd'], 0) . '%' : '' ?></td>
        <td class="text-end <?= $t['sisa'] < 0 ? 'bd-lewat' : '' ?>"><?= rpb($t['sisa'], false) ?></td>
        <td class="text-end text-muted"><?= rpb($t['lalu'], false) ?></td>
    </tr></tfoot>
</table>

<?php else: ?>
<table class="table table-sm table-hover align-middle mb-0 bd-tabel">
    <thead>
        <tr>
            <th class="col-pos" rowspan="2">Pos</th>
            <?php foreach ($BLN as $m => $n): ?><th class="text-center grp" colspan="2"><?= $n ?></th><?php endforeach; ?>
            <th class="text-center grp" colspan="3">Total <?= $tahun ?></th>
        </tr>
        <tr>
            <?php foreach ($BLN as $m => $n): ?><th class="text-end grp">Budget</th><th class="text-end">Real.</th><?php endforeach; ?>
            <th class="text-end grp">Budget</th><th class="text-end">Real.</th><th class="text-end">Sisa</th>
        </tr>
    </thead>
    <tbody>
    <?php $no = 0; foreach ($rekap['baris'] as $id => $b): $no++; ?>
    <tr>
        <td class="col-pos"><span class="text-muted me-1"><?= $no ?>.</span><?= esc($b['pos']['nama']) ?></td>
        <?php foreach ($BLN as $m => $n): $s = $b['bulan'][$m];
            $ketJudul = implode("\n", array_map(fn ($x) => $x['k'] . ': ' . number_format($x['j'], 0, ',', '.') . ($mal === 'semua' && isset(\App\Libraries\BudgetRekap::MAL[$x['mall']]) ? ' (' . \App\Libraries\BudgetRekap::MAL[$x['mall']] . ')' : ''), $s['ket']));
        ?>
        <td class="text-end grp" <?= $ketJudul ? 'title="' . esc($ketJudul) . '"' : '' ?>>
            <?php if ($s['ket']): ?><i class="bi bi-list-ul bd-ada-rinci" aria-hidden="true"></i><?php endif; ?><?= rpb($s['b']) ?>
        </td>
        <td class="text-end <?= $s['r'] > $s['b'] ? 'bd-lewat' : '' ?>">
            <?php if ($s['r'] != 0): ?>
            <a class="sel" href="<?= $url('realisasi', ['tahun' => $tahun, 'bulan' => $m, 'pos' => $id]) ?>"><?= rpb($s['r']) ?></a>
            <?php else: ?><?= rpb(0) ?><?php endif; ?>
        </td>
        <?php endforeach; ?>
        <td class="text-end grp"><?= rpb($b['budget']) ?></td>
        <td class="text-end <?= $b['realisasi'] > $b['budget'] ? 'bd-lewat' : '' ?>"><?= rpb($b['realisasi']) ?></td>
        <td class="text-end <?= $b['sisa'] < 0 ? 'bd-lewat' : '' ?>"><?= rpb($b['sisa']) ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot><tr>
        <td class="col-pos">Total</td>
        <?php foreach ($BLN as $m => $n): $s = $t['bulan'][$m]; ?>
        <td class="text-end grp"><?= rpb($s['b']) ?></td>
        <td class="text-end <?= $s['r'] > $s['b'] ? 'bd-lewat' : '' ?>"><?= rpb($s['r']) ?></td>
        <?php endforeach; ?>
        <td class="text-end grp"><?= rpb($t['budget'], false) ?></td>
        <td class="text-end"><?= rpb($t['realisasi'], false) ?></td>
        <td class="text-end <?= $t['sisa'] < 0 ? 'bd-lewat' : '' ?>"><?= rpb($t['sisa'], false) ?></td>
    </tr></tfoot>
</table>
<?php endif; ?>
</div>
</div>
<p class="text-muted small mt-2 mb-0">
    Realisasi dihitung menurut bulan pembebanan<?= $pisahMal ? '; transaksi "Keduanya" dibagi 50:50 ke eWalk dan Pentacity' : '' ?>.
    Klik angka realisasi untuk melihat rincian transaksinya.
</p>
<?php endif; ?>
<?= $this->endSection() ?>
