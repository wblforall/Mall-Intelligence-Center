/* ═══════════════════════════════════════════════════════════════════════
   MIC — Summary Sponsorship (analisis)
   Data berasal dari window.SPONSUM (disusun di view dari controller).
   Semua ambang "Perlu Perhatian" ada di THRESH — ubah di sini.
   ═══════════════════════════════════════════════════════════════════════ */
(function () {
    'use strict';

    // ── Ambang & batas (mudah diubah) ─────────────────────────────────────
    var THRESH = {
        PACE_RATIO: 0.5,        // progress (realisasi/target) < rasio ini x proporsi waktu periode berlalu => "tertinggal"
        PACE_MIN_ELAPSED: 0.25, // pace baru dinilai bila minimal sekian bagian periode sudah berlalu
        UNPAID_DAYS: 30,        // sponsor Terkonfirmasi (belum Lunas) dianggap lama bila terakhir diperbarui >= sekian hari
        DROP_PCT: 30,           // % — penurunan total vs bulan lalu dianggap besar
        MIN_PREV: 1000000,      // Rp — pembanding bulan lalu minimal sekian agar penurunan dihitung
        MAX_SHOWN: 8,           // jumlah butir tampil sebelum "lihat semua"
        MAX_PER_KIND: 4         // batas butir per jenis pada tampilan ringkas
    };

    var D = window.SPONSUM;
    var root = document.getElementById('sponSum');
    if (!D || !root) return;

    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    var nf = new Intl.NumberFormat('id-ID');
    function fmt(n) { return nf.format(Math.round(n)); }
    function rp(n) { return 'Rp ' + fmt(n); }
    function short(n) {
        n = Math.round(n);
        if (n >= 1e9) return 'Rp ' + (n / 1e9).toLocaleString('id-ID', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + ' M';
        if (n >= 1e6) return 'Rp ' + (n / 1e6).toLocaleString('id-ID', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + ' jt';
        return rp(n);
    }
    function $(s, c) { return (c || root).querySelector(s); }
    function $$(s, c) { return Array.prototype.slice.call((c || root).querySelectorAll(s)); }
    function esc(s) { return String(s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
    function pctChange(cur, prev) { return prev > 0 ? Math.round((cur - prev) / prev * 100) : null; }
    function round1(x) { return Math.round(x * 10) / 10; }

    // Offset tempel = tinggi topbar aplikasi
    function setTop() {
        var tb = document.querySelector('.topbar');
        root.style.setProperty('--ss-top', (tb ? tb.offsetHeight : 0) + 'px');
    }
    setTop(); window.addEventListener('resize', setTop);

    // ── Toast kecil ───────────────────────────────────────────────────────
    var toastT;
    function toast(msg) {
        var el = document.getElementById('ssToast');
        if (!el) { el = document.createElement('div'); el.id = 'ssToast'; el.className = 'ss-toast'; el.setAttribute('role', 'status'); root.appendChild(el); }
        el.textContent = msg; el.classList.add('is-on');
        clearTimeout(toastT); toastT = setTimeout(function () { el.classList.remove('is-on'); }, 2200);
    }

    // ══ Tabel generik: urut ══════════════════════════════════════════════
    function makeSortable(table, getUnits) {
        var ths = $$('thead th[data-sort]', table);
        ths.forEach(function (th) {
            th.querySelector('button').addEventListener('click', function () {
                var k = th.dataset.sort, numeric = th.hasAttribute('data-num');
                var cur = th.getAttribute('aria-sort');
                var dir = cur === 'none' ? (numeric ? 'descending' : 'ascending') : (cur === 'ascending' ? 'descending' : 'ascending');
                ths.forEach(function (o) { o.setAttribute('aria-sort', 'none'); });
                th.setAttribute('aria-sort', dir);
                var key = 's' + k.charAt(0).toUpperCase() + k.slice(1);
                var units = getUnits();
                units.sort(function (a, b) {
                    var x = a.row.dataset[key], y = b.row.dataset[key], r;
                    if (numeric) r = parseFloat(x) - parseFloat(y); else r = String(x).localeCompare(String(y), 'id');
                    if (r === 0) r = (parseInt(a.row.dataset.ord || 0, 10) - parseInt(b.row.dataset.ord || 0, 10));
                    return dir === 'ascending' ? r : -r;
                });
                var tbody = table.tBodies[0];
                units.forEach(function (u) { tbody.appendChild(u.row); if (u.det) tbody.appendChild(u.det); });
            });
        });
    }

    // ══ D. Tabel Kinerja Program ═════════════════════════════════════════
    var progTable = document.getElementById('ssProgTable');
    var fState = { q: '', src: '', status: '', rel: '', need: false };
    function progUnits() {
        return $$('tr.ss-row', progTable).map(function (r) { return { row: r, det: document.getElementById('det-' + r.dataset.key) }; });
    }
    function applyProgFilter() {
        var shown = 0, all = progUnits();
        all.forEach(function (u) {
            var d = u.row.dataset;
            var ok = (!fState.q || d.name.indexOf(fState.q) > -1) && (!fState.src || d.src === fState.src) && (!fState.status || d.status === fState.status) &&
                (!fState.rel || d.rel === fState.rel) && (!fState.need || d.need === '1');
            u.row.hidden = !ok;
            if (u.det) u.det.hidden = !ok || u.row.getAttribute('data-open') !== '1';
            if (ok) shown++;
        });
        var c = document.getElementById('ssProgCount'); if (c) c.textContent = shown + (shown !== all.length ? ' / ' + all.length : '');
        var em = document.getElementById('ssProgEmpty'); if (em) em.hidden = shown > 0;
        var any = fState.q || fState.src || fState.status || fState.rel || fState.need;
        var rs = document.getElementById('ssReset'); if (rs) rs.hidden = !any;
    }
    function syncSeg(group, val) {
        $$('.ss-seg[data-group="' + group + '"] button').forEach(function (b) { b.classList.toggle('is-on', b.dataset.v === val); });
    }
    function setNeed(on) {
        fState.need = on;
        var b = document.getElementById('ssNeedAnalisa'); if (b) { b.classList.toggle('is-on', on); b.setAttribute('aria-pressed', on ? 'true' : 'false'); }
    }
    function resetFilter(need) {
        fState = { q: '', src: '', status: '', rel: '', need: !!need };
        var si = document.getElementById('ssSearch'); if (si) si.value = '';
        syncSeg('src', ''); syncSeg('status', ''); syncSeg('rel', ''); setNeed(!!need); applyProgFilter();
    }
    function setOpen(row, open) {
        var det = document.getElementById('det-' + row.dataset.key);
        row.setAttribute('data-open', open ? '1' : '0');
        var btn = row.querySelector('.ss-exp'); if (btn) btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (det) det.hidden = !open || row.hidden;
    }
    if (progTable) {
        var s = document.getElementById('ssSearch');
        if (s) s.addEventListener('input', function () { fState.q = s.value.trim().toLowerCase(); applyProgFilter(); });
        $$('.ss-seg').forEach(function (seg) {
            seg.addEventListener('click', function (e) {
                var b = e.target.closest('button'); if (!b) return;
                fState[seg.dataset.group] = b.dataset.v; syncSeg(seg.dataset.group, b.dataset.v); applyProgFilter();
            });
        });
        var nb = document.getElementById('ssNeedAnalisa');
        if (nb) nb.addEventListener('click', function () { setNeed(!fState.need); applyProgFilter(); });
        var rs = document.getElementById('ssReset');
        if (rs) rs.addEventListener('click', function () { resetFilter(false); });
        $$('.ss-exp', progTable).forEach(function (btn) {
            btn.addEventListener('click', function () {
                var row = btn.closest('tr'); setOpen(row, row.getAttribute('data-open') !== '1');
            });
        });
        makeSortable(progTable, progUnits);
        applyProgFilter();
    }

    function scrollToEl(el, extra) {
        if (!el) return;
        var bar = document.getElementById('ssBar');
        var sticky = bar && getComputedStyle(bar).position === 'sticky' ? bar.offsetHeight : 0;
        var off = (document.querySelector('.topbar') ? document.querySelector('.topbar').offsetHeight : 0) + sticky + 14 + (extra || 0);
        var y = el.getBoundingClientRect().top + window.pageYOffset - off;
        window.scrollTo({ top: y, behavior: reduceMotion ? 'auto' : 'smooth' });
    }
    function flash(el) {
        if (!el) return;
        el.classList.remove('is-flash'); void el.offsetWidth; el.classList.add('is-flash');
        setTimeout(function () { el.classList.remove('is-flash'); }, 2600);
    }
    function jumpProgram(id, openForm) {
        var row = document.getElementById('row-' + id); if (!row) return;
        if (row.hidden) resetFilter(false);
        if (openForm) setOpen(row, true);
        scrollToEl(row); flash(row);
    }

    // ══ C. Panel Perlu Perhatian ═════════════════════════════════════════
    var KIND = {
        turun:     { rank: 1, level: 'bad',  icon: 'graph-down-arrow' },
        pace:      { rank: 2, level: 'bad',  icon: 'speedometer2' },
        noreal:    { rank: 3, level: 'warn', icon: 'cash-stack' },
        unpaid:    { rank: 4, level: 'warn', icon: 'hourglass-split' },
        analisa:   { rank: 5, level: 'info', icon: 'chat-left-dots' }
    };
    var DAY = 86400000;
    function parseD(s) { var p = s.split('-'); return Date.UTC(+p[0], +p[1] - 1, +p[2]); }
    function fmtD(s) { var m = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'], p = s.split('-'); return +p[2] + ' ' + m[+p[1] - 1] + ' ' + p[0]; }
    // Proporsi periode program yang sudah berlalu per tanggal "hari ini" (0..1); null bila periode tak lengkap
    function elapsed(p) {
        if (!p.mulai || !p.selesai) return null;
        var a = parseD(p.mulai), b = parseD(p.selesai), t = parseD(D.today);
        if (b < a) return null;
        if (t < a) return 0;
        if (t >= b) return 1;
        return (t - a + DAY) / (b - a + DAY);
    }

    function buildAttention() {
        var out = [];
        var K = D.kpi;
        // 1) Penurunan total vs bulan lalu
        if (K.terkumpulPrev !== null && K.terkumpulPrev >= THRESH.MIN_PREV) {
            var ch = pctChange(K.terkumpul, K.terkumpulPrev);
            if (ch !== null && ch <= -THRESH.DROP_PCT) {
                out.push({ kind: 'turun', title: 'Terkumpul turun ' + Math.abs(ch) + '% dari ' + D.prevLabel,
                    detail: short(K.terkumpulPrev) + ' → ' + short(K.terkumpul) + ' (selisih ' + short(Math.abs(K.terkumpul - K.terkumpulPrev)) + ' lebih rendah)', jump: ['chart'], sortKey: ch });
            }
        }
        D.programs.forEach(function (p) {
            // 2) Program aktif dengan progress tertinggal dari proporsi waktu
            if (p.active && p.target > 0 && p.relevant) {
                var el = elapsed(p);
                if (el !== null && el >= THRESH.PACE_MIN_ELAPSED) {
                    var prog = p.all / p.target;
                    if (prog < el * THRESH.PACE_RATIO) {
                        var selesai = el >= 1;
                        out.push({ kind: 'pace', title: p.name + ': progress ' + round1(prog * 100) + '% dari target',
                            detail: (selesai ? 'Periode ' + fmtD(p.mulai) + ' – ' + fmtD(p.selesai) + ' sudah berakhir' : Math.round(el * 100) + '% periode sudah berlalu') +
                                ' · terkumpul ' + short(p.all) + ' dari target ' + short(p.target) + ' (kurang ' + short(p.target - p.all) + ')',
                            jump: ['prog', p.id], sortKey: prog - el });
                    }
                }
            }
            // 3) Program aktif yang periodenya mencakup bulan terpilih tetapi belum ada realisasi bulan itu
            if (p.active && p.inPeriod && p.month === 0) {
                out.push({ kind: 'noreal', title: p.name + ': belum ada realisasi di ' + D.bulanLabel,
                    detail: 'Periode ' + fmtD(p.mulai) + (p.selesai ? ' – ' + fmtD(p.selesai) : '') + ' mencakup bulan ini, realisasi bulan ini Rp 0', jump: ['prog', p.id], sortKey: 0 });
            }
            // 4) Sponsor Terkonfirmasi (belum Lunas) lama
            if (p.unpaidN > 0 && p.unpaidOldest >= THRESH.UNPAID_DAYS) {
                out.push({ kind: 'unpaid', title: p.name + ': ' + p.unpaidN + ' sponsor Terkonfirmasi belum Lunas',
                    detail: 'Nilai ' + short(p.unpaidNilai) + ' · yang terlama terakhir diperbarui ' + p.unpaidOldest + ' hari lalu', jump: ['prog', p.id], sortKey: -p.unpaidOldest });
            }
        });
        // 5) Program tanpa analisa bulan ini
        if (D.canEdit) {
            var tanpa = D.programs.filter(function (p) { return p.relevant && !p.filled; }).length;
            if (tanpa > 0) out.push({ kind: 'analisa', title: tanpa + ' program belum ada analisa bulan ini', detail: 'Hanya program yang berjalan / punya realisasi di ' + D.bulanLabel + ' · klik untuk menyaring tabel', jump: ['need'] });
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
        else if (j[0] === 'need') { resetFilter(true); scrollToEl(document.getElementById('ssPrograms')); }
        else if (j[0] === 'chart') { var cv = document.getElementById('trendChart') || document.getElementById('dailyChart'); scrollToEl(cv ? cv.closest('.ss-card') : null); }
    }
    function renderAttention() {
        var list = $('#ssAttnList'), cEl = $('#ssAttnCount'), more = $('#ssAttnMore');
        cEl.textContent = attention.length;
        cEl.classList.toggle('is-ok', attention.length === 0);
        if (!attention.length) {
            list.innerHTML = '<li class="ss-attn-empty"><i class="bi bi-check2-circle"></i>Tidak ada butir yang melewati ambang pada ' + esc(D.bulanLabel) + '.</li>';
            more.hidden = true; return;
        }
        var items = attnExpanded ? attention : pickCompact(attention);
        list.innerHTML = items.map(function (a) {
            var k = KIND[a.kind];
            return '<li class="ss-attn-i" data-level="' + k.level + '"><button type="button" data-i="' + attention.indexOf(a) + '">' +
                '<span class="ss-attn-ico"><i class="bi bi-' + k.icon + '"></i></span>' +
                '<span class="ss-attn-tx"><b>' + esc(a.title) + '</b><small>' + esc(a.detail) + '</small></span>' +
                '<i class="bi bi-arrow-right-short ss-attn-go"></i></button></li>';
        }).join('');
        if (attention.length > items.length || attnExpanded) {
            more.hidden = false;
            more.textContent = attnExpanded ? 'Tampilkan lebih sedikit' : 'Lihat semua (' + attention.length + ')';
        } else more.hidden = true;
    }
    $('#ssAttnList').addEventListener('click', function (e) {
        var b = e.target.closest('button[data-i]'); if (!b) return; doJump(attention[+b.dataset.i].jump);
    });
    $('#ssAttnMore').addEventListener('click', function () { attnExpanded = !attnExpanded; renderAttention(); });
    renderAttention();

    // ── Salin ringkasan ───────────────────────────────────────────────────
    function deltaText(cur, prev) {
        if (prev === null || prev === undefined) return '';
        var ch = pctChange(cur, prev);
        if (ch === null) return cur > 0 ? ' (baru, bulan lalu Rp 0)' : '';
        return ' (' + (ch > 0 ? '▲' : ch < 0 ? '▼' : '±') + Math.abs(ch) + '% vs ' + D.prevLabel + ')';
    }
    function buildSummaryText() {
        var k = D.kpi, L = [];
        L.push('SUMMARY SPONSORSHIP — ' + D.bulanLabel);
        L.push('');
        L.push('Terkumpul bulan ini: ' + rp(k.terkumpul) + deltaText(k.terkumpul, k.terkumpulPrev));
        L.push('Nilai dikonfirmasi: ' + rp(k.committed) + ' (' + k.sponsor + ' sponsor deal)');
        L.push('Total terkumpul (All): ' + rp(k.grand) + (k.pctRealVsConf !== null ? ' — ' + k.pctRealVsConf + '% dari nilai dikonfirmasi' : ''));
        if (k.pctTarget !== null) L.push('Capaian target program: ' + k.pctTarget + '% (' + rp(k.progAll) + ' dari target ' + rp(k.targetTotal) + ')');
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
    $('#ssCopy').addEventListener('click', function () {
        copyText(buildSummaryText()).then(function () { toast('Ringkasan tersalin ke clipboard'); }, function () { toast('Gagal menyalin — izinkan akses clipboard'); });
    });
    window.SponSum = { buildSummaryText: buildSummaryText, attention: function () { return attention; }, THRESH: THRESH };

    // ══ A + simpan Analisa (endpoint, field & CSRF sama seperti sebelumnya) ═
    function refreshWarn() {
        var warn = document.getElementById('analisaWarn'); if (!warn) return;
        var empty = $$('tr.ss-row').filter(function (r) { return r.dataset.need === '1'; }).length;
        document.getElementById('analisaWarnCount').textContent = empty;
        warn.style.display = empty > 0 ? '' : 'none';
        var b = $('#ssNeedAnalisa b'); if (b) b.textContent = empty;
    }
    refreshWarn();
    var warnEl = document.getElementById('analisaWarn');
    if (warnEl) {
        var goNeed = function () { doJump(['need']); };
        warnEl.addEventListener('click', goNeed);
        warnEl.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); goNeed(); } });
    }
    $$('.analisa-form').forEach(function (form) {
        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            var pid = form.dataset.program, bulan = form.dataset.bulan;
            var status = form.querySelector('.analisa-status'), btn = form.querySelector('[type=submit]');
            var box = form.closest('.analisa-box'), flag = box.querySelector('.analisa-flag');
            btn.disabled = true; status.textContent = 'Menyimpan...'; status.className = 'analisa-status small text-muted';
            try {
                var fd = new FormData(form);
                fd.append('program_id', pid);
                fd.append('bulan', bulan);
                var resp = await fetch(D.saveUrl, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd });
                var json = await resp.json();
                if (json.csrf) { $$('input[name="' + D.csrf.name + '"]').forEach(function (el) { el.value = json.csrf; }); }
                if (json.ok) {
                    status.textContent = 'Tersimpan.'; status.className = 'analisa-status small text-success';
                    var filled = !!json.filled;
                    flag.dataset.filled = filled ? '1' : '0';
                    flag.textContent = filled ? 'terisi' : 'belum diisi';
                    flag.className = 'analisa-flag ss-flag ' + (filled ? 'is-ok' : 'is-no');
                    var row = document.getElementById('row-' + pid);
                    var prog = D.programs.filter(function (p) { return String(p.id) === String(pid); })[0];
                    if (prog) prog.filled = filled;
                    if (row) {
                        row.dataset.need = (!filled && row.dataset.rel === '1') ? '1' : '0'; row.dataset.sAnalisa = filled ? '1' : '0';
                        var cell = row.querySelector('[data-an-cell]');
                        if (cell) { cell.className = 'ss-an ' + (filled ? 'is-ok' : 'is-no'); cell.innerHTML = '<i class="bi bi-' + (filled ? 'check-circle-fill' : 'circle') + '"></i><span>' + (filled ? 'terisi' : 'belum') + '</span>'; cell.title = filled ? 'Analisa sudah diisi' : 'Analisa belum diisi'; }
                    }
                    attention = buildAttention(); renderAttention(); refreshWarn(); applyProgFilter();
                    setTimeout(function () { status.textContent = ''; }, 3000);
                } else {
                    status.textContent = 'Gagal: ' + (json.error || ''); status.className = 'analisa-status small text-danger';
                }
            } catch (err) {
                status.textContent = 'Error koneksi.'; status.className = 'analisa-status small text-danger';
            }
            btn.disabled = false;
        });
    });

    // ══ Panel Kemungkinan Sponsor Dobel (hanya ada bila pengguna berhak) ═══
    $$('.ss-dbl-all').forEach(function (all) {
        all.addEventListener('change', function () {
            $$('input[name="pasangan[]"]', all.closest('form')).forEach(function (c) { c.checked = all.checked; });
        });
    });
    $$('.ss-dbl-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            var b = e.submitter, n = $$('input[name="pasangan[]"]:checked', form).length;
            if (!n) { e.preventDefault(); toast('Pilih minimal satu pasangan'); return; }
            if (!b) return;
            var sisi = form.querySelector('select[name="sisi"]');
            var msg = b.dataset.konfirmasi === 'dobel'
                ? 'Konfirmasi ' + n + ' pasangan sebagai DOBEL?\n\nSisi ' + (sisi && sisi.value === 'event' ? 'event' : 'program mandiri') + ' dikecualikan dari semua total (data tidak dihapus). Bisa dibatalkan kapan saja.'
                : 'Tandai ' + n + ' pasangan sebagai BUKAN dobel? Pasangan ini tidak akan muncul lagi di pendeteksi.';
            if (!window.confirm(msg)) e.preventDefault();
        });
    });
    $$('form.ss-dbl-undo').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!window.confirm('Batalkan keputusan ini? Angka gabungan dihitung ulang.')) e.preventDefault();
        });
    });
    $$('.ss-dbl-jump').forEach(function (a) {
        a.addEventListener('click', function (e) {
            var d = document.getElementById(a.dataset.open); if (!d) return;
            e.preventDefault(); d.open = true; scrollToEl(d.closest('.ss-card') || d);
        });
    });

    // Akordeon tabel tren
    $$('.ss-acc-h').forEach(function (h) {
        h.addEventListener('click', function () {
            var open = h.getAttribute('aria-expanded') !== 'true';
            h.setAttribute('aria-expanded', open ? 'true' : 'false');
            document.getElementById(h.getAttribute('aria-controls')).hidden = !open;
            h.closest('.ss-acc').classList.toggle('is-open', open);
        });
    });

    // ══ F. Grafik ════════════════════════════════════════════════════════
    if (typeof Chart !== 'undefined') {
        var C = isDark
            ? { gold: '#e8c872', blue: '#60a5fa', cyan: '#22d3ee', text: 'rgba(200,215,245,.8)', grid: 'rgba(148,170,220,.10)' }
            : { gold: '#c2870a', blue: '#2563eb', cyan: '#0891b2', text: '#475569', grid: 'rgba(79,70,229,.08)' };
        function alpha(hex, a) { var n = parseInt(hex.slice(1), 16); return 'rgba(' + (n >> 16) + ',' + ((n >> 8) & 255) + ',' + (n & 255) + ',' + a + ')'; }
        var anim = reduceMotion ? false : { duration: 700, easing: 'easeOutQuart' };
        function axisMoney(v) {
            if (v >= 1e9) return (v / 1e9).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' M';
            if (v >= 1e6) return (v / 1e6).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' jt';
            return v.toLocaleString('id-ID');
        }
        function baseOpts() {
            return {
                responsive: true, maintainAspectRatio: false, animation: anim,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, boxHeight: 8, padding: 14, font: { size: 11 }, color: C.text } },
                    tooltip: { padding: 10, cornerRadius: 10, boxPadding: 4, usePointStyle: true }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 11 }, color: C.text, maxRotation: 0, autoSkip: true } },
                    y: { beginAtZero: true, grid: { color: C.grid }, border: { display: false }, ticks: { font: { size: 10 }, color: C.text, callback: axisMoney } }
                }
            };
        }
        var elD = document.getElementById('dailyChart');
        if (elD) {
            var oD = baseOpts();
            oD.scales.y1 = { position: 'right', beginAtZero: true, grid: { drawOnChartArea: false }, border: { display: false }, ticks: { font: { size: 10 }, color: C.text, callback: axisMoney }, title: { display: true, text: 'Kumulatif', color: C.cyan, font: { size: 10 } } };
            oD.plugins.tooltip.callbacks = {
                title: function (items) { return items[0].label + ' ' + D.bulanLabel; },
                label: function (ctx) { return ' ' + ctx.dataset.label + ': ' + rp(ctx.parsed.y); }
            };
            new Chart(elD, {
                data: { labels: D.daily.labels, datasets: [
                    { type: 'bar', label: 'Realisasi harian', data: D.daily.vals, backgroundColor: D.daily.vals.map(function (v) { return alpha(C.gold, v > 0 ? .85 : .15); }), borderRadius: 4, maxBarThickness: 18, yAxisID: 'y', order: 2 },
                    { type: 'line', label: 'Kumulatif bulan ini', data: D.daily.cum, borderColor: C.cyan, backgroundColor: alpha(C.cyan, .1), pointRadius: 0, pointHoverRadius: 4, borderWidth: 2, tension: .15, yAxisID: 'y1', order: 1 }
                ] },
                options: oD
            });
        }
        var elT = document.getElementById('trendChart');
        if (elT) {
            var T = D.trend, selIdx = T.keys.indexOf(D.bulan);
            var oT = baseOpts();
            oT.plugins.tooltip.callbacks = {
                title: function (items) { return items[0].label + (T.keys[items[0].dataIndex] === D.bulan ? ' (dipilih)' : ''); },
                label: function (ctx) { return ' Total realisasi: ' + rp(ctx.parsed.y); },
                afterLabel: function (ctx) {
                    var i = ctx.dataIndex; if (i < 1) return '';
                    var prev = T.vals[i - 1], ch = pctChange(ctx.parsed.y, prev);
                    return ch === null ? '' : (ch > 0 ? '▲ ' : ch < 0 ? '▼ ' : '± ') + Math.abs(ch) + '% vs ' + T.labels[i - 1];
                }
            };
            oT.onClick = function (e, els) { if (els.length) location.href = '?bulan=' + T.keys[els[0].index]; };
            oT.onHover = function (e, els) { e.native.target.style.cursor = els.length ? 'pointer' : 'default'; };
            new Chart(elT, {
                type: 'bar',
                data: { labels: T.labels, datasets: [
                    { label: 'Total realisasi (program + event)', data: T.vals, backgroundColor: T.vals.map(function (_, i) { return alpha(C.blue, i === selIdx ? .95 : .5); }), borderColor: T.vals.map(function (_, i) { return i === selIdx ? C.gold : 'transparent'; }), borderWidth: 2, borderRadius: 6, maxBarThickness: 40 }
                ] },
                options: oT
            });
        }
    }
})();
