<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — Mall Intelligence Center</title>
<link rel="icon" type="image/png" href="<?= base_url('img/mic-logo.png') ?>">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
* { box-sizing: border-box; }

body {
    background: #0f172a;
    min-height: 100vh;
    display: flex;
    align-items: center;
    overflow: hidden;
}

/* ── Background orbs ── */
.orb {
    position: fixed;
    border-radius: 50%;
    filter: blur(90px);
    pointer-events: none;
    z-index: 0;
}
.orb-1 {
    width: 520px; height: 520px;
    background: radial-gradient(circle, rgba(59,130,246,.35) 0%, transparent 70%);
    top: -160px; left: -160px;
    animation: drift1 14s ease-in-out infinite;
}
.orb-2 {
    width: 420px; height: 420px;
    background: radial-gradient(circle, rgba(99,102,241,.3) 0%, transparent 70%);
    bottom: -120px; right: -100px;
    animation: drift2 18s ease-in-out infinite;
}
.orb-3 {
    width: 280px; height: 280px;
    background: radial-gradient(circle, rgba(20,184,166,.25) 0%, transparent 70%);
    top: 50%; right: 15%;
    animation: drift3 11s ease-in-out infinite;
}

@keyframes drift1 {
    0%, 100% { transform: translate(0, 0) scale(1); }
    40%       { transform: translate(50px, 40px) scale(1.08); }
    70%       { transform: translate(-30px, 60px) scale(0.94); }
}
@keyframes drift2 {
    0%, 100% { transform: translate(0, 0) scale(1); }
    35%       { transform: translate(-60px, -40px) scale(1.06); }
    65%       { transform: translate(40px, -60px) scale(0.96); }
}
@keyframes drift3 {
    0%, 100% { transform: translateY(0) scale(1); }
    50%       { transform: translateY(-50px) scale(1.1); }
}

/* ── Card ── */
.login-wrap {
    position: relative;
    z-index: 1;
}
.login-card {
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 1.25rem;
    background: rgba(15,23,42,.85);
    backdrop-filter: blur(24px);
    box-shadow: 0 24px 80px rgba(0,0,0,.5), 0 0 0 1px rgba(59,130,246,.08);
    animation: cardIn .6s cubic-bezier(.22,.68,0,1.2) both;
}
@keyframes cardIn {
    from { opacity: 0; transform: translateY(28px) scale(.97); }
    to   { opacity: 1; transform: translateY(0)    scale(1); }
}

/* ── Logo ── */
.logo-wrap img {
    animation: logoFloat 4s ease-in-out infinite;
    filter: drop-shadow(0 8px 24px rgba(59,130,246,.4));
}
@keyframes logoFloat {
    0%, 100% { transform: translateY(0); }
    50%       { transform: translateY(-8px); }
}

/* ── Form ── */
.form-label { color: #94a3b8; }
.input-group-text {
    background: rgba(255,255,255,.05);
    border-color: rgba(255,255,255,.1);
    color: #64748b;
}
.form-control {
    background: rgba(255,255,255,.05);
    border-color: rgba(255,255,255,.1);
    color: #e2e8f0;
    transition: border-color .25s, box-shadow .25s;
}
.form-control::placeholder { color: #475569; }
.form-control:focus {
    background: rgba(255,255,255,.07);
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59,130,246,.2);
    color: #f1f5f9;
}
.form-control:focus + .input-group-text,
.input-group:focus-within .input-group-text {
    border-color: #3b82f6;
    color: #3b82f6;
}

/* ── Button ── */
.btn-login {
    position: relative;
    overflow: hidden;
    background: linear-gradient(135deg, #2563eb, #4f46e5);
    border: none;
    transition: transform .2s, box-shadow .2s;
}
.btn-login:hover {
    transform: translateY(-1px);
    box-shadow: 0 8px 24px rgba(59,130,246,.45);
}
.btn-login:active { transform: translateY(0); }
.btn-login::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(105deg, transparent 40%, rgba(255,255,255,.18) 50%, transparent 60%);
    transform: translateX(-100%);
    transition: transform .45s ease;
}
.btn-login:hover::after { transform: translateX(100%); }

/* ── Alerts ── */
.alert { font-size: .85rem; border-radius: .6rem; }
.alert-danger  { background: rgba(239,68,68,.12);  border-color: rgba(239,68,68,.25);  color: #fca5a5; }
.alert-success { background: rgba(34,197,94,.12);  border-color: rgba(34,197,94,.25);  color: #86efac; }

/* ── Footer text ── */
.login-footer { color: #334155; font-size: .72rem; text-align: center; margin-top: 1.5rem; }
</style>
</head>
<body>

<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>

<div class="container login-wrap">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <div class="card login-card p-4">

                <div class="text-center mb-4 logo-wrap">
                    <img src="<?= base_url('img/mic-logo.png') ?>" alt="MIC Logo" style="height:180px;object-fit:contain">
                </div>

                <?php if (session()->getFlashdata('success')): ?>
                <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
                <?php endif; ?>
                <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
                <?php endif; ?>

                <form method="POST" action="<?= base_url('login') ?>">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" name="email" class="form-control" value="<?= old('email') ?>" placeholder="Email Anda" required autofocus>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-semibold">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" name="password" id="passwordInput" class="form-control" placeholder="••••••••" required>
                            <button type="button" class="input-group-text" id="togglePassword" style="cursor:pointer;transition:color .2s">
                                <i class="bi bi-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-login btn-primary w-100 fw-semibold py-2">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Masuk
                    </button>
                    <div class="text-center mt-3">
                        <a href="<?= base_url('forgot-password') ?>" class="small" style="color:rgba(255,255,255,.5);text-decoration:none;" onmouseover="this.style.color='rgba(255,255,255,.8)'" onmouseout="this.style.color='rgba(255,255,255,.5)'">Lupa password?</a>
                    </div>
                </form>
            </div>
            <div class="login-footer">Mall Intelligence Center &mdash; eWalk &amp; Pentacity</div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.querySelector('form').addEventListener('submit', function () {
    const btn = this.querySelector('[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Memuat...';
});

document.getElementById('togglePassword').addEventListener('click', function () {
    const input = document.getElementById('passwordInput');
    const icon  = document.getElementById('toggleIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('bi-eye-slash', 'bi-eye');
    }
});
</script>
</body>
</html>
