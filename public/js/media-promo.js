/* MIC — Media Promo: filter/cari/urut sisi klien, modal, gantt, aksi massal.
   Satu berkas untuk semua halaman modul; modul dipilih lewat data-mp pada #mpRoot. */
(function () {
    'use strict';
    var root = document.getElementById('mpRoot');
    if (!root) return;
    var PAGE = root.getAttribute('data-mp');
    var BASE = root.getAttribute('data-base') || '/';

    function $(s, c) { return (c || root).querySelector(s); }
    function $$(s, c) { return Array.prototype.slice.call((c || root).querySelectorAll(s)); }
    function tglID(d) { var m = /^(\d{4})-(\d{2})-(\d{2})/.exec(d || ''); if (!m) return d || ''; return parseInt(m[3], 10) + ' ' + ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'][parseInt(m[2], 10) - 1] + ' ' + m[1]; }
    function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
    function modal(id) { return bootstrap.Modal.getOrCreateInstance(document.getElementById(id)); }
    function parse(a) { try { return JSON.parse(a); } catch (e) { return null; } }
    var TIPE = { t_banner: 'T-Banner', hanging: 'Hanging Banner', sticker_lift: 'Sticker Lift', totem_stainless: 'Totem Stainless', digital: 'Digital' };

    // Formulir dengan data-mp-confirm: tanya dulu sebelum kirim
    root.addEventListener('submit', function (e) {
        var msg = e.target.getAttribute && e.target.getAttribute('data-mp-confirm');
        if (msg && !window.confirm(msg)) e.preventDefault();
    });

    // Pembantu filter umum (#mpQ, #mpCount, #mpNoMatch, tombol reset)
    function wireTools(apply, controls) {
        var q = $('#mpQ'), t;
        if (q) q.addEventListener('input', function () { clearTimeout(t); t = setTimeout(apply, 120); });
        controls.forEach(function (el) { if (el) el.addEventListener('change', apply); });
        function reset() {
            if (q) q.value = '';
            controls.forEach(function (el) { if (el && el.tagName === 'SELECT') el.value = ''; });
            if (PAGE === 'my') setChip('');
            apply();
        }
        ['#mpReset', '#mpReset2'].forEach(function (s) { var b = $(s); if (b) b.addEventListener('click', reset); });
        return q;
    }
    function term() { var q = $('#mpQ'); return q ? q.value.trim().toLowerCase() : ''; }

    // Tabel yang bisa diurut lewat judul kolom
    function wireSortable() {
        $$('table[data-sortable]').forEach(function (tbl) {
            $$('th[data-sort]', tbl).forEach(function (th) {
                th.addEventListener('click', function () {
                    var col = +th.getAttribute('data-sort'), asc = !th.classList.contains('is-asc');
                    $$('th', tbl).forEach(function (h) { h.classList.remove('is-asc', 'is-desc'); });
                    th.classList.add(asc ? 'is-asc' : 'is-desc');
                    var tb = tbl.tBodies[0], rows = $$('tr:not(.mp-static)', tb);
                    rows.sort(function (a, b) {
                        var x = a.cells[col], y = b.cells[col];
                        var xv = x.getAttribute('data-v'), yv = y.getAttribute('data-v');
                        if (xv == null) xv = x.textContent.trim().toLowerCase();
                        if (yv == null) yv = y.textContent.trim().toLowerCase();
                        var xn = parseFloat(xv), yn = parseFloat(yv), r;
                        if (!isNaN(xn) && !isNaN(yn) && String(xn) === xv && String(yn) === yv) r = xn - yn;
                        else r = String(xv).localeCompare(String(yv), 'id', { numeric: true });
                        return asc ? r : -r;
                    });
                    rows.forEach(function (r) { tb.appendChild(r); });
                });
            });
        });
    }

    /* ═══════════════ KETERSEDIAAN (index) ═══════════════ */
    function pageIndex() {
        var cards = $$('.mp-spot'), fKind = $('#mpFKind'), fTipe = $('#mpFTipe'), fStatus = $('#mpFStatus'), sortSel = $('#mpSort');

        function apply() {
            var tm = term(), shown = 0;
            cards.forEach(function (c) {
                var d = c.dataset, ok = true;
                if (tm && d.search.indexOf(tm) < 0) ok = false;
                if (ok && fKind.value && d.kind !== fKind.value) ok = false;
                if (ok && fTipe.value && d.tipe !== fTipe.value) ok = false;
                if (ok && fStatus.value) {
                    if (fStatus.value === 'avail') ok = d.avail === '1';
                    else ok = d.status === fStatus.value;
                }
                c.hidden = !ok;
                if (ok) shown++;
            });
            ['cetak', 'digital'].forEach(function (k) {
                var g = $('[data-group="' + k + '"]'), list = g && $('[data-list]', g);
                if (!g) return;
                if (!list) return;
                var n = $$('.mp-spot', list).filter(function (c) { return !c.hidden; }).length;
                list.hidden = n === 0;
                g.hidden = n === 0;
                var lbl = $(k === 'cetak' ? '#mpNCetak' : '#mpNDigital');
                if (lbl) lbl.textContent = n + ' titik';
            });
            var f = tm || fKind.value || fTipe.value || fStatus.value;
            $('#mpCount').textContent = f ? shown + ' dari ' + cards.length + ' titik' : cards.length + ' titik';
            $('#mpNoMatch').hidden = shown > 0;
        }
        function sortCards() {
            var mode = sortSel.value;
            $$('[data-list]').forEach(function (list) {
                var arr = $$('.mp-spot', list);
                arr.sort(function (a, b) {
                    var x = a.dataset, y = b.dataset;
                    if (mode === 'nama') return x.nama.localeCompare(y.nama, 'id');
                    if (mode === 'end') {
                        var xe = x.end || '9999-99-99', ye = y.end || '9999-99-99';
                        return xe < ye ? -1 : (xe > ye ? 1 : (+x.order) - (+y.order));
                    }
                    return (+x.order) - (+y.order);
                });
                arr.forEach(function (c) { list.appendChild(c); });
            });
        }
        wireTools(apply, [fKind, fTipe, fStatus]);
        sortSel.addEventListener('change', sortCards);
        apply();

        // Edit titik
        root.addEventListener('click', function (e) {
            var b = e.target.closest('[data-mp-edit]');
            if (b) {
                var s = parse(b.getAttribute('data-mp-edit')); if (!s) return;
                $('#edit_spot_kode').value = s.kode; $('#edit_spot_nama').value = s.nama;
                $('#edit_spot_tipe_label').value = TIPE[s.tipe] || s.tipe;
                $('#edit_spot_area').value = s.area || ''; $('#edit_spot_ukuran').value = s.ukuran || '';
                $('#edit_spot_catatan').value = s.catatan || '';
                var row = $('#editSpotSlotRow');
                row.style.display = s.tipe === 'digital' ? '' : 'none';
                if (s.tipe === 'digital') $('#edit_spot_slots').value = s.total_slots;
                $('#formEditTitik').action = BASE + 'creative/media-promo/spots/' + s.id + '/update';
                modal('modalEditTitik').show();
                return;
            }
            var bk = e.target.closest('[data-mp-book]');
            if (bk) {
                var mode = bk.getAttribute('data-mp-book'), id = bk.getAttribute('data-spot');
                $(mode === 'cetak' ? '#modeCetak' : '#modeDigital').checked = true;
                switchReqMode(mode);
                if (mode === 'cetak') {
                    $$('.cetak-check').forEach(function (c) { c.checked = c.value === id; });
                } else {
                    $$('.digital-spot-row').forEach(function (r) { r.classList.toggle('mp-hl', r.dataset.spotId === id); });
                }
                var mEl = document.getElementById('modalBuatRequest');
                mEl.addEventListener('shown.bs.modal', function once() {
                    mEl.removeEventListener('shown.bs.modal', once);
                    var rowEl = $('.digital-spot-row[data-spot-id="' + id + '"]');
                    if (mode === 'digital' && rowEl) rowEl.parentNode.scrollTop = rowEl.offsetTop - rowEl.parentNode.offsetTop;
                });
                modal('modalBuatRequest').show();
            }
        });

        // ── Modal Buat Request ──
        var reqMode = $('#reqMode');
        function switchReqMode(mode) {
            reqMode.value = mode;
            $('#cetakSection').style.display = mode === 'cetak' ? '' : 'none';
            $('#digitalSection').style.display = mode === 'digital' ? '' : 'none';
            if (mode === 'digital') checkDigitalDates(); else checkCetakDates();
        }
        $$('input[name="req_mode_ui"]').forEach(function (r) { r.addEventListener('change', function () { switchReqMode(r.value); }); });

        function dates() { return [$('#reqTglMulai').value, $('#reqTglSelesai').value]; }
        function checkDigitalDates() {
            var d = dates(); if (!d[0] || !d[1] || d[0] > d[1]) return;
            fetch(BASE + 'creative/media-promo/spots/check-digital?tgl_mulai=' + d[0] + '&tgl_selesai=' + d[1])
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    $$('.digital-spot-row').forEach(function (row) {
                        var info = data[row.dataset.spotId]; if (!info) return;
                        var avail = new Set(info.available), lab = $('.spot-sisa-label', row);
                        if (lab) lab.textContent = info.available.length + '/' + info.total + ' bebas';
                        $$('.digital-slot-check', row).forEach(function (cb) {
                            var ok = avail.has(parseInt(cb.value, 10));
                            cb.disabled = !ok; if (!ok) cb.checked = false;
                            var lbl = cb.nextElementSibling;
                            if (lbl) {
                                lbl.classList.remove('btn-outline-success', 'btn-outline-danger', 'btn-outline-secondary', 'disabled');
                                lbl.classList.add(ok ? 'btn-outline-success' : 'btn-outline-danger');
                                if (!ok) lbl.classList.add('disabled');
                            }
                        });
                    });
                });
        }
        function checkCetakDates() {
            var d = dates(); if (!d[0] || !d[1] || d[0] > d[1]) return;
            fetch(BASE + 'creative/media-promo/spots/check-cetak?tgl_mulai=' + d[0] + '&tgl_selesai=' + d[1])
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    var occ = new Set(data.occupied.map(String));
                    $$('.cetak-check').forEach(function (chk) {
                        var o = occ.has(String(chk.value)), badge = chk.closest('.form-check').querySelector('.avail-badge');
                        chk.disabled = o;
                        if (o) { chk.checked = false; if (badge) { badge.className = 'badge bg-danger ms-auto avail-badge'; badge.textContent = 'Terpakai'; } }
                        else if (badge) { badge.className = 'badge bg-success ms-auto avail-badge'; badge.textContent = 'Tersedia'; }
                    });
                });
        }
        ['reqTglMulai', 'reqTglSelesai'].forEach(function (id) {
            document.getElementById(id).addEventListener('change', function () {
                if (reqMode.value === 'cetak') checkCetakDates(); else checkDigitalDates();
            });
        });
        $('#formBuatRequest').addEventListener('submit', function (e) {
            var cetak = reqMode.value === 'cetak';
            var none = cetak ? !$$('.cetak-check:checked').length : !$$('.digital-slot-check:checked').length;
            var msg = cetak ? $('#cetakNoCheck') : $('#digitalNoSlot');
            if (none) { e.preventDefault(); msg.style.display = ''; return; }
            msg.style.display = 'none';
        });
    }

    /* ═══════════════ MASTER TITIK ═══════════════ */
    function pageMaster() {
        var fTipe = $('#mpFTipe'), fStatus = $('#mpFStatus');
        function apply() {
            var tm = term(), shown = 0, total = 0;
            $$('[data-group]').forEach(function (g) {
                var n = 0, all = 0;
                $$('tbody tr:not(.mp-static)', g).forEach(function (r) {
                    all++;
                    var ok = true;
                    if (tm && r.dataset.search.indexOf(tm) < 0) ok = false;
                    if (ok && fTipe.value && r.dataset.tipe !== fTipe.value) ok = false;
                    if (ok && fStatus.value !== '' && r.dataset.active !== fStatus.value) ok = false;
                    r.hidden = !ok;
                    if (ok) n++;
                });
                total += all; shown += n;
                var c = $('[data-gcount]', g); if (c) c.textContent = n + ' titik';
                g.hidden = all > 0 && n === 0;
            });
            var f = tm || fTipe.value || fStatus.value !== '';
            $('#mpCount').textContent = f ? shown + ' dari ' + total + ' titik' : total + ' titik';
            $('#mpNoMatch').hidden = shown > 0 || total === 0;
        }
        wireTools(apply, [fTipe, fStatus]);
        wireSortable();
        apply();

        $$('[data-mp-slot-toggle]').forEach(function (sel) {
            sel.addEventListener('change', function () {
                var rowId = sel.getAttribute('data-mp-slot-toggle');
                document.getElementById(rowId).style.display = sel.value === 'digital' ? '' : 'none';
                var inp = document.querySelector('#' + rowId + ' input');
                if (inp) inp.required = sel.value === 'digital';
            });
        });
        root.addEventListener('click', function (e) {
            var b = e.target.closest('[data-mp-edit]'); if (!b) return;
            var s = parse(b.getAttribute('data-mp-edit')); if (!s) return;
            $('#edit_kode').value = s.kode; $('#edit_nama').value = s.nama;
            $('#edit_tipe_label').value = TIPE[s.tipe] || s.tipe;
            $('#edit_area').value = s.area || ''; $('#edit_ukuran').value = s.ukuran || ''; $('#edit_catatan').value = s.catatan || '';
            var row = $('#editSlotRow');
            if (s.tipe === 'digital') { row.style.display = ''; $('#edit_total_slots').value = s.total_slots; }
            else row.style.display = 'none';
            $('#formEditTitik').action = BASE + 'creative/media-promo/spots/' + s.id + '/update';
            modal('modalEditTitik').show();
        });
    }

    /* ═══════════════ PENDING ═══════════════ */
    function pagePending() {
        var batches = $$('.mp-batch');

        // Pilih semua / hitung terpilih per batch
        function sync(batch) {
            var items = $$('.item-check[data-batch="' + batch + '"]'), on = items.filter(function (c) { return c.checked; });
            var all = $('.batch-check-all[data-batch="' + batch + '"]');
            if (all) all.checked = items.length === on.length;
            var form = items[0] && items[0].closest('form'), lab = form && $('[data-selcount]', form);
            if (lab) lab.textContent = on.length + ' dari ' + items.length + ' dipilih';
        }
        $$('.batch-check-all').forEach(function (a) {
            a.addEventListener('change', function () {
                $$('.item-check[data-batch="' + a.dataset.batch + '"]').forEach(function (c) { c.checked = a.checked; });
                sync(a.dataset.batch);
            });
        });
        $$('.item-check').forEach(function (c) { c.addEventListener('change', function () { sync(c.dataset.batch); }); });
        $$('.batch-check-all').forEach(function (a) { sync(a.dataset.batch); });
        $$('.batch-form').forEach(function (f) {
            f.addEventListener('submit', function (e) {
                if (!$$('.item-check:checked', f).length) { e.preventDefault(); alert('Pilih minimal 1 item untuk disetujui.'); }
            });
        });

        // Filter / urut
        var fSumber = $('#mpFSumber'), fUrgent = $('#mpFUrgent'), sortSel = $('#mpSort');
        function apply() {
            if (!fSumber) return;
            var tm = term(), shown = 0;
            batches.forEach(function (b) {
                var d = b.dataset, ok = true;
                if (tm && d.search.indexOf(tm) < 0) ok = false;
                if (ok && fSumber.value && d.sumber !== fSumber.value) ok = false;
                if (ok && fUrgent.value && d.urgent !== '1') ok = false;
                b.hidden = !ok; if (ok) shown++;
            });
            var f = tm || fSumber.value || fUrgent.value;
            $('#mpCount').textContent = f ? shown + ' dari ' + batches.length + ' pengajuan' : batches.length + ' pengajuan';
            $('#mpNoMatch').hidden = shown > 0;
        }
        function sortB() {
            var list = $('#mpList'), mode = sortSel.value, arr = batches.slice();
            arr.sort(function (a, b) {
                var x = a.dataset, y = b.dataset;
                if (mode === 'mulai') return x.mulai < y.mulai ? -1 : (x.mulai > y.mulai ? 1 : 0);
                if (mode === 'nama') return x.nama.localeCompare(y.nama, 'id');
                return (+x.submitted || 9e15) - (+y.submitted || 9e15);   // terlama dulu
            });
            arr.forEach(function (b) { list.appendChild(b); });
        }
        if (fSumber) { wireTools(apply, [fSumber, fUrgent]); sortSel.addEventListener('change', sortB); apply(); }

        // Modal: tolak & detail
        root.addEventListener('click', function (e) {
            var r = e.target.closest('[data-mp-reject]');
            if (r) {
                var ids = parse(r.getAttribute('data-mp-reject')) || [];
                $('#rejectBatchIds').innerHTML = ids.map(function (id) { return '<input type="hidden" name="ids[]" value="' + esc(id) + '">'; }).join('');
                $('#formRejectBatch').action = BASE + 'creative/media-promo/usage/reject-batch';
                $('#modalRejectBatch textarea').value = '';
                modal('modalRejectBatch').show();
                return;
            }
            var d = e.target.closest('[data-mp-detail]');
            if (d) {
                var u = parse(d.getAttribute('data-mp-detail')); if (!u) return;
                var sl = { internal: 'Internal Manajemen', tenant: 'Tenant Mall', external: 'External Client' };
                $('#detailNamaMateri').textContent = u.nama_materi;
                $('#detailDeskripsi').textContent = u.deskripsi_materi || '—';
                $('#detailDept').textContent = u.dept + (u.requested_by ? ' · ' + u.requested_by : '');
                $('#detailPeriode').textContent = tglID(u.tanggal_mulai) + ' s/d ' + tglID(u.tanggal_selesai);
                $('#detailSumber').textContent = sl[u.sumber] || u.sumber;
                $('#detailBerbayar').textContent = u.is_berbayar == 1 ? 'Ya' : 'Tidak';
                $('#detailCatatan').textContent = u.catatan_pemohon || '—';
                $('#detailSubmittedBy').textContent = u.submitted_by_name || '—';
                $('#detailSubmittedAt').textContent = u.submitted_at || '—';
                $('#detailJumlahItem').textContent = d.getAttribute('data-count') + ' titik / slot';
                modal('modalDetail').show();
            }
        });
    }

    /* ═══════════════ REQUEST SAYA ═══════════════ */
    var statusFilter = '';
    function setChip(v) {
        statusFilter = v;
        $$('#mpStatusChips .mp-fchip').forEach(function (b) { b.classList.toggle('is-on', b.dataset.st === v); });
    }
    function pageMy() {
        var cards = $$('.mp-req'), sortSel = $('#mpSort'), chk = $$('.row-check');
        if (!cards.length) return;

        function apply() {
            var tm = term(), shown = 0;
            cards.forEach(function (c) {
                var ok = true;
                if (tm && c.dataset.search.indexOf(tm) < 0) ok = false;
                if (ok && statusFilter && c.dataset.status !== statusFilter) ok = false;
                c.hidden = !ok; if (ok) shown++;
            });
            var f = tm || statusFilter;
            $('#mpCount').textContent = f ? shown + ' dari ' + cards.length + ' request' : cards.length + ' request';
            $('#mpNoMatch').hidden = shown > 0;
        }
        function sortC() {
            var mode = sortSel.value, list = $('#mpList'), arr = cards.slice();
            arr.sort(function (a, b) {
                var x = a.dataset, y = b.dataset;
                if (mode === 'mulai') return x.mulai < y.mulai ? -1 : (x.mulai > y.mulai ? 1 : 0);
                if (mode === 'end') return x.end < y.end ? -1 : (x.end > y.end ? 1 : 0);
                if (mode === 'nama') return x.nama.localeCompare(y.nama, 'id');
                return (+x.order) - (+y.order);
            });
            arr.forEach(function (c) { list.appendChild(c); });
        }
        wireTools(apply, []);
        sortSel.addEventListener('change', sortC);
        $$('#mpStatusChips .mp-fchip').forEach(function (b) { b.addEventListener('click', function () { setChip(b.dataset.st); apply(); }); });
        $('#mpReset').addEventListener('click', function () { sortSel.value = ''; sortC(); });
        apply();

        // Buka/tutup rincian baris
        root.addEventListener('click', function (e) {
            var head = e.target.closest('[data-mp-toggle]');
            if (!head || e.target.closest('[data-mp-stop], a, button, input, form')) return;
            var card = head.closest('.mp-req'), body = $('.mp-req-body', card);
            body.hidden = !body.hidden; card.classList.toggle('is-open', !body.hidden);
        });

        // Bar aksi massal
        var bar = $('#bulkBar');
        function updateBulk() {
            var on = chk.filter(function (c) { return c.checked; });
            bar.hidden = on.length === 0;
            $('#bulkCount').textContent = on.length + ' request dipilih';
        }
        chk.forEach(function (c) { c.addEventListener('change', updateBulk); });
        $('#bulkClear').addEventListener('click', function () { chk.forEach(function (c) { c.checked = false; }); updateBulk(); });

        // Edit request
        root.addEventListener('click', function (e) {
            var b = e.target.closest('[data-mp-edit-req]'); if (!b) return;
            var u = parse(b.getAttribute('data-mp-edit-req')); if (!u) return;
            $('#editSpotLabel').value = '[' + u.spot_kode + '] ' + u.spot_nama;
            $('#editSpotId').value = u.spot_id;
            var dept = $('#editDept'); dept.value = u.dept;
            if (!dept.value) { var o = document.createElement('option'); o.value = u.dept; o.textContent = u.dept; o.selected = true; dept.appendChild(o); }
            $('#editRequestedBy').value = u.requested_by || '';
            $('#editTglMulai').value = u.tanggal_mulai; $('#editTglSelesai').value = u.tanggal_selesai;
            $('#editNamaMateri').value = u.nama_materi; $('#editDeskripsi').value = u.deskripsi_materi || '';
            $('#editCatatan').value = u.catatan_pemohon || ''; $('#editSumber').value = u.sumber || 'internal';
            $('#editIsBerbayar').checked = u.is_berbayar == 1;
            var slot = $('#editSlotRow');
            if (u.spot_tipe === 'digital') { slot.style.display = ''; $('#editSlotNumber').value = u.slot_number || ''; }
            else slot.style.display = 'none';
            $('#formEditRequest').action = BASE + 'creative/media-promo/usage/' + u.id + '/update';
            modal('modalEditRequest').show();
        });
    }

    /* ═══════════════ GANTT ═══════════════ */
    function pageGantt() {
        var wrap = $('#ganttWrap'), usages = parse(($('#mpUsages') || {}).textContent) || {};
        var canApprove = root.getAttribute('data-can-approve') === '1';
        var csrfName = root.getAttribute('data-csrf-name'), csrfHash = root.getAttribute('data-csrf-hash');
        var STAT = { pending: ['Pending', 'warn'], approved: ['Approved', 'ok'], done: ['Selesai', 'mute'], rejected: ['Ditolak', 'bad'], draft: ['Draft', 'blue'] };
        var SUMBER = { internal: 'Internal Manajemen', tenant: 'Tenant Mall', external: 'External Client' };

        var form = $('#mpTools');
        if (form) form.addEventListener('submit', function (e) { if (document.activeElement && document.activeElement.id === 'mpQ') e.preventDefault(); });

        function apply() {
            var rows = $$('tbody tr[data-spot]'), tm = term(), spotsShown = {}, spots = {}, booked = {};
            var only = $('#mpOnlyBooked') && $('#mpOnlyBooked').classList.contains('is-on');
            rows.forEach(function (r) { if (r.querySelector('.mp-gbar')) booked[r.dataset.spot] = true; });
            rows.forEach(function (r) {
                spots[r.dataset.spot] = true;
                if ((!tm || r.dataset.search.indexOf(tm) > -1) && (!only || booked[r.dataset.spot])) spotsShown[r.dataset.spot] = true;
            });
            rows.forEach(function (r) { r.hidden = !spotsShown[r.dataset.spot]; });
            var tot = Object.keys(spots).length, sh = Object.keys(spotsShown).length;
            var c = $('#mpCount'); if (c) c.textContent = (tm || only) ? sh + ' dari ' + tot + ' titik' : tot + ' titik';
        }
        if (wrap) {
            var q = $('#mpQ'), t; q.addEventListener('input', function () { clearTimeout(t); t = setTimeout(apply, 120); });
            var ob = $('#mpOnlyBooked');
            if (ob) ob.addEventListener('click', function () {
                ob.classList.toggle('is-on'); ob.setAttribute('aria-pressed', ob.classList.contains('is-on') ? 'true' : 'false'); apply();
            });
            apply();

            var colW = 30, labelW = 210;
            function scrollToDate(d) {
                var th = wrap.querySelector('th[data-date="' + d + '"]'); if (!th) return;
                wrap.scrollTo({ left: Math.max(0, th.offsetLeft - labelW - 20), behavior: 'smooth' });
            }
            var today = root.getAttribute('data-today');
            var tb = $('[data-mp-today]');
            if (tb) tb.addEventListener('click', function () { scrollToDate(today); });
            $$('[data-mp-jump]').forEach(function (b) { b.addEventListener('click', function () { scrollToDate(b.getAttribute('data-mp-jump')); }); });
            $$('[data-mp-scroll]').forEach(function (b) {
                b.addEventListener('click', function () { wrap.scrollBy({ left: +b.getAttribute('data-mp-scroll') * colW, behavior: 'smooth' }); });
            });
            // buka di hari ini
            var th0 = wrap.querySelector('th[data-date="' + today + '"]');
            if (th0) wrap.scrollLeft = Math.max(0, th0.offsetLeft - labelW - 20);
        }

        function row(k, v, bad) {
            return '<div style="display:flex;gap:12px;padding:7px 0;border-bottom:1px solid var(--mp-line)"><div class="mp-muted" style="min-width:84px;font-size:.78rem;padding-top:1px">' + k +
                '</div><div style="flex:1;font-size:.86rem;overflow-wrap:anywhere;' + (bad ? 'color:var(--mp-bad);font-weight:600' : '') + '">' + v + '</div></div>';
        }
        function days(d) { return Math.round((new Date(d + 'T00:00:00') - new Date(root.getAttribute('data-today') + 'T00:00:00')) / 86400000); }

        root.addEventListener('click', function (e) {
            var bar = e.target.closest('[data-mp-usage]'); if (!bar) return;
            var u = usages[bar.getAttribute('data-mp-usage')]; if (!u) return;
            var st = STAT[u.status] || [u.status, 'mute'], ends = days(u.tanggal_selesai), starts = days(u.tanggal_mulai), due = '';
            if (u.status === 'pending' || u.status === 'approved') {
                due = ends < 0 ? 'Sudah berakhir' : (starts > 0 ? 'Mulai ' + starts + ' hari lagi' : (ends === 0 ? 'Berakhir hari ini' : 'Berakhir ' + ends + ' hari lagi'));
            }
            var h = '<div style="margin-bottom:10px;display:flex;gap:.4rem;flex-wrap:wrap"><span class="mp-chip mp-chip-' + st[1] + '">' + esc(st[0]) + '</span>' +
                (due ? '<span class="mp-chip mp-chip-' + (ends >= 0 && ends <= 7 && starts <= 0 ? 'warn' : 'mute') + '"><i class="bi bi-clock"></i>' + esc(due) + '</span>' : '') + '</div>';
            h += row('Titik', esc(u.spot_kode || '') + (u.spot_nama ? ' — ' + esc(u.spot_nama) : ''));
            if (u.slot_number) h += row('Slot', 'Slot ' + esc(u.slot_number));
            h += row('Dept', esc(u.dept || '–') + (u.requested_by ? ' · ' + esc(u.requested_by) : ''));
            h += row('Materi', '<strong>' + esc(u.nama_materi || '–') + '</strong>');
            if (u.deskripsi_materi) h += row('Keterangan', esc(u.deskripsi_materi));
            h += row('Periode', esc(tglID(u.tanggal_mulai)) + (u.tanggal_selesai ? ' s/d ' + esc(tglID(u.tanggal_selesai)) : ''));
            h += row('Sumber', esc(SUMBER[u.sumber] || u.sumber || '–') + ' · ' + (u.is_berbayar == 1 ? 'Berbayar' : 'Gratis'));
            if (u.catatan_pemohon) h += row('Catatan', esc(u.catatan_pemohon));
            if (u.rejection_reason) h += row('Ditolak', esc(u.rejection_reason), true);
            $('#gModalBody').innerHTML = h;

            var foot = $('#gModalFooter');
            foot.innerHTML = '';
            foot.hidden = !(canApprove && u.status === 'pending');
            if (canApprove && u.status === 'pending') {
                foot.innerHTML = '<button type="button" class="mp-btn mp-btn-sm mp-btn-solid-ok" data-act="approve"><i class="bi bi-check-lg"></i>Approve</button>' +
                    '<button type="button" class="mp-btn mp-btn-sm mp-btn-bad" data-act="reject"><i class="bi bi-x-lg"></i>Reject</button>';
                foot.onclick = function (ev) {
                    var b = ev.target.closest('[data-act]'); if (!b) return;
                    var reason = '';
                    if (b.dataset.act === 'approve') { if (!window.confirm('Approve request ini?')) return; }
                    else { reason = window.prompt('Alasan penolakan:'); if (!reason) return; }
                    var f = document.createElement('form');
                    f.method = 'POST'; f.action = BASE + 'creative/media-promo/usage/' + u.id + '/' + b.dataset.act;
                    var i = document.createElement('input'); i.type = 'hidden'; i.name = csrfName; i.value = csrfHash; f.appendChild(i);
                    if (reason) { var r = document.createElement('input'); r.type = 'hidden'; r.name = 'rejection_reason'; r.value = reason; f.appendChild(r); }
                    document.body.appendChild(f); f.submit();
                };
            }
            modal('gModal').show();
        });
    }

    /* ═══════════════ SUMMARY ═══════════════ */
    function pageSummary() {
        var rows = $$('table[data-sortable] tbody tr'), fTipe = $('#mpFTipe'), fLevel = $('#mpFLevel');
        if (!rows.length || !fTipe) return;
        function apply() {
            var tm = term(), shown = 0;
            rows.forEach(function (r) {
                var ok = true;
                if (tm && r.dataset.search.indexOf(tm) < 0) ok = false;
                if (ok && fTipe.value && r.dataset.tipe !== fTipe.value) ok = false;
                if (ok && fLevel.value && r.dataset.level !== fLevel.value) ok = false;
                r.hidden = !ok; if (ok) shown++;
            });
            var f = tm || fTipe.value || fLevel.value;
            $('#mpCount').textContent = f ? shown + ' dari ' + rows.length + ' titik' : rows.length + ' titik';
            $('#mpNoMatch').hidden = shown > 0;
        }
        wireTools(apply, [fTipe, fLevel]);
        wireSortable();
        apply();
    }

    ({ index: pageIndex, master: pageMaster, pending: pagePending, my: pageMy, gantt: pageGantt, summary: pageSummary }[PAGE] || function () {})();
})();
