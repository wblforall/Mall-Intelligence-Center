<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan Bulanan Loyalty — <?= $bulan ?></title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: Arial, sans-serif; font-size: 10.5px; color: #111; background: #fff; }

@page { size: A4 landscape; margin: 12mm 14mm 10mm; }
@media print {
    .no-print { display: none !important; }
    body { font-size: 10px; }
}

/* ── Header ── */
.doc-header {
    border-bottom: 3px solid #1e293b; padding-bottom: 10px; margin-bottom: 16px;
    display: flex; justify-content: space-between; align-items: flex-end;
}
.doc-header .title { font-size: 17px; font-weight: 700; color: #1e293b; }
.doc-header .sub   { font-size: 12px; color: #475569; margin-top: 2px; }
.doc-header .org   { font-size: 9px; color: #94a3b8; margin-top: 5px; }
.doc-header .meta  { text-align: right; font-size: 9px; color: #64748b; line-height: 1.8; }

/* ── KPI ── */
.kpi-row { display: flex; gap: 10px; margin-bottom: 16px; }
.kpi-box {
    flex: 1; border: 1px solid #e2e8f0; border-radius: 6px;
    padding: 9px 12px; background: #f8fafc;
}
.kpi-label { font-size: 8.5px; color: #64748b; margin-bottom: 3px; }
.kpi-num   { font-size: 20px; font-weight: 700; line-height: 1.1; }
.kpi-sub   { font-size: 8px; color: #94a3b8; margin-top: 2px; }
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
    font-size: 9.5px; font-weight: 700; color: #f1f5f9; text-transform: uppercase;
    letter-spacing: .4px; background: #1e293b; padding: 4px 10px;
    margin-bottom: 0; border-radius: 4px 4px 0 0;
    display: flex; justify-content: space-between; align-items: center;
}
.sec-title .sec-sub { font-weight: 400; font-size: 8.5px; opacity: .75; }

/* ── Table ── */
.main-table { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
.main-table th {
    background: #334155; color: #f1f5f9; font-size: 8.5px;
    padding: 5px 7px; border: 1px solid #475569; text-align: left; white-space: nowrap;
}
.main-table th.text-center { text-align: center; }
.main-table td { padding: 5px 7px; border: 1px solid #e2e8f0; font-size: 9.5px; vertical-align: middle; }
.main-table tr:nth-child(even) td { background: #f8fafc; }
.num { text-align: right; font-variant-numeric: tabular-nums; }
.zero { color: #cbd5e1; text-align: right; }

/* source/status pills */
.pill {
    display: inline-block; padding: 1px 7px; border-radius: 3px;
    font-size: 8px; font-weight: 700; border: 1px solid;
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
    padding: 9px 12px 48px; text-align: center; font-size: 9.5px; color: #475569;
}
.sign-box .sign-role { font-weight: 700; color: #1e293b; font-size: 10px; margin-top: 3px; }

/* ── Footer ── */
.doc-footer {
    margin-top: 16px; border-top: 1px solid #e2e8f0; padding-top: 6px;
    display: flex; justify-content: space-between; font-size: 8.5px; color: #94a3b8;
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

$totalProgram = count($programs);
$activeProgram = count(array_filter($programs, fn($p) => $p['status'] === 'active'));

function numF(int $n): string { return $n > 0 ? number_format($n) : '—'; }

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
        <th style="width:28%">Nama Program</th>
        <th style="width:7%">Status</th>
        <?php if (!$isSt): ?><th style="width:18%">Event</th><?php endif; ?>
        <th class="text-center" style="width:9%">Member Baru</th>
        <th class="text-center" style="width:9%">Member Aktif</th>
        <th class="text-center" style="width:9%">Voucher Sebar</th>
        <th class="text-center" style="width:9%">Voucher Pakai</th>
        <th class="text-center" style="width:7%">% Serap</th>
        <th class="text-center" style="width:8%">Hadiah</th>
    </tr>
</thead>
<tbody>
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

    $statusPill = match($p['status']) {
        'active'   => 'pill-active',
        'inactive' => 'pill-inactive',
        'locked'   => 'pill-locked',
        default    => 'pill-inactive',
    };
    $statusLabel = ['active'=>'Aktif','inactive'=>'Nonaktif','locked'=>'Terkunci'][$p['status']] ?? $p['status'];
?>
<tr>
    <td><strong><?= esc($p['nama_program'] ?? ($p['nama'] ?? '—')) ?></strong></td>
    <td><span class="pill <?= $statusPill ?>"><?= $statusLabel ?></span></td>
    <?php if (!$isSt): ?><td style="color:#64748b;font-size:9px"><?= esc($p['event_name'] ?? '—') ?></td><?php endif; ?>
    <td class="<?= $member ? 'num' : 'zero' ?>"><?= numF($member) ?></td>
    <td class="<?= $aktif  ? 'num' : 'zero' ?>"><?= numF($aktif)  ?></td>
    <td class="<?= $sebar  ? 'num' : 'zero' ?>"><?= numF($sebar)  ?></td>
    <td class="<?= $pakai  ? 'num' : 'zero' ?>"><?= numF($pakai)  ?></td>
    <td class="<?= $serap  ? 'num' : 'zero' ?>"><?= $sebar ? $serap.'%' : '—' ?></td>
    <td class="<?= $hd     ? 'num' : 'zero' ?>"><?= numF($hd)     ?></td>
</tr>
<?php $aTxt = trim($analisaMap[$key] ?? ''); ?>
<tr class="analisa-row">
    <td colspan="<?= $isSt ? 8 : 9 ?>" style="background:#f8fafc;font-size:9px;color:#334155;padding:4px 8px;border-top:none">
        <strong style="color:#0f172a">Analisa:</strong>
        <?= $aTxt !== '' ? nl2br(esc($aTxt)) : '<em style="color:#94a3b8">(belum diisi)</em>' ?>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endforeach; ?>

<!-- ══ TANDA TANGAN ══ -->
<div class="sign-row">
    <div class="sign-box">
        Dibuat oleh<br>
        <span class="sign-role">Loyalty &amp; CRM</span>
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
