<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<style>
/* ═══ Gantt Timeline — dibangun sendiri (tanpa Frappe Gantt) ═══════════════
   Satu wadah gulir: baris judul menempel di atas, kolom nama menempel di kiri,
   nama di dalam bar ikut menempel sehingga program panjang tetap terbaca. */
.gx-page { --gx-label: 260px; --gx-bg: #0b1736; --gx-line: rgba(255,255,255,.06); --gx-head: #091430;
           --gx-muted: var(--txt-muted, #8fa3c4); --gx-txt: var(--txt, #e3ecfa); --gx-wk: rgba(148,163,184,.07); }
[data-theme="light"] .gx-page { --gx-bg: #ffffff; --gx-line: rgba(15,23,42,.07); --gx-head: #f5f7fb;
           --gx-muted: #64748b; --gx-txt: #0f172a; --gx-wk: rgba(100,116,139,.07); }

.gx-toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; }
.gx-seg { display: inline-flex; border: 1px solid var(--bs-border-color); border-radius: 10px; overflow: hidden; }
.gx-seg button { border: 0; background: transparent; color: var(--gx-muted); font-size: .8rem; font-weight: 600; padding: .4rem .8rem; }
.gx-seg button + button { border-left: 1px solid var(--bs-border-color); }
.gx-seg button.on { background: var(--bs-primary); color: #fff; }
.gx-chip { display: inline-flex; align-items: center; gap: .4rem; border: 1px solid var(--bs-border-color); border-radius: 999px;
           background: transparent; color: var(--gx-txt); font-size: .78rem; font-weight: 600; padding: .3rem .75rem; }
.gx-chip i { width: 10px; height: 10px; border-radius: 3px; display: inline-block; }
.gx-chip b { font-weight: 700; color: var(--gx-muted); font-variant-numeric: tabular-nums; }
.gx-chip.off { opacity: .45; text-decoration: line-through; }
.gx-key { display: inline-flex; align-items: center; gap: .35rem; font-size: .74rem; color: var(--gx-muted); }
.gx-key i { width: 12px; height: 10px; border-radius: 2px; display: inline-block; }

.gx-wrap { position: relative; overflow: auto; max-height: calc(100vh - 230px); min-height: 320px;
           border: 1px solid var(--gx-line); border-radius: 12px; background: var(--gx-bg); }
.gx-grid { position: relative; }
.gx-head { position: sticky; top: 0; z-index: 6; display: flex; background: var(--gx-head); border-bottom: 1px solid var(--gx-line); }
.gx-corner { position: sticky; left: 0; z-index: 7; flex: 0 0 var(--gx-label); width: var(--gx-label); background: var(--gx-head);
             border-right: 1px solid var(--gx-line); display: flex; align-items: flex-end; padding: 0 12px 8px;
             font-size: .7rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--gx-muted); }
.gx-scale { position: relative; flex: 0 0 auto; }
.gx-month { position: absolute; top: 0; height: 26px; display: flex; align-items: center; padding-left: 8px; font-size: .78rem; font-weight: 700;
            color: var(--gx-txt); border-left: 1px solid var(--gx-line); white-space: nowrap; overflow: hidden; }
.gx-day { position: absolute; top: 26px; height: 34px; display: flex; flex-direction: column; align-items: center; justify-content: center;
          font-size: .68rem; color: var(--gx-muted); line-height: 1.15; font-variant-numeric: tabular-nums; }
.gx-day small { font-size: .6rem; opacity: .8; }
.gx-day.red { color: #f87171; }
.gx-day.now { color: #fff; }
.gx-day.now span { background: #ef4444; border-radius: 6px; padding: 0 4px; }

.gx-body { position: relative; }
.gx-bgl { position: absolute; top: 0; bottom: 0; pointer-events: none; z-index: 0; }
.gx-bgl .wk   { position: absolute; top: 0; bottom: 0; background: var(--gx-wk); }
.gx-bgl .hol  { position: absolute; top: 0; bottom: 0; background: rgba(239,68,68,.12); }
.gx-bgl .mon  { position: absolute; top: 0; bottom: 0; width: 1px; background: var(--gx-line); }
.gx-bgl .thm  { position: absolute; top: 0; bottom: 0; background: rgba(245,158,11,.07); border-left: 1px dashed rgba(245,158,11,.45); }
.gx-bgl .today{ position: absolute; top: 0; bottom: 0; width: 2px; background: #ef4444; box-shadow: 0 0 0 3px rgba(239,68,68,.15); }

.gx-group { position: relative; z-index: 2; display: flex; height: 34px; border-bottom: 1px solid var(--gx-line); }
.gx-group .lab { position: sticky; left: 0; z-index: 3; flex: 0 0 var(--gx-label); width: var(--gx-label); background: var(--gx-head);
                 display: flex; align-items: center; gap: .5rem; padding: 0 12px; font-size: .76rem; font-weight: 800; letter-spacing: .04em;
                 text-transform: uppercase; color: var(--gx-txt); border-right: 1px solid var(--gx-line); }
.gx-group .lab i { width: 10px; height: 10px; border-radius: 3px; }
.gx-group .lab b { margin-left: auto; font-weight: 700; color: var(--gx-muted); }
.gx-group .fill { flex: 1 0 auto; background: color-mix(in srgb, var(--gx-head) 60%, transparent); }

.gx-row { position: relative; z-index: 1; display: flex; height: 34px; border-bottom: 1px solid var(--gx-line); }
.gx-row:hover { background: rgba(255,255,255,.03); }
[data-theme="light"] .gx-row:hover { background: rgba(15,23,42,.025); }
.gx-row .lab { position: sticky; left: 0; z-index: 3; flex: 0 0 var(--gx-label); width: var(--gx-label); background: var(--gx-bg);
               display: flex; align-items: center; padding: 0 12px 0 28px; font-size: .8rem; color: var(--gx-txt);
               border-right: 1px solid var(--gx-line); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.gx-row .lab span { overflow: hidden; text-overflow: ellipsis; }
.gx-track { position: relative; flex: 0 0 auto; }
.gx-bar { position: absolute; top: 6px; height: 22px; border-radius: 6px; display: flex; align-items: center; overflow: visible;
          font-size: .72rem; font-weight: 600; color: #fff; cursor: pointer; border: 0; padding: 0; text-align: left;
          box-shadow: 0 1px 2px rgba(0,0,0,.25); }
.gx-bar:focus-visible { outline: 2px solid #f2c14e; outline-offset: 2px; }
.gx-bar.cut-l { border-top-left-radius: 0; border-bottom-left-radius: 0; }
.gx-bar.cut-r { border-top-right-radius: 0; border-bottom-right-radius: 0; }
.gx-bar .nm { position: sticky; left: calc(var(--gx-label) + 8px); padding: 0 8px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%; }
.gx-bar.tiny .nm { position: absolute; left: 100%; color: var(--gx-txt); padding-left: 6px; max-width: none; }
.gx-bar.tbc { background-image: repeating-linear-gradient(135deg, rgba(255,255,255,.18) 0 6px, transparent 6px 12px); }

.gx-tip { position: fixed; z-index: 1080; max-width: 280px; background: #0d1f4a; color: #e3ecfa; border: 1px solid rgba(120,170,255,.25);
          border-radius: 10px; padding: 10px 12px; font-size: .78rem; line-height: 1.5; box-shadow: 0 12px 30px rgba(0,0,0,.4); }
[data-theme="light"] .gx-tip { background: #fff; color: #0f172a; border-color: #dbe2ee; }
.gx-tip .t { font-weight: 700; margin-bottom: 2px; }
.gx-tip .m { color: #9fb2d4; }
[data-theme="light"] .gx-tip .m { color: #64748b; }
.gx-tip a { display: inline-block; margin-top: 6px; font-weight: 600; }
.gx-empty { padding: 3rem 1rem; text-align: center; color: var(--gx-muted); }

@media (max-width: 767.98px) { .gx-page { --gx-label: 150px; } .gx-row .lab { padding-left: 14px; } }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="gx-page">

<div class="d-flex align-items-center gap-3 mb-3 flex-wrap fade-up" style="animation-delay:.05s">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-bar-chart-steps me-2"></i>Gantt Timeline</h4>
        <div class="text-muted" style="font-size:.8rem">Program, event, dan milestone dengan rentang tanggal</div>
    </div>
    <div class="ms-auto gx-toolbar">
        <select id="yearSelect" class="form-select form-select-sm" style="width:auto" aria-label="Tahun">
            <?php for ($y = date('Y') - 1; $y <= date('Y') + 2; $y++): ?>
            <option value="<?= $y ?>" <?= $y == $year ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
        <div class="gx-seg" role="group" aria-label="Rentang waktu">
            <button type="button" data-range="3" class="on">3 bulan</button>
            <button type="button" data-range="6">6 bulan</button>
            <button type="button" data-range="12">Setahun</button>
        </div>
    </div>
</div>

<div class="d-flex flex-wrap gap-2 align-items-center mb-3 fade-up" style="animation-delay:.1s">
    <div id="gx-chips" class="d-flex flex-wrap gap-2"></div>
    <div class="ms-auto d-flex flex-wrap gap-3">
        <span class="gx-key"><i style="background:#ef4444;width:3px"></i> Hari ini</span>
        <span class="gx-key"><i style="background:rgba(239,68,68,.35)"></i> Hari libur</span>
        <span class="gx-key"><i style="background:rgba(245,158,11,.35)"></i> Tema periode</span>
        <span class="gx-key"><i style="background:repeating-linear-gradient(135deg,#94a3b8 0 3px,transparent 3px 6px)"></i> Masih tbc</span>
    </div>
</div>

<div class="gx-wrap fade-up" id="gx-wrap" style="animation-delay:.15s">
    <div class="gx-empty" id="gx-msg">
        <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
        <div class="mt-2" style="font-size:.8rem">Memuat data…</div>
    </div>
    <div class="gx-grid" id="gx-grid" hidden></div>
</div>
<div class="text-muted mt-2" style="font-size:.74rem" id="gx-foot"></div>

</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
    const GROUPS = [
        { key: 'task-events',      label: 'Events',      color: '#3d8bff' },
        { key: 'task-people',      label: 'People',      color: '#22a06b' },
        { key: 'task-sponsorship', label: 'Sponsorship', color: '#9b5de5' },
        { key: 'task-loyalty',     label: 'Loyalty',     color: '#ea580c' },
        { key: 'task-vm',          label: 'VM Deadline', color: '#0891b2' },
    ];
    const EVENT_URL = <?= json_encode(rtrim(base_url('events'), '/') . '/') ?>;
    const BULAN = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    const HARI  = ['Min','Sen','Sel','Rab','Kam','Jum','Sab'];
    const DAY = 86400000;

    const st = { data: null, range: 3, off: new Set() };
    const $ = id => document.getElementById(id);
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const tgl = s => { const [y, m, d] = s.split('-').map(Number); return new Date(y, m - 1, d); };
    const fmt = d => d.toLocaleDateString('id-ID', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' });
    const hariIni = () => { const d = new Date(); d.setHours(0, 0, 0, 0); return d; };

    /* Rentang tampilan: tahun berjalan dimulai 2 minggu sebelum hari ini; tahun lain dari 1 Jan. */
    function rentang() {
        const thn = parseInt($('yearSelect').value, 10), kini = hariIni();
        if (st.range === 12) return [new Date(thn, 0, 1), new Date(thn + 1, 0, 1)];
        let awal = thn === kini.getFullYear() ? new Date(kini.getTime() - 14 * DAY) : new Date(thn, 0, 1);
        const akhir = new Date(awal.getFullYear(), awal.getMonth() + st.range, awal.getDate());
        return [awal, akhir];
    }
    const lebarHari = () => st.range === 3 ? 30 : st.range === 6 ? 15 : 4;

    function muat() {
        $('gx-msg').hidden = false; $('gx-grid').hidden = true;
        $('gx-msg').innerHTML = '<div class="spinner-border spinner-border-sm text-secondary"></div><div class="mt-2" style="font-size:.8rem">Memuat data…</div>';
        fetch(`<?= base_url('gantt/data') ?>?year=${$('yearSelect').value}`)
            .then(r => r.json())
            .then(d => { st.data = d; gambar(); })
            .catch(() => { $('gx-msg').innerHTML = 'Data tidak bisa dimuat. Coba muat ulang halaman.'; });
    }

    function gambar() {
        const d = st.data; if (!d) return;
        const [awal, akhir] = rentang();
        const W = lebarHari(), nHari = Math.round((akhir - awal) / DAY), lebar = nHari * W;
        const x = dt => Math.round((dt - awal) / DAY) * W;

        // Item yang bersinggungan dengan rentang, per kelompok
        const dalam = (d.tasks || []).filter(t => tgl(t.start) < akhir && tgl(t.end) > awal);
        const perGrup = {};
        dalam.forEach(t => (perGrup[t.custom_class] = perGrup[t.custom_class] || []).push(t));

        // Chip kategori (jumlah = di rentang ini)
        $('gx-chips').innerHTML = GROUPS.map(g => `<button type="button" class="gx-chip ${st.off.has(g.key) ? 'off' : ''}" data-g="${g.key}" aria-pressed="${!st.off.has(g.key)}">
            <i style="background:${g.color}"></i>${g.label} <b>${(perGrup[g.key] || []).length}</b></button>`).join('');
        $('gx-chips').querySelectorAll('.gx-chip').forEach(b => b.onclick = () => {
            st.off.has(b.dataset.g) ? st.off.delete(b.dataset.g) : st.off.add(b.dataset.g); gambar();
        });

        const tampil = GROUPS.filter(g => !st.off.has(g.key) && (perGrup[g.key] || []).length);
        if (!tampil.length) {
            $('gx-grid').hidden = true; $('gx-msg').hidden = false;
            $('gx-msg').innerHTML = '<i class="bi bi-calendar-x fs-3 d-block mb-2"></i>Tidak ada item di rentang ini yang dapat Anda akses.';
            $('gx-foot').textContent = ''; return;
        }

        /* ── Kepala: bulan + hari ── */
        let skala = '';
        for (let m = new Date(awal.getFullYear(), awal.getMonth(), 1); m < akhir; m = new Date(m.getFullYear(), m.getMonth() + 1, 1)) {
            const a = Math.max(0, x(m)), b = Math.min(lebar, x(new Date(m.getFullYear(), m.getMonth() + 1, 1)));
            if (b > a) skala += `<div class="gx-month" style="left:${a}px;width:${b - a}px">${BULAN[m.getMonth()]}${st.range === 12 ? '' : ' ' + m.getFullYear()}</div>`;
        }
        const libur = new Map((d.holidays || []).map(h => [h.tanggal, h.nama]));
        const kini = hariIni();
        let bg = '';
        for (let i = 0; i < nHari; i++) {
            const dt = new Date(awal.getFullYear(), awal.getMonth(), awal.getDate() + i);
            const ds = `${dt.getFullYear()}-${String(dt.getMonth() + 1).padStart(2, '0')}-${String(dt.getDate()).padStart(2, '0')}`;
            const wk = dt.getDay() === 0 || dt.getDay() === 6, hol = libur.has(ds), now = +dt === +kini;
            if (W >= 15) {
                const isi = W >= 30 ? `<small>${HARI[dt.getDay()]}</small><span>${dt.getDate()}</span>` : `<span>${dt.getDate()}</span>`;
                skala += `<div class="gx-day ${wk || hol ? 'red' : ''} ${now ? 'now' : ''}" style="left:${i * W}px;width:${W}px" ${hol ? `title="${esc(libur.get(ds))}"` : ''}>${isi}</div>`;
                if (wk) bg += `<div class="wk" style="left:${i * W}px;width:${W}px"></div>`;
            }
            if (hol) bg += `<div class="hol" style="left:${i * W}px;width:${Math.max(W, 2)}px" title="${esc(libur.get(ds))}"></div>`;
            if (dt.getDate() === 1 && i > 0) bg += `<div class="mon" style="left:${i * W}px"></div>`;
        }
        (d.theme_periods || []).forEach(tp => {
            const a = Math.max(0, x(tgl(tp.start_date))), b = Math.min(lebar, x(tgl(tp.end_date)) + W);
            if (b > a) bg += `<div class="thm" style="left:${a}px;width:${b - a}px" title="${esc(tp.nama)}"></div>`;
        });
        if (kini >= awal && kini < akhir) bg += `<div class="today" style="left:${x(kini) + W / 2 - 1}px"></div>`;
        const tinggiKepala = W >= 15 ? 60 : 26;

        /* ── Badan: kelompok + baris ── */
        let badan = '';
        tampil.forEach(g => {
            const list = perGrup[g.key].slice().sort((a, b) => a.start.localeCompare(b.start) || a.name.localeCompare(b.name));
            badan += `<div class="gx-group"><div class="lab"><i style="background:${g.color}"></i>${g.label}<b>${list.length}</b></div><div class="fill" style="width:${lebar}px"></div></div>`;
            list.forEach(t => {
                const s = tgl(t.start), e = tgl(t.end);
                const a = Math.max(0, x(s)), b = Math.min(lebar, x(e)), w = Math.max(b - a, Math.min(W, 6));
                const cls = ['gx-bar', s < awal ? 'cut-l' : '', e > akhir ? 'cut-r' : '', w < 70 ? 'tiny' : '', /\(tbc\)/i.test(t.name) ? 'tbc' : ''].join(' ');
                badan += `<div class="gx-row"><div class="lab" title="${esc(t.name)}"><span>${esc(t.name)}</span></div>
                    <div class="gx-track" style="width:${lebar}px">
                      <button type="button" class="${cls}" style="left:${a}px;width:${w}px;background-color:${g.color}" data-id="${esc(t.id)}"
                        aria-label="${esc(t.name)}, ${fmt(s)} sampai ${fmt(new Date(e - DAY))}"><span class="nm">${esc(t.name)}</span></button>
                    </div></div>`;
            });
        });

        const lab = getComputedStyle(document.querySelector('.gx-page')).getPropertyValue('--gx-label').trim();
        $('gx-grid').style.width = `calc(${lab} + ${lebar}px)`;
        $('gx-grid').innerHTML =
            `<div class="gx-head"><div class="gx-corner" style="height:${tinggiKepala}px">Item</div><div class="gx-scale" style="width:${lebar}px;height:${tinggiKepala}px">${skala}</div></div>
             <div class="gx-body"><div class="gx-bgl" style="left:${lab};width:${lebar}px">${bg}</div>${badan}</div>`;
        $('gx-msg').hidden = true; $('gx-grid').hidden = false;

        const total = (d.tasks || []).length;
        $('gx-foot').textContent = `${dalam.length} dari ${total} item tahun ${$('yearSelect').value} ada di rentang ${fmt(awal)} – ${fmt(new Date(akhir - DAY))}.`;

        // Geser ke hari ini (rentang setahun)
        const wrap = $('gx-wrap');
        if (st.range === 12 && kini >= awal && kini < akhir) wrap.scrollLeft = Math.max(0, x(kini) - wrap.clientWidth * 0.35);
        else wrap.scrollLeft = 0;

        // Detail saat bar diklik
        const byId = new Map((d.tasks || []).map(t => [t.id, t]));
        $('gx-grid').querySelectorAll('.gx-bar').forEach(btn => btn.onclick = ev => { ev.stopPropagation(); tip(btn, byId.get(btn.dataset.id)); });
    }

    let tipEl = null;
    function tutupTip() { if (tipEl) { tipEl.remove(); tipEl = null; } }
    function tip(btn, t) {
        tutupTip(); if (!t) return;
        const g = GROUPS.find(x => x.key === t.custom_class) || { label: '', color: '#64748b' };
        const s = tgl(t.start), e = new Date(tgl(t.end) - DAY), hari = Math.round((tgl(t.end) - s) / DAY);
        const link = t.id.startsWith('ev-') ? `<a href="${EVENT_URL}${encodeURIComponent(t.id.slice(3))}">Buka event →</a>` : '';
        tipEl = document.createElement('div'); tipEl.className = 'gx-tip'; tipEl.setAttribute('role', 'dialog');
        tipEl.innerHTML = `<div class="t">${esc(t.name)}</div><div class="m">${fmt(s)}${hari > 1 ? ' – ' + fmt(e) : ''} · ${hari} hari</div>
            <div class="m"><span style="display:inline-block;width:8px;height:8px;border-radius:2px;background:${g.color};margin-right:5px"></span>${g.label}</div>${link}`;
        document.body.appendChild(tipEl);
        const r = btn.getBoundingClientRect(), tw = tipEl.offsetWidth, th = tipEl.offsetHeight;
        const left = Math.min(window.innerWidth - tw - 12, Math.max(12, (window.matchMedia('(max-width:767.98px)').matches ? r.left : Math.max(r.left, 300))));
        tipEl.style.left = left + 'px';
        tipEl.style.top = (r.bottom + th + 12 > window.innerHeight ? r.top - th - 8 : r.bottom + 8) + 'px';
    }
    document.addEventListener('click', e => { if (tipEl && !tipEl.contains(e.target)) tutupTip(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') tutupTip(); });
    $('gx-wrap').addEventListener('scroll', tutupTip, { passive: true });

    document.querySelectorAll('.gx-seg button').forEach(b => b.onclick = () => {
        document.querySelectorAll('.gx-seg button').forEach(x => x.classList.toggle('on', x === b));
        st.range = parseInt(b.dataset.range, 10); gambar();
    });
    $('yearSelect').addEventListener('change', muat);

    muat();
})();
</script>
<?= $this->endSection() ?>
