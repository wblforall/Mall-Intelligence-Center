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

        // Role/departemen berubah → paksa user login ulang agar akses baru berlaku.
        if (($before['role_id'] ?? null) != $data['role_id'] || ($before['department_id'] ?? null) != $data['department_id']) {
            $data['perms_changed_at'] = date('Y-m-d H:i:s');
        }
        ActivityLog::captureBefore($before);
        $userModel->update($id, $data);
        ActivityLog::captureAfter($data);
        ActivityLog::write('update', 'user', (string)$id, $before['name'] ?? '', [
            'before' => ['name' => $before['name'], 'role' => $before['role'], 'department_id' => $before['department_id']],
            'after'  => ['name' => $data['name'],   'role' => $data['role'],   'department_id' => $data['department_id']],
            'password_changed' => !empty($post['password']),
        ]);

        // Pindah departemen: grant khusus per-user TIDAK ikut terhapus otomatis
        // (bisa jadi memang masih diperlukan). Ingatkan admin untuk meninjau.
        $pindahDept = ($before['department_id'] ?? null) != $data['department_id'];
        $jmlGrant   = $pindahDept
            ? db_connect()->table('user_menu_access')->where('user_id', $id)->countAllResults()
            : 0;
        if ($jmlGrant > 0) {
            return redirect()->to('users/' . $id . '/menu-access')->with('warning',
                'User pindah departemen, tapi masih punya ' . $jmlGrant . ' akses khusus dari departemen lama. '
                . 'Tinjau di bawah — hapus centang yang sudah tidak relevan lalu Simpan.');
        }
        return redirect()->to('/users')->with('success', 'User berhasil diperbarui.');
    }

    /** Halaman tinjauan: siapa memegang menu apa (lihat/edit/setujui). */
    public function tinjauAkses()
    {
        $db     = db_connect();
        $labels = \App\Libraries\SectionConfig::MENU_LABELS;

        $deptAkses = [];
        foreach ($db->table('department_menu_access dma')
            ->select('dma.menu_key, dma.can_view, dma.can_edit, dma.can_approve, d.name AS dept, d.id AS dept_id')
            ->join('departments d', 'd.id = dma.department_id')
            ->get()->getResultArray() as $r) {
            $deptAkses[$r['menu_key']][] = $r;
        }

        $userAkses = [];
        foreach ($db->table('user_menu_access uma')
            ->select('uma.menu_key, uma.can_view, uma.can_edit, uma.can_approve, u.id AS user_id, u.name, u.is_active, d.name AS dept')
            ->join('users u', 'u.id = uma.user_id')
            ->join('departments d', 'd.id = u.department_id', 'left')
            ->orderBy('u.name')->get()->getResultArray() as $r) {
            $userAkses[$r['menu_key']][] = $r;
        }

        // Admin = bypass total, perlu ditampilkan agar tinjauan tidak menyesatkan.
        $admins = $db->table('users')->select('id, name, is_active')
            ->where('role', 'admin')->where('is_active', 1)->orderBy('name')->get()->getResultArray();

        // Grant per-user yang SUDAH dicakup akses departemennya → kandidat dibersihkan.
        $redundan = [];
        foreach ($db->table('user_menu_access uma')
            ->select('uma.menu_key, uma.can_view, uma.can_edit, uma.can_approve, u.id AS user_id, u.name,
                      d.name AS dept, dma.can_view AS d_view, dma.can_edit AS d_edit, dma.can_approve AS d_approve')
            ->join('users u', 'u.id = uma.user_id')
            ->join('departments d', 'd.id = u.department_id', 'left')
            ->join('department_menu_access dma', 'dma.department_id = u.department_id AND dma.menu_key = uma.menu_key')
            ->where('u.role !=', 'admin')
            ->orderBy('u.name')->get()->getResultArray() as $r) {
            $tercakup = (empty($r['can_view'])    || ! empty($r['d_view']))
                     && (empty($r['can_edit'])    || ! empty($r['d_edit']))
                     && (empty($r['can_approve']) || ! empty($r['d_approve']));
            if (! $tercakup) continue;
            $redundan[$r['user_id']]['nama']    = $r['name'];
            $redundan[$r['user_id']]['dept']    = $r['dept'];
            $redundan[$r['user_id']]['menus'][] = $labels[$r['menu_key']] ?? $r['menu_key'];
        }

        return view('users/tinjau_akses', [
            'user'       => $this->currentUser(),
            'menuLabels' => $labels,
            'deptAkses'  => $deptAkses,
            'userAkses'  => $userAkses,
            'admins'     => $admins,
            'redundan'   => $redundan,
        ]);
    }

    /** Hapus semua grant per-user yang sudah dicakup akses departemennya. */
    public function bersihkanGrant(int $id)
    {
        $user = (new UserModel())->find($id);
        if (! $user) return redirect()->to('users/akses')->with('error', 'User tidak ditemukan.');

        $db   = db_connect();
        $dept = (new \App\Models\DepartmentMenuModel())->getMenuMap((int) ($user['department_id'] ?? 0));
        $umm  = new \App\Models\UserMenuModel();

        $hapus = [];
        foreach ($umm->getByUser($id) as $r) {
            $d = $dept[$r['menu_key']] ?? null;
            if (! $d) continue;
            $tercakup = (empty($r['can_view'])    || ! empty($d['can_view']))
                     && (empty($r['can_edit'])    || ! empty($d['can_edit']))
                     && (empty($r['can_approve']) || ! empty($d['can_approve']));
            if ($tercakup) $hapus[] = $r['menu_key'];
        }
        if ($hapus) {
            $db->table('user_menu_access')->where('user_id', $id)->whereIn('menu_key', $hapus)->delete();
            $db->table('users')->where('id', $id)->update(['perms_changed_at' => date('Y-m-d H:i:s')]);
            ActivityLog::write('delete', 'user', (string) $id, $user['name'], ['grant_redundan_dihapus' => $hapus]);
        }

        return redirect()->to('users/akses')->with('success',
            count($hapus) . ' akses khusus milik ' . esc($user['name']) . ' yang sudah dicakup departemen dihapus.');
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
        (new \App\Models\EmployeeModel())->where('user_id', $id)->set(['user_id' => null])->update();
        ActivityLog::write('delete', 'user', (string)$id, $user['name'] ?? '', [
            'email' => $user['email'] ?? '', 'role' => $user['role'] ?? '',
        ]);
        return redirect()->to('/users')->with('success', 'User berhasil dihapus.');
    }

    // Kelola akses menu tambahan (override) per user — additive di atas akses dept.
    public function menuAccess(int $id)
    {
        $user = (new UserModel())->find($id);
        if (! $user) return redirect()->to('/users')->with('error', 'User tidak ditemukan.');
        $db   = db_connect();
        $dept = ! empty($user['department_id'])
            ? $db->table('departments')->select('name')->where('id', $user['department_id'])->get()->getRowArray()
            : null;

        // Kandidat "samakan dengan" — user aktif yang PUNYA grant khusus,
        // beserta jumlah menunya, agar admin bisa menilai sebelum menyalin.
        $userLain = $db->table('users u')
            ->select('u.id, u.name, d.name AS dept, COUNT(uma.id) AS jml')
            ->join('user_menu_access uma', 'uma.user_id = u.id')
            ->join('departments d', 'd.id = u.department_id', 'left')
            ->where('u.is_active', 1)->where('u.id !=', $id)
            ->groupBy('u.id')->orderBy('u.name')->get()->getResultArray();

        return view('users/menu_access', [
            'user'       => $this->currentUser(),
            'target'     => $user,
            'menuLabels' => \App\Libraries\SectionConfig::MENU_LABELS,
            'access'     => (new \App\Models\UserMenuModel())->getMenuMap($id),
            'deptNama'   => $dept['name'] ?? null,
            'deptAccess' => ! empty($user['department_id'])
                ? (new \App\Models\DepartmentMenuModel())->getMenuMap((int) $user['department_id'])
                : [],
            'userLain'   => $userLain,
        ]);
    }

    /** Salin seluruh akses menu dari user lain (menimpa yang ada). */
    public function salinMenuAccess(int $id)
    {
        $user = (new UserModel())->find($id);
        if (! $user) return redirect()->to('/users')->with('error', 'User tidak ditemukan.');

        $sumberId = (int) $this->request->getPost('sumber_user_id');
        $sumber   = $sumberId ? (new UserModel())->find($sumberId) : null;
        if (! $sumber) return redirect()->back()->with('error', 'User sumber tidak valid.');

        $n = (new \App\Models\UserMenuModel())->copyFrom($sumberId, $id);
        db_connect()->table('users')->where('id', $id)->update(['perms_changed_at' => date('Y-m-d H:i:s')]);
        ActivityLog::write('update', 'user', (string) $id, $user['name'], ['salin_akses_dari' => $sumber['name'], 'jumlah_menu' => $n]);

        return redirect()->to('users/' . $id . '/menu-access')
            ->with('success', "Akses disalin dari {$sumber['name']} ({$n} menu). Periksa & sesuaikan bila perlu, lalu Simpan.");
    }

    public function saveMenuAccess(int $id)
    {
        $user = (new UserModel())->find($id);
        if (! $user) return redirect()->to('/users')->with('error', 'User tidak ditemukan.');

        $postMenus = $this->request->getPost('menus') ?? [];
        $menuData  = [];
        foreach (array_keys(\App\Libraries\SectionConfig::MENU_LABELS) as $key) {
            $menuData[$key] = [
                'can_view'    => isset($postMenus[$key]['can_view']) ? 1 : 0,
                'can_edit'    => isset($postMenus[$key]['can_edit']) ? 1 : 0,
                'can_approve' => isset($postMenus[$key]['can_approve']) ? 1 : 0,
            ];
        }
        (new \App\Models\UserMenuModel())->saveMenuAccess($id, $menuData);
        db_connect()->table('users')->where('id', $id)->update(['perms_changed_at' => date('Y-m-d H:i:s')]); // paksa login ulang
        ActivityLog::write('update', 'user', (string) $id, $user['name'], ['akses_menu_override' => true]);
        return redirect()->to('/users')->with('success', 'Akses menu tambahan untuk ' . esc($user['name']) . ' disimpan. User akan diminta login ulang.');
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
                // Disimpan sebagai `masa_berlaku`, BUKAN `status` — sejak
                // sertifikat punya alur verifikasi, `status` adalah kolom DB
                // (pending/approved/rejected) dan menimpanya akan menyembunyikan
                // apakah sertifikat itu sudah diverifikasi HR atau belum.
                $c['masa_berlaku'] = \App\Models\EmployeeCertificateModel::getCertStatus($c['tanggal_kadaluarsa']);
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
            $docModel  = new \App\Models\EmployeeDocumentModel();
            $documents = $docModel->forEmployee($employee['id']);
            $kelengkapan = $docModel->kelengkapanWajib((int) $employee['id']);
        }

        return view('users/profile', [
            'user'         => $user,
            'employee'     => $employee,
            'positions'    => $positions,
            'certificates' => $certificates,
            'appraisals'   => $appraisals,
            'requests'     => $requests,
            'documents'    => $documents ?? null,
            'kelengkapan'  => $kelengkapan ?? null,
            'jenisDok'     => \App\Models\EmployeeDocumentModel::JENIS,
            'jenisSertifikat' => \App\Models\EmployeeCertificateModel::JENIS,
            'levelSertifikat' => \App\Models\EmployeeCertificateModel::LEVEL,
            'pembiayaanSertifikat' => \App\Models\EmployeeCertificateModel::PEMBIAYAAN,
            'editable'     => \App\Models\EmployeeChangeRequestModel::EDITABLE,
        ]);
    }

    // Upload dokumen pribadi (ESS) → menunggu verifikasi HR
    public function uploadDocument()
    {
        $id  = session()->get('user_id');
        $emp = (new \App\Models\EmployeeModel())->where('user_id', $id)->first();
        if (! $emp) return redirect()->to('/profile')->with('error', 'Akun belum terhubung ke data karyawan.');

        $jenis = (string) $this->request->getPost('jenis');
        $nomor = trim((string) $this->request->getPost('nomor_identitas'));
        $field = \App\Models\EmployeeDocumentModel::PASANGAN_NOMOR[$jenis] ?? null;

        // Nomor WAJIB untuk KTP/KK/NPWP, dan diperiksa SEBELUM berkas disimpan.
        // Kalau divalidasi sesudahnya, unggahan yang ditolak akan meninggalkan
        // file yatim di disk tanpa baris yang menunjuknya.
        if ($field) {
            $label = \App\Models\EmployeeChangeRequestModel::EDITABLE[$field] ?? $field;
            if ($nomor === '') {
                return redirect()->to('/profile')->with('error', $label . ' wajib diisi saat mengunggah dokumen ini.');
            }
            if ($err = self::validasiNomorIdentitas($field, $nomor)) {
                return redirect()->to('/profile')->with('error', $err);
            }
        }

        $res = $this->storeDocument((int) $emp['id'], $id, 'pending');
        if (! $res['ok']) return redirect()->to('/profile')->with('error', $res['msg']);

        // Nomor diajukan dari form yang sama supaya kartu dan nomornya tak
        // pernah lagi terpisah — dulu karyawan hampir selalu hanya mengunggah
        // kartunya dan kolom nomor tertinggal kosong tanpa ada yang menyadari.
        $pesan   = $res['msg'];
        $catatan = $this->ajukanNomorDariDokumen($emp, (int) $id, $jenis, $nomor);
        if ($catatan) $pesan .= ' ' . $catatan;

        // Kartu NPWP memuat dua nomor sekaligus. NPWP-16 opsional — kartu
        // terbitan lama belum mencantumkannya, jadi kosong bukan kesalahan.
        if ($jenis === 'npwp') {
            $n16 = trim((string) $this->request->getPost('nomor_npwp16'));
            if ($n16 !== '') {
                $err16 = self::validasiNomorIdentitas('no_npwp16', $n16);
                $pesan .= $err16
                    ? ' Namun NPWP-16 tidak disimpan: ' . $err16
                    : ' ' . (string) $this->ajukanNomorLangsung($emp, (int) $id, 'no_npwp16', $n16);
            }
        }

        return redirect()->to('/profile')->with('success', trim($pesan));
    }

    /**
     * Buat pengajuan perubahan untuk nomor identitas yang diisi bersamaan
     * dengan unggahan kartunya. Mengembalikan keterangan tambahan untuk
     * pesan ke karyawan, atau null bila tak ada yang perlu diajukan.
     */
    private function ajukanNomorDariDokumen(array $emp, int $userId, string $jenis, string $nomor): ?string
    {
        // Kesahihan & kewajiban nomor sudah diperiksa di uploadDocument sebelum
        // berkas disimpan; di sini tinggal mencatat pengajuannya.
        $field = \App\Models\EmployeeDocumentModel::PASANGAN_NOMOR[$jenis] ?? null;
        if (! $field || $nomor === '') return null;

        return $this->ajukanNomorLangsung($emp, $userId, $field, $nomor);
    }

    /** Catat pengajuan perubahan untuk satu kolom nomor. */
    private function ajukanNomorLangsung(array $emp, int $userId, string $field, string $nomor): ?string
    {
        $label = \App\Models\EmployeeChangeRequestModel::EDITABLE[$field] ?? $field;
        $lama  = (string) ($emp[$field] ?? '');
        if ($nomor === $lama) return null;

        $m = new \App\Models\EmployeeChangeRequestModel();
        if ($m->where('employee_id', $emp['id'])->where('field', $field)->where('status', 'pending')->countAllResults()) {
            return 'Nomor tidak diajukan ulang karena sudah ada pengajuan ' . $label . ' yang menunggu.';
        }

        $m->insert([
            'employee_id' => $emp['id'], 'requested_by' => $userId, 'field' => $field,
            'label' => $label, 'value_old' => $lama, 'value_new' => $nomor, 'status' => 'pending',
        ]);
        ActivityLog::write('create', 'employee_change_request', (string) $emp['id'], $emp['nama'],
            ['field' => $field, 'via' => 'unggah_dokumen']);

        return $label . ' ikut diajukan untuk diverifikasi.';
    }

    /**
     * Karyawan membersihkan dokumennya sendiri yang BELUM terverifikasi.
     *
     * Dokumen yang ditolak berkasnya sudah dibuang saat penolakan, tapi
     * barisnya bertahan agar alasannya terbaca — dan menumpuk selamanya di
     * daftar karyawan. Setelah alasannya dibaca dan diunggah ulang, baris itu
     * tak berguna lagi. Yang `approved` tetap terkunci (hanya HR), sama
     * seperti aturan sertifikat: jejak verifikasi tak boleh dicabut sendiri.
     */
    public function deleteDocument(int $docId)
    {
        $id  = session()->get('user_id');
        $emp = (new \App\Models\EmployeeModel())->where('user_id', $id)->first();
        if (! $emp) return redirect()->to('/profile')->with('error', 'Akun belum terhubung ke data karyawan.');

        $m   = new \App\Models\EmployeeDocumentModel();
        $doc = $m->find($docId);

        if (! $doc || (int) $doc['employee_id'] !== (int) $emp['id']) {
            return redirect()->to('/profile')->with('error', 'Dokumen tidak ditemukan.');
        }
        if ($doc['status'] === 'approved') {
            return redirect()->to('/profile')->with('error',
                'Dokumen yang sudah diverifikasi HR tidak dapat dihapus sendiri. Hubungi HR bila perlu diganti.');
        }

        if (! empty($doc['file_name'])) {
            $path = WRITEPATH . 'uploads/docs/' . $doc['file_name'];
            if (is_file($path)) @unlink($path);
        }
        $m->delete($docId);
        ActivityLog::write('delete', 'employee_document', (string) $emp['id'],
            \App\Models\EmployeeDocumentModel::jenisLabel($doc['jenis'], $doc['nama_dokumen']),
            ['status_saat_dihapus' => $doc['status'], 'via' => 'karyawan']);

        return redirect()->to('/profile')->with('success', 'Dokumen dihapus dari daftar Anda.');
    }

    /** Karyawan mengajukan sertifikatnya sendiri — masuk sebagai `pending`. */
    public function storeCertificate()
    {
        $id  = session()->get('user_id');
        $emp = (new \App\Models\EmployeeModel())->where('user_id', $id)->first();
        if (! $emp) return redirect()->to('/profile')->with('error', 'Akun belum terhubung ke data karyawan.');

        $res = (new \App\Services\EmployeeCertificateService())->simpan(
            (int) $emp['id'], $this->request->getPost(), $this->request->getFile('file_sertifikat'),
            (int) $id, 'pending'
        );
        if (! $res['ok']) return redirect()->to('/profile')->with('error', $res['msg']);

        ActivityLog::write('create', 'employee_certificate', (string) $res['id'],
            trim((string) $this->request->getPost('nama_sertifikat')),
            ['employee_id' => $emp['id'], 'via' => 'pengajuan_karyawan']);

        \App\Libraries\Notify::send(
            \App\Libraries\OrgRecipients::orAdmins(array_merge(
                \App\Libraries\OrgRecipients::menuEditors('hr_main'),
                \App\Libraries\OrgRecipients::menuEditors('people_dev')
            )),
            (int) $id, 'hr', 'approval',
            'Sertifikat baru menunggu verifikasi: ' . $emp['nama'],
            trim((string) $this->request->getPost('nama_sertifikat')),
            'employee_certificate', (int) $res['id'], 'people/change-requests'
        );

        return redirect()->to('/profile')->with('success', $res['msg']);
    }

    /**
     * Karyawan menarik kembali sertifikat yang BELUM diverifikasi.
     *
     * Yang sudah `approved` sengaja tidak bisa dihapus sendiri: jejak
     * verifikasi HR tak boleh dicabut diam-diam. Untuk mengubahnya, ajukan
     * penggantian dan biarkan HR yang menghapus versi lama.
     */
    public function deleteCertificate(int $cid)
    {
        $id  = session()->get('user_id');
        $emp = (new \App\Models\EmployeeModel())->where('user_id', $id)->first();
        if (! $emp) return redirect()->to('/profile')->with('error', 'Akun belum terhubung ke data karyawan.');

        $m    = new \App\Models\EmployeeCertificateModel();
        $cert = $m->find($cid);

        if (! $cert || (int) $cert['employee_id'] !== (int) $emp['id']) {
            return redirect()->to('/profile')->with('error', 'Sertifikat tidak ditemukan.');
        }
        if ($cert['status'] === 'approved') {
            return redirect()->to('/profile')->with('error',
                'Sertifikat yang sudah diverifikasi HR tidak dapat dihapus sendiri. Hubungi HR bila perlu diubah.');
        }

        $m->delete($cid);
        (new \App\Services\EmployeeCertificateService())->hapusBerkas($cert['file_name']);
        ActivityLog::write('delete', 'employee_certificate', (string) $cid, $cert['nama_sertifikat'],
            ['employee_id' => $emp['id'], 'via' => 'pengajuan_karyawan']);

        return redirect()->to('/profile')->with('success', 'Pengajuan sertifikat dibatalkan.');
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
        \App\Libraries\ImageCompressor::compress($dir . '/' . $newName);

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
        $tolak    = [];

        // Jenjang yang berlaku untuk pengajuan ini: yang sedang diajukan bila
        // dicentang, kalau tidak ya yang tersimpan sekarang. Dipakai untuk
        // memutuskan apakah IPK relevan — tanpa ini karyawan bisa mengirim IPK
        // untuk jenjang SMA dan angkanya diam-diam tersimpan.
        $jenjangBaru = trim((string) $this->request->getPost('pendidikan'));
        $jenjang = ($this->request->getPost('pendidikan_chk') && $jenjangBaru !== '')
            ? $jenjangBaru
            : (string) ($emp['pendidikan'] ?? '');

        foreach ($editable as $field => $label) {
            if ($field === 'foto') continue;
            if (! $this->request->getPost($field . '_chk')) continue;
            $new = trim((string) $this->request->getPost($field));
            $old = (string) ($emp[$field] ?? '');
            if ($new === '' || $new === $old) continue;

            $salah = $this->validasiFieldPengajuan($field, $new, $jenjang);
            if ($salah !== null) { $tolak[] = $salah; continue; }

            // "3,45" → "3.45". Tanpa ini kolom DECIMAL memotongnya jadi 3.00
            // tanpa error, dan IPK tersimpan salah tanpa jejak.
            if ($field === 'ipk') {
                $new = number_format((float) str_replace(',', '.', $new), 2, '.', '');
                if ($new === $old) continue;
            }

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
                \App\Libraries\ImageCompressor::compress($dir . '/' . $name);
                $reqModel->insert([
                    'employee_id' => $emp['id'], 'requested_by' => $id, 'field' => 'foto',
                    'label' => 'Foto Profil', 'value_old' => $emp['foto'] ?? '', 'value_new' => $name, 'status' => 'pending',
                ]);
                $created++;
            }
        }

        // Data yang ditolak jangan hilang diam-diam — karyawan harus tahu
        // persis mana yang tidak masuk dan kenapa, supaya bisa mengulang.
        if ($created === 0) {
            return redirect()->to('/profile')->with('error', $tolak
                ? implode(' ', $tolak)
                : 'Tidak ada perubahan untuk diajukan (atau sudah ada pengajuan pending yang sama).');
        }
        ActivityLog::write('create', 'employee_change_request', (string) $emp['id'], $emp['nama'], ['jumlah_field' => $created]);

        // Notifikasi ke pengelola data (HR / People Dev)
        \App\Libraries\Notify::send(
            \App\Libraries\OrgRecipients::orAdmins(array_merge(
                \App\Libraries\OrgRecipients::menuEditors('hr_main'),
                \App\Libraries\OrgRecipients::menuEditors('people_dev')
            )),
            (int) $id, 'hr', 'approval',
            'Pengajuan perubahan data: ' . $emp['nama'],
            $created . ' field menunggu verifikasi.', 'employee_change_request', (int) $emp['id'], 'people/change-requests'
        );

        $pesan = "$created pengajuan perubahan dikirim. Menunggu persetujuan HR.";
        if ($tolak) $pesan .= ' Namun: ' . implode(' ', $tolak);

        return redirect()->to('/profile')->with('success', $pesan);
    }

    /**
     * Validasi satu field pengajuan ESS. Mengembalikan pesan penolakan, atau
     * null bila lolos.
     *
     * Dijalankan di server dan bukan hanya di form: kolom `pendidikan` sempat
     * terisi 120 variasi teks bebas hasil impor lama, dan kontrol di sisi
     * browser saja tidak menahan kiriman POST langsung.
     *
     * @param string $jenjang Jenjang yang berlaku untuk pengajuan ini.
     */
    private function validasiFieldPengajuan(string $field, string $nilai, string $jenjang): ?string
    {
        $M = \App\Models\EmployeeChangeRequestModel::class;

        if ($field === 'pendidikan' && ! in_array($nilai, $M::JENJANG, true)) {
            return 'Jenjang pendidikan harus dipilih dari daftar yang tersedia.';
        }

        if ($field === 'ipk') {
            if (! $M::jenjangPunyaIpk($jenjang)) {
                // Bisa terjadi karena jenjang tersimpan masih berupa teks lama
                // ("UNIBA", "SMU"), bukan karena karyawannya salah. Arahkan ke
                // solusinya, jangan sekadar menolak.
                return 'IPK hanya berlaku untuk jenjang D1 ke atas — centang juga "Pendidikan Terakhir" dan pilih jenjang Anda pada pengajuan yang sama.';
            }
            if (! is_numeric(str_replace(',', '.', $nilai))) {
                return 'IPK harus berupa angka, contoh: 3.45.';
            }
            $ipk = (float) str_replace(',', '.', $nilai);
            if ($ipk < 0 || $ipk > 4) {
                return 'IPK harus berada di rentang 0,00 sampai 4,00.';
            }
        }

        if ($field === 'tahun_lulus') {
            if (! ctype_digit($nilai)) return 'Tahun lulus harus berupa angka 4 digit, contoh: 2015.';
            $th = (int) $nilai;
            if ($th < 1950 || $th > (int) date('Y')) {
                return 'Tahun lulus harus antara 1950 sampai ' . date('Y') . '.';
            }
        }

        if ($field === 'institusi' && mb_strlen($nilai) > 150) {
            return 'Nama sekolah / perguruan tinggi maksimal 150 karakter.';
        }

        if ($salahNomor = self::validasiNomorIdentitas($field, $nilai)) return $salahNomor;

        return null;
    }

    /**
     * Validasi nomor identitas. Dipakai jalur ESS DAN jalur HR agar keduanya
     * tunduk pada aturan yang sama — nomor ini dipakai lintas sistem
     * (BPJS, pajak), jadi salah panjang satu digit membuatnya tak berguna.
     *
     * NPWP dipisah dua kolom: `no_npwp` khusus format lama 15 digit dan
     * `no_npwp16` khusus format baru. Sebelumnya satu kolom menerima keduanya,
     * sehingga isinya bercampur tanpa bisa dibedakan selain dari panjangnya.
     */
    public static function validasiNomorIdentitas(string $field, string $nilai): ?string
    {
        $angka = preg_replace('/\D/', '', $nilai);   // toleran titik/strip/spasi

        return match ($field) {
            'nik_ktp'   => strlen($angka) !== 16 ? 'No. KTP (NIK) harus 16 digit angka.' : null,
            'no_kk'     => strlen($angka) !== 16 ? 'No. Kartu Keluarga harus 16 digit angka.' : null,
            'no_npwp'   => strlen($angka) !== 15
                ? 'No. NPWP format lama harus 15 digit. Untuk NPWP 16 digit, isikan di kolom NPWP-16.' : null,
            'no_npwp16' => strlen($angka) !== 16 ? 'No. NPWP-16 harus 16 digit angka.' : null,
            default     => null,
        };
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

        // A self-initiated password change revokes outstanding mobile API tokens.
        if (isset($data['password'])) {
            (new \App\Models\ApiTokenModel())->revokeAllForUser((int)$id);
        }

        $changed = isset($data['password']) ? 'nama & password' : 'nama';
        ActivityLog::write('update', 'profile', (string) $id, 'Ubah profil sendiri (' . $changed . ')');
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
