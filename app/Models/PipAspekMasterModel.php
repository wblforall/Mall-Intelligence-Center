<?php

namespace App\Models;

use CodeIgniter\Model;

class PipAspekMasterModel extends Model
{
    protected $table      = 'pip_aspek_master';
    protected $primaryKey = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = ['aspek','kategori','target_default','metrik_default','aktif'];

    public function getAktif(): array
    {
        return $this->where('aktif', 1)->orderBy('kategori')->orderBy('aspek')->findAll();
    }

    public function getAllGrouped(): array
    {
        $rows = $this->orderBy('kategori')->orderBy('aspek')->findAll();
        $grouped = [];
        foreach ($rows as $r) {
            $grouped[$r['kategori'] ?? 'Lainnya'][] = $r;
        }
        return $grouped;
    }
}
