<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php $isEdit = $row !== null; ?>

<div class="container-fluid py-4">
    <div class="mb-3">
        <nav aria-label="breadcrumb"><ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="<?= base_url('legal') ?>">Legal</a></li>
            <li class="breadcrumb-item"><a href="<?= base_url('legal/spk') ?>">Review SPK</a></li>
            <li class="breadcrumb-item active"><?= $isEdit ? 'Edit SPK' : 'Tambah SPK' ?></li>
        </ol></nav>
        <h4 class="fw-bold mb-0"><?= $isEdit ? 'Edit SPK' : 'Tambah SPK' ?></h4>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
    <?php endif; ?>

    <div class="card" style="max-width:720px">
        <div class="card-body">
            <form method="post" action="<?= $isEdit ? base_url('legal/spk/' . $row['id'] . '/edit') : base_url('legal/spk') ?>">
                <?= csrf_field() ?>
                <div class="row g-3">

                    <div class="col-md-6">
                        <label class="form-label">Nomor SPK <span class="text-danger">*</span></label>
                        <input type="text" name="nomor_spk" class="form-control"
                               value="<?= esc($row['nomor_spk'] ?? '') ?>" required
                               placeholder="Contoh: SPK/IT/2026/001">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nama Vendor <span class="text-danger">*</span></label>
                        <input type="text" name="nama_vendor" class="form-control"
                               value="<?= esc($row['nama_vendor'] ?? '') ?>" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Deskripsi Pekerjaan</label>
                        <textarea name="deskripsi_pekerjaan" class="form-control" rows="3"
                                  placeholder="Uraikan lingkup pekerjaan..."><?= esc($row['deskripsi_pekerjaan'] ?? '') ?></textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Nilai SPK</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" name="nilai_spk" class="form-control"
                                   value="<?= esc($row['nilai_spk'] ?? '') ?>"
                                   placeholder="0"
                                   inputmode="numeric">
                        </div>
                        <div class="form-text">Masukkan angka tanpa titik/koma.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">PIC</label>
                        <select name="pic_user_id" class="form-select">
                            <option value="">— Pilih PIC —</option>
                            <?php foreach ($users as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= ($row['pic_user_id'] ?? '') == $u['id'] ? 'selected' : '' ?>>
                                <?= esc($u['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Tanggal Terbit <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_terbit" class="form-control"
                               value="<?= $row['tanggal_terbit'] ?? '' ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tanggal Selesai <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_selesai" class="form-control"
                               value="<?= $row['tanggal_selesai'] ?? '' ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="draft"   <?= ($row['status'] ?? 'draft') === 'draft'   ? 'selected' : '' ?>>Draft</option>
                            <option value="aktif"   <?= ($row['status'] ?? '')       === 'aktif'   ? 'selected' : '' ?>>Aktif</option>
                            <option value="selesai" <?= ($row['status'] ?? '')       === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                            <option value="batal"   <?= ($row['status'] ?? '')       === 'batal'   ? 'selected' : '' ?>>Batal</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Catatan</label>
                        <textarea name="catatan" class="form-control" rows="2"><?= esc($row['catatan'] ?? '') ?></textarea>
                    </div>

                    <div class="col-12 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Simpan</button>
                        <a href="<?= base_url('legal/spk') ?>" class="btn btn-light">Batal</a>
                    </div>

                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
