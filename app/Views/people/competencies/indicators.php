<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$levelColors = [1=>'#94a3b8',2=>'#38bdf8',3=>'#6366f1',4=>'#f59e0b',5=>'#10b981'];
$levelLabels = [1=>'Pemula',2=>'Dasar',3=>'Menengah',4=>'Mahir',5=>'Ahli'];

// Group by level
$byLevel = [];
foreach ($indicators as $ind) {
    $byLevel[$ind['level']][] = $ind;
}
?>

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= base_url('people/competencies') ?>" class="btn btn-sm btn-light">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div>
        <div class="text-muted small">Competency Framework</div>
        <h5 class="fw-bold mb-0">Behavioral Indicators — <?= esc($comp['nama']) ?></h5>
        <div class="text-muted small">
            <span class="badge <?= $comp['kategori'] === 'hard' ? 'bg-primary' : 'bg-success' ?>"><?= ucfirst($comp['kategori']) ?> Skill</span>
            <?php if ($comp['deskripsi']): ?>
            · <?= esc($comp['deskripsi']) ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success alert-dismissible fade show py-2">
    <?= session()->getFlashdata('success') ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row g-4">
    <!-- Indicator list -->
    <div class="col-lg-8">
    <?php for ($l = 1; $l <= 5; $l++):
        $inds = $byLevel[$l] ?? [];
    ?>
    <div class="card mb-3">
        <div class="card-header d-flex align-items-center gap-2 py-2">
            <span class="d-flex align-items-center justify-content-center rounded-circle fw-bold text-white flex-shrink-0"
                  style="width:28px;height:28px;font-size:.72rem;background:<?= $levelColors[$l] ?>">
                <?= $l ?>
            </span>
            <span class="fw-semibold" style="color:<?= $levelColors[$l] ?>">Level <?= $l ?> — <?= $levelLabels[$l] ?></span>
            <?php if ($comp['level_' . $l]): ?>
            <span class="text-muted small ms-1">· <?= esc($comp['level_' . $l]) ?></span>
            <?php endif; ?>
            <span class="badge bg-secondary-subtle text-secondary ms-auto"><?= count($inds) ?> indikator</span>
        </div>
        <?php if (! empty($inds)): ?>
        <ul class="list-group list-group-flush">
        <?php foreach ($inds as $ind): ?>
        <li class="list-group-item d-flex align-items-start gap-2 py-2">
            <i class="bi bi-check2-circle text-success mt-1 flex-shrink-0"></i>
            <span class="flex-grow-1 small"><?= esc($ind['deskripsi']) ?></span>
            <a href="<?= base_url('people/competencies/indicators/' . $ind['id'] . '/delete') ?>"
               class="btn btn-sm btn-outline-danger flex-shrink-0 py-0 px-1"
               onclick="return confirm('Hapus indikator ini? Data assessment terkait juga terhapus.')">
                <i class="bi bi-trash" style="font-size:.7rem"></i>
            </a>
        </li>
        <?php endforeach; ?>
        </ul>
        <?php else: ?>
        <div class="card-body py-2 text-muted small">Belum ada indikator di level ini.</div>
        <?php endif; ?>
    </div>
    <?php endfor; ?>
    </div>

    <!-- Add indicator form -->
    <div class="col-lg-4">
    <div class="card sticky-top" style="top:1rem">
        <div class="card-header fw-semibold py-2">
            <i class="bi bi-plus-circle me-1"></i> Tambah Indikator
        </div>
        <div class="card-body">
            <form method="POST" action="<?= base_url('people/competencies/' . $comp['id'] . '/indicators/add') ?>">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Level <span class="text-danger">*</span></label>
                    <div class="d-flex gap-1">
                    <?php for ($l = 1; $l <= 5; $l++): ?>
                    <input type="radio" class="btn-check" name="level" id="addLvl<?= $l ?>" value="<?= $l ?>" <?= $l === 1 ? 'checked' : '' ?> required>
                    <label class="btn btn-sm btn-outline-secondary px-2" for="addLvl<?= $l ?>"
                           style="border-color:<?= $levelColors[$l] ?>;color:<?= $levelColors[$l] ?>">
                        <?= $l ?>
                    </label>
                    <?php endfor; ?>
                    </div>
                    <div class="form-text" id="levelHint">Pemula — kemampuan paling dasar</div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Deskripsi Perilaku <span class="text-danger">*</span></label>
                    <textarea name="deskripsi" class="form-control" rows="3" required
                              placeholder="Contoh: Mampu menjelaskan ide secara lisan kepada rekan setim"></textarea>
                    <div class="form-text">Gunakan kalimat aktif yang dapat diobservasi.</div>
                </div>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-plus-lg me-1"></i> Tambah Indikator
                </button>
            </form>
        </div>
        <div class="card-footer text-muted small py-2">
            <i class="bi bi-info-circle me-1"></i>
            Level = level kompetensi yang dicapai jika indikator ini terpenuhi.
            Semua indikator di level N harus terpenuhi untuk mencapai level N.
        </div>
    </div>
    </div>
</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
const levelHints = {
    1: 'Pemula — kemampuan paling dasar',
    2: 'Dasar — bisa dengan bimbingan',
    3: 'Menengah — mandiri dalam situasi umum',
    4: 'Mahir — menguasai dan bisa membimbing',
    5: 'Ahli — expert, menjadi referensi'
};
document.querySelectorAll('input[name="level"]').forEach(r => {
    r.addEventListener('change', function() {
        document.getElementById('levelHint').textContent = levelHints[this.value] || '';
    });
});
</script>
<?= $this->endSection() ?>
