<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/events-core.css') ?>?v=<?= @filemtime(FCPATH . 'css/events-core.css') ?: 1 ?>">
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php
$mallsDef = ['ewalk' => 'eWalk', 'pentacity' => 'Pentacity'];
$totalLoc = 0; $totalOff = 0;
foreach ($grouped as $g) { foreach ($g as $l) { $totalLoc++; if (! $l['aktif']) $totalOff++; } }
?>

<div class="page-evc" id="evLoc" data-edit-base="<?= base_url('event-locations/') ?>">

<div class="ev-head">
    <a href="<?= base_url('events') ?>" class="ev-iconbtn" aria-label="Kembali ke Daftar Event" title="Kembali ke Daftar Event"><i class="bi bi-arrow-left"></i></a>
    <div class="ev-head-ico"><i class="bi bi-geo-alt"></i></div>
    <div class="min-w-0">
        <h1 class="ev-title">Master Lokasi Event</h1>
        <div class="ev-subtitle"><?= $totalLoc ?> lokasi<?= $totalOff ? ' · ' . $totalOff . ' nonaktif' : '' ?> — daftar lokasi yang bisa dipilih saat membuat event</div>
    </div>
    <div class="ev-head-act">
        <button class="ev-btn ev-btn-primary ev-btn-lg" data-bs-toggle="modal" data-bs-target="#addModal"><i class="bi bi-plus-lg"></i>Tambah Lokasi</button>
    </div>
</div>

<div class="ev-locgrid">
<?php foreach ($mallsDef as $mallKey => $mallLabel): $rows = $grouped[$mallKey] ?? []; ?>
<section class="ev-loccard">
    <div class="ev-loccard-h">
        <i class="bi bi-building" style="color:<?= $mallKey === 'ewalk' ? 'var(--ev-blue)' : 'var(--ev-ok)' ?>"></i>
        <h2><?= $mallLabel ?></h2>
        <span class="ev-chip ev-chip-mute"><?= count($rows) ?> lokasi</span>
    </div>
    <?php if (empty($rows)): ?>
    <div class="ev-loc-empty">Belum ada lokasi untuk <?= $mallLabel ?>.</div>
    <?php else: foreach ($rows as $loc): ?>
    <div class="ev-locrow<?= $loc['aktif'] ? '' : ' is-off' ?>">
        <i class="bi bi-geo-alt"></i>
        <span class="ev-locrow-n"><?= esc($loc['nama']) ?></span>
        <?php if (! $loc['aktif']): ?><span class="ev-chip ev-chip-mute">Nonaktif</span><?php endif; ?>
        <div class="ev-locrow-act">
            <button type="button" class="ev-iconbtn ev-iconbtn-sm edit-btn" title="Ubah lokasi" aria-label="Ubah <?= esc($loc['nama'], 'attr') ?>"
                data-id="<?= $loc['id'] ?>"
                data-nama="<?= esc($loc['nama'], 'attr') ?>"
                data-mall="<?= esc($loc['mall'], 'attr') ?>"
                data-aktif="<?= (int) $loc['aktif'] ?>">
                <i class="bi bi-pencil"></i>
            </button>
            <form method="POST" action="<?= base_url('event-locations/'.$loc['id'].'/delete') ?>" onsubmit="return confirm('Hapus lokasi ini?')">
                <?= csrf_field() ?>
                <button type="submit" class="ev-iconbtn ev-iconbtn-sm ev-iconbtn-bad" title="Hapus lokasi" aria-label="Hapus <?= esc($loc['nama'], 'attr') ?>"><i class="bi bi-trash"></i></button>
            </form>
        </div>
    </div>
    <?php endforeach; endif; ?>
</section>
<?php endforeach; ?>
</div>

<!-- Modal tambah -->
<div class="modal fade ev-modal" id="addModal" tabindex="-1" aria-labelledby="addModalT"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
<form method="POST" action="<?= base_url('event-locations/add') ?>">
<?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title fw-semibold" id="addModalT">Tambah Lokasi</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
<div class="modal-body">
    <div class="mb-3">
        <label class="form-label small fw-semibold" for="aMall">Mall <span class="text-danger">*</span></label>
        <select name="mall" id="aMall" class="form-select">
            <option value="ewalk">eWalk</option>
            <option value="pentacity">Pentacity</option>
        </select>
    </div>
    <div class="mb-0">
        <label class="form-label small fw-semibold" for="aNama">Nama Lokasi <span class="text-danger">*</span></label>
        <input type="text" name="nama" id="aNama" class="form-control" placeholder="Contoh: GF Atrium, LG Food Court..." required>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="ev-btn" data-bs-dismiss="modal">Batal</button>
    <button type="submit" class="ev-btn ev-btn-primary">Tambah</button>
</div>
</form>
</div></div></div>

<!-- Modal ubah -->
<div class="modal fade ev-modal" id="editModal" tabindex="-1" aria-labelledby="editModalT"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
<form id="editForm" method="POST">
<?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title fw-semibold" id="editModalT">Ubah Lokasi</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
<div class="modal-body">
    <div class="mb-3">
        <label class="form-label small fw-semibold" for="eMall">Mall</label>
        <select name="mall" id="eMall" class="form-select">
            <option value="ewalk">eWalk</option>
            <option value="pentacity">Pentacity</option>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label small fw-semibold" for="eNama">Nama Lokasi</label>
        <input type="text" name="nama" id="eNama" class="form-control" required>
    </div>
    <div class="form-check">
        <input class="form-check-input" type="checkbox" name="aktif" id="eAktif" value="1">
        <label class="form-check-label small" for="eAktif">Aktif (tampil saat pembuatan event)</label>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="ev-btn" data-bs-dismiss="modal">Batal</button>
    <button type="submit" class="ev-btn ev-btn-primary">Simpan</button>
</div>
</form>
</div></div></div>

</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="<?= base_url('js/events-core.js') ?>?v=<?= @filemtime(FCPATH . 'js/events-core.js') ?: 1 ?>"></script>
<?= $this->endSection() ?>
