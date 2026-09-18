<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Master area/lokasi temuan, per mall.
 *
 * FASE 2 — tabelnya sudah ada dan model ini sudah bisa dipakai, tapi antarmuka
 * pengisiannya belum dibangun. Sepanjang Fase 1 seluruh temuan memakai
 * area_id = 0 ("tidak dirinci").
 */
class PestAreaModel extends Model
{
    protected $table         = 'pest_areas';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['mall', 'nama', 'urutan', 'aktif', 'created_at'];
    protected $useTimestamps = false;

    public function aktifByMall(string $mall): array
    {
        return $this->where('mall', $mall)->where('aktif', 1)
            ->orderBy('urutan')->orderBy('id')->findAll();
    }

    /** Peta id => nama, untuk melabeli temuan tanpa query berulang. */
    public function petaNama(): array
    {
        $map = [0 => 'Tidak dirinci'];
        foreach ($this->findAll() as $r) $map[(int) $r['id']] = $r['nama'];
        return $map;
    }
}
