<?php

namespace App\Controllers;

use App\Models\DivisionModel;
use App\Models\DepartmentModel;
use App\Libraries\ActivityLog;

class Divisions extends BaseController
{
    public function index()
    {
        $divModel = new DivisionModel();
        $deptModel = new DepartmentModel();

        return view('admin/divisions/index', [
            'user'        => $this->currentUser(),
            'divisions'   => $divModel->getAllWithDepts(),
            'departments' => $deptModel->orderBy('name')->findAll(),
        ]);
    }

    public function store()
    {
        $post = $this->request->getPost();
        $id   = (new DivisionModel())->insert([
            'nama'      => trim($post['nama']),
            'kode'      => trim($post['kode'] ?? '') ?: null,
            'deskripsi' => trim($post['deskripsi'] ?? '') ?: null,
        ]);
        ActivityLog::write('create', 'division', (string)$id, trim($post['nama']));
        return redirect()->to('/divisions')->with('success', 'Divisi berhasil ditambahkan.');
    }

    public function update(int $id)
    {
        $post = $this->request->getPost();
        (new DivisionModel())->update($id, [
            'nama'      => trim($post['nama']),
            'kode'      => trim($post['kode'] ?? '') ?: null,
            'deskripsi' => trim($post['deskripsi'] ?? '') ?: null,
        ]);
        ActivityLog::write('update', 'division', (string)$id, trim($post['nama']));
        return redirect()->to('/divisions')->with('success', 'Divisi diperbarui.');
    }

    public function delete(int $id)
    {
        $div = (new DivisionModel())->find($id);
        // Unlink departments before deleting
        db_connect()->table('departments')->where('division_id', $id)->update(['division_id' => null]);
        (new DivisionModel())->delete($id);
        ActivityLog::write('delete', 'division', (string)$id, $div['nama'] ?? '');
        return redirect()->to('/divisions')->with('success', 'Divisi dihapus.');
    }

    // Assign/remove a department to/from a division
    public function assignDept()
    {
        $post   = $this->request->getPost();
        $deptId = (int)$post['dept_id'];
        $divId  = $post['division_id'] !== '' ? (int)$post['division_id'] : null;
        db_connect()->table('departments')->where('id', $deptId)->update(['division_id' => $divId]);
        ActivityLog::write('update', 'division', (string)($divId ?? 0), 'Assign dept ' . $deptId);
        return redirect()->to('/divisions')->with('success', 'Departemen berhasil dipindahkan.');
    }
}
