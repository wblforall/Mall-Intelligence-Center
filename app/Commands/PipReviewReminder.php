<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\PipPlanModel;
use App\Libraries\EmailNotifier;

class PipReviewReminder extends BaseCommand
{
    protected $group       = 'MIC';
    protected $name        = 'mic:pip-review-reminder';
    protected $description = 'Kirim email pengingat review PIP H-1 ke atasan langsung.';

    public function run(array $params)
    {
        $model    = new PipPlanModel();
        $tomorrow = date('Y-m-d', strtotime('+1 day'));

        $db   = db_connect();
        $plans = $db->table('pip_plans p')
            ->select('p.*, e.nama as employee_nama, e.jabatan,
                      a.nama as atasan_nama, a.jabatan as atasan_jabatan,
                      a.no_hp as atasan_no_hp, a.email as atasan_email,
                      (SELECT MAX(tanggal_review) FROM pip_reviews WHERE pip_id = p.id) as last_review_date')
            ->join('employees e', 'e.id = p.employee_id', 'left')
            ->join('employees a', 'a.id = e.atasan_id', 'left')
            ->whereIn('p.status', ['aktif', 'diperpanjang'])
            ->get()->getResultArray();

        $sent = 0;
        foreach ($plans as $plan) {
            $nextDate = PipPlanModel::nextReviewDate($plan);
            if ($nextDate !== $tomorrow) continue;
            if (empty($plan['atasan_email'])) continue;

            $body = EmailNotifier::pipReviewReminder($plan, $nextDate);
            if (EmailNotifier::send($plan['atasan_email'], 'Pengingat Review PIP — ' . $plan['employee_nama'], $body)) {
                $sent++;
                CLI::write('Sent to ' . $plan['atasan_email'] . ' for ' . $plan['employee_nama'], 'green');
            } else {
                CLI::write('Failed: ' . $plan['atasan_email'], 'red');
            }
        }

        CLI::write("Done. {$sent} email terkirim.", 'cyan');
    }
}
