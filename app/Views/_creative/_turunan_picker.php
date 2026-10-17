<?php
/**
 * Modal daftar centang turunan (satu per halaman). Isi dipilih per induk lewat JS (creative-turunan.js):
 * tombol [data-ct-pick] mengisi action form, nama item, dan menandai pilihan yang sudah ada.
 *
 * Variabel: $katalog (['design'=>[jenis...],'motion_graphic'=>[...]] dari CreativeTurunanService::katalog), $pfx ('cr'|'ev')
 */
use App\Services\CreativeTurunanService as TS;

$induks = [
    'design'         => ['Design', 'bi-vector-pen'],
    'motion_graphic' => ['Motion Graphic', 'bi-film'],
];
?>
<div class="modal fade ct-modal" id="turunanPickModal" tabindex="-1" aria-labelledby="turunanPickTitle" aria-hidden="true">
<div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content">
<form id="turunanPickForm" method="POST" action="">
<?= csrf_field() ?>
<div class="modal-header">
    <div class="min-w-0">
        <h5 class="modal-title fw-semibold" id="turunanPickTitle">Pilih turunan</h5>
        <div class="small text-muted text-truncate" id="turunanPickNama"></div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
</div>
<div class="modal-body">
    <div class="ct-tools">
        <div class="ct-search"><i class="bi bi-search"></i><input type="search" id="ctQ" placeholder="Cari jenis atau ukuran…" autocomplete="off" aria-label="Cari turunan"></div>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="ctToggleAll"><i class="bi bi-arrows-expand"></i> Buka semua</button>
    </div>

    <?php foreach ($induks as $ik => [$iLabel, $iIcon]): $list = $katalog[$ik] ?? []; ?>
    <div class="ct-induk" data-ct-induk="<?= $ik ?>" hidden>
        <?php if (! $list): ?>
        <div class="ct-empty">Katalog <?= $iLabel ?> kosong atau semua jenisnya dinonaktifkan. Atur di halaman Katalog &amp; Paket.</div>
        <?php endif; ?>
        <?php
        $per = ['digital' => [], 'cetak' => [], 'video' => []];
        foreach ($list as $j) $per[$j['kelompok']][] = $j;
        foreach ($per as $kel => $js): if (! $js) continue; ?>
        <details class="ct-pg" open>
            <summary><i class="bi <?= ['digital' => 'bi-phone', 'cetak' => 'bi-printer', 'video' => 'bi-camera-reels'][$kel] ?>"></i><?= TS::KELOMPOK[$kel] ?><span class="ct-count"><?= count($js) ?> jenis</span></summary>
            <?php foreach ($js as $j): $jid = (int) $j['id']; ?>
            <div class="ct-jenis" data-ct-jenis data-jid="<?= $jid ?>" data-has-dur="<?= $j['_durasi'] ? 1 : 0 ?>"
                 data-q="<?= esc(mb_strtolower($j['nama'] . ' ' . implode(' ', array_column($j['_opsi'], 'label')) . ' ' . TS::KELOMPOK[$kel]), 'attr') ?>">
                <div class="ct-jn"><?= esc($j['nama']) ?><?php if ($j['_bebas']): ?><span class="ct-tag">ukuran bebas</span><?php endif; ?></div>
                <div class="ct-opts">
                    <?php foreach ($j['_opsi'] as $idx => $o): ?>
                    <label class="ct-opt" data-label="<?= esc($o['label'], 'attr') ?>">
                        <input type="checkbox" name="pilih[]" value="<?= $jid ?>:<?= $idx ?>"><span><?= esc($o['label']) ?></span>
                    </label>
                    <?php endforeach; ?>
                    <input type="text" name="lain[<?= $jid ?>]" class="form-control form-control-sm ct-lain" maxlength="300"
                           placeholder="<?= $j['_bebas'] ? 'Ukuran, mis. 3x1 m (pisahkan koma bila banyak)' : 'Ukuran lain (pisahkan koma)' ?>" aria-label="Ukuran lain untuk <?= esc($j['nama'], 'attr') ?>">
                </div>
                <?php if ($j['_durasi']): ?>
                <div class="ct-durs"><span>Durasi</span>
                    <?php foreach ($j['_durasi'] as $d): ?>
                    <label class="ct-opt ct-opt-dur"><input type="checkbox" name="dur[<?= $jid ?>][]" value="<?= (int) $d ?>"><span><?= (int) $d ?> dtk</span></label>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </details>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>

    <div class="ct-nomatch" id="ctNoMatch" hidden>Tidak ada jenis yang cocok dengan pencarian.</div>

    <details class="ct-pg ct-kustom">
        <summary><i class="bi bi-plus-circle"></i>Turunan kustom (di luar katalog)</summary>
        <div class="ct-kgrid">
            <label class="ct-f"><span>Nama</span><input type="text" name="k_nama" class="form-control form-control-sm" maxlength="160" placeholder="mis. Floor Standing 3D"></label>
            <label class="ct-f"><span>Kelompok</span>
                <select name="k_kelompok" class="form-select form-select-sm">
                    <?php foreach (TS::KELOMPOK as $k => $l): ?><option value="<?= $k ?>"><?= $l ?></option><?php endforeach; ?>
                </select></label>
            <label class="ct-f"><span>Ukuran (pisahkan koma)</span><input type="text" name="k_ukuran" class="form-control form-control-sm" maxlength="300" placeholder="mis. 2x1 m"></label>
            <label class="ct-f"><span>Durasi (dtk, bila video)</span><input type="number" name="k_durasi" class="form-control form-control-sm" min="1" max="3600"></label>
        </div>
    </details>
</div>
<div class="modal-footer">
    <span class="ct-sum" id="ctSum" aria-live="polite">Belum ada yang dipilih</span>
    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
    <button type="submit" class="btn btn-primary" id="ctSubmit" disabled><i class="bi bi-plus-lg me-1"></i>Buat turunan terpilih</button>
</div>
</form>
</div></div></div>
