<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<style>
@keyframes fadeUp { from { opacity:0; transform:translateY(16px); } to { opacity:1; transform:translateY(0); } }
.anim-fade-up { opacity:0; animation:fadeUp .45s cubic-bezier(.22,.68,0,1.15) forwards; }
.emp-photo { width:80px; height:80px; border-radius:50%; object-fit:cover; }
.emp-photo-placeholder { width:80px; height:80px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:2rem; font-weight:700; background:var(--c-avatar-bg); color:var(--c-avatar-fg); }
</style>
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php
$sc = ['aktif'=>'success','resign'=>'secondary','cuti_panjang'=>'warning','pensiun'=>'info'];
$statusColor = $sc[$employee['status']] ?? 'secondary';
$statusLabel = ucfirst(str_replace('_', ' ', $employee['status']));
?>

<!-- Header -->
<div class="d-flex align-items-center gap-3 mb-4 anim-fade-up" style="animation-delay:.05s">
    <a href="<?= base_url('people/employees') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <?php if ($employee['foto']): ?>
    <img src="<?= base_url('uploads/people/photos/'.$employee['foto']) ?>" class="emp-photo">
    <?php else: ?>
    <div class="emp-photo-placeholder"><?= strtoupper(substr($employee['nama'], 0, 1)) ?></div>
    <?php endif; ?>
    <div class="flex-grow-1">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <h4 class="fw-bold mb-0"><?= esc($employee['nama']) ?></h4>
            <span class="badge bg-<?= $statusColor ?>-subtle text-<?= $statusColor ?>"><?= $statusLabel ?></span>
        </div>
        <div class="text-muted small mt-1 d-flex flex-wrap gap-3">
            <?php if ($employee['nik']): ?><span><i class="bi bi-person-badge me-1"></i><?= esc($employee['nik']) ?></span><?php endif; ?>
            <?php if ($employee['jabatan']): ?><span><i class="bi bi-briefcase me-1"></i><?= esc($employee['jabatan']) ?></span><?php endif; ?>
            <?php if ($employee['dept_name']): ?><span><i class="bi bi-diagram-3 me-1"></i><?= esc($employee['dept_name']) ?></span><?php endif; ?>
            <span><i class="bi bi-calendar-check me-1"></i>Masuk <?= date('d M Y', strtotime($employee['tanggal_masuk'])) ?> · <?= $employee['masa_kerja'] ?></span>
        </div>
    </div>
    <button class="btn btn-sm btn-outline-secondary ms-auto" data-bs-toggle="modal" data-bs-target="#editModal">
        <i class="bi bi-pencil me-1"></i> Edit
    </button>
</div>

<!-- Info Card -->
<div class="card mb-4 anim-fade-up" style="animation-delay:.1s">
<div class="card-header"><h6 class="mb-0 fw-semibold"><i class="bi bi-person me-2"></i>Informasi Karyawan</h6></div>
<div class="card-body">
    <div class="row g-3" style="font-size:.88rem">
        <div class="col-md-4">
            <div class="text-muted small">Jenis Kelamin</div>
            <div><?= $employee['jenis_kelamin'] === 'L' ? 'Laki-laki' : ($employee['jenis_kelamin'] === 'P' ? 'Perempuan' : '—') ?></div>
        </div>
        <div class="col-md-4">
            <div class="text-muted small">Tanggal Lahir</div>
            <div><?= $employee['tanggal_lahir'] ? date('d M Y', strtotime($employee['tanggal_lahir'])) : '—' ?></div>
        </div>
        <div class="col-md-4">
            <div class="text-muted small">No. HP</div>
            <div><?= $employee['no_hp'] ? esc($employee['no_hp']) : '—' ?></div>
        </div>
        <div class="col-md-4">
            <div class="text-muted small">Email</div>
            <div><?= $employee['email'] ? esc($employee['email']) : '—' ?></div>
        </div>
        <div class="col-md-4">
            <div class="text-muted small">Tanggal Masuk</div>
            <div><?= date('d M Y', strtotime($employee['tanggal_masuk'])) ?></div>
        </div>
        <div class="col-md-4">
            <div class="text-muted small">Masa Kerja</div>
            <div class="fw-semibold"><?= $employee['masa_kerja'] ?></div>
        </div>
        <?php if ($employee['catatan']): ?>
        <div class="col-12">
            <div class="text-muted small">Catatan</div>
            <div><?= esc($employee['catatan']) ?></div>
        </div>
        <?php endif; ?>
    </div>
</div>
</div>

<!-- Riwayat Jabatan -->
<div class="card mb-4 anim-fade-up" id="positions" style="animation-delay:.15s">
<div class="card-header d-flex justify-content-between align-items-center">
    <h6 class="mb-0 fw-semibold"><i class="bi bi-briefcase me-2"></i>Riwayat Jabatan</h6>
    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addPositionModal">
        <i class="bi bi-plus-lg me-1"></i> Tambah
    </button>
</div>
<div class="card-body p-0">
<?php if (empty($positions)): ?>
<p class="text-muted text-center py-4 small mb-0">Belum ada riwayat jabatan.</p>
<?php else: ?>
<div class="table-responsive">
<table class="table table-sm align-middle mb-0">
<thead class="table-light">
<tr><th class="ps-3">Jabatan</th><th>Departemen</th><th>Mulai</th><th>Selesai</th><th>Keterangan</th><th></th></tr>
</thead>
<tbody>
<?php foreach ($positions as $p): ?>
<tr>
    <td class="ps-3 fw-semibold small">
        <?= esc($p['jabatan']) ?>
        <?php if (! $p['tanggal_selesai']): ?>
        <span class="badge bg-success-subtle text-success ms-1" style="font-size:.6rem">Sekarang</span>
        <?php endif; ?>
    </td>
    <td class="small text-muted"><?= esc($p['dept_name'] ?? '—') ?></td>
    <td class="small text-nowrap"><?= date('d M Y', strtotime($p['tanggal_mulai'])) ?></td>
    <td class="small text-nowrap"><?= $p['tanggal_selesai'] ? date('d M Y', strtotime($p['tanggal_selesai'])) : '—' ?></td>
    <td class="small text-muted"><?= esc($p['keterangan'] ?? '—') ?></td>
    <td>
        <a href="<?= base_url('people/employees/'.$employee['id'].'/positions/'.$p['id'].'/delete') ?>"
           class="btn btn-sm btn-link text-danger p-0" onclick="return confirm('Hapus riwayat jabatan ini?')">
            <i class="bi bi-x-circle"></i>
        </a>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php endif; ?>
</div>
</div>

<!-- Sertifikat -->
<div class="card mb-4 anim-fade-up" id="certificates" style="animation-delay:.2s">
<div class="card-header d-flex justify-content-between align-items-center">
    <h6 class="mb-0 fw-semibold"><i class="bi bi-patch-check me-2"></i>Sertifikat</h6>
    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addCertModal">
        <i class="bi bi-plus-lg me-1"></i> Tambah
    </button>
</div>
<div class="card-body p-0">
<?php if (empty($certificates)): ?>
<p class="text-muted text-center py-4 small mb-0">Belum ada sertifikat.</p>
<?php else: ?>
<div class="table-responsive">
<table class="table table-sm align-middle mb-0">
<thead class="table-light">
<tr><th class="ps-3">Nama Sertifikat</th><th>Nomor</th><th>Penerbit</th><th>Terbit</th><th>Kadaluarsa</th><th class="text-center">Status</th><th class="text-center">File</th><th></th></tr>
</thead>
<tbody>
<?php foreach ($certificates as $c): ?>
<tr>
    <td class="ps-3 fw-semibold small"><?= esc($c['nama_sertifikat']) ?></td>
    <td class="small text-muted"><?= esc($c['nomor_sertifikat'] ?? '—') ?></td>
    <td class="small text-muted"><?= esc($c['penerbit'] ?? '—') ?></td>
    <td class="small text-nowrap"><?= $c['tanggal_terbit'] ? date('d M Y', strtotime($c['tanggal_terbit'])) : '—' ?></td>
    <td class="small text-nowrap"><?= $c['tanggal_kadaluarsa'] ? date('d M Y', strtotime($c['tanggal_kadaluarsa'])) : '—' ?></td>
    <td class="text-center">
        <span class="badge bg-<?= $c['status']['color'] ?>-subtle text-<?= $c['status']['color'] ?>" style="font-size:.65rem">
            <?= $c['status']['label'] ?>
        </span>
    </td>
    <td class="text-center">
        <?php if ($c['file_name']): ?>
        <a href="<?= base_url('uploads/people/certificates/'.$c['file_name']) ?>" target="_blank"
           class="btn btn-sm btn-outline-secondary" title="<?= esc($c['file_original']) ?>">
            <i class="bi bi-file-earmark"></i>
        </a>
        <?php else: ?>
        <span class="text-muted small">—</span>
        <?php endif; ?>
    </td>
    <td>
        <a href="<?= base_url('people/employees/'.$employee['id'].'/certificates/'.$c['id'].'/delete') ?>"
           class="btn btn-sm btn-link text-danger p-0" onclick="return confirm('Hapus sertifikat ini?')">
            <i class="bi bi-x-circle"></i>
        </a>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php endif; ?>
</div>
</div>

<!-- Training History -->
<div class="card mb-4 anim-fade-up" id="training" style="animation-delay:.25s">
<div class="card-header d-flex align-items-center justify-content-between">
    <h6 class="mb-0 fw-semibold"><i class="bi bi-mortarboard me-2"></i>Riwayat Training</h6>
    <span class="badge bg-secondary"><?= count($trainings) ?></span>
</div>
<?php if (empty($trainings)): ?>
<div class="card-body text-center py-3 text-muted small">Belum ada riwayat training.</div>
<?php else: ?>
<div class="table-responsive">
<table class="table table-sm mb-0 align-middle">
    <thead><tr>
        <th>Program</th><th>Tipe</th><th>Vendor</th><th>Tanggal</th>
        <th class="text-center">Kehadiran</th><th class="text-center">Pre</th><th class="text-center">Post</th>
    </tr></thead>
    <tbody>
    <?php foreach ($trainings as $t):
        $kehadiranColor = ['registered'=>'secondary','hadir'=>'success','tidak_hadir'=>'danger','dibatalkan'=>'warning'][$t['status_kehadiran']] ?? 'secondary';
        $kehadiranLabel = ['registered'=>'Terdaftar','hadir'=>'Hadir','tidak_hadir'=>'Tdk Hadir','dibatalkan'=>'Batal'][$t['status_kehadiran']] ?? '-';
    ?>
    <tr>
        <td class="fw-medium" style="font-size:.83rem"><?= esc($t['nama']) ?></td>
        <td><span class="badge bg-<?= $t['tipe'] === 'internal' ? 'secondary' : 'primary' ?>" style="font-size:.65rem"><?= ucfirst($t['tipe']) ?></span></td>
        <td class="text-muted" style="font-size:.8rem"><?= esc($t['vendor'] ?? '—') ?></td>
        <td style="font-size:.8rem"><?= $t['tanggal_mulai'] ? date('d M Y', strtotime($t['tanggal_mulai'])) : '—' ?></td>
        <td class="text-center"><span class="badge bg-<?= $kehadiranColor ?>" style="font-size:.65rem"><?= $kehadiranLabel ?></span></td>
        <td class="text-center" style="font-size:.83rem"><?= $t['pre_test'] !== null ? $t['pre_test'] : '—' ?></td>
        <td class="text-center" style="font-size:.83rem"><?= $t['post_test'] !== null ? $t['post_test'] : '—' ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
<form method="POST" action="<?= base_url('people/employees/'.$employee['id'].'/edit') ?>" enctype="multipart/form-data">
<?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title fw-semibold">Edit Karyawan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="row g-3">
        <div class="col-md-8">
            <label class="form-label small fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
            <input type="text" name="nama" class="form-control" value="<?= esc($employee['nama']) ?>" required>
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-semibold">NIK</label>
            <input type="text" name="nik" class="form-control" value="<?= esc($employee['nik'] ?? '') ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-semibold">Jenis Kelamin</label>
            <select name="jenis_kelamin" class="form-select">
                <option value="">— Pilih —</option>
                <option value="L" <?= $employee['jenis_kelamin'] === 'L' ? 'selected' : '' ?>>Laki-laki</option>
                <option value="P" <?= $employee['jenis_kelamin'] === 'P' ? 'selected' : '' ?>>Perempuan</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-semibold">Tanggal Lahir</label>
            <input type="date" name="tanggal_lahir" class="form-control" value="<?= $employee['tanggal_lahir'] ?? '' ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-semibold">Tanggal Masuk <span class="text-danger">*</span></label>
            <input type="date" name="tanggal_masuk" class="form-control" value="<?= $employee['tanggal_masuk'] ?>" required>
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold">Departemen</label>
            <select name="dept_id" id="editDeptId" class="form-select" onchange="onDeptChange('editDeptId','editDivId','editJabatanId')">
                <option value="">— Pilih Dept —</option>
                <?php foreach ($departments as $d): ?>
                <option value="<?= $d['id'] ?>" data-division-id="<?= $d['division_id'] ?? '' ?>" <?= $employee['dept_id'] == $d['id'] ? 'selected' : '' ?>><?= esc($d['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold">Divisi <small class="text-muted fw-normal">(isi jika tanpa dept)</small></label>
            <select id="editDivId" class="form-select" onchange="loadJabatans('editDeptId','editDivId','editJabatanId')">
                <option value="">— Pilih Divisi —</option>
                <?php foreach ($divisions as $dv): ?>
                <option value="<?= $dv['id'] ?>" <?= $currentDivisionId == $dv['id'] ? 'selected' : '' ?>><?= esc($dv['nama']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold">Jabatan</label>
            <select name="jabatan_id" id="editJabatanId" class="form-select"
                onchange="filterAtasan('editJabatanId','editAtasanId',<?= $employee['id'] ?>)">
                <option value="">— Pilih Jabatan —</option>
            </select>
            <input type="hidden" name="jabatan" value="<?= esc($employee['jabatan'] ?? '') ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold">Atasan Langsung</label>
            <select name="atasan_id" id="editAtasanId" class="form-select">
                <option value="">— Tidak ada —</option>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold">No. HP</label>
            <input type="text" name="no_hp" class="form-control" value="<?= esc($employee['no_hp'] ?? '') ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold">Email</label>
            <input type="email" name="email" class="form-control" value="<?= esc($employee['email'] ?? '') ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold">Status</label>
            <select name="status" class="form-select">
                <?php foreach (['aktif','resign','cuti_panjang','pensiun'] as $s): ?>
                <option value="<?= $s ?>" <?= $employee['status'] === $s ? 'selected' : '' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold">Foto Baru</label>
            <input type="file" name="foto" class="form-control" accept="image/*">
            <?php if ($employee['foto']): ?>
            <div class="form-text">Sudah ada foto. Upload baru untuk mengganti.</div>
            <?php endif; ?>
        </div>
        <div class="col-12">
            <label class="form-label small fw-semibold">Catatan</label>
            <textarea name="catatan" class="form-control" rows="2"><?= esc($employee['catatan'] ?? '') ?></textarea>
        </div>
    </div>
</div>
<div class="modal-footer">
    <a href="<?= base_url('people/employees/'.$employee['id'].'/delete') ?>"
       class="btn btn-outline-danger me-auto"
       onclick="return confirm('Hapus karyawan <?= esc($employee['nama']) ?>? Semua data terkait akan dihapus.')">
        <i class="bi bi-trash me-1"></i> Hapus
    </a>
    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
    <button type="submit" class="btn btn-primary">Simpan</button>
</div>
</form>
</div></div></div>

<!-- Add Position Modal -->
<div class="modal fade" id="addPositionModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<form method="POST" action="<?= base_url('people/employees/'.$employee['id'].'/positions/add') ?>">
<?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title fw-semibold">Tambah Riwayat Jabatan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="mb-3">
        <label class="form-label small fw-semibold">Jabatan <span class="text-danger">*</span></label>
        <input type="text" name="jabatan" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label small fw-semibold">Departemen</label>
        <select name="dept_id" class="form-select">
            <option value="">— Pilih Dept —</option>
            <?php foreach ($departments as $d): ?>
            <option value="<?= $d['id'] ?>" <?= $employee['dept_id'] == $d['id'] ? 'selected' : '' ?>><?= esc($d['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label small fw-semibold">Tanggal Mulai <span class="text-danger">*</span></label>
        <input type="date" name="tanggal_mulai" class="form-control" required value="<?= date('Y-m-d') ?>">
    </div>
    <div class="mb-3">
        <label class="form-label small fw-semibold">Keterangan</label>
        <input type="text" name="keterangan" class="form-control" placeholder="Promosi, mutasi, dll.">
    </div>
    <div class="alert alert-info small py-2 mb-0">
        <i class="bi bi-info-circle me-1"></i>
        Jabatan aktif sebelumnya akan otomatis ditutup pada hari sebelum tanggal mulai ini.
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
    <button type="submit" class="btn btn-primary">Simpan</button>
</div>
</form>
</div></div></div>

<!-- Add Certificate Modal -->
<div class="modal fade" id="addCertModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<form method="POST" action="<?= base_url('people/employees/'.$employee['id'].'/certificates/add') ?>" enctype="multipart/form-data">
<?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title fw-semibold">Tambah Sertifikat</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="mb-3">
        <label class="form-label small fw-semibold">Nama Sertifikat <span class="text-danger">*</span></label>
        <input type="text" name="nama_sertifikat" class="form-control" required placeholder="Contoh: Google Analytics, K3, dll.">
    </div>
    <div class="row g-2 mb-3">
        <div class="col-6">
            <label class="form-label small fw-semibold">Nomor Sertifikat</label>
            <input type="text" name="nomor_sertifikat" class="form-control">
        </div>
        <div class="col-6">
            <label class="form-label small fw-semibold">Penerbit</label>
            <input type="text" name="penerbit" class="form-control">
        </div>
    </div>
    <div class="row g-2 mb-3">
        <div class="col-6">
            <label class="form-label small fw-semibold">Tanggal Terbit</label>
            <input type="date" name="tanggal_terbit" class="form-control">
        </div>
        <div class="col-6">
            <label class="form-label small fw-semibold">Tanggal Kadaluarsa</label>
            <input type="date" name="tanggal_kadaluarsa" class="form-control">
            <div class="form-text">Kosongkan jika permanen.</div>
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label small fw-semibold">Upload File</label>
        <input type="file" name="file_sertifikat" class="form-control" accept="image/*,.pdf">
        <div class="form-text">JPG, PNG, PDF maks 5MB</div>
    </div>
    <div class="mb-3">
        <label class="form-label small fw-semibold">Catatan</label>
        <input type="text" name="catatan" class="form-control">
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
    <button type="submit" class="btn btn-primary">Simpan</button>
</div>
</form>
</div></div></div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
const jabatanMap = <?= json_encode($jabatanMap) ?>;

const employeeList = <?= json_encode(array_values(array_map(fn($e) => [
    'id'            => (int)$e['id'],
    'nama'          => $e['nama'],
    'jabatan'       => $e['jabatan'] ?? '',
    'jabatan_grade' => $e['jabatan_grade'] !== null ? (int)$e['jabatan_grade'] : null,
], $allEmployees))) ?>;

const jabatanGradeMap = {};
Object.entries(jabatanMap).forEach(([key, val]) => {
    if (key === '_div') {
        Object.values(val).forEach(arr => arr.forEach(j => { jabatanGradeMap[j.id] = j.grade; }));
    } else {
        (val || []).forEach(j => { jabatanGradeMap[j.id] = j.grade; });
    }
});

function filterAtasan(jabSelectId, atasanSelectId, excludeEmpId = 0, selectedAtasanId = 0) {
    const jabId = parseInt(document.getElementById(jabSelectId).value) || 0;
    const grade = jabatanGradeMap[jabId] ?? null;
    const sel   = document.getElementById(atasanSelectId);
    sel.innerHTML = '<option value="">— Tidak ada —</option>';
    employeeList
        .filter(e => {
            if (e.id === excludeEmpId) return false;
            if (!grade) return false;
            if (e.jabatan_grade !== null && e.jabatan_grade >= grade) return false;
            return true;
        })
        .sort((a, b) => a.jabatan_grade - b.jabatan_grade || a.nama.localeCompare(b.nama))
        .forEach(e => {
            const opt = document.createElement('option');
            opt.value = e.id;
            opt.textContent = e.nama + (e.jabatan ? ` – ${e.jabatan}` : '');
            if (e.id === selectedAtasanId) opt.selected = true;
            sel.appendChild(opt);
        });
}

function onDeptChange(deptSelectId, divSelectId, jabSelectId) {
    const deptSel = document.getElementById(deptSelectId);
    const divSel  = document.getElementById(divSelectId);
    const divId   = deptSel.options[deptSel.selectedIndex]?.dataset.divisionId ?? '';
    if (divId) {
        divSel.value    = divId;
        divSel.disabled = true;
    } else {
        divSel.value    = '';
        divSel.disabled = false;
    }
    loadJabatans(deptSelectId, divSelectId, jabSelectId);
}

function loadJabatans(deptSelectId, divSelectId, jabSelectId, selectedJabId = 0) {
    const deptId = document.getElementById(deptSelectId).value;
    const divId  = document.getElementById(divSelectId).value;
    const jabSel = document.getElementById(jabSelectId);
    jabSel.innerHTML = '<option value="">— Pilih Jabatan —</option>';
    const deptJabs = deptId ? (jabatanMap[deptId] ?? []) : [];
    const divJabs  = divId  ? (jabatanMap['_div']?.[divId] ?? []) : [];
    const all = [...deptJabs, ...divJabs].sort((a,b) => a.grade - b.grade || a.nama.localeCompare(b.nama));
    all.forEach(j => {
        const opt = document.createElement('option');
        opt.value = j.id;
        opt.textContent = `G${j.grade} – ${j.nama}`;
        if (j.id == selectedJabId) opt.selected = true;
        jabSel.appendChild(opt);
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const deptSel = document.getElementById('editDeptId');
    const divSel  = document.getElementById('editDivId');
    if (deptSel.value) divSel.disabled = true;
    loadJabatans('editDeptId', 'editDivId', 'editJabatanId', <?= (int)($employee['jabatan_id'] ?? 0) ?>);
    filterAtasan('editJabatanId', 'editAtasanId', <?= $employee['id'] ?>, <?= (int)($employee['atasan_id'] ?? 0) ?>);
});
</script>
<?= $this->endSection() ?>
