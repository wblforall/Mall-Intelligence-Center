<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<style>
.fade-up { opacity:0; transform:translateY(14px); animation:fadeUpVmS .55s cubic-bezier(.22,.68,0,1.2) forwards; }
@keyframes fadeUpVmS { to { opacity:1; transform:translateY(0); } }
</style>
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center gap-2 mb-4 fade-up" style="animation-delay:.05s">
    <div>
        <h4 class="fw-bold mb-0">Dekorasi & Visual Merchandising</h4>
        <small class="text-muted">Semua item — event & non-event</small>
    </div>
    <?php if ($canEdit): ?>
    <button class="btn btn-sm btn-primary ms-auto" data-bs-toggle="modal" data-bs-target="#addItemModal">
        <i class="bi bi-plus-lg me-1"></i> Tambah Item
    </button>
    <?php endif; ?>
</div>

<?php if (empty($items)): ?>
<div class="card"><div class="card-body text-center py-5 text-muted">
    <i class="bi bi-palette display-4 d-block mb-2 opacity-25"></i>
    <p>Belum ada item dekorasi.</p>
</div></div>
<?php else: ?>

<?php
// Group items by source for section headers
$standaloneItems = array_filter($items, fn($it) => $it['source'] === 'standalone');
$eventItems      = array_filter($items, fn($it) => $it['source'] === 'event');

$renderItem = function(array $it, array $realisasi, bool $canEdit) {
    $rData    = $realisasi[$it['id']] ?? ['total' => 0, 'entries' => []];
    $rTotal   = $rData['total'];
    $budget   = (int)$it['budget'];
    $pct      = $budget > 0 ? min(100, round($rTotal / $budget * 100)) : 0;
    $barColor = $pct >= 100 ? 'danger' : ($pct >= 60 ? 'warning' : ($pct >= 30 ? 'primary' : 'secondary'));
    $isEvent  = $it['source'] === 'event';
    ob_start();
?>
<div class="card mb-3" id="item-<?= $it['id'] ?>">
<div class="card-body">
    <div class="d-flex justify-content-between align-items-start">
        <div class="flex-grow-1">
            <div class="d-flex align-items-center gap-2 mb-1">
                <h6 class="fw-bold mb-0"><?= esc($it['nama_item']) ?></h6>
                <?php if ($isEvent): ?>
                <a href="<?= base_url('events/'.$it['event_id'].'/vm') ?>" class="badge bg-primary-subtle text-primary text-decoration-none" style="font-size:.7rem">
                    <i class="bi bi-calendar-event me-1"></i><?= esc($it['event_name']) ?><?= $it['event_mall'] ? ' — '.$it['event_mall'] : '' ?>
                </a>
                <?php endif; ?>
            </div>
            <?php if ($it['deskripsi_referensi']): ?>
            <p class="small text-muted mb-2" style="white-space:pre-line"><?= esc($it['deskripsi_referensi']) ?></p>
            <?php endif; ?>
            <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
            <?php if ($it['tanggal_deadline']): ?>
            <?php $overDeadline = strtotime($it['tanggal_deadline']) < strtotime(date('Y-m-d')); ?>
            <span class="small <?= $overDeadline ? 'text-danger fw-semibold' : 'text-muted' ?>">
                <i class="bi bi-calendar-event me-1"></i>Deadline: <?= date('d M Y', strtotime($it['tanggal_deadline'])) ?>
            </span>
            <?php if ($overDeadline): ?>
            <span class="badge bg-danger" style="font-size:.62rem"><i class="bi bi-exclamation-triangle-fill me-1"></i>Lewat Deadline</span>
            <?php endif; ?>
            <?php endif; ?>
            <?php if ($it['catatan']): ?>
            <span class="small text-muted"><i class="bi bi-sticky me-1"></i><?= esc($it['catatan']) ?></span>
            <?php endif; ?>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="small text-muted">Realisasi: <strong>Rp <?= number_format($rTotal,0,',','.') ?></strong> / Rp <?= number_format($budget,0,',','.') ?></span>
                <span class="small fw-bold text-<?= $barColor ?>"><?= $pct ?>%</span>
            </div>
            <div class="progress mb-2" style="height:6px">
                <div class="progress-bar bg-<?= $barColor ?>" style="width:<?= $pct ?>%"></div>
            </div>
        </div>
        <?php if ($canEdit && ! $isEvent): ?>
        <div class="d-flex gap-1 ms-3">
            <button class="btn btn-sm btn-outline-secondary edit-btn"
                data-id="<?= $it['id'] ?>"
                data-nama="<?= esc($it['nama_item']) ?>"
                data-deskripsi="<?= esc($it['deskripsi_referensi']) ?>"
                data-budget="<?= number_format($budget,0,',','.') ?>"
                data-catatan="<?= esc($it['catatan']) ?>"
                data-deadline="<?= esc($it['tanggal_deadline'] ?? '') ?>">
                <i class="bi bi-pencil"></i>
            </button>
            <a href="<?= base_url('vm/'.$it['id'].'/delete') ?>"
               class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus item ini beserta semua realisasinya?')">
                <i class="bi bi-trash"></i>
            </a>
        </div>
        <?php elseif ($isEvent): ?>
        <div class="ms-3">
            <a href="<?= base_url('events/'.$it['event_id'].'/vm') ?>" class="btn btn-sm btn-outline-secondary" title="Kelola di halaman event">
                <i class="bi bi-box-arrow-up-right"></i>
            </a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Realisasi section -->
    <div class="mt-2 border-top pt-2">
        <?php if ($canEdit && ! $isEvent): ?>
        <button class="btn btn-sm btn-outline-primary mb-2 toggle-realisasi" data-target="real-<?= $it['id'] ?>">
            <i class="bi bi-plus-circle me-1"></i>Input Realisasi
        </button>
        <div id="real-<?= $it['id'] ?>" class="d-none mb-2">
        <form method="POST" action="<?= base_url('vm/'.$it['id'].'/realisasi/add') ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="row g-2 align-items-end">
            <div class="col-auto">
                <label class="form-label small mb-1">Tanggal</label>
                <input type="date" name="tanggal" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="col-auto">
                <label class="form-label small mb-1">Jumlah (Rp)</label>
                <input type="text" name="jumlah" class="form-control form-control-sm currency-input" value="0" required>
            </div>
            <div class="col">
                <label class="form-label small mb-1">Keterangan</label>
                <input type="text" name="catatan" class="form-control form-control-sm" placeholder="Opsional">
            </div>
            <div class="col-auto">
                <label class="form-label small mb-1">Foto</label>
                <input type="file" name="foto" class="form-control form-control-sm" accept="image/*">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-check-lg"></i></button>
            </div>
        </div>
        </form>
        </div>
        <?php endif; ?>

        <?php if (! empty($rData['entries'])): ?>
        <table class="table table-sm table-hover mb-0">
        <thead><tr>
            <th class="small">Tanggal</th>
            <th class="small">Keterangan</th>
            <th class="small">Foto</th>
            <th class="text-end small">Jumlah</th>
            <?php if ($canEdit && ! $isEvent): ?><th></th><?php endif; ?>
        </tr></thead>
        <tbody>
        <?php foreach ($rData['entries'] as $re):
            $fotoUrl = $re['event_id']
                ? base_url('uploads/vm/' . $re['event_id'] . '/' . $re['foto_file_name'])
                : base_url('uploads/vm/standalone/' . $re['foto_file_name']);
        ?>
        <tr>
            <td class="small"><?= date('d/m/Y', strtotime($re['tanggal'])) ?></td>
            <td class="small text-muted"><?= esc($re['catatan'] ?? '—') ?></td>
            <td>
                <?php if (!empty($re['foto_file_name'])): ?>
                <a href="<?= $fotoUrl ?>" target="_blank">
                    <img src="<?= $fotoUrl ?>" style="width:60px;height:45px;object-fit:cover;border-radius:4px">
                </a>
                <?php else: ?>
                <span class="text-muted small">—</span>
                <?php endif; ?>
            </td>
            <td class="text-end small">Rp <?= number_format($re['jumlah'],0,',','.') ?></td>
            <?php if ($canEdit && ! $isEvent): ?>
            <td>
                <a href="<?= base_url('vm/'.$it['id'].'/realisasi/'.$re['id'].'/delete') ?>"
                   class="btn btn-sm btn-link text-danger p-0" onclick="return confirm('Hapus entri ini?')">
                    <i class="bi bi-x-circle"></i>
                </a>
            </td>
            <?php endif; ?>
        </tr>
        <?php endforeach; ?>
        <tr class="table-light fw-bold">
            <td colspan="3" class="small">Total Realisasi</td>
            <td class="text-end small text-<?= $barColor ?>">Rp <?= number_format($rTotal,0,',','.') ?></td>
            <?php if ($canEdit && ! $isEvent): ?><td></td><?php endif; ?>
        </tr>
        </tbody>
        </table>
        <?php elseif (! $canEdit || $isEvent): ?>
        <p class="small text-muted mb-0">Belum ada realisasi.</p>
        <?php endif; ?>
    </div>
</div>
</div>
<?php
    return ob_get_clean();
};
?>

<?php if (! empty($standaloneItems)): ?>
<h6 class="text-muted fw-semibold mb-2 mt-1" style="font-size:.72rem;letter-spacing:.06em;text-transform:uppercase">Non-Event</h6>
<?php foreach ($standaloneItems as $it): echo $renderItem($it, $realisasi, $canEdit); endforeach; ?>
<?php endif; ?>

<?php if (! empty($eventItems)): ?>
<h6 class="text-muted fw-semibold mb-2 mt-3" style="font-size:.72rem;letter-spacing:.06em;text-transform:uppercase">Dari Event</h6>
<?php foreach ($eventItems as $it): echo $renderItem($it, $realisasi, $canEdit); endforeach; ?>
<?php endif; ?>

<!-- Total footer -->
<div class="card border-warning-subtle mt-2">
<div class="card-body py-2">
    <div class="d-flex justify-content-between align-items-center mb-1">
        <span class="small fw-bold text-muted">Total Budget</span>
        <span class="fw-bold text-warning">Rp <?= number_format($totalBudget,0,',','.') ?></span>
    </div>
    <div class="d-flex justify-content-between align-items-center mb-1">
        <span class="small fw-bold text-muted">Total Realisasi</span>
        <span class="fw-bold">Rp <?= number_format($totalRealisasi,0,',','.') ?> <span class="text-muted fw-normal small">(<?= $totalPct ?>%)</span></span>
    </div>
    <div class="progress" style="height:5px">
        <div class="progress-bar bg-warning" style="width:<?= $totalPct ?>%"></div>
    </div>
</div>
</div>
<?php endif; ?>

<?php if ($canEdit): ?>
<div class="modal fade" id="addItemModal" tabindex="-1">
<div class="modal-dialog"><div class="modal-content">
<form method="POST" action="<?= base_url('vm/add') ?>">
<?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title fw-semibold">Tambah Item Dekorasi</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="mb-3"><label class="form-label small fw-semibold">Nama Item <span class="text-danger">*</span></label>
        <input type="text" name="nama_item" class="form-control" required placeholder="Backdrop, Signage, Display..."></div>
    <div class="mb-3"><label class="form-label small fw-semibold">Deskripsi / Referensi</label>
        <textarea name="deskripsi_referensi" class="form-control" rows="3" placeholder="Deskripsikan referensi desain atau spesifikasi..."></textarea></div>
    <div class="row g-2 mb-3">
        <div class="col-6"><label class="form-label small fw-semibold">Budget (Rp)</label>
            <input type="text" name="budget" class="form-control currency-input" value="0"></div>
        <div class="col-6"><label class="form-label small fw-semibold">Deadline</label>
            <input type="date" name="tanggal_deadline" class="form-control"></div>
    </div>
    <div class="mb-3"><label class="form-label small fw-semibold">Catatan</label>
        <input type="text" name="catatan" class="form-control"></div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Tambah</button></div>
</form>
</div></div></div>

<div class="modal fade" id="editItemModal" tabindex="-1">
<div class="modal-dialog"><div class="modal-content">
<form id="editItemForm" method="POST">
<?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title fw-semibold">Edit Item</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="mb-3"><label class="form-label small fw-semibold">Nama Item</label>
        <input type="text" name="nama_item" id="editNama" class="form-control" required></div>
    <div class="mb-3"><label class="form-label small fw-semibold">Deskripsi / Referensi</label>
        <textarea name="deskripsi_referensi" id="editDesk" class="form-control" rows="3"></textarea></div>
    <div class="row g-2 mb-3">
        <div class="col-6"><label class="form-label small fw-semibold">Budget (Rp)</label>
            <input type="text" name="budget" id="editBudget" class="form-control currency-input"></div>
        <div class="col-6"><label class="form-label small fw-semibold">Deadline</label>
            <input type="date" name="tanggal_deadline" id="editDeadline" class="form-control"></div>
    </div>
    <div class="mb-3"><label class="form-label small fw-semibold">Catatan</label>
        <input type="text" name="catatan" id="editCatatan" class="form-control"></div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
</form>
</div></div></div>
<?php endif; ?>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
document.querySelectorAll('.card.mb-3').forEach((card, i) => {
    card.style.opacity = '0';
    card.style.transform = 'translateY(16px)';
    setTimeout(() => {
        card.style.transition = 'opacity .45s ease, transform .45s ease';
        card.style.opacity = '1';
        card.style.transform = 'translateY(0)';
    }, 80 + i * 80);
});
(function() {
    const n = document.querySelectorAll('.card.mb-3').length;
    const footer = document.querySelector('.card.border-warning-subtle');
    if (footer) {
        footer.style.opacity = '0';
        footer.style.transform = 'translateY(12px)';
        setTimeout(() => {
            footer.style.transition = 'opacity .4s ease, transform .4s ease';
            footer.style.opacity = '1';
            footer.style.transform = 'translateY(0)';
        }, 120 + n * 80);
    }
})();
document.querySelectorAll('.progress-bar').forEach((bar, i) => {
    const target = bar.style.width;
    if (!target || parseFloat(target) === 0) return;
    bar.style.width = '0';
    setTimeout(() => {
        bar.style.transition = 'width .7s ease';
        bar.style.width = target;
    }, 300 + i * 60);
});
<?php if ($canEdit): ?>
document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('editItemForm').action = '<?= base_url('vm/') ?>' + this.dataset.id + '/edit';
        document.getElementById('editNama').value     = this.dataset.nama;
        document.getElementById('editDesk').value     = this.dataset.deskripsi;
        document.getElementById('editBudget').value   = this.dataset.budget;
        document.getElementById('editDeadline').value = this.dataset.deadline;
        document.getElementById('editCatatan').value  = this.dataset.catatan;
        new bootstrap.Modal(document.getElementById('editItemModal')).show();
    });
});
document.querySelectorAll('.toggle-realisasi').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById(this.dataset.target).classList.toggle('d-none');
    });
});
<?php endif; ?>
document.querySelectorAll('.currency-input').forEach(inp => {
    inp.addEventListener('input', function() { let n = parseInt(this.value.replace(/[^0-9]/g,''))||0; this.value=n.toLocaleString('id-ID'); });
});
</script>
<?= $this->endSection() ?>
