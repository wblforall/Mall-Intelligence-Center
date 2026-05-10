<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<style>
@keyframes fadeUp { from { opacity:0; transform:translateY(14px); } to { opacity:1; transform:none; } }
.anim-fade-up { animation: fadeUp .4s cubic-bezier(.22,.68,0,1.1) both; }
.budget-bar { height:8px; border-radius:4px; background:var(--bs-secondary-bg); overflow:hidden; }
.budget-bar-fill { height:100%; border-radius:4px; transition:width .5s; }
.kpi-card { border-radius:.75rem; padding:1.25rem; }
.over-budget { color: var(--bs-danger); }
</style>
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php
// Aggregate totals
$totalAnggaran  = 0;
$totalRealisasi = 0;
foreach ($departments as $d) {
    $totalAnggaran  += (float)($budgetMap[$d['id']]['anggaran'] ?? 0);
    $totalRealisasi += (float)($realisasiMap[$d['id']]['total_realisasi'] ?? 0);
}
$sisaTotal = $totalAnggaran - $totalRealisasi;
$pctTotal  = $totalAnggaran > 0 ? round($totalRealisasi / $totalAnggaran * 100, 1) : null;

function fmtRp(float $val): string {
    return 'Rp ' . number_format($val, 0, ',', '.');
}
?>

<!-- Header -->
<div class="d-flex align-items-center justify-content-between mb-3 anim-fade-up" style="animation-delay:.05s">
    <div>
        <h4 class="fw-bold mb-0">Budget Training</h4>
        <div class="text-muted small">Anggaran & realisasi biaya pelatihan per departemen</div>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <form method="GET" class="d-flex gap-2">
            <select name="tahun" class="form-select form-select-sm" onchange="this.form.submit()">
                <?php foreach ($years as $y): ?>
                <option value="<?= $y ?>" <?= $y == $tahun ? 'selected' : '' ?>><?= $y ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <a href="<?= base_url('people/training') ?>" class="btn btn-sm btn-light">
            <i class="bi bi-list-ul me-1"></i>Program
        </a>
    </div>
</div>

<!-- KPI -->
<div class="row g-3 mb-4 anim-fade-up" style="animation-delay:.1s">
    <div class="col-sm-6 col-lg-3">
        <div class="card kpi-card">
            <div class="text-muted small">Total Anggaran <?= $tahun ?></div>
            <div class="fw-bold fs-5 mt-1"><?= fmtRp($totalAnggaran) ?></div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card kpi-card">
            <div class="text-muted small">Total Realisasi</div>
            <div class="fw-bold fs-5 mt-1 <?= $totalRealisasi > $totalAnggaran && $totalAnggaran > 0 ? 'over-budget' : '' ?>"><?= fmtRp($totalRealisasi) ?></div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card kpi-card">
            <div class="text-muted small">Sisa Anggaran</div>
            <div class="fw-bold fs-5 mt-1 <?= $sisaTotal < 0 ? 'over-budget' : 'text-success' ?>"><?= fmtRp($sisaTotal) ?></div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card kpi-card">
            <div class="text-muted small">% Terpakai</div>
            <div class="fw-bold fs-5 mt-1 <?= $pctTotal > 100 ? 'over-budget' : '' ?>"><?= $pctTotal !== null ? $pctTotal . '%' : '—' ?></div>
            <?php if ($totalAnggaran > 0): ?>
            <div class="budget-bar mt-2">
                <div class="budget-bar-fill bg-<?= $pctTotal > 100 ? 'danger' : ($pctTotal > 80 ? 'warning' : 'primary') ?>"
                     style="width:<?= min($pctTotal ?? 0, 100) ?>%"></div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Budget Form Table -->
<form method="POST" action="<?= base_url('people/training/budget/save') ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="tahun" value="<?= $tahun ?>">

    <div class="card anim-fade-up" style="animation-delay:.15s">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-table me-2"></i>Anggaran per Departemen — <?= $tahun ?></h6>
            <button type="submit" class="btn btn-sm btn-primary">
                <i class="bi bi-floppy me-1"></i>Simpan Budget
            </button>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th style="min-width:160px">Departemen</th>
                        <th style="min-width:180px">Anggaran (Rp)</th>
                        <th class="text-end">Realisasi</th>
                        <th class="text-end">Sisa</th>
                        <th style="min-width:120px">Penggunaan</th>
                        <th style="min-width:140px">Catatan</th>
                        <th class="text-center">Detail</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($departments as $d):
                    $budget     = (float)($budgetMap[$d['id']]['anggaran'] ?? 0);
                    $catatan    = $budgetMap[$d['id']]['catatan'] ?? '';
                    $realisasi  = (float)($realisasiMap[$d['id']]['total_realisasi'] ?? 0);
                    $sisa       = $budget - $realisasi;
                    $pct        = $budget > 0 ? min(round($realisasi / $budget * 100, 1), 999) : null;
                    $overBudget = $budget > 0 && $realisasi > $budget;
                ?>
                <tr>
                    <td class="fw-medium"><?= esc($d['name']) ?></td>
                    <td>
                        <input type="number" name="budgets[<?= $d['id'] ?>][anggaran]" class="form-control form-control-sm"
                               step="100000" min="0" value="<?= $budget > 0 ? (int)$budget : '' ?>"
                               placeholder="0">
                    </td>
                    <td class="text-end <?= $overBudget ? 'over-budget fw-semibold' : '' ?>">
                        <?= $realisasi > 0 ? fmtRp($realisasi) : '<span class="text-muted">—</span>' ?>
                    </td>
                    <td class="text-end <?= $sisa < 0 ? 'over-budget fw-semibold' : ($budget > 0 ? 'text-success' : 'text-muted') ?>">
                        <?= $budget > 0 ? fmtRp($sisa) : '<span class="text-muted">—</span>' ?>
                    </td>
                    <td>
                        <?php if ($pct !== null): ?>
                        <div class="d-flex align-items-center gap-2">
                            <div class="budget-bar flex-grow-1">
                                <div class="budget-bar-fill bg-<?= $pct > 100 ? 'danger' : ($pct > 80 ? 'warning' : 'success') ?>"
                                     style="width:<?= min($pct, 100) ?>%"></div>
                            </div>
                            <span style="font-size:.75rem;min-width:38px" class="<?= $pct > 100 ? 'over-budget fw-bold' : '' ?>">
                                <?= $pct ?>%
                            </span>
                        </div>
                        <?php else: ?>
                        <span class="text-muted small">Belum ada anggaran</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <input type="text" name="budgets[<?= $d['id'] ?>][catatan]" class="form-control form-control-sm"
                               value="<?= esc($catatan) ?>" placeholder="—">
                    </td>
                    <td class="text-center">
                        <?php if ($realisasi > 0): ?>
                        <button type="button" class="btn btn-xs btn-sm btn-outline-secondary py-0 px-2"
                                style="font-size:.72rem"
                                onclick="loadDetail(<?= $d['id'] ?>, <?= $tahun ?>, '<?= esc($d['name']) ?>')">
                            <i class="bi bi-list-ul"></i>
                        </button>
                        <?php else: ?>
                        <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="fw-semibold">
                        <td>Total</td>
                        <td class="text-muted small" style="font-size:.75rem">— setelah simpan</td>
                        <td class="text-end"><?= $totalRealisasi > 0 ? fmtRp($totalRealisasi) : '—' ?></td>
                        <td class="text-end <?= $sisaTotal < 0 ? 'over-budget' : '' ?>"><?= $totalAnggaran > 0 ? fmtRp($sisaTotal) : '—' ?></td>
                        <td colspan="3"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</form>

<div class="text-muted small mt-2 anim-fade-up" style="animation-delay:.2s">
    <i class="bi bi-info-circle me-1"></i>
    Realisasi dihitung otomatis dari <strong>biaya per peserta × jumlah peserta yang hadir</strong> pada program training dalam tahun <?= $tahun ?>.
    Program tanpa biaya per peserta tidak diperhitungkan.
</div>

<!-- Detail Modal -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailModalTitle">Rincian Realisasi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailModalBody">
                <div class="text-center py-4"><div class="spinner-border spinner-border-sm"></div></div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
function loadDetail(deptId, tahun, deptName) {
    document.getElementById('detailModalTitle').textContent = 'Rincian Realisasi — ' + deptName + ' (' + tahun + ')';
    document.getElementById('detailModalBody').innerHTML = '<div class="text-center py-4"><div class="spinner-border spinner-border-sm"></div></div>';
    const modal = new bootstrap.Modal(document.getElementById('detailModal'));
    modal.show();

    fetch('<?= base_url('people/training/budget-detail/') ?>' + deptId + '?tahun=' + tahun)
        .then(r => r.json())
        .then(data => {
            if (!data.programs || data.programs.length === 0) {
                document.getElementById('detailModalBody').innerHTML = '<p class="text-muted text-center py-3">Tidak ada program dengan biaya tercatat.</p>';
                return;
            }
            let html = '<div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Program</th><th>Tanggal</th><th>Tipe</th><th class="text-center">Peserta Hadir</th><th class="text-end">Biaya/Peserta</th><th class="text-end">Total</th></tr></thead><tbody>';
            data.programs.forEach(p => {
                const tgl = p.tanggal_mulai ? new Date(p.tanggal_mulai).toLocaleDateString('id-ID', {day:'2-digit',month:'short',year:'numeric'}) : '—';
                const biaya = p.biaya_per_peserta ? 'Rp ' + Number(p.biaya_per_peserta).toLocaleString('id-ID') : '—';
                const total = p.total_biaya ? 'Rp ' + Number(p.total_biaya).toLocaleString('id-ID') : '—';
                html += `<tr><td>${p.nama}</td><td>${tgl}</td><td><span class="badge bg-secondary" style="font-size:.65rem">${p.tipe}</span></td><td class="text-center">${p.peserta_hadir}</td><td class="text-end">${biaya}</td><td class="text-end fw-semibold">${total}</td></tr>`;
            });
            html += '</tbody></table></div>';
            document.getElementById('detailModalBody').innerHTML = html;
        })
        .catch(() => {
            document.getElementById('detailModalBody').innerHTML = '<p class="text-danger text-center py-3">Gagal memuat data.</p>';
        });
}
</script>
<?= $this->endSection() ?>
