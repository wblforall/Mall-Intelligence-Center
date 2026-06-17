<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<style>
/* ── Tree structure ── */
.oc-wrap { overflow: auto; padding: 24px 24px 48px; height: 74vh; cursor: grab; overscroll-behavior: contain; }
.oc-wrap.panning { cursor: grabbing; user-select: none; }
.oc-wrap.panning .oc-node { pointer-events: none; }
.oc-tree, .oc-tree ul { list-style: none; margin: 0; padding: 0; }
.oc-tree { display: flex; flex-direction: column; align-items: center; }

.oc-tree ul {
    display: flex;
    justify-content: center;
    flex-wrap: nowrap;
    padding-top: 24px;
    position: relative;
}

/* Vertical line down from parent to horizontal bar */
.oc-tree ul::before {
    content: '';
    position: absolute;
    top: 0; left: 50%;
    border-left: 2px solid #cbd5e1;
    width: 0; height: 24px;
}

.oc-tree li {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 0 10px;
    position: relative;
}

/* Horizontal connectors between siblings */
.oc-tree li::before, .oc-tree li::after {
    content: '';
    position: absolute;
    top: 0;
    border-top: 2px solid #cbd5e1;
    width: 50%; height: 24px;
}
.oc-tree li::before { right: 50%; }
.oc-tree li::after  { left: 50%;  }

/* Remove connectors for only/first/last children */
.oc-tree li:first-child::before,
.oc-tree li:last-child::after,
.oc-tree li:only-child::before,
.oc-tree li:only-child::after { display: none; }

/* Pass-through (level kosong) agar node grade lebih rendah turun ke baris grade-nya.
   Hanya garis vertikal di tengah (node-nya transparan). */
.oc-pass { width: 140px; min-height: 58px; position: relative; }
.oc-pass::before {
    content: ''; position: absolute; left: 50%; top: 0; bottom: 0;
    border-left: 2px solid #cbd5e1; width: 0;
}

/* ── Node cards ── */
.oc-node {
    position: relative; z-index: 1;
    border-radius: 10px; border: 2px solid transparent;
    text-align: center;
    transition: box-shadow .2s, transform .2s;
}
.oc-node.has-children { cursor: pointer; }
.oc-node.has-children:hover { box-shadow: 0 4px 16px rgba(0,0,0,.13); transform: translateY(-1px); }

/* Company */
.oc-company {
    background: linear-gradient(135deg, #1e3a5f, #2563eb);
    color: #fff; padding: 14px 32px;
    font-size: 1rem; font-weight: 700; border-radius: 12px; white-space: nowrap;
}
.oc-company .sub { font-size: .72rem; opacity: .75; font-weight: 400; margin-top: 2px; }

/* Division */
.oc-div {
    background: #dbeafe; border-color: #93c5fd;
    padding: 10px 18px; min-width: 150px; white-space: nowrap;
    font-weight: 700; font-size: .82rem; color: #1d4ed8;
}
.oc-div .kode { font-size: .65rem; background: #bfdbfe; border-radius: 4px;
    padding: 1px 6px; margin-top: 3px; display: inline-block; }

/* Department */
.oc-dept {
    background: #f0fdf4; border-color: #86efac;
    padding: 8px 16px; min-width: 140px; white-space: nowrap;
    font-weight: 600; font-size: .78rem; color: #166534;
}

/* Jabatan */
.oc-jab {
    background: var(--bs-body-bg); border-color: #e2e8f0;
    padding: 8px 12px; min-width: 148px; max-width: 190px;
    white-space: normal;
}
.oc-jab .jab-name { font-weight: 600; font-size: .75rem; color: var(--bs-body-color); }
.grade-badge { font-size: .6rem; background: #f1f5f9; color: #64748b;
    border: 1px solid #e2e8f0; border-radius: 4px; padding: 1px 5px;
    margin-bottom: 4px; display: inline-block; }
.oc-jab .emp-list { margin-top: 6px; padding-top: 6px; border-top: 1px solid #f1f5f9; }
.oc-emp { display: flex; align-items: center; gap: 5px; margin-top: 3px; justify-content: center; }
.oc-avatar { width: 20px; height: 20px; border-radius: 50%;
    color: #fff; font-size: .55rem; font-weight: 700;
    display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
.oc-emp-name { font-size: .68rem; color: #475569;
    white-space: normal; overflow-wrap: anywhere; line-height: 1.25; text-align: left; }
.oc-vacant { font-size: .65rem; color: #94a3b8; font-style: italic; margin-top: 4px; }

/* Collapse state */
.oc-tree li.collapsed > ul { display: none; }
.collapse-icon { font-size: .6rem; opacity: .5; margin-left: 5px; }

/* Highlight from search */
.oc-jab.oc-highlight { border-color: #6366f1 !important; background: #eef2ff !important; }

/* Controls bar */
.oc-controls { background: var(--bs-body-bg);
    border-bottom: 1px solid var(--bs-border-color); padding: 8px 16px;
    display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
</style>
<?= $this->endSection() ?>

<?php
// ── Rendering helpers ─────────────────────────────────────────────────────────

function jabNode(array $j, bool $hasChildren = false): string
{
    $emps    = $j['employees'] ?? [];
    $search  = strtolower($j['nama']);
    foreach ($emps as $e) $search .= ' ' . strtolower($e['nama']);

    $extra = $hasChildren
        ? ' has-children" onclick="toggleNode(this.parentElement)'
        : '';

    $html  = '<div class="oc-node oc-jab' . $extra . '" data-search="' . esc($search) . '">';
    $html .= '<div class="grade-badge">G' . (int)$j['grade'] . '</div>';
    $html .= '<div class="jab-name">' . esc($j['nama']) . '</div>';
    if ($hasChildren) $html .= '<i class="bi bi-chevron-down collapse-icon"></i>';
    $html .= '<div class="emp-list">';

    if (empty($emps)) {
        $html .= '<div class="oc-vacant"><i class="bi bi-person-dash me-1"></i>Vacant</div>';
    } else {
        $colors = ['#6366f1','#0ea5e9','#10b981','#f59e0b','#ef4444','#8b5cf6'];
        foreach ($emps as $e) {
            $ini   = implode('', array_map(fn($w) => strtoupper($w[0]),
                            array_slice(array_filter(explode(' ', $e['nama'])), 0, 2)));
            $color = $colors[abs(crc32($e['nama'])) % count($colors)];
            $html .= '<div class="oc-emp">'
                   . '<span class="oc-avatar" style="background:' . $color . '">' . esc($ini) . '</span>'
                   . '<span class="oc-emp-name">' . esc($e['nama']) . '</span>'
                   . '</div>';
        }
    }

    $html .= '</div></div>';
    return $html;
}

/**
 * Render jabatans as a vertical chain, grouping same-grade as horizontal siblings.
 * Falls back when no parent-child relationships exist in the set.
 */
function jabChain(array $jabs, string $innerHtml = ''): string
{
    if (empty($jabs)) return $innerHtml;

    $groups = [];
    foreach ($jabs as $j) {
        $groups[(int)$j['grade']][] = $j;
    }
    ksort($groups);
    $groups = array_values($groups);

    $result = $innerHtml;
    foreach (array_reverse($groups) as $gradeGroup) {
        if (count($gradeGroup) === 1) {
            $j      = $gradeGroup[0];
            $result = '<ul><li>' . jabNode($j, $result !== '') . $result . '</li></ul>';
        } else {
            $liItems = '';
            foreach ($gradeGroup as $j) {
                $liItems .= '<li>' . jabNode($j, $result !== '') . $result . '</li>';
            }
            $result = '<ul>' . $liItems . '</ul>';
        }
    }

    return $result;
}

/**
 * Recursively render one node and its children (via parent_jabatan_id).
 * Children are grade-chained among themselves; $nextBranch continues at leaf nodes.
 */
function jabTreeRender(array $j, array $childrenOf, string $nextBranch): string
{
    $jid  = (int)$j['id'];
    $kids = $childrenOf[$jid] ?? [];

    // Render anak sebagai sibling langsung sesuai parent_jabatan_id (bukan grade-chain),
    // supaya anak grade rendah yang langsung di bawah node ini tidak ter-duplikasi
    // ke tiap sibling grade lebih tinggi. $nextBranch ditempel di node ini.
    $inner = '';
    if (! empty($kids)) {
        $liItems = '';
        foreach ($kids as $kid) {
            $sub = jabTreeRender($kid, $childrenOf, '');
            // Sisipkan level kosong (pass-through) bila grade anak lompat lebih dari 1
            // dari parent, supaya kedalaman = grade (G8 lurus dengan G8).
            $gap = (int) $kid['grade'] - (int) $j['grade'] - 1;
            for ($s = 0; $s < $gap; $s++) {
                $sub = '<div class="oc-pass"></div><ul><li>' . $sub . '</li></ul>';
            }
            $liItems .= '<li>' . $sub . '</li>';
        }
        $inner = '<ul>' . $liItems . '</ul>';
    }
    return jabNode($j, $inner !== '' || $nextBranch !== '') . $inner . $nextBranch;
}

/**
 * Render jabatans using parent_jabatan_id relationships.
 * Roots (no parent in scope) are grade-chained vertically like jabChain;
 * children via parent_jabatan_id appear inline within each node's subtree.
 */
function jabTree(array $jabs, string $innerHtml = ''): string
{
    if (empty($jabs)) return $innerHtml;

    $byId       = array_column($jabs, null, 'id');
    $childrenOf = [];
    $roots      = [];

    foreach ($jabs as $j) {
        $pid = (int)($j['parent_jabatan_id'] ?? 0);
        if ($pid && isset($byId[$pid])) {
            $childrenOf[$pid][] = $j;
        } else {
            $roots[] = $j;
        }
    }

    // Grade-chain roots vertically (same-grade roots become horizontal siblings)
    $rootsByGrade = [];
    foreach ($roots as $j) $rootsByGrade[(int)$j['grade']][] = $j;
    ksort($rootsByGrade);

    $result = $innerHtml;
    foreach (array_reverse($rootsByGrade) as $gradeGroup) {
        $liItems = '';
        foreach ($gradeGroup as $j) {
            $liItems .= '<li>' . jabTreeRender($j, $childrenOf, $result) . '</li>';
        }
        $result = '<ul>' . $liItems . '</ul>';
    }

    return $result;
}

/**
 * Choose grade-chain or parent-tree depending on whether parent links exist in this scope.
 */
function jabRender(array $jabs, string $innerHtml = ''): string
{
    if (empty($jabs)) return $innerHtml;

    $ids = array_column($jabs, 'id');
    foreach ($jabs as $j) {
        if (!empty($j['parent_jabatan_id']) && in_array((int)$j['parent_jabatan_id'], $ids)) {
            return jabTree($jabs, $innerHtml);
        }
    }
    return jabChain($jabs, $innerHtml);
}

function deptLi(array $dept): string
{
    $hasJ  = ! empty($dept['jabatans']);
    $click = $hasJ ? ' onclick="toggleNode(this.parentElement)"' : '';
    $icon  = $hasJ ? '<i class="bi bi-chevron-down collapse-icon"></i>' : '';

    $html  = '<li>';
    $html .= '<div class="oc-node oc-dept' . ($hasJ ? ' has-children' : '') . '"' . $click . '>';
    $html .= '<i class="bi bi-diagram-2 me-1"></i>' . esc($dept['name']) . $icon;
    $html .= '</div>';
    if ($hasJ) $html .= jabRender($dept['jabatans']);
    $html .= '</li>';
    return $html;
}

function divLi(array $div): string
{
    $hasDepts = ! empty($div['departments']);
    $hasDJabs = ! empty($div['jabatans']);
    $hasAny   = $hasDepts || $hasDJabs;

    $click = $hasAny ? ' onclick="toggleNode(this.parentElement)"' : '';
    $icon  = $hasAny ? '<i class="bi bi-chevron-down collapse-icon"></i>' : '';
    $kode  = $div['kode'] ? '<div class="kode">' . esc($div['kode']) . '</div>' : '';

    $html  = '<li>';
    $html .= '<div class="oc-node oc-div' . ($hasAny ? ' has-children' : '') . '"' . $click . '>';
    $html .= '<i class="bi bi-building me-1"></i>' . esc($div['nama']) . $kode . $icon;
    $html .= '</div>';

    if ($hasAny) {
        // Departments branch after any division-level jabatan chain
        $deptsHtml = '';
        foreach ($div['departments'] as $dept) $deptsHtml .= deptLi($dept);
        $deptsBranch = $deptsHtml ? '<ul>' . $deptsHtml . '</ul>' : '';
        $html .= jabRender($div['jabatans'], $deptsBranch);
    }

    $html .= '</li>';
    return $html;
}
?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-diagram-3-fill me-2"></i>Struktur Organisasi</h4>
        <small class="text-muted">PT. Wulandari Bangun Laksana Tbk.</small>
    </div>
    <div class="d-flex gap-2 text-center small">
        <div class="px-3 border-end">
            <div class="fw-bold"><?= $stats['divisions'] ?></div>
            <div class="text-muted">Divisi</div>
        </div>
        <div class="px-3 border-end">
            <div class="fw-bold"><?= $stats['depts'] ?></div>
            <div class="text-muted">Dept</div>
        </div>
        <div class="px-3 border-end">
            <div class="fw-bold"><?= $stats['jabatans'] ?></div>
            <div class="text-muted">Jabatan</div>
        </div>
        <div class="px-3">
            <div class="fw-bold text-primary"><?= $stats['employees'] ?></div>
            <div class="text-muted">Karyawan Aktif</div>
        </div>
    </div>
</div>

<!-- Controls -->
<div class="oc-controls rounded-top border">
    <button class="btn btn-sm btn-outline-secondary" onclick="zoom(0.15)"><i class="bi bi-plus-lg"></i></button>
    <button class="btn btn-sm btn-outline-secondary" onclick="zoom(-0.15)"><i class="bi bi-dash-lg"></i></button>
    <button class="btn btn-sm btn-outline-secondary" onclick="resetZoom()">
        <i class="bi bi-aspect-ratio me-1"></i>Reset
    </button>
    <div class="vr mx-1"></div>
    <button class="btn btn-sm btn-outline-secondary" onclick="expandAll()">
        <i class="bi bi-arrows-expand me-1"></i>Expand All
    </button>
    <button class="btn btn-sm btn-outline-secondary" onclick="collapseAll()">
        <i class="bi bi-arrows-collapse me-1"></i>Collapse
    </button>
    <div class="vr mx-1"></div>
    <button class="btn btn-sm btn-outline-danger" id="btnPdf" onclick="exportPDF()">
        <i class="bi bi-filetype-pdf me-1"></i>Export PDF
    </button>
    <div class="vr mx-1"></div>
    <input type="text" id="searchBox" class="form-control form-control-sm" style="max-width:200px"
           placeholder="Cari nama / jabatan...">
    <span class="text-muted small ms-auto" id="zoomLabel">100%</span>
</div>

<!-- Chart -->
<div class="card border-top-0 rounded-top-0">
<div class="oc-wrap">
<div id="viewport" style="transform-origin:top center; transition:transform .2s; display:inline-block; min-width:100%">

<ul class="oc-tree">
<li>

<div class="oc-node oc-company has-children" onclick="toggleNode(this.parentElement)">
    PT. Wulandari Bangun Laksana Tbk.
    <div class="sub">eWalk &amp; Pentacity Mall</div>
    <i class="bi bi-chevron-down collapse-icon"></i>
</div>

<?php
// Pisahkan top-jabatan: spine = Direktur/GM (grade ≤ 2), company-level = Sekretaris dkk
// (grade ≥ 3) yang ditempatkan sebagai sibling divisi di bawah GM (setara deputy).
$spine = $companyJabs = [];
foreach ($topJabs as $tj) {
    if ((int) $tj['grade'] <= 2) $spine[] = $tj; else $companyJabs[] = $tj;
}
// Build branch: divisi + dept tanpa divisi + jabatan company-level (Sekretaris)
$branchHtml = '';
foreach ($companyJabs as $cj)  $branchHtml .= '<li>' . jabNode($cj) . '</li>';
foreach ($divisions  as $div)  $branchHtml .= divLi($div);
foreach ($noDivDepts as $dept) $branchHtml .= deptLi($dept);
$branch = $branchHtml ? '<ul>' . $branchHtml . '</ul>' : '';

echo jabRender($spine ?: $topJabs, $branch);
?>

</li>
</ul>

</div>
</div>
</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
const ocWrap = document.querySelector('.oc-wrap');
const ocView = document.getElementById('viewport');
let scale = 1;

// ── Export PDF (tangkap seluruh pohon → 1 halaman besar) ─────────────
async function exportPDF() {
    const btn = document.getElementById('btnPdf');
    const orig = btn.innerHTML;
    btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Membuat…';
    try {
        expandAll();
        const prevScale = scale, prevH = ocWrap.style.height, prevOv = ocWrap.style.overflow;
        scale = 1; applyZoom();
        // lepas batas tinggi/scroll supaya seluruh pohon tertangkap
        ocWrap.style.height = 'auto'; ocWrap.style.overflow = 'visible';
        await new Promise(r => setTimeout(r, 250));
        const canvas = await html2canvas(ocView, { backgroundColor: '#ffffff', scale: 1.5, windowWidth: ocView.scrollWidth });
        ocWrap.style.height = prevH; ocWrap.style.overflow = prevOv;
        scale = prevScale; applyZoom();

        const { jsPDF } = window.jspdf;
        const w = canvas.width, h = canvas.height;
        const pdf = new jsPDF({ orientation: w >= h ? 'l' : 'p', unit: 'px', format: [w, h], compress: true });
        pdf.addImage(canvas.toDataURL('image/jpeg', 0.92), 'JPEG', 0, 0, w, h);
        pdf.save('struktur-organisasi.pdf');
    } catch (err) {
        alert('Gagal membuat PDF: ' + (err.message || err) + '\\nPohon mungkin terlalu besar — coba collapse sebagian dulu.');
    } finally {
        btn.disabled = false; btn.innerHTML = orig;
    }
}

function applyZoom() {
    ocView.style.transform = scale === 1 ? '' : `scale(${scale})`;
    document.getElementById('zoomLabel').textContent = Math.round(scale * 100) + '%';
}
function zoom(delta) {
    scale = Math.min(2.5, Math.max(0.25, Math.round((scale + delta) * 100) / 100));
    applyZoom();
}
function resetZoom() { scale = 1; applyZoom(); ocWrap.scrollTop = 0; }

// Default: zoom-out otomatis agar seluruh lebar pohon terlihat
function fitToView() {
    const contentW = ocView.scrollWidth;
    const wrapW = ocWrap.clientWidth - 48;
    if (contentW > 0) {
        scale = Math.min(1, Math.max(0.2, Math.round((wrapW / contentW) * 100) / 100));
        applyZoom();
        ocWrap.scrollLeft = (ocView.scrollWidth * scale - ocWrap.clientWidth) / 2;
    }
}
window.addEventListener('load', fitToView);

// ── Drag-to-pan (mouse) — klik biasa tetap bisa collapse node ─────────
let down = null, dragged = false;
ocWrap.addEventListener('mousedown', e => {
    if (e.button !== 0 || e.target.closest('button, input, a')) return;
    down = { x: e.clientX, y: e.clientY, sl: ocWrap.scrollLeft, st: ocWrap.scrollTop };
    dragged = false;
});
window.addEventListener('mousemove', e => {
    if (! down) return;
    const dx = e.clientX - down.x, dy = e.clientY - down.y;
    if (! dragged && Math.hypot(dx, dy) > 4) { dragged = true; ocWrap.classList.add('panning'); }
    if (dragged) { ocWrap.scrollLeft = down.sl - dx; ocWrap.scrollTop = down.st - dy; }
});
window.addEventListener('mouseup', () => {
    if (dragged) ocWrap.addEventListener('click', ev => ev.stopPropagation(), { capture: true, once: true });
    down = null; ocWrap.classList.remove('panning');
});

// ── Zoom: Ctrl/⌘ + scroll ────────────────────────────────────────────
ocWrap.addEventListener('wheel', e => {
    if (e.ctrlKey || e.metaKey) { e.preventDefault(); zoom(e.deltaY < 0 ? 0.1 : -0.1); }
}, { passive: false });

// ── Touch: 1 jari geser, 2 jari pinch-zoom ───────────────────────────
let tPan = null, pinch = null;
const dist = t => Math.hypot(t[0].clientX - t[1].clientX, t[0].clientY - t[1].clientY);
ocWrap.addEventListener('touchstart', e => {
    if (e.touches.length === 1) {
        const t = e.touches[0];
        if (t.target.closest('button, input, a')) return;
        tPan = { x: t.clientX, y: t.clientY, sl: ocWrap.scrollLeft, st: ocWrap.scrollTop };
    } else if (e.touches.length === 2) { pinch = { d: dist(e.touches), s: scale }; }
}, { passive: true });
ocWrap.addEventListener('touchmove', e => {
    if (e.touches.length === 2 && pinch) {
        e.preventDefault();
        scale = Math.min(2.5, Math.max(0.25, Math.round(pinch.s * dist(e.touches) / pinch.d * 100) / 100));
        applyZoom();
    } else if (e.touches.length === 1 && tPan) {
        const t = e.touches[0];
        ocWrap.scrollLeft = tPan.sl - (t.clientX - tPan.x);
        ocWrap.scrollTop  = tPan.st - (t.clientY - tPan.y);
    }
}, { passive: false });
ocWrap.addEventListener('touchend', e => { if (e.touches.length === 0) { tPan = null; pinch = null; } });

function toggleNode(li) {
    li.classList.toggle('collapsed');
    const icon = li.querySelector(':scope > .oc-node .collapse-icon');
    if (icon) icon.className = li.classList.contains('collapsed')
        ? 'bi bi-chevron-right collapse-icon'
        : 'bi bi-chevron-down collapse-icon';
}

function expandAll() {
    document.querySelectorAll('.oc-tree li.collapsed').forEach(li => {
        li.classList.remove('collapsed');
        const icon = li.querySelector(':scope > .oc-node .collapse-icon');
        if (icon) icon.className = 'bi bi-chevron-down collapse-icon';
    });
}

function collapseAll() {
    document.querySelectorAll('.oc-tree li').forEach(li => {
        if (li.querySelector('ul')) {
            li.classList.add('collapsed');
            const icon = li.querySelector(':scope > .oc-node .collapse-icon');
            if (icon) icon.className = 'bi bi-chevron-right collapse-icon';
        }
    });
}

// Search
document.getElementById('searchBox').addEventListener('input', function () {
    const q = this.value.trim().toLowerCase();
    document.querySelectorAll('.oc-jab').forEach(node => {
        node.classList.remove('oc-highlight');
        if (!q) return;
        if ((node.dataset.search || '').includes(q)) {
            node.classList.add('oc-highlight');
            // Expand ancestors
            let li = node.closest('li');
            while (li) {
                li.classList.remove('collapsed');
                const icon = li.querySelector(':scope > .oc-node .collapse-icon');
                if (icon) icon.className = 'bi bi-chevron-down collapse-icon';
                li = li.parentElement?.closest('li');
            }
        }
    });
});
</script>
<?= $this->endSection() ?>
