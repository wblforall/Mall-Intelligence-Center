<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<style>
.likert-option { cursor:pointer; }
.likert-option input:checked + .likert-label { background:var(--bs-primary); color:#fff; border-color:var(--bs-primary); }
.likert-label { display:flex; flex-direction:column; align-items:center; justify-content:center;
    border:1px solid #dee2e6; border-radius:.5rem; padding:.5rem .25rem;
    min-width:60px; font-size:.7rem; text-align:center; transition:all .15s; user-select:none; }
.likert-label:hover { border-color:var(--bs-primary); background:var(--bs-primary-bg-subtle); }
.likert-label .score-num { font-size:1.1rem; font-weight:700; margin-bottom:2px; }
.dim-card { border-left:3px solid var(--bs-primary); }
</style>
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-heart-pulse-fill me-2 text-danger"></i>Employee Engagement Survey</h4>
        <small class="text-muted">Jawaban Anda bersifat anonim — tidak dapat dilacak ke individu</small>
    </div>
    <?php if ($period): ?>
    <span class="ms-auto badge bg-success-subtle text-success border border-success-subtle px-3 py-2">
        <i class="bi bi-calendar-check me-1"></i><?= esc($period['nama']) ?>
    </span>
    <?php endif; ?>
</div>

<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
<div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
<?php endif; ?>

<?php if (! $period): ?>
<div class="card"><div class="card-body text-center py-5 text-muted">
    <i class="bi bi-calendar-x display-4 d-block mb-3 opacity-25"></i>
    <p class="fw-semibold mb-1">Tidak ada survey yang sedang berjalan.</p>
    <p class="small">Hubungi HR untuk informasi jadwal survey berikutnya.</p>
</div></div>

<?php elseif ($completed): ?>
<div class="card border-success"><div class="card-body text-center py-5">
    <i class="bi bi-check-circle-fill display-3 d-block mb-3 text-success"></i>
    <h5 class="fw-bold">Terima kasih sudah mengisi!</h5>
    <p class="text-muted mb-0">Jawaban Anda sudah tercatat untuk periode <strong><?= esc($period['nama']) ?></strong>.</p>
</div></div>

<?php elseif (empty($dimensions)): ?>
<div class="alert alert-warning">Belum ada pertanyaan survey. Hubungi admin.</div>

<?php else: ?>

<div class="alert alert-info border-0 small d-flex align-items-start gap-2 mb-4">
    <i class="bi bi-shield-check-fill fs-5 mt-1"></i>
    <div>
        <strong>Kerahasiaan terjamin.</strong> Jawaban Anda <u>tidak</u> dikaitkan dengan nama atau identitas Anda.
        Hasil hanya ditampilkan secara agregat per departemen. Jawab sejujur mungkin.
    </div>
</div>

<form method="POST" action="<?= base_url('people/eei/submit') ?>" id="surveyForm">
<?= csrf_field() ?>
<input type="hidden" name="period_id" value="<?= $period['id'] ?>">
<?php
$jabatanLevels = ['Staff','Supervisor','Asst. Manager','Manager','Senior Manager','General Manager','Director','C-Level / VP'];
?>
<?php if ($deptId): ?>
<input type="hidden" name="dept_id" value="<?= $deptId ?>">
<?php else: ?>
<div class="card mb-3">
<div class="card-body">
    <div class="row g-3">
        <div class="col-sm-6">
            <label class="form-label small fw-semibold">Divisi Anda</label>
            <select id="divSelect" class="form-select">
                <option value="">— Semua Divisi —</option>
                <?php foreach ($divisions as $dv): ?>
                <option value="<?= $dv['id'] ?>"><?= esc($dv['nama']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-sm-6">
            <label class="form-label small fw-semibold">Departemen Anda <span class="text-danger">*</span></label>
            <select name="dept_id" id="deptSelect" class="form-select" required>
                <option value="">— Pilih Departemen —</option>
                <?php foreach ($departments as $d): ?>
                <option value="<?= $d['id'] ?>" data-division="<?= $d['division_id'] ?? '' ?>">
                    <?= esc($d['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</div>
</div>
<?php endif; ?>

<div class="card mb-4">
<div class="card-body d-flex align-items-center gap-3">
    <div>
        <label class="form-label fw-semibold mb-1">Level Jabatan Anda <span class="text-danger">*</span></label>
        <div class="text-muted small">Digunakan untuk analisis agregat — tidak mengidentifikasi Anda.</div>
    </div>
    <select name="jabatan_level" class="form-select ms-auto" style="max-width:220px" required>
        <option value="">— Pilih Level —</option>
        <?php foreach ($jabatanLevels as $lv): ?>
        <option value="<?= $lv ?>"><?= $lv ?></option>
        <?php endforeach; ?>
    </select>
</div>
</div>

<?php
$likertLabels = [1 => 'Sangat<br>Tidak Setuju', 2 => 'Tidak<br>Setuju', 3 => 'Netral', 4 => 'Setuju', 5 => 'Sangat<br>Setuju'];
$totalQ = array_sum(array_map(fn($d) => count($d['questions']), $dimensions));
$qNum   = 0;
?>

<?php foreach ($dimensions as $dim): if (empty($dim['questions'])) continue; ?>
<div class="card mb-4 dim-card">
    <div class="card-header py-2 d-flex align-items-center gap-2">
        <i class="bi bi-diagram-3-fill text-primary"></i>
        <span class="fw-semibold"><?= esc($dim['nama']) ?></span>
        <?php if ($dim['deskripsi']): ?>
        <span class="text-muted small">— <?= esc($dim['deskripsi']) ?></span>
        <?php endif; ?>
    </div>
    <div class="card-body">
    <?php foreach ($dim['questions'] as $q): $qNum++; ?>
    <div class="mb-4 pb-3 <?= $qNum < $totalQ ? 'border-bottom' : '' ?>">
        <p class="fw-semibold mb-3 small">
            <span class="badge bg-secondary-subtle text-secondary border me-2"><?= $qNum ?></span>
            <?= esc($q['pertanyaan']) ?>
            <?php if ($q['is_reversed']): ?>
            <span class="badge bg-warning-subtle text-warning border ms-1" title="Item reversed — skor dibalik saat perhitungan">R</span>
            <?php endif; ?>
        </p>
        <div class="d-flex gap-2 flex-wrap">
        <?php for ($l = 1; $l <= 5; $l++): ?>
        <label class="likert-option">
            <input type="radio" name="scores[<?= $q['id'] ?>]" value="<?= $l ?>"
                   class="d-none q-radio" required>
            <div class="likert-label">
                <span class="score-num"><?= $l ?></span>
                <span><?= $likertLabels[$l] ?></span>
            </div>
        </label>
        <?php endfor; ?>
        </div>
    </div>
    <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>

<div class="card border-primary mb-4">
<div class="card-body d-flex align-items-center justify-content-between gap-3">
    <div>
        <div class="fw-semibold" id="progressText">0 dari <?= $totalQ ?> pertanyaan dijawab</div>
        <div class="progress mt-1" style="height:6px;width:200px">
            <div class="progress-bar" id="progressBar" style="width:0%"></div>
        </div>
    </div>
    <button type="submit" class="btn btn-primary btn-lg px-4" id="submitBtn" disabled>
        <i class="bi bi-send-fill me-2"></i>Kirim Jawaban
    </button>
</div>
</div>
</form>

<?php endif; ?>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
const total = <?= $totalQ ?>;

function updateProgress() {
    const answered = document.querySelectorAll('.q-radio:checked').length;
    document.getElementById('progressText').textContent = answered + ' dari ' + total + ' pertanyaan dijawab';
    document.getElementById('progressBar').style.width = (answered / total * 100) + '%';
    document.getElementById('submitBtn').disabled = answered < total;
}

document.querySelectorAll('.q-radio').forEach(r => r.addEventListener('change', updateProgress));

document.getElementById('surveyForm')?.addEventListener('submit', function(e) {
    const answered = document.querySelectorAll('.q-radio:checked').length;
    if (answered < total) {
        e.preventDefault();
        alert('Harap jawab semua ' + total + ' pertanyaan sebelum mengirim.');
    }
});

const divSelect  = document.getElementById('divSelect');
const deptSelect = document.getElementById('deptSelect');
if (divSelect && deptSelect) {
    const allDeptOptions = Array.from(deptSelect.options).slice(1);

    divSelect.addEventListener('change', function () {
        const divId = this.value;
        deptSelect.value = '';
        while (deptSelect.options.length > 1) deptSelect.remove(1);
        allDeptOptions.forEach(opt => {
            if (!divId || opt.dataset.division == divId) {
                deptSelect.appendChild(opt.cloneNode(true));
            }
        });
    });

    deptSelect.addEventListener('change', function () {
        const selected = this.options[this.selectedIndex];
        if (selected && selected.dataset.division) {
            divSelect.value = selected.dataset.division;
        } else {
            divSelect.value = '';
        }
    });
}
</script>
<?= $this->endSection() ?>
