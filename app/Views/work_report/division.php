<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/work-report.css') ?>?v=<?= @filemtime(FCPATH . 'css/work-report.css') ?: 1 ?>">
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php
$statusLabel = [
    'on_track'  => ['label' => 'On Track',  'badge' => 'bg-success',         'icon' => 'check-circle'],
    'at_risk'   => ['label' => 'At Risk',   'badge' => 'bg-warning text-dark','icon' => 'exclamation-triangle'],
    'delayed'   => ['label' => 'Delayed',   'badge' => 'bg-danger',           'icon' => 'x-circle'],
    'done'      => ['label' => 'Selesai',   'badge' => 'bg-primary',          'icon' => 'check-all'],
    'cancelled' => ['label' => 'Dibatalkan','badge' => 'bg-secondary',        'icon' => 'dash-circle'],
];
?>

<div class="page-wr" id="wrRoot" data-mode="division">

<div class="wr-head">
    <div class="wr-head-ico"><i class="bi bi-kanban"></i></div>
    <div class="min-w-0">
        <nav class="wr-crumb" aria-label="Navigasi"><a href="<?= base_url('work-report') ?>">Progress Report</a><i class="bi bi-chevron-right"></i><span>Divisi</span></nav>
        <h1 class="wr-title">Progress Report Divisi</h1>
        <div class="wr-subtitle"><?= esc($divisi['nama'] ?? '') ?> &middot; <?= (int) (($scopeCounts ?? [])[$scope ?? 'active'] ?? count($items)) ?> program kerja</div>
    </div>
    <div class="wr-head-act">
        <a href="<?= base_url('work-report/dashboard') ?>" class="wr-btn"><i class="bi bi-speedometer2"></i><span class="d-none d-sm-inline">Dashboard</span></a>
        <button class="wr-btn wr-btn-primary" data-bs-toggle="modal" data-bs-target="#modalAdd">
            <i class="bi bi-plus-lg"></i>Tambah Program Kerja
        </button>
    </div>
</div>

<div class="wr-scopebar">
<?= view('work_report/_scope_tabs', [
    'scope'       => $scope ?? 'active',
    'scopeCounts' => $scopeCounts ?? [],
    'tabBase'     => base_url('work-report/division'),
    'tabAlerts'   => $tabAlerts ?? [],
]) ?>
</div>

<?php if (($scope ?? 'active') !== 'active'): ?>
<?= view('work_report/_scope_list', [
    'items'        => $items,
    'scope'        => $scope,
    'statusLabel'  => $statusLabel,
    'showOrg'      => true,
    'canRestore'   => false,
    'canUnarchive' => true,
    'detailBase'   => base_url('work-report/division'),
    'unread'       => $scopeUnread ?? [],
]) ?>
<?php else:
    $pld = \App\Models\WorkInitiativeModel::DIVISION_LEVEL_LABEL; // grouping dilakukan di controller
    $groups = [];
    foreach ($grouped as $deptName => $deptItems) {
        $groups[] = ['title' => $deptName, 'icon' => $deptName === $pld ? 'bi-layers' : 'bi-building', 'items' => $deptItems];
    }
?>
<?= view('work_report/_wr_board', [
    'mode' => 'division', 'groups' => $groups, 'statusLabel' => $statusLabel,
    'gmUnread' => $gmUnread ?? [], 'deptReplyUnread' => $deptReplyUnread ?? [],
    'canFlag' => (bool) ($canFlag ?? true), 'detailBase' => base_url('work-report/division'),
]) ?>
<?php endif; // scope active ?>

</div>

<!-- Modal Tambah -->
<div class="modal fade" id="modalAdd" tabindex="-1">
<div class="modal-dialog modal-lg">
<div class="modal-content">
<div class="modal-header py-2"><h6 class="modal-title fw-semibold"><i class="bi bi-plus-circle me-2"></i>Tambah Program Kerja</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
<form method="POST" action="<?= base_url('work-report/division/store') ?>">
    <?= csrf_field() ?>
    <div class="mb-2">
        <label class="form-label small fw-semibold">Judul <span class="text-danger">*</span></label>
        <input type="text" name="judul" class="form-control form-control-sm" required>
    </div>
    <div class="mb-2">
        <label class="form-label small fw-semibold">Deskripsi</label>
        <textarea name="deskripsi" class="form-control form-control-sm" rows="2"></textarea>
    </div>
    <div class="mb-2">
        <label class="form-label small fw-semibold">Assign ke Departemen</label>
        <select name="assigned_to_dept_id" class="form-select form-select-sm">
            <option value="">— Program Kerja Deputy sendiri (tidak di-assign) —</option>
            <?php foreach ($depts as $d): ?>
            <option value="<?= $d['id'] ?>"><?= esc($d['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <div class="form-text" style="font-size:.68rem">Jika di-assign, Dept Head dept tersebut bisa melihat dan mengupdate.</div>
    </div>
    <div class="row g-2">
        <div class="col-12 col-sm-4">
            <label class="form-label small fw-semibold">Target Mulai</label>
            <input type="date" name="target_mulai" class="form-control form-control-sm">
        </div>
        <div class="col-12 col-sm-4">
            <label class="form-label small fw-semibold">Target Selesai</label>
            <input type="date" name="target_selesai" class="form-control form-control-sm">
        </div>
    </div>
    <div class="d-flex justify-content-end gap-2 mt-3">
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
    </div>
</form>
</div>
</div>
</div>
</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="<?= base_url('js/work-report.js') ?>?v=<?= @filemtime(FCPATH . 'js/work-report.js') ?: 1 ?>"></script>
<?= $this->endSection() ?>
