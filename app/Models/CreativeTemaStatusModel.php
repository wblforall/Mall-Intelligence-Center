<?php

namespace App\Models;

use CodeIgniter\Model;

/** Penutupan Tema milik EVENT (tabel events tidak diubah). */
class CreativeTemaStatusModel extends Model
{
    protected $table         = 'creative_tema_status';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = ['event_id', 'ditutup', 'ditutup_oleh', 'ditutup_pada'];
}
