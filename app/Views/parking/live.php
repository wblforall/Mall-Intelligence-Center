<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<style>
.pk-kpi { border:0; border-radius:1rem; overflow:hidden; }
.pk-kpi .big { font-size:2rem; font-weight:700; line-height:1.1; }
.pk-live-dot { width:.6rem; height:.6rem; border-radius:50%; background:#22c55e; display:inline-block; animation:pkpulse 1.6s infinite; }
@keyframes pkpulse { 0%{box-shadow:0 0 0 0 rgba(34,197,94,.5)} 70%{box-shadow:0 0 0 .5rem rgba(34,197,94,0)} 100%{box-shadow:0 0 0 0 rgba(34,197,94,0)} }
.pk-chart-sm { position:relative; height:220px; }
</style>
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php
$rp = fn($n) => 'Rp ' . number_format((float)$n, 0, ',', '.');
$capMobil = max(1, (int)$live['lot_mobil']);
$capMotor = max(1, (int)$live['lot_motor']);
$occMobil = min(100, round(($live['mobil'] / $capMobil) * 100));
$occMotor = min(100, round(($live['motor'] / $capMotor) * 100));
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
    <div>
        <h4 class="mb-0"><i class="bi bi-broadcast me-2"></i>Parkir — <span class="text-success">Live</span></h4>
        <div class="text-secondary small">Balikpapan Superblock · real-time · sumber: SPI (read-only)</div>
    </div>
    <div class="btn-group btn-group-sm" role="group">
        <a href="<?= base_url('parking/live') ?>" class="btn btn-success">Live</a>
        <?php if ($canVeh): ?><a href="<?= base_url('parking/vehicles/summary') ?>" class="btn btn-outline-primary">Kendaraan</a><?php endif; ?>
        <?php if ($canRev): ?><a href="<?= base_url('parking/revenue/summary') ?>" class="btn btn-outline-primary">Revenue</a><?php endif; ?>
        <a href="<?= base_url('parking/compare') ?>" class="btn btn-outline-primary">Compare</a>
    </div>
</div>

<div class="d-flex align-items-center gap-2 text-secondary small mb-3">
    <span class="pk-live-dot"></span> diperbarui otomatis tiap 30 detik <span id="pk-live-time" class="ms-1"></span>
</div>

<?php if ($canVeh): ?>
<!-- OKUPANSI -->
<h6 class="text-secondary text-uppercase small fw-bold mb-2"><i class="bi bi-car-front-fill me-1"></i>Okupansi Kendaraan</h6>
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card pk-kpi bg-primary text-white h-100"><div class="card-body">
            <div class="small opacity-75">Sedang Parkir</div>
            <div class="big" id="live-total"><?= number_format($live['total']) ?></div>
            <div class="small opacity-75 mt-1">kendaraan saat ini</div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card pk-kpi h-100"><div class="card-body">
            <div class="small text-secondary"><i class="bi bi-car-front text-info"></i> Mobil</div>
            <div class="big" id="live-mobil"><?= number_format($live['mobil']) ?></div>
            <div class="progress mt-2" style="height:7px"><div id="bar-mobil" class="progress-bar bg-info" style="width:<?= $occMobil ?>%"></div></div>
            <div class="small text-secondary mt-1"><span id="occ-mobil"><?= $occMobil ?></span>% dari <?= number_format($capMobil) ?></div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card pk-kpi h-100"><div class="card-body">
            <div class="small text-secondary"><i class="bi bi-bicycle text-warning"></i> Motor</div>
            <div class="big" id="live-motor"><?= number_format($live['motor']) ?></div>
            <div class="progress mt-2" style="height:7px"><div id="bar-motor" class="progress-bar bg-warning" style="width:<?= $occMotor ?>%"></div></div>
            <div class="small text-secondary mt-1"><span id="occ-motor"><?= $occMotor ?></span>% dari <?= number_format($capMotor) ?></div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card pk-kpi h-100"><div class="card-body">
            <div class="small text-secondary mb-1"><i class="bi bi-p-square text-success"></i> Slot Tersedia</div>
            <div class="d-flex justify-content-between align-items-end">
                <div>
                    <div class="small text-secondary"><i class="bi bi-car-front text-info"></i> Mobil</div>
                    <div style="font-size:1.5rem;font-weight:700" class="text-info" id="avail-mobil"><?= number_format($live['lot_mobil_tersedia']) ?></div>
                </div>
                <div class="text-end">
                    <div class="small text-secondary"><i class="bi bi-bicycle text-warning"></i> Motor</div>
                    <div style="font-size:1.5rem;font-weight:700" class="text-warning" id="avail-motor"><?= number_format($live['lot_motor_tersedia']) ?></div>
                </div>
            </div>
            <div class="small text-secondary mt-1 text-center">Total <span id="live-avail" class="fw-semibold text-success"><?= number_format($live['lot_mobil_tersedia'] + $live['lot_motor_tersedia']) ?></span> slot</div>
        </div></div>
    </div>
</div>
<?php endif; ?>

<?php if ($canRev): ?>
<!-- REVENUE -->
<h6 class="text-secondary text-uppercase small fw-bold mb-2"><i class="bi bi-cash-coin me-1"></i>Income Hari Ini <span class="fw-normal text-secondary">(estimasi berjalan)</span></h6>
<div class="row g-3">
    <div class="col-12 col-lg-4">
        <div class="card pk-kpi text-white h-100" style="background:linear-gradient(135deg,#16a34a,#15803d)"><div class="card-body d-flex flex-column justify-content-center">
            <div class="small opacity-75">Total Income</div>
            <div class="big" id="live-income" style="font-size:2.4rem"><?= $rp($live['totalincome']) ?></div>
            <div class="small opacity-75 mt-1">Tunai <span id="live-tunai"><?= $rp($live['tunai']) ?></span> · Non-Tunai <span id="live-nontunai"><?= $rp($live['nontunai']) ?></span></div>
        </div></div>
    </div>
    <div class="col-12 col-lg-4">
        <div class="card h-100"><div class="card-body">
            <h6 class="card-title">Komposisi Pembayaran</h6>
            <div class="pk-chart-sm"><canvas id="chartPay"></canvas></div>
        </div></div>
    </div>
    <div class="col-12 col-lg-4">
        <div class="card h-100"><div class="card-body">
            <h6 class="card-title">Rincian Metode <span class="text-secondary small fw-normal">(hari ini)</span></h6>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <tbody id="pay-rows">
                        <?php $totPay = array_sum(array_column($payments, 'amount'));
                        foreach ($payments as $p): $pct = $totPay>0?round($p['amount']/$totPay*100):0; ?>
                        <tr><td><?= esc($p['method']) ?></td><td class="text-end"><?= $rp($p['amount']) ?></td>
                            <td class="text-end" style="width:30%"><div class="progress" style="height:7px"><div class="progress-bar" style="width:<?= $pct ?>%"></div></div></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot><tr class="fw-bold border-top"><td>Total</td><td class="text-end" id="pay-total"><?= $rp($totPay) ?></td><td></td></tr></tfoot>
                </table>
            </div>
        </div></div>
    </div>
</div>
<?php endif; ?>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
const PK = { url: "<?= base_url('parking/live-data') ?>", canVeh: <?= $canVeh?'true':'false' ?>, canRev: <?= $canRev?'true':'false' ?>,
    tunai: <?= (int)$live['tunai'] ?>, nontunai: <?= (int)$live['nontunai'] ?> };
const num = n => Number(n||0).toLocaleString('id-ID');
const rp  = n => 'Rp ' + Number(n||0).toLocaleString('id-ID');

let chartPay = null;
if (PK.canRev) {
    chartPay = new Chart(document.getElementById('chartPay'), {
        type:'doughnut',
        data:{ labels:['Tunai','Non-Tunai'], datasets:[{ data:[PK.tunai, PK.nontunai], backgroundColor:['#0ea5e9','#16a34a'], borderWidth:0 }] },
        options:{ responsive:true, maintainAspectRatio:false, cutout:'60%', plugins:{ legend:{ position:'bottom' },
            tooltip:{ callbacks:{ label: c => c.label+': Rp '+Number(c.parsed).toLocaleString('id-ID') } } } }
    });
}
const $ = id => document.getElementById(id);
async function refreshLive() {
    try {
        const r = await fetch(PK.url, { headers:{ 'X-Requested-With':'XMLHttpRequest' } });
        if (!r.ok) return; const d = await r.json(); if (!d.ok) return;
        if (PK.canVeh) {
            $('live-total').textContent = num(d.total);
            $('live-mobil').textContent = num(d.mobil);
            $('live-motor').textContent = num(d.motor);
            $('live-avail').textContent = num((d.lot_mobil_tersedia||0)+(d.lot_motor_tersedia||0));
            $('avail-mobil').textContent = num(d.lot_mobil_tersedia);
            $('avail-motor').textContent = num(d.lot_motor_tersedia);
            const om = Math.min(100, Math.round((d.mobil/Math.max(1,d.lot_mobil))*100));
            const ot = Math.min(100, Math.round((d.motor/Math.max(1,d.lot_motor))*100));
            $('occ-mobil').textContent = om; $('occ-motor').textContent = ot;
            $('bar-mobil').style.width = om+'%'; $('bar-motor').style.width = ot+'%';
        }
        if (PK.canRev) {
            $('live-income').textContent = rp(d.totalincome);
            $('live-tunai').textContent = rp(d.tunai);
            $('live-nontunai').textContent = rp(d.nontunai);
            if (chartPay) { chartPay.data.datasets[0].data = [d.tunai, d.nontunai]; chartPay.update(); }
            if (Array.isArray(d.payments) && d.payments.length) {
                const tot = d.payments.reduce((s,p)=>s+(p.amount||0),0) || 1;
                $('pay-rows').innerHTML = d.payments.map(p => {
                    const pct = Math.round((p.amount||0)/tot*100);
                    return `<tr><td>${p.method}</td><td class="text-end">${rp(p.amount)}</td><td class="text-end" style="width:30%"><div class="progress" style="height:7px"><div class="progress-bar" style="width:${pct}%"></div></div></td></tr>`;
                }).join('');
                $('pay-total').textContent = rp(d.payments.reduce((s,p)=>s+(p.amount||0),0));
            }
        }
        $('pk-live-time').textContent = '· ' + new Date().toLocaleTimeString('id-ID');
    } catch (e) {}
}
refreshLive();
setInterval(refreshLive, 30000);
</script>
<?= $this->endSection() ?>
