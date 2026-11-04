<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<style>
.ss-tabs .nav-link { font-size: .85rem; padding: .4rem .8rem; }
.ss-budget { font-size: .8rem; }
.ss-budget td, .ss-budget th { padding: .25rem .5rem; }
.ss-num { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
.ss-lewat { color: var(--bs-danger); font-weight: 700; }
.ss-prog { border-left: 4px solid var(--bs-primary); }
.ss-prog.rutin { border-left-color: var(--bs-secondary); }
.ss-prog .card-header { background: var(--bs-tertiary-bg); }
.ss-prog .ss-head { display: grid; grid-template-columns: minmax(220px, 2fr) 120px 140px 140px minmax(170px, 1fr); gap: .5rem; align-items: end; }
.ss-prog .ss-head label { font-size: .7rem; font-weight: 600; color: var(--bs-secondary-color); margin-bottom: .1rem; display: block; }
.ss-lok { display: flex; flex-wrap: wrap; gap: .25rem .9rem; font-size: .78rem; margin-top: .4rem; }
.ss-grid { font-size: .8rem; margin: 0; }
.ss-grid th { font-size: .7rem; font-weight: 600; color: var(--bs-secondary-color); white-space: nowrap; background: transparent; }
.ss-grid td { padding: .15rem .25rem; vertical-align: middle; }
.ss-grid .form-control, .ss-grid .form-select { font-size: .8rem; padding: .15rem .35rem; min-height: 0; height: 28px; }
.ss-grid textarea.form-control { height: 28px; min-width: 230px; resize: vertical; overflow: hidden; }
.ss-grid tr[data-jenis=biaya] td:first-child { min-width: 250px; }
.ss-grid tr.kel td { background: var(--bs-light-bg-subtle); }
.ss-grid tr.kel .form-control { font-style: italic; font-weight: 600; }
.ss-grid tr.kunci td { opacity: .6; }
/* min-width, bukan width: di layar sempit tabel bergulir di dalam kartu, isi kolom tidak terpotong. */
.ss-grid .w-qty { min-width: 64px; } .ss-grid .w-unit { min-width: 88px; } .ss-grid .w-harga { min-width: 116px; }
.ss-grid .w-tot { min-width: 108px; } .ss-grid .w-pos { min-width: 160px; } .ss-grid .w-tgl { min-width: 136px; } .ss-grid .w-aksi { min-width: 92px; }
.ss-aksi { white-space: nowrap; width: 92px; }
.ss-aksi .btn { padding: 0 .3rem; line-height: 1.4; }
.ss-bar { position: sticky; bottom: 0; z-index: 20; background: var(--bs-body-bg); border-top: 1px solid var(--bs-border-color); padding: .6rem 0; }
</style>
<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= $this->include('budget_dept/_kepala') ?>
<?php
use App\Libraries\RabSusun;

$budgetTab = [];
foreach ($pos as $p) $budgetTab[(int) $p['id']] = (float) ($ringkas['sel'][$bulan][$mall][(int) $p['id']]['budget'] ?? 0);
$tabUrl = fn ($b, $m) => $url('rab/' . $d['id'] . '/susun', ['bulan' => $b, 'mall' => $m]);
?>

<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <a href="<?= $url('rab/' . $d['id']) ?>" class="btn btn-sm btn-outline-secondary ss-pergi" aria-label="Kembali ke RAB"><i class="bi bi-arrow-left"></i></a>
    <div>
        <div class="fw-bold">Susun RAB <?= RabSusun::labelPeriode($d['periode_mulai'], $d['periode_selesai']) ?></div>
        <div class="small text-muted">Versi <?= (int) $d['versi'] ?> — draf · setiap program tersimpan sebagai event di modul Events</div>
    </div>
    <div class="ms-auto d-flex gap-2">
        <a href="<?= $url('rab/' . $d['id'] . '/cetak') ?>" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-printer me-1"></i>Pratinjau PDF</a>
    </div>
</div>

<ul class="nav nav-tabs ss-tabs mb-3">
    <?php foreach ($bulanP as $b): foreach (['ewalk', 'pentacity'] as $m): ?>
    <li class="nav-item"><a class="nav-link ss-pergi <?= $b === $bulan && $m === $mall ? 'active' : '' ?>" href="<?= $tabUrl($b, $m) ?>"><?= RabSusun::MAL[$m] ?> · <?= RabSusun::labelBulan($b) ?></a></li>
    <?php endforeach; endforeach; ?>
</ul>

<div class="row g-3 mb-3">
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header py-2"><h6 class="mb-0 fw-semibold small"><i class="bi bi-wallet2 me-1"></i>Budget <?= RabSusun::MAL[$mall] ?> <?= RabSusun::labelBulan($bulan) ?></h6></div>
            <table class="table table-sm mb-0 ss-budget">
                <thead><tr><th>Pos</th><th class="ss-num">Budget</th><th class="ss-num">Terisi di tab ini</th><th class="ss-num">Sisa</th></tr></thead>
                <tbody id="ssBudget"></tbody>
            </table>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="alert alert-light border small h-100 mb-0">
            <div class="fw-semibold mb-1"><i class="bi bi-info-circle me-1"></i>Cara mengisi</div>
            <ul class="mb-0 ps-3">
                <li><b>Program</b> = satu event (kegiatan rutin bulanan juga satu program berjenis <i>Rutin</i>). Program baru masuk modul Events dan menunggu persetujuan seperti biasa.</li>
                <li><b>Pos</b> dipilih di program; baris ikut pos program kecuali dipilih lain.</li>
                <li>Baris dibebankan ke bulan tab ini. Kolom tanggal boleh kosong; jadwal rinci tulis di <b>Remark</b>.</li>
                <li>Event yang belum pasti boleh tanpa baris biaya — tercetak sebagai info/tbc.</li>
            </ul>
        </div>
    </div>
</div>

<div id="ssProgram"></div>

<div class="d-flex flex-wrap gap-2 mb-4">
    <button type="button" class="btn btn-sm btn-outline-primary" id="ssTambahEvent"><i class="bi bi-plus-lg me-1"></i>Program / event</button>
    <button type="button" class="btn btn-sm btn-outline-secondary" id="ssTambahRutin"><i class="bi bi-arrow-repeat me-1"></i>Kegiatan rutin bulan ini</button>
</div>

<div class="ss-bar">
    <div class="d-flex flex-wrap align-items-center gap-3">
        <div class="small">Total tab ini: <b id="ssTotal">0</b></div>
        <div class="small text-muted" id="ssStatus">Belum ada perubahan.</div>
        <button type="button" class="btn btn-primary btn-sm ms-auto" id="ssSimpan" disabled><i class="bi bi-save me-1"></i>Simpan tab ini</button>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
    const DATA   = <?= json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;
    const POS    = <?= json_encode(array_map(fn ($p) => ['id' => (int) $p['id'], 'nama' => $p['nama']], $pos), JSON_HEX_TAG | JSON_UNESCAPED_UNICODE) ?>;
    const LOKASI = <?= json_encode(array_map(fn ($l) => ['id' => (int) $l['id'], 'nama' => $l['nama']], $lokasi), JSON_HEX_TAG | JSON_UNESCAPED_UNICODE) ?>;
    const BUDGET = <?= json_encode($budgetTab) ?>;
    const BULAN  = <?= json_encode($bulan) ?>, MALL = <?= json_encode($mall) ?>, MAL_NAMA = <?= json_encode(RabSusun::MAL[$mall]) ?>;
    const LABEL_BULAN = <?= json_encode(RabSusun::labelBulan($bulan)) ?>;
    const SIMPAN_URL = <?= json_encode($url('rab/' . $d['id'] . '/susun', ['bulan' => $bulan, 'mall' => $mall])) ?>;
    const CSRF_NAMA = <?= json_encode(csrf_token()) ?>;
    let csrf = <?= json_encode(csrf_hash()) ?>;

    const awalBulan = BULAN + '-01';
    const akhirBulan = (() => { const [y, m] = BULAN.split('-').map(Number); return BULAN + '-' + String(new Date(y, m, 0).getDate()).padStart(2, '0'); })();
    const wadah = document.getElementById('ssProgram');
    let kotor = false;

    const rp = v => Math.round(v || 0).toLocaleString('id-ID');
    const angka = v => { v = String(v ?? '').trim().replace(',', '.'); return v === '' || isNaN(v) ? null : parseFloat(v); };
    const rupiah = v => { v = String(v ?? '').replace(/[^\d]/g, ''); return v === '' ? null : parseInt(v, 10); };
    const esc = s => String(s ?? '').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
    const fmtAngka = v => v === null || v === undefined || v === '' ? '' : String(parseFloat(v)).replace('.', ',');
    const opsiPos = (pilih, kosong) => `<option value="">${kosong}</option>` + POS.map(p => `<option value="${p.id}" ${+pilih === p.id ? 'selected' : ''}>${esc(p.nama)}</option>`).join('');

    function totalBaris(tr) {
        const h = rupiah(tr.querySelector('[data-f=harga]').value);
        if (h === null) return +tr.dataset.budget || 0;
        const q = angka(tr.querySelector('[data-f=qty]').value), f = angka(tr.querySelector('[data-f=freq]').value);
        return Math.round((q === null ? 1 : q) * (f === null ? 1 : f) * h);
    }

    function barisKel(r) {
        return `<tr class="kel" data-jenis="kel"><td colspan="10"><input class="form-control" data-f="nama" value="${esc(r.nama)}" placeholder="Kelompok, mis. Opening Ceremony / Competition / Pintu Lobby Utama" aria-label="Nama kelompok"></td>
            <td class="ss-aksi text-end">${tombolAksi(false)}</td></tr>`;
    }
    function barisBiaya(r) {
        const kunci = !!r.kunci;
        const dis = kunci ? 'disabled' : '';
        return `<tr data-jenis="biaya" data-id="${r.id || ''}" data-budget="${r.budget || 0}" class="${kunci ? 'kunci' : ''}" ${kunci ? `title="Tercantum di ${esc(r.kunci)} — ubah lewat revisi RAB itu"` : ''}>
            <td><input class="form-control" data-f="nama" value="${esc(r.nama)}" ${dis} aria-label="Rincian biaya"></td>
            <td class="w-qty"><input class="form-control text-end" data-f="qty" value="${fmtAngka(r.qty)}" inputmode="decimal" ${dis} aria-label="Qty"></td>
            <td class="w-unit"><input class="form-control" data-f="satuan" value="${esc(r.satuan)}" ${dis} aria-label="Satuan qty"></td>
            <td class="w-qty"><input class="form-control text-end" data-f="freq" value="${fmtAngka(r.freq)}" inputmode="decimal" ${dis} aria-label="Frekuensi"></td>
            <td class="w-unit"><input class="form-control" data-f="satuan_freq" value="${esc(r.satuan_freq)}" ${dis} aria-label="Satuan frekuensi"></td>
            <td class="w-harga"><input class="form-control text-end" data-f="harga" value="${r.harga !== null && r.harga !== undefined ? rp(r.harga) : ''}" inputmode="numeric" ${dis} aria-label="Harga satuan"></td>
            <td class="w-tot ss-num fw-semibold" data-tot>0</td>
            <td class="w-pos"><select class="form-select" data-f="pos_id" ${dis} aria-label="Pos budget baris">${opsiPos(r.pos_id, 'ikut program')}</select></td>
            <td class="w-tgl"><input type="date" class="form-control" data-f="tanggal" value="${r.tanggal && r.tanggal.startsWith(BULAN) ? r.tanggal : ''}" min="${awalBulan}" max="${akhirBulan}" ${dis} aria-label="Tanggal (opsional)"></td>
            <td><textarea class="form-control" data-f="keterangan" rows="1" ${dis} aria-label="Remark">${esc(r.keterangan)}</textarea></td>
            <td class="ss-aksi text-end w-aksi">${kunci ? '<i class="bi bi-lock" title="Terkunci RAB aktif"></i>' : tombolAksi(!!r.real)}</td></tr>`;
    }
    function tombolAksi(adaReal) {
        return `<button type="button" class="btn btn-sm btn-link" data-a="naik" title="Naik" aria-label="Naikkan baris"><i class="bi bi-arrow-up"></i></button>`
             + `<button type="button" class="btn btn-sm btn-link" data-a="turun" title="Turun" aria-label="Turunkan baris"><i class="bi bi-arrow-down"></i></button>`
             + (adaReal ? `<i class="bi bi-receipt text-success ms-1" title="Sudah ada realisasi — tidak bisa dihapus"></i>`
                        : `<button type="button" class="btn btn-sm btn-link text-danger" data-a="hapus" title="Hapus" aria-label="Hapus baris"><i class="bi bi-x-lg"></i></button>`);
    }

    function kartu(p) {
        const ro = p.event_id && !p.ubahHeader ? 'disabled' : '';
        const div = document.createElement('div');
        div.className = 'card mb-3 ss-prog' + (p.jenis === 'rutin' ? ' rutin' : '');
        div.dataset.eventId = p.event_id || '';
        const lok = LOKASI.map(l => `<label class="form-check form-check-inline m-0"><input class="form-check-input" type="checkbox" data-lok value="${l.id}" ${(p.lokasi || []).includes(l.id) ? 'checked' : ''} ${ro}> ${esc(l.nama)}</label>`).join('');
        const lain = p.mall && p.mall !== MALL ? '<span class="badge text-bg-info ms-1">Dua mal</span>' : '';
        const tunggu = p.approval === 'pending' ? '<span class="badge text-bg-warning ms-1">Menunggu persetujuan event</span>' : (p.approval === 'rejected' ? '<span class="badge text-bg-danger ms-1">Event ditolak</span>' : '');
        div.innerHTML = `
        <div class="card-header py-2">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="badge ${p.jenis === 'rutin' ? 'text-bg-secondary' : 'text-bg-primary'}" data-badge>${p.jenis === 'rutin' ? 'Rutin' : 'Event'}</span>
                ${p.event_id ? `<a class="small ss-pergi" href="<?= base_url('events') ?>/${p.event_id}/content" target="_blank">Event #${p.event_id} <i class="bi bi-box-arrow-up-right"></i></a>` : '<span class="small text-success">Event baru — dibuat saat disimpan</span>'}
                ${lain}${tunggu}
                ${ro ? '<span class="small text-muted"><i class="bi bi-lock me-1"></i>Data event diubah di modul Events</span>' : ''}
                ${p.income ? `<a class="badge text-bg-success text-decoration-none ss-pergi" href="<?= base_url('events') ?>/${p.event_id}/exhibitors" target="_blank" title="Nilai dealing exhibitor — diubah di tab Exhibitor event">Proyeksi income Rp ${rp(p.income)}</a>` : ''}
                <span class="ms-auto small">Total program: <b data-ptot>0</b></span>
                <button type="button" class="btn btn-sm btn-link text-danger p-0 ms-2" data-a="hapus-prog" title="Keluarkan program dari tab ini" aria-label="Keluarkan program"><i class="bi bi-trash"></i></button>
            </div>
            <div class="ss-head">
                <div><label>Nama program / event</label><input class="form-control form-control-sm" data-h="nama" value="${esc(p.nama)}" ${ro} placeholder="mis. BSB Wedding Expo"></div>
                <div><label>Jenis</label><select class="form-select form-select-sm" data-h="jenis" ${ro}><option value="event" ${p.jenis !== 'rutin' ? 'selected' : ''}>Event</option><option value="rutin" ${p.jenis === 'rutin' ? 'selected' : ''}>Rutin</option></select></div>
                <div><label>Mulai</label><input type="date" class="form-control form-control-sm" data-h="mulai" value="${p.mulai || ''}" ${ro}></div>
                <div><label>Selesai</label><input type="date" class="form-control form-control-sm" data-h="selesai" value="${p.selesai || ''}" ${ro}></div>
                <div><label>Pos budget program</label><select class="form-select form-select-sm" data-h="pos_id" ${ro}>${opsiPos(p.pos_id, '— pilih pos —')}</select></div>
            </div>
            ${LOKASI.length ? `<div class="ss-lok"><span class="text-muted">Lokasi:</span>${lok}</div>` : ''}
        </div>
        <div class="table-responsive">
        <table class="table table-sm ss-grid">
            <thead><tr><th>Rincian</th><th class="text-end">Qty</th><th>Unit</th><th class="text-end">Freq</th><th>Unit</th><th class="text-end">Harga satuan</th><th class="text-end">Total</th><th>Pos</th><th>Tgl</th><th>Remark</th><th></th></tr></thead>
            <tbody>${(p.rows || []).map(r => r.jenis === 'kel' ? barisKel(r) : barisBiaya(r)).join('')}</tbody>
        </table>
        </div>
        <div class="card-footer py-1 d-flex gap-2">
            <button type="button" class="btn btn-sm btn-link px-0" data-a="tambah-baris"><i class="bi bi-plus-lg me-1"></i>Baris biaya</button>
            <button type="button" class="btn btn-sm btn-link px-0" data-a="tambah-kel"><i class="bi bi-plus-lg me-1"></i>Kelompok</button>
            <span class="small text-muted ms-auto align-self-center" data-kosong ${(p.rows || []).length ? 'hidden' : ''}>Belum ada biaya — tercetak sebagai info/tbc.</span>
        </div>`;
        return div;
    }

    function hitung() {
        const perPos = {}; let total = 0;
        wadah.querySelectorAll('.ss-prog').forEach(c => {
            const posProg = c.querySelector('[data-h=pos_id]').value;
            let pt = 0;
            c.querySelectorAll('tr[data-jenis=biaya]').forEach(tr => {
                const t = totalBaris(tr);
                tr.querySelector('[data-tot]').textContent = rp(t);
                pt += t;
                const p = tr.querySelector('[data-f=pos_id]').value || posProg || '0';
                perPos[p] = (perPos[p] || 0) + t;
            });
            c.querySelector('[data-ptot]').textContent = rp(pt);
            c.querySelector('[data-kosong]').hidden = !!c.querySelector('tbody tr');
            total += pt;
        });
        document.getElementById('ssTotal').textContent = 'Rp ' + rp(total);
        let html = '', tb = 0, tt = 0;
        POS.forEach(p => {
            const b = BUDGET[p.id] || 0, t = perPos[p.id] || 0;
            if (!b && !t) return;
            tb += b; tt += t;
            html += `<tr><td>${esc(p.nama)}</td><td class="ss-num">${rp(b)}</td><td class="ss-num">${rp(t)}</td><td class="ss-num ${t > b ? 'ss-lewat' : ''}">${rp(b - t)}</td></tr>`;
        });
        if (perPos['0']) html += `<tr><td class="text-danger">Belum ada pos</td><td></td><td class="ss-num text-danger">${rp(perPos['0'])}</td><td></td></tr>`;
        html += `<tr class="fw-bold"><td>Total</td><td class="ss-num">${rp(tb)}</td><td class="ss-num">${rp(tt + (perPos['0'] || 0))}</td><td class="ss-num ${tt > tb ? 'ss-lewat' : ''}">${rp(tb - tt - (perPos['0'] || 0))}</td></tr>`;
        document.getElementById('ssBudget').innerHTML = html;
    }

    function tandaiKotor() {
        kotor = true;
        document.getElementById('ssSimpan').disabled = false;
        document.getElementById('ssStatus').textContent = 'Ada perubahan yang belum disimpan.';
    }

    function tambahProgram(p) {
        const k = kartu(p); wadah.appendChild(k); hitung(); tandaiKotor();
        k.querySelector('[data-h=nama]').focus();
    }

    // Remark panjang (daftar tanggal, hadiah lomba) tampil utuh, tidak terpotong satu baris.
    const tinggi = el => { el.style.height = 'auto'; el.style.height = Math.max(28, el.scrollHeight + 2) + 'px'; };
    const tinggiSemua = () => wadah.querySelectorAll('textarea[data-f=keterangan]').forEach(tinggi);

    DATA.forEach(p => wadah.appendChild(kartu(p)));
    hitung(); tinggiSemua();

    document.getElementById('ssTambahEvent').addEventListener('click', () => tambahProgram({ nama: '', jenis: 'event', mulai: awalBulan, selesai: awalBulan, rows: [{ jenis: 'biaya', nama: '', qty: 1, satuan: 'Package', freq: 1, satuan_freq: 'Hari' }], ubahHeader: true }));
    document.getElementById('ssTambahRutin').addEventListener('click', () => tambahProgram({ nama: `Regular Event ${MAL_NAMA} ${LABEL_BULAN}`, jenis: 'rutin', mulai: awalBulan, selesai: akhirBulan, pos_id: POS[0] ? POS[0].id : null, rows: [{ jenis: 'biaya', nama: '', qty: 1, satuan: 'Package', freq: 1, satuan_freq: 'Hari' }], ubahHeader: true }));

    wadah.addEventListener('input', e => { if (e.target.matches('textarea')) tinggi(e.target); tandaiKotor(); if (e.target.closest('tr[data-jenis=biaya]') || e.target.matches('[data-h=pos_id]')) hitung(); });
    wadah.addEventListener('change', e => {
        tandaiKotor();
        if (e.target.matches('[data-f=harga]')) { const v = rupiah(e.target.value); e.target.value = v === null ? '' : rp(v); }
        if (e.target.matches('[data-h=jenis]')) {
            const c = e.target.closest('.ss-prog'), r = e.target.value === 'rutin';
            c.classList.toggle('rutin', r);
            const b = c.querySelector('[data-badge]'); b.textContent = r ? 'Rutin' : 'Event'; b.className = 'badge ' + (r ? 'text-bg-secondary' : 'text-bg-primary');
        }
        if (e.target.matches('[data-h=mulai]')) { const s = e.target.closest('.ss-head').querySelector('[data-h=selesai]'); if (!s.value || s.value < e.target.value) s.value = e.target.value; }
        hitung();
    });
    wadah.addEventListener('click', e => {
        const b = e.target.closest('[data-a]'); if (!b) return;
        const c = b.closest('.ss-prog'), tr = b.closest('tr'), tb = c.querySelector('tbody');
        switch (b.dataset.a) {
            case 'tambah-baris': tb.insertAdjacentHTML('beforeend', barisBiaya({ nama: '', qty: 1, satuan: 'Package', freq: 1, satuan_freq: 'Hari' })); tb.lastElementChild.querySelector('[data-f=nama]').focus(); break;
            case 'tambah-kel': tb.insertAdjacentHTML('beforeend', barisKel({ nama: '' })); tb.lastElementChild.querySelector('[data-f=nama]').focus(); break;
            case 'naik': if (tr.previousElementSibling) tr.parentNode.insertBefore(tr, tr.previousElementSibling); break;
            case 'turun': if (tr.nextElementSibling) tr.parentNode.insertBefore(tr.nextElementSibling, tr); break;
            case 'hapus': tr.remove(); break;
            case 'hapus-prog':
                if (c.querySelector('tr.kunci, .bi-receipt')) { alert('Program ini punya baris yang terkunci RAB aktif atau sudah ada realisasinya — tidak bisa dikeluarkan.'); return; }
                if (!confirm(c.dataset.eventId ? 'Keluarkan program ini dari tab? Semua baris biayanya di bulan ini dihapus saat disimpan. Event-nya tetap ada di modul Events (hapus di sana bila batal).' : 'Hapus program baru ini?')) return;
                c.remove(); break;
        }
        hitung(); tandaiKotor();
    });

    // Id baris yang dimuat halaman ini: server hanya menghapus baris dari daftar ini.
    const DIMUAT = DATA.flatMap(p => (p.rows || []).filter(r => r.id).map(r => r.id));

    function kumpulkan() {
        return { dimuat: DIMUAT, program: [...wadah.querySelectorAll('.ss-prog')].map(c => {
            const h = n => c.querySelector(`[data-h=${n}]`).value;
            return {
                event_id: +c.dataset.eventId || null, nama: h('nama'), jenis: h('jenis'), mulai: h('mulai'), selesai: h('selesai'), pos_id: +h('pos_id') || null,
                lokasi: [...c.querySelectorAll('[data-lok]:checked')].map(x => +x.value),
                rows: [...c.querySelectorAll('tbody tr')].map(tr => {
                    const f = n => { const el = tr.querySelector(`[data-f=${n}]`); return el ? el.value : null; };
                    if (tr.dataset.jenis === 'kel') return { jenis: 'kel', nama: f('nama') };
                    return { jenis: 'biaya', id: +tr.dataset.id || null, nama: f('nama'), qty: f('qty'), satuan: f('satuan'), freq: f('freq'), satuan_freq: f('satuan_freq'),
                             harga: f('harga'), pos_id: +f('pos_id') || null, tanggal: f('tanggal'), keterangan: f('keterangan') };
                }),
            };
        }) };
    }

    const tombol = document.getElementById('ssSimpan'), status = document.getElementById('ssStatus');
    // Token CSRF dirotasi tiap POST: tab lain milik orang yang sama bisa membuatnya basi.
    // Ditolak 403 → ambil token baru dari halaman ini lalu coba sekali lagi; isian tidak hilang.
    async function kirim() {
        const fd = new FormData(); fd.append(CSRF_NAMA, csrf); fd.append('isi', JSON.stringify(kumpulkan()));
        return fetch(SIMPAN_URL, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
    }
    async function tokenBaru() {
        const h = await (await fetch(location.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })).text();
        const m = h.match(/let csrf = "([^"]+)"/);
        if (m) csrf = m[1];
        return !!m;
    }
    tombol.addEventListener('click', async () => {
        tombol.disabled = true; status.textContent = 'Menyimpan…';
        try {
            let r = await kirim();
            if (r.status === 403 && await tokenBaru()) r = await kirim();
            let j = null;
            try { j = await r.json(); } catch (e) {}
            if (j && j.csrf) { csrf = j.csrf; document.querySelectorAll(`input[name="${CSRF_NAMA}"]`).forEach(i => i.value = csrf); }
            if (!j || !j.ok) {
                const p = j && j.pesan ? j.pesan : `Error ${r.status}: server menolak penyimpanan. Perubahan belum tersimpan — coba lagi, atau muat ulang halaman bila terus terjadi.`;
                status.innerHTML = '<span class="text-danger"><i class="bi bi-x-octagon me-1"></i>' + esc(p) + '</span>'; tombol.disabled = false; return;
            }
            kotor = false;
            try { sessionStorage.setItem('ssPesan', j.pesan); } catch (e) {}
            location.reload();
        } catch (e) {
            status.innerHTML = '<span class="text-danger">Error: tidak tersambung ke server. Perubahan belum tersimpan.</span>'; tombol.disabled = false;
        }
    });
    try { const m = sessionStorage.getItem('ssPesan'); if (m) { status.innerHTML = '<span class="text-success"><i class="bi bi-check-circle me-1"></i>' + esc(m) + '</span>'; sessionStorage.removeItem('ssPesan'); } } catch (e) {}

    window.addEventListener('beforeunload', e => { if (kotor) { e.preventDefault(); e.returnValue = ''; } });
})();
</script>
<?= $this->endSection() ?>
