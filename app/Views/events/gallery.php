<?php helper('tanggal'); ?><?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/events-core.css') ?>?v=<?= @filemtime(FCPATH . 'css/events-core.css') ?: 1 ?>">
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php
$startDate = $event['start_date'];
$endDate   = date('Y-m-d', strtotime($startDate . ' +' . ($event['event_days'] - 1) . ' days'));
$allPhotos  = [];
foreach ($photos as $module => $list) {
    foreach ($list as $p) {
        $allPhotos[] = array_merge($p, ['module' => $module]);
    }
}
$moduleTabs = [
    'all'      => ['label' => 'Semua',       'icon' => 'bi-grid'],
    'vm'       => ['label' => 'Dekorasi & VM','icon' => 'bi-palette-fill'],
    'content'  => ['label' => 'Content',     'icon' => 'bi-collection-play'],
    'creative' => ['label' => 'Creative',    'icon' => 'bi-vector-pen'],
    'sponsor'  => ['label' => 'Sponsor',     'icon' => 'bi-award-fill'],
];
?>

<div class="page-evc">

<div class="ev-head">
    <a href="<?= base_url('events/'.$event['id'].'/summary') ?>" class="ev-iconbtn" aria-label="Kembali ke Summary" title="Kembali ke Summary"><i class="bi bi-arrow-left"></i></a>
    <div class="min-w-0">
        <h1 class="ev-title"><?= esc($event['name']) ?> — Galeri Foto</h1>
        <div class="ev-subtitle"><?= tgl_indo_pendek($startDate, false) ?> – <?= tgl_indo_pendek($endDate, false) ?> &bull; <?= $totalPhotos ?> foto</div>
    </div>
</div>
<?= view('partials/event_tabs', ['event' => $event]) ?>

<?php if ($totalPhotos === 0): ?>
<div class="ev-empty">
    <i class="bi bi-images"></i>
    <h5>Belum ada foto</h5>
    <p>Foto diunggah lewat modul Dekorasi &amp; VM, Content, Creative, atau Sponsor, lalu muncul otomatis di sini.</p>
    <a href="<?= base_url('events/'.$event['id'].'/summary') ?>" class="ev-btn">Kembali ke Summary</a>
</div>
<?php else: ?>

<!-- Tab modul -->
<div class="ev-gtabs" id="galleryTabs" role="group" aria-label="Filter modul">
    <?php foreach ($moduleTabs as $key => $tab): ?>
    <?php $cnt = $key === 'all' ? $totalPhotos : count($photos[$key] ?? []); ?>
    <?php if ($key !== 'all' && $cnt === 0) continue; ?>
    <button type="button" class="ev-gtab <?= $key === 'all' ? 'is-on' : '' ?>" data-tab="<?= $key ?>" aria-pressed="<?= $key === 'all' ? 'true' : 'false' ?>">
        <i class="bi <?= $tab['icon'] ?>"></i><?= $tab['label'] ?><b><?= $cnt ?></b>
    </button>
    <?php endforeach; ?>
</div>

<!-- Grid -->
<div class="ev-ggrid" id="galleryGrid">
<?php foreach ($allPhotos as $idx => $p): ?>
<div class="gallery-item" data-module="<?= esc($p['module'], 'attr') ?>">
    <div class="ev-gcard gallery-card" tabindex="0" role="button" data-photoidx="<?= $idx ?>" aria-label="Buka foto: <?= esc($p['caption'], 'attr') ?>">
        <div class="ev-gimg">
            <img src="<?= esc($p['src'], 'attr') ?>" alt="<?= esc($p['caption'], 'attr') ?>" loading="lazy">
        </div>
        <div class="ev-gcap"><b><?= esc($moduleTabs[$p['module']]['label'] ?? $p['module']) ?></b><?= esc($p['caption']) ?></div>
    </div>
</div>
<?php endforeach; ?>
</div>

<!-- Lightbox -->
<div class="modal fade" id="lightboxModal" tabindex="-1" aria-label="Pratinjau foto">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content bg-dark border-0">
            <div class="modal-header border-0 pb-0">
                <span class="text-white small" id="lbCaption"></span>
                <div class="ms-auto d-flex gap-2">
                    <a href="#" id="lbDownload" download class="btn btn-sm btn-outline-light" aria-label="Unduh foto" title="Unduh">
                        <i class="bi bi-download"></i>
                    </a>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
            </div>
            <div class="modal-body text-center p-2 position-relative">
                <button class="btn btn-outline-light btn-sm position-absolute start-0 top-50 translate-middle-y ms-2" id="lbPrev" style="z-index:10" aria-label="Sebelumnya">
                    <i class="bi bi-chevron-left"></i>
                </button>
                <img id="lbImg" src="" alt="" style="max-height:80vh;max-width:100%;object-fit:contain">
                <button class="btn btn-outline-light btn-sm position-absolute end-0 top-50 translate-middle-y me-2" id="lbNext" style="z-index:10" aria-label="Berikutnya">
                    <i class="bi bi-chevron-right"></i>
                </button>
            </div>
            <div class="modal-footer border-0 pt-0 justify-content-center">
                <span class="text-white-50 small" id="lbCounter"></span>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<?php if ($totalPhotos > 0): ?>
<script>window.EV_PHOTOS = <?= json_encode(array_values($allPhotos)) ?>;</script>
<script src="<?= base_url('js/events-core.js') ?>?v=<?= @filemtime(FCPATH . 'js/events-core.js') ?: 1 ?>"></script>
<?php endif; ?>
<?= $this->endSection() ?>
