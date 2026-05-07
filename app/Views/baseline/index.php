<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
use App\Libraries\SectionConfig;
$sectionType = $sectionType ?? 'all';
$canEdit     = $canEdit ?? true;
$showCol     = fn(string $col) => SectionConfig::showBaselineCol($sectionType, $col);
?>

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= base_url('events/'.$event['id'].'/dashboard') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h4 class="fw-bold mb-0">Baseline Data</h4>
        <small class="text-muted"><?= esc($event['name']) ?> &mdash; Data pembanding per hari event</small>
    </div>
    <?php if ($sectionType !== 'all'): ?>
    <span class="badge bg-info-subtle text-info ms-1">
        <i class="bi bi-funnel me-1"></i><?= SectionConfig::SECTION_LABELS[$sectionType] ?>
    </span>
    <?php endif; ?>
</div>

<div class="card">
<div class="card-header d-flex justify-content-between align-items-center">
    <h6 class="mb-0 fw-semibold"><i class="bi bi-clipboard-data me-2"></i>Baseline Per Hari Event</h6>
    <?php if ($canEdit): ?>
    <button class="btn btn-sm btn-outline-primary" id="addRowBtn">
        <i class="bi bi-plus-lg me-1"></i> Tambah Baris
    </button>
    <?php endif; ?>
</div>
<div class="card-body">
<form method="POST" action="<?= base_url('events/'.$event['id'].'/baseline') ?>">
<?= csrf_field() ?>
<div class="table-responsive">
<table class="table table-sm align-middle" id="baselineTable">
<thead>
<tr>
    <th style="width:90px">Day Label</th>
    <th>Comparable Period</th>
    <th>Day Type</th>
    <?php if ($showCol('baseline_traffic')): ?><th>Baseline Traffic</th><?php endif; ?>
    <?php if ($showCol('baseline_event_area_visitors')): ?><th>Event Area Visitors</th><?php endif; ?>
    <?php if ($showCol('baseline_transactions')): ?><th>Transactions</th><?php endif; ?>
    <?php if ($showCol('baseline_tenant_sales')): ?><th>Tenant Sales (Rp)</th><?php endif; ?>
    <?php if ($showCol('baseline_parking_revenue')): ?><th>Parking Revenue (Rp)</th><?php endif; ?>
    <?php if ($canEdit): ?><th></th><?php endif; ?>
</tr>
</thead>
<tbody id="baselineBody">
<?php
$existingMap = [];
foreach ($baselines as $b) { $existingMap[$b['day_label']] = $b; }

for ($i = 1; $i <= $event['event_days']; $i++):
    $label    = 'DAY-' . $i;
    $b        = $existingMap[$label] ?? null;
    $dayType  = $b['day_type'] ?? ($i <= 4 ? 'Weekday' : 'Weekend/High Season');
    $comparable = $b['comparable_period'] ?? 'Comparable Day ' . $i;
?>
<tr>
    <td><input type="text" name="day_label[]" class="form-control form-control-sm" value="<?= $label ?>" <?= $canEdit ? 'readonly' : 'readonly disabled' ?>></td>
    <td><input type="text" name="comparable_period[]" class="form-control form-control-sm" value="<?= esc($comparable) ?>" <?= ! $canEdit ? 'readonly' : '' ?>></td>
    <td>
        <?php if ($canEdit): ?>
        <select name="day_type[]" class="form-select form-select-sm">
            <option value="Weekday" <?= $dayType === 'Weekday' ? 'selected' : '' ?>>Weekday</option>
            <option value="Weekend/High Season" <?= $dayType === 'Weekend/High Season' ? 'selected' : '' ?>>Weekend</option>
        </select>
        <?php else: ?>
        <input type="hidden" name="day_type[]" value="<?= $dayType ?>">
        <span class="small"><?= $dayType ?></span>
        <?php endif; ?>
    </td>

    <?php if ($showCol('baseline_traffic')): ?>
    <td><input type="number" name="baseline_traffic[]" class="form-control form-control-sm" value="<?= $b['baseline_traffic'] ?? 12000 ?>" <?= ! $canEdit ? 'readonly' : '' ?>></td>
    <?php else: ?>
    <input type="hidden" name="baseline_traffic[]" value="<?= $b['baseline_traffic'] ?? 12000 ?>">
    <?php endif; ?>

    <?php if ($showCol('baseline_event_area_visitors')): ?>
    <td><input type="number" name="baseline_event_area_visitors[]" class="form-control form-control-sm" value="<?= $b['baseline_event_area_visitors'] ?? 2800 ?>" <?= ! $canEdit ? 'readonly' : '' ?>></td>
    <?php else: ?>
    <input type="hidden" name="baseline_event_area_visitors[]" value="<?= $b['baseline_event_area_visitors'] ?? 2800 ?>">
    <?php endif; ?>

    <?php if ($showCol('baseline_transactions')): ?>
    <td><input type="number" name="baseline_transactions[]" class="form-control form-control-sm" value="<?= $b['baseline_transactions'] ?? 1400 ?>" <?= ! $canEdit ? 'readonly' : '' ?>></td>
    <?php else: ?>
    <input type="hidden" name="baseline_transactions[]" value="<?= $b['baseline_transactions'] ?? 1400 ?>">
    <?php endif; ?>

    <?php if ($showCol('baseline_tenant_sales')): ?>
    <td><input type="text" name="baseline_tenant_sales[]" class="form-control form-control-sm currency-input" value="<?= number_format((int)($b['baseline_tenant_sales'] ?? 720000000), 0, ',', '.') ?>" <?= ! $canEdit ? 'readonly' : '' ?>></td>
    <?php else: ?>
    <input type="hidden" name="baseline_tenant_sales[]" value="<?= (int)($b['baseline_tenant_sales'] ?? 720000000) ?>">
    <?php endif; ?>

    <?php if ($showCol('baseline_parking_revenue')): ?>
    <td><input type="text" name="baseline_parking_revenue[]" class="form-control form-control-sm currency-input" value="<?= number_format((int)($b['baseline_parking_revenue'] ?? 70000000), 0, ',', '.') ?>" <?= ! $canEdit ? 'readonly' : '' ?>></td>
    <?php else: ?>
    <input type="hidden" name="baseline_parking_revenue[]" value="<?= (int)($b['baseline_parking_revenue'] ?? 70000000) ?>">
    <?php endif; ?>

    <?php if ($canEdit): ?>
    <td><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bi bi-trash"></i></button></td>
    <?php endif; ?>
</tr>
<?php endfor; ?>
</tbody>
</table>
</div>

<div class="mt-3">
    <?php if ($canEdit): ?>
    <button type="submit" class="btn btn-primary">
        <i class="bi bi-check-lg me-1"></i> Simpan Baseline
    </button>
    <?php endif; ?>
    <a href="<?= base_url('events/'.$event['id'].'/tracking') ?>" class="btn btn-outline-secondary ms-2">Lanjut ke Daily Tracking</a>
</div>
</form>
</div>
</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
<?php if ($canEdit): ?>
let rowCount = <?= $event['event_days'] ?>;

document.getElementById('addRowBtn').addEventListener('click', function() {
    rowCount++;
    const label = 'DAY-' + rowCount;
    const showTraffic = <?= $showCol('baseline_traffic') ? 'true' : 'false' ?>;
    const showEAV     = <?= $showCol('baseline_event_area_visitors') ? 'true' : 'false' ?>;
    const showTrans   = <?= $showCol('baseline_transactions') ? 'true' : 'false' ?>;
    const showSales   = <?= $showCol('baseline_tenant_sales') ? 'true' : 'false' ?>;
    const showPark    = <?= $showCol('baseline_parking_revenue') ? 'true' : 'false' ?>;

    let cells = `
        <td><input type="text" name="day_label[]" class="form-control form-control-sm" value="${label}"></td>
        <td><input type="text" name="comparable_period[]" class="form-control form-control-sm" value="Comparable Day ${rowCount}"></td>
        <td><select name="day_type[]" class="form-select form-select-sm">
            <option value="Weekday">Weekday</option>
            <option value="Weekend/High Season">Weekend</option>
        </select></td>`;
    if (showTraffic)  cells += `<td><input type="number" name="baseline_traffic[]" class="form-control form-control-sm" value="12000"></td>`;
    else              cells += `<input type="hidden" name="baseline_traffic[]" value="12000">`;
    if (showEAV)      cells += `<td><input type="number" name="baseline_event_area_visitors[]" class="form-control form-control-sm" value="2800"></td>`;
    else              cells += `<input type="hidden" name="baseline_event_area_visitors[]" value="2800">`;
    if (showTrans)    cells += `<td><input type="number" name="baseline_transactions[]" class="form-control form-control-sm" value="1400"></td>`;
    else              cells += `<input type="hidden" name="baseline_transactions[]" value="1400">`;
    if (showSales)    cells += `<td><input type="text" name="baseline_tenant_sales[]" class="form-control form-control-sm currency-input" value="720.000.000"></td>`;
    else              cells += `<input type="hidden" name="baseline_tenant_sales[]" value="720000000">`;
    if (showPark)     cells += `<td><input type="text" name="baseline_parking_revenue[]" class="form-control form-control-sm currency-input" value="70.000.000"></td>`;
    else              cells += `<input type="hidden" name="baseline_parking_revenue[]" value="70000000">`;
    cells += `<td><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bi bi-trash"></i></button></td>`;

    const row = `<tr>${cells}</tr>`;
    document.getElementById('baselineBody').insertAdjacentHTML('beforeend', row);
});

document.getElementById('baselineBody').addEventListener('click', function(e) {
    if (e.target.closest('.remove-row')) {
        e.target.closest('tr').remove();
    }
});
<?php endif; ?>
</script>
<?= $this->endSection() ?>
