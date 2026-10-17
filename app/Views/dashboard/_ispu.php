<?php
/**
 * Widget ISPU (Indeks Standar Pencemar Udara) Balikpapan — Dashboard.
 * Data: endpoint dashboard/ispu (Dashboard → IspuController → App\Libraries\Ispu,
 * sumber resmi KLHK). Dirender oleh public/js/ispu-widget.js.
 * Sisipkan dengan:  <?= view('dashboard/_ispu') ?>
 */
$ispuAdmin = (bool) (session()->get('role_is_admin') || session()->get('user_role') === 'admin');
?>
<link rel="stylesheet" href="<?= base_url('css/ispu-widget.css') ?>?v=<?= @filemtime(FCPATH . 'css/ispu-widget.css') ?>">
<section class="ispu-card fade-up" id="ispuWidget" data-url="<?= esc(base_url('dashboard/ispu'), 'attr') ?>" data-admin="<?= $ispuAdmin ? '1' : '0' ?>" aria-labelledby="ispuTitle">
    <div id="ispuBanner" class="ispu-banner" hidden></div>
    <header class="ispu-head">
        <div class="ispu-title">
            <span class="ispu-ico"><i class="bi bi-wind"></i></span>
            <div>
                <h3 id="ispuTitle">Indeks Standar Pencemar Udara (ISPU)</h3>
                <p>Kualitas udara Balikpapan · data resmi KLHK · BSB berupa estimasi</p>
            </div>
        </div>
        <div class="ispu-tools">
            <span class="ispu-asapchip" id="ispuAsapChip" hidden></span>
            <span class="ispu-live" id="ispuLive" hidden><i></i> <span id="ispuLiveTxt">live</span></span>
            <?php if ($ispuAdmin): ?><button type="button" class="ispu-refresh" id="ispuRefresh" title="Ambil ulang dari sumber"><i class="bi bi-arrow-clockwise"></i></button><?php endif; ?>
        </div>
    </header>

    <div class="ispu-body" id="ispuBody">
        <div class="ispu-skel" aria-hidden="true"><i></i><i></i><i></i></div>
    </div>

    <footer class="ispu-foot">
        <span id="ispuFootInfo">Sumber: ISPU KLHK</span>
        <a href="https://ispu.kemenlh.go.id/app/" target="_blank" rel="noopener noreferrer">ispu.kemenlh.go.id <i class="bi bi-box-arrow-up-right"></i></a>
    </footer>
</section>
<script defer src="<?= base_url('js/ispu-widget.js') ?>?v=<?= @filemtime(FCPATH . 'js/ispu-widget.js') ?>"></script>
