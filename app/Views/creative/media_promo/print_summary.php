<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan Bulanan Media Promo — <?= $bulan ?></title>
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

<!-- ══ DISTRIBUSI ══ -->
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

<!-- ══ OCCUPANCY ══ -->
<?php if (! empty($spotOccupancy)): ?>
<div class="sec-title"><span>Occupancy Titik Media — <?= $bulanLabel ?></span>
    <span class="sec-sub"><?= count($spotOccupancy) ?> titik &middot; merah &ge; 80% &middot; kuning &ge; 50%</span></div>
<table class="main-table occ-table">
    <thead>
        <tr>
            <th style="width:10%">Kode</th>
            <th>Nama Titik</th>
            <th style="width:11%">Tipe</th>
            <th style="width:20%">Area</th>
            <th class="text-center" style="width:10%">Hari Terpakai</th>
            <th class="text-center" style="width:10%">Kapasitas</th>
            <th class="text-center" style="width:10%">Occupancy</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($spotOccupancy as $o):
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
        <td class="text-center">
            <?= $o['occupied'] ?>
            <?php if ($isDigital): ?><span class="satuan"> slot-hr</span><?php endif; ?>
        </td>
        <td class="text-center" style="color:#94a3b8">
            <?= $o['capacity'] ?>
            <?php if ($isDigital): ?><span class="satuan"> (<?= $s['total_slots'] ?> slot)</span><?php endif; ?>
        </td>
        <td class="text-center"><span class="lencana <?= $cls ?>"><?= $pct ?>%</span></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

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
