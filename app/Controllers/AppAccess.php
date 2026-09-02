<?php

namespace App\Controllers;

use App\Libraries\ActivityLog;
use App\Models\AppModel;
use App\Models\AppRoleModel;
use App\Models\CompanyModel;
use App\Models\DepartmentAppAccessModel;
use App\Models\DepartmentModel;

/**
 * Default akses aplikasi per departemen — dasar dari portal. Karyawan baru
 * di sebuah departemen otomatis mewarisi grant di sini; employee_app_access
 * (lihat PeopleEmployees) hanya untuk tambahan/pengecualian per orang.
 *
 * Gate akses lewat menu 'app_access' yang sudah ada di MenuAccess —
 * SENGAJA tidak dibuat konsep izin baru. Siapa pun yang diberi can_edit
 * pada menu ini — HR maupun admin sistem per aplikasi — bisa mengubah
 * default di sini, dan siapa pun itu, tercatat di ActivityLog.
 */
class AppAccess extends BaseController
{
    public function index()
    {
        if (! $this->canViewMenu('app_access')) return redirect()->to('/events')->with('error', 'Akses ditolak.');

        $depts = (new DepartmentModel())->orderBy('name', 'ASC')->findAll();
        $accessModel = new DepartmentAppAccessModel();
        $jumlahGrant = [];
        foreach ($depts as $d) {
            $jumlahGrant[$d['id']] = count($accessModel->getByDepartment($d['id']));
        }

        return view('app_access/index', [
            'user'        => $this->currentUser(),
            'depts'       => $depts,
            'jumlahGrant' => $jumlahGrant,
            'companies'   => (new CompanyModel())->aktifSaja(),
            'apps'        => (new AppModel())->aktifSaja(),
            'canEdit'     => $this->canEditMenu('app_access'),
        ]);
    }

    public function edit(int $deptId)
    {
        if (! $this->canViewMenu('app_access')) return redirect()->to('/events')->with('error', 'Akses ditolak.');

        $dept = (new DepartmentModel())->find($deptId);
        if (! $dept) return redirect()->to('/app-access')->with('error', 'Departemen tidak ditemukan.');

        $apps = (new AppModel())->aktifSaja();
        $roleByApp = (new AppRoleModel())->semuaDikelompokkan();

        // Untuk departemen 'unit', grid dipersempit ke unit bisnis yang
        // NYATA punya karyawan departemen ini — bukan seluruh companies,
        // supaya tidak menampilkan puluhan sel yang tak pernah relevan.
        $companies = [];
        if ($dept['tingkat'] === 'unit') {
            $companies = db_connect()->table('employees e')
                ->select('c.id, c.kode, c.nama')
                ->join('companies c', 'c.id = e.company_id')
                ->where('e.dept_id', $deptId)->where('e.status', 'aktif')
                ->groupBy('c.id')->orderBy('c.nama', 'ASC')
                ->get()->getResultArray();
        }

        $existing = (new DepartmentAppAccessModel())->getByDepartment($deptId);
        // Peta cepat: [app_id][company_id ?? 0] => app_role_id, dibaca view untuk nilai terpilih.
        $terpilih = [];
        foreach ($existing as $g) {
            $terpilih[$g['app_id']][$g['company_id'] ?? 0] = $g['app_role_id'];
        }

        return view('app_access/edit', [
            'user'      => $this->currentUser(),
            'dept'      => $dept,
            'apps'      => $apps,
            'roleByApp' => $roleByApp,
            'companies' => $companies,
            'terpilih'  => $terpilih,
            'canEdit'   => $this->canEditMenu('app_access'),
        ]);
    }

    public function update(int $deptId)
    {
        if (! $this->canEditMenu('app_access')) return redirect()->to('/app-access')->with('error', 'Akses ditolak.');

        $dept = (new DepartmentModel())->find($deptId);
        if (! $dept) return redirect()->to('/app-access')->with('error', 'Departemen tidak ditemukan.');

        // post: grant[app_id][company_id_or_0] = app_role_id (0/kosong = tidak diberi)
        $post = $this->request->getPost('grant') ?? [];
        $baris = [];
        foreach ($post as $appId => $perCompany) {
            foreach ($perCompany as $companyKey => $roleId) {
                if (empty($roleId)) continue;
                $baris[] = [
                    'app_id'      => (int) $appId,
                    'company_id'  => ((int) $companyKey) ?: null,
                    'app_role_id' => (int) $roleId,
                ];
            }
        }

        $sebelum = (new DepartmentAppAccessModel())->getByDepartment($deptId);
        (new DepartmentAppAccessModel())->saveGrants($deptId, $baris);

        ActivityLog::write('update', 'department_app_access', (string) $deptId, $dept['name'], [
            'jumlah_grant_sebelum' => count($sebelum),
            'jumlah_grant_sesudah' => count($baris),
        ]);

        // Akses aplikasi default berubah → karyawan departemen ini perlu sesi baru
        // supaya perubahan langsung berlaku, sama seperti perubahan akses menu.
        db_connect()->table('users')->where('department_id', $deptId)
            ->update(['perms_changed_at' => date('Y-m-d H:i:s')]);

        return redirect()->to('/app-access/'.$deptId.'/edit')->with('success', 'Default akses aplikasi departemen tersimpan.');
    }
}
