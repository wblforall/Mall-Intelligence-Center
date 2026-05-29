<!DOCTYPE html>
<html lang="id" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #0f172a; color: #f1f5f9; }
        .card { background: #1e293b; border-color: #334155; }
        .comment-bubble { border-radius: 10px; padding: 10px 14px; margin-bottom: 12px; }
        .comment-internal { background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.08); }
        .comment-external { background: rgba(245,158,11,.08); border: 1px solid rgba(245,158,11,.2); }
        .version-pill { font-size:.7rem; font-weight:700; padding:2px 8px; border-radius:99px; background:rgba(79,142,247,.2); color:#4f8ef7; }
        .reply-indent { margin-left:1.5rem; border-left:2px solid rgba(255,255,255,.08); padding-left:1rem; }
        .form-control, .form-select { background:#293548; border-color:#334155; color:#f1f5f9; }
        .form-control::placeholder { color:#64748b; }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark" style="background:#0a1525; border-bottom:1px solid rgba(255,255,255,.07)">
        <div class="container">
            <span class="navbar-brand fw-bold">
                <i class="bi bi-shield-check me-2 text-primary"></i>Mall Intelligence Center
            </span>
        </div>
    </nav>

    <div class="container py-4" style="max-width:860px">

        <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
        <?php endif; ?>

        <div class="card mb-3">
            <div class="card-body">
                <h5 class="fw-bold mb-1"><?= esc($review['judul']) ?></h5>
                <?php if ($review['ext_party_name']): ?>
                <p class="text-muted small mb-0">Pihak Kedua: <strong><?= esc($review['ext_party_name']) ?></strong></p>
                <?php endif; ?>
                <?php if ($review['deskripsi']): ?>
                <p class="text-muted small mt-2 mb-0"><?= nl2br(esc($review['deskripsi'])) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="row g-3">
            <!-- Dokumen -->
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-header fw-semibold small"><i class="bi bi-file-earmark-text me-2"></i>Dokumen</div>
                    <div class="card-body text-center">
                        <?php if ($latest): ?>
                        <i class="bi bi-file-earmark-pdf text-danger" style="font-size:3rem"></i>
                        <div class="fw-medium mt-2">Versi <?= $latest['versi_ke'] ?></div>
                        <div class="text-muted small mb-3"><?= date('d M Y', strtotime($latest['uploaded_at'])) ?></div>
                        <a href="<?= base_url('uploads/legal_reviews/' . basename($latest['file_path'])) ?>"
                           target="_blank" class="btn btn-primary btn-sm w-100">
                            <i class="bi bi-download me-1"></i>Download Dokumen
                        </a>
                        <?php else: ?>
                        <p class="text-muted small">Belum ada dokumen.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Diskusi -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header fw-semibold small"><i class="bi bi-chat-dots me-2"></i>Diskusi</div>
                    <div class="card-body" style="max-height:400px; overflow-y:auto">
                        <?php if (empty($thread)): ?>
                        <p class="text-muted text-center small py-3 mb-0">Belum ada komentar.</p>
                        <?php else: ?>
                        <?php foreach ($thread as $c):
                            $isExt = $c['user_id'] === null;
                            $name  = $isExt ? '<span class="badge bg-warning-subtle text-warning me-1">Pihak Kedua</span>'.esc($c['ext_name']) : esc($c['user_name'].' (Internal)');
                        ?>
                        <div class="comment-bubble <?= $isExt ? 'comment-external' : 'comment-internal' ?>">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="fw-semibold small"><?= $name ?></span>
                                <?php if ($c['versi_ke']): ?><span class="version-pill">v<?= $c['versi_ke'] ?></span><?php endif; ?>
                                <span class="ms-auto text-muted" style="font-size:.7rem"><?= date('d M Y H:i', strtotime($c['created_at'])) ?></span>
                            </div>
                            <div style="font-size:.9rem"><?= nl2br(esc($c['komentar'])) ?></div>
                        </div>
                        <?php if (! empty($c['replies'])): ?>
                        <div class="reply-indent">
                            <?php foreach ($c['replies'] as $r): $rExt = $r['user_id'] === null; $rName = $rExt ? '<span class="badge bg-warning-subtle text-warning me-1">Pihak Kedua</span>'.esc($r['ext_name']) : esc($r['user_name'].' (Internal)'); ?>
                            <div class="comment-bubble <?= $rExt ? 'comment-external' : 'comment-internal' ?>">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="fw-semibold small"><?= $rName ?></span>
                                    <span class="ms-auto text-muted" style="font-size:.7rem"><?= date('d M Y H:i', strtotime($r['created_at'])) ?></span>
                                </div>
                                <div style="font-size:.9rem"><?= nl2br(esc($r['komentar'])) ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer">
                        <form action="<?= base_url('legal/ext/'.$token.'/comment') ?>" method="post">
                            <?= csrf_field() ?>
                            <div class="mb-2">
                                <input type="text" name="ext_name" class="form-control form-control-sm"
                                       placeholder="Nama Anda *" required maxlength="150">
                            </div>
                            <div class="d-flex gap-2">
                                <textarea name="komentar" class="form-control form-control-sm" rows="2"
                                          placeholder="Tulis komentar atau catatan..." required></textarea>
                                <button class="btn btn-primary btn-sm align-self-end"><i class="bi bi-send"></i></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <p class="text-muted small text-center mt-4">
            Link ini dibagikan oleh tim Legal PT. Wulandari Bangun Laksana Tbk. &middot; Hanya untuk keperluan review dokumen.
        </p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
