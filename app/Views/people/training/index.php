<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<style>
@keyframes fadeUp { from { opacity:0; transform:translateY(14px); } to { opacity:1; transform:none; } }
.anim-fade-up { animation: fadeUp .4s cubic-bezier(.22,.68,0,1.1) both; }
.status-draft      { background: var(--bs-secondary-bg); color: var(--bs-secondary-color); }
.status-scheduled  { background: #dbeafe; color: #1d4ed8; }
.status-ongoing    { background: #dcfce7; color: #166534; }
.status-completed  { background: #e0e7ff; color: #3730a3; }
.status-cancelled  { background: #fee2e2; color: #991b1b; }
[data-bs-theme="dark"] .status-scheduled { background:#1e3a5f; color:#93c5fd; }
[data-bs-theme="dark"] .status-ongoing   { background:#14532d; color:#86efac; }
[data-bs-theme="dark"] .status-completed { background:#1e1b4b; color:#a5b4fc; }
[data-bs-theme="dark"] .status-cancelled { background:#450a0a; color:#fca5a5; }
.comp-tag { font-size:.66rem; padding:.1rem .45rem; border-radius:.35rem; }
</style>
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php
$statusLabels = ['draft'=>'Draft','scheduled'=>'Dijadwalkan','ongoing'=>'Berjalan','completed'=>'Selesai','cancelled'=>'Dibatalkan'];

// Summary stats
$totalPrograms  = count($programs);
$scheduledCount = count(array_filter($programs, fn($p) => $p['status'] === 'scheduled'));
$ongoingCount   = count(array_filter($programs, fn($p) => $p['status'] === 'ongoing'));
$completedCount = count(array_filter($programs, fn($p) => $p['status'] === 'completed'));
$totalPeserta   = array_sum(array_column($programs, 'peserta_count'));
?>

<!-- Header -->
<div class="d-flex align-items-center justify-content-between mb-3 anim-fade-up" style="animation-delay:.05s">
    <div>
        <h4 class="fw-bold mb-0">Program Training</h4>
        <div class="text-muted small">Manajemen program pelatihan & pengembangan karyawan</div>
    </div>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
        <i class="bi bi-plus-lg me-1"></i>Tambah Program
    </button>
</div>

<!-- KPI -->
<div class="row g-3 mb-4 anim-fade-up" style="animation-delay:.1s">
    <div class="col-6 col-md-3">
        <div class="card p-3 text-center">
            <div class="text-muted small">Total Program</div>
            <div class="fw-bold fs-4"><?= $totalPrograms ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card p-3 text-center">
            <div class="text-muted small">Berjalan</div>
            <div class="fw-bold fs-4 text-success"><?= $ongoingCount ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card p-3 text-center">
            <div class="text-muted small">Dijadwalkan</div>
            <div class="fw-bold fs-4 text-primary"><?= $scheduledCount ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card p-3 text-center">
            <div class="text-muted small">Total Peserta</div>
            <div class="fw-bold fs-4"><?= $totalPeserta ?></div>
        </div>
    </div>
</div>

<!-- Filter -->
<div class="card mb-3 anim-fade-up" style="animation-delay:.13s">
    <div class="card-body py-2">
        <form method="GET" class="d-flex flex-wrap gap-2 align-items-end">
            <div>
                <label class="form-label mb-1 small">Tahun</label>
                <select name="tahun" class="form-select form-select-sm">
                    <option value="">Semua Tahun</option>
                    <?php foreach ($years as $y): ?>
                    <option value="<?= $y ?>" <?= $filterTahun == $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="form-label mb-1 small">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">Semua Status</option>
                    <?php foreach ($statusLabels as $k => $v): ?>
                    <option value="<?= $k ?>" <?= $filterStatus === $k ? 'selected' : '' ?>><?= $v ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-secondary">Filter</button>
                <?php if ($filterTahun || $filterStatus): ?>
                <a href="<?= base_url('people/training') ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Table -->
<?php if (empty($programs)): ?>
<div class="text-center py-5 anim-fade-up" style="animation-delay:.15s">
    <i class="bi bi-mortarboard" style="font-size:3rem;opacity:.25"></i>
    <p class="text-muted mt-3">Belum ada program training.</p>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
        <i class="bi bi-plus-lg me-1"></i>Tambah Program
    </button>
</div>
<?php else: ?>
<div class="card anim-fade-up" style="animation-delay:.15s">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle" id="programTable">
            <thead>
                <tr>
                    <th style="min-width:200px">Program</th>
                    <th>Tipe</th>
                    <th>Vendor / Trainer</th>
                    <th>Tanggal</th>
                    <th class="text-center">Peserta</th>
                    <th class="text-center">Avg Post Test</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($programs as $p):
                $statusClass = 'status-' . $p['status'];
            ?>
            <tr>
                <td>
                    <a href="<?= base_url('people/training/' . $p['id']) ?>" class="fw-semibold text-decoration-none">
                        <?= esc($p['nama']) ?>
                    </a>
                    <?php if ($p['lokasi']): ?>
                    <div class="text-muted" style="font-size:.73rem"><i class="bi bi-geo-alt me-1"></i><?= esc($p['lokasi']) ?></div>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="badge bg-<?= $p['tipe'] === 'internal' ? 'secondary' : 'primary' ?>">
                        <?= ucfirst($p['tipe']) ?>
                    </span>
                </td>
                <td class="text-muted" style="font-size:.83rem"><?= esc($p['vendor'] ?? '—') ?></td>
                <td style="font-size:.82rem">
                    <?php if ($p['tanggal_mulai']): ?>
                    <?= date('d M Y', strtotime($p['tanggal_mulai'])) ?>
                    <?php if ($p['tanggal_selesai'] && $p['tanggal_selesai'] !== $p['tanggal_mulai']): ?>
                    <br><span class="text-muted">s/d <?= date('d M Y', strtotime($p['tanggal_selesai'])) ?></span>
                    <?php endif; ?>
                    <?php else: ?>—<?php endif; ?>
                </td>
                <td class="text-center fw-semibold"><?= $p['peserta_count'] ?>
                    <?php if ($p['kuota']): ?><span class="text-muted fw-normal">/<?= $p['kuota'] ?></span><?php endif; ?>
                </td>
                <td class="text-center">
                    <?php if ($p['avg_post_test'] !== null): ?>
                    <span class="fw-semibold"><?= number_format((float)$p['avg_post_test'], 1) ?></span>
                    <?php if ($p['avg_pre_test'] !== null):
                        $imp = (float)$p['avg_post_test'] - (float)$p['avg_pre_test'];
                    ?>
                    <span class="text-<?= $imp >= 0 ? 'success' : 'danger' ?> small"><?= $imp >= 0 ? '+' : '' ?><?= number_format($imp, 1) ?></span>
                    <?php endif; ?>
                    <?php else: ?>—<?php endif; ?>
                </td>
                <td><span class="badge <?= $statusClass ?>"><?= $statusLabels[$p['status']] ?></span></td>
                <td>
                    <a href="<?= base_url('people/training/' . $p['id']) ?>" class="btn btn-xs btn-sm btn-outline-secondary py-0 px-2" style="font-size:.75rem">
                        Detail
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Add Modal -->
<?= view('people/training/_form_modal', [
    'modalId'     => 'addModal',
    'modalTitle'  => 'Tambah Program Training',
    'formAction'  => base_url('people/training/add'),
    'program'     => null,
    'compIds'     => [],
    'competencies'=> $competencies,
]) ?>

<?= $this->endSection() ?>
