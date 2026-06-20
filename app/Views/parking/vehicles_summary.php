<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<style>
.pk-chart { position:relative; height:300px; }
.pk-chart-sm { position:relative; height:240px; }
</style>
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php $fmtDate = fn($d) => date('d M Y', strtotime($d)); ?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h4 class="mb-0"><i class="bi bi-bar-chart-line me-2"></i>Traffic Kendaraan — <span class="text-primary">Summary</span></h4>
        <div class="text-secondary small">Balikpapan Superblock · historis · sumber: SPI (read-only)</div>
    </div>
    <div class="btn-group btn-group-sm" role="group">
        <a href="<?= base_url('parking/vehicles/live') ?>" class="btn btn-outline-primary">Live</a>
        <a href="<?= base_url('parking/vehicles/summary') ?>" class="btn btn-primary">Summary</a>
    </div>
</div>

<form class="row g-2 align-items-end mb-3" method="get">
    <div class="col-auto">
        <label class="form-label small mb-0">Dari</label>
        <input type="date" name="start" value="<?= esc($start) ?>" min="2023-01-01" class="form-control form-control-sm">
    </div>
    <div class="col-auto">
        <label class="form-label small mb-0">Sampai</label>
        <input type="date" name="end" value="<?= esc($end) ?>" min="2023-01-01" class="form-control form-control-sm">
    </div>
    <div class="col-auto">
        <button class="btn btn-primary btn-sm"><i class="bi bi-funnel"></i> Terapkan</button>
    </div>
    <div class="col-auto text-secondary small">Data tersedia sejak Jan 2023</div>
</form>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <h6 class="card-title mb-0">Jumlah Kendaraan per Hari</h6>
                    <span class="text-secondary small"><?= $fmtDate($start) ?> – <?= $fmtDate($end) ?></span>
                </div>
                <div class="pk-chart mt-2"><canvas id="chartDaily"></canvas></div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <h6 class="card-title">Komposisi Jenis</h6>
                <div class="text-secondary small mb-2">Total periode: <strong><?= number_format($grandTotal) ?></strong> kendaraan</div>
                <div class="pk-chart-sm"><canvas id="chartType"></canvas></div>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-body">
                <?php
                $paidTot = array_sum($paid); $freeTot = array_sum($free); $pfTot = max(1, $paidTot + $freeTot);
                ?>
                <h6 class="card-title">Bayar vs Langganan <span class="text-secondary small fw-normal">(per jenis)</span></h6>
                <div class="d-flex gap-3 mb-2 small">
                    <span><span class="badge" style="background:#16a34a">&nbsp;</span> Bayar (Casual): <strong><?= number_format($paidTot) ?></strong> (<?= round($paidTot/$pfTot*100) ?>%)</span>
                    <span><span class="badge" style="background:#64748b">&nbsp;</span> Langganan (Member): <strong><?= number_format($freeTot) ?></strong> (<?= round($freeTot/$pfTot*100) ?>%)</span>
                </div>
                <div class="pk-chart-sm"><canvas id="chartPaidFree"></canvas></div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-body">
                <h6 class="card-title">Distribusi Lama Parkir</h6>
                <div class="pk-chart-sm"><canvas id="chartDuration"></canvas></div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
const PK = { daily: <?= json_encode($daily) ?>, byType: <?= json_encode($byType) ?>, duration: <?= json_encode($duration) ?>,
    paid: <?= json_encode($paid) ?>, free: <?= json_encode($free) ?> };
const PK_COLORS = { mobil:'#0ea5e9', motor:'#f59e0b', box:'#10b981', truck:'#8b5cf6', taxi:'#ec4899', bus:'#ef4444' };
const PK_TL = { mobil:'Mobil', motor:'Motor', box:'Box', truck:'Truck', taxi:'Taxi', bus:'Bus' };

(function () {
    const types = ['mobil','motor','box','truck','taxi','bus'];
    const labels = PK.daily.map(d => { const p=(d.tanggal||'').split('-'); return p.length===3 ? p[2]+'/'+p[1] : d.tanggal; });

    new Chart(document.getElementById('chartDaily'), {
        type:'bar',
        data:{ labels, datasets: types.map(t => ({ label:PK_TL[t], data:PK.daily.map(d=>d[t]), backgroundColor:PK_COLORS[t], stack:'v', borderWidth:0 })) },
        options:{ responsive:true, maintainAspectRatio:false, scales:{ x:{ stacked:true }, y:{ stacked:true, beginAtZero:true } }, plugins:{ legend:{ position:'bottom' } } }
    });
    new Chart(document.getElementById('chartType'), {
        type:'doughnut',
        data:{ labels: types.map(t=>PK_TL[t]), datasets:[{ data: types.map(t=>PK.byType[t]), backgroundColor: types.map(t=>PK_COLORS[t]), borderWidth:0 }] },
        options:{ responsive:true, maintainAspectRatio:false, cutout:'62%', plugins:{ legend:{ position:'right' } } }
    });
    const dL = ['≤1 jam','1–2 jam','2–3 jam','3–4 jam','4–5 jam','5–6 jam','6–7 jam','>7 jam'];
    const dK = ['le1','h1_2','h2_3','h3_4','h4_5','h5_6','h6_7','gt7'];
    new Chart(document.getElementById('chartDuration'), {
        type:'bar',
        data:{ labels:dL, datasets:[{ label:'Kendaraan', data:dK.map(k=>PK.duration[k]||0), backgroundColor:'#6366f1', borderWidth:0 }] },
        options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{ display:false } }, scales:{ y:{ beginAtZero:true } } }
    });
    // Bayar (Casual) vs Langganan (Pass) per jenis
    new Chart(document.getElementById('chartPaidFree'), {
        type:'bar',
        data:{ labels: types.map(t=>PK_TL[t]), datasets:[
            { label:'Bayar (Casual)', data: types.map(t=>PK.paid[t]||0), backgroundColor:'#16a34a', stack:'pf', borderWidth:0 },
            { label:'Langganan (Member)', data: types.map(t=>PK.free[t]||0), backgroundColor:'#64748b', stack:'pf', borderWidth:0 },
        ] },
        options:{ responsive:true, maintainAspectRatio:false, scales:{ x:{ stacked:true }, y:{ stacked:true, beginAtZero:true } }, plugins:{ legend:{ position:'bottom' } } }
    });
})();
</script>
<?= $this->endSection() ?>
