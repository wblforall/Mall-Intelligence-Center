<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= base_url('events/'.$event['id'].'/tenants') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h4 class="fw-bold mb-0">Tenant Impact</h4>
        <small class="text-muted"><?= esc($event['name']) ?></small>
    </div>
</div>

<!-- Summary Table -->
<div class="card mb-4">
    <div class="card-header"><h6 class="mb-0 fw-semibold"><i class="bi bi-bar-chart-line me-2"></i>Ringkasan Dampak Tenant</h6></div>
    <div class="card-body p-0">
        <?php if (empty($tenants)): ?>
        <div class="p-4 text-center text-muted">Belum ada tenant. <a href="<?= base_url('events/'.$event['id'].'/tenants') ?>">Tambah tenant terlebih dahulu.</a></div>
        <?php else: ?>
        <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr>
                <th>Tenant</th><th>Kategori</th><th>Promo</th>
                <th>Baseline Sales</th><th>Actual Sales</th><th>Sales Uplift</th><th>Uplift %</th>
                <th>Receipts</th><th>Voucher Redemptions</th><th>Relevansi</th>
            </tr></thead>
            <tbody>
            <?php foreach ($tenants as $t):
                $imp = $impactMap[$t['id']] ?? null;
                $actualSales  = $imp ? (int)$imp['total_actual_sales'] : 0;
                $uplift       = $actualSales - (int)$t['baseline_sales'];
                $upliftPct    = $t['baseline_sales'] > 0 ? $uplift / $t['baseline_sales'] : 0;
            ?>
            <tr>
                <td class="fw-medium"><?= esc($t['name']) ?></td>
                <td><?= esc($t['category']) ?></td>
                <td><?= $t['participating_promo'] ? '<i class="bi bi-check-circle-fill text-success"></i>' : '<i class="bi bi-x-circle text-muted"></i>' ?></td>
                <td>Rp <?= number_format((int)$t['baseline_sales'], 0, ',', '.') ?></td>
                <td><?= $actualSales > 0 ? 'Rp '.number_format($actualSales, 0, ',', '.') : '—' ?></td>
                <td class="<?= $uplift >= 0 ? 'text-success' : 'text-danger' ?>">
                    <?= $actualSales > 0 ? 'Rp '.number_format($uplift, 0, ',', '.') : '—' ?>
                </td>
                <td class="<?= $upliftPct >= 0 ? 'text-success' : 'text-danger' ?>">
                    <?= $actualSales > 0 ? number_format($upliftPct * 100, 1).'%' : '—' ?>
                </td>
                <td><?= $imp ? number_format((int)$imp['total_receipts'], 0, ',', '.') : '—' ?></td>
                <td><?= $imp ? number_format((int)$imp['total_voucher_redemptions'], 0, ',', '.') : '—' ?></td>
                <td>
                    <?php $rc = ['High'=>'danger','Medium'=>'warning','Low'=>'secondary'][$t['event_relevance']] ?? 'secondary' ?>
                    <span class="badge bg-<?= $rc ?>-subtle text-<?= $rc ?>"><?= $t['event_relevance'] ?></span>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Input Form -->
<?php if (! empty($tenants) && ! empty($trackingDates)): ?>
<div class="card">
<div class="card-header"><h6 class="mb-0 fw-semibold"><i class="bi bi-pencil-square me-2"></i>Input Data Tenant Per Hari</h6></div>
<div class="card-body">

<ul class="nav nav-tabs mb-3" id="dateTabs" role="tablist">
<?php foreach ($trackingDates as $i => $date): ?>
<li class="nav-item">
    <button class="nav-link <?= $i === 0 ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#date-<?= $i ?>">
        <?= date('d/m', strtotime($date)) ?>
    </button>
</li>
<?php endforeach; ?>
</ul>

<form method="POST" action="<?= base_url('events/'.$event['id'].'/tenants/impact') ?>">
<?= csrf_field() ?>

<div class="tab-content">
<?php foreach ($trackingDates as $i => $date): ?>
<div class="tab-pane fade <?= $i === 0 ? 'show active' : '' ?>" id="date-<?= $i ?>">
    <div class="table-responsive">
    <table class="table table-sm">
        <thead><tr>
            <th>Tenant</th><th>Actual Sales (Rp)</th><th>Receipts</th><th>Voucher Redemptions</th>
        </tr></thead>
        <tbody>
        <?php foreach ($tenants as $t):
            $existing = null;
            // Find existing impact for this tenant and date
            // (Would need a more granular query; for now use 0 as default)
        ?>
        <tr>
            <td class="fw-medium"><?= esc($t['name']) ?></td>
            <td>
                <input type="hidden" name="tenant_id[]" value="<?= $t['id'] ?>">
                <input type="hidden" name="tracking_date[]" value="<?= $date ?>">
                <input type="text" name="actual_sales[]" class="form-control form-control-sm currency-input" value="0">
            </td>
            <td><input type="number" name="receipts[]" class="form-control form-control-sm" value="0" min="0"></td>
            <td><input type="number" name="voucher_redemptions[]" class="form-control form-control-sm" value="0" min="0"></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<?php endforeach; ?>
</div>

<button type="submit" class="btn btn-primary mt-2">
    <i class="bi bi-check-lg me-1"></i> Simpan Semua
</button>
</form>
</div>
</div>
<?php elseif (empty($trackingDates)): ?>
<div class="alert alert-info"><i class="bi bi-info-circle me-2"></i>Tambahkan data Daily Tracking terlebih dahulu untuk input tenant impact per hari.</div>
<?php endif; ?>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
document.querySelectorAll('.currency-input').forEach(inp => {
    inp.addEventListener('input', function() {
        let n = parseInt(this.value.replace(/[^0-9]/g, '')) || 0;
        this.value = n.toLocaleString('id-ID');
    });
});
</script>
<?= $this->endSection() ?>
