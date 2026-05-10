<?php

namespace App\Models;

use CodeIgniter\Model;

class SponsorshipProgramModel extends Model
{
    protected $table         = 'sponsorship_programs';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'nama_program', 'tanggal_mulai', 'tanggal_selesai', 'deskripsi',
        'target_sponsor', 'target_nilai', 'budget',
        'status', 'locked', 'locked_by', 'locked_at', 'catatan', 'created_by',
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

    public function lock(int $id, int $userId): void
    {
        $this->update($id, [
            'locked'    => 1,
            'locked_by' => $userId,
            'locked_at' => date('Y-m-d H:i:s'),
        ]);
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
