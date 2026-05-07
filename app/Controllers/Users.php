<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\DepartmentModel;
use App\Models\RoleModel;
use App\Libraries\ActivityLog;

class Users extends BaseController
{
    public function index()
    {
        $users = (new UserModel())->orderBy('name')->findAll();
        $depts = (new DepartmentModel())->orderBy('name')->findAll();
        $roles = (new RoleModel())->orderBy('id')->findAll();
        return view('users/index', [
            'user'  => $this->currentUser(),
            'users' => $users,
            'depts' => $depts,
            'roles' => $roles,
        ]);
    }

    public function store()
    {
        $rules = [
            'name'     => 'required|min_length[2]',
            'email'    => 'required|valid_email|is_unique[users.email]',
            'password' => 'required|min_length[6]',
            'role_id'  => 'required|is_natural_no_zero',
        ];
        if (! $this->validate($rules)) {
            return redirect()->to('/users')->with('errors', $this->validator->getErrors());
        }

        $roleId = (int)$this->request->getPost('role_id');
        $role   = (new RoleModel())->find($roleId);
        $deptId = $this->request->getPost('department_id');

        $model = new UserModel();
        $newId = $model->insert([
            'name'          => $this->request->getPost('name'),
            'email'         => $this->request->getPost('email'),
            'password'      => password_hash($this->request->getPost('password'), PASSWORD_BCRYPT),
            'role'          => $role ? $role['slug'] : 'operator',
            'role_id'       => $roleId,
            'department_id' => $deptId ?: null,
            'is_active'     => 1,
        ]);
        ActivityLog::write('create', 'user', (string)$newId, $this->request->getPost('name'), [
            'email' => $this->request->getPost('email'),
            'role'  => $role ? $role['slug'] : 'operator',
        ]);
        return redirect()->to('/users')->with('success', 'User berhasil ditambahkan.');
    }

    public function update(int $id)
    {
        $post     = $this->request->getPost();
        $roleId   = (int)($post['role_id'] ?? 0);
        $role     = $roleId ? (new RoleModel())->find($roleId) : null;
        $userModel = new UserModel();
        $before   = $userModel->find($id);

        $data = [
            'name'          => $post['name'],
            'role'          => $role ? $role['slug'] : ($post['role'] ?? 'operator'),
            'role_id'       => $roleId ?: null,
            'department_id' => $post['department_id'] ?: null,
        ];
        if (! empty($post['password'])) {
            $data['password'] = password_hash($post['password'], PASSWORD_BCRYPT);
        }

        $userModel->update($id, $data);
        ActivityLog::write('update', 'user', (string)$id, $before['name'] ?? '', [
            'before' => ['name' => $before['name'], 'role' => $before['role'], 'department_id' => $before['department_id']],
            'after'  => ['name' => $data['name'],   'role' => $data['role'],   'department_id' => $data['department_id']],
            'password_changed' => !empty($post['password']),
        ]);
        return redirect()->to('/users')->with('success', 'User berhasil diperbarui.');
    }

    public function toggle(int $id)
    {
        $user = (new UserModel())->find($id);
        if ($user) {
            $newStatus = $user['is_active'] ? 0 : 1;
            (new UserModel())->update($id, ['is_active' => $newStatus]);
            ActivityLog::write('update', 'user', (string)$id, $user['name'], [
                'field' => 'is_active',
                'before' => (bool)$user['is_active'],
                'after'  => (bool)$newStatus,
            ]);
        }
        return redirect()->to('/users')->with('success', 'Status user diperbarui.');
    }

    public function delete(int $id)
    {
        if ($id === session()->get('user_id')) {
            return redirect()->to('/users')->with('error', 'Tidak bisa menghapus akun sendiri.');
        }
        $user = (new UserModel())->find($id);
        (new UserModel())->delete($id);
        ActivityLog::write('delete', 'user', (string)$id, $user['name'] ?? '', [
            'email' => $user['email'] ?? '', 'role' => $user['role'] ?? '',
        ]);
        return redirect()->to('/users')->with('success', 'User berhasil dihapus.');
    }

    public function profile()
    {
        $id   = session()->get('user_id');
        $user = (new UserModel())->find($id);
        return view('users/profile', ['user' => $user]);
    }

    public function updateProfile()
    {
        $post = $this->request->getPost();
        $id   = session()->get('user_id');
        $data = ['name' => $post['name']];

        if (! empty($post['password'])) {
            if (strlen($post['password']) < 6) {
                return redirect()->to('/profile')->with('error', 'Password minimal 6 karakter.');
            }
            $data['password'] = password_hash($post['password'], PASSWORD_BCRYPT);
        }

        (new UserModel())->update($id, $data);
        session()->set('user_name', $post['name']);
        return redirect()->to('/profile')->with('success', 'Profil berhasil diperbarui.');
    }

    public function updateTheme()
    {
        $theme = $this->request->getPost('theme');
        if (! in_array($theme, ['dark', 'light'], true)) {
            $theme = 'dark';
        }
        $id = session()->get('user_id');
        (new UserModel())->update($id, ['theme' => $theme]);
        session()->set('user_theme', $theme);
        return redirect()->back();
    }
}
