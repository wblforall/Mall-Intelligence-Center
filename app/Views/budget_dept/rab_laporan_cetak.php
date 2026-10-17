<?php
use App\Libraries\RabSusun;

$rp = fn ($v) => number_format((float) $v, 0, ',', '.');
$posNama = array_column($s['pos'], 'nama', 'id');
$lap = $s['lap'];
$grup = [];
foreach ($lap['baris'] as $x) $grup[$x['mall']][$x['event_id']][] = $x;
$luar = array_filter($lap['baris'], fn ($x) => $x['luar']);
$BLN = RabSusun::BLN;
$tgl = strtotime($l['created_at']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title><?= esc($l['nomor']) ?> — Laporan Realisasi <?= RabSusun::labelBulan($s['bulan']) ?></title>
<?= view('_laporan/_style') ?>
<style>
@page { @top-right { content: "<?= esc($l['nomor']) ?>"; } }
.main-table.rab th, .main-table.rab td { padding: 3px 6px; font-size: 9px; }
.main-table.rab tr.ev { break-after: avoid; page-break-after: avoid; }
.main-table.rab tr.ev td { background: #eef2f8 !important; font-weight: 700; color: var(--tinta); }
.main-table.rab tr.sub td { font-weight: 700; background: #fdf6e3 !important; }
.main-table.rab tr.luar td { background: #fef2f2 !important; }
.lewat { color: #b91c1c; font-weight: 700; }
.doc-header .meta .nomor-dok { color: #fff; font-weight: 700; }
.tautan-kembali { position: fixed; top: 16px; left: 16px; z-index: 50; padding: 8px 14px; border-radius: 8px; background: #fff;
    border: 1px solid var(--garis); color: var(--navy-2); font: 600 12px 'Inter', Arial, sans-serif; text-decoration: none; }
</style>
</head>
<body>
<a class="tautan-kembali no-print" href="<?= $kembali ?>">← Kembali</a>
<button class="btn-print no-print" onclick="window.print()">Cetak</button>

<div class="doc-header">
    <div>
        <div class="title">Laporan Realisasi RAB — <?= esc($dept['name']) ?></div>
        <div class="sub"><?= RabSusun::labelBulan($s['bulan']) ?></div>
        <div class="org">PT. Wulandari Bangun Laksana Tbk. &mdash; eWalk &amp; Pentacity Balikpapan</div>
    </div>
    <div class="meta">
        Nomor: <span class="nomor-dok"><?= esc($l['nomor']) ?></span><br>
        Acuan RAB: <?= esc($s['rab']['nomor'] ?? 'belum ada RAB disetujui') ?><br>
        Tanggal: <?= date('j', $tgl) . ' ' . $BLN[(int) date('n', $tgl)] . ' ' . date('Y', $tgl) ?>
    </div>
</div>

<div class="kpi-row">
    <div class="kpi-box kpi-blue"><div class="kpi-label">RAB disetujui</div><div class="kpi-num"><?= $rp($lap['total']['rab']) ?></div></div>
    <div class="kpi-box kpi-green"><div class="kpi-label">Realisasi</div><div class="kpi-num"><?= $rp($lap['total']['realisasi']) ?></div>
        <div class="kpi-sub"><?= $lap['total']['rab'] > 0 ? round($lap['total']['realisasi'] / $lap['total']['rab'] * 100) . '% dari RAB' : '' ?></div></div>
    <div class="kpi-box kpi-amber"><div class="kpi-label">Sisa terhadap RAB</div>
        <div class="kpi-num <?= $lap['total']['realisasi'] > $lap['total']['rab'] ? 'lewat' : '' ?>"><?= $rp($lap['total']['rab'] - $lap['total']['realisasi']) ?></div></div>
    <div class="kpi-box kpi-red"><div class="kpi-label">Di luar RAB</div><div class="kpi-num"><?= count($luar) ?></div>
        <div class="kpi-sub">Rp <?= $rp(array_sum(array_column($luar, 'realisasi'))) ?> realisasi</div></div>
</div>

<?php
$persen = fn ($a, $b) => $b > 0 ? number_format($a / $b * 100, 1, ',', '.') . '%' : '–';
$rab = $lap['total']['rab']; $real = $lap['total']['realisasi'];
$bar = $lap['baris'];
$lewatRab = array_values(array_filter($bar, fn ($x) => ! $x['luar'] && $x['sisa'] < -0.5));
$belum    = array_values(array_filter($bar, fn ($x) => ! $x['luar'] && $x['realisasi'] == 0 && $x['rab'] > 0));
$namaItem = fn ($x) => ($s['events'][$x['event_id']]['nama'] ?? 'Event') . ' — ' . $x['nama'];
$sebut = function (array $arr, callable $f) use ($namaItem) {
    return esc(implode('; ', array_map(fn ($x) => $namaItem($x) . ' (' . $f($x) . ')', array_slice($arr, 0, 4)))) . (count($arr) > 4 ? '; dan ' . (count($arr) - 4) . ' lainnya' : '');
};
$nLuar = count($luar); $rpLuar = array_sum(array_column($luar, 'realisasi'));
$tbBud = array_sum($s['budget']);
$temuan = [];
if ($bar) {
    if ($rab > 0) $temuan[] = 'Realisasi Rp ' . $rp($real) . ' = ' . $persen($real, $rab) . ' dari RAB disetujui Rp ' . $rp($rab) . ($real > $rab ? ' (melebihi RAB Rp ' . $rp($real - $rab) . ').' : '; sisa Rp ' . $rp($rab - $real) . '.');
    else $temuan[] = 'Belum ada RAB disetujui sebagai acuan; seluruh realisasi Rp ' . $rp($real) . ' tercatat di luar RAB.';
    if ($tbBud > 0) $temuan[] = 'Terhadap budget bulan ini Rp ' . $rp($tbBud) . ', realisasi mencapai ' . $persen($real, $tbBud) . ($real > $tbBud ? ' — melampaui budget.' : '; sisa budget Rp ' . $rp($tbBud - $real) . '.');
    if ($nLuar) $temuan[] = $nLuar . ' rincian terealisasi di luar RAB (Rp ' . $rp($rpLuar) . '); perlu dimasukkan lewat revisi RAB.';
    if ($lewatRab) $temuan[] = count($lewatRab) . ' rincian melebihi nilai RAB-nya.';
    if ($belum) $temuan[] = count($belum) . ' rincian ber-RAB belum/tidak terealisasi (Rp ' . $rp(array_sum(array_column($belum, 'rab'))) . ').';
}
?>
<div class="sec-title tanpa-nomor"><span>Ringkasan &amp; Temuan Utama</span><span class="sec-sub">diturunkan dari data RAB &amp; realisasi</span></div>
<?php if ($temuan): ?>
<div class="insight-box" style="margin-bottom:16px"><div class="insight-title">Temuan</div><ul class="insight-list"><?php foreach ($temuan as $t): ?><li><?= esc($t) ?></li><?php endforeach; ?></ul></div>
<?php else: ?><div class="catatan waspada">Tidak ada RAB maupun realisasi pada <?= RabSusun::labelBulan($s['bulan']) ?>.</div><?php endif; ?>

<?php if ($lap['baris']): ?>
<div class="sec-title">Per Pos</div>
<table class="main-table rab">
    <thead><tr><th>Pos</th><th class="num">Budget bulan ini</th><th class="num">RAB</th><th class="num">Realisasi</th><th class="num">% thd RAB</th><th class="num">Sisa thd RAB</th><th class="num">Sisa thd Budget</th></tr></thead>
    <tbody>
    <?php $posTampil = $s['pos']; if (! empty($lap['perPos'][0])) $posTampil[] = ['id' => 0, 'nama' => '(tanpa pos)'];
    foreach ($posTampil as $p): $v = $lap['perPos'][$p['id']] ?? ['rab' => 0, 'realisasi' => 0]; $bud = $s['budget'][$p['id']] ?? 0;
        if (! $bud && ! $v['rab'] && ! $v['realisasi']) continue; ?>
    <tr><td><?= esc($p['nama']) ?></td><td class="num"><?= $rp($bud) ?></td><td class="num"><?= $rp($v['rab']) ?></td><td class="num"><?= $rp($v['realisasi']) ?></td>
        <td class="num"><?= $persen($v['realisasi'], $v['rab']) ?></td>
        <td class="num <?= $v['realisasi'] > $v['rab'] ? 'lewat' : '' ?>"><?= $rp($v['rab'] - $v['realisasi']) ?></td>
        <td class="num <?= $v['realisasi'] > $bud ? 'lewat' : '' ?>"><?= $rp($bud - $v['realisasi']) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot><tr><td>Total</td><td class="num"><?= $rp(array_sum($s['budget'])) ?></td><td class="num"><?= $rp($lap['total']['rab']) ?></td>
        <td class="num"><?= $rp($lap['total']['realisasi']) ?></td><td class="num"><?= $persen($real, $rab) ?></td><td class="num"><?= $rp($lap['total']['rab'] - $lap['total']['realisasi']) ?></td>
        <td class="num"><?= $rp(array_sum($s['budget']) - $lap['total']['realisasi']) ?></td></tr></tfoot>
</table>

<?php endif; ?>
<?php foreach (['ewalk', 'pentacity', 'both'] as $m): if (empty($grup[$m])) continue; $tr = $tz = 0; ?>
<div class="sec-title"><?= RabSusun::MAL[$m] ?></div>
<table class="main-table rab">
    <thead><tr><th>Rincian</th><th>Pos</th><th class="num">RAB</th><th class="num">Realisasi</th><th class="num">Sisa</th><th>Catatan</th></tr></thead>
    <tbody>
    <?php foreach ($grup[$m] as $evId => $rows): ?>
    <tr class="ev"><td colspan="6"><?= esc($s['events'][$evId]['nama'] ?? 'Event #' . $evId) ?></td></tr>
    <?php foreach ($rows as $x): $tr += $x['rab']; $tz += $x['realisasi']; ?>
    <tr class="<?= $x['luar'] ? 'luar' : '' ?>">
        <td><?= $x['kelompok'] !== '' ? esc($x['kelompok']) . ' · ' : '' ?><?= esc($x['nama']) ?></td>
        <td><?= esc($posNama[$x['pos_id']] ?? '(tanpa pos)') ?></td>
        <td class="num"><?= $x['luar'] ? '–' : $rp($x['rab']) ?></td>
        <td class="num"><?= $rp($x['realisasi']) ?></td>
        <td class="num <?= $x['sisa'] < 0 ? 'lewat' : '' ?>"><?= $rp($x['sisa']) ?></td>
        <td><?= $x['luar'] ? 'Di luar RAB — wajib masuk revisi' : ($x['realisasi'] == 0 ? 'Belum/tidak terealisasi' : '') ?></td>
    </tr>
    <?php endforeach; endforeach; ?>
    <tr class="sub"><td colspan="2">Sub Total <?= RabSusun::MAL[$m] ?></td><td class="num"><?= $rp($tr) ?></td><td class="num"><?= $rp($tz) ?></td><td class="num"><?= $rp($tr - $tz) ?></td><td></td></tr>
    </tbody>
</table>
<?php endforeach; ?>

<?php if (! $lap['baris']): ?><div class="catatan">Tidak ada RAB maupun realisasi di bulan ini.</div><?php endif; ?>

<?php if ($lap['baris']): ?>
<div class="sec-title"><span>Kendala &amp; Tindak Lanjut</span><span class="sec-sub">diturunkan dari data, bukan catatan manual</span></div>
<?php $kend = [];
if ($nLuar) $kend[] = [$nLuar . ' rincian di luar RAB (Rp ' . $rp($rpLuar) . ')', 'Ajukan revisi RAB agar rincian ini tercakup: ' . $sebut(array_values($luar), fn ($x) => 'Rp ' . $rp($x['realisasi'])) . '.'];
if ($lewatRab) $kend[] = [count($lewatRab) . ' rincian realisasi melebihi RAB', 'Jelaskan penyebab selisih & pastikan sumber dana: ' . $sebut($lewatRab, fn ($x) => 'lebih Rp ' . $rp(-$x['sisa'])) . '.'];
if ($belum) $kend[] = [count($belum) . ' rincian ber-RAB belum/tidak terealisasi', 'Konfirmasi apakah dibatalkan atau tertunda (sisa RAB Rp ' . $rp(array_sum(array_column($belum, 'rab'))) . '): ' . $sebut($belum, fn ($x) => 'RAB Rp ' . $rp($x['rab'])) . '.'];
if ($tbBud > 0 && $real > $tbBud) $kend[] = ['Realisasi melampaui budget bulan ini', 'Lampaui Rp ' . $rp($real - $tbBud) . ' — perlu persetujuan penambahan budget.'];
if (! $s['rab'] && $bar) $kend[] = ['Belum ada RAB disetujui untuk bulan ini', 'Susun & ajukan RAB periode berikutnya sebelum acara berjalan.'];
?>
<?php if ($kend): ?>
<table class="main-table rab"><thead><tr><th style="width:30%">Temuan</th><th>Tindak lanjut</th></tr></thead><tbody>
<?php foreach ($kend as [$a, $b]): ?><tr><td><b><?= esc($a) ?></b></td><td><?= $b ?></td></tr><?php endforeach; ?>
</tbody></table>
<?php else: ?><div class="catatan baik">Tidak ada kendala terdeteksi: realisasi sesuai RAB.</div><?php endif; ?>
<div class="catatan"><b>Catatan sumber:</b> RAB = snapshot RAB berlaku (<?= esc($s['rab']['nomor'] ?? 'belum ada') ?>) untuk bulan ini; realisasi = total Realisasi item Event Content per rincian. Angka dalam Rupiah. Rincian bertanda merah = di luar RAB.</div>

<?php endif; ?>
<div class="sign-row" style="margin-top:<?= $lap['baris'] ? 22 : 40 ?>px">
    <?php foreach ($ttd as $label => $o): ?>
    <div class="sign-box">
        <div class="sign-label"><?= $label === 'Disetujui oleh' ? 'Disahkan oleh' : $label ?>,</div>
        <div class="sign-space"></div>
        <span class="sign-role"><?= esc($o['nama'] ?: '..............................') ?></span>
        <div class="sign-jabatan"><?= esc($o['jabatan']) ?></div>
    </div>
    <?php endforeach; ?>
</div>
<div class="doc-footer"><span>Mall Intelligence Center</span><span><?= esc($l['nomor']) ?> · dicetak <?= date('d/m/Y H:i') ?></span></div>
</body>
</html>
