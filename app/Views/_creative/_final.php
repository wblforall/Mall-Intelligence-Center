<?php
/**
 * Baris "materi final" pada kartu item yang sudah disetujui.
 *
 * Ditampilkan di muka, bukan di dalam panel yang harus dibuka dulu: file
 * inilah yang dicari orang produksi, dan sebelum ada penanda opsi terpilih
 * mereka harus menebak file mana yang dipakai.
 *
 * Variabel: $iFiles (semua file item), $fileBase (prefix URL file)
 */
$final = array_values(array_filter(
    $iFiles,
    fn($f) => (int) ($f['is_opsi'] ?? 1) === 1 && (int) ($f['is_terpilih'] ?? 0) === 1
));
?>
<div class="small mb-1">
    <?php if (!empty($final)): ?>
    <i class="bi bi-check-circle-fill text-success me-1"></i>
    <span class="text-muted">Materi final:</span>
    <?php foreach ($final as $f): ?>
    <a href="<?= base_url($fileBase . $f['file_name']) ?>" download="<?= esc($f['original_name'], 'attr') ?>"
       class="badge bg-success text-decoration-none ms-1" style="font-size:.62rem"
       title="Unduh <?= esc($f['original_name'], 'attr') ?>">
        <i class="bi bi-download me-1"></i>Opsi <?= esc($f['opsi_label'] ?? '?') ?>
    </a>
    <?php endforeach; ?>
    <?php else: ?>
    <i class="bi bi-question-circle text-warning me-1"></i>
    <span class="text-muted fst-italic">Disetujui, tapi opsi final belum ditentukan.</span>
    <?php endif; ?>
</div>
