<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<?php $_flashErr = session()->getFlashdata('error'); $_flashOk = session()->getFlashdata('success'); ?>
<script>
/* Intro One WBL ERP: tampil saat halaman login dibuka pertama kali di tab ini.
   Tidak tampil setelah login gagal (ada pesan) atau bila perangkat minta "kurangi gerakan". */
(function () {
    var on = false;
    try {
        sessionStorage.removeItem('oneLoginTransit');
        on = <?= ($_flashErr || $_flashOk) ? 'false' : 'true' ?> && !sessionStorage.getItem('oneSplash')
             && !matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (on) sessionStorage.setItem('oneSplash', '1');
    } catch (e) {}
    if (on) document.documentElement.classList.add('splash-on');
    <?php if ($_flashErr || $_flashOk): ?>document.documentElement.classList.add('tanpa-intro');<?php endif; ?>
})();
</script>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — One WBL ERP</title>
<link rel="icon" type="image/png" href="<?= base_url('img/onewbl-mark.png') ?>?v=<?= @filemtime(FCPATH . 'img/onewbl-mark.png') ?>">
<!-- ── PWA ── -->
<link rel="manifest" href="<?= base_url('manifest.webmanifest') ?>">
<meta name="theme-color" content="#071230">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="One WBL">
<link rel="apple-touch-icon" href="<?= base_url('img/apple-touch-icon.png') ?>">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link rel="stylesheet" href="<?= base_url('css/bsb-scene.css') ?>?v=<?= @filemtime(FCPATH . 'css/bsb-scene.css') ?>">
<style>
* { box-sizing: border-box; }

body {
    background: #050b1e;
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
    max-width: 1280px;              /* konten tidak menempel ke tepi layar lebar */
    margin: 0 auto;
    min-height: 100vh;
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(360px, 440px);
    align-items: center;
    gap: clamp(2rem, 6vw, 6rem);
    padding: 0 clamp(1rem, 4vw, 3rem);
}
/* panel kiri: logo, judul, tagline, chip — satu poros tengah */
.scene-pane { min-width: 0; padding: 1.5rem 0; display: flex; flex-direction: column; align-items: center; text-align: center; }
.scene-pane .scene-copy .rule { margin-left: auto; margin-right: auto; transform-origin: center; }
.scene-pane .chips { justify-content: center; }
.scene-copy { position: relative; margin-bottom: .6rem; }
.scene-copy .eyebrow {
    display: inline-flex; align-items: center; gap: .65rem;
    color: #f2c14e; font-size: .72rem; font-weight: 700; letter-spacing: .32em;
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
    background: linear-gradient(100deg, #f8fafc 0%, #9fc8ff 48%, #f2c14e 100%);
    background-size: 220% 100%;
    -webkit-background-clip: text; background-clip: text;
    -webkit-text-fill-color: transparent; color: transparent;
    animation: wordIn .85s cubic-bezier(.22,.68,0,1.2) both, sheen 7s ease-in-out 1.6s infinite;
}
.scene-copy h1 .w:nth-child(1) { animation-delay: .22s, 1.6s; }
.scene-copy h1 .w:nth-child(2) { animation-delay: .36s, 1.7s; }
.scene-copy h1 .w:nth-child(3) { animation-delay: .5s, 1.8s; }
.scene-copy h1 .w:nth-child(4) { animation-delay: .64s, 1.9s; }
.scene-copy h1 .w:nth-child(5) { animation-delay: .78s, 2s; }
.scene-copy h1 .w:nth-child(6) { animation-delay: .92s, 2.1s; }
/* Judul "Rise & Run!" — mengikuti logo Taskforce WBL Rise & Run: miring tebal, "Rise &" biru, "Run!" emas */
.scene-copy h1.rr { font-size: clamp(2.6rem, 6vw, 4.8rem); font-style: italic; font-weight: 900; letter-spacing: -.02em; line-height: 1; }
.scene-copy h1.rr .rr-rise { background-image: linear-gradient(100deg, #9fc8ff, #3d8bff); background-size: 100% 100%; animation: wordIn .85s cubic-bezier(.22,.68,0,1.2) both; padding-right: .08em; }
.scene-copy h1.rr .rr-run  { background-image: linear-gradient(100deg, #f6d47a, #e0a000); background-size: 100% 100%; animation: wordIn .85s .14s cubic-bezier(.22,.68,0,1.2) both;
                             padding-right: .12em; filter: drop-shadow(0 0 18px rgba(224,160,0,.35)); }
.scene-copy p.rr-tag { font-size: .82rem; font-weight: 800; letter-spacing: .14em; text-transform: uppercase; color: #9fb4d8; }
.scene-copy p.rr-tag i { font-style: normal; color: #e0a000; padding: 0 .35rem; }
@media (max-width: 991.98px) { .scene-copy p.rr-tag { display: block; font-size: .66rem; letter-spacing: .08em; } .scene-copy p.rr-tag span { white-space: nowrap; } }
@keyframes wordIn { from { opacity: 0; transform: translateY(22px); filter: blur(10px); } to { opacity: 1; transform: translateY(0); filter: blur(0); } }
@keyframes sheen { 0%, 100% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } }
.scene-copy .rule {                     /* garis emas tergambar */
    display: block; height: 3px; width: 92px; border-radius: 3px; margin: .15rem 0 .9rem;
    background: linear-gradient(90deg, #f2c14e, rgba(242,193,78,0));
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
    animation: wordIn .7s cubic-bezier(.22,.68,0,1.2) both;   /* tanpa melayang: chip tetap sejajar */
}
.chips span i { color: #93c5fd; }
/* slogan grup: Estate, Mall, Developer to Be One */
.chips { align-items: center; }
.chips .chips-one { font-style: italic; font-weight: 900; font-size: .95rem; letter-spacing: -.01em; margin-left: .2rem;
    background-image: linear-gradient(100deg, #f6d47a, #e0a000); -webkit-background-clip: text; background-clip: text;
    -webkit-text-fill-color: transparent; color: transparent; padding-right: .1em;
    animation: wordIn .7s 1.5s cubic-bezier(.22,.68,0,1.2) both; }
.chips span:nth-child(1) { animation-delay: 1.15s; }
.chips span:nth-child(2) { animation-delay: 1.25s; }
.chips span:nth-child(3) { animation-delay: 1.35s; }
.chips span:nth-child(4) { animation-delay: 1.45s; }
.chips span:nth-child(5) { animation-delay: 1.55s; }
@keyframes chipFloat { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-4px); } }
@media (prefers-reduced-motion: reduce) {
    .scene-copy *, .scene-copy *::before, .chips span { animation: none !important; }
}
@keyframes copyIn { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: translateY(0); } }
/* ── Logo WBL Rise & Run besar — versi reverse (navy → putih) untuk latar gelap, tanpa pelat ── */
.brand-frame {
    position: relative;
    width: 100%;
    max-width: 560px;
    margin: 0 auto 1.75rem;
    animation: copyIn .8s .25s cubic-bezier(.22,.68,0,1.2) both;
}
.brand-frame::before {   /* pendar lembut di belakang logo */
    content: ''; position: absolute; inset: 8% 4%; z-index: -1; border-radius: 50%;
    background: radial-gradient(closest-side, rgba(61,139,255,.28), transparent 70%), radial-gradient(closest-side at 60% 75%, rgba(224,160,0,.18), transparent 70%);
    filter: blur(28px);
}
.brand-frame img { filter: drop-shadow(0 0 18px rgba(120,170,255,.25)); }
.brand-frame img {
    display: block; width: 100%; height: auto;
}
@media (prefers-reduced-motion: reduce) { .brand-frame, .brand-frame img { animation: none !important; } }
.split .login-wrap { max-width: 480px; width: 100%; margin: 0 auto; padding: 0; }

@media (max-width: 991.98px) {
    body { align-items: flex-start; }
    .split { grid-template-columns: minmax(0, 1fr); align-items: start; gap: 0; padding: 0 1rem 2rem; }
    .scene-pane { padding: 1.25rem 0 .5rem; }
    .scene-copy { text-align: center; }
    .scene-copy p { display: none; }
    .scene-copy .rule { margin-left: auto; margin-right: auto; transform-origin: center; }
    .chips { justify-content: center; }
    .scene-copy h1 { font-size: 1.9rem; }
    .brand-frame { max-width: 300px; margin: .5rem auto .85rem; }
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
    background: radial-gradient(circle, rgba(43,111,214,.38) 0%, transparent 70%);
    top: -160px; left: -160px;
    animation: drift1 14s ease-in-out infinite;
}
.orb-2 {
    width: 420px; height: 420px;
    background: radial-gradient(circle, rgba(37,99,201,.3) 0%, transparent 70%);
    bottom: -120px; right: -100px;
    animation: drift2 18s ease-in-out infinite;
}
.orb-3 {
    width: 280px; height: 280px;
    background: radial-gradient(circle, rgba(242,193,78,.14) 0%, transparent 70%);
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
    background: linear-gradient(150deg, rgba(16,36,84,.72), rgba(6,14,36,.84));
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
        radial-gradient(90% 50% at 100% 100%, rgba(242,193,78,.10), transparent 60%);
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
    width: 210px; max-width: 70%;
    filter: drop-shadow(0 0 14px rgba(80,150,255,.35));
}
@keyframes logoFloat { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-6px); } }
.card-head p.sapa { color: #f2c14e; font-size: .9rem; font-weight: 600; margin: 1rem 0 .1rem; }
.card-head h2 { color: #f8fafc; font-size: 1.55rem; font-weight: 800; letter-spacing: -.02em; margin: 0; animation: rise .6s .15s ease both; }
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
    color: #0a1534 !important;
    background: linear-gradient(110deg, #e2a92b, #f2c14e, #f6d47a, #f2c14e, #e2a92b);
    background-size: 240% 100%;
    animation: rise .6s .46s ease both, sweep 9s linear infinite;
    box-shadow: 0 14px 30px rgba(226,169,43,.30), inset 0 1px 0 rgba(255,255,255,.35);
    transition: transform .15s, box-shadow .2s, filter .2s;
}
@keyframes sweep { 0% { background-position: 0% 50%; } 100% { background-position: 240% 50%; } }
.btn-login:hover { transform: translateY(-2px); filter: brightness(1.05); box-shadow: 0 18px 36px rgba(226,169,43,.40), inset 0 1px 0 rgba(255,255,255,.4); }
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

/* ═══ Latar: lantai grid berperspektif (motif atap WBL) + berkas cahaya + partikel emas ═══ */
.bg-wbl { position: fixed; inset: 0; z-index: 0; pointer-events: none; overflow: hidden; }
.bg-rays {                                   /* berkas cahaya memancar dari puncak, seperti atap WBL */
    position: absolute; left: 50%; top: calc(-80vmax - 12vh); width: 160vmax; height: 160vmax; transform: translateX(-50%);
    background: repeating-conic-gradient(from 0deg at 50% 50%, rgba(90,160,255,.13) 0deg .8deg, transparent .8deg 7.5deg);
    -webkit-mask: radial-gradient(circle at 50% 50%, #000 0%, rgba(0,0,0,.6) 34%, transparent 50%);
            mask: radial-gradient(circle at 50% 50%, #000 0%, rgba(0,0,0,.6) 34%, transparent 50%);
    animation: raysSway 26s ease-in-out infinite alternate;
}
@keyframes raysSway { from { transform: translateX(-50%) rotate(-4deg); } to { transform: translateX(-50%) rotate(4deg); } }
.bg-floor { position: absolute; left: -50%; right: -50%; bottom: -6vh; height: 40vh; perspective: 420px; perspective-origin: 50% 0%; }
.bg-floor::before {
    content: ''; position: absolute; inset: 0;
    background:
        linear-gradient(90deg, rgba(61,139,255,.30) 1px, transparent 1px) 0 0 / 64px 64px,
        linear-gradient(0deg,  rgba(61,139,255,.30) 1px, transparent 1px) 0 0 / 64px 64px;
    transform: rotateX(68deg); transform-origin: 50% 0%;
    -webkit-mask: linear-gradient(180deg, transparent 0%, #000 35%, #000 70%, transparent 100%);
            mask: linear-gradient(180deg, transparent 0%, #000 35%, #000 70%, transparent 100%);
    animation: floorRun 3.2s linear infinite;
}
@keyframes floorRun { to { background-position: 0 64px, 0 64px; } }
.bg-horizon {                               /* garis cakrawala emas */
    position: absolute; left: 0; right: 0; bottom: 34vh; height: 1px; z-index: 1;
    background: linear-gradient(90deg, transparent, rgba(242,193,78,.55) 30%, rgba(61,139,255,.6) 50%, rgba(242,193,78,.55) 70%, transparent);
    box-shadow: 0 0 24px 4px rgba(61,139,255,.25);
}
.bg-dust i {                                /* partikel emas naik pelan */
    position: absolute; bottom: -10px; width: 3px; height: 3px; border-radius: 50%;
    background: #f2c14e; box-shadow: 0 0 8px 2px rgba(242,193,78,.6); opacity: 0;
    animation: dustUp linear infinite;
}
@keyframes dustUp { 0% { transform: translateY(0); opacity: 0; } 12% { opacity: .9; } 80% { opacity: .5; } 100% { transform: translateY(-80vh); opacity: 0; } }
@media (prefers-reduced-motion: reduce) { .bg-rays, .bg-floor::before, .bg-dust i { animation: none !important; } .bg-dust { display: none; } }

/* ═══ Logo WBL Rise & Run beranimasi: 4 lapis dari satu gambar (dipotong clip-path) ═══ */
.rr-anim { isolation: isolate; }
.rr-anim .rr-base { display: block; width: 100%; height: auto; visibility: hidden; }
.rr-anim .rr-l { position: absolute; inset: 0; }
.rr-anim .rr-l img { position: absolute; inset: 0; width: 100%; height: 100%; display: block; filter: drop-shadow(0 0 18px rgba(120,170,255,.22)); }
.rr-l1 img { clip-path: inset(0 0 73% 0); }                                   /* atap */
.rr-l2 img { clip-path: inset(27% 0 41.5% 0); }                             /* WBL + bingkai */
.rr-l3 img { clip-path: polygon(0 58.5%, 91.2% 58.5%, 91.2% 83.5%, 0 83.5%); }  /* RISE & RUN! */
.rr-l4 img { clip-path: polygon(0 83.5%, 91.2% 83.5%, 91.2% 58.5%, 100% 58.5%, 100% 100%, 0 100%); } /* panah + tagline */
.rr-l1 { animation: rrRoof 1s .15s cubic-bezier(.22,1,.36,1) both; }
.rr-l2 { animation: rrPop .9s .55s cubic-bezier(.34,1.56,.64,1) both; transform-origin: 50% 45%; }
.rr-l3 { animation: rrDash .8s 1.0s cubic-bezier(.22,1,.36,1) both; }
.rr-l4 { animation: rrWipe 1.1s 1.35s cubic-bezier(.65,0,.35,1) both; }
@keyframes rrRoof { from { opacity: 0; transform: translateY(-34px); filter: blur(6px); } to { opacity: 1; transform: none; filter: none; } }
@keyframes rrPop  { from { opacity: 0; transform: scale(.6); } to { opacity: 1; transform: none; } }
@keyframes rrDash { from { opacity: 0; transform: translateX(-60px) skewX(-12deg); filter: blur(8px); } to { opacity: 1; transform: none; filter: none; } }
@keyframes rrWipe { from { clip-path: inset(0 100% 0 0); } to { clip-path: inset(0 0 0 0); } }
/* kilau menyapu bentuk logo (mask = logo itu sendiri), berulang */
.rr-shine {
    position: absolute; inset: 0; pointer-events: none;
    -webkit-mask: var(--rr-mask) center / 100% 100% no-repeat; mask: var(--rr-mask) center / 100% 100% no-repeat;
    background: linear-gradient(105deg, transparent 42%, rgba(255,255,255,.75) 50%, transparent 58%) no-repeat;
    background-size: 260% 100%; background-position: 160% 0;
    animation: rrShine 7s 2.6s ease-in-out infinite;
}
@keyframes rrShine { 0% { background-position: 160% 0; } 28%, 100% { background-position: -60% 0; } }
/* pendar emas berdenyut pelan setelah logo lengkap */
.rr-anim::before { animation: rrGlow 5s 2.4s ease-in-out infinite; }
@keyframes rrGlow { 0%, 100% { opacity: .75; transform: scale(1); } 50% { opacity: 1; transform: scale(1.06); } }

/* ═══ Intro saat URL dibuka ═══ */
.splash { display: none; }
html.splash-on .splash {
    display: grid; place-items: center; position: fixed; inset: 0; z-index: 9999;
    background: radial-gradient(60% 50% at 50% 45%, #0d265c 0%, #071230 55%, #030817 100%);
    animation: splashOut .6s 1.45s cubic-bezier(.65,0,.35,1) forwards;
}
.splash-in { display: grid; justify-items: center; gap: 1.4rem; }
.splash-logo { width: min(420px, 72vw); height: auto; opacity: 0; filter: drop-shadow(0 0 30px rgba(61,139,255,.45));
    animation: splashLogo 1.3s .1s cubic-bezier(.22,1,.36,1) forwards; }
.splash-bar { width: min(220px, 50vw); height: 3px; border-radius: 3px; background: rgba(255,255,255,.1); overflow: hidden; }
.splash-bar i { display: block; height: 100%; width: 100%; transform-origin: left; transform: scaleX(0);
    background: linear-gradient(90deg, #3d8bff, #f2c14e); animation: splashBar 1.5s .3s cubic-bezier(.65,0,.35,1) forwards; }
@keyframes splashLogo { 0% { opacity: 0; transform: scale(.82); filter: blur(12px) drop-shadow(0 0 0 transparent); } 60% { opacity: 1; filter: blur(0) drop-shadow(0 0 40px rgba(61,139,255,.6)); } 100% { opacity: 1; transform: scale(1); filter: drop-shadow(0 0 30px rgba(61,139,255,.45)); } }
@keyframes splashBar  { to { transform: scaleX(1); } }
@keyframes splashOut  { to { opacity: 0; visibility: hidden; transform: scale(1.06); } }
/* selama intro, animasi masuk halaman ditahan, lalu jalan saat intro selesai */
html.splash-on .split, html.splash-on .split * { animation-play-state: paused !important; }

/* kembali setelah login gagal/berhasil reset: tampilkan panel kiri langsung, tanpa memutar ulang animasi masuk */
html.tanpa-intro .scene-pane, html.tanpa-intro .scene-pane *:not(.rr-shine) { animation-duration: 0s !important; animation-delay: 0s !important; }
html.tanpa-intro .rr-shine { animation-delay: 1s !important; }

/* ═══ Loading saat Login diklik ═══ */
.btn-login.is-loading { pointer-events: none; }
.btn-login .ld { display: none; align-items: center; justify-content: center; gap: .55rem; }
.btn-login.is-loading .ld { display: inline-flex; }
.btn-login.is-loading .lb { display: none; }
.ld-dots { display: inline-flex; gap: 4px; }
.ld-dots i { width: 6px; height: 6px; border-radius: 50%; background: currentColor; animation: ldDot .9s ease-in-out infinite; }
.ld-dots i:nth-child(2) { animation-delay: .15s; } .ld-dots i:nth-child(3) { animation-delay: .3s; }
@keyframes ldDot { 0%, 80%, 100% { transform: translateY(0); opacity: .35; } 40% { transform: translateY(-5px); opacity: 1; } }
.login-card.is-busy::after {     /* garis cahaya berjalan di tepi atas kartu */
    content: ''; position: absolute; left: 0; right: 0; top: 0; height: 3px; border-radius: 3px;
    background: linear-gradient(90deg, transparent, #3d8bff, #f2c14e, transparent); background-size: 50% 100%; background-repeat: no-repeat;
    animation: busyRun 1.1s linear infinite;
}
@keyframes busyRun { from { background-position: -50% 0; } to { background-position: 150% 0; } }
.login-card.is-busy .form-control { opacity: .6; transition: opacity .3s; }

/* ═══ Login gagal: kartu bergetar, pesan error masuk ═══ */
.login-card.is-gagal { animation: cardIn .6s cubic-bezier(.22,.68,0,1.2) both, shake .5s .55s cubic-bezier(.36,.07,.19,.97) both; }
.login-card.is-gagal .alert-danger { animation: alertIn .45s .5s cubic-bezier(.22,1,.36,1) both; border-left: 3px solid #f87171; }
.login-card.is-gagal .form-control[name="password"] { border-color: rgba(248,113,113,.55); }
@keyframes shake { 10%, 90% { transform: translateX(-2px); } 20%, 80% { transform: translateX(4px); } 30%, 50%, 70% { transform: translateX(-8px); } 40%, 60% { transform: translateX(8px); } }
@keyframes alertIn { from { opacity: 0; transform: translateY(-8px) scale(.98); } to { opacity: 1; transform: none; } }

/* ═══ Keluar dari halaman login setelah klik (transisi ke Dashboard) ═══ */
body.is-pergi .scene-pane { transition: opacity .5s ease, transform .6s cubic-bezier(.65,0,.35,1); opacity: .35; transform: scale(.97); }

@media (prefers-reduced-motion: reduce) {
    .rr-l, .rr-shine, .rr-anim::before, .login-card.is-gagal, .login-card.is-gagal .alert-danger { animation: none !important; }
}
</style>
</head>
<body>
<div class="bg-wbl" aria-hidden="true">
    <div class="bg-rays"></div>
    <div class="bg-horizon"></div>
    <div class="bg-floor"></div>
    <div class="bg-dust">
        <?php foreach ([[6,14,0],[14,18,4],[23,12,8],[31,16,2],[42,20,10],[55,15,6],[63,13,1],[71,19,9],[79,14,3],[88,17,7],[95,12,5],[48,22,12]] as [$x,$d,$w]): ?>
        <i style="left:<?= $x ?>%;animation-duration:<?= $d ?>s;animation-delay:<?= $w ?>s"></i>
        <?php endforeach; ?>
    </div>
</div>
<div class="splash" aria-hidden="true">
    <div class="splash-in">
        <img class="splash-logo" src="<?= base_url('img/onewbl-logo-lg.webp') ?>" alt="">
        <div class="splash-bar"><i></i></div>
    </div>
</div>

<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>

<div class="split">
<section class="scene-pane" aria-label="One WBL ERP">
    <?php $_rr = base_url('img/wbl-rise-run-gelap.webp') . '?v=' . @filemtime(FCPATH . 'img/wbl-rise-run-gelap.webp'); ?>
    <div class="brand-frame rr-anim" role="img" aria-label="WBL Rise &amp; Run! — Elevate People, Drive Performance, Build the Future" style="--rr-mask:url('<?= $_rr ?>')">
        <img class="rr-base" src="<?= $_rr ?>" alt="" width="1508" height="800">
        <div class="rr-l rr-l1"><img src="<?= $_rr ?>" alt=""></div>
        <div class="rr-l rr-l2"><img src="<?= $_rr ?>" alt=""></div>
        <div class="rr-l rr-l3"><img src="<?= $_rr ?>" alt=""></div>
        <div class="rr-l rr-l4"><img src="<?= $_rr ?>" alt=""></div>
        <div class="rr-shine"></div>
    </div>
    <div class="scene-copy">
        <div class="chips">
            <span><i class="bi bi-tree"></i> Estate</span>
            <span><i class="bi bi-shop"></i> Mall</span>
            <span><i class="bi bi-buildings"></i> Developer</span>
            <b class="chips-one">to Be One</b>
        </div>
    </div>
</section>
<div class="container login-wrap">
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="card login-card p-4 p-md-5<?= $_flashErr ? ' is-gagal' : '' ?>">

                <div class="card-head text-center mb-4">
                    <div class="logo-wrap"><picture><source srcset="<?= base_url('img/onewbl-logo-lg.webp') ?>" type="image/webp"><img src="<?= base_url('img/onewbl-logo-lg.png') ?>" alt="One WBL ERP" width="1465" height="667"></picture></div>
                    <?php $_j = (int) date('G'); $_sapa = $_j < 11 ? 'Good morning' : ($_j < 15 ? 'Good afternoon' : 'Good evening'); ?>
                    <p class="sapa"><?= $_sapa ?></p>
                    <h2>Login</h2>
                </div>

                <?php if ($_flashOk): ?>
                <div class="alert alert-success"><?= esc($_flashOk) ?></div>
                <?php endif; ?>
                <?php if ($_flashErr): ?>
                <div class="alert alert-danger"><i class="bi bi-exclamation-octagon me-1"></i><?= esc($_flashErr) ?></div>
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
                        <span class="lb"><i class="bi bi-box-arrow-in-right me-1"></i> Login</span>
                        <span class="ld" aria-live="polite"><span class="ld-dots"><i></i><i></i><i></i></span> Logging in</span>
                    </button>
                    <div class="text-center mt-3">
                        <a href="<?= base_url('forgot-password') ?>" class="small forgot">Forgot password?</a>
                    </div>
                </form>
            </div>
            <div class="login-footer">One WBL ERP &mdash; PT Wulandari Bangun Laksana Tbk</div>
        </div>
    </div>
</div>
</div><!-- /.split -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    const form = document.querySelector('form');
    const btn  = form.querySelector('[type="submit"]');
    const card = document.querySelector('.login-card');
    const html = document.documentElement;

    // Intro selesai → lepas penahan animasi halaman
    if (html.classList.contains('splash-on')) {
        setTimeout(() => html.classList.remove("splash-on"), 2000);
        document.querySelector('.splash').addEventListener('click', () => html.classList.remove('splash-on'));
    }

    form.addEventListener('submit', function () {
        btn.classList.add('is-loading');
        card.classList.remove('is-gagal');
        card.classList.add('is-busy');
        document.body.classList.add('is-pergi');
        try { sessionStorage.setItem('oneLoginTransit', '1'); } catch (e) {}
        setTimeout(() => { btn.disabled = true; }, 0);   // nonaktif setelah form terkirim
    });

    // Kembali lewat tombol Back (bfcache): pulihkan tombol
    window.addEventListener('pageshow', function (e) {
        if (!e.persisted) return;
        btn.disabled = false; btn.classList.remove('is-loading');
        card.classList.remove('is-busy'); document.body.classList.remove('is-pergi');
    });
})();

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
