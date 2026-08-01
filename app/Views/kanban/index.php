<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-kanban me-2"></i>Boards</h4>
        <small class="text-muted">Papan tugas kolaboratif — buat board untuk proyek, agenda, atau tugas tim apa pun.</small>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= base_url('kanban' . ($arsip ? '' : '?arsip=1')) ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-archive me-1"></i><?= $arsip ? 'Board Aktif' : 'Arsip' ?>
        </a>
        <?php if (! $arsip): ?>
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#newBoardModal">
            <i class="bi bi-plus-lg me-1"></i>Board Baru
        </button>
        <?php endif; ?>
    </div>
</div>

<?php if (! $boards): ?>
<div class="card"><div class="card-body text-center py-5">
    <i class="bi bi-kanban" style="font-size:2.2rem; opacity:.4"></i>
    <div class="mt-2 fw-semibold"><?= $arsip ? 'Tidak ada board terarsip.' : 'Belum ada board.' ?></div>
    <?php if (! $arsip): ?><div class="text-muted small mt-1">Klik "Board Baru" untuk mulai.</div><?php endif; ?>
</div></div>
<?php else: ?>
<div class="row g-3">
    <?php foreach ($boards as $b): ?>
    <div class="col-12 col-sm-6 col-lg-4 col-xxl-3">
        <a href="<?= base_url('kanban/' . $b['id']) ?>" class="text-decoration-none">
            <div class="card h-100 kb-board-tile">
                <div style="height:6px; border-radius:1.1rem 1.1rem 0 0; background:<?= esc($b['color'] ?: '#e8415a') ?>"></div>
                <div class="card-body py-3">
                    <div class="fw-semibold mb-1"><?= esc($b['nama']) ?></div>
                    <?php if ($b['deskripsi']): ?><div class="small text-muted mb-2" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden"><?= esc($b['deskripsi']) ?></div><?php endif; ?>
                    <div class="d-flex align-items-center gap-2 small text-muted">
                        <span class="badge <?= $b['my_role'] === 'owner' ? 'bg-danger-subtle text-danger-emphasis' : 'bg-secondary-subtle text-secondary-emphasis' ?>"><?= esc(ucfirst($b['my_role'] ?? '-')) ?></span>
                        <span><i class="bi bi-people me-1"></i><?= (int) $b['member_count'] ?></span>
                        <span><i class="bi bi-card-checklist me-1"></i><?= (int) $b['card_count'] ?> kartu</span>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Modal board baru -->
<div class="modal fade" id="newBoardModal" tabindex="-1">
<div class="modal-dialog"><div class="modal-content">
<form method="POST" action="<?= base_url('kanban/create') ?>">
    <?= csrf_field() ?>
    <div class="modal-header py-2"><h6 class="modal-title fw-semibold">Board Baru</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="mb-3">
            <label class="form-label small fw-semibold">Nama board <span class="text-danger">*</span></label>
            <input type="text" name="nama" class="form-control" maxlength="150" required autofocus placeholder="mis. Persiapan Event Ramadan">
        </div>
        <div class="mb-3">
            <label class="form-label small fw-semibold">Deskripsi</label>
            <textarea name="deskripsi" class="form-control" rows="2" placeholder="(opsional)"></textarea>
        </div>
        <div>
            <label class="form-label small fw-semibold">Warna</label>
            <div class="d-flex gap-2 flex-wrap">
                <?php foreach (['#e8415a', '#22d3ee', '#f97316', '#10b981', '#3b82f6', '#a855f7', '#64748b'] as $i => $c): ?>
                <label class="kb-color-opt">
                    <input type="radio" name="color" value="<?= $c ?>" <?= $i === 0 ? 'checked' : '' ?> class="d-none">
                    <span style="background:<?= $c ?>"></span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="modal-footer py-2">
        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i>Buat Board</button>
    </div>
</form>
</div></div>
</div>

<style>
.kb-board-tile { transition: transform .12s, border-color .12s; }
.kb-board-tile:hover { transform: translateY(-2px); border-color: rgba(232,65,90,.4) !important; }
.kb-color-opt span { display:block; width:34px; height:34px; border-radius:9px; cursor:pointer; opacity:.55; border:2px solid transparent; }
.kb-color-opt input:checked + span { opacity:1; border-color:#fff; box-shadow:0 0 0 2px rgba(255,255,255,.25); }
</style>

<?= $this->endSection() ?>
