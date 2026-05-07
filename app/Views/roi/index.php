<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php function fmtRoi(int $n): string { return 'Rp '.number_format($n,0,',','.'); } ?>

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= base_url('events/'.$event['id'].'/dashboard') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h4 class="fw-bold mb-0">ROI Summary</h4>
        <small class="text-muted"><?= esc($event['name']) ?></small>
    </div>
</div>

<div class="row g-4">

    <!-- Revenue vs Cost Overview -->
    <div class="col-md-5">
        <div class="card h-100">
            <div class="card-header"><h6 class="mb-0 fw-semibold"><i class="bi bi-pie-chart me-2"></i>Revenue vs Cost</h6></div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <canvas id="roiChart" height="220"></canvas>
            </div>
        </div>
    </div>

    <!-- Key Metrics -->
    <div class="col-md-7">
        <div class="row g-3">
            <?php
            $metrics = [
                ['Total Event Cost', $roi['total_cost'], 'danger', 'cash-stack'],
                ['Total Direct Revenue', $roi['total_direct_revenue'], 'success', 'wallet2'],
                ['Net Direct Impact', $roi['net_direct_impact'], $roi['net_direct_impact'] >= 0 ? 'success' : 'danger', 'graph-up'],
                ['Tenant Sales Uplift', $roi['tenant_uplift'], $roi['tenant_uplift'] >= 0 ? 'success' : 'danger', 'shop'],
            ];
            foreach ($metrics as [$label, $amount, $color, $icon]):
            ?>
            <div class="col-6">
                <div class="card p-3">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <i class="bi bi-<?= $icon ?> text-<?= $color ?>"></i>
                        <span class="small text-muted"><?= $label ?></span>
                    </div>
                    <div class="fs-5 fw-bold text-<?= $color ?>"><?= fmtRoi($amount) ?></div>
                </div>
            </div>
            <?php endforeach; ?>

            <!-- ROI Metrics -->
            <div class="col-6">
                <div class="card p-3">
                    <div class="small text-muted mb-1">Direct ROI</div>
                    <div class="fs-5 fw-bold <?= $roi['direct_roi'] >= $roi['target_roi'] ? 'text-success' : 'text-danger' ?>">
                        <?= number_format($roi['direct_roi'], 2) ?>x
                    </div>
                    <div class="small text-muted">Target: <?= number_format($roi['target_roi'], 2) ?>x</div>
                    <span class="badge <?= $roi['direct_roi'] >= $roi['target_roi'] ? 'bg-success' : 'bg-danger' ?>-subtle text-<?= $roi['direct_roi'] >= $roi['target_roi'] ? 'success' : 'danger' ?> small">
                        <?= $roi['direct_roi'] >= $roi['target_roi'] ? 'On Target' : 'Below Target' ?>
                    </span>
                </div>
            </div>
            <div class="col-6">
                <div class="card p-3">
                    <div class="small text-muted mb-1">Event Impact Ratio</div>
                    <div class="fs-5 fw-bold <?= $roi['event_impact_ratio'] >= 1 ? 'text-success' : 'text-danger' ?>">
                        <?= number_format($roi['event_impact_ratio'], 2) ?>x
                    </div>
                    <div class="small text-muted">Target: >1.0x</div>
                    <span class="badge <?= $roi['event_impact_ratio'] >= 1 ? 'bg-success' : 'bg-danger' ?>-subtle text-<?= $roi['event_impact_ratio'] >= 1 ? 'success' : 'danger' ?> small">
                        <?= $roi['event_impact_ratio'] >= 1 ? 'On Target' : 'Below Target' ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Cost Breakdown -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h6 class="mb-0 fw-semibold"><i class="bi bi-cash-stack me-2"></i>Cost Breakdown</h6></div>
            <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <tbody>
                <?php foreach ($costBreakdown as $c):
                    $pct = $roi['total_cost'] > 0 ? $c['amount'] / $roi['total_cost'] * 100 : 0;
                ?>
                <tr>
                    <td><?= $c['label'] ?></td>
                    <td class="text-end fw-medium"><?= fmtRoi($c['amount']) ?></td>
                    <td style="width:120px">
                        <div class="progress" style="height:6px">
                            <div class="progress-bar bg-danger" style="width:<?= $pct ?>%"></div>
                        </div>
                    </td>
                    <td class="text-muted small text-end"><?= number_format($pct,1) ?>%</td>
                </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                <tr class="table-light fw-bold">
                    <td>Total</td>
                    <td class="text-end text-danger"><?= fmtRoi($roi['total_cost']) ?></td>
                    <td colspan="2"></td>
                </tr>
                </tfoot>
            </table>
            </div>
        </div>
    </div>

    <!-- Revenue Breakdown -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h6 class="mb-0 fw-semibold"><i class="bi bi-wallet2 me-2"></i>Revenue Breakdown</h6></div>
            <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <tbody>
                <?php
                $revItems = [
                    ['Sponsor Revenue', $roi['sponsor_revenue']],
                    ['Booth / CL Revenue', $roi['booth_revenue']],
                    ['Media Revenue', $roi['media_revenue']],
                    ['Parking Uplift', $roi['parking_uplift']],
                ];
                foreach ($revItems as [$label, $amount]):
                    $pct = $roi['total_direct_revenue'] > 0 ? $amount / $roi['total_direct_revenue'] * 100 : 0;
                ?>
                <tr>
                    <td><?= $label ?></td>
                    <td class="text-end fw-medium"><?= fmtRoi($amount) ?></td>
                    <td style="width:120px">
                        <div class="progress" style="height:6px">
                            <div class="progress-bar bg-success" style="width:<?= $pct ?>%"></div>
                        </div>
                    </td>
                    <td class="text-muted small text-end"><?= number_format($pct,1) ?>%</td>
                </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                <tr class="table-light fw-bold">
                    <td>Total</td>
                    <td class="text-end text-success"><?= fmtRoi($roi['total_direct_revenue']) ?></td>
                    <td colspan="2"></td>
                </tr>
                </tfoot>
            </table>
            </div>
        </div>

        <!-- Break-even info -->
        <div class="card mt-3">
            <div class="card-body p-3">
                <div class="row text-center">
                    <div class="col">
                        <div class="small text-muted">Break-even Daily</div>
                        <div class="fw-bold"><?= fmtRoi((int)$roi['break_even_daily']) ?></div>
                    </div>
                    <div class="col">
                        <div class="small text-muted">Target Revenue</div>
                        <div class="fw-bold text-primary"><?= fmtRoi((int)$roi['target_revenue']) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
new Chart(document.getElementById('roiChart'), {
    type: 'doughnut',
    data: {
        labels: ['Total Cost', 'Direct Revenue', 'Tenant Uplift'],
        datasets: [{
            data: [
                <?= $roi['total_cost'] ?>,
                <?= $roi['total_direct_revenue'] ?>,
                <?= max(0, $roi['tenant_uplift']) ?>
            ],
            backgroundColor: ['#ef4444', '#10b981', '#3b82f6'],
            borderWidth: 0,
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom' },
            tooltip: {
                callbacks: {
                    label: (ctx) => ' Rp ' + ctx.raw.toLocaleString('id-ID')
                }
            }
        }
    }
});
</script>
<?= $this->endSection() ?>
