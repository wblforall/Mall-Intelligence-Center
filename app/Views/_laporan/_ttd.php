<?php
/* Blok tanda tangan Laporan Bulanan. Param: $signatories (ReportSignatories::resolve).
   Senior Manager divisi (bila ada) berdampingan dgn Deputy dalam satu kolom "Diperiksa oleh".
   Gaya: ruang tanda tangan, lalu nama di atas garis tipis dan jabatan di bawahnya
   (tanpa kotak) — lihat .sign-* di _laporan/_style.php. */
$sg = $signatories ?? [];
$signSlot = function (?array $s) {
    $html = '<div class="sign-space"></div>';
    if ($s) {
        return $html . '<span class="sign-role">' . esc($s['nama']) . '</span>'
             . '<div class="sign-jabatan">' . esc($s['jabatan']) . '</div>';
    }
    return $html . '<span class="sign-role">&nbsp;</span><div class="sign-jabatan">&nbsp;</div>';
};
?>
<div class="sign-row">
    <div class="sign-box"><div class="sign-label">Disusun oleh</div><?= $signSlot($sg['disusun'] ?? null) ?></div>
    <?php if (! empty($sg['diperiksa_sm'])): ?>
    <div class="sign-box" style="flex:2">
        <div class="sign-label">Diperiksa oleh</div>
        <div class="sign-pair">
            <div><?= $signSlot($sg['diperiksa_sm']) ?></div>
            <div><?= $signSlot($sg['diperiksa'] ?? null) ?></div>
        </div>
    </div>
    <?php else: ?>
    <div class="sign-box"><div class="sign-label">Diperiksa oleh</div><?= $signSlot($sg['diperiksa'] ?? null) ?></div>
    <?php endif; ?>
    <div class="sign-box"><div class="sign-label">Mengetahui</div><?= $signSlot($sg['mengetahui'] ?? null) ?></div>
</div>
