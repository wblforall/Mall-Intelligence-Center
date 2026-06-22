<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<style>
.pk-chart { position:relative; height:320px; }
.hm-table { border-collapse:separate; border-spacing:2px; font-size:.75rem; }
.hm-table td, .hm-table th { text-align:center; }
.hm-cell { width:34px; height:22px; border-radius:3px; color:#fff; font-size:.7rem; line-height:22px; overflow:hidden; }
.hm-lbl { color:var(--txt-muted,#94a3b8); padding:0 .35rem; white-space:nowrap; }
</style>
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php
$rp  = fn($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
$num = fn($n) => number_format((float) $n);
$fmtDate = fn($d) => $d ? date('d M Y', strtotime($d)) : '—';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h4 class="mb-0"><i class="bi bi-activity me-2"></i>Okupansi Intraday — <span class="text-info">Parkir</span></h4>
        <div class="text-secondary small">Balikpapan Superblock · rekaman snapshot live (tiap ~15 menit)</div>
    </div>
    <a href="<?= base_url('parking/live') ?>" class="btn btn-outline-success btn-sm"><i class="bi bi-broadcast"></i> Live</a>
</div>

<?php if (! empty($empty)): ?>
<div class="card"><div class="card-body text-center py-5">
    <div style="font-size:2.4rem"><i class="bi bi-hourglass-split text-secondary"></i></div>
    <h6 class="mt-2">Belum ada rekaman snapshot</h6>
    <p class="text-secondary small mb-0">Perekam berjalan via cron <code>mic:spi-snapshot</code> (tiap 15 menit).
    Grafik kepadatan per jam akan muncul setelah data terkumpul. Jalankan manual untuk uji:
    <code>php spark mic:spi-snapshot</code>.</p>
</div></div>
<?php else: ?>

<!-- Filter tanggal -->
<form class="row g-2 align-items-end mb-3" method="get">
    <div class="col-auto">
        <label class="form-label small mb-0">Tanggal</label>
        <select name="date" class="form-select form-select-sm" onchange="this.form.submit()">
            <?php foreach ($days as $d): ?>
            <option value="<?= esc($d['tanggal']) ?>" <?= $d['tanggal'] === $date ? 'selected' : '' ?>>
                <?= $fmtDate($d['tanggal']) ?> · <?= (int) $d['n'] ?> rekaman · puncak <?= $num($d['peak']) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto text-secondary small"><?= count($days) ?> hari terekam</div>
</form>

<!-- KPI -->
<div class="row g-3 mb-1">
    <div class="col-6 col-lg-3">
        <div class="card pk-kpi h-100" style="background:linear-gradient(135deg,#0891b2,#1d4ed8) !important"><div class="card-body" style="color:#fff">
            <div class="small" style="color:#fff;opacity:.85">Puncak Okupansi</div>
            <div style="font-size:1.5rem;font-weight:700;color:#fff"><?= $num($peak['total']) ?></div>
            <div class="small" style="color:#fff;opacity:.85">pukul <?= $peak['t'] ?? '—' ?></div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card pk-kpi h-100"><div class="card-body">
            <div class="small text-secondary">Rekaman Hari Ini</div>
            <div style="font-size:1.5rem;font-weight:700"><?= count($points) ?></div>
            <div class="small text-secondary">titik snapshot</div>
        </div></div>
    </div>
    <?php if ($canRev): ?>
    <div class="col-6 col-lg-3">
        <div class="card pk-kpi h-100"><div class="card-body">
            <div class="small text-secondary">Income (rekaman EOD)</div>
            <div style="font-size:1.25rem;font-weight:700"><?= $rp($peakInc ?? 0) ?></div>
            <div class="small text-secondary">kumulatif s/d terakhir</div>
        </div></div>
    </div>
    <?php endif; ?>
    <div class="col-6 col-lg-3">
        <div class="card pk-kpi h-100"><div class="card-body">
            <div class="small text-secondary">Tanggal</div>
            <div style="font-size:1.15rem;font-weight:700"><?= $fmtDate($date) ?></div>
            <div class="small text-secondary"><?= $points ? $points[0]['t'] . '–' . end($points)['t'] : '—' ?></div>
        </div></div>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 <?= $canRev ? 'col-lg-7' : '' ?>">
        <div class="card h-100"><div class="card-body">
            <h6 class="card-title">Kepadatan per Jam <span class="text-secondary small fw-normal">(kendaraan di dalam)</span></h6>
            <div class="pk-chart mt-2"><canvas id="chartIntra"></canvas></div>
        </div></div>
    </div>
    <?php if ($canRev): ?>
    <div class="col-12 col-lg-5">
        <div class="card h-100"><div class="card-body">
            <h6 class="card-title">Income Berjalan <span class="text-secondary small fw-normal">(kumulatif hari ini)</span></h6>
            <div class="pk-chart mt-2"><canvas id="chartIncome"></canvas></div>
        </div></div>
    </div>
    <?php endif; ?>
</div>

<!-- Arus masuk vs keluar per jam (tanggal terpilih) -->
<div class="card mt-3"><div class="card-body">
    <h6 class="card-title">Arus Masuk &amp; Keluar per Jam <span class="text-secondary small fw-normal">(<?= $fmtDate($date) ?> · semua jenis kendaraan)</span></h6>
    <?php if ($flowDay): ?>
    <div class="pk-chart mt-2" style="height:280px"><canvas id="chartFlow"></canvas></div>
    <div class="text-secondary small mt-2"><i class="bi bi-info-circle"></i> <b>Keluar = estimasi</b> (masuk − perubahan okupansi), bukan counter mentah SPI yang dobel-hitung lintasan gerbang. Terisi pada jam yang punya rekaman okupansi.</div>
    <?php else: ?>
    <div class="text-secondary small"><i class="bi bi-info-circle"></i> Arus per jam belum terekam untuk tanggal ini (mulai terisi setelah perekam menyertakan data dashboard SPI).</div>
    <?php endif; ?>
</div></div>

<?php
$dows = [2 => 'Sen', 3 => 'Sel', 4 => 'Rab', 5 => 'Kam', 6 => 'Jum', 7 => 'Sab', 1 => 'Min'];
$renderHeat = function (array $data, string $rgb) use ($dows, $num) {
    $max = 1; foreach ($data as $hrs) { foreach ($hrs as $v) { if ($v > $max) { $max = $v; } } }
    echo '<div class="table-responsive"><table class="hm-table mb-0"><thead><tr><th></th>';
    for ($h = 0; $h < 24; $h++) { echo '<th class="hm-lbl">' . $h . '</th>'; }
    echo '</tr></thead><tbody>';
    foreach ($dows as $dw => $lbl) {
        echo '<tr><td class="hm-lbl text-end fw-semibold">' . $lbl . '</td>';
        for ($h = 0; $h < 24; $h++) {
            $v = $data[$dw][$h] ?? null;
            if ($v === null) { echo '<td><div class="hm-cell" style="background:rgba(148,163,184,.12)"></div></td>'; }
            else { $a = 0.12 + 0.88 * ($v / $max); echo '<td><div class="hm-cell" style="background:rgba(' . $rgb . ',' . round($a, 2) . ')" title="' . $lbl . ' ' . $h . ':00 — ' . $num($v) . '">' . ($v >= $max * 0.6 ? $num($v) : '') . '</div></td>'; }
        }
        echo '</tr>';
    }
    echo '</tbody></table></div>';
};
?>
<!-- Heatmap -->
<div class="card mt-3"><div class="card-body">
    <h6 class="card-title mb-3">Heatmap <span class="text-secondary small fw-normal">(rata-rata · hari × jam, semua rekaman)</span></h6>
    <div class="row g-4">
        <div class="col-xl-6">
            <div class="small fw-semibold mb-1" style="color:#fbbf24"><i class="bi bi-bicycle me-1"></i>Motor</div>
            <div id="heat-motor"></div>
        </div>
        <div class="col-xl-6">
            <div class="small fw-semibold mb-1" style="color:#60a5fa"><i class="bi bi-car-front me-1"></i>Mobil</div>
            <div id="heat-mobil"></div>
        </div>
        <div class="col-xl-6">
            <div class="small fw-semibold mb-1" style="color:#4ade80"><i class="bi bi-box-arrow-in-right me-1"></i>Arus Masuk</div>
            <div id="heat-in"></div>
        </div>
        <div class="col-xl-6">
            <div class="small fw-semibold mb-1" style="color:#22d3ee"><i class="bi bi-grid me-1"></i>Total Okupansi</div>
            <?php $renderHeat($heat, '14,165,233'); ?>
        </div>
    </div>
    <div class="text-secondary small mt-3"><b>Okupansi</b> = kepadatan (kendaraan di dalam). <b>Arus Masuk</b> = kendaraan masuk per jam. Makin pekat makin ramai.</div>
</div></div>

<!-- Per pintu (gate) -->
<?php
$gateBar = function (array $list, string $color) use ($num) {
    if (! $list) { echo '<div class="text-secondary small">Belum ada data pintu untuk tanggal ini.</div>'; return; }
    $max = max(array_column($list, 'jumlah')) ?: 1;
    foreach ($list as $g) {
        $w = round($g['jumlah'] / $max * 100);
        echo '<div class="d-flex align-items-center gap-2 mb-1">'
            . '<div style="width:48px" class="small fw-semibold">' . esc($g['gate']) . '</div>'
            . '<div class="flex-grow-1"><div style="height:14px;border-radius:4px;background:' . $color . ';width:' . $w . '%"></div></div>'
            . '<div class="small text-secondary" style="width:56px;text-align:right">' . $num($g['jumlah']) . '</div></div>';
    }
};
?>
<div class="card mt-3"><div class="card-body">
    <h6 class="card-title">Per Pintu Masuk (Gate) <span class="text-secondary small fw-normal">(<?= $fmtDate($date) ?> · kendaraan)</span></h6>
    <div class="row g-4">
        <div class="col-md-6">
            <div class="small fw-semibold mb-2" style="color:#34d399"><i class="bi bi-bicycle me-1"></i>Motor — <?= count($gates['masuk']['motor']) ?> pintu</div>
            <?php $gateBar($gates['masuk']['motor'], '#34d399'); ?>
        </div>
        <div class="col-md-6">
            <div class="small fw-semibold mb-2" style="color:#22d3ee"><i class="bi bi-car-front me-1"></i>Mobil — <?= count($gates['masuk']['mobil']) ?> pintu</div>
            <?php $gateBar($gates['masuk']['mobil'], '#22d3ee'); ?>
        </div>
    </div>
    <div class="text-secondary small mt-2"><i class="bi bi-info-circle"></i> Pintu keluar tak ditampilkan — counter keluar per-gerbang SPI tidak reliable (dobel-scan) &amp; tak bisa diestimasi per-pintu.</div>
</div></div>

<?php if ($canRev): ?>
<!-- Rekonsiliasi -->
<div class="card mt-3"><div class="card-body">
    <h6 class="card-title">Rekonsiliasi vs SPI Final <span class="text-secondary small fw-normal">(rekaman EOD vs data final SPI)</span></h6>
    <div class="text-secondary small mb-2"><i class="bi bi-info-circle"></i> Income rekaman = total berjalan (kotor) hari itu; SPI final masuk ±H-3. Beda basis → selisih wajar, dipakai untuk deteksi anomali.</div>
    <?php if ($recon): ?>
    <div class="table-responsive"><table class="table table-sm align-middle mb-0">
        <thead><tr><th>Tanggal</th><th class="text-end">Rekaman (EOD)</th><th class="text-end">SPI Final</th><th class="text-end">Selisih</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($recon as $r):
            $our = (int) $r['our_income']; $spi = $r['spi_income'] !== null ? (int) $r['spi_income'] : null;
            $d = ($spi !== null && $spi > 0) ? ($our - $spi) / $spi * 100 : null; ?>
            <tr>
                <td><?= $fmtDate($r['tanggal']) ?></td>
                <td class="text-end"><?= $rp($our) ?></td>
                <td class="text-end"><?= $spi !== null ? $rp($spi) : '<span class="text-secondary">menunggu…</span>' ?></td>
                <td class="text-end <?= $d === null ? '' : ($d >= 0 ? 'text-success' : 'text-danger') ?>">
                    <?= $d === null ? '—' : ($d >= 0 ? '▲' : '▼') . ' ' . number_format(abs($d), 1) . '%' ?>
                </td>
                <td class="text-end"><?= $spi === null ? '<span class="badge bg-secondary">belum final</span>' : '<span class="badge bg-success">final</span>' ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php else: ?>
    <div class="text-secondary small">Belum ada data rekaman untuk dibandingkan.</div>
    <?php endif; ?>
</div></div>
<?php endif; ?>

<?php endif; // empty ?>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<?php if (empty($empty)): ?>
<script>
const PTS = <?= json_encode($points) ?>;
const labels = PTS.map(p => p.t);
new Chart(document.getElementById('chartIntra'), {
    type:'line',
    data:{ labels, datasets:[
        { label:'Total', data:PTS.map(p=>p.total), borderColor:'#0ea5e9', backgroundColor:'rgba(14,165,233,.12)', fill:true, tension:.3, borderWidth:2, pointRadius:0 },
        { label:'Mobil', data:PTS.map(p=>p.mobil), borderColor:'#6366f1', tension:.3, borderWidth:1.5, pointRadius:0 },
        { label:'Motor', data:PTS.map(p=>p.motor), borderColor:'#f59e0b', tension:.3, borderWidth:1.5, pointRadius:0 },
        { label:'Lainnya', data:PTS.map(p=>p.other), borderColor:'#10b981', tension:.3, borderWidth:1.5, pointRadius:0, hidden:PTS.every(p=>!p.other) },
    ]},
    options:{ responsive:true, maintainAspectRatio:false, interaction:{ mode:'index', intersect:false },
        plugins:{ legend:{ position:'bottom' } },
        scales:{ x:{ ticks:{ maxTicksLimit:12, font:{ size:9 } } }, y:{ beginAtZero:true } } }
});
<?php if ($canRev): ?>
const incEl = document.getElementById('chartIncome');
if (incEl) new Chart(incEl, {
    type:'line',
    data:{ labels, datasets:[{ label:'Income', data:PTS.map(p=>p.income), borderColor:'#16a34a', backgroundColor:'rgba(22,163,74,.12)', fill:true, tension:.3, borderWidth:2, pointRadius:0 }]},
    options:{ responsive:true, maintainAspectRatio:false,
        plugins:{ legend:{ display:false }, tooltip:{ callbacks:{ label: c => 'Rp '+Number(c.parsed.y).toLocaleString('id-ID') } } },
        scales:{ x:{ ticks:{ maxTicksLimit:12, font:{ size:9 } } }, y:{ beginAtZero:true, ticks:{ callback: v => (v/1e6)+'jt' } } } }
});
<?php endif; ?>

// Arus masuk/keluar per jam (tanggal terpilih)
const FLOW = <?= json_encode($flowDay) ?>;
const flEl = document.getElementById('chartFlow');
if (flEl && FLOW.length) new Chart(flEl, {
    type:'bar',
    data:{ labels: FLOW.map(f => ('0'+f.jam).slice(-2)+':00'), datasets:[
        { label:'Masuk', data:FLOW.map(f=>f.masuk), backgroundColor:'#16a34a', borderWidth:0 },
        { label:'Keluar (est.)', data:FLOW.map(f=>f.keluarEst), backgroundColor:'#f59e0b', borderWidth:0 },
    ]},
    options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{ position:'bottom' } },
        scales:{ x:{ ticks:{ font:{ size:9 } } }, y:{ beginAtZero:true } } }
});

// Heatmap data (JSON) — lazy build saat di-toggle
const HEATD = {
    in:    <?= json_encode($heatM) ?>,
    mobil: <?= json_encode($heatMobil) ?>,
    motor: <?= json_encode($heatMotor) ?>
};
const HEAT_RGB = { in:'34,197,94', mobil:'59,130,246', motor:'251,191,36' };
const HEAT_DOWS = [[2,'Sen'],[3,'Sel'],[4,'Rab'],[5,'Kam'],[6,'Jum'],[7,'Sab'],[1,'Min']];
const fmtN = n => Number(n).toLocaleString('id-ID');
function buildHeat(key) {
    const el = document.getElementById('heat-'+key);
    if (!el || el.dataset.built) return;
    const data = HEATD[key] || {}, rgb = HEAT_RGB[key];
    let max = 1;
    for (const d in data) for (const h in data[d]) if (data[d][h] > max) max = data[d][h];
    let html = '<div class="table-responsive"><table class="hm-table mb-0"><thead><tr><th></th>';
    for (let h=0; h<24; h++) html += '<th class="hm-lbl">'+h+'</th>';
    html += '</tr></thead><tbody>';
    for (const [dw,lbl] of HEAT_DOWS) {
        html += '<tr><td class="hm-lbl text-end fw-semibold">'+lbl+'</td>';
        for (let h=0; h<24; h++) {
            const v = (data[dw]||{})[h];
            if (v === undefined) html += '<td><div class="hm-cell" style="background:rgba(148,163,184,.12)"></div></td>';
            else { const a = (0.12+0.88*(v/max)).toFixed(2); html += '<td><div class="hm-cell" style="background:rgba('+rgb+','+a+')" title="'+lbl+' '+h+':00 — '+fmtN(v)+'">'+(v>=max*0.6?fmtN(v):'')+'</div></td>'; }
        }
        html += '</tr>';
    }
    el.innerHTML = html + '</tbody></table></div>';
    el.dataset.built = '1';
}
['in','mobil','motor'].forEach(k => buildHeat(k));
</script>
<?php endif; ?>
<?= $this->endSection() ?>
