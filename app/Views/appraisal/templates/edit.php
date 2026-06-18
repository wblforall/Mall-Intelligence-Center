<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
$badge = ['draft' => ['secondary', 'Draft'], 'submitted' => ['warning', 'Diajukan ke HR'], 'approved' => ['success', 'Disetujui']];
[$bc, $bl] = $badge[$tpl['status']] ?? ['secondary', $tpl['status']];
$canEdit = ! $locked && in_array($tpl['status'], ['draft']) ;
?>
<div class="d-flex align-items-center gap-2 mb-3">
    <a href="<?= base_url('appraisal/templates') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0"><?= esc($jab['nama'] ?? 'Template') ?> <span class="badge bg-<?= $bc ?> align-middle ms-1"><?= $bl ?></span></h4>
        <small class="text-muted"><?= esc($jab['dept_name'] ?? '-') ?> · Bobot final: KPI <?= (int)($tpl['bobot_kpi']*100) ?>% + Kompetensi <?= (int)($tpl['bobot_kompetensi']*100) ?>%</small>
    </div>
</div>

<?php if (session('error')): ?><div class="alert alert-danger py-2 small"><?= esc(session('error')) ?></div><?php endif; ?>
<?php if (session('success')): ?><div class="alert alert-success py-2 small"><?= esc(session('success')) ?></div><?php endif; ?>
<?php if (! empty($tpl['catatan_hr']) && $tpl['status'] === 'draft'): ?>
<div class="alert alert-warning py-2 small"><i class="bi bi-arrow-return-left me-1"></i><b>Catatan HR:</b> <?= esc($tpl['catatan_hr']) ?></div>
<?php endif; ?>
<?php if (! $canEdit): ?>
<div class="alert alert-light border py-2 small"><i class="bi bi-lock me-1"></i>Template terkunci (status: <?= $bl ?>). <?= $tpl['status']==='submitted' ? 'Menunggu keputusan HR.' : ($isHr ? 'Klik "Kelola" lagi untuk membuka kembali sebagai draft.' : 'Hanya bisa dilihat.') ?></div>
<?php endif; ?>

<!-- ── KPI ─────────────────────────────────────────────────────────── -->
<form method="POST" action="<?= base_url('appraisal/templates/' . $tpl['id'] . '/kpi/save') ?>" id="kpiForm">
<?= csrf_field() ?>
<div class="card mb-3">
<div class="card-header d-flex justify-content-between align-items-center py-2">
    <h6 class="mb-0 fw-semibold"><i class="bi bi-bullseye me-1"></i>Key Performance Indicators</h6>
    <span class="small">Total bobot: <b id="totBobot" class="<?= abs($totalBobot-100)<0.01?'text-success':'text-danger' ?>"><?= rtrim(rtrim(number_format($totalBobot,2),'0'),'.') ?></b>/100</span>
</div>
<div class="card-body p-0">
<div class="table-responsive">
<table class="table table-sm align-middle mb-0" id="kpiTable" style="min-width:760px">
<thead class="small text-muted">
<tr>
    <th style="width:170px" class="ps-3">Area Kinerja</th>
    <th style="min-width:280px">Indikator (KPI)</th>
    <th style="width:120px">Unit</th>
    <th style="width:80px" class="text-center">Bobot</th>
    <th style="width:90px" class="text-center">Target</th>
    <?php if ($canEdit): ?><th style="width:40px"></th><?php endif; ?>
</tr>
</thead>
<tbody id="kpiBody">
<?php foreach ($kpis as $i => $k): ?>
<tr class="kpi-row">
    <td class="ps-3">
        <select name="kpi[<?= $i ?>][area]" class="form-select form-select-sm" <?= $canEdit?'':'disabled' ?>>
            <?php foreach ($areas as $slug => $label): ?>
            <option value="<?= $slug ?>" <?= $k['area']===$slug?'selected':'' ?>><?= esc($label) ?></option>
            <?php endforeach; ?>
        </select>
    </td>
    <td><textarea name="kpi[<?= $i ?>][indikator]" class="form-control form-control-sm" rows="1" <?= $canEdit?'':'readonly' ?>><?= esc($k['indikator']) ?></textarea></td>
    <td>
        <select name="kpi[<?= $i ?>][unit]" class="form-select form-select-sm" <?= $canEdit?'':'disabled' ?>>
            <?php foreach ($units as $slug => $label): ?>
            <option value="<?= $slug ?>" <?= $k['unit']===$slug?'selected':'' ?>><?= esc($label) ?></option>
            <?php endforeach; ?>
        </select>
    </td>
    <td><input type="number" step="0.01" min="0" name="kpi[<?= $i ?>][bobot]" class="form-control form-control-sm text-center kpi-bobot" value="<?= rtrim(rtrim(number_format($k['bobot'],2),'0'),'.') ?>" <?= $canEdit?'':'readonly' ?>></td>
    <td><input type="number" step="0.01" name="kpi[<?= $i ?>][target]" class="form-control form-control-sm text-center" value="<?= $k['target']!==null?rtrim(rtrim(number_format($k['target'],2),'0'),'.'):'' ?>" <?= $canEdit?'':'readonly' ?>></td>
    <?php if ($canEdit): ?><td class="text-center"><button type="button" class="btn btn-sm btn-link text-danger p-0 del-row"><i class="bi bi-x-lg"></i></button></td><?php endif; ?>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
<?php if ($canEdit): ?>
<div class="card-footer d-flex justify-content-between py-2">
    <button type="button" class="btn btn-sm btn-outline-secondary" id="addKpi"><i class="bi bi-plus-lg me-1"></i>Tambah baris</button>
    <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-save me-1"></i>Simpan KPI</button>
</div>
<?php endif; ?>
</div>
</form>

<!-- ── Kompetensi ──────────────────────────────────────────────────── -->
<form method="POST" action="<?= base_url('appraisal/templates/' . $tpl['id'] . '/competency/save') ?>">
<?= csrf_field() ?>
<div class="card mb-3">
<div class="card-header py-2"><h6 class="mb-0 fw-semibold"><i class="bi bi-people me-1"></i>Aspek Kompetensi (dinilai 1–5)</h6></div>
<div class="card-body">
<div id="compBody">
<?php foreach ($comps as $i => $c): ?>
<div class="comp-row border rounded p-2 mb-2">
    <div class="d-flex gap-2">
        <input type="text" name="comp[<?= $i ?>][nama_aspek]" class="form-control form-control-sm fw-semibold" value="<?= esc($c['nama_aspek']) ?>" placeholder="Nama aspek" <?= $canEdit?'':'readonly' ?>>
        <?php if ($canEdit): ?><button type="button" class="btn btn-sm btn-link text-danger del-comp"><i class="bi bi-x-lg"></i></button><?php endif; ?>
    </div>
    <textarea name="comp[<?= $i ?>][deskripsi]" class="form-control form-control-sm mt-1" rows="2" placeholder="Deskripsi (opsional)" <?= $canEdit?'':'readonly' ?>><?= esc($c['deskripsi']) ?></textarea>
</div>
<?php endforeach; ?>
</div>
</div>
<?php if ($canEdit): ?>
<div class="card-footer d-flex justify-content-between py-2">
    <button type="button" class="btn btn-sm btn-outline-secondary" id="addComp"><i class="bi bi-plus-lg me-1"></i>Tambah aspek</button>
    <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-save me-1"></i>Simpan Kompetensi</button>
</div>
<?php endif; ?>
</div>
</form>

<!-- ── Aksi status ─────────────────────────────────────────────────── -->
<div class="card mb-4">
<div class="card-body d-flex flex-wrap gap-2 align-items-center">
    <?php if ($tpl['status'] === 'draft'): ?>
        <form method="POST" action="<?= base_url('appraisal/templates/' . $tpl['id'] . '/submit') ?>" onsubmit="return confirm('Ajukan template ini ke HR?')">
            <?= csrf_field() ?>
            <button class="btn btn-sm btn-warning"><i class="bi bi-send me-1"></i>Ajukan ke HR</button>
        </form>
        <form method="POST" action="<?= base_url('appraisal/templates/' . $tpl['id'] . '/delete') ?>" onsubmit="return confirm('Hapus template ini?')">
            <?= csrf_field() ?>
            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash me-1"></i>Hapus</button>
        </form>
    <?php elseif ($tpl['status'] === 'submitted' && $isHr): ?>
        <form method="POST" action="<?= base_url('appraisal/templates/' . $tpl['id'] . '/approve') ?>" onsubmit="return confirm('Setujui template ini?')">
            <?= csrf_field() ?>
            <button class="btn btn-sm btn-success"><i class="bi bi-check-lg me-1"></i>Setujui</button>
        </form>
        <form method="POST" action="<?= base_url('appraisal/templates/' . $tpl['id'] . '/reject') ?>" class="d-flex gap-2">
            <?= csrf_field() ?>
            <input type="text" name="catatan_hr" class="form-control form-control-sm" placeholder="Catatan revisi (opsional)" style="width:260px">
            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-arrow-return-left me-1"></i>Kembalikan</button>
        </form>
    <?php elseif ($tpl['status'] === 'approved' && $isHr): ?>
        <a href="<?= base_url('appraisal/templates/' . $tpl['id'] . '/kpi/save') ?>" class="text-muted small" onclick="event.preventDefault();document.getElementById('reopenForm').submit();"><i class="bi bi-unlock me-1"></i>Buka kembali untuk revisi</a>
        <form id="reopenForm" method="POST" action="<?= base_url('appraisal/templates/' . $tpl['id'] . '/kpi/save') ?>" class="d-none">
            <?= csrf_field() ?>
            <?php foreach ($kpis as $i => $k): ?>
            <input type="hidden" name="kpi[<?= $i ?>][area]" value="<?= esc($k['area']) ?>">
            <input type="hidden" name="kpi[<?= $i ?>][indikator]" value="<?= esc($k['indikator']) ?>">
            <input type="hidden" name="kpi[<?= $i ?>][unit]" value="<?= esc($k['unit']) ?>">
            <input type="hidden" name="kpi[<?= $i ?>][bobot]" value="<?= esc($k['bobot']) ?>">
            <input type="hidden" name="kpi[<?= $i ?>][target]" value="<?= esc($k['target']) ?>">
            <?php endforeach; ?>
        </form>
    <?php else: ?>
        <span class="text-muted small">Status: <?= $bl ?>.</span>
    <?php endif; ?>
</div>
</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
// Auto-tinggi textarea (indikator/deskripsi) agar teks panjang terbaca penuh
function autoGrow(t) { t.style.height = 'auto'; t.style.height = (t.scrollHeight + 2) + 'px'; }
document.querySelectorAll('#kpiTable textarea, #compBody textarea').forEach(autoGrow);
document.addEventListener('input', e => {
    if (e.target.matches('#kpiTable textarea, #compBody textarea')) autoGrow(e.target);
});
</script>
<?php if ($canEdit): ?>
<script>
const areas = <?= json_encode($areas) ?>;
const units = <?= json_encode($units) ?>;
let kpiIdx = <?= count($kpis) ?>, compIdx = <?= count($comps) ?>;

function recomputeBobot() {
    let t = 0;
    document.querySelectorAll('.kpi-bobot').forEach(i => t += parseFloat(i.value) || 0);
    const el = document.getElementById('totBobot');
    el.textContent = (Math.round(t * 100) / 100).toString();
    el.className = Math.abs(t - 100) < 0.01 ? 'text-success' : 'text-danger';
}

function areaOptions(sel) { return Object.entries(areas).map(([s,l]) => `<option value="${s}">${l}</option>`).join(''); }
function unitOptions() { return Object.entries(units).map(([s,l]) => `<option value="${s}">${l}</option>`).join(''); }

document.getElementById('addKpi').addEventListener('click', () => {
    const i = kpiIdx++;
    const tr = document.createElement('tr');
    tr.className = 'kpi-row';
    tr.innerHTML = `
        <td class="ps-3"><select name="kpi[${i}][area]" class="form-select form-select-sm">${areaOptions()}</select></td>
        <td><textarea name="kpi[${i}][indikator]" class="form-control form-control-sm" rows="1"></textarea></td>
        <td><select name="kpi[${i}][unit]" class="form-select form-select-sm">${unitOptions()}</select></td>
        <td><input type="number" step="0.01" min="0" name="kpi[${i}][bobot]" class="form-control form-control-sm text-center kpi-bobot" value="0"></td>
        <td><input type="number" step="0.01" name="kpi[${i}][target]" class="form-control form-control-sm text-center"></td>
        <td class="text-center"><button type="button" class="btn btn-sm btn-link text-danger p-0 del-row"><i class="bi bi-x-lg"></i></button></td>`;
    document.getElementById('kpiBody').appendChild(tr);
    tr.querySelector('.kpi-bobot').addEventListener('input', recomputeBobot);
    tr.querySelector('.del-row').addEventListener('click', () => { tr.remove(); recomputeBobot(); });
});

document.querySelectorAll('.kpi-bobot').forEach(i => i.addEventListener('input', recomputeBobot));
document.querySelectorAll('.del-row').forEach(b => b.addEventListener('click', e => { e.target.closest('tr').remove(); recomputeBobot(); }));

document.getElementById('addComp').addEventListener('click', () => {
    const i = compIdx++;
    const div = document.createElement('div');
    div.className = 'comp-row border rounded p-2 mb-2';
    div.innerHTML = `
        <div class="d-flex gap-2">
            <input type="text" name="comp[${i}][nama_aspek]" class="form-control form-control-sm fw-semibold" placeholder="Nama aspek">
            <button type="button" class="btn btn-sm btn-link text-danger del-comp"><i class="bi bi-x-lg"></i></button>
        </div>
        <textarea name="comp[${i}][deskripsi]" class="form-control form-control-sm mt-1" rows="2" placeholder="Deskripsi (opsional)"></textarea>`;
    document.getElementById('compBody').appendChild(div);
    div.querySelector('.del-comp').addEventListener('click', () => div.remove());
});
document.querySelectorAll('.del-comp').forEach(b => b.addEventListener('click', e => e.target.closest('.comp-row').remove()));
</script>
<?php endif; ?>
<?= $this->endSection() ?>
