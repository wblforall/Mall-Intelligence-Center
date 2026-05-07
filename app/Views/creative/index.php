<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$tipeConfig = [
    'master_design' => ['label' => 'Master Design',    'icon' => 'bi-vector-pen',         'color' => 'primary',   'bg' => 'bg-primary-subtle'],
    'digital'       => ['label' => 'Content Digital',  'icon' => 'bi-phone',               'color' => 'info',      'bg' => 'bg-info-subtle'],
    'cetak'         => ['label' => 'Media Cetak',       'icon' => 'bi-printer',             'color' => 'secondary', 'bg' => 'bg-secondary-subtle'],
    'influencer'    => ['label' => 'Influencer',        'icon' => 'bi-person-video3',       'color' => 'warning',   'bg' => 'bg-warning-subtle'],
    'media_prescon' => ['label' => 'Media Prescon',     'icon' => 'bi-newspaper',           'color' => 'dark',      'bg' => 'bg-dark-subtle'],
];
$platformLabels = ['ig' => 'Instagram', 'tiktok' => 'TikTok', 'keduanya' => 'IG & TikTok'];
$statusConfig   = [
    'draft'    => ['label' => 'Draft',         'badge' => 'bg-secondary-subtle text-secondary'],
    'review'   => ['label' => 'Dalam Review',  'badge' => 'bg-warning-subtle text-warning'],
    'approved' => ['label' => 'Approved',      'badge' => 'bg-success-subtle text-success'],
    'revision' => ['label' => 'Perlu Revisi',  'badge' => 'bg-danger-subtle text-danger'],
];
$imageExts    = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$insightFields = [
    'views'            => ['label' => 'Views',          'icon' => 'bi-eye',            'color' => 'info'],
    'reach'            => ['label' => 'Reach',          'icon' => 'bi-broadcast',      'color' => 'primary'],
    'impressions'      => ['label' => 'Impressions',    'icon' => 'bi-bar-chart',      'color' => 'secondary'],
    'likes'            => ['label' => 'Likes',          'icon' => 'bi-heart',          'color' => 'danger'],
    'comments'         => ['label' => 'Komentar',       'icon' => 'bi-chat',           'color' => 'warning'],
    'shares'           => ['label' => 'Share',          'icon' => 'bi-share',          'color' => 'success'],
    'saves'            => ['label' => 'Saves',          'icon' => 'bi-bookmark',       'color' => 'primary'],
    'followers_gained' => ['label' => 'Follower Baru',  'icon' => 'bi-person-plus',    'color' => 'success'],
];

?>

<?= view('partials/complete_data_bar', ['event' => $event, 'module' => 'creative', 'completion' => $completion, 'canEdit' => $canEdit, 'user' => $user]) ?>

<!-- Header -->
<div class="d-flex align-items-center gap-2 mb-4">
    <div class="rounded-2 d-flex align-items-center justify-content-center flex-shrink-0"
         style="width:36px;height:36px;background:rgba(99,102,241,.15)">
        <i class="bi bi-palette-fill" style="color:var(--bs-primary);font-size:1rem"></i>
    </div>
    <div>
        <h4 class="fw-bold mb-0">Creative, Concept &amp; Design</h4>
        <small class="text-muted"><?= esc($event['name']) ?></small>
    </div>
    <?php if ($canEdit): ?>
    <button class="btn btn-sm btn-primary ms-auto" data-bs-toggle="modal" data-bs-target="#addItemModal">
        <i class="bi bi-plus-lg me-1"></i>Tambah Item
    </button>
    <?php endif; ?>
</div>

<?php if ($totalBudget > 0 || $totalRealisasi > 0): ?>
<div class="row g-3 mb-4">
    <?php if ($totalBudget > 0): ?>
    <div class="col-sm-6 col-md-3">
        <div class="card border-danger-subtle h-100">
            <div class="card-body py-3">
                <div class="small text-muted mb-1"><i class="bi bi-wallet2 me-1"></i>Total Budget</div>
                <div class="fw-bold text-danger">Rp <?= number_format($totalBudget,0,',','.') ?></div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <?php if ($totalRealisasi > 0): ?>
    <?php $realPct = $totalBudget > 0 ? min(100, round($totalRealisasi / $totalBudget * 100)) : null;
          $realColor = $totalRealisasi > $totalBudget ? 'danger' : 'success'; ?>
    <div class="col-sm-6 col-md-3">
        <div class="card border-<?= $realColor ?>-subtle h-100">
            <div class="card-body py-3">
                <div class="small text-muted mb-1"><i class="bi bi-receipt me-1"></i>Total Realisasi</div>
                <div class="fw-bold text-<?= $realColor ?>">Rp <?= number_format($totalRealisasi,0,',','.') ?></div>
                <?php if ($realPct !== null): ?>
                <div class="small text-muted"><?= $realPct ?>% dari budget</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if (empty($items)): ?>
<div class="card"><div class="card-body text-center py-5 text-muted">
    <i class="bi bi-palette display-4 d-block mb-2 opacity-25"></i>
    <p class="mb-0">Belum ada item creative. Klik "Tambah Item" untuk memulai.</p>
</div></div>
<?php else: ?>

<?php foreach ($tipeConfig as $tipe => $cfg):
    $tipeItems = $byTipe[$tipe] ?? [];
    if (empty($tipeItems) && ! $canEdit) continue;
?>
<div class="card mb-4">
    <div class="card-header <?= $cfg['bg'] ?> d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold text-<?= $cfg['color'] ?>">
            <i class="<?= $cfg['icon'] ?> me-2"></i><?= $cfg['label'] ?>
            <?php if (!empty($tipeItems)): ?>
            <span class="badge bg-<?= $cfg['color'] ?> text-white ms-1"><?= count($tipeItems) ?></span>
            <?php endif; ?>
        </h6>
        <?php if ($canEdit): ?>
        <button class="btn btn-xs btn-outline-<?= $cfg['color'] ?> toggle-add-item"
                style="padding:.2rem .6rem;font-size:.75rem"
                data-tipe="<?= $tipe ?>">
            <i class="bi bi-plus-lg me-1"></i>Tambah
        </button>
        <?php endif; ?>
    </div>

    <?php if ($canEdit): ?>
    <div id="add-item-<?= $tipe ?>" class="d-none px-3 py-2 border-bottom <?= $cfg['bg'] ?> bg-opacity-25">
        <form method="POST" action="<?= base_url('events/'.$event['id'].'/creative/add') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="tipe" value="<?= $tipe ?>">
            <div class="row g-2 align-items-end">
                <div class="col-sm-4">
                    <label class="form-label small fw-semibold mb-1">Nama <span class="text-danger">*</span></label>
                    <input type="text" name="nama" class="form-control form-control-sm" required
                           placeholder="<?= $tipe === 'master_design' ? 'KV Utama, Banner Promo...' : ($tipe === 'digital' ? 'Feed Opening, Story Promo...' : ($tipe === 'cetak' ? 'Spanduk 3x1m, Flyer A5...' : ($tipe === 'media_prescon' ? 'Press Kit, Backdrop Prescon...' : 'Nama Influencer'))) ?>">
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
                    <label class="form-label small fw-semibold mb-1">
                        <i class="bi bi-camera me-1"></i>Tanggal Take
                    </label>
                    <input type="date" name="tanggal_take" class="form-control form-control-sm">
                </div>
                <div class="col-sm-2">
                    <label class="form-label small fw-semibold mb-1">Waktu Take</label>
                    <input type="time" name="jam_take" class="form-control form-control-sm">
                </div>
                <div class="col-sm-3">
                    <label class="form-label small fw-semibold mb-1">
                        <i class="bi bi-person me-1"></i>PIC
                    </label>
                    <input type="text" name="pic" class="form-control form-control-sm" placeholder="Nama PIC">
                </div>
            </div>
            <?php endif; ?>
        </form>
    </div>
    <?php endif; ?>

    <?php if (empty($tipeItems)): ?>
    <div class="card-body text-center py-3 text-muted small fst-italic">Belum ada item <?= $cfg['label'] ?>.</div>
    <?php else: ?>
    <?php foreach ($tipeItems as $item):
        $iid       = $item['id'];
        $iFiles    = $files[$iid] ?? [];
        $iReal     = $realisasi[$iid] ?? ['total' => 0, 'entries' => []];
        $iTotal    = (int)$iReal['total'];
        $iBudget   = (int)$item['budget'];
        $iPct      = $iBudget > 0 ? min(100, round($iTotal / $iBudget * 100)) : null;
        $iColor    = $iTotal > $iBudget && $iBudget > 0 ? 'danger' : 'success';
        $status    = $item['status'] ?? 'draft';
        $sCfg      = $statusConfig[$status] ?? $statusConfig['draft'];
    ?>
    <div class="border-top" id="item-<?= $iid ?>">
        <!-- Item header -->
        <div class="px-3 py-2 d-flex justify-content-between align-items-start gap-2">
            <div class="flex-grow-1 min-w-0">
                <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                    <?php if ($tipe === 'master_design'): ?>
                    <span class="badge <?= $sCfg['badge'] ?>" style="font-size:.65rem"><?= $sCfg['label'] ?></span>
                    <?php endif; ?>
                    <?php if ($tipe === 'digital' && $item['platform']): ?>
                    <span class="badge bg-info-subtle text-info" style="font-size:.65rem">
                        <i class="bi bi-<?= $item['platform'] === 'ig' ? 'instagram' : ($item['platform'] === 'tiktok' ? 'tiktok' : 'phone') ?> me-1"></i><?= $platformLabels[$item['platform']] ?? $item['platform'] ?>
                    </span>
                    <?php endif; ?>
                    <span class="fw-semibold small"><?= esc($item['nama']) ?></span>
                </div>
                <?php if ($tipe === 'digital' && ($item['tanggal_take'] || $item['jam_take'] || $item['pic'])): ?>
                <div class="small text-muted mb-1">
                    <?php if ($item['tanggal_take'] || $item['jam_take']): ?>
                    <i class="bi bi-camera me-1"></i>Take:
                    <?php if ($item['tanggal_take']): ?>
                    <strong class="text-body"><?= date('d M Y', strtotime($item['tanggal_take'])) ?></strong>
                    <?php endif; ?>
                    <?php if ($item['jam_take']): ?>
                    <span><?= substr($item['jam_take'], 0, 5) ?></span>
                    <?php endif; ?>
                    <?php endif; ?>
                    <?php if ($item['pic']): ?>
                    <?php if ($item['tanggal_take'] || $item['jam_take']): ?>&nbsp;·&nbsp;<?php endif; ?>
                    <i class="bi bi-person me-1"></i><strong class="text-body"><?= esc($item['pic']) ?></strong>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php if ($item['deskripsi']): ?>
                <div class="small text-muted" style="white-space:pre-line"><?= esc($item['deskripsi']) ?></div>
                <?php endif; ?>
                <?php if ($iBudget > 0): ?>
                <div class="small text-muted mt-1">
                    <i class="bi bi-cash me-1"></i>Budget: <strong class="text-body">Rp <?= number_format($iBudget,0,',','.') ?></strong>
                    <?php if ($iTotal > 0): ?>
                    · Realisasi: <strong class="text-<?= $iColor ?>">Rp <?= number_format($iTotal,0,',','.') ?></strong>
                    <?php if ($iPct !== null): ?>
                    <span class="text-<?= $iColor ?>">(<?= $iPct ?>%)</span>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
                <?php if ($iPct !== null && $iTotal > 0): ?>
                <div class="progress mt-1" style="height:3px;max-width:200px">
                    <div class="progress-bar bg-<?= $iColor ?>" style="width:<?= min(100,$iPct) ?>%"></div>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
            <div class="d-flex gap-1 flex-shrink-0 align-items-start">
                <?php if ($tipe === 'master_design' && $canApprove): ?>
                <div class="dropdown">
                    <button class="btn btn-xs btn-outline-secondary dropdown-toggle" style="padding:.2rem .5rem;font-size:.72rem"
                            data-bs-toggle="dropdown">Status</button>
                    <ul class="dropdown-menu dropdown-menu-end" style="font-size:.8rem;min-width:160px">
                        <?php foreach ($statusConfig as $sVal => $sCfgOpt): ?>
                        <?php if ($sVal !== $status): ?>
                        <li>
                            <form method="POST" action="<?= base_url('events/'.$event['id'].'/creative/'.$iid.'/status') ?>">
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
                <form method="POST" action="<?= base_url('events/'.$event['id'].'/creative/'.$iid.'/status') ?>">
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
                        data-deskripsi="<?= esc($item['deskripsi'], 'attr') ?>"
                        data-budget="<?= number_format($iBudget,0,',','.') ?>"
                        data-catatan="<?= esc($item['catatan'], 'attr') ?>">
                    <i class="bi bi-pencil"></i>
                </button>
                <form method="POST" action="<?= base_url('events/'.$event['id'].'/creative/'.$iid.'/delete') ?>"
                      onsubmit="return confirm('Hapus item ini beserta semua file dan realisasinya?')">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-xs btn-outline-danger" style="padding:.2rem .5rem;font-size:.72rem">
                        <i class="bi bi-trash"></i>
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($tipe === 'master_design'): ?>
        <!-- File uploads -->
        <div class="px-3 pb-2">
            <?php if (!empty($iFiles)): ?>
            <div class="d-flex flex-wrap gap-2 mb-2">
                <?php foreach ($iFiles as $f):
                    $ext = strtolower(pathinfo($f['file_name'], PATHINFO_EXTENSION));
                    $isImage = in_array($ext, $imageExts);
                    $fileUrl = base_url('uploads/creative/' . $event['id'] . '/' . $f['file_name']);
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
                    <form method="POST" action="<?= base_url('events/'.$event['id'].'/creative/'.$iid.'/file/'.$f['id'].'/delete') ?>"
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
            <?php endif; ?>
            <?php if ($canEdit): ?>
            <form method="POST" action="<?= base_url('events/'.$event['id'].'/creative/'.$iid.'/upload') ?>" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="d-flex gap-2 align-items-center">
                    <input type="file" name="file_upload" class="form-control form-control-sm" style="max-width:280px" accept="image/*,.pdf,.psd,.ai,.zip,.rar">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-upload me-1"></i>Upload</button>
                </div>
            </form>
            <?php elseif (empty($iFiles)): ?>
            <div class="small text-muted fst-italic">Belum ada file diupload.</div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if (in_array($tipe, ['cetak', 'influencer', 'digital', 'media_prescon'])): ?>
        <!-- Realisasi section -->
        <div class="border-top">
            <div class="px-3 py-2 bg-light d-flex justify-content-between align-items-center">
                <span class="small fw-semibold text-muted">
                    <i class="bi bi-receipt me-1"></i>Realisasi Biaya
                    <?php if ($iTotal > 0): ?>
                    <span class="badge bg-secondary-subtle text-secondary ms-1">Rp <?= number_format($iTotal,0,',','.') ?></span>
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
                <form method="POST" action="<?= base_url('events/'.$event['id'].'/creative/'.$iid.'/realisasi/add') ?>"
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
                <td class="small fw-semibold text-success">Rp <?= number_format($e['nilai'],0,',','.') ?></td>
                <td class="small text-muted"><?= esc($e['catatan']) ?></td>
                <?php if ($tipe === 'influencer'): ?>
                <?php
                    $fileColumns = [
                        ['file' => $e['file_name'],             'orig' => $e['original_name']],
                        ['file' => $e['serah_terima_file_name'], 'orig' => $e['serah_terima_original_name']],
                    ];
                ?>
                <?php foreach ($fileColumns as $fc): ?>
                <td>
                    <?php if ($fc['file']): ?>
                    <?php $fUrl = base_url('uploads/creative/' . $event['id'] . '/' . $fc['file']);
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
                    <?php $fUrl = base_url('uploads/creative/' . $event['id'] . '/' . $e['bukti_terpasang_file_name']);
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
                    <?php $fUrl = base_url('uploads/creative/' . $event['id'] . '/' . $e['file_name']);
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
                    <form method="POST" action="<?= base_url('events/'.$event['id'].'/creative/'.$iid.'/realisasi/'.$e['id'].'/delete') ?>"
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
                <td class="small text-success">Rp <?= number_format($iTotal,0,',','.') ?></td>
                <td colspan="<?= ($tipe === 'influencer' ? 2 : 0) + ($canEdit ? 2 : 1) ?>"></td>
            </tr>
            </tbody>
            </table>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($tipe === 'digital'):
            $iInsight = $insights[$iid] ?? null;
        ?>
        <!-- Insight section -->
        <div class="border-top">
            <div class="px-3 py-2 bg-light d-flex justify-content-between align-items-center">
                <span class="small fw-semibold text-muted">
                    <i class="bi bi-graph-up me-1"></i>Insight
                    <?php if ($iInsight): ?>
                    <span class="badge bg-info-subtle text-info ms-1"><?= count($iInsight['entries']) ?> entri</span>
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
                <?php foreach ($insightFields as $field => $fcfg):
                    $metricKey = $field === 'followers_gained' ? 'total_followers' : 'total_' . $field;
                    $val = $iInsight[$metricKey] ?? 0;
                    if ($val <= 0) continue;
                ?>
                <div class="d-flex align-items-center gap-1 px-2 py-1 rounded-2 bg-<?= $fcfg['color'] ?>-subtle">
                    <i class="<?= $fcfg['icon'] ?> text-<?= $fcfg['color'] ?>" style="font-size:.8rem"></i>
                    <span class="small fw-semibold text-<?= $fcfg['color'] ?>"><?= number_format($val,0,',','.') ?></span>
                    <span class="text-muted" style="font-size:.7rem"><?= $fcfg['label'] ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if ($canEdit): ?>
            <div id="add-insight-<?= $iid ?>" class="d-none px-3 py-2 border-top border-bottom bg-white">
                <form method="POST" action="<?= base_url('events/'.$event['id'].'/creative/'.$iid.'/insight/add') ?>"
                      enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <div class="row g-2 mb-2 align-items-end">
                        <div class="col-sm-2">
                            <label class="form-label small fw-semibold mb-1">Tanggal</label>
                            <input type="date" name="tanggal" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <?php if ($item['platform'] === 'keduanya'): ?>
                        <div class="col-sm-2">
                            <label class="form-label small fw-semibold mb-1">Platform</label>
                            <select name="platform" class="form-select form-select-sm">
                                <option value="ig">Instagram</option>
                                <option value="tiktok">TikTok</option>
                            </select>
                        </div>
                        <?php else: ?>
                        <input type="hidden" name="platform" value="<?= $item['platform'] ?>">
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
                    <?php if ($item['platform'] === 'keduanya'): ?><th>Platform</th><?php endif; ?>
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
            <?php foreach ($iInsight['entries'] as $e): ?>
            <tr>
                <td class="ps-3 small"><?= date('d M Y', strtotime($e['tanggal'])) ?></td>
                <?php if ($item['platform'] === 'keduanya'): ?>
                <td class="small">
                    <span class="badge bg-info-subtle text-info" style="font-size:.65rem">
                        <?= $platformLabels[$e['platform']] ?? $e['platform'] ?>
                    </span>
                </td>
                <?php endif; ?>
                <?php foreach ($insightFields as $field => $fcfg): ?>
                <td class="small text-end <?= $e[$field] > 0 ? 'fw-medium text-'.$fcfg['color'] : 'text-muted' ?>">
                    <?= $e[$field] > 0 ? number_format($e[$field],0,',','.') : '—' ?>
                </td>
                <?php endforeach; ?>
                <td>
                    <?php if ($e['file_name']): ?>
                    <a href="<?= base_url('uploads/creative/'.$event['id'].'/'.$e['file_name']) ?>" target="_blank">
                        <img src="<?= base_url('uploads/creative/'.$event['id'].'/'.$e['file_name']) ?>"
                             alt="ss" style="height:32px;width:auto;border-radius:4px;object-fit:cover"
                             title="<?= esc($e['original_name']) ?>">
                    </a>
                    <?php else: ?>
                    <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>
                <td class="small text-muted"><?= esc($e['catatan']) ?></td>
                <?php if ($canEdit): ?>
                <td>
                    <form method="POST" action="<?= base_url('events/'.$event['id'].'/creative/'.$iid.'/insight/'.$e['id'].'/delete') ?>"
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
    <?php endforeach; ?>
    <?php endif; // empty tipeItems ?>
</div>
<?php endforeach; ?>

<?php endif; // empty items ?>

<!-- Edit Modal -->
<?php if ($canEdit): ?>
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

<!-- Add Modal -->
<div class="modal fade" id="addItemModal" tabindex="-1">
<div class="modal-dialog"><div class="modal-content">
<form method="POST" action="<?= base_url('events/'.$event['id'].'/creative/add') ?>">
<?= csrf_field() ?>
<div class="modal-header">
    <h5 class="modal-title fw-semibold">Tambah Item Creative</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
    <div class="mb-3">
        <label class="form-label small fw-semibold">Tipe <span class="text-danger">*</span></label>
        <div class="d-flex flex-wrap gap-3">
            <?php foreach ($tipeConfig as $tv => $tc): ?>
            <div class="form-check">
                <input class="form-check-input add-tipe-radio" type="radio" name="tipe"
                       id="addTipe_<?= $tv ?>" value="<?= $tv ?>" <?= $tv === 'master_design' ? 'checked' : '' ?>>
                <label class="form-check-label small" for="addTipe_<?= $tv ?>">
                    <i class="<?= $tc['icon'] ?> me-1"></i><?= $tc['label'] ?>
                </label>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label small fw-semibold">Nama <span class="text-danger">*</span></label>
        <input type="text" name="nama" class="form-control" required>
    </div>
    <div class="mb-3 d-none" id="addPlatformDiv">
        <label class="form-label small fw-semibold">Platform</label>
        <select name="platform" class="form-select">
            <option value="ig">Instagram</option>
            <option value="tiktok">TikTok</option>
            <option value="keduanya">IG &amp; TikTok</option>
        </select>
    </div>
    <div class="mb-3" id="addBudgetDiv">
        <label class="form-label small fw-semibold">Budget (Rp)</label>
        <input type="text" name="budget" class="form-control currency-input" value="0">
    </div>
    <div class="mb-3">
        <label class="form-label small fw-semibold">Deskripsi</label>
        <textarea name="deskripsi" class="form-control" rows="2"></textarea>
    </div>
    <div class="mb-0">
        <label class="form-label small fw-semibold">Catatan</label>
        <input type="text" name="catatan" class="form-control">
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
    <button type="submit" class="btn btn-primary">Tambah</button>
</div>
</form>
</div></div></div>
<?php endif; ?>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
<?php if ($canEdit): ?>
// Toggle inline add forms
document.querySelectorAll('.toggle-add-item').forEach(btn => {
    btn.addEventListener('click', function () {
        const tipe = this.dataset.tipe;
        const el   = document.getElementById('add-item-' + tipe);
        el.classList.toggle('d-none');
        this.innerHTML = el.classList.contains('d-none')
            ? '<i class="bi bi-plus-lg me-1"></i>Tambah'
            : '<i class="bi bi-x me-1"></i>Batal';
    });
});

document.querySelectorAll('.toggle-add-realisasi').forEach(btn => {
    btn.addEventListener('click', function () {
        const el = document.getElementById('add-realisasi-' + this.dataset.iid);
        el.classList.toggle('d-none');
        this.innerHTML = el.classList.contains('d-none')
            ? '<i class="bi bi-plus-lg me-1"></i>Input'
            : '<i class="bi bi-x me-1"></i>Tutup';
    });
});

document.querySelectorAll('.toggle-add-insight').forEach(btn => {
    btn.addEventListener('click', function () {
        const el = document.getElementById('add-insight-' + this.dataset.iid);
        el.classList.toggle('d-none');
        this.innerHTML = el.classList.contains('d-none')
            ? '<i class="bi bi-plus-lg me-1"></i>Input'
            : '<i class="bi bi-x me-1"></i>Tutup';
    });
});

// Add modal: show/hide platform & budget based on tipe
function updateAddModal() {
    const tipe = document.querySelector('.add-tipe-radio:checked')?.value;
    document.getElementById('addPlatformDiv').classList.toggle('d-none', tipe !== 'digital');
    document.getElementById('addBudgetDiv').classList.toggle('d-none', tipe === 'master_design');
}
document.querySelectorAll('.add-tipe-radio').forEach(r => r.addEventListener('change', updateAddModal));
updateAddModal();

// Edit modal
document.querySelectorAll('.edit-item-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        const tipe     = this.dataset.tipe;
        const isDigital = tipe === 'digital';
        document.getElementById('editItemForm').action   = '<?= base_url('events/'.$event['id'].'/creative/') ?>' + this.dataset.id + '/edit';
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
        // pass tipe for controller to read
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

document.querySelectorAll('.currency-input').forEach(inp => {
    inp.addEventListener('input', function () {
        let n = parseInt(this.value.replace(/[^0-9]/g, '')) || 0;
        this.value = n.toLocaleString('id-ID');
    });
});
</script>
<?= $this->endSection() ?>
