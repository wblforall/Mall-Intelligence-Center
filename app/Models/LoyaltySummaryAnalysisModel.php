<?php

namespace App\Models;

use CodeIgniter\Model;

class LoyaltySummaryAnalysisModel extends Model
{
    protected $table         = 'loyalty_summary_analysis';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['bulan', 'source', 'program_id', 'analisa', 'highlight', 'kendala', 'tindak_lanjut', 'updated_by'];
    protected $useTimestamps = true;

    // Map analisa per bulan: ['s_5' => [...], 'e_12' => [...], ...]
    public function getMapByMonth(string $bulan): array
    {
        $rows = $this->where('bulan', $bulan)->findAll();
        $map  = [];
        foreach ($rows as $r) {
            $key = $r['source'] . '_' . $r['program_id'];
            $map[$key] = [
                'analisa'       => $r['analisa']       ?? '',
                'highlight'     => $r['highlight']     ?? '',
                'kendala'       => $r['kendala']       ?? '',
                'tindak_lanjut' => $r['tindak_lanjut'] ?? '',
            ];
        }
        return $map;
    }

    // Upsert analisa untuk satu program di satu bulan
    public function saveAnalisa(string $bulan, string $source, int $programId, string $analisa, ?int $userId, string $highlight = '', string $kendala = '', string $tindakLanjut = ''): void
    {
        $existing = $this->where('bulan', $bulan)
            ->where('source', $source)
            ->where('program_id', $programId)
            ->first();

        $data = [
            'highlight'     => $highlight,
            'kendala'       => $kendala,
            'tindak_lanjut' => $tindakLanjut,
            'updated_by'    => $userId,
        ];
        // Tulis analisa (legacy) hanya jika tidak kosong — hindari overwrite data lama
        if ($analisa !== '') {
            $data['analisa'] = $analisa;
        }
        if ($existing) {
            $this->update($existing['id'], $data);
        } else {
            $this->insert($data + ['bulan' => $bulan, 'source' => $source, 'program_id' => $programId]);
        }
    }
}
