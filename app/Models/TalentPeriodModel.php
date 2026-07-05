<?php

namespace App\Models;

use CodeIgniter\Model;

class TalentPeriodModel extends Model
{
    protected $table         = 'talent_periods';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['nama', 'status', 'created_by', 'locked_by', 'locked_at'];
    protected $useTimestamps = true;

    /** Semua periode, terbaru dulu. */
    public function allPeriods(): array
    {
        return $this->orderBy('id', 'DESC')->findAll();
    }

    /** Periode aktif (untuk default input). Null jika tak ada. */
    public function activePeriod(): ?array
    {
        return $this->where('status', 'active')->orderBy('id', 'DESC')->first();
    }
}
