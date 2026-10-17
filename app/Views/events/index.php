<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/events-core.css') ?>?v=<?= @filemtime(FCPATH . 'css/events-core.css') ?: 1 ?>">
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php
$mallShort = ['ewalk' => 'eWalk', 'pentacity' => 'Pentacity', 'keduanya' => 'eWalk & Pentacity'];
$mallFull  = ['ewalk' => 'eWalk Simply FUNtastic', 'pentacity' => 'Pentacity Shopping Venue', 'keduanya' => 'eWalk Simply FUNtastic & Pentacity Shopping Venue'];
$bulanNama = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$bulanPdk  = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
$tglPdk = function (string $d) use ($bulanPdk): string {
    $t = strtotime($d);
    return (int) date('j', $t) . ' ' . $bulanPdk[(int) date('n', $t)] . ' ' . date('Y', $t);
};
// status bawaan model (draft/active/waiting_data/completed) -> kunci & label tampilan
$statusMap = [
    'draft'        => ['upcoming', 'Akan datang',      'blue'],
    'active'       => ['running',  'Berjalan',         'ok'],
    'waiting_data' => ['waiting',  'Perlu dilengkapi', 'warn'],
    'completed'    => ['done',     'Selesai',          'mute'],
];
$today     = date('Y-m-d');
$todayTs   = strtotime($today);
$reqCount  = count($requiredModules ?? []);
$isAdmin   = ($user['role'] ?? '') === 'admin';
$canEditEv = in_array($user['role'] ?? '', ['admin', 'manager'], true);

$kpi = ['upcoming' => 0, 'running' => 0, 'waiting' => 0, 'done' => 0];
$yearOpts = [];
foreach ($events as $e) {
    $k = $statusMap[$e['status']][0] ?? 'done';
    $kpi[$k] = ($kpi[$k] ?? 0) + 1;
    if (! empty($e['start_date'])) $yearOpts[date('Y', strtotime($e['start_date']))] = true;
}
krsort($yearOpts);
$total = count($events);
?>

<div class="page-evc" id="evIdx">

<!-- Header -->
<div class="ev-head">
    <div class="ev-head-ico"><i class="bi bi-calendar-event"></i></div>
    <div class="min-w-0">
        <h1 class="ev-title">Daftar Event</h1>
        <div class="ev-subtitle">Kelola event mal: jadwal, lokasi, dan kelengkapan data tiap modul</div>
    </div>
    <div class="ev-head-act">
        <?php if ($isAdmin): ?>
        <a href="<?= base_url('event-locations') ?>" class="ev-btn"><i class="bi bi-geo-alt"></i><span class="d-none d-md-inline">Master Lokasi</span></a>
        <?php endif; ?>
        <a href="<?= base_url('events/compare') ?>" class="ev-btn"><i class="bi bi-arrow-left-right"></i><span class="d-none d-sm-inline">Bandingkan</span></a>
        <?php if ($canCreate ?? false): ?>
        <a href="<?= base_url('events/create') ?>" class="ev-btn ev-btn-primary ev-btn-lg"><i class="bi bi-plus-lg"></i>Tambah Event</a>
        <?php endif; ?>
    </div>
</div>

<?php if (($pendingCount ?? 0) > 0 || ($incompleteCount ?? 0) > 0): ?>
<div class="ev-notes">
    <?php if (($pendingCount ?? 0) > 0): ?>
    <div class="ev-note ev-note-info" role="status">
        <i class="bi bi-hourglass-split"></i>
        <div class="ev-note-t"><strong><?= (int) $pendingCount ?> event</strong> menunggu persetujuan Anda.</div>
        <button type="button" class="ev-btn ev-btn-sm" data-ev-quick="appr:pending">Tampilkan</button>
    </div>
    <?php endif; ?>
    <?php if (($incompleteCount ?? 0) > 0): ?>
    <div class="ev-note ev-note-warn" role="status">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <div class="ev-note-t"><strong><?= (int) $incompleteCount ?> event</strong> sudah selesai tetapi datanya belum dilengkapi.</div>
        <button type="button" class="ev-btn ev-btn-sm" data-ev-quick="status:waiting">Tampilkan</button>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Strip KPI -->
<div class="ev-kpis">
    <button type="button" class="ev-kpi" data-ev-quick="status:">
        <div class="ev-kpi-l">Semua Event</div>
        <div class="ev-kpi-v"><?= $total ?></div>
        <div class="ev-kpi-s">terdaftar</div>
    </button>
    <button type="button" class="ev-kpi" data-ev-quick="status:upcoming">
        <div class="ev-kpi-l">Akan Datang</div>
        <div class="ev-kpi-v ev-c-blue"><?= $kpi['upcoming'] ?></div>
        <div class="ev-kpi-s">belum dimulai</div>
    </button>
    <button type="button" class="ev-kpi" data-ev-quick="status:running">
        <div class="ev-kpi-l">Berjalan</div>
        <div class="ev-kpi-v ev-c-ok"><?= $kpi['running'] ?></div>
        <div class="ev-kpi-s">sedang berlangsung</div>
    </button>
    <button type="button" class="ev-kpi" data-ev-quick="status:waiting">
        <div class="ev-kpi-l">Perlu Dilengkapi</div>
        <div class="ev-kpi-v <?= $kpi['waiting'] > 0 ? 'ev-c-warn' : '' ?>"><?= $kpi['waiting'] ?></div>
        <div class="ev-kpi-s">selesai, data belum lengkap</div>
    </button>
    <button type="button" class="ev-kpi" data-ev-quick="status:done">
        <div class="ev-kpi-l">Selesai</div>
        <div class="ev-kpi-v"><?= $kpi['done'] ?></div>
        <div class="ev-kpi-s">data lengkap</div>
    </button>
</div>

<?php if (empty($events)): ?>
<div class="ev-empty">
    <i class="bi bi-calendar-plus"></i>
    <h5>Belum ada event</h5>
    <p>Mulai dengan membuat event baru, lalu lengkapi rundown, loyalty, VM, creative, exhibitor, dan sponsornya.</p>
    <?php if ($canCreate ?? false): ?>
    <a href="<?= base_url('events/create') ?>" class="ev-btn ev-btn-primary"><i class="bi bi-plus-lg"></i>Buat Event Pertama</a>
    <?php endif; ?>
</div>
<?php else: ?>

<!-- Pencarian & filter -->
<div class="ev-tools">
    <div class="ev-search">
        <i class="bi bi-search"></i>
        <input type="search" id="evQ" placeholder="Cari nama, tema, atau lokasi event…" autocomplete="off" aria-label="Cari event">
    </div>
    <select class="ev-select" id="evFStatus" aria-label="Filter status">
        <option value="">Semua status</option>
        <option value="upcoming">Akan datang</option>
        <option value="running">Berjalan</option>
        <option value="waiting">Perlu dilengkapi</option>
        <option value="done">Selesai</option>
    </select>
    <?php if ($canApprove ?? false): ?>
    <select class="ev-select" id="evFAppr" aria-label="Filter persetujuan">
        <option value="">Semua approval</option>
        <option value="pending">Pending</option>
        <option value="approved">Disetujui</option>
        <option value="rejected">Ditolak</option>
    </select>
    <?php endif; ?>
    <select class="ev-select" id="evFYear" aria-label="Filter tahun">
        <option value="">Semua tahun</option>
        <?php foreach (array_keys($yearOpts) as $y): ?><option value="<?= $y ?>"><?= $y ?></option><?php endforeach; ?>
    </select>
    <select class="ev-select" id="evFMonth" aria-label="Filter bulan">
        <option value="">Semua bulan</option>
        <?php for ($m = 1; $m <= 12; $m++): ?><option value="<?= $m ?>"><?= $bulanNama[$m] ?></option><?php endfor; ?>
    </select>
    <select class="ev-select" id="evFMall" aria-label="Filter mall">
        <option value="">Semua mall</option>
        <option value="ewalk">eWalk</option>
        <option value="pentacity">Pentacity</option>
        <option value="keduanya">Keduanya</option>
    </select>
    <div class="ev-tools-end">
        <select class="ev-select" id="evSort" aria-label="Urutkan">
            <option value="baru">Terbaru</option>
            <option value="lama">Terlama</option>
            <option value="nama">Nama A–Z</option>
            <option value="prog">Kelengkapan terendah</option>
        </select>
        <span class="ev-count" id="evCount" aria-live="polite"><?= $total ?> event</span>
        <button type="button" class="ev-btn ev-btn-sm" id="evReset" hidden><i class="bi bi-x-lg"></i>Reset</button>
    </div>
</div>

<div class="ev-list" id="evList">
<?php foreach ($events as $i => $e):
    [$stKey, $stLabel, $stTone] = $statusMap[$e['status']] ?? ['done', ucfirst((string) $e['status']), 'mute'];
    $appr       = $e['approval_status'] ?? 'approved';
    $isPending  = $appr === 'pending';
    $isRejected = $appr === 'rejected';
    $days       = max(1, (int) $e['event_days']);
    $startDate  = $e['start_date'];
    $endDate    = $startDate ? date('Y-m-d', strtotime($startDate . ' +' . ($days - 1) . ' days')) : null;
    $done       = array_values(array_intersect(array_keys($requiredModules ?? []), $doneMap[(int) $e['id']] ?? []));
    $doneN      = count($done);
    $locs       = $locMap[(int) $e['id']] ?? [];
    $mallKey    = $e['mall'] ?? '';
    $jenisRutin = ($e['jenis'] ?? 'event') === 'rutin';
    $tsStart    = $startDate ? strtotime($startDate) : 0;
    $ctx = '';
    if ($startDate) {
        if ($stKey === 'upcoming') {
            $dd = (int) round(($tsStart - $todayTs) / 86400);
            $ctx = $dd <= 0 ? 'Mulai hari ini' : 'Mulai ' . $dd . ' hari lagi';
        } elseif ($stKey === 'running') {
            $ke = (int) round(($todayTs - $tsStart) / 86400) + 1;
            $ctx = 'Hari ke-' . $ke . ' dari ' . $days;
        }
    }
    $pctMod = $reqCount > 0 ? round($doneN / $reqCount * 100) : 0;
    $barCls = $doneN >= $reqCount ? 'ok' : ($doneN >= max(1, (int) ceil($reqCount / 2)) ? 'mid' : 'low');
    $search = mb_strtolower($e['name'] . ' ' . ($e['tema'] ?? '') . ' ' . implode(' ', $locs) . ' ' . ($mallFull[$mallKey] ?? $mallKey));
    $href   = base_url('events/' . $e['id'] . '/summary');
?>
<article class="ev-card ev-st-<?= $stKey ?><?= $isPending ? ' is-pending' : '' ?><?= $isRejected ? ' is-rejected' : '' ?>"
         id="event-<?= (int) $e['id'] ?>" data-href="<?= $href ?>"
         data-name="<?= esc($e['name'], 'attr') ?>"
         data-search="<?= esc($search, 'attr') ?>"
         data-status="<?= $stKey ?>" data-appr="<?= esc($appr, 'attr') ?>"
         data-year="<?= $startDate ? date('Y', $tsStart) : '' ?>" data-month="<?= $startDate ? (int) date('n', $tsStart) : '' ?>"
         data-ym="<?= $startDate ? date('Y-m', $tsStart) : '' ?>"
         data-mall="<?= esc($mallKey, 'attr') ?>" data-start="<?= $tsStart ?>" data-prog="<?= $doneN ?>" data-order="<?= $i ?>">
    <div class="ev-card-main">
        <div class="ev-date" aria-hidden="true">
            <?php if ($startDate): ?>
            <b><?= (int) date('j', $tsStart) ?></b><span><?= $bulanPdk[(int) date('n', $tsStart)] ?></span>
            <?php else: ?><b>—</b><?php endif; ?>
        </div>
        <div class="ev-card-body">
            <div class="ev-card-name">
                <a class="ev-card-t" href="<?= $href ?>"><?= esc($e['name']) ?></a>
                <span class="ev-chip ev-chip-<?= $stTone ?>"><?= $stLabel ?></span>
                <?php if ($isPending): ?><span class="ev-chip ev-chip-info" title="Menunggu persetujuan"><i class="bi bi-hourglass-split"></i>Pending</span><?php endif; ?>
                <?php if ($isRejected): ?><span class="ev-chip ev-chip-bad" title="<?= esc($e['rejection_reason'] ?? '', 'attr') ?>"><i class="bi bi-x-circle"></i>Ditolak</span><?php endif; ?>
                <?php if ($jenisRutin): ?><span class="ev-chip ev-chip-mute"><i class="bi bi-arrow-repeat"></i>Rutin</span><?php endif; ?>
            </div>
            <?php if (! empty($e['tema'])): ?><div class="ev-card-tema"><?= esc($e['tema']) ?></div><?php endif; ?>
            <div class="ev-card-meta">
                <span title="<?= esc($mallFull[$mallKey] ?? $mallKey, 'attr') ?>"><i class="bi bi-building"></i><?= esc($mallShort[$mallKey] ?? $mallKey) ?></span>
                <?php if ($startDate): ?>
                <span><i class="bi bi-calendar3"></i><?= $tglPdk($startDate) ?><?= $endDate !== $startDate ? ' – ' . $tglPdk($endDate) : '' ?> · <?= $days ?> hari</span>
                <?php endif; ?>
                <?php if ($locs): ?><span class="ev-loc"><i class="bi bi-geo-alt"></i><?= esc(implode(', ', $locs)) ?></span><?php endif; ?>
            </div>
        </div>
        <div class="ev-card-prog" title="<?= $doneN ?> dari <?= $reqCount ?> modul sudah ditandai selesai">
            <div class="ev-prog-l">
                <span>Kelengkapan data</span>
                <b class="ev-pm-<?= $barCls ?>"><?= $doneN ?>/<?= $reqCount ?> modul</b>
            </div>
            <div class="ev-segs" role="img" aria-label="<?= $doneN ?> dari <?= $reqCount ?> modul selesai">
                <?php foreach ($requiredModules as $mk => $ml): ?>
                <i class="<?= in_array($mk, $done, true) ? 'on' : '' ?>" title="<?= esc($ml, 'attr') ?>: <?= in_array($mk, $done, true) ? 'selesai' : 'belum' ?>"></i>
                <?php endforeach; ?>
            </div>
            <?php if ($ctx): ?><div class="ev-prog-s"><?= $ctx ?></div><?php endif; ?>
        </div>
        <div class="ev-card-act" data-ev-notoggle>
            <label class="ev-cmp" title="Pilih untuk dibandingkan (maks. 3)">
                <input type="checkbox" class="evt-check" value="<?= (int) $e['id'] ?>" aria-label="Bandingkan <?= esc($e['name'], 'attr') ?>">
                <span><i class="bi bi-arrow-left-right"></i></span>
            </label>
            <a href="<?= $href ?>" class="ev-btn <?= $isPending ? '' : 'ev-btn-primary' ?> ev-btn-sm" title="<?= $isPending ? 'Buka & review' : 'Buka ringkasan event' ?>">
                <i class="bi bi-<?= $isPending ? 'eye' : 'speedometer2' ?>"></i><span class="d-none d-lg-inline"><?= $isPending ? 'Review' : 'Buka' ?></span>
            </a>
            <div class="dropdown">
                <button class="ev-iconbtn" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Aksi lain untuk <?= esc($e['name'], 'attr') ?>"><i class="bi bi-three-dots"></i></button>
                <div class="dropdown-menu dropdown-menu-end ev-menu">
                    <a class="dropdown-item" href="<?= $href ?>"><i class="bi bi-speedometer2"></i>Ringkasan event</a>
                    <a class="dropdown-item" href="<?= base_url('events/' . $e['id'] . '/gallery') ?>"><i class="bi bi-images"></i>Galeri foto</a>
                    <a class="dropdown-item" href="<?= base_url('events/' . $e['id'] . '/budget') ?>"><i class="bi bi-cash-coin"></i>Anggaran</a>
                    <?php if ($canEditEv): ?>
                    <a class="dropdown-item" href="<?= base_url('events/' . $e['id'] . '/edit') ?>"><i class="bi bi-pencil"></i>Ubah data event</a>
                    <?php endif; ?>
                    <?php if ($isAdmin): ?>
                    <div class="dropdown-divider"></div>
                    <form method="POST" action="<?= base_url('events/' . $e['id'] . '/delete') ?>" onsubmit="return confirm('Hapus event ini beserta semua datanya?')">
                        <?= csrf_field() ?>
                        <button type="submit" class="dropdown-item text-danger"><i class="bi bi-trash"></i>Hapus event</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</article>
<?php endforeach; ?>
</div>

<div class="ev-empty ev-empty-filter" id="evNoMatch" hidden>
    <i class="bi bi-search"></i>
    <h5>Tidak ada event yang cocok</h5>
    <p>Ubah kata kunci atau filter, atau hapus semua filter.</p>
    <button type="button" class="ev-btn" id="evReset2"><i class="bi bi-x-lg"></i>Hapus filter</button>
</div>

<?php endif; ?>

<!-- Bilah bandingkan -->
<div class="ev-cmpbar" id="compareBar" hidden>
    <span><i class="bi bi-arrow-left-right me-2"></i><strong id="compareCount">0</strong> event dipilih <span class="ev-cmpbar-h">(maks. 3)</span></span>
    <div class="ev-cmpbar-act">
        <button type="button" class="ev-btn ev-btn-sm" id="cmpClear">Batal</button>
        <a href="#" class="ev-btn ev-btn-primary ev-btn-sm" id="goCompare">Bandingkan sekarang</a>
    </div>
</div>

</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>window.EV_COMPARE_URL = '<?= base_url('events/compare') ?>';</script>
<script src="<?= base_url('js/events-core.js') ?>?v=<?= @filemtime(FCPATH . 'js/events-core.js') ?: 1 ?>"></script>
<?= $this->endSection() ?>
