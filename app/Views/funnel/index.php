<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= base_url('events/'.$event['id'].'/dashboard') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h4 class="fw-bold mb-0">Conversion Funnel</h4>
        <small class="text-muted"><?= esc($event['name']) ?></small>
    </div>
</div>

<div class="row g-3">
    <!-- Funnel Chart -->
    <div class="col-md-5">
        <div class="card h-100">
            <div class="card-header"><h6 class="mb-0 fw-semibold">Funnel Visual</h6></div>
            <div class="card-body"><canvas id="funnelChart"></canvas></div>
        </div>
    </div>

    <!-- Funnel Table -->
    <div class="col-md-7">
        <div class="card h-100">
            <div class="card-header"><h6 class="mb-0 fw-semibold">Detail Per Stage</h6></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr>
                        <th>Stage</th><th>Metric</th><th>Actual</th><th>Target</th>
                        <th>Conversion</th><th>Status</th>
                    </tr></thead>
                    <tbody>
                    <?php
                    $prev = null;
                    foreach ($funnel as $i => $f):
                        $convRate = ($prev && $prev > 0) ? $f['actual'] / $prev : null;
                        $prev = $f['actual'];
                        $isRp = str_contains($f['metric'], 'Revenue') || str_contains($f['metric'], '+');
                        $fmtActual = $isRp ? 'Rp '.number_format($f['actual'],0,',','.') : number_format($f['actual'],0,',','.');
                        $fmtTarget = $f['target'] > 0 ? ($isRp ? 'Rp '.number_format($f['target'],0,',','.') : number_format($f['target'],0,',','.')) : '—';
                        $onTarget  = $f['target'] > 0 && $f['actual'] >= $f['target'];
                        $statusClass = $f['target'] == 0 ? 'track' : ($onTarget ? 'on-target' : 'below-target');
                        $statusLabel = $f['target'] == 0 ? 'Track' : ($onTarget ? 'On Target' : 'Below');
                    ?>
                    <tr>
                        <td><span class="badge bg-primary-subtle text-primary"><?= $f['stage'] ?></span></td>
                        <td class="small"><?= $f['metric'] ?></td>
                        <td class="fw-medium"><?= $fmtActual ?></td>
                        <td class="text-muted small"><?= $fmtTarget ?></td>
                        <td class="small text-muted"><?= $convRate !== null ? number_format($convRate*100,1).'%' : '—' ?></td>
                        <td>
                            <span class="badge status-badge <?= $statusClass ?>"><?= $statusLabel ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
const funnelLabels = <?= json_encode(array_column($funnel, 'stage')) ?>;
const funnelData   = <?= json_encode(array_column($funnel, 'actual')) ?>;
const funnelColors = [
    '#3b82f6','#6366f1','#8b5cf6','#a855f7','#ec4899','#f43f5e','#ef4444','#f97316'
].slice(0, funnelLabels.length);

new Chart(document.getElementById('funnelChart'), {
    type: 'bar',
    data: {
        labels: funnelLabels,
        datasets: [{
            data: funnelData,
            backgroundColor: funnelColors,
            borderRadius: 6,
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { x: { beginAtZero: true } }
    }
});
</script>
<?= $this->endSection() ?>
