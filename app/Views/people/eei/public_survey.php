<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Employee Engagement Survey — PT. Wulandari Bangun Laksana</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
body { background: #f8fafc; }
.survey-wrap { max-width: 720px; margin: 0 auto; padding: 24px 16px 64px; }
.brand-bar { background: linear-gradient(135deg,#1e3a5f,#2563eb); color:#fff; padding:14px 20px; border-radius:12px; margin-bottom:28px; }
.likert-option { cursor:pointer; }
.likert-option input:checked + .likert-label { background:var(--bs-primary); color:#fff; border-color:var(--bs-primary); }
.likert-label { display:flex; flex-direction:column; align-items:center; justify-content:center;
    border:1px solid #dee2e6; border-radius:.5rem; padding:.5rem .25rem;
    min-width:60px; font-size:.7rem; text-align:center; transition:all .15s; user-select:none; }
.likert-label:hover { border-color:var(--bs-primary); background:var(--bs-primary-bg-subtle); }
.likert-label .score-num { font-size:1.1rem; font-weight:700; margin-bottom:2px; }
.dim-card { border-left:3px solid var(--bs-primary); }
</style>
</head>
<body>
<div class="survey-wrap">

    <div class="brand-bar d-flex align-items-center gap-3">
        <div>
            <div class="fw-bold" style="font-size:1rem">PT. Wulandari Bangun Laksana Tbk.</div>
            <div style="font-size:.75rem;opacity:.8">eWalk &amp; Pentacity Mall</div>
        </div>
        <span class="ms-auto badge bg-white text-primary fw-semibold px-3 py-2">EEI Survey</span>
    </div>

    <?php if ($error): ?>
    <div class="card text-center py-5">
        <div class="card-body">
            <i class="bi bi-exclamation-circle display-4 text-danger d-block mb-3"></i>
            <h5 class="fw-bold">Link Tidak Valid</h5>
            <p class="text-muted mb-0"><?= esc($error) ?></p>
        </div>
    </div>

    <?php elseif (session()->getFlashdata('pub_success') || $completed): ?>
    <div class="card border-success text-center py-5">
        <div class="card-body">
            <i class="bi bi-check-circle-fill display-3 text-success d-block mb-3"></i>
            <h5 class="fw-bold">Terima kasih sudah mengisi!</h5>
            <p class="text-muted mb-0">
                Jawaban Anda sudah tercatat untuk periode
                <strong><?= esc($period['nama']) ?></strong>.
            </p>
        </div>
    </div>

    <?php elseif (! $period || ! $period['is_active']): ?>
    <div class="card text-center py-5">
        <div class="card-body">
            <i class="bi bi-calendar-x display-4 text-muted d-block mb-3"></i>
            <h5 class="fw-bold">Survey Tidak Aktif</h5>
            <p class="text-muted mb-0">Survey untuk periode ini belum dibuka atau sudah ditutup.</p>
        </div>
    </div>

    <?php elseif (empty($dimensions)): ?>
    <div class="alert alert-warning">Belum ada pertanyaan survey. Hubungi HR/admin.</div>

    <?php else: ?>

    <?php if (session()->getFlashdata('pub_error')): ?>
    <div class="alert alert-danger"><?= session()->getFlashdata('pub_error') ?></div>
    <?php endif; ?>

    <div class="mb-3">
        <h5 class="fw-bold mb-1"><i class="bi bi-heart-pulse-fill me-2 text-danger"></i>Employee Engagement Survey</h5>
        <p class="text-muted small mb-0">
            Periode: <strong><?= esc($period['nama']) ?></strong> &nbsp;·&nbsp;
            <?= date('d M Y', strtotime($period['start_date'])) ?> – <?= date('d M Y', strtotime($period['end_date'])) ?>
        </p>
    </div>

    <div class="alert alert-info border-0 small d-flex align-items-start gap-2 mb-4">
        <i class="bi bi-shield-check-fill fs-5 mt-1 flex-shrink-0"></i>
        <div>
            <strong>Kerahasiaan terjamin.</strong> Jawaban Anda <u>tidak</u> dikaitkan dengan nama atau identitas Anda.
            Hasil hanya ditampilkan secara agregat per departemen. Jawab sejujur mungkin.
        </div>
    </div>

    <form method="POST" action="<?= base_url('eei/' . $token . '/submit') ?>" id="surveyForm">
    <?= csrf_field() ?>

    <!-- Divisi & Departemen -->
    <div class="card mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-sm-6">
                <label class="form-label fw-semibold">Divisi Anda</label>
                <select id="divSelect" class="form-select">
                    <option value="">— Semua Divisi —</option>
                    <?php foreach ($divisions as $dv): ?>
                    <option value="<?= $dv['id'] ?>"><?= esc($dv['nama']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-6">
                <label class="form-label fw-semibold">Departemen Anda <span class="text-danger">*</span></label>
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

    <!-- Level Jabatan -->
    <div class="card mb-4">
    <div class="card-body d-flex align-items-center gap-3 flex-wrap">
        <div>
            <label class="form-label fw-semibold mb-1">Level Jabatan Anda <span class="text-danger">*</span></label>
            <div class="text-muted small">Untuk analisis agregat — tidak mengidentifikasi Anda.</div>
        </div>
        <select name="jabatan_level" class="form-select ms-auto" style="max-width:220px" required>
            <option value="">— Pilih Level —</option>
            <?php foreach (['Staff','Supervisor','Asst. Manager','Manager','Senior Manager','General Manager','Director','C-Level / VP'] as $lv): ?>
            <option value="<?= $lv ?>"><?= $lv ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    </div>

    <?php
    $likertLabels = [1=>'Sangat<br>Tidak Setuju', 2=>'Tidak<br>Setuju', 3=>'Netral', 4=>'Setuju', 5=>'Sangat<br>Setuju'];
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
            </p>
            <div class="d-flex gap-2 flex-wrap">
            <?php for ($l = 1; $l <= 5; $l++): ?>
            <label class="likert-option">
                <input type="radio" name="scores[<?= $q['id'] ?>]" value="<?= $l ?>" class="d-none q-radio" required>
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
    <div class="card-body d-flex align-items-center justify-content-between gap-3 flex-wrap">
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

    <div class="text-center text-muted small mt-4">
        &copy; <?= date('Y') ?> PT. Wulandari Bangun Laksana Tbk. — Mall Intelligence Center
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const total = <?= $totalQ ?? 0 ?>;

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

// Cascading division → department
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
</body>
</html>
