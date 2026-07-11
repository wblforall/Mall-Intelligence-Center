<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php $isEdit = $row !== null; ?>

<div class="container-fluid py-4">
    <div class="mb-3">
        <nav aria-label="breadcrumb" class="d-none d-md-block"><ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="<?= base_url('legal') ?>">Legal</a></li>
            <li class="breadcrumb-item"><a href="<?= base_url('legal/psm-developer') ?>">PSM Developer</a></li>
            <li class="breadcrumb-item active"><?= esc($title) ?></li>
        </ol></nav>
        <h4 class="fw-bold mb-0"><?= esc($title) ?></h4>
    </div>

    <?php if (session()->getFlashdata('error')): ?><div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div><?php endif; ?>

    <div class="card" style="max-width:720px">
        <div class="card-body">
            <form method="post" action="<?= $isEdit ? base_url('legal/psm-developer/'.$row['id'].'/edit') : base_url('legal/psm-developer') ?>">
                <?= csrf_field() ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nomor PSM <span class="text-danger">*</span></label>
                        <input type="text" name="nomor_psm" class="form-control" value="<?= esc($row['nomor_psm'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nama Developer <span class="text-danger">*</span></label>
                        <input type="text" name="nama_developer" class="form-control" value="<?= esc($row['nama_developer'] ?? '') ?>" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Objek Perjanjian <span class="text-danger">*</span></label>
                        <input type="text" name="objek_perjanjian" class="form-control" value="<?= esc($row['objek_perjanjian'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nilai (Rp)</label>
                        <input type="text" name="nilai" class="form-control" value="<?= esc($row['nilai'] ?? '') ?>" placeholder="0">
                    </div>
                    <div class="col-md-6">
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
                        <label class="form-label">Tanggal Berakhir <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_berakhir" class="form-control" value="<?= esc($row['tanggal_berakhir'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <?php foreach (['draft'=>'Draft','active'=>'Aktif','expired'=>'Expired','terminated'=>'Diakhiri'] as $v => $l): ?>
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
                        <a href="<?= base_url('legal/psm-developer') ?>" class="btn btn-light">Batal</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
