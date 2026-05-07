<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
function fld(array $cfg, string $key, $default = 0) { return $cfg[$key] ?? $default; }
function pctVal(array $cfg, string $key, float $default): string {
    $v = isset($cfg[$key]) ? (float)$cfg[$key] * 100 : $default * 100;
    return number_format($v, 1);
}
?>

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= base_url('events/'.$event['id'].'/dashboard') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h4 class="fw-bold mb-0">Inputs & Biaya</h4>
        <small class="text-muted"><?= esc($event['name']) ?></small>
    </div>
</div>

<form method="POST" action="<?= base_url('events/'.$event['id'].'/inputs') ?>">
<?= csrf_field() ?>

<div class="row g-4">
    <!-- Cost Breakdown -->
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header"><h6 class="mb-0 fw-semibold"><i class="bi bi-cash-stack me-2"></i>Cost Breakdown</h6></div>
            <div class="card-body">
                <?php
                $costFields = [
                    ['royalty_character',  'Royalty Character',          451770000],
                    ['operational_mg',     'Operational Meet & Greet',   305677350],
                    ['production_decor',   'Production / Decor',         0],
                    ['promotion_media',    'Promotion / Media Buying',   0],
                    ['security_cost',      'Security / Crowd Control',   0],
                    ['other_cost',         'Other Cost',                 0],
                ];
                foreach ($costFields as [$name, $label, $default]):
                $val = (int)fld($config, $name, $default);
                ?>
                <div class="mb-3">
                    <label class="form-label small fw-semibold"><?= $label ?></label>
                    <div class="input-group">
                        <span class="input-group-text small">Rp</span>
                        <input type="text" name="<?= $name ?>" class="form-control currency-input"
                               value="<?= number_format($val, 0, ',', '.') ?>">
                    </div>
                </div>
                <?php endforeach; ?>

                <div class="alert alert-light border mt-2 mb-0">
                    <div class="d-flex justify-content-between">
                        <span class="small fw-semibold">Total Cost</span>
                        <span class="fw-bold text-danger" id="total-cost">Rp 0</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Target KPIs -->
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header"><h6 class="mb-0 fw-semibold"><i class="bi bi-bullseye me-2"></i>Target KPI</h6></div>
            <div class="card-body">
                <?php
                $targetFields = [
                    ['target_traffic_uplift',     'Target Traffic Uplift (%)',       30, 'Kenaikan traffic vs baseline'],
                    ['target_engagement_rate',    'Target Engagement Rate (%)',      15, 'Engaged visitor / event area visitors'],
                    ['target_member_conversion',  'Target Member Conversion (%)',    20, 'New PAM Plus / engaged visitor'],
                    ['target_transaction_conv',   'Target Transaction Conversion (%)' ,10, 'Receipt / engaged visitor'],
                    ['target_voucher_redemption', 'Target Voucher Redemption (%)',   35, 'Redeemed / claimed'],
                    ['target_sales_uplift',       'Target Sales Uplift (%)',         25, 'Kenaikan tenant sales vs baseline'],
                    ['target_sponsor_coverage',   'Target Sponsor Coverage (%)',     40, 'Coverage cost dari sponsor + booth'],
                ];
                foreach ($targetFields as [$name, $label, $default, $hint]):
                ?>
                <div class="mb-3">
                    <label class="form-label small fw-semibold"><?= $label ?></label>
                    <div class="input-group">
                        <input type="number" name="<?= $name ?>" class="form-control form-control-sm"
                               value="<?= pctVal($config, $name, $default/100) ?>"
                               step="0.1" min="0" max="999">
                        <span class="input-group-text small">%</span>
                    </div>
                    <div class="form-text"><?= $hint ?></div>
                </div>
                <?php endforeach; ?>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Target ROI Direct (x)</label>
                    <input type="number" name="target_roi_direct" class="form-control form-control-sm"
                           value="<?= number_format((float)fld($config, 'target_roi_direct', 1), 2) ?>"
                           step="0.1" min="0">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Target Repeat Visit Rate (%)</label>
                    <div class="input-group">
                        <input type="number" name="target_repeat_visit" class="form-control form-control-sm"
                               value="<?= pctVal($config, 'target_repeat_visit', 0.15) ?>"
                               step="0.1" min="0">
                        <span class="input-group-text small">%</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="mt-4">
    <button type="submit" class="btn btn-primary">
        <i class="bi bi-check-lg me-1"></i> Simpan & Lanjut ke Baseline
    </button>
    <a href="<?= base_url('events/'.$event['id'].'/dashboard') ?>" class="btn btn-outline-secondary ms-2">Batal</a>
</div>
</form>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
function parseRp(val) {
    return parseInt(val.replace(/[^0-9]/g, '')) || 0;
}
function formatRp(n) {
    return n.toLocaleString('id-ID');
}
function updateTotal() {
    let total = 0;
    document.querySelectorAll('.currency-input').forEach(inp => {
        total += parseRp(inp.value);
    });
    document.getElementById('total-cost').textContent = 'Rp ' + formatRp(total);
}
document.querySelectorAll('.currency-input').forEach(inp => {
    inp.addEventListener('input', function() {
        let n = parseRp(this.value);
        let pos = this.selectionStart;
        this.value = formatRp(n);
        updateTotal();
    });
});
updateTotal();
</script>
<?= $this->endSection() ?>
