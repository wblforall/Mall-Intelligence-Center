<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan Pemantauan AI — <?= esc($bulanLabel) ?></title>
<?= $this->include('_laporan/_style') ?>
<style>
/* Penanda angka kantor di bawah ambang */
.pct-low  { color: #b91c1c; font-weight: 700; }
.pct-ok   { color: #15803d; font-weight: 700; }
</style>
</head>
<body>

<button class="btn-print no-print" onclick="window.print()">&#128438; Cetak</button>

<?php
helper('tanggal');
$n = fn($v) => $v > 0 ? number_format($v) : '—';
$jenisLabel  = ['coding'=>'Coding','debugging'=>'Debugging','ideating'=>'Ideating','menulis'=>'Menulis','riset'=>'Riset','lainnya'=>'Lainnya','Belum'=>'Belum'];
$kantorLabel = ['kantor'=>'Kantor','pribadi'=>'Pribadi','tak_jelas'=>'Tak jelas','Belum'=>'Belum'];

/** Tiga tabel breakdown (jenis / kantor / tema) untuk satu $analisa. */
$renderBreakdown = function (array $a) use ($jenisLabel, $kantorLabel, $n) {
    $totJ = array_sum($a['jenis']);
    $totK = array_sum($a['kantor']);
    $jenis = $a['jenis']; arsort($jenis);
    $kantor = $a['kantor']; arsort($kantor);
    ?>
    <div class="duo">
    <div>
        <div class="sec-title"><span>Jenis Aktivitas</span></div>
        <table class="main-table">
        <thead><tr><th>Jenis</th><th class="text-center">Sesi</th><th class="text-center">%</th></tr></thead>
        <tbody>
        <?php if ($totJ === 0): ?>
            <tr><td colspan="3" style="text-align:center;color:#94a3b8;padding:10px">Belum ada sesi terklasifikasi</td></tr>
        <?php else: foreach ($jenis as $k => $v): ?>
            <tr><td><?= esc($jenisLabel[$k] ?? $k) ?></td>
                <td class="num"><?= $n($v) ?></td>
                <td class="num"><?= round($v / $totJ * 100) ?>%</td></tr>
        <?php endforeach; endif; ?>
        </tbody>
        </table>
    </div>
    <div>
        <div class="sec-title"><span>Kantor vs Pribadi</span></div>
        <table class="main-table">
        <thead><tr><th>Kategori</th><th class="text-center">Sesi</th><th class="text-center">%</th></tr></thead>
        <tbody>
        <?php if ($totK === 0): ?>
            <tr><td colspan="3" style="text-align:center;color:#94a3b8;padding:10px">Belum ada sesi terklasifikasi</td></tr>
        <?php else: foreach ($kantor as $k => $v): ?>
            <tr><td><?= esc($kantorLabel[$k] ?? $k) ?></td>
                <td class="num"><?= $n($v) ?></td>
                <td class="num"><?= round($v / $totK * 100) ?>%</td></tr>
        <?php endforeach; endif; ?>
        </tbody>
        </table>
    </div>
    <div>
        <div class="sec-title"><span>Top Tema</span></div>
        <table class="main-table">
        <thead><tr><th>Tema</th><th class="text-center">Sesi</th></tr></thead>
        <tbody>
        <?php if (empty($a['tema'])): ?>
            <tr><td colspan="2" style="text-align:center;color:#94a3b8;padding:10px">Belum ada tema</td></tr>
        <?php else: foreach ($a['tema'] as $t): ?>
            <tr><td><?= esc($t['tema']) ?></td><td class="num"><?= $n($t['jumlah']) ?></td></tr>
        <?php endforeach; endif; ?>
        </tbody>
        </table>
    </div>
    </div>
    <?php
};
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

<?php
    // ══ Laporan satu individu (karyawan / komputer) ══
    $tot = $analisa['total'];
    $pk  = $analisa['pct_kantor'];
    $kantorRendah = $pk !== null && $pk < $ambang_kantor;
?>
<?php if ($kantorRendah): ?>
<div style="border:1px solid #fecaca;background:#fef2f2;color:#b91c1c;border-radius:6px;padding:7px 11px;margin-bottom:14px;font-size:11px;font-weight:700">
    &#9888; Porsi pemakaian untuk KANTOR pada periode ini baru <?= $pk ?>% — di bawah ambang <?= $ambang_kantor ?>%.
</div>
<?php endif; ?>

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
    <div class="kpi-box <?= $kantorRendah ? '' : 'kpi-green' ?>" style="<?= $kantorRendah ? 'border-color:#fecaca;background:#fef2f2' : '' ?>">
        <div class="kpi-label">% Kantor</div>
        <div class="kpi-num" style="<?= $kantorRendah ? 'color:#b91c1c' : '' ?>"><?= $pk === null ? 'N/A' : $pk . '%' ?></div>
        <div class="kpi-sub"><?= $pk === null ? 'tidak ada sesi' : ('ambang ' . $ambang_kantor . '%') ?></div>
    </div>
</div>

<?php $renderBreakdown($analisa); ?>

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

<!-- ══ FOOTER ══ -->
<div class="doc-footer">
    <span>Mall Intelligence Center &mdash; Laporan Pemantauan AI</span>
    <span><?= esc($bulanLabel) ?> &middot; <?= esc($scopeLabel) ?></span>
</div>

</body>
</html>
