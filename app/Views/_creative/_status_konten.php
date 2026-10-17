<?php
/**
 * Status konten Konten Digital: Rencana -> Produksi -> Tayang -> Dilaporkan (langkah-langkah + tombol Mundur / Maju).
 * Dipakai di /creative (item standalone; item event baca-saja) dan tab Creative event.
 * Variabel: $item, $iid, $nIns (jumlah insight), $pfx ('cr'|'ev'), $aksi (URL POST status), $canEdit (sudah memperhitungkan Tema ditutup), $readOnly
 */
use App\Services\CreativeKontenService as KN;

$kn      = KN::tampil($item, (int) $nIns);
$urut    = array_keys(KN::STATUS);
$idx     = $kn['diatur'] ? array_search($kn['key'], $urut, true) : -1;
$rencana = ! empty($item['dari_paket']);
$bisa    = $canEdit && ! $readOnly && ! $rencana;
?>
<div class="kn" data-kn>
    <ol class="kn-steps" aria-label="Status konten">
        <?php foreach ($urut as $i => $k):
            $cls = $kn['diatur'] ? ($i < $idx ? 'is-lalu' : ($i === $idx ? 'is-now' : '')) : ($kn['turunan'] && $k === 'dilaporkan' ? 'is-now is-turunan' : '');
        ?>
        <li class="kn-step <?= $cls ?>"<?= $cls !== '' && strpos($cls, 'is-now') !== false ? ' aria-current="step"' : '' ?>><span class="kn-dot"><?= $i + 1 ?></span><span class="kn-lbl"><?= KN::STATUS[$k] ?></span></li>
        <?php endforeach; ?>
    </ol>
    <?php if (! $kn['diatur']): ?>
    <p class="kn-note"><i class="bi bi-info-circle"></i><?= $kn['turunan']
        ? 'Status belum diatur. Ditampilkan sebagai Dilaporkan karena sudah ada insight; belum tersimpan.'
        : 'Status belum diatur (item lama).' ?><?= $rencana ? ' Isi dulu lewat Ubah item.' : '' ?></p>
    <?php endif; ?>
    <?php if ($bisa): ?>
    <form method="POST" action="<?= esc($aksi, 'attr') ?>" class="kn-act" data-kn-status data-insight="<?= (int) $nIns ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="status" value=""><input type="hidden" name="alasan" value="">
        <?php if ($kn['diatur']): ?>
            <?php if ($idx > 0): ?><button type="submit" class="<?= $pfx ?>-btn <?= $pfx ?>-btn-sm" data-status="<?= $urut[$idx - 1] ?>"><i class="bi bi-arrow-left"></i>Mundur ke <?= KN::STATUS[$urut[$idx - 1]] ?></button><?php endif; ?>
            <?php if ($idx < count($urut) - 1): ?><button type="submit" class="<?= $pfx ?>-btn <?= $pfx ?>-btn-sm <?= $pfx ?>-btn-primary" data-status="<?= $urut[$idx + 1] ?>">Maju ke <?= KN::STATUS[$urut[$idx + 1]] ?><i class="bi bi-arrow-right"></i></button><?php endif; ?>
        <?php else: ?>
            <span class="kn-atur">Atur ke:</span>
            <?php foreach ($urut as $k): ?><button type="submit" class="<?= $pfx ?>-btn <?= $pfx ?>-btn-sm" data-status="<?= $k ?>"><?= KN::STATUS[$k] ?></button><?php endforeach; ?>
        <?php endif; ?>
    </form>
    <?php endif; ?>
</div>
