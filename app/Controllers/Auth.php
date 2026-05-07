<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\DepartmentMenuModel;
use App\Models\RoleModel;
use App\Libraries\ActivityLog;
use CodeIgniter\Controller;

class Auth extends Controller
{
    public function index()
    {
        if (session()->get('logged_in')) {
            return redirect()->to('/');
        }
        return view('auth/login');
    }

    public function login()
    {
        $email    = $this->request->getPost('email');
        $password = $this->request->getPost('password');

        $model = new UserModel();
        $user  = $model->findByEmail($email);

        if (! $user || ! password_verify($password, $user['password'])) {
            ActivityLog::write('login_failed', 'auth', null, 'Login gagal', ['email' => $email]);
            return redirect()->back()->with('error', 'Email atau password salah.')->withInput();
        }

        if (! $user['is_active']) {
            return redirect()->back()->with('error', 'Akun Anda dinonaktifkan.')->withInput();
        }

        // Load role permissions
        $rolePerms = ['is_admin' => false, 'can_create_event' => false, 'can_delete_event' => false, 'can_manage_users' => false];
        if (! empty($user['role_id'])) {
            $role = (new RoleModel())->find((int)$user['role_id']);
            if ($role) {
                $rolePerms = RoleModel::buildPerms($role);
            }
        } elseif ($user['role'] === 'admin') {
            // Legacy fallback for users without role_id yet
            $rolePerms = ['is_admin' => true, 'can_create_event' => true, 'can_delete_event' => true, 'can_manage_users' => true];
        }

        // Load department menu access map for non-admin users
        $deptMenus = null;
        if (! $rolePerms['is_admin'] && ! empty($user['department_id'])) {
            $deptMenus = (new DepartmentMenuModel())->getMenuMap((int)$user['department_id']);
        }

        session()->set([
            'logged_in'      => true,
            'user_id'        => $user['id'],
            'user_name'      => $user['name'],
            'user_email'     => $user['email'],
            'user_role'      => $user['role'],
            'role_is_admin'  => $rolePerms['is_admin'],
            'role_perms'     => $rolePerms,
            'dept_id'        => $user['department_id'],
            'dept_menus'     => $deptMenus,
            'user_theme'     => $user['theme'] ?? 'dark',
        ]);

        ActivityLog::write('login', 'auth', (string)$user['id'], $user['name']);
        return redirect()->to('/');
    }

    public function logout()
    {
        ActivityLog::write('logout', 'auth', (string)session()->get('user_id'), session()->get('user_name'));
        session()->destroy();
        return redirect()->to('/login')->with('success', 'Anda berhasil logout.');
    }
}
