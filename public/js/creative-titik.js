/* MIC — Creative: Media Cetak (periode pasang, Design induk, hint titik), panel "Pilih titik" (modal),
   cabut titik, dan bukti pasang per titik. Dipakai di /creative dan tab Creative event. */
(function () {
    'use strict';
    function $(s, c) { return (c || document).querySelector(s); }
    function $$(s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); }

    /* ── Isian Media Cetak: hint titik, Design induk, wajib budget ───────────── */
    function adaMaster(jenis) {
        var j = String(jenis || '').trim().toLowerCase();
        return (window.CRE_TITIKJENIS || []).some(function (n) { return String(n).toLowerCase() === j; });
    }
    function hint(form) {
        var sel = $('.js-cetak-jenis', form), h = $('.js-cetak-hint', form);
        if (!sel || !h) return;
        var v = sel.value;
        if (!v) { h.hidden = true; return; }
        h.hidden = false;
        h.textContent = adaMaster(v)
            ? 'Titik ' + v + ' dipilih dari master Media Promo setelah item disimpan (isi periode pasang agar ketersediaan dihitung).'
            : 'Jenis ini tidak punya titik di master Media Promo, jadi titik tidak diperlukan.';
    }
    window.CRE_DESIGNSYNC = function (form, temaKey, selected) {
        var wrap = $('.js-cetak-design-wrap', form), sel = $('.js-cetak-design', form);
        if (!wrap || !sel) return;
        var list = (window.CRE_DESIGNS || {})[temaKey || ''] || [];
        sel.innerHTML = '';
        list.forEach(function (d) { var o = document.createElement('option'); o.value = d.ref; o.textContent = d.nama; sel.appendChild(o); });
        if (selected && list.some(function (d) { return d.ref === selected; })) sel.value = selected;
        wrap.hidden = list.length < 2;
        sel.disabled = list.length < 2;   // satu Design: tak perlu dikirim (pakai bawaan)
    };
    window.CRE_CETAKEXTRA = function (form, d) {
        if (!form) return;
        d = d || {};
        if ('mulai' in d) { var pm = $('.js-cetak-pm', form); if (pm) pm.value = d.mulai || ''; }
        if ('selesai' in d) { var ps = $('.js-cetak-ps', form); if (ps) ps.value = d.selesai || ''; }
        if ('temaKey' in d || 'design' in d) window.CRE_DESIGNSYNC(form, d.temaKey, d.design);
        if ('budgetWajib' in d) {
            var b = $('input[name="budget"]', form);
            if (b) {
                b.required = !!d.budgetWajib;
                var lb = b.parentNode && b.parentNode.querySelector('label'), rq = lb && lb.querySelector('.js-bw');
                if (lb && d.budgetWajib && !rq) { rq = document.createElement('span'); rq.className = 'text-danger js-bw'; rq.textContent = ' *'; lb.appendChild(rq); }
                if (rq) rq.hidden = !d.budgetWajib;
            }
        }
        hint(form);
    };
    document.addEventListener('change', function (e) {
        var sel = e.target.closest ? e.target.closest('.js-cetak-jenis') : null;
        if (sel) hint(sel.closest('form'));
    });

    /* ── Cabut titik & hapus bukti: konfirmasi ───────────────────────────────── */
    document.addEventListener('submit', function (e) {
        var f = e.target;
        if (f.matches && f.matches('form[data-tk-cabut]')) {
            var kode = f.dataset.kode, ter = f.dataset.terpasang === '1';
            var ok = confirm(ter
                ? 'Titik ' + kode + ' sudah ditandai terpasang. Cabut tetap? Bukti pasangnya (foto, tanggal, catatan) ikut terhapus dan pemesanannya di Media Promo dihapus.'
                : 'Cabut titik ' + kode + '? Pemesanannya di Media Promo akan dihapus.');
            if (!ok) { e.preventDefault(); return; }
        }
        var sub = e.submitter;
        if (sub && sub.dataset && sub.dataset.tkKonfirmasi && !confirm(sub.dataset.tkKonfirmasi)) { e.preventDefault(); return; }
        // cegah klik ganda: kunci tombol kirim sesaat setelah pengiriman dimulai
        if (f.closest && f.closest('.tk, .tk-modal')) {
            setTimeout(function () { $$('button[type=submit]', f).forEach(function (b) { b.disabled = true; }); }, 0);
        }
    });
    document.addEventListener('change', function (e) {
        var fi = e.target.closest ? e.target.closest('[data-tk-file]') : null;
        if (fi && fi.files && fi.files.length) {
            var f = fi.closest('form');
            if (f.requestSubmit) f.requestSubmit(); else f.submit();
        }
    });

    /* ── Modal "Pilih titik" ─────────────────────────────────────────────────── */
    var modalEl = document.getElementById('titikPickModal');
    if (!modalEl) return;
    var form = $('#titikPickForm', modalEl), list = $('#tpList', modalEl), info = $('#tpInfo', modalEl), q = $('#tpQ', modalEl),
        bebas = $('#tpBebas', modalEl), sum = $('#tpSum', modalEl), submit = $('#tpSubmit', modalEl), noMatch = $('#tpNoMatch', modalEl), err = $('#tpError', modalEl);
    var data = null;

    function el(tag, cls, txt) { var n = document.createElement(tag); if (cls) n.className = cls; if (txt != null) n.textContent = txt; return n; }
    function fmt(d) { var m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(d || ''); return m ? m[3] + '/' + m[2] + '/' + m[1] : d; }

    function hitung() {
        var n = $$('input[name="spot_ids[]"]:checked', list).length;
        sum.textContent = n > 0 ? n + ' titik dipilih' : 'Belum ada yang dipilih';
        submit.disabled = n === 0;
        $$('.tp-area', list).forEach(function (a) {
            var all = $('.tp-all', a), boxes = $$('input[name="spot_ids[]"]:not(:disabled)', a).filter(function (b) { return !b.closest('.tp-spot').hidden; });
            if (!all) return;
            all.disabled = boxes.length === 0;
            all.checked = boxes.length > 0 && boxes.every(function (b) { return b.checked; });
        });
    }
    function saring() {
        var term = q.value.trim().toLowerCase(), hanyaBebas = bebas.checked, tampil = 0;
        $$('.tp-spot', list).forEach(function (s) {
            var ok = (!term || s.dataset.q.indexOf(term) > -1) && (!hanyaBebas || s.dataset.state === 'bebas');
            s.hidden = !ok; if (ok) tampil++;
        });
        $$('.tp-area', list).forEach(function (a) {
            var any = $$('.tp-spot', a).some(function (s) { return !s.hidden; });
            a.hidden = !any;
            var c = $('.tp-ac', a); if (c) c.textContent = $$('.tp-spot', a).filter(function (s) { return !s.hidden; }).length + ' titik';
        });
        noMatch.hidden = tampil > 0 || !data || !data.spots.length;
        hitung();
    }

    function render(d) {
        list.innerHTML = '';
        info.innerHTML = '';
        var spots = d.spots || [], nBebas = spots.filter(function (s) { return s.state === 'bebas'; }).length, nBentrok = spots.filter(function (s) { return s.state === 'bentrok'; }).length;
        var p = el('div', 'tp-meta');
        p.appendChild(el('span', 'tp-chip', d.tipeLabel));
        if (d.periodeOk) p.appendChild(el('span', 'tp-chip', (d.periodeNama || 'Periode pasang') + ' ' + fmt(d.mulai) + ' – ' + fmt(d.selesai)));
        if (d.ukuranItem) p.appendChild(el('span', 'tp-chip', 'Ukuran item ' + d.ukuranItem));
        p.appendChild(el('span', 'tp-chip tp-chip-ok', nBebas + ' tersedia'));
        if (nBentrok) p.appendChild(el('span', 'tp-chip tp-chip-bad', nBentrok + (d.tipe === 'digital' ? ' layar penuh' : ' dipakai pemesanan lain')));
        info.appendChild(p);
        if (d.catatanSlot) info.appendChild(el('div', 'tp-slotnote', d.catatanSlot));
        if (!spots.length) { list.appendChild(el('div', 'tp-empty', 'Belum ada titik ' + d.tipeLabel + ' di master Media Promo. Tambahkan dulu di Media Promo > Master Titik.')); hitung(); return; }

        var per = {}, urut = [];
        spots.forEach(function (s) { var a = s.area || 'Tanpa area'; if (!per[a]) { per[a] = []; urut.push(a); } per[a].push(s); });
        urut.forEach(function (a) {
            var box = el('section', 'tp-area');
            var h = el('div', 'tp-ah');
            var lab = el('label', 'tp-allw');
            var all = el('input', 'tp-all'); all.type = 'checkbox'; all.setAttribute('aria-label', 'Pilih semua titik tersedia di ' + a);
            lab.appendChild(all); lab.appendChild(el('span', 'tp-an', a)); lab.appendChild(el('span', 'tp-ac', per[a].length + ' titik'));
            lab.appendChild(el('span', 'tp-al', 'pilih semua'));
            h.appendChild(lab); box.appendChild(h);
            var grid = el('div', 'tp-grid');
            per[a].forEach(function (s) {
                var l = el('label', 'tp-spot is-' + s.state);
                l.dataset.state = s.state;
                l.dataset.q = (s.kode + ' ' + s.nama + ' ' + (s.area || '') + ' ' + (s.ukuran || '')).toLowerCase();
                var i = el('input'); i.type = 'checkbox';
                if (s.state === 'bebas') { i.name = 'spot_ids[]'; i.value = s.id; }
                else { i.disabled = true; i.checked = s.state === 'terpilih'; }
                l.appendChild(i);
                var t = el('span', 'tp-st');
                t.appendChild(el('b', null, s.kode));
                t.appendChild(el('em', null, s.nama + (s.ukuran ? ' · ' + s.ukuran : '')));
                l.appendChild(t);
                if (s.slotInfo && s.state !== 'bentrok') t.appendChild(el('span', 'tp-slot', s.slotInfo));
                if (s.state === 'terpilih') l.appendChild(el('span', 'tp-flag', 'terpilih'));
                if (s.state === 'bentrok') { l.appendChild(el('span', 'tp-flag tp-flag-bad', 'dipakai')); l.title = 'Dipakai: ' + s.pemakai; l.appendChild(el('span', 'tp-who', s.pemakai)); }
                if (s.ukuranBeda && s.state === 'bebas') { var w = el('i', 'bi bi-exclamation-triangle tp-warn'); w.title = 'Ukuran titik (' + s.ukuran + ') berbeda dengan ukuran item'; l.appendChild(w); }
                grid.appendChild(l);
            });
            box.appendChild(grid);
            list.appendChild(box);
            all.addEventListener('change', function () {
                $$('input[name="spot_ids[]"]:not(:disabled)', box).forEach(function (b) { if (!b.closest('.tp-spot').hidden) b.checked = all.checked; });
                hitung();
            });
        });
        saring();
    }

    document.addEventListener('click', function (e) {
        var b = e.target.closest('[data-tp-open]');
        if (!b) return;
        e.preventDefault();
        form.action = b.dataset.action;
        $('#titikPickNama', modalEl).textContent = (b.dataset.nama || '') + (b.dataset.jenis && b.dataset.jenis.trim() ? ' · ' + b.dataset.jenis.trim() : '');
        q.value = ''; bebas.checked = false; err.hidden = true; data = null;
        list.innerHTML = '<div class="tp-empty">Memuat daftar titik…</div>'; info.innerHTML = '';
        submit.disabled = true; sum.textContent = 'Belum ada yang dipilih'; noMatch.hidden = true;
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
        fetch(b.dataset.daftar, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (!d.ok) { list.innerHTML = ''; err.hidden = false; err.textContent = d.message || 'Gagal memuat daftar titik.'; return; }
                data = d; render(d);
            })
            .catch(function () { list.innerHTML = ''; err.hidden = false; err.textContent = 'Gagal memuat daftar titik. Muat ulang halaman lalu coba lagi.'; });
    });
    q.addEventListener('input', saring);
    bebas.addEventListener('change', saring);
    form.addEventListener('change', hitung);
    form.addEventListener('submit', function () { submit.disabled = true; });
    modalEl.addEventListener('shown.bs.modal', function () { q.focus(); });
})();
