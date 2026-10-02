<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan Pemantauan AI — <?= esc($bulanLabel) ?></title>
<?= $this->include('_laporan/_style') ?>
<style>
/* Penanda baris/angka kantor di bawah ambang */
.flag-low td { background: #fef2f2 !important; }
.pct-low  { color: #b91c1c; font-weight: 700; }
.pct-ok   { color: #15803d; font-weight: 700; }
.badge-low { display:inline-block; background:#b91c1c; color:#fff; font-size:9px; font-weight:700;
             padding:1px 6px; border-radius:4px; margin-left:6px; }
/* Blok per karyawan: jaga keutuhan, mulai halaman baru antar karyawan */
.user-block { break-inside: avoid; page-break-inside: avoid; margin-bottom: 16px; }
.user-block + .user-block { break-before: page; page-break-before: always; }
.user-sub { font-weight: 400; font-size: 9.5px; opacity: .85; }
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

<?php if ($isGlobal): ?>
<?php
    $jmlKaryawan = count($perUser ?? []);
    $tg = $analisa['total'];
    $pkG = $analisa['pct_kantor'];
?>
<!-- Ringkasan bulan (per karyawan) -->
<div class="kpi-row">
    <div class="kpi-box kpi-blue">
        <div class="kpi-label">Karyawan Aktif</div>
        <div class="kpi-num"><?= number_format($jmlKaryawan) ?></div>
        <div class="kpi-sub"><?= number_format((int) $komputerAktif) ?> komputer aktif</div>
    </div>
    <div class="kpi-box kpi-purple">
        <div class="kpi-label">Total Sesi / Prompt</div>
        <div class="kpi-num" style="font-size:16px;padding-top:3px"><?= number_format($tg['sesi']) ?> / <?= number_format($tg['prompt']) ?></div>
        <div class="kpi-sub"><?= number_format($tg['token']) ?> token</div>
    </div>
    <div class="kpi-box <?= $pkG !== null && $pkG < $ambang_kantor ? '' : 'kpi-green' ?>" style="<?= $pkG !== null && $pkG < $ambang_kantor ? 'border-color:#fecaca;background:#fef2f2' : '' ?>">
        <div class="kpi-label">% Kantor Keseluruhan</div>
        <div class="kpi-num" style="<?= $pkG !== null && $pkG < $ambang_kantor ? 'color:#b91c1c' : '' ?>"><?= $pkG === null ? 'N/A' : $pkG . '%' ?></div>
        <div class="kpi-sub">ambang <?= $ambang_kantor ?>%</div>
    </div>
    <div class="kpi-box <?= $jmlRendah > 0 ? 'kpi-amber' : 'kpi-green' ?>">
        <div class="kpi-label">Karyawan &lt; <?= $ambang_kantor ?>% Kantor</div>
        <div class="kpi-num"><?= number_format($jmlRendah) ?></div>
        <div class="kpi-sub">dari <?= number_format($jmlKaryawan) ?> karyawan</div>
    </div>
</div>

<?php if ($jmlRendah > 0): ?>
<div style="border:1px solid #fecaca;background:#fef2f2;color:#b91c1c;border-radius:6px;padding:7px 11px;margin-bottom:16px;font-size:11px;font-weight:700">
    &#9888; <?= $jmlRendah ?> dari <?= $jmlKaryawan ?> karyawan memiliki porsi pemakaian KANTOR di bawah ambang <?= $ambang_kantor ?>% — ditandai merah di tiap blok di bawah.
</div>
<?php endif; ?>

<!-- ══ BLOK PER KARYAWAN (urut %kantor terendah dulu) ══ -->
<?php if (empty($perUser)): ?>
<div style="text-align:center;color:#94a3b8;padding:24px;border:1px solid #e2e8f0;border-radius:6px">
    Belum ada karyawan dengan aktivitas pada <?= esc($bulanLabel) ?>.
</div>
<?php else: foreach ($perUser as $u):
    $info = $u['info']; $a = $u['analisa'];
    $pk   = $a['pct_kantor'];
    $low  = $pk !== null && $pk < $ambang_kantor;
    $tt   = $a['total'];
?>
<div class="user-block">
    <div class="sec-title">
        <span><i>&#128100;</i> <?= esc($info['nama']) ?><?= $info['dept'] && $info['dept'] !== '—' ? ' <span class="user-sub">— ' . esc($info['dept']) . '</span>' : '' ?></span>
        <span class="user-sub">
            <?= number_format($tt['sesi']) ?> sesi &middot; <?= number_format($tt['prompt']) ?> prompt &middot; <?= number_format($tt['token']) ?> token
            &middot; Kantor
            <?php if ($pk === null): ?>N/A
            <?php else: ?><strong style="color:<?= $low ? '#fecaca' : '#bbf7d0' ?>"><?= $pk ?>%</strong><?= $low ? ' &#9888;' : '' ?><?php endif; ?>
        </span>
    </div>

    <?php if ($low): ?>
    <div style="border:1px solid #fecaca;background:#fef2f2;color:#b91c1c;border-radius:0 0 6px 6px;padding:6px 10px;margin:-2px 0 10px;font-size:10.5px;font-weight:700">
        &#9888; Porsi pemakaian KANTOR hanya <?= $pk ?>% — di bawah ambang <?= $ambang_kantor ?>%.
    </div>
    <?php else: ?>
    <div style="height:8px"></div>
    <?php endif; ?>

    <?php $renderBreakdown($a); ?>
</div>
<?php endforeach; endif; ?>

<?php else: ?>
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
<?php endif; ?>

<!-- ══ FOOTER ══ -->
<div class="doc-footer">
    <span>Mall Intelligence Center &mdash; Laporan Pemantauan AI</span>
    <span><?= esc($bulanLabel) ?> &middot; <?= esc($scopeLabel) ?></span>
</div>

</body>
</html>
