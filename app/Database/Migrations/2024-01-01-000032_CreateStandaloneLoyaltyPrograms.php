<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateStandaloneLoyaltyPrograms extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'nama_program'      => ['type' => 'VARCHAR', 'constraint' => 255],
            'mekanisme'         => ['type' => 'TEXT', 'null' => true],
            'target_type'       => ['type' => 'ENUM', 'constraint' => ['member', 'evoucher'], 'null' => true],
            'target_peserta'    => ['type' => 'INT', 'null' => true],
            'target_penyerapan' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'total_voucher'     => ['type' => 'INT', 'null' => true],
            'budget'            => ['type' => 'BIGINT', 'default' => 0],
            'status'            => ['type' => 'ENUM', 'constraint' => ['active', 'inactive'], 'default' => 'active'],
            'catatan'           => ['type' => 'TEXT', 'null' => true],
            'created_by'        => ['type' => 'INT', 'null' => true],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('loyalty_programs');
    }

    public function down()
    {
        $this->forge->dropTable('loyalty_programs');
    }
}
