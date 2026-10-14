<?php
/**
 * Jenis event & pos budget bawaan — dipakai form buat & ubah event.
 * $posPilihan: BudgetPosModel::untukEvent(); $jenis, $posId: nilai sekarang.
 * Kegiatan rutin (live band mingguan, street performance, izin keramaian…) dicatat
 * sebagai event berjenis "Rutin": satu per mal per bulan.
 */
$jenis = $jenis ?? 'event';
$posId = (int) ($posId ?? 0);
?>
<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label fw-semibold">Jenis</label>
        <div class="btn-group w-100" role="group" aria-label="Jenis event">
            <input type="radio" class="btn-check" name="jenis" id="jenis_event" value="event" <?= $jenis === 'rutin' ? '' : 'checked' ?>>
            <label class="btn btn-outline-secondary" for="jenis_event"><i class="bi bi-stars me-1"></i>Event</label>
            <input type="radio" class="btn-check" name="jenis" id="jenis_rutin" value="rutin" <?= $jenis === 'rutin' ? 'checked' : '' ?>>
            <label class="btn btn-outline-secondary" for="jenis_rutin"><i class="bi bi-arrow-repeat me-1"></i>Rutin bulanan</label>
        </div>
        <div class="form-text" id="jenis_info">
            <?= $jenis === 'rutin'
                ? 'Kegiatan rutin satu mal satu bulan (live band, street performance, izin keramaian, biaya lain-lain).'
                : 'Event/program bertanggal, mis. Wedding Expo, Christmas is Calling.' ?>
        </div>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label fw-semibold">Pos Budget</label>
        <select name="pos_id" class="form-select">
            <option value="">— Belum ditentukan —</option>
            <?php $grup = null; foreach ($posPilihan as $p): ?>
            <?php if (count(array_unique(array_column($posPilihan, 'dept_id'))) > 1 && $grup !== $p['dept_nama']): ?>
                <?= $grup !== null ? '</optgroup>' : '' ?><optgroup label="<?= esc($p['dept_nama'], 'attr') ?>"><?php $grup = $p['dept_nama']; ?>
            <?php endif; ?>
            <option value="<?= $p['id'] ?>" <?= (int) $p['id'] === $posId ? 'selected' : '' ?>><?= esc($p['nama']) ?></option>
            <?php endforeach; ?>
            <?= $grup !== null ? '</optgroup>' : '' ?>
        </select>
        <div class="form-text">Pos bawaan semua biaya Content event ini; bisa diganti per item.</div>
    </div>
</div>
<div class="alert alert-info small py-2 mb-3 <?= $jenis === 'rutin' ? '' : 'd-none' ?>" id="rutin_bantu">
    <i class="bi bi-calendar-month me-1"></i>
    Bulan:
    <input type="month" id="rutin_bulan" class="form-control form-control-sm d-inline-block mx-1" style="width:170px">
    <span class="text-muted">— tanggal mulai & selesai diisi sebulan penuh.</span>
</div>
<script>
(function () {
    const info = document.getElementById('jenis_info'), bantu = document.getElementById('rutin_bantu');
    const teks = {
        event: 'Event/program bertanggal, mis. Wedding Expo, Christmas is Calling.',
        rutin: 'Kegiatan rutin satu mal satu bulan (live band, street performance, izin keramaian, biaya lain-lain).'
    };
    document.querySelectorAll('input[name="jenis"]').forEach(r => r.addEventListener('change', () => {
        info.textContent = teks[r.value];
        bantu.classList.toggle('d-none', r.value !== 'rutin');
    }));
    document.getElementById('rutin_bulan').addEventListener('change', function () {
        if (!this.value) return;
        const [y, m] = this.value.split('-').map(Number);
        const akhir = new Date(y, m, 0).getDate();
        const s = document.getElementById('start_date'), e = document.getElementById('end_date');
        s.value = this.value + '-01';
        e.value = this.value + '-' + String(akhir).padStart(2, '0');
        s.dispatchEvent(new Event('change')); e.dispatchEvent(new Event('change'));
        const nama = document.querySelector('input[name="name"]');
        const mall = document.querySelector('[name="mall"]');
        const bln = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'][m - 1];
        const lbl = {ewalk: 'eWalk', pentacity: 'Pentacity', keduanya: 'eWalk & Pentacity'}[mall.value] || '';
        if (nama && !nama.value.trim()) nama.value = 'Regular Event ' + lbl + ' — ' + bln + ' ' + y;
    });
})();
</script>
