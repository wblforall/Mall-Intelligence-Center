<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/creative-main.css') ?>?v=<?= @filemtime(FCPATH . 'css/creative-main.css') ?: 1 ?>">
<link rel="stylesheet" href="<?= base_url('css/creative-turunan.css') ?>?v=<?= @filemtime(FCPATH . 'css/creative-turunan.css') ?: 1 ?>">
<link rel="stylesheet" href="<?= base_url('css/creative-titik.css') ?>?v=<?= @filemtime(FCPATH . 'css/creative-titik.css') ?: 1 ?>">
<link rel="stylesheet" href="<?= base_url('css/creative-biaya-fs.css') ?>?v=<?= @filemtime(FCPATH . 'css/creative-biaya-fs.css') ?: 1 ?>">
<link rel="stylesheet" href="<?= base_url('css/creative-digital.css') ?>?v=<?= @filemtime(FCPATH . 'css/creative-digital.css') ?: 1 ?>">
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php
$tipeConfig = [
    'master_design' => ['label' => 'Design',          'icon' => 'bi-vector-pen'],
    'motion_graphic'=> ['label' => 'Motion Graphic',  'icon' => 'bi-film'],
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
    'motion_graphic'=> 'Opening Bumper, Teaser Event...',
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
    // Konten Digital yang sudah Tayang / Dilaporkan tidak lagi dihitung terlambat
    $done = !empty($item['is_closed']) || ($item['status'] ?? '') === 'approved'
        || (($item['tipe'] ?? '') === 'digital' && in_array($item['status_konten'] ?? '', ['tayang', 'dilaporkan'], true));
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
        $isRev = \App\Services\CreativeApprovalService::isDesain($it['tipe']) && ($it['status'] ?? '') === 'review';
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

// Variabel bersama untuk kartu Tema & kartu item
$shared = compact('tipeConfig', 'statusConfig', 'platformLabels', 'imageExts', 'insightFields', 'fTgl', 'rp', 'dlInfo', 'fileCell',
                  'files', 'realisasi', 'insights', 'reviews', 'canEdit', 'canApprove', 'canClose', 'temaPilihan',
                  'turunanS', 'turunanE', 'katalog', 'titikS', 'titikE', 'eRealisasi', 'fsBuka', 'insightN');
$shared['shared'] = $shared;

$nNone    = (int) $tema['none']['n'];
$nAktif   = count($tema['aktif']) ? array_sum(array_map(fn($m) => count($m['cards']), $tema['aktif'])) : 0;
$nTutup   = (int) $tema['nTerkunci'];
$bulanOpt = ['01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April', '05' => 'Mei', '06' => 'Juni',
             '07' => 'Juli', '08' => 'Agustus', '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'];
$thnIni   = (int) date('Y');
$renderBulan = function (array $months) use ($shared, $rp) {
    foreach ($months as $i => $m): ?>
    <section class="cr-monthbox <?= $i < 2 ? 'is-open' : '' ?>" data-month="<?= esc($m['bulan'], 'attr') ?>">
        <button type="button" class="cr-month-h" data-cr-month-toggle aria-expanded="<?= $i < 2 ? 'true' : 'false' ?>">
            <span class="cr-chev"><i class="bi bi-chevron-right"></i></span>
            <span class="cr-month-t"><?= esc($m['label']) ?></span>
            <span class="cr-month-s">
                <span><b class="js-mn"><?= count($m['cards']) ?></b> tema</span>
                <?php if ((int) $m['budget'] > 0): ?><span>Budget <b><?= $rp($m['budget']) ?></b></span><?php endif; ?>
                <?php if ((int) $m['realisasi'] > 0): ?><span>Realisasi <b><?= $rp($m['realisasi']) ?></b></span><?php endif; ?>
            </span>
        </button>
        <div class="cr-month-body">
            <?php foreach ($m['cards'] as $c): ?>
                <?= view('creative_main/_tema_card', ['c' => $c] + $shared, ['saveData' => false]) ?>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endforeach;
};
?>

<div class="page-cre" id="creIdx">

<!-- Header -->
<div class="cr-head">
    <div class="cr-head-ico"><i class="bi bi-palette-fill"></i></div>
    <div class="min-w-0">
        <h1 class="cr-title">Creative &amp; Design</h1>
        <div class="cr-subtitle">Semua materi desain, konten, dan media dikelompokkan per Tema — event atau tema bulanan</div>
    </div>
    <div class="cr-head-act">
        <a href="<?= base_url('creative/monthly-summary') ?>" class="cr-btn"><i class="bi bi-bar-chart-line"></i><span class="d-none d-sm-inline">Summary Bulanan</span></a>
        <?php if (!empty($canKatalog)): ?>
        <a href="<?= base_url('creative/katalog') ?>" class="cr-btn" title="Atur katalog turunan dan paket item awal"><i class="bi bi-sliders"></i><span class="d-none d-sm-inline">Katalog &amp; Paket</span></a>
        <?php endif; ?>
        <?php if ($canEdit): ?>
        <button type="button" class="cr-btn" data-cr-tema-new><i class="bi bi-calendar-plus"></i><span class="d-none d-sm-inline">Tema Bulanan</span></button>
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
        <div class="cr-kpi-s">dari <?= count($standaloneItems) + count($eventItems) ?> total<?php if (!empty($nRencana)): ?> · <b><?= (int) $nRencana ?> item rencana</b><?php endif; ?></div>
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
        <div class="cr-kpi-l" id="crKpiBL">Total Budget</div>
        <div class="cr-kpi-v" id="crKpiBV"><?= $rp($totalBudget) ?></div>
        <div class="cr-kpi-s" id="crKpiBS">standalone + event</div>
    </div>
    <div class="cr-kpi">
        <div class="cr-kpi-l" id="crKpiRL">Total Realisasi</div>
        <div class="cr-kpi-v <?= $overBudget ? 'cr-c-bad' : 'cr-c-ok' ?>" id="crKpiRV"><?= $rp($totalRealisasi) ?></div>
        <div class="cr-kpi-s" id="crKpiRS"><?= $realPct !== null ? $realPct . '% dari budget' : 'standalone only' ?></div>
        <div class="cr-bar <?= $overBudget ? 'cr-bar-over' : 'cr-bar-ok' ?>" id="crKpiBar" <?= $realPct === null ? 'hidden' : '' ?>><i style="width:<?= (int) $realPct ?>%"></i></div>
    </div>
</div>

<?php if ($nAll === 0 && !$canEdit && $tema['nTema'] === 0): ?>
<div class="cr-empty"><i class="bi bi-palette"></i><h5>Belum ada Tema atau item Creative</h5><p>Tema dan item creative akan tampil di sini setelah dibuat.</p></div>
<?php else: ?>

<!-- Pencarian & filter -->
<div class="cr-tools" id="crTools">
    <div class="cr-search">
        <i class="bi bi-search"></i>
        <input type="search" id="crQ" placeholder="Cari tema, item, PIC, catatan…" autocomplete="off" aria-label="Cari item creative">
    </div>
    <div class="cr-seg" id="creativeTabs" role="group" aria-label="Fase">
        <button type="button" class="is-on" data-tab="">Semua <span class="n"><?= $nAll ?></span></button>
        <button type="button" data-tab="review"><i class="bi bi-hourglass-split"></i>Review <span class="n"><?= (int) ($tabCounts['review'] ?? 0) ?></span></button>
        <button type="button" data-tab="realisasi"><i class="bi bi-receipt-cutoff"></i>Realisasi <span class="n"><?= (int) ($tabCounts['realisasi'] ?? 0) ?></span></button>
    </div>
    <select class="cr-select" id="crFBulan" aria-label="Filter bulan">
        <option value="">Semua bulan</option>
        <?php // bulan = bulan Tema; item "Tanpa Tema" memakai bulan tanggal itemnya
        $bulanOps = [];
        foreach (array_merge($tema['aktif'] ?? [], $tema['terkunci'] ?? []) as $mb) if (preg_match('/^\d{4}-\d{2}$/', (string) $mb['bulan'])) $bulanOps[$mb['bulan']] = 1;
        foreach (($tema['none']['items'] ?? []) as $nit) { $nb = substr((string) (($nit['tanggal'] ?? '') ?: ($nit['tanggal_take'] ?? '')), 0, 7); if (preg_match('/^\d{4}-\d{2}$/', $nb)) $bulanOps[$nb] = 1; }
        krsort($bulanOps);
        foreach (array_keys($bulanOps) as $bo): $bl = \App\Services\CreativeTemaService::bulanLabel($bo); ?>
        <option value="<?= esc($bo, 'attr') ?>" data-label="<?= esc($bl, 'attr') ?>"><?= esc($bl) ?></option>
        <?php endforeach; ?>
    </select>
    <select class="cr-select" id="crFTipe" aria-label="Filter jenis">
        <option value="">Semua jenis</option>
        <?php foreach ($tipeConfig as $tp => $tc): ?><option value="<?= $tp ?>"><?= $tc['label'] ?></option><?php endforeach; ?>
    </select>
    <select class="cr-select" id="crFStatus" aria-label="Filter status">
        <option value="">Semua status</option>
        <option value="review">Dalam Review</option>
        <option value="revision">Perlu Revisi</option>
        <option value="approved">Approved</option>
        <option value="draft">Draft (Design &amp; Motion)</option>
        <option value="closed">Selesai / close</option>
    </select>
    <select class="cr-select" id="crFKonten" aria-label="Filter status konten digital">
        <option value="">Semua status konten</option>
        <option value="rencana">Rencana</option>
        <option value="produksi">Produksi</option>
        <option value="tayang">Tayang</option>
        <option value="dilaporkan">Dilaporkan</option>
        <option value="belum">Belum diatur</option>
    </select>
    <?php if (!empty($picDaftar)): ?>
    <select class="cr-select" id="crFPic" aria-label="Filter PIC konten digital">
        <option value="">Semua PIC</option>
        <?php foreach ($picDaftar as $pc): ?><option value="<?= esc(mb_strtolower($pc), 'attr') ?>"><?= esc($pc) ?></option><?php endforeach; ?>
    </select>
    <?php endif; ?>
    <select class="cr-select" id="crFDl" aria-label="Filter tenggat">
        <option value="">Semua tenggat</option>
        <option value="late">Terlambat</option>
        <option value="soon">Segera (≤ 3 hari)</option>
        <option value="has">Punya tenggat</option>
        <option value="none">Tanpa tenggat</option>
    </select>
    <select class="cr-select" id="crFSrc" aria-label="Filter sumber">
        <option value="">Semua sumber</option>
        <option value="s">Dikerjakan di sini</option>
        <option value="e">Item event (baca-saja)</option>
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
        <?php if (!empty($nStatusBelum)): ?><button type="button" class="cr-btn cr-btn-sm" data-sm-open data-usulan="<?= base_url('creative/status-massal/usulan') ?>" title="Atur status konten untuk konten digital lama yang statusnya belum diatur"><i class="bi bi-list-check"></i>Atur status <span class="cr-chip cr-chip-warn"><?= (int) $nStatusBelum ?></span></button><?php endif; ?>
        <span class="cr-count" id="crCount" aria-live="polite"><?= $nAll ?> item</span>
        <button type="button" class="cr-btn cr-btn-sm" id="crReset"><i class="bi bi-x-circle"></i>Reset</button>
    </div>
</div>

<div class="cr-empty" id="crNoMatch" hidden>
    <i class="bi bi-funnel"></i><h5>Tidak ada item yang cocok</h5>
    <p>Ubah kata kunci atau filter, atau reset untuk melihat semua item.</p>
    <button type="button" class="cr-btn" data-cr-resetbtn><i class="bi bi-x-circle"></i>Reset filter</button>
</div>

<div class="cr-ptabs" id="crPTabs" role="tablist" aria-label="Status tema">
    <button type="button" role="tab" class="is-on" data-cr-ptab="aktif" aria-selected="true">Aktif <span class="n" data-n0="<?= $nAktif ?>"><?= $nAktif ?></span></button>
    <button type="button" role="tab" data-cr-ptab="terkunci" aria-selected="false"><i class="bi bi-lock-fill"></i>Terkunci <span class="n" data-n0="<?= $nTutup ?>"><?= $nTutup ?></span></button>
</div>

<div id="crPaneAktif" data-cr-pane-wrap="aktif">
    <?php if ($nNone > 0): ?>
    <section class="cr-monthbox is-open" data-month="none">
        <div class="cr-month-body" style="display:flex">
            <?= view('creative_main/_tema_card', ['c' => $tema['none']] + $shared, ['saveData' => false]) ?>
        </div>
    </section>
    <?php endif; ?>
    <?php if (empty($tema['aktif'])): ?>
    <div class="cr-empty"><i class="bi bi-calendar-event"></i><h5>Belum ada Tema aktif</h5><p>Tema event muncul otomatis dari daftar event. Buat Tema Bulanan untuk pekerjaan di luar event.</p></div>
    <?php else: $renderBulan($tema['aktif']); endif; ?>
</div>

<div id="crPaneTerkunci" data-cr-pane-wrap="terkunci" hidden>
    <?php if (empty($tema['terkunci'])): ?>
    <div class="cr-empty"><i class="bi bi-lock"></i><h5>Belum ada Tema yang ditutup</h5><p>Tema yang sudah selesai ditutup oleh lead Creative atau admin supaya daftar utama tetap ringkas. Materinya terkunci sampai dibuka kembali.</p></div>
    <?php else: $renderBulan($tema['terkunci']); endif; ?>
</div>

<!-- Ringkasan -->
<div class="cr-foot">
    <span class="cr-c-mute"><?= $totalStandalone ?> item di sini · <?= count($eventItems) ?> dari tab event · <?= $tema['nTema'] ?> tema</span>
    <span>Total Budget: <b class="cr-c-bad"><?= $rp($totalBudget) ?></b></span>
</div>

<?php endif; ?>

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
        <label class="form-label small fw-semibold" for="editTema">Tema</label>
        <select name="tema" id="editTema" class="form-select">
            <option value="none">Tanpa Tema</option>
            <?php if ($temaPilihan['bulanan']): ?><optgroup label="Tema Bulanan">
                <?php foreach ($temaPilihan['bulanan'] as $o): ?><option value="<?= esc($o['val'], 'attr') ?>"><?= esc($o['nama']) ?> · <?= esc($o['ket']) ?></option><?php endforeach; ?>
            </optgroup><?php endif; ?>
            <?php if ($temaPilihan['event']): ?><optgroup label="Event">
                <?php foreach ($temaPilihan['event'] as $o): ?><option value="<?= esc($o['val'], 'attr') ?>"><?= esc($o['nama']) ?> · <?= esc($o['ket']) ?></option><?php endforeach; ?>
            </optgroup><?php endif; ?>
        </select>
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
        <label class="form-label small fw-semibold" for="editPic"><i class="bi bi-person me-1"></i>PIC</label>
        <input type="text" name="pic" id="editPic" class="form-control" placeholder="Pilih dari daftar atau ketik nama" list="picList" maxlength="150" autocomplete="off">
    </div>
    <div id="editDigitalDiv" class="row g-2 mb-3 d-none">
        <?= view('_creative/_digital_fields', ['digitalKatalog' => $digitalKatalog, 'uid' => 'edit-d'], ['saveData' => false]) ?>
    </div>
    <div id="editCetakDiv" class="row g-2 mb-3 d-none">
        <?= view('_creative/_cetak_fields', ['cetakKatalog' => $cetakKatalog, 'uid' => 'edit', 'jenisId' => 'editJenisMedia', 'ukuranId' => 'editUkuran'], ['saveData' => false]) ?>
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

<?php if ($canEdit): ?>
<!-- Modal tambah item -->
<div class="modal fade" id="addItemModal" tabindex="-1" aria-labelledby="addItemTitle" aria-hidden="true">
<div class="modal-dialog modal-dialog-scrollable"><div class="modal-content">
<form id="addItemForm" method="POST" action="<?= base_url('creative/add') ?>">
<?= csrf_field() ?>
<div class="modal-header">
    <h5 class="modal-title fw-semibold" id="addItemTitle">Tambah Item</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
</div>
<div class="modal-body">
    <div class="row g-2 mb-3">
        <div class="col-sm-6">
            <label class="form-label small fw-semibold" for="addTipe">Jenis <span class="text-danger">*</span></label>
            <select name="tipe" id="addTipe" class="form-select" required>
                <?php foreach ($tipeConfig as $tp => $tc): ?><option value="<?= $tp ?>"><?= $tc['label'] ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="col-sm-6">
            <label class="form-label small fw-semibold" for="addTema">Tema</label>
            <select name="tema" id="addTema" class="form-select">
                <option value="">Tanpa Tema</option>
                <?php if ($temaPilihan['bulanan']): ?><optgroup label="Tema Bulanan">
                    <?php foreach ($temaPilihan['bulanan'] as $o): ?><option value="<?= esc($o['val'], 'attr') ?>"><?= esc($o['nama']) ?> · <?= esc($o['ket']) ?></option><?php endforeach; ?>
                </optgroup><?php endif; ?>
            </select>
            <div class="form-text" style="font-size:.72rem">Item untuk event diisi di tab Creative event.</div>
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label small fw-semibold" for="addNama">Nama <span class="text-danger">*</span></label>
        <input type="text" name="nama" id="addNama" class="form-control" required>
    </div>
    <div class="row g-2 mb-3">
        <div class="col-sm-6">
            <label class="form-label small fw-semibold"><i class="bi bi-calendar3 me-1"></i>Tanggal</label>
            <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>">
        </div>
        <div class="col-sm-6">
            <label class="form-label small fw-semibold">Deadline</label>
            <input type="date" name="deadline" class="form-control">
        </div>
    </div>
    <div class="row g-2 mb-3" data-add-for="digital">
        <div class="col-sm-4"><label class="form-label small fw-semibold">Platform</label>
            <select name="platform" class="form-select"><option value="ig">Instagram</option><option value="tiktok">TikTok</option><option value="keduanya">IG &amp; TikTok</option></select></div>
        <div class="col-sm-4"><label class="form-label small fw-semibold"><i class="bi bi-camera me-1"></i>Tanggal Take</label><input type="date" name="tanggal_take" class="form-control"></div>
        <div class="col-sm-4"><label class="form-label small fw-semibold">Waktu Take</label><input type="time" name="jam_take" class="form-control"></div>
        <div class="col-12"><label class="form-label small fw-semibold" for="addPic"><i class="bi bi-person me-1"></i>PIC</label><input type="text" name="pic" id="addPic" class="form-control" placeholder="Pilih dari daftar atau ketik nama" list="picList" maxlength="150" autocomplete="off"></div>
    </div>
    <div class="row g-2 mb-3" data-add-for="digital">
        <?= view('_creative/_digital_fields', ['digitalKatalog' => $digitalKatalog, 'uid' => 'add-d'], ['saveData' => false]) ?>
    </div>
    <div class="row g-2 mb-3" data-add-for="cetak">
        <?= view('_creative/_cetak_fields', ['cetakKatalog' => $cetakKatalog, 'uid' => 'add'], ['saveData' => false]) ?>
    </div>
    <div class="mb-3" data-add-for="cetak influencer digital media_prescon">
        <label class="form-label small fw-semibold">Budget (Rp)</label>
        <input type="text" name="budget" class="form-control currency-input" placeholder="0">
    </div>
    <div class="row g-2 mb-3" data-add-for="digital influencer">
        <div class="col-6"><label class="form-label small fw-semibold">Target Reach</label><input type="number" name="target_reach" class="form-control" placeholder="0" min="0"></div>
        <div class="col-6"><label class="form-label small fw-semibold">Target Impresi</label><input type="number" name="target_impressions" class="form-control" placeholder="0" min="0"></div>
    </div>
    <div class="mb-3">
        <label class="form-label small fw-semibold">Deskripsi / Catatan</label>
        <input type="text" name="deskripsi" class="form-control" placeholder="Opsional">
    </div>
    <div class="form-check mb-0">
        <input type="checkbox" class="form-check-input" name="is_closed" id="addIsClosed" value="1">
        <label class="form-check-label small fw-semibold" for="addIsClosed">Sudah selesai / close</label>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
    <button type="submit" class="btn btn-primary">Tambah</button>
</div>
</form>
</div></div></div>

<!-- Modal Tema Bulanan (tambah / ubah) -->
<div class="modal fade" id="temaModal" tabindex="-1" aria-labelledby="temaModalTitle" aria-hidden="true">
<div class="modal-dialog"><div class="modal-content">
<form id="temaForm" method="POST" action="<?= base_url('creative/tema/add') ?>">
<?= csrf_field() ?>
<input type="hidden" name="bulan" id="temaBulan">
<div class="modal-header">
    <h5 class="modal-title fw-semibold" id="temaModalTitle">Tema Bulanan</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
</div>
<div class="modal-body">
    <div class="mb-3">
        <label class="form-label small fw-semibold" for="temaNama">Nama tema <span class="text-danger">*</span></label>
        <input type="text" id="temaNama" name="nama" class="form-control" required maxlength="200" placeholder="mis. Design Agustus — Unity in Diversity Festive">
    </div>
    <div class="row g-2 mb-3">
        <div class="col-7">
            <label class="form-label small fw-semibold" for="temaBln">Bulan</label>
            <select id="temaBln" class="form-select"><?php foreach ($bulanOpt as $v => $l): ?><option value="<?= $v ?>"><?= $l ?></option><?php endforeach; ?></select>
        </div>
        <div class="col-5">
            <label class="form-label small fw-semibold" for="temaThn">Tahun</label>
            <select id="temaThn" class="form-select"><?php for ($y = $thnIni - 2; $y <= $thnIni + 1; $y++): ?><option value="<?= $y ?>" <?= $y === $thnIni ? 'selected' : '' ?>><?= $y ?></option><?php endfor; ?></select>
        </div>
    </div>
    <div class="mb-0">
        <label class="form-label small fw-semibold" for="temaDesk">Deskripsi</label>
        <textarea id="temaDesk" name="deskripsi" class="form-control" rows="2" placeholder="Opsional"></textarea>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
    <button type="submit" class="btn btn-primary">Simpan</button>
</div>
</form>
</div></div></div>

<!-- Modal pindahkan ke tema -->
<div class="modal fade" id="pindahModal" tabindex="-1" aria-labelledby="pindahTitle" aria-hidden="true">
<div class="modal-dialog"><div class="modal-content">
<form id="pindahForm" method="POST">
<?= csrf_field() ?>
<div class="modal-header">
    <h5 class="modal-title fw-semibold" id="pindahTitle">Pindahkan ke tema</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
</div>
<div class="modal-body">
    <p class="small text-muted mb-2" id="pindahNama"></p>
    <label class="form-label small fw-semibold" for="pindahTema">Tema tujuan</label>
    <select name="tema" id="pindahTema" class="form-select">
        <option value="none">Tanpa Tema</option>
        <?php if ($temaPilihan['bulanan']): ?><optgroup label="Tema Bulanan">
            <?php foreach ($temaPilihan['bulanan'] as $o): ?><option value="<?= esc($o['val'], 'attr') ?>"><?= esc($o['nama']) ?> · <?= esc($o['ket']) ?></option><?php endforeach; ?>
        </optgroup><?php endif; ?>
        <?php if ($temaPilihan['event']): ?><optgroup label="Event">
            <?php foreach ($temaPilihan['event'] as $o): ?><option value="<?= esc($o['val'], 'attr') ?>"><?= esc($o['nama']) ?> · <?= esc($o['ket']) ?></option><?php endforeach; ?>
        </optgroup><?php endif; ?>
    </select>
    <div class="form-text" style="font-size:.72rem">Hanya memindahkan penautan; isi dan persetujuan materi tidak berubah.</div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
    <button type="submit" class="btn btn-primary">Pindahkan</button>
</div>
</form>
</div></div></div>
<?php endif; ?>

<?php if ($canEdit): ?>
<?= view('_creative/_turunan_picker', ['katalog' => $katalog, 'pfx' => 'cr'], ['saveData' => false]) ?>
<?= view('_creative/_titik_picker', ['pfx' => 'cr'], ['saveData' => false]) ?>
<?php endif; ?>

<datalist id="picList"><?php foreach (($picDaftar ?? []) as $pc): ?><option value="<?= esc($pc, 'attr') ?>"><?php endforeach; ?></datalist>

<?php if ($canEdit && !empty($nStatusBelum)): ?>
<!-- Dialog: Atur status massal untuk konten digital lama (usulan; tidak ada penulisan otomatis) -->
<div class="modal fade" id="statusMassalModal" tabindex="-1" aria-labelledby="statusMassalTitle" aria-hidden="true">
<div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">
<form id="smForm" method="POST" action="<?= base_url('creative/status-massal/terapkan') ?>">
<?= csrf_field() ?>
<div class="modal-header">
    <h5 class="modal-title fw-semibold" id="statusMassalTitle">Atur status konten digital</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
</div>
<div class="modal-body sm-wrap">
    <p class="sm-lead">Konten lama belum punya status konten. Sistem hanya <b>mengusulkan</b>: ada insight → Dilaporkan; tanggal take sudah lewat → Tayang; selain itu Rencana. Ubah bila perlu, centang yang ingin diatur, lalu terapkan. Tidak ada yang ditulis sebelum Anda menekan Terapkan.</p>
    <div class="sm-scroll" id="smBody"></div>
    <div class="alert alert-danger py-2 mt-2 mb-0" id="smError" hidden></div>
</div>
<div class="modal-footer">
    <span class="small text-muted me-auto" id="smSum" aria-live="polite"></span>
    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
    <button type="submit" class="btn btn-primary" id="smSubmit" disabled><i class="bi bi-check2-square me-1"></i>Terapkan status terpilih</button>
</div>
</form>
</div></div></div>
<?php endif; ?>

<?php if (!empty($canClose)): ?>
<!-- Dialog: tutup Tema yang masih punya item Rencana -->
<div class="modal fade" id="rencanaTutupModal" tabindex="-1" aria-labelledby="rencanaTutupTitle" aria-hidden="true">
<div class="modal-dialog modal-dialog-centered"><div class="modal-content">
<div class="modal-header">
    <h5 class="modal-title fw-semibold" id="rencanaTutupTitle">Tutup tema</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
</div>
<div class="modal-body">
    <p class="mb-2"><b id="rencanaTutupN">0</b> item belum dikerjakan di tema <b id="rencanaTutupNama"></b>: hapus atau tetap?</p>
    <p class="small text-muted mb-0">“Hapus yang belum dikerjakan” menghapus item rencana dari paket awal yang belum diisi. “Tetap dan tutup” membiarkan item itu apa adanya lalu mengunci tema.</p>
</div>
<div class="modal-footer flex-wrap">
    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
    <button type="button" class="btn btn-outline-primary" data-rencana="tetap"><i class="bi bi-lock me-1"></i>Tetap dan tutup</button>
    <button type="button" class="btn btn-danger" data-rencana="hapus"><i class="bi bi-trash me-1"></i>Hapus yang belum dikerjakan</button>
</div>
</div></div></div>
<?php endif; ?>

</div><!-- /.page-cre -->

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>window.CRE_BASE = <?= json_encode(base_url('creative/')) ?>; window.CRE_CETAK = <?= json_encode($cetakKatalog ?? [], JSON_UNESCAPED_UNICODE) ?>; window.CRE_TITIKJENIS = <?= json_encode($jenisMaster ?? [], JSON_UNESCAPED_UNICODE) ?>; window.CRE_DESIGNS = <?= json_encode((object) ($designMap ?? []), JSON_UNESCAPED_UNICODE) ?>; window.CRE_DIGITAL = <?= json_encode($digitalKatalog ?? [], JSON_UNESCAPED_UNICODE) ?>;</script>
<script src="<?= base_url('js/creative-main.js') ?>?v=<?= @filemtime(FCPATH . 'js/creative-main.js') ?: 1 ?>"></script>
<script src="<?= base_url('js/creative-turunan.js') ?>?v=<?= @filemtime(FCPATH . 'js/creative-turunan.js') ?: 1 ?>"></script>
<script src="<?= base_url('js/creative-titik.js') ?>?v=<?= @filemtime(FCPATH . 'js/creative-titik.js') ?: 1 ?>"></script>
<script src="<?= base_url('js/creative-digital.js') ?>?v=<?= @filemtime(FCPATH . 'js/creative-digital.js') ?: 1 ?>"></script>
<?= $this->endSection() ?>
