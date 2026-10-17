<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/stock-fisik.css') ?>?v=<?= @filemtime(FCPATH . 'css/stock-fisik.css') ?: 1 ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?= $this->include('stock/_fn') ?>
<?php
$canEdit = $canEdit ?? false;
$cnt = []; $attn = 0; $sumS = $sumT = $sumR = $sumB = $sumN = 0;
foreach ($batches as $i => $b) {
    $st = sf_status_voucher($b); $batches[$i]['_st'] = $st;
    $cnt[$st['key']] = ($cnt[$st['key']] ?? 0) + 1; if (sf_attn($st['key'])) $attn++;
    $sumS += (int) $b['sisa_kode']; $sumT += (int) $b['total_kode']; $sumR += $st['reserved']; $sumB += $st['bebas'];
    $sumN += (int) $b['sisa_kode'] * (int) $b['nilai_voucher'];
}
$kLabel = ['available' => ['Tersedia', 'is-ok'], 'assigned' => ['Terpakai', 'is-info'], 'expired' => ['Kedaluwarsa', 'is-none']];
?>

<div class="page-sf" id="sfVoucherPage">

<?= sf_crumbs([['Summary Stok Fisik', sf_summary_url()], ['Stock Voucher Fisik', null]]) ?>
<?php $pq = sf_period_qs(); ?>
<div class="sf-head">
    <div class="min-w-0">
        <h4 class="sf-title"><i class="bi bi-ticket-perforated"></i>Stock Voucher Fisik</h4>
        <div class="sf-sub">Kelola batch &amp; kode voucher: import kode, distribusi manual, dan pantau kedaluwarsa. Klik baris untuk melihat daftar kode.</div>
    </div>
    <div class="sf-head-actions">
        <a href="<?= sf_summary_url() ?>" class="btn btn-sm btn-outline-secondary sf-backbtn"><i class="bi bi-arrow-left"></i>Kembali ke Summary Stok</a>
        <?php if ($canEdit): ?>
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahBatch"><i class="bi bi-plus-lg me-1"></i>Tambah Batch</button>
        <?php endif; ?>
    </div>
</div>

<div class="sf-kpis">
    <div class="sf-kpi t-info">
        <div class="sf-kpi-l"><i class="bi bi-collection"></i>Batch Voucher</div>
        <div class="sf-kpi-v"><?= count($batches) ?></div>
        <div class="sf-kpi-s"><?= sf_n($sumT) ?> kode total · <?= sf_n($sumT - $sumS) ?> terpakai</div>
    </div>
    <div class="sf-kpi t-ok">
        <div class="sf-kpi-l"><i class="bi bi-ticket-perforated"></i>Kode Tersedia</div>
        <div class="sf-kpi-v"><?= sf_n($sumS) ?></div>
        <div class="sf-kpi-s">reserved <?= sf_n($sumR) ?> · bebas <?= sf_n($sumB) ?></div>
    </div>
    <div class="sf-kpi t-main">
        <div class="sf-kpi-l"><i class="bi bi-cash-stack"></i>Nilai Stok Tersedia</div>
        <div class="sf-kpi-v"><?= sf_rp($sumN) ?></div>
        <div class="sf-kpi-s">kode tersedia × nilai voucher</div>
    </div>
    <button type="button" class="sf-kpi <?= $attn ? 't-bad' : 't-ok' ?>" data-sf-kpi="attn" data-sf-target="sfVoucherList" title="Tampilkan yang perlu perhatian">
        <div class="sf-kpi-l"><i class="bi bi-exclamation-triangle"></i>Perlu Aksi</div>
        <div class="sf-kpi-v"><?= $attn ?></div>
        <div class="sf-kpi-s"><?= sf_kc('kedaluwarsa', $cnt['exp'] ?? 0, 'bad') ?><?= sf_kc('segera exp', $cnt['soon'] ?? 0, 'warn') ?><?= sf_kc('kurang', $cnt['short'] ?? 0, 'bad') ?><?= sf_kc('menipis', $cnt['low'] ?? 0, 'warn') ?></div>
    </button>
</div>

<div class="card sf-panel" id="sfVoucherList" data-sf-list data-sf-url data-sf-default-sort="name:asc">
    <div class="sf-panel-h">
        <div class="sf-panel-t">Daftar Batch <small data-sf-count></small></div>
    </div>
    <div class="sf-tools">
        <label class="sf-search"><i class="bi bi-search"></i>
            <input type="search" class="form-control form-control-sm" data-sf-search placeholder="Cari nama voucher…" aria-label="Cari batch" autocomplete="off">
            <button type="button" class="sf-clear" aria-label="Hapus pencarian"><i class="bi bi-x-lg"></i></button></label>
        <div class="sf-chips"><?= sf_chips($cnt, count($batches)) ?></div>
        <select class="form-select form-select-sm sf-spacer" data-sf-sort aria-label="Urutkan">
            <option value="name:asc" data-type="text">Urut: Nama A–Z</option>
            <option value="sev:asc" data-type="num">Urut: Perlu perhatian dulu</option>
            <option value="exp:asc" data-type="text">Urut: Kedaluwarsa terdekat</option>
            <option value="tersedia:asc" data-type="num">Urut: Tersedia terkecil</option>
            <option value="nilai:desc" data-type="num">Urut: Nilai stok terbesar</option>
        </select>
    </div>

    <div class="sf-scroll">
    <table class="sf-tbl sf-cards">
        <thead><tr>
            <th data-sort="name" data-type="text">Voucher</th>
            <th data-sort="exp" data-type="text">Kedaluwarsa</th>
            <th data-sort="tersedia" data-type="num">Tersedia &amp; Status</th>
            <th class="c" data-sort="reserved" data-type="num" data-first="desc">Reserved</th>
            <th class="c" data-sort="bebas" data-type="num">Bebas</th>
            <th class="r" data-sort="nilai" data-type="num" data-first="desc">Nilai Stok</th>
            <th class="r">Aksi</th>
        </tr></thead>

        <?php foreach ($batches as $batch): $st = $batch['_st']; $m = sf_status_meta()[$st['key']]; $bid = (int) $batch['id'];
            $nilaiStok = (int) $batch['sisa_kode'] * (int) $batch['nilai_voucher'];
            $kc = ['available' => 0, 'assigned' => 0, 'expired' => 0];
            foreach ($batch['kodes'] as $k) { $kc[$k['status']] = ($kc[$k['status']] ?? 0) + 1; }
        ?>
        <tbody class="sf-item" id="batch-<?= $bid ?>"<?= sf_attrs([
            'data-q' => mb_strtolower($batch['nama_voucher'] . ' ' . ($batch['catatan'] ?? '') . ' ' . $m[0]), 'data-status' => $st['key'], 'data-attn' => sf_attn($st['key']) ? 1 : 0,
            'data-v-name' => mb_strtolower($batch['nama_voucher']), 'data-v-sev' => $m[3], 'data-v-exp' => $batch['expired_date'] ?: '9999-12-31',
            'data-v-tersedia' => $batch['sisa_kode'], 'data-v-reserved' => $st['reserved'], 'data-v-bebas' => $st['bebas'], 'data-v-nilai' => $nilaiStok,
            'data-sum-tersedia' => $batch['sisa_kode'], 'data-sum-reserved' => $st['reserved'], 'data-sum-bebas' => $st['bebas'], 'data-sum-nilai' => $nilaiStok,
        ]) ?>>
        <tr class="sf-row sf-open" data-sf-toggle>
            <td class="sf-t">
                <div class="d-flex align-items-start">
                    <button type="button" class="btn btn-sm p-0 border-0" data-sf-toggle aria-expanded="false" aria-label="Buka daftar kode" style="background:none;color:inherit"><i class="bi bi-chevron-right sf-chev"></i></button>
                    <div class="min-w-0">
                        <div class="sf-name"><?= esc($batch['nama_voucher']) ?></div>
                        <div class="sf-note">Rp <?= sf_n($batch['nilai_voucher']) ?> / voucher<?= $batch['catatan'] ? ' · ' . esc($batch['catatan']) : '' ?></div>
                    </div>
                </div>
            </td>
            <td data-label="Kedaluwarsa">
                <?php if ($batch['expired_date']):
                    $hari = $st['hari'];
                    $hariTxt = $hari < 0 ? 'lewat ' . abs($hari) . ' hari' : ($hari === 0 ? 'hari ini' : ($hari <= 30 ? $hari . ' hari lagi' : '')); ?>
                    <div class="<?= ($hari < 0 && $batch['sisa_kode'] > 0) ? 'sf-neg' : '' ?>"><?= date('d M Y', strtotime($batch['expired_date'])) ?></div>
                    <?php if ($hariTxt): ?><div class="sf-note<?= ($hari <= 14 && $batch['sisa_kode'] > 0) ? ' sf-neg' : '' ?>"><?= $hariTxt ?></div><?php endif; ?>
                <?php else: ?><span class="sf-muted">Tanpa batas</span><?php endif; ?>
            </td>
            <td class="sf-stok" data-label="Tersedia">
                <div class="sf-stok-n"><strong><?= sf_n($batch['sisa_kode']) ?></strong>
                    <?= sf_badge($st['key'], $st['short'] ? 'Reserved melebihi stok sebesar ' . $st['short'] : '') ?>
                    <span>dari <?= sf_n($batch['total_kode']) ?> kode</span></div>
                <?= sf_bar($st) ?>
            </td>
            <td class="c" data-label="Reserved">
                <?php if ($st['reserved'] > 0): ?><span class="sf-badge is-warn"><?= sf_n($st['reserved']) ?></span><?php else: ?><span class="sf-muted">—</span><?php endif; ?>
            </td>
            <td class="c" data-label="Bebas"><span class="sf-badge <?= $st['bebas'] > 0 ? 'is-info' : 'is-none' ?>"><?= sf_n($st['bebas']) ?></span></td>
            <td class="r fw-semibold" data-label="Nilai Stok"><?= sf_rp($nilaiStok) ?></td>
            <td class="sf-act">
                <div class="sf-actions">
                <?php if ($canEdit): ?>
                    <button type="button" class="btn btn-sm btn-success" onclick="openImportKode(<?= $bid ?>, <?= esc(json_encode($batch['nama_voucher']), 'attr') ?>)" title="Import kode"><i class="bi bi-upload"></i>Import Kode</button>
                <?php endif; ?>
                    <div class="dropdown">
                        <button type="button" class="btn btn-sm btn-outline-secondary sf-more" data-bs-toggle="dropdown" data-sf-dd aria-expanded="false" aria-label="Aksi lain"><i class="bi bi-three-dots"></i></button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><button type="button" class="dropdown-item" onclick="openKode(<?= $bid ?>)"><i class="bi bi-list-ul"></i>Daftar kode</button></li>
                            <li><a class="dropdown-item" href="<?= base_url('stock/voucher/' . $bid . '/kartu?ret=voucher' . $pq) ?>"><i class="bi bi-journal-text"></i>Kartu stok</a></li>
                        <?php if ($canEdit): ?>
                            <li><button type="button" class="dropdown-item" onclick="openEditBatch(<?= htmlspecialchars(json_encode(['id' => $bid, 'nama_voucher' => $batch['nama_voucher'], 'nilai_voucher' => $batch['nilai_voucher'], 'expired_date' => $batch['expired_date'], 'catatan' => $batch['catatan']])) ?>)"><i class="bi bi-pencil"></i>Edit batch</button></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><form method="POST" action="<?= base_url('stock/voucher/' . $bid . '/delete') ?>" onsubmit="return confirm('Hapus batch ini dan semua kode-nya?')">
                                <?= csrf_field() ?>
                                <button class="dropdown-item text-danger"><i class="bi bi-trash"></i>Hapus batch</button></form></li>
                        <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </td>
        </tr>
        <tr class="sf-detail">
            <td colspan="7">
            <div class="sf-kode-box" data-sf-list>
                <?php if (empty($batch['kodes'])): ?>
                <div class="sf-empty" style="padding:1.2rem"><i class="bi bi-ticket-perforated"></i>Batch ini belum punya kode.
                    <?php if ($canEdit): ?><br><button type="button" class="btn btn-sm btn-success" onclick="openImportKode(<?= $bid ?>, <?= esc(json_encode($batch['nama_voucher']), 'attr') ?>)"><i class="bi bi-upload me-1"></i>Import kode</button><?php endif; ?></div>
                <?php else: ?>
                <div class="sf-kode-tools">
                    <label class="sf-search"><i class="bi bi-search"></i>
                        <input type="search" class="form-control form-control-sm" data-sf-search placeholder="Cari kode / penerima / program…" aria-label="Cari kode" autocomplete="off">
                        <button type="button" class="sf-clear" aria-label="Hapus pencarian"><i class="bi bi-x-lg"></i></button></label>
                    <div class="sf-chips">
                        <button type="button" class="sf-chip is-on" data-sf-filter="all">Semua <b><?= count($batch['kodes']) ?></b></button>
                        <?php foreach (['available', 'assigned', 'expired'] as $ks): if (empty($kc[$ks])) continue; ?>
                        <button type="button" class="sf-chip" data-sf-filter="kstatus:<?= $ks ?>"><?= $kLabel[$ks][0] ?> <b><?= $kc[$ks] ?></b></button>
                        <?php endforeach; ?>
                    </div>
                    <span class="sf-count ms-auto" data-sf-count></span>
                </div>
                <div class="sf-kode-scroll">
                <table class="sf-tbl sf-kode">
                    <thead><tr><th>Kode</th><th>Status</th><th>Penerima</th><th>Tgl Assign</th><th>Program</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($batch['kodes'] as $kode):
                        $pn = null; $pLabel = '';
                        if ($kode['program_type'] === 'manual') { $pLabel = 'Manual'; }
                        elseif ($kode['program_type']) { $pn = $progNames[$kode['program_type'] . '_' . $kode['program_id']] ?? null; }
                        $by = ($kode['status'] === 'assigned' && ! empty($assignedBy[$kode['id']])) ? $assignedBy[$kode['id']] : '';
                        $ks = $kLabel[$kode['status']] ?? [$kode['status'], 'is-none'];
                    ?>
                    <tr class="sf-item"<?= sf_attrs(['data-kstatus' => $kode['status'], 'data-q' => mb_strtolower($kode['kode'] . ' ' . ($kode['nama_penerima'] ?? '') . ' ' . ($pn ?? $pLabel) . ' ' . $by . ' ' . $ks[0])]) ?>>
                        <td class="sf-mono fw-semibold"><?= esc($kode['kode']) ?></td>
                        <td class="k-m"><span class="sf-badge <?= $ks[1] ?>"><?= esc($ks[0]) ?></span></td>
                        <td class="k-m<?= ($kode['nama_penerima'] ?? '') === '' && ! $by ? ' k-hide' : '' ?>">
                            <?= esc($kode['nama_penerima'] ?? '—') ?>
                            <?php if ($by): ?><div class="sf-note"><i class="bi bi-person me-1"></i>oleh <?= esc($by) ?></div><?php endif; ?>
                        </td>
                        <td class="k-m<?= $kode['assigned_at'] ? '' : ' k-hide' ?>"><?= $kode['assigned_at'] ? date('d M Y', strtotime($kode['assigned_at'])) : '—' ?></td>
                        <td class="k-m<?= $kode['program_type'] ? '' : ' k-hide' ?>">
                            <?php if ($kode['program_type'] === 'manual'): ?>
                            <span class="sf-badge is-none">Manual</span>
                            <?php elseif ($kode['program_type']): ?>
                                <?php if ($pn): ?>
                                <span class="fw-medium"><?= esc($pn) ?></span>
                                <span class="sf-badge <?= $kode['program_type'] === 'event' ? 'is-warn' : 'is-info' ?>" style="font-size:.6rem"><?= $kode['program_type'] === 'event' ? 'Event' : 'Standalone' ?></span>
                                <?php else: ?>
                                <?= esc(($kode['program_type'] === 'event' ? 'Event' : 'Program') . ' #' . $kode['program_id']) ?>
                                <?php endif; ?>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                        <td class="k-act r">
                            <?php if ($kode['status'] === 'available' && $canEdit): ?>
                            <div class="sf-actions">
                                <button type="button" class="btn btn-sm btn-outline-success py-0 px-2"
                                    onclick="openDistribusi(<?= $bid ?>, <?= (int) $kode['id'] ?>, <?= esc(json_encode($kode['kode']), 'attr') ?>)"
                                    title="Distribusi manual"><i class="bi bi-send"></i><span class="d-none d-md-inline">Distribusi</span></button>
                                <form method="POST" action="<?= base_url('stock/voucher/' . $bid . '/kode/' . $kode['id'] . '/delete') ?>" class="d-inline" onsubmit="return confirm('Hapus kode ini?')">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-outline-danger py-0 px-2" title="Hapus kode"><i class="bi bi-x-lg"></i></button>
                                </form>
                            </div>
                            <?php elseif (($canDeassign ?? false) && $kode['status'] === 'assigned' && $kode['program_type'] === 'manual'): ?>
                            <form method="POST" action="<?= base_url('stock/voucher/' . $bid . '/kode/' . $kode['id'] . '/deassign') ?>" class="d-inline" onsubmit="return confirm('Batalkan distribusi manual kode ini? Kode kembali ke stok tersedia agar bisa dialokasikan via program.')">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-warning py-0 px-2" title="Batalkan distribusi manual"><i class="bi bi-arrow-counterclockwise me-1"></i>Batalkan</button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="sf-anchor" hidden></tr>
                    <tr data-sf-empty hidden><td colspan="6"><div class="sf-empty" style="padding:1rem"><i class="bi bi-search"></i>Tidak ada kode yang cocok.</div></td></tr>
                    </tbody>
                </table>
                </div>
                <?php endif; ?>
            </div>
            </td>
        </tr>
        </tbody>
        <?php endforeach; ?>

        <tbody class="sf-anchor"><tr data-sf-empty <?= empty($batches) ? '' : 'hidden' ?>><td colspan="7"><div class="sf-empty"><i class="bi bi-<?= empty($batches) ? 'ticket-perforated' : 'search' ?>"></i>
            <?= empty($batches) ? 'Belum ada batch voucher.' : 'Tidak ada batch yang cocok dengan pencarian/filter.' ?>
            <?php if (empty($batches) && $canEdit): ?><br><button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahBatch"><i class="bi bi-plus-lg me-1"></i>Tambah batch pertama</button><?php endif; ?>
        </div></td></tr></tbody>
        <tfoot><tr>
            <td class="sf-t">Total (yang ditampilkan)</td>
            <td class="sf-hide-m"></td>
            <td data-label="Tersedia"><span data-sf-sum="tersedia"><?= sf_n($sumS) ?></span> kode</td>
            <td class="c" data-label="Reserved"><span data-sf-sum="reserved"><?= sf_n($sumR) ?></span></td>
            <td class="c" data-label="Bebas"><span data-sf-sum="bebas"><?= sf_n($sumB) ?></span></td>
            <td class="r" data-label="Nilai Stok"><span data-sf-sum="nilai" data-fmt="rp"><?= sf_rp($sumN) ?></span></td>
            <td class="sf-hide-m"></td>
        </tr></tfoot>
    </table>
    </div>
    <div class="sf-legend"><span><i style="background:var(--sf-ok)"></i>kode bebas</span><span><i style="background:repeating-linear-gradient(135deg,#fbbf24 0 3px,#fbbf2488 3px 6px)"></i>reserved program</span><span>Segera Exp = kedaluwarsa ≤ 14 hari · Menipis = bebas ≤ 20% dari total kode · Kurang = reserved melebihi stok</span></div>
</div>

</div>

<?php if ($canEdit): ?>
<!-- Modal Tambah Batch -->
<div class="modal fade" id="modalTambahBatch" tabindex="-1">
<div class="modal-dialog">
<div class="modal-content">
<form method="POST" action="<?= base_url('stock/voucher/store') ?>">
<?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title">Tambah Batch Voucher</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="mb-3">
        <label class="form-label small fw-semibold">Nama Voucher <span class="text-danger">*</span></label>
        <input type="text" name="nama_voucher" class="form-control" required>
    </div>
    <div class="row g-2">
        <div class="col-6">
            <label class="form-label small fw-semibold">Nilai Voucher (Rp)</label>
            <input type="text" name="nilai_voucher" class="form-control nominal" value="0">
        </div>
        <div class="col-6">
            <label class="form-label small fw-semibold">Expired Date</label>
            <input type="date" name="expired_date" class="form-control">
        </div>
    </div>
    <div class="mb-3 mt-2">
        <label class="form-label small fw-semibold">Catatan</label>
        <textarea name="catatan" class="form-control" rows="2"></textarea>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
    <button type="submit" class="btn btn-primary">Simpan</button>
</div>
</form>
</div>
</div>
</div>

<!-- Modal Edit Batch -->
<div class="modal fade" id="modalEditBatch" tabindex="-1">
<div class="modal-dialog">
<div class="modal-content">
<form method="POST" id="formEditBatch" action="">
<?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title">Edit Batch Voucher</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="mb-3">
        <label class="form-label small fw-semibold">Nama Voucher <span class="text-danger">*</span></label>
        <input type="text" name="nama_voucher" id="edit_nama_voucher" class="form-control" required>
    </div>
    <div class="row g-2">
        <div class="col-6">
            <label class="form-label small fw-semibold">Nilai Voucher (Rp)</label>
            <input type="text" name="nilai_voucher" id="edit_nilai_voucher" class="form-control nominal">
        </div>
        <div class="col-6">
            <label class="form-label small fw-semibold">Expired Date</label>
            <input type="date" name="expired_date" id="edit_expired_date" class="form-control">
        </div>
    </div>
    <div class="mb-3 mt-2">
        <label class="form-label small fw-semibold">Catatan</label>
        <textarea name="catatan" id="edit_catatan_batch" class="form-control" rows="2"></textarea>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
    <button type="submit" class="btn btn-primary">Simpan</button>
</div>
</form>
</div>
</div>
</div>

<!-- Modal Distribusi Kode -->
<div class="modal fade" id="modalDistribusi" tabindex="-1">
<div class="modal-dialog modal-sm">
<div class="modal-content">
<form method="POST" id="formDistribusi" action="">
<?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title">Distribusi Manual</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <p class="small text-muted mb-2">Kode: <strong id="distribusiKode" class="font-monospace"></strong></p>
    <div class="mb-3">
        <label class="form-label small fw-semibold">Nama Penerima</label>
        <input type="text" name="nama_penerima" class="form-control" placeholder="Opsional">
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
    <button type="submit" class="btn btn-success"><i class="bi bi-send me-1"></i>Distribusikan</button>
</div>
</form>
</div>
</div>
</div>

<!-- Modal Import Kode -->
<div class="modal fade" id="modalImportKode" tabindex="-1">
<div class="modal-dialog">
<div class="modal-content">
<form method="POST" id="formImportKode" action="">
<?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title">Import Kode — <span id="importBatchNama"></span></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <p class="small text-muted">Masukkan kode voucher, satu per baris. Kode duplikat akan diabaikan.</p>
    <textarea name="kodes" class="form-control font-monospace" rows="10" placeholder="KODE001&#10;KODE002&#10;KODE003"></textarea>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
    <button type="submit" class="btn btn-success"><i class="bi bi-upload me-1"></i>Import</button>
</div>
</form>
</div>
</div>
</div>
<?php endif; ?>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/stock-fisik.js') ?>?v=<?= @filemtime(FCPATH . 'js/stock-fisik.js') ?: 1 ?>"></script>
<script>
document.querySelectorAll('.nominal').forEach(inp => {
    inp.addEventListener('input', function() {
        const n = parseInt(this.value.replace(/[^0-9]/g, '')) || 0;
        this.value = n ? n.toLocaleString('id-ID') : '';
    });
});

function openEditBatch(batch) {
    document.getElementById('edit_nama_voucher').value    = batch.nama_voucher;
    document.getElementById('edit_nilai_voucher').value   = Number(batch.nilai_voucher).toLocaleString('id-ID');
    document.getElementById('edit_expired_date').value    = batch.expired_date || '';
    document.getElementById('edit_catatan_batch').value   = batch.catatan || '';
    document.getElementById('formEditBatch').action       = '<?= base_url('stock/voucher/') ?>' + batch.id + '/update';
    new bootstrap.Modal(document.getElementById('modalEditBatch')).show();
}
function openDistribusi(batchId, kodeId, kode) {
    document.getElementById('distribusiKode').textContent = kode;
    document.getElementById('formDistribusi').action = '<?= base_url('stock/voucher/') ?>' + batchId + '/kode/' + kodeId + '/distribute';
    document.querySelector('#formDistribusi [name=nama_penerima]').value = '';
    new bootstrap.Modal(document.getElementById('modalDistribusi')).show();
}
function openImportKode(id, nama) {
    document.getElementById('importBatchNama').textContent = nama;
    document.getElementById('formImportKode').action       = '<?= base_url('stock/voucher/') ?>' + id + '/import-kode';
    document.querySelector('#formImportKode textarea').value = '';
    new bootstrap.Modal(document.getElementById('modalImportKode')).show();
}
function openKode(id) {
    const it = document.getElementById('batch-' + id);
    if (!it) return;
    document.getElementById('sfVoucherList').sfToggle(it, true);
    it.scrollIntoView({ block: 'nearest' });
}
// Kembali dari aksi kode (#batch-N): buka batch yang sama
document.addEventListener('DOMContentLoaded', function () {
    const m = /^#batch-(\d+)$/.exec(location.hash);
    if (m) { openKode(m[1]); const el = document.getElementById('batch-' + m[1]); if (el) el.scrollIntoView({ block: 'center' }); }
});
</script>
<?= $this->endSection() ?>
