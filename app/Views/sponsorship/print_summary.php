<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan Bulanan Sponsorship — <?= $bulan ?></title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: Arial, sans-serif; font-size: 11px; color: #111; background: #fff; }

@page { size: A4 landscape; margin: 12mm 14mm 10mm; }
@media print {
    .no-print { display: none !important; }
    body { font-size: 11px; }
}

/* ── Page break saat print ── */
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
.kpi-num   { font-size: 15px; font-weight: 700; line-height: 1.15; padding-top: 3px; }
.kpi-sub   { font-size: 9.5px; color: #94a3b8; margin-top: 2px; }
.kpi-deal    { border-color: #bfdbfe; background: #eff6ff; }
.kpi-deal .kpi-num    { color: #1d4ed8; }
.kpi-komit   { border-color: #c4b5fd; background: #f5f3ff; }
.kpi-komit .kpi-num   { color: #6d28d9; }
.kpi-real    { border-color: #bbf7d0; background: #f0fdf4; }
.kpi-real .kpi-num    { color: #15803d; }
.kpi-kum     { border-color: #fde68a; background: #fffbeb; }
.kpi-kum .kpi-num     { color: #b45309; }

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
.subnote { font-size: 9px; color: #94a3b8; margin-top: 1px; white-space: nowrap; }

/* pills */
.pill {
    display: inline-block; padding: 1px 7px; border-radius: 3px;
    font-size: 9px; font-weight: 700; border: 1px solid;
}
.pill-active   { background: #f0fdf4; color: #15803d; border-color: #bbf7d0; }
.pill-inactive { background: #f1f5f9; color: #94a3b8; border-color: #e2e8f0; }
.pill-locked   { background: #fef2f2; color: #b91c1c; border-color: #fecaca; }

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

/* ── Signature ── */
.sign-row { display: flex; gap: 20px; margin-top: 24px; }
.sign-box {
    flex: 1; border: 1px solid #e2e8f0; border-radius: 6px;
    padding: 9px 12px 10px; text-align: center; font-size: 11px; color: #475569;
}
.sign-box .sign-role { font-weight: 700; color: #1e293b; font-size: 11.5px; margin-top: 3px; }

/* ── Footer ── */
.doc-footer {
    margin-top: 16px; border-top: 1px solid #e2e8f0; padding-top: 6px;
    display: flex; justify-content: space-between; font-size: 9.5px; color: #94a3b8;
}

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
$prevDt     = \DateTime::createFromFormat('Y-m', $prevBulan);
$prevLabel  = $prevDt ? strtr($prevDt->format('F Y'), $idBulan) : '';

$mallLabel = ['ewalk' => 'eWalk', 'pentacity' => 'Pentacity', 'both' => 'eWalk & Pentacity'];
$rp        = fn($n) => $n > 0 ? 'Rp ' . number_format($n, 0, ',', '.') : '—';
$fmtPeriode = function (?string $a, ?string $b): string {
    if (! $a) return '—';
    $s = date('d M Y', strtotime($a));
    return $b && $b !== $a ? $s . ' – ' . date('d M Y', strtotime($b)) : $s;
};

$bMonthStart = $bulan . '-01';
$bMonthEnd   = date('Y-m-t', strtotime($bMonthStart));

// Program standalone yang relevan bulan ini: periode overlap ATAU ada penerimaan bulan ini
$programs = array_values(array_filter($programs, function ($p) use ($bMonthStart, $bMonthEnd, $monthlyReal) {
    $mulai   = $p['tanggal_mulai']   ?? '';
    $selesai = $p['tanggal_selesai'] ?? '';
    if ($mulai && $mulai <= $bMonthEnd && (empty($selesai) || $selesai >= $bMonthStart)) return true;
    if (! $mulai && $p['status'] === 'active') return true;
    return (int)($monthlyReal[$p['id']] ?? 0) > 0;
}));

// Event yang relevan bulan ini: mulai bulan ini ATAU ada penerimaan bulan ini
$eventAggs = array_values(array_filter($eventAggs, function ($e) use ($bulan, $evMonthly) {
    if (substr((string)($e['event_start_date'] ?? ''), 0, 7) === $bulan) return true;
    return (int)($evMonthly[$e['event_id']] ?? 0) > 0;
}));
?>

<!-- ══ HEADER ══ -->
<div class="doc-header">
    <div>
        <div class="title">Laporan Bulanan — Sponsorship</div>
        <div class="sub"><?= $bulanLabel ?></div>
        <div class="org">PT. Wulandari Bangun Laksana Tbk. &mdash; IT Department &mdash; Mall Intelligence Center</div>
    </div>
    <div class="meta">
        Dicetak oleh: <?= esc($printedBy) ?><br>
        Tanggal cetak: <?= $printedAt ?><br>
        Program standalone: <?= count($programs) ?> &middot; Event: <?= count($eventAggs) ?>
    </div>
</div>

<!-- ══ KPI ══ -->
<div class="kpi-row">
    <div class="kpi-box kpi-deal">
        <div class="kpi-label">Sponsor Deal</div>
        <div class="kpi-num" style="font-size:21px"><?= number_format($kpiSponsorDeal) ?></div>
        <div class="kpi-sub">terkonfirmasi + lunas (semua program)</div>
    </div>
    <div class="kpi-box kpi-komit">
        <div class="kpi-label">Nilai Komitmen Deal</div>
        <div class="kpi-num"><?= $rp($kpiKomitmen) ?></div>
        <div class="kpi-sub">cash + barang, standalone &amp; event</div>
    </div>
    <div class="kpi-box kpi-real">
        <div class="kpi-label">Penerimaan Bulan Ini</div>
        <div class="kpi-num"><?= $rp($kpiRealisasi) ?></div>
        <div class="kpi-sub">bulan <?= $bulanLabel ?></div>
    </div>
    <div class="kpi-box kpi-kum">
        <div class="kpi-label">Penerimaan Kumulatif</div>
        <div class="kpi-num"><?= $rp($kpiKumulatif) ?></div>
        <div class="kpi-sub">s/d <?= $bulanLabel ?></div>
    </div>
    <div class="kpi-box">
        <div class="kpi-label">Capaian vs Target</div>
        <div class="kpi-num" style="font-size:21px"><?= $targetNilaiAktif > 0 ? $capaianPct . '%' : '—' ?></div>
        <div class="kpi-sub"><?= $targetNilaiAktif > 0 ? 'target program aktif ' . $rp($targetNilaiAktif) : 'belum ada target nilai' ?></div>
    </div>
    <div class="kpi-box">
        <div class="kpi-label">Pipeline Berjalan</div>
        <div class="kpi-num" style="font-size:21px"><?= $pipelineTotal['prospek'] + $pipelineTotal['negosiasi'] ?></div>
        <div class="kpi-sub"><?= $pipelineTotal['prospek'] ?> prospek &middot; <?= $pipelineTotal['negosiasi'] ?> negosiasi &middot; <?= $pipelineTotal['batal'] ?> batal</div>
    </div>
</div>

<!-- ══ PROGRAM BARU PER MALL ══ -->
<?php $mc = $mallCounts ?? []; $mcTotal = array_sum($mc); ?>
<div class="sec-title"><span>Program &amp; Event Baru Bulan <?= $bulanLabel ?> — per Mall</span>
    <span class="sec-sub"><?= $mcTotal ?> program/event mulai berjalan bulan ini</span></div>
<table class="main-table">
<thead><tr>
    <th class="text-center" style="width:25%">eWalk</th>
    <th class="text-center" style="width:25%">Pentacity</th>
    <th class="text-center" style="width:25%">Keduanya</th>
    <th class="text-center" style="width:25%">Belum Diisi Mall</th>
</tr></thead>
<tbody><tr>
    <td style="text-align:center;font-size:13px;font-weight:700"><?= (int)($mc['ewalk'] ?? 0) ?></td>
    <td style="text-align:center;font-size:13px;font-weight:700"><?= (int)($mc['pentacity'] ?? 0) ?></td>
    <td style="text-align:center;font-size:13px;font-weight:700"><?= (int)($mc['both'] ?? 0) ?></td>
    <td style="text-align:center;font-size:13px;font-weight:700;color:<?= ($mc['unset'] ?? 0) > 0 ? '#b45309' : '#cbd5e1' ?>"><?= (int)($mc['unset'] ?? 0) ?></td>
</tr></tbody>
</table>

<!-- ══ ANALISA & GRAFIK ══ -->
<div class="sec-title"><span>Analisa &amp; Tren</span>
    <span class="sec-sub">tren penerimaan 6 bulan terakhir &middot; harian <?= $bulanLabel ?></span></div>
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
        <div class="chart-title">Tren Penerimaan — 6 Bulan Terakhir (Rp)</div>
        <div class="chart-wrap"><canvas id="chartTrend"></canvas></div>
    </div>
    <div class="chart-box">
        <div class="chart-title">Penerimaan Harian — <?= $bulanLabel ?> (Rp)</div>
        <div class="chart-wrap"><canvas id="chartDaily"></canvas></div>
    </div>
</div>

<!-- ══ PROGRAM STANDALONE ══ -->
<?php if (! empty($programs)): ?>
<div class="sec-title">
    <span>Program Sponsorship &nbsp;&middot;&nbsp; <?= count($programs) ?> program</span>
    <span class="sec-sub">komitmen = deal terkonfirmasi + lunas &middot; penerimaan = realisasi pembayaran masuk</span>
</div>
<table class="main-table">
<thead>
    <tr>
        <th style="width:23%">Nama Program</th>
        <th style="width:6%">Status</th>
        <th style="width:13%">Periode</th>
        <th class="text-center" style="width:11%">Sponsor</th>
        <th class="text-center" style="width:14%">Komitmen Deal</th>
        <th class="text-center" style="width:14%">Penerimaan Bulan Ini</th>
        <th class="text-center" style="width:9%">Capaian Target</th>
    </tr>
</thead>
<?php foreach ($programs as $p):
    $pid  = (int)$p['id'];
    $com  = $committedMap[$pid] ?? [];
    $pipe = $pipelineMap[$pid]  ?? [];
    $now  = (int)($monthlyReal[$pid] ?? 0);
    $prev = (int)($prevReal[$pid] ?? 0);
    $cum  = (int)($cumReal[$pid] ?? 0);
    $target = (int)($p['target_nilai'] ?? 0);
    $tSponsor = (int)($p['target_sponsor'] ?? 0);
    $dealCount = (int)($com['total_sponsor'] ?? 0);

    $statusPill  = match($p['status']) { 'active' => 'pill-active', default => 'pill-inactive' };
    $statusLabel = ['active'=>'Aktif','inactive'=>'Nonaktif'][$p['status']] ?? $p['status'];
    if (! empty($p['locked'])) { $statusPill = 'pill-locked'; $statusLabel = 'Terkunci'; }

    $pipeParts = [];
    foreach (['prospek' => 'P', 'negosiasi' => 'N', 'terkonfirmasi' => 'K', 'lunas' => 'L', 'batal' => 'B'] as $st => $ab) {
        if (! empty($pipe[$st])) $pipeParts[] = $ab . ':' . $pipe[$st];
    }
?>
<tbody class="prog-block">
<tr>
    <td>
        <strong><?= esc($p['nama_program']) ?></strong>
        <?php if (! empty($p['mall'])): ?><div style="font-size:9.5px;color:#64748b"><?= esc($mallLabel[$p['mall']] ?? ucfirst($p['mall'])) ?></div><?php endif; ?>
    </td>
    <td><span class="pill <?= $statusPill ?>"><?= $statusLabel ?></span></td>
    <td style="color:#64748b;font-size:10px"><?= $fmtPeriode($p['tanggal_mulai'] ?? null, $p['tanggal_selesai'] ?? null) ?></td>
    <td class="<?= $dealCount ? 'num' : 'zero' ?>"><?= $dealCount ?: '—' ?><?= $tSponsor ? ' / ' . $tSponsor : '' ?>
        <?php if ($pipeParts): ?><div class="subnote"><?= implode(' · ', $pipeParts) ?></div><?php endif; ?></td>
    <td class="<?= ($com['total_nilai'] ?? 0) ? 'num' : 'zero' ?>"><?= $rp((int)($com['total_nilai'] ?? 0)) ?>
        <?php if (($com['total_cash'] ?? 0) && ($com['total_barang'] ?? 0)): ?>
        <div class="subnote">cash <?= number_format($com['total_cash']) ?> · barang <?= number_format($com['total_barang']) ?></div>
        <?php endif; ?></td>
    <td class="<?= $now ? 'num' : 'zero' ?>"><?= $rp($now) ?>
        <?php if ($prev || $cum > $now): ?><div class="subnote">lalu <?= number_format($prev) ?> · kum <?= number_format($cum) ?></div><?php endif; ?></td>
    <td class="<?= $target ? 'num' : 'zero' ?>"><?= $target ? round($cum / $target * 100) . '%' : '—' ?>
        <?php if ($target): ?><div class="subnote">target <?= number_format($target) ?></div><?php endif; ?></td>
</tr>
<?php
$analisaData  = $analisaMap[$pid] ?? [];
$highlight    = $analisaData['highlight']     ?? '';
$kendala      = $analisaData['kendala']       ?? '';
$tindakLanjut = $analisaData['tindak_lanjut'] ?? '';
$hasAnalisa   = $highlight !== '' || $kendala !== '' || $tindakLanjut !== '';
?>
<tr class="analisa-row">
    <td colspan="7" style="background:#f8fafc;font-size:10.5px;color:#334155;padding:4px 8px;border-top:none">
        <strong style="color:#0f172a">Analisa:</strong>
        <?php if ($hasAnalisa): ?>
        <div style="font-size:10.5px">
            <?php if ($highlight): ?><div><strong>Highlight:</strong> <?= esc($highlight) ?></div><?php endif; ?>
            <?php if ($kendala): ?><div><strong>Kendala:</strong> <?= esc($kendala) ?></div><?php endif; ?>
            <?php if ($tindakLanjut): ?><div><strong>Tindak Lanjut:</strong> <?= esc($tindakLanjut) ?></div><?php endif; ?>
        </div>
        <?php else: ?>
        <em style="color:#94a3b8">—</em>
        <?php endif; ?>
    </td>
</tr>
</tbody>
<?php endforeach; ?>
</table>
<?php endif; ?>

<!-- ══ SPONSOR EVENT ══ -->
<?php if (! empty($eventAggs)): ?>
<div class="sec-title">
    <span>Sponsorship — Support Event &nbsp;&middot;&nbsp; <?= count($eventAggs) ?> event</span>
    <span class="sec-sub">nilai deal dari modul Sponsorship event</span>
</div>
<table class="main-table">
<thead>
    <tr>
        <th style="width:30%">Event</th>
        <th style="width:12%">Mall</th>
        <th style="width:13%">Tanggal Mulai</th>
        <th class="text-center" style="width:10%">Jml Sponsor</th>
        <th class="text-center" style="width:17%">Nilai Deal</th>
        <th class="text-center" style="width:18%">Penerimaan Bulan Ini</th>
    </tr>
</thead>
<?php foreach ($eventAggs as $e):
    $eid  = (int)$e['event_id'];
    $now  = (int)($evMonthly[$eid] ?? 0);
    $prev = (int)($evPrev[$eid] ?? 0);
    $cum  = (int)($evCum[$eid] ?? 0);
    $deal = (int)$e['total_cash'] + (int)$e['total_barang'];
?>
<tbody class="prog-block">
<tr>
    <td><strong><?= esc($e['event_name']) ?></strong></td>
    <td style="color:#64748b;font-size:10px"><?= esc($mallLabel[$e['event_mall']] ?? ucfirst((string)$e['event_mall'])) ?></td>
    <td style="color:#64748b;font-size:10px"><?= $e['event_start_date'] ? date('d M Y', strtotime($e['event_start_date'])) : '—' ?></td>
    <td class="num"><?= (int)$e['jumlah_sponsor'] ?></td>
    <td class="<?= $deal ? 'num' : 'zero' ?>"><?= $rp($deal) ?>
        <?php if ((int)$e['total_cash'] && (int)$e['total_barang']): ?>
        <div class="subnote">cash <?= number_format($e['total_cash']) ?> · barang <?= number_format($e['total_barang']) ?></div>
        <?php endif; ?></td>
    <td class="<?= $now ? 'num' : 'zero' ?>"><?= $rp($now) ?>
        <?php if ($prev || $cum > $now): ?><div class="subnote">lalu <?= number_format($prev) ?> · kum <?= number_format($cum) ?></div><?php endif; ?></td>
</tr>
</tbody>
<?php endforeach; ?>
</table>
<?php endif; ?>

<div style="font-size:9.5px;color:#94a3b8;margin:-12px 0 14px">
    Keterangan: <em>lalu</em> = penerimaan bulan sebelumnya (<?= esc($prevLabel) ?>) · <em>kum</em> = kumulatif s/d <?= $bulanLabel ?> ·
    pipeline <em>P</em> = prospek, <em>N</em> = negosiasi, <em>K</em> = terkonfirmasi, <em>L</em> = lunas, <em>B</em> = batal.
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
    <div class="sign-box">Disusun oleh<?= $signSlot($sg['disusun'] ?? null) ?></div>
    <?php if (! empty($sg['diperiksa_sm'])): ?>
    <div class="sign-box" style="flex:1.6">
        Diperiksa oleh
        <div style="display:flex;gap:14px">
            <div style="flex:1"><?= $signSlot($sg['diperiksa_sm']) ?></div>
            <div style="flex:1"><?= $signSlot($sg['diperiksa'] ?? null) ?></div>
        </div>
    </div>
    <?php else: ?>
    <div class="sign-box">Diperiksa oleh<?= $signSlot($sg['diperiksa'] ?? null) ?></div>
    <?php endif; ?>
    <div class="sign-box">Mengetahui<?= $signSlot($sg['mengetahui'] ?? null) ?></div>
</div>

<!-- ══ FOOTER ══ -->
<div class="doc-footer">
    <span>Mall Intelligence Center &mdash; IT Department PT. Wulandari Bangun Laksana Tbk.</span>
    <span>Digenerate otomatis &mdash; <?= $printedAt ?></span>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Palet tervalidasi CVD (surface terang) — satu seri per chart, judul = identitas
const C = { green: '#1baf7a', blue: '#2a78d6' };
const ink  = 'rgba(51,65,85,.75)';
const grid = 'rgba(0,0,0,.06)';
Chart.defaults.animation = false;
Chart.defaults.devicePixelRatio = 2;

const rpShort = v => v >= 1e9 ? (v/1e9).toFixed(1) + ' M' : v >= 1e6 ? Math.round(v/1e6) + ' jt' : v >= 1e3 ? Math.round(v/1e3) + ' rb' : v;
const baseOpts = {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
        x: { ticks: { color: ink, font: { size: 9.5 } }, grid: { display: false } },
        y: { ticks: { color: ink, font: { size: 9.5 }, callback: v => rpShort(v) }, grid: { color: grid }, beginAtZero: true },
    },
};
const barStyle = { borderColor: '#ffffff', borderWidth: 1, borderRadius: 3, borderSkipped: false };

const trend = <?= json_encode($trendMonths ?? []) ?>;
const idShort = { '01':'Jan','02':'Feb','03':'Mar','04':'Apr','05':'Mei','06':'Jun','07':'Jul','08':'Agu','09':'Sep','10':'Okt','11':'Nov','12':'Des' };
const mLabel  = m => idShort[m.slice(5)] + ' ' + m.slice(2, 4);
new Chart(document.getElementById('chartTrend'), {
    type: 'bar',
    data: { labels: trend.map(t => mLabel(t.bulan)),
        datasets: [{ label: 'Penerimaan', data: trend.map(t => t.total_nilai), backgroundColor: C.green, ...barStyle }] },
    options: baseOpts,
});

const daily = <?= json_encode($dailyNilai ?? []) ?>;
new Chart(document.getElementById('chartDaily'), {
    type: 'bar',
    data: { labels: daily.map((_, i) => String(i + 1).padStart(2, '0')),
        datasets: [{ label: 'Penerimaan', data: daily, backgroundColor: C.blue, ...barStyle }] },
    options: { ...baseOpts, scales: { ...baseOpts.scales,
        x: { ...baseOpts.scales.x, ticks: { ...baseOpts.scales.x.ticks, autoSkip: true, maxTicksLimit: 16 } } } },
});
</script>

</body>
</html>
