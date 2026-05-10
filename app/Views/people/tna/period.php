<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<style>
@keyframes fadeUp { from { opacity:0; transform:translateY(14px); } to { opacity:1; transform:none; } }
.anim-fade-up { animation: fadeUp .4s cubic-bezier(.22,.68,0,1.1) both; }
.assessor-row { font-size:.82rem; }
.badge-assessor { font-size:.68rem; }
</style>

<?php
$isClosed = $period['status'] === 'closed';
$typeLabels = ['self' => 'Self', 'atasan' => 'Atasan', 'rekan' => 'Rekan'];
?>

<!-- Header -->
<div class="d-flex align-items-center gap-2 mb-1 anim-fade-up" style="animation-delay:.05s">
    <a href="<?= base_url('people/tna') ?>" class="btn btn-sm btn-light"><i class="bi bi-arrow-left"></i></a>
    <div>
        <div class="text-muted small">TNA Assessment 360°</div>
        <h5 class="fw-bold mb-0"><?= esc($period['nama']) ?></h5>
    </div>
    <span class="badge <?= $isClosed ? 'bg-secondary' : 'bg-success' ?> ms-1"><?= $isClosed ? 'Closed' : 'Open' ?></span>
    <div class="ms-auto d-flex gap-2">
        <a href="<?= base_url('people/tna/periods/' . $period['id'] . '/toggle-close') ?>"
           class="btn btn-sm btn-<?= $isClosed ? 'success' : 'warning' ?>"
           onclick="return confirm('<?= $isClosed ? 'Buka kembali periode ini?' : 'Tutup periode ini?' ?>')">
            <i class="bi bi-<?= $isClosed ? 'unlock' : 'lock' ?> me-1"></i><?= $isClosed ? 'Buka Kembali' : 'Tutup Periode' ?>
        </a>
        <?php if (! $isClosed): ?>
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addEmpModal">
            <i class="bi bi-person-plus me-1"></i>Tambah Karyawan
        </button>
        <?php endif; ?>
    </div>
</div>

<?php if ($period['tanggal_mulai']): ?>
<div class="text-muted small mb-3 anim-fade-up" style="animation-delay:.08s">
    <i class="bi bi-calendar3 me-1"></i>
    <?= date('d M Y', strtotime($period['tanggal_mulai'])) ?> – <?= $period['tanggal_selesai'] ? date('d M Y', strtotime($period['tanggal_selesai'])) : '…' ?>
</div>
<?php endif; ?>

<?php if ($isClosed): ?>
<div class="alert alert-secondary py-2 anim-fade-up" style="animation-delay:.1s">
    <i class="bi bi-lock me-2"></i>Periode ditutup — tidak dapat menambah/mengubah data assessment.
</div>
<?php endif; ?>

<?php if (empty($employees)): ?>
<div class="text-center py-5 anim-fade-up" style="animation-delay:.15s">
    <i class="bi bi-people" style="font-size:2.5rem;opacity:.25"></i>
    <p class="text-muted mt-3">Belum ada karyawan dalam periode ini.</p>
    <?php if (! $isClosed): ?>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addEmpModal">
        <i class="bi bi-person-plus me-1"></i>Tambah Karyawan
    </button>
    <?php endif; ?>
</div>
<?php else: ?>

<!-- Employee table -->
<div class="anim-fade-up" style="animation-delay:.12s">
<?php foreach ($employees as $emp):
    $empAssessors = $assessorMap[$emp['employee_id']] ?? [];
    $selfAss = null;
    $nonSelf = [];
    foreach ($empAssessors as $a) {
        if ($a['assessor_type'] === 'self') $selfAss = $a;
        else $nonSelf[] = $a;
    }
    $allSubmitted = (int)$emp['submitted_forms'] > 0 && (int)$emp['submitted_forms'] === (int)$emp['total_forms'];
    $hasResult    = $selfAss && $selfAss['status'] === 'submitted';
?>
<div class="card mb-3" style="border-radius:.75rem">
    <div class="card-body">
        <!-- Employee header row -->
        <div class="d-flex align-items-start justify-content-between flex-wrap gap-2">
            <div>
                <div class="fw-semibold"><?= esc($emp['emp_nama']) ?></div>
                <div class="text-muted small"><?= esc($emp['jabatan']) ?> · <?= esc($emp['dept_name']) ?></div>
            </div>
            <div class="d-flex gap-2 flex-wrap align-items-center">
                <span class="badge bg-<?= (int)$emp['submitted_forms'] === (int)$emp['total_forms'] && $emp['total_forms'] > 0 ? 'success' : 'warning text-dark' ?> badge-assessor">
                    <?= $emp['submitted_forms'] ?>/<?= $emp['total_forms'] ?> submitted
                </span>
                <?php if ($hasResult): ?>
                <a href="<?= base_url('people/tna/period/' . $period['id'] . '/result/' . $emp['employee_id']) ?>"
                   class="btn btn-xs btn-sm btn-outline-info py-0 px-2" style="font-size:.75rem">
                    <i class="bi bi-bar-chart-line me-1"></i>Lihat Hasil
                </a>
                <?php endif; ?>
                <?php if (! $isClosed): ?>
                <button class="btn btn-xs btn-sm btn-outline-secondary py-0 px-2" style="font-size:.75rem"
                        data-bs-toggle="modal" data-bs-target="#addAssessorModal"
                        data-period="<?= $period['id'] ?>"
                        data-emp="<?= $emp['employee_id'] ?>"
                        data-nama="<?= esc($emp['emp_nama']) ?>"
                        data-dept="<?= $emp['dept_id'] ?>"
                        data-grade="<?= esc($emp['jabatan_grade'] ?? '') ?>"
                        data-atasan-nama="<?= esc($emp['atasan_nama'] ?? '') ?>"
                        data-atasan-id="<?= $emp['atasan_id'] ?? '' ?>">
                    <i class="bi bi-person-plus me-1"></i>Tambah Assessor
                </button>
                <a href="<?= base_url('people/tna/period/' . $period['id'] . '/employees/' . $emp['employee_id'] . '/remove') ?>"
                   class="btn btn-xs btn-sm btn-outline-danger py-0 px-2" style="font-size:.75rem"
                   onclick="return confirm('Hapus <?= esc($emp['emp_nama']) ?> beserta semua data assessment-nya?')">
                    <i class="bi bi-trash"></i>
                </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Assessor list -->
        <div class="mt-3 d-flex flex-wrap gap-2">
            <?php if ($selfAss): ?>
            <div class="d-flex align-items-center gap-2 border rounded px-2 py-1" style="font-size:.8rem;min-width:180px">
                <span class="fw-semibold text-muted" style="width:50px">Self</span>
                <span class="badge <?= $selfAss['status'] === 'submitted' ? 'bg-success' : 'bg-warning text-dark' ?> badge-assessor">
                    <?= $selfAss['status'] === 'submitted' ? 'Submitted' : 'Draft' ?>
                </span>
                <?php if ($selfAss['status'] === 'submitted'): ?>
                <span class="ms-auto text-muted" style="font-size:.72rem"><?= $selfAss['submitted_at'] ? date('d/m', strtotime($selfAss['submitted_at'])) : '' ?></span>
                <?php elseif (! $isClosed): ?>
                <?php if ($selfAss['fill_token']): ?>
                <button type="button" class="btn btn-xs btn-sm btn-link p-0 ms-auto copy-link-btn" style="font-size:.75rem"
                        data-url="<?= base_url('tna/fill/' . $selfAss['fill_token']) ?>" title="Salin link penilaian">
                    <i class="bi bi-link-45deg"></i> Link
                </button>
                <?php else: ?>
                <a href="<?= base_url('people/tna/assessments/' . $selfAss['id'] . '/regenerate-token') ?>"
                   class="btn btn-xs btn-sm btn-link p-0 ms-auto" style="font-size:.75rem" title="Generate link">
                    <i class="bi bi-link-45deg"></i> Gen Link
                </a>
                <?php endif; ?>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php foreach ($nonSelf as $a): ?>
            <div class="d-flex align-items-center gap-2 border rounded px-2 py-1" style="font-size:.8rem;min-width:220px">
                <span class="fw-semibold text-muted" style="min-width:55px"><?= ucfirst($a['assessor_type']) ?></span>
                <span class="text-truncate" style="max-width:100px" title="<?= esc($a['assessor_name']) ?>"><?= esc($a['assessor_name']) ?></span>
                <span class="badge <?= $a['status'] === 'submitted' ? 'bg-success' : 'bg-warning text-dark' ?> badge-assessor">
                    <?= $a['status'] === 'submitted' ? 'Done' : 'Draft' ?>
                </span>
                <?php if ($a['status'] !== 'submitted' && ! $isClosed): ?>
                <?php if ($a['fill_token']): ?>
                <button type="button" class="btn btn-xs btn-sm btn-link p-0 ms-auto copy-link-btn" style="font-size:.75rem"
                        data-url="<?= base_url('tna/fill/' . $a['fill_token']) ?>" title="Salin link penilaian">
                    <i class="bi bi-link-45deg"></i>
                </button>
                <?php else: ?>
                <a href="<?= base_url('people/tna/assessments/' . $a['id'] . '/regenerate-token') ?>"
                   class="btn btn-xs btn-sm btn-link p-0 ms-auto" style="font-size:.75rem">
                    <i class="bi bi-link-45deg"></i>
                </a>
                <?php endif; ?>
                <?php endif; ?>
                <?php if (! $isClosed): ?>
                <a href="<?= base_url('people/tna/period/' . $period['id'] . '/assessors/' . $a['id'] . '/remove') ?>"
                   class="text-danger ms-1" style="font-size:.8rem" onclick="return confirm('Hapus assessor ini?')" title="Hapus">
                   <i class="bi bi-x-circle"></i>
                </a>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Add Employee Modal -->
<div class="modal fade" id="addEmpModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="<?= base_url('people/tna/period/' . $period['id'] . '/employees/add') ?>">
            <?= csrf_field() ?>
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Karyawan ke Periode</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php if (empty($available)): ?>
                    <p class="text-muted">Semua karyawan aktif sudah masuk dalam periode ini.</p>
                    <?php else: ?>
                    <label class="form-label">Karyawan <span class="text-danger">*</span></label>
                    <select name="employee_id" class="form-select" required>
                        <option value="">— Pilih Karyawan —</option>
                        <?php foreach ($available as $e): ?>
                        <option value="<?= $e['id'] ?>"><?= esc($e['nama']) ?> — <?= esc($e['jabatan']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <?php if (! empty($available)): ?>
                    <button type="submit" class="btn btn-primary btn-sm">Tambah</button>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Add Assessor Modal -->
<div class="modal fade" id="addAssessorModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" id="addAssessorForm" action="">
            <?= csrf_field() ?>
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">Tambah Assessor — <span id="assEmpNama"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">

                    <!-- Tipe -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Tipe Assessor <span class="text-danger">*</span></label>
                        <div class="d-flex gap-2">
                            <input type="radio" class="btn-check" name="assessor_type" id="typeAtasan" value="atasan">
                            <label class="btn btn-sm btn-outline-primary" for="typeAtasan">
                                <i class="bi bi-person-up me-1"></i>Atasan
                            </label>
                            <input type="radio" class="btn-check" name="assessor_type" id="typeRekan" value="rekan">
                            <label class="btn btn-sm btn-outline-success" for="typeRekan">
                                <i class="bi bi-people me-1"></i>Rekan Kerja
                            </label>
                        </div>
                    </div>

                    <!-- Panel Atasan -->
                    <div id="panelAtasan" class="d-none">
                        <p class="small text-muted mb-2">Rantai atasan hingga level G1 — centang yang akan jadi assessor:</p>
                        <div id="atasanChainList" class="d-flex flex-column gap-2"></div>
                        <div id="atasanNotFound" class="d-none">
                            <div class="alert alert-warning py-2 small mb-0">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                Karyawan ini belum memiliki atasan di master data. Update di menu <strong>Karyawan</strong>.
                            </div>
                        </div>
                    </div>

                    <!-- Panel Rekan -->
                    <div id="panelRekan" class="d-none">
                        <div class="d-flex gap-1 mb-2">
                            <button type="button" class="btn btn-sm btn-primary rekan-tab-btn" data-tab="dept">
                                <i class="bi bi-people me-1"></i>Se-Departemen
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary rekan-tab-btn" data-tab="grade">
                                <i class="bi bi-diagram-2 me-1"></i>Se-Level (Beda Dept)
                            </button>
                        </div>
                        <input type="text" id="rekanSearch" class="form-control form-control-sm mb-2"
                               placeholder="Ketik nama untuk mencari...">
                        <div id="rekanList" class="border rounded" style="max-height:220px;overflow-y:auto"></div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm" id="btnAddAssessor" disabled>
                        Tambah <span id="btnAssessorCount"></span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
const ALL_EMPLOYEES = <?= $empPickerJson ?? '[]' ?>;

(function() {
    const modal     = document.getElementById('addAssessorModal');
    const form      = document.getElementById('addAssessorForm');
    const btnSubmit = document.getElementById('btnAddAssessor');
    const btnCount  = document.getElementById('btnAssessorCount');
    const empMap    = {};
    ALL_EMPLOYEES.forEach(e => empMap[e.id] = e);

    let currentEmpId = 0, currentDept = 0, currentGrade = '', currentRekanTab = 'dept';

    function updateSubmitBtn() {
        const checked = form.querySelectorAll('input[name="assessor_names[]"]:checked');
        const n = checked.length;
        btnSubmit.disabled = n === 0;
        btnCount.textContent = n > 0 ? `(${n})` : '';
    }

    function resetModal() {
        document.getElementById('panelAtasan').classList.add('d-none');
        document.getElementById('panelRekan').classList.add('d-none');
        document.getElementById('atasanNotFound').classList.add('d-none');
        document.getElementById('atasanChainList').innerHTML = '';
        document.getElementById('rekanSearch').value = '';
        document.getElementById('rekanList').innerHTML = '';
        // (rekan count removed)
        btnSubmit.disabled = true;
        btnCount.textContent = '';
        document.querySelectorAll('input[name="assessor_type"]').forEach(r => r.checked = false);
        // Remove any leftover name checkboxes
        form.querySelectorAll('input[name="assessor_names[]"]').forEach(el => el.remove());
    }

    modal.addEventListener('show.bs.modal', function(e) {
        const btn = e.relatedTarget;
        resetModal();
        currentEmpId  = parseInt(btn.dataset.emp)  || 0;
        currentDept   = parseInt(btn.dataset.dept) || 0;
        currentGrade  = btn.dataset.grade || '';
        currentRekanTab = 'dept';
        document.getElementById('assEmpNama').textContent = btn.dataset.nama;
        form.action = '<?= base_url('people/tna/period/') ?>' + btn.dataset.period
                    + '/employees/' + btn.dataset.emp + '/assessors/add';
    });

    // Tipe radio change
    document.querySelectorAll('input[name="assessor_type"]').forEach(r => {
        r.addEventListener('change', function() {
            // Clear checkboxes but keep radios
            form.querySelectorAll('input[name="assessor_names[]"]').forEach(el => el.remove());
            document.getElementById('panelAtasan').classList.add('d-none');
            document.getElementById('panelRekan').classList.add('d-none');
            document.getElementById('rekanSearch').value = '';
            btnSubmit.disabled = true;
            btnCount.textContent = '';

            if (this.value === 'atasan') {
                document.getElementById('panelAtasan').classList.remove('d-none');
                renderAtasanChain();
            } else {
                document.getElementById('panelRekan').classList.remove('d-none');
                renderRekanList('');
            }
        });
    });

    // Build supervisor chain up to G1.
    // Traverses through inactive/vacant positions (skip them from results but follow their atasan_id).
    function getAtasanChain(empId) {
        const chain = [];
        const seen  = new Set([empId]);
        let curr    = empMap[empId];
        if (!curr) return chain;

        let nextId = curr.atasan_id;
        while (nextId && !seen.has(nextId)) {
            const atasan = empMap[nextId];
            if (!atasan) break;
            seen.add(atasan.id);
            // Only include active supervisors in the assessor list
            if (atasan.status === 'aktif') {
                chain.push(atasan);
            }
            if (atasan.jabatan_grade === 'G1') break;
            nextId = atasan.atasan_id;
        }
        return chain;
    }

    function renderAtasanChain() {
        const chain = getAtasanChain(currentEmpId);
        const container = document.getElementById('atasanChainList');
        container.innerHTML = '';
        form.querySelectorAll('input[name="assessor_names[]"]').forEach(el => el.remove());

        if (chain.length === 0) {
            document.getElementById('atasanNotFound').classList.remove('d-none');
            return;
        }
        document.getElementById('atasanNotFound').classList.add('d-none');

        chain.forEach((a, i) => {
            const uid = 'atasan_cb_' + a.id;
            const grade = a.jabatan_grade ? `<span class="badge bg-primary-subtle text-primary ms-1" style="font-size:.6rem">${a.jabatan_grade}</span>` : '';
            const levelLabel = i === 0 ? 'Atasan langsung' : `Level ${i + 1}`;

            // Hidden checkbox appended to form
            const cb = document.createElement('input');
            cb.type = 'checkbox'; cb.name = 'assessor_names[]';
            cb.value = a.nama; cb.id = uid; cb.classList.add('d-none');
            cb.addEventListener('change', updateSubmitBtn);
            form.appendChild(cb);

            const div = document.createElement('div');
            div.className = 'assessor-chain-row d-flex align-items-center gap-2 border rounded px-3 py-2';
            div.style.cursor = 'pointer';
            div.innerHTML = `
                <div class="rounded-circle bg-primary-subtle d-flex align-items-center justify-content-center fw-bold flex-shrink-0 text-primary"
                     style="width:32px;height:32px;font-size:.75rem">${a.nama.charAt(0).toUpperCase()}</div>
                <div class="flex-grow-1">
                    <div class="small fw-semibold">${a.nama} ${grade}</div>
                    <div style="font-size:.7rem" class="text-muted">${a.jabatan} · <em>${levelLabel}</em></div>
                </div>
                <i class="bi bi-square text-muted check-icon fs-5"></i>`;
            div.addEventListener('click', () => {
                cb.checked = !cb.checked;
                div.querySelector('.check-icon').className = cb.checked
                    ? 'bi bi-check-square-fill text-primary check-icon fs-5'
                    : 'bi bi-square text-muted check-icon fs-5';
                div.classList.toggle('border-primary', cb.checked);
                updateSubmitBtn();
            });
            container.appendChild(div);
        });
    }

    // Rekan tab switching
    document.querySelectorAll('.rekan-tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            currentRekanTab = this.dataset.tab;
            document.querySelectorAll('.rekan-tab-btn').forEach(b => {
                b.className = b.dataset.tab === currentRekanTab
                    ? 'btn btn-sm btn-primary rekan-tab-btn'
                    : 'btn btn-sm btn-outline-secondary rekan-tab-btn';
            });
            document.getElementById('rekanSearch').value = '';
            renderRekanList('');
        });
    });

    // Rekan list with multi-select checkboxes
    document.getElementById('rekanSearch').addEventListener('input', function() {
        renderRekanList(this.value);
    });

    function renderRekanList(query) {
        const list = document.getElementById('rekanList');
        const q    = query.toLowerCase();
        const filtered = ALL_EMPLOYEES.filter(e => {
            if (e.id === currentEmpId) return false;
            if (e.status !== 'aktif') return false;
            const nameMatch = q === '' || e.nama.toLowerCase().includes(q);
            if (!nameMatch) return false;
            if (currentRekanTab === 'dept') return e.dept_id === currentDept;
            return currentGrade && e.jabatan_grade === currentGrade && e.dept_id !== currentDept;
        });

        if (filtered.length === 0) {
            list.innerHTML = '<div class="text-muted small p-3 text-center">Tidak ada karyawan ditemukan</div>';
            return;
        }

        // Get currently checked names to preserve state across search
        const checkedNames = new Set(
            [...form.querySelectorAll('input[name="assessor_names[]"]:checked')].map(c => c.value)
        );
        // Remove old rekan checkboxes
        form.querySelectorAll('.rekan-cb').forEach(el => el.remove());

        list.innerHTML = filtered.map(e => {
            const uid = 'rekan_cb_' + e.id;
            const checked = checkedNames.has(e.nama);
            return `<label class="d-flex align-items-center gap-2 px-3 py-2 border-bottom rekan-row"
                          style="cursor:pointer;${checked ? 'background:var(--bs-primary-bg-subtle)' : ''}" for="${uid}">
                <div class="rounded-circle bg-secondary-subtle d-flex align-items-center justify-content-center fw-bold flex-shrink-0"
                     style="width:28px;height:28px;font-size:.72rem">${e.nama.charAt(0).toUpperCase()}</div>
                <div class="flex-grow-1">
                    <div class="small fw-semibold">${e.nama}</div>
                    <div style="font-size:.7rem" class="text-muted">${e.jabatan}${currentRekanTab === 'grade' ? ' · <span class="text-body">' + (e.dept_name || '') + '</span>' : ''}</div>
                </div>
                <i class="bi ${checked ? 'bi-check-square-fill text-primary' : 'bi-square text-muted'} fs-5 check-icon"></i>
                <input type="checkbox" name="assessor_names[]" id="${uid}" value="${e.nama.replace(/"/g,'&quot;')}"
                       class="d-none rekan-cb-vis" ${checked ? 'checked' : ''}>
            </label>`;
        }).join('');

        // Move hidden checkboxes to form & wire events
        list.querySelectorAll('.rekan-cb-vis').forEach(cb => {
            const clone = cb.cloneNode();
            clone.classList.remove('rekan-cb-vis');
            clone.classList.add('rekan-cb');
            clone.addEventListener('change', () => {
                const lbl = list.querySelector(`label[for="${cb.id}"]`);
                const icon = lbl?.querySelector('.check-icon');
                if (icon) icon.className = `bi ${clone.checked ? 'bi-check-square-fill text-primary' : 'bi-square text-muted'} fs-5 check-icon`;
                if (lbl) lbl.style.background = clone.checked ? 'var(--bs-primary-bg-subtle)' : '';
                updateSubmitBtn();
            });
            list.querySelector(`label[for="${cb.id}"]`).addEventListener('click', () => {
                setTimeout(() => clone.dispatchEvent(new Event('change')), 0);
            });
            form.appendChild(clone);
            cb.remove();
        });

        updateSubmitBtn();
    }
})();

// Copy link buttons
document.querySelectorAll('.copy-link-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const url = this.dataset.url;
        navigator.clipboard.writeText(url).then(() => {
            const orig = this.innerHTML;
            this.innerHTML = '<i class="bi bi-check-lg text-success"></i>';
            setTimeout(() => this.innerHTML = orig, 1800);
        }).catch(() => {
            prompt('Salin link berikut:', url);
        });
    });
});
</script>
<?= $this->endSection() ?>
