<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\DepartmentModel;
use App\Models\RoleModel;
use App\Models\LoginLogModel;
use App\Libraries\ActivityLog;

class Users extends BaseController
{
    public function index()
    {
        $users = (new UserModel())->orderBy('name')->findAll();
        $depts = (new DepartmentModel())->orderBy('name')->findAll();
        $roles = (new RoleModel())->orderBy('id')->findAll();

        // Build login logs map: user_id → last 5 logins
        $logModel  = new LoginLogModel();
        $allUserIds = array_column($users, 'id');
        $loginLogs  = [];
        foreach ($allUserIds as $uid) {
            $loginLogs[$uid] = $logModel->getByUser($uid, 5);
        }

        return view('users/index', [
            'user'       => $this->currentUser(),
            'users'      => $users,
            'depts'      => $depts,
            'roles'      => $roles,
            'loginLogs'  => $loginLogs,
        ]);
    }

    public function store()
    {
        $rules = [
            'name'    => 'required|min_length[2]',
            'email'   => 'required|valid_email|is_unique[users.email]',
            'role_id' => 'required|is_natural_no_zero',
        ];
        if (! $this->validate($rules)) {
            return redirect()->to('/users')->with('errors', $this->validator->getErrors());
        }

        $roleId = (int)$this->request->getPost('role_id');
        $role   = (new RoleModel())->find($roleId);
        $deptId = $this->request->getPost('department_id');

        $pass  = bin2hex(random_bytes(4)); // password awal acak, wajib diganti saat login pertama
        $model = new UserModel();
        $newId = $model->insert([
            'name'                 => $this->request->getPost('name'),
            'email'                => $this->request->getPost('email'),
            'password'             => password_hash($pass, PASSWORD_BCRYPT),
            'role'                 => $role ? $role['slug'] : 'operator',
            'role_id'              => $roleId,
            'department_id'        => $deptId ?: null,
            'is_active'            => 1,
            'must_change_password' => 1,
        ]);
        ActivityLog::write('create', 'user', (string)$newId, $this->request->getPost('name'), [
            'email' => $this->request->getPost('email'),
            'role'  => $role ? $role['slug'] : 'operator',
        ]);
        return redirect()->to('/users')->with('success', 'User ditambahkan — Password awal: ' . $pass . ' (wajib diganti saat login pertama).');
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

        ActivityLog::captureBefore($before);
        $userModel->update($id, $data);
        ActivityLog::captureAfter($data);
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
            ActivityLog::captureBefore(['is_active' => $user['is_active'] ? 'Aktif' : 'Nonaktif']);
            ActivityLog::captureAfter(['is_active'  => $newStatus ? 'Aktif' : 'Nonaktif']);
            ActivityLog::write('update', 'user', (string)$id, $user['name']);
        }
        return redirect()->to('/users')->with('success', 'Status user diperbarui.');
    }

    public function unlock(int $id)
    {
        $user = (new UserModel())->find($id);
        if ($user) {
            ActivityLog::captureBefore(['locked_until' => $user['locked_until'], 'failed_login_attempts' => $user['failed_login_attempts']]);
            (new UserModel())->update($id, ['failed_login_attempts' => 0, 'locked_until' => null]);
            ActivityLog::captureAfter(['locked_until' => null, 'failed_login_attempts' => 0]);
            ActivityLog::write('update', 'user', (string)$id, $user['name'], ['action' => 'manual_unlock']);
        }
        return redirect()->to('/users')->with('success', 'Akun berhasil dibuka.');
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

        // Data karyawan yang tertaut ke akun ini (Employee Self-Service)
        $employee = $positions = $certificates = $appraisals = $requests = null;
        $db  = db_connect();
        $emp = $db->table('employees')->select('id')->where('user_id', $id)->get()->getRowArray();
        if ($emp) {
            $employee = (new \App\Models\EmployeeModel())->findWithDept((int) $emp['id']);
            $employee['masa_kerja'] = \App\Models\EmployeeModel::getMasaKerja($employee['tanggal_masuk']);
            $positions    = (new \App\Models\EmployeePositionModel())->getByEmployee($employee['id']);
            $certificates = (new \App\Models\EmployeeCertificateModel())->getByEmployee($employee['id']);
            foreach ($certificates as &$c) {
                $c['status'] = \App\Models\EmployeeCertificateModel::getCertStatus($c['tanggal_kadaluarsa']);
            }
            unset($c);
            $appraisals = $db->table('appraisal_forms f')
                ->select('f.id, f.nilai_akhir, f.skor_kpi, f.skor_kompetensi, f.finalized_at, p.nama AS periode_nama, p.tahun')
                ->join('appraisal_periods p', 'p.id = f.period_id', 'left')
                ->where('f.employee_id', $employee['id'])
                ->where('f.status', 'finalized')
                ->where('f.released_at IS NOT NULL', null, false)
                ->orderBy('f.finalized_at', 'DESC')
                ->get()->getResultArray();
            $requests = (new \App\Models\EmployeeChangeRequestModel())->pendingForEmployee($employee['id']);
            $documents = (new \App\Models\EmployeeDocumentModel())->forEmployee($employee['id']);
        }

        return view('users/profile', [
            'user'         => $user,
            'employee'     => $employee,
            'positions'    => $positions,
            'certificates' => $certificates,
            'appraisals'   => $appraisals,
            'requests'     => $requests,
            'documents'    => $documents ?? null,
            'jenisDok'     => \App\Models\EmployeeDocumentModel::JENIS,
            'editable'     => \App\Models\EmployeeChangeRequestModel::EDITABLE,
        ]);
    }

    // Upload dokumen pribadi (ESS) → menunggu verifikasi HR
    public function uploadDocument()
    {
        $id  = session()->get('user_id');
        $emp = (new \App\Models\EmployeeModel())->where('user_id', $id)->first();
        if (! $emp) return redirect()->to('/profile')->with('error', 'Akun belum terhubung ke data karyawan.');

        $res = $this->storeDocument((int) $emp['id'], $id, 'pending');
        return redirect()->to('/profile')->with($res['ok'] ? 'success' : 'error', $res['msg']);
    }

    /** Simpan file dokumen + buat record. Dipakai ESS (pending) & HR (approved). */
    private function storeDocument(int $employeeId, $uploaderId, string $status): array
    {
        $jenis = $this->request->getPost('jenis');
        $valid = array_keys(\App\Models\EmployeeDocumentModel::JENIS);
        if (! in_array($jenis, $valid, true)) return ['ok' => false, 'msg' => 'Jenis dokumen tidak valid.'];

        $nama = trim((string) $this->request->getPost('nama_dokumen')) ?: null;
        if ($jenis === 'lainnya' && ! $nama) return ['ok' => false, 'msg' => 'Sebutkan nama dokumen untuk jenis "Lainnya".'];

        $file = $this->request->getFile('file');
        if (! $file || ! $file->isValid() || $file->hasMoved()) return ['ok' => false, 'msg' => 'File tidak valid.'];
        $ext = strtolower($file->getExtension());
        if (! in_array($ext, ['jpg', 'jpeg', 'png', 'pdf'], true)) return ['ok' => false, 'msg' => 'Hanya file JPG, PNG, atau PDF.'];
        if ($file->getSize() > 5 * 1024 * 1024) return ['ok' => false, 'msg' => 'Ukuran file maksimal 5 MB.'];

        $dir = WRITEPATH . 'uploads/docs/';
        if (! is_dir($dir)) mkdir($dir, 0775, true);
        $newName = 'doc_' . $employeeId . '_' . time() . '_' . bin2hex(random_bytes(5)) . '.' . $ext;
        $file->move($dir, $newName);

        (new \App\Models\EmployeeDocumentModel())->insert([
            'employee_id'  => $employeeId,
            'jenis'        => $jenis,
            'nama_dokumen' => $nama,
            'file_name'    => $newName,
            'file_asli'    => $file->getClientName(),
            'status'       => $status,
            'uploaded_by'  => $uploaderId,
            'reviewed_by'  => $status === 'approved' ? $uploaderId : null,
            'reviewed_at'  => $status === 'approved' ? date('Y-m-d H:i:s') : null,
        ]);
        ActivityLog::write('create', 'employee_document', (string) $employeeId, \App\Models\EmployeeDocumentModel::jenisLabel($jenis, $nama), ['status' => $status]);
        return ['ok' => true, 'msg' => $status === 'approved' ? 'Dokumen diunggah.' : 'Dokumen diunggah, menunggu verifikasi HR.'];
    }

    // Ajukan perubahan data pribadi (ESS) → approval HR
    public function submitChange()
    {
        $id  = session()->get('user_id');
        $emp = (new \App\Models\EmployeeModel())->where('user_id', $id)->first();
        if (! $emp) return redirect()->to('/profile')->with('error', 'Akun belum terhubung ke data karyawan.');

        $editable = \App\Models\EmployeeChangeRequestModel::EDITABLE;
        $reqModel = new \App\Models\EmployeeChangeRequestModel();
        $created  = 0;

        foreach ($editable as $field => $label) {
            if ($field === 'foto') continue;
            if (! $this->request->getPost($field . '_chk')) continue;
            $new = trim((string) $this->request->getPost($field));
            $old = (string) ($emp[$field] ?? '');
            if ($new === '' || $new === $old) continue;
            if ($reqModel->where('employee_id', $emp['id'])->where('field', $field)->where('status', 'pending')->countAllResults()) continue;
            $reqModel->insert([
                'employee_id' => $emp['id'], 'requested_by' => $id, 'field' => $field,
                'label' => $label, 'value_old' => $old, 'value_new' => $new, 'status' => 'pending',
            ]);
            $created++;
        }

        if ($this->request->getPost('foto_chk')) {
            $file = $this->request->getFile('foto');
            if ($file && $file->isValid() && ! $file->hasMoved() && str_starts_with((string) $file->getMimeType(), 'image/')
                && ! $reqModel->where('employee_id', $emp['id'])->where('field', 'foto')->where('status', 'pending')->countAllResults()) {
                $dir = WRITEPATH . 'uploads/photos/';
                if (! is_dir($dir)) mkdir($dir, 0775, true);
                $name = 'req_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $file->getExtension();
                $file->move($dir, $name);
                $reqModel->insert([
                    'employee_id' => $emp['id'], 'requested_by' => $id, 'field' => 'foto',
                    'label' => 'Foto Profil', 'value_old' => $emp['foto'] ?? '', 'value_new' => $name, 'status' => 'pending',
                ]);
                $created++;
            }
        }

        if ($created === 0) return redirect()->to('/profile')->with('error', 'Tidak ada perubahan untuk diajukan (atau sudah ada pengajuan pending yang sama).');
        ActivityLog::write('create', 'employee_change_request', (string) $emp['id'], $emp['nama'], ['jumlah_field' => $created]);
        return redirect()->to('/profile')->with('success', "$created pengajuan perubahan dikirim. Menunggu persetujuan HR.");
    }

    public function updateProfile()
    {
        $post = $this->request->getPost();
        $id   = session()->get('user_id');
        $data = ['name' => $post['name']];

        if (! empty($post['password'])) {
            $password = $post['password'];
            if ($password !== ($post['password_confirm'] ?? '')) {
                return redirect()->to('/profile')->with('error', 'Konfirmasi password tidak cocok.');
            }
            $errors = [];
            if (strlen($password) < 8)              $errors[] = 'Minimal 8 karakter.';
            if (! preg_match('/[A-Z]/', $password)) $errors[] = 'Minimal 1 huruf kapital.';
            if (! preg_match('/[a-z]/', $password)) $errors[] = 'Minimal 1 huruf kecil.';
            if (! preg_match('/[0-9]/', $password)) $errors[] = 'Minimal 1 angka.';
            if (! preg_match('/[\W_]/', $password)) $errors[] = 'Minimal 1 karakter simbol (!@#$% dll).';
            if ($errors) {
                return redirect()->to('/profile')->with('error', 'Password belum memenuhi syarat: ' . implode(' ', $errors));
            }
            $data['password'] = password_hash($password, PASSWORD_BCRYPT);
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
