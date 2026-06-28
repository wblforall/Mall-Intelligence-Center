<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$statusLabel = ['draft' => 'Draft', 'aktif' => 'Aktif', 'selesai' => 'Selesai', 'batal' => 'Batal'];
$statusBadge = ['draft' => 'secondary', 'aktif' => 'primary', 'selesai' => 'success', 'batal' => 'danger'];
$mallLabel   = [1 => 'eWalk', 2 => 'Pentacity'];
?>

<div class="container-fluid py-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <nav aria-label="breadcrumb"><ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?= base_url('legal') ?>">Legal</a></li>
                <li class="breadcrumb-item active">Kontrak Sewa Pameran</li>
            </ol></nav>
            <h4 class="fw-bold mb-0"><i class="bi bi-calendar-event me-2 text-primary"></i>Kontrak Sewa Pameran</h4>
        </div>
        <?php if ($canEdit): ?>
        <a href="<?= base_url('legal/kontrak-pameran/new') ?>" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Tambah Kontrak</a>
        <?php endif; ?>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= session()->getFlashdata('success') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?= session()->getFlashdata('error') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <form method="get" class="card mb-3">
        <div class="card-body py-2">
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <input type="text" name="q" value="<?= esc($filters['q'] ?? '') ?>" class="form-control form-control-sm" placeholder="Cari nomor kontrak / event / penyelenggara…">
                </div>
                <div class="col-md-2">
                    <select name="mall_id" class="form-select form-select-sm">
                        <option value="">Semua Mall</option>
                        <option value="1" <?= ($filters['mall_id'] ?? '') === '1' ? 'selected' : '' ?>>eWalk</option>
                        <option value="2" <?= ($filters['mall_id'] ?? '') === '2' ? 'selected' : '' ?>>Pentacity</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Semua Status</option>
                        <?php foreach ($statusLabel as $v => $l): ?>
                        <option value="<?= $v ?>" <?= ($filters['status'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <button class="btn btn-sm btn-primary">Filter</button>
                    <a href="<?= base_url('legal/kontrak-pameran') ?>" class="btn btn-sm btn-light">Reset</a>
                </div>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="card-body p-0">
            <?php if (empty($rows)): ?>
            <p class="text-muted text-center py-4 mb-0">Belum ada data kontrak sewa pameran.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Nomor Kontrak</th>
                            <th>Event</th>
                            <th>Penyelenggara</th>
                            <th>Mall</th>
                            <th>Status</th>
                            <th>Tanggal</th>
                            <th class="text-end">Nilai</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $r): ?>
                        <?php
                        $s = $r['status'] ?? 'draft';
                        $tgl = '';
                        if ($r['tanggal_mulai'] && $r['tanggal_selesai']) {
                            $mulai   = new DateTime($r['tanggal_mulai']);
                            $selesai = new DateTime($r['tanggal_selesai']);
                            if ($mulai->format('Y') === $selesai->format('Y')) {
                                $tgl = $mulai->format('d M') . ' – ' . $selesai->format('d M Y');
                            } else {
                                $tgl = $mulai->format('d M Y') . ' – ' . $selesai->format('d M Y');
                            }
                        } elseif ($r['tanggal_mulai']) {
                            $tgl = date('d M Y', strtotime($r['tanggal_mulai']));
                        }
                        ?>
                        <tr>
                            <td>
                                <a href="<?= base_url('legal/kontrak-pameran/' . $r['id']) ?>" class="fw-medium text-decoration-none">
                                    <?= esc($r['nomor_kontrak']) ?>
                                </a>
                            </td>
                            <td><?= esc($r['nama_event']) ?></td>
                            <td class="text-muted small"><?= esc($r['nama_penyelenggara']) ?></td>
                            <td><?= $r['mall_id'] ? ($mallLabel[(int)$r['mall_id']] ?? '—') : '<span class="text-muted">—</span>' ?></td>
                            <td>
                                <span class="badge bg-<?= $statusBadge[$s] ?? 'secondary' ?>-subtle text-<?= $statusBadge[$s] ?? 'secondary' ?>">
                                    <?= $statusLabel[$s] ?? esc($s) ?>
                                </span>
                            </td>
                            <td class="small"><?= $tgl ?: '<span class="text-muted">—</span>' ?></td>
                            <td class="text-end">
                                <?= $r['nilai_sewa'] ? 'Rp ' . number_format((float)$r['nilai_sewa'], 0, ',', '.') : '<span class="text-muted">—</span>' ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
