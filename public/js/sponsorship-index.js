/* MIC — Daftar Sponsorship: buka/tutup program, pencarian, filter, urutan (sisi klien). */
(function () {
    'use strict';
    var root = document.getElementById('sponIdx');
    if (!root) return;

    function $(s, c) { return (c || root).querySelector(s); }
    function $$(s, c) { return Array.prototype.slice.call((c || root).querySelectorAll(s)); }
    var KEY = 'sponIdxOpen';

    // ── Ingat program yang terbuka (per tab) ──────────────────────────
    function loadOpen() { try { return JSON.parse(sessionStorage.getItem(KEY) || '[]'); } catch (e) { return []; } }
    function saveOpen() {
        try {
            var ids = $$('.si-prog.is-open').map(function (a) { return a.dataset.pid; });
            sessionStorage.setItem(KEY, JSON.stringify(ids));
        } catch (e) { /* abaikan */ }
    }

    function setOpen(card, open) {
        var body = $('.si-prog-body', card), chev = $('.si-chev', card);
        card.classList.toggle('is-open', open);
        body.hidden = !open;
        if (chev) chev.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    var cards = $$('.si-prog');
    var remembered = loadOpen();
    cards.forEach(function (c) { if (remembered.indexOf(c.dataset.pid) > -1) setOpen(c, true); });

    // ── Klik kepala program ──────────────────────────────────────────
    root.addEventListener('click', function (e) {
        var real = e.target.closest('[data-si-real]');
        if (real) {
            var p = document.getElementById('reals-' + real.getAttribute('data-si-real'));
            if (p) {
                p.hidden = !p.hidden;
                real.setAttribute('aria-expanded', p.hidden ? 'false' : 'true');
                real.classList.toggle('is-on', !p.hidden);
            }
            return;
        }
        var head = e.target.closest('[data-si-toggle]');
        if (!head || e.target.closest('[data-si-notoggle], a')) return;
        var card = head.closest('.si-prog');
        setOpen(card, !card.classList.contains('is-open'));
        saveOpen();
    });

    // ── Filter, cari, urut ───────────────────────────────────────────
    var q = $('#siQ'), fStatus = $('#siFStatus'), fYear = $('#siFYear'), fMall = $('#siFMall'),
        fJenis = $('#siFJenis'), sortSel = $('#siSort'), countEl = $('#siCount'), noMatch = $('#siNoMatch');
    if (!q) return;

    function apply() {
        var term = q.value.trim().toLowerCase();
        var shown = 0;
        cards.forEach(function (c) {
            var d = c.dataset, ok = true;
            if (term && d.search.indexOf(term) < 0) ok = false;
            if (ok && fStatus.value) {
                if (fStatus.value === 'locked') ok = d.locked === '1';
                else ok = d.status === fStatus.value;
            }
            if (ok && fYear.value && d.year !== fYear.value) ok = false;
            if (ok && fMall.value && d.mall !== fMall.value) ok = false;
            if (ok && fJenis.value && (',' + d.jenis + ',').indexOf(',' + fJenis.value + ',') < 0) ok = false;
            c.hidden = !ok;
            if (ok) shown++;

            // tandai sponsor yang cocok dengan kata kunci
            $$('.si-sp', c).forEach(function (s) {
                var hit = !!term && $('.si-sp-n', s).textContent.toLowerCase().indexOf(term) > -1;
                s.classList.toggle('is-hit', hit);
            });
            if (ok && term && d.name.toLowerCase().indexOf(term) < 0 && !c.classList.contains('is-open')) {
                setOpen(c, true);   // cocok lewat nama sponsor: buka agar terlihat (tak disimpan)
            }
        });
        $$('.si-group').forEach(function (g) {
            var any = $$('.si-prog', g).some(function (c) { return !c.hidden; });
            g.hidden = !any;
        });
        var filtered = term || fStatus.value || fYear.value || fMall.value || fJenis.value;
        countEl.textContent = filtered ? shown + ' dari ' + cards.length + ' program' : cards.length + ' program';
        noMatch.hidden = shown > 0;
    }

    function sortCards() {
        var mode = sortSel.value;
        $$('.si-list').forEach(function (list) {
            var arr = $$('.si-prog', list);
            arr.sort(function (a, b) {
                var x = a.dataset, y = b.dataset;
                if (mode === 'nama') return x.name.toLowerCase().localeCompare(y.name.toLowerCase(), 'id');
                if (mode === 'deal') return (+y.deal) - (+x.deal);
                if (mode === 'sisa') return (+y.sisa) - (+x.sisa);
                if (mode === 'pct')  return (+x.pct) - (+y.pct);
                return cards.indexOf(a) - cards.indexOf(b);
            });
            arr.forEach(function (c) { list.appendChild(c); });
        });
    }

    var t;
    q.addEventListener('input', function () { clearTimeout(t); t = setTimeout(apply, 120); });
    [fStatus, fYear, fMall, fJenis].forEach(function (el) { el.addEventListener('change', apply); });
    sortSel.addEventListener('change', sortCards);

    function reset() {
        q.value = ''; fStatus.value = fYear.value = fMall.value = fJenis.value = '';
        apply();
    }
    $('#siReset').addEventListener('click', reset);
    $('#siExpand').addEventListener('click', function () {
        cards.forEach(function (c) { if (!c.hidden) setOpen(c, true); }); saveOpen();
    });
    $('#siCollapse').addEventListener('click', function () {
        cards.forEach(function (c) { setOpen(c, false); }); saveOpen();
    });

    // ── Tautan #program-X / #sponsor-X ───────────────────────────────
    function openHash() {
        var h = location.hash;
        if (!h) return;
        var el = document.querySelector(h.replace(/[^#\w-]/g, ''));
        if (!el || !root.contains(el)) return;
        var card = el.closest('.si-prog');
        if (card) { setOpen(card, true); setTimeout(function () { el.scrollIntoView({ block: 'center' }); }, 60); }
    }
    openHash();
    apply();
})();
