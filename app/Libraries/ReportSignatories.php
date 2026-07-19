<?php

namespace App\Libraries;

/**
 * Penandatangan laporan bulanan modul (Loyalty, Sponsorship, Traffic, dst):
 * Disusun = Dept Head dept penyusun (jabatan grade terendah di dept),
 * Diperiksa = Senior Manager divisi (grade 4, bila ada — tampil di kiri)
 *             berdampingan dengan Deputy GM (grade 3) divisi dept tsb,
 * Mengetahui = GM.
 *
 * Dept penyusun = DEPT PEMILIK MODUL (pemegang hak edit menu di
 * department_menu_access, non-outsource) — laporan modul selalu bertanda
 * tangan dept pemiliknya (mis. traffic = Operational), siapa pun yang
 * mencetak. Bila mapping tidak ada, fallback ke dept karyawan si pencetak.
 */
class ReportSignatories
{
    /** @return array{disusun: ?array, diperiksa: ?array, mengetahui: ?array} */
    public static function resolve(string $fallbackMenuKey): array
    {
        $db  = db_connect();
        $uid = (int) session()->get('user_id');

        $pick = fn(?array $row) => $row ? ['nama' => $row['nama'], 'jabatan' => $row['jabatan_nama']] : null;

        // Dept pemilik modul = punya hak edit menu, bukan dept outsource
        // (mis. Security punya can_edit traffic hanya untuk input), dan
        // diprioritaskan yang juga punya can_view.
        $own = $db->table('department_menu_access dma')
            ->select('dma.department_id')
            ->join('departments d', 'd.id = dma.department_id')
            ->where('dma.menu_key', $fallbackMenuKey)
            ->where('dma.can_edit', 1)
            ->where('d.is_outsource', 0)
            ->orderBy('dma.can_view', 'DESC')
            ->get(1)->getRowArray();
        $deptId = (int) ($own['department_id'] ?? 0);
        if (! $deptId) {
            $me     = $db->table('employees')->select('dept_id')->where('user_id', $uid)->get()->getRowArray();
            $deptId = (int) ($me['dept_id'] ?? 0);
        }

        $deptHead      = null;
        $deputy        = null;
        $seniorManager = null;
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
                $byGrade = function (int $grade) use ($db, $divisiId): ?array {
                    return $db->table('employees e')
                        ->select('e.nama, j.nama AS jabatan_nama')
                        ->join('jabatans j', 'j.id = e.jabatan_id')
                        ->join('departments d', 'd.id = e.dept_id', 'left')
                        ->where('j.grade', $grade)
                        ->where('e.status', 'aktif')
                        ->groupStart()
                            ->where('e.division_id', $divisiId)
                            ->orWhere('d.division_id', $divisiId)
                        ->groupEnd()
                        ->get(1)->getRowArray();
                };
                $deputy        = $byGrade(3);
                $seniorManager = $byGrade(4); // pembina divisi (mis. Senior Manager Ops & BM) — null bila divisi tak punya
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
            'disusun'      => $pick($deptHead),
            'diperiksa_sm' => $pick($seniorManager), // slot kiri "Diperiksa oleh" — hanya tampil bila ada
            'diperiksa'    => $pick($deputy),
            'mengetahui'   => $pick($gm),
        ];
    }
}
