<?php

namespace App\Models;

use CodeIgniter\Model;

class EeiResponseModel extends Model
{
    protected $table         = 'eei_responses';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['period_id', 'dept_id', 'question_id', 'score'];
    protected $useTimestamps = true;

    // Returns [{id, nama, urutan, avg_raw, score(0-100)}]
    public function getScoreByDimension(int $periodId): array
    {
        $rows = db_connect()->query("
            SELECT d.id, d.nama, d.urutan,
                   AVG(CASE WHEN q.is_reversed THEN 6 - r.score ELSE r.score END) AS avg_raw
            FROM eei_dimensions d
            JOIN eei_questions q  ON q.dimension_id = d.id
            JOIN eei_responses r  ON r.question_id  = q.id
            WHERE r.period_id = ?
            GROUP BY d.id, d.nama, d.urutan
            ORDER BY d.urutan
        ", [$periodId])->getResultArray();

        foreach ($rows as &$row) {
            $row['score'] = round(((float)$row['avg_raw'] - 1) / 4 * 100, 1);
        }
        return $rows;
    }

    // Returns [{jabatan_level, score(0-100)}]
    public function getScoreByLevel(int $periodId): array
    {
        $rows = db_connect()->query("
            SELECT r.jabatan_level,
                   AVG(CASE WHEN q.is_reversed THEN 6 - r.score ELSE r.score END) AS avg_raw
            FROM eei_responses r
            JOIN eei_questions q ON q.id = r.question_id
            WHERE r.period_id = ? AND r.jabatan_level IS NOT NULL AND r.jabatan_level != ''
            GROUP BY r.jabatan_level
            ORDER BY FIELD(r.jabatan_level,
                'Staff','Supervisor','Asst. Manager','Manager','Senior Manager','General Manager','Director','C-Level / VP')
        ", [$periodId])->getResultArray();

        foreach ($rows as &$row) {
            $row['score'] = round(((float)$row['avg_raw'] - 1) / 4 * 100, 1);
        }
        return $rows;
    }

    // Returns [{id, name, score(0-100)}]
    public function getScoreByDept(int $periodId): array
    {
        $rows = db_connect()->query("
            SELECT d.id, d.name,
                   AVG(CASE WHEN q.is_reversed THEN 6 - r.score ELSE r.score END) AS avg_raw
            FROM departments d
            JOIN eei_responses r ON r.dept_id     = d.id
            JOIN eei_questions q ON q.id           = r.question_id
            WHERE r.period_id = ?
            GROUP BY d.id, d.name
            ORDER BY avg_raw DESC
        ", [$periodId])->getResultArray();

        foreach ($rows as &$row) {
            $row['score'] = round(((float)$row['avg_raw'] - 1) / 4 * 100, 1);
        }
        return $rows;
    }

    // Returns rows grouped by dept × jabatan_level for cross-tab matrix
    public function getScoreByDeptAndLevel(int $periodId): array
    {
        $rows = db_connect()->query("
            SELECT d.name AS dept_name,
                   r.jabatan_level,
                   AVG(CASE WHEN q.is_reversed THEN 6 - r.score ELSE r.score END) AS avg_raw,
                   COUNT(DISTINCT CONCAT(r.dept_id, '-', r.jabatan_level, '-',
                         (SELECT MIN(r2.id) FROM eei_responses r2
                          WHERE r2.period_id = r.period_id AND r2.dept_id = r.dept_id
                            AND r2.jabatan_level = r.jabatan_level))) AS respondents
            FROM eei_responses r
            JOIN eei_questions q  ON q.id  = r.question_id
            JOIN departments   d  ON d.id  = r.dept_id
            WHERE r.period_id = ?
              AND r.jabatan_level IS NOT NULL AND r.jabatan_level != ''
            GROUP BY d.id, d.name, r.jabatan_level
            ORDER BY d.name,
                FIELD(r.jabatan_level,
                    'Staff','Supervisor','Asst. Manager','Manager',
                    'Senior Manager','General Manager','Director','C-Level / VP')
        ", [$periodId])->getResultArray();

        foreach ($rows as &$row) {
            $row['score'] = round(((float)$row['avg_raw'] - 1) / 4 * 100, 1);
        }
        return $rows;
    }

    public function getOverallScore(int $periodId): float
    {
        $dims = $this->getScoreByDimension($periodId);
        if (empty($dims)) return 0.0;
        return round(array_sum(array_column($dims, 'score')) / count($dims), 1);
    }

    // For logged-in users
    public function hasCompleted(int $periodId, int $userId): bool
    {
        return db_connect()->table('eei_completions')
            ->where('period_id', $periodId)
            ->where('submission_key', 'u_' . $userId)
            ->countAllResults() > 0;
    }

    // For anonymous (token-based) users
    public function hasCompletedByKey(int $periodId, string $submissionKey): bool
    {
        return db_connect()->table('eei_completions')
            ->where('period_id', $periodId)
            ->where('submission_key', $submissionKey)
            ->countAllResults() > 0;
    }

    public function getParticipation(int $periodId, int $totalEmployees): array
    {
        $completed = db_connect()->table('eei_completions')
            ->where('period_id', $periodId)->countAllResults();
        return [
            'completed'  => $completed,
            'total'      => $totalEmployees,
            'percentage' => $totalEmployees > 0 ? round($completed / $totalEmployees * 100, 1) : 0,
        ];
    }

    // Save anonymous responses + mark completion. Works for both logged-in and anonymous.
    public function saveForPeriodDept(int $periodId, int $deptId, ?int $userId, array $scores, ?string $jabatanLevel = null, string $submissionKey = ''): void
    {
        if (! $submissionKey) {
            $submissionKey = 'u_' . $userId;
        }

        $db  = db_connect();
        $db->transStart();

        $rows = [];
        $now  = date('Y-m-d H:i:s');
        foreach ($scores as $questionId => $score) {
            $score = max(1, min(5, (int)$score));
            $rows[] = [
                'period_id'     => $periodId,
                'dept_id'       => $deptId,
                'jabatan_level' => $jabatanLevel ?: null,
                'question_id'   => (int)$questionId,
                'score'         => $score,
                'created_at'    => $now,
                'updated_at'    => $now,
            ];
        }
        if ($rows) $db->table('eei_responses')->insertBatch($rows);

        $db->table('eei_completions')->insert([
            'period_id'      => $periodId,
            'user_id'        => $userId,
            'submission_key' => $submissionKey,
            'completed_at'   => $now,
        ]);

        $db->transComplete();
    }
}
