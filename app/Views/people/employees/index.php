<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<style>
@keyframes fadeUp { from { opacity:0; transform:translateY(16px); } to { opacity:1; transform:translateY(0); } }
.anim-fade-up { opacity:0; animation:fadeUp .45s cubic-bezier(.22,.68,0,1.15) forwards; }
.emp-avatar { width:36px; height:36px; border-radius:50%; object-fit:cover; }
.emp-avatar-placeholder { width:36px; height:36px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:.8rem; flex-shrink:0; background:var(--c-avatar-bg); color:var(--c-avatar-fg); }
</style>
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4 anim-fade-up" style="animation-delay:.05s">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-people-fill me-2"></i>Data Karyawan</h4>
        <small class="text-muted">People Development — Employee Record</small>
    </div>
    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
        <i class="bi bi-plus-lg me-1"></i> Tambah Karyawan
    </button>
</div>

<!-- Stats strip -->
<?php
$total  = count($employees);
$aktif  = count(array_filter($employees, fn($e) => $e['status'] === 'aktif'));
$resign = count(array_filter($employees, fn($e) => $e['status'] === 'resign'));
?>
<div class="row g-3 mb-4 anim-fade-up" style="animation-delay:.1s">
    <div class="col-6 col-md-3">
        <div class="card text-center h-100"><div class="card-body py-3">
            <div class="fw-bold fs-4"><?= $total ?></div>
            <div class="small text-muted">Total Karyawan</div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center h-100"><div class="card-body py-3">
            <div class="fw-bold fs-4 text-success"><?= $aktif ?></div>
            <div class="small text-muted">Aktif</div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center h-100"><div class="card-body py-3">
            <div class="fw-bold fs-4 text-secondary"><?= $resign ?></div>
            <div class="small text-muted">Resign / Non-aktif</div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center h-100"><div class="card-body py-3">
            <div class="fw-bold fs-4 text-primary"><?= count($departments) ?></div>
            <div class="small text-muted">Departemen</div>
        </div></div>
    </div>
</div>

<!-- Filter & Search -->
<div class="card mb-3 anim-fade-up" style="animation-delay:.15s">
<div class="card-body py-2">
<div class="row g-2 align-items-center">
    <div class="col">
        <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Cari nama atau NIK...">
    </div>
    <div class="col-auto">
        <select id="filterDept" class="form-select form-select-sm" style="min-width:140px">
            <option value="">Semua Dept</option>
            <?php foreach ($departments as $d): ?>
            <option value="<?= esc($d['name']) ?>"><?= esc($d['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto">
        <select id="filterStatus" class="form-select form-select-sm">
            <option value="">Semua Status</option>
            <option value="aktif">Aktif</option>
            <option value="resign">Resign</option>
            <option value="cuti_panjang">Cuti Panjang</option>
            <option value="pensiun">Pensiun</option>
        </select>
    </div>
</div>
</div>
</div>

<!-- Table -->
<div class="card anim-fade-up" style="animation-delay:.2s">
<div class="card-body p-0">
<div class="table-responsive">
<table class="table table-hover align-middle mb-0" id="empTable">
<thead>
<tr>
    <th class="ps-3" style="width:36px"></th>
    <th>Nama</th>
    <th>NIK</th>
    <th>Departemen</th>
    <th>Jabatan</th>
    <th>Masa Kerja</th>
    <th class="text-center">Status</th>
    <th style="width:60px"></th>
</tr>
</thead>
<tbody>
<?php if (empty($employees)): ?>
<tr><td colspan="8" class="text-center text-muted py-5">
    <i class="bi bi-people opacity-25 display-5 d-block mb-2"></i>
    Belum ada data karyawan.
</td></tr>
<?php else: ?>
<?php foreach ($employees as $e): ?>
<tr data-nama="<?= esc(strtolower($e['nama'])) ?>" data-nik="<?= esc(strtolower($e['nik'] ?? '')) ?>"
    data-dept="<?= esc($e['dept_name'] ?? '') ?>" data-status="<?= esc($e['status']) ?>">
    <td class="ps-3">
        <?php if ($e['foto']): ?>
        <img src="<?= base_url('uploads/people/photos/'.$e['foto']) ?>" class="emp-avatar">
        <?php else: ?>
        <div class="emp-avatar-placeholder"><?= strtoupper(substr($e['nama'], 0, 1)) ?></div>
        <?php endif; ?>
    </td>
    <td class="fw-semibold"><?= esc($e['nama']) ?></td>
    <td class="small text-muted"><?= esc($e['nik'] ?? '—') ?></td>
    <td class="small"><?= esc($e['dept_name'] ?? '—') ?></td>
    <td class="small"><?= esc($e['jabatan'] ?? '—') ?></td>
    <td class="small text-muted"><?= $e['masa_kerja'] ?></td>
    <td class="text-center">
        <?php $sc = ['aktif'=>'success','resign'=>'secondary','cuti_panjang'=>'warning','pensiun'=>'info']; ?>
        <span class="badge bg-<?= $sc[$e['status']] ?? 'secondary' ?>-subtle text-<?= $sc[$e['status']] ?? 'secondary' ?> small">
            <?= ucfirst(str_replace('_', ' ', $e['status'])) ?>
        </span>
    </td>
    <td>
        <a href="<?= base_url('people/employees/'.$e['id']) ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-eye"></i>
        </a>
    </td>
</tr>
<?php endforeach; ?>
<?php endif; ?>
</tbody>
</table>
</div>
</div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
<form method="POST" action="<?= base_url('people/employees/add') ?>" enctype="multipart/form-data">
<?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title fw-semibold">Tambah Karyawan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="row g-3">
        <div class="col-md-8">
            <label class="form-label small fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
            <input type="text" name="nama" class="form-control" required>
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-semibold">NIK</label>
            <input type="text" name="nik" class="form-control">
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-semibold">Jenis Kelamin</label>
            <select name="jenis_kelamin" class="form-select">
                <option value="">— Pilih —</option>
                <option value="L">Laki-laki</option>
                <option value="P">Perempuan</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-semibold">Tanggal Lahir</label>
            <input type="date" name="tanggal_lahir" class="form-control">
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-semibold">Tanggal Masuk <span class="text-danger">*</span></label>
            <input type="date" name="tanggal_masuk" class="form-control" required>
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold">Departemen</label>
            <select name="dept_id" id="addDeptId" class="form-select" onchange="onDeptChange('addDeptId','addDivId','addJabatanId')">
                <option value="">— Pilih Dept —</option>
                <?php foreach ($departments as $d): ?>
                <option value="<?= $d['id'] ?>" data-division-id="<?= $d['division_id'] ?? '' ?>"><?= esc($d['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold">Divisi <small class="text-muted fw-normal">(isi jika tanpa dept)</small></label>
            <select id="addDivId" class="form-select" onchange="loadJabatans('addDeptId','addDivId','addJabatanId')">
                <option value="">— Pilih Divisi —</option>
                <?php foreach ($divisions as $dv): ?>
                <option value="<?= $dv['id'] ?>"><?= esc($dv['nama']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold">Jabatan</label>
            <select name="jabatan_id" id="addJabatanId" class="form-select" onchange="filterAtasan('addJabatanId','addAtasanId')">
                <option value="">— Pilih Jabatan —</option>
            </select>
            <input type="hidden" name="jabatan" value="">
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold">Atasan Langsung</label>
            <select name="atasan_id" id="addAtasanId" class="form-select">
                <option value="">— Pilih jabatan dulu —</option>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold">No. HP</label>
            <input type="text" name="no_hp" class="form-control">
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold">Email</label>
            <input type="email" name="email" class="form-control">
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold">Status</label>
            <select name="status" class="form-select">
                <option value="aktif">Aktif</option>
                <option value="resign">Resign</option>
                <option value="cuti_panjang">Cuti Panjang</option>
                <option value="pensiun">Pensiun</option>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold">Foto</label>
            <input type="file" name="foto" class="form-control" accept="image/*">
        </div>
        <div class="col-12">
            <label class="form-label small fw-semibold">Catatan</label>
            <textarea name="catatan" class="form-control" rows="2"></textarea>
        </div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
    <button type="submit" class="btn btn-primary">Tambah</button>
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

// Flat map: jabatan_id → grade
const jabatanGradeMap = {};
Object.entries(jabatanMap).forEach(([key, val]) => {
    if (key === '_div') {
        Object.values(val).forEach(arr => arr.forEach(j => { jabatanGradeMap[j.id] = j.grade; }));
    } else {
        (val || []).forEach(j => { jabatanGradeMap[j.id] = j.grade; });
    }
});

function filterAtasan(jabSelectId, atasanSelectId, excludeEmpId = 0, selectedAtasanId = 0) {
    const jabId  = parseInt(document.getElementById(jabSelectId).value) || 0;
    const grade  = jabatanGradeMap[jabId] ?? null;
    const sel    = document.getElementById(atasanSelectId);
    sel.innerHTML = '<option value="">— Tidak ada —</option>';
    employeeList
        .filter(e => {
            if (e.id === excludeEmpId) return false;
            if (!grade) return false;                              // jabatan belum dipilih → kosongkan
            if (e.jabatan_grade !== null && e.jabatan_grade >= grade) return false; // jelas lebih junior/setara → buang
            return true;                                           // grade null (belum di-set) atau lebih senior → tampilkan
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

const searchInput  = document.getElementById('searchInput');
const filterDept   = document.getElementById('filterDept');
const filterStatus = document.getElementById('filterStatus');

function filterTable() {
    const q    = searchInput.value.toLowerCase();
    const dept = filterDept.value.toLowerCase();
    const st   = filterStatus.value;
    document.querySelectorAll('#empTable tbody tr[data-nama]').forEach(tr => {
        const matchQ  = !q    || tr.dataset.nama.includes(q) || tr.dataset.nik.includes(q);
        const matchD  = !dept || tr.dataset.dept.toLowerCase() === dept;
        const matchSt = !st   || tr.dataset.status === st;
        tr.style.display = (matchQ && matchD && matchSt) ? '' : 'none';
    });
}

searchInput.addEventListener('input', filterTable);
filterDept.addEventListener('change', filterTable);
filterStatus.addEventListener('change', filterTable);
</script>
<?= $this->endSection() ?>
