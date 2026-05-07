<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Technical Meeting — <?= esc($event['name']) ?></title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 10.5pt; color: #1a1a1a; background: #fff; }
.page { max-width: 960px; margin: 0 auto; padding: 28px 36px; }

/* Document header */
.doc-header { border-bottom: 3px solid #1e3a5f; padding-bottom: 14px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: flex-end; }
.doc-title   { font-size: 20pt; font-weight: 700; color: #1e3a5f; }
.doc-sub     { font-size: 10pt; color: #555; margin-top: 4px; }
.doc-badge   { font-size: 8pt; background: #1e3a5f; color: #fff; padding: 4px 10px; border-radius: 4px; white-space: nowrap; }
.doc-meta    { display: flex; flex-wrap: wrap; gap: 18px; margin-top: 8px; }
.doc-meta span { font-size: 9pt; color: #555; }
.doc-meta span strong { color: #1e3a5f; }

/* Section header */
.section { margin-top: 28px; }
.section-header {
    background: #1e3a5f; color: #fff;
    padding: 7px 14px; font-size: 11pt; font-weight: 700;
    letter-spacing: .03em; display: flex; justify-content: space-between; align-items: center;
    page-break-after: avoid;
}
.section-header .badge { background: rgba(255,255,255,.2); font-size: 8pt; padding: 2px 8px; border-radius: 3px; font-weight: 600; }

/* Sub-section */
.sub-header { background: #e8eef5; color: #1e3a5f; padding: 5px 12px; font-size: 9.5pt; font-weight: 700; margin-top: 0; border-bottom: 1px solid #c5d3e0; page-break-after: avoid; }

/* Tables */
table { width: 100%; border-collapse: collapse; font-size: 9.5pt; }
thead th { background: #f0f4f8; color: #1e3a5f; font-size: 8.5pt; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; padding: 6px 10px; border-bottom: 2px solid #1e3a5f; text-align: left; }
tbody td { padding: 6px 10px; border-bottom: 1px solid #e8ecf0; vertical-align: top; }
tbody tr:nth-child(even) td { background: #f8fafc; }
tfoot td { background: #eef2f7; font-weight: 700; font-size: 9pt; padding: 6px 10px; border-top: 2px solid #1e3a5f; }

/* Rundown */
.day-header { background: #2d4e7f; color: #fff; padding: 5px 12px; font-size: 9.5pt; font-weight: 600; margin-top: 6px; page-break-after: avoid; }
.from-content-tag { font-size: 7pt; font-weight: 700; color: #2563eb; text-transform: uppercase; letter-spacing: .05em; }
tr.from-content td { background: #eef4ff !important; }

/* List items */
.list-item { padding: 8px 12px; border-bottom: 1px solid #e8ecf0; }
.list-item:last-child { border-bottom: none; }
.list-item .name { font-weight: 600; font-size: 10pt; }
.list-item .meta { font-size: 8.5pt; color: #666; margin-top: 2px; display: flex; flex-wrap: wrap; gap: 12px; }

/* Loyalty */
.program-block { border: 1px solid #d5dfe8; margin: 6px 0; border-radius: 3px; page-break-inside: avoid; }
.program-name { background: #f0f6ff; padding: 6px 12px; font-weight: 700; font-size: 10pt; border-bottom: 1px solid #d5dfe8; display: flex; justify-content: space-between; }
.program-body { padding: 6px 12px; }
.program-section-label { font-size: 7.5pt; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #888; margin: 6px 0 3px; }

/* Creative */
.tipe-group-header { background: #e8eef5; color: #1e3a5f; padding: 4px 12px; font-size: 9pt; font-weight: 700; border-bottom: 1px solid #c5d3e0; }

/* Status / badges */
.badge-status { display: inline-block; padding: 1px 7px; border-radius: 3px; font-size: 7.5pt; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; }
.badge-draft    { background: #e5e7eb; color: #6b7280; }
.badge-review   { background: #fef3c7; color: #b45309; }
.badge-approved { background: #d1fae5; color: #065f46; }
.badge-revision { background: #fee2e2; color: #991b1b; }
.badge-tipe     { background: #e0f2fe; color: #0369a1; font-size: 7.5pt; padding: 1px 6px; border-radius: 3px; font-weight: 600; }
.badge-platform { background: #cffafe; color: #0e7490; font-size: 7.5pt; padding: 1px 6px; border-radius: 3px; font-weight: 600; }

/* Sponsor */
.sponsor-cash   { background: #d1fae5; color: #065f46; }
.sponsor-inkind { background: #fef3c7; color: #92400e; }

/* Footer */
.doc-footer { margin-top: 28px; padding-top: 10px; border-top: 1px solid #ccc; display: flex; justify-content: space-between; font-size: 8.5pt; color: #888; }
.no-print { text-align: center; margin: 24px 0 0; }
.btn-print { background: #1e3a5f; color: #fff; border: none; padding: 10px 28px; font-size: 11pt; border-radius: 5px; cursor: pointer; }

/* Empty state */
.empty { color: #aaa; font-style: italic; padding: 10px 12px; font-size: 9pt; }

@media print {
    body { font-size: 9.5pt; }
    .page { padding: 0; max-width: 100%; }
    .no-print { display: none !important; }
    .section { page-break-inside: avoid; }
    thead { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .section-header, .day-header, .sub-header, .tipe-group-header, .program-name { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    tr.from-content td { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    tbody tr:nth-child(even) td { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    tfoot td { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
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
$tipeIcons   = ['master_design' => '✏️', 'digital' => '📱', 'cetak' => '🖨️', 'influencer' => '🎥', 'media_prescon' => '📰'];
$platformLbl = ['ig' => 'Instagram', 'tiktok' => 'TikTok', 'keduanya' => 'IG & TikTok'];
$statusClass = ['draft' => 'badge-draft', 'review' => 'badge-review', 'approved' => 'badge-approved', 'revision' => 'badge-revision'];
$statusLabel = ['draft' => 'Draft', 'review' => 'Review', 'approved' => 'Approved', 'revision' => 'Revisi'];

?>

<div class="page">

    <!-- Document Header -->
    <div class="doc-header">
        <div>
            <div class="doc-title"><?= esc($event['name']) ?></div>
            <div class="doc-meta">
                <span>📍 <strong><?= $mallLabels[$event['mall']] ?? esc($event['mall']) ?></strong></span>
                <?php if (!empty($eventLocations)): ?>
                <span>🏢 <strong><?= esc(implode(', ', array_column($eventLocations, 'nama'))) ?></strong></span>
                <?php endif; ?>
                <span>📅 <strong><?= $sameDay
                    ? date('l, d F Y', strtotime($startDate))
                    : date('l, d F Y', strtotime($startDate)) . ' – ' . date('l, d F Y', strtotime($endDate)) ?></strong></span>
                <span>⏱ <strong><?= $event['event_days'] ?> hari</strong></span>
                <?php if ($event['tema']): ?>
                <span>🎯 <strong><?= esc($event['tema']) ?></strong></span>
                <?php endif; ?>
            </div>
            <div class="doc-sub" style="margin-top:6px">Dokumen Technical Meeting</div>
        </div>
        <div style="text-align:right">
            <img src="<?= base_url('img/mic-logo.png') ?>" alt="MIC Logo" style="height:52px;object-fit:contain;margin-bottom:6px;display:block;margin-left:auto">
            <div class="doc-badge" style="display:inline-block">Dicetak: <?= date('d M Y, H:i') ?></div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════ -->
    <!-- 1. RUNDOWN                                  -->
    <!-- ═══════════════════════════════════════════ -->
    <div class="section">
        <div class="section-header">
            <span>1 &nbsp; Rundown</span>
            <span class="badge"><?= $event['event_days'] ?> Hari</span>
        </div>
        <?php if (empty($rundown)): ?>
        <div class="empty">Belum ada data rundown.</div>
        <?php else: ?>
        <?php foreach ($rundown as $hariKe => $rows):
            $tanggalHari = $rows[0]['tanggal'] ?? null;
        ?>
        <div class="day-header">
            Hari <?= $hariKe ?>
            <?php if ($tanggalHari): ?> — <?= date('l, d F Y', strtotime($tanggalHari)) ?><?php endif; ?>
        </div>
        <table>
        <thead>
            <tr>
                <th style="width:28px">#</th>
                <th style="width:110px">Waktu</th>
                <th style="width:28%">Sesi / Acara</th>
                <th>Deskripsi</th>
                <th style="width:110px">PIC</th>
                <th style="width:110px">Lokasi</th>
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
            <td style="text-align:center;color:#999;font-size:8.5pt"><?= $no ?></td>
            <td style="font-weight:600;color:#1e3a5f;white-space:nowrap"><?= $waktu ?: '—' ?></td>
            <td>
                <div style="font-weight:600"><?= esc($r['sesi']) ?></div>
                <?php if ($fromContent): ?><div class="from-content-tag">Content Event</div><?php endif; ?>
            </td>
            <td style="color:#444"><?= esc(($r['deskripsi'] ?? '') ?: '') ?></td>
            <td style="color:#555"><?= esc($r['pic'] ?: '—') ?></td>
            <td style="color:#555"><?= esc($r['lokasi'] ?: '—') ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        </table>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- ═══════════════════════════════════════════ -->
    <!-- 2. DEKORASI / VM                            -->
    <!-- ═══════════════════════════════════════════ -->
    <div class="section">
        <div class="section-header">
            <span>2 &nbsp; Dekorasi / Visual Merchandising</span>
            <span class="badge"><?= count($vmItems) ?> item</span>
        </div>
        <?php if (empty($vmItems)): ?>
        <div class="empty">Belum ada data dekorasi.</div>
        <?php else: ?>
        <table>
        <thead>
            <tr>
                <th style="width:28px">#</th>
                <th style="width:30%">Item</th>
                <th>Deskripsi / Referensi</th>
                <th style="width:120px;text-align:right">Budget</th>
                <th style="width:150px">Catatan</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($vmItems as $i => $vm): ?>
        <tr>
            <td style="text-align:center;color:#999;font-size:8.5pt"><?= $i + 1 ?></td>
            <td style="font-weight:600"><?= esc($vm['nama_item']) ?></td>
            <td style="color:#444;white-space:pre-line"><?= esc($vm['deskripsi_referensi'] ?: '—') ?></td>
            <td style="text-align:right;font-weight:600;color:#1e3a5f">
                <?= $vm['budget'] > 0 ? 'Rp ' . number_format($vm['budget'], 0, ',', '.') : '—' ?>
            </td>
            <td style="color:#666;font-size:9pt"><?= esc($vm['catatan'] ?: '—') ?></td>
        </tr>
        <?php endforeach; ?>
        <?php $vmTotal = array_sum(array_column($vmItems, 'budget')); if ($vmTotal > 0): ?>
        <tfoot>
            <tr>
                <td colspan="3" style="text-align:right">Total Budget</td>
                <td style="text-align:right">Rp <?= number_format($vmTotal, 0, ',', '.') ?></td>
                <td></td>
            </tr>
        </tfoot>
        <?php endif; ?>
        </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- ═══════════════════════════════════════════ -->
    <!-- 3. EXHIBITION                               -->
    <!-- ═══════════════════════════════════════════ -->
    <div class="section">
        <?php $exTotal = array_sum(array_column(array_merge(...array_values($exhibitorsByKat ?: [[]])), 'nilai_dealing')); ?>
        <div class="section-header">
            <span>3 &nbsp; Exhibition by Casual Leasing</span>
            <span class="badge"><?= array_sum(array_map('count', $exhibitorsByKat)) ?> exhibitor</span>
        </div>
        <?php if (empty($exhibitorsByKat)): ?>
        <div class="empty">Belum ada data exhibition.</div>
        <?php else: ?>
        <?php foreach ($exhibitorsByKat as $kat => $exList): ?>
        <div class="sub-header"><?= esc($kat) ?> <span style="font-weight:400;font-size:8.5pt">(<?= count($exList) ?>)</span></div>
        <table>
        <thead>
            <tr>
                <th style="width:28px">#</th>
                <th style="width:20%">Booth</th>
                <th style="width:30%">Nama Exhibitor</th>
                <th>Program</th>
                <th style="width:130px;text-align:right">Nilai Dealing</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($exList as $i => $ex):
            $exProgs = $progsByExhibitor[$ex['id']] ?? [];
        ?>
        <tr>
            <td style="text-align:center;color:#999;font-size:8.5pt"><?= $i + 1 ?></td>
            <td style="color:#666"><?= esc($ex['lokasi_booth'] ?: '—') ?></td>
            <td style="font-weight:600"><?= esc($ex['nama_exhibitor']) ?></td>
            <td style="font-size:9pt">
                <?php if (empty($exProgs)): ?>
                <span style="color:#aaa">—</span>
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
                    <span style="color:#2563eb;font-weight:600"> <?= trim($periode . ' ' . $jam) ?></span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </td>
            <td style="text-align:right;font-weight:600;color:#059669">
                Rp <?= number_format($ex['nilai_dealing'], 0, ',', '.') ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        </table>
        <?php endforeach; ?>
        <?php if ($exTotal > 0): ?>
        <table>
        <tfoot>
            <tr>
                <td colspan="4" style="text-align:right">Total Dealing</td>
                <td style="text-align:right">Rp <?= number_format($exTotal, 0, ',', '.') ?></td>
            </tr>
        </tfoot>
        </table>
        <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- ═══════════════════════════════════════════ -->
    <!-- 4. PROGRAM LOYALTY                          -->
    <!-- ═══════════════════════════════════════════ -->
    <div class="section">
        <div class="section-header">
            <span>4 &nbsp; Program Loyalty</span>
            <span class="badge"><?= count($programs) ?> program</span>
        </div>
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
                <span style="font-weight:400;font-size:9pt;color:#555">Target: <?= number_format($pr['target_peserta']) ?> peserta</span>
                <?php endif; ?>
            </div>
            <div class="program-body">
                <?php if ($pr['deskripsi'] ?? null): ?>
                <div style="font-size:9pt;color:#444;margin-bottom:6px"><?= esc($pr['deskripsi']) ?></div>
                <?php endif; ?>

                <?php if (!empty($vouchers)): ?>
                <div class="program-section-label">🎟 e-Voucher</div>
                <table>
                <thead>
                    <tr>
                        <th>Nama Voucher</th>
                        <th style="width:120px;text-align:right">Nilai</th>
                        <th style="width:120px;text-align:right">Diterbitkan</th>
                        <th style="width:120px;text-align:right">Target Serap</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($vouchers as $v): ?>
                <tr>
                    <td><?= esc($v['nama_voucher']) ?></td>
                    <td style="text-align:right;font-weight:600">Rp <?= number_format($v['nilai_voucher'], 0, ',', '.') ?></td>
                    <td style="text-align:right"><?= number_format($v['total_diterbitkan']) ?> pcs</td>
                    <td style="text-align:right;color:#2563eb"><?= ($v['target_penyerapan'] !== null && $v['target_penyerapan'] !== '') ? (float)$v['target_penyerapan'] . '%' : '—' ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
                </table>
                <?php endif; ?>

                <?php if (!empty($hadiahList)): ?>
                <div class="program-section-label" style="margin-top:8px">🎁 Hadiah</div>
                <table>
                <thead>
                    <tr>
                        <th>Nama Hadiah</th>
                        <th style="width:100px;text-align:right">Stok</th>
                        <th style="width:130px;text-align:right">Nilai Satuan</th>
                        <th style="width:140px;text-align:right">Total Nilai</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($hadiahList as $h): ?>
                <tr>
                    <td><?= esc($h['nama_hadiah']) ?></td>
                    <td style="text-align:right"><?= number_format($h['stok']) ?> pcs</td>
                    <td style="text-align:right">Rp <?= number_format($h['nilai_satuan'], 0, ',', '.') ?></td>
                    <td style="text-align:right;font-weight:600">Rp <?= number_format($h['stok'] * $h['nilai_satuan'], 0, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
                </table>
                <?php endif; ?>

                <?php if (empty($vouchers) && empty($hadiahList)): ?>
                <div style="font-size:9pt;color:#aaa;font-style:italic">Belum ada detail voucher/hadiah.</div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- ═══════════════════════════════════════════ -->
    <!-- 5. SPONSORSHIP                              -->
    <!-- ═══════════════════════════════════════════ -->
    <div class="section">
        <div class="section-header">
            <span>5 &nbsp; Sponsorship</span>
            <span class="badge"><?= count($sponsors) ?> sponsor</span>
        </div>
        <?php if (empty($sponsors)): ?>
        <div class="empty">Belum ada data sponsor.</div>
        <?php else: ?>
        <table>
        <thead>
            <tr>
                <th style="width:28px">#</th>
                <th style="width:30%">Nama Sponsor</th>
                <th style="width:80px;text-align:center">Jenis</th>
                <th>Detail / Item</th>
                <th style="width:130px;text-align:right">Nilai</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($sponsors as $i => $sp):
            $spItems = $itemsBySponsors[$sp['id']] ?? [];
        ?>
        <tr>
            <td style="text-align:center;color:#999;font-size:8.5pt"><?= $i + 1 ?></td>
            <td style="font-weight:600"><?= esc($sp['nama_sponsor']) ?></td>
            <td style="text-align:center">
                <span class="badge-status <?= $sp['jenis'] === 'cash' ? 'sponsor-cash' : 'sponsor-inkind' ?>">
                    <?= $sp['jenis'] === 'cash' ? 'Cash' : 'In-Kind' ?>
                </span>
            </td>
            <td style="font-size:9pt">
                <?php if ($sp['jenis'] === 'barang' && !empty($spItems)): ?>
                <?php foreach ($spItems as $si): ?>
                <div>• <?= esc($si['deskripsi_barang'] ?: '—') ?><?= $si['qty'] ? ' · ' . number_format($si['qty']) . ' pcs' : '' ?></div>
                <?php endforeach; ?>
                <?php elseif ($sp['deskripsi'] ?? null): ?>
                <span style="color:#555"><?= esc($sp['deskripsi']) ?></span>
                <?php else: ?>
                <span style="color:#aaa">—</span>
                <?php endif; ?>
            </td>
            <td style="text-align:right;font-weight:600">
                <?php if ($sp['jenis'] === 'cash'): ?>
                <span style="color:#059669">Rp <?= number_format($sp['nilai'], 0, ',', '.') ?></span>
                <?php else: ?>
                <span style="color:#b45309"><?= number_format(array_sum(array_column($spItems, 'qty'))) ?> pcs</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        <tfoot>
            <?php if ($totalCash > 0): ?>
            <tr>
                <td colspan="4" style="text-align:right">Total Cash</td>
                <td style="text-align:right;color:#059669">Rp <?= number_format($totalCash, 0, ',', '.') ?></td>
            </tr>
            <?php endif; ?>
            <?php if ($totalInKind > 0): ?>
            <tr>
                <td colspan="4" style="text-align:right">Total In-Kind</td>
                <td style="text-align:right;color:#b45309"><?= number_format($totalInKind) ?> pcs</td>
            </tr>
            <?php endif; ?>
        </tfoot>
        </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- ═══════════════════════════════════════════ -->
    <!-- 6. CREATIVE & DESIGN                        -->
    <!-- ═══════════════════════════════════════════ -->
    <div class="section">
        <div class="section-header">
            <span>6 &nbsp; Creative, Concept &amp; Design</span>
            <span class="badge"><?= count($creativeItems) ?> item</span>
        </div>
        <?php if (empty($creativeItems)): ?>
        <div class="empty">Belum ada data creative & design.</div>
        <?php else: ?>
        <?php foreach ($tipeLabels as $tipe => $tipeLabel):
            $tipeItems = $byTipe[$tipe] ?? [];
            if (empty($tipeItems)) continue;
        ?>
        <div class="tipe-group-header"><?= $tipeIcons[$tipe] ?? '' ?> <?= $tipeLabel ?> <span style="font-weight:400;font-size:8.5pt">(<?= count($tipeItems) ?>)</span></div>
        <table>
        <thead>
            <tr>
                <th style="width:28px">#</th>
                <th style="width:30%">Nama</th>
                <?php if ($tipe === 'digital'): ?>
                <th style="width:100px">Platform</th>
                <th style="width:120px">Tanggal Take</th>
                <th style="width:100px">PIC</th>
                <?php elseif ($tipe === 'master_design'): ?>
                <th style="width:100px">Status</th>
                <?php else: ?>
                <th style="width:130px;text-align:right">Budget</th>
                <?php endif; ?>
                <th>Deskripsi / Catatan</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($tipeItems as $i => $ci): ?>
        <tr>
            <td style="text-align:center;color:#999;font-size:8.5pt"><?= $i + 1 ?></td>
            <td style="font-weight:600"><?= esc($ci['nama']) ?></td>
            <?php if ($tipe === 'digital'): ?>
            <td>
                <?php if ($ci['platform']): ?>
                <span class="badge-platform"><?= $platformLbl[$ci['platform']] ?? $ci['platform'] ?></span>
                <?php else: ?>—<?php endif; ?>
            </td>
            <td style="font-size:9pt">
                <?php if ($ci['tanggal_take']): ?>
                <?= date('d M Y', strtotime($ci['tanggal_take'])) ?>
                <?php if ($ci['jam_take']): ?> <?= substr($ci['jam_take'], 0, 5) ?><?php endif; ?>
                <?php else: ?>—<?php endif; ?>
            </td>
            <td style="font-size:9pt;color:#555"><?= esc($ci['pic'] ?: '—') ?></td>
            <?php elseif ($tipe === 'master_design'): ?>
            <td>
                <span class="badge-status <?= $statusClass[$ci['status']] ?? 'badge-draft' ?>">
                    <?= $statusLabel[$ci['status']] ?? $ci['status'] ?>
                </span>
            </td>
            <?php else: ?>
            <td style="text-align:right;font-weight:600;color:#1e3a5f">
                <?= $ci['budget'] > 0 ? 'Rp ' . number_format($ci['budget'], 0, ',', '.') : '—' ?>
            </td>
            <?php endif; ?>
            <td style="font-size:9pt;color:#555"><?= esc(($ci['deskripsi'] ?? null) ?: (($ci['catatan'] ?? null) ?: '—')) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        </table>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <div class="doc-footer">
        <span>Dicetak: <?= date('d F Y, H:i') ?></span>
        <span>Technical Meeting — <?= esc($event['name']) ?></span>
        <span>Mall Intelligence Center</span>
    </div>

</div>

<div class="no-print">
    <button class="btn-print" onclick="window.print()">🖨️ Print / Save PDF</button>
    &nbsp;
    <a href="<?= base_url('events/'.$event['id'].'/summary') ?>" style="font-size:10pt;color:#666;margin-left:12px">← Kembali ke Summary</a>
</div>

<script>
// Auto-print jika ada parameter ?print=1
if (new URLSearchParams(window.location.search).get('print') === '1') window.print();
</script>
</body>
</html>
