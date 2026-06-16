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

.cell-inner { display: flex; align-items: center; justify-content: center; gap: 1px; }
.traffic-input {
    width: 46px; border: none; text-align: center; font-size: .8rem;
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
/* Sel terkunci (sudah ada isinya) */
.traffic-input.locked { color: #475569; font-weight: 600; cursor: default; }
[data-theme="dark"] .traffic-input.locked { color: #cbd5e1; }
/* Tombol Ubah: hanya untuk sel terisi & user yang berhak; muncul saat hover (desktop) */
.btn-ubah {
    border: none; background: transparent; color: var(--bs-primary);
    padding: 0 2px; font-size: .68rem; line-height: 1; cursor: pointer; opacity: 0;
    transition: opacity .12s;
}
.traffic-cell:not(.is-filled) .btn-ubah,
.traffic-cell.editing .btn-ubah,
[data-canedit-filled="0"] .btn-ubah { display: none; }
.traffic-cell.is-filled:hover .btn-ubah { opacity: .75; }
.traffic-cell.saving { background: rgba(var(--bs-warning-rgb),.18) !important; }
.traffic-cell.saved-flash { animation: savedFlash .9s ease; }
@keyframes savedFlash { 0%{ background: rgba(var(--bs-success-rgb),.35);} 100%{ background: transparent;} }

tr:hover .col-pintu                             { background: rgba(var(--bs-primary-rgb),.1); }
tr:hover td:not(.col-pintu):not(.col-total)     { background: rgba(var(--bs-primary-rgb),.04); }

[data-theme="dark"] .col-pintu               { background: #1c1248; }
[data-theme="dark"] thead .col-pintu          { background: rgba(139,92,246,.12); }
[data-theme="dark"] tfoot .col-pintu          { background: rgba(139,92,246,.08); }
[data-theme="dark"] .col-total               { background: rgba(139,92,246,.08); }
[data-theme="dark"] thead .col-total          { background: rgba(139,92,246,.12); }
[data-theme="dark"] tr:hover .col-pintu       { background: rgba(139,92,246,.18); }

/* ── Mobile (≤991px): layout per-jam (kartu = jam, isi = pintu) ───── */
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
    #mobileBox .btn-ubah { opacity: .8; font-size: 1rem; padding: 0 8px; }
    [data-theme="dark"] .mob-hour-card .card-header { background: rgba(139,92,246,.14); }
    [data-theme="dark"] #mobileBox .traffic-input  { background: #1c1248; color: #e5e7eb; }
}
</style>
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php
$mallLabels = ['ewalk' => 'eWalk Simply FUNtastic', 'pentacity' => 'Pentacity Shopping Venue'];
$mallLabel  = $mallLabels[$mall] ?? $mall;
$mallColor  = $mall === 'ewalk' ? 'primary' : 'success';
$hours      = range(10, 23);

// Build lookup: filled[jam][door_id] = jumlah — HANYA sel yang punya baris di DB.
// (sel tanpa baris = belum diisi; 0 yang tersimpan = diisi sengaja)
$filled = [];
foreach ($trafficRows as $tr) {
    foreach ($doors as $d) {
        if ($d['nama_pintu'] === $tr['pintu']) {
            $filled[(int) $tr['jam']][$d['id']] = (int) $tr['jumlah_pengunjung'];
            break;
        }
    }
}

// Totals per door (row) and per hour (column)
$doorTotals = [];
foreach ($doors as $d) {
    $sum = 0;
    foreach ($hours as $h) $sum += $filled[$h][$d['id']] ?? 0;
    $doorTotals[$d['id']] = $sum;
}
$hourTotals = [];
foreach ($hours as $h) {
    $sum = 0;
    foreach ($doors as $d) $sum += $filled[$h][$d['id']] ?? 0;
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

<input type="hidden" id="fMall" value="<?= esc($mall) ?>">
<input type="hidden" id="fTanggal" value="<?= esc($tanggal) ?>">

<!-- Top bar: date + status -->
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

    <div class="col-auto ms-auto d-flex align-items-end gap-3">
        <div class="small text-muted" id="saveStatus" style="min-width:120px;text-align:right">
            <i class="bi bi-cloud-check me-1"></i>Tersimpan otomatis
        </div>
        <?php if (! empty($doors)): ?>
        <div class="card text-center px-3 py-1 border-0 bg-light">
            <div class="small text-muted" style="font-size:.7rem">Total</div>
            <div class="fw-bold text-<?= $mallColor ?>" id="grand-total-top"><?= $grandTotal > 0 ? number_format($grandTotal) : '—' ?></div>
        </div>
        <?php endif; ?>
    </div>

</div>
</div>
</div>

<div class="alert alert-light border py-2 small d-flex align-items-center gap-2 mb-3">
    <i class="bi bi-info-circle text-<?= $mallColor ?>"></i>
    <span>Setiap angka tersimpan otomatis per sel.
    <?php if ($canEditFilled): ?>Sel yang sudah terisi terkunci — klik <i class="bi bi-pencil"></i> untuk mengubah.
    <?php else: ?>Anda hanya dapat mengisi sel yang masih kosong; sel yang sudah terisi terkunci.<?php endif; ?>
    Beberapa orang dapat mengisi bersamaan — tiap sel disimpan sendiri-sendiri.</span>
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

<?php
// Render satu sel (dipakai desktop & mobile)
$renderInput = function (int $h, array $d) use ($filled) {
    $isFilled = isset($filled[$h][$d['id']]);
    $val      = $isFilled ? $filled[$h][$d['id']] : '';
    ob_start(); ?>
    <div class="traffic-cell cell-inner <?= $isFilled ? 'is-filled' : '' ?>" data-jam="<?= $h ?>" data-door="<?= $d['id'] ?>">
        <input type="number"
               class="traffic-input<?= $isFilled ? ' locked' : '' ?>"
               value="<?= $val ?>"
               data-jam="<?= $h ?>"
               data-door="<?= $d['id'] ?>"
               data-orig="<?= $val ?>"
               min="0" inputmode="numeric" placeholder="0"
               <?= $isFilled ? 'readonly' : '' ?>>
        <button type="button" class="btn-ubah" title="Ubah" tabindex="-1"><i class="bi bi-pencil"></i></button>
    </div>
    <?php return ob_get_clean();
};
?>

<div class="traffic-wrap d-none d-lg-block" id="desktopBox" data-canedit-filled="<?= $canEditFilled ? '1' : '0' ?>">
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
    <td class="p-0 text-center" data-label="<?= $h ?>–<?= $h+1 ?>"><?= $renderInput($h, $d) ?></td>
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
<div class="d-lg-none p-2" id="mobileBox" data-canedit-filled="<?= $canEditFilled ? '1' : '0' ?>">
<?php foreach ($hours as $h): ?>
<div class="card mb-2 mob-hour-card">
    <div class="card-header py-2 d-flex justify-content-between align-items-center">
        <span class="small"><i class="bi bi-clock me-1"></i><?= $h ?>.00–<?= $h+1 ?>.00</span>
        <span class="small">Total: <b id="m-hour-total-<?= $h ?>"><?= $hourTotals[$h] > 0 ? number_format($hourTotals[$h]) : '—' ?></b></span>
    </div>
    <div class="card-body p-2">
        <?php foreach ($doors as $d): ?>
        <div class="mob-row">
            <span class="mob-door-name small"><?= esc($d['nama_pintu']) ?></span>
            <?= $renderInput($h, $d) ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>
</div>

<?php endif; ?>
</div>
</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
// ── Konfigurasi ──────────────────────────────────────────────────────────
const SAVE_URL  = '<?= base_url('traffic/save-cell') ?>';
const MALL      = document.getElementById('fMall').value;
const TANGGAL   = document.getElementById('fTanggal').value;
const CAN_EDIT_FILLED = <?= $canEditFilled ? 'true' : 'false' ?>;
const csrfName  = '<?= csrf_token() ?>';
let   csrfHash  = '<?= csrf_hash() ?>';

const desktopBox = document.getElementById('desktopBox');
const mobileBox  = document.getElementById('mobileBox');
const isMobile   = () => window.matchMedia('(max-width: 991.98px)').matches;
const activeBox  = () => isMobile() ? mobileBox : desktopBox;
const fmt        = n => n > 0 ? n.toLocaleString('id-ID') : '—';
const setText    = (id, t) => { const e = document.getElementById(id); if (e) e.textContent = t; };
const cellsFor   = (jam, door) =>
    [...document.querySelectorAll(`.traffic-cell[data-jam="${jam}"][data-door="${door}"]`)];

// ── Status simpan ────────────────────────────────────────────────────────
function status(html, cls = 'text-muted') {
    const el = document.getElementById('saveStatus');
    if (el) { el.className = 'small ' + cls; el.style.minWidth = '120px'; el.style.textAlign = 'right'; el.innerHTML = html; }
}

// ── Antrian simpan (serial) supaya rotasi CSRF konsisten ─────────────────
let chain = Promise.resolve();
function enqueue(task) { chain = chain.then(task).catch(() => {}); return chain; }

function postCell(jam, door, jumlah) {
    const body = new URLSearchParams();
    body.append(csrfName, csrfHash);
    body.append('mall', MALL);
    body.append('tanggal', TANGGAL);
    body.append('jam', jam);
    body.append('door_id', door);
    body.append('jumlah', jumlah);
    return fetch(SAVE_URL, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body,
    }).then(async r => {
        const data = await r.json().catch(() => ({}));
        if (data.csrf) syncCsrf(data.csrf);    // perbarui token & semua form di halaman
        if (! r.ok || ! data.ok) throw new Error(data.msg || 'Gagal menyimpan.');
        return data;
    });
}

// regenerate=true → token berganti tiap POST; samakan ke semua field CSRF
// (mis. form logout di layout) agar tidak basi.
function syncCsrf(hash) {
    csrfHash = hash;
    document.querySelectorAll(`input[name="${csrfName}"]`).forEach(i => { i.value = hash; });
}

// ── Lock / unlock sel di KEDUA layout ────────────────────────────────────
function applyValue(jam, door, jumlah) {
    cellsFor(jam, door).forEach(cell => {
        cell.classList.add('is-filled');
        cell.classList.remove('editing');
        const inp = cell.querySelector('.traffic-input');
        inp.value = jumlah;
        inp.dataset.orig = jumlah;
        inp.readOnly = true;
        inp.classList.add('locked');
    });
}

function unlock(cell) {
    if (! CAN_EDIT_FILLED) return;
    const jam = cell.dataset.jam, door = cell.dataset.door;
    cellsFor(jam, door).forEach(c => {
        c.classList.add('editing');
        const inp = c.querySelector('.traffic-input');
        inp.readOnly = false;
        inp.classList.remove('locked');
    });
    const inp = activeBox().querySelector(`.traffic-cell[data-jam="${jam}"][data-door="${door}"] .traffic-input`);
    if (inp) { inp.focus(); inp.select(); }
}

function flash(jam, door, cls) {
    cellsFor(jam, door).forEach(c => {
        c.classList.remove('saving', 'saved-flash');
        if (cls) c.classList.add(cls);
        if (cls === 'saved-flash') setTimeout(() => c.classList.remove('saved-flash'), 900);
    });
}

// ── Simpan satu sel ──────────────────────────────────────────────────────
function saveCell(input) {
    const jam = input.dataset.jam, door = input.dataset.door;
    const raw = input.value.trim();
    const orig = input.dataset.orig;

    // Kosong = batal / tidak ada perubahan → kembalikan nilai semula.
    if (raw === '') {
        if (orig !== '') { applyValue(jam, door, parseInt(orig, 10)); }
        else { cellsFor(jam, door).forEach(c => c.classList.remove('editing')); }
        recompute();
        return;
    }
    const jumlah = parseInt(raw, 10);
    if (isNaN(jumlah) || jumlah < 0) { input.value = orig; return; }
    if (String(jumlah) === String(orig)) {            // tak berubah
        applyValue(jam, door, jumlah);
        return;
    }

    cellsFor(jam, door).forEach(c => { c.classList.add('saving'); c.classList.remove('editing'); });
    status('<i class="bi bi-arrow-repeat me-1"></i>Menyimpan…', 'text-warning');

    enqueue(() => postCell(jam, door, jumlah)
        .then(() => {
            applyValue(jam, door, jumlah);
            flash(jam, door, 'saved-flash');
            status('<i class="bi bi-cloud-check me-1"></i>Tersimpan', 'text-success');
        })
        .catch(err => {
            flash(jam, door, null);
            // gagal → kembalikan nilai semula
            if (orig !== '') applyValue(jam, door, parseInt(orig, 10));
            else { cellsFor(jam, door).forEach(c => { c.classList.remove('is-filled', 'editing'); const i = c.querySelector('.traffic-input'); i.value = ''; i.readOnly = false; i.classList.remove('locked'); }); }
            recompute();
            status('<i class="bi bi-exclamation-triangle me-1"></i>' + (err.message || 'Gagal'), 'text-danger');
        })
    );
}

// ── Totals ───────────────────────────────────────────────────────────────
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
    setText('grand-total', fg); setText('grand-total-top', fg);
}

// ── Event binding ────────────────────────────────────────────────────────
document.querySelectorAll('.traffic-input').forEach(inp => {
    inp.addEventListener('input', () => {                 // mirror live ke layout lain
        const jam = inp.dataset.jam, door = inp.dataset.door;
        cellsFor(jam, door).forEach(c => {
            const t = c.querySelector('.traffic-input');
            if (t !== inp && ! t.readOnly) t.value = inp.value;
        });
        recompute();
    });
    inp.addEventListener('change', () => saveCell(inp));
    inp.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); inp.blur(); } });
});

document.querySelectorAll('.btn-ubah').forEach(btn => {
    btn.addEventListener('click', () => unlock(btn.closest('.traffic-cell')));
});

window.addEventListener('resize', recompute);

document.getElementById('changeDate').addEventListener('click', function () {
    const d = document.getElementById('datePicker').value;
    if (d) window.location.href = '<?= base_url('traffic/input/'.$mall.'/') ?>' + d;
});
</script>
<?= $this->endSection() ?>
