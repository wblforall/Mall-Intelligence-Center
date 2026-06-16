<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
$statusBadge = ['input'=>['secondary','Input penilai'],'in_review'=>['info','Review atasan'],'hr_review'=>['warning','Review HR'],'finalized'=>['success','Final']];
[$bc,$bl] = $statusBadge[$form['status']] ?? ['secondary',$form['status']];
$n = fn($v) => $v === null || $v === '' ? '—' : rtrim(rtrim(number_format((float)$v,2),'0'),'.');
$bobotKpi = (float)$form['bobot_kpi']; $bobotKomp = (float)$form['bobot_kompetensi'];
// kelompokkan KPI per area
$grouped = [];
foreach ($kpis as $k) $grouped[$k['area']][] = $k;
?>
<div class="d-flex align-items-center gap-2 mb-3">
    <a href="<?= base_url($isHr ? 'appraisal/periods/'.$form['period_id'] : 'appraisal/saya') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0"><?= esc($form['employee_nama']) ?> <span class="badge bg-<?= $bc ?> align-middle ms-1"><?= $bl ?></span></h4>
        <small class="text-muted"><?= esc($form['jabatan_nama']??'-') ?> · <?= esc($form['dept_name']??'-') ?> · <?= esc($form['periode_nama']??'-') ?></small>
    </div>
    <a href="<?= base_url('appraisal/forms/'.$form['id'].'/print') ?>" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-printer me-1"></i>Cetak</a>
</div>

<?php if (session('error')): ?><div class="alert alert-danger py-2 small"><?= esc(session('error')) ?></div><?php endif; ?>
<?php if (session('success')): ?><div class="alert alert-success py-2 small"><?= esc(session('success')) ?></div><?php endif; ?>

<?php if ($mode === 'input'): ?>
<div class="alert alert-secondary py-2 small"><i class="bi bi-pencil me-1"></i>Anda penilai. Isi <b>Realisasi</b> & <b>Skor (0–100)</b> tiap KPI, dan nilai <b>Kompetensi (1–5)</b>, lalu <b>Teruskan</b>.</div>
<?php elseif ($mode === 'review'): ?>
<div class="alert alert-info py-2 small"><i class="bi bi-eye me-1"></i>Anda reviewer. Boleh <b>override</b> skor KPI maupun kompetensi (wajib catatan jika mengubah), lalu teruskan.</div>
<?php elseif ($mode === 'hr'): ?>
<div class="alert alert-warning py-2 small"><i class="bi bi-shield-check me-1"></i>Pengecekan HR. Anda <b>hanya dapat mengubah Kompetensi</b> (skor KPI terkunci). Override wajib catatan. Lalu <b>Finalisasi</b>.</div>
<?php endif; ?>

<form method="POST" action="<?= base_url('appraisal/forms/'.$form['id'].'/score') ?>" id="scoreForm">
<?= csrf_field() ?>

<!-- ── KPI ─────────────────────────────────────────────────────────── -->
<div class="card mb-3">
<div class="card-header py-2"><h6 class="mb-0 fw-semibold"><i class="bi bi-bullseye me-1"></i>Key Performance Indicators (bobot <?= (int)($bobotKpi*100) ?>%)</h6></div>
<div class="card-body p-0">
<div class="table-responsive">
<table class="table table-sm align-middle mb-0">
<thead class="small text-muted"><tr>
    <th class="ps-3">Area / Indikator</th><th style="width:90px">Unit</th>
    <th style="width:70px" class="text-center">Bobot</th><th style="width:80px" class="text-center">Target</th>
    <th style="width:90px" class="text-center">Realisasi</th><th style="width:90px" class="text-center">Skor 0–100</th>
    <th style="width:80px" class="text-center">Skor Akhir</th>
</tr></thead>
<tbody>
<?php foreach ($grouped as $area => $rows): ?>
<tr class="table-light"><td colspan="7" class="ps-3 fw-semibold small text-uppercase text-muted" style="font-size:.7rem"><?= esc($areas[$area] ?? $area) ?></td></tr>
<?php foreach ($rows as $k): ?>
<tr>
    <td class="ps-3 small"><?= esc($k['indikator']) ?></td>
    <td class="small text-muted"><?= esc($units[$k['unit']] ?? $k['unit']) ?></td>
    <td class="text-center small kpi-bobot" data-bobot="<?= (float)$k['bobot'] ?>"><?= $n($k['bobot']) ?></td>
    <td class="text-center small text-muted"><?= $n($k['target']) ?></td>
    <td><input type="number" step="0.01" name="kpi[<?= $k['id'] ?>][realisasi]" value="<?= $k['realisasi']!==null?rtrim(rtrim(number_format($k['realisasi'],2),'0'),'.'):'' ?>" class="form-control form-control-sm text-center" <?= $canEditKpi?'':'readonly' ?>></td>
    <td><input type="number" step="0.01" min="0" max="100" name="kpi[<?= $k['id'] ?>][skor]" value="<?= $k['skor']!==null?rtrim(rtrim(number_format($k['skor'],2),'0'),'.'):'' ?>" class="form-control form-control-sm text-center kpi-skor" data-bobot="<?= (float)$k['bobot'] ?>" <?= $canEditKpi?'':'readonly' ?>></td>
    <td class="text-center small fw-medium kpi-akhir">—</td>
</tr>
<?php endforeach; endforeach; ?>
</tbody>
<tfoot><tr class="fw-bold"><td colspan="6" class="text-end pe-2">Total Skor KPI</td><td class="text-center" id="totKpi"><?= $n($form['skor_kpi']) ?></td></tr></tfoot>
</table>
</div>
</div>
</div>

<!-- ── Kompetensi ──────────────────────────────────────────────────── -->
<div class="card mb-3">
<div class="card-header py-2"><h6 class="mb-0 fw-semibold"><i class="bi bi-people me-1"></i>Aspek Kompetensi (bobot <?= (int)($bobotKomp*100) ?>%)</h6></div>
<div class="card-body p-0">
<table class="table table-sm align-middle mb-0">
<tbody>
<?php foreach ($comps as $c): ?>
<tr>
    <td class="ps-3">
        <div class="fw-medium small"><?= esc($c['nama_aspek']) ?></div>
        <?php if ($c['deskripsi']): ?><div class="text-muted" style="font-size:.72rem"><?= esc($c['deskripsi']) ?></div><?php endif; ?>
    </td>
    <td style="width:170px">
        <select name="comp[<?= $c['id'] ?>][nilai]" class="form-select form-select-sm comp-nilai" <?= $canEditComp?'':'disabled' ?>>
            <option value="">—</option>
            <?php foreach ($skala as $v => $lbl): ?>
            <option value="<?= $v ?>" <?= (string)$c['nilai']===(string)$v?'selected':'' ?>><?= $v ?> — <?= $lbl ?></option>
            <?php endforeach; ?>
        </select>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
<tfoot><tr class="fw-bold"><td class="text-end pe-2">Skor Kompetensi (rata-rata×20)</td><td class="text-center" id="totKomp"><?= $n($form['skor_kompetensi']) ?></td></tr></tfoot>
</table>
</div>
</div>

<?php if ($needNote): ?>
<div class="card mb-3"><div class="card-body py-2">
    <label class="form-label small fw-semibold mb-1">Catatan perubahan (wajib jika meng-override nilai)</label>
    <input type="text" name="catatan" class="form-control form-control-sm" placeholder="Alasan perubahan nilai…">
</div></div>
<?php endif; ?>

<?php if ($canEditKpi || $canEditComp): ?>
<div class="d-flex justify-content-end mb-3">
    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Simpan Penilaian</button>
</div>
<?php endif; ?>
</form>

<!-- ── Ringkasan & aksi ────────────────────────────────────────────── -->
<div class="card mb-3">
<div class="card-body">
    <div class="row text-center g-2 mb-2">
        <div class="col"><div class="small text-muted">Skor KPI × <?= $bobotKpi ?></div><div class="fs-5 fw-bold" id="sumKpi"><?= $n($form['skor_kpi']) ?></div></div>
        <div class="col"><div class="small text-muted">Skor Komp. × <?= $bobotKomp ?></div><div class="fs-5 fw-bold" id="sumKomp"><?= $n($form['skor_kompetensi']) ?></div></div>
        <div class="col"><div class="small text-muted">Nilai Akhir</div><div class="fs-4 fw-bold text-primary" id="sumFinal"><?= $n($form['nilai_akhir']) ?></div></div>
    </div>
    <div class="d-flex flex-wrap gap-2 justify-content-end">
        <?php if ($canForward): ?>
        <form method="POST" action="<?= base_url('appraisal/forms/'.$form['id'].'/forward') ?>" onsubmit="return confirm('Teruskan penilaian ke tahap berikutnya? Nilai akan dikunci dari Anda.')">
            <?= csrf_field() ?>
            <button class="btn btn-success"><i class="bi bi-send me-1"></i>Teruskan</button>
        </form>
        <?php endif; ?>
        <?php if ($canFinalize): ?>
        <form method="POST" action="<?= base_url('appraisal/forms/'.$form['id'].'/finalize') ?>" onsubmit="return confirm('Finalisasi penilaian ini?')">
            <?= csrf_field() ?>
            <button class="btn btn-success"><i class="bi bi-check-circle me-1"></i>Finalisasi</button>
        </form>
        <?php endif; ?>
    </div>
</div>
</div>

<!-- ── Pendapat karyawan ───────────────────────────────────────────── -->
<div class="card mb-4">
<div class="card-header py-2"><h6 class="mb-0 fw-semibold"><i class="bi bi-chat-left-text me-1"></i>Pendapat Karyawan</h6></div>
<div class="card-body">
<?php $canPendapat = $isEmployee || $isHr; ?>
<?php if ($canPendapat): ?>
<form method="POST" action="<?= base_url('appraisal/forms/'.$form['id'].'/pendapat') ?>">
    <?= csrf_field() ?>
    <textarea name="pendapat_karyawan" class="form-control form-control-sm mb-2" rows="3" placeholder="Tanggapan karyawan atas penilaian…"><?= esc($form['pendapat_karyawan'] ?? '') ?></textarea>
    <div class="text-end"><button class="btn btn-sm btn-outline-primary">Simpan Pendapat</button></div>
</form>
<?php else: ?>
<p class="small text-muted mb-0"><?= $form['pendapat_karyawan'] ? esc($form['pendapat_karyawan']) : '— belum ada —' ?></p>
<?php endif; ?>
</div>
</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
const BOBOT_KPI = <?= $bobotKpi ?>, BOBOT_KOMP = <?= $bobotKomp ?>;
const fmt = v => (v === null ? '—' : (Math.round(v * 100) / 100).toString());

function recompute() {
    // KPI: Σ bobot×skor/100
    let kpi = null, anyK = false;
    document.querySelectorAll('.kpi-skor').forEach(inp => {
        const tr = inp.closest('tr');
        const bobot = parseFloat(inp.dataset.bobot) || 0;
        const s = inp.value === '' ? null : parseFloat(inp.value);
        const cell = tr.querySelector('.kpi-akhir');
        if (s === null || isNaN(s)) { cell.textContent = '—'; return; }
        anyK = true;
        const akhir = bobot * s / 100;
        cell.textContent = fmt(akhir);
        kpi = (kpi || 0) + akhir;
    });
    // Kompetensi: avg(1-5)×20
    let sum = 0, cnt = 0;
    document.querySelectorAll('.comp-nilai').forEach(sel => {
        if (sel.value !== '') { sum += parseInt(sel.value); cnt++; }
    });
    const komp = cnt ? (sum / cnt) * 20 : null;
    const final = (kpi === null && komp === null) ? null : (kpi || 0) * BOBOT_KPI + (komp || 0) * BOBOT_KOMP;

    document.getElementById('totKpi').textContent  = anyK ? fmt(kpi) : '—';
    document.getElementById('totKomp').textContent = komp === null ? '—' : fmt(komp);
    document.getElementById('sumKpi').textContent  = anyK ? fmt(kpi) : '—';
    document.getElementById('sumKomp').textContent = komp === null ? '—' : fmt(komp);
    document.getElementById('sumFinal').textContent= final === null ? '—' : fmt(final);
}
document.querySelectorAll('.kpi-skor, .comp-nilai').forEach(el => el.addEventListener('input', recompute));
recompute();
</script>
<?= $this->endSection() ?>
