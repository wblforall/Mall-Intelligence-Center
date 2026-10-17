/* MIC — Dashboard "Command Center": count-up angka KPI + sorot kursor.
   JS murni, hanya untuk halaman Dashboard (.page-dash). Galat apa pun → teks asli dibiarkan. */
(function () {
    'use strict';

    var root = document.querySelector('.page-dash');
    if (!root) return;

    var reduce = false;
    try { reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches; } catch (e) {}

    /* ── Sorot ('senter') mengikuti kursor di kartu ─────────────────── */
    try {
        var fine = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
        if (fine && !reduce) {
            var pending = null, raf = 0, lastCard = null;
            var outermost = function (el) {
                var c = el && el.closest ? el.closest('.card, .dash-hero') : null;
                while (c && c.parentElement) {
                    var up = c.parentElement.closest('.card, .dash-hero');
                    if (!up || !root.contains(up)) break;
                    c = up;
                }
                return c;
            };
            root.addEventListener('pointermove', function (ev) {
                if (ev.pointerType && ev.pointerType !== 'mouse') return;
                pending = ev;
                if (raf) return;
                raf = requestAnimationFrame(function () {
                    raf = 0;
                    var e = pending, card = outermost(e.target);
                    if (lastCard && lastCard !== card) {
                        lastCard.style.removeProperty('--mx');
                        lastCard.style.removeProperty('--my');
                    }
                    lastCard = card;
                    if (!card) return;
                    var r = card.getBoundingClientRect();
                    card.style.setProperty('--mx', (e.clientX - r.left) + 'px');
                    card.style.setProperty('--my', (e.clientY - r.top) + 'px');
                });
            }, { passive: true });
            root.addEventListener('pointerleave', function () {
                if (lastCard) {
                    lastCard.style.removeProperty('--mx');
                    lastCard.style.removeProperty('--my');
                    lastCard = null;
                }
            });
        }
    } catch (e) { /* sorot hanya hiasan */ }

    /* ── Count-up angka KPI ─────────────────────────────────────────── */
    if (reduce) return; // gerak berkurang: angka langsung tampil apa adanya

    /* Kandidat: nilai statis kartu ekonomi. Angka KPI Events/Traffic ([data-count])
       sudah dianimasikan skrip bawaan halaman → tidak disentuh agar tidak dobel. */
    var SELECTOR = '.eco-strip [id^="strip-"], [id^="macro-"][id$="-val"], #ihsg-val, [data-cc-count]';

    /* Urai teks "awalan + angka + akhiran"; kembalikan null bila bukan angka jelas. */
    function parse(text) {
        var m = /^(\D*?)(\d[\d.,]*\d|\d)(\D*)$/.exec(text);
        if (!m) return null;
        var pre = m[1], core = m[2], post = m[3];
        if (pre.length > 6 || post.length > 4) return null;

        var dot = core.indexOf('.') !== -1, comma = core.indexOf(',') !== -1;
        var decSep = '', thSep = '';
        if (dot && comma) {
            decSep = core.lastIndexOf('.') > core.lastIndexOf(',') ? '.' : ',';
            thSep = decSep === '.' ? ',' : '.';
        } else if (dot || comma) {
            var s = dot ? '.' : ',';
            var parts = core.split(s);
            var last = parts[parts.length - 1];
            if (parts.length > 2 || (last.length === 3 && parts[0].length <= 3 && parts[0] !== '0')) thSep = s;
            else decSep = s;
        }
        var intPart = core, decPart = '';
        if (decSep) {
            var i = core.lastIndexOf(decSep);
            intPart = core.slice(0, i);
            decPart = core.slice(i + 1);
        }
        if (thSep) {
            if (!new RegExp('^\\d{1,3}(\\' + thSep + '\\d{3})*$').test(intPart)) return null;
            intPart = intPart.split(thSep).join('');
        }
        if (!/^\d+$/.test(intPart) || (decPart && !/^\d+$/.test(decPart))) return null;
        if (intPart.length > 1 && intPart[0] === '0') return null;
        var value = parseFloat(intPart + (decPart ? '.' + decPart : ''));
        if (!isFinite(value)) return null;
        return { pre: pre, post: post, thSep: thSep, decSep: decSep, dec: decPart.length, value: value };
    }

    function format(f, v) {
        var fixed = v.toFixed(f.dec);
        var bits = fixed.split('.');
        var ip = bits[0];
        if (f.thSep) ip = ip.replace(/\B(?=(\d{3})+(?!\d))/g, f.thSep);
        return f.pre + ip + (f.dec ? f.decSep + bits[1] : '') + f.post;
    }

    function animate(el) {
        try {
            if (el.hasAttribute('data-no-count') || el.hasAttribute('data-count')) return;
            if (el.closest('table, form, [data-no-count]')) return;
            if (el.children.length) return;               // hanya elemen berisi teks murni
            var text = el.textContent;
            var f = parse(text.trim());
            if (!f || f.value === 0) return;
            if (format(f, f.value) !== text.trim()) return; // format tak bisa direproduksi persis → lewati

            var original = text, last = format(f, 0);
            el.textContent = last;
            var dur = 800, t0 = performance.now();
            var step = function (now) {
                // elemen diganti / diisi skrip lain (data live) → berhenti, jangan menimpa
                if (!el.isConnected || el.textContent !== last) return;
                var p = Math.min((now - t0) / dur, 1);
                var ease = 1 - Math.pow(1 - p, 3);
                var out = p >= 1 ? original : format(f, f.value * ease);
                el.textContent = last = out;
                if (p < 1) requestAnimationFrame(step);
            };
            requestAnimationFrame(step);
        } catch (e) { /* biarkan teks asli */ }
    }

    function run() {
        try {
            var nodes = root.querySelectorAll(SELECTOR);
            for (var i = 0; i < nodes.length; i++) animate(nodes[i]);
        } catch (e) {}
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', run, { once: true });
    else run();
})();
