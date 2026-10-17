/* MIC — Modul per-event: buka/tutup kartu, panel isian, pencarian, filter, urutan (sisi klien).
   Dipakai bersama oleh Loyalty, VM, Creative, Exhibition, Sponsorship per-event.
   Markup: #evmRoot[data-key] > .ev-card[data-search,data-*] ; filter: [data-ev-filter="nama"] ;
   urut: #evSort (opsi: value=kunci data, data-dir="asc|desc", data-type="num|str") ;
   panel: tombol [data-ev-panel="idPanel"] ; buka kartu + panel: [data-ev-open="idPanel"]. */
(function () {
    'use strict';
    var root = document.getElementById('evmRoot');
    if (!root) return;

    function $(s, c) { return (c || root).querySelector(s); }
    function $$(s, c) { return Array.prototype.slice.call((c || root).querySelectorAll(s)); }
    var KEY = 'evmOpen:' + (root.getAttribute('data-key') || location.pathname);

    function loadOpen() { try { return JSON.parse(sessionStorage.getItem(KEY) || '[]'); } catch (e) { return []; } }
    function saveOpen() {
        try { sessionStorage.setItem(KEY, JSON.stringify($$('.ev-card.is-open').map(function (a) { return a.id; }))); } catch (e) { /* abaikan */ }
    }
    function setOpen(card, open) {
        var body = $('.ev-card-body', card), chev = $('.ev-chev', card);
        card.classList.toggle('is-open', open);
        if (body) body.hidden = !open;
        if (chev) chev.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    var cards = $$('.ev-card');
    var remembered = loadOpen();
    cards.forEach(function (c) { if (remembered.indexOf(c.id) > -1) setOpen(c, true); });

    // Panel isian (formulir sebaris)
    function setPanel(btn, panel, show) {
        panel.hidden = !show;
        $$('[data-ev-panel="' + panel.id + '"]').forEach(function (b) {
            b.classList.toggle('is-on', show);
            b.setAttribute('aria-expanded', show ? 'true' : 'false');
        });
    }

    root.addEventListener('click', function (e) {
        var op = e.target.closest('[data-ev-open]');
        if (op) {
            var pn = document.getElementById(op.getAttribute('data-ev-open'));
            if (pn) {
                var card = pn.closest('.ev-card');
                if (card) { setOpen(card, true); saveOpen(); }
                setPanel(op, pn, pn.hidden);
                if (!pn.hidden) {
                    var f = pn.querySelector('input:not([type=hidden]):not([type=file]):not([type=date]),select,textarea');
                    if (f) { try { f.focus({ preventScroll: true }); } catch (x) { f.focus(); } }
                    setTimeout(function () { pn.scrollIntoView({ block: 'nearest', behavior: 'smooth' }); }, 40);
                }
            }
            return;
        }
        var tp = e.target.closest('[data-ev-panel]');
        if (tp) {
            var panel = document.getElementById(tp.getAttribute('data-ev-panel'));
            if (panel) {
                setPanel(tp, panel, panel.hidden);
                if (!panel.hidden) {
                    var f2 = panel.querySelector('input:not([type=hidden]):not([type=file]):not([type=date]),select,textarea');
                    if (f2) { try { f2.focus({ preventScroll: true }); } catch (x2) { f2.focus(); } }
                }
            }
            return;
        }
        var head = e.target.closest('[data-ev-toggle]');
        if (!head || e.target.closest('[data-ev-notoggle], a, button:not(.ev-chev), input, select, textarea, form')) return;
        var c = head.closest('.ev-card');
        setOpen(c, !c.classList.contains('is-open'));
        saveOpen();
    });
    // Tombol chevron sendiri
    $$('.ev-chev').forEach(function (b) {
        b.addEventListener('click', function (e) {
            e.stopPropagation();
            var c = b.closest('.ev-card');
            setOpen(c, !c.classList.contains('is-open'));
            saveOpen();
        });
    });

    // ── Filter, cari, urut ───────────────────────────────────────────
    var q = $('#evQ'), sortSel = $('#evSort'), countEl = $('#evCount'), noMatch = $('#evNoMatch');
    var filters = $$('[data-ev-filter]');
    var unit = root.getAttribute('data-unit') || 'item';

    function apply() {
        var term = q ? q.value.trim().toLowerCase() : '';
        var shown = 0;
        cards.forEach(function (c) {
            var d = c.dataset, ok = true;
            if (term && (d.search || '').indexOf(term) < 0) ok = false;
            filters.forEach(function (f) {
                if (!ok || !f.value) return;
                var have = (d[f.getAttribute('data-ev-filter')] || '');
                if (f.hasAttribute('data-ev-multi')) ok = (',' + have + ',').indexOf(',' + f.value + ',') > -1;
                else ok = have === f.value;
            });
            c.hidden = !ok;
            if (ok) shown++;
            if (ok && term && (d.name || '').toLowerCase().indexOf(term) < 0 && !c.classList.contains('is-open') && (d.search || '').indexOf(term) > -1) {
                setOpen(c, true);   // cocok lewat isi dalam: buka agar terlihat (tak disimpan)
            }
        });
        $$('.ev-group').forEach(function (g) {
            var any = $$('.ev-card', g).some(function (c) { return !c.hidden; });
            var keep = g.hasAttribute('data-ev-keep') && !term && !filters.some(function (f) { return f.value; });
            g.hidden = !any && !keep;
        });
        var filtered = term || filters.some(function (f) { return f.value; });
        if (countEl) countEl.textContent = filtered ? shown + ' dari ' + cards.length + ' ' + unit : cards.length + ' ' + unit;
        if (noMatch) noMatch.hidden = shown > 0;
    }

    function sortCards() {
        if (!sortSel) return;
        var opt = sortSel.options[sortSel.selectedIndex], key = sortSel.value;
        var dir = opt.getAttribute('data-dir') === 'desc' ? -1 : 1, type = opt.getAttribute('data-type') || 'num';
        $$('.ev-list').forEach(function (list) {
            var arr = $$('.ev-card', list);
            arr.sort(function (a, b) {
                if (!key) return (+a.dataset.order) - (+b.dataset.order);
                var x = a.dataset[key] || '', y = b.dataset[key] || '', r;
                if (type === 'str') r = x.toLowerCase().localeCompare(y.toLowerCase(), 'id');
                else {
                    // nilai kosong selalu di akhir
                    if (x === '' && y !== '') return 1;
                    if (y === '' && x !== '') return -1;
                    r = (+x) - (+y);
                }
                return r * dir || ((+a.dataset.order) - (+b.dataset.order));
            });
            arr.forEach(function (c) { list.appendChild(c); });
        });
    }

    var t;
    if (q) q.addEventListener('input', function () { clearTimeout(t); t = setTimeout(apply, 120); });
    filters.forEach(function (el) { el.addEventListener('change', apply); });
    if (sortSel) sortSel.addEventListener('change', sortCards);

    var reset = $('#evReset');
    if (reset) reset.addEventListener('click', function () {
        if (q) q.value = '';
        filters.forEach(function (f) { f.value = ''; });
        apply();
    });
    var ex = $('#evExpand'), co = $('#evCollapse');
    if (ex) ex.addEventListener('click', function () { cards.forEach(function (c) { if (!c.hidden) setOpen(c, true); }); saveOpen(); });
    if (co) co.addEventListener('click', function () { cards.forEach(function (c) { setOpen(c, false); }); saveOpen(); });

    // Tabel realisasi panjang: tampilkan 8 baris pertama, sisanya lewat tombol
    var CAP = 8;
    $$('.ev-tbl-wrap').forEach(function (wrap) {
        var rows = $$('tbody tr', wrap);
        if (rows.length <= CAP + 2) return;
        var extra = rows.slice(CAP);
        extra.forEach(function (r) { r.hidden = true; });
        var bar = document.createElement('div');
        bar.className = 'ev-more';
        var btn = document.createElement('button');
        btn.type = 'button'; btn.className = 'ev-btn ev-btn-xs';
        function label(open) { btn.innerHTML = open ? '<i class="bi bi-chevron-up"></i>Ringkas' : '<i class="bi bi-chevron-down"></i>Tampilkan semua ' + rows.length + ' baris'; }
        label(false);
        btn.addEventListener('click', function () {
            var open = extra[0].hidden;
            extra.forEach(function (r) { r.hidden = !open; });
            label(open);
        });
        bar.appendChild(btn);
        wrap.parentNode.insertBefore(bar, wrap.nextSibling);
    });

    // Gambar bukti yang berkasnya hilang: tampilkan ikon, bukan gambar rusak
    $$('.ev-thumb img').forEach(function (img) {
        function lost() {
            var a = img.closest('.ev-thumb');
            if (!a) return;
            a.classList.add('is-lost');
            a.title = (a.title ? a.title + ' — ' : '') + 'berkas tidak ditemukan di server ini';
            img.remove();
            var i = document.createElement('i'); i.className = 'bi bi-image'; a.appendChild(i);
        }
        if (img.complete && img.naturalWidth === 0) lost(); else img.addEventListener('error', lost, { once: true });
    });

    // ── Tautan #item-X ───────────────────────────────────────────────
    function openHash() {
        var h = location.hash;
        if (!h) return;
        var el = document.querySelector(h.replace(/[^#\w-]/g, ''));
        if (!el || !root.contains(el)) return;
        var card = el.closest('.ev-card');
        if (card) { setOpen(card, true); setTimeout(function () { el.scrollIntoView({ block: 'center' }); }, 60); }
    }
    openHash();
    apply();
})();
