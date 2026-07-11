<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php $isEdit = $permit !== null; ?>

<div class="container-fluid py-4">
    <div class="mb-3">
        <nav aria-label="breadcrumb" class="d-none d-md-block"><ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="<?= base_url('legal') ?>">Legal</a></li>
            <li class="breadcrumb-item"><a href="<?= base_url('legal/permits') ?>">Perizinan</a></li>
            <li class="breadcrumb-item active"><?= $title ?></li>
        </ol></nav>
        <h4 class="fw-bold mb-0"><?= esc($title) ?></h4>
    </div>

    <div class="card" style="max-width:720px">
        <div class="card-body">
            <form method="post" action="<?= $isEdit ? base_url('legal/permits/'.$permit['id'].'/edit') : base_url('legal/permits') ?>">
                <?= csrf_field() ?>
                <div class="row g-3">

                    <div class="col-md-6">
                        <label class="form-label">Nama Izin <span class="text-danger">*</span></label>
                        <input type="text" name="nama_izin" class="form-control" value="<?= esc($permit['nama_izin'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nomor Izin <span class="text-danger">*</span></label>
                        <input type="text" name="nomor_izin" class="form-control" value="<?= esc($permit['nomor_izin'] ?? '') ?>" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Jenis Izin</label>
                        <select name="jenis_izin" class="form-select">
                            <?php foreach (['IMB','SLF','HO_SITU','SIUP','TDP','Amdal','K3','lainnya'] as $j): ?>
                            <option value="<?= $j ?>" <?= ($permit['jenis_izin'] ?? '') === $j ? 'selected' : '' ?>><?= $j === 'HO_SITU' ? 'HO/SITU' : $j ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Mall</label>
                        <select name="mall_id" class="form-select" required>
                            <option value="1" <?= ($permit['mall_id'] ?? '') == 1 ? 'selected' : '' ?>>eWalk</option>
                            <option value="2" <?= ($permit['mall_id'] ?? '') == 2 ? 'selected' : '' ?>>Pentacity</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <?php foreach (['active'=>'Aktif','expired'=>'Expired','pending_renewal'=>'Pending Renewal','revoked'=>'Dicabut'] as $v=>$l): ?>
                            <option value="<?= $v ?>" <?= ($permit['status'] ?? 'active') === $v ? 'selected' : '' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Instansi Penerbit</label>
                        <input type="text" name="instansi_penerbit" class="form-control" value="<?= esc($permit['instansi_penerbit'] ?? '') ?>" placeholder="Dinas Penanaman Modal, dll.">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tanggal Terbit <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_terbit" class="form-control" value="<?= $permit['tanggal_terbit'] ?? '' ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Berlaku Sampai <span class="text-muted small">(kosong = tetap)</span></label>
                        <input type="date" name="tanggal_berakhir" class="form-control" value="<?= $permit['tanggal_berakhir'] ?? '' ?>">
                    </div>

                    <div class="col-12">
                        <label class="form-label">Catatan</label>
                        <textarea name="catatan" class="form-control" rows="3"><?= esc($permit['catatan'] ?? '') ?></textarea>
                    </div>

                    <div class="col-12 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Simpan</button>
                        <a href="<?= base_url('legal/permits') ?>" class="btn btn-light">Batal</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
