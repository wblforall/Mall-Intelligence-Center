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
.kpi-sub .delta-up, .kpi-sub .delta-down { margin-right: 2px; }
.keterangan { font-size: 9px; color: var(--redup2); margin: 2px 0 0; line-height: 1.55; }
.keterangan em { color: var(--redup); }
.sumber { margin-top: 4px; font-size: 8.5px; color: var(--redup2); line-height: 1.5; }
.blok-akhir { break-inside: avoid; page-break-inside: avoid; }
.blok-akhir .sign-row { margin-top: 16px; }
.blok-akhir .doc-footer { margin-top: 16px; }

/* Tabel tren 6 bulan (angka di samping grafik, terbaca di cetak hitam-putih). */
.tren-table { margin-bottom: 12px; break-inside: avoid; page-break-inside: avoid; }
.tren-table td, .tren-table th { padding-top: 3px; padding-bottom: 3px; }
.tren-table td.lbl { font-weight: 600; color: var(--tinta); white-space: nowrap; }
.tren-table th.ini, .tren-table td.ini { background: var(--emas-pucat) !important; color: var(--tinta); }

/* Panel Temuan Utama + grafik: lebih rapat supaya muat di halaman 1 */
.chart-panel { padding: 10px 12px; margin-bottom: 12px; }
.chart-panel .insight-box { flex: 0 0 37%; padding: 8px 11px 5px 12px; }
.chart-panel .insight-list li { font-size: 9.2px; line-height: 1.42; margin-bottom: 3px; }
.chart-wrap { height: 150px; }
.doc-header { margin-bottom: 12px; }
.kpi-row { margin-bottom: 12px; }
.deret-angka { margin-bottom: 10px; }
.deret-angka > div { padding-top: 5px; padding-bottom: 5px; }

/* Rekap program */
.rekap-wrap { margin-bottom: 4px; }
.rekap-table { margin-bottom: 14px; table-layout: fixed; }
.rekap-table td, .rekap-table th { padding-top: 3.5px; padding-bottom: 3.5px; font-size: 9.5px; }
.rekap-table th { font-size: 8.5px; white-space: normal; }
.rekap-table td.nama { white-space: normal; overflow-wrap: anywhere; font-weight: 600; color: var(--tinta); }
.rekap-table td.periode { color: var(--redup); font-size: 8.5px; white-space: normal; }
.rekap-table tr.grup td {
    background: #eef2f8 !important; color: var(--navy-2); font-size: 8.5px; font-weight: 700;
    letter-spacing: .6px; text-transform: uppercase; padding: 3px 8px; break-after: avoid;
}
.rekap-table tr.grup { break-after: avoid; }
.rekap-table tfoot td { font-size: 9.5px; }
.rekap-table tfoot tr { break-inside: avoid; }

/* Kartu rincian per program (selaras kartu laporan Sponsorship). */
.prog-detail {
    position: relative; margin-bottom: 8px; overflow: hidden;
    border: 1px solid var(--garis); border-radius: 9px; background: #fff;
    break-inside: auto; page-break-inside: auto; box-decoration-break: clone; -webkit-box-decoration-break: clone;
}
.prog-detail::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 3px; background: var(--navy-3); }
.prog-detail.event::before { background: #7c3aed; }
.prog-detail.panjang { break-inside: auto; page-break-inside: auto; overflow: visible; }
.prog-head {
    display: flex; align-items: flex-start; justify-content: space-between; gap: 12px;
    padding: 5px 12px 5px 14px; background: var(--latar); border-bottom: 1px solid var(--garis);
    break-after: avoid; page-break-after: avoid;
}
.prog-head .ph-title { font-size: 11.5px; font-weight: 700; color: var(--tinta); overflow-wrap: anywhere; }
.prog-head .ph-title .lencana { margin-left: 6px; }
.prog-head .ph-meta { font-size: 9px; color: var(--redup); margin-top: 2px; line-height: 1.5; }
.prog-head .ph-meta b { color: var(--tinta); }
.prog-head .ph-kanan { flex: 0 0 auto; display: flex; gap: 5px; align-items: center; padding-top: 1px; flex-wrap: wrap; justify-content: flex-end; max-width: 45%; }

.prog-top { break-inside: avoid; page-break-inside: avoid; break-after: avoid; page-break-after: avoid; }
.prog-metrik { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); border-bottom: 1px solid var(--garis); break-after: avoid; page-break-after: avoid; }
.prog-metrik > div { padding: 4px 10px 4px 14px; min-width: 0; }
.prog-metrik > div + div { border-left: 1px solid var(--garis-halus); }
.prog-metrik span { display: block; font-size: 8px; color: var(--redup); text-transform: uppercase; letter-spacing: .5px; }
.prog-metrik b { display: block; font-size: 13px; font-weight: 700; color: var(--tinta); font-variant-numeric: tabular-nums; line-height: 1.25; }
.prog-metrik b.nol { color: #cbd5e1; }
.prog-metrik small { display: block; font-size: 8px; color: var(--redup2); white-space: nowrap; font-variant-numeric: tabular-nums; }
.prog-target { padding: 3px 14px 4px; font-size: 9px; color: var(--redup); border-bottom: 1px solid var(--garis-halus); }
.prog-target b { color: var(--tinta); }

.detail-table { margin-bottom: 0; table-layout: fixed; }
.detail-table th { background: var(--navy-3); font-size: 8px; padding: 3.5px 8px; white-space: normal; }
.detail-table td { font-size: 9.5px; padding: 3px 8px; overflow-wrap: anywhere; }
.detail-table th:first-child, .detail-table td:first-child { padding-left: 14px; }
.detail-table td.nama { font-weight: 600; color: var(--tinta); }
.detail-table td .jenis { font-size: 8px; color: var(--redup2); }
.detail-table tfoot td { background: #eef2f8; border-top: 1.5px solid var(--navy-3); font-size: 9.5px; }
.detail-table tfoot { break-inside: avoid; }
.detail-table tbody tr:last-child { break-after: avoid; }

/* Analisa per program: tiga kolom (Highlight / Kendala / Tindak Lanjut), teks utuh. */
.prog-analisa3 {
    display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0;
    background: #fbf8ef; border-top: 1px solid #efe4c4; border-bottom: 1px solid #efe4c4;
    break-inside: avoid; page-break-inside: avoid;
}
.prog-analisa3 .an-kol { padding: 5px 12px 5px 14px; min-width: 0; }
.prog-analisa3 .an-kol + .an-kol { border-left: 1px solid #efe4c4; }
.prog-analisa3 .an-isi { margin-top: 3px; font-size: 9.5px; line-height: 1.5; color: var(--teks); overflow-wrap: anywhere; word-break: break-word; }
.prog-analisa3 .an-isi.kosong { color: var(--redup2); font-style: italic; }
.prog-analisa3.satu { grid-template-columns: 1fr; }
.prog-eval { background: #f4f6fa; border-top: 1px solid var(--garis); }
.prog-eval .an-kol + .an-kol { border-left-color: var(--garis); }
.prog-belum, .prog-lalu { break-inside: avoid; page-break-inside: avoid; }
.prog-belum { padding: 6px 14px; background: #fbf8ef; border-top: 1px solid #efe4c4; font-size: 9.5px; color: var(--redup); font-style: italic; }
.prog-lalu {
    padding: 5px 14px 6px; border-top: 1px dashed var(--garis); background: var(--latar);
    font-size: 9px; line-height: 1.5; color: var(--redup); overflow-wrap: anywhere;
}
.prog-lalu b { color: var(--teks); font-weight: 600; }

/* Kendala & Tindak Lanjut */
.tl-table { table-layout: fixed; }
.tl-table td { vertical-align: top; font-size: 9.5px; line-height: 1.5; overflow-wrap: anywhere; }
.tl-table td.nama { font-weight: 600; color: var(--tinta); }
.tl-table td.kosong { color: var(--redup2); font-style: italic; }
.tl-table td.nama small { display: block; font-weight: 400; color: var(--redup2); font-size: 8px; }

/* Bulan tanpa program: rapatkan halaman 1 supaya tanda tangan ikut di halaman yang sama. */
body.ringkas .doc-header { margin-bottom: 10px; }
body.ringkas .sec-title { margin-bottom: 7px; }
body.ringkas .deret-angka > div { padding-top: 5px; padding-bottom: 5px; }
body.ringkas .kpi-row { margin-bottom: 12px; }
body.ringkas .deret-angka { margin-bottom: 12px; }
body.ringkas .chart-panel { margin-bottom: 8px; padding: 10px 12px; }
body.ringkas .chart-wrap { height: 96px; }
body.ringkas .tren-table td, body.ringkas .tren-table th { padding-top: 2px; padding-bottom: 2px; }
body.ringkas .insight-list li { margin-bottom: 2px; }
body.ringkas .doc-header { min-height: 90px; padding-top: 10px; padding-bottom: 10px; }
body.ringkas .tren-table { margin-bottom: 8px; }
body.ringkas .sign-box .sign-space { height: 40px; }
body.ringkas .catatan { margin-bottom: 0; }
body.ringkas .blok-akhir .sign-row { margin-top: 10px; }
body.ringkas .blok-akhir .doc-footer { margin-top: 10px; }
</style>
</head>
<?php
$idBulan = ['January'=>'Januari','February'=>'Februari','March'=>'Maret','April'=>'April',
            'May'=>'Mei','June'=>'Juni','July'=>'Juli','August'=>'Agustus',
            'September'=>'September','October'=>'Oktober','November'=>'November','December'=>'Desember'];
$bulanDt    = \DateTime::createFromFormat('Y-m', $bulan);
$bulanLabel = strtr($bulanDt->format('F Y'), $idBulan);
$prevDt     = \DateTime::createFromFormat('Y-m', $prevBulan ?? date('Y-m'));
$prevLabel  = $prevDt ? strtr($prevDt->format('F Y'), $idBulan) : '';

// Hanya program yang relevan dengan bulan terpilih (overlap tanggal / ada aktivitas bulan ini).
// Program yang punya analisa bulan ini tetapi tak relevan dicatat di $progLain (tetap masuk bagian Kendala & Tindak Lanjut).
$bMonthStart = $bulan . '-01';
$bMonthEnd   = date('Y-m-t', strtotime($bMonthStart));
$semuaProgram = $programs;
$relevanFn = function ($p) use ($bMonthStart, $bMonthEnd, $monthlyData, $voucherByProgram, $evoucherByProgram, $hadiahByProgram, $ehadiahByProgram) {
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
};
$programs = array_values(array_filter($semuaProgram, $relevanFn));
// Urutan: standalone aktif, standalone nonaktif, lalu program event (sama dengan layar Summary).
usort($programs, function ($a, $b) {
    $g = fn($p) => $p['source'] === 'event' ? 2 : (($p['status'] ?? 'active') === 'inactive' ? 1 : 0);
    return $g($a) <=> $g($b);
});

$totalProgram  = count($programs);
$activeProgram = count(array_filter($programs, fn($p) => $p['status'] === 'active'));

// Angka gaya Indonesia (titik ribuan, koma desimal).
$nf   = fn($n): string => number_format((float)$n, 0, ',', '.');
$numF = fn($n): string => (int)$n > 0 ? number_format((int)$n, 0, ',', '.') : '—';
$rpF  = fn($n): string => (float)$n > 0 ? 'Rp ' . number_format((float)$n, 0, ',', '.') : '—';
$pctF = fn($n): string => rtrim(rtrim(number_format((float)$n, 1, ',', '.'), '0'), ',');
$teks = fn($t): string => nl2br(esc(trim((string)$t)));

$blnPendek = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
$mallLabel = ['ewalk' => 'eWalk', 'pentacity' => 'Pentacity', 'both' => 'eWalk & Pentacity'];
// Periode ringkas berbulan Indonesia: "01 Jul – 30 Sep 2026" (tahun sekali bila sama).
$fmtPeriode = function (?string $a, ?string $b) use ($blnPendek): string {
    if (! $a) return '—';
    $ta = strtotime($a);
    $tgl = fn(int $t, bool $th = true) => date('d', $t) . ' ' . $blnPendek[(int)date('n', $t) - 1] . ($th ? ' ' . date('Y', $t) : '');
    if (! $b || $b === $a) return $tgl($ta);
    $tb = strtotime($b);
    return $tgl($ta, date('Y', $ta) !== date('Y', $tb)) . ' – ' . $tgl($tb);
};
// Selisih terhadap bulan lalu (▲/▼) untuk kartu KPI.
$deltaPct = function (int $now, int $prev) use ($nf): string {
    if ($prev <= 0) return $now > 0 ? '<span class="delta-up">▲ baru</span> vs bln lalu' : '';
    $pct = round(($now - $prev) / $prev * 100);
    $cls = $pct >= 0 ? 'delta-up' : 'delta-down';
    return '<span class="' . $cls . '">' . ($pct >= 0 ? '▲' : '▼') . ' ' . $nf(abs($pct)) . '%</span> vs bln lalu';
};

$prevAnalisaMap = $prevAnalisaMap ?? [];
$analisaKurang  = $analisaKurang ?? [];
$itemDetail     = $itemDetail ?? [];
$capaian        = $capaian ?? [];
$adaAnalisa = fn(array $a): bool => trim(($a['highlight'] ?? '') . ($a['kendala'] ?? '') . ($a['tindak_lanjut'] ?? '') . ($a['analisa'] ?? '')) !== '';
$progKey    = fn($p): string => ($p['source'] === 'standalone' ? 's_' : 'e_') . $p['id'];
$progMall   = fn($p): string => (string)($p['source'] === 'standalone' ? ($p['mall'] ?? '') : ($p['event_mall'] ?? ''));
$statusInfo = function ($p): array {
    $lab = ['active' => 'Aktif', 'inactive' => 'Nonaktif', 'locked' => 'Terkunci'][$p['status']] ?? (string)$p['status'];
    $cls = ['active' => 'baik', 'inactive' => 'netral', 'locked' => 'buruk'][$p['status']] ?? 'netral';
    return [$lab, $cls];
};
$capInfo = [
    'done'     => ['Tercapai', 'baik'],
    'partial'  => ['Sebagian', 'info'],
    'belum'    => ['Belum tercapai', 'waspada'],
    'notarget' => ['Tanpa target', 'netral'],
];

// Realisasi per program (bulan ini / bulan lalu / kumulatif) — dihitung sekali, dipakai rekap & kartu.
$data = [];
foreach ($programs as $p) {
    $isSt = $p['source'] === 'standalone'; $key = $progKey($p); $pid = $p['id'];
    $md = $monthlyData[$key] ?? []; $vd = $isSt ? ($voucherByProgram[$pid] ?? []) : ($evoucherByProgram[$pid] ?? []);
    $pmd = $prevMonthlyData[$key] ?? []; $pvd = $isSt ? ($prevVoucherByProgram[$pid] ?? []) : ($prevEvoucherByProgram[$pid] ?? []);
    $cmd = $cumulativeData[$key] ?? []; $cvd = $isSt ? ($voucherCumByProgram[$pid] ?? []) : ($evoucherCumByProgram[$pid] ?? []);
    $d = [
        'member' => (int)($md['total_jumlah'] ?? 0), 'aktif' => (int)($md['total_member_aktif'] ?? 0),
        'sebar'  => (int)($vd['total_tersebar'] ?? 0), 'pakai' => (int)($vd['total_terpakai'] ?? 0),
        'hadiah' => (int)($isSt ? ($hadiahByProgram[$pid] ?? 0) : ($ehadiahByProgram[$pid] ?? 0)),
        'm_lalu' => (int)($pmd['total_jumlah'] ?? 0), 'a_lalu' => (int)($pmd['total_member_aktif'] ?? 0),
        's_lalu' => (int)($pvd['total_tersebar'] ?? 0), 'p_lalu' => (int)($pvd['total_terpakai'] ?? 0),
        'h_lalu' => (int)($isSt ? ($prevHadiahByProgram[$pid] ?? 0) : ($prevEhadiahByProgram[$pid] ?? 0)),
        'm_kum' => (int)($cmd['total_jumlah'] ?? 0), 'a_kum' => (int)($cmd['total_member_aktif'] ?? 0),
        's_kum' => (int)($cvd['total_tersebar'] ?? 0), 'p_kum' => (int)($cvd['total_terpakai'] ?? 0),
        'h_kum' => (int)($isSt ? ($hadiahCumByProgram[$pid] ?? 0) : ($ehadiahCumByProgram[$pid] ?? 0)),
    ];
    $d['serap'] = $d['sebar'] > 0 ? round($d['pakai'] / $d['sebar'] * 100) : null;
    $d['aktivitas'] = (bool)($d['member'] || $d['aktif'] || $d['sebar'] || $d['pakai'] || $d['hadiah']);
    $data[$key] = $d;
}

// Program yang dirinci kartu: ada realisasi bulan ini ATAU sudah ada analisa. Selebihnya cukup di rekap.
$progKartu = array_values(array_filter($programs, fn($p) => $data[$progKey($p)]['aktivitas'] || $adaAnalisa($analisaMap[$progKey($p)] ?? [])));
$progTanpaKartu = array_values(array_filter($programs, fn($p) => ! in_array($p, $progKartu, true)));
// Analisa bulan ini milik program yang tak relevan di bulan ini (mis. periode di luar bulan) tetap dilaporkan.
$progLain = array_values(array_filter($semuaProgram, fn($p) => ! $relevanFn($p) && $adaAnalisa($analisaMap[$progKey($p)] ?? [])));
$progBeranalisa = array_merge(array_values(array_filter($programs, fn($p) => $adaAnalisa($analisaMap[$progKey($p)] ?? []))), $progLain);
$progBelumAnalisa = array_values(array_filter($programs, fn($p) => ! $adaAnalisa($analisaMap[$progKey($p)] ?? [])));

// Temuan utama dari controller + kelengkapan analisa (dihitung di sini dari data yang sama).
$insights = $insights ?? [];
if ($totalProgram > 0) {
    $nLengkap = $totalProgram - count($progBelumAnalisa);
    $insights[] = 'Analisa bulan ini terisi untuk ' . $nf($nLengkap) . ' dari ' . $nf($totalProgram) . ' program'
        . ($progBelumAnalisa ? ' — ' . $nf(count($progBelumAnalisa)) . ' program belum diisi (lihat bagian Kendala & Tindak Lanjut).' : '.');
}
?>

<body class="<?= $totalProgram === 0 && ! $progLain ? 'ringkas' : '' ?>">

<button class="btn-print no-print" onclick="window.print()">&#128438; Cetak</button>

<!-- ══ HEADER ══ -->
<div class="doc-header">
    <div>
        <div class="title">Laporan Bulanan — Program Loyalty</div>
        <div class="sub"><?= $bulanLabel ?></div>
        <div class="org">PT. Wulandari Bangun Laksana Tbk. &mdash; IT Department &mdash; Mall Intelligence Center</div>
        <?php if (! empty($analisaBelumLengkap)): ?><div class="org" style="color:#b91c1c;font-weight:700">Analisa belum lengkap (<?= (int)$analisaBelumLengkap ?> program)</div><?php endif; ?>
    </div>
    <div class="meta">
        Dicetak oleh: <?= esc($printedBy) ?><br>
        Tanggal cetak: <?= $printedAt ?><br>
        Total program: <?= $totalProgram ?> &middot; Aktif: <?= $activeProgram ?>
    </div>
</div>

<!-- ══ KPI ══ -->
<?php
$kpiPrev = ['member' => 0, 'aktif' => 0, 'sebar' => 0, 'pakai' => 0, 'hadiah' => 0];
foreach ($prevMonthlyData ?? [] as $d0) { $kpiPrev['member'] += (int)($d0['total_jumlah'] ?? 0); $kpiPrev['aktif'] += (int)($d0['total_member_aktif'] ?? 0); }
foreach (array_merge($prevVoucherByProgram ?? [], []) as $d0) { $kpiPrev['sebar'] += (int)$d0['total_tersebar']; $kpiPrev['pakai'] += (int)$d0['total_terpakai']; }
foreach ($prevEvoucherByProgram ?? [] as $d0) { $kpiPrev['sebar'] += (int)$d0['total_tersebar']; $kpiPrev['pakai'] += (int)$d0['total_terpakai']; }
foreach (array_merge($prevHadiahByProgram ?? [], $prevEhadiahByProgram ?? []) as $n0) { $kpiPrev['hadiah'] += (int)$n0; }
?>
<div class="kpi-row">
    <div class="kpi-box kpi-member">
        <div class="kpi-label">Member Baru</div>
        <div class="kpi-num"><?= $nf($kpiMember) ?></div>
        <div class="kpi-sub"><?= $deltaPct((int)$kpiMember, $kpiPrev['member']) ?: 'bulan ' . $bulanLabel ?></div>
    </div>
    <div class="kpi-box kpi-aktif">
        <div class="kpi-label">Member Aktif</div>
        <div class="kpi-num"><?= $nf($kpiMemberAktif) ?></div>
        <div class="kpi-sub"><?= $deltaPct((int)$kpiMemberAktif, $kpiPrev['aktif']) ?: 'bulan ' . $bulanLabel ?></div>
    </div>
    <div class="kpi-box kpi-sebar">
        <div class="kpi-label">Voucher Tersebar</div>
        <div class="kpi-num"><?= $nf($kpiTersebar) ?></div>
        <div class="kpi-sub"><?= $deltaPct((int)$kpiTersebar, $kpiPrev['sebar']) ?: 'bulan ' . $bulanLabel ?></div>
    </div>
    <div class="kpi-box kpi-pakai">
        <div class="kpi-label">Voucher Terpakai</div>
        <div class="kpi-num"><?= $nf($kpiTerpakai) ?></div>
        <div class="kpi-sub"><?= $kpiTersebar > 0 ? $pctF($kpiTerpakai / $kpiTersebar * 100) . '% penyerapan' : 'bulan ' . $bulanLabel ?></div>
    </div>
    <div class="kpi-box kpi-hadiah">
        <div class="kpi-label">Hadiah Dibagikan</div>
        <div class="kpi-num"><?= $nf($kpiHadiah) ?></div>
        <div class="kpi-sub"><?= $deltaPct((int)$kpiHadiah, $kpiPrev['hadiah']) ?: 'bulan ' . $bulanLabel ?></div>
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

<!-- ══ RINGKASAN EKSEKUTIF & TREN ══ -->
<div class="sec-title"><span>Ringkasan Eksekutif &amp; Tren</span>
    <span class="sec-sub">temuan utama &middot; tren 6 bulan terakhir &middot; aktivitas harian <?= $bulanLabel ?></span></div>
<div class="chart-panel">
    <div class="insight-box">
        <div class="insight-title">Temuan Utama</div>
        <ul class="insight-list">
            <?php foreach ($insights as $ins): ?>
            <li><?= esc($ins) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <div class="chart-box">
        <div class="chart-title">Tren 6 Bulan — Member, Voucher Terpakai &amp; Hadiah</div>
        <div class="chart-wrap"><canvas id="chartTrend"></canvas></div>
    </div>
    <div class="chart-box">
        <div class="chart-title">Aktivitas Harian — <?= $bulanLabel ?></div>
        <div class="chart-wrap"><canvas id="chartDaily"></canvas></div>
    </div>
</div>
<?php $trenAda = false; foreach ($trendMonths ?? [] as $tm0) { if ((int)$tm0['total_jumlah'] || (int)$tm0['total_tersebar'] || (int)$tm0['total_terpakai'] || (int)$tm0['total_hadiah']) $trenAda = true; } ?>
<?php if ($trenAda): ?>
<table class="main-table tren-table">
<thead><tr>
    <th>Tren 6 bulan (seluruh program)</th>
    <?php foreach ($trendMonths as $ti => $tm): $dt = \DateTime::createFromFormat('Y-m', $tm['bulan']); ?>
    <th class="num<?= $ti === count($trendMonths) - 1 ? ' ini' : '' ?>"><?= $blnPendek[(int)$dt->format('n') - 1] . ' ' . $dt->format('Y') ?></th>
    <?php endforeach; ?>
</tr></thead>
<tbody>
<?php foreach ([['Member baru', 'total_jumlah'], ['Voucher tersebar', 'total_tersebar'], ['Voucher terpakai', 'total_terpakai'], ['Hadiah dibagikan', 'total_hadiah']] as [$lab, $f]): ?>
<tr><td class="lbl"><?= $lab ?></td>
    <?php foreach ($trendMonths as $ti => $tm): $v = (int)($tm[$f] ?? 0); ?>
    <td class="<?= $v ? 'num' : 'zero' ?><?= $ti === count($trendMonths) - 1 ? ' ini' : '' ?>"><?= $numF($v) ?></td>
    <?php endforeach; ?>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>

<!-- ══ REKAP PROGRAM ══ -->
<?php if ($totalProgram > 0):
    $tot = ['member' => 0, 'aktif' => 0, 'sebar' => 0, 'pakai' => 0, 'hadiah' => 0];
    $grupBuka = null; ?>
<div class="rekap-wrap">
<div class="sec-title"><span>Rekap Program &middot; <?= $bulanLabel ?></span>
    <span class="sec-sub"><?= $totalProgram ?> program &middot; realisasi bulan ini, penyerapan voucher, capaian target kumulatif dan kelengkapan analisa</span></div>
<table class="main-table rekap-table">
<colgroup><col style="width:23%"><col style="width:6%"><col style="width:12%"><col style="width:7%"><col style="width:7%"><col style="width:7%"><col style="width:7%"><col style="width:6%"><col style="width:6%"><col style="width:10%"><col style="width:9%"></colgroup>
<thead><tr>
    <th>Program</th>
    <th>Status</th>
    <th>Periode / Event</th>
    <th class="num">Member Baru</th>
    <th class="num">Member Aktif</th>
    <th class="num">Voucher Sebar</th>
    <th class="num">Voucher Pakai</th>
    <th class="num">% Serap</th>
    <th class="num">Hadiah</th>
    <th class="text-center">Capaian Target</th>
    <th class="text-center">Analisa</th>
</tr></thead>
<tbody>
<?php foreach ($programs as $p):
    $isSt = $p['source'] === 'standalone'; $key = $progKey($p); $d = $data[$key];
    foreach (['member', 'aktif', 'sebar', 'pakai', 'hadiah'] as $f) $tot[$f] += $d[$f];
    $grup = $isSt ? 'st' : 'ev';
    if ($grupBuka !== $grup): $grupBuka = $grup; ?>
<tr class="grup"><td colspan="11"><?= $isSt ? 'Program loyalty standalone' : 'Program loyalty — support event' ?></td></tr>
<?php endif;
    [$stLab, $stCls] = $statusInfo($p);
    $cap = $capInfo[$capaian[$key]['status'] ?? 'notarget'];
    $ok  = $adaAnalisa($analisaMap[$key] ?? []);
    $kurang = isset($analisaKurang[$key]);
?>
<tr>
    <td class="nama"><?= esc($p['nama_program'] ?? '—') ?></td>
    <td><span class="lencana <?= $stCls ?>"><?= esc($stLab) ?></span></td>
    <td class="periode"><?= $isSt ? $fmtPeriode($p['tanggal_mulai'] ?? null, $p['tanggal_selesai'] ?? null) : esc($p['event_name'] ?? '—') ?></td>
    <td class="<?= $d['member'] ? 'num' : 'zero' ?>"><?= $numF($d['member']) ?></td>
    <td class="<?= $d['aktif'] ? 'num' : 'zero' ?>"><?= $numF($d['aktif']) ?></td>
    <td class="<?= $d['sebar'] ? 'num' : 'zero' ?>"><?= $numF($d['sebar']) ?></td>
    <td class="<?= $d['pakai'] ? 'num' : 'zero' ?>"><?= $numF($d['pakai']) ?></td>
    <td class="<?= $d['serap'] ? 'num' : 'zero' ?>"><?= $d['serap'] !== null ? $nf($d['serap']) . '%' : '—' ?></td>
    <td class="<?= $d['hadiah'] ? 'num' : 'zero' ?>"><?= $numF($d['hadiah']) ?></td>
    <td class="text-center"><span class="lencana <?= $cap[1] ?>"><?= $cap[0] ?></span></td>
    <td class="text-center"><span class="lencana <?= $kurang ? 'waspada' : ($ok ? 'baik' : 'netral') ?>"><?= $kurang ? 'Belum lengkap' : ($ok ? 'Terisi' : 'Belum diisi') ?></span></td>
</tr>
<?php endforeach; ?>
</tbody>
<tfoot><tr>
    <td colspan="3">Total &middot; <?= $totalProgram ?> program</td>
    <td class="num"><?= $nf($tot['member']) ?></td>
    <td class="num"><?= $nf($tot['aktif']) ?></td>
    <td class="num"><?= $nf($tot['sebar']) ?></td>
    <td class="num"><?= $nf($tot['pakai']) ?></td>
    <td class="num"><?= $tot['sebar'] > 0 ? $nf(round($tot['pakai'] / $tot['sebar'] * 100)) . '%' : '—' ?></td>
    <td class="num"><?= $nf($tot['hadiah']) ?></td>
    <td colspan="2"></td>
</tr></tfoot>
</table>
</div>
<?php endif; ?>

<!-- ══ RINCIAN PROGRAM ══ -->
<?php if ($progKartu): ?>
<div class="sec-title"><span>Rincian Program Loyalty &middot; <?= count($progKartu) ?> program</span>
    <span class="sec-sub">realisasi bulan ini, item voucher &amp; hadiah, dan analisa <?= $bulanLabel ?></span></div>
<?php foreach ($progKartu as $p):
    $isSt = $p['source'] === 'standalone'; $key = $progKey($p); $d = $data[$key];
    [$stLab, $stCls] = $statusInfo($p);
    $mall = $progMall($p);
    $a  = $analisaMap[$key] ?? [];
    $hl = trim((string)($a['highlight'] ?? '')); $kd = trim((string)($a['kendala'] ?? '')); $tl = trim((string)($a['tindak_lanjut'] ?? '')); $an = trim((string)($a['analisa'] ?? ''));
    $pAn = $adaAnalisa($a);
    $lalu = trim((string)(($prevAnalisaMap[$key] ?? [])['tindak_lanjut'] ?? ''));
    $det = $itemDetail[$key] ?? ['voucher' => [], 'hadiah' => []];
    // Hanya item yang punya realisasi (bulan ini / kumulatif); item tanpa realisasi diringkas jadi satu keterangan.
    $itemSepi = 0;
    $det['voucher'] = array_values(array_filter($det['voucher'], function ($v) use (&$itemSepi) { $ok = $v['sebar'] || $v['pakai'] || $v['sebar_kum'] || $v['pakai_kum']; if (! $ok) $itemSepi++; return $ok; }));
    $det['hadiah']  = array_values(array_filter($det['hadiah'],  function ($h) use (&$itemSepi) { $ok = $h['bagi'] || $h['bagi_kum']; if (! $ok) $itemSepi++; return $ok; }));
    $nItem = count($det['voucher']) + count($det['hadiah']);
    $cap = $capInfo[$capaian[$key]['status'] ?? 'notarget']; $capRinci = $capaian[$key]['rinci'] ?? [];
    $metrik = [
        ['Member baru', $d['member'], $d['m_lalu'], $d['m_kum']],
        ['Member aktif', $d['aktif'], $d['a_lalu'], $d['a_kum']],
        ['Voucher tersebar', $d['sebar'], $d['s_lalu'], $d['s_kum']],
        ['Voucher terpakai', $d['pakai'], $d['p_lalu'], $d['p_kum']],
        ['Penyerapan', null, null, null],
        ['Hadiah dibagikan', $d['hadiah'], $d['h_lalu'], $d['h_kum']],
    ];
?>
<div class="prog-detail<?= $isSt ? '' : ' event' ?><?= $nItem > 8 ? ' panjang' : '' ?>">
    <div class="prog-top">
    <div class="prog-head">
        <div>
            <div class="ph-title"><?= esc($p['nama_program'] ?? '—') ?><span class="lencana <?= $stCls ?>"><?= esc($stLab) ?></span><?php if (! $isSt): ?><span class="lencana ungu">Event</span><?php endif; ?></div>
            <div class="ph-meta">
                <?php if ($mall): ?><?= esc($mallLabel[$mall] ?? ucfirst($mall)) ?> &middot; <?php endif; ?>
                <?php if ($isSt): ?>Periode <b><?= $fmtPeriode($p['tanggal_mulai'] ?? null, $p['tanggal_selesai'] ?? null) ?></b>
                <?php else: ?>Event <b><?= esc($p['event_name'] ?? '—') ?></b><?php if (! empty($p['event_start_date'])): ?> &middot; mulai <b><?= $fmtPeriode($p['event_start_date'], null) ?></b><?php endif; ?><?php endif; ?>
                <?php if ((float)($p['budget'] ?? 0) > 0): ?> &middot; Budget <b><?= $rpF($p['budget']) ?></b><?php endif; ?>
            </div>
        </div>
        <div class="ph-kanan">
            <?php if ($capaian[$key]['tgt'] ?? 0): ?><span class="lencana <?= $cap[1] ?>">target <?= strtolower($cap[0]) ?></span><?php endif; ?>
            <?php if (! $pAn): ?><span class="lencana netral">Analisa belum diisi</span>
            <?php elseif (isset($analisaKurang[$key])): ?><span class="lencana waspada">Analisa belum lengkap</span><?php endif; ?>
        </div>
    </div>
    <div class="prog-metrik">
        <?php foreach ($metrik as [$lab, $now, $lalu0, $kum]): ?>
        <div><span><?= $lab ?></span>
            <?php if ($now === null): ?>
            <b class="<?= $d['serap'] === null ? 'nol' : '' ?>"><?= $d['serap'] !== null ? $nf($d['serap']) . '%' : '—' ?></b>
            <small><?= $d['s_kum'] > 0 ? 'kum ' . $nf(round($d['p_kum'] / $d['s_kum'] * 100)) . '%' : 'belum ada voucher' ?></small>
            <?php else: ?>
            <b class="<?= $now ? '' : 'nol' ?>"><?= $now ? $nf($now) : '—' ?></b>
            <small>lalu <?= $nf($lalu0) ?> · kum <?= $nf($kum) ?></small>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php if ($capRinci): ?>
    <div class="prog-target"><b>Capaian target kumulatif s/d <?= $bulanLabel ?>:</b> <?= esc(implode(' · ', $capRinci)) ?></div>
    <?php endif; ?>
    </div>
    <?php if ($nItem > 0): ?>
    <table class="main-table detail-table">
    <colgroup><col style="width:28%"><col style="width:11%"><col style="width:9%"><col style="width:10%"><col style="width:11%"><col style="width:9%"><col style="width:10%"><col style="width:12%"></colgroup>
    <thead><tr>
        <th>Item</th>
        <th class="num">Nilai</th>
        <th class="num">Kuota</th>
        <th class="num">Sebar (bln)</th>
        <th class="num">Pakai (bln)</th>
        <th class="num">% Serap</th>
        <th class="num">Sebar (kum)</th>
        <th class="num">Pakai (kum)</th>
    </tr></thead>
    <tbody>
    <?php foreach ($det['voucher'] as $v): $sp = $v['sebar'] > 0 ? round($v['pakai'] / $v['sebar'] * 100) : null; ?>
        <tr>
            <td class="nama"><?= esc($v['nama']) ?> <span class="jenis">voucher</span></td>
            <td class="<?= $v['nilai'] ? 'num' : 'zero' ?>"><?= $rpF($v['nilai']) ?></td>
            <td class="<?= $v['terbit'] ? 'num' : 'zero' ?>"><?= $numF($v['terbit']) ?></td>
            <td class="<?= $v['sebar'] ? 'num' : 'zero' ?>"><?= $numF($v['sebar']) ?></td>
            <td class="<?= $v['pakai'] ? 'num' : 'zero' ?>"><?= $numF($v['pakai']) ?></td>
            <td class="<?= $sp ? 'num' : 'zero' ?>"><?= $sp !== null ? $nf($sp) . '%' : '—' ?></td>
            <td class="<?= $v['sebar_kum'] ? 'num' : 'zero' ?>"><?= $numF($v['sebar_kum']) ?></td>
            <td class="<?= $v['pakai_kum'] ? 'num' : 'zero' ?>"><?= $numF($v['pakai_kum']) ?></td>
        </tr>
    <?php endforeach; foreach ($det['hadiah'] as $h): ?>
        <tr>
            <td class="nama"><?= esc($h['nama']) ?> <span class="jenis">hadiah</span></td>
            <td class="<?= $h['nilai'] ? 'num' : 'zero' ?>"><?= $rpF($h['nilai']) ?></td>
            <td class="<?= $h['stok'] ? 'num' : 'zero' ?>"><?= $numF($h['stok']) ?></td>
            <td class="zero">—</td>
            <td class="<?= $h['bagi'] ? 'num' : 'zero' ?>"><?= $numF($h['bagi']) ?></td>
            <td class="zero">—</td>
            <td class="zero">—</td>
            <td class="<?= $h['bagi_kum'] ? 'num' : 'zero' ?>"><?= $numF($h['bagi_kum']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
    </table>
    <?php endif; ?>
    <?php if ($itemSepi > 0): ?><div class="prog-target"><?= $nItem > 0 ? '+ ' : '' ?><?= $nf($itemSepi) ?> item voucher/hadiah lain belum memiliki realisasi s/d <?= $bulanLabel ?>.</div><?php endif; ?>
    <?php if ($pAn): ?>
        <?php if ($hl !== '' || $kd !== '' || $tl !== ''): ?>
        <div class="prog-analisa3">
            <?php foreach ([['Highlight', 'baik', $hl], ['Kendala', 'waspada', $kd], ['Tindak Lanjut', 'info', $tl]] as [$lab, $kls, $isi]): ?>
            <div class="an-kol"><span class="lencana <?= $kls ?>"><?= $lab ?></span>
                <?php if ($isi !== ''): ?><div class="an-isi"><?= $teks($isi) ?></div>
                <?php else: ?><div class="an-isi kosong">Tidak diisi.</div><?php endif; ?></div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="prog-analisa3 satu"><div class="an-kol"><span class="lencana emas">Analisa</span><div class="an-isi"><?= $teks($an) ?></div></div></div>
        <?php endif; ?>
    <?php else: ?>
    <div class="prog-belum">Analisa <?= $bulanLabel ?> untuk program ini belum diisi.</div>
    <?php endif; ?>
    <?php if ($lalu !== ''): ?>
    <div class="prog-lalu"><b>Tindak lanjut bulan lalu (<?= $prevLabel ?>):</b> <?= $teks($lalu) ?></div>
    <?php endif; ?>
</div>
<?php endforeach; ?>
<?php if ($progTanpaKartu): ?>
<div class="catatan info"><b>Tanpa realisasi &amp; analisa bulan ini (<?= count($progTanpaKartu) ?> program, hanya tampil di rekap):</b>
    <?= esc(implode('; ', array_map(fn($p) => $p['nama_program'] ?? '—', $progTanpaKartu))) ?>.</div>
<?php endif; ?>
<?php endif; ?>

<!-- ══ KENDALA & TINDAK LANJUT ══ -->
<?php if ($totalProgram > 0 || $progLain): ?>
<div class="sec-title"><span>Kendala &amp; Tindak Lanjut</span>
    <span class="sec-sub">dari analisa tiap program &middot; <?= $bulanLabel ?></span></div>
<?php if ($progBeranalisa): ?>
<table class="main-table tl-table">
<colgroup><col style="width:20%"><col style="width:40%"><col style="width:40%"></colgroup>
<thead><tr>
    <th>Program</th>
    <th>Kendala</th>
    <th>Tindak Lanjut</th>
</tr></thead>
<tbody>
<?php foreach ($progBeranalisa as $p): $a = $analisaMap[$progKey($p)] ?? []; $legacy = trim((string)($a['analisa'] ?? ''));
    $adaTiga = trim(($a['kendala'] ?? '') . ($a['tindak_lanjut'] ?? '') . ($a['highlight'] ?? '')) !== ''; ?>
<tr>
    <td class="nama"><?= esc($p['nama_program'] ?? '—') ?><?php if ($p['source'] === 'event'): ?><small>Support event</small><?php endif; ?></td>
    <?php if (! $adaTiga && $legacy !== ''): ?>
    <td colspan="2"><?= $teks($legacy) ?></td>
    <?php else: foreach (['kendala', 'tindak_lanjut'] as $f): $v = trim((string)($a[$f] ?? '')); ?>
    <td class="<?= $v === '' ? 'kosong' : '' ?>"><?= $v === '' ? 'Tidak diisi.' : $teks($v) ?></td>
    <?php endforeach; endif; ?>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php else: ?>
<div class="catatan waspada">Belum ada analisa (highlight, kendala, tindak lanjut) yang diisi untuk program pada <?= $bulanLabel ?>.</div>
<?php endif; ?>
<?php endif; ?>

<div class="blok-akhir">
<?php if ($progBelumAnalisa && $progBeranalisa): ?>
<div class="catatan waspada"><b>Analisa belum diisi (<?= count($progBelumAnalisa) ?> program):</b> <?= esc(implode('; ', array_map(fn($p) => $p['nama_program'] ?? '—', $progBelumAnalisa))) ?>.</div>
<?php endif; ?>
<?php if ($totalProgram === 0): ?>
<div class="catatan info">Tidak ada program loyalty yang berjalan atau mencatat aktivitas pada <?= $bulanLabel ?>.</div>
<?php else: ?>
<div class="keterangan">
    Keterangan: <em>lalu</em> = realisasi bulan sebelumnya (<?= esc($prevLabel) ?>) · <em>kum</em> = kumulatif sejak program dimulai s/d <?= $bulanLabel ?> · <em>% Serap</em> = voucher terpakai ÷ voucher tersebar · <em>Pakai</em> pada baris hadiah = hadiah dibagikan · <em>Capaian Target</em> = target peserta, member aktif, dan kuota voucher (kumulatif) terhadap targetnya.
</div>
<div class="sumber">Sumber: modul Program Loyalty &amp; Event Loyalty, Mall Intelligence Center. Periode laporan: <?= $bulanLabel ?>; pembanding: <?= esc($prevLabel) ?>. Program ditampilkan bila periodenya beririsan dengan bulan laporan atau mencatat realisasi pada bulan itu; nilai realisasi (Rp) = voucher terpakai &amp; hadiah dibagikan × nilai satuan.</div>
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
