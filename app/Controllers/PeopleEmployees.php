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
        if (($role['slug'] ?? '') === 'admin') {
            return redirect()->to('/people/employees/' . $id)->with('error', 'Role Admin tidak dapat diberikan dari sini. Buat akun Admin lewat menu Users.');
        }

        $pass = bin2hex(random_bytes(4)); // password awal acak (8 char), wajib diganti saat login pertama
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

        // Kirim email pemberitahuan akun + kredensial awal ke karyawan (gagal kirim tak membatalkan pembuatan akun).
        $mailOk = $this->sendAccountEmail($email, $emp['nama'], $pass);
        $mailNote = $mailOk ? ' Email pemberitahuan terkirim ke karyawan.' : ' ⚠️ Email gagal dikirim — sampaikan kredensial secara manual.';

        return redirect()->to('/people/employees/' . $id)
            ->with('success', "Akun login dibuat — Email: {$email} · Password awal: {$pass} (wajib diganti saat login pertama)." . $mailNote);
    }

    // Kirim email onboarding akun (kredensial awal + link login). Return true jika terkirim.
    private function sendAccountEmail(string $to, string $nama, string $pass): bool
    {
        try {
            $loginUrl = base_url('login');
            $logoUrl  = base_url('img/mic-logo.png');
            $html = '<div style="font-family:Arial,sans-serif;max-width:520px;margin:auto;color:#1e293b;border:1px solid #e2e8f0;border-radius:10px;overflow:hidden">'
                . '<div style="background:#0b1220;text-align:center;padding:22px 0">'
                . '<img src="' . $logoUrl . '" alt="Mall Intelligence Center" style="height:60px;object-fit:contain">'
                . '</div>'
                . '<div style="padding:24px">'
                . '<h2 style="color:#0f172a;margin-top:0">Akun Login Anda</h2>'
                . '<p>Halo <strong>' . esc($nama) . '</strong>,</p>'
                . '<p>Akun login Anda untuk sistem <strong>Mall Intelligence Center</strong> telah dibuat oleh HR. Berikut kredensial awal Anda:</p>'
                . '<table style="border-collapse:collapse;margin:12px 0">'
                . '<tr><td style="padding:6px 12px;background:#f1f5f9"><strong>Email</strong></td><td style="padding:6px 12px;background:#f8fafc">' . esc($to) . '</td></tr>'
                . '<tr><td style="padding:6px 12px;background:#f1f5f9"><strong>Password awal</strong></td><td style="padding:6px 12px;background:#f8fafc"><code>' . esc($pass) . '</code></td></tr>'
                . '</table>'
                . '<p style="color:#b45309"><strong>Penting:</strong> demi keamanan, Anda akan diminta <strong>mengganti password</strong> saat login pertama.</p>'
                . '<p style="margin:20px 0"><a href="' . $loginUrl . '" style="background:#6366f1;color:#fff;padding:10px 20px;border-radius:6px;text-decoration:none">Masuk ke Sistem</a></p>'
                . '<p style="font-size:12px;color:#64748b">Atau buka: ' . $loginUrl . '<br>Jika Anda merasa tidak seharusnya menerima email ini, abaikan saja.</p>'
                . '</div>'   // tutup padding content
                . '</div>';  // tutup kartu luar

            $emailer = \Config\Services::email();
            $emailer->setTo($to);
            $emailer->setSubject('Akun Login Anda — Mall Intelligence Center');
            $emailer->setMailType('html');
            $emailer->setMessage($html);
            return (bool) $emailer->send();
        } catch (\Throwable $e) {
            log_message('error', 'Gagal kirim email akun: ' . $e->getMessage());
            return false;
        }
    }

    // Tautkan karyawan ke akun user yang SUDAH ADA (bukan buat baru)
    public function linkAccount(int $id)
    {
        if (! $this->canEditMenu('people_dev') && ! $this->canEditMenu('hr_main')) return redirect()->to('/events')->with('error', 'Akses ditolak.');
        $emp = (new EmployeeModel())->find($id);
        if (! $emp) return redirect()->to('/people/employees')->with('error', 'Karyawan tidak ditemukan.');
        if (! empty($emp['user_id'])) return redirect()->to('/people/employees/' . $id)->with('error', 'Karyawan ini sudah punya akun.');

        $userId = (int) $this->request->getPost('user_id');
        $user = $userId ? (new UserModel())->find($userId) : null;
        if (! $user) return redirect()->to('/people/employees/' . $id)->with('error', 'Akun tidak valid.');
        // Pastikan akun belum tertaut ke karyawan lain
        if ((new EmployeeModel())->where('user_id', $userId)->where('id !=', $id)->first()) {
            return redirect()->to('/people/employees/' . $id)->with('error', 'Akun ini sudah tertaut ke karyawan lain.');
        }

        (new EmployeeModel())->update($id, ['user_id' => $userId]);
        ActivityLog::write('update', 'employee', (string) $id, $emp['nama'], ['tautkan_akun' => $user['email']]);
        return redirect()->to('/people/employees/' . $id . '#account')->with('success', 'Karyawan ditautkan ke akun ' . esc($user['email']) . '.');
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

    // Serve file sertifikat karyawan lewat auth (HR/People Dev atau karyawan pemilik).
    public function viewCertificate(int $id)
    {
        $cert = (new EmployeeCertificateModel())->find($id);
        if (! $cert || empty($cert['file_name'])) return $this->response->setStatusCode(404)->setBody('Tidak ditemukan.');

        $allowed = $this->canManageRequests();
        if (! $allowed) {
            $emp = (new EmployeeModel())->find($cert['employee_id']);
            $allowed = $emp && ! empty($emp['user_id']) && (int) $emp['user_id'] === (int) session()->get('user_id');
        }
        if (! $allowed) return $this->response->setStatusCode(403)->setBody('Akses ditolak.');

        $path = WRITEPATH . 'uploads/certificates/' . basename($cert['file_name']);
        if (! is_file($path)) return $this->response->setStatusCode(404)->setBody('File tidak ditemukan.');

        $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = ['pdf' => 'application/pdf', 'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg'][$ext] ?? 'application/octet-stream';
        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('Content-Disposition', 'inline; filename="' . basename($cert['file_name']) . '"')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setBody(file_get_contents($path));
    }

    // Serve foto karyawan lewat auth (tidak publik). Akses cukup login (foto wajah, sensitivitas rendah).
    public function viewPhoto(string $name)
    {
        $name = basename($name); // cegah path traversal
        $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            return $this->response->setStatusCode(404)->setBody('Not found.');
        }
        $path = WRITEPATH . 'uploads/photos/' . $name;
        if (! is_file($path)) return $this->response->setStatusCode(404)->setBody('Not found.');

        $mime = ['png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'webp' => 'image/webp', 'gif' => 'image/gif'][$ext] ?? 'application/octet-stream';
        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('Cache-Control', 'private, max-age=86400')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setBody(file_get_contents($path));
    }

    // Serve file dokumen lewat auth (PII — tidak boleh diakses publik langsung).
    public function viewDocument(int $id)
    {
        $doc = (new EmployeeDocumentModel())->find($id);
        if (! $doc) return $this->response->setStatusCode(404)->setBody('Dokumen tidak ditemukan.');

        // Akses: HR/People Dev (boleh semua) atau karyawan pemilik dokumen.
        $allowed = $this->canManageRequests();
        if (! $allowed) {
            $emp = (new EmployeeModel())->find($doc['employee_id']);
            $allowed = $emp && ! empty($emp['user_id']) && (int) $emp['user_id'] === (int) session()->get('user_id');
        }
        if (! $allowed) return $this->response->setStatusCode(403)->setBody('Akses ditolak.');

        $path = WRITEPATH . 'uploads/docs/' . basename($doc['file_name']);
        if (! is_file($path)) return $this->response->setStatusCode(404)->setBody('File tidak ditemukan.');

        $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = ['pdf' => 'application/pdf', 'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg'][$ext] ?? 'application/octet-stream';

        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('Content-Disposition', 'inline; filename="' . basename($doc['file_name']) . '"')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setBody(file_get_contents($path));
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

        $dir = WRITEPATH . 'uploads/docs/';
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
        $path = WRITEPATH . 'uploads/docs/' . $doc['file_name'];
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
        $path = WRITEPATH . 'uploads/docs/' . $doc['file_name'];
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
        // Defense-in-depth: hanya field whitelist yang boleh ditulis ke employees.
        if (! array_key_exists($req['field'], EmployeeChangeRequestModel::EDITABLE)) {
            return redirect()->to('/people/change-requests')->with('error', 'Field tidak diizinkan.');
        }

        $empModel = new EmployeeModel();
        $emp = $empModel->find($req['employee_id']);
        if (! $emp) return redirect()->to('/people/change-requests')->with('error', 'Karyawan tidak ditemukan.');

        // Foto: bersihkan file lama setelah commit nilai baru
        if ($req['field'] === 'foto') {
            $dir = WRITEPATH . 'uploads/photos/';
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
            $dir = WRITEPATH . 'uploads/photos/';
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
            'roles'             => (new RoleModel())->where('slug !=', 'admin')->orderBy('name')->findAll(),
            'linkedUser'        => $employee['user_id'] ? (new UserModel())->find($employee['user_id']) : null,
            'unlinkedUsers'     => $employee['user_id'] ? [] : (new UserModel())
                ->select('users.id, users.name, users.email, users.role')
                ->where('users.is_active', 1)
                ->where("users.id NOT IN (SELECT user_id FROM employees WHERE user_id IS NOT NULL)", null, false)
                ->orderBy('users.name')->findAll(),
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
            $uploadPath = WRITEPATH . 'uploads/photos/';
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
            $uploadPath = WRITEPATH . 'uploads/photos/';
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
            if ($employee['foto'] && is_file(WRITEPATH . 'uploads/photos/' . $employee['foto']))
                unlink(WRITEPATH . 'uploads/photos/' . $employee['foto']);
            foreach ($certs as $c) {
                if ($c['file_name'] && is_file(WRITEPATH . 'uploads/certificates/' . $c['file_name']))
                    unlink(WRITEPATH . 'uploads/certificates/' . $c['file_name']);
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
            $uploadPath = WRITEPATH . 'uploads/certificates/';
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
            $path = WRITEPATH . 'uploads/certificates/' . $cert['file_name'];
            if (file_exists($path)) unlink($path);
        }

        ActivityLog::write('delete', 'employee_certificate', (string)$cid, $cert['nama_sertifikat'] ?? '', ['employee_id' => $id]);
        return redirect()->to('/people/employees/' . $id . '#certificates')->with('success', 'Sertifikat dihapus.');
    }
}
