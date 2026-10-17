<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<?php $__bd = \DateTime::createFromFormat('Y-m', $bulan); $__id = ['January'=>'Januari','February'=>'Februari','March'=>'Maret','April'=>'April','May'=>'Mei','June'=>'Juni','July'=>'Juli','August'=>'Agustus','September'=>'September','October'=>'Oktober','November'=>'November','December'=>'Desember']; ?>
<title>Laporan Bulanan Media Promo — <?= strtr($__bd->format('F Y'), $__id) ?></title>
<?= $this->include('_laporan/_style') ?>
<style>
/* Khusus laporan Media Promo */
.kpi-red .kpi-num { color: #b91c1c; }

/* Distribusi: empat tabel ringkas berdampingan */
.dist .main-table { margin-bottom: 18px; }
.dist .main-table td:last-child, .dist .main-table th:last-child { text-align: right; width: 56px; }
.dist .main-table td:last-child { font-weight: 700; color: var(--tinta); }

/* Pil tipe media (warna per tipe dipertahankan) */
.tipe-pill {
    display: inline-block; padding: 1.5px 8px; border-radius: 999px; white-space: nowrap;
    font-size: 8.5px; font-weight: 600; line-height: 1.45; vertical-align: 1px;
}
.tipe-t_banner        { background: #dbeafe; color: #1e40af; }
.tipe-hanging         { background: #e0f2fe; color: #075985; }
.tipe-sticker_lift    { background: #fef3c7; color: #92400e; }
.tipe-totem_stainless { background: #f1f5f9; color: #475569; }
.tipe-digital         { background: var(--navy-2); color: #fff; }

.kode { font-family: 'SFMono-Regular', Menlo, Consolas, monospace; font-size: 9.5px; font-weight: 700; color: var(--tinta); }
.occ-table td { font-size: 10px; }
.occ-table .area { color: var(--redup); font-size: 9.5px; }
.occ-table .satuan { font-size: 8px; color: var(--redup2); }

/* Tanda tangan + kaki dokumen tidak terpisah halaman */
.penutup { break-inside: avoid; page-break-inside: avoid; }
/* Tanda tangan: peran tercetak di bawah garis, nama diisi tangan */
.sign-box .sign-jabatan.peran { font-size: 9.5px; font-weight: 600; color: var(--teks); }
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

$tipeLabel = ['t_banner'=>'T-Banner','hanging'=>'Hanging','sticker_lift'=>'Sticker Lift',
              'totem_stainless'=>'Totem Stainless','digital'=>'Digital'];
$sumberLabel = ['internal'=>'Internal Manajemen','tenant'=>'Tenant Mall','external'=>'External Client'];
$sumberKelas = ['internal'=>'netral','tenant'=>'info','external'=>'waspada'];
$tipeOrder   = ['t_banner','hanging','sticker_lift','totem_stainless','digital'];

$totalActive = ($statusCounts['approved'] ?? 0) + ($statusCounts['done'] ?? 0);
?>

<!-- ══ HEADER ══ -->
<div class="doc-header">
    <div>
        <div class="title">Laporan Bulanan — Media Promo</div>
        <div class="sub"><?= $bulanLabel ?></div>
        <div class="org">PT. Wulandari Bangun Laksana Tbk. &mdash; IT Department &mdash; Mall Intelligence Center</div>
    </div>
    <div class="meta">
        Periode: <?= date('d M Y', strtotime($bulanMulai)) ?> s/d <?= date('d M Y', strtotime($bulanSelesai)) ?><br>
        Dicetak oleh: <?= esc($printedBy) ?><br>
        Tanggal cetak: <?= $printedAt ?>
    </div>
</div>

<!-- ══ KPI ══ -->
<div class="kpi-row">
    <div class="kpi-box kpi-blue">
        <div class="kpi-label">Total Request</div>
        <div class="kpi-num"><?= $totalRequest ?></div>
        <div class="kpi-sub">bulan <?= $bulanLabel ?></div>
    </div>
    <div class="kpi-box kpi-green">
        <div class="kpi-label">Approved / Done</div>
        <div class="kpi-num"><?= $totalActive ?></div>
        <div class="kpi-sub"><?= $statusCounts['approved'] ?? 0 ?> approved &middot; <?= $statusCounts['done'] ?? 0 ?> done</div>
    </div>
    <div class="kpi-box kpi-amber">
        <div class="kpi-label">Pending Approval</div>
        <div class="kpi-num"><?= $statusCounts['pending'] ?? 0 ?></div>
        <div class="kpi-sub">menunggu approval</div>
    </div>
    <div class="kpi-box kpi-red">
        <div class="kpi-label">Ditolak</div>
        <div class="kpi-num"><?= $statusCounts['rejected'] ?? 0 ?></div>
        <div class="kpi-sub"><?= $statusCounts['draft'] ?? 0 ?> masih draft</div>
    </div>
</div>

<?php
// ── Turunan data untuk ringkasan & analisa (tanpa karangan) ──
$usages   = $usages ?? [];
$aktifOcc = array_filter($spotOccupancy, fn($o) => $o['occupied'] > 0);
$idleOcc  = array_filter($spotOccupancy, fn($o) => $o['occupied'] <= 0);
$totalTitik = count($spotOccupancy);
$sumOcc = array_sum(array_column($spotOccupancy, 'occupied'));
$sumCap = array_sum(array_column($spotOccupancy, 'capacity'));
$occRata = $sumCap > 0 ? $sumOcc / $sumCap * 100 : 0;
$fmtPct = fn($v) => number_format($v, 1, ',', '.') . '%';
$perTipe = [];
foreach ($spotOccupancy as $o) {
    $t = $o['spot']['tipe'];
    $perTipe[$t]['titik'] = ($perTipe[$t]['titik'] ?? 0) + 1;
    $perTipe[$t]['aktif'] = ($perTipe[$t]['aktif'] ?? 0) + ($o['occupied'] > 0 ? 1 : 0);
    $perTipe[$t]['occ']   = ($perTipe[$t]['occ'] ?? 0) + $o['occupied'];
    $perTipe[$t]['cap']   = ($perTipe[$t]['cap'] ?? 0) + $o['capacity'];
}
$temuan = [];
if ($totalRequest > 0) {
    $temuan[] = "Terdapat {$totalRequest} request media pada {$bulanLabel}: " . ($statusCounts['approved'] ?? 0) . ' disetujui, ' . ($statusCounts['done'] ?? 0) . ' selesai, ' . ($statusCounts['pending'] ?? 0) . ' menunggu approval, ' . ($statusCounts['rejected'] ?? 0) . ' ditolak.';
    if (! empty($sumberCounts)) { $sm = array_keys($sumberCounts, max($sumberCounts))[0]; if ($sumberCounts[$sm] > 0) $temuan[] = 'Sumber materi terbanyak: ' . $sumberLabel[$sm] . ' (' . $sumberCounts[$sm] . ' request, ' . round($sumberCounts[$sm] / $totalRequest * 100) . '%). Request berbayar ' . $berbayarCount . ' dari ' . $totalRequest . '.'; }
    if ($deptCounts) { $dm = array_key_first($deptCounts); $temuan[] = 'Departemen pemohon terbanyak: ' . $dm . ' (' . $deptCounts[$dm] . ' request).'; }
    if ($aktifOcc) { $teratas = reset($aktifOcc); $temuan[] = "Rata-rata okupansi seluruh titik {$fmtPct($occRata)}; " . count($aktifOcc) . " dari {$totalTitik} titik terpakai bulan ini. Tertinggi: " . $teratas['spot']['kode'] . ' (' . $teratas['pct'] . '%).'; }
    $penuh = array_filter($spotOccupancy, fn($o) => $o['pct'] >= 80);
    if ($penuh) $temuan[] = count($penuh) . ' titik berokupansi ≥ 80% (' . implode(', ', array_map(fn($o) => $o['spot']['kode'], array_slice(array_values($penuh), 0, 6))) . ') — pertimbangkan titik alternatif untuk request berikutnya.';
    if ($idleOcc) $temuan[] = count($idleOcc) . ' titik belum terpakai sama sekali; peluang penawaran ke tenant/sponsor.';
}
?>
<div class="sec-title tanpa-nomor"><span>Ringkasan &amp; Temuan Utama</span><span class="sec-sub">diturunkan dari data request &amp; okupansi bulan ini</span></div>
<?php if ($temuan): ?>
<div class="insight-box analisa" style="margin-bottom:18px"><div class="insight-title">Analisa Otomatis</div>
<ul class="insight-list"><?php foreach ($temuan as $t): ?><li><?= esc($t) ?></li><?php endforeach; ?></ul></div>
<?php else: ?>
<div class="catatan waspada">Tidak ada request media pada <?= $bulanLabel ?>. Seluruh <?= $totalTitik ?> titik media tercatat kosong pada periode ini.</div>
<?php endif; ?>

<!-- ══ DISTRIBUSI ══ -->
<?php if ($totalRequest > 0): ?>
<div class="sec-title"><span>Distribusi Request</span>
    <span class="sec-sub"><?= $totalRequest ?> request &middot; <?= $bulanLabel ?></span></div>
<div class="duo dist">
    <div>
        <table class="main-table">
            <thead><tr><th>Sumber Materi</th><th>Jumlah</th></tr></thead>
            <tbody>
            <?php foreach (['internal','tenant','external'] as $src):
                if (empty($sumberCounts[$src])) continue; ?>
            <tr>
                <td><span class="lencana <?= $sumberKelas[$src] ?>"><?= $sumberLabel[$src] ?></span></td>
                <td><?= $sumberCounts[$src] ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div>
        <table class="main-table">
            <thead><tr><th>Status Biaya</th><th>Jumlah</th></tr></thead>
            <tbody>
            <tr><td>Berbayar</td><td><?= $berbayarCount ?></td></tr>
            <tr><td>Gratis</td><td><?= $totalRequest - $berbayarCount ?></td></tr>
            </tbody>
        </table>
    </div>
    <div>
        <table class="main-table">
            <thead><tr><th>Request per Tipe Media</th><th>Jumlah</th></tr></thead>
            <tbody>
            <?php foreach ($tipeOrder as $t):
                if (empty($tipeCounts[$t])) continue; ?>
            <tr>
                <td><span class="tipe-pill tipe-<?= $t ?>"><?= $tipeLabel[$t] ?></span></td>
                <td><?= $tipeCounts[$t] ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div>
        <table class="main-table">
            <thead><tr><th>Request per Departemen</th><th>Jumlah</th></tr></thead>
            <tbody>
            <?php foreach ($deptCounts as $dept => $cnt): ?>
            <tr>
                <td><?= esc($dept) ?></td>
                <td><?= $cnt ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php endif; ?>
<!-- ══ OCCUPANCY ══ -->
<?php if (! empty($spotOccupancy)): ?>
<div class="sec-title"><span>Okupansi per Tipe Media</span><span class="sec-sub">hari terpakai ÷ kapasitas (hari × slot)</span></div>
<table class="main-table">
<thead><tr><th>Tipe</th><th class="num">Titik</th><th class="num">Titik terpakai</th><th class="num">Hari terpakai</th><th class="num">Kapasitas</th><th class="num">Okupansi</th><th style="width:24%">&nbsp;</th></tr></thead>
<tbody>
<?php foreach ($tipeOrder as $t): if (empty($perTipe[$t])) continue; $x = $perTipe[$t]; $pp = $x['cap'] > 0 ? $x['occ'] / $x['cap'] * 100 : 0; ?>
<tr><td><span class="tipe-pill tipe-<?= $t ?>"><?= $tipeLabel[$t] ?></span></td>
<td class="num"><?= $x['titik'] ?></td><td class="num"><?= $x['aktif'] ?></td><td class="num"><?= number_format($x['occ'], 0, ',', '.') ?></td><td class="num"><?= number_format($x['cap'], 0, ',', '.') ?></td>
<td class="num"><strong><?= $fmtPct($pp) ?></strong></td>
<td><div style="height:7px;border-radius:4px;background:#e2e8f0"><div style="height:7px;border-radius:4px;background:#1e3a6e;width:<?= min(100, max($pp > 0 ? 1 : 0, round($pp))) ?>%"></div></div></td></tr>
<?php endforeach; ?>
</tbody>
<tfoot><tr><td>Semua titik</td><td class="num"><?= $totalTitik ?></td><td class="num"><?= count($aktifOcc) ?></td><td class="num"><?= number_format($sumOcc, 0, ',', '.') ?></td><td class="num"><?= number_format($sumCap, 0, ',', '.') ?></td><td class="num"><?= $fmtPct($occRata) ?></td><td></td></tr></tfoot>
</table>

<div class="sec-title"><span>Okupansi Titik Terpakai — <?= $bulanLabel ?></span>
    <span class="sec-sub"><?= count($aktifOcc) ?> dari <?= $totalTitik ?> titik &middot; merah &ge; 80% &middot; kuning &ge; 50%</span></div>
<?php if ($aktifOcc): ?>
<table class="main-table occ-table">
    <thead>
        <tr>
            <th style="width:10%">Kode</th>
            <th>Nama Titik</th>
            <th style="width:11%">Tipe</th>
            <th style="width:20%">Area</th>
            <th class="num" style="width:11%">Hari Terpakai</th>
            <th class="num" style="width:11%">Kapasitas</th>
            <th class="text-center" style="width:10%">Okupansi</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($aktifOcc as $o):
        $s   = $o['spot'];
        $pct = $o['pct'];
        $cls = $pct >= 80 ? 'buruk' : ($pct >= 50 ? 'waspada' : 'baik');
        $isDigital = $s['tipe'] === 'digital';
    ?>
    <tr>
        <td class="kode"><?= esc($s['kode']) ?></td>
        <td><?= esc($s['nama']) ?></td>
        <td><span class="tipe-pill tipe-<?= $s['tipe'] ?>"><?= $tipeLabel[$s['tipe']] ?? esc($s['tipe']) ?></span></td>
        <td class="area"><?= esc($s['area'] ?? '—') ?></td>
        <td class="num"><?= $o['occupied'] ?><?php if ($isDigital): ?><span class="satuan"> slot-hari</span><?php endif; ?></td>
        <td class="num" style="color:#94a3b8"><?= $o['capacity'] ?><?php if ($isDigital): ?><span class="satuan"> (<?= $s['total_slots'] ?> slot)</span><?php endif; ?></td>
        <td class="text-center"><span class="lencana <?= $cls ?>"><?= $pct ?>%</span></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
<div class="catatan waspada">Tidak ada titik media yang terpakai pada <?= $bulanLabel ?>.</div>
<?php endif; ?>
<?php if ($idleOcc): ?>
<div class="catatan"><b>Titik belum terpakai (<?= count($idleOcc) ?>):</b>
    <?= esc(implode(', ', array_map(fn($o) => $o['spot']['kode'], $idleOcc))) ?>.</div>
<?php endif; ?>
<?php endif; ?>

<?php
// ── Catatan request: catatan pemohon & approver / alasan penolakan (apa adanya) ──
$bercatatan = array_filter($usages, fn($u) => trim(($u['catatan_pemohon'] ?? '') . ($u['catatan_approver'] ?? '') . ($u['rejection_reason'] ?? '')) !== '');
// gabungkan baris identik (satu materi tayang di banyak titik)
$grupCat = [];
foreach ($bercatatan as $u) {
    $k = md5($u['nama_materi'] . '|' . $u['dept'] . '|' . $u['catatan_pemohon'] . '|' . $u['catatan_approver'] . '|' . $u['rejection_reason'] . '|' . $u['tanggal_mulai'] . '|' . $u['tanggal_selesai']);
    $grupCat[$k]['u'] = $u; $grupCat[$k]['titik'][] = $u['spot_kode'];
}
?>
<div class="sec-title"><span>Catatan &amp; Tindak Lanjut</span><span class="sec-sub">dari catatan pemohon, approver, dan alasan penolakan</span></div>
<?php if ($grupCat): ?>
<table class="main-table">
<thead><tr><th style="width:20%">Materi</th><th style="width:12%">Dept</th><th style="width:15%">Titik</th><th style="width:11%">Periode</th><th>Catatan</th></tr></thead>
<tbody>
<?php foreach ($grupCat as $g): $u = $g['u']; $tt = array_values(array_unique($g['titik'])); ?>
<tr><td><strong><?= esc($u['nama_materi']) ?></strong></td><td><?= esc($u['dept']) ?></td>
<td><?= esc(implode(', ', array_slice($tt, 0, 4))) ?><?= count($tt) > 4 ? ' +' . (count($tt) - 4) : '' ?></td>
<td style="white-space:nowrap"><?= date('d/m/y', strtotime($u['tanggal_mulai'])) ?> – <?= date('d/m/y', strtotime($u['tanggal_selesai'])) ?></td>
<td><?php if (trim((string)$u['catatan_pemohon']) !== ''): ?><?= esc($u['catatan_pemohon']) ?><?php endif; ?>
<?php if (trim((string)$u['catatan_approver']) !== ''): ?><br><b>Approver:</b> <?= esc($u['catatan_approver']) ?><?php endif; ?>
<?php if (trim((string)$u['rejection_reason']) !== ''): ?><br><span class="lencana buruk">Ditolak</span> <?= esc($u['rejection_reason']) ?><?php endif; ?></td></tr>
<?php endforeach; ?>
</tbody></table>
<?php else: ?>
<div class="catatan">Tidak ada catatan pemohon/approver pada request bulan ini.</div>
<?php endif; ?>
<div class="catatan"><b>Catatan sumber:</b> data dari modul Media Promo MIC (booking titik media). Okupansi dihitung dari request berstatus approved/done: titik fisik = hari terpakai ÷ hari dalam bulan; titik digital = slot-hari ÷ (hari × jumlah slot). Rincian per booking ada di Booking Sheet Media Promo.</div>

<!-- ══ TANDA TANGAN ══ -->
<div class="penutup">
<div class="sign-row">
    <div class="sign-box">
        <div class="sign-label">Dibuat oleh</div>
        <div class="sign-space"></div>
        <span class="sign-role">&nbsp;</span>
        <div class="sign-jabatan peran">Creative &amp; Design</div>
    </div>
    <div class="sign-box">
        <div class="sign-label">Mengetahui</div>
        <div class="sign-space"></div>
        <span class="sign-role">&nbsp;</span>
        <div class="sign-jabatan peran">Kepala Departemen</div>
    </div>
    <div class="sign-box">
        <div class="sign-label">Menyetujui</div>
        <div class="sign-space"></div>
        <span class="sign-role">&nbsp;</span>
        <div class="sign-jabatan peran">General Manager</div>
    </div>
</div>

<!-- ══ FOOTER ══ -->
<div class="doc-footer">
    <span>Mall Intelligence Center v1.9 &mdash; IT Department PT. Wulandari Bangun Laksana Tbk.</span>
    <span>Digenerate otomatis &mdash; <?= $printedAt ?></span>
</div>
</div>

</body>
</html>
