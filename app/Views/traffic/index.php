<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-person-walking me-2"></i>Daily Traffic</h4>
        <small class="text-muted">Input traffic pengunjung harian per pintu masuk</small>
    </div>
    <?php if ($canEdit): ?>
    <div class="d-flex gap-2">
        <a href="<?= base_url('traffic/input/ewalk/'.date('Y-m-d')) ?>" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Input Traffic eWalk
        </a>
        <a href="<?= base_url('traffic/input/pentacity/'.date('Y-m-d')) ?>" class="btn btn-sm btn-success">
            <i class="bi bi-plus-lg me-1"></i> Input Traffic Pentacity
        </a>
    </div>
    <?php endif; ?>
</div>

<form method="GET" class="mb-3">
    <div class="d-flex align-items-center gap-2">
        <label class="form-label mb-0 small fw-semibold text-muted">Bulan</label>
        <input type="month" name="bulan" class="form-control form-control-sm" style="width:160px"
               value="<?= esc($month) ?>" onchange="this.form.submit()">
    </div>
</form>

<div class="row g-3">

<!-- eWalk -->
<div class="col-lg-6">
<div class="card">
<div class="card-header d-flex justify-content-between align-items-center">
    <h6 class="mb-0 fw-semibold"><i class="bi bi-building me-2 text-primary"></i>eWalk</h6>
    <span class="badge bg-primary-subtle text-primary"><?= count($ewalkDates) ?> hari</span>
</div>
<div class="card-body p-0">
<?php if (empty($ewalkDates)): ?>
<div class="text-center py-4 text-muted small">Belum ada data traffic eWalk.</div>
<?php else: ?>
<div class="list-group list-group-flush">
<?php foreach ($ewalkDates as $row): ?>
<div class="list-group-item d-flex justify-content-between align-items-center py-2">
    <div>
        <span class="fw-medium small"><?= date('d M Y', strtotime($row['tanggal'])) ?></span>
        <span class="text-muted small ms-2"><?= date('l', strtotime($row['tanggal'])) ?></span>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="fw-semibold small text-primary"><?= number_format((int)$row['total']) ?></span>
        <?php if ($canEdit): ?>
        <a href="<?= base_url('traffic/input/ewalk/'.$row['tanggal']) ?>" class="btn btn-xs btn-outline-primary" style="padding:.2rem .5rem;font-size:.75rem">
            <i class="bi bi-pencil"></i>
        </a>
        <a href="<?= base_url('traffic/delete/ewalk/'.$row['tanggal']) ?>"
           class="btn btn-xs btn-outline-danger" style="padding:.2rem .5rem;font-size:.75rem"
           onclick="return confirm('Hapus data traffic eWalk <?= $row['tanggal'] ?>?')">
            <i class="bi bi-trash"></i>
        </a>
        <?php else: ?>
        <span class="badge bg-success-subtle text-success"><i class="bi bi-check2"></i> Tersimpan</span>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>
</div>
</div>

<!-- Pentacity -->
<div class="col-lg-6">
<div class="card">
<div class="card-header d-flex justify-content-between align-items-center">
    <h6 class="mb-0 fw-semibold"><i class="bi bi-building me-2 text-success"></i>Pentacity</h6>
    <span class="badge bg-success-subtle text-success"><?= count($pentaDates) ?> hari</span>
</div>
<div class="card-body p-0">
<?php if (empty($pentaDates)): ?>
<div class="text-center py-4 text-muted small">Belum ada data traffic Pentacity.</div>
<?php else: ?>
<div class="list-group list-group-flush">
<?php foreach ($pentaDates as $row): ?>
<div class="list-group-item d-flex justify-content-between align-items-center py-2">
    <div>
        <span class="fw-medium small"><?= date('d M Y', strtotime($row['tanggal'])) ?></span>
        <span class="text-muted small ms-2"><?= date('l', strtotime($row['tanggal'])) ?></span>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="fw-semibold small text-success"><?= number_format((int)$row['total']) ?></span>
        <?php if ($canEdit): ?>
        <a href="<?= base_url('traffic/input/pentacity/'.$row['tanggal']) ?>" class="btn btn-xs btn-outline-success" style="padding:.2rem .5rem;font-size:.75rem">
            <i class="bi bi-pencil"></i>
        </a>
        <a href="<?= base_url('traffic/delete/pentacity/'.$row['tanggal']) ?>"
           class="btn btn-xs btn-outline-danger" style="padding:.2rem .5rem;font-size:.75rem"
           onclick="return confirm('Hapus data traffic Pentacity <?= $row['tanggal'] ?>?')">
            <i class="bi bi-trash"></i>
        </a>
        <?php else: ?>
        <span class="badge bg-success-subtle text-success"><i class="bi bi-check2"></i> Tersimpan</span>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>
</div>
</div>

</div>

<?= $this->endSection() ?>
