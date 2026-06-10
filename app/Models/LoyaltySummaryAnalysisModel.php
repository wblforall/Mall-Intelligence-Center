<?php

namespace App\Models;

use CodeIgniter\Model;

class LoyaltySummaryAnalysisModel extends Model
{
    protected $table         = 'loyalty_summary_analysis';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['bulan', 'source', 'program_id', 'analisa', 'updated_by'];
    protected $useTimestamps = true;

    // Map analisa per bulan: ['s_5' => 'teks', 'e_12' => 'teks', ...]
    public function getMapByMonth(string $bulan): array
    {
        $rows = $this->where('bulan', $bulan)->findAll();
        $map  = [];
        foreach ($rows as $r) {
            $map[$r['source'] . '_' . $r['program_id']] = $r['analisa'] ?? '';
        }
        return $map;
    }

    // Upsert analisa untuk satu program di satu bulan
    public function saveAnalisa(string $bulan, string $source, int $programId, string $analisa, ?int $userId): void
    {
        $existing = $this->where('bulan', $bulan)
            ->where('source', $source)
            ->where('program_id', $programId)
            ->first();

        $data = ['analisa' => $analisa, 'updated_by' => $userId];
        if ($existing) {
            $this->update($existing['id'], $data);
        } else {
            $this->insert($data + ['bulan' => $bulan, 'source' => $source, 'program_id' => $programId]);
        }
    }
}
