<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/work-report.css') ?>?v=<?= @filemtime(FCPATH . 'css/work-report.css') ?: 1 ?>">
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php
$statusLabel = [
    'on_track'  => ['label' => 'On Track',  'badge' => 'bg-success'],
    'at_risk'   => ['label' => 'At Risk',   'badge' => 'bg-warning text-dark'],
    'delayed'   => ['label' => 'Delayed',   'badge' => 'bg-danger'],
    'done'      => ['label' => 'Selesai',   'badge' => 'bg-primary'],
    'cancelled' => ['label' => 'Dibatalkan','badge' => 'bg-secondary'],
];
$scope     = $scope ?? 'active';
$scopeNoun = ['active' => 'aktif', 'archived' => 'diarsipkan', 'deleted' => 'dihapus'];
$pld       = \App\Models\WorkInitiativeModel::DIVISION_LEVEL_LABEL;
$groups    = [];
foreach (($grouped ?? []) as $divName => $deptGroups) {
    foreach ($deptGroups as $deptName => $its) {
        $groups[] = ['title' => $divName . ' › ' . $deptName, 'icon' => $deptName === $pld ? 'bi-layers' : 'bi-building', 'items' => $its];
    }
}
?>
<div class="page-wr" id="wrRoot" data-mode="admin">

<div class="wr-head">
    <div class="wr-head-ico"><i class="bi bi-kanban"></i></div>
    <div class="min-w-0">
        <nav class="wr-crumb" aria-label="Navigasi"><a href="<?= base_url('work-report') ?>">Progress Report</a><i class="bi bi-chevron-right"></i><span>Admin</span></nav>
        <h1 class="wr-title">Progress Report</h1>
        <div class="wr-subtitle"><?= (int) $total ?> program kerja <?= $scopeNoun[$scope] ?? 'aktif' ?> &middot; pantau semua divisi dan departemen</div>
    </div>
    <div class="wr-head-act">
        <a href="<?= base_url('work-report/dashboard') ?>" class="wr-btn"><i class="bi bi-speedometer2"></i>Dashboard</a>
    </div>
</div>

<div class="wr-scopebar">
<?= view('work_report/_scope_tabs', [
    'scope'       => $scope,
    'scopeCounts' => $scopeCounts ?? [],
    'tabBase'     => base_url('work-report/admin'),
    'tabQuery'    => array_filter(['divisi_id' => $filterDiv, 'dept_id' => $filterDept]),
]) ?>
<form method="GET" action="<?= base_url('work-report/admin') ?>" class="wr-scopefilter" id="wrOrgForm">
    <?php if ($scope !== 'active'): ?><input type="hidden" name="tab" value="<?= esc($scope) ?>"><?php endif; ?>
    <select name="divisi_id" class="wr-select" aria-label="Divisi" onchange="this.form.submit()">
        <option value="">Semua Divisi</option>
        <?php foreach ($divisis as $dv): ?><option value="<?= $dv['id'] ?>" <?= (int) $filterDiv === (int) $dv['id'] ? 'selected' : '' ?>><?= esc($dv['nama']) ?></option><?php endforeach; ?>
    </select>
    <select name="dept_id" class="wr-select" aria-label="Departemen" onchange="this.form.submit()">
        <option value="">Semua Dept.</option>
        <?php foreach ($depts as $d): ?><option value="<?= $d['id'] ?>" <?= (int) $filterDept === (int) $d['id'] ? 'selected' : '' ?>><?= esc($d['name']) ?></option><?php endforeach; ?>
    </select>
    <noscript><button type="submit" class="wr-btn wr-btn-sm">Filter</button></noscript>
    <?php if ($filterDiv || $filterDept): ?>
    <a href="<?= base_url('work-report/admin' . ($scope !== 'active' ? '?tab=' . $scope : '')) ?>" class="wr-btn wr-btn-sm"><i class="bi bi-x-lg"></i>Reset</a>
    <?php endif; ?>
</form>
</div>

<?php if ($scope !== 'active'): ?>
<?= view('work_report/_scope_list', [
    'items'        => $items,
    'scope'        => $scope,
    'statusLabel'  => $statusLabel,
    'showOrg'      => true,
    'canRestore'   => $scope === 'deleted',
    'canUnarchive' => true,
    'detailBase'   => base_url('work-report'),
]) ?>
<?php else: ?>
<?= view('work_report/_wr_board', [
    'mode' => 'admin', 'groups' => $groups, 'statusLabel' => $statusLabel,
    'detailBase' => base_url('work-report'), 'canFlag' => false,
]) ?>
<?php endif; ?>

</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="<?= base_url('js/work-report.js') ?>?v=<?= @filemtime(FCPATH . 'js/work-report.js') ?: 1 ?>"></script>
<?= $this->endSection() ?>
