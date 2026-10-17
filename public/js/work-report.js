/* MIC — Progress Report: pencarian, filter, urutan (sisi klien), KPI/PIC sebagai pintasan filter. */
(function () {
    'use strict';
    var root = document.getElementById('wrRoot');
    if (!root) return;
    function $(s, c) { return (c || root).querySelector(s); }
    function $$(s, c) { return Array.prototype.slice.call((c || root).querySelectorAll(s)); }

    var items = $$('.wr-item');
    var q = $('#wrQ');
    if (!q || !items.length) return;
    var fStatus = $('#wrFStatus'), fPic = $('#wrFPic'), fDept = $('#wrFDept'), sortSel = $('#wrSort'),
        countEl = $('#wrCount'), noMatch = $('#wrNoMatch'), resetBtn = $('#wrReset');

    function statusOk(d, v) {
        if (!v) return true;
        if (v === 'att') return d.att === '1';
        if (v === 'over') return d.over === '1';
        if (v === 'stale') return d.stale === '1';
        if (v === 'flag') return d.flag === '1';
        if (v === 'msg') return d.msg === '1';
        return d.st === v;
    }

    function syncShortcuts() {
        $$('[data-wr-kpi]').forEach(function (b) {
            if (b.classList.contains('wr-btn')) return;
            var on = (b.getAttribute('data-wr-kpi') || '') === fStatus.value && fStatus.value !== '';
            b.classList.toggle('is-on', on);
            b.setAttribute('aria-pressed', on ? 'true' : 'false');
        });
        $$('[data-wr-pic]').forEach(function (b) { b.classList.toggle('is-on', b.getAttribute('data-wr-pic') === fPic.value && fPic.value !== ''); });
    }

    function apply() {
        var term = q.value.trim().toLowerCase(), shown = 0;
        items.forEach(function (c) {
            var d = c.dataset, ok = true;
            if (term && d.search.indexOf(term) < 0) ok = false;
            if (ok && !statusOk(d, fStatus.value)) ok = false;
            if (ok && fPic.value && d.pic !== fPic.value) ok = false;
            if (ok && fDept && fDept.value && d.dept !== fDept.value) ok = false;
            c.hidden = !ok;
            if (ok) shown++;
        });
        $$('[data-group]').forEach(function (g) {
            var all = $$('.wr-item', g), vis = all.filter(function (c) { return !c.hidden; }).length;
            g.hidden = vis === 0;
            var n = $('[data-group-n]', g);
            if (n) n.textContent = vis === all.length ? all.length + ' program' : vis + ' dari ' + all.length + ' program';
        });
        var filtered = term || fStatus.value || fPic.value || (fDept && fDept.value);
        countEl.textContent = filtered ? shown + ' dari ' + items.length + ' program' : items.length + ' program';
        noMatch.hidden = shown > 0;
        resetBtn.hidden = !filtered;
        syncShortcuts();
    }

    function sortItems() {
        var mode = sortSel.value;
        $$('.wr-list').forEach(function (list) {
            var arr = $$('.wr-item', list);
            arr.sort(function (a, b) {
                var x = a.dataset, y = b.dataset, r = 0;
                if (mode === 'nama') r = x.name.toLowerCase().localeCompare(y.name.toLowerCase(), 'id');
                else if (mode === 'att') r = (+y.score) - (+x.score);
                else if (mode === 'pct') r = ((+x.pct < 0 ? 999 : +x.pct)) - ((+y.pct < 0 ? 999 : +y.pct));
                else if (mode === 'upd') r = (+y.upd) - (+x.upd);
                else if (mode === 'tgt') r = (+x.tgt) - (+y.tgt);
                return r || ((+x.order) - (+y.order));
            });
            arr.forEach(function (c) { list.appendChild(c); });
        });
    }

    var t;
    q.addEventListener('input', function () { clearTimeout(t); t = setTimeout(apply, 120); });
    [fStatus, fPic, fDept].forEach(function (el) { if (el) el.addEventListener('change', apply); });
    sortSel.addEventListener('change', sortItems);

    function reset() { q.value = ''; fStatus.value = ''; fPic.value = ''; if (fDept) fDept.value = ''; apply(); }
    resetBtn.addEventListener('click', reset);

    root.addEventListener('click', function (e) {
        var k = e.target.closest('[data-wr-kpi]');
        if (k) {
            var key = k.getAttribute('data-wr-kpi') || '';
            fStatus.value = (fStatus.value === key) ? '' : key;
            apply();
            if (k.classList.contains('wr-btn')) { var tl = $('#wrTools'); if (tl) tl.scrollIntoView({ block: 'start', behavior: 'smooth' }); }
            return;
        }
        var p = e.target.closest('[data-wr-pic]');
        if (p) { var v = p.getAttribute('data-wr-pic'); fPic.value = fPic.value === v ? '' : v; apply(); return; }
        if (e.target.closest('#wrPicMore')) {
            $$('.wr-pic.is-extra').forEach(function (b) { b.hidden = false; });
            e.target.closest('#wrPicMore').hidden = true;
            return;
        }
        var j = e.target.closest('[data-wr-jump]');
        if (j) {
            e.preventDefault();
            var card = document.getElementById('initiative-' + j.getAttribute('data-wr-jump'));
            if (card) {
                if (card.hidden) reset();
                card.scrollIntoView({ block: 'center', behavior: 'smooth' });
                card.classList.add('is-jump');
                setTimeout(function () { card.classList.remove('is-jump'); }, 1800);
            }
        }
    });

    // Tautan #initiative-ID (mis. dari notifikasi / setelah kirim balasan)
    if (location.hash.indexOf('#initiative-') === 0) {
        var el = document.getElementById(location.hash.slice(1));
        if (el) setTimeout(function () { el.scrollIntoView({ block: 'center' }); el.classList.add('is-jump'); setTimeout(function () { el.classList.remove('is-jump'); }, 1800); }, 80);
    }
    apply();
})();
