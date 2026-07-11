<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php $isEdit = $review !== null; ?>
<div class="container-fluid py-4">
    <div class="mb-3">
        <nav aria-label="breadcrumb" class="d-none d-md-block"><ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="<?= base_url('legal') ?>">Legal</a></li>
            <li class="breadcrumb-item"><a href="<?= base_url('legal/reviews') ?>">Review Kontrak</a></li>
            <li class="breadcrumb-item active"><?= $title ?></li>
        </ol></nav>
        <h4 class="fw-bold mb-0"><?= esc($title) ?></h4>
    </div>
    <div class="card" style="max-width:760px"><div class="card-body">
        <form method="post" enctype="multipart/form-data"
              action="<?= $isEdit ? base_url('legal/reviews/'.$review['id'].'/edit') : base_url('legal/reviews') ?>">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-12"><label class="form-label">Judul Review <span class="text-danger">*</span></label>
                    <input type="text" name="judul" class="form-control" value="<?= esc($review['judul'] ?? '') ?>" required></div>

                <div class="col-md-5"><label class="form-label">Tipe Dokumen</label>
                    <select name="entity_type" class="form-select">
                        <option value="standalone" <?= ($review['entity_type'] ?? '') === 'standalone' ? 'selected' : '' ?>>Standalone (baru)</option>
                        <option value="lease"    <?= ($review['entity_type'] ?? '') === 'lease'    ? 'selected' : '' ?>>Perjanjian Sewa</option>
                        <option value="permit"   <?= ($review['entity_type'] ?? '') === 'permit'   ? 'selected' : '' ?>>Perizinan</option>
                        <option value="contract" <?= ($review['entity_type'] ?? '') === 'contract' ? 'selected' : '' ?>>Kontrak Vendor</option>
                    </select></div>
                <div class="col-md-4"><label class="form-label">ID Terkait <span class="text-muted small">(opsional)</span></label>
                    <input type="number" name="entity_id" class="form-control" value="<?= $review['entity_id'] ?? '' ?>" placeholder="ID record di arsip"></div>

                <div class="col-12"><label class="form-label">Reviewer <span class="text-danger">*</span></label>
                    <select name="reviewer_ids[]" class="form-select" multiple size="4" required>
                        <?php foreach ($users as $u): ?>
                        <option value="<?= $u['id'] ?>"><?= esc($u['name']) ?> — <?= esc($u['email'] ?? '') ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Tahan Ctrl/Cmd untuk pilih lebih dari satu.</div>
                </div>

                <?php if (! $isEdit): ?>
                <div class="col-12"><label class="form-label">Upload Draft Pertama <span class="text-danger">*</span></label>
                    <input type="file" name="file_dokumen" class="form-control" accept=".pdf,.doc,.docx" required>
                    <div class="form-text">PDF / Word, maks 20 MB</div></div>
                <div class="col-12"><label class="form-label">Catatan Versi Awal</label>
                    <input type="text" name="catatan_perubahan" class="form-control" placeholder="misal: Draft awal dari legal"></div>
                <?php endif; ?>

                <div class="col-12"><label class="form-label">Deskripsi</label>
                    <textarea name="deskripsi" class="form-control" rows="3"><?= esc($review['deskripsi'] ?? '') ?></textarea></div>

                <div class="col-12"><label class="form-label">Nama Pihak Kedua <span class="text-muted small">(opsional)</span></label>
                    <input type="text" name="ext_party_name" class="form-control" value="<?= esc($review['ext_party_name'] ?? '') ?>" placeholder="misal: PT. Maju Jaya"></div>

                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                    <a href="<?= base_url('legal/reviews') ?>" class="btn btn-light">Batal</a>
                </div>
            </div>
        </form>
    </div></div>
</div>
<?= $this->endSection() ?>
