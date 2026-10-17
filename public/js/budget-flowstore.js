/* Tab FlowStore — Budget Departemen. Simpan otomatis pilihan (draf), Catat satu/massal, Abaikan.
   Token CSRF dirotasi setiap POST (Security::$regenerate): token baru dari respons AJAX disalin ke semua form. */
(function () {
    'use strict';
    const root = document.getElementById('bfs');
    if (!root) return;
    const NAMA = root.dataset.csrfName;
    const URL_DRAF = root.dataset.urlDraf;
    const bisaUbah = root.dataset.canEdit === '1';
    const tabel = document.getElementById('bfsTbl');

    const token = () => document.querySelector(`input[name="${NAMA}"]`)?.value || '';
    const syncCsrf = (h) => { if (h) document.querySelectorAll(`input[name="${NAMA}"]`).forEach((i) => { i.value = h; }); };

    if (window.bootstrap) document.querySelectorAll('#bfs [data-bs-toggle="tooltip"]').forEach((el) => new bootstrap.Tooltip(el, { trigger: 'hover focus' }));

    document.querySelector('[data-bfs-sinkron]')?.addEventListener('submit', (e) => {
        const b = e.target.querySelector('button');
        if (b.dataset.sedang) { e.preventDefault(); return; }
        b.dataset.sedang = '1'; b.classList.add('is-busy');
        b.querySelector('span').textContent = 'Menyinkronkan…';
    });

    // ── Keperluan: "selengkapnya" hanya muncul bila teks terpotong 2 baris (atau ada rincian: spesifikasi, No. MR/PR/PO) ──
    function ukurKeperluan() {
        document.querySelectorAll('#bfs .bfs-kepw').forEach((w) => {
            const t = w.querySelector('.bfs-kep'), b = w.querySelector('.bfs-more');
            if (!t || !b) return;
            const det = w.parentElement.querySelector('.bfs-det');
            const terbuka = w.classList.contains('is-open');
            const terpotong = terbuka || t.scrollHeight > t.clientHeight + 1;
            b.hidden = !(terpotong || det);
        });
    }
    document.addEventListener('click', (e) => {
        const b = e.target.closest('#bfs .bfs-more');
        if (!b) return;
        const w = b.closest('.bfs-kepw'), det = w.parentElement.querySelector('.bfs-det');
        const buka = !w.classList.contains('is-open');
        w.classList.toggle('is-open', buka);
        if (det) det.hidden = !buka;
        b.setAttribute('aria-expanded', buka ? 'true' : 'false');
        b.textContent = buka ? 'ringkas' : 'selengkapnya';
    });
    ukurKeperluan();
    let tUkur; window.addEventListener('resize', () => { clearTimeout(tUkur); tUkur = setTimeout(ukurKeperluan, 150); });
    if (document.fonts && document.fonts.ready) document.fonts.ready.then(ukurKeperluan);

    if (!tabel || !bisaUbah) return;

    function kabar(teks, galat) {
        const t = document.createElement('div');
        t.className = 'position-fixed bottom-0 end-0 m-3 alert alert-' + (galat ? 'danger' : 'success') + ' py-2 px-3 small shadow';
        t.style.zIndex = 2000; t.setAttribute('role', 'status'); t.textContent = teks;
        document.body.appendChild(t);
        setTimeout(() => t.remove(), galat ? 4500 : 1800);
    }

    // ── antrean simpan draf (berurutan, karena token berganti tiap POST) ──
    const antre = new Map();           // id → {kolom: nilai}
    let jalan = null;
    function kirimDraf(id, kolom) {
        antre.set(id, Object.assign(antre.get(id) || {}, kolom));
        if (!jalan) jalan = proses();
        return jalan;
    }
    async function proses() {
        while (antre.size) {
            const [id, kolom] = antre.entries().next().value;
            antre.delete(id);
            const badan = new URLSearchParams({ id });
            badan.append(NAMA, token());
            badan.append('tahun', root.dataset.tahun);
            const dept = document.querySelector('#bfsFormCatat input[name="dept"]'); if (dept) badan.append('dept', dept.value);
            Object.entries(kolom).forEach(([k, v]) => badan.append(k, v));
            const tr = tabel.querySelector(`tr[data-id="${id}"]`);
            try {
                const r = await fetch(URL_DRAF, { method: 'POST', body: badan, headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' } });
                const d = await r.json().catch(() => ({}));
                syncCsrf(d.csrf);
                if (!r.ok || !d.ok) throw new Error(d.pesan || 'Gagal');
                const s = tr?.querySelector('.bfs-simpan');
                if (s) { s.hidden = false; setTimeout(() => { s.hidden = true; }, 1500); }
            } catch (e) {
                kabar('Pilihan tidak tersimpan — ' + (e.message || 'muat ulang halaman') + '.', true);
            }
        }
        jalan = null;
    }

    const nilaiBaris = (tr) => {
        const v = {
            id: tr.dataset.id,
            pos: tr.querySelector('.bfs-pos')?.value || '',
            bulan: tr.querySelector('.bfs-bln')?.value || '',
            mall: tr.querySelector('.bfs-mal')?.value || '',
        };
        if (tr.querySelector('.bfs-cr-tema')) v.creative = tr.querySelector('.bfs-cr-item')?.value || '';   // tautan Creative (dept CCD)
        return v;
    };

    // ── Untuk Creative (dept CCD): Tema -> item, dengan usulan. Tema disaring menurut bulan terima/bulan beban ± 1. ──
    const dataCr = (() => { try { return JSON.parse(document.getElementById('bfsCreativeData')?.textContent || 'null'); } catch (e) { return null; } })();
    const geserBulan = (ym, n) => { const [y, m] = ym.split('-').map(Number); const d = new Date(y, m - 1 + n, 1); return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0'); };
    const BLN_PENDEK = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    const bulanLabel = (ym) => ym ? BLN_PENDEK[Number(ym.slice(5, 7)) - 1] + ' ' + ym.slice(0, 4) : '';
    const rpJt = (v) => v >= 1e6 ? 'Rp ' + (Math.round(v / 1e5) / 10).toString().replace('.', ',') + ' jt' : 'Rp ' + Number(v).toLocaleString('id-ID');
    const temaLabel = (t) => t.nama + (t.kind === 'bulanan' ? ' · ' + BLN_PENDEK[Number(t.bulan.slice(5, 7)) - 1] : '');   // nama dulu agar tak terpotong; Tema Bulanan diberi akhiran bulan
    const itemLabel = (it) => it.nama + (it.jenis || it.ukuran ? ' · ' + [it.jenis, it.ukuran].filter(Boolean).join(' ') : '') + (it.budget ? ' · ' + rpJt(it.budget) : '');
    const opsi = (v, t, pilih) => { const o = document.createElement('option'); o.value = v; o.textContent = t; o.title = t; if (pilih) o.selected = true; return o; };

    function temaUntuk(tr, ref) {
        const bt = tr.dataset.terima, bb = tr.querySelector('.bfs-bln')?.value || bt;
        const dari = geserBulan(bt < bb ? bt : bb, -1), sampai = geserBulan(bt > bb ? bt : bb, 1);
        return dataCr.kandidat.filter((t) => t.bulan === '' || (t.bulan >= dari && t.bulan <= sampai) || t.items.some((i) => i.ref === ref));
    }
    function badgeCr(tr, ref) {
        const b = tr.querySelector('.bfs-crbadge'); if (!b) return;
        const adaSaran = tr.dataset.crSaran && ref === tr.dataset.crSaran;
        b.hidden = !adaSaran;
        if (adaSaran) { b.setAttribute('data-bs-title', 'Usulan otomatis: ' + (tr.dataset.crAlasan || '')); b.setAttribute('aria-label', 'Usulan otomatis: ' + (tr.dataset.crAlasan || '')); window.bootstrap?.Tooltip.getInstance(b)?.dispose(); if (window.bootstrap) new bootstrap.Tooltip(b, { trigger: 'hover focus' }); }
    }
    function isiItemCr(tr, temaKey, ref) {
        const sel = tr.querySelector('.bfs-cr-item'); sel.innerHTML = '';
        const t = dataCr.kandidat.find((x) => x.key === temaKey);
        sel.hidden = !t;
        if (!t) return;
        sel.appendChild(opsi('', '— pilih item —', !ref));
        t.items.forEach((it) => sel.appendChild(opsi(it.ref, itemLabel(it), it.ref === ref)));
    }
    function isiCr(tr, ref) {
        const selT = tr.querySelector('.bfs-cr-tema'); selT.innerHTML = '';
        const daftar = temaUntuk(tr, ref);
        const tema = ref ? (dataCr.kandidat.find((t) => t.items.some((i) => i.ref === ref))?.key || '') : '';
        selT.appendChild(opsi('', '— tidak terkait Creative —', !tema));
        daftar.forEach((t) => selT.appendChild(opsi(t.key, temaLabel(t), t.key === tema)));
        isiItemCr(tr, tema, ref);
        selT.classList.toggle('is-kosong', !tema);
        badgeCr(tr, ref);
    }
    if (dataCr) {
        tabel.querySelectorAll('tr[data-id]').forEach((tr) => {
            if (!tr.querySelector('.bfs-cr-tema')) return;
            const awal = tr.dataset.crRef || (tr.dataset.crNone === '1' ? '' : (tr.dataset.crSaran || ''));
            isiCr(tr, awal);
        });
        tabel.addEventListener('change', (e) => {
            const tr = e.target.closest('tr[data-id]');
            if (!tr) return;
            if (e.target.classList.contains('bfs-cr-tema')) {
                isiItemCr(tr, e.target.value, '');
                e.target.classList.toggle('is-kosong', !e.target.value);
                badgeCr(tr, '');
                kirimDraf(tr.dataset.id, { creative: '' });
            } else if (e.target.classList.contains('bfs-cr-item')) {
                badgeCr(tr, e.target.value);
                kirimDraf(tr.dataset.id, { creative: e.target.value });
            } else if (e.target.classList.contains('bfs-bln') && tr.querySelector('.bfs-cr-tema')) {
                const ref = tr.querySelector('.bfs-cr-item')?.value || '';
                isiCr(tr, ref);   // daftar Tema mengikuti bulan beban yang baru; pilihan yang masih sah dipertahankan
            }
        });
    }

    tabel.addEventListener('change', (e) => {
        const tr = e.target.closest('tr[data-id]');
        if (!tr) return;
        if (e.target.classList.contains('bfs-pos')) {
            const rek = e.target.dataset.rek;
            tr.querySelector('.bfs-rekbadge').hidden = !(rek && e.target.value === rek && rek !== '0');
            e.target.classList.toggle('is-kosong', !e.target.value);
            kirimDraf(tr.dataset.id, { pos: e.target.value });
        } else if (e.target.classList.contains('bfs-bln')) {
            const a = tr.querySelector('.bfs-asal');
            if (a) a.hidden = e.target.value === tr.dataset.terima;
            kirimDraf(tr.dataset.id, { bulan: e.target.value });
        } else if (e.target.classList.contains('bfs-mal')) {
            kirimDraf(tr.dataset.id, { mall: e.target.value });
        } else if (e.target.classList.contains('bfs-cek') || e.target.id === 'bfsSemua') {
            if (e.target.id === 'bfsSemua') tabel.querySelectorAll('.bfs-cek').forEach((c) => { c.checked = e.target.checked; });
            hitungPilih();
        }
    });

    // ── Catat ──
    const formCatat = document.getElementById('bfsFormCatat');
    let terkirim = false;
    async function catat(daftar) {
        if (terkirim || !daftar.length) return;
        terkirim = true;
        document.querySelectorAll('#bfs .bfs-btn, #bfs select').forEach((b) => { b.disabled = true; });
        if (jalan) await jalan;                       // pastikan draf terakhir sudah di server
        formCatat.elements['data'].value = JSON.stringify(daftar);
        formCatat.submit();
    }
    tabel.addEventListener('click', (e) => {
        const b = e.target.closest('button');
        if (!b) return;
        const tr = b.closest('tr[data-id]');
        if (b.classList.contains('bfs-catat')) {
            const v = nilaiBaris(tr);
            if (!v.pos) {
                const s = tr.querySelector('.bfs-pos'); s.classList.add('is-salah'); s.focus();
                kabar('Pilih pos budget dulu untuk barang ini.', true);
                return;
            }
            catat([v]);
        } else if (b.classList.contains('bfs-abaikan')) {
            bukaAbaikan([tr.dataset.id]);
        }
    });

    // ── Massal ──
    const bar = document.getElementById('bfsBulk');
    const dipilih = () => [...tabel.querySelectorAll('.bfs-cek:checked')].map((c) => c.closest('tr'));
    function hitungPilih() {
        const n = dipilih().length;
        if (bar) { bar.hidden = n === 0; document.getElementById('bfsBulkN').textContent = n; }
        tabel.querySelectorAll('.bfs-cek').forEach((c) => c.closest('tr').classList.toggle('is-pilih', c.checked));
        const semua = document.getElementById('bfsSemua');
        if (semua) { const t = tabel.querySelectorAll('.bfs-cek').length; semua.checked = n > 0 && n === t; semua.indeterminate = n > 0 && n < t; }
    }
    document.getElementById('bfsCatatMassal')?.addEventListener('click', () => {
        const trs = dipilih().filter((tr) => tr.dataset.nol !== '1');
        catat(trs.map(nilaiBaris));   // baris tanpa pos dilewati server dengan pesan
    });
    document.getElementById('bfsAbaikanMassal')?.addEventListener('click', () => bukaAbaikan(dipilih().map((tr) => tr.dataset.id)));
    document.getElementById('bfsBatalPilih')?.addEventListener('click', () => { tabel.querySelectorAll('.bfs-cek').forEach((c) => { c.checked = false; }); hitungPilih(); });

    // ── Kelompok keperluan: tetapkan pos/bulan sekaligus. Hanya MENGISI pilihan tiap baris (lewat event change →
    //    draf per item tersimpan seperti biasa), jadi tiap baris tetap bisa diubah satu-satu sesudahnya. ──
    const barisGrup = (g) => [...tabel.querySelectorAll(`tr[data-grup-isi="${g}"]`)].filter((tr) => tr.querySelector('.bfs-pos'));
    // selalu memicu change (juga bila nilainya sama dengan rekomendasi) agar pilihan eksplisit kelompok tersimpan sebagai draf tiap item
    const setNilai = (sel, v) => { if (!sel) return false; sel.value = v; sel.dispatchEvent(new Event('change', { bubbles: true })); return true; };
    tabel.addEventListener('click', (e) => {
        const b = e.target.closest('.bfs-grup-terap, .bfs-grup-catat');
        if (!b) return;
        const h = b.closest('tr.bfs-grup'), baris = barisGrup(h.dataset.grup);
        if (b.classList.contains('bfs-grup-terap')) {
            const pos = h.querySelector('.bfs-grup-pos').value, bln = h.querySelector('.bfs-grup-bln').value;
            if (!pos && !bln) { kabar('Pilih pos dan/atau bulan untuk kelompok dulu.', true); return; }
            baris.forEach((tr) => { if (pos) setNilai(tr.querySelector('.bfs-pos'), pos); if (bln) setNilai(tr.querySelector('.bfs-bln'), bln); });
            kabar('Diterapkan ke ' + baris.length + ' item. Tiap baris tetap bisa diubah sendiri.');
        } else {
            const siap = baris.filter((tr) => tr.dataset.nol !== '1');
            const tanpa = siap.filter((tr) => !tr.querySelector('.bfs-pos').value);
            if (tanpa.length) { tanpa.forEach((tr) => tr.querySelector('.bfs-pos').classList.add('is-salah')); kabar(tanpa.length + ' item di kelompok ini belum punya pos — pilih dulu.', true); return; }
            catat(siap.map(nilaiBaris));
        }
    });
    tabel.addEventListener('change', (e) => {
        if (!e.target.classList.contains('bfs-grup-cek')) return;
        barisGrup(e.target.closest('tr.bfs-grup').dataset.grup).forEach((tr) => { const c = tr.querySelector('.bfs-cek'); if (c) c.checked = e.target.checked; });
        hitungPilih();
    });

    // ── Abaikan ──
    const modalEl = document.getElementById('mAbaikan');
    function bukaAbaikan(ids) {
        if (!ids.length || !modalEl) return;
        document.getElementById('bfsAbaikanN').textContent = ids.length;
        const w = document.getElementById('bfsAbaikanIds');
        w.innerHTML = '';
        ids.forEach((id) => { const i = document.createElement('input'); i.type = 'hidden'; i.name = 'ids[]'; i.value = id; w.appendChild(i); });
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }
    modalEl?.addEventListener('shown.bs.modal', () => document.getElementById('bfs-alasan').focus());
    document.getElementById('bfsFormAbaikan')?.addEventListener('submit', (e) => {
        if (e.target.dataset.kirim) { e.preventDefault(); return; }
        e.target.dataset.kirim = '1';
    });
})();
