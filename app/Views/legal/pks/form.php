<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php $isEdit = $row !== null; ?>

<div class="container-fluid py-4">
    <div class="mb-3">
        <nav aria-label="breadcrumb"><ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="<?= base_url('legal') ?>">Legal</a></li>
            <li class="breadcrumb-item"><a href="<?= base_url('legal/pks') ?>">Perjanjian Kerja Sama</a></li>
            <li class="breadcrumb-item active"><?= $isEdit ? 'Edit PKS' : 'Tambah PKS' ?></li>
        </ol></nav>
        <h4 class="fw-bold mb-0"><?= $isEdit ? 'Edit PKS' : 'Tambah PKS' ?></h4>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
    <?php endif; ?>

    <div class="card" style="max-width:720px">
        <div class="card-body">
            <form method="post" action="<?= $isEdit ? base_url('legal/pks/' . $row['id'] . '/edit') : base_url('legal/pks') ?>">
                <?= csrf_field() ?>
                <div class="row g-3">

                    <div class="col-md-6">
                        <label class="form-label">Nomor PKS <span class="text-danger">*</span></label>
                        <input type="text" name="nomor_pks" class="form-control"
                               value="<?= esc($row['nomor_pks'] ?? '') ?>" required
                               placeholder="Contoh: PKS/IT/2026/001">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Pihak Kedua <span class="text-danger">*</span></label>
                        <input type="text" name="pihak_kedua" class="form-control"
                               value="<?= esc($row['pihak_kedua'] ?? '') ?>" required
                               placeholder="Nama perusahaan / pihak">
                    </div>

                    <div class="col-12">
                        <label class="form-label">Ruang Lingkup</label>
                        <textarea name="ruang_lingkup" class="form-control" rows="3"
                                  placeholder="Uraikan ruang lingkup perjanjian..."><?= esc($row['ruang_lingkup'] ?? '') ?></textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Nilai</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" name="nilai" class="form-control"
                                   value="<?= esc($row['nilai'] ?? '') ?>"
                                   placeholder="0"
                                   inputmode="numeric">
                        </div>
                        <div class="form-text">Masukkan angka tanpa titik/koma.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="draft"      <?= ($row['status'] ?? 'draft') === 'draft'      ? 'selected' : '' ?>>Draft</option>
                            <option value="active"     <?= ($row['status'] ?? '')       === 'active'     ? 'selected' : '' ?>>Aktif</option>
                            <option value="expired"    <?= ($row['status'] ?? '')       === 'expired'    ? 'selected' : '' ?>>Expired</option>
                            <option value="terminated" <?= ($row['status'] ?? '')       === 'terminated' ? 'selected' : '' ?>>Dihentikan</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Tanggal Mulai <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_mulai" class="form-control"
                               value="<?= $row['tanggal_mulai'] ?? '' ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tanggal Berakhir <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_berakhir" class="form-control"
                               value="<?= $row['tanggal_berakhir'] ?? '' ?>" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Catatan</label>
                        <textarea name="catatan" class="form-control" rows="2"><?= esc($row['catatan'] ?? '') ?></textarea>
                    </div>

                    <div class="col-12 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Simpan</button>
                        <a href="<?= base_url('legal/pks') ?>" class="btn btn-light">Batal</a>
                    </div>

                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
