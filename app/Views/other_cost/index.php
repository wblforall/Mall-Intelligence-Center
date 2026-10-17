<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/events-summary.css') ?>?v=<?= @filemtime(FCPATH . 'css/events-summary.css') ?: 1 ?>">
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php
$deptMap = [];
foreach ($departments as $d) { $deptMap[$d['id']] = $d['name']; }
$rp = static fn($n) => 'Rp ' . number_format((int) $n, 0, ',', '.');
// Subtotal per departemen (hanya penjumlahan dari item yang sudah ada)
$perDept = [];
foreach ($items as $it) {
    $k = $it['dept_name'] ?? '-';
    $perDept[$k] = ($perDept[$k] ?? 0) + (int) $it['jumlah'];
}
arsort($perDept);
$maxItem = 0;
foreach ($items as $it) { $maxItem = max($maxItem, (int) $it['jumlah']); }
?>
<div class="page-evs">

<!-- Header -->
<div class="ev-head">
    <a href="<?= base_url('events/'.$event['id'].'/summary') ?>" class="ev-iconbtn ev-back" title="Kembali ke Summary event" aria-label="Kembali ke Summary event"><i class="bi bi-arrow-left"></i></a>
    <div class="min-w-0">
        <h1 class="ev-title">Other Cost</h1>
        <div class="ev-subtitle"><?= esc($event['name']) ?></div>
    </div>
    <div class="ev-head-act">
        <a href="<?= base_url('events/'.$event['id'].'/budget') ?>" class="ev-btn"><i class="bi bi-wallet2"></i><span class="d-none d-sm-inline">Budget Summary</span></a>
        <?php if ($canEdit): ?>
        <button class="ev-btn ev-btn-primary" data-bs-toggle="modal" data-bs-target="#addModal"><i class="bi bi-plus-lg"></i>Tambah</button>
        <?php endif; ?>
    </div>
</div>
<?= view('partials/event_tabs', ['event' => $event]) ?>

<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success alert-dismissible fade show py-2" role="alert">
    <?= session()->getFlashdata('success') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
<div class="alert alert-danger alert-dismissible fade show py-2" role="alert">
    <?= session()->getFlashdata('error') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (empty($items)): ?>
<div class="ev-empty">
    <i class="bi bi-receipt"></i>
    <h5>Belum ada other cost untuk event ini</h5>
    <p>Catat biaya di luar modul Loyalty, VM, Content, dan Creative — per departemen.</p>
    <?php if ($canEdit): ?>
    <button class="ev-btn ev-btn-primary" data-bs-toggle="modal" data-bs-target="#addModal"><i class="bi bi-plus-lg"></i>Tambah Other Cost Pertama</button>
    <?php endif; ?>
</div>
<?php else: ?>

<!-- Strip KPI -->
<div class="ev-kpis ev-k3">
    <div class="ev-kpi">
        <div class="ev-kpi-l">Total Other Cost</div>
        <div class="ev-kpi-v ev-warn"><?= $rp($total) ?></div>
        <div class="ev-kpi-s">seluruh item</div>
    </div>
    <div class="ev-kpi">
        <div class="ev-kpi-l">Jumlah Item</div>
        <div class="ev-kpi-v"><?= count($items) ?></div>
        <div class="ev-kpi-s">dari <?= count($perDept) ?> departemen</div>
    </div>
    <div class="ev-kpi">
        <div class="ev-kpi-l">Terbesar</div>
        <div class="ev-kpi-v"><?= $rp($maxItem) ?></div>
        <div class="ev-kpi-s"><?= $total > 0 ? round($maxItem / $total * 100) . '% dari total' : '' ?></div>
    </div>
</div>

<!-- Per departemen -->
<?php if (count($perDept) > 1): ?>
<div class="ev-panel">
    <div class="ev-panel-h"><h2 class="ev-panel-t"><i class="bi bi-buildings"></i>Per Departemen</h2></div>
    <div class="ev-tablewrap">
    <table class="ev-table"><tbody>
    <?php foreach ($perDept as $dn => $sum): $pc = $total > 0 ? round($sum / $total * 100) : 0; ?>
    <tr><td class="ev-wrap"><?= esc($dn) ?></td>
        <td style="width:40%"><div class="ev-bar ev-bar-mid"><i style="width:<?= $pc ?>%"></i></div></td>
        <td class="r nw" style="width:1%"><b><?= $rp($sum) ?></b> <span class="ev-sub d-inline ms-1"><?= $pc ?>%</span></td></tr>
    <?php endforeach; ?>
    </tbody></table>
    </div>
</div>
<?php endif; ?>

<!-- Pencarian -->
<div class="ev-tools">
    <div class="ev-search">
        <i class="bi bi-search"></i>
        <input type="search" id="evQ" placeholder="Cari kategori, keterangan, atau departemen…" autocomplete="off" aria-label="Cari other cost">
    </div>
    <span class="ev-count" id="evCount"></span>
</div>

<div class="ev-panel">
    <div class="ev-tablewrap">
        <table class="ev-table">
            <thead>
                <tr>
                    <th>Dept</th>
                    <th>Nama / Kategori</th>
                    <th>Keterangan</th>
                    <th class="r">Jumlah</th>
                    <?php if ($canEdit): ?><th class="r">Aksi</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $it): ?>
            <tr data-search="<?= esc(strtolower(($it['dept_name'] ?? '') . ' ' . $it['kategori'] . ' ' . ($it['keterangan'] ?? '')), 'attr') ?>">
                <td><span class="ev-chip ev-chip-mute"><?= esc($it['dept_name'] ?? '-') ?></span></td>
                <td class="fw-semibold ev-wrap"><?= esc($it['kategori']) ?></td>
                <td class="ev-mute ev-wrap"><?= esc($it['keterangan'] ?? '') ?></td>
                <td class="r nw fw-semibold"><?= $rp($it['jumlah']) ?></td>
                <?php if ($canEdit): ?>
                <td class="r nw">
                    <button class="ev-iconbtn ev-iconbtn-sm" title="Edit" aria-label="Edit <?= esc($it['kategori'], 'attr') ?>"
                        data-bs-toggle="modal" data-bs-target="#editModal"
                        data-id="<?= $it['id'] ?>"
                        data-dept="<?= $it['department_id'] ?>"
                        data-kategori="<?= esc($it['kategori'], 'attr') ?>"
                        data-keterangan="<?= esc($it['keterangan'] ?? '', 'attr') ?>"
                        data-jumlah="<?= (int)$it['jumlah'] ?>">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="ev-iconbtn ev-iconbtn-sm ev-iconbtn-bad" title="Hapus" aria-label="Hapus <?= esc($it['kategori'], 'attr') ?>"
                        data-bs-toggle="modal" data-bs-target="#deleteModal"
                        data-id="<?= $it['id'] ?>"
                        data-label="<?= esc($it['kategori'], 'attr') ?>">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="r">Total</td>
                    <td class="r nw"><?= $rp($total) ?></td>
                    <?php if ($canEdit): ?><td></td><?php endif; ?>
                </tr>
            </tfoot>
        </table>
    </div>
    <div class="ev-empty-sm" id="evNoMatch" hidden>Tidak ada item yang cocok dengan pencarian.</div>
</div>
<?php endif; ?>

<?php if ($canEdit): ?>
<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" action="<?= base_url('events/'.$event['id'].'/other-cost/add') ?>">
            <?= csrf_field() ?>
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title fw-semibold">Tambah Other Cost</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label form-label-sm fw-semibold">Departemen <span class="text-danger">*</span></label>
                        <select name="department_id" class="form-select form-select-sm" required>
                            <option value="">-- Pilih Departemen --</option>
                            <?php foreach ($departments as $d): ?>
                            <option value="<?= $d['id'] ?>"><?= esc($d['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label form-label-sm fw-semibold">Nama / Kategori <span class="text-danger">*</span></label>
                        <input type="text" name="kategori" class="form-control form-control-sm" required placeholder="cth. Biaya Transportasi">
                    </div>
                    <div class="mb-3">
                        <label class="form-label form-label-sm fw-semibold">Keterangan</label>
                        <input type="text" name="keterangan" class="form-control form-control-sm" placeholder="Opsional">
                    </div>
                    <div class="mb-3">
                        <label class="form-label form-label-sm fw-semibold">Jumlah (Rp) <span class="text-danger">*</span></label>
                        <input type="number" name="jumlah" class="form-control form-control-sm" required min="0" placeholder="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" id="editForm" action="">
            <?= csrf_field() ?>
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title fw-semibold">Edit Other Cost</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label form-label-sm fw-semibold">Departemen <span class="text-danger">*</span></label>
                        <select name="department_id" id="editDept" class="form-select form-select-sm" required>
                            <option value="">-- Pilih Departemen --</option>
                            <?php foreach ($departments as $d): ?>
                            <option value="<?= $d['id'] ?>"><?= esc($d['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label form-label-sm fw-semibold">Nama / Kategori <span class="text-danger">*</span></label>
                        <input type="text" name="kategori" id="editKategori" class="form-control form-control-sm" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label form-label-sm fw-semibold">Keterangan</label>
                        <input type="text" name="keterangan" id="editKeterangan" class="form-control form-control-sm">
                    </div>
                    <div class="mb-3">
                        <label class="form-label form-label-sm fw-semibold">Jumlah (Rp) <span class="text-danger">*</span></label>
                        <input type="number" name="jumlah" id="editJumlah" class="form-control form-control-sm" required min="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <form method="post" id="deleteForm" action="">
            <?= csrf_field() ?>
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title fw-semibold text-danger">Hapus Item</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Hapus <strong id="deleteLabel"></strong>?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

</div><!-- /page-evs -->
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="<?= base_url('js/events-summary.js') ?>?v=<?= @filemtime(FCPATH . 'js/events-summary.js') ?: 1 ?>"></script>
<?php if ($canEdit): ?>
<script>
document.querySelectorAll('[data-bs-target="#editModal"]').forEach(btn => {
    btn.addEventListener('click', () => {
        const id = btn.dataset.id;
        document.getElementById('editForm').action = `<?= base_url('events/'.$event['id'].'/other-cost/') ?>${id}/edit`;
        document.getElementById('editDept').value      = btn.dataset.dept;
        document.getElementById('editKategori').value  = btn.dataset.kategori;
        document.getElementById('editKeterangan').value= btn.dataset.keterangan;
        document.getElementById('editJumlah').value    = btn.dataset.jumlah;
    });
});
document.querySelectorAll('[data-bs-target="#deleteModal"]').forEach(btn => {
    btn.addEventListener('click', () => {
        const id = btn.dataset.id;
        document.getElementById('deleteForm').action = `<?= base_url('events/'.$event['id'].'/other-cost/') ?>${id}/delete`;
        document.getElementById('deleteLabel').textContent = btn.dataset.label;
    });
});
</script>
<?php endif; ?>
<?= $this->endSection() ?>
