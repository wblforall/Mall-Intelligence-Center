<?php

namespace App\Libraries;

/**
 * Resolusi penerima notifikasi berdasarkan struktur organisasi
 * (employees + jabatans + departments) — satu sumber, dipakai lintas modul
 * agar SQL hierarki tidak diduplikasi di banyak controller.
 *
 * Hirarki MIC: Dept Head (grade terendah >= 5 di dept) → Deputy GM (grade 3
 * per divisi) → GM (jabatan mengandung "general manager").
 * Semua method mengembalikan array user_id (karyawan tanpa akun dilewati).
 */
class OrgRecipients
{
    /** Dept Head satu departemen (jabatan grade terendah >= 5, karyawan aktif). */
    public static function deptHead(int $deptId): array
    {
        if ($deptId <= 0) return [];
        $db = db_connect();
        $row = $db->table('employees e')
            ->select('MIN(j.grade) AS g')
            ->join('jabatans j', 'j.id = e.jabatan_id')
            ->where('e.dept_id', $deptId)->where('e.status', 'aktif')->where('j.grade >=', 5)
            ->get()->getRowArray();
        if (! $row || $row['g'] === null) return [];

        return self::userIds($db->table('employees e')
            ->select('e.user_id')
            ->join('jabatans j', 'j.id = e.jabatan_id')
            ->where('e.dept_id', $deptId)->where('e.status', 'aktif')->where('j.grade', (int) $row['g'])
            ->get()->getResultArray());
    }

    /** Deputy GM satu divisi (grade 3). */
    public static function deputy(int $divisionId): array
    {
        if ($divisionId <= 0) return [];
        return self::userIds(db_connect()->table('employees e')
            ->select('e.user_id')
            ->join('jabatans j', 'j.id = e.jabatan_id')
            ->where('e.division_id', $divisionId)->where('e.status', 'aktif')->where('j.grade', 3)
            ->get()->getResultArray());
    }

    /** General Manager (lintas divisi). */
    public static function gm(): array
    {
        return self::userIds(db_connect()->table('employees e')
            ->select('e.user_id')
            ->join('jabatans j', 'j.id = e.jabatan_id')
            ->where('e.status', 'aktif')
            ->like('LOWER(j.nama)', 'general manager')
            ->get()->getResultArray());
    }



    /**
     * user_id atasan langsung seorang USER (lewat employees.atasan_id).
     * Hanya atasan berstatus aktif & berakun aktif — konsisten dengan
     * resolver lain di kelas ini (dulu tanpa filter, sehingga eskalasi bisa
     * mendarat di akun karyawan yang sudah resign).
     */
    public static function supervisorOfUser(int $userId): array
    {
        $db  = db_connect();
        $emp = $db->table('employees')->select('atasan_id')->where('user_id', $userId)->get()->getRowArray();
        if (! $emp || empty($emp['atasan_id'])) return [];

        return self::userIds($db->table('employees e')
            ->select('e.user_id')
            ->join('users u', 'u.id = e.user_id')
            ->where('e.id', (int) $emp['atasan_id'])
            ->where('e.status', 'aktif')->where('u.is_active', 1)
            ->get()->getResultArray());
    }

    /**
     * Sasaran eskalasi bila seorang approver membiarkan item menggantung:
     * atasan langsung (aktif) → Deputy GM divisi approver → GM.
     * Rantai ini menjaga kemandekan tetap terlihat walau atasannya sudah
     * resign atau karyawan tak punya atasan tercatat.
     */
    public static function escalationTargets(int $userId): array
    {
        if ($atasan = self::supervisorOfUser($userId)) return $atasan;

        $emp = db_connect()->table('employees')->select('division_id')
            ->where('user_id', $userId)->where('status', 'aktif')->get()->getRowArray();
        if ($emp && ! empty($emp['division_id'])) {
            if ($deputy = self::deputy((int) $emp['division_id'])) return $deputy;
        }

        return self::gm();
    }

    /**
     * Semua user pemegang akses EDIT sebuah menu.
     *
     * Menggabungkan DUA sumber sesuai aturan MIC (BaseController::canEditMenu):
     * grant per-departemen (`department_menu_access`, dept non-outsource) DAN
     * grant per-user (`user_menu_access`, additive di atas dept). Sebelumnya
     * hanya membaca grant dept, sehingga pemegang grant individual tak pernah
     * menerima notifikasi HR/Legal maupun pengingat persetujuan.
     */
    public static function menuEditors(string $menuKey): array
    {
        $db = db_connect();

        $dept = $db->table('users u')
            ->select('u.id AS user_id')
            ->join('department_menu_access dma', 'dma.department_id = u.department_id')
            ->join('departments d', 'd.id = u.department_id')
            ->where('dma.menu_key', $menuKey)->where('dma.can_edit', 1)
            ->where('d.is_outsource', 0)->where('u.is_active', 1)
            ->get()->getResultArray();

        $perUser = $db->table('user_menu_access uma')
            ->select('uma.user_id')
            ->join('users u', 'u.id = uma.user_id')
            ->where('uma.menu_key', $menuKey)->where('uma.can_edit', 1)
            ->where('u.is_active', 1)
            ->get()->getResultArray();

        return self::userIds(array_merge($dept, $perUser));
    }

    /** Izin approve yang boleh dipakai withRolePerm() (whitelist kolom `roles`). */
    private const APPROVE_PERMS = [
        'can_approve_events', 'can_approve_promo_media', 'can_approve_pip', 'can_approve_legal',
    ];

    /**
     * Semua user yang memegang izin sistem tertentu (mis. can_approve_events),
     * termasuk pemegang role admin. Nama kolom di-whitelist.
     */
    public static function withRolePerm(string $perm): array
    {
        if (! in_array($perm, self::APPROVE_PERMS, true)) return self::admins();

        return self::userIds(db_connect()->table('users u')
            ->select('u.id AS user_id')
            ->join('roles r', 'r.id = u.role_id')
            ->where('u.is_active', 1)
            ->groupStart()->where("r.{$perm}", 1)->orWhere('r.is_admin', 1)->groupEnd()
            ->get()->getResultArray());
    }

    /**
     * Jaring pengaman: kembalikan $ids bila ada isinya, selain itu admin sistem.
     * Dipakai untuk notifikasi yang TIDAK BOLEH hilang (mis. pengajuan menunggu
     * persetujuan) — sebagian menu diakses lewat role admin, bukan grant dept,
     * sehingga resolver bisa mengembalikan kosong.
     */
    public static function orAdmins(array $ids): array
    {
        return $ids ?: self::admins();
    }

    /** Semua admin sistem aktif (fallback penerima bila hierarki tak ketemu). */
    public static function admins(): array
    {
        return self::userIds(db_connect()->table('users')
            ->select('id AS user_id')->where('role', 'admin')->where('is_active', 1)
            ->get()->getResultArray());
    }

    /** @param array<int,array> $rows */
    private static function userIds(array $rows): array
    {
        $ids = [];
        foreach ($rows as $r) {
            $uid = (int) ($r['user_id'] ?? 0);
            if ($uid > 0) $ids[] = $uid;
        }
        return array_values(array_unique($ids));
    }
}
