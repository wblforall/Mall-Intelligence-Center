<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$statusLabel = [
    'on_track'  => ['label' => 'On Track',   'badge' => 'bg-success'],
    'at_risk'   => ['label' => 'At Risk',     'badge' => 'bg-warning text-dark'],
    'delayed'   => ['label' => 'Delayed',     'badge' => 'bg-danger'],
    'done'      => ['label' => 'Selesai',     'badge' => 'bg-primary'],
    'cancelled' => ['label' => 'Dibatalkan',  'badge' => 'bg-secondary'],
];
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-kanban me-2"></i>Progress Report</h4>
        <small class="text-muted"><?= esc($deptInfo['name'] ?? '') ?></small>
    </div>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAdd">
        <i class="bi bi-plus-lg me-1"></i>Tambah Program Kerja
    </button>
</div>


<?php if (empty($items)): ?>
<div class="text-center text-muted py-5">
    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
    Belum ada program kerja. Klik <strong>Tambah Program Kerja</strong> untuk memulai.
</div>
<?php else: ?>
<div class="row g-3">
<?php foreach ($items as $item):
    $st   = $item['latest_status'] ?? null;
    $info = $st ? ($statusLabel[$st] ?? $statusLabel['on_track']) : null;
    $isAssigned = ! empty($item['assigned_to_dept_id']);
    $overdue = ! empty($item['target_selesai']) && $item['target_selesai'] < date('Y-m-d') && $st !== 'done' && $st !== 'cancelled';
?>
<div class="col-12" id="initiative-<?= $item['id'] ?>">
<div class="card <?= $overdue ? 'border-danger' : '' ?>">
<div class="card-body pb-2">
    <div class="d-flex align-items-start justify-content-between gap-2 mb-1">
        <div class="flex-grow-1">
            <span class="fw-semibold"><?= esc($item['judul']) ?></span>
            <?php if ($isAssigned): ?>
            <span class="badge bg-info-subtle text-info ms-1" style="font-size:.65rem"><i class="bi bi-person-badge me-1"></i>Dari Deputy</span>
            <?php endif; ?>
            <?php if ($overdue): ?>
            <span class="badge bg-danger ms-1" style="font-size:.65rem"><i class="bi bi-exclamation-triangle me-1"></i>Terlambat</span>
            <?php endif; ?>
            <?php if (! empty($commentUnread[$item['id']])): ?>
            <span class="badge rounded-pill bg-danger ms-1" style="font-size:.65rem;min-width:1.4em"><?= $commentUnread[$item['id']] ?></span>
            <?php endif; ?>
        </div>
        <div class="d-flex gap-1 flex-shrink-0 flex-wrap">
            <?php if ($info): ?>
            <span class="badge <?= $info['badge'] ?>" style="font-size:.68rem"><?= $info['label'] ?></span>
            <?php endif; ?>
            <?php if (! $isAssigned): ?>
            <button class="btn btn-outline-secondary btn-xs py-0 px-1" style="font-size:.7rem"
                data-bs-toggle="modal" data-bs-target="#modalEdit<?= $item['id'] ?>">
                <i class="bi bi-pencil"></i>
            </button>
            <?php endif; ?>
            <a href="<?= base_url('work-report/' . $item['id'] . '/detail') ?>" class="btn btn-outline-info btn-xs py-0 px-1" style="font-size:.7rem">
                <i class="bi bi-clock-history"></i>
            </a>
        </div>
    </div>

    <?php if (! empty($item['deskripsi'])): ?>
    <p class="small text-muted mb-1"><?= nl2br(esc($item['deskripsi'])) ?></p>
    <?php endif; ?>

    <div class="d-flex flex-wrap gap-2 mb-2" style="font-size:.72rem;color:var(--bs-secondary-color)">
        <?php if (! empty($item['pic_name'])): ?>
        <span><i class="bi bi-person me-1"></i><?= esc($item['pic_name']) ?></span>
        <?php endif; ?>
        <?php if (! empty($item['target_mulai'])): ?>
        <span><i class="bi bi-calendar-range me-1"></i><?= date('d M Y', strtotime($item['target_mulai'])) ?>
            <?php if (! empty($item['target_selesai'])): ?> — <?= date('d M Y', strtotime($item['target_selesai'])) ?><?php endif; ?>
        </span>
        <?php endif; ?>
    </div>

    <?php $itemHistory = $histories[$item['id']] ?? []; ?>
    <?php if (! empty($itemHistory)): ?>
    <div class="mb-2">
        <?php $first = $itemHistory[0]; $rest = array_slice($itemHistory, 1); ?>
        <!-- Update terbaru selalu tampil -->
        <div class="p-2 rounded" style="background:var(--bs-secondary-bg);font-size:.75rem">
            <div class="d-flex align-items-center gap-2 mb-1">
                <?php $si = $statusLabel[$first['status']] ?? $statusLabel['on_track']; ?>
                <span class="badge <?= $si['badge'] ?>" style="font-size:.6rem"><?= $si['label'] ?></span>
                <?php if ($first['progress_pct'] !== null): ?>
                <span class="text-muted"><?= $first['progress_pct'] ?>%</span>
                <?php endif; ?>
                <span class="text-muted ms-auto"><?= date('d M Y', strtotime($first['created_at'])) ?></span>
            </div>
            <?php if (! empty($first['catatan'])): ?>
            <div><?= nl2br(esc($first['catatan'])) ?></div>
            <?php endif; ?>
            <?php if (! empty($first['hambatan'])): ?>
            <div class="mt-1 border-start border-warning border-2 ps-2 text-warning-emphasis">
                <i class="bi bi-cone-striped me-1"></i><?= nl2br(esc($first['hambatan'])) ?>
            </div>
            <?php endif; ?>
        </div>
        <!-- Update sebelumnya — collapsible -->
        <?php if (! empty($rest)): ?>
        <div class="collapse" id="hist<?= $item['id'] ?>">
        <?php foreach ($rest as $h):
            $hi = $statusLabel[$h['status']] ?? $statusLabel['on_track']; ?>
        <div class="p-2 rounded mt-1" style="background:var(--bs-secondary-bg);font-size:.75rem;opacity:.8">
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge <?= $hi['badge'] ?>" style="font-size:.6rem"><?= $hi['label'] ?></span>
                <?php if ($h['progress_pct'] !== null): ?>
                <span class="text-muted"><?= $h['progress_pct'] ?>%</span>
                <?php endif; ?>
                <span class="text-muted ms-auto"><?= date('d M Y', strtotime($h['created_at'])) ?></span>
            </div>
            <?php if (! empty($h['catatan'])): ?>
            <div><?= nl2br(esc($h['catatan'])) ?></div>
            <?php endif; ?>
            <?php if (! empty($h['hambatan'])): ?>
            <div class="mt-1 border-start border-warning border-2 ps-2 text-warning-emphasis">
                <i class="bi bi-cone-striped me-1"></i><?= nl2br(esc($h['hambatan'])) ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        </div>
        <button class="btn btn-link btn-sm p-0 mt-1" style="font-size:.7rem"
            data-bs-toggle="collapse" data-bs-target="#hist<?= $item['id'] ?>">
            <i class="bi bi-clock-history me-1"></i><?= count($rest) ?> update sebelumnya
        </button>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Update Status -->
    <div class="border-top pt-2 mt-1">
        <button class="btn btn-outline-primary btn-sm" style="font-size:.72rem"
            data-bs-toggle="collapse" data-bs-target="#updateForm<?= $item['id'] ?>">
            <i class="bi bi-pencil-square me-1"></i>Update Progress
        </button>
        <div class="collapse mt-2" id="updateForm<?= $item['id'] ?>">
            <form method="POST" action="<?= base_url('work-report/' . $item['id'] . '/update') ?>">
                <?= csrf_field() ?>
                <div class="row g-2">
                    <div class="col-12 col-sm-4">
                        <select name="status" class="form-select form-select-sm" required>
                            <?php foreach ($statusLabel as $k => $v): ?>
                            <option value="<?= $k ?>" <?= ($item['latest_status'] ?? '') === $k ? 'selected' : '' ?>><?= $v['label'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-sm-2">
                        <div class="input-group input-group-sm">
                            <input type="number" name="progress_pct" class="form-control form-control-sm" placeholder="0"
                                min="0" max="100" value="<?= $item['latest_progress'] ?? '' ?>">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    <div class="col-12">
                        <textarea name="catatan" class="form-control form-control-sm" placeholder="Perkembangan minggu ini" rows="3"></textarea>
                    </div>
                    <div class="col-12">
                        <textarea name="hambatan" class="form-control form-control-sm" placeholder="Hambatan (kosongkan jika tidak ada)" rows="3"></textarea>
                    </div>
                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
</div>
</div>

<!-- Modal Edit -->
<?php if (! $isAssigned): ?>
<div class="modal fade" id="modalEdit<?= $item['id'] ?>" tabindex="-1">
<div class="modal-dialog modal-lg">
<div class="modal-content">
<div class="modal-header py-2"><h6 class="modal-title fw-semibold">Edit Program Kerja</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
<form method="POST" action="<?= base_url('work-report/' . $item['id'] . '/edit') ?>">
    <?= csrf_field() ?>
    <div class="mb-2">
        <label class="form-label small fw-semibold">Judul <span class="text-danger">*</span></label>
        <input type="text" name="judul" class="form-control form-control-sm" value="<?= esc($item['judul']) ?>" required>
    </div>
    <div class="mb-2">
        <label class="form-label small fw-semibold">Deskripsi</label>
        <textarea name="deskripsi" class="form-control form-control-sm" rows="2"><?= esc($item['deskripsi'] ?? '') ?></textarea>
    </div>
    <div class="row g-2">
        <div class="col-sm-4">
            <label class="form-label small fw-semibold">PIC</label>
            <select name="pic_employee_id" class="form-select form-select-sm">
                <option value="">— Pilih PIC —</option>
                <?php foreach ($employees as $e): ?>
                <option value="<?= $e['id'] ?>" <?= (int)($item['pic_employee_id'] ?? 0) === (int)$e['id'] ? 'selected' : '' ?>><?= esc($e['nama']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-sm-4">
            <label class="form-label small fw-semibold">Target Mulai</label>
            <input type="date" name="target_mulai" class="form-control form-control-sm" value="<?= $item['target_mulai'] ?? '' ?>">
        </div>
        <div class="col-sm-4">
            <label class="form-label small fw-semibold">Target Selesai</label>
            <input type="date" name="target_selesai" class="form-control form-control-sm" value="<?= $item['target_selesai'] ?? '' ?>">
        </div>
    </div>
    <div class="d-flex gap-2 mt-3 justify-content-between">
        <button type="button" class="btn btn-outline-danger btn-sm"
            onclick="if(confirm('Hapus program kerja ini?')) document.getElementById('delForm<?= $item['id'] ?>').submit()">
            <i class="bi bi-trash me-1"></i>Hapus
        </button>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
        </div>
    </div>
</form>
<form id="delForm<?= $item['id'] ?>" method="POST" action="<?= base_url('work-report/' . $item['id'] . '/delete') ?>" class="d-none">
    <?= csrf_field() ?>
</form>
</div>
</div>
</div>
</div>
<?php endif; ?>

<?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Modal Tambah -->
<div class="modal fade" id="modalAdd" tabindex="-1">
<div class="modal-dialog modal-lg">
<div class="modal-content">
<div class="modal-header py-2"><h6 class="modal-title fw-semibold"><i class="bi bi-plus-circle me-2"></i>Tambah Program Kerja</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
<form method="POST" action="<?= base_url('work-report/store') ?>">
    <?= csrf_field() ?>
    <div class="mb-2">
        <label class="form-label small fw-semibold">Judul <span class="text-danger">*</span></label>
        <input type="text" name="judul" class="form-control form-control-sm" required placeholder="Nama program kerja">
    </div>
    <div class="mb-2">
        <label class="form-label small fw-semibold">Deskripsi</label>
        <textarea name="deskripsi" class="form-control form-control-sm" rows="2" placeholder="Detail singkat (opsional)"></textarea>
    </div>
    <div class="row g-2">
        <div class="col-sm-4">
            <label class="form-label small fw-semibold">PIC</label>
            <select name="pic_employee_id" class="form-select form-select-sm">
                <option value="">— Pilih PIC —</option>
                <?php foreach ($employees as $e): ?>
                <option value="<?= $e['id'] ?>"><?= esc($e['nama']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-sm-4">
            <label class="form-label small fw-semibold">Target Mulai</label>
            <input type="date" name="target_mulai" class="form-control form-control-sm">
        </div>
        <div class="col-sm-4">
            <label class="form-label small fw-semibold">Target Selesai</label>
            <input type="date" name="target_selesai" class="form-control form-control-sm">
        </div>
    </div>
    <div class="d-flex justify-content-end gap-2 mt-3">
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check-lg me-1"></i>Simpan</button>
    </div>
</form>
</div>
</div>
</div>
</div>

<?= $this->endSection() ?>
