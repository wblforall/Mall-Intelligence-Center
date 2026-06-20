<?php
// Banner keterangan sampai tanggal berapa data SPI sudah masuk.
// Butuh $dataUntil (Y-m-d|null).
$du = $dataUntil ?? null;
if ($du):
    $ts  = strtotime($du);
    $lag = (int) floor((strtotime(date('Y-m-d')) - $ts) / 86400);
    $cls = $lag <= 1 ? 'alert-success' : ($lag <= 4 ? 'alert-warning' : 'alert-danger');
?>
<div class="alert <?= $cls ?> py-2 px-3 small mb-3 d-flex align-items-center gap-2">
    <i class="bi bi-clock-history"></i>
    <span>Data historis SPI sudah masuk s/d <strong><?= date('d M Y', $ts) ?></strong>
    <?php if ($lag > 0): ?>(H-<?= $lag ?> — ada jeda input ~<?= $lag ?> hari, data setelah tanggal itu belum tersedia)<?php else: ?>(terkini)<?php endif; ?>.</span>
</div>
<?php endif; ?>
