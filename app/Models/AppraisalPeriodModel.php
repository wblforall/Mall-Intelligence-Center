<?php

namespace App\Models;

use CodeIgniter\Model;

class AppraisalPeriodModel extends Model
{
    protected $table         = 'appraisal_periods';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['nama', 'tipe', 'tanggal_mulai', 'tanggal_selesai', 'tahun', 'status', 'created_by'];
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
}
