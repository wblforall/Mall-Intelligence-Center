<?php
/** Fungsi bantu tampilan Progress Report (hanya format/penilaian tampilan; tanpa query, tanpa ubah angka). */
if (! function_exists('wrAgo')) {
    function wrAgo(?int $d): string
    {
        if ($d === null) return 'belum pernah';
        if ($d <= 0) return 'hari ini';
        if ($d === 1) return 'kemarin';
        return $d . ' hari lalu';
    }
}
if (! function_exists('wrMeta')) {
    /** Turunan tampilan satu program: status, keterlambatan, usang, alasan "perlu perhatian". */
    function wrMeta(array $item, int $gmUnread = 0, int $deptUnread = 0): array
    {
        $st        = $item['latest_status'] ?? null;
        $closed    = in_array($st, ['done', 'cancelled'], true);
        $overdue   = ! empty($item['target_selesai']) && $item['target_selesai'] < date('Y-m-d') && ! $closed;
        $overDays  = $overdue ? (int) floor((strtotime('today') - strtotime($item['target_selesai'])) / 86400) : 0;
        $leftDays  = (! empty($item['target_selesai']) && ! $overdue)
            ? (int) floor((strtotime($item['target_selesai']) - strtotime('today')) / 86400) : null;
        $noUpdate  = empty($item['latest_updated_at']);
        $updDays   = $noUpdate ? null : (int) floor((time() - strtotime($item['latest_updated_at'])) / 86400);
        $stale     = ! $noUpdate && ! $closed && $updDays >= 14;
        $flagged   = (int) ($item['is_flagged'] ?? 0) > 0;
        $reasons   = [];
        if ($gmUnread > 0)   $reasons[] = ['bad', 'bi-chat-left-text-fill', 'Pesan GM belum dibalas', 5];
        if ($deptUnread > 0) $reasons[] = ['blue', 'bi-reply-fill', 'Balasan Dept baru', 4];
        if ($overdue)        $reasons[] = ['bad', 'bi-alarm-fill', 'Terlambat ' . $overDays . ' hari', 4];
        if ($st === 'delayed') $reasons[] = ['bad', 'bi-x-octagon-fill', 'Delayed', 4];
        if ($st === 'at_risk') $reasons[] = ['warn', 'bi-exclamation-triangle-fill', 'At Risk', 3];
        if ($noUpdate)       $reasons[] = ['warn', 'bi-hourglass-split', 'Belum pernah update', 3];
        if ($stale)          $reasons[] = ['warn', 'bi-hourglass-split', $updDays . ' hari tanpa update', 2];
        if ($flagged)        $reasons[] = ['gold', 'bi-flag-fill', 'Di-flag ke GM', 1];
        $score = 0; $att = false; foreach ($reasons as $r) { $score += $r[3]; if ($r[3] >= 3 || $r[3] === 1) $att = true; }
        return compact('st', 'closed', 'overdue', 'overDays', 'leftDays', 'noUpdate', 'updDays', 'stale', 'flagged', 'reasons', 'score')
            + ['att' => $att, 'gm' => $gmUnread, 'dept' => $deptUnread];
    }
}
if (! function_exists('wrBarCls')) {
    function wrBarCls(?int $p): string { return $p === null ? '' : ($p >= 100 ? 'ok' : ($p >= 60 ? 'mid' : 'low')); }
}
