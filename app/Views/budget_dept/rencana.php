<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<style>
.bd-grid { font-size:.8rem; }
.bd-grid td, .bd-grid th { white-space:nowrap; vertical-align:middle; }
.bd-grid thead th { font-size:.72rem; text-transform:uppercase; letter-spacing:.03em; color:var(--bs-secondary-color); font-weight:600; text-align:center; }
.bd-grid .col-pos { position:sticky; left:0; background-color:var(--card-bg); background-image:linear-gradient(var(--card-bg),var(--card-bg)); z-index:2; min-width:230px; max-width:280px; white-space:normal; }
.bd-grid .col-pos .mal-lbl { float:right; margin-left:.5rem; }
.bd-grid td.sel { position:relative; padding-right:.15rem; }
.bd-grid input.angka { width:104px; text-align:right; font-variant-numeric:tabular-nums; padding:.2rem 1.25rem .2rem .35rem; font-size:.8rem; }
/* Sel berincian: total saja, diubah lewat jendela rincian. */
.bd-grid input.angka.berinci { cursor:pointer; border-style:dashed; border-color:rgba(var(--bs-primary-rgb),.55); color:var(--bs-primary-text-emphasis); }
.bd-grid .btn-rinci { position:absolute; right:.3rem; top:50%; transform:translateY(-50%); border:0; background:none; padding:0 .15rem; line-height:1;
                      font-size:.8rem; color:var(--bs-secondary-color); opacity:0; transition:opacity .12s; }
.bd-grid td.sel:hover .btn-rinci, .bd-grid td.sel:focus-within .btn-rinci, .bd-grid .btn-rinci:focus-visible { opacity:1; }
.bd-grid .btn-rinci.ada { opacity:1; color:var(--bs-primary-text-emphasis); }
.bd-grid .tot { font-weight:600; text-align:right; font-variant-numeric:tabular-nums; min-width:110px; }
.bd-grid tfoot td { font-weight:700; text-align:right; border-top:2px solid var(--bs-border-color); font-variant-numeric:tabular-nums; }
.bd-grid tr.mal-pertama td { border-top:1px solid var(--bs-border-color); }
.bd-grid .mal-lbl { font-size:.7rem; color:var(--bs-secondary-color); }
.bd-grid thead .col-pos, .bd-grid tfoot .col-pos { background-color:var(--card-bg) !important; background-image:linear-gradient(var(--card-bg),var(--card-bg)) !important; z-index:3; }
#mRinci .r-jml { text-align:right; font-variant-numeric:tabular-nums; }
@media (hover:none) { .bd-grid .btn-rinci { opacity:1; } }
</style>
<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= $this->include('budget_dept/_kepala') ?>
<?php
$BLN = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
$BLN_PANJANG = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$posAktif = array_filter($pos, fn ($p) => ! empty($p['aktif']) || isset($grid[(int) $p['id']]));
$modeMal  = $pisahMal ? ['ewalk' => 'eWalk', 'pentacity' => 'Pentacity'] : ['x' => ''];
$labelMal = ['x' => 'Tanpa mal', 'ewalk' => 'eWalk', 'pentacity' => 'Pentacity'];
$adaIsi = $grid !== [];
// Sel "berinci" = punya keterangan atau lebih dari satu baris → tak bisa diketik langsung.
$berinci = fn (array $r) => count($r) > 1 || (count($r) === 1 && $r[0]['k'] !== '');
?>

<?php if (! $posAktif): ?>
<div class="card"><div class="card-body text-center py-5">
    <h6>Belum ada pos budget</h6>
    <a href="<?= $url('pos') ?>" class="btn btn-primary btn-sm mt-2"><i class="bi bi-plus-lg me-1"></i>Buat Pos Budget</a>
</div></div>
<?php else: ?>

<?php if ($canEdit && ! $adaIsi && $adaTahunLalu): ?>
<div class="alert alert-info d-flex flex-wrap align-items-center gap-2 py-2 small">
    <i class="bi bi-info-circle"></i>
    <span>Budget <?= $tahun ?> masih kosong. Mulai dari angka <?= $tahun - 1 ?> lalu sesuaikan?</span>
    <form method="POST" action="<?= $url('rencana/salin', ['tahun' => $tahun]) ?>" class="ms-auto">
        <?= csrf_field() ?>
        <button class="btn btn-sm btn-outline-primary"><i class="bi bi-copy me-1"></i>Salin dari <?= $tahun - 1 ?></button>
    </form>
</div>
<?php endif; ?>

<form method="POST" action="<?= $url('rencana/simpan', ['tahun' => $tahun]) ?>" id="bd-form">
<?= csrf_field() ?>
<input type="hidden" name="data" value="">
<div class="card">
    <div class="card-header d-flex flex-wrap align-items-center gap-2">
        <h6 class="mb-0 fw-semibold">Budget per bulan — <?= $tahun ?></h6>
        <span class="text-muted small">Isi dalam Rupiah. <i class="bi bi-list-ul"></i> = pecah satu bulan per event/keterangan.</span>
        <?php if ($canEdit): ?>
        <button type="submit" class="btn btn-sm btn-primary ms-auto"><i class="bi bi-floppy me-1"></i>Simpan</button>
        <?php endif; ?>
    </div>
    <div class="table-responsive">
    <table class="table table-sm mb-0 bd-grid">
        <thead><tr>
            <th class="col-pos text-start">Pos</th>
            <?php foreach ($BLN as $n): ?><th><?= $n ?></th><?php endforeach; ?>
            <th class="text-end">Total</th>
            <?php if ($canEdit): ?><th></th><?php endif; ?>
        </tr></thead>
        <tbody>
        <?php foreach ($posAktif as $p):
            $pid = (int) $p['id'];
            // Baris mode sekarang + baris mal lain yang masih punya angka
            // (mis. dept baru beralih ke pisah mal) agar tak ada angka tersembunyi.
            $kunci = array_keys($modeMal + array_fill_keys(array_keys($grid[$pid] ?? []), ''));
            $i = 0;
        ?>
            <?php foreach ($kunci as $mk): $i++; ?>
            <tr class="<?= $i === 1 ? 'mal-pertama' : '' ?>" data-baris>
                <td class="col-pos">
                    <?php if ($pisahMal || $mk !== 'x'): ?><span class="mal-lbl"><?= $labelMal[$mk] ?></span><?php endif; ?>
                    <?php if ($i === 1): ?>
                    <span data-nama><?= esc($p['nama']) ?></span>
                    <?php if (empty($p['aktif'])): ?><span class="badge text-bg-secondary ms-1">nonaktif</span><?php endif; ?>
                    <?php endif; ?>
                </td>
                <?php foreach ($BLN as $m => $n):
                    $r = $grid[$pid][$mk][$m] ?? [];
                    $tot = array_sum(array_column($r, 'j'));
                    $inci = $berinci($r);
                    $judul = $inci ? implode("\n", array_map(fn ($x) => ($x['k'] !== '' ? $x['k'] : '(tanpa keterangan)') . ': ' . number_format($x['j'], 0, ',', '.'), $r)) : '';
                ?>
                <td class="sel" data-pos="<?= $pid ?>" data-mal="<?= $mk ?>" data-bulan="<?= $m ?>"
                    data-rinci="<?= esc(json_encode($r)) ?>" data-label="<?= esc($p['nama'] . ($pisahMal || $mk !== 'x' ? ' · ' . $labelMal[$mk] : '') . ' · ' . $BLN_PANJANG[$m] . ' ' . $tahun) ?>">
                    <input type="text" inputmode="numeric" class="form-control form-control-sm angka <?= $inci ? 'berinci' : '' ?>"
                           <?= ! $canEdit || $inci ? 'readonly' : '' ?> title="<?= esc($judul) ?>"
                           value="<?= $tot ? number_format($tot, 0, ',', '.') : '' ?>"
                           aria-label="<?= esc($p['nama']) ?> <?= $labelMal[$mk] ?> <?= $n ?>">
                    <?php if ($canEdit || $inci): ?>
                    <button type="button" class="btn-rinci <?= $inci ? 'ada' : '' ?>" title="Rincian per event / keterangan" aria-label="Rincian <?= esc($p['nama']) ?> <?= $n ?>">
                        <i class="bi bi-list-ul"></i></button>
                    <?php endif; ?>
                </td>
                <?php endforeach; ?>
                <td class="tot" data-total>0</td>
                <?php if ($canEdit): ?>
                <td><button type="button" class="btn btn-sm btn-link p-0 text-decoration-none" data-ratakan title="Samakan Feb–Des dengan angka Januari (sel berincian tidak diubah)">
                    <i class="bi bi-arrow-right-square"></i></button></td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
        <?php endforeach; ?>
        </tbody>
        <tfoot><tr>
            <td class="col-pos text-start">Total</td>
            <?php foreach ($BLN as $m => $n): ?><td data-tbulan="<?= $m ?>">0</td><?php endforeach; ?>
            <td data-tgrand>0</td>
            <?php if ($canEdit): ?><td></td><?php endif; ?>
        </tr></tfoot>
    </table>
    </div>
    <?php if ($canEdit): ?>
    <div class="card-footer d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <span class="text-muted small">
            <i class="bi bi-list-ul me-1"></i>rincian per event (mis. Januari: Tahun Baru + Isra Mi'raj) ·
            <i class="bi bi-arrow-right-square mx-1"></i>salin angka Januari ke Feb–Des
        </span>
        <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-floppy me-1"></i>Simpan</button>
    </div>
    <?php endif; ?>
</div>
</form>

<!-- Jendela rincian satu sel -->
<div class="modal fade" id="mRinci" tabindex="-1" aria-labelledby="mRinciJudul" aria-hidden="true">
<div class="modal-dialog">
<div class="modal-content">
    <div class="modal-header">
        <div>
            <h6 class="modal-title mb-0" id="mRinciJudul">Rincian budget</h6>
            <div class="small text-muted" data-r-label></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
    </div>
    <div class="modal-body">
        <table class="table table-sm align-middle mb-2">
            <thead><tr class="small text-muted"><th>Keterangan / event</th><th class="text-end" style="width:150px">Budget (Rp)</th><th style="width:32px"></th></tr></thead>
            <tbody data-r-baris></tbody>
            <tfoot><tr><td class="fw-semibold">Total bulan ini</td><td class="text-end fw-bold" data-r-total>0</td><td></td></tr></tfoot>
        </table>
        <?php if ($canEdit): ?>
        <button type="button" class="btn btn-sm btn-outline-secondary" data-r-tambah><i class="bi bi-plus-lg me-1"></i>Tambah baris</button>
        <?php endif; ?>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal"><?= $canEdit ? 'Batal' : 'Tutup' ?></button>
        <?php if ($canEdit): ?>
        <button type="button" class="btn btn-sm btn-primary" data-r-pakai>Terapkan</button>
        <?php endif; ?>
    </div>
</div>
</div>
</div>
<?php endif; ?>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
(function () {
    const form = document.getElementById('bd-form');
    if (!form) return;
    const BISA = <?= $canEdit ? 'true' : 'false' ?>;
    const fmt  = n => n ? n.toLocaleString('id-ID', {maximumFractionDigits: 0}) : '0';
    const baca = s => { s = String(s ?? '').replace(/[^\d,]/g, '').replace(',', '.'); return s ? parseFloat(s) || 0 : 0; };
    const esc  = s => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

    // Sumber kebenaran tiap sel = daftar rincian [{k, j}] di dataset.rinci.
    const rinci   = td => JSON.parse(td.dataset.rinci || '[]');
    const berinci = r => r.length > 1 || (r.length === 1 && r[0].k !== '');
    function tulisSel(td, r) {
        td.dataset.rinci = JSON.stringify(r);
        const inp = td.querySelector('input.angka'), btn = td.querySelector('.btn-rinci');
        const tot = r.reduce((a, x) => a + x.j, 0), inci = berinci(r);
        inp.value = tot ? fmt(tot) : '';
        inp.classList.toggle('berinci', inci);
        inp.readOnly = !BISA || inci;
        inp.title = inci ? r.map(x => (x.k || '(tanpa keterangan)') + ': ' + fmt(x.j)).join('\n') : '';
        if (btn) btn.classList.toggle('ada', inci);
    }

    function hitung() {
        const perBulan = {}; let grand = 0;
        form.querySelectorAll('tr[data-baris]').forEach(tr => {
            let t = 0;
            tr.querySelectorAll('td.sel').forEach(td => {
                const v = rinci(td).reduce((a, x) => a + x.j, 0); t += v;
                perBulan[td.dataset.bulan] = (perBulan[td.dataset.bulan] || 0) + v;
            });
            tr.querySelector('[data-total]').textContent = fmt(t);
            grand += t;
        });
        form.querySelectorAll('[data-tbulan]').forEach(td => td.textContent = fmt(perBulan[td.dataset.tbulan] || 0));
        form.querySelector('[data-tgrand]').textContent = fmt(grand);
    }

    let kotor = false;
    // Ketik langsung di sel biasa → satu baris tanpa keterangan.
    form.addEventListener('input', e => {
        if (!e.target.matches('input.angka') || e.target.readOnly) return;
        const v = baca(e.target.value), td = e.target.closest('td.sel');
        td.dataset.rinci = JSON.stringify(v ? [{k: '', j: v}] : []);
        kotor = true; hitung();
    });
    form.addEventListener('focusout', e => {
        if (!e.target.matches('input.angka') || e.target.readOnly) return;
        const v = baca(e.target.value);
        e.target.value = v ? fmt(v) : '';
    });

    // ── Jendela rincian ──
    const m = document.getElementById('mRinci');
    const modal = bootstrap.Modal.getOrCreateInstance(m);
    const tb = m.querySelector('[data-r-baris]');
    let selAktif = null;

    function barisRinci(k, j) {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td><input type="text" class="form-control form-control-sm r-ket" maxlength="200" placeholder="mis. Imlek" value="${esc(k)}" ${BISA ? '' : 'readonly'}></td>
            <td><input type="text" inputmode="numeric" class="form-control form-control-sm r-jml" value="${j ? fmt(j) : ''}" ${BISA ? '' : 'readonly'}></td>
            <td>${BISA ? '<button type="button" class="btn btn-sm btn-link p-0 text-danger" data-r-hapus title="Hapus baris" aria-label="Hapus baris"><i class="bi bi-x-lg"></i></button>' : ''}</td>`;
        tb.appendChild(tr);
        return tr;
    }
    const totalRinci = () => m.querySelector('[data-r-total]').textContent =
        fmt([...tb.querySelectorAll('.r-jml')].reduce((a, i) => a + baca(i.value), 0));

    function buka(td) {
        selAktif = td;
        m.querySelector('[data-r-label]').textContent = td.dataset.label;
        tb.innerHTML = '';
        const r = rinci(td);
        (r.length ? r : [{k: '', j: 0}]).forEach(x => barisRinci(x.k, x.j));
        if (BISA && r.length) barisRinci('', 0);       // baris kosong siap diisi
        totalRinci();
        modal.show();
    }
    form.addEventListener('click', e => {
        const b = e.target.closest('.btn-rinci');
        if (b) { buka(b.closest('td.sel')); return; }
        const inp = e.target.closest('input.angka.berinci');
        if (inp) buka(inp.closest('td.sel'));
    });
    m.addEventListener('shown.bs.modal', () => { const i = tb.querySelector('.r-ket'); if (i && BISA) i.focus(); });
    m.addEventListener('input', totalRinci);
    m.addEventListener('focusout', e => { if (e.target.matches('.r-jml')) { const v = baca(e.target.value); e.target.value = v ? fmt(v) : ''; } });
    m.addEventListener('click', e => {
        if (e.target.closest('[data-r-tambah]')) { barisRinci('', 0).querySelector('.r-ket').focus(); return; }
        const h = e.target.closest('[data-r-hapus]');
        if (h) { h.closest('tr').remove(); totalRinci(); return; }
        if (e.target.closest('[data-r-pakai]') && selAktif) {
            const r = [...tb.querySelectorAll('tr')]
                .map(tr => ({k: tr.querySelector('.r-ket').value.trim(), j: baca(tr.querySelector('.r-jml').value)}))
                .filter(x => x.j > 0);
            tulisSel(selAktif, r);
            kotor = true; hitung(); modal.hide();
        }
    });
    // Enter di jendela rincian = Terapkan, bukan kirim form utama.
    m.addEventListener('keydown', e => { if (e.key === 'Enter' && BISA) { e.preventDefault(); m.querySelector('[data-r-pakai]').click(); } });

    // ── Samakan Feb–Des dengan Januari (sel berincian dibiarkan) ──
    form.addEventListener('click', e => {
        const b = e.target.closest('[data-ratakan]');
        if (!b) return;
        const sel = [...b.closest('tr').querySelectorAll('td.sel')];
        const jan = rinci(sel[0]).reduce((a, x) => a + x.j, 0);
        sel.slice(1).forEach(td => { if (!berinci(rinci(td))) tulisSel(td, jan ? [{k: '', j: jan}] : []); });
        kotor = true; hitung();
    });

    form.addEventListener('submit', () => {
        const data = {};
        form.querySelectorAll('td.sel').forEach(td => {
            const p = td.dataset.pos, mk = td.dataset.mal;
            ((data[p] ??= {})[mk] ??= {})[td.dataset.bulan] = rinci(td);
        });
        form.elements['data'].value = JSON.stringify(data);
        kotor = false;
    });
    window.addEventListener('beforeunload', e => { if (kotor) { e.preventDefault(); e.returnValue = ''; } });
    hitung();
})();
</script>
<?= $this->endSection() ?>
