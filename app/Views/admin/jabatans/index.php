<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3 fade-up" style="animation-delay:.05s">
    <h4 class="fw-bold mb-0"><i class="bi bi-person-badge-fill me-2"></i>Master Jabatan</h4>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addJabModal">
        <i class="bi bi-plus-lg me-1"></i> Tambah Jabatan
    </button>
</div>

<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success alert-dismissible fade show py-2" role="alert">
    <?= session()->getFlashdata('success') ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Filter bar -->
<div class="card mb-3 fade-up" style="animation-delay:.12s">
<div class="card-body py-2">
<form method="GET" class="row g-2 align-items-end">
    <div class="col-auto">
        <label class="form-label small fw-semibold mb-0">Filter Divisi</label>
        <select name="division_id" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">-- Semua --</option>
            <?php foreach ($divisions as $dv): ?>
            <option value="<?= $dv['id'] ?>" <?= $filterDiv == $dv['id'] ? 'selected' : '' ?>><?= esc($dv['nama']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto">
        <label class="form-label small fw-semibold mb-0">Filter Departemen</label>
        <select name="dept_id" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">-- Semua --</option>
            <?php foreach ($departments as $dept): ?>
            <option value="<?= $dept['id'] ?>" <?= $filterDept == $dept['id'] ? 'selected' : '' ?>>
                <?= esc($dept['division_nama'] ? $dept['division_nama'].' › ' : '') ?><?= esc($dept['name']) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php if ($filterDept || $filterDiv): ?>
    <div class="col-auto">
        <a href="<?= base_url('jabatans') ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
    </div>
    <?php endif; ?>
</form>
</div>
</div>

<?php if (empty($jabatans)): ?>
<div class="card"><div class="card-body text-center py-4 text-muted">
    <i class="bi bi-person-badge display-4 d-block mb-2 opacity-25"></i>
    <p class="mb-0">Belum ada jabatan.</p>
</div></div>
<?php else: ?>

<?php
// Group by division/dept for display
$grouped = [];
foreach ($jabatans as $j) {
    $divLabel  = $j['division_nama'] ?? ($j['dept_name'] ? '(Tanpa Divisi)' : '(Tanpa Dept/Divisi)');
    $deptLabel = $j['dept_name'] ?? null;
    $key = $deptLabel ? $divLabel . '||' . $deptLabel : $divLabel . '||';
    $grouped[$key][] = $j;
}
ksort($grouped);
?>

<?php $gi = 0; foreach ($grouped as $key => $rows): ?>
<?php [$divLabel, $deptLabel] = explode('||', $key, 2); ?>
<div class="card mb-3 fade-up" style="animation-delay:<?= .2 + $gi++ * .07 ?>s">
    <div class="card-header py-2 d-flex align-items-center gap-2">
        <i class="bi bi-layers text-primary"></i>
        <span class="fw-semibold"><?= esc($divLabel) ?></span>
        <?php if ($deptLabel): ?>
        <i class="bi bi-chevron-right text-muted small"></i>
        <span class="text-muted"><?= esc($deptLabel) ?></span>
        <?php else: ?>
        <span class="badge bg-info-subtle text-info fw-normal ms-1">Level Divisi</span>
        <?php endif; ?>
    </div>
    <div class="table-responsive">
    <table class="table table-sm table-hover mb-0 align-middle">
    <thead class="table-light">
        <tr>
            <th class="ps-3" style="width:40px">Grade</th>
            <th>Nama Jabatan</th>
            <th class="text-muted" style="width:160px">Atasan Jabatan</th>
            <th style="width:120px"></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $j): ?>
    <tr>
        <td class="ps-3">
            <span class="badge bg-secondary-subtle text-secondary fw-semibold">G<?= $j['grade'] ?></span>
        </td>
        <td><?= esc($j['nama']) ?></td>
        <td class="text-muted small"><?= $j['parent_nama'] ? esc($j['parent_nama']) : '<span class="text-muted fst-italic">—</span>' ?></td>
        <td class="text-end pe-3">
            <button class="btn btn-sm btn-outline-secondary me-1"
                data-bs-toggle="modal" data-bs-target="#editJabModal"
                data-id="<?= $j['id'] ?>"
                data-nama="<?= esc($j['nama']) ?>"
                data-grade="<?= esc($j['grade']) ?>"
                data-dept_id="<?= $j['dept_id'] ?? '' ?>"
                data-division_id="<?= $j['division_id'] ?? '' ?>"
                data-parent_jabatan_id="<?= $j['parent_jabatan_id'] ?? '' ?>">
                <i class="bi bi-pencil"></i>
            </button>
            <a href="<?= base_url('jabatans/'.$j['id'].'/delete') ?>"
               class="btn btn-sm btn-outline-danger"
               onclick="return confirm('Hapus jabatan <?= esc($j['nama']) ?>?')">
                <i class="bi bi-trash"></i>
            </a>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    </table>
    </div>
</div>
<?php endforeach; ?>

<?php endif; ?>

<!-- Add Jabatan Modal -->
<div class="modal fade" id="addJabModal" tabindex="-1">
<div class="modal-dialog">
<div class="modal-content">
<form method="POST" action="<?= base_url('jabatans/store') ?>">
<?= csrf_field() ?>
<div class="modal-header">
    <h5 class="modal-title fw-semibold">Tambah Jabatan</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
    <div class="mb-3">
        <label class="form-label small fw-semibold">Nama Jabatan <span class="text-danger">*</span></label>
        <input type="text" name="nama" class="form-control" placeholder="Contoh: Staff Loyalty" required>
    </div>
    <div class="mb-3">
        <label class="form-label small fw-semibold">Grade <span class="text-danger">*</span></label>
        <input type="number" name="grade" class="form-control" value="5" min="1" max="9" required>
        <div class="form-text">Grade 1 = paling senior/atas, 9 = paling junior.</div>
    </div>
    <div class="mb-3">
        <label class="form-label small fw-semibold">Departemen (jika jabatan level dept)</label>
        <select name="dept_id" id="addDeptId" class="form-select" onchange="syncAddDiv()">
            <option value="">-- Pilih Departemen (opsional) --</option>
            <?php foreach ($departments as $dept): ?>
            <option value="<?= $dept['id'] ?>" data-division="<?= $dept['division_id'] ?? '' ?>">
                <?= esc($dept['division_nama'] ? $dept['division_nama'].' › ' : '') ?><?= esc($dept['name']) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="mb-3" id="addDivRow">
        <label class="form-label small fw-semibold">Divisi (jika jabatan level divisi)</label>
        <select name="division_id" id="addDivId" class="form-select">
            <option value="">-- Pilih Divisi (opsional) --</option>
            <?php foreach ($divisions as $dv): ?>
            <option value="<?= $dv['id'] ?>"><?= esc($dv['nama']) ?></option>
            <?php endforeach; ?>
        </select>
        <div class="form-text">Dipilih otomatis jika dept sudah dipilih, atau isi manual untuk jabatan lintas dept.</div>
    </div>
    <div class="mb-3">
        <label class="form-label small fw-semibold">Atasan Jabatan <span class="text-muted fw-normal">(opsional)</span></label>
        <select name="parent_jabatan_id" id="addParentJab" class="form-select">
            <option value="">-- Tidak ada (jabatan teratas di scope ini) --</option>
        </select>
        <div class="form-text">Pilih jabatan atasan langsung untuk membangun hierarki di org chart.</div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
    <button type="submit" class="btn btn-primary">Tambah</button>
</div>
</form>
</div></div></div>

<!-- Edit Jabatan Modal -->
<div class="modal fade" id="editJabModal" tabindex="-1">
<div class="modal-dialog">
<div class="modal-content">
<form method="POST" id="editJabForm" action="">
<?= csrf_field() ?>
<div class="modal-header">
    <h5 class="modal-title fw-semibold">Edit Jabatan</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
    <div class="mb-3">
        <label class="form-label small fw-semibold">Nama Jabatan <span class="text-danger">*</span></label>
        <input type="text" name="nama" id="editJabNama" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label small fw-semibold">Grade <span class="text-danger">*</span></label>
        <input type="number" name="grade" id="editJabGrade" class="form-control" min="1" max="9" required>
    </div>
    <div class="mb-3">
        <label class="form-label small fw-semibold">Departemen</label>
        <select name="dept_id" id="editJabDept" class="form-select" onchange="syncEditDiv()">
            <option value="">-- Pilih Departemen (opsional) --</option>
            <?php foreach ($departments as $dept): ?>
            <option value="<?= $dept['id'] ?>" data-division="<?= $dept['division_id'] ?? '' ?>">
                <?= esc($dept['division_nama'] ? $dept['division_nama'].' › ' : '') ?><?= esc($dept['name']) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label small fw-semibold">Divisi</label>
        <select name="division_id" id="editJabDiv" class="form-select">
            <option value="">-- Pilih Divisi (opsional) --</option>
            <?php foreach ($divisions as $dv): ?>
            <option value="<?= $dv['id'] ?>"><?= esc($dv['nama']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label small fw-semibold">Atasan Jabatan <span class="text-muted fw-normal">(opsional)</span></label>
        <select name="parent_jabatan_id" id="editParentJab" class="form-select">
            <option value="">-- Tidak ada (jabatan teratas di scope ini) --</option>
        </select>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
    <button type="submit" class="btn btn-primary">Simpan</button>
</div>
</form>
</div></div></div>

<script>
const jabatansData = <?= $jabatansJson ?>;

function buildParentOptions(selectEl, deptId, divisionId, excludeId, selectedId) {
    selectEl.innerHTML = '<option value="">-- Tidak ada (jabatan teratas di scope ini) --</option>';
    jabatansData.forEach(j => {
        if (j.id == excludeId) return;
        const inDept = deptId && j.dept_id == deptId;
        const inDiv  = !deptId && divisionId && j.division_id == divisionId && !j.dept_id;
        if (!inDept && !inDiv) return;
        const opt = document.createElement('option');
        opt.value = j.id;
        opt.textContent = `G${j.grade} – ${j.nama}`;
        if (j.id == selectedId) opt.selected = true;
        selectEl.appendChild(opt);
    });
}

function syncAddDiv() {
    const deptSel = document.getElementById('addDeptId');
    const divSel  = document.getElementById('addDivId');
    const divId   = deptSel.options[deptSel.selectedIndex]?.dataset.division ?? '';
    if (divId) divSel.value = divId;
    buildParentOptions(
        document.getElementById('addParentJab'),
        deptSel.value, divSel.value, null, null
    );
}

function syncEditDiv() {
    const deptSel = document.getElementById('editJabDept');
    const divSel  = document.getElementById('editJabDiv');
    const divId   = deptSel.options[deptSel.selectedIndex]?.dataset.division ?? '';
    if (divId) divSel.value = divId;
    const excludeId = document.getElementById('editJabForm').dataset.editId;
    buildParentOptions(
        document.getElementById('editParentJab'),
        deptSel.value, divSel.value, excludeId, null
    );
}

document.getElementById('editJabModal').addEventListener('show.bs.modal', function(e) {
    const btn = e.relatedTarget;
    const id  = btn.dataset.id;
    document.getElementById('editJabForm').action = `<?= base_url('jabatans/') ?>${id}/update`;
    document.getElementById('editJabForm').dataset.editId = id;
    document.getElementById('editJabNama').value  = btn.dataset.nama;
    document.getElementById('editJabGrade').value = btn.dataset.grade;
    document.getElementById('editJabDept').value  = btn.dataset.dept_id;
    document.getElementById('editJabDiv').value   = btn.dataset.division_id;
    buildParentOptions(
        document.getElementById('editParentJab'),
        btn.dataset.dept_id, btn.dataset.division_id,
        id, btn.dataset.parent_jabatan_id
    );
});
</script>

<?= $this->endSection() ?>
