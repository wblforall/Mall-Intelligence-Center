<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCreativeFilesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'creative_item_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'file_name'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'original_name'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'catatan'          => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'uploaded_by'      => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('creative_item_id');
        $this->forge->createTable('creative_files');
    }

    public function down()
    {
        $this->forge->dropTable('creative_files');
    }
}
