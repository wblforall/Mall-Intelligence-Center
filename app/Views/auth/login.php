<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — Mall Intelligence Center</title>
<link rel="icon" type="image/png" href="<?= base_url('img/mic-logo-sm.png') ?>">
<!-- ── PWA ── -->
<link rel="manifest" href="<?= base_url('manifest.webmanifest') ?>">
<meta name="theme-color" content="#091528">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="MIC">
<link rel="apple-touch-icon" href="<?= base_url('img/apple-touch-icon.png') ?>">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link rel="stylesheet" href="<?= base_url('css/bsb-scene.css') ?>?v=<?= @filemtime(FCPATH . 'css/bsb-scene.css') ?>">
<style>
* { box-sizing: border-box; }

body {
    background: #0f172a;
    min-height: 100vh;
    display: flex;
    align-items: center;
    overflow-x: hidden;
}

/* ── Tata letak terbelah: ilustrasi BSB (kiri) + kartu login (kanan) ── */
.split {
    position: relative;
    z-index: 1;
    width: 100%;
    min-height: 100vh;
    display: grid;
    grid-template-columns: minmax(0, 1.45fr) minmax(360px, 480px);
    align-items: center;
    gap: 1rem;
    padding: 0 clamp(1rem, 4vw, 4rem) 0 clamp(1rem, 3vw, 3rem);
}
.scene-pane { min-width: 0; padding: 1.5rem 0; }
.scene-copy { position: relative; margin-bottom: .6rem; }
.scene-copy .eyebrow {
    display: inline-flex; align-items: center; gap: .65rem;
    color: #e8c872; font-size: .72rem; font-weight: 700; letter-spacing: .32em;
    animation: wordIn .7s .1s cubic-bezier(.22,.68,0,1.2) both;
}
.scene-copy .eyebrow::before {          /* titik hidup berdenyut */
    content: ''; width: 8px; height: 8px; border-radius: 50%;
    background: #4ade80; box-shadow: 0 0 0 0 rgba(74,222,128,.6);
    animation: livePulse 2.2s ease-out infinite;
}
@keyframes livePulse { 0% { box-shadow: 0 0 0 0 rgba(74,222,128,.55); } 100% { box-shadow: 0 0 0 11px rgba(74,222,128,0); } }
.scene-copy h1 {
    margin: .55rem 0 .5rem;
    font-size: clamp(2rem, 4.1vw, 3.45rem);
    font-weight: 800; letter-spacing: -.03em; line-height: 1.02;
}
.scene-copy h1 .w {                     /* kata muncul bergantian: naik + blur -> tajam */
    display: inline-block;
    background: linear-gradient(100deg, #f8fafc 0%, #bfdbfe 48%, #f5d98a 100%);
    background-size: 220% 100%;
    -webkit-background-clip: text; background-clip: text;
    -webkit-text-fill-color: transparent; color: transparent;
    animation: wordIn .85s cubic-bezier(.22,.68,0,1.2) both, sheen 7s ease-in-out 1.6s infinite;
}
.scene-copy h1 .w:nth-child(1) { animation-delay: .22s, 1.6s; }
.scene-copy h1 .w:nth-child(2) { animation-delay: .36s, 1.7s; }
.scene-copy h1 .w:nth-child(3) { animation-delay: .5s, 1.8s; }
.scene-copy h1 .w:nth-child(4) { animation-delay: .64s, 1.9s; }
@keyframes wordIn { from { opacity: 0; transform: translateY(22px); filter: blur(10px); } to { opacity: 1; transform: translateY(0); filter: blur(0); } }
@keyframes sheen { 0%, 100% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } }
.scene-copy .rule {                     /* garis emas tergambar */
    display: block; height: 3px; width: 92px; border-radius: 3px; margin: .15rem 0 .9rem;
    background: linear-gradient(90deg, #e8c872, rgba(232,200,114,0));
    transform-origin: left; animation: ruleIn .9s .85s cubic-bezier(.22,.68,0,1.2) both;
}
@keyframes ruleIn { from { transform: scaleX(0); opacity: 0; } to { transform: scaleX(1); opacity: 1; } }
.scene-copy p { color: #a5b4cf; font-size: 1rem; line-height: 1.6; max-width: 34rem; margin: 0 0 1rem; animation: wordIn .8s .95s cubic-bezier(.22,.68,0,1.2) both; }
.chips { display: flex; flex-wrap: wrap; gap: .5rem; }
.chips span {
    display: inline-flex; align-items: center; gap: .45rem;
    padding: .38rem .8rem; border-radius: 999px; font-size: .78rem; font-weight: 600; color: #dbe7ff;
    background: rgba(255,255,255,.07); border: 1px solid rgba(255,255,255,.14);
    -webkit-backdrop-filter: blur(10px); backdrop-filter: blur(10px);
    animation: wordIn .7s cubic-bezier(.22,.68,0,1.2) both, chipFloat 5s ease-in-out infinite;
}
.chips span i { color: #93c5fd; }
.chips span:nth-child(1) { animation-delay: 1.15s, 2s; }
.chips span:nth-child(2) { animation-delay: 1.25s, 2.7s; }
.chips span:nth-child(3) { animation-delay: 1.35s, 3.4s; }
.chips span:nth-child(4) { animation-delay: 1.45s, 4.1s; }
.chips span:nth-child(5) { animation-delay: 1.55s, 4.8s; }
@keyframes chipFloat { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-4px); } }
@media (prefers-reduced-motion: reduce) {
    .scene-copy *, .scene-copy *::before, .chips span { animation: none !important; }
}
@keyframes copyIn { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: translateY(0); } }
.scene-frame {
    position: relative;
    width: 100%;
    max-width: 940px;
    margin: 0 auto;
    aspect-ratio: 1468 / 940;
    animation: copyIn .8s .25s cubic-bezier(.22,.68,0,1.2) both;
}
.scene-frame #mic-scene { position: absolute; inset: 0; width: 100%; height: 100%; display: block; overflow: visible; }
.split .login-wrap { max-width: 480px; width: 100%; margin: 0 auto; padding: 0; }
.logo-wrap img { height: 130px !important; }

@media (max-width: 991.98px) {
    body { align-items: flex-start; }
    .split { grid-template-columns: minmax(0, 1fr); align-items: start; gap: 0; padding: 0 1rem 2rem; }
    .scene-pane { padding: 1.25rem 0 .5rem; }
    .scene-copy { text-align: center; }
    .scene-copy p { display: none; }
    .scene-copy .rule { margin-left: auto; margin-right: auto; transform-origin: center; }
    .chips { justify-content: center; }
    .scene-copy h1 { font-size: 1.9rem; }
    .scene-frame { max-width: 520px; }
    .split .login-wrap { max-width: 440px; }
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
.login-wrap { position: relative; z-index: 1; }
.login-card {
    position: relative;
    overflow: hidden;
    border: 1px solid rgba(255,255,255,.12);
    border-radius: 1.6rem;
    background: linear-gradient(150deg, rgba(30,41,75,.72), rgba(15,23,42,.8));
    -webkit-backdrop-filter: blur(26px) saturate(150%);
    backdrop-filter: blur(26px) saturate(150%);
    box-shadow: 0 30px 90px rgba(2,6,23,.6), inset 0 1px 0 rgba(255,255,255,.14);
    animation: cardIn .7s cubic-bezier(.22,.68,0,1.2) both;
}
.login-card::before {            /* kilau lembut di sudut kartu */
    content: '';
    position: absolute; inset: 0;
    pointer-events: none;
    border-radius: inherit;
    background:
        radial-gradient(120% 60% at 0% 0%, rgba(96,165,250,.22), transparent 55%),
        radial-gradient(90% 50% at 100% 100%, rgba(129,140,248,.16), transparent 60%);
}
.login-card > * { position: relative; }
@keyframes cardIn {
    from { opacity: 0; transform: translateY(28px) scale(.97); }
    to   { opacity: 1; transform: translateY(0)    scale(1); }
}
@keyframes rise { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

/* ── Kepala kartu ── */
.logo-wrap img {
    height: auto !important;
    width: min(300px, 86%);
    animation: logoFloat 5s ease-in-out infinite;
    filter: drop-shadow(0 6px 18px rgba(96,165,250,.35));
}
@keyframes logoFloat { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-6px); } }
.card-head h2 { color: #f8fafc; font-size: 1.55rem; font-weight: 800; letter-spacing: -.02em; margin: .85rem 0 .25rem; animation: rise .6s .15s ease both; }
.card-head p  { color: #94a3b8; font-size: .9rem; margin: 0; animation: rise .6s .22s ease both; }

/* ── Form ── */
.form-label { color: #cbd5e1; font-size: .82rem; letter-spacing: .01em; margin-bottom: .4rem; }
.login-card form > .mb-3 { animation: rise .6s .3s ease both; }
.login-card form > .mb-4 { animation: rise .6s .38s ease both; }
.input-group {
    flex-wrap: nowrap;
    overflow: hidden;
    border: 1px solid rgba(255,255,255,.13);
    border-radius: 14px;
    background: rgba(255,255,255,.05);
    transition: border-color .25s, box-shadow .25s, background .25s;
}
.input-group:focus-within {
    background: rgba(255,255,255,.08);
    border-color: #60a5fa;
    box-shadow: 0 0 0 4px rgba(96,165,250,.18);
}
.input-group .input-group-text,
.input-group .form-control {
    background: transparent !important;
    border: 0 !important;
    border-radius: 0 !important;
    box-shadow: none !important;
}
.input-group-text { color: #7c8aa5; padding-left: 1rem; padding-right: .35rem; transition: color .25s; }
.input-group:focus-within .input-group-text { color: #93c5fd; }
.form-control { color: #f1f5f9; padding: .85rem .6rem; font-size: .95rem; }
.form-control::placeholder { color: #64748b; }
/* Bootstrap menimpa warna teks saat fokus dengan warna gelap bawaan; paksa tetap terang */
.input-group .form-control,
.input-group .form-control:focus,
.input-group .form-control:active,
.input-group .form-control:focus-visible { color: #f1f5f9 !important; caret-color: #fff; }
#togglePassword { padding-right: 1rem; padding-left: .6rem; }
#togglePassword:hover { color: #e2e8f0; }
/* isian terisi otomatis oleh peramban: tetap gelap, bukan putih kebiruan */
.form-control:-webkit-autofill,
.form-control:-webkit-autofill:hover,
.form-control:-webkit-autofill:focus {
    -webkit-text-fill-color: #f1f5f9 !important;
    caret-color: #fff;
    -webkit-box-shadow: 0 0 0 1000px #1a2543 inset !important;
    transition: background-color 99999s ease 0s;
}

/* ── Button ── */
.btn-login {
    position: relative;
    overflow: hidden;
    height: 3.1rem;
    border: 0;
    border-radius: 14px;
    font-size: 1rem;
    letter-spacing: .02em;
    background: linear-gradient(110deg, #2563eb, #4f46e5, #7c3aed, #4f46e5, #2563eb);
    background-size: 240% 100%;
    animation: rise .6s .46s ease both, sweep 9s linear infinite;
    box-shadow: 0 14px 30px rgba(79,70,229,.38), inset 0 1px 0 rgba(255,255,255,.25);
    transition: transform .15s, box-shadow .2s, filter .2s;
}
@keyframes sweep { 0% { background-position: 0% 50%; } 100% { background-position: 240% 50%; } }
.btn-login:hover { transform: translateY(-2px); filter: brightness(1.08); box-shadow: 0 18px 36px rgba(79,70,229,.48), inset 0 1px 0 rgba(255,255,255,.3); }
.btn-login:active { transform: translateY(0); }
.btn-login i { display: inline-block; transition: transform .25s; }
.btn-login:hover i { transform: translateX(4px); }
.btn-login::after {
    content: '';
    position: absolute; top: 0; bottom: 0; left: 0; width: 34%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,.28), transparent);
    transform: translateX(-130%) skewX(-18deg);
    animation: shine 5.5s ease-in-out infinite;
}
@keyframes shine { 0%, 62% { transform: translateX(-130%) skewX(-18deg); } 100% { transform: translateX(330%) skewX(-18deg); } }
.forgot { color: #94a3b8; text-decoration: none; transition: color .2s; }
.forgot:hover { color: #e2e8f0; }

@media (prefers-reduced-motion: reduce) {
    .login-card, .login-card *, .btn-login, .btn-login::after, .logo-wrap img { animation: none !important; transition: none !important; }
}

/* ── Alerts ── */
.alert { font-size: .85rem; border-radius: .6rem; }
.alert-danger  { background: rgba(239,68,68,.12);  border-color: rgba(239,68,68,.25);  color: #fca5a5; }
.alert-success { background: rgba(34,197,94,.12);  border-color: rgba(34,197,94,.25);  color: #86efac; }

/* ── Footer text ── */
.login-footer { color: #64748b; font-size: .72rem; text-align: center; margin-top: 1.5rem; }
</style>
</head>
<body>

<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>

<div class="split">
<section class="scene-pane" aria-label="Balikpapan Superblock district">
    <div class="scene-copy">
        <span class="eyebrow">MALL INTELLIGENCE CENTER</span>
        <h1><span class="w">Every</span> <span class="w">number.</span> <span class="w">One</span> <span class="w">view.</span></h1>
        <i class="rule"></i>
        <p>Footfall, leasing, events, loyalty, and budgets &mdash; all your mall data in one live command center.</p>
        <div class="chips">
            <span><i class="bi bi-person-walking"></i> Traffic</span>
            <span><i class="bi bi-calendar-event"></i> Events</span>
            <span><i class="bi bi-star"></i> Loyalty</span>
            <span><i class="bi bi-wallet2"></i> Budget</span>
            <span><i class="bi bi-graph-up-arrow"></i> Insights</span>
        </div>
    </div>
    <div class="scene-frame"><svg id="mic-scene" data-building="<?= base_url('img/mic-building.png') ?>" aria-hidden="true"></svg></div>
</section>
<div class="container login-wrap">
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="card login-card p-4 p-md-5">

                <div class="card-head text-center mb-4">
                    <div class="logo-wrap"><img src="<?= base_url('img/mic-wordmark.png') ?>" alt="Mall Intelligence Center" style="object-fit:contain"></div>
                    <h2>Welcome back</h2>
                    <p>Sign in to open the MIC dashboard</p>
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
                            <input type="email" name="email" class="form-control" value="<?= old('email') ?>" placeholder="Your email" required autofocus>
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
                        <i class="bi bi-box-arrow-in-right me-1"></i> Sign in
                    </button>
                    <div class="text-center mt-3">
                        <a href="<?= base_url('forgot-password') ?>" class="small forgot">Forgot password?</a>
                    </div>
                </form>
            </div>
            <div class="login-footer">Mall Intelligence Center &mdash; eWalk &amp; Pentacity</div>
        </div>
    </div>
</div>
</div><!-- /.split -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= base_url('js/mic-scene.js') ?>?v=<?= @filemtime(FCPATH . 'js/mic-scene.js') ?>"></script>
<script>
document.querySelector('form').addEventListener('submit', function () {
    const btn = this.querySelector('[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Signing in...';
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
<script>
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
      navigator.serviceWorker.register('<?= base_url('sw.js') ?>')
        .catch(err => console.warn('SW register gagal:', err));
    });
  }
</script>
<script>
/* Tandai aktivitas saat halaman login dibuka/dikirim, supaya tab MIC lama yang tertidur
   tidak mengeluarkan sesi baru lewat auto-logout (lihat layouts/main.php). */
(function () {
    function tandai() { try { localStorage.setItem('mic_activity', String(Date.now())); } catch (e) {} }
    tandai();
    document.addEventListener('submit', tandai, true);
})();
</script>
</body>
</html>
