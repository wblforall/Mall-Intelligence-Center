<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<style>
/* ── Bar colours ─────────────────────────────────────────── */
.gantt .bar-wrapper.task-events .bar        { fill: #2563eb; }
.gantt .bar-wrapper.task-people .bar        { fill: #16a34a; }
.gantt .bar-wrapper.task-sponsorship .bar   { fill: #9333ea; }
.gantt .bar-wrapper.task-loyalty .bar       { fill: #ea580c; }
.gantt .bar-wrapper.task-vm .bar            { fill: #0891b2; }
.gantt .bar-wrapper .bar-label              { fill: #fff; font-size: 11px; font-family: inherit; }
/* ── Grid chrome ─────────────────────────────────────────── */
.gantt .grid-header                         { fill: var(--c-surface-2, #1e293b); }
.gantt .tick                                { stroke: rgba(255,255,255,.08); }
/* ── Text (exclude our injected overlay texts) ───────────── */
.gantt :not(.mic-overlay) > text            { fill: var(--c-text-muted, #94a3b8); font-family: inherit; }
.gantt .lower-text, .gantt .upper-text      { fill: var(--c-text-muted, #94a3b8); font-size: 11px; }
/* ── Misc ────────────────────────────────────────────────── */
.gantt-container   { overflow-x: auto; border-radius: 4px; }
.gantt-legend-dot  { width: 12px; height: 12px; border-radius: 3px; display: inline-block; flex-shrink: 0; }
</style>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/frappe-gantt@0.6.1/dist/frappe-gantt.css">
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="d-flex align-items-center gap-3 mb-4 flex-wrap fade-up" style="animation-delay:.05s">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-bar-chart-steps me-2"></i>Gantt Timeline</h4>
        <div class="text-muted" style="font-size:.8rem">Program, event, dan milestone dengan rentang tanggal</div>
    </div>
    <div class="ms-auto d-flex align-items-center gap-2 flex-wrap">
        <select id="yearSelect" class="form-select form-select-sm" style="width:auto">
            <?php for ($y = date('Y') - 1; $y <= date('Y') + 2; $y++): ?>
            <option value="<?= $y ?>" <?= $y == $year ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
        <select id="viewMode" class="form-select form-select-sm" style="width:auto">
            <option value="Month">Bulanan</option>
            <option value="Week">Mingguan</option>
            <option value="Day" selected>Harian</option>
        </select>
    </div>
</div>

<!-- Legend -->
<div class="d-flex flex-wrap gap-3 mb-3 align-items-center fade-up" style="font-size:.78rem;animation-delay:.1s">
    <span class="d-flex align-items-center gap-1"><span class="gantt-legend-dot" style="background:#2563eb"></span> Events</span>
    <span class="d-flex align-items-center gap-1"><span class="gantt-legend-dot" style="background:#16a34a"></span> People (TNA / PIP / Training / EEI)</span>
    <span class="d-flex align-items-center gap-1"><span class="gantt-legend-dot" style="background:#9333ea"></span> Sponsorship</span>
    <span class="d-flex align-items-center gap-1"><span class="gantt-legend-dot" style="background:#ea580c"></span> Loyalty</span>
    <span class="d-flex align-items-center gap-1"><span class="gantt-legend-dot" style="background:#0891b2"></span> VM Deadline</span>
    <span class="d-flex align-items-center gap-1 ms-2">
        <span style="display:inline-block;width:14px;height:10px;background:#f59e0b;opacity:.5;border-radius:2px"></span>
        Tema Periode
    </span>
    <span class="d-flex align-items-center gap-1">
        <span style="display:inline-block;width:12px;height:12px;background:#ef4444;opacity:.4;border-radius:2px"></span> Hari Libur
    </span>
    <span class="d-flex align-items-center gap-1">
        <span style="display:inline-block;width:12px;height:12px;background:#22c55e;opacity:.4;border-radius:2px"></span> Hari Ini
    </span>
</div>

<!-- Chart -->
<div class="card border-0 fade-up" style="background:var(--c-surface-1,#0f172a);animation-delay:.15s">
    <div class="card-body p-3">
        <div id="gantt-spinner" class="text-center py-5">
            <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
            <div class="text-muted mt-2" style="font-size:.8rem">Memuat data…</div>
        </div>
        <div id="gantt-empty" class="text-center py-5 text-muted" style="display:none">
            <i class="bi bi-calendar-x fs-2 d-block mb-2"></i>
            Tidak ada data di tahun ini yang dapat Anda akses.
        </div>
        <div id="gantt"></div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/frappe-gantt@0.6.1/dist/frappe-gantt.min.js"></script>
<script>
(function () {
    const SVG_NS = 'http://www.w3.org/2000/svg';

    const BAR_COLORS = {
        'task-events':      '#2563eb',
        'task-people':      '#16a34a',
        'task-sponsorship': '#9333ea',
        'task-loyalty':     '#ea580c',
        'task-vm':          '#0891b2',
    };
    const GROUP_LABEL = {
        'task-events':      'Events',
        'task-people':      'People',
        'task-sponsorship': 'Sponsorship',
        'task-loyalty':     'Loyalty',
        'task-vm':          'VM',
    };

    let gantt       = null;
    let currentData = null;

    /* ── Load & render ───────────────────────────────────────────── */
    function loadGantt(year, viewMode) {
        document.getElementById('gantt-spinner').style.display = 'block';
        document.getElementById('gantt-empty').style.display   = 'none';
        document.getElementById('gantt').innerHTML              = '';

        fetch(`<?= base_url('gantt/data') ?>?year=${year}`)
            .then(r => r.json())
            .then(data => {
                document.getElementById('gantt-spinner').style.display = 'none';
                currentData = data;
                if (!data.tasks || data.tasks.length === 0) {
                    document.getElementById('gantt-empty').style.display = 'block';
                    return;
                }
                renderGantt(data, viewMode);
            })
            .catch(() => {
                document.getElementById('gantt-spinner').style.display = 'none';
                document.getElementById('gantt-empty').style.display   = 'block';
            });
    }

    function renderGantt(data, viewMode) {
        document.getElementById('gantt').innerHTML = '';

        gantt = new Gantt('#gantt', data.tasks, {
            view_mode:         viewMode || 'Day',
            date_format:       'YYYY-MM-DD',
            header_height:     70,
            bar_height:        22,
            bar_corner_radius: 4,
            arrow_curve:       4,
            padding:           12,
            language:          'en',
            popup_trigger:     'click',
            custom_popup_html: function (task) {
                const s   = new Date(task._start).toLocaleDateString('id-ID', { weekday:'short', day:'2-digit', month:'short', year:'numeric' });
                const e   = new Date(+task._end - 86400000).toLocaleDateString('id-ID', { weekday:'short', day:'2-digit', month:'short', year:'numeric' });
                const col = BAR_COLORS[task.custom_class] || '#555';
                const grp = GROUP_LABEL[task.custom_class] || '';
                return `<div style="padding:8px 10px;font-size:12px;max-width:260px;line-height:1.6">
                    <div style="font-weight:600;margin-bottom:3px">${task.name}</div>
                    <div style="color:#94a3b8;font-size:11px">${s}</div>
                    <div style="color:#94a3b8;font-size:11px">s/d ${e}</div>
                    <div style="margin-top:6px">
                        <span style="background:${col};color:#fff;font-size:10px;padding:2px 7px;border-radius:3px">${grp}</span>
                    </div>
                </div>`;
            },
        });

        setTimeout(() => injectOverlays(data), 200);
    }

    /* ── SVG overlay injection ───────────────────────────────────── */
    function injectOverlays(data) {
        const svg = document.querySelector('#gantt svg.gantt');
        if (!svg || !gantt || !gantt.gantt_start || !gantt.options) return;

        // Remove any overlays from a previous render
        svg.querySelectorAll('.mic-overlay').forEach(el => el.remove());

        const colW    = gantt.options.column_width;
        const step    = gantt.options.step;           // hours per column
        const hdrH    = gantt.options.header_height || 70;
        const svgH    = parseFloat(svg.getAttribute('height') || 400);
        const bodyH   = svgH - hdrH;
        const isDayView = step === 24;

        /* xOffset: calibrate column positions against actual bar DOM positions */
        let xOffset = 0;
        const firstBar = svg.querySelector('.bar-wrapper .bar');
        if (firstBar && data.tasks && data.tasks.length > 0) {
            const barX    = parseFloat(firstBar.getAttribute('x') || 0);
            const rawX    = (new Date(data.tasks[0].start + 'T00:00:00') - gantt.gantt_start) / 3600000 / step * colW;
            xOffset = barX - rawX;
        }

        function dateToX(dateStr) {
            return (new Date(dateStr + 'T00:00:00') - gantt.gantt_start) / 3600000 / step * colW + xOffset;
        }

        /* ── Today highlight (set via JS so CSS can't override) ── */
        const todayEl = svg.querySelector('.today-highlight');
        if (todayEl) {
            todayEl.style.fill    = 'rgba(34,197,94,0.25)';
            todayEl.style.opacity = '1';
        }

        /* ── Weekend column shading (Day view only) ───────────── */
        if (isDayView) {
            const wkGrp = mkGroup(svg, 'before-dates');
            const total = Math.round((gantt.gantt_end - gantt.gantt_start) / 86400000);
            for (let i = 0; i < total; i++) {
                const dow = new Date(gantt.gantt_start.getTime() + i * 86400000).getDay();
                if (dow === 0 || dow === 6)
                    addRect(wkGrp, i * colW + xOffset, hdrH, colW, bodyH, '#94a3b8', 0.08);
            }
        }

        /* ── Month boundary lines ────────────────────────────── */
        if (isDayView) {
            const mbGrp  = mkGroup(svg, 'before-bars');
            const total  = Math.round((gantt.gantt_end - gantt.gantt_start) / 86400000);
            let prevMonth = gantt.gantt_start.getMonth();
            for (let i = 1; i < total; i++) {
                const d = new Date(gantt.gantt_start.getTime() + i * 86400000);
                if (d.getMonth() !== prevMonth) {
                    const x = i * colW + xOffset;
                    const ln = document.createElementNS(SVG_NS, 'line');
                    ln.setAttribute('x1', x); ln.setAttribute('y1', 0);
                    ln.setAttribute('x2', x); ln.setAttribute('y2', svgH);
                    ln.setAttribute('stroke', '#94a3b8');
                    ln.setAttribute('stroke-width', '2');
                    ln.setAttribute('opacity', '0.6');
                    ln.setAttribute('pointer-events', 'none');
                    mbGrp.appendChild(ln);
                    prevMonth = d.getMonth();
                }
            }
        }

        /* ── Theme period blocks ──────────────────────────────── */
        const themeGrp = mkGroup(svg, 'before-bars');
        (data.theme_periods || []).forEach(tp => {
            const nx = dateToX(tp.start_date);
            const ex = dateToX(tp.end_date) + colW;
            const bw = ex - nx;
            if (bw <= 0) return;
            addRect(themeGrp, nx, hdrH, bw, bodyH, '#f59e0b', 0.12, `${tp.nama}: ${tp.start_date} – ${tp.end_date}`);
            if (bw > 24)
                mkText(themeGrp, nx + 4, hdrH + 13, tp.nama, { size: 9, fill: '#f59e0b', opacity: 0.85, anchor: 'start' });
        });

        /* ── Holiday columns ──────────────────────────────────── */
        const hlGrp = mkGroup(svg, 'before-dates');
        const holidaySet = new Set((data.holidays || []).map(h => h.tanggal));
        (data.holidays || []).forEach(h => {
            const x = dateToX(h.tanggal);
            addRect(hlGrp, x, hdrH,  colW, bodyH, '#ef4444', 0.18, `${h.nama} (${h.tanggal})`);
            addRect(hlGrp, x, 0,     colW, hdrH,  '#ef4444', 0.25);
            if (isDayView) {
                const cx = x + colW / 2;
                const ty = hdrH + 6;
                const t  = mkText(hlGrp, cx, ty, h.nama, { size: 8.5, fill: '#ef4444', opacity: 0.8 });
                t.setAttribute('transform', `rotate(90, ${cx}, ${ty})`);
                t.setAttribute('text-anchor', 'start');
            }
        });

        /* ── Day-abbr row + date numbers (Day view only) ─────── */
        if (isDayView) {
            const DAY_ABBR = ['S','M','T','W','T','F','S'];
            const lblGrp   = mkGroup(svg, 'append');

            // Move Frappe Gantt's month labels to top of header
            svg.querySelectorAll('text.upper-text').forEach(el => el.setAttribute('y', 15));

            // Read y of existing lower-text, then remove them all
            const origLower = Array.from(svg.querySelectorAll('text.lower-text'));
            const dateNumY  = origLower[0] ? parseFloat(origLower[0].getAttribute('y') || 60) : 60;
            origLower.forEach(el => el.remove());

            const total = Math.round((gantt.gantt_end - gantt.gantt_start) / 86400000);
            for (let i = 0; i < total; i++) {
                const d      = new Date(gantt.gantt_start.getTime() + i * 86400000);
                const dow    = d.getDay();
                const ds     = d.toISOString().slice(0, 10);
                const isRed  = dow === 0 || dow === 6 || holidaySet.has(ds);
                const cx     = i * colW + xOffset + colW / 2;
                const color  = isRed ? '#ef4444' : '#94a3b8';

                // Date number
                mkText(lblGrp, cx, dateNumY, d.getDate(), { size: 11, fill: color, anchor: 'middle' });
                // Day abbreviation (middle header row)
                mkText(lblGrp, cx, 42, DAY_ABBR[dow], { size: 8, fill: isRed ? '#ef4444' : '#64748b', anchor: 'middle', baseline: 'middle' });
            }
        }
    }

    /* ── Helpers ─────────────────────────────────────────────────── */
    function mkGroup(svg, position) {
        const g = document.createElementNS(SVG_NS, 'g');
        g.setAttribute('class', 'mic-overlay');
        if (position === 'before-dates') {
            const ref = svg.querySelector('.dates') || svg.querySelector('.date');
            ref ? svg.insertBefore(g, ref) : svg.appendChild(g);
        } else if (position === 'before-bars') {
            const ref = svg.querySelector('.bars') || svg.querySelector('.bar');
            ref ? svg.insertBefore(g, ref) : svg.appendChild(g);
        } else {
            svg.appendChild(g);
        }
        return g;
    }

    function addRect(parent, x, y, w, h, fill, opacity, title) {
        if (w <= 0) return;
        const r = document.createElementNS(SVG_NS, 'rect');
        r.setAttribute('x', x); r.setAttribute('y', y);
        r.setAttribute('width', w); r.setAttribute('height', h);
        r.setAttribute('fill', fill); r.setAttribute('opacity', opacity);
        r.setAttribute('pointer-events', 'none');
        if (title) { const t = document.createElementNS(SVG_NS, 'title'); t.textContent = title; r.appendChild(t); }
        parent.appendChild(r);
    }

    function mkText(parent, x, y, content, opts = {}) {
        const t = document.createElementNS(SVG_NS, 'text');
        t.setAttribute('x', x); t.setAttribute('y', y);
        t.setAttribute('text-anchor',      opts.anchor   || 'middle');
        t.setAttribute('font-size',        opts.size     || 11);
        t.setAttribute('font-family',      'inherit');
        t.setAttribute('pointer-events',   'none');
        t.setAttribute('fill',             opts.fill     || '#94a3b8');
        if (opts.opacity)  t.setAttribute('opacity',          opts.opacity);
        if (opts.baseline) t.setAttribute('dominant-baseline', opts.baseline);
        t.textContent = content;
        parent.appendChild(t);
        return t;
    }

    /* ── Controls ────────────────────────────────────────────────── */
    document.getElementById('yearSelect').addEventListener('change', function () {
        loadGantt(this.value, document.getElementById('viewMode').value);
    });
    document.getElementById('viewMode').addEventListener('change', function () {
        if (currentData && currentData.tasks && currentData.tasks.length)
            renderGantt(currentData, this.value);
    });

    loadGantt(<?= $year ?>, 'Day');
})();
</script>
<?= $this->endSection() ?>
