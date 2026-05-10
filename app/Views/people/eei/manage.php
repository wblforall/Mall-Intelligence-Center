<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="<?= base_url('people/eei') ?>" class="btn btn-sm btn-light">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div>
        <h5 class="fw-bold mb-0">Kelola EEI Survey</h5>
        <small class="text-muted">Dimensi, pertanyaan, dan periode survey</small>
    </div>
</div>

<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success py-2"><?= session()->getFlashdata('success') ?></div>
<?php endif; ?>

<!-- Tabs -->
<ul class="nav nav-tabs mb-4" id="manageTabs">
    <li class="nav-item">
        <a class="nav-link active" data-bs-toggle="tab" href="#tabDim">
            <i class="bi bi-diagram-3 me-1"></i>Dimensi & Pertanyaan
            <span class="badge bg-secondary ms-1"><?= count($dimensions) ?></span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#tabPeriod">
            <i class="bi bi-calendar-range me-1"></i>Periode Survey
            <span class="badge bg-secondary ms-1"><?= count($periods) ?></span>
        </a>
    </li>
</ul>

<div class="tab-content">

<!-- Dimensi & Pertanyaan -->
<div class="tab-pane fade show active" id="tabDim">
    <div class="d-flex justify-content-end mb-3">
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addDimModal">
            <i class="bi bi-plus-lg me-1"></i>Tambah Dimensi
        </button>
    </div>

    <?php if (empty($dimensions)): ?>
    <div class="card"><div class="card-body text-center py-4 text-muted">
        Belum ada dimensi. Tambahkan dimensi engagement terlebih dahulu.
    </div></div>
    <?php endif; ?>

    <?php foreach ($dimensions as $dim): ?>
    <div class="card mb-3">
    <div class="card-header d-flex align-items-center gap-2 py-2">
        <i class="bi bi-diagram-3-fill text-primary"></i>
        <span class="fw-semibold"><?= esc($dim['nama']) ?></span>
        <?php if ($dim['deskripsi']): ?>
        <span class="text-muted small">— <?= esc($dim['deskripsi']) ?></span>
        <?php endif; ?>
        <span class="badge bg-secondary ms-auto"><?= count($dim['questions']) ?> pertanyaan</span>
        <button class="btn btn-sm btn-outline-secondary edit-dim-btn ms-1"
                data-id="<?= $dim['id'] ?>"
                data-nama="<?= esc($dim['nama']) ?>"
                data-deskripsi="<?= esc($dim['deskripsi'] ?? '') ?>">
            <i class="bi bi-pencil"></i>
        </button>
        <a href="<?= base_url('people/eei/dimension/' . $dim['id'] . '/delete') ?>"
           class="btn btn-sm btn-outline-danger ms-1"
           onclick="return confirm('Hapus dimensi beserta semua pertanyaannya?')">
            <i class="bi bi-trash"></i>
        </a>
    </div>
    <div class="card-body p-0">
        <?php if (! empty($dim['questions'])): ?>
        <table class="table table-sm align-middle mb-0">
        <tbody>
        <?php foreach ($dim['questions'] as $i => $q): ?>
        <tr>
            <td class="ps-3 text-muted" style="width:32px"><?= $i + 1 ?></td>
            <td class="small"><?= esc($q['pertanyaan']) ?>
                <?php if ($q['is_reversed']): ?>
                <span class="badge bg-warning-subtle text-warning border ms-1" title="Reversed scoring">R</span>
                <?php endif; ?>
            </td>
            <td class="pe-2 text-end" style="width:60px">
                <a href="<?= base_url('people/eei/question/' . $q['id'] . '/delete') ?>"
                   class="btn btn-sm btn-outline-danger"
                   onclick="return confirm('Hapus pertanyaan ini?')">
                    <i class="bi bi-trash"></i>
                </a>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        </table>
        <?php endif; ?>
        <div class="p-2 border-top">
        <form method="POST" action="<?= base_url('people/eei/dimension/' . $dim['id'] . '/questions/add') ?>"
              class="d-flex gap-2 align-items-start">
            <?= csrf_field() ?>
            <input type="text" name="pertanyaan" class="form-control form-control-sm"
                   placeholder="Tambah pertanyaan baru..." required style="max-width:500px">
            <div class="form-check form-check-inline mt-1 mb-0">
                <input type="checkbox" class="form-check-input" name="is_reversed" value="1" id="rev<?= $dim['id'] ?>">
                <label class="form-check-label small" for="rev<?= $dim['id'] ?>">Reversed</label>
            </div>
            <button type="submit" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-plus-lg"></i>
            </button>
        </form>
        </div>
    </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Periode -->
<div class="tab-pane fade" id="tabPeriod">
    <div class="d-flex justify-content-end mb-3">
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addPeriodModal">
            <i class="bi bi-plus-lg me-1"></i>Tambah Periode
        </button>
    </div>

    <?php if (empty($periods)): ?>
    <div class="card"><div class="card-body text-center py-4 text-muted">Belum ada periode survey.</div></div>
    <?php endif; ?>

    <?php foreach ($periods as $p): ?>
    <div class="card mb-3">
    <div class="card-body">
    <div class="d-flex align-items-center gap-3">
        <div>
            <div class="fw-semibold"><?= esc($p['nama']) ?></div>
            <div class="text-muted small">
                <?= date('d M Y', strtotime($p['start_date'])) ?> —
                <?= date('d M Y', strtotime($p['end_date'])) ?>
            </div>
        </div>
        <?php if ($p['is_active']): ?>
        <span class="badge bg-success">Aktif</span>
        <?php else: ?>
        <span class="badge bg-secondary">Nonaktif</span>
        <?php endif; ?>
        <div class="ms-auto d-flex gap-2">
            <?php if (! $p['is_active']): ?>
            <a href="<?= base_url('people/eei/period/' . $p['id'] . '/activate') ?>"
               class="btn btn-sm btn-outline-success"
               onclick="return confirm('Aktifkan periode ini? Periode aktif lainnya akan dinonaktifkan.')">
                <i class="bi bi-play-fill me-1"></i>Aktifkan
            </a>
            <?php endif; ?>
            <button class="btn btn-sm btn-outline-secondary edit-period-btn"
                    data-id="<?= $p['id'] ?>"
                    data-nama="<?= esc($p['nama']) ?>"
                    data-start="<?= $p['start_date'] ?>"
                    data-end="<?= $p['end_date'] ?>">
                <i class="bi bi-pencil"></i>
            </button>
            <a href="<?= base_url('people/eei/period/' . $p['id'] . '/delete') ?>"
               class="btn btn-sm btn-outline-danger"
               onclick="return confirm('Hapus periode ini? Semua respons akan ikut terhapus.')">
                <i class="bi bi-trash"></i>
            </a>
        </div>
    </div>
    <?php if (! empty($p['survey_token'])): ?>
    <div class="border-top pt-3 mt-1">
        <label class="form-label small fw-semibold text-muted mb-1">
            <i class="bi bi-link-45deg me-1"></i>Link Survey untuk Karyawan
        </label>
        <div class="input-group input-group-sm" style="max-width:520px">
            <input type="text" class="form-control form-control-sm font-monospace"
                   id="surveyLink<?= $p['id'] ?>"
                   value="<?= base_url('eei/' . $p['survey_token']) ?>" readonly>
            <button class="btn btn-outline-secondary" type="button"
                    onclick="copyLink(<?= $p['id'] ?>)" id="copyBtn<?= $p['id'] ?>">
                <i class="bi bi-clipboard"></i> Salin
            </button>
        </div>
        <div class="form-text">Bagikan link ini via WhatsApp/email. Karyawan tidak perlu login.</div>
    </div>
    <?php endif; ?>
    </div>
    </div>
    <?php endforeach; ?>
</div>

</div><!-- tab-content -->

<!-- Add Dimension Modal -->
<div class="modal fade" id="addDimModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<form method="POST" action="<?= base_url('people/eei/dimension/add') ?>">
<?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title fw-semibold">Tambah Dimensi</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="mb-3">
        <label class="form-label small fw-semibold">Nama Dimensi <span class="text-danger">*</span></label>
        <input type="text" name="nama" class="form-control" required
               placeholder="mis. Kepemimpinan & Manajemen, Pengembangan Karir, ...">
    </div>
    <div>
        <label class="form-label small fw-semibold">Deskripsi</label>
        <input type="text" name="deskripsi" class="form-control" placeholder="Opsional">
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
    <button type="submit" class="btn btn-primary">Tambah</button>
</div>
</form>
</div></div></div>

<!-- Edit Dimension Modal -->
<div class="modal fade" id="editDimModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<form id="editDimForm" method="POST">
<?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title fw-semibold">Edit Dimensi</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="mb-3">
        <label class="form-label small fw-semibold">Nama Dimensi <span class="text-danger">*</span></label>
        <input type="text" name="nama" id="eDimNama" class="form-control" required>
    </div>
    <div>
        <label class="form-label small fw-semibold">Deskripsi</label>
        <input type="text" name="deskripsi" id="eDimDesk" class="form-control">
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
    <button type="submit" class="btn btn-primary">Simpan</button>
</div>
</form>
</div></div></div>

<!-- Add Period Modal -->
<div class="modal fade" id="addPeriodModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<form method="POST" action="<?= base_url('people/eei/period/add') ?>">
<?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title fw-semibold">Tambah Periode Survey</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="mb-3">
        <label class="form-label small fw-semibold">Nama Periode <span class="text-danger">*</span></label>
        <input type="text" name="nama" class="form-control" required placeholder="mis. EEI Q1 2026">
    </div>
    <div class="row g-2">
        <div class="col">
            <label class="form-label small fw-semibold">Tanggal Mulai</label>
            <input type="date" name="start_date" class="form-control" required>
        </div>
        <div class="col">
            <label class="form-label small fw-semibold">Tanggal Selesai</label>
            <input type="date" name="end_date" class="form-control" required>
        </div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
    <button type="submit" class="btn btn-primary">Tambah</button>
</div>
</form>
</div></div></div>

<!-- Edit Period Modal -->
<div class="modal fade" id="editPeriodModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<form id="editPeriodForm" method="POST">
<?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title fw-semibold">Edit Periode</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="mb-3">
        <label class="form-label small fw-semibold">Nama Periode</label>
        <input type="text" name="nama" id="ePeriodNama" class="form-control" required>
    </div>
    <div class="row g-2">
        <div class="col">
            <label class="form-label small fw-semibold">Tanggal Mulai</label>
            <input type="date" name="start_date" id="ePeriodStart" class="form-control" required>
        </div>
        <div class="col">
            <label class="form-label small fw-semibold">Tanggal Selesai</label>
            <input type="date" name="end_date" id="ePeriodEnd" class="form-control" required>
        </div>
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
document.querySelectorAll('.edit-dim-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('editDimForm').action =
            '<?= base_url('people/eei/dimension/') ?>' + this.dataset.id + '/edit';
        document.getElementById('eDimNama').value = this.dataset.nama;
        document.getElementById('eDimDesk').value = this.dataset.deskripsi;
        new bootstrap.Modal(document.getElementById('editDimModal')).show();
    });
});

function copyLink(id) {
    const input = document.getElementById('surveyLink' + id);
    navigator.clipboard.writeText(input.value).then(() => {
        const btn = document.getElementById('copyBtn' + id);
        btn.innerHTML = '<i class="bi bi-check-lg"></i> Disalin!';
        btn.classList.replace('btn-outline-secondary', 'btn-success');
        setTimeout(() => {
            btn.innerHTML = '<i class="bi bi-clipboard"></i> Salin';
            btn.classList.replace('btn-success', 'btn-outline-secondary');
        }, 2000);
    });
}

document.querySelectorAll('.edit-period-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('editPeriodForm').action =
            '<?= base_url('people/eei/period/') ?>' + this.dataset.id + '/edit';
        document.getElementById('ePeriodNama').value  = this.dataset.nama;
        document.getElementById('ePeriodStart').value = this.dataset.start;
        document.getElementById('ePeriodEnd').value   = this.dataset.end;
        new bootstrap.Modal(document.getElementById('editPeriodModal')).show();
    });
});
</script>
<?= $this->endSection() ?>
