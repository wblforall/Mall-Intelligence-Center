<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/budget-flowstore.css') ?>?v=<?= @filemtime(FCPATH . 'css/budget-flowstore.css') ?: 1 ?>">
<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= $this->include('budget_dept/_kepala') ?>
<?php
use App\Libraries\BudgetRekap;

$BLN = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$rp  = fn ($v) => number_format((float) $v, 0, ',', '.');
$tglPendek = fn (?string $d) => $d ? date('d/m/Y', strtotime($d)) : '–';
$bulanPendek = fn (string $ym) => substr($BLN[(int) substr($ym, 5, 2)], 0, 3) . ' ' . substr($ym, 0, 4);
$qty = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, ',', '.'), '0'), ',');
$posAktif = array_values(array_filter($pos, fn ($p) => ! empty($p['aktif'])));
[$_pk, $_pLabel, $_pIkon] = bdtPosisi((int) $tahun);
$kembali = (string) current_url(true);
if (! empty($_SERVER['QUERY_STRING'])) $kembali .= '?' . $_SERVER['QUERY_STRING'];
$q = fn (array $x = []) => array_filter(array_replace(['tahun' => $tahun, 'status' => $status !== 'baru' ? $status : null, 'bln' => $bln ?: null, 'pos' => $posF ?: null,
    'sup' => $sup !== '' ? $sup : null, 'q' => $cari !== '' ? $cari : null, 'kep' => $kep !== '' ? $kep : null, 'kel' => $kel ? 1 : null], $x), fn ($v) => $v !== null);
$totalKpi = array_sum(array_column($kpi, 'n'));
$modeContoh = $sumber !== 'http';
$tglMulai = date('j', strtotime($mulai)) . ' ' . substr($BLN[(int) date('n', strtotime($mulai))], 0, 3) . ' ' . date('Y', strtotime($mulai));
$ringkasRp = fn ($v) => (float) $v >= 1e6 ? 'Rp ' . rtrim(rtrim(number_format($v / 1e6, 1, ',', '.'), '0'), ',') . ' jt' : 'Rp ' . $rp($v);
$potong = fn (string $t, int $n) => mb_strlen($t) > $n ? mb_substr($t, 0, $n - 1) . '…' : $t;
?>

<div class="bdt bdt-banner bdt-p-<?= $_pk ?>" role="status" data-bdt-banner>
    <i class="bi <?= $_pIkon ?>"></i>
    <span><?= $canEdit ? 'Barang diterima dicatat ke <strong class="th">TAHUN ' . (int) $tahun . '</strong>' : 'Anda sedang <strong>MELIHAT BARANG DITERIMA</strong> <strong class="th">' . (int) $tahun . '</strong>' ?>
        <span class="bdt-chip ms-1"><?= $_pLabel ?></span></span>
    <span class="sub"><?= $canEdit ? 'Bulan pembebanan harus di tahun ' . (int) $tahun . '.' : esc($dept['name']) . ' · barang diterima ' . (int) $tahun . '.' ?></span>
</div>

<div class="bfs" id="bfs"
     data-url-draf="<?= $url('flowstore/draf') ?>" data-csrf-name="<?= csrf_token() ?>" data-tahun="<?= (int) $tahun ?>" data-can-edit="<?= $canEdit ? 1 : 0 ?>">

<!-- Kepala: sinkron -->
<div class="bfs-head">
    <div class="bfs-head-t">
        <div class="bfs-title"><i class="bi bi-box-seam"></i> Barang diterima dari FlowStore
            <?php if ($modeContoh): ?><span class="bfs-tag bfs-tag-warn" title="Server MIC belum tersambung ke FlowStore sungguhan — data di bawah adalah contoh uji"><?= $sumber === 'fixture' ? 'DATA CONTOH' : 'BELUM TERSAMBUNG' ?></span><?php endif; ?>
        </div>
        <div class="bfs-sub">
            <?php $ok = $sinkron['ok']; $akhir = $sinkron['terakhir']; ?>
            <?php if ($ok): ?>
            Sinkron terakhir berhasil <strong><?= date('d/m/Y H:i', strtotime($ok['selesai'])) ?></strong> · <?= (int) $ok['jumlah'] ?> item dibaca
            <?php else: ?>
            Belum pernah disinkronkan.
            <?php endif; ?>
            <?php if ($akhir && ! (int) $akhir['ok']): ?>
            <span class="bfs-gagal"><i class="bi bi-exclamation-triangle"></i> Percobaan <?= date('d/m H:i', strtotime($akhir['selesai'])) ?> gagal: <?= esc((string) $akhir['galat']) ?></span>
            <?php endif; ?>
        </div>
    </div>
    <?php if ($canEdit): ?>
    <form method="POST" action="<?= $url('flowstore/sinkron') ?>" class="bfs-head-a" data-bfs-sinkron>
        <?= csrf_field() ?><input type="hidden" name="tahun" value="<?= (int) $tahun ?>"><input type="hidden" name="dept" value="<?= (int) $dept['id'] ?>">
        <input type="hidden" name="kembali" value="<?= esc($kembali) ?>">
        <button class="bfs-btn bfs-btn-primary" <?= $siap ? '' : 'disabled title="' . esc($siap ? '' : 'Penautan FlowStore belum dikonfigurasi di server') . '"' ?>>
            <i class="bi bi-arrow-repeat"></i><span>Sinkronkan dari FlowStore</span>
        </button>
    </form>
    <?php endif; ?>
</div>

<div class="bfs-info"><i class="bi bi-info-circle"></i>
    <span><strong>Mode usulan:</strong> tidak ada angka masuk Realisasi sebelum Anda menekan <em>Catat</em>. Nilai dibaca apa adanya dari FlowStore (sudah termasuk PPN) dan tidak bisa diubah di sini —
    bila perlu koreksi, pilih <em>Abaikan</em> lalu input manual di tab <a href="<?= $url('realisasi', ['tahun' => $tahun]) ?>">Realisasi</a>.</span>
</div>

<div class="bfs-mulai" id="bfsMulai">
    <i class="bi bi-calendar-check"></i>
    <span>Penarikan FlowStore mulai <strong><?= esc($tglMulai) ?></strong><?= $mulaiKhusus ? ' <span class="bfs-tag">khusus dept ini</span>' : '' ?>; sebelum itu dicatat manual / Excel dan tidak masuk antrean.</span>
    <?php if ($isAdmin): ?>
    <details class="bfs-mulai-ubah"><summary>ubah</summary>
        <form method="POST" action="<?= $url('flowstore/mulai') ?>" class="bfs-mulai-f">
            <?= csrf_field() ?><input type="hidden" name="tahun" value="<?= (int) $tahun ?>"><input type="hidden" name="dept" value="<?= (int) $dept['id'] ?>">
            <label>Mulai tanggal<input type="date" name="mulai" value="<?= esc($mulai) ?>" class="form-control form-control-sm" required></label>
            <label>Berlaku untuk<select name="lingkup" class="form-select form-select-sm">
                <option value="dept">departemen ini saja</option><option value="semua" <?= $mulaiKhusus ? '' : 'selected' ?>>semua departemen (bawaan)</option></select></label>
            <button class="bfs-btn bfs-btn-sm bfs-btn-primary"><i class="bi bi-floppy"></i>Simpan</button>
            <?php if ($mulaiKhusus): ?><button name="reset" value="1" class="bfs-btn bfs-btn-sm" title="Kembali mengikuti bawaan semua departemen"><i class="bi bi-arrow-counterclockwise"></i>Ikuti bawaan</button><?php endif; ?>
        </form>
        <p class="bfs-hint">Memajukan tanggal membuang item lama yang belum diproses dari antrean. Memundurkan perlu sinkron ulang.</p>
    </details>
    <?php endif; ?>
</div>

<!-- KPI + status -->
<div class="bfs-kpis" role="tablist" aria-label="Status">
    <?php
    $kartu = [
        'baru'           => ['Belum diproses', 'bi-hourglass-split', 'warn'],
        'dicatat'        => ['Sudah dicatat', 'bi-check2-circle', 'ok'],
        'diabaikan'      => ['Diabaikan', 'bi-slash-circle', 'mute'],
        'perlu_ditinjau' => ['Perlu ditinjau', 'bi-flag', 'bad'],
    ];
    foreach ($kartu as $k => [$lbl, $ico, $warna]): $n = $kpi[$k]['n']; ?>
    <a href="<?= $url('flowstore', $q(['status' => $k === 'baru' ? null : $k, 'halaman' => null])) ?>" class="bfs-kpi bfs-k-<?= $warna ?> <?= $status === $k ? 'is-on' : '' ?>" role="tab" aria-selected="<?= $status === $k ? 'true' : 'false' ?>">
        <span class="bfs-kpi-l"><i class="bi <?= $ico ?>"></i><?= $lbl ?></span>
        <span class="bfs-kpi-v"><?= $n ?></span>
        <span class="bfs-kpi-s"><?= $k === 'diabaikan' && ! $n ? '–' : 'Rp ' . $rp($kpi[$k]['nilai']) ?></span>
    </a>
    <?php endforeach; ?>
    <div class="bfs-kpi bfs-k-info">
        <span class="bfs-kpi-l"><i class="bi bi-calendar3"></i><?= $bulanKpi ? 'Nilai diterima ' . substr($BLN[$bulanKpi], 0, 3) : 'Nilai diterima ' . (int) $tahun ?></span>
        <span class="bfs-kpi-v bfs-kpi-v-rp">Rp <?= $rp($nilaiBulan) ?></span>
        <span class="bfs-kpi-s"><?= $bulanKpi && ! $bln ? 'bulan ini, tanpa yang diabaikan' : 'tanpa yang diabaikan' ?></span>
    </div>
</div>

<!-- Saringan -->
<form method="GET" class="bfs-filter" id="bfsFilter">
    <?php if ($depts): ?><input type="hidden" name="dept" value="<?= (int) $dept['id'] ?>"><?php endif; ?>
    <input type="hidden" name="tahun" value="<?= (int) $tahun ?>">
    <?php if ($kel): ?><input type="hidden" name="kel" value="1"><?php endif; ?>
    <?php if ($kep !== ''): ?><input type="hidden" name="kep" value="<?= esc($kep) ?>"><?php endif; ?>
    <label class="bfs-f"><span>Status</span>
        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
            <?php foreach (['baru' => 'Belum diproses', 'dicatat' => 'Sudah dicatat', 'diabaikan' => 'Diabaikan', 'perlu_ditinjau' => 'Perlu ditinjau', 'semua' => 'Semua'] as $v => $l): ?>
            <option value="<?= $v ?>" <?= $status === $v ? 'selected' : '' ?>><?= $l ?></option>
            <?php endforeach; ?>
        </select></label>
    <label class="bfs-f"><span>Bulan terima</span>
        <select name="bln" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="0">Semua bulan</option>
            <?php foreach ($BLN as $m => $n): ?><option value="<?= $m ?>" <?= $bln === $m ? 'selected' : '' ?>><?= $n ?></option><?php endforeach; ?>
        </select></label>
    <label class="bfs-f"><span>Pos</span>
        <select name="pos" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">Semua pos</option>
            <?php foreach ($pos as $p): ?><option value="<?= (int) $p['id'] ?>" <?= (int) $posF === (int) $p['id'] ? 'selected' : '' ?>><?= esc($p['nama']) ?></option><?php endforeach; ?>
        </select></label>
    <label class="bfs-f"><span>Supplier</span>
        <select name="sup" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">Semua supplier</option>
            <?php foreach ($suppliers as $s): ?><option value="<?= esc($s) ?>" <?= $sup === $s ? 'selected' : '' ?>><?= esc($s) ?></option><?php endforeach; ?>
        </select></label>
    <label class="bfs-f bfs-f-q"><span>Cari</span>
        <input type="search" name="q" value="<?= esc($cari) ?>" class="form-control form-control-sm" placeholder="Barang, keperluan, No. MR / PR / BTB / PO, supplier"></label>
    <div class="bfs-f-a">
        <button class="bfs-btn bfs-btn-sm"><i class="bi bi-search"></i>Cari</button>
        <?php if ($bln || $posF || $sup !== '' || $cari !== '' || $status !== 'baru' || $kep !== '' || $kel): ?>
        <a href="<?= $url('flowstore', ['tahun' => $tahun]) ?>" class="bfs-btn bfs-btn-sm" title="Kembalikan saringan"><i class="bi bi-x-lg"></i>Reset</a>
        <?php endif; ?>
    </div>
</form>

<?php if (! $posAktif && $canEdit): ?>
<div class="alert alert-info small">Belum ada pos budget aktif. <a href="<?= $url('pos') ?>">Buat pos dulu</a> sebelum mencatat.</div>
<?php endif; ?>

<?php if (! $divisiDept): ?>
<div class="bfs-kosong"><i class="bi bi-diagram-3"></i>
    <h5>Belum ada divisi FlowStore untuk departemen ini</h5>
    <p><?= $isAdmin ? 'Pilih divisi FlowStore-nya di <strong>Pengaturan penautan</strong> di bawah, lalu sinkronkan.' : 'Minta admin memetakan divisi FlowStore departemen ini.' ?></p>
</div>
<?php elseif ($sebelumMulai): ?>
<div class="bfs-kosong"><i class="bi bi-calendar-x"></i>
    <h5>Tahun <?= (int) $tahun ?> tidak ditarik dari FlowStore</h5>
    <p>Penarikan FlowStore mulai <?= esc($tglMulai) ?>; barang diterima sebelum itu sudah dicatat manual / Excel di tab <a href="<?= $url('realisasi', ['tahun' => $tahun]) ?>">Realisasi</a>.</p>
</div>
<?php elseif (! $adaCermin && ! $totalKpi): ?>
<div class="bfs-kosong"><i class="bi bi-cloud-download"></i>
    <h5>Belum ada data FlowStore</h5>
    <p><?= $canEdit ? 'Tekan <strong>Sinkronkan dari FlowStore</strong> untuk menarik barang yang sudah diterima.' : 'Data akan tampil setelah sinkron pertama dijalankan oleh admin atau staf departemen.' ?></p>
</div>
<?php elseif (! $rows): ?>
<div class="bfs-kosong"><i class="bi bi-inbox"></i>
    <h5><?= $status === 'baru' && ! $bln && ! $posF && $sup === '' && $cari === '' ? 'Tidak ada barang yang menunggu' : 'Tidak ada item pada saringan ini' ?></h5>
    <p><?= $status === 'baru' && $totalKpi ? 'Semua barang diterima ' . (int) $tahun . ' sudah diproses. Lihat status lain di atas.' : 'Ubah atau reset saringan untuk melihat item lain.' ?></p>
</div>
<?php else: ?>

<div class="bfs-kepbar" aria-label="Keperluan">
    <div class="bfs-kepbar-h">
        <span class="bfs-kepbar-t"><i class="bi bi-bullseye"></i>Keperluan</span>
        <a class="bfs-sw <?= $kel ? 'is-on' : '' ?>" role="switch" aria-checked="<?= $kel ? 'true' : 'false' ?>" href="<?= $url('flowstore', $q(['kel' => $kel ? null : 1, 'halaman' => null])) ?>"
           title="Kelompokkan baris yang punya keperluan sama; pos &amp; bulan bisa ditetapkan sekaligus per kelompok"><i class="bi bi-collection"></i>Kelompokkan menurut keperluan</a>
        <?php if ($kep !== ''): ?><a class="bfs-btn bfs-btn-xs" href="<?= $url('flowstore', $q(['kep' => null, 'halaman' => null])) ?>"><i class="bi bi-x-lg"></i>Hapus saringan keperluan</a><?php endif; ?>
    </div>
    <?php if ($kelompok): ?>
    <div class="bfs-chips">
        <?php foreach (array_slice($kelompok, 0, 8) as $g): $on = $kep === $g['kep']; ?>
        <a class="bfs-chip <?= $on ? 'is-on' : '' ?>" href="<?= $url('flowstore', $q(['kep' => $on ? null : $g['kep'], 'halaman' => null])) ?>" title="<?= esc($g['kep']) ?>">
            <span class="bfs-chip-t"><?= esc($potong(preg_replace('/^UNTUK (KEPERLUAN )?/i', '', $g['kep']), 38)) ?></span>
            <span class="bfs-chip-n"><?= (int) $g['n'] ?> item · <?= $ringkasRp($g['nilai']) ?></span>
        </a>
        <?php endforeach; ?>
        <?php if (count($kelompok) > 8): ?><span class="bfs-sub align-self-center">+ <?= count($kelompok) - 8 ?> kelompok lain (gunakan tampilan dikelompokkan)</span><?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php if ($canEdit && $status === 'baru'): ?>
<div class="bfs-bulk" id="bfsBulk" hidden>
    <span><strong id="bfsBulkN">0</strong> item dipilih</span>
    <button type="button" class="bfs-btn bfs-btn-primary bfs-btn-sm" id="bfsCatatMassal"><i class="bi bi-check2-all"></i>Catat terpilih</button>
    <button type="button" class="bfs-btn bfs-btn-sm" id="bfsAbaikanMassal"><i class="bi bi-slash-circle"></i>Abaikan terpilih</button>
    <button type="button" class="bfs-btn bfs-btn-sm" id="bfsBatalPilih">Batal pilih</button>
</div>
<?php endif; ?>

<div class="bfs-card">
<table class="bfs-tbl" id="bfsTbl">
    <thead><tr>
        <?php if ($canEdit && $status === 'baru'): ?><th class="c-cek"><input type="checkbox" id="bfsSemua" aria-label="Pilih semua yang tampil"></th><?php endif; ?>
        <th>Diterima</th><th>Barang</th><th class="c-qty">Qty</th><th class="c-nilai">Nilai (Rp)</th>
        <th class="c-pos">Pos budget</th><th class="c-bln">Bulan beban<?= $pisahMal ? ' · Mal' : '' ?></th><th class="c-aksi"></th>
    </tr></thead>
    <tbody>
    <?php $grupSekarang = null; $grupHitung = []; if ($kel) { foreach ($rows as $_r) { $k = trim((string) $_r['keperluan']); $grupHitung[$k]['n'] = ($grupHitung[$k]['n'] ?? 0) + 1; $grupHitung[$k]['nilai'] = ($grupHitung[$k]['nilai'] ?? 0) + (float) $_r['nilai_diterima']; $grupHitung[$k]['baru'] = ($grupHitung[$k]['baru'] ?? 0) + ($_r['pem_status'] === 'baru' ? 1 : 0); if (! empty($_r['no_mr'])) $grupHitung[$k]['mr'][$_r['no_mr']] = 1; } }
    $grupNo = 0; foreach ($rows as $r):
        if ($kel) { $gk = trim((string) $r['keperluan']);
            if ($grupSekarang !== $gk || $grupSekarang === null) { $grupSekarang = $gk; $grupNo++; $G = $grupHitung[$gk]; $mrs = array_keys($G['mr'] ?? []);
                $kolom = 7 + (($canEdit && $status === 'baru') ? 1 : 0); ?>
    <tr class="bfs-grup" data-grup="<?= $grupNo ?>"><td colspan="<?= $kolom ?>">
        <div class="bfs-grup-in">
            <?php if ($canEdit && $status === 'baru' && $G['baru'] >= 2): ?><input type="checkbox" class="bfs-grup-cek" aria-label="Pilih semua item kelompok ini"><?php endif; ?>
            <div class="bfs-grup-t">
                <strong><?= $gk !== '' ? esc($gk) : 'Tanpa keperluan' ?></strong>
                <span class="bfs-sub"><?= $mrs ? esc(count($mrs) === 1 ? $mrs[0] : count($mrs) . ' MR (' . implode(', ', array_slice($mrs, 0, 3)) . (count($mrs) > 3 ? ', …' : '') . ')') . ' · ' : '' ?><?= (int) $G['n'] ?> item · Rp <?= $rp($G['nilai']) ?></span>
            </div>
            <?php if ($canEdit && $status === 'baru' && $G['baru'] >= 2): ?>
            <div class="bfs-grup-a">
                <select class="form-select form-select-sm bfs-grup-pos" aria-label="Pos untuk seluruh kelompok">
                    <option value="">— Pos kelompok —</option>
                    <?php foreach ($posAktif as $p): ?><option value="<?= (int) $p['id'] ?>"><?= esc($p['nama']) ?></option><?php endforeach; ?>
                </select>
                <select class="form-select form-select-sm bfs-grup-bln" aria-label="Bulan beban untuk seluruh kelompok">
                    <option value="">— Bulan —</option>
                    <?php foreach ($BLN as $m => $n): ?><option value="<?= sprintf('%d-%02d', $tahun, $m) ?>"><?= substr($n, 0, 3) ?> <?= (int) $tahun ?></option><?php endforeach; ?>
                </select>
                <button type="button" class="bfs-btn bfs-btn-xs bfs-grup-terap" title="Isi pos &amp; bulan di atas ke semua baris kelompok ini (tiap baris tetap bisa diubah sendiri)"><i class="bi bi-arrow-down-square"></i>Terapkan ke <?= (int) $G['baru'] ?> item</button>
                <button type="button" class="bfs-btn bfs-btn-primary bfs-btn-xs bfs-grup-catat"><i class="bi bi-check2-all"></i>Catat kelompok</button>
            </div>
            <?php endif; ?>
        </div>
    </td></tr>
    <?php } }
        $st = $r['pem_status']; $baru = $st === 'baru';
        $nilai0 = (float) $r['nilai_diterima'] <= 0;
        $lain = substr((string) $r['tanggal_diterima'], 0, 4) !== (string) $tahun;
        $posPilih = (int) ($r['pem_pos'] ?: ($r['rek']['pos_id'] ?? 0));
        $adaDraf = (bool) $r['pem_pos'];
        $bulanPilih = ($r['bulan_beban'] && substr($r['bulan_beban'], 0, 4) === (string) $tahun) ? $r['bulan_beban'] : $r['bln_bawaan'];
        $malPilih = $r['pem_mall'] ?: $r['mal_bawaan'];
        $bulanTerima = substr((string) $r['tanggal_diterima'], 0, 7);
        $tip = 'Sebelum PPN: Rp ' . ($r['nilai_sebelum_ppn'] !== null ? $rp($r['nilai_sebelum_ppn']) : '–') . ' · PPN: Rp ' . ($r['ppn_rupiah'] !== null ? $rp($r['ppn_rupiah']) : '–') . ' · sumber nilai: ' . ($r['sumber_nilai'] ?: '–');
    ?>
    <tr data-id="<?= (int) $r['id_btb_item'] ?>"<?= $kel ? ' data-grup-isi="' . $grupNo . '"' : '' ?> data-terima="<?= esc($bulanTerima) ?>" data-nol="<?= $nilai0 ? 1 : 0 ?>" class="<?= $st === 'perlu_ditinjau' ? 'is-tinjau' : '' ?>">
        <?php if ($canEdit && $status === 'baru'): ?>
        <td class="c-cek"><?php if ($baru): ?><input type="checkbox" class="bfs-cek" aria-label="Pilih <?= esc($r['nama_barang']) ?>"><?php endif; ?></td>
        <?php endif; ?>
        <td data-l="Diterima" class="c-tgl">
            <span class="bfs-tgl"><?= $tglPendek($r['tanggal_diterima']) ?></span>
            <?php if ($lain): ?><span class="bfs-tag bfs-tag-warn" title="Diterima di tahun <?= substr($r['tanggal_diterima'], 0, 4) ?>, bukan tahun halaman ini">diterima <?= substr($r['tanggal_diterima'], 0, 4) ?></span><?php endif; ?>
            <?php if ($r['status_btb'] === 'batal'): ?><span class="bfs-tag bfs-tag-bad">BTB batal</span><?php endif; ?>
            <span class="bfs-sub" title="No. BTB"><?= esc($r['no_btb']) ?></span>
            <span class="bfs-sub" title="No. PO"><?= esc($r['no_po']) ?></span>
        </td>
        <td data-l="Barang" class="c-brg">
            <span class="bfs-nama"><?= esc($r['nama_barang']) ?></span>
            <span class="bfs-sub"><?php if ($r['kode_barang']): ?><span class="bfs-kode"><?= esc($r['kode_barang']) ?></span> · <?php endif; ?><?= esc($r['nama_supplier'] ?? '–') ?></span>
            <?php if (! empty($r['keperluan'])): ?>
            <div class="bfs-kepw">
                <span class="bfs-kep" title="<?= esc($r['keperluan']) ?>"><i class="bi bi-bullseye"></i><?= esc($r['keperluan']) ?></span>
                <button type="button" class="bfs-more" aria-expanded="false" hidden>selengkapnya</button>
            </div>
            <?php else: ?>
            <div class="bfs-kepw"><span class="bfs-kep is-kosong"><i class="bi bi-bullseye"></i>keperluan belum diisi di FlowStore</span></div>
            <?php endif; ?>
            <?php
            $det = [];
            if (! empty($r['spesifikasi'])) $det['Spesifikasi'] = $r['spesifikasi'];
            foreach (['No. MR' => 'no_mr', 'No. PR' => 'no_pr', 'No. PO' => 'no_po', 'No. BTB' => 'no_btb'] as $l => $k) if (! empty($r[$k])) $det[$l] = $r[$k];
            foreach (['Ket. PR' => 'keterangan_pr', 'Ket. PO' => 'keterangan_po', 'Ket. BTB' => 'keterangan_btb'] as $l => $k) {
                if (! empty($r[$k]) && trim($r[$k]) !== trim((string) $r['keperluan'])) $det[$l] = $r[$k];
            }
            ?>
            <?php if ($det): ?>
            <dl class="bfs-det" hidden>
                <?php foreach ($det as $l => $v): ?><div><dt><?= esc($l) ?></dt><dd><?= esc($v) ?></dd></div><?php endforeach; ?>
            </dl>
            <?php endif; ?>
        </td>
        <td data-l="Qty" class="c-qty"><?= $qty($r['jumlah_diterima']) ?> <span class="bfs-sub-i"><?= esc($r['satuan'] ?? '') ?></span></td>
        <td data-l="Nilai" class="c-nilai">
            <span class="bfs-rp <?= $nilai0 ? 'is-nol' : '' ?>" tabindex="0" data-bs-toggle="tooltip" data-bs-title="<?= esc($tip) ?>"><?= $rp($r['nilai_diterima']) ?></span>
            <?php if ($nilai0): ?><span class="bfs-sub bfs-warn">Rp 0 — pakai Abaikan</span><?php endif; ?>
        </td>
        <td data-l="Pos budget" class="c-pos">
            <?php if ($baru && $canEdit): ?>
            <select class="form-select form-select-sm bfs-pos <?= $posPilih ? '' : 'is-kosong' ?>" aria-label="Pos budget" data-rek="<?= (int) ($r['rek']['pos_id'] ?? 0) ?>">
                <option value="">— Pilih pos —</option>
                <?php foreach ($posAktif as $p): ?><option value="<?= (int) $p['id'] ?>" <?= $posPilih === (int) $p['id'] ? 'selected' : '' ?>><?= esc($p['nama']) ?></option><?php endforeach; ?>
            </select>
            <?php $rek = $r['rek']; ?>
            <span class="bfs-rekbadge" <?= ($rek && $rek['pos_id'] && $posPilih === (int) $rek['pos_id']) ? '' : 'hidden' ?>
                  tabindex="0" data-bs-toggle="tooltip" data-bs-title="<?= esc(($rek['alasan'] ?? '') . ' · skor ' . (int) ($rek['skor'] ?? 0)) ?>"><i class="bi bi-stars"></i>rekomendasi</span>
            <?php if ($rek && ! $rek['pos_id']): ?><span class="bfs-sub" title="<?= esc($rek['alasan']) ?>"><?= esc(mb_strimwidth($rek['alasan'], 0, 60, '…')) ?></span><?php endif; ?>
            <?php else: ?>
            <?= $r['pem_pos'] ? esc($posNama[$r['pem_pos']] ?? '#' . $r['pem_pos']) : '–' ?>
            <?php endif; ?>
        </td>
        <td data-l="Bulan beban" class="c-bln">
            <?php if ($baru && $canEdit): ?>
            <select class="form-select form-select-sm bfs-bln" aria-label="Bulan pembebanan">
                <?php foreach ($BLN as $m => $n): $ym = sprintf('%d-%02d', $tahun, $m); ?><option value="<?= $ym ?>" <?= $bulanPilih === $ym ? 'selected' : '' ?>><?= substr($n, 0, 3) ?> <?= (int) $tahun ?></option><?php endforeach; ?>
            </select>
            <span class="bfs-sub bfs-asal" <?= $bulanPilih === $bulanTerima ? 'hidden' : '' ?>>tagihan <?= $bulanPendek($bulanTerima) ?></span>
            <?php else: ?>
            <?= $r['bulan_beban'] ? $bulanPendek($r['bulan_beban']) : '–' ?>
            <?php endif; ?>
            <?php if ($pisahMal): ?>
            <?php if ($baru && $canEdit): ?>
            <select class="form-select form-select-sm bfs-mal mt-1" aria-label="Mal">
                <?php foreach (BudgetRekap::MAL_REALISASI as $k => $l): ?><option value="<?= $k ?>" <?= $malPilih === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
            </select>
            <?php else: ?><span class="bfs-sub"><?= esc(BudgetRekap::MAL_REALISASI[$malPilih] ?? '–') ?></span><?php endif; ?>
            <?php endif; ?>
        </td>
        <td class="c-aksi">
            <?php if ($st === 'baru' && $canEdit): ?>
            <button type="button" class="bfs-btn bfs-btn-primary bfs-btn-xs bfs-catat" <?= $nilai0 ? 'disabled title="Nilai Rp 0 tidak bisa dicatat — gunakan Abaikan"' : '' ?>><i class="bi bi-check2"></i>Catat</button>
            <button type="button" class="bfs-btn bfs-btn-xs bfs-abaikan" title="Bukan beban departemen ini / sudah dicatat manual"><i class="bi bi-slash-circle"></i>Abaikan</button>
            <span class="bfs-simpan" hidden><i class="bi bi-check2"></i> draf tersimpan</span>
            <?php elseif ($st === 'dicatat'): ?>
            <a class="bfs-btn bfs-btn-xs" href="<?= $url('realisasi', ['tahun' => $tahun, 'q' => $r['no_btb']]) ?>" title="Lihat di tab Realisasi"><i class="bi bi-receipt"></i>Realisasi</a>
            <?php elseif ($st === 'diabaikan'): ?>
            <span class="bfs-sub" title="Alasan diabaikan"><i class="bi bi-chat-left-text"></i> <?= esc($r['alasan_abaikan']) ?></span>
            <?php if ($canEdit): ?>
            <form method="POST" action="<?= $url('flowstore/pulihkan') ?>" class="d-inline"><?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $r['id_btb_item'] ?>"><input type="hidden" name="tahun" value="<?= (int) $tahun ?>"><input type="hidden" name="dept" value="<?= (int) $dept['id'] ?>"><input type="hidden" name="kembali" value="<?= esc($kembali) ?>">
                <button class="bfs-btn bfs-btn-xs"><i class="bi bi-arrow-counterclockwise"></i>Batalkan abaikan</button></form>
            <?php endif; ?>
            <?php else: ?>
            <span class="bfs-sub bfs-warn"><i class="bi bi-flag"></i> <?= esc($r['catatan_tinjau']) ?></span>
            <a class="bfs-btn bfs-btn-xs" href="<?= $url('realisasi', ['tahun' => $tahun, 'q' => $r['no_btb']]) ?>"><i class="bi bi-receipt"></i>Realisasi</a>
            <?php if ($canEdit): ?>
            <form method="POST" action="<?= $url('flowstore/tinjau') ?>" class="d-inline"><?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $r['id_btb_item'] ?>"><input type="hidden" name="tahun" value="<?= (int) $tahun ?>"><input type="hidden" name="dept" value="<?= (int) $dept['id'] ?>"><input type="hidden" name="kembali" value="<?= esc($kembali) ?>">
                <button class="bfs-btn bfs-btn-xs" title="Realisasi dipertahankan apa adanya"><i class="bi bi-check2"></i>Sudah ditinjau</button></form>
            <?php endif; ?>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<div class="bfs-foot">
    <span><?= $total ?> item<?= $status !== 'semua' ? ' · ' . strtolower($kartu[$status][0] ?? '') : '' ?></span>
    <?php if ($halMaks > 1): ?>
    <nav class="bfs-pager" aria-label="Halaman">
        <?php if ($hal > 1): ?><a class="bfs-btn bfs-btn-xs" href="<?= $url('flowstore', $q(['halaman' => $hal - 1])) ?>"><i class="bi bi-chevron-left"></i></a><?php endif; ?>
        <span>Halaman <?= $hal ?> / <?= $halMaks ?></span>
        <?php if ($hal < $halMaks): ?><a class="bfs-btn bfs-btn-xs" href="<?= $url('flowstore', $q(['halaman' => $hal + 1])) ?>"><i class="bi bi-chevron-right"></i></a><?php endif; ?>
    </nav>
    <?php endif; ?>
</div>
</div>
<?php endif; ?>

<?php if ($isAdmin): ?>
<details class="bfs-admin">
    <summary><i class="bi bi-gear"></i> Pengaturan penautan <span class="bfs-tag">admin</span><i class="bi bi-chevron-down caret"></i></summary>
    <div class="bfs-admin-b">
        <section>
            <h6>Divisi FlowStore untuk <?= esc($dept['name']) ?></h6>
            <form method="POST" action="<?= $url('flowstore/divisi') ?>">
                <?= csrf_field() ?><input type="hidden" name="tahun" value="<?= (int) $tahun ?>"><input type="hidden" name="dept" value="<?= (int) $dept['id'] ?>">
                <div class="bfs-divisi">
                    <?php foreach ($divisiFs as $d):
                        $pemilik = $petaSemua[$d['id_divisi']]['dept_id'] ?? null; $lain = $pemilik && (int) $pemilik !== (int) $dept['id']; ?>
                    <label class="bfs-chk <?= $lain ? 'is-lain' : '' ?>" title="<?= $lain ? 'Sudah dipetakan ke ' . esc($petaSemua[$d['id_divisi']]['dept_nama']) : '' ?>">
                        <input type="checkbox" name="divisi[]" value="<?= (int) $d['id_divisi'] ?>" <?= in_array((int) $d['id_divisi'], $divisiDept, true) ? 'checked' : '' ?> <?= $lain ? 'disabled' : '' ?>>
                        <span><?= esc($d['divisi']) ?><?php if ($lain): ?> <small>→ <?= esc($petaSemua[$d['id_divisi']]['dept_nama']) ?></small><?php endif; ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
                <div class="bfs-admin-a">
                    <button class="bfs-btn bfs-btn-sm bfs-btn-primary"><i class="bi bi-floppy"></i>Simpan pemetaan</button>
                </div>
            </form>
            <form method="POST" action="<?= $url('flowstore/divisi/segarkan') ?>" class="mt-2">
                <?= csrf_field() ?><input type="hidden" name="tahun" value="<?= (int) $tahun ?>"><input type="hidden" name="dept" value="<?= (int) $dept['id'] ?>">
                <button class="bfs-btn bfs-btn-sm" <?= $siap ? '' : 'disabled' ?>><i class="bi bi-arrow-repeat"></i>Segarkan daftar divisi dari FlowStore</button>
            </form>
        </section>
        <section>
            <h6>Aturan kata kunci rekomendasi</h6>
            <p class="bfs-hint">Kata dipisah <code>|</code> (mis. <code>CCTV|CAMERA|NVR</code>), atau regex di antara garis miring. Pada nama barang: kata kunci yang muncul paling awal menang. Pada keperluan (keterangan order, didahulukan karena menjelaskan tujuan pembelian): aturan teratas yang cocok menang. Pos kosong = "tidak ada pos khusus" (mencegah rekomendasi keliru).</p>
            <div class="bfs-kata">
                <?php foreach ($kataKunci as $a): ?>
                <div class="bfs-kata-r">
                    <?php if (($a['sasaran'] ?? 'nama') === 'keperluan'): ?><span class="bfs-tag bfs-tag-kep" title="Dicocokkan ke keterangan order; urutan daftar = prioritas">keperluan</span><?php endif; ?><code><?= esc($a['kunci']) ?></code><i class="bi bi-arrow-right"></i><span><?= $a['pos_nama'] !== '' ? esc($a['pos_nama']) : '<em>tanpa pos</em>' ?></span>
                    <form method="POST" action="<?= $url('flowstore/kata/' . $a['id'] . '/hapus') ?>" onsubmit="return confirm('Hapus kata kunci ini?')"><?= csrf_field() ?>
                        <input type="hidden" name="tahun" value="<?= (int) $tahun ?>"><input type="hidden" name="dept" value="<?= (int) $dept['id'] ?>">
                        <button class="bfs-x" aria-label="Hapus"><i class="bi bi-trash"></i></button></form>
                </div>
                <?php endforeach; ?>
                <?php if (! $kataKunci): ?><div class="bfs-sub">Belum ada aturan.</div><?php endif; ?>
            </div>
            <form method="POST" action="<?= $url('flowstore/kata') ?>" class="bfs-kata-baru">
                <?= csrf_field() ?><input type="hidden" name="tahun" value="<?= (int) $tahun ?>"><input type="hidden" name="dept" value="<?= (int) $dept['id'] ?>">
                <select name="sasaran" class="form-select form-select-sm" aria-label="Dicocokkan ke">
                    <option value="nama">pada nama barang</option><option value="keperluan">pada keperluan (didahulukan)</option>
                </select>
                <input type="text" name="pola" maxlength="191" class="form-control form-control-sm" placeholder="mis. PROYEKTOR|LAYAR" required aria-label="Kata kunci">
                <select name="pos" class="form-select form-select-sm" aria-label="Pos tujuan">
                    <option value="">(tanpa pos)</option>
                    <?php foreach ($posAktif as $p): ?><option value="<?= (int) $p['id'] ?>"><?= esc($p['nama']) ?></option><?php endforeach; ?>
                </select>
                <button class="bfs-btn bfs-btn-sm"><i class="bi bi-plus-lg"></i>Tambah</button>
            </form>
        </section>
    </div>
</details>
<?php endif; ?>

</div><!-- /.bfs -->

<?php if ($canEdit): ?>
<form method="POST" action="<?= $url('flowstore/catat') ?>" id="bfsFormCatat" hidden>
    <?= csrf_field() ?><input type="hidden" name="tahun" value="<?= (int) $tahun ?>"><input type="hidden" name="dept" value="<?= (int) $dept['id'] ?>">
    <input type="hidden" name="kembali" value="<?= esc($kembali) ?>"><input type="hidden" name="data" value="">
</form>

<div class="modal fade" id="mAbaikan" tabindex="-1" aria-labelledby="mAbaikanJudul" aria-hidden="true">
<div class="modal-dialog">
<form method="POST" action="<?= $url('flowstore/abaikan') ?>" class="modal-content" id="bfsFormAbaikan">
    <?= csrf_field() ?><input type="hidden" name="tahun" value="<?= (int) $tahun ?>"><input type="hidden" name="dept" value="<?= (int) $dept['id'] ?>">
    <input type="hidden" name="kembali" value="<?= esc($kembali) ?>">
    <div class="modal-header"><h6 class="modal-title" id="mAbaikanJudul">Abaikan <span id="bfsAbaikanN">1</span> item</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
    <div class="modal-body">
        <p class="small text-muted mb-2">Item yang diabaikan tidak masuk Realisasi. Bisa dibatalkan kapan saja dari filter "Diabaikan".</p>
        <label class="form-label small" for="bfs-alasan">Alasan singkat</label>
        <input id="bfs-alasan" name="alasan" class="form-control form-control-sm" maxlength="255" required list="bfsAlasanUmum" autocomplete="off" placeholder="mis. bukan beban departemen ini">
        <datalist id="bfsAlasanUmum">
            <option value="Bukan beban departemen ini"><option value="Sudah dicatat manual di Realisasi"><option value="Dibebankan ke tahun lain"><option value="Nilai perlu dikoreksi — diinput manual">
        </datalist>
        <div id="bfsAbaikanIds"></div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Batal</button>
        <button class="btn btn-sm btn-primary"><i class="bi bi-slash-circle me-1"></i>Abaikan</button>
    </div>
</form>
</div>
</div>
<?php endif; ?>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="<?= base_url('js/budget-flowstore.js') ?>?v=<?= @filemtime(FCPATH . 'js/budget-flowstore.js') ?: 1 ?>"></script>
<?= $this->endSection() ?>
