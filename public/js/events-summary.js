/* MIC — Events (Ringkasan, Budget, Other Cost, Content, Rundown): menu ⋯, buka/tutup, batasi daftar, cari/filter, ringkasan hari. */
(function () {
    'use strict';
    var root = document.querySelector('.page-evs');
    if (!root) return;
    function $(s, c) { return (c || root).querySelector(s); }
    function $$(s, c) { return Array.prototype.slice.call((c || root).querySelectorAll(s)); }

    // ── Menu ⋯ ──────────────────────────────────────────────────────
    function closeMenus(except) {
        $$('.ev-menu').forEach(function (m) {
            if (m === except) return;
            m.hidden = true;
            var b = m.parentNode.querySelector('[data-ev-menu]');
            if (b) b.setAttribute('aria-expanded', 'false');
        });
    }
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-ev-menu]');
        if (btn && root.contains(btn)) {
            var m = btn.parentNode.querySelector('.ev-menu');
            var open = m.hidden;
            closeMenus(m);
            m.hidden = !open;
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            return;
        }
        if (!e.target.closest('.ev-menu')) closeMenus(null);
    });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeMenus(null); });

    // ── Item yang bisa dibuka (Content) ─────────────────────────────
    var KEY = 'evsOpen:' + location.pathname;
    function loadOpen() { try { return JSON.parse(sessionStorage.getItem(KEY) || '[]'); } catch (e) { return []; } }
    function saveOpen() {
        try { sessionStorage.setItem(KEY, JSON.stringify($$('.ev-item.is-open').map(function (a) { return a.id; }))); } catch (e) { /* abaikan */ }
    }
    function setOpen(card, open) {
        var body = $('.ev-item-body', card), chev = $('.ev-chev', card);
        card.classList.toggle('is-open', open);
        if (body) body.hidden = !open;
        if (chev) chev.setAttribute('aria-expanded', open ? 'true' : 'false');
    }
    var items = $$('.ev-item');
    var rem = loadOpen();
    items.forEach(function (c) { if (rem.indexOf(c.id) > -1) setOpen(c, true); });
    if (location.hash) { var t = document.getElementById(location.hash.slice(1)); if (t && t.classList.contains('ev-item')) setOpen(t, true); }
    root.addEventListener('click', function (e) {
        var head = e.target.closest('[data-ev-toggle]');
        if (!head || e.target.closest('a, button:not(.ev-chev), form, input, select, textarea, [data-ev-notoggle]')) return;
        var card = head.closest('.ev-item');
        setOpen(card, !card.classList.contains('is-open'));
        saveOpen();
    });
    var allBtn = $('[data-ev-allopen]');
    if (allBtn) allBtn.addEventListener('click', function () {
        var anyClosed = items.some(function (c) { return !c.classList.contains('is-open') && !c.hidden; });
        items.forEach(function (c) { if (!c.hidden) setOpen(c, anyClosed); });
        allBtn.querySelector('span').textContent = anyClosed ? 'Tutup semua' : 'Buka semua';
        saveOpen();
    });

    // ── Batasi daftar panjang ───────────────────────────────────────
    $$('[data-ev-limit]').forEach(function (list) {
        var lim = parseInt(list.getAttribute('data-ev-limit'), 10) || 8;
        var rows = Array.prototype.slice.call(list.children).filter(function (c) { return !c.classList.contains('bg-light') && !c.classList.contains('ev-keep'); });
        if (rows.length <= lim + 1) return;
        var extra = rows.slice(lim);
        extra.forEach(function (r) { r.hidden = true; });
        var b = document.createElement('button');
        b.type = 'button'; b.className = 'ev-lim-btn';
        var more = true;
        function label() { b.innerHTML = more ? 'Tampilkan ' + extra.length + ' lainnya <i class="bi bi-chevron-down"></i>' : 'Ringkas daftar <i class="bi bi-chevron-up"></i>'; }
        label();
        b.addEventListener('click', function () { more = !more; extra.forEach(function (r) { r.hidden = more; }); label(); });
        var foot = Array.prototype.slice.call(list.children).filter(function (c) { return c.classList.contains('bg-light'); })[0];
        if (foot) list.insertBefore(b, foot); else list.appendChild(b);
    });

    // ── Cari & filter (Content, Bulanan) ────────────────────────────
    var q = $('#evQ'), fT = $('#evFTipe'), countEl = $('#evCount'), noMatch = $('#evNoMatch');
    if (q) {
        var rowsF = $$('[data-search]');
        var apply = function () {
            var term = q.value.trim().toLowerCase(), shown = 0;
            rowsF.forEach(function (r) {
                var ok = true;
                if (term && r.getAttribute('data-search').indexOf(term) < 0) ok = false;
                if (ok && fT && fT.value && r.getAttribute('data-tipe') !== fT.value) ok = false;
                r.hidden = !ok; if (ok) shown++;
            });
            $$('[data-ev-section]').forEach(function (s) {
                var vis = $$('[data-search]', s).some(function (r) { return !r.hidden; });
                s.hidden = !vis && (term || (fT && fT.value));
            });
            if (countEl) countEl.textContent = shown + ' dari ' + rowsF.length;
            if (noMatch) noMatch.hidden = shown > 0;
        };
        q.addEventListener('input', apply);
        if (fT) fT.addEventListener('change', apply);
        apply();
    }

    // ── Baris tabel yang bisa diklik ────────────────────────────────
    root.addEventListener('click', function (e) {
        var tr = e.target.closest('tr[data-href]');
        if (!tr || e.target.closest('a, button, input, select')) return;
        location.href = tr.getAttribute('data-href');
    });

    // ── Rundown: ringkasan per hari + tambah sesi ───────────────────
    var rd = $('#rundownBody');
    if (rd) {
        var box = $('#evDays');
        var renderDays = function () {
            if (!box) return;
            var map = {}, order = [];
            $$('tr', rd).forEach(function (tr) {
                var sel = tr.querySelector('select[name="tanggal[]"]');
                var lab = sel ? sel.options[sel.selectedIndex].text : (tr.querySelector('.col-tgl') || {}).textContent;
                lab = (lab || '').trim(); if (!lab) return;
                var short = lab.split(' — ')[0];
                if (!(short in map)) { map[short] = { n: 0, d: lab.split(' — ')[1] || '' }; order.push(short); }
                map[short].n++;
            });
            box.innerHTML = '';
            order.forEach(function (k) {
                var d = document.createElement('div'); d.className = 'ev-daychip';
                d.innerHTML = '<b></b><span></span>';
                d.firstChild.textContent = k; d.lastChild.textContent = map[k].d + ' · ' + map[k].n + ' sesi';
                box.appendChild(d);
            });
            var c = $('#evSesiCount'); if (c) c.textContent = $$('tr', rd).length;
        };
        renderDays();
        rd.addEventListener('change', renderDays);
        new MutationObserver(renderDays).observe(rd, { childList: true });
    }
})();
