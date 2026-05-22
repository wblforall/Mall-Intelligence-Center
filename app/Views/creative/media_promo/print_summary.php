<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan Bulanan Media Promo — <?= $bulan ?></title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: Arial, sans-serif; font-size: 11px; color: #111; background: #fff; }

@page { size: A4 portrait; margin: 14mm 16mm 12mm; }
@media print {
    .no-print { display: none !important; }
    body { font-size: 10.5px; }
    .page-break { page-break-before: always; }
}

/* ── Header ── */
.doc-header {
    border-bottom: 3px solid #1e293b;
    padding-bottom: 10px;
    margin-bottom: 16px;
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
}
.doc-header .left .title  { font-size: 17px; font-weight: 700; color: #1e293b; }
.doc-header .left .sub    { font-size: 12px; color: #475569; margin-top: 2px; }
.doc-header .left .org    { font-size: 9px; color: #94a3b8; margin-top: 5px; }
.doc-header .right        { text-align: right; font-size: 9px; color: #64748b; line-height: 1.8; }

/* ── Section title ── */
.sec-title {
    font-size: 10px; font-weight: 700; color: #475569;
    text-transform: uppercase; letter-spacing: .5px;
    border-bottom: 1px solid #e2e8f0;
    padding-bottom: 4px; margin-bottom: 10px; margin-top: 18px;
}
.sec-title:first-of-type { margin-top: 0; }

/* ── KPI row ── */
.kpi-row { display: flex; gap: 10px; margin-bottom: 18px; }
.kpi-box {
    flex: 1; border: 1px solid #e2e8f0; border-radius: 6px;
    padding: 10px 12px; background: #f8fafc;
}
.kpi-box .kpi-label { font-size: 9px; color: #64748b; margin-bottom: 4px; }
.kpi-box .kpi-num   { font-size: 22px; font-weight: 700; line-height: 1; }
.kpi-box .kpi-sub   { font-size: 8.5px; color: #94a3b8; margin-top: 3px; }
.kpi-total  { border-color: #bfdbfe; background: #eff6ff; }
.kpi-total .kpi-num  { color: #1d4ed8; }
.kpi-ok     { border-color: #bbf7d0; background: #f0fdf4; }
.kpi-ok .kpi-num     { color: #15803d; }
.kpi-warn   { border-color: #fde68a; background: #fffbeb; }
.kpi-warn .kpi-num   { color: #b45309; }
.kpi-danger { border-color: #fecaca; background: #fef2f2; }
.kpi-danger .kpi-num { color: #b91c1c; }

/* ── Two-col layout ── */
.two-col { display: flex; gap: 14px; margin-bottom: 4px; }
.two-col > div { flex: 1; }

/* ── Dist table ── */
.dist-table { width: 100%; border-collapse: collapse; }
.dist-table td { padding: 5px 8px; border: 1px solid #e2e8f0; font-size: 10px; }
.dist-table td:last-child { text-align: right; font-weight: 700; width: 40px; }
.dist-table tr:nth-child(even) td { background: #f8fafc; }
.dist-label { font-size: 9.5px; color: #475569; font-weight: 700;
    background: #f1f5f9 !important; padding: 4px 8px !important; }

/* ── Badge ── */
.badge {
    display: inline-block; padding: 1px 7px; border-radius: 3px;
    font-size: 8.5px; font-weight: 700;
}
.badge-internal { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }
.badge-tenant   { background: #e0f2fe; color: #075985; border: 1px solid #bae6fd; }
.badge-external { background: #fef9c3; color: #713f12; border: 1px solid #fde68a; }

/* ── Occupancy table ── */
.occ-table { width: 100%; border-collapse: collapse; }
.occ-table th {
    background: #1e293b; color: #f1f5f9;
    font-size: 9px; padding: 5px 7px;
    border: 1px solid #334155; text-align: left; white-space: nowrap;
}
.occ-table td { padding: 5px 7px; border: 1px solid #e2e8f0; font-size: 10px; }
.occ-table tr:nth-child(even) td { background: #f8fafc; }
.occ-table td.text-center { text-align: center; }
.occ-table td.text-right  { text-align: right; }

/* pct color */
.pct-ok     { color: #15803d; font-weight: 700; }
.pct-warn   { color: #b45309; font-weight: 700; }
.pct-danger { color: #b91c1c; font-weight: 700; }

/* tipe pill */
.tipe-pill {
    display: inline-block; padding: 1px 7px; border-radius: 10px;
    font-size: 8px; font-weight: 700; white-space: nowrap;
}
.tipe-t_banner        { background: #dbeafe; color: #1e40af; }
.tipe-hanging         { background: #e0f2fe; color: #075985; }
.tipe-sticker_lift    { background: #fef9c3; color: #713f12; }
.tipe-totem_stainless { background: #f1f5f9; color: #475569; }
.tipe-digital         { background: #1e293b; color: #f1f5f9; }

/* ── Footer ── */
.doc-footer {
    margin-top: 20px; border-top: 1px solid #e2e8f0;
    padding-top: 7px; display: flex; justify-content: space-between;
    font-size: 8.5px; color: #94a3b8;
}

/* ── Print button ── */
.btn-print {
    position: fixed; top: 16px; right: 16px;
    background: #1e293b; color: #fff; border: none;
    padding: 8px 18px; border-radius: 6px; cursor: pointer;
    font-size: 12px; box-shadow: 0 2px 8px rgba(0,0,0,.2);
}
.btn-print:hover { background: #334155; }

/* ── Signature block ── */
.sign-row { display: flex; gap: 24px; margin-top: 28px; }
.sign-box  {
    flex: 1; border: 1px solid #e2e8f0; border-radius: 6px;
    padding: 10px 14px 50px; text-align: center;
    font-size: 9.5px; color: #475569;
}
.sign-box .sign-role { font-weight: 700; color: #1e293b; font-size: 10px; margin-top: 4px; }
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
$tipeOrder   = ['t_banner','hanging','sticker_lift','totem_stainless','digital'];

$totalActive = ($statusCounts['approved'] ?? 0) + ($statusCounts['done'] ?? 0);
?>

<!-- ══ HEADER ══ -->
<div class="doc-header">
    <div class="left">
        <div class="title">Laporan Bulanan — Media Promo</div>
        <div class="sub"><?= $bulanLabel ?></div>
        <div class="org">PT. Wulandari Bangun Laksana Tbk. &mdash; IT Department &mdash; Mall Intelligence Center</div>
    </div>
    <div class="right">
        Periode: <?= date('d M Y', strtotime($bulanMulai)) ?> s/d <?= date('d M Y', strtotime($bulanSelesai)) ?><br>
        Dicetak oleh: <?= esc($printedBy) ?><br>
        Tanggal cetak: <?= $printedAt ?>
    </div>
</div>

<!-- ══ KPI ══ -->
<div class="kpi-row">
    <div class="kpi-box kpi-total">
        <div class="kpi-label">Total Request</div>
        <div class="kpi-num"><?= $totalRequest ?></div>
        <div class="kpi-sub">bulan <?= $bulanLabel ?></div>
    </div>
    <div class="kpi-box kpi-ok">
        <div class="kpi-label">Approved / Done</div>
        <div class="kpi-num"><?= $totalActive ?></div>
        <div class="kpi-sub"><?= $statusCounts['approved'] ?? 0 ?> approved &middot; <?= $statusCounts['done'] ?? 0 ?> done</div>
    </div>
    <div class="kpi-box kpi-warn">
        <div class="kpi-label">Pending Approval</div>
        <div class="kpi-num"><?= $statusCounts['pending'] ?? 0 ?></div>
        <div class="kpi-sub">menunggu approval</div>
    </div>
    <div class="kpi-box kpi-danger">
        <div class="kpi-label">Ditolak</div>
        <div class="kpi-num"><?= $statusCounts['rejected'] ?? 0 ?></div>
        <div class="kpi-sub"><?= $statusCounts['draft'] ?? 0 ?> masih draft</div>
    </div>
</div>

<!-- ══ DISTRIBUSI ══ -->
<div class="sec-title">Distribusi Request</div>
<div class="two-col">
    <!-- Sumber -->
    <div>
        <table class="dist-table">
            <tr><td colspan="2" class="dist-label">Sumber Materi</td></tr>
            <?php foreach (['internal','tenant','external'] as $src):
                if (empty($sumberCounts[$src])) continue; ?>
            <tr>
                <td><span class="badge badge-<?= $src ?>"><?= $sumberLabel[$src] ?></span></td>
                <td><?= $sumberCounts[$src] ?></td>
            </tr>
            <?php endforeach; ?>
            <tr><td colspan="2" class="dist-label">Status Biaya</td></tr>
            <tr>
                <td>Berbayar</td>
                <td><?= $berbayarCount ?></td>
            </tr>
            <tr>
                <td>Gratis</td>
                <td><?= $totalRequest - $berbayarCount ?></td>
            </tr>
        </table>
    </div>

    <!-- Tipe & Dept -->
    <div>
        <table class="dist-table">
            <tr><td colspan="2" class="dist-label">Request per Tipe Media</td></tr>
            <?php foreach ($tipeOrder as $t):
                if (empty($tipeCounts[$t])) continue; ?>
            <tr>
                <td><span class="tipe-pill tipe-<?= $t ?>"><?= $tipeLabel[$t] ?></span></td>
                <td><?= $tipeCounts[$t] ?></td>
            </tr>
            <?php endforeach; ?>
            <tr><td colspan="2" class="dist-label">Request per Departemen</td></tr>
            <?php foreach ($deptCounts as $dept => $cnt): ?>
            <tr>
                <td><?= esc($dept) ?></td>
                <td><?= $cnt ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>

<!-- ══ OCCUPANCY ══ -->
<?php if (! empty($spotOccupancy)): ?>
<div class="sec-title" style="margin-top:20px">Occupancy Titik Media — <?= $bulanLabel ?></div>
<table class="occ-table">
    <thead>
        <tr>
            <th style="width:8%">Kode</th>
            <th>Nama Titik</th>
            <th style="width:14%">Tipe</th>
            <th style="width:10%">Area</th>
            <th class="text-center" style="width:11%">Hari Terpakai</th>
            <th class="text-center" style="width:10%">Kapasitas</th>
            <th class="text-center" style="width:10%">Occupancy</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($spotOccupancy as $o):
        $s   = $o['spot'];
        $pct = $o['pct'];
        $cls = $pct >= 80 ? 'pct-danger' : ($pct >= 50 ? 'pct-warn' : 'pct-ok');
        $isDigital = $s['tipe'] === 'digital';
    ?>
    <tr>
        <td style="font-family:monospace;font-weight:700"><?= esc($s['kode']) ?></td>
        <td><?= esc($s['nama']) ?></td>
        <td><span class="tipe-pill tipe-<?= $s['tipe'] ?>"><?= $tipeLabel[$s['tipe']] ?? esc($s['tipe']) ?></span></td>
        <td style="color:#64748b"><?= esc($s['area'] ?? '—') ?></td>
        <td class="text-center">
            <?= $o['occupied'] ?>
            <?php if ($isDigital): ?><span style="font-size:8px;color:#94a3b8"> slot-hr</span><?php endif; ?>
        </td>
        <td class="text-center" style="color:#94a3b8">
            <?= $o['capacity'] ?>
            <?php if ($isDigital): ?><span style="font-size:8px"> (<?= $s['total_slots'] ?> slot)</span><?php endif; ?>
        </td>
        <td class="text-center"><span class="<?= $cls ?>"><?= $pct ?>%</span></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<!-- ══ TANDA TANGAN ══ -->
<div class="sign-row">
    <div class="sign-box">
        Dibuat oleh<br>
        <span class="sign-role">Creative & Design</span>
    </div>
    <div class="sign-box">
        Mengetahui<br>
        <span class="sign-role">Kepala Departemen</span>
    </div>
    <div class="sign-box">
        Menyetujui<br>
        <span class="sign-role">General Manager</span>
    </div>
</div>

<!-- ══ FOOTER ══ -->
<div class="doc-footer">
    <span>Mall Intelligence Center v1.9 &mdash; IT Department PT. Wulandari Bangun Laksana Tbk.</span>
    <span>Digenerate otomatis &mdash; <?= $printedAt ?></span>
</div>

</body>
</html>
