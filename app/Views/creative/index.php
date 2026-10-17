<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/events-modul.css') ?>?v=<?= @filemtime(FCPATH . 'css/events-modul.css') ?: 1 ?>">
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php
if (! function_exists('evRp')) {
    function evRp($n): string { return 'Rp ' . number_format((float) $n, 0, ',', '.'); }
}
// Gambar kecil / ikon berkas bukti (tautan terbuka di tab baru)
if (! function_exists('evFileThumb')) {
    function evFileThumb(?string $file, ?string $orig, string $url, array $imageExts, string $label = ''): string
    {
        if (! $file) return '<span class="ev-c-mute">—</span>';
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $t   = esc(($orig ?: $label) ?: $file, 'attr');
        if (in_array($ext, $imageExts)) {
            return '<a class="ev-thumb" href="' . $url . '" target="_blank" title="' . $t . '"><img src="' . $url . '" alt="' . $t . '" loading="lazy"></a>';
        }
        return '<a class="ev-thumb" href="' . $url . '" target="_blank" title="' . $t . '"><i class="bi bi-file-earmark-' . ($ext === 'pdf' ? 'pdf' : 'fill') . '"></i></a>';
    }
}

$tipeConfig = [
    'master_design' => ['label' => 'Master Design',    'icon' => 'bi-vector-pen',   'color' => '#6366f1', 'ph' => 'KV Utama, Banner Promo...'],
    'digital'       => ['label' => 'Content Digital',  'icon' => 'bi-phone',        'color' => '#0ea5e9', 'ph' => 'Feed Opening, Story Promo...'],
    'cetak'         => ['label' => 'Media Cetak',      'icon' => 'bi-printer',      'color' => '#94a3b8', 'ph' => 'Spanduk 3x1m, Flyer A5...'],
    'influencer'    => ['label' => 'Influencer',       'icon' => 'bi-person-video3','color' => '#f59e0b', 'ph' => 'Nama Influencer'],
    'media_prescon' => ['label' => 'Media Prescon',    'icon' => 'bi-newspaper',    'color' => '#a78bfa', 'ph' => 'Press Kit, Backdrop Prescon...'],
];
$platformLabels = ['ig' => 'Instagram', 'tiktok' => 'TikTok', 'keduanya' => 'IG & TikTok'];
$statusConfig   = [
    'draft'    => ['label' => 'Draft',        'chip' => 'mute'],
    'review'   => ['label' => 'Dalam Review', 'chip' => 'warn'],
    'approved' => ['label' => 'Approved',     'chip' => 'ok'],
    'revision' => ['label' => 'Perlu Revisi', 'chip' => 'bad'],
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
$budgetTipes = ['cetak', 'influencer', 'digital', 'media_prescon'];
$today = date('Y-m-d');

// Ringkasan KPI (rumus keterlambatan sama dengan controller)
$nByTipe = []; $nOverdue = 0; $nReview = 0; $nMasterAll = 0; $nMasterOk = 0;
foreach ($items as $it) {
    $nByTipe[$it['tipe']] = ($nByTipe[$it['tipe']] ?? 0) + 1;
    if (! empty($it['deadline']) && $it['deadline'] < $today && ($it['status'] ?? '') !== 'approved') $nOverdue++;
    if ($it['tipe'] === 'master_design') {
        $nMasterAll++;
        if (($it['status'] ?? '') === 'approved') $nMasterOk++;
        if (($it['status'] ?? '') === 'review') $nReview++;
    }
}
$serap   = $totalBudget > 0 ? min(100, round($totalRealisasi / $totalBudget * 100)) : 0;
$sisaAll = $totalBudget - $totalRealisasi;
$barAll  = $totalRealisasi > $totalBudget && $totalBudget > 0 ? 'low' : ($serap >= 100 ? 'ok' : 'mid');

$actions = '';
if ($canEdit) {
    $actions = '<button class="ev-btn ev-btn-primary" data-bs-toggle="modal" data-bs-target="#addItemModal"><i class="bi bi-plus-lg"></i>Tambah Item</button>';
}
?>

<div class="page-evm evm-creative" id="evmRoot" data-key="cre-<?= $event['id'] ?>" data-unit="item">

<?= view('partials/evm_head', ['event' => $event, 'module' => 'creative', 'completion' => $completion, 'canEdit' => $canEdit,
    'title' => 'Creative, Concept & Design', 'icon' => 'bi-palette-fill', 'actions' => $actions]) ?>

<!-- Strip KPI -->
<div class="ev-kpis">
    <div class="ev-kpi">
        <div class="ev-kpi-l">Item Creative</div>
        <div class="ev-kpi-v"><?= count($items) ?></div>
        <div class="ev-kpi-s"><?php
            $parts = [];
            foreach ($tipeConfig as $tk => $tc) { if (! empty($nByTipe[$tk])) $parts[] = $nByTipe[$tk] . ' ' . strtolower($tc['label']); }
            echo $parts ? esc(implode(' · ', $parts)) : 'belum ada item';
        ?></div>
    </div>
    <div class="ev-kpi t-warn">
        <div class="ev-kpi-l">Total Budget</div>
        <div class="ev-kpi-v ev-c-warn"><?= evRp($totalBudget) ?></div>
        <div class="ev-kpi-s">media cetak, digital, influencer, prescon</div>
    </div>
    <div class="ev-kpi t-cyan">
        <div class="ev-kpi-l">Total Realisasi</div>
        <div class="ev-kpi-v ev-c-cyan"><?= evRp($totalRealisasi) ?></div>
        <div class="ev-kpi-s"><?= $totalBudget > 0 ? $serap . '% dari budget' . ($sisaAll >= 0 ? ' · sisa ' . evRp($sisaAll) : ' · lebih ' . evRp(-$sisaAll)) : 'belum ada budget' ?></div>
        <?php if ($totalBudget > 0): ?><div class="ev-bar ev-bar-<?= $barAll ?>"><i style="width:<?= $serap ?>%"></i></div><?php endif; ?>
    </div>
    <div class="ev-kpi <?= $nMasterAll > 0 && $nMasterOk === $nMasterAll ? 't-ok' : '' ?>">
        <div class="ev-kpi-l">Master Design Disetujui</div>
        <div class="ev-kpi-v"><?= $nMasterOk ?><span class="ev-c-mute" style="font-size:.9rem;font-weight:600"> / <?= $nMasterAll ?></span></div>
        <div class="ev-kpi-s"><?= $nReview ?> menunggu review</div>
        <?php if ($nMasterAll > 0): ?><div class="ev-bar ev-bar-ok"><i style="width:<?= round($nMasterOk / $nMasterAll * 100) ?>%"></i></div><?php endif; ?>
    </div>
    <div class="ev-kpi <?= $nOverdue > 0 ? 't-bad' : 't-ok' ?>">
        <div class="ev-kpi-l">Terlambat</div>
        <div class="ev-kpi-v <?= $nOverdue > 0 ? 'ev-c-bad' : '' ?>"><?= $nOverdue ?></div>
        <div class="ev-kpi-s">item lewat deadline &amp; belum approved</div>
    </div>
</div>

<?php if (empty($items) && ! $canEdit): ?>
<div class="ev-empty">
    <i class="bi bi-palette"></i>
    <h5>Belum ada item creative</h5>
    <p>Belum ada materi creative yang dicatat untuk event ini.</p>
</div>
<?php elseif (empty($items)): ?>
<div class="ev-empty">
    <i class="bi bi-palette"></i>
    <h5>Belum ada item creative</h5>
    <p>Mulai dengan master design, konten digital, media cetak, influencer, atau media prescon.</p>
    <button class="ev-btn ev-btn-primary" data-bs-toggle="modal" data-bs-target="#addItemModal"><i class="bi bi-plus-lg"></i>Tambah Item Pertama</button>
</div>
<?php else: ?>

<div class="ev-tools">
    <div class="ev-search">
        <i class="bi bi-search"></i>
        <input type="search" id="evQ" placeholder="Cari item, PIC, atau deskripsi…" autocomplete="off" aria-label="Cari item creative">
    </div>
    <select class="ev-select" data-ev-filter="tipe" aria-label="Filter tipe">
        <option value="">Semua tipe</option>
        <?php foreach ($tipeConfig as $tk => $tc): ?><option value="<?= $tk ?>"><?= $tc['label'] ?></option><?php endforeach; ?>
    </select>
    <select class="ev-select" data-ev-filter="status" aria-label="Filter status desain">
        <option value="">Semua status desain</option>
        <?php foreach ($statusConfig as $sk => $sc): ?><option value="<?= $sk ?>"><?= $sc['label'] ?></option><?php endforeach; ?>
    </select>
    <select class="ev-select" data-ev-filter="dl" aria-label="Filter deadline">
        <option value="">Semua deadline</option>
        <option value="lewat">Terlambat</option>
        <option value="dekat">Dalam 3 hari</option>
        <option value="ada">Punya deadline</option>
        <option value="tanpa">Tanpa deadline</option>
    </select>
    <select class="ev-select" data-ev-filter="biaya" aria-label="Filter progres biaya">
        <option value="">Semua progres biaya</option>
        <option value="belum">Belum ada realisasi</option>
        <option value="jalan">Berjalan</option>
        <option value="penuh">Mencapai budget</option>
        <option value="over">Melebihi budget</option>
    </select>
    <select class="ev-select" id="evSort" aria-label="Urutkan">
        <option value="" data-dir="asc">Urutan: input</option>
        <option value="name" data-type="str" data-dir="asc">Nama A–Z</option>
        <option value="budget" data-dir="desc">Budget terbesar</option>
        <option value="dlts" data-dir="asc">Deadline terdekat</option>
    </select>
    <div class="ev-tools-end">
        <span class="ev-count" id="evCount" aria-live="polite"></span>
        <button type="button" class="ev-btn ev-btn-sm" id="evExpand" title="Buka semua item"><i class="bi bi-arrows-expand"></i><span class="d-none d-md-inline">Buka semua</span></button>
        <button type="button" class="ev-btn ev-btn-sm" id="evCollapse" title="Tutup semua item"><i class="bi bi-arrows-collapse"></i><span class="d-none d-md-inline">Tutup semua</span></button>
    </div>
</div>

<div class="ev-empty ev-empty-filter" id="evNoMatch" hidden>
    <i class="bi bi-search"></i>
    <h5>Tidak ada item yang cocok</h5>
    <p>Ubah kata kunci atau filter.</p>
    <button type="button" class="ev-btn" id="evReset"><i class="bi bi-x-circle"></i>Reset filter</button>
</div>

<?php $ord = 0; foreach ($tipeConfig as $tipe => $cfg):
    $tipeItems = $byTipe[$tipe] ?? [];
    if (empty($tipeItems) && ! $canEdit) continue;
    $grpBudget = array_sum(array_column($tipeItems, 'budget'));
?>
<section class="ev-group" data-ev-keep style="--gc:<?= $cfg['color'] ?>">
<div class="ev-group-h">
    <span class="ev-group-t"><i class="<?= $cfg['icon'] ?>"></i><?= $cfg['label'] ?></span>
    <span class="ev-group-n"><?= count($tipeItems) ?> item<?php if ($grpBudget > 0): ?> · budget <?= evRp($grpBudget) ?><?php endif; ?></span>
    <?php if ($canEdit): ?>
    <button type="button" class="ev-btn ev-btn-xs ev-btn-soft ms-auto" data-ev-panel="add-item-<?= $tipe ?>" aria-expanded="false"><i class="bi bi-plus-lg"></i>Tambah <?= $cfg['label'] ?></button>
    <?php endif; ?>
</div>

<?php if ($canEdit): ?>
<div class="ev-panel mb-2 rounded-3" id="add-item-<?= $tipe ?>" hidden style="border:1px solid var(--ev-border)">
    <form method="POST" action="<?= base_url('events/' . $event['id'] . '/creative/add') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="tipe" value="<?= $tipe ?>">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Nama <span class="text-danger">*</span></label>
                <input type="text" name="nama" class="form-control form-control-sm" required placeholder="<?= esc($cfg['ph'], 'attr') ?>">
            </div>
            <?php if ($tipe === 'digital'): ?>
            <div class="col-md-2 col-6">
                <label class="form-label">Platform</label>
                <select name="platform" class="form-select form-select-sm">
                    <option value="ig">Instagram</option>
                    <option value="tiktok">TikTok</option>
                    <option value="keduanya">IG &amp; TikTok</option>
                </select>
            </div>
            <?php endif; ?>
            <?php if (in_array($tipe, $budgetTipes)): ?>
            <div class="col-md-2 col-6">
                <label class="form-label">Budget (Rp)</label>
                <input type="text" name="budget" class="form-control form-control-sm currency-input" placeholder="0">
            </div>
            <?php endif; ?>
            <?php if (in_array($tipe, ['digital', 'influencer'])): ?>
            <div class="col-md-2 col-6">
                <label class="form-label">Target Reach</label>
                <input type="number" name="target_reach" class="form-control form-control-sm" placeholder="0" min="0">
            </div>
            <div class="col-md-2 col-6">
                <label class="form-label">Target Impresi</label>
                <input type="number" name="target_impressions" class="form-control form-control-sm" placeholder="0" min="0">
            </div>
            <?php endif; ?>
            <div class="col-md-2 col-6">
                <label class="form-label">Deadline</label>
                <input type="date" name="deadline" class="form-control form-control-sm">
            </div>
            <div class="col-md">
                <label class="form-label">Deskripsi / Catatan</label>
                <input type="text" name="deskripsi" class="form-control form-control-sm" placeholder="Opsional">
            </div>
        </div>
        <?php if ($tipe === 'digital'): ?>
        <div class="row g-2 mt-1">
            <div class="col-md-3 col-6">
                <label class="form-label"><i class="bi bi-camera me-1"></i>Tanggal Take</label>
                <input type="date" name="tanggal_take" class="form-control form-control-sm">
            </div>
            <div class="col-md-2 col-6">
                <label class="form-label">Waktu Take</label>
                <input type="time" name="jam_take" class="form-control form-control-sm">
            </div>
            <div class="col-md-3">
                <label class="form-label"><i class="bi bi-person me-1"></i>PIC</label>
                <input type="text" name="pic" class="form-control form-control-sm" placeholder="Nama PIC">
            </div>
        </div>
        <?php endif; ?>
        <div class="d-flex gap-2 mt-2">
            <button type="submit" class="ev-btn ev-btn-sm ev-btn-primary"><i class="bi bi-check-lg"></i>Tambah</button>
            <button type="button" class="ev-btn ev-btn-sm" data-ev-panel="add-item-<?= $tipe ?>">Batal</button>
        </div>
    </form>
</div>
<?php endif; ?>

<div class="ev-list">
<?php if (empty($tipeItems)): ?>
<div class="ev-empty-sm" style="border:1px dashed var(--ev-border);border-radius:14px">Belum ada item <?= $cfg['label'] ?>.</div>
<?php endif; ?>
<?php foreach ($tipeItems as $item):
    $iid       = $item['id'];
    $iFiles    = $files[$iid] ?? [];
    $iReal     = $realisasi[$iid] ?? ['total' => 0, 'entries' => []];
    $iTotal    = (int) $iReal['total'];
    $iBudget   = (int) $item['budget'];
    $iPct      = $iBudget > 0 ? min(100, round($iTotal / $iBudget * 100)) : null;
    $status    = $item['status'] ?? 'draft';
    $sCfg      = $statusConfig[$status] ?? $statusConfig['draft'];
    $isMaster  = $tipe === 'master_design';
    $hasBudget = in_array($tipe, $budgetTipes);
    $sisa      = $iBudget - $iTotal;
    $biaya     = ! $hasBudget ? '' : (empty($iReal['entries']) ? 'belum' : ($iBudget > 0 && $iTotal > $iBudget ? 'over' : ($iBudget > 0 && $iTotal >= $iBudget ? 'penuh' : 'jalan')));
    $barCls    = $iPct === null ? 'blue' : ($iTotal > $iBudget ? 'low' : ($iPct >= 100 ? 'ok' : 'mid'));
    $dlDiff    = ! empty($item['deadline']) ? (int) ((strtotime($item['deadline']) - strtotime($today)) / 86400) : null;
    $isDone    = $status === 'approved';
    $dlKey     = $dlDiff === null ? 'tanpa' : (($dlDiff < 0 && ! $isDone) ? 'lewat' : (($dlDiff <= 3 && ! $isDone) ? 'dekat' : 'ada'));
    $terkunci  = $isMaster && $status === 'approved';
    $search    = mb_strtolower($item['nama'] . ' ' . ($item['deskripsi'] ?? '') . ' ' . ($item['pic'] ?? '') . ' ' . ($item['catatan'] ?? ''));
    $iInsight  = $insights[$iid] ?? null;
    $nOpsi     = count(array_filter($iFiles, fn($f) => (int) ($f['is_opsi'] ?? 1) === 1));
?>
<article class="ev-card<?= $isMaster ? ($status === 'approved' ? ' is-ok' : ($status === 'revision' ? ' is-bad' : '')) : ($biaya === 'over' ? ' is-bad' : '') ?>" id="item-<?= $iid ?>"
         data-name="<?= esc($item['nama'], 'attr') ?>" data-search="<?= esc($search, 'attr') ?>"
         data-tipe="<?= $tipe ?>" data-status="<?= $isMaster ? $status : '' ?>" data-dl="<?= $dlKey ?>" data-biaya="<?= $biaya ?>"
         data-budget="<?= $hasBudget ? $iBudget : '' ?>" data-dlts="<?= ! empty($item['deadline']) ? strtotime($item['deadline']) : '' ?>" data-order="<?= $ord++ ?>">
    <div class="ev-card-head" data-ev-toggle>
        <button type="button" class="ev-chev" aria-expanded="false" aria-controls="body-<?= $iid ?>" aria-label="Buka atau tutup rincian item"><i class="bi bi-chevron-right"></i></button>
        <div class="min-w-0">
            <div class="ev-card-name">
                <span class="ev-card-t"><?= esc($item['nama']) ?></span>
                <?php if ($isMaster): ?>
                <span class="ev-chip ev-chip-<?= $sCfg['chip'] ?>"><?= $sCfg['label'] ?><?= ($item['versi'] ?? 1) > 1 ? ' · v' . (int) $item['versi'] : '' ?></span>
                <?php endif; ?>
                <?php if ($tipe === 'digital' && $item['platform']): ?>
                <span class="ev-chip ev-chip-blue"><i class="bi bi-<?= $item['platform'] === 'ig' ? 'instagram' : ($item['platform'] === 'tiktok' ? 'tiktok' : 'phone') ?>"></i><?= esc($platformLabels[$item['platform']] ?? $item['platform']) ?></span>
                <?php endif; ?>
                <?php if ($dlDiff !== null): ?>
                    <?php if ($isDone): ?><span class="ev-chip ev-chip-ok"><i class="bi bi-check-circle"></i>Tepat waktu</span>
                    <?php elseif ($dlDiff < 0): ?><span class="ev-chip ev-chip-bad" title="Deadline: <?= date('d M Y', strtotime($item['deadline'])) ?>"><i class="bi bi-exclamation-triangle-fill"></i>Terlambat <?= abs($dlDiff) ?> hari</span>
                    <?php elseif ($dlDiff <= 3): ?><span class="ev-chip ev-chip-warn"><i class="bi bi-clock"></i><?= $dlDiff === 0 ? 'Hari ini!' : $dlDiff . ' hari lagi' ?></span>
                    <?php else: ?><span class="ev-chip ev-chip-mute"><i class="bi bi-calendar-check"></i><?= date('d M', strtotime($item['deadline'])) ?></span><?php endif; ?>
                <?php endif; ?>
                <?php if ($isMaster && $canApprove && $status === 'review'): ?>
                <span class="ev-chip ev-chip-warn" title="Menunggu keputusan Anda di panel Opsi Desain"><i class="bi bi-clipboard-check"></i>Perlu ditinjau</span>
                <?php endif; ?>
                <?php if ($terkunci && $canEdit): ?>
                <span class="ev-chip ev-chip-mute" title="Buka kembali materi di panel Opsi Desain bila perlu diubah"><i class="bi bi-lock-fill"></i>Terkunci</span>
                <?php endif; ?>
            </div>
            <div class="ev-card-meta">
                <?php if ($tipe === 'digital' && ($item['tanggal_take'] || $item['jam_take'])): ?>
                <span><i class="bi bi-camera"></i>Take <?= $item['tanggal_take'] ? date('d M Y', strtotime($item['tanggal_take'])) : '' ?> <?= $item['jam_take'] ? substr($item['jam_take'], 0, 5) : '' ?></span>
                <?php endif; ?>
                <?php if ($tipe === 'digital' && $item['pic']): ?><span><i class="bi bi-person"></i><?= esc($item['pic']) ?></span><?php endif; ?>
                <?php if ($isMaster): ?><span><i class="bi bi-images"></i><?= $nOpsi ?> opsi desain</span><?php endif; ?>
                <?php if ($item['deskripsi']): ?><span><i class="bi bi-card-text"></i><?= esc($item['deskripsi']) ?></span><?php endif; ?>
            </div>
            <?php if ($isMaster && $status === 'approved'): ?>
            <div class="mt-1" data-ev-notoggle>
            <?= view('_creative/_final', [
                'iFiles'   => $iFiles,
                'fileBase' => 'uploads/creative/' . $event['id'] . '/',
            ], ['saveData' => false]) ?>
            </div>
            <?php endif; ?>
        </div>
        <div class="ev-fig">
            <?php if ($hasBudget): ?>
            <div class="ev-fig-row">
                <div><span class="ev-fig-l">Budget</span><b><?= evRp($iBudget) ?></b></div>
                <div><span class="ev-fig-l">Realisasi</span><b class="ev-c-cyan"><?= evRp($iTotal) ?></b></div>
                <div><span class="ev-fig-l"><?= $sisa < 0 ? 'Lebih' : 'Sisa' ?></span><b class="<?= $sisa < 0 ? 'ev-c-bad' : ($sisa > 0 ? 'ev-c-warn' : 'ev-c-ok') ?>"><?= evRp(abs($sisa)) ?></b></div>
            </div>
            <div class="ev-fig-bar">
                <div class="ev-bar ev-bar-<?= $barCls ?>"><i style="width:<?= (int) ($iPct ?? 0) ?>%"></i></div>
                <span class="ev-pct"><?= $iPct !== null ? $iPct . '%' : '—' ?></span>
            </div>
            <?php else: ?>
            <div class="ev-fig-row">
                <div><span class="ev-fig-l">Status</span><b class="<?= ['ok' => 'ev-c-ok', 'warn' => 'ev-c-warn', 'bad' => 'ev-c-bad', 'mute' => ''][$sCfg['chip']] ?>"><?= $sCfg['label'] ?></b></div>
                <div><span class="ev-fig-l">Opsi desain</span><b><?= $nOpsi ?></b></div>
                <div><span class="ev-fig-l">Versi</span><b>v<?= max(1, (int) ($item['versi'] ?? 1)) ?></b></div>
            </div>
            <?php endif; ?>
        </div>
        <div class="ev-card-act" data-ev-notoggle>
            <?php if ($canEdit && $hasBudget): ?>
            <button type="button" class="ev-btn ev-btn-sm ev-btn-ok" data-ev-open="add-realisasi-<?= $iid ?>" title="Input realisasi biaya"><i class="bi bi-plus-lg"></i><span class="d-none d-sm-inline">Realisasi</span></button>
            <?php endif; ?>
            <?php if ($canEdit && ! $terkunci): ?>
            <div class="dropdown">
                <button class="ev-iconbtn" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Aksi item"><i class="bi bi-three-dots-vertical"></i></button>
                <ul class="dropdown-menu dropdown-menu-end ev-menu">
                    <li>
                        <button type="button" class="dropdown-item edit-item-btn"
                                data-id="<?= $iid ?>"
                                data-nama="<?= esc($item['nama'], 'attr') ?>"
                                data-tipe="<?= $tipe ?>"
                                data-platform="<?= esc($item['platform'] ?? '', 'attr') ?>"
                                data-tanggal-take="<?= $item['tanggal_take'] ?? '' ?>"
                                data-jam-take="<?= substr($item['jam_take'] ?? '', 0, 5) ?>"
                                data-pic="<?= esc($item['pic'] ?? '', 'attr') ?>"
                                data-deskripsi="<?= esc($item['deskripsi'], 'attr') ?>"
                                data-budget="<?= number_format($iBudget, 0, ',', '.') ?>"
                                data-target-reach="<?= (int) ($item['target_reach'] ?? 0) ?>"
                                data-target-impressions="<?= (int) ($item['target_impressions'] ?? 0) ?>"
                                data-deadline="<?= $item['deadline'] ?? '' ?>"
                                data-catatan="<?= esc($item['catatan'], 'attr') ?>"><i class="bi bi-pencil"></i>Edit item</button>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="<?= base_url('events/' . $event['id'] . '/creative/' . $iid . '/delete') ?>"
                              onsubmit="return confirm('Hapus item ini beserta semua file dan realisasinya?')">
                            <?= csrf_field() ?>
                            <button type="submit" class="dropdown-item text-danger"><i class="bi bi-trash"></i>Hapus item</button>
                        </form>
                    </li>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="ev-card-body" id="body-<?= $iid ?>" hidden>
        <?php if ($item['catatan']): ?>
        <div class="ev-pad"><p class="ev-desc"><i class="bi bi-sticky me-1"></i><?= esc($item['catatan']) ?></p></div>
        <?php endif; ?>

        <?php if ($isMaster): ?>
        <!-- Opsi desain, keputusan & riwayat -->
        <div class="ev-sec s-blue">
            <?= view('_creative/_approval', [
                'iid'        => $iid,
                'item'       => $item,
                'iFiles'     => $iFiles,
                'iReviews'   => $reviews[$iid] ?? [],
                'canEdit'    => $canEdit,
                'canApprove' => $canApprove,
                'aksiBase'   => 'events/' . $event['id'] . '/creative/' . $iid,
                'fileBase'   => 'uploads/creative/' . $event['id'] . '/',
                'fileField'  => 'file_upload',
                'imageExts'  => $imageExts,
            ], ['saveData' => false]) ?>
        </div>
        <?php endif; ?>

        <?php if ($hasBudget): ?>
        <div class="ev-sec s-cyan">
            <div class="ev-sec-h">
                <span class="ev-sec-t"><i class="bi bi-receipt"></i>Realisasi biaya
                    <?php if ($iTotal > 0): ?><span class="ev-chip ev-chip-mute"><?= evRp($iTotal) ?></span><?php endif; ?></span>
                <?php if ($canEdit): ?>
                <button type="button" class="ev-btn ev-btn-xs ev-btn-soft" data-ev-panel="add-realisasi-<?= $iid ?>" aria-expanded="false"><i class="bi bi-plus-lg"></i>Input realisasi</button>
                <?php endif; ?>
            </div>

            <?php if ($canEdit): ?>
            <div class="ev-panel" id="add-realisasi-<?= $iid ?>" hidden>
                <form method="POST" action="<?= base_url('events/' . $event['id'] . '/creative/' . $iid . '/realisasi/add') ?>"
                      <?= in_array($tipe, ['influencer', 'cetak', 'media_prescon']) ? 'enctype="multipart/form-data"' : '' ?>>
                    <?= csrf_field() ?>
                    <div class="row g-2 align-items-end">
                        <div class="col-md-2 col-6">
                            <label class="form-label">Tanggal</label>
                            <input type="date" name="tanggal" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-3 col-6">
                            <label class="form-label">Nilai (Rp) <span class="text-danger">*</span></label>
                            <input type="text" name="nilai" class="form-control form-control-sm currency-input" placeholder="0" required>
                        </div>
                        <?php if ($tipe === 'influencer'): ?>
                        <div class="col-md-3">
                            <label class="form-label">Nama Influencer <span class="text-danger">*</span></label>
                            <input type="text" name="nama_influencer" class="form-control form-control-sm" placeholder="@username" required>
                        </div>
                        <?php endif; ?>
                        <div class="col-md">
                            <label class="form-label">Catatan</label>
                            <input type="text" name="catatan" class="form-control form-control-sm" placeholder="Opsional">
                        </div>
                    </div>
                    <?php if ($tipe === 'influencer'): ?>
                    <div class="row g-2 mt-1">
                        <div class="col-md-5">
                            <label class="form-label"><i class="bi bi-image me-1"></i>Screenshot Insight <span class="fw-normal">(opsional)</span></label>
                            <input type="file" name="bukti" class="form-control form-control-sm" accept="image/*">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label"><i class="bi bi-file-earmark-check me-1"></i>Bukti Serah Terima <span class="fw-normal">(opsional)</span></label>
                            <input type="file" name="serah_terima" class="form-control form-control-sm" accept="image/*,.pdf">
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ($tipe === 'cetak'): ?>
                    <div class="row g-2 mt-1">
                        <div class="col-md-5">
                            <label class="form-label"><i class="bi bi-image me-1"></i>Bukti Terpasang <span class="fw-normal">(opsional)</span></label>
                            <input type="file" name="bukti_terpasang" class="form-control form-control-sm" accept="image/*,.pdf">
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ($tipe === 'media_prescon'): ?>
                    <div class="row g-2 mt-1">
                        <div class="col-md-5">
                            <label class="form-label"><i class="bi bi-image me-1"></i>Dokumentasi <span class="fw-normal">(opsional)</span></label>
                            <input type="file" name="bukti" class="form-control form-control-sm" accept="image/*,.pdf">
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="d-flex gap-2 mt-2">
                        <button type="submit" class="ev-btn ev-btn-sm ev-btn-primary"><i class="bi bi-check-lg"></i>Simpan</button>
                        <button type="button" class="ev-btn ev-btn-sm" data-ev-panel="add-realisasi-<?= $iid ?>">Batal</button>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <?php if (! empty($iReal['entries'])):
                $cbase = 'uploads/creative/' . $event['id'] . '/';
                $nFileCols = $tipe === 'influencer' ? 2 : (in_array($tipe, ['cetak', 'media_prescon']) ? 1 : 0); ?>
            <div class="ev-tbl-wrap">
            <table class="ev-tbl ev-stack">
                <thead><tr>
                    <th>Tanggal</th>
                    <?php if ($tipe === 'influencer'): ?><th>Influencer</th><?php endif; ?>
                    <th class="ev-num">Nilai</th>
                    <th>Catatan</th>
                    <?php if ($tipe === 'influencer'): ?><th>Screenshot insight</th><th>Serah terima</th><?php endif; ?>
                    <?php if ($tipe === 'cetak'): ?><th>Bukti terpasang</th><?php endif; ?>
                    <?php if ($tipe === 'media_prescon'): ?><th>Dokumentasi</th><?php endif; ?>
                    <?php if ($canEdit): ?><th></th><?php endif; ?>
                </tr></thead>
                <tbody>
                <?php foreach ($iReal['entries'] as $e): ?>
                <tr>
                    <td class="ev-nowrap" data-l="Tanggal"><?= date('d M Y', strtotime($e['tanggal'])) ?></td>
                    <?php if ($tipe === 'influencer'): ?><td data-l="Influencer"><b><?= esc($e['nama_influencer'] ?? '—') ?></b></td><?php endif; ?>
                    <td class="ev-num ev-c-ok" data-l="Nilai"><?= evRp($e['nilai']) ?></td>
                    <td data-l="Catatan"><?= esc($e['catatan'] ?: '—') ?></td>
                    <?php if ($tipe === 'influencer'): ?>
                    <td data-l="Insight"><?= evFileThumb($e['file_name'], $e['original_name'], base_url($cbase . $e['file_name']), $imageExts) ?></td>
                    <td data-l="Serah terima"><?= evFileThumb($e['serah_terima_file_name'], $e['serah_terima_original_name'], base_url($cbase . $e['serah_terima_file_name']), $imageExts) ?></td>
                    <?php elseif ($tipe === 'cetak'): ?>
                    <td data-l="Terpasang"><?= evFileThumb($e['bukti_terpasang_file_name'], $e['bukti_terpasang_original_name'], base_url($cbase . $e['bukti_terpasang_file_name']), $imageExts) ?></td>
                    <?php elseif ($tipe === 'media_prescon'): ?>
                    <td data-l="Dokumentasi"><?= evFileThumb($e['file_name'], $e['original_name'], base_url($cbase . $e['file_name']), $imageExts) ?></td>
                    <?php endif; ?>
                    <?php if ($canEdit): ?>
                    <td class="ev-act-cell">
                        <form method="POST" action="<?= base_url('events/' . $event['id'] . '/creative/' . $iid . '/realisasi/' . $e['id'] . '/delete') ?>" class="ev-form-inline" onsubmit="return confirm('Hapus entri ini?')">
                            <?= csrf_field() ?>
                            <button type="submit" class="ev-iconbtn ev-iconbtn-sm ev-iconbtn-bad" title="Hapus entri" aria-label="Hapus entri realisasi"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot><tr><td colspan="<?= $tipe === 'influencer' ? 2 : 1 ?>">Total</td><td class="ev-num ev-c-ok"><?= evRp($iTotal) ?></td><td colspan="<?= $nFileCols + 1 + ($canEdit ? 1 : 0) ?>"></td></tr></tfoot>
            </table>
            </div>
            <?php else: ?>
            <div class="ev-empty-sm">Belum ada realisasi biaya.</div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($tipe === 'digital'): ?>
        <div class="ev-sec s-violet">
            <div class="ev-sec-h">
                <span class="ev-sec-t"><i class="bi bi-graph-up"></i>Insight
                    <?php if ($iInsight): ?><span class="ev-chip ev-chip-mute"><?= count($iInsight['entries']) ?> entri</span><?php endif; ?></span>
                <?php if ($canEdit): ?>
                <button type="button" class="ev-btn ev-btn-xs ev-btn-soft" data-ev-panel="add-insight-<?= $iid ?>" aria-expanded="false"><i class="bi bi-plus-lg"></i>Input insight</button>
                <?php endif; ?>
            </div>

            <?php if ($iInsight): ?>
            <div class="ev-insights">
                <?php foreach ($insightFields as $field => $fcfg):
                    $metricKey = $field === 'followers_gained' ? 'total_followers' : 'total_' . $field;
                    $val = $iInsight[$metricKey] ?? 0;
                    if ($val <= 0) continue; ?>
                <span class="ev-ins"><i class="<?= $fcfg['icon'] ?> text-<?= $fcfg['color'] ?>"></i><b><?= number_format($val, 0, ',', '.') ?></b><span><?= $fcfg['label'] ?></span></span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if ($canEdit): ?>
            <div class="ev-panel" id="add-insight-<?= $iid ?>" hidden>
                <form method="POST" action="<?= base_url('events/' . $event['id'] . '/creative/' . $iid . '/insight/add') ?>" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <div class="row g-2 mb-2 align-items-end">
                        <div class="col-md-2 col-6">
                            <label class="form-label">Tanggal</label>
                            <input type="date" name="tanggal" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <?php if ($item['platform'] === 'keduanya'): ?>
                        <div class="col-md-2 col-6">
                            <label class="form-label">Platform</label>
                            <select name="platform" class="form-select form-select-sm">
                                <option value="ig">Instagram</option>
                                <option value="tiktok">TikTok</option>
                            </select>
                        </div>
                        <?php else: ?>
                        <input type="hidden" name="platform" value="<?= esc($item['platform'] ?? '', 'attr') ?>">
                        <?php endif; ?>
                        <div class="col-md-4">
                            <label class="form-label"><i class="bi bi-image me-1"></i>Screenshot <span class="fw-normal">(opsional)</span></label>
                            <input type="file" name="screenshot" class="form-control form-control-sm" accept="image/*">
                        </div>
                        <div class="col-md">
                            <label class="form-label">Catatan</label>
                            <input type="text" name="catatan" class="form-control form-control-sm" placeholder="Opsional">
                        </div>
                    </div>
                    <div class="row g-2 mb-2">
                        <?php foreach ($insightFields as $field => $fcfg): ?>
                        <div class="col-6 col-md-3">
                            <label class="form-label"><i class="<?= $fcfg['icon'] ?> text-<?= $fcfg['color'] ?> me-1"></i><?= $fcfg['label'] ?></label>
                            <input type="number" name="<?= $field ?>" class="form-control form-control-sm" value="0" min="0">
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="ev-btn ev-btn-sm ev-btn-primary"><i class="bi bi-save"></i>Simpan Insight</button>
                        <button type="button" class="ev-btn ev-btn-sm" data-ev-panel="add-insight-<?= $iid ?>">Batal</button>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <?php if ($iInsight && ! empty($iInsight['entries'])): ?>
            <div class="ev-tbl-wrap">
            <table class="ev-tbl" style="min-width:760px">
                <thead><tr>
                    <th>Tanggal</th>
                    <?php if ($item['platform'] === 'keduanya'): ?><th>Platform</th><?php endif; ?>
                    <?php foreach ($insightFields as $field => $fcfg): ?><th class="ev-num"><?= $fcfg['label'] ?></th><?php endforeach; ?>
                    <th>Screenshot</th><th>Catatan</th>
                    <?php if ($canEdit): ?><th></th><?php endif; ?>
                </tr></thead>
                <tbody>
                <?php foreach ($iInsight['entries'] as $e): ?>
                <tr>
                    <td class="ev-nowrap"><?= date('d M Y', strtotime($e['tanggal'])) ?></td>
                    <?php if ($item['platform'] === 'keduanya'): ?><td><span class="ev-chip ev-chip-blue"><?= esc($platformLabels[$e['platform']] ?? $e['platform']) ?></span></td><?php endif; ?>
                    <?php foreach ($insightFields as $field => $fcfg): ?>
                    <td class="ev-num <?= $e[$field] > 0 ? '' : 'ev-c-mute' ?>"><?= $e[$field] > 0 ? number_format($e[$field], 0, ',', '.') : '—' ?></td>
                    <?php endforeach; ?>
                    <td><?= evFileThumb($e['file_name'], $e['original_name'], base_url('uploads/creative/' . $event['id'] . '/' . $e['file_name']), $imageExts, 'Screenshot') ?></td>
                    <td><?= esc($e['catatan']) ?></td>
                    <?php if ($canEdit): ?>
                    <td>
                        <form method="POST" action="<?= base_url('events/' . $event['id'] . '/creative/' . $iid . '/insight/' . $e['id'] . '/delete') ?>" class="ev-form-inline" onsubmit="return confirm('Hapus entri insight ini?')">
                            <?= csrf_field() ?>
                            <button type="submit" class="ev-iconbtn ev-iconbtn-sm ev-iconbtn-bad" title="Hapus entri" aria-label="Hapus entri insight"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <?php elseif (! $iInsight): ?>
            <div class="ev-empty-sm">Belum ada insight.</div>
            <?php endif; ?>
        </div>
        <?php endif; // digital ?>
    </div>
</article>
<?php endforeach; ?>
</div>
</section>
<?php endforeach; ?>

<?php endif; // empty items ?>

<!-- ACT — Analisa Otomatis -->
<?php if (! empty($analysis)): ?>
<div class="ev-analysis">
    <div class="ev-analysis-h"><i class="bi bi-lightbulb-fill ev-c-cyan"></i>ACT — Analisa Otomatis <span class="ev-chip ev-chip-blue">rule-based</span></div>
    <ul>
        <?php foreach ($analysis as $line): ?>
        <li><?= esc($line) ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

</div><!-- /page-evm -->

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
        <label class="form-label small fw-semibold">Deadline Produksi</label>
        <input type="date" name="deadline" id="editDeadline" class="form-control">
        <div class="form-text">Kosongkan jika tidak ada batas waktu.</div>
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
        document.getElementById('editDeadline').value    = this.dataset.deadline || '';
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

// Panel alasan revisi / buka kembali — muncul di tempat, bukan modal,
// supaya opsi desain tetap terlihat saat alasannya ditulis.
[['toggle-revisi', 'revisi-'], ['toggle-buka', 'buka-']].forEach(([cls, prefix]) => {
    document.querySelectorAll('.' + cls).forEach(btn => {
        btn.addEventListener('click', function () {
            const el = document.getElementById(prefix + this.dataset.iid);
            if (!el) return;
            el.classList.toggle('d-none');
            if (!el.classList.contains('d-none')) {
                const ta = el.querySelector('textarea');
                if (ta) ta.focus();
            }
        });
    });
});
</script>
<script src="<?= base_url('js/events-modul.js') ?>?v=<?= @filemtime(FCPATH . 'js/events-modul.js') ?: 1 ?>"></script>
<?= $this->endSection() ?>
