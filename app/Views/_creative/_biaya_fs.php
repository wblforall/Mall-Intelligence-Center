<?php
/* Biaya Creative yang berasal dari FlowStore (BTB tertaut ke item ini). Baca-saja: dicatat di tab FlowStore Budget Departemen CCD.
   Variabel: $iid, $rows, $total, $dobel, $isS, $totalSemua, $fsBuka, $rp, $fTgl. */
if (empty($rows)) return;
$bulanIdn = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
$labelBln = fn (string $ym) => $bulanIdn[(int) substr($ym, 5, 2)] . ' ' . substr($ym, 0, 4);
?>
<section class="cr-fs" aria-label="Biaya dari FlowStore">
    <div class="cr-fs-h">
        <span class="cr-fs-t"><i class="bi bi-box-seam"></i>Biaya dari FlowStore</span>
        <span class="cr-chip cr-chip-blue" title="Angka dibaca dari BTB di FlowStore yang sudah dicatat ke Budget Departemen CCD"><i class="bi bi-box-seam"></i>FlowStore</span>
        <span class="cr-fs-sum"><b><?= count($rows) ?></b> BTB · <b><?= $rp($total) ?></b></span>
    </div>
    <?php if (!empty($dobel)): ?>
    <div class="cr-fs-warn" role="note"><i class="bi bi-exclamation-triangle-fill"></i>
        <span>Kemungkinan dobel dengan realisasi manual di <?= esc(implode(', ', array_map($labelBln, $dobel))) ?>. Periksa; angka tidak diubah otomatis.</span></div>
    <?php endif; ?>
    <div class="cr-tbl-wrap">
    <table class="cr-tbl cr-fs-tbl">
        <thead><tr><th>Diterima</th><th>BTB / PO</th><th>Supplier</th><th>Barang</th><th class="r">Nilai</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
        <tr>
            <td class="nw" data-l="Diterima"><?= $fTgl($r['tanggal_diterima'] ?: $r['tgl_beban']) ?>
                <?php if (substr((string) $r['tgl_beban'], 0, 7) !== substr((string) $r['tanggal_diterima'], 0, 7)): ?><span class="cr-fs-sub" title="Dibebankan ke bulan lain di Budget Departemen">beban <?= esc($labelBln(substr((string) $r['tgl_beban'], 0, 7))) ?></span><?php endif; ?></td>
            <td class="nw" data-l="BTB / PO"><span class="cr-fs-no"><?= esc($r['no_btb']) ?></span><span class="cr-fs-sub"><?= esc($r['no_po']) ?></span></td>
            <td data-l="Supplier"><?= esc($r['nama_supplier'] ?: '—') ?></td>
            <td class="cr-fs-brg" data-l="Barang"><span title="<?= esc($r['nama_barang'], 'attr') ?>"><?= esc($r['nama_barang']) ?></span>
                <?php if ($r['pos_nama']): ?><span class="cr-fs-sub">pos <?= esc($r['pos_nama']) ?></span><?php endif; ?>
                <?php if ($r['status'] === 'perlu_ditinjau'): ?><span class="cr-chip cr-chip-warn" title="<?= esc((string) $r['catatan_tinjau'], 'attr') ?>"><i class="bi bi-flag"></i>perlu ditinjau</span><?php endif; ?></td>
            <td class="r nw cr-c-ok fw-bold" data-l="Nilai"><?= $rp($r['nilai']) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot><tr><td colspan="4">Total FlowStore</td><td class="r nw cr-c-ok"><?= $rp($total) ?></td></tr></tfoot>
    </table>
    </div>
    <?php if (!$isS && $totalSemua !== $total): ?>
    <p class="cr-fs-note"><i class="bi bi-info-circle"></i>Total item <?= $rp($totalSemua) ?> = FlowStore <?= $rp($total) ?> + realisasi manual yang dicatat di tab Creative event.</p>
    <?php elseif ($isS && $totalSemua !== $total): ?>
    <p class="cr-fs-note"><i class="bi bi-info-circle"></i>Realisasi item <?= $rp($totalSemua) ?> = manual <?= $rp($totalSemua - $total) ?> + FlowStore <?= $rp($total) ?>.</p>
    <?php endif; ?>
</section>
