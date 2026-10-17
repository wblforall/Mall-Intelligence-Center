<?php
/**
 * Panel "Titik" pada item Media Cetak: titik terpilih dari master Media Promo (= pemesanan terhubung) dan tombol "Pilih titik".
 * Dipakai di /creative (kartu item standalone, baca-saja untuk item event) dan tab Creative event.
 *
 * Variabel:
 *   $iid, $item            id + baris item Media Cetak
 *   $titik                 ['rows'=>[], 'total'=>n, 'terpasang'=>n] dari CreativeTitikService::untukItems
 *   $pfx                   'cr' | 'ev'
 *   $scope                 'standalone' | 'event'
 *   $canEdit               boleh mengubah (sudah memperhitungkan Tema ditutup)
 *   $readOnly, $isiDiEvent item event di /creative: hanya tampil
 *   $temaTutup             bool
 */
use App\Services\CreativeTitikService as TK;

$rows    = $titik['rows'] ?? [];
$total   = (int) ($titik['total'] ?? 0);
$pasang  = (int) ($titik['terpasang'] ?? 0);
$tipeSpot = TK::tipeSpot($item['jenis_media'] ?? '');
$mulai   = (string) ($item['pasang_mulai'] ?? ''); $selesai = (string) ($item['pasang_selesai'] ?? '');
$periodeOk = $mulai !== '' && $selesai !== '' && $mulai <= $selesai;
$rencana = ! empty($item['dari_paket']);
$aksi    = base_url('creative/titik/' . $scope . '/');
$bln     = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
$fTk     = function (?string $d) use ($bln): string { if (! $d) return ''; $t = strtotime($d); return date('d', $t) . ' ' . $bln[(int) date('n', $t) - 1] . ' ' . date('Y', $t); };
$bisaPilih = $canEdit && ! $readOnly && $tipeSpot && $periodeOk && ! $rencana;
?>
<div class="tk" data-tk>
    <div class="tk-head">
        <div class="tk-title"><i class="bi bi-geo-alt"></i>Titik Media Promo
            <?php if ($total > 0): ?><span class="<?= $pfx ?>-chip <?= $pasang === $total ? $pfx . '-chip-ok' : $pfx . '-chip-mute' ?>" title="Titik yang sudah ditandai terpasang"><?= $pasang ?> dari <?= $total ?> terpasang</span><?php endif; ?>
            <?php if ($periodeOk): ?><span class="<?= $pfx ?>-chip <?= $pfx ?>-chip-blue" title="Periode pasang"><i class="bi bi-calendar-range"></i><?= esc($fTk($mulai)) ?> – <?= esc($fTk($selesai)) ?></span><?php endif; ?>
        </div>
        <div class="tk-act">
            <?php if ($readOnly): ?>
            <a href="<?= esc($isiDiEvent, 'attr') ?>" class="<?= $pfx ?>-btn <?= $pfx ?>-btn-sm <?= $pfx ?>-btn-warn"><i class="bi bi-box-arrow-up-right"></i><span>Isi di event</span></a>
            <?php elseif ($bisaPilih): ?>
            <button type="button" class="<?= $pfx ?>-btn <?= $pfx ?>-btn-sm <?= $pfx ?>-btn-primary" data-tp-open
                    data-daftar="<?= esc($aksi . $iid . '/daftar', 'attr') ?>" data-action="<?= esc($aksi . $iid . '/pilih', 'attr') ?>"
                    data-nama="<?= esc($item['nama'], 'attr') ?>" data-jenis="<?= esc(($item['jenis_media'] ?? '') . ' ' . ($item['ukuran'] ?? ''), 'attr') ?>">
                <i class="bi bi-ui-checks-grid"></i><span>Pilih titik</span>
            </button>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($temaTutup && $total > 0): ?>
    <p class="tk-note"><i class="bi bi-lock-fill"></i>Tema sudah ditutup; titik, pemesanan dan bukti pasang terkunci.</p>
    <?php endif; ?>

    <?php if (! $tipeSpot): ?>
    <p class="tk-note"><i class="bi bi-info-circle"></i><?= ($item['jenis_media'] ?? '') !== ''
        ? 'Jenis “' . esc($item['jenis_media']) . '” tidak punya titik di master Media Promo, jadi titik tidak diperlukan.'
        : 'Jenis media belum diisi. Isi jenis media, ukuran dan periode pasang lewat “Ubah item”; titik bisa dipilih untuk T-Banner, Hanging Banner, Sticker Lift dan Totem.' ?></p>
    <?php elseif ($rencana && ! $readOnly): ?>
    <p class="tk-note"><i class="bi bi-lightbulb"></i>Item masih berstatus Rencana. Lengkapi lewat “Ubah item” (budget wajib diisi), lalu pilih titik.</p>
    <?php elseif (! $periodeOk && ! $readOnly && $canEdit): ?>
    <p class="tk-note"><i class="bi bi-calendar-range"></i>Isi periode pasang (mulai dan selesai) lewat “Ubah item” dulu agar ketersediaan titik bisa dihitung.</p>
    <?php endif; ?>

    <?php if ($total === 0 && $tipeSpot): ?>
    <div class="tk-empty">Belum ada titik terpilih.<?= $readOnly ? ' Titik dikelola di tab Creative event.' : '' ?></div>
    <?php endif; ?>

    <?php if ($rows): ?>
    <div class="tk-chips">
        <?php foreach ($rows as $r):
            $lid = (int) $r['id'];
            $terpasang = ! empty($r['tanggal_pasang']);
            $selesaiPakai = ($r['usage_status'] ?? '') === 'done';
            $judul = trim(($r['spot_nama'] ?? '') . ' · ' . ($r['spot_area'] ?? '') . ' · ' . ($r['spot_ukuran'] ?? '')) . ' — pemesanan Media Promo ' . ($selesaiPakai ? 'selesai' : 'disetujui');
        ?>
        <span class="tk-chip <?= $terpasang ? 'is-pasang' : '' ?> <?= $selesaiPakai ? 'is-done' : '' ?>" title="<?= esc($judul, 'attr') ?>">
            <i class="bi <?= $terpasang ? 'bi-check-circle-fill' : 'bi-geo-alt' ?>" aria-hidden="true"></i>
            <b><?= esc($r['spot_kode']) ?></b><em><?= esc((string) $r['spot_area']) ?></em>
            <?php if ($canEdit && ! $readOnly && ! $selesaiPakai): ?>
            <form method="POST" action="<?= $aksi . $lid ?>/cabut" class="tk-x" data-tk-cabut data-kode="<?= esc($r['spot_kode'], 'attr') ?>" data-terpasang="<?= $terpasang ? '1' : '0' ?>"><?= csrf_field() ?>
                <input type="hidden" name="konfirmasi" value="<?= $terpasang ? '1' : '' ?>">
                <button type="submit" class="tk-xb" title="Cabut titik <?= esc($r['spot_kode'], 'attr') ?>" aria-label="Cabut titik <?= esc($r['spot_kode'], 'attr') ?>"><i class="bi bi-x-lg"></i></button></form>
            <?php endif; ?>
        </span>
        <?php endforeach; ?>
    </div>
    <p class="tk-note tk-note-s"><i class="bi bi-link-45deg"></i>Tiap titik adalah pemesanan di <a href="<?= base_url('creative/media-promo/gantt') ?>">Media Promo</a> (dept Creative, langsung disetujui). Mencabut titik menghapus pemesanannya.</p>
    <?php endif; ?>
</div>
