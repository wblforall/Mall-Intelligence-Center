<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/media-promo.css') ?>?v=<?= @filemtime(FCPATH . 'css/media-promo.css') ?: 1 ?>">
<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php include_once __DIR__ . '/_helpers.php'; ?>
<?php
$bulanNama = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$hariSingkat = ['','S','S','R','K','J','S','M'];

// Deret tanggal rentang gantt
$allDates = [];
for ($ptr = strtotime($tglMulai), $end = strtotime($tglSelesai); $ptr <= $end; $ptr = strtotime('+1 day', $ptr)) {
    $allDates[] = date('Y-m-d', $ptr);
}
$totalDays = count($allDates);
$monthGroups = [];
foreach ($allDates as $tgl) $monthGroups[date('Y-m', strtotime($tgl))][] = $tgl;
$monthLastDates = [];
foreach ($monthGroups as $mDates) $monthLastDates[end($mDates)] = true;

// Peta usage per (titik, slot)
$usageMap = [];
foreach ($usages as $u) $usageMap[$u['spot_id'] . '_' . ($u['slot_number'] ?? '0')][] = $u;

// Daftar baris
$rows = [];
foreach ($spots as $spot) {
    if ($filterTipe && $spot['tipe'] !== $filterTipe) continue;
    if ($spot['tipe'] === 'digital') {
        $rows[] = ['type' => 'header', 'spot' => $spot];
        for ($sl = 1; $sl <= (int) $spot['total_slots']; $sl++) $rows[] = ['type' => 'slot', 'spot' => $spot, 'slot' => $sl];
    } else {
        $rows[] = ['type' => 'single', 'spot' => $spot, 'slot' => null];
    }
}
$lastSlotIdx = [];
foreach ($rows as $i => $row) if ($row['type'] === 'slot') $lastSlotIdx[$row['spot']['id']] = $i;

$today  = date('Y-m-d');
$colW   = 30;
$labelW = 210;
$tableWidth = $labelW + ($totalDays * $colW);

// Angka ringkas (dari usage yang tampil di rentang)
$cnt = ['pending' => 0, 'approved' => 0, 'done' => 0]; $ending = 0;
foreach ($usages as $u) {
    $cnt[$u['status']] = ($cnt[$u['status']] ?? 0) + 1;
    if (in_array($u['status'], ['pending', 'approved']) && $u['tanggal_selesai'] >= $today && mpDays($u['tanggal_selesai'], $today) <= 7) $ending++;
}

// Gambar sel-sel hari pada satu baris (bar memakai colspan)
$renderDays = function (array $rowUsages, int $rowH) use ($allDates, $totalDays, $today, $tglMulai, $tglSelesai, $monthLastDates) {
    $idx = 0;
    while ($idx < $totalDays) {
        $tgl = $allDates[$idx];
        $dow = (int) date('N', strtotime($tgl));
        $cc  = ($tgl === $today) ? ' is-today' : ($dow >= 6 ? ' is-we' : '');
        $ml  = isset($monthLastDates[$tgl]) ? ' m-last' : '';
        $hit = null; $span = 1;
        foreach ($rowUsages as $u) {
            $sDate = max($u['tanggal_mulai'], $tglMulai);
            $eDate = min($u['tanggal_selesai'], $tglSelesai);
            if ($sDate === $tgl) {
                $hit = $u; $span = 0;
                for ($j = $idx; $j < $totalDays && $allDates[$j] <= $eDate; $j++) $span++;
                $span = max(1, $span);
                break;
            }
        }
        if ($hit) {
            $lastDay = $allDates[min($idx + $span - 1, $totalDays - 1)];
            $barML   = isset($monthLastDates[$lastDay]) ? ' m-last' : '';
            $endSoon = in_array($hit['status'], ['pending', 'approved']) && $hit['tanggal_selesai'] >= $today && mpDays($hit['tanggal_selesai'], $today) <= 7;
            echo '<td colspan="' . $span . '" class="' . trim($barML) . '" style="height:' . $rowH . 'px">';
            echo '<button type="button" class="mp-gbar s-' . esc($hit['status'], 'attr') . ($endSoon ? ' is-ending' : '') . '" data-mp-usage="' . (int) $hit['id'] . '"'
               . ' title="' . esc($hit['nama_materi'], 'attr') . ' (' . date('d/m', strtotime($hit['tanggal_mulai'])) . '–' . date('d/m', strtotime($hit['tanggal_selesai'])) . ')' . (! empty($hit['creative_ref']) ? ' · dari Creative' : '') . '">'
               . esc($hit['nama_materi']) . '</button></td>';
            $idx += $span;
        } else {
            echo '<td class="' . trim($cc . $ml) . '" style="height:' . $rowH . 'px"></td>';
            $idx++;
        }
    }
};

$mpTitle = 'Gantt Media Promo'; $mpIcon = 'bi-bar-chart-steps';
$mpSub = date('d M Y', strtotime($tglMulai)) . ' – ' . date('d M Y', strtotime($tglSelesai)) . ' · booking pending, approved dan selesai';
?>
<div class="page-mp" id="mpRoot" data-mp="gantt" data-base="<?= base_url() ?>" data-today="<?= $today ?>"
     data-can-approve="<?= $canApprove ? 1 : 0 ?>" data-csrf-name="<?= csrf_token() ?>" data-csrf-hash="<?= csrf_hash() ?>">
<?php include __DIR__ . '/_nav.php'; ?>

<div class="mp-kpis k4">
    <div class="mp-kpi"><div class="mp-kpi-l">Booking di Rentang</div><div class="mp-kpi-v"><?= count($usages) ?></div><div class="mp-kpi-s">titik/slot yang tampil</div></div>
    <div class="mp-kpi"><div class="mp-kpi-l">Pending</div><div class="mp-kpi-v mp-c-warn"><?= $cnt['pending'] ?></div><div class="mp-kpi-s">menunggu approval</div></div>
    <div class="mp-kpi"><div class="mp-kpi-l">Approved</div><div class="mp-kpi-v mp-c-ok"><?= $cnt['approved'] ?></div><div class="mp-kpi-s"><?= $cnt['done'] ?> selesai</div></div>
    <div class="mp-kpi"><div class="mp-kpi-l">Berakhir ≤ 7 Hari</div><div class="mp-kpi-v <?= $ending ? 'mp-c-warn' : '' ?>"><?= $ending ?></div><div class="mp-kpi-s">pending / approved</div></div>
</div>

<!-- Filter -->
<form method="GET" action="" class="mp-tools" id="mpTools">
    <div class="mp-search"><i class="bi bi-search"></i>
        <input type="search" id="mpQ" placeholder="Cari titik di tabel…" autocomplete="off" aria-label="Cari titik">
    </div>
    <select name="tipe" class="mp-select" aria-label="Filter tipe media" onchange="this.form.submit()">
        <option value="">Semua tipe</option>
        <?php foreach (['t_banner' => 'T-Banner', 'hanging' => 'Hanging Banner', 'sticker_lift' => 'Sticker Lift', 'totem_stainless' => 'Totem Stainless', 'digital' => 'Digital'] as $k => $l): ?>
        <option value="<?= $k ?>" <?= $filterTipe === $k ? 'selected' : '' ?>><?= $l ?></option>
        <?php endforeach; ?>
    </select>
    <select name="dept" class="mp-select" aria-label="Filter departemen" onchange="this.form.submit()">
        <option value="">Semua dept</option>
        <?php foreach ($depts as $d): ?>
        <option value="<?= esc($d, 'attr') ?>" <?= $filterDept === $d ? 'selected' : '' ?>><?= esc($d) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="button" class="mp-fchip" id="mpOnlyBooked" aria-pressed="false" title="Sembunyikan titik yang kosong"><i class="bi bi-funnel me-1"></i>Hanya yang ada booking</button>
    <?php if ($filterTipe || $filterDept): ?><a href="<?= base_url('creative/media-promo/gantt') ?>" class="mp-btn mp-btn-sm"><i class="bi bi-x-circle"></i>Reset</a><?php endif; ?>
    <div class="mp-gnav">
        <span class="mp-count" id="mpCount" aria-live="polite"></span>
        <button type="button" class="mp-iconbtn" data-mp-scroll="-7" title="Geser 1 minggu ke kiri" aria-label="Geser ke kiri"><i class="bi bi-chevron-left"></i></button>
        <button type="button" class="mp-btn mp-btn-sm mp-btn-soft" data-mp-today><i class="bi bi-crosshair"></i>Hari ini</button>
        <button type="button" class="mp-iconbtn" data-mp-scroll="7" title="Geser 1 minggu ke kanan" aria-label="Geser ke kanan"><i class="bi bi-chevron-right"></i></button>
        <?php foreach ($monthGroups as $mk => $mDates): [$mY, $mM] = explode('-', $mk); ?>
        <button type="button" class="mp-btn mp-btn-sm d-none d-lg-inline-flex" data-mp-jump="<?= $mDates[0] ?>"><?= mb_substr($bulanNama[(int) $mM], 0, 3) ?></button>
        <?php endforeach; ?>
    </div>
</form>

<div class="mp-legend">
    <span><i class="mp-dot" style="background:#d97706"></i>Pending</span>
    <span><i class="mp-dot" style="background:#059669"></i>Approved</span>
    <span><i class="mp-dot" style="background:#64748b"></i>Selesai</span>
    <span><i class="mp-dot" style="background:transparent;box-shadow:inset 0 0 0 2px #fbbf24"></i>Berakhir ≤ 7 hari</span>
    <span><i class="mp-dot" style="background:var(--mp-weekend);border:1px solid var(--mp-border)"></i>Akhir pekan</span>
    <span><i class="mp-dot" style="background:var(--mp-today);border:1px solid var(--mp-ok)"></i>Hari ini (<?= date('d M') ?>)</span>
    <span class="mp-muted">Klik bar untuk rincian</span>
</div>

<?php if (empty($rows)): ?>
<div class="mp-empty"><i class="bi bi-bar-chart-steps"></i><h5>Tidak ada titik media yang sesuai filter</h5>
    <a href="<?= base_url('creative/media-promo/gantt') ?>" class="mp-btn"><i class="bi bi-x-circle"></i>Reset filter</a></div>
<?php else: ?>
<div class="mp-gwrap" id="ganttWrap" tabindex="0" aria-label="Gantt media promo, bisa digulir horizontal">
<table class="mp-gtable" style="width:<?= $tableWidth ?>px">
<colgroup>
    <col style="width:<?= $labelW ?>px">
    <?php for ($i = 0; $i < $totalDays; $i++): ?><col style="width:<?= $colW ?>px"><?php endfor; ?>
</colgroup>
<thead>
<tr>
    <th class="th-label" rowspan="2">Titik / Slot</th>
    <?php foreach ($monthGroups as $mk => $mDates): [$mY, $mM] = explode('-', $mk); ?>
    <th class="m-last" colspan="<?= count($mDates) ?>"><span class="mp-gm"><?= $bulanNama[(int) $mM] ?> <?= $mY ?></span></th>
    <?php endforeach; ?>
</tr>
<tr>
    <?php foreach ($allDates as $tgl):
        $dow = (int) date('N', strtotime($tgl));
        $cc  = ($tgl === $today) ? 'is-today' : ($dow >= 6 ? 'is-we' : '');
        $ml  = isset($monthLastDates[$tgl]) ? ' m-last' : '';
    ?>
    <th class="<?= $cc . $ml ?>" data-date="<?= $tgl ?>"<?= $tgl === $today ? ' title="Hari ini"' : '' ?>><div><?= (int) date('j', strtotime($tgl)) ?></div><div style="opacity:.7"><?= $hariSingkat[$dow] ?></div></th>
    <?php endforeach; ?>
</tr>
</thead>
<tbody>
<?php foreach ($rows as $i => $row):
    $spot = $row['spot']; $type = $row['type']; $slot = $row['slot'] ?? null;
    $rowUsages = $usageMap[$spot['id'] . '_' . ($slot ?? '0')] ?? [];
    $isLastSlot = ($type === 'slot') && isset($lastSlotIdx[$spot['id']]) && $lastSlotIdx[$spot['id']] === $i;
    $srch = mb_strtolower($spot['kode'] . ' ' . $spot['nama'] . ' ' . ($spot['area'] ?? '') . ' ' . mpTipeLabel($spot['tipe']));
?>
<?php if ($type === 'header'): ?>
<tr class="r-head" data-spot="<?= $spot['id'] ?>" data-search="<?= esc($srch, 'attr') ?>">
    <td class="td-l">
        <span class="mp-gl-n" title="<?= esc($spot['kode'] . ' ' . $spot['nama'], 'attr') ?>"><?= esc($spot['kode']) ?> <?= esc($spot['nama']) ?></span>
        <span class="mp-gl-d">Digital<?= $spot['area'] ? ' · ' . esc($spot['area']) : '' ?> · <b style="color:var(--mp-blue)"><?= (int) $spot['total_slots'] ?> slot</b></span>
    </td>
    <?php foreach ($allDates as $tgl):
        $dow = (int) date('N', strtotime($tgl));
        $cc  = ($tgl === $today) ? 'is-today' : ($dow >= 6 ? 'is-we' : '');
        $ml  = isset($monthLastDates[$tgl]) ? ' m-last' : '';
    ?><td class="<?= $cc . $ml ?>" style="height:28px"></td><?php endforeach; ?>
</tr>
<?php elseif ($type === 'slot'): ?>
<tr class="<?= $isLastSlot ? 'r-last' : '' ?>" data-spot="<?= $spot['id'] ?>" data-search="<?= esc($srch, 'attr') ?>">
    <td class="td-l" style="padding-top:0;padding-bottom:0"><span class="mp-gl-s">Slot <?= $slot ?></span></td>
    <?php $renderDays($rowUsages, 32); ?>
</tr>
<?php else: ?>
<tr class="r-single" data-spot="<?= $spot['id'] ?>" data-search="<?= esc($srch, 'attr') ?>">
    <td class="td-l">
        <span class="mp-gl-n" title="<?= esc($spot['kode'] . ' ' . $spot['nama'], 'attr') ?>"><?= esc($spot['kode']) ?> <?= esc($spot['nama']) ?></span>
        <span class="mp-gl-d"><?= esc(mpTipeLabel($spot['tipe'])) ?><?= $spot['area'] ? ' · ' . esc($spot['area']) : '' ?></span>
    </td>
    <?php $renderDays($rowUsages, 36); ?>
</tr>
<?php endif; ?>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php endif; ?>

<!-- Modal rincian -->
<div class="modal fade mp-modal" id="gModal" tabindex="-1">
<div class="modal-dialog"><div class="modal-content">
    <div class="modal-header">
        <h6 class="modal-title">Detail Penggunaan</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
    </div>
    <div class="modal-body" id="gModalBody"></div>
    <div class="modal-footer" id="gModalFooter"></div>
</div></div></div>

<script type="application/json" id="mpUsages"><?= json_encode(array_column($usages, null, 'id'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
</div><!-- /.page-mp -->
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="<?= base_url('js/media-promo.js') ?>?v=<?= @filemtime(FCPATH . 'js/media-promo.js') ?: 1 ?>"></script>
<?= $this->endSection() ?>
