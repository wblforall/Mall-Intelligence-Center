<?php
/**
 * Tab Aktif | Arsip | Dihapus untuk daftar Progress Report.
 * Param: $scope (string), $scopeCounts (array), $tabBase (url), $tabQuery (array, opsional —
 * query string lain yang dipertahankan, mis. filter divisi/dept di admin view).
 */
$tabs = [
    'active'   => ['label' => 'Aktif',   'icon' => 'bi-list-task'],
    'archived' => ['label' => 'Arsip',   'icon' => 'bi-archive'],
    'deleted'  => ['label' => 'Dihapus', 'icon' => 'bi-trash3'],
];
$tabQuery = $tabQuery ?? [];
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
        </a>
    </li>
<?php endforeach; ?>
</ul>
