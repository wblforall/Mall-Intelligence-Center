<?php

namespace App\Models;

use CodeIgniter\Model;

class SponsorshipSummaryAnalysisModel extends Model
{
    protected $table         = 'sponsorship_summary_analysis';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['bulan', 'program_id', 'highlight', 'kendala', 'tindak_lanjut', 'updated_by'];
    protected $useTimestamps = true;

    // Map analisa per bulan: [program_id => ['highlight'=>'','kendala'=>'','tindak_lanjut'=>'']]
    public function getMapByMonth(string $bulan): array
    {
        $rows = $this->where('bulan', $bulan)->findAll();
        $map  = [];
        foreach ($rows as $r) {
            $map[(int)$r['program_id']] = [
                'highlight'     => $r['highlight']     ?? '',
                'kendala'       => $r['kendala']       ?? '',
                'tindak_lanjut' => $r['tindak_lanjut'] ?? '',
            ];
        }
        return $map;
    }

    // Upsert analisa untuk satu program di satu bulan
    public function saveAnalisa(string $bulan, int $programId, ?int $userId, string $highlight, string $kendala, string $tindakLanjut): void
    {
        $existing = $this->where('bulan', $bulan)->where('program_id', $programId)->first();
        $data = [
            'highlight'     => $highlight,
            'kendala'       => $kendala,
            'tindak_lanjut' => $tindakLanjut,
            'updated_by'    => $userId,
        ];
        if ($existing) {
            $this->update($existing['id'], $data);
        } else {
            $this->insert($data + ['bulan' => $bulan, 'program_id' => $programId]);
        }
    }
}
