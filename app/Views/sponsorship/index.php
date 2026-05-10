<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<style>
.fade-up { opacity:0; transform:translateY(16px); animation:fadeUpSp .5s cubic-bezier(.22,.68,0,1.2) forwards; }
@keyframes fadeUpSp { to { opacity:1; transform:translateY(0); } }
.deal-badge { font-size:.68rem; font-weight:600; padding:2px 8px; border-radius:999px; }
.deal-prospek      { background:#f1f5f9; color:#64748b; }
.deal-negosiasi    { background:#fef3c7; color:#d97706; }
.deal-terkonfirmasi{ background:#dbeafe; color:#1d4ed8; }
.deal-lunas        { background:#dcfce7; color:#16a34a; }
.deal-batal        { background:#fee2e2; color:#dc2626; }
</style>
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php
$dealStatusLabel = [
    'prospek'       => 'Prospek',
    'negosiasi'     => 'Negosiasi',
    'terkonfirmasi' => 'Terkonfirmasi',
    'lunas'         => 'Lunas',
    'batal'         => 'Batal',
];
$kategoriOptions = ['Platinum', 'Gold', 'Silver', 'Bronze', 'Media Partner', 'In-kind', 'Lainnya'];

function spFmt(int $n): string { return 'Rp ' . number_format($n, 0, ',', '.'); }
function spPct(int $actual, int $target): float {
    return $target > 0 ? min(100, round($actual / $target * 100, 1)) : 0;
}
?>

<!-- Header -->
<div class="d-flex align-items-center gap-2 mb-4 fade-up" style="animation-delay:.05s">
    <div class="rounded-2 d-flex align-items-center justify-content-center flex-shrink-0"
         style="width:36px;height:36px;background:rgba(245,158,11,.15)">
        <i class="bi bi-trophy-fill" style="color:#d97706;font-size:1rem"></i>
    </div>
    <div>
        <h4 class="fw-bold mb-0">Program Sponsorship</h4>
        <small class="text-muted">Kelola deal sponsor, realisasi penerimaan, dan tracking target</small>
    </div>
    <div class="d-flex gap-2 ms-auto">
        <a href="<?= base_url('sponsorship/summary') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-bar-chart-line me-1"></i>Summary Bulanan
        </a>
        <?php if ($canEdit): ?>
        <button class="btn btn-sm btn-warning text-white" data-bs-toggle="modal" data-bs-target="#addProgramModal">
            <i class="bi bi-plus-lg me-1"></i>Tambah Program
        </button>
        <?php endif; ?>
    </div>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-warning-subtle h-100 fade-up" style="animation-delay:.1s">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <div class="rounded-2 p-1 bg-warning-subtle"><i class="bi bi-trophy text-warning fs-5"></i></div>
                    <span class="small text-muted">Program Aktif</span>
                </div>
                <div class="fw-bold fs-4 text-warning"><?= $activeCount ?></div>
                <div class="small text-muted">dari <?= count($programs) ?> total</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-primary-subtle h-100 fade-up" style="animation-delay:.18s">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <div class="rounded-2 p-1 bg-primary-subtle"><i class="bi bi-people text-primary fs-5"></i></div>
                    <span class="small text-muted">Sponsor (Konfirmasi)</span>
                </div>
                <div class="fw-bold fs-4 text-primary"><?= $totalSponsorCount ?></div>
                <?php if ($targetSponsor > 0): ?>
                <div class="small text-muted">target <?= $targetSponsor ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-success-subtle h-100 fade-up" style="animation-delay:.26s">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <div class="rounded-2 p-1 bg-success-subtle"><i class="bi bi-cash-coin text-success fs-5"></i></div>
                    <span class="small text-muted">Nilai Deal (Konfirmasi)</span>
                </div>
                <div class="fw-bold fs-5 text-success"><?= spFmt($totalNilaiCommitted) ?></div>
                <?php if ($targetNilai > 0): ?>
                <div class="small text-muted">target <?= spFmt($targetNilai) ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-info-subtle h-100 fade-up" style="animation-delay:.34s">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <div class="rounded-2 p-1 bg-info-subtle"><i class="bi bi-arrow-down-circle text-info fs-5"></i></div>
                    <span class="small text-muted">Total Terkumpul</span>
                </div>
                <div class="fw-bold fs-5 text-info"><?= spFmt($totalNilaiTerkumpul) ?></div>
                <?php if ($totalNilaiCommitted > 0): ?>
                <?php $collRate = spPct($totalNilaiTerkumpul, $totalNilaiCommitted); ?>
                <div class="small text-muted"><?= $collRate ?>% dari deal</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$activePrograms   = array_filter($programs, fn($p) => $p['status'] === 'active');
$inactivePrograms = array_filter($programs, fn($p) => $p['status'] === 'inactive');
?>

<?php if (empty($programs)): ?>
<div class="text-center text-muted py-5 fade-up">
    <i class="bi bi-trophy fs-1 d-block mb-2 opacity-25"></i>
    <p>Belum ada program sponsorship.</p>
    <?php if ($canEdit): ?>
    <button class="btn btn-sm btn-warning text-white" data-bs-toggle="modal" data-bs-target="#addProgramModal">
        <i class="bi bi-plus-lg me-1"></i>Buat Program Pertama
    </button>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php foreach ([['active', 'Aktif', $activePrograms], ['inactive', 'Tidak Aktif', $inactivePrograms]] as [$statusKey, $statusLabel, $progList]):
    if (empty($progList)) continue; ?>

<div class="d-flex align-items-center gap-2 mb-3 mt-4 fade-up">
    <span class="badge <?= $statusKey === 'active' ? 'bg-warning text-dark' : 'bg-secondary' ?>"><?= $statusLabel ?></span>
    <small class="text-muted"><?= count($progList) ?> program</small>
</div>

<?php foreach ($progList as $prog):
    $pid          = $prog['id'];
    $spList       = $sponsors[$pid] ?? [];
    $committed    = $committedMap[$pid] ?? ['total_nilai' => 0, 'total_sponsor' => 0];
    $terkumpul    = $realisasiMap[$pid] ?? 0;
    $collPct      = spPct($terkumpul, $committed['total_nilai']);
    $collBar      = $collPct >= 100 ? 'danger' : ($collPct >= 75 ? 'warning' : 'success');
    $isLocked     = (bool)($prog['locked'] ?? false);
    $canEditProg  = $canEdit && ! $isLocked;
?>
<div class="card mb-3 fade-up" id="program-<?= $pid ?>" style="animation-delay:.1s">
    <div class="card-header d-flex align-items-center gap-2 py-2">
        <i class="bi bi-trophy-fill text-warning"></i>
        <span class="fw-semibold"><?= esc($prog['nama_program']) ?></span>
        <?php if ($prog['tanggal_mulai']): ?>
        <small class="text-muted">
            <?= date('d M Y', strtotime($prog['tanggal_mulai'])) ?>
            <?php if ($prog['tanggal_selesai']): ?>– <?= date('d M Y', strtotime($prog['tanggal_selesai'])) ?><?php endif; ?>
        </small>
        <?php endif; ?>
        <?php if ($isLocked): ?>
        <span class="badge bg-danger-subtle text-danger ms-1"><i class="bi bi-lock-fill me-1"></i>Terkunci</span>
        <?php endif; ?>
        <div class="ms-auto d-flex gap-1">
            <?php if ($canEdit): ?>
            <?php if ($isLocked): ?>
                <?php if ($user['role'] === 'admin'): ?>
                <form method="post" action="<?= base_url('sponsorship/' . $pid . '/unlock') ?>" class="d-inline">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm btn-outline-danger" title="Buka Kunci"><i class="bi bi-unlock"></i></button>
                </form>
                <?php endif; ?>
            <?php else: ?>
                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal"
                        data-bs-target="#editProgramModal"
                        data-id="<?= $pid ?>"
                        data-nama="<?= esc($prog['nama_program'], 'attr') ?>"
                        data-mulai="<?= $prog['tanggal_mulai'] ?? '' ?>"
                        data-selesai="<?= $prog['tanggal_selesai'] ?? '' ?>"
                        data-deskripsi="<?= esc($prog['deskripsi'] ?? '', 'attr') ?>"
                        data-target-sponsor="<?= $prog['target_sponsor'] ?? '' ?>"
                        data-target-nilai="<?= $prog['target_nilai'] ?? '' ?>"
                        data-catatan="<?= esc($prog['catatan'] ?? '', 'attr') ?>"
                        title="Edit"><i class="bi bi-pencil"></i></button>
                <form method="post" action="<?= base_url('sponsorship/' . $pid . '/lock') ?>" class="d-inline">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm btn-outline-warning" title="Kunci" onclick="return confirm('Kunci program ini?')"><i class="bi bi-lock"></i></button>
                </form>
                <form method="post" action="<?= base_url('sponsorship/' . $pid . '/toggle') ?>" class="d-inline">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm <?= $prog['status'] === 'active' ? 'btn-outline-secondary' : 'btn-outline-success' ?>" title="Toggle Status">
                        <i class="bi bi-<?= $prog['status'] === 'active' ? 'pause-circle' : 'play-circle' ?>"></i>
                    </button>
                </form>
                <form method="post" action="<?= base_url('sponsorship/' . $pid . '/delete') ?>" class="d-inline"
                      onsubmit="return confirm('Hapus program ini beserta semua data sponsor dan realisasinya?')">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm btn-outline-danger" title="Hapus"><i class="bi bi-trash"></i></button>
                </form>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="card-body pb-2">
        <!-- Program stats row -->
        <div class="row g-3 mb-3">
            <div class="col-6 col-md-3">
                <div class="text-muted small">Target Sponsor</div>
                <div class="fw-semibold"><?= $prog['target_sponsor'] ? $prog['target_sponsor'] . ' sponsor' : '—' ?></div>
                <div class="small text-muted">Konfirmasi: <?= $committed['total_sponsor'] ?></div>
            </div>
            <div class="col-6 col-md-3">
                <div class="text-muted small">Target Nilai</div>
                <div class="fw-semibold"><?= $prog['target_nilai'] ? spFmt((int)$prog['target_nilai']) : '—' ?></div>
                <div class="small text-muted">Deal: <?= spFmt($committed['total_nilai']) ?></div>
            </div>
            <div class="col-6 col-md-3">
                <div class="text-muted small">Terkumpul</div>
                <div class="fw-semibold text-info"><?= spFmt($terkumpul) ?></div>
            </div>
            <div class="col-6 col-md-3">
                <div class="text-muted small">Collection Rate</div>
                <div class="fw-semibold"><?= $collPct ?>%</div>
                <div class="progress mt-1" style="height:5px">
                    <div class="progress-bar bg-<?= $collBar ?>" style="width:<?= $collPct ?>%"></div>
                </div>
            </div>
        </div>

        <?php if ($prog['deskripsi']): ?>
        <p class="small text-muted mb-3"><?= esc($prog['deskripsi']) ?></p>
        <?php endif; ?>

        <!-- Sponsor deals table -->
        <?php if (! empty($spList)): ?>
        <div class="table-responsive mb-2">
            <table class="table table-sm table-hover align-middle mb-0" style="font-size:.82rem">
                <thead class="table-light">
                    <tr>
                        <th>Nama Sponsor</th>
                        <th>Kategori</th>
                        <th>Jenis</th>
                        <th class="text-end">Nilai Deal</th>
                        <th class="text-end">Terkumpul</th>
                        <th>Status Deal</th>
                        <?php if ($canEditProg): ?><th></th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($spList as $sp):
                    $spReal  = $realisasi[$sp['id']] ?? ['total_nilai' => 0, 'entries' => []];
                    $items   = $itemsBySponsors[$sp['id']] ?? [];
                ?>
                <tr id="sponsor-<?= $sp['id'] ?>">
                    <td>
                        <div class="fw-medium"><?= esc($sp['nama_sponsor']) ?></div>
                        <?php if ($sp['detail']): ?>
                        <div class="text-muted" style="font-size:.75rem"><?= esc($sp['detail']) ?></div>
                        <?php endif; ?>
                        <?php if (! empty($items)): ?>
                        <div class="mt-1">
                            <?php foreach ($items as $item): ?>
                            <span class="badge me-1" style="font-size:.7rem;background:#e2e8f0;color:#334155;font-weight:500">
                                <?= esc($item['deskripsi_barang']) ?>
                                <?= $item['qty'] ? '×' . $item['qty'] : '' ?>
                                — <?= spFmt((int)$item['nilai']) ?>
                            </span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td><?= $sp['kategori'] ? '<span class="badge bg-secondary-subtle text-secondary">' . esc($sp['kategori']) . '</span>' : '—' ?></td>
                    <td><span class="badge" style="background:#e2e8f0;color:#334155;font-weight:600"><?= ucfirst($sp['jenis']) ?></span></td>
                    <td class="text-end fw-semibold"><?= spFmt((int)$sp['nilai']) ?></td>
                    <td class="text-end fw-semibold">
                        <?php
                        $nilaiDeal = (int)$sp['nilai'];
                        $nilaiReal = (int)$spReal['total_nilai'];
                        $lunasPenuh = $nilaiDeal > 0 && $nilaiReal >= $nilaiDeal;
                        ?>
                        <span class="<?= $lunasPenuh ? 'text-success' : 'text-info' ?>"><?= spFmt($nilaiReal) ?></span>
                        <?php if ($lunasPenuh): ?>
                        <i class="bi bi-check-circle-fill text-success ms-1" style="font-size:.8rem" title="Realisasi sudah mencapai nilai deal"></i>
                        <?php elseif ($nilaiDeal > 0 && $nilaiReal > 0): ?>
                        <div class="text-muted" style="font-size:.7rem"><?= round($nilaiReal / $nilaiDeal * 100) ?>%</div>
                        <?php endif; ?>
                    </td>
                    <td><span class="deal-badge deal-<?= $sp['status_deal'] ?>"><?= $dealStatusLabel[$sp['status_deal']] ?? $sp['status_deal'] ?></span></td>
                    <?php if ($canEditProg): ?>
                    <td class="text-end">
                        <div class="d-flex gap-1 justify-content-end">
                            <button class="btn btn-xs btn-outline-primary" style="padding:2px 6px;font-size:.72rem"
                                    data-bs-toggle="modal" data-bs-target="#editSponsorModal"
                                    data-pid="<?= $pid ?>"
                                    data-id="<?= $sp['id'] ?>"
                                    data-nama="<?= esc($sp['nama_sponsor'], 'attr') ?>"
                                    data-kategori="<?= esc($sp['kategori'] ?? '', 'attr') ?>"
                                    data-jenis="<?= $sp['jenis'] ?>"
                                    data-nilai="<?= $sp['nilai'] ?>"
                                    data-status="<?= $sp['status_deal'] ?>"
                                    data-detail="<?= esc($sp['detail'] ?? '', 'attr') ?>"
                                    data-catatan="<?= esc($sp['catatan'] ?? '', 'attr') ?>"
                                    data-items="<?= esc(json_encode($itemsBySponsors[$sp['id']] ?? []), 'attr') ?>">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-xs btn-outline-success" style="padding:2px 6px;font-size:.72rem"
                                    data-bs-toggle="modal" data-bs-target="#addRealisasiModal"
                                    data-pid="<?= $pid ?>"
                                    data-spid="<?= $sp['id'] ?>"
                                    data-nama="<?= esc($sp['nama_sponsor'], 'attr') ?>">
                                <i class="bi bi-plus-circle"></i> Realisasi
                            </button>
                            <form method="post" action="<?= base_url('sponsorship/' . $pid . '/sponsor/' . $sp['id'] . '/delete') ?>"
                                  class="d-inline" onsubmit="return confirm('Hapus sponsor ini beserta realisasinya?')">
                                <?= csrf_field() ?>
                                <button class="btn btn-xs btn-outline-danger" style="padding:2px 6px;font-size:.72rem"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                        <!-- Realisasi entries inline -->
                        <?php if (! empty($spReal['entries'])): ?>
                        <div class="mt-2">
                            <?php foreach ($spReal['entries'] as $r): ?>
                            <div class="d-flex align-items-center gap-1 mb-1" style="font-size:.72rem">
                                <span class="text-muted"><?= date('d/m/Y', strtotime($r['tanggal'])) ?></span>
                                <span class="fw-semibold text-info"><?= spFmt((int)$r['nilai']) ?></span>
                                <?php if ($r['file_bukti']): ?>
                                <a href="<?= base_url('uploads/sponsorship/' . $pid . '/' . $r['file_bukti']) ?>" target="_blank" class="text-primary"><i class="bi bi-paperclip"></i></a>
                                <?php endif; ?>
                                <form method="post" action="<?= base_url('sponsorship/' . $pid . '/sponsor/' . $sp['id'] . '/realisasi/' . $r['id'] . '/delete') ?>"
                                      class="d-inline" onsubmit="return confirm('Hapus entri realisasi ini?')">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-xs text-danger p-0 border-0 bg-transparent"><i class="bi bi-x-circle"></i></button>
                                </form>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <td colspan="3" class="fw-semibold small">Total</td>
                        <td class="text-end fw-bold"><?= spFmt(array_sum(array_column($spList, 'nilai'))) ?></td>
                        <td class="text-end fw-bold text-info"><?= spFmt($terkumpul) ?></td>
                        <td colspan="<?= $canEditProg ? 2 : 1 ?>"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <?php else: ?>
        <p class="text-muted small mb-2">Belum ada sponsor terdaftar.</p>
        <?php endif; ?>

        <?php if ($canEditProg): ?>
        <button class="btn btn-sm btn-outline-primary mt-1" data-bs-toggle="modal" data-bs-target="#addSponsorModal"
                data-pid="<?= $pid ?>" data-nama="<?= esc($prog['nama_program'], 'attr') ?>">
            <i class="bi bi-plus-lg me-1"></i>Tambah Sponsor
        </button>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
<?php endforeach; ?>

<!-- ── Modals ── -->

<!-- Add Program -->
<div class="modal fade" id="addProgramModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" action="<?= base_url('sponsorship/add') ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Program Sponsorship</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Nama Program <span class="text-danger">*</span></label>
                            <input type="text" name="nama_program" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Tanggal Mulai</label>
                            <input type="date" name="tanggal_mulai" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Tanggal Selesai</label>
                            <input type="date" name="tanggal_selesai" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Target Jumlah Sponsor</label>
                            <input type="number" name="target_sponsor" class="form-control form-control-sm" min="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Target Nilai (Rp)</label>
                            <input type="text" name="target_nilai" class="form-control form-control-sm num-fmt" inputmode="numeric" placeholder="mis: 100.000.000">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Deskripsi / Mekanisme</label>
                            <textarea name="deskripsi" class="form-control form-control-sm" rows="2"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Catatan</label>
                            <textarea name="catatan" class="form-control form-control-sm" rows="1"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-warning text-white">Simpan Program</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Program -->
<div class="modal fade" id="editProgramModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" id="editProgramForm">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title">Edit Program Sponsorship</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Nama Program <span class="text-danger">*</span></label>
                            <input type="text" name="nama_program" id="ep_nama" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Tanggal Mulai</label>
                            <input type="date" name="tanggal_mulai" id="ep_mulai" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Tanggal Selesai</label>
                            <input type="date" name="tanggal_selesai" id="ep_selesai" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Target Jumlah Sponsor</label>
                            <input type="number" name="target_sponsor" id="ep_target_sponsor" class="form-control form-control-sm" min="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Target Nilai (Rp)</label>
                            <input type="text" name="target_nilai" id="ep_target_nilai" class="form-control form-control-sm num-fmt" inputmode="numeric">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Deskripsi</label>
                            <textarea name="deskripsi" id="ep_deskripsi" class="form-control form-control-sm" rows="2"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Catatan</label>
                            <textarea name="catatan" id="ep_catatan" class="form-control form-control-sm" rows="1"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Sponsor -->
<div class="modal fade" id="addSponsorModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" id="addSponsorForm">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Sponsor — <span id="as_prog_nama"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold">Nama Sponsor / Perusahaan <span class="text-danger">*</span></label>
                            <input type="text" name="nama_sponsor" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Kategori</label>
                            <select name="kategori" class="form-select form-select-sm">
                                <option value="">— Pilih —</option>
                                <?php foreach ($kategoriOptions as $k): ?>
                                <option value="<?= $k ?>"><?= $k ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Jenis Sponsorship</label>
                            <select name="jenis" class="form-select form-select-sm" id="as_jenis" onchange="toggleAsItems(this.value)">
                                <option value="cash">Cash</option>
                                <option value="barang">Barang / In-kind</option>
                            </select>
                        </div>
                        <div class="col-md-4" id="as_nilai_wrap">
                            <label class="form-label small fw-semibold">Nilai (Rp)</label>
                            <input type="text" name="nilai" class="form-control form-control-sm num-fmt" inputmode="numeric" placeholder="mis: 50.000.000">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Status Deal</label>
                            <select name="status_deal" class="form-select form-select-sm">
                                <option value="prospek">Prospek</option>
                                <option value="negosiasi">Negosiasi</option>
                                <option value="terkonfirmasi">Terkonfirmasi</option>
                                <option value="lunas">Lunas</option>
                                <option value="batal">Batal</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Detail / Keterangan Deal</label>
                            <textarea name="detail" class="form-control form-control-sm" rows="2"></textarea>
                        </div>
                        <!-- Barang items -->
                        <div class="col-12" id="as_items_wrap" style="display:none">
                            <label class="form-label small fw-semibold">Rincian Barang / In-kind</label>
                            <div id="as_items_list">
                                <div class="row g-2 mb-2 as-item-row">
                                    <div class="col-6"><input type="text" name="deskripsi_barang[]" class="form-control form-control-sm" placeholder="Deskripsi barang"></div>
                                    <div class="col-2"><input type="number" name="qty[]" class="form-control form-control-sm" placeholder="Qty"></div>
                                    <div class="col-3"><input type="text" name="nilai_item[]" class="form-control form-control-sm num-fmt" inputmode="numeric" placeholder="Nilai (Rp)"></div>
                                    <div class="col-1"><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeAsItem(this)"><i class="bi bi-x"></i></button></div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-xs btn-outline-secondary mt-1" onclick="addAsItem()" style="font-size:.75rem">
                                <i class="bi bi-plus me-1"></i>Tambah Baris
                            </button>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Catatan</label>
                            <textarea name="catatan" class="form-control form-control-sm" rows="1"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary">Simpan Sponsor</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Sponsor -->
<div class="modal fade" id="editSponsorModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" id="editSponsorForm">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title">Edit Sponsor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Nama Sponsor <span class="text-danger">*</span></label>
                            <input type="text" name="nama_sponsor" id="es_nama" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Kategori</label>
                            <select name="kategori" id="es_kategori" class="form-select form-select-sm">
                                <option value="">— Pilih —</option>
                                <?php foreach ($kategoriOptions as $k): ?>
                                <option value="<?= $k ?>"><?= $k ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Jenis</label>
                            <select name="jenis" id="es_jenis" class="form-select form-select-sm" onchange="toggleEsItems(this.value)">
                                <option value="cash">Cash</option>
                                <option value="barang">Barang / In-kind</option>
                            </select>
                        </div>
                        <div class="col-md-6" id="es_nilai_wrap">
                            <label class="form-label small fw-semibold">Nilai (Rp)</label>
                            <input type="text" name="nilai" id="es_nilai" class="form-control form-control-sm num-fmt" inputmode="numeric">
                        </div>
                        <div class="col-12" id="es_items_wrap" style="display:none">
                            <label class="form-label small fw-semibold">Rincian Barang</label>
                            <div id="es_items_list">
                                <div class="row g-2 mb-2 es-item-row">
                                    <div class="col-6"><input type="text" name="deskripsi_barang[]" class="form-control form-control-sm" placeholder="Deskripsi barang"></div>
                                    <div class="col-2"><input type="number" name="qty[]" class="form-control form-control-sm" placeholder="Qty" min="1"></div>
                                    <div class="col-3"><input type="text" name="nilai_item[]" class="form-control form-control-sm num-fmt" inputmode="numeric" placeholder="Nilai (Rp)"></div>
                                    <div class="col-1"><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeEsItem(this)"><i class="bi bi-x"></i></button></div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-outline-secondary mt-1" style="font-size:.75rem;padding:2px 8px" onclick="addEsItem()">
                                <i class="bi bi-plus me-1"></i>Tambah Baris
                            </button>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Status Deal</label>
                            <select name="status_deal" id="es_status" class="form-select form-select-sm">
                                <option value="prospek">Prospek</option>
                                <option value="negosiasi">Negosiasi</option>
                                <option value="terkonfirmasi">Terkonfirmasi</option>
                                <option value="lunas">Lunas</option>
                                <option value="batal">Batal</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Detail</label>
                            <textarea name="detail" id="es_detail" class="form-control form-control-sm" rows="2"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Catatan</label>
                            <textarea name="catatan" id="es_catatan" class="form-control form-control-sm" rows="1"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Realisasi -->
<div class="modal fade" id="addRealisasiModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" id="addRealisasiForm" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title">Input Realisasi — <span id="ar_nama"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Tanggal <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nilai Diterima (Rp) <span class="text-danger">*</span></label>
                            <input type="text" name="nilai" class="form-control form-control-sm num-fmt" inputmode="numeric" required placeholder="mis: 25.000.000">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Upload Bukti (opsional)</label>
                            <input type="file" name="file_bukti" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Catatan</label>
                            <textarea name="catatan" class="form-control form-control-sm" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-success">Simpan Realisasi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->section('scripts') ?>
<script>
function numFmt(val) {
    const digits = String(val).replace(/\D/g, '');
    return digits === '' ? '' : parseInt(digits, 10).toLocaleString('id-ID');
}

// Live formatter — static + dynamic rows via delegation
document.addEventListener('input', function(e) {
    if (!e.target.classList.contains('num-fmt')) return;
    const el  = e.target;
    const pos = el.selectionStart;
    const old = el.value;
    const formatted = numFmt(old);
    el.value = formatted;
    // adjust cursor: account for added/removed dots
    const diff = formatted.length - old.length;
    el.setSelectionRange(pos + diff, pos + diff);
});

// Edit Program modal
document.getElementById('editProgramModal').addEventListener('show.bs.modal', function(e) {
    const b = e.relatedTarget;
    this.querySelector('#ep_nama').value          = b.dataset.nama || '';
    this.querySelector('#ep_mulai').value         = b.dataset.mulai || '';
    this.querySelector('#ep_selesai').value       = b.dataset.selesai || '';
    this.querySelector('#ep_deskripsi').value     = b.dataset.deskripsi || '';
    this.querySelector('#ep_target_sponsor').value= b.dataset.targetSponsor || '';
    this.querySelector('#ep_target_nilai').value  = numFmt(b.dataset.targetNilai || '');
    this.querySelector('#ep_catatan').value       = b.dataset.catatan || '';
    document.getElementById('editProgramForm').action = '<?= base_url('sponsorship/') ?>' + b.dataset.id + '/edit';
});

// Add Sponsor modal
document.getElementById('addSponsorModal').addEventListener('show.bs.modal', function(e) {
    const b = e.relatedTarget;
    document.getElementById('as_prog_nama').textContent = b.dataset.nama || '';
    document.getElementById('addSponsorForm').action = '<?= base_url('sponsorship/') ?>' + b.dataset.pid + '/sponsor/add';
    document.getElementById('as_jenis').value = 'cash';
    toggleAsItems('cash');
});

// Edit Sponsor modal
document.getElementById('editSponsorModal').addEventListener('show.bs.modal', function(e) {
    const b = e.relatedTarget;
    const jenis = b.dataset.jenis || 'cash';
    this.querySelector('#es_nama').value    = b.dataset.nama || '';
    this.querySelector('#es_kategori').value= b.dataset.kategori || '';
    this.querySelector('#es_jenis').value   = jenis;
    this.querySelector('#es_nilai').value   = numFmt(b.dataset.nilai || '');
    this.querySelector('#es_status').value  = b.dataset.status || 'prospek';
    this.querySelector('#es_detail').value  = b.dataset.detail || '';
    this.querySelector('#es_catatan').value = b.dataset.catatan || '';
    document.getElementById('editSponsorForm').action =
        '<?= base_url('sponsorship/') ?>' + b.dataset.pid + '/sponsor/' + b.dataset.id + '/edit';

    // Populate items
    const list = document.getElementById('es_items_list');
    list.innerHTML = '';
    let items = [];
    try { items = JSON.parse(b.dataset.items || '[]'); } catch(e) {}
    if (items.length === 0) items = [{}];
    items.forEach(item => {
        const row = esItemRow();
        row.querySelector('input[name="deskripsi_barang[]"]').value = item.deskripsi_barang || '';
        row.querySelector('input[name="qty[]"]').value              = item.qty || '';
        row.querySelector('input[name="nilai_item[]"]').value       = numFmt(item.nilai || '');
        list.appendChild(row);
    });
    toggleEsItems(jenis);
});

function esItemRow() {
    const div = document.createElement('div');
    div.className = 'row g-2 mb-2 es-item-row';
    div.innerHTML = `
        <div class="col-6"><input type="text" name="deskripsi_barang[]" class="form-control form-control-sm" placeholder="Deskripsi barang"></div>
        <div class="col-2"><input type="number" name="qty[]" class="form-control form-control-sm" placeholder="Qty" min="1"></div>
        <div class="col-3"><input type="text" name="nilai_item[]" class="form-control form-control-sm num-fmt" inputmode="numeric" placeholder="Nilai (Rp)"></div>
        <div class="col-1"><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeEsItem(this)"><i class="bi bi-x"></i></button></div>`;
    return div;
}

function toggleEsItems(val) {
    document.getElementById('es_items_wrap').style.display = val === 'barang' ? '' : 'none';
    document.getElementById('es_nilai_wrap').style.display = val === 'cash'   ? '' : 'none';
}
function addEsItem() {
    document.getElementById('es_items_list').appendChild(esItemRow());
}
function removeEsItem(btn) {
    const rows = document.querySelectorAll('.es-item-row');
    if (rows.length > 1) btn.closest('.es-item-row').remove();
}

// Add Realisasi modal
document.getElementById('addRealisasiModal').addEventListener('show.bs.modal', function(e) {
    const b = e.relatedTarget;
    document.getElementById('ar_nama').textContent = b.dataset.nama || '';
    document.getElementById('addRealisasiForm').action =
        '<?= base_url('sponsorship/') ?>' + b.dataset.pid + '/sponsor/' + b.dataset.spid + '/realisasi/add';
});

function toggleAsItems(val) {
    document.getElementById('as_items_wrap').style.display = val === 'barang' ? '' : 'none';
    document.getElementById('as_nilai_wrap').style.display = val === 'cash'   ? '' : 'none';
}

function addAsItem() {
    const row = document.querySelector('.as-item-row').cloneNode(true);
    row.querySelectorAll('input').forEach(i => i.value = '');
    document.getElementById('as_items_list').appendChild(row);
}

function removeAsItem(btn) {
    const rows = document.querySelectorAll('.as-item-row');
    if (rows.length > 1) btn.closest('.as-item-row').remove();
}
</script>
<?= $this->endSection() ?>

<?= $this->endSection() ?>
