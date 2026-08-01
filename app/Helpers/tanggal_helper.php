<?php

/**
 * Format tanggal berbahasa Indonesia untuk dokumen & laporan formal.
 * Sebelumnya array nama bulan diduplikasi di beberapa view laporan.
 */

if (! function_exists('badge_persetujuan')) {
    /**
     * Jumlah item menunggu keputusan user (badge sidebar), di-cache di session
     * 5 menit karena ApprovalInbox::collect() memakai s.d. 10 query.
     *
     * Memakai ApprovalInbox::contextForUser() — SATU implementasi aturan akses
     * yang sama dengan halaman /persetujuan & cron, sehingga angka badge tak
     * mungkin berbeda dari isi halamannya.
     */
    function badge_persetujuan(): int
    {
        $c = session()->get('appr_badge');
        if (is_array($c) && ($c['t'] ?? 0) > time() - 300) return (int) $c['n'];

        try {
            $ctx = \App\Libraries\ApprovalInbox::contextForUser((int) session()->get('user_id'));
            $n   = $ctx ? count(\App\Libraries\ApprovalInbox::collect($ctx)) : 0;
        } catch (\Throwable $e) {
            return 0; // tabel/relasi belum siap (mis. sebelum migrate)
        }
        session()->set('appr_badge', ['n' => $n, 't' => time()]);
        return $n;
    }
}

if (! function_exists('bulan_indo')) {
    /** Nama bulan Indonesia dari nomor bulan 1–12 (atau tanggal apa pun). */
    function bulan_indo(int|string $bulan): string
    {
        $nama = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        $n = is_numeric($bulan) ? (int) $bulan : (int) date('n', strtotime((string) $bulan));
        return $nama[$n] ?? (string) $bulan;
    }
}

if (! function_exists('tgl_indo')) {
    /**
     * Tanggal panjang Indonesia, mis. "2 Agustus 2026".
     * $withTime = true → "2 Agustus 2026, 14:30".
     */
    function tgl_indo(?string $tanggal, bool $withTime = false): string
    {
        if (! $tanggal) return '—';
        $ts = strtotime($tanggal);
        if (! $ts) return '—';
        $out = date('j', $ts) . ' ' . bulan_indo(date('n', $ts)) . ' ' . date('Y', $ts);
        return $withTime ? $out . ', ' . date('H:i', $ts) : $out;
    }
}

if (! function_exists('hari_indo')) {
    /** Nama hari Indonesia dari sebuah tanggal. */
    function hari_indo(?string $tanggal): string
    {
        if (! $tanggal) return '';
        $ts = strtotime($tanggal);
        if (! $ts) return '';
        $nama = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
                 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
        return $nama[date('l', $ts)] ?? date('l', $ts);
    }
}

if (! function_exists('tgl_indo_hari')) {
    /** Tanggal lengkap dengan nama hari, mis. "Sabtu, 2 Agustus 2026". */
    function tgl_indo_hari(?string $tanggal): string
    {
        if (! $tanggal || ! strtotime($tanggal)) return '—';
        return hari_indo($tanggal) . ', ' . tgl_indo($tanggal);
    }
}

if (! function_exists('tgl_indo_pendek')) {
    /** Bentuk ringkas untuk kolom tabel, mis. "Sab, 2 Agu 2026". */
    function tgl_indo_pendek(?string $tanggal, bool $withHari = true): string
    {
        if (! $tanggal || ! strtotime($tanggal)) return '—';
        $ts  = strtotime($tanggal);
        $bln = mb_substr(bulan_indo((int) date('n', $ts)), 0, 3);
        $out = date('j', $ts) . ' ' . $bln . ' ' . date('Y', $ts);
        return $withHari ? mb_substr(hari_indo($tanggal), 0, 3) . ', ' . $out : $out;
    }
}

if (! function_exists('periode_indo')) {
    /** Label periode "YYYY-MM" → "Agustus 2026". */
    function periode_indo(string $periode): string
    {
        [$th, $bl] = array_pad(explode('-', $periode), 2, '1');
        return bulan_indo((int) $bl) . ' ' . $th;
    }
}
