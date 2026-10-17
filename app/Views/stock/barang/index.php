<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/stock-fisik.css') ?>?v=<?= @filemtime(FCPATH . 'css/stock-fisik.css') ?: 1 ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?= $this->include('stock/_fn') ?>
<?php
$canEdit = $canEdit ?? false;
$cnt = []; $groups = []; $attn = 0; $sumT = $sumR = $sumB = $sumN = 0;
foreach ($items as $i => $it) {
    $st = sf_status_barang($it); $items[$i]['_st'] = $st; $items[$i]['_g'] = sf_group($it['nama_barang']);
    $cnt[$st['key']] = ($cnt[$st['key']] ?? 0) + 1; if (sf_attn($st['key'])) $attn++;
    $sumT += (int) $it['stok_tersedia']; $sumR += $st['reserved']; $sumB += $st['bebas']; $sumN += (int) $it['stok_tersedia'] * (int) $it['nilai_satuan'];
    $groups[mb_strtolower($items[$i]['_g'])] = $items[$i]['_g'];
}
ksort($groups, SORT_NATURAL);
?>

<div class="page-sf" id="sfBarangPage">

<?= sf_crumbs([['Summary Stok Fisik', sf_summary_url()], ['Stock Hadiah Fisik', null]]) ?>
<?php $pq = sf_period_qs(); ?>
<div class="sf-head">
    <div class="min-w-0">
        <h4 class="sf-title"><i class="bi bi-box-seam"></i>Stock Hadiah Fisik</h4>
        <div class="sf-sub">Kelola barang/hadiah: tambah stok, keluarkan, dan pantau level stok. Klik baris untuk melihat riwayat.</div>
    </div>
    <div class="sf-head-actions">
        <a href="<?= sf_summary_url() ?>" class="btn btn-sm btn-outline-secondary sf-backbtn"><i class="bi bi-arrow-left"></i>Kembali ke Summary Stok</a>
        <a href="<?= base_url('stock/barang/mutasi?ret=barang' . $pq) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left-right me-1"></i>Laporan Mutasi</a>
        <?php if ($canEdit): ?>
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahBarang"><i class="bi bi-plus-lg me-1"></i>Tambah Barang</button>
        <?php endif; ?>
    </div>
</div>

<div class="sf-kpis">
    <div class="sf-kpi t-info">
        <div class="sf-kpi-l"><i class="bi bi-box"></i>Jenis Barang</div>
        <div class="sf-kpi-v"><?= count($items) ?></div>
        <div class="sf-kpi-s"><?= count($groups) ?> kelompok</div>
    </div>
    <div class="sf-kpi t-ok">
        <div class="sf-kpi-l"><i class="bi bi-boxes"></i>Stok Tersedia</div>
        <div class="sf-kpi-v"><?= sf_n($sumT) ?></div>
        <div class="sf-kpi-s">reserved <?= sf_n($sumR) ?> · bebas <?= sf_n($sumB) ?></div>
    </div>
    <div class="sf-kpi t-main">
        <div class="sf-kpi-l"><i class="bi bi-cash-stack"></i>Nilai Stok</div>
        <div class="sf-kpi-v"><?= sf_rp($sumN) ?></div>
        <div class="sf-kpi-s">stok tersedia × nilai satuan</div>
    </div>
    <button type="button" class="sf-kpi <?= $attn ? 't-bad' : 't-ok' ?>" data-sf-kpi="attn" data-sf-target="sfBarangList" title="Tampilkan yang perlu perhatian">
        <div class="sf-kpi-l"><i class="bi bi-exclamation-triangle"></i>Perlu Aksi</div>
        <div class="sf-kpi-v"><?= $attn ?></div>
        <div class="sf-kpi-s"><?= sf_kc('kurang', $cnt['short'] ?? 0, 'bad') ?><?= sf_kc('menipis', $cnt['low'] ?? 0, 'warn') ?><?= sf_kc('habis', $cnt['out'] ?? 0, 'none') ?></div>
    </button>
</div>

<div class="card sf-panel" id="sfBarangList" data-sf-list data-sf-url data-sf-default-sort="name:asc">
    <div class="sf-panel-h">
        <div class="sf-panel-t">Daftar Barang <small data-sf-count></small></div>
    </div>
    <div class="sf-tools">
        <label class="sf-search"><i class="bi bi-search"></i>
            <input type="search" class="form-control form-control-sm" data-sf-search placeholder="Cari nama barang / catatan…" aria-label="Cari barang" autocomplete="off">
            <button type="button" class="sf-clear" aria-label="Hapus pencarian"><i class="bi bi-x-lg"></i></button></label>
        <div class="sf-chips"><?= sf_chips($cnt, count($items)) ?></div>
        <?php if (count($groups) > 1): ?>
        <select class="form-select form-select-sm" data-sf-group aria-label="Kelompok">
            <option value="">Semua kelompok</option>
            <?php foreach ($groups as $gk => $gl): ?><option value="<?= esc($gk) ?>"><?= esc($gl) ?></option><?php endforeach; ?>
        </select>
        <?php endif; ?>
        <select class="form-select form-select-sm sf-spacer" data-sf-sort aria-label="Urutkan">
            <option value="name:asc" data-type="text">Urut: Nama A–Z</option>
            <option value="sev:asc" data-type="num">Urut: Perlu perhatian dulu</option>
            <option value="tersedia:asc" data-type="num">Urut: Stok tersedia terkecil</option>
            <option value="bebas:asc" data-type="num">Urut: Stok bebas terkecil</option>
            <option value="nilai:desc" data-type="num">Urut: Nilai stok terbesar</option>
        </select>
    </div>

    <div class="sf-scroll">
    <table class="sf-tbl sf-cards">
        <thead><tr>
            <th data-sort="name" data-type="text">Barang</th>
            <th data-sort="tersedia" data-type="num">Stok &amp; Status</th>
            <th class="c" data-sort="reserved" data-type="num" data-first="desc">Reserved</th>
            <th class="c" data-sort="bebas" data-type="num">Bebas</th>
            <th class="r" data-sort="satuan" data-type="num" data-first="desc">Nilai Satuan</th>
            <th class="r" data-sort="nilai" data-type="num" data-first="desc">Nilai Stok</th>
            <th class="r">Aksi</th>
        </tr></thead>

        <?php foreach ($items as $item): $st = $item['_st']; $m = sf_status_meta()[$st['key']]; $id = (int) $item['id']; ?>
        <tbody class="sf-item" id="barang-<?= $id ?>"<?= sf_attrs([
            'data-q' => mb_strtolower($item['nama_barang'] . ' ' . ($item['catatan'] ?? '') . ' ' . $item['satuan'] . ' ' . $m[0]), 'data-status' => $st['key'], 'data-attn' => sf_attn($st['key']) ? 1 : 0,
            'data-group' => mb_strtolower($item['_g']), 'data-v-name' => mb_strtolower($item['nama_barang']), 'data-v-sev' => $m[3],
            'data-v-tersedia' => $item['stok_tersedia'], 'data-v-reserved' => $st['reserved'], 'data-v-bebas' => $st['bebas'],
            'data-v-satuan' => $item['nilai_satuan'], 'data-v-nilai' => (int) $item['stok_tersedia'] * (int) $item['nilai_satuan'],
            'data-sum-tersedia' => $item['stok_tersedia'], 'data-sum-reserved' => $st['reserved'], 'data-sum-bebas' => $st['bebas'],
            'data-sum-nilai' => (int) $item['stok_tersedia'] * (int) $item['nilai_satuan'],
        ]) ?>>
        <tr class="sf-row sf-open" data-sf-toggle>
            <td class="sf-t">
                <div class="d-flex align-items-start">
                    <button type="button" class="btn btn-sm p-0 border-0 sf-chev-btn" data-sf-toggle aria-expanded="false" aria-label="Buka detail" style="background:none;color:inherit"><i class="bi bi-chevron-right sf-chev"></i></button>
                    <div class="min-w-0">
                        <div class="sf-name"><?= esc($item['nama_barang']) ?></div>
                        <?php if ($item['catatan']): ?><div class="sf-note"><?= esc($item['catatan']) ?></div><?php endif; ?>
                    </div>
                </div>
            </td>
            <td class="sf-stok" data-label="Stok">
                <div class="sf-stok-n"><strong><?= sf_n($item['stok_tersedia']) ?></strong>
                    <?= sf_badge($st['key'], $st['short'] ? 'Reserved melebihi stok sebesar ' . $st['short'] : '') ?>
                    <span>dari <?= sf_n($item['stok_awal']) ?> <?= esc($item['satuan']) ?></span></div>
                <?= sf_bar($st) ?>
            </td>
            <td class="c" data-label="Reserved">
                <?php if ($st['reserved'] > 0): ?><span class="sf-badge is-warn"><?= sf_n($st['reserved']) ?></span><?php else: ?><span class="sf-muted">—</span><?php endif; ?>
            </td>
            <td class="c" data-label="Bebas"><span class="sf-badge <?= $st['bebas'] > 0 ? 'is-info' : 'is-none' ?>"><?= sf_n($st['bebas']) ?></span></td>
            <td class="r sf-muted" data-label="Nilai Satuan">Rp <?= sf_n($item['nilai_satuan']) ?></td>
            <td class="r fw-semibold" data-label="Nilai Stok"><?= sf_rp((int) $item['stok_tersedia'] * (int) $item['nilai_satuan']) ?></td>
            <td class="sf-act">
                <div class="sf-actions">
                <?php if ($canEdit): ?>
                    <button type="button" class="btn btn-sm btn-success" title="Tambah stok"
                        onclick="openTambahStok(<?= $id ?>, <?= esc(json_encode($item['nama_barang']), 'attr') ?>, <?= (int) $item['stok_tersedia'] ?>)"><i class="bi bi-plus-circle"></i>Stok</button>
                    <button type="button" class="btn btn-sm btn-outline-warning" title="Keluarkan stok" onclick="toggleKeluar(<?= $id ?>)"><i class="bi bi-box-arrow-right"></i>Keluarkan</button>
                <?php endif; ?>
                    <div class="dropdown">
                        <button type="button" class="btn btn-sm btn-outline-secondary sf-more" data-bs-toggle="dropdown" data-sf-dd aria-expanded="false" aria-label="Aksi lain"><i class="bi bi-three-dots"></i></button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><button type="button" class="dropdown-item" onclick="openDetail(<?= $id ?>)"><i class="bi bi-clock-history"></i>Riwayat stok</button></li>
                            <li><a class="dropdown-item" href="<?= base_url('stock/barang/' . $id . '/kartu?ret=barang' . $pq) ?>"><i class="bi bi-journal-text"></i>Kartu stok</a></li>
                        <?php if ($canEdit): ?>
                            <li><button type="button" class="dropdown-item" onclick="openEditBarang(<?= htmlspecialchars(json_encode(['id' => $id, 'nama_barang' => $item['nama_barang'], 'satuan' => $item['satuan'], 'nilai_satuan' => $item['nilai_satuan'], 'catatan' => $item['catatan']])) ?>)"><i class="bi bi-pencil"></i>Edit barang</button></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><form method="POST" action="<?= base_url('stock/barang/' . $id . '/delete') ?>" onsubmit="return confirm('Hapus barang ini beserta semua log stok?')">
                                <?= csrf_field() ?>
                                <button class="dropdown-item text-danger"><i class="bi bi-trash"></i>Hapus</button></form></li>
                        <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </td>
        </tr>
        <tr class="sf-detail" id="detail-<?= $id ?>">
            <td colspan="7"><div class="sf-detail-in">
                <div>
                    <h6>Riwayat Stok (10 terakhir)</h6>
                    <?php if (empty($item['log'])): ?>
                    <div class="small sf-muted">Belum ada riwayat.</div>
                    <?php else: ?>
                    <div class="sf-scroll"><table class="sf-mini">
                        <thead><tr><th>Tanggal</th><th>Tipe</th><th>Jumlah</th><th>Sebelum</th><th>Sesudah</th><th>Keterangan</th></tr></thead>
                        <tbody>
                        <?php foreach ($item['log'] as $log): ?>
                        <tr>
                            <td><?= $log['tanggal'] ?></td>
                            <td><span class="sf-badge <?= $log['tipe'] === 'masuk' ? 'is-ok' : 'is-bad' ?>"><?= $log['tipe'] ?></span></td>
                            <td><?= sf_n($log['jumlah']) ?></td>
                            <td><?= sf_n($log['stok_sebelum']) ?></td>
                            <td><?= sf_n($log['stok_sesudah']) ?></td>
                            <td><?= esc($log['catatan'] ?? ($log['referensi_tipe'] ? $log['referensi_tipe'] . ' #' . $log['referensi_id'] : '')) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table></div>
                    <?php endif; ?>
                    <a class="small d-inline-block mt-2" href="<?= base_url('stock/barang/' . $id . '/kartu?ret=barang' . $pq) ?>">Lihat kartu stok lengkap <i class="bi bi-arrow-right"></i></a>
                </div>
                <?php if ($canEdit): ?>
                <div class="sf-form-card" id="keluar-<?= $id ?>">
                    <h6><i class="bi bi-box-arrow-right me-1"></i>Keluarkan Stok</h6>
                    <form method="POST" action="<?= base_url('stock/barang/' . $id . '/realisasi') ?>">
                    <?= csrf_field() ?>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small fw-semibold mb-1">Tanggal</label>
                            <input type="date" name="tanggal" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold mb-1">Jumlah</label>
                            <input type="number" name="jumlah" class="form-control form-control-sm keluar-jumlah"
                                min="1" max="<?= $item['stok_tersedia'] ?>"
                                data-bebas="<?= $st['bebas'] ?>" data-satuan="<?= esc($item['satuan']) ?>" required>
                        </div>
                        <div class="col-12">
                            <div class="keluar-warn text-warning small">Melebihi stok bebas (<?= $st['bebas'] ?>) — akan memakai stok yang sudah di-reserve.</div>
                            <label class="form-label small fw-semibold mb-1">Catatan</label>
                            <input type="text" name="catatan" class="form-control form-control-sm" placeholder="Opsional">
                        </div>
                        <div class="col-12 d-flex gap-2">
                            <button type="submit" class="btn btn-sm btn-warning">Keluarkan</button>
                            <button type="button" class="btn btn-sm btn-link text-muted" onclick="toggleKeluar(<?= $id ?>, true)">Batal</button>
                        </div>
                    </div>
                    </form>
                </div>
                <?php endif; ?>
            </div></td>
        </tr>
        </tbody>
        <?php endforeach; ?>

        <tbody class="sf-anchor"><tr class="sf-empty-row" data-sf-empty <?= empty($items) ? '' : 'hidden' ?>><td colspan="7"><div class="sf-empty"><i class="bi bi-<?= empty($items) ? 'box-seam' : 'search' ?>"></i>
            <?= empty($items) ? 'Belum ada data barang.' : 'Tidak ada barang yang cocok dengan pencarian/filter.' ?>
            <?php if (empty($items) && $canEdit): ?><br><button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahBarang"><i class="bi bi-plus-lg me-1"></i>Tambah barang pertama</button><?php endif; ?>
        </div></td></tr></tbody>
        <tfoot><tr>
            <td class="sf-t">Total (yang ditampilkan)</td>
            <td data-label="Stok"><span data-sf-sum="tersedia"><?= sf_n($sumT) ?></span> unit</td>
            <td class="c" data-label="Reserved"><span data-sf-sum="reserved"><?= sf_n($sumR) ?></span></td>
            <td class="c" data-label="Bebas"><span data-sf-sum="bebas"><?= sf_n($sumB) ?></span></td>
            <td class="sf-hide-m"></td>
            <td class="r" data-label="Nilai Stok"><span data-sf-sum="nilai" data-fmt="rp"><?= sf_rp($sumN) ?></span></td>
            <td class="sf-hide-m"></td>
        </tr></tfoot>
    </table>
    </div>
    <div class="sf-legend"><span><i style="background:var(--sf-ok)"></i>stok bebas</span><span><i style="background:repeating-linear-gradient(135deg,#fbbf24 0 3px,#fbbf2488 3px 6px)"></i>reserved program</span><span>Menipis = bebas ≤ 20% dari stok awal · Kurang = reserved melebihi stok</span></div>
</div>

</div>

<?php if ($canEdit): ?>
<!-- Modal Tambah Barang -->
<div class="modal fade" id="modalTambahBarang" tabindex="-1">
<div class="modal-dialog">
<div class="modal-content">
<form method="POST" action="<?= base_url('stock/barang/store') ?>">
<?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title">Tambah Barang</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="mb-3">
        <label class="form-label small fw-semibold">Nama Barang <span class="text-danger">*</span></label>
        <input type="text" name="nama_barang" class="form-control" required>
        <div class="form-text">Tips: awali dengan kelompok, mis. “POP JOY - Boneka” agar barang terkelompok di daftar.</div>
    </div>
    <div class="row g-2">
        <div class="col-6">
            <label class="form-label small fw-semibold">Satuan</label>
            <input type="text" name="satuan" class="form-control" value="pcs" placeholder="pcs, buah, lembar...">
        </div>
        <div class="col-6">
            <label class="form-label small fw-semibold">Nilai Satuan (Rp)</label>
            <input type="text" name="nilai_satuan" class="form-control nominal" value="0">
        </div>
    </div>
    <div class="mb-3 mt-2">
        <label class="form-label small fw-semibold">Stok Awal</label>
        <input type="number" name="stok_awal" class="form-control" value="0" min="0">
    </div>
    <div class="mb-3">
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

<!-- Modal Edit Barang -->
<div class="modal fade" id="modalEditBarang" tabindex="-1">
<div class="modal-dialog">
<div class="modal-content">
<form method="POST" id="formEditBarang" action="">
<?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title">Edit Barang</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="mb-3">
        <label class="form-label small fw-semibold">Nama Barang <span class="text-danger">*</span></label>
        <input type="text" name="nama_barang" id="edit_nama_barang" class="form-control" required>
    </div>
    <div class="row g-2">
        <div class="col-6">
            <label class="form-label small fw-semibold">Satuan</label>
            <input type="text" name="satuan" id="edit_satuan" class="form-control">
        </div>
        <div class="col-6">
            <label class="form-label small fw-semibold">Nilai Satuan (Rp)</label>
            <input type="text" name="nilai_satuan" id="edit_nilai_satuan" class="form-control nominal">
        </div>
    </div>
    <div class="mb-3 mt-2">
        <label class="form-label small fw-semibold">Catatan</label>
        <textarea name="catatan" id="edit_catatan" class="form-control" rows="2"></textarea>
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

<!-- Modal Tambah Stok -->
<div class="modal fade" id="modalTambahStok" tabindex="-1">
<div class="modal-dialog modal-sm">
<div class="modal-content">
<form method="POST" id="formTambahStok" action="">
<?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title">Tambah Stok</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <p class="small mb-1 fw-semibold" id="stokNamaBarang"></p>
    <p class="small text-muted mb-2">Stok tersedia saat ini: <strong id="stokSaatIni">—</strong></p>
    <div class="mb-3">
        <label class="form-label small fw-semibold">Jumlah Masuk <span class="text-danger">*</span></label>
        <input type="number" name="jumlah" class="form-control" min="1" required>
    </div>
    <div class="mb-3">
        <label class="form-label small fw-semibold">Catatan</label>
        <input type="text" name="catatan" class="form-control" placeholder="Opsional">
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
    <button type="submit" class="btn btn-success">Tambah</button>
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

function openEditBarang(item) {
    document.getElementById('edit_nama_barang').value  = item.nama_barang;
    document.getElementById('edit_satuan').value       = item.satuan;
    document.getElementById('edit_nilai_satuan').value = Number(item.nilai_satuan).toLocaleString('id-ID');
    document.getElementById('edit_catatan').value      = item.catatan || '';
    document.getElementById('formEditBarang').action   = '<?= base_url('stock/barang/') ?>' + item.id + '/update';
    new bootstrap.Modal(document.getElementById('modalEditBarang')).show();
}
function openTambahStok(id, nama, stok) {
    document.getElementById('stokNamaBarang').textContent = nama;
    document.getElementById('stokSaatIni').textContent = stok.toLocaleString('id-ID');
    document.getElementById('formTambahStok').action   = '<?= base_url('stock/barang/') ?>' + id + '/tambah-stok';
    document.querySelector('#formTambahStok [name=jumlah]').value = '';
    new bootstrap.Modal(document.getElementById('modalTambahStok')).show();
}
document.addEventListener('DOMContentLoaded', function () {
    const m = /^#barang-(\d+)$/.exec(location.hash);
    if (m && sfItem(m[1])) { openDetail(m[1]); sfItem(m[1]).scrollIntoView({ block: 'center' }); }
});
function sfItem(id) { return document.getElementById('barang-' + id); }
function openDetail(id) {
    const it = sfItem(id), root = document.getElementById('sfBarangList');
    root.sfToggle(it, true);
    it.scrollIntoView({ block: 'nearest' });
}
function toggleKeluar(id, tutup) {
    const it = sfItem(id), root = document.getElementById('sfBarangList');
    root.sfToggle(it, !tutup);
    if (tutup) return;
    const inp = it.querySelector('.keluar-jumlah');
    if (inp) inp.focus();
}
document.addEventListener('input', function(e) {
    if (!e.target.classList.contains('keluar-jumlah')) return;
    const inp  = e.target;
    const warn = inp.closest('.sf-form-card').querySelector('.keluar-warn');
    const val  = parseInt(inp.value) || 0;
    const bebas = parseInt(inp.dataset.bebas) || 0;
    warn.style.display = val > bebas ? 'block' : 'none';
});
</script>
<?= $this->endSection() ?>
