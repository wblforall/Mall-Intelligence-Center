<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <a href="<?= base_url('events') ?>" class="text-muted text-decoration-none small">
            <i class="bi bi-arrow-left me-1"></i>Kembali ke Daftar Event
        </a>
        <h4 class="fw-bold mb-0 mt-1"><i class="bi bi-arrow-left-right me-2"></i>Perbandingan Event</h4>
    </div>
</div>

<!-- Event selector -->
<div class="card mb-4">
    <div class="card-body py-3">
        <form method="get" action="<?= base_url('events/compare') ?>" class="row g-2 align-items-end">
            <div class="col-12 col-md-auto">
                <label class="form-label small fw-semibold mb-1">Pilih event (maks. 3):</label>
            </div>
            <?php for ($slot = 0; $slot < 3; $slot++): ?>
            <div class="col-12 col-md">
                <select name="ids[]" class="form-select form-select-sm">
                    <option value="">— Event <?= $slot + 1 ?> —</option>
                    <?php foreach ($allEvents as $e): ?>
                    <?php $sel = in_array($e['id'], $selectedIds) && ($selectedIds[$slot] ?? null) == $e['id'] ? 'selected' : '' ?>
                    <option value="<?= $e['id'] ?>" <?= $sel ?>><?= esc($e['name']) ?> (<?= date('M Y', strtotime($e['start_date'])) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endfor; ?>
            <div class="col-12 col-md-auto">
                <button class="btn btn-primary btn-sm w-100">Bandingkan</button>
            </div>
        </form>
    </div>
</div>

<?php if (empty($compared)): ?>
<div class="card">
    <div class="card-body p-5 text-center text-muted">
        <i class="bi bi-arrow-left-right display-3 d-block mb-3 opacity-25"></i>
        <p>Pilih 2–3 event di atas untuk melihat perbandingan.</p>
    </div>
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

<div class="table-responsive">
<table class="table table-bordered align-middle">
<thead class="table-light">
    <tr>
        <th style="min-width:200px">Indikator</th>
        <?php foreach ($compared as $c): ?>
        <th class="text-center" style="min-width:180px">
            <div class="fw-bold"><?= esc($c['event']['name']) ?></div>
            <div class="small text-muted">
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
<tr class="table-secondary">
    <td colspan="<?= count($compared) + 1 ?>" class="fw-semibold small text-uppercase py-1 px-3">
        <?= $row['section'] ?>
    </td>
</tr>
<?php continue; endif;

[$label, $key, $fmt, $higherBetter] = $row;
$vals    = array_map(fn($c) => $c[$key] ?? null, $compared);
$numVals = array_map(fn($v) => is_numeric($v) ? (float)$v : 0.0, $vals);
$best    = bestIdx($numVals, $higherBetter);
?>
<tr>
    <td class="small"><?= $label ?></td>
    <?php foreach ($compared as $ci => $c):
        $val = $c[$key] ?? null;
        $isBest = ($best === $ci) && count($compared) > 1;
        $cellClass = $isBest ? 'fw-bold text-success' : '';
    ?>
    <td class="text-center <?= $cellClass ?>">
        <?php if ($val === null): ?>
        <span class="text-muted">—</span>
        <?php elseif ($fmt === 'rp'): ?>
        <?php $color = $key === 'profit' ? ($val >= 0 ? 'text-success' : 'text-danger') : '' ?>
        <span class="<?= $color ?>">Rp <?= number_format($val, 0, ',', '.') ?></span>
        <?php elseif ($fmt === 'pct'): ?>
        <?= $val !== null ? $val . '%' : '—' ?>
        <?php elseif ($fmt === 'fraction'): ?>
        <?= $val ?>/<?= $c['required'] ?>
        <?php else: ?>
        <?= number_format($val, 0, ',', '.') ?>
        <?php endif; ?>
        <?php if ($isBest): ?> <i class="bi bi-trophy-fill text-warning ms-1" title="Terbaik"></i><?php endif; ?>
    </td>
    <?php endforeach; ?>
</tr>
<?php endforeach; ?>

</tbody>
</table>
</div>

<div class="text-muted small mt-2">
    <i class="bi bi-trophy-fill text-warning"></i> = nilai terbaik di antara event yang dibandingkan.
    Nilai terbaik untuk revenue/profit adalah yang tertinggi; untuk budget adalah yang paling efisien (tidak selalu tertinggi).
</div>

<?php endif; ?>

<script>
// Pre-select dropdowns based on selectedIds
const selectedIds = <?= json_encode(array_values($selectedIds)) ?>;
document.querySelectorAll('select[name="ids[]"]').forEach((sel, i) => {
    if (selectedIds[i]) sel.value = selectedIds[i];
});
</script>

<?= $this->endSection() ?>
