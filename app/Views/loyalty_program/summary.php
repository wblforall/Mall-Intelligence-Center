<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/loyalty-summary.css') ?>?v=<?= @filemtime(FCPATH . 'css/loyalty-summary.css') ?: 1 ?>">
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php
// ══════════════════════════════════════════════════════════════════════════
//  PERSIAPAN DATA (semua angka berasal dari controller; di sini hanya disusun)
// ══════════════════════════════════════════════════════════════════════════
$bulanDt   = \DateTime::createFromFormat('Y-m', $bulan);
$prevBulan = (clone $bulanDt)->modify('-1 month')->format('Y-m');
$nextBulan = (clone $bulanDt)->modify('+1 month')->format('Y-m');

$idBulan = ['January'=>'Januari','February'=>'Februari','March'=>'Maret','April'=>'April',
    'May'=>'Mei','June'=>'Juni','July'=>'Juli','August'=>'Agustus',
    'September'=>'September','October'=>'Oktober','November'=>'November','December'=>'Desember'];
$bulanLabel     = strtr($bulanDt->format('F Y'), $idBulan);
$prevBulanLabel = strtr(\DateTime::createFromFormat('Y-m', $prevBulan)->format('F Y'), $idBulan);
$labelBulan = fn(string $m): string => strtr(\DateTime::createFromFormat('Y-m', $m)->format('F Y'), $idBulan);
$shortBulan = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'];
$rp = fn($n): string => 'Rp ' . number_format((float)$n, 0, ',', '.');
$n0 = fn($n): string => number_format((int)$n);

// Data tren (tahun bulan terpilih)
$trendLabels = []; $trendMember = []; $trendMemberAktif = []; $trendSebar = []; $trendTerpakai = []; $trendHadiah = []; $trendRedemption = [];
foreach ($allMonthlyTotals as $row) {
    $dt = \DateTime::createFromFormat('Y-m', $row['bulan']);
    $trendLabels[]      = $shortBulan[(int)$dt->format('n') - 1] . ' ' . $dt->format('Y');
    $trendMember[]      = (int)$row['total_jumlah'];
    $trendMemberAktif[] = (int)($row['total_member_aktif'] ?? 0);
    $trendSebar[]       = (int)$row['total_tersebar'];
    $trendTerpakai[]    = (int)$row['total_terpakai'];
    $trendHadiah[]      = (int)($row['total_hadiah'] ?? 0);
    $trendRedemption[]  = (int)$row['total_tersebar'] > 0 ? round((int)$row['total_terpakai'] / (int)$row['total_tersebar'] * 100, 1) : null;
}

// Badge delta vs bulan sebelumnya — hanya dari data pembanding yang ada
$hasPrev = ($kpiMemberPrev + $kpiMemberAktifPrev + $kpiTersebarPrev + $kpiTerpakaiPrev + $kpiHadiahPrev + $nilaiRealisasiPrev) > 0;
$delta = function (float $cur, float $prev, bool $money = false) use ($hasPrev, $prevBulanLabel, $n0, $rp): string {
    if (! $hasPrev) {
        return '<span class="ls-delta is-na" title="Tidak ada data ' . esc($prevBulanLabel) . ' sebagai pembanding">— <small>vs bln lalu</small></span>';
    }
    $fmt = $money ? $rp : $n0;
    if ($prev <= 0) {
        if ($cur <= 0) return '<span class="ls-delta is-na" title="Bulan lalu 0, bulan ini 0">— <small>vs bln lalu</small></span>';
        return '<span class="ls-delta is-up" title="Bulan lalu 0 → ' . $fmt($cur) . '"><i class="bi bi-caret-up-fill"></i>baru <small>vs bln lalu</small></span>';
    }
    $d = (int)round(($cur - $prev) / $prev * 100);
    $title = esc($prevBulanLabel) . ': ' . $fmt($prev) . ' → ' . $fmt($cur);
    if ($d === 0) return '<span class="ls-delta is-flat" title="' . $title . '">±0% <small>vs bln lalu</small></span>';
    $up = $d > 0;
    return '<span class="ls-delta ' . ($up ? 'is-up' : 'is-down') . '" title="' . $title . '"><i class="bi bi-caret-' . ($up ? 'up' : 'down') . '-fill"></i>' . abs($d) . '% <small>vs bln lalu</small></span>';
};
$pctTerpakai     = $kpiTersebar > 0 ? round($kpiTerpakai / $kpiTersebar * 100, 1) : null;
$pctTerpakaiPrev = $kpiTersebarPrev > 0 ? round($kpiTerpakaiPrev / $kpiTersebarPrev * 100, 1) : null;
$bulanKosong = ($kpiMember + $kpiMemberAktif + $kpiTersebar + $kpiTerpakai + $kpiHadiah) === 0;

// Warna heat: persen 0..100 → hue 0 (merah) .. 120 (hijau)
$hue = fn($pct): int => (int)round(max(0, min(100, (float)$pct)) * 1.2);

// ── Data per program ──────────────────────────────────────────────────────
$buildCardData = function(string $key, array $prog) use ($monthlyData, $cumulativeData, $voucherByProgram, $evoucherByProgram, $voucherCumByProgram, $evoucherCumByProgram, $hadiahByProgram, $ehadiahByProgram, $evoucherItemsGrouped): array {
    $isS     = str_starts_with($key, 's_');
    $isEv    = ($prog['target_type'] ?? '') === 'evoucher';
    $data    = $monthlyData[$key] ?? [];
    $cum     = $cumulativeData[$key] ?? [];
    $mBaru   = (int)($data['total_jumlah'] ?? 0);
    $mAktif  = (int)($data['total_member_aktif'] ?? 0);
    $mBaruCum  = (int)($cum['total_jumlah'] ?? 0);
    $mAktifCum = (int)($cum['total_member_aktif'] ?? 0);
    $vd      = $isS ? ($voucherByProgram[$prog['id']] ?? [])    : ($evoucherByProgram[$prog['id']] ?? []);
    $vdCum   = $isS ? ($voucherCumByProgram[$prog['id']] ?? []) : ($evoucherCumByProgram[$prog['id']] ?? []);
    $vSebar  = (int)($vd['total_tersebar'] ?? 0);
    $vPakai  = (int)($vd['total_terpakai'] ?? 0);
    $vPakaiCum = (int)($vdCum['total_terpakai'] ?? 0);
    $hDibagi = $isS ? (int)($hadiahByProgram[$prog['id']] ?? 0) : (int)($ehadiahByProgram[$prog['id']] ?? 0);
    $vQuota  = 0;
    if ($isS && $isEv && ($prog['total_voucher'] ?? 0) > 0) {
        $vQuota = (int)$prog['total_voucher'];
    } elseif (!$isS && isset($evoucherItemsGrouped[$prog['id']])) {
        $vQuota = array_sum(array_column($evoucherItemsGrouped[$prog['id']], 'total_diterbitkan'));
    }
    $isMultiMonth = ($mBaruCum > $mBaru) || ($vPakaiCum > $vPakai);
    $tgt = 0; $ach = 0;
    if (($prog['target_peserta'] ?? 0) > 0)      { $tgt++; if ($mBaruCum  >= (int)$prog['target_peserta'])      $ach++; }
    if (($prog['target_member_aktif'] ?? 0) > 0) { $tgt++; if ($mAktifCum >= (int)$prog['target_member_aktif']) $ach++; }
    if ($vQuota > 0)                             { $tgt++; if ($vPakaiCum >= $vQuota)                            $ach++; }
    return compact('isS','isEv','mBaru','mAktif','mBaruCum','mAktifCum','vSebar','vPakai','vPakaiCum','hDibagi','vQuota','tgt','ach','isMultiMonth');
};

// Ringkasan status target — SEMUA program (logika sama dengan sebelumnya)
$sumPrograms = 0; $sumAchieved = 0; $sumPartial = 0; $sumBelum = 0; $sumNoTarget = 0;
$sumEvalBerhasil = 0; $sumEvalSebagian = 0; $sumEvalGagal = 0;
foreach ($programMap as $key => $prog) {
    if (($prog['source'] ?? '') === 'standalone' && ($prog['locked'] ?? false) && ($prog['eval_status'] ?? '')) {
        if ($prog['eval_status'] === 'berhasil')      $sumEvalBerhasil++;
        elseif ($prog['eval_status'] === 'sebagian')  $sumEvalSebagian++;
        elseif ($prog['eval_status'] === 'gagal')     $sumEvalGagal++;
    }
}
$sumEvalTotal = $sumEvalBerhasil + $sumEvalSebagian + $sumEvalGagal;
foreach ($programMap as $key => $prog) {
    $d2 = $buildCardData($key, $prog);
    $sumPrograms++;
    if ($d2['tgt'] === 0)              $sumNoTarget++;
    elseif ($d2['ach'] === $d2['tgt']) $sumAchieved++;
    elseif ($d2['ach'] > 0)            $sumPartial++;
    else                               $sumBelum++;
}
$sumWithTarget = $sumPrograms - $sumNoTarget;

// Program relevan dengan bulan terpilih (sama seperti sebelumnya)
$bMonthStart = $bulan . '-01';
$bMonthEnd   = date('Y-m-t', strtotime($bMonthStart));
$progInMonth = function (string $key, array $prog) use ($bMonthStart, $bMonthEnd, $monthlyData, $voucherByProgram, $evoucherByProgram, $hadiahByProgram, $ehadiahByProgram): bool {
    $isS     = str_starts_with($key, 's_');
    $mulai   = $isS ? ($prog['tanggal_mulai']   ?? '') : ($prog['event_start_date'] ?? '');
    $selesai = $isS ? ($prog['tanggal_selesai'] ?? '') : ($prog['event_start_date'] ?? '');
    if ($mulai && $mulai <= $bMonthEnd && (empty($selesai) || $selesai >= $bMonthStart)) return true;
    $md = $monthlyData[$key] ?? [];
    if ((int)($md['total_jumlah'] ?? 0) > 0 || (int)($md['total_member_aktif'] ?? 0) > 0) return true;
    $vd = $isS ? ($voucherByProgram[$prog['id']] ?? []) : ($evoucherByProgram[$prog['id']] ?? []);
    if ((int)($vd['total_tersebar'] ?? 0) > 0 || (int)($vd['total_terpakai'] ?? 0) > 0) return true;
    $h = $isS ? (int)($hadiahByProgram[$prog['id']] ?? 0) : (int)($ehadiahByProgram[$prog['id']] ?? 0);
    return $h > 0;
};

$mapA = []; $mapB = []; $mapC = [];
foreach ($programMap as $key => $prog) {
    if (! $progInMonth($key, $prog)) continue;
    if ($prog['source'] === 'event')                       $mapC[$key] = $prog;
    elseif (($prog['status'] ?? 'active') === 'inactive')  $mapB[$key] = $prog;
    else                                                   $mapA[$key] = $prog;
}
$orderedPrograms = $mapA + $mapB + $mapC;   // urutan bawaan: standalone aktif, non-aktif, event

$pRows = [];       // untuk tabel & payload JS
$ord = 0;
foreach ($orderedPrograms as $key => $prog) {
    $d = $buildCardData($key, $prog);
    $isS = $d['isS'];
    $tB = (int)($prog['target_peserta'] ?? 0);
    $tA = (int)($prog['target_member_aktif'] ?? 0);
    $status = $d['tgt'] === 0 ? 'notarget' : ($d['ach'] === $d['tgt'] ? 'done' : ($d['ach'] > 0 ? 'partial' : 'belum'));
    $an = $analisaMap[$key] ?? [];
    $filled = trim(($an['analisa'] ?? '') . ($an['highlight'] ?? '') . ($an['kendala'] ?? '') . ($an['tindak_lanjut'] ?? '')) !== '';
    // Kelengkapan menurut aturan (status target menentukan kolom wajib) — validator sama dengan JS & penjaga cetak
    $ev = \App\Controllers\LoyaltyCtrl::analisaEvaluasi($status, $an);
    $anState = $ev['lengkap'] ? 'ok' : ($ev['draf'] ? 'draft' : 'no');
    $unmet = [];
    if ($tB > 0 && $d['mBaruCum'] < $tB)        $unmet[] = ['label' => 'Member baru', 'cur' => $d['mBaruCum'],  'target' => $tB];
    if ($tA > 0 && $d['mAktifCum'] < $tA)       $unmet[] = ['label' => 'Member aktif', 'cur' => $d['mAktifCum'], 'target' => $tA];
    if ($d['vQuota'] > 0 && $d['vPakaiCum'] < $d['vQuota']) $unmet[] = ['label' => 'Voucher terpakai', 'cur' => $d['vPakaiCum'], 'target' => $d['vQuota']];
    $pRows[] = $d + [
        'key' => $key, 'prog' => $prog, 'status' => $status, 'ord' => $ord++,
        'tB' => $tB, 'tA' => $tA, 'filled' => $filled, 'unmet' => $unmet,
        'lengkap' => $ev['lengkap'], 'kurang' => $ev['kurang'], 'anState' => $anState,
        'red' => $d['vSebar'] > 0 ? round($d['vPakai'] / $d['vSebar'] * 100, 1) : null,
        'pctB' => $tB > 0 ? min(100, round($d['mBaruCum'] / $tB * 100, 1)) : null,
        'pctA' => $tA > 0 ? min(100, round($d['mAktifCum'] / $tA * 100, 1)) : null,
        'pctQ' => $d['vQuota'] > 0 ? min(100, round($d['vPakaiCum'] / $d['vQuota'] * 100, 1)) : null,
    ];
}
$emptyAnalisa = count(array_filter($pRows, fn($r) => ! $r['lengkap']));   // program yang analisanya belum lengkap
$kolomAn     = \App\Controllers\LoyaltyCtrl::KOLOM_ANALISA;
$aturanMin   = (int)$aturanAnalisa['min'];
$statusFrasa = ['done' => 'tercapai', 'partial' => 'sebagian tercapai', 'belum' => 'belum tercapai', 'notarget' => 'tanpa target'];
$anSortVal   = ['ok' => 2, 'draft' => 1, 'no' => 0];
$statusLabel = ['done' => 'Tercapai', 'partial' => 'Sebagian', 'belum' => 'Belum tercapai', 'notarget' => 'Tanpa target'];
$statusOrd   = ['belum' => 0, 'partial' => 1, 'done' => 2, 'notarget' => 3];

// ── Voucher & hadiah (digabung, kolom Sumber) ─────────────────────────────
$flatVouchers = [];
foreach ($voucherItemsGrouped as $progId => $items) {
    $prog = $programMap['s_' . $progId] ?? null;
    foreach ($items as $vi) {
        $flatVouchers[] = ['item' => $vi, 'prog' => $prog, 'src' => 'standalone', 'pkey' => 's_' . $progId,
            'vd' => $vMonthly[$vi['id']] ?? ['total_tersebar' => 0, 'total_terpakai' => 0]];
    }
}
foreach ($evoucherItemsGrouped as $progId => $items) {
    $prog = $programMap['e_' . $progId] ?? null;
    foreach ($items as $vi) {
        $flatVouchers[] = ['item' => $vi, 'prog' => $prog, 'src' => 'event', 'pkey' => 'e_' . $progId,
            'vd' => $evMonthly[$vi['id']] ?? ['total_tersebar' => 0, 'total_terpakai' => 0]];
    }
}
$flatHadiah = [];
foreach ($hadiahItemsGrouped as $progId => $items) {
    $prog = $programMap['s_' . $progId] ?? null;
    foreach ($items as $hi) {
        $flatHadiah[] = ['item' => $hi, 'prog' => $prog, 'src' => 'standalone', 'pkey' => 's_' . $progId,
            'dibagikan' => (int)($hMonthly[$hi['id']] ?? 0), 'totalAll' => (int)($hAllTime[$hi['id']] ?? 0)];
    }
}
foreach ($ehadiahItemsGrouped as $progId => $items) {
    $prog = $programMap['e_' . $progId] ?? null;
    foreach ($items as $hi) {
        $flatHadiah[] = ['item' => $hi, 'prog' => $prog, 'src' => 'event', 'pkey' => 'e_' . $progId,
            'dibagikan' => (int)($ehMonthly[$hi['id']] ?? 0), 'totalAll' => (int)($ehAllTime[$hi['id']] ?? 0)];
    }
}
$hasVouchers = ! empty($flatVouchers);
$hasHadiah   = ! empty($flatHadiah);

// Payload untuk JS (panel perhatian, ringkasan, grafik, simpan analisa)
$jsProgs = array_map(fn($r) => [
    'key' => $r['key'], 'name' => $r['prog']['nama_program'], 'status' => $r['status'], 'filled' => $r['filled'],
    'lengkap' => $r['lengkap'], 'kurang' => $r['kurang'], 'state' => $r['anState'],
    'unmet' => $r['unmet'], 'tgt' => $r['tgt'], 'ach' => $r['ach'], 'inactive' => ($r['prog']['status'] ?? 'active') === 'inactive',
], $pRows);
$jsVouchers = array_map(fn($v, $i) => [
    'row' => 'v' . $i, 'name' => $v['item']['nama_voucher'], 'prog' => $v['prog']['nama_program'] ?? '',
    'tersebar' => (int)$v['vd']['total_tersebar'], 'terpakai' => (int)$v['vd']['total_terpakai'],
], $flatVouchers, array_keys($flatVouchers));
$jsHadiah = array_map(fn($h, $i) => [
    'row' => 'h' . $i, 'name' => $h['item']['nama_hadiah'], 'prog' => $h['prog']['nama_program'] ?? '',
    'stok' => (int)$h['item']['stok'], 'total' => $h['totalAll'], 'bulanIni' => $h['dibagikan'],
], $flatHadiah, array_keys($flatHadiah));
$payload = [
    'bulan' => $bulan, 'bulanLabel' => $bulanLabel, 'prevLabel' => $prevBulanLabel, 'hasPrev' => $hasPrev, 'bulanKosong' => $bulanKosong,
    'canEdit' => (bool)$canEdit, 'saveUrl' => base_url('loyalty/summary/analisa'),
    'aturan' => $aturanAnalisa + ['printUrl' => base_url('loyalty/summary/print?bulan=' . $bulan), 'labels' => $kolomAn],
    'csrf' => ['name' => csrf_token(), 'hash' => csrf_hash()],
    'kpi'  => ['member' => $kpiMember, 'aktif' => $kpiMemberAktif, 'tersebar' => $kpiTersebar, 'terpakai' => $kpiTerpakai, 'hadiah' => $kpiHadiah,
               'nilai' => $nilaiRealisasi, 'serapan' => $serapanPct, 'budgetAktif' => $totalBudgetActive, 'budget' => $totalBudget, 'redemption' => $pctTerpakai],
    'prev' => ['member' => $kpiMemberPrev, 'aktif' => $kpiMemberAktifPrev, 'tersebar' => $kpiTersebarPrev, 'terpakai' => $kpiTerpakaiPrev, 'hadiah' => $kpiHadiahPrev, 'nilai' => $nilaiRealisasiPrev],
    'status' => ['done' => $sumAchieved, 'partial' => $sumPartial, 'belum' => $sumBelum, 'notarget' => $sumNoTarget, 'total' => $sumPrograms],
    'programs' => $jsProgs, 'vouchers' => $jsVouchers, 'hadiah' => $jsHadiah,
    'trend' => ['labels' => $trendLabels, 'member' => $trendMember, 'aktif' => $trendMemberAktif, 'sebar' => $trendSebar, 'terpakai' => $trendTerpakai, 'hadiah' => $trendHadiah, 'redemption' => $trendRedemption, 'year' => $trendYear],
    'daily' => ['labels' => $chartDates, 'member' => $dailyMember, 'tersebar' => $dailyTersebar, 'terpakai' => $dailyTerpakai],
];
$hasChartData = array_sum($dailyMember) + array_sum($dailyTersebar) + array_sum($dailyTerpakai) > 0;
$latestOther = null;
foreach ($monthList as $m) { if ($m !== $bulan) { $latestOther = $m; break; } }

// KPI helper
$kpiCard = function (string $tone, string $icon, string $label, string $value, string $sub, string $deltaHtml = '', string $title = '', string $extra = '') {
    ?>
    <div class="ls-kpi" data-tone="<?= $tone ?>"<?= $title !== '' ? ' title="' . esc($title) . '"' : '' ?>>
        <div class="ls-kpi-top"><span class="ls-chip-ico"><i class="bi bi-<?= $icon ?>"></i></span><span class="ls-kpi-label"><?= $label ?></span></div>
        <div class="ls-kpi-val"><?= $value ?></div>
        <?= $extra ?>
        <div class="ls-kpi-foot"><?= $deltaHtml ?><span class="ls-kpi-sub"><?= $sub ?></span></div>
    </div>
    <?php
};
?>

<div class="page-loy-sum" id="loySum">

    <!-- Header -->
    <div class="ls-head">
        <a href="<?= base_url('loyalty') ?>" class="ls-iconbtn" title="Kembali ke Program Loyalty"><i class="bi bi-arrow-left"></i></a>
        <div class="min-w-0">
            <h1 class="ls-title">Summary Bulanan — Program Loyalty</h1>
            <div class="ls-subtitle">Agregasi realisasi dari semua program (standalone + event)</div>
        </div>
    </div>

    <?php if ($bulanFallback ?? null): ?>
    <div class="ls-banner" role="status" id="lsBannerFallback">
        <i class="bi bi-info-circle-fill"></i>
        <span>Bulan berjalan belum ada data — menampilkan <strong><?= esc($bulanLabel) ?></strong> (data terakhir).</span>
        <a href="?bulan=<?= esc($bulanSekarang) ?>" class="ls-banner-link">Lihat bulan berjalan <i class="bi bi-arrow-right"></i></a>
    </div>
    <?php elseif ($bulanKosong): ?>
    <div class="ls-banner is-empty" role="status" id="lsBannerEmpty">
        <i class="bi bi-inbox"></i>
        <span>Belum ada realisasi di <strong><?= esc($bulanLabel) ?></strong>.</span>
        <?php if ($latestOther): ?>
        <a href="?bulan=<?= esc($latestOther) ?>" class="ls-banner-link">Lihat <?= esc($labelBulan($latestOther)) ?> <i class="bi bi-arrow-right"></i></a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if (! empty($aturanAnalisa['arsip']) && $aturanAnalisa['wajibCetak'] && ! empty($pRows)): ?>
    <div class="ls-banner is-empty" role="status" id="lsBannerArsip">
        <i class="bi bi-archive"></i>
        <span><strong>Bulan arsip.</strong> Aturan kelengkapan tidak berlaku untuk bulan arsip — laporan bisa dicetak tanpa melengkapi analisa. Aturan hanya berlaku untuk bulan berjalan dan <?= (int)\App\Controllers\LoyaltyCtrl::BATAS_BULAN_WAJIB ?> bulan sebelumnya.</span>
    </div>
    <?php endif; ?>

    <!-- A. Bilah filter menempel -->
    <div class="ls-bar" id="lsBar">
        <div class="ls-bar-nav">
            <a href="?bulan=<?= $prevBulan ?>" class="ls-iconbtn" title="Bulan sebelumnya" aria-label="Bulan sebelumnya"><i class="bi bi-chevron-left"></i></a>
            <select class="ls-select" aria-label="Pilih bulan" onchange="location.href='?bulan='+this.value">
                <?php foreach ($monthList as $m): ?>
                <option value="<?= $m ?>" <?= $m === $bulan ? 'selected' : '' ?>><?= esc($labelBulan($m)) ?></option>
                <?php endforeach; ?>
            </select>
            <a href="?bulan=<?= $nextBulan ?>" class="ls-iconbtn" title="Bulan berikutnya" aria-label="Bulan berikutnya"><i class="bi bi-chevron-right"></i></a>
            <a href="?bulan=<?= date('Y-m') ?>" class="ls-btn ls-btn-ghost">Bulan Ini</a>
        </div>
        <div class="ls-bar-title d-none d-lg-block"><?= esc($bulanLabel) ?></div>
        <div class="ls-bar-actions">
<?php if (empty($aturanAnalisa['arsip'])): /* bulan arsip: aturan kelengkapan tidak berlaku */ ?>
            <span id="analisaWarn" class="ls-warnbadge" style="display:none" role="button" tabindex="0" title="Tampilkan program yang analisanya belum lengkap">
                <i class="bi bi-exclamation-triangle-fill"></i><span><span id="analisaWarnCount">0</span> program analisanya belum lengkap</span>
            </span>
            <?php endif; ?>
            <a href="<?= base_url('loyalty/summary/print?bulan=' . $bulan) ?>" target="_blank" id="btnCetak" class="ls-btn ls-btn-primary">
                <i class="bi bi-printer" data-ico></i><span>Laporan Bulanan</span><b class="ls-lockn" hidden></b>
            </a>
        </div>
    </div>

    <!-- B. Strip KPI -->
    <section class="ls-kpis" aria-label="Indikator utama <?= esc($bulanLabel) ?>">
        <?php
        $kpiCard('blue', 'person-plus', 'Member Baru', '<span data-count="' . $kpiMember . '">' . $n0($kpiMember) . '</span>',
            'bln lalu ' . $n0($kpiMemberPrev), $delta($kpiMember, $kpiMemberPrev));
        $kpiCard('cyan', 'person-check', 'Member Aktif', '<span data-count="' . $kpiMemberAktif . '">' . $n0($kpiMemberAktif) . '</span>',
            'bln lalu ' . $n0($kpiMemberAktifPrev), $delta($kpiMemberAktif, $kpiMemberAktifPrev));
        $kpiCard('gold', 'ticket-perforated', 'Voucher Tersebar', '<span data-count="' . $kpiTersebar . '">' . $n0($kpiTersebar) . '</span>',
            'bln lalu ' . $n0($kpiTersebarPrev), $delta($kpiTersebar, $kpiTersebarPrev));
        $redBar = $pctTerpakai !== null
            ? '<div class="ls-bar-mini" aria-hidden="true"><i data-c="' . ($pctTerpakai >= 60 ? 'ok' : ($pctTerpakai >= 30 ? 'mid' : 'low')) . '" style="width:' . min(100, $pctTerpakai) . '%"></i></div>'
            : '';
        $kpiCard('green', 'ticket', 'Voucher Dipakai', '<span data-count="' . $kpiTerpakai . '">' . $n0($kpiTerpakai) . '</span>',
            $pctTerpakai !== null ? '<strong>' . $pctTerpakai . '%</strong> redemption' . ($pctTerpakaiPrev !== null ? ' <small>(bln lalu ' . $pctTerpakaiPrev . '%)</small>' : '') : 'redemption —',
            $delta($kpiTerpakai, $kpiTerpakaiPrev), 'Redemption = terpakai ÷ tersebar', $redBar);
        $kpiCard('rose', 'gift', 'Hadiah Dibagikan', '<span data-count="' . $kpiHadiah . '">' . $n0($kpiHadiah) . '</span>',
            'bln lalu ' . $n0($kpiHadiahPrev), $delta($kpiHadiah, $kpiHadiahPrev));
        $serTone = $serapanPct > 100 ? 'is-bad' : ($serapanPct >= 80 ? 'is-warn' : 'is-ok');
        $kpiCard('emerald', 'cash-coin', 'Benefit Tersalurkan', '<span class="ls-rp">Rp</span> ' . number_format($nilaiRealisasi, 0, ',', '.'),
            $totalBudgetActive > 0 ? '<strong class="ls-ser ' . $serTone . '">' . $serapanPct . '%</strong> serapan budget' : 'budget aktif belum di-set',
            $delta($nilaiRealisasi, $nilaiRealisasiPrev, true),
            'Voucher ' . $rp($nilaiVoucher) . ' + Hadiah ' . $rp($nilaiHadiah));
        $kpiCard('slate', 'wallet2', 'Total Budget', '<span class="ls-rp">Rp</span> ' . number_format($totalBudgetActive, 0, ',', '.'),
            $totalBudget !== $totalBudgetActive ? 'program aktif · total ' . $rp($totalBudget) : count($programs) . ' program');
        ?>
    </section>

    <!-- C. Perlu Perhatian + status target -->
    <div class="ls-grid-attn">
        <section class="ls-card" id="lsAttn" aria-labelledby="lsAttnT">
            <div class="ls-card-h">
                <h2 id="lsAttnT"><i class="bi bi-bell-fill"></i>Perlu Perhatian</h2>
                <span class="ls-count" id="lsAttnCount">0</span>
                <div class="ls-card-tools">
                    <button type="button" class="ls-btn ls-btn-ghost ls-btn-sm" id="lsCopy" title="Salin ringkasan bulan ini ke clipboard"><i class="bi bi-clipboard"></i><span>Salin ringkasan</span></button>
                </div>
            </div>
            <ol class="ls-attn" id="lsAttnList"><li class="ls-attn-empty">Memeriksa data…</li></ol>
            <button type="button" class="ls-more" id="lsAttnMore" hidden></button>
        </section>

        <section class="ls-card" aria-labelledby="lsStatT">
            <div class="ls-card-h"><h2 id="lsStatT"><i class="bi bi-bullseye"></i>Status Target Bulan Ini</h2></div>
            <?php if ($sumWithTarget > 0): ?>
            <div class="ls-stack" role="img" aria-label="Komposisi status target">
                <?php foreach (['done' => $sumAchieved, 'partial' => $sumPartial, 'belum' => $sumBelum, 'notarget' => $sumNoTarget] as $k => $v): if ($v < 1) continue; ?>
                <i data-st="<?= $k ?>" style="flex:<?= $v ?>" title="<?= $statusLabel[$k] ?>: <?= $v ?>"></i>
                <?php endforeach; ?>
            </div>
            <ul class="ls-legend">
                <li><button type="button" data-filter-status="done"><i data-st="done"></i>Semua target tercapai<b><?= $sumAchieved ?></b></button></li>
                <li><button type="button" data-filter-status="partial"><i data-st="partial"></i>Sebagian tercapai<b><?= $sumPartial ?></b></button></li>
                <li><button type="button" data-filter-status="belum"><i data-st="belum"></i>Belum tercapai<b><?= $sumBelum ?></b></button></li>
                <li><button type="button" data-filter-status="notarget"><i data-st="notarget"></i>Tanpa target<b><?= $sumNoTarget ?></b></button></li>
            </ul>
            <div class="ls-foot-note">Dari <?= $sumPrograms ?> program terdaftar (semua bulan).</div>
            <?php if ($sumEvalTotal > 0): ?>
            <div class="ls-eval">
                <span class="ls-eval-t">Evaluasi program terkunci</span>
                <?php if ($sumEvalBerhasil > 0): ?><span class="ls-pill" data-st="done"><i class="bi bi-lock-fill"></i><?= $sumEvalBerhasil ?> berhasil</span><?php endif; ?>
                <?php if ($sumEvalSebagian > 0): ?><span class="ls-pill" data-st="partial"><i class="bi bi-lock-fill"></i><?= $sumEvalSebagian ?> sebagian</span><?php endif; ?>
                <?php if ($sumEvalGagal > 0): ?><span class="ls-pill" data-st="belum"><i class="bi bi-lock-fill"></i><?= $sumEvalGagal ?> perlu perbaikan</span><?php endif; ?>
            </div>
            <?php endif; ?>
            <?php else: ?>
            <div class="ls-empty-s">Belum ada program dengan target.</div>
            <?php endif; ?>
        </section>
    </div>

    <!-- C2. Kelengkapan Analisa (diisi JS; tampil bila cetak terkunci) -->
    <section class="ls-card ls-comp" id="kelengkapan" aria-labelledby="lsCompT" hidden>
        <div class="ls-card-h">
            <h2 id="lsCompT"><i class="bi bi-lock-fill"></i>Kelengkapan Analisa</h2>
            <span class="ls-count is-bad" id="lsCompCount">0</span>
        </div>
        <p class="ls-comp-p" id="lsCompIntro"></p>
        <ul class="ls-comp-list" id="lsCompList"></ul>
        <div class="ls-comp-foot" id="lsCompFoot" hidden></div>
    </section>

    <!-- D. Tabel Kinerja Program -->
    <section class="ls-card" id="lsPrograms" aria-labelledby="lsProgT">
        <div class="ls-card-h">
            <h2 id="lsProgT"><i class="bi bi-table"></i>Kinerja Program</h2>
            <span class="ls-count" id="lsProgCount"><?= count($pRows) ?></span>
            <span class="ls-hint d-none d-md-inline">program aktif di <?= esc($bulanLabel) ?></span>
        </div>
        <?php if (empty($pRows)): ?>
        <div class="ls-empty"><i class="bi bi-inbox"></i><p>Belum ada program yang berjalan atau punya realisasi di bulan ini.</p></div>
        <?php else: ?>
        <div class="ls-filters" id="lsFilters">
            <label class="ls-search"><i class="bi bi-search"></i><input type="search" id="lsSearch" placeholder="Cari program…" aria-label="Cari program" autocomplete="off"></label>
            <div class="ls-seg" role="group" aria-label="Jenis program" data-group="src">
                <button type="button" class="is-on" data-v="">Semua</button>
                <button type="button" data-v="standalone">Standalone</button>
                <button type="button" data-v="event">Event</button>
            </div>
            <div class="ls-seg" role="group" aria-label="Status target" data-group="status">
                <button type="button" class="is-on" data-v="">Semua status</button>
                <button type="button" data-v="done" data-st="done">Tercapai</button>
                <button type="button" data-v="partial" data-st="partial">Sebagian</button>
                <button type="button" data-v="belum" data-st="belum">Belum</button>
                <button type="button" data-v="notarget" data-st="notarget">Tanpa target</button>
            </div>
            <button type="button" class="ls-toggle" id="lsNeedAnalisa" aria-pressed="false"><i class="bi bi-chat-left-dots"></i>Belum lengkap <b><?= $emptyAnalisa ?></b></button>
            <button type="button" class="ls-link" id="lsReset" hidden><i class="bi bi-x-circle"></i>Reset</button>
        </div>
        <div class="ls-scroll">
        <table class="loy-table" id="lsProgTable">
            <thead>
            <tr>
                <th class="ls-sticky" data-sort="name" aria-sort="none"><button type="button">Program</button></th>
                <th data-sort="member" data-num aria-sort="none"><button type="button">Member: target vs realisasi</button></th>
                <th data-sort="sebar" data-num class="t-r" aria-sort="none"><button type="button">Tersebar</button></th>
                <th data-sort="pakai" data-num class="t-r" aria-sort="none"><button type="button">Terpakai</button></th>
                <th data-sort="red" data-num aria-sort="none"><button type="button">Redemption</button></th>
                <th data-sort="hadiah" data-num class="t-r" aria-sort="none"><button type="button">Hadiah</button></th>
                <th data-sort="status" data-num aria-sort="none"><button type="button">Status</button></th>
                <th data-sort="analisa" data-num class="t-c" aria-sort="none"><button type="button">Analisa</button></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($pRows as $r):
                $p = $r['prog']; $key = $r['key']; $isInactive = ($p['status'] ?? 'active') === 'inactive';
                $src = $r['isS'] ? 'standalone' : 'event';
                $mainPct = $r['pctB'] ?? ($r['pctA'] ?? null);
                $memberSort = $mainPct ?? -1;
                $analisaData = $analisaMap[$key] ?? [];
                $prevTL = trim(($prevAnalisaMap[$key] ?? [])['tindak_lanjut'] ?? '');
                $detailUrl = base_url('loyalty/detail/' . ($r['isS'] ? 's' : 'e') . '/' . $p['id']);
            ?>
            <tr class="ls-row" id="row-<?= esc($key) ?>" data-key="<?= esc($key) ?>" data-src="<?= $src ?>" data-status="<?= $r['status'] ?>"
                data-need="<?= $r['lengkap'] ? '0' : '1' ?>" data-ord="<?= $r['ord'] ?>"
                data-name="<?= esc(mb_strtolower($p['nama_program'] . ' ' . ($p['event_name'] ?? ''))) ?>"
                data-s-name="<?= esc(mb_strtolower($p['nama_program'])) ?>" data-s-member="<?= $memberSort ?>"
                data-s-sebar="<?= $r['vSebar'] ?>" data-s-pakai="<?= $r['vPakai'] ?>" data-s-red="<?= $r['red'] ?? -1 ?>"
                data-s-hadiah="<?= $r['hDibagi'] ?>" data-s-status="<?= $statusOrd[$r['status']] ?>" data-s-analisa="<?= $anSortVal[$r['anState']] ?>">
                <td class="ls-sticky">
                    <button type="button" class="ls-exp" aria-expanded="false" aria-controls="det-<?= esc($key) ?>" title="Buka analisa & detail">
                        <i class="bi bi-chevron-right"></i>
                        <span class="ls-pname">
                            <span class="ls-pname-t"><?= esc($p['nama_program']) ?></span>
                            <span class="ls-pname-s">
                                <span class="ls-tag <?= $r['isS'] ? 'is-std' : 'is-evt' ?>"><?= $r['isS'] ? 'Standalone' : 'Event' ?></span>
                                <?php if (($p['target_type'] ?? '') === 'evoucher'): ?><span class="ls-tag">e-Voucher</span>
                                <?php elseif (($p['target_type'] ?? '') === 'member'): ?><span class="ls-tag">Member</span><?php endif; ?>
                                <?php if ($isInactive): ?><span class="ls-tag is-off">Non-aktif</span><?php endif; ?>
                                <?php if ($r['isS'] && ($p['locked'] ?? false) && ($p['eval_status'] ?? '')):
                                    $evL = ['berhasil'=>'Berhasil','sebagian'=>'Sebagian','gagal'=>'Perlu Perbaikan'][$p['eval_status']] ?? $p['eval_status']; ?>
                                <span class="ls-tag is-eval" data-ev="<?= esc($p['eval_status']) ?>"><i class="bi bi-lock-fill"></i><?= esc($evL) ?></span><?php endif; ?>
                                <?php if (! $r['isS'] && ($p['event_name'] ?? '')): ?><span class="ls-evname"><i class="bi bi-calendar-event"></i><?= esc($p['event_name']) ?></span><?php endif; ?>
                            </span>
                        </span>
                    </button>
                </td>
                <td>
                    <div class="ls-mem">
                        <span class="ls-mem-n"><b><?= $n0($r['mBaru']) ?></b> baru · <b><?= $n0($r['mAktif']) ?></b> aktif</span>
                        <?php foreach ([['pctB', 'mBaruCum', 'tB', 'Baru'], ['pctA', 'mAktifCum', 'tA', 'Aktif']] as [$pk, $ck, $tk, $lb]): if ($r[$pk] === null) continue; ?>
                        <div class="ls-prog" data-heat style="--h:<?= $hue($r[$pk]) ?>" title="<?= $lb ?> <?= $r['isMultiMonth'] ? '(kumulatif) ' : '' ?><?= $n0($r[$ck]) ?> dari target <?= $n0($r[$tk]) ?>">
                            <span class="ls-prog-l"><?= $lb ?><?= $r['isMultiMonth'] ? ' kum.' : '' ?> <?= $n0($r[$ck]) ?>/<?= $n0($r[$tk]) ?></span>
                            <span class="ls-bar-mini"><i data-c="<?= $r[$pk] >= 100 ? 'ok' : ($r[$pk] >= 60 ? 'good' : ($r[$pk] >= 30 ? 'mid' : 'low')) ?>" style="width:<?= max($r[$pk], 0) ?>%"></i></span>
                            <span class="ls-prog-p"><?= $r[$pk] ?>%</span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </td>
                <td class="t-r num"><?= $r['vSebar'] > 0 ? $n0($r['vSebar']) : '<span class="ls-dash">—</span>' ?></td>
                <td class="t-r num">
                    <?= $r['vPakai'] > 0 ? $n0($r['vPakai']) : '<span class="ls-dash">—</span>' ?>
                    <?php if ($r['pctQ'] !== null): ?><div class="ls-sub" title="Kuota voucher: <?= $n0($r['vPakaiCum']) ?> terpakai dari <?= $n0($r['vQuota']) ?>">kuota <?= $n0($r['vPakaiCum']) ?>/<?= $n0($r['vQuota']) ?> (<?= $r['pctQ'] ?>%)</div><?php endif; ?>
                </td>
                <td>
                    <?php if ($r['red'] !== null): ?>
                    <div class="ls-prog is-solo" data-heat style="--h:<?= $hue($r['red'] * 1.0) ?>">
                        <span class="ls-bar-mini"><i data-c="<?= $r['red'] >= 60 ? 'ok' : ($r['red'] >= 30 ? 'mid' : 'low') ?>" style="width:<?= min(100, $r['red']) ?>%"></i></span>
                        <span class="ls-prog-p"><?= $r['red'] ?>%</span>
                    </div>
                    <?php else: ?><span class="ls-dash">—</span><?php endif; ?>
                </td>
                <td class="t-r num"><?= $r['hDibagi'] > 0 ? $n0($r['hDibagi']) : '<span class="ls-dash">—</span>' ?></td>
                <td>
                    <?php if ($r['status'] === 'notarget'): ?>
                    <span class="ls-pill" data-st="notarget"><i class="bi bi-dash-circle"></i>Tanpa target</span>
                    <?php elseif ($r['status'] === 'done'): ?>
                    <span class="ls-pill" data-st="done"><i class="bi bi-check-circle-fill"></i>Tercapai</span>
                    <?php elseif ($r['status'] === 'partial'): ?>
                    <span class="ls-pill" data-st="partial"><i class="bi bi-exclamation-circle-fill"></i>Sebagian <small><?= $r['ach'] ?>/<?= $r['tgt'] ?></small></span>
                    <?php else: ?>
                    <span class="ls-pill" data-st="belum"><i class="bi bi-x-circle-fill"></i>Belum</span>
                    <?php endif; ?>
                </td>
                <td class="t-c">
                    <?php $anCell = ['ok' => ['is-ok', 'check-circle-fill', 'lengkap', 'Analisa lengkap'], 'draft' => ['is-draft', 'circle-half', 'draf', 'Analisa masih draf — ada kolom wajib yang kurang'], 'no' => ['is-no', 'circle', 'belum', 'Analisa belum diisi']][$r['anState']]; ?>
                    <span class="ls-an <?= $anCell[0] ?>" data-an-cell title="<?= $anCell[3] ?>">
                        <i class="bi bi-<?= $anCell[1] ?>"></i><span><?= $anCell[2] ?></span>
                    </span>
                </td>
            </tr>
            <tr class="ls-det" id="det-<?= esc($key) ?>" data-for="<?= esc($key) ?>" hidden>
                <td colspan="8">
                    <div class="ls-det-in">
                        <div class="ls-det-info">
                            <?php if ($prevTL): ?>
                            <div class="ls-prevtl">
                                <div class="ls-prevtl-t"><i class="bi bi-arrow-return-right"></i>Tindak lanjut bulan lalu</div>
                                <div class="ls-prevtl-b"><?= esc($prevTL) ?></div>
                            </div>
                            <?php endif; ?>
                            <?php if (($p['mekanisme'] ?? '') !== ''): ?>
                            <div class="ls-mek"><span>Mekanisme</span><?= esc($p['mekanisme']) ?></div>
                            <?php endif; ?>
                            <a href="<?= $detailUrl ?>" class="ls-btn ls-btn-ghost ls-btn-sm">Detail program <i class="bi bi-arrow-right"></i></a>
                        </div>
                        <?php
                        $srcL = $r['isS'] ? 's' : 'e';
                        $highlight = $analisaData['highlight'] ?? ''; $kendala = $analisaData['kendala'] ?? '';
                        $tindakLanjut = $analisaData['tindak_lanjut'] ?? ''; $analisa = $analisaData['analisa'] ?? '';
                        ?>
                        <?php
                        $wajibKol = \App\Controllers\LoyaltyCtrl::analisaKolomWajib($r['status']);
                        $arsipAn  = ! empty($aturanAnalisa['arsip']);
                        $hintAn = [
                            'highlight'     => ['Apa yang berjalan baik bulan ini?', 'mis. Voucher F&B habis dalam 3 hari pertama.'],
                            'kendala'       => ['Hambatan yang terjadi + sebabnya bila diketahui.', 'mis. Klaim voucher rumit, banyak pelanggan batal.'],
                            'tindak_lanjut' => ['Rencana aksi bulan depan: siapa/apa/kapan.', 'mis. Sederhanakan alur klaim; tambah staf konter akhir pekan.'],
                        ];
                        $icoAn = ['highlight' => '✅', 'kendala' => '⚠️', 'tindak_lanjut' => '🔁'];
                        $valAn = ['highlight' => $highlight, 'kendala' => $kendala, 'tindak_lanjut' => $tindakLanjut];
                        $labelWajib = count($wajibKol) === 3 ? 'Highlight, Kendala, dan Tindak Lanjut wajib' : 'hanya Highlight yang wajib';
                        ?>
                        <div class="ls-an-box analisa-box" data-key="<?= esc($key) ?>" data-status="<?= $r['status'] ?>" data-wajib="<?= esc(implode(',', $wajibKol)) ?>">
                            <div class="ls-an-h">
                                <span><i class="bi bi-chat-left-text"></i>Analisa <?= esc($bulanLabel) ?></span>
                                <span class="ls-an-state">
                                    <span class="ls-dirty" hidden><i class="bi bi-pencil-fill"></i>belum disimpan</span>
                                    <span class="analisa-flag ls-flag <?= $r['lengkap'] ? 'is-ok' : 'is-draft' ?>" data-state="<?= $r['anState'] ?>">
                                        <?= $r['lengkap'] ? 'Lengkap' : 'Kurang: ' . esc(implode(', ', array_map(fn($k) => $kolomAn[$k], $r['kurang']))) ?>
                                    </span>
                                </span>
                            </div>
                            <div class="ls-an-rule" data-an-rule>
                                <i class="bi bi-info-circle-fill"></i>
                                <span>Program ini <b><?= $statusFrasa[$r['status']] ?></b>: <?= $labelWajib ?> diisi (min. <?= $aturanMin ?> karakter) sebelum laporan bulanan dicetak.<?= count($wajibKol) < 3 ? ' Kendala dan Tindak Lanjut opsional.' : '' ?><?= $arsipAn ? ' <em>Bulan arsip: tidak menghalangi cetak.</em>' : '' ?></span>
                            </div>
                            <?php if ($canEdit): ?>
                            <div class="analisa-fields ls-an-fields" data-source="<?= $srcL ?>" data-id="<?= (int)$p['id'] ?>">
                                <?php foreach ($kolomAn as $kol => $kolLabel): $wajib = in_array($kol, $wajibKol, true); ?>
                                <label class="ls-fld" data-field="<?= $kol ?>" data-req="<?= $wajib ? '1' : '0' ?>">
                                    <span class="ls-fld-h">
                                        <span class="ls-fld-t"><?= $icoAn[$kol] ?> <?= $kolLabel ?></span>
                                        <span class="ls-req <?= $wajib ? 'is-req' : 'is-opt' ?>"><?= $wajib ? 'wajib' : 'opsional' ?></span>
                                    </span>
                                    <small class="ls-fld-hint"><?= esc($hintAn[$kol][0]) ?></small>
                                    <textarea class="analisa-input" name="<?= $kol ?>" rows="2" placeholder="<?= esc($hintAn[$kol][1]) ?>"><?= esc($valAn[$kol]) ?></textarea>
                                    <span class="ls-fld-foot"><span class="ls-cnt" aria-live="off"></span><small class="ls-fld-note" hidden></small></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                            <div class="ls-an-actions">
                                <span class="analisa-status small"></span>
                                <span class="ls-kbd d-none d-md-inline"><kbd>Ctrl</kbd>+<kbd>Enter</kbd> menyimpan</span>
                                <button type="button" class="analisa-save ls-btn ls-btn-primary ls-btn-sm"><i class="bi bi-save"></i>Simpan</button>
                            </div>
                            <?php else: ?>
                            <div class="ls-an-read">
                                <?php if ($highlight): ?><p><span>✅</span> <?= esc($highlight) ?></p><?php endif; ?>
                                <?php if ($kendala): ?><p><span>⚠️</span> <?= esc($kendala) ?></p><?php endif; ?>
                                <?php if ($tindakLanjut): ?><p><span>🔁</span> <?= esc($tindakLanjut) ?></p><?php endif; ?>
                                <?php if ($analisa && ! $highlight && ! $kendala && ! $tindakLanjut): ?><p style="white-space:pre-wrap"><?= esc($analisa) ?></p><?php endif; ?>
                                <?php if (! $r['filled']): ?><p class="ls-dash">Belum ada analisa</p><?php endif; ?>
                                <span class="analisa-status d-none"></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <div class="ls-empty ls-empty-filter" id="lsProgEmpty" hidden><i class="bi bi-funnel"></i><p>Tidak ada program yang cocok dengan filter.</p></div>
        <?php endif; ?>
    </section>

    <!-- E. Grafik -->
    <div class="ls-grid-2">
        <section class="ls-card" aria-labelledby="lsC1T">
            <div class="ls-card-h"><h2 id="lsC1T"><i class="bi bi-people"></i>Member Baru &amp; Aktif — <?= esc($trendYear) ?></h2></div>
            <?php if (empty($allMonthlyTotals)): ?>
            <div class="ls-empty"><i class="bi bi-graph-up"></i><p>Belum ada data realisasi untuk tahun <?= esc($trendYear) ?>.</p></div>
            <?php else: ?>
            <div class="ls-chart"><canvas id="trendMember" role="img" aria-label="Grafik member baru dan aktif per bulan"></canvas></div>
            <?php endif; ?>
        </section>
        <section class="ls-card" aria-labelledby="lsC2T">
            <div class="ls-card-h"><h2 id="lsC2T"><i class="bi bi-ticket-perforated"></i>Voucher Tersebar vs Terpakai — <?= esc($trendYear) ?></h2></div>
            <?php if (empty($allMonthlyTotals)): ?>
            <div class="ls-empty"><i class="bi bi-graph-up"></i><p>Belum ada data realisasi untuk tahun <?= esc($trendYear) ?>.</p></div>
            <?php else: ?>
            <div class="ls-chart"><canvas id="trendVoucher" role="img" aria-label="Grafik voucher tersebar dan terpakai serta tingkat redemption per bulan"></canvas></div>
            <?php endif; ?>
        </section>
    </div>

    <section class="ls-card" aria-labelledby="lsC3T">
        <div class="ls-card-h"><h2 id="lsC3T"><i class="bi bi-calendar3"></i>Realisasi Harian — <?= esc($bulanLabel) ?></h2></div>
        <?php if ($hasChartData): ?>
        <div class="ls-chart is-short"><canvas id="dailyChart" role="img" aria-label="Grafik realisasi harian"></canvas></div>
        <?php else: ?>
        <div class="ls-empty is-compact"><i class="bi bi-bar-chart"></i><p>Belum ada data harian di <?= esc($bulanLabel) ?>.</p></div>
        <?php endif; ?>
    </section>

    <!-- Tabel tren bulanan (dilipat) -->
    <section class="ls-card ls-acc" id="lsTrendAcc">
        <button type="button" class="ls-acc-h" aria-expanded="false" aria-controls="lsTrendBody">
            <span><i class="bi bi-table"></i>Tabel tren bulanan — <?= esc($trendYear) ?></span>
            <i class="bi bi-chevron-down ls-acc-ico"></i>
        </button>
        <div class="ls-acc-b" id="lsTrendBody" hidden>
        <?php if (empty($allMonthlyTotals)): ?>
            <div class="ls-empty is-compact"><p>Belum ada data realisasi.</p></div>
        <?php else: ?>
            <div class="ls-scroll">
            <table class="loy-table is-plain" id="lsTrendTable">
                <thead><tr>
                    <th>Bulan</th><th class="t-r">Member Baru</th><th class="t-r">Member Aktif</th><th class="t-r">Voucher Sebar</th><th class="t-r">Voucher Pakai</th><th class="t-r">Hadiah</th>
                </tr></thead>
                <tbody>
                <?php foreach (array_reverse($allMonthlyTotals) as $row):
                    $isSel = $row['bulan'] === $bulan;
                    $mAktifRow = (int)($row['total_member_aktif'] ?? 0);
                    $hadiahRow = (int)($row['total_hadiah'] ?? 0);
                ?>
                <tr class="<?= $isSel ? 'is-sel' : '' ?>">
                    <td><a href="?bulan=<?= $row['bulan'] ?>"><?= esc($labelBulan($row['bulan'])) ?><?= $isSel ? ' <i class="bi bi-arrow-left-short"></i>' : '' ?></a></td>
                    <td class="t-r num"><?= (int)$row['total_jumlah'] > 0 ? number_format($row['total_jumlah']) : '<span class="ls-dash">—</span>' ?></td>
                    <td class="t-r num"><?= $mAktifRow > 0 ? number_format($mAktifRow) : '<span class="ls-dash">—</span>' ?></td>
                    <td class="t-r num"><?= (int)$row['total_tersebar'] > 0 ? number_format($row['total_tersebar']) : '<span class="ls-dash">—</span>' ?></td>
                    <td class="t-r num"><?= (int)$row['total_terpakai'] > 0 ? number_format($row['total_terpakai']) : '<span class="ls-dash">—</span>' ?></td>
                    <td class="t-r num"><?= $hadiahRow > 0 ? number_format($hadiahRow) : '<span class="ls-dash">—</span>' ?></td>
                </tr>
                <?php endforeach;
                $grandMember = array_sum(array_column($allMonthlyTotals, 'total_jumlah'));
                $grandMemberAktif = array_sum(array_column($allMonthlyTotals, 'total_member_aktif'));
                $grandTersebar = array_sum(array_column($allMonthlyTotals, 'total_tersebar'));
                $grandTerpakai = array_sum(array_column($allMonthlyTotals, 'total_terpakai'));
                $grandHadiah = array_sum(array_column($allMonthlyTotals, 'total_hadiah'));
                ?>
                </tbody>
                <tfoot><tr>
                    <td>Total</td>
                    <td class="t-r num"><?= number_format($grandMember) ?></td>
                    <td class="t-r num"><?= $grandMemberAktif > 0 ? number_format($grandMemberAktif) : '—' ?></td>
                    <td class="t-r num"><?= number_format($grandTersebar) ?></td>
                    <td class="t-r num"><?= number_format($grandTerpakai) ?></td>
                    <td class="t-r num"><?= $grandHadiah > 0 ? number_format($grandHadiah) : '—' ?></td>
                </tr></tfoot>
            </table>
            </div>
        <?php endif; ?>
        </div>
    </section>

    <!-- F. Voucher & Hadiah -->
    <?php if ($hasVouchers || $hasHadiah): ?>
    <section class="ls-card" id="lsItems" aria-labelledby="lsItemsT">
        <div class="ls-card-h">
            <h2 id="lsItemsT"><i class="bi bi-boxes"></i>Voucher &amp; Hadiah — <?= esc($bulanLabel) ?></h2>
            <div class="ls-tabs" role="tablist">
                <?php if ($hasVouchers): ?><button type="button" role="tab" class="is-on" data-tab="v" aria-selected="true"><i class="bi bi-ticket-perforated"></i>Voucher <b><?= count($flatVouchers) ?></b></button><?php endif; ?>
                <?php if ($hasHadiah): ?><button type="button" role="tab" class="<?= $hasVouchers ? '' : 'is-on' ?>" data-tab="h" aria-selected="<?= $hasVouchers ? 'false' : 'true' ?>"><i class="bi bi-gift"></i>Hadiah <b><?= count($flatHadiah) ?></b></button><?php endif; ?>
            </div>
        </div>

        <?php if ($hasVouchers): ?>
        <div class="ls-tabpane" data-pane="v">
            <div class="ls-filters">
                <label class="ls-search"><i class="bi bi-search"></i><input type="search" data-items-search="v" placeholder="Cari voucher / program…" aria-label="Cari voucher" autocomplete="off"></label>
                <button type="button" class="ls-toggle" data-items-flag="v" aria-pressed="false"><i class="bi bi-flag"></i>Bermasalah <b id="lsVFlagN">0</b></button>
                <span class="ls-hint">Redemption = terpakai ÷ tersebar. % terpakai = terpakai ÷ diterbitkan.</span>
            </div>
            <div class="ls-scroll">
            <table class="loy-table" id="lsVTable">
                <thead><tr>
                    <th class="ls-sticky" data-sort="name" aria-sort="none"><button type="button">Voucher</button></th>
                    <th data-sort="src" aria-sort="none"><button type="button">Sumber</button></th>
                    <th class="t-r" data-sort="nilai" data-num aria-sort="none"><button type="button">Nilai</button></th>
                    <th class="t-r" data-sort="terbit" data-num aria-sort="none"><button type="button">Diterbitkan</button></th>
                    <th class="t-r" data-sort="sebar" data-num aria-sort="none"><button type="button">Tersebar</button></th>
                    <th class="t-r" data-sort="pakai" data-num aria-sort="none"><button type="button">Terpakai</button></th>
                    <th data-sort="pctterbit" data-num aria-sort="none"><button type="button">% Terpakai</button></th>
                    <th data-sort="red" data-num aria-sort="none"><button type="button">Redemption</button></th>
                </tr></thead>
                <tbody>
                <?php
                $tDiterbitkan = 0; $tTersebar = 0; $tTerpakai = 0;
                foreach ($flatVouchers as $i => $row):
                    $vi = $row['item']; $vd = $row['vd']; $pr = $row['prog'];
                    $terbit = (int)$vi['total_diterbitkan'];
                    $sebar = (int)$vd['total_tersebar']; $pakai = (int)$vd['total_terpakai'];
                    $pct = $terbit > 0 ? min(100, round($pakai / $terbit * 100, 1)) : 0;
                    $red = $sebar > 0 ? round($pakai / $sebar * 100, 1) : null;
                    $tDiterbitkan += $terbit; $tTersebar += $sebar; $tTerpakai += $pakai;
                ?>
                <tr class="ls-irow" id="irow-v<?= $i ?>" data-search="<?= esc(mb_strtolower($vi['nama_voucher'] . ' ' . ($pr['nama_program'] ?? '') . ' ' . ($pr['event_name'] ?? ''))) ?>"
                    data-flag="0" data-s-name="<?= esc(mb_strtolower($vi['nama_voucher'])) ?>" data-s-src="<?= $row['src'] ?>" data-s-nilai="<?= (float)$vi['nilai_voucher'] ?>"
                    data-s-terbit="<?= $terbit ?>" data-s-sebar="<?= $sebar ?>" data-s-pakai="<?= $pakai ?>" data-s-pctterbit="<?= $pct ?>" data-s-red="<?= $red ?? -1 ?>"
                    data-sebar="<?= $sebar ?>" data-pakai="<?= $pakai ?>">
                    <td class="ls-sticky">
                        <div class="ls-iname"><?= esc($vi['nama_voucher']) ?></div>
                        <?php if (($vi['target_penyerapan'] ?? null) !== null && $vi['target_penyerapan'] !== ''): ?>
                        <div class="ls-sub">Target penyerapan: <?= (float)$vi['target_penyerapan'] ?>%</div><?php endif; ?>
                        <span class="ls-flagtxt" hidden><i class="bi bi-exclamation-triangle-fill"></i>redemption rendah</span>
                    </td>
                    <td>
                        <?php if ($pr): ?>
                        <div class="ls-src"><span class="ls-tag <?= $row['src'] === 'event' ? 'is-evt' : 'is-std' ?>"><?= $row['src'] === 'event' ? 'Event' : 'Standalone' ?></span></div>
                        <div class="ls-sub"><?= esc($pr['nama_program']) ?><?= ($row['src'] === 'event' && ($pr['event_name'] ?? '')) ? ' · ' . esc($pr['event_name']) : '' ?></div>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td class="t-r num ls-muted"><?= $rp($vi['nilai_voucher']) ?></td>
                    <td class="t-r num"><?= number_format($terbit) ?></td>
                    <td class="t-r num"><?= $sebar > 0 ? number_format($sebar) : '<span class="ls-dash">—</span>' ?></td>
                    <td class="t-r num"><?= $pakai > 0 ? number_format($pakai) : '<span class="ls-dash">—</span>' ?></td>
                    <td>
                        <?php if ($pct > 0): ?>
                        <div class="ls-prog is-solo" data-heat style="--h:<?= $hue($pct) ?>"><span class="ls-bar-mini"><i data-c="<?= $pct >= 100 ? 'ok' : ($pct >= 60 ? 'good' : ($pct >= 30 ? 'mid' : 'low')) ?>" style="width:<?= $pct ?>%"></i></span><span class="ls-prog-p"><?= $pct ?>%</span></div>
                        <?php else: ?><span class="ls-dash">—</span><?php endif; ?>
                    </td>
                    <td>
                        <?php if ($red !== null): ?>
                        <div class="ls-prog is-solo" data-heat style="--h:<?= $hue($red) ?>"><span class="ls-bar-mini"><i data-c="<?= $red >= 60 ? 'ok' : ($red >= 30 ? 'mid' : 'low') ?>" style="width:<?= min(100, $red) ?>%"></i></span><span class="ls-prog-p"><?= $red ?>%</span></div>
                        <?php else: ?><span class="ls-dash">—</span><?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot><tr>
                    <td class="ls-sticky">Total</td><td></td><td class="t-r num ls-muted">—</td>
                    <td class="t-r num"><?= number_format($tDiterbitkan) ?></td>
                    <td class="t-r num"><?= $tTersebar > 0 ? number_format($tTersebar) : '—' ?></td>
                    <td class="t-r num"><?= $tTerpakai > 0 ? number_format($tTerpakai) : '—' ?></td>
                    <td></td>
                    <td class="num"><?= $tTersebar > 0 ? round($tTerpakai / $tTersebar * 100, 1) . '%' : '—' ?></td>
                </tr></tfoot>
            </table>
            </div>
            <div class="ls-empty is-compact" data-items-empty="v" hidden><i class="bi bi-funnel"></i><p>Tidak ada voucher yang cocok.</p></div>
        </div>
        <?php endif; ?>

        <?php if ($hasHadiah): ?>
        <div class="ls-tabpane" data-pane="h" <?= $hasVouchers ? 'hidden' : '' ?>>
            <div class="ls-filters">
                <label class="ls-search"><i class="bi bi-search"></i><input type="search" data-items-search="h" placeholder="Cari barang / program…" aria-label="Cari hadiah" autocomplete="off"></label>
                <button type="button" class="ls-toggle" data-items-flag="h" aria-pressed="false"><i class="bi bi-flag"></i>Bermasalah <b id="lsHFlagN">0</b></button>
                <span class="ls-hint">% stok = total dibagikan (semua bulan) ÷ stok.</span>
            </div>
            <div class="ls-scroll">
            <table class="loy-table" id="lsHTable">
                <thead><tr>
                    <th class="ls-sticky" data-sort="name" aria-sort="none"><button type="button">Barang</button></th>
                    <th data-sort="src" aria-sort="none"><button type="button">Sumber</button></th>
                    <th class="t-r" data-sort="stok" data-num aria-sort="none"><button type="button">Stok</button></th>
                    <th class="t-r" data-sort="nilai" data-num aria-sort="none"><button type="button">Nilai</button></th>
                    <th class="t-r" data-sort="bulan" data-num aria-sort="none"><button type="button">Dibagikan</button></th>
                    <th data-sort="pctstok" data-num aria-sort="none"><button type="button">% Stok terbagi</button></th>
                </tr></thead>
                <tbody>
                <?php
                $tStok = 0; $tDibagikan = 0;
                foreach ($flatHadiah as $i => $row):
                    $hi = $row['item']; $pr = $row['prog'];
                    $dibagikan = $row['dibagikan']; $totalAll = $row['totalAll']; $stok = (int)$hi['stok'];
                    $pct = $stok > 0 ? min(100, round($totalAll / $stok * 100, 1)) : 0;
                    $tStok += $stok; $tDibagikan += $dibagikan;
                ?>
                <tr class="ls-irow" id="irow-h<?= $i ?>" data-search="<?= esc(mb_strtolower($hi['nama_hadiah'] . ' ' . ($pr['nama_program'] ?? '') . ' ' . ($pr['event_name'] ?? ''))) ?>"
                    data-flag="0" data-stok="<?= $stok ?>" data-total="<?= $totalAll ?>" data-s-name="<?= esc(mb_strtolower($hi['nama_hadiah'])) ?>" data-s-src="<?= $row['src'] ?>"
                    data-s-stok="<?= $stok ?>" data-s-nilai="<?= (float)$hi['nilai_satuan'] ?>" data-s-bulan="<?= $dibagikan ?>" data-s-pctstok="<?= $stok > 0 ? $pct : -1 ?>">
                    <td class="ls-sticky">
                        <div class="ls-iname"><?= esc($hi['nama_hadiah']) ?></div>
                        <span class="ls-flagtxt" hidden></span>
                    </td>
                    <td>
                        <?php if ($pr): ?>
                        <div class="ls-src"><span class="ls-tag <?= $row['src'] === 'event' ? 'is-evt' : 'is-std' ?>"><?= $row['src'] === 'event' ? 'Event' : 'Standalone' ?></span></div>
                        <div class="ls-sub"><?= esc($pr['nama_program']) ?><?= ($row['src'] === 'event' && ($pr['event_name'] ?? '')) ? ' · ' . esc($pr['event_name']) : '' ?></div>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td class="t-r num"><?= number_format($stok) ?></td>
                    <td class="t-r num ls-muted"><?= $rp($hi['nilai_satuan']) ?></td>
                    <td class="t-r num"><?= $dibagikan > 0 ? number_format($dibagikan) : '<span class="ls-dash">—</span>' ?></td>
                    <td>
                        <?php if ($pct > 0): ?>
                        <div class="ls-prog is-solo is-stok" data-heat style="--h:<?= $hue(100 - $pct) ?>" title="<?= number_format($totalAll) ?> dari <?= number_format($stok) ?> sudah dibagikan">
                            <span class="ls-bar-mini"><i data-c="<?= $pct >= 100 ? 'low' : ($pct >= 80 ? 'mid' : 'ok') ?>" style="width:<?= $pct ?>%"></i></span>
                            <span class="ls-prog-p"><?= $pct ?>%</span>
                        </div>
                        <div class="ls-sub"><?= number_format($totalAll) ?>/<?= number_format($stok) ?> dibagikan</div>
                        <?php else: ?><span class="ls-dash">—</span><?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot><tr>
                    <td class="ls-sticky">Total</td><td></td>
                    <td class="t-r num"><?= number_format($tStok) ?></td>
                    <td class="t-r num ls-muted">—</td>
                    <td class="t-r num"><?= $tDibagikan > 0 ? number_format($tDibagikan) : '—' ?></td>
                    <td></td>
                </tr></tfoot>
            </table>
            </div>
            <div class="ls-empty is-compact" data-items-empty="h" hidden><i class="bi bi-funnel"></i><p>Tidak ada barang yang cocok.</p></div>
        </div>
        <?php endif; ?>
    </section>
    <?php endif; ?>

</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>window.LOYSUM = <?= json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_PARTIAL_OUTPUT_ON_ERROR) ?>;</script>
<script src="<?= base_url('js/loyalty-summary.js') ?>?v=<?= @filemtime(FCPATH . 'js/loyalty-summary.js') ?: 1 ?>"></script>
<?= $this->endSection() ?>
