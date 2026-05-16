<?php

namespace App\Controllers;

use App\Models\RoleModel;
use App\Models\UserModel;
use App\Libraries\ActivityLog;

class Roles extends BaseController
{
    public function index()
    {
        $roles = (new RoleModel())->orderBy('id')->findAll();

        foreach ($roles as &$r) {
            $r['user_count'] = (new UserModel())->where('role_id', $r['id'])->countAllResults();
        }

        return view('roles/index', [
            'user'  => $this->currentUser(),
            'roles' => $roles,
        ]);
    }

    public function store()
    {
        $post  = $this->request->getPost();
        $model = new RoleModel();

        $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '_', $post['slug'] ?? $post['name'])));

        if ($model->isSlugTaken($slug)) {
            return redirect()->to('/roles')->with('error', 'Slug sudah digunakan.');
        }

        $model->insert([
            'name'               => trim($post['name']),
            'slug'               => $slug,
            'description'        => trim($post['description'] ?? ''),
            'is_admin'           => isset($post['is_admin']) ? 1 : 0,
            'can_create_event'   => isset($post['can_create_event']) ? 1 : 0,
            'can_delete_event'   => isset($post['can_delete_event']) ? 1 : 0,
            'can_manage_users'   => isset($post['can_manage_users']) ? 1 : 0,
            'can_delete_traffic'  => isset($post['can_delete_traffic'])  ? 1 : 0,
            'can_import_traffic'  => isset($post['can_import_traffic'])  ? 1 : 0,
            'can_view_logs'       => isset($post['can_view_logs'])       ? 1 : 0,
            'can_approve_events'  => isset($post['can_approve_events'])  ? 1 : 0,
            'can_approve_pip'     => isset($post['can_approve_pip'])     ? 1 : 0,
            'can_view_gantt'      => isset($post['can_view_gantt'])      ? 1 : 0,
        ]);

        ActivityLog::write('create', 'role', null, trim($post['name']));
        return redirect()->to('/roles')->with('success', 'Role berhasil ditambahkan.');
    }

    public function update(int $id)
    {
        $post  = $this->request->getPost();
        $model = new RoleModel();
        $role  = $model->find($id);

        if (! $role) {
            return redirect()->to('/roles')->with('error', 'Role tidak ditemukan.');
        }

        $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '_', $post['slug'] ?? $role['slug'])));

        if ($model->isSlugTaken($slug, $id)) {
            return redirect()->to('/roles')->with('error', 'Slug sudah digunakan role lain.');
        }

        $model->update($id, [
            'name'               => trim($post['name']),
            'slug'               => $slug,
            'description'        => trim($post['description'] ?? ''),
            'is_admin'           => isset($post['is_admin']) ? 1 : 0,
            'can_create_event'   => isset($post['can_create_event']) ? 1 : 0,
            'can_delete_event'   => isset($post['can_delete_event']) ? 1 : 0,
            'can_manage_users'   => isset($post['can_manage_users']) ? 1 : 0,
            'can_delete_traffic'  => isset($post['can_delete_traffic'])  ? 1 : 0,
            'can_import_traffic'  => isset($post['can_import_traffic'])  ? 1 : 0,
            'can_view_logs'       => isset($post['can_view_logs'])       ? 1 : 0,
            'can_approve_events'  => isset($post['can_approve_events'])  ? 1 : 0,
            'can_approve_pip'     => isset($post['can_approve_pip'])     ? 1 : 0,
            'can_view_gantt'      => isset($post['can_view_gantt'])      ? 1 : 0,
        ]);

        db_connect()->query('UPDATE users SET `role` = ? WHERE role_id = ?', [$slug, $id]);

        ActivityLog::write('update', 'role', (string)$id, trim($post['name']), [
            'before' => ['name' => $role['name'], 'slug' => $role['slug']],
            'after'  => ['name' => trim($post['name']), 'slug' => $slug],
        ]);
        return redirect()->to('/roles')->with('success', 'Role berhasil diperbarui.');
    }

    public function delete(int $id)
    {
        $userCount = (new UserModel())->where('role_id', $id)->countAllResults();
        if ($userCount > 0) {
            return redirect()->to('/roles')->with('error', "Tidak bisa hapus role — masih ada {$userCount} user yang menggunakan role ini.");
        }

        $role = (new RoleModel())->find($id);
        (new RoleModel())->delete($id);
        ActivityLog::write('delete', 'role', (string)$id, $role['name'] ?? '');
        return redirect()->to('/roles')->with('success', 'Role berhasil dihapus.');
    }
}
