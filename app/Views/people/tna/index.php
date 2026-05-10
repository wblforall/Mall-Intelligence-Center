<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<style>
@keyframes fadeUp { from { opacity:0; transform:translateY(14px); } to { opacity:1; transform:none; } }
.anim-fade-up { animation: fadeUp .4s cubic-bezier(.22,.68,0,1.1) both; }
.period-card  { border-radius: .85rem; transition: box-shadow .15s; }
.period-card:hover { box-shadow: 0 6px 24px rgba(0,0,0,.15); }
</style>

<div class="d-flex align-items-center justify-content-between mb-4 anim-fade-up" style="animation-delay:.05s">
    <div>
        <h4 class="fw-bold mb-0">TNA Assessment 360°</h4>
        <div class="text-muted small">Training Need Analysis — Penilaian kompetensi per individu</div>
    </div>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
        <i class="bi bi-plus-lg me-1"></i>Tambah Periode
    </button>
</div>

<?php if (empty($periods)): ?>
<div class="text-center py-5 anim-fade-up" style="animation-delay:.15s">
    <i class="bi bi-clipboard2-pulse" style="font-size:3rem;opacity:.25"></i>
    <p class="text-muted mt-3">Belum ada periode TNA. Mulai dengan membuat periode baru.</p>
</div>
<?php else: ?>
<div class="row g-3" id="periodGrid">
<?php foreach ($periods as $i => $p):
    $total = (int)$p['total_forms'];
    $done  = (int)$p['submitted_forms'];
    $pct   = $total > 0 ? round($done / $total * 100) : 0;
    $isOpen = $p['status'] === 'open';
?>
<div class="col-md-6 col-xl-4 anim-fade-up period-card-wrap" style="animation-delay:<?= (.1 + $i * .06) ?>s">
    <div class="card period-card h-100">
        <div class="card-body">
            <div class="d-flex align-items-start justify-content-between mb-2">
                <div>
                    <span class="badge <?= $isOpen ? 'bg-success' : 'bg-secondary' ?> mb-1"><?= $isOpen ? 'Open' : 'Closed' ?></span>
                    <h6 class="fw-semibold mb-0"><?= esc($p['nama']) ?></h6>
                    <div class="text-muted small"><?= $p['tahun'] ?></div>
                </div>
                <div class="dropdown">
                    <button class="btn btn-sm btn-light" data-bs-toggle="dropdown"><i class="bi bi-three-dots-vertical"></i></button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="<?= base_url('people/tna/period/' . $p['id']) ?>"><i class="bi bi-people me-2"></i>Detail Periode</a></li>
                        <li><a class="dropdown-item edit-btn" href="#" data-id="<?= $p['id'] ?>"
                               data-nama="<?= esc($p['nama']) ?>" data-tahun="<?= $p['tahun'] ?>"
                               data-mulai="<?= $p['tanggal_mulai'] ?>" data-selesai="<?= $p['tanggal_selesai'] ?>"
                               data-catatan="<?= esc($p['catatan'] ?? '') ?>"
                               data-ws="<?= (int)($p['weight_self']   ?? 20) ?>"
                               data-wa="<?= (int)($p['weight_atasan'] ?? 50) ?>"
                               data-wr="<?= (int)($p['weight_rekan']  ?? 30) ?>"
                               data-bs-toggle="modal" data-bs-target="#editModal">
                               <i class="bi bi-pencil me-2"></i>Edit</a></li>
                        <li><a class="dropdown-item" href="<?= base_url('people/tna/periods/' . $p['id'] . '/toggle-close') ?>"
                               onclick="return confirm('Ubah status periode ini?')">
                               <i class="bi bi-<?= $isOpen ? 'lock' : 'unlock' ?> me-2"></i><?= $isOpen ? 'Tutup Periode' : 'Buka Kembali' ?></a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?= base_url('people/tna/periods/' . $p['id'] . '/delete') ?>"
                               onclick="return confirm('Hapus periode ini beserta semua data assessment di dalamnya?')">
                               <i class="bi bi-trash me-2"></i>Hapus</a></li>
                    </ul>
                </div>
            </div>

            <?php if ($p['tanggal_mulai']): ?>
            <div class="text-muted small mb-2">
                <i class="bi bi-calendar3 me-1"></i>
                <?= date('d M Y', strtotime($p['tanggal_mulai'])) ?> – <?= $p['tanggal_selesai'] ? date('d M Y', strtotime($p['tanggal_selesai'])) : '…' ?>
            </div>
            <?php endif; ?>

            <div class="d-flex gap-3 mb-3">
                <div class="text-center">
                    <div class="fw-bold fs-5"><?= $p['employee_count'] ?></div>
                    <div class="text-muted" style="font-size:.7rem">Karyawan</div>
                </div>
                <div class="text-center">
                    <div class="fw-bold fs-5"><?= $done ?>/<?= $total ?></div>
                    <div class="text-muted" style="font-size:.7rem">Form Submitted</div>
                </div>
            </div>

            <div class="progress" style="height:6px;border-radius:3px">
                <div class="progress-bar bg-<?= $pct == 100 ? 'success' : ($pct >= 50 ? 'primary' : 'warning') ?>"
                     style="width:<?= $pct ?>%"></div>
            </div>
            <div class="text-muted mt-1" style="font-size:.7rem"><?= $pct ?>% selesai</div>
        </div>
        <div class="card-footer bg-transparent border-top-0 pt-0 pb-3 px-3">
            <a href="<?= base_url('people/tna/period/' . $p['id']) ?>" class="btn btn-sm btn-outline-primary w-100">
                <i class="bi bi-arrow-right-circle me-1"></i>Buka Detail
            </a>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="<?= base_url('people/tna/periods/add') ?>">
            <?= csrf_field() ?>
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Periode TNA</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Periode <span class="text-danger">*</span></label>
                        <input type="text" name="nama" class="form-control" placeholder="cth: TNA Q1 2026" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col">
                            <label class="form-label">Tahun <span class="text-danger">*</span></label>
                            <input type="number" name="tahun" class="form-control" value="<?= date('Y') ?>" min="2020" max="2050" required>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col">
                            <label class="form-label">Tanggal Mulai</label>
                            <input type="date" name="tanggal_mulai" class="form-control">
                        </div>
                        <div class="col">
                            <label class="form-label">Tanggal Selesai</label>
                            <input type="date" name="tanggal_selesai" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Catatan</label>
                        <textarea name="catatan" class="form-control" rows="2"></textarea>
                    </div>
                    <hr class="my-2">
                    <label class="form-label small fw-semibold">Bobot Penilaian <span class="text-muted fw-normal">(total harus = 100)</span></label>
                    <div class="row g-2">
                        <div class="col-4">
                            <label class="form-label small text-muted">Self (%)</label>
                            <input type="number" name="weight_self" class="form-control form-control-sm add-weight" value="20" min="0" max="100">
                        </div>
                        <div class="col-4">
                            <label class="form-label small text-muted">Atasan (%)</label>
                            <input type="number" name="weight_atasan" class="form-control form-control-sm add-weight" value="50" min="0" max="100">
                        </div>
                        <div class="col-4">
                            <label class="form-label small text-muted">Rekan (%)</label>
                            <input type="number" name="weight_rekan" class="form-control form-control-sm add-weight" value="30" min="0" max="100">
                        </div>
                    </div>
                    <div class="form-text" id="addWeightSum">Total: <strong>100</strong></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" id="editForm" action="">
            <?= csrf_field() ?>
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Periode TNA</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Periode <span class="text-danger">*</span></label>
                        <input type="text" name="nama" id="editNama" class="form-control" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col">
                            <label class="form-label">Tahun <span class="text-danger">*</span></label>
                            <input type="number" name="tahun" id="editTahun" class="form-control" min="2020" max="2050" required>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col">
                            <label class="form-label">Tanggal Mulai</label>
                            <input type="date" name="tanggal_mulai" id="editMulai" class="form-control">
                        </div>
                        <div class="col">
                            <label class="form-label">Tanggal Selesai</label>
                            <input type="date" name="tanggal_selesai" id="editSelesai" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Catatan</label>
                        <textarea name="catatan" id="editCatatan" class="form-control" rows="2"></textarea>
                    </div>
                    <hr class="my-2">
                    <label class="form-label small fw-semibold">Bobot Penilaian <span class="text-muted fw-normal">(total harus = 100)</span></label>
                    <div class="row g-2">
                        <div class="col-4">
                            <label class="form-label small text-muted">Self (%)</label>
                            <input type="number" name="weight_self" id="editWeightSelf" class="form-control form-control-sm edit-weight" min="0" max="100">
                        </div>
                        <div class="col-4">
                            <label class="form-label small text-muted">Atasan (%)</label>
                            <input type="number" name="weight_atasan" id="editWeightAtasan" class="form-control form-control-sm edit-weight" min="0" max="100">
                        </div>
                        <div class="col-4">
                            <label class="form-label small text-muted">Rekan (%)</label>
                            <input type="number" name="weight_rekan" id="editWeightRekan" class="form-control form-control-sm edit-weight" min="0" max="100">
                        </div>
                    </div>
                    <div class="form-text" id="editWeightSum">Total: <strong>100</strong></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
function calcWeightSum(selector, sumId) {
    const inputs = document.querySelectorAll(selector);
    const sum = [...inputs].reduce((t, i) => t + (parseInt(i.value) || 0), 0);
    const el  = document.getElementById(sumId);
    if (el) {
        el.innerHTML = 'Total: <strong class="' + (sum === 100 ? 'text-success' : 'text-danger') + '">' + sum + '</strong>';
    }
}
document.querySelectorAll('.add-weight').forEach(i => i.addEventListener('input', () => calcWeightSum('.add-weight', 'addWeightSum')));
document.querySelectorAll('.edit-weight').forEach(i => i.addEventListener('input', () => calcWeightSum('.edit-weight', 'editWeightSum')));

document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('editForm').action      = '<?= base_url('people/tna/periods/') ?>' + this.dataset.id + '/edit';
        document.getElementById('editNama').value       = this.dataset.nama;
        document.getElementById('editTahun').value      = this.dataset.tahun;
        document.getElementById('editMulai').value      = this.dataset.mulai;
        document.getElementById('editSelesai').value    = this.dataset.selesai;
        document.getElementById('editCatatan').value    = this.dataset.catatan;
        document.getElementById('editWeightSelf').value   = this.dataset.ws;
        document.getElementById('editWeightAtasan').value = this.dataset.wa;
        document.getElementById('editWeightRekan').value  = this.dataset.wr;
        calcWeightSum('.edit-weight', 'editWeightSum');
    });
});
</script>
<?= $this->endSection() ?>
