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
<style>
  * { font-family: Arial, sans-serif; }
  body { font-size: 11px; color: #000; margin: 24px; }
  h2 { text-align: center; margin: 0 0 2px; font-size: 15px; }
  .sub { text-align: center; margin-bottom: 12px; font-size: 11px; }
  table { border-collapse: collapse; width: 100%; margin-bottom: 10px; }
  th, td { border: 1px solid #555; padding: 4px 6px; vertical-align: top; }
  th { background: #eee; text-align: center; }
  .ident td { border: none; padding: 1px 4px; }
  .sec { background: #d9d9d9; font-weight: bold; text-transform: uppercase; }
  .r { text-align: right; } .c { text-align: center; }
  .area { background: #f3f3f3; font-weight: bold; }
  .ttd td { border: none; height: 60px; vertical-align: top; padding-top: 4px; }
  @media print { body { margin: 0; } .noprint { display: none; } }
</style>
</head>
<body onload="window.print()">

<h2>FORM PENILAIAN KINERJA (KPI)</h2>
<div class="sub">PT. Wulandari Bangun Laksana Tbk. — Mall Intelligence Center</div>

<table class="ident">
  <tr><td style="width:130px">Nama</td><td style="width:260px">: <?= esc($form['employee_nama']) ?></td>
      <td style="width:130px">Periode</td><td>: <?= esc($form['periode_nama'] ?? '-') ?></td></tr>
  <tr><td>NIK</td><td>: <?= esc($form['nik'] ?? '-') ?></td>
      <td>Departemen</td><td>: <?= esc($form['dept_name'] ?? '-') ?></td></tr>
  <tr><td>Jabatan</td><td>: <?= esc($form['jabatan_nama'] ?? '-') ?></td>
      <td>Status</td><td>: <?= ucfirst($form['status']) ?></td></tr>
</table>

<table>
  <tr><td class="sec" colspan="7">Key Performance Indicators (KPI) — Bobot <?= (int)($bobotKpi*100) ?>%</td></tr>
  <tr>
    <th style="width:30%">Indikator</th><th>Unit</th><th>Bobot</th><th>Target</th><th>Realisasi</th><th>Skor</th><th>Skor Akhir</th>
  </tr>
  <?php foreach ($grouped as $area => $rows): ?>
  <tr><td class="area" colspan="7"><?= esc($areas[$area] ?? $area) ?></td></tr>
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
  <tr><td colspan="6" class="r"><b>Total Skor KPI</b></td><td class="r"><b><?= $n($skorKpi) ?></b></td></tr>
</table>

<table>
  <tr><td class="sec" colspan="2">Aspek Kompetensi — Bobot <?= (int)($bobotKomp*100) ?>% (skala 1–5)</td></tr>
  <?php foreach ($comps as $c): ?>
  <tr>
    <td><b><?= esc($c['nama_aspek']) ?></b><?php if ($c['deskripsi']): ?><br><span style="color:#555;font-size:10px"><?= esc($c['deskripsi']) ?></span><?php endif; ?></td>
    <td class="c" style="width:60px"><?= $c['nilai'] !== null ? (int)$c['nilai'] : '-' ?></td>
  </tr>
  <?php endforeach; ?>
  <tr><td class="r"><b>Skor Kompetensi (rata-rata × 20)</b></td><td class="c"><b><?= $n($skorKomp) ?></b></td></tr>
</table>

<table>
  <tr><td class="sec" colspan="4">Penilaian Hasil Kerja (Final Review)</td></tr>
  <tr><th>Jenis</th><th>Bobot</th><th>Skor</th><th>Hasil</th></tr>
  <tr><td>Key Performance Indicator</td><td class="c"><?= $bobotKpi ?></td><td class="c"><?= $n($skorKpi) ?></td><td class="r"><?= $skorKpi!==null?$n($skorKpi*$bobotKpi):'-' ?></td></tr>
  <tr><td>Kompetensi</td><td class="c"><?= $bobotKomp ?></td><td class="c"><?= $n($skorKomp) ?></td><td class="r"><?= $skorKomp!==null?$n($skorKomp*$bobotKomp):'-' ?></td></tr>
  <tr><td colspan="3" class="r"><b>NILAI AKHIR</b></td><td class="r"><b><?= $n($nilai) ?></b></td></tr>
</table>

<table>
  <tr><td class="sec" colspan="3">Pendapat Karyawan</td></tr>
  <tr><td colspan="3" style="height:40px"><?= nl2br(esc($form['pendapat_karyawan'] ?? '')) ?></td></tr>
</table>

<table class="ttd">
  <tr><th style="width:33%">Penilai / Atasan Langsung</th><th style="width:33%">Kepala Departemen</th><th>HR / Manajemen</th></tr>
  <tr class="ttd"><td></td><td></td><td></td></tr>
  <tr><td class="c">(______________)</td><td class="c">(______________)</td><td class="c">(______________)</td></tr>
</table>

<p style="font-size:10px;color:#555">Skala: 5=Excellent, 4=Good, 3=Standard, 2=Need Improvement, 1=Unacceptable.</p>

<div class="noprint" style="text-align:center;margin-top:12px">
  <button onclick="window.print()">Cetak</button>
</div>
</body>
</html>
