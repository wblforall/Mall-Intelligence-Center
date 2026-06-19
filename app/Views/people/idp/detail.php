<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$statusLabel = ['draft'=>'Draft','aktif'=>'Aktif','selesai'=>'Selesai','dibatalkan'=>'Dibatalkan'];
$statusColor = ['draft'=>'secondary','aktif'=>'primary','selesai'=>'success','dibatalkan'=>'danger'];
$itemStatusLabel = ['belum_mulai'=>'Belum Mulai','dalam_proses'=>'Dalam Proses','selesai'=>'Selesai','dibatalkan'=>'Dibatalkan'];
$itemStatusColor = ['belum_mulai'=>'secondary','dalam_proses'=>'primary','selesai'=>'success','dibatalkan'=>'danger'];

$totalItems   = count($items);
$selesaiItems = count(array_filter($items, fn($i) => $i['status'] === 'selesai'));
$pct          = $totalItems > 0 ? round($selesaiItems / $totalItems * 100) : 0;

// trainingRecs is already keyed by competency_id from the model
$recsByComp = $trainingRecs;
?>

<style>
@keyframes fadeUp { from { opacity:0; transform:translateY(14px); } to { opacity:1; transform:none; } }
.anim-fade-up { animation: fadeUp .4s cubic-bezier(.22,.68,0,1.1) both; }
</style>

<!-- Header -->
<div class="d-flex align-items-start justify-content-between mb-3 anim-fade-up">
    <div>
        <a href="<?= base_url('people/idp') ?>" class="text-muted small text-decoration-none">
            <i class="bi bi-arrow-left me-1"></i>Kembali ke IDP
        </a>
        <h4 class="fw-bold mb-0 mt-1"><?= esc($plan['periode_label']) ?></h4>
        <div class="text-muted small">
            <?= esc($plan['employee_nama']) ?> · <?= esc($plan['dept_name'] ?? '') ?> · Tahun <?= $plan['tahun'] ?>
            <?php if ($plan['tna_period_nama']): ?>
            · <span class="badge bg-info text-dark">Dari TNA: <?= esc($plan['tna_period_nama']) ?></span>
            <?php endif; ?>
        </div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <span class="badge bg-<?= $statusColor[$plan['status']] ?? 'secondary' ?> fs-6">
            <?= $statusLabel[$plan['status']] ?? $plan['status'] ?>
        </span>
        <a href="<?= base_url('people/idp/' . $plan['id'] . '/print') ?>" class="btn btn-sm btn-outline-secondary" target="_blank">
            <i class="bi bi-printer me-1"></i>Print
        </a>
        <?php if ($canEdit): ?>
        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal">
            <i class="bi bi-pencil me-1"></i>Edit
        </button>
        <form method="POST" action="<?= base_url('people/idp/' . $plan['id'] . '/delete') ?>" class="d-inline" onsubmit="return confirm('Hapus IDP ini beserta semua goalnya?')">
            <?= csrf_field() ?>
            <button class="btn btn-sm btn-outline-danger" title="Hapus"><i class="bi bi-trash"></i></button>
        </form>
        <?php endif; ?>
    </div>
</div>

<!-- Progress bar -->
<div class="card mb-4 anim-fade-up" style="animation-delay:.05s">
    <div class="card-body">
        <div class="d-flex justify-content-between mb-1">
            <span class="small fw-semibold">Progress Keseluruhan</span>
            <span class="small text-muted"><?= $selesaiItems ?>/<?= $totalItems ?> goal · <?= $pct ?>%</span>
        </div>
        <div class="progress" style="height:10px">
            <div class="progress-bar bg-success" style="width:<?= $pct ?>%"></div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Left: Info + Approval -->
    <div class="col-md-4 anim-fade-up" style="animation-delay:.08s">
        <div class="card mb-3">
            <div class="card-header fw-semibold">Info IDP</div>
            <div class="card-body small">
                <div class="mb-2"><span class="text-muted">Karyawan</span><br><strong><?= esc($plan['employee_nama']) ?></strong></div>
                <div class="mb-2"><span class="text-muted">Jabatan</span><br><?= esc($plan['jabatan'] ?? '-') ?></div>
                <div class="mb-2"><span class="text-muted">Atasan</span><br><?= esc($plan['atasan_nama'] ?? '-') ?></div>
                <div class="mb-2"><span class="text-muted">Dibuat oleh</span><br><?= esc($plan['created_by_name'] ?? '-') ?></div>
                <?php if ($plan['tujuan_karir']): ?>
                <div class="mb-2"><span class="text-muted">Tujuan Karir</span><br><?= nl2br(esc($plan['tujuan_karir'])) ?></div>
                <?php endif; ?>
                <?php if ($plan['catatan']): ?>
                <div><span class="text-muted">Catatan</span><br><?= nl2br(esc($plan['catatan'])) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Approval Status -->
        <div class="card mb-3">
            <div class="card-header fw-semibold">Status Persetujuan Atasan</div>
            <div class="card-body small">
                <?php
                $aprColor = ['pending'=>'warning','setuju'=>'success','menolak'=>'danger'];
                $aprLabel = ['pending'=>'Menunggu','setuju'=>'Disetujui','menolak'=>'Ditolak'];
                $apr = $plan['persetujuan_atasan'];
                ?>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-<?= $aprColor[$apr] ?> <?= $apr==='pending'?'text-dark':'' ?>">
                        <?= $aprLabel[$apr] ?>
                    </span>
                    <span><?= esc($plan['atasan_nama'] ?? 'Atasan belum diset') ?></span>
                </div>
                <?php if ($plan['catatan_penolakan']): ?>
                <div class="alert alert-danger py-1 px-2 mb-2 small">
                    <strong>Catatan penolakan:</strong><br><?= nl2br(esc($plan['catatan_penolakan'])) ?>
                </div>
                <?php endif; ?>
                <?php if ($canEdit): ?>
                <?php if (empty($plan['atasan_email'])): ?>
                <div class="text-muted small"><i class="bi bi-exclamation-triangle text-warning me-1"></i>Email atasan tidak tersedia. Isi di Data Karyawan.</div>
                <?php else: ?>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="<?= base_url('people/idp/' . $plan['id'] . '/token') ?>"
                       onclick="return confirm('Generate ulang link atasan? Link lama tidak berlaku.')"
                       class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-repeat me-1"></i>Generate Link Ulang
                    </a>
                    <?php if ($plan['token_atasan']): ?>
                    <button class="btn btn-sm btn-outline-primary" onclick="copyLink('<?= base_url('idp/approval/' . $plan['token_atasan']) ?>')">
                        <i class="bi bi-link-45deg me-1"></i>Salin Link
                    </button>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right: Goals -->
    <div class="col-md-8 anim-fade-up" style="animation-delay:.1s">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="fw-semibold">Goal Pengembangan</div>
            <?php if ($canEdit): ?>
            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addItemModal">
                <i class="bi bi-plus-lg me-1"></i>Tambah Goal
            </button>
            <?php endif; ?>
        </div>

        <?php if (empty($items)): ?>
        <div class="text-center text-muted py-4">Belum ada goal. Tambahkan goal pengembangan.</div>
        <?php endif; ?>

        <?php foreach ($items as $item): ?>
        <?php $recs = $recsByComp[$item['competency_id'] ?? 0] ?? []; ?>
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <div class="fw-semibold"><?= esc($item['judul']) ?></div>
                        <?php if ($item['competency_nama']): ?>
                        <div class="small text-muted">
                            <i class="bi bi-diagram-3 me-1"></i><?= esc($item['competency_nama']) ?>
                            <?= $item['cluster_nama'] ? '· ' . esc($item['cluster_nama']) : '' ?>
                            <span class="badge bg-<?= $item['competency_kategori']==='hard'?'info':'purple' ?> text-dark ms-1" style="<?= $item['competency_kategori']==='soft'?'background:#ede9fe;color:#5b21b6':'' ?>">
                                <?= $item['competency_kategori'] === 'hard' ? 'Hard Skill' : 'Soft Skill' ?>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <span class="badge bg-<?= $itemStatusColor[$item['status']] ?>">
                            <?= $itemStatusLabel[$item['status']] ?>
                        </span>
                        <?php if ($canEdit): ?>
                        <button class="btn btn-sm btn-outline-secondary py-0 px-1"
                                data-bs-toggle="modal" data-bs-target="#editItemModal<?= $item['id'] ?>">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <form method="POST" action="<?= base_url('people/idp/' . $plan['id'] . '/items/' . $item['id'] . '/delete') ?>" class="d-inline" onsubmit="return confirm('Hapus goal ini?')">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-danger py-0 px-1" title="Hapus"><i class="bi bi-trash"></i></button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Level progress -->
                <?php if ($item['level_saat_ini'] && $item['level_target']): ?>
                <div class="d-flex align-items-center gap-2 mb-2 small">
                    <span class="text-muted">Level:</span>
                    <span class="badge bg-secondary"><?= number_format((float)$item['level_saat_ini'], 1) ?></span>
                    <i class="bi bi-arrow-right text-muted"></i>
                    <span class="badge bg-primary"><?= $item['level_target'] ?></span>
                </div>
                <?php endif; ?>

                <?php if ($item['langkah_aksi']): ?>
                <div class="small mb-1"><span class="text-muted">Langkah aksi:</span> <?= nl2br(esc($item['langkah_aksi'])) ?></div>
                <?php endif; ?>
                <?php if ($item['sumber_daya']): ?>
                <div class="small mb-1"><span class="text-muted">Sumber daya:</span> <?= nl2br(esc($item['sumber_daya'])) ?></div>
                <?php endif; ?>
                <?php if ($item['deadline']): ?>
                <div class="small mb-1"><span class="text-muted">Deadline:</span>
                    <?php
                    $dl = strtotime($item['deadline']);
                    $dlClass = $item['status'] !== 'selesai' && $dl < time() ? 'text-danger fw-semibold' : '';
                    ?>
                    <span class="<?= $dlClass ?>"><?= date('d M Y', $dl) ?></span>
                </div>
                <?php endif; ?>
                <?php if ($item['catatan_progres']): ?>
                <div class="small mt-1 p-2 rounded" style="background:var(--bs-tertiary-bg)">
                    <i class="bi bi-chat-left-text me-1 text-muted"></i><?= nl2br(esc($item['catatan_progres'])) ?>
                </div>
                <?php endif; ?>

                <!-- Training recommendations -->
                <?php if (! empty($recs)): ?>
                <div class="mt-2 pt-2 border-top">
                    <div class="small text-muted mb-1"><i class="bi bi-mortarboard me-1"></i>Rekomendasi Training:</div>
                    <?php foreach ($recs as $rec): ?>
                    <span class="badge bg-success-subtle text-success me-1 mb-1"><?= esc($rec['nama_program']) ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Edit item modal -->
        <?php if ($canEdit): ?>
        <div class="modal fade" id="editItemModal<?= $item['id'] ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="post" action="<?= base_url('people/idp/' . $plan['id'] . '/items/' . $item['id'] . '/update') ?>">
                        <?= csrf_field() ?>
                        <div class="modal-header">
                            <h6 class="modal-title fw-bold">Edit Goal</h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-2">
                                <label class="form-label small fw-semibold">Nama Goal <span class="text-danger">*</span></label>
                                <input type="text" name="judul" class="form-control form-control-sm" required value="<?= esc($item['judul']) ?>">
                            </div>
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="form-label small fw-semibold">Level Saat Ini</label>
                                    <input type="number" name="level_saat_ini" class="form-control form-control-sm"
                                           min="1" max="5" step="0.01" value="<?= esc($item['level_saat_ini']) ?>">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-semibold">Target Level</label>
                                    <input type="number" name="level_target" class="form-control form-control-sm"
                                           min="1" max="5" value="<?= esc($item['level_target']) ?>">
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small fw-semibold">Langkah Aksi</label>
                                <textarea name="langkah_aksi" class="form-control form-control-sm" rows="2"><?= esc($item['langkah_aksi']) ?></textarea>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small fw-semibold">Sumber Daya / Support</label>
                                <textarea name="sumber_daya" class="form-control form-control-sm" rows="2"><?= esc($item['sumber_daya']) ?></textarea>
                            </div>
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="form-label small fw-semibold">Deadline</label>
                                    <input type="date" name="deadline" class="form-control form-control-sm" value="<?= esc($item['deadline']) ?>">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-semibold">Status</label>
                                    <select name="status" class="form-select form-select-sm">
                                        <?php foreach ($itemStatusLabel as $k => $v): ?>
                                        <option value="<?= $k ?>" <?= $item['status'] === $k ? 'selected' : '' ?>><?= $v ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small fw-semibold">Catatan Progres</label>
                                <textarea name="catatan_progres" class="form-control form-control-sm" rows="2"><?= esc($item['catatan_progres']) ?></textarea>
                            </div>
                            <input type="hidden" name="competency_id" value="<?= esc($item['competency_id']) ?>">
                            <input type="hidden" name="urutan" value="<?= esc($item['urutan']) ?>">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>

<?php if ($canEdit): ?>
<!-- Modal: Edit IDP Header -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="<?= base_url('people/idp/' . $plan['id'] . '/update') ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h6 class="modal-title fw-bold">Edit IDP</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Label Periode <span class="text-danger">*</span></label>
                        <input type="text" name="periode_label" class="form-control form-control-sm" required value="<?= esc($plan['periode_label']) ?>">
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Tahun <span class="text-danger">*</span></label>
                            <input type="number" name="tahun" class="form-control form-control-sm" required value="<?= esc($plan['tahun']) ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Status</label>
                            <select name="status" class="form-select form-select-sm">
                                <?php foreach ($statusLabel as $k => $v): ?>
                                <option value="<?= $k ?>" <?= $plan['status'] === $k ? 'selected' : '' ?>><?= $v ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Tujuan Karir</label>
                        <textarea name="tujuan_karir" class="form-control form-control-sm" rows="2"><?= esc($plan['tujuan_karir']) ?></textarea>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Catatan</label>
                        <textarea name="catatan" class="form-control form-control-sm" rows="2"><?= esc($plan['catatan']) ?></textarea>
                    </div>
                    <input type="hidden" name="tna_period_id" value="<?= esc($plan['tna_period_id']) ?>">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Tambah Goal -->
<div class="modal fade" id="addItemModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="<?= base_url('people/idp/' . $plan['id'] . '/items/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h6 class="modal-title fw-bold">Tambah Goal</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Nama Goal <span class="text-danger">*</span></label>
                        <input type="text" name="judul" class="form-control form-control-sm" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Kompetensi Terkait <span class="text-muted">(opsional)</span></label>
                        <select name="competency_id" class="form-select form-select-sm">
                            <option value="">— Tidak terkait kompetensi —</option>
                            <?php foreach ($competencies as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= esc($c['nama']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Level Saat Ini</label>
                            <input type="number" name="level_saat_ini" class="form-control form-control-sm" min="1" max="5" step="0.01">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Target Level</label>
                            <input type="number" name="level_target" class="form-control form-control-sm" min="1" max="5">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Langkah Aksi</label>
                        <textarea name="langkah_aksi" class="form-control form-control-sm" rows="2"></textarea>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Sumber Daya / Support</label>
                        <textarea name="sumber_daya" class="form-control form-control-sm" rows="2"></textarea>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Deadline</label>
                        <input type="date" name="deadline" class="form-control form-control-sm">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm">Tambah</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function copyLink(url) {
    navigator.clipboard.writeText(url).then(function () {
        alert('Link berhasil disalin:\n' + url);
    });
}
</script>

<?= $this->endSection() ?>
