<?php
/* Kartu Tema. Variabel: $c (kartu dari CreativeTemaService::susun) + bersama index.php
   ($tipeConfig, $canEdit, $canClose, $rp, $fTgl, ...). */
use App\Services\CreativeTemaService;

if (! function_exists('mb_strtok_safe')) {
    /** Nama item rencana tanpa akhiran " – nama Tema" (untuk chip ringkas). */
    function mb_strtok_safe(string $nama): string { $p = explode(' – ', $nama, 2); return $p[0]; }
}
$kind   = $c['kind'];
$key    = $c['key'];
$closed = !empty($c['closed']);
$n      = (int) $c['n'];                 // tanpa item Rencana (dipakai untuk statistik)
$nRen   = (int) ($c['nRencana'] ?? 0);   // item dari paket awal yang belum dikerjakan
$nAll   = (int) ($c['nAll'] ?? $n);
$selesaiPct = $n > 0 ? (int) round($c['selesai'] / $n * 100) : 0;
$over   = $c['budget'] > 0 && $c['realisasi'] > $c['budget'];
$num    = fn($v) => number_format((int) $v, 0, ',', '.');

// Periode / keterangan
$meta = [];
if ($kind === 'event') {
    if ($c['start']) {
        $tgl = $fTgl($c['start'], false);
        if ($c['end'] && $c['end'] !== $c['start']) $tgl = $fTgl($c['start'], false) . ' – ' . $fTgl($c['end']);
        else $tgl = $fTgl($c['start']);
        $meta[] = '<span><i class="bi bi-calendar3"></i>' . esc($tgl) . '</span>';
    }
    if ($c['mall'] !== '') $meta[] = '<span><i class="bi bi-geo-alt"></i>' . esc(strtoupper($c['mall'])) . '</span>';
} elseif ($kind === 'bulanan') {
    $meta[] = '<span><i class="bi bi-calendar3"></i>' . esc(CreativeTemaService::bulanLabel($c['bulan'])) . '</span>';
}

$search = mb_strtolower($c['nama'] . ' ' . ($c['deskripsi'] ?? '') . ' ' . ($kind === 'event' ? 'event ' . $c['mall'] : ($kind === 'bulanan' ? 'bulanan' : 'tanpa tema')));
?>
<article class="cr-tema k-<?= $kind ?> <?= $closed ? 'is-closed' : '' ?>" id="tema-<?= $key ?>" data-tema="<?= $key ?>" data-kind="<?= $kind ?>" data-closed="<?= $closed ? '1' : '0' ?>"
    data-n="<?= $nAll ?>" data-search="<?= esc($search, 'attr') ?>" data-name="<?= esc($c['nama'], 'attr') ?>">
    <div class="cr-tema-head" data-cr-tema-toggle>
        <button type="button" class="cr-chev" aria-expanded="false" aria-label="Buka tema <?= esc($c['nama'], 'attr') ?>"><i class="bi bi-chevron-right"></i></button>

        <div class="cr-item-main">
            <div class="cr-item-line">
                <span class="cr-tema-name"><?= esc($c['nama']) ?></span>
                <?php if ($kind === 'event'): ?><span class="cr-chip cr-chip-gold"><i class="bi bi-calendar-event"></i>Event</span>
                <?php elseif ($kind === 'bulanan'): ?><span class="cr-chip cr-chip-acc"><i class="bi bi-calendar-month"></i>Bulanan</span>
                <?php else: ?><span class="cr-chip cr-chip-mute"><i class="bi bi-question-circle"></i>Belum bertema</span><?php endif; ?>
                <?php if ($closed): ?><span class="cr-chip cr-chip-mute"><i class="bi bi-lock-fill"></i>Ditutup</span><?php endif; ?>
            </div>
            <?php if ($meta): ?><div class="cr-item-meta"><?= implode('', $meta) ?></div><?php endif; ?>
            <div class="cr-tema-prog">
                <?php if ($nAll === 0): ?>
                    <span class="cr-tema-none">Belum ada item</span>
                <?php else: ?>
                    <?php if ($n > 0 && $kind === 'none'): ?>
                    <span><b><?= $n ?></b> item belum bertema · <b><?= $c['selesai'] ?></b> selesai</span>
                    <div class="cr-bar <?= $selesaiPct >= 100 ? 'cr-bar-ok' : '' ?>"><i style="width:<?= $selesaiPct ?>%"></i></div>
                    <?php elseif ($n > 0): ?>
                    <span><b><?= $c['selesai'] ?></b> dari <b><?= $n ?></b> selesai</span>
                    <div class="cr-bar <?= $selesaiPct >= 100 ? 'cr-bar-ok' : '' ?>"><i style="width:<?= $selesaiPct ?>%"></i></div>
                    <?php endif; ?>
                    <?php if ($nRen > 0): ?><span class="cr-rencana-n" title="Item dari paket awal yang belum dikerjakan; tidak dihitung di statistik"><i class="bi bi-lightbulb"></i><b><?= $nRen ?></b> item rencana</span><?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($c['budget'] > 0 || $c['realisasi'] > 0 || $c['reach'] > 0 || $c['impr'] > 0): ?>
        <div class="cr-tema-fig">
            <?php if ($c['budget'] > 0): ?><div><span class="cr-fig-l">Budget</span><b><?= $rp($c['budget']) ?></b></div><?php endif; ?>
            <?php if ($c['realisasi'] > 0): ?><div><span class="cr-fig-l">Realisasi</span><b class="<?= $over ? 'cr-c-bad' : 'cr-c-ok' ?>"><?= $rp($c['realisasi']) ?><?php if (! empty($c['fs'])): ?><i class="bi bi-box-seam cr-fsmark" title="Termasuk <?= $rp($c['fs']) ?> dari FlowStore (BTB tertaut)" aria-label="Termasuk <?= $rp($c['fs']) ?> dari FlowStore"></i><?php endif; ?></b></div><?php endif; ?>
            <?php if ($c['reach'] > 0): ?><div><span class="cr-fig-l">Reach</span><b><?= $num($c['reach']) ?></b></div><?php endif; ?>
            <?php if ($c['impr'] > 0): ?><div><span class="cr-fig-l">Impresi</span><b><?= $num($c['impr']) ?></b></div><?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="cr-tema-act" data-cr-notoggle>
            <?php if ($kind === 'event'): ?>
            <a href="<?= base_url('events/' . $c['id'] . '/creative') ?>" class="cr-btn cr-btn-sm cr-btn-warn cr-btn-ico" data-cr-isi-event title="Isi di event" aria-label="Isi di event"><i class="bi bi-box-arrow-up-right"></i><span>Isi di event</span></a>
            <?php elseif ($kind === 'bulanan' && $canEdit && !$closed): ?>
            <button type="button" class="cr-btn cr-btn-sm cr-btn-soft cr-btn-ico" data-cr-add-tema="<?= $key ?>" title="Tambah item" aria-label="Tambah item"><i class="bi bi-plus-lg"></i><span>Tambah item</span></button>
            <?php elseif ($kind === 'none' && $canEdit && $n > 0): ?>
            <a href="<?= base_url('creative/pemetaan') ?>" class="cr-btn cr-btn-sm cr-btn-primary"><i class="bi bi-diagram-3"></i><span>Susun tema</span></a>
            <?php endif; ?>

            <?php $adaMenu = ($kind === 'bulanan' && $canEdit && !$closed) || ($kind !== 'none' && $canClose); ?>
            <?php if ($adaMenu): ?>
            <div class="dropdown">
                <button type="button" class="cr-iconbtn cr-iconbtn-sm" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Aksi lain untuk tema <?= esc($c['nama'], 'attr') ?>"><i class="bi bi-three-dots-vertical"></i></button>
                <ul class="dropdown-menu dropdown-menu-end cr-menu">
                    <?php if ($kind === 'bulanan' && $canEdit && !$closed): ?>
                    <li><button type="button" class="dropdown-item edit-tema-btn" data-id="<?= $c['id'] ?>" data-nama="<?= esc($c['nama'], 'attr') ?>" data-bulan="<?= esc($c['bulan'], 'attr') ?>" data-deskripsi="<?= esc($c['deskripsi'], 'attr') ?>"><i class="bi bi-pencil"></i>Ubah tema</button></li>
                    <?php endif; ?>
                    <?php if ($kind === 'bulanan' && $canEdit && !$closed): ?>
                    <li>
                        <form method="POST" action="<?= base_url('creative/tema/paket-awal') ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="jenis" value="bulanan"><input type="hidden" name="id" value="<?= $c['id'] ?>">
                            <button type="submit" class="dropdown-item" title="Menambah item rencana dari paket awal; yang sudah ada tidak dobel"><i class="bi bi-boxes"></i>Tambahkan paket awal</button>
                        </form>
                    </li>
                    <?php endif; ?>
                    <?php if ($kind !== 'none' && $canClose): ?>
                    <li>
                        <form method="POST" action="<?= base_url('creative/tema/' . ($closed ? 'buka' : 'tutup')) ?>"
                              <?php if (!$closed && $nRen > 0): ?>data-cr-tutup-rencana="<?= $nRen ?>" data-nama="<?= esc($c['nama'], 'attr') ?>"
                              <?php else: ?>onsubmit="return confirm('<?= $closed ? 'Buka kembali tema ini? Materinya bisa diubah lagi.' : 'Tutup tema ini? Seluruh materinya akan terkunci dan tema dipindah ke tab Terkunci.' ?>')"<?php endif; ?>>
                            <?= csrf_field() ?>
                            <input type="hidden" name="jenis" value="<?= $kind ?>"><input type="hidden" name="id" value="<?= $c['id'] ?>">
                            <button type="submit" class="dropdown-item"><i class="bi <?= $closed ? 'bi-unlock' : 'bi-lock' ?>"></i><?= $closed ? 'Buka kembali tema' : 'Tutup tema' ?></button>
                        </form>
                    </li>
                    <?php endif; ?>
                    <?php if ($kind === 'bulanan' && $canEdit && !$closed && $nAll === 0): ?>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="<?= base_url('creative/tema/' . $c['id'] . '/delete') ?>" onsubmit="return confirm('Hapus tema ini?')">
                            <?= csrf_field() ?>
                            <button type="submit" class="dropdown-item text-danger"><i class="bi bi-trash"></i>Hapus tema</button>
                        </form>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="cr-tema-body">
        <?php if (($c['deskripsi'] ?? '') !== ''): ?><p class="cr-tema-note"><i class="bi bi-card-text"></i><?= esc($c['deskripsi']) ?></p><?php endif; ?>
        <?php if ($closed): ?>
        <p class="cr-tema-note"><i class="bi bi-lock-fill"></i>Ditutup<?= $c['closed_by'] ? ' oleh ' . esc($c['closed_by']) : '' ?><?= $c['closed_at'] ? ' pada ' . esc($fTgl(substr($c['closed_at'], 0, 10))) : '' ?>. Materi di dalamnya terkunci sampai tema dibuka kembali.</p>
        <?php endif; ?>
        <?php if ($kind === 'event' && $n > 0): ?>
        <p class="cr-tema-note"><i class="bi bi-info-circle"></i>Item event dikerjakan di tab Creative event dan tampil baca-saja di sini.</p>
        <?php endif; ?>

        <?php if ($nAll === 0): ?>
        <div class="cr-tema-empty">
            <?= $kind === 'event' ? 'Belum ada item Creative untuk event ini. Isi di tab Creative event.' : ($kind === 'bulanan' ? 'Belum ada item di tema ini.' . ($canEdit && !$closed ? ' Klik “Tambah item”.' : '') : 'Semua item sudah punya tema.') ?>
        </div>
        <?php endif; ?>

        <?php
        // Tema yang isinya hanya item Rencana: tampil ringkas (satu baris chip), daftar lengkap dibuka lewat panah
        $hanyaRencana = $n === 0 && $nRen > 0;
        if ($hanyaRencana): ?>
        <details class="cr-ren">
            <summary><span class="cr-ren-t"><i class="bi bi-lightbulb"></i><?= $nRen ?> item rencana</span>
                <?php foreach (CreativeTemaService::TIPE_URUT as $tp): foreach (($c['byTipe'][$tp] ?? []) as $ri): ?>
                <span class="cr-ren-chip t-<?= $tp ?>"><i class="bi <?= $tipeConfig[$tp]['icon'] ?>"></i><?= esc(mb_strtok_safe($ri['nama'])) ?></span>
                <?php endforeach; endforeach; ?>
                <span class="cr-ren-open">Buka daftar <i class="bi bi-chevron-down"></i></span></summary>
        <?php endif; ?>
        <?php foreach (CreativeTemaService::TIPE_URUT as $tipe):
            $list = $c['byTipe'][$tipe] ?? [];
            if (!$list) continue;
            $cfg = $tipeConfig[$tipe];
            $gid = $key . '-' . $tipe;
        ?>
        <section class="cr-group t-<?= $tipe ?>" data-tipe="<?= $tipe ?>" data-gid="<?= $gid ?>" id="grup-<?= $gid ?>">
            <?php $cols = \App\Services\CreativeApprovalService::isDesain($tipe) ? ['Putaran · opsi · keputusan'] : ($tipe === 'digital' ? ['Budget', 'Realisasi', 'Reach'] : ['Budget', 'Realisasi', 'Serapan']); ?>
            <div class="cr-group-h">
                <div class="cr-group-l">
                    <div class="cr-group-ico"><i class="bi <?= $cfg['icon'] ?>"></i></div>
                    <h3 class="cr-group-t"><?= $cfg['label'] ?></h3>
                    <span class="cr-group-n"><span class="js-gcount"><?= count($list) ?></span> item</span>
                </div>
                <span class="cr-colhead-fig<?= count($cols) === 1 ? ' is-md' : '' ?>" aria-hidden="true"><?php foreach ($cols as $cl): ?><span><?= $cl ?></span><?php endforeach; ?></span>
            </div>
            <div class="cr-list">
            <?php foreach ($list as $ord => $item): ?>
                <?= view('creative_main/_item_card', ['item' => $item, 'ord' => $ord] + $shared, ['saveData' => false]) ?>
            <?php endforeach; ?>
            </div>
            <div class="cr-more" hidden>
                <button type="button" class="cr-btn cr-btn-sm" data-cr-more><i class="bi bi-chevron-down"></i><span>Tampilkan lebih banyak</span></button>
                <button type="button" class="cr-btn cr-btn-sm" data-cr-all>Tampilkan semua</button>
            </div>
        </section>
        <?php endforeach; ?>
        <?php if ($hanyaRencana): ?></details><?php endif; ?>
    </div>
</article>
