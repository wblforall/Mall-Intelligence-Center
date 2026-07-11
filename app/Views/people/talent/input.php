<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-clipboard-check me-2"></i>Penilaian Talent</h4>
        <small class="text-muted">Nilai Performance &amp; Potential bawahan, lalu teruskan ke atasan berikut / verifikasi.</small>
    </div>
    <?php if ($canViewMap): ?>
    <a href="<?= base_url('people/talent') ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-grid-3x3-gap me-1"></i>Lihat Peta 9-Box</a>
    <?php endif; ?>
</div>

<!-- Rubrik -->
<div class="accordion mb-3" id="rubrikAcc">
  <div class="accordion-item">
    <h2 class="accordion-header"><button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse" data-bs-target="#rubrik">
      <i class="bi bi-info-circle me-2"></i>Panduan Skala 1–3 (Performance &amp; Potential)</button></h2>
    <div id="rubrik" class="accordion-collapse collapse" data-bs-parent="#rubrikAcc">
      <div class="accordion-body small">
        <div class="row g-3">
          <div class="col-md-6">
            <strong>Performance (kinerja aktual)</strong>
            <ul class="mb-0 mt-1"><li><b>3 Tinggi</b> — konsisten melampaui target, jadi rujukan.</li>
            <li><b>2 Sedang</b> — memenuhi target, solid &amp; andal.</li>
            <li><b>1 Rendah</b> — di bawah target, butuh supervisi ketat.</li></ul>
          </div>
          <div class="col-md-6">
            <strong>Potential (kapasitas tumbuh)</strong>
            <ul class="mb-0 mt-1"><li><b>3 Tinggi</b> — siap naik 1–2 level dalam 1–2 tahun.</li>
            <li><b>2 Sedang</b> — bisa berkembang / naik 1 level dalam 2–3 tahun.</li>
            <li><b>1 Rendah</b> — optimal di peran sekarang, fokus mendalami keahlian.</li></ul>
          </div>
        </div>
        <div class="text-muted mt-2"><i class="bi bi-exclamation-circle me-1"></i>Potensi ≠ Kinerja. Kinerja tinggi + potensi rendah = <em>spesialis andalan</em>, itu normal &amp; berharga.</div>
      </div>
    </div>
  </div>
</div>

<?php
$rows = array_merge(
    array_map(fn($x) => $x + ['_hr' => false], $inbox),
    array_map(fn($x) => $x + ['_hr' => true, 'is_top' => true], $hrPending ?? [])
);
?>

<?php if (empty($rows)): ?>
<div class="text-center text-muted py-5">
    <i class="bi bi-check2-circle fs-1 d-block mb-2"></i>
    Tidak ada penilaian yang menunggu giliran Anda.
</div>
<?php else: ?>

<?php foreach ($rows as $r):
    $eName = $r['employee_nama'] ?? '—';
    $isTop = ! empty($r['is_top']);
?>
<div class="card mb-3">
<form method="POST" action="<?= base_url('people/talent/' . $r['id'] . '/save') ?>">
    <?= csrf_field() ?>
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <div class="fw-semibold"><?= esc($eName) ?>
                    <?php if ($r['_hr']): ?><span class="badge bg-warning text-dark ms-1" style="font-size:.6rem">Perlu HR (rantai putus)</span><?php endif; ?>
                    <?php if (($r['status'] ?? '') === 'in_review'): ?><span class="badge bg-info ms-1" style="font-size:.6rem">Review</span><?php endif; ?>
                    <?php if (! empty($r['periode'])): ?><span class="badge bg-secondary ms-1" style="font-size:.6rem"><i class="bi bi-calendar2-range me-1"></i><?= esc($r['periode']) ?></span><?php endif; ?>
                </div>
                <small class="text-muted"><?= esc($r['jabatan'] ?? '—') ?></small>
            </div>
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label small fw-semibold">Performance <span class="text-danger">*</span></label>
                <div class="btn-group w-100" role="group">
                    <?php foreach ([1 => 'Rendah', 2 => 'Sedang', 3 => 'Tinggi'] as $v => $lbl): ?>
                    <input type="radio" class="btn-check" name="performance" id="perf<?= $r['id'] ?>_<?= $v ?>" value="<?= $v ?>" <?= (int)($r['performance'] ?? 0) === $v ? 'checked' : '' ?> required>
                    <label class="btn btn-outline-primary btn-sm" for="perf<?= $r['id'] ?>_<?= $v ?>"><?= $v ?> · <?= $lbl ?></label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label small fw-semibold">Potential <span class="text-danger">*</span></label>
                <div class="btn-group w-100" role="group">
                    <?php foreach ([1 => 'Rendah', 2 => 'Sedang', 3 => 'Tinggi'] as $v => $lbl): ?>
                    <input type="radio" class="btn-check" name="potential" id="pot<?= $r['id'] ?>_<?= $v ?>" value="<?= $v ?>" <?= (int)($r['potential'] ?? 0) === $v ? 'checked' : '' ?> required>
                    <label class="btn btn-outline-success btn-sm" for="pot<?= $r['id'] ?>_<?= $v ?>"><?= $v ?> · <?= $lbl ?></label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="mt-3">
            <label class="form-label small fw-semibold">Catatan / Justifikasi <span class="text-muted fw-normal">(wajib bila mengubah nilai dari atasan sebelumnya)</span></label>
            <textarea name="catatan" class="form-control form-control-sm" rows="2"><?= esc($r['catatan'] ?? '') ?></textarea>
        </div>
    </div>
    <div class="card-footer d-flex justify-content-end gap-2 py-2">
        <button type="submit" name="action" value="save" class="btn btn-outline-secondary btn-sm"><i class="bi bi-save me-1"></i>Simpan</button>
        <?php if ($isTop): ?>
        <button type="submit" name="action" value="forward" class="btn btn-success btn-sm"><i class="bi bi-patch-check me-1"></i>Simpan &amp; Verifikasi (final)</button>
        <?php else: ?>
        <button type="submit" name="action" value="forward" class="btn btn-primary btn-sm"><i class="bi bi-arrow-up-circle me-1"></i>Simpan &amp; Teruskan ke Atasan</button>
        <?php endif; ?>
    </div>
</form>
</div>
<?php endforeach; ?>

<?php endif; ?>

<?= $this->endSection() ?>
