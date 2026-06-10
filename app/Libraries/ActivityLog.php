<?php

namespace App\Libraries;

class ActivityLog
{
    private static ?array $_before = null;
    private static ?array $_after  = null;

    // Peta module key (nama tabel internal) → label ramah-baca untuk Activity Log
    public const MODULE_LABELS = [
        'auth'                          => 'Login / Autentikasi',
        'app_settings'                  => 'Pengaturan Aplikasi',
        'user'                          => 'User',
        'role'                          => 'Role / Hak Akses',
        'department'                    => 'Departemen',
        'division'                      => 'Divisi',
        'jabatan'                       => 'Jabatan',
        'public_holiday'                => 'Hari Libur Nasional',
        // Event
        'event'                         => 'Event',
        'event_config'                  => 'Konfigurasi Event',
        'event_baseline'                => 'Baseline Event',
        'event_location'                => 'Lokasi Event',
        'event_tracking'                => 'Tracking Harian Event',
        'event_tenant'                  => 'Tenant Event',
        'event_tenant_impact'           => 'Dampak Tenant Event',
        'completion'                    => 'Penyelesaian Modul Event',
        'content'                       => 'Content & Rundown',
        'content_realisasi'             => 'Realisasi Content',
        'exhibitor'                     => 'Exhibition',
        'exhibitor_program'             => 'Program Exhibition',
        'exhibitor_target'              => 'Target Exhibition',
        'sponsor'                       => 'Sponsor (Event)',
        'sponsor_realisasi'             => 'Realisasi Sponsor (Event)',
        'other_cost'                    => 'Biaya Lain-lain',
        // Creative / VM
        'creative'                      => 'Creative (Event)',
        'creative_standalone'           => 'Creative — Standalone',
        'creative_standalone_insight'   => 'Insight Creative Standalone',
        'creative_standalone_realisasi' => 'Realisasi Creative Standalone',
        'vm'                            => 'VM (Event)',
        'vm_realisasi'                  => 'Realisasi VM (Event)',
        'vm_standalone'                 => 'VM — Standalone',
        'vm_standalone_realisasi'       => 'Realisasi VM Standalone',
        // Media Promo
        'promo_media_spots'             => 'Titik Media Promo',
        'promo_media_usage'             => 'Request Media Promo',
        // Loyalty
        'loyalty'                       => 'Loyalty',
        'loyalty_program'               => 'Program Loyalty',
        'loyalty_realisasi'             => 'Realisasi Loyalty',
        'loyalty_hadiah'                => 'Hadiah Loyalty',
        'loyalty_hadiah_item'           => 'Item Hadiah Loyalty',
        'loyalty_hadiah_realisasi'      => 'Realisasi Hadiah Loyalty',
        'loyalty_voucher'               => 'Voucher Loyalty',
        'loyalty_voucher_item'          => 'Item Voucher Loyalty',
        'loyalty_voucher_realisasi'     => 'Realisasi Voucher Loyalty',
        'tenant'                        => 'Master Tenant',
        'loyalty_analisa'               => 'Analisa Summary Loyalty',
        // Sponsorship (standalone)
        'sponsorship_program'           => 'Program Sponsorship',
        'sponsorship_sponsor'           => 'Sponsor (Sponsorship)',
        'sponsorship_realisasi'         => 'Realisasi Sponsorship',
        // Stock
        'stock_barang'                  => 'Stok Barang',
        'stock_voucher_batch'           => 'Batch Voucher',
        // Traffic
        'traffic'                       => 'Daily Traffic',
        'vehicles'                      => 'Traffic Kendaraan',
        'door'                          => 'Master Pintu',
        // People Development
        'employee'                      => 'Karyawan',
        'employee_certificate'          => 'Sertifikat Karyawan',
        'employee_position'             => 'Riwayat Jabatan Karyawan',
        'competency'                    => 'Kompetensi',
        'competency_cluster'            => 'Cluster Kompetensi',
        'competency_question'           => 'Pertanyaan Kompetensi',
        'competency_question_levels'    => 'Level Pertanyaan Kompetensi',
        'competency_import'             => 'Import Kompetensi',
        'competency_dept_map'           => 'Assign Kompetensi (Dept)',
        'competency_jabatan_map'        => 'Assign Kompetensi (Jabatan)',
        'competency_targets'            => 'Target Kompetensi',
        'tna_period'                    => 'Periode TNA',
        'tna_assessment'                => 'Penilaian TNA',
        'eei_period'                    => 'Periode EEI',
        'eei_period_activate'           => 'Aktivasi Periode EEI',
        'eei_dimension'                 => 'Dimensi EEI',
        'eei_question'                  => 'Pertanyaan EEI',
        'eei_response'                  => 'Respon EEI',
        'pip_plan'                      => 'PIP',
        'pip_review'                    => 'Review PIP',
        'pip_aspek_master'             => 'Master Aspek PIP',
        'idp_plan'                      => 'IDP',
        'idp_item'                      => 'Item IDP',
        'training_program'              => 'Program Training',
        'training_participant'          => 'Peserta Training',
        'training_budget'               => 'Budget Training',
    ];

    // Label ramah-baca untuk sebuah module key; fallback humanize bila tak terdaftar
    public static function moduleLabel(string $key): string
    {
        return self::MODULE_LABELS[$key] ?? ucwords(str_replace('_', ' ', $key));
    }

    public static function captureBefore(mixed $data): void
    {
        self::$_before = is_array($data) ? $data : null;
    }

    public static function captureAfter(array $data): void
    {
        self::$_after = $data;
    }

    public static function write(
        string  $action,
        string  $module,
        ?string $targetId    = null,
        ?string $targetLabel = null,
        array   $detail      = []
    ): void {
        if (self::$_before !== null) {
            $detail['_before'] = self::$_before;
            self::$_before = null;
        }
        if (self::$_after !== null) {
            $detail['_after'] = self::$_after;
            self::$_after = null;
        }

        $session = session();
        $ip      = service('request')->getIPAddress();
        $loopback = ['127.0.0.1', '::1', '0:0:0:0:0:0:0:1'];
        $computerName = in_array($ip, $loopback) ? 'localhost' : $ip;

        db_connect()->table('activity_logs')->insert([
            'user_id'      => $session->get('user_id'),
            'user_name'    => $session->get('user_name') ?? 'System',
            'user_role'    => $session->get('user_role') ?? '',
            'action'       => $action,
            'module'       => $module,
            'target_id'    => $targetId,
            'target_label' => $targetLabel,
            'detail'       => !empty($detail) ? json_encode($detail, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : null,
            'ip_address'    => $ip,
            'computer_name' => $computerName,
            'created_at'    => date('Y-m-d H:i:s'),
        ]);
    }
}
