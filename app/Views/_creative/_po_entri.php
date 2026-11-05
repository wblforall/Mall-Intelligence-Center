<?php
/* Rincian PO FlowStore milik satu entri realisasi Creative. Variabel: $po (baris dari CreativeBiayaFs::poEntri), $rp. */
if (empty($po)) return;
$nomor = array_values(array_unique(array_filter(array_column($po, 'no_po'))));
?>
<details class="po-entri">
    <summary><i class="bi bi-box-seam"></i>PO <?= esc(implode(', ', $nomor) ?: '—') ?> · <?= count($po) ?> barang</summary>
    <ul>
    <?php foreach ($po as $b): ?>
        <li><span class="po-entri-brg"><?= esc($b['nama_barang']) ?></span>
            <span class="po-entri-sub">BTB <?= esc($b['no_btb']) ?> · <?= esc(rtrim(rtrim(number_format((float) $b['jumlah_diterima'], 2, ',', '.'), '0'), ',')) ?> <?= esc($b['satuan']) ?><?= $b['pos_nama'] ? ' · pos ' . esc($b['pos_nama']) : '' ?><?= $b['nama_supplier'] ? ' · ' . esc($b['nama_supplier']) : '' ?></span>
            <span class="po-entri-nilai"><?= $rp($b['nilai']) ?></span>
            <?php if ($b['status'] === 'perlu_ditinjau'): ?><span class="po-entri-tinjau" title="<?= esc((string) $b['catatan_tinjau'], 'attr') ?>">perlu ditinjau</span><?php endif; ?></li>
    <?php endforeach; ?>
    </ul>
</details>
