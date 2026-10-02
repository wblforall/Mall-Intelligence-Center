<?php
/**
 * Panel ANALISA individu (per karyawan / per komputer). Disisipkan di atas
 * daftar sesi. Butuh: $analisa (AiSessionModel::analisa), $ambang_kantor,
 * $periode. Canvas di-init oleh _analisa_js.php (section scripts).
 */
$tot = $analisa['total'];
$pk  = $analisa['pct_kantor'];           // int|null
$adaKlas = array_sum($analisa['jenis']) > 0;
$kantorRendah = $pk !== null && $pk < $ambang_kantor;
?>
<div class="card mb-3">
  <div class="card-header py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <span class="fw-semibold small"><i class="bi bi-bar-chart-line me-2 text-muted"></i>Analisa <span class="text-muted fw-normal">(<?= esc($periode['label']) ?>)</span></span>
  </div>
  <div class="card-body">

    <!-- Kartu total + %kantor -->
    <div class="row g-2 mb-3">
      <div class="col-6 col-md-3">
        <div class="border rounded p-2 h-100">
          <div class="text-muted" style="font-size:.68rem;text-transform:uppercase">Sesi</div>
          <div class="fw-bold fs-5"><?= number_format($tot['sesi']) ?></div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="border rounded p-2 h-100">
          <div class="text-muted" style="font-size:.68rem;text-transform:uppercase">Prompt</div>
          <div class="fw-bold fs-5"><?= number_format($tot['prompt']) ?></div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="border rounded p-2 h-100">
          <div class="text-muted" style="font-size:.68rem;text-transform:uppercase">Token</div>
          <div class="fw-bold fs-5"><?= number_format($tot['token']) ?></div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="border rounded p-2 h-100 <?= $kantorRendah ? 'border-danger bg-danger-subtle' : '' ?>">
          <div class="text-muted" style="font-size:.68rem;text-transform:uppercase">% Kantor</div>
          <?php if ($pk === null): ?>
            <div class="fw-bold fs-5 text-muted">N/A</div>
          <?php else: ?>
            <div class="fw-bold fs-5 <?= $kantorRendah ? 'text-danger' : '' ?>"><?= $pk ?>%</div>
            <?php if ($kantorRendah): ?>
            <div class="small text-danger mt-1"><i class="bi bi-exclamation-triangle-fill me-1"></i>Kantor &lt;<?= $ambang_kantor ?>%</div>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <?php if ($kantorRendah): ?>
    <div class="alert alert-danger d-flex align-items-center py-2" role="alert">
      <i class="bi bi-exclamation-triangle-fill me-2"></i>
      <span class="small">Porsi pemakaian untuk <strong>kantor</strong> baru <strong><?= $pk ?>%</strong> &mdash; di bawah ambang <?= $ambang_kantor ?>%.</span>
    </div>
    <?php endif; ?>

    <!-- Grafik -->
    <div class="row g-3">
      <div class="col-12 col-lg-4">
        <div class="small fw-semibold text-muted mb-1"><i class="bi bi-diagram-3 me-1"></i>Jenis Aktivitas</div>
        <?php if ($adaKlas): ?>
        <div style="height:220px"><canvas id="anJenis"></canvas></div>
        <?php else: ?>
        <div class="text-center text-muted py-4" style="font-size:.85rem">Belum ada sesi terklasifikasi</div>
        <?php endif; ?>
      </div>
      <div class="col-12 col-lg-4">
        <div class="small fw-semibold text-muted mb-1"><i class="bi bi-tags me-1"></i>Top Tema</div>
        <?php if (! empty($analisa['tema'])): ?>
        <div style="height:220px"><canvas id="anTema"></canvas></div>
        <?php else: ?>
        <div class="text-center text-muted py-4" style="font-size:.85rem">Belum ada tema</div>
        <?php endif; ?>
      </div>
      <div class="col-12 col-lg-4">
        <div class="small fw-semibold text-muted mb-1"><i class="bi bi-building me-1"></i>Kantor vs Pribadi</div>
        <?php if ($adaKlas): ?>
        <div style="height:220px"><canvas id="anKantor"></canvas></div>
        <?php else: ?>
        <div class="text-center text-muted py-4" style="font-size:.85rem">Belum ada data</div>
        <?php endif; ?>
      </div>
      <div class="col-12">
        <div class="small fw-semibold text-muted mb-1"><i class="bi bi-graph-up-arrow me-1"></i>Tren Harian <span class="fw-normal">(prompt &amp; token)</span></div>
        <div style="height:240px"><canvas id="anTren"></canvas></div>
      </div>
    </div>
  </div>
</div>
