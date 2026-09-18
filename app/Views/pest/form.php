<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<style>
.pest-input {
    width: 78px; text-align: center; font-weight: 600; font-size: 1rem;
    border: 1px solid var(--bs-border-color); border-radius: .4rem; padding: .35rem .25rem;
    background: var(--bs-body-bg); color: var(--bs-body-color);
}
.pest-input:focus { outline: 2px solid rgba(var(--bs-primary-rgb), .45); border-color: transparent; }
.pest-input.locked { background: rgba(var(--bs-success-rgb), .1); border-color: rgba(var(--bs-success-rgb), .35); }
.pest-cell { display: inline-flex; align-items: center; gap: .3rem; }
.btn-ubah {
    border: none; background: transparent; color: var(--bs-secondary-color);
    padding: .15rem .3rem; border-radius: .3rem; line-height: 1; display: none;
}
.pest-cell.is-filled .btn-ubah { display: inline-flex; }
.btn-ubah:hover { background: rgba(var(--bs-primary-rgb), .12); color: var(--bs-primary); }
.foto-strip { display: flex; flex-wrap: wrap; gap: .35rem; align-items: center; }
.foto-thumb { position: relative; width: 46px; height: 46px; border-radius: .35rem; overflow: hidden; border: 1px solid var(--bs-border-color); }
.foto-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
.foto-thumb .x {
    position: absolute; top: 0; right: 0; background: rgba(0,0,0,.6); color: #fff;
    border: none; line-height: 1; font-size: .65rem; padding: 2px 4px; cursor: pointer;
}
.row-saving { opacity: .55; }
.item-row td { vertical-align: middle; }
.mob-card .pest-input { width: 72px; }
</style>
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php
$mallLabel = \App\Models\PestVisitModel::MALLS[$mall];
$mallLain  = $mall === 'ewalk' ? 'pentacity' : 'ewalk';
$total     = 0;
foreach ($temuan as $t) $total += $t['jumlah'];
$adaKunjungan = ! empty($visit);
?>

<input type="hidden" id="fMall" value="<?= esc($mall) ?>">
<input type="hidden" id="fTanggal" value="<?= esc($tanggal) ?>">
<input type="hidden" id="fVisitId" value="<?= $adaKunjungan ? (int) $visit['id'] : '' ?>">

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-bug me-2"></i>Input Temuan Pest</h4>
        <small class="text-muted"><?= $mallLabel ?> &middot; <?= tgl_indo($tanggal) ?></small>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= base_url('pest/input/' . $mallLain . '/' . $tanggal) ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left-right me-1"></i><?= \App\Models\PestVisitModel::MALLS[$mallLain] ?>
        </a>
        <a href="<?= base_url('pest/kunjungan') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-list-ul me-1"></i>Daftar Kunjungan
        </a>
    </div>
</div>

<div class="card mb-3">
<div class="card-body py-2 d-flex flex-wrap gap-3 align-items-center justify-content-between">
    <div class="d-flex align-items-center gap-2">
        <label class="small text-muted mb-0">Tanggal</label>
        <input type="date" id="datePicker" class="form-control form-control-sm" style="width:auto"
               value="<?= esc($tanggal) ?>" max="<?= date('Y-m-d') ?>">
    </div>
    <div class="d-flex align-items-center gap-3">
        <span class="small text-muted">Total temuan</span>
        <span class="badge bg-primary fs-6" id="grandTotal"><?= $total ?></span>
        <button type="button" class="btn btn-sm btn-outline-success" id="btnNihil"
                <?= $total > 0 ? 'disabled' : '' ?>>
            <i class="bi bi-check2-circle me-1"></i>Nihil temuan
        </button>
    </div>
</div>
</div>

<!-- Status kunjungan: membedakan "diperiksa, bersih" dari "belum diinput" -->
<div class="alert py-2 small mb-3 <?= $adaKunjungan ? ($total > 0 ? 'alert-warning' : 'alert-success') : 'alert-secondary' ?>" id="statusBox">
    <?php if (! $adaKunjungan): ?>
        <i class="bi bi-dash-circle me-1"></i><strong>Belum diinput.</strong>
        Isi jumlah temuan, atau tekan <em>Nihil temuan</em> bila pemeriksaan sudah dilakukan dan tidak ada temuan.
    <?php elseif ($total > 0): ?>
        <i class="bi bi-exclamation-triangle me-1"></i><strong>Tercatat <?= $total ?> temuan</strong> pada kunjungan ini.
    <?php else: ?>
        <i class="bi bi-check-circle me-1"></i><strong>Nihil temuan.</strong> Pemeriksaan tercatat, tidak ada temuan.
    <?php endif; ?>
</div>

<!-- ══ Desktop ══ -->
<div class="card d-none d-md-block">
<div class="table-responsive">
<table class="table table-hover align-middle mb-0">
<thead class="table-light">
<tr>
    <th style="width:38%">Item Temuan</th>
    <th class="text-center" style="width:130px">Jumlah</th>
    <th>Foto bukti</th>
</tr>
</thead>
<tbody id="itemBody">
<?php foreach ($items as $it):
    $f    = $temuan[(int) $it['id']] ?? null;
    $val  = $f ? (int) $f['jumlah'] : '';
    $fid  = $f ? (int) $f['id'] : '';
    $nfo  = $f ? (int) $f['jml_foto'] : 0; ?>
<tr class="item-row" data-item="<?= (int) $it['id'] ?>" data-finding="<?= $fid ?>">
    <td class="fw-medium"><?= esc($it['nama']) ?></td>
    <td class="text-center">
        <span class="pest-cell <?= $val !== '' ? 'is-filled' : '' ?>">
            <input type="number" class="pest-input <?= $val !== '' ? 'locked' : '' ?>"
                   value="<?= $val ?>" data-orig="<?= $val ?>" min="0" inputmode="numeric"
                   placeholder="0" <?= $val !== '' ? 'readonly' : '' ?>>
            <button type="button" class="btn-ubah" title="Ubah" tabindex="-1"><i class="bi bi-pencil"></i></button>
        </span>
    </td>
    <td>
        <div class="foto-strip" data-foto-for="<?= (int) $it['id'] ?>">
            <?php if ($nfo > 0): ?><span class="spinner-border spinner-border-sm text-muted"></span><?php endif; ?>
        </div>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
<tfoot class="table-light">
<tr>
    <th>Total</th>
    <th class="text-center" id="footTotal"><?= $total ?></th>
    <th></th>
</tr>
</tfoot>
</table>
</div>
</div>

<!-- ══ Mobile ══ -->
<div class="d-md-none" id="mobBox">
<?php foreach ($items as $it):
    $f    = $temuan[(int) $it['id']] ?? null;
    $val  = $f ? (int) $f['jumlah'] : '';
    $fid  = $f ? (int) $f['id'] : '';
    $nfo  = $f ? (int) $f['jml_foto'] : 0; ?>
<div class="card mb-2 mob-card item-row" data-item="<?= (int) $it['id'] ?>" data-finding="<?= $fid ?>">
    <div class="card-body py-2">
        <div class="d-flex justify-content-between align-items-center">
            <span class="fw-medium"><?= esc($it['nama']) ?></span>
            <span class="pest-cell <?= $val !== '' ? 'is-filled' : '' ?>">
                <input type="number" class="pest-input <?= $val !== '' ? 'locked' : '' ?>"
                       value="<?= $val ?>" data-orig="<?= $val ?>" min="0" inputmode="numeric"
                       placeholder="0" <?= $val !== '' ? 'readonly' : '' ?>>
                <button type="button" class="btn-ubah" title="Ubah" tabindex="-1"><i class="bi bi-pencil"></i></button>
            </span>
        </div>
        <div class="foto-strip mt-2" data-foto-for="<?= (int) $it['id'] ?>">
            <?php if ($nfo > 0): ?><span class="spinner-border spinner-border-sm text-muted"></span><?php endif; ?>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>

<input type="file" id="fotoPicker" accept="image/*" multiple hidden>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
const SAVE_URL  = '<?= base_url('pest/save-cell') ?>';
const NIHIL_URL = '<?= base_url('pest/nihil') ?>';
const FOTO_URL  = '<?= base_url('pest/foto') ?>';
const FOTO_LIST = '<?= base_url('pest/foto-list') ?>';
const FOTO_DEL  = '<?= base_url('pest/foto') ?>';
const MALL      = document.getElementById('fMall').value;
const TANGGAL   = document.getElementById('fTanggal').value;
const MAX_FOTO  = <?= (int) $maxFoto ?>;
const csrfName  = '<?= csrf_token() ?>';
let   csrfHash  = '<?= csrf_hash() ?>';

/* CSRF di MIC dirotasi setiap POST (Config\Security::$regenerate = true).
   Tanpa menulis ulang hash di SELURUH form, submit berikutnya ditolak diam-diam
   — tidak ada pesan galat, kiriman sekadar hilang. */
function pakaiCsrf(json) {
    if (json && json.csrf) {
        csrfHash = json.csrf;
        document.querySelectorAll('input[name="' + csrfName + '"]').forEach(i => i.value = csrfHash);
    }
}

function toast(msg, ok = false) {
    const el = document.createElement('div');
    el.className = 'position-fixed bottom-0 end-0 m-3 alert alert-' + (ok ? 'success' : 'danger') + ' py-2 px-3 shadow';
    el.style.zIndex = 2000;
    el.textContent = msg;
    document.body.appendChild(el);
    setTimeout(() => el.remove(), 3500);
}

function post(url, data) {
    const fd = data instanceof FormData ? data : new FormData();
    if (! (data instanceof FormData)) {
        Object.entries(data).forEach(([k, v]) => fd.append(k, v));
    }
    fd.append(csrfName, csrfHash);
    return fetch(url, { method: 'POST', body: fd, keepalive: true })
        .then(r => r.json().then(j => ({ status: r.status, json: j })))
        .then(({ status, json }) => {
            pakaiCsrf(json);
            if (! json.ok) throw new Error(json.msg || 'Gagal menyimpan (HTTP ' + status + ').');
            return json;
        });
}

/* Baris muncul dua kali (desktop + mobile) untuk item yang sama — keduanya
   harus ikut berubah, kalau tidak angka di layar kecil dan besar berbeda. */
const barisItem = id => document.querySelectorAll('.item-row[data-item="' + id + '"]');

function setTotal(n) {
    document.getElementById('grandTotal').textContent = n;
    const ft = document.getElementById('footTotal');
    if (ft) ft.textContent = n;
    document.getElementById('btnNihil').disabled = n > 0;
}

function kunci(cell, terkunci) {
    const inp = cell.querySelector('.pest-input');
    inp.readOnly = terkunci;
    inp.classList.toggle('locked', terkunci);
    cell.classList.toggle('is-filled', terkunci);
}

function simpan(itemId, jumlah, inputEl) {
    const rows = barisItem(itemId);
    rows.forEach(r => r.classList.add('row-saving'));

    post(SAVE_URL, { mall: MALL, tanggal: TANGGAL, item_id: itemId, jumlah: jumlah })
        .then(j => {
            document.getElementById('fVisitId').value = j.visit_id || '';
            rows.forEach(r => {
                r.dataset.finding = j.finding_id || '';
                const inp = r.querySelector('.pest-input');
                inp.value = jumlah > 0 ? jumlah : '';
                inp.dataset.orig = inp.value;
                kunci(r.querySelector('.pest-cell'), jumlah > 0);
            });
            setTotal(j.total);
            if (jumlah > 0) muatFoto(itemId, j.finding_id);
            else rows.forEach(r => r.querySelector('.foto-strip').innerHTML = '');
            perbaruiStatus(j.total, !! j.visit_id);
        })
        .catch(e => {
            toast(e.message);
            rows.forEach(r => {
                const inp = r.querySelector('.pest-input');
                inp.value = inp.dataset.orig;
            });
        })
        .finally(() => rows.forEach(r => r.classList.remove('row-saving')));
}

document.addEventListener('change', e => {
    if (! e.target.classList.contains('pest-input')) return;
    const row = e.target.closest('.item-row');
    const val = e.target.value.trim();
    const n   = val === '' ? 0 : parseInt(val, 10);
    if (isNaN(n) || n < 0) { e.target.value = e.target.dataset.orig; return; }
    if (String(n) === String(e.target.dataset.orig || '0') && val !== '') return;
    simpan(parseInt(row.dataset.item, 10), n, e.target);
});

document.addEventListener('click', e => {
    const btn = e.target.closest('.btn-ubah');
    if (! btn) return;
    const cell = btn.closest('.pest-cell');
    kunci(cell, false);
    cell.querySelector('.pest-input').focus();
});

// ── Nihil temuan ────────────────────────────────────────────────────────
document.getElementById('btnNihil').addEventListener('click', function () {
    if (! confirm('Catat kunjungan ini sebagai NIHIL TEMUAN?\n\nArtinya pemeriksaan sudah dilakukan dan tidak ada temuan sama sekali.')) return;
    this.disabled = true;
    post(NIHIL_URL, { mall: MALL, tanggal: TANGGAL })
        .then(j => {
            document.getElementById('fVisitId').value = j.visit_id;
            perbaruiStatus(0, true);
            toast('Kunjungan nihil tercatat.', true);
        })
        .catch(e => { toast(e.message); this.disabled = false; });
});

function perbaruiStatus(total, adaKunjungan) {
    const box = document.getElementById('statusBox');
    box.className = 'alert py-2 small mb-3 ' +
        (! adaKunjungan ? 'alert-secondary' : (total > 0 ? 'alert-warning' : 'alert-success'));
    box.innerHTML = ! adaKunjungan
        ? '<i class="bi bi-dash-circle me-1"></i><strong>Belum diinput.</strong> Isi jumlah temuan, atau tekan <em>Nihil temuan</em>.'
        : (total > 0
            ? '<i class="bi bi-exclamation-triangle me-1"></i><strong>Tercatat ' + total + ' temuan</strong> pada kunjungan ini.'
            : '<i class="bi bi-check-circle me-1"></i><strong>Nihil temuan.</strong> Pemeriksaan tercatat, tidak ada temuan.');
}

// ── Foto ────────────────────────────────────────────────────────────────
function muatFoto(itemId, findingId) {
    if (! findingId) return;
    fetch(FOTO_LIST + '/' + findingId)
        .then(r => r.json())
        .then(j => { pakaiCsrf(j); if (j.ok) gambarFoto(itemId, findingId, j.foto); })
        .catch(() => {});
}

function gambarFoto(itemId, findingId, list) {
    barisItem(itemId).forEach(row => {
        const strip = row.querySelector('.foto-strip');
        strip.innerHTML = '';
        list.forEach(f => {
            const d = document.createElement('div');
            d.className = 'foto-thumb';
            d.innerHTML = '<img src="' + f.url + '" alt="">'
                + '<button type="button" class="x" data-del="' + f.id + '" title="Hapus">&times;</button>';
            strip.appendChild(d);
        });
        if (list.length < MAX_FOTO) {
            const b = document.createElement('button');
            b.type = 'button';
            b.className = 'btn btn-sm btn-outline-secondary';
            b.dataset.add = findingId;
            b.dataset.item = itemId;
            b.innerHTML = '<i class="bi bi-camera"></i> Foto';
            strip.appendChild(b);
        }
    });
}

let targetFinding = null, targetItem = null;

document.addEventListener('click', e => {
    const add = e.target.closest('[data-add]');
    if (add) {
        targetFinding = add.dataset.add;
        targetItem    = add.dataset.item;
        const p = document.getElementById('fotoPicker');
        p.value = '';
        p.click();
        return;
    }
    const del = e.target.closest('[data-del]');
    if (del) {
        if (! confirm('Hapus foto ini?')) return;
        const row = del.closest('.item-row');
        post(FOTO_DEL + '/' + del.dataset.del + '/hapus', {})
            .then(() => muatFoto(row.dataset.item, row.dataset.finding))
            .catch(err => toast(err.message));
    }
});

document.getElementById('fotoPicker').addEventListener('change', function () {
    if (! this.files.length || ! targetFinding) return;
    const fd = new FormData();
    fd.append('finding_id', targetFinding);
    for (const f of this.files) fd.append('foto[]', f);

    post(FOTO_URL, fd)
        .then(j => gambarFoto(targetItem, targetFinding, j.foto))
        .catch(e => toast(e.message))
        .finally(() => { this.value = ''; });
});

// Muat foto baris yang sudah punya temuan saat halaman dibuka.
document.querySelectorAll('.item-row[data-finding]').forEach(r => {
    if (r.dataset.finding) muatFoto(r.dataset.item, r.dataset.finding);
});

// Pindah tanggal
document.getElementById('datePicker').addEventListener('change', function () {
    if (this.value) location.href = '<?= base_url('pest/input/' . $mall) ?>/' + this.value;
});
</script>
<?= $this->endSection() ?>
