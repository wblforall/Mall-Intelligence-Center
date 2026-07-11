<?php
/** Thumbnail foto bukti sebuah update. Param: $images (array baris work_initiative_update_images). */
if (empty($images)) return;
?>
<div class="d-flex flex-wrap gap-1 mt-1">
    <?php foreach ($images as $im): $url = base_url('uploads/work_report/' . $im['initiative_id'] . '/' . $im['file_name']); ?>
    <a href="<?= $url ?>" target="_blank" title="<?= esc($im['original_name'] ?? 'Foto') ?>">
        <img src="<?= $url ?>" alt="<?= esc($im['original_name'] ?? 'Foto bukti') ?>" loading="lazy"
             style="width:56px;height:56px;object-fit:cover;border-radius:6px;border:1px solid var(--bs-border-color)">
    </a>
    <?php endforeach; ?>
</div>
