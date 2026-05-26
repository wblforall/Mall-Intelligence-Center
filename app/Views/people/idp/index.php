<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<style>
@keyframes fadeUp { from { opacity:0; transform:translateY(14px); } to { opacity:1; transform:none; } }
.anim-fade-up { animation: fadeUp .4s cubic-bezier(.22,.68,0,1.1) both; }
.idp-card { border-radius:.85rem; transition:box-shadow .15s; }
.idp-card:hover { box-shadow:0 6px 24px rgba(0,0,0,.13); }
</style>

<?php
$statusLabel = ['draft'=>'Draft','aktif'=>'Aktif','selesai'=>'Selesai','dibatalkan'=>'Dibatalkan'];
$statusColor = ['draft'=>'secondary','aktif'=>'primary','selesai'=>'success','dibatalkan'=>'danger'];
$prefill     = $prefill ?? [];
$openModal   = $openModal ?? '';
?>

<div class="d-flex align-items-center justify-content-between mb-4 anim-fade-up" style="animation-delay:.05s">
    <div>
        <h4 class="fw-bold mb-0">Individual Development Plan</h4>
        <div class="text-muted small">Rencana pengembangan individu berbasis kompetensi</div>
    </div>
    <?php if ($canEdit): ?>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
        <i class="bi bi-plus-lg me-1"></i>Buat IDP
    </button>
    <?php endif; ?>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4 anim-fade-up" style="animation-delay:.08s">
    <div class="col-6 col-md-3">
        <div class="card text-center py-3">
            <div class="fw-bold fs-4"><?= $stats['total'] ?></div>
            <div class="text-muted small">Total</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center py-3 border-primary">
            <div class="fw-bold fs-4 text-primary"><?= $stats['aktif'] ?></div>
            <div class="text-muted small">Aktif</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center py-3 border-success">
            <div class="fw-bold fs-4 text-success"><?= $stats['selesai'] ?></div>
            <div class="text-muted small">Selesai</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center py-3 border-secondary">
            <div class="fw-bold fs-4 text-secondary"><?= $stats['draft'] ?></div>
            <div class="text-muted small">Draft</div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4 anim-fade-up" style="animation-delay:.1s">
    <div class="card-body py-2">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small mb-1">Karyawan</label>
                <select name="employee_id" class="form-select form-select-sm">
                    <option value="">Semua Karyawan</option>
                    <?php foreach ($employees as $e): ?>
                    <option value="<?= $e['id'] ?>" <?= ($filters['employee_id'] ?? '') == $e['id'] ? 'selected' : '' ?>>
                        <?= esc($e['nama']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Departemen</label>
                <select name="dept_id" class="form-select form-select-sm">
                    <option value="">Semua Dept</option>
                    <?php foreach ($departments as $d): ?>
                    <option value="<?= $d['id'] ?>" <?= ($filters['dept_id'] ?? '') == $d['id'] ? 'selected' : '' ?>>
                        <?= esc($d['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">Semua Status</option>
                    <?php foreach ($statusLabel as $k => $v): ?>
                    <option value="<?= $k ?>" <?= ($filters['status'] ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">Tahun</label>
                <input type="number" name="tahun" class="form-control form-control-sm" value="<?= esc($filters['tahun'] ?? '') ?>" placeholder="Semua">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-outline-primary flex-fill">Filter</button>
                <a href="<?= base_url('people/idp') ?>" class="btn btn-sm btn-outline-secondary flex-fill">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- IDP List -->
<?php if (empty($plans)): ?>
<div class="text-center text-muted py-5 anim-fade-up">
    <i class="bi bi-journal-text fs-1 d-block mb-2 opacity-25"></i>
    Belum ada IDP. <?= $canEdit ? 'Klik <strong>Buat IDP</strong> untuk memulai.' : '' ?>
</div>
<?php else: ?>
<div class="row g-3 anim-fade-up" style="animation-delay:.12s">
<?php foreach ($plans as $p):
    $pct = $p['item_count'] > 0 ? round($p['item_selesai'] / $p['item_count'] * 100) : 0;
?>
<div class="col-md-6 col-xl-4">
    <div class="card idp-card h-100">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="badge bg-<?= $statusColor[$p['status']] ?? 'secondary' ?>">
                    <?= $statusLabel[$p['status']] ?? $p['status'] ?>
                </span>
                <?php if ($p['persetujuan_atasan'] === 'pending' && $p['status'] === 'draft'): ?>
                <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i>Menunggu Persetujuan</span>
                <?php elseif ($p['persetujuan_atasan'] === 'menolak'): ?>
                <span class="badge bg-danger">Ditolak Atasan</span>
                <?php endif; ?>
            </div>
            <a href="<?= base_url('people/idp/' . $p['id']) ?>" class="fw-semibold text-dark text-decoration-none d-block mb-1">
                <?= esc($p['periode_label']) ?>
            </a>
            <div class="small text-muted mb-1"><?= esc($p['employee_nama']) ?> · <?= esc($p['dept_name']) ?></div>
            <div class="small text-muted mb-3">Tahun <?= $p['tahun'] ?> · <?= (int)$p['item_count'] ?> goal</div>
            <div class="d-flex align-items-center gap-2">
                <div class="progress flex-fill" style="height:6px">
                    <div class="progress-bar bg-success" style="width:<?= $pct ?>%"></div>
                </div>
                <span class="small text-muted"><?= $pct ?>%</span>
            </div>
            <div class="mt-2 small text-muted"><?= (int)$p['item_selesai'] ?>/<?= (int)$p['item_count'] ?> goal selesai</div>
        </div>
        <div class="card-footer bg-transparent d-flex justify-content-between align-items-center py-2">
            <small class="text-muted"><?= esc($p['created_by_name'] ?? '-') ?></small>
            <a href="<?= base_url('people/idp/' . $p['id']) ?>" class="btn btn-outline-primary btn-sm">
                Detail <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($canEdit): ?>
<!-- Modal: Buat IDP -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" action="<?= base_url('people/idp/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Buat IDP Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Karyawan <span class="text-danger">*</span></label>
                            <select name="employee_id" class="form-select" required>
                                <option value="">— Pilih Karyawan —</option>
                                <?php foreach ($employees as $e): ?>
                                <option value="<?= $e['id'] ?>" <?= ($prefill['employee_id'] ?? '') == $e['id'] ? 'selected' : '' ?>>
                                    <?= esc($e['nama']) ?> (<?= esc($e['dept_name'] ?? '') ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Berdasarkan Hasil TNA <span class="text-muted small">(opsional)</span></label>
                            <select name="tna_period_id" class="form-select">
                                <option value="">— Tidak terkait TNA —</option>
                                <?php foreach ($tnaPeriods as $tp): ?>
                                <option value="<?= $tp['id'] ?>" <?= ($prefill['tna_period_id'] ?? '') == $tp['id'] ? 'selected' : '' ?>>
                                    <?= esc($tp['nama']) ?> (<?= $tp['tahun'] ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Label Periode <span class="text-danger">*</span></label>
                            <input type="text" name="periode_label" class="form-control" required
                                   placeholder="contoh: 2026 Semester 1"
                                   value="<?= esc($prefill['periode_label'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Tahun <span class="text-danger">*</span></label>
                            <input type="number" name="tahun" class="form-control" required
                                   value="<?= esc($prefill['tahun'] ?? date('Y')) ?>" min="2020" max="2099">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Tujuan Karir / Aspirasi</label>
                            <textarea name="tujuan_karir" class="form-control" rows="2"
                                      placeholder="Deskripsikan tujuan pengembangan karir karyawan ini..."></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Catatan</label>
                            <textarea name="catatan" class="form-control" rows="2"></textarea>
                        </div>
                    </div>

                    <!-- Items -->
                    <hr class="my-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="fw-semibold">Goal / Area Pengembangan</div>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="addItemBtn">
                            <i class="bi bi-plus-lg me-1"></i>Tambah Goal
                        </button>
                    </div>
                    <div id="itemsContainer">
                        <?php if (! empty($prefill['gapItems'])): ?>
                        <?php foreach ($prefill['gapItems'] as $gi): ?>
                        <div class="card mb-2 item-row">
                            <div class="card-body py-2">
                                <div class="row g-2 align-items-center">
                                    <div class="col-md-5">
                                        <input type="hidden" name="item_competency_id[]" value="<?= (int)$gi['competency_id'] ?>">
                                        <input type="text" name="item_judul[]" class="form-control form-control-sm"
                                               value="<?= esc($gi['competency_nama']) ?>" placeholder="Nama goal" required>
                                        <small class="text-muted">Dari TNA · Gap kompetensi</small>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small mb-0">Level Saat Ini</label>
                                        <input type="number" name="item_level_saat_ini[]" class="form-control form-control-sm"
                                               min="1" max="5" step="0.01" value="<?= round($gi['level_saat_ini'], 1) ?>">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small mb-0">Target Level</label>
                                        <input type="number" name="item_level_target[]" class="form-control form-control-sm"
                                               min="1" max="5" value="<?= (int)$gi['level_target'] ?>">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small mb-0">Deadline</label>
                                        <input type="date" name="item_deadline[]" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-md-1 text-end">
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-item-btn">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                    <div class="col-12">
                                        <input type="text" name="item_langkah_aksi[]" class="form-control form-control-sm"
                                               placeholder="Langkah aksi yang akan dilakukan">
                                        <input type="hidden" name="item_sumber_daya[]" value="">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div id="emptyItemsMsg" class="text-muted small text-center py-2 <?= ! empty($prefill['gapItems']) ? 'd-none' : '' ?>">
                        Belum ada goal. Klik <strong>Tambah Goal</strong> atau buat IDP dari hasil TNA untuk otomatis.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan & Kirim ke Atasan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<template id="itemTemplate">
    <div class="card mb-2 item-row">
        <div class="card-body py-2">
            <div class="row g-2 align-items-center">
                <div class="col-md-5">
                    <input type="hidden" name="item_competency_id[]" value="">
                    <input type="text" name="item_judul[]" class="form-control form-control-sm" placeholder="Nama goal" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-0">Level Saat Ini</label>
                    <input type="number" name="item_level_saat_ini[]" class="form-control form-control-sm" min="1" max="5" step="0.01">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-0">Target Level</label>
                    <input type="number" name="item_level_target[]" class="form-control form-control-sm" min="1" max="5">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-0">Deadline</label>
                    <input type="date" name="item_deadline[]" class="form-control form-control-sm">
                </div>
                <div class="col-md-1 text-end">
                    <button type="button" class="btn btn-sm btn-outline-danger remove-item-btn">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
                <div class="col-12">
                    <input type="text" name="item_langkah_aksi[]" class="form-control form-control-sm" placeholder="Langkah aksi">
                    <input type="hidden" name="item_sumber_daya[]" value="">
                </div>
            </div>
        </div>
    </div>
</template>

<script>
document.getElementById('addItemBtn').addEventListener('click', function () {
    const tpl = document.getElementById('itemTemplate').content.cloneNode(true);
    document.getElementById('itemsContainer').appendChild(tpl);
    document.getElementById('emptyItemsMsg').classList.add('d-none');
});
document.getElementById('itemsContainer').addEventListener('click', function (e) {
    if (e.target.closest('.remove-item-btn')) {
        e.target.closest('.item-row').remove();
        if (! document.querySelector('.item-row')) {
            document.getElementById('emptyItemsMsg').classList.remove('d-none');
        }
    }
});
<?php if ($openModal === 'create'): ?>
document.addEventListener('DOMContentLoaded', function () {
    new bootstrap.Modal(document.getElementById('addModal')).show();
});
<?php endif; ?>
</script>
<?php endif; ?>

<?= $this->endSection() ?>
