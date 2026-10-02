<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan Bulanan Loyalty — <?= $bulan ?></title>
<?= $this->include('_laporan/_style') ?>
<style>
/* Hanya aturan KHUSUS laporan ini — dasar bersama dari _laporan/_style.php */
/* Kepala kolom angka rata kanan (th.num kalah spesifik dari .main-table th). */
.main-table th.num { text-align: right; }
.kpi-num.kpi-rp { font-size: 16px; padding-top: 3px; }
.deret-angka b.redup { color: #cbd5e1; }

/* Satu program = satu blok tbody (baris angka + baris analisa), tak terpotong halaman. */
tbody.prog-block { break-inside: avoid; page-break-inside: avoid; }
.main-table tbody.prog-block tr td { background: #fff; }
.main-table tbody.prog-block.genap tr td { background: var(--zebra); }
/* Lebar kolom tetap (colgroup) supaya tabel ekor sejajar dengan tabel induknya. */
.main-table.prog-tabel { table-layout: fixed; }
.main-table.prog-tabel.ada-ekor { margin-bottom: 0; }
.main-table.tabel-ekor { margin-top: 0; }
.main-table tbody.prog-block tr:last-child td { border-bottom: 1px solid var(--garis); }
.main-table tbody.prog-block tr.analisa-row td { border-bottom: 1px solid var(--garis); }
.main-table tbody.prog-block tr:has(+ tr.analisa-row) td { border-bottom: 0; }
.prog-nama strong { display: block; }
.prog-mall { font-size: 9px; color: var(--redup); margin-top: 1px; }
.prog-nama .lencana { margin-top: 3px; }
.periode { color: var(--redup); font-size: 9.5px; white-space: nowrap; }
.banding { font-size: 8.5px; color: var(--redup2); margin-top: 1px; white-space: nowrap; }
.target  { font-size: 8.5px; color: var(--redup); white-space: nowrap; }

/* Baris analisa: label sebagai lencana, isi di sebelahnya. */
.main-table tr.analisa-row td { padding: 3px 8px 7px 8px; }
.analisa-isi { display: grid; grid-template-columns: auto 1fr; gap: 3px 8px; align-items: baseline;
    padding: 6px 9px; border-radius: 6px; background: #fbf8ef; border-left: 3px solid var(--emas); font-size: 9.5px; color: var(--teks); }
.analisa-isi .lencana { justify-self: start; }
.keterangan { font-size: 9px; color: var(--redup2); margin: -10px 0 0; }
.keterangan em { color: var(--redup); }
.penutup { break-inside: avoid; page-break-inside: avoid; }
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

// Angka gaya Indonesia (titik ribuan).
$nf   = fn($n): string => number_format((float)$n, 0, ',', '.');
$numF = fn(int $n): string => $n > 0 ? number_format($n, 0, ',', '.') : '—';
$pctF = fn($n): string => rtrim(rtrim(number_format((float)$n, 1, ',', '.'), '0'), ',');

$blnPendek     = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
$prevDt        = \DateTime::createFromFormat('Y-m', $prevBulan ?? date('Y-m'));
$prevLabel     = $prevDt ? $blnPendek[(int)$prevDt->format('n') - 1] . ' ' . $prevDt->format('Y') : '';
$mallLabel     = ['ewalk' => 'eWalk', 'pentacity' => 'Pentacity', 'both' => 'eWalk & Pentacity'];
// Periode ringkas berbulan Indonesia: "01 Jul – 30 Sep 2026" (tahun sekali bila sama).
$fmtPeriode    = function (?string $a, ?string $b) use ($blnPendek): string {
    if (! $a) return '—';
    $ta = strtotime($a);
    $tgl = fn(int $t, bool $th = true) => date('d', $t) . ' ' . $blnPendek[(int)date('n', $t) - 1] . ($th ? ' ' . date('Y', $t) : '');
    if (! $b || $b === $a) return $tgl($ta);
    $tb = strtotime($b);
    return $tgl($ta, date('Y', $ta) !== date('Y', $tb)) . ' – ' . $tgl($tb);
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
        <div class="kpi-num"><?= $nf($kpiMember) ?></div>
        <div class="kpi-sub">bulan <?= $bulanLabel ?></div>
    </div>
    <div class="kpi-box kpi-aktif">
        <div class="kpi-label">Member Aktif</div>
        <div class="kpi-num"><?= $nf($kpiMemberAktif) ?></div>
        <div class="kpi-sub">bulan <?= $bulanLabel ?></div>
    </div>
    <div class="kpi-box kpi-sebar">
        <div class="kpi-label">Voucher Tersebar</div>
        <div class="kpi-num"><?= $nf($kpiTersebar) ?></div>
        <div class="kpi-sub">bulan <?= $bulanLabel ?></div>
    </div>
    <div class="kpi-box kpi-pakai">
        <div class="kpi-label">Voucher Terpakai</div>
        <div class="kpi-num"><?= $nf($kpiTerpakai) ?></div>
        <div class="kpi-sub">
            <?php if ($kpiTersebar > 0): ?>
            <?= round($kpiTerpakai / $kpiTersebar * 100) ?>% penyerapan
            <?php else: ?>bulan <?= $bulanLabel ?><?php endif; ?>
        </div>
    </div>
    <div class="kpi-box kpi-hadiah">
        <div class="kpi-label">Hadiah Dibagikan</div>
        <div class="kpi-num"><?= $nf($kpiHadiah) ?></div>
        <div class="kpi-sub">bulan <?= $bulanLabel ?></div>
    </div>
    <div class="kpi-box kpi-gold">
        <div class="kpi-label">Nilai Realisasi vs Budget</div>
        <div class="kpi-num kpi-rp">Rp <?= $nf($nilaiRealisasi ?? 0) ?></div>
        <div class="kpi-sub"><?= ($totalBudgetActive ?? 0) > 0
            ? 'budget program aktif Rp ' . $nf($totalBudgetActive) . ' · serapan ' . $pctF($serapanPct) . '%'
            : 'bulan ' . $bulanLabel ?></div>
    </div>
</div>

<!-- ══ PROGRAM BARU PER MALL ══ -->
<?php $mc = $mallCounts ?? []; $mcTotal = array_sum($mc); $mcUnset = (int)($mc['unset'] ?? 0); ?>
<div class="sec-title"><span>Program Baru Bulan <?= $bulanLabel ?> — per Mall</span>
    <span class="sec-sub"><?= $mcTotal ?> program mulai berjalan bulan ini</span></div>
<div class="deret-angka">
    <div><span>eWalk</span><b><?= (int)($mc['ewalk'] ?? 0) ?></b></div>
    <div><span>Pentacity</span><b><?= (int)($mc['pentacity'] ?? 0) ?></b></div>
    <div><span>Keduanya</span><b><?= (int)($mc['both'] ?? 0) ?></b></div>
    <div><span>Belum Diisi Mall</span><b class="<?= $mcUnset > 0 ? 'waspada' : 'redup' ?>"><?= $mcUnset ?></b></div>
</div>

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
// Program terakhir laporan dipisah ke tabel "ekor" di dalam .penutup bersama keterangan
// + tanda tangan, supaya tanda tangan tak pernah terdorong sendirian ke halaman baru.
$secTerakhir = null;
foreach ($sections as $si => $sec) if (! empty($sec['rows'])) $secTerakhir = $si;
$penutupDibuka = false;
$kolom = '<colgroup><col style="width:25%"><col style="width:7%"><col style="width:14%"><col style="width:10%"><col style="width:9%"><col style="width:9%"><col style="width:9%"><col style="width:7%"><col style="width:10%"></colgroup>';
foreach ($sections as $si => $sec):
    if (empty($sec['rows'])) continue;
    $isSt = $sec['src'] === 'standalone';
    $sec['rows'] = array_values($sec['rows']);
    $nBaris  = count($sec['rows']);
    $adaEkor = $si === $secTerakhir && $nBaris > 1;

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
        Member <?= $nf($secMember) ?> &middot;
        Voucher <?= $nf($secSebar) ?> sebar / <?= $nf($secPakai) ?> pakai &middot;
        Hadiah <?= $nf($secHadiah) ?>
    </span>
</div>
<table class="main-table prog-tabel<?= $adaEkor ? ' ada-ekor' : '' ?>">
<?= $kolom ?>
<thead>
    <tr>
        <th>Nama Program</th>
        <th>Status</th>
        <th><?= $isSt ? 'Periode' : 'Event' ?></th>
        <th class="num">Member Baru</th>
        <th class="num">Member Aktif</th>
        <th class="num">Voucher Sebar</th>
        <th class="num">Voucher Pakai</th>
        <th class="num">% Serap</th>
        <th class="num">Hadiah</th>
    </tr>
</thead>
<?php foreach ($sec['rows'] as $ri => $p):
    if ($adaEkor && $ri === $nBaris - 1) {
        echo '</table><div class="penutup"><table class="main-table prog-tabel tabel-ekor">' . $kolom;
        $penutupDibuka = true;
    }
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
    $cmp = fn(int $prev, int $cum) => '<div class="banding">lalu ' . $nf($prev) . ' · kum ' . $nf($cum) . '</div>';

    $statusCls = ['active' => 'baik', 'inactive' => 'netral', 'locked' => 'buruk'][$p['status']] ?? 'netral';
    $statusLabel = ['active'=>'Aktif','inactive'=>'Nonaktif','locked'=>'Terkunci'][$p['status']] ?? $p['status'];
    $progMall = $isSt ? ($p['mall'] ?? '') : ($p['event_mall'] ?? '');

    $analisaData  = $analisaMap[$key] ?? [];
    $highlight    = $analisaData['highlight']     ?? '';
    $kendala      = $analisaData['kendala']       ?? '';
    $tindakLanjut = $analisaData['tindak_lanjut'] ?? '';
    $analisa      = $analisaData['analisa']       ?? '';
    $hasAnalisa   = $analisa !== '' || $highlight !== '' || $kendala !== '' || $tindakLanjut !== '';
?>
<tbody class="prog-block<?= $ri % 2 ? ' genap' : '' ?>">
<tr>
    <td class="prog-nama">
        <strong><?= esc($p['nama_program'] ?? ($p['nama'] ?? '—')) ?></strong>
        <?php if ($progMall): ?><div class="prog-mall"><?= esc($mallLabel[$progMall] ?? ucfirst($progMall)) ?></div><?php endif; ?>
        <?php if (! $hasAnalisa): ?><span class="lencana netral">Analisa belum diisi</span><?php endif; ?>
    </td>
    <td><span class="lencana <?= $statusCls ?>"><?= esc($statusLabel) ?></span></td>
    <?php if ($isSt): ?>
    <td class="periode"><?= $fmtPeriode($p['tanggal_mulai'] ?? null, $p['tanggal_selesai'] ?? null) ?></td>
    <?php else: ?>
    <td class="periode" style="white-space:normal"><?= esc($p['event_name'] ?? '—') ?></td>
    <?php endif; ?>
    <td class="<?= $member ? 'num' : 'zero' ?>"><?= $numF($member) ?><?php if ($showCmp): ?><?= $cmp($mPrev, $mCum) ?><?php endif; ?>
        <?php if ($target): ?><div class="target">target <?= $nf($target) ?> (<?= round($mCum / $target * 100) ?>%)</div><?php endif; ?></td>
    <td class="<?= $aktif  ? 'num' : 'zero' ?>"><?= $numF($aktif)  ?><?php if ($showCmp && ($aPrev || $aCum > $aktif)): ?><?= $cmp($aPrev, $aCum) ?><?php endif; ?></td>
    <td class="<?= $sebar  ? 'num' : 'zero' ?>"><?= $numF($sebar)  ?><?php if ($showCmp && ($sPrev || $sCum > $sebar)): ?><?= $cmp($sPrev, $sCum) ?><?php endif; ?></td>
    <td class="<?= $pakai  ? 'num' : 'zero' ?>"><?= $numF($pakai)  ?><?php if ($showCmp && ($pPrev || $pCum > $pakai)): ?><?= $cmp($pPrev, $pCum) ?><?php endif; ?></td>
    <td class="<?= $serap  ? 'num' : 'zero' ?>"><?= $sebar ? $serap . '%' : '—' ?></td>
    <td class="<?= $hd     ? 'num' : 'zero' ?>"><?= $numF($hd)     ?><?php if ($showCmp && ($phd || $chd > $hd)): ?><?= $cmp($phd, $chd) ?><?php endif; ?></td>
</tr>
<?php if ($hasAnalisa): ?>
<tr class="analisa-row">
    <td colspan="9">
        <div class="analisa-isi">
            <?php if ($highlight): ?><span class="lencana baik">Highlight</span><div><?= esc($highlight) ?></div><?php endif; ?>
            <?php if ($kendala): ?><span class="lencana waspada">Kendala</span><div><?= esc($kendala) ?></div><?php endif; ?>
            <?php if ($tindakLanjut): ?><span class="lencana info">Tindak Lanjut</span><div><?= esc($tindakLanjut) ?></div><?php endif; ?>
            <?php if ($analisa && !$highlight && !$kendala && !$tindakLanjut): ?><span class="lencana emas">Analisa</span><div><?= esc($analisa) ?></div><?php endif; ?>
        </div>
    </td>
</tr>
<?php endif; ?>
</tbody>
<?php endforeach; ?>
</table>
<?php endforeach; ?>

<?php if (! $penutupDibuka): ?><div class="penutup"><?php endif; ?>
<?php if ($totalProgram === 0): ?>
<div class="sec-title"><span>Rincian Program Loyalty</span></div>
<div class="catatan info">Tidak ada program loyalty yang berjalan atau mencatat aktivitas pada <?= $bulanLabel ?>.</div>
<?php else: ?>
<div class="keterangan">
    Keterangan: <em>lalu</em> = realisasi bulan sebelumnya (<?= esc($prevLabel) ?>) · <em>kum</em> = kumulatif sejak program dimulai s/d <?= $bulanLabel ?> — ditampilkan untuk program yang berjalan lebih dari satu bulan.
</div>
<?php endif; ?>

<!-- ══ TANDA TANGAN ══ -->
<?= $this->include('_laporan/_ttd') ?>

<!-- ══ FOOTER ══ -->
<div class="doc-footer">
    <span>Mall Intelligence Center &mdash; IT Department PT. Wulandari Bangun Laksana Tbk.</span>
    <span>Digenerate otomatis &mdash; <?= $printedAt ?></span>
</div>
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
