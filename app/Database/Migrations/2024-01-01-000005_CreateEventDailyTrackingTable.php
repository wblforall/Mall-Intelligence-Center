<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEventDailyTrackingTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'                    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'event_id'              => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'tracking_date'         => ['type' => 'DATE'],
            'day_number'            => ['type' => 'INT', 'constraint' => 3],
            'day_type'              => ['type' => 'ENUM', 'constraint' => ['Weekday', 'Weekend/High Season'], 'default' => 'Weekday'],
            // Traffic
            'actual_traffic'        => ['type' => 'INT', 'null' => true],
            'event_area_visitors'   => ['type' => 'INT', 'null' => true],
            // Engagement
            'mg_registration'       => ['type' => 'INT', 'default' => 0],
            'photo_game_participants'=> ['type' => 'INT', 'default' => 0],
            'qr_scans'              => ['type' => 'INT', 'default' => 0],
            // Loyalty
            'new_pam_members'       => ['type' => 'INT', 'default' => 0],
            'voucher_claims'        => ['type' => 'INT', 'default' => 0],
            'voucher_redemptions'   => ['type' => 'INT', 'default' => 0],
            // Transaction
            'receipt_uploads'       => ['type' => 'INT', 'default' => 0],
            'actual_tenant_sales'   => ['type' => 'BIGINT', 'null' => true],
            // Revenue
            'sponsor_revenue'       => ['type' => 'BIGINT', 'default' => 0],
            'booth_cl_revenue'      => ['type' => 'BIGINT', 'default' => 0],
            'media_revenue'         => ['type' => 'BIGINT', 'default' => 0],
            'parking_actual'        => ['type' => 'BIGINT', 'null' => true],
            'notes'                 => ['type' => 'TEXT', 'null' => true],
            'created_by'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at'            => ['type' => 'DATETIME', 'null' => true],
            'updated_at'            => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['event_id', 'tracking_date']);
        $this->forge->createTable('event_daily_tracking');
    }

    public function down(): void
    {
        $this->forge->dropTable('event_daily_tracking');
    }
}
