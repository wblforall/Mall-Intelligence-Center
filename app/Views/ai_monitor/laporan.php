<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan Pemantauan AI — <?= esc($bulanLabel) ?></title>
<?= $this->include('_laporan/_style') ?>
<style>
/* ── Lokal: Laporan Pemantauan AI (individu) ─────────────────────────────── */
:root {
    --k-kantor: #16a34a; --k-pribadi: #dc2626; --k-takjelas: #d97706; --k-belum: #94a3b8;
}

/* Halaman 1 = dasbor: rapatkan jarak antarblok supaya muat satu halaman. */
.doc-header { margin-bottom: 14px; }
.kpi-row { margin-bottom: 14px; }
.catatan { margin-bottom: 12px; }
.panel-ringkas { margin-bottom: 14px; padding: 10px; }
.duo .main-table { margin-bottom: 0; }
.duo { margin-bottom: 18px; }
.sec-title { margin-bottom: 8px; }

/* Panel ringkasan: narasi lebar di kiri, komposisi di kanan. */
.panel-ringkas .insight-box { flex: 1 1 58%; display: flex; flex-direction: column; }
.panel-ringkas .chart-box   { flex: 1 1 42%; }
.insight-head { display: flex; align-items: center; gap: 8px; margin-bottom: 7px; }
.insight-head .insight-title { margin-bottom: 0; }
.insight-head .lencana { margin-left: auto; }
.narasi {
    font-size: 10.5px; line-height: 1.55; color: var(--tinta); text-align: justify; hyphens: auto;
}
.ringkas-kosong { font-size: 10.5px; color: var(--redup); font-style: italic; }

/* Bilah komposisi bertumpuk (ala "Komposisi sesi" OpsJobs). */
.komposisi-bar {
    display: flex; height: 12px; border-radius: 6px; overflow: hidden;
    background: var(--garis); margin: 2px 0 8px;
}
.komposisi-bar > i { display: block; height: 100%; }
.komposisi-bar > i + i { box-shadow: inset 1.5px 0 0 #fff; }
.legenda { display: grid; grid-template-columns: 1fr 1fr; gap: 4px 18px; margin-bottom: 9px; }
.legenda > div { display: flex; align-items: center; gap: 6px; font-size: 9.5px; color: var(--teks); min-width: 0; }
.legenda .titik { flex: 0 0 9px; width: 9px; height: 9px; border-radius: 2.5px; }
.legenda b { margin-left: auto; font-weight: 600; color: var(--tinta); font-variant-numeric: tabular-nums; white-space: nowrap; }
.legenda b small { font-weight: 400; color: var(--redup); font-size: 8.5px; }
.chart-sub { font-size: 8.5px; color: var(--redup); margin: -3px 0 7px; }
.panel-ringkas .deret-angka { margin-bottom: 0; }
.panel-ringkas .deret-angka b { font-size: 11.5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

/* Bilah porsi kecil di sel tabel sebaran. */
.porsi { display: flex; align-items: center; justify-content: flex-end; gap: 7px; }
.porsi .jalur { flex: 0 0 64px; height: 6px; border-radius: 3px; background: var(--garis-halus); overflow: hidden; }
.porsi .jalur > i { display: block; height: 100%; border-radius: 3px; background: var(--navy-3); }
.porsi span { min-width: 30px; text-align: right; font-variant-numeric: tabular-nums; }
.tbl-sebaran th { padding-top: 5px; padding-bottom: 5px; }
.tbl-sebaran td { font-size: 9.5px; padding-top: 2.5px; padding-bottom: 2.5px; }
.tbl-sebaran td:first-child { white-space: nowrap; }

/* KPI % kantor. */
.kpi-sub .lencana { margin-right: 4px; }

/* Lencana jenis — warna kategoris, bukan nada baik/buruk. */
.lencana.j-coding    { color: #1d4ed8; background: #dbeafe; }
.lencana.j-debugging { color: #b45309; background: #fef3c7; }
.lencana.j-ideating  { color: #6d28d9; background: #ede9fe; }
.lencana.j-menulis   { color: #7a5b14; background: #f3e7c4; }
.lencana.j-riset     { color: #0f766e; background: #ccfbf1; }
.lencana.j-lainnya   { color: #475569; background: #f1f5f9; }

/* Daftar sesi. */
.tbl-sesi td { vertical-align: top; padding-top: 7px; padding-bottom: 7px; }
.tbl-sesi td.no { color: var(--redup2); font-size: 9px; text-align: right; }
.sesi-judul { font-weight: 600; color: var(--tinta); font-size: 10.5px; line-height: 1.35; }
.sesi-judul .tanpa { font-weight: 400; color: var(--redup2); font-style: italic; }
.sesi-proyek {
    display: inline-block; margin-left: 5px; padding: 0 5px; border-radius: 4px; vertical-align: 1px;
    font-size: 8px; font-weight: 500; color: var(--redup); background: var(--latar); border: 1px solid var(--garis);
}
.sesi-snap {
    margin-top: 3px; padding-left: 7px; border-left: 2px solid var(--emas-pucat);
    font-size: 9px; line-height: 1.5; color: #475569;
}
.sesi-snap.kosong { color: var(--redup2); font-style: italic; }
.sesi-waktu { white-space: nowrap; font-size: 9.5px; color: var(--teks); }
.sesi-waktu small { display: block; font-size: 8.5px; color: var(--redup2); }
.strip { color: #cbd5e1; }
</style>
</head>
<body>

<button class="btn-print no-print" onclick="window.print()">&#128438; Cetak</button>

<?php
helper('tanggal');
$fmt = fn($v) => number_format((int) $v, 0, ',', '.');
$n   = fn($v) => $v > 0 ? number_format((int) $v, 0, ',', '.') : '—';
$jenisLabel  = ['coding'=>'Coding','debugging'=>'Debugging','ideating'=>'Ideating','menulis'=>'Menulis','riset'=>'Riset','lainnya'=>'Lainnya','Belum'=>'Belum terklasifikasi'];
$kantorLabel = ['kantor'=>'Kantor','pribadi'=>'Pribadi','tak_jelas'=>'Tak jelas','Belum'=>'Belum terklasifikasi'];
$kantorNada  = ['kantor'=>'baik','pribadi'=>'buruk','tak_jelas'=>'waspada','Belum'=>'netral'];
$kantorWarna = ['kantor'=>'var(--k-kantor)','pribadi'=>'var(--k-pribadi)','tak_jelas'=>'var(--k-takjelas)','Belum'=>'var(--k-belum)'];
$bulanPendek = [1=>'Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];

/** Lencana jenis aktivitas; '—' bila belum terklasifikasi. */
$lencanaJenis = function ($j) use ($jenisLabel) {
    if (! $j) return '<span class="strip">—</span>';
    $cls = isset($jenisLabel[$j]) && $j !== 'Belum' ? 'j-' . $j : 'netral';
    return '<span class="lencana ' . $cls . '">' . esc($jenisLabel[$j] ?? $j) . '</span>';
};
/** Lencana kategori kantor/pribadi/tak jelas. */
$lencanaKantor = function ($k) use ($kantorLabel, $kantorNada) {
    if (! $k) return '<span class="lencana netral">Belum</span>';
    return '<span class="lencana ' . ($kantorNada[$k] ?? 'netral') . '">' . esc($kantorLabel[$k] ?? $k) . '</span>';
};
/** Bilah porsi kecil + persentase untuk sel tabel. */
$porsi = function (int $v, int $tot, string $warna = '') {
    $p = $tot > 0 ? round($v / $tot * 100) : 0;
    $gaya = $warna !== '' ? 'background:' . $warna . ';' : '';
    return '<div class="porsi"><div class="jalur"><i style="width:' . $p . '%;' . $gaya . '"></i></div><span>' . $p . '%</span></div>';
};

// ── Angka pokok ─────────────────────────────────────────────────────────────
$tot = $analisa['total'];
$pk  = $analisa['pct_kantor'];
$kantorRendah = $pk !== null && $pk < $ambang_kantor;
$sesiList = $sesiList ?? [];

$kMap   = $analisa['kantor'];
$totK   = array_sum($kMap);
$nKantor  = $kMap['kantor'] ?? 0;
$nPribadi = $kMap['pribadi'] ?? 0;
$nPasti   = $nKantor + $nPribadi;

$jAgg = $analisa['jenis']; unset($jAgg['Belum']);
$jDom = '';
if ($jAgg) { arsort($jAgg); $jk = array_key_first($jAgg); $jDom = $jenisLabel[$jk] ?? $jk; }
$temaTop = $analisa['tema'][0]['tema'] ?? '';
$hariAktif = count(array_filter($analisa['tren'] ?? [], fn($t) => ($t['prompt'] ?? 0) > 0));
$promptPerSesi = $tot['sesi'] > 0 ? round($tot['prompt'] / $tot['sesi'], 1) : null;

// ── Ringkasan Aktivitas: sintesis AI bila ada, jatuh ke kalimat dari angka ──
$rpAi = trim((string) ($ringkasan_periode ?? ''));
$rpDariAi = $rpAi !== '';
if ($rpDariAi) {
    $rp = $rpAi;
} elseif ($tot['sesi'] === 0 && $sesiList === []) {
    $rp = '';
} else {
    $rp = $fmt($tot['sesi']) . ' sesi dengan ' . $fmt($tot['prompt']) . ' prompt pada ' . $bulanLabel
        . ($hariAktif > 0 ? ', tersebar di ' . $hariAktif . ' hari aktif' : '')
        . ($jDom !== '' ? '; jenis aktivitas terbanyak ' . $jDom : '')
        . ($temaTop !== '' ? ', tema terbanyak "' . $temaTop . '"' : '')
        . ($pk !== null ? '; porsi kantor ' . $pk . '% (' . ($kantorRendah ? 'di bawah' : 'memenuhi') . ' ambang ' . $ambang_kantor . '%)' : '')
        . '.';
}

/** Tabel sebaran satu dimensi: label (HTML), jumlah, bilah porsi. */
$tabelSebaran = function (string $kepala, array $baris, int $total, string $kosong) use ($n, $porsi) {
    ?>
    <table class="main-table tbl-sebaran">
    <thead><tr><th><?= esc($kepala) ?></th><th class="num" style="width:44px">Sesi</th><th class="num" style="width:112px">Porsi</th></tr></thead>
    <tbody>
    <?php if ($total === 0 || $baris === []): ?>
        <tr class="empty-row"><td colspan="3"><?= esc($kosong) ?></td></tr>
    <?php else: foreach ($baris as $b): ?>
        <tr><td><?= $b['label'] ?></td>
            <td class="num"><?= $n($b['v']) ?></td>
            <td class="num"><?= $porsi($b['v'], $total, $b['warna'] ?? '') ?></td></tr>
    <?php endforeach; endif; ?>
    </tbody>
    </table>
    <?php
};
?>

<!-- ══ KOP ══ -->
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
<div class="catatan buruk">
    <b style="color:inherit">Porsi pemakaian untuk kantor <?= $pk ?>% &mdash; di bawah ambang <?= $ambang_kantor ?>%.</b>
    Dari <?= $fmt($nPasti) ?> sesi yang kategorinya pasti, <?= $fmt($nKantor) ?> untuk kantor dan <?= $fmt($nPribadi) ?> untuk keperluan pribadi.
</div>
<?php endif; ?>

<!-- ══ KPI ══ -->
<div class="kpi-row">
    <div class="kpi-box kpi-blue">
        <div class="kpi-label">Jumlah Sesi</div>
        <div class="kpi-num"><?= $fmt($tot['sesi']) ?></div>
        <div class="kpi-sub"><?= $hariAktif > 0 ? $hariAktif . ' hari aktif dalam bulan ini' : 'tidak ada hari aktif' ?></div>
    </div>
    <div class="kpi-box kpi-purple">
        <div class="kpi-label">Jumlah Prompt</div>
        <div class="kpi-num"><?= $fmt($tot['prompt']) ?></div>
        <div class="kpi-sub"><?= $promptPerSesi !== null ? 'rata-rata ' . number_format($promptPerSesi, 1, ',', '.') . ' prompt per sesi' : '—' ?></div>
    </div>
    <div class="kpi-box kpi-gold">
        <div class="kpi-label">Jumlah Token</div>
        <div class="kpi-num"><?= $fmt($tot['token']) ?></div>
        <div class="kpi-sub">token masuk + keluar</div>
    </div>
    <div class="kpi-box <?= $pk === null ? '' : ($kantorRendah ? 'kpi-red' : 'kpi-green') ?>">
        <div class="kpi-label">% Kantor</div>
        <div class="kpi-num" style="<?= $kantorRendah ? 'color:#b91c1c' : ($pk === null ? 'color:#94a3b8' : '') ?>"><?= $pk === null ? 'N/A' : $pk . '%' ?></div>
        <div class="kpi-sub">
            <?php if ($pk === null): ?>
                belum ada sesi kantor/pribadi &middot; ambang <?= $ambang_kantor ?>%
            <?php else: ?>
                <span class="lencana <?= $kantorRendah ? 'buruk' : 'baik' ?>"><?= $kantorRendah ? 'Di bawah' : 'Memenuhi' ?> ambang <?= $ambang_kantor ?>%</span><?= $fmt($nKantor) ?> dari <?= $fmt($nPasti) ?> sesi
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ══ RINGKASAN AKTIVITAS ══ -->
<div class="sec-title"><span>Ringkasan Aktivitas</span>
    <span class="sec-sub"><?= esc($bulanLabel) ?><?= $rpDariAi ? ' &middot; disintesis AI dari ringkasan tiap sesi, periksa kembali dengan daftar sesi' : '' ?></span></div>
<div class="chart-panel panel-ringkas">
    <div class="insight-box">
        <div class="insight-head">
            <div class="insight-title">Ringkasan periode</div>
            <?php if ($rpDariAi): ?><span class="lencana ungu">Disusun AI</span>
            <?php elseif ($rp !== ''): ?><span class="lencana netral">Dari angka pemakaian</span><?php endif; ?>
        </div>
        <?php if ($rp === ''): ?>
            <div class="ringkas-kosong">Tidak ada aktivitas Claude Code yang tercatat pada <?= esc($bulanLabel) ?>.</div>
        <?php else: ?>
            <div class="narasi"><?= esc($rp) ?></div>
        <?php endif; ?>
    </div>
    <div class="chart-box">
        <div class="chart-title">Komposisi sesi: kantor vs pribadi</div>
        <div class="chart-sub"><?= $totK > 0 ? $fmt($totK) . ' sesi dengan aktivitas terakhir pada ' . esc($bulanLabel) : 'Belum ada sesi pada periode ini' ?></div>
        <div class="komposisi-bar">
            <?php if ($totK > 0): foreach (['kantor','pribadi','tak_jelas','Belum'] as $k): $v = $kMap[$k] ?? 0; if ($v <= 0) continue; ?>
                <i style="width:<?= round($v / $totK * 100, 2) ?>%;background:<?= $kantorWarna[$k] ?>"></i>
            <?php endforeach; endif; ?>
        </div>
        <div class="legenda">
            <?php foreach (['kantor','pribadi','tak_jelas','Belum'] as $k): $v = $kMap[$k] ?? 0; ?>
                <div><span class="titik" style="background:<?= $kantorWarna[$k] ?>"></span><?= esc($kantorLabel[$k]) ?>
                    <b><?= $fmt($v) ?> <small>&middot; <?= $totK > 0 ? round($v / $totK * 100) : 0 ?>%</small></b></div>
            <?php endforeach; ?>
        </div>
        <?php if ($totK > 0): ?>
        <div class="deret-angka">
            <div><span>Jenis terbanyak</span><b><?= $jDom !== '' ? esc($jDom) : '—' ?></b></div>
            <div><span>Tema terbanyak</span><b title="<?= esc($temaTop) ?>"><?= $temaTop !== '' ? esc($temaTop) : '—' ?></b></div>
            <div><span>% Kantor</span><b class="<?= $pk === null ? '' : ($kantorRendah ? 'buruk' : 'baik') ?>"><?= $pk === null ? 'N/A' : $pk . '%' ?></b></div>
        </div>
        <div class="chart-sub" style="margin:7px 0 0">% kantor = kantor &divide; (kantor + pribadi); sesi tak jelas &amp; belum terklasifikasi tidak dihitung.</div>
        <?php endif; ?>
    </div>
</div>

<!-- ══ SEBARAN ══ -->
<?php
$jenisSort = $analisa['jenis']; arsort($jenisSort);
// 'Belum' selalu di akhir.
if (isset($jenisSort['Belum'])) { $b = $jenisSort['Belum']; unset($jenisSort['Belum']); $jenisSort['Belum'] = $b; }
$barisJenis = [];
foreach ($jenisSort as $k => $v) $barisJenis[] = ['label' => $lencanaJenis($k), 'v' => $v];

$barisKantor = [];
foreach (['kantor','pribadi','tak_jelas','Belum'] as $k) {
    if (($kMap[$k] ?? 0) > 0) $barisKantor[] = ['label' => $lencanaKantor($k === 'Belum' ? null : $k), 'v' => $kMap[$k], 'warna' => $kantorWarna[$k]];
}
foreach ($kMap as $k => $v) { // kategori tak dikenal (jaga-jaga)
    if (! isset($kantorWarna[$k]) && $v > 0) $barisKantor[] = ['label' => esc($k), 'v' => $v];
}

$totTema = $totK; // porsi tema terhadap seluruh sesi periode
$barisTema = [];
foreach ($analisa['tema'] as $t) $barisTema[] = ['label' => esc($t['tema']), 'v' => $t['jumlah'], 'warna' => 'var(--emas)'];
?>
<div class="duo">
    <div>
        <div class="sec-title"><span>Jenis Aktivitas</span></div>
        <?php $tabelSebaran('Jenis', $barisJenis, array_sum($analisa['jenis']), 'Belum ada sesi terklasifikasi.'); ?>
    </div>
    <div>
        <div class="sec-title"><span>Kantor vs Pribadi</span></div>
        <?php $tabelSebaran('Kategori', $barisKantor, $totK, 'Belum ada sesi terklasifikasi.'); ?>
    </div>
    <div>
        <div class="sec-title"><span>Top Tema</span><span class="sec-sub">5 teratas</span></div>
        <?php $tabelSebaran('Tema', $barisTema, $totTema, 'Belum ada tema.'); ?>
    </div>
</div>

<!-- ══ DAFTAR SESI ══ -->
<?php if ($sesiList !== []): ?><div class="putus-halaman"></div><?php endif; ?>
<div class="sec-title"><span>Daftar Sesi</span>
    <span class="sec-sub"><?= count($sesiList) ?> sesi pada <?= esc($bulanLabel) ?> &middot; terbaru di atas</span></div>
<table class="main-table tbl-sesi">
<thead><tr>
    <th class="num" style="width:26px">#</th>
    <th>Sesi &amp; ringkasan</th>
    <th style="width:16%">Tema</th>
    <th class="text-center" style="width:84px">Jenis</th>
    <th class="text-center" style="width:84px">Kategori</th>
    <th style="width:96px">Waktu</th>
    <th class="num" style="width:52px">Prompt</th>
</tr></thead>
<tbody>
<?php if ($sesiList === []): ?>
    <tr class="empty-row"><td colspan="7">Belum ada sesi pada <?= esc($bulanLabel) ?>.</td></tr>
<?php else: foreach ($sesiList as $i => $s): ?>
    <?php
        // Ringkasan snapshot per sesi: pakai ringkasan, jatuh ke tema, lalu kosong.
        $rsesi = trim((string) ($s['ringkasan'] ?? ''));
        if ($rsesi === '') $rsesi = trim((string) ($s['klasifikasi_tema'] ?? ''));
        $tsA = $s['mulai_at'] ? strtotime($s['mulai_at']) : null;
        $tsB = $s['terakhir_at'] ? strtotime($s['terakhir_at']) : null;
    ?>
    <tr>
        <td class="no"><?= $i + 1 ?></td>
        <td>
            <div class="sesi-judul">
                <?= ($s['judul'] ?? '') !== '' ? esc($s['judul']) : '<span class="tanpa">(tanpa judul)</span>' ?>
                <?php if (! empty($s['proyek'])): ?><span class="sesi-proyek"><?= esc($s['proyek']) ?></span><?php endif; ?>
            </div>
            <div class="sesi-snap<?= $rsesi === '' ? ' kosong' : '' ?>"><?= $rsesi !== '' ? esc($rsesi) : 'Belum ada ringkasan sesi.' ?></div>
        </td>
        <td><?= $s['klasifikasi_tema'] ? esc($s['klasifikasi_tema']) : '<span class="strip">—</span>' ?></td>
        <td class="text-center"><?= $lencanaJenis($s['klasifikasi_jenis']) ?></td>
        <td class="text-center"><?= $lencanaKantor($s['klasifikasi_kantor']) ?></td>
        <td class="sesi-waktu">
            <?php if ($tsB): ?>
                <?= date('j', $tsB) . ' ' . $bulanPendek[(int) date('n', $tsB)] . ' ' . date('Y', $tsB) ?>
                <small><?php if (! $tsA || date('Y-m-d', $tsA) === date('Y-m-d', $tsB)): ?><?= ($tsA ? date('H:i', $tsA) . '–' : '') . date('H:i', $tsB) ?><?php else: ?>mulai <?= date('j', $tsA) . ' ' . $bulanPendek[(int) date('n', $tsA)] . ', ' . date('H:i', $tsA) ?><?php endif; ?></small>
            <?php else: ?>—<?php endif; ?>
        </td>
        <td class="num"><?= $n((int) $s['jml_prompt']) ?></td>
    </tr>
<?php endforeach; endif; ?>
</tbody>
</table>

<!-- ══ KAKI ══ -->
<div class="doc-footer">
    <span>Mall Intelligence Center &mdash; Laporan Pemantauan AI</span>
    <span><?= esc($bulanLabel) ?> &middot; <?= esc($scopeLabel) ?></span>
</div>

</body>
</html>
