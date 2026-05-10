<?php

namespace App\Models;

use CodeIgniter\Model;

class EeiPeriodModel extends Model
{
    protected $table         = 'eei_periods';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['nama', 'start_date', 'end_date', 'is_active', 'survey_token'];
    protected $useTimestamps = true;

    public function getActivePeriod(): ?array
    {
        return $this->where('is_active', 1)
                    ->where('start_date <=', date('Y-m-d'))
                    ->where('end_date >=', date('Y-m-d'))
                    ->first();
    }

    public function generateToken(): string
    {
        return bin2hex(random_bytes(20)); // 40-char hex, URL-safe
    }

    public function activate(int $id): void
    {
        $db = db_connect();
        $db->table('eei_periods')->update(['is_active' => 0]);

        $period = $this->find($id);
        $update = ['is_active' => 1];
        if (empty($period['survey_token'])) {
            $update['survey_token'] = $this->generateToken();
        }
        $db->table('eei_periods')->where('id', $id)->update($update);
    }
}
