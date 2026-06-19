<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="mb-3">
    <h4 class="fw-bold mb-0">Appraisal — Penilaian Kinerja</h4>
    <small class="text-muted">HR — kelola template, buka periode, dan finalisasi penilaian</small>
</div>

<?php if (session('error')): ?><div class="alert alert-danger py-2 small"><?= esc(session('error')) ?></div><?php endif; ?>
<?php if (session('success')): ?><div class="alert alert-success py-2 small"><?= esc(session('success')) ?></div><?php endif; ?>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card h-100">
        <div class="card-body">
            <div class="d-flex align-items-center gap-2 mb-2">
                <i class="bi bi-bullseye fs-4 text-primary"></i>
                <h6 class="mb-0 fw-bold">Template KPI</h6>
            </div>
            <p class="small text-muted mb-2">Per jabatan. Disusun manager, disetujui HR.</p>
            <div class="d-flex gap-3 small mb-3">
                <span><b><?= $tplStats['approved'] ?></b> disetujui</span>
                <span class="text-warning"><b><?= $tplStats['submitted'] ?></b> perlu review</span>
            </div>
            <a href="<?= base_url('appraisal/templates') ?>" class="btn btn-sm btn-outline-primary w-100 mb-2">Kelola Template</a>
            <a href="<?= base_url('appraisal/authors') ?>" class="btn btn-sm btn-outline-secondary w-100"><i class="bi bi-person-gear me-1"></i>Tunjuk Penyusun</a>
        </div>
        </div>
    </div>
</div>

<!-- Periode -->
<div class="card">
<div class="card-header d-flex justify-content-between align-items-center py-2">
    <h6 class="mb-0 fw-semibold"><i class="bi bi-calendar-range me-1"></i>Periode Penilaian</h6>
    <button class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#newPeriod"><i class="bi bi-plus-lg me-1"></i>Buka Periode</button>
</div>
<div class="collapse" id="newPeriod">
<div class="card-body border-bottom bg-body-tertiary">
    <form method="POST" action="<?= base_url('appraisal/periods/create') ?>" class="row g-2 align-items-end">
        <?= csrf_field() ?>
        <div class="col-md-4">
            <label class="form-label small fw-semibold mb-1">Nama Periode</label>
            <input type="text" name="nama" class="form-control form-control-sm" placeholder="mis. Juli - Desember 2025" required>
        </div>
        <div class="col-auto">
            <label class="form-label small fw-semibold mb-1">Mulai</label>
            <input type="date" name="tanggal_mulai" class="form-control form-control-sm">
        </div>
        <div class="col-auto">
            <label class="form-label small fw-semibold mb-1">Selesai</label>
            <input type="date" name="tanggal_selesai" class="form-control form-control-sm">
        </div>
        <div class="col-auto">
            <label class="form-label small fw-semibold mb-1">Tipe</label>
            <select name="tipe" id="periodTipe" class="form-select form-select-sm">
                <option value="reguler">Reguler (semua karyawan)</option>
                <option value="khusus">Khusus (per karyawan)</option>
            </select>
        </div>
        <div class="col-auto">
            <button class="btn btn-sm btn-success"><i class="bi bi-check-lg me-1"></i><span id="periodBtnLabel">Buka & Generate Form</span></button>
        </div>
        <div class="col-12"><small class="text-muted" id="periodHint"><b>Reguler:</b> sistem membuat form penilaian untuk semua karyawan yang jabatannya punya template <b>disetujui</b>. <b>Khusus:</b> periode dibuka kosong, lalu Anda menambahkan karyawan satu per satu (mis. evaluasi kontrak).</small></div>
    </form>
    <script>
    (function(){
        var t=document.getElementById('periodTipe'), b=document.getElementById('periodBtnLabel');
        if(t) t.addEventListener('change',function(){ b.textContent = this.value==='khusus' ? 'Buka Periode Khusus' : 'Buka & Generate Form'; });
    })();
    </script>
</div>
</div>
<div class="card-body p-0">
<div class="table-responsive">
<table class="table table-hover align-middle mb-0">
<thead class="small text-muted"><tr><th class="ps-3">Periode</th><th>Rentang</th><th>Status</th><th></th></tr></thead>
<tbody>
<?php if (empty($periods)): ?>
<tr><td colspan="4" class="text-center text-muted py-4">Belum ada periode.</td></tr>
<?php else: foreach ($periods as $p): ?>
<tr>
    <td class="ps-3 fw-medium"><?= esc($p['nama']) ?>
        <?php if (($p['tipe'] ?? 'reguler') === 'khusus'): ?><span class="badge bg-info-subtle text-info ms-1" style="font-size:.62rem">Khusus</span><?php endif; ?>
    </td>
    <td class="small text-muted"><?= $p['tanggal_mulai'] ? date('d M Y', strtotime($p['tanggal_mulai'])) : '—' ?> s/d <?= $p['tanggal_selesai'] ? date('d M Y', strtotime($p['tanggal_selesai'])) : '—' ?></td>
    <td><span class="badge bg-<?= $p['status']==='open'?'success':'secondary' ?>"><?= $p['status']==='open'?'Terbuka':'Ditutup' ?></span></td>
    <td class="text-end pe-3"><a href="<?= base_url('appraisal/periods/' . $p['id']) ?>" class="btn btn-sm btn-outline-primary">Lihat</a></td>
</tr>
<?php endforeach; endif; ?>
</tbody>
</table>
</div>
</div>
</div>
<?= $this->endSection() ?>
