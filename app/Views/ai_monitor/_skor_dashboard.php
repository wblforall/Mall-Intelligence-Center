<?php
/**
 * Skor mutu prompt di dashboard: kartu rata-rata, sebaran tingkat (donut), dan
 * tabel per karyawan. TIDAK ADA papan peringkat: tabel default urut abjad;
 * kolom bisa diurutkan hanya atas permintaan pengguna.
 * Butuh: $skor, $skor_karyawan, $periode.
 */
helper('ai_skor');
use App\Libraries\AiSkorPrompt;

$minSesi = AiSkorPrompt::MIN_SESI_TAMPIL;
$cukup   = $skor['n'] >= $minSesi;
[$kunciT, $labelT] = $cukup ? AiSkorPrompt::tingkat($skor['rata']) : ['', ''];
$labelTingkat = ['perlu_dilatih' => 'Perlu dilatih', 'cukup' => 'Cukup', 'baik' => 'Baik', 'sangat_baik' => 'Sangat baik'];
?>
<div class="row g-3 mb-4">
  <div class="col-12 col-lg-4">
    <div class="card h-100">
      <div class="card-header py-2 d-flex justify-content-between align-items-center">
        <span class="fw-semibold small"><i class="bi bi-award me-2 text-muted"></i>Skor mutu prompt <span class="text-muted fw-normal">(rata-rata)</span></span>
        <a href="<?= base_url('ai-monitor/rubrik') ?>" class="small text-decoration-none">Cara penilaian</a>
      </div>
      <div class="card-body">
        <?php if ($cukup): ?>
        <div class="d-flex align-items-baseline gap-2">
          <span class="fw-bold" style="font-size:2.2rem;line-height:1"><?= number_format($skor['rata'], 1) ?></span>
          <span class="text-muted">/100</span>
          <span class="badge <?= skor_kelas($kunciT) ?> ms-1"><?= esc($labelT) ?></span>
        </div>
        <?php else: ?>
        <div class="fw-semibold text-muted fs-5">Data belum cukup</div>
        <div class="small text-muted">Perlu minimal <?= $minSesi ?> sesi ternilai pada periode ini.</div>
        <?php endif; ?>
        <div class="small text-muted mt-2">
          Dari <strong><?= number_format($skor['n']) ?></strong> sesi dinilai AI<?= $skor['tak_jelas'] ? ' (' . $skor['tak_jelas'] . ' bertanda tak jelas)' : '' ?>.
          <?php if ($skor['belum'] > 0): ?>
          <span class="d-block"><i class="bi bi-hourglass-split me-1"></i><?= number_format($skor['belum']) ?> sesi belum dinilai &mdash; tidak dihitung, dicoba lagi otomatis.</span>
          <?php endif; ?>
        </div>
        <div class="alert alert-secondary py-2 small mt-3 mb-0" role="note">
          Penilaian otomatis oleh AI &mdash; perkiraan, untuk pelatihan, bukan penilaian kinerja resmi.
        </div>
      </div>
    </div>
  </div>
  <div class="col-12 col-lg-4">
    <div class="card h-100">
      <div class="card-header py-2"><span class="fw-semibold small"><i class="bi bi-pie-chart me-2 text-muted"></i>Sebaran tingkat <span class="text-muted fw-normal">(sesi)</span></span></div>
      <div class="card-body">
        <?php if ($skor['n'] > 0): ?>
        <div style="height:220px"><canvas id="skSebaran"></canvas></div>
        <?php else: ?>
        <div class="text-center text-muted py-5 small"><i class="bi bi-award d-block fs-2 mb-2 opacity-25"></i>Belum ada sesi yang dinilai AI</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-12 col-lg-4">
    <div class="card h-100">
      <div class="card-header py-2"><span class="fw-semibold small"><i class="bi bi-people me-2 text-muted"></i>Per karyawan</span></div>
      <div class="card-body p-0">
        <div class="table-responsive" style="max-height:290px;overflow:auto">
          <table class="table table-sm align-middle mb-0" id="skTabel">
            <thead class="table-light"><tr>
              <th role="button" data-k="nama">Nama</th>
              <th role="button" class="text-end" data-k="rata">Skor</th>
              <th role="button" class="text-end" data-k="selisih" title="Dibanding periode sebelumnya yang sama panjang">Tren</th>
              <th class="text-end" title="Sesi dinilai AI / belum dinilai">Sesi</th>
            </tr></thead>
            <tbody>
            <?php if (! $skor_karyawan): ?>
              <tr><td colspan="4" class="text-center text-muted py-4 small">Belum ada data</td></tr>
            <?php else: foreach ($skor_karyawan as $r): ?>
              <tr data-nama="<?= esc(mb_strtolower($r['nama'])) ?>" data-rata="<?= $r['rata'] ?? '' ?>" data-selisih="<?= $r['selisih'] ?? '' ?>">
                <td><a href="<?= base_url('ai-monitor/karyawan/' . (int) $r['employee_id']) ?>" class="text-decoration-none"><?= esc($r['nama']) ?></a>
                  <div class="text-muted" style="font-size:.7rem"><?= esc($r['dept']) ?></div></td>
                <td class="text-end">
                  <?php if ($r['rata'] === null): ?>
                  <span class="small text-muted">Data belum cukup</span>
                  <?php else: [$kk, $ll] = AiSkorPrompt::tingkat($r['rata']); ?>
                  <span class="fw-semibold"><?= number_format($r['rata'], 1) ?></span>
                  <div><span class="badge <?= skor_kelas($kk) ?>" style="font-size:.65rem"><?= esc($ll) ?></span></div>
                  <?php endif; ?>
                </td>
                <td class="text-end small">
                  <?php if ($r['selisih'] === null): ?><span class="text-muted">&ndash;</span>
                  <?php elseif ($r['selisih'] >= 0.5): ?><span class="text-success"><i class="bi bi-arrow-up-right"></i> +<?= number_format($r['selisih'], 1) ?></span>
                  <?php elseif ($r['selisih'] <= -0.5): ?><span class="text-warning-emphasis"><i class="bi bi-arrow-down-right"></i> <?= number_format($r['selisih'], 1) ?></span>
                  <?php else: ?><span class="text-muted"><i class="bi bi-dash"></i> stabil</span><?php endif; ?>
                </td>
                <td class="text-end small"><?= (int) $r['n'] ?><?= $r['belum'] ? '<span class="text-muted" title="belum dinilai"> / ' . (int) $r['belum'] . '</span>' : '' ?></td>
              </tr>
            <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
