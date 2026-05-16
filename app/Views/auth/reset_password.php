<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Reset Password — Mall Intelligence Center</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
body { background:#f0f2f5; min-height:100vh; display:flex; align-items:center; justify-content:center; }
.card { border:none; border-radius:1rem; box-shadow:0 8px 32px rgba(0,0,0,.10); }
.req-item { font-size:.85rem; }
.req-item.ok   { color:#16a34a; }
.req-item.fail { color:#dc2626; }
</style>
</head>
<body>
<div class="container" style="max-width:460px">
    <div class="text-center mb-4">
        <div class="fw-bold fs-5">Mall Intelligence Center</div>
        <div class="text-muted small">PT. Wulandari Bangun Laksana Tbk.</div>
    </div>
    <div class="card p-4">
        <h5 class="fw-bold mb-1"><i class="bi bi-shield-lock me-2 text-primary"></i>Buat Password Baru</h5>
        <p class="text-muted small mb-3">Pastikan password memenuhi standar keamanan di bawah.</p>

        <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger py-2 small"><?= session()->getFlashdata('error') ?></div>
        <?php endif; ?>
        <?php if ($errors = session()->getFlashdata('pw_errors')): ?>
        <div class="alert alert-danger py-2 small"><ul class="mb-0 ps-3"><?php foreach ($errors as $e): ?><li><?= esc($e) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>

        <form method="post" action="<?= base_url('reset-password/' . $token) ?>">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label fw-semibold">Password Baru</label>
                <div class="input-group">
                    <input type="password" name="password" id="password" class="form-control" required autofocus>
                    <button type="button" class="btn btn-outline-secondary" id="togglePw"><i class="bi bi-eye"></i></button>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Konfirmasi Password</label>
                <div class="input-group">
                    <input type="password" name="password_confirm" id="password_confirm" class="form-control" required>
                    <button type="button" class="btn btn-outline-secondary" id="togglePw2"><i class="bi bi-eye"></i></button>
                </div>
                <div id="matchMsg" class="small mt-1 d-none"></div>
            </div>

            <div class="border rounded p-2 mb-3 bg-light">
                <div class="small fw-semibold mb-1 text-muted">Syarat password:</div>
                <div class="req-item" id="req-len"><i class="bi bi-circle me-1"></i>Minimal 8 karakter</div>
                <div class="req-item" id="req-upper"><i class="bi bi-circle me-1"></i>Minimal 1 huruf kapital (A–Z)</div>
                <div class="req-item" id="req-lower"><i class="bi bi-circle me-1"></i>Minimal 1 huruf kecil (a–z)</div>
                <div class="req-item" id="req-num"><i class="bi bi-circle me-1"></i>Minimal 1 angka (0–9)</div>
                <div class="req-item" id="req-sym"><i class="bi bi-circle me-1"></i>Minimal 1 simbol (!@#$%^&* dll)</div>
            </div>

            <button type="submit" class="btn btn-primary w-100" id="submitBtn" disabled>
                <i class="bi bi-check-circle me-1"></i>Simpan Password
            </button>
        </form>
    </div>
</div>

<script>
const pw = document.getElementById('password');
const pw2 = document.getElementById('password_confirm');
const matchMsg = document.getElementById('matchMsg');
const submitBtn = document.getElementById('submitBtn');
const rules = {
    'req-len':   p => p.length >= 8,
    'req-upper': p => /[A-Z]/.test(p),
    'req-lower': p => /[a-z]/.test(p),
    'req-num':   p => /[0-9]/.test(p),
    'req-sym':   p => /[\W_]/.test(p),
};
function checkRules() {
    let allOk = true;
    for (const [id, fn] of Object.entries(rules)) {
        const el = document.getElementById(id);
        const ok = fn(pw.value);
        el.className = 'req-item ' + (ok ? 'ok' : 'fail');
        el.querySelector('i').className = 'bi me-1 ' + (ok ? 'bi-check-circle-fill' : 'bi-x-circle');
        if (!ok) allOk = false;
    }
    checkMatch(allOk);
}
function checkMatch(rulesOk) {
    const ok = pw.value && pw.value === pw2.value;
    if (pw2.value) {
        matchMsg.classList.remove('d-none');
        matchMsg.textContent = ok ? '✓ Password cocok' : '✗ Password tidak cocok';
        matchMsg.className = 'small mt-1 ' + (ok ? 'text-success' : 'text-danger');
    } else { matchMsg.classList.add('d-none'); }
    submitBtn.disabled = !(rulesOk && ok);
}
pw.addEventListener('input', checkRules);
pw2.addEventListener('input', () => checkMatch(Object.values(rules).every(fn => fn(pw.value))));
document.getElementById('togglePw').addEventListener('click', function() {
    pw.type = pw.type === 'password' ? 'text' : 'password';
    this.querySelector('i').className = 'bi bi-eye' + (pw.type === 'text' ? '-slash' : '');
});
document.getElementById('togglePw2').addEventListener('click', function() {
    pw2.type = pw2.type === 'password' ? 'text' : 'password';
    this.querySelector('i').className = 'bi bi-eye' + (pw2.type === 'text' ? '-slash' : '');
});
</script>
</body>
</html>
