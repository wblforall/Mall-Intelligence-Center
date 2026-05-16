<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="d-flex align-items-center gap-3 mb-4 fade-up" style="animation-delay:.05s">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-calendar-heart-fill me-2"></i>Hari Libur Nasional</h4>
        <div class="text-muted" style="font-size:.8rem">Kelola daftar hari libur untuk ditampilkan di Gantt Chart</div>
    </div>
    <div class="ms-auto d-flex gap-2">
        <form method="get" class="d-flex gap-2">
            <select name="year" class="form-select form-select-sm" onchange="this.form.submit()" style="width:auto">
                <?php for ($y = date('Y') - 1; $y <= date('Y') + 2; $y++): ?>
                <option value="<?= $y ?>" <?= $y == $year ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </form>
        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#bulkModal">
            <i class="bi bi-upload"></i> Import Massal
        </button>
    </div>
</div>

<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success alert-dismissible fade show py-2" role="alert">
    <?= esc(session()->getFlashdata('success')) ?>
    <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
<div class="alert alert-danger alert-dismissible fade show py-2" role="alert">
    <?= esc(session()->getFlashdata('error')) ?>
    <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row g-3 fade-up" style="animation-delay:.1s">
    <!-- Add form -->
    <div class="col-md-4">
        <div class="card border-0" style="background:var(--c-surface-1)">
            <div class="card-body">
                <h6 class="fw-semibold mb-3">Tambah Hari Libur</h6>
                <form method="post" action="<?= base_url('admin/holidays/store') ?>">
                    <?= csrf_field() ?>
                    <div class="mb-2">
                        <label class="form-label form-label-sm">Tanggal</label>
                        <input type="date" name="tanggal" class="form-control form-control-sm" required
                               value="<?= $year ?>-01-01">
                    </div>
                    <div class="mb-2">
                        <label class="form-label form-label-sm">Nama</label>
                        <input type="text" name="nama" class="form-control form-control-sm" required
                               placeholder="cth: Hari Kemerdekaan RI">
                    </div>
                    <div class="mb-3">
                        <label class="form-label form-label-sm">Jenis</label>
                        <select name="jenis" class="form-select form-select-sm">
                            <option value="nasional">Nasional</option>
                            <option value="bersama">Cuti Bersama</option>
                            <option value="lokal">Lokal</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary w-100">
                        <i class="bi bi-plus-lg"></i> Tambahkan
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- List -->
    <div class="col-md-8">
        <div class="card border-0" style="background:var(--c-surface-1)">
            <div class="card-body p-0">
                <?php if (empty($holidays)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-calendar-x fs-2 d-block mb-2"></i>
                    Belum ada hari libur untuk tahun <?= $year ?>.<br>
                    <small>Tambah satu per satu atau gunakan Import Massal.</small>
                </div>
                <?php else: ?>
                <table class="table table-hover mb-0" style="font-size:.82rem">
                    <thead>
                        <tr>
                            <th class="ps-3">Tanggal</th>
                            <th>Nama</th>
                            <th>Jenis</th>
                            <th class="pe-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $jenisColor = ['nasional' => 'danger', 'bersama' => 'warning text-dark', 'lokal' => 'secondary'];
                        $jenisLabel = ['nasional' => 'Nasional', 'bersama' => 'Cuti Bersama', 'lokal' => 'Lokal'];
                        $days       = ['Min','Sen','Sel','Rab','Kam','Jum','Sab'];
                        foreach ($holidays as $h):
                            $d   = new DateTime($h['tanggal']);
                            $fmt = $d->format('d M Y') . ' (' . $days[(int)$d->format('w')] . ')';
                        ?>
                        <tr>
                            <td class="ps-3"><?= esc($fmt) ?></td>
                            <td><?= esc($h['nama']) ?></td>
                            <td>
                                <span class="badge bg-<?= $jenisColor[$h['jenis']] ?? 'secondary' ?>">
                                    <?= $jenisLabel[$h['jenis']] ?? esc($h['jenis']) ?>
                                </span>
                            </td>
                            <td class="pe-3 text-end">
                                <form method="post" action="<?= base_url('admin/holidays/delete/' . $h['id']) ?>"
                                      style="display:inline"
                                      onsubmit="return confirm('Hapus <?= esc(addslashes($h['nama'])) ?>?')">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-link text-danger p-0">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Bulk import modal -->
<div class="modal fade" id="bulkModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="background:var(--c-surface-2)">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-semibold">Import Massal</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="<?= base_url('admin/holidays/bulk') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="year" value="<?= $year ?>">
                <div class="modal-body">
                    <p class="text-muted" style="font-size:.8rem">
                        Satu baris = satu hari libur.<br>
                        Format: <code>YYYY-MM-DD Nama Hari</code><br>
                        Tambah jenis (opsional): <code>2026-02-09 Imlek|bersama</code>
                    </p>
                    <textarea name="bulk_input" class="form-control font-monospace" rows="10"
                              placeholder="2026-01-01 Tahun Baru Masehi&#10;2026-01-27 Isra Mi'raj&#10;2026-02-09 Imlek|nasional"></textarea>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary">Import</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
