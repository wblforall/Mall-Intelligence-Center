<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="<?= base_url('traffic') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h4 class="fw-bold mb-0">Input Kendaraan</h4>
        <small class="text-muted"><?= date('l, d M Y', strtotime($tanggal)) ?></small>
    </div>
</div>

<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <?= session()->getFlashdata('success') ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<form method="POST" action="<?= base_url('traffic/vehicles/save') ?>">
<?= csrf_field() ?>
<input type="hidden" name="tanggal" value="<?= esc($tanggal) ?>">

<div class="card mb-3">
<div class="card-header py-2">
    <h6 class="mb-0 fw-semibold small"><i class="bi bi-car-front me-1"></i>Data Kendaraan</h6>
</div>
<div class="card-body">

    <div class="row g-3 align-items-end mb-3">
        <div class="col-auto">
            <label class="form-label small fw-semibold mb-1">Tanggal</label>
            <div class="d-flex gap-1">
                <input type="date" id="datePicker" class="form-control form-control-sm" value="<?= $tanggal ?>">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="changeDate">Ganti</button>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-6 col-sm-4 col-md-2">
            <label class="form-label small fw-semibold mb-1">Mobil</label>
            <input type="number" name="total_mobil" class="form-control"
                   value="<?= $vehicleRow ? (int)$vehicleRow['total_mobil'] : 0 ?>" min="0">
        </div>
        <div class="col-6 col-sm-4 col-md-2">
            <label class="form-label small fw-semibold mb-1">Motor</label>
            <input type="number" name="total_motor" class="form-control"
                   value="<?= $vehicleRow ? (int)$vehicleRow['total_motor'] : 0 ?>" min="0">
        </div>
        <div class="col-6 col-sm-4 col-md-2">
            <label class="form-label small fw-semibold mb-1">Box</label>
            <input type="number" name="total_mobil_box" class="form-control"
                   value="<?= $vehicleRow ? (int)($vehicleRow['total_mobil_box'] ?? 0) : 0 ?>" min="0">
        </div>
        <div class="col-6 col-sm-4 col-md-2">
            <label class="form-label small fw-semibold mb-1">Truk</label>
            <input type="number" name="total_truck" class="form-control"
                   value="<?= $vehicleRow ? (int)($vehicleRow['total_truck'] ?? 0) : 0 ?>" min="0">
        </div>
        <div class="col-6 col-sm-4 col-md-2">
            <label class="form-label small fw-semibold mb-1">Bus</label>
            <input type="number" name="total_bus" class="form-control"
                   value="<?= $vehicleRow ? (int)($vehicleRow['total_bus'] ?? 0) : 0 ?>" min="0">
        </div>
        <div class="col-6 col-sm-4 col-md-2">
            <label class="form-label small fw-semibold mb-1">Mobil Free</label>
            <input type="number" name="total_mobil_free" class="form-control"
                   value="<?= $vehicleRow ? (int)($vehicleRow['total_mobil_free'] ?? 0) : 0 ?>" min="0">
        </div>
        <div class="col-6 col-sm-4 col-md-2">
            <label class="form-label small fw-semibold mb-1">Motor Free</label>
            <input type="number" name="total_motor_free" class="form-control"
                   value="<?= $vehicleRow ? (int)($vehicleRow['total_motor_free'] ?? 0) : 0 ?>" min="0">
        </div>
    </div>

</div>
<div class="card-footer d-flex justify-content-end">
    <button type="submit" class="btn btn-primary px-4">
        <i class="bi bi-check-lg me-1"></i>Simpan
    </button>
</div>
</div>

</form>

<script>
document.getElementById('changeDate').addEventListener('click', function () {
    const d = document.getElementById('datePicker').value;
    if (d) window.location.href = '<?= base_url('traffic/vehicles/') ?>' + d;
});
document.getElementById('datePicker').addEventListener('keydown', function (e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        document.getElementById('changeDate').click();
    }
});
</script>

<?= $this->endSection() ?>
