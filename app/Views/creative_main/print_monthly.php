<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan Bulanan Creative — <?= $bulan ?></title>
<?= $this->include('_laporan/_style') ?>
<style>
/* Khusus laporan Creative */
.kpi-red .kpi-num   { color: #b91c1c; }
.kpi-cyan           { --aksen: #0e7490; }
.kpi-cyan .kpi-num  { color: #0e7490; }
.kpi-row .kpi-num.rp { font-size: 15px; padding-top: 4px; }

/* Baris status item */
.status-strip { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; margin: -8px 0 16px; }
.status-strip .lbl {
    font-size: 8px; font-weight: 600; text-transform: uppercase; letter-spacing: .7px; color: var(--redup); margin-right: 2px;
}

/* Analisa otomatis: panel emas penuh lebar */
.analisa { margin-bottom: 20px; }

/* Tabel rincian */
.main-table tbody tr.has-activity td { background: #f2f9ee; }
.rincian td { padding-top: 4px; padding-bottom: 4px; line-height: 1.3; }
.item-tgl { font-size: 8.5px; color: var(--redup2); }
.ket { color: var(--redup); font-size: 9.5px; }
.legenda { margin: -14px 0 18px; }
.legenda i { display: inline-block; width: 10px; height: 8px; border-radius: 2px; background: #f2f9ee; border: 1px solid #cfe6c3; vertical-align: -1px; margin-right: 3px; }

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

$statusLabels = ['draft'=>'Draft','review'=>'Review','approved'=>'Approved','revision'=>'Revision'];
$tipeLabels   = ['print'=>'Print','digital'=>'Digital'];
$tipeOrder    = ['print','digital'];

$totalItems = count($rows);

function rp(int $n): string {
    return $n > 0 ? 'Rp '.number_format($n, 0, ',', '.') : '—';
}
function num(int $n): string {
    return $n > 0 ? number_format($n) : '—';
}

// Group by tipe
$rowsByTipe = [];
foreach ($rows as $r) {
    $rowsByTipe[$r['item']['tipe']][] = $r;
}
?>

<!-- ══ HEADER ══ -->
<div class="doc-header">
    <div>
        <div class="title">Laporan Bulanan — Creative &amp; Design</div>
        <div class="sub"><?= $bulanLabel ?></div>
        <div class="org">PT. Wulandari Bangun Laksana Tbk. &mdash; IT Department &mdash; Mall Intelligence Center</div>
    </div>
    <div class="meta">
        Dicetak oleh: <?= esc($printedBy) ?><br>
        Tanggal cetak: <?= $printedAt ?><br>
        Total item: <?= $totalItems ?> &middot; Aktif bulan ini: <?= $activeCount ?>
    </div>
</div>

<!-- ══ KPI ══ -->
<div class="kpi-row">
    <div class="kpi-box kpi-blue">
        <div class="kpi-label">Total Item</div>
        <div class="kpi-num"><?= $totalItems ?></div>
        <div class="kpi-sub"><?= $activeCount ?> aktif bulan ini</div>
    </div>
    <div class="kpi-box kpi-red">
        <div class="kpi-label">Budget &amp; Serapan</div>
        <div class="kpi-num rp"><?= rp($totalBudget) ?></div>
        <div class="kpi-sub"><?= $totalBudget > 0 ? 'serapan '.$serapanPct.'%' : 'budget belum di-set' ?></div>
    </div>
    <div class="kpi-box kpi-amber">
        <div class="kpi-label">Realisasi Bulan Ini</div>
        <div class="kpi-num rp"><?= rp($totalRealisasi) ?></div>
        <div class="kpi-sub"><?= $bulanLabel ?></div>
    </div>
    <div class="kpi-box kpi-cyan">
        <div class="kpi-label">Total Reach</div>
        <div class="kpi-num"><?= num($totalReach) ?></div>
        <div class="kpi-sub">digital insight</div>
    </div>
    <div class="kpi-box kpi-purple">
        <div class="kpi-label">Impressions</div>
        <div class="kpi-num"><?= num($totalImpressions) ?></div>
        <div class="kpi-sub">
            <?php if ($totalFollowers > 0): ?>+<?= number_format($totalFollowers) ?> followers<?php else: ?>digital insight<?php endif; ?>
        </div>
    </div>
    <div class="kpi-box kpi-green">
        <div class="kpi-label">Engagement</div>
        <div class="kpi-num"><?= num($totalEngagement) ?></div>
        <div class="kpi-sub">rate <?= $engagementRate ?>%<?= $cpm > 0 ? ' · CPM '.rp($cpm) : '' ?></div>
    </div>
</div>

<!-- ══ STATUS STRIP ══ -->
<?php $statusKelas = ['draft'=>'netral','review'=>'waspada','approved'=>'baik','revision'=>'buruk']; ?>
<div class="status-strip">
    <span class="lbl">Status Item</span>
    <?php foreach ($statusLabels as $key => $lbl):
        $cnt = $statusCounts[$key] ?? 0; if (!$cnt) continue; ?>
    <span class="chip"><b><?= $lbl ?></b> <?= $cnt ?></span>
    <?php endforeach; ?>
</div>

<?php if (!empty($analysis)): ?>
<!-- ══ ANALISA OTOMATIS ══ -->
<div class="insight-box analisa">
    <div class="insight-title">Analisa Otomatis</div>
    <ul class="insight-list">
        <?php foreach ($analysis as $line): ?>
        <li><?= esc($line) ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<?php if (empty($rows)): ?>
<div class="catatan">Belum ada item creative untuk <?= $bulanLabel ?>.</div>
<?php endif; ?>

<!-- ══ DETAIL TABLE ══ -->
<?php foreach ($tipeOrder as $tipe):
    if (empty($rowsByTipe[$tipe])) continue;
    $tipeRows  = $rowsByTipe[$tipe];
    $isDigital = ($tipe === 'digital');
    $grpBudget = array_sum(array_map(fn($r) => (int)$r['item']['budget'], $tipeRows));
    $grpReal   = array_sum(array_map(fn($r) => (int)($r['realMonth']['total'] ?? 0), $tipeRows));
    $grpReach  = array_sum(array_map(fn($r) => (int)($r['insMonth']['max_reach'] ?? 0), $tipeRows));
    $grpImpr   = array_sum(array_map(fn($r) => (int)($r['insMonth']['max_impressions'] ?? 0), $tipeRows));
?>
<div class="sec-title">
    <span>Item <?= $tipeLabels[$tipe] ?></span>
    <span class="lencana <?= $isDigital ? 'info' : 'ungu' ?>"><?= count($tipeRows) ?> item</span>
    <span class="sec-sub">Budget <?= rp($grpBudget) ?> &middot; Realisasi <?= rp($grpReal) ?><?= $isDigital && $grpReach ? ' &middot; Reach '.number_format($grpReach) : '' ?></span>
</div>
<table class="main-table rincian">
<thead>
    <tr>
        <th style="width:28%">Nama Item</th>
        <th style="width:8%">Asal</th>
        <th class="text-center" style="width:10%">Budget</th>
        <th class="text-center" style="width:10%">Realisasi Bln Ini</th>
        <?php if ($isDigital): ?>
        <th class="text-center" style="width:8%">Reach</th>
        <th class="text-center" style="width:9%">Impressions</th>
        <th class="text-center" style="width:8%">Followers</th>
        <?php endif; ?>
        <th style="width:8%">Status</th>
        <th>Keterangan / Event</th>
    </tr>
</thead>
<tbody>
<?php foreach ($tipeRows as $r):
    $item    = $r['item'];
    $realMon = (int)($r['realMonth']['total']                ?? 0);
    $reach   = (int)($r['insMonth']['max_reach']             ?? 0);
    $impr    = (int)($r['insMonth']['max_impressions']       ?? 0);
    $flw     = (int)($r['insMonth']['total_followers_gained'] ?? 0);
    $budget  = (int)$item['budget'];
    $isSt    = $item['_source'] === 's';
?>
<tr class="<?= $r['hasActivity'] ? 'has-activity' : '' ?>">
    <td>
        <strong><?= esc($item['nama']) ?></strong>
        <?php if (!empty($item['is_closed'])): ?> <span class="lencana baik">Selesai</span><?php endif; ?>
        <?php $itgl = ($item['tanggal'] ?? '') ?: ($item['tanggal_take'] ?? ''); if ($itgl): ?>
        <div class="item-tgl"><?= date('d M Y', strtotime($itgl)) ?></div>
        <?php endif; ?>
    </td>
    <td>
        <span class="lencana <?= $isSt ? 'netral' : 'ungu' ?>"><?= $isSt ? 'Standalone' : 'Event' ?></span>
    </td>
    <td class="<?= !$budget ? 'zero' : 'num' ?>"><?= rp($budget) ?></td>
    <td class="<?= !$realMon ? 'zero' : 'num' ?>"><?= rp($realMon) ?></td>
    <?php if ($isDigital): ?>
    <td class="<?= !$reach ? 'zero' : 'num' ?>"><?= num($reach) ?></td>
    <td class="<?= !$impr  ? 'zero' : 'num' ?>"><?= num($impr) ?></td>
    <td class="<?= !$flw   ? 'zero' : 'num' ?>"><?= $flw > 0 ? '+'.number_format($flw) : '—' ?></td>
    <?php endif; ?>
    <td><span class="lencana <?= $statusKelas[$item['status'] ?? 'draft'] ?? 'netral' ?>"><?= $statusLabels[$item['status']] ?? ucfirst($item['status'] ?? '') ?></span></td>
    <td class="ket"><?= esc($item['event_name'] ?? '') ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php if (array_filter(array_column($tipeRows, 'hasActivity'))): ?>
<div class="subnote legenda"><i></i>baris hijau = item yang punya aktivitas di <?= $bulanLabel ?></div>
<?php endif; ?>
<?php endforeach; ?>

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
