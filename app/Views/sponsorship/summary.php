<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/sponsorship-summary.css') ?>?v=<?= @filemtime(FCPATH . 'css/sponsorship-summary.css') ?: 1 ?>">
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php
// ══════════════════════════════════════════════════════════════════════════
//  PERSIAPAN DATA — semua angka berasal dari controller; di sini hanya disusun.
// ══════════════════════════════════════════════════════════════════════════
$smFmt   = fn($n): string => 'Rp ' . number_format((int)$n, 0, ',', '.');
$smShort = function ($n) use ($smFmt): string {
    $n = (int)$n;
    if ($n >= 1_000_000_000) return 'Rp ' . number_format($n / 1_000_000_000, 1, ',', '.') . ' M';
    if ($n >= 1_000_000)     return 'Rp ' . number_format($n / 1_000_000, 1, ',', '.') . ' jt';
    return $smFmt($n);
};
$smPct = fn($a, $b): float => $b > 0 ? min(100, round($a / $b * 100, 1)) : 0;
// "Rp 197,0 jt" → awalan Rp dikecilkan untuk angka KPI
$kpiMoney = fn($n): string => '<span class="ss-rp">Rp</span> ' . esc(substr($smShort($n), 3));

$idBulan = ['January'=>'Januari','February'=>'Februari','March'=>'Maret','April'=>'April',
    'May'=>'Mei','June'=>'Juni','July'=>'Juli','August'=>'Agustus',
    'September'=>'September','October'=>'Oktober','November'=>'November','December'=>'Desember'];
$labelBulan = fn(string $m): string => strtr(\DateTime::createFromFormat('Y-m', $m)->format('F Y'), $idBulan);
$shortBulan = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'];
$shortLabel = function (string $ym) use ($shortBulan): string { [$y, $m] = explode('-', $ym); return ($shortBulan[(int)$m - 1] ?? $m) . ' ' . $y; };
$fmtTgl = function (?string $d) use ($shortBulan): string {
    if (! $d || $d === '0000-00-00') return '';
    $t = strtotime($d);
    return date('j', $t) . ' ' . $shortBulan[(int)date('n', $t) - 1] . ' ' . date('Y', $t);
};

$bulanDt        = \DateTime::createFromFormat('Y-m', $bulan);
$prevBulan      = (clone $bulanDt)->modify('-1 month')->format('Y-m');
$nextBulan      = (clone $bulanDt)->modify('+1 month')->format('Y-m');
$bulanLabel     = $labelBulan($bulan);
$prevBulanLabel = $labelBulan($prevBulan);
$bMonthStart    = $bulan . '-01';
$bMonthEnd      = date('Y-m-t', strtotime($bMonthStart));
$today          = date('Y-m-d');

// Badge delta vs bulan lalu — hanya bila pembanding ada (null = bulan lalu tak punya catatan realisasi)
$deltaBadge = function (int $cur, ?int $prev) use ($prevBulanLabel, $smFmt): string {
    if ($prev === null) return '<span class="ss-delta is-na" title="Tidak ada data realisasi ' . esc($prevBulanLabel) . ' sebagai pembanding">— <small>vs bln lalu</small></span>';
    if ($prev <= 0) {
        if ($cur <= 0) return '<span class="ss-delta is-na" title="Bulan lalu 0, bulan ini 0">— <small>vs bln lalu</small></span>';
        return '<span class="ss-delta is-up" title="Bulan lalu Rp 0 → ' . $smFmt($cur) . '"><i class="bi bi-caret-up-fill"></i>baru <small>vs bln lalu</small></span>';
    }
    $d = (int)round(($cur - $prev) / $prev * 100);
    $title = esc($prevBulanLabel) . ': ' . $smFmt($prev) . ' → ' . $smFmt($cur);
    if ($d === 0) return '<span class="ss-delta is-flat" title="' . $title . '">±0% <small>vs bln lalu</small></span>';
    $up = $d > 0;
    return '<span class="ss-delta ' . ($up ? 'is-up' : 'is-down') . '" title="' . $title . '"><i class="bi bi-caret-' . ($up ? 'up' : 'down') . '-fill"></i>' . abs($d) . '% <small>vs bln lalu</small></span>';
};

$statusOrder = ['prospek', 'negosiasi', 'terkonfirmasi', 'lunas', 'batal'];
$statusLabel = ['prospek' => 'Prospek', 'negosiasi' => 'Negosiasi', 'terkonfirmasi' => 'Terkonfirmasi', 'lunas' => 'Lunas', 'batal' => 'Batal'];
$evalLabel   = ['berhasil' => 'Berhasil', 'sebagian' => 'Sebagian', 'gagal' => 'Perlu Perbaikan'];

// ── Baris program ────────────────────────────────────────────────────────
$pRows = [];
$maxMonth = 0; $maxAll = 0;
foreach ($programs as $prog) {
    $maxMonth = max($maxMonth, (int)($monthlyReal[(int)$prog['id']] ?? 0));
    $maxAll   = max($maxAll, (int)($allTimeRealMap[(int)$prog['id']] ?? 0));
}
$aggStatus = array_fill_keys($statusOrder, ['count' => 0, 'nilai' => 0]);
$nowTs = time();
foreach ($programs as $ord => $prog) {
    $pid       = (int)$prog['id'];
    $target    = (int)($prog['target_nilai'] ?? 0);
    $committed = (int)(($committedMap[$pid] ?? [])['total_nilai'] ?? 0);
    $thisMonth = (int)($monthlyReal[$pid] ?? 0);
    $allTime   = (int)($allTimeRealMap[$pid] ?? 0);
    $spList    = $sponsors[$pid] ?? [];
    $byStatus  = [];
    $unpaidN = 0; $unpaidNilai = 0; $unpaidOldest = 0;
    foreach ($spList as $sp) {
        $s = $sp['status_deal'];
        if (! isset($byStatus[$s])) $byStatus[$s] = ['count' => 0, 'nilai' => 0];
        $byStatus[$s]['count']++;
        $byStatus[$s]['nilai'] += (int)$sp['nilai'];
        if (isset($aggStatus[$s])) { $aggStatus[$s]['count']++; $aggStatus[$s]['nilai'] += (int)$sp['nilai']; }
        if ($s === 'terkonfirmasi') {
            $unpaidN++; $unpaidNilai += (int)$sp['nilai'];
            $ts = strtotime((string)($sp['updated_at'] ?: $sp['created_at'] ?: '')) ?: $nowTs;
            $unpaidOldest = max($unpaidOldest, (int)floor(($nowTs - $ts) / 86400));
        }
    }
    $an      = $analisaMap[$pid] ?? ['highlight' => '', 'kendala' => '', 'tindak_lanjut' => ''];
    $filled  = trim(($an['highlight'] ?? '') . ($an['kendala'] ?? '') . ($an['tindak_lanjut'] ?? '')) !== '';
    $prevTL  = trim((string)(($prevAnalisaMap[$pid] ?? [])['tindak_lanjut'] ?? ''));
    $mulai   = (string)($prog['tanggal_mulai'] ?? '');
    $selesai = (string)($prog['tanggal_selesai'] ?? '');
    $inPeriod = $mulai !== '' && $mulai <= $bMonthEnd && ($selesai === '' || $selesai >= $bMonthStart);
    $relevant = $inPeriod || $thisMonth > 0 || $filled;
    $pRows[] = [
        'pid' => $pid, 'prog' => $prog, 'ord' => $ord, 'target' => $target, 'committed' => $committed,
        'month' => $thisMonth, 'all' => $allTime, 'pct' => $smPct($allTime, $target),
        'sp' => $spList, 'byStatus' => $byStatus, 'nSp' => count($spList),
        'unpaidN' => $unpaidN, 'unpaidNilai' => $unpaidNilai, 'unpaidOldest' => $unpaidOldest,
        'an' => $an, 'filled' => $filled, 'prevTL' => $prevTL, 'mulai' => $mulai, 'selesai' => $selesai,
        'inPeriod' => $inPeriod, 'relevant' => $relevant, 'active' => ($prog['status'] ?? 'active') === 'active',
    ];
}
// Urutan awal: yang relevan dengan bulan terpilih dulu (urutan periode dari controller dipertahankan)
usort($pRows, fn($a, $b) => ($b['relevant'] <=> $a['relevant']) ?: ($a['ord'] <=> $b['ord']));
foreach ($pRows as $i => &$rr) { $rr['ord'] = $i; } unset($rr);

$needN    = $canEdit ? count(array_filter($pRows, fn($r) => $r['relevant'] && ! $r['filled'])) : 0;
$relN     = count(array_filter($pRows, fn($r) => $r['relevant']));
$jsProgs  = array_map(fn($r) => [
    'id' => $r['pid'], 'name' => $r['prog']['nama_program'], 'active' => $r['active'], 'target' => $r['target'],
    'committed' => $r['committed'], 'month' => $r['month'], 'all' => $r['all'], 'mulai' => $r['mulai'], 'selesai' => $r['selesai'],
    'inPeriod' => $r['inPeriod'], 'relevant' => $r['relevant'], 'filled' => $r['filled'],
    'unpaidN' => $r['unpaidN'], 'unpaidNilai' => $r['unpaidNilai'], 'unpaidOldest' => $r['unpaidOldest'],
], $pRows);

// Kumulatif harian untuk grafik
$cum = []; $run = 0;
foreach ($dailyNilai as $v) { $run += (int)$v; $cum[] = $run; }
$hasDaily = array_sum($dailyNilai) > 0;

// Tren bulanan (tahun berjalan, sesuai controller)
$trendLabels = []; $trendVals = []; $trendKeys = [];
foreach ($allMonthlyTotals as $row) {
    $trendKeys[]   = $row['bulan'];
    $trendLabels[] = $shortLabel($row['bulan']);
    $trendVals[]   = (int)$row['total_nilai'];
}

$bulanKosong = ($kpiTerkumpul === 0);
$latestOther = null;
foreach ($monthList as $m) { if ($m !== $bulan && in_array($m, $trendKeys, true)) { $latestOther = $m; break; } }

$mallLbl   = ['ewalk' => 'eWalk', 'pentacity' => 'Pentacity', 'both' => 'eWalk & Pentacity'];
$evDealTot = array_sum(array_column($eventAggs, 'deal'));
$evRealTot = array_sum(array_column($eventAggs, 'realisasi'));
$evSponTot = array_sum(array_column($eventAggs, 'jumlah_sponsor'));

$pctRealVsConf = $kpiCommitted > 0 ? round($grandTotal / $kpiCommitted * 100, 1) : null;
$sumAllProg    = array_sum($allTimeRealMap);
$pctTarget     = $targetTotal > 0 ? round($sumAllProg / $targetTotal * 100, 1) : null;
$barTone = fn($p): string => $p >= 100 ? 'ok' : ($p >= 60 ? 'good' : ($p >= 30 ? 'mid' : 'low'));

$payload = [
    'bulan' => $bulan, 'bulanLabel' => $bulanLabel, 'prevLabel' => $prevBulanLabel, 'today' => $today,
    'canEdit' => (bool)$canEdit, 'saveUrl' => base_url('sponsorship/analisa/save'),
    'csrf' => ['name' => csrf_token(), 'hash' => csrf_hash()],
    'kpi' => ['terkumpul' => (int)$kpiTerkumpul, 'terkumpulPrev' => $kpiTerkumpulPrev, 'committed' => (int)$kpiCommitted,
              'sponsor' => (int)$kpiSponsor, 'grand' => (int)$grandTotal, 'pctRealVsConf' => $pctRealVsConf,
              'pctTarget' => $pctTarget, 'targetTotal' => (int)$targetTotal, 'progAll' => (int)$sumAllProg],
    'programs' => $jsProgs,
    'nProg' => count($pRows),
    'trend' => ['labels' => $trendLabels, 'keys' => $trendKeys, 'vals' => $trendVals],
    'daily' => ['labels' => array_values($chartDates), 'vals' => array_map('intval', array_values($dailyNilai)), 'cum' => $cum],
];

$kpiCard = function (string $tone, string $icon, string $label, string $value, string $sub, string $deltaHtml = '', string $title = '', string $extra = '') {
    ?>
    <div class="ss-kpi" data-tone="<?= $tone ?>"<?= $title !== '' ? ' title="' . esc($title) . '"' : '' ?>>
        <div class="ss-kpi-top"><span class="ss-chip-ico"><i class="bi bi-<?= $icon ?>"></i></span><span class="ss-kpi-label"><?= $label ?></span></div>
        <div class="ss-kpi-val"><?= $value ?></div>
        <?= $extra ?>
        <div class="ss-kpi-foot"><?= $deltaHtml ?><span class="ss-kpi-sub"><?= $sub ?></span></div>
    </div>
    <?php
};
?>

<div class="page-spon-sum" id="sponSum">

    <!-- Header -->
    <div class="ss-head">
        <a href="<?= base_url('sponsorship') ?>" class="ss-iconbtn" title="Kembali ke Deal"><i class="bi bi-arrow-left"></i></a>
        <div class="min-w-0">
            <h1 class="ss-title">Summary Sponsorship</h1>
            <div class="ss-subtitle">Trend bulanan, realisasi per program, dan breakdown sponsor</div>
        </div>
    </div>

    <?php if ($bulanFallback): ?>
    <div class="ss-banner" role="status" id="ssBannerFallback">
        <i class="bi bi-info-circle-fill"></i>
        <span>Bulan berjalan belum ada realisasi — menampilkan <strong><?= esc($bulanLabel) ?></strong> (data terakhir).</span>
        <a href="?bulan=<?= esc($bulanSekarang) ?>" class="ss-banner-link">Lihat bulan berjalan <i class="bi bi-arrow-right"></i></a>
    </div>
    <?php elseif ($bulanKosong): ?>
    <div class="ss-banner is-empty" role="status" id="ssBannerEmpty">
        <i class="bi bi-inbox"></i>
        <span>Belum ada realisasi di <strong><?= esc($bulanLabel) ?></strong>.</span>
        <?php if ($latestOther): ?>
        <a href="?bulan=<?= esc($latestOther) ?>" class="ss-banner-link">Lihat <?= esc($labelBulan($latestOther)) ?> <i class="bi bi-arrow-right"></i></a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- A. Bilah filter menempel -->
    <div class="ss-bar" id="ssBar">
        <div class="ss-bar-nav">
            <a href="?bulan=<?= $prevBulan ?>" class="ss-iconbtn" title="Bulan sebelumnya" aria-label="Bulan sebelumnya"><i class="bi bi-chevron-left"></i></a>
            <select class="ss-select" aria-label="Pilih bulan" onchange="location.href='?bulan='+this.value">
                <?php foreach ($monthList as $m): ?>
                <option value="<?= esc($m) ?>" <?= $m === $bulan ? 'selected' : '' ?>><?= esc($labelBulan($m)) ?></option>
                <?php endforeach; ?>
            </select>
            <a href="?bulan=<?= $nextBulan ?>" class="ss-iconbtn" title="Bulan berikutnya" aria-label="Bulan berikutnya"><i class="bi bi-chevron-right"></i></a>
            <a href="?bulan=<?= esc($bulanSekarang) ?>" class="ss-btn ss-btn-ghost">Bulan Ini</a>
        </div>
        <div class="ss-bar-title d-none d-lg-block"><?= esc($bulanLabel) ?></div>
        <div class="ss-bar-actions">
            <span id="analisaWarn" class="ss-warnbadge" <?= $needN > 0 ? '' : 'style="display:none"' ?> role="button" tabindex="0" title="Tampilkan program yang berjalan di bulan ini tetapi belum ada analisa">
                <i class="bi bi-exclamation-triangle-fill"></i><span><span id="analisaWarnCount"><?= $needN ?></span> program belum ada analisa</span>
            </span>
            <a href="<?= base_url('sponsorship/summary/print?bulan=' . $bulan) ?>" target="_blank" class="ss-btn ss-btn-primary">
                <i class="bi bi-printer"></i><span>Laporan Bulanan</span>
            </a>
            <a href="<?= base_url('sponsorship') ?>" class="ss-btn">
                <i class="bi bi-arrow-left"></i><span>Kembali ke Deal</span>
            </a>
        </div>
    </div>

    <!-- B. Strip KPI -->
    <section class="ss-kpis" aria-label="Indikator utama <?= esc($bulanLabel) ?>">
        <?php
        $kpiCard('gold', 'cash-stack', 'Terkumpul Bulan Ini', $kpiMoney($kpiTerkumpul),
            esc($bulanLabel) . ($kpiTerkumpulPrev !== null ? ' · bln lalu ' . esc($smShort($kpiTerkumpulPrev)) : ''),
            $deltaBadge((int)$kpiTerkumpul, $kpiTerkumpulPrev), $smFmt($kpiTerkumpul));
        $kpiCard('blue', 'check2-circle', 'Nilai Dikonfirmasi', $kpiMoney($kpiCommitted),
            (int)$kpiSponsor . ' sponsor deal', '', $smFmt($kpiCommitted) . ' — status Terkonfirmasi + Lunas (program + event)');
        $kpiCard('green', 'piggy-bank', 'Total Terkumpul (All)', $kpiMoney($grandTotal),
            'standalone + event', '', $smFmt($grandTotal));
        $rvBar = $pctRealVsConf !== null
            ? '<div class="ss-bar-mini" aria-hidden="true"><i data-c="' . $barTone($pctRealVsConf) . '" style="width:' . min(100, $pctRealVsConf) . '%"></i></div>' : '';
        $kpiCard('cyan', 'bullseye', 'Realisasi vs Dikonfirmasi',
            $pctRealVsConf !== null ? $pctRealVsConf . '<span class="ss-rp">%</span>' : '<span class="ss-dash">—</span>',
            $pctRealVsConf !== null ? esc($smShort($grandTotal)) . ' dari ' . esc($smShort($kpiCommitted)) : 'belum ada nilai dikonfirmasi',
            '', 'Total terkumpul (All) ÷ nilai dikonfirmasi', $rvBar);
        $tgBar = $pctTarget !== null
            ? '<div class="ss-bar-mini" aria-hidden="true"><i data-c="' . $barTone($pctTarget) . '" style="width:' . min(100, $pctTarget) . '%"></i></div>' : '';
        $kpiCard('slate', 'flag', 'Capaian Target Program',
            $pctTarget !== null ? $pctTarget . '<span class="ss-rp">%</span>' : '<span class="ss-dash">—</span>',
            $pctTarget !== null ? esc($smShort($sumAllProg)) . ' dari target ' . esc($smShort($targetTotal)) : 'belum ada target nilai',
            '', 'Realisasi all-time seluruh program (tanpa event) ÷ jumlah target nilai semua program', $tgBar);
        ?>
    </section>

    <!-- C. Perlu Perhatian + komposisi status deal -->
    <div class="ss-grid-attn">
        <section class="ss-card" id="ssAttn" aria-labelledby="ssAttnT">
            <div class="ss-card-h">
                <h2 id="ssAttnT"><i class="bi bi-bell-fill"></i>Perlu Perhatian</h2>
                <span class="ss-count" id="ssAttnCount">0</span>
                <div class="ss-card-tools">
                    <button type="button" class="ss-btn ss-btn-ghost ss-btn-sm" id="ssCopy" title="Salin ringkasan bulan ini ke clipboard"><i class="bi bi-clipboard"></i><span>Salin ringkasan</span></button>
                </div>
            </div>
            <ol class="ss-attn" id="ssAttnList"><li class="ss-attn-empty">Memeriksa data…</li></ol>
            <button type="button" class="ss-more" id="ssAttnMore" hidden></button>
        </section>

        <section class="ss-card" aria-labelledby="ssStatT">
            <div class="ss-card-h"><h2 id="ssStatT"><i class="bi bi-diagram-3"></i>Status Deal Seluruh Program</h2></div>
            <?php $aggN = array_sum(array_column($aggStatus, 'count')); if ($aggN > 0): ?>
            <div class="ss-stack" role="img" aria-label="Komposisi status deal sponsor">
                <?php foreach ($statusOrder as $s): if ($aggStatus[$s]['count'] < 1) continue; ?>
                <i data-deal="<?= $s ?>" style="flex:<?= $aggStatus[$s]['count'] ?>" title="<?= $statusLabel[$s] ?>: <?= $aggStatus[$s]['count'] ?>"></i>
                <?php endforeach; ?>
            </div>
            <ul class="ss-legend">
                <?php foreach ($statusOrder as $s): if ($aggStatus[$s]['count'] < 1) continue; ?>
                <li><i data-deal="<?= $s ?>"></i><span><?= $statusLabel[$s] ?></span><em><?= esc($smShort($aggStatus[$s]['nilai'])) ?></em><b><?= $aggStatus[$s]['count'] ?></b></li>
                <?php endforeach; ?>
            </ul>
            <div class="ss-foot-note"><?= $aggN ?> sponsor di <?= count(array_filter($pRows, fn($r) => $r['nSp'] > 0)) ?> program (belum termasuk sponsor event).</div>
            <?php else: ?>
            <div class="ss-empty-s">Belum ada sponsor pada program mana pun.</div>
            <?php endif; ?>
        </section>
    </div>

    <!-- D. Tabel Kinerja Program -->
    <section class="ss-card" id="ssPrograms" aria-labelledby="ssProgT">
        <div class="ss-card-h">
            <h2 id="ssProgT"><i class="bi bi-table"></i>Kinerja Program</h2>
            <span class="ss-count" id="ssProgCount"><?= count($pRows) ?></span>
            <span class="ss-hint d-none d-md-inline"><?= $relN ?> berjalan / punya realisasi di <?= esc($bulanLabel) ?> · klik baris untuk analisa &amp; detail sponsor</span>
        </div>
        <?php if (empty($pRows)): ?>
        <div class="ss-empty"><i class="bi bi-inbox"></i><p>Belum ada program.</p></div>
        <?php else: ?>
        <div class="ss-filters" id="ssFilters">
            <label class="ss-search"><i class="bi bi-search"></i><input type="search" id="ssSearch" placeholder="Cari program…" aria-label="Cari program" autocomplete="off"></label>
            <div class="ss-seg" role="group" aria-label="Status program" data-group="status">
                <button type="button" class="is-on" data-v="">Semua</button>
                <button type="button" data-v="active">Aktif</button>
                <button type="button" data-v="inactive">Nonaktif</button>
            </div>
            <div class="ss-seg" role="group" aria-label="Relevansi bulan" data-group="rel">
                <button type="button" class="is-on" data-v="">Semua bulan</button>
                <button type="button" data-v="1">Bulan ini</button>
            </div>
            <?php if ($canEdit): ?>
            <button type="button" class="ss-toggle" id="ssNeedAnalisa" aria-pressed="false"><i class="bi bi-chat-left-dots"></i>Perlu analisa <b><?= $needN ?></b></button>
            <?php endif; ?>
            <button type="button" class="ss-link" id="ssReset" hidden><i class="bi bi-x-circle"></i>Reset</button>
        </div>
        <div class="ss-scroll">
        <table class="ss-table" id="ssProgTable">
            <thead>
            <tr>
                <th class="ss-sticky" data-sort="name" aria-sort="none"><button type="button">Program</button></th>
                <th data-sort="status" data-num aria-sort="none"><button type="button">Status</button></th>
                <th class="t-r" data-sort="target" data-num aria-sort="none"><button type="button">Target Nilai</button></th>
                <th class="t-r" data-sort="conf" data-num aria-sort="none"><button type="button">Dikonfirmasi</button></th>
                <th class="t-r" data-sort="bulan" data-num aria-sort="none"><button type="button">Bulan Ini</button></th>
                <th class="t-r" data-sort="all" data-num aria-sort="none"><button type="button">All-time</button></th>
                <th data-sort="prog" data-num aria-sort="none"><button type="button">Progress</button></th>
                <th data-sort="spons" data-num aria-sort="none"><button type="button">Sponsor</button></th>
                <th class="t-c" data-sort="analisa" data-num aria-sort="none"><button type="button">Analisa</button></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($pRows as $r):
                $p = $r['prog']; $pid = $r['pid'];
                $periode = $r['mulai'] ? ($fmtTgl($r['mulai']) . ($r['selesai'] && $r['selesai'] !== $r['mulai'] ? ' – ' . $fmtTgl($r['selesai']) : '')) : '';
                $hvM = $maxMonth > 0 ? round($r['month'] / $maxMonth * .30, 3) : 0;
                $hvA = $maxAll > 0 ? round($r['all'] / $maxAll * .30, 3) : 0;
            ?>
            <tr class="ss-row" id="row-<?= $pid ?>" data-key="<?= $pid ?>" data-status="<?= $r['active'] ? 'active' : 'inactive' ?>"
                data-rel="<?= $r['relevant'] ? '1' : '0' ?>" data-need="<?= (! $r['filled'] && $r['relevant'] && $canEdit) ? '1' : '0' ?>" data-ord="<?= $r['ord'] ?>"
                data-name="<?= esc(mb_strtolower($p['nama_program'])) ?>" data-s-name="<?= esc(mb_strtolower($p['nama_program'])) ?>"
                data-s-status="<?= $r['active'] ? 0 : 1 ?>" data-s-target="<?= $r['target'] ?>" data-s-conf="<?= $r['committed'] ?>"
                data-s-bulan="<?= $r['month'] ?>" data-s-all="<?= $r['all'] ?>" data-s-prog="<?= $r['target'] > 0 ? $r['pct'] : -1 ?>"
                data-s-spons="<?= $r['nSp'] ?>" data-s-analisa="<?= $r['filled'] ? 1 : 0 ?>">
                <td class="ss-sticky">
                    <button type="button" class="ss-exp" aria-expanded="false" aria-controls="det-<?= $pid ?>" title="Buka analisa &amp; detail">
                        <i class="bi bi-chevron-right"></i>
                        <span class="ss-pname">
                            <span class="ss-pname-t"><?= esc($p['nama_program']) ?></span>
                            <span class="ss-pname-s">
                                <?php if ($periode): ?><span class="ss-evname"><i class="bi bi-calendar3"></i><?= esc($periode) ?></span><?php endif; ?>
                                <?php if ($p['locked']): ?><span class="ss-tag" title="Terkunci"><i class="bi bi-lock-fill"></i>Terkunci</span><?php endif; ?>
                                <?php if ($p['locked'] && ($p['eval_status'] ?? '')): ?>
                                <span class="ss-tag is-eval" data-ev="<?= esc($p['eval_status']) ?>"><?= esc($evalLabel[$p['eval_status']] ?? $p['eval_status']) ?></span>
                                <?php endif; ?>
                            </span>
                        </span>
                    </button>
                </td>
                <td><span class="ss-tag <?= $r['active'] ? 'is-on' : 'is-off' ?>"><?= $r['active'] ? 'Aktif' : 'Nonaktif' ?></span></td>
                <td class="t-r num" title="<?= $r['target'] > 0 ? esc($smFmt($r['target'])) : '' ?>"><?= $r['target'] > 0 ? esc($smShort($r['target'])) : '<span class="ss-dash">—</span>' ?></td>
                <td class="t-r num ss-c-blue" title="<?= esc($smFmt($r['committed'])) ?>"><?= esc($smShort($r['committed'])) ?></td>
                <td class="t-r num"><span class="ss-heat" style="--hv:<?= $hvM ?>" title="<?= esc($smFmt($r['month'])) ?>"><?= $r['month'] > 0 ? esc($smShort($r['month'])) : '<span class="ss-dash">—</span>' ?></span></td>
                <td class="t-r num"><span class="ss-heat" style="--hv:<?= $hvA ?>" title="<?= esc($smFmt($r['all'])) ?>"><?= esc($smShort($r['all'])) ?></span></td>
                <td>
                    <?php if ($r['target'] > 0): ?>
                    <div class="ss-prog is-solo" title="<?= esc($smFmt($r['all'])) ?> dari target <?= esc($smFmt($r['target'])) ?>">
                        <span class="ss-bar-mini"><i data-c="<?= $barTone($r['pct']) ?>" style="width:<?= $r['pct'] ?>%"></i></span>
                        <span class="ss-prog-p"><?= $r['pct'] ?>%</span>
                    </div>
                    <?php else: ?><span class="ss-dash">—</span><?php endif; ?>
                </td>
                <td>
                    <?php if ($r['nSp'] > 0): ?>
                    <div class="ss-sp"><b><?= $r['nSp'] ?></b>
                        <?php foreach ($statusOrder as $s): if (! isset($r['byStatus'][$s])) continue; ?>
                        <span class="ss-deal" data-deal="<?= $s ?>" title="<?= $statusLabel[$s] ?>: <?= $r['byStatus'][$s]['count'] ?> sponsor · <?= esc($smFmt($r['byStatus'][$s]['nilai'])) ?>"><?= $statusLabel[$s] ?> <?= $r['byStatus'][$s]['count'] ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?><span class="ss-dash">—</span><?php endif; ?>
                </td>
                <td class="t-c">
                    <span class="ss-an <?= $r['filled'] ? 'is-ok' : 'is-no' ?>" data-an-cell title="<?= $r['filled'] ? 'Analisa sudah diisi' : 'Analisa belum diisi' ?>">
                        <i class="bi bi-<?= $r['filled'] ? 'check-circle-fill' : 'circle' ?>"></i><span><?= $r['filled'] ? 'terisi' : 'belum' ?></span>
                    </span>
                </td>
            </tr>
            <tr class="ss-det" id="det-<?= $pid ?>" data-for="<?= $pid ?>" hidden>
                <td colspan="9">
                    <div class="ss-det-in">
                        <div class="ss-det-info">
                            <?php if ($r['prevTL']): ?>
                            <div class="ss-prevtl">
                                <div class="ss-prevtl-t"><i class="bi bi-arrow-return-right"></i>Tindak Lanjut Bulan Lalu</div>
                                <div class="ss-prevtl-b"><?= esc($r['prevTL']) ?></div>
                            </div>
                            <?php endif; ?>
                            <?php if (! empty($r['byStatus'])): ?>
                            <table class="ss-mini">
                                <thead><tr><th>Status Deal</th><th class="t-c">Jumlah</th><th class="t-r">Total Nilai</th></tr></thead>
                                <tbody>
                                <?php foreach ($statusOrder as $s): if (! isset($r['byStatus'][$s])) continue; $sd = $r['byStatus'][$s]; ?>
                                <tr><td><span class="ss-deal" data-deal="<?= $s ?>"><?= $statusLabel[$s] ?></span></td><td class="t-c"><?= $sd['count'] ?></td><td class="t-r num"><?= esc($smShort($sd['nilai'])) ?></td></tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                            <ul class="ss-splist">
                                <?php foreach (array_slice($r['sp'], 0, 8) as $sp): ?>
                                <li><span class="ss-deal" data-deal="<?= esc($sp['status_deal']) ?>"><?= esc($statusLabel[$sp['status_deal']] ?? $sp['status_deal']) ?></span>
                                    <span class="ss-spn"><?= esc($sp['nama_sponsor']) ?></span><span class="num"><?= esc($smShort($sp['nilai'])) ?></span></li>
                                <?php endforeach; ?>
                                <?php if (count($r['sp']) > 8): ?><li class="ss-more-sp">+<?= count($r['sp']) - 8 ?> sponsor lainnya</li><?php endif; ?>
                            </ul>
                            <?php else: ?>
                            <div class="ss-mek">Belum ada sponsor pada program ini.</div>
                            <?php endif; ?>
                            <a href="<?= base_url('sponsorship#program-' . $pid) ?>" class="ss-btn ss-btn-ghost ss-btn-sm">Buka program di Deal <i class="bi bi-arrow-right"></i></a>
                        </div>

                        <div class="ss-an-box analisa-box" data-key="<?= $pid ?>">
                            <div class="ss-an-h">
                                <span><i class="bi bi-lightbulb-fill"></i>Analisa <?= esc($bulanLabel) ?></span>
                                <span class="analisa-flag ss-flag <?= $r['filled'] ? 'is-ok' : 'is-no' ?>" data-filled="<?= $r['filled'] ? '1' : '0' ?>"><?= $r['filled'] ? 'terisi' : 'belum diisi' ?></span>
                            </div>
                            <?php if ($canEdit): ?>
                            <form class="analisa-form" data-program="<?= $pid ?>" data-bulan="<?= esc($bulan, 'attr') ?>">
                                <?= csrf_field() ?>
                                <div class="ss-an-fields">
                                    <label><span>Highlight / Capaian</span>
                                        <textarea name="highlight" rows="3" placeholder="Capaian utama bulan ini..."><?= esc($r['an']['highlight'] ?? '') ?></textarea></label>
                                    <label><span>Kendala</span>
                                        <textarea name="kendala" rows="3" placeholder="Hambatan yang ditemui..."><?= esc($r['an']['kendala'] ?? '') ?></textarea></label>
                                    <label><span>Tindak Lanjut</span>
                                        <textarea name="tindak_lanjut" rows="3" placeholder="Rencana perbaikan bulan depan..."><?= esc($r['an']['tindak_lanjut'] ?? '') ?></textarea></label>
                                </div>
                                <div class="ss-an-actions">
                                    <span class="analisa-status small"></span>
                                    <button type="submit" class="ss-btn ss-btn-primary ss-btn-sm"><i class="bi bi-save"></i>Simpan Analisa</button>
                                </div>
                            </form>
                            <?php else: ?>
                            <div class="ss-an-read">
                                <?php if ($r['an']['highlight'] ?? ''): ?><p><span>Highlight</span><?= nl2br(esc($r['an']['highlight'])) ?></p><?php endif; ?>
                                <?php if ($r['an']['kendala'] ?? ''): ?><p><span>Kendala</span><?= nl2br(esc($r['an']['kendala'])) ?></p><?php endif; ?>
                                <?php if ($r['an']['tindak_lanjut'] ?? ''): ?><p><span>Tindak Lanjut</span><?= nl2br(esc($r['an']['tindak_lanjut'])) ?></p><?php endif; ?>
                                <?php if (! $r['filled']): ?><p class="ss-dash">Belum ada analisa.</p><?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td class="ss-sticky">Total <small class="ss-muted">(termasuk event)</small></td>
                    <td></td><td></td>
                    <td class="t-r num ss-c-blue" title="<?= esc($smFmt($kpiCommitted)) ?>"><?= esc($smShort($kpiCommitted)) ?></td>
                    <td class="t-r num" title="<?= esc($smFmt($kpiTerkumpul)) ?>"><?= esc($smShort($kpiTerkumpul)) ?></td>
                    <td class="t-r num" title="<?= esc($smFmt($grandTotal)) ?>"><?= esc($smShort($grandTotal)) ?></td>
                    <td></td><td></td><td></td>
                </tr>
            </tfoot>
        </table>
        </div>
        <div class="ss-empty ss-empty-filter" id="ssProgEmpty" hidden><i class="bi bi-funnel"></i><p>Tidak ada program yang cocok dengan filter.</p></div>
        <div class="ss-foot-note ss-foot-pad">Baris Total mengikuti angka ringkasan di atas dan <strong>sudah memasukkan sponsor dari event</strong>, sehingga bisa lebih besar dari jumlah baris program.</div>
        <?php endif; ?>
    </section>

    <!-- E. Sponsor dari Event -->
    <?php if (! empty($eventAggs)): ?>
    <section class="ss-card" id="ssEvents" aria-labelledby="ssEvT">
        <div class="ss-card-h">
            <h2 id="ssEvT"><i class="bi bi-calendar-event"></i>Sponsor dari Event</h2>
            <span class="ss-count"><?= count($eventAggs) ?></span>
            <span class="ss-hint">nilai deal dari modul Sponsorship event</span>
        </div>
        <div class="ss-scroll">
        <table class="ss-table is-plain" id="ssEvTable">
            <thead><tr>
                <th>Event</th><th>Mall</th><th class="t-c">Sponsor</th><th class="t-r">Nilai Deal</th><th class="t-r">Realisasi Bulan Ini</th>
            </tr></thead>
            <tbody>
            <?php foreach ($eventAggs as $e): ?>
            <tr>
                <td><a href="<?= base_url('events/' . (int)$e['event_id'] . '/sponsors') ?>"><?= esc($e['event_name']) ?></a></td>
                <td class="ss-muted"><?= esc($mallLbl[$e['event_mall']] ?? ucfirst((string)$e['event_mall'])) ?></td>
                <td class="t-c num"><?= (int)$e['jumlah_sponsor'] ?></td>
                <td class="t-r num ss-c-blue"><span title="<?= esc($smFmt($e['deal'])) ?>"><?= esc($smShort((int)$e['deal'])) ?></span>
                    <?php if ((int)$e['total_cash'] && (int)$e['total_barang']): ?>
                    <div class="ss-sub">cash <?= number_format($e['total_cash']) ?> · barang <?= number_format($e['total_barang']) ?></div>
                    <?php endif; ?>
                </td>
                <td class="t-r num"><?= (int)$e['realisasi'] > 0 ? '<span class="ss-heat" style="--hv:.18" title="' . esc($smFmt($e['realisasi'])) . '">' . esc($smShort((int)$e['realisasi'])) . '</span>' : '<span class="ss-dash">—</span>' ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot><tr>
                <td>Total Event</td><td></td><td class="t-c num"><?= (int)$evSponTot ?></td>
                <td class="t-r num ss-c-blue"><?= esc($smShort($evDealTot)) ?></td>
                <td class="t-r num"><?= $evRealTot > 0 ? esc($smShort($evRealTot)) : '—' ?></td>
            </tr></tfoot>
        </table>
        </div>
        <div class="ss-foot-note ss-foot-pad"><i class="bi bi-info-circle"></i> Nilai event sudah termasuk di KPI, grafik tren, dan realisasi harian.</div>
    </section>
    <?php endif; ?>

    <!-- F. Grafik -->
    <div class="ss-grid-2">
        <section class="ss-card" aria-labelledby="ssC1T">
            <div class="ss-card-h">
                <h2 id="ssC1T"><i class="bi bi-calendar3"></i>Realisasi Harian — <?= esc($bulanLabel) ?></h2>
                <span class="ss-hint ms-auto"><?= esc($smShort($kpiTerkumpul)) ?> total</span>
            </div>
            <?php if ($hasDaily): ?>
            <div class="ss-chart is-short"><canvas id="dailyChart" role="img" aria-label="Grafik realisasi harian <?= esc($bulanLabel) ?>"></canvas></div>
            <?php else: ?>
            <div class="ss-empty is-compact"><i class="bi bi-bar-chart"></i><p>Belum ada realisasi harian di <?= esc($bulanLabel) ?>.</p></div>
            <?php endif; ?>
        </section>
        <section class="ss-card" aria-labelledby="ssC2T">
            <div class="ss-card-h"><h2 id="ssC2T"><i class="bi bi-graph-up"></i>Tren Bulanan <?= date('Y') ?></h2>
                <span class="ss-hint ms-auto">klik batang untuk membuka bulannya</span></div>
            <?php if (empty($allMonthlyTotals)): ?>
            <div class="ss-empty is-compact"><i class="bi bi-graph-up"></i><p>Belum ada data realisasi tahun ini.</p></div>
            <?php else: ?>
            <div class="ss-chart is-short"><canvas id="trendChart" role="img" aria-label="Grafik total realisasi per bulan tahun <?= date('Y') ?>"></canvas></div>
            <?php endif; ?>
        </section>
    </div>

    <!-- Tabel tren (dilipat) -->
    <section class="ss-card ss-acc" id="ssTrendAcc">
        <button type="button" class="ss-acc-h" aria-expanded="false" aria-controls="ssTrendBody">
            <span><i class="bi bi-table"></i>Tabel tren bulanan — <?= date('Y') ?></span>
            <i class="bi bi-chevron-down ss-acc-ico"></i>
        </button>
        <div class="ss-acc-b" id="ssTrendBody" hidden>
        <?php if (empty($allMonthlyTotals)): ?>
            <div class="ss-empty is-compact"><p>Belum ada data realisasi tahun ini.</p></div>
        <?php else: ?>
            <div class="ss-scroll">
            <table class="ss-table is-plain" id="ssTrendTable">
                <thead><tr><th>Bulan</th><th class="t-r">Total Realisasi</th></tr></thead>
                <tbody>
                <?php foreach ($allMonthlyTotals as $row): $isSel = $row['bulan'] === $bulan; ?>
                <tr class="<?= $isSel ? 'is-sel' : '' ?>">
                    <td><a href="?bulan=<?= esc($row['bulan']) ?>"><?= esc($labelBulan($row['bulan'])) ?><?= $isSel ? ' <i class="bi bi-arrow-left-short"></i>' : '' ?></a></td>
                    <td class="t-r num" title="<?= esc($smFmt($row['total_nilai'])) ?>"><?= esc($smShort((int)$row['total_nilai'])) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot><tr><td>Total <?= date('Y') ?></td><td class="t-r num"><?= esc($smShort(array_sum(array_column($allMonthlyTotals, 'total_nilai')))) ?></td></tr></tfoot>
            </table>
            </div>
        <?php endif; ?>
        </div>
    </section>

</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>window.SPONSUM = <?= json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_PARTIAL_OUTPUT_ON_ERROR) ?>;</script>
<script src="<?= base_url('js/sponsorship-summary.js') ?>?v=<?= @filemtime(FCPATH . 'js/sponsorship-summary.js') ?: 1 ?>"></script>
<?= $this->endSection() ?>
