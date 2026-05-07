<?php

namespace App\Models;

use CodeIgniter\Model;

class TrafficDoorModel extends Model
{
    protected $table         = 'traffic_doors';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['mall', 'nama_pintu', 'urutan', 'aktif'];
    protected $useTimestamps = false;

    public function getByMall(string $mall, bool $activeOnly = true): array
    {
        $builder = $this->where('mall', $mall)->orderBy('urutan')->orderBy('nama_pintu');
        if ($activeOnly) $builder->where('aktif', 1);
        return $builder->findAll();
    }

    public function getAllGrouped(): array
    {
        $rows = $this->orderBy('mall')->orderBy('urutan')->orderBy('nama_pintu')->findAll();
        $grouped = ['ewalk' => [], 'pentacity' => []];
        foreach ($rows as $r) {
            $grouped[$r['mall']][] = $r;
        }
        return $grouped;
    }
}
