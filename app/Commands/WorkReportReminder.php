<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Libraries\EmailNotifier;

class WorkReportReminder extends BaseCommand
{
    protected $group       = 'MIC';
    protected $name        = 'mic:work-report-reminder';
    protected $description = 'Kirim notifikasi Senin pagi ke Dept Head yang belum update inisiatif minggu ini.';

    public function run(array $params)
    {
        $db = db_connect();

        // Senin minggu ini (awal pekan)
        $monday = date('Y-m-d', strtotime('monday this week'));

        // Ambil semua inisiatif aktif beserta Dept Head (user tertinggi di dept)
        $initiatives = $db->table('work_initiatives wi')
            ->select('wi.id, wi.judul, wi.dept_id, d.name AS dept_name,
                      e.id AS emp_id, e.nama AS emp_nama, e.email AS emp_email,
                      (SELECT MAX(wu.created_at) FROM work_initiative_updates wu WHERE wu.initiative_id = wi.id) AS last_update')
            ->join('departments d', 'd.id = wi.dept_id', 'left')
            ->join('users u', 'u.id = (
                SELECT u2.id FROM users u2
                INNER JOIN employees e2 ON e2.user_id = u2.id
                INNER JOIN jabatans j2 ON j2.id = e2.jabatan_id
                WHERE e2.dept_id = wi.dept_id AND e2.status = "aktif" AND j2.grade = (
                    SELECT MIN(j3.grade) FROM employees e3
                    INNER JOIN jabatans j3 ON j3.id = e3.jabatan_id
                    WHERE e3.dept_id = wi.dept_id AND e3.status = "aktif" AND j3.grade >= 5
                )
                LIMIT 1
            )', 'left')
            ->join('employees e', 'e.user_id = u.id', 'left')
            ->where('wi.is_active', 1)
            ->get()->getResultArray();

        // Filter: belum ada update minggu ini
        $toNotify = [];
        foreach ($initiatives as $ini) {
            $lastUpdate = $ini['last_update'] ?? null;
            $updatedThisWeek = $lastUpdate && $lastUpdate >= $monday . ' 00:00:00';
            if (! $updatedThisWeek && ! empty($ini['emp_email'])) {
                $toNotify[$ini['emp_email']][] = $ini;
            }
        }

        $sent = 0;
        foreach ($toNotify as $email => $items) {
            $empNama  = $items[0]['emp_nama'] ?? 'Dept Head';
            $deptName = $items[0]['dept_name'] ?? '';
            $count    = count($items);

            $body = EmailNotifier::workReportReminder($empNama, $deptName, $items, $monday);
            $subject = "Reminder: Update Inisiatif Kerja Minggu Ini — {$deptName}";

            if (EmailNotifier::send($email, $subject, $body)) {
                $sent++;
                CLI::write("Sent to {$email} ({$count} inisiatif)", 'green');
            } else {
                CLI::write("Failed: {$email}", 'red');
            }
        }

        CLI::write("Done. {$sent} email terkirim.", 'cyan');
    }
}
