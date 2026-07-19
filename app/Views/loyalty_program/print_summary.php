<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan Bulanan Loyalty — <?= $bulan ?></title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: Arial, sans-serif; font-size: 11px; color: #111; background: #fff; }

@page { size: A4 landscape; margin: 12mm 14mm 10mm; }
@media print {
    .no-print { display: none !important; }
    body { font-size: 11px; }
}

/* ── Page break saat print ───────────────────────────────────────────
   - thead berulang otomatis di tiap halaman (table-header-group)
   - satu program (baris data + baris analisa) tidak boleh terpotong
   - judul section menempel ke isinya, blok grafik & ttd utuh satu halaman */
thead { display: table-header-group; }
tbody.prog-block { break-inside: avoid; page-break-inside: avoid; }
.sec-title { break-after: avoid; page-break-after: avoid; break-inside: avoid; }
.kpi-row, .chart-panel, .sign-row { break-inside: avoid; page-break-inside: avoid; }

/* ── Header ── */
.doc-header {
    border-bottom: 3px solid #1e293b; padding-bottom: 10px; margin-bottom: 16px;
    display: flex; justify-content: space-between; align-items: flex-end;
}
.doc-header .title { font-size: 18px; font-weight: 700; color: #1e293b; }
.doc-header .sub   { font-size: 13px; color: #475569; margin-top: 2px; }
.doc-header .org   { font-size: 10px; color: #94a3b8; margin-top: 5px; }
.doc-header .meta  { text-align: right; font-size: 10px; color: #64748b; line-height: 1.8; }

/* ── KPI ── */
.kpi-row { display: flex; gap: 10px; margin-bottom: 16px; }
.kpi-box {
    flex: 1; border: 1px solid #e2e8f0; border-radius: 6px;
    padding: 9px 12px; background: #f8fafc;
}
.kpi-label { font-size: 10px; color: #64748b; margin-bottom: 3px; }
.kpi-num   { font-size: 21px; font-weight: 700; line-height: 1.1; }
.kpi-sub   { font-size: 9.5px; color: #94a3b8; margin-top: 2px; }
.kpi-member  { border-color: #bfdbfe; background: #eff6ff; }
.kpi-member .kpi-num  { color: #1d4ed8; }
.kpi-aktif   { border-color: #bbf7d0; background: #f0fdf4; }
.kpi-aktif .kpi-num   { color: #15803d; }
.kpi-sebar   { border-color: #fde68a; background: #fffbeb; }
.kpi-sebar .kpi-num   { color: #b45309; }
.kpi-pakai   { border-color: #fecaca; background: #fef2f2; }
.kpi-pakai .kpi-num   { color: #b91c1c; }
.kpi-hadiah  { border-color: #c4b5fd; background: #f5f3ff; }
.kpi-hadiah .kpi-num  { color: #6d28d9; }

/* ── Section title ── */
.sec-title {
    font-size: 11px; font-weight: 700; color: #f1f5f9; text-transform: uppercase;
    letter-spacing: .4px; background: #1e293b; padding: 5px 10px;
    margin-bottom: 0; border-radius: 4px 4px 0 0;
    display: flex; justify-content: space-between; align-items: center;
}
.sec-title .sec-sub { font-weight: 400; font-size: 9.5px; opacity: .75; }

/* ── Table ── */
.main-table { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
.main-table th {
    background: #334155; color: #f1f5f9; font-size: 10px;
    padding: 5px 7px; border: 1px solid #475569; text-align: left; white-space: nowrap;
}
.main-table th.text-center { text-align: center; }
.main-table td { padding: 5px 7px; border: 1px solid #e2e8f0; font-size: 11px; vertical-align: middle; }
.main-table tbody.prog-block:nth-of-type(even) tr:first-child td { background: #f8fafc; }
.num { text-align: right; font-variant-numeric: tabular-nums; }
.zero { color: #cbd5e1; text-align: right; }

/* source/status pills */
.pill {
    display: inline-block; padding: 1px 7px; border-radius: 3px;
    font-size: 9px; font-weight: 700; border: 1px solid;
}
.pill-standalone { background: #f1f5f9; color: #64748b; border-color: #cbd5e1; }
.pill-event      { background: #ede9fe; color: #5b21b6; border-color: #c4b5fd; }
.pill-active     { background: #f0fdf4; color: #15803d; border-color: #bbf7d0; }
.pill-inactive   { background: #f1f5f9; color: #94a3b8; border-color: #e2e8f0; }
.pill-locked     { background: #fef2f2; color: #b91c1c; border-color: #fecaca; }

/* ── Signature ── */
.sign-row { display: flex; gap: 20px; margin-top: 24px; }
.sign-box {
    flex: 1; border: 1px solid #e2e8f0; border-radius: 6px;
    padding: 9px 12px 48px; text-align: center; font-size: 11px; color: #475569;
}
.sign-box .sign-role { font-weight: 700; color: #1e293b; font-size: 11.5px; margin-top: 3px; }

/* ── Footer ── */
.doc-footer {
    margin-top: 16px; border-top: 1px solid #e2e8f0; padding-top: 6px;
    display: flex; justify-content: space-between; font-size: 9.5px; color: #94a3b8;
}

/* ── Analisa & grafik ── */
.chart-panel {
    display: flex; gap: 10px; margin-bottom: 18px; page-break-inside: avoid;
    border: 1px solid #e2e8f0; border-top: none; border-radius: 0 0 6px 6px; padding: 10px;
}
.insight-box { flex: 0 0 30%; }
.insight-title { font-size: 10.5px; font-weight: 700; color: #1e293b; margin-bottom: 5px; text-transform: uppercase; letter-spacing: .3px; }
.insight-list { margin: 0; padding-left: 14px; }
.insight-list li { font-size: 10.5px; color: #334155; line-height: 1.5; margin-bottom: 5px; }
.chart-box { flex: 1; min-width: 0; }
.chart-title { font-size: 10px; font-weight: 700; color: #475569; margin-bottom: 4px; }
.chart-wrap { height: 165px; position: relative; }

/* ── Print button ── */
.btn-print {
    position: fixed; top: 16px; right: 16px; background: #1e293b; color: #fff;
    border: none; padding: 8px 18px; border-radius: 6px; cursor: pointer;
    font-size: 12px; box-shadow: 0 2px 8px rgba(0,0,0,.2);
}
.btn-print:hover { background: #334155; }
</style>
</head>
<body>

<button class="btn-print no-print" onclick="window.print()">&#128438; Cetak</button>

<?php
$idBulan = ['January'=>'Januari','February'=>'Februari','March'=>'Maret','April'=>'April',
            'May'=>'Mei','June'=>'Juni','July'=>'Juli','August'=>'Agustus',
            'September'=>'September','October'=>'Oktober','November'=>'November','December'=>'Desember'];
$bulanDt    = \DateTime::createFromFormat('Y-m', $bulan);
$bulanLabel = strtr($bulanDt->format('F Y'), $idBulan);

// Hanya program yang relevan dengan bulan terpilih (overlap tanggal / ada aktivitas bulan ini)
$bMonthStart = $bulan . '-01';
$bMonthEnd   = date('Y-m-t', strtotime($bMonthStart));
$programs = array_values(array_filter($programs, function ($p) use ($bMonthStart, $bMonthEnd, $monthlyData, $voucherByProgram, $evoucherByProgram, $hadiahByProgram, $ehadiahByProgram) {
    $isS     = $p['source'] === 'standalone';
    $key     = ($isS ? 's_' : 'e_') . $p['id'];
    $mulai   = $isS ? ($p['tanggal_mulai']   ?? '') : ($p['event_start_date'] ?? '');
    $selesai = $isS ? ($p['tanggal_selesai'] ?? '') : ($p['event_start_date'] ?? '');
    if ($mulai && $mulai <= $bMonthEnd && (empty($selesai) || $selesai >= $bMonthStart)) return true;
    $md = $monthlyData[$key] ?? [];
    if ((int)($md['total_jumlah'] ?? 0) > 0 || (int)($md['total_member_aktif'] ?? 0) > 0) return true;
    $vd = $isS ? ($voucherByProgram[$p['id']] ?? []) : ($evoucherByProgram[$p['id']] ?? []);
    if ((int)($vd['total_tersebar'] ?? 0) > 0 || (int)($vd['total_terpakai'] ?? 0) > 0) return true;
    $h = $isS ? (int)($hadiahByProgram[$p['id']] ?? 0) : (int)($ehadiahByProgram[$p['id']] ?? 0);
    return $h > 0;
}));

$totalProgram = count($programs);
$activeProgram = count(array_filter($programs, fn($p) => $p['status'] === 'active'));

function numF(int $n): string { return $n > 0 ? number_format($n) : '—'; }

$prevDt        = \DateTime::createFromFormat('Y-m', $prevBulan ?? date('Y-m'));
$prevLabel     = $prevDt ? strtr($prevDt->format('M Y'), ['Jan'=>'Jan','May'=>'Mei','Aug'=>'Agu','Oct'=>'Okt','Dec'=>'Des']) : '';
$mallLabel     = ['ewalk' => 'eWalk', 'pentacity' => 'Pentacity', 'both' => 'eWalk & Pentacity'];
$fmtPeriode    = function (?string $a, ?string $b): string {
    if (! $a) return '—';
    $s = date('d M Y', strtotime($a));
    return $b && $b !== $a ? $s . ' – ' . date('d M Y', strtotime($b)) : $s;
};
// Program multi-bulan = periode melintasi lebih dari satu bulan kalender
$isMultiMonth = function (array $p) use ($bulan): bool {
    $isS   = $p['source'] === 'standalone';
    $mulai = $isS ? ($p['tanggal_mulai'] ?? '') : ($p['event_start_date'] ?? '');
    $akhir = $isS ? ($p['tanggal_selesai'] ?? '') : '';
    if (! $mulai) return false;
    return substr($mulai, 0, 7) !== $bulan || ($akhir && substr($akhir, 0, 7) !== substr($mulai, 0, 7));
};

// Separate standalone vs event
$standalone = array_filter($programs, fn($p) => $p['source'] === 'standalone');
$eventProg  = array_filter($programs, fn($p) => $p['source'] === 'event');
?>

<!-- ══ HEADER ══ -->
<div class="doc-header">
    <div>
        <div class="title">Laporan Bulanan — Program Loyalty</div>
        <div class="sub"><?= $bulanLabel ?></div>
        <div class="org">PT. Wulandari Bangun Laksana Tbk. &mdash; IT Department &mdash; Mall Intelligence Center</div>
    </div>
    <div class="meta">
        Dicetak oleh: <?= esc($printedBy) ?><br>
        Tanggal cetak: <?= $printedAt ?><br>
        Total program: <?= $totalProgram ?> &middot; Aktif: <?= $activeProgram ?>
    </div>
</div>

<!-- ══ KPI ══ -->
<div class="kpi-row">
    <div class="kpi-box kpi-member">
        <div class="kpi-label">Member Baru</div>
        <div class="kpi-num"><?= number_format($kpiMember) ?></div>
        <div class="kpi-sub">bulan <?= $bulanLabel ?></div>
    </div>
    <div class="kpi-box kpi-aktif">
        <div class="kpi-label">Member Aktif</div>
        <div class="kpi-num"><?= number_format($kpiMemberAktif) ?></div>
        <div class="kpi-sub">bulan <?= $bulanLabel ?></div>
    </div>
    <div class="kpi-box kpi-sebar">
        <div class="kpi-label">Voucher Tersebar</div>
        <div class="kpi-num"><?= number_format($kpiTersebar) ?></div>
        <div class="kpi-sub">bulan <?= $bulanLabel ?></div>
    </div>
    <div class="kpi-box kpi-pakai">
        <div class="kpi-label">Voucher Terpakai</div>
        <div class="kpi-num"><?= number_format($kpiTerpakai) ?></div>
        <div class="kpi-sub">
            <?php if ($kpiTersebar > 0): ?>
            <?= round($kpiTerpakai / $kpiTersebar * 100) ?>% penyerapan
            <?php else: ?>bulan <?= $bulanLabel ?><?php endif; ?>
        </div>
    </div>
    <div class="kpi-box kpi-hadiah">
        <div class="kpi-label">Hadiah Dibagikan</div>
        <div class="kpi-num"><?= number_format($kpiHadiah) ?></div>
        <div class="kpi-sub">bulan <?= $bulanLabel ?></div>
    </div>
    <div class="kpi-box">
        <div class="kpi-label">Nilai Realisasi vs Budget</div>
        <div class="kpi-num" style="font-size:14px;padding-top:4px">Rp <?= number_format($nilaiRealisasi ?? 0, 0, ',', '.') ?></div>
        <div class="kpi-sub"><?= ($totalBudgetActive ?? 0) > 0
            ? 'budget program aktif Rp ' . number_format($totalBudgetActive, 0, ',', '.') . ' · serapan ' . $serapanPct . '%'
            : 'bulan ' . $bulanLabel ?></div>
    </div>
</div>

<!-- ══ PROGRAM BARU PER MALL ══ -->
<?php $mc = $mallCounts ?? []; $mcTotal = array_sum($mc); ?>
<div class="sec-title"><span>Program Baru Bulan <?= $bulanLabel ?> — per Mall</span>
    <span class="sec-sub"><?= $mcTotal ?> program mulai berjalan bulan ini</span></div>
<table class="main-table">
<thead><tr>
    <th class="text-center" style="width:25%">eWalk</th>
    <th class="text-center" style="width:25%">Pentacity</th>
    <th class="text-center" style="width:25%">Keduanya</th>
    <th class="text-center" style="width:25%">Belum Diisi Mall</th>
</tr></thead>
<tbody><tr>
    <td class="text-center" style="text-align:center;font-size:13px;font-weight:700"><?= (int)($mc['ewalk'] ?? 0) ?></td>
    <td class="text-center" style="text-align:center;font-size:13px;font-weight:700"><?= (int)($mc['pentacity'] ?? 0) ?></td>
    <td class="text-center" style="text-align:center;font-size:13px;font-weight:700"><?= (int)($mc['both'] ?? 0) ?></td>
    <td class="text-center" style="text-align:center;font-size:13px;font-weight:700;color:<?= ($mc['unset'] ?? 0) > 0 ? '#b45309' : '#cbd5e1' ?>"><?= (int)($mc['unset'] ?? 0) ?></td>
</tr></tbody>
</table>

<!-- ══ ANALISA & GRAFIK ══ -->
<div class="sec-title"><span>Analisa &amp; Tren</span>
    <span class="sec-sub">tren 6 bulan terakhir &middot; aktivitas harian <?= $bulanLabel ?></span></div>
<div class="chart-panel">
    <div class="insight-box">
        <div class="insight-title">Ringkasan Analisa</div>
        <ul class="insight-list">
            <?php foreach (($insights ?? []) as $ins): ?>
            <li><?= esc($ins) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <div class="chart-box">
        <div class="chart-title">Tren 6 Bulan — Member &amp; Voucher</div>
        <div class="chart-wrap"><canvas id="chartTrend"></canvas></div>
    </div>
    <div class="chart-box">
        <div class="chart-title">Aktivitas Harian — <?= $bulanLabel ?></div>
        <div class="chart-wrap"><canvas id="chartDaily"></canvas></div>
    </div>
</div>

<!-- ══ PROGRAM TABLE ══ -->
<?php
$sections = [
    ['label' => 'Program Loyalty Standalone', 'rows' => $standalone, 'src' => 'standalone'],
    ['label' => 'Program Loyalty — Support Event', 'rows' => $eventProg, 'src' => 'event'],
];
foreach ($sections as $sec):
    if (empty($sec['rows'])) continue;
    $isSt = $sec['src'] === 'standalone';

    // Section KPI totals
    $secMember  = $secAktif = $secSebar = $secPakai = $secHadiah = 0;
    foreach ($sec['rows'] as $p) {
        $key  = ($isSt ? 's_' : 'e_') . $p['id'];
        $md   = $monthlyData[$key] ?? null;
        $vd   = $isSt ? ($voucherByProgram[$p['id']] ?? null) : ($evoucherByProgram[$p['id']] ?? null);
        $hd   = $isSt ? ($hadiahByProgram[$p['id']] ?? 0) : ($ehadiahByProgram[$p['id']] ?? 0);
        $secMember  += (int)($md['total_jumlah']       ?? 0);
        $secAktif   += (int)($md['total_member_aktif'] ?? 0);
        $secSebar   += (int)($vd['total_tersebar']     ?? 0);
        $secPakai   += (int)($vd['total_terpakai']     ?? 0);
        $secHadiah  += (int)$hd;
    }
?>
<div class="sec-title">
    <span><?= $sec['label'] ?> &nbsp;·&nbsp; <?= count($sec['rows']) ?> program</span>
    <span class="sec-sub">
        Member <?= number_format($secMember) ?> &middot;
        Voucher <?= number_format($secSebar) ?> sebar / <?= number_format($secPakai) ?> pakai &middot;
        Hadiah <?= number_format($secHadiah) ?>
    </span>
</div>
<table class="main-table">
<thead>
    <tr>
        <th style="width:22%">Nama Program</th>
        <th style="width:6%">Status</th>
        <th style="width:12%"><?= $isSt ? 'Periode' : 'Event' ?></th>
        <th class="text-center" style="width:11%">Member Baru</th>
        <th class="text-center" style="width:10%">Member Aktif</th>
        <th class="text-center" style="width:11%">Voucher Sebar</th>
        <th class="text-center" style="width:11%">Voucher Pakai</th>
        <th class="text-center" style="width:6%">% Serap</th>
        <th class="text-center" style="width:11%">Hadiah</th>
    </tr>
</thead>
<?php foreach ($sec['rows'] as $p):
    $key    = ($isSt ? 's_' : 'e_') . $p['id'];
    $md     = $monthlyData[$key] ?? null;
    $vd     = $isSt ? ($voucherByProgram[$p['id']] ?? null) : ($evoucherByProgram[$p['id']] ?? null);
    $hd     = (int)($isSt ? ($hadiahByProgram[$p['id']] ?? 0) : ($ehadiahByProgram[$p['id']] ?? 0));
    $member = (int)($md['total_jumlah']       ?? 0);
    $aktif  = (int)($md['total_member_aktif'] ?? 0);
    $sebar  = (int)($vd['total_tersebar']     ?? 0);
    $pakai  = (int)($vd['total_terpakai']     ?? 0);
    $serap  = $sebar > 0 ? round($pakai / $sebar * 100) : 0;
    $hasData = $member || $aktif || $sebar || $pakai || $hd;

    // Pembanding: bulan lalu + kumulatif s/d bulan ini (untuk program multi-bulan)
    $pmd    = $prevMonthlyData[$key] ?? null;
    $pvd    = $isSt ? ($prevVoucherByProgram[$p['id']] ?? null) : ($prevEvoucherByProgram[$p['id']] ?? null);
    $phd    = (int)($isSt ? ($prevHadiahByProgram[$p['id']] ?? 0) : ($prevEhadiahByProgram[$p['id']] ?? 0));
    $cmd    = $cumulativeData[$key] ?? null;
    $cvd    = $isSt ? ($voucherCumByProgram[$p['id']] ?? null) : ($evoucherCumByProgram[$p['id']] ?? null);
    $chd    = (int)($isSt ? ($hadiahCumByProgram[$p['id']] ?? 0) : ($ehadiahCumByProgram[$p['id']] ?? 0));
    $mPrev  = (int)($pmd['total_jumlah'] ?? 0);      $aPrev = (int)($pmd['total_member_aktif'] ?? 0);
    $sPrev  = (int)($pvd['total_tersebar'] ?? 0);    $pPrev = (int)($pvd['total_terpakai'] ?? 0);
    $mCum   = (int)($cmd['total_jumlah'] ?? 0);      $aCum  = (int)($cmd['total_member_aktif'] ?? 0);
    $sCum   = (int)($cvd['total_tersebar'] ?? 0);    $pCum  = (int)($cvd['total_terpakai'] ?? 0);
    $target = (int)($p['target_peserta'] ?? 0);
    $multi  = $isMultiMonth($p);
    // sub-baris pembanding hanya bila relevan (multi-bulan / ada histori di luar bulan ini)
    $showCmp = $multi || $mPrev || $sPrev || $pPrev || $phd || $mCum > $member || $sCum > $sebar || $chd > $hd;
    $cmp = fn(int $prev, int $cum) => '<div style="font-size:9px;color:#94a3b8;margin-top:1px;white-space:nowrap">'
        . 'lalu ' . number_format($prev) . ' · kum ' . number_format($cum) . '</div>';

    $statusPill = match($p['status']) {
        'active'   => 'pill-active',
        'inactive' => 'pill-inactive',
        'locked'   => 'pill-locked',
        default    => 'pill-inactive',
    };
    $statusLabel = ['active'=>'Aktif','inactive'=>'Nonaktif','locked'=>'Terkunci'][$p['status']] ?? $p['status'];
    $progMall = $isSt ? ($p['mall'] ?? '') : ($p['event_mall'] ?? '');
?>
<tbody class="prog-block">
<tr>
    <td>
        <strong><?= esc($p['nama_program'] ?? ($p['nama'] ?? '—')) ?></strong>
        <?php if ($progMall): ?><div style="font-size:9.5px;color:#64748b"><?= esc($mallLabel[$progMall] ?? ucfirst($progMall)) ?></div><?php endif; ?>
    </td>
    <td><span class="pill <?= $statusPill ?>"><?= $statusLabel ?></span></td>
    <?php if ($isSt): ?>
    <td style="color:#64748b;font-size:10px"><?= $fmtPeriode($p['tanggal_mulai'] ?? null, $p['tanggal_selesai'] ?? null) ?></td>
    <?php else: ?>
    <td style="color:#64748b;font-size:10px"><?= esc($p['event_name'] ?? '—') ?></td>
    <?php endif; ?>
    <td class="<?= $member ? 'num' : 'zero' ?>"><?= numF($member) ?><?php if ($showCmp): ?><?= $cmp($mPrev, $mCum) ?><?php endif; ?>
        <?php if ($target): ?><div style="font-size:9px;color:#64748b;white-space:nowrap">target <?= number_format($target) ?> (<?= $target ? round($mCum / $target * 100) : 0 ?>%)</div><?php endif; ?></td>
    <td class="<?= $aktif  ? 'num' : 'zero' ?>"><?= numF($aktif)  ?><?php if ($showCmp && ($aPrev || $aCum > $aktif)): ?><?= $cmp($aPrev, $aCum) ?><?php endif; ?></td>
    <td class="<?= $sebar  ? 'num' : 'zero' ?>"><?= numF($sebar)  ?><?php if ($showCmp && ($sPrev || $sCum > $sebar)): ?><?= $cmp($sPrev, $sCum) ?><?php endif; ?></td>
    <td class="<?= $pakai  ? 'num' : 'zero' ?>"><?= numF($pakai)  ?><?php if ($showCmp && ($pPrev || $pCum > $pakai)): ?><?= $cmp($pPrev, $pCum) ?><?php endif; ?></td>
    <td class="<?= $serap  ? 'num' : 'zero' ?>"><?= $sebar ? $serap.'%' : '—' ?></td>
    <td class="<?= $hd     ? 'num' : 'zero' ?>"><?= numF($hd)     ?><?php if ($showCmp && ($phd || $chd > $hd)): ?><?= $cmp($phd, $chd) ?><?php endif; ?></td>
</tr>
<?php
$analisaData  = $analisaMap[$key] ?? [];
$highlight    = $analisaData['highlight']     ?? '';
$kendala      = $analisaData['kendala']       ?? '';
$tindakLanjut = $analisaData['tindak_lanjut'] ?? '';
$analisa      = $analisaData['analisa']       ?? '';
$hasAnalisa   = $analisa !== '' || $highlight !== '' || $kendala !== '' || $tindakLanjut !== '';
?>
<tr class="analisa-row">
    <td colspan="9" style="background:#f8fafc;font-size:10.5px;color:#334155;padding:4px 8px;border-top:none">
        <strong style="color:#0f172a">Analisa:</strong>
        <?php if ($hasAnalisa): ?>
        <div style="font-size:10.5px">
            <?php if ($highlight): ?><div class="mb-1"><strong>Highlight:</strong> <?= esc($highlight) ?></div><?php endif; ?>
            <?php if ($kendala): ?><div class="mb-1"><strong>Kendala:</strong> <?= esc($kendala) ?></div><?php endif; ?>
            <?php if ($tindakLanjut): ?><div><strong>Tindak Lanjut:</strong> <?= esc($tindakLanjut) ?></div><?php endif; ?>
            <?php if ($analisa && !$highlight && !$kendala && !$tindakLanjut): ?><div><?= esc($analisa) ?></div><?php endif; ?>
        </div>
        <?php else: ?>
        <em class="text-muted">—</em>
        <?php endif; ?>
    </td>
</tr>
</tbody>
<?php endforeach; ?>
</table>
<?php endforeach; ?>

<div style="font-size:9.5px;color:#94a3b8;margin:-12px 0 14px">
    Keterangan: <em>lalu</em> = realisasi bulan sebelumnya (<?= esc($prevLabel) ?>) · <em>kum</em> = kumulatif sejak program dimulai s/d <?= $bulanLabel ?> — ditampilkan untuk program yang berjalan lebih dari satu bulan.
</div>

<!-- ══ TANDA TANGAN ══ -->
<?php
$sg = $signatories ?? [];
$signSlot = function (?array $s) {
    if ($s) {
        return '<div style="height:42px"></div><span class="sign-role" style="text-decoration:underline">' . esc($s['nama']) . '</span>'
             . '<div style="font-size:8.5px;color:#64748b;margin-top:2px">' . esc($s['jabatan']) . '</div>';
    }
    return '<div style="height:42px"></div><span class="sign-role">( ……………………………… )</span>';
};
?>
<div class="sign-row">
    <div class="sign-box" style="padding-bottom:10px">
        Disusun oleh
        <?= $signSlot($sg['disusun'] ?? null) ?>
    </div>
    <div class="sign-box" style="padding-bottom:10px">
        Diperiksa oleh
        <?= $signSlot($sg['diperiksa'] ?? null) ?>
    </div>
    <div class="sign-box" style="padding-bottom:10px">
        Mengetahui
        <?= $signSlot($sg['mengetahui'] ?? null) ?>
    </div>
</div>

<!-- ══ FOOTER ══ -->
<div class="doc-footer">
    <span>Mall Intelligence Center &mdash; IT Department PT. Wulandari Bangun Laksana Tbk.</span>
    <span>Digenerate otomatis &mdash; <?= $printedAt ?></span>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Palet tervalidasi CVD (sama dgn dashboard Progress Report, surface terang)
const C = { blue: '#2a78d6', green: '#1baf7a', amber: '#eda100', red: '#d03b3b', gray: '#a3a29b' };
const ink  = 'rgba(51,65,85,.75)';
const grid = 'rgba(0,0,0,.06)';
Chart.defaults.animation = false;           // aman untuk window.print()
Chart.defaults.devicePixelRatio = 2;

const baseOpts = {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { position: 'bottom', labels: { color: ink, usePointStyle: true, pointStyle: 'circle', boxWidth: 7, boxHeight: 7, font: { size: 9.5 } } } },
    scales: {
        x: { ticks: { color: ink, font: { size: 9.5 } }, grid: { display: false } },
        y: { ticks: { color: ink, font: { size: 9.5 }, precision: 0 }, grid: { color: grid }, beginAtZero: true },
    },
};
const barStyle = { borderColor: '#ffffff', borderWidth: 1, borderRadius: 3, borderSkipped: false };

// ── Tren 6 bulan ──
const trend = <?= json_encode($trendMonths ?? []) ?>;
const idShort = { '01':'Jan','02':'Feb','03':'Mar','04':'Apr','05':'Mei','06':'Jun','07':'Jul','08':'Agu','09':'Sep','10':'Okt','11':'Nov','12':'Des' };
const mLabel  = m => idShort[m.slice(5)] + ' ' + m.slice(2, 4);
new Chart(document.getElementById('chartTrend'), {
    type: 'bar',
    data: {
        labels: trend.map(t => mLabel(t.bulan)),
        datasets: [
            { label: 'Member Baru',     data: trend.map(t => t.total_jumlah),   backgroundColor: C.blue,  ...barStyle },
            { label: 'Voucher Terpakai', data: trend.map(t => t.total_terpakai), backgroundColor: C.green, ...barStyle },
            { label: 'Hadiah',          data: trend.map(t => t.total_hadiah),   backgroundColor: C.amber, ...barStyle },
        ],
    },
    options: baseOpts,
});

// ── Aktivitas harian bulan terpilih ──
const dMember   = <?= json_encode($dailyMember   ?? []) ?>;
const dTersebar = <?= json_encode($dailyTersebar ?? []) ?>;
const dTerpakai = <?= json_encode($dailyTerpakai ?? []) ?>;
new Chart(document.getElementById('chartDaily'), {
    type: 'bar',
    data: {
        labels: dMember.map((_, i) => String(i + 1).padStart(2, '0')),
        datasets: [
            { label: 'Member Baru',      data: dMember,   backgroundColor: C.blue,  ...barStyle, stack: 's' },
            { label: 'Voucher Terpakai', data: dTerpakai, backgroundColor: C.green, ...barStyle, stack: 's' },
        ],
    },
    options: { ...baseOpts, scales: { ...baseOpts.scales,
        x: { ...baseOpts.scales.x, stacked: true, ticks: { ...baseOpts.scales.x.ticks, autoSkip: true, maxTicksLimit: 16 } },
        y: { ...baseOpts.scales.y, stacked: true } } },
});
</script>

</body>
</html>
