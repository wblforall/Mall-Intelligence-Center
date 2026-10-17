<?php

namespace App\Models;

use CodeIgniter\Model;

/** Tema Bulanan Creative (pekerjaan mal di luar event). */
class CreativeTemaModel extends Model
{
    protected $table         = 'creative_tema';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'nama', 'bulan', 'deskripsi', 'ditutup', 'ditutup_oleh', 'ditutup_pada', 'dibuat_oleh',
    ];
}
