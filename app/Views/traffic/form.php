<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<style>
.traffic-wrap { overflow-x: auto; }
.traffic-table { border-collapse: separate; border-spacing: 0; min-width: max-content; }
.traffic-table th,
.traffic-table td  { border: 1px solid var(--bs-border-color); padding: 0; }
.traffic-table thead th {
    background: rgba(var(--bs-primary-rgb),.07);
    color: rgba(var(--bs-primary-rgb),.55);
    font-size: .75rem; font-weight: 600;
    text-align: center; padding: 5px 4px; white-space: nowrap;
}
.traffic-table tfoot td {
    background: rgba(var(--bs-primary-rgb),.05);
    color: var(--txt, #1e293b);
    font-size: .75rem; font-weight: 700;
    text-align: center; padding: 4px 6px;
}
.col-pintu {
    position: sticky; left: 0; z-index: 2;
    background: var(--card-bg, #fff);
    min-width: 150px; max-width: 190px; padding: 4px 8px !important;
    font-size: .78rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    color: var(--txt, #1e293b);
}
thead .col-pintu { z-index: 3; background: rgba(var(--bs-primary-rgb),.07); text-align: left; }
tfoot .col-pintu  { background: rgba(var(--bs-primary-rgb),.05); text-align: left; }
.col-total {
    position: sticky; right: 0; z-index: 2;
    background: rgba(var(--bs-primary-rgb),.05);
    min-width: 58px; text-align: right; padding: 4px 6px !important; font-size: .75rem;
    color: var(--txt, #1e293b);
}
thead .col-total { z-index: 3; background: rgba(var(--bs-primary-rgb),.09); }
.traffic-input {
    width: 52px; border: none; text-align: center; font-size: .8rem;
    padding: 3px 2px; background: transparent;
    color: var(--txt, #1e293b);
}
.traffic-input:focus {
    background: rgba(var(--bs-primary-rgb),.1);
    outline: 1px solid var(--bs-primary);
    border-radius: 2px;
}
.traffic-input::-webkit-outer-spin-button,
.traffic-input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
.traffic-input[type=number] { -moz-appearance: textfield; }
tr:hover .col-pintu                             { background: rgba(var(--bs-primary-rgb),.1); }
tr:hover td:not(.col-pintu):not(.col-total)     { background: rgba(var(--bs-primary-rgb),.04); }

/* Dark-specific: sticky cells need the card background to cover content scrolling under them */
[data-theme="dark"] .col-pintu               { background: #1c1248; }
[data-theme="dark"] thead .col-pintu          { background: rgba(139,92,246,.12); }
[data-theme="dark"] tfoot .col-pintu          { background: rgba(139,92,246,.08); }
[data-theme="dark"] .col-total               { background: rgba(139,92,246,.08); }
[data-theme="dark"] thead .col-total          { background: rgba(139,92,246,.12); }
[data-theme="dark"] tr:hover .col-pintu       { background: rgba(139,92,246,.18); }
</style>
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php
$mallLabels = ['ewalk' => 'eWalk Simply FUNtastic', 'pentacity' => 'Pentacity Shopping Venue'];
$mallLabel  = $mallLabels[$mall] ?? $mall;
$mallColor  = $mall === 'ewalk' ? 'primary' : 'success';
$hours      = range(10, 23);

// Build lookup: existing[jam][door_id] = jumlah
$existing = [];
foreach ($trafficRows as $tr) {
    foreach ($doors as $d) {
        if ($d['nama_pintu'] === $tr['pintu']) {
            $existing[(int)$tr['jam']][$d['id']] = (int)$tr['jumlah_pengunjung'];
            break;
        }
    }
}

// Totals per door (row) and per hour (column)
$doorTotals = [];
foreach ($doors as $d) {
    $sum = 0;
    foreach ($hours as $h) $sum += $existing[$h][$d['id']] ?? 0;
    $doorTotals[$d['id']] = $sum;
}
$hourTotals = [];
foreach ($hours as $h) {
    $sum = 0;
    foreach ($doors as $d) $sum += $existing[$h][$d['id']] ?? 0;
    $hourTotals[$h] = $sum;
}
$grandTotal = array_sum($hourTotals);
?>

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="<?= base_url('traffic') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h4 class="fw-bold mb-0">Input Traffic — <?= $mallLabel ?></h4>
        <small class="text-muted"><?= date('l, d M Y', strtotime($tanggal)) ?></small>
    </div>
</div>

<form method="POST" action="<?= base_url('traffic/save') ?>">
<?= csrf_field() ?>
<input type="hidden" name="mall" value="<?= esc($mall) ?>">
<input type="hidden" name="tanggal" value="<?= esc($tanggal) ?>">

<!-- Top bar: date + vehicle + submit -->
<div class="card mb-3">
<div class="card-body py-2">
<div class="row g-2 align-items-end flex-wrap">

    <div class="col-auto">
        <label class="form-label small fw-semibold mb-1">Tanggal</label>
        <div class="d-flex gap-1">
            <input type="date" id="datePicker" class="form-control form-control-sm" value="<?= $tanggal ?>">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="changeDate">Ganti</button>
        </div>
    </div>

    <div class="col-auto">
        <label class="form-label small fw-semibold mb-1">Mobil</label>
        <input type="number" name="total_mobil" class="form-control form-control-sm" style="width:78px"
               value="<?= $vehicleRow ? $vehicleRow['total_mobil'] : 0 ?>" min="0">
    </div>

    <div class="col-auto">
        <label class="form-label small fw-semibold mb-1">Motor</label>
        <input type="number" name="total_motor" class="form-control form-control-sm" style="width:78px"
               value="<?= $vehicleRow ? $vehicleRow['total_motor'] : 0 ?>" min="0">
    </div>

    <div class="col-auto">
        <label class="form-label small fw-semibold mb-1">Mobil Box</label>
        <input type="number" name="total_mobil_box" class="form-control form-control-sm" style="width:78px"
               value="<?= $vehicleRow ? ($vehicleRow['total_mobil_box'] ?? 0) : 0 ?>" min="0">
    </div>

    <div class="col-auto">
        <label class="form-label small fw-semibold mb-1">Bus</label>
        <input type="number" name="total_bus" class="form-control form-control-sm" style="width:78px"
               value="<?= $vehicleRow ? ($vehicleRow['total_bus'] ?? 0) : 0 ?>" min="0">
    </div>

    <div class="col-auto">
        <label class="form-label small fw-semibold mb-1">Truck</label>
        <input type="number" name="total_truck" class="form-control form-control-sm" style="width:78px"
               value="<?= $vehicleRow ? ($vehicleRow['total_truck'] ?? 0) : 0 ?>" min="0">
    </div>

    <div class="col-auto">
        <label class="form-label small fw-semibold mb-1">Taxi</label>
        <input type="number" name="total_taxi" class="form-control form-control-sm" style="width:78px"
               value="<?= $vehicleRow ? ($vehicleRow['total_taxi'] ?? 0) : 0 ?>" min="0">
    </div>

    <?php if (! empty($doors)): ?>
    <div class="col-auto ms-auto d-flex gap-2">
        <div class="card text-center px-3 py-1 border-0 bg-light">
            <div class="small text-muted" style="font-size:.7rem">Total</div>
            <div class="fw-bold text-<?= $mallColor ?>" id="grand-total-top"><?= $grandTotal > 0 ? number_format($grandTotal) : '—' ?></div>
        </div>
        <button type="submit" class="btn btn-sm btn-<?= $mallColor ?> px-4">
            <i class="bi bi-check-lg me-1"></i>Simpan
        </button>
    </div>
    <?php endif; ?>

</div>
</div>
</div>

<!-- Traffic Grid: doors as rows, hours as columns -->
<div class="card">
<div class="card-header d-flex justify-content-between align-items-center py-2">
    <h6 class="mb-0 fw-semibold small"><i class="bi bi-person-walking me-1"></i>Traffic per Pintu per Jam</h6>
    <span class="small text-muted"><?= count($doors) ?> pintu · <?= count($hours) ?> jam</span>
</div>
<div class="card-body p-0">

<?php if (empty($doors)): ?>
<div class="text-center py-5 text-muted">
    <i class="bi bi-door-open display-4 d-block mb-2 opacity-25"></i>
    <p>Belum ada master pintu untuk <?= $mallLabel ?>.</p>
    <a href="<?= base_url('traffic-doors') ?>" class="btn btn-sm btn-outline-primary">Tambah Pintu Master</a>
</div>
<?php else: ?>

<div class="traffic-wrap">
<table class="traffic-table table-hover mb-0">
<thead>
<tr>
    <th class="col-pintu"><i class="bi bi-door-open me-1 text-muted"></i>Pintu</th>
    <?php foreach ($hours as $h): ?>
    <th style="min-width:54px"><?= $h ?><span class="text-muted fw-normal">–<?= $h+1 ?></span></th>
    <?php endforeach; ?>
    <th class="col-total">Total</th>
</tr>
</thead>
<tbody>
<?php foreach ($doors as $d): ?>
<tr>
    <td class="col-pintu fw-medium" title="<?= esc($d['nama_pintu']) ?>"><?= esc($d['nama_pintu']) ?></td>
    <?php foreach ($hours as $h): ?>
    <?php $val = $existing[$h][$d['id']] ?? 0; ?>
    <td class="p-0 text-center">
        <input type="number"
               name="jumlah[<?= $h ?>][<?= $d['id'] ?>]"
               class="traffic-input"
               value="<?= $val ?>"
               min="0"
               data-jam="<?= $h ?>"
               data-door="<?= $d['id'] ?>">
    </td>
    <?php endforeach; ?>
    <td class="col-total" id="door-total-<?= $d['id'] ?>">
        <?= $doorTotals[$d['id']] > 0 ? number_format($doorTotals[$d['id']]) : '—' ?>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
<tfoot>
<tr>
    <td class="col-pintu">Total</td>
    <?php foreach ($hours as $h): ?>
    <td id="hour-total-<?= $h ?>"><?= $hourTotals[$h] > 0 ? number_format($hourTotals[$h]) : '—' ?></td>
    <?php endforeach; ?>
    <td class="col-total fw-bold" id="grand-total"><?= $grandTotal > 0 ? number_format($grandTotal) : '—' ?></td>
</tr>
</tfoot>
</table>
</div>

<?php endif; ?>
</div>
</div>

</form>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
document.querySelectorAll('.traffic-input').forEach(inp => {
    inp.addEventListener('input', () => updateTotals(inp));
});

function updateTotals(inp) {
    const jam    = inp.dataset.jam;
    const doorId = inp.dataset.door;

    // Hour column total
    const hourSum = [...document.querySelectorAll(`input[data-jam="${jam}"]`)]
        .reduce((s, i) => s + (parseInt(i.value) || 0), 0);
    const hourCell = document.getElementById('hour-total-' + jam);
    if (hourCell) hourCell.textContent = hourSum > 0 ? hourSum.toLocaleString('id-ID') : '—';

    // Door row total
    const doorSum = [...document.querySelectorAll(`input[data-door="${doorId}"]`)]
        .reduce((s, i) => s + (parseInt(i.value) || 0), 0);
    const doorCell = document.getElementById('door-total-' + doorId);
    if (doorCell) doorCell.textContent = doorSum > 0 ? doorSum.toLocaleString('id-ID') : '—';

    // Grand total
    const grand = [...document.querySelectorAll('.traffic-input')]
        .reduce((s, i) => s + (parseInt(i.value) || 0), 0);
    const fmt = grand > 0 ? grand.toLocaleString('id-ID') : '—';
    const gc = document.getElementById('grand-total');
    if (gc) gc.textContent = fmt;
    const gt = document.getElementById('grand-total-top');
    if (gt) gt.textContent = fmt;
}

document.getElementById('changeDate').addEventListener('click', function () {
    const d = document.getElementById('datePicker').value;
    if (d) window.location.href = '<?= base_url('traffic/input/'.$mall.'/') ?>' + d;
});
</script>
<?= $this->endSection() ?>
