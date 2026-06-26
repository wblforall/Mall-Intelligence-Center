<?php

namespace App\Controllers\Api;

use App\Models\PipPlanModel;
use App\Libraries\ActivityLog;

class PipController extends BaseApiController
{
    public function show(int $id)
    {
        if (! $this->requireAuth()) return $this->response;

        $plan = $this->db->table('pip_plans p')
            ->select('p.*, e.nama as employee_nama, e.jabatan, d.name as dept_name')
            ->join('employees e', 'e.id = p.employee_id', 'left')
            ->join('departments d', 'd.id = e.dept_id', 'left')
            ->where('p.id', $id)
            ->get()->getRowArray();

        if (! $plan) return $this->error('PIP tidak ditemukan.', 404);

        // Read scope: approver, supervisor of the employee, or the employee themselves.
        if (! $this->can('can_approve_pip')
            && ! $this->isSupervisorOfEmployee((int)$plan['employee_id'])
            && $this->employeeUserId((int)$plan['employee_id']) !== (int)$this->apiUser['id']) {
            return $this->forbidden();
        }

        $plan['items'] = $this->db->table('pip_items')
            ->where('pip_id', $id)
            ->get()->getResultArray();

        return $this->success($plan);
    }

    public function approvals()
    {
        if (! $this->requireAuth()) return $this->response;
        if (! $this->can('can_approve_pip')) return $this->forbidden();

        $status = $this->request->getGet('status') ?? 'pending';
        $pihak  = $this->request->getGet('pihak')  ?? 'atasan';

        if (! in_array($pihak, ['atasan', 'karyawan'])) {
            return $this->error('Pihak tidak valid.');
        }

        $field = 'p.persetujuan_' . $pihak;

        $rows = $this->db->table('pip_plans p')
            ->select('p.id, p.judul, p.alasan, p.level_sp, p.status,
                      p.persetujuan_atasan, p.persetujuan_karyawan,
                      p.tanggal_mulai, p.tanggal_selesai, p.created_at,
                      e.nama as employee_nama, e.jabatan,
                      d.name as dept_name,
                      (SELECT COUNT(*) FROM pip_items WHERE pip_id = p.id) as item_count')
            ->join('employees e', 'e.id = p.employee_id', 'left')
            ->join('departments d', 'd.id = e.dept_id', 'left')
            ->where($field, $status)
            ->orderBy('p.created_at', 'DESC')
            ->get()->getResultArray();

        return $this->success($rows);
    }

    public function approve(int $id)
    {
        if (! $this->requireAuth()) return $this->response;
        if (! $this->can('can_approve_pip')) return $this->forbidden();

        $body  = $this->request->getJSON(true) ?? [];
        $pihak = trim($body['pihak'] ?? 'atasan');
        $note  = trim($body['note']  ?? '');

        if (! in_array($pihak, ['atasan', 'karyawan'])) return $this->error('Pihak tidak valid.');

        $model = new PipPlanModel();
        $plan  = $model->find($id);
        if (! $plan) return $this->error('PIP tidak ditemukan.', 404);
        // Block self-approval: an approver cannot sign off on their own PIP.
        if ($this->employeeUserId((int)$plan['employee_id']) === (int)$this->apiUser['id']) {
            return $this->forbidden('Anda tidak dapat menyetujui PIP Anda sendiri.');
        }
        if ($plan['persetujuan_' . $pihak] !== 'pending') return $this->error('PIP ini sudah diproses.');

        $model->update($id, ['persetujuan_' . $pihak => 'setuju']);
        ActivityLog::write('approve', 'pip_plan', (string)$id, "Approved ($pihak) via mobile by " . $this->apiUser['name']);

        return $this->success(null, 'PIP disetujui.');
    }

    public function reject(int $id)
    {
        if (! $this->requireAuth()) return $this->response;
        if (! $this->can('can_approve_pip')) return $this->forbidden();

        $body   = $this->request->getJSON(true) ?? [];
        $pihak  = trim($body['pihak'] ?? 'atasan');
        $reason = trim($body['note']  ?? '');

        if (! in_array($pihak, ['atasan', 'karyawan'])) return $this->error('Pihak tidak valid.');
        if (! $reason) return $this->error('Alasan penolakan wajib diisi.');

        $model = new PipPlanModel();
        $plan  = $model->find($id);
        if (! $plan) return $this->error('PIP tidak ditemukan.', 404);
        if ($this->employeeUserId((int)$plan['employee_id']) === (int)$this->apiUser['id']) {
            return $this->forbidden('Anda tidak dapat memproses PIP Anda sendiri.');
        }
        if ($plan['persetujuan_' . $pihak] !== 'pending') return $this->error('PIP ini sudah diproses.');

        $field = $pihak === 'atasan' ? 'catatan_penolakan_atasan' : 'catatan_penolakan';
        $model->update($id, [
            'persetujuan_' . $pihak => 'menolak',
            $field                  => $reason,
        ]);

        ActivityLog::write('reject', 'pip_plan', (string)$id, "Rejected ($pihak) via mobile by " . $this->apiUser['name']);
        return $this->success(null, 'PIP ditolak.');
    }
}
