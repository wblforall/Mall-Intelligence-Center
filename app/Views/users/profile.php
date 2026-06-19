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
        <img src="<?= base_url('uploads/people/photos/'.$employee['foto']) ?>" style="width:40px;height:40px;border-radius:50%;object-fit:cover">
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

    <!-- Sertifikat -->
    <div class="card mb-4">
    <div class="card-header"><h6 class="mb-0 fw-semibold"><i class="bi bi-patch-check me-2"></i>Sertifikat</h6></div>
    <div class="card-body p-0">
    <?php if (empty($certificates)): ?>
    <p class="text-muted text-center py-4 small mb-0">Belum ada sertifikat.</p>
    <?php else: ?>
    <div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
    <thead class="table-light"><tr><th class="ps-3">Nama</th><th>Penerbit</th><th>Terbit</th><th>Kadaluarsa</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($certificates as $c): ?>
    <tr>
        <td class="ps-3 fw-semibold small"><?= esc($c['nama']) ?></td>
        <td class="small text-muted"><?= esc($c['penerbit'] ?? '—') ?></td>
        <td class="small text-nowrap"><?= $c['tanggal_terbit'] ? date('d M Y', strtotime($c['tanggal_terbit'])) : '—' ?></td>
        <td class="small text-nowrap"><?= $c['tanggal_kadaluarsa'] ? date('d M Y', strtotime($c['tanggal_kadaluarsa'])) : '—' ?></td>
        <td><span class="badge bg-<?= esc($c['status']['color'] ?? 'secondary') ?>-subtle text-<?= esc($c['status']['color'] ?? 'secondary') ?>"><?= esc($c['status']['label'] ?? '—') ?></span></td>
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
    <thead class="table-light"><tr><th class="ps-3">Dokumen</th><th>File</th><th>Status</th><th>Catatan HR</th><th>Tanggal</th></tr></thead>
    <tbody>
    <?php foreach ($documents as $d): ?>
    <tr>
        <td class="ps-3 fw-semibold small"><?= esc(\App\Models\EmployeeDocumentModel::jenisLabel($d['jenis'], $d['nama_dokumen'])) ?></td>
        <td class="small"><a href="<?= base_url('uploads/people/docs/'.$d['file_name']) ?>" target="_blank"><i class="bi bi-file-earmark-text me-1"></i>Lihat</a></td>
        <td><span class="badge bg-<?= $sb[$d['status']] ?? 'secondary' ?>"><?= ucfirst($d['status']) ?></span></td>
        <td class="small text-muted"><?= esc($d['catatan'] ?? '') ?: '—' ?></td>
        <td class="small text-nowrap text-muted"><?= date('d M Y', strtotime($d['created_at'])) ?></td>
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
    $textFields = ['no_hp'=>'No. HP','email'=>'Email','alamat'=>'Alamat','alamat_non_bpn'=>'Alamat (Non-BPN)','jurusan'=>'Jurusan'];
    $selectFields = [
        'pendidikan'        => ['', 'SD', 'SMP', 'SMA', 'SMK', 'D1', 'D2', 'D3', 'D4', 'S1', 'S2', 'S3'],
        'status_pernikahan' => ['', 'Belum Menikah', 'Menikah', 'Cerai'],
        'agama'             => ['', 'Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu'],
    ];
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
            <input type="<?= $f === 'email' ? 'email' : 'text' ?>" id="in_<?= $f ?>" name="<?= $f ?>" class="form-control form-control-sm" value="<?= esc($employee[$f] ?? '') ?>" disabled>
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
        <select name="jenis" class="form-select form-select-sm" required onchange="document.getElementById('docNamaWrap').classList.toggle('d-none', this.value!=='lainnya')">
            <option value="">— pilih —</option>
            <?php foreach ($jenisDok as $k => $lbl): ?><option value="<?= $k ?>"><?= esc($lbl) ?></option><?php endforeach; ?>
        </select>
    </div>
    <div class="mb-3 d-none" id="docNamaWrap">
        <label class="form-label small fw-semibold">Nama Dokumen</label>
        <input type="text" name="nama_dokumen" class="form-control form-control-sm" placeholder="mis. Sertifikat BNSP">
    </div>
    <div class="mb-2">
        <label class="form-label small fw-semibold">File</label>
        <input type="file" name="file" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,.pdf" required>
        <div class="form-text">JPG, PNG, atau PDF · maks 5 MB. Akan diverifikasi HR.</div>
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
</script>
<?= $this->endSection() ?>
