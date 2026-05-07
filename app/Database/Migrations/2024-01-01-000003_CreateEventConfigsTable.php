<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEventConfigsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'                          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'event_id'                    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            // Cost breakdown
            'royalty_character'           => ['type' => 'BIGINT', 'default' => 0],
            'operational_mg'              => ['type' => 'BIGINT', 'default' => 0],
            'production_decor'            => ['type' => 'BIGINT', 'default' => 0],
            'promotion_media'             => ['type' => 'BIGINT', 'default' => 0],
            'security_cost'               => ['type' => 'BIGINT', 'default' => 0],
            'other_cost'                  => ['type' => 'BIGINT', 'default' => 0],
            // Targets (stored as decimal 0-1 for percentages)
            'target_traffic_uplift'       => ['type' => 'DECIMAL', 'constraint' => '8,4', 'default' => 0.30],
            'target_engagement_rate'      => ['type' => 'DECIMAL', 'constraint' => '8,4', 'default' => 0.15],
            'target_member_conversion'    => ['type' => 'DECIMAL', 'constraint' => '8,4', 'default' => 0.20],
            'target_transaction_conv'     => ['type' => 'DECIMAL', 'constraint' => '8,4', 'default' => 0.10],
            'target_voucher_redemption'   => ['type' => 'DECIMAL', 'constraint' => '8,4', 'default' => 0.35],
            'target_sales_uplift'         => ['type' => 'DECIMAL', 'constraint' => '8,4', 'default' => 0.25],
            'target_sponsor_coverage'     => ['type' => 'DECIMAL', 'constraint' => '8,4', 'default' => 0.40],
            'target_roi_direct'           => ['type' => 'DECIMAL', 'constraint' => '8,4', 'default' => 1.00],
            'target_repeat_visit'         => ['type' => 'DECIMAL', 'constraint' => '8,4', 'default' => 0.15],
            'created_at'                  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'                  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('event_id');
        $this->forge->createTable('event_configs');
    }

    public function down(): void
    {
        $this->forge->dropTable('event_configs');
    }
}
