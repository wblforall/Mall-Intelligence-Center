<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Rundown — <?= esc($event['name']) ?></title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 11pt; color: #1a1a1a; background: #fff; }

/* Page layout */
.page { max-width: 900px; margin: 0 auto; padding: 28px 32px; }

/* Header */
.doc-header { border-bottom: 3px solid #1e3a5f; padding-bottom: 14px; margin-bottom: 20px; }
.doc-title { font-size: 18pt; font-weight: 700; color: #1e3a5f; letter-spacing: .01em; }
.doc-meta { display: flex; gap: 24px; margin-top: 6px; flex-wrap: wrap; }
.doc-meta span { font-size: 9.5pt; color: #555; display: flex; align-items: center; gap: 4px; }
.doc-meta span strong { color: #222; }

/* Day header */
.day-header { background: #1e3a5f; color: #fff; padding: 6px 12px; margin-top: 18px; margin-bottom: 0;
              font-size: 10pt; font-weight: 600; letter-spacing: .04em; text-transform: uppercase; }

/* Table */
table { width: 100%; border-collapse: collapse; }
thead th {
    background: #f0f4f8; color: #1e3a5f;
    font-size: 8.5pt; font-weight: 700; text-transform: uppercase; letter-spacing: .05em;
    padding: 7px 10px; border-bottom: 2px solid #1e3a5f; text-align: left;
}
tbody tr { border-bottom: 1px solid #e8ecf0; }
tbody tr:last-child { border-bottom: 2px solid #c8d4e0; }
tbody td { padding: 7px 10px; font-size: 10pt; vertical-align: top; }
tbody tr:nth-child(even) { background: #f8fafc; }

/* From-content badge row */
tbody tr.from-content { background: #eef4ff; }
tbody tr.from-content:nth-child(even) { background: #e6effe; }

.col-no    { width: 32px;  text-align: center; color: #888; font-size: 9pt; }
.col-time  { width: 110px; white-space: nowrap; font-weight: 600; color: #1e3a5f; }
.col-sesi  { width: 25%; }
.col-desk  { }
.col-pic   { width: 110px; color: #555; font-size: 9.5pt; }
.col-lok   { width: 110px; color: #555; font-size: 9.5pt; }

.sesi-name { font-weight: 600; }
.content-tag { font-size: 7.5pt; font-weight: 700; color: #2563eb; text-transform: uppercase; letter-spacing: .05em; }
.empty-row td { color: #aaa; font-style: italic; text-align: center; padding: 14px; }

/* Footer */
.doc-footer { margin-top: 24px; padding-top: 10px; border-top: 1px solid #ddd;
              display: flex; justify-content: space-between; font-size: 8.5pt; color: #888; }

@media print {
    body { font-size: 10pt; }
    .page { padding: 0; max-width: 100%; }
    .no-print { display: none !important; }
    thead { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .day-header { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    tbody tr.from-content { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    tbody tr:nth-child(even) { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}
</style>
</head>
<body>
<?php
$mallLabels = ['ewalk' => 'eWalk Simply FUNtastic', 'pentacity' => 'Pentacity Shopping Venue', 'keduanya' => 'eWalk Simply FUNtastic & Pentacity Shopping Venue'];
$startDate  = $event['start_date'];
$endDate    = date('Y-m-d', strtotime($startDate . ' +' . ($event['event_days'] - 1) . ' days'));
$sameDay    = $startDate === $endDate;
?>
<div class="page">

    <div class="doc-header">
        <div class="doc-title"><?= esc($event['name']) ?></div>
        <div class="doc-meta">
            <span>📍 <strong><?= $mallLabels[$event['mall']] ?? esc($event['mall']) ?></strong></span>
            <span>📅 <strong>
                <?= $sameDay
                    ? date('d F Y', strtotime($startDate))
                    : date('d', strtotime($startDate)) . '–' . date('d F Y', strtotime($endDate)) ?>
            </strong></span>
            <span>⏱ <strong><?= $event['event_days'] ?> hari</strong></span>
            <?php if ($event['tema']): ?>
            <span>🎯 <strong><?= esc($event['tema']) ?></strong></span>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($grouped)): ?>
    <p style="color:#aaa;text-align:center;padding:40px 0;font-style:italic">Belum ada data rundown.</p>
    <?php else: ?>
    <?php
    $no = 0;
    foreach ($grouped as $hariKe => $rows):
        $tanggalHari = $rows[0]['tanggal'] ?? null;
    ?>
    <div class="day-header">
        Hari <?= $hariKe ?>
        <?php if ($tanggalHari): ?> &mdash; <?= date('l, d F Y', strtotime($tanggalHari)) ?><?php endif; ?>
    </div>
    <table>
    <thead>
    <tr>
        <th class="col-no">#</th>
        <th class="col-time">Waktu</th>
        <th class="col-sesi">Sesi / Acara</th>
        <th class="col-desk">Deskripsi</th>
        <th class="col-pic">PIC</th>
        <th class="col-lok">Lokasi</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $r):
        $no++;
        $fromContent = ! empty($r['content_item_id']);
        $waktu = '';
        if ($r['waktu_mulai']) {
            $waktu = date('H:i', strtotime($r['waktu_mulai']));
            if ($r['waktu_selesai']) $waktu .= '–' . date('H:i', strtotime($r['waktu_selesai']));
        }
    ?>
    <tr class="<?= $fromContent ? 'from-content' : '' ?>">
        <td class="col-no"><?= $no ?></td>
        <td class="col-time"><?= $waktu ?: '—' ?></td>
        <td class="col-sesi">
            <div class="sesi-name"><?= esc($r['sesi']) ?></div>
            <?php if ($fromContent): ?><div class="content-tag">Content Event</div><?php endif; ?>
        </td>
        <td><?= esc($r['deskripsi'] ?: '') ?></td>
        <td class="col-pic"><?= esc($r['pic'] ?: '—') ?></td>
        <td class="col-lok"><?= esc($r['lokasi'] ?: '—') ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    </table>
    <?php endforeach; ?>
    <?php endif; ?>

    <div class="doc-footer">
        <span>Dicetak: <?= date('d F Y, H:i') ?></span>
        <span>Mall Intelligence Center</span>
    </div>

</div>

<script>window.onload = function() { window.print(); }</script>
</body>
</html>
