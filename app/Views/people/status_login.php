<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
// [label, warna, ikon, kelas teks badge]
// Kelas teks ikut di sini, bukan ditambal per pemakaian: bg-warning butuh
// text-dark agar terbaca (putih di atas kuning hanya ~1,6:1), dan kalau warna
// keadaan lain suatu saat diubah, penyesuaiannya cukup di satu tempat.
$L = [
    'belum_akun'    => ['Belum dibuatkan akun',        'secondary', 'bi-person-slash',        ''],
    'akun_nonaktif' => ['Akun dinonaktifkan',          'dark',      'bi-slash-circle',        ''],
    'belum_login'   => ['Belum pernah login',          'danger',    'bi-door-closed',         ''],
    'belum_ganti'   => ['Login, belum ganti password', 'warning',   'bi-shield-exclamation', ' text-dark'],
    'aktif'         => ['Aktif',                       'success',   'bi-check-circle',        ''],
];
$qs = fn (array $ubah) => '?' . http_build_query(array_filter(
    ['dept' => $fDept ?: null, 'keadaan' => $fKeadaan ?: null] + [], fn ($v) => $v !== null) + $ubah);
?>

<div class="d-flex align-items-center gap-2 mb-4 flex-wrap">
    <a href="<?= base_url('people/employees') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h4 class="fw-bold mb-0">Status Login Karyawan</h4>
    <a href="<?= base_url('people/status-login') . $qs(['export' => 'csv']) ?>" class="btn btn-sm btn-outline-primary ms-auto">
        <i class="bi bi-download me-1"></i>Ekspor CSV
    </a>
</div>

<p class="small text-muted">
    Memantau sejauh mana karyawan benar-benar sudah memakai MIC setelah didaftarkan.
    Hanya karyawan berstatus <b>aktif</b> yang dihitung.
</p>

<!-- Ringkasan -->
<div class="row g-3 mb-4">
    <?php foreach ($L as $k => [$lbl, $warna, $ikon, $teks]):
        $n = $rekap['per_keadaan'][$k] ?? 0;
        // "Akun dinonaktifkan" jarang terjadi. Ditampilkan hanya bila memang
        // ada — sebagai kartu "0" permanen ia cuma jadi kolom kosong yang
        // mengganggu, dan justru muncul tepat saat perlu diperhatikan.
        if ($k === 'akun_nonaktif' && $n === 0 && $fKeadaan !== $k) continue;
        $pct = $rekap['total'] ? round($n / $rekap['total'] * 100) : 0; ?>
    <div class="col-6 col-lg-3">
        <a href="<?= base_url('people/status-login') . '?' . http_build_query(array_filter(['dept' => $fDept ?: null, 'keadaan' => $fKeadaan === $k ? null : $k])) ?>"
           class="card h-100 text-decoration-none <?= $fKeadaan === $k ? 'border-' . $warna : '' ?>">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <i class="bi <?= $ikon ?> text-<?= $warna ?>"></i>
                    <span class="small text-muted"><?= $lbl ?></span>
                </div>
                <div class="fs-3 fw-bold text-<?= $warna ?>"><?= $n ?></div>
                <div class="small text-muted"><?= $pct ?>% dari <?= $rekap['total'] ?> karyawan</div>
            </div>
        </a>
    </div>
    <?php endforeach; ?>
</div>

<!-- Filter departemen -->
<form method="get" class="row g-2 align-items-end mb-3">
    <?php if ($fKeadaan): ?><input type="hidden" name="keadaan" value="<?= esc($fKeadaan) ?>"><?php endif; ?>
    <div class="col-md-4">
        <label class="form-label small fw-semibold">Departemen</label>
        <select name="dept" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">— semua departemen —</option>
            <?php foreach ($departments as $d): ?>
            <option value="<?= $d['id'] ?>" <?= $fDept === (int) $d['id'] ? 'selected' : '' ?>><?= esc($d['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php if ($fDept || $fKeadaan): ?>
    <div class="col-auto">
        <a href="<?= base_url('people/status-login') ?>" class="btn btn-sm btn-outline-secondary">Reset filter</a>
    </div>
    <?php endif; ?>
</form>

<!-- Daftar -->
<div class="card mb-4">
<div class="card-header d-flex align-items-center">
    <h6 class="mb-0 fw-semibold"><i class="bi bi-people me-2"></i>Daftar Karyawan</h6>
    <span class="badge bg-secondary ms-2"><?= count($rows) ?></span>
</div>
<div class="card-body p-0">
<?php if (empty($rows)): ?>
<p class="text-muted text-center py-4 small mb-0">Tidak ada karyawan pada filter ini.</p>
<?php else: ?>
<div class="table-responsive">
<table class="table table-sm align-middle mb-0">
<thead class="table-light"><tr>
    <th class="ps-3">Karyawan</th><th>Email Login</th><th>Status</th><th>Login Terakhir</th><th>Umur Akun</th>
</tr></thead>
<tbody>
<?php foreach ($rows as $r): [$lbl, $warna, , $teks] = $L[$r['keadaan']]; ?>
<tr>
    <td class="ps-3">
        <div class="fw-semibold small"><?= esc($r['nama']) ?></div>
        <div class="text-muted" style="font-size:.72rem"><?= esc($r['dept_name'] ?: '—') ?></div>
    </td>
    <td class="small text-muted"><?= esc($r['email_login'] ?: '') ?: '—' ?></td>
    <td><span class="badge bg-<?= $warna . $teks ?>"><?= $lbl ?></span></td>
    <td class="small text-nowrap"><?= $r['last_login_at'] ? date('d M Y H:i', strtotime($r['last_login_at'])) : '—' ?></td>
    <td class="small text-nowrap text-muted"><?= $r['umur_akun'] !== null ? $r['umur_akun'] . ' hari' : '—' ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php endif; ?>
</div>
</div>

<!-- Rekap per departemen -->
<div class="card">
<div class="card-header"><h6 class="mb-0 fw-semibold"><i class="bi bi-diagram-3 me-2"></i>Rekap per Departemen</h6></div>
<div class="card-body p-0">
<div class="table-responsive">
<table class="table table-sm align-middle mb-0">
<thead class="table-light"><tr>
    <th class="ps-3">Departemen</th><th class="text-center">Jumlah</th>
    <th class="text-center">Belum Ada Akun</th>
    <?php if ($rekap['per_keadaan']['akun_nonaktif'] > 0): ?><th class="text-center">Nonaktif</th><?php endif; ?>
    <th class="text-center">Belum Login</th>
    <th class="text-center">Belum Ganti PW</th><th class="text-center">Aktif</th>
</tr></thead>
<tbody>
<?php foreach ($rekap['per_dept'] as $nama => $d): ?>
<tr>
    <td class="ps-3 fw-semibold small"><?= esc($nama) ?></td>
    <td class="text-center small"><?= $d['total'] ?></td>
    <td class="text-center small<?= $d['belum_akun']  ? ' text-secondary fw-semibold' : ' text-muted' ?>"><?= $d['belum_akun'] ?: '—' ?></td>
    <?php if ($rekap['per_keadaan']['akun_nonaktif'] > 0): ?>
    <td class="text-center small<?= $d['akun_nonaktif'] ? ' fw-semibold' : ' text-muted' ?>"><?= $d['akun_nonaktif'] ?: '—' ?></td>
    <?php endif; ?>
    <td class="text-center small<?= $d['belum_login'] ? ' text-danger fw-semibold'    : ' text-muted' ?>"><?= $d['belum_login'] ?: '—' ?></td>
    <td class="text-center small<?= $d['belum_ganti'] ? ' text-warning fw-semibold'   : ' text-muted' ?>"><?= $d['belum_ganti'] ?: '—' ?></td>
    <td class="text-center small<?= $d['aktif']       ? ' text-success fw-semibold'   : ' text-muted' ?>"><?= $d['aktif'] ?: '—' ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
</div>

<div class="alert alert-secondary small mt-4 mb-0">
    <b>Cara membaca:</b>
    <span class="badge bg-secondary">Belum dibuatkan akun</span> perlu ditindaklanjuti Divisi IT.
    <span class="badge bg-danger">Belum pernah login</span> akunnya ada tapi belum pernah dipakai — kemungkinan email kredensial tidak sampai.
    <span class="badge bg-warning text-dark">Login, belum ganti password</span> sempat masuk lalu berhenti di layar ganti password, sehingga akunnya <b>masih memakai password awal</b>.
    <?php if ($rekap['per_keadaan']['akun_nonaktif'] > 0): ?>
    <span class="badge bg-dark">Akun dinonaktifkan</span> akunnya sudah ada tetapi dimatikan — <b>bukan</b> perlu dibuatkan akun baru; aktifkan kembali lewat menu Users bila memang masih bekerja.
    <?php endif; ?>
</div>

<p class="small text-muted mt-2 mb-0">
    Angka pada kartu dan Rekap per Departemen mengikuti <b>filter departemen</b>, tetapi
    sengaja tidak mengikuti filter keadaan — supaya Anda tetap bisa berpindah antar keadaan.
</p>

<?= $this->endSection() ?>
