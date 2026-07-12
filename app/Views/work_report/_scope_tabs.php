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
<ul class="nav nav-pills gap-1 mb-3" style="font-size:.78rem">
<?php foreach ($tabs as $key => $t):
    $q   = $key === 'active' ? $tabQuery : $tabQuery + ['tab' => $key];
    $url = $tabBase . ($q ? '?' . http_build_query($q) : '');
    $on  = $scope === $key;
?>
    <li class="nav-item">
        <a class="nav-link py-1 px-2 <?= $on ? 'active' : '' ?>" href="<?= $url ?>">
            <i class="bi <?= $t['icon'] ?> me-1"></i><?= $t['label'] ?>
            <span class="badge rounded-pill <?= $on ? 'bg-light text-dark' : 'bg-secondary-subtle text-secondary' ?> ms-1"><?= $scopeCounts[$key] ?? 0 ?></span>
            <?php if (! empty($tabAlerts[$key])): ?>
            <span class="badge rounded-pill bg-danger ms-1" style="font-size:.62rem" title="Pesan belum dibaca"><?= $tabAlerts[$key] ?></span>
            <?php endif; ?>
        </a>
    </li>
<?php endforeach; ?>
</ul>
