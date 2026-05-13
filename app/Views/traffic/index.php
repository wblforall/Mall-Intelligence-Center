<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<style>
.card { overflow: hidden; }
@keyframes countUp {
    from { opacity: 0; transform: scale(.85); }
    to   { opacity: 1; transform: scale(1); }
}
.traffic-num { display: inline-block; animation: countUp .35s ease forwards; }
.list-group-item { transition: background .15s; }
</style>
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3 fade-up" style="animation-delay:.05s">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-person-walking me-2"></i>Daily Traffic</h4>
        <small class="text-muted">Input traffic pengunjung harian per pintu masuk</small>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#exportModal">
            <i class="bi bi-file-earmark-excel me-1"></i>Export Excel
        </button>
        <?php if ($canEdit): ?>
        <a href="<?= base_url('traffic/input/ewalk/'.date('Y-m-d')) ?>" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Input eWalk
        </a>
        <a href="<?= base_url('traffic/input/pentacity/'.date('Y-m-d')) ?>" class="btn btn-sm btn-success">
            <i class="bi bi-plus-lg me-1"></i> Input Pentacity
        </a>
        <?php endif; ?>
    </div>
</div>

<form method="GET" class="mb-3 fade-up" style="animation-delay:.1s">
    <div class="d-flex align-items-center gap-2">
        <label class="form-label mb-0 small fw-semibold text-muted">Bulan</label>
        <input type="month" name="bulan" class="form-control form-control-sm" style="width:160px"
               value="<?= esc($month) ?>" onchange="this.form.submit()">
    </div>
</form>

<div class="row g-3">

<!-- eWalk -->
<div class="col-lg-6 fade-up" style="animation-delay:.18s">
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
<?php foreach ($ewalkDates as $i => $row): ?>
<div class="list-group-item d-flex justify-content-between align-items-center py-2 fade-up"
     style="animation-delay:<?= .22 + $i * .05 ?>s">
    <div>
        <span class="fw-medium small"><?= date('d M Y', strtotime($row['tanggal'])) ?></span>
        <span class="text-muted small ms-2"><?= date('l', strtotime($row['tanggal'])) ?></span>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="fw-semibold small text-primary traffic-num"><?= number_format((int)$row['total']) ?></span>
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
<div class="col-lg-6 fade-up" style="animation-delay:.28s">
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
<?php foreach ($pentaDates as $i => $row): ?>
<div class="list-group-item d-flex justify-content-between align-items-center py-2 fade-up"
     style="animation-delay:<?= .32 + $i * .05 ?>s">
    <div>
        <span class="fw-medium small"><?= date('d M Y', strtotime($row['tanggal'])) ?></span>
        <span class="text-muted small ms-2"><?= date('l', strtotime($row['tanggal'])) ?></span>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="fw-semibold small text-success traffic-num"><?= number_format((int)$row['total']) ?></span>
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

<!-- Export Modal -->
<div class="modal fade" id="exportModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-semibold"><i class="bi bi-file-earmark-excel me-2 text-success"></i>Export Traffic ke Excel</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="GET" action="<?= base_url('traffic/export') ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Dari Tanggal</label>
                        <input type="date" name="from" class="form-control form-control-sm"
                               value="<?= $month ?>-01">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Sampai Tanggal</label>
                        <input type="date" name="to" class="form-control form-control-sm"
                               value="<?= $month ?>-<?= date('t', strtotime($month . '-01')) ?>">
                    </div>
                    <div class="mb-1">
                        <label class="form-label small fw-semibold">Mall</label>
                        <select name="mall" class="form-select form-select-sm">
                            <option value="">Semua Mall</option>
                            <option value="ewalk">eWalk</option>
                            <option value="pentacity">Pentacity</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-success">
                        <i class="bi bi-download me-1"></i>Download
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
