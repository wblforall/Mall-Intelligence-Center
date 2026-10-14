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

<div class="sec-title">Per Pos</div>
<table class="main-table rab">
    <thead><tr><th>Pos</th><th class="num">Budget bulan ini</th><th class="num">RAB</th><th class="num">Realisasi</th><th class="num">Sisa thd RAB</th><th class="num">Sisa thd Budget</th></tr></thead>
    <tbody>
    <?php foreach ($s['pos'] as $p): $v = $lap['perPos'][$p['id']] ?? ['rab' => 0, 'realisasi' => 0]; $bud = $s['budget'][$p['id']] ?? 0;
        if (! $bud && ! $v['rab'] && ! $v['realisasi']) continue; ?>
    <tr><td><?= esc($p['nama']) ?></td><td class="num"><?= $rp($bud) ?></td><td class="num"><?= $rp($v['rab']) ?></td><td class="num"><?= $rp($v['realisasi']) ?></td>
        <td class="num <?= $v['realisasi'] > $v['rab'] ? 'lewat' : '' ?>"><?= $rp($v['rab'] - $v['realisasi']) ?></td>
        <td class="num <?= $v['realisasi'] > $bud ? 'lewat' : '' ?>"><?= $rp($bud - $v['realisasi']) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot><tr><td>Total</td><td class="num"><?= $rp(array_sum($s['budget'])) ?></td><td class="num"><?= $rp($lap['total']['rab']) ?></td>
        <td class="num"><?= $rp($lap['total']['realisasi']) ?></td><td class="num"><?= $rp($lap['total']['rab'] - $lap['total']['realisasi']) ?></td>
        <td class="num"><?= $rp(array_sum($s['budget']) - $lap['total']['realisasi']) ?></td></tr></tfoot>
</table>

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
        <td><?= esc($posNama[$x['pos_id']] ?? '') ?></td>
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

<div class="sign-row">
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
