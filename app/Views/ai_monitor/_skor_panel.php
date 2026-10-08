<?php
/**
 * Panel SKOR MUTU PROMPT individu (halaman karyawan).
 * Butuh: $skor (AiSessionModel::skorRingkas), $skor_tren (skorTrenMingguan), $periode.
 * Hanya sesi yang dinilai AI yang dihitung; sesi pribadi tidak pernah dinilai.
 * Chart diinisialisasi oleh _skor_js.php (section scripts).
 */
helper('ai_skor');
use App\Libraries\AiSkorPrompt;

$minSesi = AiSkorPrompt::MIN_SESI_TAMPIL;
$cukup   = $skor['n'] >= $minSesi;
$adaTren = array_sum(array_column($skor_tren, 'n')) >= $minSesi;
[$kunciT, $labelT] = $cukup ? AiSkorPrompt::tingkat($skor['rata']) : ['', ''];
?>
<div class="card mb-3">
  <div class="card-header py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <span class="fw-semibold small"><i class="bi bi-award me-2 text-muted"></i>Skor Mutu Prompt <span class="text-muted fw-normal">(<?= esc($periode['label']) ?>)</span></span>
    <a href="<?= base_url('ai-monitor/rubrik') ?>" class="small text-decoration-none"><i class="bi bi-info-circle me-1"></i>Cara penilaian</a>
  </div>
  <div class="card-body">
    <div class="alert alert-secondary py-2 small mb-3" role="note">
      <i class="bi bi-robot me-1"></i><strong>Penilaian otomatis oleh AI</strong> &mdash; perkiraan, untuk pelatihan, bukan penilaian kinerja resmi.
    </div>

    <div class="row g-2 mb-3">
      <div class="col-6 col-md-3">
        <div class="border rounded p-2 h-100">
          <div class="text-muted" style="font-size:.68rem;text-transform:uppercase">Rata-rata skor</div>
          <?php if ($cukup): ?>
          <div class="fw-bold fs-4"><?= number_format($skor['rata'], 1) ?><span class="fs-6 text-muted">/100</span></div>
          <span class="badge <?= skor_kelas($kunciT) ?>"><?= esc($labelT) ?></span>
          <?php else: ?>
          <div class="fw-semibold text-muted mt-1">Data belum cukup</div>
          <div class="small text-muted">Perlu minimal <?= $minSesi ?> sesi ternilai</div>
          <?php endif; ?>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="border rounded p-2 h-100">
          <div class="text-muted" style="font-size:.68rem;text-transform:uppercase">Sesi dasar penilaian</div>
          <div class="fw-bold fs-4"><?= number_format($skor['n']) ?></div>
          <div class="small text-muted">dinilai AI<?= $skor['tak_jelas'] ? ' &middot; ' . $skor['tak_jelas'] . ' bertanda tak jelas' : '' ?></div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="border rounded p-2 h-100">
          <div class="text-muted" style="font-size:.68rem;text-transform:uppercase">Belum dinilai</div>
          <div class="fw-bold fs-4"><?= number_format($skor['belum']) ?></div>
          <div class="small text-muted">menunggu AI, tidak dihitung</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="border rounded p-2 h-100">
          <div class="text-muted" style="font-size:.68rem;text-transform:uppercase">Tidak dinilai</div>
          <div class="small text-muted mt-1">Sesi pribadi, sesi dengan kurang dari 2 prompt instruksi, dan sesi yang masih berjalan.</div>
        </div>
      </div>
    </div>

    <?php if ($cukup || $adaTren): ?>
    <div class="row g-3">
      <div class="col-12 col-lg-8">
        <div class="small fw-semibold text-muted mb-1"><i class="bi bi-graph-up me-1"></i>Tren skor rata-rata per minggu <span class="fw-normal">(12 minggu terakhir)</span></div>
        <?php if ($adaTren): ?>
        <div style="height:240px"><canvas id="skTren"></canvas></div>
        <?php else: ?>
        <div class="text-center text-muted py-4 small">Data belum cukup untuk tren</div>
        <?php endif; ?>
      </div>
      <div class="col-12 col-lg-4">
        <div class="small fw-semibold text-muted mb-1"><i class="bi bi-radar me-1"></i>Rata-rata per dimensi <span class="fw-normal">(maks. 20)</span></div>
        <?php if ($cukup): ?>
        <div style="height:240px"><canvas id="skDimensi"></canvas></div>
        <?php else: ?>
        <div class="text-center text-muted py-4 small">Data belum cukup</div>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>
