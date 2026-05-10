<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>TNA Assessment — <?= esc($period['nama'] ?? '') ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
body { background:#f8fafc; }
.brand-bar { background:#1e293b; color:#fff; padding:.6rem 1rem; font-size:.85rem; }
.brand-bar strong { font-size:.95rem; }
.q-row { border-bottom:1px solid var(--bs-border-color); padding:.75rem 0; }
.q-row:last-child { border-bottom:none; }
.likert-group { display:flex; gap:.35rem; flex-wrap:wrap; }
.likert-btn input { display:none; }
.likert-btn label {
    display:flex; flex-direction:column; align-items:center; justify-content:center;
    width:52px; height:52px; border-radius:.5rem; cursor:pointer; font-size:.78rem; font-weight:600;
    border:2px solid var(--bs-border-color); color:var(--bs-secondary-color);
    transition:background .15s, border-color .15s, color .15s;
}
.likert-btn label:hover { border-color:var(--bs-primary); color:var(--bs-primary); }
.likert-btn input:checked + label { color:#fff; border-color:transparent; }
.likert-btn.s1 input:checked + label { background:#ef4444; }
.likert-btn.s2 input:checked + label { background:#f97316; }
.likert-btn.s3 input:checked + label { background:#eab308; }
.likert-btn.s4 input:checked + label { background:#22c55e; }
.likert-btn.s5 input:checked + label { background:#6366f1; }
.score-num { font-size:1rem; }
.score-lbl { font-size:.55rem; text-align:center; line-height:1.1; }
.level-desc-grid { display:grid; grid-template-columns:repeat(5,1fr); gap:.35rem; margin-top:.4rem; }
.level-desc-cell { font-size:.65rem; color:var(--bs-secondary-color); text-align:center; line-height:1.2;
    padding:.2rem .1rem; border-radius:.3rem; background:var(--bs-secondary-bg); min-height:2rem; }
.comp-avg { font-size:.72rem; font-weight:600; padding:.15rem .5rem; border-radius:.4rem; background:var(--bs-secondary-bg); }
.sticky-actions { position:sticky; bottom:0; z-index:100;
    background:#fff; border-top:1px solid var(--bs-border-color); padding:.75rem 1rem; }
</style>
</head>
<body>

<div class="brand-bar d-flex align-items-center gap-2">
    <i class="bi bi-building"></i>
    <strong>Mall Intelligence Center</strong>
    <span class="text-white-50 ms-1">· TNA Assessment 360°</span>
</div>

<?php
$typeLabel  = ['self' => 'Self Assessment', 'atasan' => 'Penilaian Atasan', 'rekan' => 'Penilaian Rekan Kerja'];
$submitted  = $assessment['status'] === 'submitted';
$scaleLabel = [1 => 'Tidak pernah', 2 => 'Jarang', 3 => 'Kadang', 4 => 'Sering', 5 => 'Selalu'];
?>

<div class="container py-4" style="max-width:720px">

<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success py-2 mb-3">
    <i class="bi bi-check-circle me-2"></i><?= session()->getFlashdata('success') ?>
</div>
<?php endif; ?>

<!-- Header info -->
<div class="card mb-4">
    <div class="card-body py-3">
        <div class="text-muted small mb-1"><?= esc($period['nama'] ?? '') ?> · <?= esc($typeLabel[$assessment['assessor_type']] ?? '') ?></div>
        <h5 class="fw-bold mb-1">
            Penilaian untuk: <span class="text-primary"><?= esc($employee['nama'] ?? '') ?></span>
        </h5>
        <div class="text-muted small">
            <?= esc($employee['jabatan'] ?? '—') ?>
            <?php if ($assessment['assessor_name']): ?>
            · Dinilai oleh: <strong><?= esc($assessment['assessor_name']) ?></strong>
            <?php endif; ?>
        </div>
        <?php if ($submitted): ?>
        <span class="badge bg-success mt-2">Sudah disubmit <?= $assessment['submitted_at'] ? date('d M Y', strtotime($assessment['submitted_at'])) : '' ?></span>
        <?php else: ?>
        <span class="badge bg-warning text-dark mt-2">Draft — belum disubmit</span>
        <?php endif; ?>
    </div>
</div>

<?php if ($submitted): ?>
<div class="alert alert-success">
    <i class="bi bi-check-circle-fill me-2"></i>
    Penilaian sudah disubmit dan tidak dapat diubah lagi. Terima kasih atas partisipasi Anda.
</div>
<?php else: ?>

<form method="POST" action="<?= base_url('tna/fill/' . $token . '/submit') ?>" id="assessForm">
    <?= csrf_field() ?>
    <input type="hidden" name="action" id="actionField" value="draft">

<?php foreach (['hard' => 'Hard Skill', 'soft' => 'Soft Skill'] as $cat => $catLabel):
    $comps = $grouped[$cat] ?? [];
    if (empty($comps)) continue;
?>
<div class="card mb-4">
    <div class="card-header fw-semibold">
        <i class="bi bi-<?= $cat === 'hard' ? 'gear-fill text-primary' : 'heart-fill text-danger' ?> me-2"></i>
        <?= $catLabel ?>
        <span class="badge bg-secondary ms-1"><?= count($comps) ?></span>
    </div>
    <div class="card-body p-0">
    <?php foreach ($comps as $c):
        $questions = $questionsMap[$c['id']] ?? [];
    ?>
    <div class="px-3 pt-3 pb-1 <?= $c !== end($comps) ? 'border-bottom' : '' ?>">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <div>
                <div class="fw-semibold" style="font-size:.88rem"><?= esc($c['nama']) ?></div>
                <?php if ($c['deskripsi']): ?>
                <div class="text-muted" style="font-size:.74rem"><?= esc($c['deskripsi']) ?></div>
                <?php endif; ?>
            </div>
            <span class="comp-avg ms-3 flex-shrink-0" id="cavg_<?= $c['id'] ?>">Avg: —</span>
        </div>

        <?php if (empty($questions)): ?>
        <div class="text-muted small py-2"><i class="bi bi-exclamation-circle me-1"></i>Belum ada pertanyaan untuk kompetensi ini.</div>
        <?php else: ?>
        <?php foreach ($questions as $qi => $q): ?>
        <div class="q-row">
            <div style="font-size:.83rem" class="mb-2">
                <span class="text-muted me-1"><?= $qi + 1 ?>.</span><?= esc($q['pertanyaan']) ?>
            </div>
            <div class="likert-group mb-1">
                <?php for ($s = 1; $s <= 5; $s++): ?>
                <div class="likert-btn s<?= $s ?>">
                    <input type="radio" name="score[<?= $q['id'] ?>]" id="q<?= $q['id'] ?>_s<?= $s ?>"
                           value="<?= $s ?>"
                           class="q-radio" data-comp="<?= $c['id'] ?>"
                           <?= (($itemMap[$q['id']] ?? null) == $s) ? 'checked' : '' ?>>
                    <label for="q<?= $q['id'] ?>_s<?= $s ?>">
                        <span class="score-num"><?= $s ?></span>
                        <span class="score-lbl"><?= $scaleLabel[$s] ?></span>
                    </label>
                </div>
                <?php endfor; ?>
            </div>
            <?php $hasLevelDesc = array_filter(array_map(fn($l) => $q['level_'.$l] ?? null, range(1,5))); ?>
            <?php if ($hasLevelDesc): ?>
            <div class="level-desc-grid">
                <?php for ($s = 1; $s <= 5; $s++): ?>
                <div class="level-desc-cell">
                    <?= $q['level_'.$s] ? esc($q['level_'.$s]) : '<span class="text-muted">—</span>' ?>
                </div>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>

<div class="sticky-actions d-flex justify-content-between align-items-center gap-2">
    <div class="text-muted small" id="fillStatus"></div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-secondary btn-sm" id="btnDraft">
            <i class="bi bi-floppy me-1"></i>Simpan Draft
        </button>
        <button type="button" class="btn btn-success btn-sm" id="btnSubmit">
            <i class="bi bi-send-check me-1"></i>Submit Final
        </button>
    </div>
</div>

</form>

<div class="card mt-3">
    <div class="card-body py-2 small text-muted">
        <strong>Skala:</strong>
        1 = Tidak pernah &nbsp;·&nbsp; 2 = Jarang &nbsp;·&nbsp; 3 = Kadang-kadang &nbsp;·&nbsp;
        4 = Sering &nbsp;·&nbsp; 5 = Selalu / Sangat kompeten
    </div>
</div>

<?php endif; ?>
</div><!-- /container -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function updateCompAvg(compId) {
    const radios = document.querySelectorAll('.q-radio[data-comp="' + compId + '"]:checked');
    const el = document.getElementById('cavg_' + compId);
    if (!el) return;
    if (radios.length === 0) { el.textContent = 'Avg: —'; return; }
    const sum = [...radios].reduce((t, r) => t + parseInt(r.value), 0);
    el.textContent = 'Avg: ' + (sum / radios.length).toFixed(2);
}

function updateFillStatus() {
    const total    = document.querySelectorAll('.q-radio[value="1"]').length;
    const answered = new Set([...document.querySelectorAll('.q-radio:checked')].map(r => r.name)).size;
    const el = document.getElementById('fillStatus');
    if (el) el.textContent = answered + '/' + total + ' pertanyaan dijawab';
}

document.querySelectorAll('.q-radio').forEach(r => {
    r.addEventListener('change', function() {
        updateCompAvg(this.dataset.comp);
        updateFillStatus();
    });
});

const compIds = new Set([...document.querySelectorAll('.q-radio')].map(r => r.dataset.comp));
compIds.forEach(updateCompAvg);
updateFillStatus();

document.getElementById('btnDraft')?.addEventListener('click', function() {
    document.getElementById('actionField').value = 'draft';
    document.getElementById('assessForm').submit();
});

document.getElementById('btnSubmit')?.addEventListener('click', function() {
    const total    = document.querySelectorAll('.q-radio[value="1"]').length;
    const answered = new Set([...document.querySelectorAll('.q-radio:checked')].map(r => r.name)).size;
    if (answered < total) {
        if (!confirm('Masih ada ' + (total - answered) + ' pertanyaan belum dijawab. Tetap submit?')) return;
    } else {
        if (!confirm('Submit penilaian ini? Setelah disubmit tidak dapat diubah lagi.')) return;
    }
    document.getElementById('actionField').value = 'submit';
    document.getElementById('assessForm').submit();
});
</script>
</body>
</html>
