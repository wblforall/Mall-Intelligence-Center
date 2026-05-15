<?php

namespace App\Models;

use CodeIgniter\Model;

class ThemePeriodModel extends Model
{
    protected $table         = 'theme_periods';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['nama','start_date','end_date','alert_days','animation','emoji','pesan','is_active'];
    protected $useTimestamps = true;

    public function getActive(): array
    {
        $today = date('Y-m-d');
        return $this->where('is_active', 1)
                    ->where('end_date >=', $today)
                    ->orderBy('start_date')
                    ->findAll();
    }

    // Periode yang seharusnya sudah tampil alert atau animasinya hari ini
    public function getTodayPeriods(): array
    {
        $today = date('Y-m-d');
        return $this->where('is_active', 1)
                    ->where('end_date >=', $today)
                    ->where("DATE_SUB(start_date, INTERVAL alert_days DAY) <=", $today)
                    ->orderBy('start_date')
                    ->findAll();
    }
}
