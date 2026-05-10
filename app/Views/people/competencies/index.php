<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<style>
@keyframes fadeUp { from { opacity:0; transform:translateY(16px); } to { opacity:1; transform:translateY(0); } }
.anim-fade-up { opacity:0; animation:fadeUp .45s cubic-bezier(.22,.68,0,1.15) forwards; }
.level-badge { display:inline-flex; align-items:center; justify-content:center;
    width:26px; height:26px; border-radius:50%; font-size:.7rem; font-weight:700; }
.level-pip { width:10px; height:10px; border-radius:50%; display:inline-block; margin:0 1px; }
</style>
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4 anim-fade-up" style="animation-delay:.05s">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-diagram-2-fill me-2"></i>Competency Framework</h4>
        <small class="text-muted">People Development — Master Kompetensi & Pemetaan Target</small>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= base_url('people/competencies/import') ?>" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-upload me-1"></i>Import CSV
        </a>
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="bi bi-plus-lg me-1"></i> Tambah Kompetensi
        </button>
    </div>
</div>

<?php $jabatanAssignedCount = $jabatanAssignedCount ?? 0; ?>
<!-- Filter bar — above tabs -->
<div class="card mb-3 anim-fade-up" style="animation-delay:.08s">
<div class="card-body py-2">
<form method="GET" action="<?= base_url('people/competencies') ?>"
      class="d-flex flex-wrap align-items-center gap-2" id="filterForm">
    <i class="bi bi-building text-muted"></i>
    <select name="dept_id" class="form-select form-select-sm" style="max-width:200px"
            onchange="this.form.submit()">
        <option value="">— Pilih Departemen —</option>
        <?php foreach ($departments as $d): ?>
        <option value="<?= $d['id'] ?>" <?= $deptId == $d['id'] ? 'selected' : '' ?>>
            <?= esc($d['name']) ?>
        </option>
        <?php endforeach; ?>
    </select>

    <?php if ($deptId && ! empty($jabatans)): ?>
    <i class="bi bi-person-badge text-muted ms-1"></i>
    <select name="jabatan" class="form-select form-select-sm" style="max-width:220px"
            onchange="this.form.submit()">
        <option value="">Default Dept</option>
        <?php foreach ($jabatans as $j): ?>
        <option value="<?= esc($j['nama']) ?>" <?= $jabatan === $j['nama'] ? 'selected' : '' ?>>
            <?= esc($j['nama']) ?><?php if (in_array($j['nama'], $overrides)): ?> ★<?php endif; ?>
        </option>
        <?php endforeach; ?>
    </select>
    <?php if (! empty($overrides)): ?>
    <span class="text-muted small fst-italic">★ ada override</span>
    <?php endif; ?>
    <?php elseif ($deptId): ?>
    <span class="text-muted small">Belum ada master jabatan</span>
    <?php endif; ?>

    <?php if ($deptId): ?>
    <div class="d-flex gap-2 ms-auto">
        <?php if ($selectedJabatanId): ?>
        <a href="<?= base_url('people/competencies/jabatan/' . $selectedJabatanId . '/assign') ?>"
           class="btn btn-sm btn-outline-primary">
            <i class="bi bi-person-badge me-1"></i>Kompetensi Jabatan
            <?php if ($jabatanAssignedCount): ?>
            <span class="badge bg-primary ms-1"><?= $jabatanAssignedCount ?></span>
            <?php endif; ?>
        </a>
        <?php endif; ?>
        <a href="<?= base_url('people/competencies/dept/' . $deptId . '/assign') ?>"
           class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-ui-checks me-1"></i>Kelola Kompetensi
            <?php if (! empty($assignedIds)): ?>
            <span class="badge bg-secondary ms-1"><?= count($assignedIds) ?></span>
            <?php endif; ?>
        </a>
    </div>
    <?php endif; ?>
</form>
</div>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-4 anim-fade-up" style="animation-delay:.1s" id="compTabs">
    <li class="nav-item">
        <a class="nav-link <?= ! $deptId ? 'active' : '' ?>" href="<?= base_url('people/competencies') ?>">
            <i class="bi bi-list-ul me-1"></i> Master Kompetensi
            <span class="badge bg-secondary-subtle text-secondary ms-1"><?= count($all) ?></span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $deptId ? 'active' : '' ?>" href="#" id="tabPemetaan" data-bs-toggle="tab" data-bs-target="#pemetaan">
            <i class="bi bi-grid-3x3 me-1"></i> Pemetaan Target
        </a>
    </li>
</ul>

<div class="tab-content">

<!-- Tab: Master Kompetensi -->
<div class="tab-pane fade <?= ! $deptId ? 'show active' : '' ?>" id="master">

<?php if (empty($all)): ?>
<div class="card"><div class="card-body text-center py-5 text-muted">
    <i class="bi bi-diagram-2 display-4 d-block mb-2 opacity-25"></i>
    <p class="mb-0">Belum ada kompetensi. Tambahkan kompetensi terlebih dahulu.</p>
</div></div>
<?php else: ?>

<?php foreach ($groupedByCluster as $group): ?>
<div class="card mb-3 anim-fade-up" style="animation-delay:.15s">
<div class="card-header d-flex align-items-center gap-2">
    <i class="bi bi-collection-fill text-primary"></i>
    <span class="fw-semibold"><?= esc($group['cluster_nama']) ?></span>
    <?php if ($group['cluster_deskripsi']): ?>
    <span class="text-muted small">— <?= esc($group['cluster_deskripsi']) ?></span>
    <?php endif; ?>
    <span class="badge bg-secondary ms-auto"><?= count($group['comps']) ?></span>
</div>
<div class="card-body p-0">
<div class="table-responsive">
<table class="table table-sm align-middle mb-0">
<thead class="table-light">
<tr>
    <th class="ps-3" style="width:28%">Nama</th>
    <th style="width:60px" class="text-center">Tipe</th>
    <th>Deskripsi</th>
    <th class="text-center" style="width:130px">Level</th>
    <th style="width:80px"></th>
</tr>
</thead>
<tbody>
<?php foreach ($group['comps'] as $c): ?>
<tr>
    <td class="ps-3 fw-semibold small"><?= esc($c['nama']) ?></td>
    <td class="text-center">
        <span class="badge <?= $c['kategori'] === 'hard' ? 'bg-primary' : 'bg-success' ?>" style="font-size:.65rem">
            <?= ucfirst($c['kategori']) ?>
        </span>
    </td>
    <td class="small text-muted"><?= $c['deskripsi'] ? esc($c['deskripsi']) : '—' ?></td>
    <td class="text-center">
        <div class="d-flex gap-1 justify-content-center">
        <?php for ($l = 1; $l <= 5; $l++): ?>
        <?php $desc = $c['level_'.$l] ?? null; ?>
        <span class="level-badge <?= $desc ? 'bg-primary text-white' : 'bg-secondary-subtle text-muted' ?>"
              title="<?= $desc ? 'Level '.$l.': '.esc($desc) : 'Level '.$l.' — belum didefinisikan' ?>">
            <?= $l ?>
        </span>
        <?php endfor; ?>
        </div>
    </td>
    <td class="text-end pe-2">
        <a href="<?= base_url('people/competencies/'.$c['id'].'/questions') ?>"
           class="btn btn-sm btn-outline-primary me-1" title="Kelola Pertanyaan">
            <i class="bi bi-list-check"></i>
        </a>
        <button class="btn btn-sm btn-outline-secondary edit-btn"
            data-id="<?= $c['id'] ?>"
            data-nama="<?= esc($c['nama']) ?>"
            data-kategori="<?= $c['kategori'] ?>"
            data-clusterid="<?= $c['cluster_id'] ?? '' ?>"
            data-deskripsi="<?= esc($c['deskripsi'] ?? '') ?>"
            data-level1="<?= esc($c['level_1'] ?? '') ?>"
            data-level2="<?= esc($c['level_2'] ?? '') ?>"
            data-level3="<?= esc($c['level_3'] ?? '') ?>"
            data-level4="<?= esc($c['level_4'] ?? '') ?>"
            data-level5="<?= esc($c['level_5'] ?? '') ?>">
            <i class="bi bi-pencil"></i>
        </button>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>

<!-- Tab: Pemetaan Target -->
<div class="tab-pane fade <?= $deptId ? 'show active' : '' ?>" id="pemetaan">

<?php if ($deptId && ! empty($assignedIds)): ?>

<?php if ($jabatan): ?>
<div class="alert alert-info border-0 py-2 mb-3 small d-flex align-items-center gap-2">
    <i class="bi bi-person-check-fill fs-5"></i>
    <div class="flex-grow-1">
        Mode <strong>override jabatan</strong>: target berlaku khusus untuk <strong><?= esc($jabatan) ?></strong>.
        Kompetensi yang di-× akan mengikuti target default departemen.
    </div>
    <?php if ($selectedJabatanId): ?>
    <a href="<?= base_url('people/competencies/jabatan/' . $selectedJabatanId . '/assign') ?>"
       class="btn btn-sm btn-primary flex-shrink-0">
        <i class="bi bi-ui-checks me-1"></i>Atur Kompetensi Jabatan
        <?php if ($jabatanAssignedCount): ?>
        <span class="badge bg-white text-primary ms-1"><?= $jabatanAssignedCount ?></span>
        <?php endif; ?>
    </a>
    <?php endif; ?>
</div>
<?php endif; ?>

<form method="POST" action="<?= base_url('people/competencies/targets/save') ?>">
<?= csrf_field() ?>
<input type="hidden" name="dept_id" value="<?= $deptId ?>">
<input type="hidden" name="jabatan" value="<?= esc($jabatan) ?>">

<?php foreach ($groupedTargetCluster as $group): ?>
<div class="card mb-3">
<div class="card-header d-flex align-items-center gap-2 py-2">
    <i class="bi bi-collection-fill text-primary"></i>
    <span class="fw-semibold"><?= esc($group['cluster_nama']) ?></span>
    <?php if ($group['cluster_deskripsi']): ?>
    <span class="text-muted small d-none d-md-inline">— <?= esc($group['cluster_deskripsi']) ?></span>
    <?php endif; ?>
    <span class="badge bg-secondary ms-auto"><?= count($group['comps']) ?></span>
</div>
<div class="card-body p-0">
<table class="table table-sm align-middle mb-0">
<thead class="table-light">
<tr>
    <th class="ps-3">Kompetensi</th>
    <th class="text-center" style="width:60px">Tipe</th>
    <?php if ($jabatan): ?>
    <th class="text-center" style="width:90px">Default Dept</th>
    <?php endif; ?>
    <th class="text-center" style="width:230px">
        <?= $jabatan ? 'Target — ' . esc($jabatan) : 'Target Level' ?>
    </th>
</tr>
</thead>
<tbody>
<?php foreach ($group['comps'] as $c):
    $tgt     = $targetMap[$c['id']] ?? 0;
    $deptTgt = $deptTargetMap[$c['id']] ?? 0;
?>
<tr>
    <td class="ps-3">
        <div class="fw-semibold small"><?= esc($c['nama']) ?></div>
        <?php if (! empty($c['deskripsi'])): ?>
        <div class="text-muted" style="font-size:.7rem"><?= esc($c['deskripsi']) ?></div>
        <?php endif; ?>
    </td>
    <td class="text-center">
        <span class="badge <?= $c['kategori'] === 'hard' ? 'bg-primary' : 'bg-success' ?>" style="font-size:.62rem">
            <?= ucfirst($c['kategori']) ?>
        </span>
    </td>
    <?php if ($jabatan): ?>
    <td class="text-center">
        <?php if ($deptTgt): ?>
        <span class="badge bg-secondary-subtle text-secondary border fw-semibold"><?= $deptTgt ?></span>
        <?php else: ?>
        <span class="text-muted">—</span>
        <?php endif; ?>
    </td>
    <?php endif; ?>
    <td class="text-center py-2">
        <div class="d-flex justify-content-center gap-1">
        <?php for ($l = 1; $l <= 5; $l++): ?>
        <input type="radio" class="btn-check" name="levels[<?= $c['id'] ?>]"
               id="lvl_<?= $c['id'] ?>_<?= $l ?>" value="<?= $l ?>" <?= $tgt == $l ? 'checked' : '' ?>>
        <label class="btn btn-sm <?= $tgt == $l ? 'btn-primary' : 'btn-outline-secondary' ?>"
               for="lvl_<?= $c['id'] ?>_<?= $l ?>"
               style="width:34px;padding:3px 0"
               title="Level <?= $l ?><?= $c['level_'.$l] ? ': '.esc($c['level_'.$l]) : '' ?>"><?= $l ?></label>
        <?php endfor; ?>
        <input type="radio" class="btn-check" name="levels[<?= $c['id'] ?>]"
               id="lvl_<?= $c['id'] ?>_0" value="0" <?= $tgt == 0 ? 'checked' : '' ?>>
        <label class="btn btn-sm <?= $tgt == 0 ? 'btn-danger' : 'btn-outline-danger' ?>"
               for="lvl_<?= $c['id'] ?>_0"
               style="width:28px;padding:3px 0"
               title="<?= $jabatan ? 'Ikuti default dept' : 'Tidak dipetakan' ?>">×</label>
        </div>
        <?php if ($tgt > 0 && ! empty($c['level_'.$tgt])): ?>
        <div class="text-primary mt-1" style="font-size:.65rem;opacity:.75"><?= esc($c['level_'.$tgt]) ?></div>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
<?php endforeach; ?>

<div class="d-flex justify-content-end mt-1 mb-4">
    <button type="submit" class="btn btn-primary px-4">
        <i class="bi bi-check-lg me-1"></i>Simpan Target
        <?= $jabatan ? '— ' . esc($jabatan) : '— Default Dept' ?>
    </button>
</div>
</form>

<?php elseif ($deptId && empty($all)): ?>
<div class="alert alert-warning">Belum ada kompetensi. Tambahkan kompetensi terlebih dahulu di tab Master Kompetensi.</div>
<?php elseif ($deptId): ?>
<div class="card border-dashed"><div class="card-body text-center py-5">
    <i class="bi bi-ui-checks display-4 d-block mb-3 text-primary opacity-50"></i>
    <p class="fw-semibold mb-1">Belum ada kompetensi yang di-assign ke departemen ini.</p>
    <p class="text-muted small mb-3">Pilih kompetensi yang relevan terlebih dahulu sebelum mengatur target level.</p>
    <a href="<?= base_url('people/competencies/dept/' . $deptId . '/assign') ?>" class="btn btn-primary btn-sm">
        <i class="bi bi-ui-checks me-1"></i>Assign Kompetensi ke Departemen Ini
    </a>
</div></div>
<?php else: ?>
<div class="card"><div class="card-body text-center py-5 text-muted">
    <i class="bi bi-building display-4 d-block mb-2 opacity-25"></i>
    <p class="mb-0 small">Pilih departemen untuk mengatur target kompetensi.</p>
</div></div>
<?php endif; ?>
</div>

</div><!-- /tab-content -->

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
<form method="POST" action="<?= base_url('people/competencies/add') ?>">
<?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title fw-semibold">Tambah Kompetensi</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="row g-3">
        <div class="col-md-8">
            <label class="form-label small fw-semibold">Nama Kompetensi <span class="text-danger">*</span></label>
            <input type="text" name="nama" class="form-control" required placeholder="Contoh: Public Speaking, Excel, Negotiation...">
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-semibold">Kategori</label>
            <select name="kategori" class="form-select">
                <option value="hard">Hard Skill</option>
                <option value="soft">Soft Skill</option>
            </select>
        </div>
        <div class="col-12">
            <label class="form-label small fw-semibold">Cluster</label>
            <select name="cluster_id" class="form-select">
                <option value="">— Tanpa Cluster —</option>
                <?php foreach ($clusters as $cl): ?>
                <option value="<?= $cl['id'] ?>"><?= esc($cl['nama']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12">
            <label class="form-label small fw-semibold">Deskripsi Umum</label>
            <textarea name="deskripsi" class="form-control" rows="2" placeholder="Opsional — jelaskan kompetensi ini secara umum"></textarea>
        </div>
    </div>
    <hr class="my-3">
    <p class="small fw-semibold mb-2">Deskripsi per Level <span class="text-muted fw-normal">(opsional)</span></p>
    <?php
    $lvlColors = [1=>'#94a3b8',2=>'#38bdf8',3=>'#6366f1',4=>'#f59e0b',5=>'#10b981'];
    $lvlLabels = [1=>'Pemula',2=>'Dasar',3=>'Menengah',4=>'Mahir',5=>'Ahli'];
    ?>
    <div class="d-flex flex-column gap-2">
        <?php foreach ($lvlLabels as $l => $lbl): ?>
        <div class="d-flex align-items-center gap-2">
            <span class="d-flex align-items-center justify-content-center rounded-circle fw-bold flex-shrink-0 text-white"
                  style="width:28px;height:28px;font-size:.72rem;background:<?= $lvlColors[$l] ?>">
                <?= $l ?>
            </span>
            <span class="small fw-semibold text-nowrap" style="min-width:68px;color:<?= $lvlColors[$l] ?>"><?= $lbl ?></span>
            <input type="text" name="level_<?= $l ?>" class="form-control form-control-sm"
                   placeholder="Deskripsi kemampuan level <?= $l ?>...">
        </div>
        <?php endforeach; ?>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
    <button type="submit" class="btn btn-primary">Tambah</button>
</div>
</form>
</div></div></div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
<form id="editForm" method="POST">
<?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title fw-semibold">Edit Kompetensi</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="row g-3">
        <div class="col-md-8">
            <label class="form-label small fw-semibold">Nama Kompetensi <span class="text-danger">*</span></label>
            <input type="text" name="nama" id="eNama" class="form-control" required>
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-semibold">Kategori</label>
            <select name="kategori" id="eKategori" class="form-select">
                <option value="hard">Hard Skill</option>
                <option value="soft">Soft Skill</option>
            </select>
        </div>
        <div class="col-12">
            <label class="form-label small fw-semibold">Cluster</label>
            <select name="cluster_id" id="eCluster" class="form-select">
                <option value="">— Tanpa Cluster —</option>
                <?php foreach ($clusters as $cl): ?>
                <option value="<?= $cl['id'] ?>"><?= esc($cl['nama']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12">
            <label class="form-label small fw-semibold">Deskripsi Umum</label>
            <textarea name="deskripsi" id="eDesk" class="form-control" rows="2"></textarea>
        </div>
    </div>
    <hr class="my-3">
    <p class="small fw-semibold mb-2">Deskripsi per Level</p>
    <div class="d-flex flex-column gap-2">
        <?php foreach ($lvlLabels as $l => $lbl): ?>
        <div class="d-flex align-items-center gap-2">
            <span class="d-flex align-items-center justify-content-center rounded-circle fw-bold flex-shrink-0 text-white"
                  style="width:28px;height:28px;font-size:.72rem;background:<?= $lvlColors[$l] ?>">
                <?= $l ?>
            </span>
            <span class="small fw-semibold text-nowrap" style="min-width:68px;color:<?= $lvlColors[$l] ?>"><?= $lbl ?></span>
            <input type="text" name="level_<?= $l ?>" id="eLevel<?= $l ?>" class="form-control form-control-sm"
                   placeholder="Deskripsi kemampuan level <?= $l ?>...">
        </div>
        <?php endforeach; ?>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-outline-danger me-auto" id="deleteBtn">
        <i class="bi bi-trash me-1"></i> Hapus
    </button>
    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
    <button type="submit" class="btn btn-primary">Simpan</button>
</div>
</form>
</div></div></div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
// Activate pemetaan tab if dept_id in URL
<?php if ($deptId): ?>
new bootstrap.Tab(document.getElementById('tabPemetaan')).show();
document.querySelector('#compTabs a:first-child').classList.remove('active');
<?php endif; ?>

// Edit modal
document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const d = this.dataset;
        document.getElementById('editForm').action = '<?= base_url('people/competencies/') ?>' + d.id + '/edit';
        document.getElementById('eNama').value       = d.nama;
        document.getElementById('eKategori').value   = d.kategori;
        document.getElementById('eCluster').value    = d.clusterid;
        document.getElementById('eDesk').value       = d.deskripsi;
        document.getElementById('eLevel1').value     = d.level1;
        document.getElementById('eLevel2').value     = d.level2;
        document.getElementById('eLevel3').value     = d.level3;
        document.getElementById('eLevel4').value     = d.level4;
        document.getElementById('eLevel5').value     = d.level5;
        document.getElementById('deleteBtn').onclick = () => {
            if (confirm('Hapus kompetensi ini? Semua target terkait juga akan dihapus.'))
                location.href = '<?= base_url('people/competencies/') ?>' + d.id + '/delete';
        };
        new bootstrap.Modal(document.getElementById('editModal')).show();
    });
});

// Level radio button label styling
document.querySelectorAll('input[type=radio]').forEach(r => {
    r.addEventListener('change', function() {
        const name = this.name;
        document.querySelectorAll(`input[name="${name}"]`).forEach(rb => {
            const lbl = document.querySelector(`label[for="${rb.id}"]`);
            if (!lbl) return;
            if (rb.value === '0') {
                lbl.className = 'btn btn-sm ' + (rb.checked ? 'btn-danger' : 'btn-outline-danger');
            } else {
                lbl.className = 'btn btn-sm ' + (rb.checked ? 'btn-primary' : 'btn-outline-secondary');
            }
        });
    });
});
</script>
<?= $this->endSection() ?>
