<?php
/**
 * Isian item Media Cetak: jenis media + ukuran (katalog cetak; ukuran boleh diketik bebas), periode pasang,
 * dan Design induk (hanya tampil bila Tema punya lebih dari satu Design; diisi JS dari window.CRE_DESIGNS).
 * Variabel: $cetakKatalog (CreativeTurunanService::cetakKatalog), $uid (awalan id unik), $jenisId / $ukuranId (opsional id input), $cls (kelas kolom, bawaan col-sm-6)
 */
$uid = $uid ?? 'x'; $cls = $cls ?? 'col-sm-6';
?>
<div class="<?= $cls ?>">
    <label class="form-label small fw-semibold" for="cj-<?= $uid ?>">Jenis media</label>
    <select name="jenis_media" id="<?= $jenisId ?? 'cj-' . $uid ?>" class="form-select js-cetak-jenis" data-list="cu-<?= $uid ?>">
        <option value="">— pilih jenis —</option>
        <?php foreach ($cetakKatalog as $j): ?><option value="<?= esc($j['nama'], 'attr') ?>"><?= esc($j['nama']) ?></option><?php endforeach; ?>
        <option value="Lainnya">Lainnya</option>
    </select>
</div>
<div class="<?= $cls ?>">
    <label class="form-label small fw-semibold" for="cuk-<?= $uid ?>">Ukuran</label>
    <input type="text" name="ukuran" id="<?= $ukuranId ?? 'cuk-' . $uid ?>" class="form-control js-cetak-ukuran" maxlength="80" list="cu-<?= $uid ?>" placeholder="pilih dari daftar atau ketik ukuran khusus" autocomplete="off">
    <datalist id="cu-<?= $uid ?>"></datalist>
</div>
<div class="<?= $cls ?>">
    <label class="form-label small fw-semibold" for="cpm-<?= $uid ?>">Pasang mulai</label>
    <input type="date" name="pasang_mulai" id="cpm-<?= $uid ?>" class="form-control js-cetak-pm">
</div>
<div class="<?= $cls ?>">
    <label class="form-label small fw-semibold" for="cps-<?= $uid ?>">Pasang selesai</label>
    <input type="date" name="pasang_selesai" id="cps-<?= $uid ?>" class="form-control js-cetak-ps">
</div>
<div class="col-12 js-cetak-design-wrap" hidden>
    <label class="form-label small fw-semibold" for="cdg-<?= $uid ?>">Design induk <span class="text-muted fw-normal">(Tema ini punya lebih dari satu Design)</span></label>
    <select name="design_ref" id="cdg-<?= $uid ?>" class="form-select js-cetak-design"></select>
</div>
<div class="col-12 js-cetak-hint ctk-hint" hidden></div>
