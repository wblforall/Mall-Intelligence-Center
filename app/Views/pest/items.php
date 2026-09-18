<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= base_url('pest') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h4 class="fw-bold mb-0">Master Item Temuan Pest</h4>
        <small class="text-muted">Daftar jenis hama yang muncul sebagai baris di form input</small>
    </div>
</div>

<div class="row g-3">
<div class="col-lg-4">
    <div class="card">
    <div class="card-header py-2"><span class="fw-semibold small"><i class="bi bi-plus-lg me-1"></i>Tambah Item</span></div>
    <div class="card-body">
        <form method="POST" action="<?= base_url('pest-items/add') ?>">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label small fw-semibold">Nama Item</label>
                <input type="text" name="nama" class="form-control" required placeholder="mis. Semut">
            </div>
            <button class="btn btn-primary btn-sm w-100"><i class="bi bi-check-lg me-1"></i>Tambah</button>
        </form>
    </div>
    <div class="card-footer bg-transparent">
        <small class="text-muted"><i class="bi bi-info-circle me-1"></i>
            Item yang sudah punya temuan <strong>tidak bisa dihapus</strong> — angka historisnya ikut hilang.
            Nonaktifkan saja: ia lenyap dari form input, tapi tetap muncul di rekap tahun sebelumnya.
        </small>
    </div>
    </div>
</div>

<div class="col-lg-8">
    <div class="card">
    <div class="card-header py-2"><span class="fw-semibold small"><i class="bi bi-list-ol me-1"></i>Daftar Item</span></div>
    <div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
    <thead class="table-light">
    <tr><th style="width:70px">Urutan</th><th>Nama</th><th class="text-center" style="width:90px">Aktif</th><th class="text-end" style="width:120px">Aksi</th></tr>
    </thead>
    <tbody>
    <?php foreach ($items as $it): ?>
    <tr>
        <form method="POST" action="<?= base_url('pest-items/' . $it['id'] . '/edit') ?>">
        <?= csrf_field() ?>
        <td><input type="number" name="urutan" class="form-control form-control-sm" value="<?= (int) $it['urutan'] ?>" style="width:64px"></td>
        <td><input type="text" name="nama" class="form-control form-control-sm" value="<?= esc($it['nama']) ?>" required></td>
        <td class="text-center">
            <input type="checkbox" class="form-check-input" name="aktif" value="1" <?= $it['aktif'] ? 'checked' : '' ?>>
        </td>
        <td class="text-end">
            <button class="btn btn-sm btn-outline-primary" title="Simpan"><i class="bi bi-check-lg"></i></button>
        </form>
            <form method="POST" action="<?= base_url('pest-items/' . $it['id'] . '/delete') ?>" class="d-inline"
                  onsubmit="return confirm('Hapus item <?= esc($it['nama']) ?>?');">
                <?= csrf_field() ?>
                <button class="btn btn-sm btn-outline-danger" title="Hapus"><i class="bi bi-trash"></i></button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    </table>
    </div>
    </div>
</div>
</div>

<?= $this->endSection() ?>
