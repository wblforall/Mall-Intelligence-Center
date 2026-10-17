<?php
/**
 * Form buat & ubah event (dipakai events/create dan events/edit).
 * Vars: $action, $backUrl, $cancelUrl, $submitLabel, $v (name,tema,mall,start_date,end_date,jenis,pos_id,content),
 *       $locations, $savedLocs (array id), $posPilihan, $isEdit
 */
$errs = (array) (session()->getFlashdata('errors') ?? []);
$inv  = fn(string $f) => isset($errs[$f]) ? ' is-invalid' : '';
$mallOpts = ['ewalk' => 'eWalk', 'pentacity' => 'Pentacity', 'keduanya' => 'eWalk & Pentacity'];
?>
<div class="page-evc">
<div class="ev-form-wrap">

<div class="ev-head">
    <a href="<?= $backUrl ?>" class="ev-iconbtn" aria-label="Kembali" title="Kembali"><i class="bi bi-arrow-left"></i></a>
    <div class="min-w-0">
        <h1 class="ev-title"><?= $isEdit ? 'Ubah Event' : 'Buat Event Baru' ?></h1>
        <div class="ev-subtitle"><?= $isEdit ? esc($v['name']) : 'Isi data dasar, rundown dan modul lain dilengkapi setelah event dibuat' ?></div>
    </div>
</div>

<form method="POST" action="<?= $action ?>" id="evForm" class="ev-form" novalidate>
    <?= csrf_field() ?>

    <div class="alert alert-danger py-2 small" id="evFormErr" hidden role="alert">
        <i class="bi bi-exclamation-triangle-fill me-1"></i>Ada kolom yang belum benar. Periksa kolom yang ditandai merah.
    </div>

    <!-- 1. Identitas -->
    <section class="ev-sec">
        <div class="ev-sec-h"><span class="ev-sec-n">1</span><div><h2 class="ev-sec-t">Identitas Event</h2><div class="ev-sec-s">Nama, tema, dan mal penyelenggara</div></div></div>
        <div class="ev-sec-b">
            <div class="row">
                <div class="col-md-8 mb-3">
                    <label class="form-label" for="f_name">Nama Event <span class="req">*</span></label>
                    <input type="text" name="name" id="f_name" class="form-control<?= $inv('name') ?>" value="<?= esc($v['name'], 'attr') ?>"
                           placeholder="Contoh: Doraemon IP Event, Hello Kitty Pop-up" required minlength="3">
                    <div class="invalid-feedback"><?= esc($errs['name'] ?? 'Nama event wajib diisi (minimal 3 huruf).') ?></div>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label" for="f_mall">Mall <span class="req">*</span></label>
                    <select name="mall" id="f_mall" class="form-select<?= $inv('mall') ?>" required>
                        <?php foreach ($mallOpts as $k => $l): ?>
                        <option value="<?= $k ?>" <?= $v['mall'] === $k ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback"><?= esc($errs['mall'] ?? 'Pilih mall.') ?></div>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label" for="f_tema">Tema Event</label>
                <input type="text" name="tema" id="f_tema" class="form-control" value="<?= esc($v['tema'], 'attr') ?>"
                       placeholder="Contoh: Jepang Klasik, Tropical Wonderland, IP Character, Seasonal">
            </div>
        </div>
    </section>

    <!-- 2. Jenis & anggaran -->
    <section class="ev-sec">
        <div class="ev-sec-h"><span class="ev-sec-n">2</span><div><h2 class="ev-sec-t">Jenis &amp; Pos Anggaran</h2><div class="ev-sec-s">Event bertanggal atau kegiatan rutin bulanan</div></div></div>
        <div class="ev-sec-b">
            <?= view('events/_anggaran', ['posPilihan' => $posPilihan, 'jenis' => $v['jenis'], 'posId' => $v['pos_id']]) ?>
        </div>
    </section>

    <!-- 3. Jadwal -->
    <section class="ev-sec">
        <div class="ev-sec-h"><span class="ev-sec-n">3</span><div><h2 class="ev-sec-t">Jadwal</h2><div class="ev-sec-s">Lama event dihitung otomatis dari dua tanggal</div></div></div>
        <div class="ev-sec-b">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="start_date">Tanggal Mulai <span class="req">*</span></label>
                    <input type="date" name="start_date" id="start_date" class="form-control<?= $inv('start_date') ?>" value="<?= esc($v['start_date'], 'attr') ?>" required>
                    <div class="invalid-feedback"><?= esc($errs['start_date'] ?? 'Tanggal mulai wajib diisi.') ?></div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="end_date">Tanggal Selesai <span class="req">*</span></label>
                    <input type="date" name="end_date" id="end_date" class="form-control<?= $inv('end_date') ?>" value="<?= esc($v['end_date'], 'attr') ?>" required>
                    <div class="invalid-feedback"><?= esc($errs['end_date'] ?? 'Tanggal selesai wajib diisi dan tidak sebelum tanggal mulai.') ?></div>
                </div>
            </div>
            <div class="mb-3"><span class="ev-days" id="days_info" aria-live="polite"></span></div>
        </div>
    </section>

    <!-- 4. Lokasi -->
    <section class="ev-sec" id="lokasi_wrap">
        <div class="ev-sec-h"><span class="ev-sec-n">4</span><div><h2 class="ev-sec-t">Lokasi Event</h2><div class="ev-sec-s">Bisa lebih dari satu; daftar mengikuti mall yang dipilih</div></div></div>
        <div class="ev-sec-b">
            <div id="lokasi_list" class="ev-locs mb-3">
                <?php foreach ($locations as $loc): ?>
                <label class="ev-loc-pick lokasi-item" data-mall="<?= esc($loc['mall'], 'attr') ?>" for="loc_<?= $loc['id'] ?>">
                    <input type="checkbox" name="location_ids[]" id="loc_<?= $loc['id'] ?>" value="<?= $loc['id'] ?>" <?= in_array($loc['id'], $savedLocs) ? 'checked' : '' ?>>
                    <span><?= esc($loc['nama']) ?></span>
                </label>
                <?php endforeach; ?>
            </div>
            <?php if (empty($locations)): ?>
            <div class="form-text mb-3">Belum ada lokasi. <a href="<?= base_url('event-locations') ?>">Tambah di Master Lokasi</a>.</div>
            <?php else: ?>
            <div class="form-text mb-3" id="lokasi_none" hidden>Belum ada lokasi untuk mall ini. <a href="<?= base_url('event-locations') ?>">Tambah di Master Lokasi</a>.</div>
            <?php endif; ?>
        </div>
    </section>

    <?php if (! $isEdit): /* ubah event tidak menyimpan deskripsi (lihat Events::update) */ ?>
    <!-- 5. Deskripsi -->
    <section class="ev-sec">
        <div class="ev-sec-h"><span class="ev-sec-n">5</span><div><h2 class="ev-sec-t">Deskripsi</h2><div class="ev-sec-s">Opsional: konsep dan program event secara umum</div></div></div>
        <div class="ev-sec-b">
            <div class="mb-3">
                <label class="form-label" for="f_content">Deskripsi / Content Event</label>
                <textarea name="content" id="f_content" class="form-control" rows="4"
                          placeholder="Deskripsikan program dan konsep event secara umum..."><?= esc($v['content']) ?></textarea>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <div class="ev-actions">
        <button type="submit" class="ev-btn ev-btn-primary ev-btn-lg"><i class="bi bi-check-lg"></i><?= $submitLabel ?></button>
        <a href="<?= $cancelUrl ?>" class="ev-btn ev-btn-lg">Batal</a>
        <span class="ev-hint"><span class="text-danger">*</span> wajib diisi</span>
    </div>
</form>

</div>
</div>
