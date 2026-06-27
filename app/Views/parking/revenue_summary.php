<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<style>
.pk-chart { position:relative; height:300px; }
.pk-chart-sm { position:relative; height:240px; }
/* Tabel revenue: header & footer sticky, background navy solid (tema --card-bg cuma .92 opaque) */
.rev-tbl { border-collapse:separate; border-spacing:0; }
.rev-tbl thead th { position:sticky; top:0; z-index:5; background-color:#0e1a2a !important; box-shadow:inset 0 -1px 0 rgba(255,255,255,.12); }
.rev-tbl tfoot td { position:sticky; bottom:0; z-index:5; background-color:#0e1a2a !important; box-shadow:inset 0 1px 0 rgba(255,255,255,.12); }
</style>
<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= $this->include('parking/_databanner') ?>

<?php
$rp = fn($n) => 'Rp ' . number_format((float)$n, 0, ',', '.');
$fmtDate = fn($d) => date('d M Y', strtotime($d));
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h4 class="mb-0"><i class="bi bi-graph-up-arrow me-2"></i>Revenue Parkir — <span class="text-success">Summary</span></h4>
        <div class="text-secondary small">Balikpapan Superblock · historis · sumber: SPI (read-only)</div>
    </div>
    <div class="d-flex flex-wrap align-items-center gap-2">
        <?= $this->include('parking/_syncbtn') ?>
        <a href="<?= base_url('parking/compare') ?>" class="btn btn-outline-success btn-sm"><i class="bi bi-arrow-left-right"></i> Compare</a>
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
    <div class="col-auto"><button class="btn btn-success btn-sm"><i class="bi bi-funnel"></i> Terapkan</button></div>
    <div class="col-auto text-secondary small">Data tersedia sejak Jan 2023</div>
</form>

<!-- KPI periode terpilih (Casual/Member basis bulanan) -->
<div class="row g-3 mb-1">
    <div class="col-12 col-md-4">
        <div class="card pk-kpi h-100" style="background:linear-gradient(135deg,#15803d,#166534) !important"><div class="card-body" style="color:#fff">
            <div class="small" style="color:#fff;opacity:.8">Total (Casual + Member) · periode</div>
            <div style="font-size:1.5rem;font-weight:700;color:#fff"><?= $rp($perTotal) ?></div>
            <div class="small" style="color:#fff;opacity:.8"><?= $fmtDate($start) ?> – <?= $fmtDate($end) ?></div>
        </div></div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card pk-kpi h-100"><div class="card-body">
            <div class="small text-secondary">Casual</div>
            <div style="font-size:1.4rem;font-weight:700"><?= $rp($perCasual) ?></div>
            <div class="small text-secondary"><?= $perTotal>0?round($perCasual/$perTotal*100):0 ?>%</div>
        </div></div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card pk-kpi h-100"><div class="card-body">
            <div class="small text-secondary">Member</div>
            <div style="font-size:1.4rem;font-weight:700"><?= $rp($perMember) ?></div>
            <div class="small text-secondary"><?= $perTotal>0?round($perMember/$perTotal*100):0 ?>%</div>
        </div></div>
    </div>
</div>
<div class="text-secondary small mb-3"><i class="bi bi-info-circle"></i> Casual &amp; Member dihitung per <strong>bulan</strong> yang termasuk periode (sumber SPI hanya menyediakan split bulanan). Untuk income casual harian presisi, lihat <em>Income per Hari</em> &amp; <em>Income per Jenis</em> di bawah.</div>

<div class="row g-3">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title">Tren Income Bulanan <span class="text-secondary small fw-normal">(sejak Jan 2023, basis tanggal bayar)</span></h6>
                <div class="pk-chart mt-2"><canvas id="chartMonthly"></canvas></div>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <h6 class="card-title mb-0">Income per Hari</h6>
                    <span class="text-secondary small"><?= $fmtDate($start) ?> – <?= $fmtDate($end) ?></span>
                </div>
                <div class="pk-chart mt-2"><canvas id="chartDaily"></canvas></div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <h6 class="card-title">Income per Jenis</h6>
                <div class="text-secondary small mb-2">Periode: <strong><?= $rp($sumPeriod) ?></strong></div>
                <div class="pk-chart-sm"><canvas id="chartType"></canvas></div>
            </div>
        </div>
    </div>
</div>

<!-- Statistik harian + Income tahunan + Payment history -->
<div class="row g-3 mt-1">
    <div class="col-12 col-lg-4">
        <div class="card h-100"><div class="card-body">
            <h6 class="card-title">Statistik Harian <span class="text-secondary small fw-normal">(periode terpilih)</span></h6>
            <div class="d-flex justify-content-between py-1 border-bottom"><span class="text-secondary small">Rata-rata / hari</span><strong><?= $rp($stats['avg']) ?></strong></div>
            <div class="d-flex justify-content-between py-1 border-bottom"><span class="text-secondary small">Tertinggi <?= $stats['maxDay'] ? '('.date('d M', strtotime($stats['maxDay'])).')' : '' ?></span><strong class="text-success"><?= $rp($stats['maxVal']) ?></strong></div>
            <div class="d-flex justify-content-between py-1 border-bottom"><span class="text-secondary small">Terendah <?= $stats['minDay'] ? '('.date('d M', strtotime($stats['minDay'])).')' : '' ?></span><strong class="text-danger"><?= $rp($stats['minVal']) ?></strong></div>
            <div class="d-flex justify-content-between py-1"><span class="text-secondary small">Jumlah hari berdata</span><strong><?= (int)$stats['days'] ?></strong></div>
        </div></div>
    </div>
    <div class="col-12 col-lg-4">
        <div class="card h-100"><div class="card-body">
            <h6 class="card-title">Income Tahunan <span class="text-secondary small fw-normal">(Casual+Member)</span></h6>
            <div class="pk-chart-sm"><canvas id="chartYearly"></canvas></div>
        </div></div>
    </div>
    <div class="col-12 col-lg-4">
        <div class="card h-100"><div class="card-body">
            <h6 class="card-title">Per Metode Pembayaran <span class="text-secondary small fw-normal">(periode terpilih)</span></h6>
            <?php if (! empty($payRows)): $pTot = array_sum(array_column($payRows, 'total')); ?>
            <div class="text-secondary small mb-2">Total: <strong><?= $rp($pTot) ?></strong></div>
            <div class="table-responsive" style="max-height:220px;overflow:auto">
                <table class="table table-sm align-middle mb-0">
                    <tbody>
                    <?php foreach ($payRows as $pr): ?>
                        <tr><td><?= esc($pr['method']) ?></td><td class="text-end"><?= $rp($pr['total']) ?></td>
                            <td class="text-end text-secondary small"><?= $pTot>0?round($pr['total']/$pTot*100):0 ?>%</td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-secondary small"><i class="bi bi-info-circle"></i> Belum ada arsip metode pembayaran untuk periode ini. Jalankan <code>php spark mic:spi-sync --from 2023-01-01</code> untuk mengisi arsip dari SPI.</div>
            <?php endif; ?>
        </div></div>
    </div>
</div>

<!-- Tabel revenue: Month-to-Month & Day-to-Day -->
<div class="row g-3 mt-1">
    <!-- Month to month (Casual / Member / Total) -->
    <div class="col-12 col-xl-5">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-calendar3 me-2"></i>Revenue per Bulan</h6>
                <span class="text-secondary small">sejak Jan 2023</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height:460px">
                    <table class="table table-sm table-hover align-middle mb-0 small rev-tbl">
                        <thead>
                            <tr>
                                <th class="text-start ps-3">Bulan</th>
                                <th class="text-end">Casual</th>
                                <th class="text-end">Member</th>
                                <th class="text-end pe-3">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($total)): ?>
                            <tr><td colspan="4" class="text-secondary py-3 text-center">Belum ada data.</td></tr>
                            <?php else: foreach ($total as $i => $t):
                                $c = $casual[$i]['value'] ?? 0;
                                $m = $member[$i]['value'] ?? 0;
                            ?>
                            <tr>
                                <td class="text-start ps-3 fw-medium"><?= esc($t['label']) ?></td>
                                <td class="text-end"><?= $rp($c) ?></td>
                                <td class="text-end"><?= $rp($m) ?></td>
                                <td class="text-end pe-3 fw-semibold"><?= $rp($t['value']) ?></td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                        <?php if (! empty($total)): ?>
                        <tfoot class="fw-bold">
                            <tr>
                                <td class="text-start ps-3">Total</td>
                                <td class="text-end"><?= $rp($sumCasual) ?></td>
                                <td class="text-end"><?= $rp($sumMember) ?></td>
                                <td class="text-end pe-3 text-success"><?= $rp($sumTotal) ?></td>
                            </tr>
                        </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Day to day (per jenis) -->
    <div class="col-12 col-xl-7">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-calendar-day me-2"></i>Revenue per Hari <span class="text-secondary small fw-normal">(per jenis)</span></h6>
                <span class="text-secondary small"><?= $fmtDate($start) ?> – <?= $fmtDate($end) ?></span>
            </div>
            <div class="card-body p-0">
                <?php $rtypes = ['mobil' => 'Mobil', 'motor' => 'Motor', 'box' => 'Box', 'truck' => 'Truck', 'taxi' => 'Taxi', 'bus' => 'Bus']; ?>
                <div class="table-responsive" style="max-height:460px">
                    <table class="table table-sm table-hover align-middle mb-0 small text-end rev-tbl">
                        <thead>
                            <tr>
                                <th class="text-start ps-3">Tanggal</th>
                                <?php foreach ($rtypes as $lbl): ?>
                                <th><?= $lbl ?></th>
                                <?php endforeach; ?>
                                <th class="pe-3">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($daily)): ?>
                            <tr><td colspan="<?= count($rtypes) + 2 ?>" class="text-secondary py-3 text-center">Belum ada data untuk periode ini.</td></tr>
                            <?php else: foreach ($daily as $row): ?>
                            <tr>
                                <td class="text-start ps-3 fw-medium"><?= $fmtDate($row['tanggal']) ?></td>
                                <?php foreach ($rtypes as $k => $lbl): $v = (int) ($row[$k] ?? 0); ?>
                                <td class="<?= $v === 0 ? 'text-secondary' : '' ?>"><?= $rp($v) ?></td>
                                <?php endforeach; ?>
                                <td class="pe-3 fw-semibold"><?= $rp((int) ($row['total'] ?? 0)) ?></td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                        <?php if (! empty($daily)): ?>
                        <tfoot class="fw-bold">
                            <tr>
                                <td class="text-start ps-3">Total</td>
                                <?php foreach ($rtypes as $k => $lbl): ?>
                                <td><?= $rp($byType[$k] ?? 0) ?></td>
                                <?php endforeach; ?>
                                <td class="pe-3 text-success"><?= $rp($sumPeriod) ?></td>
                            </tr>
                        </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="alert alert-secondary small mt-3 mb-0">
    <i class="bi bi-info-circle me-1"></i>
    Angka <strong>bulanan</strong> = laporan resmi <em>income-summary</em> (basis tanggal bayar). Angka <strong>harian per jenis</strong> = basis tanggal tiket — bisa beda di hari tertentu untuk transaksi non-tunai yang settle beda tanggal.
</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
const PK = { casual: <?= json_encode($casual) ?>, member: <?= json_encode($member) ?>, total: <?= json_encode($total) ?>,
    daily: <?= json_encode($daily) ?>, byType: <?= json_encode($byType) ?>, yearly: <?= json_encode($yearly) ?> };
const PK_COLORS = { mobil:'#0ea5e9', motor:'#f59e0b', box:'#10b981', truck:'#8b5cf6', taxi:'#ec4899', bus:'#ef4444' };
const PK_TL = { mobil:'Mobil', motor:'Motor', box:'Box', truck:'Truck', taxi:'Taxi', bus:'Bus' };
const rpShort = v => { v=Number(v||0); if(v>=1e9) return (v/1e9).toFixed(2)+' M'; if(v>=1e6) return (v/1e6).toFixed(1)+' jt'; return v.toLocaleString('id-ID'); };

(function () {
    new Chart(document.getElementById('chartMonthly'), {
        type:'line',
        data:{ labels: PK.casual.map(d=>d.label), datasets:[
            { label:'Total (Casual+Member)', data:PK.total.map(d=>d.value), borderColor:'#15803d', backgroundColor:'rgba(21,128,61,.10)', fill:true, tension:.35, pointRadius:2, borderWidth:2 },
            { label:'Casual', data:PK.casual.map(d=>d.value), borderColor:'#0ea5e9', backgroundColor:'rgba(14,165,233,.08)', fill:false, tension:.35, pointRadius:2 },
            { label:'Member', data:PK.member.map(d=>d.value), borderColor:'#f59e0b', backgroundColor:'rgba(245,158,11,.08)', fill:false, tension:.35, pointRadius:2 },
        ] },
        options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{ position:'bottom' },
            tooltip:{ callbacks:{ label: c => c.dataset.label+': Rp '+Number(c.parsed.y).toLocaleString('id-ID') } } },
            scales:{ y:{ beginAtZero:true, ticks:{ callback:v=>rpShort(v) } } } }
    });
    const types = ['mobil','motor','box','truck','taxi','bus'];
    new Chart(document.getElementById('chartType'), {
        type:'doughnut',
        data:{ labels: types.map(t=>PK_TL[t]), datasets:[{ data: types.map(t=>PK.byType[t]), backgroundColor: types.map(t=>PK_COLORS[t]), borderWidth:0 }] },
        options:{ responsive:true, maintainAspectRatio:false, cutout:'62%', plugins:{ legend:{ position:'right' },
            tooltip:{ callbacks:{ label: c => c.label+': Rp '+Number(c.parsed).toLocaleString('id-ID') } } } }
    });
    // Income tahunan
    const yKeys = Object.keys(PK.yearly);
    if (yKeys.length) new Chart(document.getElementById('chartYearly'), {
        type:'bar',
        data:{ labels:yKeys, datasets:[{ label:'Total', data:yKeys.map(k=>PK.yearly[k]), backgroundColor:'#15803d', borderWidth:0 }] },
        options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{ display:false },
            tooltip:{ callbacks:{ label: c => 'Rp '+Number(c.parsed.y).toLocaleString('id-ID') } } },
            scales:{ y:{ beginAtZero:true, ticks:{ callback:v=>rpShort(v) } } } }
    });

    const dL = PK.daily.map(d => { const p=(d.tanggal||'').split('-'); return p.length===3 ? p[2]+'/'+p[1] : d.tanggal; });
    new Chart(document.getElementById('chartDaily'), {
        type:'bar',
        data:{ labels:dL, datasets:[{ label:'Income', data:PK.daily.map(d=>d.total), backgroundColor:'#16a34a', borderWidth:0 }] },
        options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{ display:false },
            tooltip:{ callbacks:{ label: c => 'Rp '+Number(c.parsed.y).toLocaleString('id-ID') } } },
            scales:{ y:{ beginAtZero:true, ticks:{ callback:v=>rpShort(v) } } } }
    });
})();
</script>
<?= $this->endSection() ?>
