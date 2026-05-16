<?php

namespace App\Controllers;

use App\Models\EmployeeModel;
use App\Models\EmployeePositionModel;
use App\Models\EmployeeCertificateModel;
use App\Models\TrainingProgramModel;
use App\Models\DepartmentModel;
use App\Models\DivisionModel;
use App\Models\JabatanModel;
use App\Libraries\ActivityLog;

class PeopleEmployees extends BaseController
{
    public function index()
    {
        if (! $this->canViewMenu('people_dev')) return redirect()->to('/events')->with('error', 'Akses ditolak.');
        $employees = (new EmployeeModel())->getWithDept();

        foreach ($employees as &$e) {
            $e['masa_kerja'] = EmployeeModel::getMasaKerja($e['tanggal_masuk']);
        }

        return view('people/employees/index', [
            'user'        => $this->currentUser(),
            'employees'   => $employees,
            'departments' => (new DepartmentModel())->orderBy('name')->findAll(),
            'divisions'   => (new DivisionModel())->orderBy('nama')->findAll(),
            'jabatanMap'  => (new JabatanModel())->getAllAsMap(),
            'allEmployees'=> $employees,
        ]);
    }

    public function show(int $id)
    {
        if (! $this->canViewMenu('people_dev')) return redirect()->to('/events')->with('error', 'Akses ditolak.');
        $employee = (new EmployeeModel())->findWithDept($id);
        if (! $employee) return redirect()->to('/people/employees')->with('error', 'Karyawan tidak ditemukan.');

        $employee['masa_kerja'] = EmployeeModel::getMasaKerja($employee['tanggal_masuk']);

        $positions    = (new EmployeePositionModel())->getByEmployee($id);
        $certificates = (new EmployeeCertificateModel())->getByEmployee($id);

        foreach ($certificates as &$c) {
            $c['status'] = EmployeeCertificateModel::getCertStatus($c['tanggal_kadaluarsa']);
        }

        $allEmployees = (new EmployeeModel())->getWithDept();
        $departments  = (new DepartmentModel())->orderBy('name')->findAll();

        // Derive current division for the edit form (deputy case: dept_id is null)
        $currentDivisionId = null;
        if ($employee['dept_id']) {
            foreach ($departments as $d) {
                if ($d['id'] == $employee['dept_id']) {
                    $currentDivisionId = $d['division_id'] ?? null;
                    break;
                }
            }
        } elseif ($employee['jabatan_id']) {
            $jab = (new JabatanModel())->find($employee['jabatan_id']);
            $currentDivisionId = $jab['division_id'] ?? null;
        }

        return view('people/employees/detail', [
            'user'              => $this->currentUser(),
            'employee'          => $employee,
            'positions'         => $positions,
            'certificates'      => $certificates,
            'trainings'         => (new TrainingProgramModel())->getByEmployee($id),
            'departments'       => $departments,
            'divisions'         => (new DivisionModel())->orderBy('nama')->findAll(),
            'jabatanMap'        => (new JabatanModel())->getAllAsMap(),
            'allEmployees'      => $allEmployees,
            'currentDivisionId' => $currentDivisionId,
        ]);
    }

    public function store()
    {
        if (! $this->canEditMenu('people_dev')) return redirect()->to('/events')->with('error', 'Akses ditolak.');
        $post = $this->request->getPost();
        $model = new EmployeeModel();

        $fotoName = null;
        $file = $this->request->getFile('foto');
        if ($file && $file->isValid() && ! $file->hasMoved()) {
            if ($err = $this->validateUpload($file, self::MIME_IMAGE, 5)) {
                return redirect()->back()->with('error', $err);
            }
            $uploadPath = FCPATH . 'uploads/people/photos/';
            if (! is_dir($uploadPath)) mkdir($uploadPath, 0755, true);
            $fotoName = 'emp_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $this->safeExt($file);
            $file->move($uploadPath, $fotoName);
        }

        $jabatanId = $post['jabatan_id'] !== '' ? (int)$post['jabatan_id'] : null;
        $atasanId  = $post['atasan_id']  !== '' ? (int)$post['atasan_id']  : null;
        $jabatanNama = trim($post['jabatan'] ?? '') ?: null;
        if ($jabatanId) {
            $jab = (new JabatanModel())->find($jabatanId);
            if ($jab) $jabatanNama = $jab['nama'];
        }

        $newId = $model->insert([
            'nik'           => trim($post['nik'] ?? '') ?: null,
            'nama'          => trim($post['nama']),
            'jenis_kelamin' => $post['jenis_kelamin'] ?: null,
            'tanggal_lahir' => $post['tanggal_lahir'] ?: null,
            'tanggal_masuk' => $post['tanggal_masuk'],
            'dept_id'       => $post['dept_id'] ?: null,
            'jabatan'       => $jabatanNama,
            'jabatan_id'    => $jabatanId,
            'atasan_id'     => $atasanId,
            'no_hp'         => trim($post['no_hp'] ?? '') ?: null,
            'email'         => trim($post['email'] ?? '') ?: null,
            'status'        => $post['status'] ?? 'aktif',
            'foto'          => $fotoName,
            'catatan'       => trim($post['catatan'] ?? '') ?: null,
        ]);

        // Catat posisi awal jika jabatan diisi
        if ($jabatanNama) {
            (new EmployeePositionModel())->insert([
                'employee_id'   => $newId,
                'jabatan'       => $jabatanNama,
                'dept_id'       => $post['dept_id'] ?: null,
                'tanggal_mulai' => $post['tanggal_masuk'],
            ]);
        }

        ActivityLog::write('create', 'employee', (string)$newId, trim($post['nama']));
        return redirect()->to('/people/employees/' . $newId)->with('success', 'Karyawan berhasil ditambahkan.');
    }

    public function update(int $id)
    {
        if (! $this->canEditMenu('people_dev')) return redirect()->to('/events')->with('error', 'Akses ditolak.');
        $post = $this->request->getPost();
        $model = new EmployeeModel();
        $employee = $model->find($id);
        if (! $employee) return redirect()->to('/people/employees')->with('error', 'Karyawan tidak ditemukan.');

        $fotoName = $employee['foto'];
        $file = $this->request->getFile('foto');
        if ($file && $file->isValid() && ! $file->hasMoved()) {
            if ($err = $this->validateUpload($file, self::MIME_IMAGE, 5)) {
                return redirect()->back()->with('error', $err);
            }
            $uploadPath = FCPATH . 'uploads/people/photos/';
            if (! is_dir($uploadPath)) mkdir($uploadPath, 0755, true);
            if ($fotoName && file_exists($uploadPath . $fotoName)) unlink($uploadPath . $fotoName);
            $fotoName = 'emp_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $this->safeExt($file);
            $file->move($uploadPath, $fotoName);
        }

        $jabatanId = $post['jabatan_id'] !== '' ? (int)$post['jabatan_id'] : null;
        $atasanId  = $post['atasan_id']  !== '' ? (int)$post['atasan_id']  : null;
        $jabatanNama = trim($post['jabatan'] ?? '') ?: null;
        if ($jabatanId) {
            $jab = (new JabatanModel())->find($jabatanId);
            if ($jab) $jabatanNama = $jab['nama'];
        }

        $model->update($id, [
            'nik'           => trim($post['nik'] ?? '') ?: null,
            'nama'          => trim($post['nama']),
            'jenis_kelamin' => $post['jenis_kelamin'] ?: null,
            'tanggal_lahir' => $post['tanggal_lahir'] ?: null,
            'tanggal_masuk' => $post['tanggal_masuk'],
            'dept_id'       => $post['dept_id'] ?: null,
            'jabatan'       => $jabatanNama,
            'jabatan_id'    => $jabatanId,
            'atasan_id'     => $atasanId,
            'no_hp'         => trim($post['no_hp'] ?? '') ?: null,
            'email'         => trim($post['email'] ?? '') ?: null,
            'status'        => $post['status'] ?? 'aktif',
            'foto'          => $fotoName,
            'catatan'       => trim($post['catatan'] ?? '') ?: null,
        ]);

        ActivityLog::write('update', 'employee', (string)$id, trim($post['nama']));
        return redirect()->to('/people/employees/' . $id)->with('success', 'Data karyawan diperbarui.');
    }

    public function delete(int $id)
    {
        if (! $this->canEditMenu('people_dev')) return redirect()->to('/events')->with('error', 'Akses ditolak.');
        $model    = new EmployeeModel();
        $employee = $model->find($id);
        if (! $employee) return redirect()->to('/people/employees')->with('error', 'Karyawan tidak ditemukan.');

        $db = db_connect();
        $db->transStart();
        (new EmployeePositionModel())->where('employee_id', $id)->delete();
        $certs = (new EmployeeCertificateModel())->where('employee_id', $id)->findAll();
        (new EmployeeCertificateModel())->where('employee_id', $id)->delete();
        $model->delete($id);
        $db->transComplete();

        if ($db->transStatus()) {
            $uploadPath = FCPATH . 'uploads/people/';
            if ($employee['foto'] && file_exists($uploadPath . 'photos/' . $employee['foto']))
                unlink($uploadPath . 'photos/' . $employee['foto']);
            foreach ($certs as $c) {
                if ($c['file_name'] && file_exists($uploadPath . 'certificates/' . $c['file_name']))
                    unlink($uploadPath . 'certificates/' . $c['file_name']);
            }
        }

        ActivityLog::write('delete', 'employee', (string)$id, $employee['nama']);
        return redirect()->to('/people/employees')->with('success', 'Karyawan berhasil dihapus.');
    }

    public function storePosition(int $id)
    {
        if (! $this->canEditMenu('people_dev')) return redirect()->to('/events')->with('error', 'Akses ditolak.');
        $post = $this->request->getPost();
        $posModel = new EmployeePositionModel();

        // Tutup posisi lama yang masih aktif
        $db = db_connect();
        $db->table('employee_positions')
            ->where('employee_id', $id)
            ->where('tanggal_selesai IS NULL', null, false)
            ->update(['tanggal_selesai' => date('Y-m-d', strtotime($post['tanggal_mulai'] . ' -1 day'))]);

        $posModel->insert([
            'employee_id'   => $id,
            'jabatan'       => trim($post['jabatan']),
            'dept_id'       => $post['dept_id'] ?: null,
            'tanggal_mulai' => $post['tanggal_mulai'],
            'keterangan'    => trim($post['keterangan'] ?? '') ?: null,
        ]);

        // Sync jabatan & dept di tabel employees
        (new EmployeeModel())->update($id, [
            'jabatan' => trim($post['jabatan']),
            'dept_id' => $post['dept_id'] ?: null,
        ]);

        ActivityLog::write('create', 'employee_position', (string)$id, trim($post['jabatan']));
        return redirect()->to('/people/employees/' . $id . '#positions')->with('success', 'Riwayat jabatan ditambahkan.');
    }

    public function deletePosition(int $id, int $pid)
    {
        if (! $this->canEditMenu('people_dev')) return redirect()->to('/events')->with('error', 'Akses ditolak.');
        (new EmployeePositionModel())->delete($pid);
        ActivityLog::write('delete', 'employee_position', (string)$pid, '', ['employee_id' => $id]);
        return redirect()->to('/people/employees/' . $id . '#positions')->with('success', 'Riwayat jabatan dihapus.');
    }

    public function storeCertificate(int $id)
    {
        if (! $this->canEditMenu('people_dev')) return redirect()->to('/events')->with('error', 'Akses ditolak.');
        $post = $this->request->getPost();

        $fileName = null;
        $fileOrig = null;
        $file = $this->request->getFile('file_sertifikat');
        if ($file && $file->isValid() && ! $file->hasMoved()) {
            if ($err = $this->validateUpload($file, self::MIME_DOC, 10)) {
                return redirect()->back()->with('error', $err);
            }
            $uploadPath = FCPATH . 'uploads/people/certificates/';
            if (! is_dir($uploadPath)) mkdir($uploadPath, 0755, true);
            $fileName = 'cert_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $this->safeExt($file);
            $fileOrig = $file->getClientName();
            $file->move($uploadPath, $fileName);
        }

        (new EmployeeCertificateModel())->insert([
            'employee_id'        => $id,
            'nama_sertifikat'    => trim($post['nama_sertifikat']),
            'nomor_sertifikat'   => trim($post['nomor_sertifikat'] ?? '') ?: null,
            'penerbit'           => trim($post['penerbit'] ?? '') ?: null,
            'tanggal_terbit'     => $post['tanggal_terbit'] ?: null,
            'tanggal_kadaluarsa' => $post['tanggal_kadaluarsa'] ?: null,
            'file_name'          => $fileName,
            'file_original'      => $fileOrig,
            'catatan'            => trim($post['catatan'] ?? '') ?: null,
        ]);

        ActivityLog::write('create', 'employee_certificate', (string)$id, trim($post['nama_sertifikat']));
        return redirect()->to('/people/employees/' . $id . '#certificates')->with('success', 'Sertifikat berhasil ditambahkan.');
    }

    public function deleteCertificate(int $id, int $cid)
    {
        if (! $this->canEditMenu('people_dev')) return redirect()->to('/events')->with('error', 'Akses ditolak.');
        $cert = (new EmployeeCertificateModel())->find($cid);
        (new EmployeeCertificateModel())->delete($cid);

        if ($cert && $cert['file_name']) {
            $path = FCPATH . 'uploads/people/certificates/' . $cert['file_name'];
            if (file_exists($path)) unlink($path);
        }

        ActivityLog::write('delete', 'employee_certificate', (string)$cid, $cert['nama_sertifikat'] ?? '', ['employee_id' => $id]);
        return redirect()->to('/people/employees/' . $id . '#certificates')->with('success', 'Sertifikat dihapus.');
    }
}
