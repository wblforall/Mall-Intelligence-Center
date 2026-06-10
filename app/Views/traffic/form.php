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

/* ── Mobile (≤991px): layout per-jam (kartu = jam, isi = pintu) ───── */
.mobile-save-bar { display: none; }
@media (max-width: 991.98px) {
    .mob-hour-card .card-header {
        background: rgba(var(--bs-primary-rgb),.08);
        font-weight: 700;
    }
    .mob-row {
        display: flex; align-items: center; justify-content: space-between; gap: 10px;
        padding: 7px 2px; border-bottom: 1px solid var(--bs-border-color);
    }
    .mob-row:last-child { border-bottom: none; }
    .mob-door-name { color: var(--txt, #1e293b); line-height: 1.2; }
    #mobileBox .traffic-input {
        width: 104px; flex: 0 0 auto; font-size: 1.05rem;
        padding: 9px 10px; text-align: right;
        border: 1px solid var(--bs-border-color);
        border-radius: 8px; background: var(--bs-body-bg);
    }
    [data-theme="dark"] .mob-hour-card .card-header { background: rgba(139,92,246,.14); }
    [data-theme="dark"] #mobileBox .traffic-input  { background: #1c1248; color: #e5e7eb; }

    /* Bar simpan menempel di bawah */
    .mobile-save-bar {
        display: flex; align-items: center; justify-content: space-between; gap: 12px;
        position: sticky; bottom: 0; z-index: 20;
        margin: 12px -1rem -1rem; padding: 10px 1rem;
        background: var(--card-bg, #fff);
        border-top: 1px solid var(--bs-border-color);
        box-shadow: 0 -2px 8px rgba(0,0,0,.06);
    }
    [data-theme="dark"] .mobile-save-bar { background: #1c1248; }
}
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

<!-- Top bar: date + submit -->
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

<div class="traffic-wrap d-none d-lg-block" id="desktopBox">
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
    <td class="p-0 text-center" data-label="<?= $h ?>–<?= $h+1 ?>">
        <input type="number"
               name="jumlah[<?= $h ?>][<?= $d['id'] ?>]"
               class="traffic-input"
               value="<?= $val ?>"
               min="0"
               inputmode="numeric"
               data-jam="<?= $h ?>"
               data-door="<?= $d['id'] ?>">
    </td>
    <?php endforeach; ?>
    <td class="col-total" id="door-total-<?= $d['id'] ?>" data-label="Total pintu">
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

<!-- Mobile: kartu per jam, isi semua pintu -->
<div class="d-lg-none p-2" id="mobileBox">
<?php foreach ($hours as $h): ?>
<div class="card mb-2 mob-hour-card">
    <div class="card-header py-2 d-flex justify-content-between align-items-center">
        <span class="small"><i class="bi bi-clock me-1"></i><?= $h ?>.00–<?= $h+1 ?>.00</span>
        <span class="small">Total: <b id="m-hour-total-<?= $h ?>"><?= $hourTotals[$h] > 0 ? number_format($hourTotals[$h]) : '—' ?></b></span>
    </div>
    <div class="card-body p-2">
        <?php foreach ($doors as $d): $val = $existing[$h][$d['id']] ?? 0; ?>
        <div class="mob-row">
            <span class="mob-door-name small"><?= esc($d['nama_pintu']) ?></span>
            <input type="number"
                   name="jumlah[<?= $h ?>][<?= $d['id'] ?>]"
                   class="traffic-input"
                   value="<?= $val ?>"
                   min="0"
                   inputmode="numeric"
                   data-jam="<?= $h ?>"
                   data-door="<?= $d['id'] ?>">
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>
</div>

<?php endif; ?>
</div>
</div>

<?php if (! empty($doors)): ?>
<div class="mobile-save-bar">
    <div>
        <div class="small text-muted" style="font-size:.7rem;line-height:1">Total</div>
        <div class="fw-bold text-<?= $mallColor ?>" id="grand-total-mobile"><?= $grandTotal > 0 ? number_format($grandTotal) : '—' ?></div>
    </div>
    <button type="submit" class="btn btn-<?= $mallColor ?> flex-grow-1 py-2">
        <i class="bi bi-check-lg me-1"></i>Simpan
    </button>
</div>
<?php endif; ?>

</form>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
// Dua layout (desktop tabel & mobile per-jam) berbagi nilai yang sama.
// Edit di layout aktif disalin ke layout lain; total dihitung dari layout aktif;
// saat submit, layout tersembunyi dinonaktifkan agar tidak dobel terkirim.
const desktopBox = document.getElementById('desktopBox');
const mobileBox  = document.getElementById('mobileBox');
const isMobile   = () => window.matchMedia('(max-width: 991.98px)').matches;
const activeBox  = () => isMobile() ? mobileBox : desktopBox;
const fmt        = n => n > 0 ? n.toLocaleString('id-ID') : '—';
const setText    = (id, t) => { const e = document.getElementById(id); if (e) e.textContent = t; };

document.querySelectorAll('.traffic-input').forEach(inp => {
    inp.addEventListener('input', () => { mirror(inp); recompute(); });
});

// Salin nilai ke input pasangannya (jam+pintu sama) di layout satunya
function mirror(inp) {
    const other = inp.closest('#mobileBox') ? desktopBox : mobileBox;
    if (! other) return;
    const t = other.querySelector(`input[data-jam="${inp.dataset.jam}"][data-door="${inp.dataset.door}"]`);
    if (t) t.value = inp.value;
}

function recompute() {
    const box = activeBox();
    if (! box) return;
    const hourSum = {}, doorSum = {};
    let grand = 0;
    box.querySelectorAll('.traffic-input').forEach(i => {
        const v = parseInt(i.value) || 0;
        grand += v;
        hourSum[i.dataset.jam]  = (hourSum[i.dataset.jam]  || 0) + v;
        doorSum[i.dataset.door] = (doorSum[i.dataset.door] || 0) + v;
    });
    for (const h in hourSum) { setText('hour-total-' + h, fmt(hourSum[h])); setText('m-hour-total-' + h, fmt(hourSum[h])); }
    for (const d in doorSum) { setText('door-total-' + d, fmt(doorSum[d])); }
    const fg = fmt(grand);
    setText('grand-total', fg); setText('grand-total-top', fg); setText('grand-total-mobile', fg);
}

// Recompute saat ganti orientasi/ukuran (layout aktif bisa berganti)
window.addEventListener('resize', recompute);

// Anti dobel-submit: nonaktifkan input di layout tersembunyi
document.querySelector('form').addEventListener('submit', () => {
    const hidden = isMobile() ? desktopBox : mobileBox;
    if (hidden) hidden.querySelectorAll('.traffic-input').forEach(i => i.disabled = true);
});

document.getElementById('changeDate').addEventListener('click', function () {
    const d = document.getElementById('datePicker').value;
    if (d) window.location.href = '<?= base_url('traffic/input/'.$mall.'/') ?>' + d;
});
</script>
<?= $this->endSection() ?>
