<?php

namespace App\Models;

use CodeIgniter\Model;

class TnaAssessmentItemModel extends Model
{
    protected $table         = 'tna_assessment_items';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['assessment_id', 'question_id', 'score'];
    protected $useTimestamps = true;

    // [question_id => score] for a single assessment
    public function getMapByAssessment(int $assessmentId): array
    {
        $rows = $this->where('assessment_id', $assessmentId)->findAll();
        return array_column($rows, 'score', 'question_id');
    }

    // Save scores (delete-then-insertBatch)
    public function saveForAssessment(int $assessmentId, array $scores): void
    {
        $db  = db_connect();
        $db->table('tna_assessment_items')->where('assessment_id', $assessmentId)->delete();
        $now  = date('Y-m-d H:i:s');
        $rows = [];
        foreach ($scores as $qId => $score) {
            $score = (int)$score;
            if ($score < 1 || $score > 5) continue;
            $rows[] = [
                'assessment_id' => $assessmentId,
                'question_id'   => (int)$qId,
                'score'         => $score,
                'created_at'    => $now,
                'updated_at'    => $now,
            ];
        }
        if ($rows) $db->table('tna_assessment_items')->insertBatch($rows);
    }

    // Returns [competency_id, assessor_type, avg_level] — level = round(avg(score))
    public function getResultForEmployee(int $periodId, int $empId): array
    {
        $db = db_connect();

        $assessments = $db->table('tna_assessments')
            ->where('period_id', $periodId)
            ->where('employee_id', $empId)
            ->where('status', 'submitted')
            ->get()->getResultArray();

        if (empty($assessments)) return [];

        $assessmentIds = array_column($assessments, 'id');
        $assessorMap   = array_column($assessments, 'assessor_type', 'id');

        $items = $db->table('tna_assessment_items i')
            ->select('i.assessment_id, i.question_id, i.score, cq.competency_id')
            ->join('competency_questions cq', 'cq.id = i.question_id')
            ->whereIn('i.assessment_id', $assessmentIds)
            ->where('i.score IS NOT NULL')
            ->get()->getResultArray();

        // [comp_id => [assessor_type => [scores]]]
        $byCompType = [];
        foreach ($items as $item) {
            $type = $assessorMap[$item['assessment_id']];
            $byCompType[$item['competency_id']][$type][] = (int)$item['score'];
        }

        $out = [];
        foreach ($byCompType as $compId => $byType) {
            foreach ($byType as $type => $scores) {
                $out[] = [
                    'competency_id' => $compId,
                    'assessor_type' => $type,
                    'avg_level'     => round(array_sum($scores) / count($scores), 2),
                    'rater_count'   => count($scores),
                ];
            }
        }
        return $out;
    }
}
