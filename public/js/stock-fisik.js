/* ═══════════════════════════════════════════════════════════════════════
   MIC — Stok Fisik: cari / saring / urut sisi klien, buka-tutup baris.
   Pakai: <div data-sf-list> membungkus toolbar + tabel.
     [data-sf-search]   kolom cari (cocok ke data-q tiap .sf-item)
     [data-sf-filter]   tombol chip (data-sf-filter="status:low" | "all" | "attn")
     [data-sf-group]    <select> kelompok (cocok ke data-group)
     [data-sf-sort]     <select> urut, opsi value="kunci:asc|desc" (data-v-kunci pada item)
     th[data-sort]      judul kolom yang bisa diurut (data-type="num|text")
     [data-sf-count]    teks "x dari y"; [data-sf-empty] pesan kosong
     [data-sf-sum]      jumlah data-sum-<kunci> item terlihat (data-fmt="rp")
   ═══════════════════════════════════════════════════════════════════════ */
(function () {
    'use strict';
    var nf = new Intl.NumberFormat('id-ID');
    function fmt(n, k) { n = Math.round(n); return k === 'rp' ? 'Rp ' + nf.format(n) : nf.format(n); }
    function $$(s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); }
    function norm(s) { return String(s || '').toLowerCase().replace(/\s+/g, ' ').trim(); }

    function initList(root) {
        function own(sel) { return $$(sel, root).filter(function (e) { return e.closest('[data-sf-list]') === root; }); }
        function one(sel) { return own(sel)[0] || null; }
        var items = own('.sf-item');
        var parent = items.length ? items[0].parentNode : null;
        var anchor = one('.sf-anchor');
        var state = { q: '', filter: 'all', group: '', sortKey: root.getAttribute('data-sf-default-sort') || '', dir: 1, type: 'text' };
        var def = state.sortKey.split(':');
        if (def.length === 2) { state.sortKey = def[0]; state.dir = def[1] === 'desc' ? -1 : 1; }
        var search = one('[data-sf-search]');
        var selSort = one('[data-sf-sort]');
        var selGroup = one('[data-sf-group]');
        var chips = own('[data-sf-filter]');
        var heads = own('th[data-sort]');
        var countEl = one('[data-sf-count]');
        var emptyEl = one('[data-sf-empty]');
        var qBox = search ? search.closest('.sf-search') : null;

        function matchFilter(it) {
            var f = state.filter, st = it.getAttribute('data-status');
            if (f === 'all') return true;
            if (f === 'attn') return it.getAttribute('data-attn') === '1';
            if (f.indexOf('status:') === 0) return st === f.slice(7);
            if (f.indexOf('kstatus:') === 0) return it.getAttribute('data-kstatus') === f.slice(8);
            return true;
        }
        function apply() {
            var shown = 0, sums = {};
            items.forEach(function (it) {
                var ok = (!state.q || (it.getAttribute('data-q') || '').indexOf(state.q) !== -1) &&
                    matchFilter(it) && (!state.group || it.getAttribute('data-group') === state.group);
                it.hidden = !ok;
                if (!ok) { var d = it.querySelector('.sf-detail'); if (d) d.classList.remove('show'); }
                if (ok) {
                    shown++;
                    Array.prototype.forEach.call(it.attributes, function (a) {
                        if (a.name.indexOf('data-sum-') === 0) { var k = a.name.slice(9); sums[k] = (sums[k] || 0) + (parseFloat(a.value) || 0); }
                    });
                }
            });
            if (countEl) countEl.textContent = shown === items.length ? items.length + ' data' : shown + ' dari ' + items.length;
            if (emptyEl) emptyEl.hidden = shown !== 0 || items.length === 0;
            own('[data-sf-sum]').forEach(function (el) { el.textContent = fmt(sums[el.getAttribute('data-sf-sum')] || 0, el.getAttribute('data-fmt')); });
            if (qBox) qBox.classList.toggle('has-q', !!state.q);
            chips.forEach(function (c) { c.classList.toggle('is-on', c.getAttribute('data-sf-filter') === state.filter); });
            $$('[data-sf-kpi]', document).forEach(function (k) {
                if (root.contains(k) || k.getAttribute('data-sf-target') === root.id) k.classList.toggle('is-on', k.getAttribute('data-sf-kpi') === state.filter);
            });
        }
        function sort() {
            if (!state.sortKey || !parent) return;
            var key = state.sortKey, dir = state.dir, num = state.type === 'num';
            var arr = items.slice().sort(function (a, b) {
                var x = a.getAttribute('data-v-' + key) || '', y = b.getAttribute('data-v-' + key) || '';
                var r = num ? (parseFloat(x) || 0) - (parseFloat(y) || 0) : x.localeCompare(y, 'id', { numeric: true, sensitivity: 'base' });
                if (r === 0) r = (a.getAttribute('data-v-name') || '').localeCompare(b.getAttribute('data-v-name') || '', 'id', { numeric: true });
                return r * dir;
            });
            arr.forEach(function (it) { parent.insertBefore(it, anchor || null); });
            heads.forEach(function (h) {
                h.classList.remove('sort-asc', 'sort-desc');
                if (h.getAttribute('data-sort') === key) h.classList.add(dir === 1 ? 'sort-asc' : 'sort-desc');
            });
            if (selSort) {
                var v = key + ':' + (dir === 1 ? 'asc' : 'desc');
                if ([].some.call(selSort.options, function (o) { return o.value === v; })) selSort.value = v;
            }
        }
        function setType(key) {
            var h = heads.filter(function (x) { return x.getAttribute('data-sort') === key; })[0];
            var o = selSort && [].filter.call(selSort.options, function (x) { return x.value.split(':')[0] === key; })[0];
            state.type = (h && h.getAttribute('data-type')) || (o && o.getAttribute('data-type')) || 'text';
        }
        if (state.sortKey) setType(state.sortKey);

        if (search) {
            var t;
            search.addEventListener('input', function () { clearTimeout(t); t = setTimeout(function () { state.q = norm(search.value); apply(); }, 80); });
            var clr = qBox && qBox.querySelector('.sf-clear');
            if (clr) clr.addEventListener('click', function () { search.value = ''; state.q = ''; apply(); search.focus(); });
            search.addEventListener('keydown', function (e) { if (e.key === 'Escape') { search.value = ''; state.q = ''; apply(); } });
        }
        chips.forEach(function (c) { c.addEventListener('click', function () { state.filter = c.getAttribute('data-sf-filter'); apply(); }); });
        if (selGroup) selGroup.addEventListener('change', function () { state.group = selGroup.value; apply(); });
        if (selSort) selSort.addEventListener('change', function () {
            var p = selSort.value.split(':'); state.sortKey = p[0]; state.dir = p[1] === 'desc' ? -1 : 1; setType(p[0]); sort(); apply();
        });
        heads.forEach(function (h) {
            h.addEventListener('click', function () {
                var k = h.getAttribute('data-sort');
                if (state.sortKey === k) state.dir = -state.dir; else { state.sortKey = k; state.dir = h.getAttribute('data-first') === 'desc' ? -1 : 1; }
                setType(k); sort(); apply();
            });
        });
        // KPI yang bisa diklik (di luar root tapi menarget daftar ini)
        $$('[data-sf-kpi]').forEach(function (k) {
            if (k.getAttribute('data-sf-target') !== root.id) return;
            k.addEventListener('click', function () {
                var f = k.getAttribute('data-sf-kpi');
                state.filter = state.filter === f ? 'all' : f; apply();
                root.scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'start' });
            });
        });
        // Buka-tutup detail baris
        root.addEventListener('click', function (e) {
            var tg = e.target.closest('[data-sf-toggle]');
            if (!tg) return;
            var inter = e.target.closest('a, button, form, input, select, textarea, .dropdown-menu');
            if (inter && inter !== tg) return;
            var it = tg.closest('.sf-item');
            if (it && it.closest('[data-sf-list]') === root) toggle(it);
        });
        function toggle(it, force) {
            var d = it.querySelector('.sf-detail');
            if (!d) return;
            var open = force === undefined ? !d.classList.contains('show') : force;
            d.classList.toggle('show', open); it.classList.toggle('is-open', open);
            var tr = it.querySelector('[data-sf-toggle][aria-expanded]');
            if (tr) tr.setAttribute('aria-expanded', open ? 'true' : 'false');
        }
        root.sfToggle = toggle;
        // Bawa konteks (cari/filter/kelompok/urut) lewat tautan [data-sf-carry]
        function stateQS() {
            var q = new URLSearchParams();
            if (state.q) q.set('q', search ? search.value.trim() : state.q);
            if (state.filter !== 'all') q.set('f', state.filter);
            if (state.group) q.set('g', state.group);
            if (state.sortKey) q.set('s', state.sortKey + ':' + (state.dir === 1 ? 'asc' : 'desc'));
            return q.toString();
        }
        own('a[data-sf-carry]').concat($$('a[data-sf-carry="' + root.id + '"]')).forEach(function (a) {
            if (!a.getAttribute('data-base')) a.setAttribute('data-base', a.getAttribute('href'));
            function upd() { var b = a.getAttribute('data-base'), s = stateQS(); a.setAttribute('href', b + (s ? (b.indexOf('?') > -1 ? '&' : '?') + s : '')); }
            a.addEventListener('mousedown', upd); a.addEventListener('focus', upd); a.addEventListener('touchstart', upd, { passive: true }); a.addEventListener('click', upd);
        });
        // Mulai dari konteks yang dibawa di URL (?q= &f= &g= &s=)
        if (root.hasAttribute('data-sf-url')) {
            var u = new URLSearchParams(location.search);
            if (u.get('q') && search) { search.value = u.get('q'); state.q = norm(search.value); }
            if (u.get('f') && chips.some(function (c) { return c.getAttribute('data-sf-filter') === u.get('f'); })) state.filter = u.get('f');
            if (u.get('g') && selGroup && [].some.call(selGroup.options, function (o) { return o.value === u.get('g'); })) { state.group = u.get('g'); selGroup.value = state.group; }
            if (u.get('s')) { var sp = u.get('s').split(':'); if (/^[a-z]+$/.test(sp[0])) { state.sortKey = sp[0]; state.dir = sp[1] === 'desc' ? -1 : 1; setType(sp[0]); } }
        }
        sort(); apply();
        return { apply: apply };
    }

    document.addEventListener('DOMContentLoaded', function () {
        $$('[data-sf-list]').forEach(initList);
        // Dropdown aksi: posisi tetap agar tidak terpotong tabel/kartu
        if (window.bootstrap) $$('.page-sf [data-sf-dd]').forEach(function (el) {
            new bootstrap.Dropdown(el, { popperConfig: function (c) { c.strategy = 'fixed'; return c; } });
        });
        // Tempel offset topbar
        var pg = document.querySelector('.page-sf'), tb = document.querySelector('.topbar');
        if (pg && tb) pg.style.setProperty('--sf-top', tb.offsetHeight + 'px');
    });
})();
