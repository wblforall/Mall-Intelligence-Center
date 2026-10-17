<?php
/**
 * Kepala + sub-navigasi bersama modul Media Promo.
 * Variabel: $mp (active, canEdit, canApprove, pending), $mpTitle, $mpIcon, $mpSub, $mpActions (HTML, opsional)
 */
$mpTabs = [
    'index'   => ['creative/media-promo',         'bi-grid-3x3-gap', 'Ketersediaan'],
    'my'      => ['creative/media-promo/my',      'bi-list-check',   'Request Saya'],
];
if (! empty($mp['canApprove'])) $mpTabs['pending'] = ['creative/media-promo/pending', 'bi-hourglass-split', 'Pending Approval'];
$mpTabs['gantt']   = ['creative/media-promo/gantt',   'bi-bar-chart-steps', 'Gantt'];
$mpTabs['summary'] = ['creative/media-promo/summary', 'bi-graph-up-arrow',  'Summary'];
if (! empty($mp['canEdit'])) $mpTabs['master'] = ['creative/media-promo/master', 'bi-pin-map', 'Master Titik'];
?>
<?php if ($mp['active'] !== 'index'): ?>
<a href="<?= base_url('creative/media-promo') ?>" class="mp-back"><i class="bi bi-arrow-left"></i>Kembali ke Ketersediaan Media</a>
<?php endif; ?>
<div class="mp-head">
    <div class="mp-head-ico"><i class="bi <?= esc($mpIcon) ?>"></i></div>
    <div class="min-w-0">
        <h1 class="mp-title"><?= esc($mpTitle) ?></h1>
        <div class="mp-subtitle"><?= esc($mpSub ?? '') ?></div>
    </div>
    <?php if (! empty($mpActions)): ?><div class="mp-head-act"><?= $mpActions ?></div><?php endif; ?>
</div>
<nav class="mp-tabs" aria-label="Halaman Media Promo">
    <?php foreach ($mpTabs as $k => [$href, $ico, $lbl]): ?>
    <a href="<?= base_url($href) ?>" class="mp-tab<?= $mp['active'] === $k ? ' is-on' : '' ?>"<?= $mp['active'] === $k ? ' aria-current="page"' : '' ?>>
        <i class="bi <?= $ico ?>"></i><?= $lbl ?>
        <?php if ($k === 'pending' && ! empty($mp['pending'])): ?><span class="mp-tab-n"><?= (int) $mp['pending'] ?></span><?php endif; ?>
    </a>
    <?php endforeach; ?>
</nav>
