<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCompetencyClusters extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'        => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'nama'      => ['type' => 'VARCHAR', 'constraint' => 100],
            'deskripsi' => ['type' => 'TEXT', 'null' => true],
            'urutan'    => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('competency_clusters');

        $this->forge->addColumn('competencies', [
            'cluster_id' => [
                'type'       => 'INT',
                'unsigned'   => true,
                'null'       => true,
                'default'    => null,
                'after'      => 'id',
            ],
        ]);

        $this->db->query('ALTER TABLE competencies ADD CONSTRAINT fk_comp_cluster
            FOREIGN KEY (cluster_id) REFERENCES competency_clusters(id) ON DELETE SET NULL');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE competencies DROP FOREIGN KEY fk_comp_cluster');
        $this->forge->dropColumn('competencies', 'cluster_id');
        $this->forge->dropTable('competency_clusters');
    }
}
