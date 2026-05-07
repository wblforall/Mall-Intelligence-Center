<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<style>
.fade-up {
    opacity: 0;
    transform: translateY(14px);
    animation: fadeUpCrI .5s cubic-bezier(.22,.68,0,1.2) forwards;
}
@keyframes fadeUpCrI { to { opacity: 1; transform: translateY(0); } }
</style>
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php
$tipeConfig = [
    'master_design' => ['label' => 'Master Design',   'icon' => 'bi-vector-pen',   'color' => 'primary',   'bg' => 'bg-primary-subtle'],
    'digital'       => ['label' => 'Content Digital', 'icon' => 'bi-phone',         'color' => 'info',      'bg' => 'bg-info-subtle'],
    'cetak'         => ['label' => 'Media Cetak',      'icon' => 'bi-printer',       'color' => 'secondary', 'bg' => 'bg-secondary-subtle'],
    'influencer'    => ['label' => 'Influencer',       'icon' => 'bi-person-video3', 'color' => 'warning',   'bg' => 'bg-warning-subtle'],
    'media_prescon' => ['label' => 'Media Prescon',    'icon' => 'bi-newspaper',     'color' => 'dark',      'bg' => 'bg-dark-subtle'],
];
$platformLabels = ['ig' => 'Instagram', 'tiktok' => 'TikTok', 'keduanya' => 'IG & TikTok'];
$statusConfig = [
    'draft'    => ['label' => 'Draft',        'badge' => 'bg-secondary-subtle text-secondary'],
    'review'   => ['label' => 'Dalam Review', 'badge' => 'bg-warning-subtle text-warning'],
    'approved' => ['label' => 'Approved',     'badge' => 'bg-success-subtle text-success'],
    'revision' => ['label' => 'Perlu Revisi', 'badge' => 'bg-danger-subtle text-danger'],
];
$imageExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$insightFields = [
    'views'            => ['label' => 'Views',         'icon' => 'bi-eye',         'color' => 'info'],
    'reach'            => ['label' => 'Reach',         'icon' => 'bi-broadcast',   'color' => 'primary'],
    'impressions'      => ['label' => 'Impressions',   'icon' => 'bi-bar-chart',   'color' => 'secondary'],
    'likes'            => ['label' => 'Likes',         'icon' => 'bi-heart',       'color' => 'danger'],
    'comments'         => ['label' => 'Komentar',      'icon' => 'bi-chat',        'color' => 'warning'],
    'shares'           => ['label' => 'Share',         'icon' => 'bi-share',       'color' => 'success'],
    'saves'            => ['label' => 'Saves',         'icon' => 'bi-bookmark',    'color' => 'primary'],
    'followers_gained' => ['label' => 'Follower Baru', 'icon' => 'bi-person-plus', 'color' => 'success'],
];
$tipePlaceholders = [
    'master_design' => 'KV Utama, Banner Promo...',
    'digital'       => 'Feed Opening, Story Promo...',
    'cetak'         => 'Spanduk 3x1m, Flyer A5...',
    'influencer'    => 'Nama Influencer',
    'media_prescon' => 'Press Kit, Backdrop Prescon...',
];

// Total standalone count across all tipes
$totalStandalone = 0;
foreach ($byTipe as $tipeItems) {
    foreach ($tipeItems as $item) {
        if ($item['_source'] === 's') $totalStandalone++;
    }
}
?>

<!-- Header -->
<div class="d-flex align-items-center gap-2 mb-4 fade-up" style="animation-delay:.05s">
    <div class="rounded-2 d-flex align-items-center justify-content-center flex-shrink-0"
         style="width:36px;height:36px;background:rgba(99,102,241,.15)">
        <i class="bi bi-palette-fill" style="color:var(--bs-primary);font-size:1rem"></i>
    </div>
    <div>
        <h4 class="fw-bold mb-0">Creative &amp; Design</h4>
        <small class="text-muted">Standalone &amp; dari Event</small>
    </div>
</div>

<!-- KPI strip -->
<div class="row g-3 mb-4">
    <div class="col-sm-4">
        <div class="card border-primary-subtle h-100 fade-up" style="animation-delay:.14s">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <div class="rounded-2 p-1 bg-primary-subtle"><i class="bi bi-palette text-primary fs-5"></i></div>
                    <span class="small text-muted">Item Standalone</span>
                </div>
                <div class="fw-bold fs-4 text-primary" data-count="<?= $totalStandalone ?>"><?= $totalStandalone ?></div>
                <div class="small text-muted">dari <?= count($standaloneItems) + count($eventItems) ?> total</div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card border-danger-subtle h-100 fade-up" style="animation-delay:.24s">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <div class="rounded-2 p-1 bg-danger-subtle"><i class="bi bi-wallet2 text-danger fs-5"></i></div>
                    <span class="small text-muted">Total Budget</span>
                </div>
                <div class="fw-bold fs-4 text-danger">Rp <?= number_format($totalBudget, 0, ',', '.') ?></div>
                <div class="small text-muted">standalone + event</div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <?php
        $realPct   = $totalBudget > 0 ? min(100, round($totalRealisasi / $totalBudget * 100)) : null;
        $realColor = $totalRealisasi > $totalBudget && $totalBudget > 0 ? 'danger' : 'success';
        ?>
        <div class="card border-<?= $realColor ?>-subtle h-100 fade-up" style="animation-delay:.34s">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <div class="rounded-2 p-1 bg-<?= $realColor ?>-subtle"><i class="bi bi-receipt text-<?= $realColor ?> fs-5"></i></div>
                    <span class="small text-muted">Total Realisasi</span>
                </div>
                <div class="fw-bold fs-4 text-<?= $realColor ?>">Rp <?= number_format($totalRealisasi, 0, ',', '.') ?></div>
                <?php if ($realPct !== null): ?>
                <div class="small text-muted"><?= $realPct ?>% dari budget</div>
                <?php else: ?>
                <div class="small text-muted">standalone only</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-3" id="creativeTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active d-flex align-items-center gap-1"
                id="tab-review" data-bs-toggle="tab" data-tab="review"
                type="button" role="tab" style="font-size:.85rem;padding:.4rem .85rem">
            <i class="bi bi-hourglass-split text-warning" style="font-size:.8rem"></i>
            Review
            <?php if (($tabCounts['review'] ?? 0) > 0): ?>
            <span class="badge rounded-pill bg-warning ms-1" style="font-size:.65rem"><?= $tabCounts['review'] ?></span>
            <?php endif; ?>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link d-flex align-items-center gap-1"
                id="tab-realisasi" data-bs-toggle="tab" data-tab="realisasi"
                type="button" role="tab" style="font-size:.85rem;padding:.4rem .85rem">
            <i class="bi bi-receipt-cutoff text-success" style="font-size:.8rem"></i>
            Realisasi
            <?php if (($tabCounts['realisasi'] ?? 0) > 0): ?>
            <span class="badge rounded-pill bg-success ms-1" style="font-size:.65rem"><?= $tabCounts['realisasi'] ?></span>
            <?php endif; ?>
        </button>
    </li>
</ul>

<!-- Flash messages -->
<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success alert-dismissible fade show py-2" role="alert">
    <i class="bi bi-check-circle me-1"></i><?= esc(session()->getFlashdata('success')) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
<div class="alert alert-danger alert-dismissible fade show py-2" role="alert">
    <i class="bi bi-exclamation-triangle me-1"></i><?= esc(session()->getFlashdata('error')) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php foreach ($tipeConfig as $tipe => $cfg):
    $tipeItems = $byTipe[$tipe] ?? [];
    if (empty($tipeItems) && !$canEdit) continue;

    $standaloneOfTipe = array_values(array_filter($tipeItems, fn($i) => $i['_source'] === 's'));
    $eventOfTipe      = array_values(array_filter($tipeItems, fn($i) => $i['_source'] === 'e'));
    $sCount           = count($standaloneOfTipe);
    $eCount           = count($eventOfTipe);
?>

<!-- ═══ TIPE SECTION: <?= $cfg['label'] ?> ═══ -->
<div class="tipe-section" data-tipe="<?= $tipe ?>">
<div class="d-flex align-items-center gap-2 mb-2 mt-4">
    <i class="<?= $cfg['icon'] ?> text-<?= $cfg['color'] ?> fs-5"></i>
    <h6 class="fw-bold mb-0 text-<?= $cfg['color'] ?>"><?= $cfg['label'] ?></h6>
    <?php if ($sCount > 0): ?>
    <span class="badge bg-<?= $cfg['color'] ?> text-white"><?= $sCount ?> standalone</span>
    <?php endif; ?>
    <?php if ($eCount > 0): ?>
    <span class="badge bg-warning text-dark"><?= $eCount ?> event</span>
    <?php endif; ?>
    <?php if ($canEdit): ?>
    <button class="btn ms-auto toggle-add-item"
            style="padding:.2rem .6rem;font-size:.75rem;border:1px solid var(--bs-<?= $cfg['color'] ?>);color:var(--bs-<?= $cfg['color'] ?>)"
            data-tipe="<?= $tipe ?>">
        <i class="bi bi-plus-lg me-1"></i>Tambah Item
    </button>
    <?php endif; ?>
</div>

<?php if ($canEdit): ?>
<!-- Inline add form for this tipe (collapsible) -->
<div id="add-item-<?= $tipe ?>" class="d-none mb-3">
    <div class="card border-<?= $cfg['color'] ?>-subtle">
        <div class="card-body py-2 px-3 <?= $cfg['bg'] ?> bg-opacity-50">
            <form method="POST" action="<?= base_url('creative/add') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="tipe" value="<?= $tipe ?>">
                <div class="row g-2 align-items-end">
                    <div class="col-sm-4">
                        <label class="form-label small fw-semibold mb-1">Nama <span class="text-danger">*</span></label>
                        <input type="text" name="nama" class="form-control form-control-sm" required
                               placeholder="<?= $tipePlaceholders[$tipe] ?? '' ?>">
                    </div>
                    <?php if ($tipe === 'digital'): ?>
                    <div class="col-sm-2">
                        <label class="form-label small fw-semibold mb-1">Platform</label>
                        <select name="platform" class="form-select form-select-sm">
                            <option value="ig">Instagram</option>
                            <option value="tiktok">TikTok</option>
                            <option value="keduanya">IG &amp; TikTok</option>
                        </select>
                    </div>
                    <?php endif; ?>
                    <?php if (in_array($tipe, ['cetak', 'influencer', 'digital', 'media_prescon'])): ?>
                    <div class="col-sm-2">
                        <label class="form-label small fw-semibold mb-1">Budget (Rp)</label>
                        <input type="text" name="budget" class="form-control form-control-sm currency-input" placeholder="0">
                    </div>
                    <?php endif; ?>
                    <div class="col">
                        <label class="form-label small fw-semibold mb-1">Deskripsi / Catatan</label>
                        <input type="text" name="deskripsi" class="form-control form-control-sm" placeholder="Opsional">
                    </div>
                    <div class="col-sm-auto">
                        <button type="submit" class="btn btn-<?= $cfg['color'] ?> btn-sm">Tambah</button>
                    </div>
                </div>
                <?php if ($tipe === 'digital'): ?>
                <div class="row g-2 mt-1">
                    <div class="col-sm-3">
                        <label class="form-label small fw-semibold mb-1"><i class="bi bi-camera me-1"></i>Tanggal Take</label>
                        <input type="date" name="tanggal_take" class="form-control form-control-sm">
                    </div>
                    <div class="col-sm-2">
                        <label class="form-label small fw-semibold mb-1">Waktu Take</label>
                        <input type="time" name="jam_take" class="form-control form-control-sm">
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label small fw-semibold mb-1"><i class="bi bi-person me-1"></i>PIC</label>
                        <input type="text" name="pic" class="form-control form-control-sm" placeholder="Nama PIC">
                    </div>
                </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (empty($tipeItems)): ?>
<div class="card mb-3"><div class="card-body text-center py-3 text-muted small fst-italic">
    Belum ada item <?= $cfg['label'] ?>. <?= $canEdit ? 'Klik "Tambah Item" untuk memulai.' : '' ?>
</div></div>
<?php endif; ?>

<!-- Standalone items for this tipe -->
<?php foreach ($standaloneOfTipe as $item):
    $iid     = $item['id'];
    $iFiles  = $files[$iid]    ?? [];
    $iReal   = $realisasi[$iid] ?? ['total' => 0, 'entries' => []];
    $iInsight= $insights[$iid]  ?? null;
    $iTotal  = (int)($iReal['total'] ?? 0);
    $iBudget = (int)($item['budget'] ?? 0);
    $iPct    = $iBudget > 0 ? min(100, round($iTotal / $iBudget * 100)) : null;
    $iColor  = $iTotal > $iBudget && $iBudget > 0 ? 'danger' : 'success';
    $status  = $item['status'] ?? 'draft';
    $sCfg    = $statusConfig[$status] ?? $statusConfig['draft'];
?>
<div class="card mb-3 border-start border-4 border-primary creative-item-card" data-tab="<?= $item['_tab'] ?>" id="item-<?= $iid ?>-s">
    <!-- Source strip -->
    <div class="card-header py-1 px-3 bg-primary-subtle d-flex align-items-center gap-2" style="font-size:.75rem">
        <i class="bi bi-vector-pen text-primary"></i>
        <span class="fw-semibold text-primary">Creative Standalone</span>
    </div>
    <div class="card-body pb-2">
        <div class="d-flex justify-content-between align-items-start gap-3">
            <!-- Left: info -->
            <div class="flex-grow-1 min-w-0">
                <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                    <?php if ($tipe === 'master_design'): ?>
                    <span class="badge <?= $sCfg['badge'] ?>" style="font-size:.65rem"><?= $sCfg['label'] ?></span>
                    <?php endif; ?>
                    <?php if ($tipe === 'digital' && ($item['platform'] ?? '')): ?>
                    <span class="badge bg-info-subtle text-info" style="font-size:.65rem">
                        <i class="bi bi-<?= $item['platform'] === 'ig' ? 'instagram' : ($item['platform'] === 'tiktok' ? 'tiktok' : 'phone') ?> me-1"></i><?= $platformLabels[$item['platform']] ?? $item['platform'] ?>
                    </span>
                    <?php endif; ?>
                    <span class="fw-semibold"><?= esc($item['nama']) ?></span>
                </div>
                <?php if ($tipe === 'digital' && (($item['tanggal_take'] ?? '') || ($item['jam_take'] ?? '') || ($item['pic'] ?? ''))): ?>
                <div class="small text-muted mb-1">
                    <?php if ($item['tanggal_take'] || $item['jam_take']): ?>
                    <i class="bi bi-camera me-1"></i>Take:
                    <?php if ($item['tanggal_take']): ?><strong class="text-body"><?= date('d M Y', strtotime($item['tanggal_take'])) ?></strong><?php endif; ?>
                    <?php if ($item['jam_take']): ?> <?= substr($item['jam_take'], 0, 5) ?><?php endif; ?>
                    <?php endif; ?>
                    <?php if ($item['pic'] ?? ''): ?>
                    <?php if ($item['tanggal_take'] || $item['jam_take']): ?>&nbsp;·&nbsp;<?php endif; ?>
                    <i class="bi bi-person me-1"></i><strong class="text-body"><?= esc($item['pic']) ?></strong>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php if ($item['deskripsi'] ?? ''): ?>
                <p class="small text-muted mb-1" style="white-space:pre-line"><?= esc($item['deskripsi']) ?></p>
                <?php endif; ?>
                <?php if ($iBudget > 0): ?>
                <div class="small text-muted">
                    <i class="bi bi-cash me-1"></i>Budget: <strong class="text-body">Rp <?= number_format($iBudget, 0, ',', '.') ?></strong>
                    <?php if ($iTotal > 0): ?>
                    · Realisasi: <strong class="text-<?= $iColor ?>">Rp <?= number_format($iTotal, 0, ',', '.') ?></strong>
                    <?php if ($iPct !== null): ?><span class="text-<?= $iColor ?>">(<?= $iPct ?>%)</span><?php endif; ?>
                    <?php endif; ?>
                </div>
                <?php if ($iPct !== null && $iTotal > 0): ?>
                <div class="progress mt-1" style="height:3px;max-width:200px">
                    <div class="progress-bar bg-<?= $iColor ?>" style="width:<?= min(100, $iPct) ?>%"></div>
                </div>
                <?php endif; ?>
                <?php endif; ?>
                <?php if ($item['catatan'] ?? ''): ?>
                <div class="small text-muted mt-1"><i class="bi bi-sticky me-1"></i><?= esc($item['catatan']) ?></div>
                <?php endif; ?>
            </div>

            <!-- Right: action buttons -->
            <div class="d-flex gap-1 flex-shrink-0 align-items-start">
                <?php if ($tipe === 'master_design' && $canApprove): ?>
                <div class="dropdown">
                    <button class="btn btn-xs btn-outline-secondary dropdown-toggle" style="padding:.2rem .5rem;font-size:.72rem"
                            data-bs-toggle="dropdown">Status</button>
                    <ul class="dropdown-menu dropdown-menu-end" style="font-size:.8rem;min-width:160px">
                        <?php foreach ($statusConfig as $sVal => $sCfgOpt): ?>
                        <?php if ($sVal !== $status): ?>
                        <li>
                            <form method="POST" action="<?= base_url('creative/' . $iid . '/status') ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="status" value="<?= $sVal ?>">
                                <button type="submit" class="dropdown-item"><?= $sCfgOpt['label'] ?></button>
                            </form>
                        </li>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php elseif ($tipe === 'master_design' && $canEdit && $status === 'draft'): ?>
                <form method="POST" action="<?= base_url('creative/' . $iid . '/status') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="status" value="review">
                    <button type="submit" class="btn btn-xs btn-outline-warning" style="padding:.2rem .5rem;font-size:.72rem"
                            title="Ajukan untuk review">
                        <i class="bi bi-send me-1"></i>Review
                    </button>
                </form>
                <?php endif; ?>
                <?php if ($canEdit): ?>
                <button class="btn btn-xs btn-outline-secondary edit-item-btn"
                        style="padding:.2rem .5rem;font-size:.72rem"
                        data-id="<?= $iid ?>"
                        data-nama="<?= esc($item['nama'], 'attr') ?>"
                        data-tipe="<?= $tipe ?>"
                        data-platform="<?= $item['platform'] ?? '' ?>"
                        data-tanggal-take="<?= $item['tanggal_take'] ?? '' ?>"
                        data-jam-take="<?= substr($item['jam_take'] ?? '', 0, 5) ?>"
                        data-pic="<?= esc($item['pic'] ?? '', 'attr') ?>"
                        data-deskripsi="<?= esc($item['deskripsi'] ?? '', 'attr') ?>"
                        data-budget="<?= number_format($iBudget, 0, ',', '.') ?>"
                        data-catatan="<?= esc($item['catatan'] ?? '', 'attr') ?>">
                    <i class="bi bi-pencil"></i>
                </button>
                <form method="POST" action="<?= base_url('creative/' . $iid . '/delete') ?>"
                      onsubmit="return confirm('Hapus item ini beserta semua file dan realisasinya?')">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-xs btn-outline-danger" style="padding:.2rem .5rem;font-size:.72rem">
                        <i class="bi bi-trash"></i>
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sub-section toggle buttons -->
        <?php $hasToggles = ($tipe === 'master_design') || in_array($tipe, ['cetak', 'influencer', 'digital', 'media_prescon']); ?>
        <?php if ($hasToggles && ($canEdit || !empty($iFiles) || !empty($iReal['entries']) || $iInsight)): ?>
        <div class="d-flex flex-wrap gap-1 mt-2 pt-2 border-top">
            <?php if ($tipe === 'master_design'): ?>
            <button class="btn btn-xs btn-outline-primary toggle-files"
                    style="padding:.2rem .5rem;font-size:.72rem"
                    data-iid="<?= $iid ?>">
                <i class="bi bi-paperclip me-1"></i>File
                <?php if (!empty($iFiles)): ?><span class="badge bg-primary-subtle text-primary ms-1" style="font-size:.65rem"><?= count($iFiles) ?></span><?php endif; ?>
            </button>
            <?php endif; ?>
            <?php if (in_array($tipe, ['cetak', 'influencer', 'digital', 'media_prescon'])): ?>
            <button class="btn btn-xs btn-outline-secondary toggle-realisasi"
                    style="padding:.2rem .5rem;font-size:.72rem"
                    data-iid="<?= $iid ?>">
                <i class="bi bi-receipt me-1"></i>Realisasi
                <?php if ($iTotal > 0): ?><span class="badge bg-secondary-subtle text-secondary ms-1" style="font-size:.65rem">Rp <?= number_format($iTotal, 0, ',', '.') ?></span><?php endif; ?>
            </button>
            <?php endif; ?>
            <?php if ($tipe === 'digital'): ?>
            <button class="btn btn-xs btn-outline-info toggle-insight"
                    style="padding:.2rem .5rem;font-size:.72rem"
                    data-iid="<?= $iid ?>">
                <i class="bi bi-graph-up me-1"></i>Insight
                <?php if ($iInsight && !empty($iInsight['entries'])): ?><span class="badge bg-info-subtle text-info ms-1" style="font-size:.65rem"><?= count($iInsight['entries']) ?> entri</span><?php endif; ?>
            </button>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($tipe === 'master_design'): ?>
    <!-- ── File section (master_design) ── -->
    <div id="files-<?= $iid ?>" class="d-none border-top">
        <div class="px-3 py-2 bg-primary-subtle d-flex justify-content-between align-items-center">
            <span class="small fw-semibold text-primary">
                <i class="bi bi-paperclip me-1"></i>File Referensi
                <?php if (!empty($iFiles)): ?>
                <span class="badge bg-primary text-white ms-1"><?= count($iFiles) ?></span>
                <?php endif; ?>
            </span>
        </div>
        <div class="px-3 py-2">
            <?php if (!empty($iFiles)): ?>
            <div class="d-flex flex-wrap gap-2 mb-2">
                <?php foreach ($iFiles as $f):
                    $ext     = strtolower(pathinfo($f['file_name'], PATHINFO_EXTENSION));
                    $isImage = in_array($ext, $imageExts);
                    $fileUrl = base_url('uploads/creative-standalone/' . $iid . '/' . $f['file_name']);
                ?>
                <div class="border rounded-2 p-1 bg-white text-center" style="min-width:80px;max-width:120px">
                    <?php if ($isImage): ?>
                    <a href="<?= $fileUrl ?>" target="_blank">
                        <img src="<?= $fileUrl ?>" alt="<?= esc($f['original_name']) ?>"
                             class="rounded-1 d-block mb-1"
                             style="width:100%;height:70px;object-fit:cover">
                    </a>
                    <?php else: ?>
                    <a href="<?= $fileUrl ?>" target="_blank" class="d-block mb-1 text-muted" style="font-size:2rem">
                        <i class="bi bi-file-earmark-<?= $ext === 'pdf' ? 'pdf text-danger' : 'fill' ?>"></i>
                    </a>
                    <?php endif; ?>
                    <div class="text-muted" style="font-size:.6rem;line-height:1.2;word-break:break-all"><?= esc($f['original_name']) ?></div>
                    <?php if ($canEdit): ?>
                    <form method="POST" action="<?= base_url('creative/' . $iid . '/file/' . $f['id'] . '/delete') ?>"
                          onsubmit="return confirm('Hapus file ini?')" class="mt-1">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-xs btn-outline-danger w-100" style="padding:.1rem .3rem;font-size:.6rem">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php elseif (!$canEdit): ?>
            <div class="small text-muted fst-italic">Belum ada file diupload.</div>
            <?php endif; ?>
            <?php if ($canEdit): ?>
            <form method="POST" action="<?= base_url('creative/' . $iid . '/upload') ?>" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="d-flex gap-2 align-items-center">
                    <input type="file" name="file" class="form-control form-control-sm" style="max-width:280px"
                           accept="image/*,.pdf,.psd,.ai,.zip,.rar">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-upload me-1"></i>Upload</button>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; // master_design files ?>

    <?php if (in_array($tipe, ['cetak', 'influencer', 'digital', 'media_prescon'])): ?>
    <!-- ── Realisasi section ── -->
    <div id="realisasi-<?= $iid ?>" class="d-none border-top">
        <div class="px-3 py-2 bg-light d-flex justify-content-between align-items-center">
            <span class="small fw-semibold text-muted">
                <i class="bi bi-receipt me-1"></i>Realisasi Biaya
                <?php if ($iTotal > 0): ?>
                <span class="badge bg-secondary-subtle text-secondary ms-1">Rp <?= number_format($iTotal, 0, ',', '.') ?></span>
                <?php endif; ?>
                <?php if (!empty($iReal['entries'])): ?>
                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle ms-1"><?= count($iReal['entries']) ?> entri</span>
                <?php endif; ?>
            </span>
            <?php if ($canEdit): ?>
            <button class="btn btn-xs btn-outline-primary toggle-add-realisasi"
                    style="padding:.2rem .6rem;font-size:.75rem"
                    data-iid="<?= $iid ?>">
                <i class="bi bi-plus-lg me-1"></i>Input
            </button>
            <?php endif; ?>
        </div>

        <?php if ($canEdit): ?>
        <div id="add-realisasi-<?= $iid ?>" class="d-none px-3 py-2 border-bottom bg-white">
            <form method="POST" action="<?= base_url('creative/' . $iid . '/realisasi/add') ?>"
                  <?= in_array($tipe, ['influencer', 'cetak', 'media_prescon']) ? 'enctype="multipart/form-data"' : '' ?>>
                <?= csrf_field() ?>
                <div class="row g-2 align-items-end">
                    <div class="col-sm-2">
                        <label class="form-label small fw-semibold mb-1">Tanggal</label>
                        <input type="date" name="tanggal" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label small fw-semibold mb-1">Nilai (Rp) <span class="text-danger">*</span></label>
                        <input type="text" name="nilai" class="form-control form-control-sm currency-input" placeholder="0" required>
                    </div>
                    <?php if ($tipe === 'influencer'): ?>
                    <div class="col-sm-3">
                        <label class="form-label small fw-semibold mb-1">Nama Influencer <span class="text-danger">*</span></label>
                        <input type="text" name="nama_influencer" class="form-control form-control-sm" placeholder="@username" required>
                    </div>
                    <?php endif; ?>
                    <div class="col">
                        <label class="form-label small fw-semibold mb-1">Catatan</label>
                        <input type="text" name="catatan" class="form-control form-control-sm" placeholder="Opsional">
                    </div>
                    <div class="col-sm-auto">
                        <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                    </div>
                </div>
                <?php if ($tipe === 'influencer'): ?>
                <div class="row g-2 mt-1">
                    <div class="col-sm-5">
                        <label class="form-label small fw-semibold mb-1">
                            <i class="bi bi-image me-1"></i>Screenshot Insight
                            <span class="fw-normal text-muted">(opsional)</span>
                        </label>
                        <input type="file" name="bukti" class="form-control form-control-sm" accept="image/*">
                    </div>
                    <div class="col-sm-5">
                        <label class="form-label small fw-semibold mb-1">
                            <i class="bi bi-file-earmark-check me-1"></i>Bukti Serah Terima
                            <span class="fw-normal text-muted">(opsional)</span>
                        </label>
                        <input type="file" name="serah_terima" class="form-control form-control-sm" accept="image/*,.pdf">
                    </div>
                </div>
                <?php endif; ?>
                <?php if ($tipe === 'cetak'): ?>
                <div class="row g-2 mt-1">
                    <div class="col-sm-5">
                        <label class="form-label small fw-semibold mb-1">
                            <i class="bi bi-image me-1"></i>Bukti Terpasang
                            <span class="fw-normal text-muted">(opsional)</span>
                        </label>
                        <input type="file" name="bukti_terpasang" class="form-control form-control-sm" accept="image/*,.pdf">
                    </div>
                </div>
                <?php endif; ?>
                <?php if ($tipe === 'media_prescon'): ?>
                <div class="row g-2 mt-1">
                    <div class="col-sm-5">
                        <label class="form-label small fw-semibold mb-1">
                            <i class="bi bi-image me-1"></i>Dokumentasi
                            <span class="fw-normal text-muted">(opsional)</span>
                        </label>
                        <input type="file" name="bukti" class="form-control form-control-sm" accept="image/*,.pdf">
                    </div>
                </div>
                <?php endif; ?>
            </form>
        </div>
        <?php endif; ?>

        <?php if (!empty($iReal['entries'])): ?>
        <div class="table-responsive">
        <table class="table table-sm mb-0" style="font-size:.8rem">
        <thead class="table-light">
            <tr>
                <th class="ps-3">Tanggal</th>
                <?php if ($tipe === 'influencer'): ?><th>Influencer</th><?php endif; ?>
                <th>Nilai</th>
                <th>Catatan</th>
                <?php if ($tipe === 'influencer'): ?>
                <th title="Screenshot Insight"><i class="bi bi-image text-muted"></i></th>
                <th title="Bukti Serah Terima"><i class="bi bi-file-earmark-check text-muted"></i></th>
                <?php endif; ?>
                <?php if ($tipe === 'cetak'): ?>
                <th title="Bukti Terpasang"><i class="bi bi-image text-muted"></i></th>
                <?php endif; ?>
                <?php if ($tipe === 'media_prescon'): ?>
                <th title="Dokumentasi"><i class="bi bi-image text-muted"></i></th>
                <?php endif; ?>
                <?php if ($canEdit): ?><th style="width:40px"></th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($iReal['entries'] as $e): ?>
        <tr>
            <td class="ps-3 small"><?= date('d M Y', strtotime($e['tanggal'])) ?></td>
            <?php if ($tipe === 'influencer'): ?>
            <td class="small fw-medium"><?= esc($e['nama_influencer'] ?? '—') ?></td>
            <?php endif; ?>
            <td class="small fw-semibold text-success">Rp <?= number_format($e['nilai'], 0, ',', '.') ?></td>
            <td class="small text-muted"><?= esc($e['catatan'] ?? '') ?></td>
            <?php if ($tipe === 'influencer'): ?>
            <?php
                $fileColumns = [
                    ['file' => $e['file_name'],              'orig' => $e['original_name']],
                    ['file' => $e['serah_terima_file_name'], 'orig' => $e['serah_terima_original_name']],
                ];
            ?>
            <?php foreach ($fileColumns as $fc): ?>
            <td>
                <?php if ($fc['file']): ?>
                <?php $fUrl = base_url('uploads/creative-standalone-realisasi/' . $iid . '/' . $fc['file']);
                      $fExt = strtolower(pathinfo($fc['file'], PATHINFO_EXTENSION)); ?>
                <?php if (in_array($fExt, $imageExts)): ?>
                <a href="<?= $fUrl ?>" target="_blank">
                    <img src="<?= $fUrl ?>" alt="" style="height:32px;width:auto;border-radius:4px;object-fit:cover"
                         title="<?= esc($fc['orig']) ?>">
                </a>
                <?php else: ?>
                <a href="<?= $fUrl ?>" target="_blank" class="text-muted" title="<?= esc($fc['orig']) ?>">
                    <i class="bi bi-file-earmark-<?= $fExt === 'pdf' ? 'pdf text-danger' : 'fill' ?>" style="font-size:1.1rem"></i>
                </a>
                <?php endif; ?>
                <?php else: ?>
                <span class="text-muted">—</span>
                <?php endif; ?>
            </td>
            <?php endforeach; ?>
            <?php endif; ?>
            <?php if ($tipe === 'cetak'): ?>
            <td>
                <?php if ($e['bukti_terpasang_file_name']): ?>
                <?php $fUrl = base_url('uploads/creative-standalone-realisasi/' . $iid . '/' . $e['bukti_terpasang_file_name']);
                      $fExt = strtolower(pathinfo($e['bukti_terpasang_file_name'], PATHINFO_EXTENSION)); ?>
                <?php if (in_array($fExt, $imageExts)): ?>
                <a href="<?= $fUrl ?>" target="_blank">
                    <img src="<?= $fUrl ?>" alt="" style="height:32px;width:auto;border-radius:4px;object-fit:cover"
                         title="<?= esc($e['bukti_terpasang_original_name']) ?>">
                </a>
                <?php else: ?>
                <a href="<?= $fUrl ?>" target="_blank" class="text-muted" title="<?= esc($e['bukti_terpasang_original_name']) ?>">
                    <i class="bi bi-file-earmark-<?= $fExt === 'pdf' ? 'pdf text-danger' : 'fill' ?>" style="font-size:1.1rem"></i>
                </a>
                <?php endif; ?>
                <?php else: ?>
                <span class="text-muted">—</span>
                <?php endif; ?>
            </td>
            <?php endif; ?>
            <?php if ($tipe === 'media_prescon'): ?>
            <td>
                <?php if ($e['file_name']): ?>
                <?php $fUrl = base_url('uploads/creative-standalone-realisasi/' . $iid . '/' . $e['file_name']);
                      $fExt = strtolower(pathinfo($e['file_name'], PATHINFO_EXTENSION)); ?>
                <?php if (in_array($fExt, $imageExts)): ?>
                <a href="<?= $fUrl ?>" target="_blank">
                    <img src="<?= $fUrl ?>" alt="" style="height:32px;width:auto;border-radius:4px;object-fit:cover"
                         title="<?= esc($e['original_name']) ?>">
                </a>
                <?php else: ?>
                <a href="<?= $fUrl ?>" target="_blank" class="text-muted" title="<?= esc($e['original_name']) ?>">
                    <i class="bi bi-file-earmark-<?= $fExt === 'pdf' ? 'pdf text-danger' : 'fill' ?>" style="font-size:1.1rem"></i>
                </a>
                <?php endif; ?>
                <?php else: ?>
                <span class="text-muted">—</span>
                <?php endif; ?>
            </td>
            <?php endif; ?>
            <?php if ($canEdit): ?>
            <td>
                <form method="POST" action="<?= base_url('creative/' . $iid . '/realisasi/' . $e['id'] . '/delete') ?>"
                      onsubmit="return confirm('Hapus entri ini?')">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-xs btn-outline-danger" style="padding:.15rem .4rem;font-size:.7rem">
                        <i class="bi bi-trash"></i>
                    </button>
                </form>
            </td>
            <?php endif; ?>
        </tr>
        <?php endforeach; ?>
        <tr class="table-light fw-semibold">
            <td class="ps-3 small" colspan="<?= $tipe === 'influencer' ? 2 : 1 ?>">Total</td>
            <td class="small text-success">Rp <?= number_format($iTotal, 0, ',', '.') ?></td>
            <td colspan="<?= ($tipe === 'influencer' ? 2 : 0) + ($canEdit ? 2 : 1) ?>"></td>
        </tr>
        </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; // realisasi section ?>

    <?php if ($tipe === 'digital'): ?>
    <!-- ── Insight section ── -->
    <div id="insight-<?= $iid ?>" class="d-none border-top">
        <div class="px-3 py-2 bg-info-subtle d-flex justify-content-between align-items-center">
            <span class="small fw-semibold text-info-emphasis">
                <i class="bi bi-graph-up me-1"></i>Insight
                <?php if ($iInsight && !empty($iInsight['entries'])): ?>
                <span class="badge bg-info text-white ms-1"><?= count($iInsight['entries']) ?> entri</span>
                <?php endif; ?>
            </span>
            <?php if ($canEdit): ?>
            <button class="btn btn-xs btn-outline-info toggle-add-insight"
                    style="padding:.2rem .6rem;font-size:.75rem"
                    data-iid="<?= $iid ?>">
                <i class="bi bi-plus-lg me-1"></i>Input
            </button>
            <?php endif; ?>
        </div>

        <?php if ($iInsight): ?>
        <div class="px-3 py-2 d-flex flex-wrap gap-2">
            <?php
            $insightMetricMap = [
                'views'            => $iInsight['max_views']              ?? 0,
                'reach'            => $iInsight['max_reach']              ?? 0,
                'impressions'      => $iInsight['max_impressions']        ?? 0,
                'likes'            => $iInsight['max_likes']              ?? 0,
                'comments'         => $iInsight['max_comments']           ?? 0,
                'shares'           => $iInsight['max_shares']             ?? 0,
                'saves'            => $iInsight['max_saves']              ?? 0,
                'followers_gained' => $iInsight['total_followers_gained'] ?? 0,
            ];
            foreach ($insightFields as $field => $fcfg):
                $val = $insightMetricMap[$field] ?? 0;
                if ($val <= 0) continue;
            ?>
            <div class="d-flex align-items-center gap-1 px-2 py-1 rounded-2 bg-<?= $fcfg['color'] ?>-subtle">
                <i class="<?= $fcfg['icon'] ?> text-<?= $fcfg['color'] ?>" style="font-size:.8rem"></i>
                <span class="small fw-semibold text-<?= $fcfg['color'] ?>"><?= number_format($val, 0, ',', '.') ?></span>
                <span class="text-muted" style="font-size:.7rem"><?= $fcfg['label'] ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ($canEdit): ?>
        <div id="add-insight-<?= $iid ?>" class="d-none px-3 py-2 border-top border-bottom bg-white">
            <form method="POST" action="<?= base_url('creative/' . $iid . '/insight/add') ?>"
                  enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="row g-2 mb-2 align-items-end">
                    <div class="col-sm-2">
                        <label class="form-label small fw-semibold mb-1">Tanggal</label>
                        <input type="date" name="tanggal" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <?php if (($item['platform'] ?? '') === 'keduanya'): ?>
                    <div class="col-sm-2">
                        <label class="form-label small fw-semibold mb-1">Platform</label>
                        <select name="platform" class="form-select form-select-sm">
                            <option value="ig">Instagram</option>
                            <option value="tiktok">TikTok</option>
                        </select>
                    </div>
                    <?php else: ?>
                    <input type="hidden" name="platform" value="<?= $item['platform'] ?? '' ?>">
                    <?php endif; ?>
                    <div class="col-sm-4">
                        <label class="form-label small fw-semibold mb-1">
                            <i class="bi bi-image me-1"></i>Screenshot
                            <span class="fw-normal text-muted">(opsional)</span>
                        </label>
                        <input type="file" name="screenshot" class="form-control form-control-sm" accept="image/*">
                    </div>
                    <div class="col">
                        <label class="form-label small fw-semibold mb-1">Catatan</label>
                        <input type="text" name="catatan" class="form-control form-control-sm" placeholder="Opsional">
                    </div>
                </div>
                <div class="row g-2 mb-2">
                    <?php foreach ($insightFields as $field => $fcfg): ?>
                    <div class="col-6 col-sm-3">
                        <label class="form-label small fw-semibold mb-1">
                            <i class="<?= $fcfg['icon'] ?> text-<?= $fcfg['color'] ?> me-1"></i><?= $fcfg['label'] ?>
                        </label>
                        <input type="number" name="<?= $field ?>" class="form-control form-control-sm" value="0" min="0">
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="text-end">
                    <button type="submit" class="btn btn-info btn-sm text-white"><i class="bi bi-save me-1"></i>Simpan Insight</button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <?php if ($iInsight && !empty($iInsight['entries'])): ?>
        <div class="table-responsive">
        <table class="table table-sm mb-0" style="font-size:.75rem">
        <thead class="table-light">
            <tr>
                <th class="ps-3">Tanggal</th>
                <?php if (($item['platform'] ?? '') === 'keduanya'): ?><th>Platform</th><?php endif; ?>
                <?php foreach ($insightFields as $field => $fcfg): ?>
                <th class="text-end" title="<?= $fcfg['label'] ?>">
                    <i class="<?= $fcfg['icon'] ?> text-<?= $fcfg['color'] ?>"></i>
                </th>
                <?php endforeach; ?>
                <th><i class="bi bi-image text-muted"></i></th>
                <th>Catatan</th>
                <?php if ($canEdit): ?><th style="width:40px"></th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($iInsight['entries'] as $ins): ?>
        <tr>
            <td class="ps-3 small"><?= date('d M Y', strtotime($ins['tanggal'])) ?></td>
            <?php if (($item['platform'] ?? '') === 'keduanya'): ?>
            <td class="small">
                <span class="badge bg-info-subtle text-info" style="font-size:.65rem">
                    <?= $platformLabels[$ins['platform']] ?? $ins['platform'] ?>
                </span>
            </td>
            <?php endif; ?>
            <?php foreach ($insightFields as $field => $fcfg): ?>
            <td class="small text-end <?= $ins[$field] > 0 ? 'fw-medium text-' . $fcfg['color'] : 'text-muted' ?>">
                <?= $ins[$field] > 0 ? number_format($ins[$field], 0, ',', '.') : '—' ?>
            </td>
            <?php endforeach; ?>
            <td>
                <?php if ($ins['file_name']): ?>
                <a href="<?= base_url('uploads/creative-standalone-insight/' . $iid . '/' . $ins['file_name']) ?>" target="_blank">
                    <img src="<?= base_url('uploads/creative-standalone-insight/' . $iid . '/' . $ins['file_name']) ?>"
                         alt="ss" style="height:32px;width:auto;border-radius:4px;object-fit:cover"
                         title="<?= esc($ins['original_name'] ?? '') ?>">
                </a>
                <?php else: ?>
                <span class="text-muted">—</span>
                <?php endif; ?>
            </td>
            <td class="small text-muted"><?= esc($ins['catatan'] ?? '') ?></td>
            <?php if ($canEdit): ?>
            <td>
                <form method="POST" action="<?= base_url('creative/' . $iid . '/insight/' . $ins['id'] . '/delete') ?>"
                      onsubmit="return confirm('Hapus entri insight ini?')">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-xs btn-outline-danger" style="padding:.15rem .4rem;font-size:.7rem">
                        <i class="bi bi-trash"></i>
                    </button>
                </form>
            </td>
            <?php endif; ?>
        </tr>
        <?php endforeach; ?>
        </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; // tipe === digital ?>

</div>
<?php endforeach; // standaloneOfTipe ?>

<?php if (!empty($eventOfTipe)): ?>
<!-- "Dari Event" divider -->
<div class="d-flex align-items-center gap-2 my-3 dari-event-divider">
    <span class="text-muted" style="font-size:.7rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase">Dari Event</span>
    <div class="flex-grow-1 border-top"></div>
</div>

<!-- Event items for this tipe -->
<?php foreach ($eventOfTipe as $item):
    $iBudget = (int)($item['budget'] ?? 0);
    $status  = $item['status'] ?? 'draft';
    $sCfg    = $statusConfig[$status] ?? $statusConfig['draft'];
?>
<div class="card mb-3 border-start border-4 border-warning creative-item-card" data-tab="<?= $item['_tab'] ?>" id="item-e-<?= $item['id'] ?>">
    <!-- Source strip -->
    <div class="card-header py-1 px-3 bg-warning-subtle d-flex align-items-center gap-2" style="font-size:.75rem">
        <i class="bi bi-calendar-event text-warning-emphasis"></i>
        <span class="fw-semibold text-warning-emphasis">Creative Event</span>
        <span class="text-warning-emphasis ms-1">· <?= esc($item['event_name'] ?? '') ?></span>
    </div>
    <div class="card-body pb-2">
        <div class="d-flex justify-content-between align-items-start gap-3">
            <!-- Left: info (read-only) -->
            <div class="flex-grow-1 min-w-0">
                <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                    <?php if ($tipe === 'master_design'): ?>
                    <span class="badge <?= $sCfg['badge'] ?>" style="font-size:.65rem"><?= $sCfg['label'] ?></span>
                    <?php endif; ?>
                    <?php if ($tipe === 'digital' && ($item['platform'] ?? '')): ?>
                    <span class="badge bg-info-subtle text-info" style="font-size:.65rem">
                        <i class="bi bi-<?= $item['platform'] === 'ig' ? 'instagram' : ($item['platform'] === 'tiktok' ? 'tiktok' : 'phone') ?> me-1"></i><?= $platformLabels[$item['platform']] ?? $item['platform'] ?>
                    </span>
                    <?php endif; ?>
                    <span class="fw-semibold"><?= esc($item['nama']) ?></span>
                </div>
                <?php if ($tipe === 'digital' && (($item['tanggal_take'] ?? '') || ($item['jam_take'] ?? '') || ($item['pic'] ?? ''))): ?>
                <div class="small text-muted mb-1">
                    <?php if ($item['tanggal_take'] || $item['jam_take']): ?>
                    <i class="bi bi-camera me-1"></i>Take:
                    <?php if ($item['tanggal_take']): ?><strong class="text-body"><?= date('d M Y', strtotime($item['tanggal_take'])) ?></strong><?php endif; ?>
                    <?php if ($item['jam_take']): ?> <?= substr($item['jam_take'], 0, 5) ?><?php endif; ?>
                    <?php endif; ?>
                    <?php if ($item['pic'] ?? ''): ?>
                    <?php if ($item['tanggal_take'] || $item['jam_take']): ?>&nbsp;·&nbsp;<?php endif; ?>
                    <i class="bi bi-person me-1"></i><strong class="text-body"><?= esc($item['pic']) ?></strong>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php if ($item['deskripsi'] ?? ''): ?>
                <p class="small text-muted mb-1" style="white-space:pre-line"><?= esc($item['deskripsi']) ?></p>
                <?php endif; ?>
                <?php if ($iBudget > 0): ?>
                <div class="small text-muted">
                    <i class="bi bi-cash me-1"></i>Budget: <strong class="text-body">Rp <?= number_format($iBudget, 0, ',', '.') ?></strong>
                </div>
                <?php endif; ?>
                <?php if ($item['catatan'] ?? ''): ?>
                <div class="small text-muted mt-1"><i class="bi bi-sticky me-1"></i><?= esc($item['catatan']) ?></div>
                <?php endif; ?>
            </div>
            <!-- Right: Kelola di Event -->
            <div class="flex-shrink-0">
                <a href="<?= base_url('events/' . $item['event_id'] . '/creative') ?>"
                   class="btn btn-xs btn-outline-warning" style="padding:.2rem .6rem;font-size:.72rem">
                    <i class="bi bi-box-arrow-up-right me-1"></i>Kelola di Event
                </a>
            </div>
        </div>
    </div>
    <div class="border-top px-3 py-2 text-center">
        <a href="<?= base_url('events/' . $item['event_id'] . '/creative') ?>" class="small text-muted">
            <i class="bi bi-box-arrow-up-right me-1"></i>Kelola di halaman event
        </a>
    </div>
</div>
<?php endforeach; // eventOfTipe ?>
<?php endif; // has event items ?>

</div><?php // close .tipe-section ?>
<?php endforeach; // tipeConfig ?>

<!-- Footer -->
<div class="card mt-3">
    <div class="card-body py-2 d-flex justify-content-between align-items-center">
        <span class="small text-muted"><?= $totalStandalone ?> standalone · <?= count($eventItems) ?> dari event</span>
        <span class="fw-bold">Total Budget: <span class="text-danger">Rp <?= number_format($totalBudget, 0, ',', '.') ?></span></span>
    </div>
</div>

<?php if ($canEdit): ?>
<!-- Edit Item Modal -->
<div class="modal fade" id="editItemModal" tabindex="-1">
<div class="modal-dialog"><div class="modal-content">
<form id="editItemForm" method="POST">
<?= csrf_field() ?>
<div class="modal-header">
    <h5 class="modal-title fw-semibold">Edit Item</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
    <div class="mb-3">
        <label class="form-label small fw-semibold">Tipe</label>
        <div id="editTipeDisplay" class="form-control form-control-sm bg-light text-muted" style="cursor:default"></div>
    </div>
    <div class="mb-3">
        <label class="form-label small fw-semibold">Nama <span class="text-danger">*</span></label>
        <input type="text" name="nama" id="editNama" class="form-control" required>
    </div>
    <div id="editPlatformDiv" class="mb-3 d-none">
        <label class="form-label small fw-semibold">Platform</label>
        <select name="platform" id="editPlatform" class="form-select">
            <option value="ig">Instagram</option>
            <option value="tiktok">TikTok</option>
            <option value="keduanya">IG &amp; TikTok</option>
        </select>
    </div>
    <div id="editTakeDiv" class="mb-3 d-none">
        <label class="form-label small fw-semibold"><i class="bi bi-camera me-1"></i>Tanggal &amp; Waktu Take</label>
        <div class="row g-2">
            <div class="col-7">
                <input type="date" name="tanggal_take" id="editTanggalTake" class="form-control">
            </div>
            <div class="col-5">
                <input type="time" name="jam_take" id="editJamTake" class="form-control">
            </div>
        </div>
    </div>
    <div id="editPicDiv" class="mb-3 d-none">
        <label class="form-label small fw-semibold"><i class="bi bi-person me-1"></i>PIC</label>
        <input type="text" name="pic" id="editPic" class="form-control" placeholder="Nama PIC">
    </div>
    <div id="editBudgetDiv" class="mb-3">
        <label class="form-label small fw-semibold">Budget (Rp)</label>
        <input type="text" name="budget" id="editBudget" class="form-control currency-input" value="0">
    </div>
    <div class="mb-3">
        <label class="form-label small fw-semibold">Deskripsi</label>
        <textarea name="deskripsi" id="editDeskripsi" class="form-control" rows="2"></textarea>
    </div>
    <div class="mb-0">
        <label class="form-label small fw-semibold">Catatan</label>
        <input type="text" name="catatan" id="editCatatan" class="form-control">
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
    <button type="submit" class="btn btn-primary">Simpan</button>
</div>
</form>
</div></div></div>
<?php endif; ?>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
// KPI count-up
(function() {
    const dur = 900;
    document.querySelectorAll('[data-count]').forEach(el => {
        const target = parseInt(el.dataset.count) || 0;
        if (!target) return;
        const start = performance.now();
        function step(now) {
            const t = Math.min(1, (now - start) / dur);
            const ease = 1 - Math.pow(1 - t, 3);
            el.textContent = Math.round(ease * target).toLocaleString('id-ID');
            if (t < 1) requestAnimationFrame(step);
        }
        requestAnimationFrame(step);
    });
})();

// Item card stagger (visible cards on initial load)
document.querySelectorAll('.creative-item-card:not(.d-none)').forEach((card, i) => {
    card.style.opacity = '0';
    card.style.transform = 'translateY(14px)';
    setTimeout(() => {
        card.style.transition = 'opacity .4s ease, transform .4s ease';
        card.style.opacity = '1';
        card.style.transform = 'translateY(0)';
    }, 120 + i * 55);
});

// Progress bar animation
document.querySelectorAll('.progress-bar').forEach((bar, i) => {
    const target = bar.style.width;
    if (!target || parseFloat(target) === 0) return;
    bar.style.width = '0';
    setTimeout(() => {
        bar.style.transition = 'width .6s ease';
        bar.style.width = target;
    }, 350 + i * 40);
});
</script>
<script>
<?php if ($canEdit): ?>
// Toggle inline add-item forms (per tipe)
document.querySelectorAll('.toggle-add-item').forEach(btn => {
    btn.addEventListener('click', function () {
        const tipe = this.dataset.tipe;
        const el   = document.getElementById('add-item-' + tipe);
        el.classList.toggle('d-none');
        this.innerHTML = el.classList.contains('d-none')
            ? '<i class="bi bi-plus-lg me-1"></i>Tambah Item'
            : '<i class="bi bi-x me-1"></i>Batal';
    });
});

// Toggle realisasi add form
document.querySelectorAll('.toggle-add-realisasi').forEach(btn => {
    btn.addEventListener('click', function () {
        const el = document.getElementById('add-realisasi-' + this.dataset.iid);
        el.classList.toggle('d-none');
        this.innerHTML = el.classList.contains('d-none')
            ? '<i class="bi bi-plus-lg me-1"></i>Input'
            : '<i class="bi bi-x me-1"></i>Tutup';
    });
});

// Toggle insight add form
document.querySelectorAll('.toggle-add-insight').forEach(btn => {
    btn.addEventListener('click', function () {
        const el = document.getElementById('add-insight-' + this.dataset.iid);
        el.classList.toggle('d-none');
        this.innerHTML = el.classList.contains('d-none')
            ? '<i class="bi bi-plus-lg me-1"></i>Input'
            : '<i class="bi bi-x me-1"></i>Tutup';
    });
});

// Edit modal
document.querySelectorAll('.edit-item-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        const tipe      = this.dataset.tipe;
        const isDigital = tipe === 'digital';
        const tipeLabels = {
            'master_design': 'Master Design',
            'digital':       'Content Digital',
            'cetak':         'Media Cetak',
            'influencer':    'Influencer',
            'media_prescon': 'Media Prescon',
        };
        document.getElementById('editItemForm').action   = '<?= base_url('creative/') ?>' + this.dataset.id + '/edit';
        document.getElementById('editTipeDisplay').textContent = tipeLabels[tipe] || tipe;
        document.getElementById('editNama').value        = this.dataset.nama;
        document.getElementById('editDeskripsi').value   = this.dataset.deskripsi;
        document.getElementById('editBudget').value      = this.dataset.budget;
        document.getElementById('editCatatan').value     = this.dataset.catatan;
        document.getElementById('editPlatformDiv').classList.toggle('d-none', !isDigital);
        document.getElementById('editTakeDiv').classList.toggle('d-none', !isDigital);
        document.getElementById('editPicDiv').classList.toggle('d-none', !isDigital);
        document.getElementById('editBudgetDiv').classList.toggle('d-none', tipe === 'master_design');
        if (isDigital) {
            document.getElementById('editPlatform').value    = this.dataset.platform;
            document.getElementById('editTanggalTake').value = this.dataset.tanggalTake;
            document.getElementById('editJamTake').value     = this.dataset.jamTake;
            document.getElementById('editPic').value         = this.dataset.pic;
        }
        let tipeInput = document.getElementById('editTipeHidden');
        if (!tipeInput) {
            tipeInput = document.createElement('input');
            tipeInput.type = 'hidden'; tipeInput.name = 'tipe'; tipeInput.id = 'editTipeHidden';
            document.getElementById('editItemForm').appendChild(tipeInput);
        }
        tipeInput.value = tipe;
        new bootstrap.Modal(document.getElementById('editItemModal')).show();
    });
});
<?php endif; ?>

// Toggle file section
document.querySelectorAll('.toggle-files').forEach(btn => {
    btn.addEventListener('click', function () {
        const el = document.getElementById('files-' + this.dataset.iid);
        if (!el) return;
        el.classList.toggle('d-none');
        const isHidden = el.classList.contains('d-none');
        const count = this.querySelector('.badge');
        const countHtml = count ? ' ' + count.outerHTML : '';
        this.innerHTML = (isHidden
            ? '<i class="bi bi-paperclip me-1"></i>File'
            : '<i class="bi bi-paperclip me-1"></i>File') + countHtml;
    });
});

// Toggle realisasi section
document.querySelectorAll('.toggle-realisasi').forEach(btn => {
    btn.addEventListener('click', function () {
        const el = document.getElementById('realisasi-' + this.dataset.iid);
        if (!el) return;
        el.classList.toggle('d-none');
        const isHidden = el.classList.contains('d-none');
        const count = this.querySelector('.badge');
        const countHtml = count ? ' ' + count.outerHTML : '';
        this.innerHTML = (isHidden
            ? '<i class="bi bi-receipt me-1"></i>Realisasi'
            : '<i class="bi bi-receipt me-1"></i>Realisasi') + countHtml;
    });
});

// Toggle insight section
document.querySelectorAll('.toggle-insight').forEach(btn => {
    btn.addEventListener('click', function () {
        const el = document.getElementById('insight-' + this.dataset.iid);
        if (!el) return;
        el.classList.toggle('d-none');
        const isHidden = el.classList.contains('d-none');
        const count = this.querySelector('.badge');
        const countHtml = count ? ' ' + count.outerHTML : '';
        this.innerHTML = (isHidden
            ? '<i class="bi bi-graph-up me-1"></i>Insight'
            : '<i class="bi bi-graph-up me-1"></i>Insight') + countHtml;
    });
});

// Tab filtering
(function () {
    function applyTab(activeTab) {
        document.querySelectorAll('.creative-item-card').forEach(card => {
            card.classList.toggle('d-none', card.dataset.tab !== activeTab);
        });
        document.querySelectorAll('.tipe-section').forEach(section => {
            const visible = section.querySelectorAll('.creative-item-card:not(.d-none)').length;
            section.classList.toggle('d-none', visible === 0);
            const divider = section.querySelector('.dari-event-divider');
            if (divider) {
                const visibleEvent = section.querySelectorAll('.creative-item-card[data-tab="' + activeTab + '"].border-warning').length;
                divider.classList.toggle('d-none', visibleEvent === 0);
            }
        });
        // Update add-item button visibility: only meaningful for standalone (draft tab)
        document.querySelectorAll('.toggle-add-item').forEach(btn => {
            btn.classList.toggle('d-none', activeTab === 'archived');
        });
    }

    applyTab('review');

    document.querySelectorAll('#creativeTabs .nav-link').forEach(btn => {
        btn.addEventListener('shown.bs.tab', function () {
            applyTab(this.dataset.tab);
        });
    });
})();

document.querySelectorAll('.currency-input').forEach(inp => {
    inp.addEventListener('input', function () {
        let n = parseInt(this.value.replace(/[^0-9]/g, '')) || 0;
        this.value = n.toLocaleString('id-ID');
    });
});
</script>
<?= $this->endSection() ?>
