<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<style>
.level-desc-row { font-size:.75rem; color:var(--bs-secondary-color); }
.level-dot { display:inline-flex; align-items:center; justify-content:center;
    width:18px; height:18px; border-radius:50%; font-size:.62rem; font-weight:700;
    color:#fff; flex-shrink:0; }
</style>
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php
$levelColors = [1=>'#ef4444',2=>'#f97316',3=>'#eab308',4=>'#22c55e',5=>'#6366f1'];
$levelLabels = [1=>'Tidak pernah',2=>'Jarang',3=>'Kadang-kadang',4=>'Sering',5=>'Selalu'];
?>

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= base_url('people/competencies') ?>" class="btn btn-sm btn-light">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div>
        <div class="text-muted small"><?= $comp['kategori'] === 'hard' ? 'Hard Skill' : 'Soft Skill' ?></div>
        <h5 class="fw-bold mb-0"><?= esc($comp['nama']) ?></h5>
        <div class="text-muted small">Pertanyaan Penilaian Likert 1–5</div>
    </div>
</div>

<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success py-2"><?= session()->getFlashdata('success') ?></div>
<?php endif; ?>

<div class="row g-3">
    <!-- Question list -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header fw-semibold d-flex align-items-center justify-content-between">
                <span><i class="bi bi-list-check me-2"></i>Daftar Pertanyaan</span>
                <span class="badge bg-secondary"><?= count($questions) ?></span>
            </div>
            <?php if (empty($questions)): ?>
            <div class="card-body text-center text-muted py-4">
                <i class="bi bi-chat-left-dots" style="font-size:2rem;opacity:.3"></i>
                <p class="mt-2 mb-0">Belum ada pertanyaan.</p>
            </div>
            <?php else: ?>
            <ul class="list-group list-group-flush">
                <?php foreach ($questions as $i => $q): ?>
                <li class="list-group-item py-3">
                    <div class="d-flex align-items-start gap-3">
                        <span class="badge bg-secondary rounded-pill mt-1 flex-shrink-0"><?= $i + 1 ?></span>
                        <div class="flex-grow-1">
                            <div style="font-size:.88rem" class="mb-2"><?= esc($q['pertanyaan']) ?></div>
                            <!-- Level descriptions inline -->
                            <?php $hasDesc = array_filter(array_map(fn($l) => $q['level_'.$l] ?? null, range(1,5))); ?>
                            <?php if ($hasDesc): ?>
                            <div class="d-flex flex-column gap-1">
                                <?php for ($l = 1; $l <= 5; $l++): ?>
                                <?php if (! empty($q['level_'.$l])): ?>
                                <div class="d-flex align-items-start gap-2 level-desc-row">
                                    <span class="level-dot flex-shrink-0" style="background:<?= $levelColors[$l] ?>"><?= $l ?></span>
                                    <span><?= esc($q['level_'.$l]) ?></span>
                                </div>
                                <?php endif; ?>
                                <?php endfor; ?>
                            </div>
                            <?php else: ?>
                            <div class="text-muted" style="font-size:.72rem">
                                <i class="bi bi-exclamation-circle me-1"></i>Deskripsi level belum diisi
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex gap-1 flex-shrink-0">
                            <button class="btn btn-sm btn-outline-primary level-btn"
                                    data-id="<?= $q['id'] ?>"
                                    data-pertanyaan="<?= esc($q['pertanyaan']) ?>"
                                    data-l1="<?= esc($q['level_1'] ?? '') ?>"
                                    data-l2="<?= esc($q['level_2'] ?? '') ?>"
                                    data-l3="<?= esc($q['level_3'] ?? '') ?>"
                                    data-l4="<?= esc($q['level_4'] ?? '') ?>"
                                    data-l5="<?= esc($q['level_5'] ?? '') ?>"
                                    title="Set deskripsi level">
                                <i class="bi bi-sliders"></i>
                            </button>
                            <a href="<?= base_url('people/competencies/questions/' . $q['id'] . '/delete') ?>"
                               class="btn btn-sm btn-outline-danger"
                               onclick="return confirm('Hapus pertanyaan ini?')">
                                <i class="bi bi-trash"></i>
                            </a>
                        </div>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add form -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header fw-semibold"><i class="bi bi-plus-circle me-2"></i>Tambah Pertanyaan</div>
            <div class="card-body">
                <form method="POST" action="<?= base_url('people/competencies/' . $comp['id'] . '/questions/add') ?>">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Pertanyaan <span class="text-danger">*</span></label>
                        <textarea name="pertanyaan" class="form-control" rows="3"
                                  placeholder="cth: Apakah karyawan mampu menggunakan AI untuk menyusun prompt yang efektif?" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="bi bi-plus-lg me-1"></i>Tambah
                    </button>
                </form>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-body py-2 small text-muted">
                <strong>Skala penilaian:</strong>
                <div class="d-flex flex-column gap-1 mt-1">
                    <?php for ($l = 1; $l <= 5; $l++): ?>
                    <div class="d-flex align-items-center gap-2">
                        <span class="level-dot" style="background:<?= $levelColors[$l] ?>"><?= $l ?></span>
                        <span><?= $levelLabels[$l] ?></span>
                    </div>
                    <?php endfor; ?>
                </div>
                <div class="mt-2 text-muted" style="font-size:.7rem">
                    <i class="bi bi-lightbulb me-1 text-warning"></i>
                    Klik <i class="bi bi-sliders"></i> pada pertanyaan untuk mengisi deskripsi spesifik tiap level.
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Level Description Modal -->
<div class="modal fade" id="levelModal" tabindex="-1">
<div class="modal-dialog modal-lg"><div class="modal-content">
<form method="POST" id="levelForm">
    <?= csrf_field() ?>
    <div class="modal-header">
        <div>
            <h5 class="modal-title fw-semibold">Deskripsi Level</h5>
            <div class="text-muted small" id="modalPertanyaan"></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
        <p class="small text-muted mb-3">
            Isi deskripsi perilaku konkret untuk setiap level sehingga assessor dapat menilai dengan lebih tepat.
        </p>
        <?php for ($l = 1; $l <= 5; $l++): ?>
        <div class="mb-3">
            <label class="form-label small fw-semibold d-flex align-items-center gap-2">
                <span class="level-dot" style="background:<?= $levelColors[$l] ?>"><?= $l ?></span>
                <?= $levelLabels[$l] ?>
            </label>
            <textarea name="level_<?= $l ?>" id="mLevel<?= $l ?>" class="form-control form-control-sm" rows="2"
                      placeholder="Deskripsikan perilaku/kemampuan yang menunjukkan level <?= $l ?>..."></textarea>
        </div>
        <?php endfor; ?>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan Deskripsi</button>
    </div>
</form>
</div></div></div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
document.querySelectorAll('.level-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const d = this.dataset;
        document.getElementById('levelForm').action =
            '<?= base_url('people/competencies/questions/') ?>' + d.id + '/levels';
        document.getElementById('modalPertanyaan').textContent = d.pertanyaan;
        for (let l = 1; l <= 5; l++) {
            document.getElementById('mLevel' + l).value = d['l' + l] || '';
        }
        new bootstrap.Modal(document.getElementById('levelModal')).show();
    });
});
</script>
<?= $this->endSection() ?>
