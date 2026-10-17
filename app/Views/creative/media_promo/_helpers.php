<?php
/** Pembantu tampilan Media Promo (aman di-include berulang). */
if (! function_exists('mpDate')) {
    function mpDate(?string $d): string { return $d ? date('d M Y', strtotime($d)) : '—'; }
}
if (! function_exists('mpRange')) {
    function mpRange(string $a, string $b): string {
        return date('d M', strtotime($a)) . ' – ' . date('d M Y', strtotime($b));
    }
}
/** Selisih hari kalender: tanggal - hari ini. */
if (! function_exists('mpDays')) {
    function mpDays(string $d, string $today): int { return (int) round((strtotime($d) - strtotime($today)) / 86400); }
}
/** Tenggat: [teks, kelas chip] — berakhir / mulai relatif terhadap hari ini. */
if (! function_exists('mpDue')) {
    function mpDue(array $u, string $today): array {
        $s = mpDays($u['tanggal_selesai'], $today);
        $m = mpDays($u['tanggal_mulai'], $today);
        if ($s < 0) return ['Sudah berakhir', 'mute'];
        if ($m > 0) return ['Mulai ' . $m . ' hari lagi', 'blue'];
        if ($s === 0) return ['Berakhir hari ini', 'bad'];
        if ($s <= 7) return ['Berakhir ' . $s . ' hari lagi', 'warn'];
        return ['Berakhir ' . $s . ' hari lagi', 'mute'];
    }
}
if (! function_exists('mpTipeLabel')) {
    function mpTipeLabel(string $t): string {
        return ['t_banner' => 'T-Banner', 'hanging' => 'Hanging Banner', 'sticker_lift' => 'Sticker Lift', 'totem_stainless' => 'Totem Stainless', 'digital' => 'Digital'][$t] ?? $t;
    }
}
if (! function_exists('mpStatusMeta')) {
    /** status usage -> [label, kelas chip] */
    function mpStatusMeta(string $s): array {
        return [
            'draft' => ['Draft', 'blue'], 'pending' => ['Pending', 'warn'], 'approved' => ['Approved', 'ok'],
            'rejected' => ['Ditolak', 'bad'], 'done' => ['Selesai', 'mute'],
        ][$s] ?? [ucfirst($s), 'mute'];
    }
}
if (! function_exists('mpSumberLabel')) {
    function mpSumberLabel(?string $s): string { return ['internal' => 'Internal', 'tenant' => 'Tenant', 'external' => 'External'][$s ?? 'internal'] ?? (string) $s; }
}

/** Lencana kecil untuk pemesanan yang dibuat dari modul Creative (kolom creative_ref terisi). */
if (! function_exists('mpDariCreative')) {
    function mpDariCreative(array $u): string {
        if (empty($u['creative_ref'])) return '';
        return '<span class="mp-chip mp-chip-blue" title="Pemesanan dibuat dari item Creative: Media Cetak atau Konten Digital (' . esc((string) $u['creative_ref'], 'attr') . ')"><i class="bi bi-palette"></i>dari Creative</span>';
    }
}
