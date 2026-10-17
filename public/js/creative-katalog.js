/* MIC — Creative: halaman Katalog Turunan & Paket Item Awal. */
(function () {
    'use strict';
    var root = document.getElementById('creKat');
    if (!root) return;
    function $(s, c) { return (c || document).querySelector(s); }
    function $$(s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); }
    function g(id) { return document.getElementById(id); }
    function modal(id) { return bootstrap.Modal.getOrCreateInstance(g(id)); }

    // ── Tab ──
    var tabs = g('katTabs');
    tabs.addEventListener('click', function (e) {
        var b = e.target.closest('[data-kat-tab]'); if (!b) return;
        $$('[data-kat-tab]', tabs).forEach(function (x) { var on = x === b; x.classList.toggle('is-on', on); x.setAttribute('aria-selected', on ? 'true' : 'false'); });
        $$('[data-kat-pane]', root).forEach(function (p) { p.hidden = p.dataset.katPane !== b.dataset.katTab; });
        try { history.replaceState(null, '', '?tab=' + b.dataset.katTab); } catch (x) { /* abaikan */ }
    });

    // ── Jenis turunan ──
    var opsiBox = g('kjOpsi');
    function barisOpsi(o) {
        o = o || {};
        var d = document.createElement('div'); d.className = 'kat-opsi';
        d.innerHTML = '<input type="text" name="opsi_label[]" class="form-control form-control-sm" placeholder="Label, mis. 1080x1350 (potret)" maxlength="120" aria-label="Label ukuran">' +
            '<input type="number" name="opsi_w[]" class="form-control form-control-sm" placeholder="Lebar" min="0" aria-label="Lebar">' +
            '<input type="number" name="opsi_h[]" class="form-control form-control-sm" placeholder="Tinggi" min="0" aria-label="Tinggi">' +
            '<select name="opsi_sat[]" class="form-select form-select-sm" aria-label="Satuan"><option>px</option><option>mm</option><option>cm</option><option>m</option></select>' +
            '<button type="button" class="btn btn-sm btn-outline-secondary" aria-label="Hapus opsi"><i class="bi bi-x-lg"></i></button>';
        var i = $$('input,select', d);
        i[0].value = o.label || ''; i[1].value = o.w || ''; i[2].value = o.h || ''; i[3].value = o.satuan || 'px';
        $('button', d).addEventListener('click', function () { d.remove(); });
        opsiBox.appendChild(d);
    }
    function aturBebas() { g('kjOpsiWrap').hidden = g('kjBebas').checked; }
    g('kjBebas').addEventListener('change', aturBebas);
    g('kjTambahOpsi').addEventListener('click', function () { barisOpsi(); });

    function bukaJenis(d, induk) {
        d = d || {};
        induk = d.induk || induk;
        g('kjId').value = d.id || ''; g('kjInduk').value = induk;
        g('katJenisTitle').textContent = (d.id ? 'Ubah jenis turunan' : 'Jenis turunan baru') + ' — ' + (induk === 'design' ? 'Design' : 'Motion Graphic');
        g('kjNama').value = d.nama || '';
        var kel = g('kjKel'); kel.innerHTML = '';
        (induk === 'design' ? [['digital', 'Digital'], ['cetak', 'Cetak']] : [['video', 'Video']]).forEach(function (k) {
            var o = document.createElement('option'); o.value = k[0]; o.textContent = k[1]; kel.appendChild(o);
        });
        kel.value = d.kelompok || kel.options[0].value;
        g('kjBebas').checked = !!d.bebas; aturBebas();
        opsiBox.innerHTML = '';
        (d.opsi && d.opsi.length ? d.opsi : (d.bebas ? [] : [{}])).forEach(barisOpsi);
        if (!opsiBox.children.length) barisOpsi();
        g('kjDurasiWrap').hidden = induk !== 'motion_graphic';
        g('kjDurasi').value = (d.durasi || []).join(', ');
        modal('katJenisModal').show();
    }
    $$('[data-kat-jenis-baru]', root).forEach(function (b) { b.addEventListener('click', function () { bukaJenis(null, b.dataset.katJenisBaru); }); });
    $$('[data-kat-jenis-ubah]', root).forEach(function (b) { b.addEventListener('click', function () { bukaJenis(JSON.parse(b.dataset.katJenisUbah)); }); });
    g('katJenisModal').addEventListener('shown.bs.modal', function () { g('kjNama').focus(); });

    // ── Paket ──
    function platTampil() { g('kpPlatDiv').hidden = g('kpTipe').value !== 'digital'; g('kpCetakDiv').hidden = ['cetak', 'digital'].indexOf(g('kpTipe').value) < 0; g('kpJenisLbl').textContent = g('kpTipe').value === 'digital' ? 'Format konten' : 'Jenis media'; }
    g('kpTipe').addEventListener('change', platTampil);
    function bukaPaket(d, konteks) {
        d = d || {};
        g('kpId').value = d.id || ''; g('kpKonteks').value = d.konteks || konteks;
        g('katPaketTitle').textContent = (d.id ? 'Ubah baris paket' : 'Baris paket baru') + ' — ' + ((d.konteks || konteks) === 'event' ? 'Event' : 'Tema Bulanan');
        g('kpTipe').value = d.tipe || 'master_design'; g('kpPola').value = d.nama_pola || ''; g('kpPlat').value = d.platform || 'ig'; g('kpJenis').value = d.jenis_media || ''; g('kpUkuran').value = d.ukuran || '';
        platTampil(); modal('katPaketModal').show();
    }
    $$('[data-kat-paket-baru]', root).forEach(function (b) { b.addEventListener('click', function () { bukaPaket(null, b.dataset.katPaketBaru); }); });
    $$('[data-kat-paket-ubah]', root).forEach(function (b) { b.addEventListener('click', function () { bukaPaket(JSON.parse(b.dataset.katPaketUbah)); }); });
    g('katPaketModal').addEventListener('shown.bs.modal', function () { g('kpPola').focus(); });

    // ── Sorot baris setelah simpan / geser ──
    if (location.hash && /^#jenis-\d+$/.test(location.hash)) {
        var r = $(location.hash); if (r) { r.classList.add('is-flash'); setTimeout(function () { r.scrollIntoView({ block: 'center' }); }, 60); }
    }
})();
