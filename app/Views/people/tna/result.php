<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<style>
@keyframes fadeUp { from { opacity:0; transform:translateY(14px); } to { opacity:1; transform:none; } }
.anim-fade-up { animation: fadeUp .4s cubic-bezier(.22,.68,0,1.1) both; }
.gap-positive { color: var(--bs-danger); font-weight:600; }
.gap-zero     { color: var(--bs-success); }
.gap-negative { color: var(--bs-info); }
.level-bar { height:6px; border-radius:3px; background:var(--bs-secondary-bg); position:relative; }
.level-bar-fill { height:100%; border-radius:3px; transition:width .4s; }
.kpi-mini { border-radius:.65rem; padding:1rem 1.25rem; }
</style>
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php
$typeLabel = ['self' => 'Self', 'atasan' => 'Atasan', 'rekan' => 'Rekan'];
$allComps  = array_merge($grouped['hard'], $grouped['soft']);

// Compute overall summary stats
$totalGap     = 0;
$gapCount     = 0;
$belowTarget  = 0;
$onTarget     = 0;
$aboveTarget  = 0;

foreach ($allComps as $c) {
    $target  = $targetMap[$c['id']] ?? null;
    $overall = $matrix[$c['id']]['overall'] ?? null;
    if ($target !== null && $overall !== null) {
        $gap = $target - $overall;
        $totalGap += $gap;
        $gapCount++;
        if ($gap > 0) $belowTarget++;
        elseif ($gap < 0) $aboveTarget++;
        else $onTarget++;
    }
}
$avgGap = $gapCount > 0 ? round($totalGap / $gapCount, 2) : null;
$submittedCount = count(array_filter($assessments, fn($a) => $a['status'] === 'submitted'));
?>

<!-- Header -->
<div class="d-flex align-items-center gap-2 mb-3 anim-fade-up" style="animation-delay:.05s">
    <a href="<?= base_url('people/tna/period/' . $period['id']) ?>" class="btn btn-sm btn-light">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div>
        <div class="text-muted small"><?= esc($period['nama']) ?></div>
        <h5 class="fw-bold mb-0">Gap Analysis — <?= esc($employee['nama']) ?></h5>
        <div class="text-muted small"><?= esc($employee['jabatan']) ?> · <?= esc($employee['dept_name']) ?></div>
    </div>
    <button class="btn btn-sm btn-outline-secondary ms-auto" onclick="window.print()">
        <i class="bi bi-printer me-1"></i>Print
    </button>
</div>

<!-- KPI Summary -->
<div class="row g-3 mb-4 anim-fade-up" style="animation-delay:.1s">
    <div class="col-6 col-md-3">
        <div class="kpi-mini card">
            <div class="text-muted small">Avg Gap</div>
            <div class="fw-bold fs-4 <?= $avgGap === null ? '' : ($avgGap > 0 ? 'text-danger' : ($avgGap < 0 ? 'text-info' : 'text-success')) ?>">
                <?= $avgGap !== null ? ($avgGap > 0 ? '+' : '') . $avgGap : '—' ?>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kpi-mini card">
            <div class="text-muted small">Di Bawah Target</div>
            <div class="fw-bold fs-4 text-danger"><?= $belowTarget ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kpi-mini card">
            <div class="text-muted small">Sesuai Target</div>
            <div class="fw-bold fs-4 text-success"><?= $onTarget ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kpi-mini card">
            <div class="text-muted small">Melampaui Target</div>
            <div class="fw-bold fs-4 text-info"><?= $aboveTarget ?></div>
        </div>
    </div>
</div>

<!-- Assessment status -->
<div class="card mb-4 anim-fade-up" style="animation-delay:.15s">
    <div class="card-body py-2">
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <span class="text-muted small fw-semibold">Status Assessment:</span>
            <?php foreach ($assessments as $a): ?>
            <span class="badge <?= $a['status'] === 'submitted' ? 'bg-success' : 'bg-warning text-dark' ?>">
                <?= $typeLabel[$a['assessor_type']] ?>
                <?= $a['assessor_name'] ? '(' . esc($a['assessor_name']) . ')' : '' ?>
                — <?= $a['status'] === 'submitted' ? 'Submitted' : 'Draft' ?>
            </span>
            <?php endforeach; ?>
            <?php if ($submittedCount < count($assessments)): ?>
            <span class="text-muted small"><i class="bi bi-info-circle me-1"></i>Beberapa assessor belum submit. Gap dihitung dari yang sudah submit saja.</span>
            <?php endif; ?>
            <span class="ms-auto text-muted small">
                Bobot: Self <?= (int)($period['weight_self'] ?? 20) ?>% &middot;
                Atasan <?= (int)($period['weight_atasan'] ?? 50) ?>% &middot;
                Rekan <?= (int)($period['weight_rekan'] ?? 30) ?>%
            </span>
        </div>
    </div>
</div>

<!-- Gap Tables -->
<?php foreach (['hard' => 'Hard Skill', 'soft' => 'Soft Skill'] as $cat => $catLabel):
    $comps = $grouped[$cat] ?? [];
    if (empty($comps)) continue;
?>
<div class="card mb-4 anim-fade-up" style="animation-delay:.2s">
    <div class="card-header fw-semibold">
        <i class="bi bi-<?= $cat === 'hard' ? 'gear-fill text-primary' : 'heart-fill text-danger' ?> me-2"></i>
        <?= $catLabel ?>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th style="min-width:160px">Kompetensi</th>
                    <th class="text-center" style="width:70px">Target</th>
                    <th class="text-center" style="width:70px">Self</th>
                    <th class="text-center" style="width:70px">Atasan</th>
                    <th class="text-center" style="width:70px">Rekan</th>
                    <th class="text-center" style="width:80px">Avg</th>
                    <th class="text-center" style="width:80px">Gap</th>
                    <th style="min-width:120px">Status</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($comps as $c):
                $target  = $targetMap[$c['id']] ?? null;
                $self    = $matrix[$c['id']]['self']    ?? null;
                $atasan  = $matrix[$c['id']]['atasan']  ?? null;
                $rekan   = $matrix[$c['id']]['rekan']   ?? null;
                $overall = $matrix[$c['id']]['overall'] ?? null;
                $gap     = ($target !== null && $overall !== null) ? round($target - $overall, 2) : null;

                $gapClass = '';
                $gapLabel = '—';
                if ($gap !== null) {
                    if ($gap > 0)      { $gapClass = 'gap-positive'; $gapLabel = '+' . $gap; }
                    elseif ($gap == 0) { $gapClass = 'gap-zero';     $gapLabel = '0'; }
                    else               { $gapClass = 'gap-negative';  $gapLabel = (string)$gap; }
                }

                $statusLabel = '—';
                $statusBadge = 'bg-secondary';
                if ($gap !== null) {
                    if ($gap > 1)      { $statusLabel = 'Perlu Perhatian'; $statusBadge = 'bg-danger'; }
                    elseif ($gap > 0)  { $statusLabel = 'Di Bawah Target'; $statusBadge = 'bg-warning text-dark'; }
                    elseif ($gap == 0) { $statusLabel = 'Sesuai Target';   $statusBadge = 'bg-success'; }
                    else               { $statusLabel = 'Melampaui';       $statusBadge = 'bg-info'; }
                }
            ?>
            <tr>
                <td>
                    <div class="fw-medium" style="font-size:.85rem"><?= esc($c['nama']) ?></div>
                    <?php if ($target): ?>
                    <div class="level-bar mt-1" style="width:100px">
                        <div class="level-bar-fill bg-primary" style="width:<?= ($overall ?? 0) / 5 * 100 ?>%"></div>
                    </div>
                    <?php endif; ?>
                </td>
                <td class="text-center fw-bold"><?= $target ?? '—' ?></td>
                <td class="text-center text-muted"><?= $self !== null ? $self : '—' ?></td>
                <td class="text-center text-muted"><?= $atasan !== null ? $atasan : '—' ?></td>
                <td class="text-center text-muted"><?= $rekan !== null ? $rekan : '—' ?></td>
                <td class="text-center fw-semibold"><?= $overall !== null ? $overall : '—' ?></td>
                <td class="text-center <?= $gapClass ?>"><?= $gapLabel ?></td>
                <td><span class="badge <?= $statusBadge ?>" style="font-size:.68rem"><?= $statusLabel ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endforeach; ?>

<!-- Legend -->
<div class="card anim-fade-up" style="animation-delay:.25s">
    <div class="card-body py-2">
        <div class="d-flex flex-wrap gap-3 align-items-center small text-muted">
            <strong>Keterangan gap:</strong>
            <span><span class="gap-positive">+N</span> = N level di bawah target</span>
            <span><span class="gap-zero">0</span> = Sesuai target</span>
            <span><span class="gap-negative">-N</span> = N level melampaui target</span>
            <span class="ms-auto"><em>Avg = rata-rata tertimbang semua assessor yang sudah submit</em></span>
        </div>
    </div>
</div>

<?php
// Build flat list of all competencies for name lookup
$allCompsFlat = [];
foreach ($grouped as $cat => $comps) {
    foreach ($comps as $c) $allCompsFlat[$c['id']] = $c;
}
?>

<?php if (! empty($belowTargetIds)): ?>
<div class="card mt-4 anim-fade-up" style="animation-delay:.3s">
    <div class="card-header fw-semibold d-flex align-items-center gap-2">
        <i class="bi bi-mortarboard-fill text-primary"></i>
        Rekomendasi Training
        <span class="badge bg-primary ms-1"><?= count($belowTargetIds) ?> kompetensi perlu dikembangkan</span>
    </div>
    <div class="card-body p-0">
    <?php foreach ($belowTargetIds as $cid):
        $comp = $allCompsFlat[$cid] ?? null;
        if (! $comp) continue;
        $programs = $trainingRecommendations[$cid] ?? [];
        $gap = isset($matrix[$cid]['overall'], $targetMap[$cid])
            ? round($targetMap[$cid] - $matrix[$cid]['overall'], 2) : null;
    ?>
    <div class="px-3 py-3 border-bottom">
        <div class="d-flex align-items-center gap-2 mb-2">
            <span class="badge <?= $comp['kategori'] === 'hard' ? 'bg-primary' : 'bg-success' ?>" style="font-size:.65rem">
                <?= ucfirst($comp['kategori']) ?>
            </span>
            <span class="fw-semibold small"><?= esc($comp['nama']) ?></span>
            <?php if ($gap !== null): ?>
            <span class="badge bg-warning text-dark ms-1" style="font-size:.65rem">Gap +<?= $gap ?></span>
            <?php endif; ?>
        </div>
        <?php if (empty($programs)): ?>
        <div class="text-muted small">
            <i class="bi bi-info-circle me-1"></i>
            Belum ada program training yang terjadwal untuk kompetensi ini.
        </div>
        <?php else: ?>
        <div class="d-flex flex-wrap gap-2">
            <?php foreach ($programs as $p): ?>
            <div class="border rounded px-3 py-2" style="font-size:.8rem;min-width:200px">
                <div class="fw-semibold"><?= esc($p['nama']) ?></div>
                <div class="text-muted" style="font-size:.72rem">
                    <?= esc($p['tipe']) ?>
                    <?php if ($p['vendor']): ?> · <?= esc($p['vendor']) ?><?php endif; ?>
                </div>
                <?php if ($p['tanggal_mulai']): ?>
                <div class="text-muted mt-1" style="font-size:.72rem">
                    <i class="bi bi-calendar3 me-1"></i>
                    <?= date('d M Y', strtotime($p['tanggal_mulai'])) ?>
                </div>
                <?php endif; ?>
                <span class="badge <?= $p['status'] === 'upcoming' ? 'bg-info text-dark' : 'bg-success' ?> mt-1" style="font-size:.6rem">
                    <?= $p['status'] === 'upcoming' ? 'Upcoming' : 'Ongoing' ?>
                </span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?= $this->endSection() ?>
