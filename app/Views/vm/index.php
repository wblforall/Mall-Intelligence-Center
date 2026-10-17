<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/events-modul.css') ?>?v=<?= @filemtime(FCPATH . 'css/events-modul.css') ?: 1 ?>">
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php
if (! function_exists('evRp')) {
    function evRp($n): string { return 'Rp ' . number_format((float) $n, 0, ',', '.'); }
}
$today = strtotime(date('Y-m-d'));
$vmBase = 'uploads/vm/' . $event['id'] . '/';
$nOver = $nDone = $nNoReal = 0;
foreach ($items as $it) {
    $rt = $realisasi[$it['id']]['total'] ?? 0;
    $bd = (int) $it['budget'];
    if ($it['tanggal_deadline'] && strtotime($it['tanggal_deadline']) < $today) $nOver++;
    if ($bd > 0 && $rt >= $bd) $nDone++;
    if (empty($realisasi[$it['id']]['entries'])) $nNoReal++;
}
$sisaVM = $totalBudgetVM - $totalRealisasiVM;
$barAll = $totalPct >= 100 ? 'ok' : ($totalPct >= 50 ? 'mid' : 'low');

$actions = '';
if ($canEdit) {
    $actions = '<button class="ev-btn ev-btn-primary" data-bs-toggle="modal" data-bs-target="#addItemModal"><i class="bi bi-plus-lg"></i>Tambah Item</button>';
}
?>

<div class="page-evm evm-vm" id="evmRoot" data-key="vm-<?= $event['id'] ?>" data-unit="item">

<?= view('partials/evm_head', ['event' => $event, 'module' => 'vm', 'completion' => $completion, 'canEdit' => $canEdit,
    'title' => 'Dekorasi & Visual Merchandising', 'icon' => 'bi-palette-fill', 'actions' => $actions]) ?>

<!-- Strip KPI -->
<div class="ev-kpis">
    <div class="ev-kpi">
        <div class="ev-kpi-l">Item Dekorasi</div>
        <div class="ev-kpi-v"><?= count($items) ?></div>
        <div class="ev-kpi-s"><?= $nNoReal ?> belum ada realisasi</div>
    </div>
    <div class="ev-kpi t-warn">
        <div class="ev-kpi-l">Total Budget</div>
        <div class="ev-kpi-v ev-c-warn"><?= evRp($totalBudgetVM) ?></div>
        <div class="ev-kpi-s">seluruh item dekorasi</div>
    </div>
    <div class="ev-kpi t-cyan">
        <div class="ev-kpi-l">Total Realisasi</div>
        <div class="ev-kpi-v ev-c-cyan"><?= evRp($totalRealisasiVM) ?></div>
        <div class="ev-kpi-s"><?= $totalBudgetVM > 0 ? $totalPct . '% dari budget' : 'belum ada budget' ?></div>
        <?php if ($totalBudgetVM > 0): ?><div class="ev-bar ev-bar-<?= $barAll ?>"><i style="width:<?= $totalPct ?>%"></i></div><?php endif; ?>
    </div>
    <div class="ev-kpi <?= $sisaVM < 0 ? 't-bad' : 't-ok' ?>">
        <div class="ev-kpi-l"><?= $sisaVM < 0 ? 'Melebihi Budget' : 'Sisa Budget' ?></div>
        <div class="ev-kpi-v <?= $sisaVM < 0 ? 'ev-c-bad' : 'ev-c-ok' ?>"><?= evRp(abs($sisaVM)) ?></div>
        <div class="ev-kpi-s"><?= $nDone ?> item sudah mencapai budget</div>
    </div>
    <div class="ev-kpi <?= $nOver > 0 ? 't-bad' : 't-ok' ?>">
        <div class="ev-kpi-l">Lewat Deadline</div>
        <div class="ev-kpi-v <?= $nOver > 0 ? 'ev-c-bad' : '' ?>"><?= $nOver ?></div>
        <div class="ev-kpi-s">item melewati tanggal deadline</div>
    </div>
</div>

<?php if (empty($items)): ?>
<div class="ev-empty">
    <i class="bi bi-palette"></i>
    <h5>Belum ada item dekorasi untuk event ini</h5>
    <p>Catat backdrop, signage, dan display beserta budget, lalu input realisasi biayanya di sini.</p>
    <?php if ($canEdit): ?>
    <button class="ev-btn ev-btn-primary" data-bs-toggle="modal" data-bs-target="#addItemModal"><i class="bi bi-plus-lg"></i>Tambah Item Pertama</button>
    <?php endif; ?>
</div>
<?php else: ?>

<div class="ev-tools">
    <div class="ev-search">
        <i class="bi bi-search"></i>
        <input type="search" id="evQ" placeholder="Cari item atau referensi…" autocomplete="off" aria-label="Cari item dekorasi">
    </div>
    <select class="ev-select" data-ev-filter="state" aria-label="Filter progres">
        <option value="">Semua progres</option>
        <option value="belum">Belum ada realisasi</option>
        <option value="jalan">Berjalan</option>
        <option value="penuh">Mencapai budget</option>
        <option value="over">Melebihi budget</option>
    </select>
    <select class="ev-select" data-ev-filter="dl" aria-label="Filter deadline">
        <option value="">Semua deadline</option>
        <option value="lewat">Lewat deadline</option>
        <option value="ada">Punya deadline</option>
        <option value="tanpa">Tanpa deadline</option>
    </select>
    <select class="ev-select" id="evSort" aria-label="Urutkan">
        <option value="" data-dir="asc">Urutan: input</option>
        <option value="name" data-type="str" data-dir="asc">Nama A–Z</option>
        <option value="budget" data-dir="desc">Budget terbesar</option>
        <option value="sisa" data-dir="desc">Sisa terbesar</option>
        <option value="dlts" data-dir="asc">Deadline terdekat</option>
    </select>
    <div class="ev-tools-end">
        <span class="ev-count" id="evCount" aria-live="polite"></span>
        <button type="button" class="ev-btn ev-btn-sm" id="evExpand" title="Buka semua item"><i class="bi bi-arrows-expand"></i><span class="d-none d-md-inline">Buka semua</span></button>
        <button type="button" class="ev-btn ev-btn-sm" id="evCollapse" title="Tutup semua item"><i class="bi bi-arrows-collapse"></i><span class="d-none d-md-inline">Tutup semua</span></button>
    </div>
</div>

<div class="ev-empty ev-empty-filter" id="evNoMatch" hidden>
    <i class="bi bi-search"></i>
    <h5>Tidak ada item yang cocok</h5>
    <p>Ubah kata kunci atau filter.</p>
    <button type="button" class="ev-btn" id="evReset"><i class="bi bi-x-circle"></i>Reset filter</button>
</div>

<section class="ev-group">
<div class="ev-list">
<?php foreach ($items as $n => $it):
    $iid       = $it['id'];
    $rData     = $realisasi[$iid] ?? ['total' => 0, 'entries' => []];
    $rTotal    = $rData['total'];
    $budget    = (int) $it['budget'];
    $pct       = $budget > 0 ? min(100, round($rTotal / $budget * 100)) : 0;
    $barColor  = $pct >= 100 ? 'ok' : ($pct >= 50 ? 'mid' : 'low');
    $sisa      = $budget - $rTotal;
    $state     = empty($rData['entries']) ? 'belum' : ($budget > 0 && $rTotal > $budget ? 'over' : ($budget > 0 && $rTotal >= $budget ? 'penuh' : 'jalan'));
    $stLabel   = ['belum' => 'Belum ada realisasi', 'jalan' => 'Berjalan', 'penuh' => 'Mencapai budget', 'over' => 'Melebihi budget'][$state];
    $stChip    = ['belum' => 'mute', 'jalan' => 'warn', 'penuh' => 'ok', 'over' => 'bad'][$state];
    if ($state === 'over') $barColor = 'low';
    $dl        = $it['tanggal_deadline'] ?: '';
    $overDl    = $dl && strtotime($dl) < $today;
    $dlDiff    = $dl ? (int) ((strtotime($dl) - $today) / 86400) : null;
    $search    = mb_strtolower($it['nama_item'] . ' ' . ($it['deskripsi_referensi'] ?? '') . ' ' . ($it['catatan'] ?? ''));
?>
<article class="ev-card<?= $state === 'over' ? ' is-bad' : ($state === 'penuh' ? ' is-ok' : '') ?>" id="item-<?= $iid ?>"
         data-name="<?= esc($it['nama_item'], 'attr') ?>" data-search="<?= esc($search, 'attr') ?>"
         data-state="<?= $state ?>" data-dl="<?= $overDl ? 'lewat' : ($dl ? 'ada' : 'tanpa') ?>"
         data-budget="<?= $budget ?>" data-sisa="<?= $sisa ?>" data-dlts="<?= $dl ? strtotime($dl) : '' ?>" data-order="<?= $n ?>">
    <div class="ev-card-head" data-ev-toggle>
        <button type="button" class="ev-chev" aria-expanded="false" aria-controls="body-<?= $iid ?>" aria-label="Buka atau tutup rincian item"><i class="bi bi-chevron-right"></i></button>
        <div class="min-w-0">
            <div class="ev-card-name">
                <span class="ev-card-t"><?= esc($it['nama_item']) ?></span>
                <span class="ev-chip ev-chip-<?= $stChip ?>"><?= $stLabel ?></span>
                <?php if ($overDl): ?><span class="ev-chip ev-chip-bad"><i class="bi bi-exclamation-triangle-fill"></i>Lewat deadline</span><?php endif; ?>
            </div>
            <div class="ev-card-meta">
                <?php if ($dl): ?><span class="<?= $overDl ? 'ev-c-bad' : '' ?>"><i class="bi bi-calendar-event"></i>Deadline <?= date('d M Y', strtotime($dl)) ?><?php if (! $overDl && $dlDiff <= 7): ?> (<?= $dlDiff === 0 ? 'hari ini' : $dlDiff . ' hari lagi' ?>)<?php endif; ?></span><?php endif; ?>
                <span><i class="bi bi-receipt"></i><?= count($rData['entries']) ?> realisasi</span>
                <?php if ($it['catatan']): ?><span><i class="bi bi-sticky"></i><?= esc($it['catatan']) ?></span><?php endif; ?>
            </div>
        </div>
        <div class="ev-fig">
            <div class="ev-fig-row">
                <div><span class="ev-fig-l">Budget</span><b><?= evRp($budget) ?></b></div>
                <div><span class="ev-fig-l">Realisasi</span><b class="ev-c-cyan"><?= evRp($rTotal) ?></b></div>
                <div><span class="ev-fig-l"><?= $sisa < 0 ? 'Lebih' : 'Sisa' ?></span><b class="<?= $sisa < 0 ? 'ev-c-bad' : ($sisa > 0 ? 'ev-c-warn' : 'ev-c-ok') ?>"><?= evRp(abs($sisa)) ?></b></div>
            </div>
            <div class="ev-fig-bar">
                <div class="ev-bar ev-bar-<?= $barColor ?>"><i style="width:<?= $pct ?>%"></i></div>
                <span class="ev-pct"><?= $budget > 0 ? $pct . '%' : '—' ?></span>
            </div>
        </div>
        <div class="ev-card-act" data-ev-notoggle>
            <?php if ($canEdit): ?>
            <button type="button" class="ev-btn ev-btn-sm ev-btn-ok" data-ev-open="real-<?= $iid ?>" title="Input realisasi biaya"><i class="bi bi-plus-lg"></i><span class="d-none d-sm-inline">Realisasi</span></button>
            <div class="dropdown">
                <button class="ev-iconbtn" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Aksi item"><i class="bi bi-three-dots-vertical"></i></button>
                <ul class="dropdown-menu dropdown-menu-end ev-menu">
                    <li>
                        <button type="button" class="dropdown-item edit-btn"
                            data-id="<?= $iid ?>"
                            data-nama="<?= esc($it['nama_item']) ?>"
                            data-deskripsi="<?= esc($it['deskripsi_referensi']) ?>"
                            data-budget="<?= number_format($budget, 0, ',', '.') ?>"
                            data-catatan="<?= esc($it['catatan']) ?>"
                            data-deadline="<?= esc($it['tanggal_deadline'] ?? '') ?>"><i class="bi bi-pencil"></i>Edit item</button>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="<?= base_url('events/' . $event['id'] . '/vm/' . $iid . '/delete') ?>" onsubmit="return confirm('Hapus item ini?')">
                            <?= csrf_field() ?>
                            <button class="dropdown-item text-danger"><i class="bi bi-trash"></i>Hapus item</button>
                        </form>
                    </li>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="ev-card-body" id="body-<?= $iid ?>" hidden>
        <?php if ($it['deskripsi_referensi']): ?>
        <div class="ev-pad"><p class="ev-desc"><?= esc($it['deskripsi_referensi']) ?></p></div>
        <?php endif; ?>
        <div class="ev-sec s-cyan">
            <div class="ev-sec-h">
                <span class="ev-sec-t"><i class="bi bi-receipt"></i>Realisasi biaya
                    <?php if ($rTotal > 0): ?><span class="ev-chip ev-chip-mute"><?= evRp($rTotal) ?></span><?php endif; ?></span>
                <?php if ($canEdit): ?>
                <button type="button" class="ev-btn ev-btn-xs ev-btn-soft" data-ev-panel="real-<?= $iid ?>" aria-expanded="false"><i class="bi bi-plus-lg"></i>Input realisasi</button>
                <?php endif; ?>
            </div>
            <?php if ($canEdit): ?>
            <div class="ev-panel" id="real-<?= $iid ?>" hidden>
                <form method="POST" action="<?= base_url('events/' . $event['id'] . '/vm/' . $iid . '/realisasi/add') ?>" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <div class="row g-2 align-items-end">
                        <div class="col-md-2 col-6">
                            <label class="form-label">Tanggal</label>
                            <input type="date" name="tanggal" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-3 col-6">
                            <label class="form-label">Jumlah (Rp)</label>
                            <input type="text" name="jumlah" class="form-control form-control-sm currency-input" value="0" required>
                        </div>
                        <div class="col-md">
                            <label class="form-label">Keterangan</label>
                            <input type="text" name="catatan" class="form-control form-control-sm" placeholder="Opsional">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Foto bukti</label>
                            <input type="file" name="foto" class="form-control form-control-sm" accept="image/*">
                        </div>
                        <div class="col-md-auto d-flex gap-2">
                            <button type="submit" class="ev-btn ev-btn-sm ev-btn-primary"><i class="bi bi-check-lg"></i>Simpan</button>
                            <button type="button" class="ev-btn ev-btn-sm" data-ev-panel="real-<?= $iid ?>">Batal</button>
                        </div>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <?php if (! empty($rData['entries'])): ?>
            <div class="ev-tbl-wrap">
            <table class="ev-tbl ev-stack">
                <thead><tr><th>Tanggal</th><th>Keterangan</th><th>Foto</th><th class="ev-num">Jumlah</th><?php if ($canEdit): ?><th></th><?php endif; ?></tr></thead>
                <tbody>
                <?php foreach ($rData['entries'] as $re): ?>
                <tr>
                    <td class="ev-nowrap" data-l="Tanggal"><?= date('d M Y', strtotime($re['tanggal'])) ?></td>
                    <td data-l="Keterangan"><?= esc($re['catatan'] ?: '—') ?></td>
                    <td data-l="Foto">
                        <?php if (! empty($re['foto_file_name'])): $furl = base_url($vmBase . $re['foto_file_name']); ?>
                        <a class="ev-thumb" href="<?= $furl ?>" target="_blank" title="Foto bukti"><img src="<?= $furl ?>" alt="Foto bukti" loading="lazy"></a>
                        <?php else: ?><span class="ev-c-mute">—</span><?php endif; ?>
                    </td>
                    <td class="ev-num" data-l="Jumlah"><?= evRp($re['jumlah']) ?></td>
                    <?php if ($canEdit): ?>
                    <td class="ev-act-cell">
                        <form method="POST" action="<?= base_url('events/' . $event['id'] . '/vm/' . $iid . '/realisasi/' . $re['id'] . '/delete') ?>" class="ev-form-inline" onsubmit="return confirm('Hapus entri ini?')">
                            <?= csrf_field() ?>
                            <button class="ev-iconbtn ev-iconbtn-sm ev-iconbtn-bad" title="Hapus entri" aria-label="Hapus entri realisasi"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot><tr><td colspan="3">Total realisasi</td><td class="ev-num"><?= evRp($rTotal) ?></td><?php if ($canEdit): ?><td></td><?php endif; ?></tr></tfoot>
            </table>
            </div>
            <?php else: ?>
            <div class="ev-empty-sm">Belum ada realisasi.</div>
            <?php endif; ?>
        </div>
    </div>
</article>
<?php endforeach; ?>
</div>
</section>

<div class="ev-foot">
    <span class="ev-foot-l"><?= count($items) ?> item</span>
    <span class="ev-foot-v ev-c-warn"><?= evRp($totalBudgetVM) ?><small>total budget</small></span>
    <span class="ev-foot-v ev-c-cyan"><?= evRp($totalRealisasiVM) ?><small>realisasi (<?= $totalPct ?>%)</small></span>
</div>
<?php endif; ?>

</div><!-- /page-evm -->

<?php if ($canEdit): ?>
<div class="modal fade" id="addItemModal" tabindex="-1">
<div class="modal-dialog"><div class="modal-content">
<form method="POST" action="<?= base_url('events/'.$event['id'].'/vm/add') ?>">
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
<?php if ($canEdit): ?>
document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('editItemForm').action = '<?= base_url('events/'.$event['id'].'/vm/') ?>' + this.dataset.id + '/edit';
        document.getElementById('editNama').value     = this.dataset.nama;
        document.getElementById('editDesk').value     = this.dataset.deskripsi;
        document.getElementById('editBudget').value   = this.dataset.budget;
        document.getElementById('editDeadline').value = this.dataset.deadline;
        document.getElementById('editCatatan').value  = this.dataset.catatan;
        new bootstrap.Modal(document.getElementById('editItemModal')).show();
    });
});
<?php endif; ?>
document.querySelectorAll('.currency-input').forEach(inp => {
    inp.addEventListener('input', function() { let n = parseInt(this.value.replace(/[^0-9]/g,''))||0; this.value=n.toLocaleString('id-ID'); });
});
</script>
<script src="<?= base_url('js/events-modul.js') ?>?v=<?= @filemtime(FCPATH . 'js/events-modul.js') ?: 1 ?>"></script>
<?= $this->endSection() ?>
