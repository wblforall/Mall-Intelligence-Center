<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= base_url('people/employees') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h4 class="fw-bold mb-0">Pengajuan Perubahan Data</h4>
    <?php if (! empty($pending)): ?><span class="badge bg-danger"><?= count($pending) ?> menunggu</span><?php endif; ?>
</div>

<!-- Pending -->
<div class="card mb-4">
<div class="card-header"><h6 class="mb-0 fw-semibold"><i class="bi bi-hourglass-split me-2"></i>Menunggu Persetujuan</h6></div>
<div class="card-body p-0">
<?php if (empty($pending)): ?>
<p class="text-muted text-center py-4 small mb-0">Tidak ada pengajuan yang menunggu.</p>
<?php else: ?>
<div class="table-responsive">
<table class="table table-sm align-middle mb-0">
<thead class="table-light"><tr><th class="ps-3">Karyawan</th><th>Data</th><th>Lama</th><th>Baru</th><th>Tanggal</th><th class="text-end pe-3">Aksi</th></tr></thead>
<tbody>
<?php foreach ($pending as $r): ?>
<tr>
    <td class="ps-3">
        <div class="fw-semibold small"><?= esc($r['employee_nama']) ?></div>
        <div class="text-muted" style="font-size:.72rem"><?= esc($r['dept_name'] ?? '—') ?></div>
    </td>
    <td class="small fw-semibold"><?= esc($r['label']) ?></td>
    <td class="small text-muted">
        <?php if ($r['field'] === 'foto'): ?>
            <?php if (! empty($r['value_old'])): ?><img src="<?= base_url('people/photo/' . $r['value_old']) ?>" style="width:40px;height:40px;border-radius:6px;object-fit:cover"><?php else: ?>—<?php endif; ?>
        <?php else: ?>
            <?= esc($r['value_old']) ?: '—' ?>
        <?php endif; ?>
    </td>
    <td class="small">
        <?php if ($r['field'] === 'foto'): ?>
            <img src="<?= base_url('people/photo/' . $r['value_new']) ?>" style="width:40px;height:40px;border-radius:6px;object-fit:cover">
        <?php else: ?>
            <span class="fw-semibold text-success"><?= esc($r['value_new']) ?></span>
        <?php endif; ?>
    </td>
    <td class="small text-nowrap text-muted"><?= date('d M Y H:i', strtotime($r['created_at'])) ?></td>
    <td class="text-end pe-3 text-nowrap">
        <form method="POST" action="<?= base_url('people/change-requests/'.$r['id'].'/approve') ?>" class="d-inline">
            <?= csrf_field() ?>
            <button class="btn btn-sm btn-success" onclick="return confirm('Setujui & terapkan perubahan ini?')"><i class="bi bi-check-lg"></i> Setujui</button>
        </form>
        <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal<?= $r['id'] ?>"><i class="bi bi-x-lg"></i> Tolak</button>

        <div class="modal fade" id="rejectModal<?= $r['id'] ?>" tabindex="-1">
        <div class="modal-dialog"><div class="modal-content">
        <form method="POST" action="<?= base_url('people/change-requests/'.$r['id'].'/reject') ?>">
            <?= csrf_field() ?>
            <div class="modal-header"><h6 class="modal-title fw-semibold">Tolak Pengajuan — <?= esc($r['label']) ?></h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body text-start">
                <label class="form-label small">Alasan penolakan <span class="text-danger">*</span></label>
                <textarea name="catatan" class="form-control" rows="3" required placeholder="Jelaskan alasan penolakan..."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-sm btn-danger">Tolak Pengajuan</button>
            </div>
        </form>
        </div></div></div>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php endif; ?>
</div>
</div>

<!-- Dokumen Menunggu Verifikasi -->
<div class="card mb-4">
<div class="card-header d-flex align-items-center">
    <h6 class="mb-0 fw-semibold"><i class="bi bi-folder-check me-2"></i>Dokumen Menunggu Verifikasi</h6>
    <?php if (! empty($pendingDocs)): ?><span class="badge bg-danger ms-2"><?= count($pendingDocs) ?></span><?php endif; ?>
</div>
<div class="card-body p-0">
<?php if (empty($pendingDocs)): ?>
<p class="text-muted text-center py-4 small mb-0">Tidak ada dokumen yang menunggu verifikasi.</p>
<?php else: ?>
<div class="table-responsive">
<table class="table table-sm align-middle mb-0">
<thead class="table-light"><tr><th class="ps-3">Karyawan</th><th>Dokumen</th><th>File</th><th>Tanggal</th><th class="text-end pe-3">Aksi</th></tr></thead>
<tbody>
<?php foreach ($pendingDocs as $d): ?>
<tr>
    <td class="ps-3">
        <div class="fw-semibold small"><?= esc($d['employee_nama']) ?></div>
        <div class="text-muted" style="font-size:.72rem"><?= esc($d['dept_name'] ?? '—') ?></div>
    </td>
    <td class="small fw-semibold"><?= esc(\App\Models\EmployeeDocumentModel::jenisLabel($d['jenis'], $d['nama_dokumen'])) ?></td>
    <td class="small"><a href="<?= base_url('people/documents/'.$d['id'].'/view') ?>" target="_blank"><i class="bi bi-file-earmark-text me-1"></i>Lihat</a></td>
    <td class="small text-nowrap text-muted"><?= date('d M Y H:i', strtotime($d['created_at'])) ?></td>
    <td class="text-end pe-3 text-nowrap">
        <form method="POST" action="<?= base_url('people/documents/'.$d['id'].'/approve') ?>" class="d-inline">
            <?= csrf_field() ?>
            <button class="btn btn-sm btn-success" onclick="return confirm('Verifikasi dokumen ini?')"><i class="bi bi-check-lg"></i> Verifikasi</button>
        </form>
        <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectDoc<?= $d['id'] ?>"><i class="bi bi-x-lg"></i> Tolak</button>
        <div class="modal fade" id="rejectDoc<?= $d['id'] ?>" tabindex="-1">
        <div class="modal-dialog"><div class="modal-content">
        <form method="POST" action="<?= base_url('people/documents/'.$d['id'].'/reject') ?>">
            <?= csrf_field() ?>
            <div class="modal-header"><h6 class="modal-title fw-semibold">Tolak Dokumen</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body text-start">
                <label class="form-label small">Alasan penolakan <span class="text-danger">*</span></label>
                <textarea name="catatan" class="form-control" rows="3" required placeholder="mis. foto buram / dokumen salah"></textarea>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-sm btn-danger">Tolak</button></div>
        </form>
        </div></div></div>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php endif; ?>
</div>
</div>

<!-- Riwayat -->
<div class="card mb-4">
<div class="card-header"><h6 class="mb-0 fw-semibold"><i class="bi bi-clock-history me-2"></i>Riwayat</h6></div>
<div class="card-body p-0">
<?php if (empty($processed)): ?>
<p class="text-muted text-center py-4 small mb-0">Belum ada riwayat.</p>
<?php else: ?>
<div class="table-responsive">
<table class="table table-sm align-middle mb-0">
<thead class="table-light"><tr><th class="ps-3">Karyawan</th><th>Data</th><th>Baru</th><th>Status</th><th>Catatan</th><th>Diproses</th></tr></thead>
<tbody>
<?php $sb = ['approved'=>'success','rejected'=>'danger']; foreach ($processed as $r): ?>
<tr>
    <td class="ps-3 small fw-semibold"><?= esc($r['employee_nama']) ?></td>
    <td class="small"><?= esc($r['label']) ?></td>
    <td class="small text-muted"><?= $r['field'] === 'foto' ? '(foto)' : esc($r['value_new']) ?></td>
    <td><span class="badge bg-<?= $sb[$r['status']] ?? 'secondary' ?>"><?= ucfirst($r['status']) ?></span></td>
    <td class="small text-muted"><?= esc($r['catatan'] ?? '') ?: '—' ?></td>
    <td class="small text-nowrap text-muted"><?= $r['reviewed_at'] ? date('d M Y', strtotime($r['reviewed_at'])) : '—' ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php endif; ?>
</div>
</div>

<?= $this->endSection() ?>
