<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<style>
/* ── Dashboard Pemantauan AI — aksen tema "AI" ─────────────────────── */
.ai-dash-title{
    background:linear-gradient(90deg,#6366f1 0%,#22d3ee 60%,#a855f7 100%);
    -webkit-background-clip:text;background-clip:text;
    -webkit-text-fill-color:transparent;color:transparent;
}
.ai-accent-rule{height:3px;border-radius:3px;width:72px;
    background:linear-gradient(90deg,#6366f1,#22d3ee);}
.ai-kpi{position:relative;overflow:hidden}
.ai-kpi .ai-kpi-ico{
    width:2.5rem;height:2.5rem;border-radius:50%;
    display:flex;align-items:center;justify-content:center;
    font-size:1.15rem;flex:0 0 auto;
}
.ai-kpi .ai-kpi-num{font-size:1.7rem;font-weight:700;line-height:1.1}
.ai-kpi .ai-kpi-lbl{font-size:.68rem;letter-spacing:.02em;text-transform:uppercase}
/* lingkaran ikon beraksen — subtle, terbaca di gelap & terang */
.ai-ico-indigo{background:rgba(99,102,241,.15);color:#818cf8}
.ai-ico-cyan  {background:rgba(34,211,238,.15);color:#22d3ee}
.ai-ico-violet{background:rgba(168,85,247,.15);color:#c084fc}
.ai-ico-green {background:rgba(27,175,122,.15);color:#2bbf8a}
.ai-ico-amber {background:rgba(237,161,0,.15);color:#e0a92e}
.ai-ico-red   {background:rgba(208,59,59,.15);color:#e06464}
.ai-ico-slate {background:rgba(100,116,139,.15);color:#94a3b8}
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1 ai-dash-title"><i class="bi bi-cpu me-2"></i>Dashboard Pemantauan AI</h4>
        <div class="ai-accent-rule mb-2"></div>
        <small class="text-muted">Ikhtisar pemakaian Claude Code tim &mdash; <?= tgl_indo($tanggal) ?></small>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= base_url('ai-monitor') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-people me-1"></i>Per Karyawan</a>
        <a href="<?= base_url('ai-monitor/komputer') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pc-display me-1"></i>Per Komputer</a>
    </div>
</div>

<?php
$kpiTiles = [
    ['k' => 'prompt_hari_ini',   'lbl' => 'Prompt hari ini',   'icon' => 'bi-chat-dots',       'ico' => 'ai-ico-indigo'],
    ['k' => 'prompt_7hari',      'lbl' => 'Prompt 7 hari',     'icon' => 'bi-chat-left-text',  'ico' => 'ai-ico-cyan'],
    ['k' => 'sesi_7hari',        'lbl' => 'Sesi 7 hari',       'icon' => 'bi-collection',      'ico' => 'ai-ico-violet'],
    ['k' => 'token_7hari',       'lbl' => 'Token 7 hari',      'icon' => 'bi-coin',            'ico' => 'ai-ico-cyan'],
    ['k' => 'total_komputer',    'lbl' => 'Total komputer',    'icon' => 'bi-pc-display',      'ico' => 'ai-ico-slate'],
    ['k' => 'komputer_aktif',    'lbl' => 'Komputer aktif',    'icon' => 'bi-check-circle',    'ico' => 'ai-ico-green'],
    ['k' => 'komputer_pending',  'lbl' => 'Menunggu setujui',  'icon' => 'bi-hourglass-split', 'ico' => 'ai-ico-amber'],
    ['k' => 'komputer_diblokir', 'lbl' => 'Diblokir',          'icon' => 'bi-slash-circle',    'ico' => 'ai-ico-red'],
];
?>
<div class="row g-2 mb-4">
<?php foreach ($kpiTiles as $t): ?>
    <div class="col-6 col-md-4 col-xl-3">
        <div class="card h-100 ai-kpi">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="ai-kpi-ico <?= $t['ico'] ?>"><i class="bi <?= $t['icon'] ?>"></i></div>
                <div>
                    <div class="ai-kpi-num"><?= number_format((int) $kpi[$t['k']]) ?></div>
                    <div class="ai-kpi-lbl text-muted"><?= esc($t['lbl']) ?></div>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>

<div class="row g-3 mb-4">
    <!-- Tren 14 hari -->
    <div class="col-12 col-lg-8">
        <div class="card h-100">
            <div class="card-header py-2"><span class="fw-semibold small"><i class="bi bi-graph-up-arrow me-2 text-muted"></i>Tren 14 Hari <span class="text-muted fw-normal">(prompt &amp; token)</span></span></div>
            <div class="card-body">
                <div style="height:280px"><canvas id="aiTren"></canvas></div>
            </div>
        </div>
    </div>
    <!-- Donut status -->
    <div class="col-12 col-lg-4">
        <div class="card h-100">
            <div class="card-header py-2"><span class="fw-semibold small"><i class="bi bi-pie-chart me-2 text-muted"></i>Status Perangkat</span></div>
            <div class="card-body">
                <?php if ($kpi['total_komputer'] > 0): ?>
                <div style="height:280px"><canvas id="aiStatus"></canvas></div>
                <?php else: ?>
                <div class="text-center text-muted py-5" style="font-size:.85rem"><i class="bi bi-pc-display d-block fs-2 mb-2 opacity-25"></i>Belum ada perangkat</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <!-- Top komputer -->
    <div class="col-12 col-lg-6">
        <div class="card h-100">
            <div class="card-header py-2"><span class="fw-semibold small"><i class="bi bi-pc-display me-2 text-muted"></i>Top Komputer <span class="text-muted fw-normal">(prompt 7 hari)</span></span></div>
            <div class="card-body">
                <?php if (! empty($top_komputer)): ?>
                <div style="height:<?= max(160, count($top_komputer) * 44 + 40) ?>px"><canvas id="aiTopKomp"></canvas></div>
                <?php else: ?>
                <div class="text-center text-muted py-4" style="font-size:.85rem">Belum ada aktivitas pada rentang ini</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <!-- Top karyawan -->
    <div class="col-12 col-lg-6">
        <div class="card h-100">
            <div class="card-header py-2"><span class="fw-semibold small"><i class="bi bi-people me-2 text-muted"></i>Top Karyawan <span class="text-muted fw-normal">(prompt 7 hari)</span></span></div>
            <div class="card-body">
                <?php if (! empty($top_karyawan)): ?>
                <div style="height:<?= max(160, count($top_karyawan) * 44 + 40) ?>px"><canvas id="aiTopKar"></canvas></div>
                <?php else: ?>
                <div class="text-center text-muted py-4" style="font-size:.85rem">Belum ada aktivitas pada rentang ini</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Sesi terbaru -->
<div class="card">
<div class="card-header py-2"><span class="fw-semibold small"><i class="bi bi-clock-history me-2 text-muted"></i>Sesi Terbaru</span></div>
<div class="table-responsive">
<table class="table table-hover align-middle mb-0">
<thead class="table-light">
<tr>
    <th>Judul</th>
    <th>Komputer</th>
    <th>Karyawan</th>
    <th>Waktu</th>
    <th class="text-center">Prompt</th>
</tr>
</thead>
<tbody>
<?php if (empty($sesi_terbaru)): ?>
<tr><td colspan="5" class="text-center text-muted py-5">
    <i class="bi bi-chat-square-text d-block fs-1 mb-2 opacity-25"></i>Belum ada sesi
</td></tr>
<?php else: foreach ($sesi_terbaru as $s): ?>
<tr>
    <td>
        <a href="<?= base_url('ai-monitor/sesi/' . (int) $s['id']) ?>" class="fw-medium text-decoration-none">
            <?= $s['judul'] !== '' && $s['judul'] !== null ? esc($s['judul']) : '(tanpa judul)' ?>
        </a>
    </td>
    <td><span class="badge bg-secondary-subtle text-secondary"><i class="bi bi-laptop me-1"></i><?= esc($s['komputer']) ?></span></td>
    <td><?= esc($s['nama']) ?></td>
    <td class="small"><?= $s['terakhir_at'] ? tgl_indo($s['terakhir_at'], true) : '—' ?></td>
    <td class="text-center"><?= number_format((int) $s['jml_prompt']) ?></td>
</tr>
<?php endforeach; endif; ?>
</tbody>
</table>
</div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof Chart === 'undefined') return;
    const dark = document.documentElement.getAttribute('data-theme') === 'dark';

    // Palet CVD-safe (light/dark) — konsisten dgn laporan lain.
    const C = {
        indigo: dark ? '#818cf8' : '#6366f1',
        cyan:   dark ? '#22d3ee' : '#0891b2',
        green:  dark ? '#199e70' : '#1baf7a',
        amber:  dark ? '#c98500' : '#eda100',
        red:    '#d03b3b',
        slate:  dark ? '#94a3b8' : '#64748b',
    };
    const surface  = dark ? '#0e1a2a' : '#ffffff';
    const inkMuted = dark ? 'rgba(221,232,248,.65)' : 'rgba(33,37,41,.65)';
    const gridCol  = dark ? 'rgba(255,255,255,.07)' : 'rgba(0,0,0,.06)';

    const hexA = (hex, a) => {
        const n = parseInt(hex.slice(1), 16);
        return `rgba(${(n>>16)&255},${(n>>8)&255},${n&255},${a})`;
    };

    // ── Tren 14 hari: prompt (batang) + token (garis, sumbu kanan) ──────
    const deret = <?= json_encode($deret14) ?>;
    new Chart(document.getElementById('aiTren'), {
        data: {
            labels: deret.map(d => d.label),
            datasets: [
                {
                    type: 'line', label: 'Prompt', yAxisID: 'y',
                    data: deret.map(d => d.prompt),
                    borderColor: C.indigo, backgroundColor: hexA(C.indigo, .18),
                    fill: true, tension: .35, borderWidth: 2,
                    pointRadius: 2, pointHoverRadius: 4, pointBackgroundColor: C.indigo,
                },
                {
                    type: 'line', label: 'Token', yAxisID: 'y1',
                    data: deret.map(d => d.token),
                    borderColor: C.cyan, backgroundColor: hexA(C.cyan, .12),
                    fill: true, tension: .35, borderWidth: 2, borderDash: [5, 4],
                    pointRadius: 2, pointHoverRadius: 4, pointBackgroundColor: C.cyan,
                },
            ],
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'bottom', labels: { color: inkMuted, usePointStyle: true, pointStyle: 'circle', boxWidth: 7, boxHeight: 7, font: { size: 11 } } },
            },
            scales: {
                x:  { ticks: { color: inkMuted, font: { size: 10 } }, grid: { display: false } },
                y:  { position: 'left',  beginAtZero: true, title: { display: true, text: 'Prompt', color: inkMuted, font: { size: 10 } }, ticks: { color: inkMuted, precision: 0, font: { size: 10 } }, grid: { color: gridCol } },
                y1: { position: 'right', beginAtZero: true, title: { display: true, text: 'Token', color: inkMuted, font: { size: 10 } }, ticks: { color: inkMuted, font: { size: 10 } }, grid: { drawOnChartArea: false } },
            },
        },
    });

    // ── Donut status perangkat ──────────────────────────────────────────
    <?php if ($kpi['total_komputer'] > 0): ?>
    const sc = <?= json_encode($status_counts) ?>;
    new Chart(document.getElementById('aiStatus'), {
        type: 'doughnut',
        data: {
            labels: ['Aktif', 'Menunggu persetujuan', 'Diblokir', 'Dinonaktifkan'],
            datasets: [{
                data: [sc.aktif, sc.pending, sc.diblokir, sc.nonaktif],
                backgroundColor: [C.green, C.amber, C.red, C.slate],
                borderColor: surface, borderWidth: 2,
            }],
        },
        options: {
            responsive: true, maintainAspectRatio: false, cutout: '62%',
            plugins: {
                legend: { position: 'bottom', labels: { color: inkMuted, usePointStyle: true, pointStyle: 'circle', boxWidth: 7, boxHeight: 7, font: { size: 11 } } },
            },
        },
    });
    <?php endif; ?>

    // ── Top komputer (bar horizontal) ───────────────────────────────────
    <?php if (! empty($top_komputer)): ?>
    const tk = <?= json_encode($top_komputer) ?>;
    new Chart(document.getElementById('aiTopKomp'), {
        type: 'bar',
        data: {
            labels: tk.map(r => r.label),
            datasets: [{ label: 'Prompt', data: tk.map(r => r.prompt), backgroundColor: hexA(C.indigo, .85), borderColor: C.indigo, borderWidth: 1, borderRadius: 5 }],
        },
        options: {
            indexAxis: 'y', responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { beginAtZero: true, ticks: { color: inkMuted, precision: 0, font: { size: 10 } }, grid: { color: gridCol } },
                y: { ticks: { color: inkMuted, font: { size: 10 } }, grid: { display: false } },
            },
        },
    });
    <?php endif; ?>

    // ── Top karyawan (bar horizontal) ───────────────────────────────────
    <?php if (! empty($top_karyawan)): ?>
    const tkar = <?= json_encode($top_karyawan) ?>;
    new Chart(document.getElementById('aiTopKar'), {
        type: 'bar',
        data: {
            labels: tkar.map(r => r.nama),
            datasets: [{ label: 'Prompt', data: tkar.map(r => r.prompt), backgroundColor: hexA(C.cyan, .85), borderColor: C.cyan, borderWidth: 1, borderRadius: 5 }],
        },
        options: {
            indexAxis: 'y', responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { beginAtZero: true, ticks: { color: inkMuted, precision: 0, font: { size: 10 } }, grid: { color: gridCol } },
                y: { ticks: { color: inkMuted, font: { size: 10 } }, grid: { display: false } },
            },
        },
    });
    <?php endif; ?>
});
</script>
<?= $this->endSection() ?>
