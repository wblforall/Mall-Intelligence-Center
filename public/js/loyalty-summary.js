/* ═══════════════════════════════════════════════════════════════════════
   MIC — Summary Bulanan Program Loyalty (analisis)
   Data berasal dari window.LOYSUM (disusun di view dari controller).
   Semua ambang "Perlu Perhatian" ada di THRESH — ubah di sini.
   ═══════════════════════════════════════════════════════════════════════ */
(function () {
    'use strict';

    // ── Ambang & batas (mudah diubah) ─────────────────────────────────────
    var THRESH = {
        REDEMPTION_LOW: 10,   // % — voucher dianggap redemption rendah bila terpakai/tersebar di bawah ini
        MIN_SEBAR: 10,        // voucher baru diperiksa bila tersebar bulan ini minimal sekian
        STOK_WARN: 80,        // % — stok hadiah "menipis" bila total dibagikan >= nilai ini
        DROP_PCT: 30,         // % — penurunan vs bulan lalu dianggap besar
        MIN_PREV: 10,         // pembanding bulan lalu minimal sekian agar penurunan dihitung (hindari angka kecil)
        MAX_SHOWN: 8,         // jumlah butir tampil sebelum "lihat semua"
        MAX_PER_KIND: 3       // batas butir per jenis pada tampilan ringkas
    };

    var D = window.LOYSUM;
    var root = document.getElementById('loySum');
    if (!D || !root) return;

    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    var nf = new Intl.NumberFormat('id-ID');
    function fmt(n) { return nf.format(Math.round(n)); }
    function $(s, c) { return (c || root).querySelector(s); }
    function $$(s, c) { return Array.prototype.slice.call((c || root).querySelectorAll(s)); }
    function esc(s) { return String(s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
    function pctChange(cur, prev) { return prev > 0 ? Math.round((cur - prev) / prev * 100) : null; }

    // ── Aturan kelengkapan analisa (cermin LoyaltyCtrl::analisaTeksSah / analisaEvaluasi) ──
    var A = D.aturan || { wajibCetak: false, berlaku: false, min: 10, maksLunak: 1000, maksKeras: 2000, isAdmin: false, labels: {} };
    var KOLOM = ['highlight', 'kendala', 'tindak_lanjut'];
    var KOLOM_LABEL = A.labels && A.labels.highlight ? A.labels : { highlight: 'Highlight', kendala: 'Kendala', tindak_lanjut: 'Tindak Lanjut' };
    function trimU(s) { return String(s == null ? '' : s).replace(/^\s+|\s+$/g, ''); }
    function nChar(s) { return Array.from(trimU(s)).length; }
    // Sah: >= min karakter (setelah trim), memuat huruf, dan bukan pengulangan satu karakter.
    function analisaSah(t) {
        t = trimU(t);
        if (Array.from(t).length < A.min) return false;
        if (!/\p{L}/u.test(t)) return false;
        var seen = {}, n = 0;
        Array.from(t.replace(/\s+/g, '').toLowerCase()).forEach(function (c) { if (!seen[c]) { seen[c] = 1; n++; } });
        return n > 1;
    }
    function kolomWajib(status) { return (status === 'partial' || status === 'belum' || status === 'sebagian') ? KOLOM.slice() : ['highlight']; }
    function evalAn(status, vals) {
        var kurang = kolomWajib(status).filter(function (k) { return !analisaSah(vals[k] || ''); });
        var draf = KOLOM.some(function (k) { return trimU(vals[k] || '') !== ''; });
        return { lengkap: kurang.length === 0, kurang: kurang, draf: draf };
    }
    function incompletePrograms() { return D.programs.filter(function (p) { return !p.lengkap; }); }
    function locked() { return !!(A.wajibCetak && A.berlaku && incompletePrograms().length > 0); }

    // Offset tempel = tinggi topbar aplikasi
    function setTop() {
        var tb = document.querySelector('.topbar');
        root.style.setProperty('--ls-top', (tb ? tb.offsetHeight : 0) + 'px');
    }
    setTop(); window.addEventListener('resize', setTop);

    // ── Toast kecil ───────────────────────────────────────────────────────
    var toastT;
    function toast(msg) {
        var el = document.getElementById('lsToast');
        if (!el) { el = document.createElement('div'); el.id = 'lsToast'; el.className = 'ls-toast'; el.setAttribute('role', 'status'); root.appendChild(el); }
        el.textContent = msg; el.classList.add('is-on');
        clearTimeout(toastT); toastT = setTimeout(function () { el.classList.remove('is-on'); }, 2200);
    }

    // ══ Tabel generik: urut ══════════════════════════════════════════════
    function makeSortable(table, getUnits, onSorted) {
        var ths = $$('thead th[data-sort]', table);
        ths.forEach(function (th) {
            th.querySelector('button').addEventListener('click', function () {
                var k = th.dataset.sort, numeric = th.hasAttribute('data-num');
                var cur = th.getAttribute('aria-sort');
                var dir = cur === 'none' ? (numeric ? 'descending' : 'ascending') : (cur === 'ascending' ? 'descending' : 'ascending');
                ths.forEach(function (o) { o.setAttribute('aria-sort', 'none'); });
                th.setAttribute('aria-sort', dir);
                var units = getUnits();
                units.sort(function (a, b) {
                    var x = a.row.dataset['s' + k.charAt(0).toUpperCase() + k.slice(1)], y = b.row.dataset['s' + k.charAt(0).toUpperCase() + k.slice(1)];
                    var r;
                    if (numeric) r = parseFloat(x) - parseFloat(y); else r = String(x).localeCompare(String(y), 'id');
                    if (r === 0) r = (parseInt(a.row.dataset.ord || 0, 10) - parseInt(b.row.dataset.ord || 0, 10));
                    return dir === 'ascending' ? r : -r;
                });
                var tbody = table.tBodies[0];
                units.forEach(function (u) { tbody.appendChild(u.row); if (u.det) tbody.appendChild(u.det); });
                if (onSorted) onSorted();
            });
        });
    }

    // ══ D. Tabel Kinerja Program ═════════════════════════════════════════
    var progTable = document.getElementById('lsProgTable');
    var fState = { q: '', src: '', status: '', need: false };
    function progUnits() {
        return $$('tr.ls-row', progTable).map(function (r) { return { row: r, det: document.getElementById('det-' + r.dataset.key) }; });
    }
    function applyProgFilter() {
        var shown = 0;
        progUnits().forEach(function (u) {
            var d = u.row.dataset;
            var ok = (!fState.q || d.name.indexOf(fState.q) > -1) && (!fState.src || d.src === fState.src) &&
                (!fState.status || d.status === fState.status) && (!fState.need || d.need === '1');
            u.row.hidden = !ok;
            if (u.det) u.det.hidden = !ok || u.row.getAttribute('data-open') !== '1';
            if (ok) shown++;
        });
        var c = document.getElementById('lsProgCount'); if (c) c.textContent = shown + (shown !== progUnits().length ? ' / ' + progUnits().length : '');
        var em = document.getElementById('lsProgEmpty'); if (em) em.hidden = shown > 0;
        var any = fState.q || fState.src || fState.status || fState.need;
        var rs = document.getElementById('lsReset'); if (rs) rs.hidden = !any;
        $$('.ls-legend button').forEach(function (b) { b.classList.toggle('is-on', b.dataset.filterStatus === fState.status); });
    }
    function syncSeg(group, val) {
        $$('.ls-seg[data-group="' + group + '"] button').forEach(function (b) { b.classList.toggle('is-on', b.dataset.v === val); });
    }
    function setNeed(on) {
        fState.need = on;
        var b = document.getElementById('lsNeedAnalisa'); if (b) { b.classList.toggle('is-on', on); b.setAttribute('aria-pressed', on ? 'true' : 'false'); }
    }
    function setOpen(row, open) {
        var det = document.getElementById('det-' + row.dataset.key);
        row.setAttribute('data-open', open ? '1' : '0');
        var btn = row.querySelector('.ls-exp'); if (btn) btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (det) det.hidden = !open || row.hidden;
    }
    if (progTable) {
        var s = document.getElementById('lsSearch');
        if (s) s.addEventListener('input', function () { fState.q = s.value.trim().toLowerCase(); applyProgFilter(); });
        $$('.ls-seg').forEach(function (seg) {
            seg.addEventListener('click', function (e) {
                var b = e.target.closest('button'); if (!b) return;
                fState[seg.dataset.group] = b.dataset.v; syncSeg(seg.dataset.group, b.dataset.v); applyProgFilter();
            });
        });
        var nb = document.getElementById('lsNeedAnalisa');
        if (nb) nb.addEventListener('click', function () { setNeed(!fState.need); applyProgFilter(); });
        var rs = document.getElementById('lsReset');
        if (rs) rs.addEventListener('click', function () {
            fState = { q: '', src: '', status: '', need: false };
            if (s) s.value = ''; syncSeg('src', ''); syncSeg('status', ''); setNeed(false); applyProgFilter();
        });
        $$('.ls-legend button').forEach(function (b) {
            b.addEventListener('click', function () {
                fState.status = fState.status === b.dataset.filterStatus ? '' : b.dataset.filterStatus;
                syncSeg('status', fState.status); applyProgFilter(); scrollToEl(document.getElementById('lsPrograms'));
            });
        });
        $$('.ls-exp', progTable).forEach(function (btn) {
            btn.addEventListener('click', function () {
                var row = btn.closest('tr'), open = row.getAttribute('data-open') !== '1';
                if (!open) {
                    var det = document.getElementById('det-' + row.dataset.key), box = det && det.querySelector('.analisa-box');
                    if (box && boxDirty(box)) {
                        if (!confirm('Perubahan analisa belum disimpan. Tutup tanpa menyimpan?')) return;
                        discardBox(box);
                    }
                }
                setOpen(row, open);
            });
        });
        makeSortable(progTable, progUnits, null);
        applyProgFilter();
    }

    function scrollToEl(el, extra) {
        if (!el) return;
        var bar = document.getElementById('lsBar');
        var off = (document.querySelector('.topbar') ? document.querySelector('.topbar').offsetHeight : 0) + (bar ? bar.offsetHeight : 0) + 14 + (extra || 0);
        var y = el.getBoundingClientRect().top + window.pageYOffset - off;
        window.scrollTo({ top: y, behavior: reduceMotion ? 'auto' : 'smooth' });
    }
    function flash(el) {
        if (!el) return;
        el.classList.remove('is-flash'); void el.offsetWidth; el.classList.add('is-flash');
        setTimeout(function () { el.classList.remove('is-flash'); }, 2600);
    }
    function jumpProgram(key, openForm) {
        var row = document.getElementById('row-' + key); if (!row) return;
        if (row.hidden) { fState = { q: '', src: '', status: '', need: false }; var si = document.getElementById('lsSearch'); if (si) si.value = ''; syncSeg('src', ''); syncSeg('status', ''); setNeed(false); applyProgFilter(); }
        if (openForm) setOpen(row, true);
        scrollToEl(row); flash(row);
    }

    // ══ F. Voucher & Hadiah ══════════════════════════════════════════════
    var vRows = $$('#lsVTable tbody tr.ls-irow'), hRows = $$('#lsHTable tbody tr.ls-irow');
    var itemFlag = { v: false, h: false }, itemQ = { v: '', h: '' };
    var vFlagged = [], hFlagged = [];
    vRows.forEach(function (r) {
        var sebar = +r.dataset.sebar, pakai = +r.dataset.pakai;
        if (sebar >= THRESH.MIN_SEBAR && pakai / sebar * 100 < THRESH.REDEMPTION_LOW) {
            r.dataset.flag = '1'; r.classList.add('is-problem'); var t = r.querySelector('.ls-flagtxt'); if (t) t.hidden = false; vFlagged.push(r);
        }
    });
    hRows.forEach(function (r) {
        var stok = +r.dataset.stok, total = +r.dataset.total, t = r.querySelector('.ls-flagtxt');
        if (stok > 0 && total >= stok) { r.dataset.flag = '1'; r.dataset.kind = 'habis'; r.classList.add('is-problem'); if (t) { t.hidden = false; t.innerHTML = '<i class="bi bi-exclamation-triangle-fill"></i>stok habis'; } hFlagged.push(r); }
        else if (stok > 0 && total / stok * 100 >= THRESH.STOK_WARN) { r.dataset.flag = '1'; r.dataset.kind = 'menipis'; r.classList.add('is-problem', 'is-warnrow'); if (t) { t.hidden = false; t.innerHTML = '<i class="bi bi-exclamation-circle-fill"></i>stok menipis'; } hFlagged.push(r); }
    });
    var vn = document.getElementById('lsVFlagN'); if (vn) vn.textContent = vFlagged.length;
    var hn = document.getElementById('lsHFlagN'); if (hn) hn.textContent = hFlagged.length;

    function applyItems(kind) {
        var rows = kind === 'v' ? vRows : hRows, shown = 0;
        rows.forEach(function (r) {
            var ok = (!itemQ[kind] || r.dataset.search.indexOf(itemQ[kind]) > -1) && (!itemFlag[kind] || r.dataset.flag === '1');
            r.hidden = !ok; if (ok) shown++;
        });
        var em = $('[data-items-empty="' + kind + '"]'); if (em) em.hidden = shown > 0;
    }
    $$('[data-items-search]').forEach(function (i) {
        i.addEventListener('input', function () { itemQ[i.dataset.itemsSearch] = i.value.trim().toLowerCase(); applyItems(i.dataset.itemsSearch); });
    });
    $$('[data-items-flag]').forEach(function (b) {
        b.addEventListener('click', function () {
            var k = b.dataset.itemsFlag; itemFlag[k] = !itemFlag[k];
            b.classList.toggle('is-on', itemFlag[k]); b.setAttribute('aria-pressed', itemFlag[k] ? 'true' : 'false'); applyItems(k);
        });
    });
    function showTab(t) {
        $$('.ls-tabs button').forEach(function (b) { var on = b.dataset.tab === t; b.classList.toggle('is-on', on); b.setAttribute('aria-selected', on ? 'true' : 'false'); });
        $$('.ls-tabpane').forEach(function (p) { p.hidden = p.dataset.pane !== t; });
    }
    $$('.ls-tabs button').forEach(function (b) { b.addEventListener('click', function () { showTab(b.dataset.tab); }); });
    var vt = document.getElementById('lsVTable'), ht = document.getElementById('lsHTable');
    if (vt) makeSortable(vt, function () { return $$('tbody tr.ls-irow', vt).map(function (r) { return { row: r }; }); });
    if (ht) makeSortable(ht, function () { return $$('tbody tr.ls-irow', ht).map(function (r) { return { row: r }; }); });
    function jumpItem(kind, rowId) {
        showTab(kind); itemFlag[kind] = false; itemQ[kind] = '';
        var b = $('[data-items-flag="' + kind + '"]'); if (b) { b.classList.remove('is-on'); b.setAttribute('aria-pressed', 'false'); }
        var i = $('[data-items-search="' + kind + '"]'); if (i) i.value = '';
        applyItems(kind);
        var r = document.getElementById('irow-' + rowId); scrollToEl(r); flash(r);
    }

    // ══ C. Panel Perlu Perhatian ═════════════════════════════════════════
    var KIND = {
        stokHabis:  { rank: 1, level: 'bad',  icon: 'box-seam' },
        belum:      { rank: 2, level: 'bad',  icon: 'x-octagon' },
        turun:      { rank: 3, level: 'bad',  icon: 'graph-down-arrow' },
        redemption: { rank: 4, level: 'warn', icon: 'ticket-perforated' },
        stokTipis:  { rank: 5, level: 'warn', icon: 'box' },
        sebagian:   { rank: 6, level: 'warn', icon: 'exclamation-circle' },
        analisa:    { rank: 7, level: 'info', icon: 'chat-left-dots' }
    };
    function targetText(u) { var pct = u.target > 0 ? Math.round(u.cur / u.target * 1000) / 10 : 0; return u.label + ' ' + fmt(u.cur) + ' dari target ' + fmt(u.target) + ' (' + pct + '%, kurang ' + fmt(u.target - u.cur) + ')'; }

    function buildAttention() {
        var out = [];
        D.hadiah.forEach(function (h) {
            if (h.stok <= 0) return;
            var pct = h.total / h.stok * 100;
            if (h.total >= h.stok) out.push({ kind: 'stokHabis', title: 'Stok habis: ' + h.name, detail: fmt(h.total) + ' dari ' + fmt(h.stok) + ' sudah dibagikan' + (h.prog ? ' · ' + h.prog : ''), jump: ['item', 'h', h.row] });
            else if (pct >= THRESH.STOK_WARN) out.push({ kind: 'stokTipis', title: 'Stok menipis: ' + h.name, detail: fmt(h.total) + ' dari ' + fmt(h.stok) + ' dibagikan (' + (Math.round(pct * 10) / 10) + '%, sisa ' + fmt(h.stok - h.total) + ')' + (h.prog ? ' · ' + h.prog : ''), jump: ['item', 'h', h.row], sortKey: -pct });
        });
        D.programs.forEach(function (p) {
            if (p.status === 'belum') out.push({ kind: 'belum', title: 'Belum mencapai target: ' + p.name, detail: p.unmet.map(targetText).join(' · '), jump: ['prog', p.key], sortKey: 0 });
            else if (p.status === 'sebagian' || p.status === 'partial') out.push({ kind: 'sebagian', title: 'Sebagian target tercapai (' + p.ach + '/' + p.tgt + '): ' + p.name, detail: p.unmet.map(targetText).join(' · '), jump: ['prog', p.key] });
        });
        D.vouchers.forEach(function (v) {
            if (v.tersebar >= THRESH.MIN_SEBAR) {
                var red = v.terpakai / v.tersebar * 100;
                if (red < THRESH.REDEMPTION_LOW) out.push({ kind: 'redemption', title: 'Redemption rendah: ' + v.name, detail: 'Hanya ' + fmt(v.terpakai) + ' terpakai dari ' + fmt(v.tersebar) + ' tersebar (' + (Math.round(red * 10) / 10) + '%)' + (v.prog ? ' · ' + v.prog : ''), jump: ['item', 'v', v.row], sortKey: red });
            }
        });
        if (D.hasPrev) {
            [['member', 'Member baru'], ['terpakai', 'Voucher dipakai']].forEach(function (m) {
                var cur = D.kpi[m[0]], prev = D.prev[m[0]];
                if (prev >= THRESH.MIN_PREV) {
                    var ch = pctChange(cur, prev);
                    if (ch !== null && ch <= -THRESH.DROP_PCT) out.push({ kind: 'turun', title: m[1] + ' turun ' + Math.abs(ch) + '% dari ' + D.prevLabel, detail: fmt(prev) + ' → ' + fmt(cur) + ' (selisih ' + fmt(cur - prev) + ')', jump: ['chart'], sortKey: ch });
                }
            });
        }
        var kurangN = incompletePrograms().length;
        if (kurangN > 0) {
            var blok = locked();
            out.push({ kind: 'analisa', level: blok ? 'warn' : 'info', title: kurangN + ' program belum lengkap analisanya',
                detail: blok ? 'Laporan bulanan belum bisa dicetak — klik untuk melihat daftar & kolom yang kurang' : 'Klik untuk menyaring tabel pada program yang perlu dilengkapi',
                jump: [blok ? 'comp' : 'need'] });
        }
        out.sort(function (a, b) { return (KIND[a.kind].rank - KIND[b.kind].rank) || ((a.sortKey || 0) - (b.sortKey || 0)); });
        return out;
    }

    var attention = buildAttention();
    var attnExpanded = false;
    function pickCompact(list) {
        var cnt = {}, res = [];
        list.forEach(function (a) {
            cnt[a.kind] = (cnt[a.kind] || 0) + 1;
            if (cnt[a.kind] <= THRESH.MAX_PER_KIND && res.length < THRESH.MAX_SHOWN) res.push(a);
        });
        return res;
    }
    function doJump(j) {
        if (j[0] === 'prog') jumpProgram(j[1], true);
        else if (j[0] === 'item') jumpItem(j[1], j[2]);
        else if (j[0] === 'need') { fState = { q: '', src: '', status: '', need: true }; var si = document.getElementById('lsSearch'); if (si) si.value = ''; syncSeg('src', ''); syncSeg('status', ''); setNeed(true); applyProgFilter(); scrollToEl(document.getElementById('lsPrograms')); }
        else if (j[0] === 'comp') { var cp = document.getElementById('kelengkapan'); if (cp && !cp.hidden) { scrollToEl(cp); flash(cp); } else doJump(['need']); }
        else if (j[0] === 'chart') scrollToEl(document.getElementById('trendMember') ? document.getElementById('trendMember').closest('.ls-card') : null);
    }
    function renderAttention() {
        var list = $('#lsAttnList'), cEl = $('#lsAttnCount'), more = $('#lsAttnMore');
        cEl.textContent = attention.length;
        cEl.classList.toggle('is-ok', attention.length === 0);
        if (!attention.length) {
            list.innerHTML = '<li class="ls-attn-empty"><i class="bi bi-check2-circle"></i>Tidak ada butir yang melewati ambang pada ' + esc(D.bulanLabel) + '.</li>';
            more.hidden = true; return;
        }
        var items = attnExpanded ? attention : pickCompact(attention);
        list.innerHTML = items.map(function (a, i) {
            var k = KIND[a.kind];
            return '<li class="ls-attn-i" data-level="' + (a.level || k.level) + '"><button type="button" data-i="' + attention.indexOf(a) + '">' +
                '<span class="ls-attn-ico"><i class="bi bi-' + k.icon + '"></i></span>' +
                '<span class="ls-attn-tx"><b>' + esc(a.title) + '</b><small>' + esc(a.detail) + '</small></span>' +
                '<i class="bi bi-arrow-right-short ls-attn-go"></i></button></li>';
        }).join('');
        if (attention.length > items.length || attnExpanded) {
            more.hidden = false;
            more.textContent = attnExpanded ? 'Tampilkan lebih sedikit' : 'Lihat semua (' + attention.length + ')';
        } else more.hidden = true;
    }
    $('#lsAttnList').addEventListener('click', function (e) {
        var b = e.target.closest('button[data-i]'); if (!b) return; doJump(attention[+b.dataset.i].jump);
    });
    $('#lsAttnMore').addEventListener('click', function () { attnExpanded = !attnExpanded; renderAttention(); });
    renderAttention();

    // ── Salin ringkasan ───────────────────────────────────────────────────
    function deltaText(cur, prev) {
        if (!D.hasPrev) return '';
        var ch = pctChange(cur, prev);
        if (ch === null) return cur > 0 ? ' (baru, bulan lalu 0)' : '';
        return ' (' + (ch > 0 ? '▲' : ch < 0 ? '▼' : '±') + Math.abs(ch) + '% vs ' + D.prevLabel + ')';
    }
    function buildSummaryText() {
        var k = D.kpi, p = D.prev, L = [];
        L.push('SUMMARY PROGRAM LOYALTY — ' + D.bulanLabel);
        L.push('');
        L.push('Member baru: ' + fmt(k.member) + deltaText(k.member, p.member));
        L.push('Member aktif: ' + fmt(k.aktif) + deltaText(k.aktif, p.aktif));
        L.push('Voucher tersebar: ' + fmt(k.tersebar) + deltaText(k.tersebar, p.tersebar));
        L.push('Voucher dipakai: ' + fmt(k.terpakai) + (k.redemption !== null ? ' — redemption ' + k.redemption + '%' : '') + deltaText(k.terpakai, p.terpakai));
        L.push('Hadiah dibagikan: ' + fmt(k.hadiah) + deltaText(k.hadiah, p.hadiah));
        L.push('Benefit tersalurkan: Rp ' + fmt(k.nilai) + (k.budgetAktif > 0 ? ' — ' + k.serapan + '% serapan budget aktif (Rp ' + fmt(k.budgetAktif) + ')' : '') + deltaText(k.nilai, p.nilai));
        L.push('');
        var st = D.status;
        L.push('Status target (' + st.total + ' program): ' + st.done + ' tercapai, ' + st.partial + ' sebagian, ' + st.belum + ' belum, ' + st.notarget + ' tanpa target.');
        L.push('');
        if (attention.length) {
            L.push('PERLU PERHATIAN (' + attention.length + '):');
            attention.forEach(function (a, i) { L.push((i + 1) + '. ' + a.title + ' — ' + a.detail); });
        } else {
            L.push('Tidak ada butir yang melewati ambang perhatian.');
        }
        return L.join('\n');
    }
    function copyText(txt) {
        if (navigator.clipboard && window.isSecureContext) return navigator.clipboard.writeText(txt);
        return new Promise(function (res, rej) {
            var ta = document.createElement('textarea'); ta.value = txt; ta.style.cssText = 'position:fixed;opacity:0;top:0;left:0';
            document.body.appendChild(ta); ta.select();
            try { document.execCommand('copy') ? res() : rej(); } catch (e) { rej(e); } document.body.removeChild(ta);
        });
    }
    $('#lsCopy').addEventListener('click', function () {
        copyText(buildSummaryText()).then(function () { toast('Ringkasan tersalin ke clipboard'); }, function () { toast('Gagal menyalin — izinkan akses clipboard'); });
    });
    window.LoySum = { analisaSah: function (t) { return analisaSah(t); }, evalAn: function (s, v) { return evalAn(s, v); }, buildSummaryText: buildSummaryText, attention: function () { return attention; }, THRESH: THRESH };

    // ══ Analisa: isian terpandu, kelengkapan, simpan (endpoint & CSRF sama seperti sebelumnya) ═══
    var csrf = { name: D.csrf.name, hash: D.csrf.hash };
    var STATE_PHRASE = { done: 'tercapai', partial: 'sebagian tercapai', belum: 'belum tercapai', notarget: 'tanpa target' };
    function namaKolom(list) { return list.map(function (k) { return KOLOM_LABEL[k] || k; }).join(', '); }
    function boxFields(box) { return box.querySelector('.analisa-fields'); }
    function boxVals(box) { var v = {}; $$('textarea[name]', boxFields(box)).forEach(function (ta) { v[ta.name] = ta.value; }); return v; }
    function boxDirty(box) { var f = boxFields(box); return !!f && $$('textarea[name]', f).some(function (ta) { return ta.value !== ta.defaultValue; }); }
    function anyDirty() { return $$('.analisa-box').some(boxDirty); }
    function discardBox(box) { $$('textarea[name]', boxFields(box)).forEach(function (ta) { ta.value = ta.defaultValue; }); updateBox(box); }
    function progOf(key) { for (var i = 0; i < D.programs.length; i++) if (D.programs[i].key === key) return D.programs[i]; return null; }

    // Perbarui penghitung, catatan, indikator "Lengkap/Kurang", penanda belum-disimpan & tombol simpan satu kotak.
    function updateBox(box) {
        var f = boxFields(box); if (!f) return;
        var status = box.dataset.status, tooLong = false;
        $$('.ls-fld', f).forEach(function (lab) {
            var ta = lab.querySelector('textarea'), cnt = lab.querySelector('.ls-cnt'), note = lab.querySelector('.ls-fld-note');
            var req = lab.dataset.req === '1', n = nChar(ta.value), sah = analisaSah(ta.value), cls = '', txt, msg = '';
            if (req) {
                txt = n + '/' + A.min + ' minimum';
                cls = n === 0 ? '' : (sah ? 'is-ok' : 'is-warn');
                if (n >= A.min && !sah) msg = 'Isian belum sah: harus memuat huruf dan bukan pengulangan satu karakter.';
            } else txt = n + ' karakter';
            if (n > A.maksLunak) { txt += ' · melebihi ' + fmt(A.maksLunak); cls = 'is-over'; msg = 'Terlalu panjang — sebaiknya diringkas (disarankan maks. ' + fmt(A.maksLunak) + ' karakter).'; }
            if (n > A.maksKeras && trimU(ta.value) !== trimU(ta.defaultValue)) { cls = 'is-bad'; tooLong = true; msg = 'Melebihi batas ' + fmt(A.maksKeras) + ' karakter — ringkas dulu agar bisa disimpan.'; }
            cnt.textContent = txt; cnt.className = 'ls-cnt' + (cls ? ' ' + cls : '');
            lab.classList.toggle('is-invalid', cls === 'is-warn' && n >= A.min || cls === 'is-bad');
            if (note) { note.textContent = msg; note.hidden = !msg; }
        });
        var ev = evalAn(status, boxVals(box)), flag = box.querySelector('.analisa-flag');
        flag.className = 'analisa-flag ls-flag ' + (ev.lengkap ? 'is-ok' : 'is-draft');
        flag.dataset.state = ev.lengkap ? 'ok' : (ev.draf ? 'draft' : 'no');
        flag.textContent = ev.lengkap ? 'Lengkap' : 'Kurang: ' + namaKolom(ev.kurang);
        var dirty = boxDirty(box), d = box.querySelector('.ls-dirty'); if (d) d.hidden = !dirty;
        var btn = box.querySelector('.analisa-save'); if (btn) btn.disabled = tooLong || btn.dataset.busy === '1';
        return ev;
    }

    function setRowState(key, state) {
        var row = document.getElementById('row-' + key); if (!row) return;
        var lengkap = state === 'ok';
        row.dataset.need = lengkap ? '0' : '1'; row.dataset.sAnalisa = state === 'ok' ? '2' : (state === 'draft' ? '1' : '0');
        var cell = row.querySelector('[data-an-cell]');
        var map = { ok: ['is-ok', 'check-circle-fill', 'lengkap', 'Analisa lengkap'], draft: ['is-draft', 'circle-half', 'draf', 'Analisa masih draf — ada kolom wajib yang kurang'], no: ['is-no', 'circle', 'belum', 'Analisa belum diisi'] }[state];
        if (cell) { cell.className = 'ls-an ' + map[0]; cell.title = map[3]; cell.innerHTML = '<i class="bi bi-' + map[1] + '"></i><span>' + map[2] + '</span>'; }
    }

    // Tombol Laporan Bulanan: terkunci bila aturan berlaku & masih ada yang kurang
    var cetak = document.getElementById('btnCetak');
    function renderCetak() {
        if (!cetak) return;
        var inc = incompletePrograms().length, lock = locked();
        cetak.classList.toggle('is-locked', lock);
        var ico = cetak.querySelector('[data-ico]'); if (ico) ico.className = 'bi ' + (lock ? 'bi-lock-fill' : 'bi-printer');
        var nb = cetak.querySelector('.ls-lockn'); if (nb) { nb.hidden = !lock; nb.textContent = inc; }
        cetak.title = lock ? inc + ' program belum lengkap — klik untuk melihat daftarnya' : 'Cetak laporan bulanan';
        cetak.setAttribute('aria-label', lock ? 'Laporan Bulanan terkunci: ' + inc + ' program belum lengkap analisanya' : 'Laporan Bulanan');
    }
    if (cetak) cetak.addEventListener('click', function (e) {
        if (anyDirty()) { e.preventDefault(); toast('Simpan dulu perubahan analisa sebelum mencetak'); return; }
        if (locked()) { e.preventDefault(); doJump(['comp']); return; }
        var inc = incompletePrograms().length;
        // Aturan wajib dimatikan di server: pertahankan konfirmasi lama
        if (!A.wajibCetak && inc > 0 && !confirm('Masih ada ' + inc + ' program yang analisanya belum lengkap. Tetap cetak laporan?')) e.preventDefault();
    });

    // Panel merah "Kelengkapan Analisa"
    function renderComp() {
        var panel = document.getElementById('kelengkapan'); if (!panel) return;
        var list = incompletePrograms(), show = locked();
        panel.hidden = !show;
        if (!show) return;
        document.getElementById('lsCompCount').textContent = list.length;
        document.getElementById('lsCompIntro').innerHTML = 'Laporan Bulanan <b>' + esc(D.bulanLabel) + '</b> baru bisa dicetak setelah analisa program di bawah lengkap. ' +
            'Program <i>tercapai</i> atau <i>tanpa target</i>: cukup Highlight. Program <i>sebagian/belum tercapai</i>: Highlight, Kendala, dan Tindak Lanjut. Tiap kolom minimal ' + A.min + ' karakter.';
        document.getElementById('lsCompList').innerHTML = list.map(function (p) {
            return '<li><div class="ls-comp-n"><b>' + esc(p.name) + '</b><span class="ls-pill" data-st="' + (p.status === 'sebagian' ? 'partial' : p.status) + '">' + esc(STATE_PHRASE[p.status] || p.status) + '</span></div>' +
                '<div class="ls-comp-k"><span>Kurang:</span>' + p.kurang.map(function (k) { return '<em>' + esc(KOLOM_LABEL[k] || k) + '</em>'; }).join('') + '</div>' +
                '<button type="button" class="ls-btn ls-btn-ghost ls-btn-sm" data-comp-key="' + esc(p.key) + '"><i class="bi bi-' + (D.canEdit ? 'pencil-square' : 'eye') + '"></i>' + (D.canEdit ? 'Lengkapi' : 'Lihat') + '</button></li>';
        }).join('');
        var foot = document.getElementById('lsCompFoot'); foot.hidden = false;
        foot.innerHTML = A.isAdmin
            ? '<span><i class="bi bi-shield-lock"></i>Sebagai admin Anda dapat mencetak tetap. Tindakan ini dicatat di log aktivitas dan laporan ditandai &ldquo;Analisa belum lengkap&rdquo;.</span><button type="button" class="ls-btn ls-btn-sm" id="lsCompForce"><i class="bi bi-printer"></i>Cetak tetap (admin)</button>'
            : '<span><i class="bi bi-shield-lock"></i>Hanya admin yang dapat mencetak sebelum analisa lengkap.</span>';
    }
    (function () {
        var panel = document.getElementById('kelengkapan'); if (!panel) return;
        panel.addEventListener('click', function (e) {
            var b = e.target.closest('[data-comp-key]');
            if (b) { jumpProgram(b.dataset.compKey, true); return; }
            if (e.target.closest('#lsCompForce')) {
                if (anyDirty()) { toast('Simpan dulu perubahan analisa sebelum mencetak'); return; }
                var n = incompletePrograms().length;
                if (confirm('Masih ada ' + n + ' program yang analisanya belum lengkap.\n\nCetak tetap dilanjutkan? Tindakan ini dicatat di log aktivitas dan laporan ditandai "Analisa belum lengkap".'))
                    window.open(A.printUrl + '&lanjut=1', '_blank');
            }
        });
    })();

    function refreshWarn() {
        var warn = document.getElementById('analisaWarn');
        var empty = incompletePrograms().length;
        if (warn) {
            document.getElementById('analisaWarnCount').textContent = empty;
            warn.style.display = empty > 0 ? '' : 'none';
            warn.dataset.empty = empty;
        }
        var b = $('#lsNeedAnalisa b'); if (b) b.textContent = empty;
    }
    function refreshAll() { attention = buildAttention(); renderAttention(); refreshWarn(); renderCetak(); renderComp(); applyProgFilter(); }

    var warnEl = document.getElementById('analisaWarn');
    if (warnEl) {
        var goNeed = function () { doJump([locked() ? 'comp' : 'need']); };
        warnEl.addEventListener('click', goNeed);
        warnEl.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); goNeed(); } });
    }

    function saveBox(box) {
        var btn = box.querySelector('.analisa-save'), fields = boxFields(box), status = box.querySelector('.analisa-status');
        if (!btn || btn.disabled) return;
        var sent = boxVals(box), local = evalAn(box.dataset.status, sent);
        btn.dataset.busy = '1'; btn.disabled = true; status.textContent = 'Menyimpan...'; status.className = 'analisa-status small text-muted';
        var body = new URLSearchParams();
        body.append(csrf.name, csrf.hash);
        body.append('bulan', D.bulan);
        body.append('source', fields.dataset.source);
        body.append('program_id', fields.dataset.id);
        KOLOM.forEach(function (k) { body.append(k, sent[k] || ''); });
        fetch(D.saveUrl, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: body })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.csrf) csrf.hash = res.csrf;
                if (!res.ok) { status.textContent = res.error || 'Gagal menyimpan'; status.className = 'analisa-status small text-danger'; toast(res.error || 'Gagal menyimpan'); return; }
                // Server menjadi acuan; bila field baru tak ada (server lama) pakai hitungan lokal.
                var lengkap = typeof res.lengkap === 'boolean' ? res.lengkap : local.lengkap;
                var kurang = Array.isArray(res.kurang) ? res.kurang : local.kurang;
                var draf = local.draf;
                var st = lengkap ? 'ok' : (draf ? 'draft' : 'no');
                $$('textarea[name]', fields).forEach(function (ta) { if (sent[ta.name] === ta.value) ta.defaultValue = ta.value; });
                var p = progOf(box.dataset.key);
                if (p) { p.filled = !!res.filled; p.lengkap = lengkap; p.kurang = kurang; p.state = st; }
                setRowState(box.dataset.key, st);
                btn.dataset.busy = '0'; updateBox(box);
                refreshAll();
                status.textContent = 'Tersimpan'; status.className = 'analisa-status small text-success';
                setTimeout(function () { if (status.textContent === 'Tersimpan') status.textContent = ''; }, 2500);
                toast(lengkap ? 'Analisa tersimpan — lengkap' : 'Analisa tersimpan — masih kurang: ' + namaKolom(kurang));
            })
            .catch(function () { status.textContent = 'Gagal menyimpan'; status.className = 'analisa-status small text-danger'; toast('Gagal menyimpan'); })
            .finally(function () { btn.dataset.busy = '0'; updateBox(box); });
    }
    $$('.analisa-box').forEach(function (box) {
        if (!boxFields(box)) return;
        updateBox(box);
        box.addEventListener('input', function (e) { if (e.target.matches('textarea')) updateBox(box); });
        box.addEventListener('keydown', function (e) {
            if (e.target.matches('textarea') && (e.ctrlKey || e.metaKey) && e.key === 'Enter') { e.preventDefault(); saveBox(box); }
        });
        var btn = box.querySelector('.analisa-save'); if (btn) btn.addEventListener('click', function () { saveBox(box); });
    });
    window.addEventListener('beforeunload', function (e) { if (anyDirty()) { e.preventDefault(); e.returnValue = ''; } });
    refreshWarn(); renderCetak(); renderComp();
    if (location.hash === '#kelengkapan') setTimeout(function () { var cp = document.getElementById('kelengkapan'); if (cp && !cp.hidden) { scrollToEl(cp); flash(cp); } }, 250);

    // Akordeon tabel tren
    $$('.ls-acc-h').forEach(function (h) {
        h.addEventListener('click', function () {
            var open = h.getAttribute('aria-expanded') !== 'true';
            h.setAttribute('aria-expanded', open ? 'true' : 'false');
            document.getElementById(h.getAttribute('aria-controls')).hidden = !open;
            h.closest('.ls-acc').classList.toggle('is-open', open);
        });
    });

    // ══ E. Grafik ════════════════════════════════════════════════════════
    if (typeof Chart !== 'undefined') {
        var C = isDark
            ? { blue: '#60a5fa', cyan: '#22d3ee', gold: '#e8c872', green: '#34d399', rose: '#fb7185', text: 'rgba(200,215,245,.8)', grid: 'rgba(148,170,220,.10)' }
            : { blue: '#2563eb', cyan: '#0891b2', gold: '#c2870a', green: '#059669', rose: '#e11d48', text: '#475569', grid: 'rgba(79,70,229,.08)' };
        function alpha(hex, a) { var n = parseInt(hex.slice(1), 16); return 'rgba(' + (n >> 16) + ',' + ((n >> 8) & 255) + ',' + (n & 255) + ',' + a + ')'; }
        var T = D.trend;
        // indeks bulan terpilih
        var selLabel = (function () {
            var m = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];
            var parts = D.bulan.split('-'); return m[+parts[1] - 1] + ' ' + parts[0];
        })();
        var sel = T.labels.indexOf(selLabel);
        function barColors(hex) { return T.labels.map(function (_, i) { return alpha(hex, sel < 0 || i === sel ? .85 : .45); }); }
        var anim = reduceMotion ? false : { duration: 700, easing: 'easeOutQuart' };
        var base = {
            responsive: true, maintainAspectRatio: false, animation: anim,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, boxHeight: 8, padding: 14, font: { size: 11 }, color: C.text } },
                tooltip: { padding: 10, cornerRadius: 10, boxPadding: 4, usePointStyle: true }
            },
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: 11 }, color: C.text } },
                y: { beginAtZero: true, grid: { color: C.grid }, border: { display: false }, ticks: { font: { size: 10 }, color: C.text, precision: 0 } }
            }
        };
        function deep(o) { return JSON.parse(JSON.stringify(o)); }
        var el1 = document.getElementById('trendMember');
        if (el1) {
            var o1 = deep(base); o1.animation = anim;
            o1.plugins.legend.labels.color = C.text;
            o1.scales.y.title = { display: true, text: 'Member baru', color: C.blue, font: { size: 10 } };
            o1.scales.y1 = { position: 'right', beginAtZero: true, grid: { drawOnChartArea: false }, border: { display: false }, ticks: { font: { size: 10 }, color: C.text, precision: 0 }, title: { display: true, text: 'Member aktif', color: C.cyan, font: { size: 10 } } };
            o1.plugins.tooltip.callbacks = null;
            new Chart(el1, {
                data: { labels: T.labels, datasets: [
                    { type: 'bar', label: 'Member Baru', data: T.member, backgroundColor: barColors(C.blue), borderRadius: 6, maxBarThickness: 36, yAxisID: 'y', order: 2 },
                    { type: 'line', label: 'Member Aktif', data: T.aktif, borderColor: C.cyan, backgroundColor: alpha(C.cyan, .12), pointBackgroundColor: C.cyan, pointRadius: 3.5, pointHoverRadius: 5, borderWidth: 2.2, tension: .15, yAxisID: 'y1', order: 1 }
                ] },
                options: o1
            });
        }
        var el2 = document.getElementById('trendVoucher');
        if (el2) {
            var o2 = deep(base); o2.animation = anim;
            o2.scales.y1 = { position: 'right', min: 0, suggestedMax: 100, grid: { drawOnChartArea: false }, border: { display: false }, ticks: { font: { size: 10 }, color: C.text, callback: function (v) { return v + '%'; } }, title: { display: true, text: 'Redemption', color: C.rose, font: { size: 10 } } };
            o2.plugins.tooltip.callbacks = { label: function (ctx) { var v = ctx.parsed.y; return ' ' + ctx.dataset.label + ': ' + (v === null ? '—' : (ctx.dataset.yAxisID === 'y1' ? v + '%' : fmt(v))); } };
            new Chart(el2, {
                data: { labels: T.labels, datasets: [
                    { type: 'bar', label: 'Voucher Tersebar', data: T.sebar, backgroundColor: barColors(C.gold), borderRadius: 6, maxBarThickness: 28, yAxisID: 'y', order: 3 },
                    { type: 'bar', label: 'Voucher Terpakai', data: T.terpakai, backgroundColor: barColors(C.green), borderRadius: 6, maxBarThickness: 28, yAxisID: 'y', order: 2 },
                    { type: 'line', label: 'Tingkat Redemption (%)', data: T.redemption, borderColor: C.rose, pointBackgroundColor: C.rose, pointRadius: 3.5, pointHoverRadius: 5, borderWidth: 2.2, tension: .15, spanGaps: false, yAxisID: 'y1', order: 1 }
                ] },
                options: o2
            });
        }
        var el3 = document.getElementById('dailyChart');
        if (el3) {
            var o3 = deep(base); o3.animation = anim;
            o3.scales.x.ticks.maxRotation = 0; o3.scales.x.ticks.autoSkip = true;
            new Chart(el3, {
                data: { labels: D.daily.labels, datasets: [
                    { type: 'bar', label: 'Member Baru', data: D.daily.member, backgroundColor: alpha(C.blue, .8), borderRadius: 4, maxBarThickness: 18, order: 3 },
                    { type: 'bar', label: 'Voucher Tersebar', data: D.daily.tersebar, backgroundColor: alpha(C.gold, .75), borderRadius: 4, maxBarThickness: 18, order: 2 },
                    { type: 'line', label: 'Voucher Dipakai', data: D.daily.terpakai, borderColor: C.green, pointBackgroundColor: C.green, pointRadius: 2.5, borderWidth: 2, tension: .15, order: 1 }
                ] },
                options: o3
            });
        }
    }
})();
