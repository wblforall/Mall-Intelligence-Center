<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4 fade-up" style="animation-delay:.05s">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-door-open me-2"></i>Master Pintu Traffic</h4>
        <small class="text-muted">Drag untuk mengubah urutan tampil di form input traffic</small>
    </div>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
        <i class="bi bi-plus-lg me-1"></i> Tambah Pintu
    </button>
</div>

<style>
.drag-handle { cursor: grab; color: #94a3b8; padding: 0 6px; }
.drag-handle:active { cursor: grabbing; }
.sortable-ghost { opacity: .4; background: #f1f5f9; }
.sortable-chosen { box-shadow: 0 4px 12px rgba(0,0,0,.12); }
.card { overflow: hidden; }
.list-group-item { transition: background .15s; }
</style>

<div class="row g-3">

<?php $mi = 0; foreach (['ewalk' => 'eWalk', 'pentacity' => 'Pentacity'] as $mallKey => $mallLabel): ?>
<div class="col-lg-6 fade-up" style="animation-delay:<?= .15 + $mi++ * .1 ?>s">
<div class="card">
<div class="card-header d-flex justify-content-between align-items-center">
    <h6 class="mb-0 fw-semibold">
        <i class="bi bi-building me-2 text-<?= $mallKey === 'ewalk' ? 'primary' : 'success' ?>"></i><?= $mallLabel ?>
    </h6>
    <span class="badge bg-secondary-subtle text-secondary" id="count-<?= $mallKey ?>"><?= count($grouped[$mallKey]) ?> pintu</span>
</div>
<div class="card-body p-0">
<?php if (empty($grouped[$mallKey])): ?>
<div class="text-center py-4 text-muted small">Belum ada pintu untuk <?= $mallLabel ?>.</div>
<?php else: ?>
<div class="list-group list-group-flush sortable-list" id="sortable-<?= $mallKey ?>" data-mall="<?= $mallKey ?>">
<?php foreach ($grouped[$mallKey] as $di => $door): ?>
<div class="list-group-item d-flex justify-content-between align-items-center py-2 fade-up"
     style="animation-delay:<?= (.15 + $mi * .1) + $di * .05 ?>s" data-id="<?= $door['id'] ?>">
    <div class="d-flex align-items-center gap-1">
        <span class="drag-handle"><i class="bi bi-grip-vertical"></i></span>
        <span class="fw-medium small"><?= esc($door['nama_pintu']) ?></span>
        <?php if (! $door['aktif']): ?>
        <span class="badge bg-secondary-subtle text-secondary ms-1" style="font-size:.65rem">Nonaktif</span>
        <?php endif; ?>
    </div>
    <div class="d-flex gap-1">
        <button class="btn btn-xs btn-outline-secondary edit-btn" style="padding:.2rem .5rem;font-size:.75rem"
            data-id="<?= $door['id'] ?>"
            data-mall="<?= $door['mall'] ?>"
            data-nama="<?= esc($door['nama_pintu']) ?>"
            data-aktif="<?= $door['aktif'] ?>">
            <i class="bi bi-pencil"></i>
        </button>
        <a href="<?= base_url('traffic-doors/'.$door['id'].'/delete') ?>"
           class="btn btn-xs btn-outline-danger" style="padding:.2rem .5rem;font-size:.75rem"
           onclick="return confirm('Hapus pintu ini?')">
            <i class="bi bi-trash"></i>
        </a>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>
</div>
</div>
<?php endforeach; ?>

</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<form method="POST" action="<?= base_url('traffic-doors/add') ?>">
<?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title fw-semibold">Tambah Pintu</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="mb-3">
        <label class="form-label small fw-semibold">Mall <span class="text-danger">*</span></label>
        <select name="mall" class="form-select">
            <option value="ewalk">eWalk</option>
            <option value="pentacity">Pentacity</option>
        </select>
    </div>
    <div class="mb-0">
        <label class="form-label small fw-semibold">Nama Pintu <span class="text-danger">*</span></label>
        <input type="text" name="nama_pintu" class="form-control" placeholder="Pintu Utama, Gate A, Lobby..." required>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
    <button type="submit" class="btn btn-primary">Tambah</button>
</div>
</form>
</div></div></div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<form id="editForm" method="POST">
<?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title fw-semibold">Edit Pintu</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="mb-3">
        <label class="form-label small fw-semibold">Mall</label>
        <select name="mall" id="eMall" class="form-select">
            <option value="ewalk">eWalk</option>
            <option value="pentacity">Pentacity</option>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label small fw-semibold">Nama Pintu</label>
        <input type="text" name="nama_pintu" id="eNama" class="form-control" required>
    </div>
    <div class="form-check">
        <input class="form-check-input" type="checkbox" name="aktif" id="eAktif" value="1">
        <label class="form-check-label small" for="eAktif">Aktif (tampil di form input traffic)</label>
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
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
const reorderUrl  = '<?= base_url('traffic-doors/reorder') ?>';
const csrfName    = '<?= csrf_token() ?>';
const csrfHash    = '<?= csrf_hash() ?>';

let saveTimer = null;

function saveOrder(listEl) {
    const ids  = [...listEl.querySelectorAll('[data-id]')].map(el => el.dataset.id);
    const mall = listEl.dataset.mall;
    const body = new URLSearchParams();
    body.append(csrfName, csrfHash);
    ids.forEach(id => body.append('ids[]', id));

    fetch(reorderUrl, { method: 'POST', body })
        .then(r => r.json())
        .then(() => showToast('Urutan disimpan'))
        .catch(() => showToast('Gagal menyimpan urutan', true));
}

document.querySelectorAll('.sortable-list').forEach(el => {
    Sortable.create(el, {
        animation: 150,
        handle: '.drag-handle',
        ghostClass: 'sortable-ghost',
        chosenClass: 'sortable-chosen',
        onEnd() {
            clearTimeout(saveTimer);
            saveTimer = setTimeout(() => saveOrder(el), 400);
        }
    });
});

function showToast(msg, isError = false) {
    const t = document.createElement('div');
    t.className = `position-fixed bottom-0 end-0 m-3 alert alert-${isError ? 'danger' : 'success'} py-2 px-3 small shadow`;
    t.style.cssText = 'z-index:9999;opacity:0;transition:opacity .2s';
    t.textContent = msg;
    document.body.appendChild(t);
    requestAnimationFrame(() => t.style.opacity = '1');
    setTimeout(() => { t.style.opacity = '0'; setTimeout(() => t.remove(), 200); }, 2000);
}

document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('editForm').action = '<?= base_url('traffic-doors/') ?>' + this.dataset.id + '/edit';
        document.getElementById('eMall').value    = this.dataset.mall;
        document.getElementById('eNama').value    = this.dataset.nama;
        document.getElementById('eAktif').checked = this.dataset.aktif === '1';
        new bootstrap.Modal(document.getElementById('editModal')).show();
    });
});
</script>
<?= $this->endSection() ?>
