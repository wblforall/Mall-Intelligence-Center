<?php

namespace App\Models;

use CodeIgniter\Model;

class PublicHolidayModel extends Model
{
    protected $table         = 'public_holidays';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = ['tanggal', 'nama', 'jenis'];

    public function getByYear(int $year): array
    {
        return $this->where('YEAR(tanggal)', $year)->orderBy('tanggal', 'ASC')->findAll();
    }

    public function getByRange(string $from, string $to): array
    {
        return $this->where('tanggal >=', $from)->where('tanggal <=', $to)->orderBy('tanggal', 'ASC')->findAll();
    }
}
