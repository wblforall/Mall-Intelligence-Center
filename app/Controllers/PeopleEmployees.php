<?php

namespace App\Controllers;

use App\Models\EmployeeModel;
use App\Models\EmployeePositionModel;
use App\Models\EmployeeCertificateModel;
use App\Models\TrainingProgramModel;
use App\Models\DepartmentModel;
use App\Models\DivisionModel;
use App\Models\JabatanModel;
use App\Models\UserModel;
use App\Models\RoleModel;
use App\Models\EmployeeChangeRequestModel;
use App\Models\EmployeeDocumentModel;
use App\Libraries\ActivityLog;

class PeopleEmployees extends BaseController
{
    public function index()
    {
        if (! $this->canViewMenu('people_dev') && ! $this->canViewMenu('hr_main')) return redirect()->to('/events')->with('error', 'Akses ditolak.');
        $employees = (new EmployeeModel())->getWithDept();

        foreach ($employees as &$e) {
            $e['masa_kerja'] = EmployeeModel::getMasaKerja($e['tanggal_masuk']);
        }

        return view('people/employees/index', [
            'user'        => $this->currentUser(),
            'employees'   => $employees,
            'departments' => (new DepartmentModel())->selectable(),
            'divisions'   => (new DivisionModel())->orderBy('nama')->findAll(),
            'jabatanMap'  => (new JabatanModel())->getAllAsMap(),
            'allEmployees'=> $employees,
        ]);
    }

    // Field profil tambahan (status kontrak, project/payroll, dll) dari form
    private function profileData(array $post): array
    {
        $f = fn($k) => trim($post[$k] ?? '') ?: null;
        return [
            'nik_ktp'            => $f('nik_ktp'),
            'status_kontrak'     => $f('status_kontrak'),
            'tanggal_akhir_kontrak' => $f('tanggal_akhir_kontrak'),
            'project'            => $f('project'),
            'pendidikan'         => $f('pendidikan'),
            'jurusan'            => $f('jurusan'),
            'status_pernikahan'  => $f('status_pernikahan'),
            'agama'              => $f('agama'),
            'jabatan_sebelumnya' => $f('jabatan_sebelumnya'),
            'alamat'             => $f('alamat'),
            'alamat_non_bpn'     => $f('alamat_non_bpn'),
        ];
    }

    // Buatkan akun login untuk karyawan (link employees.user_id ke users)
    public function createAccount(int $id)
    {
        if (! $this->canEditMenu('people_dev') && ! $this->canEditMenu('hr_main')) return redirect()->to('/events')->with('error', 'Akses ditolak.');
        $emp = (new EmployeeModel())->find($id);
        if (! $emp) return redirect()->to('/people/employees')->with('error', 'Karyawan tidak ditemukan.');
        if (! empty($emp['user_id'])) return redirect()->to('/people/employees/' . $id)->with('error', 'Karyawan ini sudah punya akun login.');

        $email  = trim($this->request->getPost('email') ?? '') ?: trim($emp['email'] ?? '');
        $roleId = (int) $this->request->getPost('role_id');
        if ($email === '') return redirect()->to('/people/employees/' . $id)->with('error', 'Email wajib diisi untuk membuat akun.');
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) return redirect()->to('/people/employees/' . $id)->with('error', 'Format email tidak valid.');
        if ((new UserModel())->where('email', $email)->first()) return redirect()->to('/people/employees/' . $id)->with('error', 'Email sudah dipakai akun lain.');
        $role = (new RoleModel())->find($roleId);
        if (! $role) return redirect()->to('/people/employees/' . $id)->with('error', 'Role wajib dipilih.');

        $pass = '123456';
        $userId = (new UserModel())->insert([
            'name'                 => $emp['nama'],
            'email'                => $email,
            'password'             => password_hash($pass, PASSWORD_BCRYPT),
            'role'                 => $role['slug'],
            'role_id'              => $roleId,
            'department_id'        => $emp['dept_id'] ?: null,
            'is_active'            => 1,
            'must_change_password' => 1,
        ]);
        (new EmployeeModel())->update($id, ['user_id' => $userId]);
        ActivityLog::write('create', 'user', (string) $userId, $emp['nama'], ['dari_karyawan' => $id, 'email' => $email, 'role' => $role['slug']]);

        return redirect()->to('/people/employees/' . $id)
            ->with('success', "Akun login dibuat — Email: {$email} · Password awal: {$pass} (wajib diganti saat login pertama).");
    }

    // ── Pengajuan Perubahan Data (approval HR) ──────────────────────────
    private function canManageRequests(): bool
    {
        return $this->canEditMenu('people_dev') || $this->canEditMenu('hr_main');
    }

    public function changeRequests()
    {
        if (! $this->canManageRequests()) return redirect()->to('/events')->with('error', 'Akses ditolak.');
        $model = new EmployeeChangeRequestModel();
        return view('people/change_requests', [
            'user'        => $this->currentUser(),
            'pending'     => $model->inbox('pending'),
            'processed'   => array_merge($model->inbox('approved'), $model->inbox('rejected')),
            'pendingDocs' => (new EmployeeDocumentModel())->pendingInbox(),
            'jenisDok'    => EmployeeDocumentModel::JENIS,
        ]);
    }

    // ── Dokumen Karyawan (upload HR langsung + verifikasi) ───────────────
    public function uploadDocument(int $employeeId)
    {
        if (! $this->canManageRequests()) return redirect()->to('/events')->with('error', 'Akses ditolak.');
        if (! (new EmployeeModel())->find($employeeId)) return redirect()->to('/people/employees')->with('error', 'Karyawan tidak ditemukan.');

        $jenis = $this->request->getPost('jenis');
        if (! array_key_exists($jenis, EmployeeDocumentModel::JENIS)) return redirect()->to('/people/employees/' . $employeeId)->with('error', 'Jenis dokumen tidak valid.');
        $nama = trim((string) $this->request->getPost('nama_dokumen')) ?: null;
        if ($jenis === 'lainnya' && ! $nama) return redirect()->to('/people/employees/' . $employeeId)->with('error', 'Sebutkan nama dokumen untuk jenis "Lainnya".');

        $file = $this->request->getFile('file');
        if (! $file || ! $file->isValid() || $file->hasMoved()) return redirect()->to('/people/employees/' . $employeeId)->with('error', 'File tidak valid.');
        $ext = strtolower($file->getExtension());
        if (! in_array($ext, ['jpg', 'jpeg', 'png', 'pdf'], true)) return redirect()->to('/people/employees/' . $employeeId)->with('error', 'Hanya file JPG, PNG, atau PDF.');
        if ($file->getSize() > 5 * 1024 * 1024) return redirect()->to('/people/employees/' . $employeeId)->with('error', 'Ukuran file maksimal 5 MB.');

        $dir = FCPATH . 'uploads/people/docs/';
        if (! is_dir($dir)) mkdir($dir, 0775, true);
        $newName = 'doc_' . $employeeId . '_' . time() . '_' . bin2hex(random_bytes(5)) . '.' . $ext;
        $file->move($dir, $newName);

        (new EmployeeDocumentModel())->insert([
            'employee_id'  => $employeeId, 'jenis' => $jenis, 'nama_dokumen' => $nama,
            'file_name'    => $newName, 'file_asli' => $file->getClientName(),
            'status'       => 'approved', 'uploaded_by' => session()->get('user_id'),
            'reviewed_by'  => session()->get('user_id'), 'reviewed_at' => date('Y-m-d H:i:s'),
        ]);
        ActivityLog::write('create', 'employee_document', (string) $employeeId, EmployeeDocumentModel::jenisLabel($jenis, $nama), ['oleh' => 'HR']);
        return redirect()->to('/people/employees/' . $employeeId . '#documents')->with('success', 'Dokumen diunggah.');
    }

    public function approveDocument(int $id)
    {
        if (! $this->canManageRequests()) return redirect()->to('/events')->with('error', 'Akses ditolak.');
        $m = new EmployeeDocumentModel();
        $doc = $m->find($id);
        if (! $doc || $doc['status'] !== 'pending') return redirect()->to('/people/change-requests')->with('error', 'Dokumen tidak valid.');
        $m->update($id, ['status' => 'approved', 'reviewed_by' => session()->get('user_id'), 'reviewed_at' => date('Y-m-d H:i:s')]);
        ActivityLog::write('update', 'employee_document', (string) $doc['employee_id'], EmployeeDocumentModel::jenisLabel($doc['jenis'], $doc['nama_dokumen']), ['status' => 'approved']);
        return redirect()->to('/people/change-requests')->with('success', 'Dokumen diverifikasi.');
    }

    public function rejectDocument(int $id)
    {
        if (! $this->canManageRequests()) return redirect()->to('/events')->with('error', 'Akses ditolak.');
        $catatan = trim((string) $this->request->getPost('catatan'));
        if ($catatan === '') return redirect()->to('/people/change-requests')->with('error', 'Alasan penolakan wajib diisi.');
        $m = new EmployeeDocumentModel();
        $doc = $m->find($id);
        if (! $doc || $doc['status'] !== 'pending') return redirect()->to('/people/change-requests')->with('error', 'Dokumen tidak valid.');
        // Hapus file yang ditolak
        $path = FCPATH . 'uploads/people/docs/' . $doc['file_name'];
        if (is_file($path)) @unlink($path);
        $m->update($id, ['status' => 'rejected', 'reviewed_by' => session()->get('user_id'), 'reviewed_at' => date('Y-m-d H:i:s'), 'catatan' => $catatan]);
        ActivityLog::write('update', 'employee_document', (string) $doc['employee_id'], EmployeeDocumentModel::jenisLabel($doc['jenis'], $doc['nama_dokumen']), ['status' => 'rejected']);
        return redirect()->to('/people/change-requests')->with('success', 'Dokumen ditolak.');
    }

    public function deleteDocument(int $id)
    {
        if (! $this->canManageRequests()) return redirect()->to('/events')->with('error', 'Akses ditolak.');
        $m = new EmployeeDocumentModel();
        $doc = $m->find($id);
        if (! $doc) return redirect()->to('/people/employees')->with('error', 'Dokumen tidak ditemukan.');
        $path = FCPATH . 'uploads/people/docs/' . $doc['file_name'];
        if (is_file($path)) @unlink($path);
        $m->delete($id);
        ActivityLog::write('delete', 'employee_document', (string) $doc['employee_id'], EmployeeDocumentModel::jenisLabel($doc['jenis'], $doc['nama_dokumen']));
        return redirect()->to('/people/employees/' . $doc['employee_id'] . '#documents')->with('success', 'Dokumen dihapus.');
    }

    public function approveChange(int $id)
    {
        if (! $this->canManageRequests()) return redirect()->to('/events')->with('error', 'Akses ditolak.');
        $model = new EmployeeChangeRequestModel();
        $req = $model->find($id);
        if (! $req || $req['status'] !== 'pending') return redirect()->to('/people/change-requests')->with('error', 'Pengajuan tidak valid.');

        $empModel = new EmployeeModel();
        $emp = $empModel->find($req['employee_id']);
        if (! $emp) return redirect()->to('/people/change-requests')->with('error', 'Karyawan tidak ditemukan.');

        // Foto: bersihkan file lama setelah commit nilai baru
        if ($req['field'] === 'foto') {
            $dir = FCPATH . 'uploads/people/photos/';
            if (! empty($emp['foto']) && file_exists($dir . $emp['foto'])) @unlink($dir . $emp['foto']);
        }

        $empModel->update($req['employee_id'], [$req['field'] => $req['value_new']]);

        // Jika email berubah & karyawan punya akun login → email login ikut berubah
        if ($req['field'] === 'email' && ! empty($emp['user_id'])) {
            $userModel = new UserModel();
            $clash = $userModel->where('email', $req['value_new'])->where('id !=', $emp['user_id'])->first();
            if (! $clash) {
                $userModel->update($emp['user_id'], ['email' => $req['value_new']]);
            }
        }

        $model->update($id, ['status' => 'approved', 'reviewed_by' => session()->get('user_id'), 'reviewed_at' => date('Y-m-d H:i:s')]);
        ActivityLog::write('update', 'employee', (string) $req['employee_id'], $emp['nama'], ['field' => $req['field'], 'dari' => $req['value_old'], 'jadi' => $req['value_new'], 'via' => 'pengajuan_karyawan']);
        return redirect()->to('/people/change-requests')->with('success', 'Pengajuan disetujui & data diperbarui.');
    }

    public function rejectChange(int $id)
    {
        if (! $this->canManageRequests()) return redirect()->to('/events')->with('error', 'Akses ditolak.');
        $catatan = trim((string) $this->request->getPost('catatan'));
        if ($catatan === '') return redirect()->to('/people/change-requests')->with('error', 'Alasan penolakan wajib diisi.');

        $model = new EmployeeChangeRequestModel();
        $req = $model->find($id);
        if (! $req || $req['status'] !== 'pending') return redirect()->to('/people/change-requests')->with('error', 'Pengajuan tidak valid.');

        // Foto ditolak → hapus file yang sudah terupload
        if ($req['field'] === 'foto') {
            $dir = FCPATH . 'uploads/people/photos/';
            if (! empty($req['value_new']) && file_exists($dir . $req['value_new'])) @unlink($dir . $req['value_new']);
        }

        $model->update($id, ['status' => 'rejected', 'reviewed_by' => session()->get('user_id'), 'reviewed_at' => date('Y-m-d H:i:s'), 'catatan' => $catatan]);
        ActivityLog::write('update', 'employee_change_request', (string) $id, $req['label'], ['status' => 'rejected']);
        return redirect()->to('/people/change-requests')->with('success', 'Pengajuan ditolak.');
    }

    // Export seluruh data karyawan ke CSV (buka di Excel)
    public function export()
    {
        if (! $this->canViewMenu('people_dev') && ! $this->canViewMenu('hr_main')) return redirect()->to('/events')->with('error', 'Akses ditolak.');
        $employees = (new EmployeeModel())->getWithDept();

        $cols = ['No', 'NIK', 'NIK KTP', 'Nama', 'Jenis Kelamin', 'Tanggal Lahir', 'Tanggal Masuk', 'Masa Kerja',
                 'Departemen', 'Divisi', 'Jabatan', 'Grade', 'Atasan', 'Status Kontrak', 'Project (Sumber Gaji)',
                 'Pendidikan', 'Jurusan', 'Status Pernikahan', 'Agama', 'Jabatan Sebelumnya',
                 'No HP', 'Email', 'Alamat', 'Alamat Non-BPN', 'Status', 'Catatan'];
        $esc = fn($v) => '"' . str_replace('"', '""', (string) ($v ?? '')) . '"';

        $csv = "\xEF\xBB\xBF" . implode(',', array_map($esc, $cols)) . "\r\n";
        $i = 1;
        foreach ($employees as $e) {
            $row = [
                $i++, $e['nik'] ?? '', $e['nik_ktp'] ?? '', $e['nama'] ?? '',
                ($e['jenis_kelamin'] === 'P' ? 'Perempuan' : ($e['jenis_kelamin'] === 'L' ? 'Laki-laki' : '')),
                $e['tanggal_lahir'] ?? '', $e['tanggal_masuk'] ?? '',
                EmployeeModel::getMasaKerja($e['tanggal_masuk'] ?? null),
                $e['dept_name'] ?? '', $e['division_nama'] ?? '',
                $e['jabatan'] ?? '', $e['jabatan_grade'] ?? '', $e['atasan_nama'] ?? '',
                $e['status_kontrak'] ?? '', $e['project'] ?? '',
                $e['pendidikan'] ?? '', $e['jurusan'] ?? '', $e['status_pernikahan'] ?? '', $e['agama'] ?? '', $e['jabatan_sebelumnya'] ?? '',
                $e['no_hp'] ?? '', $e['email'] ?? '', $e['alamat'] ?? '', $e['alamat_non_bpn'] ?? '', $e['status'] ?? '', $e['catatan'] ?? '',
            ];
            $csv .= implode(',', array_map($esc, $row)) . "\r\n";
        }

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="data-karyawan-' . date('Ymd') . '.csv"')
            ->setBody($csv);
    }

    public function show(int $id)
    {
        if (! $this->canViewMenu('people_dev') && ! $this->canViewMenu('hr_main')) return redirect()->to('/events')->with('error', 'Akses ditolak.');
        $employee = (new EmployeeModel())->findWithDept($id);
        if (! $employee) return redirect()->to('/people/employees')->with('error', 'Karyawan tidak ditemukan.');

        $employee['masa_kerja'] = EmployeeModel::getMasaKerja($employee['tanggal_masuk']);

        $positions    = (new EmployeePositionModel())->getByEmployee($id);
        $certificates = (new EmployeeCertificateModel())->getByEmployee($id);

        foreach ($certificates as &$c) {
            $c['status'] = EmployeeCertificateModel::getCertStatus($c['tanggal_kadaluarsa']);
        }

        $allEmployees = (new EmployeeModel())->getWithDept();
        $departments  = (new DepartmentModel())->selectable();

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
            'roles'             => (new RoleModel())->orderBy('name')->findAll(),
            'linkedUser'        => $employee['user_id'] ? (new UserModel())->find($employee['user_id']) : null,
            'documents'         => (new EmployeeDocumentModel())->forEmployee($id),
            'jenisDok'          => EmployeeDocumentModel::JENIS,
        ]);
    }

    public function store()
    {
        if (! $this->canEditMenu('people_dev') && ! $this->canEditMenu('hr_main')) return redirect()->to('/events')->with('error', 'Akses ditolak.');
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

        $newId = $model->insert(array_merge([
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
        ], $this->profileData($post)));

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
        if (! $this->canEditMenu('people_dev') && ! $this->canEditMenu('hr_main')) return redirect()->to('/events')->with('error', 'Akses ditolak.');
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

        ActivityLog::captureBefore($employee);
        $employeeData = [
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
        ] + $this->profileData($post);
        $model->update($id, $employeeData);
        ActivityLog::captureAfter($employeeData);

        ActivityLog::write('update', 'employee', (string)$id, trim($post['nama']));
        return redirect()->to('/people/employees/' . $id)->with('success', 'Data karyawan diperbarui.');
    }

    public function delete(int $id)
    {
        if (! $this->canEditMenu('people_dev') && ! $this->canEditMenu('hr_main')) return redirect()->to('/events')->with('error', 'Akses ditolak.');
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
        if (! $this->canEditMenu('people_dev') && ! $this->canEditMenu('hr_main')) return redirect()->to('/events')->with('error', 'Akses ditolak.');
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
        if (! $this->canEditMenu('people_dev') && ! $this->canEditMenu('hr_main')) return redirect()->to('/events')->with('error', 'Akses ditolak.');
        (new EmployeePositionModel())->delete($pid);
        ActivityLog::write('delete', 'employee_position', (string)$pid, '', ['employee_id' => $id]);
        return redirect()->to('/people/employees/' . $id . '#positions')->with('success', 'Riwayat jabatan dihapus.');
    }

    public function storeCertificate(int $id)
    {
        if (! $this->canEditMenu('people_dev') && ! $this->canEditMenu('hr_main')) return redirect()->to('/events')->with('error', 'Akses ditolak.');
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
        if (! $this->canEditMenu('people_dev') && ! $this->canEditMenu('hr_main')) return redirect()->to('/events')->with('error', 'Akses ditolak.');
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
