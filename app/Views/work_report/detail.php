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
    <a href="<?= base_url('work-report') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h5 class="fw-bold mb-0"><?= esc($item['judul']) ?></h5>
        <small class="text-muted"><?= esc($deptInfo['name'] ?? '') ?></small>
    </div>
</div>

<div class="row g-3">
<!-- Info + History -->
<div class="col-12 col-lg-7">
    <div class="card mb-3">
        <div class="card-header py-2"><h6 class="mb-0 fw-semibold"><i class="bi bi-info-circle me-2"></i>Detail Program Kerja</h6></div>
        <div class="card-body">
            <?php if (! empty($item['deskripsi'])): ?>
            <p class="small mb-2"><?= nl2br(esc($item['deskripsi'])) ?></p>
            <?php endif; ?>
            <div class="row g-2" style="font-size:.8rem">
                <?php if (! empty($item['pic_name'])): ?>
                <div class="col-6 col-sm-4"><span class="text-muted">PIC</span><br><?= esc($item['pic_name']) ?></div>
                <?php endif; ?>
                <?php if (! empty($item['target_mulai'])): ?>
                <div class="col-6 col-sm-4"><span class="text-muted">Target Mulai</span><br><?= date('d M Y', strtotime($item['target_mulai'])) ?></div>
                <?php endif; ?>
                <?php if (! empty($item['target_selesai'])): ?>
                <div class="col-6 col-sm-4"><span class="text-muted">Target Selesai</span><br><?= date('d M Y', strtotime($item['target_selesai'])) ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header py-2"><h6 class="mb-0 fw-semibold"><i class="bi bi-clock-history me-2"></i>Riwayat Update</h6></div>
        <div class="list-group list-group-flush">
        <?php if (empty($history)): ?>
            <div class="list-group-item text-muted small py-3 text-center">Belum ada update.</div>
        <?php else: ?>
            <?php foreach ($history as $h):
                $info = $statusLabel[$h['status']] ?? $statusLabel['on_track'];
            ?>
            <div class="list-group-item py-2">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge <?= $info['badge'] ?>" style="font-size:.65rem"><?= $info['label'] ?></span>
                    <?php if ($h['progress_pct'] !== null): ?>
                    <span class="badge bg-body-secondary text-body" style="font-size:.65rem"><?= $h['progress_pct'] ?>%</span>
                    <?php endif; ?>
                    <small class="text-muted ms-auto"><?= date('d M Y H:i', strtotime($h['created_at'])) ?></small>
                    <small class="text-muted">· <?= esc($h['updated_by_name'] ?? '—') ?></small>
                </div>
                <?php if (! empty($h['catatan'])): ?>
                <div class="small mb-1"><?= nl2br(esc($h['catatan'])) ?></div>
                <?php endif; ?>
                <?php if (! empty($h['hambatan'])): ?>
                <div class="small p-1 rounded border-start border-warning border-2 ps-2" style="background:var(--bs-secondary-bg)">
                    <i class="bi bi-cone-striped text-warning me-1"></i><?= nl2br(esc($h['hambatan'])) ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
        </div>
    </div>
</div>

<!-- Komunikasi dengan Deputy (dua arah) -->
<div class="col-12 col-lg-5" id="komentar">
    <div class="card">
        <div class="card-header py-2">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-chat-left-text me-2 text-primary"></i>Komunikasi dengan Deputy</h6>
        </div>
        <div class="list-group list-group-flush">
        <?php if (empty($comments)): ?>
            <div class="list-group-item text-muted small py-3 text-center">Belum ada komunikasi. Kirim komentar/pertanyaan ke Deputy di bawah.</div>
        <?php else: ?>
            <?php foreach ($comments as $c): $mine = (int) ($c['author_id'] ?? 0) === (int) $empId; ?>
            <div class="list-group-item py-2 <?= $mine ? 'bg-primary-subtle' : '' ?>">
                <div class="d-flex justify-content-between mb-1">
                    <small class="fw-semibold"><?= esc($c['author_name'] ?? '—') ?><?= $mine ? ' <span class="text-primary">(Anda)</span>' : '' ?></small>
                    <small class="text-muted"><?= date('d M Y H:i', strtotime($c['created_at'])) ?></small>
                </div>
                <div class="small"><?= nl2br(esc($c['body'])) ?></div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
        </div>
        <div class="card-footer py-2">
            <form method="POST" action="<?= base_url('work-report/' . $item['id'] . '/comment') ?>">
                <?= csrf_field() ?>
                <div class="input-group input-group-sm">
                    <input type="text" name="body" class="form-control" placeholder="Tulis komentar ke Deputy…" required>
                    <button class="btn btn-primary" type="submit"><i class="bi bi-send me-1"></i>Kirim</button>
                </div>
            </form>
        </div>
    </div>
</div>
</div>

<?= $this->endSection() ?>
