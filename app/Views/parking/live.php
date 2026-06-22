<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<style>
.pk-kpi { border:0; border-radius:1rem; overflow:hidden; }
.pk-kpi .big { font-size:2rem; font-weight:700; line-height:1.1; }
.pk-live-dot { width:.6rem; height:.6rem; border-radius:50%; background:#22c55e; display:inline-block; animation:pkpulse 1.6s infinite; }
@keyframes pkpulse { 0%{box-shadow:0 0 0 0 rgba(34,197,94,.5)} 70%{box-shadow:0 0 0 .5rem rgba(34,197,94,0)} 100%{box-shadow:0 0 0 0 rgba(34,197,94,0)} }
.pk-chart-sm { position:relative; height:220px; }
.pk-loader { position:fixed; inset:0; z-index:1080; display:flex; align-items:center; justify-content:center;
    background:rgba(15,23,42,.55); backdrop-filter:blur(3px); transition:opacity .4s ease; }
.pk-loader.hide { opacity:0; pointer-events:none; }
.pk-loader-card { background:#1e293b; color:#e2e8f0; border:1px solid rgba(148,163,184,.25);
    border-radius:1rem; padding:1.5rem 1.75rem; width:min(92%,360px); text-align:center; box-shadow:0 10px 40px rgba(0,0,0,.35); }
.pk-spin { width:2.5rem; height:2.5rem; border:3px solid rgba(148,163,184,.3); border-top-color:#22c55e;
    border-radius:50%; margin:0 auto .9rem; animation:pkspin .8s linear infinite; }
@keyframes pkspin { to { transform:rotate(360deg) } }
.pk-prog { height:8px; background:rgba(148,163,184,.25); border-radius:6px; overflow:hidden; margin-top:.85rem; }
.pk-prog-bar { height:100%; width:0; background:linear-gradient(90deg,#16a34a,#22c55e); border-radius:6px; transition:width .5s ease; }
.pk-slot-low { animation:pklow 1.4s ease-in-out infinite; }
@keyframes pklow { 0%,100%{box-shadow:0 0 0 0 rgba(220,38,38,.55)} 50%{box-shadow:0 0 0 .6rem rgba(220,38,38,0)} }
.pk-skel { color:transparent !important; background:linear-gradient(90deg,rgba(148,163,184,.18),rgba(148,163,184,.35),rgba(148,163,184,.18));
    background-size:200% 100%; border-radius:.4rem; animation:pkshim 1.3s infinite; }
@keyframes pkshim { to { background-position:-200% 0 } }
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
        <div class="text-secondary small">Balikpapan Superblock · real-time · <?= date('d M Y') ?> · sumber: SPI (read-only)</div>
    </div>
    <div class="btn-group btn-group-sm" role="group">
        <a href="<?= base_url('parking/live') ?>" class="btn btn-success">Live</a>
        <?php if ($canVeh): ?><a href="<?= base_url('parking/vehicles/summary') ?>" class="btn btn-outline-primary">Kendaraan</a><?php endif; ?>
        <?php if ($canRev): ?><a href="<?= base_url('parking/revenue/summary') ?>" class="btn btn-outline-primary">Revenue</a><?php endif; ?>
        <a href="<?= base_url('parking/compare') ?>" class="btn btn-outline-primary">Compare</a>
    </div>
</div>

<div class="d-flex align-items-center gap-2 text-secondary small mb-3">
    <span class="pk-live-dot"></span> diperbarui otomatis tiap 15 detik <span id="pk-live-time" class="ms-1"></span>
</div>

<div id="pk-live-wrap" style="position:relative">
<div id="pk-loader" class="pk-loader">
    <div class="pk-loader-card">
        <div class="pk-spin"></div>
        <div class="fw-semibold mb-1"><i class="bi bi-broadcast text-success me-1"></i>Mengambil data live</div>
        <div id="pk-loader-status" class="small text-secondary">Menyambung ke server SPI…</div>
        <div class="pk-prog"><div id="pk-prog-bar" class="pk-prog-bar"></div></div>
        <div class="small text-secondary mt-2" style="font-size:.72rem">Data real-time ditarik langsung dari SPI — butuh beberapa detik.</div>
    </div>
</div>

<!-- ALERT slot hampir penuh (sisa <= ambang) -->
<div id="pk-slot-alert" class="alert alert-danger d-none align-items-center gap-2 mb-3" role="alert">
    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
    <span id="pk-slot-alert-msg" class="fw-semibold"></span>
</div>
<!-- OKUPANSI -->
<h6 class="text-secondary text-uppercase small fw-bold mb-2"><i class="bi bi-car-front-fill me-1"></i>Okupansi Kendaraan</h6>
<div class="row g-3 mb-4">
    <div class="col-12 col-lg-4">
        <div class="card pk-kpi h-100" style="background:linear-gradient(135deg,#6366f1,#4f46e5) !important"><div class="card-body d-flex flex-column justify-content-center" style="color:#fff">
            <div class="small" style="color:#fff;opacity:.85"><i class="bi bi-broadcast me-1"></i>Sedang Parkir</div>
            <div class="big" id="live-total" style="color:#fff"><?= number_format($live['total']) ?></div>
            <div class="small mt-1" style="color:#fff;opacity:.85">total kendaraan saat ini</div>
        </div></div>
    </div>
    <div class="col-12 col-sm-6 col-lg-4">
        <div id="card-slot-mobil" class="card pk-kpi h-100" style="background:linear-gradient(135deg,#1d4ed8,#22d3ee) !important"><div class="card-body" style="color:#fff">
            <div class="fw-semibold mb-1" style="color:#fff;opacity:.9"><i class="bi bi-p-square me-1"></i>Slot Mobil Tersedia</div>
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-car-front-fill" style="font-size:1.9rem;color:#fff"></i>
                <span class="big" id="avail-mobil" style="color:#fff"><?= number_format($live['lot_mobil_tersedia']) ?></span>
            </div>
            <div class="small mt-1" style="color:#fff;opacity:.85">Terisi <span id="slotsub-mobil"><?= number_format($live['mobil']) ?> / <?= number_format($capMobil) ?></span></div>
        </div></div>
    </div>
    <div class="col-12 col-sm-6 col-lg-4">
        <div id="card-slot-motor" class="card pk-kpi h-100" style="background:linear-gradient(135deg,#0891b2,#34d399) !important"><div class="card-body" style="color:#fff">
            <div class="fw-semibold mb-1" style="color:#fff;opacity:.9"><i class="bi bi-p-square me-1"></i>Slot Motor Tersedia</div>
            <div class="d-flex align-items-center gap-2">
                <svg viewBox="0 0 24 24" fill="#fff" style="width:1.9rem;height:1.9rem;flex-shrink:0" aria-label="motor"><path d="M19.44 9.03L15.41 5H11v2h3.59l2 2H5c-2.8 0-5 2.2-5 5s2.2 5 5 5c2.46 0 4.45-1.69 4.9-4h1.65l2.77-2.77c-.21.54-.32 1.14-.32 1.77 0 2.8 2.2 5 5 5s5-2.2 5-5c0-2.79-2.2-4.97-4.56-4.97zM7.82 15C7.4 16.15 6.28 17 5 17c-1.63 0-3-1.37-3-3s1.37-3 3-3c1.28 0 2.4.85 2.82 2H5v2h2.82zM19 17c-1.63 0-3-1.37-3-3s1.37-3 3-3 3 1.37 3 3-1.37 3-3 3z"/></svg>
                <span class="big" id="avail-motor" style="color:#fff"><?= number_format($live['lot_motor_tersedia']) ?></span>
            </div>
            <div class="small mt-1" style="color:#fff;opacity:.85">Terisi <span id="slotsub-motor"><?= number_format($live['motor']) ?> / <?= number_format($capMotor) ?></span></div>
        </div></div>
    </div>
</div>

<?php if (! empty($gates['masuk']['motor']) || ! empty($gates['masuk']['mobil'])):
$gbar = function ($list, $color) {
    if (! $list) { echo '<div class="text-secondary small">—</div>'; return; }
    $max = max(array_column($list, 'jumlah')) ?: 1;
    foreach ($list as $g) {
        $w = round($g['jumlah'] / $max * 100);
        echo '<div class="d-flex align-items-center gap-2 mb-1"><div style="width:48px" class="small fw-semibold">' . esc($g['gate']) . '</div>'
            . '<div class="flex-grow-1"><div style="height:13px;border-radius:4px;background:' . $color . ';width:' . $w . '%"></div></div>'
            . '<div class="small text-secondary" style="width:54px;text-align:right">' . number_format($g['jumlah']) . '</div></div>';
    }
};
?>
<h6 class="text-secondary text-uppercase small fw-bold mb-2 mt-1"><i class="bi bi-door-open me-1"></i>Aktivitas Pintu Masuk <span class="fw-normal text-secondary">(kumulatif hari ini · diperbarui ~30 menit)</span></h6>
<div class="row g-3">
    <div class="col-md-6"><div class="card h-100"><div class="card-body">
        <div class="small fw-semibold mb-2" style="color:#34d399"><i class="bi bi-bicycle me-1"></i>Motor — <?= count($gates['masuk']['motor']) ?> pintu</div>
        <?php $gbar($gates['masuk']['motor'], '#34d399'); ?>
    </div></div></div>
    <div class="col-md-6"><div class="card h-100"><div class="card-body">
        <div class="small fw-semibold mb-2" style="color:#22d3ee"><i class="bi bi-car-front me-1"></i>Mobil — <?= count($gates['masuk']['mobil']) ?> pintu</div>
        <?php $gbar($gates['masuk']['mobil'], '#22d3ee'); ?>
    </div></div></div>
</div>
<?php endif; ?>
</div><!-- /pk-live-wrap -->

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
const PK = { url: "<?= base_url('parking/live-data') ?>", canVeh: <?= $canVeh?'true':'false' ?> };
const num = n => Number(n||0).toLocaleString('id-ID');
const $ = id => document.getElementById(id);

// Alert slot hampir penuh: sisa <= ambang (200). Ubah warna kartu + tampilkan banner.
const PK_SLOT_TH = 200;
const PK_GRAD = { mobil:'linear-gradient(135deg,#1d4ed8,#22d3ee)', motor:'linear-gradient(135deg,#0891b2,#34d399)' };
function slotAlert(availMobil, availMotor) {
    const set = (cardId, avail, grad) => {
        const c = $(cardId); if (!c) return;
        const low = avail <= PK_SLOT_TH;
        c.style.setProperty('background', low ? 'linear-gradient(135deg,#dc2626,#f59e0b)' : grad, 'important');
        c.classList.toggle('pk-slot-low', low);
    };
    set('card-slot-mobil', availMobil, PK_GRAD.mobil);
    set('card-slot-motor', availMotor, PK_GRAD.motor);
    const msgs = [];
    if (availMobil <= PK_SLOT_TH) msgs.push('Mobil sisa ' + num(availMobil));
    if (availMotor <= PK_SLOT_TH) msgs.push('Motor sisa ' + num(availMotor));
    const al = $('pk-slot-alert');
    if (!al) return;
    if (msgs.length) {
        $('pk-slot-alert-msg').textContent = 'Slot parkir hampir penuh — ' + msgs.join(' · ') + ' (ambang ' + PK_SLOT_TH + ').';
        al.classList.remove('d-none'); al.classList.add('d-flex');
    } else {
        al.classList.add('d-none'); al.classList.remove('d-flex');
    }
}

// ── Overlay loader: progress bertahap (simulasi) sampai data pertama tiba ──
const PKL = {
    stages: [
        { p:15, t:'Menyambung ke server SPI…' },
        <?php if ($canVeh): ?>{ p:45, t:'Menarik okupansi kendaraan…' },<?php endif; ?>
        <?php if ($canRev): ?>{ p:70, t:'Menghitung income hari ini…' },
        { p:90, t:'Mengambil rincian metode pembayaran…' },<?php endif; ?>
    ],
    i:0, timer:null, done:false,
    tick() {
        if (this.done || this.i >= this.stages.length) return;
        const s = this.stages[this.i++];
        const bar = $('pk-prog-bar'), st = $('pk-loader-status');
        if (bar) bar.style.width = s.p + '%';
        if (st)  st.textContent = s.t;
        this.timer = setTimeout(() => this.tick(), 650);
    },
    start() { this.tick(); },
    finish(ok) {
        clearTimeout(this.timer);
        const bar = $('pk-prog-bar'), st = $('pk-loader-status'), ld = $('pk-loader');
        if (!ok) { // jangan kunci: retry cepat, overlay tetap bisa selesai saat sukses
            if (st) st.innerHTML = '<span class="text-warning">Server SPI lambat merespons. Mencoba lagi…</span>';
            setTimeout(() => refreshLive(), 3000);
            return;
        }
        this.done = true;
        if (bar) bar.style.width = '100%';
        if (st)  st.textContent = 'Selesai';
        setTimeout(() => { if (ld) ld.classList.add('hide'); }, 350);
    }
};

async function refreshLive() {
    try {
        const r = await fetch(PK.url, { headers:{ 'X-Requested-With':'XMLHttpRequest' } });
        if (!r.ok) { if (!PKL.done) PKL.finish(false); return; }
        const d = await r.json();
        if (!d.ok) { if (!PKL.done) PKL.finish(false); return; }
        $('live-total').textContent = num(d.total);
        $('avail-mobil').textContent = num(d.lot_mobil_tersedia);
        $('avail-motor').textContent = num(d.lot_motor_tersedia);
        $('slotsub-mobil').textContent = num(d.mobil) + ' / ' + num(d.lot_mobil);
        $('slotsub-motor').textContent = num(d.motor) + ' / ' + num(d.lot_motor);
        slotAlert(d.lot_mobil_tersedia || 0, d.lot_motor_tersedia || 0);
        $('pk-live-time').textContent = '· ' + new Date().toLocaleTimeString('id-ID');
        if (!PKL.done) PKL.finish(true);
    } catch (e) { if (!PKL.done) PKL.finish(false); }
}
PKL.start();
refreshLive();
setInterval(refreshLive, 15000); // 15 dtk; load2.php fresh (cache 15s), payment dari cache 60s
</script>
<?= $this->endSection() ?>
