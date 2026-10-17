/* ═══════════════════════════════════════════════════════════════
   MIC — Sidebar: akordeon tunggal, pencarian, favorit, mode rail, laci mobile
   Dimuat sinkron tepat setelah markup #sidebar di layouts/main.php.
   Membangun ulang DOM dari tautan yang SUDAH dirender server (tautan & hak
   akses tidak diubah — simpul <a> yang sama hanya dipindah & dibungkus).
   ═══════════════════════════════════════════════════════════════ */
(function () {
    'use strict';
    var sb = document.getElementById('sidebar');
    var nav = sb && sb.querySelector('.nav-section');
    if (!nav) return;

    var html = document.documentElement;
    var DESK = window.matchMedia('(min-width: 992px)');
    var REDUCE = window.matchMedia('(prefers-reduced-motion: reduce)');
    var uid = sb.getAttribute('data-uid') || '0';
    var FAV_MAX = 8;
    var KEY_MINI = 'micSidebarMini';
    var KEY_OPEN = 'micNavOpen';
    var KEY_FAV = 'micSidebarFav:' + uid;

    function lsGet(k) { try { return localStorage.getItem(k); } catch (e) { return null; } }
    function lsSet(k, v) { try { localStorage.setItem(k, v); } catch (e) {} }
    function el(tag, cls, attrs) {
        var n = document.createElement(tag);
        if (cls) n.className = cls;
        if (attrs) for (var k in attrs) n.setAttribute(k, attrs[k]);
        return n;
    }
    function icon(cls) { var i = document.createElement('i'); i.className = 'bi ' + cls; return i; }
    function slug(s) { return s.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, ''); }
    function normChar(c) { return c.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase(); }
    function norm(s) { return (s || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().replace(/\s+/g, ' ').trim(); }
    function isRail() { return html.classList.contains('sb-mini') && DESK.matches; }

    var ICONS = {
        'traffic': 'bi-person-walking', 'parkir': 'bi-p-circle-fill', 'pest-control': 'bi-bug-fill',
        'pemantauan-ai': 'bi-robot', 'human-resources': 'bi-people-fill',
        'people-development': 'bi-mortarboard-fill', 'talent': 'bi-gem', 'penilaian': 'bi-clipboard-check-fill',
        'legal': 'bi-shield-check', 'progress-report': 'bi-kanban-fill', 'budget': 'bi-wallet2',
        'portal': 'bi-grid-3x3-gap-fill', 'system': 'bi-cpu-fill', 'admin': 'bi-gear-wide-connected'
    };

    /* ── 1. Baca model dari markup server ─────────────────────── */
    function labelOf(a) {
        var t = '';
        a.childNodes.forEach(function (n) {
            if (n.nodeType === 3) t += n.textContent;
            else if (n.nodeType === 1 && n.tagName !== 'I' && !n.classList.contains('badge')) t += n.textContent;
        });
        return t.replace(/\s+/g, ' ').trim();
    }
    function iconOf(a) {
        var i = a.querySelector(':scope > i.bi');
        if (!i) return 'bi-circle';
        var m = (i.className.match(/bi-[\w-]+/g) || []).filter(function (c) { return c !== 'bi'; });
        return m[0] || 'bi-circle';
    }

    var items = [];   // urutan tampil: {kind:'link', a} | {kind:'group', ...}
    var cur = null;
    Array.prototype.slice.call(nav.children).forEach(function (n) {
        if (n.classList.contains('nav-label')) {
            var title = n.textContent.trim(), key = slug(title);
            if (key === 'main') { cur = null; return; }
            cur = { kind: 'group', key: key, title: title, icon: ICONS[key] || 'bi-folder2-open', links: [], headA: null, hint: false };
            items.push(cur);
        } else if (n.matches('a[data-sb-toggle]')) {
            var panel = document.getElementById(n.getAttribute('data-sb-toggle'));
            var t = labelOf(n);
            items.push({
                kind: 'group', key: slug(t), title: t, icon: iconOf(n), headA: n,
                links: panel ? Array.prototype.slice.call(panel.querySelectorAll('a')) : [],
                hint: n.getAttribute('aria-expanded') === 'true',
                badge: n.querySelector('.badge')
            });
            cur = null;
        } else if (n.tagName === 'A') {
            if (cur) cur.links.push(n); else items.push({ kind: 'link', a: n });
        } else if (n.classList.contains('subnav')) {
            if (cur) Array.prototype.push.apply(cur.links, n.querySelectorAll('a'));
        }
    });
    // Bagian berisi satu tautan → jadi tautan tunggal (tanpa judul + klik ekstra)
    items = items.map(function (it) {
        if (it.kind === 'group' && !it.headA && it.links.length === 1) return { kind: 'link', a: it.links[0] };
        return it;
    }).filter(function (it) { return it.kind === 'link' || it.links.length > 0; });

    /* ── 2. Bangun DOM baru ───────────────────────────────────── */
    var favOpt = [];          // tautan asli semua daun (untuk peta favorit)
    var allRows = [];         // {row, a, label, group}
    var groups = [];          // {el, head, panel, key, title, rows}
    var topLinks = [];        // baris tautan tingkat atas

    function prepLeaf(a) {
        a.classList.remove('nav-link');
        a.classList.add('sb-link');
        var label = labelOf(a), ic = iconOf(a), badge = a.querySelector('.badge');
        var ico = el('span', 'sb-ico', { 'aria-hidden': 'true' }); ico.appendChild(icon(ic));
        var tx = el('span', 'sb-text'); tx.textContent = label;
        a.textContent = '';
        a.appendChild(ico); a.appendChild(tx);
        if (badge) { badge.classList.remove('ms-auto'); badge.classList.add('sb-badge'); a.appendChild(badge); }
        a.setAttribute('data-label', label);
        return label;
    }
    function mkStar() {
        var b = el('button', 'sb-star', { type: 'button', 'aria-pressed': 'false' });
        b.appendChild(icon('bi-star'));
        return b;
    }
    function mkRow(a, ownGroup) {
        var label = prepLeaf(a);
        var row = el('div', 'sb-row');
        var lvl = a.getAttribute('data-lvl'); if (lvl) row.setAttribute('data-lvl', lvl);
        row.appendChild(a); row.appendChild(mkStar());
        if (a.classList.contains('active')) { row.classList.add('is-active'); a.setAttribute('aria-current', 'page'); }
        var rec = { row: row, a: a, label: label, group: ownGroup || null };
        allRows.push(rec);
        return rec;
    }

    var favBox = el('div', 'sb-fav'); favBox.hidden = true;
    var frag = document.createDocumentFragment();
    frag.appendChild(favBox);

    items.forEach(function (it) {
        if (it.kind === 'link') {
            var r = mkRow(it.a, null); topLinks.push(r); frag.appendChild(r.row); return;
        }
        var g = { key: it.key, title: it.title, rows: [], hint: it.hint };
        var box = el('div', 'sb-group', { 'data-key': it.key });
        var pid = 'sbp-' + it.key;
        var head;
        if (it.headA) {
            head = it.headA; head.classList.remove('nav-link');
            head.textContent = '';
            head.setAttribute('role', 'button');
        } else {
            head = el('button', '', { type: 'button' });
        }
        head.classList.add('sb-head');
        head.setAttribute('aria-expanded', 'false'); head.setAttribute('aria-controls', pid);
        var ico = el('span', 'sb-ico', { 'aria-hidden': 'true' }); ico.appendChild(icon(it.icon));
        var tx = el('span', 'sb-text'); tx.textContent = it.title;
        head.appendChild(ico); head.appendChild(tx);
        if (it.badge) { it.badge.classList.add('sb-badge'); head.appendChild(it.badge); }
        var hasBadge = it.links.some(function (a) { return a.querySelector('.badge'); });
        if (hasBadge) head.appendChild(el('span', 'sb-dot', { 'aria-hidden': 'true', title: 'Ada yang perlu perhatian' }));
        head.appendChild(icon('bi-chevron-down sb-caret'));
        head.setAttribute('data-label', it.title);

        var panel = el('div', 'sb-panel', { id: pid, role: 'group', 'aria-label': it.title });
        var pin = el('div', 'sb-panel-in'), pbox = el('div', 'sb-panel-box');
        var ft = el('div', 'sb-fly-title'); ft.textContent = it.title; pbox.appendChild(ft);
        it.links.forEach(function (a) { var r = mkRow(a, g); g.rows.push(r); pbox.appendChild(r.row); });
        pin.appendChild(pbox); panel.appendChild(pin);
        box.appendChild(head); box.appendChild(panel);
        g.el = box; g.head = head; g.panel = panel;
        g.hasActive = g.rows.some(function (r) { return r.row.classList.contains('is-active'); });
        if (it.headA && it.headA.classList.contains('active')) g.hasActive = true;
        if (g.hasActive) box.classList.add('has-active');
        groups.push(g); frag.appendChild(box);
    });

    var empty = el('div', 'sb-empty', { role: 'status' }); empty.hidden = true;
    frag.appendChild(empty);
    nav.textContent = '';
    nav.appendChild(frag);
    // Sumber submenu lama sudah kosong (tautan dipindah)
    allRows.forEach(function (r) { favOpt.push(r.a); });

    /* ── 3. Akordeon tunggal ──────────────────────────────────── */
    function setOpen(g, open) {
        g.el.classList.toggle('is-open', open);
        g.head.setAttribute('aria-expanded', open ? 'true' : 'false');
    }
    function openOnly(g) { groups.forEach(function (x) { setOpen(x, x === g); }); }
    function storeOpen(key) { lsSet(KEY_OPEN, key || ''); }

    groups.forEach(function (g) {
        function toggle(e) {
            if (e && (e.ctrlKey || e.metaKey || e.shiftKey || e.button > 0)) return;   // buka di tab baru tetap bekerja
            if (e) e.preventDefault();
            if (isRail()) {
                var on = !g.el.classList.contains('is-fly');
                groups.forEach(function (x) { x.el.classList.remove('is-fly'); });
                if (on) { g.el.classList.add('is-fly'); placeFly(g); }
                return;
            }
            var willOpen = !g.el.classList.contains('is-open');
            groups.forEach(function (x) { setOpen(x, willOpen && x === g); });
            storeOpen(willOpen ? g.key : '');
        }
        g.head.addEventListener('click', toggle);
        g.head.addEventListener('keydown', function (e) {
            if (e.key === ' ' && g.head.tagName === 'A') { e.preventDefault(); toggle(); }
        });
    });

    // Terbuka saat muat: grup berisi halaman aktif → petunjuk server → pilihan terakhir
    (function initOpen() {
        var target = groups.filter(function (g) { return g.hasActive; })[0]
            || groups.filter(function (g) { return g.hint; })[0];
        if (!target) {
            var k = lsGet(KEY_OPEN);
            target = k ? groups.filter(function (g) { return g.key === k; })[0] : null;
        }
        if (target) openOnly(target);
    })();

    /* ── 4. Favorit ───────────────────────────────────────────── */
    var live = document.getElementById('sbLive');
    function say(msg) { if (live) { live.textContent = ''; setTimeout(function () { live.textContent = msg; }, 30); } }
    var favs = [];
    try { favs = JSON.parse(lsGet(KEY_FAV) || '[]'); if (!Array.isArray(favs)) favs = []; } catch (e) { favs = []; }
    function saveFavs() { lsSet(KEY_FAV, JSON.stringify(favs)); }
    function hrefMap() { var m = {}; allRows.forEach(function (r) { if (!m[r.a.href]) m[r.a.href] = r; }); return m; }

    function refreshStars() {
        allRows.forEach(function (r) {
            var on = favs.indexOf(r.a.href) > -1, b = r.row.querySelector('.sb-star');
            b.classList.toggle('on', on); b.setAttribute('aria-pressed', on ? 'true' : 'false');
            b.firstChild.className = 'bi ' + (on ? 'bi-star-fill' : 'bi-star');
            var t = (on ? 'Lepas dari Favorit: ' : 'Sematkan ke Favorit: ') + r.label;
            b.setAttribute('aria-label', t); b.title = on ? 'Lepas dari Favorit' : 'Sematkan ke Favorit';
        });
    }
    function renderFav() {
        var m = hrefMap();
        favs = favs.filter(function (h) { return m[h]; });       // tautan yang tak lagi boleh diakses disingkirkan
        favBox.textContent = '';
        if (!favs.length) { favBox.hidden = true; refreshStars(); return; }
        var t = el('div', 'sb-fav-title'); t.appendChild(icon('bi-star-fill')); t.appendChild(document.createTextNode('Favorit'));
        favBox.appendChild(t);
        favs.slice(0, FAV_MAX).forEach(function (h) {
            var src = m[h].a;
            var a = el('a', 'sb-link', { href: src.getAttribute('href'), 'data-label': m[h].label });
            var ico = el('span', 'sb-ico', { 'aria-hidden': 'true' }); ico.appendChild(icon(iconOf2(src)));
            var tx = el('span', 'sb-text'); tx.textContent = m[h].label;
            a.appendChild(ico); a.appendChild(tx);
            var row = el('div', 'sb-row sb-favrow');
            if (src.classList.contains('active')) row.classList.add('is-active');
            row.appendChild(a);
            var s = mkStar(); s.classList.add('on'); s.firstChild.className = 'bi bi-star-fill'; s.setAttribute('aria-pressed', 'true');
            s.setAttribute('aria-label', 'Lepas dari Favorit: ' + m[h].label); s.title = 'Lepas dari Favorit';
            s.setAttribute('data-href', src.href);
            row.appendChild(s); favBox.appendChild(row);
        });
        favBox.hidden = false; refreshStars();
    }
    function iconOf2(a) { var i = a.querySelector('.sb-ico i'); var m = i && i.className.match(/bi-[\w-]+/g); return (m && m.filter(function (c) { return c !== 'bi'; })[0]) || 'bi-circle'; }

    nav.addEventListener('click', function (e) {
        var b = e.target.closest('.sb-star');
        if (!b) return;
        e.preventDefault(); e.stopPropagation();
        var h = b.getAttribute('data-href') || (b.parentNode.querySelector('a') || {}).href;
        if (!h) return;
        var i = favs.indexOf(h);
        if (i > -1) { favs.splice(i, 1); say('Dilepas dari Favorit'); }
        else {
            if (favs.length >= FAV_MAX) { say('Favorit penuh, maksimal ' + FAV_MAX + '. Lepas satu dulu.'); b.title = 'Favorit penuh (maks ' + FAV_MAX + ')'; return; }
            favs.push(h); say('Disematkan ke Favorit');
        }
        saveFavs(); renderFav();
    });
    renderFav();

    /* ── 5. Pencarian ─────────────────────────────────────────── */
    var input = document.getElementById('sbSearchInput');
    var clearBtn = document.getElementById('sbSearchClear');
    var searchBox = input && input.parentNode;
    var searchBtn = document.getElementById('sbSearchBtn');
    var firstHit = null;

    function highlight(node, text, tokens) {
        node.textContent = '';
        var low = '', map = [];
        for (var i = 0; i < text.length; i++) {
            var n = normChar(text[i]);
            for (var j = 0; j < n.length; j++) { low += n[j]; map.push(i); }
        }
        var mark = new Array(text.length + 1).fill(false);
        tokens.forEach(function (tk) {
            var p = low.indexOf(tk);
            while (p > -1) {
                for (var k = p; k < p + tk.length; k++) mark[map[k]] = true;
                p = low.indexOf(tk, p + tk.length);
            }
        });
        var buf = '', on = false;
        function flush() {
            if (!buf) return;
            if (on) { var m = el('mark', 'sb-mark'); m.textContent = buf; node.appendChild(m); }
            else node.appendChild(document.createTextNode(buf));
            buf = '';
        }
        for (var c = 0; c < text.length; c++) {
            if (mark[c] !== on) { flush(); on = mark[c]; }
            buf += text[c];
        }
        flush();
    }

    function visibleHits() { return Array.prototype.slice.call(nav.querySelectorAll('.sb-row.sb-hit .sb-link')); }

    function applySearch() {
        var raw = input.value, q = norm(raw), on = q.length > 0;
        var tokens = q.split(' ').filter(Boolean);
        nav.classList.toggle('sb-searching', on);
        searchBox.classList.toggle('has-q', on);
        clearBtn.hidden = !on;
        if (firstHit) { firstHit.classList.remove('sb-first'); firstHit = null; }
        var total = 0;
        function reset(r) {
            r.row.classList.remove('sb-hit', 'sb-nomatch');
            var tx = r.a.querySelector('.sb-text');
            if (tx.querySelector('mark')) tx.textContent = r.label;
        }
        allRows.forEach(reset);
        groups.forEach(function (g) { g.el.classList.remove('sb-hit', 'sb-nomatch'); });
        if (!on) { empty.hidden = true; return; }
        function test(r, title) {
            var hay = norm((title ? title + ' ' : '') + r.label);
            return tokens.every(function (t) { return hay.indexOf(t) > -1; });
        }
        topLinks.forEach(function (r) {
            if (test(r, '')) { r.row.classList.add('sb-hit'); highlight(r.a.querySelector('.sb-text'), r.label, tokens); total++; }
            else r.row.classList.add('sb-nomatch');
        });
        groups.forEach(function (g) {
            var any = 0;
            g.rows.forEach(function (r) {
                if (test(r, g.title)) { r.row.classList.add('sb-hit'); highlight(r.a.querySelector('.sb-text'), r.label, tokens); any++; }
                else r.row.classList.add('sb-nomatch');
            });
            g.el.classList.add(any ? 'sb-hit' : 'sb-nomatch');
            total += any;
        });
        empty.hidden = total > 0;
        if (!total) {
            empty.textContent = '';
            empty.appendChild(icon('bi-search'));
            empty.appendChild(document.createTextNode('Menu “' + raw.trim() + '” tidak ditemukan. Coba kata lain.'));
        } else {
            var h = visibleHits()[0];
            if (h) { firstHit = h.parentNode; firstHit.classList.add('sb-first'); }
        }
    }
    function clearSearch() { input.value = ''; applySearch(); }

    input.addEventListener('input', applySearch);
    clearBtn.addEventListener('click', function () { clearSearch(); input.focus(); });
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            e.preventDefault();
            if (input.value) clearSearch(); else input.blur();
        } else if (e.key === 'Enter') {
            var h = visibleHits()[0];
            if (h) { e.preventDefault(); location.href = h.href; }
        } else if (e.key === 'ArrowDown') {
            var h2 = visibleHits()[0];
            if (h2) { e.preventDefault(); h2.focus(); }
        }
    });
    nav.addEventListener('keydown', function (e) {
        if (!nav.classList.contains('sb-searching')) return;
        if (e.key !== 'ArrowDown' && e.key !== 'ArrowUp') return;
        var hits = visibleHits(), i = hits.indexOf(document.activeElement);
        if (i < 0) return;
        e.preventDefault();
        if (e.key === 'ArrowDown') { if (hits[i + 1]) hits[i + 1].focus(); }
        else if (i === 0) input.focus(); else hits[i - 1].focus();
    });

    function focusSearch() {
        if (!DESK.matches) setDrawer(true);
        else if (isRail()) setMini(false, true);
        setTimeout(function () { input.focus(); input.select(); }, 0);
    }
    searchBtn.addEventListener('click', focusSearch);
    document.addEventListener('keydown', function (e) {
        var t = e.target, tag = t && t.tagName;
        var typing = tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || (t && t.isContentEditable);
        if ((e.ctrlKey || e.metaKey) && !e.altKey && !e.shiftKey && (e.key === 'k' || e.key === 'K')) { e.preventDefault(); focusSearch(); }
        else if (e.key === '/' && !e.ctrlKey && !e.metaKey && !e.altKey && !typing) { e.preventDefault(); focusSearch(); }
        else if (e.key === 'Escape' && !DESK.matches && sb.classList.contains('show')) setDrawer(false);
    });

    /* ── 6. Mode rail ─────────────────────────────────────────── */
    var collapseBtn = document.getElementById('sbCollapse');
    function syncMiniUi() {
        var m = html.classList.contains('sb-mini');
        var rail = isRail();
        if (collapseBtn) {
            collapseBtn.setAttribute('aria-expanded', m ? 'false' : 'true');
            collapseBtn.setAttribute('aria-label', m ? 'Expand Sidebar' : 'Minimize Sidebar');
            collapseBtn.setAttribute('data-tip', m ? 'Expand Sidebar' : 'Minimize Sidebar');
        }
        var topToggle = document.getElementById('sbToggle');   // topbar dirender setelah skrip ini
        if (topToggle) topToggle.setAttribute('aria-pressed', m ? 'true' : 'false');
        searchBtn.tabIndex = rail ? 0 : -1;
        searchBtn.setAttribute('aria-hidden', rail ? 'false' : 'true');
        input.tabIndex = rail ? -1 : 0;
    }
    function setMini(m, persist) {
        html.classList.toggle('sb-mini', m);
        if (persist !== false) lsSet(KEY_MINI, m ? '1' : '0');
        if (m) { if (input.value) clearSearch(); groups.forEach(function (x) { x.el.classList.remove('is-fly'); }); }
        hideTip(); syncMiniUi();
        window.dispatchEvent(new Event('resize'));
        setTimeout(function () { window.dispatchEvent(new Event('resize')); }, 260);
    }
    function toggleMini() { setMini(!html.classList.contains('sb-mini'), true); }
    if (collapseBtn) collapseBtn.addEventListener('click', toggleMini);

    /* Tooltip label (rail) */
    var tip = el('div', 'sb-tip', { role: 'tooltip' });
    document.body.appendChild(tip);
    function showTip(target) {
        if (!isRail()) return;
        hideTip();                                    // tooltip lama tak boleh tertinggal saat pindah ke kepala grup/flyout
        if (target.closest('.sb-panel')) return;      // di flyout label sudah terlihat
        if (target.classList.contains('sb-head')) return;   // grup memakai flyout
        var text = target.getAttribute('data-tip') || target.getAttribute('data-label') || target.getAttribute('aria-label');
        if (!text) return;
        tip.textContent = text;
        var r = target.getBoundingClientRect(), sr = sb.getBoundingClientRect();
        tip.style.left = (sr.right + 10) + 'px';
        tip.style.top = '0px';
        tip.classList.add('show');
        var th = tip.offsetHeight;
        tip.style.top = Math.max(6, Math.min(window.innerHeight - th - 6, r.top + r.height / 2 - th / 2)) + 'px';
    }
    function hideTip() { tip.classList.remove('show'); }
    sb.addEventListener('mouseover', function (e) {
        var t = e.target.closest('.sb-link, .sb-head, .sb-collapse, .sb-search-btn, .sb-act');
        if (t) showTip(t); else hideTip();
    });
    sb.addEventListener('mouseleave', hideTip);
    sb.addEventListener('focusin', function (e) { var t = e.target.closest('.sb-link, .sb-collapse, .sb-search-btn, .sb-act'); if (t) showTip(t); });
    sb.addEventListener('focusout', hideTip);
    nav.addEventListener('scroll', function () { hideTip(); groups.forEach(function (x) { x.el.classList.remove('is-fly'); }); }, { passive: true });

    /* Flyout grup (rail) */
    function placeFly(g) {
        var r = g.head.getBoundingClientRect(), sr = sb.getBoundingClientRect(), p = g.panel;
        p.style.left = (sr.right - 14) + 'px';
        p.style.top = Math.max(8, r.top - 4) + 'px';
        var ph = p.getBoundingClientRect().height;
        if (r.top - 4 + ph > window.innerHeight - 8) p.style.top = Math.max(8, window.innerHeight - 8 - ph) + 'px';
    }
    groups.forEach(function (g) {
        g.el.addEventListener('mouseenter', function () { if (isRail()) { requestAnimationFrame(function () { placeFly(g); }); placeFly(g); } });
        g.el.addEventListener('focusin', function () { if (isRail()) placeFly(g); });
    });
    document.addEventListener('click', function (e) {
        if (!e.target.closest('.sb-group')) groups.forEach(function (x) { x.el.classList.remove('is-fly'); });
    });

    /* ── 7. Laci mobile ───────────────────────────────────────── */
    var overlay = document.getElementById('sbOverlay');
    function burgerEl() { return document.getElementById('sbBurger'); }
    function setDrawer(open) {
        sb.classList.toggle('show', open);
        if (overlay) overlay.classList.toggle('show', open);
        html.classList.toggle('sb-lock', open && !DESK.matches);
        var burger = burgerEl();
        if (burger) burger.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (!open && burger && !DESK.matches && sb.contains(document.activeElement)) burger.focus();
    }
    document.addEventListener('click', function (e) {          // tombol topbar: delegasi (elemen belum ada saat skrip ini jalan)
        if (e.target.closest('#sbBurger')) setDrawer(!sb.classList.contains('show'));
        else if (e.target.closest('#sbToggle')) toggleMini();
    });
    document.addEventListener('DOMContentLoaded', syncMiniUi);
    if (overlay) overlay.addEventListener('click', function () { setDrawer(false); });
    sb.addEventListener('click', function (e) {
        var a = e.target.closest('a[href]');
        if (a && !DESK.matches && !(a.getAttribute('role') === 'button') && !e.defaultPrevented) setDrawer(false);
    });
    (DESK.addEventListener ? DESK.addEventListener.bind(DESK, 'change') : DESK.addListener.bind(DESK))(function () {
        setDrawer(false); hideTip(); syncMiniUi();
        groups.forEach(function (x) { x.el.classList.remove('is-fly'); });
    });

    /* ── 8. Akhir ─────────────────────────────────────────────── */
    syncMiniUi();
    function revealOpen() {
        var g = nav.querySelector('.sb-group.is-open') || nav.querySelector('.sb-row.is-active');
        if (!g) return;
        var nr = nav.getBoundingClientRect(), gr = g.getBoundingClientRect();
        if (gr.bottom > nr.bottom || gr.top < nr.top) nav.scrollTop += gr.top - nr.top - 8;   // kepala grup di atas daftar
    }
    revealOpen();
    requestAnimationFrame(function () { requestAnimationFrame(function () { html.classList.add('sb-ready'); }); });
    window.micSidebar = { toggleMini: toggleMini, setMini: setMini, focusSearch: focusSearch, openDrawer: setDrawer };
})();
