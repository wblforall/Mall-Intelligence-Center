<?php
// Usage: <?= view('legal/partials/expiry_badge', ['date' => $row['tanggal_berakhir']]) ?>
$today = new DateTime();
$exp   = $date ? new DateTime($date) : null;
$diff  = $exp ? (int)$today->diff($exp)->format('%r%a') : null;

if ($exp === null) {
    echo '<span class="badge bg-secondary-subtle text-secondary">Berlaku Tetap</span>';
} elseif ($diff < 0) {
    echo '<span class="badge bg-danger-subtle text-danger">Expired ' . abs($diff) . ' hari lalu</span>';
} elseif ($diff <= 7) {
    echo '<span class="badge bg-danger-subtle text-danger"><i class="bi bi-exclamation-triangle-fill me-1"></i>H-' . $diff . '</span>';
} elseif ($diff <= 30) {
    echo '<span class="badge bg-warning-subtle text-warning"><i class="bi bi-clock me-1"></i>H-' . $diff . '</span>';
}
