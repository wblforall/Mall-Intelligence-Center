<?php

namespace App\Controllers;

use App\Models\IdpPlanModel;
use App\Models\IdpItemModel;
use App\Libraries\ActivityLog;

class IdpApproval extends BaseController
{
    public function show(string $token)
    {
        $db   = db_connect();
        $plan = $db->table('idp_plans p')
            ->select('p.*, e.nama as employee_nama, e.jabatan, d.name as dept_name,
                      a.nama as atasan_nama, a.jabatan as atasan_jabatan')
            ->join('employees e', 'e.id = p.employee_id', 'left')
            ->join('employees a', 'a.id = e.atasan_id',  'left')
            ->join('departments d', 'd.id = e.dept_id',  'left')
            ->where('p.token_atasan', $token)
            ->get()->getRowArray();

        if (! $plan) {
            return view('people/idp/approval_invalid');
        }

        return view('people/idp/approval', [
            'plan'       => $plan,
            'token'      => $token,
            'items'      => (new IdpItemModel())->getByIdp((int)$plan['id']),
            'statusDone' => $plan['persetujuan_atasan'] !== 'pending',
        ]);
    }

    public function submit(string $token)
    {
        $db   = db_connect();
        $plan = $db->table('idp_plans')->where('token_atasan', $token)->get()->getRowArray();

        if (! $plan || $plan['persetujuan_atasan'] !== 'pending') {
            return redirect()->to('/idp/approval/' . $token);
        }

        $keputusan = $this->request->getPost('keputusan') ?? '';
        if (! in_array($keputusan, ['setuju', 'menolak'])) {
            return redirect()->to('/idp/approval/' . $token);
        }

        $updateData = [
            'persetujuan_atasan' => $keputusan,
            'catatan_penolakan'  => trim($this->request->getPost('catatan') ?? '') ?: null,
        ];

        if ($keputusan === 'setuju') {
            $updateData['status']           = 'aktif';
            $updateData['approved_at']      = date('Y-m-d H:i:s');
        }

        $planModel = new IdpPlanModel();
        ActivityLog::captureBefore($plan);
        $planModel->update((int)$plan['id'], $updateData);
        ActivityLog::captureAfter($planModel->find((int)$plan['id']));
        ActivityLog::write('update', 'idp_plan', (string)$plan['id'], 'approval atasan: ' . $keputusan);

        $planFull = $planModel->getWithEmployee((int)$plan['id']);
        return view('people/idp/approval_done', [
            'keputusan' => $keputusan,
            'plan'      => $planFull ?? $plan,
        ]);
    }
}
