<?php

namespace App\Models;

use CodeIgniter\Model;

class AppModel extends Model
{
    protected $table         = 'apps';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['kode', 'nama', 'url', 'ikon', 'aktif'];
    protected $useTimestamps = true;

    public function aktifSaja(): array
    {
        return $this->where('aktif', 1)->orderBy('nama', 'ASC')->findAll();
    }
}
