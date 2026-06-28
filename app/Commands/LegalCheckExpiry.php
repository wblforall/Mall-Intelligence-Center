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
        $count = 0;

        // Perizinan
        $permits = $db->table('legal_permits')
            ->where('tanggal_berakhir IS NOT NULL')
            ->where('tanggal_berakhir <=', $d30)->where('tanggal_berakhir >=', $today)
            ->where('status', 'active')->get()->getResultArray();
        foreach ($permits as $row) {
            $days = (int)(new \DateTime())->diff(new \DateTime($row['tanggal_berakhir']))->format('%r%a');
            $this->writeWarning('legal_permit', $row['id'], 'Izin: ' . $row['nama_izin'], $days);
            $count++;
        }

        // SPK
        $spk = $db->table('legal_spk')
            ->where('tanggal_selesai <=', $d30)->where('tanggal_selesai >=', $today)
            ->whereIn('status', ['draft', 'aktif'])->get()->getResultArray();
        foreach ($spk as $row) {
            $days = (int)(new \DateTime())->diff(new \DateTime($row['tanggal_selesai']))->format('%r%a');
            $this->writeWarning('legal_spk', $row['id'], 'SPK: ' . $row['nama_vendor'] . ' (' . $row['nomor_spk'] . ')', $days);
            $count++;
        }

        // PKS
        $pks = $db->table('legal_pks')
            ->where('tanggal_berakhir <=', $d30)->where('tanggal_berakhir >=', $today)
            ->whereIn('status', ['active', 'draft'])->get()->getResultArray();
        foreach ($pks as $row) {
            $days = (int)(new \DateTime())->diff(new \DateTime($row['tanggal_berakhir']))->format('%r%a');
            $this->writeWarning('legal_pks', $row['id'], 'PKS: ' . $row['pihak_kedua'] . ' (' . $row['nomor_pks'] . ')', $days);
            $count++;
        }

        // PSM Mall
        $psmMall = $db->table('legal_psm_mall')
            ->where('tanggal_berakhir <=', $d30)->where('tanggal_berakhir >=', $today)
            ->whereIn('status', ['active', 'draft'])->get()->getResultArray();
        foreach ($psmMall as $row) {
            $days = (int)(new \DateTime())->diff(new \DateTime($row['tanggal_berakhir']))->format('%r%a');
            $this->writeWarning('legal_psm_mall', $row['id'], 'PSM Mall: ' . $row['nama_tenant'] . ' (' . $row['nomor_psm'] . ')', $days);
            $count++;
        }

        // PSM Developer
        $psmDev = $db->table('legal_psm_developer')
            ->where('tanggal_berakhir <=', $d30)->where('tanggal_berakhir >=', $today)
            ->whereIn('status', ['active', 'draft'])->get()->getResultArray();
        foreach ($psmDev as $row) {
            $days = (int)(new \DateTime())->diff(new \DateTime($row['tanggal_berakhir']))->format('%r%a');
            $this->writeWarning('legal_psm_developer', $row['id'], 'PSM Dev: ' . $row['nama_developer'] . ' (' . $row['nomor_psm'] . ')', $days);
            $count++;
        }

        // PSM Gudang
        $psmGudang = $db->table('legal_psm_gudang')
            ->where('tanggal_berakhir <=', $d30)->where('tanggal_berakhir >=', $today)
            ->whereIn('status', ['active', 'draft'])->get()->getResultArray();
        foreach ($psmGudang as $row) {
            $days = (int)(new \DateTime())->diff(new \DateTime($row['tanggal_berakhir']))->format('%r%a');
            $this->writeWarning('legal_psm_gudang', $row['id'], 'PSM Gudang: ' . $row['nama_penyewa'] . ' (' . $row['nomor_psm'] . ')', $days);
            $count++;
        }

        // Kontrak Pameran
        $pameran = $db->table('legal_kontrak_pameran')
            ->where('tanggal_selesai <=', $d30)->where('tanggal_selesai >=', $today)
            ->whereIn('status', ['draft', 'aktif'])->get()->getResultArray();
        foreach ($pameran as $row) {
            $days = (int)(new \DateTime())->diff(new \DateTime($row['tanggal_selesai']))->format('%r%a');
            $this->writeWarning('legal_kontrak_pameran', $row['id'], 'Pameran: ' . $row['nama_event'] . ' (' . $row['nomor_kontrak'] . ')', $days);
            $count++;
        }

        CLI::write("[legal:check-expiry] {$count} item peringatan ditulis.", 'green');
    }

    private function writeWarning(string $module, int $targetId, string $label, int $daysLeft): void
    {
        $level  = $daysLeft <= 7 ? 'KRITIS' : 'PERINGATAN';
        $action = 'expiry_warning';

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
