<?php

namespace App\Controllers;

use App\Models\DepartmentModel;
use App\Models\DepartmentMenuModel;
use App\Libraries\ActivityLog;
use App\Libraries\SectionConfig;

class Departments extends BaseController
{
    public function index()
    {
        $depts = (new DepartmentModel())->getAllWithMenuCount();
        return view('departments/index', [
            'user'       => $this->currentUser(),
            'depts'      => $depts,
            'menuLabels' => SectionConfig::MENU_LABELS,
        ]);
    }

    public function store()
    {
        $name = trim($this->request->getPost('name'));
        if (! $name) {
            return redirect()->to('/departments')->with('error', 'Nama departemen wajib diisi.');
        }

        $deptModel = new DepartmentModel();
        $deptModel->insert([
            'name'        => $name,
            'description' => $this->request->getPost('description'),
        ]);
        ActivityLog::write('create', 'department', (string)$deptModel->getInsertID(), $name);

        return redirect()->to('/departments')->with('success', 'Departemen berhasil ditambahkan.');
    }

    public function edit(int $id)
    {
        $dept = (new DepartmentModel())->getWithMenus($id);
        if (! $dept) {
            return redirect()->to('/departments')->with('error', 'Departemen tidak ditemukan.');
        }

        return view('departments/edit', [
            'user'          => $this->currentUser(),
            'dept'          => $dept,
            'menuLabels'    => SectionConfig::MENU_LABELS,
            'sectionLabels' => SectionConfig::SECTION_LABELS,
        ]);
    }

    public function update(int $id)
    {
        $deptModel = new DepartmentModel();
        if (! $deptModel->find($id)) {
            return redirect()->to('/departments')->with('error', 'Departemen tidak ditemukan.');
        }

        $name = trim($this->request->getPost('name'));
        $deptModel->update($id, [
            'name'        => $name,
            'description' => $this->request->getPost('description'),
        ]);

        // Save menu access
        $menuModel  = new DepartmentMenuModel();
        $menuKeys   = array_keys(SectionConfig::MENU_LABELS);
        $postMenus  = $this->request->getPost('menus') ?? [];
        $menuData   = [];

        foreach ($menuKeys as $key) {
            $menuData[$key] = [
                'section_type' => $postMenus[$key]['section_type'] ?? 'all',
                'can_view'     => isset($postMenus[$key]['can_view']) ? 1 : 0,
                'can_edit'     => isset($postMenus[$key]['can_edit']) ? 1 : 0,
            ];
        }

        $menuModel->saveMenuAccess($id, $menuData);
        ActivityLog::write('update', 'department', (string)$id, $name);

        return redirect()->to('/departments')->with('success', 'Departemen berhasil diperbarui.');
    }

    public function delete(int $id)
    {
        // Check if any users are assigned
        $count = (new \App\Models\UserModel())->where('department_id', $id)->countAllResults();
        if ($count > 0) {
            return redirect()->to('/departments')->with('error', 'Tidak bisa menghapus departemen yang masih memiliki user.');
        }

        $deptModel = new DepartmentModel();
        $dept      = $deptModel->find($id);
        (new DepartmentMenuModel())->where('department_id', $id)->delete();
        $deptModel->delete($id);
        ActivityLog::write('delete', 'department', (string)$id, $dept['name'] ?? '');

        return redirect()->to('/departments')->with('success', 'Departemen berhasil dihapus.');
    }
}
