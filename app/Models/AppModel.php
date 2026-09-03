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
        // Urutan sama dengan portal (`apps.urutan`), supaya aplikasi tidak
        // tersusun berbeda antara layar admin dan yang dilihat karyawan —
        // beda urutan antar layar membuat orang mencari dua kali.
        return $this->where('aktif', 1)
            ->orderBy('urutan', 'ASC')->orderBy('nama', 'ASC')
            ->findAll();
    }
}
