<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<style>
.eei-gauge { width:140px; height:140px; border-radius:50%;
    background:conic-gradient(var(--gauge-color) calc(var(--pct) * 1%), #e9ecef 0);
    display:flex; align-items:center; justify-content:center; position:relative; }
.eei-gauge::before { content:''; position:absolute; width:100px; height:100px;
    border-radius:50%; background:var(--bs-body-bg); }
.eei-gauge-inner { position:relative; z-index:1; text-align:center; }
.dim-bar { height:10px; border-radius:5px; transition:width .6s ease; }
.score-chip { min-width:52px; }
</style>
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-heart-pulse-fill me-2 text-danger"></i>Employee Engagement Index</h4>
        <small class="text-muted">Hasil survey — agregat anonim per departemen</small>
    </div>
    <a href="<?= base_url('people/eei/manage') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-gear me-1"></i>Kelola Survey
    </a>
</div>

<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success py-2"><?= session()->getFlashdata('success') ?></div>
<?php endif; ?>

<!-- Period selector -->
<?php if (! empty($periods)): ?>
<div class="card mb-4">
<div class="card-body py-2">
<form method="GET" class="d-flex align-items-center gap-2">
    <i class="bi bi-calendar2-range text-muted"></i>
    <select name="period_id" class="form-select form-select-sm" style="max-width:240px"
            onchange="this.form.submit()">
        <?php foreach ($periods as $p): ?>
        <option value="<?= $p['id'] ?>" <?= $periodId == $p['id'] ? 'selected' : '' ?>>
            <?= esc($p['nama']) ?>
            <?= $p['is_active'] ? ' ● Aktif' : '' ?>
            (<?= date('d M Y', strtotime($p['start_date'])) ?> – <?= date('d M Y', strtotime($p['end_date'])) ?>)
        </option>
        <?php endforeach; ?>
    </select>
</form>
</div>
</div>
<?php endif; ?>

<?php if (! $periodId || empty($dimScores)): ?>
<div class="card"><div class="card-body text-center py-5 text-muted">
    <i class="bi bi-bar-chart-line display-4 d-block mb-3 opacity-25"></i>
    <p class="mb-1 fw-semibold">Belum ada data untuk ditampilkan.</p>
    <p class="small mb-3">Buat periode survey dan pastikan karyawan sudah mengisi.</p>
    <a href="<?= base_url('people/eei/manage') ?>" class="btn btn-sm btn-primary">Kelola Survey</a>
</div></div>
<?php else: ?>

<?php
$scoreColor = fn($s) => $s >= 75 ? '#10b981' : ($s >= 55 ? '#f59e0b' : '#ef4444');
$scoreLabel = fn($s) => $s >= 75 ? 'Engaged' : ($s >= 55 ? 'Moderate' : 'Disengaged');
$scoreBadge = fn($s) => $s >= 75 ? 'success' : ($s >= 55 ? 'warning' : 'danger');
$color = $scoreColor($overall);
?>

<!-- Summary row -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card h-100">
        <div class="card-body d-flex flex-column align-items-center justify-content-center py-4">
            <div class="eei-gauge mb-3"
                 style="--pct:<?= min($overall, 100) ?>;--gauge-color:<?= $color ?>">
                <div class="eei-gauge-inner">
                    <div style="font-size:1.6rem;font-weight:800;color:<?= $color ?>"><?= $overall ?></div>
                    <div style="font-size:.65rem;color:#6c757d">/ 100</div>
                </div>
            </div>
            <div class="fw-semibold">Overall EEI</div>
            <span class="badge bg-<?= $scoreBadge($overall) ?> mt-1"><?= $scoreLabel($overall) ?></span>
        </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
        <div class="card-body text-center py-4">
            <div class="display-5 fw-bold text-primary"><?= $participation['completed'] ?></div>
            <div class="text-muted small">dari <?= $participation['total'] ?> karyawan aktif</div>
            <div class="fw-semibold mt-2">Partisipasi</div>
            <div class="progress mt-2" style="height:6px">
                <div class="progress-bar bg-primary" style="width:<?= $participation['percentage'] ?>%"></div>
            </div>
            <div class="small text-muted mt-1"><?= $participation['percentage'] ?>%</div>
        </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100">
        <div class="card-body">
            <div class="fw-semibold mb-3">Skor per Dimensi</div>
            <?php foreach ($dimScores as $dim): ?>
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="small text-nowrap" style="min-width:160px;max-width:160px;overflow:hidden;text-overflow:ellipsis"
                      title="<?= esc($dim['nama']) ?>"><?= esc($dim['nama']) ?></span>
                <div class="flex-grow-1">
                    <div class="dim-bar" style="width:<?= $dim['score'] ?>%;background:<?= $scoreColor($dim['score']) ?>"></div>
                </div>
                <span class="score-chip badge bg-secondary-subtle text-secondary border fw-semibold text-end"><?= $dim['score'] ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        </div>
    </div>
</div>

<!-- Dept breakdown -->
<?php if (! empty($deptScores)): ?>
<div class="card mb-4">
<div class="card-header fw-semibold py-2">Skor per Departemen</div>
<div class="card-body p-0">
<table class="table table-sm align-middle mb-0">
<thead class="table-light">
<tr>
    <th class="ps-3">Departemen</th>
    <th class="text-center" style="width:200px">Skor EEI</th>
    <th class="text-center" style="width:100px">Status</th>
</tr>
</thead>
<tbody>
<?php foreach ($deptScores as $ds): ?>
<tr>
    <td class="ps-3 fw-semibold small"><?= esc($ds['name']) ?></td>
    <td class="text-center">
        <div class="d-flex align-items-center gap-2 justify-content-center">
            <div class="flex-grow-1" style="max-width:120px">
                <div class="dim-bar" style="width:<?= $ds['score'] ?>%;background:<?= $scoreColor($ds['score']) ?>"></div>
            </div>
            <span class="fw-bold" style="color:<?= $scoreColor($ds['score']) ?>;min-width:36px"><?= $ds['score'] ?></span>
        </div>
    </td>
    <td class="text-center">
        <span class="badge bg-<?= $scoreBadge($ds['score']) ?>-subtle text-<?= $scoreBadge($ds['score']) ?> border">
            <?= $scoreLabel($ds['score']) ?>
        </span>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>

<!-- Level jabatan breakdown -->
<?php if (! empty($levelScores)): ?>
<div class="card mb-4">
<div class="card-header fw-semibold py-2">Skor per Level Jabatan</div>
<div class="card-body p-0">
<table class="table table-sm align-middle mb-0">
<thead class="table-light">
<tr>
    <th class="ps-3">Level Jabatan</th>
    <th class="text-center" style="width:200px">Skor EEI</th>
    <th class="text-center" style="width:100px">Status</th>
</tr>
</thead>
<tbody>
<?php foreach ($levelScores as $ls): ?>
<tr>
    <td class="ps-3 fw-semibold small"><?= esc($ls['jabatan_level']) ?></td>
    <td class="text-center">
        <div class="d-flex align-items-center gap-2 justify-content-center">
            <div class="flex-grow-1" style="max-width:120px">
                <div class="dim-bar" style="width:<?= $ls['score'] ?>%;background:<?= $scoreColor($ls['score']) ?>"></div>
            </div>
            <span class="fw-bold" style="color:<?= $scoreColor($ls['score']) ?>;min-width:36px"><?= $ls['score'] ?></span>
        </div>
    </td>
    <td class="text-center">
        <span class="badge bg-<?= $scoreBadge($ls['score']) ?>-subtle text-<?= $scoreBadge($ls['score']) ?> border">
            <?= $scoreLabel($ls['score']) ?>
        </span>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
<?php endif; ?>

<!-- Cross-tab: Dept × Level -->
<?php if (! empty($deptLevelScores)): ?>
<?php
// Build pivot: dept_name → [jabatan_level → score]
$levels = ['Staff','Supervisor','Asst. Manager','Manager','Senior Manager','General Manager','Director','C-Level / VP'];
$pivot  = [];
foreach ($deptLevelScores as $r) {
    $pivot[$r['dept_name']][$r['jabatan_level']] = $r['score'];
}
// Only include levels that appear in the data
$usedLevels = [];
foreach ($levels as $lv) {
    foreach ($pivot as $row) {
        if (isset($row[$lv])) { $usedLevels[] = $lv; break; }
    }
}
?>
<div class="card mb-4">
<div class="card-header fw-semibold py-2 d-flex align-items-center justify-content-between">
    <span>Matriks Skor: Departemen × Level Jabatan</span>
    <span class="text-muted small fw-normal">Sel kosong = tidak ada responden</span>
</div>
<div class="card-body p-0" style="overflow-x:auto">
<table class="table table-sm table-bordered align-middle mb-0" style="min-width:600px;font-size:.8rem">
<thead class="table-light">
<tr>
    <th class="ps-3" style="min-width:140px">Departemen</th>
    <?php foreach ($usedLevels as $lv): ?>
    <th class="text-center" style="min-width:80px"><?= esc($lv) ?></th>
    <?php endforeach; ?>
</tr>
</thead>
<tbody>
<?php foreach ($pivot as $deptName => $lvScores): ?>
<tr>
    <td class="ps-3 fw-semibold"><?= esc($deptName) ?></td>
    <?php foreach ($usedLevels as $lv): ?>
    <?php if (isset($lvScores[$lv])): $s = $lvScores[$lv]; ?>
    <td class="text-center fw-semibold" style="color:<?= $scoreColor($s) ?>;background:<?= $scoreColor($s) ?>18">
        <?= $s ?>
    </td>
    <?php else: ?>
    <td class="text-center text-muted" style="background:#f8f9fa">—</td>
    <?php endif; ?>
    <?php endforeach; ?>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
<?php endif; ?>

<!-- Legend -->
<div class="d-flex gap-3 text-muted small mb-4">
    <span><span class="badge bg-success me-1">Engaged</span>≥ 75</span>
    <span><span class="badge bg-warning me-1">Moderate</span>55–74</span>
    <span><span class="badge bg-danger me-1">Disengaged</span>&lt; 55</span>
    <span class="ms-auto fst-italic">Skor 0–100, dihitung dari rata-rata Likert 1–5 yang dinormalisasi.</span>
</div>
<?php endif; ?>

<?php endif; ?>

<?= $this->endSection() ?>
