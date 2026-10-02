<?php
/**
 * Pemilih PERIODE bersama (dashboard / per karyawan / per komputer):
 * 7 hari · 30 hari · pilih bulan, plus tombol "Cetak Laporan Bulanan".
 *
 * Butuh:
 *   $periode     array dari AiMonitor::resolvePeriode()
 *   $printScope  (opsional) query tambahan untuk rute cetak, mis.
 *                '&employee_id=5' atau '&device_id=3'. KOSONG = tak terikat satu
 *                user (mis. dashboard) → tombol "Cetak Laporan Bulanan" tak
 *                ditampilkan: laporan bulanan hanya untuk user/komputer tertentu.
 */
$printScope = $printScope ?? '';
$base = current_url();
$mk   = fn($pr) => $base . '?periode=' . $pr . '&bulan=' . esc($periode['bulan'], 'url');
// Bulan untuk dicetak: bila periode aktif = 'bulan' pakai bulan itu; selain itu
// pakai bulan dari tanggal 'sampai' (untuk 7h/30h = bulan berjalan).
$cetakBulan = $periode['periode'] === 'bulan' ? $periode['bulan'] : substr($periode['sampai'], 0, 7);
?>
<div class="card mb-3">
  <div class="card-body py-2 d-flex flex-wrap gap-2 align-items-center">
    <span class="small text-muted me-1"><i class="bi bi-calendar3 me-1"></i>Periode:</span>
    <div class="btn-group btn-group-sm" role="group">
      <a href="<?= $mk('7h') ?>"  class="btn btn-outline-primary <?= $periode['periode'] === '7h'  ? 'active' : '' ?>">7 hari</a>
      <a href="<?= $mk('30h') ?>" class="btn btn-outline-primary <?= $periode['periode'] === '30h' ? 'active' : '' ?>">30 hari</a>
    </div>
    <form method="GET" action="<?= $base ?>" class="d-flex gap-1 align-items-center mb-0">
      <input type="hidden" name="periode" value="bulan">
      <input type="month" name="bulan" value="<?= esc($periode['bulan']) ?>" class="form-control form-control-sm" style="width:10rem">
      <button type="submit" class="btn btn-sm btn-outline-primary <?= $periode['periode'] === 'bulan' ? 'active' : '' ?>">
        <i class="bi bi-calendar-month me-1"></i>Lihat bulan
      </button>
    </form>
    <?php if ($printScope !== ''): ?>
    <span class="ms-auto">
      <a href="<?= base_url('ai-monitor/laporan?bulan=' . esc($cetakBulan, 'url') . $printScope) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-dark">
        <i class="bi bi-printer me-1"></i>Cetak Laporan Bulanan
      </a>
    </span>
    <?php endif; ?>
  </div>
</div>
