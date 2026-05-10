<?php

namespace App\Controllers;

use App\Models\JabatanModel;
use App\Models\DivisionModel;
use App\Models\DepartmentModel;
use App\Libraries\ActivityLog;

class Jabatans extends BaseController
{
    public function index()
    {
        $filterDept = $this->request->getGet('dept_id') ? (int)$this->request->getGet('dept_id') : null;
        $filterDiv  = $this->request->getGet('division_id') ? (int)$this->request->getGet('division_id') : null;

        $jabModel  = new JabatanModel();
        $all       = $jabModel->getAllWithContext();

        // Apply filter
        if ($filterDept) {
            $all = array_filter($all, fn($j) => (int)$j['dept_id'] === $filterDept);
        } elseif ($filterDiv) {
            $all = array_filter($all, fn($j) => (int)$j['division_id'] === $filterDiv && ! $j['dept_id']);
        }

        $allJabs = $jabModel->orderBy('grade')->orderBy('nama')->findAll();

        return view('admin/jabatans/index', [
            'user'         => $this->currentUser(),
            'jabatans'     => array_values($all),
            'divisions'    => (new DivisionModel())->orderBy('nama')->findAll(),
            'departments'  => (new DepartmentModel())->select('departments.*, divisions.nama AS division_nama')
                                ->join('divisions', 'divisions.id = departments.division_id', 'left')
                                ->orderBy('divisions.nama')->orderBy('departments.name')->findAll(),
            'filterDept'   => $filterDept,
            'filterDiv'    => $filterDiv,
            'jabatansJson' => json_encode(array_map(fn($j) => [
                'id'          => (int)$j['id'],
                'nama'        => $j['nama'],
                'grade'       => (int)$j['grade'],
                'dept_id'     => $j['dept_id'] ? (int)$j['dept_id'] : null,
                'division_id' => $j['division_id'] ? (int)$j['division_id'] : null,
            ], $allJabs)),
        ]);
    }

    public function store()
    {
        $post  = $this->request->getPost();
        $deptId = $post['dept_id']     !== '' ? (int)$post['dept_id']     : null;
        $divId  = $post['division_id'] !== '' ? (int)$post['division_id'] : null;

        $parentId = isset($post['parent_jabatan_id']) && $post['parent_jabatan_id'] !== ''
            ? (int)$post['parent_jabatan_id'] : null;

        $id = (new JabatanModel())->insert([
            'nama'              => trim($post['nama']),
            'grade'             => (int)$post['grade'],
            'dept_id'           => $deptId,
            'division_id'       => $deptId ? null : $divId,
            'parent_jabatan_id' => $parentId,
        ]);
        ActivityLog::write('create', 'jabatan', (string)$id, trim($post['nama']));
        return redirect()->back()->with('success', 'Jabatan berhasil ditambahkan.');
    }

    public function update(int $id)
    {
        $post   = $this->request->getPost();
        $deptId = $post['dept_id']     !== '' ? (int)$post['dept_id']     : null;
        $divId  = $post['division_id'] !== '' ? (int)$post['division_id'] : null;

        $parentId = isset($post['parent_jabatan_id']) && $post['parent_jabatan_id'] !== ''
            ? (int)$post['parent_jabatan_id'] : null;

        (new JabatanModel())->update($id, [
            'nama'              => trim($post['nama']),
            'grade'             => (int)$post['grade'],
            'dept_id'           => $deptId,
            'division_id'       => $deptId ? null : $divId,
            'parent_jabatan_id' => $parentId,
        ]);
        ActivityLog::write('update', 'jabatan', (string)$id, trim($post['nama']));
        return redirect()->back()->with('success', 'Jabatan diperbarui.');
    }

    public function delete(int $id)
    {
        $j = (new JabatanModel())->find($id);
        // Unlink employees before deleting
        db_connect()->table('employees')->where('jabatan_id', $id)->update(['jabatan_id' => null]);
        (new JabatanModel())->delete($id);
        ActivityLog::write('delete', 'jabatan', (string)$id, $j['nama'] ?? '');
        return redirect()->back()->with('success', 'Jabatan dihapus.');
    }
}
