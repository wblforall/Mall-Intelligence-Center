<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<style>
@keyframes fadeUp { from { opacity:0; transform:translateY(14px); } to { opacity:1; transform:none; } }
.anim-fade-up { animation: fadeUp .4s cubic-bezier(.22,.68,0,1.1) both; }
</style>

<?php
$statusLabel = ['draft'=>'Draft','menunggu_persetujuan'=>'Menunggu Persetujuan','aktif'=>'Aktif','selesai'=>'Selesai','diperpanjang'=>'Diperpanjang','dihentikan'=>'Dihentikan'];
$statusColor = ['draft'=>'secondary','menunggu_persetujuan'=>'info','aktif'=>'primary','selesai'=>'success','diperpanjang'=>'warning','dihentikan'=>'danger'];
$progresLabel = ['baik'=>'Baik','cukup'=>'Cukup','kurang'=>'Kurang'];
$progresColor = ['baik'=>'success','cukup'=>'warning','kurang'=>'danger'];
$spLabel  = ['none'=>'Tanpa SP','sp1'=>'SP 1','sp2'=>'SP 2','sp3'=>'SP 3','phk'=>'PHK'];
$spColor  = ['none'=>'secondary','sp1'=>'warning','sp2'=>'orange','sp3'=>'danger','phk'=>'dark'];
$setujuLabel = ['pending'=>'Menunggu','setuju'=>'Disetujui','menolak'=>'Ditolak'];
$setujuColor = ['pending'=>'secondary','setuju'=>'success','menolak'=>'danger'];
$frekLabel = ['mingguan'=>'Mingguan','2mingguan'=>'2 Mingguan','bulanan'=>'Bulanan'];
?>

<!-- Header -->
<div class="d-flex align-items-start justify-content-between mb-4 anim-fade-up" style="animation-delay:.05s">
    <div>
        <a href="<?= base_url('people/pip') ?>" class="text-muted small text-decoration-none">
            <i class="bi bi-arrow-left me-1"></i>Performance Improvement Plan
        </a>
        <h4 class="fw-bold mb-0 mt-1"><?= esc($plan['judul']) ?></h4>
        <div class="text-muted small">
            <?= esc($plan['employee_nama']) ?> · <?= esc($plan['jabatan'] ?? '') ?> · <?= esc($plan['dept_name'] ?? '') ?>
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= base_url('people/pip/' . $plan['id'] . '/print') ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-printer me-1"></i>Print
        </a>
        <?php if ($canApprovePip && $plan['status'] === 'menunggu_persetujuan'): ?>
        <a href="<?= base_url('people/pip/' . $plan['id'] . '/approve') ?>" class="btn btn-sm btn-success"
           onclick="return confirm('Setujui PIP ini? Status akan berubah menjadi Aktif.')">
            <i class="bi bi-check-circle me-1"></i>Setujui
        </a>
        <?php endif; ?>
        <?php if ($canEdit): ?>
        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal">
            <i class="bi bi-pencil me-1"></i>Edit
        </button>
        <a href="<?= base_url('people/pip/' . $plan['id'] . '/delete') ?>" class="btn btn-sm btn-outline-danger"
           onclick="return confirm('Hapus PIP ini beserta semua data review?')">
            <i class="bi bi-trash me-1"></i>Hapus
        </a>
        <?php endif; ?>
    </div>
</div>

<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i><?= session()->getFlashdata('success') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
<div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-circle me-2"></i><?= session()->getFlashdata('error') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<?php if ($plan['status'] === 'menunggu_persetujuan'): ?>
<div class="alert alert-info d-flex align-items-center gap-3 mb-4">
    <i class="bi bi-hourglass-split fs-4"></i>
    <div>
        <div class="fw-semibold">Menunggu Persetujuan Head People Development</div>
        <div class="small">PIP ini belum aktif. Head PD perlu menyetujuinya terlebih dahulu sebelum bisa berjalan.</div>
    </div>
    <?php if ($canApprovePip): ?>
    <a href="<?= base_url('people/pip/' . $plan['id'] . '/approve') ?>" class="btn btn-success btn-sm ms-auto text-nowrap"
       onclick="return confirm('Setujui PIP ini? Status akan berubah menjadi Aktif.')">
        <i class="bi bi-check-circle me-1"></i>Setujui Sekarang
    </a>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Info Cards -->
<div class="row g-3 mb-4 anim-fade-up" style="animation-delay:.1s">
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">Status</div>
                <span class="badge bg-<?= $statusColor[$plan['status']] ?> fs-6"><?= $statusLabel[$plan['status']] ?></span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">Periode</div>
                <div class="fw-semibold small">
                    <?= date('d M Y', strtotime($plan['tanggal_mulai'])) ?> –
                    <?= date('d M Y', strtotime($plan['tanggal_selesai'])) ?>
                </div>
                <?php
                $sisa = (int)ceil((strtotime($plan['tanggal_selesai']) - time()) / 86400);
                if (in_array($plan['status'], ['aktif','diperpanjang'])):
                ?>
                <div class="small mt-1 <?= $sisa < 0 ? 'text-danger' : ($sisa <= 7 ? 'text-warning' : 'text-muted') ?>">
                    <?= $sisa >= 0 ? $sisa . ' hari lagi' : abs($sisa) . ' hari lewat' ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">Atasan Langsung</div>
                <?php if ($plan['atasan_nama']): ?>
                <div class="fw-semibold"><?= esc($plan['atasan_nama']) ?></div>
                <div class="text-muted small"><?= esc($plan['atasan_jabatan'] ?? '') ?></div>
                <?php if ($plan['atasan_no_hp']): ?>
                <div class="small mt-1"><i class="bi bi-telephone me-1"></i><?= esc($plan['atasan_no_hp']) ?></div>
                <?php endif; ?>
                <?php else: ?>
                <div class="text-muted small">—</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">Item Perbaikan</div>
                <div class="fw-bold fs-5"><?= count($items) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">Total Review</div>
                <div class="fw-bold fs-5"><?= count($reviews) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">Surat Peringatan</div>
                <span class="badge bg-<?= $spColor[$plan['level_sp']] ?>"><?= $spLabel[$plan['level_sp']] ?></span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">Frekuensi Review</div>
                <div class="fw-semibold small"><?= $frekLabel[$plan['frekuensi_review'] ?? 'mingguan'] ?></div>
                <?php if (in_array($plan['status'], ['aktif','diperpanjang'])):
                    $next = \App\Models\PipPlanModel::nextReviewDate($plan);
                    $sisa = (int)ceil((strtotime($next) - time()) / 86400);
                ?>
                <div class="small mt-1">
                    Next: <?= date('d M Y', strtotime($next)) ?>
                    <?php if ($sisa < 0): ?>
                    <span class="badge bg-danger ms-1">Terlambat <?= abs($sisa) ?> hr</span>
                    <?php elseif ($sisa <= 2): ?>
                    <span class="badge bg-warning text-dark ms-1"><?= $sisa === 0 ? 'Hari ini' : $sisa . ' hari lagi' ?></span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">Persetujuan Atasan</div>
                <span class="badge bg-<?= $setujuColor[$plan['persetujuan_atasan']] ?>"><?= $setujuLabel[$plan['persetujuan_atasan']] ?></span>
                <?php if ($plan['persetujuan_atasan'] === 'menolak' && $plan['catatan_penolakan_atasan']): ?>
                <div class="small text-danger mt-1"><?= esc($plan['catatan_penolakan_atasan']) ?></div>
                <?php endif; ?>
                <?php if ($canEdit && $plan['persetujuan_atasan'] === 'pending'): ?>
                <div class="mt-2">
                    <?php if ($plan['token_atasan']): ?>
                    <?php $urlAtasan = base_url('pip/approval/atasan/' . $plan['token_atasan']); ?>
                    <button class="btn btn-xs btn-outline-secondary btn-sm w-100 copy-btn" data-url="<?= $urlAtasan ?>">
                        <i class="bi bi-clipboard me-1"></i>Salin Link
                    </button>
                    <?php else: ?>
                    <a href="<?= base_url('people/pip/' . $plan['id'] . '/token/atasan') ?>" class="btn btn-sm btn-outline-primary w-100">
                        <i class="bi bi-link-45deg me-1"></i>Generate Link
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">Persetujuan Karyawan</div>
                <span class="badge bg-<?= $setujuColor[$plan['persetujuan_karyawan']] ?>"><?= $setujuLabel[$plan['persetujuan_karyawan']] ?></span>
                <?php if ($plan['persetujuan_karyawan'] === 'menolak' && $plan['catatan_penolakan']): ?>
                <div class="small text-danger mt-1"><?= esc($plan['catatan_penolakan']) ?></div>
                <?php endif; ?>
                <?php if ($canEdit && $plan['persetujuan_karyawan'] === 'pending'): ?>
                <div class="mt-2">
                    <?php if ($plan['token_karyawan']): ?>
                    <?php $urlKaryawan = base_url('pip/approval/karyawan/' . $plan['token_karyawan']); ?>
                    <button class="btn btn-sm btn-outline-secondary w-100 copy-btn" data-url="<?= $urlKaryawan ?>">
                        <i class="bi bi-clipboard me-1"></i>Salin Link
                    </button>
                    <?php else: ?>
                    <a href="<?= base_url('people/pip/' . $plan['id'] . '/token/karyawan') ?>" class="btn btn-sm btn-outline-primary w-100">
                        <i class="bi bi-link-45deg me-1"></i>Generate Link
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if ($plan['alasan'] || $plan['dukungan'] || $plan['konsekuensi']): ?>
<div class="row g-3 mb-4 anim-fade-up" style="animation-delay:.15s">
    <?php if ($plan['alasan']): ?>
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="fw-semibold mb-1 text-muted small">LATAR BELAKANG</div>
                <div><?= nl2br(esc($plan['alasan'])) ?></div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <?php if ($plan['dukungan']): ?>
    <div class="col-md-6">
        <div class="card h-100 border-info">
            <div class="card-body">
                <div class="fw-semibold mb-1 text-muted small"><i class="bi bi-hand-thumbs-up me-1"></i>DUKUNGAN PERUSAHAAN</div>
                <div><?= nl2br(esc($plan['dukungan'])) ?></div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <?php if ($plan['konsekuensi']): ?>
    <div class="col-md-6">
        <div class="card h-100 border-warning">
            <div class="card-body">
                <div class="fw-semibold mb-1 text-muted small"><i class="bi bi-exclamation-triangle me-1"></i>KONSEKUENSI JIKA TIDAK TERCAPAI</div>
                <div><?= nl2br(esc($plan['konsekuensi'])) ?></div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Item Perbaikan -->
<div class="card mb-4 anim-fade-up" style="animation-delay:.2s">
    <div class="card-header fw-semibold">
        <i class="bi bi-list-check me-2"></i>Item Perbaikan
    </div>
    <?php if (empty($items)): ?>
    <div class="card-body text-muted small">Belum ada item perbaikan.</div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover align-top mb-0">
            <thead class="table-light">
                <tr>
                    <th width="5%">#</th>
                    <th width="20%">Aspek</th>
                    <th width="25%">Kondisi Saat Ini</th>
                    <th width="25%">Target</th>
                    <th width="15%">Metrik</th>
                    <th width="10%">Deadline</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $i => $item): ?>
            <tr>
                <td class="text-muted"><?= $i + 1 ?></td>
                <td class="fw-semibold"><?= esc($item['aspek']) ?></td>
                <td class="small"><?= nl2br(esc($item['masalah'] ?? '—')) ?></td>
                <td class="small"><?= nl2br(esc($item['target'] ?? '—')) ?></td>
                <td class="small text-muted"><?= esc($item['metrik'] ?? '—') ?></td>
                <td class="small text-nowrap">
                    <?php if ($item['deadline']): ?>
                    <?= date('d M Y', strtotime($item['deadline'])) ?>
                    <?php if (strtotime($item['deadline']) < time() && in_array($plan['status'], ['aktif','diperpanjang'])): ?>
                    <br><span class="badge bg-danger" style="font-size:.65rem">Lewat</span>
                    <?php endif; ?>
                    <?php else: ?>
                    <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Review -->
<div class="card mb-4 anim-fade-up" style="animation-delay:.25s">
    <div class="card-header d-flex align-items-center justify-content-between fw-semibold">
        <span><i class="bi bi-chat-square-text me-2"></i>Riwayat Review</span>
        <?php if ($canEdit && in_array($plan['status'], ['aktif','diperpanjang'])): ?>
        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#reviewModal">
            <i class="bi bi-plus-lg me-1"></i>Tambah Review
        </button>
        <?php endif; ?>
    </div>
    <?php if (empty($reviews)): ?>
    <div class="card-body text-muted small">Belum ada review.</div>
    <?php else: ?>
    <div class="list-group list-group-flush">
    <?php foreach ($reviews as $r): ?>
        <div class="list-group-item">
            <div class="d-flex align-items-start justify-content-between">
                <div>
                    <span class="badge bg-<?= $progresColor[$r['progres']] ?> me-2"><?= $progresLabel[$r['progres']] ?></span>
                    <span class="fw-semibold"><?= date('d M Y', strtotime($r['tanggal_review'])) ?></span>
                    <span class="text-muted small ms-2">oleh <?= esc($r['reviewer_name']) ?></span>
                </div>
                <?php if ($canEdit): ?>
                <a href="<?= base_url('people/pip/' . $plan['id'] . '/reviews/' . $r['id'] . '/delete') ?>"
                   class="btn btn-sm btn-link text-danger p-0"
                   onclick="return confirm('Hapus review ini?')">
                    <i class="bi bi-trash"></i>
                </a>
                <?php endif; ?>
            </div>
            <?php if ($r['catatan']): ?>
            <div class="small mt-1 text-muted"><?= nl2br(esc($r['catatan'])) ?></div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php if ($plan['catatan_penutup']): ?>
<div class="card mb-4 anim-fade-up border-<?= $statusColor[$plan['status']] ?>" style="animation-delay:.3s">
    <div class="card-body">
        <div class="fw-semibold mb-1 text-muted small">CATATAN PENUTUP</div>
        <div><?= nl2br(esc($plan['catatan_penutup'])) ?></div>
    </div>
</div>
<?php endif; ?>

<!-- Modal Edit -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form method="post" action="<?= base_url('people/pip/' . $plan['id'] . '/update') ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Edit PIP</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Judul <span class="text-danger">*</span></label>
                            <input type="text" name="judul" class="form-control" required value="<?= esc($plan['judul']) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Status</label>
                            <select name="status" class="form-select">
                                <?php foreach ($statusLabel as $k => $v): ?>
                                <option value="<?= $k ?>" <?= $plan['status'] === $k ? 'selected' : '' ?>><?= $v ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Frekuensi Review</label>
                            <select name="frekuensi_review" class="form-select">
                                <?php foreach ($frekLabel as $k => $v): ?>
                                <option value="<?= $k ?>" <?= ($plan['frekuensi_review'] ?? 'mingguan') === $k ? 'selected' : '' ?>><?= $v ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Surat Peringatan</label>
                            <select name="level_sp" class="form-select">
                                <?php foreach ($spLabel as $k => $v): ?>
                                <option value="<?= $k ?>" <?= $plan['level_sp'] === $k ? 'selected' : '' ?>><?= $v ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tanggal Mulai</label>
                            <input type="date" name="tanggal_mulai" class="form-control" value="<?= $plan['tanggal_mulai'] ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tanggal Selesai</label>
                            <input type="date" name="tanggal_selesai" class="form-control" value="<?= $plan['tanggal_selesai'] ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Alasan / Latar Belakang</label>
                            <textarea name="alasan" class="form-control" rows="2"><?= esc($plan['alasan'] ?? '') ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Dukungan Perusahaan</label>
                            <textarea name="dukungan" class="form-control" rows="2" placeholder="Coaching, pelatihan, pendampingan…"><?= esc($plan['dukungan'] ?? '') ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Konsekuensi jika Tidak Tercapai</label>
                            <textarea name="konsekuensi" class="form-control" rows="2" placeholder="SP 2, proses PHK, dll…"><?= esc($plan['konsekuensi'] ?? '') ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Persetujuan Atasan</label>
                            <select name="persetujuan_atasan" class="form-select">
                                <?php foreach ($setujuLabel as $k => $v): ?>
                                <option value="<?= $k ?>" <?= $plan['persetujuan_atasan'] === $k ? 'selected' : '' ?>><?= $v ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Catatan Penolakan Atasan</label>
                            <textarea name="catatan_penolakan_atasan" class="form-control" rows="2" placeholder="Alasan atasan menolak…"><?= esc($plan['catatan_penolakan_atasan'] ?? '') ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Persetujuan Karyawan</label>
                            <select name="persetujuan_karyawan" class="form-select">
                                <?php foreach ($setujuLabel as $k => $v): ?>
                                <option value="<?= $k ?>" <?= $plan['persetujuan_karyawan'] === $k ? 'selected' : '' ?>><?= $v ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Catatan Penolakan Karyawan</label>
                            <textarea name="catatan_penolakan" class="form-control" rows="2" placeholder="Alasan karyawan menolak…"><?= esc($plan['catatan_penolakan'] ?? '') ?></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Catatan Penutup</label>
                            <textarea name="catatan_penutup" class="form-control" rows="2" placeholder="Isi saat PIP ditutup / selesai…"><?= esc($plan['catatan_penutup'] ?? '') ?></textarea>
                        </div>
                    </div>
                    <hr>
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="fw-semibold">Item Perbaikan</div>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="addItemBtnEdit">
                            <i class="bi bi-plus-lg me-1"></i>Tambah Item
                        </button>
                    </div>
                    <div id="itemsContainerEdit">
                    <?php foreach ($items as $item): ?>
                        <div class="item-row card card-body mb-2 p-3">
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Aspek <span class="text-danger">*</span></label>
                                    <select name="aspek[]" class="form-select form-select-sm aspek-select-edit" required
                                        data-current="<?= esc($item['aspek']) ?>" onchange="autoFillItem(this)">
                                        <option value="">-- Pilih Aspek --</option>
                                    </select>
                                    <input type="text" name="aspek_custom[]" class="form-control form-control-sm mt-1 aspek-custom d-none"
                                        placeholder="Tuliskan aspek lainnya…">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Kondisi Saat Ini</label>
                                    <input type="text" name="masalah[]" class="form-control form-control-sm" value="<?= esc($item['masalah'] ?? '') ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Target yang Diharapkan</label>
                                    <input type="text" name="target[]" class="form-control form-control-sm" value="<?= esc($item['target'] ?? '') ?>">
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label small fw-semibold">Metrik / Cara Ukur</label>
                                    <input type="text" name="metrik[]" class="form-control form-control-sm" value="<?= esc($item['metrik'] ?? '') ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold">Deadline</label>
                                    <input type="date" name="deadline[]" class="form-control form-control-sm" value="<?= $item['deadline'] ?? '' ?>">
                                </div>
                                <div class="col-md-4 d-flex align-items-end justify-content-end">
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-item-btn"><i class="bi bi-trash"></i> Hapus</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($items)): ?>
                        <div class="item-row card card-body mb-2 p-3">
                            <?= $this->include('people/pip/_item_row') ?>
                        </div>
                    <?php endif; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Review -->
<div class="modal fade" id="reviewModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="<?= base_url('people/pip/' . $plan['id'] . '/reviews/add') ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Tambah Review</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Tanggal Review <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_review" class="form-control" required value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nama Reviewer <span class="text-danger">*</span></label>
                        <input type="text" name="reviewer_name" class="form-control" required
                            value="<?= esc($plan['atasan_nama'] ?? '') ?>"
                            placeholder="Nama atasan / HR">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Progress Keseluruhan</label>
                        <select name="progres" class="form-select">
                            <option value="baik">Baik — Sesuai target</option>
                            <option value="cukup" selected>Cukup — Ada kemajuan, perlu peningkatan</option>
                            <option value="kurang">Kurang — Belum ada kemajuan signifikan</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Catatan</label>
                        <textarea name="catatan" class="form-control" rows="3" placeholder="Ringkasan hasil review…"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Review</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const ASPEK_DATA = <?= json_encode(array_map(fn($a) => [
    'aspek'  => $a['aspek'],
    'target' => $a['target_default'] ?? '',
    'metrik' => $a['metrik_default'] ?? '',
    'kategori' => $a['kategori'] ?? '',
], $aspekMaster)) ?>;

function buildAspekOptions(currentVal = '') {
    const grouped = {};
    ASPEK_DATA.forEach(a => {
        const kat = a.kategori || 'Lainnya';
        if (!grouped[kat]) grouped[kat] = [];
        grouped[kat].push(a);
    });
    let html = '<option value="">-- Pilih Aspek --</option>';
    Object.entries(grouped).forEach(([kat, items]) => {
        html += `<optgroup label="${kat}">`;
        items.forEach(a => {
            const sel = a.aspek === currentVal ? 'selected' : '';
            html += `<option value="${a.aspek}" data-target="${a.target}" data-metrik="${a.metrik}" ${sel}>${a.aspek}</option>`;
        });
        html += `<option value="__lainnya__" ${currentVal && !ASPEK_DATA.find(a=>a.aspek===currentVal) ? 'selected' : ''}>— Lainnya (ketik manual)</option></optgroup>`;
    });
    return html;
}

function autoFillItem(sel) {
    const row = sel.closest('.row, .item-row');
    const custom = row?.querySelector('.aspek-custom');
    if (sel.value === '__lainnya__') {
        custom?.classList.remove('d-none');
        row.querySelector('[name="target[]"]').value = '';
        row.querySelector('[name="metrik[]"]').value = '';
    } else {
        custom?.classList.add('d-none');
        const opt = sel.selectedOptions[0];
        if (opt && opt.dataset.target !== undefined) {
            row.querySelector('[name="target[]"]').value = opt.dataset.target;
            row.querySelector('[name="metrik[]"]').value = opt.dataset.metrik;
        }
    }
}

document.querySelectorAll('.copy-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        navigator.clipboard.writeText(this.dataset.url).then(() => {
            const orig = this.innerHTML;
            this.innerHTML = '<i class="bi bi-check me-1"></i>Tersalin!';
            this.classList.replace('btn-outline-secondary','btn-success');
            setTimeout(() => { this.innerHTML = orig; this.classList.replace('btn-success','btn-outline-secondary'); }, 2000);
        });
    });
});

// Populate existing aspek selects when edit modal opens
document.getElementById('editModal')?.addEventListener('show.bs.modal', function() {
    document.querySelectorAll('#itemsContainerEdit .aspek-select-edit').forEach(sel => {
        const current = sel.dataset.current || '';
        sel.innerHTML = buildAspekOptions(current);
        const isLainnya = current && !ASPEK_DATA.find(a => a.aspek === current);
        const custom = sel.closest('.row, .item-row')?.querySelector('.aspek-custom');
        if (isLainnya) {
            sel.value = '__lainnya__';
            if (custom) { custom.classList.remove('d-none'); custom.value = current; }
        } else {
            if (custom) custom.classList.add('d-none');
        }
    });
});

document.getElementById('addItemBtnEdit')?.addEventListener('click', function() {
    const div = document.createElement('div');
    div.className = 'item-row card card-body mb-2 p-3';
    div.innerHTML = `<div class="row g-2">
        <div class="col-md-4">
            <label class="form-label small fw-semibold">Aspek <span class="text-danger">*</span></label>
            <select name="aspek[]" class="form-select form-select-sm" required onchange="autoFillItem(this)">${buildAspekOptions()}</select>
            <input type="text" name="aspek_custom[]" class="form-control form-control-sm mt-1 aspek-custom d-none" placeholder="Tuliskan aspek lainnya…">
        </div>
        <div class="col-md-4"><label class="form-label small fw-semibold">Kondisi Saat Ini</label><input type="text" name="masalah[]" class="form-control form-control-sm"></div>
        <div class="col-md-4"><label class="form-label small fw-semibold">Target yang Diharapkan</label><input type="text" name="target[]" class="form-control form-control-sm"></div>
        <div class="col-md-5"><label class="form-label small fw-semibold">Metrik / Cara Ukur</label><input type="text" name="metrik[]" class="form-control form-control-sm"></div>
        <div class="col-md-3"><label class="form-label small fw-semibold">Deadline</label><input type="date" name="deadline[]" class="form-control form-control-sm"></div>
        <div class="col-md-4 d-flex align-items-end justify-content-end"><button type="button" class="btn btn-sm btn-outline-danger remove-item-btn"><i class="bi bi-trash"></i> Hapus</button></div>
    </div>`;
    document.getElementById('itemsContainerEdit').appendChild(div);
});

document.getElementById('itemsContainerEdit')?.addEventListener('click', function(e) {
    if (e.target.closest('.remove-item-btn')) {
        const rows = document.querySelectorAll('#itemsContainerEdit .item-row');
        if (rows.length > 1) e.target.closest('.item-row').remove();
    }
});

// Swap __lainnya__ with custom text before edit form submit
document.querySelector('#editModal form')?.addEventListener('submit', function() {
    this.querySelectorAll('select[name="aspek[]"]').forEach(sel => {
        if (sel.value === '__lainnya__') {
            const custom = sel.closest('.row, .item-row')?.querySelector('.aspek-custom');
            if (custom?.value) sel.value = custom.value;
        }
    });
});
</script>

<?= $this->endSection() ?>
