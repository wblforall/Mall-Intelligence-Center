<?= $this->extend('layouts/main') ?>
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

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <a href="<?= base_url('events/'.$event['id'].'/summary') ?>" class="text-muted text-decoration-none small">
            <i class="bi bi-arrow-left me-1"></i>Kembali ke Summary
        </a>
        <h4 class="fw-bold mb-0 mt-1"><?= esc($event['name']) ?> — Gallery Foto</h4>
        <div class="text-muted small"><?= date('d M Y', strtotime($startDate)) ?> – <?= date('d M Y', strtotime($endDate)) ?> &bull; <?= $totalPhotos ?> foto</div>
    </div>
</div>

<?php if ($totalPhotos === 0): ?>
<div class="card">
    <div class="card-body p-5 text-center text-muted">
        <i class="bi bi-images display-3 d-block mb-3 opacity-25"></i>
        <p>Belum ada foto yang diupload untuk event ini.</p>
        <p class="small">Upload foto melalui modul Dekorasi & VM, Content, Creative, atau Sponsor.</p>
    </div>
</div>
<?php else: ?>

<!-- Tabs -->
<ul class="nav nav-tabs mb-3" id="galleryTabs">
    <?php foreach ($moduleTabs as $key => $tab): ?>
    <?php $cnt = $key === 'all' ? $totalPhotos : count($photos[$key] ?? []); ?>
    <?php if ($key !== 'all' && $cnt === 0) continue; ?>
    <li class="nav-item">
        <a class="nav-link <?= $key === 'all' ? 'active' : '' ?>" href="#" data-tab="<?= $key ?>">
            <i class="bi <?= $tab['icon'] ?> me-1"></i><?= $tab['label'] ?>
            <span class="badge bg-secondary ms-1"><?= $cnt ?></span>
        </a>
    </li>
    <?php endforeach; ?>
</ul>

<!-- Grid -->
<div class="row g-3" id="galleryGrid">
<?php foreach ($allPhotos as $idx => $p):
?>
<div class="col-6 col-sm-4 col-md-3 col-lg-2 gallery-item" data-module="<?= $p['module'] ?>">
    <div class="card h-100 border-0 shadow-sm gallery-card" style="cursor:pointer" data-photoidx="<?= $idx ?>">
        <div style="aspect-ratio:1;overflow:hidden;background:var(--c-placeholder-bg)" class="rounded-top">
            <img src="<?= $p['src'] ?>" alt="<?= esc($p['caption']) ?>"
                 style="width:100%;height:100%;object-fit:cover"
                 loading="lazy"
                 onerror="this.parentElement.innerHTML='<div class=\'d-flex align-items-center justify-content-center h-100 text-muted\'><i class=\'bi bi-image-slash fs-3\'></i></div>'">
        </div>
        <div class="card-body p-2">
            <div class="small text-muted text-truncate" title="<?= esc($p['caption']) ?>"><?= esc($p['caption']) ?></div>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>

<!-- Lightbox modal -->
<div class="modal fade" id="lightboxModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content bg-dark border-0">
            <div class="modal-header border-0 pb-0">
                <span class="text-white small" id="lbCaption"></span>
                <div class="ms-auto d-flex gap-2">
                    <a href="#" id="lbDownload" download class="btn btn-sm btn-outline-light">
                        <i class="bi bi-download"></i>
                    </a>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <div class="modal-body text-center p-2 position-relative">
                <button class="btn btn-outline-light btn-sm position-absolute start-0 top-50 translate-middle-y ms-2" id="lbPrev" style="z-index:10">
                    <i class="bi bi-chevron-left"></i>
                </button>
                <img id="lbImg" src="" alt="" style="max-height:80vh;max-width:100%;object-fit:contain">
                <button class="btn btn-outline-light btn-sm position-absolute end-0 top-50 translate-middle-y me-2" id="lbNext" style="z-index:10">
                    <i class="bi bi-chevron-right"></i>
                </button>
            </div>
            <div class="modal-footer border-0 pt-0 justify-content-center">
                <span class="text-muted small" id="lbCounter"></span>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<?php if ($totalPhotos > 0): ?>
<script>
const allPhotos    = <?= json_encode(array_values($allPhotos)) ?>;
let visibleIndices = allPhotos.map((_, i) => i);
let currentPos     = 0;

const lbModal   = new bootstrap.Modal(document.getElementById('lightboxModal'));
const lbImg     = document.getElementById('lbImg');
const lbCaption = document.getElementById('lbCaption');
const lbDown    = document.getElementById('lbDownload');
const lbCounter = document.getElementById('lbCounter');

function openLightbox(photoIdx) {
    const pos  = visibleIndices.indexOf(photoIdx);
    currentPos = pos >= 0 ? pos : 0;
    showPhoto();
    lbModal.show();
}

function showPhoto() {
    const p = allPhotos[visibleIndices[currentPos]];
    if (! p) return;
    lbImg.src             = p.src;
    lbImg.alt             = p.caption;
    lbCaption.textContent = p.caption;
    lbDown.href           = p.src;
    lbCounter.textContent = (currentPos + 1) + ' / ' + visibleIndices.length;
}

function navLightbox(dir) {
    currentPos = (currentPos + dir + visibleIndices.length) % visibleIndices.length;
    showPhoto();
}

document.getElementById('galleryGrid').addEventListener('click', e => {
    const card = e.target.closest('.gallery-card');
    if (! card) return;
    openLightbox(parseInt(card.dataset.photoidx));
});

document.getElementById('lbPrev').addEventListener('click', () => navLightbox(-1));
document.getElementById('lbNext').addEventListener('click', () => navLightbox(1));

document.addEventListener('keydown', e => {
    if (! document.getElementById('lightboxModal').classList.contains('show')) return;
    if (e.key === 'ArrowLeft')  navLightbox(-1);
    if (e.key === 'ArrowRight') navLightbox(1);
    if (e.key === 'Escape')     lbModal.hide();
});

document.querySelectorAll('#galleryTabs .nav-link').forEach(tab => {
    tab.addEventListener('click', e => {
        e.preventDefault();
        document.querySelectorAll('#galleryTabs .nav-link').forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
        const filter = tab.dataset.tab;
        document.querySelectorAll('.gallery-item').forEach(item => {
            item.style.display = (filter === 'all' || item.dataset.module === filter) ? '' : 'none';
        });
        visibleIndices = filter === 'all'
            ? allPhotos.map((_, i) => i)
            : allPhotos.reduce((acc, p, i) => { if (p.module === filter) acc.push(i); return acc; }, []);
        currentPos = 0;
    });
});
</script>
<?php endif; ?>
<?= $this->endSection() ?>
