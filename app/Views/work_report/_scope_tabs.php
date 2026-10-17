<?php
/**
 * Tab Aktif | Arsip | Dihapus untuk daftar Progress Report.
 * Param: $scope (string), $scopeCounts (array), $tabBase (url), $tabQuery (array, opsional —
 * query string lain yang dipertahankan, mis. filter divisi/dept di admin view),
 * $tabKeys (array, opsional — subset tab yang ditampilkan, mis. GM tanpa 'deleted'),
 * $tabAlerts (array, opsional — key tab => jumlah pesan belum dibaca, tampil badge merah).
 */
$tabs = [
    'active'   => ['label' => 'Aktif',   'icon' => 'bi-list-task'],
    'archived' => ['label' => 'Arsip',   'icon' => 'bi-archive'],
    'deleted'  => ['label' => 'Dihapus', 'icon' => 'bi-trash3'],
];
if (! empty($tabKeys)) {
    $tabs = array_intersect_key($tabs, array_flip($tabKeys));
}
$tabQuery  = $tabQuery ?? [];
$tabAlerts = $tabAlerts ?? [];
?>
<nav class="wr-tabs" aria-label="Cakupan daftar">
<?php foreach ($tabs as $key => $t):
    $q   = $key === 'active' ? $tabQuery : $tabQuery + ['tab' => $key];
    $url = $tabBase . ($q ? '?' . http_build_query($q) : '');
    $on  = $scope === $key;
?>
    <a class="wr-tab<?= $on ? ' is-on' : '' ?>" href="<?= $url ?>"<?= $on ? ' aria-current="page"' : '' ?>>
        <i class="bi <?= $t['icon'] ?>"></i><?= $t['label'] ?>
        <span class="wr-tab-n"><?= $scopeCounts[$key] ?? 0 ?></span>
        <?php if (! empty($tabAlerts[$key])): ?>
        <span class="wr-tab-alert" title="Pesan belum dibaca"><?= $tabAlerts[$key] ?></span>
        <?php endif; ?>
    </a>
<?php endforeach; ?>
</nav>
