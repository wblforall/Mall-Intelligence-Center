<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
use App\Libraries\PenautanAkses;

$warnaCara = [
    'email_kerja'   => 'success',
    'email_pribadi' => 'success',
    'nama_persis'   => 'warning',
    'nama_ambigu'   => 'danger',
    'tidak_ada'     => 'secondary',
    'bukan_orang'   => 'light',
];
$perluTinjau = ['nama_ambigu', 'tidak_ada'];
$usulTercentang = count(array_filter($baris, fn ($b) => $b['usul_centang']));
$unitAdminUsul  = count(array_filter($baris, fn ($b) => $b['usul_centang'] && $b['peran_usul'] === 'unit_admin'));
?>

<div class="d-flex justify-content-between align-items-center mb-3 fade-up" style="animation-delay:.05s">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-link-45deg me-2"></i>Penautan — <?= esc($app['nama']) ?></h4>
        <small class="text-muted"><?= count($baris) ?> akun ditarik dari <?= esc($app['nama']) ?></small>
    </div>
    <a href="<?= base_url('penautan-akun') ?>" class="btn btn-sm btn-light border">
        <i class="bi bi-arrow-left me-1"></i>Kembali
    </a>
</div>

<div class="card mb-3 fade-up" style="animation-delay:.1s">
    <div class="card-body py-2 px-3">
        <small>
        <?php foreach ($ringkasan as $cara => $n): ?>
            <span class="badge text-bg-<?= $warnaCara[$cara] ?? 'light' ?> border me-1">
                <?= esc(PenautanAkses::label($cara)) ?>: <?= (int) $n ?>
            </span>
        <?php endforeach; ?>
        </small>
    </div>
</div>

<?php if ($unitAdminUsul > 0): ?>
<div class="alert alert-warning d-flex gap-2 fade-up" style="animation-delay:.15s">
    <i class="bi bi-exclamation-triangle mt-1"></i>
    <div class="small">
        <strong><?= $unitAdminUsul ?> usul berperan <code>unit_admin</code>.</strong>
        Peran itu bisa mengelola pengguna unit <em>dan mengubah template alur
        persetujuan</em> — jauh lebih besar daripada menandatangani dokumen.
        Nilainya disalin dari kolom <code>unit_admin</code> di <?= esc($app['nama']) ?>,
        dan di sana peran itu pernah diberikan lebih luas dari yang diperlukan.
        <strong>Turunkan ke <code>user</code> bila memang tidak perlu</strong> —
        sekarang saat meninjau, bukan nanti.
    </div>
</div>
<?php endif; ?>

<form method="post" action="<?= base_url('penautan-akun/' . $app['kode']) ?>">
<?= csrf_field() ?>

<div class="card fade-up" style="animation-delay:.2s">
<div class="table-responsive">
<table class="table table-sm table-hover align-middle mb-0">
    <thead class="table-light">
        <tr>
            <th style="width:38px"></th>
            <th>Akun di <?= esc($app['nama']) ?></th>
            <th>Dasar cocok</th>
            <th>Karyawan MIC</th>
            <th style="width:150px">Peran</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($baris as $b): $id = esc($b['id_lokal'], 'attr'); ?>
        <tr class="<?= in_array($b['cara'], $perluTinjau, true) ? 'table-warning' : '' ?>">

            <td>
            <?php if ($b['sudah_tertaut']): ?>
                <input type="checkbox" class="form-check-input" name="lepas[<?= $id ?>]"
                       title="Lepas tautan ini" <?= $canEdit ? '' : 'disabled' ?>>
            <?php elseif ($b['cara'] === 'bukan_orang'): ?>
                <span class="text-muted" title="Akun sistem, bukan orang">—</span>
            <?php else: ?>
                <input type="checkbox" class="form-check-input" name="tautkan[<?= $id ?>]"
                       <?= $b['usul_centang'] ? 'checked' : '' ?> <?= $canEdit ? '' : 'disabled' ?>>
            <?php endif; ?>
            </td>

            <td>
                <div class="fw-semibold"><?= esc($b['nama_akun']) ?></div>
                <div class="text-muted" style="font-size:.78rem">
                    <?= esc($b['email_akun']) ?>
                    <?php if ($b['unit_akun'] !== ''): ?>
                        · <?= esc($b['unit_akun']) ?>
                    <?php endif; ?>
                    <?php if (! $b['aktif_akun']): ?>
                        <span class="badge text-bg-secondary ms-1">nonaktif</span>
                    <?php endif; ?>
                </div>
            </td>

            <td>
                <span class="badge text-bg-<?= $warnaCara[$b['cara']] ?? 'light' ?> border">
                    <?= esc(PenautanAkses::label($b['cara'])) ?>
                </span>
                <?php if ($b['catatan'] !== ''): ?>
                    <div class="text-muted" style="font-size:.75rem"><?= esc($b['catatan']) ?></div>
                <?php endif; ?>
            </td>

            <td>
            <?php if ($b['sudah_tertaut']): ?>
                <div class="fw-semibold text-success">
                    <i class="bi bi-check2-circle me-1"></i><?= esc($b['tautan_kini']['nama_mic']) ?>
                </div>
                <div class="text-muted" style="font-size:.78rem">
                    sudah tertaut · <?= esc($b['tautan_kini']['nik']) ?>
                </div>
            <?php elseif ($b['cara'] === 'bukan_orang'): ?>
                <span class="text-muted small">tidak ditautkan</span>
            <?php else: ?>
                <?php /* Dropdown berisi SELURUH karyawan aktif: usul boleh salah,
                         dan admin harus bisa memilih orang lain tanpa keluar layar. */ ?>
                <select name="employee[<?= $id ?>]" class="form-select form-select-sm"
                        <?= $canEdit ? '' : 'disabled' ?>>
                    <option value="">— pilih karyawan —</option>
                    <?php foreach ($karyawan as $k): ?>
                        <option value="<?= (int) $k['id'] ?>"
                            <?= (int) $k['id'] === (int) $b['employee_id'] ? 'selected' : '' ?>>
                            <?= esc($k['nama']) ?> — <?= esc($k['jabatan']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
            </td>

            <td>
            <?php if ($b['sudah_tertaut']): ?>
                <span class="badge text-bg-light border"><?= esc($b['tautan_kini']['peran'] ?? '-') ?></span>
            <?php elseif ($b['cara'] !== 'bukan_orang'): ?>
                <select name="peran[<?= $id ?>]" class="form-select form-select-sm"
                        <?= $canEdit ? '' : 'disabled' ?>>
                    <?php foreach ($peran as $p): ?>
                        <option value="<?= esc($p['kode'], 'attr') ?>"
                            <?= $p['kode'] === $b['peran_usul'] ? 'selected' : '' ?>>
                            <?= esc($p['label']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
            </td>

        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<?php if ($canEdit): ?>
<div class="card-footer d-flex justify-content-between align-items-center">
    <small class="text-muted">
        <?= $usulTercentang ?> akun tercentang sebagai usul. Periksa dulu, ubah bila perlu,
        baru simpan.
    </small>
    <button type="submit" class="btn btn-primary btn-sm">
        <i class="bi bi-check2 me-1"></i>Simpan tautan tercentang
    </button>
</div>
<?php endif; ?>
</div>

</form>

<?= $this->endSection() ?>
