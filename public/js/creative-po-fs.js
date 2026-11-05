/* MIC — pemilih "Tautkan PO FlowStore" di form realisasi Creative, Content Event, dan Dekorasi & VM (lihat app/Views/_creative/_po_fs.php).
   Barang yang dipilih dikirim sebagai JSON [{id,pos,mall}] di input fs_po. Nilai & tanggal form mengikuti BTB (dikunci);
   server menghitung ulang dari FlowStore, jadi angka di layar hanya pratinjau. Antrean ber-tanpa_pos (Content/VM) tidak
   meminta pos: pos & bulan mengikuti item realisasinya. */
(function () {
    'use strict';
    const rp = n => 'Rp ' + Math.round(n).toLocaleString('id-ID');
    const bulanIdn = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    const tgl = s => { const p = String(s || '').split('-'); return p.length === 3 ? (+p[2]) + ' ' + bulanIdn[+p[1] - 1] + ' ' + p[0] : s; };
    const esc = s => String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);

    function pasang(box) {
        const form = box.closest('form');
        const hidden = box.querySelector('input[name="fs_po"]');
        const tombol = box.querySelector('[data-po-fs-buka]');
        const panel = box.querySelector('[data-po-fs-panel]');
        const daftar = box.querySelector('[data-po-fs-daftar]');
        const cari = box.querySelector('[data-po-fs-q]');
        const ringkas = box.querySelector('[data-po-fs-ringkas]');
        const inNilai = form.querySelector('input[name="nilai"], input[name="jumlah"]');
        const inTgl = form.querySelector('input[name="tanggal"]');
        let data = null, dimuat = false, timer = null;
        const pilihan = new Map();   // id -> {id,pos,mall,nilai,tanggal}

        function muat(q) {
            daftar.innerHTML = '<div class="po-fs-kosong">Memuat antrean FlowStore…</div>';
            fetch(box.dataset.url + (q ? '?q=' + encodeURIComponent(q) : ''), { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
                .then(r => r.json())
                .then(j => { data = j; gambar(); })
                .catch(() => { daftar.innerHTML = '<div class="po-fs-kosong">Antrean FlowStore tidak bisa dimuat. Muat ulang halaman lalu coba lagi.</div>'; });
        }

        function gambar() {
            if (!data || !data.ok) { daftar.innerHTML = '<div class="po-fs-kosong">' + esc((data && data.pesan) || 'Antrean tidak tersedia.') + '</div>'; return; }
            const per = new Map();
            data.baris.forEach(b => { const k = b.no_po || '(tanpa PO)'; if (!per.has(k)) per.set(k, []); per.get(k).push(b); });
            // Barang yang sudah dipilih tetap terpilih walau tidak muncul di hasil pencarian (ringkasan di tombol tetap menghitungnya)
            if (!per.size && !pilihan.size) { daftar.innerHTML = '<div class="po-fs-kosong">Tidak ada barang FlowStore ' + esc(box.dataset.dept || '') + ' yang belum diproses' + (cari.value ? ' untuk pencarian ini' : '') + '.</div>'; return; }
            const opsiPos = sel => '<option value="">— pilih pos —</option>' + data.pos.map(p => '<option value="' + p.id + '"' + (p.id === sel ? ' selected' : '') + '>' + esc(p.nama) + '</option>').join('');
            const opsiMal = sel => Object.entries(data.mal).map(([k, v]) => '<option value="' + k + '"' + (k === sel ? ' selected' : '') + '>' + esc(v) + '</option>').join('');
            let html = '';
            per.forEach((rows, po) => {
                const total = rows.reduce((a, b) => a + b.nilai, 0);
                const semua = rows.every(b => pilihan.has(b.id));
                html += '<div class="po-fs-po"><div class="po-fs-po-h"><label><input type="checkbox" class="form-check-input" data-po-semua="' + esc(po) + '"' + (semua ? ' checked' : '') + '>PO ' + esc(po) + '</label>'
                    + '<span class="po-fs-sub">' + esc(rows[0].supplier || '') + ' · ' + rows.length + ' barang · ' + rp(total) + '</span>'
                    + (rows[0].keperluan ? '<span class="po-fs-kep">' + esc(rows[0].keperluan) + '</span>' : '') + '</div>';
                rows.forEach(b => {
                    const p = pilihan.get(b.id);
                    html += '<div class="po-fs-brs" data-id="' + b.id + '"><input type="checkbox" class="form-check-input" data-po-brs aria-label="Pilih ' + esc(b.barang) + '"' + (p ? ' checked' : '') + '>'
                        + '<div><span class="po-fs-brg">' + esc(b.barang) + '</span><span class="po-fs-sub">diterima ' + esc(tgl(b.tanggal)) + ' · BTB ' + esc(b.no_btb) + ' · ' + esc(b.qty) + ' ' + esc(b.satuan) + '</span></div>'
                        + '<span class="po-fs-nilai">' + rp(b.nilai) + '</span>'
                        + (p && !data.tanpa_pos ? '<div class="po-fs-pilih"><select class="form-select form-select-sm' + (p.pos ? '' : ' is-invalid') + '" data-po-pos aria-label="Pos budget ' + esc(box.dataset.dept || '') + '">' + opsiPos(p.pos) + '</select>'
                            + (data.pisah ? '<select class="form-select form-select-sm" data-po-mal aria-label="Mal">' + opsiMal(p.mall) + '</select>' : '') + '</div>' : '')
                        + '</div>';
                });
                html += '</div>';
            });
            daftar.innerHTML = html;
        }

        function cariBaris(id) { return data && data.baris.find(b => b.id === id); }
        function pilih(b, ya) {
            if (ya) pilihan.set(b.id, { id: b.id, pos: b.pos || 0, mall: data.pisah ? (b.mall || 'both') : '', nilai: b.nilai, tanggal: b.tanggal });
            else pilihan.delete(b.id);
        }

        function segarkan() {
            const arr = [...pilihan.values()];
            hidden.value = arr.length ? JSON.stringify(arr.map(p => ({ id: p.id, pos: p.pos, mall: p.mall }))) : '';
            const total = arr.reduce((a, p) => a + p.nilai, 0);
            ringkas.textContent = arr.length ? arr.length + ' barang · ' + rp(total) : '';
            [inNilai, inTgl].forEach(el => {
                if (!el) return;
                if (!el.dataset.poAsli && arr.length) el.dataset.poAsli = el.value;
                el.readOnly = arr.length > 0;
                el.classList.toggle('po-fs-terkunci', arr.length > 0);
            });
            if (arr.length) {
                if (inNilai) inNilai.value = Math.round(total).toLocaleString('id-ID');
                if (inTgl) inTgl.value = arr.map(p => p.tanggal).sort().pop();
            } else {
                [inNilai, inTgl].forEach(el => { if (el && el.dataset.poAsli !== undefined) { el.value = el.dataset.poAsli; delete el.dataset.poAsli; } });
            }
            let note = form.querySelector('.po-fs-kunci');
            if (arr.length && !note && inNilai) { note = document.createElement('span'); note.className = 'po-fs-kunci'; inNilai.insertAdjacentElement('afterend', note); }
            if (note) { if (arr.length) note.textContent = 'Nilai & tanggal mengikuti PO FlowStore'; else note.remove(); }
        }

        tombol.addEventListener('click', () => {
            const buka = panel.hidden;
            panel.hidden = !buka;
            tombol.setAttribute('aria-expanded', buka ? 'true' : 'false');
            if (buka && !dimuat) { dimuat = true; muat(''); }
        });
        cari.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(() => muat(cari.value.trim()), 300); });
        cari.addEventListener('keydown', e => { if (e.key === 'Enter') e.preventDefault(); });
        daftar.addEventListener('change', e => {
            const t = e.target;
            if (t.matches('[data-po-semua]')) {
                data.baris.filter(b => (b.no_po || '(tanpa PO)') === t.dataset.poSemua).forEach(b => pilih(b, t.checked));
                gambar();
            } else if (t.matches('[data-po-brs]')) {
                const b = cariBaris(+t.closest('[data-id]').dataset.id);
                if (b) pilih(b, t.checked);
                gambar();
            } else if (t.matches('[data-po-pos], [data-po-mal]')) {
                const p = pilihan.get(+t.closest('[data-id]').dataset.id);
                if (p) { if (t.matches('[data-po-pos]')) { p.pos = +t.value || 0; t.classList.toggle('is-invalid', !p.pos); } else p.mall = t.value; }
            }
            segarkan();
        });
        form.addEventListener('submit', e => {
            const kurang = data && data.tanpa_pos ? [] : [...pilihan.values()].filter(p => !p.pos);
            if (kurang.length) {
                e.preventDefault();
                panel.hidden = false; tombol.setAttribute('aria-expanded', 'true');
                alert('Pilih pos budget ' + (box.dataset.dept || '') + ' untuk ' + kurang.length + ' barang PO yang belum berpos.');
                const s = daftar.querySelector('select.is-invalid'); if (s) s.focus();
            }
        });
    }

    function mulai() { document.querySelectorAll('[data-po-fs]').forEach(pasang); }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', mulai); else mulai();
})();
