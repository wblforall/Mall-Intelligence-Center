<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$statusLabel = [
    'on_track'  => ['label' => 'On Track',  'badge' => 'bg-success'],
    'at_risk'   => ['label' => 'At Risk',   'badge' => 'bg-warning text-dark'],
    'delayed'   => ['label' => 'Delayed',   'badge' => 'bg-danger'],
    'done'      => ['label' => 'Selesai',   'badge' => 'bg-primary'],
    'cancelled' => ['label' => 'Dibatalkan','badge' => 'bg-secondary'],
];
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-kanban me-2"></i>Inisiatif Kerja — Admin</h4>
        <small class="text-muted"><?= $total ?> inisiatif aktif</small>
    </div>
</div>

<!-- Filter -->
<form method="GET" action="<?= base_url('work-report/admin') ?>" class="card mb-4">
<div class="card-body py-2">
<div class="row g-2 align-items-end">
    <div class="col-12 col-sm-4">
        <label class="form-label small fw-semibold mb-1">Divisi</label>
        <select name="divisi_id" class="form-select form-select-sm">
            <option value="">Semua Divisi</option>
            <?php foreach ($divisis as $dv): ?>
            <option value="<?= $dv['id'] ?>" <?= (int)$filterDiv === (int)$dv['id'] ? 'selected' : '' ?>><?= esc($dv['nama']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-12 col-sm-4">
        <label class="form-label small fw-semibold mb-1">Departemen</label>
        <select name="dept_id" class="form-select form-select-sm">
            <option value="">Semua Departemen</option>
            <?php foreach ($depts as $d): ?>
            <option value="<?= $d['id'] ?>" <?= (int)$filterDept === (int)$d['id'] ? 'selected' : '' ?>><?= esc($d['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-12 col-sm-auto">
        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
        <?php if ($filterDiv || $filterDept): ?>
        <a href="<?= base_url('work-report/admin') ?>" class="btn btn-outline-secondary btn-sm ms-1">Reset</a>
        <?php endif; ?>
    </div>
</div>
</div>
</form>

<?php if (empty($grouped)): ?>
<div class="text-center text-muted py-5">
    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
    Tidak ada inisiatif ditemukan.
</div>
<?php else: ?>

<?php foreach ($grouped as $divName => $deptGroups): ?>
<div class="mb-4">
<div class="fw-bold mb-2 px-1" style="font-size:.8rem;text-transform:uppercase;letter-spacing:.08em;color:var(--bs-primary)">
    <i class="bi bi-layers me-1"></i><?= esc($divName) ?>
</div>

<?php foreach ($deptGroups as $deptName => $items): ?>
<div class="card mb-3">
<div class="card-header py-2 d-flex align-items-center justify-content-between">
    <span class="fw-semibold small"><i class="bi bi-building me-2 text-muted"></i><?= esc($deptName) ?></span>
    <small class="text-muted"><?= count($items) ?> inisiatif</small>
</div>
<div class="table-responsive">
<table class="table table-sm align-middle mb-0" style="font-size:.78rem">
<thead>
<tr>
    <th style="width:30%">Inisiatif</th>
    <th style="width:10%">Status</th>
    <th style="width:10%">Progress</th>
    <th style="width:15%">PIC</th>
    <th style="width:10%">Target Selesai</th>
    <th style="width:15%">Update Terakhir</th>
    <th style="width:10%">Flag GM</th>
</tr>
</thead>
<tbody>
<?php foreach ($items as $item):
    $st     = $item['latest_status'] ?? null;
    $info   = $st ? ($statusLabel[$st] ?? null) : null;
    $overdue = ! empty($item['target_selesai']) && $item['target_selesai'] < date('Y-m-d') && $st !== 'done' && $st !== 'cancelled';
    $noUpdate = empty($item['latest_updated_at']);
    $staleWeeks = $item['latest_updated_at']
        ? floor((time() - strtotime($item['latest_updated_at'])) / (7 * 86400))
        : null;
?>
<tr class="<?= $overdue ? 'table-danger' : ($noUpdate ? 'table-warning' : '') ?>">
    <td>
        <div class="fw-semibold"><?= esc($item['judul']) ?></div>
        <?php if (! empty($item['latest_hambatan'])): ?>
        <div class="text-warning-emphasis" style="font-size:.7rem"><i class="bi bi-cone-striped me-1"></i><?= esc(mb_substr($item['latest_hambatan'], 0, 60)) ?></div>
        <?php endif; ?>
        <?php if (! empty($item['assigned_to_dept_id'])): ?>
        <span class="badge bg-info-subtle text-info" style="font-size:.6rem">Dari Deputy</span>
        <?php endif; ?>
    </td>
    <td>
        <?php if ($info): ?>
        <span class="badge <?= $info['badge'] ?>" style="font-size:.65rem"><?= $info['label'] ?></span>
        <?php elseif ($noUpdate): ?>
        <span class="badge bg-secondary" style="font-size:.65rem">Belum ada</span>
        <?php endif; ?>
        <?php if ($overdue): ?><br><span class="badge bg-danger" style="font-size:.6rem">Terlambat</span><?php endif; ?>
    </td>
    <td>
        <?php if ($item['latest_progress'] !== null): ?>
        <div class="progress" style="height:6px">
            <div class="progress-bar <?= (int)$item['latest_progress'] >= 100 ? 'bg-success' : ((int)$item['latest_progress'] >= 60 ? 'bg-primary' : 'bg-warning') ?>"
                style="width:<?= $item['latest_progress'] ?>%"></div>
        </div>
        <div class="text-muted" style="font-size:.65rem"><?= $item['latest_progress'] ?>%</div>
        <?php else: ?>
        <span class="text-muted">—</span>
        <?php endif; ?>
    </td>
    <td><?= esc($item['pic_name'] ?? '—') ?></td>
    <td>
        <?php if (! empty($item['target_selesai'])): ?>
        <span <?= $overdue ? 'class="text-danger fw-semibold"' : '' ?>><?= date('d M Y', strtotime($item['target_selesai'])) ?></span>
        <?php else: ?>—<?php endif; ?>
    </td>
    <td>
        <?php if (! empty($item['latest_updated_at'])): ?>
        <?= date('d M Y', strtotime($item['latest_updated_at'])) ?>
        <?php if ($staleWeeks >= 2): ?>
        <br><span class="badge bg-warning text-dark" style="font-size:.6rem"><?= $staleWeeks ?> minggu lalu</span>
        <?php endif; ?>
        <?php else: ?>
        <span class="text-danger">Belum pernah</span>
        <?php endif; ?>
    </td>
    <td class="text-center">
        <?php if ((int)($item['is_flagged'] ?? 0)): ?>
        <i class="bi bi-flag-fill text-warning"></i>
        <?php else: ?>
        <i class="bi bi-flag text-muted"></i>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
<?php endforeach; ?>
</div>
<?php endforeach; ?>

<?php endif; ?>

<?= $this->endSection() ?>
