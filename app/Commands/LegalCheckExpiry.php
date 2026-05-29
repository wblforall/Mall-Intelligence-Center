<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Libraries\ActivityLog;

class LegalCheckExpiry extends BaseCommand
{
    protected $group       = 'MIC';
    protected $name        = 'legal:check-expiry';
    protected $description = 'Cek dokumen legal yang akan berakhir dan tulis peringatan ke activity_logs.';

    public function run(array $params)
    {
        $db    = db_connect();
        $today = date('Y-m-d');
        $d30   = date('Y-m-d', strtotime('+30 days'));
        $d7    = date('Y-m-d', strtotime('+7 days'));
        $count = 0;

        // Leases
        $leases = $db->table('legal_leases')
            ->where('tanggal_berakhir <=', $d30)->where('tanggal_berakhir >=', $today)
            ->whereIn('status', ['active','draft'])->get()->getResultArray();
        foreach ($leases as $row) {
            $days = (int)(new \DateTime())->diff(new \DateTime($row['tanggal_berakhir']))->format('%r%a');
            $this->writeWarning('legal_lease', $row['id'], 'Sewa: '.$row['tenant_name'], $days);
            $count++;
        }

        // Permits
        $permits = $db->table('legal_permits')
            ->where('tanggal_berakhir IS NOT NULL')
            ->where('tanggal_berakhir <=', $d30)->where('tanggal_berakhir >=', $today)
            ->where('status', 'active')->get()->getResultArray();
        foreach ($permits as $row) {
            $days = (int)(new \DateTime())->diff(new \DateTime($row['tanggal_berakhir']))->format('%r%a');
            $this->writeWarning('legal_permit', $row['id'], 'Izin: '.$row['nama_izin'], $days);
            $count++;
        }

        // Contracts
        $contracts = $db->table('legal_contracts')
            ->where('tanggal_berakhir <=', $d30)->where('tanggal_berakhir >=', $today)
            ->whereIn('status', ['active','draft'])->get()->getResultArray();
        foreach ($contracts as $row) {
            $days = (int)(new \DateTime())->diff(new \DateTime($row['tanggal_berakhir']))->format('%r%a');
            $this->writeWarning('legal_contract', $row['id'], 'Vendor: '.$row['nama_vendor'], $days);
            $count++;
        }

        CLI::write("[legal:check-expiry] {$count} item peringatan ditulis.", 'green');
    }

    private function writeWarning(string $module, int $targetId, string $label, int $daysLeft): void
    {
        $level  = $daysLeft <= 7 ? 'KRITIS' : 'PERINGATAN';
        $action = 'expiry_warning';

        // Hindari duplikat pada hari yang sama
        $db = db_connect();
        $exists = $db->table('activity_logs')
            ->where('module', $module)
            ->where('action', $action)
            ->where('target_id', $targetId)
            ->where('created_at >=', date('Y-m-d') . ' 00:00:00')
            ->countAllResults();

        if ($exists) return;

        ActivityLog::write(
            $module,
            $action,
            $targetId,
            "[{$level}] {$label} — berakhir dalam {$daysLeft} hari"
        );
    }
}
