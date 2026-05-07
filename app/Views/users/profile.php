<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= base_url('/') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h4 class="fw-bold mb-0">Profil Saya</h4>
</div>

<div class="row justify-content-center g-4">
<div class="col-md-5">

<!-- Info + Password -->
<div class="card">
<div class="card-body p-4">
<div class="text-center mb-4">
    <div class="rounded-circle d-inline-flex align-items-center justify-content-center"
         style="width:64px;height:64px;background:var(--c-avatar-bg)">
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
    <div class="mb-4">
        <label class="form-label fw-semibold">Password Baru <span class="text-muted small">(kosongkan jika tidak ganti)</span></label>
        <input type="password" name="password" class="form-control" minlength="6" placeholder="Min. 6 karakter">
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

            <!-- Dark -->
            <div class="col-6">
            <label class="d-block" style="cursor:pointer">
                <input type="radio" name="theme" value="dark" class="d-none theme-radio"
                       <?= ($user['theme'] ?? 'dark') === 'dark' ? 'checked' : '' ?>>
                <div class="theme-card rounded-3 p-3 text-center <?= ($user['theme'] ?? 'dark') === 'dark' ? 'theme-card-active' : '' ?>">
                    <!-- mini preview dark -->
                    <div class="mx-auto mb-2 rounded-2 overflow-hidden position-relative"
                         style="width:88px;height:58px;background:linear-gradient(160deg,#0c1a2e,#091628);border:1px solid rgba(255,255,255,.08)">
                        <div style="position:absolute;inset:0 auto 0 0;width:20px;background:#091528"></div>
                        <div style="position:absolute;left:24px;top:7px;right:5px;height:12px;background:rgba(14,26,42,.92);border-radius:3px;border:1px solid rgba(255,255,255,.07)"></div>
                        <div style="position:absolute;left:24px;top:23px;right:5px;height:10px;background:rgba(14,26,42,.92);border-radius:3px;border:1px solid rgba(255,255,255,.07)"></div>
                        <div style="position:absolute;left:24px;top:37px;right:5px;height:10px;background:rgba(14,26,42,.92);border-radius:3px;border:1px solid rgba(255,255,255,.07)"></div>
                        <div style="position:absolute;left:5px;top:8px;width:10px;height:5px;border-radius:2px;background:rgba(232,65,90,.5)"></div>
                        <div style="position:absolute;left:5px;top:17px;width:10px;height:3px;border-radius:2px;background:rgba(255,255,255,.1)"></div>
                        <div style="position:absolute;left:5px;top:24px;width:10px;height:3px;border-radius:2px;background:rgba(255,255,255,.1)"></div>
                    </div>
                    <div class="small fw-semibold">Dark</div>
                    <div style="font-size:.7rem" class="text-muted">Navy Pro</div>
                </div>
            </label>
            </div>

            <!-- Light -->
            <div class="col-6">
            <label class="d-block" style="cursor:pointer">
                <input type="radio" name="theme" value="light" class="d-none theme-radio"
                       <?= ($user['theme'] ?? 'dark') === 'light' ? 'checked' : '' ?>>
                <div class="theme-card rounded-3 p-3 text-center <?= ($user['theme'] ?? 'dark') === 'light' ? 'theme-card-active' : '' ?>">
                    <!-- mini preview light -->
                    <div class="mx-auto mb-2 rounded-2 overflow-hidden position-relative"
                         style="width:88px;height:58px;background:linear-gradient(135deg,#f0f4ff,#e8eeff);border:1px solid rgba(99,102,241,.18)">
                        <div style="position:absolute;inset:0 auto 0 0;width:20px;background:#fff;border-right:1px solid rgba(99,102,241,.15)"></div>
                        <div style="position:absolute;left:24px;top:7px;right:5px;height:12px;background:#fff;border-radius:3px;box-shadow:0 1px 4px rgba(0,0,0,.08);border:1px solid rgba(99,102,241,.1)"></div>
                        <div style="position:absolute;left:24px;top:23px;right:5px;height:10px;background:#fff;border-radius:3px;box-shadow:0 1px 4px rgba(0,0,0,.08);border:1px solid rgba(99,102,241,.1)"></div>
                        <div style="position:absolute;left:24px;top:37px;right:5px;height:10px;background:#fff;border-radius:3px;box-shadow:0 1px 4px rgba(0,0,0,.08);border:1px solid rgba(99,102,241,.1)"></div>
                        <div style="position:absolute;left:4px;top:8px;width:12px;height:4px;border-radius:2px;background:rgba(99,102,241,.2)"></div>
                        <div style="position:absolute;left:4px;top:16px;width:12px;height:3px;border-radius:2px;background:rgba(99,102,241,.12)"></div>
                        <div style="position:absolute;left:4px;top:23px;width:12px;height:3px;border-radius:2px;background:rgba(99,102,241,.12)"></div>
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

<?= $this->endSection() ?>
<?= $this->section('styles') ?>
<style>
.theme-card {
    border: 2px solid transparent;
    background: var(--c-inner-bg);
    transition: border-color .18s, box-shadow .18s;
}
.theme-card:hover {
    border-color: var(--bs-primary);
    box-shadow: 0 0 0 3px rgba(var(--bs-primary-rgb),.1);
}
.theme-card-active {
    border-color: var(--bs-primary) !important;
    box-shadow: 0 0 0 3px rgba(var(--bs-primary-rgb),.15) !important;
}
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
</script>
<?= $this->endSection() ?>
