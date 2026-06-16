<?php

namespace App\Libraries;

use App\Models\AppraisalTemplateAuthorModel;
use App\Models\AppraisalDivisionDeputyModel;

/**
 * Menentukan SIAPA yang berwenang menyusun template KPI sebuah jabatan.
 *
 * Aturan:
 *  - Dept Head (ditunjuk per dept) → menyusun template jabatan di dept-nya yang
 *    levelnya DI BAWAH posisi puncak dept (grade > grade tertinggi dept).
 *  - Posisi puncak dept (grade terkecil = level tertinggi, mis. Manager/Dept Head)
 *    → Deputy divisi (ditunjuk per divisi).
 *  - Deputy ke atas, atau dept tanpa head/deputy → HR (fallback).
 *
 * Catatan: grade makin kecil = makin tinggi (1 Direktur … 7 Staff).
 */
class AppraisalAuthority
{
    protected $db;
    protected array $authors;     // dept_id => user_id (dept head)
    protected array $deputies;    // division_id => user_id
    protected array $deptDiv = []; // dept_id => division_id|null
    protected array $deptTop = []; // dept_id => grade tertinggi (min)

    public function __construct()
    {
        $this->db       = db_connect();
        $this->authors  = (new AppraisalTemplateAuthorModel())->map();
        $this->deputies = (new AppraisalDivisionDeputyModel())->map();

        foreach ($this->db->table('departments')->select('id, division_id')->get()->getResultArray() as $d) {
            $this->deptDiv[(int) $d['id']] = $d['division_id'] !== null ? (int) $d['division_id'] : null;
        }
        foreach ($this->db->table('jabatans')->select('dept_id, MIN(grade) AS topg')
                    ->where('dept_id IS NOT NULL')->groupBy('dept_id')->get()->getResultArray() as $r) {
            $this->deptTop[(int) $r['dept_id']] = (int) $r['topg'];
        }
    }

    /** @return array{level:string,user_id:?int} level = dept_head|deputy|hr */
    public function whoAuthors(array $jab): array
    {
        $deptId = $jab['dept_id'] !== null ? (int) $jab['dept_id'] : null;
        if ($deptId === null) return ['level' => 'hr', 'user_id' => null]; // jabatan lintas/level atas

        $head = $this->authors[$deptId] ?? null;
        if ($head === null) return ['level' => 'hr', 'user_id' => null];   // dept tanpa head → HR

        $topGrade = $this->deptTop[$deptId] ?? 0;
        if ((int) $jab['grade'] > $topGrade) {
            return ['level' => 'dept_head', 'user_id' => $head];           // di bawah puncak → dept head
        }
        // posisi puncak dept (selevel head) → Deputy divisi
        $div    = $this->deptDiv[$deptId] ?? null;
        $deputy = $div !== null ? ($this->deputies[$div] ?? null) : null;
        return $deputy !== null ? ['level' => 'deputy', 'user_id' => $deputy] : ['level' => 'hr', 'user_id' => null];
    }

    public function canAuthor(int $userId, array $jab, bool $isHr): bool
    {
        if ($isHr) return true;
        $w = $this->whoAuthors($jab);
        return $w['user_id'] !== null && $w['user_id'] === $userId;
    }

    /** Apakah user ditunjuk sebagai dept head atau deputy (untuk menu/akses). */
    public function isAssignedAuthor(int $userId): bool
    {
        return in_array($userId, $this->authors, true) || in_array($userId, $this->deputies, true);
    }
}
