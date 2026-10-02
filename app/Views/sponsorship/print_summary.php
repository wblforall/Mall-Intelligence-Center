<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan Bulanan Sponsorship — <?= $bulan ?></title>
<?= $this->include('_laporan/_style') ?>
<style>
/* Hanya aturan KHUSUS laporan ini — dasar bersama dari _laporan/_style.php */
/* Kepala kolom angka rata kanan (th.num kalah spesifik dari .main-table th). */
.main-table th.num { text-align: right; }
.kpi-num.kpi-rp { font-size: 16px; padding-top: 3px; }
.deret-angka b.redup { color: #cbd5e1; }
.subnote { margin-top: 1px; white-space: normal; }

/* Kartu rincian per program / event (selaras panel tema: putih, garis halus, aksen kiri). */
.prog-detail {
    position: relative; margin-bottom: 12px; overflow: hidden;
    border: 1px solid var(--garis); border-radius: 9px; background: #fff;
    break-inside: avoid; page-break-inside: avoid;
}
.prog-detail::before {
    content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 3px; background: var(--navy-3);
}
.prog-detail.event::before { background: #7c3aed; }
.prog-head {
    display: flex; align-items: flex-start; justify-content: space-between; gap: 12px;
    padding: 7px 12px 7px 14px; background: var(--latar); border-bottom: 1px solid var(--garis);
}
.prog-head .ph-title { font-size: 11.5px; font-weight: 700; color: var(--tinta); }
.prog-head .ph-title .lencana { margin-left: 6px; }
.prog-head .ph-meta { font-size: 9px; color: var(--redup); margin-top: 2px; }
.prog-head .ph-meta b { color: var(--tinta); }
.prog-head .ph-kanan { flex: 0 0 auto; display: flex; gap: 5px; align-items: center; padding-top: 1px; }
.detail-table { margin-bottom: 0; }
.detail-table th { background: var(--navy-3); font-size: 8.5px; padding: 4px 8px; }
.detail-table td { font-size: 10px; padding: 4px 8px; }
.detail-table th:first-child, .detail-table td:first-child { padding-left: 14px; }
.detail-table tfoot td { background: #eef2f8; border-top: 1.5px solid var(--navy-3); }
.detail-table tfoot .bln-ini { font-size: 8.5px; font-weight: 600; color: var(--redup); white-space: nowrap; }

/* Analisa per program: label sebagai lencana, isi di sebelahnya. */
.prog-analisa {
    display: grid; grid-template-columns: auto 1fr; gap: 3px 8px; align-items: baseline;
    padding: 6px 12px 7px 14px; background: #fbf8ef; border-top: 1px solid #efe4c4;
    font-size: 9.5px; color: var(--teks);
}
.prog-analisa .lencana { justify-self: start; }

.keterangan { font-size: 9px; color: var(--redup2); margin: 2px 0 0; }
.keterangan em { color: var(--redup); }
/* Blok penutup (kartu terakhir + keterangan + tanda tangan) tidak dipisah halaman. */
.penutup { break-inside: avoid; page-break-inside: avoid; }
.penutup .sign-row { margin-top: 16px; }
.penutup .doc-footer { margin-top: 16px; }
/* Bulan tanpa rincian: rapatkan halaman 1 supaya tanda tangan ikut di halaman yang sama. */
body.ringkas .doc-header { margin-bottom: 10px; }
body.ringkas .sec-title { margin-bottom: 7px; }
body.ringkas .deret-angka > div { padding-top: 5px; padding-bottom: 5px; }
body.ringkas .kpi-row { margin-bottom: 12px; }
body.ringkas .deret-angka { margin-bottom: 12px; }
body.ringkas .chart-panel { margin-bottom: 8px; padding: 10px 12px; }
body.ringkas .chart-wrap { height: 118px; }
body.ringkas .sign-box .sign-space { height: 40px; }
body.ringkas .catatan { margin-bottom: 0; }
body.ringkas .penutup .sign-row { margin-top: 10px; }
body.ringkas .penutup .doc-footer { margin-top: 10px; }
</style>
</head>
<?php
$idBulan = ['January'=>'Januari','February'=>'Februari','March'=>'Maret','April'=>'April',
            'May'=>'Mei','June'=>'Juni','July'=>'Juli','August'=>'Agustus',
            'September'=>'September','October'=>'Oktober','November'=>'November','December'=>'Desember'];
$bulanDt    = \DateTime::createFromFormat('Y-m', $bulan);
$bulanLabel = strtr($bulanDt->format('F Y'), $idBulan);
$prevDt     = \DateTime::createFromFormat('Y-m', $prevBulan);
$prevLabel  = $prevDt ? strtr($prevDt->format('F Y'), $idBulan) : '';

$mallLabel = ['ewalk' => 'eWalk', 'pentacity' => 'Pentacity', 'both' => 'eWalk & Pentacity'];
// Angka gaya Indonesia (titik ribuan, koma desimal).
$nf        = fn($n): string => number_format((float)$n, 0, ',', '.');
$rp        = fn($n) => $n > 0 ? 'Rp ' . number_format($n, 0, ',', '.') : '—';
$pctF      = fn($n): string => rtrim(rtrim(number_format((float)$n, 1, ',', '.'), '0'), ',');
$blnPendek = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
$tglId     = fn(int $t, bool $th = true): string => date('d', $t) . ' ' . $blnPendek[(int)date('n', $t) - 1] . ($th ? ' ' . date('Y', $t) : '');
// Periode ringkas: "01 Jul – 31 Jul 2026" (tahun sekali bila sama).
$fmtPeriode = function (?string $a, ?string $b) use ($tglId): string {
    if (! $a) return '—';
    $ta = strtotime($a);
    if (! $b || $b === $a) return $tglId($ta);
    $tb = strtotime($b);
    return $tglId($ta, date('Y', $ta) !== date('Y', $tb)) . ' – ' . $tglId($tb);
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

// Kartu rincian terakhir dibungkus bersama keterangan + tanda tangan (.penutup)
// supaya tanda tangan tak pernah terdorong sendirian ke halaman baru.
$nProg  = count($programs);
$nEvent = count($eventAggs);
$penutupDibuka = false;
?>

<body class="<?= $nProg === 0 && $nEvent === 0 ? 'ringkas' : '' ?>">

<button class="btn-print no-print" onclick="window.print()">&#128438; Cetak</button>


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
        Program standalone: <?= $nProg ?> &middot; Event: <?= $nEvent ?>
    </div>
</div>

<!-- ══ KPI ══ -->
<?php
// Delta vs bulan lalu (▲/▼ + %) — dipakai di beberapa kartu KPI.
$deltaPct = function (int $now, int $prev) use ($nf): string {
    if ($prev <= 0) return $now > 0 ? '<span class="delta-up">▲ baru</span> vs bln lalu' : '';
    $pct = round(($now - $prev) / $prev * 100);
    $cls = $pct >= 0 ? 'delta-up' : 'delta-down';
    return '<span class="' . $cls . '">' . ($pct >= 0 ? '▲' : '▼') . ' ' . $nf(abs($pct)) . '%</span> vs bln lalu';
};
?>
<div class="kpi-row">
    <div class="kpi-box kpi-deal">
        <div class="kpi-label">Sponsor Deal</div>
        <div class="kpi-num"><?= $nf($kpiSponsorDeal) ?></div>
        <div class="kpi-sub"><?php $d = $kpiSponsorDeal - ($prevSponsorDeal ?? 0); ?>
            <span class="<?= $d >= 0 ? 'delta-up' : 'delta-down' ?>"><?= $d >= 0 ? '+' : '' ?><?= $nf($d) ?></span> vs bln lalu (<?= $nf((int)($prevSponsorDeal ?? 0)) ?>)</div>
    </div>
    <div class="kpi-box kpi-komit">
        <div class="kpi-label">Nilai Komitmen Deal</div>
        <div class="kpi-num kpi-rp"><?= $rp($kpiKomitmen) ?></div>
        <div class="kpi-sub"><?= $deltaPct($kpiKomitmen, (int)($prevKomitmen ?? 0)) ?: 'cash + barang' ?></div>
    </div>
    <div class="kpi-box kpi-real">
        <div class="kpi-label">Penerimaan Bulan Ini</div>
        <div class="kpi-num kpi-rp"><?= $rp($kpiRealisasi) ?></div>
        <div class="kpi-sub"><?= $deltaPct($kpiRealisasi, (int)($kpiRealisasiPrev ?? 0)) ?: 'bulan ' . $bulanLabel ?></div>
    </div>
    <div class="kpi-box kpi-kum">
        <div class="kpi-label">Penerimaan Kumulatif</div>
        <div class="kpi-num kpi-rp"><?= $rp($kpiKumulatif) ?></div>
        <div class="kpi-sub">s/d <?= $bulanLabel ?></div>
    </div>
    <div class="kpi-box kpi-gold">
        <div class="kpi-label">Capaian vs Target</div>
        <div class="kpi-num"><?= $targetNilaiAktif > 0 ? $pctF($capaianPct) . '%' : '—' ?></div>
        <div class="kpi-sub"><?= $targetNilaiAktif > 0 ? 'target program bulan ini ' . $rp($targetNilaiAktif) : 'belum ada target nilai' ?></div>
    </div>
    <div class="kpi-box">
        <div class="kpi-label">Pipeline Berjalan</div>
        <div class="kpi-num"><?= $nf($pipelineTotal['prospek'] + $pipelineTotal['negosiasi']) ?></div>
        <div class="kpi-sub"><?= $nf($pipelineTotal['prospek']) ?> prospek &middot; <?= $nf($pipelineTotal['negosiasi']) ?> negosiasi &middot; <?= $nf($pipelineTotal['batal']) ?> batal</div>
    </div>
</div>

<!-- ══ PROGRAM BARU PER MALL ══ -->
<?php $mc = $mallCounts ?? []; $mcTotal = array_sum($mc); $mcUnset = (int)($mc['unset'] ?? 0); ?>
<div class="sec-title"><span>Program &amp; Event Baru Bulan <?= $bulanLabel ?> — per Mall</span>
    <span class="sec-sub"><?= $mcTotal ?> program/event mulai berjalan bulan ini</span></div>
<div class="deret-angka">
    <div><span>eWalk</span><b><?= (int)($mc['ewalk'] ?? 0) ?></b></div>
    <div><span>Pentacity</span><b><?= (int)($mc['pentacity'] ?? 0) ?></b></div>
    <div><span>Keduanya</span><b><?= (int)($mc['both'] ?? 0) ?></b></div>
    <div><span>Belum Diisi Mall</span><b class="<?= $mcUnset > 0 ? 'waspada' : 'redup' ?>"><?= $mcUnset ?></b></div>
</div>

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

<?php
// Status deal → kelas lencana
$stMap = [
    'prospek'       => ['Prospek', 'netral'],
    'negosiasi'     => ['Negosiasi', 'waspada'],
    'terkonfirmasi' => ['Terkonfirmasi', 'info'],
    'lunas'         => ['Lunas', 'baik'],
    'batal'         => ['Batal', 'buruk'],
];
?>

<!-- ══ DETAIL PROGRAM STANDALONE ══ -->
<?php if (! empty($programs)): ?>
<div class="sec-title"><span>Detail Program Sponsorship &middot; <?= $nProg ?> program</span>
    <span class="sec-sub">rincian per sponsor — pencapaian tim bulan <?= $bulanLabel ?></span></div>
<?php foreach ($programs as $pi => $p):
    $pid   = (int)$p['id'];
    $com   = $committedMap[$pid] ?? [];
    $now   = (int)($monthlyReal[$pid] ?? 0);
    $cum   = (int)($cumReal[$pid] ?? 0);
    $target   = (int)($p['target_nilai'] ?? 0);
    $tSponsor = (int)($p['target_sponsor'] ?? 0);
    $komit    = (int)($com['total_nilai'] ?? 0);
    $spList   = $sponsorsMap[$pid] ?? [];
    $statusLabel = ! empty($p['locked']) ? 'Terkunci' : (['active'=>'Aktif','inactive'=>'Nonaktif'][$p['status']] ?? $p['status']);
    $statusCls   = ! empty($p['locked']) ? 'buruk' : ($p['status'] === 'active' ? 'baik' : 'netral');
    $a  = $analisaMap[$pid] ?? [];
    $hl = $a['highlight'] ?? ''; $kd = $a['kendala'] ?? ''; $tl = $a['tindak_lanjut'] ?? '';
    if ($nEvent === 0 && $pi === $nProg - 1) { echo '<div class="penutup">'; $penutupDibuka = true; }
?>
<div class="prog-detail">
    <div class="prog-head">
        <div>
            <div class="ph-title"><?= esc($p['nama_program']) ?><span class="lencana <?= $statusCls ?>"><?= esc($statusLabel) ?></span></div>
            <div class="ph-meta">
                <?php if (! empty($p['mall'])): ?><?= esc($mallLabel[$p['mall']] ?? ucfirst($p['mall'])) ?> &middot; <?php endif; ?>
                Periode <?= $fmtPeriode($p['tanggal_mulai'] ?? null, $p['tanggal_selesai'] ?? null) ?> &middot;
                Sponsor deal <b><?= $nf((int)($com['total_sponsor'] ?? 0)) ?><?= $tSponsor ? ' / ' . $nf($tSponsor) . ' target' : '' ?></b> &middot;
                Komitmen <b><?= $rp($komit) ?></b> &middot;
                Target <b><?= $target ? $rp($target) : '—' ?></b>
            </div>
        </div>
        <div class="ph-kanan">
            <?php if ($target): $cp = round($cum / $target * 100); ?>
            <span class="lencana <?= $cp >= 100 ? 'baik' : ($cp >= 50 ? 'info' : 'waspada') ?>">capaian <?= $nf($cp) ?>%</span>
            <?php endif; ?>
            <?php if (! ($hl || $kd || $tl)): ?><span class="lencana netral">Analisa belum diisi</span><?php endif; ?>
        </div>
    </div>
    <table class="main-table detail-table">
    <thead><tr>
        <th style="width:5%">#</th>
        <th style="width:30%">Nama Sponsor</th>
        <th style="width:15%">Kategori</th>
        <th style="width:9%">Jenis</th>
        <th class="num" style="width:15%">Nilai Deal</th>
        <th class="text-center" style="width:12%">Status Deal</th>
        <th class="num" style="width:14%">Realisasi</th>
    </tr></thead>
    <tbody>
    <?php if (empty($spList)): ?>
        <tr class="empty-row"><td colspan="7">Belum ada sponsor terdaftar.</td></tr>
    <?php endif; $i = 1; foreach ($spList as $s):
        $sid = (int)$s['id'];
        $rz  = (int)(($realBySponsor[$sid]['total_nilai']) ?? 0);
        $st  = $stMap[$s['status_deal']] ?? [ucfirst((string)$s['status_deal']), 'netral'];
    ?>
        <tr>
            <td class="num" style="text-align:left"><?= $i++ ?></td>
            <td><strong><?= esc($s['nama_sponsor']) ?></strong>
                <?php if (! empty($s['catatan'])): ?><div class="subnote"><?= esc(mb_substr($s['catatan'], 0, 60)) ?></div><?php endif; ?></td>
            <td style="color:var(--redup)"><?= esc($s['kategori'] ?: '—') ?></td>
            <td><?= $s['jenis'] === 'cash' ? 'Cash' : 'Barang' ?></td>
            <td class="<?= (int)$s['nilai'] ? 'num' : 'zero' ?>"><?= $rp((int)$s['nilai']) ?></td>
            <td class="text-center"><span class="lencana <?= $st[1] ?>"><?= esc($st[0]) ?></span></td>
            <td class="<?= $rz ? 'num' : 'zero' ?>"><?= $rz ? $rp($rz) : '—' ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot><tr>
        <td colspan="4">Total &middot; <?= $nf(count($spList)) ?> sponsor</td>
        <td class="num"><?= $rp($komit) ?></td>
        <td class="text-center"><span class="bln-ini">bln ini <?= $now ? $rp($now) : '—' ?></span></td>
        <td class="num"><?= $cum ? $rp($cum) : '—' ?></td>
    </tr></tfoot>
    </table>
    <?php if ($hl || $kd || $tl): ?>
    <div class="prog-analisa">
        <?php if ($hl): ?><span class="lencana baik">Highlight</span><div><?= esc($hl) ?></div><?php endif; ?>
        <?php if ($kd): ?><span class="lencana waspada">Kendala</span><div><?= esc($kd) ?></div><?php endif; ?>
        <?php if ($tl): ?><span class="lencana info">Tindak Lanjut</span><div><?= esc($tl) ?></div><?php endif; ?>
    </div>
    <?php endif; ?>
</div>
<?php endforeach; ?>
<?php endif; ?>

<!-- ══ DETAIL SPONSOR EVENT ══ -->
<?php if (! empty($eventAggs)): ?>
<div class="sec-title"><span>Detail Sponsorship — Support Event &middot; <?= $nEvent ?> event</span>
    <span class="sec-sub">rincian sponsor per event</span></div>
<?php foreach ($eventAggs as $ei => $e):
    $eid   = (int)$e['event_id'];
    $now   = (int)($evMonthly[$eid] ?? 0);
    $cum   = (int)($evCum[$eid] ?? 0);
    $deal  = (int)$e['total_cash'] + (int)$e['total_barang'];
    $spList = $eventSponsors[$eid] ?? [];
    if ($ei === $nEvent - 1) { echo '<div class="penutup">'; $penutupDibuka = true; }
?>
<div class="prog-detail event">
    <div class="prog-head">
        <div>
            <div class="ph-title"><?= esc($e['event_name']) ?><span class="lencana ungu">Event</span></div>
            <div class="ph-meta">
                <?= esc($mallLabel[$e['event_mall']] ?? ucfirst((string)$e['event_mall'])) ?> &middot;
                Mulai <?= $e['event_start_date'] ? $tglId(strtotime($e['event_start_date'])) : '—' ?> &middot;
                Sponsor <b><?= $nf((int)$e['jumlah_sponsor']) ?></b> &middot; Nilai deal <b><?= $rp($deal) ?></b>
            </div>
        </div>
    </div>
    <table class="main-table detail-table">
    <thead><tr>
        <th style="width:5%">#</th>
        <th style="width:35%">Nama Sponsor</th>
        <th style="width:12%">Jenis</th>
        <th class="num" style="width:10%">Qty</th>
        <th class="num" style="width:19%">Nilai</th>
        <th class="num" style="width:19%">Realisasi</th>
    </tr></thead>
    <tbody>
    <?php if (empty($spList)): ?>
        <tr class="empty-row"><td colspan="6">Belum ada sponsor terdaftar.</td></tr>
    <?php endif; $i = 1; foreach ($spList as $s):
        $sid = (int)$s['id'];
        $rz  = (int)(($eventRealBySp[$eid][$sid]) ?? 0);
        $qty = (int)($s['qty'] ?? 0);
    ?>
        <tr>
            <td class="num" style="text-align:left"><?= $i++ ?></td>
            <td><strong><?= esc($s['nama_sponsor']) ?></strong>
                <?php if (! empty($s['deskripsi_barang'])): ?><div class="subnote"><?= esc(mb_substr($s['deskripsi_barang'], 0, 60)) ?></div><?php endif; ?></td>
            <td><?= $s['jenis'] === 'cash' ? 'Cash' : 'Barang' ?></td>
            <td class="<?= $qty ? 'num' : 'zero' ?>"><?= $qty ? $nf($qty) : '—' ?></td>
            <td class="<?= (int)$s['nilai'] ? 'num' : 'zero' ?>"><?= $rp((int)$s['nilai']) ?></td>
            <td class="<?= $rz ? 'num' : 'zero' ?>"><?= $rz ? $rp($rz) : '—' ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot><tr>
        <td colspan="4">Total &middot; <?= $nf(count($spList)) ?> sponsor</td>
        <td class="num"><?= $rp($deal) ?></td>
        <td class="num"><?= $cum ? $rp($cum) : ($now ? $rp($now) : '—') ?></td>
    </tr></tfoot>
    </table>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php if (! $penutupDibuka): ?><div class="penutup"><?php endif; ?>
<?php if ($nProg === 0 && $nEvent === 0): ?>
<div class="catatan info">Tidak ada program sponsorship maupun event bersponsor yang berjalan atau menerima pembayaran pada <?= $bulanLabel ?>.</div>
<?php else: ?>
<div class="keterangan">
    Keterangan: <em>Nilai Deal</em> = komitmen sponsor (nilai kontrak) · <em>Realisasi</em> = pembayaran yang sudah masuk (kumulatif).
    Status: Prospek → Negosiasi → Terkonfirmasi → Lunas (Batal = gagal).
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
