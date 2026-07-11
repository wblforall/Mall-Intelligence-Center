<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php $isEdit = $lease !== null; ?>
<div class="container-fluid py-4">
    <div class="mb-3">
        <nav aria-label="breadcrumb" class="d-none d-md-block"><ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="<?= base_url('legal') ?>">Legal</a></li>
            <li class="breadcrumb-item"><a href="<?= base_url('legal/leases') ?>">Perjanjian Sewa</a></li>
            <li class="breadcrumb-item active"><?= $title ?></li>
        </ol></nav>
        <h4 class="fw-bold mb-0"><?= esc($title) ?></h4>
    </div>
    <div class="card" style="max-width:720px"><div class="card-body">
        <form method="post" action="<?= $isEdit ? base_url('legal/leases/'.$lease['id'].'/edit') : base_url('legal/leases') ?>">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Nama Tenant <span class="text-danger">*</span></label>
                    <input type="text" name="tenant_name" class="form-control" value="<?= esc($lease['tenant_name'] ?? '') ?>" required></div>
                <div class="col-md-6"><label class="form-label">Nomor Kontrak <span class="text-danger">*</span></label>
                    <input type="text" name="nomor_kontrak" class="form-control" value="<?= esc($lease['nomor_kontrak'] ?? '') ?>" required></div>
                <div class="col-md-4"><label class="form-label">Nomor Unit</label>
                    <input type="text" name="unit_no" class="form-control" value="<?= esc($lease['unit_no'] ?? '') ?>" placeholder="A-01"></div>
                <div class="col-md-4"><label class="form-label">Jenis Sewa</label>
                    <select name="jenis_sewa" class="form-select">
                        <?php foreach (['retail'=>'Retail','fnb'=>'F&B','anchor'=>'Anchor','kiosk'=>'Kiosk','atm'=>'ATM','lainnya'=>'Lainnya'] as $v=>$l): ?>
                        <option value="<?= $v ?>" <?= ($lease['jenis_sewa'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select></div>
                <div class="col-md-4"><label class="form-label">Mall <span class="text-danger">*</span></label>
                    <select name="mall_id" class="form-select" required>
                        <option value="1" <?= ($lease['mall_id'] ?? '') == 1 ? 'selected' : '' ?>>eWalk</option>
                        <option value="2" <?= ($lease['mall_id'] ?? '') == 2 ? 'selected' : '' ?>>Pentacity</option>
                    </select></div>
                <div class="col-md-3"><label class="form-label">Tanggal Mulai <span class="text-danger">*</span></label>
                    <input type="date" name="tanggal_mulai" class="form-control" value="<?= $lease['tanggal_mulai'] ?? '' ?>" required></div>
                <div class="col-md-3"><label class="form-label">Tanggal Berakhir <span class="text-danger">*</span></label>
                    <input type="date" name="tanggal_berakhir" class="form-control" value="<?= $lease['tanggal_berakhir'] ?? '' ?>" required></div>
                <div class="col-md-3"><label class="form-label">Nilai Sewa (Rp)</label>
                    <input type="text" name="nilai_sewa" class="form-control" value="<?= $lease['nilai_sewa'] ?? '' ?>"></div>
                <div class="col-md-3"><label class="form-label">Periode Pembayaran</label>
                    <select name="periode_pembayaran" class="form-select">
                        <option value="">—</option>
                        <?php foreach (['bulanan'=>'Bulanan','triwulan'=>'Triwulan','tahunan'=>'Tahunan'] as $v=>$l): ?>
                        <option value="<?= $v ?>" <?= ($lease['periode_pembayaran'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select></div>
                <div class="col-md-3"><label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <?php foreach (['draft'=>'Draft','active'=>'Aktif','expired'=>'Expired','terminated'=>'Diakhiri'] as $v=>$l): ?>
                        <option value="<?= $v ?>" <?= ($lease['status'] ?? 'draft') === $v ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select></div>
                <div class="col-12"><label class="form-label">Catatan</label>
                    <textarea name="catatan" class="form-control" rows="2"><?= esc($lease['catatan'] ?? '') ?></textarea></div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                    <a href="<?= base_url('legal/leases') ?>" class="btn btn-light">Batal</a>
                </div>
            </div>
        </form>
    </div></div>
</div>
<?= $this->endSection() ?>
