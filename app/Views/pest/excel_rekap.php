<?php
/* Ekspor Excel rekap rentang — HTML-as-XLS seperti Traffic exportSummary.
   Bedanya: angka dikirim sebagai BILANGAN (mso-number-format), bukan teks
   "1.234" — di Excel berlokal Inggris teks itu terbaca 1,234 (desimal). */
use App\Libraries\PestRekap as PR;
use App\Models\PestVisitModel;

$e   = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$rc  = $r['rincian'];
$cols = count($items) + 3;
$malls = $r['malls'];

$sec  = 'background:#0f2a4f;color:#ffffff;font-weight:bold;padding:6px 10px;font-size:10pt;';
$th   = 'background:#e8edf5;color:#0f172a;font-weight:bold;padding:5px 8px;border:1px solid #c7d2e3;font-size:9pt;';
$thR  = $th . 'text-align:right;';
$td   = 'padding:4px 8px;border:1px solid #e2e8f0;font-size:9pt;';
$num  = $td . 'text-align:right;mso-number-format:\'#,##0\';';
$tot  = 'background:#eef2f8;font-weight:bold;padding:5px 8px;border:1px solid #c7d2e3;font-size:9pt;';
$totN = $tot . 'text-align:right;mso-number-format:\'#,##0\';';
$imp  = 'background:#fdf6e3;font-style:italic;';
$pctTxt = fn(?float $p) => $p === null ? '—' : PR::persen($p, true);
$prevLabel = PR::rentang($prev['dari'], $prev['sampai']);
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8">
<style>body{font-family:Calibri,Arial,sans-serif;font-size:10pt} table{border-collapse:collapse;margin-bottom:16px} td,th{white-space:nowrap;vertical-align:top}</style>
</head><body>

<table>
<tr><td colspan="<?= $cols ?>" style="background:#091528;color:#ffffff;font-size:15pt;font-weight:bold;padding:10px 12px">Rekap Periode Pest Control — <?= $e($mall ? PestVisitModel::MALLS[$mall] : 'eWalk & Pentacity') ?></td></tr>
<tr><td colspan="<?= $cols ?>" style="background:#0f2a4f;color:#f3e7c4;font-size:10pt;padding:5px 12px">Periode: <?= $e(PR::rentang($r['dari'], $r['sampai'])) ?> (<?= $r['hari'] ?> hari) · Pembanding: <?= $e($prevLabel) ?></td></tr>
<tr><td colspan="<?= $cols ?>" style="background:#0f2a4f;color:#c7d3e6;font-size:8.5pt;padding:4px 12px">Digenerate: <?= date('d/m/Y H:i') ?> · Mall Intelligence Center · Rincian <?= $e($rc['mode']) ?></td></tr>
</table>

<table>
<tr><td colspan="<?= count($malls) + 2 ?>" style="<?= $sec ?>">RINGKASAN</td></tr>
<tr><th style="<?= $th ?>">Metrik</th>
<?php foreach ($malls as $mk): ?><th style="<?= $thR ?>"><?= $e(PestVisitModel::MALLS[$mk]) ?></th><?php endforeach; ?>
<th style="<?= $thR ?>">Total</th></tr>
<tr><td style="<?= $td ?>font-weight:bold">Total temuan</td>
<?php foreach ($malls as $mk): ?><td style="<?= $num ?>font-weight:bold"><?= (int) ($r['totalMall'][$mk] ?? 0) ?></td><?php endforeach; ?>
<td style="<?= $num ?>font-weight:bold"><?= $r['grand'] ?></td></tr>
<tr><td style="<?= $td ?>">— dari kunjungan</td>
<?php foreach ($malls as $mk): ?><td style="<?= $num ?>"><?= array_sum($r['perMall'][$mk] ?? []) - array_sum(array_map(fn($l) => $l['mall'] === $mk ? $l['total'] : 0, $r['legacyDipakai'])) ?></td><?php endforeach; ?>
<td style="<?= $num ?>"><?= $r['grandKunjungan'] ?></td></tr>
<tr><td style="<?= $td ?>">— dari rekap impor bulanan</td>
<?php foreach ($malls as $mk): ?><td style="<?= $num ?>"><?= array_sum(array_map(fn($l) => $l['mall'] === $mk ? $l['total'] : 0, $r['legacyDipakai'])) ?></td><?php endforeach; ?>
<td style="<?= $num ?>"><?= $r['grandLegacy'] ?></td></tr>
<tr><td style="<?= $td ?>">Kunjungan tercatat</td>
<?php foreach ($malls as $mk): ?><td style="<?= $num ?>"><?= $r['kunjungan'][$mk]['n'] ?></td><?php endforeach; ?>
<td style="<?= $num ?>"><?= $r['jmlKunjungan'] ?></td></tr>
<tr><td style="<?= $td ?>">Kunjungan nihil temuan</td>
<?php foreach ($malls as $mk): ?><td style="<?= $num ?>"><?= $r['kunjungan'][$mk]['nihil'] ?></td><?php endforeach; ?>
<td style="<?= $num ?>"><?= $r['jmlNihil'] ?></td></tr>
<tr><td style="<?= $td ?>">Total periode pembanding (<?= $e($prevLabel) ?>)</td>
<?php foreach ($malls as $mk): ?><td style="<?= $num ?>"><?= (int) ($prev['totalMall'][$mk] ?? 0) ?></td><?php endforeach; ?>
<td style="<?= $num ?>"><?= $prev['grand'] ?></td></tr>
<tr><td style="<?= $td ?>">Perubahan (naik = memburuk)</td>
<?php foreach ($malls as $mk): ?><td style="<?= $td ?>text-align:right"><?= $pctTxt(PR::pct((int) ($r['totalMall'][$mk] ?? 0), (int) ($prev['totalMall'][$mk] ?? 0))) ?></td><?php endforeach; ?>
<td style="<?= $td ?>text-align:right;font-weight:bold"><?= $pctTxt(PR::pct($r['grand'], $prev['grand'])) ?></td></tr>
</table>

<table>
<tr><td colspan="<?= count($malls) + 4 ?>" style="<?= $sec ?>">REKAP PER ITEM &amp; MALL</td></tr>
<tr><th style="<?= $th ?>">Item</th>
<?php foreach ($malls as $mk): ?><th style="<?= $thR ?>"><?= $e(PestVisitModel::MALLS[$mk]) ?></th><?php endforeach; ?>
<th style="<?= $thR ?>">Total</th><th style="<?= $thR ?>">Periode lalu</th><th style="<?= $thR ?>">Perubahan</th></tr>
<?php foreach ($items as $it): $id = (int) $it['id']; $t = $r['perItem'][$id] ?? 0; $l = $prev['perItem'][$id] ?? 0; ?>
<tr><td style="<?= $td ?>"><?= $e($it['nama']) ?><?= (int) $it['aktif'] ? '' : ' (nonaktif)' ?></td>
<?php foreach ($malls as $mk): ?><td style="<?= $num ?>"><?= (int) ($r['perMall'][$mk][$id] ?? 0) ?></td><?php endforeach; ?>
<td style="<?= $num ?>font-weight:bold"><?= $t ?></td><td style="<?= $num ?>"><?= $l ?></td>
<td style="<?= $td ?>text-align:right"><?= $t === 0 && $l === 0 ? '—' : $pctTxt(PR::pct($t, $l)) ?></td></tr>
<?php endforeach; ?>
<tr><td style="<?= $tot ?>">TOTAL</td>
<?php foreach ($malls as $mk): ?><td style="<?= $totN ?>"><?= (int) ($r['totalMall'][$mk] ?? 0) ?></td><?php endforeach; ?>
<td style="<?= $totN ?>"><?= $r['grand'] ?></td><td style="<?= $totN ?>"><?= $prev['grand'] ?></td>
<td style="<?= $tot ?>text-align:right"><?= $pctTxt(PR::pct($r['grand'], $prev['grand'])) ?></td></tr>
</table>

<table>
<tr><td colspan="<?= $cols ?>" style="<?= $sec ?>">RINCIAN PER <?= strtoupper(['harian' => 'hari', 'mingguan' => 'minggu (ISO, dipotong di batas rentang)', 'bulanan' => 'bulan'][$rc['mode']]) ?></td></tr>
<tr><th style="<?= $th ?>">Periode</th>
<?php foreach ($items as $it): ?><th style="<?= $thR ?>"><?= $e($it['nama']) ?></th><?php endforeach; ?>
<th style="<?= $thR ?>">Total</th><th style="<?= $thR ?>">Kunjungan</th></tr>
<?php foreach ($rc['ember'] as $b): $kosong = $b['kunjungan'] === 0 && ! $b['legacy']; ?>
<tr><td style="<?= $td ?>"><?= $e(PR::labelEmber($b, $rc['mode'])) ?><?= $b['legacy'] ? ' [memuat rekap impor]' : '' ?><?= $kosong ? ' [belum ada catatan]' : '' ?></td>
<?php foreach ($items as $it): ?><td style="<?= $num ?>"><?= (int) ($b['items'][(int) $it['id']] ?? 0) ?></td><?php endforeach; ?>
<td style="<?= $num ?>font-weight:bold"><?= $b['total'] ?></td><td style="<?= $num ?>"><?= $b['kunjungan'] ?></td></tr>
<?php endforeach; ?>
<?php if ($rc['legacy']['total'] > 0): ?>
<tr><td style="<?= $td . $imp ?>">Rekap impor bulanan (tanpa tanggal)</td>
<?php foreach ($items as $it): ?><td style="<?= $num . $imp ?>"><?= (int) ($rc['legacy']['items'][(int) $it['id']] ?? 0) ?></td><?php endforeach; ?>
<td style="<?= $num . $imp ?>font-weight:bold"><?= $rc['legacy']['total'] ?></td><td style="<?= $td . $imp ?>text-align:right"><?= $rc['legacy']['baris'] ?> rekap</td></tr>
<?php endif; ?>
<tr><td style="<?= $tot ?>">TOTAL</td>
<?php foreach ($items as $it): ?><td style="<?= $totN ?>"><?= (int) ($r['perItem'][(int) $it['id']] ?? 0) ?></td><?php endforeach; ?>
<td style="<?= $totN ?>"><?= $r['grand'] ?></td><td style="<?= $totN ?>"><?= $r['jmlKunjungan'] ?></td></tr>
</table>

<table>
<tr><td colspan="<?= $cols ?>" style="<?= $sec ?>">DAFTAR KUNJUNGAN (termasuk nihil temuan)</td></tr>
<tr><th style="<?= $th ?>">Tanggal</th><th style="<?= $th ?>">Mall</th>
<?php foreach ($items as $it): ?><th style="<?= $thR ?>"><?= $e($it['nama']) ?></th><?php endforeach; ?>
<th style="<?= $thR ?>">Total</th></tr>
<?php if (! $r['daftarKunjungan']): ?>
<tr><td colspan="<?= $cols ?>" style="<?= $td ?>font-style:italic;color:#64748b">Belum ada kunjungan tercatat pada periode ini.</td></tr>
<?php endif; ?>
<?php foreach ($r['daftarKunjungan'] as $k): $per = $r['harian'][$k['tanggal']][$k['mall']] ?? []; ?>
<tr><td style="<?= $td ?>"><?= date('d/m/Y', strtotime($k['tanggal'])) ?></td><td style="<?= $td ?>"><?= $e(PestVisitModel::MALLS[$k['mall']]) ?></td>
<?php foreach ($items as $it): ?><td style="<?= $num ?>"><?= (int) ($per[(int) $it['id']] ?? 0) ?></td><?php endforeach; ?>
<td style="<?= $num ?>font-weight:bold"><?= (int) $k['total'] ?></td></tr>
<?php endforeach; ?>
</table>

<table>
<tr><td style="<?= $sec ?>">CATATAN &amp; ANALISA</td></tr>
<?php foreach (array_merge($catatan, $catatanPrev ? ['Periode pembanding: ' . implode(' ', $catatanPrev)] : [], $analisa) as $c): ?>
<tr><td style="<?= $td ?>white-space:normal;width:900px"><?= $e($c) ?></td></tr>
<?php endforeach; ?>
</table>

</body></html>
