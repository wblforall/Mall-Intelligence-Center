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
            'name'         => $name,
            'description'  => $this->request->getPost('description'),
            'is_outsource' => $this->request->getPost('is_outsource') ? 1 : 0,
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
        ActivityLog::captureBefore($deptModel->find($id));
        $deptData = [
            'name'         => $name,
            'description'  => $this->request->getPost('description'),
            'is_outsource' => $this->request->getPost('is_outsource') ? 1 : 0,
        ];
        $deptModel->update($id, $deptData);
        ActivityLog::captureAfter($deptData);

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
        // Akses menu dept berubah → paksa semua user di dept ini login ulang.
        $affected = db_connect()->table('users')->where('department_id', $id)->countAllResults();
        db_connect()->table('users')->where('department_id', $id)->update(['perms_changed_at' => date('Y-m-d H:i:s')]);
        ActivityLog::write('update', 'department', (string)$id, $name);

        $msg = 'Departemen berhasil diperbarui.';
        if ($affected > 0) {
            $msg .= ' Perubahan akses baru berlaku setelah ' . $affected . ' user di dept ini login ulang.';
        }
        return redirect()->to('/departments')->with('success', $msg);
    }

    public function delete(int $id)
    {
        $db = db_connect();

        // Blokir hapus bila masih ada data yang menaut dept ini — cegah orphan
        // (mis. kasus dept "Building Maintenance" dihapus tapi karyawan, program
        // kerja, & riwayat posisi masih menunjuk ke id-nya). Cek field yang benar:
        // employees.dept_id (bukan users.department_id), + work_initiatives + posisi.
        $blockers = [
            'karyawan'      => $db->table('employees')->where('dept_id', $id)->countAllResults(),
            'program kerja' => $db->table('work_initiatives')->where('dept_id', $id)
                                  ->orWhere('assigned_to_dept_id', $id)->countAllResults(),
            'riwayat posisi'=> $db->table('employee_positions')->where('dept_id', $id)->countAllResults(),
            'user'          => $db->table('users')->where('department_id', $id)->countAllResults(),
        ];
        $found = array_filter($blockers);
        if ($found) {
            $rincian = implode(', ', array_map(fn($k, $v) => "$v $k", array_keys($found), $found));
            return redirect()->to('/departments')->with('error',
                'Tidak bisa menghapus departemen — masih ditaut oleh: ' . $rincian
                . '. Pindahkan/lepas dulu sebelum menghapus.');
        }

        $deptModel = new DepartmentModel();
        $dept      = $deptModel->find($id);
        (new DepartmentMenuModel())->where('department_id', $id)->delete();
        $deptModel->delete($id);
        ActivityLog::write('delete', 'department', (string)$id, $dept['name'] ?? '');

        return redirect()->to('/departments')->with('success', 'Departemen berhasil dihapus.');
    }
}
