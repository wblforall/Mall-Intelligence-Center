/* MIC — Creative: Konten Digital (format konten dari katalog, ukuran, periode tayang layar, Design induk, PIC wajib,
   dialog "Atur status" massal untuk item lama, kontrol status konten). Dipakai di /creative dan tab Creative event. */
(function () {
    'use strict';
    function $(s, c) { return (c || document).querySelector(s); }
    function $$(s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); }
    function katalog(nama) { return (window.CRE_DIGITAL || []).filter(function (j) { return j.nama === nama; })[0]; }

    /* ── Format konten ───────────────────────────────────────────────────────── */
    function isiUkuran(sel) {
        var dl = document.getElementById(sel.dataset.list), form = sel.closest('form');
        if (!dl) return;
        var j = katalog(sel.value), uk = $('.js-dig-ukuran', form);
        dl.innerHTML = '';
        if (j) j.ukuran.forEach(function (u) { var o = document.createElement('option'); o.value = u; dl.appendChild(o); });
        if (uk && j) uk.placeholder = j.ukuran.length ? 'pilih dari daftar atau ketik ukuran' : 'ketik ukuran (mis. 1920x1080 px)';
    }
    function lain(form) {
        var sel = $('.js-dig-jenis', form), w = $('.js-dig-lain-wrap', form), t = $('.js-dig-lain', form);
        if (!sel || !w) return;
        var on = sel.value === '__lain';
        w.hidden = !on; if (t) t.disabled = !on;
    }
    function hint(form) {
        var h = $('.js-dig-hint', form), sel = $('.js-dig-jenis', form);
        if (!h || !sel) return;
        var v = sel.value;
        h.hidden = !v;
        h.textContent = v ? 'Format dan ukuran ini memunculkan satu kebutuhan turunan Design di Tema (dipakai bersama bila konten lain memakai format dan ukuran yang sama). Layar digital dipilih di tab Titik setelah item disimpan.' : '';
    }
    document.addEventListener('change', function (e) {
        var sel = e.target.closest ? e.target.closest('.js-dig-jenis') : null;
        if (!sel) return;
        var form = sel.closest('form'), uk = $('.js-dig-ukuran', form);
        isiUkuran(sel); lain(form); hint(form);
        var j = katalog(sel.value);
        if (uk && j && j.ukuran.length === 1 && !uk.value) uk.value = j.ukuran[0];
    });

    window.CRE_DIGITALFILL = function (form, d) {
        if (!form) return;
        var sel = $('.js-dig-jenis', form), uk = $('.js-dig-ukuran', form), t = $('.js-dig-lain', form);
        if (!sel) return;
        d = d || {};
        var jenis = d.jenis || '';
        if (jenis && !katalog(jenis)) { sel.value = '__lain'; if (t) t.value = jenis; } else { sel.value = jenis; if (t) t.value = ''; }
        isiUkuran(sel); lain(form); hint(form);
        if (uk) uk.value = d.ukuran || '';
        var tm = $('.js-dig-tm', form), ts = $('.js-dig-ts', form);
        if (tm) tm.value = d.mulai || ''; if (ts) ts.value = d.selesai || '';
        window.CRE_DIGITALDESIGN(form, d.temaKey, d.design);
    };
    window.CRE_DIGITALDESIGN = function (form, temaKey, selected) {
        var wrap = $('.js-dig-design-wrap', form), sel = $('.js-dig-design', form);
        if (!wrap || !sel) return;
        var list = (window.CRE_DESIGNS || {})[temaKey || ''] || [];
        sel.innerHTML = '';
        list.forEach(function (x) { var o = document.createElement('option'); o.value = x.ref; o.textContent = x.nama; sel.appendChild(o); });
        if (selected && list.some(function (x) { return x.ref === selected; })) sel.value = selected;
        wrap.hidden = list.length < 2; sel.disabled = list.length < 2;
    };
    /* Aktifkan / matikan wajib-isi PIC sesuai jenis item (isian tersembunyi tidak boleh menahan pengiriman). */
    window.CRE_PICWAJIB = function (input, wajib) {
        if (!input) return;
        input.required = !!wajib;
        var lb = input.parentNode && input.parentNode.querySelector('label'), rq = lb && lb.querySelector('.js-picw');
        if (lb && wajib && !rq) { rq = document.createElement('span'); rq.className = 'text-danger js-picw'; rq.textContent = ' *'; lb.appendChild(rq); }
        if (rq) rq.hidden = !wajib;
    };

    /* ── Kontrol status konten: Mundur / Maju (alasan bila Dilaporkan tanpa insight) ── */
    document.addEventListener('submit', function (e) {
        var f = e.target;
        if (!f.matches || !f.matches('form[data-kn-status]')) return;
        var sub = e.submitter, target = sub && sub.dataset ? sub.dataset.status : '';
        if (target) { var h = $('input[name="status"]', f); if (h) h.value = target; }
        if (target === 'dilaporkan' && f.dataset.insight === '0' && !f.dataset.alasan) {
            var a = prompt('Belum ada insight untuk konten ini. Isi alasan (min. 10 karakter) bila tetap ingin menandainya Dilaporkan:', '');
            if (a === null) { e.preventDefault(); return; }
            var al = $('input[name="alasan"]', f); if (al) al.value = a;
        }
    });

    /* ── "Atur status" massal untuk item lama ──────────────────────────────── */
    var modal = document.getElementById('statusMassalModal');
    if (!modal) return;
    var body = $('#smBody', modal), sum = $('#smSum', modal), submit = $('#smSubmit', modal), err = $('#smError', modal), form = $('#smForm', modal);
    var STATUS = [['rencana', 'Rencana'], ['produksi', 'Produksi'], ['tayang', 'Tayang'], ['dilaporkan', 'Dilaporkan']];
    function fmt(d) { var m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(d || ''); return m ? m[3] + '/' + m[2] + '/' + m[1] : '—'; }
    function el(t, c, x) { var n = document.createElement(t); if (c) n.className = c; if (x != null) n.textContent = x; return n; }
    function hitung() {
        var n = $$('input[name="pilih[]"]:checked', body).length;
        sum.textContent = n + ' konten dipilih';
        submit.disabled = n === 0;
    }
    function boleh(it, st) { return !((st === 'tayang' && !it.tanggal_take) || (st === 'dilaporkan' && !it.insight)); }
    function render(items) {
        body.innerHTML = '';
        if (!items.length) { body.appendChild(el('div', 'sm-empty', 'Tidak ada konten digital yang statusnya belum diatur.')); hitung(); return; }
        var tbl = el('table', 'sm-tbl'), thead = el('thead'), tr = el('tr');
        ['', 'Konten', 'Tema', 'Take', 'Insight', 'Status'].forEach(function (h, i) { var th = el('th', null, h); if (i === 0) { var all = el('input'); all.type = 'checkbox'; all.checked = true; all.setAttribute('aria-label', 'Pilih semua'); all.id = 'smAll'; th.appendChild(all); } tr.appendChild(th); });
        thead.appendChild(tr); tbl.appendChild(thead);
        var tb = el('tbody');
        items.forEach(function (it) {
            var r = el('tr'), c0 = el('td'), cb = el('input'); cb.type = 'checkbox'; cb.name = 'pilih[]'; cb.value = it.key; cb.checked = true; cb.setAttribute('aria-label', 'Pilih ' + it.nama); c0.appendChild(cb); r.appendChild(c0);
            var c1 = el('td', 'sm-nm'); c1.appendChild(el('b', null, it.nama)); if (it.pic) c1.appendChild(el('em', null, it.pic)); r.appendChild(c1);
            r.appendChild(el('td', 'sm-tm', it.tema));
            r.appendChild(el('td', 'sm-nw', fmt(it.tanggal_take)));
            r.appendChild(el('td', 'sm-nw', it.insight ? String(it.insight) : '—'));
            var c5 = el('td'), sel = el('select', 'form-select form-select-sm'); sel.name = 'status[' + it.key + ']'; sel.setAttribute('aria-label', 'Status ' + it.nama);
            STATUS.forEach(function (s) { var o = el('option', null, s[1]); o.value = s[0]; if (!boleh(it, s[0])) o.disabled = true; sel.appendChild(o); });
            sel.value = it.usulan; c5.appendChild(sel); c5.appendChild(el('span', 'sm-why', 'usulan: ' + it.alasan)); r.appendChild(c5);
            tb.appendChild(r);
        });
        tbl.appendChild(tb); body.appendChild(tbl);
        $('#smAll', body).addEventListener('change', function () { $$('input[name="pilih[]"]', body).forEach(function (b) { b.checked = this.checked; }, this); hitung(); });
        hitung();
    }
    document.addEventListener('click', function (e) {
        var b = e.target.closest('[data-sm-open]');
        if (!b) return;
        e.preventDefault();
        body.innerHTML = '<div class="sm-empty">Memuat daftar…</div>'; err.hidden = true; submit.disabled = true; sum.textContent = '';
        bootstrap.Modal.getOrCreateInstance(modal).show();
        fetch(b.dataset.usulan, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (d) { if (!d.ok) { body.innerHTML = ''; err.hidden = false; err.textContent = d.message || 'Gagal memuat.'; return; } render(d.items || []); })
            .catch(function () { body.innerHTML = ''; err.hidden = false; err.textContent = 'Gagal memuat daftar. Muat ulang halaman lalu coba lagi.'; });
    });
    body.addEventListener('change', hitung);
    form.addEventListener('submit', function (e) {
        var n = $$('input[name="pilih[]"]:checked', body).length;
        if (!n || !confirm('Atur status ' + n + ' konten digital sesuai pilihan? Status hanya ditulis untuk yang dicentang.')) { e.preventDefault(); return; }
        submit.disabled = true;
    });
})();
