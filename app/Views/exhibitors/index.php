<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/events-modul.css') ?>?v=<?= @filemtime(FCPATH . 'css/events-modul.css') ?: 1 ?>">
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php
if (! function_exists('evRp')) {
    function evRp($n): string { return 'Rp ' . number_format((float) $n, 0, ',', '.'); }
}
$totalDealing = array_sum(array_column($exhibitors, 'nilai_dealing'));
$byKategori   = [];
foreach ($exhibitors as $e) { $byKategori[$e['kategori']][] = $e; }
ksort($byKategori);

// Generate daftar tanggal selama periode event
$eventDates = [];
$startDt    = new DateTime($event['start_date']);
for ($i = 0; $i < (int)$event['event_days']; $i++) {
    $d = clone $startDt;
    $d->modify("+{$i} days");
    $eventDates[] = $d->format('Y-m-d');
}

$tgtJumlah = (int)($target['target_jumlah']        ?? 0);
$tgtNilai  = (int)($target['target_nilai_dealing']  ?? 0);
$hasTarget = $tgtJumlah > 0 || $tgtNilai > 0;
$pctJumlah = ($tgtJumlah > 0) ? min(100, round(count($exhibitors) / $tgtJumlah * 100)) : null;
$pctNilai  = ($tgtNilai  > 0) ? min(100, round($totalDealing       / $tgtNilai  * 100)) : null;
$barOf = fn($p) => $p === null ? 'blue' : ($p >= 100 ? 'ok' : ($p >= 50 ? 'mid' : 'low'));
$tOf   = fn($p) => $p === null ? '' : ($p >= 100 ? 't-ok' : ($p >= 50 ? 't-warn' : 't-bad'));
$totalProg = 0;
foreach ($exhibitors as $e) { $totalProg += count($programs[$e['id']] ?? []); }
$nTanpaProg = count(array_filter($exhibitors, fn($e) => empty($programs[$e['id']])));

$actions = '';
if ($canEdit) {
    $actions = '<button class="ev-btn" data-bs-toggle="modal" data-bs-target="#targetModal"><i class="bi bi-bullseye"></i><span class="d-none d-sm-inline">Set Target</span></button>'
             . '<button class="ev-btn ev-btn-primary" data-bs-toggle="modal" data-bs-target="#addModal"><i class="bi bi-plus-lg"></i>Tambah Exhibition</button>';
}
?>

<div class="page-evm evm-exhibitors" id="evmRoot" data-key="exh-<?= $event['id'] ?>" data-unit="exhibition">

<?= view('partials/evm_head', ['event' => $event, 'module' => 'exhibitors', 'completion' => $completion, 'canEdit' => $canEdit,
    'title' => 'Exhibition by Casual Leasing', 'icon' => 'bi-shop', 'actions' => $actions]) ?>

<!-- Strip KPI -->
<div class="ev-kpis">
    <div class="ev-kpi <?= $tOf($pctJumlah) ?>">
        <div class="ev-kpi-l">Total Exhibition</div>
        <div class="ev-kpi-v"><?= count($exhibitors) ?></div>
        <div class="ev-kpi-s"><?= $tgtJumlah > 0 ? $pctJumlah . '% dari target ' . $tgtJumlah : 'belum ada target jumlah' ?></div>
        <?php if ($tgtJumlah > 0): ?><div class="ev-bar ev-bar-<?= $barOf($pctJumlah) ?>"><i style="width:<?= $pctJumlah ?>%"></i></div><?php endif; ?>
    </div>
    <div class="ev-kpi">
        <div class="ev-kpi-l">Kategori</div>
        <div class="ev-kpi-v"><?= count($byKategori) ?></div>
        <div class="ev-kpi-s"><?= $nTanpaProg ?> exhibition belum punya program</div>
    </div>
    <div class="ev-kpi t-cyan">
        <div class="ev-kpi-l">Program Booth</div>
        <div class="ev-kpi-v ev-c-cyan"><?= $totalProg ?></div>
        <div class="ev-kpi-s">jadwal program semua exhibitor</div>
    </div>
    <div class="ev-kpi <?= $tOf($pctNilai) ?: 't-ok' ?>">
        <div class="ev-kpi-l">Nilai Dealing</div>
        <div class="ev-kpi-v ev-c-ok"><?= evRp($totalDealing) ?></div>
        <div class="ev-kpi-s"><?= $tgtNilai > 0 ? $pctNilai . '% dari target ' . evRp($tgtNilai) : 'belum ada target nilai' ?></div>
        <?php if ($tgtNilai > 0): ?><div class="ev-bar ev-bar-<?= $barOf($pctNilai) ?>"><i style="width:<?= $pctNilai ?>%"></i></div><?php endif; ?>
    </div>
</div>

<?php if (empty($exhibitors)): ?>
<div class="ev-empty">
    <i class="bi bi-shop"></i>
    <h5>Belum ada exhibition untuk event ini</h5>
    <p>Catat exhibitor casual leasing beserta lokasi booth, nilai dealing, dan program boothnya.</p>
    <?php if ($canEdit): ?>
    <button class="ev-btn ev-btn-primary" data-bs-toggle="modal" data-bs-target="#addModal"><i class="bi bi-plus-lg"></i>Tambah Exhibition Pertama</button>
    <?php endif; ?>
</div>
<?php else: ?>

<div class="ev-tools">
    <div class="ev-search">
        <i class="bi bi-search"></i>
        <input type="search" id="evQ" placeholder="Cari exhibition atau booth…" autocomplete="off" aria-label="Cari exhibition">
    </div>
    <select class="ev-select" data-ev-filter="kat" aria-label="Filter kategori">
        <option value="">Semua kategori</option>
        <?php foreach (array_keys($byKategori) as $k): ?><option value="<?= esc($k, 'attr') ?>"><?= esc($k) ?></option><?php endforeach; ?>
    </select>
    <select class="ev-select" data-ev-filter="prog" aria-label="Filter program">
        <option value="">Semua program</option>
        <option value="ada">Sudah ada program</option>
        <option value="kosong">Belum ada program</option>
    </select>
    <select class="ev-select" id="evSort" aria-label="Urutkan">
        <option value="" data-dir="asc">Urutan: input</option>
        <option value="name" data-type="str" data-dir="asc">Nama A–Z</option>
        <option value="nilai" data-dir="desc">Nilai dealing terbesar</option>
        <option value="nprog" data-dir="desc">Program terbanyak</option>
    </select>
    <div class="ev-tools-end">
        <span class="ev-count" id="evCount" aria-live="polite"></span>
        <button type="button" class="ev-btn ev-btn-sm" id="evExpand" title="Buka semua"><i class="bi bi-arrows-expand"></i><span class="d-none d-md-inline">Buka semua</span></button>
        <button type="button" class="ev-btn ev-btn-sm" id="evCollapse" title="Tutup semua"><i class="bi bi-arrows-collapse"></i><span class="d-none d-md-inline">Tutup semua</span></button>
    </div>
</div>

<div class="ev-empty ev-empty-filter" id="evNoMatch" hidden>
    <i class="bi bi-search"></i>
    <h5>Tidak ada exhibition yang cocok</h5>
    <p>Ubah kata kunci atau filter.</p>
    <button type="button" class="ev-btn" id="evReset"><i class="bi bi-x-circle"></i>Reset filter</button>
</div>

<?php $ord = 0; foreach ($byKategori as $kat => $items): ?>
<section class="ev-group">
<div class="ev-group-h">
    <span class="ev-group-t"><i class="bi bi-tag"></i><?= esc($kat) ?></span>
    <span class="ev-group-n"><?= count($items) ?> exhibition</span>
    <span class="ev-group-sum"><?= evRp(array_sum(array_column($items, 'nilai_dealing'))) ?></span>
</div>
<div class="ev-list">
<?php foreach ($items as $ex):
    $exPrograms = $programs[$ex['id']] ?? [];
    $nP = count($exPrograms);
    $search = mb_strtolower($ex['nama_exhibitor'] . ' ' . ($ex['lokasi_booth'] ?? '') . ' ' . ($ex['catatan'] ?? '') . ' ' . implode(' ', array_column($exPrograms, 'nama_program')));
?>
<article class="ev-card" id="ex-<?= $ex['id'] ?>"
         data-name="<?= esc($ex['nama_exhibitor'], 'attr') ?>" data-search="<?= esc($search, 'attr') ?>"
         data-kat="<?= esc($kat, 'attr') ?>" data-prog="<?= $nP ? 'ada' : 'kosong' ?>"
         data-nilai="<?= (int) $ex['nilai_dealing'] ?>" data-nprog="<?= $nP ?>" data-order="<?= $ord++ ?>">
    <div class="ev-card-head is-slim" data-ev-toggle>
        <button type="button" class="ev-chev" aria-expanded="false" aria-controls="body-ex<?= $ex['id'] ?>" aria-label="Buka atau tutup program exhibition"><i class="bi bi-chevron-right"></i></button>
        <div class="min-w-0">
            <div class="ev-card-name">
                <span class="ev-card-t"><?= esc($ex['nama_exhibitor']) ?></span>
                <?php if ($ex['lokasi_booth']): ?><span class="ev-chip ev-chip-blue"><i class="bi bi-geo-alt"></i><?= esc($ex['lokasi_booth']) ?></span><?php endif; ?>
                <span class="ev-chip <?= $nP ? 'ev-chip-ok' : 'ev-chip-mute' ?>"><i class="bi bi-calendar-event"></i><?= $nP ? $nP . ' program' : 'Belum ada program' ?></span>
            </div>
            <?php if ($ex['catatan']): ?><div class="ev-card-meta"><span><i class="bi bi-sticky"></i><?= esc($ex['catatan']) ?></span></div><?php endif; ?>
        </div>
        <div class="ev-fig">
            <div><span class="ev-fig-l">Nilai dealing</span><b class="ev-c-ok"><?= evRp($ex['nilai_dealing']) ?></b></div>
        </div>
        <div class="ev-card-act" data-ev-notoggle>
            <button type="button" class="ev-btn ev-btn-sm ev-btn-soft" data-bs-toggle="modal" data-bs-target="#programsModal-<?= $ex['id'] ?>" title="<?= $canEdit ? 'Kelola program booth' : 'Lihat program booth' ?>">
                <i class="bi bi-calendar-event"></i><span class="d-none d-sm-inline"><?= $canEdit ? 'Program' : 'Lihat program' ?></span>
            </button>
            <?php if ($canEdit): ?>
            <div class="dropdown">
                <button class="ev-iconbtn" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Aksi exhibition"><i class="bi bi-three-dots-vertical"></i></button>
                <ul class="dropdown-menu dropdown-menu-end ev-menu">
                    <li>
                        <button type="button" class="dropdown-item edit-btn"
                            data-id="<?= $ex['id'] ?>"
                            data-nama="<?= esc($ex['nama_exhibitor'], 'attr') ?>"
                            data-kategori="<?= esc($ex['kategori'], 'attr') ?>"
                            data-nilai="<?= number_format($ex['nilai_dealing'], 0, ',', '.') ?>"
                            data-lokasi="<?= esc($ex['lokasi_booth'], 'attr') ?>"
                            data-catatan="<?= esc($ex['catatan'], 'attr') ?>"><i class="bi bi-pencil"></i>Edit exhibition</button>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="<?= base_url('events/' . $event['id'] . '/exhibitors/' . $ex['id'] . '/delete') ?>" onsubmit="return confirm('Hapus exhibitor ini?')">
                            <?= csrf_field() ?>
                            <button type="submit" class="dropdown-item text-danger"><i class="bi bi-trash"></i>Hapus exhibition</button>
                        </form>
                    </li>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="ev-card-body" id="body-ex<?= $ex['id'] ?>" hidden>
        <?php if (empty($exPrograms)): ?>
        <div class="ev-empty-sm">Belum ada program booth.<?php if ($canEdit): ?> Klik "Program" untuk menambah.<?php endif; ?></div>
        <?php else: ?>
        <div class="ev-progs">
        <?php foreach ($exPrograms as $ep):
            $jam = '';
            if ($ep['jam_mulai'])   $jam  = substr($ep['jam_mulai'], 0, 5);
            if ($ep['jam_selesai']) $jam .= ' – ' . substr($ep['jam_selesai'], 0, 5);
            if ($ep['tanggal_mulai'] && $ep['tanggal_selesai'] && $ep['tanggal_mulai'] !== $ep['tanggal_selesai']) {
                $per = date('d M', strtotime($ep['tanggal_mulai'])) . ' – ' . date('d M Y', strtotime($ep['tanggal_selesai']));
            } elseif ($ep['tanggal_mulai']) { $per = date('d M Y', strtotime($ep['tanggal_mulai'])); } else { $per = '—'; }
        ?>
            <div class="ev-prog-item">
                <b><?= esc($ep['nama_program']) ?></b>
                <span class="ev-c-cyan ev-nowrap"><i class="bi bi-calendar3 me-1"></i><?= $per ?></span>
                <span class="ev-c-mute ev-nowrap"><?php if ($jam): ?><i class="bi bi-clock me-1"></i><?= $jam ?><?php endif; ?></span>
                <?php if (! empty($ep['deskripsi'])): ?><span class="ev-who"><?= esc($ep['deskripsi']) ?></span><?php endif; ?>
            </div>
        <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</article>
<?php endforeach; ?>
</div>
</section>
<?php endforeach; ?>

<div class="ev-foot">
    <span class="ev-foot-l"><?= count($exhibitors) ?> exhibition · <?= count($byKategori) ?> kategori</span>
    <span class="ev-foot-v ev-c-ok"><?= evRp($totalDealing) ?><small>total nilai dealing</small></span>
</div>
<?php endif; ?>

</div><!-- /page-evm -->

<?php if ($canEdit): ?>
<div class="modal fade" id="addModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<form method="POST" action="<?= base_url('events/'.$event['id'].'/exhibitors/add') ?>">
<?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title fw-semibold">Tambah Exhibitor</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="mb-3"><label class="form-label small fw-semibold">Nama Exhibition <span class="text-danger">*</span></label>
        <input type="text" name="nama_exhibitor" class="form-control" required></div>
    <div class="row">
        <div class="col-6 mb-3"><label class="form-label small fw-semibold">Kategori <span class="text-danger">*</span></label>
            <select name="kategori" class="form-select kategori-select" required data-other-target="addKategoriCustom">
                <option value="">— Pilih —</option>
                <?php foreach ($kategoriOptions as $k): ?>
                <option value="<?= esc($k, 'attr') ?>"><?= esc($k) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="kategori_custom" id="addKategoriCustom" class="form-control mt-1 d-none" placeholder="Tulis kategori...">
        </div>
        <div class="col-6 mb-3"><label class="form-label small fw-semibold">Lokasi Booth</label>
            <input type="text" name="lokasi_booth" class="form-control" placeholder="A1, Lantai 1..."></div>
    </div>
    <div class="mb-3"><label class="form-label small fw-semibold">Nilai Dealing (Rp)</label>
        <input type="text" name="nilai_dealing" class="form-control currency-input" value="0"></div>
    <div class="mb-3"><label class="form-label small fw-semibold">Catatan</label>
        <input type="text" name="catatan" class="form-control"></div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Tambah</button></div>
</form>
</div></div></div>

<div class="modal fade" id="editModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<form id="editForm" method="POST">
<?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title fw-semibold">Edit Exhibitor</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="mb-3"><label class="form-label small fw-semibold">Nama Exhibition</label>
        <input type="text" name="nama_exhibitor" id="eNama" class="form-control" required></div>
    <div class="row">
        <div class="col-6 mb-3"><label class="form-label small fw-semibold">Kategori</label>
            <select name="kategori" id="eKat" class="form-select kategori-select" required data-other-target="editKategoriCustom">
                <option value="">— Pilih —</option>
                <?php foreach ($kategoriOptions as $k): ?>
                <option value="<?= esc($k, 'attr') ?>"><?= esc($k) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="kategori_custom" id="editKategoriCustom" class="form-control mt-1 d-none" placeholder="Tulis kategori...">
        </div>
        <div class="col-6 mb-3"><label class="form-label small fw-semibold">Lokasi Booth</label>
            <input type="text" name="lokasi_booth" id="eLok" class="form-control"></div>
    </div>
    <div class="mb-3"><label class="form-label small fw-semibold">Nilai Dealing (Rp)</label>
        <input type="text" name="nilai_dealing" id="eNilai" class="form-control currency-input"></div>
    <div class="mb-3"><label class="form-label small fw-semibold">Catatan</label>
        <input type="text" name="catatan" id="eCatatan" class="form-control"></div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
</form>
</div></div></div>
<?php endif; ?>

<!-- Target Modal -->
<?php if ($canEdit): ?>
<div class="modal fade" id="targetModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<form method="POST" action="<?= base_url('events/'.$event['id'].'/exhibitors/save-target') ?>">
<?= csrf_field() ?>
<div class="modal-header">
    <h5 class="modal-title fw-semibold"><i class="bi bi-bullseye me-2 text-primary"></i>Set Target Exhibition</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
    <p class="text-muted small mb-3">Target digunakan sebagai pembanding di halaman ini dan di Summary Event.</p>
    <div class="mb-3">
        <label class="form-label small fw-semibold">Target Jumlah Exhibitor</label>
        <input type="number" name="target_jumlah" class="form-control" min="0"
               value="<?= (int)($target['target_jumlah'] ?? 0) ?>" placeholder="0">
        <div class="form-text">Berapa booth exhibitor yang direncanakan.</div>
    </div>
    <div class="mb-3">
        <label class="form-label small fw-semibold">Target Total Nilai Dealing (Rp)</label>
        <input type="text" name="target_nilai_dealing" class="form-control currency-input"
               value="<?= ($target['target_nilai_dealing'] ?? 0) > 0 ? number_format((int)$target['target_nilai_dealing'],0,',','.') : '' ?>"
               placeholder="0">
        <div class="form-text">Target total revenue dari semua exhibitor.</div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Simpan Target</button>
</div>
</form>
</div></div></div>
<?php endif; ?>

<!-- Program Modals (one per exhibitor) -->
<?php foreach ($exhibitors as $ex): ?>
<?php $exPrograms = $programs[$ex['id']] ?? []; ?>
<div class="modal fade" id="programsModal-<?= $ex['id'] ?>" tabindex="-1">
<div class="modal-dialog modal-lg">
<div class="modal-content">
<div class="modal-header">
    <div>
        <h5 class="modal-title fw-semibold"><i class="bi bi-calendar-event me-2"></i>Program Exhibitor</h5>
        <small class="text-muted"><?= esc($ex['nama_exhibitor']) ?></small>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
    <?php if (empty($exPrograms)): ?>
    <p class="text-muted text-center py-3"><i class="bi bi-calendar-x d-block fs-3 mb-2 opacity-25"></i>Belum ada program.</p>
    <?php else: ?>
    <table class="table table-sm mb-4">
    <thead><tr><th>Nama Program</th><th>Periode</th><th>Jam</th><th>Deskripsi</th><?php if ($canEdit): ?><th></th><?php endif; ?></tr></thead>
    <tbody>
    <?php foreach ($exPrograms as $p): ?>
    <?php
        // Format periode tanggal
        if ($p['tanggal_mulai'] && $p['tanggal_selesai'] && $p['tanggal_mulai'] !== $p['tanggal_selesai']) {
            $periodeTgl = date('d M', strtotime($p['tanggal_mulai'])) . ' – ' . date('d M Y', strtotime($p['tanggal_selesai']));
        } elseif ($p['tanggal_mulai']) {
            $periodeTgl = date('d M Y', strtotime($p['tanggal_mulai']));
        } else {
            $periodeTgl = '—';
        }
        // Format jam
        $jam = '';
        if ($p['jam_mulai'])   $jam  = substr($p['jam_mulai'], 0, 5);
        if ($p['jam_selesai']) $jam .= ' – ' . substr($p['jam_selesai'], 0, 5);
        if (!$jam)             $jam  = '—';
    ?>
    <tr>
        <td class="fw-medium"><?= esc($p['nama_program']) ?></td>
        <td class="small text-nowrap"><?= $periodeTgl ?></td>
        <td class="small text-nowrap"><?= $jam ?></td>
        <td class="small text-muted"><?= esc($p['deskripsi']) ?: '—' ?></td>
        <?php if ($canEdit): ?>
        <td>
            <form method="POST" action="<?= base_url('events/'.$event['id'].'/exhibitors/'.$ex['id'].'/programs/'.$p['id'].'/delete') ?>" onsubmit="return confirm('Hapus program ini?')">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
            </form>
        </td>
        <?php endif; ?>
    </tr>
    <?php endforeach; ?>
    </tbody>
    </table>
    <?php endif; ?>

    <?php if ($canEdit): ?>
    <hr class="my-3">
    <h6 class="fw-semibold mb-3">Tambah Program Baru</h6>
    <form method="POST" action="<?= base_url('events/'.$event['id'].'/exhibitors/'.$ex['id'].'/programs/add') ?>">
        <?= csrf_field() ?>
        <div class="mb-3">
            <label class="form-label small fw-semibold">Nama Program <span class="text-danger">*</span></label>
            <input type="text" name="nama_program" class="form-control" required placeholder="Contoh: Diskon 30%, Demo Produk, Buy 1 Get 1...">
        </div>
        <div class="row g-2 mb-3">
            <div class="col-6">
                <label class="form-label small fw-semibold">Tanggal Mulai</label>
                <select name="tanggal_mulai" class="form-select form-select-sm tgl-mulai-select">
                    <option value="">— Pilih tanggal —</option>
                    <?php foreach ($eventDates as $d): ?>
                    <option value="<?= $d ?>"><?= date('D, d M Y', strtotime($d)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6">
                <label class="form-label small fw-semibold">Tanggal Selesai</label>
                <select name="tanggal_selesai" class="form-select form-select-sm tgl-selesai-select">
                    <option value="">— Sama dengan mulai —</option>
                    <?php foreach ($eventDates as $d): ?>
                    <option value="<?= $d ?>"><?= date('D, d M Y', strtotime($d)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="row g-2 mb-3">
            <div class="col-6">
                <label class="form-label small fw-semibold">Jam Mulai</label>
                <input type="time" name="jam_mulai" class="form-control form-control-sm">
            </div>
            <div class="col-6">
                <label class="form-label small fw-semibold">Jam Selesai</label>
                <input type="time" name="jam_selesai" class="form-control form-control-sm">
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label small fw-semibold">Deskripsi</label>
            <input type="text" name="deskripsi" class="form-control form-control-sm" placeholder="Detail program (opsional)">
        </div>
        <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
            <button type="submit" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Tambah Program</button>
        </div>
    </form>
    <?php else: ?>
    <div class="text-end"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button></div>
    <?php endif; ?>
</div>
</div>
</div>
</div>
<?php endforeach; ?>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
<?php if ($canEdit): ?>
document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('editForm').action = '<?= base_url('events/'.$event['id'].'/exhibitors/') ?>' + this.dataset.id + '/edit';
        document.getElementById('eNama').value    = this.dataset.nama;
        document.getElementById('eNilai').value   = this.dataset.nilai;
        document.getElementById('eLok').value     = this.dataset.lokasi;
        document.getElementById('eCatatan').value = this.dataset.catatan;
        setKategoriSelect(document.getElementById('eKat'), this.dataset.kategori, document.getElementById('editKategoriCustom'));
        new bootstrap.Modal(document.getElementById('editModal')).show();
    });
});

<?php endif; ?>

// Kategori select: show/hide custom input for "Lainnya"
function toggleKategoriCustom(select, customInput) {
    const isOther = select.value === 'Lainnya';
    customInput.classList.toggle('d-none', !isOther);
    customInput.required = isOther;
    if (!isOther) customInput.value = '';
}

function setKategoriSelect(select, value, customInput) {
    const opt = [...select.options].find(o => o.value === value);
    if (opt) {
        select.value = value;
        toggleKategoriCustom(select, customInput);
    } else {
        select.value = 'Lainnya';
        customInput.classList.remove('d-none');
        customInput.required = true;
        customInput.value = value;
    }
}

document.querySelectorAll('.kategori-select').forEach(sel => {
    const customInput = document.getElementById(sel.dataset.otherTarget);
    sel.addEventListener('change', () => toggleKategoriCustom(sel, customInput));
    sel.closest('form').addEventListener('submit', function() {
        if (sel.value === 'Lainnya' && customInput.value.trim()) {
            sel.value = customInput.value.trim();
        }
    });
});

// Currency input formatting
document.addEventListener('input', function(e) {
    if (!e.target.classList.contains('currency-input')) return;
    const n = parseInt(e.target.value.replace(/[^0-9]/g, '')) || 0;
    e.target.value = n.toLocaleString('id-ID');
});

// Tanggal Selesai otomatis ikut Tanggal Mulai jika belum dipilih
document.addEventListener('change', function(e) {
    if (!e.target.classList.contains('tgl-mulai-select')) return;
    const form     = e.target.closest('form');
    const selesai  = form.querySelector('.tgl-selesai-select');
    if (selesai && !selesai.value) selesai.value = e.target.value;
});
</script>
<script src="<?= base_url('js/events-modul.js') ?>?v=<?= @filemtime(FCPATH . 'js/events-modul.js') ?: 1 ?>"></script>
<?= $this->endSection() ?>
