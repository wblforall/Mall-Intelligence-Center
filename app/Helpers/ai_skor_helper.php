<?php

use App\Libraries\AiSkorPrompt;

/**
 * Pembantu tampilan skor mutu prompt (Pemantauan AI).
 * Warna sengaja netral-hangat (bukan merah) agar tidak terasa menghakimi.
 */

if (! function_exists('skor_kelas')) {
    /** Kelas badge Bootstrap per kunci tingkat. */
    function skor_kelas(string $kunciTingkat): string
    {
        return [
            'perlu_dilatih' => 'bg-warning-subtle text-warning-emphasis',
            'cukup'         => 'bg-info-subtle text-info',
            'baik'          => 'bg-success-subtle text-success',
            'sangat_baik'   => 'bg-primary-subtle text-primary',
        ][$kunciTingkat] ?? 'bg-secondary-subtle text-secondary';
    }
}

if (! function_exists('skor_status_sesi')) {
    /**
     * Status penilaian satu sesi untuk tampilan:
     *  'dinilai' | 'pribadi' | 'belum' | 'tidak' (tak layak: <2 prompt/lanjutan).
     */
    function skor_status_sesi(array $s): string
    {
        if (($s['klasifikasi_kantor'] ?? null) === 'pribadi') return 'pribadi';
        if (($s['skor_metode'] ?? null) === 'llm' && $s['skor_prompt'] !== null) return 'dinilai';
        if (($s['skor_metode'] ?? null) === 'lewati' || (int) ($s['jml_prompt'] ?? 0) < AiSkorPrompt::MIN_PROMPT_MANUSIA) return 'tidak';
        return 'belum';
    }
}

if (! function_exists('skor_badge')) {
    /** Badge ringkas (HTML aman) untuk satu sesi. */
    function skor_badge(array $s): string
    {
        switch (skor_status_sesi($s)) {
            case 'dinilai':
                [$kunci, $label] = AiSkorPrompt::tingkat((int) $s['skor_prompt']);
                $tj = ($s['klasifikasi_kantor'] ?? '') === 'tak_jelas'
                    ? ' <span class="badge bg-secondary-subtle text-secondary" title="Konteks sesi tidak jelas (kantor/pribadi) — dinilai dengan tanda">tak jelas</span>' : '';
                $mdl = ! empty($s['skor_model']) ? ' (model: ' . $s['skor_model'] . ')' : '';
                return '<span class="badge ' . skor_kelas($kunci) . '" title="' . esc('Dinilai otomatis oleh AI' . $mdl) . '">'
                    . (int) $s['skor_prompt'] . ' &middot; ' . esc($label) . '</span>' . $tj;
            case 'pribadi':
                return '<span class="small text-muted" title="Sesi pribadi tidak pernah dinilai">Tidak dinilai (pribadi)</span>';
            case 'tidak':
                return '<span class="small text-muted" title="Kurang dari 2 prompt instruksi">Tidak dinilai</span>';
            default:
                return '<span class="small text-muted fst-italic" title="Menunggu penilaian AI; dicoba lagi otomatis">Belum dinilai</span>';
        }
    }
}

if (! function_exists('skor_rincian_html')) {
    /** Detail yang bisa dibuka: saran + 5 dimensi. Kosong bila sesi belum dinilai. */
    function skor_rincian_html(array $s): string
    {
        if (skor_status_sesi($s) !== 'dinilai') return '';
        $rin = json_decode((string) ($s['skor_rincian'] ?? ''), true) ?: [];
        $h = '<details class="mt-1"><summary class="small text-muted" style="cursor:pointer">Saran &amp; rincian</summary><div class="small mt-1">';
        if (! empty($s['skor_saran'])) {
            $h .= '<div class="mb-2"><i class="bi bi-lightbulb me-1 text-warning"></i>' . esc($s['skor_saran']) . '</div>';
        }
        if (! empty($s['skor_model'])) {
            $h .= '<div class="mb-2 text-muted"><i class="bi bi-cpu me-1"></i>Model penilai: <code>' . esc($s['skor_model']) . '</code></div>';
        }
        foreach (AiSkorPrompt::DIMENSI as $k => $d) {
            $v = (int) ($rin[$k] ?? 0);
            $pct = (int) round($v / AiSkorPrompt::MAKS_PER_DIMENSI * 100);
            $h .= '<div class="d-flex align-items-center gap-2 mb-1"><span style="width:8.5rem">' . esc($d['nama']) . '</span>'
                . '<div class="progress flex-grow-1" style="height:6px"><div class="progress-bar" style="width:' . $pct . '%"></div></div>'
                . '<span class="text-muted" style="width:2.5rem">' . $v . '/' . AiSkorPrompt::MAKS_PER_DIMENSI . '</span></div>';
        }
        return $h . '</div></details>';
    }
}
