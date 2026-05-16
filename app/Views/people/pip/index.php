<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<style>
@keyframes fadeUp { from { opacity:0; transform:translateY(14px); } to { opacity:1; transform:none; } }
.anim-fade-up { animation: fadeUp .4s cubic-bezier(.22,.68,0,1.1) both; }
.pip-card { border-radius:.85rem; transition:box-shadow .15s; }
.pip-card:hover { box-shadow:0 6px 24px rgba(0,0,0,.13); }
</style>

<?php
$statusLabel = ['draft'=>'Draft','menunggu_persetujuan'=>'Menunggu Persetujuan','aktif'=>'Aktif','selesai'=>'Selesai','diperpanjang'=>'Diperpanjang','dihentikan'=>'Dihentikan'];
$statusColor = ['draft'=>'secondary','menunggu_persetujuan'=>'info','aktif'=>'primary','selesai'=>'success','diperpanjang'=>'warning','dihentikan'=>'danger'];
$progresColor = ['baik'=>'success','cukup'=>'warning','kurang'=>'danger'];
$frekLabel = ['mingguan'=>'Mingguan','2mingguan'=>'2 Mingguan','bulanan'=>'Bulanan'];
?>

<div class="d-flex align-items-center justify-content-between mb-4 anim-fade-up" style="animation-delay:.05s">
    <div>
        <h4 class="fw-bold mb-0">Performance Improvement Plan</h4>
        <div class="text-muted small">Rencana peningkatan performa karyawan</div>
    </div>
    <?php if ($canEdit): ?>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
        <i class="bi bi-plus-lg me-1"></i>Buat PIP
    </button>
    <?php endif; ?>
</div>

<?php if (! empty($reviewAlerts)): ?>
<div class="alert alert-warning alert-dismissible fade show anim-fade-up mb-4" style="animation-delay:.08s">
    <div class="fw-semibold mb-2"><i class="bi bi-bell-fill me-2"></i>Review PIP Jatuh Tempo</div>
    <ul class="mb-0 ps-3">
    <?php foreach ($reviewAlerts as $p):
        $next = \App\Models\PipPlanModel::nextReviewDate($p);
        $sisa = (int)ceil((strtotime($next) - time()) / 86400);
    ?>
    <li>
        <a href="<?= base_url('people/pip/' . $p['id']) ?>" class="fw-semibold text-dark"><?= esc($p['judul']) ?></a>
        — <?= esc($p['employee_nama']) ?>
        <span class="ms-1 badge <?= $sisa < 0 ? 'bg-danger' : 'bg-warning text-dark' ?>">
            <?= $sisa < 0 ? 'Terlambat ' . abs($sisa) . ' hari' : ($sisa === 0 ? 'Hari ini' : $sisa . ' hari lagi') ?>
        </span>
        <span class="text-muted small ms-1">(<?= $frekLabel[$p['frekuensi_review'] ?? 'mingguan'] ?>)</span>
    </li>
    <?php endforeach; ?>
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- KPI Cards -->
<div class="row g-3 mb-4 anim-fade-up" style="animation-delay:.1s">
    <div class="col-6 col-md-2">
        <div class="card text-center py-3">
            <div class="fw-bold fs-4"><?= $stats['total'] ?></div>
            <div class="text-muted small">Total</div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card text-center py-3 border-info">
            <div class="fw-bold fs-4 text-info"><?= $stats['menunggu_persetujuan'] ?></div>
            <div class="text-muted small">Menunggu</div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card text-center py-3 border-primary">
            <div class="fw-bold fs-4 text-primary"><?= $stats['aktif'] ?></div>
            <div class="text-muted small">Aktif</div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card text-center py-3 border-warning">
            <div class="fw-bold fs-4 text-warning"><?= $stats['diperpanjang'] ?></div>
            <div class="text-muted small">Diperpanjang</div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card text-center py-3 border-success">
            <div class="fw-bold fs-4 text-success"><?= $stats['selesai'] ?></div>
            <div class="text-muted small">Selesai</div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card text-center py-3 border-danger">
            <div class="fw-bold fs-4 text-danger"><?= $stats['dihentikan'] ?></div>
            <div class="text-muted small">Dihentikan</div>
        </div>
    </div>
</div>

<!-- Filter -->
<div class="card mb-4 anim-fade-up" style="animation-delay:.15s">
    <div class="card-body py-2">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm">
                    <option value="">Semua Status</option>
                    <?php foreach ($statusLabel as $k => $v): ?>
                    <option value="<?= $k ?>" <?= ($filters['status'] === $k) ? 'selected' : '' ?>><?= $v ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select name="dept_id" class="form-select form-select-sm">
                    <option value="">Semua Departemen</option>
                    <?php foreach ($departments as $d): ?>
                    <option value="<?= $d['id'] ?>" <?= ($filters['dept_id'] == $d['id']) ? 'selected' : '' ?>><?= esc($d['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-funnel me-1"></i>Filter
                </button>
                <?php if ($filters['status'] || $filters['dept_id'] || $filters['employee_id']): ?>
                <a href="<?= base_url('people/pip') ?>" class="btn btn-sm btn-link text-muted">Reset</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Table -->
<?php if (empty($plans)): ?>
<div class="text-center py-5 anim-fade-up" style="animation-delay:.2s">
    <i class="bi bi-clipboard2-x" style="font-size:3rem;opacity:.25"></i>
    <p class="text-muted mt-3">Belum ada PIP<?= $filters['status'] ? ' dengan status "' . $statusLabel[$filters['status']] . '"' : '' ?>.</p>
</div>
<?php else: ?>
<div class="card anim-fade-up" style="animation-delay:.2s">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Karyawan</th>
                    <th>Judul PIP</th>
                    <th>Periode</th>
                    <th>Item</th>
                    <th>Frekuensi</th>
                    <th>Review Berikutnya</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($plans as $p): ?>
            <tr>
                <td>
                    <div class="fw-semibold"><?= esc($p['employee_nama'] ?? '-') ?></div>
                    <div class="text-muted small"><?= esc($p['jabatan'] ?? '') ?> · <?= esc($p['dept_name'] ?? '') ?></div>
                </td>
                <td><?= esc($p['judul']) ?></td>
                <td class="small text-nowrap">
                    <?= date('d M Y', strtotime($p['tanggal_mulai'])) ?><br>
                    <span class="text-muted">s/d <?= date('d M Y', strtotime($p['tanggal_selesai'])) ?></span>
                    <?php
                    $sisa = (int)ceil((strtotime($p['tanggal_selesai']) - time()) / 86400);
                    if (in_array($p['status'], ['aktif','diperpanjang']) && $sisa <= 7 && $sisa >= 0):
                    ?>
                    <br><span class="badge bg-warning text-dark"><?= $sisa ?> hari lagi</span>
                    <?php elseif (in_array($p['status'], ['aktif','diperpanjang']) && $sisa < 0): ?>
                    <br><span class="badge bg-danger">Lewat <?= abs($sisa) ?> hari</span>
                    <?php endif; ?>
                </td>
                <td class="text-center"><?= $p['item_count'] ?></td>
                <td class="small text-muted"><?= $frekLabel[$p['frekuensi_review'] ?? 'mingguan'] ?></td>
                <td class="small text-nowrap">
                <?php if (in_array($p['status'], ['aktif','diperpanjang'])):
                    $next = \App\Models\PipPlanModel::nextReviewDate($p);
                    $sisa = (int)ceil((strtotime($next) - time()) / 86400);
                ?>
                    <?= date('d M Y', strtotime($next)) ?>
                    <?php if ($sisa < 0): ?>
                    <br><span class="badge bg-danger" style="font-size:.65rem">Terlambat <?= abs($sisa) ?> hr</span>
                    <?php elseif ($sisa <= 2): ?>
                    <br><span class="badge bg-warning text-dark" style="font-size:.65rem"><?= $sisa === 0 ? 'Hari ini' : $sisa . ' hari lagi' ?></span>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="text-muted">—</span>
                <?php endif; ?>
                </td>
                <td><span class="badge bg-<?= $statusColor[$p['status']] ?>"><?= $statusLabel[$p['status']] ?></span></td>
                <td class="text-end">
                    <a href="<?= base_url('people/pip/' . $p['id']) ?>" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-eye"></i>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Modal Buat PIP -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form method="post" action="<?= base_url('people/pip/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Buat PIP Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Karyawan <span class="text-danger">*</span></label>
                            <select name="employee_id" class="form-select" required>
                                <option value="">-- Pilih Karyawan --</option>
                                <?php foreach ($employees as $e): if (($e['status'] ?? 'aktif') !== 'aktif') continue; ?>
                                <option value="<?= $e['id'] ?>"><?= esc($e['nama']) ?> — <?= esc($e['jabatan'] ?? '') ?> (<?= esc($e['dept_name'] ?? '') ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Judul PIP <span class="text-danger">*</span></label>
                            <input type="text" name="judul" class="form-control" required placeholder="cth: PIP Q2 2026 — Ketepatan Waktu">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tanggal Mulai <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal_mulai" class="form-control" required value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tanggal Selesai <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal_selesai" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Frekuensi Review</label>
                            <select name="frekuensi_review" class="form-select">
                                <option value="mingguan">Mingguan (tiap 7 hari)</option>
                                <option value="2mingguan">2 Mingguan (tiap 14 hari)</option>
                                <option value="bulanan">Bulanan (tiap 30 hari)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Tingkat Surat Peringatan</label>
                            <select name="level_sp" class="form-select">
                                <option value="none">Tanpa SP</option>
                                <option value="sp1">SP 1</option>
                                <option value="sp2">SP 2</option>
                                <option value="sp3">SP 3</option>
                                <option value="phk">PHK</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Alasan / Latar Belakang</label>
                            <textarea name="alasan" class="form-control" rows="3" placeholder="Deskripsikan masalah performa yang menjadi dasar PIP ini…"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Dukungan Perusahaan</label>
                            <textarea name="dukungan" class="form-control" rows="2" placeholder="cth: Coaching mingguan, pelatihan komunikasi, pendampingan atasan…"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Konsekuensi jika Tidak Tercapai</label>
                            <textarea name="konsekuensi" class="form-control" rows="2" placeholder="cth: Akan dilanjutkan ke SP 2 / proses PHK sesuai ketentuan…"></textarea>
                        </div>
                    </div>

                    <hr>
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="fw-semibold">Item Perbaikan</div>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="addItemBtn">
                            <i class="bi bi-plus-lg me-1"></i>Tambah Item
                        </button>
                    </div>
                    <div id="itemsContainer">
                        <div class="item-row card card-body mb-2 p-3">
                            <?= $this->include('people/pip/_item_row') ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan PIP</button>
                </div>
            </form>
        </div>
    </div>
</div>

<template id="itemRowTemplate">
    <div class="item-row card card-body mb-2 p-3">
        <?= $this->include('people/pip/_item_row') ?>
    </div>
</template>

<script>
const ASPEK_DATA = <?= json_encode(array_map(fn($a) => [
    'id'      => $a['id'],
    'aspek'   => $a['aspek'],
    'kategori'=> $a['kategori'] ?? '',
    'target'  => $a['target_default'] ?? '',
    'metrik'  => $a['metrik_default'] ?? '',
], $aspekMaster)) ?>;

function buildAspekOptions() {
    const grouped = {};
    ASPEK_DATA.forEach(a => {
        const kat = a.kategori || 'Lainnya';
        if (!grouped[kat]) grouped[kat] = [];
        grouped[kat].push(a);
    });
    let html = '<option value="">-- Pilih Aspek --</option>';
    Object.entries(grouped).forEach(([kat, items]) => {
        html += `<optgroup label="${kat}">`;
        items.forEach(a => { html += `<option value="${a.aspek}" data-target="${a.target}" data-metrik="${a.metrik}">${a.aspek}</option>`; });
        html += `<option value="__lainnya__">— Lainnya (ketik manual)</option></optgroup>`;
    });
    return html;
}

function populateSelects(container) {
    container.querySelectorAll('.aspek-select').forEach(sel => {
        if (sel.options.length <= 1) sel.innerHTML = buildAspekOptions();
    });
}

function autoFillItem(sel) {
    const row = sel.closest('.row');
    const custom = row.querySelector('.aspek-custom');
    if (sel.value === '__lainnya__') {
        custom.classList.remove('d-none');
        custom.required = true;
        row.querySelector('[name="target[]"]').value = '';
        row.querySelector('[name="metrik[]"]').value = '';
    } else {
        custom.classList.add('d-none');
        custom.required = false;
        const opt = sel.selectedOptions[0];
        if (opt) {
            row.querySelector('[name="target[]"]').value = opt.dataset.target || '';
            row.querySelector('[name="metrik[]"]').value = opt.dataset.metrik || '';
        }
    }
}

document.getElementById('addItemBtn').addEventListener('click', function() {
    const tpl = document.getElementById('itemRowTemplate').innerHTML;
    document.getElementById('itemsContainer').insertAdjacentHTML('beforeend', tpl);
    populateSelects(document.getElementById('itemsContainer'));
});

document.getElementById('itemsContainer').addEventListener('click', function(e) {
    if (e.target.closest('.remove-item-btn')) {
        const rows = document.querySelectorAll('#itemsContainer .item-row');
        if (rows.length > 1) e.target.closest('.item-row').remove();
    }
});

// Populate on page load
populateSelects(document.getElementById('itemsContainer'));

// Handle aspek_custom sebagai aspek value sebelum submit
document.querySelector('#addModal form').addEventListener('submit', function() {
    this.querySelectorAll('.aspek-select').forEach(sel => {
        if (sel.value === '__lainnya__') {
            const custom = sel.closest('.row').querySelector('.aspek-custom');
            sel.value = custom.value;
        }
    });
});
</script>

<?= $this->endSection() ?>
