<?php

namespace App\Controllers\Api;

use App\Models\UserModel;
use App\Models\ApiTokenModel;
use App\Models\RoleModel;
use App\Libraries\ActivityLog;

class AuthController extends BaseApiController
{
    public function options(): never
    {
        $this->json([])->send();
        exit;
    }

    public function login()
    {
        $throttler = \Config\Services::throttler();
        if ($throttler->check(md5($this->request->getIPAddress() . '_apilogin'), 10, MINUTE) === false) {
            return $this->error('Terlalu banyak percobaan login. Coba lagi sebentar.', 429);
        }

        $body     = $this->request->getJSON(true) ?? [];
        $email    = trim($body['email'] ?? '');
        $password = $body['password'] ?? '';

        if (! $email || ! $password) {
            return $this->error('Email dan password wajib diisi.');
        }

        $userModel = new UserModel();
        $user      = $userModel->findByEmail($email);

        if (! $user || ! $user['is_active']) {
            password_verify($password, '$2y$10$usesomesillystringforsalt0123456789abcdefghijklmnopqrstuv');
            return $this->error('Email atau password salah.', 401);
        }

        if (! empty($user['locked_until']) && strtotime($user['locked_until']) > time()) {
            $menit = (int)ceil((strtotime($user['locked_until']) - time()) / 60);
            return $this->error("Akun terkunci. Coba lagi dalam {$menit} menit.", 403);
        }

        if (! password_verify($password, $user['password'])) {
            $attempts = (int)($user['failed_login_attempts'] ?? 0) + 1;
            if ($attempts >= 5) {
                $userModel->update($user['id'], [
                    'failed_login_attempts' => $attempts,
                    'locked_until'          => date('Y-m-d H:i:s', strtotime('+15 minutes')),
                ]);
                return $this->error('Akun dikunci 15 menit karena terlalu banyak percobaan gagal.', 403);
            }
            $userModel->update($user['id'], ['failed_login_attempts' => $attempts]);
            return $this->error('Email atau password salah.', 401);
        }

        if (! empty($user['must_change_password'])) {
            return $this->error('Anda harus mengganti password melalui web sebelum login mobile.', 403);
        }

        $userModel->update($user['id'], ['failed_login_attempts' => 0, 'locked_until' => null]);

        $rolePerms = ['is_admin' => false];
        if (! empty($user['role_id'])) {
            $role = (new RoleModel())->find((int)$user['role_id']);
            if ($role) $rolePerms = RoleModel::buildPerms($role);
        } elseif ($user['role'] === 'admin') {
            $rolePerms = ['is_admin' => true, 'can_create_event' => true, 'can_delete_event' => true, 'can_manage_users' => true];
        }

        // Load department menu access
        $deptMenus = null;
        if (! $rolePerms['is_admin'] && ! empty($user['department_id'])) {
            $deptMenus = (new \App\Models\DepartmentMenuModel())->getMenuMap((int)$user['department_id']);
        }

        $token = (new ApiTokenModel())->generate((int)$user['id']);
        ActivityLog::write('login_api', 'auth', (string)$user['id'], $user['name'] . ' via mobile');

        return $this->success([
            'token' => $token,
            'user'  => [
                'user_id'    => $user['id'],
                'name'       => $user['name'],
                'email'      => $user['email'],
                'role'       => $user['role'],
                'dept_id'    => $user['department_id'],
                'role_perms' => $rolePerms,
                'dept_menus' => $deptMenus,
            ],
        ], 'Login berhasil.');
    }

    public function logout()
    {
        if (! $this->requireAuth()) return $this->response;

        $token = substr($this->request->getHeaderLine('Authorization'), 7);
        (new ApiTokenModel())->revoke($token);
        ActivityLog::write('logout_api', 'auth', (string)$this->apiUser['id'], $this->apiUser['name'] . ' via mobile');

        return $this->success(null, 'Logout berhasil.');
    }

    public function me()
    {
        if (! $this->requireAuth()) return $this->response;

        return $this->success([
            'user_id'  => $this->apiUser['id'],
            'name'     => $this->apiUser['name'],
            'email'    => $this->apiUser['email'],
            'role'     => $this->apiUser['role'],
            'dept_id'  => $this->apiUser['department_id'],
        ]);
    }

    public function savePushToken()
    {
        if (! $this->requireAuth()) return $this->response;

        $body  = $this->request->getJSON(true) ?? [];
        $token = trim($body['push_token'] ?? '');
        if (! $token) return $this->error('Push token tidak valid.');

        $bearerToken = substr($this->request->getHeaderLine('Authorization'), 7);
        $this->db->table('api_tokens')
            ->where('token', $bearerToken)
            ->update(['push_token' => $token]);

        return $this->success(null, 'Push token disimpan.');
    }
}
