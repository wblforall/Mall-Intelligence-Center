<?php
/* Catatan tertulis petugas pada kunjungan Pest Control dalam periode cetak.
   Param: $catatanKunjungan (tanggal, mall, vendor, petugas, catatan), $periodeLabel.
   Tidak mengarang: bila tak ada catatan, ditulis apa adanya. */
$catatanKunjungan = $catatanKunjungan ?? [];
$mallNama = \App\Models\PestVisitModel::MALLS;
?>
<div class="sec-title">Catatan Kunjungan &amp; Tindak Lanjut<span class="sec-sub">catatan tertulis petugas pada kunjungan, <?= esc($periodeLabel ?? '') ?></span></div>
<?php if (! $catatanKunjungan): ?>
<div class="catatan">Catatan kunjungan belum diisi — tidak ada kunjungan pada periode ini yang disertai catatan, kendala, atau tindak lanjut tertulis dari petugas.</div>
<?php else: ?>
<table class="main-table tbl-catatan">
<thead><tr><th style="width:78px">Tanggal</th><th style="width:74px">Mall</th><th style="width:20%">Vendor / Petugas</th><th>Catatan</th></tr></thead>
<tbody>
<?php foreach ($catatanKunjungan as $c): ?>
<tr>
    <td class="nowrap"><?= date('d/m/Y', strtotime($c['tanggal'])) ?></td>
    <td><?= esc($mallNama[$c['mall']] ?? $c['mall']) ?></td>
    <td><?= esc(trim($c['vendor'] . ($c['petugas'] !== '' && $c['petugas'] !== null ? ' / ' . $c['petugas'] : ''), ' /')) ?: '—' ?></td>
    <td style="white-space:pre-line;overflow-wrap:anywhere"><?= esc(trim($c['catatan'])) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>
