<?php
/**
 * Isian Konten Digital: FORMAT konten (katalog Design kelompok digital + "Lainnya"), ukuran, periode tayang di layar
 * (hanya bila item punya titik layar) dan Design induk (tampil bila Tema punya lebih dari satu Design).
 * Nama isian sengaja berbeda dari Media Cetak agar tidak bentrok di modal yang sama:
 *   fmt_jenis, fmt_jenis_lain, fmt_ukuran, tayang_mulai, tayang_selesai, design_ref_d.
 * Variabel: $uid (awalan id unik), $cls (kelas kolom, bawaan col-sm-6), $digitalKatalog (CreativeTurunanService::digitalKatalog)
 */
$uid = $uid ?? 'x'; $cls = $cls ?? 'col-sm-6';
?>
<div class="<?= $cls ?>">
    <label class="form-label small fw-semibold" for="dj-<?= $uid ?>">Format konten</label>
    <select name="fmt_jenis" id="dj-<?= $uid ?>" class="form-select js-dig-jenis" data-list="du-<?= $uid ?>">
        <option value="">— pilih format —</option>
        <?php foreach ($digitalKatalog as $j): ?><option value="<?= esc($j['nama'], 'attr') ?>"><?= esc($j['nama']) ?></option><?php endforeach; ?>
        <option value="__lain">Lainnya (ketik sendiri)</option>
    </select>
</div>
<div class="<?= $cls ?>">
    <label class="form-label small fw-semibold" for="duk-<?= $uid ?>">Ukuran</label>
    <input type="text" name="fmt_ukuran" id="duk-<?= $uid ?>" class="form-control js-dig-ukuran" maxlength="80" list="du-<?= $uid ?>" placeholder="pilih dari daftar atau ketik ukuran" autocomplete="off">
    <datalist id="du-<?= $uid ?>"></datalist>
</div>
<div class="col-12 js-dig-lain-wrap" hidden>
    <label class="form-label small fw-semibold" for="dl-<?= $uid ?>">Nama format</label>
    <input type="text" name="fmt_jenis_lain" id="dl-<?= $uid ?>" class="form-control js-dig-lain" maxlength="60" placeholder="mis. Highlight Instagram">
</div>
<div class="col-12 js-dig-hint dg-hint" hidden></div>
<div class="<?= $cls ?>">
    <label class="form-label small fw-semibold" for="dtm-<?= $uid ?>">Tayang di layar: mulai <span class="text-muted fw-normal">(opsional)</span></label>
    <input type="date" name="tayang_mulai" id="dtm-<?= $uid ?>" class="form-control js-dig-tm">
</div>
<div class="<?= $cls ?>">
    <label class="form-label small fw-semibold" for="dts-<?= $uid ?>">Tayang di layar: selesai</label>
    <input type="date" name="tayang_selesai" id="dts-<?= $uid ?>" class="form-control js-dig-ts">
</div>
<div class="col-12 js-dig-design-wrap" hidden>
    <label class="form-label small fw-semibold" for="ddg-<?= $uid ?>">Design induk <span class="text-muted fw-normal">(Tema ini punya lebih dari satu Design)</span></label>
    <select name="design_ref_d" id="ddg-<?= $uid ?>" class="form-select js-dig-design"></select>
</div>
