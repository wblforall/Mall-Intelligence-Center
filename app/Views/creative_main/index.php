<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/creative-main.css') ?>?v=<?= @filemtime(FCPATH . 'css/creative-main.css') ?: 1 ?>">
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php
$tipeConfig = [
    'master_design' => ['label' => 'Master Design',   'icon' => 'bi-vector-pen'],
    'digital'       => ['label' => 'Content Digital', 'icon' => 'bi-phone'],
    'cetak'         => ['label' => 'Media Cetak',     'icon' => 'bi-printer'],
    'influencer'    => ['label' => 'Influencer',      'icon' => 'bi-person-video3'],
    'media_prescon' => ['label' => 'Media Prescon',   'icon' => 'bi-newspaper'],
];
$platformLabels = ['ig' => 'Instagram', 'tiktok' => 'TikTok', 'keduanya' => 'IG & TikTok'];
$statusConfig = [
    'draft'    => ['label' => 'Draft',        'chip' => 'cr-chip-mute', 'icon' => 'bi-pencil'],
    'review'   => ['label' => 'Dalam Review', 'chip' => 'cr-chip-warn', 'icon' => 'bi-hourglass-split'],
    'approved' => ['label' => 'Approved',     'chip' => 'cr-chip-ok',   'icon' => 'bi-check-circle-fill'],
    'revision' => ['label' => 'Perlu Revisi', 'chip' => 'cr-chip-bad',  'icon' => 'bi-arrow-repeat'],
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
$bulanS = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
$fTgl = function ($d, bool $tahun = true) use ($bulanS): string {
    if (!$d || $d === '0000-00-00') return '';
    $t = strtotime($d);
    return date('d', $t) . ' ' . $bulanS[(int) date('n', $t) - 1] . ($tahun ? ' ' . date('Y', $t) : '');
};
$rp   = fn($n) => 'Rp ' . number_format((int) $n, 0, ',', '.');
$today = date('Y-m-d');

// Tenggat: kunci none|done|late|soon|ok + selisih hari
$dlInfo = function (array $item) use ($today): array {
    $dl = $item['deadline'] ?? '';
    if (!$dl || $dl === '0000-00-00') return ['key' => 'none', 'diff' => 0];
    $diff = (int) ((strtotime($dl) - strtotime($today)) / 86400);
    $done = !empty($item['is_closed']) || ($item['status'] ?? '') === 'approved';
    if ($done) return ['key' => 'done', 'diff' => $diff];
    if ($diff < 0) return ['key' => 'late', 'diff' => $diff];
    if ($diff <= 3) return ['key' => 'soon', 'diff' => $diff];
    return ['key' => 'ok', 'diff' => $diff];
};

// Total & hitungan yang butuh tindakan (dihitung dari data yang sama dengan daftar)
$totalStandalone = 0; $nReview = 0; $nLate = 0; $nSoon = 0; $nAttention = 0; $nAll = 0;
foreach ($byTipe as $tipeItems) {
    foreach ($tipeItems as $it) {
        $nAll++;
        if ($it['_source'] === 's') $totalStandalone++;
        $isRev = $it['tipe'] === 'master_design' && ($it['status'] ?? '') === 'review';
        $dk = $dlInfo($it)['key'];
        if ($isRev) $nReview++;
        if ($dk === 'late') $nLate++;
        if ($dk === 'soon') $nSoon++;
        if ($isRev || $dk === 'late') $nAttention++;
    }
}
$realPct   = $totalBudget > 0 ? min(100, round($totalRealisasi / $totalBudget * 100)) : null;
$overBudget = $totalBudget > 0 && $totalRealisasi > $totalBudget;

// Satu sel berkas bukti pada tabel realisasi
$fileCell = function (?string $file, ?string $orig, int $iid) use ($imageExts): string {
    if (!$file) return '<span class="cr-c-mute">—</span>';
    $url = base_url('uploads/creative-standalone-realisasi/' . $iid . '/' . $file);
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if (in_array($ext, $imageExts)) {
        return '<a href="' . $url . '" target="_blank"><img class="thumb" src="' . $url . '" alt="" title="' . esc((string) $orig, 'attr') . '"></a>';
    }
    return '<a href="' . $url . '" target="_blank" class="cr-c-mute" title="' . esc((string) $orig, 'attr') . '"><i class="bi bi-file-earmark-' . ($ext === 'pdf' ? 'pdf cr-c-bad' : 'fill') . '" style="font-size:1.2rem"></i></a>';
};
?>

<div class="page-cre" id="creIdx">

<!-- Header -->
<div class="cr-head">
    <div class="cr-head-ico"><i class="bi bi-palette-fill"></i></div>
    <div class="min-w-0">
        <h1 class="cr-title">Creative &amp; Design</h1>
        <div class="cr-subtitle">Kelola materi desain, konten, dan media — standalone &amp; dari Event</div>
    </div>
    <div class="cr-head-act">
        <a href="<?= base_url('creative/monthly-summary') ?>" class="cr-btn"><i class="bi bi-bar-chart-line"></i><span class="d-none d-sm-inline">Summary Bulanan</span></a>
        <?php if ($canEdit): ?>
        <div class="dropdown">
            <button class="cr-btn cr-btn-primary" type="button" data-bs-toggle="dropdown" aria-expanded="false" id="crAddMenuBtn">
                <i class="bi bi-plus-lg"></i>Tambah Item <i class="bi bi-chevron-down" style="font-size:.65rem"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end cr-menu">
                <li class="dropdown-item-text">Pilih jenis item</li>
                <?php foreach ($tipeConfig as $tp => $tc): ?>
                <li><button type="button" class="dropdown-item" data-cr-add="<?= $tp ?>"><i class="bi <?= $tc['icon'] ?>"></i><?= $tc['label'] ?></button></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Flash -->
<?php if (session()->getFlashdata('success')): ?>
<div class="cr-flash cr-flash-ok" role="alert"><i class="bi bi-check-circle"></i><span><?= esc(session()->getFlashdata('success')) ?></span><button type="button" aria-label="Tutup" onclick="this.parentNode.remove()">&times;</button></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
<div class="cr-flash cr-flash-bad" role="alert"><i class="bi bi-exclamation-triangle"></i><span><?= esc(session()->getFlashdata('error')) ?></span><button type="button" aria-label="Tutup" onclick="this.parentNode.remove()">&times;</button></div>
<?php endif; ?>

<!-- Strip KPI -->
<div class="cr-kpis">
    <div class="cr-kpi">
        <div class="cr-kpi-l">Item Standalone</div>
        <div class="cr-kpi-v"><?= $totalStandalone ?></div>
        <div class="cr-kpi-s">dari <?= count($standaloneItems) + count($eventItems) ?> total</div>
    </div>
    <div class="cr-kpi">
        <div class="cr-kpi-l">Perlu Perhatian</div>
        <div class="cr-kpi-v <?= $nAttention > 0 ? 'cr-c-warn' : 'cr-c-ok' ?>"><?= $nAttention ?></div>
        <div class="cr-kpi-s">
            <button type="button" data-cr-quick="review" class="<?= $nReview ? 'b-warn' : '' ?>"><?= $nReview ?> menunggu review</button> ·
            <button type="button" data-cr-quick="late" class="<?= $nLate ? 'b-bad' : '' ?>"><?= $nLate ?> terlambat</button>
        </div>
    </div>
    <div class="cr-kpi">
        <div class="cr-kpi-l">Total Budget</div>
        <div class="cr-kpi-v"><?= $rp($totalBudget) ?></div>
        <div class="cr-kpi-s">standalone + event</div>
    </div>
    <div class="cr-kpi">
        <div class="cr-kpi-l">Total Realisasi</div>
        <div class="cr-kpi-v <?= $overBudget ? 'cr-c-bad' : 'cr-c-ok' ?>"><?= $rp($totalRealisasi) ?></div>
        <div class="cr-kpi-s"><?= $realPct !== null ? $realPct . '% dari budget' : 'standalone only' ?></div>
        <?php if ($realPct !== null): ?><div class="cr-bar <?= $overBudget ? 'cr-bar-over' : 'cr-bar-ok' ?>"><i style="width:<?= $realPct ?>%"></i></div><?php endif; ?>
    </div>
</div>

<?php if ($nAll === 0 && !$canEdit): ?>
<div class="cr-empty"><i class="bi bi-palette"></i><h5>Belum ada item Creative</h5><p>Item creative akan tampil di sini setelah dibuat.</p></div>
<?php else: ?>

<!-- Pencarian & filter -->
<div class="cr-tools" id="crTools">
    <div class="cr-search">
        <i class="bi bi-search"></i>
        <input type="search" id="crQ" placeholder="Cari nama, PIC, catatan, event…" autocomplete="off" aria-label="Cari item creative">
    </div>
    <div class="cr-seg" id="creativeTabs" role="group" aria-label="Fase">
        <button type="button" class="is-on" data-tab="">Semua <span class="n"><?= $nAll ?></span></button>
        <button type="button" data-tab="review"><i class="bi bi-hourglass-split"></i>Review <span class="n"><?= (int) ($tabCounts['review'] ?? 0) ?></span></button>
        <button type="button" data-tab="realisasi"><i class="bi bi-receipt-cutoff"></i>Realisasi <span class="n"><?= (int) ($tabCounts['realisasi'] ?? 0) ?></span></button>
    </div>
    <select class="cr-select" id="crFTipe" aria-label="Filter jenis">
        <option value="">Semua jenis</option>
        <?php foreach ($tipeConfig as $tp => $tc): ?><option value="<?= $tp ?>"><?= $tc['label'] ?></option><?php endforeach; ?>
    </select>
    <select class="cr-select" id="crFStatus" aria-label="Filter status">
        <option value="">Semua status</option>
        <option value="review">Dalam Review</option>
        <option value="revision">Perlu Revisi</option>
        <option value="approved">Approved</option>
        <option value="draft">Draft (Master Design)</option>
        <option value="closed">Selesai / close</option>
    </select>
    <select class="cr-select" id="crFDl" aria-label="Filter tenggat">
        <option value="">Semua tenggat</option>
        <option value="late">Terlambat</option>
        <option value="soon">Segera (≤ 3 hari)</option>
        <option value="has">Punya tenggat</option>
        <option value="none">Tanpa tenggat</option>
    </select>
    <select class="cr-select" id="crFSrc" aria-label="Filter sumber">
        <option value="">Semua sumber</option>
        <option value="s">Standalone</option>
        <option value="e">Dari Event</option>
    </select>
    <select class="cr-select" id="crSort" aria-label="Urutkan">
        <option value="">Urutan asli</option>
        <option value="tenggat">Tenggat terdekat</option>
        <option value="tanggal">Tanggal terbaru</option>
        <option value="nama">Nama A–Z</option>
        <option value="budget">Budget terbesar</option>
        <option value="real">Realisasi terbesar</option>
    </select>
    <div class="cr-tools-end">
        <span class="cr-count" id="crCount" aria-live="polite"><?= $nAll ?> item</span>
        <button type="button" class="cr-btn cr-btn-sm" id="crReset"><i class="bi bi-x-circle"></i>Reset</button>
    </div>
</div>

<div class="cr-empty" id="crNoMatch" hidden>
    <i class="bi bi-funnel"></i><h5>Tidak ada item yang cocok</h5>
    <p>Ubah kata kunci atau filter, atau reset untuk melihat semua item.</p>
    <button type="button" class="cr-btn" data-cr-resetbtn><i class="bi bi-x-circle"></i>Reset filter</button>
</div>

<?php foreach ($tipeConfig as $tipe => $cfg):
    $tipeItems = $byTipe[$tipe] ?? [];
    if (empty($tipeItems) && !$canEdit) continue;
    $sCount = count(array_filter($tipeItems, fn($i) => $i['_source'] === 's'));
    $eCount = count($tipeItems) - $sCount;
    $hasBudgetField = in_array($tipe, ['cetak', 'influencer', 'digital', 'media_prescon']);
?>
<!-- ═══ <?= $cfg['label'] ?> ═══ -->
<section class="cr-group t-<?= $tipe ?>" data-tipe="<?= $tipe ?>" id="grup-<?= $tipe ?>">
    <div class="cr-group-h">
        <div class="cr-group-ico"><i class="bi <?= $cfg['icon'] ?>"></i></div>
        <h2 class="cr-group-t"><?= $cfg['label'] ?></h2>
        <span class="cr-group-n"><span class="js-gcount"><?= count($tipeItems) ?></span> item<?= $sCount > 0 && $eCount > 0 ? ' · ' . $sCount . ' standalone · ' . $eCount . ' event' : '' ?></span>
        <?php if ($canEdit): ?>
        <button type="button" class="cr-btn cr-btn-sm cr-btn-soft toggle-add-item" data-tipe="<?= $tipe ?>"><i class="bi bi-plus-lg"></i>Tambah Item</button>
        <?php endif; ?>
    </div>

    <?php if ($canEdit): ?>
    <!-- Form tambah (per jenis) -->
    <div id="add-item-<?= $tipe ?>" class="cr-add" hidden>
        <div class="cr-add-t"><i class="bi <?= $cfg['icon'] ?> cr-c-mute"></i>Item <?= $cfg['label'] ?> baru</div>
        <form method="POST" action="<?= base_url('creative/add') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="tipe" value="<?= $tipe ?>">
            <div class="cr-fgrid">
                <div class="cr-f w4">
                    <label>Nama <span class="req">*</span></label>
                    <input type="text" name="nama" class="form-control" required placeholder="<?= $tipePlaceholders[$tipe] ?? '' ?>">
                </div>
                <div class="cr-f w2">
                    <label><i class="bi bi-calendar3"></i> Tanggal</label>
                    <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>">
                </div>
                <?php if ($tipe === 'digital'): ?>
                <div class="cr-f w2">
                    <label>Platform</label>
                    <select name="platform" class="form-select">
                        <option value="ig">Instagram</option>
                        <option value="tiktok">TikTok</option>
                        <option value="keduanya">IG &amp; TikTok</option>
                    </select>
                </div>
                <?php endif; ?>
                <?php if ($hasBudgetField): ?>
                <div class="cr-f w2">
                    <label>Budget (Rp)</label>
                    <input type="text" name="budget" class="form-control currency-input" placeholder="0">
                </div>
                <?php endif; ?>
                <?php if (in_array($tipe, ['digital', 'influencer'])): ?>
                <div class="cr-f w2">
                    <label>Target Reach</label>
                    <input type="number" name="target_reach" class="form-control" placeholder="0" min="0">
                </div>
                <div class="cr-f w2">
                    <label>Target Impresi</label>
                    <input type="number" name="target_impressions" class="form-control" placeholder="0" min="0">
                </div>
                <?php endif; ?>
                <div class="cr-f w2">
                    <label>Deadline</label>
                    <input type="date" name="deadline" class="form-control">
                </div>
                <?php if ($tipe === 'digital'): ?>
                <div class="cr-f w2">
                    <label><i class="bi bi-camera"></i> Tanggal Take</label>
                    <input type="date" name="tanggal_take" class="form-control">
                </div>
                <div class="cr-f w2">
                    <label>Waktu Take</label>
                    <input type="time" name="jam_take" class="form-control">
                </div>
                <div class="cr-f w2">
                    <label><i class="bi bi-person"></i> PIC</label>
                    <input type="text" name="pic" class="form-control" placeholder="Nama PIC">
                </div>
                <?php endif; ?>
                <div class="cr-f w6">
                    <label>Deskripsi / Catatan</label>
                    <input type="text" name="deskripsi" class="form-control" placeholder="Opsional">
                </div>
                <div class="cr-f w6 cr-f-act">
                    <label class="cr-check" for="close-<?= $tipe ?>" style="margin:0;text-transform:none;letter-spacing:0;font-size:.8rem;color:var(--cr-txt)" title="Tandai item sudah selesai/close">
                        <input type="checkbox" name="is_closed" value="1" id="close-<?= $tipe ?>">Selesai
                    </label>
                    <button type="submit" class="cr-btn cr-btn-primary"><i class="bi bi-plus-lg"></i>Tambah</button>
                    <button type="button" class="cr-btn" data-cr-addclose="<?= $tipe ?>">Batal</button>
                </div>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <?php if (empty($tipeItems)): ?>
    <div class="cr-groupempty">Belum ada item <?= $cfg['label'] ?>. <?= $canEdit ? 'Klik “Tambah Item” untuk memulai.' : '' ?></div>
    <?php endif; ?>

    <div class="cr-list">
    <?php foreach ($tipeItems as $ord => $item):
        $isS    = $item['_source'] === 's';
        $iid    = (int) $item['id'];
        $status = $item['status'] ?? 'draft';
        $sCfg   = $statusConfig[$status] ?? $statusConfig['draft'];
        $closed = !empty($item['is_closed']);
        $iBudget = (int) ($item['budget'] ?? 0);
        if ($isS) {
            $iFiles   = $files[$iid] ?? [];
            $iReal    = $realisasi[$iid] ?? ['total' => 0, 'entries' => []];
            $iInsight = $insights[$iid] ?? null;
            $iReviews = $reviews[$iid] ?? [];
        } else {
            $iFiles = []; $iReal = ['total' => 0, 'entries' => []]; $iInsight = null; $iReviews = [];
        }
        $iTotal = (int) ($iReal['total'] ?? 0);
        $iPct   = $iBudget > 0 ? min(100, round($iTotal / $iBudget * 100)) : null;
        $over   = $iTotal > $iBudget && $iBudget > 0;
        $barCls = $over ? 'cr-bar-over' : ($iPct !== null && $iPct >= 100 ? 'cr-bar-ok' : 'cr-bar-ok');
        $dl     = $dlInfo($item);
        $isMD   = $tipe === 'master_design';
        $terkunci   = $isS && $isMD && $status === 'approved';
        $perluTinjau = $isS && $isMD && $canApprove && $status === 'review';
        $itgl   = ($item['tanggal'] ?? '') ?: ($item['tanggal_take'] ?? '');
        $stKey  = $isMD ? $status : ($closed ? 'closed' : 'run');
        $jmlOpsi = $isMD ? count(array_filter($iFiles, fn($f) => (int) ($f['is_opsi'] ?? 1) === 1)) : 0;
        $nEntries = count($iReal['entries'] ?? []);
        $nIns     = $iInsight && !empty($iInsight['entries']) ? count($iInsight['entries']) : 0;
        $actReach = (int) ($iInsight['max_reach'] ?? 0);
        $tgtReach = (int) ($item['target_reach'] ?? 0);
        $tgtImpr  = (int) ($item['target_impressions'] ?? 0);
        $actImpr  = (int) ($iInsight['max_impressions'] ?? 0);

        // Panel yang tersedia (hanya item standalone yang bisa dikelola di sini)
        $panes = [];
        if ($isS && $isMD && ($canEdit || !empty($iFiles) || $iReviews)) $panes['files'] = ['Opsi & Persetujuan', 'bi-images', $jmlOpsi ?: null];
        if ($isS && $hasBudgetField && ($canEdit || $nEntries)) $panes['realisasi'] = ['Realisasi', 'bi-receipt', $nEntries ?: null];
        if ($isS && $tipe === 'digital' && ($canEdit || $nIns)) $panes['insight'] = ['Insight', 'bi-graph-up', $nIns ?: null];
        $autoOpen = $perluTinjau && isset($panes['files']);
        $primaryPane = $autoOpen ? 'files' : (array_key_first($panes) ?: 'detail');

        $search = mb_strtolower(implode(' ', [
            $item['nama'], $item['deskripsi'] ?? '', $item['catatan'] ?? '', $item['pic'] ?? '', $item['event_name'] ?? '',
            $platformLabels[$item['platform'] ?? ''] ?? '', $cfg['label'],
        ]));
    ?>
    <article class="cr-item <?= $isS ? 'is-s' : 'is-e' ?> <?= ($closed || ($isMD && $status === 'approved')) ? 'is-done' : '' ?> creative-item-card"
        id="<?= $isS ? 'item-' . $iid . '-s' : 'item-e-' . $iid ?>"
        data-id="<?= $iid ?>" data-ord="<?= $ord ?>" data-src="<?= $isS ? 's' : 'e' ?>" data-tipe="<?= $tipe ?>" data-tab="<?= esc($item['_tab'], 'attr') ?>"
        data-status="<?= $stKey ?>" data-closed="<?= $closed ? '1' : '0' ?>" data-dl="<?= $dl['key'] ?>"
        data-deadline="<?= esc($item['deadline'] ?? '', 'attr') ?>" data-tgl="<?= esc($itgl, 'attr') ?>"
        data-name="<?= esc($item['nama'], 'attr') ?>" data-budget="<?= $iBudget ?>" data-real="<?= $iTotal ?>"
        data-pane="<?= $primaryPane ?>" data-autoopen="<?= $autoOpen ? '1' : '0' ?>"
        data-search="<?= esc($search, 'attr') ?>">
        <div class="cr-item-head" data-cr-toggle>
            <button type="button" class="cr-chev" aria-expanded="false" aria-label="Buka rincian <?= esc($item['nama'], 'attr') ?>"><i class="bi bi-chevron-right"></i></button>

            <div class="cr-item-main">
                <div class="cr-item-line">
                    <span class="cr-item-name"><?= esc($item['nama']) ?></span>
                    <?php if ($isMD): ?>
                    <span class="cr-chip <?= $sCfg['chip'] ?>"><i class="bi <?= $sCfg['icon'] ?>"></i><?= $sCfg['label'] ?><?= ($item['versi'] ?? 1) > 1 ? ' · v' . (int) $item['versi'] : '' ?></span>
                    <?php endif; ?>
                    <?php if ($tipe === 'digital' && ($item['platform'] ?? '')): ?>
                    <span class="cr-chip cr-chip-t"><i class="bi bi-<?= $item['platform'] === 'ig' ? 'instagram' : ($item['platform'] === 'tiktok' ? 'tiktok' : 'phone') ?>"></i><?= esc($platformLabels[$item['platform']] ?? $item['platform']) ?></span>
                    <?php endif; ?>
                    <?php if (!$isS): ?><span class="cr-chip cr-chip-gold"><i class="bi bi-calendar-event"></i>Event</span><?php endif; ?>
                    <?php if ($closed): ?><span class="cr-chip cr-chip-mute"><i class="bi bi-check2-circle"></i>Selesai</span><?php endif; ?>
                    <?php if ($dl['key'] === 'done'): ?>
                    <span class="cr-chip cr-chip-ok" title="Deadline: <?= $fTgl($item['deadline']) ?>"><i class="bi bi-check-circle"></i>Tepat waktu</span>
                    <?php elseif ($dl['key'] === 'late'): ?>
                    <span class="cr-chip cr-chip-bad" title="Deadline: <?= $fTgl($item['deadline']) ?>"><i class="bi bi-exclamation-triangle-fill"></i>Terlambat <?= abs($dl['diff']) ?> hari</span>
                    <?php elseif ($dl['key'] === 'soon'): ?>
                    <span class="cr-chip cr-chip-warn" title="Deadline: <?= $fTgl($item['deadline']) ?>"><i class="bi bi-clock"></i><?= $dl['diff'] === 0 ? 'Hari ini!' : $dl['diff'] . ' hari lagi' ?></span>
                    <?php elseif ($dl['key'] === 'ok'): ?>
                    <span class="cr-chip cr-chip-blue" title="Deadline"><i class="bi bi-calendar-check"></i><?= $fTgl($item['deadline'], false) ?></span>
                    <?php endif; ?>
                    <?php if ($perluTinjau): ?><span class="cr-chip cr-chip-warn"><i class="bi bi-clipboard-check"></i>Perlu ditinjau</span><?php endif; ?>
                    <?php if ($terkunci && $canEdit): ?><span class="cr-chip cr-chip-mute" title="Buka kembali materi di panel Opsi &amp; Persetujuan bila perlu diubah"><i class="bi bi-lock-fill"></i>Terkunci</span><?php endif; ?>
                </div>
                <div class="cr-item-meta">
                    <?php if ($itgl): ?><span><i class="bi bi-calendar3"></i><?= $fTgl($itgl) ?></span><?php endif; ?>
                    <?php if (!$isS && ($item['event_name'] ?? '')): ?><span><i class="bi bi-calendar-event"></i><?= esc($item['event_name']) ?></span><?php endif; ?>
                    <?php if ($tipe === 'digital' && (($item['tanggal_take'] ?? '') || ($item['jam_take'] ?? ''))): ?>
                    <span><i class="bi bi-camera"></i>Take <b><?= $fTgl($item['tanggal_take'] ?? '') ?></b><?= ($item['jam_take'] ?? '') ? ' ' . substr($item['jam_take'], 0, 5) : '' ?></span>
                    <?php endif; ?>
                    <?php if ($tipe === 'digital' && ($item['pic'] ?? '')): ?><span><i class="bi bi-person"></i><b><?= esc($item['pic']) ?></b></span><?php endif; ?>
                </div>
                <?php if (($item['deskripsi'] ?? '') !== ''): ?><div class="cr-desc1"><?= esc($item['deskripsi']) ?></div><?php endif; ?>
            </div>

            <div class="cr-fig">
                <?php if ($isMD): ?>
                <div class="cr-fig-row">
                    <div><span class="cr-fig-l">Opsi desain</span><b><?= $isS ? $jmlOpsi : '—' ?></b></div>
                    <div><span class="cr-fig-l">Putaran</span><b>v<?= (int) ($item['versi'] ?? 1) ?></b></div>
                    <div><span class="cr-fig-l">Keputusan</span><b><?= $sCfg['label'] ?></b></div>
                </div>
                <?php else: ?>
                <div class="cr-fig-row">
                    <div><span class="cr-fig-l">Budget</span><b><?= $iBudget > 0 ? $rp($iBudget) : '—' ?></b></div>
                    <div><span class="cr-fig-l">Realisasi</span><b class="<?= $iTotal > 0 ? ($over ? 'cr-c-bad' : 'cr-c-ok') : 'cr-c-mute' ?>"><?= $isS ? ($iTotal > 0 ? $rp($iTotal) : '—') : '—' ?></b></div>
                    <?php if ($tipe === 'digital'): ?>
                    <div><span class="cr-fig-l">Reach</span><b><?= $actReach > 0 ? number_format($actReach, 0, ',', '.') : '—' ?></b><?php if ($tgtReach > 0): ?> <span class="cr-fig-s">/ <?= number_format($tgtReach, 0, ',', '.') ?></span><?php endif; ?></div>
                    <?php else: ?>
                    <div><span class="cr-fig-l">Serapan</span><b><?= $iPct !== null && $isS ? $iPct . '%' : '—' ?></b></div>
                    <?php endif; ?>
                </div>
                <?php if ($isS && $iPct !== null && $iTotal > 0): ?>
                <div class="cr-fig-bar"><div class="cr-bar <?= $barCls ?>"><i style="width:<?= $iPct ?>%"></i></div><span class="cr-pct <?= $over ? 'cr-c-bad' : 'cr-c-ok' ?>"><?= $iPct ?>%</span></div>
                <?php endif; ?>
                <?php endif; ?>
            </div>

            <div class="cr-item-act" data-cr-notoggle>
                <?php if (!$isS): ?>
                <a href="<?= base_url('events/' . $item['event_id'] . '/creative') ?>" class="cr-btn cr-btn-sm cr-btn-warn"><i class="bi bi-box-arrow-up-right"></i><span>Kelola di Event</span></a>
                <?php elseif ($perluTinjau): ?>
                <button type="button" class="cr-btn cr-btn-sm cr-btn-primary toggle-files" data-iid="<?= $iid ?>" data-cr-open="files"><i class="bi bi-clipboard-check"></i><span>Tinjau</span></button>
                <?php elseif ($panes): $pk = array_key_first($panes); ?>
                <button type="button" class="cr-btn cr-btn-sm cr-btn-soft" data-cr-open="<?= $pk ?>"><i class="bi <?= $panes[$pk][1] ?>"></i><span><?= $panes[$pk][0] ?></span><?php if ($panes[$pk][2]): ?><span class="cr-chip cr-chip-mute"><?= $panes[$pk][2] ?></span><?php endif; ?></button>
                <?php endif; ?>
                <?php if ($isS && $canEdit && !$terkunci): ?>
                <div class="dropdown">
                    <button type="button" class="cr-iconbtn cr-iconbtn-sm" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Aksi lain untuk <?= esc($item['nama'], 'attr') ?>"><i class="bi bi-three-dots-vertical"></i></button>
                    <ul class="dropdown-menu dropdown-menu-end cr-menu">
                        <li><button type="button" class="dropdown-item edit-item-btn"
                            data-id="<?= $iid ?>"
                            data-nama="<?= esc($item['nama'], 'attr') ?>"
                            data-tipe="<?= $tipe ?>"
                            data-platform="<?= esc($item['platform'] ?? '', 'attr') ?>"
                            data-tanggal-take="<?= $item['tanggal_take'] ?? '' ?>"
                            data-jam-take="<?= substr($item['jam_take'] ?? '', 0, 5) ?>"
                            data-pic="<?= esc($item['pic'] ?? '', 'attr') ?>"
                            data-deskripsi="<?= esc($item['deskripsi'] ?? '', 'attr') ?>"
                            data-tanggal="<?= $item['tanggal'] ?? '' ?>"
                            data-is-closed="<?= $closed ? '1' : '0' ?>"
                            data-budget="<?= number_format($iBudget, 0, ',', '.') ?>"
                            data-target-reach="<?= (int) ($item['target_reach'] ?? 0) ?>"
                            data-target-impressions="<?= (int) ($item['target_impressions'] ?? 0) ?>"
                            data-deadline="<?= $item['deadline'] ?? '' ?>"
                            data-catatan="<?= esc($item['catatan'] ?? '', 'attr') ?>"><i class="bi bi-pencil"></i>Ubah item</button></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="<?= base_url('creative/' . $iid . '/delete') ?>" onsubmit="return confirm('Hapus item ini beserta semua file dan realisasinya?')">
                                <?= csrf_field() ?>
                                <button type="submit" class="dropdown-item text-danger"><i class="bi bi-trash"></i>Hapus item</button>
                            </form>
                        </li>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="cr-item-body" hidden>
            <?php if (count($panes) > 0): ?>
            <div class="cr-tabs" role="tablist">
                <button type="button" role="tab" data-cr-tab="detail"><i class="bi bi-card-text"></i>Rincian</button>
                <?php foreach ($panes as $pk => $pv): ?>
                <button type="button" role="tab" data-cr-tab="<?= $pk ?>"><i class="bi <?= $pv[1] ?>"></i><?= $pv[0] ?><?php if ($pv[2]): ?> <span class="n"><?= $pv[2] ?></span><?php endif; ?></button>
                <?php endforeach; ?>
            </div>
            <?php else: ?><div style="height:.75rem"></div><?php endif; ?>

            <!-- Rincian -->
            <div class="cr-pane" data-cr-pane="detail">
                <?php if ($isS && $isMD && $status === 'approved'): ?>
                <div class="cr-final">
                <?= view('_creative/_final', ['iFiles' => $iFiles, 'fileBase' => 'uploads/creative-standalone/' . $iid . '/'], ['saveData' => false]) ?>
                </div>
                <?php endif; ?>
                <dl class="cr-dl">
                    <div><dt>Jenis</dt><dd><?= $cfg['label'] ?></dd></div>
                    <div><dt>Sumber</dt><dd><?= $isS ? 'Creative Standalone' : 'Event: ' . esc($item['event_name'] ?? '') ?></dd></div>
                    <?php if ($itgl): ?><div><dt>Tanggal</dt><dd><?= $fTgl($itgl) ?></dd></div><?php endif; ?>
                    <div><dt>Deadline</dt><dd><?= ($item['deadline'] ?? '') ? $fTgl($item['deadline']) : '—' ?></dd></div>
                    <?php if ($hasBudgetField || $iBudget > 0): ?><div><dt>Budget</dt><dd><?= $iBudget > 0 ? $rp($iBudget) : '—' ?></dd></div><?php endif; ?>
                    <?php if ($isS && $hasBudgetField): ?><div><dt>Realisasi</dt><dd class="<?= $over ? 'cr-c-bad' : '' ?>"><?= $iTotal > 0 ? $rp($iTotal) . ($iPct !== null ? ' (' . $iPct . '%)' : '') : '—' ?></dd></div><?php endif; ?>
                    <?php if ($tipe === 'digital'): ?>
                    <div><dt>Platform</dt><dd><?= esc($platformLabels[$item['platform'] ?? ''] ?? '—') ?></dd></div>
                    <div><dt>Take</dt><dd><?= (($item['tanggal_take'] ?? '') || ($item['jam_take'] ?? '')) ? trim($fTgl($item['tanggal_take'] ?? '') . ' ' . substr($item['jam_take'] ?? '', 0, 5)) : '—' ?></dd></div>
                    <div><dt>PIC</dt><dd><?= ($item['pic'] ?? '') !== '' ? esc($item['pic']) : '—' ?></dd></div>
                    <?php endif; ?>
                    <?php if ($tgtReach > 0 || $tgtImpr > 0): ?>
                    <div><dt>Target reach / impresi</dt><dd><?= number_format($tgtReach, 0, ',', '.') ?> / <?= number_format($tgtImpr, 0, ',', '.') ?></dd></div>
                    <?php endif; ?>
                </dl>
                <?php if (($item['deskripsi'] ?? '') !== ''): ?><p class="cr-text"><?= esc($item['deskripsi']) ?></p><?php endif; ?>
                <?php if (($item['catatan'] ?? '') !== ''): ?><p class="cr-note"><i class="bi bi-sticky"></i><?= esc($item['catatan']) ?></p><?php endif; ?>
                <?php if (!$isS): ?>
                <p class="cr-note"><i class="bi bi-info-circle"></i>Item ini milik event dan hanya bisa dikelola di halaman event. <a href="<?= base_url('events/' . $item['event_id'] . '/creative') ?>" style="color:var(--cr-link);font-weight:600">Buka halaman event</a></p>
                <?php endif; ?>
            </div>

            <?php if (isset($panes['files'])): ?>
            <!-- Opsi desain, keputusan & riwayat -->
            <div class="cr-pane" data-cr-pane="files" id="files-<?= $iid ?>" hidden>
                <div class="cr-approval">
                <?= view('_creative/_approval', [
                    'iid'        => $iid,
                    'item'       => $item,
                    'iFiles'     => $iFiles,
                    'iReviews'   => $iReviews,
                    'canEdit'    => $canEdit,
                    'canApprove' => $canApprove,
                    'aksiBase'   => 'creative/' . $iid,
                    'fileBase'   => 'uploads/creative-standalone/' . $iid . '/',
                    'fileField'  => 'file',
                    'imageExts'  => $imageExts,
                ], ['saveData' => false]) ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (isset($panes['realisasi'])): ?>
            <!-- Realisasi biaya -->
            <div class="cr-pane" data-cr-pane="realisasi" id="realisasi-<?= $iid ?>" hidden>
                <div class="cr-pane-h">
                    <div class="cr-pane-t"><i class="bi bi-receipt"></i>Realisasi Biaya
                        <?php if ($iTotal > 0): ?><span class="cr-chip cr-chip-ok"><?= $rp($iTotal) ?></span><?php endif; ?>
                        <?php if ($nEntries): ?><span class="cr-chip cr-chip-mute"><?= $nEntries ?> entri</span><?php endif; ?>
                    </div>
                    <?php if ($canEdit): ?>
                    <button type="button" class="cr-btn cr-btn-sm cr-btn-primary toggle-add-realisasi" data-iid="<?= $iid ?>"><i class="bi bi-plus-lg"></i>Input</button>
                    <?php endif; ?>
                </div>

                <?php if ($canEdit): ?>
                <div id="add-realisasi-<?= $iid ?>" class="cr-formbox" hidden>
                    <form method="POST" action="<?= base_url('creative/' . $iid . '/realisasi/add') ?>" <?= in_array($tipe, ['influencer', 'cetak', 'media_prescon']) ? 'enctype="multipart/form-data"' : '' ?>>
                        <?= csrf_field() ?>
                        <div class="cr-fgrid">
                            <div class="cr-f w2"><label>Tanggal</label><input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
                            <div class="cr-f w3"><label>Nilai (Rp) <span class="req">*</span></label><input type="text" name="nilai" class="form-control currency-input" placeholder="0" required></div>
                            <?php if ($tipe === 'influencer'): ?>
                            <div class="cr-f w3"><label>Nama Influencer <span class="req">*</span></label><input type="text" name="nama_influencer" class="form-control" placeholder="@username" required></div>
                            <?php endif; ?>
                            <div class="cr-f"><label>Catatan</label><input type="text" name="catatan" class="form-control" placeholder="Opsional"></div>
                            <?php if ($tipe === 'influencer'): ?>
                            <div class="cr-f w6"><label><i class="bi bi-image"></i> Screenshot Insight (opsional)</label><input type="file" name="bukti" class="form-control" accept="image/*"></div>
                            <div class="cr-f w6"><label><i class="bi bi-file-earmark-check"></i> Bukti Serah Terima (opsional)</label><input type="file" name="serah_terima" class="form-control" accept="image/*,.pdf"></div>
                            <?php elseif ($tipe === 'cetak'): ?>
                            <div class="cr-f w6"><label><i class="bi bi-image"></i> Bukti Terpasang (opsional)</label><input type="file" name="bukti_terpasang" class="form-control" accept="image/*,.pdf"></div>
                            <?php elseif ($tipe === 'media_prescon'): ?>
                            <div class="cr-f w6"><label><i class="bi bi-image"></i> Dokumentasi (opsional)</label><input type="file" name="bukti" class="form-control" accept="image/*,.pdf"></div>
                            <?php endif; ?>
                            <div class="cr-f w12 cr-f-act" style="justify-content:flex-end"><button type="submit" class="cr-btn cr-btn-primary"><i class="bi bi-save"></i>Simpan</button></div>
                        </div>
                    </form>
                </div>
                <?php endif; ?>

                <?php if (!empty($iReal['entries'])): ?>
                <div class="cr-tbl-wrap">
                <table class="cr-tbl">
                    <thead><tr>
                        <th>Tanggal</th>
                        <?php if ($tipe === 'influencer'): ?><th>Influencer</th><?php endif; ?>
                        <th class="r">Nilai</th>
                        <th>Catatan</th>
                        <?php if ($tipe === 'influencer'): ?><th title="Screenshot Insight"><i class="bi bi-image"></i> Insight</th><th title="Bukti Serah Terima"><i class="bi bi-file-earmark-check"></i> Serah terima</th><?php endif; ?>
                        <?php if ($tipe === 'cetak'): ?><th title="Bukti Terpasang"><i class="bi bi-image"></i> Terpasang</th><?php endif; ?>
                        <?php if ($tipe === 'media_prescon'): ?><th title="Dokumentasi"><i class="bi bi-image"></i> Dokumentasi</th><?php endif; ?>
                        <?php if ($canEdit): ?><th style="width:48px"></th><?php endif; ?>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($iReal['entries'] as $e): ?>
                    <tr>
                        <td class="nw"><?= $fTgl($e['tanggal']) ?></td>
                        <?php if ($tipe === 'influencer'): ?><td class="fw-semibold"><?= esc($e['nama_influencer'] ?? '—') ?></td><?php endif; ?>
                        <td class="r nw cr-c-ok fw-bold"><?= $rp($e['nilai']) ?></td>
                        <td class="cr-note-cell"><?= esc($e['catatan'] ?? '') ?></td>
                        <?php if ($tipe === 'influencer'): ?>
                        <td><?= $fileCell($e['file_name'] ?? null, $e['original_name'] ?? null, $iid) ?></td>
                        <td><?= $fileCell($e['serah_terima_file_name'] ?? null, $e['serah_terima_original_name'] ?? null, $iid) ?></td>
                        <?php elseif ($tipe === 'cetak'): ?>
                        <td><?= $fileCell($e['bukti_terpasang_file_name'] ?? null, $e['bukti_terpasang_original_name'] ?? null, $iid) ?></td>
                        <?php elseif ($tipe === 'media_prescon'): ?>
                        <td><?= $fileCell($e['file_name'] ?? null, $e['original_name'] ?? null, $iid) ?></td>
                        <?php endif; ?>
                        <?php if ($canEdit): ?>
                        <td>
                            <form method="POST" action="<?= base_url('creative/' . $iid . '/realisasi/' . $e['id'] . '/delete') ?>" onsubmit="return confirm('Hapus entri ini?')">
                                <?= csrf_field() ?>
                                <button type="submit" class="cr-iconbtn cr-iconbtn-sm" title="Hapus entri" aria-label="Hapus entri"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot><tr>
                        <td colspan="<?= $tipe === 'influencer' ? 2 : 1 ?>">Total</td>
                        <td class="r nw cr-c-ok"><?= $rp($iTotal) ?></td>
                        <td colspan="<?= 1 + ($tipe === 'influencer' ? 2 : (in_array($tipe, ['cetak', 'media_prescon']) ? 1 : 0)) + ($canEdit ? 1 : 0) ?>"></td>
                    </tr></tfoot>
                </table>
                </div>
                <?php else: ?>
                <div class="cr-groupempty">Belum ada realisasi biaya.<?= $canEdit ? ' Klik “Input” untuk mencatat.' : '' ?></div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if (isset($panes['insight'])): ?>
            <!-- Insight -->
            <div class="cr-pane" data-cr-pane="insight" id="insight-<?= $iid ?>" hidden>
                <div class="cr-pane-h">
                    <div class="cr-pane-t"><i class="bi bi-graph-up"></i>Insight
                        <?php if ($nIns): ?><span class="cr-chip cr-chip-mute"><?= $nIns ?> entri</span><?php endif; ?>
                    </div>
                    <?php if ($canEdit): ?>
                    <button type="button" class="cr-btn cr-btn-sm cr-btn-primary toggle-add-insight" data-iid="<?= $iid ?>"><i class="bi bi-plus-lg"></i>Input</button>
                    <?php endif; ?>
                </div>

                <?php if ($iInsight): ?>
                <?php
                $insightMetricMap = [
                    'views' => $iInsight['max_views'] ?? 0, 'reach' => $iInsight['max_reach'] ?? 0, 'impressions' => $iInsight['max_impressions'] ?? 0,
                    'likes' => $iInsight['max_likes'] ?? 0, 'comments' => $iInsight['max_comments'] ?? 0, 'shares' => $iInsight['max_shares'] ?? 0,
                    'saves' => $iInsight['max_saves'] ?? 0, 'followers_gained' => $iInsight['total_followers_gained'] ?? 0,
                ];
                ?>
                <div class="cr-metrics">
                    <?php foreach ($insightFields as $field => $fcfg): $val = $insightMetricMap[$field] ?? 0; if ($val <= 0) continue; ?>
                    <div class="cr-metric"><i class="<?= $fcfg['icon'] ?> text-<?= $fcfg['color'] ?>"></i><b><?= number_format($val, 0, ',', '.') ?></b><span><?= $fcfg['label'] ?></span></div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php if (($tgtReach > 0 || $tgtImpr > 0) && ($actReach > 0 || $actImpr > 0)): ?>
                <div style="margin-bottom:.8rem">
                    <?php foreach ([['Reach', $actReach, $tgtReach], ['Impresi', $actImpr, $tgtImpr]] as [$tl, $ta, $tt]): if ($tt <= 0) continue;
                        $pp = min(100, round($ta / $tt * 100)); $bc = $pp >= 100 ? 'cr-bar-ok' : ($pp >= 60 ? '' : 'cr-bar-mid'); ?>
                    <div class="cr-target"><span class="cr-c-mute"><?= $tl ?></span><div class="cr-bar <?= $bc ?>"><i style="width:<?= $pp ?>%"></i></div><em><?= number_format($ta, 0, ',', '.') ?> / <?= number_format($tt, 0, ',', '.') ?> (<?= $pp ?>%)</em></div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php if ($canEdit): ?>
                <div id="add-insight-<?= $iid ?>" class="cr-formbox" hidden>
                    <form method="POST" action="<?= base_url('creative/' . $iid . '/insight/add') ?>" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <div class="cr-fgrid" style="margin-bottom:.6rem">
                            <div class="cr-f w2"><label>Tanggal</label><input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
                            <?php if (($item['platform'] ?? '') === 'keduanya'): ?>
                            <div class="cr-f w2"><label>Platform</label><select name="platform" class="form-select"><option value="ig">Instagram</option><option value="tiktok">TikTok</option></select></div>
                            <?php else: ?>
                            <input type="hidden" name="platform" value="<?= esc($item['platform'] ?? '', 'attr') ?>">
                            <?php endif; ?>
                            <div class="cr-f w4"><label><i class="bi bi-image"></i> Screenshot (opsional)</label><input type="file" name="screenshot" class="form-control" accept="image/*"></div>
                            <div class="cr-f"><label>Catatan</label><input type="text" name="catatan" class="form-control" placeholder="Opsional"></div>
                        </div>
                        <div class="cr-fgrid">
                            <?php foreach ($insightFields as $field => $fcfg): ?>
                            <div class="cr-f w2"><label><i class="<?= $fcfg['icon'] ?> text-<?= $fcfg['color'] ?>"></i> <?= $fcfg['label'] ?></label><input type="number" name="<?= $field ?>" class="form-control" value="0" min="0"></div>
                            <?php endforeach; ?>
                            <div class="cr-f w12 cr-f-act" style="justify-content:flex-end"><button type="submit" class="cr-btn cr-btn-primary"><i class="bi bi-save"></i>Simpan Insight</button></div>
                        </div>
                    </form>
                </div>
                <?php endif; ?>

                <?php if ($nIns): ?>
                <div class="cr-tbl-wrap">
                <table class="cr-tbl">
                    <thead><tr>
                        <th>Tanggal</th>
                        <?php if (($item['platform'] ?? '') === 'keduanya'): ?><th>Platform</th><?php endif; ?>
                        <?php foreach ($insightFields as $field => $fcfg): ?><th class="r" title="<?= $fcfg['label'] ?>"><i class="<?= $fcfg['icon'] ?> text-<?= $fcfg['color'] ?>"></i> <?= $fcfg['label'] ?></th><?php endforeach; ?>
                        <th><i class="bi bi-image"></i></th>
                        <th>Catatan</th>
                        <?php if ($canEdit): ?><th style="width:48px"></th><?php endif; ?>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($iInsight['entries'] as $ins): ?>
                    <tr>
                        <td class="nw"><?= $fTgl($ins['tanggal']) ?></td>
                        <?php if (($item['platform'] ?? '') === 'keduanya'): ?><td><span class="cr-chip cr-chip-t"><?= esc($platformLabels[$ins['platform']] ?? $ins['platform']) ?></span></td><?php endif; ?>
                        <?php foreach ($insightFields as $field => $fcfg): ?>
                        <td class="r nw <?= $ins[$field] > 0 ? 'fw-semibold' : 'cr-c-mute' ?>"><?= $ins[$field] > 0 ? number_format($ins[$field], 0, ',', '.') : '—' ?></td>
                        <?php endforeach; ?>
                        <td>
                            <?php if ($ins['file_name']): $su = base_url('uploads/creative-standalone-insight/' . $iid . '/' . $ins['file_name']); ?>
                            <a href="<?= $su ?>" target="_blank"><img class="thumb" src="<?= $su ?>" alt="Screenshot insight" title="<?= esc($ins['original_name'] ?? '', 'attr') ?>"></a>
                            <?php else: ?><span class="cr-c-mute">—</span><?php endif; ?>
                        </td>
                        <td class="cr-note-cell"><?= esc($ins['catatan'] ?? '') ?></td>
                        <?php if ($canEdit): ?>
                        <td>
                            <form method="POST" action="<?= base_url('creative/' . $iid . '/insight/' . $ins['id'] . '/delete') ?>" onsubmit="return confirm('Hapus entri insight ini?')">
                                <?= csrf_field() ?>
                                <button type="submit" class="cr-iconbtn cr-iconbtn-sm" title="Hapus entri" aria-label="Hapus entri insight"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <?php else: ?>
                <div class="cr-groupempty">Belum ada data insight.<?= $canEdit ? ' Klik “Input” untuk mencatat.' : '' ?></div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </article>
    <?php endforeach; ?>
    </div>
    <div class="cr-more" hidden>
        <button type="button" class="cr-btn cr-btn-sm" data-cr-more><i class="bi bi-chevron-down"></i><span>Tampilkan lebih banyak</span></button>
        <button type="button" class="cr-btn cr-btn-sm" data-cr-all>Tampilkan semua</button>
    </div>
</section>
<?php endforeach; ?>

<!-- Ringkasan -->
<div class="cr-foot">
    <span class="cr-c-mute"><?= $totalStandalone ?> standalone · <?= count($eventItems) ?> dari event</span>
    <span>Total Budget: <b class="cr-c-bad"><?= $rp($totalBudget) ?></b></span>
</div>

<?php endif; // ada item / bisa edit ?>

<?php if ($canEdit): ?>
<!-- Modal ubah item -->
<div class="modal fade" id="editItemModal" tabindex="-1" aria-labelledby="editItemTitle" aria-hidden="true">
<div class="modal-dialog modal-dialog-scrollable"><div class="modal-content">
<form id="editItemForm" method="POST">
<?= csrf_field() ?>
<div class="modal-header">
    <h5 class="modal-title fw-semibold" id="editItemTitle">Ubah Item</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
</div>
<div class="modal-body">
    <div class="mb-3">
        <label class="form-label small fw-semibold">Tipe</label>
        <div id="editTipeDisplay" class="form-control form-control-sm bg-body-secondary text-muted" style="cursor:default"></div>
    </div>
    <div class="mb-3">
        <label class="form-label small fw-semibold">Nama <span class="text-danger">*</span></label>
        <input type="text" name="nama" id="editNama" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label small fw-semibold"><i class="bi bi-calendar3 me-1"></i>Tanggal</label>
        <input type="date" name="tanggal" id="editTanggal" class="form-control">
        <div class="form-text" style="font-size:.72rem">Menentukan item masuk bulan apa di Summary Bulanan.</div>
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
            <div class="col-7"><input type="date" name="tanggal_take" id="editTanggalTake" class="form-control"></div>
            <div class="col-5"><input type="time" name="jam_take" id="editJamTake" class="form-control"></div>
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
    <div id="editTargetDiv" class="d-none">
        <div class="row g-2 mb-3">
            <div class="col-6">
                <label class="form-label small fw-semibold">Target Reach</label>
                <input type="number" name="target_reach" id="editTargetReach" class="form-control" placeholder="0" min="0">
            </div>
            <div class="col-6">
                <label class="form-label small fw-semibold">Target Impresi</label>
                <input type="number" name="target_impressions" id="editTargetImpressions" class="form-control" placeholder="0" min="0">
            </div>
        </div>
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
    <div class="mb-3">
        <label class="form-label small fw-semibold">Catatan</label>
        <input type="text" name="catatan" id="editCatatan" class="form-control">
    </div>
    <div class="form-check mb-0">
        <input type="checkbox" class="form-check-input" name="is_closed" id="editIsClosed" value="1">
        <label class="form-check-label small fw-semibold" for="editIsClosed">Sudah selesai / close</label>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
    <button type="submit" class="btn btn-primary">Simpan</button>
</div>
</form>
</div></div></div>
<?php endif; ?>

</div><!-- /.page-cre -->

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>window.CRE_BASE = <?= json_encode(base_url('creative/')) ?>;</script>
<script src="<?= base_url('js/creative-main.js') ?>?v=<?= @filemtime(FCPATH . 'js/creative-main.js') ?: 1 ?>"></script>
<?= $this->endSection() ?>
