<?php

namespace App\Controllers\Api;

use App\Models\IdpPlanModel;
use App\Models\IdpItemModel;
use App\Libraries\ActivityLog;

class IdpController extends BaseApiController
{
    public function show(int $id)
    {
        if (! $this->requireAuth()) return $this->response;

        $plan = $this->db->table('idp_plans p')
            ->select('p.*, e.nama as employee_nama, e.jabatan, d.name as dept_name')
            ->join('employees e', 'e.id = p.employee_id', 'left')
            ->join('departments d', 'd.id = e.dept_id', 'left')
            ->where('p.id', $id)
            ->get()->getRowArray();

        if (! $plan) return $this->error('IDP tidak ditemukan.', 404);

        $plan['items'] = $this->db->table('idp_items')
            ->where('idp_id', $id)
            ->orderBy('urutan', 'ASC')
            ->get()->getResultArray();

        return $this->success($plan);
    }

    public function approvals()
    {
        if (! $this->requireAuth()) return $this->response;

        $status = $this->request->getGet('status') ?? 'pending';

        $rows = $this->db->table('idp_plans p')
            ->select('p.id, p.periode_label, p.tahun, p.tujuan_karir, p.status,
                      p.persetujuan_atasan, p.catatan_penolakan, p.created_at,
                      e.nama as employee_nama, e.jabatan,
                      d.name as dept_name,
                      (SELECT COUNT(*) FROM idp_items WHERE idp_id = p.id) as item_count')
            ->join('employees e', 'e.id = p.employee_id', 'left')
            ->join('departments d', 'd.id = e.dept_id', 'left')
            ->where('p.persetujuan_atasan', $status)
            ->orderBy('p.created_at', 'DESC')
            ->get()->getResultArray();

        return $this->success($rows);
    }

    public function approve(int $id)
    {
        if (! $this->requireAuth()) return $this->response;

        $model = new IdpPlanModel();
        $plan  = $model->find($id);
        if (! $plan) return $this->error('IDP tidak ditemukan.', 404);
        if ($plan['persetujuan_atasan'] !== 'pending') return $this->error('IDP ini sudah diproses.');

        $body = $this->request->getJSON(true) ?? [];
        $note = trim($body['note'] ?? '');

        $model->update($id, [
            'persetujuan_atasan'  => 'setuju',
            'status'              => 'aktif',
            'catatan'             => $note ?: null,
            'approved_by_user_id' => $this->apiUser['id'],
            'approved_at'         => date('Y-m-d H:i:s'),
        ]);

        ActivityLog::write('approve', 'idp_plan', (string)$id, 'Approved via mobile by ' . $this->apiUser['name']);
        return $this->success(null, 'IDP disetujui.');
    }

    public function reject(int $id)
    {
        if (! $this->requireAuth()) return $this->response;

        $body   = $this->request->getJSON(true) ?? [];
        $reason = trim($body['note'] ?? '');
        if (! $reason) return $this->error('Alasan penolakan wajib diisi.');

        $model = new IdpPlanModel();
        $plan  = $model->find($id);
        if (! $plan) return $this->error('IDP tidak ditemukan.', 404);
        if ($plan['persetujuan_atasan'] !== 'pending') return $this->error('IDP ini sudah diproses.');

        $model->update($id, [
            'persetujuan_atasan' => 'menolak',
            'catatan_penolakan'  => $reason,
        ]);

        ActivityLog::write('reject', 'idp_plan', (string)$id, 'Rejected via mobile by ' . $this->apiUser['name']);
        return $this->success(null, 'IDP ditolak.');
    }
}
