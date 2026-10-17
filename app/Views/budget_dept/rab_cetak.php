<?php
use App\Libraries\RabSusun;

$rp  = fn ($v) => number_format((float) $v, 0, ',', '.');
$num = fn ($v) => $v === null ? '' : rtrim(rtrim(number_format((float) $v, 2, ',', '.'), '0'), ',');
$posNama = array_column($snap['pos'], 'nama', 'id');
$bulanP  = RabSusun::daftarBulan($snap['periode']['mulai'], $snap['periode']['selesai']);
$periode = RabSusun::labelPeriode($snap['periode']['mulai'], $snap['periode']['selesai']);
$draf    = $d['status'] === 'draf';
$grup = [];
foreach ($snap['items'] as $it) $grup[$it['bulan']][$it['mall']][$it['event_id']][] = $it;
$tglDok = $d['diajukan_at'] ? strtotime($d['diajukan_at']) : time();
$BLN = RabSusun::BLN;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title><?= esc(($d['nomor'] ?: 'Draf RAB') . ' — ' . $periode) ?></title>
<?= view('_laporan/_style') ?>
<style>
@page { @top-right { content: "<?= esc($d['nomor'] ?: 'DRAF — belum diajukan') ?>"; } }
.main-table.rab th, .main-table.rab td { padding: 3px 6px; font-size: 9px; }
.main-table.rab tr.ev { break-after: avoid; page-break-after: avoid; }
.main-table.rab tr.ev td { background: #eef2f8 !important; font-weight: 700; color: var(--tinta); }
.main-table.rab tr.kel td { font-style: italic; color: var(--redup); background: #fff !important; }
.main-table.rab tr.sub td { font-weight: 700; background: #fdf6e3 !important; }
.main-table.rab tr.var td { font-weight: 700; }
.lewat { color: #b91c1c; font-weight: 700; }
/* Nomor dokumen di kop gelap: <b> berwarna tinta (gelap) di tema cetak → tak terbaca. */
.doc-header .meta .nomor-dok { color: #fff; font-weight: 700; }
.tanda { font-size: 7.5px; font-weight: 700; padding: 0 4px; border-radius: 3px; margin-left: 4px; }
.tanda.baru { background: #dcfce7; color: #15803d; } .tanda.berubah { background: #fef3c7; color: #b45309; }
.draf-cap { position: fixed; top: 40%; left: 0; right: 0; text-align: center; font-size: 110px; font-weight: 800;
    color: rgba(185, 28, 28, .07); transform: rotate(-18deg); pointer-events: none; z-index: 0; }
.tautan-kembali { position: fixed; top: 16px; left: 16px; z-index: 50; padding: 8px 14px; border-radius: 8px; background: #fff;
    border: 1px solid var(--garis); color: var(--navy-2); font: 600 12px 'Inter', Arial, sans-serif; text-decoration: none; }
</style>
</head>
<body>
<?php if ($draf): ?><div class="draf-cap">DRAF</div><?php endif; ?>
<a class="tautan-kembali no-print" href="<?= $kembali ?>">← Kembali</a>
<button class="btn-print no-print" onclick="window.print()">Cetak</button>

<div class="doc-header">
    <div>
        <div class="title">Proposal Budget — <?= esc($dept['name']) ?></div>
        <div class="sub"><?= $periode ?><?= (int) $d['versi'] > 1 ? ' · Revisi ' . ((int) $d['versi'] - 1) : '' ?></div>
        <div class="org">PT. Wulandari Bangun Laksana Tbk. &mdash; eWalk &amp; Pentacity Balikpapan</div>
    </div>
    <div class="meta">
        Nomor: <span class="nomor-dok"><?= esc($d['nomor'] ?: 'DRAF (belum diajukan)') ?></span><br>
        Tanggal: <?= date('j', $tglDok) . ' ' . $BLN[(int) date('n', $tglDok)] . ' ' . date('Y', $tglDok) ?><br>
        Angka dalam Rupiah
    </div>
</div>

<?php if ($d['catatan']): ?><div class="catatan info"><b>Alasan revisi:</b> <?= esc($d['catatan']) ?></div><?php endif; ?>

<?php
$tot = $ringkas['total']; $selisih = $tot['budget'] - $tot['rab'];
$pakai = $tot['budget'] > 0 ? number_format($tot['rab'] / $tot['budget'] * 100, 1, ',', '.') . '%' : '–';
$nEv = count(array_unique(array_column($snap['items'], 'event_id'))); $nIt = count($snap['items']);
$periksa = RabSusun::periksa($snap);
$tanpaPos = $ringkas['tanpaPos'] ?? 0;
$temuan = [];
if ($tot['budget'] > 0) $temuan[] = 'Total RAB Rp ' . $rp($tot['rab']) . ' = ' . $pakai . ' dari budget Rp ' . $rp($tot['budget']) . ($selisih < 0 ? ' — MELEBIHI budget Rp ' . $rp(-$selisih) . '.' : '; variant (sisa) Rp ' . $rp($selisih) . '.');
else $temuan[] = 'Total RAB Rp ' . $rp($tot['rab']) . '; budget periode ini belum ditetapkan.';
foreach ($bulanP as $b) foreach (['ewalk', 'pentacity'] as $m) { $v = $ringkas['perBulanMal'][$b][$m] ?? null; if ($v && $v['rab'] > $v['budget'] + 0.5) $temuan[] = RabSusun::labelBulan($b) . ' ' . RabSusun::MAL[$m] . ': RAB Rp ' . $rp($v['rab']) . ' melebihi budget Rp ' . $rp($v['budget']) . '.'; }
if ($tanpaPos > 0) $temuan[] = 'Rincian senilai Rp ' . $rp($tanpaPos) . ' belum dipetakan ke pos budget.';
?>
<div class="kpi-row">
    <div class="kpi-box kpi-blue"><div class="kpi-label">Total Budget</div><div class="kpi-num"><?= $rp($tot['budget']) ?></div><div class="kpi-sub"><?= $periode ?></div></div>
    <div class="kpi-box kpi-green"><div class="kpi-label">Total RAB</div><div class="kpi-num"><?= $rp($tot['rab']) ?></div><div class="kpi-sub"><?= $pakai ?> dari budget</div></div>
    <div class="kpi-box kpi-amber"><div class="kpi-label">Variant</div><div class="kpi-num <?= $selisih < 0 ? 'lewat' : '' ?>"><?= $rp($selisih) ?></div><div class="kpi-sub"><?= $selisih < 0 ? 'melebihi budget' : 'sisa budget' ?></div></div>
    <div class="kpi-box kpi-purple"><div class="kpi-label">Cakupan</div><div class="kpi-num"><?= $nEv ?> event</div><div class="kpi-sub"><?= $nIt ?> rincian biaya</div></div>
</div>
<div class="insight-box" style="margin-bottom:16px"><div class="insight-title">Ringkasan &amp; Catatan Utama</div>
<ul class="insight-list"><?php foreach ($temuan as $t): ?><li><?= esc($t) ?></li><?php endforeach; ?>
<?php $hal = $periksa['halang']; foreach (array_slice($hal, 0, 4) as $h): ?><li><b>Perlu dilengkapi:</b> <?= esc($h) ?></li><?php endforeach; ?>
<?php if (count($hal) > 4): ?><li><b>Perlu dilengkapi:</b> dan <?= count($hal) - 4 ?> catatan serupa lainnya.</li><?php endif; ?></ul></div>

<div class="sec-title">Budget &amp; RAB per Pos</div>
<table class="main-table rab">
    <thead>
        <tr><th rowspan="2">Pos</th>
            <?php foreach ($bulanP as $b): ?><th colspan="2" class="text-center"><?= RabSusun::labelBulan($b) ?></th><?php endforeach; ?>
            <th rowspan="2" class="num">Total Budget</th><th rowspan="2" class="num">Total RAB</th><th rowspan="2" class="num">Variant</th></tr>
        <tr><?php foreach ($bulanP as $b): ?><th class="num">eWalk</th><th class="num">Pentacity</th><?php endforeach; ?></tr>
    </thead>
    <tbody>
    <?php foreach ($snap['pos'] as $p): $pp = $ringkas['perPos'][$p['id']] ?? ['budget' => 0, 'rab' => 0]; if ($pp['budget'] == 0 && $pp['rab'] == 0) continue; ?>
    <tr><td><?= esc($p['nama']) ?></td>
        <?php foreach ($bulanP as $b): foreach (['ewalk', 'pentacity'] as $m): $v = $ringkas['sel'][$b][$m][$p['id']] ?? ['budget' => 0]; ?>
        <td class="num"><?= $v['budget'] ? $rp($v['budget']) : '–' ?></td>
        <?php endforeach; endforeach; ?>
        <td class="num"><?= $rp($pp['budget']) ?></td><td class="num"><?= $rp($pp['rab']) ?></td>
        <td class="num <?= $pp['rab'] > $pp['budget'] ? 'lewat' : '' ?>"><?= $rp($pp['budget'] - $pp['rab']) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot><tr><td>Total</td>
        <?php foreach ($bulanP as $b): foreach (['ewalk', 'pentacity'] as $m): ?><td class="num"><?= $rp($ringkas['perBulanMal'][$b][$m]['budget'] ?? 0) ?></td><?php endforeach; endforeach; ?>
        <td class="num"><?= $rp($ringkas['total']['budget']) ?></td><td class="num"><?= $rp($ringkas['total']['rab']) ?></td>
        <td class="num"><?= $rp($ringkas['total']['budget'] - $ringkas['total']['rab']) ?></td></tr></tfoot>
</table>

<?php foreach ($bulanP as $b): foreach (['ewalk', 'pentacity', 'both'] as $m): if (empty($grup[$b][$m])) continue; $sub = 0; $no = 0; ?>
<div class="sec-title"><?= RabSusun::MAL[$m] ?> — <?= RabSusun::labelBulan($b) ?></div>
<table class="main-table rab">
    <thead><tr><th>No</th><th>Detail of Expenses</th><th class="num">Qty</th><th>Unit</th><th class="num">Freq</th><th>Unit</th>
        <th class="num">Unit Cost</th><th class="num">Total Cost</th><th>Pos</th><th>Remark</th></tr></thead>
    <tbody>
    <?php foreach ($grup[$b][$m] as $evId => $rows): $ev = $snap['events'][$evId] ?? []; $no++; $kel = null; ?>
    <tr class="ev"><td><?= $no ?></td><td colspan="9"><?= esc($ev['nama'] ?? '') ?>
        <span style="font-weight:400;color:var(--redup)"> · <?= ! empty($ev['mulai']) ? date('j', strtotime($ev['mulai'])) . ' ' . $BLN[(int) date('n', strtotime($ev['mulai']))] . ' s.d. ' . date('j', strtotime($ev['akhir'])) . ' ' . $BLN[(int) date('n', strtotime($ev['akhir']))] . ' ' . date('Y', strtotime($ev['akhir'])) : '' ?><?= ! empty($ev['lokasi']) ? ' · ' . esc($ev['lokasi']) : '' ?></span></td></tr>
    <?php foreach ($rows as $it): $sub += $it['total'];
        if ($it['kelompok'] !== '' && $it['kelompok'] !== $kel): $kel = $it['kelompok']; ?>
    <tr class="kel"><td></td><td colspan="9"><?= esc($kel) ?></td></tr>
    <?php endif; $tanda = $beda['tanda'][$it['id']] ?? null; ?>
    <tr><td></td>
        <td><?= esc($it['nama']) ?><?= $tanda ? '<span class="tanda ' . $tanda . '">' . strtoupper($tanda) . '</span>' : '' ?></td>
        <td class="num"><?= $num($it['qty']) ?></td><td><?= esc($it['satuan']) ?></td>
        <td class="num"><?= $num($it['freq']) ?></td><td><?= esc($it['satuan_freq']) ?></td>
        <td class="num"><?= $it['harga_satuan'] !== null ? $rp($it['harga_satuan']) : '' ?></td>
        <td class="num"><?= $rp($it['total']) ?></td>
        <td><?= esc($posNama[$it['pos_id']] ?? '(tanpa pos)') ?></td>
        <td><?= esc(implode(' — ', array_filter([$it['tanggal'] ? date('j/n', strtotime($it['tanggal'])) : '', $it['keterangan']]))) ?></td></tr>
    <?php endforeach; endforeach;
    $bud = $m === 'both' ? null : ($ringkas['perBulanMal'][$b][$m]['budget'] ?? 0); ?>
    <tr class="sub"><td></td><td colspan="6">Sub Total <?= RabSusun::MAL[$m] ?> <?= RabSusun::labelBulan($b) ?></td><td class="num"><?= $rp($sub) ?></td><td colspan="2"></td></tr>
    <?php if ($bud !== null): ?>
    <tr class="var"><td></td><td colspan="6">Budget <?= RabSusun::MAL[$m] ?> <?= RabSusun::labelBulan($b) ?> (semua pos) · Variant</td>
        <td class="num"><?= $rp($bud) ?></td><td colspan="2" class="<?= ($ringkas['perBulanMal'][$b][$m]['rab'] ?? 0) > $bud ? 'lewat' : '' ?>">
        Variant <?= $rp($bud - ($ringkas['perBulanMal'][$b][$m]['rab'] ?? 0)) ?></td></tr>
    <?php endif; ?>
    </tbody>
</table>
<?php endforeach; endforeach; ?>

<?php if ($beda['dihapus']): ?>
<div class="sec-title">Dihapus dibanding versi sebelumnya</div>
<table class="main-table rab">
    <thead><tr><th>Event — Rincian</th><th>Bulan</th><th>Mal</th><th class="num">Total sebelumnya</th></tr></thead>
    <tbody><?php foreach ($beda['dihapus'] as $it): ?>
    <tr><td><s><?= esc(($snap['events'][$it['event_id']]['nama'] ?? 'Event') . ' — ' . $it['nama']) ?></s></td><td><?= RabSusun::labelBulan($it['bulan']) ?></td>
        <td><?= RabSusun::MAL[$it['mall']] ?></td><td class="num"><?= $rp($it['total']) ?></td></tr>
    <?php endforeach; ?></tbody>
</table>
<?php endif; ?>

<?php $tbc = array_filter($snap['events'], fn ($e) => ! in_array($e['id'], array_column($snap['items'], 'event_id'), true)); if ($tbc): ?>
<div class="catatan info"><b>Event tanpa biaya (info / tbc):</b>
    <?= esc(implode('; ', array_map(fn ($e) => $e['nama'] . ' (' . date('j/n', strtotime($e['mulai'])) . '–' . date('j/n', strtotime($e['akhir'])) . ')', $tbc))) ?></div>
<?php endif; ?>

<div class="sign-row">
    <?php foreach ($ttd as $label => $o): ?>
    <div class="sign-box">
        <div class="sign-label"><?= $label ?>,</div>
        <div class="sign-space"></div>
        <span class="sign-role"><?= esc($o['nama'] ?: '..............................') ?></span>
        <div class="sign-jabatan"><?= esc($o['jabatan']) ?></div>
    </div>
    <?php endforeach; ?>
</div>
<div class="doc-footer"><span>Mall Intelligence Center</span><span><?= esc($d['nomor'] ?: 'DRAF') ?> · dicetak <?= date('d/m/Y H:i') ?></span></div>
</body>
</html>
