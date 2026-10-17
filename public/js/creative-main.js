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

    /* ══════════════ Daftar item ══════════════ */
    var root = document.getElementById('creIdx');
    if (!root) return;
    var KEY = 'creIdxOpen', STEP = 25;

    var cards = $$('.cr-item', root);
    var groups = $$('.cr-group', root);
    var limit = {};      // per grup: jumlah kartu yang boleh tampil
    groups.forEach(function (g) { limit[g.dataset.tipe] = STEP; });

    // ── Buka/tutup kartu (ingat per tab) ──────────────────────────────
    function loadOpen() { try { return JSON.parse(sessionStorage.getItem(KEY) || '[]'); } catch (e) { return []; } }
    function saveOpen() {
        try { sessionStorage.setItem(KEY, JSON.stringify($$('.cr-item.is-open', root).map(function (c) { return c.id; }))); } catch (e) { /* abaikan */ }
    }
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
    var remembered = loadOpen();
    cards.forEach(function (c) {
        if (c.dataset.autoopen === '1') setOpen(c, true, c.dataset.pane);
        else if (remembered.indexOf(c.id) > -1) setOpen(c, true, 'detail');
    });

    root.addEventListener('click', function (e) {
        var t = e.target;
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
        // tambah item: toggle / batal
        var ta2 = t.closest('.toggle-add-item');
        if (ta2) { toggleAdd(ta2.dataset.tipe); return; }
        var ac = t.closest('[data-cr-addclose]');
        if (ac) { toggleAdd(ac.dataset.crAddclose, false); return; }
        // kepala kartu
        var head = t.closest('.cr-item-head');
        if (head && !t.closest('[data-cr-notoggle], a, button.dropdown-item')) {
            var card = head.closest('.cr-item');
            setOpen(card, !card.classList.contains('is-open')); saveOpen();
        }
    });

    // ── Form tambah item ──────────────────────────────────────────────
    function toggleAdd(tipe, force) {
        var el = document.getElementById('add-item-' + tipe);
        if (!el) return;
        var show = (typeof force === 'boolean') ? force : el.hidden;
        el.hidden = !show;
        var btn = $('.toggle-add-item[data-tipe="' + tipe + '"]', root);
        if (btn) btn.innerHTML = show ? '<i class="bi bi-x-lg"></i>Batal' : '<i class="bi bi-plus-lg"></i>Tambah Item';
        if (show) {
            var g = document.getElementById('grup-' + tipe); if (g) g.hidden = false;
            el.scrollIntoView({ block: 'center', behavior: 'smooth' });
            var inp = $('input[name=nama]', el); if (inp) setTimeout(function () { inp.focus({ preventScroll: true }); }, 250);
        }
    }
    $$('[data-cr-add]', root).forEach(function (b) {
        b.addEventListener('click', function () { toggleAdd(b.dataset.crAdd, true); });
    });

    // ── Modal ubah item ───────────────────────────────────────────────
    var tipeLabels = { master_design: 'Master Design', digital: 'Content Digital', cetak: 'Media Cetak', influencer: 'Influencer', media_prescon: 'Media Prescon' };
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
            ['editPlatformDiv', 'editTakeDiv', 'editPicDiv'].forEach(function (id) { g(id).classList.toggle('d-none', !isDigital); });
            g('editBudgetDiv').classList.toggle('d-none', tipe === 'master_design');
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

    // ── Filter, cari, urut ────────────────────────────────────────────
    var q = $('#crQ'), fTipe = $('#crFTipe'), fStatus = $('#crFStatus'), fDl = $('#crFDl'), fSrc = $('#crFSrc'),
        sortSel = $('#crSort'), countEl = $('#crCount'), noMatch = $('#crNoMatch'), seg = $('#creativeTabs');
    if (!q) return;
    var fase = '';
    var total = cards.length;

    function matches(c) {
        var d = c.dataset, term = q.value.trim().toLowerCase();
        if (term && d.search.indexOf(term) < 0 && d.name.toLowerCase().indexOf(term) < 0) return false;
        if (fase && d.tab !== fase) return false;
        if (fTipe.value && d.tipe !== fTipe.value) return false;
        if (fSrc.value && d.src !== fSrc.value) return false;
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
    function isFiltered() { return !!(q.value.trim() || fase || fTipe.value || fStatus.value || fDl.value || fSrc.value); }

    function apply(resetLimit) {
        if (resetLimit !== false) groups.forEach(function (g) { limit[g.dataset.tipe] = STEP; });
        var shownTotal = 0, matched = 0, filtered = isFiltered();
        groups.forEach(function (g) {
            var list = $('.cr-list', g), more = $('.cr-more', g), n = 0, m = 0;
            $$('.cr-item', list).forEach(function (c) {
                var ok = matches(c); c.dataset.match = ok ? '1' : '0';
                if (ok) { m++; var vis = n < limit[g.dataset.tipe]; c.hidden = !vis; if (vis) n++; } else { c.hidden = true; }
            });
            matched += m; shownTotal += n;
            var gc = $('.js-gcount', g); if (gc) gc.textContent = filtered ? m + ' dari ' + $$('.cr-item', list).length : $$('.cr-item', list).length;
            if (more) {
                more.hidden = m <= n;
                var sp = $('[data-cr-more] span', more); if (sp) sp.textContent = 'Tampilkan ' + Math.min(STEP, m - n) + ' lagi (sisa ' + (m - n) + ')';
            }
            var addOpen = $('.cr-add', g) && !$('.cr-add', g).hidden;
            var emptyMsg = $('.cr-groupempty', g);
            g.hidden = filtered && m === 0 && !addOpen;
            if (emptyMsg && !emptyMsg.closest('.cr-item-body')) emptyMsg.hidden = filtered;
        });
        countEl.textContent = filtered ? matched + ' dari ' + total + ' item' : total + ' item';
        noMatch.hidden = matched > 0 || total === 0 || !filtered;
        // tandai kartu yang cocok kata kunci (nama)
        var term = q.value.trim().toLowerCase();
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

    var tmr;
    q.addEventListener('input', function () { clearTimeout(tmr); tmr = setTimeout(apply, 120); });
    [fTipe, fStatus, fDl, fSrc].forEach(function (el) { el.addEventListener('change', apply); });
    sortSel.addEventListener('change', sortCards);
    $$('button', seg).forEach(function (b) {
        b.addEventListener('click', function () {
            fase = b.dataset.tab;
            $$('button', seg).forEach(function (x) { x.classList.toggle('is-on', x === b); });
            apply();
        });
    });
    function reset() {
        q.value = ''; fTipe.value = fStatus.value = fDl.value = fSrc.value = ''; fase = '';
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
    $$('.cr-group', root).forEach(function (g) {
        var more = $('.cr-more', g); if (!more) return;
        $('[data-cr-more]', more).addEventListener('click', function () { limit[g.dataset.tipe] += STEP; apply(false); });
        $('[data-cr-all]', more).addEventListener('click', function () { limit[g.dataset.tipe] = 1e6; apply(false); });
    });

    // ── Tautan #item-X-s dari Summary Bulanan ────────────────────────
    function openHash() {
        var h = location.hash;
        if (!h || !/^#item-[\w-]+$/.test(h)) return;
        var el = document.querySelector(h);
        if (!el || !root.contains(el)) return;
        var g = el.closest('.cr-group'), idx = $$('.cr-item', g).indexOf(el);
        limit[g.dataset.tipe] = Math.max(limit[g.dataset.tipe], idx + 1);
        apply(false);
        setOpen(el, true, el.dataset.pane || 'detail');
        setTimeout(function () { el.scrollIntoView({ block: 'center' }); }, 80);
    }
    // ── Item yang baru ditambahkan: tampilkan & tandai (daftar dipaginasi, item baru bisa di luar halaman pertama) ──
    root.addEventListener('submit', function (e) {
        var f = e.target;
        if (f && f.action && /creative\/add$/.test(f.action)) {
            try {
                sessionStorage.setItem('creJustAdded', JSON.stringify({ nama: (f.elements.nama || {}).value || '', tipe: (f.elements.tipe || {}).value || '' }));
            } catch (x) { /* abaikan */ }
        }
    });
    function showJustAdded() {
        var raw; try { raw = sessionStorage.getItem('creJustAdded'); sessionStorage.removeItem('creJustAdded'); } catch (x) { return; }
        if (!raw) return;
        var j; try { j = JSON.parse(raw); } catch (x) { return; }
        var hits = cards.filter(function (c) { return c.dataset.name === j.nama && c.dataset.tipe === j.tipe && c.dataset.src === 's'; });
        if (!hits.length) return;
        hits.sort(function (a, b) { return (+b.dataset.id) - (+a.dataset.id); });
        var el = hits[0], g = el.closest('.cr-group');
        limit[g.dataset.tipe] = 1e6; apply(false);
        setOpen(el, true, 'detail');
        el.classList.add('is-new');
        setTimeout(function () { el.scrollIntoView({ block: 'center', behavior: 'smooth' }); }, 120);
        setTimeout(function () { el.classList.remove('is-new'); }, 4000);
    }
    apply();
    openHash();
    if (!location.hash) showJustAdded();
    window.addEventListener('hashchange', openHash);
})();
