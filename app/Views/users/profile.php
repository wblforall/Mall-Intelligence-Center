<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$hasEmp = ! empty($employee);
$infoFields = [
    'nik' => 'NIK', 'jenis_kelamin' => 'Jenis Kelamin', 'tanggal_lahir' => 'Tanggal Lahir',
    'no_hp' => 'No. HP', 'email' => 'Email', 'pendidikan' => 'Pendidikan', 'jurusan' => 'Jurusan',
    'status_pernikahan' => 'Status Pernikahan', 'agama' => 'Agama', 'status_kontrak' => 'Status Kontrak',
    'alamat' => 'Alamat', 'alamat_non_bpn' => 'Alamat (Non-BPN)',
];
?>

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= base_url('/') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h4 class="fw-bold mb-0">Profil Saya</h4>
    <?php if ($hasEmp): ?>
    <button class="btn btn-sm btn-primary ms-auto" data-bs-toggle="modal" data-bs-target="#requestModal">
        <i class="bi bi-pencil-square me-1"></i> Ajukan Perubahan Data
    </button>
    <?php endif; ?>
</div>

<div class="row g-4 <?= $hasEmp ? '' : 'justify-content-center' ?>">

<!-- Kolom data karyawan (ESS) -->
<?php if ($hasEmp): ?>
<div class="col-lg-8">

    <!-- Data Pribadi -->
    <div class="card mb-4">
    <div class="card-header d-flex align-items-center gap-2">
        <?php if ($employee['foto']): ?>
        <img src="<?= base_url('people/photo/' . $employee['foto']) ?>" style="width:40px;height:40px;border-radius:50%;object-fit:cover">
        <?php endif; ?>
        <div>
            <h6 class="mb-0 fw-semibold"><?= esc($employee['nama']) ?></h6>
            <small class="text-muted"><?= esc($employee['jabatan'] ?? '') ?><?= $employee['dept_name'] ? ' · '.esc($employee['dept_name']) : '' ?> · Masuk <?= date('d M Y', strtotime($employee['tanggal_masuk'])) ?> (<?= $employee['masa_kerja'] ?>)</small>
        </div>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <?php foreach ($infoFields as $f => $lbl): ?>
            <div class="col-md-4">
                <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.05em;color:var(--bs-secondary-color)"><?= $lbl ?></div>
                <div style="font-size:.9rem;font-weight:500">
                    <?php if ($f === 'tanggal_lahir' && ! empty($employee[$f])): ?><?= date('d M Y', strtotime($employee[$f])) ?>
                    <?php else: ?><?= $employee[$f] !== null && $employee[$f] !== '' ? esc($employee[$f]) : '<span class="text-muted">—</span>' ?><?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <p class="small text-muted mt-3 mb-0"><i class="bi bi-info-circle me-1"></i>Untuk mengubah data kontak/pribadi, gunakan <strong>Ajukan Perubahan Data</strong> — berlaku setelah disetujui HR.</p>
    </div>
    </div>

    <!-- Riwayat Penilaian -->
    <div class="card mb-4">
    <div class="card-header"><h6 class="mb-0 fw-semibold"><i class="bi bi-clipboard-check me-2"></i>Riwayat Penilaian</h6></div>
    <div class="card-body p-0">
    <?php if (empty($appraisals)): ?>
    <p class="text-muted text-center py-4 small mb-0">Belum ada hasil penilaian yang dirilis.</p>
    <?php else: ?>
    <div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
    <thead class="table-light"><tr><th class="ps-3">Periode</th><th>Tahun</th><th class="text-center">KPI</th><th class="text-center">Kompetensi</th><th class="text-center">Nilai Akhir</th><th>Tgl Final</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($appraisals as $a): ?>
    <tr>
        <td class="ps-3 fw-semibold small"><?= esc($a['periode_nama'] ?? '—') ?></td>
        <td class="small"><?= esc($a['tahun'] ?? '—') ?></td>
        <td class="text-center small"><?= $a['skor_kpi'] !== null ? number_format($a['skor_kpi'], 2) : '—' ?></td>
        <td class="text-center small"><?= $a['skor_kompetensi'] !== null ? number_format($a['skor_kompetensi'], 2) : '—' ?></td>
        <td class="text-center"><span class="badge bg-primary"><?= $a['nilai_akhir'] !== null ? number_format($a['nilai_akhir'], 2) : '—' ?></span></td>
        <td class="small text-nowrap text-muted"><?= $a['finalized_at'] ? date('d M Y', strtotime($a['finalized_at'])) : '—' ?></td>
        <td><a href="<?= base_url('appraisal/forms/'.$a['id']) ?>" class="btn btn-sm btn-link p-0">Lihat</a></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    </table>
    </div>
    <?php endif; ?>
    </div>
    </div>

    <!-- Status Pengajuan -->
    <div class="card mb-4">
    <div class="card-header"><h6 class="mb-0 fw-semibold"><i class="bi bi-pencil-square me-2"></i>Pengajuan Perubahan Data Saya</h6></div>
    <div class="card-body p-0">
    <?php if (empty($requests)): ?>
    <p class="text-muted text-center py-4 small mb-0">Belum ada pengajuan.</p>
    <?php else: ?>
    <div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
    <thead class="table-light"><tr><th class="ps-3">Data</th><th>Lama</th><th>Diajukan</th><th>Status</th><th>Catatan HR</th><th>Tanggal</th></tr></thead>
    <tbody>
    <?php $sb = ['pending'=>'warning','approved'=>'success','rejected'=>'danger']; foreach ($requests as $r): ?>
    <tr>
        <td class="ps-3 fw-semibold small"><?= esc($r['label']) ?></td>
        <td class="small text-muted"><?= $r['field'] === 'foto' ? '(foto)' : (esc($r['value_old']) ?: '—') ?></td>
        <td class="small"><?= $r['field'] === 'foto' ? '(foto baru)' : esc($r['value_new']) ?></td>
        <td><span class="badge bg-<?= $sb[$r['status']] ?? 'secondary' ?>"><?= ucfirst($r['status']) ?></span></td>
        <td class="small text-muted"><?= esc($r['catatan'] ?? '') ?: '—' ?></td>
        <td class="small text-nowrap text-muted"><?= date('d M Y', strtotime($r['created_at'])) ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    </table>
    </div>
    <?php endif; ?>
    </div>
    </div>

    <!-- Riwayat Jabatan -->
    <div class="card mb-4">
    <div class="card-header"><h6 class="mb-0 fw-semibold"><i class="bi bi-briefcase me-2"></i>Riwayat Jabatan</h6></div>
    <div class="card-body p-0">
    <?php if (empty($positions)): ?>
    <p class="text-muted text-center py-4 small mb-0">Belum ada riwayat jabatan.</p>
    <?php else: ?>
    <div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
    <thead class="table-light"><tr><th class="ps-3">Jabatan</th><th>Departemen</th><th>Mulai</th><th>Selesai</th></tr></thead>
    <tbody>
    <?php foreach ($positions as $p): ?>
    <tr>
        <td class="ps-3 fw-semibold small"><?= esc($p['jabatan']) ?><?php if (! $p['tanggal_selesai']): ?> <span class="badge bg-success-subtle text-success ms-1" style="font-size:.6rem">Sekarang</span><?php endif; ?></td>
        <td class="small text-muted"><?= esc($p['dept_name'] ?? '—') ?></td>
        <td class="small text-nowrap"><?= date('d M Y', strtotime($p['tanggal_mulai'])) ?></td>
        <td class="small text-nowrap"><?= $p['tanggal_selesai'] ? date('d M Y', strtotime($p['tanggal_selesai'])) : '—' ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    </table>
    </div>
    <?php endif; ?>
    </div>
    </div>

    <!-- Kelengkapan Dokumen Wajib -->
    <?php if ($hasEmp && $kelengkapan): ?>
    <div class="card mb-4 border-<?= $kelengkapan['lengkap'] ? 'success' : 'warning' ?>">
    <div class="card-header d-flex align-items-center">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-shield-check me-2"></i>Dokumen Wajib</h6>
        <span class="badge ms-auto bg-<?= $kelengkapan['lengkap'] ? 'success' : 'warning' ?>">
            <?= $kelengkapan['terverifikasi'] ?>/<?= $kelengkapan['total'] ?> terverifikasi
        </span>
    </div>
    <div class="card-body">
        <?php if (! $kelengkapan['lengkap']): ?>
        <p class="small text-muted mb-3">Ketiga dokumen di bawah ini wajib dilengkapi. Unggah lewat tombol <em>Upload Dokumen</em>, lalu HR akan memverifikasi.</p>
        <?php endif; ?>
        <div class="row g-2">
        <?php foreach ($kelengkapan['per_jenis'] as $jenis => $d):
            [$ikon, $warna, $ket] = match ($d['status']) {
                'approved' => ['bi-check-circle-fill', 'success', 'Terverifikasi'],
                'pending'  => ['bi-clock-fill',        'warning', 'Menunggu verifikasi HR'],
                'rejected' => ['bi-x-circle-fill',     'danger',  'Ditolak — mohon unggah ulang'],
                default    => ['bi-circle',            'secondary', 'Belum diunggah'],
            }; ?>
            <div class="col-md-4">
                <div class="d-flex align-items-center gap-2 border rounded p-2 h-100">
                    <i class="bi <?= $ikon ?> text-<?= $warna ?>"></i>
                    <div class="min-w-0">
                        <div class="small fw-semibold"><?= esc($d['label']) ?></div>
                        <div class="text-muted" style="font-size:.72rem"><?= $ket ?></div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    </div>
    </div>
    <?php endif; ?>

    <!-- Sertifikat -->
    <div class="card mb-4">
    <div class="card-header d-flex align-items-center">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-patch-check me-2"></i>Sertifikat</h6>
        <?php if ($hasEmp): ?>
        <button class="btn btn-sm btn-primary ms-auto" data-bs-toggle="modal" data-bs-target="#certModal"><i class="bi bi-plus-lg me-1"></i>Ajukan Sertifikat</button>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
    <?php if (empty($certificates)): ?>
    <p class="text-muted text-center py-4 small mb-0">Belum ada sertifikat. Ajukan sertifikat keahlian Anda — akan diverifikasi HR.</p>
    <?php else: ?>
    <?php $vb = ['pending'=>'warning','approved'=>'success','rejected'=>'danger']; ?>
    <div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
    <thead class="table-light"><tr><th class="ps-3">Nama</th><th>Jenis</th><th>Penerbit</th><th>Kadaluarsa</th><th>Masa Berlaku</th><th>Verifikasi</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($certificates as $c): ?>
    <tr>
        <td class="ps-3 fw-semibold small">
            <?= esc($c['nama_sertifikat']) ?>
            <?php if (! empty($c['bidang'])): ?><div class="text-muted fw-normal" style="font-size:.72rem"><?= esc($c['bidang']) ?></div><?php endif; ?>
        </td>
        <td class="small text-muted"><?= esc($jenisSertifikat[$c['jenis']] ?? '—') ?></td>
        <td class="small text-muted"><?= esc($c['penerbit'] ?? '') ?: '—' ?></td>
        <td class="small text-nowrap"><?= $c['tanggal_kadaluarsa'] ? date('d M Y', strtotime($c['tanggal_kadaluarsa'])) : '—' ?></td>
        <td><span class="badge bg-<?= esc($c['masa_berlaku']['color'] ?? 'secondary') ?>-subtle text-<?= esc($c['masa_berlaku']['color'] ?? 'secondary') ?>"><?= esc($c['masa_berlaku']['label'] ?? '—') ?></span></td>
        <td>
            <span class="badge bg-<?= $vb[$c['status']] ?? 'secondary' ?>"><?= ucfirst($c['status']) ?></span>
            <?php if ($c['status'] === 'rejected' && ! empty($c['catatan_review'])): ?>
            <div class="text-danger" style="font-size:.7rem"><?= esc($c['catatan_review']) ?></div>
            <?php endif; ?>
        </td>
        <td class="text-end pe-3">
            <?php if ($c['status'] !== 'approved'): ?>
            <form method="POST" action="<?= base_url('profile/certificates/' . $c['id'] . '/delete') ?>" class="d-inline"
                  onsubmit="return confirm('Batalkan pengajuan sertifikat ini?')">
                <?= csrf_field() ?>
                <button class="btn btn-sm btn-outline-danger py-0 px-1" title="Batalkan pengajuan"><i class="bi bi-trash"></i></button>
            </form>
            <?php else: ?>
            <span class="text-muted" style="font-size:.7rem" title="Sudah diverifikasi HR — hubungi HR bila perlu diubah"><i class="bi bi-lock"></i></span>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    </table>
    </div>
    <?php endif; ?>
    </div>
    </div>

    <!-- Dokumen Saya -->
    <div class="card mb-4">
    <div class="card-header d-flex align-items-center">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-folder2-open me-2"></i>Dokumen Saya</h6>
        <button class="btn btn-sm btn-primary ms-auto" data-bs-toggle="modal" data-bs-target="#docModal"><i class="bi bi-upload me-1"></i>Upload Dokumen</button>
    </div>
    <div class="card-body p-0">
    <?php if (empty($documents)): ?>
    <p class="text-muted text-center py-4 small mb-0">Belum ada dokumen. Upload KTP, NPWP, KK, atau ijazah — akan diverifikasi HR.</p>
    <?php else: ?>
    <?php $sb = ['pending'=>'warning','approved'=>'success','rejected'=>'danger']; ?>
    <div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
    <thead class="table-light"><tr><th class="ps-3">Dokumen</th><th>File</th><th>Status</th><th>Catatan HR</th><th>Tanggal</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($documents as $d): ?>
    <tr>
        <td class="ps-3 fw-semibold small"><?= esc(\App\Models\EmployeeDocumentModel::jenisLabel($d['jenis'], $d['nama_dokumen'])) ?></td>
        <td class="small">
            <?php if (! empty($d['file_name'])): ?>
            <a href="<?= base_url('people/documents/'.$d['id'].'/view') ?>" target="_blank"><i class="bi bi-file-earmark-text me-1"></i>Lihat</a>
            <?php else: ?>
            <span class="text-muted" title="Berkas dibuang saat ditolak">—</span>
            <?php endif; ?>
        </td>
        <td><span class="badge bg-<?= $sb[$d['status']] ?? 'secondary' ?>"><?= ucfirst($d['status']) ?></span></td>
        <td class="small text-muted"><?= esc($d['catatan'] ?? '') ?: '—' ?></td>
        <td class="small text-nowrap text-muted"><?= date('d M Y', strtotime($d['created_at'])) ?></td>
        <td class="text-end pe-3">
            <?php if ($d['status'] !== 'approved'): ?>
            <form method="POST" action="<?= base_url('profile/documents/'.$d['id'].'/delete') ?>" class="d-inline"
                  onsubmit="return confirm('Hapus dokumen ini dari daftar Anda?')">
                <?= csrf_field() ?>
                <button class="btn btn-sm btn-outline-danger py-0 px-1" title="Hapus dari daftar"><i class="bi bi-trash"></i></button>
            </form>
            <?php else: ?>
            <span class="text-muted" style="font-size:.7rem" title="Sudah diverifikasi HR"><i class="bi bi-lock"></i></span>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    </table>
    </div>
    <?php endif; ?>
    </div>
    </div>

</div>
<?php endif; ?>

<!-- Kolom pengaturan akun -->
<div class="<?= $hasEmp ? 'col-lg-4' : 'col-md-5' ?>">

<div class="card">
<div class="card-body p-4">
<div class="text-center mb-4">
    <div class="rounded-circle d-inline-flex align-items-center justify-content-center" style="width:64px;height:64px;background:var(--c-avatar-bg)">
        <i class="bi bi-person-fill fs-2" style="color:var(--c-avatar-fg)"></i>
    </div>
    <div class="fw-bold mt-2"><?= esc($user['name']) ?></div>
    <small class="text-muted"><?= esc($user['email']) ?></small>
    <div class="mt-1">
        <?php $rc = ['admin'=>'danger','manager'=>'primary','operator'=>'secondary'][$user['role']] ?? 'secondary' ?>
        <span class="badge bg-<?= $rc ?>"><?= ucfirst($user['role']) ?></span>
    </div>
</div>
<form method="POST" action="<?= base_url('profile') ?>">
    <?= csrf_field() ?>
    <div class="mb-3">
        <label class="form-label fw-semibold">Nama</label>
        <input type="text" name="name" class="form-control" value="<?= esc($user['name']) ?>" required>
    </div>
    <div class="mb-2">
        <label class="form-label fw-semibold">Password Baru <span class="text-muted small">(kosongkan jika tidak ganti)</span></label>
        <input type="password" id="pwNew" name="password" class="form-control" minlength="8" placeholder="Password baru">
    </div>
    <div class="mb-2">
        <label class="form-label fw-semibold">Konfirmasi Password Baru</label>
        <input type="password" id="pwConfirm" name="password_confirm" class="form-control" minlength="8" placeholder="Ulangi password baru">
    </div>
    <div class="mb-4 d-none" id="pwReqs">
        <div class="req-item" id="req-len"><i class="bi bi-circle me-1"></i>Minimal 8 karakter</div>
        <div class="req-item" id="req-upper"><i class="bi bi-circle me-1"></i>Minimal 1 huruf kapital (A–Z)</div>
        <div class="req-item" id="req-lower"><i class="bi bi-circle me-1"></i>Minimal 1 huruf kecil (a–z)</div>
        <div class="req-item" id="req-num"><i class="bi bi-circle me-1"></i>Minimal 1 angka (0–9)</div>
        <div class="req-item" id="req-sym"><i class="bi bi-circle me-1"></i>Minimal 1 simbol (!@#$%^&* dll)</div>
        <div class="req-item" id="req-match"><i class="bi bi-circle me-1"></i>Konfirmasi password cocok</div>
    </div>
    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-check-lg me-1"></i>Simpan Perubahan</button>
</form>
</div>
</div>

<!-- Theme Preference -->
<div class="card mt-4">
<div class="card-body p-4">
    <h6 class="fw-semibold mb-1"><i class="bi bi-palette me-2" style="color:var(--bs-primary)"></i>Tampilan</h6>
    <p class="text-muted small mb-3">Pilih tema antarmuka sesuai preferensi kamu.</p>
    <form method="POST" action="<?= base_url('profile/theme') ?>" id="themeForm">
        <?= csrf_field() ?>
        <div class="row g-3">
            <div class="col-6">
            <label class="d-block" style="cursor:pointer">
                <input type="radio" name="theme" value="dark" class="d-none theme-radio" <?= ($user['theme'] ?? 'dark') === 'dark' ? 'checked' : '' ?>>
                <div class="theme-card rounded-3 p-3 text-center <?= ($user['theme'] ?? 'dark') === 'dark' ? 'theme-card-active' : '' ?>">
                    <div class="mx-auto mb-2 rounded-2 overflow-hidden position-relative" style="width:88px;height:58px;background:linear-gradient(160deg,#0c1a2e,#091628);border:1px solid rgba(255,255,255,.08)">
                        <div style="position:absolute;inset:0 auto 0 0;width:20px;background:#091528"></div>
                        <div style="position:absolute;left:24px;top:7px;right:5px;height:12px;background:rgba(14,26,42,.92);border-radius:3px;border:1px solid rgba(255,255,255,.07)"></div>
                        <div style="position:absolute;left:24px;top:23px;right:5px;height:10px;background:rgba(14,26,42,.92);border-radius:3px;border:1px solid rgba(255,255,255,.07)"></div>
                        <div style="position:absolute;left:24px;top:37px;right:5px;height:10px;background:rgba(14,26,42,.92);border-radius:3px;border:1px solid rgba(255,255,255,.07)"></div>
                    </div>
                    <div class="small fw-semibold">Dark</div>
                    <div style="font-size:.7rem" class="text-muted">Navy Pro</div>
                </div>
            </label>
            </div>
            <div class="col-6">
            <label class="d-block" style="cursor:pointer">
                <input type="radio" name="theme" value="light" class="d-none theme-radio" <?= ($user['theme'] ?? 'dark') === 'light' ? 'checked' : '' ?>>
                <div class="theme-card rounded-3 p-3 text-center <?= ($user['theme'] ?? 'dark') === 'light' ? 'theme-card-active' : '' ?>">
                    <div class="mx-auto mb-2 rounded-2 overflow-hidden position-relative" style="width:88px;height:58px;background:linear-gradient(135deg,#f0f4ff,#e8eeff);border:1px solid rgba(99,102,241,.18)">
                        <div style="position:absolute;inset:0 auto 0 0;width:20px;background:#fff;border-right:1px solid rgba(99,102,241,.15)"></div>
                        <div style="position:absolute;left:24px;top:7px;right:5px;height:12px;background:#fff;border-radius:3px;box-shadow:0 1px 4px rgba(0,0,0,.08);border:1px solid rgba(99,102,241,.1)"></div>
                        <div style="position:absolute;left:24px;top:23px;right:5px;height:10px;background:#fff;border-radius:3px;box-shadow:0 1px 4px rgba(0,0,0,.08);border:1px solid rgba(99,102,241,.1)"></div>
                        <div style="position:absolute;left:24px;top:37px;right:5px;height:10px;background:#fff;border-radius:3px;box-shadow:0 1px 4px rgba(0,0,0,.08);border:1px solid rgba(99,102,241,.1)"></div>
                    </div>
                    <div class="small fw-semibold">Light</div>
                    <div style="font-size:.7rem" class="text-muted">Clean Pro</div>
                </div>
            </label>
            </div>
        </div>
    </form>
</div>
</div>

</div>
</div>

<!-- Modal Ajukan Perubahan -->
<?php if ($hasEmp): ?>
<div class="modal fade" id="requestModal" tabindex="-1">
<div class="modal-dialog modal-lg modal-dialog-scrollable">
<div class="modal-content">
<form method="POST" action="<?= base_url('profile/request-change') ?>" enctype="multipart/form-data">
<?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title fw-semibold">Ajukan Perubahan Data</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <p class="small text-muted">Centang data yang ingin diubah, lalu isi nilai barunya. Pengajuan akan ditinjau & disetujui HR sebelum berlaku.</p>
    <?php
    $textFields = [
        'no_hp'          => 'No. HP',
        'email'          => 'Email',
        'nik_ktp'        => 'No. KTP (NIK)',
        'no_kk'          => 'No. Kartu Keluarga',
        'no_npwp'        => 'No. NPWP (15 digit)',
        'no_npwp16'      => 'No. NPWP-16',
        'alamat'         => 'Alamat',
        'alamat_non_bpn' => 'Alamat (Non-BPN)',
    ];
    $petunjukNomor = [
        'nik_ktp'   => '16 digit sesuai KTP',
        'no_kk'     => '16 digit sesuai Kartu Keluarga',
        'no_npwp'   => '15 digit — format lama, tertulis bertitik seperti 09.254.294.5-017.000',
        'no_npwp16' => '16 digit — format baru. Untuk WNI biasanya sama dengan NIK Anda.',
    ];
    $selectFields = [
        'status_pernikahan' => ['', 'Belum Menikah', 'Menikah', 'Cerai'],
        'agama'             => ['', 'Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu'],
    ];
    // Pendidikan dipisah ke bloknya sendiri: field-fieldnya saling terkait
    // (IPK hanya relevan untuk D1 ke atas), jadi tak bisa ikut perulangan
    // generik seperti field lain.
    $jenjangOpts = \App\Models\EmployeeChangeRequestModel::JENJANG;
    $pendNow     = (string) ($employee['pendidikan'] ?? '');
    ?>
    <?php foreach ($textFields as $f => $lbl): ?>
    <div class="mb-3 row align-items-center">
        <div class="col-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="<?= $f ?>_chk" id="chk_<?= $f ?>" onchange="document.getElementById('in_<?= $f ?>').disabled=!this.checked">
                <label class="form-check-label small fw-semibold" for="chk_<?= $f ?>"><?= $lbl ?></label>
            </div>
            <div class="form-text small">Skrg: <?= esc($employee[$f] ?? '') ?: '—' ?></div>
        </div>
        <div class="col-8">
            <input type="<?= $f === 'email' ? 'email' : 'text' ?>" id="in_<?= $f ?>" name="<?= $f ?>" class="form-control form-control-sm" value="<?= esc($employee[$f] ?? '') ?>"
                   <?= isset($petunjukNomor[$f]) ? 'inputmode="numeric"' : '' ?> disabled>
            <?php if (isset($petunjukNomor[$f])): ?>
            <div class="form-text small"><?= $petunjukNomor[$f] ?></div>
            <div class="mt-1 d-flex align-items-center gap-2 flex-wrap">
                <label class="btn btn-sm btn-outline-secondary py-0 px-2 mb-0" style="font-size:.72rem">
                    <i class="bi bi-camera me-1"></i>Pindai dari foto
                    <input type="file" accept="image/*" class="d-none ocr-pilih" data-target="in_<?= $f ?>" data-jenis="<?= $f ?>">
                </label>
                <span class="small text-muted ocr-status" data-for="in_<?= $f ?>"></span>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    <?php foreach ($selectFields as $f => $opts): ?>
    <div class="mb-3 row align-items-center">
        <div class="col-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="<?= $f ?>_chk" id="chk_<?= $f ?>" onchange="document.getElementById('in_<?= $f ?>').disabled=!this.checked">
                <label class="form-check-label small fw-semibold" for="chk_<?= $f ?>"><?= ucwords(str_replace('_',' ',$f)) ?></label>
            </div>
            <div class="form-text small">Skrg: <?= esc($employee[$f] ?? '') ?: '—' ?></div>
        </div>
        <div class="col-8">
            <select id="in_<?= $f ?>" name="<?= $f ?>" class="form-select form-select-sm" disabled>
                <?php foreach ($opts as $o): ?>
                <option value="<?= esc($o) ?>" <?= ($employee[$f] ?? '') === $o ? 'selected' : '' ?>><?= $o === '' ? '— pilih —' : esc($o) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <?php endforeach; ?>
    <!-- ── Riwayat Pendidikan ── -->
    <hr class="my-3">
    <div class="fw-semibold small mb-1">Pendidikan Terakhir</div>
    <p class="small text-muted mb-3">Isi jenjang tertinggi yang Anda selesaikan. Lampirkan ijazah dan transkrip lewat tombol <em>Unggah Dokumen</em> agar HR dapat memverifikasi.</p>

    <?php
    $pendFields = [
        'pendidikan'  => 'Jenjang',
        'institusi'   => 'Nama Sekolah / Perguruan Tinggi',
        'jurusan'     => 'Jurusan / Fakultas',
        'ipk'         => 'IPK',
        'tahun_lulus' => 'Tahun Lulus',
    ];
    foreach ($pendFields as $f => $lbl):
        $isIpk = $f === 'ipk'; ?>
    <div class="mb-3 row align-items-center<?= $isIpk ? ' baris-ipk' : '' ?>">
        <div class="col-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="<?= $f ?>_chk" id="chk_<?= $f ?>" onchange="document.getElementById('in_<?= $f ?>').disabled=!this.checked">
                <label class="form-check-label small fw-semibold" for="chk_<?= $f ?>"><?= $lbl ?></label>
            </div>
            <div class="form-text small">Skrg: <?= esc($employee[$f] ?? '') ?: '—' ?></div>
        </div>
        <div class="col-8">
            <?php if ($f === 'pendidikan'): ?>
            <select id="in_pendidikan" name="pendidikan" class="form-select form-select-sm" disabled onchange="aturIpk()">
                <option value="">— pilih —</option>
                <?php foreach ($jenjangOpts as $o): ?>
                <option value="<?= $o ?>" <?= $pendNow === $o ? 'selected' : '' ?>><?= $o ?></option>
                <?php endforeach; ?>
            </select>
            <?php if ($pendNow !== '' && ! in_array($pendNow, $jenjangOpts, true)): ?>
            <div class="form-text small text-warning">Data lama Anda tercatat sebagai "<?= esc($pendNow) ?>" — mohon pilih jenjang yang sesuai.</div>
            <?php endif; ?>
            <?php elseif ($isIpk): ?>
            <input type="text" inputmode="decimal" id="in_ipk" name="ipk" class="form-control form-control-sm" value="<?= esc($employee['ipk'] ?? '') ?>" placeholder="contoh: 3.45" disabled>
            <div class="form-text small">Skala 0,00–4,00. Hanya untuk jenjang D1 ke atas.</div>
            <?php elseif ($f === 'tahun_lulus'): ?>
            <input type="number" id="in_tahun_lulus" name="tahun_lulus" class="form-control form-control-sm" value="<?= esc($employee['tahun_lulus'] ?? '') ?>" min="1950" max="<?= date('Y') ?>" placeholder="contoh: 2015" disabled>
            <?php else: ?>
            <input type="text" id="in_<?= $f ?>" name="<?= $f ?>" class="form-control form-control-sm" value="<?= esc($employee[$f] ?? '') ?>" disabled>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    <hr class="my-3">

    <div class="mb-2 row align-items-center">
        <div class="col-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="foto_chk" id="chk_foto" onchange="document.getElementById('in_foto').disabled=!this.checked">
                <label class="form-check-label small fw-semibold" for="chk_foto">Foto Profil</label>
            </div>
        </div>
        <div class="col-8">
            <input type="file" id="in_foto" name="foto" accept="image/*" class="form-control form-control-sm" disabled>
        </div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
    <button type="submit" class="btn btn-sm btn-primary">Kirim Pengajuan</button>
</div>
</form>
</div>
</div>
</div>

<!-- Modal Upload Dokumen -->
<div class="modal fade" id="docModal" tabindex="-1">
<div class="modal-dialog">
<div class="modal-content">
<form method="POST" action="<?= base_url('profile/upload-document') ?>" enctype="multipart/form-data">
<?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title fw-semibold">Upload Dokumen</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="mb-3">
        <label class="form-label small fw-semibold">Jenis Dokumen</label>
        <select name="jenis" id="docJenis" class="form-select form-select-sm" required>
            <option value="">— pilih —</option>
            <?php foreach ($jenisDok as $k => $lbl): ?><option value="<?= $k ?>"><?= esc($lbl) ?></option><?php endforeach; ?>
        </select>
    </div>
    <div class="mb-3 d-none" id="docNamaWrap">
        <label class="form-label small fw-semibold">Nama Dokumen</label>
        <input type="text" name="nama_dokumen" class="form-control form-control-sm" placeholder="mis. Piagam Penghargaan">
    </div>
    <div class="mb-2">
        <label class="form-label small fw-semibold">File</label>
        <input type="file" name="file" id="docFile" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,.pdf" required>
        <div class="form-text">JPG, PNG, atau PDF · maks 5 MB. Akan diverifikasi HR.</div>
    </div>

    <!-- Nomor ikut ditanyakan di sini: dulu karyawan hanya mengunggah kartu,
         nomornya tertinggal kosong tanpa ada yang menyadari sampai HR
         memverifikasi berkasnya. -->
    <div class="mt-3 d-none" id="docNomorWrap">
        <label class="form-label small fw-semibold" id="docNomorLabel">Nomor <span class="text-danger">*</span></label>
        <input type="text" name="nomor_identitas" id="docNomor" class="form-control form-control-sm" inputmode="numeric">
        <div class="form-text small" id="docNomorHint"></div>

        <!-- Kartu NPWP elektronik memuat DUA nomor sekaligus; sekali pindai
             mengisi keduanya, jadi karyawan tak perlu mengunggah dua kali. -->
        <div class="mt-3 d-none" id="docNpwp16Wrap">
            <label class="form-label small fw-semibold">No. NPWP-16</label>
            <input type="text" name="nomor_npwp16" id="docNpwp16" class="form-control form-control-sm" inputmode="numeric">
            <div class="form-text small">16 digit — format baru. Untuk WNI biasanya sama dengan NIK. Boleh dikosongkan bila kartu Anda belum mencantumkannya.</div>
        </div>

        <div class="mt-1 small text-muted" id="docOcrStatus"></div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
    <button type="submit" class="btn btn-sm btn-primary">Upload</button>
</div>
</form>
</div>
</div>
</div>

<!-- Modal Ajukan Sertifikat -->
<div class="modal fade" id="certModal" tabindex="-1">
<div class="modal-dialog modal-lg modal-dialog-scrollable">
<div class="modal-content">
<form method="POST" action="<?= base_url('profile/certificates/add') ?>" enctype="multipart/form-data">
<?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title fw-semibold">Ajukan Sertifikat</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <p class="small text-muted">Lengkapi data sertifikat keahlian Anda. Pengajuan akan diverifikasi HR sebelum tercatat resmi.</p>
    <div class="row g-3">
        <div class="col-md-8">
            <label class="form-label small fw-semibold">Nama Sertifikat <span class="text-danger">*</span></label>
            <input type="text" name="nama_sertifikat" class="form-control form-control-sm" required maxlength="200" placeholder="mis. Ahli K3 Umum">
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-semibold">Jenis</label>
            <select name="jenis" class="form-select form-select-sm">
                <option value="">— pilih —</option>
                <?php foreach ($jenisSertifikat as $k => $lbl): ?><option value="<?= $k ?>"><?= esc($lbl) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-8">
            <label class="form-label small fw-semibold">Bidang Keahlian</label>
            <input type="text" name="bidang" class="form-control form-control-sm" maxlength="150" placeholder="mis. Keselamatan Kerja, Akuntansi, Jaringan Komputer">
            <div class="form-text small">Dipakai untuk mencari siapa saja yang menguasai bidang tertentu.</div>
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-semibold">Level / Jenjang</label>
            <select name="level" class="form-select form-select-sm">
                <option value="">— pilih —</option>
                <?php foreach ($levelSertifikat as $k => $lbl): ?><option value="<?= $k ?>"><?= esc($lbl) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold">Lembaga Penerbit</label>
            <input type="text" name="penerbit" class="form-control form-control-sm" maxlength="200" placeholder="mis. BNSP / LSP Konstruksi">
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold">Nomor Sertifikat</label>
            <input type="text" name="nomor_sertifikat" class="form-control form-control-sm" maxlength="100">
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold">Tanggal Terbit</label>
            <input type="date" name="tanggal_terbit" class="form-control form-control-sm" max="<?= date('Y-m-d') ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold">Tanggal Kadaluarsa</label>
            <input type="date" name="tanggal_kadaluarsa" class="form-control form-control-sm">
            <div class="form-text small">Kosongkan bila sertifikat berlaku selamanya.</div>
        </div>
        <div class="col-md-8">
            <label class="form-label small fw-semibold">URL Verifikasi</label>
            <input type="url" name="url_verifikasi" class="form-control form-control-sm" maxlength="255" placeholder="https://...">
            <div class="form-text small">Bila lembaga penerbit menyediakan pengecekan online.</div>
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-semibold">Pembiayaan</label>
            <select name="pembiayaan" class="form-select form-select-sm">
                <option value="">— pilih —</option>
                <?php foreach ($pembiayaanSertifikat as $k => $lbl): ?><option value="<?= $k ?>"><?= esc($lbl) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="col-12">
            <label class="form-label small fw-semibold">File Sertifikat</label>
            <input type="file" name="file_sertifikat" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,.pdf">
            <div class="form-text">JPG, PNG, atau PDF · maks 10 MB.</div>
        </div>
        <div class="col-12">
            <label class="form-label small fw-semibold">Catatan</label>
            <input type="text" name="catatan" class="form-control form-control-sm" maxlength="255">
        </div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
    <button type="submit" class="btn btn-sm btn-primary">Kirim Pengajuan</button>
</div>
</form>
</div>
</div>
</div>
<?php endif; ?>

<?= $this->endSection() ?>
<?= $this->section('styles') ?>
<style>
.theme-card { border: 2px solid transparent; background: var(--c-inner-bg); transition: border-color .18s, box-shadow .18s; }
.theme-card:hover { border-color: var(--bs-primary); box-shadow: 0 0 0 3px rgba(var(--bs-primary-rgb),.1); }
.theme-card-active { border-color: var(--bs-primary) !important; box-shadow: 0 0 0 3px rgba(var(--bs-primary-rgb),.15) !important; }
.req-item { font-size:.82rem; color:var(--bs-secondary-color); }
.req-item.ok  { color:#16a34a; }
.req-item.fail { color:#dc2626; }
</style>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
document.querySelectorAll('.theme-radio').forEach(function(radio) {
    radio.addEventListener('change', function() {
        document.querySelectorAll('.theme-card').forEach(function(c) { c.classList.remove('theme-card-active'); });
        this.closest('label').querySelector('.theme-card').classList.add('theme-card-active');
        document.getElementById('themeForm').submit();
    });
});

// Live-check password baru (sama dengan syarat saat ubah password pertama)
(function () {
    const pw = document.getElementById('pwNew');
    const pw2 = document.getElementById('pwConfirm');
    const box = document.getElementById('pwReqs');
    if (!pw) return;
    const rules = {
        'req-len':   p => p.length >= 8,
        'req-upper': p => /[A-Z]/.test(p),
        'req-lower': p => /[a-z]/.test(p),
        'req-num':   p => /[0-9]/.test(p),
        'req-sym':   p => /[\W_]/.test(p),
    };
    function allOk() {
        const p = pw.value;
        let ok = true;
        for (const id in rules) {
            const pass = rules[id](p);
            const el = document.getElementById(id);
            el.className = 'req-item ' + (pass ? 'ok' : 'fail');
            el.querySelector('i').className = 'bi me-1 ' + (pass ? 'bi-check-circle-fill' : 'bi-x-circle');
            if (!pass) ok = false;
        }
        const match = p.length > 0 && p === pw2.value;
        const m = document.getElementById('req-match');
        m.className = 'req-item ' + (match ? 'ok' : 'fail');
        m.querySelector('i').className = 'bi me-1 ' + (match ? 'bi-check-circle-fill' : 'bi-x-circle');
        return ok && match;
    }
    function toggle() { box.classList.toggle('d-none', pw.value.length === 0); allOk(); }
    pw.addEventListener('input', toggle);
    pw2.addEventListener('input', toggle);
    pw.closest('form').addEventListener('submit', function (e) {
        if (pw.value.length > 0 && !allOk()) {
            e.preventDefault();
            box.classList.remove('d-none');
        }
    });
})();

/**
 * IPK hanya masuk akal untuk jenjang D1 ke atas, jadi barisnya disembunyikan
 * untuk jenjang di bawahnya. Jenjang yang dipakai = pilihan dropdown bila
 * karyawan sedang mengubahnya, kalau tidak ya jenjang yang tersimpan.
 * Aturan yang sama ditegakkan ulang di server (Users::validasiFieldPengajuan).
 */
function aturIpk() {
    const baris = document.querySelector('.baris-ipk');
    if (!baris) return;

    const sel  = document.getElementById('in_pendidikan');
    const chk  = document.getElementById('chk_pendidikan');
    const kini = <?= json_encode((string) ($employee['pendidikan'] ?? '')) ?>;
    const pakaiPilihan = chk && chk.checked && sel && sel.value !== '';
    const jenjang = (pakaiPilihan ? sel.value : kini).toUpperCase().trim();

    const boleh = ['D1','D2','D3','D4','S1','S2','S3'].includes(jenjang);
    baris.classList.toggle('d-none', !boleh);

    // Jangan biarkan nilai yang tersembunyi ikut terkirim.
    if (!boleh) {
        const c = document.getElementById('chk_ipk');
        const i = document.getElementById('in_ipk');
        if (c) c.checked = false;
        if (i) i.disabled = true;
    }
}
document.addEventListener('DOMContentLoaded', function () {
    const chk = document.getElementById('chk_pendidikan');
    if (chk) chk.addEventListener('change', aturIpk);
    aturIpk();
});

/**
 * Pindai nomor identitas dari foto. Seluruh proses terjadi di perangkat
 * karyawan — foto tidak diunggah ke mana pun untuk keperluan ini.
 *
 * Hasilnya SELALU diperlakukan sebagai usulan: field ikut dicentang dan
 * disorot agar karyawan sadar harus mencocokkannya dengan kartu aslinya.
 */
document.addEventListener('change', function (e) {
    const inp = e.target.closest('.ocr-pilih');
    if (!inp || !inp.files || !inp.files[0]) return;

    const target = document.getElementById(inp.dataset.target);
    const status = document.querySelector('.ocr-status[data-for="' + inp.dataset.target + '"]');
    const file   = inp.files[0];
    inp.value = '';                       // supaya file yang sama bisa dipilih lagi

    const pesan = (t, kelas) => {
        status.textContent = t;
        status.className = 'small ocr-status ' + (kelas || 'text-muted');
    };

    if (typeof OcrIdentitas === 'undefined') {
        pesan('Mesin OCR belum termuat — ketik nomornya manual.', 'text-danger');
        return;
    }

    pesan('Memuat mesin OCR (unduhan pertama ±8 MB)…');

    OcrIdentitas.baca(file, inp.dataset.jenis, p => pesan('Membaca… ' + p + '%'))
        .then(function (hasil) {
            if (!hasil.nomor) {
                pesan('Nomor tidak terbaca. Coba foto lebih terang & lurus, atau ketik manual.', 'text-warning');
                return;
            }
            // Aktifkan field-nya agar nilai hasil pindai ikut terkirim.
            const chk = document.getElementById('chk_' + inp.dataset.jenis);
            if (chk && !chk.checked) { chk.checked = true; target.disabled = false; }

            target.value = hasil.nomor;
            target.classList.add('border-warning');
            target.focus();
            pesan('Terbaca ' + hasil.nomor.length + ' digit — WAJIB dicocokkan dengan kartu asli.', 'text-warning');
        })
        .catch(function (err) {
            pesan(err.message || 'Gagal membaca gambar.', 'text-danger');
        });
});

/**
 * Form Upload Dokumen: untuk KTP/KK/NPWP sekalian minta nomornya, dan coba
 * bacakan dari berkas yang baru saja dipilih. Menggabungkan keduanya di satu
 * langkah — sebelumnya kartu dan nomor diajukan lewat dua jalur terpisah,
 * dan karyawan hampir selalu hanya melakukan yang pertama.
 */
(function () {
    var ATURAN = {
        ktp:  { field: 'nik_ktp', label: 'No. KTP (NIK)',      hint: '16 digit sesuai KTP' },
        kk:   { field: 'no_kk',   label: 'No. Kartu Keluarga', hint: '16 digit sesuai Kartu Keluarga' },
        npwp: { field: 'no_npwp', label: 'No. NPWP (15 digit)', hint: 'Format lama, tertulis bertitik seperti 09.254.294.5-017.000' }
    };

    var jenis  = document.getElementById('docJenis');
    var file   = document.getElementById('docFile');
    if (!jenis || !file) return;

    var wrap   = document.getElementById('docNomorWrap');
    var label  = document.getElementById('docNomorLabel');
    var hint   = document.getElementById('docNomorHint');
    var input  = document.getElementById('docNomor');
    var status = document.getElementById('docOcrStatus');
    var npwp16     = document.getElementById('docNpwp16');
    var npwp16Wrap = document.getElementById('docNpwp16Wrap');

    function aturan() { return ATURAN[jenis.value] || null; }

    function perbarui() {
        var a = aturan();
        document.getElementById('docNamaWrap').classList.toggle('d-none', jenis.value !== 'lainnya');
        wrap.classList.toggle('d-none', !a);
        // `required` dipasang/dilepas mengikuti jenis: kalau dibiarkan menempel
        // saat kolomnya tersembunyi, form jadi tak bisa dikirim untuk jenis
        // dokumen lain dengan galat yang tak terlihat oleh pengguna.
        input.required = !!a;
        // NPWP-16 hanya relevan pada kartu NPWP, dan sengaja TIDAK wajib:
        // kartu terbitan lama belum mencantumkannya.
        npwp16Wrap.classList.toggle('d-none', jenis.value !== 'npwp');
        if (jenis.value !== 'npwp') npwp16.value = '';

        if (a) {
            label.innerHTML = a.label + ' <span class="text-danger">*</span>';
            hint.textContent = a.hint + ' · wajib diisi';
        } else {
            input.value = ''; status.textContent = '';
        }
    }

    jenis.addEventListener('change', function () { perbarui(); if (file.files[0]) pindai(); });
    file.addEventListener('change', pindai);

    function pindai() {
        var a = aturan();
        var f = file.files && file.files[0];
        if (!a || !f || typeof OcrIdentitas === 'undefined') return;

        status.className = 'mt-1 small text-muted';
        status.textContent = 'Mencoba membaca nomor dari berkas…';

        OcrIdentitas.baca(f, a.field, function (p) { status.textContent = 'Membaca… ' + p + '%'; })
            .then(function (h) {
                if (!h.nomor) {
                    status.textContent = 'Nomor tidak terbaca — mohon ketik manual.';
                    status.className = 'mt-1 small text-warning';
                    return;
                }
                input.value = h.nomor;

                // Nomor dari lapisan teks PDF diambil apa adanya dari berkas,
                // bukan ditebak — tak perlu diperlakukan seragam dengan hasil
                // pengenalan gambar yang memang rawan salah baca.
                if (h.sumber === 'teks') {
                    input.classList.remove('border-warning');
                    status.textContent = 'Diambil langsung dari teks PDF — tetap periksa sebelum mengirim.';
                    status.className = 'mt-1 small text-success';
                } else {
                    input.classList.add('border-warning');
                    status.textContent = 'Terbaca dari berkas — WAJIB dicocokkan dengan kartu asli.';
                    status.className = 'mt-1 small text-warning';
                }

                // Kartu NPWP memuat dua nomor; ambil sekalian yang 16 digit.
                if (jenis.value === 'npwp') {
                    OcrIdentitas.baca(f, 'no_npwp16', null).then(function (h16) {
                        if (h16.nomor && ! npwp16.value) {
                            npwp16.value = h16.nomor;
                            if (h16.sumber !== 'teks') npwp16.classList.add('border-warning');
                            status.textContent += ' NPWP-16 juga terbaca.';
                        }
                    }).catch(function () { /* diabaikan — NPWP-16 memang opsional */ });
                }
            })
            .catch(function () {
                status.textContent = 'Gagal membaca berkas — ketik nomornya manual.';
                status.className = 'mt-1 small text-muted';
            });
    }

    perbarui();
})();
</script>
<script>window.MIC_BASE_URL = '<?= base_url() ?>';</script>
<script src="<?= base_url('lib/tesseract/tesseract.min.js') ?>"></script>
<script src="<?= base_url('js/ocr-identitas.js') ?>?v=2.24.9"></script>
<?= $this->endSection() ?>
