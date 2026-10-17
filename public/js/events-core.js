/* MIC — Events inti: daftar (cari/filter/urut/bandingkan), form, galeri, master lokasi. */
(function () {
    'use strict';
    function $(s, c) { return (c || document).querySelector(s); }
    function $$(s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); }
    var BLN = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

    /* ══════════ Daftar event ══════════ */
    (function () {
        var root = document.getElementById('evIdx');
        if (!root) return;
        var list = $('#evList', root);
        if (!list) return;
        var cards = $$('.ev-card', list);
        var q = $('#evQ'), fStatus = $('#evFStatus'), fAppr = $('#evFAppr'), fYear = $('#evFYear'),
            fMonth = $('#evFMonth'), fMall = $('#evFMall'), sortSel = $('#evSort'),
            countEl = $('#evCount'), noMatch = $('#evNoMatch'), resetBtn = $('#evReset');
        var KEY = 'evIdxFilter';

        function filters() { return [fStatus, fAppr, fYear, fMonth, fMall].filter(Boolean); }

        function save() {
            try {
                sessionStorage.setItem(KEY, JSON.stringify({
                    q: q.value, st: fStatus.value, ap: fAppr ? fAppr.value : '', y: fYear.value,
                    m: fMonth.value, ml: fMall.value, s: sortSel.value
                }));
            } catch (e) { /* abaikan */ }
        }
        function restore() {
            try {
                var d = JSON.parse(sessionStorage.getItem(KEY) || 'null');
                if (!d) return;
                q.value = d.q || ''; fStatus.value = d.st || ''; if (fAppr) fAppr.value = d.ap || '';
                fYear.value = d.y || ''; fMonth.value = d.m || ''; fMall.value = d.ml || ''; sortSel.value = d.s || 'baru';
            } catch (e) { /* abaikan */ }
        }

        function monthHeaders() {
            $$('.ev-month', list).forEach(function (h) { h.remove(); });
            var mode = sortSel.value;
            if (mode !== 'baru' && mode !== 'lama') return;
            var last = null, head = null, n = 0;
            $$('.ev-card', list).forEach(function (c) {
                if (c.hidden) return;
                var ym = c.dataset.ym || '';
                if (ym !== last) {
                    if (head) head.querySelector('.ev-month-n').textContent = n + ' event';
                    head = document.createElement('div');
                    head.className = 'ev-month';
                    var label = ym ? BLN[parseInt(ym.split('-')[1], 10)] + ' ' + ym.split('-')[0] : 'Tanpa tanggal';
                    head.innerHTML = '<span class="ev-month-t"></span><span class="ev-month-n"></span>';
                    head.firstChild.textContent = label;
                    list.insertBefore(head, c);
                    last = ym; n = 0;
                }
                n++;
            });
            if (head) head.querySelector('.ev-month-n').textContent = n + ' event';
        }

        function apply() {
            var term = q.value.trim().toLowerCase(), shown = 0;
            cards.forEach(function (c) {
                var d = c.dataset, ok = true;
                if (term && d.search.indexOf(term) < 0) ok = false;
                if (ok && fStatus.value && d.status !== fStatus.value) ok = false;
                if (ok && fAppr && fAppr.value && d.appr !== fAppr.value) ok = false;
                if (ok && fYear.value && d.year !== fYear.value) ok = false;
                if (ok && fMonth.value && d.month !== fMonth.value) ok = false;
                if (ok && fMall.value && d.mall !== fMall.value) ok = false;
                c.hidden = !ok;
                if (ok) shown++;
            });
            var on = !!(term || filters().some(function (f) { return f.value; }));
            countEl.textContent = on ? shown + ' dari ' + cards.length + ' event' : cards.length + ' event';
            resetBtn.hidden = !on;
            noMatch.hidden = shown > 0;
            list.hidden = shown === 0;
            // sorot kartu KPI yang sesuai
            $$('.ev-kpi', root).forEach(function (k) {
                var v = (k.getAttribute('data-ev-quick') || '').split(':');
                k.classList.toggle('is-on', v[0] === 'status' && v[1] === fStatus.value && !term &&
                    !fYear.value && !fMonth.value && !fMall.value && !(fAppr && fAppr.value));
            });
            monthHeaders();
            save();
        }

        function sortCards() {
            var mode = sortSel.value;
            var arr = cards.slice().sort(function (a, b) {
                var x = a.dataset, y = b.dataset;
                if (mode === 'nama') return x.name.toLowerCase().localeCompare(y.name.toLowerCase(), 'id');
                if (mode === 'prog') return (+x.prog) - (+y.prog) || (+y.start) - (+x.start);
                if (mode === 'lama') return (+x.start) - (+y.start);
                return (+y.start) - (+x.start);
            });
            arr.forEach(function (c) { list.appendChild(c); });
            apply();
        }

        function reset() {
            q.value = '';
            filters().forEach(function (f) { f.value = ''; });
            apply();
        }

        var t;
        q.addEventListener('input', function () { clearTimeout(t); t = setTimeout(apply, 120); });
        filters().forEach(function (el) { el.addEventListener('change', apply); });
        sortSel.addEventListener('change', sortCards);
        resetBtn.addEventListener('click', reset);
        $('#evReset2').addEventListener('click', reset);

        // KPI & banner = filter cepat
        $$('[data-ev-quick]', root).forEach(function (b) {
            b.addEventListener('click', function () {
                var v = b.getAttribute('data-ev-quick').split(':');
                var wasOn = b.classList.contains('is-on') && v[0] === 'status' && v[1] !== '';
                reset();
                if (v[0] === 'status' && !wasOn) fStatus.value = v[1];
                if (v[0] === 'appr' && fAppr) fAppr.value = v[1];
                apply();
                list.scrollIntoView({ block: 'start', behavior: 'smooth' });
            });
        });

        // Kartu bisa dibuka lewat klik (kecuali tombol/tautan/menu/centang)
        list.addEventListener('click', function (e) {
            var card = e.target.closest('.ev-card');
            if (!card) return;
            if (e.target.closest('a, button, input, label, form, .dropdown, [data-ev-notoggle]')) return;
            if (window.getSelection && String(window.getSelection()).length > 0) return;
            window.location.href = card.dataset.href;
        });

        // ── Bandingkan ──
        var bar = $('#compareBar'), cnt = $('#compareCount'), go = $('#goCompare');
        var checks = $$('.evt-check', list);
        function updateCompare() {
            var sel = checks.filter(function (c) { return c.checked; });
            cnt.textContent = sel.length;
            if (sel.length >= 2) {
                go.href = window.EV_COMPARE_URL + '?' + sel.map(function (c) { return 'ids[]=' + c.value; }).join('&');
                bar.hidden = false;
                requestAnimationFrame(function () { bar.classList.add('is-show'); });
            } else {
                bar.classList.remove('is-show');
                setTimeout(function () { if (!bar.classList.contains('is-show')) bar.hidden = true; }, 300);
            }
        }
        checks.forEach(function (c) {
            c.addEventListener('change', function () {
                if (checks.filter(function (x) { return x.checked; }).length > 3) { c.checked = false; return; }
                updateCompare();
            });
        });
        $('#cmpClear').addEventListener('click', function () { checks.forEach(function (c) { c.checked = false; }); updateCompare(); });

        restore();
        sortCards();
    })();

    /* ══════════ Form buat / ubah ══════════ */
    (function () {
        var form = $('#evForm');
        if (!form) return;
        var s = $('#start_date'), e = $('#end_date'), info = $('#days_info');

        function days() {
            if (!s.value || !e.value) { info.textContent = 'Isi tanggal mulai dan selesai'; info.className = 'ev-days'; return; }
            var diff = Math.round((new Date(e.value) - new Date(s.value)) / 86400000) + 1;
            if (diff < 1) {
                info.innerHTML = '<i class="bi bi-exclamation-circle"></i>Tanggal selesai harus sama atau setelah tanggal mulai';
                info.className = 'ev-days is-bad';
                e.setCustomValidity('Tanggal selesai harus setelah tanggal mulai');
            } else {
                info.innerHTML = '<i class="bi bi-calendar-check"></i>' + diff + ' hari';
                info.className = 'ev-days is-ok';
                e.setCustomValidity('');
            }
            e.min = s.value;
        }
        s.addEventListener('change', function () {
            if (e.value && e.value < s.value) e.value = s.value;
            days();
        });
        e.addEventListener('change', days);
        days();

        // Lokasi menyesuaikan mall
        var mall = $('[name="mall"]', form);
        function filterLokasi() {
            var m = mall.value, any = 0;
            $$('.lokasi-item', form).forEach(function (el) {
                var show = m === 'keduanya' || el.dataset.mall === m;
                el.style.display = show ? '' : 'none';
                if (!show) el.querySelector('input').checked = false; else any++;
            });
            var none = $('#lokasi_none');
            if (none) none.hidden = any > 0;
        }
        mall.addEventListener('change', filterLokasi);
        filterLokasi();

        // Validasi jelas: tandai kolom yang belum benar, fokus ke yang pertama
        form.addEventListener('submit', function (ev) {
            if (!form.checkValidity()) {
                ev.preventDefault();
                form.classList.add('was-validated');
                var bad = $(':invalid', form);
                if (bad) { bad.scrollIntoView({ block: 'center', behavior: 'smooth' }); bad.focus({ preventScroll: true }); }
                var sum = $('#evFormErr');
                if (sum) sum.hidden = false;
            }
        });
        var first = $('.is-invalid', form);
        if (first) first.scrollIntoView({ block: 'center' });
    })();

    /* ══════════ Galeri ══════════ */
    (function () {
        var grid = $('#galleryGrid');
        if (!grid || !window.EV_PHOTOS) return;
        var allPhotos = window.EV_PHOTOS, visible = allPhotos.map(function (_, i) { return i; }), pos = 0;
        var el = $('#lightboxModal'), modal = new bootstrap.Modal(el);
        var img = $('#lbImg'), cap = $('#lbCaption'), down = $('#lbDownload'), counter = $('#lbCounter');
        function show() {
            var p = allPhotos[visible[pos]]; if (!p) return;
            img.src = p.src; img.alt = p.caption; cap.textContent = p.caption; down.href = p.src;
            counter.textContent = (pos + 1) + ' / ' + visible.length;
        }
        function nav(d) { if (!visible.length) return; pos = (pos + d + visible.length) % visible.length; show(); }
        function open(idx) { var p = visible.indexOf(idx); pos = p >= 0 ? p : 0; show(); modal.show(); }
        grid.addEventListener('click', function (e) { var c = e.target.closest('.gallery-card'); if (c) open(parseInt(c.dataset.photoidx, 10)); });
        grid.addEventListener('keydown', function (e) {
            if (e.key !== 'Enter' && e.key !== ' ') return;
            var c = e.target.closest('.gallery-card'); if (c) { e.preventDefault(); open(parseInt(c.dataset.photoidx, 10)); }
        });
        $('#lbPrev').addEventListener('click', function () { nav(-1); });
        $('#lbNext').addEventListener('click', function () { nav(1); });
        document.addEventListener('keydown', function (e) {
            if (!el.classList.contains('show')) return;
            if (e.key === 'ArrowLeft') nav(-1);
            if (e.key === 'ArrowRight') nav(1);
        });
        $$('#galleryTabs .ev-gtab').forEach(function (tab) {
            tab.addEventListener('click', function () {
                $$('#galleryTabs .ev-gtab').forEach(function (t) { t.classList.remove('is-on'); t.setAttribute('aria-pressed', 'false'); });
                tab.classList.add('is-on'); tab.setAttribute('aria-pressed', 'true');
                var f = tab.dataset.tab;
                $$('.gallery-item').forEach(function (it) { it.hidden = !(f === 'all' || it.dataset.module === f); });
                visible = f === 'all' ? allPhotos.map(function (_, i) { return i; })
                    : allPhotos.reduce(function (a, p, i) { if (p.module === f) a.push(i); return a; }, []);
                pos = 0;
            });
        });
        // foto yang gagal dimuat: tampilkan penanda, bukan ikon rusak
        $$('.ev-gimg img', grid).forEach(function (im) {
            im.addEventListener('error', function () { im.parentElement.innerHTML = '<i class="bi bi-image-slash"></i>'; });
            if (im.complete && im.naturalWidth === 0) im.dispatchEvent(new Event('error'));
        });
    })();

    /* ══════════ Master lokasi ══════════ */
    (function () {
        var root = $('#evLoc');
        if (!root) return;
        $$('.edit-btn', root).forEach(function (btn) {
            btn.addEventListener('click', function () {
                $('#editForm').action = root.dataset.editBase + this.dataset.id + '/edit';
                $('#eMall').value = this.dataset.mall;
                $('#eNama').value = this.dataset.nama;
                $('#eAktif').checked = this.dataset.aktif === '1';
                bootstrap.Modal.getOrCreateInstance($('#editModal')).show();
            });
        });
        var add = $('#addModal');
        if (add) add.addEventListener('shown.bs.modal', function () { var i = $('input[name="nama"]', add); if (i) i.focus(); });
    })();
})();
