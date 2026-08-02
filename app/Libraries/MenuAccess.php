<?php

namespace App\Libraries;

use App\Models\DepartmentMenuModel;
use App\Models\UserMenuModel;

/**
 * SUMBER TUNGGAL aturan akses menu MIC.
 *
 * Aturannya cuma satu kalimat: **admin bypass → grant per-user → grant dept**,
 * dan ketiga kolom (`can_view`, `can_edit`, `can_approve`) memakai aturan yang
 * sama persis. Sebelumnya kalimat itu ditulis ulang di tiga tempat —
 * `BaseController` (web, berbasis session), `BaseApiController` (mobile,
 * berbasis DB), dan closure di `ApprovalInbox` — dan ketiganya sempat
 * menyimpang: API tak pernah membaca `user_menu_access`, sehingga pemegang
 * grant khusus ditolak keliru, dan hak "Setujui" (v2.24) tak terbaca sama
 * sekali. Kelas ini menutup celah itu untuk selamanya.
 *
 * Dua pintu masuk:
 *  - {@see allowed()} — fungsi murni, dipakai web dengan peta dari session.
 *  - {@see can()} / {@see effectiveMap()} — versi berbasis DB tanpa session,
 *    dipakai API mobile, cron, dan ApprovalInbox.
 */
class MenuAccess
{
    public const KOLOM = ['can_view', 'can_edit', 'can_approve'];

    /**
     * Aturan inti. Murni — tak menyentuh session maupun database.
     *
     * @param array|null $userMap peta grant per-user  ['menu' => ['can_view'=>bool, ...]]
     * @param array|null $deptMap peta grant departemen; null = user tanpa dept
     */
    public static function allowed(
        bool $isAdmin,
        ?array $userMap,
        ?array $deptMap,
        string $menuKey,
        string $kolom
    ): bool {
        if ($isAdmin) return true;

        // Grant per-user bersifat ADITIF di atas dept — dicek lebih dulu.
        if (! empty($userMap[$menuKey][$kolom])) return true;

        // Non-admin tanpa departemen tidak punya akses apa pun.
        if ($deptMap === null) return false;

        return ! empty($deptMap[$menuKey][$kolom]);
    }

    /**
     * Peta akses seorang user langsung dari database (tanpa session).
     * @return array{is_admin:bool, user:array, dept:?array}
     */
    public static function mapsForUser(int $userId): array
    {
        $db   = db_connect();
        $user = $db->table('users')->select('id, role, role_id, department_id')
            ->where('id', $userId)->get()->getRowArray();

        if (! $user) return ['is_admin' => false, 'user' => [], 'dept' => null];

        $isAdmin = ($user['role'] ?? '') === 'admin';
        if (! $isAdmin && ! empty($user['role_id'])) {
            $role = $db->table('roles')->where('id', (int) $user['role_id'])->get()->getRowArray();
            $isAdmin = $role && ! empty($role['is_admin']);
        }

        return [
            'is_admin' => $isAdmin,
            'user'     => (new UserMenuModel())->getMenuMap($userId),
            'dept'     => ! empty($user['department_id'])
                ? (new DepartmentMenuModel())->getMenuMap((int) $user['department_id'])
                : null,
        ];
    }

    /** Cek satu hak untuk satu user, langsung dari DB. */
    public static function can(int $userId, string $menuKey, string $kolom): bool
    {
        $m = self::mapsForUser($userId);
        return self::allowed($m['is_admin'], $m['user'], $m['dept'], $menuKey, $kolom);
    }

    /**
     * Peta hak efektif seluruh menu untuk dikirim ke app mobile —
     * sudah digabung (dept + per-user + bypass admin), jadi app tinggal baca.
     *
     * @param array|null $maps hasil mapsForUser(), agar tak query dua kali
     * @return array<string, array{can_view:bool, can_edit:bool, can_approve:bool}>
     */
    public static function effectiveMap(int $userId, ?array $maps = null): array
    {
        $m    = $maps ?? self::mapsForUser($userId);
        $hasil = [];
        foreach (array_keys(SectionConfig::MENU_LABELS) as $menuKey) {
            $baris = [];
            foreach (self::KOLOM as $kolom) {
                $baris[$kolom] = self::allowed($m['is_admin'], $m['user'], $m['dept'], $menuKey, $kolom);
            }
            // Menu yang sama sekali tak boleh diakses tak perlu dikirim —
            // menghemat payload dan bikin app lebih sulit salah tampil.
            if ($baris['can_view'] || $baris['can_edit'] || $baris['can_approve']) {
                $hasil[$menuKey] = $baris;
            }
        }
        return $hasil;
    }
}
