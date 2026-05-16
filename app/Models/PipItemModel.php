<?php

namespace App\Models;

use CodeIgniter\Model;

class PipItemModel extends Model
{
    protected $table      = 'pip_items';
    protected $primaryKey = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = ['pip_id','aspek','masalah','target','metrik','deadline'];

    public function getByPip(int $pipId): array
    {
        return $this->where('pip_id', $pipId)->orderBy('id', 'ASC')->findAll();
    }
}
