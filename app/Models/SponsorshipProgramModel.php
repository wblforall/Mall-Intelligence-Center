<?php

namespace App\Models;

use CodeIgniter\Model;

class SponsorshipProgramModel extends Model
{
    protected $table         = 'sponsorship_programs';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'nama_program', 'mall', 'tanggal_mulai', 'tanggal_selesai', 'deskripsi',
        'target_sponsor', 'target_nilai', 'budget',
        'status', 'locked', 'locked_by', 'locked_at',
        'eval_status', 'eval_kendala', 'eval_rekomendasi',
        'catatan', 'created_by',
    ];

    public function getAll(): array
    {
        return $this->orderBy('status', 'ASC')->orderBy('nama_program', 'ASC')->findAll();
    }

    public function toggleStatus(int $id): void
    {
        $current = $this->find($id);
        if (! $current) return;
        $this->update($id, [
            'status' => $current['status'] === 'active' ? 'inactive' : 'active',
        ]);
    }

    public function lock(int $id, int $userId, ?string $evalStatus = null, ?string $evalKendala = null, ?string $evalRekomendasi = null): void
    {
        $data = [
            'locked'    => 1,
            'locked_by' => $userId,
            'locked_at' => date('Y-m-d H:i:s'),
        ];
        if ($evalStatus !== null) {
            $data['eval_status']      = $evalStatus;
            $data['eval_kendala']     = $evalKendala ?: null;
            $data['eval_rekomendasi'] = $evalRekomendasi ?: null;
        }
        $this->update($id, $data);
    }

    public function unlock(int $id): void
    {
        $this->update($id, ['locked' => 0, 'locked_by' => null, 'locked_at' => null]);
    }

    public function isLocked(int $id): bool
    {
        $row = $this->select('locked')->find($id);
        return (bool)($row['locked'] ?? false);
    }
}
