<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/creative-main.css') ?>?v=<?= @filemtime(FCPATH . 'css/creative-main.css') ?: 1 ?>">
<link rel="stylesheet" href="<?= base_url('css/creative-turunan.css') ?>?v=<?= @filemtime(FCPATH . 'css/creative-turunan.css') ?: 1 ?>">
<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
use App\Services\CreativeTurunanService as TS;

$induks = ['design' => ['Design', 'bi-vector-pen'], 'motion_graphic' => ['Motion Graphic', 'bi-film']];
$tipeLabel = ['master_design' => 'Design', 'motion_graphic' => 'Motion Graphic', 'digital' => 'Konten Digital', 'cetak' => 'Media Cetak'];
$platLabel = ['ig' => 'Instagram', 'tiktok' => 'TikTok', 'keduanya' => 'IG & TikTok'];
$nJenis = 0; foreach ($katalog as $l) $nJenis += count($l);
$nPaket = count($paket['bulanan']) + count($paket['event']);
?>
<div class="page-cre" id="creKat">

<div class="cr-head">
    <div class="cr-head-ico"><i class="bi bi-sliders"></i></div>
    <div class="min-w-0">
        <div class="cr-crumb"><a href="<?= base_url('creative') ?>">Creative &amp; Design</a><i class="bi bi-chevron-right" style="font-size:.6rem"></i><span>Katalog &amp; Paket</span></div>
        <h1 class="cr-title">Katalog Turunan &amp; Paket Item Awal</h1>
        <div class="cr-subtitle">Diatur oleh lead Creative atau admin. Perubahan tidak mengubah turunan atau item yang sudah dibuat.</div>
    </div>
    <div class="cr-head-act"><a href="<?= base_url('creative') ?>" class="cr-btn"><i class="bi bi-arrow-left"></i>Kembali</a></div>
</div>

<?php if (session()->getFlashdata('success')): ?>
<div class="cr-flash cr-flash-ok" role="alert"><i class="bi bi-check-circle"></i><span><?= esc(session()->getFlashdata('success')) ?></span><button type="button" aria-label="Tutup" onclick="this.parentNode.remove()">&times;</button></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
<div class="cr-flash cr-flash-bad" role="alert"><i class="bi bi-exclamation-triangle"></i><span><?= esc(session()->getFlashdata('error')) ?></span><button type="button" aria-label="Tutup" onclick="this.parentNode.remove()">&times;</button></div>
<?php endif; ?>

<div class="cr-ptabs" id="katTabs" role="tablist" aria-label="Bagian">
    <button type="button" role="tab" data-kat-tab="turunan" class="<?= $tab === 'turunan' ? 'is-on' : '' ?>" aria-selected="<?= $tab === 'turunan' ? 'true' : 'false' ?>"><i class="bi bi-diagram-2"></i>Katalog Turunan <span class="n"><?= $nJenis ?></span></button>
    <button type="button" role="tab" data-kat-tab="paket" class="<?= $tab === 'paket' ? 'is-on' : '' ?>" aria-selected="<?= $tab === 'paket' ? 'true' : 'false' ?>"><i class="bi bi-boxes"></i>Paket Item Awal <span class="n"><?= $nPaket ?></span></button>
</div>

<!-- ═════════ Katalog turunan ═════════ -->
<div data-kat-pane="turunan" <?= $tab === 'turunan' ? '' : 'hidden' ?>>
    <?php foreach ($induks as $ik => [$iLabel, $iIcon]): $list = $katalog[$ik] ?? []; ?>
    <section class="cr-group t-<?= $ik === 'design' ? 'master_design' : 'motion_graphic' ?>">
        <div class="cr-group-h">
            <div class="cr-group-ico"><i class="bi <?= $iIcon ?>"></i></div>
            <h3 class="cr-group-t" style="font-size:.95rem"><?= $iLabel ?></h3>
            <span class="cr-group-n"><?= count($list) ?> jenis</span>
            <button type="button" class="cr-btn cr-btn-sm cr-btn-primary ms-auto" data-kat-jenis-baru="<?= $ik ?>"><i class="bi bi-plus-lg"></i><span>Tambah jenis</span></button>
        </div>
        <div class="cr-tbl-wrap">
        <table class="cr-tbl kat-tbl">
            <thead><tr>
                <th style="width:74px">Urut</th><th>Jenis</th><th>Kelompok</th><th>Ukuran baku</th>
                <?php if ($ik === 'motion_graphic'): ?><th>Durasi</th><?php endif; ?>
                <th class="r">Dipakai</th><th>Status</th><th style="width:150px"></th>
            </tr></thead>
            <tbody>
            <?php foreach ($list as $i => $j):
                $pakai = (int) ($dipakai[(int) $j['id']] ?? 0);
                $json = json_encode(['id' => (int) $j['id'], 'induk' => $j['induk'], 'kelompok' => $j['kelompok'], 'nama' => $j['nama'],
                    'bebas' => $j['_bebas'], 'opsi' => $j['_opsi'], 'durasi' => $j['_durasi']], JSON_UNESCAPED_UNICODE);
            ?>
            <tr id="jenis-<?= (int) $j['id'] ?>" class="<?= $j['aktif'] ? '' : 'kat-off' ?>">
                <td class="nw">
                    <form method="POST" action="<?= base_url('creative/katalog/jenis/' . $j['id'] . '/geser') ?>" class="d-inline"><?= csrf_field() ?><input type="hidden" name="arah" value="naik">
                        <button class="cr-iconbtn cr-iconbtn-sm" <?= $i === 0 ? 'disabled' : '' ?> title="Naikkan" aria-label="Naikkan <?= esc($j['nama'], 'attr') ?>"><i class="bi bi-arrow-up"></i></button></form>
                    <form method="POST" action="<?= base_url('creative/katalog/jenis/' . $j['id'] . '/geser') ?>" class="d-inline"><?= csrf_field() ?><input type="hidden" name="arah" value="turun">
                        <button class="cr-iconbtn cr-iconbtn-sm" <?= $i === count($list) - 1 ? 'disabled' : '' ?> title="Turunkan" aria-label="Turunkan <?= esc($j['nama'], 'attr') ?>"><i class="bi bi-arrow-down"></i></button></form>
                </td>
                <td class="fw-semibold"><?= esc($j['nama']) ?></td>
                <td><span class="cr-chip cr-chip-mute"><?= TS::KELOMPOK[$j['kelompok']] ?></span></td>
                <td>
                    <?php if ($j['_bebas']): ?><span class="cr-chip cr-chip-gold">Ukuran bebas</span>
                    <?php else: foreach ($j['_opsi'] as $o): ?><span class="cr-chip cr-chip-blue kat-ukuran"><?= esc($o['label']) ?></span> <?php endforeach; endif; ?>
                </td>
                <?php if ($ik === 'motion_graphic'): ?><td class="nw"><?= $j['_durasi'] ? esc(implode(' / ', $j['_durasi'])) . ' dtk' : '—' ?></td><?php endif; ?>
                <td class="r"><?= $pakai ?></td>
                <td><?= $j['aktif'] ? '<span class="cr-chip cr-chip-ok">Aktif</span>' : '<span class="cr-chip cr-chip-mute">Nonaktif</span>' ?></td>
                <td class="nw">
                    <button type="button" class="cr-btn cr-btn-xs" data-kat-jenis-ubah="<?= esc($json, 'attr') ?>"><i class="bi bi-pencil"></i>Ubah</button>
                    <form method="POST" action="<?= base_url('creative/katalog/jenis/' . $j['id'] . '/toggle') ?>" class="d-inline"><?= csrf_field() ?>
                        <button class="cr-btn cr-btn-xs" title="<?= $j['aktif'] ? 'Sembunyikan dari daftar centang' : 'Tampilkan lagi di daftar centang' ?>"><i class="bi bi-<?= $j['aktif'] ? 'eye-slash' : 'eye' ?>"></i><?= $j['aktif'] ? 'Nonaktifkan' : 'Aktifkan' ?></button></form>
                    <?php if ($pakai === 0): ?>
                    <form method="POST" action="<?= base_url('creative/katalog/jenis/' . $j['id'] . '/hapus') ?>" class="d-inline" onsubmit="return confirm('Hapus jenis ini?')"><?= csrf_field() ?>
                        <button class="cr-iconbtn cr-iconbtn-sm" title="Hapus (belum dipakai)" aria-label="Hapus <?= esc($j['nama'], 'attr') ?>"><i class="bi bi-trash"></i></button></form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </section>
    <?php endforeach; ?>
    <p class="cr-tema-note"><i class="bi bi-info-circle"></i>Jenis yang sudah dipakai turunan tidak bisa dihapus, hanya dinonaktifkan. Pilihan di daftar centang hanya memuat jenis berstatus Aktif.</p>
</div>

<!-- ═════════ Paket item awal ═════════ -->
<div data-kat-pane="paket" <?= $tab === 'paket' ? '' : 'hidden' ?>>
    <?php foreach (['bulanan' => 'Tema Bulanan', 'event' => 'Event'] as $kk => $kl): $list = $paket[$kk]; ?>
    <section class="cr-group t-master_design">
        <div class="cr-group-h">
            <div class="cr-group-ico"><i class="bi <?= $kk === 'event' ? 'bi-calendar-event' : 'bi-calendar-month' ?>"></i></div>
            <h3 class="cr-group-t" style="font-size:.95rem">Paket <?= $kl ?></h3>
            <span class="cr-group-n"><?= count($list) ?> baris</span>
            <button type="button" class="cr-btn cr-btn-sm cr-btn-primary ms-auto" data-kat-paket-baru="<?= $kk ?>"><i class="bi bi-plus-lg"></i><span>Tambah baris</span></button>
        </div>
        <div class="cr-tbl-wrap">
        <table class="cr-tbl kat-tbl">
            <thead><tr><th style="width:74px">Urut</th><th>Jenis item</th><th>Nama item</th><th>Jenis & ukuran</th><th>Platform</th><th>Status</th><th style="width:150px"></th></tr></thead>
            <tbody>
            <?php foreach ($list as $i => $p):
                $json = json_encode(['id' => (int) $p['id'], 'konteks' => $p['konteks'], 'tipe' => $p['tipe'], 'nama_pola' => $p['nama_pola'], 'platform' => $p['platform'], 'jenis_media' => $p['jenis_media'], 'ukuran' => $p['ukuran']], JSON_UNESCAPED_UNICODE);
            ?>
            <tr class="<?= $p['aktif'] ? '' : 'kat-off' ?>">
                <td class="nw">
                    <form method="POST" action="<?= base_url('creative/katalog/paket/' . $p['id'] . '/geser') ?>" class="d-inline"><?= csrf_field() ?><input type="hidden" name="arah" value="naik">
                        <button class="cr-iconbtn cr-iconbtn-sm" <?= $i === 0 ? 'disabled' : '' ?> title="Naikkan" aria-label="Naikkan <?= esc($p['nama_pola'], 'attr') ?>"><i class="bi bi-arrow-up"></i></button></form>
                    <form method="POST" action="<?= base_url('creative/katalog/paket/' . $p['id'] . '/geser') ?>" class="d-inline"><?= csrf_field() ?><input type="hidden" name="arah" value="turun">
                        <button class="cr-iconbtn cr-iconbtn-sm" <?= $i === count($list) - 1 ? 'disabled' : '' ?> title="Turunkan" aria-label="Turunkan <?= esc($p['nama_pola'], 'attr') ?>"><i class="bi bi-arrow-down"></i></button></form>
                </td>
                <td><span class="cr-chip cr-chip-mute"><?= esc($tipeLabel[$p['tipe']] ?? $p['tipe']) ?></span></td>
                <td class="fw-semibold"><?= esc($p['nama_pola']) ?></td>
                <td><?= $p['tipe'] === 'cetak' ? esc(trim(($p['jenis_media'] ?? '') . ($p['ukuran'] ? ' · ' . $p['ukuran'] : ''))) ?: '—' : '<span class="cr-c-mute">—</span>' ?></td>
                <td><?= $p['platform'] ? esc($platLabel[$p['platform']] ?? $p['platform']) : '—' ?></td>
                <td><?= $p['aktif'] ? '<span class="cr-chip cr-chip-ok">Aktif</span>' : '<span class="cr-chip cr-chip-mute">Nonaktif</span>' ?></td>
                <td class="nw">
                    <button type="button" class="cr-btn cr-btn-xs" data-kat-paket-ubah="<?= esc($json, 'attr') ?>"><i class="bi bi-pencil"></i>Ubah</button>
                    <form method="POST" action="<?= base_url('creative/katalog/paket/' . $p['id'] . '/toggle') ?>" class="d-inline"><?= csrf_field() ?>
                        <button class="cr-btn cr-btn-xs"><i class="bi bi-<?= $p['aktif'] ? 'eye-slash' : 'eye' ?>"></i><?= $p['aktif'] ? 'Nonaktifkan' : 'Aktifkan' ?></button></form>
                    <form method="POST" action="<?= base_url('creative/katalog/paket/' . $p['id'] . '/hapus') ?>" class="d-inline" onsubmit="return confirm('Hapus baris paket ini?')"><?= csrf_field() ?>
                        <button class="cr-iconbtn cr-iconbtn-sm" title="Hapus" aria-label="Hapus <?= esc($p['nama_pola'], 'attr') ?>"><i class="bi bi-trash"></i></button></form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (! $list): ?><tr><td colspan="7" class="cr-c-mute">Belum ada baris. Tema / event baru tidak akan mendapat item otomatis.</td></tr><?php endif; ?>
            </tbody>
        </table>
        </div>
    </section>
    <?php endforeach; ?>
    <p class="cr-tema-note"><i class="bi bi-info-circle"></i>Paket dibuat otomatis saat Tema Bulanan atau Event baru dibuat; Tema / event lama memakai tombol “Tambahkan paket awal”. Influencer dan Media Prescon tidak pernah otomatis.</p>
</div>

<!-- Modal jenis -->
<div class="modal fade" id="katJenisModal" tabindex="-1" aria-labelledby="katJenisTitle" aria-hidden="true">
<div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content">
<form method="POST" action="<?= base_url('creative/katalog/jenis/simpan') ?>" id="katJenisForm">
<?= csrf_field() ?><input type="hidden" name="id" id="kjId"><input type="hidden" name="induk" id="kjInduk">
<div class="modal-header"><h5 class="modal-title fw-semibold" id="katJenisTitle">Jenis turunan</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
<div class="modal-body">
    <div class="row g-2 mb-3">
        <div class="col-sm-7"><label class="form-label small fw-semibold" for="kjNama">Nama jenis <span class="text-danger">*</span></label><input type="text" name="nama" id="kjNama" class="form-control" required maxlength="120"></div>
        <div class="col-sm-5"><label class="form-label small fw-semibold" for="kjKel">Kelompok</label><select name="kelompok" id="kjKel" class="form-select"></select></div>
    </div>
    <div class="form-check mb-2"><input type="checkbox" class="form-check-input" name="bebas" id="kjBebas" value="1"><label class="form-check-label small fw-semibold" for="kjBebas">Ukuran bebas (diketik per pemakaian, mis. per layar / per lokasi)</label></div>
    <div id="kjOpsiWrap">
        <div class="small fw-semibold mb-1">Opsi ukuran baku</div>
        <div id="kjOpsi"></div>
        <button type="button" class="btn btn-sm btn-outline-secondary mt-1" id="kjTambahOpsi"><i class="bi bi-plus-lg"></i> Tambah opsi</button>
    </div>
    <div id="kjDurasiWrap" class="mt-3" hidden>
        <label class="form-label small fw-semibold" for="kjDurasi">Opsi durasi (detik, pisahkan koma)</label>
        <input type="text" name="durasi" id="kjDurasi" class="form-control" placeholder="6, 15, 30, 60">
    </div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
</form>
</div></div></div>

<!-- Modal paket -->
<div class="modal fade" id="katPaketModal" tabindex="-1" aria-labelledby="katPaketTitle" aria-hidden="true">
<div class="modal-dialog"><div class="modal-content">
<form method="POST" action="<?= base_url('creative/katalog/paket/simpan') ?>">
<?= csrf_field() ?><input type="hidden" name="id" id="kpId"><input type="hidden" name="konteks" id="kpKonteks">
<div class="modal-header"><h5 class="modal-title fw-semibold" id="katPaketTitle">Baris paket</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
<div class="modal-body">
    <div class="mb-3"><label class="form-label small fw-semibold" for="kpTipe">Jenis item</label>
        <select name="tipe" id="kpTipe" class="form-select"><?php foreach ($tipeLabel as $k => $l): ?><option value="<?= $k ?>"><?= $l ?></option><?php endforeach; ?></select></div>
    <div class="mb-3"><label class="form-label small fw-semibold" for="kpPola">Nama item <span class="text-danger">*</span></label>
        <input type="text" name="nama_pola" id="kpPola" class="form-control" required maxlength="200" placeholder="mis. Design"><div class="form-text">Cukup nama jenisnya; nama Tema tidak perlu ditulis karena item berada di dalam kartu Tema.</div></div>
    <div class="row g-2 mb-3" id="kpCetakDiv">
        <div class="col-6"><label class="form-label small fw-semibold" for="kpJenis">Jenis media</label><input type="text" name="jenis_media" id="kpJenis" class="form-control" maxlength="60" placeholder="mis. Poster"></div>
        <div class="col-6"><label class="form-label small fw-semibold" for="kpUkuran">Ukuran bawaan</label><input type="text" name="ukuran" id="kpUkuran" class="form-control" maxlength="80" placeholder="mis. A3 297×420 mm"></div>
    </div>
    <div class="mb-0" id="kpPlatDiv"><label class="form-label small fw-semibold" for="kpPlat">Platform (konten digital)</label>
        <select name="platform" id="kpPlat" class="form-select"><?php foreach ($platLabel as $k => $l): ?><option value="<?= $k ?>"><?= $l ?></option><?php endforeach; ?></select></div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
</form>
</div></div></div>

</div><!-- /.page-cre -->
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="<?= base_url('js/creative-katalog.js') ?>?v=<?= @filemtime(FCPATH . 'js/creative-katalog.js') ?: 1 ?>"></script>
<?= $this->endSection() ?>
