<?php
/**
 * Bagian "Bukti pasang (per titik)" pada realisasi Media Cetak: foto + tanggal pasang + catatan per titik terpilih,
 * dengan indikator "N dari M titik terpasang". Variabel: $iid, $item, $titik, $pfx ('cr'|'ev'), $scope, $canEdit.
 */
$rows   = $titik['rows'] ?? [];
$total  = (int) ($titik['total'] ?? 0);
$pasang = (int) ($titik['terpasang'] ?? 0);
if ($total === 0) return;
$pct    = (int) round($pasang / $total * 100);
$aksi   = base_url('creative/titik/' . $scope . '/');
$imgExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
?>
<div class="tk tk-bukti" data-tk-bukti>
    <div class="tk-head">
        <div class="tk-title"><i class="bi bi-camera"></i>Bukti pasang (per titik)
            <span class="<?= $pfx ?>-chip <?= $pasang === $total ? $pfx . '-chip-ok' : $pfx . '-chip-mute' ?>"><?= $pasang ?> dari <?= $total ?> titik terpasang</span></div>
        <div class="tk-prog" aria-hidden="true"><div class="<?= $pfx ?>-bar <?= $pasang === $total ? $pfx . '-bar-ok' : '' ?>"><i style="width:<?= $pct ?>%"></i></div></div>
    </div>
    <div class="tk-table">
        <div class="tk-th" aria-hidden="true"><span>Titik</span><span>Tanggal pasang</span><span>Catatan</span><span>Foto</span><span></span></div>
        <?php foreach ($rows as $r):
            $lid = (int) $r['id'];
            $file = (string) ($r['bukti_pasang_file'] ?? '');
            $url  = $file !== '' ? base_url('uploads/creative-cetak-titik/' . $lid . '/' . $file) : '';
            $ext  = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        ?>
        <form method="POST" action="<?= $aksi . $lid ?>/bukti" enctype="multipart/form-data" class="tk-row <?= ! empty($r['tanggal_pasang']) ? 'is-pasang' : '' ?>" data-tk-row>
            <?= csrf_field() ?>
            <div class="tk-c tk-c-kode" title="<?= esc(trim($r['spot_nama'] . ' · ' . $r['spot_area']), 'attr') ?>"><b><?= esc($r['spot_kode']) ?></b><span><?= esc((string) $r['spot_area']) ?></span></div>
            <div class="tk-c tk-c-tg" data-l="Tanggal pasang"><input type="date" name="tanggal_pasang" value="<?= esc((string) $r['tanggal_pasang'], 'attr') ?>" class="form-control form-control-sm" <?= $canEdit ? '' : 'disabled' ?> aria-label="Tanggal pasang titik <?= esc($r['spot_kode'], 'attr') ?>"></div>
            <div class="tk-c tk-c-cat" data-l="Catatan"><input type="text" name="catatan" value="<?= esc((string) $r['catatan'], 'attr') ?>" maxlength="500" class="form-control form-control-sm" placeholder="Opsional" <?= $canEdit ? '' : 'disabled' ?> aria-label="Catatan titik <?= esc($r['spot_kode'], 'attr') ?>"></div>
            <div class="tk-c tk-c-foto" data-l="Foto">
                <?php if ($url): ?>
                <a class="tk-fi" href="<?= $url ?>" target="_blank" rel="noopener" title="<?= esc((string) $r['bukti_pasang_asli'], 'attr') ?>" aria-label="Buka foto bukti pasang <?= esc($r['spot_kode'], 'attr') ?>">
                    <?php if (in_array($ext, $imgExt, true)): ?><img src="<?= $url ?>" alt="" loading="lazy"><?php else: ?><i class="bi bi-file-earmark-image"></i><?php endif; ?></a>
                <?php else: ?><span class="tk-nil">—</span><?php endif; ?>
                <?php if ($canEdit): ?>
                <label class="tk-ib" title="<?= $url ? 'Ganti foto' : 'Unggah foto bukti pasang' ?> (otomatis tersimpan)"><i class="bi bi-camera"></i>
                    <input type="file" name="berkas" accept="image/*" hidden data-tk-file aria-label="Unggah foto bukti pasang <?= esc($r['spot_kode'], 'attr') ?>"></label>
                <?php endif; ?>
            </div>
            <div class="tk-c tk-c-act">
                <?php if ($canEdit): ?>
                <button type="submit" class="tk-ib tk-ib-soft" title="Simpan tanggal dan catatan" aria-label="Simpan titik <?= esc($r['spot_kode'], 'attr') ?>"><i class="bi bi-check2"></i></button>
                <?php if (! empty($r['tanggal_pasang']) || $file !== ''): ?>
                <button type="submit" class="tk-ib tk-ib-bad" formaction="<?= $aksi . $lid ?>/bukti-hapus" formnovalidate data-tk-konfirmasi="Hapus bukti pasang titik <?= esc($r['spot_kode'], 'attr') ?> (foto, tanggal, catatan)?" title="Hapus bukti pasang" aria-label="Hapus bukti pasang <?= esc($r['spot_kode'], 'attr') ?>"><i class="bi bi-trash"></i></button>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </form>
        <?php endforeach; ?>
    </div>
</div>
