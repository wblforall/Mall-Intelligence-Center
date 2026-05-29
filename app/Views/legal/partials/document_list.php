<?php
// Usage: <?= view('legal/partials/document_list', ['documents' => $docs, 'entity_type' => 'permit', 'entity_id' => $id, 'canEdit' => true]) ?>
?>
<div class="card mb-3">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span class="fw-semibold"><i class="bi bi-paperclip me-2"></i>Dokumen Terlampir</span>
        <?php if ($canEdit ?? false): ?>
        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#uploadDocForm">
            <i class="bi bi-upload me-1"></i> Upload
        </button>
        <?php endif; ?>
    </div>

    <?php if ($canEdit ?? false): ?>
    <div class="collapse" id="uploadDocForm">
        <div class="card-body border-bottom">
            <form action="<?= base_url('legal/documents/upload') ?>" method="post" enctype="multipart/form-data" class="row g-2">
                <?= csrf_field() ?>
                <input type="hidden" name="entity_type" value="<?= esc($entity_type) ?>">
                <input type="hidden" name="entity_id"   value="<?= esc($entity_id) ?>">
                <div class="col-md-5">
                    <input type="text" name="nama_dokumen" class="form-control form-control-sm" placeholder="Nama dokumen (opsional)">
                </div>
                <div class="col-md-5">
                    <input type="file" name="file_dokumen" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.jpg,.png" required>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-sm btn-primary w-100" type="submit">Simpan</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <div class="card-body p-0">
        <?php if (empty($documents)): ?>
            <p class="text-muted text-center py-3 mb-0 small">Belum ada dokumen.</p>
        <?php else: ?>
        <ul class="list-group list-group-flush">
            <?php foreach ($documents as $doc): ?>
            <li class="list-group-item d-flex align-items-center gap-3 py-2">
                <i class="bi bi-file-earmark-pdf text-danger fs-5"></i>
                <div class="flex-grow-1 min-w-0">
                    <div class="fw-medium text-truncate"><?= esc($doc['nama_dokumen']) ?></div>
                    <div class="text-muted small"><?= esc($doc['uploader_name'] ?? '—') ?> · <?= date('d M Y', strtotime($doc['uploaded_at'])) ?>
                        <?php if ($doc['file_size']): ?> · <?= round($doc['file_size'] / 1024) ?> KB<?php endif; ?>
                    </div>
                </div>
                <div class="d-flex gap-2 flex-shrink-0">
                    <a href="<?= base_url('legal/documents/' . $doc['id'] . '/download') ?>" class="btn btn-sm btn-outline-secondary" title="Download">
                        <i class="bi bi-download"></i>
                    </a>
                    <?php if ($canEdit ?? false): ?>
                    <form action="<?= base_url('legal/documents/' . $doc['id'] . '/delete') ?>" method="post"
                          onsubmit="return confirm('Hapus dokumen ini?')">
                        <?= csrf_field() ?>
                        <button class="btn btn-sm btn-outline-danger" title="Hapus"><i class="bi bi-trash"></i></button>
                    </form>
                    <?php endif; ?>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
</div>
