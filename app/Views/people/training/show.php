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
.kehadiran-hadir      { color: var(--bs-success); }
.kehadiran-tidak_hadir { color: var(--bs-danger); }
.kehadiran-registered  { color: var(--bs-secondary-color); }
.kehadiran-dibatalkan  { color: var(--bs-warning); }
</style>
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php
$statusLabels    = ['draft'=>'Draft','scheduled'=>'Dijadwalkan','ongoing'=>'Berjalan','completed'=>'Selesai','cancelled'=>'Dibatalkan'];
$kehadiranLabels = ['registered'=>'Terdaftar','hadir'=>'Hadir','tidak_hadir'=>'Tidak Hadir','dibatalkan'=>'Dibatalkan'];
$improvement     = ($avgPost !== null && $avgPre !== null) ? round($avgPost - $avgPre, 1) : null;
?>

<!-- Header -->
<div class="d-flex align-items-center gap-2 mb-3 anim-fade-up" style="animation-delay:.05s">
    <a href="<?= base_url('people/training') ?>" class="btn btn-sm btn-light"><i class="bi bi-arrow-left"></i></a>
    <div class="flex-grow-1 min-w-0">
        <div class="text-muted small">Program Training</div>
        <h5 class="fw-bold mb-0 text-truncate"><?= esc($program['nama']) ?></h5>
    </div>
    <span class="badge status-<?= $program['status'] ?>"><?= $statusLabels[$program['status']] ?></span>
    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal">
        <i class="bi bi-pencil me-1"></i>Edit
    </button>
    <a href="<?= base_url('people/training/' . $program['id'] . '/delete') ?>"
       class="btn btn-sm btn-outline-danger"
       onclick="return confirm('Hapus program ini beserta semua data peserta?')">
        <i class="bi bi-trash"></i>
    </a>
</div>

<!-- Program Info -->
<div class="row g-3 mb-4 anim-fade-up" style="animation-delay:.1s">
    <div class="col-md-8">
        <div class="card h-100">
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-sm-6">
                        <div class="text-muted small">Tipe</div>
                        <div class="fw-medium"><span class="badge bg-<?= $program['tipe'] === 'internal' ? 'secondary' : 'primary' ?>"><?= ucfirst($program['tipe']) ?></span></div>
                    </div>
                    <?php if ($program['vendor']): ?>
                    <div class="col-sm-6">
                        <div class="text-muted small">Vendor / Trainer</div>
                        <div class="fw-medium"><?= esc($program['vendor']) ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if ($program['tanggal_mulai']): ?>
                    <div class="col-sm-6">
                        <div class="text-muted small">Tanggal</div>
                        <div class="fw-medium">
                            <?= date('d M Y', strtotime($program['tanggal_mulai'])) ?>
                            <?php if ($program['tanggal_selesai'] && $program['tanggal_selesai'] !== $program['tanggal_mulai']): ?>
                            – <?= date('d M Y', strtotime($program['tanggal_selesai'])) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ($program['lokasi']): ?>
                    <div class="col-sm-6">
                        <div class="text-muted small">Lokasi</div>
                        <div class="fw-medium"><?= esc($program['lokasi']) ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if ($program['biaya_per_peserta'] !== null): ?>
                    <div class="col-sm-6">
                        <div class="text-muted small">Biaya / Peserta</div>
                        <div class="fw-medium">Rp <?= number_format($program['biaya_per_peserta'], 0, ',', '.') ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if ($program['kuota']): ?>
                    <div class="col-sm-6">
                        <div class="text-muted small">Kuota</div>
                        <div class="fw-medium"><?= $program['kuota'] ?> orang</div>
                    </div>
                    <?php endif; ?>
                    <?php if ($program['deskripsi']): ?>
                    <div class="col-12">
                        <div class="text-muted small">Deskripsi</div>
                        <div style="font-size:.85rem"><?= nl2br(esc($program['deskripsi'])) ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if (! empty($linkedComps)): ?>
                    <div class="col-12">
                        <div class="text-muted small mb-1">Kompetensi yang Dikembangkan</div>
                        <div class="d-flex flex-wrap gap-1">
                            <?php foreach ($linkedComps as $c): ?>
                            <span class="badge bg-secondary-subtle text-secondary-emphasis" style="font-size:.7rem"><?= esc($c['nama']) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="row g-3 h-100">
            <div class="col-6 col-md-12">
                <div class="card p-3 text-center">
                    <div class="text-muted small">Peserta</div>
                    <div class="fw-bold fs-3"><?= count($participants) ?></div>
                    <div class="text-muted small">
                        <span class="text-success"><?= $hadir ?> hadir</span>
                        <?php if ($tdkHadir): ?> · <span class="text-danger"><?= $tdkHadir ?> tdk hadir</span><?php endif; ?>
                    </div>
                </div>
            </div>
            <?php if ($avgPost !== null): ?>
            <div class="col-6 col-md-12">
                <div class="card p-3 text-center">
                    <div class="text-muted small">Avg Post Test</div>
                    <div class="fw-bold fs-3"><?= $avgPost ?></div>
                    <?php if ($improvement !== null): ?>
                    <div class="text-<?= $improvement >= 0 ? 'success' : 'danger' ?> small">
                        <?= $improvement >= 0 ? '+' : '' ?><?= $improvement ?> dari pre test
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Participants -->
<div class="card anim-fade-up" style="animation-delay:.15s">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-people me-2"></i>Peserta (<?= count($participants) ?>)</h6>
        <?php if (! in_array($program['status'], ['completed', 'cancelled'])): ?>
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addParticipantModal">
            <i class="bi bi-person-plus me-1"></i>Daftarkan Peserta
        </button>
        <?php endif; ?>
    </div>
    <?php if (empty($participants)): ?>
    <div class="card-body text-center py-4 text-muted small">
        Belum ada peserta terdaftar.
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Jabatan / Dept</th>
                    <th class="text-center">Kehadiran</th>
                    <th class="text-center">Pre Test</th>
                    <th class="text-center">Post Test</th>
                    <th class="text-center">Peningkatan</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($participants as $pt):
                $imp = null;
                if ($pt['pre_test'] !== null && $pt['post_test'] !== null) {
                    $imp = round((float)$pt['post_test'] - (float)$pt['pre_test'], 1);
                }
            ?>
            <tr>
                <td class="fw-medium"><?= esc($pt['emp_nama']) ?></td>
                <td class="text-muted small"><?= esc($pt['jabatan']) ?> · <?= esc($pt['dept_name']) ?></td>
                <td class="text-center">
                    <span class="badge kehadiran-<?= $pt['status_kehadiran'] ?>"
                          style="background:transparent;border:1px solid currentColor;font-size:.72rem">
                        <?= $kehadiranLabels[$pt['status_kehadiran']] ?>
                    </span>
                </td>
                <td class="text-center"><?= $pt['pre_test'] !== null ? number_format((float)$pt['pre_test'], 1) : '—' ?></td>
                <td class="text-center"><?= $pt['post_test'] !== null ? number_format((float)$pt['post_test'], 1) : '—' ?></td>
                <td class="text-center">
                    <?php if ($imp !== null): ?>
                    <span class="fw-semibold text-<?= $imp >= 0 ? 'success' : 'danger' ?>"><?= $imp >= 0 ? '+' : '' ?><?= $imp ?></span>
                    <?php else: ?>—<?php endif; ?>
                </td>
                <td>
                    <div class="d-flex gap-1">
                        <button class="btn btn-xs btn-sm btn-outline-secondary py-0 px-2" style="font-size:.72rem"
                                data-bs-toggle="modal" data-bs-target="#editParticipantModal"
                                data-id="<?= $pt['id'] ?>"
                                data-nama="<?= esc($pt['emp_nama']) ?>"
                                data-kehadiran="<?= $pt['status_kehadiran'] ?>"
                                data-pre="<?= $pt['pre_test'] ?? '' ?>"
                                data-post="<?= $pt['post_test'] ?? '' ?>"
                                data-catatan="<?= esc($pt['catatan'] ?? '') ?>">
                            Edit
                        </button>
                        <a href="<?= base_url('people/training/' . $program['id'] . '/participants/' . $pt['id'] . '/remove') ?>"
                           class="btn btn-xs btn-sm btn-outline-danger py-0 px-2" style="font-size:.72rem"
                           onclick="return confirm('Hapus peserta ini?')">
                            <i class="bi bi-trash"></i>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Edit Program Modal -->
<?= view('people/training/_form_modal', [
    'modalId'     => 'editModal',
    'modalTitle'  => 'Edit Program Training',
    'formAction'  => base_url('people/training/' . $program['id'] . '/edit'),
    'program'     => $program,
    'compIds'     => $compIds,
    'competencies'=> $allComps,
]) ?>

<!-- Add Participant Modal -->
<div class="modal fade" id="addParticipantModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="<?= base_url('people/training/' . $program['id'] . '/participants/add') ?>">
            <?= csrf_field() ?>
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Daftarkan Peserta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php if (empty($availableEmps)): ?>
                    <p class="text-muted">Semua karyawan aktif sudah terdaftar.</p>
                    <?php else: ?>
                    <label class="form-label">Karyawan <span class="text-danger">*</span></label>
                    <select name="employee_id" class="form-select" required>
                        <option value="">— Pilih Karyawan —</option>
                        <?php foreach ($availableEmps as $e): ?>
                        <option value="<?= $e['id'] ?>"><?= esc($e['nama']) ?> — <?= esc($e['jabatan']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <?php if (! empty($availableEmps)): ?>
                    <button type="submit" class="btn btn-primary btn-sm">Daftarkan</button>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Edit Participant Modal -->
<div class="modal fade" id="editParticipantModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" id="editParticipantForm" action="">
            <?= csrf_field() ?>
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Data — <span id="ptNama"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Status Kehadiran</label>
                        <select name="status_kehadiran" id="ptKehadiran" class="form-select">
                            <?php foreach ($kehadiranLabels as $k => $v): ?>
                            <option value="<?= $k ?>"><?= $v ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col">
                            <label class="form-label">Pre Test (0–100)</label>
                            <input type="number" name="pre_test" id="ptPre" class="form-control" step="0.1" min="0" max="100">
                        </div>
                        <div class="col">
                            <label class="form-label">Post Test (0–100)</label>
                            <input type="number" name="post_test" id="ptPost" class="form-control" step="0.1" min="0" max="100">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Catatan</label>
                        <textarea name="catatan" id="ptCatatan" class="form-control" rows="2"></textarea>
                    </div>
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
document.getElementById('editParticipantModal').addEventListener('show.bs.modal', function(e) {
    const btn = e.relatedTarget;
    document.getElementById('ptNama').textContent      = btn.dataset.nama;
    document.getElementById('ptKehadiran').value       = btn.dataset.kehadiran;
    document.getElementById('ptPre').value             = btn.dataset.pre;
    document.getElementById('ptPost').value            = btn.dataset.post;
    document.getElementById('ptCatatan').value         = btn.dataset.catatan;
    document.getElementById('editParticipantForm').action =
        '<?= base_url('people/training/' . $program['id'] . '/participants/') ?>' + btn.dataset.id + '/update';
});
</script>
<?= $this->endSection() ?>
