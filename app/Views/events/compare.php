<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/events-core.css') ?>?v=<?= @filemtime(FCPATH . 'css/events-core.css') ?: 1 ?>">
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<div class="page-evc">

<div class="ev-head">
    <a href="<?= base_url('events') ?>" class="ev-iconbtn" aria-label="Kembali ke Daftar Event" title="Kembali ke Daftar Event"><i class="bi bi-arrow-left"></i></a>
    <div class="min-w-0">
        <h1 class="ev-title">Perbandingan Event</h1>
        <div class="ev-subtitle">Bandingkan revenue, anggaran, traffic, dan kelengkapan data hingga 3 event</div>
    </div>
</div>

<!-- Pilih event -->
<div class="ev-panel">
    <form method="get" action="<?= base_url('events/compare') ?>" class="ev-pick">
        <?php for ($slot = 0; $slot < 3; $slot++): ?>
        <div>
            <label for="cmpSel<?= $slot ?>">Event <?= $slot + 1 ?></label>
            <select name="ids[]" id="cmpSel<?= $slot ?>" class="ev-select">
                <option value="">— Pilih event —</option>
                <?php foreach ($allEvents as $e): ?>
                <?php $sel = in_array($e['id'], $selectedIds) && ($selectedIds[$slot] ?? null) == $e['id'] ? 'selected' : '' ?>
                <option value="<?= $e['id'] ?>" <?= $sel ?>><?= esc($e['name']) ?> (<?= date('M Y', strtotime($e['start_date'])) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endfor; ?>
        <div>
            <button class="ev-btn ev-btn-primary" style="height:38px"><i class="bi bi-arrow-left-right"></i>Bandingkan</button>
        </div>
    </form>
</div>

<?php if (empty($compared)): ?>
<div class="ev-empty">
    <i class="bi bi-arrow-left-right"></i>
    <h5>Belum ada event dipilih</h5>
    <p>Pilih 2–3 event di atas untuk melihat perbandingan. Anda juga bisa mencentang event di Daftar Event.</p>
    <a href="<?= base_url('events') ?>" class="ev-btn">Ke Daftar Event</a>
</div>
<?php else: ?>

<?php
$mallLabels = ['ewalk' => 'eWalk', 'pentacity' => 'Pentacity', 'keduanya' => 'eWalk & Pentacity'];

// Helper: find best value index (highest)
function bestIdx(array $vals, bool $higher = true): int {
    $fn    = $higher ? 'max' : 'min';
    $best  = $fn($vals);
    $found = array_search($best, $vals);
    return ($found !== false && $best != 0) ? (int)$found : -1;
}
?>

<div class="ev-table-wrap">
<table class="ev-table">
<thead>
    <tr>
        <th class="ev-ind">Indikator</th>
        <?php foreach ($compared as $c): ?>
        <th class="ev-colh">
            <div class="n"><?= esc($c['event']['name']) ?></div>
            <div class="s">
                <?= $mallLabels[$c['event']['mall']] ?? esc($c['event']['mall']) ?> &bull;
                <?= date('d M Y', strtotime($c['event']['start_date'])) ?>
                – <?= date('d M Y', strtotime($c['endDate'])) ?>
                (<?= $c['event']['event_days'] ?> hari)
            </div>
        </th>
        <?php endforeach; ?>
    </tr>
</thead>
<tbody>

<?php
// Rows definition: [label, key, format, higher_is_better]
$rows = [
    // Revenue
    ['section' => 'Revenue'],
    ['Total Revenue', 'totalRevenue', 'rp', true],
    ['— Exhibitor Dealing', 'totalDealing', 'rp', true],
    ['— Sponsor Cash', 'totalSponsorCash', 'rp', true],
    ['Jumlah Exhibitor', 'exhibitorCount', 'num', true],
    ['Jumlah Sponsor', 'sponsorCount', 'num', true],
    // Budget
    ['section' => 'Budget & Realisasi'],
    ['Total Budget', 'totalBudget', 'rp', false],
    ['Budget Realisasi', 'totalBudgetReal', 'rp', false],
    ['Serapan Budget (%)', 'budgetUsePct', 'pct', false],
    ['— Loyalty Budget', 'loyaltyBudget', 'rp', false],
    ['— Loyalty Realisasi', 'loyaltyReal', 'rp', false],
    ['— VM Budget', 'vmBudget', 'rp', false],
    ['— VM Realisasi', 'vmReal', 'rp', false],
    ['— Content Budget', 'contentBudget', 'rp', false],
    ['— Content Realisasi', 'contentReal', 'rp', false],
    ['— Creative Budget', 'creativeBudget', 'rp', false],
    ['— Creative Realisasi', 'creativeReal', 'rp', false],
    // Profitability
    ['section' => 'Profitabilitas'],
    ['Profit (Revenue − Budget)', 'profit', 'rp', true],
    ['Margin (%)', 'marginPct', 'pct', true],
    // Traffic
    ['section' => 'Traffic'],
    ['Total Traffic', 'totalTraffic', 'num', true],
    ['— eWalk', 'trafficEwalk', 'num', true],
    ['— Pentacity', 'trafficPenta', 'num', true],
    // Completion
    ['section' => 'Kelengkapan Data'],
    ['Modul Selesai', 'completions', 'fraction', true],
];

foreach ($rows as $row):
    if (isset($row['section'])):
?>
<tr class="ev-sect">
    <td colspan="<?= count($compared) + 1 ?>"><?= $row['section'] ?></td>
</tr>
<?php continue; endif;

[$label, $key, $fmt, $higherBetter] = $row;
$vals    = array_map(fn($c) => $c[$key] ?? null, $compared);
$numVals = array_map(fn($v) => is_numeric($v) ? (float)$v : 0.0, $vals);
$best    = bestIdx($numVals, $higherBetter);
?>
<tr>
    <td class="ev-ind <?= str_starts_with($label, '—') ? 'ev-sub' : '' ?>"><?= $label ?></td>
    <?php foreach ($compared as $ci => $c):
        $val = $c[$key] ?? null;
        $isBest = ($best === $ci) && count($compared) > 1;
        $cellClass = $isBest ? 'is-best' : '';
    ?>
    <td class="ev-val <?= $cellClass ?>">
        <?php if ($val === null): ?>
        <span class="ev-c-mute" style="color:var(--ev-muted)">—</span>
        <?php elseif ($fmt === 'rp'): ?>
        <?php $color = $key === 'profit' ? ($val >= 0 ? 'ev-c-ok' : 'ev-c-bad') : '' ?>
        <span class="<?= $color ?>">Rp <?= number_format($val, 0, ',', '.') ?></span>
        <?php elseif ($fmt === 'pct'): ?>
        <?= $val !== null ? $val . '%' : '—' ?>
        <?php elseif ($fmt === 'fraction'): ?>
        <?= $val ?>/<?= $c['required'] ?>
        <?php else: ?>
        <?= number_format($val, 0, ',', '.') ?>
        <?php endif; ?>
        <?php if ($isBest): ?> <i class="bi bi-trophy-fill ms-1" style="color:var(--ev-gold)" title="Terbaik"></i><?php endif; ?>
    </td>
    <?php endforeach; ?>
</tr>
<?php endforeach; ?>

</tbody>
</table>
</div>

<div class="ev-legend">
    <i class="bi bi-trophy-fill"></i>
    <span>= nilai terbaik di antara event yang dibandingkan. Terbaik untuk revenue/profit adalah yang tertinggi; untuk budget adalah yang paling efisien (nilai terendah, tidak selalu tertinggi).</span>
</div>

<?php endif; ?>

<script>
// Pre-select dropdowns based on selectedIds
const selectedIds = <?= json_encode(array_values($selectedIds)) ?>;
document.querySelectorAll('select[name="ids[]"]').forEach((sel, i) => {
    if (selectedIds[i]) sel.value = selectedIds[i];
});
</script>

</div>

<?= $this->endSection() ?>
