<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEvalToSponsorshipPrograms extends Migration
{
    public function up()
    {
        $this->forge->addColumn('sponsorship_programs', [
            'eval_status' => [
                'type'       => 'ENUM',
                'constraint' => ['berhasil', 'sebagian', 'gagal'],
                'null'       => true,
                'default'    => null,
                'after'      => 'locked_at',
            ],
            'eval_kendala' => [
                'type'  => 'TEXT',
                'null'  => true,
                'after' => 'eval_status',
            ],
            'eval_rekomendasi' => [
                'type'  => 'TEXT',
                'null'  => true,
                'after' => 'eval_kendala',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('sponsorship_programs', ['eval_status', 'eval_kendala', 'eval_rekomendasi']);
    }
}
