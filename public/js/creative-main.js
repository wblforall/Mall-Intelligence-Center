/* MIC — Creative & Design: buka/tutup kartu, panel, pencarian, filter, urut (sisi klien),
   form tambah/ubah, dan summary bulanan (pencarian baris). */
(function () {
    'use strict';
    function $(s, c) { return (c || document).querySelector(s); }
    function $$(s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); }

    // ── Format uang di input .currency-input ───────────────────────────
    document.addEventListener('input', function (e) {
        var t = e.target;
        if (t && t.classList && t.classList.contains('currency-input')) {
            var n = parseInt(t.value.replace(/[^0-9]/g, ''), 10) || 0;
            t.value = n.toLocaleString('id-ID');
        }
    });

    /* ══════════════ Summary bulanan ══════════════ */
    var mon = document.getElementById('creMon');
    if (mon) {
        var mq = $('#cmQ', mon), mact = $('#cmActive', mon), mcnt = $('#cmCount', mon);
        var mrows = $$('tr[data-cm-row]', mon);
        var mapply = function () {
            var term = mq ? mq.value.trim().toLowerCase() : '', onlyAct = mact && mact.checked, shown = 0;
            mrows.forEach(function (r) {
                var ok = (!term || r.dataset.search.indexOf(term) > -1) && (!onlyAct || r.dataset.active === '1');
                r.hidden = !ok; if (ok) shown++;
            });
            $$('.cm-grp', mon).forEach(function (g) {
                var any = $$('tr[data-cm-row]', g).some(function (r) { return !r.hidden; });
                g.hidden = !any;
            });
            if (mcnt) mcnt.textContent = (term || onlyAct) ? shown + ' dari ' + mrows.length + ' item' : mrows.length + ' item';
            var nm = $('#cmNoMatch', mon); if (nm) nm.hidden = shown > 0 || !mrows.length;
        };
        if (mq) { var mt; mq.addEventListener('input', function () { clearTimeout(mt); mt = setTimeout(mapply, 120); }); }
        if (mact) mact.addEventListener('change', mapply);
        var mr = $('#cmReset', mon);
        if (mr) mr.addEventListener('click', function () { if (mq) mq.value = ''; if (mact) mact.checked = false; mapply(); });
        mapply();
    }

    /* ══════════════ Daftar per bulan -> Tema -> item ══════════════ */
    var root = document.getElementById('creIdx');
    if (!root) return;
    var KEY = 'creIdxOpen', KEYT = 'creIdxTema', STEP = 25;

    var cards = $$('.cr-item', root);
    var groups = $$('.cr-group', root);
    var temas = $$('.cr-tema', root);
    var months = $$('.cr-monthbox', root);
    var limit = {};      // per grup (tema+jenis): jumlah kartu yang boleh tampil
    groups.forEach(function (g) { limit[g.dataset.gid] = STEP; });

    // ── Ingatan buka/tutup (per tab browser) ──────────────────────────
    function load(k) { try { return JSON.parse(sessionStorage.getItem(k) || '[]'); } catch (e) { return []; } }
    function save(k, arr) { try { sessionStorage.setItem(k, JSON.stringify(arr)); } catch (e) { /* abaikan */ } }
    function saveOpen() { save(KEY, $$('.cr-item.is-open', root).map(function (c) { return c.id; })); }
    function saveTema() { save(KEYT, $$('.cr-tema.is-open', root).map(function (c) { return c.id; })); }

    function showPane(card, name) {
        var body = $('.cr-item-body', card);
        var has = false;
        $$('[data-cr-pane]', body).forEach(function (p) {
            var on = p.dataset.crPane === name; p.hidden = !on; if (on) has = true;
        });
        if (!has) { name = 'detail'; var d = $('[data-cr-pane="detail"]', body); if (d) d.hidden = false; }
        $$('[data-cr-tab]', body).forEach(function (b) { b.classList.toggle('is-on', b.dataset.crTab === name); b.setAttribute('aria-selected', b.dataset.crTab === name ? 'true' : 'false'); });
        card.dataset.cur = name;
    }
    function setOpen(card, open, pane) {
        var body = $('.cr-item-body', card), chev = $('.cr-chev', card);
        card.classList.toggle('is-open', open);
        body.hidden = !open;
        if (chev) chev.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (open) showPane(card, pane || card.dataset.cur || 'detail');
    }
    function setTemaOpen(t, open) {
        t.classList.toggle('is-open', open);
        var chev = $('.cr-tema-head .cr-chev', t); if (chev) chev.setAttribute('aria-expanded', open ? 'true' : 'false');
    }
    function setMonthOpen(m, open) {
        m.classList.toggle('is-open', open);
        var b = $('.cr-month-h', m); if (b) b.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    var remembered = load(KEY), rememberedT = load(KEYT);
    temas.forEach(function (t) { if (rememberedT.indexOf(t.id) > -1) setTemaOpen(t, true); });
    // "Tanpa Tema" tertutup bawaan; item dengan panel yang harus dibuka (perlu ditinjau) membuka induknya
    cards.forEach(function (c) {
        if (c.dataset.autoopen === '1') {
            setOpen(c, true, c.dataset.pane);
            var t = c.closest('.cr-tema'), m = c.closest('.cr-monthbox');
            if (t) setTemaOpen(t, true); if (m) setMonthOpen(m, true);
        } else if (remembered.indexOf(c.id) > -1) setOpen(c, true, 'detail');
    });

    // ── Tab Aktif / Terkunci ──────────────────────────────────────────
    var ptabs = $('#crPTabs'), curPane = 'aktif';
    function showPaneWrap(name) {
        curPane = name;
        $$('[data-cr-pane-wrap]', root).forEach(function (w) { w.hidden = w.dataset.crPaneWrap !== name; });
        if (ptabs) $$('button', ptabs).forEach(function (b) {
            var on = b.dataset.crPtab === name; b.classList.toggle('is-on', on); b.setAttribute('aria-selected', on ? 'true' : 'false');
        });
    }
    if (ptabs) ptabs.addEventListener('click', function (e) {
        var b = e.target.closest('[data-cr-ptab]'); if (b) { showPaneWrap(b.dataset.crPtab); if (window.CRE_APPLY) window.CRE_APPLY(false); }
    });

    root.addEventListener('click', function (e) {
        var t = e.target;
        // header bulan
        var mt = t.closest('[data-cr-month-toggle]');
        if (mt) { var mb = mt.closest('.cr-monthbox'); setMonthOpen(mb, !mb.classList.contains('is-open')); return; }
        // tab dalam kartu
        var tab = t.closest('[data-cr-tab]');
        if (tab) { showPane(tab.closest('.cr-item'), tab.dataset.crTab); return; }
        // tombol aksi utama: buka kartu pada panel tertentu
        var op = t.closest('[data-cr-open]');
        if (op) {
            var c1 = op.closest('.cr-item');
            if (c1.classList.contains('is-open') && c1.dataset.cur === op.dataset.crOpen) { setOpen(c1, false); }
            else { setOpen(c1, true, op.dataset.crOpen); if (!c1.dataset.sc) { c1.scrollIntoView({ block: 'nearest', behavior: 'smooth' }); } }
            saveOpen(); return;
        }
        // toggle form realisasi / insight
        var ar = t.closest('.toggle-add-realisasi, .toggle-add-insight');
        if (ar) {
            var isR = ar.classList.contains('toggle-add-realisasi');
            var f = document.getElementById((isR ? 'add-realisasi-' : 'add-insight-') + ar.dataset.iid);
            if (f) {
                f.hidden = !f.hidden;
                ar.innerHTML = f.hidden ? '<i class="bi bi-plus-lg"></i>Input' : '<i class="bi bi-x-lg"></i>Tutup';
                if (!f.hidden) { var fi = $('input:not([type=hidden])', f); if (fi) fi.focus(); }
            }
            return;
        }
        // panel alasan revisi / buka kembali (partial persetujuan)
        var rv = t.closest('.toggle-revisi, .toggle-buka');
        if (rv) {
            var el = document.getElementById((rv.classList.contains('toggle-revisi') ? 'revisi-' : 'buka-') + rv.dataset.iid);
            if (el) {
                el.classList.toggle('d-none');
                if (!el.classList.contains('d-none')) { var ta = $('textarea', el); if (ta) ta.focus(); }
            }
            return;
        }
        // kepala kartu Tema
        var th = t.closest('.cr-tema-head');
        if (th && !t.closest('[data-cr-notoggle], a, button.dropdown-item')) {
            var tm = th.closest('.cr-tema');
            setTemaOpen(tm, !tm.classList.contains('is-open')); saveTema(); return;
        }
        // kepala kartu item
        var head = t.closest('.cr-item-head');
        if (head && !t.closest('[data-cr-notoggle], a, button.dropdown-item')) {
            var card = head.closest('.cr-item');
            setOpen(card, !card.classList.contains('is-open')); saveOpen();
        }
    });

    // ── Modal tambah item ─────────────────────────────────────────────
    var addModal = document.getElementById('addItemModal');
    function addToggle() {
        if (!addModal) return;
        var tp = document.getElementById('addTipe').value;
        $$('[data-add-for]', addModal).forEach(function (el) { el.classList.toggle('d-none', el.dataset.addFor.split(' ').indexOf(tp) < 0); });
        if (window.CRE_CETAKEXTRA) window.CRE_CETAKEXTRA(document.getElementById('addItemForm'), { budgetWajib: tp === 'cetak', temaKey: document.getElementById('addTema').value });
        // Konten Digital: PIC wajib (isian tersembunyi tidak boleh menahan pengiriman), Design induk mengikuti Tema
        if (window.CRE_PICWAJIB) window.CRE_PICWAJIB(document.getElementById('addPic'), tp === 'digital');
        if (window.CRE_DIGITALDESIGN) window.CRE_DIGITALDESIGN(document.getElementById('addItemForm'), document.getElementById('addTema').value);
    }
    if (addModal) {
        document.getElementById('addTipe').addEventListener('change', addToggle);
        document.getElementById('addTema').addEventListener('change', addToggle);
        var openAdd = function (tipe, tema) {
            if (tipe) document.getElementById('addTipe').value = tipe;
            document.getElementById('addTema').value = tema || '';
            if (tema && document.getElementById('addTema').value !== tema) document.getElementById('addTema').value = '';
            addToggle();
            if (window.CRE_DIGITALFILL) window.CRE_DIGITALFILL(document.getElementById('addItemForm'), { temaKey: document.getElementById('addTema').value });
            bootstrap.Modal.getOrCreateInstance(addModal).show();
        };
        addModal.addEventListener('shown.bs.modal', function () { var n = document.getElementById('addNama'); if (n) n.focus(); });
        $$('[data-cr-add]', root).forEach(function (b) { b.addEventListener('click', function () { openAdd(b.dataset.crAdd, ''); }); });
        $$('[data-cr-add-tema]', root).forEach(function (b) { b.addEventListener('click', function () { openAdd('', b.dataset.crAddTema); }); });
        addToggle();
    }

    // ── Modal Tema Bulanan (tambah / ubah) ────────────────────────────
    var temaModal = document.getElementById('temaModal');
    if (temaModal) {
        var tf = document.getElementById('temaForm');
        var openTema = function (d) {
            d = d || {};
            tf.action = (window.CRE_BASE || '') + (d.id ? 'tema/' + d.id + '/edit' : 'tema/add');
            document.getElementById('temaModalTitle').textContent = d.id ? 'Ubah Tema Bulanan' : 'Tema Bulanan Baru';
            document.getElementById('temaNama').value = d.nama || '';
            document.getElementById('temaDesk').value = d.deskripsi || '';
            var ym = (d.bulan || '').match(/^(\d{4})-(\d{2})$/);
            var thn = document.getElementById('temaThn');
            if (ym) {
                if (!$('option[value="' + ym[1] + '"]', thn)) { var o = document.createElement('option'); o.value = o.textContent = ym[1]; thn.appendChild(o); }
                thn.value = ym[1]; document.getElementById('temaBln').value = ym[2];
            } else { document.getElementById('temaBln').value = ('0' + (new Date().getMonth() + 1)).slice(-2); thn.value = String(new Date().getFullYear()); }
            bootstrap.Modal.getOrCreateInstance(temaModal).show();
        };
        tf.addEventListener('submit', function () {
            document.getElementById('temaBulan').value = document.getElementById('temaThn').value + '-' + document.getElementById('temaBln').value;
        });
        $$('[data-cr-tema-new]', root).forEach(function (b) { b.addEventListener('click', function () { openTema(); }); });
        $$('.edit-tema-btn', root).forEach(function (b) { b.addEventListener('click', function () { openTema(b.dataset); }); });
        temaModal.addEventListener('shown.bs.modal', function () { document.getElementById('temaNama').focus(); });
    }

    // ── Modal pindahkan ke tema ───────────────────────────────────────
    var pModal = document.getElementById('pindahModal');
    if (pModal) {
        $$('.pindah-item-btn', root).forEach(function (b) {
            b.addEventListener('click', function () {
                document.getElementById('pindahForm').action = (window.CRE_BASE || '') + b.dataset.id + '/pindah';
                document.getElementById('pindahNama').textContent = b.dataset.nama;
                var sel = document.getElementById('pindahTema'); sel.value = b.dataset.tema || 'none';
                if (sel.value !== (b.dataset.tema || 'none')) sel.value = 'none';
                bootstrap.Modal.getOrCreateInstance(pModal).show();
            });
        });
    }

    // ── Modal ubah item ───────────────────────────────────────────────
    var DESAIN = ['master_design', 'motion_graphic'];
    var tipeLabels = { master_design: 'Design', motion_graphic: 'Motion Graphic', digital: 'Content Digital', cetak: 'Media Cetak', influencer: 'Influencer', media_prescon: 'Media Prescon' };
    $$('.edit-item-btn', root).forEach(function (btn) {
        btn.addEventListener('click', function () {
            var d = this.dataset, tipe = d.tipe, isDigital = tipe === 'digital';
            var g = function (id) { return document.getElementById(id); };
            g('editItemForm').action = (window.CRE_BASE || '') + d.id + '/edit';
            g('editTipeDisplay').textContent = tipeLabels[tipe] || tipe;
            g('editNama').value = d.nama;
            g('editTanggal').value = d.tanggal || '';
            g('editIsClosed').checked = d.isClosed === '1';
            g('editDeskripsi').value = d.deskripsi;
            g('editBudget').value = d.budget;
            g('editDeadline').value = d.deadline || '';
            g('editCatatan').value = d.catatan;
            var te = g('editTema'); if (te) { te.value = d.tema || 'none'; if (te.value !== (d.tema || 'none')) te.value = 'none'; }
            ['editPlatformDiv', 'editTakeDiv', 'editPicDiv', 'editDigitalDiv'].forEach(function (id) { g(id).classList.toggle('d-none', !isDigital); });
            if (window.CRE_PICWAJIB) window.CRE_PICWAJIB(g('editPic'), isDigital);
            if (isDigital && window.CRE_DIGITALFILL) window.CRE_DIGITALFILL(g('editItemForm'), { jenis: d.jenisMedia || '', ukuran: d.ukuran || '', mulai: d.pasangMulai || '', selesai: d.pasangSelesai || '',
                temaKey: g('editTema').value === 'none' ? '' : g('editTema').value, design: d.designRef || '' });
            g('editBudgetDiv').classList.toggle('d-none', DESAIN.indexOf(tipe) > -1);
            g('editCetakDiv').classList.toggle('d-none', tipe !== 'cetak');
            if (window.CRE_CETAKFILL) window.CRE_CETAKFILL(g('editJenisMedia'), g('editUkuran'), tipe === 'cetak' ? d.jenisMedia || '' : '', tipe === 'cetak' ? d.ukuran || '' : '');
            if (window.CRE_CETAKEXTRA) window.CRE_CETAKEXTRA(g('editItemForm'), { mulai: tipe === 'cetak' ? d.pasangMulai || '' : '', selesai: tipe === 'cetak' ? d.pasangSelesai || '' : '', temaKey: g('editTema').value === 'none' ? '' : g('editTema').value, design: d.designRef || '', budgetWajib: tipe === 'cetak' });
            var hasTarget = tipe === 'digital' || tipe === 'influencer';
            g('editTargetDiv').classList.toggle('d-none', !hasTarget);
            if (hasTarget) { g('editTargetReach').value = d.targetReach || ''; g('editTargetImpressions').value = d.targetImpressions || ''; }
            if (isDigital) {
                g('editPlatform').value = d.platform; g('editTanggalTake').value = d.tanggalTake;
                g('editJamTake').value = d.jamTake; g('editPic').value = d.pic;
            }
            var ti = g('editTipeHidden');
            if (!ti) { ti = document.createElement('input'); ti.type = 'hidden'; ti.name = 'tipe'; ti.id = 'editTipeHidden'; g('editItemForm').appendChild(ti); }
            ti.value = tipe;
            bootstrap.Modal.getOrCreateInstance(g('editItemModal')).show();
        });
    });

    var editTemaSel = document.getElementById('editTema');
    if (editTemaSel) editTemaSel.addEventListener('change', function () {
        if (window.CRE_DESIGNSYNC) window.CRE_DESIGNSYNC(document.getElementById('editItemForm'), editTemaSel.value === 'none' ? '' : editTemaSel.value, '');
        if (window.CRE_DIGITALDESIGN) window.CRE_DIGITALDESIGN(document.getElementById('editItemForm'), editTemaSel.value === 'none' ? '' : editTemaSel.value, '');
    });

    // ── Filter, cari, urut ────────────────────────────────────────────
    var q = $('#crQ'), fTipe = $('#crFTipe'), fStatus = $('#crFStatus'), fDl = $('#crFDl'), fSrc = $('#crFSrc'), fKonten = $('#crFKonten') || { value: '' }, fPic = $('#crFPic') || { value: '' }, fBulan = $('#crFBulan') || { value: '' },
        sortSel = $('#crSort'), countEl = $('#crCount'), noMatch = $('#crNoMatch'), seg = $('#creativeTabs');
    if (!q) return;
    var fase = '';
    var isRen = function (c) { return c.dataset.rencana === '1'; };
    var total = cards.filter(function (c) { return !isRen(c); }).length;   // item "Rencana" dihitung terpisah

    function monthOf(c) {   // bulan Tema; item tanpa Tema: bulan tanggal item
        var mb = c.closest('.cr-monthbox'), m = mb ? mb.dataset.month : '';
        return m && m !== 'none' ? m : (c.dataset.tgl || '').slice(0, 7);
    }
    function matches(c) {
        var d = c.dataset, term = q.value.trim().toLowerCase();
        if (term && d.search.indexOf(term) < 0 && d.name.toLowerCase().indexOf(term) < 0) return false;
        // item Rencana (paket awal, belum dikerjakan) tidak ikut filter fase / status / tenggat / sumber
        if (d.rencana === '1' && (fase || fStatus.value || fDl.value || fSrc.value || fKonten.value || fPic.value)) return false;
        if (fase && d.tab !== fase) return false;
        if (fBulan.value && monthOf(c) !== fBulan.value) return false;
        if (fTipe.value && d.tipe !== fTipe.value) return false;
        if (fSrc.value && d.src !== fSrc.value) return false;
        if (fKonten.value && d.konten !== fKonten.value) return false;   // status konten / PIC hanya ada pada Konten Digital
        if (fPic.value && d.pic !== fPic.value) return false;
        if (fStatus.value) {
            if (fStatus.value === 'closed') { if (d.closed !== '1') return false; }
            else if (d.status !== fStatus.value) return false;
        }
        if (fDl.value) {
            var k = d.dl;
            if (fDl.value === 'has') { if (k === 'none') return false; }
            else if (k !== fDl.value) return false;
        }
        return true;
    }
    function itemFilterOn() { return !!(fase || fBulan.value || fTipe.value || fStatus.value || fDl.value || fSrc.value || fKonten.value || fPic.value); }
    function isFiltered() { return !!(q.value.trim() || itemFilterOn()); }

    // ── KPI Budget & Serapan mengikuti tampilan (bulan / filter / fase / tab Aktif-Terkunci) ──
    // Tanpa filter di tab Aktif nilai tetap total semula dari server. Item Rencana tidak dihitung.
    var kp = { bl: $('#crKpiBL'), bv: $('#crKpiBV'), bs: $('#crKpiBS'), rl: $('#crKpiRL'), rv: $('#crKpiRV'), rs: $('#crKpiRS'), bar: $('#crKpiBar') };
    var kp0 = kp.bv ? { bl: kp.bl.textContent, bv: kp.bv.textContent, bs: kp.bs.textContent, rl: kp.rl.textContent, rv: kp.rv.textContent, rs: kp.rs.textContent,
                        rvc: kp.rv.className, barc: kp.bar.className, barw: kp.bar.firstElementChild.style.width, barh: kp.bar.hidden } : null;
    function rpf(n) { return 'Rp ' + Math.round(n).toLocaleString('id-ID'); }
    function updateKpi(filtered) {
        if (!kp0) return;
        var pane = curPane !== 'aktif';
        if (!filtered && !pane) {
            kp.bl.textContent = kp0.bl; kp.bv.textContent = kp0.bv; kp.bs.textContent = kp0.bs; kp.rl.textContent = kp0.rl;
            kp.rv.textContent = kp0.rv; kp.rv.className = kp0.rvc; kp.rs.textContent = kp0.rs;
            kp.bar.className = kp0.barc; kp.bar.firstElementChild.style.width = kp0.barw; kp.bar.hidden = kp0.barh;
            return;
        }
        var b = 0, bs = 0, r = 0, n = 0;
        cards.forEach(function (c) {
            if (isRen(c) || c.dataset.match !== '1') return;
            var w = c.closest('[data-cr-pane-wrap]'); if (w && w.dataset.crPaneWrap !== curPane) return;
            n++; b += +c.dataset.budget || 0;
            if (c.dataset.src === 's') { bs += +c.dataset.budget || 0; r += +c.dataset.real || 0; }
        });
        var opt = fBulan.options && fBulan.options[fBulan.selectedIndex];
        var ctx = fBulan.value ? (opt ? (opt.dataset.label || opt.textContent) : '') : '';
        var lain = !!(q.value.trim() || fase || fTipe.value || fStatus.value || fDl.value || fSrc.value || fKonten.value || fPic.value);
        var extra = ctx && lain ? ' (terfilter)' : '';
        if (!ctx) ctx = pane ? 'Tema Terkunci' : 'tampilan ini';
        kp.bl.textContent = 'Budget ' + ctx + extra;
        kp.bv.textContent = rpf(b);
        kp.bs.textContent = n + ' item · standalone + event';
        kp.rl.textContent = 'Realisasi ' + ctx + extra;
        kp.rv.textContent = rpf(r);
        var over = bs > 0 && r > bs, pct = bs > 0 ? Math.min(100, Math.round(r / bs * 100)) : null;
        kp.rv.className = kp0.rvc.replace(/cr-c-(bad|ok)/, over ? 'cr-c-bad' : 'cr-c-ok');
        kp.rs.textContent = pct !== null ? pct + '% dari budget standalone' : 'standalone only';
        kp.bar.hidden = pct === null;
        kp.bar.className = kp0.barc.replace(/cr-bar-(over|ok)/, over ? 'cr-bar-over' : 'cr-bar-ok');
        if (pct !== null) kp.bar.firstElementChild.style.width = pct + '%';
    }

    function apply(resetLimit) {
        if (resetLimit !== false) groups.forEach(function (g) { limit[g.dataset.gid] = STEP; });
        var matched = 0, matchedAll = 0, filtered = isFiltered(), term = q.value.trim().toLowerCase();
        groups.forEach(function (g) {
            var list = $('.cr-list', g), more = $('.cr-more', g), n = 0, m = 0, lim = limit[g.dataset.gid];
            $$('.cr-item', list).forEach(function (c) {
                var ok = matches(c); c.dataset.match = ok ? '1' : '0';
                if (ok) { m++; if (!isRen(c)) matched++; var vis = n < lim; c.hidden = !vis; if (vis) n++; } else { c.hidden = true; }
            });
            g.dataset.m = m; matchedAll += m;
            var all = $$('.cr-item', list).length;
            var gc = $('.js-gcount', g); if (gc) gc.textContent = filtered ? m + ' dari ' + all : all;
            if (more) {
                more.hidden = m <= n;
                var sp = $('[data-cr-more] span', more); if (sp) sp.textContent = 'Tampilkan ' + Math.min(STEP, m - n) + ' lagi (sisa ' + (m - n) + ')';
            }
            g.hidden = filtered && m === 0;
        });
        // kartu Tema: tampil bila ada item cocok (atau namanya cocok kata kunci tanpa filter item)
        var paneCount = { aktif: 0, terkunci: 0 };
        temas.forEach(function (t) {
            var m = 0;
            $$('.cr-group', t).forEach(function (g) { m += +g.dataset.m || 0; });
            var selfHit = !!term && !itemFilterOn() && (t.dataset.search.indexOf(term) > -1);
            var show = !filtered || m > 0 || selfHit;
            t.hidden = !show;
            t.classList.toggle('fopen', filtered && show && (m > 0));
            var rn = $('details.cr-ren', t); if (rn && filtered && m > 0) rn.open = true;
            t.dataset.show = show ? '1' : '0';
            if (show && t.dataset.kind !== 'none') paneCount[t.dataset.closed === '1' ? 'terkunci' : 'aktif']++;
        });
        months.forEach(function (mo) {
            var any = $$('.cr-tema', mo).some(function (t) { return t.dataset.show === '1'; });
            mo.hidden = filtered && !any;
            mo.classList.toggle('fopen', filtered && any);
            var n = $('.js-mn', mo); if (n) n.textContent = $$('.cr-tema', mo).filter(function (t) { return t.dataset.show === '1'; }).length;
        });
        if (ptabs) $$('button', ptabs).forEach(function (b) {
            var n = $('.n', b); if (n) n.textContent = filtered ? paneCount[b.dataset.crPtab] : n.dataset.n0;
        });
        var nRenAll = cards.filter(isRen).length, nRenHit = filtered ? matchedAll - matched : nRenAll;
        countEl.textContent = (filtered ? matched + ' dari ' + total + ' item' : total + ' item') + (nRenHit > 0 ? ' · ' + nRenHit + ' rencana' : '');
        noMatch.hidden = matchedAll > 0 || (total === 0 && nRenAll === 0) || !filtered;
        updateKpi(filtered);
        cards.forEach(function (c) { c.classList.toggle('is-hit', !!term && c.dataset.name.toLowerCase().indexOf(term) > -1 && c.dataset.match === '1'); });
    }

    function sortCards() {
        var mode = sortSel.value;
        $$('.cr-list', root).forEach(function (list) {
            var arr = $$('.cr-item', list);
            arr.sort(function (a, b) {
                var x = a.dataset, y = b.dataset;
                if (mode === 'nama') return x.name.toLowerCase().localeCompare(y.name.toLowerCase(), 'id');
                if (mode === 'budget') return (+y.budget) - (+x.budget);
                if (mode === 'real') return (+y.real) - (+x.real);
                if (mode === 'tanggal') return (y.tgl || '').localeCompare(x.tgl || '');
                if (mode === 'tenggat') {
                    // yang belum selesai dulu, tenggat terdekat dulu; tanpa tenggat di akhir
                    var ax = (x.dl === 'done' || !x.deadline) ? '9999-99' + (x.dl === 'done' ? '2' : '1') : x.deadline;
                    var ay = (y.dl === 'done' || !y.deadline) ? '9999-99' + (y.dl === 'done' ? '2' : '1') : y.deadline;
                    return ax.localeCompare(ay);
                }
                return (+x.ord) - (+y.ord) || (x.src < y.src ? 1 : -1);
            });
            arr.forEach(function (c) { list.appendChild(c); });
        });
        apply();
    }

    window.CRE_APPLY = apply;
    var tmr;
    q.addEventListener('input', function () { clearTimeout(tmr); tmr = setTimeout(apply, 120); });
    [fBulan, fTipe, fStatus, fDl, fSrc, fKonten, fPic].forEach(function (el) { if (el.addEventListener) el.addEventListener('change', apply); });
    sortSel.addEventListener('change', sortCards);
    $$('button', seg).forEach(function (b) {
        b.addEventListener('click', function () {
            fase = b.dataset.tab;
            $$('button', seg).forEach(function (x) { x.classList.toggle('is-on', x === b); });
            apply();
        });
    });
    function reset() {
        q.value = ''; fBulan.value = fTipe.value = fStatus.value = fDl.value = fSrc.value = fKonten.value = fPic.value = ''; fase = '';
        $$('button', seg).forEach(function (x) { x.classList.toggle('is-on', x.dataset.tab === ''); });
        apply();
    }
    $('#crReset').addEventListener('click', reset);
    $$('[data-cr-resetbtn]', root).forEach(function (b) { b.addEventListener('click', reset); });
    // KPI cepat
    $$('[data-cr-quick]', root).forEach(function (b) {
        b.addEventListener('click', function () {
            reset();
            if (b.dataset.crQuick === 'late') fDl.value = 'late';
            else { fStatus.value = 'review'; }
            apply();
            var t = $('#crTools'); if (t) t.scrollIntoView({ block: 'start', behavior: 'smooth' });
        });
    });
    // Tampilkan lebih banyak
    groups.forEach(function (g) {
        var more = $('.cr-more', g); if (!more) return;
        $('[data-cr-more]', more).addEventListener('click', function () { limit[g.dataset.gid] += STEP; apply(false); });
        $('[data-cr-all]', more).addEventListener('click', function () { limit[g.dataset.gid] = 1e6; apply(false); });
    });

    // ── Tutup Tema yang masih punya item Rencana: hapus / tetap / batal ──
    var rtModal = document.getElementById('rencanaTutupModal'), rtForm = null;
    if (rtModal) {
        root.addEventListener('submit', function (e) {
            var f = e.target.closest ? e.target.closest('form[data-cr-tutup-rencana]') : null;
            if (!f) return;
            e.preventDefault(); rtForm = f;
            document.getElementById('rencanaTutupN').textContent = f.dataset.crTutupRencana;
            document.getElementById('rencanaTutupNama').textContent = f.dataset.nama || '';
            bootstrap.Modal.getOrCreateInstance(rtModal).show();
        });
        $$('[data-rencana]', rtModal).forEach(function (b) {
            b.addEventListener('click', function () {
                if (!rtForm) return;
                var h = $('input[name="rencana"]', rtForm);
                if (!h) { h = document.createElement('input'); h.type = 'hidden'; h.name = 'rencana'; rtForm.appendChild(h); }
                h.value = b.dataset.rencana;
                b.disabled = true;
                HTMLFormElement.prototype.submit.call(rtForm);
            });
        });
        rtModal.addEventListener('hidden.bs.modal', function () { rtForm = null; $$('[data-rencana]', rtModal).forEach(function (b) { b.disabled = false; }); });
    }

    // ── Tautan #item-X-s / #tema-X (mis. dari Summary Bulanan) ───────
    function bukaInduk(el) {
        var wrap = el.closest('[data-cr-pane-wrap]'); if (wrap) showPaneWrap(wrap.dataset.crPaneWrap);
        var mo = el.closest('.cr-monthbox'); if (mo) setMonthOpen(mo, true);
        var t = el.closest('.cr-tema'); if (t) setTemaOpen(t, true);
        var rn = el.closest('details.cr-ren'); if (rn) rn.open = true;
    }
    function openHash() {
        var h = location.hash, pane = '';
        var pm = h.match(/^(#[\w-]+)~(\w+)$/);
        if (pm) { h = pm[1]; pane = pm[2]; }
        if (!h || !/^#(item|tema)-[\w-]+$/.test(h)) return;
        var el = document.querySelector(h);
        if (!el || !root.contains(el)) return;
        bukaInduk(el);
        if (el.classList.contains('cr-item')) {
            var g = el.closest('.cr-group'), idx = $$('.cr-item', g).indexOf(el);
            limit[g.dataset.gid] = Math.max(limit[g.dataset.gid], idx + 1);
            apply(false);
            setOpen(el, true, pane || el.dataset.pane || 'detail');
        }
        setTimeout(function () { el.scrollIntoView({ block: 'center' }); }, 80);
    }
    // ── Item yang baru ditambahkan: tampilkan & tandai ──
    function showJustAdded() {
        try { sessionStorage.removeItem('creJustAdded'); } catch (x) { /* abaikan */ }
    }
    apply();
    openHash();
    if (!location.hash) showJustAdded();
    window.addEventListener('hashchange', openHash);
})();
