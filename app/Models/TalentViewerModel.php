<?php

namespace App\Models;

use CodeIgniter\Model;

class TalentViewerModel extends Model
{
    protected $table         = 'talent_viewers';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['user_id', 'added_by'];
    protected $useTimestamps = true;

    /** True jika user termasuk daftar penonton peta talent. */
    public function isViewer(int $userId): bool
    {
        return $this->where('user_id', $userId)->countAllResults() > 0;
    }

    /** Daftar viewer + nama/email user (untuk halaman kelola viewer). */
    public function listWithUser(): array
    {
        return $this->db->table('talent_viewers tv')
            ->select('tv.id, tv.user_id, u.name, u.email, u.role, tv.created_at')
            ->join('users u', 'u.id = tv.user_id', 'left')
            ->orderBy('u.name', 'ASC')
            ->get()->getResultArray();
    }
}
