<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<style>
@keyframes hrUp { from { opacity:0; transform:translateY(14px); } to { opacity:1; transform:translateY(0); } }
.hr-up { opacity:0; animation:hrUp .5s cubic-bezier(.22,.68,0,1.15) forwards; }
.kpi-card { display:flex; align-items:center; gap:.95rem; border-radius:1rem; padding:1.05rem 1.2rem; position:relative; overflow:hidden; transition:transform .18s ease, box-shadow .18s ease; }
.kpi-card:hover { transform:translateY(-3px); box-shadow:0 .6rem 1.4rem rgba(0,0,0,.12); }
.kpi-card .kpi-ico { width:50px; height:50px; border-radius:.85rem; display:flex; align-items:center; justify-content:center; font-size:1.45rem; flex:0 0 auto; }
.kpi-card .kpi-val { font-size:1.95rem; font-weight:800; line-height:1; letter-spacing:-.02em; color:var(--txt) !important; }
.kpi-card .kpi-lbl { font-size:.7rem; text-transform:uppercase; letter-spacing:.05em; margin-top:.32rem; font-weight:600; color:var(--txt) !important; opacity:.75; }
.kpi-card .kpi-sub { font-size:.67rem; margin-top:.1rem; color:var(--txt-muted) !important; }
.kpi-card::after { content:''; position:absolute; right:-26px; top:-26px; width:96px; height:96px; border-radius:50%; opacity:.09; }
.kpi-indigo .kpi-ico{background:rgba(99,102,241,.16);color:#6366f1} .kpi-indigo::after{background:#6366f1}
.kpi-green  .kpi-ico{background:rgba(34,197,94,.16);color:#16a34a}  .kpi-green::after{background:#22c55e}
.kpi-slate  .kpi-ico{background:rgba(100,116,139,.18);color:#64748b} .kpi-slate::after{background:#64748b}
.kpi-blue   .kpi-ico{background:rgba(59,130,246,.16);color:#3b82f6}  .kpi-blue::after{background:#3b82f6}
.hr-sec-title { font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.08em; opacity:.6; }
.hr-card { border-radius:1rem; transition:box-shadow .18s ease; }
.hr-card:hover { box-shadow:0 .5rem 1.2rem rgba(0,0,0,.08); }
.hr-card canvas { cursor:pointer; }
</style>
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center gap-2 mb-4">
    <h4 class="fw-bold mb-0"><i class="bi bi-people-fill me-2"></i>Dashboard Karyawan</h4>
    <a href="<?= base_url('people/employees') ?>" class="btn btn-sm btn-outline-secondary ms-auto"><i class="bi bi-person-vcard me-1"></i>Data Karyawan</a>
    <a href="<?= base_url('people/employees/export') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-download me-1"></i>Export</a>
</div>

<!-- KPI Strip -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3 hr-up" style="animation-delay:.04s">
        <div class="card kpi-card kpi-indigo">
            <div class="kpi-ico"><i class="bi bi-people-fill"></i></div>
            <div><div class="kpi-val"><?= $total ?></div><div class="kpi-lbl">Total Terdaftar</div><div class="kpi-sub">seluruh karyawan</div></div>
        </div>
    </div>
    <div class="col-6 col-lg-3 hr-up" style="animation-delay:.1s">
        <div class="card kpi-card kpi-green">
            <div class="kpi-ico"><i class="bi bi-person-check-fill"></i></div>
            <div><div class="kpi-val"><?= $nAktif ?></div><div class="kpi-lbl">Aktif</div><div class="kpi-sub"><?= $total > 0 ? round($nAktif/$total*100) : 0 ?>% dari total</div></div>
        </div>
    </div>
    <div class="col-6 col-lg-3 hr-up" style="animation-delay:.16s">
        <div class="card kpi-card kpi-slate">
            <div class="kpi-ico"><i class="bi bi-person-dash-fill"></i></div>
            <div><div class="kpi-val"><?= $nNonaktif ?></div><div class="kpi-lbl">Nonaktif</div><div class="kpi-sub">resign / pensiun / dll</div></div>
        </div>
    </div>
    <div class="col-6 col-lg-3 hr-up" style="animation-delay:.22s">
        <div class="card kpi-card kpi-blue">
            <div class="kpi-ico"><i class="bi bi-person-plus-fill"></i></div>
            <div><div class="kpi-val"><?= $baru90 ?></div><div class="kpi-lbl">Karyawan Baru</div><div class="kpi-sub">bergabung ≤ 90 hari</div></div>
        </div>
    </div>
</div>
<!-- Kontrak Mendekati Habis -->
<div class="card hr-card mb-4">
<div class="card-header d-flex align-items-center">
    <span class="hr-sec-title"><i class="bi bi-hourglass-bottom me-1"></i>Kontrak Mendekati Habis</span>
    <span class="badge bg-<?= !empty($kontrakHabis) ? 'danger' : 'secondary' ?> ms-2"><?= count($kontrakHabis) ?></span>
    <span class="text-muted ms-auto" style="font-size:.72rem">≤ 90 hari ke depan / sudah lewat</span>
</div>
<div class="card-body p-0">
<?php if (empty($kontrakHabis)): ?>
<p class="text-muted text-center py-4 small mb-0">Tidak ada kontrak yang mendekati habis. <span class="opacity-75">(Pastikan <b>Tanggal Akhir Kontrak</b> sudah diisi di data karyawan.)</span></p>
<?php else: ?>
<div class="table-responsive">
<table class="table table-sm align-middle mb-0">
<thead class="table-light"><tr><th class="ps-3">Karyawan</th><th>Departemen</th><th>Status</th><th>Akhir Kontrak</th><th class="text-end pe-3">Sisa</th></tr></thead>
<tbody>
<?php foreach ($kontrakHabis as $k):
    $sisa = (int) $k['sisa'];
    if ($sisa < 0)       { $bc = 'danger';  $txt = 'lewat ' . abs($sisa) . ' hari'; }
    elseif ($sisa <= 30) { $bc = 'danger';  $txt = $sisa . ' hari'; }
    elseif ($sisa <= 60) { $bc = 'warning'; $txt = $sisa . ' hari'; }
    else                 { $bc = 'info';    $txt = $sisa . ' hari'; }
?>
<tr style="cursor:pointer" onclick="location.href='<?= base_url('people/employees/'.$k['id']) ?>'">
    <td class="ps-3 fw-semibold small"><?= esc($k['nama']) ?></td>
    <td class="small text-muted"><?= esc($k['dept']) ?></td>
    <td class="small"><?= esc($k['kontrak']) ?></td>
    <td class="small text-nowrap"><?= date('d M Y', strtotime($k['akhir'])) ?></td>
    <td class="text-end pe-3"><span class="badge bg-<?= $bc ?>"><?= $txt ?></span></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php endif; ?>
</div>
</div>

<div class="text-muted small mb-3"><i class="bi bi-info-circle me-1"></i>Grafik komposisi di bawah dihitung dari <b><?= $nAktif ?> karyawan aktif</b>. <i class="bi bi-hand-index-thumb ms-1"></i> Klik segmen/bar mana pun untuk melihat daftar karyawannya.</div>

<!-- Row: Divisi + Status Kontrak + Gender -->
<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="card hr-card h-100"><div class="card-header"><span class="hr-sec-title">Headcount per Divisi</span></div>
            <div class="card-body"><canvas id="cDivisi" style="max-height:260px"></canvas></div></div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="card hr-card h-100"><div class="card-header"><span class="hr-sec-title">Status Kontrak</span></div>
            <div class="card-body d-flex align-items-center justify-content-center"><canvas id="cKontrak" style="max-height:220px"></canvas></div></div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="card hr-card h-100"><div class="card-header"><span class="hr-sec-title">Gender</span></div>
            <div class="card-body d-flex align-items-center justify-content-center"><canvas id="cGender" style="max-height:220px"></canvas></div></div>
    </div>
</div>

<!-- Row: Dept + Pendidikan -->
<div class="row g-3 mb-3">
    <div class="col-lg-7">
        <div class="card hr-card h-100"><div class="card-header"><span class="hr-sec-title">Headcount per Departemen</span></div>
            <div class="card-body"><canvas id="cDept" style="max-height:320px"></canvas></div></div>
    </div>
    <div class="col-lg-5">
        <div class="card hr-card h-100"><div class="card-header"><span class="hr-sec-title">Pendidikan</span></div>
            <div class="card-body"><canvas id="cPendidikan" style="max-height:320px"></canvas></div></div>
    </div>
</div>

<!-- Row: Project + Masa Kerja + Usia -->
<div class="row g-3 mb-3">
    <div class="col-lg-4">
        <div class="card hr-card h-100"><div class="card-header"><span class="hr-sec-title">Per Project (Sumber Gaji)</span></div>
            <div class="card-body d-flex align-items-center justify-content-center"><canvas id="cProject" style="max-height:240px"></canvas></div></div>
    </div>
    <div class="col-lg-4">
        <div class="card hr-card h-100"><div class="card-header"><span class="hr-sec-title">Masa Kerja</span></div>
            <div class="card-body"><canvas id="cMasa" style="max-height:240px"></canvas></div></div>
    </div>
    <div class="col-lg-4">
        <div class="card hr-card h-100"><div class="card-header"><span class="hr-sec-title">Rentang Usia</span></div>
            <div class="card-body"><canvas id="cUsia" style="max-height:240px"></canvas></div></div>
    </div>
</div>

<!-- Drill-down modal: daftar karyawan saat segmen grafik diklik -->
<div class="modal fade" id="drillModal" tabindex="-1">
<div class="modal-dialog modal-dialog-scrollable">
<div class="modal-content">
    <div class="modal-header"><h6 class="modal-title fw-semibold" id="drillTitle"></h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body p-0"><div id="drillBody"></div></div>
</div>
</div>
</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
const _palette = ['#6366f1','#22c55e','#f59e0b','#ef4444','#06b6d4','#8b5cf6','#ec4899','#14b8a6','#f97316','#3b82f6','#84cc16','#a855f7'];
const _tick = () => (getComputedStyle(document.body).getPropertyValue('--bs-secondary-color') || '#888').trim();
const MEMBERS = <?= json_encode($members, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_APOS) ?>;
const EMP_URL = '<?= base_url('people/employees') ?>/';
const esc = (s) => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const _drill = new bootstrap.Modal(document.getElementById('drillModal'));

function showMembers(dim, label, sub) {
    const list = (MEMBERS[dim] && MEMBERS[dim][label]) || [];
    document.getElementById('drillTitle').innerHTML =
        `${label} <span class="text-muted fw-normal">· ${list.length} orang${sub ? ' · ' + sub : ''}</span>`;
    let html = '<table class="table table-sm table-hover align-middle mb-0"><tbody>';
    list.slice().sort((a, b) => a.n.localeCompare(b.n)).forEach(m => {
        html += `<tr style="cursor:pointer" onclick="location.href='${EMP_URL}${encodeURIComponent(m.i)}'">
            <td class="ps-3 fw-medium small">${esc(m.n)}</td>
            <td class="small text-muted">${esc(m.d)}</td>
            <td class="text-end pe-3"><i class="bi bi-chevron-right text-muted"></i></td></tr>`;
    });
    html += '</tbody></table>';
    if (!list.length) html = '<p class="text-muted text-center py-4 small mb-0">Tidak ada data.</p>';
    document.getElementById('drillBody').innerHTML = html;
    _drill.show();
}
function _clickOpts(dim) {
    return { onClick: (e, els, chart) => { if (els.length) showMembers(dim, chart.data.labels[els[0].index]); } };
}
function barH(id, data, color, dim) {
    const lbl = Object.keys(data), val = Object.values(data);
    new Chart(document.getElementById(id), {
        type: 'bar',
        data: { labels: lbl, datasets: [{ data: val, backgroundColor: color || '#6366f1', borderRadius: 5, barPercentage: .8 }] },
        options: { indexAxis: 'y', plugins: { legend: { display: false } }, ..._clickOpts(dim),
            scales: { x: { ticks: { precision: 0, color: _tick() }, grid: { display: false } }, y: { ticks: { color: _tick() }, grid: { display: false } } } }
    });
}
function barV(id, data, color, dim) {
    const lbl = Object.keys(data), val = Object.values(data);
    new Chart(document.getElementById(id), {
        type: 'bar',
        data: { labels: lbl, datasets: [{ data: val, backgroundColor: color || '#22c55e', borderRadius: 5, barPercentage: .7 }] },
        options: { plugins: { legend: { display: false } }, ..._clickOpts(dim),
            scales: { y: { ticks: { precision: 0, color: _tick() }, grid: { display: false } }, x: { ticks: { color: _tick() }, grid: { display: false } } } }
    });
}
function donut(id, data, dim) {
    const lbl = Object.keys(data), val = Object.values(data), tot = val.reduce((a, b) => a + b, 0);
    new Chart(document.getElementById(id), {
        type: 'doughnut',
        data: { labels: lbl, datasets: [{ data: val, backgroundColor: _palette, borderWidth: 2, borderColor: 'rgba(0,0,0,0)', hoverOffset: 6 }] },
        options: { cutout: '62%', ..._clickOpts(dim), plugins: {
            legend: { position: 'bottom', labels: { boxWidth: 10, padding: 10, font: { size: 11 }, color: _tick() } },
            tooltip: { callbacks: { label: c => ` ${c.label}: ${c.parsed} (${tot ? Math.round(c.parsed / tot * 100) : 0}%)` } }
        } }
    });
}
barH('cDivisi', <?= json_encode($divisi) ?>, '#6366f1', 'divisi');
donut('cKontrak', <?= json_encode($kontrak) ?>, 'kontrak');
donut('cGender', <?= json_encode($gender) ?>, 'gender');
barH('cDept', <?= json_encode($dept) ?>, '#06b6d4', 'dept');
barH('cPendidikan', <?= json_encode($pendidikan) ?>, '#8b5cf6', 'pendidikan');
donut('cProject', <?= json_encode($project) ?>, 'project');
barV('cMasa', <?= json_encode($masaKerja) ?>, '#f59e0b', 'masaKerja');
barV('cUsia', <?= json_encode($usia) ?>, '#22c55e', 'usia');
</script>
<?= $this->endSection() ?>
