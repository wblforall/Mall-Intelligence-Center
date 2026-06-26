<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$bulanNama = ['','Januari','Februari','Maret','April','Mei','Juni',
              'Juli','Agustus','September','Oktober','November','Desember'];

// Full date array for the 3-month window
$allDates = [];
$ptr = strtotime($tglMulai);
$end = strtotime($tglSelesai);
while ($ptr <= $end) {
    $allDates[] = date('Y-m-d', $ptr);
    $ptr = strtotime('+1 day', $ptr);
}
$totalDays = count($allDates);

// Group by month for 2-row header
$monthGroups = [];
foreach ($allDates as $tgl) {
    $mk = date('Y-m', strtotime($tgl));
    $monthGroups[$mk][] = $tgl;
}

// Pre-compute last date of each month (for border)
$monthLastDates = [];
foreach ($monthGroups as $mk => $mDates) {
    $monthLastDates[end($mDates)] = true;
}

// Usage map: spot_id_slot -> [usage,...]
$usageMap = [];
foreach ($usages as $u) {
    $key = $u['spot_id'].'_'.($u['slot_number'] ?? '0');
    $usageMap[$key][] = $u;
}

// Row list
$rows = [];
foreach ($spots as $spot) {
    if ($filterTipe && $spot['tipe'] !== $filterTipe) continue;
    if ($spot['tipe'] === 'digital') {
        $rows[] = ['type'=>'header','spot'=>$spot];
        for ($sl = 1; $sl <= (int)$spot['total_slots']; $sl++) {
            $rows[] = ['type'=>'slot','spot'=>$spot,'slot'=>$sl];
        }
    } else {
        $rows[] = ['type'=>'single','spot'=>$spot,'slot'=>null];
    }
}

// Last slot index per digital group
$lastSlotIdx = [];
foreach ($rows as $i => $row) {
    if ($row['type'] === 'slot') $lastSlotIdx[$row['spot']['id']] = $i;
}

$statusColor = ['pending'=>'#f59e0b','approved'=>'#16a34a','done'=>'#64748b','rejected'=>'#dc2626'];
$tipeLabel   = ['t_banner'=>'T-Banner','hanging'=>'Hanging','sticker_lift'=>'Sticker Lift','totem_stainless'=>'Totem Stainless','digital'=>'Digital'];
$today       = date('Y-m-d');

// Fixed column widths
$colW       = 28;          // px per day column
$labelW     = 190;         // px for label column
$tableWidth = $labelW + ($totalDays * $colW);

// colspan bar renderer
$renderDays = function(array $rowUsages, int $rowH)
    use ($allDates, $totalDays, $today, $tglMulai, $tglSelesai, $statusColor, $monthLastDates, $colW) {
    $idx = 0;
    while ($idx < $totalDays) {
        $tgl = $allDates[$idx];
        $dow = (int)date('N', strtotime($tgl));
        $cc  = ($tgl === $today) ? 'col-today' : ($dow >= 6 ? 'col-weekend' : '');
        $ml  = isset($monthLastDates[$tgl]) ? ' month-last' : '';

        // Find usage whose clamped start == this date
        $hit  = null;
        $span = 1;
        foreach ($rowUsages as $u) {
            $sDate = ($u['tanggal_mulai'] < $tglMulai) ? $tglMulai : $u['tanggal_mulai'];
            $eDate = ($u['tanggal_selesai'] > $tglSelesai) ? $tglSelesai : $u['tanggal_selesai'];
            if ($sDate === $tgl) {
                $hit  = $u;
                $span = 0;
                for ($j = $idx; $j < $totalDays && $allDates[$j] <= $eDate; $j++) $span++;
                $span = max(1, $span);
                break;
            }
        }

        if ($hit) {
            $color = $statusColor[$hit['status']] ?? '#94a3b8';
            // For the bar td, check if the last day of the span is a month-last date
            $lastDay = $allDates[min($idx + $span - 1, $totalDays - 1)];
            $barML   = isset($monthLastDates[$lastDay]) ? ' month-last' : '';
            echo '<td colspan="'.$span.'" class="p-0'.$barML.'" style="height:'.$rowH.'px">';
            echo '<div class="gbar" style="background:'.$color.'" onclick="showDetail('.$hit['id'].')"'
                .' title="'.esc($hit['nama_materi']).' ('.date('d/m', strtotime($hit['tanggal_mulai'])).'–'.date('d/m', strtotime($hit['tanggal_selesai'])).')">';
            echo '<span class="gbar-label">'.esc($hit['nama_materi']).'</span>';
            echo '</div></td>';
            $idx += $span;
        } else {
            echo '<td class="p-0 '.$cc.$ml.'" style="height:'.$rowH.'px"></td>';
            $idx++;
        }
    }
};
?>

<style>
/* ── wrapper ── */
.gantt-wrap { overflow:auto; max-height:calc(100vh - 260px); border-radius:10px 10px 0 0; border:1px solid #cbd5e1; border-bottom:none; box-shadow:0 2px 8px rgba(0,0,0,.08); }
.gantt-wrap::-webkit-scrollbar { width:8px; height:0; }
.gantt-wrap::-webkit-scrollbar-track { background:#e2e8f0; }
.gantt-wrap::-webkit-scrollbar-thumb { background:#94a3b8; border-radius:4px; }
.gantt-wrap::-webkit-scrollbar-thumb:hover { background:#64748b; }
.gantt-wrap { scrollbar-width:thin; scrollbar-color:#94a3b8 #e2e8f0; }

/* ── table: explicit width ensures columns never compress ── */
.gantt-table { border-collapse:collapse; font-size:.72rem; table-layout:fixed; }
.gantt-table th, .gantt-table td { border:1px solid #e2e8f0; }

/* ── thead ── */
.gantt-table thead th             { background:#1e293b !important; color:#f1f5f9 !important; padding:3px 0; position:sticky; top:0; z-index:3; }
.gantt-table thead th.th-label    { background:#0f172a !important; font-size:.73rem; vertical-align:middle; }
.gantt-table thead th.th-month    { font-size:.7rem; font-weight:700; }
.gantt-table thead th.col-today   { background:#16a34a !important; color:#fff !important; }
.gantt-table thead th.col-weekend { background:#991b1b !important; }
.gantt-table thead th.month-last  { border-right:2px solid #4b5e7a !important; }

/* ── tbody: force white ── */
.gantt-table tbody td             { background:#fff !important; vertical-align:middle; }
.gantt-table tbody td.td-label    { background:#f8fafc !important; }
.gantt-table tbody td.col-weekend { background:#fee2e2 !important; }
.gantt-table tbody td.col-today   { background:#dcfce7 !important; }
.gantt-table tbody td.month-last  { border-right:2px solid #94a3b8 !important; }

/* ── group header row (digital) ── */
.gantt-table tr.row-header td          { background:#edf2f7 !important; border-top:2px solid #94a3b8 !important; }
.gantt-table tr.row-header td.td-label { background:#dde3ea !important; }
.gantt-table tr.row-header td.col-today  { background:#bbf7d0 !important; }
.gantt-table tr.row-header td.col-weekend{ background:#fecaca !important; }

/* ── single row top border ── */
.gantt-table tr.row-single td { border-top:2px solid #94a3b8 !important; }

/* ── last slot bottom border ── */
.gantt-table tr.row-last-slot td { border-bottom:2px solid #94a3b8 !important; }

/* ── sticky label column ── */
.td-label                      { position:sticky; left:0; z-index:2; }
.gantt-table thead th.th-label { position:sticky; left:0; top:0; z-index:6; }

/* ── bar ── */
.gbar {
    display:block; height:20px; margin:5px 2px; border-radius:4px;
    cursor:pointer; position:relative; transition:filter .15s;
}
.gbar:hover { filter:brightness(1.1); }
.gbar-label {
    position:absolute; left:7px; right:7px; top:3px;
    font-size:.62rem; color:#fff; white-space:nowrap;
    overflow:hidden; text-overflow:ellipsis;
    line-height:14px; font-weight:600;
    text-shadow:0 1px 2px rgba(0,0,0,.45);
    pointer-events:none;
}

/* ── modal: force light palette regardless of data-bs-theme ── */
#gModal .modal-content  { background:#ffffff !important; border:none !important; box-shadow:0 12px 40px rgba(0,0,0,.22) !important; }
#gModal .modal-header   { background:#f8fafc !important; border-bottom:1px solid #e2e8f0 !important; }
#gModal .modal-title    { color:#111827 !important; font-size:.95rem !important; }
#gModal .modal-body     { background:#ffffff !important; color:#111827 !important; }
#gModal .modal-footer   { background:#f8fafc !important; border-top:1px solid #e2e8f0 !important; }
#gModal .btn-close      { filter:none !important; opacity:.6; }
#gModal .gm-label       { color:#6b7280 !important; }
#gModal .gm-value       { color:#111827 !important; font-weight:500; }
</style>

<!-- Title -->
<div class="d-flex align-items-center justify-content-between mb-3">
    <h4 class="fw-bold mb-0">
        <i class="bi bi-bar-chart-gantt me-2"></i>Gantt Media Promo
        <small class="fw-normal ms-2" style="font-size:.74rem;opacity:.6">
            <?= date('d M Y', strtotime($tglMulai)) ?> – <?= date('d M Y', strtotime($tglSelesai)) ?>
        </small>
    </h4>
    <a href="<?= base_url('creative/media-promo') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Kembali
    </a>
</div>

<!-- Filter -->
<div class="card mb-3">
<div class="card-body py-2">
<form method="GET" action="" class="d-flex align-items-center gap-3 flex-wrap">
    <select name="tipe" class="form-select form-select-sm" style="width:160px" onchange="this.form.submit()">
        <option value="">Semua Tipe</option>
        <option value="t_banner"       <?= $filterTipe==='t_banner'       ?'selected':''?>>T-Banner</option>
        <option value="hanging"        <?= $filterTipe==='hanging'        ?'selected':''?>>Hanging Banner</option>
        <option value="sticker_lift"   <?= $filterTipe==='sticker_lift'   ?'selected':''?>>Sticker Lift</option>
        <option value="totem_stainless"<?= $filterTipe==='totem_stainless'?'selected':''?>>Totem Stainless</option>
        <option value="digital"        <?= $filterTipe==='digital'        ?'selected':''?>>Digital</option>
    </select>
    <select name="dept" class="form-select form-select-sm" style="width:180px" onchange="this.form.submit()">
        <option value="">Semua Dept</option>
        <?php foreach ($depts as $d): ?>
        <option value="<?= esc($d) ?>" <?= $filterDept===$d?'selected':''?>><?= esc($d) ?></option>
        <?php endforeach; ?>
    </select>
    <span class="text-muted small">Menampilkan 3 bulan ke depan</span>
</form>
</div>
</div>

<!-- Legend -->
<div class="d-flex flex-wrap gap-3 mb-2" style="font-size:.8rem">
    <?php foreach (['pending'=>'Pending','approved'=>'Approved','done'=>'Done'] as $s=>$lbl): ?>
    <span class="d-flex align-items-center gap-1">
        <span style="width:13px;height:13px;border-radius:3px;background:<?= $statusColor[$s] ?>;display:inline-block"></span><?= $lbl ?>
    </span>
    <?php endforeach; ?>
    <span class="d-flex align-items-center gap-1 ms-1 text-muted">
        <span style="width:13px;height:13px;border-radius:3px;background:#fee2e2;border:1px solid #fca5a5;display:inline-block"></span>Akhir pekan
    </span>
    <span class="d-flex align-items-center gap-1 text-muted">
        <span style="width:13px;height:13px;border-radius:3px;background:#dcfce7;border:1px solid #86efac;display:inline-block"></span>Hari ini
    </span>
</div>

<?php if (empty($rows)): ?>
<div class="card"><div class="card-body text-center text-muted py-5">Tidak ada titik media yang sesuai filter.</div></div>
<?php else: ?>

<div class="gantt-wrap" id="ganttWrap">
<table class="gantt-table" style="width:<?= $tableWidth ?>px">
<colgroup>
    <col style="width:<?= $labelW ?>px">
    <?php for ($i = 0; $i < $totalDays; $i++): ?><col style="width:<?= $colW ?>px"><?php endfor; ?>
</colgroup>
<thead>
<!-- Row 1: Month names -->
<tr>
    <th class="th-label text-center" rowspan="2" style="vertical-align:middle">Titik / Slot</th>
    <?php foreach ($monthGroups as $mk => $mDates):
        [$mY, $mM] = explode('-', $mk);
    ?>
    <th class="th-month text-center month-last" colspan="<?= count($mDates) ?>">
        <?= $bulanNama[(int)$mM] ?> <?= $mY ?>
    </th>
    <?php endforeach; ?>
</tr>
<!-- Row 2: Day numbers -->
<tr>
    <?php foreach ($allDates as $tgl):
        $d   = (int)date('j', strtotime($tgl));
        $dow = (int)date('N', strtotime($tgl));
        $cc  = ($tgl === $today) ? 'col-today' : ($dow >= 6 ? 'col-weekend' : '');
        $ml  = isset($monthLastDates[$tgl]) ? ' month-last' : '';
    ?>
    <th class="text-center <?= $cc.$ml ?>" style="font-size:.6rem;line-height:1.2;padding:2px 0">
        <div><?= $d ?></div>
        <div style="opacity:.65"><?= ['','S','S','R','K','J','S','M'][$dow] ?></div>
    </th>
    <?php endforeach; ?>
</tr>
</thead>

<tbody>
<?php foreach ($rows as $i => $row):
    $spot      = $row['spot'];
    $type      = $row['type'];
    $slot      = $row['slot'] ?? null;
    $key       = $spot['id'].'_'.($slot ?? '0');
    $rowUsages = $usageMap[$key] ?? [];
    $isLastSlot = ($type==='slot') && isset($lastSlotIdx[$spot['id']]) && $lastSlotIdx[$spot['id']]===$i;
?>

<?php if ($type === 'header'): ?>
<tr class="row-header">
    <td class="td-label px-2 py-1" style="border-left:3px solid #475569">
        <div class="fw-bold text-truncate" style="font-size:.74rem;color:#1e293b" title="<?= esc($spot['nama']) ?>">
            <?= esc($spot['kode']) ?> <?= esc($spot['nama']) ?>
        </div>
        <div style="font-size:.62rem;color:#64748b">
            <?= $tipeLabel[$spot['tipe']] ?? $spot['tipe'] ?><?= $spot['area'] ? ' · '.esc($spot['area']) : '' ?>
            &nbsp;·&nbsp;<span style="color:#6366f1;font-weight:600"><?= (int)$spot['total_slots'] ?> slot</span>
        </div>
    </td>
    <?php foreach ($allDates as $tgl):
        $dow = (int)date('N', strtotime($tgl));
        $cc  = ($tgl===$today) ? 'col-today' : ($dow>=6 ? 'col-weekend' : '');
        $ml  = isset($monthLastDates[$tgl]) ? ' month-last' : '';
    ?><td class="p-0 <?= $cc.$ml ?>" style="height:30px"></td><?php endforeach; ?>
</tr>

<?php elseif ($type === 'slot'): ?>
<tr class="row-slot<?= $isLastSlot ? ' row-last-slot' : '' ?>">
    <td class="td-label px-2 py-0" style="border-left:3px solid #dde3ea">
        <span style="font-size:.65rem;color:#64748b;padding-left:6px">– Slot <?= $slot ?></span>
    </td>
    <?php $renderDays($rowUsages, 30); ?>
</tr>

<?php else: // single ?>
<tr class="row-single">
    <td class="td-label px-2 py-1" style="border-left:3px solid #6366f1">
        <div class="fw-bold text-truncate" style="font-size:.74rem;color:#1e293b" title="<?= esc($spot['nama']) ?>">
            <?= esc($spot['kode']) ?> <?= esc($spot['nama']) ?>
        </div>
        <div style="font-size:.62rem;color:#64748b">
            <?= $tipeLabel[$spot['tipe']] ?? $spot['tipe'] ?><?= $spot['area'] ? ' · '.esc($spot['area']) : '' ?>
        </div>
    </td>
    <?php $renderDays($rowUsages, 34); ?>
</tr>
<?php endif; ?>

<?php endforeach; ?>
</tbody>
</table>
</div>

<!-- Scroll bar bawah -->
<div id="ganttScrollBar" style="overflow-x:scroll;overflow-y:hidden;height:18px;border-radius:0 0 10px 10px;border:1px solid #cbd5e1;border-top:none">
    <div id="ganttScrollInner" style="height:1px"></div>
</div>
<style>
#ganttScrollBar::-webkit-scrollbar        { height:12px; }
#ganttScrollBar::-webkit-scrollbar-track  { background:#e2e8f0; border-radius:6px; }
#ganttScrollBar::-webkit-scrollbar-thumb  { background:#94a3b8; border-radius:6px; }
#ganttScrollBar::-webkit-scrollbar-thumb:hover { background:#64748b; }
#ganttScrollBar { scrollbar-width:auto; scrollbar-color:#94a3b8 #e2e8f0; }
</style>

<?php endif; ?>

<!-- Modal — ID gModal, CSS override di atas sudah force-light -->
<div class="modal fade" id="gModal" tabindex="-1">
<div class="modal-dialog" style="max-width:440px">
<div class="modal-content">
    <div class="modal-header">
        <h6 class="modal-title fw-bold">Detail Penggunaan</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body" id="gModalBody" style="padding:1.1rem 1.25rem"></div>
    <?php if ($canApprove): ?>
    <div class="modal-footer" id="gModalFooter"></div>
    <?php endif; ?>
</div></div></div>

<script>
const BASE       = '<?= base_url() ?>';
const canApprove = <?= $canApprove ? 'true' : 'false' ?>;
const USAGES     = <?= json_encode(array_column($usages, null, 'id'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
const sBadge = {pending:'warning text-dark', approved:'success', done:'secondary', rejected:'danger'};
const sLabel = {pending:'Pending', approved:'Approved', done:'Done', rejected:'Rejected'};

const esc = (s) => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
function showDetail(id) {
    const u = USAGES[id];
    if (!u) return;

    const row = (k, v, danger) =>
        `<div style="display:flex;gap:12px;padding:7px 0;border-bottom:1px solid #f1f5f9">
            <div class="gm-label" style="min-width:70px;font-size:.78rem;padding-top:1px">${k}</div>
            <div class="gm-value" style="flex:1;font-size:.84rem;word-break:break-word;${danger?'color:#dc2626!important':''}">${v}</div>
        </div>`;

    let html = `<div style="margin-bottom:14px">
        <span class="badge bg-${sBadge[u.status]||'secondary'}" style="font-size:.8rem;padding:5px 12px">${sLabel[u.status]||u.status}</span>
    </div>`;
    html += row('Titik',   esc(u.spot_kode||'')+(u.spot_nama?' — '+esc(u.spot_nama):''));
    if (u.slot_number) html += row('Slot', 'Slot '+esc(u.slot_number));
    html += row('Dept',    esc(u.dept||'–'));
    html += row('Materi',  '<strong>'+esc(u.nama_materi||'–')+'</strong>');
    if (u.deskripsi_materi) html += row('Keterangan', esc(u.deskripsi_materi));
    html += row('Periode', esc(u.tanggal_mulai||'')+(u.tanggal_selesai?' s/d '+esc(u.tanggal_selesai):''));
    if (u.catatan_pemohon)  html += row('Catatan',   esc(u.catatan_pemohon));
    if (u.rejection_reason) html += row('Ditolak',   esc(u.rejection_reason), true);

    document.getElementById('gModalBody').innerHTML = html;

    const footer = document.getElementById('gModalFooter');
    if (footer) {
        footer.innerHTML = (canApprove && u.status === 'pending')
            ? `<button class="btn btn-sm btn-success" onclick="qApprove(${u.id})"><i class="bi bi-check-lg me-1"></i>Approve</button>`
            + `<button class="btn btn-sm btn-danger ms-1" onclick="qReject(${u.id})"><i class="bi bi-x-lg me-1"></i>Reject</button>`
            : '';
    }
    new bootstrap.Modal(document.getElementById('gModal')).show();
}

function qApprove(id) {
    if (!confirm('Approve request ini?')) return;
    const f = document.createElement('form');
    f.method='POST'; f.action=BASE+'creative/media-promo/usage/'+id+'/approve';
    f.innerHTML='<input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">';
    document.body.appendChild(f); f.submit();
}
function qReject(id) {
    const r = prompt('Alasan penolakan:'); if (!r) return;
    const f = document.createElement('form');
    f.method='POST'; f.action=BASE+'creative/media-promo/usage/'+id+'/reject';
    f.innerHTML=`<input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">
                 <input type="hidden" name="rejection_reason" value="${r}">`;
    document.body.appendChild(f); f.submit();
}

// Sticky thead row 2 — top = tinggi row 1
(function() {
    const thead = document.querySelector('.gantt-table thead');
    if (!thead) return;
    const row1H = thead.rows[0].offsetHeight;
    Array.from(thead.rows[1].cells).forEach(th => th.style.top = row1H + 'px');
})();

// Scroll bar bawah — sinkronisasi dua arah
(function() {
    const wrap  = document.getElementById('ganttWrap');
    const bar   = document.getElementById('ganttScrollBar');
    const inner = document.getElementById('ganttScrollInner');

    // Samakan lebar inner dengan lebar tabel agar thumb proporsional
    inner.style.width = wrap.scrollWidth + 'px';

    let syncingFromBar = false, syncingFromWrap = false;
    wrap.addEventListener('scroll', () => {
        if (syncingFromBar) return;
        syncingFromWrap = true;
        bar.scrollLeft = wrap.scrollLeft;
        syncingFromWrap = false;
    });
    bar.addEventListener('scroll', () => {
        if (syncingFromWrap) return;
        syncingFromBar = true;
        wrap.scrollLeft = bar.scrollLeft;
        syncingFromBar = false;
    });

    // Scroll ke hari ini saat load
    const today = wrap.querySelector('.col-today');
    if (today) {
        const target = today.offsetLeft - 200;
        wrap.scrollLeft = target;
        bar.scrollLeft  = target;
    }
})();
</script>

<?= $this->endSection() ?>
