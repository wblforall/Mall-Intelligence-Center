<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= base_url('ai-monitor') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-laptop me-2"></i>Perangkat &amp; Token</h4>
        <small class="text-muted">Laptop bisa mendaftar otomatis dengan kunci enrollment, atau diberi token manual.</small>
    </div>
</div>

<?php if ($tokenBaru): ?>
<?php // Token mentah hanya tampil SEKALI di sini. Sesudah halaman ditinggalkan,
      // hanya hash-nya yang tersimpan — tak bisa ditampilkan ulang. ?>
<div class="alert alert-success">
    <div class="fw-semibold mb-1"><i class="bi bi-key me-1"></i>Token untuk "<?= esc($tokenBaru['label']) ?>" berhasil dibuat</div>
    <p class="small mb-2">Salin sekarang — token hanya ditampilkan <strong>sekali</strong>. Jika hilang, buat token baru (yang lama otomatis berhenti dipakai).</p>
    <div class="input-group input-group-sm" style="max-width:640px">
        <input type="text" class="form-control font-monospace" id="tokenBaru" value="<?= esc($tokenBaru['token']) ?>" readonly>
        <button class="btn btn-outline-secondary" type="button" onclick="navigator.clipboard.writeText(document.getElementById('tokenBaru').value)">
            <i class="bi bi-clipboard"></i> Salin
        </button>
    </div>
    <p class="small text-muted mb-0 mt-2">
        Pasang di laptop (PowerShell, dari folder <code>public/agen-ai/</code>):<br>
        <code>.\pasang.ps1 -Endpoint "<?= esc(base_url('api/ai-monitor/ingest')) ?>" -Token "<?= esc($tokenBaru['token']) ?>"</code>
    </p>
</div>
<?php endif; ?>

<?php // ── Kunci enrollment: pendaftaran otomatis laptop ──────────────── ?>
<div class="card mb-3 border-primary-subtle">
    <div class="card-header py-2 d-flex align-items-center justify-content-between">
        <span class="fw-semibold small"><i class="bi bi-upc-scan me-1"></i>Kunci Enrollment</span>
    </div>
    <div class="card-body">
        <p class="small text-muted mb-2">
            Laptop yang dipasang dengan kunci ini <strong>muncul otomatis</strong> di daftar bawah (belum ditautkan ke
            karyawan — tautkan sendiri setelah muncul). Satu kunci dipakai bersama seluruh tim.
        </p>
        <div class="input-group input-group-sm mb-2" style="max-width:640px">
            <span class="input-group-text"><i class="bi bi-key"></i></span>
            <input type="text" class="form-control font-monospace" id="enrollKey" value="<?= esc($enrollKey) ?>" readonly>
            <button class="btn btn-outline-secondary" type="button" onclick="navigator.clipboard.writeText(document.getElementById('enrollKey').value)">
                <i class="bi bi-clipboard"></i> Salin
            </button>
        </div>
        <p class="small text-muted mb-2">
            Pasang di laptop (PowerShell, dari folder <code>public/agen-ai/</code>):<br>
            <code>.\pasang.ps1 -Endpoint "<?= esc(base_url('api/ai-monitor/enroll')) ?>" -EnrollKey "<?= esc($enrollKey) ?>"</code>
        </p>
        <form method="POST" action="<?= base_url('ai-monitor/perangkat/regen-enroll-key') ?>"
              onsubmit="return confirm('Regenerasi kunci enrollment? Pemasangan laptop BARU harus pakai kunci baru. Laptop yang sudah terdaftar tetap jalan.')">
            <?= csrf_field() ?>
            <button class="btn btn-sm btn-outline-warning"><i class="bi bi-arrow-repeat me-1"></i>Regenerasi</button>
        </form>
    </div>
</div>

<?php // ── Password copot (uninstall) — KHUSUS admin ─────────────────── ?>
<?php if (session()->get('role_is_admin') || session()->get('user_role') === 'admin'): ?>
<div class="card mb-3 border-danger-subtle">
    <div class="card-header py-2 d-flex align-items-center justify-content-between">
        <span class="fw-semibold small"><i class="bi bi-shield-lock me-1"></i>Password Copot (Uninstall)</span>
        <?php if ($copotSudahDiset): ?>
        <span class="badge bg-success">Sudah diatur</span>
        <?php else: ?>
        <span class="badge bg-secondary">Belum diatur</span>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <p class="small text-muted mb-2">
            Password ini diperlukan untuk mencopot agen dari laptop (bersama hak admin). Berlaku untuk
            <strong>semua perangkat</strong>; perubahan tersinkron ke laptop pada laporan berikutnya.
            Server hanya menyimpan hash-nya — ketik ulang password baru untuk menggantinya.
        </p>
        <form method="POST" action="<?= base_url('ai-monitor/perangkat/set-copot-password') ?>"
              onsubmit="if(this.password.value!==this.password2.value){alert('Konfirmasi password tidak cocok.');return false;} return confirm('Simpan password copot baru? Berlaku untuk semua perangkat.');">
            <?= csrf_field() ?>
            <div class="row g-2" style="max-width:640px">
                <div class="col-sm-6">
                    <input type="password" name="password" class="form-control form-control-sm" required minlength="8" placeholder="Password baru (min. 8 karakter)">
                </div>
                <div class="col-sm-6">
                    <input type="password" name="password2" class="form-control form-control-sm" required minlength="8" placeholder="Ulangi password">
                </div>
            </div>
            <button class="btn btn-sm btn-outline-danger mt-2"><i class="bi bi-save me-1"></i>Simpan Password Copot</button>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="alert alert-info small">
    <i class="bi bi-info-circle me-1"></i>
    Pemantauan ini <strong>terbuka</strong>: beri tahu pemilik laptop sebelum memasang. Skrip pemasang
    menampilkan pemberitahuan dan dapat dicopot pemakainya. Kata sandi dan token dalam percakapan
    otomatis disamarkan sebelum disimpan.
</div>

<div class="row g-3">
<div class="col-lg-4">
    <div class="card">
    <div class="card-header py-2"><span class="fw-semibold small"><i class="bi bi-plus-lg me-1"></i>Terbitkan Token Perangkat</span></div>
    <div class="card-body">
        <p class="small text-muted mb-2">Untuk laptop yang tidak memakai enrollment otomatis (token dipasang manual).</p>
        <form method="POST" action="<?= base_url('ai-monitor/perangkat/buat') ?>">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label small fw-semibold">Pemilik (karyawan)</label>
                <select name="employee_id" class="form-select" required>
                    <option value="">— pilih karyawan —</option>
                    <?php foreach ($karyawan as $k): ?>
                    <option value="<?= (int) $k['id'] ?>"><?= esc($k['nama']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold">Label Perangkat</label>
                <input type="text" name="label" class="form-control" required placeholder="mis. Laptop Budi - IT">
            </div>
            <button class="btn btn-primary btn-sm w-100"><i class="bi bi-key me-1"></i>Buat Token</button>
        </form>
    </div>
    </div>
</div>

<div class="col-lg-8">
    <div class="card">
    <div class="card-header py-2"><span class="fw-semibold small"><i class="bi bi-list-ul me-1"></i>Daftar Perangkat</span></div>
    <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
    <thead class="table-light">
    <tr>
        <th>Perangkat</th><th>Pemilik</th><th>Lapor terakhir</th>
        <th class="text-center">Status</th><th class="text-end">Aksi</th>
    </tr>
    </thead>
    <tbody>
    <?php if (! $devices): ?>
    <tr><td colspan="5" class="text-center text-muted py-4">
        <i class="bi bi-laptop d-block fs-3 mb-2 opacity-25"></i>Belum ada perangkat terdaftar.
    </td></tr>
    <?php else: foreach ($devices as $d): ?>
    <tr class="<?= $d['aktif'] ? '' : 'opacity-50' ?>">
        <td>
            <?php // Nama = alias yang diatur IT (nama komputer bisa acak).
                  // Alias ini menang dan tak tertimpa saat enroll ulang. ?>
            <form method="POST" action="<?= base_url('ai-monitor/perangkat/ubah-label') ?>" class="input-group input-group-sm" style="max-width:260px">
                <?= csrf_field() ?>
                <input type="hidden" name="device_id" value="<?= (int) $d['id'] ?>">
                <input type="text" name="label" class="form-control form-control-sm fw-medium" value="<?= esc($d['label'], 'attr') ?>" required maxlength="100">
                <button class="btn btn-outline-secondary btn-sm" title="Simpan nama"><i class="bi bi-check-lg"></i></button>
            </form>
            <?php if ($d['host_terakhir']): ?>
            <span class="d-block small text-muted mt-1"><i class="bi bi-pc-display me-1"></i><?= esc($d['host_terakhir']) ?><?= $d['akun_terakhir'] ? ' · ' . esc($d['akun_terakhir']) : '' ?></span>
            <?php endif; ?>
            <?php if (! empty($d['machine_id'])): ?>
            <span class="d-block small text-muted"><i class="bi bi-upc me-1"></i><?= esc($d['machine_id']) ?><?= $d['enrolled_at'] ? ' · enroll ' . tgl_indo($d['enrolled_at'], true) : '' ?></span>
            <?php endif; ?>
        </td>
        <td>
            <?php if (! empty($d['employee_id'])): ?>
            <?= esc($d['nama'] ?? '—') ?>
            <?php if (! empty($d['dept'])): ?><span class="d-block small text-muted"><?= esc($d['dept']) ?></span><?php endif; ?>
            <?php else: ?>
            <span class="badge bg-warning text-dark mb-1">Belum ditautkan</span>
            <form method="POST" action="<?= base_url('ai-monitor/perangkat/tautkan') ?>" class="input-group input-group-sm" style="max-width:260px">
                <?= csrf_field() ?>
                <input type="hidden" name="device_id" value="<?= (int) $d['id'] ?>">
                <select name="employee_id" class="form-select form-select-sm" required>
                    <option value="">— pilih pemilik —</option>
                    <?php foreach ($karyawan as $k): ?>
                    <option value="<?= (int) $k['id'] ?>"><?= esc($k['nama']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-outline-primary btn-sm" title="Tautkan"><i class="bi bi-link-45deg"></i></button>
            </form>
            <?php endif; ?>
        </td>
        <td>
            <?php if ($d['lapor_at']): ?>
            <span class="small"><?= tgl_indo($d['lapor_at'], true) ?></span>
            <?php else: ?>
            <span class="badge bg-secondary">Belum pernah</span>
            <?php endif; ?>
        </td>
        <td class="text-center">
            <?php if ($d['aktif']): ?>
            <span class="badge bg-success">Aktif</span>
            <?php else: ?>
            <span class="badge bg-secondary">Nonaktif</span>
            <?php endif; ?>
            <?php if (! empty($d['diblokir'])): ?>
            <span class="d-block mt-1"><span class="badge bg-danger"><i class="bi bi-slash-circle me-1"></i>Diblokir</span></span>
            <?php if (! empty($d['alasan_blokir'])): ?>
            <span class="d-block small text-danger mt-1"><?= esc($d['alasan_blokir']) ?></span>
            <?php endif; ?>
            <?php if (! empty($d['blokir_at'])): ?>
            <span class="d-block small text-muted"><?= tgl_indo($d['blokir_at'], true) ?></span>
            <?php endif; ?>
            <?php endif; ?>
        </td>
        <td class="text-end">
            <div class="d-inline-flex flex-column gap-1 align-items-end">
                <?php if (empty($d['diblokir'])): ?>
                <form method="POST" action="<?= base_url('ai-monitor/perangkat/blokir') ?>"
                      onsubmit="var a=prompt('Alasan menghentikan akses AI untuk &quot;<?= esc($d['label'], 'js') ?>&quot;:'); if(a===null||a.trim()===''){return false;} this.alasan.value=a; return true;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="device_id" value="<?= (int) $d['id'] ?>">
                    <input type="hidden" name="alasan" value="">
                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-pause-circle me-1"></i>Hentikan Sementara</button>
                </form>
                <?php else: ?>
                <form method="POST" action="<?= base_url('ai-monitor/perangkat/buka-blokir') ?>"
                      onsubmit="return confirm('Pulihkan akses AI untuk perangkat ini?')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="device_id" value="<?= (int) $d['id'] ?>">
                    <button class="btn btn-sm btn-outline-success"><i class="bi bi-play-circle me-1"></i>Pulihkan</button>
                </form>
                <?php endif; ?>
                <form method="POST" action="<?= base_url('ai-monitor/perangkat/' . (int) $d['id'] . '/nonaktif') ?>"
                      onsubmit="return confirm('<?= $d['aktif'] ? 'Nonaktifkan' : 'Aktifkan' ?> perangkat ini?')">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm <?= $d['aktif'] ? 'btn-outline-danger' : 'btn-outline-success' ?>">
                        <?= $d['aktif'] ? 'Nonaktifkan' : 'Aktifkan' ?>
                    </button>
                </form>
            </div>
        </td>
    </tr>
    <?php endforeach; endif; ?>
    </tbody>
    </table>
    </div>
    <div class="card-footer bg-transparent">
        <small class="text-muted"><i class="bi bi-info-circle me-1"></i>
            <strong>Hentikan Sementara</strong> = agen laptop berhenti mengirim tapi tokennya tetap sah (bisa langsung dipulihkan).
            <strong>Nonaktifkan</strong> = token berhenti diterima server. Keduanya tak menghapus riwayat.
        </small>
    </div>
    </div>
</div>
</div>

<?= $this->endSection() ?>
