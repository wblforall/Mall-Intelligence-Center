<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php $isEdit = isset($row) && $row !== null; ?>

<div class="container-fluid py-4">
    <div class="mb-3">
        <nav aria-label="breadcrumb" class="d-none d-md-block"><ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="<?= base_url('legal') ?>">Legal</a></li>
            <li class="breadcrumb-item"><a href="<?= base_url('legal/kontrak-pameran') ?>">Kontrak Sewa Pameran</a></li>
            <li class="breadcrumb-item active"><?= esc($title) ?></li>
        </ol></nav>
        <h4 class="fw-bold mb-0"><?= esc($title) ?></h4>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" style="max-width:720px">
        <?= session()->getFlashdata('error') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="card" style="max-width:720px">
        <div class="card-body">
            <form method="post" action="<?= $isEdit ? base_url('legal/kontrak-pameran/' . $row['id'] . '/edit') : base_url('legal/kontrak-pameran') ?>">
                <?= csrf_field() ?>
                <div class="row g-3">

                    <div class="col-md-6">
                        <label class="form-label">Nomor Kontrak <span class="text-danger">*</span></label>
                        <input type="text" name="nomor_kontrak" class="form-control" value="<?= esc($row['nomor_kontrak'] ?? '') ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Nama Penyelenggara <span class="text-danger">*</span></label>
                        <input type="text" name="nama_penyelenggara" class="form-control" value="<?= esc($row['nama_penyelenggara'] ?? '') ?>" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Nama Event <span class="text-danger">*</span></label>
                        <input type="text" name="nama_event" class="form-control" value="<?= esc($row['nama_event'] ?? '') ?>" required>
                    </div>

                    <div class="col-md-8">
                        <label class="form-label">Lokasi / Area</label>
                        <input type="text" name="lokasi_area" class="form-control" value="<?= esc($row['lokasi_area'] ?? '') ?>" placeholder="Atrium Lt.1">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Mall</label>
                        <select name="mall_id" class="form-select">
                            <option value="">Keduanya</option>
                            <option value="1" <?= ($row['mall_id'] ?? '') == 1 ? 'selected' : '' ?>>eWalk</option>
                            <option value="2" <?= ($row['mall_id'] ?? '') == 2 ? 'selected' : '' ?>>Pentacity</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Tanggal Mulai <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_mulai" class="form-control" value="<?= esc($row['tanggal_mulai'] ?? '') ?>" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Tanggal Selesai <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_selesai" class="form-control" value="<?= esc($row['tanggal_selesai'] ?? '') ?>" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Nilai Sewa (Rp)</label>
                        <input type="text" name="nilai_sewa" class="form-control" value="<?= esc($row['nilai_sewa'] ?? '') ?>" placeholder="0">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <?php foreach (['draft' => 'Draft', 'aktif' => 'Aktif', 'selesai' => 'Selesai', 'batal' => 'Batal'] as $v => $l): ?>
                            <option value="<?= $v ?>" <?= ($row['status'] ?? 'draft') === $v ? 'selected' : '' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Catatan</label>
                        <textarea name="catatan" class="form-control" rows="2"><?= esc($row['catatan'] ?? '') ?></textarea>
                    </div>

                    <div class="col-12 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Simpan</button>
                        <a href="<?= $isEdit ? base_url('legal/kontrak-pameran/' . $row['id']) : base_url('legal/kontrak-pameran') ?>" class="btn btn-light">Batal</a>
                    </div>

                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
