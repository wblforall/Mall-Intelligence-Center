<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php function fmtN(int $n): string { return number_format($n, 0, ',', '.'); } ?>
<?php function fmtP(?float $v): string { return $v !== null ? number_format($v*100,1).'%' : '—'; } ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center gap-2">
        <a href="<?= base_url('events/'.$event['id'].'/dashboard') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
        <div>
            <h4 class="fw-bold mb-0">Daily Tracking</h4>
            <small class="text-muted"><?= esc($event['name']) ?></small>
        </div>
    </div>
    <?php $daysEntered = count($rows); ?>
    <?php if ($daysEntered < $event['event_days']): ?>
    <a href="<?= base_url('events/'.$event['id'].'/tracking/add') ?>" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i> Input Hari <?= $daysEntered + 1 ?>
    </a>
    <?php endif; ?>
</div>

<?php if (empty($rows)): ?>
<div class="card">
    <div class="card-body p-5 text-center text-muted">
        <i class="bi bi-journal-x display-3 d-block mb-3"></i>
        <p>Belum ada data harian. Pastikan Baseline sudah diisi terlebih dahulu.</p>
        <a href="<?= base_url('events/'.$event['id'].'/tracking/add') ?>" class="btn btn-primary">Input Hari Pertama</a>
    </div>
</div>
<?php else: ?>

<!-- Summary totals -->
<?php
$totTraffic  = array_sum(array_column($rows, 'actual_traffic'));
$totEngaged  = array_sum(array_map(fn($r) => $r['engaged_visitors'], $rows));
$totMembers  = array_sum(array_column($rows, 'new_pam_members'));
$totReceipts = array_sum(array_column($rows, 'receipt_uploads'));
$totRevenue  = array_sum(array_map(fn($r) => $r['total_direct_revenue'], $rows));
?>
<div class="row g-3 mb-4">
    <div class="col-6 col-md"><div class="card p-3 text-center"><div class="fs-5 fw-bold"><?= fmtN($totTraffic) ?></div><div class="small text-muted">Total Traffic</div></div></div>
    <div class="col-6 col-md"><div class="card p-3 text-center"><div class="fs-5 fw-bold"><?= fmtN($totEngaged) ?></div><div class="small text-muted">Engaged Visitors</div></div></div>
    <div class="col-6 col-md"><div class="card p-3 text-center"><div class="fs-5 fw-bold"><?= fmtN($totMembers) ?></div><div class="small text-muted">New PAM Plus</div></div></div>
    <div class="col-6 col-md"><div class="card p-3 text-center"><div class="fs-5 fw-bold"><?= fmtN($totReceipts) ?></div><div class="small text-muted">Receipt Uploads</div></div></div>
    <div class="col-12 col-md"><div class="card p-3 text-center"><div class="fs-5 fw-bold text-success">Rp <?= fmtN($totRevenue) ?></div><div class="small text-muted">Total Direct Revenue</div></div></div>
</div>

<div class="card">
    <div class="card-header"><h6 class="mb-0 fw-semibold">Data Per Hari (<?= count($rows) ?>/<?= $event['event_days'] ?> hari)</h6></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead><tr>
                    <th>Hari</th><th>Tanggal</th><th>Type</th>
                    <th>Traffic</th><th>Traffic Uplift</th>
                    <th>Engaged</th><th>Eng. Rate</th>
                    <th>New Member</th><th>Receipt</th>
                    <th>Revenue</th><th>Aksi</th>
                </tr></thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                <tr>
                    <td class="fw-medium">Hari <?= $r['day_number'] ?></td>
                    <td><?= date('d M', strtotime($r['tracking_date'])) ?></td>
                    <td><span class="badge bg-light text-dark"><?= $r['day_type'] === 'Weekday' ? 'WD' : 'WE' ?></span></td>
                    <td><?= $r['actual_traffic'] !== null ? fmtN((int)$r['actual_traffic']) : '—' ?></td>
                    <td>
                        <?php if ($r['traffic_uplift'] !== null): ?>
                        <span class="<?= $r['traffic_uplift'] >= 0 ? 'text-success' : 'text-danger' ?>">
                            <?= fmtP($r['traffic_uplift']) ?>
                        </span>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td><?= fmtN((int)$r['engaged_visitors']) ?></td>
                    <td><?= fmtP($r['engagement_rate']) ?></td>
                    <td><?= fmtN((int)$r['new_pam_members']) ?></td>
                    <td><?= fmtN((int)$r['receipt_uploads']) ?></td>
                    <td class="text-success fw-medium">Rp <?= fmtN((int)$r['total_direct_revenue']) ?></td>
                    <td>
                        <a href="<?= base_url('events/'.$event['id'].'/tracking/'.$r['id'].'/edit') ?>" class="btn btn-xs btn-outline-secondary btn-sm py-0 px-1">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <a href="<?= base_url('events/'.$event['id'].'/tracking/'.$r['id'].'/delete') ?>"
                           class="btn btn-xs btn-outline-danger btn-sm py-0 px-1"
                           onclick="return confirm('Hapus data hari ini?')">
                            <i class="bi bi-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?= $this->endSection() ?>
