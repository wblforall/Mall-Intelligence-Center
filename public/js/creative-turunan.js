/* MIC — Creative: daftar centang Turunan (modal), dipakai di /creative dan tab Creative event. */
(function () {
    'use strict';
    // Tombol ubah pada baris turunan: buka/tutup panel ubah di bawah baris
    document.addEventListener('click', function (e) {
        var b = e.target.closest('[data-ct-edit]');
        if (!b) return;
        var row = b.closest('[data-ct-row]'), box = row && row.querySelector('.ct-editbox');
        if (box) box.hidden = !box.hidden;
    });
    // Media Cetak: daftar ukuran (datalist) mengikuti jenis media terpilih
    function isiUkuran(sel) {
        var dl = document.getElementById(sel.dataset.list); if (!dl) return;
        var jenis = (window.CRE_CETAK || []).filter(function (j) { return j.nama === sel.value; })[0];
        dl.innerHTML = '';
        (jenis ? jenis.ukuran : []).forEach(function (u) { var o = document.createElement('option'); o.value = u.replace(/\s*\((.*)\)\s*$/, ' $1').replace(/(\d)x(\d)/g, '$1×$2'); dl.appendChild(o); });
        return jenis;
    }
    document.addEventListener('change', function (e) {
        var sel = e.target.closest ? e.target.closest('.js-cetak-jenis') : null;
        if (!sel) return;
        var j = isiUkuran(sel), uk = sel.closest('form').querySelector('.js-cetak-ukuran');
        if (uk && j && j.ukuran.length === 1 && !uk.value) uk.value = document.getElementById(sel.dataset.list).firstChild.value;
    });
    window.CRE_CETAKFILL = function (sel, uk, jenis, ukuran) {
        if (!sel) return;
        if (jenis && !Array.prototype.some.call(sel.options, function (o) { return o.value === jenis; })) {
            var o = document.createElement('option'); o.value = o.textContent = jenis; sel.insertBefore(o, sel.lastElementChild);
        }
        sel.value = jenis || ''; isiUkuran(sel); uk.value = ukuran || '';
    };
    var modalEl = document.getElementById('turunanPickModal');
    if (!modalEl) return;
    function $(s, c) { return (c || document).querySelector(s); }
    function $$(s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); }

    var form = $('#turunanPickForm', modalEl), q = $('#ctQ', modalEl), sum = $('#ctSum', modalEl),
        submit = $('#ctSubmit', modalEl), toggleAll = $('#ctToggleAll', modalEl), noMatch = $('#ctNoMatch', modalEl);
    var curInduk = '';

    function blokAktif() { return $('[data-ct-induk="' + curInduk + '"]', modalEl); }

    function hitung() {
        var blok = blokAktif(), n = 0;
        if (blok) {
            $$('[data-ct-jenis]', blok).forEach(function (j) {
                var ukuran = $$('input[name="pilih[]"]:checked', j).length;
                var lain = $('.ct-lain', j);
                if (lain && lain.value.trim()) ukuran += lain.value.split(/[;,\n]+/).filter(function (x) { return x.trim(); }).length;
                if (!ukuran) return;
                if (j.dataset.hasDur === '1') {
                    var d = $$('input[name^="dur"]:checked', j).length;
                    n += ukuran * d;
                } else n += ukuran;
            });
        }
        var kNama = $('input[name="k_nama"]', modalEl);
        if (kNama && kNama.value.trim()) {
            var ku = $('input[name="k_ukuran"]', modalEl).value.split(/[;,\n]+/).filter(function (x) { return x.trim(); }).length;
            n += Math.max(1, ku);
        }
        sum.textContent = n > 0 ? n + ' turunan akan dibuat' : 'Belum ada yang dipilih';
        submit.disabled = n === 0;
        // petunjuk durasi untuk jenis berdurasi yang sudah punya ukuran terpilih tapi belum durasi
        if (blok) $$('[data-ct-jenis]', blok).forEach(function (j) {
            if (j.dataset.hasDur !== '1') return;
            var ukuran = $$('input[name="pilih[]"]:checked', j).length + (($('.ct-lain', j).value || '').trim() ? 1 : 0);
            var d = $$('input[name^="dur"]:checked', j).length;
            j.classList.toggle('need-dur', ukuran > 0 && d === 0);
        });
    }

    function saring() {
        var term = q.value.trim().toLowerCase(), blok = blokAktif(), tampil = 0;
        if (!blok) return;
        $$('[data-ct-jenis]', blok).forEach(function (j) {
            var ok = !term || j.dataset.q.indexOf(term) > -1;
            j.hidden = !ok; if (ok) tampil++;
        });
        $$('.ct-pg', blok).forEach(function (g) {
            var any = $$('[data-ct-jenis]', g).some(function (j) { return !j.hidden; });
            g.hidden = !any;
            if (term && any) g.open = true;
        });
        noMatch.hidden = tampil > 0 || !term;
    }

    document.addEventListener('click', function (e) {
        var b = e.target.closest('[data-ct-pick]');
        if (!b) return;
        e.preventDefault();
        curInduk = b.dataset.induk;
        form.action = b.dataset.action;
        form.reset();
        $('#turunanPickNama', modalEl).textContent = b.dataset.nama || '';
        $$('[data-ct-induk]', modalEl).forEach(function (el) { el.hidden = el.dataset.ctInduk !== curInduk; });
        q.value = '';
        // tandai yang sudah ada
        var ada = []; try { ada = JSON.parse(b.dataset.ada || '[]'); } catch (x) { ada = []; }
        var blok = blokAktif();
        $$('.ct-opt[data-label]', modalEl).forEach(function (o) { o.classList.remove('is-ada'); var i = $('input', o); i.disabled = false; var t = $('.ct-ada', o); if (t) t.remove(); });
        if (blok) $$('[data-ct-jenis]', blok).forEach(function (j) {
            if (j.dataset.hasDur === '1') return;
            var jid = +j.dataset.jid;
            $$('.ct-opt[data-label]', j).forEach(function (o) {
                var hit = ada.some(function (a) { return a[0] === jid && String(a[1]).toLowerCase() === o.dataset.label.toLowerCase(); });
                if (hit) {
                    o.classList.add('is-ada'); $('input', o).disabled = true;
                    var s = document.createElement('em'); s.className = 'ct-ada'; s.textContent = 'sudah ada'; o.appendChild(s);
                }
            });
        });
        $$('[data-ct-jenis], .ct-pg', modalEl).forEach(function (el) { el.hidden = false; });
        if (blok) $$('.ct-pg', blok).forEach(function (gp, i) { gp.open = i === 0; });   // kelompok pertama terbuka, sisanya terlipat
        $$('.ct-kustom', modalEl).forEach(function (gp) { gp.open = false; });
        toggleAll.innerHTML = '<i class="bi bi-arrows-expand"></i> Buka semua';
        noMatch.hidden = true;
        hitung();
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    });

    q.addEventListener('input', function () { saring(); });
    form.addEventListener('input', hitung);
    form.addEventListener('change', hitung);
    form.addEventListener('submit', function () { submit.disabled = true; });
    toggleAll.addEventListener('click', function () {
        var blok = blokAktif(); if (!blok) return;
        var gs = $$('.ct-pg', blok), anyClosed = gs.some(function (g) { return !g.open; });
        gs.forEach(function (g) { g.open = anyClosed; });
        toggleAll.innerHTML = anyClosed ? '<i class="bi bi-arrows-collapse"></i> Lipat semua' : '<i class="bi bi-arrows-expand"></i> Buka semua';
    });
    modalEl.addEventListener('shown.bs.modal', function () { q.focus(); });
})();
