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

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= base_url('work-report/division') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h5 class="fw-bold mb-0"><?= esc($item['judul']) ?></h5>
        <small class="text-muted"><?= esc($item['dept_name'] ?? '') ?></small>
    </div>
    <div class="ms-auto d-flex gap-2 flex-wrap">
        <?php if ($canFlag ?? true): ?>
        <form method="POST" action="<?= base_url('work-report/division/' . $item['id'] . '/flag') ?>">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-sm <?= $isFlagged ? 'btn-warning' : 'btn-outline-secondary' ?>">
                <i class="bi bi-flag<?= $isFlagged ? '-fill' : '' ?> me-1"></i><?= $isFlagged ? 'Tampil di GM' : 'Flag ke GM' ?>
            </button>
        </form>
        <?php elseif ($isFlagged): ?>
        <span class="badge bg-warning text-dark align-self-center"><i class="bi bi-flag-fill me-1"></i>Tampil di GM</span>
        <?php endif; ?>
        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalEdit">
            <i class="bi bi-pencil me-1"></i>Edit
        </button>
    </div>
</div>


<div class="row g-3">
<!-- Riwayat -->
<div class="col-12 col-lg-5">
    <div class="card mb-3">
        <div class="card-header py-2"><h6 class="mb-0 fw-semibold"><i class="bi bi-clock-history me-2"></i>Riwayat Update</h6></div>
        <div class="list-group list-group-flush" style="max-height:400px;overflow-y:auto">
        <?php if (empty($history)): ?>
            <div class="list-group-item small text-muted text-center py-3">Belum ada update.</div>
        <?php else: ?>
            <?php foreach ($history as $h):
                $info = $statusLabel[$h['status']] ?? $statusLabel['on_track'];
            ?>
            <div class="list-group-item py-2">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge <?= $info['badge'] ?>" style="font-size:.63rem"><?= $info['label'] ?></span>
                    <?php if ($h['progress_pct'] !== null): ?>
                    <span class="badge bg-body-secondary text-body" style="font-size:.63rem"><?= $h['progress_pct'] ?>%</span>
                    <?php endif; ?>
                    <small class="text-muted ms-auto"><?= date('d M Y', strtotime($h['created_at'])) ?></small>
                </div>
                <?php if (! empty($h['catatan'])): ?><div class="small"><?= nl2br(esc($h['catatan'])) ?></div><?php endif; ?>
                <?php if (! empty($h['hambatan'])): ?>
                <div class="small mt-1 p-1 rounded border-start border-warning border-2 ps-2" style="background:var(--bs-secondary-bg)">
                    <i class="bi bi-cone-striped text-warning me-1"></i><?= nl2br(esc($h['hambatan'])) ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
        </div>
    </div>
</div>

<!-- Komentar & Thread GM -->
<div class="col-12 col-lg-7">
    <!-- Thread GM ↔ Deputy (atas) -->
    <div class="card mb-3">
        <div class="card-header py-2 d-flex align-items-center justify-content-between">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-chat-dots me-2 text-warning"></i>Thread GM ↔ Deputy</h6>
            <small class="text-muted" style="font-size:.68rem">Hanya terlihat GM + Deputy</small>
        </div>
        <div class="list-group list-group-flush">
        <?php if (empty($gmThread)): ?>
            <div class="list-group-item small text-muted text-center py-2">Belum ada catatan dari GM.</div>
        <?php else: ?>
            <?php foreach ($gmThread as $c): ?>
            <div class="list-group-item py-2 <?= (int)$c['author_id'] === (int)$emp['id'] ? 'ps-4 border-start border-primary border-3' : '' ?>">
                <div class="d-flex justify-content-between mb-1">
                    <small class="fw-semibold"><?= esc($c['author_name'] ?? '—') ?></small>
                    <small class="text-muted"><?= date('d M Y H:i', strtotime($c['created_at'])) ?></small>
                </div>
                <div class="small"><?= nl2br(esc($c['body'])) ?></div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
        </div>
        <div class="card-footer py-2">
            <form method="POST" action="<?= base_url('work-report/division/' . $item['id'] . '/reply-gm') ?>">
                <?= csrf_field() ?>
                <?php $lastGm = ! empty($gmThread) ? end($gmThread) : null; ?>
                <?php if ($lastGm): ?>
                <input type="hidden" name="parent_id" value="<?= $lastGm['id'] ?>">
                <?php endif; ?>
                <div class="d-flex gap-2">
                    <input type="text" name="body" class="form-control form-control-sm" placeholder="Balas ke GM…" required>
                    <button type="submit" class="btn btn-warning btn-sm flex-shrink-0 text-dark">Kirim</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Komentar Deputy → Dept Head -->
    <div class="card">
        <div class="card-header py-2 d-flex align-items-center justify-content-between">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-chat-left-text me-2 text-primary"></i>Komentar ke Dept Head</h6>
            <small class="text-muted" style="font-size:.68rem">Terlihat Dept Head + Deputy</small>
        </div>
        <div class="list-group list-group-flush">
        <?php if (empty($deptComments)): ?>
            <div class="list-group-item small text-muted text-center py-2">Belum ada komentar.</div>
        <?php else: ?>
            <?php foreach ($deptComments as $c): ?>
            <div class="list-group-item py-2">
                <div class="d-flex justify-content-between mb-1">
                    <small class="fw-semibold"><?= esc($c['author_name'] ?? '—') ?></small>
                    <small class="text-muted"><?= date('d M Y H:i', strtotime($c['created_at'])) ?></small>
                </div>
                <div class="small"><?= nl2br(esc($c['body'])) ?></div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
        </div>
        <div class="card-footer py-2">
            <form method="POST" action="<?= base_url('work-report/division/' . $item['id'] . '/comment') ?>">
                <?= csrf_field() ?>
                <div class="d-flex gap-2">
                    <input type="text" name="body" class="form-control form-control-sm" placeholder="Tulis komentar…" required>
                    <button type="submit" class="btn btn-primary btn-sm flex-shrink-0">Kirim</button>
                </div>
            </form>
        </div>
    </div>
</div>
</div>

<!-- Modal Edit -->
<div class="modal fade" id="modalEdit" tabindex="-1">
<div class="modal-dialog modal-lg">
<div class="modal-content">
<div class="modal-header py-2"><h6 class="modal-title fw-semibold">Edit Program Kerja</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
<form method="POST" action="<?= base_url('work-report/division/' . $item['id'] . '/edit') ?>">
    <?= csrf_field() ?>
    <div class="mb-2">
        <label class="form-label small fw-semibold">Judul <span class="text-danger">*</span></label>
        <input type="text" name="judul" class="form-control form-control-sm" value="<?= esc($item['judul']) ?>" required>
    </div>
    <div class="mb-2">
        <label class="form-label small fw-semibold">Deskripsi</label>
        <textarea name="deskripsi" class="form-control form-control-sm" rows="2"><?= esc($item['deskripsi'] ?? '') ?></textarea>
    </div>
    <div class="mb-2">
        <label class="form-label small fw-semibold">Assign ke Departemen</label>
        <select name="assigned_to_dept_id" class="form-select form-select-sm">
            <option value="">— Tidak di-assign —</option>
            <?php foreach ($depts as $d): ?>
            <option value="<?= $d['id'] ?>" <?= (int)($item['assigned_to_dept_id'] ?? 0) === (int)$d['id'] ? 'selected' : '' ?>><?= esc($d['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="row g-2">
        <div class="col-12 col-sm-6">
            <label class="form-label small fw-semibold">Target Mulai</label>
            <input type="date" name="target_mulai" class="form-control form-control-sm" value="<?= $item['target_mulai'] ?? '' ?>">
        </div>
        <div class="col-12 col-sm-6">
            <label class="form-label small fw-semibold">Target Selesai</label>
            <input type="date" name="target_selesai" class="form-control form-control-sm" value="<?= $item['target_selesai'] ?? '' ?>">
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
