<?php
/* Blok tanda tangan Laporan Bulanan. Param: $signatories (ReportSignatories::resolve).
   Senior Manager divisi (bila ada) berdampingan dgn Deputy dalam satu kolom "Diperiksa oleh". */
$sg = $signatories ?? [];
$signSlot = function (?array $s) {
    if ($s) {
        return '<div style="height:42px"></div><span class="sign-role" style="text-decoration:underline">' . esc($s['nama']) . '</span>'
             . '<div style="font-size:8.5px;color:#64748b;margin-top:2px">' . esc($s['jabatan']) . '</div>';
    }
    return '<div style="height:42px"></div><span class="sign-role">( ……………………………… )</span>';
};
?>
<div class="sign-row">
    <div class="sign-box">Disusun oleh<?= $signSlot($sg['disusun'] ?? null) ?></div>
    <?php if (! empty($sg['diperiksa_sm'])): ?>
    <div class="sign-box" style="flex:1.6">
        Diperiksa oleh
        <div style="display:flex;gap:14px">
            <div style="flex:1"><?= $signSlot($sg['diperiksa_sm']) ?></div>
            <div style="flex:1"><?= $signSlot($sg['diperiksa'] ?? null) ?></div>
        </div>
    </div>
    <?php else: ?>
    <div class="sign-box">Diperiksa oleh<?= $signSlot($sg['diperiksa'] ?? null) ?></div>
    <?php endif; ?>
    <div class="sign-box">Mengetahui<?= $signSlot($sg['mengetahui'] ?? null) ?></div>
</div>
