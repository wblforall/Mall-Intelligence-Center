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
        $user  = (new ApiTokenModel())->findUser($token);

        if (! $user) {
            $this->json(['success' => false, 'message' => 'Token tidak valid atau sudah kadaluarsa.'], 401)->send();
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

        if (empty($perms['is_admin']) && ! empty($user['department_id'])) {
            $this->apiMenus = (new DepartmentMenuModel())->getMenuMap((int)$user['department_id']);
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

    protected function canViewMenu(string $menuKey): bool
    {
        if ($this->isAdmin()) return true;
        return isset($this->apiMenus[$menuKey]) && $this->apiMenus[$menuKey]['can_view'];
    }

    protected function canEditMenu(string $menuKey): bool
    {
        if ($this->isAdmin()) return true;
        return isset($this->apiMenus[$menuKey]) && $this->apiMenus[$menuKey]['can_edit'];
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
