<?php

namespace App\Controllers;

use App\Models\TrainingProgramModel;
use App\Models\TrainingBudgetModel;
use App\Models\DepartmentModel;

class PeopleDashboard extends BaseController
{
    public function index()
    {
        $db    = db_connect();
        $tahun = (int)date('Y');

        // Employee status counts
        $empStats = $db->query("SELECT status, COUNT(*) AS cnt FROM employees GROUP BY status")->getResultArray();
        $empMap   = array_column($empStats, 'cnt', 'status');

        // Certificates expiring within 60 days (active employees only)
        $certExpiring = $db->query("
            SELECT e.nama AS emp_nama, e.jabatan, d.name AS dept_name,
                   ec.nama_sertifikat, ec.tanggal_kadaluarsa,
                   DATEDIFF(ec.tanggal_kadaluarsa, CURDATE()) AS days_left
            FROM employee_certificates ec
            JOIN employees e ON e.id = ec.employee_id
            LEFT JOIN departments d ON d.id = e.dept_id
            WHERE ec.tanggal_kadaluarsa IS NOT NULL
              AND ec.tanggal_kadaluarsa >= CURDATE()
              AND ec.tanggal_kadaluarsa <= DATE_ADD(CURDATE(), INTERVAL 60 DAY)
              AND e.status = 'aktif'
            ORDER BY ec.tanggal_kadaluarsa ASC
        ")->getResultArray();

        // Upcoming/ongoing training
        $upcomingTraining = $db->query("
            SELECT p.id, p.nama, p.tipe, p.tanggal_mulai, p.status,
                   COUNT(tp.id) AS peserta_count
            FROM training_programs p
            LEFT JOIN training_participants tp ON tp.program_id = p.id
            WHERE p.status IN ('scheduled', 'ongoing')
            GROUP BY p.id
            ORDER BY FIELD(p.status,'ongoing','scheduled'), p.tanggal_mulai ASC
            LIMIT 6
        ")->getResultArray();

        // Count training this month
        $trainingThisMonth = (int)$db->query("
            SELECT COUNT(*) AS cnt FROM training_programs
            WHERE status IN ('scheduled','ongoing','completed')
              AND YEAR(tanggal_mulai) = ? AND MONTH(tanggal_mulai) = ?
        ", [$tahun, (int)date('m')])->getRow()->cnt;

        // Open TNA periods count
        $activeTnaPeriods = (int)$db->query(
            "SELECT COUNT(*) AS cnt FROM tna_periods WHERE status = 'open'"
        )->getRow()->cnt;

        // Latest TNA period with submitted data → gap per competency
        $latestPeriod = $db->query("
            SELECT p.id, p.nama FROM tna_periods p
            JOIN tna_assessments a ON a.period_id = p.id
            WHERE a.status = 'submitted'
            ORDER BY p.id DESC LIMIT 1
        ")->getRowArray();

        $tnaGaps = [];
        if ($latestPeriod) {
            $tnaGaps = $db->query("
                SELECT c.id, c.nama, c.kategori,
                       ROUND(AVG(ct.target_level), 2)                                      AS avg_target,
                       ROUND(AVG(tai.score), 2)                                            AS avg_assessed,
                       ROUND(AVG(ct.target_level) - AVG(tai.score), 2)                    AS avg_gap
                FROM tna_assessment_items tai
                JOIN competency_questions cq ON cq.id = tai.question_id
                JOIN tna_assessments ta      ON ta.id = tai.assessment_id
                JOIN competencies c          ON c.id  = cq.competency_id
                JOIN employees e             ON e.id  = ta.employee_id
                JOIN competency_targets ct   ON ct.competency_id = cq.competency_id
                     AND ct.dept_id = e.dept_id AND ct.jabatan IS NULL
                WHERE ta.period_id = ? AND ta.status = 'submitted'
                  AND tai.score IS NOT NULL
                GROUP BY c.id, c.nama, c.kategori
                HAVING avg_gap > 0
                ORDER BY avg_gap DESC
                LIMIT 8
            ", [$latestPeriod['id']])->getResultArray();
        }

        // Budget rows for current year
        $realisasiMap = (new TrainingProgramModel())->getRealisasiByDeptYear($tahun);
        $budgetMap    = (new TrainingBudgetModel())->getMapByYear($tahun);
        $departments  = (new DepartmentModel())->orderBy('name')->findAll();

        $budgetRows     = [];
        $totalAnggaran  = 0;
        $totalRealisasi = 0;
        foreach ($departments as $d) {
            $anggaran  = (float)($budgetMap[$d['id']]['anggaran'] ?? 0);
            $realisasi = (float)($realisasiMap[$d['id']]['total_realisasi'] ?? 0);
            if ($anggaran > 0 || $realisasi > 0) {
                $budgetRows[] = [
                    'dept_name' => $d['name'],
                    'anggaran'  => $anggaran,
                    'realisasi' => $realisasi,
                    'pct'       => $anggaran > 0 ? min(round($realisasi / $anggaran * 100, 1), 999) : null,
                ];
                $totalAnggaran  += $anggaran;
                $totalRealisasi += $realisasi;
            }
        }

        // Employee count by dept (active only)
        $empByDept = $db->query("
            SELECT d.name AS dept_name, COUNT(e.id) AS cnt
            FROM employees e
            JOIN departments d ON d.id = e.dept_id
            WHERE e.status = 'aktif'
            GROUP BY e.dept_id, d.name
            ORDER BY cnt DESC
        ")->getResultArray();

        return view('people/dashboard', [
            'user'              => $this->currentUser(),
            'tahun'             => $tahun,
            'empMap'            => $empMap,
            'certExpiring'      => $certExpiring,
            'certExpiring30'    => count(array_filter($certExpiring, fn($c) => $c['days_left'] <= 30)),
            'upcomingTraining'  => $upcomingTraining,
            'trainingThisMonth' => $trainingThisMonth,
            'activeTnaPeriods'  => $activeTnaPeriods,
            'latestPeriod'      => $latestPeriod,
            'tnaGaps'           => $tnaGaps,
            'budgetRows'        => $budgetRows,
            'totalAnggaran'     => $totalAnggaran,
            'totalRealisasi'    => $totalRealisasi,
            'empByDept'         => $empByDept,
        ]);
    }
}
