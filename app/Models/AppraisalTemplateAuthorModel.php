<?php

namespace App\Models;

use CodeIgniter\Model;

class AppraisalTemplateAuthorModel extends Model
{
    protected $table         = 'appraisal_template_authors';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['dept_id', 'user_id'];
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';

    /** dept_id => user_id penyusun. */
    public function map(): array
    {
        $rows = $this->findAll();
        $map = [];
        foreach ($rows as $r) $map[(int) $r['dept_id']] = (int) $r['user_id'];
        return $map;
    }

    /** Dept yang ditugaskan ke seorang user. */
    public function deptIdsForUser(int $userId): array
    {
        return array_map('intval', array_column(
            $this->where('user_id', $userId)->findAll(),
            'dept_id'
        ));
    }

    public function authorOfDept(int $deptId): ?int
    {
        $row = $this->where('dept_id', $deptId)->first();
        return $row ? (int) $row['user_id'] : null;
    }

    /** Set/ubah/hapus penyusun untuk satu dept (user_id null = hapus). */
    public function setAuthor(int $deptId, ?int $userId): void
    {
        $existing = $this->where('dept_id', $deptId)->first();
        if ($userId === null) {
            if ($existing) $this->delete($existing['id']);
            return;
        }
        if ($existing) $this->update($existing['id'], ['user_id' => $userId]);
        else $this->insert(['dept_id' => $deptId, 'user_id' => $userId]);
    }
}
