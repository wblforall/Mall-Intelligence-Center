<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<style>
.comment-bubble { border-radius: 12px; padding: 10px 14px; }
.comment-internal { background: var(--c-inner-bg); border: 1px solid var(--c-inner-border); }
.comment-external { background: var(--bs-warning-bg-subtle, rgba(245,158,11,.08)); border: 1px solid rgba(245,158,11,.2); }
.comment-action   { background: var(--bs-success-bg-subtle, rgba(16,185,129,.08)); border: 1px solid rgba(16,185,129,.2); }
.comment-revision { background: var(--bs-danger-bg-subtle, rgba(239,68,68,.08)); border: 1px solid rgba(239,68,68,.2); }
.reply-indent     { margin-left: 2rem; border-left: 2px solid var(--c-inner-border); padding-left: 1rem; }
.version-pill     { font-size: .72rem; font-weight: 700; padding: 2px 8px; border-radius: 99px;
                    background: var(--c-icon-primary-bg); color: var(--c-icon-primary-fg); }
</style>
<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
$statusLabel = ['draft'=>'Draft','in_review'=>'In Review','revision'=>'Perlu Revisi','final'=>'Final','signed'=>'Signed'];
$statusBadge = ['draft'=>'secondary','in_review'=>'primary','revision'=>'warning','final'=>'success','signed'=>'success'];
$canAct = $canEdit;
$canApprove = $canApprove ?? false;
$status = $review['status'];

function renderComment(array $c, bool $canAct, int $reviewId): void {
    $isExt      = $c['user_id'] === null;
    $isRevision = $c['tipe'] === 'request_revision';
    $isFinal    = $c['tipe'] === 'mark_final';
    $bubbleClass = $isExt ? 'comment-external' : ($isRevision ? 'comment-revision' : ($isFinal ? 'comment-action' : 'comment-internal'));
    $name = $isExt ? ('<span class="badge bg-warning-subtle text-warning me-1">Pihak Kedua</span>' . esc($c['ext_name'])) : esc($c['user_name']);
    ?>
    <div class="mb-3">
        <div class="comment-bubble <?= $bubbleClass ?>">
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="fw-semibold small"><?= $name ?></span>
                <?php if ($isRevision): ?><span class="badge bg-danger-subtle text-danger small">Minta Revisi</span><?php endif; ?>
                <?php if ($isFinal):    ?><span class="badge bg-success-subtle text-success small">Finalisasi</span><?php endif; ?>
                <?php if ($c['versi_ke']): ?><span class="version-pill">v<?= $c['versi_ke'] ?></span><?php endif; ?>
                <span class="text-muted ms-auto" style="font-size:.72rem"><?= date('d M Y H:i', strtotime($c['created_at'])) ?></span>
            </div>
            <div class="text-body" style="font-size:.9rem"><?= nl2br(esc($c['komentar'])) ?></div>
        </div>
        <?php if ($canAct && !empty($c['replies'])): ?>
        <div class="reply-indent mt-2">
            <?php foreach ($c['replies'] as $reply): renderComment($reply, $canAct, $reviewId); endforeach; ?>
        </div>
        <?php endif; ?>
        <?php if ($canAct): ?>
        <div class="mt-1">
            <button class="btn btn-link btn-sm text-muted p-0" onclick="toggleReply(<?= $c['id'] ?>)">
                <i class="bi bi-reply me-1"></i>Balas
            </button>
            <div id="reply-<?= $c['id'] ?>" class="mt-2" style="display:none">
                <form action="<?= base_url('legal/reviews/'.$reviewId.'/comment') ?>" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="parent_id" value="<?= $c['id'] ?>">
                    <div class="d-flex gap-2">
                        <textarea name="komentar" class="form-control form-control-sm" rows="2" placeholder="Tulis balasan..." required></textarea>
                        <button class="btn btn-sm btn-primary align-self-end">Kirim</button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php
}
?>

<div class="container-fluid py-4">
    <div class="mb-3">
        <nav aria-label="breadcrumb" class="d-none d-md-block"><ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="<?= base_url('legal') ?>">Legal</a></li>
            <li class="breadcrumb-item"><a href="<?= base_url('legal/reviews') ?>">Review Kontrak</a></li>
            <li class="breadcrumb-item active"><?= esc($review['judul']) ?></li>
        </ol></nav>
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <h4 class="fw-bold mb-1"><?= esc($review['judul']) ?></h4>
                <span class="badge bg-<?= $statusBadge[$status] ?>-subtle text-<?= $statusBadge[$status] ?> me-2"><?= $statusLabel[$status] ?></span>
                <?php if ($review['ext_link_active']): ?><span class="badge bg-info-subtle text-info"><i class="bi bi-link-45deg me-1"></i>Link Eksternal Aktif</span><?php endif; ?>
            </div>
            <?php if ($canEdit): ?>
            <div class="d-flex gap-2 flex-wrap">
                <a href="<?= base_url('legal/reviews/'.$review['id'].'/edit') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil me-1"></i>Edit</a>
                <?php if ($status === 'final' && $canApprove): ?>
                <form action="<?= base_url('legal/reviews/'.$review['id'].'/mark-signed') ?>" method="post"><?= csrf_field() ?>
                    <button class="btn btn-sm btn-success"><i class="bi bi-pen me-1"></i>Tandai Signed</button>
                </form>
                <?php endif; ?>
                <?php if ($status === 'final'): ?>
                <a href="<?= base_url('legal/reviews/'.$review['id'].'/archive') ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-archive me-1"></i>Arsipkan</a>
                <?php endif; ?>
                <form action="<?= base_url('legal/reviews/'.$review['id'].'/delete') ?>" method="post" onsubmit="return confirm('Hapus review ini?')"><?= csrf_field() ?>
                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if (session()->getFlashdata('success')): ?><div class="alert alert-success"><?= session()->getFlashdata('success') ?></div><?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?><div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div><?php endif; ?>

    <div class="row g-3">
        <!-- Left: Dokumen -->
        <div class="col-lg-5">

            <!-- Versi terkini -->
            <div class="card mb-3">
                <div class="card-header fw-semibold d-flex align-items-center justify-content-between">
                    <span><i class="bi bi-file-earmark-text me-2"></i>Dokumen</span>
                    <?php if ($latest): ?><span class="version-pill">v<?= $latest['versi_ke'] ?></span><?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if ($latest): ?>
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <i class="bi bi-file-earmark-pdf text-danger fs-2"></i>
                        <div>
                            <div class="fw-medium">Versi <?= $latest['versi_ke'] ?></div>
                            <div class="text-muted small"><?= esc($latest['uploader_name'] ?? '—') ?> · <?= date('d M Y H:i', strtotime($latest['uploaded_at'])) ?></div>
                            <?php if ($latest['catatan_perubahan']): ?><div class="text-muted small fst-italic"><?= esc($latest['catatan_perubahan']) ?></div><?php endif; ?>
                        </div>
                    </div>
                    <a href="<?= base_url('legal/review-file/' . basename($latest['file_path'])) ?>"
                       target="_blank" class="btn btn-sm btn-outline-primary w-100">
                        <i class="bi bi-download me-1"></i>Download PDF
                    </a>
                    <?php else: ?>
                    <p class="text-muted text-center mb-0">Belum ada dokumen.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Upload versi baru -->
            <?php if ($canEdit && in_array($status, ['draft','revision'])): ?>
            <div class="card mb-3">
                <div class="card-header fw-semibold"><i class="bi bi-upload me-2"></i>Upload Versi Baru</div>
                <div class="card-body">
                    <form action="<?= base_url('legal/reviews/'.$review['id'].'/version') ?>" method="post" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <div class="mb-2"><input type="file" name="file_dokumen" class="form-control form-control-sm" accept=".pdf,.doc,.docx" required></div>
                        <div class="mb-2"><input type="text" name="catatan_perubahan" class="form-control form-control-sm" placeholder="Catatan perubahan (opsional)"></div>
                        <button class="btn btn-sm btn-primary w-100"><i class="bi bi-upload me-1"></i>Submit ke Reviewer</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- Riwayat versi -->
            <?php if (count($review['versions']) > 1): ?>
            <div class="card mb-3">
                <div class="card-header fw-semibold small">Riwayat Versi</div>
                <ul class="list-group list-group-flush">
                    <?php foreach ($review['versions'] as $v): ?>
                    <li class="list-group-item d-flex align-items-center gap-2 py-2">
                        <span class="version-pill">v<?= $v['versi_ke'] ?></span>
                        <span class="text-muted small flex-grow-1"><?= date('d M Y', strtotime($v['uploaded_at'])) ?> — <?= esc($v['uploader_name'] ?? '—') ?></span>
                        <a href="<?= base_url('legal/review-file/' . basename($v['file_path'])) ?>" target="_blank" class="btn btn-sm btn-outline-secondary py-0 px-2"><i class="bi bi-download"></i></a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <!-- Share link eksternal -->
            <?php if ($canEdit): ?>
            <div class="card">
                <div class="card-header fw-semibold"><i class="bi bi-share me-2"></i>Bagikan ke Pihak Kedua</div>
                <div class="card-body">
                    <?php if ($review['ext_token']): ?>
                    <div class="mb-2">
                        <label class="form-label small text-muted">Pihak Kedua</label>
                        <div class="fw-medium"><?= esc($review['ext_party_name'] ?? '(belum diisi)') ?></div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small text-muted">Link</label>
                        <div class="input-group input-group-sm">
                            <input type="text" id="extLink" class="form-control"
                                   value="<?= base_url('legal/ext/'.$review['ext_token']) ?>" readonly>
                            <button class="btn btn-outline-secondary" onclick="copyLink()"><i class="bi bi-clipboard"></i></button>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="text-muted small">Status:</span>
                        <?php if ($review['ext_link_active']): ?>
                        <span class="badge bg-success-subtle text-success">Aktif</span>
                        <?php else: ?>
                        <span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>
                        <?php endif; ?>
                        <form action="<?= base_url('legal/reviews/'.$review['id'].'/toggle-link') ?>" method="post" class="ms-auto">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-<?= $review['ext_link_active'] ? 'danger' : 'success' ?>">
                                <?= $review['ext_link_active'] ? 'Nonaktifkan' : 'Aktifkan' ?>
                            </button>
                        </form>
                    </div>
                    <?php else: ?>
                    <form action="<?= base_url('legal/reviews/'.$review['id'].'/generate-link') ?>" method="post">
                        <?= csrf_field() ?>
                        <div class="mb-2">
                            <input type="text" name="ext_party_name" class="form-control form-control-sm"
                                   value="<?= esc($review['ext_party_name'] ?? '') ?>" placeholder="Nama pihak kedua (opsional)">
                        </div>
                        <button class="btn btn-sm btn-outline-primary w-100"><i class="bi bi-link-45deg me-1"></i>Generate Link</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Right: Diskusi -->
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header fw-semibold d-flex align-items-center justify-content-between">
                    <span><i class="bi bi-chat-dots me-2"></i>Diskusi</span>
                    <!-- Reviewer actions -->
                    <?php if ($canEdit && in_array($status, ['in_review'])): ?>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalRevision">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>Minta Revisi
                        </button>
                        <?php if ($canApprove): ?>
                        <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalFinal">
                            <i class="bi bi-check-lg me-1"></i>Finalisasi
                        </button>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="card-body" style="max-height:600px; overflow-y:auto">
                    <?php if (empty($thread)): ?>
                    <p class="text-muted text-center py-3 mb-0 small">Belum ada komentar.</p>
                    <?php else: ?>
                    <?php foreach ($thread as $c): renderComment($c, $canAct, $review['id']); endforeach; ?>
                    <?php endif; ?>
                </div>
                <!-- Form komentar -->
                <div class="card-footer">
                    <form action="<?= base_url('legal/reviews/'.$review['id'].'/comment') ?>" method="post">
                        <?= csrf_field() ?>
                        <div class="d-flex gap-2">
                            <textarea name="komentar" class="form-control form-control-sm" rows="2"
                                      placeholder="Tulis komentar..." required></textarea>
                            <button class="btn btn-primary btn-sm align-self-end"><i class="bi bi-send"></i></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Minta Revisi -->
<div class="modal fade" id="modalRevision" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="<?= base_url('legal/reviews/'.$review['id'].'/request-revision') ?>" method="post">
                <?= csrf_field() ?>
                <div class="modal-header"><h5 class="modal-title">Minta Revisi</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <label class="form-label">Catatan untuk drafter</label>
                    <textarea name="catatan" class="form-control" rows="4" placeholder="Jelaskan apa yang perlu direvisi..."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger"><i class="bi bi-arrow-counterclockwise me-1"></i>Kirim Permintaan Revisi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Finalisasi -->
<div class="modal fade" id="modalFinal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="<?= base_url('legal/reviews/'.$review['id'].'/mark-final') ?>" method="post">
                <?= csrf_field() ?>
                <div class="modal-header"><h5 class="modal-title">Finalisasi Dokumen</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <p class="text-muted small">Dokumen akan ditandai sebagai <strong>Final</strong>. Drafter kemudian bisa mengarsipkan ke Legal.</p>
                    <label class="form-label">Catatan (opsional)</label>
                    <textarea name="catatan" class="form-control" rows="2" placeholder="Dokumen disetujui, siap ditandatangani."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-check-lg me-1"></i>Finalisasi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
function toggleReply(id) {
    const el = document.getElementById('reply-' + id);
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
}
function copyLink() {
    const el = document.getElementById('extLink');
    navigator.clipboard.writeText(el.value).then(() => {
        el.select();
        const btn = el.nextElementSibling;
        btn.innerHTML = '<i class="bi bi-check"></i>';
        setTimeout(() => btn.innerHTML = '<i class="bi bi-clipboard"></i>', 2000);
    });
}
</script>
<?= $this->endSection() ?>
