<?php

namespace App\Models;

use CodeIgniter\Model;

/** Jejak pemetaan item lama ke Tema, supaya bisa dibatalkan. */
class CreativePemetaanLogModel extends Model
{
    protected $table         = 'creative_pemetaan_log';
    protected $primaryKey    = 'id';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'batch', 'item_id', 'tema_id_lama', 'event_id_lama', 'tema_id_baru', 'event_id_baru',
        'tema_dibuat', 'oleh', 'dibatalkan_pada', 'created_at',
    ];
}
