<?php

namespace App\Libraries;

/**
 * Konstanta & kalkulasi terpusat modul Appraisal.
 * Semua perhitungan skor HARUS lewat sini (jangan dihitung ulang di controller/view).
 */
class AppraisalConfig
{
    // Area Kinerja Utama (baku) — slug => label
    const AREAS = [
        'pencapaian_target' => 'Pencapaian Target Pekerjaan',
        'program_kerja'     => 'Program Kerja & Pelatihan',
        'metode_kerja'      => 'Metode Kerja & Program Improvisasi',
        'pelaporan'         => 'Pelaporan & Pertanggungjawaban Pekerjaan',
    ];

    // Unit pengukur — slug => label
    const UNITS = [
        'persen'       => '%',
        'jumlah_nilai' => 'Jumlah Nilai',
        'minggu'       => 'Minggu',
        'bulan'        => 'Bulan',
    ];

    // Bobot final (disepakati fix)
    const BOBOT_KPI         = 0.60;
    const BOBOT_KOMPETENSI  = 0.40;

    // Skala kompetensi 1-5
    const SKALA = [
        5 => 'Excellent',
        4 => 'Good Performance',
        3 => 'Standard Performance',
        2 => 'Need Improvement',
        1 => 'Unacceptable',
    ];

    // 5 aspek kompetensi default (dari format PENILAIAN KPI) — bisa diubah per template
    const DEFAULT_KOMPETENSI = [
        ['nama_aspek' => 'Disiplin dan Taat pada Peraturan Perusahaan',
         'deskripsi'  => 'Baik, jujur, disiplin dalam bekerja, dibuktikan perilaku nyata, menjunjung tinggi etos kerja, menjadi panutan bagi tim.'],
        ['nama_aspek' => 'Quality Orientation',
         'deskripsi'  => 'Kecakapan mengerjakan tugas dengan tuntas, tepat waktu, dengan mutu prima atau di atas standar yang ditetapkan.'],
        ['nama_aspek' => 'Problem Solving Skills',
         'deskripsi'  => 'Kecakapan menganalisa masalah, mengidentifikasi sumber penyebab, dan merumuskan alternatif solusi yang relevan dan aplikatif.'],
        ['nama_aspek' => 'Planning Skills',
         'deskripsi'  => 'Kecakapan menyusun rencana kerja sistematis dan terjadwal, alokasi sumber daya, serta monitoring agar rencana berjalan efektif.'],
        ['nama_aspek' => 'Teamwork and Communications',
         'deskripsi'  => 'Kecakapan koordinasi dan komunikasi dengan berbagai pihak, merumuskan tujuan bersama, berbagi tugas, dan saling menghargai pendapat.'],
    ];

    public static function areaLabel(string $slug): string
    {
        return self::AREAS[$slug] ?? $slug;
    }

    public static function unitLabel(string $slug): string
    {
        return self::UNITS[$slug] ?? $slug;
    }

    /**
     * Total Skor KPI = Σ (bobot × skor/100). Bobot Σ=100 → hasil skala 0-100.
     * $items: array baris dengan 'bobot' dan 'skor' (skor null diabaikan).
     */
    public static function skorKpi(array $items): ?float
    {
        $any = false; $total = 0.0;
        foreach ($items as $it) {
            if (($it['skor'] ?? null) === null || $it['skor'] === '') continue;
            $any = true;
            $total += (float) $it['bobot'] * ((float) $it['skor'] / 100);
        }
        return $any ? round($total, 2) : null;
    }

    /**
     * Skor Kompetensi = rata-rata nilai (1-5) × 20 → skala 0-100.
     * $values: array nilai 1-5 (null diabaikan).
     */
    public static function skorKompetensi(array $values): ?float
    {
        $filled = array_filter($values, fn($v) => $v !== null && $v !== '');
        if (! $filled) return null;
        $avg = array_sum($filled) / count($filled);
        return round($avg * 20, 2);
    }

    /** Nilai akhir = KPI×bobotKpi + Kompetensi×bobotKomp. */
    public static function nilaiAkhir(?float $kpi, ?float $komp, float $bobotKpi, float $bobotKomp): ?float
    {
        if ($kpi === null && $komp === null) return null;
        return round(((float) $kpi) * $bobotKpi + ((float) $komp) * $bobotKomp, 2);
    }

    /** Apakah semua item KPI sudah dinilai (skor terisi). */
    public static function kpiComplete(array $items): bool
    {
        foreach ($items as $it) {
            if (($it['skor'] ?? null) === null || $it['skor'] === '') return false;
        }
        return ! empty($items);
    }

    public static function kompetensiComplete(array $rows): bool
    {
        foreach ($rows as $r) {
            if (($r['nilai'] ?? null) === null || $r['nilai'] === '') return false;
        }
        return ! empty($rows);
    }
}
