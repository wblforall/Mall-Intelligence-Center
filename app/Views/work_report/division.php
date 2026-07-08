<?= $this->extend('layouts/main') ?>
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

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-kanban me-2"></i>Progress Report — Divisi</h4>
        <small class="text-muted"><?= esc($divisi['nama'] ?? '') ?></small>
    </div>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAdd">
        <i class="bi bi-plus-lg me-1"></i>Tambah Program Kerja
    </button>
</div>


<?php
// Kelompokkan per dept
$grouped = [];
foreach ($items as $item) {
    $key = $item['dept_name'] ?? 'Tanpa Departemen';
    $grouped[$key][] = $item;
}
ksort($grouped);
?>

<?php foreach ($grouped as $deptName => $deptItems): ?>
<div class="card mb-3">
<div class="card-header d-flex align-items-center justify-content-between py-2">
    <h6 class="mb-0 fw-semibold"><i class="bi bi-building me-2 text-muted"></i><?= esc($deptName) ?></h6>
    <small class="text-muted"><?= count($deptItems) ?> program kerja</small>
</div>
<div class="list-group list-group-flush">
<?php foreach ($deptItems as $item):
    $st   = $item['latest_status'] ?? null;
    $info = $st ? ($statusLabel[$st] ?? $statusLabel['on_track']) : null;
    $isFlagged = (int)($item['is_flagged'] ?? 0) > 0;
    $overdue = ! empty($item['target_selesai']) && $item['target_selesai'] < date('Y-m-d') && $st !== 'done' && $st !== 'cancelled';
?>
<div class="list-group-item py-2" id="initiative-<?= $item['id'] ?>">
    <div class="d-flex align-items-start gap-2">
        <div class="flex-grow-1">
            <div class="d-flex align-items-center gap-1 flex-wrap mb-1">
                <span class="fw-semibold small"><?= esc($item['judul']) ?></span>
                <?php if ($info): ?>
                <span class="badge <?= $info['badge'] ?>" style="font-size:.63rem"><?= $info['label'] ?></span>
                <?php endif; ?>
                <?php if ($isFlagged): ?>
                <span class="badge bg-warning text-dark" style="font-size:.63rem"><i class="bi bi-flag-fill me-1"></i>Tampil di GM</span>
                <?php endif; ?>
                <?php if ($overdue): ?>
                <span class="badge bg-danger" style="font-size:.63rem"><i class="bi bi-exclamation-triangle me-1"></i>Terlambat</span>
                <?php endif; ?>
                <?php if (! empty($gmUnread[$item['id']])): ?>
                <span class="badge rounded-pill bg-danger" style="font-size:.65rem;min-width:1.4em">
                    <?= $gmUnread[$item['id']] ?>
                </span>
                <?php endif; ?>
                <?php if (! empty($deptReplyUnread[$item['id']])): ?>
                <span class="badge rounded-pill bg-primary" style="font-size:.63rem"><i class="bi bi-reply-fill me-1"></i><?= $deptReplyUnread[$item['id']] ?> balasan Dept</span>
                <?php endif; ?>
                <?php if (! empty($item['assigned_to_dept_id'])): ?>
                <span class="badge bg-info-subtle text-info" style="font-size:.63rem"><i class="bi bi-person-badge me-1"></i>Dari Deputy</span>
                <?php endif; ?>
            </div>
            <?php if ($item['latest_progress'] !== null): ?>
            <div class="mt-2 d-flex align-items-center gap-2">
                <div class="progress flex-grow-1" style="height:5px">
                    <div class="progress-bar <?= (int)$item['latest_progress'] >= 100 ? 'bg-success' : ((int)$item['latest_progress'] >= 60 ? 'bg-primary' : 'bg-warning') ?>"
                        style="width:<?= $item['latest_progress'] ?>%"></div>
                </div>
                <small class="text-muted flex-shrink-0" style="font-size:.7rem"><?= $item['latest_progress'] ?>%</small>
            </div>
            <?php endif; ?>
            <?php if (! empty($item['latest_catatan']) || ! empty($item['latest_hambatan'])): ?>
            <div class="mt-1 d-flex flex-column gap-1">
                <?php if (! empty($item['latest_catatan'])): ?>
                <div class="px-2 py-1 rounded" style="background:var(--bs-secondary-bg);font-size:.8rem;border-left:3px solid var(--bs-primary)">
                    <?= nl2br(esc(mb_substr($item['latest_catatan'], 0, 150))) ?><?= mb_strlen($item['latest_catatan']) > 150 ? '…' : '' ?>
                </div>
                <?php endif; ?>
                <?php if (! empty($item['latest_hambatan'])): ?>
                <div class="px-2 py-1 rounded" style="background:var(--bs-secondary-bg);font-size:.8rem;border-left:3px solid var(--bs-warning)">
                    <i class="bi bi-cone-striped text-warning me-1"></i><?= nl2br(esc(mb_substr($item['latest_hambatan'], 0, 120))) ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <div class="d-flex gap-2 mt-1 flex-wrap" style="font-size:.68rem;color:var(--bs-secondary-color)">
                <?php if (! empty($item['created_by_name'])): ?><span class="text-muted"><i class="bi bi-pencil me-1"></i><?= esc($item['created_by_name']) ?></span><?php endif; ?>
                <?php if (! empty($item['pic_name'])): ?><span><i class="bi bi-person-check me-1"></i>PIC: <?= esc($item['pic_name']) ?></span><?php endif; ?>
                <?php if (! empty($item['target_selesai'])): ?><span><i class="bi bi-calendar-check me-1"></i><?= date('d M Y', strtotime($item['target_selesai'])) ?></span><?php endif; ?>
                <?php if (! empty($item['latest_updated_at'])): ?><span><i class="bi bi-arrow-repeat me-1"></i><?= date('d M Y', strtotime($item['latest_updated_at'])) ?></span><?php endif; ?>
            </div>
        </div>

        <div class="d-flex flex-column gap-1 align-items-end flex-shrink-0">
            <!-- Flag/Unflag (hanya Deputy GM asli; manajer divisi lihat status saja) -->
            <?php if ($canFlag ?? true): ?>
            <form method="POST" action="<?= base_url('work-report/division/' . $item['id'] . '/flag') ?>">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-sm <?= $isFlagged ? 'btn-warning' : 'btn-outline-secondary' ?>" style="font-size:.68rem">
                    <i class="bi bi-flag<?= $isFlagged ? '-fill' : '' ?> me-1"></i><?= $isFlagged ? 'Batalkan Flag' : 'Flag ke GM' ?>
                </button>
            </form>
            <?php elseif ($isFlagged): ?>
            <span class="badge bg-warning text-dark" style="font-size:.62rem"><i class="bi bi-flag-fill me-1"></i>Tampil di GM</span>
            <?php endif; ?>
            <!-- Komentar ke Dept -->
            <button class="btn btn-sm btn-outline-primary" style="font-size:.68rem"
                data-bs-toggle="collapse" data-bs-target="#commentForm<?= $item['id'] ?>">
                <i class="bi bi-chat-left-dots me-1"></i>Komentar
            </button>
            <!-- Detail -->
            <a href="<?= base_url('work-report/division/' . $item['id'] . '/detail') ?>" class="btn btn-sm btn-outline-info" style="font-size:.68rem">
                <i class="bi bi-eye me-1"></i>Detail
            </a>
        </div>
    </div>

    <!-- Form Komentar ke Dept -->
    <div class="collapse mt-2" id="commentForm<?= $item['id'] ?>">
        <form method="POST" action="<?= base_url('work-report/division/' . $item['id'] . '/comment') ?>">
            <?= csrf_field() ?>
            <div class="d-flex gap-2">
                <input type="text" name="body" class="form-control form-control-sm" placeholder="Komentar ke Dept Head…" required>
                <button type="submit" class="btn btn-primary btn-sm flex-shrink-0">Kirim</button>
            </div>
            <div class="form-text" style="font-size:.65rem">Hanya terlihat oleh Dept Head dan Deputy.</div>
        </form>
    </div>
</div>
<?php endforeach; ?>
</div>
</div>
<?php endforeach; ?>

<?php if (empty($items)): ?>
<div class="text-center text-muted py-5">
    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
    Belum ada program kerja di divisi ini.
</div>
<?php endif; ?>

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
