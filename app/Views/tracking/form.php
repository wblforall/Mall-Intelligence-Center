<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
use App\Libraries\SectionConfig;
$isEdit     = $row !== null;
$sectionType = $sectionType ?? 'all';
$showCard   = fn(string $card) => SectionConfig::showTrackingCard($sectionType, $card);
$actionUrl  = $isEdit
    ? base_url('events/'.$event['id'].'/tracking/'.$row['id'].'/edit')
    : base_url('events/'.$event['id'].'/tracking/add');
?>

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= base_url('events/'.$event['id'].'/tracking') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h4 class="fw-bold mb-0"><?= $isEdit ? 'Edit Data Hari '.$row['day_number'] : 'Input Data Harian' ?></h4>
        <small class="text-muted"><?= esc($event['name']) ?></small>
    </div>
    <?php if ($sectionType !== 'all'): ?>
    <span class="badge bg-info-subtle text-info ms-1">
        <i class="bi bi-funnel me-1"></i><?= SectionConfig::SECTION_LABELS[$sectionType] ?>
    </span>
    <?php endif; ?>
</div>

<form method="POST" action="<?= $actionUrl ?>">
<?= csrf_field() ?>

<?php if (! $isEdit): ?>
<div class="card mb-3">
<div class="card-body">
<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label fw-semibold">Hari Ke- <span class="text-danger">*</span></label>
        <select name="day_number" class="form-select" id="daySelect" required>
            <?php foreach ($availableDays as $d): ?>
            <option value="<?= $d['number'] ?>" data-date="<?= $d['date'] ?>">
                Hari <?= $d['number'] ?> (<?= date('d M Y', strtotime($d['date'])) ?>)
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label fw-semibold">Tanggal</label>
        <input type="date" name="tracking_date" class="form-control" id="trackingDate" required>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label fw-semibold">Day Type</label>
        <select name="day_type" class="form-select" id="dayType">
            <option value="Weekday">Weekday</option>
            <option value="Weekend/High Season">Weekend / High Season</option>
        </select>
    </div>
</div>
</div>
</div>
<?php else: ?>
<input type="hidden" name="tracking_date" value="<?= $row['tracking_date'] ?>">
<input type="hidden" name="day_number" value="<?= $row['day_number'] ?>">
<div class="card mb-3">
<div class="card-body">
    <div class="row align-items-center">
        <div class="col-md-6">
            <strong>Hari <?= $row['day_number'] ?></strong> — <?= date('d M Y', strtotime($row['tracking_date'])) ?>
        </div>
        <div class="col-md-3">
            <select name="day_type" class="form-select">
                <option value="Weekday" <?= $row['day_type'] === 'Weekday' ? 'selected' : '' ?>>Weekday</option>
                <option value="Weekend/High Season" <?= $row['day_type'] !== 'Weekday' ? 'selected' : '' ?>>Weekend</option>
            </select>
        </div>
    </div>
</div>
</div>
<?php endif; ?>

<div class="row g-3">

<?php if ($showCard('traffic')): ?>
<!-- Traffic -->
<div class="col-md-6">
<div class="card">
<div class="card-header bg-primary bg-opacity-10"><h6 class="mb-0 text-primary fw-semibold"><i class="bi bi-people me-2"></i>Traffic</h6></div>
<div class="card-body">
    <div class="row">
        <div class="col-6 mb-3">
            <label class="form-label small fw-semibold">Actual Traffic</label>
            <input type="number" name="actual_traffic" class="form-control" value="<?= $isEdit ? $row['actual_traffic'] : '' ?>" placeholder="Isi jika tersedia">
        </div>
        <div class="col-6 mb-3">
            <label class="form-label small fw-semibold">Event Area Visitors</label>
            <input type="number" name="event_area_visitors" class="form-control" value="<?= $isEdit ? $row['event_area_visitors'] : '' ?>" placeholder="Estimasi area event">
        </div>
    </div>
</div>
</div>
</div>
<?php else: ?>
<input type="hidden" name="actual_traffic" value="<?= $isEdit ? $row['actual_traffic'] : '' ?>">
<input type="hidden" name="event_area_visitors" value="<?= $isEdit ? $row['event_area_visitors'] : '' ?>">
<?php endif; ?>

<?php if ($showCard('engagement')): ?>
<!-- Engagement -->
<div class="col-md-6">
<div class="card">
<div class="card-header bg-success bg-opacity-10"><h6 class="mb-0 text-success fw-semibold"><i class="bi bi-hand-thumbs-up me-2"></i>Engagement</h6></div>
<div class="card-body">
    <div class="row">
        <div class="col-4 mb-3">
            <label class="form-label small fw-semibold">M&G Registration</label>
            <input type="number" name="mg_registration" class="form-control" value="<?= $isEdit ? $row['mg_registration'] : 0 ?>" min="0">
        </div>
        <div class="col-4 mb-3">
            <label class="form-label small fw-semibold">Photo/Game</label>
            <input type="number" name="photo_game_participants" class="form-control" value="<?= $isEdit ? $row['photo_game_participants'] : 0 ?>" min="0">
        </div>
        <div class="col-4 mb-3">
            <label class="form-label small fw-semibold">QR Scans</label>
            <input type="number" name="qr_scans" class="form-control" value="<?= $isEdit ? $row['qr_scans'] : 0 ?>" min="0">
        </div>
    </div>
</div>
</div>
</div>
<?php else: ?>
<input type="hidden" name="mg_registration" value="<?= $isEdit ? $row['mg_registration'] : 0 ?>">
<input type="hidden" name="photo_game_participants" value="<?= $isEdit ? $row['photo_game_participants'] : 0 ?>">
<input type="hidden" name="qr_scans" value="<?= $isEdit ? $row['qr_scans'] : 0 ?>">
<?php endif; ?>

<?php if ($showCard('loyalty')): ?>
<!-- Loyalty & Transaction -->
<div class="col-md-6">
<div class="card">
<div class="card-header bg-warning bg-opacity-10"><h6 class="mb-0 text-warning fw-semibold"><i class="bi bi-star me-2"></i>Loyalty & Transaksi</h6></div>
<div class="card-body">
    <div class="row">
        <div class="col-6 mb-3">
            <label class="form-label small fw-semibold">New PAM Plus Members</label>
            <input type="number" name="new_pam_members" class="form-control" value="<?= $isEdit ? $row['new_pam_members'] : 0 ?>" min="0">
        </div>
        <div class="col-6 mb-3">
            <label class="form-label small fw-semibold">Receipt Uploads</label>
            <input type="number" name="receipt_uploads" class="form-control" value="<?= $isEdit ? $row['receipt_uploads'] : 0 ?>" min="0">
        </div>
        <div class="col-6 mb-3">
            <label class="form-label small fw-semibold">Voucher Claims</label>
            <input type="number" name="voucher_claims" class="form-control" value="<?= $isEdit ? $row['voucher_claims'] : 0 ?>" min="0">
        </div>
        <div class="col-6 mb-3">
            <label class="form-label small fw-semibold">Voucher Redemptions</label>
            <input type="number" name="voucher_redemptions" class="form-control" value="<?= $isEdit ? $row['voucher_redemptions'] : 0 ?>" min="0">
        </div>
        <div class="col-12">
            <label class="form-label small fw-semibold">Actual Tenant Sales (Rp)</label>
            <div class="input-group">
                <span class="input-group-text small">Rp</span>
                <input type="text" name="actual_tenant_sales" class="form-control currency-input"
                       value="<?= $isEdit && $row['actual_tenant_sales'] !== null ? number_format((int)$row['actual_tenant_sales'],0,',','.') : '' ?>" placeholder="Opsional">
            </div>
        </div>
    </div>
</div>
</div>
</div>
<?php else: ?>
<input type="hidden" name="new_pam_members" value="<?= $isEdit ? $row['new_pam_members'] : 0 ?>">
<input type="hidden" name="receipt_uploads" value="<?= $isEdit ? $row['receipt_uploads'] : 0 ?>">
<input type="hidden" name="voucher_claims" value="<?= $isEdit ? $row['voucher_claims'] : 0 ?>">
<input type="hidden" name="voucher_redemptions" value="<?= $isEdit ? $row['voucher_redemptions'] : 0 ?>">
<input type="hidden" name="actual_tenant_sales" value="<?= $isEdit ? $row['actual_tenant_sales'] : '' ?>">
<?php endif; ?>

<?php if ($showCard('commercial')): ?>
<!-- Revenue -->
<div class="col-md-6">
<div class="card">
<div class="card-header bg-info bg-opacity-10"><h6 class="mb-0 text-info fw-semibold"><i class="bi bi-wallet2 me-2"></i>Revenue</h6></div>
<div class="card-body">
    <div class="row">
        <div class="col-6 mb-3">
            <label class="form-label small fw-semibold">Sponsor Revenue (Rp)</label>
            <input type="text" name="sponsor_revenue" class="form-control currency-input" value="<?= $isEdit ? number_format((int)$row['sponsor_revenue'],0,',','.') : '0' ?>">
        </div>
        <div class="col-6 mb-3">
            <label class="form-label small fw-semibold">Booth / CL Revenue (Rp)</label>
            <input type="text" name="booth_cl_revenue" class="form-control currency-input" value="<?= $isEdit ? number_format((int)$row['booth_cl_revenue'],0,',','.') : '0' ?>">
        </div>
        <div class="col-6 mb-3">
            <label class="form-label small fw-semibold">Media Revenue (Rp)</label>
            <input type="text" name="media_revenue" class="form-control currency-input" value="<?= $isEdit ? number_format((int)$row['media_revenue'],0,',','.') : '0' ?>">
        </div>
        <div class="col-6 mb-3">
            <label class="form-label small fw-semibold">Parking Actual (Rp)</label>
            <input type="text" name="parking_actual" class="form-control currency-input"
                   value="<?= $isEdit && $row['parking_actual'] !== null ? number_format((int)$row['parking_actual'],0,',','.') : '' ?>" placeholder="Opsional">
        </div>
    </div>
</div>
</div>
</div>
<?php else: ?>
<input type="hidden" name="sponsor_revenue" value="<?= $isEdit ? $row['sponsor_revenue'] : 0 ?>">
<input type="hidden" name="booth_cl_revenue" value="<?= $isEdit ? $row['booth_cl_revenue'] : 0 ?>">
<input type="hidden" name="media_revenue" value="<?= $isEdit ? $row['media_revenue'] : 0 ?>">
<input type="hidden" name="parking_actual" value="<?= $isEdit ? $row['parking_actual'] : '' ?>">
<?php endif; ?>

<!-- Notes always visible -->
<div class="col-12">
<div class="card">
<div class="card-body">
    <label class="form-label fw-semibold">Catatan</label>
    <textarea name="notes" class="form-control" rows="2" placeholder="Catatan opsional untuk hari ini..."><?= $isEdit ? esc($row['notes']) : '' ?></textarea>
</div>
</div>
</div>

</div>

<div class="mt-4">
    <button type="submit" class="btn btn-primary">
        <i class="bi bi-check-lg me-1"></i> <?= $isEdit ? 'Perbarui Data' : 'Simpan Data Hari Ini' ?>
    </button>
    <a href="<?= base_url('events/'.$event['id'].'/tracking') ?>" class="btn btn-outline-secondary ms-2">Batal</a>
</div>
</form>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
<?php if (! $isEdit): ?>
document.getElementById('daySelect').addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    document.getElementById('trackingDate').value = opt.dataset.date;
});
if (document.getElementById('daySelect').options.length > 0) {
    const opt = document.getElementById('daySelect').options[0];
    document.getElementById('trackingDate').value = opt.dataset.date;
}
<?php endif; ?>

document.querySelectorAll('.currency-input').forEach(inp => {
    inp.addEventListener('input', function() {
        let n = parseInt(this.value.replace(/[^0-9]/g, '')) || 0;
        this.value = n.toLocaleString('id-ID');
    });
});
</script>
<?= $this->endSection() ?>
