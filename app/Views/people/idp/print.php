<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>IDP — <?= esc($plan['employee_nama']) ?> · <?= esc($plan['periode_label']) ?></title>
<?= view('_laporan/_dokumen', ['labelHalaman' => 'IDP · ' . strip_tags((string) $plan['employee_nama'])]) ?>
<style>
.progres-label { font-size: 9px; color: var(--redup); margin-bottom: 3px; }
.progress-wrap { background: var(--garis); border-radius: 999px; height: 7px; margin-bottom: 10px; }
.progress-bar  { background: linear-gradient(90deg, var(--navy-3), var(--emas)); border-radius: 999px; height: 7px; }
.kecil { display: block; color: var(--redup); font-size: 8.5px; margin-top: 1px; }
.kosong { color: var(--redup); }
</style>
</head>
<body>
<button class="dk-tombol no-print" onclick="window.print()">Cetak / Simpan PDF</button>

<header class="dk-kop">
    <div>
        <div class="dk-label">Mall Intelligence Center · People Development</div>
        <h1 class="dk-judul">Individual Development Plan</h1>
        <div class="dk-sub">PT. Wulandari Bangun Laksana Tbk. — IT Department · Dicetak: <?= date('d M Y') ?></div>
    </div>
    <img class="dk-logo" src="<?= base_url('img/mic-logo.png') ?>" alt="MIC">
</header>
<div class="dk-pita"></div>

<h2 class="dk-bagian">Informasi IDP</h2>
<dl class="dk-identitas">
    <div><dt>Karyawan</dt><dd><?= esc($plan['employee_nama']) ?></dd></div>
    <div><dt>Departemen</dt><dd><?= esc($plan['dept_name'] ?? '-') ?></dd></div>
    <div><dt>Jabatan</dt><dd><?= esc($plan['jabatan'] ?? '-') ?></dd></div>
    <div><dt>Atasan</dt><dd><?= esc($plan['atasan_nama'] ?? '-') ?></dd></div>
    <div><dt>Periode</dt><dd><?= esc($plan['periode_label']) ?></dd></div>
    <div><dt>Tahun</dt><dd><?= $plan['tahun'] ?></dd></div>
    <div><dt>Status</dt><dd><?php
        $sl = ['draft'=>'Draft','aktif'=>'Aktif','selesai'=>'Selesai','dibatalkan'=>'Dibatalkan'];
        echo '<span class="dk-lencana biru">' . ($sl[$plan['status']] ?? $plan['status']) . '</span>';
    ?></dd></div>
    <div><dt>Persetujuan Atasan</dt><dd><?php
        $al = ['pending'=>'Menunggu','setuju'=>'Disetujui','menolak'=>'Ditolak'];
        echo $al[$plan['persetujuan_atasan']] ?? '-';
        if ($plan['approved_at']) echo ' · ' . date('d M Y', strtotime($plan['approved_at']));
    ?></dd></div>
    <?php if ($plan['tujuan_karir']): ?>
    <div class="penuh"><dt>Tujuan Karir</dt><dd><?= nl2br(esc($plan['tujuan_karir'])) ?></dd></div>
    <?php endif; ?>
</dl>

<h2 class="dk-bagian">Goal Pengembangan</h2>
    <?php if (empty($items)): ?>
    <p class="kosong">Belum ada goal.</p>
    <?php else: ?>
    <?php
    $totalItems   = count($items);
    $selesaiItems = count(array_filter($items, fn($i) => $i['status'] === 'selesai'));
    $pct          = $totalItems > 0 ? round($selesaiItems / $totalItems * 100) : 0;
    $isl = ['belum_mulai'=>'Belum Mulai','dalam_proses'=>'Dalam Proses','selesai'=>'Selesai','dibatalkan'=>'Dibatalkan'];
    ?>
    <div>
        <div class="progres-label">Progress: <?= $selesaiItems ?>/<?= $totalItems ?> goal selesai (<?= $pct ?>%)</div>
        <div class="progress-wrap"><div class="progress-bar" style="width:<?= $pct ?>%"></div></div>
    </div>
    <table class="dk-tabel">
        <thead>
            <tr>
                <th style="width:4%">#</th>
                <th style="width:22%">Goal / Kompetensi</th>
                <th style="width:8%">Level</th>
                <th style="width:28%">Langkah Aksi</th>
                <th style="width:10%">Deadline</th>
                <th style="width:13%">Status</th>
                <th>Progres</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $i => $item): ?>
        <tr>
            <td><?= $i + 1 ?></td>
            <td>
                <strong><?= esc($item['judul']) ?></strong>
                <?php if ($item['competency_nama']): ?>
                <small class="kecil"><?= esc($item['competency_nama']) ?></small>
                <?php endif; ?>
            </td>
            <td style="text-align:center">
                <?= $item['level_saat_ini'] ? number_format((float)$item['level_saat_ini'], 1) : '-' ?>
                <?= ($item['level_saat_ini'] && $item['level_target']) ? ' → ' . $item['level_target'] : '' ?>
            </td>
            <td><?= nl2br(esc($item['langkah_aksi'] ?? '-')) ?></td>
            <td><?= $item['deadline'] ? date('d M Y', strtotime($item['deadline'])) : '-' ?></td>
            <td><?= $isl[$item['status']] ?? $item['status'] ?></td>
            <td><?= nl2br(esc($item['catatan_progres'] ?? '-')) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

<footer class="dk-kaki">
    <span><b>Mall Intelligence Center</b> v1.9 — IT Dept PT. Wulandari Bangun Laksana Tbk.</span>
    <span>Individual Development Plan</span>
</footer>
<script>window.onload = function(){ window.print(); }</script>
</body>
</html>
