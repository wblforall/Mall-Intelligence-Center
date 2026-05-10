<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="<?= base_url('people/competencies?dept_id=' . $dept['id']) ?>" class="btn btn-sm btn-light">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div>
        <div class="text-muted small">Pemetaan Target → <?= esc($dept['name']) ?></div>
        <h5 class="fw-bold mb-0">Assign Kompetensi</h5>
    </div>
    <span class="ms-auto badge bg-primary rounded-pill px-3 py-2 fs-6 fw-normal" id="selectedCount">
        <?= count($assignedIds) ?> dipilih
    </span>
</div>

<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success py-2"><?= session()->getFlashdata('success') ?></div>
<?php endif; ?>

<form method="POST" action="<?= base_url('people/competencies/dept/' . $dept['id'] . '/assign') ?>">
    <?= csrf_field() ?>

    <?php if (empty($groupedByCluster)): ?>
    <div class="card"><div class="card-body text-center py-4 text-muted">
        Belum ada kompetensi. Tambahkan di tab Master Kompetensi terlebih dahulu.
    </div></div>
    <?php else: ?>

    <?php foreach ($groupedByCluster as $group): ?>
    <div class="card mb-3">
        <div class="card-header d-flex align-items-center py-2 gap-2">
            <i class="bi bi-collection-fill text-primary"></i>
            <span class="fw-semibold"><?= esc($group['cluster_nama']) ?></span>
            <span class="badge bg-secondary-subtle text-secondary border"><?= count($group['comps']) ?></span>
            <button type="button" class="btn btn-sm btn-link text-decoration-none text-muted ms-auto p-0 select-cluster-btn"
                    data-cluster="<?= esc($group['cluster_nama']) ?>">
                Pilih Semua
            </button>
        </div>
        <div class="card-body p-0">
        <table class="table table-sm table-hover align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th style="width:40px" class="text-center ps-3">
                    <input type="checkbox" class="form-check-input cluster-check-all"
                           data-cluster="<?= esc($group['cluster_nama']) ?>">
                </th>
                <th style="width:70px" class="text-center">Tipe</th>
                <th>Kompetensi</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($group['comps'] as $c): ?>
        <tr class="comp-row" data-cluster="<?= esc($group['cluster_nama']) ?>"
            onclick="toggleCheck(this)" style="cursor:pointer">
            <td class="text-center ps-3" onclick="event.stopPropagation()">
                <input type="checkbox" class="form-check-input comp-check"
                       name="competency_ids[]"
                       value="<?= $c['id'] ?>"
                       data-cluster="<?= esc($group['cluster_nama']) ?>"
                       <?= in_array((int)$c['id'], $assignedIds) ? 'checked' : '' ?>
                       onchange="updateCount()">
            </td>
            <td class="text-center">
                <span class="badge <?= $c['kategori'] === 'hard' ? 'bg-primary' : 'bg-success' ?>" style="font-size:.65rem">
                    <?= ucfirst($c['kategori']) ?>
                </span>
            </td>
            <td>
                <div class="fw-semibold small"><?= esc($c['nama']) ?></div>
                <?php if (! empty($c['deskripsi'])): ?>
                <div class="text-muted" style="font-size:.72rem"><?= esc($c['deskripsi']) ?></div>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        </table>
        </div>
    </div>
    <?php endforeach; ?>

    <div class="d-flex gap-2 justify-content-between align-items-center mt-3 mb-4">
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary btn-sm" id="selectAll">
                <i class="bi bi-check-all me-1"></i>Pilih Semua
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="clearAll">
                <i class="bi bi-x-lg me-1"></i>Hapus Semua
            </button>
        </div>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-lg me-1"></i>Simpan Assignment
        </button>
    </div>

    <?php endif; ?>
</form>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
function updateCount() {
    const n = document.querySelectorAll('.comp-check:checked').length;
    document.getElementById('selectedCount').textContent = n + ' dipilih';
}

function toggleCheck(row) {
    const cb = row.querySelector('.comp-check');
    cb.checked = !cb.checked;
    updateCount();
}

document.querySelectorAll('.cluster-check-all').forEach(master => {
    master.addEventListener('change', function() {
        document.querySelectorAll(`.comp-check[data-cluster="${this.dataset.cluster}"]`)
            .forEach(cb => cb.checked = this.checked);
        updateCount();
    });
});

document.querySelectorAll('.select-cluster-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const cls = this.dataset.cluster;
        const cbs = document.querySelectorAll(`.comp-check[data-cluster="${cls}"]`);
        const allChecked = [...cbs].every(cb => cb.checked);
        cbs.forEach(cb => cb.checked = !allChecked);
        this.textContent = allChecked ? 'Pilih Semua' : 'Hapus Semua';
        updateCount();
    });
});

document.getElementById('selectAll').addEventListener('click', () => {
    document.querySelectorAll('.comp-check').forEach(cb => cb.checked = true);
    updateCount();
});
document.getElementById('clearAll').addEventListener('click', () => {
    document.querySelectorAll('.comp-check').forEach(cb => cb.checked = false);
    updateCount();
});
</script>
<?= $this->endSection() ?>
