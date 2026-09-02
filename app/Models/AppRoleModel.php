<?php

namespace App\Models;

use CodeIgniter\Model;

class AppRoleModel extends Model
{
    protected $table         = 'app_roles';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['app_id', 'kode', 'label', 'aktif'];
    protected $useTimestamps = true;

    public function byApp(int $appId): array
    {
        return $this->where('app_id', $appId)->where('aktif', 1)->orderBy('label', 'ASC')->findAll();
    }

    /** Peran aktif seluruh aplikasi, dikelompokkan per app_id — untuk mengisi dropdown di satu layar. */
    public function semuaDikelompokkan(): array
    {
        $rows = $this->where('aktif', 1)->orderBy('label', 'ASC')->findAll();
        $out = [];
        foreach ($rows as $r) {
            $out[$r['app_id']][] = $r;
        }
        return $out;
    }
}
