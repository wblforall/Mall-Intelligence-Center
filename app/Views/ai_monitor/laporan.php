<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan Pemantauan AI — <?= esc($bulanLabel) ?></title>
<?= $this->include('_laporan/_style') ?>
<style>
/* Penanda baris kantor di bawah ambang */
.flag-low td { background: #fef2f2 !important; }
.pct-low  { color: #b91c1c; font-weight: 700; }
.pct-ok   { color: #15803d; font-weight: 700; }
.badge-low { display:inline-block; background:#b91c1c; color:#fff; font-size:9px; font-weight:700;
             padding:1px 6px; border-radius:4px; margin-left:6px; }
.mini-wrap { height: 150px; position: relative; }
</style>
</head>
<body>

<button class="btn-print no-print" onclick="window.print()">&#128438; Cetak</button>

<?php
helper('tanggal');
$n = fn($v) => $v > 0 ? number_format($v) : '—';
$jenisLabel = ['coding'=>'Coding','debugging'=>'Debugging','ideating'=>'Ideating','menulis'=>'Menulis','riset'=>'Riset','lainnya'=>'Lainnya','Belum'=>'Belum'];
$kantorLabel = ['kantor'=>'Kantor','pribadi'=>'Pribadi','tak_jelas'=>'Tak jelas','Belum'=>'Belum'];

$tot       = $analisa['total'];
$pk        = $analisa['pct_kantor'];           // int|null
$totJenis  = array_sum($analisa['jenis']);
$totKantor = array_sum($analisa['kantor']);
$kantorRendah = $pk !== null && $pk < $ambang_kantor;
?>

<!-- ══ HEADER ══ -->
<div class="doc-header">
    <div>
        <div class="title">Laporan Pemantauan AI</div>
        <div class="sub"><?= esc($bulanLabel) ?> &middot; <?= esc($scopeLabel) ?></div>
        <div class="org">PT. Wulandari Bangun Laksana Tbk. &mdash; IT Department &mdash; Mall Intelligence Center</div>
    </div>
    <div class="meta">
        Dicetak oleh: <?= esc($printedBy) ?><br>
        Tanggal cetak: <?= esc($printedAt) ?><br>
        Sumber: Pemantauan Claude Code
    </div>
</div>

<?php if ($kantorRendah): ?>
<div style="border:1px solid #fecaca;background:#fef2f2;color:#b91c1c;border-radius:6px;padding:7px 11px;margin-bottom:14px;font-size:11px;font-weight:700">
    &#9888; Porsi pemakaian untuk KANTOR pada periode ini baru <?= $pk ?>% — di bawah ambang <?= $ambang_kantor ?>%.
</div>
<?php endif; ?>

<!-- ══ KPI ══ -->
<div class="kpi-row">
    <div class="kpi-box kpi-blue">
        <div class="kpi-label">Jumlah Sesi</div>
        <div class="kpi-num"><?= number_format($tot['sesi']) ?></div>
    </div>
    <div class="kpi-box kpi-purple">
        <div class="kpi-label">Jumlah Prompt</div>
        <div class="kpi-num"><?= number_format($tot['prompt']) ?></div>
    </div>
    <div class="kpi-box kpi-green">
        <div class="kpi-label">Jumlah Token</div>
        <div class="kpi-num"><?= number_format($tot['token']) ?></div>
    </div>
    <?php if ($isGlobal): ?>
    <div class="kpi-box kpi-amber">
        <div class="kpi-label">Komputer Aktif</div>
        <div class="kpi-num"><?= number_format((int) $komputerAktif) ?></div>
    </div>
    <?php endif; ?>
    <div class="kpi-box <?= $kantorRendah ? '' : 'kpi-green' ?>" style="<?= $kantorRendah ? 'border-color:#fecaca;background:#fef2f2' : '' ?>">
        <div class="kpi-label">% Kantor</div>
        <div class="kpi-num" style="<?= $kantorRendah ? 'color:#b91c1c' : '' ?>"><?= $pk === null ? 'N/A' : $pk . '%' ?></div>
        <div class="kpi-sub"><?= $pk === null ? 'tidak ada sesi' : ('ambang ' . $ambang_kantor . '%') ?></div>
    </div>
</div>

<!-- ══ BREAKDOWN ══ -->
<div class="duo">
<div>
    <div class="sec-title"><span>Jenis Aktivitas</span></div>
    <table class="main-table">
    <thead><tr><th>Jenis</th><th class="text-center">Sesi</th><th class="text-center">%</th></tr></thead>
    <tbody>
    <?php if ($totJenis === 0): ?>
        <tr><td colspan="3" style="text-align:center;color:#94a3b8;padding:12px">Belum ada sesi terklasifikasi</td></tr>
    <?php else: arsort($analisa['jenis']); foreach ($analisa['jenis'] as $k => $v): ?>
        <tr><td><?= esc($jenisLabel[$k] ?? $k) ?></td>
            <td class="num"><?= $n($v) ?></td>
            <td class="num"><?= round($v / $totJenis * 100) ?>%</td></tr>
    <?php endforeach; endif; ?>
    </tbody>
    </table>
</div>
<div>
    <div class="sec-title"><span>Kantor vs Pribadi</span></div>
    <table class="main-table">
    <thead><tr><th>Kategori</th><th class="text-center">Sesi</th><th class="text-center">%</th></tr></thead>
    <tbody>
    <?php if ($totKantor === 0): ?>
        <tr><td colspan="3" style="text-align:center;color:#94a3b8;padding:12px">Belum ada sesi terklasifikasi</td></tr>
    <?php else: arsort($analisa['kantor']); foreach ($analisa['kantor'] as $k => $v): ?>
        <tr><td><?= esc($kantorLabel[$k] ?? $k) ?></td>
            <td class="num"><?= $n($v) ?></td>
            <td class="num"><?= round($v / $totKantor * 100) ?>%</td></tr>
    <?php endforeach; endif; ?>
    </tbody>
    </table>
</div>
<div>
    <div class="sec-title"><span>Top Tema</span></div>
    <table class="main-table">
    <thead><tr><th>Tema</th><th class="text-center">Sesi</th></tr></thead>
    <tbody>
    <?php if (empty($analisa['tema'])): ?>
        <tr><td colspan="2" style="text-align:center;color:#94a3b8;padding:12px">Belum ada tema</td></tr>
    <?php else: foreach ($analisa['tema'] as $t): ?>
        <tr><td><?= esc($t['tema']) ?></td><td class="num"><?= $n($t['jumlah']) ?></td></tr>
    <?php endforeach; endif; ?>
    </tbody>
    </table>
</div>
</div>

<?php if ($isGlobal): ?>
<!-- ══ REKAP PER KARYAWAN ══ -->
<div class="sec-title"><span>Rekap per Karyawan</span>
    <span class="sec-sub">baris merah = porsi kantor &lt; <?= $ambang_kantor ?>%</span></div>
<table class="main-table">
<thead><tr>
    <th style="width:22%">Karyawan</th><th style="width:16%">Departemen</th>
    <th class="text-center">Sesi</th><th class="text-center">Prompt</th><th class="text-center">Token</th>
    <th>Jenis Dominan</th><th class="text-center">% Kantor</th>
</tr></thead>
<tbody>
<?php if (empty($rekap)): ?>
    <tr><td colspan="7" style="text-align:center;color:#94a3b8;padding:14px">Belum ada aktivitas pada <?= esc($bulanLabel) ?>.</td></tr>
<?php else: foreach ($rekap as $r):
    $low = $r['pct_kantor'] !== null && $r['pct_kantor'] < $ambang_kantor; ?>
    <tr class="<?= $low ? 'flag-low' : '' ?>">
        <td><strong><?= esc($r['nama']) ?></strong></td>
        <td style="color:#64748b;font-size:10px"><?= esc($r['dept']) ?></td>
        <td class="num"><?= $n($r['sesi']) ?></td>
        <td class="num"><?= $n($r['prompt']) ?></td>
        <td class="num"><?= $n($r['token']) ?></td>
        <td><?= $r['jenis_dominan'] ? esc($jenisLabel[$r['jenis_dominan']] ?? $r['jenis_dominan']) : '<span style="color:#cbd5e1">—</span>' ?></td>
        <td class="num">
            <?php if ($r['pct_kantor'] === null): ?>
                <span style="color:#cbd5e1">—</span>
            <?php else: ?>
                <span class="<?= $low ? 'pct-low' : 'pct-ok' ?>"><?= $r['pct_kantor'] ?>%</span>
                <?= $low ? '<span class="badge-low">&lt;' . $ambang_kantor . '%</span>' : '' ?>
            <?php endif; ?>
        </td>
    </tr>
<?php endforeach; endif; ?>
</tbody>
</table>
<?php else: ?>
<!-- ══ DAFTAR SESI (individu) ══ -->
<div class="sec-title"><span>Daftar Sesi</span>
    <span class="sec-sub"><?= count($sesiList ?? []) ?> sesi pada <?= esc($bulanLabel) ?></span></div>
<table class="main-table">
<thead><tr>
    <th style="width:30%">Judul</th><th>Tema</th><th class="text-center">Jenis</th>
    <th class="text-center">Kantor</th><th>Waktu</th><th class="text-center">Prompt</th>
</tr></thead>
<tbody>
<?php if (empty($sesiList)): ?>
    <tr><td colspan="6" style="text-align:center;color:#94a3b8;padding:14px">Belum ada sesi pada <?= esc($bulanLabel) ?>.</td></tr>
<?php else: foreach ($sesiList as $s): ?>
    <tr>
        <td><?= ($s['judul'] ?? '') !== '' ? esc($s['judul']) : '<span style="color:#94a3b8">(tanpa judul)</span>' ?>
            <?php if (! empty($s['proyek'])): ?><div class="subnote"><?= esc($s['proyek']) ?></div><?php endif; ?></td>
        <td><?= $s['klasifikasi_tema'] ? esc($s['klasifikasi_tema']) : '<span style="color:#cbd5e1">—</span>' ?></td>
        <td class="text-center" style="text-align:center"><?= $s['klasifikasi_jenis'] ? esc($jenisLabel[$s['klasifikasi_jenis']] ?? $s['klasifikasi_jenis']) : '<span style="color:#cbd5e1">—</span>' ?></td>
        <td class="text-center" style="text-align:center"><?= $s['klasifikasi_kantor'] ? esc($kantorLabel[$s['klasifikasi_kantor']] ?? $s['klasifikasi_kantor']) : '<span style="color:#cbd5e1">—</span>' ?></td>
        <td style="font-size:10px"><?= $s['terakhir_at'] ? tgl_indo($s['terakhir_at'], true) : '—' ?></td>
        <td class="num"><?= $n((int) $s['jml_prompt']) ?></td>
    </tr>
<?php endforeach; endif; ?>
</tbody>
</table>
<?php endif; ?>

<!-- ══ FOOTER ══ -->
<div class="doc-footer">
    <span>Mall Intelligence Center &mdash; Laporan Pemantauan AI</span>
    <span><?= esc($bulanLabel) ?> &middot; <?= esc($scopeLabel) ?></span>
</div>

</body>
</html>
