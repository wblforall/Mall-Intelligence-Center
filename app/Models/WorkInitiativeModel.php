<?php

namespace App\Models;

use CodeIgniter\Model;

class WorkInitiativeModel extends Model
{
    protected $table         = 'work_initiatives';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'dept_id', 'divisi_id', 'judul', 'deskripsi', 'pic_employee_id',
        'target_mulai', 'target_selesai', 'assigned_to_dept_id', 'created_by', 'is_active',
    ];

    // Semua inisiatif yang bisa dilihat oleh Dept Head:
    // 1. Inisiatif milik dept sendiri (dept_id = X, assigned_to_dept_id IS NULL)
    // 2. Inisiatif yang di-assign Deputy ke dept ini (assigned_to_dept_id = X)
    public function forDeptHead(int $deptId): array
    {
        return $this->select('work_initiatives.*, d.name AS dept_name, dv.nama AS divisi_name,
                e.nama AS pic_name,
                cb.nama AS created_by_name,
                u_latest.status AS latest_status,
                u_latest.progress_pct AS latest_progress,
                u_latest.catatan AS latest_catatan,
                u_latest.hambatan AS latest_hambatan,
                u_latest.created_at AS latest_updated_at')
            ->join('departments d', 'd.id = work_initiatives.dept_id', 'left')
            ->join('divisions dv', 'dv.id = work_initiatives.divisi_id', 'left')
            ->join('employees e', 'e.id = work_initiatives.pic_employee_id', 'left')
            ->join('employees cb', 'cb.id = work_initiatives.created_by', 'left')
            ->join('work_initiative_updates u_latest',
                'u_latest.id = (SELECT id FROM work_initiative_updates WHERE initiative_id = work_initiatives.id ORDER BY created_at DESC LIMIT 1)',
                'left')
            ->groupStart()
                ->where('work_initiatives.dept_id', $deptId)
                ->orWhere('work_initiatives.assigned_to_dept_id', $deptId)
            ->groupEnd()
            ->where('work_initiatives.is_active', 1)
            ->orderBy('work_initiatives.created_at', 'DESC')
            ->findAll();
    }

    // Semua inisiatif dalam satu divisi (untuk Deputy)
    public function forDivision(int $divisiId): array
    {
        return $this->select('work_initiatives.*, d.name AS dept_name, dv.nama AS divisi_name,
                e.nama AS pic_name,
                cb.nama AS created_by_name,
                ad.name AS assigned_dept_name,
                u_latest.status AS latest_status,
                u_latest.progress_pct AS latest_progress,
                u_latest.catatan AS latest_catatan,
                u_latest.hambatan AS latest_hambatan,
                u_latest.created_at AS latest_updated_at,
                (SELECT COUNT(*) FROM work_initiative_flags f WHERE f.initiative_id = work_initiatives.id AND f.is_active = 1) AS is_flagged')
            ->join('departments d', 'd.id = work_initiatives.dept_id', 'left')
            ->join('divisions dv', 'dv.id = work_initiatives.divisi_id', 'left')
            ->join('employees e', 'e.id = work_initiatives.pic_employee_id', 'left')
            ->join('employees cb', 'cb.id = work_initiatives.created_by', 'left')
            ->join('departments ad', 'ad.id = work_initiatives.assigned_to_dept_id', 'left')
            ->join('work_initiative_updates u_latest',
                'u_latest.id = (SELECT id FROM work_initiative_updates WHERE initiative_id = work_initiatives.id ORDER BY created_at DESC LIMIT 1)',
                'left')
            ->where('work_initiatives.divisi_id', $divisiId)
            ->where('work_initiatives.is_active', 1)
            ->orderBy('d.name')
            ->orderBy('work_initiatives.created_at', 'DESC')
            ->findAll();
    }

    // Inisiatif yang di-flag untuk GM (semua divisi)
    public function forGm(): array
    {
        return $this->select('work_initiatives.*, d.name AS dept_name, dv.nama AS divisi_name,
                e.nama AS pic_name,
                cb.nama AS created_by_name,
                ad.name AS assigned_dept_name,
                u_latest.status AS latest_status,
                u_latest.progress_pct AS latest_progress,
                u_latest.catatan AS latest_catatan,
                u_latest.hambatan AS latest_hambatan,
                u_latest.created_at AS latest_updated_at,
                f.flagged_at, f.flagged_by,
                dep_emp.nama AS deputy_name')
            ->join('departments d', 'd.id = work_initiatives.dept_id', 'left')
            ->join('divisions dv', 'dv.id = work_initiatives.divisi_id', 'left')
            ->join('employees e', 'e.id = work_initiatives.pic_employee_id', 'left')
            ->join('employees cb', 'cb.id = work_initiatives.created_by', 'left')
            ->join('departments ad', 'ad.id = work_initiatives.assigned_to_dept_id', 'left')
            ->join('work_initiative_flags f', 'f.initiative_id = work_initiatives.id AND f.is_active = 1', 'inner')
            ->join('users u_dep', 'u_dep.id = f.flagged_by', 'left')
            ->join('employees dep_emp', 'dep_emp.user_id = u_dep.id', 'left')
            ->join('work_initiative_updates u_latest',
                'u_latest.id = (SELECT id FROM work_initiative_updates WHERE initiative_id = work_initiatives.id ORDER BY created_at DESC LIMIT 1)',
                'left')
            ->where('work_initiatives.is_active', 1)
            ->orderBy('dv.nama')
            ->orderBy('d.name')
            ->orderBy('work_initiatives.created_at', 'DESC')
            ->findAll();
    }
}
