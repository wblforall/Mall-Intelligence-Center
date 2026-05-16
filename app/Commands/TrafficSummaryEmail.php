<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\AppSettingsModel;
use App\Libraries\EmailNotifier;

class TrafficSummaryEmail extends BaseCommand
{
    protected $group       = 'MIC';
    protected $name        = 'mic:traffic-summary-email';
    protected $description = 'Kirim email rekap traffic pengunjung harian ke daftar penerima.';

    public function run(array $params)
    {
        $tanggal = $params[0] ?? date('Y-m-d', strtotime('-1 day'));

        $db   = db_connect();
        $rows = $db->table('daily_traffic')
            ->select('mall, SUM(jumlah_pengunjung) AS total')
            ->where('tanggal', $tanggal)
            ->groupBy('mall')
            ->orderBy('mall')
            ->get()->getResultArray();

        if (empty($rows)) {
            CLI::write("Tidak ada data traffic untuk {$tanggal}.", 'yellow');
            return;
        }

        $mallLabels = ['ewalk' => 'eWalk', 'pentacity' => 'Pentacity'];
        $data = [];
        foreach ($rows as $r) {
            $label        = $mallLabels[$r['mall']] ?? ucfirst($r['mall']);
            $data[$label] = (int)$r['total'];
        }

        $recipients = (new AppSettingsModel())->getEmails('traffic_summary_emails');
        if (empty($recipients)) {
            CLI::write('Tidak ada penerima terdaftar di app_settings.', 'yellow');
            return;
        }

        $subject = 'Traffic Summary — ' . date('d M Y', strtotime($tanggal));
        $body    = EmailNotifier::trafficSummary($data, $tanggal);

        $results = EmailNotifier::sendBulk($recipients, $subject, fn($to) => $body);
        CLI::write("Terkirim: {$results['sent']}, Gagal: {$results['failed']}", 'cyan');
        if ($results['errors']) {
            CLI::write('Gagal ke: ' . implode(', ', $results['errors']), 'red');
        }
    }
}
