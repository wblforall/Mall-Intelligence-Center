<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php $isEdit = isset($row) && $row !== null; ?>

<div class="container-fluid py-4">
    <div class="mb-3">
        <nav aria-label="breadcrumb" class="d-none d-md-block"><ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="<?= base_url('legal') ?>">Legal</a></li>
            <li class="breadcrumb-item"><a href="<?= base_url('legal/psm-gudang') ?>">PSM Gudang</a></li>
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
            <form method="post" action="<?= $isEdit ? base_url('legal/psm-gudang/' . $row['id'] . '/edit') : base_url('legal/psm-gudang') ?>">
                <?= csrf_field() ?>
                <div class="row g-3">

                    <div class="col-md-6">
                        <label class="form-label">Nomor PSM <span class="text-danger">*</span></label>
                        <input type="text" name="nomor_psm" class="form-control" value="<?= esc($row['nomor_psm'] ?? '') ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Nama Penyewa <span class="text-danger">*</span></label>
                        <input type="text" name="nama_penyewa" class="form-control" value="<?= esc($row['nama_penyewa'] ?? '') ?>" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Lokasi Gudang <span class="text-danger">*</span></label>
                        <input type="text" name="lokasi_gudang" class="form-control" value="<?= esc($row['lokasi_gudang'] ?? '') ?>" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Luas (m²)</label>
                        <input type="number" name="luas_m2" class="form-control" step="0.01" min="0" value="<?= esc($row['luas_m2'] ?? '') ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Nilai Sewa (Rp)</label>
                        <input type="text" name="nilai_sewa" class="form-control" value="<?= esc($row['nilai_sewa'] ?? '') ?>" placeholder="0">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Periode Pembayaran</label>
                        <select name="periode_pembayaran" class="form-select">
                            <option value="">— Pilih —</option>
                            <?php foreach (['bulanan' => 'Bulanan', 'triwulan' => 'Triwulan', 'tahunan' => 'Tahunan'] as $v => $l): ?>
                            <option value="<?= $v ?>" <?= ($row['periode_pembayaran'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
                            <?php endforeach; ?>
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
                            <?php foreach (['draft' => 'Draft', 'active' => 'Aktif', 'expired' => 'Expired', 'terminated' => 'Diakhiri'] as $v => $l): ?>
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
                        <a href="<?= $isEdit ? base_url('legal/psm-gudang/' . $row['id']) : base_url('legal/psm-gudang') ?>" class="btn btn-light">Batal</a>
                    </div>

                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
