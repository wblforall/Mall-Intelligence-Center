<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<style>
.pk-chart-sm { position:relative; height:260px; }
.delta-up { color:#16a34a; } .delta-down { color:#ef4444; }
.cmp-num { font-size:1.5rem; font-weight:700; }
</style>
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php
$rp  = fn($n) => 'Rp ' . number_format((float)$n, 0, ',', '.');
$num = fn($n) => number_format((float)$n);
// delta KINI vs LALU
$delta = function ($cur, $prev) {
    if ($prev == 0) return ['pct' => null, 'cls' => '', 'arrow' => ''];
    $d = ($cur - $prev) / $prev * 100;
    return ['pct' => $d, 'cls' => $d >= 0 ? 'delta-up' : 'delta-down', 'arrow' => $d >= 0 ? '▲' : '▼'];
};
$types = ['mobil', 'motor', 'box', 'truck', 'taxi', 'bus'];
$TL = ['mobil'=>'Mobil','motor'=>'Motor','box'=>'Box','truck'=>'Truck','taxi'=>'Taxi','bus'=>'Bus'];
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h4 class="mb-0"><i class="bi bi-arrow-left-right me-2"></i>Compare Periode — Parkir</h4>
        <div class="text-secondary small">Balikpapan Superblock · <?= esc($prev['label']) ?> <i class="bi bi-arrow-right"></i> <?= esc($cur['label']) ?></div>
    </div>
    <div class="btn-group btn-group-sm" role="group">
        <a href="?mode=mom" class="btn btn-<?= $mode==='mom'?'primary':'outline-primary' ?>">Bulan lalu vs ini</a>
        <a href="?mode=yoy" class="btn btn-<?= $mode==='yoy'?'primary':'outline-primary' ?>">YoY (vs tahun lalu)</a>
        <a href="?mode=custom" class="btn btn-<?= $mode==='custom'?'primary':'outline-primary' ?>">Custom</a>
    </div>
</div>

<?php if ($mode === 'custom'): ?>
<form class="card card-body mb-3" method="get">
    <input type="hidden" name="mode" value="custom">
    <div class="row g-2 align-items-end">
        <div class="col-12 col-md-auto fw-semibold text-secondary small">Periode Lalu</div>
        <div class="col-6 col-md-2"><label class="form-label small mb-0">Dari</label>
            <input type="date" name="start1" value="<?= esc($prev['start']) ?>" min="2023-01-01" class="form-control form-control-sm"></div>
        <div class="col-6 col-md-2"><label class="form-label small mb-0">Sampai</label>
            <input type="date" name="end1" value="<?= esc($prev['end']) ?>" min="2023-01-01" class="form-control form-control-sm"></div>
        <div class="col-12 col-md-auto fw-semibold text-secondary small">Periode Kini</div>
        <div class="col-6 col-md-2"><label class="form-label small mb-0">Dari</label>
            <input type="date" name="start2" value="<?= esc($cur['start']) ?>" min="2023-01-01" class="form-control form-control-sm"></div>
        <div class="col-6 col-md-2"><label class="form-label small mb-0">Sampai</label>
            <input type="date" name="end2" value="<?= esc($cur['end']) ?>" min="2023-01-01" class="form-control form-control-sm"></div>
        <div class="col-12 col-md-auto"><button class="btn btn-primary btn-sm w-100"><i class="bi bi-funnel"></i> Banding</button></div>
    </div>
</form>
<?php else: ?>
<div class="text-secondary small mb-3">
    Membandingkan <strong><?= date('d M', strtotime($prev['start'])) ?>–<?= date('d M Y', strtotime($prev['end'])) ?></strong>
    (lalu) <i class="bi bi-arrow-right"></i> <strong><?= date('d M', strtotime($cur['start'])) ?>–<?= date('d M Y', strtotime($cur['end'])) ?></strong>
    (kini) · periode berjalan bisa parsial (data final ±H-3).
</div>
<?php endif; ?>

<?php if ($canVeh): $d = $delta($cur['traffic']['total'], $prev['traffic']['total']); ?>
<!-- TRAFFIC -->
<div class="card mb-3">
    <div class="card-body">
        <h6 class="card-title"><i class="bi bi-car-front-fill me-1"></i>Traffic Kendaraan</h6>
        <div class="row g-3 align-items-center">
            <div class="col-6 col-md-3">
                <div class="text-secondary small"><?= esc($prev['label']) ?> <span class="badge bg-secondary">lalu</span></div>
                <div class="cmp-num text-secondary"><?= $num($prev['traffic']['total']) ?></div>
            </div>
            <div class="col-6 col-md-3">
                <div class="text-secondary small"><?= esc($cur['label']) ?> <span class="badge bg-primary">kini</span></div>
                <div class="cmp-num"><?= $num($cur['traffic']['total']) ?></div>
            </div>
            <div class="col-12 col-md-3">
                <div class="text-secondary small">Selisih (kini vs lalu)</div>
                <div class="cmp-num <?= $d['cls'] ?>"><?= $d['pct']===null?'—':$d['arrow'].' '.number_format(abs($d['pct']),1).'%' ?></div>
            </div>
            <div class="col-12 col-md-3"><div class="pk-chart-sm" style="height:120px"><canvas id="cmpTrafficType"></canvas></div></div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($canRev): $d = $delta($cur['revenue']['total'], $prev['revenue']['total']); ?>
<!-- REVENUE -->
<div class="card mb-3">
    <div class="card-body">
        <h6 class="card-title"><i class="bi bi-cash-stack me-1"></i>Revenue Parkir</h6>
        <div class="row g-3 align-items-center">
            <div class="col-6 col-md-3">
                <div class="text-secondary small"><?= esc($prev['label']) ?> <span class="badge bg-secondary">lalu</span></div>
                <div class="cmp-num text-secondary"><?= $rp($prev['revenue']['total']) ?></div>
            </div>
            <div class="col-6 col-md-3">
                <div class="text-secondary small"><?= esc($cur['label']) ?> <span class="badge bg-primary">kini</span></div>
                <div class="cmp-num"><?= $rp($cur['revenue']['total']) ?></div>
            </div>
            <div class="col-12 col-md-3">
                <div class="text-secondary small">Selisih (kini vs lalu)</div>
                <div class="cmp-num <?= $d['cls'] ?>"><?= $d['pct']===null?'—':$d['arrow'].' '.number_format(abs($d['pct']),1).'%' ?></div>
            </div>
            <div class="col-12 col-md-3"><div class="pk-chart-sm" style="height:120px"><canvas id="cmpRevenueType"></canvas></div></div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Tabel per jenis -->
<div class="card">
    <div class="card-body">
        <h6 class="card-title">Rincian per Jenis</h6>
        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead><tr>
                    <th>Jenis</th>
                    <?php if ($canVeh): ?><th class="text-end"><?= esc($prev['label']) ?></th><th class="text-end"><?= esc($cur['label']) ?></th><th class="text-end">Δ Traffic</th><?php endif; ?>
                    <?php if ($canRev): ?><th class="text-end"><?= esc($prev['label']) ?> (Rp)</th><th class="text-end"><?= esc($cur['label']) ?> (Rp)</th><th class="text-end">Δ Revenue</th><?php endif; ?>
                </tr></thead>
                <tbody>
                <?php foreach ($types as $t):
                    $dvt = $canVeh ? $delta($cur['traffic']['byType'][$t], $prev['traffic']['byType'][$t]) : null;
                    $drv = $canRev ? $delta($cur['revenue']['byType'][$t], $prev['revenue']['byType'][$t]) : null; ?>
                    <tr>
                        <td><?= $TL[$t] ?></td>
                        <?php if ($canVeh): ?>
                        <td class="text-end text-secondary"><?= $num($prev['traffic']['byType'][$t]) ?></td>
                        <td class="text-end"><?= $num($cur['traffic']['byType'][$t]) ?></td>
                        <td class="text-end <?= $dvt['cls'] ?>"><?= $dvt['pct']===null?'—':$dvt['arrow'].number_format(abs($dvt['pct']),1).'%' ?></td>
                        <?php endif; ?>
                        <?php if ($canRev): ?>
                        <td class="text-end text-secondary"><?= $rp($prev['revenue']['byType'][$t]) ?></td>
                        <td class="text-end"><?= $rp($cur['revenue']['byType'][$t]) ?></td>
                        <td class="text-end <?= $drv['cls'] ?>"><?= $drv['pct']===null?'—':$drv['arrow'].number_format(abs($drv['pct']),1).'%' ?></td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
const TYPES = <?= json_encode($types) ?>;
const TL = <?= json_encode($TL) ?>;
const PREV = <?= json_encode($prev) ?>, CUR = <?= json_encode($cur) ?>;
const labelPrev = <?= json_encode($prev['label']) ?>, labelCur = <?= json_encode($cur['label']) ?>;

function grouped(canvasId, prevData, curData, money) {
    const el = document.getElementById(canvasId); if (!el) return;
    new Chart(el, {
        type:'bar',
        data:{ labels: TYPES.map(t=>TL[t]), datasets:[
            { label: labelPrev, data: TYPES.map(t=>prevData[t]||0), backgroundColor:'#cbd5e1', borderWidth:0 },
            { label: labelCur,  data: TYPES.map(t=>curData[t]||0),  backgroundColor:'#6366f1', borderWidth:0 },
        ] },
        options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{ display:false },
            tooltip:{ callbacks:{ label: c => c.dataset.label+': '+(money?'Rp ':'')+Number(c.parsed.y).toLocaleString('id-ID') } } },
            scales:{ x:{ ticks:{ font:{ size:9 } } }, y:{ beginAtZero:true, display:false } } }
    });
}
<?php if ($canVeh): ?>grouped('cmpTrafficType', PREV.traffic.byType, CUR.traffic.byType, false);<?php endif; ?>
<?php if ($canRev): ?>grouped('cmpRevenueType', PREV.revenue.byType, CUR.revenue.byType, true);<?php endif; ?>
</script>
<?= $this->endSection() ?>
