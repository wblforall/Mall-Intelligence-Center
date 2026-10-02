<?php
$n = fn($v) => $v === null || $v === '' ? '-' : rtrim(rtrim(number_format((float)$v,2),'0'),'.');
$bobotKpi = (float)$form['bobot_kpi']; $bobotKomp = (float)$form['bobot_kompetensi'];
$grouped = [];
foreach ($kpis as $k) $grouped[$k['area']][] = $k;
$skorKpi = $form['skor_kpi']; $skorKomp = $form['skor_kompetensi']; $nilai = $form['nilai_akhir'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Penilaian KPI — <?= esc($form['employee_nama']) ?></title>
<?= view('_laporan/_dokumen', ['labelHalaman' => 'Penilaian Kinerja (KPI) · ' . strip_tags((string) $form['employee_nama'])]) ?>
<style>
  .dk-tabel td.c, .dk-tabel th.c { text-align: center; }
  .dk-tabel td.nilai { width: 70px; text-align: center; font-weight: 700; color: var(--tinta); }
  .desk { display: block; margin-top: 1px; color: var(--redup); font-size: 9px; }
  .catatan-skala { margin-top: 10px; font-size: 9px; color: var(--redup); }
</style>
</head>
<body onload="window.print()">

<button class="dk-tombol no-print" onclick="window.print()">Cetak / Simpan PDF</button>

<header class="dk-kop">
  <div>
    <div class="dk-label">Mall Intelligence Center</div>
    <h1 class="dk-judul">Form Penilaian Kinerja (KPI)</h1>
    <div class="dk-sub">PT. Wulandari Bangun Laksana Tbk. — Mall Intelligence Center</div>
  </div>
  <img class="dk-logo" src="<?= base_url('img/mic-logo.png') ?>" alt="MIC">
</header>
<div class="dk-pita"></div>

<dl class="dk-identitas">
  <div><dt>Nama</dt><dd><?= esc($form['employee_nama']) ?></dd></div>
  <div><dt>Periode</dt><dd><?= esc($form['periode_nama'] ?? '-') ?></dd></div>
  <div><dt>NIK</dt><dd><?= esc($form['nik'] ?? '-') ?></dd></div>
  <div><dt>Departemen</dt><dd><?= esc($form['dept_name'] ?? '-') ?></dd></div>
  <div><dt>Jabatan</dt><dd><?= esc($form['jabatan_nama'] ?? '-') ?></dd></div>
  <div><dt>Status</dt><dd><?= ucfirst($form['status']) ?></dd></div>
</dl>

<h2 class="dk-bagian">Key Performance Indicators (KPI) <span class="dk-bagian-ket">Bobot <?= (int)($bobotKpi*100) ?>%</span></h2>
<table class="dk-tabel">
  <thead>
  <tr>
    <th style="width:30%">Indikator</th><th class="c">Unit</th><th class="c">Bobot</th><th class="c">Target</th><th class="c">Realisasi</th><th class="c">Skor</th><th class="c">Skor Akhir</th>
  </tr>
  </thead>
  <tbody>
  <?php foreach ($grouped as $area => $rows): ?>
  <tr class="dk-grup"><td colspan="7"><?= esc($areas[$area] ?? $area) ?></td></tr>
  <?php foreach ($rows as $k): $akhir = $k['skor']!==null ? (float)$k['bobot']*(float)$k['skor']/100 : null; ?>
  <tr>
    <td><?= esc($k['indikator']) ?></td>
    <td class="c"><?= esc($units[$k['unit']] ?? $k['unit']) ?></td>
    <td class="c"><?= $n($k['bobot']) ?></td>
    <td class="c"><?= $n($k['target']) ?></td>
    <td class="c"><?= $n($k['realisasi']) ?></td>
    <td class="c"><?= $n($k['skor']) ?></td>
    <td class="r"><?= $n($akhir) ?></td>
  </tr>
  <?php endforeach; endforeach; ?>
  <tr class="dk-total"><td colspan="6" class="r">Total Skor KPI</td><td class="r"><?= $n($skorKpi) ?></td></tr>
  </tbody>
</table>

<h2 class="dk-bagian">Aspek Kompetensi <span class="dk-bagian-ket">Bobot <?= (int)($bobotKomp*100) ?>% · skala 1–5</span></h2>
<table class="dk-tabel">
  <thead><tr><th>Aspek</th><th class="c" style="width:70px">Nilai</th></tr></thead>
  <tbody>
  <?php foreach ($comps as $c): ?>
  <tr>
    <td><b><?= esc($c['nama_aspek']) ?></b><?php if ($c['deskripsi']): ?><span class="desk"><?= esc($c['deskripsi']) ?></span><?php endif; ?></td>
    <td class="nilai"><?= $c['nilai'] !== null ? (int)$c['nilai'] : '-' ?></td>
  </tr>
  <?php endforeach; ?>
  <tr class="dk-total"><td class="r">Skor Kompetensi (rata-rata × 20)</td><td class="c"><?= $n($skorKomp) ?></td></tr>
  </tbody>
</table>

<h2 class="dk-bagian">Penilaian Hasil Kerja (Final Review)</h2>
<table class="dk-tabel">
  <thead><tr><th>Jenis</th><th class="c">Bobot</th><th class="c">Skor</th><th class="c">Hasil</th></tr></thead>
  <tbody>
  <tr><td>Key Performance Indicator</td><td class="c"><?= $bobotKpi ?></td><td class="c"><?= $n($skorKpi) ?></td><td class="r"><?= $skorKpi!==null?$n($skorKpi*$bobotKpi):'-' ?></td></tr>
  <tr><td>Kompetensi</td><td class="c"><?= $bobotKomp ?></td><td class="c"><?= $n($skorKomp) ?></td><td class="r"><?= $skorKomp!==null?$n($skorKomp*$bobotKomp):'-' ?></td></tr>
  <tr class="dk-total"><td colspan="3" class="r">NILAI AKHIR</td><td class="r"><?= $n($nilai) ?></td></tr>
  </tbody>
</table>

<h2 class="dk-bagian">Pendapat Karyawan</h2>
<table class="dk-tabel">
  <tbody><tr><td class="dk-isian" style="height:64px"><?= nl2br(esc($form['pendapat_karyawan'] ?? '')) ?></td></tr></tbody>
</table>

<div class="dk-utuh">
<div class="dk-ttd">
  <div><div class="dk-ttd-peran">Penilai / Atasan Langsung</div><div class="dk-ttd-ruang"></div><div class="dk-ttd-nama">&nbsp;</div></div>
  <div><div class="dk-ttd-peran">Kepala Departemen</div><div class="dk-ttd-ruang"></div><div class="dk-ttd-nama">&nbsp;</div></div>
  <div><div class="dk-ttd-peran">HR / Manajemen</div><div class="dk-ttd-ruang"></div><div class="dk-ttd-nama">&nbsp;</div></div>
</div>

<p class="catatan-skala">Skala: 5=Excellent, 4=Good, 3=Standard, 2=Need Improvement, 1=Unacceptable.</p>

<footer class="dk-kaki">
  <span><b>Mall Intelligence Center</b> · dicetak <?= date('d/m/Y H:i') ?></span>
  <span>Penilaian KPI — <?= esc($form['employee_nama']) ?></span>
</footer>
</div>
</body>
</html>
