<?php

namespace App\Controllers;

use App\Models\PipPlanModel;
use App\Models\PipItemModel;
use App\Libraries\ActivityLog;

class PipApproval extends BaseController
{
    public function show(string $pihak, string $token)
    {
        if (! in_array($pihak, ['atasan','karyawan'])) return $this->notFound();

        $field = 'token_' . $pihak;
        $plan  = (new PipPlanModel())->getWithEmployee(0);

        $db   = db_connect();
        $plan = $db->table('pip_plans p')
            ->select('p.*, e.nama as employee_nama, e.jabatan, d.name as dept_name')
            ->join('employees e', 'e.id = p.employee_id', 'left')
            ->join('departments d', 'd.id = e.dept_id', 'left')
            ->where("p.$field", $token)
            ->get()->getRowArray();

        if (! $plan) {
            return view('people/pip/approval_invalid');
        }

        $statusDone = $plan['persetujuan_' . $pihak] !== 'pending';

        return view('people/pip/approval', [
            'plan'       => $plan,
            'pihak'      => $pihak,
            'token'      => $token,
            'items'      => (new PipItemModel())->getByPip((int)$plan['id']),
            'statusDone' => $statusDone,
        ]);
    }

    public function submit(string $pihak, string $token)
    {
        if (! in_array($pihak, ['atasan','karyawan'])) return $this->notFound();

        $field = 'token_' . $pihak;
        $db    = db_connect();
        $plan  = $db->table('pip_plans')->where($field, $token)->get()->getRowArray();

        if (! $plan || $plan['persetujuan_' . $pihak] !== 'pending') {
            return redirect()->to('/pip/approval/' . $pihak . '/' . $token);
        }

        $post    = $this->request->getPost();
        $keputusan = $post['keputusan'] ?? '';
        if (! in_array($keputusan, ['setuju','menolak'])) {
            return redirect()->to('/pip/approval/' . $pihak . '/' . $token);
        }

        $updateData = ['persetujuan_' . $pihak => $keputusan];
        if ($pihak === 'atasan') {
            $updateData['catatan_penolakan_atasan'] = trim($post['catatan'] ?? '') ?: null;
        } else {
            $updateData['catatan_penolakan'] = trim($post['catatan'] ?? '') ?: null;
        }

        (new PipPlanModel())->update((int)$plan['id'], $updateData);
        ActivityLog::write('update', 'pip_plan', (string)$plan['id'], 'approval ' . $pihak . ': ' . $keputusan);

        return view('people/pip/approval_done', [
            'pihak'     => $pihak,
            'keputusan' => $keputusan,
            'plan'      => $plan,
        ]);
    }

    private function notFound()
    {
        return view('people/pip/approval_invalid');
    }
}
