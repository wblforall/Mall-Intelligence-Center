<?php

namespace App\Controllers\Api;

use App\Models\ApiTokenModel;
use App\Models\RoleModel;
use App\Models\DepartmentMenuModel;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

abstract class BaseApiController extends Controller
{
    protected array $apiUser = [];

    /** Computed role permissions for the authenticated API user (mirror of session role_perms). */
    protected array $apiPerms = [];

    /** Department menu access map, or null for admin / no department. */
    protected ?array $apiMenus = null;

    /** Grant menu per-user (user_menu_access) — aditif di atas akses dept. */
    protected array $apiUserMenus = [];

    /** @var \CodeIgniter\Database\BaseConnection */
    protected $db;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->db = db_connect();
    }

    protected function json(mixed $data, int $status = 200): ResponseInterface
    {
        return $this->response
            ->setStatusCode($status)
            ->setHeader('Content-Type', 'application/json')
            ->setHeader('Access-Control-Allow-Origin', '*')
            ->setHeader('Access-Control-Allow-Headers', 'Authorization, Content-Type')
            ->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
            ->setJSON($data);
    }

    protected function error(string $message, int $status = 400): ResponseInterface
    {
        return $this->json(['success' => false, 'message' => $message], $status);
    }

    protected function success(mixed $data, string $message = 'OK'): ResponseInterface
    {
        return $this->json(['success' => true, 'message' => $message, 'data' => $data]);
    }

    protected function requireAuth(): bool
    {
        $header = $this->request->getHeaderLine('Authorization');
        if (! $header || ! str_starts_with($header, 'Bearer ')) {
            $this->json(['success' => false, 'message' => 'Unauthorized.'], 401)->send();
            return false;
        }

        $token = substr($header, 7);
        $row   = (new ApiTokenModel())->findRow($token);

        if (! $row) {
            $this->json(['success' => false, 'message' => 'Token tidak valid atau sudah kadaluarsa.'], 401)->send();
            return false;
        }

        $user = (new \App\Models\UserModel())->find($row['user_id']);

        // Force-logout — menyamakan API dengan web, yang memutus sesi begitu
        // akun dinonaktifkan atau hak aksesnya diubah. Tanpa ini token masih
        // berlaku sampai 30 hari, jadi karyawan resign tetap bisa memakai app.
        if (! $user || empty($user['is_active'])) {
            (new ApiTokenModel())->revoke($token);
            $this->json(['success' => false, 'message' => 'Akun tidak aktif. Silakan hubungi admin.'], 401)->send();
            return false;
        }
        if (! empty($user['perms_changed_at']) && ! empty($row['created_at'])
            && strtotime($user['perms_changed_at']) > strtotime($row['created_at'])) {
            (new ApiTokenModel())->revoke($token);
            $this->json(['success' => false, 'message' => 'Hak akses Anda berubah. Silakan login ulang.'], 401)->send();
            return false;
        }

        $this->apiUser = $user;
        $this->loadPerms($user);
        return true;
    }

    /** Build role permissions + department menu map for the user (mirrors web login). */
    private function loadPerms(array $user): void
    {
        $perms = ['is_admin' => false];
        if (! empty($user['role_id'])) {
            $role = (new RoleModel())->find((int)$user['role_id']);
            if ($role) $perms = RoleModel::buildPerms($role);
        } elseif (($user['role'] ?? '') === 'admin') {
            $perms = ['is_admin' => true];
        }
        $this->apiPerms = $perms;

        if (empty($perms['is_admin'])) {
            // Grant per-user WAJIB ikut dimuat — aturan MIC bersifat aditif.
            // Sebelumnya hanya akses dept yang dibaca, sehingga pemegang grant
            // khusus ditolak keliru oleh app.
            $this->apiUserMenus = (new \App\Models\UserMenuModel())->getMenuMap((int)$user['id']);
            if (! empty($user['department_id'])) {
                $this->apiMenus = (new DepartmentMenuModel())->getMenuMap((int)$user['department_id']);
            }
        }
    }

    protected function isAdmin(): bool
    {
        return ! empty($this->apiPerms['is_admin']) || ($this->apiUser['role'] ?? '') === 'admin';
    }

    /** System-level permission (e.g. can_approve_pip), admin always granted. */
    protected function can(string $perm): bool
    {
        if ($this->isAdmin()) return true;
        return (bool)($this->apiPerms[$perm] ?? false);
    }

    /**
     * Aturan akses menu — SAMA PERSIS dengan web (BaseController), karena
     * keduanya memanggil {@see \App\Libraries\MenuAccess::allowed()}. API tak
     * boleh jadi pintu belakang: kalau di web tidak boleh, di app juga tidak.
     */
    private function menuAllowed(string $menuKey, string $kolom): bool
    {
        return \App\Libraries\MenuAccess::allowed(
            $this->isAdmin(), $this->apiUserMenus, $this->apiMenus, $menuKey, $kolom
        );
    }

    protected function canViewMenu(string $menuKey): bool
    {
        return $this->menuAllowed($menuKey, 'can_view');
    }

    protected function canEditMenu(string $menuKey): bool
    {
        return $this->menuAllowed($menuKey, 'can_edit');
    }

    /** Boleh menyetujui pada menu ini (kolom `can_approve`, v2.24). */
    protected function canApproveMenu(string $menuKey): bool
    {
        return $this->menuAllowed($menuKey, 'can_approve');
    }

    /** Peta hak efektif seluruh menu — dikirim ke app lewat /api/auth/me. */
    protected function effectiveMenuMap(): array
    {
        return \App\Libraries\MenuAccess::effectiveMap((int) $this->apiUser['id'], [
            'is_admin' => $this->isAdmin(),
            'user'     => $this->apiUserMenus,
            'dept'     => $this->apiMenus,
        ]);
    }

    protected function forbidden(string $message = 'Anda tidak memiliki izin untuk tindakan ini.'): ResponseInterface
    {
        return $this->error($message, 403);
    }

    /** True if the authenticated API user is the direct supervisor (atasan) of the given employee. */
    protected function isSupervisorOfEmployee(int $employeeId): bool
    {
        $emp = $this->db->table('employees')->select('atasan_id')->where('id', $employeeId)->get()->getRowArray();
        if (! $emp || empty($emp['atasan_id'])) return false;
        $atasan = $this->db->table('employees')->select('user_id')->where('id', (int)$emp['atasan_id'])->get()->getRowArray();
        return $atasan && ! empty($atasan['user_id']) && (int)$atasan['user_id'] === (int)$this->apiUser['id'];
    }

    /** The user_id linked to an employee record, or null. Used to block self-approval. */
    protected function employeeUserId(int $employeeId): ?int
    {
        $emp = $this->db->table('employees')->select('user_id')->where('id', $employeeId)->get()->getRowArray();
        return $emp && ! empty($emp['user_id']) ? (int)$emp['user_id'] : null;
    }
}
