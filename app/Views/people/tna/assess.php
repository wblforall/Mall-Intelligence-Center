<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<style>
@keyframes fadeUp { from { opacity:0; transform:translateY(14px); } to { opacity:1; transform:none; } }
.anim-fade-up { animation: fadeUp .4s cubic-bezier(.22,.68,0,1.1) both; }
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
.level-desc-cell .ld-num { font-weight:700; font-size:.7rem; }
.comp-avg { font-size:.72rem; font-weight:600; padding:.15rem .5rem; border-radius:.4rem; background:var(--bs-secondary-bg); }
.sticky-actions { position:sticky; bottom:0; z-index:100;
    background:var(--bs-body-bg); border-top:1px solid var(--bs-border-color); padding:.75rem 1rem; }
</style>
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php
$typeLabel  = ['self' => 'Self', 'atasan' => 'Atasan', 'rekan' => 'Rekan'];
$submitted  = $assessment['status'] === 'submitted';
$scaleLabel = [1 => 'Tidak pernah', 2 => 'Jarang', 3 => 'Kadang', 4 => 'Sering', 5 => 'Selalu'];
?>

<!-- Header -->
<div class="d-flex align-items-center gap-2 mb-3 anim-fade-up" style="animation-delay:.05s">
    <a href="<?= base_url('people/tna/period/' . $assessment['period_id']) ?>" class="btn btn-sm btn-light">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div>
        <div class="text-muted small"><?= esc($period['nama']) ?></div>
        <h5 class="fw-bold mb-0">
            Penilaian <?= $typeLabel[$assessment['assessor_type']] ?>
            <?php if ($assessment['assessor_name']): ?>— <?= esc($assessment['assessor_name']) ?><?php endif; ?>
        </h5>
        <div class="text-muted small">untuk <strong><?= esc($employee['nama']) ?></strong> · <?= esc($employee['jabatan'] ?? '—') ?></div>
    </div>
    <?php if ($submitted): ?>
    <span class="badge bg-success ms-2">Submitted <?= $assessment['submitted_at'] ? date('d M Y', strtotime($assessment['submitted_at'])) : '' ?></span>
    <?php else: ?>
    <span class="badge bg-warning text-dark ms-2">Draft</span>
    <?php endif; ?>
</div>

<?php if ($submitted): ?>
<div class="alert alert-success py-2 mb-3">
    <i class="bi bi-check-circle me-2"></i>Assessment sudah disubmit dan tidak dapat diubah.
</div>
<?php endif; ?>

<form method="POST" action="<?= base_url('people/tna/assess/' . $assessment['id'] . '/submit') ?>" id="assessForm">
    <?= csrf_field() ?>
    <input type="hidden" name="action" id="actionField" value="draft">

<?php foreach (['hard' => 'Hard Skill', 'soft' => 'Soft Skill'] as $cat => $catLabel):
    $comps = $grouped[$cat] ?? [];
    if (empty($comps)) continue;
?>
<div class="card mb-4 anim-fade-up" style="animation-delay:.1s">
    <div class="card-header fw-semibold">
        <i class="bi bi-<?= $cat === 'hard' ? 'gear-fill text-primary' : 'heart-fill text-danger' ?> me-2"></i>
        <?= $catLabel ?>
        <span class="badge bg-secondary ms-1"><?= count($comps) ?></span>
    </div>
    <div class="card-body p-0">
    <?php foreach ($comps as $c):
        $questions = $questionsMap[$c['id']] ?? [];
    ?>
    <div class="px-3 pt-3 pb-1 <?= $c !== end($comps) ? 'border-bottom' : '' ?>" data-comp="<?= $c['id'] ?>">
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
        <div class="text-muted small py-2">
            <i class="bi bi-exclamation-circle me-1"></i>Belum ada pertanyaan.
            <a href="<?= base_url('people/competencies/'.$c['id'].'/questions') ?>">Tambah pertanyaan</a>.
        </div>
        <?php else: ?>
        <?php foreach ($questions as $qi => $q): ?>
        <div class="q-row" data-qcomp="<?= $c['id'] ?>">
            <div style="font-size:.83rem" class="mb-2">
                <span class="text-muted me-1"><?= $qi + 1 ?>.</span><?= esc($q['pertanyaan']) ?>
            </div>
            <div class="likert-group mb-1">
                <?php for ($s = 1; $s <= 5; $s++): ?>
                <div class="likert-btn s<?= $s ?>">
                    <input type="radio" name="score[<?= $q['id'] ?>]" id="q<?= $q['id'] ?>_s<?= $s ?>"
                           value="<?= $s ?>"
                           class="q-radio" data-comp="<?= $c['id'] ?>"
                           <?= (($itemMap[$q['id']] ?? null) == $s) ? 'checked' : '' ?>
                           <?= $submitted ? 'disabled' : '' ?>>
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

<?php if (! $submitted): ?>
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
<?php endif; ?>

</form>

<div class="card mt-3 anim-fade-up" style="animation-delay:.15s">
    <div class="card-body py-2 small text-muted">
        <strong>Skala:</strong>
        1 = Tidak pernah &nbsp;·&nbsp;
        2 = Jarang &nbsp;·&nbsp;
        3 = Kadang-kadang &nbsp;·&nbsp;
        4 = Sering &nbsp;·&nbsp;
        5 = Selalu / Sangat kompeten
    </div>
</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
function updateCompAvg(compId) {
    const radios = document.querySelectorAll('.q-radio[data-comp="' + compId + '"]:checked');
    const total  = document.querySelectorAll('.q-radio[data-comp="' + compId + '"]').length / 5;
    const el     = document.getElementById('cavg_' + compId);
    if (! el) return;
    if (radios.length === 0) { el.textContent = 'Avg: —'; return; }
    const sum = [...radios].reduce((t, r) => t + parseInt(r.value), 0);
    el.textContent = 'Avg: ' + (sum / radios.length).toFixed(2);
}

function updateFillStatus() {
    const total   = document.querySelectorAll('.q-radio[value="1"]').length;
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

// Init on load
const compIds = new Set([...document.querySelectorAll('.q-radio')].map(r => r.dataset.comp));
compIds.forEach(updateCompAvg);
updateFillStatus();

const btnDraft = document.getElementById('btnDraft');
if (btnDraft) {
    btnDraft.addEventListener('click', function() {
        document.getElementById('actionField').value = 'draft';
        document.getElementById('assessForm').submit();
    });
}

const btnSubmit = document.getElementById('btnSubmit');
if (btnSubmit) {
    btnSubmit.addEventListener('click', function() {
        const total    = document.querySelectorAll('.q-radio[value="1"]').length;
        const answered = new Set([...document.querySelectorAll('.q-radio:checked')].map(r => r.name)).size;
        if (answered < total) {
            if (! confirm('Masih ada ' + (total - answered) + ' pertanyaan belum dijawab. Tetap submit?')) return;
        } else {
            if (! confirm('Submit assessment ini? Setelah disubmit tidak dapat diubah lagi.')) return;
        }
        document.getElementById('actionField').value = 'submit';
        document.getElementById('assessForm').submit();
    });
}
</script>
<?= $this->endSection() ?>
