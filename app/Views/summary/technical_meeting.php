<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Technical Meeting — <?= esc($event['name']) ?></title>
<?= $this->include('_laporan/_style') ?>
<?php
// Teks kop berjalan (@page margin box) — di-escape sebagai string CSS.
$cssStr = fn (string $s) => '"' . preg_replace_callback('/[^A-Za-z0-9 .,:&()\-\/]/u', fn ($m) => '\\' . dechex(mb_ord($m[0])) . ' ', $s) . '"';
$kopBerjalan = $cssStr('Technical Meeting — ' . mb_strimwidth((string) $event['name'], 0, 60, '…'));
?>
<style>
/* Dokumen Technical Meeting: A4 PORTRAIT (menimpa landscape bawaan tema). */
@page { size: A4 portrait; margin: 13mm 12mm 14mm; @top-right { content: <?= $kopBerjalan ?>; } }
@page :first { @top-right { content: none; } }
@media screen { body { max-width: 210mm; padding: 12mm 12mm; } }

/* Kop: tema event di bawah periode */
.doc-header .hdr-tema { order: 4; margin-top: 6px; font-size: 9.5px; color: #c7d3e6; }
.doc-header .hdr-tema b { color: var(--emas-terang); font-weight: 600; letter-spacing: 1px; font-size: 7.5px; margin-right: 5px; }
.doc-header .sub::before { content: 'PELAKSANAAN'; }
.doc-header .meta { max-width: 42%; }

/* Bagian */
.section { margin-top: 6px; margin-bottom: 14px; }
.section .main-table { margin-bottom: 12px; }
.main-table th { white-space: normal; vertical-align: bottom; }
.main-table td { vertical-align: top; }
.main-table td.nomor { text-align: center; color: var(--redup2); font-size: 9px; }
.main-table.tanpa-kosong::after { content: none; display: none; }
.main-table.tanpa-kosong { margin-top: -12px; }
.empty {
    margin-bottom: 12px; padding: 12px; text-align: center; font-size: 10px; font-style: italic;
    color: var(--redup2); background: #fbfcfe; border: 1px dashed var(--garis); border-radius: 8px;
}
.empty.kecil { text-align: left; padding: 6px 10px; margin: 4px 0 8px; }

/* Sub-judul di dalam bagian (hari rundown, kategori, kelompok tipe) */
.sub-header, .day-header, .tipe-group-header {
    display: flex; justify-content: space-between; align-items: center; gap: 12px;
    margin: 8px 0 0; padding: 5px 10px;
    background: #eef2f8; border-left: 3px solid var(--emas); border-radius: 6px 6px 0 0;
    font-size: 10px; font-weight: 700; color: var(--navy-2);
    break-after: avoid; page-break-after: avoid;
}
.sub-header .ket, .tipe-group-header .ket { font-size: 9px; font-weight: 400; color: var(--redup); }

/* Rundown: baris dari Content Event */
.from-content-tag { font-size: 7px; font-weight: 700; color: #2563eb; text-transform: uppercase; letter-spacing: .5px; }
.main-table tr.from-content td { background: #eef4ff !important; }

/* Loyalty */
.program-block {
    margin: 0 0 10px; border: 1px solid var(--garis); border-radius: 8px; overflow: hidden;
    break-inside: avoid; page-break-inside: avoid;
}
.program-name {
    display: flex; justify-content: space-between; align-items: center; gap: 12px;
    padding: 6px 12px; background: var(--latar); border-bottom: 1px solid var(--garis);
    font-size: 10.5px; font-weight: 700; color: var(--tinta);
}
.program-name .ket { font-size: 9px; font-weight: 400; color: var(--redup); }
.program-body { padding: 8px 12px 4px; }
.program-body .main-table { margin-bottom: 8px; }
.program-desk { font-size: 10px; color: var(--teks); margin-bottom: 6px; }
.program-section-label { font-size: 8px; font-weight: 700; text-transform: uppercase; letter-spacing: .8px; color: var(--emas); margin: 4px 0 4px; }

.text-green  { color: #15803d; }
.text-yellow { color: #a16207; }
.text-blue   { color: #1d4ed8; }

/* Tautan kembali (layar saja) */
.btn-kembali {
    position: fixed; top: 16px; left: 16px; z-index: 50; padding: 8px 14px; border-radius: 8px;
    background: #fff; border: 1px solid var(--garis); color: var(--navy-2); font: 600 12px 'Inter', Arial, sans-serif;
    text-decoration: none; box-shadow: 0 4px 14px rgba(9,21,40,.12);
}
</style>
</head>
<body>
<?php
$mallLabels  = ['ewalk' => 'eWalk Simply FUNtastic', 'pentacity' => 'Pentacity Shopping Venue', 'keduanya' => 'eWalk Simply FUNtastic & Pentacity Shopping Venue'];
$startDate   = $event['start_date'];
$endDate     = date('Y-m-d', strtotime($startDate . ' +' . ($event['event_days'] - 1) . ' days'));
$sameDay     = $startDate === $endDate;
$tipeLabels  = ['master_design' => 'Master Design', 'digital' => 'Content Digital', 'cetak' => 'Media Cetak', 'influencer' => 'Influencer', 'media_prescon' => 'Media Prescon'];
$platformLbl = ['ig' => 'Instagram', 'tiktok' => 'TikTok', 'keduanya' => 'IG & TikTok'];
$statusClass = ['draft' => 'netral', 'review' => 'waspada', 'approved' => 'baik', 'revision' => 'buruk'];
$statusLabel = ['draft' => 'Draft', 'review' => 'Review', 'approved' => 'Approved', 'revision' => 'Revisi'];
?>

<button class="btn-print no-print" onclick="window.print()">Cetak</button>
<a class="btn-kembali no-print" href="<?= base_url('events/'.$event['id'].'/summary') ?>">← Kembali ke Summary</a>

<!-- ══ KOP ══ -->
<div class="doc-header">
    <div>
        <div class="title"><?= esc($event['name']) ?></div>
        <div class="org">Dokumen Technical Meeting &mdash; <?= $mallLabels[$event['mall']] ?? esc($event['mall']) ?><?php if (!empty($eventLocations)): ?> &middot; <?= esc(implode(', ', array_column($eventLocations, 'nama'))) ?><?php endif; ?></div>
        <div class="sub"><?= $sameDay
            ? tgl_indo_hari($startDate)
            : tgl_indo_hari($startDate) . ' – ' . tgl_indo_hari($endDate) ?> &middot; <?= $event['event_days'] ?> hari</div>
        <?php if ($event['tema']): ?>
        <div class="hdr-tema"><b>TEMA</b><?= esc($event['tema']) ?></div>
        <?php endif; ?>
    </div>
    <div class="meta">
        Dokumen: Technical Meeting<br>
        Durasi: <?= $event['event_days'] ?> hari<br>
        Dicetak: <?= date('d M Y, H:i') ?>
    </div>
</div>

<!-- ══ 1. RUNDOWN ══ -->
<div class="section">
    <div class="sec-title"><span>Rundown</span>
        <span class="sec-sub"><span class="lencana netral"><?= $event['event_days'] ?> Hari</span></span></div>
    <?php if (empty($rundown)): ?>
    <div class="empty">Belum ada data rundown.</div>
    <?php else: ?>
    <?php foreach ($rundown as $hariKe => $rows):
        $tanggalHari = $rows[0]['tanggal'] ?? null;
    ?>
    <div class="day-header">
        <span>Hari <?= $hariKe ?><?php if ($tanggalHari): ?> — <?= tgl_indo_hari($tanggalHari) ?><?php endif; ?></span>
    </div>
    <table class="main-table">
    <thead>
        <tr>
            <th style="width:28px">#</th>
            <th style="width:80px">Waktu</th>
            <th style="width:28%">Sesi / Acara</th>
            <th>Deskripsi</th>
            <th style="width:100px">PIC</th>
            <th style="width:100px">Lokasi</th>
        </tr>
    </thead>
    <tbody>
    <?php $no = 0; foreach ($rows as $r):
        $no++;
        $waktu = '';
        if ($r['waktu_mulai']) {
            $waktu = date('H:i', strtotime($r['waktu_mulai']));
            if ($r['waktu_selesai']) $waktu .= '–' . date('H:i', strtotime($r['waktu_selesai']));
        }
        $fromContent = ! empty($r['content_item_id']);
    ?>
    <tr class="<?= $fromContent ? 'from-content' : '' ?>">
        <td class="nomor"><?= $no ?></td>
        <td style="font-weight:600;color:var(--navy-2);white-space:nowrap"><?= $waktu ?: '—' ?></td>
        <td>
            <div style="font-weight:600;color:var(--tinta)"><?= esc($r['sesi']) ?></div>
            <?php if ($fromContent): ?><div class="from-content-tag">Content Event</div><?php endif; ?>
        </td>
        <td><?= esc(($r['deskripsi'] ?? '') ?: '') ?></td>
        <td><?= esc($r['pic'] ?: '—') ?></td>
        <td><?= esc($r['lokasi'] ?: '—') ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    </table>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- ══ 2. DEKORASI / VM ══ -->
<div class="section">
    <div class="sec-title"><span>Dekorasi / Visual Merchandising</span>
        <span class="sec-sub"><span class="lencana netral"><?= count($vmItems) ?> item</span></span></div>
    <?php if (empty($vmItems)): ?>
    <div class="empty">Belum ada data dekorasi.</div>
    <?php else: ?>
    <table class="main-table">
    <thead>
        <tr>
            <th style="width:28px">#</th>
            <th style="width:28%">Item</th>
            <th>Deskripsi / Referensi</th>
            <th style="width:110px;text-align:right">Budget</th>
            <th style="width:140px">Catatan</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($vmItems as $i => $vm): ?>
    <tr>
        <td class="nomor"><?= $i + 1 ?></td>
        <td style="font-weight:600;color:var(--tinta)"><?= esc($vm['nama_item']) ?></td>
        <td style="white-space:pre-line"><?= esc($vm['deskripsi_referensi'] ?: '—') ?></td>
        <td class="num" style="font-weight:600;color:var(--navy-2)">
            <?= $vm['budget'] > 0 ? 'Rp ' . number_format($vm['budget'], 0, ',', '.') : '—' ?>
        </td>
        <td style="color:var(--redup)"><?= esc($vm['catatan'] ?: '—') ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    <?php $vmTotal = array_sum(array_column($vmItems, 'budget')); if ($vmTotal > 0): ?>
    <tfoot>
        <tr>
            <td colspan="3" style="text-align:right">Total Budget</td>
            <td class="num">Rp <?= number_format($vmTotal, 0, ',', '.') ?></td>
            <td></td>
        </tr>
    </tfoot>
    <?php endif; ?>
    </table>
    <?php endif; ?>
</div>

<!-- ══ 3. EXHIBITION ══ -->
<div class="section">
    <?php $exTotal = array_sum(array_column(array_merge(...array_values($exhibitorsByKat ?: [[]])), 'nilai_dealing')); ?>
    <div class="sec-title"><span>Exhibition by Casual Leasing</span>
        <span class="sec-sub"><span class="lencana netral"><?= array_sum(array_map('count', $exhibitorsByKat)) ?> exhibitor</span></span></div>
    <?php if (empty($exhibitorsByKat)): ?>
    <div class="empty">Belum ada data exhibition.</div>
    <?php else: ?>
    <?php foreach ($exhibitorsByKat as $kat => $exList): ?>
    <div class="sub-header"><span><?= esc($kat) ?> <span class="ket">(<?= count($exList) ?>)</span></span></div>
    <table class="main-table">
    <thead>
        <tr>
            <th style="width:28px">#</th>
            <th style="width:20%">Booth</th>
            <th style="width:28%">Nama Exhibitor</th>
            <th>Program</th>
            <th style="width:120px;text-align:right">Nilai Dealing</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($exList as $i => $ex):
        $exProgs = $progsByExhibitor[$ex['id']] ?? [];
    ?>
    <tr>
        <td class="nomor"><?= $i + 1 ?></td>
        <td><?= esc($ex['lokasi_booth'] ?: '—') ?></td>
        <td style="font-weight:600;color:var(--tinta)"><?= esc($ex['nama_exhibitor']) ?></td>
        <td style="font-size:9.5px">
            <?php if (empty($exProgs)): ?>
            <span style="color:var(--redup2)">—</span>
            <?php else: ?>
            <?php foreach ($exProgs as $p):
                $jam = '';
                if ($p['jam_mulai'])   $jam  = substr($p['jam_mulai'], 0, 5);
                if ($p['jam_selesai']) $jam .= '–' . substr($p['jam_selesai'], 0, 5);
                $periode = '';
                if ($p['tanggal_mulai']) {
                    $periode = date('d/m', strtotime($p['tanggal_mulai']));
                    if ($p['tanggal_selesai'] && $p['tanggal_selesai'] !== $p['tanggal_mulai'])
                        $periode .= '–' . date('d/m', strtotime($p['tanggal_selesai']));
                }
            ?>
            <div style="margin-bottom:1px">
                • <?= esc($p['nama_program']) ?>
                <?php if ($periode || $jam): ?>
                <span style="color:#1d4ed8;font-weight:600"> <?= trim($periode . ' ' . $jam) ?></span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </td>
        <td class="num text-green" style="font-weight:600">
            Rp <?= number_format($ex['nilai_dealing'], 0, ',', '.') ?>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    </table>
    <?php endforeach; ?>
    <?php if ($exTotal > 0): ?>
    <table class="main-table tanpa-kosong">
    <tfoot>
        <tr>
            <td colspan="4" style="text-align:right">Total Dealing</td>
            <td class="num" style="width:140px">Rp <?= number_format($exTotal, 0, ',', '.') ?></td>
        </tr>
    </tfoot>
    </table>
    <?php endif; ?>
    <?php endif; ?>
</div>

<!-- ══ 4. PROGRAM LOYALTY ══ -->
<div class="section">
    <div class="sec-title"><span>Program Loyalty</span>
        <span class="sec-sub"><span class="lencana netral"><?= count($programs) ?> program</span></span></div>
    <?php if (empty($programs)): ?>
    <div class="empty">Belum ada program loyalty.</div>
    <?php else: ?>
    <?php foreach ($programs as $pr):
        $pid      = $pr['id'];
        $vouchers = $voucherItems[$pid] ?? [];
        $hadiahList = $hadiahItems[$pid] ?? [];
    ?>
    <div class="program-block">
        <div class="program-name">
            <span><?= esc($pr['nama_program']) ?></span>
            <?php if ($pr['target_peserta'] > 0): ?>
            <span class="ket">Target: <b><?= number_format($pr['target_peserta']) ?></b> peserta</span>
            <?php endif; ?>
        </div>
        <div class="program-body">
            <?php if ($pr['deskripsi'] ?? null): ?>
            <div class="program-desk"><?= esc($pr['deskripsi']) ?></div>
            <?php endif; ?>

            <?php if (!empty($vouchers)): ?>
            <div class="program-section-label">e-Voucher</div>
            <table class="main-table">
            <thead>
                <tr>
                    <th>Nama Voucher</th>
                    <th style="width:110px;text-align:right">Nilai</th>
                    <th style="width:110px;text-align:right">Diterbitkan</th>
                    <th style="width:110px;text-align:right">Target Serap</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($vouchers as $v): ?>
            <tr>
                <td><?= esc($v['nama_voucher']) ?></td>
                <td class="num" style="font-weight:600">Rp <?= number_format($v['nilai_voucher'], 0, ',', '.') ?></td>
                <td class="num"><?= number_format($v['total_diterbitkan']) ?> pcs</td>
                <td class="num text-blue"><?= ($v['target_penyerapan'] !== null && $v['target_penyerapan'] !== '') ? (float)$v['target_penyerapan'] . '%' : '—' ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
            </table>
            <?php endif; ?>

            <?php if (!empty($hadiahList)): ?>
            <div class="program-section-label" style="margin-top:8px">Hadiah</div>
            <table class="main-table">
            <thead>
                <tr>
                    <th>Nama Hadiah</th>
                    <th style="width:90px;text-align:right">Stok</th>
                    <th style="width:120px;text-align:right">Nilai Satuan</th>
                    <th style="width:130px;text-align:right">Total Nilai</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($hadiahList as $h): ?>
            <tr>
                <td><?= esc($h['nama_hadiah']) ?></td>
                <td class="num"><?= number_format($h['stok']) ?> pcs</td>
                <td class="num">Rp <?= number_format($h['nilai_satuan'], 0, ',', '.') ?></td>
                <td class="num" style="font-weight:600">Rp <?= number_format($h['stok'] * $h['nilai_satuan'], 0, ',', '.') ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
            </table>
            <?php endif; ?>

            <?php if (empty($vouchers) && empty($hadiahList)): ?>
            <div class="empty kecil">Belum ada detail voucher/hadiah.</div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- ══ 5. SPONSORSHIP ══ -->
<div class="section">
    <div class="sec-title"><span>Sponsorship</span>
        <span class="sec-sub"><span class="lencana netral"><?= count($sponsors) ?> sponsor</span></span></div>
    <?php if (empty($sponsors)): ?>
    <div class="empty">Belum ada data sponsor.</div>
    <?php else: ?>
    <table class="main-table">
    <thead>
        <tr>
            <th style="width:28px">#</th>
            <th style="width:30%">Nama Sponsor</th>
            <th style="width:70px;text-align:center">Jenis</th>
            <th>Detail / Item</th>
            <th style="width:120px;text-align:right">Nilai</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($sponsors as $i => $sp):
        $spItems = $itemsBySponsors[$sp['id']] ?? [];
    ?>
    <tr>
        <td class="nomor"><?= $i + 1 ?></td>
        <td style="font-weight:600;color:var(--tinta)"><?= esc($sp['nama_sponsor']) ?></td>
        <td style="text-align:center">
            <span class="lencana <?= $sp['jenis'] === 'cash' ? 'baik' : 'waspada' ?>"><?= $sp['jenis'] === 'cash' ? 'Cash' : 'In-Kind' ?></span>
        </td>
        <td style="font-size:9.5px">
            <?php if ($sp['jenis'] === 'barang' && !empty($spItems)): ?>
            <?php foreach ($spItems as $si): ?>
            <div>• <?= esc($si['deskripsi_barang'] ?: '—') ?><?= $si['qty'] ? ' · ' . number_format($si['qty']) . ' pcs' : '' ?></div>
            <?php endforeach; ?>
            <?php elseif ($sp['deskripsi'] ?? null): ?>
            <span><?= esc($sp['deskripsi']) ?></span>
            <?php else: ?>
            <span style="color:var(--redup2)">—</span>
            <?php endif; ?>
        </td>
        <td class="num" style="font-weight:600">
            <?php if ($sp['jenis'] === 'cash'): ?>
            <span class="text-green">Rp <?= number_format($sp['nilai'], 0, ',', '.') ?></span>
            <?php else: ?>
            <span class="text-yellow"><?= number_format(array_sum(array_column($spItems, 'qty'))) ?> pcs</span>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot>
        <?php if ($totalCash > 0): ?>
        <tr>
            <td colspan="4" style="text-align:right">Total Cash</td>
            <td class="num text-green">Rp <?= number_format($totalCash, 0, ',', '.') ?></td>
        </tr>
        <?php endif; ?>
        <?php if ($totalInKind > 0): ?>
        <tr>
            <td colspan="4" style="text-align:right">Total In-Kind</td>
            <td class="num text-yellow"><?= number_format($totalInKind) ?> pcs</td>
        </tr>
        <?php endif; ?>
    </tfoot>
    </table>
    <?php endif; ?>
</div>

<!-- ══ 6. CREATIVE & DESIGN ══ -->
<div class="section">
    <div class="sec-title"><span>Creative, Concept &amp; Design</span>
        <span class="sec-sub"><span class="lencana netral"><?= count($creativeItems) ?> item</span></span></div>
    <?php if (empty($creativeItems)): ?>
    <div class="empty">Belum ada data creative &amp; design.</div>
    <?php else: ?>
    <?php foreach ($tipeLabels as $tipe => $tipeLabel):
        $tipeItems = $byTipe[$tipe] ?? [];
        if (empty($tipeItems)) continue;
    ?>
    <div class="tipe-group-header"><span><?= $tipeLabel ?> <span class="ket">(<?= count($tipeItems) ?>)</span></span></div>
    <table class="main-table">
    <thead>
        <tr>
            <th style="width:28px">#</th>
            <th style="width:30%">Nama</th>
            <?php if ($tipe === 'digital'): ?>
            <th style="width:90px">Platform</th>
            <th style="width:100px">Tanggal Take</th>
            <th style="width:90px">PIC</th>
            <?php elseif ($tipe === 'master_design'): ?>
            <th style="width:90px">Status</th>
            <?php else: ?>
            <th style="width:120px;text-align:right">Budget</th>
            <?php endif; ?>
            <th>Deskripsi / Catatan</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($tipeItems as $i => $ci): ?>
    <tr>
        <td class="nomor"><?= $i + 1 ?></td>
        <td style="font-weight:600;color:var(--tinta)"><?= esc($ci['nama']) ?></td>
        <?php if ($tipe === 'digital'): ?>
        <td>
            <?php if ($ci['platform']): ?>
            <span class="lencana info"><?= $platformLbl[$ci['platform']] ?? $ci['platform'] ?></span>
            <?php else: ?>—<?php endif; ?>
        </td>
        <td style="white-space:nowrap">
            <?php if ($ci['tanggal_take']): ?>
            <?= date('d M Y', strtotime($ci['tanggal_take'])) ?>
            <?php if ($ci['jam_take']): ?> <?= substr($ci['jam_take'], 0, 5) ?><?php endif; ?>
            <?php else: ?>—<?php endif; ?>
        </td>
        <td style="color:var(--redup)"><?= esc($ci['pic'] ?: '—') ?></td>
        <?php elseif ($tipe === 'master_design'): ?>
        <td>
            <span class="lencana <?= $statusClass[$ci['status']] ?? 'netral' ?>"><?= $statusLabel[$ci['status']] ?? $ci['status'] ?></span>
        </td>
        <?php else: ?>
        <td class="num" style="font-weight:600;color:var(--navy-2)">
            <?= $ci['budget'] > 0 ? 'Rp ' . number_format($ci['budget'], 0, ',', '.') : '—' ?>
        </td>
        <?php endif; ?>
        <td style="color:var(--redup)"><?= esc(($ci['deskripsi'] ?? null) ?: (($ci['catatan'] ?? null) ?: '—')) ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    </table>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- ══ KAKI ══ -->
<div class="doc-footer">
    <span>Technical Meeting &mdash; <?= esc($event['name']) ?></span>
    <span>Mall Intelligence Center</span>
    <span>Dicetak: <?= tgl_indo(date('Y-m-d H:i:s'), true) ?></span>
</div>

<script>
// Auto-print jika ada parameter ?print=1
if (new URLSearchParams(window.location.search).get('print') === '1') window.print();
</script>
</body>
</html>
