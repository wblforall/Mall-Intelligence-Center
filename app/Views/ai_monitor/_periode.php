<?php
/**
 * Pemilih PERIODE bersama (dashboard / per karyawan / per komputer):
 * 7 hari · 30 hari · pilih bulan, plus tombol "Cetak Laporan Bulanan".
 *
 * Butuh:
 *   $periode     array dari AiMonitor::resolvePeriode()
 *   $printScope  (opsional) query tambahan untuk rute cetak, mis.
 *                '&employee_id=5' atau '&device_id=3' (kosong = global).
 */
$printScope = $printScope ?? '';
$base = current_url();
$mk   = fn($pr) => $base . '?periode=' . $pr . '&bulan=' . esc($periode['bulan'], 'url');
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
    <span class="ms-auto">
      <a href="<?= base_url('ai-monitor/laporan?bulan=' . esc($periode['bulan'], 'url') . $printScope) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-dark">
        <i class="bi bi-printer me-1"></i>Cetak Laporan Bulanan
      </a>
    </span>
  </div>
</div>
