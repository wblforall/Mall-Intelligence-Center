<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTrainingParticipantsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'program_id'       => ['type' => 'INT', 'unsigned' => true],
            'employee_id'      => ['type' => 'INT', 'unsigned' => true],
            'status_kehadiran' => ['type' => 'ENUM', 'constraint' => ['registered', 'hadir', 'tidak_hadir', 'dibatalkan'], 'default' => 'registered'],
            'pre_test'         => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'post_test'        => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'catatan'          => ['type' => 'TEXT', 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['program_id', 'employee_id']);
        $this->forge->createTable('training_participants');
    }

    public function down()
    {
        $this->forge->dropTable('training_participants', true);
    }
}
