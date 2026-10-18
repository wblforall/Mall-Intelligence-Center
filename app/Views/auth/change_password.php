<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Create New Password — Mall Intelligence Center</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link rel="stylesheet" href="<?= base_url('css/auth-simple.css') ?>?v=<?= @filemtime(FCPATH . 'css/auth-simple.css') ?>">
</head>
<body class="auth-simple">
<div class="container" style="max-width:460px">
    <div class="text-center mb-4 auth-brand">
        <div class="fw-bold fs-5">Mall Intelligence Center</div>
        <div class="text-muted small">PT. Wulandari Bangun Laksana Tbk.</div>
    </div>
    <div class="card p-4">
        <div class="mb-3">
            <h5 class="fw-bold mb-1"><i class="bi bi-shield-lock me-2 text-primary"></i>Create a new password</h5>
            <div class="text-muted small">Your default password must be changed before you continue. Make sure it meets the security requirements below.</div>
        </div>

        <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger py-2 small"><?= session()->getFlashdata('error') ?></div>
        <?php endif; ?>

        <?php if ($errors = session()->getFlashdata('pw_errors')): ?>
        <div class="alert alert-danger py-2 small">
            <ul class="mb-0 ps-3">
                <?php foreach ($errors as $e): ?>
                <li><?= esc($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <form method="post" action="<?= base_url('change-password') ?>" id="pwForm">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label fw-semibold">New password</label>
                <div class="input-group">
                    <input type="password" name="password" id="password" class="form-control" required autofocus>
                    <button type="button" class="btn btn-outline-secondary" id="togglePw"><i class="bi bi-eye"></i></button>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Confirm password</label>
                <div class="input-group">
                    <input type="password" name="password_confirm" id="password_confirm" class="form-control" required>
                    <button type="button" class="btn btn-outline-secondary" id="togglePw2"><i class="bi bi-eye"></i></button>
                </div>
                <div id="matchMsg" class="small mt-1 d-none"></div>
            </div>

            <div class="border rounded p-2 mb-3 bg-light">
                <div class="small fw-semibold mb-1 text-muted">Password requirements:</div>
                <div class="req-item" id="req-len"><i class="bi bi-circle me-1"></i>At least 8 characters</div>
                <div class="req-item" id="req-upper"><i class="bi bi-circle me-1"></i>At least 1 uppercase letter (A–Z)</div>
                <div class="req-item" id="req-lower"><i class="bi bi-circle me-1"></i>At least 1 lowercase letter (a–z)</div>
                <div class="req-item" id="req-num"><i class="bi bi-circle me-1"></i>At least 1 number (0–9)</div>
                <div class="req-item" id="req-sym"><i class="bi bi-circle me-1"></i>At least 1 symbol (!@#$%^&* etc.)</div>
            </div>

            <button type="submit" class="btn btn-primary w-100" id="submitBtn" disabled>
                <i class="bi bi-check-circle me-1"></i>Save password
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
    const val = pw.value;
    for (const [id, fn] of Object.entries(rules)) {
        const el = document.getElementById(id);
        const ok = fn(val);
        el.className = 'req-item ' + (ok ? 'ok' : 'fail');
        el.querySelector('i').className = 'bi me-1 ' + (ok ? 'bi-check-circle-fill' : 'bi-x-circle');
        if (! ok) allOk = false;
    }
    checkMatch(allOk);
}

function checkMatch(rulesOk) {
    const ok = pw.value && pw.value === pw2.value;
    if (pw2.value) {
        matchMsg.classList.remove('d-none');
        matchMsg.textContent = ok ? '✓ Passwords match' : '✗ Passwords do not match';
        matchMsg.className = 'small mt-1 ' + (ok ? 'text-success' : 'text-danger');
    } else {
        matchMsg.classList.add('d-none');
    }
    submitBtn.disabled = !(rulesOk && ok);
}

pw.addEventListener('input', checkRules);
pw2.addEventListener('input', () => {
    const allOk = Object.values(rules).every(fn => fn(pw.value));
    checkMatch(allOk);
});

// Toggle visibility
document.getElementById('togglePw').addEventListener('click', function() {
    const type = pw.type === 'password' ? 'text' : 'password';
    pw.type = type;
    this.querySelector('i').className = 'bi bi-eye' + (type === 'text' ? '-slash' : '');
});
document.getElementById('togglePw2').addEventListener('click', function() {
    const type = pw2.type === 'password' ? 'text' : 'password';
    pw2.type = type;
    this.querySelector('i').className = 'bi bi-eye' + (type === 'text' ? '-slash' : '');
});
</script>
</body>
</html>
