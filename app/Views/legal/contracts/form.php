<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php $isEdit = $contract !== null; ?>

<div class="container-fluid py-4">
    <div class="mb-3">
        <nav aria-label="breadcrumb" class="d-none d-md-block"><ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="<?= base_url('legal') ?>">Legal</a></li>
            <li class="breadcrumb-item"><a href="<?= base_url('legal/contracts') ?>">Kontrak Vendor</a></li>
            <li class="breadcrumb-item active"><?= $title ?></li>
        </ol></nav>
        <h4 class="fw-bold mb-0"><?= esc($title) ?></h4>
    </div>
    <div class="card" style="max-width:720px">
        <div class="card-body">
            <form method="post" action="<?= $isEdit ? base_url('legal/contracts/'.$contract['id'].'/edit') : base_url('legal/contracts') ?>">
                <?= csrf_field() ?>
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label">Nama Vendor <span class="text-danger">*</span></label>
                        <input type="text" name="nama_vendor" class="form-control" value="<?= esc($contract['nama_vendor'] ?? '') ?>" required></div>
                    <div class="col-md-6"><label class="form-label">Nomor Kontrak <span class="text-danger">*</span></label>
                        <input type="text" name="nomor_kontrak" class="form-control" value="<?= esc($contract['nomor_kontrak'] ?? '') ?>" required></div>
                    <div class="col-md-4"><label class="form-label">Jenis Kontrak</label>
                        <select name="jenis_kontrak" class="form-select">
                            <?php foreach (['cleaning'=>'Cleaning','security'=>'Security','parkir'=>'Parkir','maintenance'=>'Maintenance','catering'=>'Catering','IT'=>'IT','marketing'=>'Marketing','lainnya'=>'Lainnya'] as $v=>$l): ?>
                            <option value="<?= $v ?>" <?= ($contract['jenis_kontrak'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select></div>
                    <div class="col-md-4"><label class="form-label">Mall</label>
                        <select name="mall_id" class="form-select">
                            <option value="">Keduanya</option>
                            <option value="1" <?= ($contract['mall_id'] ?? '') == 1 ? 'selected' : '' ?>>eWalk</option>
                            <option value="2" <?= ($contract['mall_id'] ?? '') == 2 ? 'selected' : '' ?>>Pentacity</option>
                        </select></div>
                    <div class="col-md-4"><label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <?php foreach (['draft'=>'Draft','active'=>'Aktif','expired'=>'Expired','terminated'=>'Diakhiri'] as $v=>$l): ?>
                            <option value="<?= $v ?>" <?= ($contract['status'] ?? 'draft') === $v ? 'selected' : '' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select></div>
                    <div class="col-md-4"><label class="form-label">Tanggal Mulai <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_mulai" class="form-control" value="<?= $contract['tanggal_mulai'] ?? '' ?>" required></div>
                    <div class="col-md-4"><label class="form-label">Tanggal Berakhir <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_berakhir" class="form-control" value="<?= $contract['tanggal_berakhir'] ?? '' ?>" required></div>
                    <div class="col-md-4"><label class="form-label">Nilai Kontrak (Rp)</label>
                        <input type="text" name="nilai_kontrak" class="form-control" value="<?= $contract['nilai_kontrak'] ?? '' ?>" placeholder="0"></div>
                    <div class="col-12"><label class="form-label">Lingkup Pekerjaan</label>
                        <textarea name="lingkup_pekerjaan" class="form-control" rows="3"><?= esc($contract['lingkup_pekerjaan'] ?? '') ?></textarea></div>
                    <div class="col-12"><label class="form-label">Catatan</label>
                        <textarea name="catatan" class="form-control" rows="2"><?= esc($contract['catatan'] ?? '') ?></textarea></div>
                    <div class="col-12 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Simpan</button>
                        <a href="<?= base_url('legal/contracts') ?>" class="btn btn-light">Batal</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
