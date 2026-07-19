<?php

namespace App\Libraries;

/**
 * Penandatangan laporan bulanan modul (Loyalty, Sponsorship, dst):
 * Disusun = Dept Head dept penyusun (jabatan grade terendah di dept),
 * Diperiksa = Deputy GM (grade 3) divisi dept tsb, Mengetahui = GM.
 *
 * Dept penyusun = dept karyawan user yang mencetak; bila pencetak admin /
 * tidak terhubung ke karyawan ber-dept, fallback ke dept pemilik hak edit
 * menu terkait (department_menu_access) — agar laporan tetap bertanda tangan.
 */
class ReportSignatories
{
    /** @return array{disusun: ?array, diperiksa: ?array, mengetahui: ?array} */
    public static function resolve(string $fallbackMenuKey): array
    {
        $db  = db_connect();
        $uid = (int) session()->get('user_id');

        $pick = fn(?array $row) => $row ? ['nama' => $row['nama'], 'jabatan' => $row['jabatan_nama']] : null;

        $me     = $db->table('employees')->select('dept_id')->where('user_id', $uid)->get()->getRowArray();
        $deptId = (int) ($me['dept_id'] ?? 0);
        if (! $deptId) {
            $own = $db->table('department_menu_access')
                ->select('department_id')
                ->where('menu_key', $fallbackMenuKey)
                ->where('can_edit', 1)
                ->get(1)->getRowArray();
            $deptId = (int) ($own['department_id'] ?? 0);
        }

        $deptHead = null;
        $deputy   = null;
        if ($deptId) {
            $deptHead = $db->table('employees e')
                ->select('e.nama, j.nama AS jabatan_nama')
                ->join('jabatans j', 'j.id = e.jabatan_id')
                ->where('e.dept_id', $deptId)
                ->where('e.status', 'aktif')
                ->orderBy('j.grade', 'ASC')
                ->get(1)->getRowArray();

            $divisiId = (int) ($db->table('departments')->select('division_id')
                ->where('id', $deptId)->get()->getRowArray()['division_id'] ?? 0);
            if ($divisiId) {
                $deputy = $db->table('employees e')
                    ->select('e.nama, j.nama AS jabatan_nama')
                    ->join('jabatans j', 'j.id = e.jabatan_id')
                    ->join('departments d', 'd.id = e.dept_id', 'left')
                    ->where('j.grade', 3)
                    ->where('e.status', 'aktif')
                    ->groupStart()
                        ->where('e.division_id', $divisiId)
                        ->orWhere('d.division_id', $divisiId)
                    ->groupEnd()
                    ->get(1)->getRowArray();
            }
        }

        $gm = $db->table('employees e')
            ->select('e.nama, j.nama AS jabatan_nama')
            ->join('jabatans j', 'j.id = e.jabatan_id')
            ->where('LOWER(j.nama) LIKE', '%general manager%')
            ->where('e.status', 'aktif')
            ->orderBy('j.grade', 'ASC')
            ->get(1)->getRowArray();

        return [
            'disusun'    => $pick($deptHead),
            'diperiksa'  => $pick($deputy),
            'mengetahui' => $pick($gm),
        ];
    }
}
