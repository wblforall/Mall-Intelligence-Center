<?php

namespace App\Libraries;

/**
 * Navigasi rantai atasan (org chart) untuk alur review Appraisal.
 * Rantai = naik employees.atasan_id sampai puncak; tiap atasan yang punya akun
 * login (user_id) menjadi reviewer. Setelah reviewer teratas → HR.
 */
class AppraisalChain
{
    protected $db;

    public function __construct()
    {
        $this->db = db_connect();
    }

    public function employee(int $employeeId): ?array
    {
        return $this->db->table('employees')
            ->select('id, nama, atasan_id, user_id, dept_id, jabatan_id')
            ->where('id', $employeeId)->get()->getRowArray();
    }

    public function employeeByUser(int $userId): ?array
    {
        return $this->db->table('employees')
            ->select('id, nama, atasan_id, user_id, dept_id, jabatan_id')
            ->where('user_id', $userId)->get()->getRowArray();
    }

    /** Ordered list atasan employee dari atasan langsung ke atas (maks 15 level, anti-loop). */
    public function atasanChain(int $employeeId): array
    {
        $chain = [];
        $seen  = [$employeeId => true];
        $cur   = $this->employee($employeeId);
        $depth = 0;
        while ($cur && ! empty($cur['atasan_id']) && $depth < 15) {
            $aid = (int) $cur['atasan_id'];
            if (isset($seen[$aid])) break;            // cegah siklus
            $seen[$aid] = true;
            $atasan = $this->employee($aid);
            if (! $atasan) break;
            $chain[] = $atasan;
            $cur = $atasan;
            $depth++;
        }
        return $chain;
    }

    /** Atasan pertama (dari bawah) yang punya akun login → penilai awal. Null jika tak ada. */
    public function firstActor(int $employeeId): ?array
    {
        foreach ($this->atasanChain($employeeId) as $a) {
            if (! empty($a['user_id'])) return $a;
        }
        return null;
    }

    /**
     * Reviewer berikutnya di atas $currentActorUserId untuk karyawan yang dinilai.
     * Null = tidak ada lagi → lanjut ke HR.
     */
    public function nextActorAfter(int $currentActorUserId, int $appraisedEmployeeId): ?array
    {
        $chain = $this->atasanChain($appraisedEmployeeId);
        // temukan posisi current actor dalam rantai, ambil atasan berakun login berikutnya
        $idx = null;
        foreach ($chain as $i => $a) {
            if ((int) ($a['user_id'] ?? 0) === $currentActorUserId) { $idx = $i; break; }
        }
        if ($idx === null) return null;
        for ($j = $idx + 1; $j < count($chain); $j++) {
            if (! empty($chain[$j]['user_id'])) return $chain[$j];
        }
        return null;
    }
}
