<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-calendar2-range me-2"></i>Periode Talent Review</h4>
        <small class="text-muted">Buat periode, aktifkan (generate penilaian), lalu kunci saat selesai.</small>
    </div>
    <a href="<?= base_url('people/talent') ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-grid-3x3-gap me-1"></i>Peta 9-Box</a>
</div>

<div class="row g-3">
<div class="col-lg-4">
    <div class="card">
        <div class="card-header py-2 fw-semibold small"><i class="bi bi-plus-circle me-1"></i>Buat Periode Baru</div>
        <div class="card-body">
            <form method="POST" action="<?= base_url('people/talent/periods/create') ?>">
                <?= csrf_field() ?>
                <label class="form-label small fw-semibold">Nama Periode</label>
                <input type="text" name="nama" class="form-control form-control-sm mb-2" placeholder="mis. Talent Review 2026" required>
                <button class="btn btn-primary btn-sm w-100"><i class="bi bi-save me-1"></i>Buat (status Draft)</button>
            </form>
            <div class="small text-muted mt-2"><i class="bi bi-info-circle me-1"></i>Setelah dibuat, klik <strong>Aktifkan</strong> untuk membuat penilaian bagi semua karyawan dalam cakupan (di bawah GM, non-outsource, non-probation).</div>
        </div>
    </div>
</div>
<div class="col-lg-8">
    <div class="card">
    <div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
        <thead><tr><th>Periode</th><th>Status</th><th class="text-center">Dinilai</th><th class="text-end">Aksi</th></tr></thead>
        <tbody>
        <?php if (empty($periods)): ?>
        <tr><td colspan="4" class="text-center text-muted py-4">Belum ada periode.</td></tr>
        <?php else: foreach ($periods as $p):
            $badge = ['draft'=>'bg-secondary','active'=>'bg-success','locked'=>'bg-dark'][$p['status']] ?? 'bg-secondary'; ?>
        <tr>
            <td class="fw-medium"><?= esc($p['nama']) ?></td>
            <td><span class="badge <?= $badge ?>"><?= ucfirst($p['status']) ?></span></td>
            <td class="text-center small"><?= $p['n_placed'] ?> / <?= $p['n_total'] ?></td>
            <td class="text-end">
                <?php if ($p['status'] === 'draft'): ?>
                <form method="POST" action="<?= base_url('people/talent/periods/'.$p['id'].'/activate') ?>" class="d-inline" onsubmit="return confirm('Aktifkan periode ini &amp; generate penilaian untuk semua karyawan dalam cakupan?')">
                    <?= csrf_field() ?><button class="btn btn-success btn-sm"><i class="bi bi-play-fill"></i> Aktifkan</button>
                </form>
                <?php elseif ($p['status'] === 'active'): ?>
                <form method="POST" action="<?= base_url('people/talent/periods/'.$p['id'].'/lock') ?>" class="d-inline" onsubmit="return confirm('Kunci periode? Penempatan menjadi read-only dan tak bisa diubah.')">
                    <?= csrf_field() ?><button class="btn btn-outline-dark btn-sm"><i class="bi bi-lock-fill"></i> Kunci</button>
                </form>
                <?php else: ?>
                <span class="text-muted small"><i class="bi bi-lock"></i> terkunci</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
    </div>
    </div>
</div>
</div>

<?= $this->endSection() ?>
