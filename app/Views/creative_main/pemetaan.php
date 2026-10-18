<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/creative-main.css') ?>?v=<?= @filemtime(FCPATH . 'css/creative-main.css') ?: 1 ?>">
<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
use App\Services\CreativeTemaService;

$tipeConfig = [
    'master_design'  => ['label' => 'Design',         'icon' => 'bi-vector-pen'],
    'motion_graphic' => ['label' => 'Motion Graphic', 'icon' => 'bi-film'],
    'digital'        => ['label' => 'Content Digital', 'icon' => 'bi-phone'],
    'cetak'          => ['label' => 'Media Cetak',    'icon' => 'bi-printer'],
    'influencer'     => ['label' => 'Influencer',     'icon' => 'bi-person-video3'],
    'media_prescon'  => ['label' => 'Media Prescon',  'icon' => 'bi-newspaper'],
];
$confChip = ['tinggi' => ['cr-chip-ok', 'Tinggi'], 'sedang' => ['cr-chip-warn', 'Sedang'], 'rendah' => ['cr-chip-mute', 'Rendah']];
$bulanS = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
$fTgl = function ($d) use ($bulanS) { if (!$d || $d === '0000-00-00') return '—'; $t = strtotime($d); return date('j', $t) . ' ' . $bulanS[(int) date('n', $t) - 1] . ' ' . date('Y', $t); };
$itemTgl = fn($it) => $fTgl(($it['tanggal'] ?? '') ?: (($it['tanggal_take'] ?? '') ?: substr((string) $it['created_at'], 0, 10)));
$nGroup = count($groups);
?>
<div class="page-cre" id="crePm">

<div class="cr-head">
    <div class="cr-head-ico"><i class="bi bi-diagram-3"></i></div>
    <div class="min-w-0">
        <div class="cr-crumb"><a href="<?= base_url('creative') ?>">Creative &amp; Design</a><i class="bi bi-chevron-right" style="font-size:.6rem"></i><span>Susun Tema</span></div>
        <h1 class="cr-title">Susun Tema</h1>
        <div class="cr-subtitle"><?= (int) $jumlahBelumBertema ?> item belum punya Tema. Ini hanya <b>usulan</b> — tidak ada yang berubah sampai Anda mencentang dan menekan Terapkan; setiap penerapan bisa dibatalkan.</div>
    </div>
    <div class="cr-head-act">
        <a href="<?= base_url('creative') ?>" class="cr-btn"><i class="bi bi-arrow-left"></i>Kembali</a>
    </div>
</div>

<?php if ($batches): ?>
<div class="cm-card">
    <div class="cm-card-h"><h2><i class="bi bi-clock-history"></i>Riwayat pemetaan</h2><span class="cr-chip cr-chip-mute"><?= count($batches) ?> terakhir</span></div>
    <div class="cr-tbl-wrap"><table class="cr-tbl">
        <thead><tr><th>Waktu</th><th>Tujuan</th><th class="r">Item</th><th>Oleh</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($batches as $b): ?>
        <tr>
            <td class="nw"><?= esc(date('d M Y H:i', strtotime($b['waktu']))) ?></td>
            <td><?= esc($b['tujuan']) ?></td>
            <td class="r nw"><?= (int) $b['n'] ?></td>
            <td><?= esc($b['oleh'] ?? '—') ?></td>
            <td><?= $b['batal'] ? '<span class="cr-chip cr-chip-mute">Dibatalkan</span>' : '<span class="cr-chip cr-chip-ok">Berlaku</span>' ?></td>
            <td class="r">
                <?php if (!$b['batal']): ?>
                <form method="POST" action="<?= base_url('creative/pemetaan/batal') ?>" onsubmit="return confirm('Batalkan pemetaan ini? Item dikembalikan ke Tanpa Tema.')">
                    <?= csrf_field() ?><input type="hidden" name="batch" value="<?= esc($b['batch'], 'attr') ?>">
                    <button type="submit" class="cr-btn cr-btn-xs"><i class="bi bi-arrow-counterclockwise"></i>Batalkan</button>
                </form>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</div>
<?php endif; ?>

<?php if ($nGroup === 0): ?>
<div class="cr-empty"><i class="bi bi-check2-circle"></i><h5>Tidak ada usulan</h5>
    <p><?= $jumlahBelumBertema > 0 ? count($ragu) . ' item belum bisa dicocokkan ke Tema mana pun; pindahkan manual lewat menu ⋯ → “Pindahkan ke tema…”.' : 'Semua item sudah punya Tema.' ?></p>
</div>
<?php endif; ?>

<?php foreach ($groups as $gi => $g):
    $isBaru = $g['type'] === 'baru';
    $isEv   = $g['type'] === 'event';
?>
<section class="cr-pm-group <?= $isEv ? 'k-event' : '' ?>" data-pm-group>
<form method="POST" action="<?= base_url('creative/pemetaan/terapkan') ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="type" value="<?= $g['type'] ?>">
    <?php if (!$isBaru): ?><input type="hidden" name="id" value="<?= (int) $g['id'] ?>"><?php endif; ?>
    <div class="cr-pm-head">
        <span class="cr-chip <?= $isEv ? 'cr-chip-gold' : 'cr-chip-acc' ?>"><i class="bi <?= $isEv ? 'bi-calendar-event' : 'bi-calendar-month' ?>"></i><?= $isBaru ? 'Tema Bulanan baru' : ($isEv ? 'Event' : 'Tema Bulanan') ?></span>
        <h2><?= esc($g['nama']) ?></h2>
        <span class="cr-c-mute" style="font-size:.78rem"><?= esc($g['label']) ?> · <?= count($g['items']) ?> item</span>
        <div class="sp">
            <label class="cr-check"><input type="checkbox" data-pm-all> Pilih semua</label>
            <button type="submit" class="cr-btn cr-btn-sm cr-btn-primary"><i class="bi bi-check2-all"></i>Terapkan ke yang dicentang</button>
        </div>
    </div>
    <?php if ($isBaru): ?>
    <div class="cr-pm-new">
        <div class="cr-f" style="min-width:260px"><label>Nama tema</label><input type="text" name="nama" class="form-control" value="<?= esc($g['nama'], 'attr') ?>" required maxlength="200"></div>
        <div class="cr-f" style="min-width:160px"><label>Bulan</label><input type="text" name="bulan" class="form-control" value="<?= esc($g['bulan'], 'attr') ?>" pattern="\d{4}-(0[1-9]|1[0-2])" placeholder="YYYY-MM" required title="Format YYYY-MM, mis. 2026-07"></div>
    </div>
    <?php endif; ?>
    <div class="cr-pm-rows">
    <?php foreach ($g['items'] as $r): $it = $r['item']; $cc = $confChip[$r['conf']]; ?>
        <label class="cr-pm-row" style="cursor:pointer">
            <input type="checkbox" name="items[]" value="<?= (int) $it['id'] ?>" <?= $r['pre'] ? 'checked' : '' ?>>
            <span class="nm"><i class="bi <?= $tipeConfig[$it['tipe']]['icon'] ?? 'bi-circle' ?> cr-c-mute"></i> <?= esc($it['nama']) ?> <span class="cr-c-mute" style="font-weight:400">· <?= esc($tipeConfig[$it['tipe']]['label'] ?? $it['tipe']) ?> · <?= $itemTgl($it) ?></span></span>
            <span class="cf"><span class="cr-chip <?= $cc[0] ?>">Keyakinan <?= $cc[1] ?></span></span>
            <span class="al"><?= esc($r['alasan']) ?></span>
        </label>
    <?php endforeach; ?>
    </div>
</form>
</section>
<?php endforeach; ?>

<?php if (!empty($ukuranUsulan)): ?>
<section class="cm-card" id="ukuran">
    <div class="cm-card-h"><h2><i class="bi bi-rulers"></i>Usulan ukuran Media Cetak</h2><span class="cr-chip cr-chip-mute"><?= count($ukuranUsulan) ?> item tanpa ukuran</span></div>
    <form method="POST" action="<?= base_url('creative/pemetaan/ukuran') ?>" class="cm-card-b" style="font-size:.84rem">
        <?= csrf_field() ?>
        <p class="cr-c-mute" style="margin-bottom:.6rem">Ukuran dibaca dari pola angka × angka di nama / deskripsi / catatan. Ini hanya <b>usulan</b>: tidak ada yang tersimpan sampai Anda mencentang, memeriksa (boleh diubah), lalu menekan Simpan.</p>
        <div class="cr-pm-rows">
        <?php foreach ($ukuranUsulan as $u): ?>
            <label class="cr-pm-row" style="cursor:pointer">
                <input type="checkbox" name="ukuran_ok[]" value="<?= (int) $u['id'] ?>" <?= $u['saran'] ? 'checked' : '' ?>>
                <span class="nm"><i class="bi bi-printer cr-c-mute"></i> <?= esc($u['nama']) ?></span>
                <span class="cf"><input type="text" name="ukuran[<?= (int) $u['id'] ?>]" value="<?= esc((string) $u['saran'], 'attr') ?>" maxlength="80" class="form-control form-control-sm" placeholder="ukuran, mis. 60×160 cm" aria-label="Ukuran untuk <?= esc($u['nama'], 'attr') ?>"></span>
                <span class="al"><?= $u['saran'] ? 'Usulan dari teks item' : 'Tidak ada pola ukuran di teks; isi manual bila perlu' ?></span>
            </label>
        <?php endforeach; ?>
        </div>
        <div style="margin-top:.7rem;text-align:right"><button type="submit" class="cr-btn cr-btn-primary"><i class="bi bi-save"></i>Simpan ukuran terpilih</button></div>
    </form>
</section>
<?php endif; ?>

<?php if ($ragu): ?>
<div class="cm-card">
    <div class="cm-card-h"><h2><i class="bi bi-question-circle"></i>Tanpa usulan (ragu)</h2><span class="cr-chip cr-chip-mute"><?= count($ragu) ?> item</span></div>
    <div class="cm-card-b" style="font-size:.84rem">
        <p class="cr-c-mute" style="margin-bottom:.5rem">Nama dan tanggalnya tidak cocok dengan Tema mana pun. Biarkan sebagai Tanpa Tema, atau pindahkan manual lewat menu ⋯ → “Pindahkan ke tema…” di kartu item.</p>
        <?php foreach (array_slice($ragu, 0, 12) as $it): ?><span class="cr-chip cr-chip-mute" style="margin:.1rem"><?= esc($it['nama']) ?></span><?php endforeach; ?>
        <?php if (count($ragu) > 12): ?><span class="cr-c-mute"> … dan <?= count($ragu) - 12 ?> lainnya</span><?php endif; ?>
    </div>
</div>
<?php endif; ?>

</div>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
document.querySelectorAll('[data-pm-all]').forEach(function (a) {
    a.addEventListener('change', function () {
        a.closest('[data-pm-group]').querySelectorAll('input[name="items[]"]').forEach(function (c) { c.checked = a.checked; });
    });
});
</script>
<?= $this->endSection() ?>
