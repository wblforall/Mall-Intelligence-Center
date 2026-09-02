<?php

namespace App\Models;

use CodeIgniter\Model;

class CompanyModel extends Model
{
    protected $table         = 'companies';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['kode', 'nama', 'parent_id', 'aktif'];
    protected $useTimestamps = true;

    public function aktifSaja(): array
    {
        return $this->where('aktif', 1)->orderBy('nama', 'ASC')->findAll();
    }
}
